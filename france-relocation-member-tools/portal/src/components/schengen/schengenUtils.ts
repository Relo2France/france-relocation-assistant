/**
 * Schengen 90/180 Day Rule Calculator
 *
 * Core algorithm for tracking Schengen zone compliance.
 * The rule: Maximum 90 days within ANY rolling 180-day period.
 */

import type { SchengenTrip, SchengenSummary, SchengenStatus, SchengenPlanningResult } from '@/types';

const SCHENGEN_MAX_DAYS = 90;
const SCHENGEN_WINDOW_DAYS = 180;

/**
 * Parse a date string as UTC midnight to ensure timezone consistency.
 * This matches the PHP backend which uses UTC for all calculations.
 *
 * @param dateStr - ISO date string (YYYY-MM-DD) or Date object
 * @returns Date object set to UTC midnight
 */
function parseAsUTC(dateStr: string | Date): Date {
  if (dateStr instanceof Date) {
    // If already a Date, normalize to UTC midnight
    const str = dateStr.toISOString().split('T')[0];
    return new Date(str + 'T00:00:00Z');
  }
  // Parse string as UTC midnight
  return new Date(dateStr + 'T00:00:00Z');
}

/**
 * Get today's date as UTC midnight
 */
function getTodayUTC(): Date {
  const now = new Date();
  const str = now.toISOString().split('T')[0];
  return new Date(str + 'T00:00:00Z');
}

/**
 * Format a UTC date to ISO string (YYYY-MM-DD)
 */
function toISODateString(date: Date): string {
  return date.toISOString().split('T')[0];
}

/**
 * Calculate Schengen days in the 180-day window ending on referenceDate
 *
 * Uses UTC dates to ensure consistency with PHP backend calculations.
 *
 * @param trips - Array of trips
 * @param referenceDate - The end date of the 180-day window (default: today UTC)
 * @returns Total days spent in Schengen within the window
 */
export function calculateSchengenDays(
  trips: SchengenTrip[],
  referenceDate: Date = getTodayUTC()
): number {
  const ref = parseAsUTC(referenceDate);

  // Calculate window start (179 days before ref for a 180-day window including ref)
  const windowStart = new Date(ref.getTime() - (SCHENGEN_WINDOW_DAYS - 1) * 24 * 60 * 60 * 1000);

  const daysInWindow = new Set<string>();

  trips.forEach((trip) => {
    const tripStart = parseAsUTC(trip.startDate);
    const tripEnd = parseAsUTC(trip.endDate);

    // Skip future trips
    if (tripStart > ref) return;

    // Clamp trip to window boundaries
    const effectiveStart = tripStart < windowStart ? windowStart : tripStart;
    const effectiveEnd = tripEnd > ref ? ref : tripEnd;

    if (effectiveStart <= effectiveEnd) {
      const current = new Date(effectiveStart);
      while (current <= effectiveEnd) {
        // Use ISO date string as key to avoid duplicates
        daysInWindow.add(toISODateString(current));
        // Add one day in milliseconds (UTC safe)
        current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      }
    }
  });

  return daysInWindow.size;
}

/**
 * Get the set of dates spent in Schengen within the window
 * Uses UTC dates for consistency with backend.
 */
export function getSchengenDatesInWindow(
  trips: SchengenTrip[],
  referenceDate: Date = getTodayUTC()
): Set<string> {
  const ref = parseAsUTC(referenceDate);
  const windowStart = new Date(ref.getTime() - (SCHENGEN_WINDOW_DAYS - 1) * 24 * 60 * 60 * 1000);

  const daysInWindow = new Set<string>();

  trips.forEach((trip) => {
    const tripStart = parseAsUTC(trip.startDate);
    const tripEnd = parseAsUTC(trip.endDate);

    if (tripStart > ref) return;

    const effectiveStart = tripStart < windowStart ? windowStart : tripStart;
    const effectiveEnd = tripEnd > ref ? ref : tripEnd;

    if (effectiveStart <= effectiveEnd) {
      const current = new Date(effectiveStart);
      while (current <= effectiveEnd) {
        daysInWindow.add(toISODateString(current));
        current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      }
    }
  });

  return daysInWindow;
}

/**
 * Determine compliance status based on days used
 */
export function getSchengenStatus(
  daysUsed: number,
  thresholds: { yellow: number; red: number } = { yellow: 60, red: 80 }
): SchengenStatus {
  if (daysUsed >= SCHENGEN_MAX_DAYS) return 'critical';
  if (daysUsed >= thresholds.red) return 'danger';
  if (daysUsed >= thresholds.yellow) return 'warning';
  return 'safe';
}

/**
 * Find when the next day will "expire" (drop off the 180-day window)
 */
export function findNextExpiration(
  trips: SchengenTrip[],
  referenceDate: Date = getTodayUTC()
): string | null {
  const dates = getSchengenDatesInWindow(trips, referenceDate);
  if (dates.size === 0) return null;

  // Sort dates and find the earliest one
  const sortedDates = Array.from(dates).sort();
  const earliestDate = sortedDates[0];

  // Calculate when this date will drop off (180 days after it)
  const expirationDate = parseAsUTC(earliestDate);
  expirationDate.setTime(expirationDate.getTime() + SCHENGEN_WINDOW_DAYS * 24 * 60 * 60 * 1000);

  return toISODateString(expirationDate);
}

/**
 * Generate a full Schengen summary for the dashboard
 */
export function getSchengenSummary(
  trips: SchengenTrip[],
  thresholds: { yellow: number; red: number } = { yellow: 60, red: 80 }
): SchengenSummary {
  const today = getTodayUTC();
  const daysUsed = calculateSchengenDays(trips, today);
  const daysRemaining = Math.max(0, SCHENGEN_MAX_DAYS - daysUsed);

  const windowEnd = today;
  const windowStart = new Date(today.getTime() - (SCHENGEN_WINDOW_DAYS - 1) * 24 * 60 * 60 * 1000);

  return {
    daysUsed,
    daysRemaining,
    windowStart: toISODateString(windowStart),
    windowEnd: toISODateString(windowEnd),
    status: getSchengenStatus(daysUsed, thresholds),
    nextExpiration: findNextExpiration(trips, today),
    statusThresholds: thresholds,
  };
}

/**
 * Calculate days between two dates (inclusive)
 * Uses UTC dates for consistency.
 */
export function daysBetween(startDate: Date | string, endDate: Date | string): number {
  const start = parseAsUTC(startDate);
  const end = parseAsUTC(endDate);

  const diffTime = Math.abs(end.getTime() - start.getTime());
  return Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 for inclusive
}

/**
 * Calculate trip duration in days (inclusive of both start and end)
 */
export function getTripDuration(trip: { startDate: string; endDate: string }): number {
  return daysBetween(trip.startDate, trip.endDate);
}

/**
 * Check if a hypothetical trip would violate the 90-day rule
 * Uses UTC dates for consistency.
 */
export function wouldTripViolate(
  existingTrips: SchengenTrip[],
  newTripStart: string,
  newTripEnd: string
): SchengenPlanningResult {
  const tripStart = parseAsUTC(newTripStart);
  const tripEnd = parseAsUTC(newTripEnd);

  // Check each day of the hypothetical trip
  const current = new Date(tripStart);
  let maxDaysUsed = 0;

  while (current <= tripEnd) {
    // Create a temporary trips array including days up to current
    const hypotheticalTrip: SchengenTrip = {
      id: 'temp',
      startDate: newTripStart,
      endDate: toISODateString(current),
      country: 'France',
      category: 'personal',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };

    const allTrips = [...existingTrips, hypotheticalTrip];
    const daysUsed = calculateSchengenDays(allTrips, current);
    maxDaysUsed = Math.max(maxDaysUsed, daysUsed);

    if (daysUsed > SCHENGEN_MAX_DAYS) {
      return {
        wouldViolate: true,
        projectedDaysUsed: daysUsed,
        earliestSafeEntry: null,
        maxTripLength: null,
        message: `This trip would exceed the 90-day limit on ${toISODateString(current)} with ${daysUsed} days used.`,
      };
    }

    current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
  }

  return {
    wouldViolate: false,
    projectedDaysUsed: maxDaysUsed,
    earliestSafeEntry: newTripStart,
    maxTripLength: daysBetween(newTripStart, newTripEnd),
    message: `This trip is safe. Maximum days used during trip: ${maxDaysUsed}/90.`,
  };
}

/**
 * Find the earliest date a user can enter Schengen for a trip of given length
 * Uses UTC dates for consistency.
 */
export function findEarliestEntryDate(
  trips: SchengenTrip[],
  tripLength: number,
  startSearchDate: Date = getTodayUTC()
): string | null {
  const checkDate = parseAsUTC(startSearchDate);
  const maxSearch = 365; // Don't search more than a year out
  const oneDay = 24 * 60 * 60 * 1000;

  for (let i = 0; i < maxSearch; i++) {
    const hypotheticalEnd = new Date(checkDate.getTime() + (tripLength - 1) * oneDay);

    const result = wouldTripViolate(
      trips,
      toISODateString(checkDate),
      toISODateString(hypotheticalEnd)
    );

    if (!result.wouldViolate) {
      return toISODateString(checkDate);
    }

    checkDate.setTime(checkDate.getTime() + oneDay);
  }

  return null; // Could not find a valid date within search range
}

/**
 * Find the maximum trip length starting on a given date
 * Uses UTC dates for consistency.
 */
export function findMaxTripLength(
  trips: SchengenTrip[],
  startDate: string
): number {
  const start = parseAsUTC(startDate);
  let maxLength = 0;
  const oneDay = 24 * 60 * 60 * 1000;

  // Binary search would be more efficient, but linear is simpler and 90 iterations max
  for (let length = 1; length <= SCHENGEN_MAX_DAYS; length++) {
    const end = new Date(start.getTime() + (length - 1) * oneDay);

    const result = wouldTripViolate(
      trips,
      startDate,
      toISODateString(end)
    );

    if (result.wouldViolate) {
      break;
    }
    maxLength = length;
  }

  return maxLength;
}

/**
 * Generate a unique ID for trips
 */
export function generateTripId(): string {
  return crypto.randomUUID();
}

/**
 * Format a date for display
 */
export function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

/**
 * Format a date range for display
 */
export function formatDateRange(startDate: string, endDate: string): string {
  const start = new Date(startDate);
  const end = new Date(endDate);

  if (startDate === endDate) {
    return formatDate(startDate);
  }

  // Same month and year
  if (
    start.getMonth() === end.getMonth() &&
    start.getFullYear() === end.getFullYear()
  ) {
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.getDate()}, ${end.getFullYear()}`;
  }

  // Same year
  if (start.getFullYear() === end.getFullYear()) {
    return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${end.getFullYear()}`;
  }

  // Different years
  return `${formatDate(startDate)} - ${formatDate(endDate)}`;
}
