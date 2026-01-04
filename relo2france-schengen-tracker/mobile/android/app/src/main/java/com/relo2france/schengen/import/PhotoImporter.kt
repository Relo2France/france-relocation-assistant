/**
 * PhotoImporter.kt
 *
 * Imports trip data from photo library GPS metadata.
 * Scans photos, groups by location/date, and creates trips.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.import

import android.content.ContentResolver
import android.content.Context
import android.graphics.Bitmap
import android.location.Geocoder
import android.net.Uri
import android.provider.MediaStore
import android.util.Log
import android.util.Size
import com.relo2france.schengen.data.Trip
import com.relo2france.schengen.data.TripCategory
import com.relo2france.schengen.util.SchengenCountries
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.time.temporal.ChronoUnit
import java.util.*

/**
 * Detected trip from photo GPS data
 */
data class DetectedPhotoTrip(
    val id: String = UUID.randomUUID().toString(),
    val country: String,
    val countryCode: String,
    val startDate: LocalDate,
    val endDate: LocalDate,
    val photoCount: Int,
    val samplePhotoUri: Uri?,
    val isSchengen: Boolean
) {
    val durationDays: Int
        get() = ChronoUnit.DAYS.between(startDate, endDate).toInt() + 1

    fun toTrip(): Trip {
        val formatter = DateTimeFormatter.ISO_LOCAL_DATE
        return Trip(
            startDate = startDate.format(formatter),
            endDate = endDate.format(formatter),
            country = countryCode,
            category = if (isSchengen) TripCategory.SCHENGEN else TripCategory.NON_SCHENGEN,
            notes = "Imported from $photoCount photos"
        )
    }
}

/**
 * Import progress phases
 */
sealed class ImportPhase {
    object Scanning : ImportPhase()
    object Geocoding : ImportPhase()
    object Grouping : ImportPhase()
    object Complete : ImportPhase()
}

/**
 * Import progress data
 */
data class PhotoImportProgress(
    val current: Int,
    val total: Int,
    val phase: ImportPhase
) {
    val percentage: Float
        get() = if (total > 0) current.toFloat() / total else 0f
}

/**
 * Photo importer service
 */
class PhotoImporter(private val context: Context) {

    companion object {
        private const val TAG = "PhotoImporter"

        @Volatile
        private var INSTANCE: PhotoImporter? = null

        fun getInstance(context: Context): PhotoImporter {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: PhotoImporter(context.applicationContext).also { INSTANCE = it }
            }
        }
    }

    private val geocoder = Geocoder(context, Locale.getDefault())
    private val geocodeCache = mutableMapOf<String, Pair<String, String>>() // Cache country results

    /**
     * Scan photos for GPS metadata within date range
     */
    suspend fun scanPhotos(
        startDate: LocalDate,
        endDate: LocalDate,
        onProgress: (PhotoImportProgress) -> Unit
    ): List<DetectedPhotoTrip> = withContext(Dispatchers.IO) {

        val contentResolver = context.contentResolver

        // Convert dates to milliseconds for query
        val startMillis = startDate.atStartOfDay(ZoneId.systemDefault()).toInstant().toEpochMilli()
        val endMillis = endDate.plusDays(1).atStartOfDay(ZoneId.systemDefault()).toInstant().toEpochMilli()

        // Query photos with GPS in date range
        val projection = arrayOf(
            MediaStore.Images.Media._ID,
            MediaStore.Images.Media.DATE_TAKEN,
            MediaStore.Images.Media.LATITUDE,
            MediaStore.Images.Media.LONGITUDE
        )

        val selection = "${MediaStore.Images.Media.DATE_TAKEN} >= ? AND ${MediaStore.Images.Media.DATE_TAKEN} < ?"
        val selectionArgs = arrayOf(startMillis.toString(), endMillis.toString())
        val sortOrder = "${MediaStore.Images.Media.DATE_TAKEN} ASC"

        val cursor = contentResolver.query(
            MediaStore.Images.Media.EXTERNAL_CONTENT_URI,
            projection,
            selection,
            selectionArgs,
            sortOrder
        )

        val locationsByDate = mutableMapOf<LocalDate, MutableList<PhotoLocation>>()

        cursor?.use {
            val total = it.count
            var scanned = 0

            val idColumn = it.getColumnIndexOrThrow(MediaStore.Images.Media._ID)
            val dateColumn = it.getColumnIndexOrThrow(MediaStore.Images.Media.DATE_TAKEN)
            val latColumn = it.getColumnIndexOrThrow(MediaStore.Images.Media.LATITUDE)
            val lngColumn = it.getColumnIndexOrThrow(MediaStore.Images.Media.LONGITUDE)

            onProgress(PhotoImportProgress(0, total, ImportPhase.Scanning))

            while (it.moveToNext()) {
                val id = it.getLong(idColumn)
                val dateTaken = it.getLong(dateColumn)
                val lat = it.getDouble(latColumn)
                val lng = it.getDouble(lngColumn)

                // Skip photos without GPS
                if (lat != 0.0 && lng != 0.0) {
                    val date = Instant.ofEpochMilli(dateTaken)
                        .atZone(ZoneId.systemDefault())
                        .toLocalDate()

                    val uri = Uri.withAppendedPath(
                        MediaStore.Images.Media.EXTERNAL_CONTENT_URI,
                        id.toString()
                    )

                    locationsByDate.getOrPut(date) { mutableListOf() }.add(
                        PhotoLocation(uri, lat, lng)
                    )
                }

                scanned++
                if (scanned % 50 == 0) {
                    onProgress(PhotoImportProgress(scanned, total, ImportPhase.Scanning))
                }
            }

            onProgress(PhotoImportProgress(total, total, ImportPhase.Scanning))
        }

        if (locationsByDate.isEmpty()) {
            return@withContext emptyList()
        }

        // Geocode locations
        val countriesByDate = mutableMapOf<LocalDate, CountryData>()
        var geocoded = 0
        val totalDays = locationsByDate.size

        onProgress(PhotoImportProgress(0, totalDays, ImportPhase.Geocoding))

        for ((date, photos) in locationsByDate.entries.sortedBy { it.key }) {
            val primaryPhoto = photos.first()

            try {
                val country = geocodeLocation(primaryPhoto.lat, primaryPhoto.lng)
                if (country != null) {
                    countriesByDate[date] = CountryData(
                        code = country.first,
                        name = country.second,
                        photos = photos
                    )
                }
            } catch (e: Exception) {
                Log.e(TAG, "Geocoding failed for $date: ${e.message}")
            }

            geocoded++
            onProgress(PhotoImportProgress(geocoded, totalDays, ImportPhase.Geocoding))

            // Rate limit geocoding
            kotlinx.coroutines.delay(100)
        }

        // Group into trips
        onProgress(PhotoImportProgress(0, countriesByDate.size, ImportPhase.Grouping))

        val trips = groupIntoTrips(countriesByDate)

        onProgress(PhotoImportProgress(trips.size, trips.size, ImportPhase.Complete))

        trips
    }

    /**
     * Load thumbnail for a photo
     */
    suspend fun loadThumbnail(uri: Uri): Bitmap? = withContext(Dispatchers.IO) {
        try {
            context.contentResolver.loadThumbnail(uri, Size(100, 100), null)
        } catch (e: Exception) {
            Log.e(TAG, "Failed to load thumbnail: ${e.message}")
            null
        }
    }

    private suspend fun geocodeLocation(lat: Double, lng: Double): Pair<String, String>? {
        // Check cache
        val cacheKey = "${(lat * 100).toInt()},${(lng * 100).toInt()}"
        geocodeCache[cacheKey]?.let { return it }

        return withContext(Dispatchers.IO) {
            try {
                @Suppress("DEPRECATION")
                val addresses = geocoder.getFromLocation(lat, lng, 1)
                val address = addresses?.firstOrNull()

                if (address?.countryCode != null && address.countryName != null) {
                    val result = Pair(address.countryCode, address.countryName)
                    geocodeCache[cacheKey] = result
                    result
                } else {
                    null
                }
            } catch (e: Exception) {
                Log.e(TAG, "Geocoding error: ${e.message}")
                null
            }
        }
    }

    private fun groupIntoTrips(countriesByDate: Map<LocalDate, CountryData>): List<DetectedPhotoTrip> {
        val trips = mutableListOf<DetectedPhotoTrip>()
        var currentTrip: TripBuilder? = null

        val sortedDates = countriesByDate.keys.sorted()

        for (date in sortedDates) {
            val dayData = countriesByDate[date] ?: continue

            currentTrip?.let { current ->
                // Check if same country and consecutive day (2 day gap allowed)
                val dayDiff = ChronoUnit.DAYS.between(current.endDate, date).toInt()

                if (current.code == dayData.code && dayDiff <= 2) {
                    // Extend current trip
                    current.endDate = date
                    current.photoCount += dayData.photos.size
                } else {
                    // Save current trip and start new
                    trips.add(current.build())
                    currentTrip = TripBuilder(
                        code = dayData.code,
                        name = dayData.name,
                        startDate = date,
                        endDate = date,
                        photoCount = dayData.photos.size,
                        samplePhotoUri = dayData.photos.firstOrNull()?.uri
                    )
                }
            } ?: run {
                // Start first trip
                currentTrip = TripBuilder(
                    code = dayData.code,
                    name = dayData.name,
                    startDate = date,
                    endDate = date,
                    photoCount = dayData.photos.size,
                    samplePhotoUri = dayData.photos.firstOrNull()?.uri
                )
            }
        }

        // Don't forget the last trip
        currentTrip?.let { trips.add(it.build()) }

        return trips
    }

    private data class PhotoLocation(
        val uri: Uri,
        val lat: Double,
        val lng: Double
    )

    private data class CountryData(
        val code: String,
        val name: String,
        val photos: List<PhotoLocation>
    )

    private class TripBuilder(
        val code: String,
        val name: String,
        val startDate: LocalDate,
        var endDate: LocalDate,
        var photoCount: Int,
        val samplePhotoUri: Uri?
    ) {
        fun build() = DetectedPhotoTrip(
            country = name,
            countryCode = code,
            startDate = startDate,
            endDate = endDate,
            photoCount = photoCount,
            samplePhotoUri = samplePhotoUri,
            isSchengen = SchengenCountries.isSchengen(code)
        )
    }
}
