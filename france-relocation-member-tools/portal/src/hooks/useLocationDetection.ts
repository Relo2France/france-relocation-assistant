/**
 * useLocationDetection Hook
 *
 * Detects potential location changes through:
 * - Timezone changes (compared to stored timezone)
 * - Locale/language changes
 * - Provides smart suggestions for check-ins
 *
 * @package R2F_Member_Tools
 * @since   2.2.0
 */

import { useState, useEffect, useCallback, useMemo } from 'react';

/**
 * Map of timezone prefixes to likely Schengen countries
 */
const TIMEZONE_SCHENGEN_MAP: Record<string, string[]> = {
  'Europe/Paris': ['FR'],
  'Europe/Berlin': ['DE'],
  'Europe/Rome': ['IT'],
  'Europe/Madrid': ['ES'],
  'Europe/Amsterdam': ['NL'],
  'Europe/Brussels': ['BE'],
  'Europe/Vienna': ['AT'],
  'Europe/Zurich': ['CH'],
  'Europe/Stockholm': ['SE'],
  'Europe/Oslo': ['NO'],
  'Europe/Copenhagen': ['DK'],
  'Europe/Helsinki': ['FI'],
  'Europe/Warsaw': ['PL'],
  'Europe/Prague': ['CZ'],
  'Europe/Budapest': ['HU'],
  'Europe/Athens': ['GR'],
  'Europe/Lisbon': ['PT'],
  'Europe/Dublin': ['IE'], // Not Schengen but close
  'Europe/London': ['GB'], // Not Schengen
  'Europe/Bucharest': ['RO'],
  'Europe/Sofia': ['BG'],
  'Europe/Zagreb': ['HR'],
  'Europe/Ljubljana': ['SI'],
  'Europe/Bratislava': ['SK'],
  'Europe/Tallinn': ['EE'],
  'Europe/Riga': ['LV'],
  'Europe/Vilnius': ['LT'],
  'Europe/Luxembourg': ['LU'],
  'Europe/Malta': ['MT'],
  'Europe/Reykjavik': ['IS'],
  'Europe/Vaduz': ['LI'],
  'Europe/Monaco': ['MC'], // Uses French timezone typically
};

/**
 * Schengen country codes
 */
const SCHENGEN_CODES = new Set([
  'AT', 'BE', 'BG', 'HR', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
  'GR', 'HU', 'IS', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL',
  'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH'
]);

export interface LocationDetectionState {
  /** Current browser timezone */
  currentTimezone: string;
  /** Stored/last known timezone */
  storedTimezone: string | null;
  /** Whether timezone has changed since last check */
  timezoneChanged: boolean;
  /** Likely country based on timezone */
  likelyCountry: string | null;
  /** Whether likely in Schengen zone based on timezone */
  likelySchengen: boolean;
  /** Current browser locale */
  currentLocale: string;
  /** Whether user should be prompted to check in */
  shouldPromptCheckin: boolean;
  /** Last check-in date (from localStorage) */
  lastCheckinDate: string | null;
  /** Whether checked in today */
  checkedInToday: boolean;
}

export interface UseLocationDetectionOptions {
  /** Enable daily check-in reminder */
  enableReminder?: boolean;
  /** Reminder hour (0-23, default 9 = 9 AM) */
  reminderHour?: number;
}

export interface UseLocationDetectionResult {
  /** Current detection state */
  state: LocationDetectionState;
  /** Mark timezone as acknowledged (store it) */
  acknowledgeTimezone: () => void;
  /** Mark as checked in today */
  markCheckedIn: () => void;
  /** Dismiss the check-in prompt for today */
  dismissPrompt: () => void;
  /** Check if reminder should show based on time */
  isReminderTime: () => boolean;
}

const STORAGE_KEYS = {
  TIMEZONE: 'r2f_schengen_last_timezone',
  LAST_CHECKIN: 'r2f_schengen_last_checkin_date',
  PROMPT_DISMISSED: 'r2f_schengen_prompt_dismissed_date',
};

/**
 * Get current browser timezone
 */
function getCurrentTimezone(): string {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch {
    return 'UTC';
  }
}

/**
 * Get current browser locale
 */
function getCurrentLocale(): string {
  return navigator.language || 'en-US';
}

/**
 * Get country code from timezone
 */
function getCountryFromTimezone(timezone: string): string | null {
  const countries = TIMEZONE_SCHENGEN_MAP[timezone];
  return countries?.[0] || null;
}

/**
 * Check if country is in Schengen zone
 */
function isSchengenCountry(countryCode: string | null): boolean {
  return countryCode ? SCHENGEN_CODES.has(countryCode) : false;
}

/**
 * Get today's date as YYYY-MM-DD
 */
function getTodayDate(): string {
  return new Date().toISOString().split('T')[0];
}

/**
 * Custom hook for smart location detection
 */
export function useLocationDetection(
  options: UseLocationDetectionOptions = {}
): UseLocationDetectionResult {
  const { enableReminder = true, reminderHour = 9 } = options;

  const [state, setState] = useState<LocationDetectionState>(() => {
    const currentTimezone = getCurrentTimezone();
    const storedTimezone = localStorage.getItem(STORAGE_KEYS.TIMEZONE);
    const lastCheckinDate = localStorage.getItem(STORAGE_KEYS.LAST_CHECKIN);
    const dismissedDate = localStorage.getItem(STORAGE_KEYS.PROMPT_DISMISSED);
    const today = getTodayDate();
    const likelyCountry = getCountryFromTimezone(currentTimezone);

    const timezoneChanged = storedTimezone !== null && storedTimezone !== currentTimezone;
    const checkedInToday = lastCheckinDate === today;
    const promptDismissedToday = dismissedDate === today;

    // Should prompt if:
    // 1. Timezone changed, OR
    // 2. Haven't checked in today AND reminder is enabled AND not dismissed today
    const shouldPromptCheckin =
      timezoneChanged ||
      (enableReminder && !checkedInToday && !promptDismissedToday);

    return {
      currentTimezone,
      storedTimezone,
      timezoneChanged,
      likelyCountry,
      likelySchengen: isSchengenCountry(likelyCountry),
      currentLocale: getCurrentLocale(),
      shouldPromptCheckin,
      lastCheckinDate,
      checkedInToday,
    };
  });

  // Check for timezone changes periodically (every 5 minutes)
  useEffect(() => {
    const checkTimezone = () => {
      const currentTimezone = getCurrentTimezone();
      setState((prev) => {
        if (prev.currentTimezone !== currentTimezone) {
          const likelyCountry = getCountryFromTimezone(currentTimezone);
          return {
            ...prev,
            currentTimezone,
            timezoneChanged: prev.storedTimezone !== null && prev.storedTimezone !== currentTimezone,
            likelyCountry,
            likelySchengen: isSchengenCountry(likelyCountry),
            shouldPromptCheckin: true,
          };
        }
        return prev;
      });
    };

    const interval = setInterval(checkTimezone, 5 * 60 * 1000); // 5 minutes
    return () => clearInterval(interval);
  }, []);

  // Check at reminder hour
  useEffect(() => {
    if (!enableReminder) return;

    const checkReminderTime = () => {
      const now = new Date();
      const today = getTodayDate();
      const lastCheckin = localStorage.getItem(STORAGE_KEYS.LAST_CHECKIN);
      const dismissed = localStorage.getItem(STORAGE_KEYS.PROMPT_DISMISSED);

      // Show reminder if:
      // - Current hour >= reminder hour
      // - Haven't checked in today
      // - Haven't dismissed today
      if (
        now.getHours() >= reminderHour &&
        lastCheckin !== today &&
        dismissed !== today
      ) {
        setState((prev) => ({
          ...prev,
          shouldPromptCheckin: true,
          checkedInToday: false,
        }));
      }
    };

    checkReminderTime();
    const interval = setInterval(checkReminderTime, 60 * 1000); // Every minute
    return () => clearInterval(interval);
  }, [enableReminder, reminderHour]);

  const acknowledgeTimezone = useCallback(() => {
    localStorage.setItem(STORAGE_KEYS.TIMEZONE, state.currentTimezone);
    setState((prev) => ({
      ...prev,
      storedTimezone: prev.currentTimezone,
      timezoneChanged: false,
    }));
  }, [state.currentTimezone]);

  const markCheckedIn = useCallback(() => {
    const today = getTodayDate();
    localStorage.setItem(STORAGE_KEYS.LAST_CHECKIN, today);
    localStorage.setItem(STORAGE_KEYS.TIMEZONE, state.currentTimezone);
    setState((prev) => ({
      ...prev,
      lastCheckinDate: today,
      checkedInToday: true,
      shouldPromptCheckin: false,
      storedTimezone: prev.currentTimezone,
      timezoneChanged: false,
    }));
  }, [state.currentTimezone]);

  const dismissPrompt = useCallback(() => {
    const today = getTodayDate();
    localStorage.setItem(STORAGE_KEYS.PROMPT_DISMISSED, today);
    setState((prev) => ({
      ...prev,
      shouldPromptCheckin: false,
    }));
  }, []);

  const isReminderTime = useCallback(() => {
    const now = new Date();
    return now.getHours() >= reminderHour;
  }, [reminderHour]);

  return useMemo(
    () => ({
      state,
      acknowledgeTimezone,
      markCheckedIn,
      dismissPrompt,
      isReminderTime,
    }),
    [state, acknowledgeTimezone, markCheckedIn, dismissPrompt, isReminderTime]
  );
}

export default useLocationDetection;
