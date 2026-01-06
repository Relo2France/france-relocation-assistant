/**
 * WidgetDataManager.swift
 *
 * Manages data sharing between the main app and the home screen widget.
 * Uses App Groups to share compliance data with the WidgetKit extension.
 *
 * @package MyTravelStatus
 * @since   1.8.3
 */

import Foundation
import WidgetKit

/// Manages widget data synchronization
class WidgetDataManager {

    // MARK: - Singleton

    static let shared = WidgetDataManager()

    // MARK: - Constants

    private let appGroupIdentifier = "group.com.mytravelstatus.app"
    private let complianceDataKey = "widgetComplianceData"
    private let apiBaseURLKey = "apiBaseURL"
    private let authTokenKey = "authToken"

    // MARK: - Properties

    private var sharedDefaults: UserDefaults? {
        UserDefaults(suiteName: appGroupIdentifier)
    }

    // MARK: - Initialization

    private init() {}

    // MARK: - Public Methods

    /// Updates the widget with the latest compliance data
    /// - Parameter jurisdictions: Array of jurisdiction summaries from API
    func updateWidgetData(jurisdictions: [JurisdictionSummary]) {
        let cachedJurisdictions = jurisdictions.map { summary in
            CachedWidgetJurisdiction(
                code: summary.jurisdictionCode,
                name: summary.jurisdictionName,
                flagEmoji: summary.flagEmoji,
                daysUsed: summary.daysUsed,
                daysAllowed: summary.daysAllowed,
                daysRemaining: summary.daysRemaining,
                status: summary.status,
                category: summary.category ?? "visa"
            )
        }

        let cachedData = CachedWidgetData(
            timestamp: Date(),
            jurisdictions: cachedJurisdictions
        )

        if let encoded = try? JSONEncoder().encode(cachedData) {
            sharedDefaults?.set(encoded, forKey: complianceDataKey)
        }

        // Request widget refresh
        WidgetCenter.shared.reloadAllTimelines()
    }

    /// Stores the API configuration for widget background fetches
    /// - Parameters:
    ///   - baseURL: The API base URL
    ///   - authToken: The authentication token
    func storeAPIConfiguration(baseURL: String, authToken: String) {
        sharedDefaults?.set(baseURL, forKey: apiBaseURLKey)
        sharedDefaults?.set(authToken, forKey: authTokenKey)
    }

    /// Clears all widget data (on logout)
    func clearWidgetData() {
        sharedDefaults?.removeObject(forKey: complianceDataKey)
        sharedDefaults?.removeObject(forKey: apiBaseURLKey)
        sharedDefaults?.removeObject(forKey: authTokenKey)

        // Request widget refresh to show empty state
        WidgetCenter.shared.reloadAllTimelines()
    }

    /// Triggers a widget refresh
    func refreshWidget() {
        WidgetCenter.shared.reloadAllTimelines()
    }

    /// Returns the cached compliance data if available
    func getCachedData() -> CachedWidgetData? {
        guard let data = sharedDefaults?.data(forKey: complianceDataKey),
              let cached = try? JSONDecoder().decode(CachedWidgetData.self, from: data) else {
            return nil
        }
        return cached
    }
}

// MARK: - Cache Models (shared between app and widget)

struct CachedWidgetData: Codable {
    let timestamp: Date
    let jurisdictions: [CachedWidgetJurisdiction]

    var isExpired: Bool {
        // Cache expires after 1 hour
        Date().timeIntervalSince(timestamp) > 3600
    }
}

struct CachedWidgetJurisdiction: Codable {
    let code: String
    let name: String
    let flagEmoji: String?
    let daysUsed: Int
    let daysAllowed: Int
    let daysRemaining: Int
    let status: String
    let category: String
}

// MARK: - App Group Entitlement Documentation

/**
 To enable widget data sharing, add the following entitlement to both
 the main app and the widget extension:

 1. In Xcode, select the main app target
 2. Go to Signing & Capabilities
 3. Click "+ Capability" and add "App Groups"
 4. Add the group identifier: group.com.mytravelstatus.app

 5. Repeat for the Widget extension target

 The Info.plist should include:
 ```xml
 <key>com.apple.security.application-groups</key>
 <array>
     <string>group.com.mytravelstatus.app</string>
 </array>
 ```
 */
