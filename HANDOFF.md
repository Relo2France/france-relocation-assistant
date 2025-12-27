# Session Handoff Document

**Date:** December 27, 2025
**Branch:** `claude/review-handoff-docs-QtcLj`
**Last Commit:** `Extract Schengen Tracker to standalone plugin (Phase 0)`

---

## 1. Current Project Status

**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active use:

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v3.6.4 | Active |
| Member Tools Plugin | v2.1.0 | Active |
| React Portal | v2.1.0 | Active |
| Theme | v1.2.4 | Active |
| **Schengen Tracker Plugin** | **v1.0.0** | **Complete** |

The React portal is fully functional with 40+ REST API endpoints. All major features are complete including profile management, task tracking, document generation, checklists, AI-powered guides, Schengen day tracking, and profile reset functionality.

### Architecture Decision: Schengen Tracker Extraction

**Decision:** Extract Schengen Tracker into standalone plugin (`relo2france-schengen-tracker`)

**Rationale:**
- Can be sold/marketed independently to digital nomads, frequent travelers
- Competes with Monaeo ($999/year) at a fraction of the price
- Cleaner architecture with dedicated codebase
- Separate release cycle from Member Tools

**Premium Model:** Entire feature is Premium (like Family Members)
- Free users see preview/upgrade prompt
- Premium members (MemberPress) get full access
- See: `SCHENGEN-MONAEO-PARITY-PLAN.md` for full details

---

## 2. What We Completed This Session

### 2.0 Schengen Tracker Plugin Extraction (Phase 0)

Extracted the Schengen Tracker into a standalone WordPress plugin following Option C (Hybrid) architecture:

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
- Portal URLs routed to `/portal/?view=schengen`

**GitHub Sync:** Added `relo2france-schengen-tracker` to plugins list

**Premium Gating Flow:**
1. User meta override (`r2f_schengen_enabled` = '1' or '0')
2. Filter hook (`r2f_schengen_premium_check`) - Member Tools hooks here
3. Global setting fallback (`r2f_schengen_global_enabled`)

### 2.1 Fixed All Accessibility Warnings (37 issues)

Resolved all jsx-a11y ESLint warnings in the React portal:

**Click events with keyboard handlers (8 files):**
- `TaskCard.tsx` - Added role, tabIndex, onKeyDown for clickable div
- `FileGrid.tsx` - Added keyboard support for file preview area
- `FileUpload.tsx` - Added keyboard support for drop zone
- `TaskList.tsx` - Added keyboard support for task list items
- `TaskDetail.tsx` - Added keyboard support for editable title/description
- `Modal.tsx` - Added role="presentation" to overlay divs
- `GenerateReportModal.tsx` - Added role="presentation" to overlay

**Label associations (6 files):**
- `ChecklistItem.tsx` - Added htmlFor/id to notes textarea
- `AIVerification.tsx` - Used aria-labelledby for button groups, htmlFor for file input
- `FilePreview.tsx` - Added htmlFor/id for description field
- `FamilyView.tsx` - Added htmlFor/id to all 7 form fields, used fieldset for checkboxes
- `TripForm.tsx` - Used aria-labelledby for radio group
- `TaskForm.tsx` - Used aria-labelledby for task type radio group

**AutoFocus warnings (6 files):**
- `NoteCard.tsx` - Replaced autoFocus with useRef/useEffect pattern
- `NoteForm.tsx` - Replaced autoFocus with useRef/useEffect pattern
- `TaskChecklist.tsx` - Replaced autoFocus with useRef/useEffect pattern
- `TaskDetail.tsx` - Replaced autoFocus with useRef/useEffect pattern
- `TaskForm.tsx` - Replaced autoFocus with useRef/useEffect pattern (both forms)

**Role/focus issues (2 files):**
- `DocumentTypeSelector.tsx` - Removed invalid role="listitem" from button
- `TaskBoard.tsx` - Added tabIndex={0} to listbox element

### 2.2 Added Optimistic Updates to Profile Hooks

Enhanced React Query mutations for instant UI feedback:

**`useUpdateMemberProfile`:**
- Added `onMutate` to immediately update cache before API completes
- Added `onError` to rollback to previous data on failure
- Maintains server data sync via `onSuccess`

**`useUpdateSettings`:**
- Same optimistic pattern for user settings

This improves perceived performance by showing changes immediately while the API call completes in the background.

---

## 3. Files Modified This Session

### New Plugin Files
| File | Purpose |
|------|---------|
| `relo2france-schengen-tracker/*` | Complete standalone Schengen Tracker plugin |
| `france-relocation-member-tools/includes/class-framt-schengen-bridge.php` | MemberPress bridge |
| `france-relocation-github-sync/france-relocation-github-sync.php` | Added new plugin to sync |

### TypeScript/React Files
| File | Changes |
|------|---------|
| `portal/src/hooks/useApi.ts` | Added optimistic updates to useUpdateMemberProfile and useUpdateSettings |
| `portal/src/components/dashboard/TaskCard.tsx` | Added keyboard handler, role, tabIndex |
| `portal/src/components/documents/FileGrid.tsx` | Added keyboard handler, role, tabIndex |
| `portal/src/components/documents/FileUpload.tsx` | Added keyboard handler, role, tabIndex |
| `portal/src/components/documents/AIVerification.tsx` | Fixed label associations |
| `portal/src/components/documents/FilePreview.tsx` | Fixed label associations |
| `portal/src/components/documents/DocumentTypeSelector.tsx` | Removed invalid role |
| `portal/src/components/checklists/ChecklistItem.tsx` | Fixed label association |
| `portal/src/components/family/FamilyView.tsx` | Fixed 7 label associations, used fieldset |
| `portal/src/components/messages/NoteCard.tsx` | Replaced autoFocus with ref pattern |
| `portal/src/components/messages/NoteForm.tsx` | Replaced autoFocus with ref pattern |
| `portal/src/components/research/GenerateReportModal.tsx` | Added role="presentation" |
| `portal/src/components/schengen/TripForm.tsx` | Fixed label association |
| `portal/src/components/shared/Modal.tsx` | Added role="presentation" to overlays |
| `portal/src/components/tasks/TaskBoard.tsx` | Added tabIndex to listbox |
| `portal/src/components/tasks/TaskChecklist.tsx` | Replaced autoFocus with ref pattern |
| `portal/src/components/tasks/TaskDetail.tsx` | Added keyboard handler, replaced autoFocus |
| `portal/src/components/tasks/TaskForm.tsx` | Fixed label, replaced autoFocus (2 forms) |
| `portal/src/components/tasks/TaskList.tsx` | Added keyboard handler, role, tabIndex |

---

## 4. Build & Test Status

```bash
cd france-relocation-member-tools/portal

# Build
npm install
npm run build  # Successful
```

**Current Status:**
- **Type Check:** 0 errors
- **Lint:** 0 jsx-a11y warnings (all 37 fixed)
- **Build:** Successful

---

## 5. Previous Session Summary (Earlier Today)

### Profile Reset Feature
- Added complete "Reset Visa Profile" functionality
- REST endpoint `POST /profile/reset` with confirmation
- Comprehensive data deletion across all tables

### Automatic Task Due Date Calculation
- Added `days_offset` to 50+ task templates
- Auto-recalculate when move date changes

### Move Date Certainty Setting
- New field with options: fixed, anticipated, flexible

### Schengen Tracker Improvements
- Added error display for API failures

### Critical Bug Fix
- Fixed table names using FRAMT_Portal_Schema::get_table()

---

## 6. Schengen Tracker Enhancement Plan

A comprehensive plan has been created to bring the Schengen Tracker to feature parity with Monaeo ($999/year product), plus unique features.

**Plan Document:** `SCHENGEN-MONAEO-PARITY-PLAN.md`

### Implementation Phases

| Phase | Description | Priority |
|-------|-------------|----------|
| **0** | Plugin Extraction & Premium Setup | **Complete** |
| **1** | Browser Geolocation + Smart Detection | P1 |
| **2** | Google/Outlook Calendar Sync | P1 |
| **3** | Multi-Jurisdiction (US States, etc.) | P1 |
| **4** | Professional PDF Reports | P1 |
| **5** | Push + In-App Notifications | P1 |
| **6** | CSV/ICS Import + PWA | P2 |
| **7** | AI Suggestions + Family + Analytics | P3 |

### What We Can't Replicate
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

## 7. Known Issues / Next Steps

### Lower Priority: Dual Profile Storage Migration
Profile data currently exists in both:
1. User meta (`fra_*` prefixed keys) - main source
2. Projects table (`visa_type`, `target_move_date`)

A future migration could consolidate this to ensure consistency.

### Schengen Trip Edit/Delete
User reported issues with editing trip dates and deleting trips. Error display was added to help debug. Check browser console for specific error messages.

### Potential Improvements
- Add loading spinner during reset operation
- Consider adding "undo" grace period for reset
- Profile reset could log an activity entry before clearing

---

## 8. Commit Summary (This Session)

1. `Fix all accessibility warnings (37 issues resolved)`
2. `Add optimistic updates to profile hooks`
3. `Extract Schengen Tracker to standalone plugin (Phase 0)`

---

*Generated: December 27, 2025*
