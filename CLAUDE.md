# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Relo2France** is a WordPress-based platform helping Americans relocate to France. It consists of:

1. **Main Plugin** (`france-relocation-assistant-plugin/`) - AI chat, MemberPress integration, auth flows
2. **Member Tools Plugin** (`france-relocation-member-tools/`) - React SPA portal, profiles, documents, tasks
3. **Theme** (`relo2france-theme/`) - Custom WordPress theme
4. **GitHub Sync Plugin** (`france-relocation-github-sync/`) - Deployment sync utility

## Build Commands

```bash
# Build React portal (required before deploying changes to portal)
cd france-relocation-member-tools/portal && npm run build

# Run dev server
cd france-relocation-member-tools/portal && npm run dev

# Lint TypeScript/React code
cd france-relocation-member-tools/portal && npm run lint

# Type check without emit
cd france-relocation-member-tools/portal && npx tsc --noEmit

# Create deployment zip (excludes node_modules)
cd france-relocation-member-tools && zip -r ../france-relocation-member-tools.zip . -x "portal/node_modules/*" -x "*.git*" -x "portal/.vite/*"
```

## Architecture

### React Portal (Member Tools)
- **Location**: `france-relocation-member-tools/portal/src/`
- **Stack**: React 18 + TypeScript + Vite + Tailwind CSS
- **State**: Zustand (global), React Query (server state)
- **Build output**: Copied to `assets/portal/` for WordPress to serve

### REST API
- **Namespace**: `/wp-json/framt/v1/`
- **Main handler**: `france-relocation-member-tools/includes/class-framt-portal-api.php` (~4000 lines, 40+ endpoints)
- **Auth**: WordPress cookie auth + nonce

Key endpoints:
- `/projects`, `/tasks`, `/notes`, `/files` - Core CRUD
- `/profile`, `/profile/completion` - User profile (30+ fields)
- `/checklists/{type}` - Visa checklists
- `/documents/generate`, `/documents/preview` - Document generation
- `/chat/message`, `/guides/chat` - AI chat interfaces

### Database

Custom tables (prefix `wp_framt_`):
- `projects`, `tasks`, `task_checklists`, `files`, `notes`, `messages`, `message_replies`

User meta keys (prefix `fra_`):
- `fra_profile_*` - Profile fields
- `fra_checklist_*` - Checklist progress
- `fra_dependents`, `fra_previous_visas` - Array data
- `fra_chat_profile_hash` - Tracks profile changes for chat context refresh

## Coding Conventions

### PHP
- **SQL**: Always use `$wpdb->prepare()` for queries
- **Input sanitization**: `sanitize_text_field()`, `sanitize_email()`, `sanitize_array_recursive()`
- **Output escaping**: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- **Comparisons**: Use strict (`===`, `!==`) with type casting for permission checks

### React/TypeScript
- Components in `PascalCase.tsx` in `components/{feature}/` directories
- API calls via hooks in `hooks/useApi.ts`
- Types defined in `types/index.ts`
- Configuration constants in `config/*.ts`
- Avoid `dangerouslySetInnerHTML` (XSS risk)

## Key Files

| Purpose | Location |
|---------|----------|
| Portal API (PHP) | `france-relocation-member-tools/includes/class-framt-portal-api.php` |
| Portal Routes | `france-relocation-member-tools/portal/src/App.tsx` |
| API Client | `france-relocation-member-tools/portal/src/api/client.ts` |
| React Query Hooks | `france-relocation-member-tools/portal/src/hooks/useApi.ts` |
| TypeScript Types | `france-relocation-member-tools/portal/src/types/index.ts` |
| Zustand Store | `france-relocation-member-tools/portal/src/store/index.ts` |
| In-Chat UI | `france-relocation-assistant-plugin/includes/shortcode-template.php` |
| Knowledge Base | `france-relocation-assistant-plugin/includes/knowledge-base-default.php` |

## WordPress.com Hosting Notes

- Script combining can break ES modules - use `data-cfasync="false"` attributes
- Portal script loaded directly in template-portal.php to bypass optimization
- MemberPress features gracefully degrade if not installed
