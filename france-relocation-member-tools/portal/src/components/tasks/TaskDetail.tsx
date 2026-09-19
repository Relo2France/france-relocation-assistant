import { useEffect, useRef, useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  Calendar,
  CheckCircle,
  Circle,
  Clock,
  FileSignature,
  FileText,
  Flag,
  Tag,
  Trash2,
  User,
} from 'lucide-react';
import { letterForStep } from '@/components/documents/letterForStep';
import { Drawer } from '@/components/shared/Modal';
import { useDashboard, useDeleteTask, useLetters, useUpdateTask, useUpdateTaskStatus } from '@/hooks/useApi';
import { stageById, stageForTask } from '@/journey/journey';
import { usePortalStore } from '@/store';
import type { Task, TaskHowto, TaskPriority, TaskStatus } from '@/types';
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
  const howto = (task?.metadata as { howto?: TaskHowto } | null | undefined)?.howto;
  const howtoValid = howto && Array.isArray(howto.steps) && howto.steps.length > 0 ? howto : null;
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
  const { data: dashboard } = useDashboard();
  const setActiveView = usePortalStore((s) => s.setActiveView);

  if (!task) return null;

  // The journey stage by name, the same placement the stage pages use.
  const project = dashboard?.project;
  const stageName = task.stage
    ? (project ? stageById(stageForTask(task, project)) : stageById(task.stage))?.name ?? task.stage.replace(/[_-]/g, ' ')
    : null;

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
      title="Step details"
      width="lg"
      footer={
        <div className="flex items-center justify-between w-full">
          <button
            onClick={() => setShowDeleteConfirm(true)}
            className="btn btn-ghost text-red-600 hover:bg-red-50"
          >
            <Trash2 className="w-4 h-4" aria-hidden="true" />
            Delete
          </button>
          <div className="flex gap-2">
            <button onClick={onClose} className="btn btn-secondary">
              Close
            </button>
            {task ? (
              task.status === 'done' ? (
                <button onClick={() => handleStatusChange('todo')} className="btn btn-secondary">Mark not done</button>
              ) : (
                <button onClick={() => handleStatusChange('done')} className="btn btn-primary">Mark as done</button>
              )
            ) : null}
          </div>
        </div>
      }
    >
      {/* Task overdue warning */}
      {task.is_overdue && (
        <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-red-700">
          <AlertTriangle className="w-5 h-5" aria-hidden="true" />
          <span className="text-sm font-medium">This step is overdue</span>
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
              placeholder="Step title"
              aria-label="Step title"
            />
            <textarea
              value={editedDescription}
              onChange={(e) => setEditedDescription(e.target.value)}
              className="textarea h-24"
              placeholder="Add a description..."
              aria-label="Step description"
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

      {/* How to do this: the walkthrough attached to generated steps */}
      {howtoValid ? <HowTo howto={howtoValid} /> : null}

      {/* A step the portal can draft the letter for */}
      <DraftLetterPrompt title={task.title} onGo={onClose} />

      {/* Task Checklist */}
      <div className="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <TaskChecklist taskId={task.id} editable={true} />
      </div>

      {/* Properties grid */}
      <div className="space-y-4">
        {/* Status */}
        <PropertyRow icon={Circle} label="Status">
          <div className="flex flex-wrap gap-2">
            {statusOptions.map((option) => {
              const Icon = option.icon;
              return (
                <button
                  key={option.value}
                  onClick={() => handleStatusChange(option.value)}
                  className={clsx(
                    'px-3 py-1.5 rounded-full text-sm font-medium transition-colors inline-flex items-center gap-1.5 whitespace-nowrap',
                    task.status === option.value
                      ? 'bg-primary-500 text-white'
                      : 'bg-card-2 text-gray-600 hover:bg-primary-100'
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
          <div className="flex flex-wrap gap-2">
            {priorityOptions.map((option) => (
              <button
                key={option.value}
                onClick={() => handlePriorityChange(option.value)}
                className={clsx(
                  'px-3 py-1.5 rounded-full text-sm font-medium transition-colors whitespace-nowrap',
                  task.priority === option.value
                    ? option.color + ' ring-1 ring-primary-500'
                    : 'bg-card-2 text-gray-600 hover:bg-primary-100'
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
        {stageName && (
          <PropertyRow icon={Tag} label="Stage">
            <span className="badge badge-primary">{stageName}</span>
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

        <button
          type="button"
          onClick={() => {
            setActiveView('documents');
            onClose();
          }}
          className="w-full flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors text-left"
        >
          <FileText className="w-5 h-5 text-gray-400" aria-hidden="true" />
          <div>
            <p className="text-sm font-medium text-gray-900">Documents</p>
            <p className="text-xs text-gray-500">Files for this step live in Documents</p>
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
            <h3 className="text-lg font-semibold text-gray-900 mb-2">Delete this step?</h3>
            <p className="text-gray-600 mb-4">
              Are you sure you want to delete &quot;{task.title}&quot;? This action cannot be undone.
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

/** The site a link goes to, for the link text; a malformed URL shows as written. */
function hostOf(url: string): string {
  try {
    return new URL(url).hostname.replace(/^www\./, '');
  } catch {
    return url;
  }
}

function formatDaysUntil(days: number): string {
  if (days === 0) return '(Today)';
  if (days === 1) return '(Tomorrow)';
  if (days === -1) return '(Yesterday)';
  if (days < 0) return `(${Math.abs(days)} days overdue)`;
  return `(in ${days} days)`;
}

/**
 * The walkthrough for a generated step: the order, where to go, what to
 * bring, how long, what it costs. Links open the official site in a new tab.
 */
function HowTo({ howto }: { howto: TaskHowto }) {
  return (
    <section className="mb-6 rounded-lg border border-rule bg-card p-5 flex flex-col gap-4" aria-labelledby="howto-title">
      <div className="flex flex-col gap-1">
        <h4 id="howto-title" className="font-display text-[1.05rem] font-semibold m-0 whitespace-nowrap">How to do this</h4>
        {(howto.time || howto.cost) ? (
          <p className="text-[0.82rem] text-gray-500 m-0 leading-snug">
            {howto.time ? <><span className="font-semibold text-gray-600">Time:</span> {howto.time}</> : null}
            {howto.time && howto.cost ? ' · ' : ''}
            {howto.cost ? <><span className="font-semibold text-gray-600">Cost:</span> {howto.cost}</> : null}
          </p>
        ) : null}
      </div>
      <ol className="list-none m-0 p-0 flex flex-col">
        {howto.steps.map((step, i) => {
          const last = i === howto.steps.length - 1;
          return (
            <li key={step.title} className="grid grid-cols-[1.5rem_minmax(0,1fr)] gap-x-3">
              <div className="flex flex-col items-center" aria-hidden="true">
                <span className="w-6 h-6 rounded-full border-2 border-primary-500 text-primary-500 text-[0.68rem] font-bold flex items-center justify-center flex-shrink-0">{i + 1}</span>
                {!last ? <span className="w-px flex-1 my-0.5 bg-rule" /> : null}
              </div>
              <div className={last ? 'pb-0' : 'pb-3'}>
                <span className="block text-[0.92rem] font-semibold leading-snug">{step.title}</span>
                <span className="block text-[0.85rem] text-gray-600 leading-snug mt-0.5">{step.detail}</span>
                {step.url ? (
                  <a href={step.url} target="_blank" rel="noopener noreferrer" className="inline-block mt-1 text-[0.8rem] font-semibold text-primary-500 hover:text-primary-700">
                    {hostOf(step.url)} ↗
                  </a>
                ) : null}
              </div>
            </li>
          );
        })}
      </ol>
      {howto.bring.length ? (
        <div className="flex flex-col gap-1">
          <span className="eyebrow">Bring</span>
          <ul className="m-0 p-0 list-none flex flex-wrap gap-1.5">
            {howto.bring.map((b) => (
              <li key={b} className="px-2.5 py-1 rounded-full bg-card-2 text-[0.8rem] text-gray-700">{b}</li>
            ))}
          </ul>
        </div>
      ) : null}
      {howto.links.length ? (
        <div className="flex flex-col gap-1">
          <span className="eyebrow">Official links</span>
          <ul className="m-0 p-0 list-none flex flex-col gap-0.5">
            {howto.links.map((l) => (
              <li key={l.url}>
                <a href={l.url} target="_blank" rel="noopener noreferrer" className="text-[0.85rem] font-semibold text-primary-500 hover:text-primary-700">{l.label} ↗</a>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </section>
  );
}

/**
 * "Draft it for me" on a step whose letter the member's route calls for.
 * Opens Documents at that letter.
 */
function DraftLetterPrompt({ title, onGo }: { title: string; onGo: () => void }) {
  const type = letterForStep(title);
  const { data } = useLetters();
  const { setActiveView, setOpenLetter } = usePortalStore();
  const letter = type ? data?.letters.find((l) => l.type === type && l.person === 'you') : undefined;
  if (!letter) return null;
  const drafted = !!letter.file;
  return (
    <div className="mb-6 p-4 rounded-lg border border-primary-100 bg-primary-50 flex flex-wrap items-center justify-between gap-3">
      <p className="text-sm text-gray-800 flex items-start gap-2 min-w-0">
        <FileSignature className="w-4 h-4 mt-0.5 shrink-0 text-primary-600" aria-hidden="true" />
        {drafted ? `Your ${letter.title.toLowerCase()} is drafted in Documents.` : `The portal can draft your ${letter.title.toLowerCase()} from your profile.`}
      </p>
      <button
        type="button"
        className="btn btn-primary btn-sm"
        onClick={() => {
          setOpenLetter(type);
          setActiveView('documents');
          onGo();
        }}
      >
        {drafted ? 'Open it' : 'Draft it for me'}
      </button>
    </div>
  );
}
