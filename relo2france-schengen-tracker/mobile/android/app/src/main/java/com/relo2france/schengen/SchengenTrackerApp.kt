/**
 * MyTravelStatusApp.kt
 *
 * Main Application class for MyTravelStatus Android app.
 * Initializes WorkManager for background tasks.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.relo2france.schengen

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import androidx.work.Configuration
import com.relo2france.schengen.service.LocationScheduler
import com.relo2france.schengen.util.SecureStorage

class MyTravelStatusApp : Application(), Configuration.Provider {

    override fun onCreate() {
        super.onCreate()
        instance = this

        // Initialize secure storage
        SecureStorage.init(this)

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
                getString(R.string.notification_channel_location),
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = getString(R.string.notification_channel_location_desc)
            }
            notificationManager.createNotificationChannel(locationChannel)

            // Alerts channel
            val alertsChannel = NotificationChannel(
                CHANNEL_ALERTS,
                getString(R.string.notification_channel_alerts),
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = getString(R.string.notification_channel_alerts_desc)
            }
            notificationManager.createNotificationChannel(alertsChannel)

            // Sync channel
            val syncChannel = NotificationChannel(
                CHANNEL_SYNC,
                getString(R.string.notification_channel_sync),
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = getString(R.string.notification_channel_sync_desc)
            }
            notificationManager.createNotificationChannel(syncChannel)
        }
    }

    companion object {
        const val PREFS_NAME = "mytravelstatus_prefs"
        const val CHANNEL_LOCATION = "location_tracking"
        const val CHANNEL_ALERTS = "travel_alerts"
        const val CHANNEL_SYNC = "sync_status"

        lateinit var instance: MyTravelStatusApp
            private set
    }
}

// Legacy alias for compatibility
typealias SchengenTrackerApp = MyTravelStatusApp
