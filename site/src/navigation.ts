/**
 * Client-side navigation over prerendered pages.
 *
 * The static HTML is what crawlers and the first paint get. Once hydrated,
 * same-page navigation should feel like the portal rather than a 2005 website,
 * so link clicks are intercepted and the view swapped without a round trip.
 *
 * Plain <a href> is kept throughout rather than a <Link> component: the markup
 * stays correct with JavaScript disabled, and crawlers see real links.
 */

/**
 * Should this click be handled in-page?
 *
 * Pure and exported so the decision can be tested - it is easy to get wrong in
 * ways that break middle-click, cmd-click and downloads, and those failures are
 * quiet.
 */
export function shouldIntercept(
  event: Pick<MouseEvent, 'button' | 'metaKey' | 'ctrlKey' | 'shiftKey' | 'altKey' | 'defaultPrevented'>,
  anchor: { href: string; target?: string | null; hasDownload?: boolean; origin?: string },
  currentOrigin: string,
  isKnownRoute: (path: string) => boolean
): boolean {
  // Anything but a plain left click belongs to the browser.
  if (event.defaultPrevented) return false;
  if (event.button !== 0) return false;
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;

  if (anchor.hasDownload) return false;
  if (anchor.target && anchor.target !== '' && anchor.target !== '_self') return false;

  let url: URL;
  try {
    url = new URL(anchor.href, currentOrigin);
  } catch {
    return false;
  }

  if (url.origin !== currentOrigin) return false;

  // Let the browser handle in-page anchors and anything we cannot render -
  // /start and /sign-in are real destinations that do not exist here yet.
  if (url.hash && url.pathname === window.location.pathname) return false;

  return isKnownRoute(url.pathname);
}

export function startNavigation(
  isKnownRoute: (path: string) => boolean,
  onNavigate: (path: string) => void
): () => void {
  const onClick = (event: MouseEvent) => {
    const anchor = (event.target as Element | null)?.closest?.('a');
    if (!anchor) return;

    const href = anchor.getAttribute('href');
    if (!href) return;

    const ok = shouldIntercept(
      event,
      { href, target: anchor.getAttribute('target'), hasDownload: anchor.hasAttribute('download') },
      window.location.origin,
      isKnownRoute
    );
    if (!ok) return;

    const url = new URL(href, window.location.origin);
    event.preventDefault();

    if (url.pathname === window.location.pathname) return;

    window.history.pushState({}, '', url.pathname);
    onNavigate(url.pathname);
  };

  const onPop = () => onNavigate(window.location.pathname);

  document.addEventListener('click', onClick);
  window.addEventListener('popstate', onPop);

  return () => {
    document.removeEventListener('click', onClick);
    window.removeEventListener('popstate', onPop);
  };
}

/**
 * After an in-page navigation, put the reader at the top and move focus into
 * the new page. Without the focus move a screen reader stays where it was and
 * the change goes unannounced.
 */
export function settleAfterNavigation() {
  window.scrollTo({ top: 0, behavior: 'auto' });

  const heading = document.querySelector('h1');
  if (heading instanceof HTMLElement) {
    heading.setAttribute('tabindex', '-1');
    heading.focus({ preventScroll: true });
  }
}
