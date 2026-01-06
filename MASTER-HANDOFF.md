# Relo2France Master Handoff Document

**Last Updated:** January 6, 2026
**Repository:** Relo2France/france-relocation-assistant
**Active Branch:** `claude/review-redesign-handoff-IANoS`

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Current Status](#2-current-status)
3. [Component Versions](#3-component-versions)
4. [Feature Status](#4-feature-status)
5. [Architecture](#5-architecture)
6. [Key Files Reference](#6-key-files-reference)
7. [API Endpoints](#7-api-endpoints)
8. [Database Schema](#8-database-schema)
9. [Build Commands](#9-build-commands)
10. [Configuration Required](#10-configuration-required)
11. [Known Issues](#11-known-issues)
12. [Testing Checklists](#12-testing-checklists)
13. [Session History](#13-session-history)
14. [Lessons Learned](#14-lessons-learned)
15. [Future Roadmap](#15-future-roadmap)
16. [Appendix A: Brand Guidelines](#appendix-a-brand-guidelines)
17. [Appendix B: Adding New Portal Menu Items](#appendix-b-adding-new-portal-menu-items)
18. [Appendix C: Multi-Jurisdiction Expansion Plan](#appendix-c-multi-jurisdiction-expansion-plan)

---

## 1. Project Overview

**Relo2France** is a WordPress-based platform helping Americans relocate to France. It consists of:

| Component | Description |
|-----------|-------------|
| **Main Plugin** | AI chat, MemberPress integration, auth flows |
| **Member Tools Plugin** | React SPA portal, profiles, documents, tasks |
| **MyTravelStatus Plugin** | 90/180-day compliance tracker (premium feature) |
| **Theme** | Custom WordPress theme |
| **GitHub Sync Plugin** | Deployment sync utility |
| **MyTravelStatus Native Apps** | iOS/Android apps (in development) |

### Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | React 18 + TypeScript + Vite + Tailwind CSS |
| State | Zustand (global), React Query (server) |
| Backend | WordPress REST API (PHP) |
| Database | MySQL with custom tables (prefix `wp_framt_`, `wp_fra_`) |
| Auth | WordPress cookie auth + nonce |
| Mobile | Swift/SwiftUI (iOS), Kotlin/Jetpack Compose (Android) |

---

## 2. Current Status

### What's Complete
- Core WordPress plugins (Main, Member Tools, Theme)
- React member portal with profile, documents, tasks
- Schengen Tracker v1.5.0 with all planned features
- Native app Phase 1 & 2 (partial) complete

### What's In Progress
- Native app widgets (iOS/Android)
- Multi-jurisdiction expansion (see Appendix C for detailed 6-phase plan)
  - Phase 1: Foundation + France tax - **Complete**
  - Phase 2: Top Schengen EU Countries (multi-factor) - **Complete**
  - Phase 3-5: 15+ jurisdictions, PDF reports, native sync
  - Phase 6: Other relo sites (Relo2Spain, Relo2Portugal, etc.)

### Recently Completed
- Push notifications (APNs for iOS, FCM for Android) - Full implementation
- iOS App Store Review Readiness - Complete compliance implementation
- Android Google Play Compliance - Complete compliance implementation

### Recent Session Work
- iOS: Privacy manifest, permissions, StoreKit subscriptions, demo mode, data export/delete
- Android: Privacy settings, Google Play Billing, photo picker, analytics, demo mode
- Both: Background location OFF by default, educational disclosure screens

---

## 3. Component Versions

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v3.6.4 | Active |
| Member Tools Plugin | v2.1.0 | Active |
| React Portal | v2.1.0 | Active |
| Theme | v1.2.4 | Active |
| **MyTravelStatus Plugin** | **v1.7.1** | **Active** |
| MyTravelStatus iOS | v1.0.0 | In Development |
| MyTravelStatus Android | v1.0.0 | In Development |

---

## 4. Feature Status

### Member Portal Features

| Feature | Status | Location |
|---------|--------|----------|
| User Profile (30+ fields) | Complete | `ProfileView.tsx` |
| Document Management | Complete | `DocumentsView.tsx` |
| Task Management | Complete | `TasksView.tsx` |
| Project Management | Complete | `ProjectsView.tsx` |
| Visa Checklists | Complete | `ChecklistsView.tsx` |
| AI Chat Interface | Complete | `shortcode-template.php` |
| Personalized Guides | Complete | `GuidesView.tsx` |

### Schengen Tracker Features

| Phase | Feature | Status |
|-------|---------|--------|
| 1.0 | Core trip CRUD, 90/180 calculation | Complete |
| 1.1 | Browser Geolocation Check-in | Complete |
| 1.2 | Smart Location Detection | Complete |
| 2 | Calendar Sync (Google/Microsoft OAuth) | Complete |
| 3 | Multi-jurisdiction support | Complete |
| 4 | Professional PDF reports | Complete |
| 5 | Push + In-app notifications | Complete |
| 6 | CSV import/export | Complete |
| 6 | PWA manifest + service worker | Complete |
| 7 | AI-powered trip suggestions | Complete |
| 7 | Family member tracking | Complete |
| 7 | Analytics dashboard | Complete |

### Native App Features (MyTravelStatus)

| Phase | Feature | Status |
|-------|---------|--------|
| 1.0 | Backend Mobile API | Complete |
| 1.1 | iOS/Android Project Setup | Complete |
| 1.2 | Authentication (JWT) | Complete |
| 1.3 | Local Database | Complete |
| 1.4 | Background GPS (3x daily) | Complete |
| 1.5 | Passport Control Mode | Complete |
| 1.6 | API Integration | Complete |
| 1.7 | Offline Support | Complete |
| 1.8 | Push Notifications (APNs/FCM) | Complete |
| 2.1 | Photo GPS Import | Complete |
| 2.2 | Calendar Integration | Complete |
| 2.3 | Widgets | Pending |
| 3.x | Multi-Jurisdiction Engine | Pending |
| 4.x | Premium Features | Pending |

### App Store / Play Store Compliance

| Feature | iOS | Android |
|---------|-----|---------|
| Privacy Settings Manager | Complete | Complete |
| Background Location (opt-in) | Complete | Complete |
| Location Education Screen | Complete | Complete |
| Photo Picker (limited access) | Complete | Complete |
| EXIF Confirmation Dialog | Complete | Complete |
| Analytics (opt-in only) | Complete | Complete |
| Subscriptions | Complete (StoreKit 2) | Complete (Google Play Billing) |
| Restore Purchases | Complete | Complete |
| Demo Mode for Reviewers | Complete | Complete |
| Data Export (JSON) | Complete | Complete |
| Account Deletion | Complete | Complete |
| Legal Disclaimer | Complete | Complete |
| Privacy Policy Links | Complete | Complete |
| Compliance Documentation | APP-REVIEW-READINESS.md | PLAY-STORE-COMPLIANCE.md |

---

## 5. Architecture

### Portal Component Tree

```
Member Portal (React SPA)
├── App.tsx (routing)
├── Layout
│   ├── Sidebar.tsx (navigation)
│   ├── Header.tsx
│   └── MainContent
│
├── Views
│   ├── DashboardView.tsx
│   ├── ProfileView.tsx
│   │   └── PersonalSection.tsx (2-column grid layout)
│   ├── DocumentsView.tsx
│   ├── TasksView.tsx
│   ├── ProjectsView.tsx
│   ├── ChecklistsView.tsx
│   └── GuidesView.tsx
│
└── Schengen Tracker
    ├── TravelStatusDashboard.tsx
    ├── Header
    │   ├── NotificationCenter.tsx
    │   ├── ReportExport.tsx
    │   └── Add Trip → TripForm modal
    ├── Status Cards (DayCounter, Days Remaining, etc.)
    ├── LocationDetectionBanner.tsx
    ├── Tabs
    │   ├── TripList.tsx
    │   ├── CalendarView.tsx
    │   ├── CalendarSync.tsx
    │   ├── PlanningTool.tsx + AISuggestions.tsx
    │   ├── LocationTracker.tsx
    │   ├── FamilyManager.tsx
    │   ├── AnalyticsDashboard.tsx
    │   └── Settings + CSVImportExport.tsx
    └── PWAPrompt.tsx
```

### Native App Architecture

```
Native App (iOS/Android)
├── UI Layer (SwiftUI / Jetpack Compose)
│   ├── Views/Screens
│   ├── ViewModels
│   └── Navigation
├── Domain Layer
│   ├── Use Cases
│   ├── Models
│   └── Calculators (Schengen, UK SRT, US SPT)
├── Data Layer
│   ├── Local Database (SQLite)
│   ├── API Client (WordPress REST)
│   ├── Sync Manager
│   └── Background Services
└── Platform Services
    ├── Location Manager (3x daily)
    ├── Photo Library
    ├── Calendar Access
    ├── Push Notifications
    └── Background Tasks
```

---

## 6. Key Files Reference

### PHP Backend

| Purpose | Location |
|---------|----------|
| Portal API (40+ endpoints) | `france-relocation-member-tools/includes/class-framt-portal-api.php` |
| Portal Settings | `france-relocation-member-tools/includes/class-framt-portal-settings.php` |
| Portal Template | `france-relocation-member-tools/templates/template-portal.php` |
| Schengen API | `mytravelstatus/includes/class-mts-api.php` |
| Schengen Mobile API | `mytravelstatus/includes/class-mts-mobile-api.php` |
| Schengen Location | `mytravelstatus/includes/class-mts-location.php` |
| Schengen Family | `mytravelstatus/includes/class-mts-family.php` |
| Schengen Notifications | `mytravelstatus/includes/class-mts-notifications.php` |
| Schengen Calendar | `mytravelstatus/includes/class-mts-calendar.php` |
| Schengen Mobile Push | `mytravelstatus/includes/class-mts-mobile-push.php` |
| Schengen Schema | `mytravelstatus/includes/class-mts-schema.php` |

### React Frontend

| Purpose | Location |
|---------|----------|
| App Routes | `portal/src/App.tsx` |
| API Client | `portal/src/api/client.ts` |
| React Query Hooks | `portal/src/hooks/useApi.ts` |
| TypeScript Types | `portal/src/types/index.ts` |
| Zustand Store | `portal/src/store/index.ts` |
| Sidebar Navigation | `portal/src/components/layout/Sidebar.tsx` |
| Profile Section | `portal/src/components/profile/PersonalSection.tsx` |
| Schengen Dashboard | `portal/src/components/travel-status/TravelStatusDashboard.tsx` |
| Schengen Utils | `portal/src/utils/travelStatusUtils.ts` |

### Native Apps

| Purpose | Location |
|---------|----------|
| iOS App Entry | `mobile/ios/MyTravelStatus/App/MyTravelStatusApp.swift` |
| iOS App Delegate | `mobile/ios/MyTravelStatus/App/AppDelegate.swift` |
| iOS Push Manager | `mobile/ios/MyTravelStatus/Services/PushNotificationManager.swift` |
| iOS API Client | `mobile/ios/MyTravelStatus/Services/APIClient.swift` |
| iOS Location Manager | `mobile/ios/MyTravelStatus/Services/BackgroundLocationManager.swift` |
| iOS Passport Control | `mobile/ios/MyTravelStatus/Views/PassportControl/PassportControlView.swift` |
| Android Main | `mobile/android/app/src/main/java/com.mytravelstatus.app/MainActivity.kt` |
| Android API Client | `mobile/android/.../network/ApiClient.kt` |
| Android FCM Service | `mobile/android/.../service/FCMService.kt` |
| Android Location Worker | `mobile/android/.../service/LocationWorker.kt` |
| Shared Types | `mobile/shared/types.ts` |
| API Reference | `mobile/shared/api-reference.md` |

### Native App Compliance Files

| Purpose | iOS | Android |
|---------|-----|---------|
| Privacy Settings | `Services/PrivacySettings.swift` | `service/PrivacySettings.kt` |
| Analytics Manager | `Services/AnalyticsManager.swift` | `service/AnalyticsManager.kt` |
| Subscription Manager | `Services/SubscriptionManager.swift` | `service/SubscriptionManager.kt` |
| Demo Mode Service | `Services/DemoModeService.swift` | `service/DemoModeService.kt` |
| Data Management | `Services/DataManagementService.swift` | `service/DataManagementService.kt` |
| Photo Picker | `Services/PhotoPickerService.swift` | `service/PhotoPickerService.kt` |
| Location Education UI | `Views/Privacy/LocationEducationView.swift` | `ui/screens/LocationEducationScreen.kt` |
| Privacy Settings UI | `Views/Settings/PrivacySettingsView.swift` | `ui/screens/PrivacySettingsScreen.kt` |
| Subscription UI | `Views/Subscription/SubscriptionView.swift` | `ui/screens/SubscriptionScreen.kt` |
| Privacy Manifest | `PrivacyInfo.xcprivacy` | N/A |
| Permissions Config | `Info.plist` | `AndroidManifest.xml` |
| Compliance Docs | `APP-REVIEW-READINESS.md` | `PLAY-STORE-COMPLIANCE.md` |

---

## 7. API Endpoints

### Portal API (`/wp-json/framt/v1/`)

```
# Core CRUD
GET/POST   /projects
GET/PUT/DELETE /projects/{id}
GET/POST   /tasks
GET/PUT/DELETE /tasks/{id}
GET/POST   /notes
GET/POST   /files

# Profile
GET/PUT    /profile
GET        /profile/completion

# Checklists
GET/PUT    /checklists/{type}

# Documents
POST       /documents/generate
GET        /documents/preview

# Chat
POST       /chat/message
POST       /guides/chat
```

### Schengen API (`/wp-json/mts/v1/`)

```
# Trips
GET/POST   /trips
GET/PUT/DELETE /trips/{id}
GET        /trips/export (CSV)
POST       /trips/import (CSV)

# Location
POST       /location
GET        /location/today
GET        /location/history
GET        /location/detect

# Family
GET/POST   /family
GET/PUT/DELETE /family/{id}
GET        /family/{id}/summary
GET        /family/summaries

# Calendar
GET        /calendar/providers
GET        /calendar/connections
POST       /calendar/connect
DELETE     /calendar/disconnect/{id}
POST       /calendar/sync/{id}
GET        /calendar/events
POST       /calendar/events/import
POST       /calendar/ical

# Notifications
GET        /notifications
GET        /notifications/unread
POST       /notifications/{id}/read
POST       /notifications/read-all
DELETE     /notifications/{id}

# Push
GET        /push/status
POST       /push/subscribe
POST       /push/unsubscribe
GET        /push/vapid-key

# Analytics
GET        /analytics
GET        /analytics/patterns
GET        /analytics/history
GET        /analytics/monthly
GET        /analytics/export

# Suggestions
GET        /suggestions

# Mobile App
GET        /app/status
POST       /sync
GET        /changes
GET        /passport-control
POST       /device/register
POST       /device/unregister
POST       /locations/batch
GET        /push/status
POST       /push/test
```

---

## 8. Database Schema

### Member Tools Tables (`wp_framt_`)

| Table | Purpose |
|-------|---------|
| `projects` | User projects |
| `tasks` | Task management |
| `task_checklists` | Checklist items |
| `files` | Document storage |
| `notes` | User notes |
| `messages` | AI chat messages |
| `message_replies` | Chat replies |

### Schengen Tracker Tables (`wp_fra_`)

| Table | Purpose |
|-------|---------|
| `schengen_trips` | Trip records |
| `schengen_locations` | GPS readings |
| `schengen_family_members` | Family profiles |
| `schengen_trip_travelers` | Trip-to-family junction |
| `schengen_analytics` | Historical compliance snapshots |
| `push_subscriptions` | Push notification subscriptions |
| `notifications` | In-app notifications |
| `devices` | Mobile device registrations |

### User Meta Keys (`fra_`)

```
fra_profile_*          - Profile fields (30+)
fra_checklist_*        - Checklist progress
fra_dependents         - Array data
fra_previous_visas     - Array data
fra_chat_profile_hash  - Profile change tracking
```

---

## 9. Build Commands

```bash
# Build React portal (required before deploying)
cd france-relocation-member-tools/portal && npm run build

# Run dev server
cd france-relocation-member-tools/portal && npm run dev

# Lint TypeScript/React
cd france-relocation-member-tools/portal && npm run lint

# Type check
cd france-relocation-member-tools/portal && npx tsc --noEmit

# Verify PHP syntax
php -l mytravelstatus/includes/class-mts-api.php

# Create deployment zip
cd france-relocation-member-tools && zip -r ../france-relocation-member-tools.zip . \
  -x "portal/node_modules/*" -x "*.git*" -x "portal/.vite/*"
```

---

## 10. Configuration Required

### Web Push Notifications (Production)

**Step 1: Install dependencies**
```bash
cd mytravelstatus
composer install
```

**Step 2: Generate VAPID keys** at https://vapidkeys.com/

**Step 3: Configure keys**
```php
// wp-config.php or WordPress options
update_option('mts_vapid_public_key', 'your-public-key');
update_option('mts_vapid_private_key', 'your-private-key');
```

Note: Without Composer dependencies, push notifications will store in DB for frontend polling (works but no background delivery).

### Mobile Push Notifications (APNs/FCM)

Configure in WordPress admin: Settings → MyTravelStatus → Mobile Push Notifications

**iOS (APNs):**
1. Create an APNs Key in App Store Connect → Keys
2. Download the .p8 file and store securely (outside web root)
3. Configure settings:
```php
update_option('mts_apns_team_id', 'XXXXXXXXXX');        // 10-char team ID
update_option('mts_apns_key_id', 'XXXXXXXXXX');         // 10-char key ID
update_option('mts_apns_bundle_id', 'com.mytravelstatus.app');
update_option('mts_apns_key_path', '/secure/path/AuthKey.p8');
update_option('mts_apns_sandbox', '1');  // '1' for dev, '0' for production
```

**Android (FCM):**
1. Create a Firebase project and download service account JSON
2. Enable Firebase Cloud Messaging API
3. Configure settings:
```php
update_option('mts_fcm_project_id', 'mytravelstatus-xxxxx');
update_option('mts_fcm_service_account_path', '/secure/path/firebase-sa.json');
```

### Calendar OAuth

Configure in WordPress admin: Settings → Schengen Tracker

**Google Calendar:**
- Authorized redirect URIs must include BOTH:
  - `https://relo2france.com/wp-json/mts/v1/calendar/callback`
  - `https://www.relo2france.com/wp-json/mts/v1/calendar/callback`

```php
update_option('mts_google_client_id', 'your-client-id');
update_option('mts_google_client_secret', 'your-client-secret');
```

---

## 11. Known Issues

### Active Issues

| Issue | Severity | Notes |
|-------|----------|-------|
| esbuild vulnerability | Low | Dev-only (vite dev server), doesn't affect production builds |

### Resolved Issues

| Issue | Resolution | Commit |
|-------|------------|--------|
| npm audit (d3-color ReDoS) | Fixed via npm overrides for d3-color ^3.1.0 | Jan 5, 2026 |
| PWA Icons missing | Created 192x192 and 512x512 icons from logo | Jan 5, 2026 |
| Web Push simplified | Added `minishlink/web-push` integration via Composer | Jan 5, 2026 |
| ESLint warnings | Fixed all lint warnings across portal | Jan 5, 2026 |
| Buttons dark navy instead of green | Scoped to `#fra-portal-root` with `!important` | `9493c95` |
| Analytics 404 error | Legacy routes always registered | `f1794b8` |
| FRAMT_URL undefined | Changed to `FRAMT_PLUGIN_URL` | `6f12426` |
| PersonalSection layout overflow | Rewrote with simple 2-column grid | `4aeb804` |

### CSS Conflict Note

The WordPress theme defines global `.btn-primary` with navy color. Portal buttons are scoped to `#fra-portal-root` to avoid conflicts. New button classes may need similar scoping.

---

## 12. Testing Checklists

### Profile PersonalSection
- [ ] Fields display in 2-column layout on desktop
- [ ] Fields stack to single column on mobile
- [ ] No horizontal overflow/scrolling
- [ ] Form submits correctly
- [ ] Data loads from profile correctly
- [ ] Save button shows loading/success/error states

### Schengen Notifications
- [ ] Bell icon shows in header
- [ ] Unread count badge updates
- [ ] Dropdown opens on click
- [ ] Mark individual as read works
- [ ] Mark all as read works
- [ ] Delete notification works

### Schengen CSV Import/Export
- [ ] Export downloads CSV file
- [ ] CSV contains correct format
- [ ] Import from file works
- [ ] Import from paste works
- [ ] Skip duplicates option works
- [ ] Invalid rows show errors

### Schengen Family Tracking
- [ ] Family tab appears in navigation
- [ ] Add family member form works
- [ ] Compliance summary per member
- [ ] Edit/delete family member works
- [ ] Premium gating works

### Schengen Analytics
- [ ] Overview tab shows stats
- [ ] Countries tab shows patterns
- [ ] Compliance History chart works
- [ ] Monthly breakdown works
- [ ] Period selector works
- [ ] Export button downloads CSV

### Native App (iOS/Android)
- [ ] Background GPS captures 3x daily
- [ ] Passport Control Mode displays correctly
- [ ] Offline mode shows cached data
- [ ] Sync works when online
- [ ] Photo import extracts GPS
- [ ] Calendar import detects trips

### Push Notifications
- [ ] iOS: Request notification permission on first login
- [ ] iOS: Device token registers with backend
- [ ] iOS: Test notification arrives on device
- [ ] iOS: Notification tap opens correct screen
- [ ] Android: FCM token saves to SharedPreferences
- [ ] Android: Token registers with backend on login
- [ ] Android: Test notification displays with correct icon
- [ ] Android: Notification tap navigates to app
- [ ] Backend: `/push/status` shows registered devices
- [ ] Backend: `/push/test` sends notification to all devices
- [ ] Logout clears device registration

### App Store / Play Store Compliance

#### Background Location
- [ ] Background location is OFF by default on fresh install
- [ ] Educational screen (3 pages) shown before permission request
- [ ] User can decline and use manual check-in instead
- [ ] App functions fully with foreground-only permission
- [ ] Can be toggled off in Settings → Privacy & Data
- [ ] Only country names stored (never precise coordinates)

#### Photo Import
- [ ] Uses system Photo Picker (not full gallery access)
- [ ] Only selected photos are processed
- [ ] Confirmation dialog appears before reading EXIF
- [ ] User can cancel metadata extraction
- [ ] Manual entry alternative is offered

#### Analytics
- [ ] Analytics is OFF by default
- [ ] Toggle in Settings → Privacy & Data works
- [ ] No analytics events fire when disabled
- [ ] No PII in analytics when enabled

#### Subscriptions
- [ ] Products load from App Store / Play Store
- [ ] Purchase flow completes
- [ ] Restore Purchases button works
- [ ] Manage Subscription opens store
- [ ] Auto-renewal disclosure text visible
- [ ] Terms/Privacy links work

#### Demo Mode (Reviewer Access)
- [ ] Demo credentials work: `demo@mytravelstatus.com` / `demo123`
- [ ] All premium features accessible
- [ ] Demo data loads correctly
- [ ] iOS: Shake activation (5 times in Settings)
- [ ] Android: Tap version 5 times
- [ ] Deep link `mytravelstatus://demo` works

#### Data Management
- [ ] Export My Data generates valid JSON
- [ ] Share sheet opens with export file
- [ ] Delete Account shows two-step confirmation
- [ ] Typing DELETE confirms deletion
- [ ] All local data cleared after deletion

---

## 13. Session History

### January 6, 2026 (Session 6)
- **Multi-Jurisdiction Expansion Phase 2 Implementation** - EU Multi-Factor Rules:
  - **Database Schema v1.7.1:**
    - Added multi-factor `rule_config` for Germany, Italy, Netherlands tax rules
    - Migration function to update existing installations
  - **Multi-Factor Configurations:**
    - **Germany (de_tax):** Permanent home + habitual abode (any = residency trigger)
    - **Italy (it_tax):** Registered residence (Anagrafe) + domicile + 183-day presence (any = residency trigger)
    - **Netherlands (nl_tax):** Permanent home + vital interests + habitual abode (weighted assessment)
  - **New API Endpoints:**
    - `GET/PUT /jurisdictions/user/{code}/factors` - User multi-factor responses
    - `POST /jurisdictions/bulk` - Bulk enable/disable jurisdictions
    - `GET /jurisdictions/eu-tax` - Get all EU tax jurisdictions
  - **TypeScript Types:**
    - Added `ResidencyFactor`, `MultiFactorRuleConfig` interfaces
    - Added `UserFactorResponses`, `EUTaxJurisdictionsResponse` types
    - Added `BulkUpdateJurisdictionsRequest/Response` types
  - **React Hooks:**
    - Added `useUserFactors()` for multi-factor responses
    - Added `useUpdateUserFactors()` mutation
    - Added `useEUTaxJurisdictions()` for bulk operations
    - Added `useBulkUpdateJurisdictions()` mutation
  - **React Components:**
    - Created `MultiFactorIndicators.tsx` - Toggle UI for residency factors with save
    - Created `JurisdictionSettings.tsx` - Full settings panel with EU bulk toggle
    - Integrated `MultiFactorIndicators` into `JurisdictionCard` expanded view

### January 5, 2026 (Session 5)
- **Multi-Jurisdiction Expansion Phase 1 Implementation** - Backend + Frontend:
  - **Database Schema v1.7.0:**
    - Added `user_jurisdictions` table for user tracking preferences
    - Added `compliance_snapshots` table for historical compliance tracking
    - Extended `jurisdiction_rules` with `category`, `rule_config`, `country_code`, `flag_emoji`
    - Database migration for existing installations
  - **Calculator Engine (PHP):**
    - Implemented multi-year counting method (Ireland 183/280 rule)
    - Implemented weighted multi-year counting (US SPT with weights 1.0/⅓/⅙)
    - Added 14 tax residency jurisdictions (France, Spain, Portugal, Germany, Italy, Netherlands, Ireland, US SPT, Mexico, Japan, Singapore, Australia, New Zealand, Canada)
    - Updated `calculate_summary()` to return breakdown data for complex rules
  - **TypeScript Types:**
    - Added `JurisdictionCategory`, `CountingMethod` extended types
    - Added `WeightedBreakdown`, `MultiYearBreakdown` interfaces
    - Added `UserJurisdiction`, `ComplianceSnapshot`, `ComplianceAlert` types
    - Added `ComplianceOverview` response type
  - **React Hooks:**
    - Extended `useJurisdictionsByCategory()` for category filtering
    - Added `useUserJurisdictions()` for user preferences
    - Added `useComplianceOverview()` for alerts dashboard
    - Added `useComplianceHistory()` for historical snapshots
  - **React Components:**
    - Enhanced `JurisdictionOverview.tsx` with category tabs (visa/tax/immigration/custom)
    - Added flag emoji support and enhanced Add Jurisdiction modal
    - Updated `JurisdictionCard` with weighted/multi-year breakdown displays
    - Created `ComplianceQuickView.tsx` stacked card component with alerts

### January 5, 2026 (Session 4)
- **Multi-Jurisdiction Expansion Planning** - Complete documentation:
  - Added Appendix C: Multi-Jurisdiction Expansion Plan
  - Documented 15+ jurisdictions with rules (France, Spain, UK SRT, US SPT, etc.)
  - Created 6-phase development schedule with effort estimates
  - Designed Compliance Quick View UI specification
  - Designed Auditable PDF Report format
  - Added database schema for jurisdictions, compliance snapshots, UK ties
  - Added API endpoint specifications
  - Created development checklists and notes sections per phase
  - Phase 6 reserved for other relo sites (Relo2Spain, Relo2Portugal, etc.)

### January 5, 2026 (Session 3)
- **iOS App Store Review Readiness** - Complete implementation:
  - `PrivacyInfo.xcprivacy` - Privacy manifest required by Apple
  - `Info.plist` - All permission usage strings with user-friendly descriptions
  - `PrivacySettings.swift` - Privacy preferences manager (bg location OFF by default)
  - `AnalyticsManager.swift` - Privacy-focused analytics (opt-in only, no PII)
  - `SubscriptionManager.swift` - StoreKit 2 auto-renewable subscriptions
  - `DemoModeService.swift` - Reviewer demo mode (credentials, shake, URL scheme)
  - `DataManagementService.swift` - Data export (JSON) and account deletion
  - `PhotoPickerService.swift` - PHPicker with EXIF confirmation dialog
  - `LocationEducationView.swift` - 3-page background location disclosure
  - `PrivacySettingsView.swift` - Privacy settings UI with legal disclaimer
  - `SubscriptionView.swift` - Subscription UI with full App Store disclosures
  - `APP-REVIEW-READINESS.md` - Complete compliance documentation

- **Android Google Play Compliance** - Complete implementation:
  - `PrivacySettings.kt` - Privacy preferences manager (StateFlow-based)
  - `AnalyticsManager.kt` - Privacy-focused analytics (opt-in only)
  - `SubscriptionManager.kt` - Google Play Billing Library v6.1.0
  - `DemoModeService.kt` - Reviewer demo mode (tap version 5x, deep link)
  - `DataManagementService.kt` - Data export and account deletion
  - `PhotoPickerService.kt` - Photo Picker with EXIF confirmation
  - `LocationScheduler.kt` - Updated with PrivacySettings integration
  - `LocationEducationScreen.kt` - 3-page HorizontalPager disclosure
  - `PrivacySettingsScreen.kt` - Privacy settings with legal disclaimer
  - `SubscriptionScreen.kt` - Subscription UI with Play Store disclosures
  - `AndroidManifest.xml` - Documented permissions, deep link, FileProvider
  - `build.gradle.kts` - Added billing and exifinterface dependencies
  - `libs.versions.toml` - Version catalog updates
  - `file_paths.xml` - FileProvider paths for data export
  - `PLAY-STORE-COMPLIANCE.md` - Complete compliance documentation

### January 5, 2026 (Session 2)
- Implemented complete push notification system for native apps
- iOS: PushNotificationManager.swift, AppDelegate.swift, MyTravelStatusApp integration
- Android: FCMService.kt, Firebase dependencies, MainViewModel integration
- Backend: class-mts-mobile-push.php with APNs JWT auth and FCM HTTP v1
- Added admin settings for APNs and FCM configuration
- Added /push/status and /push/test API endpoints

### January 5, 2026 (Session 1)
- Read and consolidated all handoff documents
- Created MASTER-HANDOFF.md
- Fixed known issues (PWA icons, Web Push library, npm vulnerabilities)

### December 29, 2024 (Session 2)
- Fixed button styling (theme CSS override)
- Fixed Analytics API 404
- Redesigned Calendar View UI

### December 29, 2024 (Session 1)
- Completed Phase 7: Analytics Dashboard
- Completed Phase 7: Family Member Tracking
- Added recharts library

### December 28, 2025
- Completed Phase 5: Notifications
- Completed Phase 6: CSV Import/Export
- Completed Phase 6: PWA Support
- Completed Phase 7: AI Suggestions

### PersonalSection Redesign Session
- Rewrote PersonalSection with simple 2-column grid
- Removed complex fieldset/dynamic approach
- Fixed layout overflow issues

---

## 14. Lessons Learned

### Form Layout
1. **Simpler is better** - Complex fieldset/dynamic approaches cause more problems than they solve
2. **Consistent grid columns** - Uniform 2-column layout works better than varying columns per row
3. **Avoid nested containers** - Fieldsets with padding inside accordions inside cards create unpredictable widths
4. **Don't over-engineer forms** - Plain HTML with Tailwind is often sufficient

### CSS Conflicts
1. **Scope portal styles** - Use `#fra-portal-root` selector to avoid WordPress theme conflicts
2. **Use `!important` when needed** - Theme CSS loads after portal CSS, may need override
3. **Explicit colors** - Use hex values instead of CSS variables when theme overrides are problematic

### Development Patterns
1. **Build after TypeScript changes** - Always run `npm run build` before testing
2. **Check legacy route registration** - Portal uses legacy routes that must be registered
3. **Test mobile layouts** - Responsive breakpoints need verification

---

## 15. Future Roadmap

### Short Term (Next Up)
- [x] Complete native app push notifications (APNs/FCM) ✓
- [x] iOS App Store Review Readiness ✓
- [x] Android Google Play Compliance ✓
- [ ] Build iOS/Android widgets
- [ ] App Store / Play Store submission (compliance ready, pending final build & submit)
- [ ] **Phase 1: Multi-Jurisdiction Foundation** (see Appendix C)
  - Jurisdiction configuration schema
  - Multi-jurisdiction calculator engine
  - France 183-day tax rule
  - Compliance Quick View UI

### Medium Term (Phases 2-5)
- [ ] **Phase 2**: Spain, Portugal, Germany, Italy, Netherlands
- [ ] **Phase 3**: UK SRT, US SPT, Canada, Mexico
- [ ] **Phase 4**: Ireland, Japan, Singapore, NZ, Australia + PDF Reports
- [ ] **Phase 5**: Native app sync, widgets, polish
- [ ] Family sync in native apps
- [ ] AI suggestions in native apps

### Long Term (Phase 6+)
- [ ] **Phase 6**: Other relo sites (Relo2Spain, Relo2Portugal, Relo2UK, Relo2Mexico)
- [ ] Multi-user household accounts
- [ ] Travel document management (passport expiry)
- [ ] Integration with visa tracking module
- [ ] Tax advisor sharing portal
- [ ] Proof of presence feature
- [ ] Community features

---

## Appendix A: Brand Guidelines

### Colors
```javascript
const brandColors = {
  blue: "#4A7BA7",      // Primary - headings, links
  gold: "#E5A54B",      // Secondary - CTAs, highlights
  darkText: "#2D3748",  // Body text
  lightGray: "#718096", // Subdued text
  lightBlue: "#EBF4FA"  // Background for cards
};
```

### Status Colors
```javascript
const statusColors = {
  safe: "green",       // < 60 days
  warning: "yellow",   // 60-79 days
  danger: "orange",    // 80-89 days
  critical: "red"      // 90+ days
};
```

---

## Appendix B: Adding New Portal Menu Items

When adding a new menu item to the portal sidebar, ALL locations must be updated:

### PHP Backend
1. `class-framt-portal-settings.php`:
   - Add to `$defaults` array (menu_, label_, icon_)
   - Add to `$bool_fields` and `$text_fields` in `sanitize()`
   - Add to `$tab_fields['menu']` arrays
   - Add to `$menu_items` in `render_menu_tab_content()`

2. `template-portal.php`:
   - Add to `$defaults` array
   - Add to `$menu_items` array

### React Frontend
3. `Sidebar.tsx`:
   - Import icon from `lucide-react`
   - Add to `iconComponents` map
   - Add to `defaultSectionOrder`

4. `App.tsx`:
   - Add route
   - Import view component

5. Run build: `npm run build`

---

*This document consolidates: HANDOFF.md, SCHENGEN-TRACKER-HANDOFF.md, SCHENGEN-APP-HANDOFF.md, handoff-document.md, and HANDOFF-PersonalSection-Redesign.md*

---

## Appendix C: Multi-Jurisdiction Expansion Plan

This appendix contains the complete development plan for expanding MyTravelStatus beyond Schengen 90/180 to support tax residency rules, visa compliance, and complex multi-factor tests for 15+ jurisdictions.

**Target Markets:** Americans relocating to France (primary), with future expansion to other destinations.

---

### C.1 Overview & Goals

#### Business Objectives
1. **Tax Compliance Tracking** - Help users avoid unintended tax residency triggers
2. **Visa Compliance** - Track visa-free stay limits, residence card requirements
3. **Audit-Ready Reports** - Generate professional PDF reports for tax advisors/immigration
4. **Multi-Country Support** - Support top 15 destinations Americans relocate to

#### User Stories
- "As a France resident, I want to know if I'm approaching 183 days in another country so I don't trigger tax obligations there"
- "As a frequent traveler, I want one app to track my compliance status in multiple jurisdictions"
- "As someone meeting with a tax advisor, I want to export a professional report showing my travel history"
- "At passport control, I want a quick view showing I'm compliant with local rules"

---

### C.2 Jurisdiction Rules Reference

#### Tier 1: Simple 183-Day Calendar Year (1-2 days each)

| Country | Tax Trigger | Visa-Free Limit | Notes |
|---------|-------------|-----------------|-------|
| **France** | 183+ days/year | N/A (resident) | Calendar year Jan-Dec |
| **Spain** | 183+ days/year | N/A (resident) | Calendar year |
| **Portugal** | 183+ days/year | N/A (resident) | Calendar year |
| **Germany** | 183+ days/year | N/A (resident) | Calendar year; also permanent home test |
| **Mexico** | 183+ days/year | 180 days visa-free | Calendar year |
| **Singapore** | 183+ days/year | 90 days visa-free | Calendar year |
| **Japan** | 183+ days/year | 90 days visa-free | Calendar year |
| **New Zealand** | 183 days/12 months | 90 days visa-free | Rolling 12-month window |

**Logic:** Reuse 80% of existing Schengen calculator with different thresholds.

#### Tier 2: Multi-Year or Multi-Factor Tests (3-5 days each)

| Country | Primary Test | Secondary Test | Complexity |
|---------|--------------|----------------|------------|
| **Ireland** | 183 days/year | OR 280 days over 2 years | Cumulative check |
| **USA (SPT)** | 31+ days current year | + weighted 3-year calculation | Complex formula |
| **Canada** | 183+ days/year | + significant ties test | Multi-factor |
| **France (full)** | 183 days | OR principal home OR economic center | Multi-factor |
| **UK (visitor)** | 180 days/rolling year | Informal rule | Rolling window |

**USA Substantial Presence Test (SPT) Formula:**
```
Current year days × 1.0
+ Prior year days × 1/3
+ Second prior year days × 1/6
≥ 183 = US tax resident
```

**Ireland Formula:**
```
(Current year ≥ 183) OR (Current year + Prior year ≥ 280)
```

#### Tier 3: Complex Multi-Test Systems (1-2 weeks each)

| Country | Test Name | Components |
|---------|-----------|------------|
| **UK** | Statutory Residence Test (SRT) | 3 automatic overseas tests, 3 automatic UK tests, sufficient ties test |
| **Australia** | Domicile Test | Primary test of resides, 183-day test, superannuation test, Commonwealth test |
| **Netherlands** | Multi-factor | Permanent home, vital interests, habitual abode |
| **Italy** | Multi-factor | Registered residence, domicile, presence |

**UK SRT Summary:**
1. **Automatic Overseas Tests** - If ANY true, automatically non-resident:
   - Resident previous 3 years + <16 days UK
   - Not resident previous 3 years + <46 days UK
   - Leave UK during year + <16 days UK

2. **Automatic UK Tests** - If ANY true, automatically resident:
   - 183+ days in UK
   - Only home in UK
   - Full-time work in UK

3. **Sufficient Ties Test** - Count UK ties, compare to day threshold:
   - Family tie (spouse/children in UK)
   - Accommodation tie (available 91+ days)
   - Work tie (40+ days substantive work)
   - 90-day tie (90+ days either of previous 2 years)
   - Country tie (UK is country of most days)

---

### C.3 Phased Development Schedule

#### Phase 1: Foundation + France Focus (3-4 weeks)

**Objective:** Build multi-jurisdiction engine, France tax compliance, Compliance Quick View UI

| Task | Est. Days | Description |
|------|-----------|-------------|
| Jurisdiction Configuration Schema | 2 | Define JSON/DB schema for jurisdiction rules |
| Multi-Jurisdiction Calculator Engine | 5 | Abstract calculator supporting multiple rule types |
| France 183-Day Tax Rule | 2 | Implement France tax residency (183 days/calendar year) |
| Jurisdiction Selection UI | 2 | User selects which jurisdictions to track |
| Compliance Quick View UI | 3 | Stacked card view per jurisdiction |
| Database Updates | 1 | New tables for jurisdiction tracking |
| API Endpoints | 2 | `/jurisdictions`, `/compliance/summary`, `/compliance/{jurisdiction}` |
| Unit Tests | 2 | Core calculator tests |

**Deliverables:**
- [x] User can enable France tax tracking alongside Schengen
- [x] Dashboard shows compliance status for multiple jurisdictions
- [x] Compliance Quick View available in app

**Development Notes (Phase 1):**
```
Started: January 5, 2026
Completed: January 5, 2026 (Backend + Frontend core)

Implementation highlights:
- Database v1.7.0 with user_jurisdictions, compliance_snapshots tables
- Extended jurisdiction_rules with category, rule_config, country_code, flag_emoji
- Calculator engine supports: rolling, calendar_year, fiscal_year, multi_year, weighted_multi_year
- 14 tax residency jurisdictions pre-populated (FR, ES, PT, DE, IT, NL, IE, US SPT, MX, JP, SG, AU, NZ, CA)
- JurisdictionOverview enhanced with category tabs (visa/tax/immigration/custom)
- ComplianceQuickView component with stacked cards and alert banners
- TypeScript types: WeightedBreakdown, MultiYearBreakdown for complex rules

Key files modified:
- mytravelstatus/includes/class-mts-schema.php (DB_VERSION 1.7.0)
- mytravelstatus/includes/class-mts-jurisdiction.php (calculator engine)
- portal/src/types/index.ts (extended jurisdiction types)
- portal/src/hooks/useApi.ts (new hooks)
- portal/src/api/client.ts (new API endpoints)
- portal/src/components/travel-status/JurisdictionOverview.tsx (enhanced)
- portal/src/components/travel-status/ComplianceQuickView.tsx (new)

Pending for production:
- Backend API endpoints for compliance/overview and compliance/history
- Integration into TravelStatusDashboard main view
```

---

#### Phase 2: Top Schengen Countries (2 weeks)

**Objective:** Add Spain, Portugal, Germany, Italy 183-day rules

| Task | Est. Days | Description |
|------|-----------|-------------|
| Spain 183-Day | 1 | Clone France rule, adjust parameters |
| Portugal 183-Day | 1 | Clone France rule |
| Germany 183-Day | 1.5 | 183-day + permanent home indicator |
| Italy Multi-Factor | 2 | Registered residence, domicile, presence |
| Netherlands Multi-Factor | 2 | Permanent home, vital interests, habitual abode |
| Bulk Enable/Disable | 1 | "Enable all EU countries" toggle |
| Settings UI Updates | 1.5 | Per-jurisdiction settings |

**Deliverables:**
- [ ] Spain, Portugal, Germany available with simple toggle
- [ ] Italy, Netherlands with multi-factor indicators
- [ ] User can enable "all EU countries" in one click

**Development Notes (Phase 2):**
```
Started: ___________
Completed: ___________
Issues encountered:
-
-
-

Blockers resolved:
-
-
```

---

#### Phase 3: UK + Americas (3 weeks)

**Objective:** UK SRT (complex), US SPT, Canada, Mexico

| Task | Est. Days | Description |
|------|-----------|-------------|
| UK SRT Engine | 7 | Full statutory residence test with all components |
| UK Ties Questionnaire | 2 | UI for user to input family, accommodation, work ties |
| UK SRT Result Display | 1 | Clear explanation of which test determined status |
| US SPT Calculator | 3 | Weighted 3-year calculation |
| US SPT Historical Data | 1 | Ensure 3-year trip history available |
| Canada 183 + Ties | 2 | Day count + significant ties questionnaire |
| Mexico 183-Day | 1 | Simple implementation |
| Americas Integration Tests | 1 | Cross-jurisdiction test suite |

**Deliverables:**
- [ ] UK SRT fully implemented with ties questionnaire
- [ ] US SPT with 3-year rolling calculation
- [ ] Canada with ties indicator
- [ ] Mexico simple day count

**Development Notes (Phase 3):**
```
Started: ___________
Completed: ___________
Issues encountered:
-
-
-

Blockers resolved:
-
-
```

---

#### Phase 4: Rest of World + Reports (2 weeks)

**Objective:** Australia, Japan, Singapore, NZ, Ireland + PDF Reports

| Task | Est. Days | Description |
|------|-----------|-------------|
| Ireland 183/280 Rule | 1.5 | Cumulative 2-year check |
| Japan 183-Day | 1 | Simple calendar year |
| Singapore 183-Day | 1 | Simple calendar year |
| New Zealand 183/12mo | 1.5 | Rolling 12-month window |
| Australia Domicile Test | 3 | Multi-factor with indicators |
| PDF Report Generator | 3 | Professional audit-ready reports |
| Report Templates | 1 | Per-jurisdiction report sections |
| QR Verification | 1 | Optional verification QR code |

**Deliverables:**
- [ ] All 15 primary jurisdictions available
- [ ] PDF report generation for any jurisdiction
- [ ] Professional, advisor-ready formatting

**Development Notes (Phase 4):**
```
Started: ___________
Completed: ___________
Issues encountered:
-
-
-

Blockers resolved:
-
-
```

---

#### Phase 5: Infrastructure & Polish (2 weeks)

**Objective:** Performance, native app sync, widget updates, final polish

| Task | Est. Days | Description |
|------|-----------|-------------|
| Performance Optimization | 2 | Caching, lazy loading, calculation optimization |
| Native App Sync | 3 | Multi-jurisdiction sync to iOS/Android |
| Widget Updates | 2 | Update widgets for multi-jurisdiction display |
| Compliance Alerts | 2 | Push notifications for approaching thresholds |
| Documentation | 1 | User guides, help content |
| End-to-End Testing | 2 | Full test suite across all jurisdictions |
| Bug Fixes & Polish | 2 | Address issues from earlier phases |

**Deliverables:**
- [ ] Native apps show multi-jurisdiction compliance
- [ ] Widgets display most critical status
- [ ] Push alerts for approaching limits
- [ ] Comprehensive documentation

**Development Notes (Phase 5):**
```
Started: ___________
Completed: ___________
Issues encountered:
-
-
-

Blockers resolved:
-
-
```

---

#### Phase 6 (Future): Other Relo Sites

**Objective:** Expand beyond France to other destination markets

| Market | Description | Estimated Effort |
|--------|-------------|------------------|
| **Relo2Spain** | Spain as primary destination, similar feature set | 4-6 weeks |
| **Relo2Portugal** | Portugal Golden Visa market | 4-6 weeks |
| **Relo2UK** | UK post-Brexit, complex visa landscape | 6-8 weeks |
| **Relo2Mexico** | Digital nomad market | 3-4 weeks |
| **Relo2Netherlands** | DAFT visa for Americans | 4-6 weeks |

**Scope per site:**
- Localized content and guidance
- Country-specific visa checklists
- Local tax residency rules as primary
- Integration with local services/advisors

**Development Notes (Phase 6):**
```
Started: ___________
Completed: ___________
Priority order:
1.
2.
3.

Issues encountered:
-
-
```

---

### C.4 UI Specifications

#### Compliance Quick View

**Purpose:** Clean, easy-to-read display for border officials, tax advisors, or quick personal reference.

**Location:** New tab in Travel Status, also available as standalone view in native apps.

**Design:**

```
┌─────────────────────────────────────────────────────┐
│  COMPLIANCE QUICK VIEW                    [⚙] [📄]  │
│  As of January 5, 2026                              │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │ 🇪🇺 SCHENGEN AREA                    ✓ OK    │   │
│  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━░░░░ │   │
│  │ 67 of 90 days used  •  23 days remaining     │   │
│  │ Current period: Jul 10, 2025 - Jan 5, 2026   │   │
│  │ [View Details]                               │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │ 🇫🇷 FRANCE TAX RESIDENCY              ✓ OK   │   │
│  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━░░░░░░░░░░░░░░ │   │
│  │ 142 of 183 days (2026)  •  41 days to limit  │   │
│  │ Rule: 183+ days = French tax resident        │   │
│  │ [View Details]                               │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │ 🇺🇸 US SUBSTANTIAL PRESENCE            ✓ OK   │   │
│  │ ━━━━━━━━━━━░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │   │
│  │ SPT Score: 68 of 183  •  115 days to limit   │   │
│  │ 2026: 15 days × 1.0 = 15                     │   │
│  │ 2025: 90 days × ⅓ = 30                       │   │
│  │ 2024: 68 days × ⅙ = 11                       │   │
│  │ [View Details]                               │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │ 🇬🇧 UK VISITOR STATUS                 ⚠ WARN │   │
│  │ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━░░░ │   │
│  │ 165 of 180 days  •  15 days remaining        │   │
│  │ Rolling 12-month period                      │   │
│  │ [View Details]                               │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  [+ Add Jurisdiction]                              │
│                                                     │
│  ────────────────────────────────────────────────  │
│  ℹ️ This is not legal or tax advice. Always        │
│  consult qualified professionals.                  │
│  [Privacy Policy] [Terms of Service]               │
└─────────────────────────────────────────────────────┘
```

**Status Indicators:**
| Status | Color | Icon | Threshold |
|--------|-------|------|-----------|
| OK | Green | ✓ | < 80% of limit |
| Warning | Amber | ⚠ | 80-95% of limit |
| Critical | Red | ⚠ | > 95% of limit |
| Exceeded | Red | ✗ | Over limit |

**Interactions:**
- Tap card → Expand to show trip details for that jurisdiction
- [⚙] → Jurisdiction settings
- [📄] → Generate PDF report
- [View Details] → Full breakdown with trip list

---

#### Auditable PDF Report

**Purpose:** Professional document for tax advisors, immigration officials, audit defense.

**Sections:**

```
═══════════════════════════════════════════════════════════
                    TRAVEL COMPLIANCE REPORT
                       MyTravelStatus
═══════════════════════════════════════════════════════════

REPORT DETAILS
──────────────────────────────────────────────────────────
Report ID:        MTS-2026-001-ABC123
Generated:        January 5, 2026 at 14:32 UTC
Report Period:    January 1, 2025 - December 31, 2025
User:             John Smith (user ID: 12345)
Email:            john.smith@email.com

═══════════════════════════════════════════════════════════
                    COMPLIANCE SUMMARY
═══════════════════════════════════════════════════════════

Jurisdiction          Status    Days Used    Limit    Margin
────────────────────────────────────────────────────────────
Schengen Area         ✓ OK      67/90        90       23 days
France Tax            ✓ OK      142/183      183      41 days
US (SPT)              ✓ OK      68/183       183      115 days
UK Visitor            ⚠ WARN    165/180      180      15 days

═══════════════════════════════════════════════════════════
              FRANCE TAX RESIDENCY ANALYSIS
═══════════════════════════════════════════════════════════

RULE SUMMARY
─────────────────────────────────────────────────────────
An individual is considered a French tax resident if they:
  • Spend 183 or more days in France during a calendar year
  • Have their principal residence ("foyer") in France
  • Exercise their principal professional activity in France
  • Have the center of their economic interests in France

This report tracks the 183-day presence test only.

2025 CALENDAR YEAR SUMMARY
─────────────────────────────────────────────────────────
Total days in France:     142 days
Threshold:                183 days
Days remaining:           41 days
Status:                   ✓ COMPLIANT

MONTHLY BREAKDOWN
─────────────────────────────────────────────────────────
Month         Days in France    Running Total
January       31                31
February      28                59
March         15                74
April         0                 74
May           0                 74
June          12                86
July          18                104
August        31                135
September     7                 142
October       0                 142
November      0                 142
December      0 (projected)     142

═══════════════════════════════════════════════════════════
                    DETAILED TRIP LOG
═══════════════════════════════════════════════════════════

#    Entry Date    Exit Date     Location       Days   Source
──────────────────────────────────────────────────────────────
1    Jan 1, 2025   Jan 31, 2025  Paris, FR      31     Manual
2    Feb 1, 2025   Feb 28, 2025  Lyon, FR       28     Calendar
3    Mar 1, 2025   Mar 15, 2025  Nice, FR       15     GPS Auto
4    Jun 10, 2025  Jun 21, 2025  Bordeaux, FR   12     Manual
5    Jul 1, 2025   Jul 18, 2025  Paris, FR      18     Photo GPS
6    Aug 1, 2025   Aug 31, 2025  Provence, FR   31     Manual
7    Sep 15, 2025  Sep 21, 2025  Paris, FR      7      GPS Auto

TOTAL DAYS IN FRANCE: 142

═══════════════════════════════════════════════════════════
                    CALCULATION METHODOLOGY
═══════════════════════════════════════════════════════════

Day Counting Method:
  • Entry day counted as Day 1
  • Exit day counted as final day
  • Partial days count as full days
  • Overnight stays determine location

Data Sources:
  • Manual Entry: User-entered trips
  • GPS Auto: Automatic location detection (3x daily)
  • Calendar: Imported from Google/Microsoft calendar
  • Photo GPS: Extracted from photo EXIF metadata

Accuracy Notes:
  • GPS accuracy: ±50 meters, converted to country only
  • Calendar events may not reflect actual travel
  • Photo timestamps depend on camera settings

═══════════════════════════════════════════════════════════
                    LEGAL DISCLAIMER
═══════════════════════════════════════════════════════════

IMPORTANT NOTICE

This report is provided for INFORMATIONAL PURPOSES ONLY and
does NOT constitute legal, tax, or immigration advice.

• Tax residency determination involves multiple factors beyond
  physical presence, including intention, family ties, property
  ownership, and economic interests.

• Immigration status depends on visa type, entry conditions,
  and individual circumstances.

• Always consult with qualified legal and tax professionals
  for advice specific to your situation.

• Border officials and tax authorities make final
  determinations at their discretion.

• MyTravelStatus does not guarantee the accuracy of
  calculations or compliance with any jurisdiction's rules.

USE THIS REPORT AS A HELPFUL REFERENCE, NOT AS LEGAL GUIDANCE.

═══════════════════════════════════════════════════════════
                    VERIFICATION
═══════════════════════════════════════════════════════════

Report Hash:    sha256:a1b2c3d4e5f6...
Generated By:   MyTravelStatus v1.6.0
Verification:   https://mytravelstatus.com/verify/MTS-2026-001

[QR CODE for verification URL]

───────────────────────────────────────────────────────────
© 2026 MyTravelStatus  |  Privacy Policy  |  Terms of Service
```

**PDF Features:**
- Timestamped with report ID for audit trail
- SHA-256 hash for integrity verification
- QR code linking to online verification
- Professional formatting suitable for advisors
- Per-jurisdiction sections with rules explanation
- Complete trip log with data sources
- Prominent legal disclaimer

---

### C.5 Database Schema Updates

#### New Tables

**`wp_fra_jurisdictions`**
```sql
CREATE TABLE wp_fra_jurisdictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,              -- 'FR', 'UK', 'US', 'SCHENGEN'
    name VARCHAR(100) NOT NULL,             -- 'France', 'United Kingdom'
    rule_type ENUM('simple_calendar', 'rolling_window', 'multi_year', 'multi_factor', 'complex') NOT NULL,
    threshold INT NOT NULL,                 -- 183, 90, etc.
    period_type ENUM('calendar_year', 'rolling_days', 'tax_year') NOT NULL,
    period_days INT DEFAULT NULL,           -- For rolling: 180, 365
    config JSON DEFAULT NULL,               -- Additional rule parameters
    enabled BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**`wp_fra_user_jurisdictions`**
```sql
CREATE TABLE wp_fra_user_jurisdictions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    jurisdiction_id BIGINT UNSIGNED NOT NULL,
    enabled BOOLEAN DEFAULT 1,
    alert_threshold INT DEFAULT 80,         -- Alert at X% of limit
    custom_config JSON DEFAULT NULL,        -- User-specific settings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_jurisdiction (user_id, jurisdiction_id)
);
```

**`wp_fra_compliance_snapshots`**
```sql
CREATE TABLE wp_fra_compliance_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    jurisdiction_id BIGINT UNSIGNED NOT NULL,
    snapshot_date DATE NOT NULL,
    days_used INT NOT NULL,
    days_remaining INT NOT NULL,
    status ENUM('ok', 'warning', 'critical', 'exceeded') NOT NULL,
    calculation_data JSON NOT NULL,         -- Full calculation details
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_jurisdiction_date (user_id, jurisdiction_id, snapshot_date)
);
```

**`wp_fra_uk_ties`** (UK SRT specific)
```sql
CREATE TABLE wp_fra_uk_ties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tax_year VARCHAR(9) NOT NULL,           -- '2025-2026'
    family_tie BOOLEAN DEFAULT 0,
    accommodation_tie BOOLEAN DEFAULT 0,
    work_tie BOOLEAN DEFAULT 0,
    ninety_day_tie BOOLEAN DEFAULT 0,
    country_tie BOOLEAN DEFAULT 0,
    notes TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_year (user_id, tax_year)
);
```

---

### C.6 API Endpoints

**New Endpoints for `/wp-json/mts/v1/`**

```
# Jurisdictions
GET    /jurisdictions                    # List all available jurisdictions
GET    /jurisdictions/{code}             # Get jurisdiction details
GET    /jurisdictions/user               # Get user's enabled jurisdictions
POST   /jurisdictions/user               # Enable jurisdiction for user
DELETE /jurisdictions/user/{code}        # Disable jurisdiction for user

# Compliance
GET    /compliance/summary               # Summary of all enabled jurisdictions
GET    /compliance/{code}                # Detailed compliance for jurisdiction
GET    /compliance/{code}/history        # Historical compliance data
GET    /compliance/{code}/trips          # Trips relevant to jurisdiction

# UK SRT Specific
GET    /uk/ties                          # Get UK ties for current year
PUT    /uk/ties                          # Update UK ties
GET    /uk/srt-result                    # Full SRT calculation result

# Reports
POST   /reports/generate                 # Generate PDF report
GET    /reports/{id}                     # Download generated report
GET    /reports/{id}/verify              # Verify report integrity
```

---

### C.7 Effort Summary

| Phase | Duration | Focus |
|-------|----------|-------|
| **Phase 1** | 3-4 weeks | Foundation, France tax, Quick View UI |
| **Phase 2** | 2 weeks | Spain, Portugal, Germany, Italy, Netherlands |
| **Phase 3** | 3 weeks | UK SRT, US SPT, Canada, Mexico |
| **Phase 4** | 2 weeks | Ireland, Japan, Singapore, NZ, Australia, PDF Reports |
| **Phase 5** | 2 weeks | Native app sync, widgets, polish |
| **Phase 6** | TBD | Other relo sites (Spain, Portugal, UK, Mexico, etc.) |

**Total Estimated:** 12-15 weeks for Phases 1-5

---

### C.8 Development Checklist by Phase

#### Phase 1 Checklist
- [ ] Jurisdiction configuration schema designed
- [ ] Multi-jurisdiction calculator engine built
- [ ] France 183-day rule implemented
- [ ] Jurisdiction selection UI created
- [ ] Compliance Quick View UI implemented
- [ ] Database tables created
- [ ] API endpoints functional
- [ ] Unit tests passing
- [ ] Integration tested with existing Schengen tracker

#### Phase 2 Checklist
- [ ] Spain 183-day rule
- [ ] Portugal 183-day rule
- [ ] Germany 183-day rule (+ permanent home indicator)
- [ ] Italy multi-factor
- [ ] Netherlands multi-factor
- [ ] Bulk enable/disable UI
- [ ] Settings UI per jurisdiction

#### Phase 3 Checklist
- [ ] UK SRT engine complete
- [ ] UK ties questionnaire UI
- [ ] UK SRT result display with explanation
- [ ] US SPT calculator with 3-year lookback
- [ ] Canada 183 + ties
- [ ] Mexico 183-day
- [ ] Cross-jurisdiction tests passing

#### Phase 4 Checklist
- [ ] Ireland 183/280 rule
- [ ] Japan 183-day
- [ ] Singapore 183-day
- [ ] New Zealand 183/12mo rolling
- [ ] Australia domicile test
- [ ] PDF report generator
- [ ] Report templates per jurisdiction
- [ ] QR verification functional

#### Phase 5 Checklist
- [ ] Performance optimized
- [ ] Native iOS app updated
- [ ] Native Android app updated
- [ ] Widgets show multi-jurisdiction
- [ ] Push alerts for thresholds
- [ ] Documentation complete
- [ ] End-to-end tests passing

---

### C.9 How to Prompt for Next Phase

When ready to start the next phase, use:

> "Let's start **Phase [N]** of the multi-jurisdiction expansion. Please review the requirements in MASTER-HANDOFF.md Appendix C and begin implementation."

Or for status check:

> "What's the current status of the multi-jurisdiction expansion? Which phase are we on?"

The assistant should:
1. Read the phase requirements from this document
2. Create a task list for the phase
3. Implement features in order
4. Update the Development Notes section for the phase
5. Mark checklist items as complete
6. Report on completion and any issues

---

### C.10 Risk & Mitigation

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Tax rules change | Medium | High | Design for configurable rules via JSON |
| UK SRT complexity | High | Medium | Start with simplified version, iterate |
| Performance with many jurisdictions | Medium | Medium | Lazy loading, caching, pagination |
| User confusion | Medium | High | Clear UI, tooltips, help content |
| Legal liability | Low | High | Prominent disclaimers, not advice |

---

### C.11 Success Metrics

| Metric | Target |
|--------|--------|
| Jurisdictions supported | 15+ |
| Calculation accuracy | 99%+ |
| PDF report generation time | < 5 seconds |
| User adoption (enabled > 1 jurisdiction) | 50% of active users |
| Quick View load time | < 1 second |
| Mobile app sync latency | < 3 seconds |

---

*End of Appendix C: Multi-Jurisdiction Expansion Plan*
