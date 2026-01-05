# MyTravelStatus Android App - Google Play Store Compliance Report

**Version:** 1.0.0
**Date:** January 5, 2026
**Package Name:** com.mytravelstatus.app

---

## Table of Contents

1. [Reviewer Access](#1-reviewer-access)
2. [Permissions List](#2-permissions-list)
3. [Background Location Justification](#3-background-location-justification)
4. [Data Safety Worksheet](#4-data-safety-worksheet)
5. [Analytics Implementation](#5-analytics-implementation)
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

1. **Hidden Tap Activation**: In Settings, tap "App Version" 5 times rapidly
2. **Deep Link**: Open `mytravelstatus://demo` to activate demo mode

### Key Flows to Test

| Feature | Navigation Path |
|---------|----------------|
| Dashboard | Home tab → View compliance status |
| Add Trip | Trips tab → + button → Manual entry |
| Passport Control | Passport Control tab → One-tap display |
| Photo Import | Trips tab → Import → Photos |
| Calendar Import | Trips tab → Import → Calendar |
| Settings | Settings tab → All options |
| Privacy Settings | Settings → Privacy & Data |
| Subscription | Settings → Premium |

---

## 2. Permissions List

### Location Permissions

| Permission | Purpose | When Requested | User Benefit |
|------------|---------|----------------|--------------|
| `ACCESS_FINE_LOCATION` | Determine current country for manual check-in | When user taps "Check In" button | Accurate country detection for travel tracking |
| `ACCESS_COARSE_LOCATION` | Fallback country detection | With fine location | Works when GPS unavailable |
| `ACCESS_BACKGROUND_LOCATION` | **OPTIONAL** - Automatic 3x daily country detection | After user enables in Settings → Privacy & Data (shows education screen first) | Automatic travel logging without manual entry |

### Other Permissions

| Permission | Purpose | When Requested |
|------------|---------|----------------|
| `INTERNET` | Sync data, authentication | Always required |
| `ACCESS_NETWORK_STATE` | Offline/online status | Always required |
| `FOREGROUND_SERVICE` | Location check-in service | With location permission |
| `FOREGROUND_SERVICE_LOCATION` | Background location service | With background location |
| `POST_NOTIFICATIONS` | Travel alerts | After user opts-in in Settings |
| `RECEIVE_BOOT_COMPLETED` | Reschedule checks after reboot | With background location |
| `WAKE_LOCK` | Complete background tasks | With background location |
| `READ_CALENDAR` | Import trips from calendar | When user taps "Import from Calendar" |
| `READ_MEDIA_IMAGES` | Photo EXIF import | When user selects photos in picker |
| `com.android.vending.BILLING` | Subscription purchases | Always (Google Play Billing) |

---

## 3. Background Location Justification

### Feature Description

MyTravelStatus uses background location to automatically track which country the user is in for Schengen visa compliance monitoring.

### How It Works

1. **Frequency**: 3 checks per day (8 AM, 2 PM, 8 PM local time)
2. **Data Stored**: Country name only - **precise coordinates are never stored**
3. **Purpose**: Automatically count days spent in Schengen countries
4. **User Benefit**: No manual entry required; accurate visa compliance tracking

### Privacy Protections

| Protection | Implementation |
|------------|---------------|
| **Off by Default** | Background location is disabled on fresh install |
| **Education First** | 3-page education screen shown before permission request |
| **Explicit Opt-In** | User must enable via Settings → Privacy & Data toggle |
| **No Precise Coordinates** | GPS coordinates converted to country name immediately |
| **No Third-Party Sharing** | Location data never shared with third parties |
| **User Control** | Can disable at any time; manual check-in always available |
| **Data Deletion** | User can delete all location data via Settings |

### User Education Flow

When user attempts to enable background location:

1. **Page 1**: "Automatic Country Detection" - explains what data is collected
2. **Page 2**: "Your Privacy Protected" - explains privacy protections
3. **Page 3**: "You're in Control" - explains user options and alternatives

User must view all pages before enabling. Can skip/decline at any time.

### Justification for Background Location

> MyTravelStatus is a travel compliance app that helps users track days spent in Schengen countries to avoid visa overstays. Background location enables automatic trip logging by checking which country the user is in 3 times daily. This core functionality cannot be achieved through foreground location alone, as users travel continuously and cannot reasonably open the app multiple times daily. Only country names are stored - never precise coordinates - and the feature is completely optional with manual check-in as an alternative.

---

## 4. Data Safety Worksheet

### Data Collected

| Data Type | Collected | Shared | Required | Purpose |
|-----------|-----------|--------|----------|---------|
| **Approximate location** | Yes | No | No (optional) | App functionality - country detection |
| **Precise location** | Yes | No | No (optional) | App functionality - country detection (converted to country name immediately) |
| **Email address** | Yes | No | Yes | Account authentication |
| **User IDs** | Yes | No | Yes | Account functionality |
| **Device ID** | Yes | No | Yes | Push notifications |
| **App interactions** | Yes (opt-in) | No | No | Analytics (opt-in only) |
| **Crash logs** | Yes | No | No | App stability |
| **App info and performance** | Yes | No | No | App stability |
| **Purchase history** | Yes | No | Yes | Subscription management |

### Data NOT Collected

- ❌ Precise GPS coordinates (converted to country immediately)
- ❌ Photo content (only EXIF location metadata from selected photos)
- ❌ Calendar event details (only titles/dates/locations for import)
- ❌ Contacts
- ❌ SMS or call logs
- ❌ Files (except user-initiated exports)
- ❌ Audio/video recordings
- ❌ Health data
- ❌ Financial data

### Security Practices

| Practice | Implemented |
|----------|-------------|
| Data encrypted in transit | ✅ Yes (HTTPS) |
| Data encrypted at rest | ✅ Yes (EncryptedSharedPreferences) |
| User can request data deletion | ✅ Yes (Settings → Privacy & Data → Delete Account) |
| Follows Google Play's User Data Policy | ✅ Yes |

---

## 5. Analytics Implementation

### Analytics SDK

**SDK**: Custom privacy-focused implementation (no third-party SDK)

### Privacy Controls

| Control | Default | User Configurable |
|---------|---------|-------------------|
| Analytics Enabled | **OFF** | Yes (Settings → Privacy & Data) |
| Crash Reporting | ON | Yes (opt-out) |

### Events Tracked (When Enabled)

| Category | Events | Contains Sensitive Data |
|----------|--------|------------------------|
| App Lifecycle | app_launched, app_backgrounded | No |
| Navigation | screen_viewed, tab_selected | No (screen names only) |
| Feature Usage | trip_added, trip_deleted, manual_check_in | No (counts only) |
| Permissions | permission_requested, granted, denied | No (type only) |
| Import Features | import_started, import_completed | No (counts only) |
| Subscriptions | view_opened, purchase_started, completed | No (product ID only) |
| Errors | error_occurred | No (type only, sanitized) |

### Data NOT in Analytics

- ❌ Location coordinates
- ❌ Country names
- ❌ Trip details
- ❌ User identifiers (email, name)
- ❌ Device identifiers
- ❌ Photo metadata
- ❌ Calendar content
- ❌ Financial information

### Full Analytics Event List

```
# App Lifecycle
app_launched
app_backgrounded
app_foregrounded

# Authentication
login_started
login_completed
login_failed
logout

# Navigation
screen_viewed {screen: "home"|"trips"|"passport_control"|...}
tab_selected {tab: "home"|"trips"|...}

# Feature Usage
trip_added
trip_deleted
manual_check_in
photo_import_started
photo_import_completed {import_count: N}
calendar_import_started
calendar_import_completed

# Data Management
data_export_requested
account_deletion_requested

# Permissions
permission_requested {permission_type: "location"|"notification"|...}
permission_granted {permission_type: "..."}
permission_denied {permission_type: "..."}

# Subscriptions
subscription_view_opened
subscription_purchase_started {subscription_id: "..."}
subscription_purchase_completed {subscription_id: "..."}
subscription_purchase_failed
subscription_restored

# Settings
analytics_opt_in
analytics_opt_out
background_location_enabled
background_location_disabled

# Errors
error_occurred {error_type: "sanitized_type"}
sync_failed
```

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
| Display Pricing | ✅ `product.subscriptionOfferDetails.pricingPhases` | SubscriptionScreen |
| Restore Purchases | ✅ `BillingClient.queryPurchasesAsync()` | SubscriptionScreen → "Restore Purchases" |
| Manage Subscription | ✅ Deep link to Play Store subscription management | SubscriptionScreen → "Manage Subscription" |
| Auto-Renewal Disclosure | ✅ Full disclosure text | SubscriptionScreen → Legal section |

### Renewal Disclosure Text

> Subscription automatically renews at [PRICE]/[PERIOD] unless canceled at least 24 hours before the end of the current period. Your Google Play account will be charged for renewal within 24 hours prior to the end of the current period. You can manage and cancel your subscription in your Google Play Store account settings after purchase.

### Implementation Details

- **File**: `service/SubscriptionManager.kt`
- **UI**: `ui/screens/SubscriptionScreen.kt`
- **Billing Library**: Google Play Billing Library v6.1.0

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
- [ ] Educational disclosure (3 pages) shown before permission request
- [ ] User can decline background location and use manual check-in
- [ ] App functions fully with only foreground permission
- [ ] Background location can be toggled off in Settings → Privacy & Data
- [ ] Only country names stored in database (verify no coordinates)

### Photo Import

- [ ] Photo Picker opens (not full gallery access)
- [ ] Only selected photos are processed
- [ ] Confirmation dialog appears before reading EXIF
- [ ] User can cancel metadata extraction
- [ ] Manual entry alternative is offered

### Analytics

- [ ] Analytics is disabled by default
- [ ] Toggle in Settings → Privacy & Data works
- [ ] No analytics events fire when disabled
- [ ] Analytics events contain no PII when enabled

### Notifications

- [ ] Notification permission NOT requested on first launch
- [ ] Permission only requested after user opts-in
- [ ] App works fully without notification permission

### Subscriptions

- [ ] Subscription screen loads products from Play Store
- [ ] Purchase flow completes successfully
- [ ] Restore Purchases button works
- [ ] Manage Subscription opens Play Store
- [ ] All disclosure text is visible
- [ ] Terms/Privacy links work

### Demo Mode

- [ ] Demo credentials work (demo@mytravelstatus.com / demo123)
- [ ] All premium features accessible in demo mode
- [ ] Demo data loads correctly
- [ ] Hidden activation (5 taps) works
- [ ] Deep link (mytravelstatus://demo) works

### Data Management

- [ ] Export My Data generates valid JSON
- [ ] Share sheet opens with export file
- [ ] Delete Account shows two-step confirmation
- [ ] Typing DELETE confirms deletion
- [ ] All local data cleared after deletion

### Permissions

- [ ] All permission dialogs show at appropriate times
- [ ] Permissions only requested when user initiates feature
- [ ] App gracefully handles denied permissions
- [ ] Runtime permission rationales shown appropriately

---

## File References

| File | Purpose |
|------|---------|
| `AndroidManifest.xml` | Permissions with documentation |
| `service/PrivacySettings.kt` | User privacy preferences manager |
| `service/AnalyticsManager.kt` | Privacy-focused analytics |
| `service/SubscriptionManager.kt` | Google Play Billing |
| `service/DemoModeService.kt` | Reviewer demo mode |
| `service/DataManagementService.kt` | Export & deletion |
| `service/PhotoPickerService.kt` | Photo picker with EXIF confirmation |
| `service/LocationScheduler.kt` | Background location scheduling |
| `ui/screens/LocationEducationScreen.kt` | Background location education |
| `ui/screens/PrivacySettingsScreen.kt` | Privacy settings UI |
| `ui/screens/SubscriptionScreen.kt` | Subscription UI |
| `build.gradle.kts` | Dependencies (billing, exifinterface) |
| `gradle/libs.versions.toml` | Version catalog |

---

## Summary

This app is designed with privacy-first principles and full Google Play compliance:

1. **Background location is optional** - OFF by default, requires education + explicit opt-in
2. **Photo access is limited** - Uses Photo Picker, EXIF extraction requires confirmation
3. **Analytics are opt-in** - Disabled by default, no sensitive data collected
4. **Subscriptions follow guidelines** - Restore, manage, full disclosures
5. **Data management available** - Export (JSON) and delete account supported
6. **Legal disclaimer prominent** - Not legal/tax advice
7. **Reviewer access provided** - Demo mode with full premium features

The app is ready for Google Play Store review.
