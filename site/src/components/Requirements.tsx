import type { ReactNode } from 'react';

/**
 * Official requirements. Deliberately plain: hairline rules, no colour beyond
 * the marker, monospaced figures via <Figure>. Nothing here should look
 * decorated - it is the part people act on.
 */
export function Requirements({ children }: { children: ReactNode }) {
  return (
    <ul data-kind="requirements" className="list-none mt-1 mb-5 p-0 flex flex-col gap-[10px]">
      {children}
    </ul>
  );
}

export function Requirement({ children }: { children: ReactNode }) {
  return (
    <li className="flex gap-3 text-[1rem]">
      <span aria-hidden="true" className="w-[7px] h-[7px] mt-[0.7em] rounded-full bg-vine flex-none" />
      <span>{children}</span>
    </li>
  );
}

/**
 * A requirement written as "{{Term}} (official name) — what it means".
 * The term stands on its own in the UI face, the official name under it in
 * mono, the meaning beside. Returns null when the text is not shaped that
 * way, so the caller falls back to a plain requirement.
 */
export const TERM_ITEM = /^\{\{([^}]+)\}\}\s*(\([^)]*\))?\s*—\s*([\s\S]*)$/;

export function TermList({ children }: { children: ReactNode }) {
  return <dl data-kind="terms" className="mt-2 mb-5 grid gap-[14px]">{children}</dl>;
}

export function Term({ term, official, children }: { term: string; official?: string; children: ReactNode }) {
  return (
    <div className="grid sm:grid-cols-[11rem_minmax(0,1fr)] gap-x-[18px] gap-y-1 items-baseline">
      <dt className="font-ui text-[0.95rem] font-bold leading-[1.35] text-vine m-0">
        {term}
        {official ? <span className="block font-mono font-normal text-[0.74rem] text-muted mt-[2px]">{official}</span> : null}
      </dt>
      <dd className="m-0 text-[1rem]">{children}</dd>
    </div>
  );
}

/** A number someone would check twice: a fee, a threshold, a validity period. */
export function Figure({ children }: { children: ReactNode }) {
  return (
    <mark className="fig font-bold text-inherit">
      {children}
    </mark>
  );
}

/** A qualification on a requirement - quieter than the requirement itself. */
export function Caveat({ children }: { children: ReactNode }) {
  return (
    <aside data-kind="caveat" className="mt-1 mb-6 px-[18px] py-[14px] bg-card-2 border-l-2 border-rule rounded-r-[8px] font-ui text-[0.92rem] leading-[1.55]">
      <span className="block font-ui text-[0.68rem] font-bold uppercase tracking-[0.12em] text-muted">Worth knowing</span>
      <p className="mt-[6px] mb-0">{children}</p>
    </aside>
  );
}
