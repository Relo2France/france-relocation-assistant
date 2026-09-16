/**
 * DecideLanding
 *
 * The first stage is not a list of tasks. Someone here is still asking
 * whether they can do this and how, so the page is built around the five
 * questions that settle it, each leading to the place that answers it.
 */
import { ArrowRight } from 'lucide-react';
import { usePortalStore } from '@/store';
import type { Project } from '@/types';

const SITE = 'https://relo2france.com';

interface Question {
  eyebrow: string;
  title: string;
  body: string;
  actions: { label: string; onClick?: () => void; href?: string; primary?: boolean }[];
}

export default function DecideLanding({ project, visaType }: { project: Project; visaType: string | null }) {
  const { setActiveView, setSettingsTab } = usePortalStore();
  const routeChosen = !!visaType && visaType !== 'undecided';

  const questions: Question[] = [
    {
      eyebrow: 'Which route',
      title: routeChosen ? `Your route: ${project.visa_type_label}` : 'Which visa route fits us?',
      body: routeChosen
        ? 'You can change it any time. The stages that follow are built for the route you chose.'
        : 'Seven long-stay routes. Which one fits depends on whether you will work, who is coming, and what you can show. Start with the overview, then set your route in your profile.',
      actions: [
        { label: 'Read the visa overview', href: `${SITE}/guides/long-stay-visa-overview/` },
        { label: routeChosen ? 'Change my route' : 'Set my route', onClick: () => { setSettingsTab('visa-profile'); setActiveView('profile'); }, primary: !routeChosen },
      ],
    },
    {
      eyebrow: 'Where',
      title: 'Where in France?',
      body: 'Regions, cost of living, and the questions to ask before you pick a town.',
      actions: [{ label: 'Explore France', onClick: () => setActiveView('research'), primary: true }],
    },
    {
      eyebrow: 'What it takes',
      title: 'What will it cost, and what must we show?',
      body: 'The fee, the resources benchmark, the insurance rule, and how long the paperwork takes from the States.',
      actions: [
        { label: 'What each document needs', href: `${SITE}/guides/visa-application-timeline/` },
        { label: 'The visitor route in full', href: `${SITE}/guides/visitor-visa-requirements/` },
      ],
    },
    {
      eyebrow: 'Who',
      title: 'Who is moving?',
      body: 'Each person applies separately. A spouse or a child gets their own file, on the same calendar as yours.',
      actions: [{ label: 'Family plans', onClick: () => setActiveView('family'), primary: true }],
    },
    {
      eyebrow: 'When',
      title: project.target_move_date ? 'Your move date is set' : 'When, roughly?',
      body: project.target_move_date
        ? 'Every step in the stages ahead is counted back from it. Change it and the plan moves with it.'
        : 'A target date, even a rough one, is what turns the stages ahead into dated steps.',
      actions: [{ label: project.target_move_date ? 'Change the date' : 'Set a move date', onClick: () => setActiveView('dashboard'), primary: !project.target_move_date }],
    },
  ];

  return (
    <div className="flex flex-col gap-4">
      {questions.map((q) => (
        <div key={q.eyebrow} className="card p-6 flex flex-col gap-2">
          <span className="eyebrow">{q.eyebrow}</span>
          <h3 className="font-display text-[1.25rem] font-semibold tracking-[-0.018em] leading-snug">{q.title}</h3>
          <p className="text-gray-600 max-w-[60ch]">{q.body}</p>
          <div className="flex flex-wrap gap-2 mt-2">
            {q.actions.map((a) =>
              a.href ? (
                <a key={a.label} href={a.href} target="_blank" rel="noreferrer" className={a.primary ? 'btn btn-primary' : 'btn btn-secondary'}>
                  {a.label}
                </a>
              ) : (
                <button key={a.label} onClick={a.onClick} className={a.primary ? 'btn btn-primary gap-1.5' : 'btn btn-secondary gap-1.5'}>
                  {a.label} <ArrowRight className="w-4 h-4" aria-hidden="true" />
                </button>
              )
            )}
          </div>
        </div>
      ))}
    </div>
  );
}
