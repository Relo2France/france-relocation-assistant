import { clsx } from 'clsx';
import {
  LayoutDashboard,
  CheckSquare,
  FileText,
  MessageSquare,
  Users,
  Settings,
  HelpCircle,
  ChevronLeft,
  ChevronRight,
  FolderKanban,
  BookOpen,
  Calendar,
} from 'lucide-react';
import { usePortalStore } from '@/store';
import type { NavSection } from '@/types';

const navigationSections: NavSection[] = [
  {
    id: 'project',
    label: 'PROJECT',
    items: [
      { id: 'dashboard', label: 'Dashboard', icon: 'LayoutDashboard', path: '/dashboard' },
      { id: 'tasks', label: 'Tasks', icon: 'CheckSquare', path: '/tasks' },
      { id: 'timeline', label: 'Timeline', icon: 'Calendar', path: '/timeline' },
      { id: 'messages', label: 'Messages', icon: 'MessageSquare', path: '/messages' },
    ],
  },
  {
    id: 'resources',
    label: 'RESOURCES',
    items: [
      { id: 'documents', label: 'Documents', icon: 'FileText', path: '/documents' },
      { id: 'guides', label: 'Guides', icon: 'BookOpen', path: '/guides' },
      { id: 'files', label: 'Files', icon: 'FolderKanban', path: '/files' },
    ],
  },
  {
    id: 'account',
    label: 'ACCOUNT',
    items: [
      { id: 'family', label: 'Family Members', icon: 'Users', path: '/family' },
      { id: 'settings', label: 'Settings', icon: 'Settings', path: '/settings' },
      { id: 'help', label: 'Help & Support', icon: 'HelpCircle', path: '/help' },
    ],
  },
];

const iconComponents: Record<string, React.ComponentType<{ className?: string }>> = {
  LayoutDashboard,
  CheckSquare,
  FileText,
  MessageSquare,
  Users,
  Settings,
  HelpCircle,
  FolderKanban,
  BookOpen,
  Calendar,
};

export default function Sidebar() {
  const { sidebarCollapsed, toggleSidebar, activeView, setActiveView } = usePortalStore();

  return (
    <aside
      className={clsx(
        'sidebar flex flex-col',
        sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'
      )}
    >
      {/* Logo */}
      <div className="flex items-center h-16 px-4 border-b border-gray-700/50">
        {!sidebarCollapsed && (
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-primary-500 flex items-center justify-center">
              <span className="text-white font-bold text-sm">R2F</span>
            </div>
            <span className="font-semibold text-sidebar-textActive">Members Portal</span>
          </div>
        )}
        {sidebarCollapsed && (
          <div className="w-8 h-8 rounded-lg bg-primary-500 flex items-center justify-center mx-auto">
            <span className="text-white font-bold text-sm">R</span>
          </div>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 scrollbar-thin">
        {navigationSections.map((section) => (
          <div key={section.id} className="mb-6">
            {!sidebarCollapsed && (
              <h3 className="px-4 mb-2 text-xs font-semibold text-sidebar-text/60 uppercase tracking-wider">
                {section.label}
              </h3>
            )}
            <ul className="space-y-1 px-2">
              {section.items.map((item) => {
                const Icon = iconComponents[item.icon];
                const isActive = activeView === item.id;

                return (
                  <li key={item.id}>
                    <button
                      onClick={() => setActiveView(item.id)}
                      className={clsx(
                        'nav-item w-full',
                        isActive && 'nav-item-active',
                        sidebarCollapsed && 'justify-center px-2'
                      )}
                      title={sidebarCollapsed ? item.label : undefined}
                    >
                      {Icon && <Icon className="w-5 h-5 flex-shrink-0" />}
                      {!sidebarCollapsed && <span>{item.label}</span>}
                      {item.badge && !sidebarCollapsed && (
                        <span className="ml-auto bg-primary-500 text-white text-xs px-2 py-0.5 rounded-full">
                          {item.badge}
                        </span>
                      )}
                    </button>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
      </nav>

      {/* Collapse toggle */}
      <button
        onClick={toggleSidebar}
        className="flex items-center justify-center h-12 border-t border-gray-700/50 text-sidebar-text hover:text-sidebar-textActive transition-colors"
        title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
      >
        {sidebarCollapsed ? (
          <ChevronRight className="w-5 h-5" />
        ) : (
          <>
            <ChevronLeft className="w-5 h-5" />
            <span className="ml-2 text-sm">Collapse</span>
          </>
        )}
      </button>
    </aside>
  );
}
