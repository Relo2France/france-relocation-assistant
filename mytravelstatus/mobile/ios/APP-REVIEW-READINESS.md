# MyTravelStatus iOS App - App Store Review Readiness Report

**Version:** 1.0.0
**Date:** January 5, 2026
**Bundle ID:** com.mytravelstatus.app

---

## Table of Contents

1. [Reviewer Access](#1-reviewer-access)
2. [Permissions & Usage Strings](#2-permissions--usage-strings)
3. [Privacy Manifest](#3-privacy-manifest)
4. [Data Collection & Sharing](#4-data-collection--sharing)
5. [Analytics Configuration](#5-analytics-configuration)
6. [Subscription Implementation](#6-subscription-implementation)
7. [Legal Disclosures](#7-legal-disclosures)
8. [Testing Checklist](#8-testing-checklist)

---

## 1. Reviewer Access

### Demo Account (Recommended)

Reviewers can access all premium features using the demo account:

| Field | Value |
|-------|-------|
| **Email** | `demo@mytravelstatus.com` |
| **Password** | `demo123` |

This account:
- ✅ Has full access to all premium features
- ✅ Contains pre-loaded demo trip data
- ✅ Does not require a subscription purchase
- ✅ Can be used immediately without setup

### Alternative Activation Methods

1. **Hidden Shake Activation**: In Settings, shake the device 5 times within 3 seconds
2. **URL Scheme**: Open `mytravelstatus://demo` to activate demo mode

### Key Flows to Test

| Feature | Navigation Path |
|---------|----------------|
| Dashboard | Home tab → View compliance status |
| Add Trip | Trips tab → + button → Manual entry |
| Passport Control | Passport Control tab → One-tap display for border officials |
| Photo Import | Trips tab → Import → Photos → Select photos |
| Calendar Import | Trips tab → Import → Calendar |
| Settings | Settings tab → Privacy & Data |
| Subscription | Settings → Subscription |

---

## 2. Permissions & Usage Strings

All permissions are configured in `Info.plist` with user-friendly descriptions:

### Location Services

| Key | Usage Description |
|-----|-------------------|
| `NSLocationWhenInUseUsageDescription` | MyTravelStatus uses your location to automatically detect which country you're in and help track your travel days for visa compliance. Your location is used solely to determine your country of presence and is never shared with third parties. |
| `NSLocationAlwaysAndWhenInUseUsageDescription` | Enable background location to automatically track country entries and exits for accurate travel compliance monitoring. This feature is optional and can be enabled in Settings. When enabled, your device checks your location three times daily (morning, afternoon, evening) to record which country you're in. Your precise coordinates are never stored—only the country name. You can disable this anytime and manually enter trips instead. |

**Implementation Notes:**
- ✅ Background location is **OFF by default** (opt-in only)
- ✅ Educational screen shown before requesting background permission
- ✅ App fully functional without background location (manual check-in available)
- ✅ Only country names stored, never precise coordinates

### Photo Library

| Key | Usage Description |
|-----|-------------------|
| `NSPhotoLibraryUsageDescription` | MyTravelStatus can scan photos you select to detect trip dates from GPS metadata. Select specific photos to import travel history. We only read location data from photos you choose—photos are never uploaded or stored on our servers. You can skip this and manually enter trips instead. |

**Implementation Notes:**
- ✅ Uses PHPicker (iOS 14+) - no full library access
- ✅ Only user-selected photos are processed
- ✅ Confirmation dialog before reading EXIF metadata
- ✅ Manual entry alternative always available

### Calendar

| Key | Usage Description |
|-----|-------------------|
| `NSCalendarsUsageDescription` | MyTravelStatus can scan your calendar for travel events (flights, hotels, trips) to automatically suggest trip entries. We only read event titles, dates, and locations locally on your device—calendar data is never uploaded to our servers. You can skip this and manually enter trips instead. |

### Push Notifications

| Key | Usage Description |
|-----|-------------------|
| `NSUserNotificationsUsageDescription` | Receive alerts when you're approaching visa day limits, reminders about upcoming trips, and important travel compliance updates. |

### Face ID

| Key | Usage Description |
|-----|-------------------|
| `NSFaceIDUsageDescription` | Use Face ID to securely unlock the app and protect your travel data. |

---

## 3. Privacy Manifest

**File:** `PrivacyInfo.xcprivacy`

### Required Reason APIs

| API Category | Reason Code | Description |
|--------------|-------------|-------------|
| UserDefaults | CA92.1 | App saves user preferences and settings |
| System Boot Time | 35F9.1 | Background task scheduling |
| Disk Space | E174.1 | Cache management |

### Tracking Declaration

| Setting | Value |
|---------|-------|
| `NSPrivacyTracking` | `false` |
| `NSPrivacyTrackingDomains` | Empty (no tracking domains) |

---

## 4. Data Collection & Sharing

### Data Collected

| Data Type | Linked to Identity | Used for Tracking | Purpose |
|-----------|-------------------|-------------------|---------|
| Precise Location | Yes | No | App Functionality (country detection) |
| Coarse Location | Yes | No | App Functionality (travel compliance) |
| User ID | Yes | No | App Functionality (account) |
| Email Address | Yes | No | App Functionality (login) |
| Device ID | Yes | No | App Functionality (push notifications) |
| Product Interaction | No | No | Analytics |
| Crash Data | No | No | App Functionality |
| Performance Data | No | No | App Functionality |

### Data NOT Collected

- ❌ Precise GPS coordinates (only country names)
- ❌ Photo content (only EXIF metadata from selected photos)
- ❌ Calendar event details (only titles/dates/locations)
- ❌ Financial information
- ❌ Contact information (beyond login email)
- ❌ Browsing history
- ❌ Search history

### Data Sharing

| Shared with Third Parties | Value |
|--------------------------|-------|
| Any data | **No** |

---

## 5. Analytics Configuration

**File:** `Services/AnalyticsManager.swift`

### Privacy Controls

| Control | Default | User Configurable |
|---------|---------|-------------------|
| Analytics Enabled | **OFF** | Yes (opt-in) |
| Crash Reporting | ON | Yes (opt-out) |

### Analytics Opt-Out Location

**Settings → Privacy & Data → Analytics & Diagnostics**

### Events Tracked (When Enabled)

| Category | Events | Contains Sensitive Data |
|----------|--------|------------------------|
| App Lifecycle | app_launched, app_backgrounded | No |
| Navigation | screen_viewed, tab_selected | No (screen names only) |
| Feature Usage | trip_added, trip_deleted, manual_check_in | No (counts only) |
| Permissions | permission_requested, granted, denied | No (type only) |
| Import Features | import_started, import_completed | No (counts only) |
| Errors | error_occurred | No (type only, sanitized) |

### Data NOT in Analytics

- ❌ Location coordinates
- ❌ Country names
- ❌ Trip details
- ❌ User identifiers
- ❌ Email addresses
- ❌ Photo metadata
- ❌ Calendar content

---

## 6. Subscription Implementation

### Products

| Product ID | Type | Period |
|------------|------|--------|
| `com.mytravelstatus.premium.monthly` | Auto-Renewable | Monthly |
| `com.mytravelstatus.premium.annual` | Auto-Renewable | Annual |

### Required Features

| Feature | Implementation | Location |
|---------|---------------|----------|
| Restore Purchases | ✅ `AppStore.sync()` | SubscriptionView → "Restore Purchases" button |
| Manage Subscription | ✅ `AppStore.showManageSubscriptions()` | SubscriptionView → "Manage Subscription" link |
| Pricing Display | ✅ `product.displayPrice` | SubscriptionView |
| Auto-Renewal Disclosure | ✅ Full disclosure text | SubscriptionView → Legal section |

### Renewal Disclosure Text

> Subscription automatically renews at [PRICE]/[PERIOD] unless canceled at least 24 hours before the end of the current period. Your account will be charged for renewal within 24 hours prior to the end of the current period. You can manage and cancel your subscription in your App Store account settings after purchase.

### Screenshots/Paths

| Feature | Path |
|---------|------|
| Subscription View | Settings → Subscription |
| Restore Purchases Button | Settings → Subscription → "Restore Purchases" |
| Manage Subscription | Settings → Subscription → "Manage Subscription" |
| Terms Link | Settings → Subscription → "Terms of Service" |
| Privacy Link | Settings → Subscription → "Privacy Policy" |

---

## 7. Legal Disclosures

### Legal Disclaimer

Displayed prominently in **Settings → Privacy & Data**:

> **Important Notice**
>
> MyTravelStatus is an informational tool only. It is NOT legal, tax, or immigration advice.
>
> • Always verify visa requirements with official government sources
> • Consult with qualified professionals for legal/tax matters
> • Entry decisions are at the discretion of border officials
> • We do not guarantee accuracy of compliance calculations
>
> Use this app as a helpful tracker, not as legal guidance.

### Links Provided

| Document | URL |
|----------|-----|
| Privacy Policy | https://mytravelstatus.com/privacy |
| Terms of Service | https://mytravelstatus.com/terms |
| Help & Support | https://mytravelstatus.com/support |

---

## 8. Testing Checklist

### Background Location

- [ ] Background location is OFF by default on fresh install
- [ ] Educational screen appears before requesting Always authorization
- [ ] User can decline background location and use manual check-in
- [ ] App functions fully with only When In Use permission
- [ ] Background location can be toggled off in Settings → Privacy & Data

### Photo Import

- [ ] PHPicker opens (not full library access)
- [ ] Only selected photos are processed
- [ ] Confirmation dialog appears before reading EXIF
- [ ] User can cancel metadata extraction
- [ ] Manual entry alternative is offered

### Analytics

- [ ] Analytics is disabled by default
- [ ] Toggle in Settings → Privacy & Data works
- [ ] No analytics events fire when disabled

### Subscriptions

- [ ] Subscription view loads products
- [ ] Purchase flow completes
- [ ] Restore Purchases works
- [ ] Manage Subscription opens App Store
- [ ] All disclosure text is visible

### Demo Mode

- [ ] Demo credentials work (demo@mytravelstatus.com / demo123)
- [ ] All premium features accessible in demo mode
- [ ] Demo data loads correctly

### Data Management

- [ ] Export My Data generates valid JSON
- [ ] Share sheet opens with export file
- [ ] Delete Account shows confirmation
- [ ] Typing DELETE confirms deletion
- [ ] All local data cleared after deletion

### Permissions

- [ ] All permission dialogs show correct usage strings
- [ ] Permissions only requested when user initiates feature
- [ ] App gracefully handles denied permissions

---

## File References

| File | Purpose |
|------|---------|
| `Info.plist` | All permission usage strings |
| `PrivacyInfo.xcprivacy` | Privacy manifest |
| `Services/PrivacySettings.swift` | User privacy preferences |
| `Services/AnalyticsManager.swift` | Privacy-focused analytics |
| `Services/SubscriptionManager.swift` | StoreKit subscriptions |
| `Services/DemoModeService.swift` | Reviewer demo mode |
| `Services/DataManagementService.swift` | Export & deletion |
| `Services/PhotoPickerService.swift` | PHPicker implementation |
| `Views/Privacy/LocationEducationView.swift` | Background location education |
| `Views/Settings/PrivacySettingsView.swift` | Privacy settings UI |
| `Views/Subscription/SubscriptionView.swift` | Subscription UI |

---

## Summary

This app is designed with privacy-first principles:

1. **Background location is optional** - Users can track travel manually
2. **Photo access is limited** - PHPicker, user selection only
3. **Analytics are opt-in** - Disabled by default, no sensitive data
4. **Subscriptions follow guidelines** - Restore, manage, full disclosures
5. **Data management available** - Export and delete supported
6. **Legal disclaimer prominent** - Not legal/tax advice
7. **Reviewer access provided** - Demo mode with full features

The app is ready for App Store review.
