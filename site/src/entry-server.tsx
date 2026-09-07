/**
 * Server entry, used only by the prerender step.
 *
 * The whole SEO argument rests on this: cgp-site shipped client-rendered
 * content pages and Google flagged ~136 URLs as Soft 404, because crawlers got
 * a title stub with the article locked behind JavaScript. Rendering the real
 * component to HTML at build time is the fix.
 */
import type { ReactElement } from 'react';
import { renderToString } from 'react-dom/server';
import { notFoundRoute, resolveRoute, type PageMeta } from './router';
import { MemberProvider } from './member';
import { Shell } from './Shell';

/**
 * The same tree the browser will hydrate into. MemberProvider renders its
 * children with no member on the server - it only fetches in an effect - so
 * nothing personal reaches the HTML. member.test.ts asserts that.
 */
function frame(element: ReactElement) {
  return (
    <MemberProvider>
      <Shell>{element}</Shell>
    </MemberProvider>
  );
}

export function render(path: string): { html: string; meta: PageMeta } | null {
  const route = resolveRoute(path);
  if (!route) return null;
  return { html: renderToString(frame(route.element)), meta: route.meta };
}

/** The 404 shell. Rendered separately so it never enters the route list. */
export function renderNotFound(): { html: string; meta: PageMeta } {
  const route = notFoundRoute();
  return { html: renderToString(frame(route.element)), meta: route.meta };
}

export { routes } from './content/guides';
export { guides } from './content/guides';
export { SITE } from './router';
