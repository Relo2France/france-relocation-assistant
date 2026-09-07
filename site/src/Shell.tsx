import type { ReactNode } from 'react';
import { SiteFooter } from './components/SiteFooter';

/**
 * The frame around every page, used by BOTH entries.
 *
 * It has to be shared. The server used to render the route element bare while
 * the client wrapped it in <main>, so hydration found different markup and
 * threw the prerendered tree away - the exact cost the prerender exists to
 * avoid, and invisible in every test that only checked the page's own content.
 */
export function Shell({ children }: { children: ReactNode }) {
  return (
    <main className="max-w-[1140px] mx-auto bg-card border-x border-rule min-h-screen flex flex-col">
      <div className="flex-1">{children}</div>
      <SiteFooter />
    </main>
  );
}
