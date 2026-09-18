/**
 * Messages
 *
 * What the site has to tell you: the welcome note, anything the team sends,
 * rule changes that touch your route, and the alerts your own file raises
 * (an overdue step, a deadline inside two weeks, the move itself getting
 * close, a point where a professional should look). Nothing here is a
 * conversation you started; that is Support. Each alert's action opens the
 * exact thing it is about, and each message can be marked unread or deleted.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { AlertTriangle, ArrowLeft, Bell, CalendarClock, Mail, MailOpen, Scale, Trash2 } from 'lucide-react';
import MarkdownMessage from '@/components/shared/MarkdownMessage';
import Jargon from '@/components/shared/Jargon';
import { useDashboard, useDeleteSupportTicket, useMarkSupportTicketUnread, useReplyToSupportTicket, useSupportTicket, useSupportTickets, useTasks } from '@/hooks/useApi';
import { currentStage } from '@/journey/journey';
import { usePortalStore } from '@/store';
import type { SupportTicket, Task } from '@/types';

type Alert = {
  id: string;
  icon: typeof Bell;
  tone: 'accent' | 'primary' | 'ink';
  title: string;
  body: string;
  /** Why this alert is on your file, when it is not obvious from the body. */
  because?: string;
  action: { label: string; go: () => void };
};

export default function MessagesView() {
  const [openId, setOpenId] = useState<number | null>(null);
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null);
  const { data: ticketsData, isLoading } = useSupportTickets();
  const { data: dashboard } = useDashboard();
  const { data: tasks } = useTasks(dashboard?.project?.id ?? 0);
  const { setActiveView, setActiveStage, setTaskFilters, setOpenTaskId } = usePortalStore();
  const markUnread = useMarkSupportTicketUnread();
  const remove = useDeleteSupportTicket();

  const fromSite = (ticketsData?.tickets ?? []).filter((t) => t.from_site);

  const openTask = (task: Task) => {
    setTaskFilters({ stage: null, status: null, taskType: null });
    setOpenTaskId(task.id);
    setActiveView('tasks');
  };
  const alerts = buildAlerts(dashboard, tasks ?? [], { setActiveView, setActiveStage, openTask });

  if (openId !== null) return <Thread ticketId={openId} onBack={() => setOpenId(null)} />;

  return (
    <div className="flex flex-col">
      <header className="flex flex-col gap-1.5 px-6 md:px-8 pt-6 pb-5 bg-card border-b border-rule">
        <span className="eyebrow">From Relo2France</span>
        <h2 className="font-display text-[1.75rem] font-semibold tracking-[-0.018em] leading-tight">Messages</h2>
        <p className="text-ink/80 max-w-[64ch]">What the site and your own file have to tell you. To ask us something, use Support.</p>
      </header>

      <div className="px-6 md:px-8 py-5 flex flex-col gap-5">
        {alerts.length > 0 ? (
          <div className="card overflow-hidden">
            <div className="px-5 py-3.5 bg-card-2 border-b border-rule"><span className="font-display font-semibold">Your file is saying</span></div>
            <ul className="divide-y divide-rule-soft">
              {alerts.map((a) => {
                const Icon = a.icon;
                return (
                  <li key={a.id} className="flex items-start gap-3.5 px-5 py-3.5">
                    <Icon className={clsx('w-5 h-5 flex-shrink-0 mt-0.5', a.tone === 'accent' ? 'text-accent-500' : a.tone === 'primary' ? 'text-primary-500' : 'text-gray-500')} aria-hidden="true" />
                    <div className="flex flex-col min-w-0 flex-1 gap-0.5">
                      <span className="font-semibold text-[0.95rem]"><Jargon text={a.title} /></span>
                      <span className="text-[0.85rem] text-gray-600"><Jargon text={a.body} /></span>
                      {a.because ? <span className="text-[0.78rem] text-gray-500 italic">Because: {a.because}</span> : null}
                    </div>
                    <button onClick={a.action.go} className="btn btn-secondary text-sm whitespace-nowrap">{a.action.label}</button>
                  </li>
                );
              })}
            </ul>
          </div>
        ) : null}

        <div className="card overflow-hidden">
          <div className="px-5 py-3.5 bg-card-2 border-b border-rule flex justify-between items-baseline">
            <span className="font-display font-semibold">From the team</span>
            <span className="font-mono text-[0.7rem] text-gray-500 uppercase">{fromSite.length} message{fromSite.length === 1 ? '' : 's'}</span>
          </div>
          {isLoading ? (
            <div className="p-5"><div className="h-10 animate-pulse bg-card-2 rounded" /></div>
          ) : fromSite.length === 0 ? (
            <p className="px-5 py-6 text-sm text-gray-500 m-0">Nothing yet. When a rule on your route changes, or we have something for you, it lands here and in your inbox.</p>
          ) : (
            <ul className="divide-y divide-rule-soft">
              {fromSite.map((t: SupportTicket) => (
                <li key={t.id} className="flex items-center gap-2 pr-3 group">
                  <button onClick={() => setOpenId(t.id)} className="flex-1 min-w-0 flex items-center gap-3.5 px-5 py-3.5 text-left hover:bg-card-2">
                    <Mail className={clsx('w-5 h-5 flex-shrink-0', t.has_unread_user ? 'text-primary-500' : 'text-gray-400')} aria-hidden="true" />
                    <div className="flex flex-col min-w-0 flex-1">
                      <span className={clsx('text-[0.95rem]', t.has_unread_user ? 'font-semibold' : '')}>{t.subject}</span>
                      {t.initial_message ? <span className="text-[0.82rem] text-gray-500 line-clamp-1">{t.initial_message.replace(/[#*_>`]/g, '')}</span> : null}
                    </div>
                    <span className="font-mono text-[0.7rem] text-gray-500 whitespace-nowrap">{t.relative_time}</span>
                  </button>
                  <div className="flex items-center gap-1 flex-shrink-0">
                    {!t.has_unread_user ? (
                      <button
                        onClick={() => markUnread.mutate(t.id)}
                        className="p-2 rounded-full text-gray-400 hover:text-primary-500 hover:bg-card-2"
                        title="Mark as unread"
                        aria-label={`Mark “${t.subject}” as unread`}
                      >
                        <MailOpen className="w-4 h-4" aria-hidden="true" />
                      </button>
                    ) : null}
                    {confirmDelete === t.id ? (
                      <span className="flex items-center gap-1">
                        <button onClick={() => { remove.mutate(t.id); setConfirmDelete(null); }} className="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-600 text-white hover:bg-red-700">Delete</button>
                        <button onClick={() => setConfirmDelete(null)} className="px-2.5 py-1 rounded-full text-xs font-semibold bg-card-2 text-gray-600">Keep</button>
                      </span>
                    ) : (
                      <button
                        onClick={() => setConfirmDelete(t.id)}
                        className="p-2 rounded-full text-gray-400 hover:text-red-600 hover:bg-card-2"
                        title="Delete"
                        aria-label={`Delete “${t.subject}”`}
                      >
                        <Trash2 className="w-4 h-4" aria-hidden="true" />
                      </button>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        <p className="text-[0.82rem] text-gray-500 m-0">
          Have a question of your own? <button onClick={() => setActiveView('support')} className="font-semibold text-primary-500 hover:text-primary-700">Write to Support</button>.
        </p>
      </div>
    </div>
  );
}

function buildAlerts(
  dashboard: ReturnType<typeof useDashboard>['data'],
  tasks: Task[],
  nav: { setActiveView: (v: string) => void; setActiveStage: (s: string) => void; openTask: (t: Task) => void }
): Alert[] {
  if (!dashboard) return [];
  const { setActiveView, setActiveStage, openTask } = nav;
  const alerts: Alert[] = [];
  const overdue = dashboard.task_stats?.overdue ?? 0;
  if (overdue > 0) {
    const first = dashboard.overdue_tasks[0];
    alerts.push({
      id: 'overdue',
      icon: AlertTriangle,
      tone: 'accent',
      title: overdue === 1 ? 'One step is past its date' : `${overdue} steps are past their date`,
      body: first ? `Starting with “${first.title}”. Past dates are not failures; they are the order to work in.` : 'Past dates are not failures; they are the order to work in.',
      action: first ? { label: 'Open the step', go: () => openTask(first) } : { label: 'See deadlines', go: () => setActiveView('deadlines') },
    });
  }
  const soon = dashboard.upcoming_tasks.filter((t) => t.days_until_due !== null && t.days_until_due !== undefined && t.days_until_due <= 14 && t.status !== 'done');
  if (soon.length > 0) {
    alerts.push({
      id: 'soon',
      icon: CalendarClock,
      tone: 'primary',
      title: soon.length === 1 ? 'One step is due inside two weeks' : `${soon.length} steps are due inside two weeks`,
      body: `Next up: “${soon[0].title}”.`,
      action: { label: 'See deadlines', go: () => setActiveView('deadlines') },
    });
  }
  const project = dashboard.project;
  if (project?.target_move_date) {
    const days = Math.ceil((new Date(`${project.target_move_date.slice(0, 10)}T00:00:00Z`).getTime() - Date.now()) / 86400000);
    if (days >= 0 && days <= 30) {
      alerts.push({
        id: 'move',
        icon: Bell,
        tone: 'primary',
        title: days === 0 ? 'You move today' : `You move in ${days} day${days === 1 ? '' : 's'}`,
        body: 'The Move stage has the last-week list: what to carry, what to ship, what to cancel.',
        action: { label: 'Open Move', go: () => { setActiveStage('move'); setActiveView('stage'); } },
      });
    }
  }
  const stage = project ? currentStage(project, dashboard.profile_visa_type) : 'decide';
  const pro = (dashboard.professionals ?? []).find((p) => p.stage === stage);
  if (pro) {
    // The step that carries this prompt, so the action lands on it rather than on a page.
    const step = tasks.find((t) => t.status !== 'done' && (t.metadata as { professional?: string } | null)?.professional === pro.kind)
      ?? tasks.find((t) => (t.metadata as { professional?: string } | null)?.professional === pro.kind);
    alerts.push({
      id: `pro-${pro.id}`,
      icon: Scale,
      tone: 'ink',
      title: `${pro.who}: ${pro.when.toLowerCase()}`,
      body: pro.why,
      because: pro.trigger,
      action: step
        ? { label: 'Open the step', go: () => openTask(step) }
        : { label: `Open ${stage.charAt(0).toUpperCase() + stage.slice(1)}`, go: () => { setActiveStage(stage); setActiveView('stage'); } },
    });
  }
  return alerts;
}

function Thread({ ticketId, onBack }: { ticketId: number; onBack: () => void }) {
  const { data, isLoading } = useSupportTicket(ticketId);
  const reply = useReplyToSupportTicket();
  const [text, setText] = useState('');
  return (
    <div className="flex flex-col">
      <header className="flex items-center gap-3 px-6 md:px-8 pt-6 pb-5 bg-card border-b border-rule">
        <button onClick={onBack} className="btn btn-ghost" aria-label="Back to messages"><ArrowLeft className="w-4 h-4" /> Messages</button>
        <h2 className="font-display text-[1.3rem] font-semibold tracking-[-0.018em] leading-tight">{data?.ticket.subject ?? ''}</h2>
      </header>
      <div className="px-6 md:px-8 py-5 flex flex-col gap-4 max-w-[72ch]">
        {isLoading || !data ? <div className="card h-24 animate-pulse" /> : null}
        {data?.replies.map((r) => (
          <div key={r.id} className={clsx('card p-5', r.is_admin ? '' : 'bg-card-2')}>
            <div className="flex justify-between items-baseline mb-2">
              <span className="eyebrow">{r.is_admin ? 'Relo2France' : 'You'}</span>
              <span className="font-mono text-[0.7rem] text-gray-500">{r.relative_time}</span>
            </div>
            <MarkdownMessage content={r.content} />
          </div>
        ))}
        {data && data.ticket.status === 'open' ? (
          <form
            className="card p-5 flex flex-col gap-2"
            onSubmit={(e) => { e.preventDefault(); if (text.trim()) reply.mutate({ ticketId, content: text.trim() }, { onSuccess: () => setText('') }); }}
          >
            <label htmlFor="message-reply" className="eyebrow">Reply</label>
            <textarea id="message-reply" className="input min-h-[96px]" value={text} onChange={(e) => setText(e.target.value)} placeholder="Reply to the team" />
            <button className="btn btn-primary self-start" type="submit" disabled={reply.isPending || !text.trim()}>{reply.isPending ? 'Sending…' : 'Send'}</button>
          </form>
        ) : null}
      </div>
    </div>
  );
}
