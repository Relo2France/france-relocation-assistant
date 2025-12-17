import { create } from 'zustand';
import type { Project, User } from '@/types';

interface PortalState {
  // User state
  user: User | null;
  setUser: (user: User | null) => void;

  // Current project
  currentProject: Project | null;
  setCurrentProject: (project: Project | null) => void;

  // UI state
  sidebarCollapsed: boolean;
  toggleSidebar: () => void;
  setSidebarCollapsed: (collapsed: boolean) => void;

  // Active view for navigation
  activeView: string;
  setActiveView: (view: string) => void;

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
}

export const usePortalStore = create<PortalState>((set) => ({
  // User state
  user: null,
  setUser: (user) => set({ user }),

  // Current project
  currentProject: null,
  setCurrentProject: (currentProject) => set({ currentProject }),

  // UI state
  sidebarCollapsed: false,
  toggleSidebar: () => set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
  setSidebarCollapsed: (sidebarCollapsed) => set({ sidebarCollapsed }),

  // Active view
  activeView: 'dashboard',
  setActiveView: (activeView) => set({ activeView }),

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
}));
