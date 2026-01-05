/**
 * LocationEducationScreen.kt
 *
 * Educational disclosure screen shown before enabling background location.
 * Required by Google Play for apps using background location.
 *
 * This screen must explain:
 * 1. What data is collected (country only, not precise coordinates)
 * 2. How often it's collected (3 times daily)
 * 3. Why it's needed (automatic travel compliance tracking)
 * 4. User benefit (no manual entry required)
 * 5. That it's optional (manual check-in available)
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.ui.screens

import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.mytravelstatus.app.service.AnalyticsManager
import com.mytravelstatus.app.service.AnalyticsScreen
import kotlinx.coroutines.launch

/**
 * Education page data
 */
private data class EducationPage(
    val icon: ImageVector,
    val title: String,
    val description: String,
    val bulletPoints: List<String>
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LocationEducationScreen(
    onEnable: () -> Unit,
    onDecline: () -> Unit,
    modifier: Modifier = Modifier
) {
    val pages = remember {
        listOf(
            EducationPage(
                icon = Icons.Default.LocationOn,
                title = "Automatic Country Detection",
                description = "Background location helps track your travel days automatically without manual entry.",
                bulletPoints = listOf(
                    "Checks your location 3 times daily (morning, afternoon, evening)",
                    "Only stores which country you're in—never precise coordinates",
                    "Runs quietly in the background with minimal battery impact"
                )
            ),
            EducationPage(
                icon = Icons.Default.Security,
                title = "Your Privacy Protected",
                description = "We designed this feature with your privacy as the top priority.",
                bulletPoints = listOf(
                    "GPS coordinates are converted to country names immediately",
                    "Precise location data is never stored or transmitted",
                    "All data stays on your device unless you choose to sync",
                    "No tracking or advertising—ever"
                )
            ),
            EducationPage(
                icon = Icons.Default.TouchApp,
                title = "You're in Control",
                description = "Background location is completely optional.",
                bulletPoints = listOf(
                    "Turn it on or off anytime in Settings → Privacy & Data",
                    "Manual check-in is always available as an alternative",
                    "You can delete all location data at any time",
                    "Denying this permission will not affect other features"
                )
            )
        )
    }

    val pagerState = rememberPagerState(pageCount = { pages.size })
    val coroutineScope = rememberCoroutineScope()
    val isLastPage = pagerState.currentPage == pages.size - 1

    LaunchedEffect(Unit) {
        AnalyticsManager.trackScreen(AnalyticsScreen.LOCATION_EDUCATION)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Background Location") },
                navigationIcon = {
                    IconButton(onClick = onDecline) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            // Pager
            HorizontalPager(
                state = pagerState,
                modifier = Modifier.weight(1f)
            ) { page ->
                EducationPageContent(page = pages[page])
            }

            // Page indicator
            Row(
                horizontalArrangement = Arrangement.Center,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 16.dp)
            ) {
                repeat(pages.size) { index ->
                    val isSelected = pagerState.currentPage == index
                    Box(
                        modifier = Modifier
                            .padding(horizontal = 4.dp)
                            .size(if (isSelected) 10.dp else 8.dp)
                            .clip(CircleShape)
                            .background(
                                if (isSelected) MaterialTheme.colorScheme.primary
                                else MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.3f)
                            )
                    )
                }
            }

            // Buttons
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 24.dp)
                    .padding(bottom = 32.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                if (isLastPage) {
                    // Final page - show Enable/Decline buttons
                    Button(
                        onClick = {
                            AnalyticsManager.track(
                                com.mytravelstatus.app.service.AnalyticsEvent.BACKGROUND_LOCATION_ENABLED
                            )
                            onEnable()
                        },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Icon(Icons.Default.LocationOn, contentDescription = null)
                        Spacer(Modifier.width(8.dp))
                        Text("Enable Background Location")
                    }

                    OutlinedButton(
                        onClick = {
                            AnalyticsManager.track(
                                com.mytravelstatus.app.service.AnalyticsEvent.BACKGROUND_LOCATION_DISABLED
                            )
                            onDecline()
                        },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("No Thanks, I'll Check In Manually")
                    }
                } else {
                    // Not final page - show Next button
                    Button(
                        onClick = {
                            coroutineScope.launch {
                                pagerState.animateScrollToPage(pagerState.currentPage + 1)
                            }
                        },
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Next")
                        Spacer(Modifier.width(8.dp))
                        Icon(Icons.Default.ArrowForward, contentDescription = null)
                    }

                    TextButton(
                        onClick = onDecline,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Skip")
                    }
                }
            }
        }
    }
}

@Composable
private fun EducationPageContent(
    page: EducationPage,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .padding(horizontal = 24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(modifier = Modifier.height(32.dp))

        // Icon
        Surface(
            shape = CircleShape,
            color = MaterialTheme.colorScheme.primaryContainer,
            modifier = Modifier.size(100.dp)
        ) {
            Icon(
                page.icon,
                contentDescription = null,
                modifier = Modifier
                    .padding(24.dp)
                    .fillMaxSize(),
                tint = MaterialTheme.colorScheme.primary
            )
        }

        Spacer(modifier = Modifier.height(32.dp))

        // Title
        Text(
            page.title,
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold,
            textAlign = TextAlign.Center
        )

        Spacer(modifier = Modifier.height(16.dp))

        // Description
        Text(
            page.description,
            style = MaterialTheme.typography.bodyLarge,
            textAlign = TextAlign.Center,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )

        Spacer(modifier = Modifier.height(24.dp))

        // Bullet points
        Column(
            modifier = Modifier.fillMaxWidth(),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            page.bulletPoints.forEach { point ->
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.Start
                ) {
                    Icon(
                        Icons.Default.CheckCircle,
                        contentDescription = null,
                        modifier = Modifier.size(20.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.width(12.dp))
                    Text(
                        point,
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
    }
}
