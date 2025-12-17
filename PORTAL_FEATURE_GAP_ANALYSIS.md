# Portal Feature Gap Analysis

## Overview

This document compares the **existing website functionality** (main plugin + member tools) with the **new React portal** to identify missing features that need to be implemented.

---

## Feature Comparison Matrix

| Feature | Existing Website | New Portal | Status |
|---------|-----------------|------------|--------|
| Dashboard with Progress | Yes | Yes | **Complete** |
| Task Management (CRUD, Kanban) | Yes | Yes | **Complete** |
| Document Upload/Storage | Yes | Yes | **Complete** |
| Activity Timeline | Yes | Yes | **Complete** |
| Notes/Messages | Yes | Yes | **Complete** |
| Family Members | Yes | Yes | **Complete** |
| Help/FAQ | Yes | Yes | **Complete** |
| Basic Settings | Yes | Yes | **Complete** |
| **Full Profile (30+ fields)** | Yes | No (only 3 fields) | **MISSING** |
| **Document Generation** | Yes | No | **MISSING** |
| **Visa Application Checklists** | Yes | No | **MISSING** |
| **Personalized AI Guides** | Yes | No (static only) | **MISSING** |
| **French Glossary** | Yes | No | **MISSING** |
| **AI Document Verification** | Yes | No | **MISSING** |
| **MemberPress Integration** | Yes | No | **MISSING** |
| **Knowledge Base Chat** | Yes | No | **MISSING** |

---

## CRITICAL MISSING FEATURES

### 1. Full Profile Management (HIGH PRIORITY)
**Location in existing:** `includes/class-framt-profile.php`

The existing profile has 30+ fields essential for visa applications:

**Personal Information:**
- Legal first/middle/last name (as on passport)
- Date of birth, nationality
- Passport number and expiry date

**Applicant Information:**
- Applicants type (alone, spouse, spouse+kids, kids only)
- Spouse details (legal name, DOB, work status)
- Number of children, children ages
- Pet information (type, details)

**Visa & Employment:**
- Visa type selection (visitor, talent passport, employee, etc.)
- Employment status
- Work in France plans
- Industry, employer name

**Location Information:**
- Current state (US) - needed for consulate routing
- Birth state - needed for apostille routing
- Marriage state/country
- Target location in France

**Financial Information:**
- Financial resources range
- Income sources

**New Portal Status:** Only has first_name, last_name, display_name in Settings

---

### 2. Document Generation (HIGH PRIORITY)
**Location in existing:** `includes/class-framt-document-generator.php`

The existing system generates critical visa documents:

| Document Type | Description |
|--------------|-------------|
| **Cover Letter** | Personalized bilingual cover letter for visa application |
| **Financial Statement** | Proof of sufficient means attestation |
| **No Work Attestation** | Declaration not to work in France (French + English) |
| **Accommodation Letter** | Proof of housing letter |

**Features:**
- Pre-fills user data from profile
- Supports French and English
- Option for placeholder text (privacy)
- State-specific consulate information
- Word and PDF export

**New Portal Status:** Only has file upload/storage - no document generation

---

### 3. Visa Application Checklists (HIGH PRIORITY)
**Location in existing:** `includes/class-framt-checklists.php`

Two structured checklists with lead times:

**Visa Application Checklist:**
- Passport valid 6+ months (Check now)
- Passport photos (Same day)
- Completed visa form (30 min)
- Cover letter (30 min)
- Proof of financial means (1-2 weeks)
- Proof of accommodation (Varies)
- Health insurance certificate (1-2 days)
- Birth certificate apostilled (4-8 weeks)
- No work attestation - visitor visa (30 min)

**Relocation Document Checklist:**
- Bank statements 3 months (1-2 weeks)
- Marriage certificate (4-8 weeks)
- Employment verification letter (3-5 days)
- Tax returns 2 years (Check now)

**Features:**
- Visa-type-specific items
- Lead time estimates
- Status tracking (pending, in-progress, complete)
- "Handled on own" option
- Completion percentage

**New Portal Status:** Generic task system exists but no structured visa checklists

---

### 4. Personalized Guides (MEDIUM PRIORITY)
**Location in existing:** `includes/class-framt-guides.php`, `includes/class-framt-guide-generator.php`

| Guide | Personalization |
|-------|----------------|
| **Visa Application Guide** | AI-generated based on visa type, state, family |
| **Apostille Guide** | State-specific agency info, costs, timelines |
| **Pet Relocation Guide** | Based on pet type and details |
| **French Mortgages Guide** | With loan calculations |
| **French Bank Ratings** | Comparison data |

**Apostille Guide Personalization:**
- Pulls birth state, spouse birth state, marriage state from profile
- Shows state-specific:
  - Agency name (e.g., "California Secretary of State")
  - Method (Online, Mail, In-Person)
  - Cost ($20, $15, etc.)
  - Processing time
  - Website URL

**New Portal Status:** Has 9 static guides with hardcoded content - no personalization

---

### 5. French Glossary (MEDIUM PRIORITY)
**Location in existing:** `includes/class-framt-glossary.php`

Categories with searchable terms:

**Document & Legal Terms:**
- Apostille, Certified Copy, Attestation
- Compromis de vente, Acte de vente, Procuration

**Visa & Residency Terms:**
- VLS-TS, Titre de sejour, OFII
- Prefecture, Recepisse

**Healthcare Terms:**
- PUMA, Carte vitale, Mutuelle

**Features:**
- Short and full definitions
- French/English translations
- Searchable

**New Portal Status:** Not implemented

---

### 6. AI Document Verification (MEDIUM PRIORITY)
**Location in existing:** `includes/class-framt-ai-verification.php`

**Features:**
- Verify health insurance documents meet French requirements
- Supports PDF and image uploads (JPEG, PNG, GIF, WebP)
- Max 20MB file size
- Uses Claude API for analysis
- Checks for required coverage types
- Validates duration and territory

**New Portal Status:** Not implemented

---

### 7. MemberPress Integration (LOW PRIORITY - can use existing)
**Location in existing:** Main plugin `france-relocation-assistant.php`

**Features:**
- `[fra_mepr_subscriptions]` - In-chat subscription management
- `[fra_mepr_payments]` - Payment history
- Cancel/suspend/resume/update/upgrade actions

**New Portal Status:** Settings shows membership status badge but no management

---

### 8. Knowledge Base & AI Chat (OUT OF SCOPE)
**Location in existing:** Main plugin

The AI-powered chat with 8 knowledge categories (Visas, Property, Healthcare, etc.) is in the main plugin and typically accessed separately. This may remain outside the portal scope.

---

## IMPLEMENTATION PLAN

### Phase 1: Profile Management (Highest Priority)
**Estimated scope:** Add new "Profile" view to portal

1. Create `ProfileView.tsx` component with multi-section form:
   - Personal Information section
   - Applicant/Family section
   - Visa & Employment section
   - Location section
   - Financial section

2. Add profile API endpoints to `client.ts`:
   - `GET /profile` - fetch full profile
   - `PUT /profile` - update profile

3. Add to sidebar navigation as "My Profile"

4. Conditional field rendering based on applicant type

5. Profile completion percentage indicator

---

### Phase 2: Document Generation (Highest Priority)
**Estimated scope:** Add document generation section to Documents view

1. Create `DocumentGenerator.tsx` component:
   - Document type selector
   - Wizard-style questions per document type
   - Preview before generation
   - Download (Word/PDF) functionality

2. Add API endpoints:
   - `POST /documents/generate` - generate document
   - `GET /documents/generated` - list generated docs

3. Document types to implement:
   - Cover Letter (with language selection)
   - Financial Statement
   - No Work Attestation
   - Accommodation Letter

4. Add "Generate Document" button to Documents view

---

### Phase 3: Visa Checklists (High Priority)
**Estimated scope:** Add checklist section to Tasks or new view

1. Create `ChecklistsView.tsx` component:
   - Visa Application Checklist
   - Relocation Document Checklist

2. Features:
   - Item status (pending, in-progress, complete)
   - Lead time display
   - "Handled on own" toggle
   - Completion percentage
   - Visa-type-specific items

3. Add API endpoints:
   - `GET /checklists` - get checklists with status
   - `PUT /checklists/:type/:item` - update item status

4. Option: Integrate into existing Tasks view as a special "Checklist" task type

---

### Phase 4: Personalized Guides (Medium Priority)
**Estimated scope:** Enhance existing Guides view

1. Add guide generation for:
   - Visa Application Guide (AI-generated)
   - Apostille Guide (state-specific)
   - Pet Relocation Guide

2. Create `PersonalizedGuide.tsx` component:
   - Fetch guide content from API
   - Display personalized data
   - State-specific information

3. Add API endpoints:
   - `GET /guides/:type` - get personalized guide

4. Replace static guide content with API-driven content

---

### Phase 5: Glossary (Medium Priority)
**Estimated scope:** New simple view

1. Create `GlossaryView.tsx`:
   - Category tabs (Documents, Visa, Healthcare)
   - Search functionality
   - Expandable term definitions

2. Add to sidebar under Resources

3. Add API endpoint:
   - `GET /glossary` - get all terms

---

### Phase 6: AI Verification (Lower Priority)
**Estimated scope:** Add to Documents view

1. Create `AIVerification.tsx` component:
   - File upload for verification
   - Verification result display
   - Pass/fail status with details

2. Add to file upload flow or as separate action

3. Add API endpoint:
   - `POST /verify/health-insurance` - verify document

---

## FILE CHANGES SUMMARY

### New Components to Create:
```
src/components/
├── profile/
│   ├── ProfileView.tsx
│   ├── ProfileSection.tsx
│   └── index.ts
├── documents/
│   ├── DocumentGenerator.tsx  (add)
│   ├── GeneratedDocuments.tsx (add)
│   └── AIVerification.tsx     (add)
├── checklists/
│   ├── ChecklistsView.tsx
│   ├── ChecklistItem.tsx
│   └── index.ts
├── glossary/
│   ├── GlossaryView.tsx
│   └── index.ts
└── guides/
    └── PersonalizedGuide.tsx  (add)
```

### API Client Updates (`src/api/client.ts`):
- Add profileApi
- Add checklistsApi
- Add documentGeneratorApi
- Add glossaryApi
- Add verificationApi

### Types Updates (`src/types/index.ts`):
- Add Profile interface
- Add ChecklistItem interface
- Add GeneratedDocument interface
- Add GlossaryTerm interface

### Navigation Updates:
- Add "My Profile" to sidebar
- Add "Checklists" to sidebar (or integrate with Tasks)
- Add "Glossary" to Resources section

---

## PRIORITY ORDER

1. **Profile Management** - Required for document generation
2. **Document Generation** - Core member value
3. **Visa Checklists** - Critical for relocation planning
4. **Personalized Guides** - Enhances existing guides
5. **Glossary** - Quick win, simple implementation
6. **AI Verification** - Nice-to-have enhancement

---

## QUESTIONS FOR USER

1. Should the Knowledge Base Chat be integrated into the portal, or remain in the main website?
2. Should MemberPress subscription management be accessible from within the portal?
3. For Checklists - prefer a separate view or integrated into the Tasks view?
4. Should generated documents be stored in the same Documents view as uploaded files?
