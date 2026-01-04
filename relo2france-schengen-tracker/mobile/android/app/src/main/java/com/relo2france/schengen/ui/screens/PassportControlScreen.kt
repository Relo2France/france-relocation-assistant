/**
 * PassportControlScreen.kt
 *
 * Border officer optimized display showing Schengen status.
 * Large, clear numbers that can be read at a glance.
 * Works offline with cached data.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.screens

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.relo2france.schengen.data.PassportControlData
import com.relo2france.schengen.data.Trip
import com.relo2france.schengen.ui.theme.StatusGreen
import com.relo2france.schengen.ui.theme.StatusRed
import com.relo2france.schengen.ui.theme.StatusYellow
import com.relo2france.schengen.util.SchengenCountries
import java.time.LocalDate
import java.time.format.DateTimeFormatter
import java.time.temporal.ChronoUnit

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PassportControlScreen(
    passportData: PassportControlData?,
    isLoading: Boolean,
    isOnline: Boolean,
    onRefresh: () -> Unit,
    modifier: Modifier = Modifier
) {
    val dateFormatter = DateTimeFormatter.ofPattern("d MMM yyyy")

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        "Passport Control",
                        fontWeight = FontWeight.Bold
                    )
                },
                actions = {
                    if (!isOnline) {
                        Text(
                            "Offline",
                            color = StatusYellow,
                            modifier = Modifier.padding(end = 8.dp),
                            style = MaterialTheme.typography.labelMedium
                        )
                    }
                    IconButton(onClick = onRefresh, enabled = !isLoading) {
                        if (isLoading) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                        }
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        if (passportData == null && isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else if (passportData == null) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("No data available")
                    Spacer(modifier = Modifier.height(16.dp))
                    Button(onClick = onRefresh) {
                        Text("Retry")
                    }
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                horizontalAlignment = Alignment.CenterHorizontally,
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                // Main status circle
                item {
                    StatusCircle(
                        daysUsed = passportData.daysUsed,
                        daysRemaining = passportData.daysRemaining
                    )
                }

                // Period info
                item {
                    PeriodInfo(
                        periodStart = passportData.periodStart,
                        periodEnd = passportData.periodEnd,
                        dateFormatter = dateFormatter
                    )
                }

                // Current location
                passportData.currentCountry?.let { country ->
                    item {
                        CurrentLocationCard(
                            countryCode = country,
                            currentTrip = passportData.currentTrip
                        )
                    }
                }

                // Recent trips header
                if (passportData.recentTrips.isNotEmpty()) {
                    item {
                        Text(
                            "Recent Schengen Trips",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.SemiBold,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(top = 8.dp)
                        )
                    }

                    // Recent trips
                    items(passportData.recentTrips) { trip ->
                        TripCard(trip = trip, dateFormatter = dateFormatter)
                    }
                }

                // Last updated
                item {
                    Text(
                        "Last updated: ${formatLastUpdated(passportData.lastUpdated)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                }
            }
        }
    }
}

@Composable
private fun StatusCircle(
    daysUsed: Int,
    daysRemaining: Int,
    modifier: Modifier = Modifier
) {
    val progress = (daysUsed / 90f).coerceIn(0f, 1f)
    val animatedProgress by animateFloatAsState(targetValue = progress, label = "progress")

    val statusColor by animateColorAsState(
        targetValue = when {
            daysUsed <= 80 -> StatusGreen
            daysUsed <= 85 -> StatusYellow
            else -> StatusRed
        },
        label = "statusColor"
    )

    val statusText = when {
        daysUsed <= 80 -> "COMPLIANT"
        daysUsed <= 85 -> "WARNING"
        else -> "EXCEEDED"
    }

    Box(
        modifier = modifier
            .size(280.dp)
            .clip(CircleShape)
            .background(
                brush = Brush.radialGradient(
                    colors = listOf(
                        statusColor.copy(alpha = 0.1f),
                        statusColor.copy(alpha = 0.05f)
                    )
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        // Outer ring
        CircularProgressIndicator(
            progress = { 1f },
            modifier = Modifier.size(260.dp),
            color = MaterialTheme.colorScheme.surfaceVariant,
            strokeWidth = 12.dp
        )

        // Progress ring
        CircularProgressIndicator(
            progress = { animatedProgress },
            modifier = Modifier.size(260.dp),
            color = statusColor,
            strokeWidth = 12.dp
        )

        // Center content
        Column(
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = "$daysUsed",
                fontSize = 72.sp,
                fontWeight = FontWeight.Bold,
                color = statusColor
            )
            Text(
                text = "of 90 days used",
                style = MaterialTheme.typography.bodyLarge,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(8.dp))
            Surface(
                shape = RoundedCornerShape(16.dp),
                color = statusColor.copy(alpha = 0.15f)
            ) {
                Text(
                    text = statusText,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp),
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = statusColor
                )
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "$daysRemaining days remaining",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
        }
    }
}

@Composable
private fun PeriodInfo(
    periodStart: String,
    periodEnd: String,
    dateFormatter: DateTimeFormatter,
    modifier: Modifier = Modifier
) {
    val startDate = LocalDate.parse(periodStart)
    val endDate = LocalDate.parse(periodEnd)

    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        )
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceEvenly
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    "Period Start",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    startDate.format(dateFormatter),
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium
                )
            }
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    "Period End",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    endDate.format(dateFormatter),
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium
                )
            }
        }
    }
}

@Composable
private fun CurrentLocationCard(
    countryCode: String,
    currentTrip: Trip?,
    modifier: Modifier = Modifier
) {
    val countryName = SchengenCountries.nameFor(countryCode) ?: countryCode
    val flag = SchengenCountries.flagFor(countryCode)
    val isSchengen = SchengenCountries.isSchengen(countryCode)

    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = if (isSchengen) {
                MaterialTheme.colorScheme.primaryContainer
            } else {
                MaterialTheme.colorScheme.secondaryContainer
            }
        )
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                flag,
                fontSize = 48.sp
            )
            Spacer(modifier = Modifier.width(16.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    "Currently in",
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    countryName,
                    style = MaterialTheme.typography.headlineSmall,
                    fontWeight = FontWeight.Bold
                )
                currentTrip?.let { trip ->
                    val days = trip.durationDays()
                    Text(
                        "$days day${if (days != 1) "s" else ""} this trip",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            Surface(
                shape = RoundedCornerShape(8.dp),
                color = if (isSchengen) {
                    MaterialTheme.colorScheme.primary
                } else {
                    MaterialTheme.colorScheme.secondary
                }
            ) {
                Text(
                    if (isSchengen) "SCHENGEN" else "NON-SCHENGEN",
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )
            }
        }
    }
}

@Composable
private fun TripCard(
    trip: Trip,
    dateFormatter: DateTimeFormatter,
    modifier: Modifier = Modifier
) {
    val startDate = LocalDate.parse(trip.startDate)
    val endDate = LocalDate.parse(trip.endDate)
    val countryName = SchengenCountries.nameFor(trip.country) ?: trip.country
    val flag = SchengenCountries.flagFor(trip.country)
    val days = trip.durationDays()

    Card(
        modifier = modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                flag,
                fontSize = 32.sp
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    countryName,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    "${startDate.format(dateFormatter)} - ${endDate.format(dateFormatter)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Text(
                "$days day${if (days != 1) "s" else ""}",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
        }
    }
}

private fun formatLastUpdated(isoString: String): String {
    return try {
        val instant = java.time.Instant.parse(isoString)
        val now = java.time.Instant.now()
        val minutes = ChronoUnit.MINUTES.between(instant, now)

        when {
            minutes < 1 -> "just now"
            minutes < 60 -> "$minutes min ago"
            minutes < 1440 -> "${minutes / 60}h ago"
            else -> "${minutes / 1440}d ago"
        }
    } catch (e: Exception) {
        isoString
    }
}
