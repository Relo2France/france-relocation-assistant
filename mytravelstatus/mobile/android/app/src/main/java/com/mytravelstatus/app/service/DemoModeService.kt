/**
 * DemoModeService.kt
 *
 * Demo mode for Google Play reviewers.
 * Allows reviewers to evaluate all premium features without payment.
 *
 * Activation methods:
 * 1. Special demo account (demo@mytravelstatus.com / demo123)
 * 2. Settings menu: tap "App Version" 5 times rapidly (hidden activation)
 * 3. Direct deep link: mytravelstatus://demo
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.content.Context
import android.net.Uri
import android.util.Log
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import java.util.Calendar
import java.util.Date

/**
 * Demo mode configuration
 */
object DemoModeConfig {
    const val DEMO_EMAIL = "demo@mytravelstatus.com"
    const val DEMO_PASSWORD = "demo123"

    val REVIEWER_NOTE = """
        Google Play Reviewer Access:
        - Email: demo@mytravelstatus.com
        - Password: demo123

        This account has full access to all premium features.
        Demo data is pre-loaded for evaluation purposes.
    """.trimIndent()
}

object DemoModeService {
    private const val TAG = "DemoModeService"

    // StateFlow for reactive UI updates
    private val _isDemoMode = MutableStateFlow(false)
    val isDemoMode: StateFlow<Boolean> = _isDemoMode.asStateFlow()

    private val _demoTrips = MutableStateFlow<List<DemoTrip>>(emptyList())
    val demoTrips: StateFlow<List<DemoTrip>> = _demoTrips.asStateFlow()

    // Hidden activation: tap count tracking
    private var tapCount = 0
    private var lastTapTime = 0L
    private const val TAP_THRESHOLD = 5
    private const val TAP_TIMEOUT_MS = 3000L

    /**
     * Initialize demo mode service
     */
    fun initialize(context: Context) {
        // Check if demo mode was previously enabled
        _isDemoMode.value = PrivacySettings.demoModeEnabled.value

        if (_isDemoMode.value) {
            loadDemoData()
        }
    }

    /**
     * Activate demo mode
     */
    fun activateDemoMode() {
        Log.d(TAG, "Demo mode activated")
        _isDemoMode.value = true
        PrivacySettings.setDemoModeEnabled(true)
        loadDemoData()
        AnalyticsManager.trackScreen(AnalyticsScreen.HOME) // Track activation as viewing home
    }

    /**
     * Deactivate demo mode
     */
    fun deactivateDemoMode() {
        Log.d(TAG, "Demo mode deactivated")
        _isDemoMode.value = false
        PrivacySettings.setDemoModeEnabled(false)
        _demoTrips.value = emptyList()
    }

    /**
     * Check if credentials are demo account
     */
    fun isDemoCredentials(email: String, password: String): Boolean {
        return email.lowercase() == DemoModeConfig.DEMO_EMAIL.lowercase() &&
                password == DemoModeConfig.DEMO_PASSWORD
    }

    /**
     * Handle tap on version for hidden demo activation
     * @return true if demo mode was activated
     */
    fun recordVersionTap(): Boolean {
        val now = System.currentTimeMillis()

        // Reset count if too much time has passed
        if (now - lastTapTime > TAP_TIMEOUT_MS) {
            tapCount = 0
        }

        lastTapTime = now
        tapCount++

        Log.d(TAG, "Version tap count: $tapCount")

        if (tapCount >= TAP_THRESHOLD) {
            tapCount = 0
            activateDemoMode()
            return true
        }

        return false
    }

    /**
     * Handle demo deep link
     * @return true if the URI was handled as a demo activation
     */
    fun handleDeepLink(uri: Uri): Boolean {
        if (uri.scheme == "mytravelstatus" && uri.host == "demo") {
            activateDemoMode()
            return true
        }
        return false
    }

    /**
     * Get demo passport control data
     */
    fun getDemoPassportControlData(): DemoPassportControlData {
        val calendar = Calendar.getInstance()
        val today = calendar.time

        // Calculate demo days used (sum of demo trips)
        val daysUsed = _demoTrips.value.sumOf { trip ->
            val daysDiff = ((trip.endDate.time - trip.startDate.time) / (1000 * 60 * 60 * 24)).toInt()
            daysDiff + 1
        }

        calendar.add(Calendar.DAY_OF_YEAR, -180)
        val windowStart = calendar.time

        return DemoPassportControlData(
            daysUsed = daysUsed,
            daysRemaining = 90 - daysUsed,
            daysAllowed = 90,
            windowDays = 180,
            windowStart = windowStart,
            windowEnd = today,
            status = if (daysUsed > 80) ComplianceStatus.WARNING else ComplianceStatus.SAFE,
            recentTrips = _demoTrips.value.take(3).map { trip ->
                val days = ((trip.endDate.time - trip.startDate.time) / (1000 * 60 * 60 * 24)).toInt() + 1
                DemoRecentTrip(
                    country = trip.country,
                    startDate = trip.startDate,
                    endDate = trip.endDate,
                    days = days
                )
            },
            nextAvailableDate = null,
            jurisdictionCode = "schengen"
        )
    }

    /**
     * Load sample demo data
     */
    private fun loadDemoData() {
        val calendar = Calendar.getInstance()
        val today = calendar.time

        val trips = mutableListOf<DemoTrip>()

        // Recent France trip (10-3 days ago)
        calendar.time = today
        calendar.add(Calendar.DAY_OF_YEAR, -3)
        val franceEnd = calendar.time
        calendar.add(Calendar.DAY_OF_YEAR, -7)
        val franceStart = calendar.time
        trips.add(DemoTrip(
            id = 9001,
            startDate = franceStart,
            endDate = franceEnd,
            country = "France",
            category = TripCategory.PERSONAL,
            notes = "Demo trip - Paris vacation",
            locationSource = LocationSource.MANUAL
        ))

        // Germany trip (25-20 days ago)
        calendar.time = today
        calendar.add(Calendar.DAY_OF_YEAR, -20)
        val germanyEnd = calendar.time
        calendar.add(Calendar.DAY_OF_YEAR, -5)
        val germanyStart = calendar.time
        trips.add(DemoTrip(
            id = 9002,
            startDate = germanyStart,
            endDate = germanyEnd,
            country = "Germany",
            category = TripCategory.BUSINESS,
            notes = "Demo trip - Berlin conference",
            locationSource = LocationSource.MANUAL
        ))

        // Italy trip (45-40 days ago)
        calendar.time = today
        calendar.add(Calendar.DAY_OF_YEAR, -40)
        val italyEnd = calendar.time
        calendar.add(Calendar.DAY_OF_YEAR, -5)
        val italyStart = calendar.time
        trips.add(DemoTrip(
            id = 9003,
            startDate = italyStart,
            endDate = italyEnd,
            country = "Italy",
            category = TripCategory.PERSONAL,
            notes = "Demo trip - Rome exploration",
            locationSource = LocationSource.MANUAL
        ))

        // Spain trip (60-55 days ago)
        calendar.time = today
        calendar.add(Calendar.DAY_OF_YEAR, -55)
        val spainEnd = calendar.time
        calendar.add(Calendar.DAY_OF_YEAR, -5)
        val spainStart = calendar.time
        trips.add(DemoTrip(
            id = 9004,
            startDate = spainStart,
            endDate = spainEnd,
            country = "Spain",
            category = TripCategory.PERSONAL,
            notes = "Demo trip - Barcelona",
            locationSource = LocationSource.MANUAL
        ))

        // Current trip (today - simulating being in France)
        trips.add(DemoTrip(
            id = 9005,
            startDate = today,
            endDate = today,
            country = "France",
            category = TripCategory.PERSONAL,
            notes = "Demo - current location",
            locationSource = LocationSource.MOBILE_GPS
        ))

        _demoTrips.value = trips
    }
}

// MARK: - Demo Data Models

data class DemoTrip(
    val id: Int,
    val startDate: Date,
    val endDate: Date,
    val country: String,
    val category: TripCategory,
    val notes: String,
    val locationSource: LocationSource
)

enum class TripCategory {
    PERSONAL, BUSINESS
}

enum class LocationSource {
    MANUAL, MOBILE_GPS, PHOTO_EXIF, CALENDAR
}

data class DemoPassportControlData(
    val daysUsed: Int,
    val daysRemaining: Int,
    val daysAllowed: Int,
    val windowDays: Int,
    val windowStart: Date,
    val windowEnd: Date,
    val status: ComplianceStatus,
    val recentTrips: List<DemoRecentTrip>,
    val nextAvailableDate: Date?,
    val jurisdictionCode: String
)

data class DemoRecentTrip(
    val country: String,
    val startDate: Date,
    val endDate: Date,
    val days: Int
)

enum class ComplianceStatus {
    SAFE, WARNING, DANGER, CRITICAL
}
