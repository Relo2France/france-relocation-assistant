# Schengen Tracker Native App - Development Handoff

**Project**: Relo2France Schengen Tracker Mobile App
**Version**: 1.0.0 (In Development)
**Last Updated**: December 29, 2025
**Status**: Phase 1 - Backend API Complete, Native App Development Ready

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Current State](#2-current-state)
3. [Development Phases](#3-development-phases)
4. [Phase 1: Core Native App](#4-phase-1-core-native-app)
5. [Phase 2: Smart Integrations](#5-phase-2-smart-integrations)
6. [Phase 3: Multi-Jurisdiction Expansion](#6-phase-3-multi-jurisdiction-expansion)
7. [Phase 4: Premium Features](#7-phase-4-premium-features)
8. [Technical Architecture](#8-technical-architecture)
9. [API Specifications](#9-api-specifications)
10. [WordPress Integration Patterns](#10-wordpress-integration-patterns)
11. [UI/UX Guidelines](#11-uiux-guidelines)
12. [Testing Checklist](#12-testing-checklist)
13. [Task Tracker](#13-task-tracker)

---

## 1. Executive Summary

### Goal
Build a **simple, clean native mobile app** that provides:
- Efficient background GPS tracking (3 reads/day)
- Passport Control Mode for border crossings
- Photo GPS metadata import
- Calendar integration
- Seamless sync with existing web portal

### Design Philosophy
> **"The best tracker is the one you forget is running."**

- **Minimal interaction required** - Set it and forget it
- **Smart, not intrusive** - 3 GPS reads/day, not constant tracking
- **Clean UI** - One-tap access to key information
- **Battery friendly** - Users shouldn't notice it's running

### Target Platforms
- iOS (Swift/SwiftUI) - Primary
- Android (Kotlin/Jetpack Compose) - Secondary
- Shared backend: Existing WordPress REST API

---

## 2. Current State

### Existing Web Portal Features (Already Built)
| Feature | Status | Location |
|---------|--------|----------|
| Trip CRUD | ✅ Complete | `class-r2f-schengen-api.php` |
| 90/180 Calculation | ✅ Complete | `schengenUtils.ts` |
| Browser Geolocation Check-in | ✅ Complete | `LocationTracker.tsx` |
| IP-based Detection | ✅ Complete | `class-r2f-schengen-location.php` |
| Timezone Detection | ✅ Complete | `useLocationDetection.ts` |
| Google Calendar Sync | ✅ Complete | `class-r2f-schengen-calendar.php` |
| Microsoft Calendar Sync | ✅ Complete | `class-r2f-schengen-calendar.php` |
| Family Member Tracking | ✅ Complete | `class-r2f-schengen-family.php` |
| Analytics Dashboard | ✅ Complete | `AnalyticsDashboard.tsx` |
| AI Suggestions | ✅ Complete | `AISuggestions.tsx` |
| PDF Reports | ✅ Complete | `ReportExport.tsx` |
| CSV Import/Export | ✅ Complete | `CSVImportExport.tsx` |
| Push Notifications | ✅ Complete | `class-r2f-schengen-notifications.php` |
| PWA Support | ✅ Complete | `manifest.json`, `service-worker.js` |

### What the Native App Adds
| Feature | Priority | Phase |
|---------|----------|-------|
| Background GPS (3x daily) | Critical | 1 |
| Passport Control Mode | Critical | 1 |
| Photo GPS Import | High | 2 |
| Native Calendar Access | High | 2 |
| Widgets (iOS/Android) | Medium | 2 |
| Multi-Jurisdiction | Medium | 3 |
| Offline Mode | Medium | 1 |

---

## 3. Development Phases

### Phase Overview

```
Phase 1: Core Native App (MVP)
├── Background GPS tracking (3 reads/day)
├── Passport Control Mode
├── Basic trip sync
├── Offline support
└── Push notifications

Phase 2: Smart Integrations
├── Photo library GPS import
├── Native calendar access
├── Home screen widgets
├── Enhanced sync
└── Flight import (TripIt, Flighty)

Phase 3: Multi-Jurisdiction
├── UK Statutory Residence Test
├── Portugal/Spain/Italy 183-day
├── Jurisdiction selector
└── Custom rules engine

Phase 4: Premium Features
├── Family member sync
├── AI trip suggestions
├── Proof of presence
├── Tax advisor sharing
└── Community features
```

### Timeline Estimate

| Phase | Scope | Dependencies |
|-------|-------|--------------|
| Phase 1 | Core app, GPS, Passport Mode | None |
| Phase 2 | Integrations, widgets | Phase 1 complete |
| Phase 3 | Multi-jurisdiction | Phase 1 complete |
| Phase 4 | Premium features | Phases 1-2 complete |

---

## 4. Phase 1: Core Native App

### 4.1 Background GPS Tracking

#### Design: 3-Read Strategy

```
Morning Check (8:00 AM local)
├── Capture GPS coordinates
├── Reverse geocode to country
├── Compare to yesterday's location
└── Queue for sync

Midday Check (2:00 PM local)
├── Capture GPS coordinates
├── Confirm morning location or detect movement
└── Queue for sync

Evening Check (8:00 PM local)
├── Final daily location
├── Determine "day location" (majority rule)
├── Create/extend trip if Schengen country
└── Sync to server
```

#### Why 3 Reads?
- **Battery efficient**: ~0.1% battery/day vs 5-10% for continuous
- **Accurate enough**: Catches travel (you don't teleport between reads)
- **User-friendly**: No "always on" location permission anxiety
- **Differentiator**: Competitors require continuous tracking

#### Location Resolution Logic

```swift
func determineDayLocation(readings: [LocationReading]) -> DayLocation {
    // Group readings by country
    let countries = readings.map { $0.countryCode }

    // If all same country -> that's today's location
    if Set(countries).count == 1 {
        return DayLocation(country: countries[0], confidence: .high)
    }

    // If mixed -> user is traveling
    // Use the country where they spent the night (evening reading)
    let eveningReading = readings.last!
    return DayLocation(
        country: eveningReading.countryCode,
        confidence: .medium,
        notes: "Travel detected: \(countries.joined(separator: " → "))"
    )
}
```

#### iOS Implementation

```swift
// BackgroundLocationManager.swift

import CoreLocation
import BackgroundTasks

class BackgroundLocationManager: NSObject, CLLocationManagerDelegate {
    static let shared = BackgroundLocationManager()

    private let locationManager = CLLocationManager()
    private let checkTimes = [8, 14, 20] // Hours in local time

    func setupBackgroundTasks() {
        // Register background task
        BGTaskScheduler.shared.register(
            forTaskWithIdentifier: "com.relo2france.schengen.locationCheck",
            using: nil
        ) { task in
            self.handleLocationCheck(task: task as! BGAppRefreshTask)
        }
    }

    func scheduleNextCheck() {
        let request = BGAppRefreshTaskRequest(
            identifier: "com.relo2france.schengen.locationCheck"
        )
        request.earliestBeginDate = nextCheckTime()

        try? BGTaskScheduler.shared.submit(request)
    }

    private func nextCheckTime() -> Date {
        let calendar = Calendar.current
        let now = Date()
        let currentHour = calendar.component(.hour, from: now)

        // Find next check time
        for checkHour in checkTimes {
            if checkHour > currentHour {
                return calendar.date(
                    bySettingHour: checkHour,
                    minute: 0,
                    second: 0,
                    of: now
                )!
            }
        }

        // Next check is tomorrow morning
        let tomorrow = calendar.date(byAdding: .day, value: 1, to: now)!
        return calendar.date(
            bySettingHour: checkTimes[0],
            minute: 0,
            second: 0,
            of: tomorrow
        )!
    }

    func captureLocation() async throws -> LocationReading {
        return try await withCheckedThrowingContinuation { continuation in
            locationManager.requestLocation()
            // Handle in delegate...
        }
    }
}
```

#### Android Implementation

```kotlin
// LocationWorker.kt

class LocationWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        return try {
            val location = captureLocation()
            val country = reverseGeocode(location)

            // Store locally
            LocationDatabase.getInstance(applicationContext)
                .locationDao()
                .insert(LocationReading(
                    lat = location.latitude,
                    lng = location.longitude,
                    country = country,
                    timestamp = System.currentTimeMillis()
                ))

            // Queue sync
            SyncManager.queueLocationSync()

            Result.success()
        } catch (e: Exception) {
            Result.retry()
        }
    }

    companion object {
        fun schedule(context: Context) {
            val checkTimes = listOf(8, 14, 20)

            checkTimes.forEach { hour ->
                val request = PeriodicWorkRequestBuilder<LocationWorker>(
                    1, TimeUnit.DAYS
                )
                    .setInitialDelay(calculateDelay(hour), TimeUnit.MILLISECONDS)
                    .setConstraints(Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.NOT_REQUIRED)
                        .build()
                    )
                    .build()

                WorkManager.getInstance(context)
                    .enqueueUniquePeriodicWork(
                        "location_check_$hour",
                        ExistingPeriodicWorkPolicy.KEEP,
                        request
                    )
            }
        }
    }
}
```

### 4.2 Passport Control Mode

#### Purpose
One-tap view optimized for showing border officials your Schengen compliance.

#### UI Design

```
┌─────────────────────────────────────┐
│  🇪🇺 SCHENGEN STATUS                │
│  ─────────────────────────────────  │
│                                     │
│      ┌─────────────────────┐        │
│      │                     │        │
│      │   ✓ COMPLIANT       │        │
│      │                     │        │
│      │   42 / 90 days      │        │
│      │   used              │        │
│      │                     │        │
│      └─────────────────────┘        │
│                                     │
│  48 DAYS REMAINING                  │
│  Window: Jun 15 - Dec 12, 2025      │
│                                     │
│  ─────────────────────────────────  │
│  RECENT ENTRIES                     │
│                                     │
│  🇫🇷 France                         │
│     Nov 15, 2025 → Present (44d)    │
│                                     │
│  🇪🇸 Spain                          │
│     Oct 1-8, 2025 (8d)              │
│                                     │
│  🇮🇹 Italy                          │
│     Sep 10-15, 2025 (6d)            │
│                                     │
│  ─────────────────────────────────  │
│  │ Last verified: 2 hours ago │     │
│  └──────────────────────────────    │
│                                     │
│  [ View Full History ]              │
└─────────────────────────────────────┘
```

#### Key Features

1. **Large, clear status** - Green checkmark or red X
2. **Days used prominently displayed** - 42/90 format
3. **Current window dates** - Officers can verify the period
4. **Recent entries list** - Shows last 3-5 trips
5. **Offline capable** - Works without network
6. **Timestamp** - Shows when last synced/verified
7. **No login required to view** - Cached data available

#### Implementation

```swift
// PassportControlView.swift

struct PassportControlView: View {
    @StateObject private var viewModel = PassportControlViewModel()

    var body: some View {
        VStack(spacing: 24) {
            // Header
            HStack {
                Text("🇪🇺")
                    .font(.system(size: 40))
                Text("SCHENGEN STATUS")
                    .font(.title2.bold())
                    .foregroundColor(.primary)
            }

            // Status Circle
            ZStack {
                Circle()
                    .fill(viewModel.isCompliant ? Color.green : Color.red)
                    .frame(width: 180, height: 180)

                VStack(spacing: 8) {
                    Image(systemName: viewModel.isCompliant ? "checkmark" : "xmark")
                        .font(.system(size: 50, weight: .bold))
                        .foregroundColor(.white)

                    Text(viewModel.isCompliant ? "COMPLIANT" : "OVERSTAY")
                        .font(.headline)
                        .foregroundColor(.white)

                    Text("\(viewModel.daysUsed) / 90 days")
                        .font(.title3)
                        .foregroundColor(.white.opacity(0.9))
                }
            }

            // Days remaining
            VStack(spacing: 4) {
                Text("\(viewModel.daysRemaining) DAYS REMAINING")
                    .font(.title2.bold())

                Text("Window: \(viewModel.windowStart) - \(viewModel.windowEnd)")
                    .font(.subheadline)
                    .foregroundColor(.secondary)
            }

            Divider()

            // Recent entries
            VStack(alignment: .leading, spacing: 12) {
                Text("RECENT ENTRIES")
                    .font(.headline)
                    .foregroundColor(.secondary)

                ForEach(viewModel.recentTrips.prefix(5)) { trip in
                    TripRow(trip: trip)
                }
            }

            Spacer()

            // Verification timestamp
            HStack {
                Image(systemName: "clock")
                Text("Last verified: \(viewModel.lastVerified)")
            }
            .font(.caption)
            .foregroundColor(.secondary)
        }
        .padding()
        .background(Color(.systemBackground))
    }
}
```

### 4.3 Offline Support

#### Data Architecture

```
Local Storage (SQLite/Realm)
├── trips/
│   ├── id, start_date, end_date, country, category, notes
│   ├── sync_status: synced | pending | conflict
│   └── last_modified: timestamp
├── locations/
│   ├── id, lat, lng, country, city, timestamp
│   ├── source: gps | photo | calendar | manual
│   └── sync_status: synced | pending
├── settings/
│   ├── user_id, email
│   ├── premium_status
│   └── notification_preferences
└── cache/
    ├── schengen_summary (days_used, days_remaining, etc.)
    ├── recent_trips (last 10)
    └── last_sync_timestamp
```

#### Sync Strategy

```swift
class SyncManager {

    enum SyncResult {
        case success
        case partial(synced: Int, failed: Int)
        case offline
        case conflict(items: [ConflictItem])
    }

    func sync() async -> SyncResult {
        guard NetworkMonitor.shared.isConnected else {
            return .offline
        }

        // 1. Push local changes
        let pendingItems = database.getPendingChanges()
        var synced = 0
        var failed = 0
        var conflicts: [ConflictItem] = []

        for item in pendingItems {
            do {
                let serverVersion = try await api.getItem(id: item.id)

                if serverVersion.lastModified > item.lastSynced {
                    // Server has newer version - conflict
                    conflicts.append(ConflictItem(local: item, server: serverVersion))
                } else {
                    // Safe to push
                    try await api.updateItem(item)
                    database.markSynced(item.id)
                    synced += 1
                }
            } catch {
                failed += 1
            }
        }

        // 2. Pull server changes
        let lastSync = database.getLastSyncTimestamp()
        let serverChanges = try await api.getChanges(since: lastSync)

        for change in serverChanges {
            database.applyServerChange(change)
        }

        database.setLastSyncTimestamp(Date())

        if !conflicts.isEmpty {
            return .conflict(items: conflicts)
        } else if failed > 0 {
            return .partial(synced: synced, failed: failed)
        }
        return .success
    }
}
```

### 4.4 Push Notifications

#### Notification Types

| Type | Trigger | Message Example |
|------|---------|-----------------|
| `threshold_warning` | Days used > 60 | "You've used 65 of 90 Schengen days" |
| `threshold_danger` | Days used > 80 | "Only 8 days remaining in Schengen!" |
| `trip_reminder` | Upcoming trip starts | "Your trip to Spain starts tomorrow" |
| `location_confirm` | New country detected | "Are you now in Germany?" |
| `sync_reminder` | No sync in 7 days | "Open app to sync your travel data" |

#### Implementation

Use existing WordPress push infrastructure (`class-r2f-schengen-notifications.php`) with native push registration.

---

## 5. Phase 2: Smart Integrations

### 5.1 Photo GPS Import

#### Flow

```
User taps "Import from Photos"
    │
    ▼
Request Photo Library access
    │
    ▼
Scan photos for GPS metadata
    │
    ▼
Group by date + location
    │
    ▼
Identify trips (consecutive days in same country)
    │
    ▼
Show preview: "Found 3 trips"
    │
    ▼
User confirms/edits
    │
    ▼
Import trips to database
```

#### iOS Implementation

```swift
// PhotoImporter.swift

import Photos

class PhotoImporter {

    struct DetectedTrip {
        let country: String
        let countryCode: String
        let startDate: Date
        let endDate: Date
        let photoCount: Int
        let samplePhoto: UIImage?
    }

    func scanPhotos(
        dateRange: ClosedRange<Date>,
        progress: @escaping (Float) -> Void
    ) async throws -> [DetectedTrip] {

        let fetchOptions = PHFetchOptions()
        fetchOptions.predicate = NSPredicate(
            format: "creationDate >= %@ AND creationDate <= %@",
            dateRange.lowerBound as NSDate,
            dateRange.upperBound as NSDate
        )
        fetchOptions.sortDescriptors = [
            NSSortDescriptor(key: "creationDate", ascending: true)
        ]

        let assets = PHAsset.fetchAssets(with: .image, options: fetchOptions)
        var locationsByDate: [Date: [(CLLocation, PHAsset)]] = [:]

        let total = assets.count
        var processed = 0

        assets.enumerateObjects { asset, _, _ in
            if let location = asset.location,
               let date = asset.creationDate {
                let dayStart = Calendar.current.startOfDay(for: date)
                locationsByDate[dayStart, default: []].append((location, asset))
            }
            processed += 1
            progress(Float(processed) / Float(total))
        }

        // Group consecutive days in same country into trips
        return await groupIntoTrips(locationsByDate)
    }

    private func groupIntoTrips(
        _ locationsByDate: [Date: [(CLLocation, PHAsset)]]
    ) async -> [DetectedTrip] {

        var trips: [DetectedTrip] = []
        var currentTrip: (country: String, code: String, start: Date, end: Date, count: Int, photo: PHAsset?)?

        let sortedDates = locationsByDate.keys.sorted()

        for date in sortedDates {
            guard let locations = locationsByDate[date],
                  let primaryLocation = locations.first else { continue }

            let country = try? await reverseGeocode(primaryLocation.0)
            guard let countryCode = country?.code,
                  let countryName = country?.name else { continue }

            if let current = currentTrip {
                // Check if same country and consecutive day
                let dayDiff = Calendar.current.dateComponents(
                    [.day], from: current.end, to: date
                ).day ?? 0

                if current.code == countryCode && dayDiff <= 1 {
                    // Extend current trip
                    currentTrip = (
                        current.country,
                        current.code,
                        current.start,
                        date,
                        current.count + locations.count,
                        current.photo
                    )
                } else {
                    // Save current trip, start new one
                    trips.append(DetectedTrip(
                        country: current.country,
                        countryCode: current.code,
                        startDate: current.start,
                        endDate: current.end,
                        photoCount: current.count,
                        samplePhoto: await loadThumbnail(current.photo)
                    ))
                    currentTrip = (countryName, countryCode, date, date, locations.count, locations.first?.1)
                }
            } else {
                // Start first trip
                currentTrip = (countryName, countryCode, date, date, locations.count, locations.first?.1)
            }
        }

        // Don't forget last trip
        if let current = currentTrip {
            trips.append(DetectedTrip(
                country: current.country,
                countryCode: current.code,
                startDate: current.start,
                endDate: current.end,
                photoCount: current.count,
                samplePhoto: await loadThumbnail(current.photo)
            ))
        }

        return trips
    }
}
```

### 5.2 Native Calendar Access

#### Flow

```
User taps "Import from Calendar"
    │
    ▼
Request Calendar access
    │
    ▼
Scan events for travel-related keywords
    │
    ▼
Extract location from event location field
    │
    ▼
Identify potential trips
    │
    ▼
User confirms/edits
    │
    ▼
Import trips
```

#### Travel Detection Keywords

```swift
let travelKeywords = [
    // Direct travel terms
    "flight", "fly", "plane", "airport",
    "train", "eurostar", "tgv",
    "hotel", "airbnb", "booking",
    "vacation", "holiday", "trip",

    // Countries/cities (top destinations)
    "paris", "rome", "barcelona", "amsterdam",
    "france", "italy", "spain", "germany",

    // Business travel
    "conference", "meeting in", "visit to",
    "business trip", "work travel"
]
```

### 5.3 Home Screen Widgets

#### iOS Widget Sizes

**Small (2x2)**
```
┌─────────────┐
│ 🇪🇺 42/90   │
│ days used   │
│             │
│ 48 left     │
└─────────────┘
```

**Medium (4x2)**
```
┌───────────────────────────────┐
│ 🇪🇺 SCHENGEN      ✓ Compliant │
│                               │
│ ████████████░░░░░░ 42/90 days │
│                               │
│ 48 days remaining             │
│ Window ends: Dec 12, 2025     │
└───────────────────────────────┘
```

**Large (4x4)**
```
┌───────────────────────────────┐
│ 🇪🇺 SCHENGEN TRACKER          │
│                               │
│     ┌─────────┐               │
│     │ ✓  42   │ days used     │
│     │   /90   │               │
│     └─────────┘               │
│                               │
│ 48 days remaining             │
│ Window: Jun 15 - Dec 12       │
│                               │
│ Recent:                       │
│ 🇫🇷 France (44d)              │
│ 🇪🇸 Spain (8d)                │
│ 🇮🇹 Italy (6d)                │
└───────────────────────────────┘
```

---

## 6. Phase 3: Multi-Jurisdiction Expansion

### 6.1 Jurisdiction Configuration

```swift
// JurisdictionConfig.swift

protocol JurisdictionCalculator {
    func calculate(trips: [Trip], asOf date: Date) -> JurisdictionStatus
}

struct JurisdictionConfig {
    let id: String
    let name: String
    let shortName: String
    let emoji: String
    let countries: Set<String>  // ISO country codes
    let ruleType: RuleType
    let calculator: JurisdictionCalculator

    enum RuleType {
        case rollingDays(period: Int, threshold: Int)  // Schengen: 90 in 180
        case calendarYear(threshold: Int)              // Most 183-day rules
        case taxYear(threshold: Int, yearStart: Int)   // UK: April 6
        case nights(period: Int, threshold: Int)       // UK SRT
        case weightedPresence                          // US Substantial Presence
    }
}

// Predefined jurisdictions
extension JurisdictionConfig {

    static let schengen = JurisdictionConfig(
        id: "schengen",
        name: "Schengen Area (90/180)",
        shortName: "Schengen",
        emoji: "🇪🇺",
        countries: Set(["AT", "BE", "BG", "HR", "CZ", "DK", "EE", "FI", "FR",
                       "DE", "GR", "HU", "IS", "IT", "LV", "LI", "LT", "LU",
                       "MT", "NL", "NO", "PL", "PT", "RO", "SK", "SI", "ES",
                       "SE", "CH"]),
        ruleType: .rollingDays(period: 180, threshold: 90),
        calculator: SchengenCalculator()
    )

    static let ukSRT = JurisdictionConfig(
        id: "uk-srt",
        name: "UK Statutory Residence Test",
        shortName: "UK SRT",
        emoji: "🇬🇧",
        countries: Set(["GB"]),
        ruleType: .nights(period: 365, threshold: 183),
        calculator: UKSRTCalculator()
    )

    static let portugal183 = JurisdictionConfig(
        id: "portugal-183",
        name: "Portugal Tax Residency",
        shortName: "Portugal",
        emoji: "🇵🇹",
        countries: Set(["PT"]),
        ruleType: .calendarYear(threshold: 183),
        calculator: Simple183Calculator(countryCode: "PT")
    )

    static let usSubstantialPresence = JurisdictionConfig(
        id: "us-spt",
        name: "US Substantial Presence Test",
        shortName: "US SPT",
        emoji: "🇺🇸",
        countries: Set(["US"]),
        ruleType: .weightedPresence,
        calculator: USSubstantialPresenceCalculator()
    )
}
```

### 6.2 Calculators

```swift
// SchengenCalculator.swift (already exists - for reference)

class SchengenCalculator: JurisdictionCalculator {

    func calculate(trips: [Trip], asOf date: Date) -> JurisdictionStatus {
        let windowStart = Calendar.current.date(byAdding: .day, value: -179, to: date)!

        let daysInWindow = trips
            .filter { $0.overlaps(with: windowStart...date) }
            .reduce(0) { $0 + $1.daysInRange(windowStart...date) }

        return JurisdictionStatus(
            jurisdictionId: "schengen",
            daysUsed: daysInWindow,
            daysAllowed: 90,
            daysRemaining: max(0, 90 - daysInWindow),
            periodStart: windowStart,
            periodEnd: date,
            status: statusLevel(daysUsed: daysInWindow)
        )
    }
}

// UKSRTCalculator.swift (new)

class UKSRTCalculator: JurisdictionCalculator {

    func calculate(trips: [Trip], asOf date: Date) -> JurisdictionStatus {
        // UK tax year: April 6 to April 5
        let taxYear = ukTaxYear(containing: date)

        // Count NIGHTS, not days
        // A night counts if you're in UK at midnight
        let nightsInUK = trips
            .filter { $0.country == "GB" && $0.overlaps(with: taxYear) }
            .reduce(0) { total, trip in
                total + countNights(trip: trip, in: taxYear)
            }

        return JurisdictionStatus(
            jurisdictionId: "uk-srt",
            daysUsed: nightsInUK,  // Actually nights
            daysAllowed: 183,
            daysRemaining: max(0, 183 - nightsInUK),
            periodStart: taxYear.lowerBound,
            periodEnd: taxYear.upperBound,
            status: statusLevel(daysUsed: nightsInUK)
        )
    }

    private func countNights(trip: Trip, in period: ClosedRange<Date>) -> Int {
        // Each day in the trip (except arrival day) counts as a night
        let start = max(trip.startDate, period.lowerBound)
        let end = min(trip.endDate, period.upperBound)

        guard start <= end else { return 0 }

        // Nights = days - 1 (no night on arrival day)
        let days = Calendar.current.dateComponents([.day], from: start, to: end).day ?? 0
        return max(0, days)
    }
}

// USSubstantialPresenceCalculator.swift (new)

class USSubstantialPresenceCalculator: JurisdictionCalculator {

    func calculate(trips: [Trip], asOf date: Date) -> JurisdictionStatus {
        // Weighted formula:
        // Current year days × 1
        // Prior year days × 1/3
        // Two years ago days × 1/6
        // Total >= 183 = tax resident

        let currentYear = Calendar.current.component(.year, from: date)

        let currentYearDays = daysInUS(trips: trips, year: currentYear)
        let priorYearDays = daysInUS(trips: trips, year: currentYear - 1)
        let twoYearsAgoDays = daysInUS(trips: trips, year: currentYear - 2)

        let weightedTotal = Double(currentYearDays) +
                           (Double(priorYearDays) / 3.0) +
                           (Double(twoYearsAgoDays) / 6.0)

        // Also must have 31+ days in current year
        let meetsCurrentYearMin = currentYearDays >= 31
        let meetsWeightedTest = weightedTotal >= 183

        return JurisdictionStatus(
            jurisdictionId: "us-spt",
            daysUsed: Int(weightedTotal),
            daysAllowed: 183,
            daysRemaining: max(0, 183 - Int(weightedTotal)),
            periodStart: startOfYear(currentYear - 2),
            periodEnd: date,
            status: (meetsCurrentYearMin && meetsWeightedTest) ? .danger : .safe,
            notes: """
                Current year: \(currentYearDays) days
                Prior year: \(priorYearDays) days (×1/3 = \(priorYearDays/3))
                2 years ago: \(twoYearsAgoDays) days (×1/6 = \(twoYearsAgoDays/6))
                Weighted total: \(Int(weightedTotal))
                """
        )
    }
}
```

---

## 7. Phase 4: Premium Features

### 7.1 Family Member Sync

Leverage existing `class-r2f-schengen-family.php` API:

```
GET  /wp-json/r2f-schengen/v1/family
POST /wp-json/r2f-schengen/v1/family
GET  /wp-json/r2f-schengen/v1/family/{id}/summary
```

### 7.2 AI Trip Suggestions

Display suggestions from existing `AISuggestions.tsx` API:

```
GET /wp-json/r2f-schengen/v1/suggestions
```

### 7.3 Proof of Presence

New feature for préfecture applications:

```swift
struct PresenceProof {
    let date: Date
    let location: CLLocation
    let country: String
    let sources: [ProofSource]

    enum ProofSource {
        case gpsReading(accuracy: Double)
        case photo(assetId: String, thumbnail: UIImage?)
        case calendarEvent(title: String)
        case bankTransaction(merchant: String, amount: Decimal)
        case manualCheckin(note: String?)
    }
}
```

---

## 8. Technical Architecture

### 8.1 App Architecture

```
┌─────────────────────────────────────────────────┐
│                 Native App                       │
├─────────────────────────────────────────────────┤
│  UI Layer (SwiftUI / Jetpack Compose)           │
│  ├── Views/Screens                              │
│  ├── ViewModels                                 │
│  └── Navigation                                 │
├─────────────────────────────────────────────────┤
│  Domain Layer                                    │
│  ├── Use Cases                                  │
│  ├── Models                                     │
│  └── Calculators (Schengen, UK SRT, etc.)       │
├─────────────────────────────────────────────────┤
│  Data Layer                                      │
│  ├── Local Database (SQLite/Realm)              │
│  ├── API Client (WordPress REST)                │
│  ├── Sync Manager                               │
│  └── Background Services                        │
├─────────────────────────────────────────────────┤
│  Platform Services                               │
│  ├── Location Manager                           │
│  ├── Photo Library                              │
│  ├── Calendar Access                            │
│  ├── Push Notifications                         │
│  └── Background Tasks                           │
└─────────────────────────────────────────────────┘
           │
           │ REST API (HTTPS)
           ▼
┌─────────────────────────────────────────────────┐
│           WordPress Backend                      │
├─────────────────────────────────────────────────┤
│  r2f-schengen/v1/*                              │
│  fra-portal/v1/schengen/*                       │
├─────────────────────────────────────────────────┤
│  Existing PHP Classes:                          │
│  ├── class-r2f-schengen-api.php                │
│  ├── class-r2f-schengen-location.php           │
│  ├── class-r2f-schengen-family.php             │
│  ├── class-r2f-schengen-calendar.php           │
│  └── class-r2f-schengen-notifications.php      │
└─────────────────────────────────────────────────┘
```

### 8.2 Data Flow

```
Location Reading Flow:
─────────────────────

[Background Task Trigger]
         │
         ▼
[Capture GPS Coordinates]
         │
         ▼
[Reverse Geocode (Nominatim)]
         │
         ▼
[Store in Local DB] ────────┐
         │                  │
         ▼                  │
[Check: Is Schengen?]       │
         │                  │
    ┌────┴────┐             │
    │ YES     │ NO          │
    ▼         ▼             │
[Create/     [Log only]     │
 Extend                     │
 Trip]                      │
    │                       │
    ▼                       │
[Queue for Sync] ◄──────────┘
         │
         ▼
[Network Available?]
    │         │
  YES         NO
    │         │
    ▼         ▼
[Sync to    [Retry
 Server]     Later]
```

---

## 9. API Specifications

### 9.1 Existing Endpoints (Use As-Is)

```
# Trips
GET    /wp-json/r2f-schengen/v1/trips
POST   /wp-json/r2f-schengen/v1/trips
PUT    /wp-json/r2f-schengen/v1/trips/{id}
DELETE /wp-json/r2f-schengen/v1/trips/{id}

# Location
POST   /wp-json/fra-portal/v1/schengen/location
GET    /wp-json/fra-portal/v1/schengen/location/today
GET    /wp-json/fra-portal/v1/schengen/location/history
GET    /wp-json/fra-portal/v1/schengen/location/detect

# Family
GET    /wp-json/r2f-schengen/v1/family
POST   /wp-json/r2f-schengen/v1/family
GET    /wp-json/r2f-schengen/v1/family/{id}/summary

# Suggestions
GET    /wp-json/r2f-schengen/v1/suggestions

# Notifications
GET    /wp-json/r2f-schengen/v1/notifications
POST   /wp-json/r2f-schengen/v1/push/subscribe
```

### 9.2 New Endpoints Needed

```php
// Add to class-r2f-schengen-api.php

/**
 * Batch sync endpoint for mobile app
 *
 * POST /wp-json/r2f-schengen/v1/sync
 *
 * Request:
 * {
 *   "last_sync": "2025-12-01T00:00:00Z",
 *   "local_changes": [
 *     { "type": "trip", "action": "create", "data": {...} },
 *     { "type": "location", "action": "create", "data": {...} }
 *   ]
 * }
 *
 * Response:
 * {
 *   "server_changes": [...],
 *   "sync_results": [...],
 *   "conflicts": [...],
 *   "server_time": "2025-12-29T12:00:00Z"
 * }
 */
register_rest_route(
    'r2f-schengen/v1',
    '/sync',
    array(
        'methods'             => 'POST',
        'callback'            => array( $this, 'handle_batch_sync' ),
        'permission_callback' => array( $this, 'check_permission' ),
    )
);

/**
 * Mobile app status check
 *
 * GET /wp-json/r2f-schengen/v1/app/status
 *
 * Response:
 * {
 *   "min_app_version": "1.0.0",
 *   "latest_app_version": "1.2.0",
 *   "force_update": false,
 *   "maintenance_mode": false,
 *   "server_time": "2025-12-29T12:00:00Z"
 * }
 */
register_rest_route(
    'r2f-schengen/v1',
    '/app/status',
    array(
        'methods'             => 'GET',
        'callback'            => array( $this, 'get_app_status' ),
        'permission_callback' => '__return_true',
    )
);
```

---

## 10. WordPress Integration Patterns

### 10.1 PHP Coding Standards

```php
<?php
/**
 * Class Description
 *
 * @package     R2F_Schengen_Tracker
 * @subpackage  API
 * @since       1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class R2F_Schengen_Mobile_API
 */
class R2F_Schengen_Mobile_API {

    /**
     * Singleton instance.
     *
     * @var R2F_Schengen_Mobile_API|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return R2F_Schengen_Mobile_API
     */
    public static function get_instance(): R2F_Schengen_Mobile_API {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST routes.
     */
    public function register_routes(): void {
        // Routes here...
    }

    /**
     * Handle batch sync from mobile app.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_batch_sync( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        // ALWAYS use prepare() for SQL
        global $wpdb;
        $table = $wpdb->prefix . 'fra_schengen_trips';

        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND updated_at > %s",
                $user_id,
                sanitize_text_field( $params['last_sync'] )
            )
        );

        // Return proper response
        return rest_ensure_response( array(
            'success'        => true,
            'server_changes' => $result,
            'server_time'    => gmdate( 'c' ),
        ) );
    }
}
```

### 10.2 Security Checklist

```php
// ALWAYS do these:

// 1. Permission checks
public function check_permission() {
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_forbidden',
            __( 'You must be logged in.', 'r2f-schengen' ),
            array( 'status' => 401 )
        );
    }
    return true;
}

// 2. Ownership verification
$trip = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM $table WHERE id = %d",
    $trip_id
) );

if ( (int) $trip->user_id !== (int) get_current_user_id() ) {
    return new WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
}

// 3. Input sanitization
$title    = sanitize_text_field( $params['title'] );
$email    = sanitize_email( $params['email'] );
$content  = wp_kses_post( $params['content'] );
$date     = sanitize_text_field( $params['date'] ); // Then validate format
$number   = absint( $params['number'] );
$float    = (float) $params['float'];

// 4. Prepared statements
$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );
$wpdb->prepare( "SELECT * FROM $table WHERE name = %s", $name );

// 5. Nonce verification for non-REST (if applicable)
if ( ! wp_verify_nonce( $_POST['nonce'], 'my_action' ) ) {
    die( 'Security check failed' );
}
```

### 10.3 Database Patterns

```php
// Getting table name
$table = $wpdb->prefix . 'fra_schengen_trips';

// Insert
$wpdb->insert(
    $table,
    array(
        'user_id'    => $user_id,
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'country'    => $country,
    ),
    array( '%d', '%s', '%s', '%s' )  // Format specifiers
);
$new_id = $wpdb->insert_id;

// Update
$wpdb->update(
    $table,
    array( 'end_date' => $new_end_date ),  // Data
    array( 'id' => $trip_id ),              // Where
    array( '%s' ),                          // Data format
    array( '%d' )                           // Where format
);

// Delete
$wpdb->delete(
    $table,
    array( 'id' => $trip_id ),
    array( '%d' )
);

// Select with prepare
$trips = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d
         AND start_date >= %s
         ORDER BY start_date DESC",
        $user_id,
        $since_date
    )
);
```

---

## 11. UI/UX Guidelines

### 11.1 Design Principles

1. **Minimal cognitive load** - Users shouldn't have to think
2. **Glanceable status** - Key info visible in < 1 second
3. **Progressive disclosure** - Show complexity only when needed
4. **Forgiving interaction** - Easy to undo, hard to make mistakes
5. **Offline-first** - Always works, syncs when possible

### 11.2 Color System

```swift
// Colors.swift

extension Color {
    // Status colors
    static let statusSafe = Color.green       // < 60 days
    static let statusWarning = Color.yellow   // 60-79 days
    static let statusDanger = Color.orange    // 80-89 days
    static let statusCritical = Color.red     // 90+ days

    // Brand colors
    static let primary = Color(hex: "2563EB")    // Blue
    static let primaryDark = Color(hex: "1D4ED8")

    // Neutral
    static let background = Color(.systemBackground)
    static let secondaryBackground = Color(.secondarySystemBackground)
}
```

### 11.3 Navigation Structure

```
Tab Bar
├── 🏠 Home
│   ├── Status card (days used/remaining)
│   ├── Recent trips
│   └── Quick actions (check-in, add trip)
│
├── 🗺️ Trips
│   ├── Trip list (sortable, filterable)
│   ├── Add trip
│   └── Trip detail
│
├── 📊 Analytics
│   ├── Overview stats
│   ├── Country breakdown
│   └── Timeline
│
├── 👨‍👩‍👧‍👦 Family (if premium)
│   ├── Family member list
│   └── Member detail
│
└── ⚙️ Settings
    ├── Profile
    ├── Notifications
    ├── Jurisdictions
    ├── Import (photos, calendar)
    └── Export (PDF, CSV)
```

### 11.4 Key Screens

**Home Screen (Main Dashboard)**
```
┌─────────────────────────────────┐
│ Good morning, John        [👤]  │
├─────────────────────────────────┤
│                                 │
│   ┌─────────────────────────┐   │
│   │    🇪🇺 SCHENGEN          │   │
│   │                         │   │
│   │      42 / 90            │   │
│   │      days used          │   │
│   │                         │   │
│   │  ████████░░░░░░░░░░░░   │   │
│   │                         │   │
│   │  48 days remaining      │   │
│   └─────────────────────────┘   │
│                                 │
│   Last location: Paris, France  │
│   Updated: 2 hours ago          │
│                                 │
├─────────────────────────────────┤
│  Recent Trips                   │
│                                 │
│  🇫🇷 France                     │
│     Nov 15 - Present            │
│                                 │
│  🇪🇸 Spain                      │
│     Oct 1-8, 2025               │
│                                 │
│  [ View All Trips ]             │
│                                 │
├─────────────────────────────────┤
│  [ + Add Trip ]  [ 📍 Check In ]│
└─────────────────────────────────┘
│ 🏠      🗺️      📊      ⚙️      │
└─────────────────────────────────┘
```

---

## 12. Testing Checklist

### 12.1 Unit Tests

- [ ] Schengen calculation matches EU calculator
- [ ] UK SRT night counting is accurate
- [ ] US Substantial Presence weighted formula correct
- [ ] Date range overlap detection works
- [ ] Trip merging/extending logic correct

### 12.2 Integration Tests

- [ ] API sync works with slow network
- [ ] Offline mode stores data correctly
- [ ] Sync resolves conflicts properly
- [ ] Push notification registration works
- [ ] Photo import extracts GPS correctly

### 12.3 E2E Tests

- [ ] New user onboarding flow
- [ ] Add trip manually
- [ ] GPS auto-tracking creates trip
- [ ] Photo import creates trips
- [ ] Family member CRUD
- [ ] Export PDF report
- [ ] Passport Control Mode displays correctly

### 12.4 Device Tests

- [ ] iOS 15+ compatibility
- [ ] Android 10+ compatibility
- [ ] Tablet layouts
- [ ] Dark mode
- [ ] Accessibility (VoiceOver, TalkBack)
- [ ] Low battery mode impact
- [ ] Background task reliability

---

## 13. Task Tracker

### Status Legend
- `[ ]` Not started
- `[~]` In progress
- `[x]` Complete
- `[!]` Blocked
- `[-]` Cancelled

### Phase 1: Core Native App

#### 1.0 Backend API (WordPress) ✅ COMPLETE
- [x] Create mobile API class (`class-r2f-schengen-mobile-api.php`)
- [x] Add `/app/status` endpoint (version check, maintenance mode)
- [x] Add `/sync` endpoint (batch sync for mobile)
- [x] Add `/changes` endpoint (pull sync)
- [x] Add `/passport-control` endpoint (optimized border display)
- [x] Add `/device/register` endpoint (push notifications)
- [x] Add `/device/unregister` endpoint
- [x] Add `/locations/batch` endpoint (bulk location upload)
- [x] Add devices table to database schema
- [x] Update plugin version to 1.6.0
- [x] Create shared TypeScript types (`mobile/shared/types.ts`)
- [x] Create API reference documentation (`mobile/shared/api-reference.md`)

#### 1.1 Project Setup
- [ ] Create iOS project (Xcode)
- [ ] Create Android project (Android Studio)
- [x] Set up shared data models (TypeScript reference complete)
- [ ] Configure CI/CD pipeline
- [ ] Set up TestFlight / Play Store internal testing

#### 1.2 Authentication
- [ ] WordPress JWT auth integration
- [ ] Secure token storage (Keychain/Keystore)
- [ ] Auto-refresh token logic
- [ ] Logout/session management

#### 1.3 Local Database
- [ ] Define schema (trips, locations, settings)
- [ ] Implement iOS database (Realm or CoreData)
- [ ] Implement Android database (Room)
- [ ] Migration strategy

#### 1.4 Background GPS
- [ ] iOS: Implement BackgroundLocationManager
- [ ] iOS: Configure background task scheduling (3x daily)
- [ ] Android: Implement LocationWorker
- [ ] Android: Configure WorkManager scheduling
- [ ] Reverse geocoding integration
- [ ] Auto trip creation/extension logic
- [ ] Battery usage optimization

#### 1.5 Passport Control Mode
- [x] Design UI (large status, recent entries) - see Section 4.2
- [x] Backend API ready (`/passport-control` endpoint)
- [ ] Implement iOS view
- [ ] Implement Android view
- [ ] Offline data display
- [ ] Timestamp/verification display

#### 1.6 API Integration
- [x] Backend sync endpoints ready
- [ ] Implement API client (iOS)
- [ ] Implement API client (Android)
- [ ] Trip CRUD operations
- [ ] Location submission
- [ ] Sync manager (push/pull)
- [ ] Conflict resolution

#### 1.7 Offline Support
- [ ] Queue local changes
- [ ] Detect network status
- [ ] Auto-sync when online
- [ ] Display sync status

#### 1.8 Push Notifications
- [ ] iOS: APNs registration
- [ ] Android: FCM registration
- [ ] Server subscription endpoint
- [ ] Notification handlers
- [ ] Deep linking from notifications

### Phase 2: Smart Integrations

#### 2.1 Photo GPS Import
- [ ] Request photo library permission
- [ ] Scan photos for GPS metadata
- [ ] Group into detected trips
- [ ] Preview/confirm UI
- [ ] Import to database

#### 2.2 Calendar Integration
- [ ] Request calendar permission
- [ ] Scan events for travel keywords
- [ ] Extract location data
- [ ] Preview/confirm UI
- [ ] Import to database

#### 2.3 Widgets
- [ ] iOS: Small widget (2x2)
- [ ] iOS: Medium widget (4x2)
- [ ] iOS: Large widget (4x4)
- [ ] Android: Small widget
- [ ] Android: Large widget
- [ ] Widget data refresh

### Phase 3: Multi-Jurisdiction

#### 3.1 Jurisdiction Engine
- [ ] Define JurisdictionConfig protocol
- [ ] Implement Schengen calculator (migrate)
- [ ] Implement UK SRT calculator
- [ ] Implement Portugal 183-day
- [ ] Implement Spain 183-day
- [ ] Implement US SPT calculator

#### 3.2 UI Updates
- [ ] Jurisdiction selector
- [ ] Multi-jurisdiction dashboard
- [ ] Jurisdiction-specific alerts

### Phase 4: Premium Features

#### 4.1 Family Sync
- [ ] Fetch family members from API
- [ ] Display family dashboard
- [ ] Per-member compliance view

#### 4.2 AI Suggestions
- [ ] Fetch suggestions from API
- [ ] Display suggestion cards
- [ ] Trip planning integration

#### 4.3 Proof of Presence
- [ ] Define proof sources
- [ ] Capture GPS + photo + calendar proofs
- [ ] Generate presence report
- [ ] Export as PDF

---

## Appendix A: Competitor Reference

| App | Price | Auto GPS | Multi-Jur | Family | Calendar |
|-----|-------|----------|-----------|--------|----------|
| Monaeo | $79/mo | ✅ | ✅ | ❌ | ❌ |
| Flamingo | $30-120/yr | ✅ | ✅ | ✅ | ❌ |
| TrackingDays | $48/yr | ✅ | ✅ | ❌ | ❌ |
| Schengen Simple | $9/yr | ❌ | ❌ | ❌ | ❌ |
| NomadTracker | Free | ❌ | ✅ | ✅ | Import |
| **Relo2France** | **$39-79/yr** | **✅** | **🔜** | **✅** | **✅** |

---

## Appendix B: File Reference

### Existing PHP Files
```
relo2france-schengen-tracker/
├── relo2france-schengen-tracker.php    # Main plugin file
└── includes/
    ├── class-r2f-schengen-api.php      # REST API endpoints
    ├── class-r2f-schengen-core.php     # Core initialization
    ├── class-r2f-schengen-location.php # Location tracking
    ├── class-r2f-schengen-calendar.php # Calendar sync
    ├── class-r2f-schengen-family.php   # Family tracking
    ├── class-r2f-schengen-notifications.php # Push notifications
    ├── class-r2f-schengen-schema.php   # Database schema
    └── class-r2f-schengen-alerts.php   # Email alerts
```

### Existing React Components
```
france-relocation-member-tools/portal/src/components/schengen/
├── SchengenDashboard.tsx      # Main dashboard
├── TripList.tsx               # Trip list view
├── TripForm.tsx               # Add/edit trip
├── CalendarView.tsx           # Calendar visualization
├── CalendarSync.tsx           # Google/Microsoft sync
├── LocationTracker.tsx        # GPS check-in
├── LocationDetectionBanner.tsx # Smart detection
├── FamilyManager.tsx          # Family tracking
├── AnalyticsDashboard.tsx     # Analytics with charts
├── AISuggestions.tsx          # AI suggestions
├── CSVImportExport.tsx        # CSV import/export
├── ReportExport.tsx           # PDF generation
└── NotificationCenter.tsx     # Notification UI
```

---

*This document should be updated as development progresses. Mark tasks complete in Section 13 and add notes about decisions, blockers, or changes.*
