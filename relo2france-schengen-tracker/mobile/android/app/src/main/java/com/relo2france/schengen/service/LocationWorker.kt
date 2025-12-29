/**
 * LocationWorker.kt
 *
 * Background worker for capturing GPS location.
 * Runs 3 times daily (8 AM, 2 PM, 8 PM) using WorkManager.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.service

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.Geocoder
import android.location.Location
import android.os.Build
import android.util.Log
import androidx.core.app.ActivityCompat
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import com.relo2france.schengen.SchengenTrackerApp
import com.relo2france.schengen.data.models.LocationReading
import com.relo2france.schengen.data.models.LocationSource
import com.relo2france.schengen.util.SchengenCountries
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.withContext
import java.util.Date
import java.util.Locale
import kotlin.coroutines.resume
import kotlin.coroutines.resumeWithException

class LocationWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    private val fusedLocationClient: FusedLocationProviderClient =
        LocationServices.getFusedLocationProviderClient(context)

    private val repository = SchengenTrackerApp.instance.repository

    override suspend fun doWork(): Result {
        Log.d(TAG, "Starting location capture")

        // Check permissions
        if (!hasLocationPermission()) {
            Log.w(TAG, "Location permission not granted")
            return Result.failure()
        }

        return try {
            // Capture location
            val location = captureLocation()

            // Reverse geocode
            val (countryCode, countryName, city) = reverseGeocode(location)

            // Check if Schengen country
            val isSchengen = countryCode?.let { SchengenCountries.isSchengen(it) } ?: false

            // Create location reading
            val reading = LocationReading(
                lat = location.latitude,
                lng = location.longitude,
                accuracy = location.accuracy.toDouble(),
                countryCode = countryCode,
                countryName = countryName,
                city = city,
                isSchengen = isSchengen,
                source = LocationSource.MOBILE_GPS,
                recordedAt = Date()
            )

            // Store locally
            repository.insertLocation(reading)

            // Check if we should create/update a trip
            if (isSchengen && countryCode != null) {
                repository.checkAndUpdateTrip(countryCode, countryName ?: countryCode)
            }

            // Queue for sync
            repository.queueLocationForSync(reading)

            Log.d(TAG, "Location captured: $countryName ($countryCode), Schengen: $isSchengen")
            Result.success()

        } catch (e: Exception) {
            Log.e(TAG, "Location capture failed", e)
            Result.retry()
        }
    }

    private fun hasLocationPermission(): Boolean {
        return ActivityCompat.checkSelfPermission(
            applicationContext,
            Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED ||
        ActivityCompat.checkSelfPermission(
            applicationContext,
            Manifest.permission.ACCESS_COARSE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }

    private suspend fun captureLocation(): Location {
        return suspendCancellableCoroutine { continuation ->
            val cancellationToken = CancellationTokenSource()

            if (ActivityCompat.checkSelfPermission(
                    applicationContext,
                    Manifest.permission.ACCESS_FINE_LOCATION
                ) != PackageManager.PERMISSION_GRANTED
            ) {
                continuation.resumeWithException(SecurityException("Location permission not granted"))
                return@suspendCancellableCoroutine
            }

            fusedLocationClient.getCurrentLocation(
                Priority.PRIORITY_BALANCED_POWER_ACCURACY,
                cancellationToken.token
            ).addOnSuccessListener { location ->
                if (location != null) {
                    continuation.resume(location)
                } else {
                    continuation.resumeWithException(Exception("Location is null"))
                }
            }.addOnFailureListener { exception ->
                continuation.resumeWithException(exception)
            }

            continuation.invokeOnCancellation {
                cancellationToken.cancel()
            }
        }
    }

    private suspend fun reverseGeocode(location: Location): Triple<String?, String?, String?> {
        return withContext(Dispatchers.IO) {
            try {
                val geocoder = Geocoder(applicationContext, Locale.getDefault())

                @Suppress("DEPRECATION")
                val addresses = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    suspendCancellableCoroutine { continuation ->
                        geocoder.getFromLocation(location.latitude, location.longitude, 1) { addresses ->
                            continuation.resume(addresses)
                        }
                    }
                } else {
                    geocoder.getFromLocation(location.latitude, location.longitude, 1)
                }

                val address = addresses?.firstOrNull()
                Triple(
                    address?.countryCode,
                    address?.countryName,
                    address?.locality
                )
            } catch (e: Exception) {
                Log.e(TAG, "Geocoding failed", e)
                Triple(null, null, null)
            }
        }
    }

    companion object {
        private const val TAG = "LocationWorker"
        const val WORK_NAME_PREFIX = "location_check_"
    }
}
