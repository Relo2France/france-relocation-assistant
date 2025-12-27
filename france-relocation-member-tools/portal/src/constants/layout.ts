/**
 * Layout Constants
 *
 * Centralized values for UI dimensions and breakpoints.
 */

/**
 * Sidebar dimensions (Tailwind units)
 */
export const SIDEBAR = {
  /** Collapsed width - tailwind w-16 */
  COLLAPSED_WIDTH: 16,
  /** Expanded width - tailwind w-64 */
  EXPANDED_WIDTH: 64,
  /** Logo area height - tailwind h-16 */
  LOGO_HEIGHT: 16,
} as const;

/**
 * Header dimensions
 */
export const HEADER = {
  /** Header height - tailwind h-16 */
  HEIGHT: 16,
  /** Search input width - tailwind w-64 */
  SEARCH_WIDTH: 64,
} as const;

/**
 * Loading fallback dimensions
 */
export const LOADING = {
  /** Minimum height for view loading fallback (px) */
  MIN_HEIGHT: 400,
} as const;

/**
 * Responsive breakpoints (px)
 */
export const BREAKPOINTS = {
  /** Mobile breakpoint */
  SM: 640,
  /** Tablet breakpoint */
  MD: 768,
  /** Desktop breakpoint */
  LG: 1024,
  /** Wide desktop breakpoint */
  XL: 1280,
} as const;
