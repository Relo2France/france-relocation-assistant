# Relo2France Session Handoff Document

**Date:** December 28, 2025
**Branch:** `claude/review-handoff-docs-QtcLj`
**Plan Document:** `SCHENGEN-MONAEO-PARITY-PLAN.md`

---

## 1. Current Project Status

**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active production use.

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v2.9.83 | Active |
| Member Tools Plugin | v1.0.80 | Active |
| **Schengen Tracker Plugin** | **v1.1.0** | **Active** |
| Theme | v1.2.3 | Active |

The React portal is fully functional with 40+ REST API endpoints covering profile management, task tracking, document generation, checklists, AI-powered guides, and Schengen day tracking.

---

## 2. Schengen Tracker - COMPLETE FEATURES

The Schengen Tracker has been built as a comprehensive 90/180 day compliance tool. All core phases are complete.

### Phase 0: Plugin Extraction & Premium Setup ✅

Extracted Schengen Tracker into standalone plugin (`relo2france-schengen-tracker/`):

| File | Purpose |
|------|---------|
| `relo2france-schengen-tracker.php` | Main plugin file with autoloader |
| `class-r2f-schengen-core.php` | Core singleton, admin menu, shortcode |
| `class-r2f-schengen-schema.php` | Database schema (trips + location tables) |
| `class-r2f-schengen-api.php` | REST API (~40 endpoints) |
| `class-r2f-schengen-alerts.php` | Daily cron email alerts |
| `class-r2f-schengen-location.php` | GPS check-in, auto-trip creation |
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

**Location:** `class-r2f-schengen-location.php`

- Browser GPS location check-in via Geolocation API
- Reverse geocoding via OpenStreetMap Nominatim (free)
- Location history tracking in `fra_schengen_location_log` table
- **Auto-trip creation** when checking in from Schengen country:
  - Creates new trip for today if none exists
  - Extends yesterday's trip if applicable
  - Updates location data on existing trip

**API Endpoints:**
```
POST /r2f-schengen/v1/location/store    - Store location check-in
GET  /r2f-schengen/v1/location/history  - Get location history
GET  /r2f-schengen/v1/location/today    - Check if checked in today
POST /r2f-schengen/v1/location/clear    - Clear all history
DELETE /r2f-schengen/v1/location/{id}   - Delete single entry
GET  /r2f-schengen/v1/location/detect   - IP-based country detection
```

### Phase 1.2: Smart Location Detection ✅

**Location:** `useLocationDetection.ts`, `LocationDetectionBanner.tsx`

- **Timezone detection:** Compares browser timezone to stored timezone
- **IP-based detection:** Uses ip-api.com (free, 1-hour cache)
- **Daily reminders:** Configurable hour, tracks last check-in
- **Smart banners:**
  - Amber (plane icon): Timezone change detected
  - Purple (globe icon): IP country differs from timezone
  - Blue (clock icon): Daily reminder

### Phase 2: Database & API ✅

**Database Tables:**
- `wp_fra_schengen_trips` - Trip records with location columns
- `wp_fra_schengen_location_log` - Location check-in history

**Full API Endpoints:**
```
GET/POST   /r2f-schengen/v1/trips           - List/create trips
GET/PUT/DELETE /r2f-schengen/v1/trips/{id}  - Single trip CRUD
GET        /r2f-schengen/v1/summary         - Day count & status
GET/PUT    /r2f-schengen/v1/settings        - User alert settings
POST       /r2f-schengen/v1/simulate        - "What if" calculation
GET        /r2f-schengen/v1/report          - Generate PDF report
GET        /r2f-schengen/v1/feature-status  - Premium status check
POST       /r2f-schengen/v1/test-alert      - Send test email
```

### Phase 3: Enhanced Features ✅

**Calendar View** (`CalendarView.tsx`):
- Monthly calendar with navigation
- Trip days highlighted in brand blue
- Future trips shown with dashed border
- Days outside window shown in gray
- Legend and mobile responsive

**Planning Tool** (`PlanningTool.tsx`):
- "What If" calculator for future trips
- Shows if trip would violate 90/180 rule
- Earliest safe start date suggestion
- Maximum safe trip length calculation

**Alert System** (`class-r2f-schengen-alerts.php`):
- Three thresholds: 60 (warning), 80 (danger), 85 (urgent)
- Daily cron at 8am UTC
- Prevents duplicate alerts within 7 days
- HTML emails with brand styling
- Test alert button in Settings

**PDF Reports** (`ReportExport.tsx`):
- Generate button in header
- Preview modal with summary
- HTML report in iframe
- Print / Save as PDF via browser

### Phase 4: Polish ✅

**Onboarding** (`SchengenOnboarding.tsx`):
5-step walkthrough for first-time users:
1. Welcome - 90/180 rule explanation
2. Track Your Trips - How to add trips
3. Smart Location - GPS feature
4. Planning Tool - "What If" calculator
5. Email Alerts - Threshold explanations

Features:
- Auto-shows for first-time users
- Progress dots with navigation
- Help button (?) to reopen
- Stored in localStorage

**Email Notifications:**
- Subject lines vary by severity
- Links to tracker and settings
- Unsubscribe via toggle

**Mobile Responsive:**
- Tailwind responsive classes throughout
- Abbreviated labels on mobile
- Touch-friendly button sizes

---

## 3. React Frontend Files

| File | Purpose |
|------|---------|
| `SchengenDashboard.tsx` | Main dashboard component |
| `CalendarView.tsx` | Monthly calendar |
| `PlanningTool.tsx` | "What if" calculator |
| `ReportExport.tsx` | PDF generation |
| `LocationTracker.tsx` | GPS check-in UI |
| `LocationDetectionBanner.tsx` | Smart prompts |
| `SchengenOnboarding.tsx` | First-time walkthrough |
| `TripForm.tsx` | Add/edit trip modal |
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
    │   ├── Planning Tool → PlanningTool (premium)
    │   ├── Location → LocationTracker (full)
    │   └── Settings → Alert toggles, thresholds
    │
    └── Modals
        ├── TripForm (add/edit)
        └── SchengenOnboarding (first-time)
```

---

## 5. What's Still Pending

From `SCHENGEN-MONAEO-PARITY-PLAN.md`, these features are NOT yet implemented:

| Feature | Description | Priority |
|---------|-------------|----------|
| **Calendar Sync** | Google/Outlook OAuth integration | P1 |
| **Multi-Jurisdiction** | US state residency rules, UK, etc. | P1 |
| **CSV/ICS Import** | Import trips from files | P2 |
| **PWA + Offline** | Progressive Web App, offline mode | P3 |
| **AI Suggestions** | Smart trip recommendations | P3 |
| **Family Tracking** | Group/family trip sharing | P4 |
| **Analytics Dashboard** | Travel patterns visualization | P3 |

### What We Can't Build (Limitations)
- True background GPS (requires native mobile app)
- Credit card import (requires Plaid + PCI compliance)
- "Audit-certified" claims (requires legal partnership)

---

## 6. Testing Checklist

### Core Functionality
- [ ] Add trip with dates and country
- [ ] Edit existing trip
- [ ] Delete trip
- [ ] Day counter updates correctly
- [ ] Status badge changes at thresholds

### Location Check-in
- [ ] Browser prompts for location permission
- [ ] Check-in creates/extends trip for Schengen country
- [ ] Location history shows in Location tab
- [ ] Delete individual entry works
- [ ] Clear all history works

### Smart Detection
- [ ] Banner appears when timezone changes
- [ ] IP detection suggests correct country
- [ ] Dismiss banner hides for today
- [ ] Check-in from banner works

### Premium Features
- [ ] Calendar view shows trip days
- [ ] Navigate months works
- [ ] Planning tool calculates violations
- [ ] PDF report generates and previews

### Alerts & Onboarding
- [ ] Email alerts toggle saves
- [ ] Test alert sends email
- [ ] Onboarding shows for first-time users
- [ ] Help button (?) reopens tour

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
- Lint: 0 warnings
- Build: Successful

---

## 8. Known Issues / Notes

### Timezone Fix Applied
**Issue:** Check-in times displayed incorrectly
**Fix:** Return ISO 8601 format with timezone:
```php
'recordedAt' => gmdate('c') // "2025-12-28T14:52:00+00:00"
```

### Portal URL Routing
Direct links like `/portal/?view=schengen` now work correctly. The store reads `?view=` parameter on initial load.

### GitHub Sync
Added `relo2france-schengen-tracker` and `france-relocation-github-sync` (self-update) to managed plugins list.

---

## 9. To Resume Next Session

**Priority tasks:**

1. **Calendar Sync (Phase 2 from original plan)**
   - Google Calendar OAuth integration
   - Parse travel events automatically
   - Create trips from calendar

2. **Multi-Jurisdiction Support**
   - US state residency rules
   - UK visitor rules
   - Configurable day/window limits

3. **Reference:** `SCHENGEN-MONAEO-PARITY-PLAN.md` for detailed specs

---

## 10. GitHub Repositories

- **Main Plugin:** Relo2France/france-relocation-assistant
- **Member Tools:** Relo2France/france-relocation-member-tools
- **Schengen Tracker:** relo2france-schengen-tracker (within main repo)

---

*Last Updated: December 28, 2025*
