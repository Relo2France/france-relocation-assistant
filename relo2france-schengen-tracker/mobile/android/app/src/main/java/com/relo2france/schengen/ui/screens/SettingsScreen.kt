/**
 * SettingsScreen.kt
 *
 * App settings and preferences.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.relo2france.schengen.ui.theme.StatusGreen
import com.relo2france.schengen.ui.theme.StatusRed

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    isLoggedIn: Boolean,
    userName: String?,
    isLocationEnabled: Boolean,
    isNotificationsEnabled: Boolean,
    pendingSyncCount: Int,
    onToggleLocation: (Boolean) -> Unit,
    onToggleNotifications: (Boolean) -> Unit,
    onLogin: () -> Unit,
    onLogout: () -> Unit,
    onSyncNow: () -> Unit,
    onClearData: () -> Unit,
    onOpenPrivacyPolicy: () -> Unit,
    onOpenSupport: () -> Unit,
    modifier: Modifier = Modifier
) {
    var showLogoutDialog by remember { mutableStateOf(false) }
    var showClearDataDialog by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Settings", fontWeight = FontWeight.Bold) }
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
            // Account section
            item {
                SettingsSectionHeader("Account")
            }

            item {
                if (isLoggedIn) {
                    AccountCard(
                        userName = userName ?: "User",
                        onLogout = { showLogoutDialog = true }
                    )
                } else {
                    SettingsItem(
                        icon = Icons.Default.Person,
                        title = "Sign In",
                        subtitle = "Connect to sync your data",
                        onClick = onLogin
                    )
                }
            }

            // Tracking section
            item {
                SettingsSectionHeader("Location Tracking")
            }

            item {
                SettingsSwitch(
                    icon = Icons.Default.LocationOn,
                    title = "Background Location",
                    subtitle = "Check location 3 times daily",
                    checked = isLocationEnabled,
                    onCheckedChange = onToggleLocation
                )
            }

            item {
                SettingsSwitch(
                    icon = Icons.Default.Notifications,
                    title = "Notifications",
                    subtitle = "Get alerts about your Schengen days",
                    checked = isNotificationsEnabled,
                    onCheckedChange = onToggleNotifications
                )
            }

            // Sync section
            item {
                SettingsSectionHeader("Data & Sync")
            }

            item {
                SettingsItem(
                    icon = Icons.Default.Sync,
                    title = "Sync Now",
                    subtitle = if (pendingSyncCount > 0) {
                        "$pendingSyncCount changes pending"
                    } else {
                        "All data synced"
                    },
                    onClick = onSyncNow,
                    trailing = {
                        if (pendingSyncCount > 0) {
                            Badge(containerColor = MaterialTheme.colorScheme.primary) {
                                Text("$pendingSyncCount")
                            }
                        } else {
                            Icon(
                                Icons.Default.CheckCircle,
                                contentDescription = null,
                                tint = StatusGreen,
                                modifier = Modifier.size(24.dp)
                            )
                        }
                    }
                )
            }

            item {
                SettingsItem(
                    icon = Icons.Default.Delete,
                    title = "Clear Local Data",
                    subtitle = "Remove all cached data",
                    onClick = { showClearDataDialog = true },
                    iconTint = StatusRed
                )
            }

            // About section
            item {
                SettingsSectionHeader("About")
            }

            item {
                SettingsItem(
                    icon = Icons.Default.Info,
                    title = "App Version",
                    subtitle = "1.0.0",
                    onClick = {}
                )
            }

            item {
                SettingsItem(
                    icon = Icons.Default.Lock,
                    title = "Privacy Policy",
                    subtitle = "How we handle your data",
                    onClick = onOpenPrivacyPolicy
                )
            }

            item {
                SettingsItem(
                    icon = Icons.Default.Email,
                    title = "Support",
                    subtitle = "Get help with the app",
                    onClick = onOpenSupport
                )
            }

            // Footer
            item {
                Spacer(modifier = Modifier.height(32.dp))
                Text(
                    "Schengen Tracker by Relo2France",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp)
                )
                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    // Logout dialog
    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title = { Text("Sign Out") },
            text = { Text("Are you sure you want to sign out? Unsynced data will be lost.") },
            confirmButton = {
                TextButton(
                    onClick = {
                        showLogoutDialog = false
                        onLogout()
                    }
                ) {
                    Text("Sign Out", color = StatusRed)
                }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    // Clear data dialog
    if (showClearDataDialog) {
        AlertDialog(
            onDismissRequest = { showClearDataDialog = false },
            title = { Text("Clear Local Data") },
            text = { Text("This will remove all cached trips and locations from this device. Data synced to the server will not be affected.") },
            confirmButton = {
                TextButton(
                    onClick = {
                        showClearDataDialog = false
                        onClearData()
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
}

@Composable
private fun SettingsSectionHeader(
    title: String,
    modifier: Modifier = Modifier
) {
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
private fun AccountCard(
    userName: String,
    onLogout: () -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 4.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Surface(
                shape = MaterialTheme.shapes.medium,
                color = MaterialTheme.colorScheme.primaryContainer
            ) {
                Icon(
                    Icons.Default.Person,
                    contentDescription = null,
                    modifier = Modifier.padding(12.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
            }
            Spacer(modifier = Modifier.width(16.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    userName,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Medium
                )
                Text(
                    "Signed in",
                    style = MaterialTheme.typography.bodySmall,
                    color = StatusGreen
                )
            }
            TextButton(onClick = onLogout) {
                Text("Sign Out")
            }
        }
    }
}

@Composable
private fun SettingsItem(
    icon: ImageVector,
    title: String,
    subtitle: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    iconTint: androidx.compose.ui.graphics.Color = MaterialTheme.colorScheme.onSurfaceVariant,
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
                Text(
                    title,
                    style = MaterialTheme.typography.bodyLarge
                )
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

@Composable
private fun SettingsSwitch(
    icon: ImageVector,
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
                .padding(horizontal = 16.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                icon,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(24.dp)
            )
            Spacer(modifier = Modifier.width(16.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    title,
                    style = MaterialTheme.typography.bodyLarge
                )
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
