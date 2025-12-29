/**
 * CalendarImporter.swift
 *
 * Imports trip data from calendar events.
 * Scans for travel-related events and extracts location data.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation
import EventKit
import CoreLocation

/// Detected trip from calendar event
struct DetectedCalendarTrip: Identifiable {
    let id = UUID()
    let country: String
    let countryCode: String
    let startDate: Date
    let endDate: Date
    let eventTitle: String
    let calendarName: String
    let isSchengen: Bool

    var durationDays: Int {
        Calendar.current.dateComponents([.day], from: startDate, to: endDate).day ?? 0 + 1
    }
}

/// Calendar import service
actor CalendarImporter {
    static let shared = CalendarImporter()

    private let eventStore = EKEventStore()
    private let geocoder = CLGeocoder()

    /// Travel-related keywords to search for
    private let travelKeywords = [
        // Direct travel terms
        "flight", "fly", "plane", "airport",
        "train", "eurostar", "tgv", "rail",
        "hotel", "airbnb", "booking", "accommodation",
        "vacation", "holiday", "trip", "travel",
        "departure", "arrival",

        // Countries (Schengen)
        "austria", "belgium", "bulgaria", "croatia", "czech",
        "denmark", "estonia", "finland", "france", "germany",
        "greece", "hungary", "iceland", "italy", "latvia",
        "liechtenstein", "lithuania", "luxembourg", "malta",
        "netherlands", "norway", "poland", "portugal", "romania",
        "slovakia", "slovenia", "spain", "sweden", "switzerland",

        // Major cities
        "paris", "rome", "barcelona", "amsterdam", "berlin",
        "vienna", "prague", "lisbon", "madrid", "munich",
        "milan", "athens", "brussels", "copenhagen", "dublin",
        "stockholm", "oslo", "helsinki", "zurich", "geneva",

        // Business travel
        "conference", "meeting in", "visit to",
        "business trip", "work travel", "offsite"
    ]

    // MARK: - Public Methods

    /// Request calendar access
    func requestAccess() async -> Bool {
        do {
            if #available(iOS 17.0, *) {
                return try await eventStore.requestFullAccessToEvents()
            } else {
                return await withCheckedContinuation { continuation in
                    eventStore.requestAccess(to: .event) { granted, _ in
                        continuation.resume(returning: granted)
                    }
                }
            }
        } catch {
            return false
        }
    }

    /// Check current authorization status
    func checkAuthorizationStatus() -> EKAuthorizationStatus {
        EKEventStore.authorizationStatus(for: .event)
    }

    /// Scan calendar events for travel
    func scanEvents(
        from startDate: Date,
        to endDate: Date,
        progress: @escaping (Int, Int) -> Void
    ) async throws -> [DetectedCalendarTrip] {

        let calendars = eventStore.calendars(for: .event)
        let predicate = eventStore.predicateForEvents(
            withStart: startDate,
            end: endDate,
            calendars: calendars
        )

        let events = eventStore.events(matching: predicate)
        var detectedTrips: [DetectedCalendarTrip] = []
        var processed = 0

        progress(0, events.count)

        for event in events {
            // Check if event might be travel-related
            if isTravelRelated(event) {
                if let trip = await processEvent(event) {
                    detectedTrips.append(trip)
                }
            }

            processed += 1
            if processed % 10 == 0 {
                progress(processed, events.count)
            }
        }

        progress(events.count, events.count)

        // Merge overlapping trips in the same country
        return mergeOverlappingTrips(detectedTrips)
    }

    /// Import detected trips to the database
    func importTrips(_ trips: [DetectedCalendarTrip]) async throws {
        for trip in trips {
            let newTrip = Trip(
                startDate: trip.startDate,
                endDate: trip.endDate,
                country: trip.countryCode,
                category: trip.isSchengen ? .schengen : .nonSchengen,
                notes: "Imported from calendar: \(trip.eventTitle)"
            )

            try await LocalDatabase.shared.insertTrip(newTrip)
            SyncManager.shared.queueTripForSync(newTrip)
        }
    }

    // MARK: - Private Methods

    private func isTravelRelated(_ event: EKEvent) -> Bool {
        let title = event.title?.lowercased() ?? ""
        let location = event.location?.lowercased() ?? ""
        let notes = event.notes?.lowercased() ?? ""

        let searchText = "\(title) \(location) \(notes)"

        return travelKeywords.contains { keyword in
            searchText.contains(keyword)
        }
    }

    private func processEvent(_ event: EKEvent) async -> DetectedCalendarTrip? {
        guard let startDate = event.startDate,
              let endDate = event.endDate else {
            return nil
        }

        // Try to extract country from location
        var countryCode: String?
        var countryName: String?

        // First, check if location string contains a country name
        if let location = event.location {
            (countryCode, countryName) = extractCountryFromText(location)
        }

        // If not found, try to geocode the location
        if countryCode == nil, let location = event.location, !location.isEmpty {
            if let geocoded = try? await geocodeAddress(location) {
                countryCode = geocoded.code
                countryName = geocoded.name
            }
        }

        // If still not found, check the title
        if countryCode == nil {
            (countryCode, countryName) = extractCountryFromText(event.title ?? "")
        }

        guard let code = countryCode, let name = countryName else {
            return nil
        }

        return DetectedCalendarTrip(
            country: name,
            countryCode: code,
            startDate: Calendar.current.startOfDay(for: startDate),
            endDate: Calendar.current.startOfDay(for: endDate),
            eventTitle: event.title ?? "Untitled Event",
            calendarName: event.calendar.title,
            isSchengen: SchengenCountries.isSchengen(code: code)
        )
    }

    private func extractCountryFromText(_ text: String) -> (code: String?, name: String?) {
        let lowercased = text.lowercased()

        // Check for country names
        for (code, name) in SchengenCountries.names {
            if lowercased.contains(name.lowercased()) {
                return (code, name)
            }
        }

        // Check for major cities
        let cityToCountry: [String: (code: String, name: String)] = [
            "paris": ("FR", "France"),
            "rome": ("IT", "Italy"),
            "milan": ("IT", "Italy"),
            "barcelona": ("ES", "Spain"),
            "madrid": ("ES", "Spain"),
            "amsterdam": ("NL", "Netherlands"),
            "berlin": ("DE", "Germany"),
            "munich": ("DE", "Germany"),
            "vienna": ("AT", "Austria"),
            "prague": ("CZ", "Czech Republic"),
            "lisbon": ("PT", "Portugal"),
            "brussels": ("BE", "Belgium"),
            "copenhagen": ("DK", "Denmark"),
            "stockholm": ("SE", "Sweden"),
            "oslo": ("NO", "Norway"),
            "helsinki": ("FI", "Finland"),
            "zurich": ("CH", "Switzerland"),
            "geneva": ("CH", "Switzerland"),
            "athens": ("GR", "Greece"),
            "budapest": ("HU", "Hungary"),
            "warsaw": ("PL", "Poland"),
            "dublin": ("IE", "Ireland"),
            "london": ("GB", "United Kingdom"),
            "edinburgh": ("GB", "United Kingdom"),
            "new york": ("US", "United States"),
            "los angeles": ("US", "United States"),
            "tokyo": ("JP", "Japan"),
            "sydney": ("AU", "Australia")
        ]

        for (city, country) in cityToCountry {
            if lowercased.contains(city) {
                return (country.code, country.name)
            }
        }

        return (nil, nil)
    }

    private func geocodeAddress(_ address: String) async throws -> (code: String, name: String)? {
        let placemarks = try await geocoder.geocodeAddressString(address)

        guard let placemark = placemarks.first,
              let countryCode = placemark.isoCountryCode,
              let countryName = placemark.country else {
            return nil
        }

        return (countryCode, countryName)
    }

    private func mergeOverlappingTrips(_ trips: [DetectedCalendarTrip]) -> [DetectedCalendarTrip] {
        guard !trips.isEmpty else { return [] }

        // Sort by start date
        let sorted = trips.sorted { $0.startDate < $1.startDate }
        var merged: [DetectedCalendarTrip] = []

        var current = sorted[0]

        for trip in sorted.dropFirst() {
            // If same country and overlapping/adjacent, merge
            if current.countryCode == trip.countryCode {
                let daysBetween = Calendar.current.dateComponents(
                    [.day],
                    from: current.endDate,
                    to: trip.startDate
                ).day ?? 0

                if daysBetween <= 1 {
                    // Merge: extend current trip
                    current = DetectedCalendarTrip(
                        country: current.country,
                        countryCode: current.countryCode,
                        startDate: current.startDate,
                        endDate: max(current.endDate, trip.endDate),
                        eventTitle: current.eventTitle,
                        calendarName: current.calendarName,
                        isSchengen: current.isSchengen
                    )
                    continue
                }
            }

            // Different country or gap - save current and start new
            merged.append(current)
            current = trip
        }

        merged.append(current)
        return merged
    }
}

// MARK: - Calendar Import View Model

@MainActor
class CalendarImportViewModel: ObservableObject {
    @Published var hasAccess = false
    @Published var isScanning = false
    @Published var progress: (current: Int, total: Int)?
    @Published var detectedTrips: [DetectedCalendarTrip] = []
    @Published var selectedTrips: Set<UUID> = []
    @Published var error: String?
    @Published var importComplete = false

    @Published var startDate = Calendar.current.date(byAdding: .year, value: -1, to: Date())!
    @Published var endDate = Date()

    func checkAccess() async {
        let status = await CalendarImporter.shared.checkAuthorizationStatus()
        hasAccess = status == .authorized || status == .fullAccess
    }

    func requestAccess() async {
        hasAccess = await CalendarImporter.shared.requestAccess()
    }

    func scanEvents() async {
        guard hasAccess else {
            error = "Calendar access required"
            return
        }

        isScanning = true
        error = nil
        detectedTrips = []

        do {
            let trips = try await CalendarImporter.shared.scanEvents(
                from: startDate,
                to: endDate
            ) { [weak self] current, total in
                Task { @MainActor in
                    self?.progress = (current, total)
                }
            }

            detectedTrips = trips
            // Select Schengen trips by default
            selectedTrips = Set(trips.filter { $0.isSchengen }.map { $0.id })

        } catch {
            self.error = "Failed to scan calendar: \(error.localizedDescription)"
        }

        isScanning = false
    }

    func toggleTrip(_ trip: DetectedCalendarTrip) {
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
            try await CalendarImporter.shared.importTrips(tripsToImport)
            importComplete = true
        } catch {
            self.error = "Failed to import trips: \(error.localizedDescription)"
        }
    }
}
