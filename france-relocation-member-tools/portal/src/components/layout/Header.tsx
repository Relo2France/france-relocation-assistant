import { useEffect, useRef, useState } from 'react';
import { AlertTriangle, Bell, CheckCircle, Info, User, X } from 'lucide-react';
import { useCurrentUser, useDashboard, useSupportTickets } from '@/hooks/useApi';
import { usePortalStore } from '@/store';

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
  const { activeView, setActiveView } = usePortalStore();
  const { data: user } = useCurrentUser();
  const { data: dashboardData } = useDashboard();
  const { data: ticketsData } = useSupportTickets();
  const [showNotifications, setShowNotifications] = useState(false);
  // Dismissals are per day and per browser: a derived alert comes back tomorrow if it still applies.
  const today = new Date().toISOString().slice(0, 10);
  const [dismissed, setDismissed] = useState<Record<string, string>>(() => {
    try { return JSON.parse(window.localStorage.getItem('framt_dismissed_notices') ?? '{}') as Record<string, string>; } catch { return {}; }
  });
  const dismiss = (id: string) => {
    const next = { ...dismissed, [id]: today };
    setDismissed(next);
    try { window.localStorage.setItem('framt_dismissed_notices', JSON.stringify(next)); } catch { /* per-viewer convenience only */ }
  };
  const go = (view: string) => { setShowNotifications(false); setActiveView(view); };
  const notificationRef = useRef<HTMLDivElement>(null);

  const title = viewTitles[activeView] || 'Dashboard';

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notificationRef.current && !notificationRef.current.contains(event.target as Node)) {
        setShowNotifications(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Build notifications from dashboard data. Each one opens the thing it is about.
  const notifications: { id: string; type: string; title: string; message: string; time: string; go: () => void }[] = [];

  if (dashboardData?.task_stats?.overdue && dashboardData.task_stats.overdue > 0) {
    notifications.push({
      id: 'overdue',
      type: 'warning',
      title: `${dashboardData.task_stats.overdue} Overdue Task${dashboardData.task_stats.overdue > 1 ? 's' : ''}`,
      message: 'Past dates are the order to work in; open Deadlines to see them first.',
      time: 'Now',
      go: () => go('deadlines'),
    });
  }

  if (dashboardData?.upcoming_tasks && dashboardData.upcoming_tasks.length > 0) {
    notifications.push({
      id: 'upcoming',
      type: 'info',
      title: 'Upcoming Deadlines',
      message: `${dashboardData.upcoming_tasks.length} step${dashboardData.upcoming_tasks.length === 1 ? '' : 's'} due inside two weeks. Next: “${dashboardData.upcoming_tasks[0].title}”.`,
      time: 'Today',
      go: () => go('deadlines'),
    });
  }

  if (dashboardData?.project?.days_until_move && dashboardData.project.days_until_move <= 30) {
    notifications.push({
      id: 'move-date',
      type: 'info',
      title: 'Move Date Approaching',
      message: `${dashboardData.project.days_until_move} days until your move`,
      time: 'Reminder',
      go: () => go('stage'),
    });
  }

  for (const t of (ticketsData?.tickets ?? []).filter((x) => x.from_site && x.has_unread_user)) {
    notifications.push({
      id: `message-${t.id}`,
      type: 'success',
      title: t.subject,
      message: 'A new message from Relo2France.',
      time: t.relative_time,
      go: () => go('messages'),
    });
  }

  const visible = notifications.filter((n) => dismissed[n.id] !== today);

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
                        <button onClick={notification.go} className="flex gap-3 flex-1 min-w-0 text-left">
                          <div className="flex-shrink-0 mt-0.5">
                            {notification.type === 'warning' ? (
                              <AlertTriangle className="w-5 h-5 text-yellow-500" />
                            ) : notification.type === 'success' ? (
                              <CheckCircle className="w-5 h-5 text-green-500" />
                            ) : (
                              <Info className="w-5 h-5 text-blue-500" />
                            )}
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900">
                              {notification.title}
                            </p>
                            <p className="text-sm text-gray-500 mt-0.5">
                              {notification.message}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">
                              {notification.time} · <span className="text-primary-500 font-semibold">Open</span>
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
                      <button onClick={() => go('messages')} className="text-sm font-semibold text-primary-500 hover:text-primary-700">All messages →</button>
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
