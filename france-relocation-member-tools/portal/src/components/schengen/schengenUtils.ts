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
 * Calculate Schengen days in the 180-day window ending on referenceDate
 *
 * @param trips - Array of trips
 * @param referenceDate - The end date of the 180-day window (default: today)
 * @returns Total days spent in Schengen within the window
 */
export function calculateSchengenDays(
  trips: SchengenTrip[],
  referenceDate: Date = new Date()
): number {
  const ref = new Date(referenceDate);
  ref.setHours(23, 59, 59, 999); // End of day

  const windowStart = new Date(ref);
  windowStart.setDate(windowStart.getDate() - (SCHENGEN_WINDOW_DAYS - 1)); // 180-day window
  windowStart.setHours(0, 0, 0, 0); // Start of day

  const daysInWindow = new Set<string>();

  trips.forEach((trip) => {
    const tripStart = new Date(trip.startDate);
    tripStart.setHours(0, 0, 0, 0);
    const tripEnd = new Date(trip.endDate);
    tripEnd.setHours(23, 59, 59, 999);

    // Skip future trips
    if (tripStart > ref) return;

    // Clamp trip to window boundaries
    const effectiveStart = tripStart < windowStart ? windowStart : tripStart;
    const effectiveEnd = tripEnd > ref ? ref : tripEnd;

    if (effectiveStart <= effectiveEnd) {
      const current = new Date(effectiveStart);
      while (current <= effectiveEnd) {
        // Use ISO date string as key to avoid duplicates
        daysInWindow.add(current.toISOString().split('T')[0]);
        current.setDate(current.getDate() + 1);
      }
    }
  });

  return daysInWindow.size;
}

/**
 * Get the set of dates spent in Schengen within the window
 */
export function getSchengenDatesInWindow(
  trips: SchengenTrip[],
  referenceDate: Date = new Date()
): Set<string> {
  const ref = new Date(referenceDate);
  ref.setHours(23, 59, 59, 999);

  const windowStart = new Date(ref);
  windowStart.setDate(windowStart.getDate() - (SCHENGEN_WINDOW_DAYS - 1));
  windowStart.setHours(0, 0, 0, 0);

  const daysInWindow = new Set<string>();

  trips.forEach((trip) => {
    const tripStart = new Date(trip.startDate);
    tripStart.setHours(0, 0, 0, 0);
    const tripEnd = new Date(trip.endDate);
    tripEnd.setHours(23, 59, 59, 999);

    if (tripStart > ref) return;

    const effectiveStart = tripStart < windowStart ? windowStart : tripStart;
    const effectiveEnd = tripEnd > ref ? ref : tripEnd;

    if (effectiveStart <= effectiveEnd) {
      const current = new Date(effectiveStart);
      while (current <= effectiveEnd) {
        daysInWindow.add(current.toISOString().split('T')[0]);
        current.setDate(current.getDate() + 1);
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
  referenceDate: Date = new Date()
): string | null {
  const dates = getSchengenDatesInWindow(trips, referenceDate);
  if (dates.size === 0) return null;

  // Sort dates and find the earliest one
  const sortedDates = Array.from(dates).sort();
  const earliestDate = sortedDates[0];

  // Calculate when this date will drop off (180 days after it)
  const expirationDate = new Date(earliestDate);
  expirationDate.setDate(expirationDate.getDate() + SCHENGEN_WINDOW_DAYS);

  return expirationDate.toISOString().split('T')[0];
}

/**
 * Generate a full Schengen summary for the dashboard
 */
export function getSchengenSummary(
  trips: SchengenTrip[],
  thresholds: { yellow: number; red: number } = { yellow: 60, red: 80 }
): SchengenSummary {
  const today = new Date();
  const daysUsed = calculateSchengenDays(trips, today);
  const daysRemaining = Math.max(0, SCHENGEN_MAX_DAYS - daysUsed);

  const windowEnd = new Date(today);
  const windowStart = new Date(today);
  windowStart.setDate(windowStart.getDate() - (SCHENGEN_WINDOW_DAYS - 1));

  return {
    daysUsed,
    daysRemaining,
    windowStart: windowStart.toISOString().split('T')[0],
    windowEnd: windowEnd.toISOString().split('T')[0],
    status: getSchengenStatus(daysUsed, thresholds),
    nextExpiration: findNextExpiration(trips, today),
    statusThresholds: thresholds,
  };
}

/**
 * Calculate days between two dates (inclusive)
 */
export function daysBetween(startDate: Date, endDate: Date): number {
  const start = new Date(startDate);
  start.setHours(0, 0, 0, 0);
  const end = new Date(endDate);
  end.setHours(0, 0, 0, 0);

  const diffTime = Math.abs(end.getTime() - start.getTime());
  return Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 for inclusive
}

/**
 * Calculate trip duration in days (inclusive of both start and end)
 */
export function getTripDuration(trip: SchengenTrip): number {
  return daysBetween(new Date(trip.startDate), new Date(trip.endDate));
}

/**
 * Check if a hypothetical trip would violate the 90-day rule
 */
export function wouldTripViolate(
  existingTrips: SchengenTrip[],
  newTripStart: string,
  newTripEnd: string
): SchengenPlanningResult {
  const tripStart = new Date(newTripStart);
  const tripEnd = new Date(newTripEnd);

  // Check each day of the hypothetical trip
  const current = new Date(tripStart);
  let maxDaysUsed = 0;

  while (current <= tripEnd) {
    // Create a temporary trips array including days up to current
    const hypotheticalTrip: SchengenTrip = {
      id: 'temp',
      startDate: newTripStart,
      endDate: current.toISOString().split('T')[0],
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
        message: `This trip would exceed the 90-day limit on ${current.toISOString().split('T')[0]} with ${daysUsed} days used.`,
      };
    }

    current.setDate(current.getDate() + 1);
  }

  return {
    wouldViolate: false,
    projectedDaysUsed: maxDaysUsed,
    earliestSafeEntry: newTripStart,
    maxTripLength: daysBetween(tripStart, tripEnd),
    message: `This trip is safe. Maximum days used during trip: ${maxDaysUsed}/90.`,
  };
}

/**
 * Find the earliest date a user can enter Schengen for a trip of given length
 */
export function findEarliestEntryDate(
  trips: SchengenTrip[],
  tripLength: number,
  startSearchDate: Date = new Date()
): string | null {
  const checkDate = new Date(startSearchDate);
  const maxSearch = 365; // Don't search more than a year out

  for (let i = 0; i < maxSearch; i++) {
    const hypotheticalEnd = new Date(checkDate);
    hypotheticalEnd.setDate(hypotheticalEnd.getDate() + tripLength - 1);

    const result = wouldTripViolate(
      trips,
      checkDate.toISOString().split('T')[0],
      hypotheticalEnd.toISOString().split('T')[0]
    );

    if (!result.wouldViolate) {
      return checkDate.toISOString().split('T')[0];
    }

    checkDate.setDate(checkDate.getDate() + 1);
  }

  return null; // Could not find a valid date within search range
}

/**
 * Find the maximum trip length starting on a given date
 */
export function findMaxTripLength(
  trips: SchengenTrip[],
  startDate: string
): number {
  const start = new Date(startDate);
  let maxLength = 0;

  // Binary search would be more efficient, but linear is simpler and 90 iterations max
  for (let length = 1; length <= SCHENGEN_MAX_DAYS; length++) {
    const end = new Date(start);
    end.setDate(end.getDate() + length - 1);

    const result = wouldTripViolate(
      trips,
      startDate,
      end.toISOString().split('T')[0]
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
