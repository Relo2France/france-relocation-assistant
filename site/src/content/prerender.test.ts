/**
 * The Soft 404 guard.
 *
 * cgp-site shipped client-rendered content pages and Google flagged ~136 URLs
 * as Soft 404: the crawler received a title and an empty div. These tests read
 * the built output and assert a crawler gets the actual article.
 *
 * Requires `npm run build` first. They are skipped rather than failed when
 * dist is absent, so a plain `npm test` still works.
 */
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { guides, routes } from './guides';

const DIST = resolve(process.cwd(), 'dist');
const built = existsSync(resolve(DIST, 'index.html'));
const describeBuilt = built ? describe : describe.skip;

const read = (route: string) =>
  readFileSync(resolve(DIST, route === '/' ? 'index.html' : `${route.slice(1)}index.html`), 'utf8');

describeBuilt('prerendered output', () => {
  it('writes a file for every route', () => {
    for (const route of routes) {
      const path = resolve(DIST, route === '/' ? 'index.html' : `${route.slice(1)}index.html`);
      expect(existsSync(path), `${route} was not written`).toBe(true);
    }
  });

  it('serves the real article body, not an empty shell', () => {
    const html = read('/guides/visitor-visa-requirements/');
    // Text a crawler must see without running JavaScript.
    expect(html).toContain('declaration promising not to engage in any professional activity');
    expect(html).toContain('€1,478/month');
    expect(html).toContain('sufficient means of existence');
    expect(html).not.toContain('<div id="root"></div>');
  });

  it('keeps the official / anecdotal distinction in the static HTML', () => {
    const html = read('/guides/visitor-visa-requirements/');
    expect(html).toContain('data-kind="practice"');
    expect(html).toContain('data-kind="requirements"');
    expect(html).toContain('What people actually experience');
  });

  it('gives every page its own title, description and canonical', () => {
    const seen = new Set<string>();
    for (const route of routes) {
      const html = read(route);
      const title = html.match(/<title>([^<]*)<\/title>/)?.[1];
      const desc = html.match(/name="description" content="([^"]*)"/)?.[1];
      const canonical = html.match(/rel="canonical" href="([^"]*)"/)?.[1];

      expect(title, `${route} has no title`).toBeTruthy();
      expect(desc, `${route} has no description`).toBeTruthy();
      expect(canonical, `${route} has no canonical`).toBe(`https://relo2france.com${route}`);

      expect(seen.has(title!), `${route} reuses the title "${title}"`).toBe(false);
      seen.add(title!);
    }
  });

  it('emits Article JSON-LD on guides', () => {
    const html = read('/guides/tax-residency-rules/');
    const ld = html.match(/application\/ld\+json">([^<]*)</)?.[1];
    expect(ld).toBeTruthy();
    const parsed = JSON.parse(ld!.replace(/\\u003c/g, '<'));
    expect(parsed['@type']).toBe('Article');
    expect(parsed.headline).toBe('French Tax Residency Rules');
  });

  it('still ships the app script, so browsers hydrate', () => {
    expect(read('/')).toMatch(/<script type="module"[^>]*src="[^"]*\.js"/);
  });
});

describeBuilt('404, sitemap and robots', () => {
  const raw = (name: string) => readFileSync(resolve(DIST, name), 'utf8');

  it('ships a 404 page with real content, not an apology', () => {
    const html = raw('404.html');
    expect(html).toContain('That page isn');
    expect(html).not.toContain('<div id="root"></div>');
    // It offers a way onward rather than dead-ending.
    expect(html).toContain('/guides/');
  });

  it('keeps the 404 out of the index', () => {
    expect(raw('404.html')).toContain('<meta name="robots" content="noindex">');
  });

  it('lists every real route in the sitemap', () => {
    const xml = raw('sitemap.xml');
    for (const route of routes) {
      expect(xml, `${route} missing from sitemap`).toContain(`<loc>https://relo2france.com${route}</loc>`);
    }
    expect((xml.match(/<loc>/g) ?? []).length).toBe(routes.length);
  });

  it('never lists the 404 in the sitemap', () => {
    expect(raw('sitemap.xml')).not.toContain('/404');
  });

  it('dates guide entries from when their content was verified', () => {
    const xml = raw('sitemap.xml');
    for (const g of guides) {
      const block = xml.split(`<loc>https://relo2france.com/guides/${g.slug}/</loc>`)[1] ?? '';
      expect(block, `${g.slug} has no lastmod`).toContain(`<lastmod>${g.verified}-01</lastmod>`);
    }
  });

  it('matches robots.txt to the build it came from', () => {
    // dist holds whichever build ran last, so detect the mode rather than
    // assuming production - and assert the two stay consistent with each
    // other, which is the thing that actually matters.
    const txt = raw('robots.txt');
    const isStagingBuild = raw('index.html').includes('name="robots" content="noindex"');

    expect(txt).toContain('User-agent: *');

    if (isStagingBuild) {
      expect(txt).toContain('Disallow: /');
      expect(txt).not.toContain('Allow: /');
      // A staging build must not advertise the production sitemap.
      expect(txt).not.toContain('Sitemap:');
    } else {
      expect(txt).toContain('Allow: /');
      expect(txt).toContain('Sitemap: https://relo2france.com/sitemap.xml');
    }
  });
});

describe('guide URLs', () => {
  /**
   * These slugs are already indexed on relo2france.com. Changing one costs a
   * ranking and needs a redirect, so it should be a deliberate act - not
   * something that slips through in a rename.
   */
  it('matches the URLs already live in the sitemap', () => {
    expect(guides.map((g) => g.slug).sort()).toEqual(
      [
        'buying-property-france',
        'carte-vitale-application',
        'french-bank-account',
        'french-healthcare-overview',
        'long-stay-visa-overview',
        'role-of-notaire',
        'tax-residency-rules',
        'visitor-visa-requirements',
      ].sort()
    );
  });

  it('gives every guide a description for search results', () => {
    for (const g of guides) {
      expect(g.description.length, `${g.slug} description too short`).toBeGreaterThan(40);
      expect(g.description.length, `${g.slug} description too long`).toBeLessThan(200);
    }
  });
});
