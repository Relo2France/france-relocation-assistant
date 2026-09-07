import { useEffect, useState } from 'react';
import { notFoundRoute, resolveRoute } from './router';
import { settleAfterNavigation, startNavigation } from './navigation';
import { MemberProvider } from './member';
import { Shell } from './Shell';

export function App({ path }: { path?: string }) {
  // On the server, and for the first client render, this must match the
  // prerendered markup exactly or hydration will discard it.
  const [current, setCurrent] = useState(
    () => path ?? (typeof window === 'undefined' ? '/' : window.location.pathname)
  );
  const [navigated, setNavigated] = useState(false);

  useEffect(() => {
    return startNavigation(
      (candidate) => resolveRoute(candidate) !== null,
      (next) => {
        setCurrent(next);
        setNavigated(true);
      }
    );
  }, []);

  // Only after an in-page navigation - never on first load, where the browser
  // already handles scroll and focus.
  useEffect(() => {
    if (navigated) settleAfterNavigation();
  }, [navigated, current]);

  const route = resolveRoute(current);

  useEffect(() => {
    if (navigated && route) document.title = route.meta.title;
  }, [navigated, route]);

  return (
    <MemberProvider>
      <Shell>{(route ?? notFoundRoute()).element}</Shell>
    </MemberProvider>
  );
}
