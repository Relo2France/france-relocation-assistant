import type { ReactNode } from 'react';
import { Label } from './Label';

/**
 * Lived experience, visibly separated from official requirement.
 *
 * This is the component the whole system exists to make possible. In the
 * current site an "In Practice" note and a legal requirement are rendered
 * identically, which means a Reddit consensus can read as law. Here it is
 * honey-coloured, labelled, and its sources are cited - so nobody mistakes
 * "most Americans show 1.5x" for a rule.
 */
export function PracticeNote({ children, sources }: { children: ReactNode; sources?: string }) {
  return (
    <aside
      data-kind="practice"
      className="mt-[26px] bg-honey-soft rounded-[10px] px-5 py-[18px] font-ui"
    >
      <span className="block mb-2">
        <Label tone="honey">What people actually experience</Label>
      </span>
      <div className="text-[0.93rem] leading-[1.6] [&>p]:mb-[9px] [&>p:last-child]:mb-0">
        {children}
      </div>
      {sources ? (
        <cite className="not-italic block mt-3 font-mono text-[0.7rem] text-muted">{sources}</cite>
      ) : null}
    </aside>
  );
}
