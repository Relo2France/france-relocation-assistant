/**
 * The walkthrough: each stage told as a story with a beginning, an order and
 * an end, built from the member's own file and their visa route.
 *
 * The rail says where you are. This says what that means: what the stage is
 * for, the order things go in and why, and what has to be true before the
 * next stage starts. Every sentence is tailored from the route, the profile,
 * the tasks, the dossier and the household, so a retired couple from
 * Maryland reads a different page from a salaried engineer in Texas or a
 * spouse joining a French partner. Milestone status is read from the tasks
 * and checklists already on file; where nothing on file speaks to a
 * milestone it shows no tick rather than a guess.
 *
 * Route facts come from the knowledge base topics for each visa (visitor,
 * work, talent, entrepreneur, student, spouse and family), the application
 * timeline, TLScontact and the validation topic, as verified September 2026.
 */
import { US_STATES } from '@/config/profile';
import type { ChecklistItem, FamilyMember, MemberProfile, Project, StateFacts, Task } from '@/types';
import { ARRIVAL_WINDOW_DAYS, stageForTask } from './journey';
import type { JourneyStageId } from './journey';

export type Route = 'visitor' | 'retiree' | 'employee' | 'talent_passport' | 'entrepreneur' | 'student' | 'spouse_french' | 'family' | 'other' | 'undecided';

export interface WalkContext {
  project: Pick<Project, 'target_move_date' | 'visa_type'>;
  /** The route from the profile; the project's visa type is the fallback. */
  route?: string | null;
  profile?: Partial<MemberProfile>;
  tasks: Task[];
  /** The visa-application checklist. */
  dossier: ChecklistItem[];
  /** The pre-departure checklist. */
  departure: ChecklistItem[];
  /** The arrival checklist. */
  arrival: ChecklistItem[];
  members: Pick<FamilyMember, 'name' | 'relationship'>[];
  /** From the dashboard: what the member's state changes. */
  stateFacts?: StateFacts | null;
}

export interface Milestone {
  title: string;
  why: string;
  /** true = on file as done, false = on file and open, null = nothing on file speaks to it */
  done: boolean | null;
}

export interface Walkthrough {
  /** One or two short paragraphs: what this stage is for, for this person. */
  intro: string[];
  /** The order it goes in. */
  milestones: Milestone[];
  /** What has to be true before the next stage starts. */
  readyWhen: string;
}

const DAY = 86_400_000;

function parseDate(iso: string | null | undefined): Date | null {
  if (!iso) return null;
  const d = new Date(`${iso.slice(0, 10)}T00:00:00Z`);
  return Number.isNaN(d.getTime()) ? null : d;
}

function monthOf(d: Date): string {
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' });
}

function dayOf(d: Date): string {
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
}

function stateName(code: string | undefined): string | null {
  if (!code) return null;
  return US_STATES.find((s) => s.value === code)?.label ?? null;
}

function cap(s: string): string {
  return s.charAt(0).toUpperCase() + s.slice(1);
}

/** What the task list says about a milestone: all matching tasks done, some open, or nothing matching. */
function taskState(tasks: Task[], re: RegExp): boolean | null {
  const hits = tasks.filter((t) => re.test(t.title));
  if (hits.length === 0) return null;
  return hits.every((t) => t.status === 'done');
}

function itemState(items: ChecklistItem[], ids: string[]): boolean | null {
  const hits = items.filter((i) => ids.includes(i.id));
  if (hits.length === 0) return null;
  return hits.every((i) => i.status === 'complete' || i.handled_own);
}

/** The first answer on file wins; a later null never overrides an earlier boolean. */
function firstKnown(...states: (boolean | null)[]): boolean | null {
  for (const s of states) if (s !== null) return s;
  return null;
}

export function routeOf(ctx: Pick<WalkContext, 'route' | 'project'>): Route {
  const r = (ctx.route || (ctx.project.visa_type as string) || '') as string;
  const known: Route[] = ['visitor', 'retiree', 'employee', 'talent_passport', 'entrepreneur', 'student', 'spouse_french', 'family', 'other'];
  return (known as string[]).includes(r) ? (r as Route) : 'undecided';
}

export function walkthroughFor(stage: JourneyStageId, ctx: WalkContext): Walkthrough {
  const route = routeOf(ctx);
  const move = parseDate(ctx.project.target_move_date);
  const profile = ctx.profile ?? {};
  const applyStart = move ? new Date(move.getTime() - 120 * DAY) : null;
  const moveStart = move ? new Date(move.getTime() - 30 * DAY) : null;
  const validateBy = move ? new Date(move.getTime() + ARRIVAL_WINDOW_DAYS * DAY) : null;
  const prefectureBy = move ? new Date(move.getTime() + 60 * DAY) : null;
  const applyMonth = applyStart ? monthOf(applyStart) : null;

  const applicants: string = profile.applicants ?? '';
  const withSpouse = applicants === 'spouse' || applicants === 'spouse_kids' || ctx.members.some((m) => m.relationship === 'spouse');
  const spouse = ctx.members.find((m) => m.relationship === 'spouse');
  const spouseName = spouse?.name?.split(' ')[0] || profile.spouse_legal_first_name || '';
  const spouseWord = spouseName || 'your spouse';
  const children = ctx.members.filter((m) => m.relationship === 'child').length || (profile.num_children ?? 0);
  const pets = !!profile.has_pets && profile.has_pets !== 'no';
  const birthState = stateName(profile.birth_state) ?? 'the state that issued it';
  const married = !!profile.has_marriage_cert && profile.has_marriage_cert !== 'na' && profile.has_marriage_cert !== 'no';
  const buying = profile.housing_plan === 'buying';
  const employer = (profile.employer_name ?? '').trim() || 'your employer';
  const talentCategory: string = profile.talent_category ?? '';
  const pacs = profile.relationship_type === 'pacs';
  const multiYear = profile.study_length === 'multi_year';
  const TALENT_NAMES: Record<string, string> = { qualified_employee: 'qualified employee', blue_card: 'EU Blue Card', founder: 'company founder', investor: 'investor', researcher: 'researcher', artist: 'artist' };

  // Tasks that belong to this stage, so a milestone reads only its own stage's file.
  const own = ctx.tasks.filter((t) => stageForTask(t, ctx.project) === stage);
  const T = (re: RegExp) => taskState(own, re);
  const Tall = (re: RegExp) => taskState(ctx.tasks, re);
  const D = (...ids: string[]) => itemState(ctx.dossier, ids);

  // Routes whose visa can come back marked "carte de séjour à solliciter":
  // no online validation, a prefecture appointment within two months instead.
  const maybePrefecture = route === 'spouse_french' || route === 'family' || route === 'talent_passport';
  // Routes usually summoned to OFII after validation (medical visit, and for family routes the integration contract).
  const ofiiVisit = route === 'visitor' || route === 'retiree' || route === 'family' || route === 'spouse_french';

  switch (stage) {
    case 'decide': {
      const visa = (ctx.route || (ctx.project.visa_type as string) || '') as string;
      const chosen = visa !== '' && visa !== 'undecided';
      return {
        intro: [
          'Four answers settle whether and how: which visa route, where in France, who is moving, and roughly when. Each one unlocks the next. The route decides which documents the consulate will ask for and who has to act first; the date turns those documents into deadlines.',
        ],
        milestones: [
          { title: 'Pick the visa route', why: 'Eight long-stay routes. It turns on whether you will work, who is coming, and what you can show. Two of them start with someone else: an employer filing a work authorisation, or a sponsor in France filing with OFII.', done: chosen },
          { title: 'Choose where in France', why: 'Region first, then town. It changes the cost of living, the housing market and which prefecture you will deal with.', done: !!profile.target_location },
          { title: 'Say who is moving', why: 'Each adult applies separately; each child rides on a parent’s file. The count sets how many dossiers you build.', done: (applicants !== '' && applicants !== 'unknown') || ctx.members.length > 0 },
          { title: 'Set a move date, even a rough one', why: 'Everything after this is counted back from it. Change it later and the plan moves with it.', done: !!move },
        ],
        readyWhen: 'A route and a move date are set. Then Prepare starts, and every step on it carries a date.',
      };
    }

    case 'prepare': {
      const apostilled = firstKnown(D('birth-certificate-apostilled', 'marriage-certificate'), T(/apostill/i));
      // Apostilled records are certified copies by definition.
      const civil = { title: 'Certified copies of your civil records', why: `Birth certificate${married && withSpouse ? ', and the marriage certificate' : ''}, ordered fresh from the state. A photocopy from the drawer will not be apostilled.`, done: apostilled === true ? true : firstKnown(T(/civil records|gather (all )?required documents|birth certificate/i)) };
      const fbi = { title: 'The FBI background check, through a channeler', why: 'Ten to fourteen weeks by mail, days through an FBI-approved channeler, then an apostille from the State Department. Consulates want it issued within the last six months.', done: firstKnown(T(/background check/i), D('background-check')) };
      const apostille = { title: `Apostilles from ${birthState}`, why: 'The Secretary of State that issued a record certifies it. One to three weeks in most states. Do it before you leave: the apostilled records serve you for years at the prefecture and CPAM. No translation for the consulate; that comes after arrival.', done: apostilled };
      const insurance = { title: 'Health insurance for the whole first year', why: 'At least €30,000 of medical cover plus repatriation, with the dates written in the policy letter. Ordinary travel insurance is refused.', done: firstKnown(D('travel-insurance'), T(/insurance/i)) };
      const home = { title: 'Somewhere to live for the first months', why: buying ? 'You are buying, which takes longer than the visa. For the application, a booking or a short lease covers the first weeks; the purchase carries on after you arrive.' : 'A lease, a deed, a booking, or a host’s attestation d’hébergement with their ID. A booking covers the first weeks for most consulates.', done: firstKnown(D('proof-accommodation'), T(/where you will live|accommodation/i)) };
      const money = { title: 'Money, pulled in the last weeks', why: route === 'student' ? 'The monthly minimum set by decree, about €877.50 a month from August 2026, for the whole stay: statements, a scholarship letter or a sponsor’s attestation.' : 'Three months of statements and proof of income, dated close to the appointment. Visitors are benchmarked against the French net minimum wage, about €1,478 a month in 2026; showing more avoids questions.', done: firstKnown(D('proof-funds', 'proof-funds-studies'), T(/bank statements|pension income|financial resources/i)) };

      let intro: string[] = [];
      let milestones: Milestone[] = [];
      let readyWhen = '';

      switch (route) {
        case 'employee':
          intro = [
            `This route starts with ${employer}, not with you. They file the work authorisation on the ANEF portal and the regional labour office has about two months to decide; unless the job is on the shortage list they must show no one already in France could fill it. Only once that approval exists can you apply. Your own paperwork runs alongside: the FBI check, the apostilles, your diplomas.`,
          ];
          milestones = [
            { title: `${cap(employer)} files the work authorisation`, why: 'Their application, their timeline: six to ten weeks is common, slower in Paris. Ask for the DREETS approval letter the day it lands.', done: firstKnown(T(/work authorisation|consulate paperwork/i), D('work-authorisation')) },
            fbi, civil, apostille,
            { title: 'Diplomas and proof of qualifications', why: 'The consulate takes them in English; apostilled where its checklist asks.', done: firstKnown(T(/diplomas/i), D('qualifications')) },
            { title: 'The signed contract and the approval, in hand', why: 'The two documents the consulate will not look at your file without. Both come from the employer.', done: firstKnown(T(/signed contract/i), D('work-contract')) },
            insurance, home, money,
          ];
          readyWhen = `The DREETS approval and the signed contract are in hand and every dossier line reads Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}. ${withSpouse ? `${cap(spouseWord)} applies separately for a vie privée et familiale visa; it is not automatic on this route.` : ''}`.trim();
          break;

        case 'talent_passport':
          intro = [
            `Talent is a family of routes, and the category decides the proof: a contract at or above the reference salary (€39,582 gross a year in 2026, €59,373 for the EU Blue Card), a hosting agreement for a researcher, a funded project for a founder, or the investment file. Settle the category first; the rest of the dossier is the standard set.`,
            withSpouse ? `${cap(spouseWord)}${children ? ' and the children' : ''} come under Talent - Famille, on the same application, with work rights for the spouse.` : '',
          ].filter(Boolean);
          milestones = [
            { title: talentCategory && TALENT_NAMES[talentCategory] ? `Your category: ${TALENT_NAMES[talentCategory]}` : 'Confirm the category and its threshold', why: talentCategory === 'qualified_employee' ? 'A contract at or above €39,582 gross a year and a master’s degree or equivalent experience.' : talentCategory === 'blue_card' ? 'A contract of three months or more at or above €59,373 gross a year, and a three-year degree or five years of experience.' : talentCategory === 'founder' ? 'An innovative project recognised by a public body or incubator, a plan, and funds; €30,000 is the cited benchmark.' : talentCategory === 'investor' ? 'At least €300,000 invested in a French company, and the jobs it carries over four years.' : talentCategory === 'researcher' ? 'The convention d’accueil from your host institution is the proof; ask for it early.' : talentCategory === 'artist' ? 'Contracts, income from your work, and recognition: press, prizes, exhibitions.' : 'Thresholds are set by ministerial order and revised; confirm the one in force when your contract is signed. Set the category in your profile and this step names its proof.', done: firstKnown(T(/talent category|assemble the .*(proof|file)|hosting agreement/i)) },
            { title: 'The category proof', why: 'Contract, hosting agreement, business plan with funding, or investment file. This is what makes it a Talent application.', done: firstKnown(T(/category proof/i), D('category-proof')) },
            fbi, civil, apostille,
            { title: 'Diplomas and proof of qualifications', why: 'A master’s degree or equivalent is the usual qualification. In English is fine for the consulate; apostilled where asked.', done: firstKnown(T(/diplomas/i), D('qualifications')) },
            insurance, home, money,
          ];
          readyWhen = `The category proof is complete and every dossier line reads Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}; Talent files often take longer than visitor files to decide.`;
          break;

        case 'entrepreneur':
          intro = [
            'There is no capital threshold on this route; the test is whether the business is viable in France. That means a business plan the consulate can believe, income projections at or above the minimum wage, and, if your profession is regulated, proof you meet the same diploma or experience conditions as a French national. One rule shapes the calendar: the application cannot be filed more than three months before you arrive.',
          ];
          milestones = [
            { title: 'The business plan and viability file', why: 'What the business does, who pays for it, projected income, why it works in France.', done: firstKnown(T(/business plan/i), D('business-plan')) },
            { title: 'Is the profession regulated?', why: 'Law, accounting, medicine, architecture and many trades are. If yours is, the proof of qualification goes in the file.', done: firstKnown(T(/regulated/i), D('professional-qualification')) },
            fbi, civil, apostille,
            { title: 'Income projections at or above the SMIC', why: 'Statements, contracts and letters of intent that make the projections credible.', done: firstKnown(T(/income projections/i), D('income-projection')) },
            insurance, home,
          ];
          readyWhen = `The dossier is finished before the three-month window opens${move ? `, on ${dayOf(new Date(move.getTime() - 90 * DAY))}` : ''}, so the appointment can be booked the day it does.`;
          break;

        case 'student':
          intro = [
            'The acceptance letter comes first and has the longest lead time; the visa follows it. Études en France through Campus France is compulsory only for a list of countries and the United States is not on it, so most US students apply straight through France-Visas, but practice varies by consular district and you confirm it before booking anything. No FBI check on this route.',
          ];
          milestones = [
            { title: 'The acceptance letter', why: 'An official attestation d’inscription from an institution recognised to enrol international students.', done: firstKnown(T(/acceptance letter/i), D('acceptance-letter')) },
            { title: 'Does Campus France apply to you?', why: 'Check usa.campusfrance.org for your district. If a CEF file is required it must be done before the visa appointment.', done: firstKnown(T(/campus france/i)) },
            { title: 'Housing, through CROUS or a lease', why: 'The CROUS confirmation doubles as proof of accommodation.', done: firstKnown(T(/crous/i), D('proof-accommodation')) },
            civil, apostille, insurance, money,
          ];
          readyWhen = `The acceptance letter and proof of funds are in hand and every dossier line reads Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}; the student visa fee is reduced.`;
          break;

        case 'spouse_french':
          intro = [
            'Joining a French spouse is one of the most protected routes: no visa fee, decisions in about four weeks, and refusal only for fraud, an annulled marriage or public order. What the file has to prove is the marriage and the life behind it: the apostilled certificate, your spouse’s French identity, and evidence of a shared life. A PACS needs twelve months of documented cohabitation before it counts.',
          ];
          milestones = [
            pacs
              ? { title: 'Twelve months of PACS cohabitation, documented', why: 'The PACS certificate plus a year of shared life on paper: joint lease, accounts, bills, travel. A March 2025 circular asks that it be effective, stable and not fraudulent.', done: firstKnown(T(/pacs cohabitation/i)) }
              : { title: 'The marriage certificate, apostilled', why: 'A certified copy apostilled by the issuing state; digital apostilles are accepted, and English is fine for the consulate. If you married in France, the French acte de mariage is used.', done: firstKnown(T(/marriage certificate/i), D('marriage-certificate')) },
            { title: 'Your spouse’s French ID', why: 'Passport or identity card, and a certificate of nationality where asked.', done: firstKnown(T(/french id/i), D('spouse-french-id')) },
            { title: 'Proof the relationship is genuine', why: 'Joint accounts, leases, travel, photographs, correspondence. The file shows a shared life, not just a certificate.', done: firstKnown(T(/relationship is genuine/i), D('relationship-proof')) },
            fbi, civil, apostille, insurance, home, money,
          ];
          readyWhen = `Every dossier line reads Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}. Expect the visa to come back either as a VLS-TS or marked carte de séjour à solliciter; the sticker decides what you do on arrival.`;
          break;

        case 'family':
          intro = [
            'Family reunification is filed from inside France by the person you are joining, not by you at a consulate. The sponsor must have lived in France legally for eighteen months on a permit valid a year or more, show stable household income around the minimum wage over the last year, and housing that meets the standards for the zone. OFII checks the file and the prefecture decides; the whole route runs six to fifteen months, so it starts a year or more before the move.',
          ];
          milestones = [
            { title: 'The sponsor files with OFII', why: 'Their application, from France. Nothing on the visa side can start until it is approved.', done: firstKnown(T(/sponsor applies to ofii/i), D('ofii-approval')) },
            { title: 'Proof of residence, income and housing', why: 'Eighteen months of legal residence, twelve months of income, and a home that passes the size and condition check.', done: firstKnown(T(/sponsor gathers/i), D('sponsor-permit')) },
            { title: 'The family records, apostilled', why: 'Marriage certificate and children’s birth certificates; custody papers where a child has another parent.', done: firstKnown(T(/family records/i), D('marriage-certificate')) },
            fbi,
            { title: 'The decision', why: 'Some prefectures take five months at this step alone; an incomplete file adds two to four. Chase through the sponsor.', done: firstKnown(T(/ofii and prefecture decision/i), D('ofii-approval')) },
            insurance, money,
          ];
          readyWhen = 'OFII approval is in hand. Only then do you apply for the visa at the consulate; issuance usually follows within two to four weeks.';
          break;

        case 'other':
          intro = [
            'Intern, temporary or seasonal worker, posted employee, au pair: each of the narrower routes has its own document list, and it is not on file here yet. Run the France-Visas wizard with your real category and confirm the list with the consulate. The shared steps below still apply to every long-stay visa.',
          ];
          milestones = [
            { title: 'The document list for your category', why: 'A tripartite internship agreement stamped by DREETS, a fixed-term contract with the employer’s work permit, a host-family agreement: the wizard and the consulate say which.', done: firstKnown(T(/document list for your category/i)) },
            fbi, civil, apostille, insurance, home, money,
          ];
          readyWhen = `The category’s own documents and every shared dossier line read Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}.`;
          break;

        case 'retiree':
        case 'visitor':
        default: {
          const retired = route === 'retiree';
          intro = [
            `Between now and ${applyMonth ?? 'the application'}, you assemble what the consulate will ask for. The slow pieces come first: the FBI background check, certified copies of your birth certificate${married && withSpouse ? ' and marriage certificate' : ''}, the apostilles from ${birthState}. No French translations for the first application; the consulate in the US takes English, and the sworn translations come after arrival for the prefecture and CPAM. Insurance, proof of where you will live and the money come last, so they are fresh on the day. ${retired ? 'Your pension paperwork is the proof of means: the award letter, the statements, last year’s distributions.' : 'You will also sign a declaration that you will not work in France.'}`,
          ];
          if (withSpouse) intro.push(`${cap(spouseWord)} needs the same set under their own name${children ? `, and the ${children === 1 ? 'child rides' : 'children ride'} on a parent’s file` : ''}. You file separate applications at the same appointment.`);
          if (pets) intro.push('The pet paperwork runs alongside: microchip, rabies shot at least 21 days before travel, then the health certificate in the last ten days.');
          milestones = [
            fbi, civil, apostille,
            retired
              ? { title: 'Pension income, documented', why: 'Social Security award letter, pension statements, IRA or 401(k) balances and distributions. Retirees usually clear the benchmark; the paper has to show it.', done: firstKnown(T(/pension income/i), D('proof-funds')) }
              : { title: 'Proof of financial resources', why: 'Benchmarked against the French net minimum wage, about €1,478 a month in 2026. Many applicants show one and a half to two times that, or a year in savings.', done: firstKnown(T(/financial resources/i), D('proof-funds')) },
            { title: 'The declaration not to work', why: 'The attestation sur l’honneur. Whether remote work for a US employer counts is contested; read the guide before you sign.', done: firstKnown(T(/declaration not to work/i), D('declaration-no-work')) },
            insurance, home, money,
          ];
          readyWhen = `Every line of the dossier reads Ready. Then Apply${applyMonth ? ` starts in ${applyMonth}` : ''}: the France-Visas form, then the TLScontact appointment.`;
        }
      }
      return { intro, milestones, readyWhen };
    }

    case 'apply': {
      const intro = [
        `${applyMonth ? `From ${applyMonth}: ` : ''}fill in the application on France-Visas, book the TLScontact appointment at whichever of the ten US centres suits you, go in person with the full dossier and give fingerprints, then wait. The decision is made by the Consulate General in Washington, usually in two to six weeks, longer in summer. Your passport stays with them meanwhile, so nothing that needs it gets booked in that window.`,
      ];
      if (route === 'entrepreneur') intro.push('On this route the file is accepted no earlier than three months before your arrival date.');
      if (route === 'family') intro.push('With OFII’s approval in hand this is the short part: issuance usually follows the appointment within two to four weeks.');
      if (route === 'spouse_french') intro.push('No fee on this route, and it is fast-tracked: around four weeks.');
      if (withSpouse && route !== 'spouse_french') intro.push(`${cap(spouseWord)} applies at the same appointment with their own file${route === 'talent_passport' ? ', under Talent - Famille' : route === 'employee' ? ', for a vie privée et familiale visa' : ''}.`);
      return {
        intro,
        milestones: [
          { title: 'The application on France-Visas', why: 'One per adult. Print it, sign it, keep the receipt page: it is the first thing they ask for.', done: firstKnown(D('application-form'), T(/france-visas|application form|apply for the visa/i)) },
          { title: 'The TLScontact appointment, booked', why: 'Slots open a few weeks ahead and go fast from May to August. Book as soon as the dossier is complete.', done: firstKnown(T(/tlscontact|appointment.*book|book.*appointment/i)) },
          { title: 'The appointment itself', why: `Every original plus a photocopy, the France-Visas printout, ${route === 'spouse_french' ? 'no visa fee' : route === 'student' ? 'the reduced student fee' : 'the €99 fee'} and about €220 for TLScontact. Fingerprints and photo taken there; the dossier leaves with them.`, done: firstKnown(T(/attend the appointment|biometric|submit/i)) },
          { title: 'The decision', why: 'The passport comes back by courier with the visa sticker in it. Read the mention on the sticker: VLS-TS, or carte de séjour à solliciter.', done: firstKnown(itemState(ctx.departure, ['visa-received']), T(/collect your passport|visa received|decision/i)) },
        ],
        readyWhen: `The passport is back with the visa in it. Check the dates and the spelling before anything else; then Move begins${moveStart ? `, around ${monthOf(moveStart)}` : ''}.`,
      };
    }

    case 'move': {
      return {
        intro: [
          `The last month: ${pets ? 'the pet file, ' : ''}the shipment or the sale of what stays, the loose ends in the States, and the first address. The visa passport and every original from the dossier${pets ? ', and the pet certificate,' : ''} travel in hand luggage. Nothing that mattered at the consulate goes in the hold.`,
        ],
        milestones: [
          { title: 'Flights, on or after the visa start date', why: 'A day early and you enter as a tourist. Keep the boarding pass: it proves the arrival date if the passport is not stamped.', done: firstKnown(itemState(ctx.departure, ['flights-booked']), Tall(/flights/i)) },
          { title: 'The first address', why: 'Where you sleep the first weeks, and where the validation confirmation and the bank card will be posted.', done: firstKnown(itemState(ctx.departure, ['accommodation-first']), Tall(/temporary accommodation/i)) },
          { title: 'What comes with you', why: 'Movers and customs for a household, or a sale and two suitcases. The inventory list does double duty at French customs.', done: firstKnown(Tall(/shipping|movers|packing|household goods/i)) },
          ...(pets ? [{ title: 'The pet file', why: 'Microchip, rabies shot at least 21 days before travel, and the USDA-endorsed health certificate in the last ten days.', done: firstKnown(Tall(/pet/i)) }] : []),
          { title: 'The US side, tidied', why: 'Mail forwarding, three months of prescriptions, the bank told you are travelling, and a US number kept alive for two-factor codes.', done: firstKnown(itemState(ctx.departure, ['prescriptions', 'bank-notify', 'phone-plan']), Tall(/tidy the us side|mail|prescription|notify/i)) },
        ],
        readyWhen: maybePrefecture
          ? 'You land in France with the visa in your passport. If it says VLS-TS the 90-day validation clock starts that day; if it says carte de séjour à solliciter, the two-month prefecture clock does.'
          : `You land in France with the visa in your passport. The ${ARRIVAL_WINDOW_DAYS}-day clock to validate it starts that day.`,
      };
    }

    case 'arrive': {
      const intro: string[] = [];
      if (maybePrefecture) {
        intro.push(`Two clocks, and the sticker says which is yours. A VLS-TS is validated online within ${ARRIVAL_WINDOW_DAYS} days${validateBy ? `, by ${dayOf(validateBy)}` : ''}. A visa marked carte de séjour à solliciter is not validated at all: you apply at the prefecture within two months${prefectureBy ? `, by ${dayOf(prefectureBy)}` : ''}, and your residence right is not settled until the card is issued.`);
      } else {
        intro.push(`You have ${ARRIVAL_WINDOW_DAYS} days from the entry stamp to validate the visa online${validateBy ? `, by ${dayOf(validateBy)}` : ''}. Miss it and the visa stops working as a residence permit.`);
      }
      if (route === 'employee') intro.push('Then a French bank account, which the payroll and everything else hang off. Health cover comes through your employer from the first payslip, with no waiting period, and they must offer a mutuelle.');
      else if (route === 'student') intro.push('Then enrolment in person, a bank account, and student health cover registered online at ameli; no separate student mutual is needed.');
      else if (route === 'entrepreneur') intro.push('Then the business has to exist on paper: registration (a Kbis or company statutes) or affiliation to the self-employed scheme, which the prefecture wants before the card is finalised. An expert-comptable sets the status up.');
      else intro.push(`Then a French bank account, which most other things hang off; health cover, which opens after three months of residence${ofiiVisit ? '; and the OFII convocation, a medical visit' : ''}${route === 'family' || route === 'spouse_french' ? ' and the signing of the integration contract' : ''}.`);
      intro.push(pets ? 'Register with a vet in the first weeks; the passport your pet travelled on needs a French one to leave again.' : 'A French phone number comes first of all: every account sends its codes there.');

      const milestones: Milestone[] = [
        maybePrefecture
          ? { title: 'Read the sticker, then validate online or book the prefecture', why: 'VLS-TS: ANEF portal, passport, visa, arrival date, address, tax paid by card. Carte de séjour à solliciter: prefecture appointment within two months.', done: firstKnown(itemState(ctx.arrival, ['validate-visa']), Tall(/validate your visa|validate visa|visa sticker/i)) }
          : { title: 'Validate the visa online', why: 'On the ANEF portal: passport, visa, arrival date and a French address, then the tax paid by card. The confirmation PDF is your proof of status until the card.', done: firstKnown(itemState(ctx.arrival, ['validate-visa']), Tall(/validate your visa|validate visa/i)) },
        { title: 'A French phone number', why: 'Every French account sends its codes to a French number. Get the SIM in the first days.', done: firstKnown(itemState(ctx.arrival, ['french-sim']), Tall(/french sim/i)) },
        { title: 'A French bank account', why: 'Rent, utilities, health cover and the phone all want a French RIB. Passport, visa and proof of address; the validation confirmation helps.', done: firstKnown(Tall(/bank account/i)) },
        { title: 'A home, and the utilities in your name', why: 'The lease or the deed, then electricity, water and internet. Each bill becomes a proof of address for the next office.', done: firstKnown(Tall(/permanent housing|utilities/i)) },
        { title: 'Sworn translations, now', why: 'The consulate took your records in English; the prefecture and CPAM will not. A traducteur assermenté for the apostilled birth and marriage certificates, from scans, before the health-cover application and the renewal.', done: firstKnown(Tall(/sworn french translations|translate marriage certificate/i)) },
      ];
      if (route === 'employee') milestones.push({ title: 'The first payslip', why: 'It shows the social charges and the health affiliation; query anything missing, and ask about the mutuelle.', done: firstKnown(Tall(/first payslip/i)) });
      else if (route === 'student') milestones.push({ title: 'Enrolment, and student health cover on ameli', why: 'The inscription in person, then etudiant-etranger.ameli.fr with passport, visa, enrolment and a RIB.', done: firstKnown(Tall(/enrollment|student health cover/i)) });
      else if (route === 'entrepreneur') milestones.push({ title: 'The business, registered', why: 'Kbis or statutes, or affiliation to the self-employed scheme, and a professional bank account.', done: firstKnown(Tall(/register the business|business bank account/i)) });
      else milestones.push({ title: 'Health cover, after three months', why: 'Residence-based cover opens once you have lived in France for three months. Your private policy carries you until then.', done: firstKnown(Tall(/health cover|social security|PUMa|CPAM/i)) });
      if (ofiiVisit) milestones.push({ title: 'The OFII convocation', why: `A medical visit${route === 'family' || route === 'spouse_french' ? ' and the signing of the integration contract, with free language classes' : ''}. The letter comes to the address you validated with, weeks to months later.`, done: firstKnown(Tall(/ofii convocation|integration contract/i)) });

      return {
        intro,
        milestones,
        readyWhen: maybePrefecture
          ? 'The visa is validated, or the prefecture file is in, and a bank account is open. Then Settle & renew: health cover, the first tax return, the card.'
          : 'The visa is validated and a bank account is open. Then Settle & renew: the first tax return, the carte Vitale, the renewal.',
      };
    }

    case 'settle':
    default: {
      const renewal = (() => {
        switch (route) {
          case 'talent_passport': return { title: 'The card, and its long run', why: 'Talent cards run up to four years and renew simply. Five years of continuous residence opens the resident card.', done: null };
          case 'student': return multiYear
            ? { title: 'The yearly renewal, then the multi-year card', why: 'Before each academic year: enrolment, funds at the decree minimum, attendance. After the first year, ask for a student card covering the rest of the programme.', done: firstKnown(Tall(/yearly renewals/i), Tall(/renewal/i)) }
            : { title: 'The renewal, if you stay on', why: 'A one-year programme ends with the permit; staying on means a renewal through ANEF two to four months before it expires, with the new enrolment.', done: firstKnown(Tall(/renewal/i)) };
          case 'entrepreneur': return { title: 'The entrepreneur card, with proof of registration', why: 'At the end of the first year: the entrepreneur / profession libérale card, or the multi-year talent card for a project holder. Start four months before expiry.', done: firstKnown(Tall(/entrepreneur carte de séjour/i)) };
          case 'spouse_french': return { title: 'The multi-year card, and the civic exam', why: 'The renewal is a multi-year vie privée et familiale card, and since 2026 a civic exam applies at this step. Start four months before expiry.', done: firstKnown(Tall(/multi-year card/i)) };
          case 'visitor':
          case 'retiree': return { title: 'The renewal, and the four-year wall', why: 'A one-year visiteur card each time, applied for two to four months before expiry. A temporary card is generally not renewed more than three times, so plan a change of status or the five-year resident card before the fourth year.', done: firstKnown(Tall(/renewal/i)) };
          default: return { title: 'The renewal', why: 'Filed online through the prefecture two to four months before the visa expires; appointments are the bottleneck, so start at four months.', done: firstKnown(Tall(/renewal/i)) };
        }
      })();
      const intro = [
        `The long game. The first French tax return comes in the spring after your first year, and the US return continues alongside; the treaty decides who taxes what. ${route === 'employee' ? 'The carte Vitale follows the payroll affiliation.' : route === 'student' ? 'The carte Vitale follows the ameli registration.' : 'The carte Vitale follows the health registration by some weeks to months.'} And the visa has a date on it: ${route === 'talent_passport' ? 'a Talent card runs up to four years, so the renewal is far off.' : 'the renewal goes in through the prefecture in the months before it runs out.'}`,
      ];
      return {
        intro,
        milestones: [
          { title: 'The carte Vitale', why: 'Until it arrives, keep every receipt for reimbursement.', done: firstKnown(Tall(/carte vitale/i)) },
          {
            title: 'The driving licence',
            why: (() => {
              const f = ctx.stateFacts;
              if (f?.licence_exchange === 'yes') return `${f.name} exchanges${f.licence_classes && f.licence_classes !== 'all' ? ` (class ${f.licence_classes} only)` : ''}. Within your first year of residence: apply online at ANTS with the licence, passport, proof of address and a digital photo, pay the €40 stamp, post the original licence by registered mail and keep the slip. The attestation de dépôt keeps you driving while Nantes processes it, three to twelve months. List as of ${f.verified}; confirm on service-public.fr first.`;
              if (f?.licence_exchange === 'no') return `${f.name} has no agreement as of ${f.verified}, so the French licence has to be earned inside your first year: a driving school or candidat libre, the code de la route, then the practical exam. Budget €1,200 to €3,800 and a few months of waits for exam slots; start in the first months. Confirm on service-public.fr; the list changes.`;
              return 'Eighteen US states exchange licences with France and the rest sit the French test; your US licence counts for one year either way. Set your current state in your profile and this line walks you through yours.';
            })(),
            done: firstKnown(Tall(/driving licen/i)),
          },
          { title: 'The first French tax return', why: 'Filed in spring for the previous calendar year, even when the treaty means little tax is due in France.', done: firstKnown(Tall(/tax return/i)) },
          renewal,
          { title: 'Schengen days, if you travel', why: 'As a French resident your days in France do not count. Days in other Schengen countries still do.', done: null },
        ],
        readyWhen: 'There is no finish line here. The file just gets thinner each year.',
      };
    }
  }
}
