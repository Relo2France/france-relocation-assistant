import { useEffect, useRef, useState } from 'react';
import { AlertTriangle, Bell, CheckCircle, Info, Mail, Scale, User, X } from 'lucide-react';
import { buildFileAlerts, useDismissedAlerts } from '@/alerts/alerts';
import { useCurrentUser, useDashboard, useSupportTickets, useTasks } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { Task } from '@/types';

const viewTitles: Record<string, string> = {
  dashboard: 'Where you are',
  tasks: 'Tasks',
  timeline: 'Timeline',
  messages: 'Messages',
  documents: 'Documents',
  guides: 'Guides',
  deadlines: 'Deadlines',
  support: 'Support',
  files: 'Files',
  family: 'Family plans',
  settings: 'Settings',
  help: 'Help',
  profile: 'My Profile',
  checklists: 'Checklists',
  glossary: 'Glossary',
  chat: 'Ask about my case',
  membership: 'Membership',
  research: 'Explore France',
  schengen: 'Schengen days',
  stage: 'Your move',
  guide: 'Guide',
};

export default function Header() {
  const { activeView, setActiveView, setActiveStage, setTaskFilters, setOpenTaskId } = usePortalStore();
  const { data: user } = useCurrentUser();
  const { data: dashboardData } = useDashboard();
  const { data: ticketsData } = useSupportTickets();
  const { data: tasks } = useTasks(dashboardData?.project?.id ?? 0);
  const [showNotifications, setShowNotifications] = useState(false);
  const { isDismissed, dismiss, refresh } = useDismissedAlerts();
  const notificationRef = useRef<HTMLDivElement>(null);

  const title = viewTitles[activeView] || 'Dashboard';

  // Close dropdown when clicking outside; follow dismissals made on Messages.
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notificationRef.current && !notificationRef.current.contains(event.target as Node)) {
        setShowNotifications(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('framt:alerts-changed', refresh);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      window.removeEventListener('framt:alerts-changed', refresh);
    };
  }, [refresh]);

  // The same alerts as "Your file is saying" on Messages, with the same words.
  const nav = {
    setActiveView: (v: string) => { setShowNotifications(false); setActiveView(v); },
    setActiveStage,
    openTask: (t: Task) => { setShowNotifications(false); setTaskFilters({ stage: null, status: null, taskType: null }); setOpenTaskId(t.id); setActiveView('tasks'); },
  };
  const visible = buildFileAlerts(dashboardData, tasks ?? [], ticketsData?.tickets ?? [], nav).filter((n) => !isDismissed(n.id));
  const notificationCount = visible.length;

  return (
    <header className="h-16 bg-card border-b border-rule flex items-center justify-between px-6">
      {/* Title */}
      <div>
        <h1 className="font-display text-2xl font-semibold tracking-[-0.018em] text-ink">{title}</h1>
      </div>

      {/* Right side */}
      <div className="flex items-center gap-4">
        {/* Notifications */}
        <div className="relative" ref={notificationRef}>
          <button
            onClick={() => setShowNotifications(!showNotifications)}
            className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            aria-label={`Notifications${notificationCount > 0 ? ` (${notificationCount} new)` : ''}`}
          >
            <Bell className="w-5 h-5" />
            {notificationCount > 0 && (
              <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            )}
          </button>

          {/* Notifications Dropdown */}
          {showNotifications && (
            <div className="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
              <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 className="font-semibold text-gray-900">Notifications</h3>
                <button
                  onClick={() => setShowNotifications(false)}
                  className="p-1 text-gray-400 hover:text-gray-600 rounded"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              <div className="max-h-96 overflow-y-auto">
                {visible.length > 0 ? (
                  <div className="divide-y divide-gray-100">
                    {visible.map((notification) => (
                      <div
                        key={notification.id}
                        className="px-4 py-3 hover:bg-gray-50 transition-colors flex items-start gap-2"
                      >
                        <button onClick={notification.action.go} className="flex gap-3 flex-1 min-w-0 text-left">
                          <div className="flex-shrink-0 mt-0.5">
                            {notification.tone === 'accent' ? (
                              <AlertTriangle className="w-5 h-5 text-accent-500" aria-hidden="true" />
                            ) : notification.tone === 'message' ? (
                              <Mail className="w-5 h-5 text-primary-500" aria-hidden="true" />
                            ) : notification.tone === 'ink' ? (
                              <Scale className="w-5 h-5 text-gray-500" aria-hidden="true" />
                            ) : (
                              <Info className="w-5 h-5 text-primary-500" aria-hidden="true" />
                            )}
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900">
                              {notification.title}
                            </p>
                            <p className="text-sm text-gray-500 mt-0.5">
                              {notification.body}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">
                              {notification.when} · <span className="text-primary-500 font-semibold">{notification.action.label}</span>
                            </p>
                          </div>
                        </button>
                        <button
                          onClick={() => dismiss(notification.id)}
                          className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0"
                          aria-label={`Dismiss “${notification.title}”`}
                          title="Dismiss"
                        >
                          <X className="w-4 h-4" aria-hidden="true" />
                        </button>
                      </div>
                    ))}
                    <div className="px-4 py-2.5">
                      <button onClick={() => nav.setActiveView('messages')} className="text-sm font-semibold text-primary-500 hover:text-primary-700">All messages →</button>
                    </div>
                  </div>
                ) : (
                  <div className="px-4 py-8 text-center">
                    <CheckCircle className="w-10 h-10 text-gray-300 mx-auto mb-2" />
                    <p className="text-sm text-gray-500">You&apos;re all caught up!</p>
                    <p className="text-xs text-gray-400 mt-1">No new notifications</p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* User menu */}
        <div className="flex items-center gap-3 pl-4 border-l border-gray-200">
          {user?.avatar_url ? (
            <img
              src={user.avatar_url}
              alt={user.display_name}
              className="w-8 h-8 rounded-full"
            />
          ) : (
            <div className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
              <User className="w-4 h-4 text-gray-500" />
            </div>
          )}
          <div className="hidden sm:block">
            <p className="font-sans text-sm font-medium text-ink">
              {user?.display_name || 'Loading...'}
            </p>
            <p className="text-xs text-gray-500">
              {user?.is_member ? 'Member' : 'Free'}
            </p>
          </div>
        </div>
      </div>
    </header>
  );
}
