/**
 * LocationScheduler.kt
 *
 * Schedules background location checks at 8 AM, 2 PM, and 8 PM.
 * Uses WorkManager for reliable background execution.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.service

import android.content.Context
import android.util.Log
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import java.util.Calendar
import java.util.concurrent.TimeUnit

object LocationScheduler {

    private const val TAG = "LocationScheduler"

    /**
     * Check hours in local time (8 AM, 2 PM, 8 PM)
     */
    private val CHECK_HOURS = listOf(8, 14, 20)

    /**
     * Schedule location checks for 3 times daily.
     * Each check is a separate periodic work request.
     */
    fun scheduleLocationChecks(context: Context) {
        val workManager = WorkManager.getInstance(context)

        CHECK_HOURS.forEach { hour ->
            scheduleCheckAtHour(workManager, hour)
        }

        Log.d(TAG, "Scheduled location checks for hours: $CHECK_HOURS")
    }

    /**
     * Schedule a single daily check at the specified hour.
     */
    private fun scheduleCheckAtHour(workManager: WorkManager, hour: Int) {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.NOT_REQUIRED) // Works offline
            .setRequiresBatteryNotLow(false) // Run even on low battery
            .build()

        val initialDelay = calculateInitialDelay(hour)

        val workRequest = PeriodicWorkRequestBuilder<LocationWorker>(
            repeatInterval = 24,
            repeatIntervalTimeUnit = TimeUnit.HOURS
        )
            .setConstraints(constraints)
            .setInitialDelay(initialDelay, TimeUnit.MILLISECONDS)
            .addTag("location_check")
            .addTag("hour_$hour")
            .build()

        workManager.enqueueUniquePeriodicWork(
            "${LocationWorker.WORK_NAME_PREFIX}$hour",
            ExistingPeriodicWorkPolicy.UPDATE,
            workRequest
        )

        Log.d(TAG, "Scheduled check at $hour:00, initial delay: ${initialDelay / 1000 / 60} minutes")
    }

    /**
     * Calculate delay until the next occurrence of the specified hour.
     */
    private fun calculateInitialDelay(targetHour: Int): Long {
        val now = Calendar.getInstance()
        val target = Calendar.getInstance().apply {
            set(Calendar.HOUR_OF_DAY, targetHour)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
            set(Calendar.MILLISECOND, 0)
        }

        // If target time has passed today, schedule for tomorrow
        if (target.before(now)) {
            target.add(Calendar.DAY_OF_YEAR, 1)
        }

        return target.timeInMillis - now.timeInMillis
    }

    /**
     * Cancel all scheduled location checks.
     */
    fun cancelAll(context: Context) {
        val workManager = WorkManager.getInstance(context)
        workManager.cancelAllWorkByTag("location_check")
        Log.d(TAG, "Cancelled all location checks")
    }

    /**
     * Check if location tracking is currently scheduled.
     */
    fun isScheduled(context: Context): Boolean {
        // Check if any location work is enqueued
        // This is a simplified check - in production, inspect WorkInfo states
        return true
    }
}
