import { create } from 'zustand';
import type { MenuItem, PortalSettings, Project, User } from '@/types';
import { dropParam, pushLocation, readLocation } from './urlSync';

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
    { id: 'schengen', label: 'Schengen Tracker', icon: 'Globe', path: '/schengen' },
    { id: 'files', label: 'Files', icon: 'FolderOpen', path: '/files' },
    // ACCOUNT section
    { id: 'profile', label: 'My Profile', icon: 'User', path: '/profile' },
    { id: 'family', label: 'Family Members', icon: 'Users', path: '/family' },
    { id: 'membership', label: 'Membership', icon: 'CreditCard', path: '/membership' },
    { id: 'settings', label: 'Settings', icon: 'Settings', path: '/settings' },
    { id: 'help', label: 'Help', icon: 'HelpCircle', path: '/help' },
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

// Initial view, stage and guide from the URL (?view=, ?stage=, ?guide=)
const getInitialLocation = () =>
  typeof window !== 'undefined'
    ? readLocation(window.location.search)
    : { view: 'dashboard', stage: null, guide: null };

/** A numeric id from the URL, for links in emails: ?task=123, ?message=45. */
const getInitialId = (param: string): number | null => {
  if (typeof window === 'undefined') return null;
  const raw = new URLSearchParams(window.location.search).get(param);
  const id = raw ? Number.parseInt(raw, 10) : NaN;
  return Number.isFinite(id) && id > 0 ? id : null;
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
  /** Phones: the rail slides in over the page. */
  mobileNavOpen: boolean;
  setMobileNavOpen: (open: boolean) => void;

  // Track if sidebar was manually expanded (to restore after auto-collapse)
  sidebarManuallyExpanded: boolean;
  setSidebarManuallyExpanded: (expanded: boolean) => void;

  // Active view for navigation
  activeView: string;
  setActiveView: (view: string) => void;

  // Which journey stage the stage page shows
  activeStage: string | null;
  setActiveStage: (stage: string | null) => void;

  // Which guide the guide page shows, by slug
  activeGuide: string | null;
  setActiveGuide: (slug: string | null) => void;

  // A question handed to the assistant by another view, consumed on open
  chatDraft: string | null;
  setChatDraft: (draft: string | null) => void;

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
  /** A step to open in the drawer as soon as the steps view has it. */
  openTaskId: number | null;
  setOpenTaskId: (id: number | null) => void;
  /** A message to open as soon as Messages has it. */
  openMessageId: number | null;
  setOpenMessageId: (id: number | null) => void;
  /** A profile section to open and scroll to when Profile loads. */
  profileSection: string | null;
  setProfileSection: (id: string | null) => void;
  /** A letter to open in Documents, as "type" or "type|partner". */
  openLetter: string | null;
  setOpenLetter: (key: string | null) => void;

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
  const initialLocation = getInitialLocation();

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
    toggleSidebar: () =>
      set((state) => {
        const newCollapsed = !state.sidebarCollapsed;
        // Track manual expansion/collapse preference
        return {
          sidebarCollapsed: newCollapsed,
          sidebarManuallyExpanded: !newCollapsed,
        };
      }),
    setSidebarCollapsed: (sidebarCollapsed) => set({ sidebarCollapsed }),
    mobileNavOpen: false,
    setMobileNavOpen: (mobileNavOpen) => set({ mobileNavOpen }),

    // Track if sidebar was manually expanded (to restore after auto-collapse)
    sidebarManuallyExpanded: !initialSettings.layout.sidebarCollapsed,
    setSidebarManuallyExpanded: (sidebarManuallyExpanded) => set({ sidebarManuallyExpanded }),

    // Active view - initialized from URL ?view= parameter
    activeView: initialLocation.view,
    // Any navigation closes the phone menu, and goes into the browser
    // history so Back returns to the previous screen.
    setActiveView: (activeView) => {
      set({ activeView, mobileNavOpen: false });
      const { activeStage, activeGuide } = get();
      pushLocation({ view: activeView, stage: activeStage, guide: activeGuide });
    },
    activeStage: initialLocation.view === 'stage' ? initialLocation.stage : null,
    setActiveStage: (activeStage) => {
      set({ activeStage });
      // Moving between stages while on a stage page is a navigation too.
      const { activeView, activeGuide } = get();
      if (activeView === 'stage') pushLocation({ view: activeView, stage: activeStage, guide: activeGuide });
    },
    activeGuide: initialLocation.view === 'guide' ? initialLocation.guide : null,
    setActiveGuide: (activeGuide) => {
      set({ activeGuide });
      const { activeView, activeStage } = get();
      if (activeView === 'guide') pushLocation({ view: activeView, stage: activeStage, guide: activeGuide });
    },
    chatDraft: null,
    setChatDraft: (chatDraft) => set({ chatDraft }),

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
    openTaskId: getInitialId('task'),
    setOpenTaskId: (id) => {
      set({ openTaskId: id });
      // Consumed: a reload should not open it again.
      if (id === null) dropParam('task');
    },
    openMessageId: getInitialId('message'),
    setOpenMessageId: (id) => {
      set({ openMessageId: id });
      if (id === null) dropParam('message');
    },
    profileSection: null,
    setProfileSection: (id) => set({ profileSection: id }),
    openLetter: null,
    setOpenLetter: (key) => set({ openLetter: key }),

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

/**
 * Follow the browser's Back and Forward buttons: put the screen the URL
 * names back on show. Returns the cleanup for an effect.
 */
export function startUrlSync(): () => void {
  if (typeof window === 'undefined') return () => undefined;
  const onPopState = () => {
    const loc = readLocation(window.location.search);
    usePortalStore.setState((state) => ({
      activeView: loc.view,
      activeStage: loc.view === 'stage' ? loc.stage ?? state.activeStage : state.activeStage,
      activeGuide: loc.view === 'guide' ? loc.guide ?? state.activeGuide : state.activeGuide,
      mobileNavOpen: false,
    }));
  };
  window.addEventListener('popstate', onPopState);
  return () => window.removeEventListener('popstate', onPopState);
}
