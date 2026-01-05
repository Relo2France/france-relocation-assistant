# Add New Profile Field

Add a new field to the member profile system. This requires updates in multiple places.

## Arguments
$ARGUMENTS - The field name (e.g., "phone_number") and type (e.g., "string")

## Checklist

### 1. TypeScript Type (REQUIRED)
**File:** `france-relocation-member-tools/portal/src/types/index.ts`
- Add field to `MemberProfile` interface
- Add type alias if needed (e.g., `export type FieldType = 'option1' | 'option2'`)

### 2. React Component
**File:** Appropriate section in `portal/src/components/profile/`
- PersonalSection.tsx - name, email, phone, dob
- ApplicantSection.tsx - applicant type, pets, dependents
- VisaSection.tsx - visa type, employment, work plans
- LocationSection.tsx - current location, birth/marriage states
- TimelineSection.tsx - move timeline, target date, housing
- FinancialSection.tsx - resources, income, french proficiency
- DocumentsSection.tsx - birth/marriage certificate status

Steps:
1. Add to formData state
2. Add to useEffect initialization from profile
3. Add form input JSX
4. Ensure field is included in handleSubmit

### 3. PHP Backend (if new field)
**File:** `france-relocation-member-tools/includes/class-framt-portal-api.php`
- Add to allowed profile fields array in `update_profile()` method
- Add to `get_profile()` response if needed

### 4. Verify
Run `/verify` to ensure everything compiles and builds.

## Example
For field `phone_number: string`:

```typescript
// types/index.ts
interface MemberProfile {
  // ...existing fields
  phone_number: string;
}
```

```tsx
// PersonalSection.tsx - add to formData
const [formData, setFormData] = useState({
  // ...existing
  phone_number: '',
});
```
