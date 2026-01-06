/**
 * ComplianceWidget.kt
 *
 * Home screen widget for MyTravelStatus Android app using Jetpack Glance.
 * Displays compliance status for tracked jurisdictions.
 *
 * Supports multiple widget sizes:
 * - Small (2x2): Single jurisdiction with progress
 * - Medium (4x2): Primary jurisdiction with detailed stats
 * - Large (4x4): Multi-jurisdiction overview grid
 *
 * @package MyTravelStatus
 * @since   1.8.3
 */

package com.mytravelstatus.app.widget

import android.content.Context
import android.content.Intent
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.glance.*
import androidx.glance.action.ActionParameters
import androidx.glance.action.clickable
import androidx.glance.appwidget.*
import androidx.glance.appwidget.action.ActionCallback
import androidx.glance.appwidget.action.actionRunCallback
import androidx.glance.appwidget.action.actionStartActivity
import androidx.glance.appwidget.state.updateAppWidgetState
import androidx.glance.layout.*
import androidx.glance.text.FontWeight
import androidx.glance.text.Text
import androidx.glance.text.TextStyle
import androidx.glance.unit.ColorProvider
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import com.mytravelstatus.app.MainActivity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.util.*

// MARK: - Data Models

data class WidgetJurisdiction(
    val code: String,
    val name: String,
    val flagEmoji: String?,
    val daysUsed: Int,
    val daysAllowed: Int,
    val daysRemaining: Int,
    val status: String,
    val category: String
) {
    val percentage: Float
        get() = if (daysAllowed > 0) (daysUsed.toFloat() / daysAllowed).coerceIn(0f, 1f) else 0f

    val shortName: String
        get() = when (code) {
            "schengen" -> "Schengen"
            "uk_srt" -> "UK SRT"
            "us_spt" -> "US SPT"
            "ie_tax" -> "Ireland"
            "fr_tax" -> "France"
            else -> name.take(10)
        }

    val statusColor: Color
        get() = when (status) {
            "ok" -> Color(0xFF22C55E)       // Green
            "warning" -> Color(0xFFEAB308)   // Yellow
            "critical" -> Color(0xFFF97316)  // Orange
            "exceeded" -> Color(0xFFEF4444)  // Red
            else -> Color(0xFF22C55E)
        }
}

data class WidgetData(
    val timestamp: Long,
    val jurisdictions: List<WidgetJurisdiction>
)

// MARK: - Widget State Keys

object WidgetStateKeys {
    val WIDGET_DATA = stringPreferencesKey("widget_data")
}

// MARK: - Widget Receiver

class ComplianceWidgetReceiver : GlanceAppWidgetReceiver() {
    override val glanceAppWidget: GlanceAppWidget = ComplianceWidget()
}

// MARK: - Main Widget

class ComplianceWidget : GlanceAppWidget() {

    override val sizeMode = SizeMode.Responsive(
        setOf(
            DpSize(100.dp, 100.dp),  // Small
            DpSize(250.dp, 100.dp),  // Medium
            DpSize(250.dp, 250.dp)   // Large
        )
    )

    override suspend fun provideGlance(context: Context, id: GlanceId) {
        provideContent {
            val prefs = currentState<Preferences>()
            val dataJson = prefs[WidgetStateKeys.WIDGET_DATA]
            val widgetData = parseWidgetData(dataJson)

            GlanceTheme {
                WidgetContent(widgetData)
            }
        }
    }

    private fun parseWidgetData(json: String?): WidgetData? {
        if (json.isNullOrEmpty()) return null
        return try {
            Gson().fromJson(json, WidgetData::class.java)
        } catch (e: Exception) {
            null
        }
    }
}

// MARK: - Widget Content

@Composable
private fun WidgetContent(data: WidgetData?) {
    val size = LocalSize.current

    Box(
        modifier = GlanceModifier
            .fillMaxSize()
            .background(GlanceTheme.colors.surface)
            .clickable(actionStartActivity<MainActivity>())
    ) {
        when {
            data == null || data.jurisdictions.isEmpty() -> {
                EmptyState()
            }
            size.width < 200.dp -> {
                SmallWidgetContent(data.jurisdictions.firstOrNull())
            }
            size.height < 200.dp -> {
                MediumWidgetContent(data.jurisdictions)
            }
            else -> {
                LargeWidgetContent(data.jurisdictions)
            }
        }
    }
}

// MARK: - Empty State

@Composable
private fun EmptyState() {
    Column(
        modifier = GlanceModifier
            .fillMaxSize()
            .padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(
            text = "✈️",
            style = TextStyle(fontSize = 32.sp)
        )
        Spacer(modifier = GlanceModifier.height(8.dp))
        Text(
            text = "No Data",
            style = TextStyle(
                fontSize = 14.sp,
                fontWeight = FontWeight.Medium,
                color = ColorProvider(Color.Gray)
            )
        )
        Text(
            text = "Open app to sync",
            style = TextStyle(
                fontSize = 12.sp,
                color = ColorProvider(Color.Gray)
            )
        )
    }
}

// MARK: - Small Widget (2x2)

@Composable
private fun SmallWidgetContent(jurisdiction: WidgetJurisdiction?) {
    if (jurisdiction == null) {
        EmptyState()
        return
    }

    Column(
        modifier = GlanceModifier
            .fillMaxSize()
            .padding(12.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalAlignment = Alignment.CenterVertically
    ) {
        // Header with flag and name
        Row(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalAlignment = Alignment.CenterVertically
        ) {
            jurisdiction.flagEmoji?.let {
                Text(text = it, style = TextStyle(fontSize = 16.sp))
                Spacer(modifier = GlanceModifier.width(4.dp))
            }
            Text(
                text = jurisdiction.shortName,
                style = TextStyle(
                    fontSize = 12.sp,
                    fontWeight = FontWeight.Medium
                ),
                maxLines = 1
            )
        }

        Spacer(modifier = GlanceModifier.height(8.dp))

        // Days count
        Text(
            text = "${jurisdiction.daysUsed}",
            style = TextStyle(
                fontSize = 28.sp,
                fontWeight = FontWeight.Bold,
                color = ColorProvider(jurisdiction.statusColor)
            )
        )
        Text(
            text = "/ ${jurisdiction.daysAllowed}",
            style = TextStyle(
                fontSize = 12.sp,
                color = ColorProvider(Color.Gray)
            )
        )

        Spacer(modifier = GlanceModifier.height(4.dp))

        // Progress bar
        ProgressBar(
            percentage = jurisdiction.percentage,
            color = jurisdiction.statusColor,
            modifier = GlanceModifier.fillMaxWidth()
        )

        Spacer(modifier = GlanceModifier.height(4.dp))

        Text(
            text = "${jurisdiction.daysRemaining} days left",
            style = TextStyle(
                fontSize = 10.sp,
                color = ColorProvider(Color.Gray)
            )
        )
    }
}

// MARK: - Medium Widget (4x2)

@Composable
private fun MediumWidgetContent(jurisdictions: List<WidgetJurisdiction>) {
    val primary = jurisdictions.firstOrNull() ?: return

    Row(
        modifier = GlanceModifier
            .fillMaxSize()
            .padding(12.dp)
    ) {
        // Primary jurisdiction
        Column(
            modifier = GlanceModifier.defaultWeight(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                primary.flagEmoji?.let {
                    Text(text = it, style = TextStyle(fontSize = 20.sp))
                    Spacer(modifier = GlanceModifier.width(4.dp))
                }
                Text(
                    text = primary.shortName,
                    style = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.Medium)
                )
            }

            Spacer(modifier = GlanceModifier.height(8.dp))

            Text(
                text = "${primary.daysUsed}/${primary.daysAllowed}",
                style = TextStyle(
                    fontSize = 24.sp,
                    fontWeight = FontWeight.Bold,
                    color = ColorProvider(primary.statusColor)
                )
            )

            Spacer(modifier = GlanceModifier.height(4.dp))

            ProgressBar(
                percentage = primary.percentage,
                color = primary.statusColor,
                modifier = GlanceModifier.fillMaxWidth().padding(horizontal = 8.dp)
            )

            Spacer(modifier = GlanceModifier.height(4.dp))

            Text(
                text = "${primary.daysRemaining} days left",
                style = TextStyle(fontSize = 11.sp, color = ColorProvider(Color.Gray))
            )
        }

        // Divider
        Box(
            modifier = GlanceModifier
                .width(1.dp)
                .fillMaxHeight()
                .padding(vertical = 8.dp)
                .background(ColorProvider(Color.Gray.copy(alpha = 0.3f)))
        ) {}

        // Other jurisdictions
        Column(
            modifier = GlanceModifier
                .defaultWeight()
                .padding(start = 12.dp)
        ) {
            Text(
                text = "Other Rules",
                style = TextStyle(
                    fontSize = 11.sp,
                    color = ColorProvider(Color.Gray)
                )
            )

            Spacer(modifier = GlanceModifier.height(4.dp))

            jurisdictions.drop(1).take(3).forEach { j ->
                Row(
                    modifier = GlanceModifier.fillMaxWidth().padding(vertical = 2.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    j.flagEmoji?.let {
                        Text(text = it, style = TextStyle(fontSize = 10.sp))
                        Spacer(modifier = GlanceModifier.width(4.dp))
                    }
                    Text(
                        text = j.shortName,
                        style = TextStyle(fontSize = 11.sp),
                        maxLines = 1,
                        modifier = GlanceModifier.defaultWeight()
                    )
                    Text(
                        text = "${j.daysUsed}/${j.daysAllowed}",
                        style = TextStyle(
                            fontSize = 10.sp,
                            color = ColorProvider(j.statusColor)
                        )
                    )
                }
            }

            if (jurisdictions.size <= 1) {
                Text(
                    text = "Add more in app",
                    style = TextStyle(fontSize = 10.sp, color = ColorProvider(Color.Gray))
                )
            }
        }
    }
}

// MARK: - Large Widget (4x4)

@Composable
private fun LargeWidgetContent(jurisdictions: List<WidgetJurisdiction>) {
    Column(
        modifier = GlanceModifier
            .fillMaxSize()
            .padding(12.dp)
    ) {
        // Header
        Row(
            modifier = GlanceModifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "✈️",
                style = TextStyle(fontSize = 16.sp)
            )
            Spacer(modifier = GlanceModifier.width(8.dp))
            Text(
                text = "Travel Compliance",
                style = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.Medium),
                modifier = GlanceModifier.defaultWeight()
            )
            Text(
                text = "MyTravelStatus",
                style = TextStyle(fontSize = 10.sp, color = ColorProvider(Color.Gray))
            )
        }

        Spacer(modifier = GlanceModifier.height(12.dp))

        // Jurisdiction cards in 2x2 grid
        val chunkedJurisdictions = jurisdictions.take(4).chunked(2)

        chunkedJurisdictions.forEach { row ->
            Row(modifier = GlanceModifier.fillMaxWidth()) {
                row.forEach { jurisdiction ->
                    JurisdictionCard(
                        jurisdiction = jurisdiction,
                        modifier = GlanceModifier.defaultWeight().padding(4.dp)
                    )
                }
                // Fill empty space if odd number
                if (row.size == 1) {
                    Box(modifier = GlanceModifier.defaultWeight()) {}
                }
            }
        }

        if (jurisdictions.size > 4) {
            Spacer(modifier = GlanceModifier.height(8.dp))
            Text(
                text = "+${jurisdictions.size - 4} more in app",
                style = TextStyle(fontSize = 10.sp, color = ColorProvider(Color.Gray)),
                modifier = GlanceModifier.fillMaxWidth()
            )
        }
    }
}

// MARK: - Jurisdiction Card

@Composable
private fun JurisdictionCard(
    jurisdiction: WidgetJurisdiction,
    modifier: GlanceModifier = GlanceModifier
) {
    Column(
        modifier = modifier
            .background(ColorProvider(Color.Gray.copy(alpha = 0.1f)))
            .cornerRadius(8.dp)
            .padding(10.dp)
    ) {
        // Header
        Row(
            modifier = GlanceModifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            jurisdiction.flagEmoji?.let {
                Text(text = it, style = TextStyle(fontSize = 12.sp))
                Spacer(modifier = GlanceModifier.width(4.dp))
            }
            Text(
                text = jurisdiction.shortName,
                style = TextStyle(fontSize = 11.sp, fontWeight = FontWeight.Medium),
                maxLines = 1,
                modifier = GlanceModifier.defaultWeight()
            )
            StatusIndicator(jurisdiction.status)
        }

        Spacer(modifier = GlanceModifier.height(6.dp))

        // Progress bar
        ProgressBar(
            percentage = jurisdiction.percentage,
            color = jurisdiction.statusColor,
            modifier = GlanceModifier.fillMaxWidth()
        )

        Spacer(modifier = GlanceModifier.height(4.dp))

        // Stats
        Row(modifier = GlanceModifier.fillMaxWidth()) {
            Text(
                text = "${jurisdiction.daysUsed}/${jurisdiction.daysAllowed}",
                style = TextStyle(fontSize = 10.sp, fontWeight = FontWeight.Medium)
            )
            Spacer(modifier = GlanceModifier.defaultWeight())
            Text(
                text = "${jurisdiction.daysRemaining} left",
                style = TextStyle(fontSize = 10.sp, color = ColorProvider(Color.Gray))
            )
        }
    }
}

// MARK: - Status Indicator

@Composable
private fun StatusIndicator(status: String) {
    val (icon, color) = when (status) {
        "ok" -> "✓" to Color(0xFF22C55E)
        "warning" -> "⚠" to Color(0xFFEAB308)
        "critical" -> "⚠" to Color(0xFFF97316)
        "exceeded" -> "✕" to Color(0xFFEF4444)
        else -> "✓" to Color(0xFF22C55E)
    }

    Text(
        text = icon,
        style = TextStyle(fontSize = 10.sp, color = ColorProvider(color))
    )
}

// MARK: - Progress Bar

@Composable
private fun ProgressBar(
    percentage: Float,
    color: Color,
    modifier: GlanceModifier = GlanceModifier
) {
    Box(
        modifier = modifier
            .height(6.dp)
            .background(ColorProvider(Color.Gray.copy(alpha = 0.2f)))
            .cornerRadius(3.dp)
    ) {
        Box(
            modifier = GlanceModifier
                .fillMaxHeight()
                .fillMaxWidth(percentage)
                .background(ColorProvider(color))
                .cornerRadius(3.dp)
        ) {}
    }
}

// MARK: - Refresh Action

class RefreshWidgetAction : ActionCallback {
    override suspend fun onAction(
        context: Context,
        glanceId: GlanceId,
        parameters: ActionParameters
    ) {
        // Trigger a widget update
        ComplianceWidget().update(context, glanceId)
    }
}
