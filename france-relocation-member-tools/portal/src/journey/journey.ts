/**
 * The journey: six stages from "could we really do this?" to "time to renew".
 *
 * The database seeds six stages per visa type (planning, documents,
 * application, approval, moving, settling) and every task carries one. The
 * portal shows a different six: Apply absorbs the wait for a decision, and
 * Settling In splits into the first 90 days and everything after. Mapping
 * here, rather than migrating stored data, keeps every existing task where
 * it is and lets the split follow the move date.
 */
import type { Project, StageProgress, Task } from '@/types';

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

function parseDate(iso: string | null | undefined): Date | null {
  if (!iso) return null;
  const d = new Date(`${iso.slice(0, 10)}T00:00:00Z`);
  return Number.isNaN(d.getTime()) ? null : d;
}

function daysBetween(a: Date, b: Date): number {
  return Math.round((b.getTime() - a.getTime()) / 86_400_000);
}

/**
 * Which journey stage a task belongs to. The only ambiguity is "settling":
 * a task due within the arrival window after the move is arriving; later, or
 * undated, it is settling.
 */
export function stageForTask(task: Pick<Task, 'stage' | 'due_date'>, project: Pick<Project, 'target_move_date'>): JourneyStageId {
  if (task.stage !== 'settling') {
    const found = JOURNEY.find((s) => s.dbStages.includes(task.stage));
    return found ? found.id : 'decide';
  }
  const move = parseDate(project.target_move_date);
  const due = parseDate(task.due_date);
  if (!move || !due) return 'settle';
  return daysBetween(move, due) <= ARRIVAL_WINDOW_DAYS ? 'arrive' : 'settle';
}

/**
 * Where the person is now. The project's stored stage is the source of
 * truth, with the settling split decided by today's date against the move.
 */
export function currentStage(project: Pick<Project, 'current_stage' | 'target_move_date'>, now = new Date()): JourneyStageId {
  if (project.current_stage !== 'settling') {
    const found = JOURNEY.find((s) => s.dbStages.includes(project.current_stage));
    return found ? found.id : 'decide';
  }
  const move = parseDate(project.target_move_date);
  if (!move) return 'arrive';
  return daysBetween(move, now) <= ARRIVAL_WINDOW_DAYS ? 'arrive' : 'settle';
}

/** Progress for a journey stage, summed over the database stages it holds. */
export function progressFor(stage: JourneyStage, stages: StageProgress[]): { total: number; completed: number } {
  const own = stages.filter((s) => stage.dbStages.includes(s.slug));
  return own.reduce((acc, s) => ({ total: acc.total + s.total, completed: acc.completed + s.completed }), { total: 0, completed: 0 });
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
    case 'decide': return `${Math.max(1, Math.round(daysBetween(now, move) / 30))} MONTHS OUT`;
    case 'prepare': return month(shift(-180));
    case 'apply': return month(shift(-90));
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
    { label: 'Then', tasks: later.sort(byDue) },
    { label: 'When you get to it', tasks: undated },
    { label: 'Done', tasks: done },
  ].filter((g) => g.tasks.length > 0);
}
