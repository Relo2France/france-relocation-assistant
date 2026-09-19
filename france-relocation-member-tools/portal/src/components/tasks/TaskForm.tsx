import { useEffect, useRef, useState } from 'react';
import Modal from '@/components/shared/Modal';
import { useCreateTask } from '@/hooks/useApi';
import type { TaskPriority, TaskStatus } from '@/types';

interface TaskFormProps {
  isOpen: boolean;
  onClose: () => void;
  projectId: number;
  defaultStatus?: TaskStatus;
  defaultStage?: string;
  stages?: { slug: string; title: string }[];
}

export default function TaskForm({
  isOpen,
  onClose,
  projectId,
  defaultStatus = 'todo',
  defaultStage,
  stages = [],
}: TaskFormProps) {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState<TaskStatus>(defaultStatus);
  const [priority, setPriority] = useState<TaskPriority>('medium');
  const [stage, setStage] = useState(defaultStage || '');
  const [dueDate, setDueDate] = useState('');
  const [taskType, setTaskType] = useState<'client' | 'team'>('client');
  const titleInputRef = useRef<HTMLInputElement>(null);

  const createTask = useCreateTask(projectId);

  // Focus title input when modal opens, and take that opening's defaults
  useEffect(() => {
    if (isOpen) {
      setStatus(defaultStatus);
      setStage(defaultStage || '');
      if (titleInputRef.current) titleInputRef.current.focus();
    }
  }, [isOpen, defaultStatus, defaultStage]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim()) return;

    createTask.mutate(
      {
        title: title.trim(),
        description: description.trim() || undefined,
        status,
        priority,
        stage: stage || undefined,
        due_date: dueDate || undefined,
        task_type: taskType,
      },
      {
        onSuccess: () => {
          // Reset form
          setTitle('');
          setDescription('');
          setStatus(defaultStatus);
          setPriority('medium');
          setStage(defaultStage || '');
          setDueDate('');
          setTaskType('client');
          onClose();
        },
      }
    );
  };

  const handleClose = () => {
    // Reset form on close
    setTitle('');
    setDescription('');
    setStatus(defaultStatus);
    setPriority('medium');
    setStage(defaultStage || '');
    setDueDate('');
    setTaskType('client');
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Add a step"
      size="lg"
      footer={
        <>
          <button onClick={handleClose} className="btn btn-secondary">
            Cancel
          </button>
          <button
            onClick={handleSubmit}
            disabled={!title.trim() || createTask.isPending}
            className="btn btn-primary"
          >
            {createTask.isPending ? 'Creating...' : 'Add step'}
          </button>
        </>
      }
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Title */}
        <div>
          <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
            Step title <span className="text-red-500">*</span>
          </label>
          <input
            ref={titleInputRef}
            id="title"
            type="text"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            className="input"
            placeholder="What needs to be done?"
          />
        </div>

        {/* Description */}
        <div>
          <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            id="description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="textarea h-24"
            placeholder="Add more details..."
          />
        </div>

        {/* Two-column layout for options */}
        <div className="grid grid-cols-2 gap-4">
          {/* Status */}
          <div>
            <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
              Status
            </label>
            <select
              id="status"
              value={status}
              onChange={(e) => setStatus(e.target.value as TaskStatus)}
              className="select"
            >
              <option value="todo">To do</option>
              <option value="in_progress">In progress</option>
              <option value="waiting">Waiting</option>
              <option value="done">Done</option>
            </select>
          </div>

          {/* Priority */}
          <div>
            <label htmlFor="priority" className="block text-sm font-medium text-gray-700 mb-1">
              Priority
            </label>
            <select
              id="priority"
              value={priority}
              onChange={(e) => setPriority(e.target.value as TaskPriority)}
              className="select"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          {/* Stage */}
          <div>
            <label htmlFor="stage" className="block text-sm font-medium text-gray-700 mb-1">
              Stage
            </label>
            <select
              id="stage"
              value={stage}
              onChange={(e) => setStage(e.target.value)}
              className="select"
            >
              <option value="">Select stage...</option>
              {stages.map((s) => (
                <option key={s.slug} value={s.slug}>
                  {s.title}
                </option>
              ))}
            </select>
          </div>

          {/* Due Date */}
          <div>
            <label htmlFor="dueDate" className="block text-sm font-medium text-gray-700 mb-1">
              Due date
            </label>
            <input
              id="dueDate"
              type="date"
              value={dueDate}
              onChange={(e) => setDueDate(e.target.value)}
              className="input"
            />
          </div>
        </div>

        {/* Task Type */}
        <div role="radiogroup" aria-labelledby="task-type-label">
          <span id="task-type-label" className="block text-sm font-medium text-gray-700 mb-2">
            Task type
          </span>
          <div className="flex gap-4">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                name="taskType"
                value="client"
                checked={taskType === 'client'}
                onChange={(e) => setTaskType(e.target.value as 'client' | 'team')}
                className="w-4 h-4 text-primary-600"
              />
              <span className="text-sm text-gray-700">Client task</span>
              <span className="text-xs text-gray-500">(visible to you)</span>
            </label>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                name="taskType"
                value="team"
                checked={taskType === 'team'}
                onChange={(e) => setTaskType(e.target.value as 'client' | 'team')}
                className="w-4 h-4 text-primary-600"
              />
              <span className="text-sm text-gray-700">Team task</span>
              <span className="text-xs text-gray-500">(for support team)</span>
            </label>
          </div>
        </div>
      </form>
    </Modal>
  );
}

// Quick add form (inline)
