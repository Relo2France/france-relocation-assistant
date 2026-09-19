import { useEffect, useRef, useState } from 'react';
import { AlertTriangle, Bell, CheckCircle, ChevronDown, Info, LogOut, Mail, Menu, Scale, Settings, User, X } from 'lucide-react';
import { buildFileAlerts, useDismissedAlerts } from '@/alerts/alerts';
import { useCurrentUser, useDashboard, useSupportTickets, useTasks } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { Task } from '@/types';
import { signOutUrl } from '@/utils/signOut';

const viewTitles: Record<string, string> = {
  dashboard: 'Where you are',
  tasks: 'Steps',
  messages: 'Messages',
  documents: 'Documents',
  deadlines: 'Deadlines',
  support: 'Support',
  files: 'Files',
  family: 'Family',
  settings: 'Settings',
  help: 'Help',
  profile: 'My profile',
  checklists: 'Checklists',
  glossary: 'Glossary',
  chat: 'Ask about my case',
  research: 'Explore France',
  schengen: 'Schengen days',
  stage: 'Your move',
  guide: 'Guide',
  // Old view names still arriving in bookmarks; App shows their replacements.
  timeline: 'Deadlines',
  guides: 'Explore France',
};

export default function Header() {
  const { activeView, setActiveView, setActiveStage, setTaskFilters, setOpenTaskId, setOpenMessageId, setMobileNavOpen } = usePortalStore();
  const { data: user } = useCurrentUser();
  const { data: dashboardData } = useDashboard();
  const { data: ticketsData } = useSupportTickets();
  const { data: tasks } = useTasks(dashboardData?.project?.id ?? 0);
  const [showNotifications, setShowNotifications] = useState(false);
  const { isDismissed, dismiss, refresh } = useDismissedAlerts();
  const notificationRef = useRef<HTMLDivElement>(null);
  const [showAccount, setShowAccount] = useState(false);
  const accountRef = useRef<HTMLDivElement>(null);

  const title = viewTitles[activeView] || 'Where you are';

  // Close dropdown when clicking outside; follow dismissals made on Messages.
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notificationRef.current && !notificationRef.current.contains(event.target as Node)) {
        setShowNotifications(false);
      }
      if (accountRef.current && !accountRef.current.contains(event.target as Node)) {
        setShowAccount(false);
      }
    };
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setShowAccount(false);
        setShowNotifications(false);
      }
    };
    document.addEventListener('keydown', handleEscape);
    document.addEventListener('mousedown', handleClickOutside);
    window.addEventListener('framt:alerts-changed', refresh);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEscape);
      window.removeEventListener('framt:alerts-changed', refresh);
    };
  }, [refresh]);

  // The same alerts as "Your file is saying" on Messages, with the same words.
  const nav = {
    setActiveView: (v: string) => { setShowNotifications(false); setActiveView(v); },
    setActiveStage,
    openTask: (t: Task) => { setShowNotifications(false); setTaskFilters({ stage: null, status: null, taskType: null }); setOpenTaskId(t.id); setActiveView('tasks'); },
    openMessage: (id: number) => { setShowNotifications(false); setOpenMessageId(id); setActiveView('messages'); },
  };
  const visible = buildFileAlerts(dashboardData, tasks ?? [], ticketsData?.tickets ?? [], nav).filter((n) => !isDismissed(n.id));
  const notificationCount = visible.length;

  return (
    <header className="h-16 bg-card border-b border-rule flex items-center justify-between gap-3 px-4 md:px-6 sticky top-0 z-20">
      {/* Menu on phones, then the title */}
      <div className="flex items-center gap-2 min-w-0">
        <button
          type="button"
          onClick={() => setMobileNavOpen(true)}
          className="md:hidden -ml-1 p-2 rounded-lg text-gray-600 hover:bg-gray-100"
          aria-label="Open menu"
        >
          <Menu className="w-5 h-5" aria-hidden="true" />
        </button>
        <h1 className="font-display text-xl md:text-2xl font-semibold tracking-[-0.018em] text-ink truncate">{title}</h1>
      </div>

      {/* Right side */}
      <div className="flex items-center gap-4">
        {/* Notifications */}
        <div className="relative" ref={notificationRef}>
          <button
            onClick={() => setShowNotifications(!showNotifications)}
            aria-expanded={showNotifications}
            aria-controls="notifications-panel"
            className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            aria-label={notificationCount > 0 ? `Notifications: ${notificationCount} thing${notificationCount === 1 ? '' : 's'} your file or the team raised` : 'Notifications: nothing new'}
          >
            <Bell className="w-5 h-5" aria-hidden="true" />
            {notificationCount > 0 && (
              <span className="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-accent-500 text-white font-mono text-[0.62rem] leading-[1.1rem] text-center" aria-hidden="true">
                {notificationCount > 9 ? '9+' : notificationCount}
              </span>
            )}
          </button>

          {/* Notifications Dropdown */}
          {showNotifications && (
            // On a phone the bell sits mid-header, so the panel pins to the
            // screen edge rather than the bell and never runs off the left.
            <div
              id="notifications-panel"
              className="fixed right-4 top-[4.5rem] sm:absolute sm:right-0 sm:top-full sm:mt-2 w-[min(20rem,calc(100vw-2rem))] bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden"
            >
              <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 className="font-semibold text-gray-900">Notifications</h3>
                <button
                  type="button"
                  onClick={() => setShowNotifications(false)}
                  className="p-1 text-gray-400 hover:text-gray-600 rounded"
                  aria-label="Close notifications"
                >
                  <X className="w-4 h-4" aria-hidden="true" />
                </button>
              </div>

              <div className="max-h-[min(24rem,calc(100dvh-8rem))] overflow-y-auto">
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
                        {notification.tone !== 'message' ? (
                        <button
                          onClick={() => dismiss(notification.id)}
                          className="p-1 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0"
                          aria-label={`Dismiss “${notification.title}”`}
                          title="Dismiss"
                        >
                          <X className="w-4 h-4" aria-hidden="true" />
                        </button>
                        ) : null}
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

        {/* Account menu: profile, settings, sign out */}
        <div ref={accountRef} className="relative pl-4 border-l border-gray-200">
          <button
            type="button"
            onClick={() => setShowAccount(!showAccount)}
            className="flex items-center gap-3 rounded-lg py-1 pr-1 hover:bg-gray-50 focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500"
            aria-haspopup="menu"
            aria-expanded={showAccount}
            aria-controls="account-menu"
            aria-label="Account menu"
          >
            {user?.avatar_url ? (
              <img src={user.avatar_url} alt="" className="w-8 h-8 rounded-full" />
            ) : (
              <span className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                <User className="w-4 h-4 text-gray-500" aria-hidden="true" />
              </span>
            )}
            <span className="hidden sm:block text-left">
              <span className="block font-sans text-sm font-medium text-ink">{user?.display_name || 'Loading...'}</span>
              <span className="block text-xs text-gray-500">{user?.is_member ? 'Member' : 'Free'}</span>
            </span>
            <ChevronDown className="w-4 h-4 text-gray-400" aria-hidden="true" />
          </button>
          {showAccount ? (
            <div id="account-menu" role="menu" className="absolute right-0 mt-2 w-56 bg-card border border-rule rounded-lg shadow-lg z-50 py-1">
              <button role="menuitem" type="button" className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink hover:bg-card-2" onClick={() => { setShowAccount(false); setActiveView('profile'); }}>
                <User className="w-4 h-4 text-gray-500" aria-hidden="true" /> Profile
              </button>
              <button role="menuitem" type="button" className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink hover:bg-card-2" onClick={() => { setShowAccount(false); setActiveView('settings'); }}>
                <Settings className="w-4 h-4 text-gray-500" aria-hidden="true" /> Settings
              </button>
              <div className="my-1 border-t border-rule" />
              <a role="menuitem" href={signOutUrl()} className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-card-2">
                <LogOut className="w-4 h-4 text-gray-500" aria-hidden="true" /> Sign out
              </a>
            </div>
          ) : null}
        </div>
      </div>
    </header>
  );
}
