/**
 * Dashboard — "Where you are"
 *
 * A walk through the move, not a dashboard of widgets. The road across the
 * top says where you are; the stage below is told as a story in the
 * member's own terms (what it is for, the order it goes in, what has to be
 * true before the next one starts), then the next few dated steps, with the
 * dossier and the household alongside. The welcome banner and the
 * professional prompts bookend it.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { AlertTriangle, Calendar, Check, CheckCircle2, Circle } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import Modal from '@/components/shared/Modal';
import ProfessionalsCard from '@/components/shared/ProfessionalsCard';
import { useChecklist, useDashboard, useFamilyMembers, useMemberProfile, useTasks, useUpdateProject } from '@/hooks/useApi';
import { JOURNEY, currentStage, progressFor, stageById, stageForTask, stageWhen, timeToGo } from '@/journey/journey';
import { walkthroughFor } from '@/journey/walkthrough';
import { usePortalStore } from '@/store';
import type { Task } from '@/types';
import WelcomeBanner from './WelcomeBanner';

function dueLabel(task: Task): string {
  if (!task.due_date) return '';
  const d = new Date(`${task.due_date.slice(0, 10)}T00:00:00Z`);
  const when = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', timeZone: 'UTC' }).toUpperCase();
  if (task.is_overdue) return `${when} · OVERDUE`;
  if (task.days_until_due !== null && task.days_until_due >= 0) return `${when} · ${task.days_until_due} DAYS`;
  return when;
}

export default function Dashboard() {
  const { data, isLoading, error } = useDashboard();
  const { setActiveView, setActiveStage, setTaskFilters, setOpenTaskId } = usePortalStore();
  const [showMoveDateModal, setShowMoveDateModal] = useState(false);
  const [newMoveDate, setNewMoveDate] = useState('');
  const updateProject = useUpdateProject();
  const { data: tasks } = useTasks(data?.project.id ?? 0);
  const { data: profile } = useMemberProfile();
  const { data: dossier } = useChecklist('visa-application');
  const { data: departure } = useChecklist('pre-departure');
  const { data: arrival } = useChecklist('arrival');
  const { data: family } = useFamilyMembers();

  if (isLoading) return <DashboardSkeleton />;

  if (error) {
    return (
      <div className="p-6">
        <div className="card p-8 text-center">
          <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <h2 className="text-lg font-semibold text-gray-900 mb-2">Error Loading Dashboard</h2>
          <p className="text-gray-600">{(error as Error).message}</p>
        </div>
      </div>
    );
  }

  if (!data) return null;

  const { project, profile_visa_type, welcome_banner, upcoming_tasks, overdue_tasks } = data;
  const nowId = currentStage(project, profile_visa_type);
  const now = stageById(nowId) ?? JOURNEY[0];
  const nowIndex = JOURNEY.findIndex((s) => s.id === nowId);
  const after = JOURNEY[nowIndex + 1];

  const all = tasks ?? [];
  const byDue = (a: Task, b: Task) => (a.due_date ?? '9').localeCompare(b.due_date ?? '9');
  const stageTasks = all.filter((t) => stageForTask(t, project) === nowId);
  const open = stageTasks.filter((t) => t.status !== 'done').sort(byDue);
  const doneCount = stageTasks.length - open.length;

  // What to do next: anything overdue, then whatever is due soonest, in this
  // stage first and across the whole plan if this stage is quiet. The
  // two-week window the server uses is not a reason to show nothing.
  const openAll = all.filter((t) => t.status !== 'done').sort(byDue);
  const nextList = (() => {
    const seen = new Set<number>();
    const out: Task[] = [];
    for (const t of [...overdue_tasks, ...upcoming_tasks, ...open, ...openAll]) {
      if (!seen.has(t.id)) { seen.add(t.id); out.push(t); }
      if (out.length === 3) break;
    }
    return out;
  })();
  const next = nextList[0] ?? null;

  const items = dossier?.items ?? [];
  const ready = items.filter((i) => i.status === 'complete' || i.handled_own).length;
  const members = family?.members ?? [];

  const walk = walkthroughFor(nowId, {
    project,
    route: profile_visa_type,
    profile,
    tasks: all,
    dossier: items,
    departure: departure?.items ?? [],
    arrival: arrival?.items ?? [],
    members,
    stateFacts: data.state_facts ?? null,
  });

  const moveDateLabel = project.target_move_date
    ? new Date(`${project.target_move_date.slice(0, 10)}T00:00:00Z`).toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' })
    : null;

  const openStage = (id: string) => { setActiveStage(id); setActiveView('stage'); };
  // Opens the step itself in the drawer, not just the list it lives in.
  const openTask = (task: Task) => { setTaskFilters({ stage: stageForTask(task, project), status: null, taskType: null }); setOpenTaskId(task.id); setActiveView('tasks'); };

  return (
    <div className="flex flex-col">
      {welcome_banner && <div className="px-6 md:px-8 pt-6"><WelcomeBanner banner={welcome_banner} /></div>}

      <header className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 md:px-8 pt-6 pb-2">
        <div className="flex flex-col">
          <span className="eyebrow">Stage {now.number} of {JOURNEY.length}{moveDateLabel ? ` · Move date ${moveDateLabel}` : ''}</span>
          <h2 className="font-display text-[1.6rem] font-semibold tracking-[-0.018em] leading-tight">
            {now.name} · {project.target_move_date ? `${timeToGo(project)}${nowIndex < 3 ? ' to the move' : ''}` : 'no move date yet'}
          </h2>
        </div>
        <button
          className="btn btn-primary"
          onClick={() => { setNewMoveDate(project.target_move_date || ''); setShowMoveDateModal(true); }}
        >
          <Calendar className="w-4 h-4" />
          {project.target_move_date ? 'Update move date' : 'Set your move date'}
        </button>
      </header>

      <Modal
        isOpen={showMoveDateModal}
        onClose={() => setShowMoveDateModal(false)}
        title="Your move date"
        size="sm"
        footer={
          <>
            <button className="btn btn-secondary" onClick={() => setShowMoveDateModal(false)}>Cancel</button>
            <button
              className="btn btn-primary"
              onClick={() => {
                if (newMoveDate && project.id) {
                  updateProject.mutate({ id: project.id, data: { target_move_date: newMoveDate } }, { onSuccess: () => setShowMoveDateModal(false) });
                }
              }}
              disabled={updateProject.isPending}
            >
              {updateProject.isPending ? 'Saving...' : 'Save'}
            </button>
          </>
        }
      >
        <div className="space-y-4">
          <p className="text-gray-600">Every deadline is counted back from this date. Change it and the plan moves with it.</p>
          <div>
            <label htmlFor="move-date" className="block text-sm font-medium text-gray-700 mb-1">Target move date</label>
            <input id="move-date" type="date" value={newMoveDate} onChange={(e) => setNewMoveDate(e.target.value)} className="input" min={new Date().toISOString().split('T')[0]} />
          </div>
        </div>
      </Modal>

      {/* The road: the map, first */}
      <div className="flex flex-col gap-3 px-6 md:px-8 pt-4">
        <span className="eyebrow">The road to France</span>
        <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5">
          {JOURNEY.map((stage, i) => {
            const state = i < nowIndex ? 'done' : i === nowIndex ? 'now' : 'ahead';
            const p = progressFor(stage, all, project);
            return (
              <button
                key={stage.id}
                onClick={() => openStage(stage.id)}
                className={clsx(
                  'flex flex-col items-start gap-1.5 p-3.5 rounded-lg text-left transition-colors',
                  state === 'done' && 'bg-primary-100 border border-primary-100',
                  state === 'now' && 'bg-card border-2 border-primary-500',
                  state === 'ahead' && 'bg-card border border-rule hover:bg-card-2'
                )}
                aria-current={state === 'now' ? 'step' : undefined}
              >
                <span className={clsx('font-mono text-[0.68rem]', state === 'ahead' ? 'text-gray-500' : 'text-primary-500')}>
                  {state === 'done' ? 'DONE' : state === 'now' ? `NOW${p.total ? ` · ${p.completed}/${p.total}` : ''}` : stageWhen(stage.id, project) || 'AHEAD'}
                </span>
                <span className="font-semibold text-[0.9rem]">{stage.name}</span>
                <span className="text-[0.78rem] text-gray-500 leading-tight">{stage.blurb}</span>
              </button>
            );
          })}
        </div>
      </div>

      <div className="grid lg:grid-cols-3 gap-5 px-6 md:px-8 pt-5 pb-6">
        {/* The stage, walked through */}
        <div className="lg:col-span-2 flex flex-col gap-5">
          <section className="card p-6 flex flex-col gap-5" aria-labelledby="walk-title">
            <div className="flex flex-col gap-2">
              <span className="eyebrow text-primary-500">{now.name}, in plain words</span>
              <h3 id="walk-title" className="font-display text-[1.35rem] font-semibold tracking-[-0.018em] leading-snug m-0">{now.question}</h3>
              {walk.intro.map((p, i) => (
                <p key={i} className="text-gray-600 m-0 max-w-[64ch] leading-relaxed"><Jargon text={p} /></p>
              ))}
            </div>

            <div className="flex flex-col gap-2">
              <span className="eyebrow">The order it goes in</span>
              <ol className="list-none m-0 p-0 flex flex-col">
                {walk.milestones.map((m, i) => {
                  const last = i === walk.milestones.length - 1;
                  return (
                    <li key={m.title} className="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-x-3">
                      <div className="flex flex-col items-center" aria-hidden="true">
                        <span
                          className={clsx(
                            'w-7 h-7 rounded-full flex items-center justify-center text-[0.72rem] font-bold flex-shrink-0 border-2',
                            m.done === true && 'bg-primary-500 border-primary-500 text-white',
                            m.done === false && 'bg-card border-primary-500 text-primary-500',
                            m.done === null && 'bg-card border-rule text-gray-500'
                          )}
                        >
                          {m.done === true ? <Check className="w-3.5 h-3.5" /> : i + 1}
                        </span>
                        {!last ? <span className={clsx('w-px flex-1 my-0.5', m.done === true ? 'bg-primary-500' : 'bg-rule')} /> : null}
                      </div>
                      <div className={clsx('pb-3.5', last && 'pb-0')}>
                        <span className={clsx('block text-[0.95rem] font-semibold leading-snug', m.done === true && 'text-gray-500')}>
                          <Jargon text={m.title} />{m.done === true ? <span className="font-mono text-[0.65rem] uppercase text-primary-500 ml-2">Done</span> : null}
                        </span>
                        <span className="block text-[0.85rem] text-gray-600 leading-snug mt-0.5 max-w-[60ch]"><Jargon text={m.why} /></span>
                      </div>
                    </li>
                  );
                })}
              </ol>
            </div>

            <div className="rounded-lg bg-primary-100/60 px-4 py-3 flex flex-col gap-0.5">
              <span className="eyebrow text-primary-700">{after ? `Ready for ${after.name} when` : 'From here'}</span>
              <p className="text-[0.92rem] text-ink m-0 max-w-[60ch]"><Jargon text={walk.readyWhen} /></p>
            </div>
          </section>

          {/* Do this next */}
          <section className="card overflow-hidden" aria-labelledby="next-title">
            <div className="flex justify-between items-baseline px-5 py-4 border-b border-rule">
              <span id="next-title" className="font-display font-semibold text-[1.1rem]">Do this next</span>
              <button onClick={() => openStage(nowId)} className="text-sm font-semibold text-primary-500 hover:text-primary-700">
                {stageTasks.length ? `All ${stageTasks.length} steps in ${now.name}${doneCount ? ` · ${doneCount} done` : ''}` : `Open ${now.name}`}
              </button>
            </div>
            {next ? (
              <>
                <div className="px-5 pt-4 pb-3 flex flex-col gap-2">
                  <h3 className="font-display text-[1.25rem] font-semibold tracking-[-0.018em] leading-snug m-0"><Jargon text={next.title} /></h3>
                  {next.description ? <p className="text-gray-600 max-w-[60ch] line-clamp-3 m-0"><Jargon text={next.description} /></p> : null}
                  <div className="flex flex-wrap items-center gap-3 mt-1">
                    <button className="btn btn-primary" onClick={() => openTask(next)}>Open this step</button>
                    {next.due_date ? <span className={clsx('font-mono text-[0.7rem] uppercase', next.is_overdue ? 'text-red-600' : 'text-accent-500')}>{dueLabel(next)}</span> : null}
                  </div>
                </div>
                {nextList.length > 1 ? (
                  <ul className="divide-y divide-rule-soft border-t border-rule-soft">
                    {nextList.slice(1).map((task) => (
                      <li key={task.id}>
                        <button onClick={() => openTask(task)} className="w-full flex items-center gap-3.5 px-5 py-3 text-left hover:bg-card-2 transition-colors">
                          {task.status === 'done' ? <CheckCircle2 className="w-5 h-5 text-primary-500 flex-shrink-0" /> : <Circle className={clsx('w-5 h-5 flex-shrink-0', task.is_overdue ? 'text-accent-500' : 'text-gray-300')} />}
                          <span className="flex-1 min-w-0 truncate text-[0.95rem]"><Jargon text={task.title} /></span>
                          <span className={clsx('font-mono text-[0.7rem]', task.is_overdue ? 'text-accent-500' : 'text-gray-500')}>{dueLabel(task).split(' · ')[0]}</span>
                        </button>
                      </li>
                    ))}
                  </ul>
                ) : null}
              </>
            ) : (
              <div className="px-5 py-5 flex flex-col gap-3">
                <p className="text-gray-600 m-0 max-w-[60ch]">
                  {project.target_move_date
                    ? (all.length ? 'Every step on file is done. Open the stage to add your own, or read the guides for it.' : 'No steps on file yet. Choose a visa route in your profile and the plan writes itself.')
                    : 'Set your move date and every step gets a date counted back from it.'}
                </p>
                {project.target_move_date
                  ? <button className="btn btn-secondary self-start" onClick={() => openStage(nowId)}>Open {now.name}</button>
                  : <button className="btn btn-primary self-start" onClick={() => setShowMoveDateModal(true)}>Set your move date</button>}
              </div>
            )}
          </section>
        </div>

        {/* Alongside: the dossier and the household */}
        <div className="flex flex-col gap-5">
          <div className="card p-6 flex flex-col gap-3">
            <div className="flex justify-between items-baseline"><span className="eyebrow">Your dossier</span><span className="font-mono text-xs text-gray-500">{ready} / {items.length}</span></div>
            <div className="progress-bar"><div className="progress-bar-fill" style={{ width: `${items.length ? Math.round((ready / items.length) * 100) : 0}%` }} /></div>
            <ul className="flex flex-col gap-2 text-sm">
              {items.slice(0, 5).map((item) => {
                const isReady = item.status === 'complete' || item.handled_own;
                return (
                  <li key={item.id} className="flex justify-between gap-3">
                    <span className="truncate"><Jargon text={item.title} /></span>
                    <span className={clsx('flex-shrink-0 font-semibold', isReady ? 'text-primary-500' : item.status === 'in_progress' ? 'text-gray-600' : 'text-gray-400')}>
                      {isReady ? 'Ready' : item.status === 'in_progress' ? 'Waiting' : 'Not started'}
                    </span>
                  </li>
                );
              })}
            </ul>
            <button onClick={() => setActiveView('checklists')} className="text-sm font-semibold text-primary-500 hover:text-primary-700 self-start">
              {items.length ? `See all ${items.length} documents` : 'Open the document list'}
            </button>
          </div>

          <div className="card p-5 flex flex-col gap-3">
            <span className="eyebrow">Who’s moving</span>
            <div className="flex items-center gap-3 py-2 border-b border-rule-soft">
              <div className="w-9 h-9 rounded-full bg-primary-100 text-primary-500 font-bold flex items-center justify-center">You</div>
              <div className="flex flex-col min-w-0"><span className="font-semibold text-[0.95rem]">{data.profile_visa_label || project.visa_type_label || 'Visa route not set'}</span><span className="text-[0.8rem] text-gray-500">{items.length ? `${ready} of ${items.length} documents` : 'Your dossier'}</span></div>
            </div>
            {members.map((m) => (
              <div key={m.id} className="flex items-center gap-3 py-2 border-b border-rule-soft">
                <div className="w-9 h-9 rounded-full bg-card-2 text-ink font-bold flex items-center justify-center">{m.name.slice(0, 1).toUpperCase()}</div>
                <div className="flex flex-col min-w-0"><span className="font-semibold text-[0.95rem]">{m.name}</span><span className="text-[0.8rem] text-gray-500 capitalize">{m.relationship} · own visa file</span></div>
              </div>
            ))}
            <p className="text-[0.82rem] text-gray-500 leading-snug">
              {data.household?.partner && data.household.partner.userId > 0
                ? `${data.household.partner.name.split(' ')[0]} has their own sign-in. Hand steps over from Family plans.`
                : 'Each person applies separately. Their steps are dated from the same move.'}
            </p>
            <button onClick={() => setActiveView('family')} className="text-sm font-semibold text-primary-500 hover:text-primary-700 self-start">
              {members.length ? 'Family plans' : 'Add a family member'}
            </button>
          </div>
        </div>
      </div>

      {(data.professionals ?? []).some((p) => p.stage === nowId) ? (
        <div className="px-6 md:px-8 pb-6">
          <ProfessionalsCard prompts={(data.professionals ?? []).filter((p) => p.stage === nowId)} compact />
        </div>
      ) : null}
    </div>
  );
}

function DashboardSkeleton() {
  return (
    <div className="p-6 md:p-8 space-y-5 animate-pulse">
      <div className="h-10 w-72 bg-card-2 rounded" />
      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5">
        {Array.from({ length: 6 }).map((_, i) => <div key={i} className="h-20 bg-card-2 rounded-lg" />)}
      </div>
      <div className="grid lg:grid-cols-3 gap-5">
        <div className="lg:col-span-2 h-96 bg-card-2 rounded-lg" />
        <div className="h-96 bg-card-2 rounded-lg" />
      </div>
    </div>
  );
}
