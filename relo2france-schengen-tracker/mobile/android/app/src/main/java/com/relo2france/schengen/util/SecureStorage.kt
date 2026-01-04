/**
 * SecureStorage.kt
 *
 * Secure storage for sensitive data like auth tokens.
 * Uses EncryptedSharedPreferences for Android.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.util

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

object SecureStorage {
    private const val PREFS_NAME = "schengen_secure_prefs"

    private var sharedPreferences: SharedPreferences? = null

    /**
     * Initialize secure storage
     */
    fun init(context: Context) {
        if (sharedPreferences != null) return

        val masterKey = MasterKey.Builder(context)
            .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
            .build()

        sharedPreferences = EncryptedSharedPreferences.create(
            context,
            PREFS_NAME,
            masterKey,
            EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
            EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
        )
    }

    /**
     * Save a string value
     */
    fun save(key: String, value: String) {
        sharedPreferences?.edit()?.putString(key, value)?.apply()
    }

    /**
     * Load a string value
     */
    fun load(key: String): String? {
        return sharedPreferences?.getString(key, null)
    }

    /**
     * Delete a value
     */
    fun delete(key: String) {
        sharedPreferences?.edit()?.remove(key)?.apply()
    }

    /**
     * Clear all stored values
     */
    fun clearAll() {
        sharedPreferences?.edit()?.clear()?.apply()
    }

    // Common keys
    object Keys {
        const val AUTH_TOKEN = "auth_token"
        const val USER_ID = "user_id"
        const val DEVICE_ID = "device_id"
        const val API_BASE_URL = "api_base_url"
        const val LAST_SYNC_TIME = "last_sync_time"
    }

    /**
     * Get or create device ID
     */
    fun getOrCreateDeviceId(): String {
        val existing = load(Keys.DEVICE_ID)
        if (existing != null) return existing

        val newId = java.util.UUID.randomUUID().toString()
        save(Keys.DEVICE_ID, newId)
        return newId
    }

    /**
     * Check if user is authenticated
     */
    fun isAuthenticated(): Boolean {
        return load(Keys.AUTH_TOKEN) != null
    }

    /**
     * Get auth token
     */
    fun getAuthToken(): String? {
        return load(Keys.AUTH_TOKEN)
    }

    /**
     * Set auth token
     */
    fun setAuthToken(token: String) {
        save(Keys.AUTH_TOKEN, token)
    }

    /**
     * Clear auth data (logout)
     */
    fun clearAuth() {
        delete(Keys.AUTH_TOKEN)
        delete(Keys.USER_ID)
    }
}
