/**
 * AnalyticsManager.kt
 *
 * Privacy-focused analytics manager.
 * - Analytics are OFF by default (opt-in only)
 * - No sensitive data is ever tracked (no locations, no PII)
 * - All properties are sanitized before recording
 * - User can opt-out at any time via Settings
 *
 * Events tracked (when enabled):
 * - App lifecycle (launch, background)
 * - Screen views (screen names only, no content)
 * - Feature usage (counts only, no details)
 * - Permission responses (type only)
 * - Errors (type only, sanitized)
 *
 * Events NOT tracked:
 * - Location data (coordinates or country names)
 * - Trip details (dates, countries, notes)
 * - User identifiers (email, name)
 * - Device identifiers
 * - Photo metadata
 * - Calendar content
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.util.Log
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import java.util.Date

/**
 * Analytics event types
 */
enum class AnalyticsEvent(val eventName: String) {
    // App Lifecycle
    APP_LAUNCHED("app_launched"),
    APP_BACKGROUNDED("app_backgrounded"),
    APP_FOREGROUNDED("app_foregrounded"),

    // Authentication
    LOGIN_STARTED("login_started"),
    LOGIN_COMPLETED("login_completed"),
    LOGIN_FAILED("login_failed"),
    LOGOUT("logout"),

    // Navigation
    SCREEN_VIEWED("screen_viewed"),
    TAB_SELECTED("tab_selected"),

    // Feature Usage
    TRIP_ADDED("trip_added"),
    TRIP_DELETED("trip_deleted"),
    MANUAL_CHECK_IN("manual_check_in"),
    PHOTO_IMPORT_STARTED("photo_import_started"),
    PHOTO_IMPORT_COMPLETED("photo_import_completed"),
    CALENDAR_IMPORT_STARTED("calendar_import_started"),
    CALENDAR_IMPORT_COMPLETED("calendar_import_completed"),
    DATA_EXPORT_REQUESTED("data_export_requested"),
    ACCOUNT_DELETION_REQUESTED("account_deletion_requested"),

    // Permissions
    PERMISSION_REQUESTED("permission_requested"),
    PERMISSION_GRANTED("permission_granted"),
    PERMISSION_DENIED("permission_denied"),

    // Subscriptions
    SUBSCRIPTION_VIEW_OPENED("subscription_view_opened"),
    SUBSCRIPTION_PURCHASE_STARTED("subscription_purchase_started"),
    SUBSCRIPTION_PURCHASE_COMPLETED("subscription_purchase_completed"),
    SUBSCRIPTION_PURCHASE_FAILED("subscription_purchase_failed"),
    SUBSCRIPTION_RESTORED("subscription_restored"),

    // Errors
    ERROR_OCCURRED("error_occurred"),
    SYNC_FAILED("sync_failed"),

    // Settings
    ANALYTICS_OPT_IN("analytics_opt_in"),
    ANALYTICS_OPT_OUT("analytics_opt_out"),
    BACKGROUND_LOCATION_ENABLED("background_location_enabled"),
    BACKGROUND_LOCATION_DISABLED("background_location_disabled")
}

/**
 * Analytics screen identifiers
 */
enum class AnalyticsScreen(val screenName: String) {
    HOME("home"),
    TRIPS("trips"),
    PASSPORT_CONTROL("passport_control"),
    PHOTO_IMPORT("photo_import"),
    CALENDAR_IMPORT("calendar_import"),
    SETTINGS("settings"),
    PRIVACY_SETTINGS("privacy_settings"),
    SUBSCRIPTION("subscription"),
    LOCATION_EDUCATION("location_education"),
    LOGIN("login"),
    ACCOUNT("account")
}

object AnalyticsManager {
    private const val TAG = "AnalyticsManager"

    private val _isEnabled = MutableStateFlow(false)
    val isEnabled: StateFlow<Boolean> = _isEnabled.asStateFlow()

    // Event buffer for when analytics is enabled but not yet initialized
    private val eventBuffer = mutableListOf<AnalyticsLogEntry>()

    // Maximum events to buffer
    private const val MAX_BUFFER_SIZE = 100

    /**
     * Initialize analytics (but don't enable yet)
     */
    fun initialize() {
        // Load enabled state from privacy settings
        _isEnabled.value = PrivacySettings.analyticsEnabled.value

        Log.d(TAG, "Analytics initialized, enabled: ${_isEnabled.value}")
    }

    /**
     * Enable analytics (user opted in)
     */
    fun enable() {
        _isEnabled.value = true
        PrivacySettings.setAnalyticsEnabled(true)
        track(AnalyticsEvent.ANALYTICS_OPT_IN)
        Log.d(TAG, "Analytics enabled")
    }

    /**
     * Disable analytics (user opted out)
     */
    fun disable() {
        track(AnalyticsEvent.ANALYTICS_OPT_OUT)
        _isEnabled.value = false
        PrivacySettings.setAnalyticsEnabled(false)
        eventBuffer.clear()
        Log.d(TAG, "Analytics disabled")
    }

    /**
     * Track an event
     * @param event The event to track
     * @param properties Optional properties (will be sanitized)
     */
    fun track(event: AnalyticsEvent, properties: Map<String, String>? = null) {
        if (!_isEnabled.value) {
            Log.d(TAG, "Analytics disabled, skipping event: ${event.eventName}")
            return
        }

        val sanitizedProperties = properties?.let { sanitizeProperties(it) }

        val entry = AnalyticsLogEntry(
            eventName = event.eventName,
            properties = sanitizedProperties,
            timestamp = Date()
        )

        // Buffer the event
        if (eventBuffer.size >= MAX_BUFFER_SIZE) {
            eventBuffer.removeAt(0) // Remove oldest
        }
        eventBuffer.add(entry)

        Log.d(TAG, "Tracked event: ${event.eventName}, properties: $sanitizedProperties")

        // TODO: Send to analytics service if configured
        // For now, events are just logged locally
    }

    /**
     * Track a screen view
     * @param screen The screen being viewed
     */
    fun trackScreen(screen: AnalyticsScreen) {
        track(AnalyticsEvent.SCREEN_VIEWED, mapOf("screen" to screen.screenName))
    }

    /**
     * Track a permission request/response
     * @param permissionType The type of permission (location, camera, etc.)
     * @param granted Whether permission was granted
     */
    fun trackPermission(permissionType: String, granted: Boolean) {
        val event = if (granted) AnalyticsEvent.PERMISSION_GRANTED else AnalyticsEvent.PERMISSION_DENIED
        track(event, mapOf("permission_type" to permissionType))
    }

    /**
     * Track an error (sanitized, no PII)
     * @param errorType The type of error
     */
    fun trackError(errorType: String) {
        // Sanitize error type - only allow alphanumeric and underscores
        val sanitizedType = errorType.replace(Regex("[^a-zA-Z0-9_]"), "_").take(50)
        track(AnalyticsEvent.ERROR_OCCURRED, mapOf("error_type" to sanitizedType))
    }

    /**
     * Export analytics data for user data export
     */
    fun exportData(): Map<String, Any> {
        return mapOf(
            "analyticsEnabled" to _isEnabled.value,
            "eventsTracked" to if (_isEnabled.value) {
                eventBuffer.map { entry ->
                    mapOf(
                        "event" to entry.eventName,
                        "properties" to (entry.properties ?: emptyMap()),
                        "timestamp" to entry.timestamp.time
                    )
                }
            } else {
                "Analytics disabled - no data collected"
            }
        )
    }

    /**
     * Clear all analytics data (for account deletion)
     */
    fun clearAllData() {
        eventBuffer.clear()
        _isEnabled.value = false
    }

    // MARK: - Private Methods

    /**
     * Sanitize properties to ensure no sensitive data is tracked
     * - Remove any potential PII patterns
     * - Limit string lengths
     * - Only allow whitelisted property keys
     */
    private fun sanitizeProperties(properties: Map<String, String>): Map<String, String> {
        val allowedKeys = setOf(
            "screen", "tab", "permission_type", "error_type",
            "import_count", "subscription_id", "source"
        )

        return properties
            .filterKeys { it in allowedKeys }
            .mapValues { (_, value) -> sanitizeValue(value) }
    }

    /**
     * Sanitize a single value
     */
    private fun sanitizeValue(value: String): String {
        // Remove potential PII patterns
        var sanitized = value

        // Remove email patterns
        sanitized = sanitized.replace(Regex("[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}"), "[EMAIL]")

        // Remove potential phone numbers
        sanitized = sanitized.replace(Regex("\\+?[0-9]{10,}"), "[PHONE]")

        // Remove potential coordinates
        sanitized = sanitized.replace(Regex("-?\\d+\\.\\d{4,}"), "[COORD]")

        // Limit length
        return sanitized.take(100)
    }
}

/**
 * Internal analytics log entry
 */
private data class AnalyticsLogEntry(
    val eventName: String,
    val properties: Map<String, String>?,
    val timestamp: Date
)
