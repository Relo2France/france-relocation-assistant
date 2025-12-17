import type {
  DashboardData,
  Project,
  Task,
  Stage,
  Activity,
  User,
  UserSettings,
  UpdateProfileData,
  TaskStatus,
  PortalFile,
  FileCategory,
  Note,
  NoteVisibility,
} from '@/types';

/**
 * Get WordPress data from localized script
 */
function getWpData() {
  return window.fraPortalData || {
    apiUrl: '/wp-json/fra-portal/v1',
    nonce: '',
    userId: 0,
    siteUrl: '',
    pluginUrl: '',
    isAdmin: false,
  };
}

/**
 * Base fetch function with WordPress authentication
 */
async function apiFetch<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const wpData = getWpData();
  const url = `${wpData.apiUrl}${endpoint}`;

  const response = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpData.nonce,
      ...options.headers,
    },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: 'An error occurred',
    }));
    throw new Error(error.message || `HTTP error ${response.status}`);
  }

  return response.json();
}

// Dashboard API
export const dashboardApi = {
  get: () => apiFetch<DashboardData>('/dashboard'),
};

// Projects API
export const projectsApi = {
  list: () => apiFetch<Project[]>('/projects'),

  get: (id: number) => apiFetch<Project>(`/projects/${id}`),

  create: (data: Partial<Project>) =>
    apiFetch<Project>('/projects', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: number, data: Partial<Project>) =>
    apiFetch<Project>(`/projects/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: number) =>
    apiFetch<{ deleted: boolean }>(`/projects/${id}`, {
      method: 'DELETE',
    }),
};

// Tasks API
export const tasksApi = {
  list: (projectId: number, filters?: { stage?: string; status?: string; task_type?: string }) => {
    const params = new URLSearchParams();
    if (filters?.stage) params.set('stage', filters.stage);
    if (filters?.status) params.set('status', filters.status);
    if (filters?.task_type) params.set('task_type', filters.task_type);
    const query = params.toString();
    return apiFetch<Task[]>(`/projects/${projectId}/tasks${query ? `?${query}` : ''}`);
  },

  get: (id: number) => apiFetch<Task>(`/tasks/${id}`),

  create: (projectId: number, data: Partial<Task>) =>
    apiFetch<Task>(`/projects/${projectId}/tasks`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: number, data: Partial<Task>) =>
    apiFetch<Task>(`/tasks/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  updateStatus: (id: number, status: TaskStatus) =>
    apiFetch<Task>(`/tasks/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    }),

  delete: (id: number) =>
    apiFetch<{ deleted: boolean }>(`/tasks/${id}`, {
      method: 'DELETE',
    }),

  reorder: (order: Record<number, number>) =>
    apiFetch<{ success: boolean }>('/tasks/reorder', {
      method: 'POST',
      body: JSON.stringify({ order }),
    }),
};

// Stages API
export const stagesApi = {
  getByVisaType: (visaType: string) =>
    apiFetch<Stage[]>(`/stages/${visaType}`),
};

// Activity API
export const activityApi = {
  list: (projectId: number, options?: { limit?: number; offset?: number; grouped?: boolean }) => {
    const params = new URLSearchParams();
    if (options?.limit) params.set('limit', String(options.limit));
    if (options?.offset) params.set('offset', String(options.offset));
    if (options?.grouped) params.set('grouped', 'true');
    const query = params.toString();
    return apiFetch<Activity[]>(`/projects/${projectId}/activity${query ? `?${query}` : ''}`);
  },
};

// User API
export const userApi = {
  me: () => apiFetch<User>('/me'),

  updateProfile: (data: UpdateProfileData) =>
    apiFetch<User>('/me', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  getSettings: () => apiFetch<UserSettings>('/me/settings'),

  updateSettings: (data: Partial<UserSettings>) =>
    apiFetch<UserSettings>('/me/settings', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),
};

// Files API
export const filesApi = {
  list: (projectId: number, filters?: { category?: FileCategory; file_type?: string }) => {
    const params = new URLSearchParams();
    if (filters?.category) params.set('category', filters.category);
    if (filters?.file_type) params.set('file_type', filters.file_type);
    const query = params.toString();
    return apiFetch<PortalFile[]>(`/projects/${projectId}/files${query ? `?${query}` : ''}`);
  },

  get: (id: number) => apiFetch<PortalFile>(`/files/${id}`),

  upload: async (projectId: number, file: File, data?: { category?: FileCategory; description?: string }) => {
    const wpData = getWpData();
    const formData = new FormData();
    formData.append('file', file);
    if (data?.category) formData.append('category', data.category);
    if (data?.description) formData.append('description', data.description);

    const response = await fetch(`${wpData.apiUrl}/projects/${projectId}/files`, {
      method: 'POST',
      headers: {
        'X-WP-Nonce': wpData.nonce,
      },
      credentials: 'same-origin',
      body: formData,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: 'Upload failed' }));
      throw new Error(error.message || `HTTP error ${response.status}`);
    }

    return response.json() as Promise<PortalFile>;
  },

  update: (id: number, data: { category?: FileCategory; description?: string }) =>
    apiFetch<PortalFile>(`/files/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: number) =>
    apiFetch<{ deleted: boolean }>(`/files/${id}`, {
      method: 'DELETE',
    }),

  download: (id: number) => {
    const wpData = getWpData();
    // Return the download URL - browser will handle the download
    return `${wpData.apiUrl}/files/${id}/download?_wpnonce=${wpData.nonce}`;
  },
};

// Notes API
export const notesApi = {
  list: (projectId: number, filters?: { task_id?: number; pinned?: boolean }) => {
    const params = new URLSearchParams();
    if (filters?.task_id) params.set('task_id', String(filters.task_id));
    if (filters?.pinned) params.set('pinned', 'true');
    const query = params.toString();
    return apiFetch<Note[]>(`/projects/${projectId}/notes${query ? `?${query}` : ''}`);
  },

  get: (id: number) => apiFetch<Note>(`/notes/${id}`),

  create: (projectId: number, data: { content: string; task_id?: number; visibility?: NoteVisibility }) =>
    apiFetch<Note>(`/projects/${projectId}/notes`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: number, data: { content?: string; visibility?: NoteVisibility }) =>
    apiFetch<Note>(`/notes/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: number) =>
    apiFetch<{ deleted: boolean }>(`/notes/${id}`, {
      method: 'DELETE',
    }),

  togglePin: (id: number) =>
    apiFetch<Note>(`/notes/${id}/pin`, {
      method: 'PATCH',
    }),
};
