/**
 * The six stages of a move, and which guides belong to each.
 *
 * The same six the member portal is built around, so a reader who joins finds
 * the guides where they left them. A guide not listed here still appears, in
 * a final "More" group: nothing is ever hidden by an omission in this file.
 */
import { guides, type GuideDoc } from './guides';

export interface StageGroup {
  id: string;
  name: string;
  /** The question the person is asking at this point. */
  question: string;
  slugs: string[];
}

export const STAGES: StageGroup[] = [
  {
    id: 'decide', name: 'Decide', question: 'Could we really do this, and how?',
    slugs: ['long-stay-visa-overview', 'vls-ts-or-carte-de-sejour', 'digital-nomad-visa-france', 'tax-residency-rules'],
  },
  {
    id: 'prepare', name: 'Prepare', question: 'What do they need from us, and in what order?',
    slugs: ['visa-application-timeline', 'visitor-visa-requirements', 'work-visa-salarie', 'talent-passport', 'spouse-and-family-visas'],
  },
  { id: 'apply', name: 'Apply', question: 'Get the appointment, submit, wait.', slugs: [] },
  {
    id: 'move', name: 'Move', question: 'Get us, the dog and the furniture there.',
    slugs: ['buying-property-france', 'role-of-notaire'],
  },
  {
    id: 'arrive', name: 'Arrive', question: 'What must happen before the clock runs out?',
    slugs: ['validate-your-visa-anef', 'french-bank-account', 'french-healthcare-overview', 'carte-vitale-application'],
  },
  { id: 'settle', name: 'Settle & renew', question: 'Stay legal, pay the right taxes, put down roots.', slugs: [] },
];

/** Guides grouped by stage, in stage order, with a final "More" for anything unlisted. Empty groups are dropped. */
export function guidesByStage(): { stage: StageGroup; guides: GuideDoc[] }[] {
  const bySlug = new Map(guides.map((g) => [g.slug, g]));
  const placed = new Set<string>();
  const groups = STAGES.map((stage) => {
    const own = stage.slugs.map((slug) => bySlug.get(slug)).filter((g): g is GuideDoc => !!g);
    own.forEach((g) => placed.add(g.slug));
    return { stage, guides: own };
  });
  const rest = guides.filter((g) => !placed.has(g.slug));
  if (rest.length > 0) {
    groups.push({ stage: { id: 'more', name: 'More', question: '', slugs: rest.map((g) => g.slug) }, guides: rest });
  }
  return groups.filter((g) => g.guides.length > 0);
}

/** The stage a guide belongs to, or undefined for an unlisted one. */
export function stageOfGuide(slug: string): StageGroup | undefined {
  return STAGES.find((s) => s.slugs.includes(slug));
}
