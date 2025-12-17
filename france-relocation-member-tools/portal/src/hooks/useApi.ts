import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  dashboardApi,
  projectsApi,
  tasksApi,
  activityApi,
  userApi,
} from '@/api/client';
import type { Task, TaskStatus, Project } from '@/types';

// Query keys
export const queryKeys = {
  dashboard: ['dashboard'] as const,
  projects: ['projects'] as const,
  project: (id: number) => ['project', id] as const,
  tasks: (projectId: number, filters?: object) => ['tasks', projectId, filters] as const,
  task: (id: number) => ['task', id] as const,
  activity: (projectId: number) => ['activity', projectId] as const,
  user: ['user'] as const,
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
