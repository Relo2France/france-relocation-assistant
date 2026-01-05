/**
 * PhotoPickerService.kt
 *
 * Photo picker with EXIF metadata extraction.
 * Uses Android Photo Picker (no full gallery access needed).
 * Requires explicit user confirmation before reading EXIF data.
 *
 * Privacy features:
 * - Uses system photo picker (limited access)
 * - Shows confirmation before reading EXIF
 * - Only extracts location data from selected photos
 * - Photos are never uploaded to servers
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.content.Context
import android.net.Uri
import android.util.Log
import androidx.exifinterface.media.ExifInterface
import com.mytravelstatus.app.util.TravelStatusCountries
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.withContext
import java.io.InputStream
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Photo with extracted metadata
 */
data class PhotoMetadata(
    val uri: Uri,
    val dateTaken: Date?,
    val latitude: Double?,
    val longitude: Double?,
    val countryCode: String?,
    val countryName: String?
)

/**
 * Suggested trip from photo metadata
 */
data class PhotoTripSuggestion(
    val date: Date,
    val countryCode: String,
    val countryName: String,
    val photoCount: Int,
    val isSchengen: Boolean
)

object PhotoPickerService {
    private const val TAG = "PhotoPickerService"

    // StateFlows for reactive UI updates
    private val _selectedPhotos = MutableStateFlow<List<Uri>>(emptyList())
    val selectedPhotos: StateFlow<List<Uri>> = _selectedPhotos.asStateFlow()

    private val _extractedMetadata = MutableStateFlow<List<PhotoMetadata>>(emptyList())
    val extractedMetadata: StateFlow<List<PhotoMetadata>> = _extractedMetadata.asStateFlow()

    private val _suggestedTrips = MutableStateFlow<List<PhotoTripSuggestion>>(emptyList())
    val suggestedTrips: StateFlow<List<PhotoTripSuggestion>> = _suggestedTrips.asStateFlow()

    private val _isProcessing = MutableStateFlow(false)
    val isProcessing: StateFlow<Boolean> = _isProcessing.asStateFlow()

    private val _awaitingConfirmation = MutableStateFlow(false)
    val awaitingConfirmation: StateFlow<Boolean> = _awaitingConfirmation.asStateFlow()

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()

    /**
     * Handle photos selected from the photo picker
     * This does NOT yet read EXIF data - that requires confirmation
     */
    fun handleSelection(uris: List<Uri>) {
        _selectedPhotos.value = uris
        _awaitingConfirmation.value = true
        _extractedMetadata.value = emptyList()
        _suggestedTrips.value = emptyList()

        Log.d(TAG, "Photos selected: ${uris.size}")
        AnalyticsManager.track(AnalyticsEvent.PHOTO_IMPORT_STARTED, mapOf(
            "import_count" to uris.size.toString()
        ))
    }

    /**
     * User confirmed EXIF extraction - proceed with reading metadata
     */
    suspend fun confirmMetadataExtraction(context: Context) {
        _awaitingConfirmation.value = false

        if (_selectedPhotos.value.isEmpty()) {
            return
        }

        _isProcessing.value = true
        _error.value = null

        PrivacySettings.markPhotoExifConfirmed()

        withContext(Dispatchers.IO) {
            try {
                val metadata = _selectedPhotos.value.mapNotNull { uri ->
                    extractMetadata(context, uri)
                }

                _extractedMetadata.value = metadata

                // Group by country and date to suggest trips
                val suggestions = generateTripSuggestions(metadata)
                _suggestedTrips.value = suggestions

                Log.d(TAG, "Extracted metadata from ${metadata.size} photos, ${suggestions.size} trip suggestions")

                AnalyticsManager.track(AnalyticsEvent.PHOTO_IMPORT_COMPLETED, mapOf(
                    "import_count" to metadata.size.toString()
                ))

            } catch (e: Exception) {
                Log.e(TAG, "Metadata extraction failed", e)
                _error.value = "Failed to extract photo data: ${e.message}"
                AnalyticsManager.trackError("photo_import_failed")
            } finally {
                _isProcessing.value = false
            }
        }
    }

    /**
     * User declined EXIF extraction
     */
    fun declineMetadataExtraction() {
        _awaitingConfirmation.value = false
        _selectedPhotos.value = emptyList()
        Log.d(TAG, "User declined EXIF extraction")
    }

    /**
     * Clear all photo import data
     */
    fun clear() {
        _selectedPhotos.value = emptyList()
        _extractedMetadata.value = emptyList()
        _suggestedTrips.value = emptyList()
        _awaitingConfirmation.value = false
        _error.value = null
    }

    // MARK: - Private Methods

    /**
     * Extract EXIF metadata from a single photo
     */
    private fun extractMetadata(context: Context, uri: Uri): PhotoMetadata? {
        var inputStream: InputStream? = null

        try {
            inputStream = context.contentResolver.openInputStream(uri)
            if (inputStream == null) {
                Log.w(TAG, "Could not open input stream for $uri")
                return null
            }

            val exif = ExifInterface(inputStream)

            // Get date taken
            val dateString = exif.getAttribute(ExifInterface.TAG_DATETIME_ORIGINAL)
                ?: exif.getAttribute(ExifInterface.TAG_DATETIME)
            val dateTaken = dateString?.let { parseExifDate(it) }

            // Get GPS coordinates
            val latLong = FloatArray(2)
            val hasGps = exif.getLatLong(latLong)

            val latitude = if (hasGps) latLong[0].toDouble() else null
            val longitude = if (hasGps) latLong[1].toDouble() else null

            // Reverse geocode to get country
            var countryCode: String? = null
            var countryName: String? = null

            if (latitude != null && longitude != null) {
                // Use reverse geocoding (simplified - in production, use Geocoder)
                val country = reverseGeocodeToCountry(context, latitude, longitude)
                countryCode = country?.first
                countryName = country?.second
            }

            return PhotoMetadata(
                uri = uri,
                dateTaken = dateTaken,
                latitude = latitude,
                longitude = longitude,
                countryCode = countryCode,
                countryName = countryName
            )

        } catch (e: Exception) {
            Log.e(TAG, "Failed to extract metadata from $uri", e)
            return null
        } finally {
            inputStream?.close()
        }
    }

    /**
     * Parse EXIF date string
     */
    private fun parseExifDate(dateString: String): Date? {
        return try {
            val format = SimpleDateFormat("yyyy:MM:dd HH:mm:ss", Locale.US)
            format.parse(dateString)
        } catch (e: Exception) {
            Log.w(TAG, "Failed to parse EXIF date: $dateString")
            null
        }
    }

    /**
     * Reverse geocode coordinates to country
     * In production, this would use Android Geocoder
     */
    private fun reverseGeocodeToCountry(
        context: Context,
        latitude: Double,
        longitude: Double
    ): Pair<String, String>? {
        try {
            val geocoder = android.location.Geocoder(context, Locale.getDefault())

            @Suppress("DEPRECATION")
            val addresses = geocoder.getFromLocation(latitude, longitude, 1)

            val address = addresses?.firstOrNull()
            if (address != null && address.countryCode != null) {
                return Pair(address.countryCode, address.countryName ?: address.countryCode)
            }
        } catch (e: Exception) {
            Log.e(TAG, "Reverse geocoding failed for $latitude, $longitude", e)
        }
        return null
    }

    /**
     * Generate trip suggestions from extracted metadata
     */
    private fun generateTripSuggestions(metadata: List<PhotoMetadata>): List<PhotoTripSuggestion> {
        // Filter photos with country data
        val photosWithLocation = metadata.filter { it.countryCode != null && it.dateTaken != null }

        // Group by country and date
        val grouped = photosWithLocation.groupBy { photo ->
            val dateFormat = SimpleDateFormat("yyyy-MM-dd", Locale.US)
            "${photo.countryCode}-${dateFormat.format(photo.dateTaken!!)}"
        }

        return grouped.map { (key, photos) ->
            val countryCode = photos.first().countryCode!!
            val countryName = photos.first().countryName ?: countryCode
            val date = photos.first().dateTaken!!

            PhotoTripSuggestion(
                date = date,
                countryCode = countryCode,
                countryName = countryName,
                photoCount = photos.size,
                isSchengen = TravelStatusCountries.isSchengen(countryCode)
            )
        }.sortedByDescending { it.date }
    }
}
