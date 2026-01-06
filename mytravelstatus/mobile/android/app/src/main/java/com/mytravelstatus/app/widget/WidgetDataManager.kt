/**
 * WidgetDataManager.kt
 *
 * Manages data synchronization between the main app and home screen widgets.
 * Stores compliance data in DataStore for widget access.
 *
 * @package MyTravelStatus
 * @since   1.8.3
 */

package com.mytravelstatus.app.widget

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.glance.appwidget.GlanceAppWidgetManager
import androidx.glance.appwidget.state.updateAppWidgetState
import com.google.gson.Gson
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

/**
 * Manages widget data storage and refresh
 */
class WidgetDataManager private constructor(private val context: Context) {

    private val gson = Gson()
    private val scope = CoroutineScope(Dispatchers.IO)

    companion object {
        @Volatile
        private var instance: WidgetDataManager? = null

        fun getInstance(context: Context): WidgetDataManager {
            return instance ?: synchronized(this) {
                instance ?: WidgetDataManager(context.applicationContext).also { instance = it }
            }
        }
    }

    /**
     * Updates all compliance widgets with new jurisdiction data
     *
     * @param jurisdictions List of jurisdiction summaries from API response
     */
    fun updateWidgetData(jurisdictions: List<JurisdictionApiSummary>) {
        scope.launch {
            val widgetJurisdictions = jurisdictions.map { summary ->
                WidgetJurisdiction(
                    code = summary.jurisdictionCode,
                    name = summary.jurisdictionName,
                    flagEmoji = summary.flagEmoji,
                    daysUsed = summary.daysUsed,
                    daysAllowed = summary.daysAllowed,
                    daysRemaining = summary.daysRemaining,
                    status = summary.status,
                    category = summary.category ?: "visa"
                )
            }

            val widgetData = WidgetData(
                timestamp = System.currentTimeMillis(),
                jurisdictions = widgetJurisdictions
            )

            val dataJson = gson.toJson(widgetData)

            // Update all widget instances
            val manager = GlanceAppWidgetManager(context)
            val glanceIds = manager.getGlanceIds(ComplianceWidget::class.java)

            glanceIds.forEach { glanceId ->
                updateAppWidgetState(context, glanceId) { prefs ->
                    prefs[WidgetStateKeys.WIDGET_DATA] = dataJson
                }
                ComplianceWidget().update(context, glanceId)
            }
        }
    }

    /**
     * Clears widget data (e.g., on logout)
     */
    fun clearWidgetData() {
        scope.launch {
            val manager = GlanceAppWidgetManager(context)
            val glanceIds = manager.getGlanceIds(ComplianceWidget::class.java)

            glanceIds.forEach { glanceId ->
                updateAppWidgetState(context, glanceId) { prefs ->
                    prefs.remove(WidgetStateKeys.WIDGET_DATA)
                }
                ComplianceWidget().update(context, glanceId)
            }
        }
    }

    /**
     * Forces a refresh of all widgets
     */
    fun refreshAllWidgets() {
        scope.launch {
            val manager = GlanceAppWidgetManager(context)
            val glanceIds = manager.getGlanceIds(ComplianceWidget::class.java)

            glanceIds.forEach { glanceId ->
                ComplianceWidget().update(context, glanceId)
            }
        }
    }
}

/**
 * API response model for jurisdiction summary
 */
data class JurisdictionApiSummary(
    val jurisdictionCode: String,
    val jurisdictionName: String,
    val category: String?,
    val flagEmoji: String?,
    val daysUsed: Int,
    val daysAllowed: Int,
    val daysRemaining: Int,
    val percentage: Double,
    val status: String,
    val windowStart: String?,
    val windowEnd: String?,
    val referenceDate: String?,
    val countingMethod: String?,
    val nextExpiringDate: String?,
    val nextExpiringDays: Int?,
    val tripCount: Int?
)
