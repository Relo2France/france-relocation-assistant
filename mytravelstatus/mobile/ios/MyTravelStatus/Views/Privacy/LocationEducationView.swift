/**
 * LocationEducationView.swift
 *
 * Educational screen shown before requesting background location permission.
 * Explains what data is collected, how it's used, and provides alternatives.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI
import CoreLocation

struct LocationEducationView: View {
    @Environment(\.dismiss) private var dismiss
    @EnvironmentObject private var privacySettings: PrivacySettings
    @ObservedObject private var locationManager = BackgroundLocationManager.shared

    @State private var showingPermissionRequest = false
    @State private var currentPage = 0

    let onComplete: (Bool) -> Void // true = enabled background, false = declined

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                // Page indicator
                HStack(spacing: 8) {
                    ForEach(0..<3) { index in
                        Circle()
                            .fill(currentPage == index ? Color.blue : Color.gray.opacity(0.3))
                            .frame(width: 8, height: 8)
                    }
                }
                .padding(.top)

                TabView(selection: $currentPage) {
                    // Page 1: What we track
                    educationPage1.tag(0)

                    // Page 2: How it works
                    educationPage2.tag(1)

                    // Page 3: Your choices
                    educationPage3.tag(2)
                }
                .tabViewStyle(.page(indexDisplayMode: .never))

                // Bottom buttons
                VStack(spacing: 12) {
                    if currentPage < 2 {
                        Button {
                            withAnimation {
                                currentPage += 1
                            }
                        } label: {
                            Text("Next")
                                .font(.headline)
                                .frame(maxWidth: .infinity)
                                .padding()
                                .background(Color.blue)
                                .foregroundColor(.white)
                                .cornerRadius(12)
                        }
                    } else {
                        // Final page - show choices
                        Button {
                            enableBackgroundLocation()
                        } label: {
                            Label("Enable Background Tracking", systemImage: "location.fill")
                                .font(.headline)
                                .frame(maxWidth: .infinity)
                                .padding()
                                .background(Color.blue)
                                .foregroundColor(.white)
                                .cornerRadius(12)
                        }

                        Button {
                            declineBackgroundLocation()
                        } label: {
                            Text("No Thanks, I'll Check In Manually")
                                .font(.subheadline)
                                .foregroundColor(.secondary)
                        }
                        .padding(.top, 4)
                    }
                }
                .padding(.horizontal)
                .padding(.bottom, 32)
            }
            .navigationTitle("Location Tracking")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Skip") {
                        declineBackgroundLocation()
                    }
                    .foregroundColor(.secondary)
                }
            }
        }
    }

    // MARK: - Education Pages

    private var educationPage1: some View {
        ScrollView {
            VStack(spacing: 24) {
                Image(systemName: "location.circle.fill")
                    .font(.system(size: 80))
                    .foregroundColor(.blue)
                    .padding(.top, 40)

                Text("Country Detection Only")
                    .font(.title2)
                    .fontWeight(.bold)

                VStack(alignment: .leading, spacing: 16) {
                    EducationRow(
                        icon: "checkmark.circle.fill",
                        iconColor: .green,
                        title: "We store",
                        description: "The country you're in (e.g., \"France\")"
                    )

                    EducationRow(
                        icon: "xmark.circle.fill",
                        iconColor: .red,
                        title: "We never store",
                        description: "Your exact address, street, GPS coordinates, or movement history"
                    )

                    EducationRow(
                        icon: "clock.fill",
                        iconColor: .blue,
                        title: "When we check",
                        description: "Only 3 times per day (8 AM, 2 PM, 8 PM) - not continuous tracking"
                    )
                }
                .padding(.horizontal)

                Spacer()
            }
            .padding()
        }
    }

    private var educationPage2: some View {
        ScrollView {
            VStack(spacing: 24) {
                Image(systemName: "shield.checkered")
                    .font(.system(size: 80))
                    .foregroundColor(.green)
                    .padding(.top, 40)

                Text("Your Privacy Is Protected")
                    .font(.title2)
                    .fontWeight(.bold)

                VStack(alignment: .leading, spacing: 16) {
                    EducationRow(
                        icon: "eye.slash.fill",
                        iconColor: .purple,
                        title: "Never shared",
                        description: "Your location data is never sold or shared with third parties"
                    )

                    EducationRow(
                        icon: "icloud.and.arrow.up.fill",
                        iconColor: .blue,
                        title: "Secure sync",
                        description: "Only country names sync to your account - encrypted in transit"
                    )

                    EducationRow(
                        icon: "trash.fill",
                        iconColor: .orange,
                        title: "Deletable anytime",
                        description: "You can export and delete all your data at any time"
                    )

                    EducationRow(
                        icon: "gearshape.fill",
                        iconColor: .gray,
                        title: "Control",
                        description: "Disable background tracking anytime in Settings"
                    )
                }
                .padding(.horizontal)

                Spacer()
            }
            .padding()
        }
    }

    private var educationPage3: some View {
        ScrollView {
            VStack(spacing: 24) {
                Image(systemName: "hand.raised.fill")
                    .font(.system(size: 80))
                    .foregroundColor(.orange)
                    .padding(.top, 40)

                Text("You Have a Choice")
                    .font(.title2)
                    .fontWeight(.bold)

                VStack(alignment: .leading, spacing: 20) {
                    ChoiceCard(
                        icon: "location.fill",
                        iconColor: .blue,
                        title: "Automatic Tracking",
                        description: "Enable background location for automatic country detection. The app checks your location 3 times daily to record which country you're in.",
                        isRecommended: true
                    )

                    Text("OR")
                        .font(.caption)
                        .foregroundColor(.secondary)
                        .frame(maxWidth: .infinity)

                    ChoiceCard(
                        icon: "hand.tap.fill",
                        iconColor: .green,
                        title: "Manual Check-In",
                        description: "Open the app and tap \"Check In\" when you want to record your location. You can also add trips manually.",
                        isRecommended: false
                    )
                }
                .padding(.horizontal)

                Text("The app works fully either way. You can change this anytime in Settings.")
                    .font(.caption)
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                    .padding()

                Spacer()
            }
            .padding()
        }
    }

    // MARK: - Actions

    private func enableBackgroundLocation() {
        privacySettings.backgroundLocationEducationShown = true
        privacySettings.backgroundLocationEnabled = true

        // Request always authorization
        locationManager.requestAlwaysPermission()

        // Wait a moment for the system dialog, then complete
        DispatchQueue.main.asyncAfter(deadline: .now() + 0.5) {
            onComplete(true)
            dismiss()
        }
    }

    private func declineBackgroundLocation() {
        privacySettings.backgroundLocationEducationShown = true
        privacySettings.backgroundLocationEnabled = false

        // Still request when-in-use for manual check-ins
        locationManager.requestPermission()

        onComplete(false)
        dismiss()
    }
}

// MARK: - Helper Views

private struct EducationRow: View {
    let icon: String
    let iconColor: Color
    let title: String
    let description: String

    var body: some View {
        HStack(alignment: .top, spacing: 12) {
            Image(systemName: icon)
                .font(.title3)
                .foregroundColor(iconColor)
                .frame(width: 28)

            VStack(alignment: .leading, spacing: 4) {
                Text(title)
                    .font(.subheadline)
                    .fontWeight(.semibold)
                Text(description)
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }
        }
    }
}

private struct ChoiceCard: View {
    let icon: String
    let iconColor: Color
    let title: String
    let description: String
    let isRecommended: Bool

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Image(systemName: icon)
                    .font(.title2)
                    .foregroundColor(iconColor)

                Text(title)
                    .font(.headline)

                Spacer()

                if isRecommended {
                    Text("Recommended")
                        .font(.caption)
                        .padding(.horizontal, 8)
                        .padding(.vertical, 4)
                        .background(Color.blue.opacity(0.1))
                        .foregroundColor(.blue)
                        .cornerRadius(4)
                }
            }

            Text(description)
                .font(.subheadline)
                .foregroundColor(.secondary)
        }
        .padding()
        .background(Color(.systemGray6))
        .cornerRadius(12)
    }
}

// MARK: - Preview

#Preview {
    LocationEducationView { enabled in
        print("Background location: \(enabled)")
    }
    .environmentObject(PrivacySettings.shared)
}
