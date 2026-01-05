/**
 * PrivacySettingsView.swift
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

import SwiftUI

struct PrivacySettingsView: View {
    @EnvironmentObject private var privacySettings: PrivacySettings
    @ObservedObject private var dataManager = DataManagementService.shared
    @ObservedObject private var locationManager = BackgroundLocationManager.shared

    @State private var showingExportSheet = false
    @State private var showingDeleteConfirmation = false
    @State private var showingDeleteFinalConfirmation = false
    @State private var showingLocationEducation = false
    @State private var exportResult: DataExportResult?
    @State private var deleteConfirmText = ""

    var body: some View {
        List {
            // Legal Disclaimer Section
            Section {
                disclaimerCard
            }

            // Location Privacy Section
            Section {
                locationPrivacyRow
            } header: {
                Text("Location")
            } footer: {
                Text("Background tracking checks your country 3 times daily. Only country names are stored—never precise coordinates.")
            }

            // Analytics Section
            Section {
                analyticsRow
                crashReportingRow
            } header: {
                Text("Analytics & Diagnostics")
            } footer: {
                Text("Anonymous usage data helps us improve the app. No personal or location data is included.")
            }

            // Data Management Section
            Section {
                exportDataRow
                clearLocalDataRow
                deleteAccountRow
            } header: {
                Text("Your Data")
            } footer: {
                Text("You can export a copy of all your data or permanently delete your account and all associated data.")
            }

            // Legal Links Section
            Section {
                legalLinksSection
            } header: {
                Text("Legal")
            }
        }
        .navigationTitle("Privacy & Data")
        .sheet(isPresented: $showingLocationEducation) {
            LocationEducationView { enabled in
                privacySettings.backgroundLocationEnabled = enabled
                if enabled {
                    locationManager.scheduleBackgroundTasks()
                }
            }
            .environmentObject(privacySettings)
        }
        .sheet(isPresented: $showingExportSheet) {
            if let result = exportResult {
                ExportResultView(result: result)
            }
        }
        .alert("Delete Account?", isPresented: $showingDeleteConfirmation) {
            Button("Continue", role: .destructive) {
                showingDeleteFinalConfirmation = true
            }
            Button("Cancel", role: .cancel) {}
        } message: {
            Text("This will permanently delete your account and all data from our servers. This action cannot be undone.")
        }
        .alert("Confirm Deletion", isPresented: $showingDeleteFinalConfirmation) {
            TextField("Type DELETE to confirm", text: $deleteConfirmText)
            Button("Delete Everything", role: .destructive) {
                if deleteConfirmText.uppercased() == "DELETE" {
                    Task {
                        try? await dataManager.deleteAccountAndData()
                    }
                }
            }
            .disabled(deleteConfirmText.uppercased() != "DELETE")
            Button("Cancel", role: .cancel) {
                deleteConfirmText = ""
            }
        } message: {
            Text("Type DELETE to confirm. All your trips, locations, and account data will be permanently removed.")
        }
        .onAppear {
            AnalyticsManager.shared.trackScreen(.privacy)
        }
    }

    // MARK: - Disclaimer Card

    private var disclaimerCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Image(systemName: "exclamationmark.triangle.fill")
                    .foregroundColor(.orange)
                Text("Important Notice")
                    .font(.headline)
            }

            Text("""
            MyTravelStatus is an informational tool only. It is NOT legal, tax, or immigration advice.

            • Always verify visa requirements with official government sources
            • Consult with qualified professionals for legal/tax matters
            • Entry decisions are at the discretion of border officials
            • We do not guarantee accuracy of compliance calculations

            Use this app as a helpful tracker, not as legal guidance.
            """)
            .font(.caption)
            .foregroundColor(.secondary)
        }
        .padding(.vertical, 8)
    }

    // MARK: - Location Privacy Row

    private var locationPrivacyRow: some View {
        Toggle(isOn: Binding(
            get: { privacySettings.backgroundLocationEnabled },
            set: { newValue in
                if newValue && !privacySettings.backgroundLocationEducationShown {
                    showingLocationEducation = true
                } else {
                    privacySettings.backgroundLocationEnabled = newValue
                    if newValue {
                        locationManager.requestAlwaysPermission()
                        locationManager.scheduleBackgroundTasks()
                    }
                }
            }
        )) {
            HStack {
                Image(systemName: "location.fill")
                    .foregroundColor(.blue)
                VStack(alignment: .leading) {
                    Text("Background Location")
                    Text(locationStatusText)
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
        }
    }

    private var locationStatusText: String {
        switch locationManager.authorizationStatus {
        case .authorizedAlways:
            return privacySettings.backgroundLocationEnabled ? "Active - 3 checks/day" : "Authorized but disabled"
        case .authorizedWhenInUse:
            return "Only when app is open"
        case .denied, .restricted:
            return "Denied - tap to open Settings"
        case .notDetermined:
            return "Not configured"
        @unknown default:
            return "Unknown"
        }
    }

    // MARK: - Analytics Rows

    private var analyticsRow: some View {
        Toggle(isOn: $privacySettings.analyticsEnabled) {
            HStack {
                Image(systemName: "chart.bar.fill")
                    .foregroundColor(.purple)
                VStack(alignment: .leading) {
                    Text("Usage Analytics")
                    Text("Anonymous app usage data")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
        }
    }

    private var crashReportingRow: some View {
        Toggle(isOn: $privacySettings.crashReportingEnabled) {
            HStack {
                Image(systemName: "ant.fill")
                    .foregroundColor(.orange)
                VStack(alignment: .leading) {
                    Text("Crash Reports")
                    Text("Help us fix bugs")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
        }
    }

    // MARK: - Data Management Rows

    private var exportDataRow: some View {
        Button {
            Task {
                do {
                    exportResult = try await dataManager.exportAllData()
                    showingExportSheet = true
                } catch {
                    // Handle error
                }
            }
        } label: {
            HStack {
                Image(systemName: "square.and.arrow.up")
                    .foregroundColor(.blue)
                VStack(alignment: .leading) {
                    Text("Export My Data")
                    Text("Download all your data as JSON")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                Spacer()
                if dataManager.isExporting {
                    ProgressView()
                } else {
                    Image(systemName: "chevron.right")
                        .foregroundColor(.secondary)
                }
            }
        }
        .disabled(dataManager.isExporting)
    }

    private var clearLocalDataRow: some View {
        Button(role: .destructive) {
            Task {
                try? await dataManager.clearLocalDataOnly()
            }
        } label: {
            HStack {
                Image(systemName: "trash")
                    .foregroundColor(.orange)
                VStack(alignment: .leading) {
                    Text("Clear Local Data")
                    Text("Remove cached data (keeps account)")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
            }
        }
    }

    private var deleteAccountRow: some View {
        Button(role: .destructive) {
            showingDeleteConfirmation = true
        } label: {
            HStack {
                Image(systemName: "person.crop.circle.badge.xmark")
                    .foregroundColor(.red)
                VStack(alignment: .leading) {
                    Text("Delete Account & Data")
                    Text("Permanently remove everything")
                        .font(.caption)
                        .foregroundColor(.secondary)
                }
                Spacer()
                if dataManager.isDeletingAccount {
                    ProgressView()
                }
            }
        }
        .disabled(dataManager.isDeletingAccount)
    }

    // MARK: - Legal Links

    private var legalLinksSection: some View {
        Group {
            Link(destination: URL(string: "https://mytravelstatus.com/privacy")!) {
                HStack {
                    Image(systemName: "hand.raised.fill")
                        .foregroundColor(.blue)
                    Text("Privacy Policy")
                    Spacer()
                    Image(systemName: "arrow.up.right")
                        .foregroundColor(.secondary)
                }
            }

            Link(destination: URL(string: "https://mytravelstatus.com/terms")!) {
                HStack {
                    Image(systemName: "doc.text.fill")
                        .foregroundColor(.blue)
                    Text("Terms of Service")
                    Spacer()
                    Image(systemName: "arrow.up.right")
                        .foregroundColor(.secondary)
                }
            }

            Link(destination: URL(string: "https://mytravelstatus.com/support")!) {
                HStack {
                    Image(systemName: "questionmark.circle.fill")
                        .foregroundColor(.blue)
                    Text("Help & Support")
                    Spacer()
                    Image(systemName: "arrow.up.right")
                        .foregroundColor(.secondary)
                }
            }
        }
    }
}

// MARK: - Export Result View

struct ExportResultView: View {
    @Environment(\.dismiss) private var dismiss
    let result: DataExportResult

    var body: some View {
        NavigationStack {
            VStack(spacing: 24) {
                Image(systemName: "checkmark.circle.fill")
                    .font(.system(size: 60))
                    .foregroundColor(.green)

                Text("Data Export Ready")
                    .font(.title2)
                    .fontWeight(.bold)

                Text("Your data has been exported. You can save it to Files, send it via email, or share it another way.")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                    .multilineTextAlignment(.center)
                    .padding(.horizontal)

                VStack(alignment: .leading, spacing: 8) {
                    HStack {
                        Text("File:")
                        Spacer()
                        Text(result.filename)
                            .foregroundColor(.secondary)
                    }
                    HStack {
                        Text("Size:")
                        Spacer()
                        Text(ByteCountFormatter.string(fromByteCount: Int64(result.jsonData.count), countStyle: .file))
                            .foregroundColor(.secondary)
                    }
                    HStack {
                        Text("Date:")
                        Spacer()
                        Text(result.exportDate, style: .date)
                            .foregroundColor(.secondary)
                    }
                }
                .font(.subheadline)
                .padding()
                .background(Color(.systemGray6))
                .cornerRadius(12)
                .padding(.horizontal)

                Button {
                    DataManagementService.shared.shareExportedData(result)
                } label: {
                    Label("Save or Share", systemImage: "square.and.arrow.up")
                        .font(.headline)
                        .frame(maxWidth: .infinity)
                        .padding()
                        .background(Color.blue)
                        .foregroundColor(.white)
                        .cornerRadius(12)
                }
                .padding(.horizontal)

                Spacer()
            }
            .padding(.top, 40)
            .navigationTitle("Export Complete")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Done") {
                        dismiss()
                    }
                }
            }
        }
    }
}

// MARK: - Preview

#Preview {
    NavigationStack {
        PrivacySettingsView()
            .environmentObject(PrivacySettings.shared)
    }
}
