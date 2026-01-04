/**
 * Models.kt
 *
 * Data classes matching iOS and TypeScript definitions.
 * Used for API communication and local storage.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.data

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
