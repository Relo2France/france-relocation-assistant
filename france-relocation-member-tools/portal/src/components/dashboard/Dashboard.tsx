/**
 * Dashboard — "Where you are"
 *
 * Not a list of every task. Where the person is on the journey, the single
 * next thing to do and why, how far the dossier has got, the road ahead
 * counted back from the move, this stage's own steps, and who is moving.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { AlertTriangle, Calendar, CheckCircle2, Circle } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import Modal from '@/components/shared/Modal';
import { useChecklist, useDashboard, useFamilyMembers, useTasks, useUpdateProject } from '@/hooks/useApi';
import { JOURNEY, currentStage, progressFor, stageById, stageForTask, stageWhen, timeToGo } from '@/journey/journey';
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
  const { setActiveView, setActiveStage, setTaskFilters } = usePortalStore();
  const [showMoveDateModal, setShowMoveDateModal] = useState(false);
  const [newMoveDate, setNewMoveDate] = useState('');
  const updateProject = useUpdateProject();
  const { data: tasks } = useTasks(data?.project.id ?? 0);
  const { data: dossier } = useChecklist('visa-application');
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

  const next = overdue_tasks[0] ?? upcoming_tasks[0] ?? null;
  const then = (overdue_tasks[0] ? overdue_tasks[1] ?? upcoming_tasks[0] : upcoming_tasks[1]) ?? null;

  const stageTasks = (tasks ?? []).filter((t) => stageForTask(t, project) === nowId);
  const open = stageTasks.filter((t) => t.status !== 'done').sort((a, b) => (a.due_date ?? '9').localeCompare(b.due_date ?? '9'));
  const doneCount = stageTasks.length - open.length;
  const listed = [...stageTasks.filter((t) => t.status === 'done').slice(-1), ...open.slice(0, 4)];

  const items = dossier?.items ?? [];
  const ready = items.filter((i) => i.status === 'complete' || i.handled_own).length;
  const members = family?.members ?? [];

  const openStage = (id: string) => { setActiveStage(id); setActiveView('stage'); };
  const openTask = (task: Task) => { setTaskFilters({ stage: task.stage }); setActiveView('tasks'); };

  return (
    <div className="flex flex-col">
      {welcome_banner && <div className="px-6 md:px-8 pt-6"><WelcomeBanner banner={welcome_banner} /></div>}

      <header className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-6 md:px-8 pt-6 pb-2">
        <div className="flex flex-col">
          <span className="eyebrow">Where you are</span>
          <h2 className="font-display text-[1.6rem] font-semibold tracking-[-0.018em] leading-tight">
            {now.name} · {timeToGo(project)}
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

      {/* Next step + dossier */}
      <div className="grid lg:grid-cols-3 gap-5 px-6 md:px-8 pt-4">
        <div className="lg:col-span-2 card p-6 flex flex-col gap-3">
          <span className="eyebrow text-primary-500">Do this next</span>
          {next ? (
            <>
              <h3 className="font-display text-[1.4rem] font-semibold tracking-[-0.018em] leading-snug"><Jargon text={next.title} /></h3>
              {next.description ? <p className="text-gray-600 max-w-[60ch] line-clamp-3"><Jargon text={next.description} /></p> : null}
              <div className="flex flex-wrap items-center gap-3 mt-1">
                <button className="btn btn-primary" onClick={() => openTask(next)}>Open this step</button>
                {next.due_date ? <span className={clsx('font-mono text-[0.7rem] uppercase', next.is_overdue ? 'text-red-600' : 'text-accent-500')}>{dueLabel(next)}</span> : null}
                {then ? <span className="text-sm text-gray-500">Then: {then.title}</span> : null}
              </div>
            </>
          ) : (
            <>
              <h3 className="font-display text-[1.4rem] font-semibold tracking-[-0.018em]">Nothing is due. Set your move date and the plan dates itself.</h3>
              <button className="btn btn-primary self-start" onClick={() => setShowMoveDateModal(true)}>Set your move date</button>
            </>
          )}
        </div>

        <div className="card p-6 flex flex-col gap-3">
          <div className="flex justify-between items-baseline"><span className="eyebrow">Your dossier</span><span className="font-mono text-xs text-gray-500">{ready} / {items.length}</span></div>
          <div className="progress-bar"><div className="progress-bar-fill" style={{ width: `${items.length ? Math.round((ready / items.length) * 100) : 0}%` }} /></div>
          <ul className="flex flex-col gap-2 text-sm">
            {items.slice(0, 4).map((item) => {
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
      </div>

      {/* The road */}
      <div className="flex flex-col gap-3 px-6 md:px-8 pt-5">
        <div className="flex justify-between items-baseline">
          <span className="eyebrow">The road to France</span>
          {project.target_move_date ? (
            <span className="font-mono text-xs text-gray-500">Move date {new Date(`${project.target_move_date.slice(0, 10)}T00:00:00Z`).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })}</span>
          ) : null}
        </div>
        <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5">
          {JOURNEY.map((stage, i) => {
            const state = i < nowIndex ? 'done' : i === nowIndex ? 'now' : 'ahead';
            const p = progressFor(stage, tasks ?? [], project);
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

      {/* This stage + who's moving */}
      <div className="grid lg:grid-cols-3 gap-5 px-6 md:px-8 py-5">
        <div className="lg:col-span-2 card overflow-hidden">
          <div className="flex justify-between items-baseline px-5 py-4 border-b border-rule">
            <span className="font-display font-semibold text-[1.1rem]">
              {nowId === 'decide' ? 'Decide · start here' : `${now.name} · ${stageTasks.length} step${stageTasks.length === 1 ? '' : 's'}${doneCount ? `, ${doneCount} done` : ''}`}
            </span>
            <button onClick={() => openStage(nowId)} className="text-sm font-semibold text-primary-500 hover:text-primary-700">Open stage</button>
          </div>
          {nowId === 'decide' ? (
            <ul className="divide-y divide-rule-soft">
              {[
                ['Which visa route fits us?', 'Seven long-stay routes, one of which is yours.'],
                ['Where in France?', 'Regions, cost of living, the questions to ask first.'],
                ['What will it cost, and what must we show?', 'The fee, the resources benchmark, the insurance rule.'],
                ['Who is moving?', 'Each person gets their own file on the same calendar.'],
                ['When, roughly?', 'A target date turns the stages ahead into dated steps.'],
              ].map(([q, a]) => (
                <li key={q} className="px-5 py-3">
                  <button onClick={() => openStage('decide')} className="text-left w-full">
                    <span className="block text-[0.95rem] font-semibold">{q}</span>
                    <span className="block text-[0.82rem] text-gray-500">{a}</span>
                  </button>
                </li>
              ))}
            </ul>
          ) : listed.length === 0 ? (
            <p className="px-5 py-6 text-sm text-gray-500">No steps in this stage yet.</p>
          ) : (
            <ul className="divide-y divide-rule-soft">
              {listed.map((task) => {
                const done = task.status === 'done';
                return (
                  <li key={task.id} className="flex items-center gap-3.5 px-5 py-3">
                    {done ? <CheckCircle2 className="w-5 h-5 text-primary-500 flex-shrink-0" /> : <Circle className={clsx('w-5 h-5 flex-shrink-0', task.is_overdue ? 'text-accent-500' : 'text-gray-300')} />}
                    <span className={clsx('flex-1 min-w-0 truncate text-[0.95rem]', done ? 'text-gray-500 line-through' : task.id === next?.id ? 'font-semibold' : '')}>
                      <Jargon text={task.title} />
                    </span>
                    <span className={clsx('font-mono text-[0.7rem]', task.is_overdue && !done ? 'text-accent-500' : 'text-gray-500')}>{done ? 'DONE' : dueLabel(task).split(' · ')[0]}</span>
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        <div className="card p-5 flex flex-col gap-3">
          <span className="eyebrow">Who’s moving</span>
          <div className="flex items-center gap-3 py-2 border-b border-rule-soft">
            <div className="w-9 h-9 rounded-full bg-primary-100 text-primary-500 font-bold flex items-center justify-center">You</div>
            <div className="flex flex-col min-w-0"><span className="font-semibold text-[0.95rem]">{project.visa_type_label || 'Visa route not set'}</span><span className="text-[0.8rem] text-gray-500">{items.length ? `${ready} of ${items.length} documents` : 'Your dossier'}</span></div>
          </div>
          {members.map((m) => (
            <div key={m.id} className="flex items-center gap-3 py-2 border-b border-rule-soft">
              <div className="w-9 h-9 rounded-full bg-card-2 text-ink font-bold flex items-center justify-center">{m.name.slice(0, 1).toUpperCase()}</div>
              <div className="flex flex-col min-w-0"><span className="font-semibold text-[0.95rem]">{m.name}</span><span className="text-[0.8rem] text-gray-500 capitalize">{m.relationship} · own visa file</span></div>
            </div>
          ))}
          <p className="text-[0.82rem] text-gray-500 leading-snug">Each person applies separately. Their steps are dated from the same move.</p>
          <button onClick={() => setActiveView('family')} className="text-sm font-semibold text-primary-500 hover:text-primary-700 self-start">
            {members.length ? 'Family plans' : 'Add a family member'}
          </button>
        </div>
      </div>
    </div>
  );
}

function DashboardSkeleton() {
  return (
    <div className="p-6 md:p-8 space-y-5 animate-pulse">
      <div className="h-10 w-72 bg-card-2 rounded" />
      <div className="grid lg:grid-cols-3 gap-5">
        <div className="lg:col-span-2 h-44 bg-card-2 rounded-lg" />
        <div className="h-44 bg-card-2 rounded-lg" />
      </div>
      <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5">
        {Array.from({ length: 6 }).map((_, i) => <div key={i} className="h-20 bg-card-2 rounded-lg" />)}
      </div>
      <div className="grid lg:grid-cols-3 gap-5">
        <div className="lg:col-span-2 h-64 bg-card-2 rounded-lg" />
        <div className="h-64 bg-card-2 rounded-lg" />
      </div>
    </div>
  );
}
