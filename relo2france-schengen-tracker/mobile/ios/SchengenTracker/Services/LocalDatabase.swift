/**
 * LocalDatabase.swift
 *
 * Local SQLite database for offline storage.
 * Uses SwiftData or Core Data in production.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation

/// Local database manager for offline storage
/// TODO: Replace with SwiftData or Core Data implementation
actor LocalDatabase {
    static let shared = LocalDatabase()

    private var trips: [Trip] = []
    private var locations: [LocationReading] = []

    private init() {
        loadFromDisk()
    }

    // MARK: - Trips

    func insertTrip(_ trip: Trip) async throws {
        var newTrip = trip
        if newTrip.id == 0 {
            // Generate temporary local ID
            newTrip.id = -Int.random(in: 1...999999)
        }
        trips.append(newTrip)
        saveToDisk()
    }

    func updateTrip(_ trip: Trip) async throws {
        if let index = trips.firstIndex(where: { $0.id == trip.id }) {
            trips[index] = trip
            saveToDisk()
        }
    }

    func deleteTrip(id: Int) async throws {
        trips.removeAll { $0.id == id }
        saveToDisk()
    }

    func getTrips() async -> [Trip] {
        return trips
    }

    func getTripForDate(_ date: Date) async throws -> Trip? {
        let calendar = Calendar.current
        let targetDay = calendar.startOfDay(for: date)

        return trips.first { trip in
            let tripStart = calendar.startOfDay(for: trip.startDate)
            let tripEnd = calendar.startOfDay(for: trip.endDate)
            return targetDay >= tripStart && targetDay <= tripEnd
        }
    }

    // MARK: - Locations

    func insertLocation(_ location: LocationReading) async throws {
        var newLocation = location
        if newLocation.id == nil {
            newLocation.id = -Int.random(in: 1...999999)
        }
        locations.append(newLocation)
        saveToDisk()
    }

    func getLocations(since: Date) async -> [LocationReading] {
        return locations.filter { $0.recordedAt >= since }
    }

    func getLocationHistory(limit: Int = 100) async -> [LocationReading] {
        return Array(locations.suffix(limit))
    }

    // MARK: - Persistence

    private func saveToDisk() {
        let encoder = JSONEncoder()
        encoder.dateEncodingStrategy = .iso8601

        if let tripsData = try? encoder.encode(trips) {
            UserDefaults.standard.set(tripsData, forKey: "localTrips")
        }

        if let locationsData = try? encoder.encode(locations) {
            UserDefaults.standard.set(locationsData, forKey: "localLocations")
        }
    }

    private func loadFromDisk() {
        let decoder = JSONDecoder()
        decoder.dateDecodingStrategy = .iso8601

        if let tripsData = UserDefaults.standard.data(forKey: "localTrips"),
           let loadedTrips = try? decoder.decode([Trip].self, from: tripsData) {
            trips = loadedTrips
        }

        if let locationsData = UserDefaults.standard.data(forKey: "localLocations"),
           let loadedLocations = try? decoder.decode([LocationReading].self, from: locationsData) {
            locations = loadedLocations
        }
    }

    // MARK: - Clear

    func clearAll() async {
        trips.removeAll()
        locations.removeAll()
        UserDefaults.standard.removeObject(forKey: "localTrips")
        UserDefaults.standard.removeObject(forKey: "localLocations")
    }
}
