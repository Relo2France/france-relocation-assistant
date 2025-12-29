/**
 * SchengenTrackerApp.kt
 *
 * Main Application class for Schengen Tracker Android app.
 * Initializes WorkManager for background tasks.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import androidx.work.Configuration
import androidx.work.WorkManager
import com.relo2france.schengen.data.local.LocalDatabase
import com.relo2france.schengen.data.repository.SchengenRepository
import com.relo2france.schengen.service.LocationScheduler

class SchengenTrackerApp : Application(), Configuration.Provider {

    lateinit var repository: SchengenRepository
        private set

    override fun onCreate() {
        super.onCreate()
        instance = this

        // Initialize database
        val database = LocalDatabase.getInstance(this)

        // Initialize repository
        repository = SchengenRepository(
            apiClient = ApiClient.getInstance(this),
            database = database,
            preferences = getSharedPreferences(PREFS_NAME, MODE_PRIVATE)
        )

        // Create notification channels
        createNotificationChannels()

        // Schedule background location checks
        LocationScheduler.scheduleLocationChecks(this)
    }

    override val workManagerConfiguration: Configuration
        get() = Configuration.Builder()
            .setMinimumLoggingLevel(android.util.Log.INFO)
            .build()

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val notificationManager = getSystemService(NotificationManager::class.java)

            // Location tracking channel
            val locationChannel = NotificationChannel(
                CHANNEL_LOCATION,
                "Location Tracking",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Background location tracking notifications"
            }
            notificationManager.createNotificationChannel(locationChannel)

            // Alerts channel
            val alertsChannel = NotificationChannel(
                CHANNEL_ALERTS,
                "Schengen Alerts",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Important alerts about your Schengen status"
            }
            notificationManager.createNotificationChannel(alertsChannel)

            // Sync channel
            val syncChannel = NotificationChannel(
                CHANNEL_SYNC,
                "Sync Status",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Data synchronization notifications"
            }
            notificationManager.createNotificationChannel(syncChannel)
        }
    }

    companion object {
        const val PREFS_NAME = "schengen_prefs"
        const val CHANNEL_LOCATION = "location_tracking"
        const val CHANNEL_ALERTS = "schengen_alerts"
        const val CHANNEL_SYNC = "sync_status"

        lateinit var instance: SchengenTrackerApp
            private set
    }
}
