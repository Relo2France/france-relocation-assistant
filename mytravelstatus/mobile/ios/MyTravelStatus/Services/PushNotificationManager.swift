/**
 * PushNotificationManager.swift
 *
 * Handles push notification registration, permissions, and handling
 * for the MyTravelStatus iOS app using APNs.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import Foundation
import UserNotifications
import UIKit

@MainActor
class PushNotificationManager: NSObject, ObservableObject {
    static let shared = PushNotificationManager()

    // MARK: - Published Properties

    @Published var isAuthorized = false
    @Published var authorizationStatus: UNAuthorizationStatus = .notDetermined
    @Published var deviceToken: String?

    // MARK: - Private Properties

    private let notificationCenter = UNUserNotificationCenter.current()

    // MARK: - Initialization

    override private init() {
        super.init()
        notificationCenter.delegate = self
    }

    // MARK: - Authorization

    /// Request notification permissions from the user
    func requestAuthorization() async -> Bool {
        do {
            let granted = try await notificationCenter.requestAuthorization(
                options: [.alert, .badge, .sound]
            )

            await updateAuthorizationStatus()

            if granted {
                // Register for remote notifications on the main thread
                await MainActor.run {
                    UIApplication.shared.registerForRemoteNotifications()
                }
            }

            return granted
        } catch {
            print("[Push] Authorization request failed: \(error)")
            return false
        }
    }

    /// Check current authorization status
    func checkAuthorizationStatus() async {
        await updateAuthorizationStatus()
    }

    private func updateAuthorizationStatus() async {
        let settings = await notificationCenter.notificationSettings()
        authorizationStatus = settings.authorizationStatus
        isAuthorized = settings.authorizationStatus == .authorized
    }

    // MARK: - Device Token Registration

    /// Called when APNs registration succeeds
    func didRegisterForRemoteNotifications(deviceToken: Data) {
        let token = deviceToken.map { String(format: "%02.2hhx", $0) }.joined()
        self.deviceToken = token
        print("[Push] Device token received: \(token)")

        // Register with backend
        Task {
            await registerDeviceWithBackend(token: token)
        }
    }

    /// Called when APNs registration fails
    func didFailToRegisterForRemoteNotifications(error: Error) {
        print("[Push] Failed to register for remote notifications: \(error)")
        deviceToken = nil
    }

    /// Register device token with the backend
    private func registerDeviceWithBackend(token: String) async {
        guard APIClient.shared.isAuthenticated else {
            print("[Push] Not authenticated, skipping device registration")
            return
        }

        let deviceId = await UIDevice.current.identifierForVendor?.uuidString ?? UUID().uuidString
        let appVersion = Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String ?? "1.0.0"
        let deviceName = await UIDevice.current.name

        let registration = DeviceRegistration(
            deviceId: deviceId,
            pushToken: token,
            platform: "ios",
            appVersion: appVersion,
            deviceName: deviceName
        )

        do {
            try await APIClient.shared.registerDevice(registration)
            print("[Push] Device registered with backend successfully")
        } catch {
            print("[Push] Failed to register device with backend: \(error)")
        }
    }

    /// Unregister device from push notifications
    func unregisterDevice() async {
        guard let deviceId = await UIDevice.current.identifierForVendor?.uuidString else {
            return
        }

        do {
            try await APIClient.shared.unregisterDevice(deviceId: deviceId)
            deviceToken = nil
            print("[Push] Device unregistered from backend")
        } catch {
            print("[Push] Failed to unregister device: \(error)")
        }

        // Unregister from APNs
        await MainActor.run {
            UIApplication.shared.unregisterForRemoteNotifications()
        }
    }

    // MARK: - Re-register on Login

    /// Call this when user logs in to ensure device is registered
    func registerOnLogin() async {
        // Check if we have a token and re-register
        if let token = deviceToken {
            await registerDeviceWithBackend(token: token)
        } else {
            // Request permissions if not already authorized
            if authorizationStatus == .notDetermined {
                _ = await requestAuthorization()
            } else if authorizationStatus == .authorized {
                await MainActor.run {
                    UIApplication.shared.registerForRemoteNotifications()
                }
            }
        }
    }
}

// MARK: - UNUserNotificationCenterDelegate

extension PushNotificationManager: UNUserNotificationCenterDelegate {

    /// Handle notification received while app is in foreground
    nonisolated func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        willPresent notification: UNNotification,
        withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void
    ) {
        let userInfo = notification.request.content.userInfo
        print("[Push] Notification received in foreground: \(userInfo)")

        // Show banner, play sound, and update badge even when app is in foreground
        completionHandler([.banner, .sound, .badge])
    }

    /// Handle notification tap (app was in background or terminated)
    nonisolated func userNotificationCenter(
        _ center: UNUserNotificationCenter,
        didReceive response: UNNotificationResponse,
        withCompletionHandler completionHandler: @escaping () -> Void
    ) {
        let userInfo = response.notification.request.content.userInfo
        print("[Push] Notification tapped: \(userInfo)")

        // Handle the notification action
        Task { @MainActor in
            await handleNotificationAction(userInfo: userInfo)
        }

        completionHandler()
    }

    /// Process notification action and navigate if needed
    @MainActor
    private func handleNotificationAction(userInfo: [AnyHashable: Any]) async {
        // Extract notification type and data
        guard let type = userInfo["type"] as? String else {
            return
        }

        // Post notification for navigation handling
        NotificationCenter.default.post(
            name: .pushNotificationReceived,
            object: nil,
            userInfo: ["type": type, "data": userInfo]
        )

        // Handle specific notification types
        switch type {
        case "threshold_warning", "threshold_danger":
            // Navigate to dashboard/passport control
            NotificationCenter.default.post(name: .navigateToPassportControl, object: nil)

        case "trip_reminder":
            // Navigate to trips view
            NotificationCenter.default.post(name: .navigateToTrips, object: nil)

        case "calendar_sync":
            // Navigate to calendar sync
            NotificationCenter.default.post(name: .navigateToCalendarSync, object: nil)

        case "location_checkin":
            // Navigate to location tracker
            NotificationCenter.default.post(name: .navigateToLocation, object: nil)

        default:
            print("[Push] Unknown notification type: \(type)")
        }
    }
}

// MARK: - Notification Names

extension Notification.Name {
    static let pushNotificationReceived = Notification.Name("pushNotificationReceived")
    static let navigateToPassportControl = Notification.Name("navigateToPassportControl")
    static let navigateToTrips = Notification.Name("navigateToTrips")
    static let navigateToCalendarSync = Notification.Name("navigateToCalendarSync")
    static let navigateToLocation = Notification.Name("navigateToLocation")
}
