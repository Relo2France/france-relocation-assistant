/**
 * FCMService.kt
 *
 * Firebase Cloud Messaging service for handling push notifications.
 * Receives FCM tokens and notification payloads.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.util.Log
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.mytravelstatus.app.MainActivity
import com.mytravelstatus.app.MyTravelStatusApp
import com.mytravelstatus.app.R
import com.mytravelstatus.app.network.ApiClient
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch

class FCMService : FirebaseMessagingService() {

    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    companion object {
        private const val TAG = "FCMService"
    }

    /**
     * Called when a new FCM token is generated.
     * This occurs on initial app install or when token is refreshed.
     */
    override fun onNewToken(token: String) {
        super.onNewToken(token)
        Log.d(TAG, "New FCM token: $token")

        // Save token locally
        PushNotificationManager.saveToken(applicationContext, token)

        // Register with backend if user is authenticated
        serviceScope.launch {
            PushNotificationManager.registerTokenWithBackend(applicationContext, token)
        }
    }

    /**
     * Called when a message is received.
     * This handles both notification and data messages.
     */
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        super.onMessageReceived(remoteMessage)
        Log.d(TAG, "Message received from: ${remoteMessage.from}")

        // Handle data payload
        val data = remoteMessage.data
        if (data.isNotEmpty()) {
            Log.d(TAG, "Message data: $data")
            handleDataMessage(data)
        }

        // Handle notification payload (shown automatically if app is in background)
        remoteMessage.notification?.let { notification ->
            Log.d(TAG, "Notification: ${notification.title} - ${notification.body}")

            // Show notification manually if app is in foreground
            showNotification(
                title = notification.title ?: getString(R.string.app_name),
                body = notification.body ?: "",
                data = data
            )
        }

        // If no notification payload but has data, create notification from data
        if (remoteMessage.notification == null && data.isNotEmpty()) {
            val title = data["title"] ?: getString(R.string.app_name)
            val body = data["body"] ?: data["message"] ?: ""
            if (body.isNotEmpty()) {
                showNotification(title, body, data)
            }
        }
    }

    /**
     * Handle data-only messages (e.g., for silent syncs)
     */
    private fun handleDataMessage(data: Map<String, String>) {
        val type = data["type"]

        when (type) {
            "sync" -> {
                // Trigger background sync
                Log.d(TAG, "Triggering sync from push notification")
                serviceScope.launch {
                    try {
                        // Could trigger SyncWorker here if needed
                        Log.d(TAG, "Background sync triggered")
                    } catch (e: Exception) {
                        Log.e(TAG, "Sync failed", e)
                    }
                }
            }
            "threshold_warning", "threshold_danger" -> {
                // Schengen alert
                Log.d(TAG, "Schengen alert received: $type")
            }
            "trip_reminder" -> {
                // Trip reminder
                Log.d(TAG, "Trip reminder received")
            }
            "calendar_sync" -> {
                // Calendar sync notification
                Log.d(TAG, "Calendar sync notification received")
            }
            else -> {
                Log.d(TAG, "Unknown notification type: $type")
            }
        }
    }

    /**
     * Show a notification to the user
     */
    private fun showNotification(
        title: String,
        body: String,
        data: Map<String, String> = emptyMap()
    ) {
        val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

        // Create intent for notification tap
        val intent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            // Pass notification data to activity
            data.forEach { (key, value) ->
                putExtra(key, value)
            }
        }

        val pendingIntent = PendingIntent.getActivity(
            this,
            System.currentTimeMillis().toInt(),
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        // Determine notification channel based on type
        val channelId = when (data["type"]) {
            "threshold_warning", "threshold_danger" -> MyTravelStatusApp.CHANNEL_ALERTS
            "sync" -> MyTravelStatusApp.CHANNEL_SYNC
            "location_checkin" -> MyTravelStatusApp.CHANNEL_LOCATION
            else -> MyTravelStatusApp.CHANNEL_ALERTS
        }

        // Build notification
        val notification = NotificationCompat.Builder(this, channelId)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setPriority(
                if (data["type"] == "threshold_danger")
                    NotificationCompat.PRIORITY_HIGH
                else
                    NotificationCompat.PRIORITY_DEFAULT
            )
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .build()

        // Show notification
        val notificationId = System.currentTimeMillis().toInt()
        notificationManager.notify(notificationId, notification)
    }
}

/**
 * Manager for push notification operations
 */
object PushNotificationManager {
    private const val TAG = "PushNotificationManager"
    private const val PREF_FCM_TOKEN = "fcm_token"

    /**
     * Save FCM token to shared preferences
     */
    fun saveToken(context: Context, token: String) {
        val prefs = context.getSharedPreferences(MyTravelStatusApp.PREFS_NAME, Context.MODE_PRIVATE)
        prefs.edit().putString(PREF_FCM_TOKEN, token).apply()
        Log.d(TAG, "FCM token saved")
    }

    /**
     * Get saved FCM token
     */
    fun getToken(context: Context): String? {
        val prefs = context.getSharedPreferences(MyTravelStatusApp.PREFS_NAME, Context.MODE_PRIVATE)
        return prefs.getString(PREF_FCM_TOKEN, null)
    }

    /**
     * Register token with backend server
     */
    suspend fun registerTokenWithBackend(context: Context, token: String) {
        val apiClient = ApiClient.getInstance(context)

        if (!apiClient.isAuthenticated) {
            Log.d(TAG, "Not authenticated, skipping token registration")
            return
        }

        try {
            val deviceId = android.provider.Settings.Secure.getString(
                context.contentResolver,
                android.provider.Settings.Secure.ANDROID_ID
            )

            val packageInfo = context.packageManager.getPackageInfo(context.packageName, 0)
            val appVersion = packageInfo.versionName ?: "1.0.0"

            apiClient.registerDevice(
                deviceId = deviceId,
                pushToken = token,
                platform = "android",
                appVersion = appVersion
            )

            Log.d(TAG, "Device registered with backend")
        } catch (e: Exception) {
            Log.e(TAG, "Failed to register device with backend", e)
        }
    }

    /**
     * Unregister device from push notifications
     */
    suspend fun unregisterDevice(context: Context) {
        val apiClient = ApiClient.getInstance(context)

        try {
            val deviceId = android.provider.Settings.Secure.getString(
                context.contentResolver,
                android.provider.Settings.Secure.ANDROID_ID
            )

            apiClient.unregisterDevice(deviceId)

            // Clear saved token
            val prefs = context.getSharedPreferences(MyTravelStatusApp.PREFS_NAME, Context.MODE_PRIVATE)
            prefs.edit().remove(PREF_FCM_TOKEN).apply()

            Log.d(TAG, "Device unregistered")
        } catch (e: Exception) {
            Log.e(TAG, "Failed to unregister device", e)
        }
    }

    /**
     * Called on login to ensure device is registered
     */
    suspend fun onLogin(context: Context) {
        val token = getToken(context)
        if (token != null) {
            registerTokenWithBackend(context, token)
        }
    }
}
