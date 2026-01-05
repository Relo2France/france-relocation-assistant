/**
 * AppDelegate.swift
 *
 * UIApplicationDelegate for handling system-level events including
 * push notification registration with APNs.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import UIKit
import UserNotifications

class AppDelegate: NSObject, UIApplicationDelegate {

    // MARK: - Push Notification Registration

    func application(
        _ application: UIApplication,
        didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data
    ) {
        Task { @MainActor in
            PushNotificationManager.shared.didRegisterForRemoteNotifications(deviceToken: deviceToken)
        }
    }

    func application(
        _ application: UIApplication,
        didFailToRegisterForRemoteNotificationsWithError error: Error
    ) {
        Task { @MainActor in
            PushNotificationManager.shared.didFailToRegisterForRemoteNotifications(error: error)
        }
    }

    // MARK: - Remote Notification Handling (Silent/Background)

    func application(
        _ application: UIApplication,
        didReceiveRemoteNotification userInfo: [AnyHashable: Any],
        fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void
    ) {
        // Handle silent push notifications for background data refresh
        print("[AppDelegate] Received remote notification: \(userInfo)")

        // Check if this is a content-available silent push
        if let aps = userInfo["aps"] as? [String: Any],
           let contentAvailable = aps["content-available"] as? Int,
           contentAvailable == 1 {

            // Trigger background sync
            Task {
                do {
                    try await SyncManager.shared.sync()
                    completionHandler(.newData)
                } catch {
                    print("[AppDelegate] Background sync failed: \(error)")
                    completionHandler(.failed)
                }
            }
        } else {
            completionHandler(.noData)
        }
    }

    // MARK: - Application Lifecycle

    func application(
        _ application: UIApplication,
        didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil
    ) -> Bool {
        // Check if app was launched from notification
        if let notificationOption = launchOptions?[.remoteNotification] as? [AnyHashable: Any] {
            print("[AppDelegate] Launched from notification: \(notificationOption)")

            // Handle the notification that launched the app
            Task { @MainActor in
                // Small delay to ensure UI is ready
                try? await Task.sleep(nanoseconds: 500_000_000) // 0.5 seconds
                NotificationCenter.default.post(
                    name: .pushNotificationReceived,
                    object: nil,
                    userInfo: notificationOption as? [String: Any]
                )
            }
        }

        return true
    }

    func applicationDidBecomeActive(_ application: UIApplication) {
        // Clear badge count when app becomes active
        application.applicationIconBadgeNumber = 0
    }
}
