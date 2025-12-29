/**
 * MyTravelStatusApp.swift
 *
 * Main entry point for the MyTravelStatus iOS app.
 * Track your visa, tax, and residency days worldwide.
 *
 * @package MyTravelStatus
 * @since   1.0.0
 */

import SwiftUI
import BackgroundTasks

@main
struct MyTravelStatusApp: App {
    @StateObject private var appState = AppState()
    @StateObject private var locationManager = BackgroundLocationManager.shared
    @StateObject private var syncManager = SyncManager.shared

    init() {
        // Register background tasks
        registerBackgroundTasks()
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
                .environmentObject(appState)
                .environmentObject(locationManager)
                .environmentObject(syncManager)
                .onAppear {
                    // Check app status on launch
                    Task {
                        await appState.checkAppStatus()
                    }

                    // Schedule background location checks
                    locationManager.scheduleBackgroundTasks()
                }
        }
    }

    // MARK: - Background Tasks

    private func registerBackgroundTasks() {
        // Register location check task
        BGTaskScheduler.shared.register(
            forTaskWithIdentifier: BackgroundLocationManager.taskIdentifier,
            using: nil
        ) { task in
            self.handleLocationCheck(task: task as! BGAppRefreshTask)
        }

        // Register sync task
        BGTaskScheduler.shared.register(
            forTaskWithIdentifier: SyncManager.taskIdentifier,
            using: nil
        ) { task in
            self.handleSync(task: task as! BGProcessingTask)
        }
    }

    private func handleLocationCheck(task: BGAppRefreshTask) {
        // Schedule the next check
        locationManager.scheduleBackgroundTasks()

        // Create a task to capture location
        let captureTask = Task {
            do {
                try await locationManager.captureLocation()
                task.setTaskCompleted(success: true)
            } catch {
                print("Background location capture failed: \(error)")
                task.setTaskCompleted(success: false)
            }
        }

        // Handle task expiration
        task.expirationHandler = {
            captureTask.cancel()
        }
    }

    private func handleSync(task: BGProcessingTask) {
        // Schedule the next sync
        syncManager.scheduleBackgroundSync()

        // Create a task to sync data
        let syncTask = Task {
            do {
                try await syncManager.sync()
                task.setTaskCompleted(success: true)
            } catch {
                print("Background sync failed: \(error)")
                task.setTaskCompleted(success: false)
            }
        }

        // Handle task expiration
        task.expirationHandler = {
            syncTask.cancel()
        }
    }
}

// MARK: - App State

@MainActor
class AppState: ObservableObject {
    @Published var isAuthenticated = false
    @Published var appStatus: AppStatus?
    @Published var showForceUpdate = false
    @Published var showMaintenance = false
    @Published var isLoading = true

    private let apiClient = APIClient.shared

    func checkAppStatus() async {
        do {
            let status = try await apiClient.getAppStatus()
            appStatus = status

            if status.forceUpdate {
                showForceUpdate = true
            } else if status.maintenanceMode {
                showMaintenance = true
            }

            isLoading = false
        } catch {
            print("Failed to check app status: \(error)")
            isLoading = false
        }
    }

    func login(email: String, password: String) async throws {
        try await apiClient.login(email: email, password: password)
        isAuthenticated = true
    }

    func logout() {
        apiClient.logout()
        isAuthenticated = false
    }
}

// Legacy alias for compatibility
typealias SchengenTrackerApp = MyTravelStatusApp
