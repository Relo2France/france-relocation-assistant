import { describe, expect, it } from 'vitest';
import { isWordPressPath } from './worker';
import { routes } from './content/guides';

/**
 * Which paths belong to WordPress.
 *
 * Getting this wrong is not a styling bug: claim too much and the new pages
 * disappear behind the old site; claim too little and signing in 404s, or the
 * portal loads with no API and no assets.
 */
describe('routing between the new site and WordPress', () => {
  it('sends the portal, auth and checkout to WordPress', () => {
    for (const path of [
      '/portal', '/portal/', '/portal/dashboard',
      '/login/', '/logged-out/', '/account/',
      '/register/lifetime-membership/', '/thank-you/',
      '/wp-admin/options.php', '/wp-login.php',
      '/wp-json/fra-portal/v1/dashboard',
      '/wp-content/plugins/france-relocation-member-tools/assets/portal/js/main.js',
    ]) {
      expect(isWordPressPath(path), path).toBe(true);
    }
  });

  it('keeps every prerendered route for the new site', () => {
    for (const route of routes) {
      expect(isWordPressPath(route), route).toBe(false);
    }
  });

  it('does not hand over paths that merely start with the same letters', () => {
    // "/about" is WordPress; "/aboutique" would not be.
    for (const path of ['/logins', '/portals', '/accounts-payable', '/aboutique']) {
      expect(isWordPressPath(path), path).toBe(false);
    }
  });
})
