/**
 * TripsScreen.kt
 *
 * Screen for viewing and managing all trips.
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.relo2france.schengen.data.SyncStatus
import com.relo2france.schengen.data.Trip
import com.relo2france.schengen.data.TripCategory
import com.relo2france.schengen.ui.theme.StatusGreen
import com.relo2france.schengen.ui.theme.StatusYellow
import com.relo2france.schengen.util.SchengenCountries
import java.time.LocalDate
import java.time.format.DateTimeFormatter

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TripsScreen(
    trips: List<Trip>,
    isLoading: Boolean,
    onRefresh: () -> Unit,
    onTripClick: (Trip) -> Unit,
    onAddTrip: () -> Unit,
    modifier: Modifier = Modifier
) {
    val dateFormatter = DateTimeFormatter.ofPattern("d MMM yyyy")

    // Group trips by year
    val tripsByYear = trips
        .sortedByDescending { it.startDate }
        .groupBy { LocalDate.parse(it.startDate).year }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text("All Trips", fontWeight = FontWeight.Bold)
                },
                actions = {
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
        floatingActionButton = {
            FloatingActionButton(
                onClick = onAddTrip,
                containerColor = MaterialTheme.colorScheme.primary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Add Trip")
            }
        },
        modifier = modifier
    ) { paddingValues ->
        if (trips.isEmpty() && !isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    Icon(
                        Icons.Default.Place,
                        contentDescription = null,
                        modifier = Modifier.size(64.dp),
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        "No trips yet",
                        style = MaterialTheme.typography.titleLarge,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                    Text(
                        "Your trips will appear here as they're detected\nor you can add them manually.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(horizontal = 32.dp)
                    )
                    Button(onClick = onAddTrip) {
                        Icon(Icons.Default.Add, contentDescription = null)
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Add Trip")
                    }
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                // Summary card
                item {
                    TripsSummaryCard(trips = trips)
                }

                tripsByYear.forEach { (year, yearTrips) ->
                    item {
                        Text(
                            year.toString(),
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            modifier = Modifier.padding(vertical = 8.dp)
                        )
                    }

                    items(yearTrips) { trip ->
                        TripListItem(
                            trip = trip,
                            dateFormatter = dateFormatter,
                            onClick = { onTripClick(trip) }
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun TripsSummaryCard(
    trips: List<Trip>,
    modifier: Modifier = Modifier
) {
    val schengenTrips = trips.filter { it.category == TripCategory.SCHENGEN }
    val totalSchengenDays = schengenTrips.sumOf { it.durationDays() }
    val countriesVisited = trips.map { it.country }.distinct().size

    Card(
        modifier = modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f)
        )
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceEvenly
        ) {
            SummaryItem(
                value = trips.size.toString(),
                label = "Total Trips"
            )
            SummaryItem(
                value = totalSchengenDays.toString(),
                label = "Schengen Days"
            )
            SummaryItem(
                value = countriesVisited.toString(),
                label = "Countries"
            )
        }
    }
}

@Composable
private fun SummaryItem(
    value: String,
    label: String,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text(
            value,
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
        Text(
            label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TripListItem(
    trip: Trip,
    dateFormatter: DateTimeFormatter,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val startDate = LocalDate.parse(trip.startDate)
    val endDate = LocalDate.parse(trip.endDate)
    val countryName = SchengenCountries.nameFor(trip.country) ?: trip.country
    val flag = SchengenCountries.flagFor(trip.country)
    val days = trip.durationDays()
    val isSchengen = trip.category == TripCategory.SCHENGEN

    Card(
        onClick = onClick,
        modifier = modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Country flag
            Text(
                flag,
                style = MaterialTheme.typography.headlineLarge
            )

            Spacer(modifier = Modifier.width(16.dp))

            // Trip details
            Column(modifier = Modifier.weight(1f)) {
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        countryName,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Medium
                    )
                    Spacer(modifier = Modifier.width(8.dp))

                    // Sync status indicator
                    when (trip.syncStatus) {
                        SyncStatus.PENDING -> {
                            Icon(
                                Icons.Default.Sync,
                                contentDescription = "Pending sync",
                                modifier = Modifier.size(16.dp),
                                tint = StatusYellow
                            )
                        }
                        SyncStatus.SYNCED -> {
                            Icon(
                                Icons.Default.Check,
                                contentDescription = "Synced",
                                modifier = Modifier.size(16.dp),
                                tint = StatusGreen
                            )
                        }
                        SyncStatus.FAILED -> {
                            Icon(
                                Icons.Default.Warning,
                                contentDescription = "Sync failed",
                                modifier = Modifier.size(16.dp),
                                tint = MaterialTheme.colorScheme.error
                            )
                        }
                    }
                }

                Text(
                    "${startDate.format(dateFormatter)} - ${endDate.format(dateFormatter)}",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                trip.notes?.let { notes ->
                    if (notes.isNotBlank()) {
                        Text(
                            notes,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            maxLines = 1
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.width(12.dp))

            // Days count and category
            Column(
                horizontalAlignment = Alignment.End
            ) {
                Text(
                    "$days days",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = if (isSchengen) {
                        MaterialTheme.colorScheme.primary
                    } else {
                        MaterialTheme.colorScheme.secondary
                    }
                )
                Surface(
                    shape = RoundedCornerShape(4.dp),
                    color = if (isSchengen) {
                        MaterialTheme.colorScheme.primaryContainer
                    } else {
                        MaterialTheme.colorScheme.secondaryContainer
                    }
                ) {
                    Text(
                        if (isSchengen) "Schengen" else "Other",
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = if (isSchengen) {
                            MaterialTheme.colorScheme.onPrimaryContainer
                        } else {
                            MaterialTheme.colorScheme.onSecondaryContainer
                        }
                    )
                }
            }

            Spacer(modifier = Modifier.width(8.dp))

            Icon(
                Icons.Default.ChevronRight,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}
