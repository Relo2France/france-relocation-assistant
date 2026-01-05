/**
 * DataManagementService.swift
 *
 * Handles data export and account deletion for GDPR/privacy compliance.
 * - Export all user data in JSON format
 * - Full account and data deletion
 * - Clear local data only option
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation
import UIKit

/// Result of data export
struct DataExportResult {
    let jsonData: Data
    let filename: String
    let exportDate: Date
}

/// Account deletion status
enum DeletionStatus: Equatable {
    case notStarted
    case inProgress
    case completed
    case failed(String)
}

@MainActor
class DataManagementService: ObservableObject {
    static let shared = DataManagementService()

    // MARK: - Published Properties

    @Published private(set) var isExporting = false
    @Published private(set) var isDeletingAccount = false
    @Published private(set) var deletionStatus: DeletionStatus = .notStarted
    @Published var exportError: String?
    @Published var deletionError: String?

    private init() {}

    // MARK: - Data Export

    /// Export all user data to JSON
    func exportAllData() async throws -> DataExportResult {
        isExporting = true
        exportError = nil

        defer { isExporting = false }

        // Gather all data
        var exportData: [String: Any] = [:]

        // Export metadata
        exportData["exportInfo"] = [
            "exportDate": ISO8601DateFormatter().string(from: Date()),
            "appVersion": Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "Unknown",
            "dataTypes": ["trips", "locations", "settings", "analytics"]
        ]

        // Export trips
        let trips = try await LocalDatabase.shared.getAllTrips()
        exportData["trips"] = trips.map { trip in
            [
                "id": trip.id ?? 0,
                "startDate": ISO8601DateFormatter().string(from: trip.startDate),
                "endDate": ISO8601DateFormatter().string(from: trip.endDate),
                "country": trip.country,
                "category": trip.category.rawValue,
                "notes": trip.notes ?? "",
                "locationSource": trip.locationSource?.rawValue ?? "manual"
            ]
        }

        // Export locations
        let locations = try await LocalDatabase.shared.getAllLocations()
        exportData["locations"] = locations.map { loc in
            [
                "recordedAt": ISO8601DateFormatter().string(from: loc.recordedAt),
                "countryCode": loc.countryCode ?? "",
                "countryName": loc.countryName ?? "",
                "isSchengen": loc.isSchengen,
                "source": loc.source.rawValue
                // Note: We don't export precise coordinates for privacy
            ]
        }

        // Export settings
        exportData["settings"] = PrivacySettings.shared.getDataCollectionSummary()

        // Export analytics (if enabled)
        exportData["analytics"] = AnalyticsManager.shared.exportData()

        // Export sync status
        exportData["syncStatus"] = [
            "lastSync": UserDefaults.standard.object(forKey: "lastSyncTimestamp") as? TimeInterval ?? 0,
            "pendingUploads": SyncManager.shared.pendingCount
        ]

        // Convert to JSON
        let jsonData = try JSONSerialization.data(withJSONObject: exportData, options: [.prettyPrinted, .sortedKeys])

        let formatter = DateFormatter()
        formatter.dateFormat = "yyyy-MM-dd"
        let filename = "mytravelstatus-export-\(formatter.string(from: Date())).json"

        AnalyticsManager.shared.track(.dataExportRequested)

        return DataExportResult(
            jsonData: jsonData,
            filename: filename,
            exportDate: Date()
        )
    }

    /// Share exported data via system share sheet
    func shareExportedData(_ result: DataExportResult) {
        // Write to temporary file
        let tempURL = FileManager.default.temporaryDirectory.appendingPathComponent(result.filename)

        do {
            try result.jsonData.write(to: tempURL)

            // Show share sheet
            let activityVC = UIActivityViewController(
                activityItems: [tempURL],
                applicationActivities: nil
            )

            if let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
               let window = windowScene.windows.first,
               let rootVC = window.rootViewController {
                activityVC.popoverPresentationController?.sourceView = rootVC.view
                rootVC.present(activityVC, animated: true)
            }
        } catch {
            exportError = "Failed to save export file: \(error.localizedDescription)"
        }
    }

    // MARK: - Account Deletion

    /// Delete all user data (local and server)
    func deleteAccountAndData() async throws {
        isDeletingAccount = true
        deletionStatus = .inProgress
        deletionError = nil

        defer { isDeletingAccount = false }

        AnalyticsManager.shared.track(.accountDeletionRequested)

        do {
            // Step 1: Request server-side deletion
            try await requestServerDeletion()

            // Step 2: Clear local database
            try await LocalDatabase.shared.clearAll()

            // Step 3: Clear privacy settings
            PrivacySettings.shared.clearAllData()

            // Step 4: Clear keychain (auth token)
            KeychainHelper.clearAll()

            // Step 5: Clear UserDefaults
            clearUserDefaults()

            // Step 6: Unregister push notifications
            await PushNotificationManager.shared.unregisterDevice()

            // Step 7: Disable background tasks
            BackgroundLocationManager.shared.isTrackingEnabled = false

            deletionStatus = .completed

        } catch {
            deletionStatus = .failed(error.localizedDescription)
            deletionError = error.localizedDescription
            throw error
        }
    }

    /// Clear only local data (keep server data)
    func clearLocalDataOnly() async throws {
        // Clear local database
        try await LocalDatabase.shared.clearAll()

        // Clear cached data
        clearUserDefaults()

        // Keep auth token so user can re-sync
    }

    // MARK: - Private Methods

    private func requestServerDeletion() async throws {
        // Request account deletion from server
        guard let url = URL(string: APIClient.shared.baseURL + "/account/delete") else {
            throw DataError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "DELETE"

        if let token = KeychainHelper.load(key: "authToken") {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        let (_, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse,
              (200...299).contains(httpResponse.statusCode) else {
            throw DataError.serverDeletionFailed
        }
    }

    private func clearUserDefaults() {
        let domain = Bundle.main.bundleIdentifier ?? "com.mytravelstatus.app"
        UserDefaults.standard.removePersistentDomain(forName: domain)
        UserDefaults.standard.synchronize()
    }
}

// MARK: - Errors

enum DataError: Error, LocalizedError {
    case exportFailed
    case invalidURL
    case serverDeletionFailed
    case localDeletionFailed

    var errorDescription: String? {
        switch self {
        case .exportFailed:
            return "Failed to export data"
        case .invalidURL:
            return "Invalid server URL"
        case .serverDeletionFailed:
            return "Failed to delete data from server"
        case .localDeletionFailed:
            return "Failed to delete local data"
        }
    }
}

// MARK: - LocalDatabase Extensions

extension LocalDatabase {
    func getAllTrips() async throws -> [Trip] {
        // Implementation would load all trips from local storage
        // This is a placeholder - actual implementation depends on database structure
        return []
    }

    func getAllLocations() async throws -> [LocationReading] {
        // Implementation would load all locations from local storage
        return []
    }
}

// MARK: - SyncManager Extension

extension SyncManager {
    var pendingCount: Int {
        // Return count of pending sync items
        return 0
    }
}
