import { useState, useRef, useEffect } from 'react';
import { Bell, Search, User, CheckCircle, AlertTriangle, Info, X } from 'lucide-react';
import { useCurrentUser, useDashboard } from '@/hooks/useApi';
import { usePortalStore } from '@/store';

const viewTitles: Record<string, string> = {
  dashboard: 'Dashboard',
  tasks: 'Tasks',
  timeline: 'Timeline',
  messages: 'Messages',
  documents: 'Documents',
  guides: 'Guides',
  files: 'Files',
  family: 'Family Members',
  settings: 'Settings',
  help: 'Help & Support',
  profile: 'My Profile',
  checklists: 'Checklists',
  glossary: 'Glossary',
  chat: 'Ask AI',
  membership: 'Membership',
};

export default function Header() {
  const { activeView } = usePortalStore();
  const { data: user } = useCurrentUser();
  const { data: dashboardData } = useDashboard();
  const [showNotifications, setShowNotifications] = useState(false);
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

  // Build notifications from dashboard data
  const notifications = [];

  if (dashboardData?.task_stats?.overdue && dashboardData.task_stats.overdue > 0) {
    notifications.push({
      id: 'overdue',
      type: 'warning',
      title: `${dashboardData.task_stats.overdue} Overdue Task${dashboardData.task_stats.overdue > 1 ? 's' : ''}`,
      message: 'You have tasks that need immediate attention',
      time: 'Now',
    });
  }

  if (dashboardData?.upcoming_tasks && dashboardData.upcoming_tasks.length > 0) {
    notifications.push({
      id: 'upcoming',
      type: 'info',
      title: 'Upcoming Deadlines',
      message: `${dashboardData.upcoming_tasks.length} tasks due soon`,
      time: 'Today',
    });
  }

  if (dashboardData?.project?.days_until_move && dashboardData.project.days_until_move <= 30) {
    notifications.push({
      id: 'move-date',
      type: 'info',
      title: 'Move Date Approaching',
      message: `${dashboardData.project.days_until_move} days until your move`,
      time: 'Reminder',
    });
  }

  const notificationCount = notifications.length;

  return (
    <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      {/* Title */}
      <div>
        <h1 className="text-xl font-semibold text-gray-900">{title}</h1>
      </div>

      {/* Right side */}
      <div className="flex items-center gap-4">
        {/* Search */}
        <div className="relative hidden md:block">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            placeholder="Search..."
            className="w-64 pl-10 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          />
        </div>

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
                {notifications.length > 0 ? (
                  <div className="divide-y divide-gray-100">
                    {notifications.map((notification) => (
                      <div
                        key={notification.id}
                        className="px-4 py-3 hover:bg-gray-50 transition-colors"
                      >
                        <div className="flex gap-3">
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
                              {notification.time}
                            </p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="px-4 py-8 text-center">
                    <CheckCircle className="w-10 h-10 text-gray-300 mx-auto mb-2" />
                    <p className="text-sm text-gray-500">You're all caught up!</p>
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
            <p className="text-sm font-medium text-gray-700">
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
