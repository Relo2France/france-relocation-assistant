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

## Style Guide & Coding Rules

### PHP Style

**File Structure:**
```php
<?php
/**
 * Class Description
 *
 * @package     FRA_Member_Tools
 * @subpackage  Portal
 * @since       2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

**Naming:**
- Classes: `FRAMT_Class_Name` (prefix `FRAMT_` for Member Tools, `FRA_` for main plugin)
- Functions: `snake_case` with descriptive verbs (`get_dashboard`, `create_task`)
- Constants: `UPPERCASE_SNAKE_CASE`
- User meta keys: `fra_` prefix (e.g., `fra_profile_first_name`)
- Database tables: `wp_framt_` prefix

**Security (MANDATORY):**
```php
// SQL - ALWAYS use prepare()
$wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

// Input sanitization
$title = sanitize_text_field( $params['title'] );
$email = sanitize_email( $params['email'] );
$html  = wp_kses_post( $params['content'] );

// Output escaping
echo esc_html( $value );
echo esc_attr( $attribute );

// Permission checks - ALWAYS use strict comparison with type cast
if ( (int) $resource->user_id !== (int) $current_user_id ) {
    return new WP_Error( 'forbidden', 'Access denied', array( 'status' => 403 ) );
}
```

**WordPress Patterns:**
- Use singleton pattern for main classes (`get_instance()`)
- Register hooks in constructor: `add_action( 'rest_api_init', array( $this, 'register_routes' ) )`
- Return `WP_Error` for REST API errors, not exceptions
- Use `array()` syntax (not `[]`) for WordPress compatibility

### React/TypeScript Style

**File Organization:**
```
components/
  {feature}/           # Feature folder
    FeatureView.tsx    # Main view component
    SubComponent.tsx   # Sub-components
    index.ts           # Barrel export
```

**Component Pattern:**
```tsx
/**
 * ComponentName
 *
 * Brief description of what the component does.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import { IconName } from 'lucide-react';
import { useApiHook } from '@/hooks/useApi';
import type { TypeName } from '@/types';

interface Props {
  requiredProp: string;
  optionalProp?: number;
}

export default function ComponentName({ requiredProp, optionalProp }: Props) {
  const [state, setState] = useState<string>('');
  const { data, isLoading } = useApiHook();

  if (isLoading) {
    return <LoadingSkeleton />;
  }

  return (
    <div className="p-6">
      {/* Section comment */}
      <h1 className="text-2xl font-bold text-gray-900">{requiredProp}</h1>
    </div>
  );
}
```

**Naming:**
- Components: `PascalCase` (e.g., `ProfileView`, `TaskCard`)
- Hooks: `useCamelCase` (e.g., `useMemberProfile`, `useUpdateTask`)
- Types/Interfaces: `PascalCase` (e.g., `MemberProfile`, `TaskStatus`)
- Files: Match component name (`ProfileView.tsx`)

**React Query Patterns:**
```tsx
// Query keys - define in useApi.ts
export const queryKeys = {
  profile: ['profile'] as const,
  task: (id: number) => ['task', id] as const,
};

// Queries
export function useProfile() {
  return useQuery({
    queryKey: queryKeys.profile,
    queryFn: profileApi.get,
    staleTime: 30000,
  });
}

// Mutations with cache invalidation
export function useUpdateProfile() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: profileApi.update,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.profile });
    },
  });
}
```

**API Client Pattern:**
```tsx
// All API calls go through apiFetch in api/client.ts
export const profileApi = {
  get: () => apiFetch<MemberProfile>('/profile'),
  update: (data: Partial<MemberProfile>) =>
    apiFetch<MemberProfile>('/profile', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),
};
```

**Tailwind CSS Conventions:**
- Use `clsx()` for conditional classes
- Common card style: `className="card p-6"` (card class defined in CSS)
- Color scale: `primary-500`, `primary-600` for brand colors
- Spacing: Use Tailwind scale (`p-6`, `mb-4`, `gap-3`)
- Responsive: Mobile-first (`md:`, `lg:` prefixes)

**Accessibility (Required):**
```tsx
// ARIA attributes on interactive elements
<button
  aria-expanded={isOpen}
  aria-controls="section-id"
  aria-label="Toggle section"
>

// Progress bars
<div
  role="progressbar"
  aria-valuenow={75}
  aria-valuemin={0}
  aria-valuemax={100}
>

// Icons should be decorative
<Icon className="w-5 h-5" aria-hidden="true" />
```

**Forbidden Patterns:**
- `dangerouslySetInnerHTML` - XSS risk, use markdown parser instead
- Inline styles - Use Tailwind classes
- `any` type - Define proper types in `types/index.ts`
- Direct DOM manipulation - Use React state

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

## Adding New Portal Menu Items (CHECKLIST)

When adding a new menu item to the portal sidebar, ALL of the following locations must be updated:

### 1. PHP Backend - Portal Settings Class
**File:** `france-relocation-member-tools/includes/class-framt-portal-settings.php`

- [ ] Add `'menu_{item}' => true` to `$defaults` array (~line 57-74)
- [ ] Add `'label_{item}' => 'Item Label'` to `$defaults` array (~line 76-93)
- [ ] Add `'icon_{item}' => 'IconName'` to `$defaults` array (~line 95-112)
- [ ] Add `'menu_{item}'` to `$bool_fields` array in `sanitize()` (~line 286-289)
- [ ] Add `'label_{item}'` to `$text_fields` array in `sanitize()` (~line 296-302)
- [ ] Add `'icon_{item}'` to `$text_fields` array in `sanitize()` (~line 296-302)
- [ ] Add `'menu_{item}'` to `$tab_fields['menu']` visibility array (~line 632-635) **← CRITICAL: often missed!**
- [ ] Add `'label_{item}'` to `$tab_fields['menu']` labels array (~line 637-640)
- [ ] Add `'icon_{item}'` to `$tab_fields['menu']` icons array (~line 642-645)
- [ ] Add item to `$menu_items` array in `render_menu_tab_content()` (~line 830-847)
- [ ] Add item to appropriate section in `$default_section_items` (~line 850-854)

### 2. PHP Backend - Portal Template
**File:** `france-relocation-member-tools/templates/template-portal.php`

- [ ] Add `'menu_{item}' => true` to `$defaults` array (~line 55-71)
- [ ] Add `'label_{item}' => 'Item Label'` to `$defaults` array (~line 73-89)
- [ ] Add `'icon_{item}' => 'IconName'` to `$defaults` array (~line 91-107)
- [ ] Add `'{item}' => '/{item}'` to `$menu_items` array (~line 161-178)

### 3. React Frontend - Sidebar Component
**File:** `france-relocation-member-tools/portal/src/components/layout/Sidebar.tsx`

- [ ] Import the icon from `lucide-react` (~line 2-25)
- [ ] Add icon to `iconComponents` map (~line 30-52)
- [ ] Add `'{item}'` to appropriate section in `defaultSectionOrder` (~line 54-58) **← CRITICAL: often missed!**

### 4. React Frontend - Routes
**File:** `france-relocation-member-tools/portal/src/App.tsx`

- [ ] Add route: `<Route path="/{item}" element={<ItemView />} />`
- [ ] Import the view component

### 5. Build
- [ ] Run `cd france-relocation-member-tools/portal && npm run build`

### Common Issues

1. **Menu item enabled but not showing in sidebar**: Check `defaultSectionOrder` in `Sidebar.tsx`
2. **Menu item not preserved when saving other tabs**: Check `$tab_fields['menu']` visibility array
3. **Icon not rendering**: Check icon import and `iconComponents` map in `Sidebar.tsx`
4. **404 when clicking menu item**: Check route exists in `App.tsx`

## WordPress.com Hosting Notes

- Script combining can break ES modules - use `data-cfasync="false"` attributes
- Portal script loaded directly in template-portal.php to bypass optimization
- MemberPress features gracefully degrade if not installed
