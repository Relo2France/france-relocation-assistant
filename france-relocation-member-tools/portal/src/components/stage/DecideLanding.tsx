/**
 * DecideLanding
 *
 * The first stage is not a list of tasks. Someone here is still asking
 * whether they can do this and how, so the page is a path down the page:
 * five steps on one rail, in the order that settles it, the ones already
 * answered ticked and tinted, the next one lit. Each step carries its own
 * way forward. A single column because five things do not fill a grid and
 * an order reads best as a line.
 */
import { clsx } from 'clsx';
import { ArrowRight, Check } from 'lucide-react';
import { useFamilyMembers, useMemberProfile } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { Project } from '@/types';

interface Block {
  id: string;
  title: string;
  body: string;
  done: boolean;
  doneNote?: string;
  primary: { label: string; onClick: () => void };
  secondary?: { label: string; onClick: () => void };
}

export default function DecideLanding({ project, visaType }: { project: Project; visaType: string | null }) {
  const { setActiveView, setProfileSection, setActiveGuide } = usePortalStore();
  const openGuide = (slug: string) => () => { setActiveGuide(slug); setActiveView('guide'); };
  const { data: profile } = useMemberProfile();
  const { data: family } = useFamilyMembers();

  const routeChosen = !!visaType && visaType !== 'undecided';
  const location = (profile as { target_location?: string } | undefined)?.target_location?.trim() ?? '';
  const applicants = (profile as { applicants?: string } | undefined)?.applicants ?? '';
  const familyCount = family?.members?.length ?? 0;
  const whoAnswered = familyCount > 0 || (applicants !== '' && applicants !== 'unknown');
  const dateSet = !!project.target_move_date;

  const blocks: Block[] = [
    {
      id: 'route',
      title: 'Which visa route?',
      body: 'Seven long-stay routes. It depends on whether you will work, who is coming, and what you can show.',
      done: routeChosen,
      doneNote: routeChosen ? project.visa_type_label : undefined,
      primary: { label: routeChosen ? 'Check my route' : 'Find my route', onClick: openGuide('long-stay-visa-overview') },
      secondary: { label: routeChosen ? 'Change it in my profile' : 'I already know it', onClick: () => { setProfileSection('visa'); setActiveView('profile'); } },
    },
    {
      id: 'where',
      title: 'Where in France?',
      body: 'Regions, cost of living, and the questions to ask before you pick a town.',
      done: location !== '',
      doneNote: location || undefined,
      primary: { label: 'Explore France', onClick: () => setActiveView('research') },
    },
    {
      id: 'cost',
      title: 'What will it cost, and what must we show?',
      body: 'The fee, the resources benchmark, the insurance rule, and the lead times from the States.',
      done: false,
      primary: { label: 'What each document needs', onClick: openGuide('visa-application-timeline') },
      secondary: { label: 'The visitor route in full', onClick: openGuide('visitor-visa-requirements') },
    },
    {
      id: 'who',
      title: 'Who is moving?',
      body: 'Each person applies separately. A spouse or a child gets their own file, on the same calendar as yours.',
      done: whoAnswered,
      doneNote: familyCount > 0 ? `You and ${familyCount} other${familyCount === 1 ? '' : 's'}` : whoAnswered ? 'Answered in your profile' : undefined,
      primary: { label: 'Family plans', onClick: () => setActiveView('family') },
    },
    {
      id: 'when',
      title: 'When, roughly?',
      body: 'A target date, even a rough one, turns the stages ahead into dated steps.',
      done: dateSet,
      doneNote: dateSet ? new Date(`${(project.target_move_date ?? '').slice(0, 10)}T00:00:00Z`).toLocaleDateString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' }) : undefined,
      primary: { label: dateSet ? 'Change the date' : 'Set a move date', onClick: () => setActiveView('dashboard') },
    },
  ];

  const nextIndex = blocks.findIndex((b) => !b.done && b.id !== 'cost');
  const questions = blocks.filter((b) => b.id !== 'cost');
  const answered = questions.filter((b) => b.done).length;
  const words = ['none', 'one', 'two', 'three', 'four'];

  return (
    <div className="flex flex-col gap-5">
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <p className="text-gray-600 m-0 max-w-[56ch]">
          Four answers settle it, in this order; the middle step is reading. Take them in turn or jump to the one on your mind.
        </p>
        <span className="font-mono text-xs text-gray-500 whitespace-nowrap">
          {answered === questions.length ? 'All four settled' : `${words[answered]} of four settled`}
        </span>
      </div>

      <ol className="list-none m-0 p-0 flex flex-col">
        {blocks.map((b, i) => {
          const state: 'done' | 'next' | 'later' = b.done ? 'done' : i === nextIndex ? 'next' : 'later';
          const isLast = i === blocks.length - 1;
          const reading = b.id === 'cost';
          return (
            <li key={b.id} className="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-x-4">
              {/* Rail: the marker and the line that carries on to the next step */}
              <div className="flex flex-col items-center" aria-hidden="true">
                <span
                  className={clsx(
                    'w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 border-2',
                    state === 'done' && 'bg-primary-500 border-primary-500 text-white',
                    state === 'next' && 'bg-card border-primary-500 text-primary-500',
                    state === 'later' && 'bg-card border-rule text-gray-500'
                  )}
                >
                  {state === 'done' ? <Check className="w-4 h-4" /> : i + 1}
                </span>
                {!isLast ? <span className={clsx('w-px flex-1 my-1', b.done ? 'bg-primary-500' : 'bg-rule')} /> : null}
              </div>

              {/* The step */}
              <div
                className={clsx(
                  'rounded-lg px-5 py-4 mb-3 flex flex-col gap-2',
                  state === 'done' && 'bg-primary-100/60',
                  state === 'next' && 'bg-card border border-primary-500 shadow-sm',
                  state === 'later' && 'bg-card border border-rule-soft'
                )}
                aria-current={state === 'next' ? 'step' : undefined}
              >
                <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                  <h3 className="font-display text-[1.15rem] font-semibold tracking-[-0.018em] leading-snug m-0">{b.title}</h3>
                  <span className={clsx('font-mono text-[0.68rem] uppercase tracking-wide', state === 'next' ? 'text-primary-500' : state === 'done' ? 'text-primary-700' : 'text-gray-500')}>
                    {state === 'done' ? (b.doneNote ?? 'Settled') : state === 'next' ? 'Next' : reading ? 'Read any time' : 'Later'}
                  </span>
                </div>
                <p className={clsx('text-sm m-0 max-w-[60ch]', state === 'done' ? 'text-gray-600' : 'text-gray-600')}>{b.body}</p>
                <div className="flex flex-wrap gap-2 mt-1">
                  <button onClick={b.primary.onClick} className={clsx(state === 'next' ? 'btn btn-primary' : 'btn btn-secondary', 'gap-1.5')}>
                    {b.primary.label} <ArrowRight className="w-4 h-4" aria-hidden="true" />
                  </button>
                  {b.secondary ? (
                    <button onClick={b.secondary.onClick} className="btn btn-ghost">{b.secondary.label}</button>
                  ) : null}
                </div>
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}
