/**
 * PrivacySettingsScreen.kt
 *
 * Privacy and data management settings.
 * Includes:
 * - Permission controls (location, analytics)
 * - Data export
 * - Account deletion
 * - Legal disclaimer
 * - Privacy policy links
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.ui.screens

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.mytravelstatus.app.service.*
import com.mytravelstatus.app.ui.theme.StatusOrange
import com.mytravelstatus.app.ui.theme.StatusRed
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PrivacySettingsScreen(
    onNavigateBack: () -> Unit,
    onShowLocationEducation: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val coroutineScope = rememberCoroutineScope()

    // Collect state from services
    val backgroundLocationEnabled by PrivacySettings.backgroundLocationEnabled.collectAsState()
    val analyticsEnabled by PrivacySettings.analyticsEnabled.collectAsState()
    val crashReportingEnabled by PrivacySettings.crashReportingEnabled.collectAsState()
    val isExporting by DataManagementService.isExporting.collectAsState()
    val isDeletingAccount by DataManagementService.isDeletingAccount.collectAsState()

    // Dialog states
    var showExportDialog by remember { mutableStateOf(false) }
    var showClearDataDialog by remember { mutableStateOf(false) }
    var showDeleteAccountDialog by remember { mutableStateOf(false) }
    var showDeleteConfirmDialog by remember { mutableStateOf(false) }
    var deleteConfirmText by remember { mutableStateOf("") }

    LaunchedEffect(Unit) {
        AnalyticsManager.trackScreen(AnalyticsScreen.PRIVACY_SETTINGS)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Privacy & Data", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
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
            contentPadding = PaddingValues(vertical = 8.dp)
        ) {
            // Legal Disclaimer Section
            item {
                DisclaimerCard()
            }

            // Location Section
            item {
                SectionHeader("Location")
            }

            item {
                PrivacySwitch(
                    icon = Icons.Default.LocationOn,
                    iconTint = MaterialTheme.colorScheme.primary,
                    title = "Background Location",
                    subtitle = if (backgroundLocationEnabled) {
                        "Active - 3 checks/day"
                    } else {
                        "Disabled - use manual check-in"
                    },
                    checked = backgroundLocationEnabled,
                    onCheckedChange = { enabled ->
                        if (enabled && !PrivacySettings.hasShownBackgroundLocationEducation()) {
                            onShowLocationEducation()
                        } else {
                            PrivacySettings.setBackgroundLocationEnabled(enabled)
                            if (enabled) {
                                LocationScheduler.scheduleAllChecks(context)
                            } else {
                                LocationScheduler.cancelAllTasks(context)
                            }
                        }
                    }
                )
            }

            item {
                Text(
                    "Background tracking checks your country 3 times daily. Only country names are stored—never precise coordinates.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                )
            }

            // Analytics Section
            item {
                SectionHeader("Analytics & Diagnostics")
            }

            item {
                PrivacySwitch(
                    icon = Icons.Default.BarChart,
                    iconTint = Color(0xFF9C27B0), // Purple
                    title = "Usage Analytics",
                    subtitle = "Anonymous app usage data",
                    checked = analyticsEnabled,
                    onCheckedChange = { enabled ->
                        if (enabled) {
                            AnalyticsManager.enable()
                        } else {
                            AnalyticsManager.disable()
                        }
                    }
                )
            }

            item {
                PrivacySwitch(
                    icon = Icons.Default.BugReport,
                    iconTint = StatusOrange,
                    title = "Crash Reports",
                    subtitle = "Help us fix bugs",
                    checked = crashReportingEnabled,
                    onCheckedChange = { PrivacySettings.setCrashReportingEnabled(it) }
                )
            }

            item {
                Text(
                    "Anonymous usage data helps us improve the app. No personal or location data is included.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                )
            }

            // Data Management Section
            item {
                SectionHeader("Your Data")
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.FileUpload,
                    iconTint = MaterialTheme.colorScheme.primary,
                    title = "Export My Data",
                    subtitle = "Download all your data as JSON",
                    onClick = { showExportDialog = true },
                    trailing = {
                        if (isExporting) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                strokeWidth = 2.dp
                            )
                        }
                    }
                )
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.DeleteSweep,
                    iconTint = StatusOrange,
                    title = "Clear Local Data",
                    subtitle = "Remove cached data (keeps account)",
                    onClick = { showClearDataDialog = true }
                )
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.PersonOff,
                    iconTint = StatusRed,
                    title = "Delete Account & Data",
                    subtitle = "Permanently remove everything",
                    onClick = { showDeleteAccountDialog = true },
                    trailing = {
                        if (isDeletingAccount) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(24.dp),
                                strokeWidth = 2.dp
                            )
                        }
                    }
                )
            }

            item {
                Text(
                    "You can export a copy of all your data or permanently delete your account and all associated data.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)
                )
            }

            // Legal Section
            item {
                SectionHeader("Legal")
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.PrivacyTip,
                    iconTint = MaterialTheme.colorScheme.primary,
                    title = "Privacy Policy",
                    subtitle = "How we handle your data",
                    onClick = {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://mytravelstatus.com/privacy"))
                        context.startActivity(intent)
                    },
                    trailing = {
                        Icon(
                            Icons.Default.OpenInNew,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                )
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.Description,
                    iconTint = MaterialTheme.colorScheme.primary,
                    title = "Terms of Service",
                    subtitle = "User agreement",
                    onClick = {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://mytravelstatus.com/terms"))
                        context.startActivity(intent)
                    },
                    trailing = {
                        Icon(
                            Icons.Default.OpenInNew,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                )
            }

            item {
                PrivacyItem(
                    icon = Icons.Default.HelpOutline,
                    iconTint = MaterialTheme.colorScheme.primary,
                    title = "Help & Support",
                    subtitle = "Get assistance",
                    onClick = {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://mytravelstatus.com/support"))
                        context.startActivity(intent)
                    },
                    trailing = {
                        Icon(
                            Icons.Default.OpenInNew,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                )
            }

            item {
                Spacer(modifier = Modifier.height(32.dp))
            }
        }
    }

    // Export Dialog
    if (showExportDialog) {
        AlertDialog(
            onDismissRequest = { showExportDialog = false },
            title = { Text("Export Your Data") },
            text = {
                Text("Your data will be exported as a JSON file that you can save or share. This includes all your trips, locations, and settings.")
            },
            confirmButton = {
                Button(
                    onClick = {
                        showExportDialog = false
                        coroutineScope.launch {
                            try {
                                val result = DataManagementService.exportAllData(context)
                                DataManagementService.shareExportedData(context, result)
                            } catch (e: Exception) {
                                // Error handled by service
                            }
                        }
                    },
                    enabled = !isExporting
                ) {
                    Text("Export")
                }
            },
            dismissButton = {
                TextButton(onClick = { showExportDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Clear Data Dialog
    if (showClearDataDialog) {
        AlertDialog(
            onDismissRequest = { showClearDataDialog = false },
            title = { Text("Clear Local Data") },
            text = {
                Text("This will remove all cached trips and locations from this device. Your account and server data will not be affected.")
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showClearDataDialog = false
                        coroutineScope.launch {
                            DataManagementService.clearLocalDataOnly(context)
                        }
                    }
                ) {
                    Text("Clear", color = StatusRed)
                }
            },
            dismissButton = {
                TextButton(onClick = { showClearDataDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Delete Account Dialog (first confirmation)
    if (showDeleteAccountDialog) {
        AlertDialog(
            onDismissRequest = { showDeleteAccountDialog = false },
            title = { Text("Delete Account?") },
            text = {
                Text("This will permanently delete your account and all data from our servers. This action cannot be undone.")
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        showDeleteAccountDialog = false
                        showDeleteConfirmDialog = true
                    }
                ) {
                    Text("Continue", color = StatusRed)
                }
            },
            dismissButton = {
                TextButton(onClick = { showDeleteAccountDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Delete Account Dialog (final confirmation)
    if (showDeleteConfirmDialog) {
        AlertDialog(
            onDismissRequest = {
                showDeleteConfirmDialog = false
                deleteConfirmText = ""
            },
            title = { Text("Confirm Deletion") },
            text = {
                Column {
                    Text("Type DELETE to confirm. All your trips, locations, and account data will be permanently removed.")
                    Spacer(modifier = Modifier.height(16.dp))
                    OutlinedTextField(
                        value = deleteConfirmText,
                        onValueChange = { deleteConfirmText = it },
                        label = { Text("Type DELETE") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        if (deleteConfirmText.uppercase() == "DELETE") {
                            showDeleteConfirmDialog = false
                            deleteConfirmText = ""
                            coroutineScope.launch {
                                try {
                                    DataManagementService.deleteAccountAndData(context)
                                    // Navigate to login after deletion
                                } catch (e: Exception) {
                                    // Error handled by service
                                }
                            }
                        }
                    },
                    enabled = deleteConfirmText.uppercase() == "DELETE" && !isDeletingAccount
                ) {
                    Text("Delete Everything", color = StatusRed)
                }
            },
            dismissButton = {
                TextButton(
                    onClick = {
                        showDeleteConfirmDialog = false
                        deleteConfirmText = ""
                    }
                ) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
private fun DisclaimerCard(modifier: Modifier = Modifier) {
    Card(
        modifier = modifier
            .fillMaxWidth()
            .padding(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = StatusOrange.copy(alpha = 0.1f)
        )
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Default.Warning,
                    contentDescription = null,
                    tint = StatusOrange
                )
                Spacer(modifier = Modifier.width(8.dp))
                Text(
                    "Important Notice",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
            }

            Spacer(modifier = Modifier.height(12.dp))

            Text(
                """MyTravelStatus is an informational tool only. It is NOT legal, tax, or immigration advice.

• Always verify visa requirements with official government sources
• Consult with qualified professionals for legal/tax matters
• Entry decisions are at the discretion of border officials
• We do not guarantee accuracy of compliance calculations

Use this app as a helpful tracker, not as legal guidance.""",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun SectionHeader(title: String, modifier: Modifier = Modifier) {
    Text(
        title,
        style = MaterialTheme.typography.titleSmall,
        fontWeight = FontWeight.SemiBold,
        color = MaterialTheme.colorScheme.primary,
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp)
    )
}

@Composable
private fun PrivacySwitch(
    icon: ImageVector,
    iconTint: Color,
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit,
    modifier: Modifier = Modifier
) {
    Surface(modifier = modifier.fillMaxWidth()) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                icon,
                contentDescription = null,
                tint = iconTint,
                modifier = Modifier.size(24.dp)
            )
            Spacer(modifier = Modifier.width(16.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.bodyLarge)
                Text(
                    subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Switch(
                checked = checked,
                onCheckedChange = onCheckedChange
            )
        }
    }
}

@Composable
private fun PrivacyItem(
    icon: ImageVector,
    iconTint: Color,
    title: String,
    subtitle: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    trailing: @Composable (() -> Unit)? = null
) {
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                icon,
                contentDescription = null,
                tint = iconTint,
                modifier = Modifier.size(24.dp)
            )
            Spacer(modifier = Modifier.width(16.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.bodyLarge)
                Text(
                    subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            if (trailing != null) {
                trailing()
            } else {
                Icon(
                    Icons.Default.ChevronRight,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}
