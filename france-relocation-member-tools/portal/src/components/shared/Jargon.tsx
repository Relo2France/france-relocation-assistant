/**
 * Jargon
 *
 * Wraps glossary terms found in a piece of text with a small "?" that shows
 * the definition in place. The glossary stops being a page people have to
 * go and find; the word explains itself where it appears.
 */
import { useEffect, useMemo, useRef, useState } from 'react';
import { HelpCircle } from 'lucide-react';
import { useGlossary } from '@/hooks/useApi';
import { usePortalStore } from '@/store';

/**
 * What a glossary entry looks like once normalised. The API sends
 * { term, definition, pronunciation }; the older type in @/types says
 * { title, short, french }. Accept either, and never trust that a field is
 * a string - one entry without a name must not take the page down.
 */
interface GlossaryTerm {
  title: string;
  short: string;
  french?: string;
  guide?: string;
  ask?: string;
}

function asString(v: unknown): string | undefined {
  return typeof v === 'string' && v.trim() ? v.trim() : undefined;
}

function normalise(raw: unknown): GlossaryTerm | null {
  if (!raw || typeof raw !== 'object') return null;
  const r = raw as Record<string, unknown>;
  const title = asString(r.term) ?? asString(r.title);
  const short = asString(r.definition) ?? asString(r.short);
  const guide = asString(r.guide);
  const ask = asString(r.ask);
  if (!title || !short) return null;
  return { title, short, french: asString(r.french) , guide, ask };
}

function escapeRegExp(s: string) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/** Terms sorted longest first so "carte de séjour" wins over "carte". */
function buildIndex(categories: unknown): { terms: GlossaryTerm[]; pattern: RegExp | null } {
  const cats = Array.isArray(categories) ? categories : [];
  const terms = cats
    .flatMap((c) => (c && typeof c === 'object' && Array.isArray((c as { terms?: unknown }).terms) ? ((c as { terms: unknown[] }).terms) : []))
    .map(normalise)
    .filter((t): t is GlossaryTerm => t !== null && t.title.length >= 4);
  if (terms.length === 0) return { terms, pattern: null };
  const names = terms
    .flatMap((t) => [t.title, t.french].filter((x): x is string => !!x && x.length >= 4))
    .sort((a, b) => b.length - a.length)
    .map(escapeRegExp);
  try {
    // A term followed by a plain suffix still is the term: "apostilled",
    // "apostilles", "notaires". The suffix is matched but not looked up.
    return { terms, pattern: new RegExp(`(?<![\\p{L}])(${names.join('|')})(s|es|d|ed)?(?![\\p{L}])`, 'giu') };
  } catch {
    return { terms, pattern: null };
  }
}

function findTerm(terms: GlossaryTerm[], word: string): GlossaryTerm | undefined {
  const w = word.toLowerCase();
  return terms.find((t) => t.title.toLowerCase() === w || t.french?.toLowerCase() === w);
}

function Term({ term, children }: { term: GlossaryTerm; children: string }) {
  const [open, setOpen] = useState(false);
  const { setActiveGuide, setActiveView, setChatDraft } = usePortalStore();
  const ref = useRef<HTMLSpanElement>(null);

  useEffect(() => {
    if (!open) return;
    const onDown = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setOpen(false); };
    document.addEventListener('mousedown', onDown);
    document.addEventListener('keydown', onKey);
    return () => { document.removeEventListener('mousedown', onDown); document.removeEventListener('keydown', onKey); };
  }, [open]);

  return (
    <span ref={ref} className="relative inline">
      {children}
      <button
        type="button"
        onClick={(e) => { e.stopPropagation(); setOpen((o) => !o); }}
        aria-expanded={open}
        aria-label={`What is ${term.title}?`}
        className="inline-flex align-middle ml-0.5 text-primary-500 hover:text-primary-700"
      >
        <HelpCircle className="w-3.5 h-3.5" aria-hidden="true" />
      </button>
      {open ? (
        <span
          role="tooltip"
          className="absolute left-0 top-full z-40 mt-1 w-72 rounded-lg border border-rule bg-card p-3 text-left shadow-lg text-sm font-normal normal-case tracking-normal text-ink"
        >
          <span className="block font-display font-semibold text-base mb-1">
            {term.title}
            {term.french && term.french.toLowerCase() !== term.title.toLowerCase() ? (
              <span className="font-sans text-xs text-gray-500 ml-2">{term.french}</span>
            ) : null}
          </span>
          <span className="block leading-snug">{term.short}</span>
          {term.guide || term.ask ? (
            <span className="flex gap-3 mt-2">
              {term.guide ? (
                <button type="button" onClick={(e) => { e.stopPropagation(); setActiveGuide(term.guide ?? ''); setActiveView('guide'); }} className="text-xs font-semibold text-primary-500 hover:text-primary-700">Read the guide →</button>
              ) : null}
              {term.ask ? (
                <button type="button" onClick={(e) => { e.stopPropagation(); setChatDraft(term.ask ?? ''); setActiveView('chat'); }} className="text-xs font-semibold text-primary-500 hover:text-primary-700">Ask about my case →</button>
              ) : null}
            </span>
          ) : null}
        </span>
      ) : null}
    </span>
  );
}

/**
 * Text with glossary terms made explainable. Renders the plain text while the
 * glossary loads, so nothing waits on it.
 */
export default function Jargon({ text, className }: { text: string; className?: string }) {
  const { data } = useGlossary();
  const index = useMemo(() => buildIndex(data), [data]);

  const parts = useMemo(() => {
    if (!index.pattern) return [text];
    const out: (string | { term: GlossaryTerm; word: string })[] = [];
    let last = 0;
    for (const m of text.matchAll(index.pattern)) {
      const start = m.index ?? 0;
      const term = findTerm(index.terms, m[1]);
      if (!term) continue;
      if (start > last) out.push(text.slice(last, start));
      out.push({ term, word: m[0] });
      last = start + m[0].length;
    }
    if (last < text.length) out.push(text.slice(last));
    return out;
  }, [text, index]);

  return (
    <span className={className}>
      {parts.map((p, i) => (typeof p === 'string' ? <span key={i}>{p}</span> : <Term key={i} term={p.term}>{p.word}</Term>))}
    </span>
  );
}
