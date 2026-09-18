import { useEffect, useState } from 'react';
/**
 * Sidebar
 *
 * The rail is the journey: home, the six stages with their progress, then
 * the tools that cut across every stage, then the account. Tasks, checklists
 * and guides no longer have their own doors; they live inside the stage they
 * belong to.
 */
import { clsx } from 'clsx';
import {
  BookOpen,
  Calendar,
  ChevronLeft,
  ChevronRight,
  FileText,
  Globe,
  HelpCircle,
  LifeBuoy,
  Mail,
  MessageSquare,
  Settings,
  User,
  Users,
} from 'lucide-react';
import { useDashboard, useSupportTickets, useTasks } from '@/hooks/useApi';
import { JOURNEY, type JourneyStage, currentStage, progressFor, timeToGo } from '@/journey/journey';
import { usePortalStore } from '@/store';

function StageRing({ state }: { state: 'done' | 'now' | 'ahead' }) {
  if (state === 'done') {
    return (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="10" stroke="#7fbaa3" strokeWidth="2" />
        <path d="M8 12.5l2.5 2.5L16 9.5" stroke="#7fbaa3" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
    );
  }
  if (state === 'now') {
    return (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="10" stroke="#7fbaa3" strokeWidth="2" />
        <path d="M12 6a6 6 0 0 1 0 12z" fill="#7fbaa3" />
      </svg>
    );
  }
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="10" stroke="rgba(214,223,217,0.35)" strokeWidth="2" />
    </svg>
  );
}

const TOOLS: { id: string; label: string; icon: typeof MessageSquare; views: string[] }[] = [
  { id: 'chat', label: 'Ask about my case', icon: MessageSquare, views: ['chat', 'glossary'] },
  { id: 'documents', label: 'Documents & files', icon: FileText, views: ['documents', 'files', 'checklists'] },
  { id: 'family', label: 'Family plans', icon: Users, views: ['family'] },
  { id: 'deadlines', label: 'Deadlines', icon: Calendar, views: ['deadlines', 'timeline', 'tasks'] },
  { id: 'schengen', label: 'Schengen days', icon: Globe, views: ['schengen'] },
  { id: 'research', label: 'Explore France', icon: BookOpen, views: ['research', 'guides', 'guide'] },
  { id: 'messages', label: 'Messages', icon: Mail, views: ['messages'] },
];

const ACCOUNT: { id: string; label: string; icon: typeof User }[] = [
  { id: 'profile', label: 'Profile', icon: User },
  { id: 'settings', label: 'Settings', icon: Settings },
  { id: 'support', label: 'Support', icon: LifeBuoy },
  { id: 'help', label: 'Help', icon: HelpCircle },
];

export default function Sidebar() {
  const { sidebarCollapsed, toggleSidebar, activeView, setActiveView, activeStage, setActiveStage, settings, isMenuItemVisible, mobileNavOpen, setMobileNavOpen } =
    usePortalStore();
  // On a phone the rail opens as a full menu, so it always shows its labels.
  const [isPhone, setIsPhone] = useState(() => typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches);
  useEffect(() => {
    const mq = window.matchMedia('(max-width: 767px)');
    const on = () => setIsPhone(mq.matches);
    mq.addEventListener('change', on);
    return () => mq.removeEventListener('change', on);
  }, []);
  useEffect(() => {
    if (!mobileNavOpen) return;
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') setMobileNavOpen(false); };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [mobileNavOpen, setMobileNavOpen]);
  const collapsed = sidebarCollapsed && !isPhone;
  const { data } = useDashboard();
  const project = data?.project;
  const { data: tasks } = useTasks(project?.id ?? 0);
  const { data: ticketsData } = useSupportTickets();
  const unreadMessages = (ticketsData?.tickets ?? []).filter((t) => t.from_site && t.has_unread_user).length;
  const household = data?.household;
  const nowStage = project ? currentStage(project, data?.profile_visa_type) : 'decide';
  const nowIndex = JOURNEY.findIndex((s) => s.id === nowStage);

  const sidebarStyle = {
    '--sidebar-bg': settings.colors.sidebarBg,
    '--sidebar-text': settings.colors.sidebarText,
  } as React.CSSProperties;

  const openStage = (stage: JourneyStage) => {
    setActiveStage(stage.id);
    setActiveView('stage');
  };

  const isTool = (views: string[]) => views.includes(activeView);

  return (
    <aside
      className={clsx('sidebar flex flex-col', collapsed ? 'sidebar-collapsed' : 'sidebar-expanded', mobileNavOpen && 'sidebar-mobile-open')}
      aria-hidden={isPhone && !mobileNavOpen ? true : undefined}
      style={sidebarStyle}
    >
      {/* Wordmark */}
      <div className="flex items-center h-16 px-4 border-b border-white/10">
        {!collapsed ? (
          <span className="font-display font-bold text-[1.1rem] tracking-[-0.02em] text-sidebar-textActive">
            Relo<span style={{ color: '#7fbaa3' }}>2</span>France
          </span>
        ) : (
          <span className="font-display font-bold text-[0.85rem] tracking-[-0.02em] text-sidebar-textActive mx-auto">R2F</span>
        )}
      </div>

      <nav className="flex-1 overflow-y-auto py-3 scrollbar-thin">
        {/* Home */}
        <div className="px-2">
          <button
            onClick={() => setActiveView('dashboard')}
            className={clsx('nav-item w-full', activeView === 'dashboard' && 'nav-item-active', collapsed && 'justify-center px-2')}
            title={collapsed ? 'Where you are' : undefined}
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" className="flex-shrink-0">
              <path d="M4 11l8-7 8 7v9H4z" /><path d="M10 20v-6h4v6" />
            </svg>
            {!collapsed && <span>Where you are</span>}
          </button>
        </div>

        {/* The journey */}
        <div className="mt-4 px-2">
          {!collapsed && (
            <div className="flex items-baseline justify-between px-2 mb-2">
              <span className="eyebrow text-sidebar-text/60">Your move</span>
              {project ? <span className="font-mono text-[0.72rem] text-sidebar-text/70">{timeToGo(project)}</span> : null}
            </div>
          )}
          <ul className="space-y-1">
            {JOURNEY.map((stage, i) => {
              const state: 'done' | 'now' | 'ahead' = i < nowIndex ? 'done' : i === nowIndex ? 'now' : 'ahead';
              const isActive = activeView === 'stage' && activeStage === stage.id;
              const progress = project ? progressFor(stage, tasks ?? [], project) : { total: 0, completed: 0 };
              const sub =
                state === 'done'
                  ? 'Done'
                  : progress.total > 0
                    ? `${progress.completed} of ${progress.total} steps`
                    : stage.blurb;
              return (
                <li key={stage.id}>
                  <button
                    onClick={() => openStage(stage)}
                    className={clsx(
                      'w-full flex items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors',
                      isActive ? 'bg-sidebar-active text-sidebar-textActive' : state === 'now' ? 'bg-white/[0.05] text-sidebar-textActive hover:bg-sidebar-hover' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-textActive',
                      collapsed && 'justify-center px-2'
                    )}
                    title={collapsed ? `${stage.number} · ${stage.name}` : undefined}
                    aria-current={state === 'now' ? 'step' : undefined}
                  >
                    <StageRing state={state} />
                    {!collapsed && (
                      <span className="flex flex-col min-w-0">
                        <span className="text-[0.9rem] font-semibold leading-tight truncate">{stage.number} · {stage.name}</span>
                        <span className="text-[0.76rem] opacity-70 leading-tight truncate">{sub}</span>
                      </span>
                    )}
                  </button>
                </li>
              );
            })}
          </ul>
        </div>

        {/* Tools */}
        <div className="mt-5 px-2 pt-4 border-t border-white/10">
          {!collapsed && <span className="eyebrow block px-2 mb-2 text-sidebar-text/60">Tools</span>}
          <ul className="space-y-0.5">
            {TOOLS.filter((t) => t.id === 'deadlines' ? isMenuItemVisible('timeline') || isMenuItemVisible('tasks') : isMenuItemVisible(t.id)).map((tool) => {
              const Icon = tool.icon;
              return (
                <li key={tool.id}>
                  <button
                    onClick={() => setActiveView(tool.id)}
                    className={clsx('nav-item w-full py-2', isTool(tool.views) && 'nav-item-active', collapsed && 'justify-center px-2')}
                    title={collapsed ? tool.label : undefined}
                  >
                    <Icon className="w-[18px] h-[18px] flex-shrink-0" aria-hidden="true" />
                    {!collapsed && <span className="text-[0.9rem]">{tool.label}</span>}
                    {tool.id === 'messages' && unreadMessages > 0 ? (
                      <>
                        <span className={clsx('ml-auto font-mono text-[0.66rem] rounded-full px-1.5 py-0.5 bg-white/15 text-sidebar-textActive', collapsed && 'absolute top-1 right-1 ml-0')} aria-hidden="true">{unreadMessages}</span>
                        <span className="sr-only">{unreadMessages} unread</span>
                      </>
                    ) : null}
                  </button>
                </li>
              );
            })}
          </ul>
        </div>

        {/* Account */}
        <div className="mt-4 px-2 pt-4 border-t border-white/10">
          <ul className="space-y-0.5">
            {ACCOUNT.filter((a) => a.id === 'profile' || a.id === 'support' || isMenuItemVisible(a.id)).map((item) => {
              const Icon = item.icon;
              const active = item.id === activeView;
              return (
                <li key={item.id}>
                  <button
                    onClick={() => setActiveView(item.id)}
                    className={clsx('nav-item w-full py-1.5', active && 'nav-item-active', collapsed && 'justify-center px-2')}
                    title={collapsed ? item.label : undefined}
                  >
                    <Icon className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                    {!collapsed && <span className="text-[0.85rem]">{item.label}</span>}
                  </button>
                </li>
              );
            })}
          </ul>
        </div>
      </nav>

      {household && household.role === 'partner' && !collapsed ? (

        <div className="mx-4 mb-2 px-3 py-2 rounded-lg bg-white/[0.06] text-[0.74rem] leading-snug text-sidebar-text">

          Working on <span className="text-sidebar-textActive font-semibold">{household.ownerName}’s</span> household file

        </div>

      ) : null}

      {/* Collapse toggle */}
      <button
        onClick={toggleSidebar}
        className={clsx(
          // On a phone the menu closes by tapping outside it; no collapse.
          'hidden md:flex items-center justify-center h-12 border-t border-white/10 transition-colors',
          collapsed ? 'text-sidebar-textActive hover:bg-sidebar-hover' : 'text-sidebar-text hover:text-sidebar-textActive'
        )}
        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        aria-expanded={!collapsed}
      >
        {collapsed ? <ChevronRight className="w-6 h-6" /> : (<><ChevronLeft className="w-5 h-5" /><span className="ml-2 text-sm">Collapse</span></>)}
      </button>
    </aside>
  );
}
