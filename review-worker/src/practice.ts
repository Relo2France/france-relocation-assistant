/**
 * The In Practice gate.
 *
 * The In Practice section is the site's lived-experience layer, and it is
 * only worth having if it is not rumour. The rule, set 2026-09-17: a report
 * reaches the review queue only when at least two independent, dated
 * community sources back it. A section that arrives with fewer is withheld
 * (the official text still goes through), and the reason travels with the
 * draft so it shows on the review card and in the run email.
 */

export interface PracticeSource {
  name: string;
  type: string;
  date: string;
}

export interface PracticeVerdict {
  /** The section to post, or '' when withheld. */
  content: string;
  sources: PracticeSource[];
  /** Why the section was withheld, or '' when it was not. */
  withheld: string;
  /** How well backed the section is: the count of independent dated sources. */
  corroboration: number;
}

const MIN_SOURCES = 2;

/** A source counts as dated when it names a year. */
function isDated(s: PracticeSource): boolean {
  return /\b(19|20)\d{2}\b/.test(`${s.date} ${s.name}`);
}

/**
 * Two sources are the same outlet when their names agree once the noise is
 * stripped: "Reddit r/expats, Jan 2025" and "Reddit r/expats thread" are one.
 */
function outletKey(s: PracticeSource): string {
  return s.name
    .toLowerCase()
    .replace(/\b(19|20)\d{2}\b/g, '')
    .replace(/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*\b/g, '')
    .replace(/\b(thread|post|comment|discussion|forum|blog|article|group|page)s?\b/g, '')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
    .split(' ')
    .slice(0, 3)
    .join(' ');
}

export function normaliseSources(raw: unknown): PracticeSource[] {
  if (!Array.isArray(raw)) return [];
  const out: PracticeSource[] = [];
  for (const item of raw.slice(0, 50)) {
    if (typeof item === 'string') {
      const name = item.trim();
      if (name) out.push({ name, type: 'forum', date: '' });
    } else if (item && typeof item === 'object') {
      const o = item as { name?: unknown; type?: unknown; date?: unknown };
      const name = typeof o.name === 'string' ? o.name.trim() : '';
      if (name) out.push({ name, type: typeof o.type === 'string' ? o.type : 'forum', date: typeof o.date === 'string' ? o.date : '' });
    }
  }
  return out;
}

export function vetInPractice(content: unknown, rawSources: unknown): PracticeVerdict {
  const text = typeof content === 'string' ? content.trim() : '';
  const sources = normaliseSources(rawSources);
  if (text === '') {
    return { content: '', sources, withheld: '', corroboration: 0 };
  }
  const dated = sources.filter(isDated);
  const outlets = new Set(dated.map(outletKey));
  const corroboration = outlets.size;
  if (sources.length === 0) {
    return { content: '', sources, withheld: 'In Practice withheld: no community sources were given', corroboration };
  }
  if (dated.length === 0) {
    return { content: '', sources, withheld: `In Practice withheld: ${sources.length} source${sources.length === 1 ? '' : 's'} given, none dated`, corroboration };
  }
  if (corroboration < MIN_SOURCES) {
    return {
      content: '',
      sources,
      withheld: `In Practice withheld: only ${corroboration} independent dated source${corroboration === 1 ? '' : 's'}; the rule is ${MIN_SOURCES} or more so one account cannot become site advice`,
      corroboration,
    };
  }
  return { content: text, sources, withheld: '', corroboration };
}

/** The wording both prompts share on what In Practice may contain. */
export const IN_PRACTICE_RULES = `**IN PRACTICE RULES (the site's reputation rides on this section):**
- Include a report only when at least two independent community sources, each with a date, say the same thing. One person's story is not enough; leave it out or, if it is important, label it plainly as "one account, unconfirmed".
- Attribute inline with source and date, e.g. (Reddit r/expats, Jan 2026; FrenchEntrée forum, Mar 2026). Prefer accounts from the last eighteen months.
- Say how widely reported each point is: "widely reported", "several accounts", "one account".
- Distinguish "the law says X" from "in practice, Y". Be honest about grey areas without encouraging rule-breaking.
- No rumours, no speculation, no advice to break rules. If you found nothing that meets this bar, return an empty in_practice_content; that is the right answer.
- List every community source you relied on in practice_sources, each with a date. A section with fewer than two independent dated sources will be withheld automatically.`;
