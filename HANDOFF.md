# Session Handoff Document

**Date:** December 23, 2025
**Branch:** `claude/continue-session-pOGZm`
**Last Commit:** `7332d72` - Update all remaining profile lookups to use portal profile
# HANDOFF.md - Session Handoff Document

**Date:** December 23, 2025
**Branch:** `claude/update-resume-Os09v`

---

## 1. Current Project Status

The France Relocation Assistant is fully functional with the React portal, AI-powered guides, and personalization features now correctly pulling user profile data. All "Nice to Have" optimizations from previous sessions have been implemented (virtual scrolling, code splitting, rate limiting, caching, etc.).

---

## 2. What We Completed This Session

### Fixed: AI Not Seeing Correct Visa Type

**Root Cause Identified:** The system had TWO separate profile storage mechanisms that were not synchronized:

| Storage Location | Used By | Issue |
|------------------|---------|-------|
| User Meta (`fra_*` prefix) | React Portal (save/load) | ✅ Correct source |
| Database Table (`wp_framt_profiles`) | AI/Guide Generation | ❌ Was reading stale/empty data |

**Solution:** Created `FRAMT_Profile::get_portal_profile()` static method that reads from user meta, then updated ALL profile lookups for AI/personalization to use it.

### Files Modified

**New Method Added:**
- `class-framt-profile.php` - Added `get_portal_profile()` static method (70+ lines)

**Profile Lookups Updated (14 locations):**

| File | Method/Function |
|------|-----------------|
| `class-framt-portal-api.php` | `get_user_portal_context()` |
| `france-relocation-member-tools.php` | `ajax_generate_guide()` |
| `france-relocation-member-tools.php` | `ajax_guide_chat()` |
| `france-relocation-member-tools.php` | `ajax_get_guide_questions()` |
| `france-relocation-member-tools.php` | `ajax_document_chat()` |
| `france-relocation-member-tools.php` | `ajax_verify_health_insurance()` |
| `france-relocation-member-tools.php` | `render_research_tool()` |
| `france-relocation-member-tools.php` | `add_member_context()` |
| `class-framt-guides.php` | `get_apostille_guide()` |
| `class-framt-checklists.php` | `get_total_items()`, `render()` |
| `class-framt-document-generator.php` | `ajax_generate()` |
| `class-framt-chat-handler.php` | `get_document_chat_intro()` |
| `class-framt-dashboard.php` | `get_dashboard_data()` |
| `class-framt-documents.php` | `render_health_insurance_chat()` |
| `class-framt-glossary.php` | `render()` |

### Also Fixed Earlier in Session

- `get_personalized_guide()` - Was using wrong meta key `fra_visa_type_applying` instead of `fra_visa_type`
- `get_user_portal_context()` - Was using wrong field names (`target_region` instead of `target_location`, etc.)
- `build_user_context_summary()` - Updated to match corrected field names
- Checklist progress retrieval - Was using wrong checklist types and meta key format
**Relo2France** is a WordPress-based platform helping Americans relocate to France. The project is stable and in active use with the following components:

- **Main Plugin** (`france-relocation-assistant-plugin/`) - v2.9.83
- **Member Tools Plugin** (`france-relocation-member-tools/`) - v2.1.0
- **Theme** (`relo2france-theme/`) - v1.2.3

The React portal is fully functional with 40+ REST API endpoints. All major features are complete including profile management, task tracking, document generation, checklists, and AI-powered guides.

---

## 2. What We Completed This Session

### Profile Change Detection for AI Chat
- **Problem:** When users changed their visa type in the profile, the AI guide chat continued using stale context from the old profile data
- **Solution:** Added profile hash comparison in `class-framt-portal-api.php:3903-3909`
- **How it works:**
  - Calculates MD5 hash of current profile
  - Compares to stored hash in `fra_chat_profile_hash` user meta
  - Clears chat history if profile changed, forcing fresh context
- **Commit:** `a07c47e` - "Clear chat history when profile changes to avoid stale context"

### CLAUDE.md Improvements
- **Streamlined content:** Reduced from 294 to ~260 lines by removing:
  - Dated development history entries that become stale
  - Manual testing checklist (not useful for AI)
  - Version numbers that get outdated
  - Completed items in "Outstanding Work" section
- **Added required header:** Claude Code guidance prefix
- **Added lint command:** `npm run lint` to build commands
- **Commits:**
  - `a379c06` - "Streamline CLAUDE.md for Claude Code"
  - `82ce9bb` - "Add comprehensive style guide and coding rules to CLAUDE.md"

### Comprehensive Style Guide Added to CLAUDE.md
New sections covering:
- **PHP Style:** File structure, naming conventions, security patterns (wpdb->prepare, sanitization, escaping, permission checks), WordPress patterns
- **React/TypeScript Style:** File organization, component patterns, naming conventions
- **React Query Patterns:** Query keys, queries with staleTime, mutations with cache invalidation
- **API Client Pattern:** Typed apiFetch usage
- **Tailwind CSS Conventions:** clsx usage, card styles, spacing, responsive design
- **Accessibility Requirements:** ARIA attributes, progress bars, icon accessibility
- **Forbidden Patterns:** dangerouslySetInnerHTML, inline styles, any type, direct DOM manipulation

---

## 3. What's In Progress / Partially Done

**Nothing currently in progress.** All identified issues have been fixed and pushed.

---

## 4. Next Steps

1. **Create Pull Request** - The changes are on branch `claude/continue-session-pOGZm` and ready for PR to main
2. **Deploy and Test** - Verify on production that AI correctly shows user's visa type
3. **Consider Profile Sync** - Long-term, may want to consolidate the two storage systems or add a sync mechanism
**Nothing left incomplete from this session.**

Previous session work (all completed before this session resumed):
- Chat window height matching sidebar
- Auto-scroll to top of AI responses
- AI accuracy fixes for translations and background checks
- Personalized guides based on user profile/documents/tasks
- AI verification requirements with web search

---

## 4. Next Steps Discussed

No specific next steps were discussed this session. Potential future work from CLAUDE.md "Nice to Have":
- Virtual scrolling for long lists
- Bundle size optimization
- Rate limiting for AI endpoints

---

## 5. Decisions Made This Session

1. **Use user meta as source of truth for AI personalization** - Since the React portal saves to user meta (`fra_*` keys), all AI/personalization code should read from there via `get_portal_profile()`

2. **Keep database table for internal operations** - The `wp_framt_profiles` table and `get_profile()` method are still used for internal operations like MemberPress sync and prefill logic

3. **Static method for portal profile** - Made `get_portal_profile()` a static method for easier access without needing an instance
1. **CLAUDE.md Structure:** Decided to focus on actionable content (commands, architecture, style guide) rather than historical changelog
2. **Style Guide Scope:** Included concrete code examples rather than abstract rules
3. **Security Emphasis:** Made security patterns (sanitization, escaping, prepare) prominently labeled as "MANDATORY"
4. **Forbidden Patterns:** Explicitly listed anti-patterns to prevent common mistakes

---

## 6. Issues / Bugs Being Tracked

### Resolved This Session
- ✅ AI not seeing correct visa type in personalized guides
- ✅ AI context using wrong profile field names
- ✅ Checklist progress using wrong types and meta keys

### Known Technical Debt
- **Dual profile storage** - Two separate storage systems (user meta vs database table) is confusing. Consider consolidating in future.
- **Document generation address fields** - `fra_current_address`, `fra_current_city`, `fra_current_country` are expected by document generator but not in profile definition (users provide via form)

### Previously Tracked (From CLAUDE.md "Nice to Have")
- ✅ Virtual scrolling - Implemented
- ✅ Bundle optimization - Implemented
- ✅ Rate limiting - Implemented
- ✅ Security logging - Implemented
- ✅ PHP type hints - Added to key methods
- ✅ Performance caching - Implemented

---

## Commits This Session

```
7332d72 Update all remaining profile lookups to use portal profile
c7bd82e Fix get_user_portal_context to use portal profile
5414ebc Fix guides using wrong profile storage - use portal user meta
49fcdf5 Fix AI context using wrong profile field names
3b2f557 Fix personalized guides using wrong visa type meta key
```

---

## Quick Reference

### Testing the Fix
1. Log into portal as a member
2. Set visa type in Profile section
3. Use AI chat or generate a guide
4. Verify the AI references the correct visa type

### Key File Locations
- Portal profile method: `class-framt-profile.php::get_portal_profile()`
- AI context builder: `class-framt-portal-api.php::get_user_portal_context()`
- User meta prefix: `fra_` (e.g., `fra_visa_type`, `fra_nationality`)

---

*Generated: December 23, 2025*
**No active bugs identified this session.**

### Known WordPress.com Hosting Quirks (not bugs, just notes):
- Script combining can break ES modules - use `data-cfasync="false"` attributes
- Portal script loaded directly in template to bypass optimization
- MemberPress features gracefully degrade if not installed

---

## Files Modified This Session

| File | Changes |
|------|---------|
| `france-relocation-member-tools/includes/class-framt-portal-api.php` | Added profile hash detection (lines 3903-3909) |
| `CLAUDE.md` | Streamlined + added comprehensive style guide |
| `france-relocation-member-tools-v2.1.0.zip` | Updated with latest changes |

---

## Git Status

```
Branch: claude/update-resume-Os09v
Status: Clean (all changes committed and pushed)
Latest commit: 82ce9bb - Add comprehensive style guide and coding rules to CLAUDE.md
```
