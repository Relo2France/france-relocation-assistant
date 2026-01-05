/**
 * SubscriptionManager.kt
 *
 * Google Play Billing subscription management.
 * Implements auto-renewable subscriptions with:
 * - Full pricing display with period
 * - Auto-renewal terms disclosure
 * - Restore purchases
 * - Manage subscription link
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

package com.mytravelstatus.app.service

import android.app.Activity
import android.content.Context
import android.util.Log
import com.android.billingclient.api.*
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Subscription product IDs
 */
object SubscriptionProducts {
    const val PREMIUM_MONTHLY = "com.mytravelstatus.premium.monthly"
    const val PREMIUM_ANNUAL = "com.mytravelstatus.premium.annual"

    val ALL_PRODUCTS = listOf(PREMIUM_MONTHLY, PREMIUM_ANNUAL)
}

/**
 * Subscription status
 */
data class SubscriptionStatus(
    val isActive: Boolean = false,
    val productId: String? = null,
    val expiryDate: Long? = null,
    val isAutoRenewing: Boolean = false,
    val displayText: String = "Free Plan"
)

object SubscriptionManager : PurchasesUpdatedListener {
    private const val TAG = "SubscriptionManager"

    private var billingClient: BillingClient? = null
    private val coroutineScope = CoroutineScope(Dispatchers.Main + SupervisorJob())

    // StateFlows for reactive UI updates
    private val _products = MutableStateFlow<List<ProductDetails>>(emptyList())
    val products: StateFlow<List<ProductDetails>> = _products.asStateFlow()

    private val _subscriptionStatus = MutableStateFlow(SubscriptionStatus())
    val subscriptionStatus: StateFlow<SubscriptionStatus> = _subscriptionStatus.asStateFlow()

    private val _isLoading = MutableStateFlow(false)
    val isLoading: StateFlow<Boolean> = _isLoading.asStateFlow()

    private val _purchaseError = MutableStateFlow<String?>(null)
    val purchaseError: StateFlow<String?> = _purchaseError.asStateFlow()

    /**
     * Initialize the billing client
     */
    fun initialize(context: Context) {
        if (billingClient != null) return

        billingClient = BillingClient.newBuilder(context)
            .setListener(this)
            .enablePendingPurchases()
            .build()

        startConnection()
    }

    /**
     * Start billing client connection
     */
    private fun startConnection() {
        billingClient?.startConnection(object : BillingClientStateListener {
            override fun onBillingSetupFinished(result: BillingResult) {
                if (result.responseCode == BillingClient.BillingResponseCode.OK) {
                    Log.d(TAG, "Billing client connected")
                    coroutineScope.launch {
                        queryProducts()
                        queryPurchases()
                    }
                } else {
                    Log.e(TAG, "Billing setup failed: ${result.debugMessage}")
                }
            }

            override fun onBillingServiceDisconnected() {
                Log.d(TAG, "Billing client disconnected")
                // Try to reconnect
                coroutineScope.launch {
                    delay(5000)
                    startConnection()
                }
            }
        })
    }

    /**
     * Query available subscription products
     */
    private suspend fun queryProducts() {
        val client = billingClient ?: return

        val productList = SubscriptionProducts.ALL_PRODUCTS.map { productId ->
            QueryProductDetailsParams.Product.newBuilder()
                .setProductId(productId)
                .setProductType(BillingClient.ProductType.SUBS)
                .build()
        }

        val params = QueryProductDetailsParams.newBuilder()
            .setProductList(productList)
            .build()

        val result = client.queryProductDetails(params)

        if (result.billingResult.responseCode == BillingClient.BillingResponseCode.OK) {
            _products.value = result.productDetailsList ?: emptyList()
            Log.d(TAG, "Products loaded: ${_products.value.size}")
        } else {
            Log.e(TAG, "Failed to query products: ${result.billingResult.debugMessage}")
        }
    }

    /**
     * Query existing purchases to check subscription status
     */
    private suspend fun queryPurchases() {
        val client = billingClient ?: return

        val params = QueryPurchasesParams.newBuilder()
            .setProductType(BillingClient.ProductType.SUBS)
            .build()

        val result = client.queryPurchasesAsync(params)

        if (result.billingResult.responseCode == BillingClient.BillingResponseCode.OK) {
            val activePurchase = result.purchasesList.find { purchase ->
                purchase.purchaseState == Purchase.PurchaseState.PURCHASED
            }

            if (activePurchase != null) {
                updateSubscriptionStatus(activePurchase)
            } else {
                _subscriptionStatus.value = SubscriptionStatus()
            }
        }
    }

    /**
     * Launch purchase flow for a product
     */
    fun purchase(activity: Activity, productDetails: ProductDetails) {
        val client = billingClient
        if (client == null) {
            _purchaseError.value = "Billing not initialized"
            return
        }

        _isLoading.value = true
        _purchaseError.value = null

        // Get the subscription offer
        val offerToken = productDetails.subscriptionOfferDetails?.firstOrNull()?.offerToken
        if (offerToken == null) {
            _purchaseError.value = "No subscription offer available"
            _isLoading.value = false
            return
        }

        val productDetailsParams = BillingFlowParams.ProductDetailsParams.newBuilder()
            .setProductDetails(productDetails)
            .setOfferToken(offerToken)
            .build()

        val params = BillingFlowParams.newBuilder()
            .setProductDetailsParamsList(listOf(productDetailsParams))
            .build()

        val result = client.launchBillingFlow(activity, params)

        if (result.responseCode != BillingClient.BillingResponseCode.OK) {
            _purchaseError.value = "Failed to launch purchase: ${result.debugMessage}"
            _isLoading.value = false
        }

        AnalyticsManager.track(AnalyticsEvent.SUBSCRIPTION_PURCHASE_STARTED, mapOf(
            "subscription_id" to productDetails.productId
        ))
    }

    /**
     * Restore purchases
     */
    fun restorePurchases() {
        coroutineScope.launch {
            _isLoading.value = true
            queryPurchases()
            _isLoading.value = false
            AnalyticsManager.track(AnalyticsEvent.SUBSCRIPTION_RESTORED)
        }
    }

    /**
     * Open subscription management in Play Store
     */
    fun openManageSubscription(context: Context) {
        val productId = _subscriptionStatus.value.productId ?: return

        val url = "https://play.google.com/store/account/subscriptions?sku=$productId&package=${context.packageName}"
        val intent = android.content.Intent(android.content.Intent.ACTION_VIEW)
        intent.data = android.net.Uri.parse(url)
        intent.flags = android.content.Intent.FLAG_ACTIVITY_NEW_TASK
        context.startActivity(intent)
    }

    /**
     * Get the auto-renewal disclosure text for a product (REQUIRED by Google Play)
     */
    fun getRenewalDisclosure(productDetails: ProductDetails): String {
        val offer = productDetails.subscriptionOfferDetails?.firstOrNull()
        val pricingPhase = offer?.pricingPhases?.pricingPhaseList?.firstOrNull()

        val price = pricingPhase?.formattedPrice ?: "N/A"
        val period = when (pricingPhase?.billingPeriod) {
            "P1M" -> "month"
            "P1Y" -> "year"
            "P1W" -> "week"
            else -> "period"
        }

        return """
            Subscription automatically renews at $price/$period unless canceled at least 24 hours before the end of the current period. Your Google Play account will be charged for renewal within 24 hours prior to the end of the current period. You can manage and cancel your subscription in your Google Play Store account settings after purchase.
        """.trimIndent()
    }

    /**
     * Get formatted price for display
     */
    fun getFormattedPrice(productDetails: ProductDetails): String {
        return productDetails.subscriptionOfferDetails?.firstOrNull()
            ?.pricingPhases?.pricingPhaseList?.firstOrNull()
            ?.formattedPrice ?: "N/A"
    }

    /**
     * Get billing period display text
     */
    fun getBillingPeriod(productDetails: ProductDetails): String {
        val period = productDetails.subscriptionOfferDetails?.firstOrNull()
            ?.pricingPhases?.pricingPhaseList?.firstOrNull()
            ?.billingPeriod

        return when (period) {
            "P1M" -> "month"
            "P1Y" -> "year"
            "P1W" -> "week"
            else -> "period"
        }
    }

    /**
     * Check if user has active premium subscription
     */
    fun isPremium(): Boolean {
        // In demo mode, always return true
        if (DemoModeService.isDemoMode.value) {
            return true
        }
        return _subscriptionStatus.value.isActive
    }

    // MARK: - PurchasesUpdatedListener

    override fun onPurchasesUpdated(result: BillingResult, purchases: List<Purchase>?) {
        _isLoading.value = false

        when (result.responseCode) {
            BillingClient.BillingResponseCode.OK -> {
                purchases?.forEach { purchase ->
                    handlePurchase(purchase)
                }
            }
            BillingClient.BillingResponseCode.USER_CANCELED -> {
                Log.d(TAG, "Purchase canceled by user")
                _purchaseError.value = null // User intentionally canceled
            }
            else -> {
                Log.e(TAG, "Purchase failed: ${result.debugMessage}")
                _purchaseError.value = "Purchase failed: ${result.debugMessage}"
                AnalyticsManager.track(AnalyticsEvent.SUBSCRIPTION_PURCHASE_FAILED)
            }
        }
    }

    // MARK: - Private Methods

    private fun handlePurchase(purchase: Purchase) {
        if (purchase.purchaseState == Purchase.PurchaseState.PURCHASED) {
            // Acknowledge the purchase if not already acknowledged
            if (!purchase.isAcknowledged) {
                coroutineScope.launch {
                    acknowledgePurchase(purchase)
                }
            }

            updateSubscriptionStatus(purchase)
            AnalyticsManager.track(AnalyticsEvent.SUBSCRIPTION_PURCHASE_COMPLETED, mapOf(
                "subscription_id" to (purchase.products.firstOrNull() ?: "unknown")
            ))
        }
    }

    private suspend fun acknowledgePurchase(purchase: Purchase) {
        val client = billingClient ?: return

        val params = AcknowledgePurchaseParams.newBuilder()
            .setPurchaseToken(purchase.purchaseToken)
            .build()

        val result = client.acknowledgePurchase(params)
        if (result.responseCode == BillingClient.BillingResponseCode.OK) {
            Log.d(TAG, "Purchase acknowledged")
        } else {
            Log.e(TAG, "Failed to acknowledge purchase: ${result.debugMessage}")
        }
    }

    private fun updateSubscriptionStatus(purchase: Purchase) {
        val productId = purchase.products.firstOrNull()
        val product = _products.value.find { it.productId == productId }

        val periodText = product?.let { getBillingPeriod(it) } ?: "subscription"

        _subscriptionStatus.value = SubscriptionStatus(
            isActive = true,
            productId = productId,
            expiryDate = null, // Would need to query from server
            isAutoRenewing = purchase.isAutoRenewing,
            displayText = "Premium ($periodText)"
        )
    }

    /**
     * Clean up resources
     */
    fun destroy() {
        billingClient?.endConnection()
        billingClient = null
        coroutineScope.cancel()
    }
}
