# Session Handoff Document

**Date:** December 27, 2025
**Branch:** `claude/resume-from-handoff-JlK7B`
**Last Commit:** `Fix profile reset table names - use schema helper`

---

## 1. Current Project Status

**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active use:

| Component | Version | Status |
|-----------|---------|--------|
| Main Plugin | v3.6.4 | Active |
| Member Tools Plugin | v2.1.0 | Active |
| React Portal | v2.1.0 | Active |
| Theme | v1.2.4 | Active |

The React portal is fully functional with 40+ REST API endpoints. All major features are complete including profile management, task tracking, document generation, checklists, AI-powered guides, Schengen day tracking, and **profile reset functionality**.

---

## 2. What We Completed This Session

### 2.1 Profile Reset Feature (NEW)

Added complete "Reset Visa Profile" functionality in Settings → Portal Account → Danger Zone:

**Backend (`class-framt-portal-api.php`):**
- Added REST endpoint `POST /profile/reset`
- Requires confirmation phrase "RESET MY PROFILE"
- Deletes all user portal data comprehensively

**Frontend (`SettingsView.tsx`):**
- Added amber warning section in Danger Zone
- Confirmation input with exact phrase matching
- Success message with auto-reload after 2 seconds

**Data deleted on reset:**
| Data Type | Table/Storage |
|-----------|---------------|
| Projects | `fra_projects` |
| Tasks | `fra_tasks` |
| Notes | `fra_notes` |
| Files | `fra_files` + physical files |
| Activity log | `fra_activity` |
| Schengen trips | `fra_schengen_trips` |
| Schengen settings | `framt_schengen_settings` user meta |
| Messages | `framt_messages`, `framt_message_replies` |
| Generated documents | `framt_generated_documents` |
| Research report links | `framt_research_report_links` |
| WordPress documents | `fra_document` post type |
| User uploads folder | `uploads/fra-portal/{user_id}/` |
| Profile meta | All `fra_*` user meta keys |

### 2.2 Automatic Task Due Date Calculation

Tasks now automatically get due dates based on the user's move date:

- Added `days_offset` to all 50+ task templates (e.g., -120 = 4 months before move)
- Created `calculate_due_date()` function for smart date calculation
- Created `recalculate_task_due_dates()` to update existing tasks when move date changes
- Hooked into `update_member_profile()` and `update_project()` to trigger recalculation
- Smart handling for past dates (sets to 7 days from now if calculated date is in past)

### 2.3 Move Date Certainty Setting

Added setting for users to indicate how firm their move date is:

- New field `move_date_certainty` with options: `fixed`, `anticipated`, `flexible`
- Added to PHP profile API (`fra_move_date_certainty` user meta)
- Added TypeScript types (`MoveDateCertainty`, `TimelineType`)
- Added UI dropdown in Settings → Visa Profile → Timeline section

### 2.4 Schengen Tracker Improvements

- Added `actionError` state to display API errors to users
- Error banner shows when trip add/update/delete fails (previously silent)
- Added dismiss button for error messages
- Fixed ARIA role issue in `DayCounter.tsx` (removed invalid `role="text"`)

### 2.5 Bug Fix: Profile Reset Table Names

**Critical fix:** The reset function was using wrong table names:
- `framt_projects` → should be `fra_projects`
- `framt_tasks` → should be `fra_tasks`
- `framt_notes` → should be `fra_notes`
- `framt_files` → should be `fra_files`

Now uses `FRAMT_Portal_Schema::get_table()` for correct names.

---

## 3. Files Modified This Session

### PHP Files
| File | Changes |
|------|---------|
| `class-framt-portal-api.php` | Added reset_profile(), calculate_due_date(), recalculate_task_due_dates(), days_offset to task templates, move_date_certainty field |
| `class-framt-portal-api.php` | Fixed table names using FRAMT_Portal_Schema::get_table() |

### TypeScript/React Files
| File | Changes |
|------|---------|
| `portal/src/types/index.ts` | Added MoveDateCertainty, TimelineType types; added move_date_certainty to MemberProfile |
| `portal/src/api/client.ts` | Added profileApi.reset() function |
| `portal/src/hooks/useApi.ts` | Added useResetProfile() hook |
| `portal/src/components/settings/SettingsView.tsx` | Added Reset Profile UI, move_date_certainty dropdown |
| `portal/src/components/schengen/SchengenDashboard.tsx` | Added actionError state and error display banner |
| `portal/src/components/schengen/DayCounter.tsx` | Removed invalid ARIA role="text" |

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
- **Lint:** 1 error fixed (ARIA role), warnings for accessibility and import ordering
- **Build:** Successful

---

## 5. API Endpoints Added

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/profile/reset` | POST | Reset all user portal data (requires confirmation) |

---

## 6. Known Issues / Next Steps

### Schengen Trip Edit/Delete
User reported issues with editing trip dates and deleting trips. Error display was added to help debug, but root cause may need further investigation if issue persists. Check browser console for specific error messages.

### Potential Improvements
- Add loading spinner during reset operation
- Consider adding "undo" grace period for reset
- Profile reset could log an activity entry before clearing (for admin tracking)

---

## 7. Commit Summary (This Session)

1. `Add profile reset feature and fix ARIA accessibility`
2. `Fix profile reset to delete all user data`
3. `Add Schengen error display and include tracker data in reset`
4. `Fix profile reset for move date and research reports`
5. `Fix profile reset: add activity deletion and direct SQL for move date`
6. `Fix profile reset table names - use schema helper`

---

## 8. Previous Session Summary

### Comprehensive Codebase Review
- Reviewed 7 major areas, identified 216 issues
- Implemented security fixes (XSS prevention, permission callbacks)
- Fixed TypeScript naming conventions
- Added React Query staleTime values
- Added ESLint plugins for React, accessibility, imports
- Added JSDoc to theme functions

---

*Generated: December 27, 2025*
