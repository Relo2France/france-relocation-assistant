/**
 * SubscriptionScreen.kt
 *
 * Subscription purchase and management view.
 * Includes all required Google Play disclosures:
 * - Full pricing with period
 * - Auto-renewal terms
 * - Restore purchases
 * - Manage subscription link
 * - Links to Terms & Privacy Policy
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.ui.screens

import android.app.Activity
import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import com.android.billingclient.api.ProductDetails
import com.mytravelstatus.app.service.AnalyticsEvent
import com.mytravelstatus.app.service.AnalyticsManager
import com.mytravelstatus.app.service.AnalyticsScreen
import com.mytravelstatus.app.service.SubscriptionManager
import com.mytravelstatus.app.ui.theme.StatusGreen

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SubscriptionScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val activity = context as? Activity

    // Collect state from SubscriptionManager
    val products by SubscriptionManager.products.collectAsState()
    val subscriptionStatus by SubscriptionManager.subscriptionStatus.collectAsState()
    val isLoading by SubscriptionManager.isLoading.collectAsState()
    val purchaseError by SubscriptionManager.purchaseError.collectAsState()

    var selectedProduct by remember { mutableStateOf<ProductDetails?>(null) }
    var showErrorDialog by remember { mutableStateOf(false) }
    var showRestoreSuccessDialog by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        AnalyticsManager.trackScreen(AnalyticsScreen.SUBSCRIPTION)
        AnalyticsManager.track(AnalyticsEvent.SUBSCRIPTION_VIEW_OPENED)
    }

    LaunchedEffect(purchaseError) {
        if (purchaseError != null) {
            showErrorDialog = true
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Premium", fontWeight = FontWeight.Bold) },
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
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            // Header
            item {
                HeaderSection()
            }

            // Current subscription status (if subscribed)
            if (subscriptionStatus.isActive) {
                item {
                    CurrentSubscriptionCard(
                        displayText = subscriptionStatus.displayText,
                        onManageClick = {
                            SubscriptionManager.openManageSubscription(context)
                        }
                    )
                }
            }

            // Features list
            item {
                FeaturesSection()
            }

            // Products (if not subscribed)
            if (!subscriptionStatus.isActive) {
                item {
                    ProductsSection(
                        products = products,
                        selectedProduct = selectedProduct,
                        isLoading = isLoading,
                        onProductSelect = { selectedProduct = it }
                    )
                }

                // Purchase button
                item {
                    selectedProduct?.let { product ->
                        Button(
                            onClick = {
                                activity?.let { act ->
                                    SubscriptionManager.purchase(act, product)
                                }
                            },
                            enabled = !isLoading,
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            if (isLoading) {
                                CircularProgressIndicator(
                                    modifier = Modifier.size(24.dp),
                                    color = MaterialTheme.colorScheme.onPrimary,
                                    strokeWidth = 2.dp
                                )
                            } else {
                                Text(
                                    "Subscribe for ${SubscriptionManager.getFormattedPrice(product)}/${SubscriptionManager.getBillingPeriod(product)}"
                                )
                            }
                        }
                    }
                }
            }

            // Legal disclosures (REQUIRED by Google Play)
            item {
                LegalDisclosuresSection(
                    selectedProduct = selectedProduct ?: products.firstOrNull()
                )
            }

            // Actions (Restore, Manage)
            item {
                ActionsSection(
                    isActive = subscriptionStatus.isActive,
                    isLoading = isLoading,
                    onRestoreClick = {
                        SubscriptionManager.restorePurchases()
                        showRestoreSuccessDialog = true
                    },
                    onManageClick = {
                        SubscriptionManager.openManageSubscription(context)
                    }
                )
            }

            item {
                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    // Error dialog
    if (showErrorDialog && purchaseError != null) {
        AlertDialog(
            onDismissRequest = { showErrorDialog = false },
            title = { Text("Error") },
            text = { Text(purchaseError ?: "An error occurred") },
            confirmButton = {
                TextButton(onClick = { showErrorDialog = false }) {
                    Text("OK")
                }
            }
        )
    }

    // Restore success dialog
    if (showRestoreSuccessDialog) {
        AlertDialog(
            onDismissRequest = { showRestoreSuccessDialog = false },
            title = { Text("Restored") },
            text = { Text("Your purchases have been restored successfully.") },
            confirmButton = {
                TextButton(onClick = { showRestoreSuccessDialog = false }) {
                    Text("OK")
                }
            }
        )
    }
}

@Composable
private fun HeaderSection() {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier.fillMaxWidth()
    ) {
        // Gradient icon background
        Box(
            modifier = Modifier
                .size(80.dp)
                .clip(CircleShape)
                .background(
                    Brush.linearGradient(
                        colors = listOf(
                            MaterialTheme.colorScheme.primary,
                            Color(0xFF9C27B0) // Purple
                        )
                    )
                ),
            contentAlignment = Alignment.Center
        ) {
            Icon(
                Icons.Default.Star,
                contentDescription = null,
                modifier = Modifier.size(40.dp),
                tint = Color.White
            )
        }

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            "MyTravelStatus Premium",
            style = MaterialTheme.typography.headlineSmall,
            fontWeight = FontWeight.Bold
        )

        Text(
            "Track your travel compliance with ease",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}

@Composable
private fun CurrentSubscriptionCard(
    displayText: String,
    onManageClick: () -> Unit
) {
    Card(
        colors = CardDefaults.cardColors(
            containerColor = StatusGreen.copy(alpha = 0.1f)
        ),
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Icon(
                Icons.Default.CheckCircle,
                contentDescription = null,
                tint = StatusGreen
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    "Active Subscription",
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    displayText,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            TextButton(onClick = onManageClick) {
                Text("Manage")
            }
        }
    }
}

@Composable
private fun FeaturesSection() {
    Card(
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        ),
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                "Premium Features",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )

            FeatureRow(Icons.Default.LocationOn, "Background Location", "Automatic country detection 3x daily")
            FeatureRow(Icons.Default.PhotoLibrary, "Photo Import", "Extract trips from photo GPS data")
            FeatureRow(Icons.Default.CalendarMonth, "Calendar Sync", "Import trips from your calendar")
            FeatureRow(Icons.Default.Notifications, "Smart Alerts", "Get notified before hitting limits")
            FeatureRow(Icons.Default.People, "Family Tracking", "Track multiple travelers")
            FeatureRow(Icons.Default.Analytics, "Analytics", "Detailed compliance reports")
            FeatureRow(Icons.Default.Sync, "Cloud Sync", "Sync across all devices")
        }
    }
}

@Composable
private fun FeatureRow(icon: ImageVector, title: String, description: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            icon,
            contentDescription = null,
            modifier = Modifier.size(24.dp),
            tint = MaterialTheme.colorScheme.primary
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column {
            Text(title, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
            Text(description, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun ProductsSection(
    products: List<ProductDetails>,
    selectedProduct: ProductDetails?,
    isLoading: Boolean,
    onProductSelect: (ProductDetails) -> Unit
) {
    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        if (isLoading && products.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(32.dp),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator()
            }
        } else if (products.isEmpty()) {
            Text(
                "Unable to load subscription options",
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.fillMaxWidth(),
                textAlign = TextAlign.Center
            )
        } else {
            products.forEach { product ->
                ProductCard(
                    product = product,
                    isSelected = selectedProduct?.productId == product.productId,
                    onClick = { onProductSelect(product) }
                )
            }
        }
    }
}

@Composable
private fun ProductCard(
    product: ProductDetails,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    val isAnnual = product.productId.contains("annual")

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        colors = CardDefaults.cardColors(
            containerColor = if (isSelected) {
                MaterialTheme.colorScheme.primaryContainer
            } else {
                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
            }
        ),
        border = if (isSelected) {
            CardDefaults.outlinedCardBorder().copy(
                width = 2.dp,
                brush = Brush.linearGradient(
                    colors = listOf(
                        MaterialTheme.colorScheme.primary,
                        MaterialTheme.colorScheme.primary
                    )
                )
            )
        } else null
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        product.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    if (isAnnual) {
                        Spacer(modifier = Modifier.width(8.dp))
                        Surface(
                            shape = RoundedCornerShape(4.dp),
                            color = StatusGreen.copy(alpha = 0.2f)
                        ) {
                            Text(
                                "Best Value",
                                style = MaterialTheme.typography.labelSmall,
                                color = StatusGreen,
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp)
                            )
                        }
                    }
                }
                Text(
                    product.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Column(horizontalAlignment = Alignment.End) {
                Text(
                    SubscriptionManager.getFormattedPrice(product),
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    "per ${SubscriptionManager.getBillingPeriod(product)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Spacer(modifier = Modifier.width(12.dp))

            Icon(
                if (isSelected) Icons.Default.CheckCircle else Icons.Default.RadioButtonUnchecked,
                contentDescription = null,
                tint = if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun LegalDisclosuresSection(selectedProduct: ProductDetails?) {
    val context = LocalContext.current

    Card(
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
        ),
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Auto-renewal disclosure (REQUIRED by Google Play)
            selectedProduct?.let { product ->
                Text(
                    SubscriptionManager.getRenewalDisclosure(product),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Payment notice
            Text(
                "Payment will be charged to your Google Play account at the confirmation of purchase.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            // Links
            Row(
                horizontalArrangement = Arrangement.spacedBy(16.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                TextButton(
                    onClick = {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://mytravelstatus.com/terms"))
                        context.startActivity(intent)
                    },
                    contentPadding = PaddingValues(0.dp)
                ) {
                    Text("Terms of Service", style = MaterialTheme.typography.bodySmall)
                }

                Text("•", color = MaterialTheme.colorScheme.onSurfaceVariant)

                TextButton(
                    onClick = {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse("https://mytravelstatus.com/privacy"))
                        context.startActivity(intent)
                    },
                    contentPadding = PaddingValues(0.dp)
                ) {
                    Text("Privacy Policy", style = MaterialTheme.typography.bodySmall)
                }
            }
        }
    }
}

@Composable
private fun ActionsSection(
    isActive: Boolean,
    isLoading: Boolean,
    onRestoreClick: () -> Unit,
    onManageClick: () -> Unit
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        // Restore Purchases (REQUIRED by Google Play)
        TextButton(
            onClick = onRestoreClick,
            enabled = !isLoading
        ) {
            Icon(Icons.Default.Refresh, contentDescription = null)
            Spacer(modifier = Modifier.width(8.dp))
            Text("Restore Purchases")
        }

        // Manage Subscription
        if (isActive) {
            TextButton(onClick = onManageClick) {
                Icon(Icons.Default.Settings, contentDescription = null)
                Spacer(modifier = Modifier.width(8.dp))
                Text("Manage Subscription")
            }
        }
    }
}
