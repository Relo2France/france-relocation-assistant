# relo2france Regional Overview Report Generator

## Task
Generate a comprehensive, branded React artifact overview report for a French region or department. These reports serve Americans relocating to France who need detailed, practical information about specific areas.

## Brand Identity

### Colors
```javascript
const brandColors = {
  blue: "#4A7BA7",      // Primary - headings, links, accent backgrounds
  gold: "#E5A54B",      // Secondary - highlights, special callouts
  darkText: "#2D3748",  // Body text
  lightGray: "#718096", // Subdued text, captions
  lightBlue: "#EBF4FA"  // Background for stat cards and info boxes
};
```

### Logo
- Location: `/mnt/user-data/uploads/relo2france_updated_logo.png`
- Note: File is WebP format despite .png extension - must convert before base64 encoding
- Display size: 250px width, centered at top of report

## Report Structure

### Header Section
- Centered logo (250px width)
- Main title: Region/Department name (4xl, brand blue)
- Subtitle: Department number + parent region (xl, brand gold)
- Tagline: Key distinguishing characteristic
- Secondary tagline: Notable features

### Quick Stats Grid
4-column grid of StatCard components showing key metrics:
- Area (km²)
- Population
- Préfecture/Capital
- Distinguishing metric (vineyards, coastline, etc.)

### Content Sections
Each section uses the expandable Section component with:
- Icon from lucide-react
- Title
- Mix of prose paragraphs, stat grids, DataRow tables, and callout boxes

**Required Sections (adapt content to location):**

1. **Geography & Landscape** (Mountain icon)
   - Physical description, borders, terrain
   - Major geographic features (rivers, mountains, coasts)
   - Elevation range, notable landmarks
   - Stat grid: area details, communes, specific features

2. **Climate** (Thermometer icon)
   - Climate type and characteristics
   - Seasonal breakdown (Spring/Summer/Autumn/Winter)
   - Temperature and rainfall data
   - Climate considerations for residents

3. **Population & Demographics** (Users icon)
   - Total population, density
   - Major cities/towns with populations
   - Growth trends, age demographics
   - Migration patterns, expat communities

4. **Economy** (TrendingUp icon)
   - GDP, major industries
   - Key employers, economic sectors
   - Employment statistics
   - Recent economic developments

5. **Language & Identity** (Languages icon)
   - Regional languages/dialects
   - Local accent characteristics
   - Cultural identity markers
   - Historical linguistic influences

6. **Gastronomy** (Utensils icon)
   - Signature dishes and products
   - Local specialties grid
   - AOC/AOP products
   - Restaurant scene, markets

7. **Culture & Lifestyle** (Music icon)
   - UNESCO sites, cultural institutions
   - Local traditions, festivals
   - Sports, recreation
   - Daily life characteristics

8. **Administrative Geography** (MapPin icon)
   - Arrondissements or sub-regions
   - Notable communes
   - Local governance structure
   - Postal codes, department numbers

9. **Quality of Life** (Heart icon)
   - Healthcare facilities
   - Education options
   - Transport connections
   - Considerations/challenges callout box

10. **Environment & Natural Heritage** (TreePine icon)
    - Natural parks, protected areas
    - Biodiversity highlights
    - Environmental challenges
    - Outdoor recreation

### Footer
- Data sources with dates
- Reference websites
- Branded "relo2france.com" wordmark

## Component Patterns

### StatCard
```jsx
<StatCard label="Population" value="1.64M" sublabel="2024 estimate" />
```
Light blue background, centered, bold value in brand blue.

### DataRow
```jsx
<DataRow label="TGV to Paris" value="2 hours" />
```
Flex justified, label in light gray, value in dark text with medium weight.

### Info Box (brand blue)
```jsx
<div className="rounded-lg p-4" style={{ backgroundColor: brandColors.lightBlue }}>
  <h4 className="font-semibold mb-2" style={{ color: brandColors.blue }}>Title</h4>
  {/* content */}
</div>
```

### Callout Box (gold/warning style)
```jsx
<div className="rounded-lg p-3 border" style={{ backgroundColor: '#FEF3E2', borderColor: '#f5d9a8' }}>
  <p className="text-sm"><span className="font-medium" style={{ color: brandColors.gold }}>Considerations:</span> Content...</p>
</div>
```

### Highlight Box (themed - emerald, amber, teal, etc.)
```jsx
<div className="rounded-lg p-4 mb-3 border bg-emerald-50 border-emerald-100">
  <h4 className="font-semibold mb-2 text-emerald-800">Title</h4>
  <div className="text-sm text-emerald-700 space-y-1">
    {/* content */}
  </div>
</div>
```

## Technical Requirements

### Dependencies
```jsx
import React, { useState } from 'react';
import { Mountain, Sun, Users, Grape, MapPin, TrendingUp, Heart, BookOpen, 
         ChevronDown, ChevronUp, Utensils, TreePine, Thermometer, Building2, 
         Languages, Music, Anchor, Wine, Waves } from 'lucide-react';
```

### Logo Embedding Process
The logo file is WebP format despite .png extension. Must convert before embedding:

```bash
# 1. Convert WebP to actual PNG
convert /mnt/user-data/uploads/relo2france_updated_logo.png /home/claude/logo.png

# 2. Generate base64
base64 -w 0 /home/claude/logo.png > /home/claude/logo_base64.txt

# 3. Embed in JSX (use node script)
node -e "
const fs = require('fs');
const logo = fs.readFileSync('/home/claude/logo_base64.txt', 'utf8');
let jsx = fs.readFileSync('/home/claude/[report].jsx', 'utf8');
jsx = jsx.replace('const logo = \"LOGO_PLACEHOLDER\";', 
                  \`const logo = \"data:image/png;base64,\${logo}\";\`);
fs.writeFileSync('/mnt/user-data/outputs/[report].jsx', jsx);
"
```

### File Output
- Save final JSX to: `/mnt/user-data/outputs/[location]-overview.jsx`
- Use present_files tool to deliver to user

## Content Guidelines

### Tone
- Informative but accessible
- Practical focus for relocators
- Balanced presentation (include considerations/challenges)
- Specific data points over vague descriptions

### Data Sources
- INSEE for demographics and economics
- Official regional/departmental websites
- Government sources (service-public.fr)
- Include source attribution in footer

### Accuracy
- Use recent data (2024-2025 where available)
- Note when estimates or approximations
- Include context for statistics (comparisons to national averages)

## Example Prompt Usage

```
Create a relo2france branded overview report for [Dordogne Department / Brittany Region / etc.].

Follow the relo2france report template with:
- Full logo embedding
- All 10 standard sections
- Stat cards and data tables
- Brand color scheme throughout
- Practical relocation focus

Output as React JSX artifact.
```

## Variations

### For Departments
- Include department number prominently
- Reference parent region
- Focus on préfecture and sous-préfectures
- Include arrondissement breakdown

### For Regions
- Cover all constituent departments
- Focus on regional capital
- Include inter-departmental comparisons
- Address regional governance (Conseil régional)

### For Cities
- Neighborhood/arrondissement focus
- Detailed transport and amenities
- Cost of living specifics
- Expat community information
