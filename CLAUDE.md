# CLAUDE.md - Project Memory for France Relocation Assistant

> This file serves as persistent context for AI assistants across development sessions.

---

## Project Overview

**Relo2France** is a WordPress-based platform helping users relocate to France. It consists of:

1. **Main Plugin** (`france-relocation-assistant`) - AI chat, MemberPress integration, auth flows
2. **Member Tools Plugin** (`france-relocation-member-tools`) - Portal, profiles, documents, tasks
3. **Theme** (`relo2france-theme`) - Custom WordPress theme with scroll-snap behavior
4. **GitHub Sync Plugin** (`france-relocation-github-sync`) - Deployment sync utility

---

## Current Versions

| Component | Version | Main File |
|-----------|---------|-----------|
| Main Plugin | v2.9.83 | `france-relocation-assistant-plugin/france-relocation-assistant.php` |
| Member Tools | v2.1.0 | `france-relocation-member-tools/france-relocation-member-tools.php` |
| Theme | v1.2.3 | `relo2france-theme/functions.php` |

---

## Repository Structure

```
france-relocation-assistant/
├── france-relocation-assistant-plugin/   # Main plugin (chat, auth, MemberPress)
│   ├── france-relocation-assistant.php   # Main plugin file
│   ├── includes/
│   │   └── shortcode-template.php        # In-chat auth UI
│   └── assets/
│       ├── css/frontend.css
│       └── js/frontend.js
│
├── france-relocation-member-tools/       # Member tools plugin
│   ├── france-relocation-member-tools.php
│   ├── includes/
│   │   ├── class-framt-portal-api.php    # REST API (~4000 lines, 40+ endpoints)
│   │   ├── class-framt-documents.php
│   │   ├── class-framt-document-generator.php
│   │   ├── class-framt-profile.php
│   │   ├── class-framt-task.php
│   │   ├── class-framt-messages.php      # Support ticket system
│   │   └── class-framt-ai-guide-generator.php
│   ├── portal/                           # React SPA
│   │   ├── src/
│   │   │   ├── App.tsx                   # Routes
│   │   │   ├── main.tsx                  # Entry point
│   │   │   ├── api/client.ts             # API client modules
│   │   │   ├── hooks/useApi.ts           # React Query hooks
│   │   │   ├── store/index.ts            # Zustand store
│   │   │   ├── types/index.ts            # TypeScript interfaces
│   │   │   └── components/
│   │   │       ├── dashboard/
│   │   │       ├── tasks/
│   │   │       ├── documents/
│   │   │       ├── profile/
│   │   │       ├── checklists/
│   │   │       ├── glossary/
│   │   │       ├── membership/
│   │   │       ├── chat/
│   │   │       └── layout/
│   │   └── dist/                         # Built assets
│   ├── assets/portal/                    # Copied build output
│   └── templates/
│       ├── template-portal.php           # Portal page template
│       └── template-homepage.php
│
├── relo2france-theme/                    # WordPress theme
│   ├── functions.php
│   ├── front-page.php
│   └── template-auth.php
│
└── france-relocation-github-sync/        # Deployment utility
```

---

## Key Technical Details

### React Portal

- **Framework**: React 18 + TypeScript + Vite
- **State**: Zustand (global), React Query (server state)
- **Styling**: Tailwind CSS
- **Build**: `npm run build` in `/portal` directory
- **Output**: Copied to `assets/portal/` for WordPress

### REST API Namespace

```
/wp-json/framt/v1/
```

**Key endpoint groups:**
- `/projects`, `/tasks`, `/notes`, `/files` - Core CRUD
- `/profile`, `/profile/completion` - User profile (30+ fields)
- `/checklists`, `/checklists/{type}` - Visa checklists
- `/documents/generate`, `/documents/preview` - Document generation
- `/glossary`, `/glossary/search` - French terms
- `/chat/message`, `/chat/categories` - Knowledge base chat
- `/membership/*` - MemberPress integration

### WordPress Integration

- Portal loads via `template-portal.php` page template
- Script loaded with `type="module"` for ES modules
- Uses WordPress REST API with cookie auth + nonce
- MemberPress shortcodes: `[fra_mepr_subscriptions]`, `[fra_mepr_payments]`

---

## Important Patterns & Conventions

### PHP Coding Standards

- Use `$wpdb->prepare()` for ALL SQL queries
- Sanitize inputs: `sanitize_text_field()`, `sanitize_email()`, `sanitize_array_recursive()`
- Escape outputs: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- Use strict comparisons (`===`, `!==`) with type casting for permission checks
- WordPress Yoda conditions preferred (though not consistently used)

### React Patterns

- Components in `PascalCase.tsx`
- API calls via hooks in `useApi.ts`
- Types defined in `types/index.ts`
- Avoid `dangerouslySetInnerHTML` (XSS risk)
- Use ARIA attributes for accessibility

### Database Tables

Custom tables (prefix: `wp_framt_`):
- `wp_framt_projects` - Relocation projects
- `wp_framt_tasks` - Task management
- `wp_framt_task_checklists` - Task sub-items
- `wp_framt_files` - Document storage
- `wp_framt_notes` - Notes/messages
- `wp_framt_messages` - Support tickets
- `wp_framt_message_replies` - Ticket replies

User meta keys (prefix: `fra_`):
- `fra_profile_*` - Profile fields
- `fra_checklist_*` - Checklist progress
- `fra_dependents`, `fra_previous_visas` - Array data

---

## Recent Development History

### December 18, 2025 - Dynamic Task Generation System
- **Auto-generated Tasks**: Tasks automatically created when user selects visa type
  - 15 common tasks for all visa types (document prep, banking, housing, OFII validation, etc.)
  - Visa-specific tasks: Visitor (3), Talent Passport (3), Employee (3), Entrepreneur (4), Student (5), Retiree (3)
- **Conditional Task Generation**: Dynamic tasks based on profile fields
  - Pet owners: 6 tasks (microchip, vaccinations, EU passport, pet-friendly housing, etc.)
  - Applicants with spouse: 5 tasks (marriage cert apostille/translation, spouse visa, etc.)
  - Families with children: 8 tasks (birth certs, school enrollment, vaccinations, CAF benefits, etc.)
- **Project-Profile Sync**: Project visa_type automatically syncs when profile visa_type is updated
- **Duplicate Prevention**: Tasks only created if they don't already exist (by title match)
- **Portal Account Settings**: Combined Profile & Account into single "Portal Account" tab
- **Self-Service Account Deletion**: Users can delete their own account with confirmation phrase
- **Dashboard Visa Display**: Shows visa type from profile with link to set if empty

### December 18, 2025 - Outstanding Improvements Completed
- **Component Refactoring**: Split ProfileView (1135→183 lines) and DocumentGenerator (963→402 lines)
  - ProfileView now uses: PersonalSection, ApplicantSection, VisaSection, LocationSection, FinancialSection
  - DocumentGenerator now uses: DocumentTypeSelector, DocumentWizard, DocumentPreviewStep, DownloadOptions
  - Created shared SaveButton and ProfileSkeleton components
  - Extracted constants to `config/profile.ts` and `config/documents.ts`
- **Accessibility**: Added comprehensive ARIA labels, roles, and describedby attributes
- **Error Handling**: Added ErrorBoundary component wrapping ViewRouter in App.tsx
- **Keyboard Navigation**: Full keyboard support for TaskBoard drag-and-drop
  - Space to pick up/drop, Arrow keys to move columns, Escape to cancel
  - Screen reader announcements for all operations
- **API Improvements**: Added AbortController support with `createCancellableRequest()` utility

### December 18, 2025 - Portal Feature Completion (Merged)
- Added 8 missing portal features for feature parity
- Created ProfileView, ChecklistsView, GlossaryView, KnowledgeBaseChat
- Created DocumentGenerator, AIVerification, MembershipView
- Added ~1,800 lines PHP API code, ~4,000 lines React code
- Fixed security issues (SQL injection, XSS, type juggling)
- Fixed accessibility issues (ARIA labels, roles)

### December 14, 2025 - In-Chat Account System
- Complete in-chat account management (no page navigation)
- Custom MemberPress shortcodes for subscriptions/payments
- Member messaging/support ticket system
- Dropdown structure: My Visa Profile → My Visa Documents → My Membership Account

---

## Outstanding Work / Known Issues

### Completed (Previously Should Address)
- ✅ Split large components (ProfileView, DocumentGenerator)
- ✅ Add more ARIA labels to form fields
- ✅ Implement keyboard navigation for drag-and-drop
- ✅ Add error boundaries at route level
- ✅ Request cancellation with AbortController for long operations

### Nice to Have
- Virtual scrolling for long lists
- Bundle size optimization
- Rate limiting for AI endpoints

### WordPress.com Specific
- Script combining can break ES modules - use `data-cfasync="false"` attributes
- Portal script loaded directly in template to bypass optimization

---

## Testing Checklist

### Portal Features
- [ ] Dashboard loads with progress tracker
- [ ] Tasks CRUD + Kanban drag-and-drop
- [ ] Documents upload/download/preview
- [ ] Profile view loads/saves 30+ fields
- [ ] Profile completion percentage works
- [ ] All 6 checklists load with correct items
- [ ] Document generator creates all 4 types
- [ ] Glossary search finds terms
- [ ] Chat responds to questions
- [ ] Membership info displays correctly

### In-Chat Features
- [ ] Login/logout flows work
- [ ] My Visa Profile loads member tools profile
- [ ] My Visa Documents shows documents
- [ ] My Membership Account shows billing form
- [ ] Subscriptions/Payments tables display
- [ ] My Messages shows support tickets

---

## Deployment Notes

1. **Build Portal**: `cd portal && npm run build`
2. **Copy Assets**: Build output auto-copies to `assets/portal/`
3. **Version Bump**: Update version in main plugin PHP file
4. **Database**: No migrations needed (uses user_meta + existing tables)
5. **Cache Clear**: Clear WordPress cache after deployment
6. **MemberPress**: Features gracefully degrade if not installed

---

## GitHub Repositories

- **Main Plugin**: `Relo2France/france-relocation-assistant`
- **Member Tools**: `Relo2France/france-relocation-member-tools`

---

## Quick Reference

### Common Commands

```bash
# Build portal
cd france-relocation-member-tools/portal && npm run build

# Run dev server (if configured)
cd france-relocation-member-tools/portal && npm run dev

# Check TypeScript
cd france-relocation-member-tools/portal && npx tsc --noEmit
```

### Key File Locations

| Purpose | File |
|---------|------|
| Portal API | `france-relocation-member-tools/includes/class-framt-portal-api.php` |
| Portal Entry | `france-relocation-member-tools/portal/src/main.tsx` |
| Portal Routes | `france-relocation-member-tools/portal/src/App.tsx` |
| API Client | `france-relocation-member-tools/portal/src/api/client.ts` |
| TypeScript Types | `france-relocation-member-tools/portal/src/types/index.ts` |
| Profile Config | `france-relocation-member-tools/portal/src/config/profile.ts` |
| Document Config | `france-relocation-member-tools/portal/src/config/documents.ts` |
| Error Boundary | `france-relocation-member-tools/portal/src/components/shared/ErrorBoundary.tsx` |
| In-Chat UI | `france-relocation-assistant-plugin/includes/shortcode-template.php` |
| MemberPress Shortcodes | `france-relocation-assistant-plugin/france-relocation-assistant.php` |

---

*Last Updated: December 18, 2025*
