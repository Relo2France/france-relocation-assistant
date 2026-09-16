/**
 * The home page hero, decided from what the site actually knows.
 *
 * The prerendered page is for strangers and crawlers, so it may not name a
 * month or mark a "you are here": it does not know either. Once a member is
 * loaded in the browser, both come from their real move date. A hardcoded
 * "in March" was right for one visitor for about a week.
 */
export interface HeroStep {
  when: string;
  what: string;
  note: string;
}

export const HERO_STEPS: HeroStep[] = [
  { when: '12 months out', what: 'Choose your visa', note: 'Seven long-stay types. Retired and not working narrows it to one.' },
  { when: '6 months out', what: 'Order apostilles', note: 'Marriage and birth certificates. State processing runs to weeks.' },
  { when: '3 months out', what: 'Book the consulate appointment', note: 'Slots fill first for spring movers.' },
  { when: 'On arrival', what: 'Validate through ANEF', note: 'Within three months, or the visa lapses.' },
];

export const GENERIC_HEADLINE = 'Every requirement for your move to France, in the order you’ll need it.';

/** Whole months from `now` to the move; negative once it has passed. */
export function monthsUntil(iso: string, now: Date): number | null {
  const target = new Date(`${iso}T00:00:00Z`);
  if (Number.isNaN(target.getTime())) return null;
  return (
    (target.getUTCFullYear() - now.getUTCFullYear()) * 12 +
    (target.getUTCMonth() - now.getUTCMonth())
  );
}

/** Which step of HERO_STEPS the reader is on, from months to their move. */
export function stepFor(months: number): number {
  if (months > 9) return 0;
  if (months > 3) return 1;
  if (months > 0) return 2;
  return 3;
}

export function heroFor(
  member: { moveDate: string } | null,
  now: Date = new Date()
): { headline: string; nowIndex: number | null } {
  const months = member ? monthsUntil(member.moveDate, now) : null;
  if (member === null || months === null) {
    return { headline: GENERIC_HEADLINE, nowIndex: null };
  }
  if (months <= 0) {
    return { headline: 'You’re in France. Here’s what to do next.', nowIndex: 3 };
  }
  const target = new Date(`${member.moveDate}T00:00:00Z`);
  const month = target.toLocaleString('en-US', { month: 'long', timeZone: 'UTC' });
  const when = months > 12 ? `${month} ${target.getUTCFullYear()}` : month;
  return {
    headline: `You’re moving to France in ${when}. Here’s what to do next.`,
    nowIndex: stepFor(months),
  };
}
