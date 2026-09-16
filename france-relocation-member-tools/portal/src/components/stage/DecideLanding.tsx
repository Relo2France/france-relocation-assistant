/**
 * DecideLanding
 *
 * The first stage is not a list of tasks. Someone here is still asking
 * whether they can do this and how, so the page is a path: five blocks in
 * the order that settles it, arrows between them, the ones already answered
 * ticked off, the next one lit. Each block carries its own way forward.
 */
import { clsx } from 'clsx';
import { ArrowDown, ArrowRight, Check } from 'lucide-react';
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
  const { setActiveView, setSettingsTab, setActiveGuide } = usePortalStore();
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
      primary: { label: routeChosen ? 'Change my route' : 'Set my route', onClick: () => { setSettingsTab('visa-profile'); setActiveView('profile'); } },
      secondary: { label: 'Read the overview', onClick: openGuide('long-stay-visa-overview') },
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
      doneNote: dateSet ? new Date(`${project.target_move_date!.slice(0, 10)}T00:00:00Z`).toLocaleDateString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' }) : undefined,
      primary: { label: dateSet ? 'Change the date' : 'Set a move date', onClick: () => setActiveView('dashboard') },
    },
  ];

  const nextIndex = blocks.findIndex((b) => !b.done && b.id !== 'cost');
  const answered = blocks.filter((b) => b.done).length;

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-baseline justify-between">
        <p className="text-gray-600 m-0">Five questions settle it. Take them in order, or jump to the one on your mind.</p>
        <span className="font-mono text-xs text-gray-500">{answered} of 4 answered</span>
      </div>

      <ol className="flex flex-col md:flex-row md:flex-wrap md:items-stretch gap-3 list-none m-0 p-0">
        {blocks.map((b, i) => {
          const state: 'done' | 'next' | 'later' = b.done ? 'done' : i === nextIndex ? 'next' : 'later';
          const isLast = i === blocks.length - 1;
          return (
            <li key={b.id} className="contents">
              <div
                className={clsx(
                  'flex flex-col gap-2 p-5 rounded-lg md:basis-[calc(33.333%-2.5rem)] md:flex-grow min-w-0',
                  state === 'done' && 'bg-primary-100 border border-primary-100',
                  state === 'next' && 'bg-card border-2 border-primary-500',
                  state === 'later' && 'bg-card border border-rule'
                )}
                aria-current={state === 'next' ? 'step' : undefined}
              >
                <div className="flex items-center justify-between">
                  <span className={clsx('w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold', state === 'done' ? 'bg-primary-500 text-white' : state === 'next' ? 'bg-primary-500 text-white' : 'bg-card-2 text-gray-600')}>
                    {state === 'done' ? <Check className="w-4 h-4" aria-hidden="true" /> : i + 1}
                  </span>
                  <span className={clsx('font-mono text-[0.68rem] uppercase', state === 'next' ? 'text-primary-500' : 'text-gray-500')}>
                    {state === 'done' ? (b.doneNote ?? 'Done') : state === 'next' ? 'Next' : b.id === 'cost' ? 'Read any time' : 'Later'}
                  </span>
                </div>
                <h3 className="font-display text-[1.1rem] font-semibold tracking-[-0.018em] leading-snug">{b.title}</h3>
                <p className="text-sm text-gray-600 m-0 flex-grow">{b.body}</p>
                <div className="flex flex-wrap gap-2 mt-1">
                  <button onClick={b.primary.onClick} className={clsx(state === 'next' ? 'btn btn-primary' : 'btn btn-secondary', 'gap-1.5')}>
                    {b.primary.label} <ArrowRight className="w-4 h-4" aria-hidden="true" />
                  </button>
                  {b.secondary ? (
                    <button onClick={b.secondary.onClick} className="btn btn-ghost">{b.secondary.label}</button>
                  ) : null}
                </div>
              </div>
              {!isLast ? (
                <div className="flex items-center justify-center text-gray-400 md:w-6" aria-hidden="true">
                  <ArrowRight className="hidden md:block w-5 h-5" />
                  <ArrowDown className="md:hidden w-5 h-5" />
                </div>
              ) : null}
            </li>
          );
        })}
      </ol>
    </div>
  );
}
