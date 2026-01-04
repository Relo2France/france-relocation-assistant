/**
 * CalendarImporter.kt
 *
 * Imports trip data from calendar events.
 * Scans for travel-related events and extracts location data.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.import

import android.Manifest
import android.content.ContentResolver
import android.content.Context
import android.content.pm.PackageManager
import android.location.Geocoder
import android.provider.CalendarContract
import android.util.Log
import androidx.core.content.ContextCompat
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
 * Detected trip from calendar event
 */
data class DetectedCalendarTrip(
    val id: String = UUID.randomUUID().toString(),
    val country: String,
    val countryCode: String,
    val startDate: LocalDate,
    val endDate: LocalDate,
    val eventTitle: String,
    val calendarName: String,
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
            notes = "Imported from calendar: $eventTitle"
        )
    }
}

/**
 * Calendar importer service
 */
class CalendarImporter(private val context: Context) {

    companion object {
        private const val TAG = "CalendarImporter"

        @Volatile
        private var INSTANCE: CalendarImporter? = null

        fun getInstance(context: Context): CalendarImporter {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: CalendarImporter(context.applicationContext).also { INSTANCE = it }
            }
        }

        /** Travel-related keywords to search for */
        private val travelKeywords = listOf(
            // Direct travel terms
            "flight", "fly", "plane", "airport",
            "train", "eurostar", "tgv", "rail",
            "hotel", "airbnb", "booking", "accommodation",
            "vacation", "holiday", "trip", "travel",
            "departure", "arrival",

            // Countries (Schengen)
            "austria", "belgium", "bulgaria", "croatia", "czech",
            "denmark", "estonia", "finland", "france", "germany",
            "greece", "hungary", "iceland", "italy", "latvia",
            "liechtenstein", "lithuania", "luxembourg", "malta",
            "netherlands", "norway", "poland", "portugal", "romania",
            "slovakia", "slovenia", "spain", "sweden", "switzerland",

            // Major cities
            "paris", "rome", "barcelona", "amsterdam", "berlin",
            "vienna", "prague", "lisbon", "madrid", "munich",
            "milan", "athens", "brussels", "copenhagen", "dublin",
            "stockholm", "oslo", "helsinki", "zurich", "geneva",

            // Business travel
            "conference", "meeting in", "visit to",
            "business trip", "work travel", "offsite"
        )

        /** City to country mapping */
        private val cityToCountry = mapOf(
            "paris" to ("FR" to "France"),
            "rome" to ("IT" to "Italy"),
            "milan" to ("IT" to "Italy"),
            "barcelona" to ("ES" to "Spain"),
            "madrid" to ("ES" to "Spain"),
            "amsterdam" to ("NL" to "Netherlands"),
            "berlin" to ("DE" to "Germany"),
            "munich" to ("DE" to "Germany"),
            "vienna" to ("AT" to "Austria"),
            "prague" to ("CZ" to "Czech Republic"),
            "lisbon" to ("PT" to "Portugal"),
            "brussels" to ("BE" to "Belgium"),
            "copenhagen" to ("DK" to "Denmark"),
            "stockholm" to ("SE" to "Sweden"),
            "oslo" to ("NO" to "Norway"),
            "helsinki" to ("FI" to "Finland"),
            "zurich" to ("CH" to "Switzerland"),
            "geneva" to ("CH" to "Switzerland"),
            "athens" to ("GR" to "Greece"),
            "budapest" to ("HU" to "Hungary"),
            "warsaw" to ("PL" to "Poland"),
            "dublin" to ("IE" to "Ireland"),
            "london" to ("GB" to "United Kingdom"),
            "edinburgh" to ("GB" to "United Kingdom"),
            "new york" to ("US" to "United States"),
            "los angeles" to ("US" to "United States"),
            "tokyo" to ("JP" to "Japan"),
            "sydney" to ("AU" to "Australia")
        )
    }

    private val geocoder = Geocoder(context, Locale.getDefault())

    /**
     * Check if calendar permission is granted
     */
    fun hasCalendarPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            context,
            Manifest.permission.READ_CALENDAR
        ) == PackageManager.PERMISSION_GRANTED
    }

    /**
     * Scan calendar events for travel
     */
    suspend fun scanEvents(
        startDate: LocalDate,
        endDate: LocalDate,
        onProgress: (current: Int, total: Int) -> Unit
    ): List<DetectedCalendarTrip> = withContext(Dispatchers.IO) {

        if (!hasCalendarPermission()) {
            return@withContext emptyList()
        }

        val contentResolver = context.contentResolver

        // Convert dates to milliseconds
        val startMillis = startDate.atStartOfDay(ZoneId.systemDefault()).toInstant().toEpochMilli()
        val endMillis = endDate.plusDays(1).atStartOfDay(ZoneId.systemDefault()).toInstant().toEpochMilli()

        // Query events
        val projection = arrayOf(
            CalendarContract.Events._ID,
            CalendarContract.Events.TITLE,
            CalendarContract.Events.EVENT_LOCATION,
            CalendarContract.Events.DTSTART,
            CalendarContract.Events.DTEND,
            CalendarContract.Events.CALENDAR_DISPLAY_NAME
        )

        val selection = "${CalendarContract.Events.DTSTART} >= ? AND ${CalendarContract.Events.DTSTART} < ?"
        val selectionArgs = arrayOf(startMillis.toString(), endMillis.toString())
        val sortOrder = "${CalendarContract.Events.DTSTART} ASC"

        val cursor = contentResolver.query(
            CalendarContract.Events.CONTENT_URI,
            projection,
            selection,
            selectionArgs,
            sortOrder
        )

        val detectedTrips = mutableListOf<DetectedCalendarTrip>()

        cursor?.use {
            val total = it.count
            var processed = 0

            val idColumn = it.getColumnIndexOrThrow(CalendarContract.Events._ID)
            val titleColumn = it.getColumnIndexOrThrow(CalendarContract.Events.TITLE)
            val locationColumn = it.getColumnIndexOrThrow(CalendarContract.Events.EVENT_LOCATION)
            val startColumn = it.getColumnIndexOrThrow(CalendarContract.Events.DTSTART)
            val endColumn = it.getColumnIndexOrThrow(CalendarContract.Events.DTEND)
            val calendarColumn = it.getColumnIndexOrThrow(CalendarContract.Events.CALENDAR_DISPLAY_NAME)

            onProgress(0, total)

            while (it.moveToNext()) {
                val title = it.getString(titleColumn) ?: ""
                val location = it.getString(locationColumn) ?: ""
                val eventStart = it.getLong(startColumn)
                val eventEnd = it.getLong(endColumn)
                val calendarName = it.getString(calendarColumn) ?: "Calendar"

                // Check if event might be travel-related
                if (isTravelRelated(title, location)) {
                    val trip = processEvent(title, location, eventStart, eventEnd, calendarName)
                    if (trip != null) {
                        detectedTrips.add(trip)
                    }
                }

                processed++
                if (processed % 10 == 0) {
                    onProgress(processed, total)
                }
            }

            onProgress(total, total)
        }

        // Merge overlapping trips
        mergeOverlappingTrips(detectedTrips)
    }

    private fun isTravelRelated(title: String, location: String): Boolean {
        val searchText = "$title $location".lowercase()
        return travelKeywords.any { keyword -> searchText.contains(keyword) }
    }

    private suspend fun processEvent(
        title: String,
        location: String,
        startMillis: Long,
        endMillis: Long,
        calendarName: String
    ): DetectedCalendarTrip? {

        // Try to extract country from location/title
        var countryCode: String? = null
        var countryName: String? = null

        // First, check if location string contains a country name
        extractCountryFromText(location)?.let { (code, name) ->
            countryCode = code
            countryName = name
        }

        // If not found, try to geocode the location
        if (countryCode == null && location.isNotBlank()) {
            geocodeAddress(location)?.let { (code, name) ->
                countryCode = code
                countryName = name
            }
        }

        // If still not found, check the title
        if (countryCode == null) {
            extractCountryFromText(title)?.let { (code, name) ->
                countryCode = code
                countryName = name
            }
        }

        if (countryCode == null || countryName == null) {
            return null
        }

        val startDate = Instant.ofEpochMilli(startMillis)
            .atZone(ZoneId.systemDefault())
            .toLocalDate()

        val endDate = Instant.ofEpochMilli(endMillis)
            .atZone(ZoneId.systemDefault())
            .toLocalDate()

        return DetectedCalendarTrip(
            country = countryName!!,
            countryCode = countryCode!!,
            startDate = startDate,
            endDate = endDate,
            eventTitle = title,
            calendarName = calendarName,
            isSchengen = SchengenCountries.isSchengen(countryCode!!)
        )
    }

    private fun extractCountryFromText(text: String): Pair<String, String>? {
        val lowercased = text.lowercase()

        // Check for country names
        for ((code, name) in SchengenCountries.names) {
            if (lowercased.contains(name.lowercase())) {
                return code to name
            }
        }

        // Check for cities
        for ((city, country) in cityToCountry) {
            if (lowercased.contains(city)) {
                return country
            }
        }

        return null
    }

    private suspend fun geocodeAddress(address: String): Pair<String, String>? {
        return withContext(Dispatchers.IO) {
            try {
                @Suppress("DEPRECATION")
                val addresses = geocoder.getFromLocationName(address, 1)
                val result = addresses?.firstOrNull()

                if (result?.countryCode != null && result.countryName != null) {
                    result.countryCode to result.countryName
                } else {
                    null
                }
            } catch (e: Exception) {
                Log.e(TAG, "Geocoding error: ${e.message}")
                null
            }
        }
    }

    private fun mergeOverlappingTrips(trips: MutableList<DetectedCalendarTrip>): List<DetectedCalendarTrip> {
        if (trips.isEmpty()) return emptyList()

        val sorted = trips.sortedBy { it.startDate }
        val merged = mutableListOf<DetectedCalendarTrip>()

        var current = sorted[0]

        for (trip in sorted.drop(1)) {
            // If same country and overlapping/adjacent, merge
            if (current.countryCode == trip.countryCode) {
                val daysBetween = ChronoUnit.DAYS.between(current.endDate, trip.startDate).toInt()

                if (daysBetween <= 1) {
                    // Merge
                    current = current.copy(
                        endDate = maxOf(current.endDate, trip.endDate)
                    )
                    continue
                }
            }

            merged.add(current)
            current = trip
        }

        merged.add(current)
        return merged
    }
}
