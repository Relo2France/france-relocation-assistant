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
  MemberProfile,
  Checklist,
  ChecklistItem,
  ChecklistItemStatus,
  TaskChecklist,
  TaskChecklistItem,
  GeneratedDocument,
  DocumentGenerationRequest,
  DocumentGenerationResponse,
  GlossaryCategory,
  VerificationRequest,
  VerificationResult,
  PersonalizedGuide,
  ChatRequest,
  ChatResponse,
  KnowledgeCategory,
  MembershipInfo,
  Subscription,
  Payment,
  UpgradeOption,
  SupportTicketsResponse,
  SupportTicketDetailResponse,
  CreateTicketRequest,
  TicketReplyRequest,
  SchengenTrip,
  SchengenSummary,
  SchengenAlertSettings,
  SchengenFeatureStatus,
  SchengenSimulationResult,
  SchengenReportResponse,
  SchengenTestAlertResult,
  TaskFilters,
  FileFilters,
  NoteFilters,
  FamilyMember,
  FamilyMembersResponse,
  FamilyFeatureStatus,
  LocationSource,
  LocationStoreResponse,
  LocationHistoryResponse,
  LocationTodayStatus,
  GeocodeResult,
  LocationSettings,
  IPDetectionResult,
  CalendarProviderInfo,
  CalendarConnection,
  CalendarEvent,
  CalendarSyncResult,
  CalendarImportResult,
  CalendarICalImportResult,
  CalendarProvider,
  CalendarEventStatus,
  JurisdictionRule,
  JurisdictionSummary,
  MultiJurisdictionSummary,
  TrackedJurisdictionsResponse,
  JurisdictionType,
  NotificationItem,
  PushStatus,
  NotificationPreferences,
  CSVImportResult,
  CSVExportResult,
  SuggestionsResponse,
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
 * Extended request options with optional AbortSignal
 */
interface ApiRequestOptions extends Omit<RequestInit, 'signal'> {
  signal?: AbortSignal;
}

/**
 * Base fetch function with WordPress authentication and AbortController support
 *
 * @param endpoint - API endpoint (e.g., '/dashboard')
 * @param options - Fetch options including optional AbortSignal
 * @returns Promise resolving to the response data
 * @throws Error if request fails or is aborted
 */
async function apiFetch<T>(
  endpoint: string,
  options: ApiRequestOptions = {}
): Promise<T> {
  const wpData = getWpData();
  const url = `${wpData.apiUrl}${endpoint}`;
  const { signal, ...restOptions } = options;

  const response = await fetch(url, {
    ...restOptions,
    signal,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': wpData.nonce,
      ...restOptions.headers,
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

/**
 * FormData fetch function for file uploads
 * Similar to apiFetch but doesn't set Content-Type (browser sets it with boundary)
 */
async function apiFormDataFetch<T>(
  endpoint: string,
  formData: FormData,
  signal?: AbortSignal
): Promise<T> {
  const wpData = getWpData();
  const url = `${wpData.apiUrl}${endpoint}`;

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'X-WP-Nonce': wpData.nonce,
    },
    credentials: 'same-origin',
    body: formData,
    signal,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: 'Request failed',
    }));
    throw new Error(error.message || `HTTP error ${response.status}`);
  }

  return response.json();
}

/**
 * Create a cancellable API request
 *
 * Returns an object with the promise and an abort function.
 * Useful for long-running operations or when you need to cancel
 * on component unmount.
 *
 * @example
 * const { promise, abort } = createCancellableRequest('/chat', {
 *   method: 'POST',
 *   body: JSON.stringify({ message: 'Hello' })
 * });
 *
 * // Later, to cancel:
 * abort();
 *
 * // In useEffect cleanup:
 * useEffect(() => {
 *   const { promise, abort } = createCancellableRequest('/long-operation');
 *   promise.then(setData).catch(handleError);
 *   return () => abort();
 * }, []);
 */
export function createCancellableRequest<T>(
  endpoint: string,
  options: ApiRequestOptions = {}
): { promise: Promise<T>; abort: () => void } {
  const controller = new AbortController();

  const promise = apiFetch<T>(endpoint, {
    ...options,
    signal: controller.signal,
  });

  return {
    promise,
    abort: () => controller.abort(),
  };
}

/**
 * Check if an error is an AbortError (request was cancelled)
 */
export function isAbortError(error: unknown): boolean {
  return error instanceof Error && error.name === 'AbortError';
}

// Dashboard API
export const dashboardApi = {
  get: () => apiFetch<DashboardData>('/dashboard'),
  dismissWelcomeBanner: () => apiFetch<{ success: boolean }>('/welcome-banner/dismiss', { method: 'POST' }),
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
  list: (projectId: number, filters?: TaskFilters) => {
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

  deleteAccount: (confirmation: string) =>
    apiFetch<{ deleted: boolean; message: string }>('/account/delete', {
      method: 'POST',
      body: JSON.stringify({ confirmation }),
    }),
};

// Files API
export const filesApi = {
  list: (projectId: number, filters?: FileFilters) => {
    const params = new URLSearchParams();
    if (filters?.category) params.set('category', filters.category);
    if (filters?.file_type) params.set('file_type', filters.file_type);
    const query = params.toString();
    return apiFetch<PortalFile[]>(`/projects/${projectId}/files${query ? `?${query}` : ''}`);
  },

  get: (id: number) => apiFetch<PortalFile>(`/files/${id}`),

  /**
   * Upload a file with optional cancellation support
   * @param projectId - Project ID
   * @param file - File to upload
   * @param data - Optional metadata (category, description)
   * @param signal - Optional AbortSignal for cancellation
   */
  upload: (
    projectId: number,
    file: File,
    data?: { category?: FileCategory; description?: string },
    signal?: AbortSignal
  ) => {
    const formData = new FormData();
    formData.append('file', file);
    if (data?.category) formData.append('category', data.category);
    if (data?.description) formData.append('description', data.description);

    return apiFormDataFetch<PortalFile>(`/projects/${projectId}/files`, formData, signal);
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
  list: (projectId: number, filters?: NoteFilters) => {
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

// Profile API (Full 30+ fields)
export const profileApi = {
  get: () => apiFetch<MemberProfile>('/profile'),

  update: (data: Partial<MemberProfile>) =>
    apiFetch<MemberProfile>('/profile', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  getCompletion: () => apiFetch<{ percentage: number; missing_fields: string[] }>('/profile/completion'),

  /**
   * Reset all profile data and portal data (tasks, notes, files, etc.)
   * Keeps the user account but deletes all relocation-related data.
   * Requires confirmation phrase "RESET MY PROFILE" to prevent accidents.
   */
  reset: (confirmation: string) =>
    apiFetch<{ success: boolean; message: string }>('/profile/reset', {
      method: 'POST',
      body: JSON.stringify({ confirmation }),
    }),
};

// Checklists API
export const checklistsApi = {
  list: (visaType?: string) => {
    const params = new URLSearchParams();
    if (visaType) params.set('visa_type', visaType);
    const query = params.toString();
    return apiFetch<Checklist[]>(`/checklists${query ? `?${query}` : ''}`);
  },

  get: (type: string) => apiFetch<Checklist>(`/checklists/${type}`),

  updateItem: (
    checklistType: string,
    itemId: string,
    data: { status?: ChecklistItemStatus; handled_own?: boolean; notes?: string }
  ) =>
    apiFetch<ChecklistItem>(`/checklists/${checklistType}/items/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  // Task-specific mini checklists
  getTaskChecklist: (taskId: number) =>
    apiFetch<TaskChecklist>(`/tasks/${taskId}/checklist`),

  updateTaskChecklistItem: (taskId: number, itemId: string, completed: boolean) =>
    apiFetch<TaskChecklistItem>(`/tasks/${taskId}/checklist/${itemId}`, {
      method: 'PUT',
      body: JSON.stringify({ completed }),
    }),

  addTaskChecklistItem: (taskId: number, title: string) =>
    apiFetch<TaskChecklistItem>(`/tasks/${taskId}/checklist`, {
      method: 'POST',
      body: JSON.stringify({ title }),
    }),

  deleteTaskChecklistItem: (taskId: number, itemId: string) =>
    apiFetch<{ deleted: boolean }>(`/tasks/${taskId}/checklist/${itemId}`, {
      method: 'DELETE',
    }),
};

// Document Generation API
export const documentGeneratorApi = {
  getTypes: () =>
    apiFetch<{ type: string; label: string; description: string; requires_profile: string[] }[]>(
      '/documents/generator/types'
    ),

  preview: (data: DocumentGenerationRequest) =>
    apiFetch<DocumentGenerationResponse>('/documents/generator/preview', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  generate: (projectId: number, data: DocumentGenerationRequest) =>
    apiFetch<DocumentGenerationResponse>(`/projects/${projectId}/documents/generate`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  listGenerated: (projectId: number) =>
    apiFetch<GeneratedDocument[]>(`/projects/${projectId}/documents/generated`),

  downloadGenerated: (id: number, format: 'pdf' | 'docx' = 'pdf') => {
    const wpData = getWpData();
    return `${wpData.apiUrl}/documents/generated/${id}/download?format=${format}&_wpnonce=${wpData.nonce}`;
  },
};

// Glossary API
export const glossaryApi = {
  getAll: () => apiFetch<GlossaryCategory[]>('/glossary'),

  search: (query: string) =>
    apiFetch<GlossaryCategory[]>(`/glossary/search?q=${encodeURIComponent(query)}`),

  getCategory: (categoryId: string) =>
    apiFetch<GlossaryCategory>(`/glossary/category/${categoryId}`),
};

// AI Verification API
export const verificationApi = {
  verify: (data: VerificationRequest) =>
    apiFetch<VerificationResult>('/verify', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  verifyFile: (projectId: number, file: File, verificationType: string) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('verification_type', verificationType);

    return apiFormDataFetch<VerificationResult>(`/projects/${projectId}/verify`, formData);
  },

  getHistory: (projectId: number) =>
    apiFetch<VerificationResult[]>(`/projects/${projectId}/verify/history`),
};

// Personalized Guides API
export const guidesApi = {
  list: () => apiFetch<PersonalizedGuide[]>('/guides'),

  get: (guideType: string) => apiFetch<PersonalizedGuide>(`/guides/${guideType}`),

  getPersonalized: (guideType: string) =>
    apiFetch<PersonalizedGuide>(`/guides/${guideType}/personalized`),

  generateAI: (guideType: string) =>
    apiFetch<PersonalizedGuide>(`/guides/${guideType}/generate`, {
      method: 'POST',
    }),
};

// Knowledge Base Chat API
export const chatApi = {
  /**
   * Send a chat message with optional cancellation support
   * @param data - Chat request data
   * @param signal - Optional AbortSignal for cancellation
   */
  send: (data: ChatRequest, signal?: AbortSignal) =>
    apiFetch<ChatResponse>('/chat', {
      method: 'POST',
      body: JSON.stringify(data),
      signal,
    }),

  /**
   * Create a cancellable chat request
   * @returns Object with promise and abort function
   */
  sendCancellable: (data: ChatRequest) =>
    createCancellableRequest<ChatResponse>('/chat', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  getCategories: () => apiFetch<KnowledgeCategory[]>('/chat/categories'),

  searchTopics: (query: string, signal?: AbortSignal) =>
    apiFetch<{ results: { title: string; category: string; is_premium: boolean }[] }>(
      `/chat/search?q=${encodeURIComponent(query)}`,
      { signal }
    ),
};

// MemberPress/Membership API
export const membershipApi = {
  getInfo: () => apiFetch<MembershipInfo>('/membership'),

  getSubscriptions: () => apiFetch<Subscription[]>('/membership/subscriptions'),

  getPayments: () => apiFetch<Payment[]>('/membership/payments'),

  cancelSubscription: (subscriptionId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/membership/subscriptions/${subscriptionId}/cancel`, {
      method: 'POST',
    }),

  suspendSubscription: (subscriptionId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/membership/subscriptions/${subscriptionId}/suspend`, {
      method: 'POST',
    }),

  resumeSubscription: (subscriptionId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/membership/subscriptions/${subscriptionId}/resume`, {
      method: 'POST',
    }),

  getUpgradeOptions: () =>
    apiFetch<UpgradeOption[]>(
      '/membership/upgrade-options'
    ),
};

// Research API
export const researchApi = {
  // Communes search
  searchCommunes: (params: { department?: string; q?: string; limit?: number }, signal?: AbortSignal) => {
    const queryParams = new URLSearchParams();
    if (params.department) queryParams.set('department', params.department);
    if (params.q) queryParams.set('q', params.q);
    if (params.limit) queryParams.set('limit', String(params.limit));
    return apiFetch<{ communes: Array<{ code: string; name: string; postal_codes: string[]; department_code: string; department_name: string; region_code: string; region_name: string; population: number; type: 'city' | 'town' | 'village'; }> }>(
      `/research/communes/search?${queryParams.toString()}`,
      { signal }
    );
  },

  // Generate report
  generateReport: (data: { location_type: string; location_code: string; location_name: string; force_refresh?: boolean }) =>
    apiFetch<{
      report: {
        id: number;
        location_type: string;
        location_code: string;
        location_name: string;
        content: Record<string, unknown>;
        version: number;
        generated_at: string;
        updated_at: string;
        download_url: string;
      };
      cached: boolean;
      cache_age?: string;
      is_placeholder?: boolean;
      placeholder_reason?: string;
    }>('/research/report/generate', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // Save report to documents
  saveReport: (reportId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/research/report/${reportId}/save`, {
      method: 'POST',
    }),

  // Get saved reports
  getSavedReports: () =>
    apiFetch<{ reports: Array<{ id: number; location_name: string; location_type: string; updated_at: string; download_url: string; }> }>('/research/saved'),
};

// Support Ticket API
export const supportApi = {
  // Get all tickets for current user
  getTickets: () =>
    apiFetch<SupportTicketsResponse>('/support/tickets'),

  // Get a single ticket with replies
  getTicket: (ticketId: number) =>
    apiFetch<SupportTicketDetailResponse>(`/support/tickets/${ticketId}`),

  // Create a new support ticket
  createTicket: (data: CreateTicketRequest) =>
    apiFetch<{ success: boolean; message: string; ticket_id: number }>('/support/tickets', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // Reply to a ticket
  replyToTicket: (ticketId: number, data: TicketReplyRequest) =>
    apiFetch<{ success: boolean; message: string }>(`/support/tickets/${ticketId}/reply`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // Delete a ticket
  deleteTicket: (ticketId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/support/tickets/${ticketId}`, {
      method: 'DELETE',
    }),

  // Get unread count
  getUnreadCount: () =>
    apiFetch<{ count: number }>('/support/unread-count'),
};

// ============================================
// Family Members API (Paid Add-on Ready)
// ============================================

export const familyApi = {
  // Get feature status
  getFeatureStatus: () =>
    apiFetch<FamilyFeatureStatus>('/family/feature-status'),

  // Get all family members
  getAll: () =>
    apiFetch<FamilyMembersResponse>('/family'),

  // Get single family member
  get: (memberId: number) =>
    apiFetch<FamilyMember>(`/family/${memberId}`),

  // Create family member
  create: (data: Omit<FamilyMember, 'id' | 'created_at' | 'updated_at'>) =>
    apiFetch<FamilyMember>('/family', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // Update family member
  update: (memberId: number, data: Partial<FamilyMember>) =>
    apiFetch<FamilyMember>(`/family/${memberId}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  // Delete family member
  delete: (memberId: number) =>
    apiFetch<{ success: boolean; message: string }>(`/family/${memberId}`, {
      method: 'DELETE',
    }),
};

// ============================================
// Schengen Tracker API
// ============================================

export const schengenApi = {
  // Get all trips for current user
  getTrips: () => apiFetch<SchengenTrip[]>('/schengen/trips'),

  // Get single trip
  getTrip: (id: string) => apiFetch<SchengenTrip>(`/schengen/trips/${id}`),

  // Create a new trip
  createTrip: (data: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) =>
    apiFetch<SchengenTrip>('/schengen/trips', {
      method: 'POST',
      body: JSON.stringify({
        start_date: data.startDate,
        end_date: data.endDate,
        country: data.country,
        category: data.category,
        notes: data.notes,
      }),
    }),

  // Update an existing trip
  updateTrip: (id: string, data: Partial<SchengenTrip>) =>
    apiFetch<SchengenTrip>(`/schengen/trips/${id}`, {
      method: 'PUT',
      body: JSON.stringify({
        ...(data.startDate !== undefined && { start_date: data.startDate }),
        ...(data.endDate !== undefined && { end_date: data.endDate }),
        ...(data.country !== undefined && { country: data.country }),
        ...(data.category !== undefined && { category: data.category }),
        ...(data.notes !== undefined && { notes: data.notes }),
      }),
    }),

  // Delete a trip
  deleteTrip: (id: string) =>
    apiFetch<{ deleted: boolean; id: string }>(`/schengen/trips/${id}`, {
      method: 'DELETE',
    }),

  // Get summary (days used, days remaining, status, etc.)
  getSummary: () => apiFetch<SchengenSummary>('/schengen/summary'),

  // Get user settings
  getSettings: () => apiFetch<SchengenAlertSettings>('/schengen/settings'),

  // Update user settings
  updateSettings: (data: Partial<SchengenAlertSettings>) =>
    apiFetch<SchengenAlertSettings>('/schengen/settings', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  // Get feature status (premium gating)
  getFeatureStatus: () => apiFetch<SchengenFeatureStatus>('/schengen/feature-status'),

  // Simulate a trip (premium feature - planning tool)
  simulateTrip: (data: { startDate: string; endDate: string }) =>
    apiFetch<SchengenSimulationResult>('/schengen/simulate', {
      method: 'POST',
      body: JSON.stringify({
        start_date: data.startDate,
        end_date: data.endDate,
      }),
    }),

  // Generate PDF report (premium feature)
  generateReport: () => apiFetch<SchengenReportResponse>('/schengen/report'),

  // Test email alert (sends a test alert to current user)
  testAlert: () =>
    apiFetch<SchengenTestAlertResult>('/schengen/test-alert', {
      method: 'POST',
    }),

  // ============================================
  // Location Tracking (Phase 1)
  // ============================================

  // Store current location (check-in)
  storeLocation: (data: { lat: number; lng: number; accuracy?: number; source?: LocationSource }) =>
    apiFetch<LocationStoreResponse>('/schengen/location', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  // Get location history
  getLocationHistory: (options?: { limit?: number; offset?: number }) =>
    apiFetch<LocationHistoryResponse>('/schengen/location/history', {
      method: 'GET',
      ...(options && {
        headers: {
          'X-Query-Params': JSON.stringify(options),
        },
      }),
    }),

  // Get today's location status
  getTodayStatus: () => apiFetch<LocationTodayStatus>('/schengen/location/today'),

  // Reverse geocode coordinates
  geocode: (lat: number, lng: number) =>
    apiFetch<GeocodeResult>('/schengen/location/geocode', {
      method: 'POST',
      body: JSON.stringify({ lat, lng }),
    }),

  // Delete a location entry
  deleteLocation: (id: number) =>
    apiFetch<{ deleted: boolean; id: number }>(`/schengen/location/${id}`, {
      method: 'DELETE',
    }),

  // Clear all location history
  clearLocationHistory: () =>
    apiFetch<{ cleared: boolean; message: string }>('/schengen/location/clear', {
      method: 'POST',
    }),

  // Get location settings
  getLocationSettings: () => apiFetch<LocationSettings>('/schengen/location/settings'),

  // Update location settings
  updateLocationSettings: (data: Partial<LocationSettings>) =>
    apiFetch<LocationSettings>('/schengen/location/settings', {
      method: 'PUT',
      body: JSON.stringify({
        trackingEnabled: data.tracking_enabled,
        dailyReminder: data.daily_reminder,
        autoDetect: data.auto_detect,
      }),
    }),

  // Detect country from IP (fallback)
  detectFromIP: () => apiFetch<IPDetectionResult>('/schengen/location/detect'),

  // ============================================
  // Calendar Sync (Phase 2)
  // ============================================

  // Get available calendar providers
  getCalendarProviders: () =>
    apiFetch<CalendarProviderInfo[]>('/schengen/calendar/providers'),

  // Get user's calendar connections
  getCalendarConnections: () =>
    apiFetch<CalendarConnection[]>('/schengen/calendar/connections'),

  // Start OAuth connection flow (returns auth URL)
  connectCalendar: (provider: CalendarProvider) =>
    apiFetch<{ authUrl: string }>('/schengen/calendar/connect', {
      method: 'POST',
      body: JSON.stringify({ provider }),
    }),

  // Disconnect a calendar provider
  disconnectCalendar: (connectionId: number) =>
    apiFetch<{ disconnected: boolean; id: number }>(`/schengen/calendar/connections/${connectionId}`, {
      method: 'DELETE',
    }),

  // Trigger manual sync for a connection
  syncCalendar: (connectionId: number) =>
    apiFetch<CalendarSyncResult>(`/schengen/calendar/connections/${connectionId}/sync`, {
      method: 'POST',
    }),

  // Get detected calendar events
  getCalendarEvents: (status?: CalendarEventStatus | 'all') =>
    apiFetch<CalendarEvent[]>(`/schengen/calendar/events${status ? `?status=${status}` : ''}`),

  // Import calendar events as trips
  importCalendarEvents: (eventIds: number[]) =>
    apiFetch<CalendarImportResult>('/schengen/calendar/events/import', {
      method: 'POST',
      body: JSON.stringify({ event_ids: eventIds }),
    }),

  // Skip calendar events
  skipCalendarEvents: (eventIds: number[]) =>
    apiFetch<{ skipped: number }>('/schengen/calendar/events/skip', {
      method: 'POST',
      body: JSON.stringify({ event_ids: eventIds }),
    }),

  // Import iCal file
  importICalFile: (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return apiFormDataFetch<CalendarICalImportResult>('/schengen/calendar/import-ical', formData);
  },

  // ============================================
  // CSV Import/Export API (Phase 6)
  // ============================================

  // Import trips from CSV
  importTripsCSV: (csv: string, skipDuplicates = true) =>
    apiFetch<CSVImportResult>('/schengen/trips/import', {
      method: 'POST',
      body: JSON.stringify({ csv, skip_duplicates: skipDuplicates }),
    }),

  // Export trips to CSV
  exportTripsCSV: () =>
    apiFetch<CSVExportResult>('/schengen/trips/export'),

  // ============================================
  // AI Suggestions API (Phase 7)
  // ============================================

  // Get AI-powered trip planning suggestions
  getSuggestions: () =>
    apiFetch<SuggestionsResponse>('/schengen/suggestions'),

  // ============================================
  // Jurisdiction API (Phase 3)
  // ============================================

  // Get all available jurisdiction rules
  getJurisdictions: (type?: JurisdictionType) =>
    apiFetch<JurisdictionRule[]>(
      type ? `/schengen/jurisdictions?type=${type}` : '/schengen/jurisdictions'
    ),

  // Get a single jurisdiction rule by code
  getJurisdiction: (code: string) =>
    apiFetch<JurisdictionRule>(`/schengen/jurisdictions/${code}`),

  // Get user's tracked jurisdictions
  getTrackedJurisdictions: () =>
    apiFetch<JurisdictionRule[]>('/schengen/jurisdictions/tracked'),

  // Add a jurisdiction to tracking
  addTrackedJurisdiction: (code: string) =>
    apiFetch<TrackedJurisdictionsResponse>('/schengen/jurisdictions/tracked', {
      method: 'POST',
      body: JSON.stringify({ code }),
    }),

  // Remove a jurisdiction from tracking
  removeTrackedJurisdiction: (code: string) =>
    apiFetch<TrackedJurisdictionsResponse>(`/schengen/jurisdictions/tracked/${code}`, {
      method: 'DELETE',
    }),

  // Get summary for a specific jurisdiction
  getJurisdictionSummary: (code: string, date?: string) =>
    apiFetch<JurisdictionSummary>(
      date
        ? `/schengen/jurisdictions/${code}/summary?date=${date}`
        : `/schengen/jurisdictions/${code}/summary`
    ),

  // Get summary for all tracked jurisdictions
  getMultiJurisdictionSummary: () =>
    apiFetch<MultiJurisdictionSummary>('/schengen/jurisdictions/summary'),

  // ============================================
  // Notifications API (Phase 5)
  // ============================================

  // Get user's notifications
  getNotifications: (unreadOnly = false) =>
    apiFetch<NotificationItem[]>(
      unreadOnly ? '/schengen/notifications?unread_only=true' : '/schengen/notifications'
    ),

  // Get unread notification count
  getNotificationUnreadCount: () =>
    apiFetch<{ count: number }>('/schengen/notifications/unread-count'),

  // Mark a notification as read
  markNotificationRead: (id: number) =>
    apiFetch<{ success: boolean }>(`/schengen/notifications/${id}/read`, {
      method: 'POST',
    }),

  // Mark all notifications as read
  markAllNotificationsRead: () =>
    apiFetch<{ success: boolean }>('/schengen/notifications/read-all', {
      method: 'POST',
    }),

  // Delete a notification
  deleteNotification: (id: number) =>
    apiFetch<{ deleted: boolean }>(`/schengen/notifications/${id}`, {
      method: 'DELETE',
    }),

  // Get push subscription status
  getPushStatus: () =>
    apiFetch<PushStatus>('/schengen/push/status'),

  // Subscribe to push notifications
  subscribePush: (subscription: {
    endpoint: string;
    p256dh: string;
    auth: string;
    userAgent?: string;
  }) =>
    apiFetch<{ subscribed: boolean; id: number }>('/schengen/push/subscribe', {
      method: 'POST',
      body: JSON.stringify(subscription),
    }),

  // Unsubscribe from push notifications
  unsubscribePush: (endpoint: string) =>
    apiFetch<{ unsubscribed: boolean }>('/schengen/push/unsubscribe', {
      method: 'POST',
      body: JSON.stringify({ endpoint }),
    }),

  // Get VAPID public key
  getVapidKey: () =>
    apiFetch<{ publicKey: string }>('/schengen/push/vapid-key'),

  // Get notification preferences
  getNotificationPreferences: () =>
    apiFetch<NotificationPreferences>('/schengen/notifications/preferences'),

  // Update notification preferences
  updateNotificationPreferences: (prefs: Partial<NotificationPreferences>) =>
    apiFetch<{ success: boolean; preferences: NotificationPreferences }>(
      '/schengen/notifications/preferences',
      {
        method: 'PUT',
        body: JSON.stringify(prefs),
      }
    ),

  // Send test notification
  sendTestNotification: () =>
    apiFetch<{ success: boolean; notificationId: number; pushSent: boolean }>(
      '/schengen/notifications/test',
      {
        method: 'POST',
      }
    ),
};
