/**
 * SyncWorker.kt
 *
 * Background worker for syncing data with server.
 * Runs periodically to ensure data is synchronized.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.service

import android.content.Context
import android.util.Log
import androidx.work.*
import com.relo2france.schengen.data.SchengenRepository
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.util.concurrent.TimeUnit

class SyncWorker(
    appContext: Context,
    workerParams: WorkerParameters
) : CoroutineWorker(appContext, workerParams) {

    companion object {
        private const val TAG = "SyncWorker"
        const val WORK_NAME = "schengen_sync_worker"

        /**
         * Schedule periodic sync work
         */
        fun schedule(context: Context) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val syncRequest = PeriodicWorkRequestBuilder<SyncWorker>(
                repeatInterval = 6,
                repeatIntervalTimeUnit = TimeUnit.HOURS
            )
                .setConstraints(constraints)
                .setBackoffCriteria(
                    BackoffPolicy.EXPONENTIAL,
                    WorkRequest.MIN_BACKOFF_MILLIS,
                    TimeUnit.MILLISECONDS
                )
                .addTag("sync")
                .build()

            WorkManager.getInstance(context).enqueueUniquePeriodicWork(
                WORK_NAME,
                ExistingPeriodicWorkPolicy.KEEP,
                syncRequest
            )

            Log.d(TAG, "Scheduled periodic sync every 6 hours")
        }

        /**
         * Run sync immediately
         */
        fun syncNow(context: Context) {
            val constraints = Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()

            val syncRequest = OneTimeWorkRequestBuilder<SyncWorker>()
                .setConstraints(constraints)
                .addTag("sync_immediate")
                .build()

            WorkManager.getInstance(context).enqueue(syncRequest)
            Log.d(TAG, "Enqueued immediate sync")
        }

        /**
         * Cancel all sync work
         */
        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(WORK_NAME)
            Log.d(TAG, "Cancelled sync work")
        }
    }

    override suspend fun doWork(): Result = withContext(Dispatchers.IO) {
        Log.d(TAG, "Starting sync work")

        return@withContext try {
            val repository = SchengenRepository.getInstance(applicationContext)

            // Perform full sync
            repository.fullSync()

            Log.d(TAG, "Sync completed successfully")
            Result.success()

        } catch (e: Exception) {
            Log.e(TAG, "Sync failed: ${e.message}", e)

            // Retry if under retry limit
            if (runAttemptCount < 3) {
                Result.retry()
            } else {
                Result.failure()
            }
        }
    }
}
