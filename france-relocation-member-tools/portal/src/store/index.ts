import { create } from 'zustand';
import type { Project, User, PortalSettings, MenuItem } from '@/types';

// Default settings when not provided by PHP
const defaultSettings: PortalSettings = {
  colors: {
    primary: '#22c55e',
    secondary: '#3b82f6',
    accent: '#f59e0b',
    sidebarBg: '#1f2937',
    sidebarText: '#ffffff',
    headerBg: '#ffffff',
  },
  layout: {
    showWpHeader: false,
    showWpFooter: false,
    showPromoBanner: false,
    sidebarPosition: 'left',
    sidebarCollapsed: false,
  },
  branding: {
    title: 'Members Portal',
    logoUrl: '',
  },
  features: {
    notifications: true,
    fileUpload: true,
  },
  menu: [
    // PROJECT section
    { id: 'dashboard', label: 'Dashboard', icon: 'LayoutDashboard', path: '/dashboard' },
    { id: 'tasks', label: 'Tasks', icon: 'CheckSquare', path: '/tasks' },
    { id: 'checklists', label: 'Checklists', icon: 'ClipboardList', path: '/checklists' },
    { id: 'timeline', label: 'Timeline', icon: 'Calendar', path: '/timeline' },
    { id: 'messages', label: 'Messages', icon: 'MessageSquare', path: '/messages' },
    // RESOURCES section
    { id: 'research', label: 'Explore France', icon: 'MapPin', path: '/research' },
    { id: 'chat', label: 'Ask AI', icon: 'Bot', path: '/chat' },
    { id: 'documents', label: 'Documents', icon: 'FileText', path: '/documents' },
    { id: 'guides', label: 'Guides', icon: 'BookOpen', path: '/guides' },
    { id: 'glossary', label: 'Glossary', icon: 'BookMarked', path: '/glossary' },
    { id: 'files', label: 'Files', icon: 'FolderOpen', path: '/files' },
    // ACCOUNT section
    { id: 'profile', label: 'My Profile', icon: 'User', path: '/profile' },
    { id: 'family', label: 'Family Members', icon: 'Users', path: '/family' },
    { id: 'membership', label: 'Membership', icon: 'CreditCard', path: '/membership' },
    { id: 'settings', label: 'Settings', icon: 'Settings', path: '/settings' },
    { id: 'help', label: 'Help & Support', icon: 'HelpCircle', path: '/help' },
  ],
  customCss: '',
};

// Get settings from PHP or use defaults
const getInitialSettings = (): PortalSettings => {
  if (typeof window !== 'undefined' && window.PORTAL_SETTINGS) {
    return window.PORTAL_SETTINGS;
  }
  return defaultSettings;
};

interface PortalState {
  // User state
  user: User | null;
  setUser: (user: User | null) => void;

  // Current project
  currentProject: Project | null;
  setCurrentProject: (project: Project | null) => void;

  // Portal settings from PHP
  settings: PortalSettings;
  updateSettings: (settings: Partial<PortalSettings>) => void;

  // UI state
  sidebarCollapsed: boolean;
  toggleSidebar: () => void;
  setSidebarCollapsed: (collapsed: boolean) => void;

  // Active view for navigation
  activeView: string;
  setActiveView: (view: string) => void;

  // Settings tab navigation
  settingsTab: string | null;
  setSettingsTab: (tab: string | null) => void;

  // Task filters
  taskFilters: {
    stage: string | null;
    status: string | null;
    taskType: string | null;
  };
  setTaskFilters: (filters: Partial<PortalState['taskFilters']>) => void;
  resetTaskFilters: () => void;

  // Modal state
  activeModal: string | null;
  modalData: unknown;
  openModal: (modal: string, data?: unknown) => void;
  closeModal: () => void;

  // Menu helpers
  getMenuItems: () => MenuItem[];
  isMenuItemVisible: (id: string) => boolean;
}

export const usePortalStore = create<PortalState>((set, get) => {
  const initialSettings = getInitialSettings();

  return {
    // User state
    user: null,
    setUser: (user) => set({ user }),

    // Current project
    currentProject: null,
    setCurrentProject: (currentProject) => set({ currentProject }),

    // Portal settings
    settings: initialSettings,
    updateSettings: (newSettings) =>
      set((state) => ({
        settings: { ...state.settings, ...newSettings },
      })),

    // UI state - initialized from PHP settings
    sidebarCollapsed: initialSettings.layout.sidebarCollapsed,
    toggleSidebar: () => set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
    setSidebarCollapsed: (sidebarCollapsed) => set({ sidebarCollapsed }),

    // Active view
    activeView: 'dashboard',
    setActiveView: (activeView) => set({ activeView }),

    // Settings tab navigation
    settingsTab: null,
    setSettingsTab: (settingsTab) => set({ settingsTab }),

    // Task filters
    taskFilters: {
      stage: null,
      status: null,
      taskType: null,
    },
    setTaskFilters: (filters) =>
      set((state) => ({
        taskFilters: { ...state.taskFilters, ...filters },
      })),
    resetTaskFilters: () =>
      set({
        taskFilters: { stage: null, status: null, taskType: null },
      }),

    // Modal state
    activeModal: null,
    modalData: null,
    openModal: (activeModal, modalData = null) => set({ activeModal, modalData }),
    closeModal: () => set({ activeModal: null, modalData: null }),

    // Menu helpers
    getMenuItems: () => get().settings.menu,
    isMenuItemVisible: (id) => get().settings.menu.some((item) => item.id === id),
  };
});
