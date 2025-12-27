# Session Handoff Document

**Date:** December 27, 2025
**Branch:** `claude/add-github-sync-self-update-QtcLj`
**Last Commit:** `Fix portal to read ?view= parameter from URL on initial load`

---

## 1. Current Project Status

**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active use:

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v3.6.4 | Active |
| Member Tools Plugin | v2.1.0 | Active |
| React Portal | v2.1.0 | Active |
| Theme | v1.2.4 | Active |
| **Schengen Tracker Plugin** | **v1.0.0** | **Installed & Active** |

The React portal is fully functional with 40+ REST API endpoints. All major features are complete including profile management, task tracking, document generation, checklists, AI-powered guides, Schengen day tracking, and profile reset functionality.

### Schengen Tracker: Phase 0 Complete

The Schengen Tracker has been extracted into a standalone plugin (`relo2france-schengen-tracker`) and is now installed and active on the production site.

**Architecture:** Option C (Hybrid - Standalone Plugin + Portal Integration)
- Works independently via `[schengen_tracker]` shortcode
- Integrates with Member Tools Portal at `/portal/?view=schengen`
- Premium gating via MemberPress (like Family Members feature)

**Plan Document:** `SCHENGEN-MONAEO-PARITY-PLAN.md`

---

## 2. What We Completed This Session

### 2.0 Schengen Tracker Plugin Extraction (Phase 0)

Extracted the Schengen Tracker into a standalone WordPress plugin:

**New Plugin:** `relo2france-schengen-tracker/`

| File | Purpose |
|------|---------|
| `relo2france-schengen-tracker.php` | Main plugin file with autoloader, activation hooks |
| `includes/class-r2f-schengen-core.php` | Core singleton, admin menu, shortcode |
| `includes/class-r2f-schengen-schema.php` | Database schema (reuses `fra_schengen_trips` table) |
| `includes/class-r2f-schengen-premium.php` | Premium gating (like Family Members pattern) |
| `includes/class-r2f-schengen-api.php` | Full REST API (`r2f-schengen/v1/` namespace) |
| `includes/class-r2f-schengen-alerts.php` | Daily cron email alerts at 8am UTC |
| `templates/dashboard.php` | Standalone frontend template |
| `assets/css/schengen-frontend.css` | Modern dashboard styling |
| `assets/js/schengen-frontend.js` | jQuery-based trip management |

**Member Tools Integration:**
- Added `includes/class-framt-schengen-bridge.php` - Bridges MemberPress membership checks
- Bridge provides `r2f_schengen_premium_check` filter for premium access

**Premium Gating Flow:**
1. User meta override (`r2f_schengen_enabled` = '1' or '0')
2. Filter hook (`r2f_schengen_premium_check`) - Member Tools hooks here
3. Global setting fallback (`r2f_schengen_global_enabled`)

### 2.1 GitHub Sync Self-Update Fix

Added GitHub Sync to its own managed plugins list so it can update itself:

```php
'france-relocation-github-sync' => array(
    'file' => 'france-relocation-github-sync/france-relocation-github-sync.php',
    'name' => 'France Relocation GitHub Sync',
),
```

**Why this matters:** Previously, when new plugins were added to GitHub Sync, the user couldn't see them until manually editing the plugin file. Now GitHub Sync can update itself to pick up new plugin entries.

### 2.2 Portal URL Routing Fix

Fixed the portal to read the `?view=` parameter from the URL on initial page load.

**Problem:** Direct links like `https://relo2france.com/portal/?view=schengen` would always show the dashboard instead of the requested view.

**Solution:** Updated `portal/src/store/index.ts` to read the initial view from `URLSearchParams`:

```typescript
const getInitialView = (): string => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const view = params.get('view');
    if (view) {
      return view;
    }
  }
  return 'dashboard';
};
```

Now direct links to any portal view work correctly.

---

## 3. Files Modified This Session

### New Plugin Files
| File | Purpose |
|------|---------|
| `relo2france-schengen-tracker/*` | Complete standalone Schengen Tracker plugin (9 files) |
| `france-relocation-member-tools/includes/class-framt-schengen-bridge.php` | MemberPress bridge |

### Modified Files
| File | Changes |
|------|---------|
| `france-relocation-github-sync/france-relocation-github-sync.php` | Added GitHub Sync and Schengen Tracker to managed plugins |
| `france-relocation-member-tools/portal/src/store/index.ts` | Read `?view=` parameter on initial load |
| `france-relocation-member-tools/france-relocation-member-tools.php` | Load Schengen Bridge class |

---

## 4. Build & Test Status

```bash
cd france-relocation-member-tools/portal
npm install
npm run build  # Successful
```

**Current Status:**
- **Type Check:** 0 errors
- **Build:** Successful
- **Schengen Tracker:** Installed and active on production
- **Portal URL routing:** Working (direct links now work)

---

## 5. Schengen Tracker Enhancement Plan - Next Phases

**Plan Document:** `SCHENGEN-MONAEO-PARITY-PLAN.md`

| Phase | Description | Status |
|-------|-------------|--------|
| **0** | Plugin Extraction & Premium Setup | **Complete** |
| **1** | Browser Geolocation + Smart Detection | Next |
| **2** | Google/Outlook Calendar Sync | Pending |
| **3** | Multi-Jurisdiction (US States, etc.) | Pending |
| **4** | Professional PDF Reports | Pending |
| **5** | Push + In-App Notifications | Pending |
| **6** | CSV/ICS Import + PWA | Pending |
| **7** | AI Suggestions + Family + Analytics | Pending |

### Phase 1 Overview: Browser Geolocation + Smart Detection
- Browser Geolocation API for automatic location detection
- "Check In" button to record current location
- Smart country detection from coordinates
- Location history tracking

### What We Can't Replicate from Monaeo
- True background GPS (requires native mobile app)
- Credit card import (requires Plaid + PCI compliance)
- "Audit-certified" claims (requires legal partnership)

### Unique "Plus" Features (Beyond Monaeo)
- "What If" planning tool with suggestions
- AI-powered trip recommendations
- Family/group tracking
- Integration with France relocation workflow
- Analytics dashboard

---

## 6. Known Issues / Notes

### GitHub Sync Managed Plugins
The current managed plugins list is:
1. France Relocation Assistant
2. France Relocation Member Tools
3. France Relocation GitHub Sync (self-updates)
4. Relo2France Schengen Tracker

### Portal Direct Links
All portal views now support direct linking:
- `/portal/?view=dashboard`
- `/portal/?view=schengen`
- `/portal/?view=profile`
- etc.

### Lower Priority: Dual Profile Storage Migration
Profile data currently exists in both user meta and projects table. A future migration could consolidate this.

---

## 7. Commit Summary (This Session)

1. `Extract Schengen Tracker to standalone plugin (Phase 0)`
2. `Merge main and resolve HANDOFF.md conflict`
3. `Add GitHub Sync to its own managed plugins list for self-updates`
4. `Fix portal to read ?view= parameter from URL on initial load`

---

## 8. To Resume Next Session

1. **Merge pending PR:** Branch `claude/add-github-sync-self-update-QtcLj` has the URL routing fix
2. **Continue with Phase 1:** Browser Geolocation + Smart Detection
3. **Reference:** `SCHENGEN-MONAEO-PARITY-PLAN.md` for detailed implementation specs

---

*Generated: December 27, 2025*
