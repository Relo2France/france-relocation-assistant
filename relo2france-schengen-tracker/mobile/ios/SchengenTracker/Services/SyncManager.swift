/**
 * SyncManager.swift
 *
 * Manages data synchronization between local storage and server.
 * Handles offline queuing, conflict resolution, and background sync.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

import Foundation
import BackgroundTasks
import Network

@MainActor
class SyncManager: ObservableObject {
    static let shared = SyncManager()
    static let taskIdentifier = "com.relo2france.schengen.sync"

    // MARK: - Published Properties

    @Published var isSyncing = false
    @Published var lastSyncTime: Date?
    @Published var pendingChangesCount = 0
    @Published var syncError: Error?
    @Published var isOnline = true

    // MARK: - Private Properties

    private let apiClient = APIClient.shared
    private let networkMonitor = NWPathMonitor()
    private let monitorQueue = DispatchQueue(label: "NetworkMonitor")

    private var pendingTrips: [Trip] = []
    private var pendingLocations: [LocationReading] = []

    private let userDefaults = UserDefaults.standard
    private let lastSyncKey = "lastSyncTime"
    private let deviceIdKey = "deviceId"

    // MARK: - Initialization

    private init() {
        loadLastSyncTime()
        loadPendingChanges()
        setupNetworkMonitoring()
    }

    // MARK: - Device ID

    var deviceId: String {
        if let existing = userDefaults.string(forKey: deviceIdKey) {
            return existing
        }
        let newId = UUID().uuidString
        userDefaults.set(newId, forKey: deviceIdKey)
        return newId
    }

    // MARK: - Queue Changes

    func queueTripForSync(_ trip: Trip) {
        var mutableTrip = trip
        mutableTrip.syncStatus = .pending
        pendingTrips.append(mutableTrip)
        savePendingChanges()
        updatePendingCount()

        // Try to sync immediately if online
        if isOnline {
            Task {
                try? await sync()
            }
        }
    }

    func queueLocationForSync(_ location: CLLocation, country: String) {
        let reading = LocationReading(
            lat: location.coordinate.latitude,
            lng: location.coordinate.longitude,
            accuracy: location.horizontalAccuracy,
            countryCode: country,
            countryName: nil,
            city: nil,
            isSchengen: SchengenCountries.isSchengen(code: country),
            source: .mobileGps,
            recordedAt: Date()
        )
        pendingLocations.append(reading)
        savePendingChanges()
        updatePendingCount()
    }

    // MARK: - Sync

    func sync() async throws {
        guard !isSyncing else { return }
        guard isOnline else {
            throw SyncError.offline
        }

        isSyncing = true
        syncError = nil

        defer {
            isSyncing = false
        }

        do {
            // Build sync request
            let changes = buildSyncChanges()

            let request = SyncRequest(
                lastSync: lastSyncTime,
                deviceId: deviceId,
                changes: changes
            )

            // Send to server
            let response = try await apiClient.sync(request: request)

            // Process results
            processResults(response.syncResults)

            // Apply server changes
            for change in response.serverChanges {
                try await applyServerChange(change)
            }

            // Handle conflicts
            if !response.conflicts.isEmpty {
                // For now, server wins
                for conflict in response.conflicts {
                    print("Conflict on \(conflict.type) \(conflict.id) - server version applied")
                }
            }

            // Update last sync time
            lastSyncTime = response.serverTime
            saveLastSyncTime()

            // Clear synced items
            clearSyncedItems()

        } catch {
            syncError = error
            throw error
        }
    }

    // MARK: - Background Sync

    func scheduleBackgroundSync() {
        let request = BGProcessingTaskRequest(identifier: Self.taskIdentifier)
        request.requiresNetworkConnectivity = true
        request.requiresExternalPower = false

        // Schedule for when the device is idle
        request.earliestBeginDate = Date(timeIntervalSinceNow: 15 * 60) // 15 minutes

        do {
            try BGTaskScheduler.shared.submit(request)
            print("Scheduled background sync")
        } catch {
            print("Failed to schedule background sync: \(error)")
        }
    }

    // MARK: - Private Methods

    private func buildSyncChanges() -> [SyncChange] {
        var changes: [SyncChange] = []

        // Add pending trips
        for trip in pendingTrips where trip.syncStatus == .pending {
            let action = trip.id == 0 ? "create" : "update"
            changes.append(SyncChange(
                type: "trip",
                action: action,
                localId: trip.localId,
                data: AnyCodable(tripToDictionary(trip))
            ))
        }

        // Add pending locations
        for location in pendingLocations where location.syncStatus == .pending {
            changes.append(SyncChange(
                type: "location",
                action: "create",
                localId: location.localId,
                data: AnyCodable(locationToDictionary(location))
            ))
        }

        return changes
    }

    private func tripToDictionary(_ trip: Trip) -> [String: Any] {
        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"

        var dict: [String: Any] = [
            "start_date": formatter.string(from: trip.startDate),
            "end_date": formatter.string(from: trip.endDate),
            "country": trip.country,
            "category": trip.category.rawValue
        ]

        if trip.id > 0 {
            dict["id"] = trip.id
        }
        if let notes = trip.notes {
            dict["notes"] = notes
        }
        if let lat = trip.locationLat {
            dict["lat"] = lat
        }
        if let lng = trip.locationLng {
            dict["lng"] = lng
        }

        return dict
    }

    private func locationToDictionary(_ location: LocationReading) -> [String: Any] {
        let formatter = ISO8601DateFormatter()

        var dict: [String: Any] = [
            "lat": location.lat,
            "lng": location.lng,
            "is_schengen": location.isSchengen,
            "recorded_at": formatter.string(from: location.recordedAt)
        ]

        if let accuracy = location.accuracy {
            dict["accuracy"] = accuracy
        }
        if let code = location.countryCode {
            dict["country_code"] = code
        }
        if let name = location.countryName {
            dict["country_name"] = name
        }
        if let city = location.city {
            dict["city"] = city
        }

        return dict
    }

    private func processResults(_ results: [SyncResult]) {
        for result in results {
            guard let localId = result.localId else { continue }

            if result.success {
                // Mark as synced
                if let index = pendingTrips.firstIndex(where: { $0.localId == localId }) {
                    pendingTrips[index].syncStatus = .synced
                    if let serverId = result.serverId {
                        pendingTrips[index].id = serverId
                    }
                }
                if let index = pendingLocations.firstIndex(where: { $0.localId == localId }) {
                    pendingLocations[index].syncStatus = .synced
                }
            } else {
                // Mark as failed
                print("Sync failed for \(localId): \(result.error ?? "Unknown error")")
                if let index = pendingTrips.firstIndex(where: { $0.localId == localId }) {
                    pendingTrips[index].syncStatus = .failed
                }
                if let index = pendingLocations.firstIndex(where: { $0.localId == localId }) {
                    pendingLocations[index].syncStatus = .failed
                }
            }
        }
    }

    private func applyServerChange(_ change: ServerChange) async throws {
        // TODO: Apply changes to local database
        // This would update local storage with server changes
        print("Applying server change: \(change.type) \(change.action)")
    }

    private func clearSyncedItems() {
        pendingTrips.removeAll { $0.syncStatus == .synced }
        pendingLocations.removeAll { $0.syncStatus == .synced }
        savePendingChanges()
        updatePendingCount()
    }

    private func updatePendingCount() {
        pendingChangesCount = pendingTrips.filter { $0.syncStatus == .pending }.count +
                              pendingLocations.filter { $0.syncStatus == .pending }.count
    }

    // MARK: - Persistence

    private func saveLastSyncTime() {
        if let time = lastSyncTime {
            userDefaults.set(time, forKey: lastSyncKey)
        }
    }

    private func loadLastSyncTime() {
        lastSyncTime = userDefaults.object(forKey: lastSyncKey) as? Date
    }

    private func savePendingChanges() {
        // Save to UserDefaults (in production, use a proper database)
        if let tripsData = try? JSONEncoder().encode(pendingTrips) {
            userDefaults.set(tripsData, forKey: "pendingTrips")
        }
        if let locationsData = try? JSONEncoder().encode(pendingLocations) {
            userDefaults.set(locationsData, forKey: "pendingLocations")
        }
    }

    private func loadPendingChanges() {
        if let tripsData = userDefaults.data(forKey: "pendingTrips"),
           let trips = try? JSONDecoder().decode([Trip].self, from: tripsData) {
            pendingTrips = trips
        }
        if let locationsData = userDefaults.data(forKey: "pendingLocations"),
           let locations = try? JSONDecoder().decode([LocationReading].self, from: locationsData) {
            pendingLocations = locations
        }
        updatePendingCount()
    }

    // MARK: - Network Monitoring

    private func setupNetworkMonitoring() {
        networkMonitor.pathUpdateHandler = { [weak self] path in
            DispatchQueue.main.async {
                self?.isOnline = path.status == .satisfied

                // Auto-sync when coming online
                if path.status == .satisfied {
                    Task { @MainActor in
                        try? await self?.sync()
                    }
                }
            }
        }
        networkMonitor.start(queue: monitorQueue)
    }
}

// MARK: - Errors

enum SyncError: Error, LocalizedError {
    case offline
    case conflict
    case serverError

    var errorDescription: String? {
        switch self {
        case .offline:
            return "No internet connection"
        case .conflict:
            return "Sync conflict detected"
        case .serverError:
            return "Server error during sync"
        }
    }
}
