/**
 * PassportControlView.swift
 *
 * One-tap view optimized for showing border officials your travel compliance.
 * Large, clear status display with recent entries list.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI

struct PassportControlView: View {
    @StateObject private var viewModel = PassportControlViewModel()
    @Environment(\.colorScheme) var colorScheme

    var body: some View {
        ScrollView {
            VStack(spacing: 24) {
                // Header
                headerSection

                // Status Circle
                statusCircle

                // Days Remaining
                daysRemainingSection

                Divider()
                    .padding(.horizontal)

                // Recent Entries
                recentEntriesSection

                Spacer(minLength: 20)

                // Verification Timestamp
                verificationTimestamp
            }
            .padding()
        }
        .background(Color(.systemBackground))
        .navigationBarTitleDisplayMode(.inline)
        .onAppear {
            Task {
                await viewModel.loadData()
            }
        }
        .refreshable {
            await viewModel.loadData()
        }
        .overlay {
            if viewModel.isLoading {
                ProgressView()
                    .scaleEffect(1.5)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .background(Color.black.opacity(0.1))
            }
        }
    }

    // MARK: - Header

    private var headerSection: some View {
        HStack(spacing: 12) {
            Text("🇪🇺")
                .font(.system(size: 40))

            Text("SCHENGEN STATUS")
                .font(.title2.bold())
                .foregroundColor(.primary)
        }
    }

    // MARK: - Status Circle

    private var statusCircle: some View {
        ZStack {
            // Background circle
            Circle()
                .fill(viewModel.statusColor)
                .frame(width: 200, height: 200)
                .shadow(color: viewModel.statusColor.opacity(0.4), radius: 10, x: 0, y: 5)

            VStack(spacing: 8) {
                // Checkmark or X
                Image(systemName: viewModel.isCompliant ? "checkmark" : "xmark")
                    .font(.system(size: 50, weight: .bold))
                    .foregroundColor(.white)

                // Status text
                Text(viewModel.isCompliant ? "COMPLIANT" : "OVERSTAY")
                    .font(.headline)
                    .foregroundColor(.white)

                // Days used
                Text("\(viewModel.daysUsed) / \(viewModel.daysAllowed) days")
                    .font(.title3)
                    .foregroundColor(.white.opacity(0.9))
            }
        }
    }

    // MARK: - Days Remaining

    private var daysRemainingSection: some View {
        VStack(spacing: 6) {
            Text("\(viewModel.daysRemaining) DAYS REMAINING")
                .font(.title2.bold())
                .foregroundColor(.primary)

            Text("Window: \(viewModel.formattedWindowStart) - \(viewModel.formattedWindowEnd)")
                .font(.subheadline)
                .foregroundColor(.secondary)

            // Progress bar
            GeometryReader { geometry in
                ZStack(alignment: .leading) {
                    // Background
                    RoundedRectangle(cornerRadius: 4)
                        .fill(Color.gray.opacity(0.2))
                        .frame(height: 8)

                    // Progress
                    RoundedRectangle(cornerRadius: 4)
                        .fill(viewModel.statusColor)
                        .frame(width: geometry.size.width * viewModel.progressPercentage, height: 8)
                }
            }
            .frame(height: 8)
            .padding(.horizontal, 40)
            .padding(.top, 8)
        }
    }

    // MARK: - Recent Entries

    private var recentEntriesSection: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("RECENT ENTRIES")
                .font(.headline)
                .foregroundColor(.secondary)
                .padding(.horizontal)

            if viewModel.recentTrips.isEmpty {
                Text("No recent trips")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
                    .padding(.horizontal)
            } else {
                ForEach(viewModel.recentTrips) { trip in
                    RecentTripRow(trip: trip)
                }
            }
        }
    }

    // MARK: - Verification Timestamp

    private var verificationTimestamp: some View {
        HStack {
            Image(systemName: "clock")
                .foregroundColor(.secondary)

            Text("Last verified: \(viewModel.formattedLastVerified)")
                .font(.caption)
                .foregroundColor(.secondary)
        }
        .padding(.vertical, 8)
        .padding(.horizontal, 16)
        .background(Color(.secondarySystemBackground))
        .cornerRadius(8)
    }
}

// MARK: - Recent Trip Row

struct RecentTripRow: View {
    let trip: RecentTripSummary

    var body: some View {
        HStack {
            // Flag emoji
            Text(flagEmoji(for: trip.country))
                .font(.title2)

            VStack(alignment: .leading, spacing: 2) {
                Text(trip.country)
                    .font(.subheadline.bold())
                    .foregroundColor(.primary)

                Text(dateRangeString)
                    .font(.caption)
                    .foregroundColor(.secondary)
            }

            Spacer()

            Text("\(trip.days)d")
                .font(.subheadline.bold())
                .foregroundColor(.secondary)
        }
        .padding(.horizontal)
        .padding(.vertical, 8)
    }

    private var dateRangeString: String {
        let formatter = DateFormatter()
        formatter.dateFormat = "MMM d, yyyy"

        let start = formatter.string(from: trip.startDate)

        // Check if trip is ongoing (end date is today or future)
        if trip.endDate >= Calendar.current.startOfDay(for: Date()) {
            return "\(start) → Present"
        }

        let end = formatter.string(from: trip.endDate)
        return "\(start) → \(end)"
    }

    private func flagEmoji(for country: String) -> String {
        // Map country names to flag emojis
        let flags: [String: String] = [
            "France": "🇫🇷",
            "Germany": "🇩🇪",
            "Italy": "🇮🇹",
            "Spain": "🇪🇸",
            "Netherlands": "🇳🇱",
            "Belgium": "🇧🇪",
            "Portugal": "🇵🇹",
            "Austria": "🇦🇹",
            "Switzerland": "🇨🇭",
            "Greece": "🇬🇷",
            "Poland": "🇵🇱",
            "Czech Republic": "🇨🇿",
            "Hungary": "🇭🇺",
            "Sweden": "🇸🇪",
            "Norway": "🇳🇴",
            "Denmark": "🇩🇰",
            "Finland": "🇫🇮",
            "Iceland": "🇮🇸",
            "Luxembourg": "🇱🇺",
            "Malta": "🇲🇹",
            "Estonia": "🇪🇪",
            "Latvia": "🇱🇻",
            "Lithuania": "🇱🇹",
            "Slovenia": "🇸🇮",
            "Slovakia": "🇸🇰",
            "Croatia": "🇭🇷",
            "Bulgaria": "🇧🇬",
            "Romania": "🇷🇴",
            "Liechtenstein": "🇱🇮"
        ]

        return flags[country] ?? "🏳️"
    }
}

// MARK: - View Model

@MainActor
class PassportControlViewModel: ObservableObject {
    @Published var isLoading = true
    @Published var isCompliant = true
    @Published var daysUsed = 0
    @Published var daysAllowed = 90
    @Published var daysRemaining = 90
    @Published var status: StatusLevel = .safe
    @Published var windowStart = Date()
    @Published var windowEnd = Date()
    @Published var recentTrips: [RecentTripSummary] = []
    @Published var lastVerified = Date()
    @Published var error: Error?

    private let apiClient = APIClient.shared

    var statusColor: Color {
        switch status {
        case .safe:
            return .green
        case .warning:
            return .yellow
        case .danger:
            return .orange
        case .critical:
            return .red
        }
    }

    var progressPercentage: CGFloat {
        guard daysAllowed > 0 else { return 0 }
        return min(1.0, CGFloat(daysUsed) / CGFloat(daysAllowed))
    }

    var formattedWindowStart: String {
        let formatter = DateFormatter()
        formatter.dateFormat = "MMM d"
        return formatter.string(from: windowStart)
    }

    var formattedWindowEnd: String {
        let formatter = DateFormatter()
        formatter.dateFormat = "MMM d, yyyy"
        return formatter.string(from: windowEnd)
    }

    var formattedLastVerified: String {
        let formatter = RelativeDateTimeFormatter()
        formatter.unitsStyle = .abbreviated
        return formatter.localizedString(for: lastVerified, relativeTo: Date())
    }

    func loadData() async {
        isLoading = true
        error = nil

        do {
            // Try to load from server first
            let data = try await apiClient.getPassportControlData()
            updateFromData(data)
        } catch {
            // Fall back to cached data
            if let cachedData = loadCachedData() {
                updateFromData(cachedData)
            } else {
                self.error = error
            }
        }

        isLoading = false
    }

    private func updateFromData(_ data: PassportControlData) {
        isCompliant = data.isCompliant
        daysUsed = data.daysUsed
        daysAllowed = data.daysAllowed
        daysRemaining = data.daysRemaining
        status = data.status
        windowStart = data.windowStart
        windowEnd = data.windowEnd
        recentTrips = data.recentTrips
        lastVerified = data.lastVerified

        // Cache for offline use
        cacheData(data)
    }

    private func cacheData(_ data: PassportControlData) {
        // Store in UserDefaults for quick offline access
        if let encoded = try? JSONEncoder().encode(data) {
            UserDefaults.standard.set(encoded, forKey: "passportControlCache")
        }
    }

    private func loadCachedData() -> PassportControlData? {
        guard let data = UserDefaults.standard.data(forKey: "passportControlCache"),
              let decoded = try? JSONDecoder().decode(PassportControlData.self, from: data) else {
            return nil
        }
        return decoded
    }
}

// MARK: - Preview

#Preview {
    NavigationStack {
        PassportControlView()
    }
}
