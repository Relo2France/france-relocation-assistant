# Relo2France Handoff Document
## Session: December 28, 2025 - Schengen Tracker Plugin Development

---

## CURRENT VERSIONS

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v2.9.83 | Active |
| Member Tools Plugin | v1.0.80 | Active |
| Schengen Tracker Plugin | v1.1.0 | Active |
| Theme | v1.2.3 | Active |

---

## WHAT WAS BUILT THIS SESSION

### SCHENGEN TRACKER - COMPLETE FEATURE IMPLEMENTATION

Built a comprehensive Schengen 90/180 day compliance tracker as a premium add-on feature. All phases from the specification are now complete.

---

### Phase 1.0: Core Functionality (Previously Built)
- Trip entry form with Schengen country validation
- Rolling 180-day window calculation algorithm
- Dashboard with day counter and status badges
- Trip list with edit/delete functionality

### Phase 1.1: Browser Geolocation Check-in
**Location:** `relo2france-schengen-tracker/includes/class-r2f-schengen-location.php`

- Browser-based GPS location check-in
- Reverse geocoding via OpenStreetMap Nominatim API
- Location history tracking in `fra_schengen_location_log` table
- Clear history and delete individual entries
- **Auto-trip creation**: When checking in from a Schengen country:
  - Creates new trip for today if none exists
  - Extends yesterday's trip to today if applicable
  - Updates location data on existing trip

**API Endpoints:**
```
POST /wp-json/r2f-schengen/v1/location/store    - Store location check-in
GET  /wp-json/r2f-schengen/v1/location/history  - Get location history
GET  /wp-json/r2f-schengen/v1/location/today    - Check if checked in today
POST /wp-json/r2f-schengen/v1/location/clear    - Clear all history
DELETE /wp-json/r2f-schengen/v1/location/{id}   - Delete single entry
GET  /wp-json/r2f-schengen/v1/location/detect   - IP-based country detection
```

### Phase 1.2: Smart Location Detection
**Location:** `france-relocation-member-tools/portal/src/hooks/useLocationDetection.ts`

- Timezone change detection (compares browser timezone to stored)
- IP-based country detection via ip-api.com
- Smart check-in prompts when timezone changes
- Daily reminder system (configurable hour)
- `LocationDetectionBanner` component shows prompts

### Phase 2: Database Persistence ✅
**Location:** `relo2france-schengen-tracker/includes/class-r2f-schengen-schema.php`

**Database Tables:**
- `wp_fra_schengen_trips` - Trip records with location columns
- `wp_fra_schengen_location_log` - Location check-in history

**API Endpoints (class-r2f-schengen-api.php):**
```
GET/POST   /wp-json/r2f-schengen/v1/trips          - List/create trips
GET/PUT/DELETE /wp-json/r2f-schengen/v1/trips/{id} - Single trip CRUD
GET        /wp-json/r2f-schengen/v1/summary        - Current day count & status
GET/PUT    /wp-json/r2f-schengen/v1/settings       - User alert settings
POST       /wp-json/r2f-schengen/v1/simulate       - "What if" calculation
GET        /wp-json/r2f-schengen/v1/report         - Generate PDF report
GET        /wp-json/r2f-schengen/v1/feature-status - Premium status check
POST       /wp-json/r2f-schengen/v1/test-alert     - Send test email alert
```

### Phase 3: Enhanced Features ✅

#### Calendar View
**Location:** `portal/src/components/schengen/CalendarView.tsx`
- Monthly calendar visualization with navigation
- Days with Schengen presence highlighted in brand blue
- Future planned trips shown with dashed blue border
- Days outside 180-day window shown in gray
- Legend explaining color coding
- Mobile responsive with abbreviated labels

#### Planning Tool ("What If" Calculator)
**Location:** `portal/src/components/schengen/PlanningTool.tsx`
- Enter hypothetical future trip dates
- Check if trip would violate 90/180 rule
- Shows earliest safe start date if violation
- Shows maximum safe trip length from given date
- Visual results with green (safe) or red (violation) indicators

#### Alert System
**Location:** `relo2france-schengen-tracker/includes/class-r2f-schengen-alerts.php`
- Three threshold levels: 60 (warning), 80 (danger), 85 (urgent)
- Daily cron job at 8am UTC
- Prevents duplicate alerts within 7 days
- HTML emails with brand styling
- Test alert button in Settings tab

#### PDF Report Generation
**Location:** `portal/src/components/schengen/ReportExport.tsx`
- Generate report button in header
- Preview modal with summary stats
- HTML report rendered in iframe
- Download as HTML option
- Print / Save as PDF using browser print dialog

### Phase 4: Polish ✅

#### Email Notifications
- Fully functional email system via `class-r2f-schengen-alerts.php`
- Subject lines vary by severity level
- Links to tracker and settings in email body
- Unsubscribe via settings toggle

#### Mobile Responsiveness
- All components use Tailwind responsive classes
- Calendar uses abbreviated weekday/month names on mobile
- Grid layouts adjust for different screen sizes
- Touch-friendly button sizes

#### Onboarding Flow (NEW)
**Location:** `portal/src/components/schengen/SchengenOnboarding.tsx`

5-step walkthrough for first-time users:
1. **Welcome** - Explains the 90/180 day rule
2. **Track Your Trips** - How to add trips
3. **Smart Location Check-in** - GPS feature explanation
4. **Planning Tool** - "What If" calculator benefits
5. **Email Alerts** - Threshold explanations (60/80/85 days)

**Features:**
- Auto-shows for first-time users
- Progress dots with click navigation
- Skip tour / Back / Next buttons
- Help button (?) in header to reopen
- Stores completion in localStorage (`r2f_schengen_onboarding_complete`)
- Large modal with spacious, professional design

#### Premium Tier Gating
**Location:** `relo2france-schengen-tracker/includes/class-r2f-schengen-premium.php`

3-tier priority system:
1. User meta override (admin can enable/disable per user)
2. Filter hook for external plugins (Member Tools integration)
3. Global setting fallback

**Free tier limitations:**
- Limited number of trips
- No calendar view
- No planning tool
- No PDF export

---

## KEY FILES - SCHENGEN TRACKER

### Backend (PHP)
| File | Purpose |
|------|---------|
| `class-r2f-schengen-core.php` | Main plugin class, initialization |
| `class-r2f-schengen-api.php` | REST API endpoints (~40+ endpoints) |
| `class-r2f-schengen-schema.php` | Database schema, migrations |
| `class-r2f-schengen-location.php` | Location check-in, auto-trip creation |
| `class-r2f-schengen-alerts.php` | Email notifications, cron jobs |
| `class-r2f-schengen-premium.php` | Premium feature gating |

### Frontend (React/TypeScript)
| File | Purpose |
|------|---------|
| `SchengenDashboard.tsx` | Main dashboard component |
| `CalendarView.tsx` | Monthly calendar visualization |
| `PlanningTool.tsx` | "What if" trip calculator |
| `ReportExport.tsx` | PDF report generation |
| `LocationTracker.tsx` | GPS check-in interface |
| `LocationDetectionBanner.tsx` | Smart prompts for check-in |
| `SchengenOnboarding.tsx` | First-time user walkthrough |
| `TripForm.tsx` | Add/edit trip modal |
| `TripList.tsx` | Trip list with actions |
| `DayCounter.tsx` | Circular progress display |
| `StatusBadge.tsx` | Green/yellow/red status indicator |
| `useSchengenStore.ts` | Zustand store for trips/settings |
| `useLocationDetection.ts` | Timezone/location detection hook |
| `schengenUtils.ts` | Calculation utilities |

---

## ARCHITECTURE - SCHENGEN TRACKER

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
    ├── Warning Banners (conditional)
    │
    ├── Tab Navigation
    │   ├── Trip List → TripList component
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

## TIMEZONE FIX APPLIED

**Issue:** Check-in times displayed incorrectly (e.g., showing 1:52 PM when it was 9:52 AM local)

**Root Cause:** Database stored GMT times, but response returned `current_time('mysql')` which is local time

**Fix:** Return ISO 8601 format with timezone indicator:
```php
// In store_location_debug():
'recordedAt' => gmdate('c'), // Returns "2025-12-28T14:52:00+00:00"

// In format_location_row():
$recorded_at_iso = gmdate('c', strtotime($row->recorded_at . ' UTC'));
```

JavaScript correctly parses ISO 8601 and displays in user's local timezone.

---

## TESTING CHECKLIST - SCHENGEN TRACKER

### Core Functionality
- [ ] Add trip with dates and country
- [ ] Edit existing trip
- [ ] Delete trip
- [ ] Day counter updates correctly
- [ ] Status badge changes at thresholds (60/80/85/90)

### Location Check-in
- [ ] Browser prompts for location permission
- [ ] Check-in creates/extends trip for Schengen country
- [ ] Location history shows in Location tab
- [ ] Delete individual location entry works
- [ ] Clear all history works
- [ ] "Checked in today" status updates

### Smart Detection
- [ ] Banner appears when timezone changes
- [ ] IP detection suggests correct country
- [ ] Dismiss banner hides for today
- [ ] Check-in from banner works

### Premium Features
- [ ] Calendar view shows trip days highlighted
- [ ] Navigate months works
- [ ] Planning tool calculates violations correctly
- [ ] PDF report generates and previews
- [ ] Print to PDF works

### Alerts
- [ ] Email alerts toggle saves
- [ ] Test alert sends email
- [ ] Email contains correct day counts
- [ ] Settings link in email works

### Onboarding
- [ ] Shows automatically for first-time users
- [ ] Progress dots navigate between steps
- [ ] Skip tour works
- [ ] Get Started completes and closes
- [ ] Help button (?) reopens tour

---

## GITHUB REPOSITORY

**Main Plugin:** Relo2France/france-relocation-assistant
**Member Tools:** Relo2France/france-relocation-member-tools
**Schengen Tracker:** relo2france-schengen-tracker (within france-relocation-assistant repo)

**Development Branch:** `claude/tracker-plugin-dev-1iVyB`

---

## PREVIOUS SESSION NOTES (December 14, 2025)

### In-Chat Account System
- Complete account management within chat panel
- Custom MemberPress shortcodes for subscriptions/payments
- Member messaging/support ticket system

See previous handoff document sections below for details on:
- MemberPress integration
- Logout cookie redirect system (removed)
- Member Tools plugin architecture

---

*Last Updated: December 28, 2025*
