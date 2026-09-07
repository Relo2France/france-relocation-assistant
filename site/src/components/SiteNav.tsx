import { Button } from './Button';
import { external } from '../content/links';

/** Warm chrome. Nothing in the navigation is ever a legal requirement. */
export function SiteNav({ cta = 'Get started' }: { cta?: string }) {
  return (
    <nav className="flex items-center justify-between gap-4 px-7 py-4 border-b border-rule-soft">
      <a href="/" className="font-display font-bold text-[1.1rem] tracking-[-0.02em] text-ink no-underline">
        Relo<span className="text-vine">2</span>France
      </a>
      <ul className="hidden sm:flex gap-[22px] list-none m-0 p-0">
        {[
          ['Guides', '/guides/'],
          ['How it works', '/how-it-works/'],
          ['Pricing', '/pricing/'],
        ].map(([label, href]) => (
          <li key={href}>
            <a href={href} className="font-ui text-[0.86rem] font-medium text-muted no-underline hover:text-ink">
              {label}
            </a>
          </li>
        ))}
      </ul>
      <div className="flex gap-2">
        {/* Sign-in still lives on WordPress until the portal is ported. */}
        <Button href={external.signIn} variant="ghost">Sign in</Button>
        <Button href="/pricing/">{cta}</Button>
      </div>
    </nav>
  );
}
