/**
 * PhotoImporter.swift
 *
 * Imports trip data from photo library GPS metadata.
 * Scans photos, groups by location/date, and creates trips.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation
import Photos
import CoreLocation
import UIKit

/// Detected trip from photo GPS data
struct DetectedPhotoTrip: Identifiable {
    let id = UUID()
    let country: String
    let countryCode: String
    let startDate: Date
    let endDate: Date
    let photoCount: Int
    let samplePhoto: UIImage?
    let isSchengen: Bool

    var durationDays: Int {
        Calendar.current.dateComponents([.day], from: startDate, to: endDate).day ?? 0 + 1
    }
}

/// Photo import progress
struct PhotoImportProgress {
    let current: Int
    let total: Int
    let phase: Phase

    enum Phase {
        case scanning
        case geocoding
        case grouping
        case complete
    }

    var percentage: Float {
        guard total > 0 else { return 0 }
        return Float(current) / Float(total)
    }
}

/// Photo importer service
actor PhotoImporter {
    static let shared = PhotoImporter()

    private let geocoder = CLGeocoder()
    private var geocodeCache: [String: (code: String, name: String)] = [:]

    // MARK: - Public Methods

    /// Request photo library access
    func requestAccess() async -> Bool {
        let status = await PHPhotoLibrary.requestAuthorization(for: .readWrite)
        return status == .authorized || status == .limited
    }

    /// Check current authorization status
    func checkAuthorizationStatus() -> PHAuthorizationStatus {
        PHPhotoLibrary.authorizationStatus(for: .readWrite)
    }

    /// Scan photos for GPS metadata within date range
    func scanPhotos(
        from startDate: Date,
        to endDate: Date,
        progress: @escaping (PhotoImportProgress) -> Void
    ) async throws -> [DetectedPhotoTrip] {

        // Fetch photos with GPS in date range
        let fetchOptions = PHFetchOptions()
        fetchOptions.predicate = NSPredicate(
            format: "creationDate >= %@ AND creationDate <= %@",
            startDate as NSDate,
            endDate as NSDate
        )
        fetchOptions.sortDescriptors = [
            NSSortDescriptor(key: "creationDate", ascending: true)
        ]

        let assets = PHAsset.fetchAssets(with: .image, options: fetchOptions)

        // Phase 1: Scan for photos with GPS
        var locationsByDate: [Date: [(location: CLLocation, asset: PHAsset)]] = [:]
        let total = assets.count
        var scanned = 0

        progress(PhotoImportProgress(current: 0, total: total, phase: .scanning))

        assets.enumerateObjects { asset, _, _ in
            if let location = asset.location,
               let date = asset.creationDate {
                let dayStart = Calendar.current.startOfDay(for: date)
                locationsByDate[dayStart, default: []].append((location, asset))
            }
            scanned += 1
            if scanned % 50 == 0 {
                progress(PhotoImportProgress(current: scanned, total: total, phase: .scanning))
            }
        }

        progress(PhotoImportProgress(current: total, total: total, phase: .scanning))

        guard !locationsByDate.isEmpty else {
            return []
        }

        // Phase 2: Geocode locations
        progress(PhotoImportProgress(current: 0, total: locationsByDate.count, phase: .geocoding))

        var countriesByDate: [Date: (code: String, name: String, photos: [PHAsset])] = [:]
        var geocoded = 0

        for (date, locationsAndAssets) in locationsByDate.sorted(by: { $0.key < $1.key }) {
            // Use the first location of the day as representative
            guard let primaryLocation = locationsAndAssets.first?.location else { continue }

            if let country = try? await geocodeLocation(primaryLocation) {
                countriesByDate[date] = (
                    code: country.code,
                    name: country.name,
                    photos: locationsAndAssets.map { $0.asset }
                )
            }

            geocoded += 1
            progress(PhotoImportProgress(current: geocoded, total: locationsByDate.count, phase: .geocoding))

            // Rate limit geocoding
            try? await Task.sleep(nanoseconds: 100_000_000) // 0.1 seconds
        }

        // Phase 3: Group consecutive days in same country into trips
        progress(PhotoImportProgress(current: 0, total: countriesByDate.count, phase: .grouping))

        let trips = await groupIntoTrips(countriesByDate)

        progress(PhotoImportProgress(current: trips.count, total: trips.count, phase: .complete))

        return trips
    }

    /// Import detected trips to the database
    func importTrips(_ trips: [DetectedPhotoTrip]) async throws {
        for trip in trips {
            let newTrip = Trip(
                startDate: trip.startDate,
                endDate: trip.endDate,
                country: trip.countryCode,
                category: trip.isSchengen ? .schengen : .nonSchengen,
                notes: "Imported from \(trip.photoCount) photos"
            )

            try await LocalDatabase.shared.insertTrip(newTrip)
            SyncManager.shared.queueTripForSync(newTrip)
        }
    }

    // MARK: - Private Methods

    private func geocodeLocation(_ location: CLLocation) async throws -> (code: String, name: String)? {
        // Check cache first
        let cacheKey = "\(Int(location.coordinate.latitude * 100)),\(Int(location.coordinate.longitude * 100))"
        if let cached = geocodeCache[cacheKey] {
            return cached
        }

        // Geocode
        let placemarks = try await geocoder.reverseGeocodeLocation(location)

        guard let placemark = placemarks.first,
              let countryCode = placemark.isoCountryCode,
              let countryName = placemark.country else {
            return nil
        }

        let result = (code: countryCode, name: countryName)
        geocodeCache[cacheKey] = result
        return result
    }

    private func groupIntoTrips(_ countriesByDate: [Date: (code: String, name: String, photos: [PHAsset])]) async -> [DetectedPhotoTrip] {
        var trips: [DetectedPhotoTrip] = []
        var currentTrip: (
            code: String,
            name: String,
            start: Date,
            end: Date,
            photoCount: Int,
            sampleAsset: PHAsset?
        )?

        let sortedDates = countriesByDate.keys.sorted()

        for date in sortedDates {
            guard let dayData = countriesByDate[date] else { continue }

            if let current = currentTrip {
                // Check if same country and consecutive day (within 1 day gap allowed)
                let dayDiff = Calendar.current.dateComponents([.day], from: current.end, to: date).day ?? 0

                if current.code == dayData.code && dayDiff <= 2 {
                    // Extend current trip
                    currentTrip = (
                        current.code,
                        current.name,
                        current.start,
                        date,
                        current.photoCount + dayData.photos.count,
                        current.sampleAsset ?? dayData.photos.first
                    )
                } else {
                    // Save current trip and start new one
                    if let thumbnail = await loadThumbnail(current.sampleAsset) {
                        trips.append(DetectedPhotoTrip(
                            country: current.name,
                            countryCode: current.code,
                            startDate: current.start,
                            endDate: current.end,
                            photoCount: current.photoCount,
                            samplePhoto: thumbnail,
                            isSchengen: SchengenCountries.isSchengen(code: current.code)
                        ))
                    }
                    currentTrip = (dayData.code, dayData.name, date, date, dayData.photos.count, dayData.photos.first)
                }
            } else {
                // Start first trip
                currentTrip = (dayData.code, dayData.name, date, date, dayData.photos.count, dayData.photos.first)
            }
        }

        // Don't forget the last trip
        if let current = currentTrip {
            let thumbnail = await loadThumbnail(current.sampleAsset)
            trips.append(DetectedPhotoTrip(
                country: current.name,
                countryCode: current.code,
                startDate: current.start,
                endDate: current.end,
                photoCount: current.photoCount,
                samplePhoto: thumbnail,
                isSchengen: SchengenCountries.isSchengen(code: current.code)
            ))
        }

        return trips
    }

    private func loadThumbnail(_ asset: PHAsset?) async -> UIImage? {
        guard let asset = asset else { return nil }

        return await withCheckedContinuation { continuation in
            let options = PHImageRequestOptions()
            options.deliveryMode = .fastFormat
            options.resizeMode = .fast
            options.isSynchronous = false

            PHImageManager.default().requestImage(
                for: asset,
                targetSize: CGSize(width: 100, height: 100),
                contentMode: .aspectFill,
                options: options
            ) { image, _ in
                continuation.resume(returning: image)
            }
        }
    }
}

// MARK: - Photo Import View Model

@MainActor
class PhotoImportViewModel: ObservableObject {
    @Published var hasAccess = false
    @Published var isScanning = false
    @Published var progress: PhotoImportProgress?
    @Published var detectedTrips: [DetectedPhotoTrip] = []
    @Published var selectedTrips: Set<UUID> = []
    @Published var error: String?
    @Published var importComplete = false

    @Published var startDate = Calendar.current.date(byAdding: .year, value: -1, to: Date())!
    @Published var endDate = Date()

    func checkAccess() async {
        let status = await PhotoImporter.shared.checkAuthorizationStatus()
        hasAccess = status == .authorized || status == .limited
    }

    func requestAccess() async {
        hasAccess = await PhotoImporter.shared.requestAccess()
    }

    func scanPhotos() async {
        guard hasAccess else {
            error = "Photo library access required"
            return
        }

        isScanning = true
        error = nil
        detectedTrips = []

        do {
            let trips = try await PhotoImporter.shared.scanPhotos(
                from: startDate,
                to: endDate
            ) { [weak self] progress in
                Task { @MainActor in
                    self?.progress = progress
                }
            }

            detectedTrips = trips
            // Select Schengen trips by default
            selectedTrips = Set(trips.filter { $0.isSchengen }.map { $0.id })

        } catch {
            self.error = "Failed to scan photos: \(error.localizedDescription)"
        }

        isScanning = false
    }

    func toggleTrip(_ trip: DetectedPhotoTrip) {
        if selectedTrips.contains(trip.id) {
            selectedTrips.remove(trip.id)
        } else {
            selectedTrips.insert(trip.id)
        }
    }

    func selectAllSchengen() {
        selectedTrips = Set(detectedTrips.filter { $0.isSchengen }.map { $0.id })
    }

    func selectAll() {
        selectedTrips = Set(detectedTrips.map { $0.id })
    }

    func deselectAll() {
        selectedTrips.removeAll()
    }

    func importSelected() async {
        let tripsToImport = detectedTrips.filter { selectedTrips.contains($0.id) }
        guard !tripsToImport.isEmpty else { return }

        do {
            try await PhotoImporter.shared.importTrips(tripsToImport)
            importComplete = true
        } catch {
            self.error = "Failed to import trips: \(error.localizedDescription)"
        }
    }
}
