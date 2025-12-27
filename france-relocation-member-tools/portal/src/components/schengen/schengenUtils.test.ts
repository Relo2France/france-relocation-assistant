/**
 * Schengen 90/180 Day Rule Calculator Tests
 *
 * Comprehensive tests for the Schengen compliance algorithm.
 */

import { describe, it, expect } from 'vitest';
import type { SchengenTrip, SchengenCountry } from '@/types';
import {
  calculateSchengenDays,
  getSchengenDatesInWindow,
  getSchengenStatus,
  findNextExpiration,
  getSchengenSummary,
  daysBetween,
  getTripDuration,
  wouldTripViolate,
  findEarliestEntryDate,
  findMaxTripLength,
  formatDate,
  formatDateRange,
} from './schengenUtils';

// Helper to create a trip
function createTrip(
  startDate: string,
  endDate: string,
  country: SchengenCountry = 'France'
): SchengenTrip {
  return {
    id: `trip-${Math.random().toString(36).slice(2, 9)}`,
    startDate,
    endDate,
    country,
    category: 'personal',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };
}

// Helper to get date N days ago from reference
function daysAgo(days: number, reference: Date = new Date()): string {
  const date = new Date(reference);
  date.setDate(date.getDate() - days);
  return date.toISOString().split('T')[0];
}

// Helper to get date N days from now
function daysFromNow(days: number, reference: Date = new Date()): string {
  const date = new Date(reference);
  date.setDate(date.getDate() + days);
  return date.toISOString().split('T')[0];
}

describe('calculateSchengenDays', () => {
  it('returns 0 for no trips', () => {
    expect(calculateSchengenDays([])).toBe(0);
  });

  it('counts a single day trip correctly', () => {
    const today = new Date();
    const trips = [createTrip(daysAgo(5, today), daysAgo(5, today))];
    expect(calculateSchengenDays(trips, today)).toBe(1);
  });

  it('counts a multi-day trip correctly (inclusive)', () => {
    const today = new Date();
    // A 7-day trip from 10 days ago to 4 days ago
    const trips = [createTrip(daysAgo(10, today), daysAgo(4, today))];
    expect(calculateSchengenDays(trips, today)).toBe(7);
  });

  it('handles multiple non-overlapping trips', () => {
    const today = new Date();
    const trips = [
      createTrip(daysAgo(30, today), daysAgo(25, today)), // 6 days
      createTrip(daysAgo(20, today), daysAgo(15, today)), // 6 days
    ];
    expect(calculateSchengenDays(trips, today)).toBe(12);
  });

  it('handles overlapping trips without double counting', () => {
    const today = new Date();
    // Two trips that overlap by 3 days
    const trips = [
      createTrip(daysAgo(20, today), daysAgo(10, today)), // 11 days
      createTrip(daysAgo(12, today), daysAgo(5, today)),  // 8 days, overlaps 3
    ];
    // Total unique days: 16 (not 19)
    expect(calculateSchengenDays(trips, today)).toBe(16);
  });

  it('excludes future trips', () => {
    const today = new Date();
    const trips = [
      createTrip(daysAgo(10, today), daysAgo(5, today)),  // Past trip: 6 days
      createTrip(daysFromNow(5, today), daysFromNow(10, today)), // Future trip
    ];
    expect(calculateSchengenDays(trips, today)).toBe(6);
  });

  it('clips trips that extend beyond the window', () => {
    const today = new Date();
    // Trip that started 200 days ago (outside 180-day window)
    const trips = [createTrip(daysAgo(200, today), daysAgo(170, today))];
    // Only 10 days should be counted (days 180-170)
    expect(calculateSchengenDays(trips, today)).toBe(10);
  });

  it('excludes trips entirely outside the window', () => {
    const today = new Date();
    // Trip that ended 181 days ago
    const trips = [createTrip(daysAgo(200, today), daysAgo(181, today))];
    expect(calculateSchengenDays(trips, today)).toBe(0);
  });

  it('handles trip that spans entire 180-day window', () => {
    const today = new Date();
    const trips = [createTrip(daysAgo(200, today), daysAgo(0, today))];
    expect(calculateSchengenDays(trips, today)).toBe(180);
  });

  it('handles trip ending today', () => {
    const today = new Date();
    const trips = [createTrip(daysAgo(5, today), daysAgo(0, today))];
    expect(calculateSchengenDays(trips, today)).toBe(6);
  });
});

describe('getSchengenDatesInWindow', () => {
  it('returns empty set for no trips', () => {
    const dates = getSchengenDatesInWindow([]);
    expect(dates.size).toBe(0);
  });

  it('returns correct dates for a trip', () => {
    const today = new Date();
    const startDate = daysAgo(5, today);
    const endDate = daysAgo(3, today);
    const trips = [createTrip(startDate, endDate)];

    const dates = getSchengenDatesInWindow(trips, today);
    expect(dates.size).toBe(3);
    expect(dates.has(startDate)).toBe(true);
    expect(dates.has(endDate)).toBe(true);
  });
});

describe('getSchengenStatus', () => {
  it('returns safe for low usage', () => {
    expect(getSchengenStatus(0)).toBe('safe');
    expect(getSchengenStatus(30)).toBe('safe');
    expect(getSchengenStatus(59)).toBe('safe');
  });

  it('returns warning at yellow threshold', () => {
    expect(getSchengenStatus(60)).toBe('warning');
    expect(getSchengenStatus(79)).toBe('warning');
  });

  it('returns danger at red threshold', () => {
    expect(getSchengenStatus(80)).toBe('danger');
    expect(getSchengenStatus(89)).toBe('danger');
  });

  it('returns critical at or above 90 days', () => {
    expect(getSchengenStatus(90)).toBe('critical');
    expect(getSchengenStatus(100)).toBe('critical');
  });

  it('respects custom thresholds', () => {
    const thresholds = { yellow: 50, red: 70 };
    expect(getSchengenStatus(49, thresholds)).toBe('safe');
    expect(getSchengenStatus(50, thresholds)).toBe('warning');
    expect(getSchengenStatus(70, thresholds)).toBe('danger');
  });
});

describe('findNextExpiration', () => {
  it('returns null for no trips', () => {
    expect(findNextExpiration([])).toBeNull();
  });

  it('calculates expiration correctly for single trip', () => {
    const today = new Date();
    const tripDate = daysAgo(10, today);
    const trips = [createTrip(tripDate, tripDate)];

    const expiration = findNextExpiration(trips, today);
    expect(expiration).not.toBeNull();

    // The expiration should be 180 days after the trip date
    const expectedExpiration = new Date(tripDate);
    expectedExpiration.setDate(expectedExpiration.getDate() + 180);
    expect(expiration).toBe(expectedExpiration.toISOString().split('T')[0]);
  });

  it('returns earliest expiration for multiple trips', () => {
    const today = new Date();
    const trips = [
      createTrip(daysAgo(30, today), daysAgo(25, today)), // Earlier dates
      createTrip(daysAgo(10, today), daysAgo(5, today)),  // Later dates
    ];

    const expiration = findNextExpiration(trips, today);
    // Should be 180 days after the earliest date (30 days ago)
    const expectedDate = daysAgo(30, today);
    const expectedExpiration = new Date(expectedDate);
    expectedExpiration.setDate(expectedExpiration.getDate() + 180);
    expect(expiration).toBe(expectedExpiration.toISOString().split('T')[0]);
  });
});

describe('getSchengenSummary', () => {
  it('returns correct summary for no trips', () => {
    const summary = getSchengenSummary([]);
    expect(summary.daysUsed).toBe(0);
    expect(summary.daysRemaining).toBe(90);
    expect(summary.status).toBe('safe');
    expect(summary.nextExpiration).toBeNull();
  });

  it('returns correct summary with trips', () => {
    const today = new Date();
    const trips = [createTrip(daysAgo(10, today), daysAgo(5, today))]; // 6 days

    const summary = getSchengenSummary(trips);
    expect(summary.daysUsed).toBe(6);
    expect(summary.daysRemaining).toBe(84);
    expect(summary.status).toBe('safe');
  });

  it('calculates window dates correctly', () => {
    const summary = getSchengenSummary([]);
    const today = new Date();
    const expectedWindowStart = new Date(today);
    expectedWindowStart.setDate(expectedWindowStart.getDate() - 179);

    expect(summary.windowEnd).toBe(today.toISOString().split('T')[0]);
    expect(summary.windowStart).toBe(expectedWindowStart.toISOString().split('T')[0]);
  });
});

describe('daysBetween', () => {
  it('returns 1 for same day', () => {
    const date = new Date('2024-01-15');
    expect(daysBetween(date, date)).toBe(1);
  });

  it('returns correct count for date range', () => {
    const start = new Date('2024-01-01');
    const end = new Date('2024-01-07');
    expect(daysBetween(start, end)).toBe(7);
  });

  it('works regardless of order', () => {
    const date1 = new Date('2024-01-01');
    const date2 = new Date('2024-01-10');
    expect(daysBetween(date1, date2)).toBe(10);
    expect(daysBetween(date2, date1)).toBe(10);
  });
});

describe('getTripDuration', () => {
  it('returns 1 for same-day trip', () => {
    const trip = createTrip('2024-01-15', '2024-01-15');
    expect(getTripDuration(trip)).toBe(1);
  });

  it('returns correct duration for multi-day trip', () => {
    const trip = createTrip('2024-01-01', '2024-01-07');
    expect(getTripDuration(trip)).toBe(7);
  });
});

describe('wouldTripViolate', () => {
  it('allows trip when no existing trips', () => {
    const result = wouldTripViolate([], '2024-06-01', '2024-06-30');
    expect(result.wouldViolate).toBe(false);
    expect(result.projectedDaysUsed).toBe(30);
  });

  it('allows trip within limit', () => {
    const today = new Date();
    const existingTrips = [createTrip(daysAgo(30, today), daysAgo(1, today))]; // 30 days

    const newStart = daysFromNow(1, today);
    const newEnd = daysFromNow(30, today); // 30 more days = 60 total

    const result = wouldTripViolate(existingTrips, newStart, newEnd);
    expect(result.wouldViolate).toBe(false);
  });

  it('detects violation when exceeding 90 days', () => {
    const today = new Date();
    const existingTrips = [createTrip(daysAgo(60, today), daysAgo(1, today))]; // 60 days

    const newStart = daysFromNow(1, today);
    const newEnd = daysFromNow(40, today); // 40 more days = 100 total > 90

    const result = wouldTripViolate(existingTrips, newStart, newEnd);
    expect(result.wouldViolate).toBe(true);
    expect(result.projectedDaysUsed).toBeGreaterThan(90);
  });

  it('allows exactly 90 days', () => {
    const today = new Date();
    const existingTrips = [createTrip(daysAgo(50, today), daysAgo(1, today))]; // 50 days

    const newStart = daysFromNow(1, today);
    const newEnd = daysFromNow(40, today); // 40 more days = 90 total

    const result = wouldTripViolate(existingTrips, newStart, newEnd);
    expect(result.wouldViolate).toBe(false);
    expect(result.projectedDaysUsed).toBeLessThanOrEqual(90);
  });
});

describe('findEarliestEntryDate', () => {
  it('returns immediately for no existing trips', () => {
    const today = new Date();
    const result = findEarliestEntryDate([], 30, today);
    expect(result).toBe(today.toISOString().split('T')[0]);
  });

  it('finds safe entry date when near limit', () => {
    const today = new Date();
    // 85 days used, need to wait for some days to expire
    const existingTrips = [createTrip(daysAgo(85, today), daysAgo(1, today))];

    const result = findEarliestEntryDate(existingTrips, 10, today);
    expect(result).not.toBeNull();
    // The entry date should be in the future
    if (result) {
      expect(new Date(result) >= today).toBe(true);
    }
  });
});

describe('findMaxTripLength', () => {
  it('returns 90 for no existing trips', () => {
    const today = new Date();
    const maxLength = findMaxTripLength([], daysFromNow(1, today));
    expect(maxLength).toBe(90);
  });

  it('returns reduced length when days are used', () => {
    const today = new Date();
    const existingTrips = [createTrip(daysAgo(30, today), daysAgo(1, today))]; // 30 days

    const maxLength = findMaxTripLength(existingTrips, daysFromNow(1, today));
    // Should be around 60 days (90 - 30 already used)
    expect(maxLength).toBeLessThanOrEqual(60);
    expect(maxLength).toBeGreaterThan(50); // Allow some variance
  });

  it('returns 0 when already at limit', () => {
    const today = new Date();
    const existingTrips = [createTrip(daysAgo(90, today), daysAgo(1, today))]; // 90 days

    const maxLength = findMaxTripLength(existingTrips, daysFromNow(1, today));
    expect(maxLength).toBe(0);
  });
});

describe('formatDate', () => {
  it('formats date correctly', () => {
    const result = formatDate('2024-01-15');
    expect(result).toContain('Jan');
    expect(result).toContain('15');
    expect(result).toContain('2024');
  });
});

describe('formatDateRange', () => {
  it('formats single day correctly', () => {
    const result = formatDateRange('2024-01-15', '2024-01-15');
    expect(result).toContain('Jan');
    expect(result).toContain('15');
  });

  it('formats same month range correctly', () => {
    const result = formatDateRange('2024-01-10', '2024-01-20');
    expect(result).toContain('10');
    expect(result).toContain('20');
    expect(result).toContain('2024');
    // Should only show month once
    expect(result.match(/Jan/g)?.length).toBe(1);
  });

  it('formats different month same year correctly', () => {
    const result = formatDateRange('2024-01-10', '2024-02-20');
    expect(result).toContain('Jan');
    expect(result).toContain('Feb');
    expect(result).toContain('2024');
  });

  it('formats different years correctly', () => {
    const result = formatDateRange('2023-12-25', '2024-01-05');
    expect(result).toContain('2023');
    expect(result).toContain('2024');
  });
});

describe('Edge cases', () => {
  it('handles trip on exact window boundary', () => {
    const today = new Date();
    // Trip exactly 179 days ago (last day of window)
    const trips = [createTrip(daysAgo(179, today), daysAgo(179, today))];
    expect(calculateSchengenDays(trips, today)).toBe(1);

    // Trip exactly 180 days ago (should be excluded)
    const trips2 = [createTrip(daysAgo(180, today), daysAgo(180, today))];
    expect(calculateSchengenDays(trips2, today)).toBe(0);
  });

  it('handles multiple trips on same day', () => {
    const today = new Date();
    // Two trips registered for the same day (e.g., different entries)
    const trips = [
      createTrip(daysAgo(10, today), daysAgo(10, today), 'France'),
      createTrip(daysAgo(10, today), daysAgo(10, today), 'Germany'),
    ];
    // Should still count as 1 day, not 2
    expect(calculateSchengenDays(trips, today)).toBe(1);
  });

  it('handles leap year correctly', () => {
    // Feb 29, 2024 exists (leap year)
    const trip = createTrip('2024-02-28', '2024-03-01');
    const duration = getTripDuration(trip);
    expect(duration).toBe(3); // Feb 28, Feb 29, Mar 1
  });
});
