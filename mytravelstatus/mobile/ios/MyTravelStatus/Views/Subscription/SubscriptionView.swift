/**
 * SubscriptionView.swift
 *
 * Subscription purchase and management view.
 * Includes all required App Store disclosures:
 * - Full pricing with period
 * - Auto-renewal terms
 * - Restore purchases
 * - Manage subscription link
 * - Links to Terms & Privacy Policy
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI
import StoreKit

struct SubscriptionView: View {
    @Environment(\.dismiss) private var dismiss
    @ObservedObject private var subscriptionManager = SubscriptionManager.shared

    @State private var selectedProduct: Product?
    @State private var showingError = false
    @State private var showingRestoreSuccess = false

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(spacing: 24) {
                    // Header
                    headerSection

                    // Current status (if subscribed)
                    if subscriptionManager.subscriptionStatus.isActive {
                        currentSubscriptionSection
                    }

                    // Features list
                    featuresSection

                    // Products
                    if !subscriptionManager.subscriptionStatus.isActive {
                        productsSection
                    }

                    // Legal disclosures (REQUIRED)
                    legalDisclosuresSection

                    // Restore & Manage
                    actionsSection
                }
                .padding()
            }
            .navigationTitle("Premium")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") {
                        dismiss()
                    }
                }
            }
            .alert("Error", isPresented: $showingError) {
                Button("OK", role: .cancel) {}
            } message: {
                Text(subscriptionManager.purchaseError ?? "An error occurred")
            }
            .alert("Restored", isPresented: $showingRestoreSuccess) {
                Button("OK", role: .cancel) {}
            } message: {
                Text("Your purchases have been restored successfully.")
            }
            .onAppear {
                AnalyticsManager.shared.trackScreen(.subscription)
            }
        }
    }

    // MARK: - Sections

    private var headerSection: some View {
        VStack(spacing: 12) {
            Image(systemName: "star.circle.fill")
                .font(.system(size: 60))
                .foregroundStyle(
                    LinearGradient(
                        colors: [.blue, .purple],
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    )
                )

            Text("MyTravelStatus Premium")
                .font(.title)
                .fontWeight(.bold)

            Text("Track your travel compliance with ease")
                .font(.subheadline)
                .foregroundColor(.secondary)
        }
        .padding(.top)
    }

    private var currentSubscriptionSection: some View {
        VStack(spacing: 12) {
            HStack {
                Image(systemName: "checkmark.seal.fill")
                    .foregroundColor(.green)
                Text("Active Subscription")
                    .fontWeight(.semibold)
            }

            Text(subscriptionManager.subscriptionStatus.displayText)
                .font(.subheadline)
                .foregroundColor(.secondary)

            Button {
                Task {
                    await subscriptionManager.openManageSubscription()
                }
            } label: {
                Text("Manage Subscription")
                    .font(.subheadline)
            }
        }
        .padding()
        .background(Color.green.opacity(0.1))
        .cornerRadius(12)
    }

    private var featuresSection: some View {
        VStack(alignment: .leading, spacing: 16) {
            Text("Premium Features")
                .font(.headline)

            FeatureRow(icon: "location.fill", title: "Background Location", description: "Automatic country detection 3x daily")
            FeatureRow(icon: "photo.stack.fill", title: "Photo Import", description: "Extract trips from photo GPS data")
            FeatureRow(icon: "calendar", title: "Calendar Sync", description: "Import trips from your calendar")
            FeatureRow(icon: "bell.fill", title: "Smart Alerts", description: "Get notified before hitting limits")
            FeatureRow(icon: "person.2.fill", title: "Family Tracking", description: "Track multiple travelers")
            FeatureRow(icon: "chart.bar.fill", title: "Analytics", description: "Detailed compliance reports")
            FeatureRow(icon: "arrow.triangle.2.circlepath", title: "Cloud Sync", description: "Sync across all devices")
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }

    private var productsSection: some View {
        VStack(spacing: 16) {
            if subscriptionManager.isLoading {
                ProgressView()
                    .padding()
            } else if subscriptionManager.products.isEmpty {
                Text("Unable to load subscription options")
                    .foregroundColor(.secondary)
            } else {
                ForEach(subscriptionManager.products, id: \.id) { product in
                    ProductCard(
                        product: product,
                        isSelected: selectedProduct?.id == product.id,
                        onSelect: { selectedProduct = product }
                    )
                }

                // Purchase button
                if let product = selectedProduct {
                    Button {
                        Task {
                            do {
                                try await subscriptionManager.purchase(product)
                            } catch {
                                showingError = true
                            }
                        }
                    } label: {
                        HStack {
                            if subscriptionManager.isLoading {
                                ProgressView()
                                    .tint(.white)
                            } else {
                                Text("Subscribe for \(product.displayPrice)/\(product.subscription?.subscriptionPeriod.displayUnit ?? "period")")
                            }
                        }
                        .font(.headline)
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.blue)
                        .foregroundColor(.white)
                        .cornerRadius(12)
                    }
                    .disabled(subscriptionManager.isLoading)
                }
            }
        }
    }

    private var legalDisclosuresSection: some View {
        VStack(alignment: .leading, spacing: 12) {
            // Auto-renewal disclosure (REQUIRED)
            if let product = selectedProduct {
                Text(subscriptionManager.renewalDisclosure(for: product))
                    .font(.caption)
                    .foregroundColor(.secondary)
            } else if let firstProduct = subscriptionManager.products.first {
                Text(subscriptionManager.renewalDisclosure(for: firstProduct))
                    .font(.caption)
                    .foregroundColor(.secondary)
            }

            // Payment will be charged notice
            Text("Payment will be charged to your Apple ID account at the confirmation of purchase.")
                .font(.caption)
                .foregroundColor(.secondary)

            // Links row
            HStack(spacing: 16) {
                Button {
                    if let url = URL(string: "https://mytravelstatus.com/terms") {
                        UIApplication.shared.open(url)
                    }
                } label: {
                    Text("Terms of Service")
                        .font(.caption)
                }

                Text("•")
                    .foregroundColor(.secondary)

                Button {
                    if let url = URL(string: "https://mytravelstatus.com/privacy") {
                        UIApplication.shared.open(url)
                    }
                } label: {
                    Text("Privacy Policy")
                        .font(.caption)
                }
            }
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }

    private var actionsSection: some View {
        VStack(spacing: 12) {
            // Restore Purchases (REQUIRED)
            Button {
                Task {
                    do {
                        try await subscriptionManager.restorePurchases()
                        showingRestoreSuccess = true
                    } catch {
                        showingError = true
                    }
                }
            } label: {
                HStack {
                    Image(systemName: "arrow.clockwise")
                    Text("Restore Purchases")
                }
                .font(.subheadline)
            }
            .disabled(subscriptionManager.isLoading)

            // Manage Subscription
            if subscriptionManager.subscriptionStatus.isActive {
                Button {
                    Task {
                        await subscriptionManager.openManageSubscription()
                    }
                } label: {
                    HStack {
                        Image(systemName: "gear")
                        Text("Manage Subscription")
                    }
                    .font(.subheadline)
                }
            }
        }
    }
}

// MARK: - Helper Views

private struct FeatureRow: View {
    let icon: String
    let title: String
    let description: String

    var body: some View {
        HStack(spacing: 12) {
            Image(systemName: icon)
                .font(.title3)
                .foregroundColor(.blue)
                .frame(width: 28)

            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.subheadline)
                    .fontWeight(.medium)
                Text(description)
                    .font(.caption)
                    .foregroundColor(.secondary)
            }
        }
    }
}

private struct ProductCard: View {
    let product: Product
    let isSelected: Bool
    let onSelect: () -> Void

    var body: some View {
        Button(action: onSelect) {
            HStack {
                VStack(alignment: .leading, spacing: 4) {
                    HStack {
                        Text(product.displayName)
                            .font(.headline)

                        if product.id.contains("annual") {
                            Text("Best Value")
                                .font(.caption)
                                .padding(.horizontal, 8)
                                .padding(.vertical, 2)
                                .background(Color.green.opacity(0.2))
                                .foregroundColor(.green)
                                .cornerRadius(4)
                        }
                    }

                    Text(product.description)
                        .font(.caption)
                        .foregroundColor(.secondary)
                }

                Spacer()

                VStack(alignment: .trailing) {
                    Text(product.displayPrice)
                        .font(.title2)
                        .fontWeight(.bold)

                    Text("per \(product.subscription?.subscriptionPeriod.displayUnit ?? "period")")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }

                Image(systemName: isSelected ? "checkmark.circle.fill" : "circle")
                    .foregroundColor(isSelected ? .blue : .gray)
                    .font(.title2)
            }
            .padding()
            .background(isSelected ? Color.blue.opacity(0.1) : Color(.systemGray6))
            .cornerRadius(12)
            .overlay(
                RoundedRectangle(cornerRadius: 12)
                    .stroke(isSelected ? Color.blue : Color.clear, lineWidth: 2)
            )
        }
        .buttonStyle(.plain)
    }
}

// MARK: - Preview

#Preview {
    SubscriptionView()
}
