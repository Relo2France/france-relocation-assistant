/**
 * ApiClient.kt
 *
 * REST API client for WordPress backend.
 * Handles authentication, requests, and error handling.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.network

import com.relo2france.schengen.data.*
import com.relo2france.schengen.util.SecureStorage
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.IOException
import java.util.concurrent.TimeUnit

class ApiClient private constructor() {

    companion object {
        @Volatile
        private var INSTANCE: ApiClient? = null

        fun getInstance(): ApiClient {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: ApiClient().also { INSTANCE = it }
            }
        }

        private const val DEFAULT_BASE_URL = "https://relo2france.com/wp-json/r2f-schengen/v1"
    }

    private val json = Json {
        ignoreUnknownKeys = true
        isLenient = true
        encodeDefaults = true
    }

    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()

    private val baseUrl: String
        get() = SecureStorage.load(SecureStorage.Keys.API_BASE_URL) ?: DEFAULT_BASE_URL

    private val authToken: String?
        get() = SecureStorage.getAuthToken()

    // MARK: - Authentication

    /**
     * Login with WordPress credentials
     */
    suspend fun login(username: String, password: String): Result<AuthResponse> {
        return post("/auth/login", mapOf("username" to username, "password" to password))
    }

    /**
     * Logout and invalidate token
     */
    suspend fun logout(): Result<Unit> {
        val result = post<Map<String, Boolean>>("/auth/logout", emptyMap<String, String>())
        SecureStorage.clearAuth()
        return result.map { }
    }

    // MARK: - App Status

    /**
     * Get current app status
     */
    suspend fun getAppStatus(): Result<AppStatusResponse> {
        return get("/app/status")
    }

    // MARK: - Passport Control

    /**
     * Get passport control data for border display
     */
    suspend fun getPassportControl(): Result<PassportControlData> {
        return get("/passport-control")
    }

    // MARK: - Trips

    /**
     * Get all trips
     */
    suspend fun getTrips(): Result<List<Trip>> {
        return get("/trips")
    }

    /**
     * Create a new trip
     */
    suspend fun createTrip(trip: Trip): Result<Trip> {
        return post("/trips", trip)
    }

    /**
     * Update an existing trip
     */
    suspend fun updateTrip(id: Int, trip: Trip): Result<Trip> {
        return put("/trips/$id", trip)
    }

    /**
     * Delete a trip
     */
    suspend fun deleteTrip(id: Int): Result<Unit> {
        return delete("/trips/$id")
    }

    // MARK: - Locations

    /**
     * Submit location readings in batch
     */
    suspend fun submitLocations(locations: List<LocationReading>): Result<List<SyncResult>> {
        return post("/locations/batch", mapOf("locations" to locations))
    }

    // MARK: - Sync

    /**
     * Perform full sync
     */
    suspend fun sync(request: SyncRequest): Result<SyncResponse> {
        return post("/sync", request)
    }

    /**
     * Get changes since last sync
     */
    suspend fun getChanges(since: String?): Result<List<ServerChange>> {
        val params = since?.let { "?since=$it" } ?: ""
        return get("/changes$params")
    }

    // MARK: - Device Registration

    /**
     * Register device for push notifications
     */
    suspend fun registerDevice(registration: DeviceRegistration): Result<Map<String, Boolean>> {
        return post("/device/register", registration)
    }

    /**
     * Unregister device
     */
    suspend fun unregisterDevice(deviceId: String): Result<Map<String, Boolean>> {
        return post("/device/unregister", mapOf("device_id" to deviceId))
    }

    // MARK: - HTTP Methods

    private suspend inline fun <reified T> get(endpoint: String): Result<T> {
        return request("GET", endpoint, null)
    }

    private suspend inline fun <reified T, reified B> post(endpoint: String, body: B): Result<T> {
        val jsonBody = json.encodeToString(body)
        return request("POST", endpoint, jsonBody)
    }

    private suspend inline fun <reified T, reified B> put(endpoint: String, body: B): Result<T> {
        val jsonBody = json.encodeToString(body)
        return request("PUT", endpoint, jsonBody)
    }

    private suspend inline fun <reified T> delete(endpoint: String): Result<T> {
        return request("DELETE", endpoint, null)
    }

    private suspend inline fun <reified T> request(
        method: String,
        endpoint: String,
        body: String?
    ): Result<T> = withContext(Dispatchers.IO) {
        try {
            val url = "$baseUrl$endpoint"

            val requestBuilder = Request.Builder()
                .url(url)
                .header("Content-Type", "application/json")
                .header("Accept", "application/json")

            // Add auth header if token exists
            authToken?.let {
                requestBuilder.header("Authorization", "Bearer $it")
            }

            // Set method and body
            val requestBody = body?.toRequestBody("application/json".toMediaType())
            when (method) {
                "GET" -> requestBuilder.get()
                "POST" -> requestBuilder.post(requestBody ?: "".toRequestBody())
                "PUT" -> requestBuilder.put(requestBody ?: "".toRequestBody())
                "DELETE" -> requestBuilder.delete(requestBody)
            }

            val response = client.newCall(requestBuilder.build()).execute()
            val responseBody = response.body?.string() ?: ""

            if (response.isSuccessful) {
                if (T::class == Unit::class) {
                    @Suppress("UNCHECKED_CAST")
                    Result.success(Unit as T)
                } else {
                    val parsed = json.decodeFromString<T>(responseBody)
                    Result.success(parsed)
                }
            } else {
                val error = try {
                    json.decodeFromString<ApiError>(responseBody)
                } catch (e: Exception) {
                    ApiError("unknown", "Request failed with status ${response.code}")
                }
                Result.failure(ApiException(error.code, error.message, response.code))
            }
        } catch (e: IOException) {
            Result.failure(ApiException("network_error", "Network error: ${e.message}", 0))
        } catch (e: Exception) {
            Result.failure(ApiException("parse_error", "Failed to parse response: ${e.message}", 0))
        }
    }
}

/**
 * API exception with error details
 */
class ApiException(
    val code: String,
    override val message: String,
    val statusCode: Int
) : Exception(message)
