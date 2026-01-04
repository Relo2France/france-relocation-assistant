/**
 * Shared Data Models for Schengen Tracker Mobile App
 *
 * These TypeScript interfaces define the data structures shared between
 * the mobile apps (iOS/Android) and the WordPress REST API.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.6.0
 */

// ============================================================================
// Core Models
// ============================================================================

/**
 * A trip to a Schengen country.
 */
export interface Trip {
  id: number;
  userId?: number;
  familyMemberId?: number | null;
  startDate: string; // YYYY-MM-DD
  endDate: string; // YYYY-MM-DD
  country: string;
  jurisdictionCode?: string; // Default: 'schengen'
  category: 'personal' | 'business';
  notes?: string | null;
  locationSource?: LocationSource;
  locationLat?: number | null;
  locationLng?: number | null;
  locationAccuracy?: number | null;
  locationTimestamp?: string | null; // ISO 8601
  createdAt: string; // ISO 8601
  updatedAt: string; // ISO 8601
}

/**
 * Source of location data.
 */
export type LocationSource =
  | 'manual'
  | 'browser'
  | 'mobile_gps'
  | 'calendar'
  | 'photo'
  | 'checkin';

/**
 * A GPS location reading.
 */
export interface LocationReading {
  id: number;
  lat: number;
  lng: number;
  accuracy?: number | null;
  countryCode?: string | null;
  countryName?: string | null;
  city?: string | null;
  isSchengen: boolean;
  source: LocationSource;
  recordedAt: string; // ISO 8601
}

/**
 * A family member for tracking.
 */
export interface FamilyMember {
  id: number;
  userId: number;
  name: string;
  relationship?: string | null;
  nationality?: string | null;
  passportCountry?: string | null;
  dateOfBirth?: string | null; // YYYY-MM-DD
  notes?: string | null;
  color: string; // Hex color
  isActive: boolean;
  displayOrder: number;
  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// Calculation Models
// ============================================================================

/**
 * Status level for compliance.
 */
export type StatusLevel = 'safe' | 'warning' | 'danger' | 'critical';

/**
 * Summary of Schengen/jurisdiction compliance.
 */
export interface JurisdictionSummary {
  jurisdictionId: string;
  jurisdictionName: string;
  daysUsed: number;
  daysAllowed: number;
  daysRemaining: number;
  status: StatusLevel;
  periodStart: string; // YYYY-MM-DD
  periodEnd: string; // YYYY-MM-DD
  lastUpdated: string; // ISO 8601
  notes?: string;
}

/**
 * Data for Passport Control Mode display.
 */
export interface PassportControlData {
  isCompliant: boolean;
  daysUsed: number;
  daysAllowed: number;
  daysRemaining: number;
  status: StatusLevel;
  windowStart: string; // YYYY-MM-DD
  windowEnd: string; // YYYY-MM-DD
  recentTrips: RecentTripSummary[];
  lastVerified: string; // ISO 8601
}

/**
 * Summary of a recent trip for display.
 */
export interface RecentTripSummary {
  country: string;
  startDate: string; // YYYY-MM-DD
  endDate: string; // YYYY-MM-DD
  days: number;
}

// ============================================================================
// Sync Models
// ============================================================================

/**
 * Request for batch sync.
 */
export interface SyncRequest {
  lastSync: string | null; // ISO 8601
  deviceId: string;
  changes: SyncChange[];
}

/**
 * A single change to sync.
 */
export interface SyncChange {
  type: 'trip' | 'location';
  action: 'create' | 'update' | 'delete';
  localId?: string; // Client-side ID for new items
  data: Partial<Trip> | Partial<LocationReading>;
}

/**
 * Response from batch sync.
 */
export interface SyncResponse {
  success: boolean;
  syncResults: SyncResult[];
  serverChanges: ServerChange[];
  conflicts: SyncConflict[];
  serverTime: string; // ISO 8601
}

/**
 * Result of syncing a single change.
 */
export interface SyncResult {
  localId?: string;
  serverId?: number;
  success: boolean;
  action?: 'created' | 'updated' | 'deleted';
  error?: string;
}

/**
 * A change from the server.
 */
export interface ServerChange {
  type: 'trip' | 'location';
  action: 'create' | 'update' | 'delete';
  data: Trip | LocationReading;
  updatedAt: string;
}

/**
 * A sync conflict.
 */
export interface SyncConflict {
  type: 'trip' | 'location';
  id: number;
  localVersion: Partial<Trip> | Partial<LocationReading>;
  serverVersion: Trip | LocationReading;
}

// ============================================================================
// Device & App Models
// ============================================================================

/**
 * Device registration for push notifications.
 */
export interface DeviceRegistration {
  deviceId: string;
  pushToken: string;
  platform: 'ios' | 'android';
  appVersion: string;
  deviceName?: string;
  osVersion?: string;
}

/**
 * App status from server.
 */
export interface AppStatus {
  minVersion: string;
  latestVersion: string;
  forceUpdate: boolean;
  maintenanceMode: boolean;
  maintenanceMessage?: string | null;
  updateUrl?: string | null;
  serverTime: string; // ISO 8601
  features: FeatureFlags;
}

/**
 * Feature flags for the app.
 */
export interface FeatureFlags {
  backgroundGps: boolean;
  photoImport: boolean;
  calendarSync: boolean;
  familyTracking: boolean;
  multiJurisdiction: boolean;
}

// ============================================================================
// Notification Models
// ============================================================================

/**
 * A notification.
 */
export interface Notification {
  id: number;
  type: NotificationType;
  title: string;
  body?: string | null;
  data?: Record<string, unknown>;
  actionUrl?: string | null;
  icon?: string | null;
  priority: 'low' | 'normal' | 'high';
  readAt?: string | null;
  createdAt: string;
}

/**
 * Types of notifications.
 */
export type NotificationType =
  | 'threshold_warning'
  | 'threshold_danger'
  | 'threshold_critical'
  | 'trip_reminder'
  | 'location_confirm'
  | 'sync_reminder'
  | 'calendar_sync_complete'
  | 'system';

// ============================================================================
// Background GPS Models
// ============================================================================

/**
 * Configuration for background GPS tracking.
 */
export interface BackgroundGpsConfig {
  enabled: boolean;
  checkTimes: number[]; // Hours in local time (e.g., [8, 14, 20])
  minAccuracy: number; // Minimum accuracy in meters
  syncInterval: number; // Sync interval in minutes
}

/**
 * A queued location for sync.
 */
export interface QueuedLocation {
  id: string; // Client-side UUID
  lat: number;
  lng: number;
  accuracy: number;
  countryCode?: string;
  countryName?: string;
  city?: string;
  isSchengen: boolean;
  recordedAt: string; // ISO 8601
  syncStatus: 'pending' | 'syncing' | 'synced' | 'failed';
  failureCount: number;
}

/**
 * Day location determination result.
 */
export interface DayLocation {
  date: string; // YYYY-MM-DD
  country: string;
  countryCode: string;
  isSchengen: boolean;
  confidence: 'high' | 'medium' | 'low';
  readings: LocationReading[];
  notes?: string;
}

// ============================================================================
// Photo Import Models
// ============================================================================

/**
 * A detected trip from photos.
 */
export interface DetectedPhotoTrip {
  country: string;
  countryCode: string;
  startDate: string; // YYYY-MM-DD
  endDate: string; // YYYY-MM-DD
  photoCount: number;
  samplePhotoUri?: string;
  isSchengen: boolean;
}

// ============================================================================
// Calendar Models
// ============================================================================

/**
 * A detected trip from calendar.
 */
export interface DetectedCalendarTrip {
  eventId: string;
  title: string;
  startDate: string; // YYYY-MM-DD
  endDate: string; // YYYY-MM-DD
  location?: string;
  detectedCountry?: string;
  detectedCountryCode?: string;
  isSchengen: boolean;
  confidenceScore: number; // 0-1
}

// ============================================================================
// API Response Wrappers
// ============================================================================

/**
 * Standard success response.
 */
export interface ApiSuccessResponse<T> {
  success: true;
  data: T;
  message?: string;
}

/**
 * Standard error response.
 */
export interface ApiErrorResponse {
  success: false;
  code: string;
  message: string;
  status?: number;
}

/**
 * Combined API response type.
 */
export type ApiResponse<T> = ApiSuccessResponse<T> | ApiErrorResponse;

// ============================================================================
// Schengen Countries Reference
// ============================================================================

/**
 * List of Schengen country codes.
 */
export const SCHENGEN_COUNTRY_CODES = [
  'AT', // Austria
  'BE', // Belgium
  'BG', // Bulgaria
  'HR', // Croatia
  'CZ', // Czech Republic
  'DK', // Denmark
  'EE', // Estonia
  'FI', // Finland
  'FR', // France
  'DE', // Germany
  'GR', // Greece
  'HU', // Hungary
  'IS', // Iceland
  'IT', // Italy
  'LV', // Latvia
  'LI', // Liechtenstein
  'LT', // Lithuania
  'LU', // Luxembourg
  'MT', // Malta
  'NL', // Netherlands
  'NO', // Norway
  'PL', // Poland
  'PT', // Portugal
  'RO', // Romania
  'SK', // Slovakia
  'SI', // Slovenia
  'ES', // Spain
  'SE', // Sweden
  'CH', // Switzerland
] as const;

export type SchengenCountryCode = typeof SCHENGEN_COUNTRY_CODES[number];

/**
 * Schengen countries with names.
 */
export const SCHENGEN_COUNTRIES: Record<SchengenCountryCode, string> = {
  AT: 'Austria',
  BE: 'Belgium',
  BG: 'Bulgaria',
  HR: 'Croatia',
  CZ: 'Czech Republic',
  DK: 'Denmark',
  EE: 'Estonia',
  FI: 'Finland',
  FR: 'France',
  DE: 'Germany',
  GR: 'Greece',
  HU: 'Hungary',
  IS: 'Iceland',
  IT: 'Italy',
  LV: 'Latvia',
  LI: 'Liechtenstein',
  LT: 'Lithuania',
  LU: 'Luxembourg',
  MT: 'Malta',
  NL: 'Netherlands',
  NO: 'Norway',
  PL: 'Poland',
  PT: 'Portugal',
  RO: 'Romania',
  SK: 'Slovakia',
  SI: 'Slovenia',
  ES: 'Spain',
  SE: 'Sweden',
  CH: 'Switzerland',
};

/**
 * Check if a country code is in Schengen.
 */
export function isSchengenCountry(code: string): boolean {
  return SCHENGEN_COUNTRY_CODES.includes(code.toUpperCase() as SchengenCountryCode);
}
