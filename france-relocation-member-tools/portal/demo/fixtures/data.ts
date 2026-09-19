/**
 * The demo household: invented, obviously fictional, for screenshots only.
 *
 * Jordan and Sam Ellis, retired, from Asheville, North Carolina, moving to
 * Bordeaux on the long-stay visitor visa on 15 February 2027. Every name,
 * email, date of birth, passport number and address here is made up
 * (example.com addresses, passport X0000000, "Sample Street").
 *
 * Step titles, descriptions, how-tos, the letter catalogue, the glossary and
 * the chat categories come from the plugin's own PHP through
 * scripts/extract-fixtures.php, so the demo shows the product as it is.
 */
import type {
  Activity,
  Checklist,
  ChecklistItem,
  DashboardData,
  FamilyMember,
  Household,
  LettersResponse,
  MemberProfile,
  PortalFile,
  Project,
  SupportTicket,
  SupportTicketsResponse,
  Task,
  TaskHowto,
  TaskStatus,
  User,
} from '@/types';
import chatCategories from './chat-categories.json';
import dossierItems from './dossier-items.json';
import glossary from './glossary.json';
import howtoJson from './howto.json';
import lettersJson from './letters.json';
import templatesJson from './templates.json';

export const MOVE_DATE = '2027-02-15';
const DAY = 86_400_000;

// ------------------------------------------------------------------ dates
/** Midnight UTC today, read from the (demo-frozen) clock. */
function todayUtc(): number {
  const n = new Date();
  return Date.UTC(n.getUTCFullYear(), n.getUTCMonth(), n.getUTCDate());
}
function ymd(ms: number): string {
  return new Date(ms).toISOString().slice(0, 10);
}
function addDays(iso: string, days: number): string {
  return ymd(Date.parse(`${iso}T00:00:00Z`) + days * DAY);
}
function daysFromToday(iso: string): number {
  return Math.round((Date.parse(`${iso}T00:00:00Z`) - todayUtc()) / DAY);
}
/** "YYYY-MM-DD HH:MM:SS", as MySQL hands it to the PHP. */
function mysql(daysAgo: number, time = '10:15:00'): string {
  return `${ymd(todayUtc() - daysAgo * DAY)} ${time}`;
}

// ------------------------------------------------------------------ people
export const ME: User = {
  id: 1,
  username: 'jordan.ellis',
  email: 'jordan@example.com',
  display_name: 'Jordan Ellis',
  first_name: 'Jordan',
  last_name: 'Ellis',
  avatar_url: '',
  roles: ['subscriber'],
  is_admin: false,
  active_memberships: [101],
  is_member: true,
};

const PARTNER_USER_ID = 2;
const PARTNER_MEMBER_ID = 11;

export const HOUSEHOLD: Household = {
  role: 'owner',
  ownerId: 1,
  ownerName: 'Jordan',
  userId: 1,
  partner: { id: PARTNER_MEMBER_ID, name: 'Sam Ellis', email: 'sam@example.com', inviteStatus: 'joined', userId: PARTNER_USER_ID },
};

export const PROFILE: MemberProfile = {
  legal_first_name: 'Jordan',
  legal_middle_name: '',
  legal_last_name: 'Ellis',
  date_of_birth: '1958-04-12',
  nationality: 'US',
  passport_number: 'X0000000',
  passport_expiry: '2033-05-01',
  applicants: 'spouse',
  spouse_legal_first_name: 'Sam',
  spouse_legal_last_name: 'Ellis',
  spouse_date_of_birth: '1960-08-30',
  spouse_name: 'Sam Ellis',
  spouse_work_status: 'retired',
  num_children: 0,
  children_ages: '',
  has_pets: 'no',
  pet_details: '',
  visa_type: 'visitor',
  talent_category: '',
  relationship_type: 'married',
  study_length: '',
  employment_status: 'retired',
  work_in_france: 'no',
  industry: '',
  employer_name: '',
  job_title: '',
  current_country: 'US',
  current_state: 'NC',
  current_city: 'Asheville',
  birth_state: 'NC',
  birth_state_other: '',
  spouse_birth_state: 'NC',
  marriage_state: 'NC',
  marriage_country: 'US',
  target_location: 'Bordeaux',
  housing_plan: 'renting',
  application_location: 'us',
  consulate: 'Atlanta',
  mailing_address: '123 Sample Street\nAsheville, NC 28801',
  timeline: '6_months',
  target_move_date: MOVE_DATE,
  move_date_certainty: 'fixed',
  financial_resources: '200k_500k',
  income_sources: 'Social Security and pensions',
  french_proficiency: 'basic',
  french_mortgage: 'no',
  has_birth_cert: 'yes',
  birth_cert_apostilled: 'no',
  has_marriage_cert: 'yes',
  marriage_cert_apostilled: 'no',
  profile_completion: 92,
  created_at: mysql(61),
  updated_at: mysql(2),
};

export function project(): Project {
  return {
    id: 7,
    user_id: 1,
    title: 'Our move to Bordeaux',
    description: '',
    visa_type: 'visitor',
    visa_type_label: 'Visitor Visa (Long Stay)',
    current_stage: 'documents',
    target_move_date: MOVE_DATE,
    days_until_move: daysFromToday(MOVE_DATE),
    status: 'active',
    status_label: 'Active',
    settings: {},
    created_at: mysql(61),
    updated_at: mysql(2),
  };
}

// ------------------------------------------------------------------ tasks
interface Template {
  title: string;
  description: string;
  stage: string;
  priority: string;
  task_type?: string;
  days_offset: number;
  person?: string;
  professional?: string;
}

const templates = templatesJson as { visa: Template[]; profile: Template[]; spouse: Template[] };
const howtos = howtoJson as unknown as Record<string, TaskHowto>;

/** Where the Ellises are: early October-ish work done, the rest ahead. */
const PROGRESS: Record<string, { status: TaskStatus; doneDaysAgo?: number }> = {
  'Order certified copies of your civil records': { status: 'done', doneDaysAgo: 19 },
  'Request your FBI background check': { status: 'done', doneDaysAgo: 12 },
  'Get passport photos taken (35 x 45 mm)': { status: 'done', doneDaysAgo: 9 },
  'Buy health insurance for the whole first year': { status: 'done', doneDaysAgo: 4 },
  'Get the apostilles from the state': { status: 'in_progress' },
  'Gather spouse documents': { status: 'in_progress' },
  'Write the cover letter': { status: 'in_progress' },
  'Talk to a cross-border tax professional before you move': { status: 'waiting' },
};

/** The partner's steps Sam has taken on. */
const SAM_DOES = new Set(['Gather spouse documents', 'Get marriage certificate apostilled', 'Translate marriage certificate']);

const STATUS_LABEL: Record<TaskStatus, string> = { todo: 'To Do', in_progress: 'In Progress', waiting: 'Waiting', done: 'Done' };
const PRIORITY_LABEL: Record<string, string> = { low: 'Low', medium: 'Medium', high: 'High', urgent: 'Urgent' };

export const TASK_IDS: Record<string, number> = {};

function buildTasks(): Task[] {
  const all = [...templates.visa, ...templates.profile, ...templates.spouse];
  return all.map((tpl, i) => {
    const id = 201 + i;
    TASK_IDS[tpl.title] = id;
    const due = addDays(MOVE_DATE, tpl.days_offset);
    const progress = PROGRESS[tpl.title] ?? { status: 'todo' as TaskStatus };
    const days = daysFromToday(due);
    const metadata: Record<string, unknown> = { from_template: true, days_offset: tpl.days_offset };
    if (tpl.person) metadata.person = tpl.person;
    if (tpl.professional) metadata.professional = tpl.professional;
    if (howtos[tpl.title]) metadata.howto = howtos[tpl.title];
    const type = tpl.task_type ?? 'task';
    const samDoes = SAM_DOES.has(tpl.title);
    return {
      id,
      project_id: 7,
      user_id: 1,
      title: tpl.title,
      description: tpl.description,
      stage: tpl.stage,
      status: progress.status,
      status_label: STATUS_LABEL[progress.status],
      priority: tpl.priority as Task['priority'],
      priority_label: PRIORITY_LABEL[tpl.priority] ?? tpl.priority,
      task_type: type as Task['task_type'],
      task_type_label: type,
      due_date: due,
      days_until_due: days,
      is_overdue: days < 0 && progress.status !== 'done',
      assignee_id: samDoes ? PARTNER_USER_ID : null,
      assignee_name: samDoes ? 'Sam Ellis' : null,
      portal_visible: true,
      sort_order: i,
      parent_task_id: null,
      metadata,
      completed_at: progress.status === 'done' ? mysql(progress.doneDaysAgo ?? 3, '16:40:00') : null,
      created_at: mysql(61, '09:02:00'),
      updated_at: mysql(progress.doneDaysAgo ?? 30),
    };
  });
}

let TASKS: Task[] | null = null;
export function tasks(): Task[] {
  if (!TASKS) TASKS = buildTasks();
  return TASKS;
}

export function updateTask(id: number, patch: Partial<Task>): Task | null {
  const list = tasks();
  const i = list.findIndex((t) => t.id === id);
  if (i < 0) return null;
  const next = { ...list[i], ...patch };
  if (patch.status) {
    next.status_label = STATUS_LABEL[patch.status];
    next.completed_at = patch.status === 'done' ? mysql(0, '12:00:00') : null;
    next.is_overdue = (next.days_until_due ?? 0) < 0 && patch.status !== 'done';
  }
  list[i] = next;
  return next;
}

// ------------------------------------------------------------------ dashboard
export function dashboard(): DashboardData {
  const list = tasks();
  const done = list.filter((t) => t.status === 'done').length;
  const inProgress = list.filter((t) => t.status === 'in_progress').length;
  const overdue = list.filter((t) => t.is_overdue);
  const upcoming = list
    .filter((t) => t.status !== 'done' && t.days_until_due !== null && t.days_until_due >= 0 && t.days_until_due <= 14)
    .sort((a, b) => (a.due_date ?? '').localeCompare(b.due_date ?? ''))
    .slice(0, 5);
  return {
    project: project(),
    task_stats: {
      total: list.length,
      completed: done,
      in_progress: inProgress,
      todo: list.length - done - inProgress,
      overdue: overdue.length,
      percentage: Math.round((done / list.length) * 100),
    },
    profile_visa_type: 'visitor',
    profile_visa_label: 'Visitor Visa (VLS-TS Visiteur)',
    welcome_banner: null,
    household: HOUSEHOLD,
    professionals: [
      {
        id: 'tax-residency',
        kind: 'tax',
        who: 'A cross-border tax professional',
        when: 'Before the year you move',
        stage: 'prepare',
        why: 'US citizens keep filing in the US; France taxes residents on worldwide income. The treaty decides who taxes what, and the year you move is the one to plan.',
        trigger: 'Applies to every US citizen moving to France.',
      },
      {
        id: 'refusal',
        kind: 'law',
        who: 'An immigration lawyer (avocat)',
        when: 'If the consulate refuses, or your history is complicated',
        stage: 'apply',
        why: 'A refusal can be appealed, but on a clock. A prior overstay, a criminal record or a situation that does not fit a route cleanly are all reasons to have a lawyer look before you apply.',
        trigger: 'Applies to every application.',
      },
      {
        id: 'pensions',
        kind: 'tax',
        who: 'A cross-border tax professional',
        when: 'Before you move',
        stage: 'prepare',
        why: 'Social Security, pensions, IRAs and 401(k)s are each treated differently under the treaty. Withdrawals planned around US rules can look different from France.',
        trigger: 'Your profile says you are retired.',
      },
    ],
    state_facts: {
      state: 'NC',
      name: 'North Carolina',
      licence_exchange: 'no',
      licence_classes: '',
      verified: 'September 2026',
      apostille: null,
      vital_records: null,
      tax_domicile: null,
    },
    upcoming_tasks: upcoming,
    overdue_tasks: overdue,
    recent_activity: activity(),
  };
}

function activity(): Activity[] {
  const rows: [string, string, string, number][] = [
    ['task_completed', 'Completed', 'Buy health insurance for the whole first year', 4],
    ['file_uploaded', 'Uploaded', 'Health insurance attestation (both of us).pdf', 4],
    ['letter_drafted', 'Drafted', 'Cover letter', 7],
    ['task_completed', 'Completed', 'Request your FBI background check', 12],
  ];
  return rows.map(([action, label, what, days], i) => ({
    id: 900 + i,
    project_id: 7,
    user_id: 1,
    user_name: 'Jordan Ellis',
    user_avatar: '',
    action,
    action_label: label,
    action_icon: 'check',
    entity_type: 'task',
    entity_id: 0,
    description: `${label} “${what}”`,
    metadata: {},
    created_at: mysql(days),
    relative_time: `${days} days ago`,
  }));
}

// ------------------------------------------------------------------ checklists
const DOSSIER_STATE: Record<string, { done: boolean; note?: string; file_id?: number }> = {
  'passport-valid': { done: true, note: 'Valid until May 2033 · 75 months after your move' },
  'passport-photos': { done: true, file_id: 503 },
  'travel-insurance': { done: true, file_id: 502 },
  'birth-certificate-apostilled': { done: false, note: 'You have the certificate · the apostille is still to do' },
  'cover-letter': { done: false, note: 'Drafted in Documents · print it, sign it, then tick this off', file_id: 601 },
};

export function checklist(type: string): Checklist & { type: string } {
  if (type === 'visa-application') {
    const items: ChecklistItem[] = (dossierItems as { id: string; title: string; lead_time: number }[]).map((raw, i) => {
      const s = DOSSIER_STATE[raw.id] ?? { done: false };
      return {
        id: raw.id,
        checklist_type: 'visa-application',
        title: raw.title,
        lead_time: String(raw.lead_time),
        status: s.done ? 'complete' : 'pending',
        handled_own: false,
        notes: '',
        note: s.note ?? '',
        file_id: s.file_id ?? null,
        completed_at: s.done ? mysql(5) : undefined,
        sort_order: i,
      };
    });
    const done = items.filter((i) => i.status === 'complete').length;
    return { id: type, type, title: 'Visa application dossier', visa_type: 'visitor', items, completion_percentage: Math.round((done / items.length) * 100) } as Checklist & { type: string };
  }
  return { id: type, type, title: type, visa_type: 'visitor', items: [], completion_percentage: 0 } as Checklist & { type: string };
}

// ------------------------------------------------------------------ family
export function family(): Record<string, unknown> {
  const sam: FamilyMember = {
    id: PARTNER_MEMBER_ID,
    name: 'Sam Ellis',
    relationship: 'spouse',
    birthDate: '1960-08-30',
    nationality: 'US',
    visaStatus: 'pending',
    documents: { passport: true, birthCertificate: false, marriageCertificate: false, photos: true },
    email: 'sam@example.com',
    inviteStatus: 'joined',
    invitedUserId: PARTNER_USER_ID,
    source: 'profile',
    createdAt: mysql(55),
    updatedAt: mysql(3),
  };
  return {
    members: [sam],
    feature_enabled: true,
    can_edit: true,
    household: HOUSEHOLD,
    profile: { hasPartner: true, partnerName: 'Sam Ellis', partnerDob: '1960-08-30', children: 0, childrenAges: [] },
    addon: { price: '$20', priceNote: 'once', url: '#', limits: { adults: 1, children: 4 }, configured: true },
  };
}

// ------------------------------------------------------------------ letters and files
type LettersFixture = LettersResponse & { drafts: Record<string, { title: string; text: string; missing: string[]; filename: string }> };
const lettersFixture = lettersJson as unknown as LettersFixture;

export function letters(): LettersResponse {
  const { drafts, ...rest } = lettersFixture;
  const cover = drafts['cover-letter'];
  return {
    ...rest,
    letters: rest.letters.map((l) =>
      l.type === 'cover-letter' && l.person === 'you'
        ? {
            ...l,
            file: {
              id: 601,
              name: 'Cover letter - Jordan Ellis.pdf',
              size: '41 KB',
              text: cover.text,
              missing: cover.missing,
              blanks: 0,
              generated_at: mysql(7, '18:12:00'),
              edited_at: null,
              edited: false,
              stale: false,
              preview_url: '#',
              download_url: '#',
            },
          }
        : l
    ),
  };
}

function file(id: number, name: string, title: string, category: PortalFile['category'], categoryLabel: string, kb: number, daysAgo: number, check: PortalFile['check'], documentType: string): PortalFile {
  return {
    id,
    project_id: 7,
    user_id: 1,
    title,
    document_type: documentType,
    check,
    metadata: null,
    filename: `demo-${id}.pdf`,
    original_name: name,
    file_type: 'pdf',
    file_type_label: 'PDF',
    mime_type: 'application/pdf',
    file_size: kb * 1024,
    file_size_formatted: `${kb} KB`,
    category,
    category_label: categoryLabel,
    description: null,
    is_generated: false,
    entity_type: null,
    entity_id: null,
    thumbnail_url: null,
    preview_url: '#',
    download_url: '#',
    uploaded_by: 1,
    uploaded_by_name: 'Jordan Ellis',
    created_at: mysql(daysAgo),
    updated_at: mysql(daysAgo),
  };
}

export function files(): PortalFile[] {
  return [
    file(501, 'passport-jordan-ellis.pdf', 'Passport · Jordan', 'identity', 'Identity Documents', 812, 20, { status: 'ok', note: 'Valid until May 2033, well past the six months the consulate wants' }, 'passport'),
    file(502, 'health-insurance-attestation.pdf', 'Health insurance attestation', 'medical', 'Medical', 236, 4, { status: 'ok', note: 'Covers both of you for 12 months from 15 Feb 2027, €30,000+ with repatriation' }, 'health-insurance'),
    file(503, 'visa-photos-35x45.pdf', 'Visa photos, 35 x 45 mm', 'visa', 'Visa & Immigration', 1204, 9, { status: 'ok', note: 'French size, plain light background' }, 'photos'),
    file(504, 'birth-certificate-certified-copy.pdf', 'Birth certificate · certified copy', 'identity', 'Identity Documents', 655, 18, { status: 'flag', note: 'Certified copy on file · the North Carolina apostille is still to come' }, 'birth-certificate'),
    file(505, 'fbi-identity-history-summary.pdf', 'FBI background check', 'visa', 'Visa & Immigration', 318, 12, { status: 'ok', note: 'Issued within six months of your appointment window' }, 'background-check'),
  ];
}

// ------------------------------------------------------------------ research, chat, messages
export function savedReports() {
  return {
    reports: [
      { id: 71, location_name: 'Bordeaux', location_type: 'commune', updated_at: mysql(26), download_url: '#' },
      { id: 72, location_name: 'Gironde', location_type: 'department', updated_at: mysql(33), download_url: '#' },
    ],
  };
}

export const CHAT_CATEGORIES = chatCategories;
export const GLOSSARY = glossary;

export function chatHistory() {
  return {
    messages: [
      {
        role: 'user' as const,
        content: 'We are both retired and applying for the visitor visa together. How much do we need to show the consulate, and what counts as proof?',
        timestamp: mysql(1, '09:41:00'),
      },
      {
        role: 'assistant' as const,
        content: [
          '## What the consulate measures you against',
          '',
          'The visitor route has no fixed legal minimum. Consulates benchmark each adult against the French **net minimum wage (SMIC)**, **€1,477.93 a month since 1 June 2026**. For the two of you that is about **€2,956 a month**, or roughly a year of it in savings.',
          '',
          'On your file you show about **€4,549 a month** from Social Security and a pension, plus savings. That clears the benchmark comfortably, so the job is to make the paper say so clearly.',
          '',
          '## What counts as proof',
          '',
          '- **Three months of official statements** for every account you rely on, pulled in the last weeks before the appointment',
          '- **Social Security benefit verification letters**, one for each of you, from your my Social Security account',
          '- **Pension statements** or an award letter from the plan',
          '- **Investment or retirement account statements** (IRA, 401(k)) for savings you draw on',
          '- A **one-page statement of resources** that adds it up in euros, with the exchange rate and date you used',
          '',
          '## Two things that trip people up',
          '',
          '1. **Old statements.** Anything older than three months at the appointment gets asked for again.',
          '2. **Figures that do not match.** The income in your cover letter must be the same as the statements behind it.',
          '',
          'Your **Statement of resources** is ready to draft in Documents, from the income you already entered for the cover letter.',
          '',
          '*Sources: France-Visas, visitor visa requirements; service-public.gouv.fr, SMIC 2026.*',
        ].join('\n'),
        timestamp: mysql(1, '09:41:30'),
      },
    ],
  };
}

const WELCOME: SupportTicket = {
  id: 301,
  user_id: 1,
  subject: 'Welcome to Relo2France, Jordan',
  status: 'open',
  priority: 'normal',
  has_unread_user: false,
  reply_count: 0,
  from_site: true,
  initial_message: 'Your plan is dated from 15 February 2027. Start with Prepare: the apostilles take the longest.',
  created_at: mysql(61, '09:00:00'),
  updated_at: mysql(61, '09:00:00'),
  closed_at: null,
  relative_time: '2 months ago',
  last_reply_at: mysql(61, '09:00:00'),
};

const RULE_CHANGE: SupportTicket = {
  id: 302,
  user_id: 1,
  subject: 'Visitor route: the SMIC benchmark rose on 1 June 2026',
  status: 'open',
  priority: 'normal',
  has_unread_user: true,
  reply_count: 0,
  from_site: true,
  initial_message: 'Consulates now benchmark visitors against €1,477.93 a month per adult. Your statement of resources uses the new figure.',
  created_at: mysql(6, '08:30:00'),
  updated_at: mysql(6, '08:30:00'),
  closed_at: null,
  relative_time: '6 days ago',
  last_reply_at: mysql(6, '08:30:00'),
};

export function supportTickets(): SupportTicketsResponse {
  return { tickets: [RULE_CHANGE, WELCOME], unread_count: 1 };
}

export function supportTicket(id: number) {
  const ticket = [RULE_CHANGE, WELCOME].find((t) => t.id === id) ?? WELCOME;
  return {
    ticket,
    replies: [
      {
        id: id * 10,
        message_id: id,
        user_id: 0,
        content:
          ticket.id === WELCOME.id
            ? 'Welcome, Jordan and Sam. Your plan is dated from your move on 15 February 2027, and each step carries its own date counted back from it.\n\nStart with **Prepare**: the North Carolina apostilles take the longest. Ask about your case any time from the rail.'
            : 'The French net minimum wage rose to **€1,477.93 a month** on 1 June 2026. Consulates use it as the benchmark for visitor applicants, per adult.\n\nYour statement of resources and cover letter already use the new figure.',
        is_admin: true,
        author_name: 'Relo2France',
        created_at: ticket.created_at,
        relative_time: ticket.relative_time,
      },
    ],
  };
}
