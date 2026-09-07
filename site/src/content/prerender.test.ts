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
import { guides, routes, OFFICIAL_DOMAINS } from './guides';
import { external, WP_ORIGIN } from './links';

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

describe('guide bodies', () => {
  it('claims exactly as many sources as it names', () => {
    // The header prints sourceCount; the footer prints the chips. If they drift,
    // the page tells the reader it checked more sources than it actually did.
    for (const g of guides) {
      expect(g.sources.length, g.slug).toBe(g.sourceCount);
      expect(g.sources.length, g.slug).toBeGreaterThan(0);
    }
  });

  it('gives every guide a real body, not a stub', () => {
    for (const g of guides) {
      expect(g.sections.length, g.slug).toBeGreaterThanOrEqual(2);
      const words = g.sections
        .flatMap((x) => [...(x.paragraphs ?? []), ...(x.requirements ?? [])])
        .join(' ')
        .split(/\s+/).length;
      // A stub detector, not a length target. The bar sits just under
      // visitor-visa-requirements, the thinnest page we ship.
      expect(words, g.slug).toBeGreaterThan(80);
    }
  });

  it('closes every figure it opens', () => {
    for (const g of guides) {
      // Read the prose itself - JSON.stringify would count its own braces.
      const text = [
        ...g.sections.flatMap((x) => [...(x.paragraphs ?? []), ...(x.requirements ?? []), x.caveat ?? '']),
        ...(g.practice?.paragraphs ?? []),
      ].join(' ');
      expect((text.match(/\{\{/g) ?? []).length, g.slug)
        .toBe((text.match(/\}\}/g) ?? []).length);
    }
  });
});

/**
 * The sourcing rule.
 *
 * A guide body states what the French government states, and nothing else.
 * Anything learned from people reporting their own experience is confined to
 * the In Practice note, where the reader is told that is what it is. These
 * tests exist because that distinction is invisible in a diff: prose sourced
 * from a forum reads exactly like prose sourced from service-public.fr.
 */
describe('sourcing rule', () => {
  it('cites only official sources for the body', () => {
    for (const g of guides) {
      for (const s of g.sources) {
        expect(s.kind, `${g.slug}: ${s.label}`).toBe('official');
        expect(
          OFFICIAL_DOMAINS.some((d) => s.label.includes(d)),
          `${g.slug}: "${s.label}" is not an approved official source`,
        ).toBe(true);
      }
    }
  });

  it('never dresses community reporting as an official source', () => {
    // Phrases that mean "someone told us", not "the state published it".
    const tells = /reddit|r\/|forum|blog|expat|anecdot|reported|reports|people say|community|facebook|our experience/i;
    for (const g of guides) {
      for (const s of g.sources) {
        expect(tells.test(s.label), `${g.slug}: "${s.label}" reads as community`).toBe(false);
      }
    }
  });

  it('attributes every In Practice note', () => {
    for (const g of guides) {
      if (!g.practice) continue;
      expect(g.practice.sources?.trim(), g.slug).toBeTruthy();
      expect(g.practice.paragraphs.length, g.slug).toBeGreaterThan(0);
    }
  });

  it('keeps hedged, experience-shaped language out of the body', () => {
    // "most people find", "usually takes longer" are In Practice claims. The
    // body may say what a rule is, not what tends to happen to people.
    const hedges = /\b(most|many|some) (people|americans|applicants|expats|buyers)\b|\b(people report|anecdotally|in our experience|tends to take|often takes longer|the classic mistake|get wrong)\b/i;
    for (const g of guides) {
      const body = g.sections
        .flatMap((x) => [...(x.paragraphs ?? []), ...(x.requirements ?? []), x.caveat ?? ''])
        .join(' ');
      const hit = body.match(hedges);
      expect(hit?.[0], `${g.slug}: move "${hit?.[0]}" into the In Practice note`).toBeUndefined();
    }
  });
});

/**
 * Link integrity.
 *
 * The navigation shipped links to /how-it-works, /pricing, /start and
 * /sign-in when none of those routes existed, so every page carried four
 * links to a 404. Reading the built HTML is the only check that catches it:
 * a component can reference any path it likes and still compile.
 */
describe('internal links', () => {
  const pages = () =>
    routes
      .map((r) => ({ route: r, file: resolve(DIST, r === '/' ? 'index.html' : `${r.slice(1)}index.html`) }))
      .filter((p) => existsSync(p.file));

  it('points every internal link at a route that exists', () => {
    const built = pages();
    if (built.length === 0) return; // build not run; the prerender tests report that

    const known = new Set(routes);
    const broken: string[] = [];

    for (const { route, file } of built) {
      const html = readFileSync(file, 'utf8');
      for (const m of html.matchAll(/href="(\/[^"#?]*)"/g)) {
        const href = m[1]!;
        const clean = href.endsWith('/') ? href : `${href}/`;
        // Assets are emitted by the bundler, not routed.
        if (href.startsWith('/assets/')) continue;
        if (!known.has(clean)) broken.push(`${route} -> ${href}`);
      }
    }

    expect(broken).toEqual([]);
  });

  it('sends members to WordPress explicitly, not to a missing local route', () => {
    // Sign-in, checkout and the portal are not ported yet. They must be
    // absolute links, so that when they are ported it is a deliberate change
    // in one file rather than a silent 404 nobody notices.
    for (const value of Object.values(external)) {
      expect(value.startsWith(`${WP_ORIGIN}/`), value).toBe(true);
    }
  });
});

/**
 * The shell has to be in the prerendered HTML too.
 *
 * The server rendered the route element bare while the client wrapped it in
 * <main> plus the footer, so hydration discarded the prerendered tree and the
 * footer never reached a crawler. Both entries now share Shell.
 */
describe('page shell', () => {
  it('prerenders the footer on every page', () => {
    for (const route of routes) {
      const file = resolve(DIST, route === '/' ? 'index.html' : `${route.slice(1)}index.html`);
      if (!existsSync(file)) continue;
      const html = readFileSync(file, 'utf8');
      expect(html, route).toContain('Not affiliated with the French government');
      expect(html, route).toContain('Checked against official French sources weekly');
    }
  });
});
