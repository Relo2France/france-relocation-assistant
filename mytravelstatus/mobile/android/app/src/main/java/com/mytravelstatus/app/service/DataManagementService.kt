/**
 * DataManagementService.kt
 *
 * Handles data export and account deletion for GDPR/privacy compliance.
 * - Export all user data in JSON format
 * - Full account and data deletion
 * - Clear local data only option
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.content.Context
import android.content.Intent
import android.util.Log
import androidx.core.content.FileProvider
import com.mytravelstatus.app.data.TravelStatusRepository
import com.mytravelstatus.app.network.ApiClient
import com.mytravelstatus.app.util.SecureStorage
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import kotlinx.serialization.json.putJsonArray
import kotlinx.serialization.json.putJsonObject
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Result of data export
 */
data class DataExportResult(
    val jsonData: String,
    val filename: String,
    val exportDate: Date,
    val file: File? = null
)

/**
 * Account deletion status
 */
enum class DeletionStatus {
    NOT_STARTED,
    IN_PROGRESS,
    COMPLETED,
    FAILED
}

object DataManagementService {
    private const val TAG = "DataManagementService"

    // StateFlows for reactive UI updates
    private val _isExporting = MutableStateFlow(false)
    val isExporting: StateFlow<Boolean> = _isExporting.asStateFlow()

    private val _isDeletingAccount = MutableStateFlow(false)
    val isDeletingAccount: StateFlow<Boolean> = _isDeletingAccount.asStateFlow()

    private val _deletionStatus = MutableStateFlow(DeletionStatus.NOT_STARTED)
    val deletionStatus: StateFlow<DeletionStatus> = _deletionStatus.asStateFlow()

    private val _exportError = MutableStateFlow<String?>(null)
    val exportError: StateFlow<String?> = _exportError.asStateFlow()

    private val _deletionError = MutableStateFlow<String?>(null)
    val deletionError: StateFlow<String?> = _deletionError.asStateFlow()

    private val json = Json { prettyPrint = true }

    // MARK: - Data Export

    /**
     * Export all user data to JSON
     */
    suspend fun exportAllData(context: Context): DataExportResult = withContext(Dispatchers.IO) {
        _isExporting.value = true
        _exportError.value = null

        try {
            val repository = TravelStatusRepository.getInstance(context)

            // Build export data
            val exportData = buildJsonObject {
                // Export metadata
                putJsonObject("exportInfo") {
                    put("exportDate", SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'", Locale.US).format(Date()))
                    put("appVersion", getAppVersion(context))
                    putJsonArray("dataTypes") {
                        add(kotlinx.serialization.json.JsonPrimitive("trips"))
                        add(kotlinx.serialization.json.JsonPrimitive("locations"))
                        add(kotlinx.serialization.json.JsonPrimitive("settings"))
                        add(kotlinx.serialization.json.JsonPrimitive("analytics"))
                    }
                }

                // Export trips
                val trips = repository.trips.value
                putJsonArray("trips") {
                    trips.forEach { trip ->
                        add(buildJsonObject {
                            put("id", trip.id)
                            put("startDate", trip.startDate?.toString() ?: "")
                            put("endDate", trip.endDate?.toString() ?: "")
                            put("country", trip.country)
                            put("category", trip.category.name)
                            put("notes", trip.notes ?: "")
                            put("locationSource", trip.locationSource?.name ?: "manual")
                        })
                    }
                }

                // Export settings
                putJsonObject("settings") {
                    val summary = PrivacySettings.getDataCollectionSummary()
                    summary.forEach { (key, value) ->
                        when (value) {
                            is Boolean -> put(key, value)
                            is String -> put(key, value)
                            else -> put(key, value.toString())
                        }
                    }
                }

                // Export analytics (if enabled)
                putJsonObject("analytics") {
                    val analyticsData = AnalyticsManager.exportData()
                    analyticsData.forEach { (key, value) ->
                        when (value) {
                            is Boolean -> put(key, value)
                            is String -> put(key, value)
                            else -> put(key, value.toString())
                        }
                    }
                }

                // Sync status
                putJsonObject("syncStatus") {
                    put("lastSync", SecureStorage.load(SecureStorage.Keys.LAST_SYNC_TIME) ?: "never")
                }
            }

            val jsonString = json.encodeToString(exportData)

            // Create file
            val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.US)
            val filename = "mytravelstatus-export-${dateFormat.format(Date())}.json"

            val exportDir = File(context.cacheDir, "exports")
            exportDir.mkdirs()
            val exportFile = File(exportDir, filename)
            exportFile.writeText(jsonString)

            AnalyticsManager.track(AnalyticsEvent.DATA_EXPORT_REQUESTED)

            Log.d(TAG, "Data export completed: $filename")

            DataExportResult(
                jsonData = jsonString,
                filename = filename,
                exportDate = Date(),
                file = exportFile
            )
        } catch (e: Exception) {
            Log.e(TAG, "Export failed", e)
            _exportError.value = e.message
            throw e
        } finally {
            _isExporting.value = false
        }
    }

    /**
     * Share exported data via system share sheet
     */
    fun shareExportedData(context: Context, result: DataExportResult) {
        val file = result.file ?: return

        try {
            val uri = FileProvider.getUriForFile(
                context,
                "${context.packageName}.fileprovider",
                file
            )

            val intent = Intent(Intent.ACTION_SEND).apply {
                type = "application/json"
                putExtra(Intent.EXTRA_STREAM, uri)
                putExtra(Intent.EXTRA_SUBJECT, "MyTravelStatus Data Export")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }

            val chooser = Intent.createChooser(intent, "Share Data Export")
            chooser.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            context.startActivity(chooser)
        } catch (e: Exception) {
            Log.e(TAG, "Failed to share export", e)
            _exportError.value = "Failed to share: ${e.message}"
        }
    }

    // MARK: - Account Deletion

    /**
     * Delete all user data (local and server)
     */
    suspend fun deleteAccountAndData(context: Context) = withContext(Dispatchers.IO) {
        _isDeletingAccount.value = true
        _deletionStatus.value = DeletionStatus.IN_PROGRESS
        _deletionError.value = null

        AnalyticsManager.track(AnalyticsEvent.ACCOUNT_DELETION_REQUESTED)

        try {
            // Step 1: Request server-side deletion
            requestServerDeletion()

            // Step 2: Clear local database
            val repository = TravelStatusRepository.getInstance(context)
            repository.clearAll()

            // Step 3: Clear privacy settings
            PrivacySettings.clearAllData()

            // Step 4: Clear secure storage (auth token, etc.)
            SecureStorage.clearAll()

            // Step 5: Clear analytics data
            AnalyticsManager.clearAllData()

            // Step 6: Unregister push notifications
            PushNotificationManager.unregisterDevice(context)

            // Step 7: Disable background tasks
            LocationScheduler.cancelAllTasks(context)

            // Step 8: Deactivate demo mode if active
            DemoModeService.deactivateDemoMode()

            _deletionStatus.value = DeletionStatus.COMPLETED
            Log.d(TAG, "Account deletion completed")

        } catch (e: Exception) {
            Log.e(TAG, "Account deletion failed", e)
            _deletionStatus.value = DeletionStatus.FAILED
            _deletionError.value = e.message
            throw e
        } finally {
            _isDeletingAccount.value = false
        }
    }

    /**
     * Clear only local data (keep server data)
     */
    suspend fun clearLocalDataOnly(context: Context) = withContext(Dispatchers.IO) {
        try {
            val repository = TravelStatusRepository.getInstance(context)
            repository.clearAll()

            // Clear cached exports
            val exportDir = File(context.cacheDir, "exports")
            exportDir.deleteRecursively()

            Log.d(TAG, "Local data cleared")
        } catch (e: Exception) {
            Log.e(TAG, "Failed to clear local data", e)
            throw e
        }
    }

    // MARK: - Private Methods

    private suspend fun requestServerDeletion() {
        val apiClient = ApiClient.getInstance()

        // Check if authenticated
        val token = SecureStorage.getAuthToken()
        if (token == null) {
            Log.d(TAG, "No auth token, skipping server deletion")
            return
        }

        val result = apiClient.deleteAccount()
        result.onFailure { error ->
            Log.e(TAG, "Server deletion failed: ${error.message}")
            throw error
        }
    }

    private fun getAppVersion(context: Context): String {
        return try {
            val packageInfo = context.packageManager.getPackageInfo(context.packageName, 0)
            packageInfo.versionName ?: "Unknown"
        } catch (e: Exception) {
            "Unknown"
        }
    }

    /**
     * Reset state (for testing)
     */
    fun reset() {
        _isExporting.value = false
        _isDeletingAccount.value = false
        _deletionStatus.value = DeletionStatus.NOT_STARTED
        _exportError.value = null
        _deletionError.value = null
    }
}
