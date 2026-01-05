/**
 * SubscriptionManager.swift
 *
 * StoreKit 2 subscription management for MyTravelStatus.
 * Features:
 * - Auto-renewable subscriptions
 * - Restore purchases
 * - Manage subscription links
 * - Full pricing/renewal disclosures
 * - App Store review compliance
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation
import StoreKit

/// Subscription tier
enum SubscriptionTier: String, CaseIterable {
    case monthly = "com.mytravelstatus.premium.monthly"
    case annual = "com.mytravelstatus.premium.annual"

    var displayName: String {
        switch self {
        case .monthly: return "Monthly"
        case .annual: return "Annual"
        }
    }

    var period: String {
        switch self {
        case .monthly: return "month"
        case .annual: return "year"
        }
    }
}

/// Subscription status
enum SubscriptionStatus: Equatable {
    case notSubscribed
    case subscribed(tier: SubscriptionTier, expirationDate: Date, willRenew: Bool)
    case expired(tier: SubscriptionTier, expirationDate: Date)
    case inGracePeriod(tier: SubscriptionTier, expirationDate: Date)
    case inBillingRetry(tier: SubscriptionTier)

    var isActive: Bool {
        switch self {
        case .subscribed, .inGracePeriod, .inBillingRetry:
            return true
        case .notSubscribed, .expired:
            return false
        }
    }

    var displayText: String {
        switch self {
        case .notSubscribed:
            return "Not subscribed"
        case .subscribed(let tier, let date, let willRenew):
            let formatter = DateFormatter()
            formatter.dateStyle = .medium
            let renewal = willRenew ? "Renews" : "Expires"
            return "\(tier.displayName) • \(renewal) \(formatter.string(from: date))"
        case .expired(_, let date):
            let formatter = DateFormatter()
            formatter.dateStyle = .medium
            return "Expired on \(formatter.string(from: date))"
        case .inGracePeriod(_, let date):
            let formatter = DateFormatter()
            formatter.dateStyle = .medium
            return "Grace period until \(formatter.string(from: date))"
        case .inBillingRetry:
            return "Payment issue - update payment method"
        }
    }
}

@MainActor
class SubscriptionManager: ObservableObject {
    static let shared = SubscriptionManager()

    // MARK: - Published Properties

    @Published private(set) var products: [Product] = []
    @Published private(set) var purchasedProductIDs: Set<String> = []
    @Published private(set) var subscriptionStatus: SubscriptionStatus = .notSubscribed
    @Published private(set) var isLoading = false
    @Published var purchaseError: String?

    // MARK: - Private Properties

    private var updateListenerTask: Task<Void, Error>?

    // MARK: - Initialization

    private init() {
        // Start listening for transactions
        updateListenerTask = listenForTransactions()

        // Load products and check status
        Task {
            await loadProducts()
            await updateSubscriptionStatus()
        }
    }

    deinit {
        updateListenerTask?.cancel()
    }

    // MARK: - Public Methods

    /// Load available products from App Store
    func loadProducts() async {
        isLoading = true

        do {
            let productIDs = SubscriptionTier.allCases.map { $0.rawValue }
            products = try await Product.products(for: productIDs)
                .sorted { $0.price < $1.price }
        } catch {
            print("Failed to load products: \(error)")
        }

        isLoading = false
    }

    /// Purchase a subscription
    func purchase(_ product: Product) async throws {
        isLoading = true
        purchaseError = nil

        do {
            let result = try await product.purchase()

            switch result {
            case .success(let verification):
                let transaction = try checkVerified(verification)
                await transaction.finish()
                await updateSubscriptionStatus()

                // Track analytics
                AnalyticsManager.shared.track(.subscriptionStarted, properties: [
                    "tier": product.id
                ])

            case .userCancelled:
                break

            case .pending:
                purchaseError = "Purchase is pending approval"

            @unknown default:
                break
            }
        } catch {
            purchaseError = "Purchase failed: \(error.localizedDescription)"
            throw error
        }

        isLoading = false
    }

    /// Restore purchases
    func restorePurchases() async throws {
        isLoading = true
        purchaseError = nil

        do {
            try await AppStore.sync()
            await updateSubscriptionStatus()

            AnalyticsManager.shared.track(.subscriptionRestored)
        } catch {
            purchaseError = "Failed to restore purchases: \(error.localizedDescription)"
            throw error
        }

        isLoading = false
    }

    /// Get URL to manage subscription in App Store
    func getManageSubscriptionURL() -> URL? {
        URL(string: "https://apps.apple.com/account/subscriptions")
    }

    /// Open subscription management in App Store
    func openManageSubscription() async {
        guard let scene = UIApplication.shared.connectedScenes.first as? UIWindowScene else { return }

        do {
            try await AppStore.showManageSubscriptions(in: scene)
        } catch {
            // Fall back to URL
            if let url = getManageSubscriptionURL() {
                await UIApplication.shared.open(url)
            }
        }
    }

    /// Check if user has active subscription
    var hasActiveSubscription: Bool {
        subscriptionStatus.isActive
    }

    /// Get formatted price for a product
    func formattedPrice(for product: Product) -> String {
        product.displayPrice
    }

    /// Get renewal disclosure text (required by App Store)
    func renewalDisclosure(for product: Product) -> String {
        let price = product.displayPrice
        let period = product.subscription?.subscriptionPeriod.displayUnit ?? "period"

        return """
        Subscription automatically renews at \(price)/\(period) unless canceled at least 24 hours before the end of the current period. \
        Your account will be charged for renewal within 24 hours prior to the end of the current period. \
        You can manage and cancel your subscription in your App Store account settings after purchase.
        """
    }

    // MARK: - Private Methods

    private func listenForTransactions() -> Task<Void, Error> {
        Task.detached {
            for await result in Transaction.updates {
                do {
                    let transaction = try self.checkVerified(result)
                    await self.updateSubscriptionStatus()
                    await transaction.finish()
                } catch {
                    print("Transaction verification failed: \(error)")
                }
            }
        }
    }

    private func checkVerified<T>(_ result: VerificationResult<T>) throws -> T {
        switch result {
        case .unverified:
            throw StoreError.failedVerification
        case .verified(let safe):
            return safe
        }
    }

    func updateSubscriptionStatus() async {
        var newStatus: SubscriptionStatus = .notSubscribed

        for await result in Transaction.currentEntitlements {
            do {
                let transaction = try checkVerified(result)

                if transaction.productType == .autoRenewable {
                    purchasedProductIDs.insert(transaction.productID)

                    if let tier = SubscriptionTier(rawValue: transaction.productID) {
                        // Check subscription status
                        if let expirationDate = transaction.expirationDate {
                            if transaction.revocationDate != nil {
                                // Subscription was revoked
                                newStatus = .expired(tier: tier, expirationDate: expirationDate)
                            } else if expirationDate > Date() {
                                // Active subscription
                                let status = try? await transaction.subscriptionStatus

                                // Check for grace period or billing retry
                                if let renewalState = status?.first?.state {
                                    switch renewalState {
                                    case .inGracePeriod:
                                        newStatus = .inGracePeriod(tier: tier, expirationDate: expirationDate)
                                    case .inBillingRetryPeriod:
                                        newStatus = .inBillingRetry(tier: tier)
                                    default:
                                        let willRenew = status?.first?.renewalInfo != nil
                                        newStatus = .subscribed(tier: tier, expirationDate: expirationDate, willRenew: willRenew)
                                    }
                                } else {
                                    newStatus = .subscribed(tier: tier, expirationDate: expirationDate, willRenew: true)
                                }
                            } else {
                                newStatus = .expired(tier: tier, expirationDate: expirationDate)
                            }
                        }
                    }
                }
            } catch {
                print("Transaction error: \(error)")
            }
        }

        subscriptionStatus = newStatus
    }
}

// MARK: - Store Errors

enum StoreError: Error, LocalizedError {
    case failedVerification
    case productNotFound
    case purchaseFailed

    var errorDescription: String? {
        switch self {
        case .failedVerification:
            return "Transaction verification failed"
        case .productNotFound:
            return "Product not found"
        case .purchaseFailed:
            return "Purchase failed"
        }
    }
}

// MARK: - Extensions

extension Product.SubscriptionPeriod {
    var displayUnit: String {
        switch unit {
        case .day: return value == 1 ? "day" : "\(value) days"
        case .week: return value == 1 ? "week" : "\(value) weeks"
        case .month: return value == 1 ? "month" : "\(value) months"
        case .year: return value == 1 ? "year" : "\(value) years"
        @unknown default: return "period"
        }
    }
}

// MARK: - Demo Mode Support

extension SubscriptionManager {
    /// Check if we're in demo mode (for App Store review)
    var isDemoMode: Bool {
        PrivacySettings.shared.demoModeEnabled
    }

    /// Check if user should have premium features (subscription OR demo mode)
    var hasPremiumAccess: Bool {
        hasActiveSubscription || isDemoMode
    }
}
