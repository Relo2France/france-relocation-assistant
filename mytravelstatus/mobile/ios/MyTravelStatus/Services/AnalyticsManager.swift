/**
 * AnalyticsManager.swift
 *
 * Privacy-focused analytics implementation.
 * - Collects only anonymous, non-identifying events
 * - No precise location data
 * - No photo metadata
 * - No visa/tax/financial fields
 * - User opt-in required (disabled by default)
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation

/// Analytics event types - carefully curated to avoid sensitive data
enum AnalyticsEvent: String {
    // App lifecycle
    case appLaunched = "app_launched"
    case appBackgrounded = "app_backgrounded"
    case appForegrounded = "app_foregrounded"

    // Navigation (no specific content)
    case screenViewed = "screen_viewed"
    case tabSelected = "tab_selected"

    // Feature usage (counts only, no content)
    case tripAdded = "trip_added"
    case tripEdited = "trip_edited"
    case tripDeleted = "trip_deleted"
    case manualCheckIn = "manual_check_in"

    // Permission flows (granted/denied only)
    case locationPermissionRequested = "location_permission_requested"
    case locationPermissionGranted = "location_permission_granted"
    case locationPermissionDenied = "location_permission_denied"
    case notificationPermissionRequested = "notification_permission_requested"
    case notificationPermissionGranted = "notification_permission_granted"
    case notificationPermissionDenied = "notification_permission_denied"

    // Import features (count only, no content)
    case photoImportStarted = "photo_import_started"
    case photoImportCompleted = "photo_import_completed"
    case calendarImportStarted = "calendar_import_started"
    case calendarImportCompleted = "calendar_import_completed"

    // Sync events (status only)
    case syncStarted = "sync_started"
    case syncCompleted = "sync_completed"
    case syncFailed = "sync_failed"

    // Subscription events
    case subscriptionViewed = "subscription_viewed"
    case subscriptionStarted = "subscription_started"
    case subscriptionRestored = "subscription_restored"
    case subscriptionCancelled = "subscription_cancelled"

    // Errors (type only, no details)
    case errorOccurred = "error_occurred"

    // Privacy actions
    case analyticsOptIn = "analytics_opt_in"
    case analyticsOptOut = "analytics_opt_out"
    case dataExportRequested = "data_export_requested"
    case accountDeletionRequested = "account_deletion_requested"
}

/// Screen names for analytics (no sensitive screens)
enum AnalyticsScreen: String {
    case home = "home"
    case trips = "trips"
    case passportControl = "passport_control"
    case settings = "settings"
    case subscription = "subscription"
    case privacy = "privacy"
    case photoImport = "photo_import"
    case calendarImport = "calendar_import"
    case locationEducation = "location_education"
    case onboarding = "onboarding"
}

@MainActor
class AnalyticsManager: ObservableObject {
    static let shared = AnalyticsManager()

    // MARK: - Properties

    @Published private(set) var isEnabled: Bool = false
    private var sessionId: String = UUID().uuidString
    private var eventQueue: [QueuedEvent] = []
    private let maxQueueSize = 100

    // Event batch upload (if using server-side analytics)
    private var uploadTimer: Timer?

    // MARK: - Initialization

    private init() {
        // Analytics is OFF by default - respects user privacy
        isEnabled = false
    }

    // MARK: - Public Methods

    /// Update analytics enabled state (called by PrivacySettings)
    func updateEnabled(_ enabled: Bool) {
        isEnabled = enabled

        if enabled {
            startSession()
        } else {
            endSession()
            clearQueue()
        }
    }

    /// Track an event (only if analytics is enabled)
    func track(_ event: AnalyticsEvent, properties: [String: String]? = nil) {
        guard isEnabled else { return }

        // Sanitize properties to ensure no sensitive data
        let sanitizedProperties = sanitizeProperties(properties)

        let queuedEvent = QueuedEvent(
            event: event.rawValue,
            properties: sanitizedProperties,
            timestamp: Date(),
            sessionId: sessionId
        )

        eventQueue.append(queuedEvent)

        // Trim queue if too large
        if eventQueue.count > maxQueueSize {
            eventQueue.removeFirst(eventQueue.count - maxQueueSize)
        }

        #if DEBUG
        print("[Analytics] \(event.rawValue): \(sanitizedProperties ?? [:])")
        #endif
    }

    /// Track screen view
    func trackScreen(_ screen: AnalyticsScreen) {
        track(.screenViewed, properties: ["screen": screen.rawValue])
    }

    /// Track error (type only, no details)
    func trackError(_ errorType: String) {
        // Only track error type, not message (could contain sensitive data)
        let sanitizedType = errorType
            .replacingOccurrences(of: "[0-9]", with: "", options: .regularExpression)
            .prefix(50)

        track(.errorOccurred, properties: ["type": String(sanitizedType)])
    }

    // MARK: - Private Methods

    private func startSession() {
        sessionId = UUID().uuidString

        // Start periodic upload if using server-side analytics
        // uploadTimer = Timer.scheduledTimer(withTimeInterval: 60, repeats: true) { [weak self] _ in
        //     self?.uploadEvents()
        // }
    }

    private func endSession() {
        uploadTimer?.invalidate()
        uploadTimer = nil
    }

    private func clearQueue() {
        eventQueue.removeAll()
    }

    /// Sanitize properties to remove any potential sensitive data
    private func sanitizeProperties(_ properties: [String: String]?) -> [String: String]? {
        guard let properties = properties else { return nil }

        var sanitized: [String: String] = [:]

        // Allowed property keys (whitelist approach)
        let allowedKeys: Set<String> = [
            "screen", "tab", "source", "count", "status", "type",
            "platform", "version", "error_type"
        ]

        for (key, value) in properties {
            // Only include whitelisted keys
            guard allowedKeys.contains(key.lowercased()) else { continue }

            // Sanitize value
            var sanitizedValue = value

            // Remove anything that looks like an email
            sanitizedValue = sanitizedValue.replacingOccurrences(
                of: "[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}",
                with: "[REDACTED]",
                options: .regularExpression
            )

            // Remove anything that looks like coordinates
            sanitizedValue = sanitizedValue.replacingOccurrences(
                of: "-?\\d+\\.\\d{4,}",
                with: "[REDACTED]",
                options: .regularExpression
            )

            // Truncate long values
            sanitizedValue = String(sanitizedValue.prefix(100))

            sanitized[key] = sanitizedValue
        }

        return sanitized.isEmpty ? nil : sanitized
    }

    // MARK: - Event Upload (Optional Server-Side)

    private func uploadEvents() {
        guard !eventQueue.isEmpty else { return }

        // If you want to send to a server:
        // let events = eventQueue
        // eventQueue.removeAll()
        // Task {
        //     try await APIClient.shared.uploadAnalytics(events)
        // }

        // For now, events are just logged locally
        #if DEBUG
        print("[Analytics] Queue has \(eventQueue.count) events")
        #endif
    }

    // MARK: - Data Export

    /// Export analytics data for user data export request
    func exportData() -> [String: Any] {
        return [
            "enabled": isEnabled,
            "sessionId": sessionId,
            "eventCount": eventQueue.count,
            "events": eventQueue.map { event in
                [
                    "event": event.event,
                    "timestamp": ISO8601DateFormatter().string(from: event.timestamp),
                    "properties": event.properties ?? [:]
                ]
            }
        ]
    }
}

// MARK: - Models

private struct QueuedEvent {
    let event: String
    let properties: [String: String]?
    let timestamp: Date
    let sessionId: String
}
