# Schengen Tracker Feature — Handoff Document for Claude Code

## Overview

Build a Schengen compliance tracker as a premium add-on feature for the relo2france Portal website. This tool helps Americans relocating to France (and other Schengen countries) track their days in the Schengen zone to comply with the 90/180-day rule before obtaining long-stay visas.

**Business Context:** Kevin and Lee are building relo2france.com as a resource for Americans relocating to France. The Schengen tracker would be a paid add-on feature providing real value to their target audience.

---

## The Problem This Solves

Americans visiting Schengen countries (before obtaining a long-stay visa) are limited to **90 days within any rolling 180-day period**. This is notoriously difficult to track manually because:

1. The 180-day window is **rolling** (not calendar-based)
2. Multiple short trips compound in unexpected ways
3. Overstaying can result in fines, deportation, and future visa denials
4. Users need to plan future travel without exceeding limits

**Competitor Reference:** Monaeo.com offers similar functionality at ~$1,000/year for high-net-worth individuals. A simpler, more affordable version for relocators is the target.

---

## relo2france Brand Guidelines

### Colors
```javascript
const brandColors = {
  blue: "#4A7BA7",      // Primary — headings, links, accent backgrounds
  gold: "#E5A54B",      // Secondary — highlights, special callouts, CTAs
  darkText: "#2D3748",  // Body text
  lightGray: "#718096", // Subdued text, captions
  lightBlue: "#EBF4FA"  // Background for stat cards and info boxes
};
```

### Typography
- Headings: System sans-serif stack, bold weights
- Body: System sans-serif, regular weight
- Stats/numbers: Medium weight, brand blue for emphasis

### Component Patterns (Existing in Portal)

**StatCard:**
```jsx
<StatCard label="Days Used" value="47" sublabel="of 90 allowed" />
// Light blue background, centered, bold value in brand blue
```

**DataRow:**
```jsx
<DataRow label="Next expiration" value="March 15, 2026" />
// Flex justified, label in light gray, value in dark text
```

**Info Box (brand blue):**
```jsx
<div className="rounded-lg p-4" style={{ backgroundColor: '#EBF4FA' }}>
  <h4 className="font-semibold mb-2" style={{ color: '#4A7BA7' }}>Title</h4>
  {/* content */}
</div>
```

**Callout Box (gold/warning):**
```jsx
<div className="rounded-lg p-3 border" style={{ backgroundColor: '#FEF3E2', borderColor: '#f5d9a8' }}>
  <p className="text-sm">
    <span className="font-medium" style={{ color: '#E5A54B' }}>⚠️ Warning:</span> Content...
  </p>
</div>
```

### Icons
- Use `lucide-react` throughout
- Common icons: MapPin, Calendar, Clock, AlertTriangle, Check, Plane, Globe

### Logo
- File is WebP format (despite .png extension)
- Convert before base64 embedding: `convert input.png output.png && base64 -w 0 output.png`

---

## Feature Specification: Schengen Tracker

### Core Features (MVP)

#### 1. Trip Entry
- **Fields:**
  - Start date (required)
  - End date (required)
  - Country (dropdown of Schengen countries, required)
  - Trip name/notes (optional)
  - Category: Personal / Business (optional, for future tax features)

- **Schengen Countries List:**
  ```javascript
  const SCHENGEN_COUNTRIES = [
    'Austria', 'Belgium', 'Bulgaria', 'Croatia', 'Czech Republic', 'Denmark',
    'Estonia', 'Finland', 'France', 'Germany', 'Greece', 'Hungary', 'Iceland',
    'Italy', 'Latvia', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Malta',
    'Netherlands', 'Norway', 'Poland', 'Portugal', 'Romania', 'Slovakia',
    'Slovenia', 'Spain', 'Sweden', 'Switzerland'
  ];
  ```
  Note: Bulgaria and Romania joined Schengen in 2024 (air/sea) with full land border integration in 2025.

- **Validation:**
  - End date must be >= start date
  - Warn if trip spans > 90 days (they'd already be over)
  - Allow past and future trips

#### 2. Dashboard / Home View
Display at a glance:

| Metric | Description |
|--------|-------------|
| **Days Used** | Days spent in Schengen within current 180-day window |
| **Days Remaining** | 90 minus days used |
| **Window Start** | Date 180 days ago |
| **Window End** | Today |
| **Status Indicator** | Green (0-60), Yellow (61-80), Red (81-90) |
| **Next "Expiration"** | Date when oldest days in window drop off |

**Visual Elements:**
- Circular or bar progress indicator showing 47/90 days used
- Color-coded status (green/yellow/red based on usage)
- Quick-add trip button prominently displayed

#### 3. Rolling Window Calculator

The core algorithm:

```javascript
/**
 * Calculate Schengen days in the 180-day window ending on referenceDate
 * @param {Array} trips - Array of {startDate, endDate, isSchengen}
 * @param {Date} referenceDate - The end date of the 180-day window (default: today)
 * @returns {number} - Total days spent in Schengen within the window
 */
function calculateSchengenDays(trips, referenceDate = new Date()) {
  const ref = new Date(referenceDate);
  ref.setHours(23, 59, 59, 999); // End of day
  
  const windowStart = new Date(ref);
  windowStart.setDate(windowStart.getDate() - 179); // 180-day window
  windowStart.setHours(0, 0, 0, 0); // Start of day
  
  const daysInWindow = new Set();
  
  trips.forEach(trip => {
    if (!trip.isSchengen) return;
    
    const tripStart = new Date(trip.startDate);
    const tripEnd = new Date(trip.endDate);
    
    // Clamp trip to window boundaries
    const effectiveStart = tripStart < windowStart ? windowStart : tripStart;
    const effectiveEnd = tripEnd > ref ? ref : tripEnd;
    
    if (effectiveStart <= effectiveEnd) {
      let current = new Date(effectiveStart);
      while (current <= effectiveEnd) {
        // Use ISO date string as key to avoid duplicates
        daysInWindow.add(current.toISOString().split('T')[0]);
        current.setDate(current.getDate() + 1);
      }
    }
  });
  
  return daysInWindow.size;
}
```

#### 4. Calendar View
- Monthly calendar showing:
  - Days with Schengen presence (highlighted in brand blue)
  - Days outside Schengen (neutral)
  - Future planned trips (lighter shade or dashed border)
- Click on a day to see trip details
- Navigate between months

#### 5. Trip List View
- Sortable/filterable list of all trips
- Columns: Date range, Country, Duration, Notes, Actions (Edit/Delete)
- Show which trips fall within current 180-day window

#### 6. Alerts & Warnings
- **Threshold Alerts:**
  - Yellow warning at 60 days used
  - Red warning at 80 days used
  - Critical alert at 85+ days
- **Future Trip Warnings:**
  - When adding a future trip, calculate if it would cause overstay
  - Show projected days used after the trip

#### 7. Planning Tool ("What If" Calculator)
Allow users to:
- Enter a hypothetical future trip
- See if it would violate the 90-day rule
- Calculate the earliest safe entry date for a given trip length
- Calculate the maximum trip length starting on a given date

```javascript
/**
 * Find the earliest date a user can enter Schengen for a trip of given length
 * @param {Array} trips - Existing trips
 * @param {number} tripLength - Desired trip length in days
 * @returns {Date} - Earliest safe entry date
 */
function findEarliestEntryDate(trips, tripLength) {
  let checkDate = new Date();
  const maxSearch = 365; // Don't search more than a year out
  
  for (let i = 0; i < maxSearch; i++) {
    // Create hypothetical trip
    const hypotheticalEnd = new Date(checkDate);
    hypotheticalEnd.setDate(hypotheticalEnd.getDate() + tripLength - 1);
    
    // Check each day of the hypothetical trip
    let wouldViolate = false;
    let current = new Date(checkDate);
    
    while (current <= hypotheticalEnd) {
      const daysUsed = calculateSchengenDays(trips, current) + 
        daysBetween(checkDate, current);
      if (daysUsed > 90) {
        wouldViolate = true;
        break;
      }
      current.setDate(current.getDate() + 1);
    }
    
    if (!wouldViolate) return checkDate;
    checkDate.setDate(checkDate.getDate() + 1);
  }
  
  return null; // Could not find a valid date within search range
}
```

#### 8. Report Generation (PDF Export)
Generate a printable/downloadable report showing:
- Summary of days used
- Complete trip history
- Current compliance status
- Useful for visa applications or border crossings

---

### Data Model

```typescript
interface Trip {
  id: string;                    // UUID
  userId: string;                // Owner
  startDate: string;             // ISO date (YYYY-MM-DD)
  endDate: string;               // ISO date (YYYY-MM-DD)
  country: string;               // Country name
  isSchengen: boolean;           // Auto-calculated from country
  category: 'personal' | 'business';
  notes?: string;
  createdAt: string;             // ISO timestamp
  updatedAt: string;             // ISO timestamp
}

interface User {
  id: string;
  email: string;
  name?: string;
  alertThresholds: {
    yellow: number;              // Default: 60
    red: number;                 // Default: 80
  };
  timezone: string;              // For accurate day calculations
  createdAt: string;
  subscription: 'free' | 'premium';
}

interface AlertSettings {
  userId: string;
  emailAlerts: boolean;
  yellowThreshold: number;       // Days (default 60)
  redThreshold: number;          // Days (default 80)
  upcomingTripReminders: boolean;
}
```

---

### UI Components to Build

```
src/components/schengen/
├── SchengenDashboard.jsx       # Main dashboard with stats
├── TripForm.jsx                # Add/edit trip modal or form
├── TripList.jsx                # Sortable table of trips
├── CalendarView.jsx            # Monthly calendar visualization
├── DayCounter.jsx              # Circular/bar progress display
├── StatusBadge.jsx             # Green/yellow/red indicator
├── PlanningTool.jsx            # "What if" calculator
├── AlertBanner.jsx             # Warning banners
├── ExpirationTimeline.jsx      # Shows when days "drop off"
└── ReportExport.jsx            # PDF generation
```

---

### API Endpoints (if backend exists)

```
GET    /api/schengen/trips           # List user's trips
POST   /api/schengen/trips           # Create trip
PUT    /api/schengen/trips/:id       # Update trip
DELETE /api/schengen/trips/:id       # Delete trip
GET    /api/schengen/summary         # Get current day count & status
GET    /api/schengen/calculate       # Calculate days for date range
POST   /api/schengen/simulate        # "What if" calculation
GET    /api/schengen/report          # Generate PDF report
```

---

### Questions for Claude Code to Determine

Before building, please check the existing Portal codebase and answer:

1. **Framework:** Is this Next.js, plain React, or something else?
2. **Routing:** App Router or Pages Router (if Next.js)?
3. **Database:** What database is being used (PostgreSQL, SQLite, Supabase, etc.)?
4. **ORM:** Prisma, Drizzle, raw SQL?
5. **Authentication:** Is there auth already? (NextAuth, Clerk, custom?)
6. **State Management:** React Context, Zustand, Redux, or just local state?
7. **Styling:** Tailwind CSS? Styled-components? CSS modules?
8. **Component Library:** Any existing UI library (shadcn/ui, etc.)?
9. **File Structure:** Where should new features go?
10. **Existing Components:** Can we reuse StatCard, DataRow, etc.?

---

### Implementation Order (Suggested)

**Phase 1: Core (Local State Only)**
1. Create data model and types
2. Build TripForm component
3. Build calculateSchengenDays function with tests
4. Build DayCounter display component
5. Build basic Dashboard view
6. Store trips in localStorage (temporary)

**Phase 2: Persistence**
1. Set up database schema
2. Create API routes
3. Connect frontend to API
4. Add user association

**Phase 3: Enhanced Features**
1. Calendar view
2. Planning tool / "what if" calculator
3. Alert system
4. PDF report generation

**Phase 4: Polish**
1. Email notifications (optional)
2. Mobile responsiveness
3. Onboarding flow
4. Premium tier gating

---

### Test Cases

```javascript
// Test: Single trip within window
const trips1 = [{ startDate: '2025-12-01', endDate: '2025-12-10', isSchengen: true }];
// Expected: 10 days (Dec 1-10 inclusive)

// Test: Trip partially outside window
// If today is 2025-12-26 and window starts 2025-06-30
const trips2 = [{ startDate: '2025-06-15', endDate: '2025-07-15', isSchengen: true }];
// Expected: Only days from June 30 - July 15 count (16 days)

// Test: Multiple non-overlapping trips
const trips3 = [
  { startDate: '2025-10-01', endDate: '2025-10-10', isSchengen: true },
  { startDate: '2025-11-15', endDate: '2025-11-20', isSchengen: true },
];
// Expected: 10 + 6 = 16 days

// Test: Non-Schengen trip should not count
const trips4 = [{ startDate: '2025-12-01', endDate: '2025-12-10', isSchengen: false }];
// Expected: 0 days

// Test: Future trip (after reference date)
// If reference date is 2025-12-26
const trips5 = [{ startDate: '2026-01-15', endDate: '2026-01-20', isSchengen: true }];
// Expected: 0 days (trip is in the future)
```

---

### Monetization Notes

This feature should be gated for premium subscribers. Suggested approach:

- **Free Tier:** Manual entry of up to 3 trips, basic day count
- **Premium Tier ($X/month):** Unlimited trips, planning tool, PDF reports, email alerts

---

### Reference: Schengen 90/180 Rule

For implementation accuracy:

1. The rule is **90 days within ANY 180-day period** (rolling window)
2. Both entry and exit days count as full days
3. Days in non-Schengen EU countries (Ireland, Cyprus pre-2025) don't count
4. Days in Schengen with a valid long-stay visa or residence permit don't count toward tourist days
5. The window "rolls" — every day, one day drops off and a new day is added

**Official Calculator Reference:** https://ec.europa.eu/home-affairs/content/visa-calculator_en

---

## Summary

This document provides everything needed to build the Schengen Tracker feature. Start by checking the existing codebase to answer the technical questions above, then follow the phased implementation approach.

The core algorithm (calculateSchengenDays) is the heart of the feature — get that right with tests, then build the UI around it.

For questions, Kevin can follow up in Claude.ai or continue in Claude Code.
