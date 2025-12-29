# Schengen Tracker Mobile API Reference

**Base URL**: `https://your-site.com/wp-json/r2f-schengen/v1`

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
    "multi_jurisdiction": false
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

## Rate Limits

- Standard rate limit: 60 requests/minute
- Sync endpoint: 10 requests/minute
- Location batch: 30 requests/minute
