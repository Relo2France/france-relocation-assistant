/**
 * StageView
 *
 * One stage of the journey: its question, its steps in the order the lead
 * times demand, who it applies to, and the guides that feed it. The person
 * switcher is the family: each member has their own file on the same
 * calendar.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { ArrowRight, CheckCircle2, Circle } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import { AssignSelect, PersonChip, personOf } from '@/components/family/Assign';
import ProfessionalsCard from '@/components/shared/ProfessionalsCard';
import { useDashboard, useFamilyMembers, useTasks, useUpdateTaskStatus } from '@/hooks/useApi';
import { JOURNEY, currentStage, groupByLeadTime, progressFor, stageById, stageForTask, timeToGo } from '@/journey/journey';
import { usePortalStore } from '@/store';
import type { Task } from '@/types';
import DecideLanding from './DecideLanding';

function dueLabel(task: Task): string {
  if (task.status === 'done') return 'DONE';
  if (!task.due_date) return '';
  const d = new Date(`${task.due_date.slice(0, 10)}T00:00:00Z`);
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', timeZone: 'UTC' }).toUpperCase();
}

export default function StageView() {
  const { activeStage, setActiveView, setActiveStage, setTaskFilters, setActiveGuide } = usePortalStore();
  const { data } = useDashboard();
  const stage = stageById(activeStage) ?? JOURNEY[0];
  const project = data?.project;
  const nowStage = project ? currentStage(project, data?.profile_visa_type) : 'decide';
  const { data: tasks } = useTasks(project?.id ?? 0);
  const { data: family } = useFamilyMembers();
  const updateStatus = useUpdateTaskStatus();
  const [person, setPerson] = useState<'me' | number>('me');

  const members = family?.members ?? [];
  const household = data?.household ?? family?.household ?? null;
  const inStage = (tasks ?? []).filter((t) => project && stageForTask(t, project) === stage.id);
  // The person switcher narrows to that person's steps: the partner's, or a
  // child's (children share their steps unless one is named on a task).
  const forPerson = (t: Task) => {
    const p = personOf(t);
    const m = person === 'me' ? undefined : members.find((x) => x.id === person);
    if (person === 'me' || !m) return members.length === 0 || p === 'you' || p === '';
    if (m.relationship === 'spouse') return p === 'partner';
    return p === 'children' || p === `child:${m.id}`;
  };
  const own = inStage.filter(forPerson);
  const groups = groupByLeadTime(own);
  const progress = project ? progressFor(stage, tasks ?? [], project) : { total: 0, completed: 0 };
  const pct = progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0;
  const nextHard = own.filter((t) => t.status !== 'done' && t.due_date).sort((a, b) => (a.due_date ?? '').localeCompare(b.due_date ?? ''))[0];
  const isNow = stage.id === nowStage;
  const professionals = (data?.professionals ?? []).filter((p) => p.stage === stage.id);

  return (
    <div className="flex flex-col">
      <header className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 px-6 md:px-8 pt-6 pb-5 bg-card border-b border-rule">
        <div className="flex flex-col gap-1.5">
          <span className="eyebrow">
            Stage {stage.number} of 6{project ? ` · ${isNow ? timeToGo(project) : stage.blurb}` : ''}
          </span>
          <h2 className="font-display text-[1.75rem] font-semibold tracking-[-0.018em] leading-tight">{stage.name}</h2>
          <p className="text-ink/80 max-w-[64ch]">{stage.question}</p>
        </div>
        {stage.id !== 'decide' ? (
        <div className="flex flex-col items-start md:items-end gap-1.5 md:min-w-[220px]">
          <div className="flex justify-between w-full md:w-[220px]"><span className="eyebrow">Progress</span><span className="font-mono text-xs text-gray-500">{progress.completed} / {progress.total}</span></div>
          <div className="progress-bar w-full md:w-[220px]"><div className="progress-bar-fill" style={{ width: `${pct}%` }} /></div>
          {nextHard ? <span className="font-mono text-[0.7rem] uppercase text-accent-500">Next hard date · {dueLabel(nextHard)}</span> : null}
        </div>
        ) : null}
      </header>

      {members.length > 0 && stage.id !== 'decide' ? (
        <div className="flex flex-wrap gap-2 px-6 md:px-8 pt-4">
          <button onClick={() => setPerson('me')} className={clsx('badge h-7 px-3', person === 'me' ? 'bg-primary-500 text-white' : 'bg-card border border-rule text-ink')}>You</button>
          {members.map((m) => (
            <button key={m.id} onClick={() => setPerson(m.id)} className={clsx('badge h-7 px-3', person === m.id ? 'bg-primary-500 text-white' : 'bg-card border border-rule text-ink')}>
              {m.name}
            </button>
          ))}
          <button onClick={() => setActiveView('family')} className="badge h-7 px-3 bg-card border border-rule text-gray-500">+ Add a person</button>
        </div>
      ) : null}

      <div className="grid md:grid-cols-[minmax(0,1fr)_300px] gap-5 px-6 md:px-8 py-5">
        <div className="flex flex-col gap-5">
          {stage.id === 'decide' && project ? <DecideLanding project={project} visaType={data?.profile_visa_type ?? null} /> : null}
          {stage.id !== 'decide' && person !== 'me' ? (
            <div className="card p-5">
              <span className="eyebrow">Their file</span>
              <p className="mt-2 text-sm text-gray-600">Only the steps that are theirs are shown below, dated from the same move. Their documents and who is doing what live under Family plans.</p>
              <button onClick={() => setActiveView('family')} className="btn btn-secondary mt-3">Open family plans</button>
            </div>
          ) : null}

          {stage.id === 'decide' ? null : groups.length === 0 ? (
            <div className="card p-6">
              <p className="font-display font-semibold text-lg">Nothing dated here yet.</p>
              <p className="text-sm text-gray-600 mt-1">Set your move date and this stage fills in, counted back from it.</p>
            </div>
          ) : (
            groups.map((group) => (
              <div key={group.label} className="card overflow-hidden">
                <div className="flex justify-between items-baseline px-5 py-3.5 bg-card-2 border-b border-rule">
                  <span className="font-display font-semibold">{group.label}</span>
                  <span className="font-mono text-[0.7rem] text-gray-500 uppercase">{group.tasks.length} of {own.length}</span>
                </div>
                <ul className="divide-y divide-rule-soft">
                  {group.tasks.map((task) => {
                    const done = task.status === 'done';
                    return (
                      <li key={task.id} className="flex items-center gap-3.5 px-5 py-3">
                        <button
                          onClick={() => updateStatus.mutate({ id: task.id, status: done ? 'todo' : 'done' })}
                          aria-label={done ? `Mark "${task.title}" not done` : `Mark "${task.title}" done`}
                          className="flex-shrink-0 text-primary-500"
                        >
                          {done ? <CheckCircle2 className="w-5 h-5" /> : <Circle className={clsx('w-5 h-5', task.is_overdue ? 'text-accent-500' : 'text-gray-300')} />}
                        </button>
                        <div className="flex flex-col min-w-0 flex-1">
                          <span className={clsx('text-[0.95rem]', done ? 'text-gray-500 line-through' : 'font-semibold')}>
                            <Jargon text={task.title} />
                          </span>
                          {task.description && !done ? (
                            <span className="text-[0.82rem] text-gray-500 line-clamp-1"><Jargon text={task.description} /></span>
                          ) : null}
                          <span className="flex flex-wrap gap-x-3">
                            <PersonChip task={task} members={members} household={household} />
                            {typeof task.metadata?.professional === 'string' && !done ? (
                              <span className="font-mono text-[0.66rem] uppercase tracking-wide text-gray-500">Bring in a professional</span>
                            ) : null}
                          </span>
                        </div>
                        <span className={clsx('font-mono text-[0.7rem]', task.is_overdue && !done ? 'text-accent-500' : 'text-gray-500')}>{dueLabel(task)}</span>
                        <AssignSelect task={task} household={household} />
                        <button
                          onClick={() => { setTaskFilters({ stage: stage.id }); setActiveView('tasks'); }}
                          className="text-sm font-semibold text-primary-500 hover:text-primary-700"
                        >
                          Open
                        </button>
                      </li>
                    );
                  })}
                </ul>
              </div>
            ))
          )}
        </div>

        <aside className="flex flex-col gap-5">
          {stage.guides.length > 0 ? (
            <div className="card p-5 flex flex-col gap-2.5">
              <span className="eyebrow">Guides for this stage</span>
              {stage.guides.map((g) => (
                <button key={g.slug + g.title} onClick={() => { setActiveGuide(g.slug); setActiveView('guide'); }} className="text-left text-[0.95rem] font-semibold text-primary-500 hover:text-primary-700">
                  {g.title}
                </button>
              ))}
              <span className="text-xs text-gray-500">Official sources only, re-checked weekly</span>
            </div>
          ) : null}

          {professionals.length > 0 ? <ProfessionalsCard prompts={professionals} /> : null}

          {stage.checklists.length > 0 ? (
            <div className="card p-5 flex flex-col gap-2">
              <span className="eyebrow">Checklists</span>
              <p className="text-sm text-gray-600">The document lists for this stage, ticked off as they come in.</p>
              <button onClick={() => setActiveView('checklists')} className="btn btn-secondary self-start mt-1">Open checklists</button>
            </div>
          ) : null}


          <div className="card p-5 flex flex-col gap-2">
            <span className="eyebrow">Ask about this stage</span>
            <p className="text-sm text-gray-600">The assistant answers against your file and the knowledge base, and shows where each answer comes from.</p>
            <button onClick={() => setActiveView('chat')} className="btn btn-secondary self-start mt-1 gap-1.5">Ask <ArrowRight className="w-4 h-4" /></button>
            <button onClick={() => setActiveView('glossary')} className="text-xs text-gray-500 self-start hover:text-ink">Browse every term</button>
          </div>

          {!isNow ? (
            <button onClick={() => { setActiveStage(nowStage); }} className="text-sm text-gray-500 hover:text-ink self-start">
              Back to where you are · {stageById(nowStage)?.name}
            </button>
          ) : null}
        </aside>
      </div>
    </div>
  );
}
