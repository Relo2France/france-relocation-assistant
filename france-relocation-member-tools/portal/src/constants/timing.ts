/**
 * Cache & Timing Constants
 *
 * Centralized timing values for React Query staleTime and other cache configurations.
 */

/** Time constants in milliseconds */
export const ONE_SECOND_MS = 1000;
export const ONE_MINUTE_MS = 60 * ONE_SECOND_MS;
export const ONE_HOUR_MS = 60 * ONE_MINUTE_MS;
export const ONE_DAY_MS = 24 * ONE_HOUR_MS;

/**
 * React Query staleTime values
 * Used to prevent unnecessary refetches of cached data
 */
export const STALE_TIME = {
  /** 10 seconds - for highly dynamic data like activity feeds */
  DYNAMIC: 10 * ONE_SECOND_MS,
  /** 30 seconds - default for most data */
  DEFAULT: 30 * ONE_SECOND_MS,
  /** 1 minute - for moderately static data */
  SHORT: ONE_MINUTE_MS,
  /** 5 minutes - for user profile data */
  MEDIUM: 5 * ONE_MINUTE_MS,
  /** 1 hour - for static reference data like glossary */
  LONG: ONE_HOUR_MS,
} as const;

/**
 * Refetch intervals for polling
 */
export const REFETCH_INTERVAL = {
  /** 1 minute - for support ticket unread counts */
  SUPPORT_UNREAD: ONE_MINUTE_MS,
} as const;

/**
 * Animation and transition durations
 */
export const TRANSITION_DURATION = {
  /** Sidebar expand/collapse animation */
  SIDEBAR: 300,
  /** Profile completion bar animation */
  PROFILE_BAR: 500,
  /** Scroll to element delay */
  SCROLL_DELAY: 100,
} as const;
