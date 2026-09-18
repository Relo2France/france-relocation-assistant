/**
 * Visa finder
 *
 * Six questions at most, one at a time, ending on a route the member can
 * set with one click. Every fact in the results comes from the knowledge
 * base's visa topics (france-visas.gouv.fr, service-public.gouv.fr); where the
 * knowledge base does not cover a route yet, the result says so rather than
 * guessing. The point is to turn "seven routes, which one?" into a choice
 * the member can defend to a consulate.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { ArrowLeft, ArrowRight, CheckCircle2, RotateCcw, Scale } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { ProfileVisaType } from '@/types';

type Step =
  | 'start'
  | 'visitor-means'
  | 'remote-where'
  | 'work-offer'
  | 'work-level'
  | 'business-kind'
  | 'student-enrolled'
  | 'family-who';

interface Result {
  route: ProfileVisaType;
  name: string;
  official: string;
  why: string;
  means: string[];
  show: string[];
  guide?: string;
  caution?: string;
  gap?: boolean;
}

interface Question {
  title: string;
  hint?: string;
  options: { label: string; note?: string; next: Step | Result }[];
}

const RESULTS = {
  visitor: (why: string, caution?: string): Result => ({
    route: 'visitor',
    name: 'Visitor',
    official: 'VLS-TS visiteur',
    why,
    means: [
      'You sign a declaration not to take up any professional activity in France.',
      'It is issued as a VLS-TS: you validate it online within 3 months of arrival and it then counts as your residence permit for the year.',
      'Renewed at the prefecture each year; the same resources and insurance are checked again.',
    ],
    show: [
      'Resources benchmarked to the French net minimum wage, about €1,478 a month in 2026, as income or the equivalent in savings. Consulates assess case by case; many applicants show more.',
      'Private health insurance for the whole stay, €30,000 minimum cover, no exclusion for pre-existing conditions, repatriation included.',
      'Proof of where you will live, an apostilled birth certificate with a sworn translation, and an FBI background check.',
    ],
    guide: 'visitor-visa-requirements',
    caution,
  }),
  employee: (why: string): Result => ({
    route: 'employee',
    name: 'Work visa',
    official: 'VLS-TS salarié',
    why,
    means: [
      'Your employer applies first for the autorisation de travail through the ANEF portal; the consulate will not look at your file without it.',
      'Officially about two months for the authorisation, then 3 to 6 weeks for the visa. Six months end to end is a fair estimate.',
      'You may work from the day you arrive; validate the visa online within 3 months.',
    ],
    show: [
      'The signed contract or detailed offer, and the employer’s work authorisation approval.',
      'Diplomas with certified translations, proof of accommodation, health insurance, and an apostilled background check.',
    ],
    guide: 'work-visa-salarie',
  }),
  talent: (sub: string, why: string, show: string[]): Result => ({
    route: 'talent_passport',
    name: `Talent Passport, ${sub}`,
    official: 'passeport talent',
    why,
    means: [
      'Valid for up to four years and renewable, instead of one year at a time.',
      'Your spouse or partner gets the right to work automatically.',
      'A path to permanent residence after five years of continuous residence.',
    ],
    show,
    guide: 'talent-passport',
  }),
  student: (why: string): Result => ({
    route: 'student',
    name: 'Student',
    official: 'VLS-TS étudiant',
    why,
    means: [
      'Work is allowed up to 964 hours a year alongside your studies, about 60% of full time.',
      'Validated online within 3 months of arrival; the validation tax is at the reduced rate.',
    ],
    show: [
      'The acceptance or enrolment letter from the French institution.',
      'Financial resources for a student, indexed to the French minimum wage and revised at least yearly, plus accommodation and insurance.',
    ],
  }),
  spouseFrench: (why: string): Result => ({
    route: 'spouse_french',
    name: 'Spouse of a French citizen',
    official: 'vie privée et familiale',
    why,
    means: [
      'You may work from the day you arrive.',
      'No income requirement on your French spouse.',
      'Often issued as a visa marked “carte de séjour à solliciter”: you apply for the residence card at the prefecture within two months of arrival, rather than validating online.',
    ],
    show: [
      'Your marriage certificate, apostilled, with a sworn translation, and your spouse’s French ID.',
      'Proof the relationship is genuine. For a PACS, the partnership must be at least 12 months old with continuous shared life, and expect more scrutiny than a marriage.',
    ],
    guide: 'spouse-and-family-visas',
  }),
  family: (why: string, means: string[], show: string[]): Result => ({
    route: 'family',
    name: 'Family',
    official: 'regroupement familial / famille accompagnante',
    why,
    means,
    show,
    guide: 'spouse-and-family-visas',
  }),
};

const QUESTIONS: Record<Step, Question> = {
  start: {
    title: 'What will you do in France?',
    hint: 'The route follows from this more than from anything else.',
    options: [
      { label: 'Not work at all', note: 'Retired, or living on savings, pensions or investments', next: 'visitor-means' },
      { label: 'Keep my remote job or clients', note: 'Employer or clients outside France', next: 'remote-where' },
      { label: 'Work for a French employer', note: 'A job offer in hand, or one coming', next: 'work-offer' },
      { label: 'Start or run a business, or invest', next: 'business-kind' },
      { label: 'Study', note: 'At a French school or university', next: 'student-enrolled' },
      { label: 'Join my spouse or partner', note: 'A French citizen, or someone already living in France', next: 'family-who' },
    ],
  },
  'visitor-means': {
    title: 'Can you show steady resources at or above the French net minimum wage?',
    hint: 'About €1,478 a month in 2026, as income, or the equivalent for a year in savings.',
    options: [
      {
        label: 'Yes, comfortably',
        next: RESULTS.visitor('You will not work in France and can show the resources the consulate benchmarks against.'),
      },
      {
        label: 'Roughly, or with savings to top it up',
        next: RESULTS.visitor(
          'You will not work in France. The consulate assesses resources case by case, so the file has to make the numbers easy to read.',
          'Applicants near the line are often advised to show more than the benchmark, or to add savings. If the numbers are tight, have a professional look at the file before you book the appointment.'
        ),
      },
      {
        label: 'No',
        next: RESULTS.visitor(
          'You will not work in France, but resources are the whole test for this route.',
          'Without resources at the benchmark the visitor route is unlikely to succeed. If someone in France can host you or you have another basis for the move, an immigration lawyer can tell you whether a different route fits.'
        ),
      },
    ],
  },
  'remote-where': {
    title: 'Will your employer and every client stay outside France?',
    hint: 'France has no separate “digital nomad” visa. Remote work for foreign employers is handled under the visitor route.',
    options: [
      {
        label: 'Yes, entirely outside France',
        next: RESULTS.visitor(
          'France has no digital nomad visa; remote workers with strictly foreign employers or clients apply as visitors.',
          'Some consulates now ask for an employer letter confirming the arrangement is compatible with living in France, and how your time will be split. Once you are French tax resident the salary is taxable in France, so a cross-border tax professional should see this before you move.'
        ),
      },
      { label: 'No, some of the work would be for French companies', note: 'Then it is work in France, not visiting', next: 'work-offer' },
    ],
  },
  'work-offer': {
    title: 'Do you have a written job offer from a French employer?',
    options: [
      { label: 'Yes', next: 'work-level' },
      {
        label: 'Not yet',
        next: RESULTS.employee(
          'Working for a French employer needs the offer first: the employer, not you, applies for the work authorisation, and the visa application follows it. Until there is an offer there is no application to make.'
        ),
      },
    ],
  },
  'work-level': {
    title: 'How is the job classed?',
    hint: 'The Talent Passport is worth checking before settling for the one-year work visa.',
    options: [
      {
        label: 'Highly qualified: gross salary around €66,600 a year or more, or a Master’s with the employer classing it as highly qualified',
        next: RESULTS.talent(
          'qualified employee',
          'A qualifying salary or qualification opens the multi-year route instead of the one-year salarié visa.',
          [
            'The contract showing the salary, and your degree or proof of equivalent experience.',
            'Standard visa documents; the fee is higher than the ordinary long-stay visa, around €225 to €250 depending on the consulate.',
            'Thresholds are revised each January; confirm the current figure on france-visas.gouv.fr before you rely on it.',
          ]
        ),
      },
      {
        label: 'A regular salaried job',
        next: RESULTS.employee('A job offer from a French employer at an ordinary salary is the salarié route.'),
      },
    ],
  },
  'business-kind': {
    title: 'Which is closest?',
    options: [
      {
        label: 'An innovative company, with a recognised project or €30,000 or more to put in',
        next: RESULTS.talent(
          'company founder',
          'Founding an innovative business in France is one of the Talent Passport categories.',
          [
            'Recognition of the project by an incubator or BPI France, or a solid business plan with €30,000 or more invested.',
            'Standard visa documents plus proof you qualify for the category.',
          ]
        ),
      },
      {
        label: 'Investing €300,000 or more in a French company',
        next: RESULTS.talent(
          'investor',
          'A €300,000 investment in a new or existing French company qualifies for the investor category.',
          ['Proof of the investment and of the funds, plus job-creation or maintenance commitments.', 'Standard visa documents.']
        ),
      },
      {
        label: 'Freelance, consulting, or a small business that is not “innovative”',
        next: {
          route: 'entrepreneur',
          name: 'Entrepreneur / profession libérale',
          official: 'VLS-TS entrepreneur / profession libérale',
          why: 'Self-employment in France that does not fit the Talent Passport categories has its own long-stay route.',
          means: ['You register the activity in France after arrival and pay social charges through URSSAF.'],
          show: ['A viable, documented activity and the resources to live on while it starts.'],
          gap: true,
          caution: 'This route is not in our knowledge base yet; it has been flagged for research, and the guide will appear here when it is written from official sources. Until then, an immigration lawyer and an expert-comptable are the people to ask.',
        },
      },
    ],
  },
  'student-enrolled': {
    title: 'Are you accepted or enrolled at a French institution?',
    options: [
      { label: 'Yes', next: RESULTS.student('Enrolment at a French institution is the whole basis of the student route.') },
      {
        label: 'Not yet',
        next: {
          ...RESULTS.student('The student route needs the acceptance letter first; the application follows the enrolment, not the other way round.'),
          caution: 'Once admitted, US students complete the Campus France Études en France procedure before applying for the visa. Your plan walks you through it.',
        },
      },
    ],
  },
  'family-who': {
    title: 'Who are you joining?',
    options: [
      {
        label: 'My spouse or PACS partner is a French citizen',
        next: RESULTS.spouseFrench('Spouses and PACS partners of French citizens have their own route with the right to work on arrival.'),
      },
      {
        label: 'My spouse lives in France on a residence permit, not as a citizen',
        next: RESULTS.family(
          'Joining a non-citizen resident is family reunification, which they apply for from France.',
          [
            'Your spouse applies for regroupement familial on your behalf, and only 18 months after their first residence permit.',
            'They must show income of about 1.3 times the minimum wage, roughly €2,730 a month in 2026, and housing the prefecture judges adequate.',
            'Expect 6 to 15 months after the application is filed.',
          ],
          ['Your apostilled marriage certificate with a sworn translation, and their permit, income and housing evidence.']
        ),
      },
      {
        label: 'My spouse is moving on a Talent Passport or EU Blue Card',
        next: RESULTS.family(
          'Spouses of Talent Passport and EU Blue Card holders come as accompanying family, with automatic work authorisation.',
          ['You get the right to work automatically.', 'Each of you files your own application; file them together so the consulate sees one household.'],
          ['Your apostilled marriage certificate with a sworn translation, and your spouse’s visa application or permit.']
        ),
      },
    ],
  },
};

function isResult(x: Step | Result): x is Result {
  return typeof x !== 'string';
}

export default function VisaFinder({ currentRoute, currentLabel }: { currentRoute: string | null; currentLabel?: string | null }) {
  const [path, setPath] = useState<Step[]>(['start']);
  const [result, setResult] = useState<Result | null>(null);
  const [saved, setSaved] = useState(false);
  const update = useUpdateMemberProfile();
  const { setActiveView, setActiveGuide, setChatDraft, setProfileSection } = usePortalStore();

  const step = path[path.length - 1];
  const q = QUESTIONS[step];

  const choose = (next: Step | Result) => {
    if (isResult(next)) setResult(next);
    else setPath([...path, next]);
  };
  const back = () => {
    if (result) { setResult(null); return; }
    if (path.length > 1) setPath(path.slice(0, -1));
  };
  const restart = () => { setPath(['start']); setResult(null); setSaved(false); };

  const setRoute = (r: Result) => {
    update.mutate({ visa_type: r.route }, { onSuccess: () => setSaved(true) });
  };

  return (
    <section className="card overflow-hidden mb-8 font-sans" aria-label="Visa finder">
      <div className="flex items-center justify-between px-5 py-3.5 bg-card-2 border-b border-rule">
        <div className="flex flex-col">
          <span className="eyebrow">Find your route</span>
          <span className="text-[0.82rem] text-gray-500">{result ? 'Your result' : `Question ${path.length}`} · a couple of minutes, nothing is saved until you say so</span>
        </div>
        <div className="flex gap-1.5" aria-hidden="true">
          {[0, 1, 2].map((i) => (
            <span key={i} className={clsx('w-2 h-2 rounded-full', (result ? 3 : path.length) > i ? 'bg-primary-500' : 'bg-rule')} />
          ))}
        </div>
      </div>

      {!result ? (
        <div className="px-5 py-5">
          <h3 className="font-display text-[1.25rem] font-semibold tracking-[-0.015em] leading-tight m-0"><Jargon text={q.title} /></h3>
          {q.hint ? <p className="text-[0.88rem] text-gray-600 mt-1.5 mb-0"><Jargon text={q.hint} /></p> : null}
          <ul className="mt-4 grid sm:grid-cols-2 gap-2.5">
            {q.options.map((o) => (
              <li key={o.label}>
                <button
                  onClick={() => choose(o.next)}
                  className="w-full text-left rounded-lg border border-rule bg-card px-4 py-3 hover:border-primary-500 hover:bg-primary-100/30 transition-colors flex items-start justify-between gap-3"
                >
                  <span className="flex flex-col">
                    <span className="font-semibold text-[0.95rem] leading-snug">{o.label}</span>
                    {o.note ? <span className="text-[0.8rem] text-gray-500 mt-0.5">{o.note}</span> : null}
                  </span>
                  <ArrowRight className="w-4 h-4 text-primary-500 flex-shrink-0 mt-1" aria-hidden="true" />
                </button>
              </li>
            ))}
          </ul>
          {path.length > 1 ? (
            <button onClick={back} className="mt-4 text-sm text-gray-500 hover:text-ink inline-flex items-center gap-1"><ArrowLeft className="w-3.5 h-3.5" /> Back</button>
          ) : null}
        </div>
      ) : (
        <div className="px-5 py-5 flex flex-col gap-4">
          <div>
            <span className="font-mono text-[0.7rem] uppercase tracking-wide text-primary-500">{result.official}</span>
            <h3 className="font-display text-[1.45rem] font-semibold tracking-[-0.015em] leading-tight mt-1 mb-2">{result.name}</h3>
            <p className="text-[0.95rem] m-0"><Jargon text={result.why} /></p>
          </div>

          {result.caution ? (
            <div className="rounded-lg border-l-2 border-accent-500 bg-accent-100 px-4 py-3 flex gap-2.5">
              <Scale className="w-4 h-4 text-accent-500 flex-shrink-0 mt-0.5" aria-hidden="true" />
              <p className="text-[0.88rem] m-0"><Jargon text={result.caution} /></p>
            </div>
          ) : null}

          <div className="grid sm:grid-cols-2 gap-4">
            <div>
              <span className="eyebrow">What it means</span>
              <ul className="mt-1.5 flex flex-col gap-1.5 text-[0.88rem]">
                {result.means.map((m) => <li key={m} className="flex gap-2"><CheckCircle2 className="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /><span><Jargon text={m} /></span></li>)}
              </ul>
            </div>
            <div>
              <span className="eyebrow">What you will need to show</span>
              <ul className="mt-1.5 flex flex-col gap-1.5 text-[0.88rem]">
                {result.show.map((m) => <li key={m} className="flex gap-2"><span className="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0 mt-2" /><span><Jargon text={m} /></span></li>)}
              </ul>
            </div>
          </div>

          {currentRoute && currentRoute !== 'undecided' && currentRoute !== result.route && !saved ? (
            <p className="text-[0.85rem] text-gray-600 m-0">Your profile currently says <strong>{currentLabel || currentRoute}</strong>. Setting this route replaces it and re-dates your steps.</p>
          ) : null}

          <div className="flex flex-wrap gap-2 items-center">
            {saved ? (
              <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-500"><CheckCircle2 className="w-4 h-4" /> Set as your route. Your stages are filling in.</span>
            ) : (
              <button onClick={() => setRoute(result)} disabled={update.isPending} className="btn btn-primary">{update.isPending ? 'Saving…' : currentRoute === result.route ? 'This is already your route' : 'Set as my route'}</button>
            )}
            {result.guide ? (
              <button onClick={() => { setActiveGuide(result.guide ?? ''); setActiveView('guide'); }} className="btn btn-secondary">Read the full guide</button>
            ) : null}
            <button
              onClick={() => { setChatDraft(`The visa finder suggests the ${result.name} route (${result.official}). Does that fit my situation, and what would the consulate want to see from me?`); setActiveView('chat'); }}
              className="btn btn-ghost"
            >
              Ask about my case
            </button>
            {saved ? (
              <button onClick={() => { setProfileSection('visa'); setActiveView('profile'); }} className="text-sm text-primary-500 hover:text-primary-700">Open my profile</button>
            ) : null}
          </div>

          <p className="text-[0.8rem] text-gray-500 m-0 border-t border-rule-soft pt-3">
            Based on the official requirements as published on france-visas.gouv.fr and service-public.gouv.fr and re-checked weekly. It is not legal advice. If your situation does not fit one answer cleanly, an immigration lawyer can confirm the route before you spend on documents.
          </p>
          <div className="flex gap-4">
            <button onClick={back} className="text-sm text-gray-500 hover:text-ink inline-flex items-center gap-1"><ArrowLeft className="w-3.5 h-3.5" /> Change my last answer</button>
            <button onClick={restart} className="text-sm text-gray-500 hover:text-ink inline-flex items-center gap-1"><RotateCcw className="w-3.5 h-3.5" /> Start over</button>
          </div>
        </div>
      )}
    </section>
  );
}
