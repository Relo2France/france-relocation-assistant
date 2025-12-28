# Relo2France Session Handoff Document

**Date:** December 28, 2025
**Branch:** `claude/resume-france-relocation-tWg9t`
**Plan Document:** `SCHENGEN-MONAEO-PARITY-PLAN.md`

---

## 1. Current Project Status

**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active production use.

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v3.6.4 | Active |
| Member Tools Plugin | v2.1.0 | Active |
| React Portal | v2.1.0 | Active |
| Theme | v1.2.4 | Active |
| **Schengen Tracker Plugin** | **v1.2.0** | **Active** |

The React portal is fully functional with 40+ REST API endpoints covering profile management, task tracking, document generation, checklists, AI-powered guides, Schengen day tracking, location tracking, and calendar sync.

### Schengen Tracker: Phase 2 Complete

Calendar Sync has been implemented. Users can connect Google Calendar or Microsoft Outlook, sync events, detect travel automatically, and import them as Schengen trips. iCal file upload is also supported.

---

## 2. Schengen Tracker - COMPLETE FEATURES

The Schengen Tracker has been built as a comprehensive 90/180 day compliance tool. All core phases are complete.

### Phase 0: Plugin Extraction & Premium Setup ✅

Extracted Schengen Tracker into standalone plugin (`relo2france-schengen-tracker/`):

| File | Purpose |
|------|---------|
| `relo2france-schengen-tracker.php` | Main plugin file with autoloader |
| `class-r2f-schengen-core.php` | Core singleton, admin menu, shortcode |
| `class-r2f-schengen-schema.php` | Database schema (trips + location + calendar + jurisdiction tables) |
| `class-r2f-schengen-api.php` | REST API (~40 endpoints) |
| `class-r2f-schengen-alerts.php` | Daily cron email alerts |
| `class-r2f-schengen-location.php` | GPS check-in, auto-trip creation |
| `class-r2f-schengen-calendar.php` | Calendar sync (Google, Outlook, iCal) |
| `class-r2f-schengen-jurisdiction.php` | Multi-jurisdiction rules engine |
| `class-r2f-schengen-premium.php` | Premium feature gating |

**Premium Gating (3-tier priority):**
1. User meta override (`r2f_schengen_enabled`)
2. Filter hook (`r2f_schengen_premium_check`) - Member Tools integration
3. Global setting fallback

### Phase 1.0: Core Functionality ✅

- Trip entry form with Schengen country validation (29 countries)
- Rolling 180-day window calculation algorithm
- Dashboard with circular day counter and status badges
- Trip list with edit/delete functionality
- Status thresholds: safe → warning (60) → danger (80) → critical (90)

### Phase 1.1: Browser Geolocation Check-in ✅

- Browser GPS location check-in via Geolocation API
- Reverse geocoding via OpenStreetMap Nominatim (free)
- Location history tracking in `fra_schengen_location_log` table
- **Auto-trip creation** when checking in from Schengen country

### Phase 1.2: Smart Location Detection ✅

- **Timezone detection:** Compares browser timezone to stored timezone
- **IP-based detection:** Uses ip-api.com (free, 1-hour cache)
- **Daily reminders:** Configurable hour, tracks last check-in
- **Smart banners:**
  - Amber (plane icon): Timezone change detected
  - Purple (globe icon): IP country differs from timezone
  - Blue (clock icon): Daily reminder

### Phase 2: Calendar Sync ✅ (NEW)

#### Database Schema
- `wp_fra_calendar_connections` - OAuth connections (tokens, sync status)
- `wp_fra_calendar_events` - Detected travel events

#### PHP Calendar Class (`class-r2f-schengen-calendar.php`)
- Complete OAuth 2.0 flow for Google Calendar and Microsoft Outlook
- Token encryption/decryption using WordPress salts
- Event fetching and syncing from calendar APIs
- Travel keyword detection (countries, cities, transport, accommodation)
- iCal file parsing and import
- Confidence scoring for travel detection

#### Calendar REST API Endpoints
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/calendar/providers` | GET | Available providers |
| `/calendar/connections` | GET | Connected calendars |
| `/calendar/connect` | POST | Start OAuth flow |
| `/calendar/connections/{id}` | DELETE | Disconnect |
| `/calendar/connections/{id}/sync` | POST | Manual sync |
| `/calendar/events` | GET | Detected events |
| `/calendar/events/import` | POST | Import as trips |
| `/calendar/events/skip` | POST | Skip events |
| `/calendar/import-ical` | POST | Upload iCal file |

#### React Component (`CalendarSync.tsx`)
- Full UI for connecting calendars (Google/Microsoft)
- Connection status cards with sync and disconnect actions
- iCal file upload
- Detected travel events list with select/import/skip
- OAuth callback handling via URL parameters

### Enhanced Features ✅

**Calendar View** (`CalendarView.tsx`):
- Monthly calendar with navigation
- Trip days highlighted in brand blue
- Future trips shown with dashed border

**Planning Tool** (`PlanningTool.tsx`):
- "What If" calculator for future trips
- Shows if trip would violate 90/180 rule
- Earliest safe start date suggestion

**Alert System** (`class-r2f-schengen-alerts.php`):
- Three thresholds: 60 (warning), 80 (danger), 85 (urgent)
- Daily cron at 8am UTC
- HTML emails with brand styling

**PDF Reports** (`ReportExport.tsx`):
- Generate button in header
- Preview modal with summary
- Print / Save as PDF via browser

**Onboarding** (`SchengenOnboarding.tsx`):
- 5-step walkthrough for first-time users
- Auto-shows for first-time users
- Help button (?) to reopen

---

## 3. React Frontend Files

| File | Purpose |
|------|---------|
| `SchengenDashboard.tsx` | Main dashboard component |
| `JurisdictionOverview.tsx` | Multi-jurisdiction tracking UI |
| `CalendarSync.tsx` | Calendar connection & sync UI |
| `CalendarView.tsx` | Monthly calendar |
| `PlanningTool.tsx` | "What if" calculator |
| `ReportExport.tsx` | PDF generation |
| `LocationTracker.tsx` | GPS check-in UI |
| `LocationDetectionBanner.tsx` | Smart prompts |
| `SchengenOnboarding.tsx` | First-time walkthrough |
| `TripForm.tsx` | Add/edit trip modal (with jurisdiction selector) |
| `TripList.tsx` | Trip list with actions |
| `DayCounter.tsx` | Circular progress |
| `StatusBadge.tsx` | Status indicator |
| `useSchengenStore.ts` | Zustand store |
| `useLocationDetection.ts` | Timezone/IP detection hook |
| `useGeolocation.ts` | Browser geolocation hook |
| `schengenUtils.ts` | Calculation utilities |

---

## 4. Architecture

```
SchengenDashboard
    │
    ├── Header
    │   ├── HelpCircle (?) → Opens onboarding
    │   ├── ReportExport → PDF generation
    │   └── Add Trip → TripForm modal
    │
    ├── Status Cards Row
    │   ├── DayCounter (circular progress)
    │   ├── Days Remaining card
    │   ├── Current Window card
    │   └── Next Expiration card
    │
    ├── LocationDetectionBanner → Smart check-in prompts
    ├── LocationTracker (compact) → Quick check-in widget
    │
    ├── Warning Banners (conditional by status)
    │
    ├── Tab Navigation
    │   ├── Trip List → TripList
    │   ├── Calendar View → CalendarView (premium)
    │   ├── Calendar Sync → CalendarSync (NEW)
    │   ├── Planning Tool → PlanningTool (premium)
    │   ├── Location → LocationTracker (full)
    │   └── Settings → Alert toggles, thresholds
    │
    └── Modals
        ├── TripForm (add/edit)
        └── SchengenOnboarding (first-time)
```

---

## 5. Configuration Required

For calendar sync to work, admins need to configure OAuth credentials in WordPress options:

### Google Calendar
```php
update_option('r2f_schengen_google_client_id', 'your-client-id');
update_option('r2f_schengen_google_client_secret', 'your-client-secret');
```

### Microsoft Outlook
```php
update_option('r2f_schengen_microsoft_client_id', 'your-client-id');
update_option('r2f_schengen_microsoft_client_secret', 'your-client-secret');
```

**Note:** An admin settings page for these credentials is not yet implemented.

---

## 6. Phase Status

| Phase | Description | Status |
|-------|-------------|--------|
| **0** | Plugin Extraction & Premium Setup | **Complete** |
| **1.1** | Browser Geolocation Integration | **Complete** |
| **1.2** | Smart Location Detection | **Complete** |
| **2** | Google/Outlook Calendar Sync | **Complete** |
| **3** | Multi-Jurisdiction (US States, etc.) | **Complete** |
| **4** | Professional PDF Reports | Complete |
| **5** | Push + In-App Notifications | Pending |
| **6** | CSV/ICS Import + PWA | Partially done (iCal import complete) |
| **7** | AI Suggestions + Family + Analytics | Pending |

---

## 7. Build Commands

```bash
# Build React portal
cd france-relocation-member-tools/portal
npm install
npm run build

# Type check
npx tsc --noEmit

# Lint
npm run lint
```

**Current Status:**
- Type Check: 0 errors
- Build: Successful
- Schengen Tracker: v1.2.0 with calendar sync

---

## 8. Known Issues / Notes

### Timezone Fix Applied
**Issue:** Check-in times displayed incorrectly
**Fix:** Return ISO 8601 format with timezone:
```php
'recordedAt' => gmdate('c') // "2025-12-28T14:52:00+00:00"
```

### Portal URL Routing
Direct links like `/portal/?view=schengen` now work correctly.

### Travel Detection Keywords
The calendar sync detects travel events using keywords:
- **Transport:** flight, fly, train, eurostar, airport, airline
- **Accommodation:** hotel, airbnb, hostel, booking, reservation
- **Travel:** trip, vacation, holiday, visit
- **Countries:** All 30 Schengen countries
- **Cities:** 30+ major European cities (Paris, Berlin, Rome, etc.)

### Confidence Scoring
- **0.9:** Country name detected in title/location
- **0.85:** Major city detected
- **0.5:** Travel keyword only (no specific location)

---

## 9. To Resume Next Session

**Priority tasks:**

1. **OAuth Credentials Setup:**
   - Set up Google Calendar OAuth credentials in Google Cloud Console
   - Microsoft Outlook OAuth requires 365 Developer Program (user doesn't qualify)

2. **Background Sync:** Set up cron job for automatic calendar syncing

3. **Push + In-App Notifications** (Phase 5)
   - Web Push API subscription flow
   - In-app notification center
   - See SCHENGEN-MONAEO-PARITY-PLAN.md section 5.1-5.2

**Completed this session:**
- Phase 3: Multi-Jurisdiction Support
  - Database schema with jurisdiction_rules table
  - PHP class with REST API endpoints
  - React hooks and API client methods
  - JurisdictionOverview component
  - TripForm jurisdiction selector
  - Support for zones (Schengen, UK), countries, and US states

**Reference:** `SCHENGEN-MONAEO-PARITY-PLAN.md` for detailed specs

---

## 10. GitHub Repositories

- **Main Plugin:** Relo2France/france-relocation-assistant
- **Member Tools:** Relo2France/france-relocation-member-tools
- **Schengen Tracker:** relo2france-schengen-tracker (within main repo)

---

*Last Updated: December 28, 2025*
