import { Bell, Search, User } from 'lucide-react';
import { useCurrentUser } from '@/hooks/useApi';
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
};

export default function Header() {
  const { activeView } = usePortalStore();
  const { data: user } = useCurrentUser();

  const title = viewTitles[activeView] || 'Dashboard';

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
        <button className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
          <Bell className="w-5 h-5" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

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
