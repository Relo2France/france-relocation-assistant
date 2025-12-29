/**
 * BackgroundLocationManager.swift
 *
 * Manages background GPS tracking with the 3-read daily strategy.
 * Captures location at 8 AM, 2 PM, and 8 PM local time.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation
import CoreLocation
import BackgroundTasks

@MainActor
class BackgroundLocationManager: NSObject, ObservableObject {
    static let shared = BackgroundLocationManager()
    static let taskIdentifier = "com.relo2france.schengen.locationCheck"

    // MARK: - Published Properties

    @Published var currentLocation: CLLocation?
    @Published var currentCountry: String?
    @Published var currentCountryCode: String?
    @Published var isSchengenCountry: Bool = false
    @Published var lastCheckTime: Date?
    @Published var isTrackingEnabled: Bool = true
    @Published var authorizationStatus: CLAuthorizationStatus = .notDetermined

    // MARK: - Private Properties

    private let locationManager = CLLocationManager()
    private let geocoder = CLGeocoder()
    private var locationContinuation: CheckedContinuation<CLLocation, Error>?

    /// Check times in local hours (8 AM, 2 PM, 8 PM)
    private let checkHours = [8, 14, 20]

    /// Minimum accuracy in meters
    private let minAccuracy: CLLocationAccuracy = 100

    // MARK: - Initialization

    private override init() {
        super.init()
        locationManager.delegate = self
        locationManager.desiredAccuracy = kCLLocationAccuracyHundredMeters
        locationManager.allowsBackgroundLocationUpdates = true
        locationManager.pausesLocationUpdatesAutomatically = false
        authorizationStatus = locationManager.authorizationStatus
    }

    // MARK: - Public Methods

    /// Request location permissions
    func requestPermission() {
        locationManager.requestWhenInUseAuthorization()
    }

    /// Request always-on permission for background tracking
    func requestAlwaysPermission() {
        locationManager.requestAlwaysAuthorization()
    }

    /// Schedule background tasks for the 3 daily checks
    func scheduleBackgroundTasks() {
        guard isTrackingEnabled else { return }

        let request = BGAppRefreshTaskRequest(identifier: Self.taskIdentifier)
        request.earliestBeginDate = nextCheckTime()

        do {
            try BGTaskScheduler.shared.submit(request)
            print("Scheduled next location check for: \(request.earliestBeginDate!)")
        } catch {
            print("Failed to schedule background task: \(error)")
        }
    }

    /// Capture current location
    func captureLocation() async throws {
        guard authorizationStatus == .authorizedAlways ||
              authorizationStatus == .authorizedWhenInUse else {
            throw LocationError.notAuthorized
        }

        // Request location
        let location = try await requestSingleLocation()
        currentLocation = location
        lastCheckTime = Date()

        // Reverse geocode to get country
        try await reverseGeocode(location: location)

        // Store in local database
        try await storeLocation(location)

        // Check if we should create/extend a trip
        if isSchengenCountry {
            try await checkAndUpdateTrip()
        }

        // Queue for sync
        SyncManager.shared.queueLocationForSync(location, country: currentCountryCode ?? "")
    }

    /// Manual check-in triggered by user
    func manualCheckIn() async throws {
        try await captureLocation()
    }

    // MARK: - Private Methods

    private func requestSingleLocation() async throws -> CLLocation {
        return try await withCheckedThrowingContinuation { continuation in
            self.locationContinuation = continuation
            self.locationManager.requestLocation()
        }
    }

    private func reverseGeocode(location: CLLocation) async throws {
        let placemarks = try await geocoder.reverseGeocodeLocation(location)

        guard let placemark = placemarks.first else {
            throw LocationError.geocodingFailed
        }

        currentCountry = placemark.country
        currentCountryCode = placemark.isoCountryCode

        if let code = placemark.isoCountryCode {
            isSchengenCountry = SchengenCountries.isSchengen(code: code)
        }
    }

    private func storeLocation(_ location: CLLocation) async throws {
        let reading = LocationReading(
            lat: location.coordinate.latitude,
            lng: location.coordinate.longitude,
            accuracy: location.horizontalAccuracy,
            countryCode: currentCountryCode,
            countryName: currentCountry,
            city: nil, // Could extract from geocoding
            isSchengen: isSchengenCountry,
            source: .mobileGps,
            recordedAt: Date()
        )

        try await LocalDatabase.shared.insertLocation(reading)
    }

    private func checkAndUpdateTrip() async throws {
        guard let countryCode = currentCountryCode,
              isSchengenCountry else { return }

        let today = Calendar.current.startOfDay(for: Date())

        // Check if there's an existing trip for today
        if let existingTrip = try await LocalDatabase.shared.getTripForDate(today) {
            // If same country, no action needed (trip already covers today)
            if existingTrip.country == SchengenCountries.nameFor(code: countryCode) {
                return
            }
            // Different country - close existing trip and start new one
            var updatedTrip = existingTrip
            updatedTrip.endDate = Calendar.current.date(byAdding: .day, value: -1, to: today) ?? today
            try await LocalDatabase.shared.updateTrip(updatedTrip)
        }

        // Create new trip for today
        let newTrip = Trip(
            id: 0, // Will be assigned by server
            startDate: today,
            endDate: today,
            country: SchengenCountries.nameFor(code: countryCode) ?? countryCode,
            category: .personal,
            notes: "Auto-detected via GPS",
            locationSource: .mobileGps,
            locationLat: currentLocation?.coordinate.latitude,
            locationLng: currentLocation?.coordinate.longitude
        )

        try await LocalDatabase.shared.insertTrip(newTrip)
        SyncManager.shared.queueTripForSync(newTrip)
    }

    private func nextCheckTime() -> Date {
        let calendar = Calendar.current
        let now = Date()
        let currentHour = calendar.component(.hour, from: now)

        // Find the next check hour
        for checkHour in checkHours {
            if checkHour > currentHour {
                return calendar.date(
                    bySettingHour: checkHour,
                    minute: 0,
                    second: 0,
                    of: now
                )!
            }
        }

        // All checks for today are done, schedule for tomorrow morning
        let tomorrow = calendar.date(byAdding: .day, value: 1, to: now)!
        return calendar.date(
            bySettingHour: checkHours[0],
            minute: 0,
            second: 0,
            of: tomorrow
        )!
    }
}

// MARK: - CLLocationManagerDelegate

extension BackgroundLocationManager: CLLocationManagerDelegate {
    nonisolated func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        guard let location = locations.last else { return }

        // Check accuracy
        guard location.horizontalAccuracy <= minAccuracy else {
            print("Location accuracy too low: \(location.horizontalAccuracy)m")
            return
        }

        Task { @MainActor in
            locationContinuation?.resume(returning: location)
            locationContinuation = nil
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didFailWithError error: Error) {
        Task { @MainActor in
            locationContinuation?.resume(throwing: error)
            locationContinuation = nil
        }
    }

    nonisolated func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        Task { @MainActor in
            authorizationStatus = manager.authorizationStatus

            // If authorized, schedule background tasks
            if authorizationStatus == .authorizedAlways {
                scheduleBackgroundTasks()
            }
        }
    }
}

// MARK: - Errors

enum LocationError: Error, LocalizedError {
    case notAuthorized
    case geocodingFailed
    case timeout

    var errorDescription: String? {
        switch self {
        case .notAuthorized:
            return "Location permission not granted"
        case .geocodingFailed:
            return "Failed to determine country from location"
        case .timeout:
            return "Location request timed out"
        }
    }
}
