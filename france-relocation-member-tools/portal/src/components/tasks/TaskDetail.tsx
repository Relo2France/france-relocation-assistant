import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  Calendar,
  Clock,
  Flag,
  Tag,
  User,
  Trash2,
  CheckCircle,
  Circle,
  AlertTriangle,
  FileText,
  MessageSquare,
} from 'lucide-react';
import { Drawer } from '@/components/shared/Modal';
import { useUpdateTask, useUpdateTaskStatus, useDeleteTask } from '@/hooks/useApi';
import type { Task, TaskStatus, TaskPriority } from '@/types';
import TaskChecklist from './TaskChecklist';

interface TaskDetailProps {
  task: Task | null;
  isOpen: boolean;
  onClose: () => void;
}

const statusOptions: { value: TaskStatus; label: string; icon: typeof Circle; color: string }[] = [
  { value: 'todo', label: 'To Do', icon: Circle, color: 'text-gray-500' },
  { value: 'in_progress', label: 'In Progress', icon: Clock, color: 'text-blue-500' },
  { value: 'waiting', label: 'Waiting', icon: Clock, color: 'text-yellow-500' },
  { value: 'done', label: 'Done', icon: CheckCircle, color: 'text-green-500' },
];

const priorityOptions: { value: TaskPriority; label: string; color: string }[] = [
  { value: 'low', label: 'Low', color: 'bg-gray-100 text-gray-600' },
  { value: 'medium', label: 'Medium', color: 'bg-blue-100 text-blue-700' },
  { value: 'high', label: 'High', color: 'bg-yellow-100 text-yellow-700' },
  { value: 'urgent', label: 'Urgent', color: 'bg-red-100 text-red-700' },
];

export default function TaskDetail({ task, isOpen, onClose }: TaskDetailProps) {
  const [isEditing, setIsEditing] = useState(false);
  const [editedTitle, setEditedTitle] = useState('');
  const [editedDescription, setEditedDescription] = useState('');
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const titleInputRef = useRef<HTMLInputElement>(null);

  const updateTask = useUpdateTask();

  // Focus title input when entering edit mode
  useEffect(() => {
    if (isEditing && titleInputRef.current) {
      titleInputRef.current.focus();
    }
  }, [isEditing]);
  const updateStatus = useUpdateTaskStatus();
  const deleteTask = useDeleteTask();

  if (!task) return null;

  const handleStatusChange = (status: TaskStatus) => {
    updateStatus.mutate({ id: task.id, status });
  };

  const handlePriorityChange = (priority: TaskPriority) => {
    updateTask.mutate({ id: task.id, data: { priority } });
  };

  const handleDueDateChange = (date: string) => {
    updateTask.mutate({ id: task.id, data: { due_date: date || null } });
  };

  const handleSaveEdit = () => {
    if (editedTitle.trim()) {
      updateTask.mutate({
        id: task.id,
        data: {
          title: editedTitle.trim(),
          description: editedDescription.trim(),
        },
      });
    }
    setIsEditing(false);
  };

  const handleStartEdit = () => {
    setEditedTitle(task.title);
    setEditedDescription(task.description || '');
    setIsEditing(true);
  };

  const handleDelete = () => {
    deleteTask.mutate({ id: task.id, projectId: task.project_id });
    onClose();
  };

  return (
    <Drawer
      isOpen={isOpen}
      onClose={onClose}
      title="Task Details"
      width="lg"
      footer={
        <div className="flex items-center justify-between w-full">
          <button
            onClick={() => setShowDeleteConfirm(true)}
            className="btn btn-ghost text-red-600 hover:bg-red-50"
          >
            <Trash2 className="w-4 h-4" />
            Delete
          </button>
          <div className="flex gap-2">
            <button onClick={onClose} className="btn btn-secondary">
              Close
            </button>
          </div>
        </div>
      }
    >
      {/* Task overdue warning */}
      {task.is_overdue && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-700">
          <AlertTriangle className="w-5 h-5" />
          <span className="text-sm font-medium">This task is overdue</span>
        </div>
      )}

      {/* Title section */}
      <div className="mb-6">
        {isEditing ? (
          <div className="space-y-3">
            <input
              ref={titleInputRef}
              type="text"
              value={editedTitle}
              onChange={(e) => setEditedTitle(e.target.value)}
              className="input text-lg font-semibold"
              placeholder="Task title"
            />
            <textarea
              value={editedDescription}
              onChange={(e) => setEditedDescription(e.target.value)}
              className="textarea h-24"
              placeholder="Add a description..."
            />
            <div className="flex gap-2">
              <button onClick={handleSaveEdit} className="btn btn-primary btn-sm">
                Save
              </button>
              <button onClick={() => setIsEditing(false)} className="btn btn-secondary btn-sm">
                Cancel
              </button>
            </div>
          </div>
        ) : (
          <div
            onClick={handleStartEdit}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleStartEdit();
              }
            }}
            role="button"
            tabIndex={0}
            className="cursor-pointer group"
          >
            <h3 className="text-xl font-semibold text-gray-900 group-hover:text-primary-600">
              {task.title}
            </h3>
            {task.description ? (
              <p className="mt-2 text-gray-600">{task.description}</p>
            ) : (
              <p className="mt-2 text-gray-400 italic">Click to add description...</p>
            )}
          </div>
        )}
      </div>

      {/* Task Checklist */}
      <div className="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <TaskChecklist taskId={task.id} editable={true} />
      </div>

      {/* Properties grid */}
      <div className="space-y-4">
        {/* Status */}
        <PropertyRow icon={Circle} label="Status">
          <div className="flex gap-2">
            {statusOptions.map((option) => {
              const Icon = option.icon;
              return (
                <button
                  key={option.value}
                  onClick={() => handleStatusChange(option.value)}
                  className={clsx(
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5',
                    task.status === option.value
                      ? 'bg-gray-900 text-white'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  )}
                >
                  <Icon className={clsx('w-4 h-4', task.status === option.value ? 'text-white' : option.color)} />
                  {option.label}
                </button>
              );
            })}
          </div>
        </PropertyRow>

        {/* Priority */}
        <PropertyRow icon={Flag} label="Priority">
          <div className="flex gap-2">
            {priorityOptions.map((option) => (
              <button
                key={option.value}
                onClick={() => handlePriorityChange(option.value)}
                className={clsx(
                  'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                  task.priority === option.value
                    ? option.color + ' ring-2 ring-offset-1 ring-gray-400'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                )}
              >
                {option.label}
              </button>
            ))}
          </div>
        </PropertyRow>

        {/* Due date */}
        <PropertyRow icon={Calendar} label="Due Date">
          <input
            type="date"
            value={task.due_date || ''}
            onChange={(e) => handleDueDateChange(e.target.value)}
            className="input w-auto"
          />
          {task.due_date && task.days_until_due !== null && (
            <span
              className={clsx(
                'ml-2 text-sm',
                task.is_overdue ? 'text-red-600' : 'text-gray-500'
              )}
            >
              {formatDaysUntil(task.days_until_due)}
            </span>
          )}
        </PropertyRow>

        {/* Stage */}
        {task.stage && (
          <PropertyRow icon={Tag} label="Stage">
            <span className="badge badge-primary capitalize">
              {task.stage.replace('_', ' ')}
            </span>
          </PropertyRow>
        )}

        {/* Type */}
        <PropertyRow icon={User} label="Type">
          <span className={clsx('badge', task.task_type === 'team' ? 'badge-blue' : 'badge-gray')}>
            {task.task_type_label}
          </span>
        </PropertyRow>

        {/* Assignee */}
        {task.assignee_name && (
          <PropertyRow icon={User} label="Assignee">
            <span className="text-gray-900">{task.assignee_name}</span>
          </PropertyRow>
        )}
      </div>

      {/* Divider */}
      <hr className="my-6" />

      {/* Related items */}
      <div className="space-y-4">
        <h4 className="text-sm font-medium text-gray-700">Related</h4>

        <button className="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors text-left">
          <FileText className="w-5 h-5 text-gray-400" />
          <div>
            <p className="text-sm font-medium text-gray-900">Attachments</p>
            <p className="text-xs text-gray-500">Add files to this task</p>
          </div>
        </button>

        <button className="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors text-left">
          <MessageSquare className="w-5 h-5 text-gray-400" />
          <div>
            <p className="text-sm font-medium text-gray-900">Comments</p>
            <p className="text-xs text-gray-500">Add notes or comments</p>
          </div>
        </button>
      </div>

      {/* Timestamps */}
      <div className="mt-6 pt-4 border-t border-gray-200">
        <p className="text-xs text-gray-400">
          Created {new Date(task.created_at).toLocaleDateString()}
          {task.completed_at && (
            <> • Completed {new Date(task.completed_at).toLocaleDateString()}</>
          )}
        </p>
      </div>

      {/* Delete confirmation */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="bg-white rounded-xl p-6 max-w-sm mx-4">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Delete Task?</h3>
            <p className="text-gray-600 mb-4">
              Are you sure you want to delete "{task.title}"? This action cannot be undone.
            </p>
            <div className="flex gap-2 justify-end">
              <button
                onClick={() => setShowDeleteConfirm(false)}
                className="btn btn-secondary"
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                className="btn bg-red-600 text-white hover:bg-red-700"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </Drawer>
  );
}

interface PropertyRowProps {
  icon: typeof Circle;
  label: string;
  children: React.ReactNode;
}

function PropertyRow({ icon: Icon, label, children }: PropertyRowProps) {
  return (
    <div className="flex items-start gap-4">
      <div className="flex items-center gap-2 w-24 flex-shrink-0 text-gray-500">
        <Icon className="w-4 h-4" />
        <span className="text-sm">{label}</span>
      </div>
      <div className="flex-1 flex items-center flex-wrap gap-2">
        {children}
      </div>
    </div>
  );
}

function formatDaysUntil(days: number): string {
  if (days === 0) return '(Today)';
  if (days === 1) return '(Tomorrow)';
  if (days === -1) return '(Yesterday)';
  if (days < 0) return `(${Math.abs(days)} days overdue)`;
  return `(in ${days} days)`;
}
