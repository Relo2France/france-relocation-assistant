// Project types
export interface Project {
  id: number;
  user_id: number;
  title: string;
  description: string;
  visa_type: VisaType;
  visa_type_label: string;
  current_stage: string;
  target_move_date: string | null;
  days_until_move: number | null;
  status: ProjectStatus;
  status_label: string;
  settings: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export type VisaType = 'visitor' | 'talent' | 'student' | 'family' | 'work' | 'other';
export type ProjectStatus = 'active' | 'completed' | 'paused' | 'archived';

// Task types
export interface Task {
  id: number;
  project_id: number;
  user_id: number;
  title: string;
  description: string;
  stage: string;
  status: TaskStatus;
  status_label: string;
  priority: TaskPriority;
  priority_label: string;
  task_type: TaskType;
  task_type_label: string;
  due_date: string | null;
  days_until_due: number | null;
  is_overdue: boolean;
  assignee_id: number | null;
  assignee_name: string | null;
  portal_visible: boolean;
  sort_order: number;
  parent_task_id: number | null;
  metadata: Record<string, unknown>;
  completed_at: string | null;
  created_at: string;
  updated_at: string;
  subtasks?: Task[];
}

export type TaskStatus = 'todo' | 'in_progress' | 'waiting' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';
export type TaskType = 'client' | 'team' | 'system';

// Stage types
export interface Stage {
  id: number;
  visa_type: string;
  slug: string;
  title: string;
  description: string;
  sort_order: number;
  color: string;
  icon: string;
  created_at: string;
}

export interface StageProgress extends Stage {
  total: number;
  completed: number;
  percentage: number;
  is_current: boolean;
  is_completed: boolean;
  status: 'completed' | 'current' | 'upcoming';
}

// Activity types
export interface Activity {
  id: number;
  project_id: number;
  user_id: number;
  user_name: string;
  user_avatar: string;
  action: string;
  action_label: string;
  action_icon: string;
  entity_type: string;
  entity_id: number;
  description: string;
  metadata: Record<string, unknown>;
  created_at: string;
  relative_time: string;
}

export interface ActivityGroup {
  date: string;
  label: string;
  activities: Activity[];
}

// Dashboard types
export interface DashboardData {
  project: Project;
  stages: StageProgress[];
  task_stats: TaskStats;
  upcoming_tasks: Task[];
  overdue_tasks: Task[];
  recent_activity: Activity[];
}

export interface TaskStats {
  total: number;
  completed: number;
  in_progress: number;
  todo: number;
  overdue: number;
  percentage: number;
}

// User types
export interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  first_name: string;
  last_name: string;
  avatar_url: string;
  roles: string[];
  is_admin: boolean;
  active_memberships?: number[];
  is_member?: boolean;
}

export interface UserSettings {
  email_notifications: boolean;
  task_reminders: boolean;
  weekly_digest: boolean;
  language: string;
  timezone: string;
  date_format: string;
}

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  display_name?: string;
}

// File types
export interface PortalFile {
  id: number;
  project_id: number;
  user_id: number;
  filename: string;
  original_name: string;
  file_type: FileType;
  file_type_label: string;
  mime_type: string;
  file_size: number;
  file_size_formatted: string;
  category: FileCategory;
  category_label: string;
  description: string | null;
  is_generated: boolean;
  entity_type: string | null;
  entity_id: number | null;
  thumbnail_url: string | null;
  preview_url: string | null;
  download_url: string;
  uploaded_by: number;
  uploaded_by_name: string;
  created_at: string;
  updated_at: string;
}

export type FileType = 'document' | 'image' | 'pdf' | 'spreadsheet' | 'archive' | 'other';
export type FileCategory = 'identity' | 'financial' | 'housing' | 'employment' | 'visa' | 'medical' | 'education' | 'other';

// Note types
export interface Note {
  id: number;
  project_id: number;
  user_id: number;
  user_name: string;
  user_avatar: string;
  task_id: number | null;
  content: string;
  is_pinned: boolean;
  visibility: NoteVisibility;
  created_at: string;
  updated_at: string;
  relative_time: string;
}

export type NoteVisibility = 'private' | 'team' | 'shared';

// API Response types
export interface ApiError {
  code: string;
  message: string;
  status: number;
}

// Navigation types
export interface NavItem {
  id: string;
  label: string;
  icon: string;
  path: string;
  badge?: number;
}

export interface NavSection {
  id: string;
  label: string;
  items: NavItem[];
}

// WordPress global types
declare global {
  interface Window {
    fraPortalData: {
      apiUrl: string;
      nonce: string;
      userId: number;
      siteUrl: string;
      pluginUrl: string;
      isAdmin: boolean;
    };
  }
}
