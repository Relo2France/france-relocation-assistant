/**
 * The walkthrough: each stage told as a story with a beginning, an order and
 * an end, built from the member's own file.
 *
 * The rail says where you are. This says what that means: what the stage is
 * for, the order things go in and why, and what has to be true before the
 * next stage starts. Every sentence is tailored from the profile, the tasks,
 * the dossier and the household, so a retired couple from Maryland reads a
 * different page from a single engineer in Texas. Milestone status is read
 * from the tasks and checklists already on file; where nothing on file
 * speaks to a milestone it simply shows no tick rather than a guess.
 */
import { US_STATES } from '@/config/profile';
import type { ChecklistItem, FamilyMember, MemberProfile, Project, Task } from '@/types';
import { ARRIVAL_WINDOW_DAYS, stageForTask } from './journey';
import type { JourneyStageId } from './journey';

export interface WalkContext {
  project: Pick<Project, 'target_move_date' | 'visa_type'>;
  profile?: Partial<MemberProfile>;
  tasks: Task[];
  /** The visa-application checklist. */
  dossier: ChecklistItem[];
  /** The pre-departure checklist. */
  departure: ChecklistItem[];
  /** The arrival checklist. */
  arrival: ChecklistItem[];
  members: Pick<FamilyMember, 'name' | 'relationship'>[];
  now?: Date;
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

/** The state a task list says about a milestone: all matching tasks done, some open, or nothing matching. */
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

export function walkthroughFor(stage: JourneyStageId, ctx: WalkContext): Walkthrough {
  const move = parseDate(ctx.project.target_move_date);
  const profile = ctx.profile ?? {};
  const applyStart = move ? new Date(move.getTime() - 120 * DAY) : null;
  const moveStart = move ? new Date(move.getTime() - 30 * DAY) : null;
  const validateBy = move ? new Date(move.getTime() + ARRIVAL_WINDOW_DAYS * DAY) : null;

  const applicants: string = profile.applicants ?? '';
  const withSpouse = applicants === 'spouse' || applicants === 'spouse_kids' || ctx.members.some((m) => m.relationship === 'spouse');
  const spouse = ctx.members.find((m) => m.relationship === 'spouse');
  const spouseName = spouse?.name?.split(' ')[0] || profile.spouse_legal_first_name || '';
  const spouseWord = spouseName || 'your spouse';
  const children = ctx.members.filter((m) => m.relationship === 'child').length || (profile.num_children ?? 0);
  const pets = !!profile.has_pets && profile.has_pets !== 'no';
  const birthState = stateName(profile.birth_state) ?? 'the state that issued it';
  const currentState = stateName(profile.current_state);
  const married = !!profile.has_marriage_cert && profile.has_marriage_cert !== 'na' && profile.has_marriage_cert !== 'no';
  const buying = profile.housing_plan === 'buying';

  // Tasks that belong to this stage, so a milestone reads only its own stage's file.
  const own = ctx.tasks.filter((t) => stageForTask(t, ctx.project) === stage);
  const T = (re: RegExp) => taskState(own, re);
  const Tall = (re: RegExp) => taskState(ctx.tasks, re);

  switch (stage) {
    case 'decide': {
      const visa = (ctx.project.visa_type as string) || '';
      const route = visa !== '' && visa !== 'undecided';
      return {
        intro: [
          'Four answers settle whether and how: which visa route, where in France, who is moving, and roughly when. Each one unlocks the next. The route decides which documents the consulate will ask for; the date turns those documents into deadlines.',
        ],
        milestones: [
          { title: 'Pick the visa route', why: 'Seven long-stay routes. It turns on whether you will work, who is coming, and what you can show.', done: route },
          { title: 'Choose where in France', why: 'Region first, then town. It changes the cost of living, the housing market and which prefecture you will deal with.', done: !!profile.target_location },
          { title: 'Say who is moving', why: 'Each adult applies separately; each child rides on a parent’s file. The count sets how many dossiers you build.', done: applicants !== '' && applicants !== 'unknown' || ctx.members.length > 0 },
          { title: 'Set a move date, even a rough one', why: 'Everything after this is counted back from it. Change it later and the plan moves with it.', done: !!move },
        ],
        readyWhen: 'A route and a move date are set. Then Prepare starts, and every step on it carries a date.',
      };
    }

    case 'prepare': {
      const intro = [
        `Between now and ${applyStart ? monthOf(applyStart) : 'the application'}, you assemble what the consulate will ask for. The slow pieces come first: certified copies of your birth certificate${married && withSpouse ? ' and marriage certificate' : ''}, the apostille from ${birthState}, then a sworn French translation of each. Bank statements, insurance and proof of where you will live come last, so they are fresh on the day.`,
      ];
      if (withSpouse) {
        intro.push(`${spouseWord.charAt(0).toUpperCase() + spouseWord.slice(1)} needs the same set under their own name${children ? `, and the ${children === 1 ? 'child rides' : 'children ride'} on a parent’s file` : ''}. You file separate applications on the same day.`);
      }
      if (pets) intro.push('The pet paperwork runs alongside: microchip, rabies shot, then the health certificate in the last ten days before you fly.');
      return {
        intro,
        milestones: [
          { title: 'Certified copies of your civil records', why: `Birth certificate${married && withSpouse ? ', and the marriage certificate' : ''}, ordered fresh from the state. A photocopy from the drawer will not be apostilled.`, done: firstKnown(T(/gather (all )?required documents|birth certificate|marriage certificate/i)) },
          { title: `Apostilles from ${birthState}`, why: 'The Secretary of State that issued a record certifies it. Weeks, not days, and it cannot start until the copies are in hand: this is the long pole.', done: firstKnown(itemState(ctx.dossier, ['birth-certificate-apostilled', 'marriage-certificate']), T(/apostill/i)) },
          { title: 'Sworn French translations', why: 'A traducteur assermenté, not any translator. Send scans the day the apostilles come back.', done: firstKnown(itemState(ctx.dossier, ['certified-translations']), T(/translat/i)) },
          { title: 'Health insurance for the whole first year', why: 'Medical costs and repatriation, with the dates and the cover written in the policy letter you hand over.', done: firstKnown(itemState(ctx.dossier, ['travel-insurance']), T(/insurance/i)) },
          { title: 'Somewhere to live for the first months', why: buying ? 'You are buying, which takes longer than the visa. For the application, a booking or a short lease covers the first weeks; the purchase carries on after you arrive.' : 'A lease, a deed, or a host’s attestation d’hébergement. A booking often covers the first weeks; the guide says what each consulate accepts.', done: firstKnown(itemState(ctx.dossier, ['proof-accommodation']), T(/accommodation/i)) },
          { title: 'Money, pulled in the last weeks', why: 'Three months of statements and proof of income, dated close to the appointment. Older statements get asked for again.', done: firstKnown(itemState(ctx.dossier, ['proof-funds']), T(/pension income|financial resources|bank statement/i)) },
        ],
        readyWhen: `Every line of the dossier reads Ready and you know which consulate covers ${currentState ?? 'your state'}. Then Apply starts${applyStart ? ` in ${monthOf(applyStart)}` : ''}.`,
      };
    }

    case 'apply': {
      const intro = [
        `${applyStart ? `From ${monthOf(applyStart)}: ` : ''}fill in the application on France-Visas, book the appointment with the visa centre that serves ${currentState ?? 'your state'}, go in person with the full dossier and give fingerprints, then wait. Your passport stays with them while they decide, so nothing that needs it gets booked in that window.`,
      ];
      if (withSpouse) intro.push(`${spouseWord.charAt(0).toUpperCase() + spouseWord.slice(1)} applies at the same appointment with their own file.`);
      return {
        intro,
        milestones: [
          { title: 'The application on France-Visas', why: 'One per adult. Print it, sign it, and keep the receipt page: it is the first thing they ask for.', done: firstKnown(itemState(ctx.dossier, ['application-form']), T(/france-visas|application form|apply for/i)) },
          { title: 'The appointment, booked', why: 'Slots open a few weeks ahead and go fast in spring and summer. Book as soon as the dossier is complete.', done: firstKnown(T(/appointment|book.*(consulate|visa)/i)) },
          { title: 'The appointment itself', why: 'Every original plus a photocopy of each, the fee, and fingerprints. The dossier leaves with them.', done: firstKnown(T(/attend|biometric|submit/i)) },
          { title: 'The decision', why: 'Days to a few weeks. The passport comes back by courier with the visa sticker inside.', done: firstKnown(itemState(ctx.departure, ['visa-received']), T(/visa received|decision/i)) },
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
          { title: 'Flights', why: 'Land on or after the date the visa starts; a day early and you enter as a tourist.', done: firstKnown(itemState(ctx.departure, ['flights-booked']), Tall(/flight/i)) },
          { title: 'The first address', why: 'Where you sleep the first weeks, and where the validation letter and the bank card will be posted.', done: firstKnown(itemState(ctx.departure, ['accommodation-first']), Tall(/temporary accommodation/i)) },
          { title: 'What comes with you', why: 'Movers and customs for a household, or a sale and two suitcases. The inventory list does double duty at French customs.', done: firstKnown(Tall(/shipping|movers|packing|household goods/i)) },
          ...(pets ? [{ title: 'The pet file', why: 'Microchip, rabies shot at least 21 days before travel, and the USDA-endorsed health certificate in the last ten days.', done: firstKnown(Tall(/pet/i)) }] : []),
          { title: 'The US side, tidied', why: 'Mail forwarding, three months of prescriptions, the bank told you are travelling, and a US phone number kept alive for two-factor codes.', done: firstKnown(itemState(ctx.departure, ['prescriptions', 'bank-notify', 'phone-plan']), Tall(/mail|prescription|notify/i)) },
        ],
        readyWhen: `You land in France with the visa in your passport. The ${ARRIVAL_WINDOW_DAYS}-day clock to validate it starts that day.`,
      };
    }

    case 'arrive': {
      return {
        intro: [
          `You have ${ARRIVAL_WINDOW_DAYS} days from arrival to validate the visa online${validateBy ? `, by ${dayOf(validateBy)}` : ''}. Miss it and the visa stops working as a residence permit. After that comes a French bank account, which most other things hang off; then health cover, which opens after three months of residence; then the local rhythm: a phone plan, a doctor, the mairie.`,
        ],
        milestones: [
          { title: 'Validate the visa online', why: 'On the ANEF site, with the passport, the visa, the arrival date and a French address. The receipt is your proof of status until the card.', done: firstKnown(itemState(ctx.arrival, ['validate-visa']), Tall(/validate visa/i)) },
          { title: 'A French bank account', why: 'Rent, utilities, health cover and the phone all want a French RIB. Bring the passport, the visa and proof of address.', done: firstKnown(Tall(/bank account/i)) },
          { title: 'A home, and the utilities in your name', why: 'The lease or the deed, then electricity, water and internet. Each bill becomes a proof of address for the next office.', done: firstKnown(Tall(/permanent housing|utilities/i)) },
          { title: 'Health cover, after three months', why: 'Residence-based cover opens once you have lived in France for three months. Your private policy carries you until then.', done: firstKnown(Tall(/health cover|social security|PUMa|CPAM/i)) },
          { title: 'A French phone number', why: 'Every French account sends its codes to a French number. Get the SIM in the first week.', done: firstKnown(itemState(ctx.arrival, ['french-sim'])) },
        ],
        readyWhen: 'The visa is validated and a bank account is open. Then Settle & renew: the first tax return, the carte Vitale, the renewal.',
      };
    }

    case 'settle':
    default: {
      return {
        intro: [
          'The long game. The first French tax return comes in the spring after your first year, and the US return continues alongside; the treaty decides who taxes what. The carte Vitale follows the health registration. And the visa has a date on it: the renewal goes in through the prefecture online in the months before it runs out.',
        ],
        milestones: [
          { title: 'The carte Vitale', why: 'Arrives some weeks after health cover is granted. Until then, keep every receipt for reimbursement.', done: firstKnown(Tall(/carte vitale/i)) },
          { title: 'The driving licence', why: 'Some US states exchange licences with France and some do not, and the window is your first year. Check yours early.', done: firstKnown(Tall(/driving licen/i)) },
          { title: 'The first French tax return', why: 'Filed in spring for the previous calendar year, even when the treaty means little tax is due in France.', done: firstKnown(Tall(/tax return/i)) },
          { title: 'The renewal', why: 'A carte de séjour from the prefecture, applied for online before the visa expires. The dossier is lighter than the first one.', done: firstKnown(Tall(/renew|titre de séjour|carte de séjour/i)) },
          { title: 'Schengen days, if you travel', why: 'As a French resident your days in France do not count. Days in other Schengen countries still do.', done: null },
        ],
        readyWhen: 'There is no finish line here. The file just gets thinner each year.',
      };
    }
  }
}
