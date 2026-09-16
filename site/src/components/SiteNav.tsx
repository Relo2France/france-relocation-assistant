import { useState } from 'react';
import { Button } from './Button';
import { external } from '../content/links';
import { useMember } from '../member';

const LINKS: [string, string][] = [
  ['Guides', '/guides/'],
  ['How it works', '/how-it-works/'],
  ['Pricing', '/pricing/'],
];

/**
 * Warm chrome. Nothing in the navigation is ever a legal requirement.
 *
 * A signed-in member is not asked to sign in: they get their account and
 * their portal, the same pair the WordPress header shows them. Below the
 * `sm` breakpoint the links fold behind a Menu button; the markup is plain
 * anchors either way, so the prerender and a no-JS visitor still get links.
 */
export function SiteNav({ cta = 'Get started' }: { cta?: string }) {
  const member = useMember();
  const [open, setOpen] = useState(false);

  const actions = member ? (
    <>
      <Button href={external.account} variant="ghost">Account</Button>
      <Button href={external.portal}>Member portal</Button>
    </>
  ) : (
    <>
      {/* Sign-in still lives on WordPress until the portal is ported. */}
      <Button href={external.signIn} variant="ghost">Sign in</Button>
      <Button href="/pricing/">{cta}</Button>
    </>
  );

  return (
    <nav className="border-b border-rule-soft">
      <div className="flex items-center justify-between gap-4 px-7 py-4">
        <a href="/" className="font-display font-bold text-[1.1rem] tracking-[-0.02em] text-ink no-underline">
          Relo<span className="text-vine">2</span>France
        </a>
        <ul className="hidden sm:flex gap-[22px] list-none m-0 p-0">
          {LINKS.map(([label, href]) => (
            <li key={href}>
              <a href={href} className="font-ui text-[0.86rem] font-medium text-muted no-underline hover:text-ink">
                {label}
              </a>
            </li>
          ))}
        </ul>
        <div className="hidden sm:flex gap-2">{actions}</div>
        <button
          type="button"
          className="sm:hidden inline-flex items-center gap-2 font-ui text-[0.83rem] font-semibold px-[14px] py-[8px] rounded-pill border border-rule bg-transparent text-ink"
          aria-expanded={open}
          aria-controls="site-menu"
          onClick={() => setOpen((o) => !o)}
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
            {open ? <path d="M6 6l12 12M18 6L6 18" /> : <path d="M4 7h16M4 12h16M4 17h16" />}
          </svg>
          Menu
        </button>
      </div>
      <div id="site-menu" className={`${open ? 'flex' : 'hidden'} sm:hidden flex-col gap-3 px-7 pb-5`}>
        <ul className="flex flex-col gap-2 list-none m-0 p-0">
          {LINKS.map(([label, href]) => (
            <li key={href}>
              <a href={href} className="block font-ui text-[1rem] font-medium text-ink no-underline py-1">
                {label}
              </a>
            </li>
          ))}
        </ul>
        <div className="flex flex-col gap-2 [&>a]:text-center">{actions}</div>
      </div>
    </nav>
  );
}
