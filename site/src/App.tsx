import { notFoundRoute, resolveRoute } from './router';

export function App({ path }: { path?: string }) {
  const current = path ?? (typeof window === 'undefined' ? '/' : window.location.pathname);
  const route = resolveRoute(current);

  return (
    <main className="max-w-[1140px] mx-auto bg-card border-x border-rule min-h-screen">
      {(route ?? notFoundRoute()).element}
    </main>
  );
}
