import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import worker, { isPrivateBuildPath, isWordPressPath, legacyRedirect, withSecurityHeaders } from './worker';
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
      // WordPress.com's concatenated asset bundles. Without this, proxied
      // pages render unstyled because every bundle 404s.
      '/_static/??wp-content/themes/relo2france-theme/style.css',
      '/_static/??-eJwrL9BNzs8rSc0r0S',
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
});

/**
 * The old sitemap listed four category archives the new site has no page
 * for. They were the only sitemap URLs that would have 404ed at cutover.
 */
describe('legacy URLs from the old sitemap', () => {
  it('redirects the guide category archives to the guides index', () => {
    for (const cat of ['healthcare', 'property', 'taxes', 'visas']) {
      expect(legacyRedirect(`/guides/category/${cat}/`)).toBe('/guides/');
      expect(legacyRedirect(`/guides/category/${cat}`)).toBe('/guides/');
    }
  });

  it('leaves real pages and WordPress paths alone', () => {
    for (const path of [...routes, '/guides/category/', '/guides/category/visas/extra/', '/portal/', '/category/visas/']) {
      expect(legacyRedirect(path), path).toBeNull();
    }
  });
});

/**
 * The asset layer runs before the Worker unless told otherwise, and for a
 * browser navigation to a path that is not an asset it answers with the 404
 * page itself. The Worker's proxy never runs, so /portal/ and /login/ 404 in
 * every browser while curl - which sends no Sec-Fetch-Mode - reaches
 * WordPress and every check passes. That is exactly what happened at cutover.
 */
describe('wrangler config', () => {
  it('runs the Worker before the asset layer, so proxied paths work in a browser', () => {
    // vitest runs this in a jsdom environment, where import.meta.url is not a
    // file URL. The root is the working directory, which is the site folder.
    const config = readFileSync(resolve(process.cwd(), 'wrangler.jsonc'), 'utf8');
    expect(config).toMatch(/"run_worker_first":\s*true/);
  });
});


describe('security', () => {
  const env = {
    ASSETS: { fetch: async () => new Response('<html>ok</html>', { headers: { 'content-type': 'text/html' } }) },
    WP_ORIGIN: 'https://origin.example',
  };

  it('redirects plain HTTP to HTTPS, keeping the path and query', async () => {
    const res = await worker.fetch(new Request('http://relo2france.com/portal/?view=deadlines'), env);
    expect(res.status).toBe(301);
    expect(res.headers.get('location')).toBe('https://relo2france.com/portal/?view=deadlines');
  });

  it('puts the security headers on every page', async () => {
    const res = await worker.fetch(new Request('https://relo2france.com/pricing/'), env);
    expect(res.headers.get('strict-transport-security')).toContain('max-age=');
    expect(res.headers.get('x-content-type-options')).toBe('nosniff');
    expect(res.headers.get('x-frame-options')).toBe('SAMEORIGIN');
    expect(res.headers.get('content-security-policy')).toContain("frame-ancestors 'self'");
    expect(res.headers.get('referrer-policy')).toBe('strict-origin-when-cross-origin');
  });

  it('keeps a header the origin already set', () => {
    const res = withSecurityHeaders(new Response('x', { headers: { 'X-Frame-Options': 'DENY' } }));
    expect(res.headers.get('x-frame-options')).toBe('DENY');
  });

  it('never serves the portal source, demo or package files', async () => {
    for (const p of [
      '/wp-content/plugins/france-relocation-member-tools/portal/src/App.tsx',
      '/wp-content/plugins/france-relocation-member-tools/portal/demo/demo.html',
      '/wp-content/plugins/france-relocation-member-tools/portal/package-lock.json',
    ]) {
      expect(isPrivateBuildPath(p)).toBe(true);
      const res = await worker.fetch(new Request(`https://relo2france.com${p}`), env);
      expect(res.status).toBe(404);
    }
    expect(isPrivateBuildPath('/wp-content/plugins/france-relocation-member-tools/assets/portal/js/main.js')).toBe(false);
  });
});
