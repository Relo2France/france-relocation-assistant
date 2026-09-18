/**
 * The alerts a member's own file raises, built once and shown in two places
 * with the same words: the bell in the header and "Your file is saying" on
 * Messages. Dismissing one anywhere hides it everywhere for the day; an
 * alert that still applies comes back tomorrow. Dismissals live in this
 * browser only, so they never need a server round trip.
 */
import { useCallback, useState } from 'react';
import { currentStage } from '@/journey/journey';
import type { DashboardData, SupportTicket, Task } from '@/types';

export interface FileAlert {
  id: string;
  tone: 'accent' | 'primary' | 'ink' | 'message';
  title: string;
  body: string;
  /** Why this alert is on your file, when it is not obvious from the body. */
  because?: string;
  /** When it arose, in the member's words. */
  when: string;
  action: { label: string; go: () => void };
}

export interface AlertNav {
  setActiveView: (v: string) => void;
  setActiveStage: (s: string) => void;
  openTask: (t: Task) => void;
}

export function buildFileAlerts(
  dashboard: DashboardData | undefined,
  tasks: Task[],
  tickets: SupportTicket[],
  nav: AlertNav
): FileAlert[] {
  if (!dashboard) return [];
  const { setActiveView, setActiveStage, openTask } = nav;
  const alerts: FileAlert[] = [];

  const overdue = dashboard.task_stats?.overdue ?? 0;
  if (overdue > 0) {
    const first = dashboard.overdue_tasks[0];
    alerts.push({
      id: 'overdue',
      tone: 'accent',
      title: overdue === 1 ? 'One step is past its date' : `${overdue} steps are past their date`,
      body: first ? `Starting with “${first.title}”. Past dates are not failures; they are the order to work in.` : 'Past dates are not failures; they are the order to work in.',
      when: 'Now',
      action: first ? { label: 'Open the step', go: () => openTask(first) } : { label: 'See deadlines', go: () => setActiveView('deadlines') },
    });
  }

  const soon = dashboard.upcoming_tasks.filter((t) => t.days_until_due !== null && t.days_until_due !== undefined && t.days_until_due <= 14 && t.status !== 'done');
  if (soon.length > 0) {
    alerts.push({
      id: 'soon',
      tone: 'primary',
      title: soon.length === 1 ? 'One step is due inside two weeks' : `${soon.length} steps are due inside two weeks`,
      body: `Next up: “${soon[0].title}”.`,
      when: 'Today',
      action: { label: 'See deadlines', go: () => setActiveView('deadlines') },
    });
  }

  const project = dashboard.project;
  if (project?.target_move_date) {
    const days = Math.ceil((new Date(`${project.target_move_date.slice(0, 10)}T00:00:00Z`).getTime() - Date.now()) / 86400000);
    if (days >= 0 && days <= 30) {
      alerts.push({
        id: 'move',
        tone: 'primary',
        title: days === 0 ? 'You move today' : `You move in ${days} day${days === 1 ? '' : 's'}`,
        body: 'The Move stage has the last-week list: what to carry, what to ship, what to cancel.',
        when: 'Reminder',
        action: { label: 'Open Move', go: () => { setActiveStage('move'); setActiveView('stage'); } },
      });
    }
  }

  const stage = project ? currentStage(project, dashboard.profile_visa_type) : 'decide';
  const pro = (dashboard.professionals ?? []).find((p) => p.stage === stage);
  if (pro) {
    const step = tasks.find((t) => t.status !== 'done' && (t.metadata as { professional?: string } | null)?.professional === pro.kind)
      ?? tasks.find((t) => (t.metadata as { professional?: string } | null)?.professional === pro.kind);
    alerts.push({
      id: `pro-${pro.id}`,
      tone: 'ink',
      title: `${pro.who}: ${pro.when.toLowerCase()}`,
      body: pro.why,
      because: pro.trigger,
      when: 'This stage',
      action: step
        ? { label: 'Open the step', go: () => openTask(step) }
        : { label: `Open ${stage.charAt(0).toUpperCase() + stage.slice(1)}`, go: () => { setActiveStage(stage); setActiveView('stage'); } },
    });
  }

  for (const t of tickets.filter((x) => x.from_site && x.has_unread_user)) {
    alerts.push({
      id: `message-${t.id}`,
      tone: 'message',
      title: t.subject,
      body: 'A new message from Relo2France.',
      when: t.relative_time,
      action: { label: 'Read it', go: () => setActiveView('messages') },
    });
  }

  return alerts;
}

const STORAGE_KEY = 'framt_dismissed_alerts';

function readDismissed(): Record<string, string> {
  try {
    return JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? '{}') as Record<string, string>;
  } catch {
    return {};
  }
}

/** Per-day, per-browser dismissals shared by every surface that shows alerts. */
export function useDismissedAlerts() {
  const today = new Date().toISOString().slice(0, 10);
  const [dismissed, setDismissed] = useState<Record<string, string>>(readDismissed);

  const write = useCallback((next: Record<string, string>) => {
    setDismissed(next);
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
      window.dispatchEvent(new CustomEvent('framt:alerts-changed'));
    } catch { /* per-viewer convenience only */ }
  }, []);

  const isDismissed = useCallback((id: string) => dismissed[id] === today, [dismissed, today]);
  const dismiss = useCallback((id: string) => write({ ...dismissed, [id]: today }), [dismissed, today, write]);
  const restore = useCallback((id: string) => {
    const next = { ...dismissed };
    delete next[id];
    write(next);
  }, [dismissed, write]);
  const refresh = useCallback(() => setDismissed(readDismissed()), []);

  return { isDismissed, dismiss, restore, refresh };
}
