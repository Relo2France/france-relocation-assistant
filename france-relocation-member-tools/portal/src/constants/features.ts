/**
 * Feature Constants
 *
 * Centralized configuration values for various features.
 */

/**
 * Virtualization settings
 */
export const VIRTUALIZATION = {
  /** Minimum items before virtualization kicks in */
  THRESHOLD: 50,
  /** Overscan for list virtualization */
  OVERSCAN_LIST: 5,
  /** Overscan for grid virtualization */
  OVERSCAN_GRID: 2,
  /** Default gap between grid items (px) */
  GRID_GAP: 16,
} as const;

/**
 * Item heights for virtualization (px)
 */
export const ITEM_HEIGHTS = {
  /** Task list item estimated height */
  TASK_LIST: 72,
  /** File grid item estimated height */
  FILE_GRID: 220,
} as const;

/**
 * Search configuration
 */
export const SEARCH = {
  /** Minimum query length before search triggers */
  MIN_QUERY_LENGTH: 2,
} as const;

/**
 * Chat configuration
 */
export const CHAT = {
  /** Maximum message character length */
  MAX_MESSAGE_LENGTH: 500,
  /** Show warning at this percentage of max length */
  WARNING_THRESHOLD_PERCENT: 0.9,
} as const;

/**
 * Profile completion thresholds (percentage)
 */
export const PROFILE_COMPLETION = {
  /** Below this: red/low */
  LOW: 50,
  /** Above this: green/high */
  HIGH: 80,
} as const;

/**
 * Move date alerts
 */
export const MOVE_DATE = {
  /** Days before move date to show alert */
  ALERT_THRESHOLD_DAYS: 30,
} as const;

/**
 * Map configuration
 */
export const MAP = {
  /** Zoom level to show department labels */
  LABEL_ZOOM_THRESHOLD: 2.5,
  /** Font size for labels when zoomed in */
  LABEL_FONT_SIZE: 6,
} as const;

/**
 * Document generator
 */
export const DOCUMENT_GENERATOR = {
  /** Preview step number */
  PREVIEW_STEP: 2,
} as const;
