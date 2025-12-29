/**
 * PhotoImportScreen.kt
 *
 * UI for importing trips from photo library GPS metadata.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.screens

import android.Manifest
import android.graphics.Bitmap
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.relo2france.schengen.data.SchengenRepository
import com.relo2france.schengen.import.DetectedPhotoTrip
import com.relo2france.schengen.import.ImportPhase
import com.relo2france.schengen.import.PhotoImportProgress
import com.relo2france.schengen.import.PhotoImporter
import com.relo2france.schengen.ui.theme.StatusGreen
import com.relo2france.schengen.util.SchengenCountries
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.format.DateTimeFormatter

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PhotoImportScreen(
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    var hasPermission by remember { mutableStateOf(false) }
    var isScanning by remember { mutableStateOf(false) }
    var progress by remember { mutableStateOf<PhotoImportProgress?>(null) }
    var detectedTrips by remember { mutableStateOf<List<DetectedPhotoTrip>>(emptyList()) }
    var selectedTripIds by remember { mutableStateOf<Set<String>>(emptySet()) }
    var importComplete by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    var startDate by remember { mutableStateOf(LocalDate.now().minusYears(1)) }
    var endDate by remember { mutableStateOf(LocalDate.now()) }

    // Thumbnails cache
    var thumbnails by remember { mutableStateOf<Map<String, Bitmap>>(emptyMap()) }

    val photoImporter = remember { PhotoImporter.getInstance(context) }
    val repository = remember { SchengenRepository.getInstance(context) }

    // Permission launcher
    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission()
    ) { granted ->
        hasPermission = granted
    }

    LaunchedEffect(Unit) {
        // Request permission on launch
        permissionLauncher.launch(Manifest.permission.READ_MEDIA_IMAGES)
    }

    // Load thumbnails for detected trips
    LaunchedEffect(detectedTrips) {
        detectedTrips.forEach { trip ->
            trip.samplePhotoUri?.let { uri ->
                if (!thumbnails.containsKey(trip.id)) {
                    val bitmap = photoImporter.loadThumbnail(uri)
                    if (bitmap != null) {
                        thumbnails = thumbnails + (trip.id to bitmap)
                    }
                }
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Import from Photos") },
                navigationIcon = {
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when {
                !hasPermission -> {
                    // Permission request view
                    PermissionRequestContent(
                        onRequestPermission = {
                            permissionLauncher.launch(Manifest.permission.READ_MEDIA_IMAGES)
                        }
                    )
                }
                isScanning -> {
                    // Scanning progress view
                    ScanningContent(progress = progress)
                }
                detectedTrips.isEmpty() && progress == null -> {
                    // Date selection view
                    DateSelectionContent(
                        startDate = startDate,
                        endDate = endDate,
                        onStartDateChange = { startDate = it },
                        onEndDateChange = { endDate = it },
                        onScan = {
                            scope.launch {
                                isScanning = true
                                error = null
                                try {
                                    val trips = photoImporter.scanPhotos(startDate, endDate) { p ->
                                        progress = p
                                    }
                                    detectedTrips = trips
                                    // Auto-select Schengen trips
                                    selectedTripIds = trips.filter { it.isSchengen }.map { it.id }.toSet()
                                } catch (e: Exception) {
                                    error = e.message
                                }
                                isScanning = false
                            }
                        }
                    )
                }
                importComplete -> {
                    // Import complete view
                    ImportCompleteContent(
                        importedCount = selectedTripIds.size,
                        onDone = onDismiss
                    )
                }
                else -> {
                    // Trip selection view
                    TripSelectionContent(
                        trips = detectedTrips,
                        selectedIds = selectedTripIds,
                        thumbnails = thumbnails,
                        onToggleTrip = { trip ->
                            selectedTripIds = if (selectedTripIds.contains(trip.id)) {
                                selectedTripIds - trip.id
                            } else {
                                selectedTripIds + trip.id
                            }
                        },
                        onSelectAllSchengen = {
                            selectedTripIds = detectedTrips.filter { it.isSchengen }.map { it.id }.toSet()
                        },
                        onSelectAll = {
                            selectedTripIds = detectedTrips.map { it.id }.toSet()
                        },
                        onDeselectAll = {
                            selectedTripIds = emptySet()
                        },
                        onImport = {
                            scope.launch {
                                val tripsToImport = detectedTrips.filter { selectedTripIds.contains(it.id) }
                                for (trip in tripsToImport) {
                                    repository.insertTrip(trip.toTrip())
                                }
                                importComplete = true
                            }
                        }
                    )
                }
            }

            // Error snackbar
            error?.let { errorMessage ->
                Snackbar(
                    modifier = Modifier
                        .align(Alignment.BottomCenter)
                        .padding(16.dp),
                    action = {
                        TextButton(onClick = { error = null }) {
                            Text("OK")
                        }
                    }
                ) {
                    Text(errorMessage)
                }
            }
        }
    }
}

@Composable
private fun PermissionRequestContent(
    onRequestPermission: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.Photo,
            contentDescription = null,
            modifier = Modifier.size(64.dp),
            tint = MaterialTheme.colorScheme.primary
        )

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            "Photo Library Access",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold
        )

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            "Allow access to scan your photos for GPS data and automatically detect your travel history.",
            style = MaterialTheme.typography.bodyMedium,
            textAlign = TextAlign.Center,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )

        Spacer(modifier = Modifier.height(32.dp))

        Button(
            onClick = onRequestPermission,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Grant Access")
        }

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            "We only read GPS metadata. Your photos stay on your device.",
            style = MaterialTheme.typography.bodySmall,
            textAlign = TextAlign.Center,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DateSelectionContent(
    startDate: LocalDate,
    endDate: LocalDate,
    onStartDateChange: (LocalDate) -> Unit,
    onEndDateChange: (LocalDate) -> Unit,
    onScan: () -> Unit
) {
    val dateFormatter = remember { DateTimeFormatter.ofPattern("MMM d, yyyy") }
    var showStartPicker by remember { mutableStateOf(false) }
    var showEndPicker by remember { mutableStateOf(false) }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.DateRange,
            contentDescription = null,
            modifier = Modifier.size(56.dp),
            tint = MaterialTheme.colorScheme.primary
        )

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            "Select Date Range",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            "Choose the period to scan for travel photos.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )

        Spacer(modifier = Modifier.height(32.dp))

        Card(
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(modifier = Modifier.padding(16.dp)) {
                OutlinedButton(
                    onClick = { showStartPicker = true },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Default.CalendarToday, contentDescription = null)
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("From: ${startDate.format(dateFormatter)}")
                }

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedButton(
                    onClick = { showEndPicker = true },
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Icon(Icons.Default.CalendarToday, contentDescription = null)
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("To: ${endDate.format(dateFormatter)}")
                }
            }
        }

        Spacer(modifier = Modifier.height(32.dp))

        Button(
            onClick = onScan,
            modifier = Modifier.fillMaxWidth()
        ) {
            Icon(Icons.Default.Search, contentDescription = null)
            Spacer(modifier = Modifier.width(8.dp))
            Text("Scan Photos")
        }
    }

    // Date pickers would go here (simplified for now)
}

@Composable
private fun ScanningContent(
    progress: PhotoImportProgress?
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        CircularProgressIndicator(
            modifier = Modifier.size(64.dp)
        )

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            when (progress?.phase) {
                ImportPhase.Scanning -> "Scanning photos..."
                ImportPhase.Geocoding -> "Detecting countries..."
                ImportPhase.Grouping -> "Grouping into trips..."
                ImportPhase.Complete -> "Complete!"
                null -> "Preparing..."
            },
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Medium
        )

        progress?.let { p ->
            Spacer(modifier = Modifier.height(24.dp))

            LinearProgressIndicator(
                progress = { p.percentage },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(8.dp))

            Text(
                "${p.current} / ${p.total}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun TripSelectionContent(
    trips: List<DetectedPhotoTrip>,
    selectedIds: Set<String>,
    thumbnails: Map<String, Bitmap>,
    onToggleTrip: (DetectedPhotoTrip) -> Unit,
    onSelectAllSchengen: () -> Unit,
    onSelectAll: () -> Unit,
    onDeselectAll: () -> Unit,
    onImport: () -> Unit
) {
    val dateFormatter = remember { DateTimeFormatter.ofPattern("MMM d, yyyy") }

    Column(modifier = Modifier.fillMaxSize()) {
        // Header
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                "${trips.size} trips found",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )

            var expanded by remember { mutableStateOf(false) }

            Box {
                IconButton(onClick = { expanded = true }) {
                    Icon(Icons.Default.MoreVert, contentDescription = "More options")
                }

                DropdownMenu(
                    expanded = expanded,
                    onDismissRequest = { expanded = false }
                ) {
                    DropdownMenuItem(
                        text = { Text("Select All Schengen") },
                        onClick = {
                            onSelectAllSchengen()
                            expanded = false
                        }
                    )
                    DropdownMenuItem(
                        text = { Text("Select All") },
                        onClick = {
                            onSelectAll()
                            expanded = false
                        }
                    )
                    DropdownMenuItem(
                        text = { Text("Deselect All") },
                        onClick = {
                            onDeselectAll()
                            expanded = false
                        }
                    )
                }
            }
        }

        HorizontalDivider()

        // Trip list
        LazyColumn(
            modifier = Modifier.weight(1f),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(trips) { trip ->
                TripSelectionCard(
                    trip = trip,
                    isSelected = selectedIds.contains(trip.id),
                    thumbnail = thumbnails[trip.id],
                    dateFormatter = dateFormatter,
                    onClick = { onToggleTrip(trip) }
                )
            }
        }

        HorizontalDivider()

        // Import button
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            val schengenDays = trips
                .filter { selectedIds.contains(it.id) && it.isSchengen }
                .sumOf { it.durationDays }

            if (schengenDays > 0) {
                Text(
                    "$schengenDays Schengen days will be imported",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.fillMaxWidth(),
                    textAlign = TextAlign.Center
                )
                Spacer(modifier = Modifier.height(8.dp))
            }

            Button(
                onClick = onImport,
                modifier = Modifier.fillMaxWidth(),
                enabled = selectedIds.isNotEmpty()
            ) {
                Text("Import ${selectedIds.size} Trip${if (selectedIds.size == 1) "" else "s"}")
            }
        }
    }
}

@Composable
private fun TripSelectionCard(
    trip: DetectedPhotoTrip,
    isSelected: Boolean,
    thumbnail: Bitmap?,
    dateFormatter: DateTimeFormatter,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .then(
                if (isSelected) {
                    Modifier.border(2.dp, MaterialTheme.colorScheme.primary, RoundedCornerShape(12.dp))
                } else {
                    Modifier
                }
            )
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Checkbox
            Icon(
                if (isSelected) Icons.Default.CheckCircle else Icons.Default.RadioButtonUnchecked,
                contentDescription = if (isSelected) "Selected" else "Not selected",
                tint = if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(24.dp)
            )

            Spacer(modifier = Modifier.width(12.dp))

            // Thumbnail
            Box(
                modifier = Modifier
                    .size(50.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .background(MaterialTheme.colorScheme.surfaceVariant)
            ) {
                if (thumbnail != null) {
                    Image(
                        bitmap = thumbnail.asImageBitmap(),
                        contentDescription = null,
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop
                    )
                } else {
                    Icon(
                        Icons.Default.Photo,
                        contentDescription = null,
                        modifier = Modifier
                            .align(Alignment.Center)
                            .size(24.dp),
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }

            Spacer(modifier = Modifier.width(12.dp))

            // Trip info
            Column(modifier = Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(SchengenCountries.flagFor(trip.countryCode))
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        trip.country,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Medium
                    )
                }

                Text(
                    "${trip.startDate.format(dateFormatter)} - ${trip.endDate.format(dateFormatter)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Text(
                    "${trip.photoCount} photos",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Duration and badge
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    "${trip.durationDays}d",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = if (trip.isSchengen) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                )

                if (trip.isSchengen) {
                    Surface(
                        shape = RoundedCornerShape(4.dp),
                        color = MaterialTheme.colorScheme.primaryContainer
                    ) {
                        Text(
                            "SCHENGEN",
                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun ImportCompleteContent(
    importedCount: Int,
    onDone: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.CheckCircle,
            contentDescription = null,
            modifier = Modifier.size(64.dp),
            tint = StatusGreen
        )

        Spacer(modifier = Modifier.height(24.dp))

        Text(
            "Import Complete!",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold
        )

        Spacer(modifier = Modifier.height(8.dp))

        Text(
            "$importedCount trip${if (importedCount == 1) "" else "s"} imported successfully.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )

        Spacer(modifier = Modifier.height(32.dp))

        Button(
            onClick = onDone,
            modifier = Modifier.fillMaxWidth()
        ) {
            Text("Done")
        }
    }
}
