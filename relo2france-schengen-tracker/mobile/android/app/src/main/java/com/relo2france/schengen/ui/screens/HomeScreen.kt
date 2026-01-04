/**
 * HomeScreen.kt
 *
 * Main dashboard showing Schengen status overview.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.relo2france.schengen.data.AppStatusResponse
import com.relo2france.schengen.data.Trip
import com.relo2france.schengen.ui.theme.StatusGreen
import com.relo2france.schengen.ui.theme.StatusRed
import com.relo2france.schengen.ui.theme.StatusYellow
import com.relo2france.schengen.util.SchengenCountries
import java.time.LocalDate
import java.time.format.DateTimeFormatter

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    appStatus: AppStatusResponse?,
    trips: List<Trip>,
    isLoading: Boolean,
    isOnline: Boolean,
    pendingSyncCount: Int,
    onRefresh: () -> Unit,
    onNavigateToPassport: () -> Unit,
    onNavigateToTrips: () -> Unit,
    modifier: Modifier = Modifier
) {
    val dateFormatter = DateTimeFormatter.ofPattern("d MMM")

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text(
                            "Schengen Tracker",
                            fontWeight = FontWeight.Bold
                        )
                        if (!isOnline) {
                            Text(
                                "Offline mode",
                                style = MaterialTheme.typography.labelSmall,
                                color = StatusYellow
                            )
                        }
                    }
                },
                actions = {
                    if (pendingSyncCount > 0) {
                        Badge(
                            containerColor = StatusYellow
                        ) {
                            Text("$pendingSyncCount")
                        }
                    }
                    IconButton(onClick = onRefresh, enabled = !isLoading) {
                        if (isLoading) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                strokeWidth = 2.dp
                            )
                        } else {
                            Icon(Icons.Default.Refresh, contentDescription = "Sync")
                        }
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Status card
            item {
                StatusCard(
                    daysUsed = appStatus?.daysUsed ?: 0,
                    daysRemaining = appStatus?.daysRemaining ?: 90,
                    inSchengen = appStatus?.inSchengen ?: false,
                    currentCountry = appStatus?.currentCountry,
                    onViewDetails = onNavigateToPassport
                )
            }

            // Alerts
            appStatus?.alerts?.let { alerts ->
                if (alerts.isNotEmpty()) {
                    item {
                        AlertsSection(alerts = alerts)
                    }
                }
            }

            // Quick actions
            item {
                QuickActionsRow(
                    onPassportControl = onNavigateToPassport,
                    onViewTrips = onNavigateToTrips
                )
            }

            // Recent trips
            if (trips.isNotEmpty()) {
                item {
                    Text(
                        "Recent Trips",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold
                    )
                }

                items(trips.take(5)) { trip ->
                    RecentTripItem(trip = trip, dateFormatter = dateFormatter)
                }

                if (trips.size > 5) {
                    item {
                        TextButton(
                            onClick = onNavigateToTrips,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Text("View all ${trips.size} trips")
                            Icon(
                                Icons.Default.ArrowForward,
                                contentDescription = null,
                                modifier = Modifier.padding(start = 4.dp)
                            )
                        }
                    }
                }
            }

            // Location tracking status
            item {
                LocationTrackingCard()
            }
        }
    }
}

@Composable
private fun StatusCard(
    daysUsed: Int,
    daysRemaining: Int,
    inSchengen: Boolean,
    currentCountry: String?,
    onViewDetails: () -> Unit,
    modifier: Modifier = Modifier
) {
    val statusColor = when {
        daysUsed <= 80 -> StatusGreen
        daysUsed <= 85 -> StatusYellow
        else -> StatusRed
    }

    Card(
        onClick = onViewDetails,
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = statusColor.copy(alpha = 0.1f)
        )
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column {
                    Text(
                        "Schengen Days",
                        style = MaterialTheme.typography.labelLarge,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Row(
                        verticalAlignment = Alignment.Baseline
                    ) {
                        Text(
                            "$daysUsed",
                            style = MaterialTheme.typography.displayMedium,
                            fontWeight = FontWeight.Bold,
                            color = statusColor
                        )
                        Text(
                            " / 90",
                            style = MaterialTheme.typography.headlineSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }

                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = if (inSchengen) {
                        MaterialTheme.colorScheme.primaryContainer
                    } else {
                        MaterialTheme.colorScheme.secondaryContainer
                    }
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        currentCountry?.let { code ->
                            Text(
                                SchengenCountries.flagFor(code),
                                style = MaterialTheme.typography.titleLarge
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                        }
                        Text(
                            if (inSchengen) "In Schengen" else "Outside",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            LinearProgressIndicator(
                progress = { (daysUsed / 90f).coerceIn(0f, 1f) },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(8.dp),
                color = statusColor,
                trackColor = MaterialTheme.colorScheme.surfaceVariant
            )

            Spacer(modifier = Modifier.height(12.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    "$daysRemaining days remaining",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    "Tap for details →",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.primary
                )
            }
        }
    }
}

@Composable
private fun AlertsSection(
    alerts: List<com.relo2france.schengen.data.Alert>,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        alerts.forEach { alert ->
            val (containerColor, contentColor) = when (alert.severity) {
                "error" -> StatusRed.copy(alpha = 0.1f) to StatusRed
                "warning" -> StatusYellow.copy(alpha = 0.1f) to StatusYellow
                else -> MaterialTheme.colorScheme.primaryContainer to MaterialTheme.colorScheme.onPrimaryContainer
            }

            Card(
                colors = CardDefaults.cardColors(containerColor = containerColor)
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        when (alert.severity) {
                            "error" -> Icons.Default.Warning
                            "warning" -> Icons.Default.Info
                            else -> Icons.Default.CheckCircle
                        },
                        contentDescription = null,
                        tint = contentColor,
                        modifier = Modifier.size(20.dp)
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Text(
                        alert.message,
                        style = MaterialTheme.typography.bodyMedium,
                        color = contentColor
                    )
                }
            }
        }
    }
}

@Composable
private fun QuickActionsRow(
    onPassportControl: () -> Unit,
    onViewTrips: () -> Unit,
    modifier: Modifier = Modifier
) {
    Row(
        modifier = modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        OutlinedCard(
            onClick = onPassportControl,
            modifier = Modifier.weight(1f)
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(
                    Icons.Default.Person,
                    contentDescription = null,
                    modifier = Modifier.size(32.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    "Passport Control",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Medium
                )
            }
        }

        OutlinedCard(
            onClick = onViewTrips,
            modifier = Modifier.weight(1f)
        ) {
            Column(
                modifier = Modifier.padding(16.dp),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Icon(
                    Icons.Default.Place,
                    contentDescription = null,
                    modifier = Modifier.size(32.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    "All Trips",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Medium
                )
            }
        }
    }
}

@Composable
private fun RecentTripItem(
    trip: Trip,
    dateFormatter: DateTimeFormatter,
    modifier: Modifier = Modifier
) {
    val startDate = LocalDate.parse(trip.startDate)
    val endDate = LocalDate.parse(trip.endDate)
    val countryName = SchengenCountries.nameFor(trip.country) ?: trip.country
    val flag = SchengenCountries.flagFor(trip.country)
    val days = trip.durationDays()

    Card(modifier = modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(flag, style = MaterialTheme.typography.headlineMedium)
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    countryName,
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    "${startDate.format(dateFormatter)} - ${endDate.format(dateFormatter)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Text(
                "$days d",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
        }
    }
}

@Composable
private fun LocationTrackingCard(
    modifier: Modifier = Modifier
) {
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
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                Icons.Default.LocationOn,
                contentDescription = null,
                tint = StatusGreen,
                modifier = Modifier.size(24.dp)
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    "Background Tracking Active",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    "Checking location at 8 AM, 2 PM, 8 PM",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Surface(
                shape = RoundedCornerShape(8.dp),
                color = StatusGreen.copy(alpha = 0.15f)
            ) {
                Text(
                    "ON",
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp),
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = StatusGreen
                )
            }
        }
    }
}
