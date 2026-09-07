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
  const { render, routes } = await vite.ssrLoadModule('/src/entry-server.tsx');
  let written = 0;

  for (const route of routes) {
    const result = render(route);

    if (!result) {
      // A route the router cannot resolve would ship as an empty shell, which
      // is the exact failure this script exists to prevent.
      throw new Error(`Route ${route} did not resolve - refusing to write an empty page`);
    }

    const html = template
      .replace(/<title>[^<]*<\/title>/, buildHead(result.meta))
      .replace('<div id="root"></div>', `<div id="root">${result.html}</div>`);

    if (!html.includes(result.html.slice(0, 60))) {
      throw new Error(`Body injection failed for ${route}`);
    }

    const out = join(DIST, route === '/' ? 'index.html' : `${route}index.html`);
    mkdirSync(dirname(out), { recursive: true });
    writeFileSync(out, html);
    written++;
    console.log(`  prerendered ${route}`);
  }

  console.log(`\n${written} routes prerendered into ${DIST}/`);
} finally {
  await vite.close();
}
