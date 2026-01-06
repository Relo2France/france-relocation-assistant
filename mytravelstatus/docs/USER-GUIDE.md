# MyTravelStatus User Guide

**Version 1.8.3** | Track Your Travel Days Worldwide

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Tracking Jurisdictions](#tracking-jurisdictions)
3. [Adding Trips](#adding-trips)
4. [Understanding the Dashboard](#understanding-the-dashboard)
5. [Multi-Jurisdiction Tracking](#multi-jurisdiction-tracking)
6. [UK Statutory Residence Test](#uk-statutory-residence-test)
7. [US Substantial Presence Test](#us-substantial-presence-test)
8. [Alerts & Notifications](#alerts--notifications)
9. [PDF Reports](#pdf-reports)
10. [Mobile Apps & Widgets](#mobile-apps--widgets)
11. [FAQ](#faq)

---

## Getting Started

MyTravelStatus helps you track your travel days to stay compliant with visa, tax, and residency rules across multiple jurisdictions.

### First Steps

1. **Access the Dashboard** - Navigate to the MyTravelStatus page on your site
2. **Add Your First Trip** - Enter your travel dates and destination country
3. **Enable Additional Jurisdictions** - Click "Manage Tracked Jurisdictions" to add more rules

### Supported Rules

- **Schengen 90/180** - Visa-free travel limit for the Schengen Area
- **UK SRT** - UK Statutory Residence Test (complex)
- **US SPT** - US Substantial Presence Test (weighted 3-year)
- **183-Day Tax Rules** - France, Spain, Portugal, Germany, Ireland, etc.
- **Custom Rules** - Configure jurisdiction-specific tracking

---

## Tracking Jurisdictions

### Enabling a Jurisdiction

1. Click the **"Manage Tracked Jurisdictions"** button on the dashboard
2. Select a category tab: **Visa Rules**, **Tax Residency**, or **Immigration**
3. Check the box next to each rule you want to track
4. Click **Save Changes**

### Jurisdiction Categories

| Category | Purpose | Examples |
|----------|---------|----------|
| **Visa Rules** | Track visa-free stay limits | Schengen 90/180, UK Visitor 180 |
| **Tax Residency** | Avoid triggering tax obligations | France 183-day, US SPT |
| **Immigration** | Residency permit requirements | Canada ties, Australia domicile |

### Removing a Jurisdiction

You can disable any jurisdiction except Schengen (the primary rule). To remove:
1. Open "Manage Tracked Jurisdictions"
2. Uncheck the jurisdiction
3. Click **Save Changes**

---

## Adding Trips

### Manual Entry

1. Fill in the **Country** dropdown
2. Select **Start Date** and **End Date**
3. Optionally add **Notes** (e.g., "Business trip")
4. Click **Add Trip**

### Trip Data Sources

MyTravelStatus supports multiple ways to log trips:

| Source | How It Works |
|--------|--------------|
| **Manual** | You enter trips directly |
| **GPS Auto** | Mobile app detects country (opt-in) |
| **Calendar** | Import from Google/Microsoft Calendar |
| **Photo** | Extract location from photo EXIF data |

### Editing & Deleting Trips

- Click on any trip to view details
- Use **Edit** to change dates or country
- Use **Delete** to remove the trip
- Changes automatically update all jurisdiction calculations

---

## Understanding the Dashboard

### Compliance Overview

When tracking multiple jurisdictions, the dashboard shows a **Compliance Overview** with:

- **Status Cards** - One card per tracked jurisdiction
- **Progress Bars** - Visual percentage of days used
- **Status Badges** - OK (green), Warning (yellow), Critical (orange), Exceeded (red)

### Status Levels

| Status | Percentage | Meaning |
|--------|------------|---------|
| **OK** | < 70% | Safe, no action needed |
| **Warning** | 70-84% | Approaching limit |
| **Critical** | 85-94% | Very close to limit |
| **Exceeded** | 95%+ or over | At or past limit |

### Day Counting Methods

Different jurisdictions count days differently:

- **Rolling Window** (Schengen) - Any 180-day period
- **Calendar Year** (France Tax) - Jan 1 - Dec 31
- **UK Tax Year** - April 6 - April 5
- **Weighted Multi-Year** (US SPT) - 3-year formula

---

## Multi-Jurisdiction Tracking

### Why Track Multiple Jurisdictions?

If you travel frequently, you may be subject to multiple rules:

- **US Citizens in France** - Track both Schengen (visa) and France Tax (183-day)
- **UK Visitors** - Track UK 180-day informal limit and UK SRT for tax
- **Digital Nomads** - Track multiple country tax residency rules

### Jurisdiction Priority

The dashboard sorts jurisdictions by urgency:
1. **Exceeded** - Immediate attention needed
2. **Critical** - Action required soon
3. **Warning** - Monitor closely
4. **OK** - No action needed

---

## UK Statutory Residence Test

The UK SRT is the most complex test we support. It determines UK tax residency through three stages:

### 1. Automatic Overseas Tests

If **any** of these are true, you're automatically **non-resident**:
- Previously resident + less than 16 days in UK
- Not previously resident + less than 46 days in UK
- Left UK permanently + less than 16 days

### 2. Automatic UK Tests

If **any** of these are true, you're automatically **UK resident**:
- 183+ days in UK
- Only home is in UK
- Full-time UK work (365+ days with no significant break)

### 3. Sufficient Ties Test

If neither automatic test applies, count your UK ties:

| Tie | Description |
|-----|-------------|
| **Family** | Spouse/partner or minor child in UK |
| **Accommodation** | UK accommodation available 91+ days |
| **Work** | 40+ days substantive UK work |
| **90-Day** | 90+ days in UK in either of previous 2 years |
| **Country** | UK is country where you spent most days |

**Tie Thresholds:**

| Days in UK | Previously Resident | Not Previously Resident |
|------------|---------------------|------------------------|
| 16-45 | 4+ ties = resident | N/A |
| 46-90 | 3+ ties = resident | 4+ ties = resident |
| 91-120 | 2+ ties = resident | 3+ ties = resident |
| 121-182 | 1+ tie = resident | 2+ ties = resident |

### Updating Your UK Ties

1. Click **"View Details"** on the UK SRT card
2. Answer the ties questionnaire
3. Click **Save**
4. View your complete SRT result breakdown

---

## US Substantial Presence Test

The US SPT uses a weighted 3-year formula:

```
Current year days × 1.0
+ Prior year days × 1/3
+ 2nd prior year days × 1/6
= Weighted total (≥183 = US tax resident)
```

### Requirements

- Must have 31+ days in current year
- Weighted total must be 183+ to trigger residency

### Example

```
2026: 120 days × 1.0 = 120
2025:  90 days × 0.333 = 30
2024:  60 days × 0.167 = 10
Total: 160 (under 183 = NOT resident)
```

### SPT Display

The dashboard shows:
- Year-by-year breakdown
- Weighted calculation for each year
- Total weighted days
- Threshold comparison (183)

---

## Alerts & Notifications

### Email Alerts

Enable email alerts to receive notifications when:
- You reach 70% of any tracked limit (**Warning**)
- You reach 85% of any tracked limit (**Critical**)
- You reach 95% of any tracked limit (**Urgent**)

### Push Notifications

If you use the mobile app, you'll receive push notifications for:
- Approaching thresholds
- Automatic location updates (if enabled)
- Sync status updates

### Alert Settings

1. Scroll to **Alert Settings** on the dashboard
2. Check **"Email me when approaching my limit"**
3. Click **Save Settings**

Alerts are sent once per threshold level, with a 7-day cooldown to prevent spam.

---

## PDF Reports

Generate professional, audit-ready PDF reports for:
- Tax advisors
- Immigration officials
- Personal records

### Report Contents

- **Compliance Summary** - All jurisdictions with status
- **Detailed Analysis** - Per-jurisdiction rule explanation
- **Trip Log** - Complete list with dates and data sources
- **Calculation Methodology** - How days are counted
- **Legal Disclaimer** - Not legal/tax advice notice
- **Verification** - SHA-256 hash and QR code

### Generating a Report

1. Click the **PDF icon** on the dashboard
2. Select the **reporting period**
3. Choose **jurisdictions to include**
4. Click **Generate Report**

Reports include a verification URL and QR code for authenticity confirmation.

---

## Mobile Apps & Widgets

### iOS App

- Download from App Store (search "MyTravelStatus")
- Features: Trip logging, GPS detection, calendar import, photo import
- Widget: Add to home screen for at-a-glance compliance status

### Android App

- Download from Google Play (search "MyTravelStatus")
- Features: Same as iOS
- Widget: Add compliance widget to home screen

### Home Screen Widgets

Widgets are available in three sizes:

| Size | Contents |
|------|----------|
| **Small** (2×2) | Primary jurisdiction with progress ring |
| **Medium** (4×2) | Primary + list of other jurisdictions |
| **Large** (4×4) | Grid view of up to 4 jurisdictions |

Widgets update every 30 minutes or when you open the app.

### Background Location (Optional)

The mobile app can automatically detect country changes:
- **OFF by default** - Must be explicitly enabled
- Checks 3 times daily (8 AM, 2 PM, 8 PM local time)
- Stores country name only, not precise coordinates
- View educational screen before enabling

---

## FAQ

### Q: How are partial days counted?
**A:** The day you arrive and the day you depart both count as full days in most jurisdictions. This is the most conservative approach.

### Q: Can I import historical trips?
**A:** Yes! You can manually add trips from any date in the past. The system will recalculate all jurisdictions based on the new data.

### Q: What happens if I exceed a limit?
**A:** The system will show "Exceeded" status. This is for your awareness only - we don't report anything to authorities. Consult a professional for advice.

### Q: Are my trips private?
**A:** Yes. Your trip data is stored in your WordPress user account and is not shared with third parties. See our Privacy Policy for details.

### Q: Can I track jurisdictions not listed?
**A:** Currently, we support pre-defined jurisdictions. Contact support if you need a specific country or rule added.

### Q: How accurate is GPS auto-detection?
**A:** GPS accuracy varies by device and conditions. We convert coordinates to country level only. When in doubt, manually verify entries.

### Q: Is this legal or tax advice?
**A:** **No.** MyTravelStatus is an informational tool only. Always consult qualified legal and tax professionals for advice specific to your situation.

---

## Support

- **Documentation**: This guide and API reference
- **Email**: support@mytravelstatus.com
- **Website**: https://mytravelstatus.com/help

---

*Last updated: January 2026 | MyTravelStatus v1.8.3*
