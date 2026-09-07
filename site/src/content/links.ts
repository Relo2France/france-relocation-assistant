/**
 * Destinations that still live on WordPress.
 *
 * Sign-in, checkout and the portal itself have not been ported yet, so linking
 * to them means leaving this stack. Naming them in one place means the port is
 * a single edit here rather than a hunt through components - and the link test
 * can tell a deliberate external link from a route someone forgot to build.
 */
export const WP_ORIGIN = 'https://relo2france.com';

export const external = {
  signIn: `${WP_ORIGIN}/login/`,
  /** MemberPress checkout. One product: lifetime access. */
  join: `${WP_ORIGIN}/register/lifetime-membership/`,
  portal: `${WP_ORIGIN}/portal/`,
  account: `${WP_ORIGIN}/account/`,
  about: `${WP_ORIGIN}/about/`,
} as const;

/**
 * The price, in one place, because it appears on three pages and a wrong one
 * is worse than a missing one. Sourced from the live MemberPress product.
 */
export const PRICE = '$35';
export const PRICE_NOTE = 'once, for life. No subscription, no renewal.';
