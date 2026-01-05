/**
 * DemoModeService.swift
 *
 * Demo mode for App Store reviewers.
 * Allows reviewers to evaluate all premium features without payment.
 *
 * Activation methods:
 * 1. Special demo account (demo@mytravelstatus.com / demo123)
 * 2. Settings > shake device 5 times (hidden activation)
 * 3. Direct URL: mytravelstatus://demo
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation
import Combine

/// Demo mode configuration
struct DemoModeConfig {
    static let demoEmail = "demo@mytravelstatus.com"
    static let demoPassword = "demo123"
    static let reviewerNote = """
    App Store Reviewer Access:
    - Email: demo@mytravelstatus.com
    - Password: demo123

    This account has full access to all premium features.
    Demo data is pre-loaded for evaluation purposes.
    """
}

@MainActor
class DemoModeService: ObservableObject {
    static let shared = DemoModeService()

    // MARK: - Published Properties

    @Published private(set) var isDemoMode: Bool = false
    @Published private(set) var demoTrips: [Trip] = []
    @Published private(set) var shakeCount = 0

    // MARK: - Private Properties

    private var shakeTimer: Timer?
    private let shakeThreshold = 5
    private let shakeResetInterval: TimeInterval = 3.0

    private init() {
        isDemoMode = PrivacySettings.shared.demoModeEnabled
        if isDemoMode {
            loadDemoData()
        }
    }

    // MARK: - Public Methods

    /// Activate demo mode (used by reviewer credentials)
    func activateDemoMode() {
        isDemoMode = true
        PrivacySettings.shared.demoModeEnabled = true
        loadDemoData()

        AnalyticsManager.shared.track(.screenViewed, properties: ["screen": "demo_activated"])
    }

    /// Deactivate demo mode
    func deactivateDemoMode() {
        isDemoMode = false
        PrivacySettings.shared.demoModeEnabled = false
        demoTrips = []
    }

    /// Check if credentials are demo account
    func isDemoCredentials(email: String, password: String) -> Bool {
        email.lowercased() == DemoModeConfig.demoEmail.lowercased() &&
        password == DemoModeConfig.demoPassword
    }

    /// Handle shake gesture for hidden demo activation
    func recordShake() {
        shakeCount += 1

        // Reset shake counter after timeout
        shakeTimer?.invalidate()
        shakeTimer = Timer.scheduledTimer(withTimeInterval: shakeResetInterval, repeats: false) { [weak self] _ in
            Task { @MainActor in
                self?.shakeCount = 0
            }
        }

        if shakeCount >= shakeThreshold {
            activateDemoMode()
            shakeCount = 0
        }
    }

    /// Handle demo URL scheme
    func handleDemoURL(_ url: URL) -> Bool {
        if url.scheme == "mytravelstatus" && url.host == "demo" {
            activateDemoMode()
            return true
        }
        return false
    }

    // MARK: - Demo Data

    private func loadDemoData() {
        // Create sample trips for demo mode
        let calendar = Calendar.current
        let today = Date()

        demoTrips = [
            // Recent France trip
            Trip(
                id: 9001,
                startDate: calendar.date(byAdding: .day, value: -10, to: today)!,
                endDate: calendar.date(byAdding: .day, value: -3, to: today)!,
                country: "France",
                category: .personal,
                notes: "Demo trip - Paris vacation",
                locationSource: .manual
            ),
            // Germany trip
            Trip(
                id: 9002,
                startDate: calendar.date(byAdding: .day, value: -25, to: today)!,
                endDate: calendar.date(byAdding: .day, value: -20, to: today)!,
                country: "Germany",
                category: .business,
                notes: "Demo trip - Berlin conference",
                locationSource: .manual
            ),
            // Italy trip
            Trip(
                id: 9003,
                startDate: calendar.date(byAdding: .day, value: -45, to: today)!,
                endDate: calendar.date(byAdding: .day, value: -40, to: today)!,
                country: "Italy",
                category: .personal,
                notes: "Demo trip - Rome exploration",
                locationSource: .manual
            ),
            // Spain trip
            Trip(
                id: 9004,
                startDate: calendar.date(byAdding: .day, value: -60, to: today)!,
                endDate: calendar.date(byAdding: .day, value: -55, to: today)!,
                country: "Spain",
                category: .personal,
                notes: "Demo trip - Barcelona",
                locationSource: .manual
            ),
            // Current trip (simulating being in France now)
            Trip(
                id: 9005,
                startDate: today,
                endDate: today,
                country: "France",
                category: .personal,
                notes: "Demo - current location",
                locationSource: .mobileGps
            )
        ]
    }

    /// Get demo passport control data
    func getDemoPassportControlData() -> PassportControlData {
        let calendar = Calendar.current
        let today = Date()

        // Calculate demo days used (sum of demo trips)
        let daysUsed = demoTrips.reduce(0) { total, trip in
            let days = calendar.dateComponents([.day], from: trip.startDate, to: trip.endDate).day ?? 0
            return total + days + 1
        }

        return PassportControlData(
            daysUsed: daysUsed,
            daysRemaining: 90 - daysUsed,
            daysAllowed: 90,
            windowDays: 180,
            windowStart: calendar.date(byAdding: .day, value: -180, to: today)!,
            windowEnd: today,
            status: daysUsed > 80 ? .warning : .safe,
            recentTrips: demoTrips.prefix(3).map { trip in
                RecentTrip(
                    country: trip.country,
                    startDate: trip.startDate,
                    endDate: trip.endDate,
                    days: calendar.dateComponents([.day], from: trip.startDate, to: trip.endDate).day ?? 0 + 1
                )
            },
            nextAvailableDate: nil,
            jurisdictionCode: "schengen"
        )
    }
}

// MARK: - Demo Data Models

struct PassportControlData {
    let daysUsed: Int
    let daysRemaining: Int
    let daysAllowed: Int
    let windowDays: Int
    let windowStart: Date
    let windowEnd: Date
    let status: ComplianceStatus
    let recentTrips: [RecentTrip]
    let nextAvailableDate: Date?
    let jurisdictionCode: String
}

struct RecentTrip {
    let country: String
    let startDate: Date
    let endDate: Date
    let days: Int
}

enum ComplianceStatus: String {
    case safe = "safe"
    case warning = "warning"
    case danger = "danger"
    case critical = "critical"
}
