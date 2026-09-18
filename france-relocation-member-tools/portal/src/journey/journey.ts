/**
 * The journey: six stages from "could we really do this?" to "time to renew".
 *
 * Tasks arrive with two different stage vocabularies. The database seeds six
 * stages per visa type (planning, documents, application, approval, moving,
 * settling); the task templates that actually populate a project use four
 * (pre-arrival, arrival, integration, settlement). The portal shows six of
 * its own. Mapping here, rather than migrating stored data, keeps every task
 * where it is and lets the split between stages follow the move date.
 */
import type { Project, Task } from '@/types';

export type JourneyStageId = 'decide' | 'prepare' | 'apply' | 'move' | 'arrive' | 'settle';

export interface JourneyStage {
  id: JourneyStageId;
  number: number;
  name: string;
  /** The question the person is actually asking at this point. */
  question: string;
  /** What the rail shows under the name when nothing more specific applies. */
  blurb: string;
  /** Database stage slugs whose tasks belong here. */
  dbStages: string[];
  /** Guides on the public site that belong to this stage, by slug. */
  guides: { slug: string; title: string }[];
  /** Checklist types on the portal that belong to this stage. */
  checklists: string[];
}

/** Days after the move date during which "settling" still means arriving. */
export const ARRIVAL_WINDOW_DAYS = 90;

export const JOURNEY: JourneyStage[] = [
  {
    id: 'decide', number: 1, name: 'Decide',
    question: 'Could we really do this, and how?',
    blurb: 'Visa route, who is moving, a date',
    dbStages: ['planning'],
    guides: [
      { slug: 'long-stay-visa-overview', title: 'Long-Stay Visa Overview' },
      { slug: 'vls-ts-or-carte-de-sejour', title: 'VLS-TS or Carte de Séjour à Solliciter?' },
      { slug: 'digital-nomad-visa-france', title: 'There Is No Digital Nomad Visa' },
      { slug: 'tax-residency-rules', title: 'Tax Residency Rules' },
    ],
    checklists: [],
  },
  {
    id: 'prepare', number: 2, name: 'Prepare',
    question: 'What do they need from us, and in what order?',
    blurb: 'Documents, apostilles, translations',
    dbStages: ['documents'],
    guides: [
      { slug: 'visa-application-timeline', title: 'When to Start Each Document' },
      { slug: 'visitor-visa-requirements', title: 'The Long-Stay Visitor Visa' },
      { slug: 'work-visa-salarie', title: 'The Salaried Work Visa' },
      { slug: 'talent-passport', title: 'The Talent Passport' },
      { slug: 'spouse-and-family-visas', title: 'Joining a Spouse or Family in France' },
    ],
    checklists: ['visa-application'],
  },
  {
    id: 'apply', number: 3, name: 'Apply',
    question: 'Get the appointment, submit, wait.',
    blurb: 'Consulate, biometrics, the decision',
    dbStages: ['application', 'approval'],
    guides: [{ slug: 'visa-application-timeline', title: 'When to Start Each Document' }],
    checklists: [],
  },
  {
    id: 'move', number: 4, name: 'Move',
    question: 'Get us, the dog and the furniture there.',
    blurb: 'Shipping, pets, housing',
    dbStages: ['moving'],
    guides: [
      { slug: 'buying-property-france', title: 'Buying Property in France' },
      { slug: 'role-of-notaire', title: 'The Role of the Notaire' },
    ],
    checklists: ['pre-departure'],
  },
  {
    id: 'arrive', number: 5, name: 'Arrive',
    question: 'What must happen before the clock runs out?',
    blurb: 'Validate in 90 days, bank, healthcare',
    dbStages: ['settling'],
    guides: [
      { slug: 'validate-your-visa-anef', title: 'Validating Your Visa After Arrival' },
      { slug: 'french-bank-account', title: 'Opening a French Bank Account' },
      { slug: 'french-healthcare-overview', title: 'French Healthcare Overview' },
      { slug: 'carte-vitale-application', title: 'Applying for the Carte Vitale' },
    ],
    checklists: ['arrival', 'banking', 'healthcare'],
  },
  {
    id: 'settle', number: 6, name: 'Settle & renew',
    question: 'Stay legal, pay the right taxes, put down roots.',
    blurb: 'Renewal, taxes, Schengen days',
    dbStages: ['settling'],
    guides: [
      { slug: 'tax-residency-rules', title: 'Tax Residency Rules' },
      { slug: 'vls-ts-or-carte-de-sejour', title: 'Renewal: what the visa type means' },
    ],
    checklists: ['administrative'],
  },
];

export function stageById(id: string | null | undefined): JourneyStage | undefined {
  return JOURNEY.find((s) => s.id === id);
}

export function parseDate(iso: string | null | undefined): Date | null {
  if (!iso) return null;
  const d = new Date(`${iso.slice(0, 10)}T00:00:00Z`);
  return Number.isNaN(d.getTime()) ? null : d;
}

function daysBetween(a: Date, b: Date): number {
  return Math.round((b.getTime() - a.getTime()) / 86_400_000);
}

/** Template tasks that are about getting there, not about the dossier. */
const MOVE_WORDS = /\b(pets?|accommodation|housing|book(ing)?|flights?|shipping|movers?|packing|vet(erinarian)?|vaccination|hand luggage|mail forwarding)\b/i;
/** Template tasks that are the application itself. */
const APPLY_WORDS = /\b(apply for [a-z ]*visas?|consulate|appointment|biometrics?|submit|france-visas|visa application|tlscontact|visa sticker|collect your passport)\b/i;

/**
 * Which journey stage a task belongs to.
 *
 * Seeded database stages map one to one, with the wait for a decision folded
 * into Apply. Template stages need more: "pre-arrival" spans three of our
 * stages, so the title decides between preparing, applying and moving; and
 * anything after the move is arriving for 90 days, then settling.
 */
export function stageForTask(
  task: Pick<Task, 'stage' | 'due_date'> & { title?: string },
  project: Pick<Project, 'target_move_date'>
): JourneyStageId {
  const stage = task.stage ?? '';
  const move = parseDate(project.target_move_date);
  const due = parseDate(task.due_date);
  const daysFromMove = move && due ? daysBetween(move, due) : null;

  // A task already carrying a journey stage (created from the Tasks page)
  // is where it says it is.
  if (JOURNEY.some((j) => j.id === stage)) return stage as JourneyStageId;

  // After the move, the date decides regardless of vocabulary. Move day
  // itself still belongs to Move: the flight is not an arrival step.
  if (daysFromMove !== null && daysFromMove > 0) {
    return daysFromMove <= ARRIVAL_WINDOW_DAYS ? 'arrive' : 'settle';
  }

  switch (stage) {
    case 'planning': return 'decide';
    case 'documents': return 'prepare';
    case 'application':
    case 'approval': return 'apply';
    case 'moving': return 'move';
    case 'settling': return daysFromMove === null ? 'settle' : 'arrive';
    case 'arrival': return 'arrive';
    case 'integration':
    case 'settlement': return 'settle';
    case 'pre-arrival': {
      const title = task.title ?? '';
      if (APPLY_WORDS.test(title)) return 'apply';
      if (MOVE_WORDS.test(title)) return 'move';
      return 'prepare';
    }
    default: return 'prepare';
  }
}

/**
 * Where the person is now.
 *
 * The stored project stage never advances on its own, so it cannot be the
 * answer. Until a visa route is chosen they are deciding. After that the
 * move date places them: preparing until four months out, applying until
 * the last month, moving in that month, arriving for 90 days, then settling.
 */
export function currentStage(
  project: Pick<Project, 'target_move_date'>,
  visaType: string | null | undefined,
  now = new Date()
): JourneyStageId {
  if (!visaType || visaType === 'undecided') return 'decide';
  const move = parseDate(project.target_move_date);
  if (!move) return 'prepare';
  const days = daysBetween(now, move);
  if (days > 120) return 'prepare';
  if (days > 30) return 'apply';
  if (days > 0) return 'move';
  if (-days <= ARRIVAL_WINDOW_DAYS) return 'arrive';
  return 'settle';
}

/** Progress for a journey stage, counted from the tasks that map to it. */
export function progressFor(
  stage: JourneyStage,
  tasks: Pick<Task, 'stage' | 'due_date' | 'status' | 'title'>[],
  project: Pick<Project, 'target_move_date'>
): { total: number; completed: number } {
  const own = tasks.filter((t) => stageForTask(t, project) === stage.id);
  return { total: own.length, completed: own.filter((t) => t.status === 'done').length };
}

/**
 * A human label for when each stage happens, counted back from the move.
 * Used on the road; blank where the move date is unknown.
 */
export function stageWhen(stage: JourneyStageId, project: Pick<Project, 'target_move_date'>, now = new Date()): string {
  const move = parseDate(project.target_move_date);
  if (!move) return '';
  const month = (d: Date) => d.toLocaleString('en-US', { month: 'short', year: 'numeric', timeZone: 'UTC' }).toUpperCase();
  const shift = (days: number) => new Date(move.getTime() + days * 86_400_000);
  switch (stage) {
    case 'decide': {
      const months = Math.max(1, Math.round(daysBetween(now, move) / 30));
      return `${months} ${months === 1 ? 'MONTH' : 'MONTHS'} OUT`;
    }
    case 'prepare': return month(shift(-180));
    case 'apply': return month(shift(-120));
    case 'move': return month(move);
    case 'arrive': return `${month(move)} · 90 DAYS`;
    case 'settle': return `${move.getUTCFullYear() + 1} →`;
  }
}

/** "11 months to go", "2 weeks to go", "in France" */
export function timeToGo(project: Pick<Project, 'target_move_date'>, now = new Date()): string {
  const move = parseDate(project.target_move_date);
  if (!move) return 'no move date yet';
  const days = daysBetween(now, move);
  if (days < 0) return 'in France';
  if (days === 0) return 'moving today';
  if (days < 21) return `${days} day${days === 1 ? '' : 's'} to go`;
  if (days < 60) return `${Math.round(days / 7)} weeks to go`;
  return `${Math.round(days / 30)} months to go`;
}

/** Group a stage's tasks by how soon they are due, so the page reads as an order. */
export function groupByLeadTime(tasks: Task[], now = new Date()): { label: string; tasks: Task[] }[] {
  const soon: Task[] = [], later: Task[] = [], undated: Task[] = [], done: Task[] = [];
  for (const t of tasks) {
    if (t.status === 'done') done.push(t);
    else if (!t.due_date) undated.push(t);
    else {
      const due = parseDate(t.due_date);
      if (due && daysBetween(now, due) <= 30) soon.push(t); else later.push(t);
    }
  }
  const byDue = (a: Task, b: Task) => (a.due_date ?? '').localeCompare(b.due_date ?? '');
  return [
    { label: 'Start now', tasks: soon.sort(byDue) },
    { label: soon.length ? 'Then' : 'In order', tasks: later.sort(byDue) },
    { label: 'When you get to it', tasks: undated },
    { label: 'Done', tasks: done },
  ].filter((g) => g.tasks.length > 0);
}
