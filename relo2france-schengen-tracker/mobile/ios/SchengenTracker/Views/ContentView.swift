/**
 * ContentView.swift
 *
 * Main navigation container for the MyTravelStatus app.
 * Track your visa, tax, and residency days worldwide.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI

struct ContentView: View {
    @EnvironmentObject var appState: AppState
    @EnvironmentObject var syncManager: SyncManager

    @State private var selectedTab = 0

    var body: some View {
        Group {
            if appState.isLoading {
                // Loading screen
                loadingView
            } else if appState.showForceUpdate {
                // Force update required
                forceUpdateView
            } else if appState.showMaintenance {
                // Maintenance mode
                maintenanceView
            } else if !appState.isAuthenticated {
                // Login screen
                LoginView()
            } else {
                // Main app
                mainTabView
            }
        }
    }

    // MARK: - Loading View

    private var loadingView: some View {
        VStack(spacing: 20) {
            Image(systemName: "globe.europe.africa.fill")
                .font(.system(size: 80))
                .foregroundColor(.blue)

            Text("MyTravelStatus")
                .font(.title.bold())

            Text("Track your visa, tax, and residency days worldwide")
                .font(.caption)
                .foregroundColor(.secondary)
                .multilineTextAlignment(.center)
                .padding(.horizontal)

            ProgressView()
                .scaleEffect(1.2)
        }
    }

    // MARK: - Force Update View

    private var forceUpdateView: some View {
        VStack(spacing: 24) {
            Image(systemName: "arrow.down.circle.fill")
                .font(.system(size: 80))
                .foregroundColor(.orange)

            Text("Update Required")
                .font(.title.bold())

            Text("Please update to the latest version to continue using MyTravelStatus.")
                .multilineTextAlignment(.center)
                .foregroundColor(.secondary)
                .padding(.horizontal)

            if let updateUrl = appState.appStatus?.updateUrl,
               let url = URL(string: updateUrl) {
                Link("Update Now", destination: url)
                    .buttonStyle(.borderedProminent)
            }
        }
        .padding()
    }

    // MARK: - Maintenance View

    private var maintenanceView: some View {
        VStack(spacing: 24) {
            Image(systemName: "wrench.and.screwdriver.fill")
                .font(.system(size: 80))
                .foregroundColor(.gray)

            Text("Under Maintenance")
                .font(.title.bold())

            if let message = appState.appStatus?.maintenanceMessage {
                Text(message)
                    .multilineTextAlignment(.center)
                    .foregroundColor(.secondary)
                    .padding(.horizontal)
            }

            Button("Retry") {
                Task {
                    await appState.checkAppStatus()
                }
            }
            .buttonStyle(.bordered)
        }
        .padding()
    }

    // MARK: - Main Tab View

    private var mainTabView: some View {
        TabView(selection: $selectedTab) {
            // Home / Dashboard
            NavigationStack {
                HomeView()
            }
            .tabItem {
                Label("Home", systemImage: "house.fill")
            }
            .tag(0)

            // Trips
            NavigationStack {
                TripsView()
            }
            .tabItem {
                Label("Trips", systemImage: "map.fill")
            }
            .tag(1)

            // Passport Control (quick access)
            NavigationStack {
                PassportControlView()
            }
            .tabItem {
                Label("Passport", systemImage: "person.text.rectangle.fill")
            }
            .tag(2)

            // Settings
            NavigationStack {
                SettingsView()
            }
            .tabItem {
                Label("Settings", systemImage: "gearshape.fill")
            }
            .tag(3)
        }
        .overlay(alignment: .top) {
            // Sync status banner
            if syncManager.pendingChangesCount > 0 && !syncManager.isOnline {
                offlineBanner
            }
        }
    }

    // MARK: - Offline Banner

    private var offlineBanner: some View {
        HStack {
            Image(systemName: "wifi.slash")
            Text("\(syncManager.pendingChangesCount) changes pending sync")
            Spacer()
        }
        .padding(.horizontal)
        .padding(.vertical, 8)
        .background(Color.orange)
        .foregroundColor(.white)
        .font(.caption.bold())
    }
}

// MARK: - Placeholder Views

struct LoginView: View {
    @EnvironmentObject var appState: AppState

    @State private var email = ""
    @State private var password = ""
    @State private var isLoading = false
    @State private var error: String?

    var body: some View {
        NavigationStack {
            VStack(spacing: 32) {
                // Logo
                VStack(spacing: 12) {
                    Image(systemName: "globe.europe.africa.fill")
                        .font(.system(size: 80))
                        .foregroundColor(.blue)

                    Text("MyTravelStatus")
                        .font(.title.bold())

                    Text("Track your visa, tax, and residency days worldwide")
                        .foregroundColor(.secondary)
                        .multilineTextAlignment(.center)
                        .padding(.horizontal)
                }
                .padding(.top, 40)

                // Form
                VStack(spacing: 16) {
                    TextField("Email", text: $email)
                        .textContentType(.emailAddress)
                        .keyboardType(.emailAddress)
                        .autocapitalization(.none)
                        .textFieldStyle(.roundedBorder)

                    SecureField("Password", text: $password)
                        .textContentType(.password)
                        .textFieldStyle(.roundedBorder)

                    if let error = error {
                        Text(error)
                            .foregroundColor(.red)
                            .font(.caption)
                    }

                    Button {
                        login()
                    } label: {
                        if isLoading {
                            ProgressView()
                                .progressViewStyle(.circular)
                        } else {
                            Text("Sign In")
                                .frame(maxWidth: .infinity)
                        }
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(email.isEmpty || password.isEmpty || isLoading)
                }
                .padding(.horizontal, 32)

                Spacer()
            }
        }
    }

    private func login() {
        isLoading = true
        error = nil

        Task {
            do {
                try await appState.login(email: email, password: password)
            } catch {
                self.error = error.localizedDescription
            }
            isLoading = false
        }
    }
}

struct HomeView: View {
    @EnvironmentObject var locationManager: BackgroundLocationManager
    @EnvironmentObject var syncManager: SyncManager

    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                // Status Card
                StatusCard()

                // Quick Actions
                HStack(spacing: 16) {
                    QuickActionButton(title: "Check In", icon: "location.fill") {
                        Task {
                            try? await locationManager.manualCheckIn()
                        }
                    }

                    QuickActionButton(title: "Add Trip", icon: "plus.circle.fill") {
                        // TODO: Navigate to add trip
                    }
                }
                .padding(.horizontal)

                // Recent Trips
                RecentTripsCard()
            }
            .padding(.vertical)
        }
        .navigationTitle("Dashboard")
        .refreshable {
            try? await syncManager.sync()
        }
    }
}

struct StatusCard: View {
    // Support for multiple jurisdictions - Schengen shown as example
    var jurisdictionName = "Schengen (90/180)"
    var jurisdictionIcon = "🇪🇺"

    var body: some View {
        VStack(spacing: 16) {
            HStack {
                Text(jurisdictionIcon)
                    .font(.title)
                Text(jurisdictionName.uppercased())
                    .font(.headline)
                Spacer()
                Text("✓ Compliant")
                    .foregroundColor(.green)
                    .font(.subheadline.bold())
            }

            HStack {
                VStack(alignment: .leading) {
                    Text("42")
                        .font(.system(size: 48, weight: .bold))
                    Text("days used")
                        .foregroundColor(.secondary)
                }

                Spacer()

                VStack(alignment: .trailing) {
                    Text("48")
                        .font(.system(size: 48, weight: .bold))
                        .foregroundColor(.green)
                    Text("days left")
                        .foregroundColor(.secondary)
                }
            }

            // Progress bar
            GeometryReader { geometry in
                ZStack(alignment: .leading) {
                    RoundedRectangle(cornerRadius: 4)
                        .fill(Color.gray.opacity(0.2))
                        .frame(height: 8)

                    RoundedRectangle(cornerRadius: 4)
                        .fill(Color.green)
                        .frame(width: geometry.size.width * 0.47, height: 8)
                }
            }
            .frame(height: 8)
        }
        .padding()
        .background(Color(.secondarySystemBackground))
        .cornerRadius(16)
        .padding(.horizontal)
    }
}

struct QuickActionButton: View {
    let title: String
    let icon: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            VStack(spacing: 8) {
                Image(systemName: icon)
                    .font(.title2)
                Text(title)
                    .font(.caption.bold())
            }
            .frame(maxWidth: .infinity)
            .padding()
            .background(Color(.secondarySystemBackground))
            .cornerRadius(12)
        }
        .buttonStyle(.plain)
    }
}

struct RecentTripsCard: View {
    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Recent Trips")
                .font(.headline)
                .padding(.horizontal)

            VStack(spacing: 8) {
                TripRow(flag: "🇫🇷", country: "France", dates: "Nov 15 - Present", days: 44)
                TripRow(flag: "🇪🇸", country: "Spain", dates: "Oct 1-8", days: 8)
                TripRow(flag: "🇮🇹", country: "Italy", dates: "Sep 10-15", days: 6)
            }
            .padding(.horizontal)
        }
    }
}

struct TripRow: View {
    let flag: String
    let country: String
    let dates: String
    let days: Int

    var body: some View {
        HStack {
            Text(flag)
                .font(.title2)

            VStack(alignment: .leading) {
                Text(country)
                    .font(.subheadline.bold())
                Text(dates)
                    .font(.caption)
                    .foregroundColor(.secondary)
            }

            Spacer()

            Text("\(days)d")
                .font(.subheadline.bold())
                .foregroundColor(.secondary)
        }
        .padding()
        .background(Color(.secondarySystemBackground))
        .cornerRadius(12)
    }
}

struct TripsView: View {
    var body: some View {
        List {
            Text("Trips list coming soon...")
        }
        .navigationTitle("Trips")
    }
}

struct SettingsView: View {
    @EnvironmentObject var appState: AppState
    @EnvironmentObject var locationManager: BackgroundLocationManager

    var body: some View {
        List {
            Section("Location Tracking") {
                Toggle("Background GPS", isOn: $locationManager.isTrackingEnabled)

                HStack {
                    Text("Permission")
                    Spacer()
                    Text(permissionStatus)
                        .foregroundColor(.secondary)
                }
            }

            Section("Account") {
                Button("Sign Out", role: .destructive) {
                    appState.logout()
                }
            }

            Section("About") {
                HStack {
                    Text("Version")
                    Spacer()
                    Text("1.0.0")
                        .foregroundColor(.secondary)
                }
            }
        }
        .navigationTitle("Settings")
    }

    private var permissionStatus: String {
        switch locationManager.authorizationStatus {
        case .authorizedAlways: return "Always"
        case .authorizedWhenInUse: return "When In Use"
        case .denied: return "Denied"
        case .restricted: return "Restricted"
        case .notDetermined: return "Not Set"
        @unknown default: return "Unknown"
        }
    }
}

// MARK: - Preview

#Preview {
    ContentView()
        .environmentObject(AppState())
        .environmentObject(BackgroundLocationManager.shared)
        .environmentObject(SyncManager.shared)
}
