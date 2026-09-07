/**
 * Static prerender, run after `vite build`.
 *
 * Renders each route's real React component to HTML and writes a static shell
 * with the full body plus correct <head> meta and JSON-LD, so crawlers receive
 * actual content. The app script stays, so browsers hydrate and behave as an
 * SPA from there.
 *
 * This exists because cgp-site shipped client-rendered content pages and Google
 * flagged ~136 URLs as Soft 404 - the crawler saw a title and an empty div.
 */
import { createServer } from 'vite';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const DIST = 'dist';

/**
 * Staging must never be indexed.
 *
 * A public staging URL serving "Allow: /" invites Google to index a duplicate
 * of the site, which then competes with the real one. Canonicals already point
 * at production, but that is not enough on its own.
 */
const IS_STAGING = process.env.SITE_ENV === 'staging';

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function buildHead(meta) {
  const parts = [
    `<title>${escapeHtml(meta.title)}</title>`,
    `<meta name="description" content="${escapeHtml(meta.description)}">`,
    `<link rel="canonical" href="${escapeHtml(meta.canonical)}">`,
    `<meta property="og:type" content="website">`,
    `<meta property="og:title" content="${escapeHtml(meta.title)}">`,
    `<meta property="og:description" content="${escapeHtml(meta.description)}">`,
    `<meta property="og:url" content="${escapeHtml(meta.canonical)}">`,
  ];

  if (meta.noindex || IS_STAGING) {
    parts.push('<meta name="robots" content="noindex">');
  }

  if (meta.jsonLd) {
    // </script> inside JSON would close the tag early.
    const json = JSON.stringify(meta.jsonLd).replace(/</g, '\\u003c');
    parts.push(`<script type="application/ld+json">${json}</script>`);
  }

  return parts.join('\n    ');
}

const template = readFileSync(join(DIST, 'index.html'), 'utf8');

const vite = await createServer({
  server: { middlewareMode: true },
  appType: 'custom',
  logLevel: 'warn',
});

try {
  const { render, renderNotFound, routes, guides, SITE } = await vite.ssrLoadModule(
    '/src/entry-server.tsx'
  );
  let written = 0;

  const shell = (result) =>
    template
      .replace(/<title>[^<]*<\/title>/, buildHead(result.meta))
      .replace('<div id="root"></div>', `<div id="root">${result.html}</div>`);

  for (const route of routes) {
    const result = render(route);

    if (!result) {
      // A route the router cannot resolve would ship as an empty shell, which
      // is the exact failure this script exists to prevent.
      throw new Error(`Route ${route} did not resolve - refusing to write an empty page`);
    }

    const html = shell(result);

    if (!html.includes(result.html.slice(0, 60))) {
      throw new Error(`Body injection failed for ${route}`);
    }

    const out = join(DIST, route === '/' ? 'index.html' : `${route}index.html`);
    mkdirSync(dirname(out), { recursive: true });
    writeFileSync(out, html);
    written++;
    console.log(`  prerendered ${route}`);
  }

  // 404: prerendered like any other page, but noindex and absent from the
  // sitemap. Hosts serve it for unmatched paths.
  writeFileSync(join(DIST, '404.html'), shell(renderNotFound()));
  console.log('  prerendered /404.html');

  // Sitemap. Guides carry a lastmod from the date their content was verified,
  // which is the only honest signal we have.
  const lastmod = (route) => {
    const match = route.match(/^\/guides\/([a-z0-9-]+)\/$/);
    const guide = match && guides.find((g) => g.slug === match[1]);
    return guide ? `${guide.verified}-01` : null;
  };

  const urls = routes
    .map((route) => {
      const mod = lastmod(route);
      return [
        '  <url>',
        `    <loc>${SITE}${route}</loc>`,
        mod ? `    <lastmod>${mod}</lastmod>` : null,
        '  </url>',
      ]
        .filter(Boolean)
        .join('\n');
    })
    .join('\n');

  writeFileSync(
    join(DIST, 'sitemap.xml'),
    `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`
  );
  console.log(`  wrote sitemap.xml (${routes.length} urls)`);

  writeFileSync(
    join(DIST, 'robots.txt'),
    IS_STAGING
      ? ['# Staging. Not for indexing.', 'User-agent: *', 'Disallow: /', ''].join('\n')
      : ['User-agent: *', 'Allow: /', '', `Sitemap: ${SITE}/sitemap.xml`, ''].join('\n')
  );
  console.log(`  wrote robots.txt${IS_STAGING ? ' (staging: disallow all)' : ''}`);

  console.log(
    `\n${written} routes prerendered into ${DIST}/${IS_STAGING ? ' — staging build, noindex throughout' : ''}`
  );
} finally {
  await vite.close();
}
