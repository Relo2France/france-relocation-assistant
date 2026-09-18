/**
 * DeadlinesView
 *
 * Everything that is dated, across all six stages: what is overdue first,
 * then month by month. The rail's "Deadlines" lands here; the activity feed
 * it used to open is not a deadline list.
 */
import { clsx } from 'clsx';
import { CheckCircle2, Circle } from 'lucide-react';
import { CompactErrorFallback } from '@/components/shared/ErrorBoundary';
import Jargon from '@/components/shared/Jargon';
import { useDashboard, useTasks, useUpdateTaskStatus } from '@/hooks/useApi';
import { JOURNEY, stageForTask } from '@/journey/journey';
import { usePortalStore } from '@/store';
import type { Task } from '@/types';

function monthKey(iso: string): string {
  return iso.slice(0, 7);
}

function monthLabel(key: string): string {
  const d = new Date(`${key}-01T00:00:00Z`);
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric', timeZone: 'UTC' });
}

function dueLabel(iso: string): string {
  const d = new Date(`${iso.slice(0, 10)}T00:00:00Z`);
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', timeZone: 'UTC' }).toUpperCase();
}

export default function DeadlinesView() {
  const { data, isLoading: dashLoading, isError: dashFailed, refetch: refetchDash } = useDashboard();
  const project = data?.project;
  const { data: tasks, isLoading: tasksLoading, isError: tasksFailed, refetch: refetchTasks } = useTasks(project?.id ?? 0);
  const loading = dashLoading || tasksLoading;
  const updateStatus = useUpdateTaskStatus();
  const { setActiveView, setActiveStage, setTaskFilters, setOpenTaskId } = usePortalStore();

  const dated = (tasks ?? []).filter((t): t is Task & { due_date: string } => !!t.due_date);
  const overdue = dated.filter((t) => t.is_overdue && t.status !== 'done').sort((a, b) => a.due_date.localeCompare(b.due_date));
  const rest = dated.filter((t) => !(t.is_overdue && t.status !== 'done')).sort((a, b) => a.due_date.localeCompare(b.due_date));
  const months = new Map<string, Task[]>();
  for (const t of rest) {
    const k = monthKey(t.due_date);
    months.set(k, [...(months.get(k) ?? []), t]);
  }

  const openStage = (id: string) => { setActiveStage(id); setActiveView('stage'); };
  // Opens the step itself in the drawer, not just the list it lives in.
  const openTask = (task: Task) => { setTaskFilters({ stage: project ? stageForTask(task, project) : task.stage, status: null, taskType: null }); setOpenTaskId(task.id); setActiveView('tasks'); };

  return (
    <div className="flex flex-col">
      <header className="px-6 md:px-8 pt-6 pb-5 bg-card border-b border-rule">
        <span className="eyebrow">Across all six stages</span>
        <p className="text-gray-600 max-w-[60ch]">Every dated step, counted back from your move. Overdue first, then month by month.</p>
      </header>

      <div className="flex flex-col gap-5 px-6 md:px-8 py-5">
        {dashFailed || tasksFailed ? (
          <div className="card"><CompactErrorFallback message="Your steps could not be loaded." onRetry={() => { void refetchDash(); void refetchTasks(); }} /></div>
        ) : loading ? (
          <div className="flex flex-col gap-5" role="status" aria-label="Loading deadlines">
            {[1, 2].map((i) => (
              <div key={i} className="card p-5 flex flex-col gap-3">
                <div className="h-5 w-40 bg-gray-200 rounded animate-pulse" />
                <div className="h-4 w-full bg-gray-100 rounded animate-pulse" />
                <div className="h-4 w-3/4 bg-gray-100 rounded animate-pulse" />
              </div>
            ))}
          </div>
        ) : dated.length === 0 ? (
          <div className="card p-6">
            <p className="font-display font-semibold text-lg">Nothing is dated yet.</p>
            <p className="text-sm text-gray-600 mt-1">Set your move date and the plan dates itself.</p>
            <button onClick={() => setActiveView('dashboard')} className="btn btn-primary mt-3">Set your move date</button>
          </div>
        ) : null}

        {overdue.length > 0 ? (
          <div className="card overflow-hidden">
            <div className="flex justify-between items-baseline px-5 py-3.5 bg-card-2 border-b border-rule">
              <span className="font-display font-semibold text-accent-500">Overdue</span>
              <span className="font-mono text-[0.7rem] text-gray-500 uppercase">{overdue.length}</span>
            </div>
            <ul className="divide-y divide-rule-soft">{overdue.map((t) => <DeadlineRow key={t.id} task={t} project={project} updateStatus={updateStatus} openStage={openStage} openTask={openTask} />)}</ul>
          </div>
        ) : null}

        {[...months.entries()].map(([key, list]) => (
          <div key={key} className="card overflow-hidden">
            <div className="flex justify-between items-baseline px-5 py-3.5 bg-card-2 border-b border-rule">
              <span className="font-display font-semibold">{monthLabel(key)}</span>
              <span className="font-mono text-[0.7rem] text-gray-500 uppercase">{list.length}</span>
            </div>
            <ul className="divide-y divide-rule-soft">{list.map((t) => <DeadlineRow key={t.id} task={t} project={project} updateStatus={updateStatus} openStage={openStage} openTask={openTask} />)}</ul>
          </div>
        ))}
      </div>
    </div>
  );
}

function DeadlineRow({ task, project, updateStatus, openStage, openTask }: {
  task: Task;
  project: { target_move_date: string | null } | undefined;
  updateStatus: ReturnType<typeof useUpdateTaskStatus>;
  openStage: (id: string) => void;
  openTask: (task: Task) => void;
}) {
    const done = task.status === 'done';
    const stageId = project ? stageForTask(task, project) : 'decide';
    const stage = JOURNEY.find((s) => s.id === stageId);
    const late = task.is_overdue && !done;
    return (
      <li className="flex items-center gap-3.5 px-5 py-3">
        <button
          onClick={() => updateStatus.mutate({ id: task.id, status: done ? 'todo' : 'done' })}
          aria-label={done ? `Mark "${task.title}" not done` : `Mark "${task.title}" done`}
          className="flex-shrink-0 text-primary-500"
        >
          {done ? <CheckCircle2 className="w-5 h-5" /> : <Circle className={clsx('w-5 h-5', late ? 'text-accent-500' : 'text-gray-300')} />}
        </button>
        <span className={clsx('flex-1 min-w-0 truncate text-[0.95rem]', done ? 'text-gray-500 line-through' : 'font-semibold')}>
          <Jargon text={task.title} />
        </span>
        {stage ? (
          <button onClick={() => openStage(stage.id)} className="badge bg-card-2 text-gray-600 hover:text-ink hidden sm:inline-flex">
            {stage.number} · {stage.name}
          </button>
        ) : null}
        <span className={clsx('font-mono text-[0.7rem]', late ? 'text-accent-500' : 'text-gray-500')}>{done ? 'DONE' : dueLabel(task.due_date ?? '')}</span>
        <button onClick={() => openTask(task)} className="text-sm font-semibold text-primary-500 hover:text-primary-700">Open</button>
      </li>
    );
}
