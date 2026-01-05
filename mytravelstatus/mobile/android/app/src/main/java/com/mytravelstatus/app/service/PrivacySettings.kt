/**
 * PrivacySettings.kt
 *
 * Manages user privacy preferences and consent.
 * - Background location: OFF by default, opt-in only
 * - Analytics: OFF by default, opt-in only
 * - Crash reporting: ON by default, opt-out
 *
 * Aligned with Google Play Data Safety requirements.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.content.Context
import android.content.SharedPreferences
import androidx.core.content.edit
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

object PrivacySettings {
    private const val PREFS_NAME = "privacy_settings"

    // Preference keys
    private object Keys {
        const val BACKGROUND_LOCATION_ENABLED = "background_location_enabled"
        const val BACKGROUND_LOCATION_EDUCATION_SHOWN = "background_location_education_shown"
        const val ANALYTICS_ENABLED = "analytics_enabled"
        const val CRASH_REPORTING_ENABLED = "crash_reporting_enabled"
        const val PHOTO_EXIF_CONFIRMED = "photo_exif_confirmed"
        const val NOTIFICATION_PERMISSION_REQUESTED = "notification_permission_requested"
        const val DEMO_MODE_ENABLED = "demo_mode_enabled"
        const val PRIVACY_POLICY_VERSION_ACCEPTED = "privacy_policy_version_accepted"
    }

    private var prefs: SharedPreferences? = null

    // StateFlows for reactive UI updates
    private val _backgroundLocationEnabled = MutableStateFlow(false)
    val backgroundLocationEnabled: StateFlow<Boolean> = _backgroundLocationEnabled.asStateFlow()

    private val _analyticsEnabled = MutableStateFlow(false)
    val analyticsEnabled: StateFlow<Boolean> = _analyticsEnabled.asStateFlow()

    private val _crashReportingEnabled = MutableStateFlow(true)
    val crashReportingEnabled: StateFlow<Boolean> = _crashReportingEnabled.asStateFlow()

    private val _demoModeEnabled = MutableStateFlow(false)
    val demoModeEnabled: StateFlow<Boolean> = _demoModeEnabled.asStateFlow()

    // Current privacy policy version
    const val CURRENT_PRIVACY_POLICY_VERSION = "1.0.0"

    /**
     * Initialize privacy settings
     */
    fun init(context: Context) {
        if (prefs != null) return

        prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

        // Load initial values from preferences
        // IMPORTANT: Background location defaults to FALSE (opt-in)
        _backgroundLocationEnabled.value = prefs?.getBoolean(Keys.BACKGROUND_LOCATION_ENABLED, false) ?: false
        // IMPORTANT: Analytics defaults to FALSE (opt-in)
        _analyticsEnabled.value = prefs?.getBoolean(Keys.ANALYTICS_ENABLED, false) ?: false
        // Crash reporting defaults to TRUE (opt-out)
        _crashReportingEnabled.value = prefs?.getBoolean(Keys.CRASH_REPORTING_ENABLED, true) ?: true
        // Demo mode defaults to FALSE
        _demoModeEnabled.value = prefs?.getBoolean(Keys.DEMO_MODE_ENABLED, false) ?: false
    }

    // MARK: - Background Location

    /**
     * Check if background location education has been shown
     */
    fun hasShownBackgroundLocationEducation(): Boolean {
        return prefs?.getBoolean(Keys.BACKGROUND_LOCATION_EDUCATION_SHOWN, false) ?: false
    }

    /**
     * Mark background location education as shown
     */
    fun markBackgroundLocationEducationShown() {
        prefs?.edit { putBoolean(Keys.BACKGROUND_LOCATION_EDUCATION_SHOWN, true) }
    }

    /**
     * Set background location enabled status
     * NOTE: This should only be called AFTER showing the education screen
     */
    fun setBackgroundLocationEnabled(enabled: Boolean) {
        prefs?.edit { putBoolean(Keys.BACKGROUND_LOCATION_ENABLED, enabled) }
        _backgroundLocationEnabled.value = enabled
    }

    // MARK: - Analytics

    /**
     * Set analytics enabled status
     */
    fun setAnalyticsEnabled(enabled: Boolean) {
        prefs?.edit { putBoolean(Keys.ANALYTICS_ENABLED, enabled) }
        _analyticsEnabled.value = enabled
    }

    // MARK: - Crash Reporting

    /**
     * Set crash reporting enabled status
     */
    fun setCrashReportingEnabled(enabled: Boolean) {
        prefs?.edit { putBoolean(Keys.CRASH_REPORTING_ENABLED, enabled) }
        _crashReportingEnabled.value = enabled
    }

    // MARK: - Photo EXIF

    /**
     * Check if user has confirmed photo EXIF extraction
     */
    fun hasConfirmedPhotoExif(): Boolean {
        return prefs?.getBoolean(Keys.PHOTO_EXIF_CONFIRMED, false) ?: false
    }

    /**
     * Mark photo EXIF extraction as confirmed
     */
    fun markPhotoExifConfirmed() {
        prefs?.edit { putBoolean(Keys.PHOTO_EXIF_CONFIRMED, true) }
    }

    /**
     * Reset photo EXIF confirmation (for next session)
     */
    fun resetPhotoExifConfirmation() {
        prefs?.edit { putBoolean(Keys.PHOTO_EXIF_CONFIRMED, false) }
    }

    // MARK: - Notifications

    /**
     * Check if notification permission has been requested
     */
    fun hasRequestedNotificationPermission(): Boolean {
        return prefs?.getBoolean(Keys.NOTIFICATION_PERMISSION_REQUESTED, false) ?: false
    }

    /**
     * Mark notification permission as requested
     */
    fun markNotificationPermissionRequested() {
        prefs?.edit { putBoolean(Keys.NOTIFICATION_PERMISSION_REQUESTED, true) }
    }

    // MARK: - Demo Mode

    /**
     * Set demo mode enabled status
     */
    fun setDemoModeEnabled(enabled: Boolean) {
        prefs?.edit { putBoolean(Keys.DEMO_MODE_ENABLED, enabled) }
        _demoModeEnabled.value = enabled
    }

    // MARK: - Privacy Policy

    /**
     * Get the accepted privacy policy version
     */
    fun getAcceptedPrivacyPolicyVersion(): String? {
        return prefs?.getString(Keys.PRIVACY_POLICY_VERSION_ACCEPTED, null)
    }

    /**
     * Accept the current privacy policy version
     */
    fun acceptPrivacyPolicy() {
        prefs?.edit { putString(Keys.PRIVACY_POLICY_VERSION_ACCEPTED, CURRENT_PRIVACY_POLICY_VERSION) }
    }

    /**
     * Check if user needs to accept updated privacy policy
     */
    fun needsPrivacyPolicyAcceptance(): Boolean {
        val accepted = getAcceptedPrivacyPolicyVersion()
        return accepted != CURRENT_PRIVACY_POLICY_VERSION
    }

    // MARK: - Data Collection Summary

    /**
     * Get a summary of current data collection settings for export
     */
    fun getDataCollectionSummary(): Map<String, Any> {
        return mapOf(
            "backgroundLocationEnabled" to _backgroundLocationEnabled.value,
            "analyticsEnabled" to _analyticsEnabled.value,
            "crashReportingEnabled" to _crashReportingEnabled.value,
            "demoModeEnabled" to _demoModeEnabled.value,
            "privacyPolicyVersionAccepted" to (getAcceptedPrivacyPolicyVersion() ?: "none")
        )
    }

    // MARK: - Clear All Data

    /**
     * Clear all privacy settings (for account deletion)
     */
    fun clearAllData() {
        prefs?.edit { clear() }

        // Reset StateFlows to defaults
        _backgroundLocationEnabled.value = false
        _analyticsEnabled.value = false
        _crashReportingEnabled.value = true
        _demoModeEnabled.value = false
    }
}
