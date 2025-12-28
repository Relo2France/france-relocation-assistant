import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { STALE_TIME, SEARCH, REFETCH_INTERVAL } from '@/constants';
import {
  dashboardApi,
  projectsApi,
  tasksApi,
  activityApi,
  userApi,
  filesApi,
  notesApi,
  profileApi,
  checklistsApi,
  documentGeneratorApi,
  glossaryApi,
  verificationApi,
  guidesApi,
  chatApi,
  membershipApi,
  supportApi,
  researchApi,
  familyApi,
  schengenApi,
} from '@/api/client';
import type {
  FamilyMember,
  Task,
  TaskStatus,
  Project,
  FileCategory,
  NoteVisibility,
  UpdateProfileData,
  UserSettings,
  MemberProfile,
  ChecklistItemStatus,
  DocumentGenerationRequest,
  ChatRequest,
  DashboardData,
  SchengenTrip,
  SchengenAlertSettings,
  TaskFilters,
  FileFilters,
  NoteFilters,
  JurisdictionType,
} from '@/types';

// Query keys
export const queryKeys = {
  dashboard: ['dashboard'] as const,
  projects: ['projects'] as const,
  project: (id: number) => ['project', id] as const,
  tasks: (projectId: number, filters?: TaskFilters) => ['tasks', projectId, filters] as const,
  task: (id: number) => ['task', id] as const,
  taskChecklist: (taskId: number) => ['taskChecklist', taskId] as const,
  activity: (projectId: number) => ['activity', projectId] as const,
  user: ['user'] as const,
  userSettings: ['userSettings'] as const,
  files: (projectId: number, filters?: FileFilters) => ['files', projectId, filters] as const,
  file: (id: number) => ['file', id] as const,
  notes: (projectId: number, filters?: NoteFilters) => ['notes', projectId, filters] as const,
  note: (id: number) => ['note', id] as const,
  // New keys
  profile: ['profile'] as const,
  profileCompletion: ['profileCompletion'] as const,
  checklists: (visaType?: string) => ['checklists', visaType] as const,
  checklist: (type: string) => ['checklist', type] as const,
  documentTypes: ['documentTypes'] as const,
  generatedDocuments: (projectId: number) => ['generatedDocuments', projectId] as const,
  glossary: ['glossary'] as const,
  glossarySearch: (query: string) => ['glossary', 'search', query] as const,
  verificationHistory: (projectId: number) => ['verificationHistory', projectId] as const,
  guides: ['guides'] as const,
  chatSearch: (query: string) => ['chatSearch', query] as const,
  guide: (type: string) => ['guide', type] as const,
  personalizedGuide: (type: string) => ['personalizedGuide', type] as const,
  chatCategories: ['chatCategories'] as const,
  membership: ['membership'] as const,
  subscriptions: ['subscriptions'] as const,
  payments: ['payments'] as const,
  upgradeOptions: ['upgradeOptions'] as const,
  // Support tickets
  supportTickets: ['supportTickets'] as const,
  supportTicket: (id: number) => ['supportTicket', id] as const,
  supportUnreadCount: ['supportUnreadCount'] as const,
  // Research
  savedReports: ['savedReports'] as const,
  // Family members
  familyMembers: ['familyMembers'] as const,
  familyMember: (id: number) => ['familyMember', id] as const,
  familyFeatureStatus: ['familyFeatureStatus'] as const,
  // Schengen tracker
  schengenTrips: ['schengenTrips'] as const,
  schengenTrip: (id: string) => ['schengenTrip', id] as const,
  schengenSummary: ['schengenSummary'] as const,
  schengenSettings: ['schengenSettings'] as const,
  schengenFeatureStatus: ['schengenFeatureStatus'] as const,
  // Schengen location tracking (Phase 1)
  schengenLocationHistory: ['schengenLocationHistory'] as const,
  schengenLocationToday: ['schengenLocationToday'] as const,
  schengenLocationSettings: ['schengenLocationSettings'] as const,
};

// Dashboard hook
export function useDashboard() {
  return useQuery({
    queryKey: queryKeys.dashboard,
    queryFn: dashboardApi.get,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useDismissWelcomeBanner() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: dashboardApi.dismissWelcomeBanner,
    onSuccess: () => {
      // Update the dashboard data to remove the banner
      queryClient.setQueryData(queryKeys.dashboard, (oldData: DashboardData | undefined) => {
        if (!oldData) return oldData;
        return { ...oldData, welcome_banner: null };
      });
    },
  });
}

// User hook
export function useCurrentUser() {
  return useQuery({
    queryKey: queryKeys.user,
    queryFn: userApi.me,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: UpdateProfileData) => userApi.updateProfile(data),
    onSuccess: (updatedUser) => {
      queryClient.setQueryData(queryKeys.user, updatedUser);
    },
  });
}

export function useUserSettings() {
  return useQuery({
    queryKey: queryKeys.userSettings,
    queryFn: userApi.getSettings,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useUpdateSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Partial<UserSettings>) => userApi.updateSettings(data),
    // Optimistic update: immediately update cache before API completes
    onMutate: async (newData) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.userSettings });

      const previousSettings = queryClient.getQueryData<UserSettings>(queryKeys.userSettings);

      if (previousSettings) {
        queryClient.setQueryData<UserSettings>(queryKeys.userSettings, {
          ...previousSettings,
          ...newData,
        });
      }

      return { previousSettings };
    },
    onError: (_err, _newData, context) => {
      if (context?.previousSettings) {
        queryClient.setQueryData(queryKeys.userSettings, context.previousSettings);
      }
    },
    onSuccess: (updatedSettings) => {
      queryClient.setQueryData(queryKeys.userSettings, updatedSettings);
    },
  });
}

export function useDeleteAccount() {
  return useMutation({
    mutationFn: (confirmation: string) => userApi.deleteAccount(confirmation),
  });
}

export function useResetProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (confirmation: string) => profileApi.reset(confirmation),
    onSuccess: () => {
      // Invalidate all queries to force refresh after reset
      queryClient.invalidateQueries();
    },
  });
}

// Projects hooks
export function useProjects() {
  return useQuery({
    queryKey: queryKeys.projects,
    queryFn: projectsApi.list,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useProject(id: number) {
  return useQuery({
    queryKey: queryKeys.project(id),
    queryFn: () => projectsApi.get(id),
    enabled: id > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useUpdateProject() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Project> }) =>
      projectsApi.update(id, data),
    onSuccess: (updatedProject) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
      queryClient.invalidateQueries({ queryKey: queryKeys.projects });
      queryClient.setQueryData(queryKeys.project(updatedProject.id), updatedProject);
    },
  });
}

// Tasks hooks
export function useTasks(projectId: number, filters?: TaskFilters) {
  return useQuery({
    queryKey: queryKeys.tasks(projectId, filters),
    queryFn: () => tasksApi.list(projectId, filters),
    enabled: projectId > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useTask(id: number) {
  return useQuery({
    queryKey: queryKeys.task(id),
    queryFn: () => tasksApi.get(id),
    enabled: id > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useCreateTask(projectId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Partial<Task>) => tasksApi.create(projectId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tasks(projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
    },
  });
}

export function useUpdateTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Task> }) =>
      tasksApi.update(id, data),
    onSuccess: (updatedTask) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tasks(updatedTask.project_id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
      queryClient.setQueryData(queryKeys.task(updatedTask.id), updatedTask);
    },
  });
}

export function useUpdateTaskStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, status }: { id: number; status: TaskStatus }) =>
      tasksApi.updateStatus(id, status),
    onSuccess: (updatedTask) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tasks(updatedTask.project_id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
      queryClient.setQueryData(queryKeys.task(updatedTask.id), updatedTask);
    },
  });
}

export function useDeleteTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, projectId }: { id: number; projectId: number }) =>
      tasksApi.delete(id).then((result) => ({ ...result, projectId })),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.tasks(variables.projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
    },
  });
}

// Activity hooks
export function useActivity(projectId: number, options?: { limit?: number; offset?: number }) {
  return useQuery({
    queryKey: queryKeys.activity(projectId),
    queryFn: () => activityApi.list(projectId, options),
    enabled: projectId > 0,
    staleTime: STALE_TIME.DYNAMIC, // 10 seconds - activity is more dynamic
  });
}

// Files hooks
export function useFiles(projectId: number, filters?: FileFilters) {
  return useQuery({
    queryKey: queryKeys.files(projectId, filters),
    queryFn: () => filesApi.list(projectId, filters),
    enabled: projectId > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useFile(id: number) {
  return useQuery({
    queryKey: queryKeys.file(id),
    queryFn: () => filesApi.get(id),
    enabled: id > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useUploadFile(projectId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ file, data }: { file: File; data?: { category?: FileCategory; description?: string } }) =>
      filesApi.upload(projectId, file, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.files(projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
    },
  });
}

export function useUpdateFile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: { category?: FileCategory; description?: string } }) =>
      filesApi.update(id, data),
    onSuccess: (updatedFile) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.files(updatedFile.project_id) });
      queryClient.setQueryData(queryKeys.file(updatedFile.id), updatedFile);
    },
  });
}

export function useDeleteFile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, projectId }: { id: number; projectId: number }) =>
      filesApi.delete(id).then((result) => ({ ...result, projectId })),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.files(variables.projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard });
    },
  });
}

export function useDownloadFile() {
  return {
    download: (id: number) => {
      const url = filesApi.download(id);
      // Open in new tab or trigger download
      window.open(url, '_blank');
    },
  };
}

// Notes hooks
export function useNotes(projectId: number, filters?: NoteFilters) {
  return useQuery({
    queryKey: queryKeys.notes(projectId, filters),
    queryFn: () => notesApi.list(projectId, filters),
    enabled: projectId > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useNote(id: number) {
  return useQuery({
    queryKey: queryKeys.note(id),
    queryFn: () => notesApi.get(id),
    enabled: id > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useCreateNote(projectId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: { content: string; task_id?: number; visibility?: NoteVisibility }) =>
      notesApi.create(projectId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.notes(projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.activity(projectId) });
    },
  });
}

export function useUpdateNote() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: { content?: string; visibility?: NoteVisibility } }) =>
      notesApi.update(id, data),
    onSuccess: (updatedNote) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.notes(updatedNote.project_id) });
      queryClient.setQueryData(queryKeys.note(updatedNote.id), updatedNote);
    },
  });
}

export function useDeleteNote() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, projectId }: { id: number; projectId: number }) =>
      notesApi.delete(id).then((result) => ({ ...result, projectId })),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.notes(variables.projectId) });
    },
  });
}

export function useToggleNotePin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => notesApi.togglePin(id),
    onSuccess: (updatedNote) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.notes(updatedNote.project_id) });
      queryClient.setQueryData(queryKeys.note(updatedNote.id), updatedNote);
    },
  });
}

// ============================================
// Profile Hooks (Full 30+ fields)
// ============================================

export function useMemberProfile() {
  return useQuery({
    queryKey: queryKeys.profile,
    queryFn: profileApi.get,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useUpdateMemberProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Partial<MemberProfile>) => profileApi.update(data),
    // Optimistic update: immediately update cache before API completes
    onMutate: async (newData) => {
      // Cancel outgoing refetches
      await queryClient.cancelQueries({ queryKey: queryKeys.profile });

      // Snapshot previous value
      const previousProfile = queryClient.getQueryData<MemberProfile>(queryKeys.profile);

      // Optimistically update cache
      if (previousProfile) {
        queryClient.setQueryData<MemberProfile>(queryKeys.profile, {
          ...previousProfile,
          ...newData,
        });
      }

      // Return context for rollback
      return { previousProfile };
    },
    onError: (_err, _newData, context) => {
      // Roll back on error
      if (context?.previousProfile) {
        queryClient.setQueryData(queryKeys.profile, context.previousProfile);
      }
    },
    onSuccess: (updatedProfile) => {
      // Replace with server data (in case of computed fields)
      queryClient.setQueryData(queryKeys.profile, updatedProfile);
      queryClient.invalidateQueries({ queryKey: queryKeys.profileCompletion });
    },
  });
}

export function useProfileCompletion() {
  return useQuery({
    queryKey: queryKeys.profileCompletion,
    queryFn: profileApi.getCompletion,
    staleTime: STALE_TIME.SHORT, // 1 minute
  });
}

// ============================================
// Checklists Hooks
// ============================================

export function useChecklists(visaType?: string) {
  return useQuery({
    queryKey: queryKeys.checklists(visaType),
    queryFn: () => checklistsApi.list(visaType),
    staleTime: STALE_TIME.SHORT, // 1 minute - static data
  });
}

export function useChecklist(type: string) {
  return useQuery({
    queryKey: queryKeys.checklist(type),
    queryFn: () => checklistsApi.get(type),
    enabled: !!type,
    staleTime: STALE_TIME.SHORT, // 1 minute - static data
  });
}

export function useUpdateChecklistItem() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      checklistType,
      itemId,
      data,
    }: {
      checklistType: string;
      itemId: string;
      data: { status?: ChecklistItemStatus; handled_own?: boolean; notes?: string };
    }) => checklistsApi.updateItem(checklistType, itemId, data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.checklist(variables.checklistType) });
      queryClient.invalidateQueries({ queryKey: queryKeys.checklists() });
    },
  });
}

// Task-specific mini checklists
export function useTaskChecklist(taskId: number) {
  return useQuery({
    queryKey: queryKeys.taskChecklist(taskId),
    queryFn: () => checklistsApi.getTaskChecklist(taskId),
    enabled: taskId > 0,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useUpdateTaskChecklistItem(taskId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ itemId, completed }: { itemId: string; completed: boolean }) =>
      checklistsApi.updateTaskChecklistItem(taskId, itemId, completed),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.taskChecklist(taskId) });
    },
  });
}

export function useAddTaskChecklistItem(taskId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (title: string) => checklistsApi.addTaskChecklistItem(taskId, title),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.taskChecklist(taskId) });
    },
  });
}

export function useDeleteTaskChecklistItem(taskId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (itemId: string) => checklistsApi.deleteTaskChecklistItem(taskId, itemId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.taskChecklist(taskId) });
    },
  });
}

// ============================================
// Document Generation Hooks
// ============================================

export function useDocumentTypes() {
  return useQuery({
    queryKey: queryKeys.documentTypes,
    queryFn: documentGeneratorApi.getTypes,
    staleTime: STALE_TIME.LONG, // 1 hour
  });
}

export function useGeneratedDocuments(projectId: number) {
  return useQuery({
    queryKey: queryKeys.generatedDocuments(projectId),
    queryFn: () => documentGeneratorApi.listGenerated(projectId),
    enabled: projectId > 0,
    staleTime: STALE_TIME.SHORT, // 1 minute
  });
}

export function usePreviewDocument() {
  return useMutation({
    mutationFn: (data: DocumentGenerationRequest) => documentGeneratorApi.preview(data),
  });
}

export function useGenerateDocument(projectId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: DocumentGenerationRequest) => documentGeneratorApi.generate(projectId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.generatedDocuments(projectId) });
      queryClient.invalidateQueries({ queryKey: queryKeys.files(projectId) });
    },
  });
}

export function useDownloadGeneratedDocument() {
  return {
    download: (id: number, format: 'pdf' | 'docx' = 'pdf') => {
      const url = documentGeneratorApi.downloadGenerated(id, format);
      window.open(url, '_blank');
    },
  };
}

// ============================================
// Glossary Hooks
// ============================================

export function useGlossary() {
  return useQuery({
    queryKey: queryKeys.glossary,
    queryFn: glossaryApi.getAll,
    staleTime: STALE_TIME.LONG, // 1 hour
  });
}

export function useGlossarySearch(query: string) {
  return useQuery({
    queryKey: queryKeys.glossarySearch(query),
    queryFn: () => glossaryApi.search(query),
    enabled: query.length >= SEARCH.MIN_QUERY_LENGTH,
  });
}

// ============================================
// AI Verification Hooks
// ============================================

export function useVerifyDocument(projectId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ file, verificationType }: { file: File; verificationType: string }) =>
      verificationApi.verifyFile(projectId, file, verificationType),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.verificationHistory(projectId) });
    },
  });
}

export function useVerificationHistory(projectId: number) {
  return useQuery({
    queryKey: queryKeys.verificationHistory(projectId),
    queryFn: () => verificationApi.getHistory(projectId),
    enabled: projectId > 0,
    staleTime: STALE_TIME.SHORT, // 1 minute
  });
}

// ============================================
// Personalized Guides Hooks
// ============================================

export function useGuides() {
  return useQuery({
    queryKey: queryKeys.guides,
    queryFn: guidesApi.list,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useGuide(type: string) {
  return useQuery({
    queryKey: queryKeys.guide(type),
    queryFn: () => guidesApi.get(type),
    enabled: !!type,
    staleTime: STALE_TIME.LONG, // 1 hour - guides are static content
  });
}

export function usePersonalizedGuide(type: string) {
  return useQuery({
    queryKey: queryKeys.personalizedGuide(type),
    queryFn: () => guidesApi.getPersonalized(type),
    enabled: !!type,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useGenerateAIGuide() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (guideType: string) => guidesApi.generateAI(guideType),
    onSuccess: (_, guideType) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.personalizedGuide(guideType) });
    },
  });
}

// ============================================
// Knowledge Base Chat Hooks
// ============================================

export function useChatCategories() {
  return useQuery({
    queryKey: queryKeys.chatCategories,
    queryFn: chatApi.getCategories,
    staleTime: STALE_TIME.LONG, // 1 hour
  });
}

export function useSendChatMessage() {
  return useMutation({
    mutationFn: (data: ChatRequest) => chatApi.send(data),
  });
}

export function useSearchChatTopics(query: string) {
  return useQuery({
    queryKey: queryKeys.chatSearch(query),
    queryFn: () => chatApi.searchTopics(query),
    enabled: query.length >= SEARCH.MIN_QUERY_LENGTH,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

// ============================================
// MemberPress/Membership Hooks
// ============================================

export function useMembership() {
  return useQuery({
    queryKey: queryKeys.membership,
    queryFn: membershipApi.getInfo,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useSubscriptions() {
  return useQuery({
    queryKey: queryKeys.subscriptions,
    queryFn: membershipApi.getSubscriptions,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function usePayments() {
  return useQuery({
    queryKey: queryKeys.payments,
    queryFn: membershipApi.getPayments,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useCancelSubscription() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (subscriptionId: number) => membershipApi.cancelSubscription(subscriptionId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.subscriptions });
      queryClient.invalidateQueries({ queryKey: queryKeys.membership });
    },
  });
}

export function useSuspendSubscription() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (subscriptionId: number) => membershipApi.suspendSubscription(subscriptionId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.subscriptions });
      queryClient.invalidateQueries({ queryKey: queryKeys.membership });
    },
  });
}

export function useResumeSubscription() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (subscriptionId: number) => membershipApi.resumeSubscription(subscriptionId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.subscriptions });
      queryClient.invalidateQueries({ queryKey: queryKeys.membership });
    },
  });
}

export function useUpgradeOptions() {
  return useQuery({
    queryKey: queryKeys.upgradeOptions,
    queryFn: membershipApi.getUpgradeOptions,
    staleTime: STALE_TIME.LONG, // 1 hour
  });
}

// ============================================
// Support Ticket Hooks
// ============================================

export function useSupportTickets() {
  return useQuery({
    queryKey: queryKeys.supportTickets,
    queryFn: supportApi.getTickets,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useSupportTicket(ticketId: number | null) {
  return useQuery({
    queryKey: queryKeys.supportTicket(ticketId || 0),
    queryFn: () => {
      // Guard is redundant due to `enabled: !!ticketId` but satisfies type checker
      if (!ticketId) throw new Error('ticketId is required');
      return supportApi.getTicket(ticketId);
    },
    enabled: !!ticketId,
    staleTime: STALE_TIME.DYNAMIC, // 10 seconds
  });
}

export function useSupportUnreadCount() {
  return useQuery({
    queryKey: queryKeys.supportUnreadCount,
    queryFn: supportApi.getUnreadCount,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
    refetchInterval: REFETCH_INTERVAL.SUPPORT_UNREAD, // 1 minute
  });
}

export function useCreateSupportTicket() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: supportApi.createTicket,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.supportTickets });
      queryClient.invalidateQueries({ queryKey: queryKeys.supportUnreadCount });
    },
  });
}

export function useReplyToSupportTicket() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ ticketId, content }: { ticketId: number; content: string }) =>
      supportApi.replyToTicket(ticketId, { content }),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.supportTickets });
      queryClient.invalidateQueries({ queryKey: queryKeys.supportTicket(variables.ticketId) });
    },
  });
}

export function useDeleteSupportTicket() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: supportApi.deleteTicket,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.supportTickets });
      queryClient.invalidateQueries({ queryKey: queryKeys.supportUnreadCount });
    },
  });
}

// ============================================
// Research Hooks
// ============================================

export function useSavedReports() {
  return useQuery({
    queryKey: queryKeys.savedReports,
    queryFn: researchApi.getSavedReports,
    staleTime: STALE_TIME.SHORT, // 1 minute
  });
}

// ============================================
// Family Members Hooks (Paid Add-on Ready)
// ============================================

export function useFamilyMembers() {
  return useQuery({
    queryKey: queryKeys.familyMembers,
    queryFn: familyApi.getAll,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useFamilyFeatureStatus() {
  return useQuery({
    queryKey: queryKeys.familyFeatureStatus,
    queryFn: familyApi.getFeatureStatus,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useCreateFamilyMember() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: familyApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.familyMembers });
    },
  });
}

export function useUpdateFamilyMember() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ memberId, data }: { memberId: number; data: Partial<FamilyMember> }) =>
      familyApi.update(memberId, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.familyMembers });
    },
  });
}

export function useDeleteFamilyMember() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: familyApi.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.familyMembers });
    },
  });
}

// ============================================
// Schengen Tracker Hooks
// ============================================

export function useSchengenTrips() {
  return useQuery({
    queryKey: queryKeys.schengenTrips,
    queryFn: schengenApi.getTrips,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useSchengenSummary() {
  return useQuery({
    queryKey: queryKeys.schengenSummary,
    queryFn: schengenApi.getSummary,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useSchengenSettings() {
  return useQuery({
    queryKey: queryKeys.schengenSettings,
    queryFn: schengenApi.getSettings,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useCreateSchengenTrip() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.createTrip,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenTrips });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenSummary });
    },
  });
}

export function useUpdateSchengenTrip() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<SchengenTrip> }) =>
      schengenApi.updateTrip(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenTrips });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenSummary });
    },
  });
}

export function useDeleteSchengenTrip() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.deleteTrip,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenTrips });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenSummary });
    },
  });
}

export function useUpdateSchengenSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: Partial<SchengenAlertSettings>) => schengenApi.updateSettings(data),
    onSuccess: (updatedSettings) => {
      queryClient.setQueryData(queryKeys.schengenSettings, updatedSettings);
      // Summary depends on settings thresholds, so invalidate it too
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenSummary });
    },
  });
}

export function useSchengenFeatureStatus() {
  return useQuery({
    queryKey: queryKeys.schengenFeatureStatus,
    queryFn: schengenApi.getFeatureStatus,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useSimulateSchengenTrip() {
  return useMutation({
    mutationFn: schengenApi.simulateTrip,
  });
}

export function useGenerateSchengenReport() {
  return useMutation({
    mutationFn: schengenApi.generateReport,
  });
}

export function useTestSchengenAlert() {
  return useMutation({
    mutationFn: schengenApi.testAlert,
  });
}

// ============================================
// Schengen Location Hooks (Phase 1)
// ============================================

export function useSchengenLocationHistory(options?: { limit?: number; offset?: number }) {
  return useQuery({
    queryKey: queryKeys.schengenLocationHistory,
    queryFn: () => schengenApi.getLocationHistory(options),
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useSchengenLocationToday() {
  return useQuery({
    queryKey: queryKeys.schengenLocationToday,
    queryFn: schengenApi.getTodayStatus,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
  });
}

export function useSchengenLocationSettings() {
  return useQuery({
    queryKey: queryKeys.schengenLocationSettings,
    queryFn: schengenApi.getLocationSettings,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes
  });
}

export function useStoreSchengenLocation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.storeLocation,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationHistory });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationToday });
    },
  });
}

export function useDeleteSchengenLocation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.deleteLocation,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationHistory });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationToday });
    },
  });
}

export function useClearSchengenLocationHistory() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.clearLocationHistory,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationHistory });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenLocationToday });
    },
  });
}

export function useUpdateSchengenLocationSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.updateLocationSettings,
    onSuccess: (updatedSettings) => {
      queryClient.setQueryData(queryKeys.schengenLocationSettings, updatedSettings);
    },
  });
}

export function useGeocodeLocation() {
  return useMutation({
    mutationFn: ({ lat, lng }: { lat: number; lng: number }) =>
      schengenApi.geocode(lat, lng),
  });
}

export function useIPDetection() {
  return useQuery({
    queryKey: ['schengenIPDetection'] as const,
    queryFn: schengenApi.detectFromIP,
    staleTime: STALE_TIME.MEDIUM, // 5 minutes - IP doesn't change often
    retry: 1, // Only retry once for IP detection
  });
}

// ============================================
// Calendar Sync Hooks (Phase 2)
// ============================================

export function useCalendarProviders() {
  return useQuery({
    queryKey: ['calendarProviders'] as const,
    queryFn: schengenApi.getCalendarProviders,
    staleTime: STALE_TIME.LONG, // 1 hour - providers don't change
    throwOnError: false, // Handle errors in component, not error boundary
  });
}

export function useCalendarConnections() {
  return useQuery({
    queryKey: ['calendarConnections'] as const,
    queryFn: schengenApi.getCalendarConnections,
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
    throwOnError: false, // Handle errors in component, not error boundary
  });
}

export function useConnectCalendar() {
  return useMutation({
    mutationFn: schengenApi.connectCalendar,
    onSuccess: (data) => {
      // Redirect to OAuth URL
      if (data.authUrl) {
        window.location.href = data.authUrl;
      }
    },
  });
}

export function useDisconnectCalendar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.disconnectCalendar,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendarConnections'] });
      queryClient.invalidateQueries({ queryKey: ['calendarEvents'] });
    },
  });
}

export function useSyncCalendar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.syncCalendar,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendarConnections'] });
      queryClient.invalidateQueries({ queryKey: ['calendarEvents'] });
    },
  });
}

export function useCalendarEvents(status?: 'pending' | 'imported' | 'skipped' | 'all') {
  return useQuery({
    queryKey: ['calendarEvents', status] as const,
    queryFn: () => schengenApi.getCalendarEvents(status),
    staleTime: STALE_TIME.DEFAULT, // 30 seconds
    throwOnError: false, // Handle errors in component, not error boundary
  });
}

export function useImportCalendarEvents() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.importCalendarEvents,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendarEvents'] });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenTrips });
      queryClient.invalidateQueries({ queryKey: queryKeys.schengenSummary });
    },
  });
}

export function useSkipCalendarEvents() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.skipCalendarEvents,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendarEvents'] });
    },
  });
}

export function useImportICalFile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.importICalFile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['calendarEvents'] });
    },
  });
}

// ============================================
// Jurisdiction Hooks (Phase 3)
// ============================================

export function useJurisdictions(type?: JurisdictionType) {
  return useQuery({
    queryKey: ['jurisdictions', type] as const,
    queryFn: () => schengenApi.getJurisdictions(type),
    staleTime: STALE_TIME.LONG, // Rules don't change often
    throwOnError: false,
  });
}

export function useJurisdiction(code: string) {
  return useQuery({
    queryKey: ['jurisdiction', code] as const,
    queryFn: () => schengenApi.getJurisdiction(code),
    staleTime: STALE_TIME.LONG,
    throwOnError: false,
    enabled: !!code,
  });
}

export function useTrackedJurisdictions() {
  return useQuery({
    queryKey: ['trackedJurisdictions'] as const,
    queryFn: schengenApi.getTrackedJurisdictions,
    staleTime: STALE_TIME.DEFAULT,
    throwOnError: false,
  });
}

export function useAddTrackedJurisdiction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.addTrackedJurisdiction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['trackedJurisdictions'] });
      queryClient.invalidateQueries({ queryKey: ['multiJurisdictionSummary'] });
    },
  });
}

export function useRemoveTrackedJurisdiction() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: schengenApi.removeTrackedJurisdiction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['trackedJurisdictions'] });
      queryClient.invalidateQueries({ queryKey: ['multiJurisdictionSummary'] });
    },
  });
}

export function useJurisdictionSummary(code: string, date?: string) {
  return useQuery({
    queryKey: ['jurisdictionSummary', code, date] as const,
    queryFn: () => schengenApi.getJurisdictionSummary(code, date),
    staleTime: STALE_TIME.DEFAULT,
    throwOnError: false,
    enabled: !!code,
  });
}

export function useMultiJurisdictionSummary() {
  return useQuery({
    queryKey: ['multiJurisdictionSummary'] as const,
    queryFn: schengenApi.getMultiJurisdictionSummary,
    staleTime: STALE_TIME.DEFAULT,
    throwOnError: false,
  });
}
