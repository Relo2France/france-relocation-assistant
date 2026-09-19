/**
 * The portal's place in the browser URL.
 *
 * The portal is one WordPress page; which screen it shows lives in
 * ?view=… (plus ?stage=… on a stage page and ?guide=… on a guide). Every
 * navigation pushes a history entry so the browser's Back button moves
 * within the portal, and popstate puts the screen back.
 *
 * ?task= and ?message= are one-shot links from emails: they open something
 * once and are dropped from the URL, so a reload does not reopen them.
 */

export interface PortalLocation {
  view: string;
  stage: string | null;
  guide: string | null;
}

/** Links from emails that open one thing, once. */
const ONE_SHOT_PARAMS = ['task', 'message'] as const;

/** Where the URL says the portal is. No ?view= means the dashboard. */
export function readLocation(search: string): PortalLocation {
  const params = new URLSearchParams(search);
  return {
    view: params.get('view') || 'dashboard',
    stage: params.get('stage'),
    guide: params.get('guide'),
  };
}

/** Path, query and hash of an absolute URL: what pushState takes and compares. */
function relative(url: URL): string {
  return `${url.pathname}${url.search}${url.hash}`;
}

/**
 * The URL for a screen, built on the current one so the WordPress page path
 * and any unrelated parameters survive. One-shot parameters are dropped.
 */
export function urlForLocation(href: string, loc: PortalLocation): string {
  const url = new URL(href);
  url.searchParams.set('view', loc.view);
  if (loc.view === 'stage' && loc.stage) url.searchParams.set('stage', loc.stage);
  else url.searchParams.delete('stage');
  if (loc.view === 'guide' && loc.guide) url.searchParams.set('guide', loc.guide);
  else url.searchParams.delete('guide');
  for (const p of ONE_SHOT_PARAMS) url.searchParams.delete(p);
  return relative(url);
}

/** The current URL without the given parameters. */
export function urlWithout(href: string, params: readonly string[]): string {
  const url = new URL(href);
  for (const p of params) url.searchParams.delete(p);
  return relative(url);
}

/**
 * Record a navigation in the browser history. Returns whether an entry was
 * pushed: navigating to the screen already showing adds nothing.
 */
export function pushLocation(loc: PortalLocation): boolean {
  if (typeof window === 'undefined') return false;
  const next = urlForLocation(window.location.href, loc);
  const current = relative(new URL(window.location.href));
  if (next === current) return false;
  // Same screen, different spelling (no ?view= on first load, or a one-shot
  // parameter still attached): tidy the URL in place.
  if (sameLocation(readLocation(window.location.search), loc)) {
    window.history.replaceState(window.history.state, '', next);
    return false;
  }
  window.history.pushState({ portal: loc }, '', next);
  return true;
}

/** Two locations show the same screen. Stage and guide only matter on their own views. */
export function sameLocation(a: PortalLocation, b: PortalLocation): boolean {
  if (a.view !== b.view) return false;
  if (a.view === 'stage') return (a.stage ?? null) === (b.stage ?? null);
  if (a.view === 'guide') return (a.guide ?? null) === (b.guide ?? null);
  return true;
}

/** Drop a consumed one-shot parameter without adding a history entry. */
export function dropParam(param: string): void {
  if (typeof window === 'undefined') return;
  const current = relative(new URL(window.location.href));
  const next = urlWithout(window.location.href, [param]);
  if (next !== current) window.history.replaceState(window.history.state, '', next);
}
