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
  FolderOpen,
  BookOpen,
  Calendar,
  LucideIcon,
  // New icons for added features
  ClipboardList,
  BookMarked,
  Bot,
  User,
  CreditCard,
} from 'lucide-react';
import { usePortalStore } from '@/store';
import type { MenuItem } from '@/types';

// Map icon names to components
const iconComponents: Record<string, LucideIcon> = {
  LayoutDashboard,
  CheckSquare,
  FileText,
  MessageSquare,
  Users,
  Settings,
  HelpCircle,
  FolderOpen,
  FolderKanban: FolderOpen,
  BookOpen,
  Calendar,
  // New icons for added features
  ClipboardList,
  BookMarked,
  Bot,
  User,
  CreditCard,
};

// Group menu items into sections for display
const groupMenuItems = (items: MenuItem[]) => {
  const sections = [
    {
      id: 'project',
      label: 'PROJECT',
      itemIds: ['dashboard', 'tasks', 'checklists', 'timeline', 'messages'],
    },
    {
      id: 'resources',
      label: 'RESOURCES',
      itemIds: ['documents', 'guides', 'glossary', 'chat', 'files'],
    },
    {
      id: 'account',
      label: 'ACCOUNT',
      itemIds: ['profile', 'family', 'membership', 'settings', 'help'],
    },
  ];

  return sections
    .map((section) => ({
      ...section,
      items: items.filter((item) => section.itemIds.includes(item.id)),
    }))
    .filter((section) => section.items.length > 0);
};

export default function Sidebar() {
  const { sidebarCollapsed, toggleSidebar, activeView, setActiveView, settings } = usePortalStore();
  const menuItems = settings.menu;
  const sections = groupMenuItems(menuItems);

  // Apply custom colors from settings
  const sidebarStyle = {
    '--sidebar-bg': settings.colors.sidebarBg,
    '--sidebar-text': settings.colors.sidebarText,
  } as React.CSSProperties;

  return (
    <aside
      className={clsx(
        'sidebar flex flex-col',
        sidebarCollapsed ? 'sidebar-collapsed' : 'sidebar-expanded'
      )}
      style={sidebarStyle}
    >
      {/* Logo */}
      <div className="flex items-center h-16 px-4 border-b border-gray-700/50">
        {!sidebarCollapsed && (
          <div className="flex items-center gap-3">
            {settings.branding.logoUrl ? (
              <img
                src={settings.branding.logoUrl}
                alt={settings.branding.title}
                className="h-8 w-auto"
              />
            ) : (
              <div
                className="w-8 h-8 rounded-lg flex items-center justify-center"
                style={{ backgroundColor: settings.colors.primary }}
              >
                <span className="text-white font-bold text-sm">R2F</span>
              </div>
            )}
            <span className="font-semibold text-sidebar-textActive">
              {settings.branding.title}
            </span>
          </div>
        )}
        {sidebarCollapsed && (
          <div
            className="w-8 h-8 rounded-lg flex items-center justify-center mx-auto"
            style={{ backgroundColor: settings.colors.primary }}
          >
            <span className="text-white font-bold text-sm">R</span>
          </div>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 scrollbar-thin">
        {sections.map((section) => (
          <div key={section.id} className="mb-6">
            {!sidebarCollapsed && (
              <h3 className="px-4 mb-2 text-xs font-semibold text-sidebar-text/60 uppercase tracking-wider">
                {section.label}
              </h3>
            )}
            <ul className="space-y-1 px-2">
              {section.items.map((item) => {
                const Icon = iconComponents[item.icon] || LayoutDashboard;
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
                      <Icon className="w-5 h-5 flex-shrink-0" />
                      {!sidebarCollapsed && <span>{item.label}</span>}
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
