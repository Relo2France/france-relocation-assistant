# HANDOFF.md - Session Handoff Document

**Date:** December 23, 2025
**Branch:** `claude/update-resume-Os09v`

---

## 1. Current Project Status

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

1. **CLAUDE.md Structure:** Decided to focus on actionable content (commands, architecture, style guide) rather than historical changelog
2. **Style Guide Scope:** Included concrete code examples rather than abstract rules
3. **Security Emphasis:** Made security patterns (sanitization, escaping, prepare) prominently labeled as "MANDATORY"
4. **Forbidden Patterns:** Explicitly listed anti-patterns to prevent common mistakes

---

## 6. Issues / Bugs Being Tracked

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
