/**
 * Server entry, used only by the prerender step.
 *
 * The whole SEO argument rests on this: cgp-site shipped client-rendered
 * content pages and Google flagged ~136 URLs as Soft 404, because crawlers got
 * a title stub with the article locked behind JavaScript. Rendering the real
 * component to HTML at build time is the fix.
 */
import { renderToString } from 'react-dom/server';
import { resolveRoute, type PageMeta } from './router';

export function render(path: string): { html: string; meta: PageMeta } | null {
  const route = resolveRoute(path);
  if (!route) return null;
  return { html: renderToString(route.element), meta: route.meta };
}

export { routes } from './content/guides';
