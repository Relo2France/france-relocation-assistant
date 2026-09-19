# Schengen day tracker: what exists today

**For:** the developer building the standalone Schengen app that Relo2France will embed.
**Written:** 19 September 2026, from a read of the code in this repo.
**Read with:** [SCHENGEN-EMBED-REQUIREMENTS.md](SCHENGEN-EMBED-REQUIREMENTS.md), the integration plan. This document covers only what is built today. It does not repeat that plan.

Paths are relative to the repo root. "Member Tools" means `france-relocation-member-tools/`.

---

## 1. Summary

Members open **Schengen days** in the portal (`/portal/?view=schengen`). They can log trips (dates, Schengen country, personal or business, notes) and see a 90/180 counter with a status colour, the current 180-day window and the next date a day drops off. Premium members also get a "what if" planning check, a printable HTML report and a calendar view. An email-alert toggle and a daily alert cron exist, but a settings-key mismatch stops the emails from ever being sent (see section 9). Many more tabs are on screen (Family, Analytics, Jurisdictions, Calendar Sync, Location, CSV, AI suggestions, notifications), but none of their server routes exists in the running code. They were served by the MyTravelStatus plugin, which is not running on the live site, so those tabs show errors or empty states. A "Coming soon" banner tells members the tracker is becoming its own app.

**Live status:** the core tracker (trips, counter, planning, report) works. No member has trips yet. The older separate plugin, "Relo2France Schengen Tracker", is deactivated and removed from GitHub Sync. The new app will replace all of this and starts fresh: nothing will be migrated.

## 2. Where it lives

| File | What it does |
|---|---|
| `france-relocation-member-tools/includes/class-framt-schengen-api.php` | The only live server code: 9 REST routes, the 90/180 maths, premium gating, the planning simulation and the HTML report |
| `.../includes/class-framt-schengen-alerts.php` | Daily cron and alert emails |
| `.../includes/class-framt-schengen-bridge.php` | Hooks for the retired separate plugin. Dead code (section 9) |
| `.../includes/class-framt-portal-schema.php` (lines 54, 257–274) | Creates the `wp_fra_schengen_trips` table |
| `.../includes/class-framt-portal-api.php:7114–7121` | Account deletion removes trips and settings |
| `.../includes/class-framt-magic-link.php:121–128` | Redirects the old `/my-travel-status/` page to `/portal/?view=schengen` |
| `.../includes/class-framt-portal-settings.php:96,115,134,141,678` | Menu switch, label "Schengen days", Globe icon, menu order |
| `france-relocation-member-tools.php:340–446` | Loads and starts the three classes |
| `portal/src/App.tsx:91` and `components/layout/Sidebar.tsx:61` | Route `schengen` → `TravelStatusDashboard` |
| `portal/src/components/travel-status/TravelStatusDashboard.tsx` | The page: header, cards, banners, 9 tabs, modals |
| `.../travel-status/travelStatusUtils.ts` (+ `.test.ts`) | Client-side 90/180 calculator, which the dashboard uses for its numbers |
| `.../travel-status/useTravelStatusStore.ts` | Wraps the React Query hooks for trips and settings |
| `.../travel-status/*.tsx` (others) | Tab components, listed in section 3 |
| `portal/src/api/client.ts:827–1308` | `travelStatusApi`, about 70 client calls under `/schengen/...` |
| `portal/src/hooks/useApi.ts:804–1452` | React Query hooks for those calls |
| `portal/src/types/index.ts:804–1800` | TS types, including `SCHENGEN_COUNTRIES` |
| `mytravelstatus/` | The MyTravelStatus plugin and native apps, as prior art (section 11) |

Note: the portal's REST namespace is `fra-portal/v1` (`france-relocation-member-tools.php:3914`), not the `framt/v1` given in `CLAUDE.md`.

## 3. Features, screen by screen

Status key: **Built** = works end to end today. **Stub** = the UI renders, but the server route is missing in the running code, so it errors or shows an empty state. **Hidden** = in the code but switched off.

### Header and top of page

| Element | Status | Notes |
|---|---|---|
| "Coming soon" banner | Built | "The Schengen tracker is becoming its own app… Until then this version keeps counting; nothing you log is lost." (`TravelStatusDashboard.tsx:158–163`) |
| Add Trip button → modal `TripForm` | Built | Disabled when `featureStatus.canAddTrip` is false |
| Export Report (`ReportExport`) | Built, premium | Shown only when `isPremium` |
| Help (?) → reopens onboarding | Built | 5-step modal. Completion stored in `localStorage` key `r2f_schengen_onboarding_complete`. Step 3 promotes location check-in that auto-creates trips, which does not work today |
| Bell (`NotificationCenter`) | Stub | Polls `/schengen/notifications` and `/unread-count` every 60 s (`useApi.ts:1215–1231`) |
| Free-plan banner | Built | "Free Plan: N of 3 trips used" with an Upgrade link, shown to non-premium users |
| Day counter (ring), status badge, Days Remaining, Current 180-Day Window, Next Day Expires | Built | All computed in the browser from the trip list, not from `/schengen/summary` |
| "The 90/180 Rule" info box with resident note | Built | Says: "Living in France? Once you hold a French long-stay visa or residence permit, days in France don't count toward the 90/180 limit. Use this counter for time in other Schengen countries… and for visits before your visa." (`:319–322`). **Advice only.** The counter does not apply the rule |
| Status banners (warning / danger / critical) | Built | Text for 60+, 80+ and 90+ days |
| Daily check-in prompts (`LocationDetectionBanner`, compact `LocationTracker`) | Hidden | `const CHECK_IN_PROMPTS = false` (`:61`, used at `:334`) |

### Tabs (`ViewTab`, `:63`)

| Tab | Status | Premium | What the member sees |
|---|---|---|---|
| Trip List (`TripList`) | Built | No | Table sortable by date, country or days, with notes and a category chip. Trips outside the window are greyed and marked "outside window". Edit opens `TripForm`. Delete needs a second click within 3 s. Empty state: "No trips recorded / Add your first trip to start tracking your Schengen days." |
| Family (`FamilyManager`) | Stub | Yes (lock icon; upgrade prompt otherwise) | Add, edit or delete people (name, relationship, nationality, passport country, DOB, colour). Shows "Unable to load family members" today. No trip can be assigned to a person: `TripForm` has no person field |
| Analytics (`AnalyticsDashboard`) | Stub | Yes | Charts by country, month and year; compliance history. Today: "Failed to load analytics data" |
| Jurisdictions (`JurisdictionOverview` + `UKSRTStatus`, `UKTiesQuestionnaire`, `MultiFactorIndicators`) | Stub | No | Track other rules (UK SRT, US states, EU tax 183-day). Today: "No Jurisdictions Tracked", and adding one fails silently (console only) |
| Calendar View (`CalendarView`) | Built | Yes | Month grid with trip days coloured and window days dotted. Client-only |
| Calendar Sync (`CalendarSync`) | Stub | No | Google or Outlook OAuth, detected events to import or skip, `.ics` upload. Today: "Unable to load calendar sync" |
| Planning Tool (`AISuggestions` + `PlanningTool`) | Planning built; suggestions stub | Yes | Enter dates, then Check Trip: safe or violating, max days used, days over, earliest safe start date, max safe length. The suggestions panel shows "Unable to load suggestions" |
| Location (`LocationTracker`) | Stub | No | Browser geolocation check-in plus history. The geolocation prompt works; saving fails with "Failed to save location." |
| Settings | Partly built | CSV is premium | Email Alerts toggle (saves; see bug 9.1), "Send Test Alert", thresholds shown read-only (no editor, although the API accepts them), and CSV Import/Export (stub) |

### Trip form (`TripForm.tsx`)

- Fields: start date, end date, country (the 29 countries in section 4), category (`personal` / `business`), notes. A jurisdiction selector appears only if the member tracks more than one jurisdiction, which cannot happen today.
- Client checks: both dates present, end ≥ start, length ≤ 90 days (`:143–149`).
- Live warning while typing (`:88–124`): runs `wouldTripViolate` over the other trips. It shows the violation message, or "approaching the limit" at ≥ 80 days, or a plain notice at ≥ 60 (hard-coded, `:103–106`).
- It sends `jurisdictionCode`, which the server ignores.

## 4. The rules engine

The same algorithm exists **three times**: PHP in the API (`class-framt-schengen-api.php:661–744`), a copy in the alerts class (`class-framt-schengen-alerts.php:198–253`), and TypeScript (`travelStatusUtils.ts`). The dashboard displays the TS result. The PHP one drives the report, simulation and emails. The unused `/schengen/summary` route returns the same thing.

**Counting**

- Constants: `SCHENGEN_MAX_DAYS = 90` and `SCHENGEN_WINDOW_DAYS = 180` (`travelStatusUtils.ts:10–11`). PHP hard-codes 90 and `-179 days`.
- Window: `[today − 179 days, today]`, 180 calendar days including today, in UTC (`api.php:517–518`; `utils.ts:62`).
- Each trip is clamped to the window, and every date from start to end **inclusive** is added to a set. Entry and exit days both count as full days. Overlapping trips are not double-counted (`api.php:661–682`; `utils.ts:55–89`).
- Future days do not count toward "today": the trip end is clamped to today, and the TS version skips trips that start after today (`utils.ts:71`).
- `daysRemaining = max(0, 90 − daysUsed)`.
- Next expiration = earliest counted date + 180 days (`api.php:692–723`; `utils.ts:141–157`). This is when the oldest day leaves the window. It is not when a full 90 days becomes available again.
- A single trip over 90 days is rejected (`api.php:773–781`: "A single trip cannot exceed 90 days.").

**Countries that count** (`api.php:38–68`, mirrored in `types/index.ts:808–814`). There are 29, and the server enforces this list as an `enum`:
Austria, Belgium, Bulgaria, Croatia, Czech Republic, Denmark, Estonia, Finland, France, Germany, Greece, Hungary, Iceland, Italy, Latvia, Liechtenstein, Lithuania, Luxembourg, Malta, Netherlands, Norway, Poland, Portugal, Romania, Slovakia, Slovenia, Spain, Sweden, Switzerland.
Every logged trip counts; there is no concept of a non-Schengen trip. Monaco, San Marino, Vatican City and Andorra cannot be logged. Cyprus and Ireland are correctly absent.

**Status** (`api.php:733–744`; `utils.ts:128–136`)

| Status | Condition | Default |
|---|---|---|
| `critical` | `daysUsed >= 90` | fixed |
| `danger` | `daysUsed >= red_threshold` | 80 |
| `warning` | `daysUsed >= yellow_threshold` | 60 |
| `safe` | otherwise | |

Defaults are at `api.php:637–642`. `PUT /schengen/settings` clamps yellow to 1–89 and red to 1–90, and requires yellow < red (`:583–598`). No UI edits them.

**Planning / "what if"** (`POST /schengen/simulate`, `api.php:968–1178`)

- For each day D of the proposed trip, it counts days in `[D − 179, D]` across all existing trips (including already-booked future ones) plus the proposed trip up to D. Any D with a count > 90 is a violation.
- Returns `wouldViolate`, `violations[]`, `maxDaysUsed`, `proposedLength`, `daysOverLimit = maxDaysUsed − 90`.
- `earliestSafeDate`: searches day by day from **today** for up to 365 days for a start date where a trip of the same length never exceeds 90. Returns `null` if none is found.
- `maxSafeLength`: the longest trip (1–90 days) from the proposed start date that never exceeds 90.
- The client has an equivalent (`wouldTripViolate`, `findEarliestEntryDate`, `findMaxTripLength`, `utils.ts:207–318`). Only `wouldTripViolate` is used, for the form warning.

**Not supported in the live code**

- Multi-jurisdiction: the UI exists, but there is no server support (MyTravelStatus had it).
- Family / per-person counting: none. Trips belong to the WordPress user only.
- **French long-stay / residence rule: not applied anywhere.** A resident's days in France count like any others. The only mitigation is the advisory note on the page. Nationality-specific bilateral agreements and visa-exempt vs visa-required travellers are also not modelled.

## 5. Data model

### Table `wp_fra_schengen_trips` (`class-framt-portal-schema.php:257–274`)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, PK, auto-increment | returned as a string |
| `user_id` | bigint unsigned, indexed | WordPress user; no household link |
| `start_date` | date, indexed | inclusive |
| `end_date` | date, indexed | inclusive |
| `country` | varchar(100) | one of the 29 names |
| `category` | varchar(20), default `personal` | `personal` / `business` |
| `notes` | text, nullable | |
| `created_at`, `updated_at` | datetime | auto-set |

There is no jurisdiction, family-member, location or source column.

### User meta

| Key | Written by | Content |
|---|---|---|
| `fra_schengen_settings` | API (`api.php:610`) | `yellow_threshold`, `red_threshold`, `email_alerts`, `upcoming_trip_reminders` (stored but never used) |
| `framt_schengen_settings` | read by alerts (`alerts.php:183`), deleted on account deletion | never written by the live code (bug 9.1) |
| `framt_schengen_last_alert_level`, `framt_schengen_last_alert_time` | alerts | dedupe |
| `framt_schengen_premium_enabled` | admin, by hand | `'1'` / `'0'` per-user premium override |

Options: `framt_schengen_premium_memberships` (comma-separated MemberPress IDs), `framt_schengen_premium_enabled` (global fallback, default `'0'`), `framt_schengen_upgrade_url`.

### TypeScript types (`portal/src/types/index.ts`)

- `TravelStatusTrip` (`:818`): `id, startDate, endDate, country, jurisdictionCode?, category, notes?, familyMemberId?, createdAt, updatedAt`. The last two optional fields are never sent by the server.
- `TravelStatusSummary` (`:831`), `TravelStatusStatus` (`safe|warning|danger|critical`), `TravelStatusAlertSettings` (`:846`), `TravelStatusFeatureStatus` (`:861`), `TravelStatusSimulationResult` (`:872`), `TravelStatusReportResponse` (`:882`), `TravelStatusTestAlertResult` (`:893`).
- Types for the stub features: location (`:916–980`), family (`:1185–1249`), calendar (`:1086–1140`), CSV, suggestions, jurisdictions and UK SRT (`:1251–1640`), notifications and push, analytics, PDF reports (`:1641–1800`).

## 6. API (live routes)

All are registered on `fra-portal/v1`, with WordPress cookie auth plus a nonce. "Logged in" = `check_permission` (401 otherwise). "Owner" = logged in and `trip.user_id` is the current user (404 or 403 otherwise). "Premium" = logged in and `is_schengen_premium_enabled` (403 `premium_required` otherwise).

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| GET | `/schengen/trips` | Logged in | – | `Trip[]`, newest start first |
| POST | `/schengen/trips` | Logged in | `start_date`, `end_date` (YYYY-MM-DD, required), `country` (enum, required), `category`, `notes` | `Trip` |
| GET | `/schengen/trips/{id}` | Owner | – | `Trip` |
| PUT | `/schengen/trips/{id}` | Owner | same fields as POST; the schema marks dates and country required | `Trip` |
| DELETE | `/schengen/trips/{id}` | Owner | – | `{deleted:true, id}` |
| GET | `/schengen/summary` | Logged in | – | `{daysUsed, daysRemaining, windowStart, windowEnd, status, nextExpiration, statusThresholds:{yellow,red}}` |
| GET / PUT | `/schengen/settings` | Logged in | `yellowThreshold`, `redThreshold`, `emailAlerts`, `upcomingTripReminders` | same four fields |
| GET | `/schengen/feature-status` | Logged in | – | `{isPremium, tripLimit (3 or null), tripCount, canAddTrip, canUsePlanning, canExportPdf, upgradeUrl, upgradeMessage}` |
| GET | `/schengen/report` | Premium | – | `{html, filename, summary:{daysUsed, daysRemaining, status, tripCount}}` |
| POST | `/schengen/simulate` | Premium | `start_date`, `end_date` | see section 4 |
| POST | `/schengen/test-alert` | Logged in | – | `{success, message, alert_level?, days_used?, thresholds?, summary?}` |

`Trip` = `{id, startDate, endDate, country, category, notes, createdAt, updatedAt}`.

Errors: `invalid_date`, `invalid_date_range`, `trip_too_long`, `no_data`, `invalid_thresholds`, `missing_dates`, `db_error`.

**Premium logic** (`api.php:848–893`), in order: (1) per-user meta override `'1'`/`'0'`; (2) if MemberPress is installed, any active membership counts as premium, unless `framt_schengen_premium_memberships` lists specific IDs; with MemberPress present and no qualifying membership the answer is false; (3) otherwise the global option. In practice every active Relo2France member is premium. Free tier = 3 trips (`FREE_TRIP_LIMIT`, `:812`), no planning, no report; Calendar View, Family, Analytics and CSV are hidden client-side. The upgrade URL is the custom option, else the MemberPress account page, else `/membership/`.

**Client calls with no server route today:** `/schengen/trips/import|export`, `/schengen/reports/*`, `/schengen/location/*`, `/schengen/calendar/*`, `/schengen/suggestions`, `/schengen/family*`, `/schengen/jurisdictions*`, `/schengen/user-jurisdictions`, `/schengen/compliance/*`, `/schengen/notifications*`, `/schengen/push/*` and `/schengen/analytics` (`client.ts:896–1308`). Some were registered on `fra-portal/v1` by MyTravelStatus (location in `class-mts-location.php:82`, jurisdictions in `class-mts-jurisdiction.php:272`). The rest assumed `mts/v1` equivalents that were never mapped.

## 7. Notifications and background jobs

- **Cron** `framt_schengen_daily_alerts`, `daily`, first run "tomorrow 08:00 UTC" (`alerts.php:36, 83–89`). It is re-scheduled on any page load if missing. It is never unscheduled, because the `framt_activate`/`framt_deactivate` actions it listens for are never fired anywhere.
- **Run:** for each distinct `user_id` in the trips table, skip unless `email_alerts` is on. Compute days used, then pick a level from **fixed** thresholds `warning 60`, `danger 80`, `urgent 85` (`alerts.php:41–45`), independent of the member's yellow/red settings. The same level is not re-sent within 7 days (`:156–162`); a higher level is sent at once.
- **Email:** HTML via `wp_mail` to the account email.
  - Subjects: "Schengen Tracker: N days remaining", "Warning: N Schengen days remaining", "URGENT: Only N Schengen days remaining!"
  - Body: heading, message, days used and days remaining, window dates, a "View Your Schengen Tracker" button to `/portal/?view=schengen`, and a footer link to `/portal/?view=settings` (`:324–435`).
- **Test alert:** `POST /schengen/test-alert` runs the same checks and sends immediately.
- **No** in-app notifications, push, trip reminders or digests exist in the live code.

## 8. Integrations: what each actually does

| Integration | Reality today |
|---|---|
| PDF report | **Not a PDF.** The server returns an HTML page (`api.php:1190–1362`, comment at `:1231`). The modal previews it in an iframe, offers Print (browser print-to-PDF) and downloads a `.html` file. The report contains name, generated time, days used and remaining, status, window, and a full trip table. It carries old branding (blue `#4A7BA7`, Arial) |
| Planning | Built, server-side (section 4) |
| Calendar view | Built, client-side only |
| Calendar sync (Google/Outlook/iCal) | Stub. The OAuth code lives in `mytravelstatus/includes/class-mts-calendar.php` |
| CSV import/export | Stub. The client calls routes that don't exist. `POST /schengen/trips/import` and `GET /schengen/trips/export` don't match the numeric `{id}` pattern, so they get `rest_no_route` |
| "AI" suggestions | Stub. The MyTravelStatus version (`class-mts-api.php:1354`) is **rule-based text, not an LLM** |
| Location / GPS | Browser geolocation (`hooks/useGeolocation.ts`) works; saving fails. MyTravelStatus did reverse geocoding with OpenStreetMap Nominatim and IP lookup with ip-api.com |
| Activity log | The bridge would log trip and alert events, but only on `r2f_schengen_*` actions fired by the retired plugin. Nothing is logged today |

## 9. Known gaps, bugs and rough edges

1. **Email alerts can never fire.** The API saves settings to `fra_schengen_settings` (`class-framt-schengen-api.php:610, 644`). The alerts class reads `framt_schengen_settings` (`class-framt-schengen-alerts.php:183`), so `email_alerts` is always false there. "Send Test Alert" reports "Email alerts are disabled" to anyone at 60+ days.
2. **Account deletion leaves settings behind:** it deletes `framt_schengen_settings`, not `fra_schengen_settings` (`class-framt-portal-api.php:7121`).
3. **Free-tier limit is UI-only.** `create_trip` never checks `FREE_TRIP_LIMIT` (`api.php:362–397`). The dashboard also treats a user as premium while feature status is loading or has failed (`TravelStatusDashboard.tsx:138–139`).
4. **Dates display one day early for US members.** `formatDate`/`formatDateRange` use `new Date('YYYY-MM-DD')`, which is UTC midnight, then format in local time (`travelStatusUtils.ts:330–365`). In US time zones, 10 March shows as 9 March.
5. **Calendar grid shifts a day for members in France.** A local-midnight date is keyed with `toISOString()` (`CalendarView.tsx:92–93`, also `:78–79` and `:107–108`). In UTC+1/+2 the key is the previous day.
6. **Notes can't be cleared on edit.** `TripForm` sends `notes: undefined` when empty (`TripForm.tsx:157`), and `client.ts:859` then drops the key, so the old note stays.
7. **French-residence rule not applied** (section 4). Residents who log time in France see false warnings.
8. **Three copies of the counting logic** (API, alerts, TS). The dashboard shows the TS copy; emails and reports use PHP. `/schengen/summary` is never called by the UI.
9. **Alert thresholds ignore member settings** (fixed 60/80/85). The Settings tab labels the 80-day card "Status turns red", but it is orange/"danger".
10. **Stub tabs** (Family, Analytics, Jurisdictions, Calendar Sync, Location, CSV, suggestions, bell) call missing routes. The bell polls them every minute (`useApi.ts:1221, 1230`). The onboarding promises location auto-trips that don't exist.
11. **Bridge is dead code.** It filters `framt_portal_data` (never applied) and `r2f_schengen_*` (consumed only by the retired plugin). It references `R2F_Schengen_Premium`, which is guarded by `is_schengen_plugin_active()` (`class-framt-schengen-bridge.php:59–82, 160–193`).
12. **Cron never unscheduled** (section 7).
13. **Report is HTML labelled `.pdf`** (`api.php:1235`), with old branding.
14. **No overlap warning** between trips. Duplicates are silently de-duplicated in the count but stay in the list.
15. **Simulation cost:** nested loops (up to 365 × trip length × 180 days per trip) run in PHP on every check. This is fine at today's volumes; unsure how it behaves with many trips.
16. `upcomingTripReminders` is stored but never used.

## 10. Recommendation for the new app

**Keep**

- The counting model: inclusive entry and exit days, a set of distinct dates, a rolling 180-day window ending on the reference date, and future-booked trips counted when checking future dates.
- The "what if" check with *earliest safe start* and *max safe length*. It is the most useful feature.
- Next-expiry date, a status with words as well as colour, and the country list (reviewed: consider adding the microstates as "counts as" entries).
- Threshold alerts by email with de-duplication.
- A printable report (make it a real PDF).
- The test cases in `travelStatusUtils.test.ts`, as a starting spec.

**Add, per Relo2France's domain rule:** once a person holds a French long-stay visa or residence permit, from `residence.french_long_stay_from` in the token, **days in France stop counting toward 90/180; days in every other Schengen country still count.** Nothing in this repo implements that. Count per person in the household, with trips assignable to several people.

**Drop or defer:** multi-jurisdiction and tax tests (UK SRT, US states, EU 183-day), calendar OAuth, GPS and IP detection, push, the analytics dashboard, "AI" suggestions, CSV, and the free/premium split inside the embed. The embed plan forbids upsells. Keep one algorithm, server-side, with the client only displaying it.

## 11. Prior art: MyTravelStatus (`mytravelstatus/`)

The successor plugin to the retired "Relo2France Schengen Tracker", version 1.8.4, about 18,000 lines of PHP plus native apps. **It is not running on the live site: the `mts/v1` namespace is absent.** It is useful as a reference, not as a base.

- **Features:** trips with jurisdiction and family member; Schengen plus 22 other rules (`uk_srt`, `us_spt`, `us_vwp`, `uk_visitor`, `ca_visitor`, US states CA/FL/NY/TX, and 183-day tax rules for FR, DE, ES, IT, PT, NL, IE, AU, CA, JP, MX, NZ, SG); the UK Statutory Residence Test with ties; multi-factor EU residency; family members with a per-person summary; browser location log with Nominatim and IP detection; Google/Outlook OAuth calendar sync (cron `mts_calendar_sync`, twice daily) and iCal import; real PDF reports via TCPDF with QR verification; in-app notifications and web push (VAPID); rule-based suggestions; analytics; CSV; a mobile API (device registration, `/sync`, batch locations, passport-control mode); daily alerts (`mts_daily_alerts`, same 60/80/85 thresholds).
- **Native apps:** iOS (Swift, with a widget) and Android (Kotlin, with calendar and photo-GPS importers and a widget) are in `mytravelstatus/mobile/`. I found no evidence they shipped.
- **Premium:** user meta `mts_enabled`, then a filter for Member Tools, then the global option `mts_global_enabled`.
- **Data model (`class-mts-schema.php`), 12 tables with prefix `mts_`:**
  - `trips`: the Member Tools columns plus `jurisdiction_code`, `family_member_id` and `location_*`
  - `location_log`, `calendar_connections`, `calendar_events`
  - `jurisdiction_rules`: `days_allowed`, `window_days`, `counting_method` (`rolling`, `calendar_year`, `fiscal_year`, `multi_year`, `weighted_multi_year`, `uk_srt`), `rule_config`
  - `user_jurisdictions`, `compliance_snapshots`, `uk_ties`, `push_subscriptions`, `notifications`, `family_members`, `devices`
- It also does **not** implement the French-residence exemption.

**Planned but never built** (from `SCHENGEN-TRACKER-HANDOFF.md`, `SCHENGEN-MONAEO-PARITY-PLAN.md`, `SCHENGEN-APP-HANDOFF.md` and `MYTRAVELSTATUS-INFRASTRUCTURE.md`):

- background GPS (3 reads a day) and offline sync as a shipped product
- flight import (TripIt, Flighty)
- "proof of presence" bundles for préfecture applications
- tax-adviser sharing and community features
- linking trips to passports and visas with expiry checks
- a PWA
- an integrations hub
- a standalone mytravelstatus.com site on Cloudways with SendGrid and OneSignal

These docs describe WordPress-shared designs that `SCHENGEN-EMBED-REQUIREMENTS.md` now supersedes.
