# Relo2France Master Handoff Document

**Last Updated:** January 5, 2026
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
- Multi-jurisdiction expansion (UK SRT, US SPT, 183-day rules)

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
| **MyTravelStatus Plugin** | **v1.6.0** | **Active** |
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

### Short Term
- [x] Complete native app push notifications (APNs/FCM) ✓
- [x] iOS App Store Review Readiness ✓
- [x] Android Google Play Compliance ✓
- [ ] Build iOS/Android widgets
- [ ] App Store / Play Store submission (compliance ready, pending final build & submit)

### Medium Term
- [ ] Multi-jurisdiction engine (UK SRT, US SPT, 183-day rules)
- [ ] Family sync in native apps
- [ ] AI suggestions in native apps
- [ ] Proof of presence feature

### Long Term
- [ ] Multi-user household accounts
- [ ] Travel document management (passport expiry)
- [ ] Integration with visa tracking module
- [ ] Tax advisor sharing portal
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
