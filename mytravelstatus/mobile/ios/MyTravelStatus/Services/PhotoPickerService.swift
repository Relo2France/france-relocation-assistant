/**
 * PhotoPickerService.swift
 *
 * Privacy-focused photo selection using PHPicker (iOS 14+).
 * - Only accesses photos explicitly selected by user
 * - No full library access required
 * - Asks for confirmation before reading EXIF/GPS metadata
 * - Provides manual entry alternative
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI
import PhotosUI
import CoreLocation

/// A photo selected by the user with extracted metadata
struct SelectedPhotoData: Identifiable {
    let id = UUID()
    let image: UIImage?
    let date: Date?
    let location: CLLocation?
    let countryCode: String?
    let countryName: String?
    let isSchengen: Bool

    var hasLocation: Bool { location != nil }
}

/// Grouped photos by country and date range
struct PhotoTripGroup: Identifiable {
    let id = UUID()
    let countryCode: String
    let countryName: String
    let startDate: Date
    let endDate: Date
    let photos: [SelectedPhotoData]
    let isSchengen: Bool

    var durationDays: Int {
        Calendar.current.dateComponents([.day], from: startDate, to: endDate).day ?? 0 + 1
    }
}

/// Photo picker coordinator for SwiftUI
@MainActor
class PhotoPickerService: ObservableObject {
    static let shared = PhotoPickerService()

    // MARK: - Published State

    @Published var selectedPhotos: [SelectedPhotoData] = []
    @Published var tripGroups: [PhotoTripGroup] = []
    @Published var isProcessing = false
    @Published var processingProgress: Float = 0
    @Published var error: String?

    // Confirmation state
    @Published var showMetadataConfirmation = false
    @Published var pendingItems: [PhotosPickerItem] = []

    // MARK: - Private

    private let geocoder = CLGeocoder()
    private var geocodeCache: [String: (code: String, name: String)] = [:]

    private init() {}

    // MARK: - Public Methods

    /// Process selected photos - called after user picks from PHPicker
    /// Shows confirmation before reading EXIF data
    func handleSelection(_ items: [PhotosPickerItem]) {
        guard !items.isEmpty else { return }

        // Store items and show confirmation
        pendingItems = items
        showMetadataConfirmation = true
    }

    /// User confirmed they want to read photo metadata
    func confirmMetadataExtraction() async {
        showMetadataConfirmation = false

        guard !pendingItems.isEmpty else { return }

        isProcessing = true
        processingProgress = 0
        error = nil
        selectedPhotos = []
        tripGroups = []

        do {
            let photos = try await processPhotos(pendingItems)
            selectedPhotos = photos
            tripGroups = await groupIntoTrips(photos)
        } catch {
            self.error = "Failed to process photos: \(error.localizedDescription)"
        }

        pendingItems = []
        isProcessing = false
    }

    /// User declined metadata extraction - clear selection
    func declineMetadataExtraction() {
        showMetadataConfirmation = false
        pendingItems = []
    }

    /// Clear all selected photos
    func clearSelection() {
        selectedPhotos = []
        tripGroups = []
        pendingItems = []
    }

    // MARK: - Private Methods

    private func processPhotos(_ items: [PhotosPickerItem]) async throws -> [SelectedPhotoData] {
        var results: [SelectedPhotoData] = []
        let total = items.count

        for (index, item) in items.enumerated() {
            autoreleasepool {
                Task { @MainActor in
                    self.processingProgress = Float(index + 1) / Float(total)
                }
            }

            // Load image data
            guard let data = try? await item.loadTransferable(type: Data.self),
                  let image = UIImage(data: data) else {
                continue
            }

            // Extract EXIF metadata
            var date: Date?
            var location: CLLocation?

            if let source = CGImageSourceCreateWithData(data as CFData, nil),
               let properties = CGImageSourceCopyPropertiesAtIndex(source, 0, nil) as? [String: Any] {

                // Get date from EXIF
                if let exif = properties[kCGImagePropertyExifDictionary as String] as? [String: Any],
                   let dateString = exif[kCGImagePropertyExifDateTimeOriginal as String] as? String {
                    let formatter = DateFormatter()
                    formatter.dateFormat = "yyyy:MM:dd HH:mm:ss"
                    date = formatter.date(from: dateString)
                }

                // Get GPS coordinates
                if let gps = properties[kCGImagePropertyGPSDictionary as String] as? [String: Any] {
                    if let lat = gps[kCGImagePropertyGPSLatitude as String] as? Double,
                       let latRef = gps[kCGImagePropertyGPSLatitudeRef as String] as? String,
                       let lon = gps[kCGImagePropertyGPSLongitude as String] as? Double,
                       let lonRef = gps[kCGImagePropertyGPSLongitudeRef as String] as? String {

                        let latitude = latRef == "S" ? -lat : lat
                        let longitude = lonRef == "W" ? -lon : lon
                        location = CLLocation(latitude: latitude, longitude: longitude)
                    }
                }
            }

            // Geocode location to get country
            var countryCode: String?
            var countryName: String?

            if let loc = location {
                if let country = try? await geocodeLocation(loc) {
                    countryCode = country.code
                    countryName = country.name
                }
            }

            let photoData = SelectedPhotoData(
                image: image,
                date: date,
                location: location,
                countryCode: countryCode,
                countryName: countryName,
                isSchengen: countryCode != nil ? TravelStatusCountries.isSchengen(code: countryCode!) : false
            )

            results.append(photoData)

            // Rate limit geocoding
            if location != nil {
                try? await Task.sleep(nanoseconds: 100_000_000)
            }
        }

        return results
    }

    private func geocodeLocation(_ location: CLLocation) async throws -> (code: String, name: String)? {
        let cacheKey = "\(Int(location.coordinate.latitude * 100)),\(Int(location.coordinate.longitude * 100))"
        if let cached = geocodeCache[cacheKey] {
            return cached
        }

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

    private func groupIntoTrips(_ photos: [SelectedPhotoData]) async -> [PhotoTripGroup] {
        // Filter to photos with location data
        let geotaggedPhotos = photos.filter { $0.hasLocation && $0.date != nil && $0.countryCode != nil }
            .sorted { ($0.date ?? .distantPast) < ($1.date ?? .distantPast) }

        guard !geotaggedPhotos.isEmpty else { return [] }

        var groups: [PhotoTripGroup] = []
        var currentGroup: (code: String, name: String, start: Date, end: Date, photos: [SelectedPhotoData])?

        for photo in geotaggedPhotos {
            guard let code = photo.countryCode,
                  let name = photo.countryName,
                  let date = photo.date else { continue }

            let dayStart = Calendar.current.startOfDay(for: date)

            if let current = currentGroup {
                let dayDiff = Calendar.current.dateComponents([.day], from: current.end, to: dayStart).day ?? 0

                if current.code == code && dayDiff <= 2 {
                    currentGroup = (current.code, current.name, current.start, dayStart, current.photos + [photo])
                } else {
                    // Save current group
                    groups.append(PhotoTripGroup(
                        countryCode: current.code,
                        countryName: current.name,
                        startDate: current.start,
                        endDate: current.end,
                        photos: current.photos,
                        isSchengen: TravelStatusCountries.isSchengen(code: current.code)
                    ))
                    currentGroup = (code, name, dayStart, dayStart, [photo])
                }
            } else {
                currentGroup = (code, name, dayStart, dayStart, [photo])
            }
        }

        // Save last group
        if let current = currentGroup {
            groups.append(PhotoTripGroup(
                countryCode: current.code,
                countryName: current.name,
                startDate: current.start,
                endDate: current.end,
                photos: current.photos,
                isSchengen: TravelStatusCountries.isSchengen(code: current.code)
            ))
        }

        return groups
    }

    /// Import selected trip groups to the database
    func importTrips(_ groups: [PhotoTripGroup]) async throws {
        for group in groups {
            let trip = Trip(
                startDate: group.startDate,
                endDate: group.endDate,
                country: group.countryName,
                category: .personal,
                notes: "Imported from \(group.photos.count) selected photos",
                locationSource: .photoGps
            )

            try await LocalDatabase.shared.insertTrip(trip)
            SyncManager.shared.queueTripForSync(trip)
        }
    }
}

// MARK: - PHPicker SwiftUI View

struct PhotoPickerView: View {
    @ObservedObject private var service = PhotoPickerService.shared
    @EnvironmentObject private var privacySettings: PrivacySettings

    @State private var selectedItems: [PhotosPickerItem] = []
    @State private var selectedGroups: Set<UUID> = []
    @State private var showingImportSuccess = false

    let onDismiss: () -> Void

    var body: some View {
        NavigationStack {
            VStack {
                if service.isProcessing {
                    processingView
                } else if !service.tripGroups.isEmpty {
                    tripSelectionView
                } else {
                    photoSelectionView
                }
            }
            .navigationTitle("Import from Photos")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarLeading) {
                    Button("Cancel") {
                        service.clearSelection()
                        onDismiss()
                    }
                }
            }
            .alert("Read Photo Metadata?", isPresented: $service.showMetadataConfirmation) {
                Button("Read Location Data") {
                    Task {
                        await service.confirmMetadataExtraction()
                    }
                }
                Button("Cancel", role: .cancel) {
                    service.declineMetadataExtraction()
                }
            } message: {
                Text("MyTravelStatus will read the GPS location and date from your selected photos to detect trips. Your photos will not be uploaded or stored—only the trip dates and countries will be saved.\n\nYou can also skip this and enter trips manually.")
            }
            .alert("Trips Imported", isPresented: $showingImportSuccess) {
                Button("Done") {
                    service.clearSelection()
                    onDismiss()
                }
            } message: {
                Text("\(selectedGroups.count) trips have been imported successfully.")
            }
        }
    }

    // MARK: - Views

    private var photoSelectionView: some View {
        VStack(spacing: 24) {
            Image(systemName: "photo.on.rectangle.angled")
                .font(.system(size: 60))
                .foregroundColor(.blue)
                .padding(.top, 40)

            Text("Select Photos with GPS Data")
                .font(.title2)
                .fontWeight(.bold)

            Text("Choose photos from your trips. We'll extract the GPS location and date to create trip entries. Your photos are never uploaded—only the country and date are saved.")
                .font(.subheadline)
                .foregroundColor(.secondary)
                .multilineTextAlignment(.center)
                .padding(.horizontal)

            PhotosPicker(
                selection: $selectedItems,
                maxSelectionCount: 500,
                matching: .images,
                photoLibrary: .shared()
            ) {
                Label("Select Photos", systemImage: "photo.stack")
                    .font(.headline)
                    .frame(maxWidth: .infinity)
                    .padding()
                    .background(Color.blue)
                    .foregroundColor(.white)
                    .cornerRadius(12)
            }
            .padding(.horizontal)
            .onChange(of: selectedItems) { _, newItems in
                if !newItems.isEmpty {
                    service.handleSelection(newItems)
                    selectedItems = []
                }
            }

            Divider()
                .padding(.horizontal)

            Button {
                onDismiss()
            } label: {
                Text("Skip and Enter Trips Manually")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }

            Spacer()
        }
        .padding()
    }

    private var processingView: some View {
        VStack(spacing: 20) {
            ProgressView(value: service.processingProgress)
                .progressViewStyle(.linear)
                .padding(.horizontal)

            Text("Processing photos...")
                .font(.headline)

            Text("Reading GPS data from \(Int(service.processingProgress * 100))% of photos")
                .font(.subheadline)
                .foregroundColor(.secondary)
        }
        .padding()
    }

    private var tripSelectionView: some View {
        VStack {
            if service.tripGroups.isEmpty {
                ContentUnavailableView(
                    "No GPS Data Found",
                    systemImage: "location.slash",
                    description: Text("None of the selected photos had GPS location data. Try selecting different photos or enter trips manually.")
                )
            } else {
                List {
                    Section {
                        ForEach(service.tripGroups) { group in
                            TripGroupRow(
                                group: group,
                                isSelected: selectedGroups.contains(group.id),
                                onToggle: { toggleGroup(group) }
                            )
                        }
                    } header: {
                        Text("Detected Trips")
                    } footer: {
                        Text("Select the trips you want to import. Only trip dates and countries will be saved.")
                    }
                }
                .listStyle(.insetGrouped)

                HStack {
                    Button("Select All Schengen") {
                        selectedGroups = Set(service.tripGroups.filter { $0.isSchengen }.map { $0.id })
                    }
                    .buttonStyle(.bordered)

                    Spacer()

                    Button {
                        Task {
                            let groupsToImport = service.tripGroups.filter { selectedGroups.contains($0.id) }
                            try? await service.importTrips(groupsToImport)
                            showingImportSuccess = true
                        }
                    } label: {
                        Text("Import \(selectedGroups.count) Trips")
                            .fontWeight(.semibold)
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(selectedGroups.isEmpty)
                }
                .padding()
            }
        }
    }

    private func toggleGroup(_ group: PhotoTripGroup) {
        if selectedGroups.contains(group.id) {
            selectedGroups.remove(group.id)
        } else {
            selectedGroups.insert(group.id)
        }
    }
}

// MARK: - Helper Views

private struct TripGroupRow: View {
    let group: PhotoTripGroup
    let isSelected: Bool
    let onToggle: () -> Void

    var body: some View {
        Button(action: onToggle) {
            HStack {
                // Checkbox
                Image(systemName: isSelected ? "checkmark.circle.fill" : "circle")
                    .foregroundColor(isSelected ? .blue : .gray)
                    .font(.title2)

                // Flag and country
                VStack(alignment: .leading, spacing: 4) {
                    HStack {
                        Text(TravelStatusCountries.flagFor(code: group.countryCode) ?? "")
                        Text(group.countryName)
                            .fontWeight(.medium)
                        if group.isSchengen {
                            Text("Schengen")
                                .font(.caption)
                                .padding(.horizontal, 6)
                                .padding(.vertical, 2)
                                .background(Color.blue.opacity(0.1))
                                .foregroundColor(.blue)
                                .cornerRadius(4)
                        }
                    }

                    Text(formatDateRange(group.startDate, group.endDate))
                        .font(.subheadline)
                        .foregroundColor(.secondary)

                    Text("\(group.durationDays) days • \(group.photos.count) photos")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }

                Spacer()

                // Sample thumbnail
                if let image = group.photos.first?.image {
                    Image(uiImage: image)
                        .resizable()
                        .aspectRatio(contentMode: .fill)
                        .frame(width: 50, height: 50)
                        .cornerRadius(8)
                }
            }
        }
        .buttonStyle(.plain)
    }

    private func formatDateRange(_ start: Date, _ end: Date) -> String {
        let formatter = DateFormatter()
        formatter.dateStyle = .medium
        formatter.timeStyle = .none

        if start == end {
            return formatter.string(from: start)
        } else {
            return "\(formatter.string(from: start)) - \(formatter.string(from: end))"
        }
    }
}

// MARK: - Preview

#Preview {
    PhotoPickerView(onDismiss: {})
        .environmentObject(PrivacySettings.shared)
}
