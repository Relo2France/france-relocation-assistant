# Add New Portal Menu Item

Add a new menu item to the portal sidebar. Follow the checklist in CLAUDE.md.

## Arguments
$ARGUMENTS - The menu item key (e.g., "resources") and label (e.g., "Resources")

## Full Checklist

### 1. PHP Backend - Portal Settings Class
**File:** `france-relocation-member-tools/includes/class-framt-portal-settings.php`

- Add `'menu_{item}' => true` to `$defaults` array (~line 57-74)
- Add `'label_{item}' => 'Item Label'` to `$defaults` array (~line 76-93)
- Add `'icon_{item}' => 'IconName'` to `$defaults` array (~line 95-112)
- Add `'menu_{item}'` to `$bool_fields` array in `sanitize()` (~line 286-289)
- Add `'label_{item}'` to `$text_fields` array in `sanitize()` (~line 296-302)
- Add `'icon_{item}'` to `$text_fields` array in `sanitize()` (~line 296-302)
- Add `'menu_{item}'` to `$tab_fields['menu']` visibility array (~line 632-635) **CRITICAL**
- Add `'label_{item}'` to `$tab_fields['menu']` labels array (~line 637-640)
- Add `'icon_{item}'` to `$tab_fields['menu']` icons array (~line 642-645)
- Add item to `$menu_items` array in `render_menu_tab_content()` (~line 830-847)
- Add item to appropriate section in `$default_section_items` (~line 850-854)

### 2. PHP Backend - Portal Template
**File:** `france-relocation-member-tools/templates/template-portal.php`

- Add `'menu_{item}' => true` to `$defaults` array (~line 55-71)
- Add `'label_{item}' => 'Item Label'` to `$defaults` array (~line 73-89)
- Add `'icon_{item}' => 'IconName'` to `$defaults` array (~line 91-107)
- Add `'{item}' => '/{item}'` to `$menu_items` array (~line 161-178)

### 3. React Frontend - Sidebar Component
**File:** `france-relocation-member-tools/portal/src/components/layout/Sidebar.tsx`

- Import the icon from `lucide-react`
- Add icon to `iconComponents` map
- Add `'{item}'` to appropriate section in `defaultSectionOrder` **CRITICAL**

### 4. React Frontend - Routes & View
**File:** `france-relocation-member-tools/portal/src/App.tsx`

- Import the view component
- Add route: `<Route path="/{item}" element={<ItemView />} />`

Create the view component in `portal/src/components/{item}/ItemView.tsx`

### 5. Verify
Run `/build-portal` to build and verify.

## Common Issues
- Menu item enabled but not showing? Check `defaultSectionOrder` in Sidebar.tsx
- 404 when clicking? Check route exists in App.tsx
- Icon not rendering? Check iconComponents map in Sidebar.tsx
