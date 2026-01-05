/**
 * PrivacySettings.swift
 *
 * Manages user privacy preferences and settings.
 * Ensures all optional features are opt-in with clear user consent.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation
import Combine

@MainActor
class PrivacySettings: ObservableObject {
    static let shared = PrivacySettings()

    // MARK: - Keys

    private enum Keys {
        static let backgroundLocationEnabled = "privacy.backgroundLocationEnabled"
        static let backgroundLocationEducationShown = "privacy.backgroundLocationEducationShown"
        static let analyticsEnabled = "privacy.analyticsEnabled"
        static let analyticsEducationShown = "privacy.analyticsEducationShown"
        static let crashReportingEnabled = "privacy.crashReportingEnabled"
        static let photoMetadataConfirmed = "privacy.photoMetadataConfirmed"
        static let calendarAccessConfirmed = "privacy.calendarAccessConfirmed"
        static let lastPrivacyPolicyAccepted = "privacy.lastPolicyAccepted"
        static let demoModeEnabled = "privacy.demoModeEnabled"
    }

    // MARK: - Published Properties

    /// Background location tracking - OFF by default, opt-in only
    @Published var backgroundLocationEnabled: Bool {
        didSet {
            UserDefaults.standard.set(backgroundLocationEnabled, forKey: Keys.backgroundLocationEnabled)
            if !backgroundLocationEnabled {
                // Disable background updates when user opts out
                BackgroundLocationManager.shared.isTrackingEnabled = false
            }
        }
    }

    /// Whether we've shown the background location education screen
    @Published var backgroundLocationEducationShown: Bool {
        didSet {
            UserDefaults.standard.set(backgroundLocationEducationShown, forKey: Keys.backgroundLocationEducationShown)
        }
    }

    /// Analytics collection - OFF by default, opt-in only
    @Published var analyticsEnabled: Bool {
        didSet {
            UserDefaults.standard.set(analyticsEnabled, forKey: Keys.analyticsEnabled)
            AnalyticsManager.shared.updateEnabled(analyticsEnabled)
        }
    }

    /// Whether we've shown the analytics education screen
    @Published var analyticsEducationShown: Bool {
        didSet {
            UserDefaults.standard.set(analyticsEducationShown, forKey: Keys.analyticsEducationShown)
        }
    }

    /// Crash reporting - ON by default (opt-out)
    @Published var crashReportingEnabled: Bool {
        didSet {
            UserDefaults.standard.set(crashReportingEnabled, forKey: Keys.crashReportingEnabled)
        }
    }

    /// User confirmed EXIF metadata extraction from photos
    @Published var photoMetadataConfirmed: Bool {
        didSet {
            UserDefaults.standard.set(photoMetadataConfirmed, forKey: Keys.photoMetadataConfirmed)
        }
    }

    /// User confirmed calendar scanning
    @Published var calendarAccessConfirmed: Bool {
        didSet {
            UserDefaults.standard.set(calendarAccessConfirmed, forKey: Keys.calendarAccessConfirmed)
        }
    }

    /// Demo mode for App Store reviewers
    @Published var demoModeEnabled: Bool {
        didSet {
            UserDefaults.standard.set(demoModeEnabled, forKey: Keys.demoModeEnabled)
        }
    }

    /// Date of last privacy policy acceptance
    @Published var lastPrivacyPolicyAccepted: Date? {
        didSet {
            if let date = lastPrivacyPolicyAccepted {
                UserDefaults.standard.set(date.timeIntervalSince1970, forKey: Keys.lastPrivacyPolicyAccepted)
            }
        }
    }

    // MARK: - Initialization

    private init() {
        // Load saved preferences - defaults ensure privacy-first approach
        self.backgroundLocationEnabled = UserDefaults.standard.bool(forKey: Keys.backgroundLocationEnabled)
        self.backgroundLocationEducationShown = UserDefaults.standard.bool(forKey: Keys.backgroundLocationEducationShown)

        // Analytics OFF by default
        self.analyticsEnabled = UserDefaults.standard.object(forKey: Keys.analyticsEnabled) as? Bool ?? false
        self.analyticsEducationShown = UserDefaults.standard.bool(forKey: Keys.analyticsEducationShown)

        // Crash reporting ON by default (non-tracking)
        self.crashReportingEnabled = UserDefaults.standard.object(forKey: Keys.crashReportingEnabled) as? Bool ?? true

        self.photoMetadataConfirmed = UserDefaults.standard.bool(forKey: Keys.photoMetadataConfirmed)
        self.calendarAccessConfirmed = UserDefaults.standard.bool(forKey: Keys.calendarAccessConfirmed)
        self.demoModeEnabled = UserDefaults.standard.bool(forKey: Keys.demoModeEnabled)

        if let timestamp = UserDefaults.standard.object(forKey: Keys.lastPrivacyPolicyAccepted) as? TimeInterval {
            self.lastPrivacyPolicyAccepted = Date(timeIntervalSince1970: timestamp)
        } else {
            self.lastPrivacyPolicyAccepted = nil
        }
    }

    // MARK: - Public Methods

    /// Reset all privacy settings to defaults
    func resetToDefaults() {
        backgroundLocationEnabled = false
        analyticsEnabled = false
        crashReportingEnabled = true
        photoMetadataConfirmed = false
        calendarAccessConfirmed = false
        // Don't reset education flags - user has seen them
    }

    /// Clear all data for account deletion
    func clearAllData() {
        UserDefaults.standard.removeObject(forKey: Keys.backgroundLocationEnabled)
        UserDefaults.standard.removeObject(forKey: Keys.backgroundLocationEducationShown)
        UserDefaults.standard.removeObject(forKey: Keys.analyticsEnabled)
        UserDefaults.standard.removeObject(forKey: Keys.analyticsEducationShown)
        UserDefaults.standard.removeObject(forKey: Keys.crashReportingEnabled)
        UserDefaults.standard.removeObject(forKey: Keys.photoMetadataConfirmed)
        UserDefaults.standard.removeObject(forKey: Keys.calendarAccessConfirmed)
        UserDefaults.standard.removeObject(forKey: Keys.lastPrivacyPolicyAccepted)
        UserDefaults.standard.removeObject(forKey: Keys.demoModeEnabled)

        // Reset in-memory values
        backgroundLocationEnabled = false
        backgroundLocationEducationShown = false
        analyticsEnabled = false
        analyticsEducationShown = false
        crashReportingEnabled = true
        photoMetadataConfirmed = false
        calendarAccessConfirmed = false
        demoModeEnabled = false
        lastPrivacyPolicyAccepted = nil
    }

    /// Accept privacy policy
    func acceptPrivacyPolicy() {
        lastPrivacyPolicyAccepted = Date()
    }

    /// Check if privacy policy needs re-acceptance (e.g., after update)
    var needsPrivacyPolicyAcceptance: Bool {
        guard let accepted = lastPrivacyPolicyAccepted else { return true }
        // Policy was updated on this date - require re-acceptance
        let policyUpdateDate = ISO8601DateFormatter().date(from: "2026-01-01T00:00:00Z") ?? Date.distantPast
        return accepted < policyUpdateDate
    }

    // MARK: - Data Summary for Export

    /// Generate summary of data collected for privacy report
    func getDataCollectionSummary() -> [String: Any] {
        return [
            "settings": [
                "backgroundLocationEnabled": backgroundLocationEnabled,
                "analyticsEnabled": analyticsEnabled,
                "crashReportingEnabled": crashReportingEnabled,
                "photoMetadataConfirmed": photoMetadataConfirmed,
                "calendarAccessConfirmed": calendarAccessConfirmed
            ],
            "dataCollected": [
                "location": backgroundLocationEnabled ? "Country-level only (3x daily)" : "Manual entries only",
                "photos": photoMetadataConfirmed ? "GPS metadata from selected photos" : "Not accessed",
                "calendar": calendarAccessConfirmed ? "Event titles and locations" : "Not accessed",
                "analytics": analyticsEnabled ? "Anonymous usage events" : "Disabled",
                "crashes": crashReportingEnabled ? "Anonymous crash reports" : "Disabled"
            ],
            "dataSharedWithThirdParties": false,
            "dataUsedForTracking": false
        ]
    }
}
