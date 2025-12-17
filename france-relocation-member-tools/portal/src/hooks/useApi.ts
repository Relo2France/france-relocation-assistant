import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  dashboardApi,
  projectsApi,
  tasksApi,
  activityApi,
  userApi,
  filesApi,
  notesApi,
} from '@/api/client';
import type { Task, TaskStatus, Project, FileCategory, NoteVisibility } from '@/types';

// Query keys
export const queryKeys = {
  dashboard: ['dashboard'] as const,
  projects: ['projects'] as const,
  project: (id: number) => ['project', id] as const,
  tasks: (projectId: number, filters?: object) => ['tasks', projectId, filters] as const,
  task: (id: number) => ['task', id] as const,
  activity: (projectId: number) => ['activity', projectId] as const,
  user: ['user'] as const,
  files: (projectId: number, filters?: object) => ['files', projectId, filters] as const,
  file: (id: number) => ['file', id] as const,
  notes: (projectId: number, filters?: object) => ['notes', projectId, filters] as const,
  note: (id: number) => ['note', id] as const,
};

// Dashboard hook
export function useDashboard() {
  return useQuery({
    queryKey: queryKeys.dashboard,
    queryFn: dashboardApi.get,
    staleTime: 30000, // 30 seconds
  });
}

// User hook
export function useCurrentUser() {
  return useQuery({
    queryKey: queryKeys.user,
    queryFn: userApi.me,
    staleTime: 60000 * 5, // 5 minutes
  });
}

// Projects hooks
export function useProjects() {
  return useQuery({
    queryKey: queryKeys.projects,
    queryFn: projectsApi.list,
  });
}

export function useProject(id: number) {
  return useQuery({
    queryKey: queryKeys.project(id),
    queryFn: () => projectsApi.get(id),
    enabled: id > 0,
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
export function useTasks(projectId: number, filters?: { stage?: string; status?: string; task_type?: string }) {
  return useQuery({
    queryKey: queryKeys.tasks(projectId, filters),
    queryFn: () => tasksApi.list(projectId, filters),
    enabled: projectId > 0,
  });
}

export function useTask(id: number) {
  return useQuery({
    queryKey: queryKeys.task(id),
    queryFn: () => tasksApi.get(id),
    enabled: id > 0,
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
  });
}

// Files hooks
export function useFiles(projectId: number, filters?: { category?: FileCategory; file_type?: string }) {
  return useQuery({
    queryKey: queryKeys.files(projectId, filters),
    queryFn: () => filesApi.list(projectId, filters),
    enabled: projectId > 0,
  });
}

export function useFile(id: number) {
  return useQuery({
    queryKey: queryKeys.file(id),
    queryFn: () => filesApi.get(id),
    enabled: id > 0,
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
export function useNotes(projectId: number, filters?: { task_id?: number; pinned?: boolean }) {
  return useQuery({
    queryKey: queryKeys.notes(projectId, filters),
    queryFn: () => notesApi.list(projectId, filters),
    enabled: projectId > 0,
  });
}

export function useNote(id: number) {
  return useQuery({
    queryKey: queryKeys.note(id),
    queryFn: () => notesApi.get(id),
    enabled: id > 0,
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
