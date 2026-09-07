import type { ReactNode } from 'react';

/**
 * Official requirements. Deliberately plain: hairline rules, no colour beyond
 * the marker, monospaced figures via <Figure>. Nothing here should look
 * decorated - it is the part people act on.
 */
export function Requirements({ children }: { children: ReactNode }) {
  return (
    <ul data-kind="requirements" className="list-none m-0 mb-[14px] p-0 flex flex-col">
      {children}
    </ul>
  );
}

export function Requirement({ children }: { children: ReactNode }) {
  return (
    <li className="flex gap-3 py-[9px] border-b border-rule-soft last:border-b-0">
      <span aria-hidden="true" className="w-[5px] h-[5px] mt-[11px] bg-vine flex-none" />
      <span>{children}</span>
    </li>
  );
}

/** A number someone would check twice: a fee, a threshold, a validity period. */
export function Figure({ children }: { children: ReactNode }) {
  return (
    <span className="font-mono font-semibold text-[0.92em] bg-vine-soft text-ink px-[5px] py-px rounded-[3px]">
      {children}
    </span>
  );
}

/** A qualification on a requirement - quieter than the requirement itself. */
export function Caveat({ children }: { children: ReactNode }) {
  return (
    <p className="text-[0.93rem] text-muted border-l-2 border-rule pl-[14px]">{children}</p>
  );
}
