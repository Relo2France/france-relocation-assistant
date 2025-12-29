/**
 * AppNavigation.kt
 *
 * Main navigation setup with bottom navigation.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

package com.relo2france.schengen.ui.navigation

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.relo2france.schengen.data.AppStatusResponse
import com.relo2france.schengen.data.PassportControlData
import com.relo2france.schengen.data.Trip
import com.relo2france.schengen.ui.screens.*

sealed class Screen(
    val route: String,
    val title: String,
    val selectedIcon: ImageVector,
    val unselectedIcon: ImageVector
) {
    data object Home : Screen(
        route = "home",
        title = "Home",
        selectedIcon = Icons.Filled.Home,
        unselectedIcon = Icons.Outlined.Home
    )

    data object Trips : Screen(
        route = "trips",
        title = "Trips",
        selectedIcon = Icons.Filled.Place,
        unselectedIcon = Icons.Outlined.Place
    )

    data object Passport : Screen(
        route = "passport",
        title = "Passport",
        selectedIcon = Icons.Filled.Person,
        unselectedIcon = Icons.Outlined.Person
    )

    data object Settings : Screen(
        route = "settings",
        title = "Settings",
        selectedIcon = Icons.Filled.Settings,
        unselectedIcon = Icons.Outlined.Settings
    )
}

val bottomNavItems = listOf(
    Screen.Home,
    Screen.Trips,
    Screen.Passport,
    Screen.Settings
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AppNavigation(
    appStatus: AppStatusResponse?,
    passportData: PassportControlData?,
    trips: List<Trip>,
    isLoading: Boolean,
    isOnline: Boolean,
    isLoggedIn: Boolean,
    userName: String?,
    isLocationEnabled: Boolean,
    isNotificationsEnabled: Boolean,
    pendingSyncCount: Int,
    onRefresh: () -> Unit,
    onToggleLocation: (Boolean) -> Unit,
    onToggleNotifications: (Boolean) -> Unit,
    onLogin: () -> Unit,
    onLogout: () -> Unit,
    onSyncNow: () -> Unit,
    onClearData: () -> Unit,
    onAddTrip: () -> Unit,
    onTripClick: (Trip) -> Unit,
    modifier: Modifier = Modifier
) {
    val navController = rememberNavController()
    val navBackStackEntry by navController.currentBackStackEntryAsState()
    val currentDestination = navBackStackEntry?.destination

    Scaffold(
        bottomBar = {
            NavigationBar {
                bottomNavItems.forEach { screen ->
                    val selected = currentDestination?.hierarchy?.any { it.route == screen.route } == true

                    NavigationBarItem(
                        icon = {
                            Icon(
                                if (selected) screen.selectedIcon else screen.unselectedIcon,
                                contentDescription = screen.title
                            )
                        },
                        label = { Text(screen.title) },
                        selected = selected,
                        onClick = {
                            navController.navigate(screen.route) {
                                popUpTo(navController.graph.findStartDestination().id) {
                                    saveState = true
                                }
                                launchSingleTop = true
                                restoreState = true
                            }
                        }
                    )
                }
            }
        },
        modifier = modifier
    ) { innerPadding ->
        NavHost(
            navController = navController,
            startDestination = Screen.Home.route,
            modifier = Modifier.padding(innerPadding)
        ) {
            composable(Screen.Home.route) {
                HomeScreen(
                    appStatus = appStatus,
                    trips = trips,
                    isLoading = isLoading,
                    isOnline = isOnline,
                    pendingSyncCount = pendingSyncCount,
                    onRefresh = onRefresh,
                    onNavigateToPassport = {
                        navController.navigate(Screen.Passport.route)
                    },
                    onNavigateToTrips = {
                        navController.navigate(Screen.Trips.route)
                    }
                )
            }

            composable(Screen.Trips.route) {
                TripsScreen(
                    trips = trips,
                    isLoading = isLoading,
                    onRefresh = onRefresh,
                    onTripClick = onTripClick,
                    onAddTrip = onAddTrip
                )
            }

            composable(Screen.Passport.route) {
                PassportControlScreen(
                    passportData = passportData,
                    isLoading = isLoading,
                    isOnline = isOnline,
                    onRefresh = onRefresh
                )
            }

            composable(Screen.Settings.route) {
                SettingsScreen(
                    isLoggedIn = isLoggedIn,
                    userName = userName,
                    isLocationEnabled = isLocationEnabled,
                    isNotificationsEnabled = isNotificationsEnabled,
                    pendingSyncCount = pendingSyncCount,
                    onToggleLocation = onToggleLocation,
                    onToggleNotifications = onToggleNotifications,
                    onLogin = onLogin,
                    onLogout = onLogout,
                    onSyncNow = onSyncNow,
                    onClearData = onClearData,
                    onOpenPrivacyPolicy = {
                        // Open privacy policy URL
                    },
                    onOpenSupport = {
                        // Open support URL
                    }
                )
            }
        }
    }
}
