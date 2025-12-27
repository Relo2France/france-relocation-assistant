# Schengen Tracker Enhancement Plan: Monaeo Feature Parity + Beyond

**Created:** December 27, 2025
**Branch:** `claude/review-handoff-docs-QtcLj`
**Status:** Planning

---

## Executive Summary

This plan outlines the implementation strategy to bring the Relo2France Schengen Tracker to feature parity with Monaeo ($999/year product), plus additional features that exceed their offering. The plan is organized into 6 phases over an estimated development effort.

### Feature Categories

| Category | Can Replicate? | Notes |
|----------|---------------|-------|
| GPS Auto-Tracking | ⚠️ Partial | Browser geolocation + PWA, not native app background GPS |
| Calendar Sync | ✅ Full | Google Calendar, Outlook, iCal import/export |
| Multi-Jurisdiction | ✅ Full | US states + international rules |
| Audit-Ready Reports | ✅ Full | Professional PDF templates |
| Push Notifications | ✅ Full | Web Push API + email |
| Data Import | ✅ Full | CSV, ICS, booking confirmations |
| Native Mobile App | ❌ No | Would require separate React Native project |
| Credit Card Integration | ❌ No | Requires Plaid/financial partnerships |
| "Audit-Certified" Claims | ❌ No | Would need legal/tax expert validation |

---

## What We CANNOT Replicate (Limitations)

### 1. True Background GPS Tracking
**Monaeo:** Native iOS/Android app runs GPS in background 24/7
**Our Limitation:** Browser geolocation requires user interaction and page focus
**Workaround:**
- Progressive Web App (PWA) with periodic check-ins
- Smart prompts when app is open ("Log today's location")
- Integration with travel calendars for automatic detection
- Optional: Future React Native app (separate major project)

### 2. Credit Card / Booking System Integration
**Monaeo:** Imports travel data from credit cards and booking systems
**Our Limitation:** Requires Plaid integration ($$$), PCI compliance, and partnerships
**Workaround:**
- Email parsing for booking confirmations (Gmail API)
- Manual import from downloaded bank statements (CSV)
- Integration with TripIt (free API available)
- Airline/hotel confirmation email forwarding

### 3. "Audit-Certified" / "IRS-Ready" Claims
**Monaeo:** Claims reports are "developed with SALT law experts"
**Our Limitation:** Cannot make legal claims without actual expert validation
**Workaround:**
- Professional, detailed report templates
- Include all data an auditor would need
- Partner with tax advisory firm for review (future)
- Add disclaimer: "Consult your tax advisor"

### 4. Native Mobile Apps
**Monaeo:** Native iOS (15.6+) and Android apps
**Our Limitation:** Would require React Native development, app store accounts
**Workaround:**
- PWA with "Add to Home Screen" capability
- Responsive design optimized for mobile
- Push notifications via Web Push API
- Future: Consider React Native if demand justifies

---

## Phase 1: Location Tracking (Browser + Smart Detection)

### 1.1 Browser Geolocation Integration

**New Files:**
- `portal/src/components/schengen/LocationTracker.tsx`
- `portal/src/hooks/useGeolocation.ts`
- `includes/class-framt-schengen-location.php`

**Database Changes:**
```sql
ALTER TABLE wp_fra_schengen_trips
ADD COLUMN location_source VARCHAR(20) DEFAULT 'manual',
ADD COLUMN location_lat DECIMAL(10, 8) NULL,
ADD COLUMN location_lng DECIMAL(11, 8) NULL,
ADD COLUMN location_accuracy FLOAT NULL,
ADD COLUMN location_timestamp DATETIME NULL;

CREATE TABLE wp_fra_schengen_location_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  lat DECIMAL(10, 8) NOT NULL,
  lng DECIMAL(11, 8) NOT NULL,
  accuracy FLOAT,
  country_code VARCHAR(2),
  country_name VARCHAR(100),
  city VARCHAR(100),
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_date (user_id, recorded_at)
);
```

**API Endpoints:**
```
POST /schengen/location          - Store current location
GET  /schengen/location/history  - Get location log
GET  /schengen/location/today    - Get today's location status
POST /schengen/location/auto-trip - Auto-create trip from locations
```

**React Components:**
```typescript
// LocationTracker.tsx
interface Props {
  onLocationDetected: (country: string) => void;
}

export default function LocationTracker({ onLocationDetected }: Props) {
  const [permission, setPermission] = useState<'prompt' | 'granted' | 'denied'>('prompt');
  const [currentCountry, setCurrentCountry] = useState<string | null>(null);

  // Request permission, track location
  // Reverse geocode to country
  // Prompt user to log trip if in Schengen zone
}
```

**Features:**
- [ ] Request geolocation permission with clear explanation
- [ ] Reverse geocode coordinates to country (OpenStreetMap Nominatim - free)
- [ ] Detect if user is in Schengen zone
- [ ] Prompt to create/extend trip when location changes
- [ ] Show current location on mini-map
- [ ] Location history timeline
- [ ] Privacy controls (user can disable, delete history)

### 1.2 Smart Location Detection

**Passive Detection Methods:**
- [ ] Timezone change detection (Intl.DateTimeFormat)
- [ ] Language/locale changes
- [ ] IP-based country detection (fallback, less accurate)

**Active Prompts:**
- [ ] Daily check-in reminder (if enabled)
- [ ] "Where are you today?" notification
- [ ] Smart suggestions based on calendar events

---

## Phase 2: Calendar Sync (Google, Outlook, iCal)

### 2.1 Google Calendar Integration

**New Files:**
- `includes/class-framt-schengen-calendar.php`
- `portal/src/components/schengen/CalendarSync.tsx`
- `portal/src/components/schengen/CalendarSyncSettings.tsx`

**OAuth Flow:**
```
1. User clicks "Connect Google Calendar"
2. Redirect to Google OAuth consent screen
3. Callback receives auth code
4. Exchange for access/refresh tokens
5. Store encrypted tokens in user meta
6. Sync calendar events with travel keywords
```

**Database Changes:**
```sql
CREATE TABLE wp_fra_calendar_connections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  provider ENUM('google', 'outlook', 'apple') NOT NULL,
  access_token TEXT,
  refresh_token TEXT,
  token_expires_at DATETIME,
  last_sync_at DATETIME,
  sync_status ENUM('active', 'error', 'expired') DEFAULT 'active',
  settings JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY idx_user_provider (user_id, provider)
);

CREATE TABLE wp_fra_calendar_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  connection_id BIGINT UNSIGNED NOT NULL,
  external_event_id VARCHAR(255),
  title VARCHAR(500),
  start_date DATE,
  end_date DATE,
  location VARCHAR(500),
  detected_country VARCHAR(100),
  imported_as_trip_id BIGINT UNSIGNED NULL,
  status ENUM('pending', 'imported', 'skipped', 'conflict') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_status (user_id, status)
);
```

**API Endpoints:**
```
GET  /schengen/calendar/providers       - List available providers
POST /schengen/calendar/connect         - Start OAuth flow
GET  /schengen/calendar/callback        - OAuth callback handler
GET  /schengen/calendar/connections     - List user's connections
DELETE /schengen/calendar/connections/{id} - Disconnect provider
POST /schengen/calendar/sync            - Trigger manual sync
GET  /schengen/calendar/events          - List detected events
POST /schengen/calendar/events/import   - Import selected events as trips
POST /schengen/calendar/events/skip     - Mark events as skipped
```

**Sync Logic:**
```php
// Keywords to detect travel events
$travel_keywords = [
  'flight', 'hotel', 'airbnb', 'trip', 'travel', 'vacation',
  'paris', 'berlin', 'rome', // Schengen capitals
  // ... all Schengen country names and major cities
];

// Location parsing
// "Hotel in Paris, France" -> France
// "Flight to Berlin" -> Germany
// Event location field -> reverse geocode if needed
```

**Features:**
- [ ] OAuth 2.0 connection with Google Calendar
- [ ] OAuth 2.0 connection with Microsoft Outlook
- [ ] iCal URL import (Apple Calendar, any iCal feed)
- [ ] Automatic event detection with smart keyword matching
- [ ] Location parsing from event title/location fields
- [ ] Preview detected trips before import
- [ ] Conflict detection (overlapping trips)
- [ ] Two-way sync option (export trips back to calendar)
- [ ] Sync frequency settings (daily, manual only)
- [ ] Disconnect and revoke access

### 2.2 iCal File Import

**Features:**
- [ ] Upload .ics file directly
- [ ] Parse VEVENT entries
- [ ] Extract DTSTART, DTEND, SUMMARY, LOCATION
- [ ] Smart location detection
- [ ] Preview and bulk import

---

## Phase 3: Multi-Jurisdiction Support

### 3.1 Jurisdiction Rules Engine

**New Files:**
- `includes/class-framt-jurisdiction-rules.php`
- `portal/src/components/schengen/JurisdictionManager.tsx`
- `portal/src/components/schengen/JurisdictionSelector.tsx`

**Database Changes:**
```sql
CREATE TABLE wp_fra_jurisdiction_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  type ENUM('country', 'state', 'zone') NOT NULL,
  parent_zone VARCHAR(20) NULL,
  days_allowed INT NOT NULL,
  window_days INT NOT NULL,
  counting_method ENUM('calendar', 'rolling', 'fiscal_year') DEFAULT 'rolling',
  notes TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  INDEX idx_type (type)
);

-- Migrate existing trips table
ALTER TABLE wp_fra_schengen_trips
ADD COLUMN jurisdiction_code VARCHAR(20) DEFAULT 'schengen',
ADD COLUMN jurisdiction_type VARCHAR(20) DEFAULT 'zone';

-- Pre-populate rules
INSERT INTO wp_fra_jurisdiction_rules (code, name, type, days_allowed, window_days, notes) VALUES
('schengen', 'Schengen Zone', 'zone', 90, 180, 'Standard 90/180 rule for visa-free travel'),
('us_b1b2', 'US B1/B2 Visa', 'country', 180, 365, 'Maximum 180 days per visit, complex rules'),
('us_vwp', 'US Visa Waiver Program', 'country', 90, 180, '90 days including Canada/Mexico'),
('uk_visitor', 'UK Standard Visitor', 'country', 180, 365, '180 days in any 12-month period'),
('us_ny', 'New York State', 'state', 183, 365, 'Statutory residency threshold'),
('us_ca', 'California', 'state', 183, 365, 'Presumption of residency'),
('us_fl', 'Florida', 'state', 183, 365, 'Domicile + physical presence'),
-- Add more jurisdictions as needed
```

**Algorithm Adaptation:**
```typescript
// Generalized calculation function
interface JurisdictionRule {
  code: string;
  name: string;
  daysAllowed: number;
  windowDays: number;
  countingMethod: 'calendar' | 'rolling' | 'fiscal_year';
}

function calculateDaysUsed(
  trips: Trip[],
  rule: JurisdictionRule,
  referenceDate: Date
): number {
  switch (rule.countingMethod) {
    case 'rolling':
      return calculateRollingWindow(trips, rule.windowDays, referenceDate);
    case 'calendar':
      return calculateCalendarYear(trips, referenceDate);
    case 'fiscal_year':
      return calculateFiscalYear(trips, referenceDate);
  }
}
```

**Features:**
- [ ] Jurisdiction selector in trip form
- [ ] Multiple active jurisdiction trackers
- [ ] Separate dashboards per jurisdiction
- [ ] Combined view showing all jurisdictions
- [ ] Custom jurisdiction rules (user-defined)
- [ ] Jurisdiction-specific alerts
- [ ] Cross-jurisdiction planning (e.g., US + Schengen)

### 3.2 US State Residency Tracking

**State-Specific Rules:**
```javascript
const usStateRules = {
  'NY': {
    name: 'New York',
    daysThreshold: 183,
    window: 365,
    countPartialDays: true,
    hasAbodeRequirement: true,
    notes: 'Statutory residency: 183+ days AND permanent place of abode'
  },
  'CA': {
    name: 'California',
    daysThreshold: 183, // approximate
    window: 365,
    usesFactsAndCircumstances: true,
    notes: 'No bright-line test, uses facts and circumstances'
  },
  // ... other states
};
```

**Features:**
- [ ] US state selector (50 states + DC)
- [ ] State-specific day counting rules
- [ ] Partial day handling (some states count any part of day)
- [ ] Domicile vs residency distinction
- [ ] Tax nexus warnings
- [ ] State-specific report templates

---

## Phase 4: Enhanced Reporting

### 4.1 Professional Report Templates

**New Files:**
- `includes/class-framt-schengen-reports.php`
- `includes/templates/report-schengen-detailed.php`
- `includes/templates/report-us-state.php`
- `includes/templates/report-multi-jurisdiction.php`
- `portal/src/components/schengen/ReportGenerator.tsx`

**Report Types:**
```php
$report_types = [
  'schengen_summary' => [
    'name' => 'Schengen Summary Report',
    'description' => 'Overview of 90/180 compliance status',
    'premium' => false,
  ],
  'schengen_detailed' => [
    'name' => 'Schengen Detailed Report',
    'description' => 'Full trip history with daily breakdown',
    'premium' => true,
  ],
  'schengen_audit' => [
    'name' => 'Schengen Audit Report',
    'description' => 'Professional format for border officials',
    'premium' => true,
  ],
  'us_state_residency' => [
    'name' => 'US State Residency Report',
    'description' => 'Day count for state tax purposes',
    'premium' => true,
  ],
  'multi_jurisdiction' => [
    'name' => 'Multi-Jurisdiction Overview',
    'description' => 'Combined report for all tracked jurisdictions',
    'premium' => true,
  ],
  'annual_summary' => [
    'name' => 'Annual Travel Summary',
    'description' => 'Full year travel history with statistics',
    'premium' => true,
  ],
];
```

**Report Content Enhancements:**
- [ ] Cover page with user info and date range
- [ ] Executive summary with key metrics
- [ ] Visual charts (days used bar chart, country breakdown pie chart)
- [ ] Month-by-month breakdown table
- [ ] Full trip history with entry/exit dates
- [ ] Location verification section (if GPS data available)
- [ ] Certification statement with timestamp
- [ ] Print-optimized CSS (page breaks, headers/footers)
- [ ] Digital signature placeholder
- [ ] Advisor notes section (editable)

### 4.2 PDF Generation

**Option A: Server-Side PDF (Recommended)**
```php
// Using TCPDF or DOMPDF library
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

class FRAMT_PDF_Generator {
  public function generate_schengen_report($user_id, $options = []) {
    $pdf = new TCPDF();
    $pdf->SetTitle('Schengen Compliance Report');
    // ... build PDF
    return $pdf->Output('report.pdf', 'S'); // Return as string
  }
}
```

**Option B: Client-Side PDF**
```typescript
// Using jsPDF + html2canvas
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

async function generatePDF(element: HTMLElement): Promise<Blob> {
  const canvas = await html2canvas(element);
  const pdf = new jsPDF();
  pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0);
  return pdf.output('blob');
}
```

**Features:**
- [ ] Multiple export formats (PDF, HTML, CSV)
- [ ] Date range selection
- [ ] Jurisdiction filter
- [ ] Include/exclude options (notes, locations, etc.)
- [ ] Branding customization (logo, colors)
- [ ] Email report directly to advisor
- [ ] Scheduled report generation (monthly/quarterly)

---

## Phase 5: Notifications & Alerts

### 5.1 Web Push Notifications

**New Files:**
- `portal/public/sw.js` (service worker)
- `portal/src/hooks/usePushNotifications.ts`
- `includes/class-framt-push-notifications.php`

**Database Changes:**
```sql
CREATE TABLE wp_fra_push_subscriptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  endpoint TEXT NOT NULL,
  p256dh_key VARCHAR(255),
  auth_key VARCHAR(255),
  user_agent VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME,
  INDEX idx_user (user_id)
);
```

**Service Worker:**
```javascript
// portal/public/sw.js
self.addEventListener('push', (event) => {
  const data = event.data.json();

  self.registration.showNotification(data.title, {
    body: data.body,
    icon: '/icons/schengen-icon-192.png',
    badge: '/icons/badge-72.png',
    tag: data.tag,
    data: data.url,
    actions: [
      { action: 'view', title: 'View Dashboard' },
      { action: 'dismiss', title: 'Dismiss' }
    ]
  });
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  if (event.action === 'view') {
    clients.openWindow(event.notification.data);
  }
});
```

**Notification Types:**
```php
$notification_types = [
  'threshold_warning' => [
    'title' => 'Schengen Alert: {days} days used',
    'body' => 'You have used {days} of 90 days in the Schengen zone.',
    'icon' => 'warning',
  ],
  'threshold_danger' => [
    'title' => 'URGENT: Only {remaining} days remaining!',
    'body' => 'You are approaching the 90-day Schengen limit.',
    'icon' => 'danger',
  ],
  'trip_reminder' => [
    'title' => 'Upcoming trip: {destination}',
    'body' => 'Your trip to {destination} starts in {days} days.',
    'icon' => 'calendar',
  ],
  'day_expiring' => [
    'title' => 'Days expiring tomorrow',
    'body' => '{count} days will drop off your 180-day window tomorrow.',
    'icon' => 'info',
  ],
  'calendar_sync' => [
    'title' => 'New trips detected',
    'body' => 'Found {count} potential trips in your calendar.',
    'icon' => 'calendar',
  ],
];
```

**Features:**
- [ ] Web Push API subscription flow
- [ ] Permission request with explanation
- [ ] Multiple notification types
- [ ] User preferences per notification type
- [ ] Quiet hours setting
- [ ] Test notification button
- [ ] Fallback to email if push fails

### 5.2 In-App Notification Center

**New Component:**
```typescript
// NotificationCenter.tsx
interface Notification {
  id: string;
  type: string;
  title: string;
  body: string;
  createdAt: string;
  readAt: string | null;
  actionUrl?: string;
}

export default function NotificationCenter() {
  const { notifications, unreadCount, markAsRead } = useNotifications();

  return (
    <Popover>
      <PopoverTrigger>
        <Bell />
        {unreadCount > 0 && <Badge>{unreadCount}</Badge>}
      </PopoverTrigger>
      <PopoverContent>
        <NotificationList
          notifications={notifications}
          onRead={markAsRead}
        />
      </PopoverContent>
    </Popover>
  );
}
```

**Features:**
- [ ] Bell icon in header with unread count
- [ ] Dropdown notification list
- [ ] Mark as read (individual and all)
- [ ] Notification preferences link
- [ ] Empty state with helpful message
- [ ] Real-time updates (polling or WebSocket)

---

## Phase 6: Data Import & PWA

### 6.1 Multi-Source Data Import

**New Files:**
- `includes/class-framt-schengen-import.php`
- `portal/src/components/schengen/ImportWizard.tsx`
- `portal/src/components/schengen/ImportPreview.tsx`

**Import Sources:**
```php
$import_sources = [
  'csv' => [
    'name' => 'CSV File',
    'description' => 'Import from spreadsheet (Excel, Google Sheets)',
    'accept' => '.csv',
    'parser' => 'parse_csv_import',
  ],
  'ics' => [
    'name' => 'Calendar File (iCal)',
    'description' => 'Import from calendar export (.ics)',
    'accept' => '.ics',
    'parser' => 'parse_ics_import',
  ],
  'tripit' => [
    'name' => 'TripIt',
    'description' => 'Import from TripIt account',
    'auth' => 'oauth',
    'parser' => 'parse_tripit_import',
  ],
  'email_forwarding' => [
    'name' => 'Email Forwarding',
    'description' => 'Forward booking confirmations to import',
    'address' => 'trips-{user_id}@import.relo2france.com',
    'parser' => 'parse_email_import',
  ],
];
```

**CSV Template:**
```csv
start_date,end_date,country,category,notes
2025-01-15,2025-01-20,France,business,Paris conference
2025-02-01,2025-02-14,Spain,personal,Barcelona vacation
```

**Import Wizard Steps:**
1. Select import source
2. Upload file / connect account
3. Map columns (for CSV)
4. Preview detected trips
5. Resolve conflicts
6. Confirm import

**Features:**
- [ ] CSV file upload with column mapping
- [ ] ICS (iCal) file parsing
- [ ] TripIt OAuth integration
- [ ] Email forwarding address for confirmations
- [ ] Duplicate detection (same dates/country)
- [ ] Conflict resolution UI
- [ ] Dry-run preview before commit
- [ ] Import history log
- [ ] Undo last import

### 6.2 Progressive Web App (PWA)

**New Files:**
- `portal/public/manifest.json`
- `portal/public/sw.js`
- `portal/src/hooks/usePWA.ts`

**Web App Manifest:**
```json
{
  "name": "Relo2France Schengen Tracker",
  "short_name": "Schengen",
  "description": "Track your Schengen 90/180 day compliance",
  "start_url": "/member-portal/schengen",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

**Service Worker Caching:**
```javascript
// Cache strategies
const CACHE_NAME = 'schengen-v1';
const STATIC_ASSETS = [
  '/member-portal/',
  '/assets/portal/main.js',
  '/assets/portal/main.css',
  // ... other static assets
];

// Cache-first for static assets
// Network-first for API calls
// Background sync for offline mutations
```

**Offline Capabilities:**
```typescript
// IndexedDB schema
interface OfflineStore {
  trips: SchengenTrip[];
  pendingMutations: PendingMutation[];
  lastSync: string;
  settings: SchengenSettings;
}

// Sync when online
window.addEventListener('online', () => {
  syncPendingMutations();
});
```

**Features:**
- [ ] "Add to Home Screen" prompt
- [ ] App-like experience (no browser UI)
- [ ] Offline trip viewing
- [ ] Offline trip creation (syncs when online)
- [ ] Background sync for pending changes
- [ ] Cache management (storage limits)
- [ ] Update notification when new version available

---

## Phase 7: "Plus" Features (Beyond Monaeo)

### 7.1 AI-Powered Features

**Smart Trip Suggestions:**
```typescript
// Analyze patterns and suggest optimal trip dates
interface TripSuggestion {
  suggestedStart: string;
  suggestedEnd: string;
  reason: string;
  daysAvailable: number;
  riskLevel: 'low' | 'medium' | 'high';
}

function suggestNextTrip(trips: Trip[], desiredLength: number): TripSuggestion {
  // Find optimal window where days are expiring
  // Suggest dates that maximize available days
  // Consider existing planned trips
}
```

**Features:**
- [ ] AI trip planning suggestions
- [ ] Natural language trip entry ("I'm going to Paris next week")
- [ ] Anomaly detection (unusual patterns)
- [ ] Predictive alerts (projected violations)

### 7.2 Family/Group Tracking

**Database:**
```sql
CREATE TABLE wp_fra_schengen_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  owner_user_id BIGINT UNSIGNED,
  created_at DATETIME
);

CREATE TABLE wp_fra_schengen_group_members (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  group_id BIGINT UNSIGNED,
  user_id BIGINT UNSIGNED,
  role ENUM('owner', 'admin', 'member') DEFAULT 'member',
  can_view BOOLEAN DEFAULT TRUE,
  can_edit BOOLEAN DEFAULT FALSE
);
```

**Features:**
- [ ] Create family/travel group
- [ ] Invite members (email or link)
- [ ] Shared trip visibility
- [ ] Synchronized trip creation (add for all members)
- [ ] Group dashboard showing all members
- [ ] Individual vs group reports

### 7.3 Travel Document Integration

**Link Trips to Documents:**
```sql
ALTER TABLE wp_fra_schengen_trips
ADD COLUMN passport_id BIGINT UNSIGNED NULL,
ADD COLUMN visa_id BIGINT UNSIGNED NULL,
ADD COLUMN flight_confirmation VARCHAR(100) NULL,
ADD COLUMN hotel_confirmation VARCHAR(100) NULL;
```

**Features:**
- [ ] Link trip to passport (expiration warning)
- [ ] Link trip to visa (validity check)
- [ ] Attach booking confirmations
- [ ] Document expiration alerts
- [ ] Travel document checklist per trip

### 7.4 Analytics Dashboard

**New Component: SchengenAnalytics.tsx**

**Visualizations:**
- [ ] Days used over time (line chart)
- [ ] Country breakdown (pie chart)
- [ ] Monthly travel patterns (bar chart)
- [ ] Year-over-year comparison
- [ ] Business vs personal split
- [ ] Average trip duration
- [ ] Most visited countries
- [ ] Seasonal patterns

### 7.5 Integration Hub

**Third-Party Integrations:**
- [ ] TripIt (trip import)
- [ ] Google Maps Timeline (location history import)
- [ ] Notion (export to database)
- [ ] Zapier/Make webhooks
- [ ] IFTTT integration
- [ ] Slack notifications
- [ ] WhatsApp alerts (via Twilio)

---

## Implementation Priority Matrix

| Phase | Feature | Effort | Impact | Priority |
|-------|---------|--------|--------|----------|
| 1 | Browser Geolocation | Medium | High | P1 |
| 2 | Google Calendar Sync | High | High | P1 |
| 2 | iCal Import | Low | Medium | P2 |
| 3 | Multi-Jurisdiction | High | High | P1 |
| 3 | US State Tracking | Medium | Medium | P2 |
| 4 | Professional Reports | Medium | High | P1 |
| 4 | PDF Generation | Medium | Medium | P2 |
| 5 | Push Notifications | Medium | High | P1 |
| 5 | In-App Notifications | Low | Medium | P2 |
| 6 | CSV Import | Low | Medium | P2 |
| 6 | PWA + Offline | High | Medium | P3 |
| 7 | AI Suggestions | High | Medium | P3 |
| 7 | Family Tracking | High | Low | P4 |
| 7 | Analytics Dashboard | Medium | Medium | P3 |

---

## Technical Requirements

### Dependencies to Add

**PHP (Composer):**
```json
{
  "require": {
    "tecnickcom/tcpdf": "^6.6",
    "google/apiclient": "^2.15",
    "league/oauth2-client": "^2.7",
    "minishlink/web-push": "^8.0"
  }
}
```

**JavaScript (npm):**
```json
{
  "dependencies": {
    "jspdf": "^2.5.1",
    "html2canvas": "^1.4.1",
    "chart.js": "^4.4.1",
    "react-chartjs-2": "^5.2.0",
    "idb": "^7.1.1",
    "workbox-core": "^7.0.0",
    "workbox-precaching": "^7.0.0"
  }
}
```

### API Keys Required

| Service | Purpose | Cost |
|---------|---------|------|
| Google OAuth | Calendar sync | Free |
| Microsoft Graph | Outlook sync | Free |
| OpenStreetMap Nominatim | Reverse geocoding | Free (rate limited) |
| VAPID Keys | Web Push | Free (self-generated) |
| TripIt API | Trip import | Free tier available |

### Infrastructure Considerations

1. **Cron Jobs:** Need reliable WordPress cron or external cron for:
   - Daily alert processing
   - Calendar sync polling
   - Background sync processing

2. **Storage:** Location history can grow large:
   - Consider retention policy (1 year?)
   - Compression for older data
   - User quota limits

3. **OAuth Tokens:** Need secure storage:
   - Encrypt tokens at rest
   - Handle token refresh gracefully
   - Revocation handling

---

## Testing Strategy

### Unit Tests
- [ ] Multi-jurisdiction calculation algorithms
- [ ] Calendar event parsing
- [ ] CSV/ICS file parsing
- [ ] Notification scheduling logic

### Integration Tests
- [ ] OAuth flow (mock providers)
- [ ] Push notification delivery
- [ ] Offline sync
- [ ] Report generation

### E2E Tests
- [ ] Complete import wizard flow
- [ ] Calendar connection + sync
- [ ] Notification permission + delivery
- [ ] PWA install + offline usage

---

## Migration Path

### For Existing Users

1. **Data Migration:** None required (additive changes only)
2. **Settings Migration:** New settings default to sensible values
3. **Feature Discovery:** In-app announcements for new features
4. **Opt-In Features:** Location tracking, calendar sync require explicit consent

### Database Migrations

```php
// Run on plugin update
function framt_schengen_upgrade_2_0() {
  global $wpdb;

  // Add new columns to trips table
  $wpdb->query("ALTER TABLE {$wpdb->prefix}fra_schengen_trips ...");

  // Create new tables
  require_once FRAMT_PLUGIN_DIR . 'includes/class-framt-portal-schema.php';
  FRAMT_Portal_Schema::create_tables();

  // Update version
  update_option('framt_schengen_db_version', '2.0');
}
```

---

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Feature Parity | 90% of Monaeo features | Feature checklist completion |
| User Adoption | 50% use new features | Analytics tracking |
| Engagement | 2x dashboard visits | Session tracking |
| Premium Conversion | +20% | MemberPress metrics |
| User Satisfaction | 4.5+ stars | In-app feedback |

---

## Conclusion

This plan outlines a comprehensive path to match and exceed Monaeo's feature set. While we cannot replicate native mobile app background GPS or credit card integrations, we can provide:

1. **Smart location detection** via browser + calendar sync
2. **Full calendar integration** with Google, Outlook, and iCal
3. **Multi-jurisdiction tracking** including US states
4. **Professional reporting** with PDF export
5. **Modern notifications** via Web Push
6. **Flexible import** from multiple sources
7. **PWA experience** for mobile users

Plus unique features Monaeo doesn't offer:
- "What If" planning tool with suggestions
- AI-powered trip recommendations
- Family/group tracking
- Integration with France relocation workflow
- Document linking
- Analytics dashboard

The result will be a more comprehensive, France-relocation-focused tool that provides exceptional value compared to Monaeo's $999/year price point.
