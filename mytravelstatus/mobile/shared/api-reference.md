# MyTravelStatus Mobile API Reference

**Base URL**: `https://your-site.com/wp-json/mts/v1`

**Authentication**: WordPress cookie auth or JWT token

---

## App Status (Public)

### GET `/app/status`

Check app version requirements and maintenance status.

**Parameters** (query):
- `version` (optional): Client app version
- `platform` (optional): `ios` or `android`

**Response**:
```json
{
  "min_version": "1.0.0",
  "latest_version": "1.2.0",
  "force_update": false,
  "maintenance_mode": false,
  "maintenance_message": null,
  "update_url": "https://apps.apple.com/...",
  "server_time": "2025-12-29T12:00:00+00:00",
  "features": {
    "background_gps": true,
    "photo_import": true,
    "calendar_sync": true,
    "family_tracking": true,
    "multi_jurisdiction": true
  }
}
```

---

## Sync

### POST `/sync`

Batch sync local changes and retrieve server changes.

**Request Body**:
```json
{
  "last_sync": "2025-12-28T00:00:00Z",
  "device_id": "unique-device-id",
  "changes": [
    {
      "type": "trip",
      "action": "create",
      "local_id": "temp-123",
      "data": {
        "start_date": "2025-12-01",
        "end_date": "2025-12-05",
        "country": "France",
        "category": "personal",
        "notes": "Paris vacation"
      }
    },
    {
      "type": "location",
      "action": "create",
      "data": {
        "lat": 48.8566,
        "lng": 2.3522,
        "accuracy": 10.5,
        "country_code": "FR",
        "country_name": "France",
        "city": "Paris",
        "is_schengen": true,
        "recorded_at": "2025-12-01T10:30:00Z"
      }
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "sync_results": [
    {
      "local_id": "temp-123",
      "server_id": 456,
      "success": true,
      "action": "created"
    }
  ],
  "server_changes": [
    {
      "type": "trip",
      "action": "update",
      "data": { ... },
      "updated_at": "2025-12-28T15:00:00Z"
    }
  ],
  "conflicts": [],
  "server_time": "2025-12-29T12:00:00+00:00"
}
```

### GET `/changes`

Get server changes since a timestamp.

**Parameters** (query):
- `since` (required): ISO 8601 timestamp

**Response**:
```json
{
  "changes": [
    {
      "type": "trip",
      "action": "update",
      "data": { ... },
      "updated_at": "2025-12-28T15:00:00Z"
    }
  ],
  "server_time": "2025-12-29T12:00:00+00:00"
}
```

---

## Passport Control Mode

### GET `/passport-control`

Get optimized data for border crossing display.

**Response**:
```json
{
  "is_compliant": true,
  "days_used": 42,
  "days_allowed": 90,
  "days_remaining": 48,
  "status": "safe",
  "window_start": "2025-06-15",
  "window_end": "2025-12-12",
  "recent_trips": [
    {
      "country": "France",
      "start_date": "2025-11-15",
      "end_date": "2025-12-12",
      "days": 28
    },
    {
      "country": "Spain",
      "start_date": "2025-10-01",
      "end_date": "2025-10-08",
      "days": 8
    }
  ],
  "last_verified": "2025-12-29T12:00:00+00:00"
}
```

**Status Values**:
- `safe`: < 60 days used (green)
- `warning`: 60-79 days used (yellow)
- `danger`: 80-89 days used (orange)
- `critical`: 90+ days used (red)

---

## Device Registration

### POST `/device/register`

Register a device for push notifications.

**Request Body**:
```json
{
  "device_id": "unique-device-id",
  "push_token": "apns-or-fcm-token",
  "platform": "ios",
  "app_version": "1.0.0",
  "device_name": "iPhone 15 Pro"
}
```

**Response**:
```json
{
  "success": true,
  "device_id": "unique-device-id",
  "message": "Device registered successfully."
}
```

### POST `/device/unregister`

Unregister a device.

**Request Body**:
```json
{
  "device_id": "unique-device-id"
}
```

---

## Batch Location Upload

### POST `/locations/batch`

Upload multiple location readings at once.

**Request Body**:
```json
{
  "locations": [
    {
      "lat": 48.8566,
      "lng": 2.3522,
      "accuracy": 10.5,
      "country_code": "FR",
      "country_name": "France",
      "city": "Paris",
      "is_schengen": true,
      "recorded_at": "2025-12-01T08:00:00Z"
    },
    {
      "lat": 48.8566,
      "lng": 2.3522,
      "accuracy": 15.0,
      "country_code": "FR",
      "country_name": "France",
      "city": "Paris",
      "is_schengen": true,
      "recorded_at": "2025-12-01T14:00:00Z"
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "inserted": 2,
  "total": 2,
  "errors": []
}
```

---

## Existing Endpoints (Available for Mobile)

These endpoints from the main API are also available:

### Trips

- `GET /trips` - Get all trips
- `POST /trips` - Create a trip
- `GET /trips/{id}` - Get single trip
- `PUT /trips/{id}` - Update trip
- `DELETE /trips/{id}` - Delete trip
- `GET /trips/export` - Export trips as CSV
- `POST /trips/import` - Import trips from CSV (premium)

### Summary

- `GET /summary` - Get compliance summary

### Location

- `POST /schengen/location` - Store location check-in
- `GET /schengen/location/today` - Get today's status
- `GET /schengen/location/history` - Get location history
- `GET /schengen/location/detect` - Detect country from IP

### Family (Premium)

- `GET /family` - Get family members
- `POST /family` - Add family member
- `GET /family/{id}/summary` - Get member's compliance

### Suggestions (Premium)

- `GET /suggestions` - Get AI trip suggestions

### Analytics (Premium)

- `GET /analytics` - Get analytics data

---

## Error Responses

All endpoints may return errors in this format:

```json
{
  "code": "error_code",
  "message": "Human-readable error message",
  "data": {
    "status": 400
  }
}
```

**Common Error Codes**:
- `rest_forbidden` (401) - Not authenticated
- `forbidden` (403) - Not authorized
- `not_found` (404) - Resource not found
- `invalid_params` (400) - Invalid parameters
- `conflict` (409) - Sync conflict detected

---

## Multi-Jurisdiction (v1.8.2+)

### GET `/jurisdictions/rules`

Get all available jurisdiction rules.

**Parameters** (query):
- `type` (optional): `zone`, `country`, or `state`
- `category` (optional): `visa`, `tax`, or `residency`

**Response**:
```json
[
  {
    "id": 1,
    "code": "schengen_visa",
    "name": "Schengen 90/180",
    "type": "zone",
    "category": "visa",
    "days_allowed": 90,
    "window_days": 180,
    "counting_method": "rolling",
    "flag_emoji": "🇪🇺",
    "description": "Non-EU nationals can stay up to 90 days in any 180-day period.",
    "is_system": true,
    "is_active": true
  },
  {
    "id": 2,
    "code": "uk_srt",
    "name": "UK Statutory Residence Test",
    "type": "country",
    "category": "tax",
    "days_allowed": 183,
    "window_days": 365,
    "counting_method": "uk_srt",
    "flag_emoji": "🇬🇧",
    "rule_config": {
      "auto_overseas_days": 16,
      "auto_uk_days": 183,
      "tie_thresholds": { ... }
    }
  }
]
```

### GET `/jurisdictions/tracked`

Get user's tracked jurisdictions.

**Response**:
```json
{
  "jurisdictions": ["schengen_visa", "uk_srt", "us_spt"]
}
```

### PUT `/jurisdictions/tracked`

Update user's tracked jurisdictions.

**Request Body**:
```json
{
  "jurisdictions": ["schengen_visa", "uk_srt", "ireland_183"]
}
```

**Response**:
```json
{
  "success": true,
  "jurisdictions": ["schengen_visa", "uk_srt", "ireland_183"]
}
```

### GET `/jurisdictions/summary`

Get summaries for all tracked jurisdictions.

**Response**:
```json
{
  "schengen_visa": {
    "jurisdiction_code": "schengen_visa",
    "jurisdiction_name": "Schengen 90/180",
    "flag_emoji": "🇪🇺",
    "days_used": 45,
    "days_allowed": 90,
    "days_remaining": 45,
    "percentage": 50.0,
    "status": "ok",
    "window_start": "2025-06-15",
    "window_end": "2025-12-12",
    "counting_method": "rolling",
    "trip_count": 3
  },
  "uk_srt": {
    "jurisdiction_code": "uk_srt",
    "jurisdiction_name": "UK Statutory Residence Test",
    "flag_emoji": "🇬🇧",
    "days_used": 120,
    "days_allowed": 183,
    "days_remaining": 63,
    "percentage": 65.6,
    "status": "warning",
    "window_start": "2025-01-01",
    "window_end": "2025-12-31",
    "counting_method": "uk_srt",
    "trip_count": 8,
    "uk_srt_breakdown": {
      "result": "non_resident",
      "days_in_uk": 120,
      "auto_overseas": { "passed": false },
      "auto_uk": { "passed": false },
      "sufficient_ties": {
        "resident": false,
        "tie_count": 2,
        "tie_breakdown": {
          "family": false,
          "accommodation": true,
          "work": false,
          "ninety_day": true,
          "country": false
        },
        "day_threshold": 120
      }
    }
  }
}
```

### GET `/jurisdictions/overview`

Get compliance overview across all tracked jurisdictions.

**Response**:
```json
{
  "total_jurisdictions": 4,
  "critical_count": 0,
  "warning_count": 1,
  "ok_count": 3,
  "exceeded_count": 0,
  "summaries": [
    { ... jurisdiction summary objects ... }
  ]
}
```

### GET `/jurisdictions/{code}/summary`

Get summary for a specific jurisdiction.

**Response**: Same as individual summary in `/jurisdictions/summary`.

---

## PDF Reports (v1.8.2+)

### POST `/schengen/reports/generate`

Generate a PDF compliance report.

**Request Body**:
```json
{
  "period_start": "2025-01-01",
  "period_end": "2025-12-31",
  "jurisdictions": ["schengen_visa", "uk_srt"],
  "include_trips": true,
  "include_qr": true
}
```

**Response**:
```json
{
  "report_id": "MTS-2025-ABC123",
  "file_path": "/uploads/mts-reports/...",
  "file_url": "https://site.com/wp-content/uploads/...",
  "hash": "sha256:abc123...",
  "generated": "2025-12-29T12:00:00+00:00",
  "period": {
    "start": "2025-01-01",
    "end": "2025-12-31"
  }
}
```

### GET `/schengen/reports/{report_id}/download`

Get download URL for a report.

**Response**:
```json
{
  "report_id": "MTS-2025-ABC123",
  "file_url": "https://site.com/wp-content/uploads/...",
  "filename": "MTS-2025-ABC123.pdf"
}
```

### GET `/schengen/reports/{report_id}/verify` (Public)

Verify report authenticity using QR code.

**Response**:
```json
{
  "report_id": "MTS-2025-ABC123",
  "user_id": 123,
  "period_start": "2025-01-01",
  "period_end": "2025-12-31",
  "created_at": "2025-12-29T12:00:00+00:00",
  "file_exists": true,
  "hash_valid": true,
  "verified": true
}
```

---

## Counting Methods

| Method | Description |
|--------|-------------|
| `rolling` | Rolling window (e.g., Schengen 90/180) |
| `calendar_year` | Reset January 1st (e.g., 183-day tax rules) |
| `fiscal_year` | Reset on fiscal year boundary |
| `multi_year` | Multi-year combined test (e.g., Ireland) |
| `weighted_multi_year` | US Substantial Presence Test |
| `uk_srt` | UK Statutory Residence Test |

---

## Status Values

| Status | Percentage | Description |
|--------|------------|-------------|
| `ok` | < 70% | Safe, plenty of days remaining |
| `warning` | 70-84% | Approaching threshold |
| `critical` | 85-99% | Very close to limit |
| `exceeded` | 100%+ | Over the limit |

---

## Rate Limits

- Standard rate limit: 60 requests/minute
- Sync endpoint: 10 requests/minute
- Location batch: 30 requests/minute
- PDF generation: 5 requests/minute
