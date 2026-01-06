/**
 * Models.kt
 *
 * Data classes matching iOS and TypeScript definitions.
 * Used for API communication and local storage.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.mytravelstatus.app.data

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import java.time.LocalDate
import java.time.LocalDateTime

/**
 * Trip category enumeration
 */
@Serializable
enum class TripCategory {
    @SerialName("schengen") SCHENGEN,
    @SerialName("non_schengen") NON_SCHENGEN,
    @SerialName("home_country") HOME_COUNTRY,
    @SerialName("transit") TRANSIT
}

/**
 * Sync status for offline-first architecture
 */
@Serializable
enum class SyncStatus {
    @SerialName("synced") SYNCED,
    @SerialName("pending") PENDING,
    @SerialName("failed") FAILED
}

/**
 * Location source enumeration
 */
@Serializable
enum class LocationSource {
    @SerialName("mobile_gps") MOBILE_GPS,
    @SerialName("photo_exif") PHOTO_EXIF,
    @SerialName("calendar") CALENDAR,
    @SerialName("manual") MANUAL,
    @SerialName("ip") IP,
    @SerialName("timezone") TIMEZONE
}

/**
 * Trip data model
 */
@Serializable
data class Trip(
    val id: Int = 0,
    @SerialName("user_id") val userId: Int = 0,
    @SerialName("start_date") val startDate: String,
    @SerialName("end_date") val endDate: String,
    val country: String,
    val category: TripCategory = TripCategory.SCHENGEN,
    val notes: String? = null,
    @SerialName("location_lat") val locationLat: Double? = null,
    @SerialName("location_lng") val locationLng: Double? = null,
    @SerialName("created_at") val createdAt: String? = null,
    @SerialName("updated_at") val updatedAt: String? = null,

    // Local-only fields
    @SerialName("local_id") val localId: String = java.util.UUID.randomUUID().toString(),
    @SerialName("sync_status") val syncStatus: SyncStatus = SyncStatus.PENDING
) {
    /**
     * Calculate duration in days
     */
    fun durationDays(): Int {
        return try {
            val start = LocalDate.parse(startDate)
            val end = LocalDate.parse(endDate)
            (end.toEpochDay() - start.toEpochDay()).toInt() + 1
        } catch (e: Exception) {
            1
        }
    }
}

/**
 * Location reading from GPS or other sources
 */
@Serializable
data class LocationReading(
    val id: Int? = null,
    val lat: Double,
    val lng: Double,
    val accuracy: Double? = null,
    @SerialName("country_code") val countryCode: String? = null,
    @SerialName("country_name") val countryName: String? = null,
    val city: String? = null,
    @SerialName("is_schengen") val isSchengen: Boolean,
    val source: LocationSource = LocationSource.MOBILE_GPS,
    @SerialName("recorded_at") val recordedAt: String,

    // Local-only fields
    @SerialName("local_id") val localId: String = java.util.UUID.randomUUID().toString(),
    @SerialName("sync_status") val syncStatus: SyncStatus = SyncStatus.PENDING
)

/**
 * Passport control display data
 */
@Serializable
data class PassportControlData(
    @SerialName("days_used") val daysUsed: Int,
    @SerialName("days_remaining") val daysRemaining: Int,
    @SerialName("period_start") val periodStart: String,
    @SerialName("period_end") val periodEnd: String,
    @SerialName("current_country") val currentCountry: String?,
    @SerialName("current_trip") val currentTrip: Trip?,
    @SerialName("recent_trips") val recentTrips: List<Trip>,
    @SerialName("last_updated") val lastUpdated: String
) {
    /**
     * Check if within safe limits (under 80 days)
     */
    fun isWithinSafeLimit(): Boolean = daysUsed <= 80

    /**
     * Check if warning needed (80-85 days)
     */
    fun needsWarning(): Boolean = daysUsed in 81..85

    /**
     * Check if critical (86+ days)
     */
    fun isCritical(): Boolean = daysUsed >= 86
}

/**
 * Sync request to server
 */
@Serializable
data class SyncRequest(
    @SerialName("last_sync") val lastSync: String?,
    @SerialName("device_id") val deviceId: String,
    val changes: List<SyncChange>
)

/**
 * Individual change in sync request
 */
@Serializable
data class SyncChange(
    val type: String,
    val action: String,
    @SerialName("local_id") val localId: String,
    val data: Map<String, String>
)

/**
 * Sync response from server
 */
@Serializable
data class SyncResponse(
    val success: Boolean,
    @SerialName("server_time") val serverTime: String,
    @SerialName("sync_results") val syncResults: List<SyncResult>,
    @SerialName("server_changes") val serverChanges: List<ServerChange>,
    val conflicts: List<SyncConflict>
)

/**
 * Result of individual sync operation
 */
@Serializable
data class SyncResult(
    @SerialName("local_id") val localId: String?,
    val success: Boolean,
    @SerialName("server_id") val serverId: Int? = null,
    val error: String? = null
)

/**
 * Server-initiated change
 */
@Serializable
data class ServerChange(
    val type: String,
    val action: String,
    val data: Trip
)

/**
 * Sync conflict
 */
@Serializable
data class SyncConflict(
    val type: String,
    val id: Int,
    @SerialName("server_version") val serverVersion: Trip,
    @SerialName("local_version") val localVersion: Trip
)

/**
 * Device registration request
 */
@Serializable
data class DeviceRegistration(
    @SerialName("device_id") val deviceId: String,
    val platform: String = "android",
    @SerialName("push_token") val pushToken: String? = null,
    @SerialName("app_version") val appVersion: String,
    @SerialName("os_version") val osVersion: String
)

/**
 * App status response
 */
@Serializable
data class AppStatusResponse(
    @SerialName("days_used") val daysUsed: Int,
    @SerialName("days_remaining") val daysRemaining: Int,
    @SerialName("current_country") val currentCountry: String?,
    @SerialName("in_schengen") val inSchengen: Boolean,
    @SerialName("last_location") val lastLocation: LocationReading?,
    @SerialName("pending_sync") val pendingSync: Int,
    val alerts: List<Alert>
)

/**
 * Alert notification
 */
@Serializable
data class Alert(
    val type: String,
    val message: String,
    val severity: String
)

/**
 * Authentication response
 */
@Serializable
data class AuthResponse(
    val token: String,
    @SerialName("user_id") val userId: Int,
    @SerialName("expires_at") val expiresAt: String
)

/**
 * API error response
 */
@Serializable
data class ApiError(
    val code: String,
    val message: String,
    val data: Map<String, String>? = null
)

// ===================================
// Multi-Jurisdiction Models
// ===================================

/**
 * Jurisdiction type enumeration
 */
@Serializable
enum class JurisdictionType {
    @SerialName("zone") ZONE,
    @SerialName("country") COUNTRY,
    @SerialName("state") STATE
}

/**
 * Jurisdiction category enumeration
 */
@Serializable
enum class JurisdictionCategory {
    @SerialName("visa") VISA,
    @SerialName("tax") TAX,
    @SerialName("residency") RESIDENCY
}

/**
 * Counting method for day calculations
 */
@Serializable
enum class CountingMethod {
    @SerialName("rolling") ROLLING,
    @SerialName("calendar_year") CALENDAR_YEAR,
    @SerialName("fiscal_year") FISCAL_YEAR,
    @SerialName("multi_year") MULTI_YEAR,
    @SerialName("weighted_multi_year") WEIGHTED_MULTI_YEAR,
    @SerialName("uk_srt") UK_SRT
}

/**
 * Jurisdiction rule definition
 */
@Serializable
data class JurisdictionRule(
    val id: Int? = null,
    val code: String,
    val name: String,
    val type: JurisdictionType,
    val category: JurisdictionCategory,
    @SerialName("days_allowed") val daysAllowed: Int,
    @SerialName("window_days") val windowDays: Int,
    @SerialName("counting_method") val countingMethod: CountingMethod,
    @SerialName("reset_month") val resetMonth: Int? = null,
    @SerialName("reset_day") val resetDay: Int? = null,
    val description: String? = null,
    val notes: String? = null,
    @SerialName("rule_config") val ruleConfig: Map<String, String>? = null,
    @SerialName("country_code") val countryCode: String? = null,
    @SerialName("flag_emoji") val flagEmoji: String? = null,
    @SerialName("is_system") val isSystem: Boolean = false,
    @SerialName("is_active") val isActive: Boolean = true,
    @SerialName("display_order") val displayOrder: Int = 0
)

/**
 * Jurisdiction compliance summary
 */
@Serializable
data class JurisdictionSummary(
    @SerialName("jurisdiction_code") val jurisdictionCode: String,
    @SerialName("jurisdiction_name") val jurisdictionName: String,
    val category: String? = null,
    @SerialName("flag_emoji") val flagEmoji: String? = null,
    @SerialName("days_used") val daysUsed: Int,
    @SerialName("days_allowed") val daysAllowed: Int,
    @SerialName("days_remaining") val daysRemaining: Int,
    val percentage: Double,
    val status: String,
    @SerialName("window_start") val windowStart: String,
    @SerialName("window_end") val windowEnd: String,
    @SerialName("reference_date") val referenceDate: String,
    @SerialName("counting_method") val countingMethod: String,
    @SerialName("next_expiring_date") val nextExpiringDate: String? = null,
    @SerialName("next_expiring_days") val nextExpiringDays: Int? = null,
    @SerialName("trip_count") val tripCount: Int,

    // Optional breakdowns
    @SerialName("weighted_breakdown") val weightedBreakdown: WeightedBreakdown? = null,
    @SerialName("multi_year_breakdown") val multiYearBreakdown: MultiYearBreakdown? = null,
    @SerialName("uk_srt_breakdown") val ukSrtBreakdown: UKSRTBreakdown? = null
) {
    fun isOk(): Boolean = status == "ok"
    fun isWarning(): Boolean = status == "warning"
    fun isCritical(): Boolean = status == "critical"
    fun isExceeded(): Boolean = status == "exceeded"
}

/**
 * US SPT weighted breakdown
 */
@Serializable
data class WeightedBreakdown(
    val years: List<YearBreakdown>,
    @SerialName("total_weighted") val totalWeighted: Double,
    val threshold: Int,
    @SerialName("meets_threshold") val meetsThreshold: Boolean,
    @SerialName("meets_current_year_minimum") val meetsCurrentYearMinimum: Boolean
)

@Serializable
data class YearBreakdown(
    val year: Int,
    @SerialName("actual_days") val actualDays: Int,
    val weight: Double,
    @SerialName("weighted_days") val weightedDays: Double
)

/**
 * Ireland multi-year breakdown
 */
@Serializable
data class MultiYearBreakdown(
    @SerialName("current_year") val currentYear: YearDays,
    @SerialName("prior_year") val priorYear: YearDays,
    @SerialName("combined_days") val combinedDays: Int,
    @SerialName("primary_threshold") val primaryThreshold: Int,
    @SerialName("secondary_threshold") val secondaryThreshold: Int,
    @SerialName("meets_primary") val meetsPrimary: Boolean,
    @SerialName("meets_secondary") val meetsSecondary: Boolean
)

@Serializable
data class YearDays(
    val year: Int,
    val days: Int
)

/**
 * UK SRT breakdown
 */
@Serializable
data class UKSRTBreakdown(
    val result: String,
    @SerialName("days_in_uk") val daysInUK: Int,
    @SerialName("auto_overseas") val autoOverseas: AutoTestResult? = null,
    @SerialName("auto_uk") val autoUK: AutoTestResult? = null,
    @SerialName("sufficient_ties") val sufficientTies: SufficientTiesResult? = null,
    val explanation: String? = null
) {
    fun isResident(): Boolean = result == "resident"
    fun isNonResident(): Boolean = result == "non_resident"
}

@Serializable
data class AutoTestResult(
    val passed: Boolean,
    val test: String? = null,
    val description: String? = null
)

@Serializable
data class SufficientTiesResult(
    val resident: Boolean,
    @SerialName("tie_count") val tieCount: Int,
    @SerialName("tie_breakdown") val tieBreakdown: TieBreakdown,
    @SerialName("day_threshold") val dayThreshold: Int,
    @SerialName("days_in_uk") val daysInUK: Int
)

@Serializable
data class TieBreakdown(
    val family: Boolean,
    val accommodation: Boolean,
    val work: Boolean,
    @SerialName("ninety_day") val ninetyDay: Boolean,
    val country: Boolean
)

/**
 * Compliance overview across all jurisdictions
 */
@Serializable
data class ComplianceOverview(
    @SerialName("total_jurisdictions") val totalJurisdictions: Int,
    @SerialName("critical_count") val criticalCount: Int,
    @SerialName("warning_count") val warningCount: Int,
    @SerialName("ok_count") val okCount: Int,
    @SerialName("exceeded_count") val exceededCount: Int,
    val summaries: List<JurisdictionSummary>
) {
    fun hasCriticalIssues(): Boolean = criticalCount > 0 || exceededCount > 0
    fun needsAttention(): Boolean = warningCount > 0
}

/**
 * Tracked jurisdictions response
 */
@Serializable
data class TrackedJurisdictionsResponse(
    val jurisdictions: List<String>
)
