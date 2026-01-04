/**
 * SchengenRepository.kt
 *
 * Repository pattern for data access.
 * Handles offline-first logic with sync to server.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.data

import android.content.Context
import android.util.Log
import com.relo2france.schengen.network.ApiClient
import com.relo2france.schengen.util.SchengenCountries
import com.relo2france.schengen.util.SecureStorage
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import java.time.LocalDate
import java.time.format.DateTimeFormatter

class SchengenRepository private constructor(private val context: Context) {

    companion object {
        private const val TAG = "SchengenRepository"
        private const val PREFS_NAME = "schengen_data"
        private const val KEY_TRIPS = "trips"
        private const val KEY_LOCATIONS = "locations"
        private const val KEY_PASSPORT_DATA = "passport_data"

        @Volatile
        private var INSTANCE: SchengenRepository? = null

        fun getInstance(context: Context): SchengenRepository {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: SchengenRepository(context.applicationContext).also { INSTANCE = it }
            }
        }
    }

    private val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    private val apiClient = ApiClient.getInstance()
    private val json = Json { ignoreUnknownKeys = true }

    // State flows for reactive UI
    private val _trips = MutableStateFlow<List<Trip>>(emptyList())
    val trips: StateFlow<List<Trip>> = _trips.asStateFlow()

    private val _locations = MutableStateFlow<List<LocationReading>>(emptyList())
    val locations: StateFlow<List<LocationReading>> = _locations.asStateFlow()

    private val _passportData = MutableStateFlow<PassportControlData?>(null)
    val passportData: StateFlow<PassportControlData?> = _passportData.asStateFlow()

    private val _isOnline = MutableStateFlow(true)
    val isOnline: StateFlow<Boolean> = _isOnline.asStateFlow()

    init {
        loadFromDisk()
    }

    // MARK: - Trips

    /**
     * Get all trips
     */
    suspend fun getTrips(forceRefresh: Boolean = false): List<Trip> {
        if (forceRefresh && _isOnline.value) {
            apiClient.getTrips().onSuccess { serverTrips ->
                _trips.value = serverTrips
                saveToDisk()
            }
        }
        return _trips.value
    }

    /**
     * Insert a new trip locally and queue for sync
     */
    suspend fun insertTrip(trip: Trip) {
        val newTrip = trip.copy(syncStatus = SyncStatus.PENDING)
        _trips.value = _trips.value + newTrip
        saveToDisk()

        // Try to sync immediately
        if (_isOnline.value) {
            syncTrip(newTrip)
        }
    }

    /**
     * Update an existing trip
     */
    suspend fun updateTrip(trip: Trip) {
        val updatedTrip = trip.copy(syncStatus = SyncStatus.PENDING)
        _trips.value = _trips.value.map {
            if (it.localId == trip.localId || (it.id > 0 && it.id == trip.id)) {
                updatedTrip
            } else {
                it
            }
        }
        saveToDisk()

        if (_isOnline.value) {
            syncTrip(updatedTrip)
        }
    }

    /**
     * Delete a trip
     */
    suspend fun deleteTrip(trip: Trip) {
        _trips.value = _trips.value.filter { it.localId != trip.localId }
        saveToDisk()

        if (_isOnline.value && trip.id > 0) {
            apiClient.deleteTrip(trip.id)
        }
    }

    /**
     * Check and update trip for current location
     */
    suspend fun checkAndUpdateTrip(countryCode: String, countryName: String) {
        val today = LocalDate.now()
        val todayStr = today.format(DateTimeFormatter.ISO_DATE)

        // Find existing trip for today in this country
        val existingTrip = _trips.value.find { trip ->
            trip.country == countryCode &&
            LocalDate.parse(trip.startDate) <= today &&
            LocalDate.parse(trip.endDate) >= today
        }

        if (existingTrip != null) {
            // Extend trip to today if needed
            if (LocalDate.parse(existingTrip.endDate) < today) {
                updateTrip(existingTrip.copy(endDate = todayStr))
            }
        } else {
            // Create new trip starting today
            val isSchengen = SchengenCountries.isSchengen(countryCode)
            val category = if (isSchengen) TripCategory.SCHENGEN else TripCategory.NON_SCHENGEN

            insertTrip(
                Trip(
                    startDate = todayStr,
                    endDate = todayStr,
                    country = countryCode,
                    category = category,
                    notes = "Auto-detected in $countryName"
                )
            )
        }
    }

    // MARK: - Locations

    /**
     * Insert a location reading
     */
    suspend fun insertLocation(location: LocationReading) {
        _locations.value = _locations.value + location
        saveToDisk()

        Log.d(TAG, "Inserted location: ${location.countryCode} (${location.lat}, ${location.lng})")
    }

    /**
     * Get recent locations
     */
    fun getRecentLocations(limit: Int = 100): List<LocationReading> {
        return _locations.value.takeLast(limit)
    }

    /**
     * Get pending locations for sync
     */
    fun getPendingLocations(): List<LocationReading> {
        return _locations.value.filter { it.syncStatus == SyncStatus.PENDING }
    }

    // MARK: - Passport Control

    /**
     * Get passport control data (for border officer display)
     */
    suspend fun getPassportControlData(forceRefresh: Boolean = false): PassportControlData? {
        if (forceRefresh && _isOnline.value) {
            apiClient.getPassportControl().onSuccess { data ->
                _passportData.value = data
                saveToDisk()
            }
        }
        return _passportData.value
    }

    /**
     * Calculate passport control data locally (fallback)
     */
    fun calculateLocalPassportData(): PassportControlData {
        val today = LocalDate.now()
        val periodEnd = today
        val periodStart = today.minusDays(179)

        // Calculate days used in rolling 180-day window
        var daysUsed = 0
        for (trip in _trips.value) {
            if (trip.category != TripCategory.SCHENGEN) continue

            val tripStart = LocalDate.parse(trip.startDate)
            val tripEnd = LocalDate.parse(trip.endDate)

            // Clamp to window
            val effectiveStart = maxOf(tripStart, periodStart)
            val effectiveEnd = minOf(tripEnd, periodEnd)

            if (effectiveEnd >= effectiveStart) {
                daysUsed += (effectiveEnd.toEpochDay() - effectiveStart.toEpochDay()).toInt() + 1
            }
        }

        val currentTrip = _trips.value.find { trip ->
            LocalDate.parse(trip.startDate) <= today && LocalDate.parse(trip.endDate) >= today
        }

        val recentTrips = _trips.value
            .filter { it.category == TripCategory.SCHENGEN }
            .sortedByDescending { it.startDate }
            .take(5)

        return PassportControlData(
            daysUsed = daysUsed,
            daysRemaining = 90 - daysUsed,
            periodStart = periodStart.format(DateTimeFormatter.ISO_DATE),
            periodEnd = periodEnd.format(DateTimeFormatter.ISO_DATE),
            currentCountry = currentTrip?.country,
            currentTrip = currentTrip,
            recentTrips = recentTrips,
            lastUpdated = java.time.Instant.now().toString()
        )
    }

    // MARK: - Sync

    /**
     * Sync a single trip
     */
    private suspend fun syncTrip(trip: Trip) {
        val result = if (trip.id > 0) {
            apiClient.updateTrip(trip.id, trip)
        } else {
            apiClient.createTrip(trip)
        }

        result.onSuccess { serverTrip ->
            _trips.value = _trips.value.map {
                if (it.localId == trip.localId) {
                    serverTrip.copy(localId = trip.localId, syncStatus = SyncStatus.SYNCED)
                } else {
                    it
                }
            }
            saveToDisk()
        }.onFailure { error ->
            Log.e(TAG, "Failed to sync trip: ${error.message}")
            _trips.value = _trips.value.map {
                if (it.localId == trip.localId) {
                    it.copy(syncStatus = SyncStatus.FAILED)
                } else {
                    it
                }
            }
            saveToDisk()
        }
    }

    /**
     * Sync pending locations to server
     */
    suspend fun syncLocations() {
        val pending = getPendingLocations()
        if (pending.isEmpty()) return

        apiClient.submitLocations(pending).onSuccess { results ->
            val syncedIds = results.filter { it.success }.mapNotNull { it.localId }.toSet()
            _locations.value = _locations.value.map { loc ->
                if (syncedIds.contains(loc.localId)) {
                    loc.copy(syncStatus = SyncStatus.SYNCED)
                } else {
                    loc
                }
            }
            saveToDisk()
        }.onFailure { error ->
            Log.e(TAG, "Failed to sync locations: ${error.message}")
        }
    }

    /**
     * Full sync with server
     */
    suspend fun fullSync() {
        val lastSync = SecureStorage.load(SecureStorage.Keys.LAST_SYNC_TIME)
        val deviceId = SecureStorage.getOrCreateDeviceId()

        // Build changes
        val pendingTrips = _trips.value.filter { it.syncStatus == SyncStatus.PENDING }
        val changes = pendingTrips.map { trip ->
            SyncChange(
                type = "trip",
                action = if (trip.id > 0) "update" else "create",
                localId = trip.localId,
                data = mapOf(
                    "start_date" to trip.startDate,
                    "end_date" to trip.endDate,
                    "country" to trip.country,
                    "category" to trip.category.name.lowercase()
                )
            )
        }

        val request = SyncRequest(
            lastSync = lastSync,
            deviceId = deviceId,
            changes = changes
        )

        apiClient.sync(request).onSuccess { response ->
            // Process results
            for (result in response.syncResults) {
                if (result.success && result.localId != null) {
                    _trips.value = _trips.value.map { trip ->
                        if (trip.localId == result.localId) {
                            trip.copy(
                                id = result.serverId ?: trip.id,
                                syncStatus = SyncStatus.SYNCED
                            )
                        } else {
                            trip
                        }
                    }
                }
            }

            // Apply server changes
            for (change in response.serverChanges) {
                when (change.action) {
                    "create", "update" -> {
                        val existing = _trips.value.find { it.id == change.data.id }
                        if (existing != null) {
                            _trips.value = _trips.value.map {
                                if (it.id == change.data.id) change.data.copy(syncStatus = SyncStatus.SYNCED)
                                else it
                            }
                        } else {
                            _trips.value = _trips.value + change.data.copy(syncStatus = SyncStatus.SYNCED)
                        }
                    }
                    "delete" -> {
                        _trips.value = _trips.value.filter { it.id != change.data.id }
                    }
                }
            }

            // Update last sync time
            SecureStorage.save(SecureStorage.Keys.LAST_SYNC_TIME, response.serverTime)
            saveToDisk()

            Log.d(TAG, "Full sync completed: ${response.syncResults.size} results, ${response.serverChanges.size} changes")
        }.onFailure { error ->
            Log.e(TAG, "Full sync failed: ${error.message}")
        }

        // Also sync locations
        syncLocations()
    }

    // MARK: - Network Status

    /**
     * Update online status
     */
    fun setOnlineStatus(online: Boolean) {
        _isOnline.value = online
    }

    // MARK: - Persistence

    private fun saveToDisk() {
        prefs.edit().apply {
            putString(KEY_TRIPS, json.encodeToString(_trips.value))
            putString(KEY_LOCATIONS, json.encodeToString(_locations.value))
            _passportData.value?.let { putString(KEY_PASSPORT_DATA, json.encodeToString(it)) }
            apply()
        }
    }

    private fun loadFromDisk() {
        prefs.getString(KEY_TRIPS, null)?.let { data ->
            try {
                _trips.value = json.decodeFromString(data)
            } catch (e: Exception) {
                Log.e(TAG, "Failed to load trips: ${e.message}")
            }
        }

        prefs.getString(KEY_LOCATIONS, null)?.let { data ->
            try {
                _locations.value = json.decodeFromString(data)
            } catch (e: Exception) {
                Log.e(TAG, "Failed to load locations: ${e.message}")
            }
        }

        prefs.getString(KEY_PASSPORT_DATA, null)?.let { data ->
            try {
                _passportData.value = json.decodeFromString(data)
            } catch (e: Exception) {
                Log.e(TAG, "Failed to load passport data: ${e.message}")
            }
        }
    }

    /**
     * Clear all local data
     */
    fun clearAll() {
        _trips.value = emptyList()
        _locations.value = emptyList()
        _passportData.value = null
        prefs.edit().clear().apply()
    }
}
