# MyTravelStatus API Reference

**Version 1.8.3** | REST API Documentation

---

## Base URL

```
/wp-json/mts/v1/
```

All endpoints require authentication via WordPress cookie auth with X-WP-Nonce header.

---

## Jurisdictions

### List All Jurisdictions

```http
GET /jurisdictions
```

Returns all active jurisdiction rules.

**Response:**
```json
[
  {
    "id": 1,
    "code": "schengen",
    "name": "Schengen Zone",
    "type": "zone",
    "category": "visa",
    "daysAllowed": 90,
    "windowDays": 180,
    "countingMethod": "rolling",
    "description": "Standard 90/180 rule for visa-free travel",
    "flagEmoji": "🇪🇺",
    "isSystem": true,
    "isActive": true
  }
]
```

### Get Single Jurisdiction

```http
GET /jurisdictions/{code}
```

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| code | string | Jurisdiction code (e.g., "schengen", "uk_srt") |

### Get Tracked Jurisdictions

```http
GET /jurisdictions/tracked
```

Returns the current user's tracked jurisdictions.

### Add Tracked Jurisdiction

```http
POST /jurisdictions/tracked
```

**Body:**
```json
{
  "code": "fr_tax"
}
```

### Remove Tracked Jurisdiction

```http
DELETE /jurisdictions/tracked/{code}
```

### Bulk Update Jurisdictions

```http
POST /jurisdictions/bulk
```

**Body:**
```json
{
  "action": "enable",
  "codes": ["fr_tax", "es_tax", "pt_tax"]
}
```

### Get EU Tax Jurisdictions

```http
GET /jurisdictions/eu-tax
```

Returns all EU country tax residency rules for bulk enabling.

---

## Jurisdiction Summaries

### Get Summary for Single Jurisdiction

```http
GET /jurisdictions/{code}/summary
```

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| date | string | Optional reference date (Y-m-d format) |

**Response:**
```json
{
  "days_used": 45,
  "days_allowed": 90,
  "days_remaining": 45,
  "percentage": 50.0,
  "status": "ok",
  "window_start": "2025-07-10",
  "window_end": "2026-01-05",
  "reference_date": "2026-01-06",
  "counting_method": "rolling",
  "trip_count": 3
}
```

### Get Multi-Jurisdiction Summary

```http
GET /jurisdictions/summary
```

Returns summaries for all tracked jurisdictions.

**Response:**
```json
{
  "schengen": {
    "days_used": 45,
    "days_allowed": 90,
    "days_remaining": 45,
    "percentage": 50.0,
    "status": "ok",
    "rule": { ... }
  },
  "fr_tax": {
    "days_used": 120,
    "days_allowed": 183,
    "days_remaining": 63,
    "percentage": 65.6,
    "status": "ok",
    "rule": { ... }
  }
}
```

---

## UK SRT Specific

### Get UK Ties

```http
GET /jurisdictions/uk-srt/ties
```

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| tax_year | int | UK tax year (e.g., 2025 for 2025/26) |

**Response:**
```json
{
  "success": true,
  "taxYear": 2025,
  "taxYearLabel": "2025/26",
  "ties": {
    "family": false,
    "accommodation": true,
    "work": false,
    "ninety_day": true,
    "country": false,
    "prior_year_resident": false,
    "only_home_uk": false,
    "full_time_work_uk": false,
    "leaving_uk": false
  }
}
```

### Update UK Ties

```http
PUT /jurisdictions/uk-srt/ties
```

**Body:**
```json
{
  "tax_year": 2025,
  "ties": {
    "family": false,
    "accommodation": true,
    "work": false,
    "ninety_day": true,
    "country": false
  }
}
```

### Get UK SRT Result

```http
GET /jurisdictions/uk-srt/result
```

**Response:**
```json
{
  "success": true,
  "result": {
    "result": "non_resident",
    "daysInUK": 85,
    "autoOverseas": {
      "passed": false,
      "test": null,
      "description": null
    },
    "autoUK": {
      "passed": false,
      "test": null,
      "description": null
    },
    "sufficientTies": {
      "resident": false,
      "tieCount": 2,
      "tieBreakdown": {
        "family": false,
        "accommodation": true,
        "work": false,
        "ninety_day": true,
        "country": false
      },
      "dayThreshold": 91,
      "daysInUK": 85
    },
    "explanation": "Non-resident under sufficient ties test: 2 ties with 85 UK days requires 3+ ties for residence."
  }
}
```

---

## Multi-Factor Jurisdictions

### Get User Factors

```http
GET /jurisdictions/{code}/factors
```

For jurisdictions with multi-factor rules (Germany, Italy, Netherlands, etc.).

**Response:**
```json
{
  "code": "de_tax",
  "hasFactors": true,
  "factors": [
    {
      "id": "permanent_home",
      "label": "Permanent Home in Germany",
      "description": "Do you maintain a permanent home in Germany?",
      "weight": 1
    }
  ],
  "responses": {
    "permanent_home": false
  },
  "factorLogic": "any"
}
```

### Update User Factors

```http
PUT /jurisdictions/{code}/factors
```

**Body:**
```json
{
  "permanent_home": true,
  "habitual_abode": false
}
```

---

## Trips

### List Trips

```http
GET /trips
```

**Parameters:**
| Name | Type | Description |
|------|------|-------------|
| start_date | string | Filter by start date (Y-m-d) |
| end_date | string | Filter by end date (Y-m-d) |
| country | string | Filter by country name |
| limit | int | Number of results (default 50) |
| offset | int | Pagination offset |

### Create Trip

```http
POST /trips
```

**Body:**
```json
{
  "start_date": "2026-01-10",
  "end_date": "2026-01-15",
  "country": "France",
  "category": "personal",
  "notes": "Business trip"
}
```

### Update Trip

```http
PUT /trips/{id}
```

### Delete Trip

```http
DELETE /trips/{id}
```

---

## Reports

### Generate PDF Report

```http
POST /reports/generate
```

**Body:**
```json
{
  "period_start": "2025-01-01",
  "period_end": "2025-12-31",
  "jurisdictions": ["schengen", "fr_tax"]
}
```

**Response:**
```json
{
  "success": true,
  "report_id": "MTS-2026-001-ABC123",
  "download_url": "/wp-json/mts/v1/reports/MTS-2026-001-ABC123/download"
}
```

### Download Report

```http
GET /reports/{report_id}/download
```

Returns the PDF file.

### Verify Report

```http
GET /reports/{report_id}/verify
```

Public endpoint for report verification.

**Response:**
```json
{
  "valid": true,
  "report_id": "MTS-2026-001-ABC123",
  "generated": "2026-01-06T14:32:00Z",
  "hash": "sha256:a1b2c3d4..."
}
```

---

## Alerts

### Get User Alert Settings

```http
GET /alerts/settings
```

### Update Alert Settings

```http
PUT /alerts/settings
```

**Body:**
```json
{
  "email_alerts": true,
  "push_alerts": true,
  "warning_threshold": 70,
  "critical_threshold": 85
}
```

---

## Mobile Sync

### Sync Data

```http
POST /mobile/sync
```

**Body:**
```json
{
  "lastSync": "2026-01-05T12:00:00Z",
  "deviceId": "device-uuid",
  "changes": [
    {
      "type": "trip",
      "action": "create",
      "localId": "local-uuid",
      "data": { ... }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "syncResults": [
    {
      "localId": "local-uuid",
      "serverId": 123,
      "success": true,
      "action": "created"
    }
  ],
  "serverChanges": [],
  "conflicts": [],
  "serverTime": "2026-01-06T10:00:00Z"
}
```

### Register Device

```http
POST /mobile/devices
```

**Body:**
```json
{
  "deviceId": "device-uuid",
  "pushToken": "apns-or-fcm-token",
  "platform": "ios",
  "appVersion": "1.8.3",
  "deviceName": "iPhone 15"
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `not_found` | 404 | Resource not found |
| `forbidden` | 403 | Permission denied |
| `invalid_param` | 400 | Invalid parameter value |
| `missing_param` | 400 | Required parameter missing |
| `rate_limited` | 429 | Too many requests |

---

## Rate Limiting

- **API calls**: 100 requests per minute per user
- **PDF generation**: 10 reports per hour per user
- **Mobile sync**: 60 syncs per hour per device

---

## Caching

Responses are cached as follows:

| Endpoint | Cache TTL | Notes |
|----------|-----------|-------|
| `/jurisdictions` | 1 hour | Rarely changes |
| `/jurisdictions/summary` | 5 minutes | Invalidated on trip changes |
| `/jurisdictions/tracked` | No cache | User-specific |
| `/trips` | No cache | Dynamic |

---

*Last updated: January 2026 | MyTravelStatus v1.8.3*
