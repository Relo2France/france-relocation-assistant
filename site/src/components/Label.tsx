import type { ReactNode } from 'react';

/** The small uppercase eyebrow used throughout. Tone carries meaning: honey is never law. */
export function Label({ children, tone = 'muted' }: { children: ReactNode; tone?: 'muted' | 'vine' | 'honey' }) {
  const colour = tone === 'honey' ? 'text-honey' : tone === 'vine' ? 'text-vine' : 'text-muted';
  return (
    <span className={`font-ui text-[0.67rem] font-bold uppercase tracking-[0.15em] ${colour}`}>
      {children}
    </span>
  );
}
