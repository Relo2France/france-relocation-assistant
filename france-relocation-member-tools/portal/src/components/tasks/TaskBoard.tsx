/**
 * TaskBoard Component
 *
 * Kanban-style board for task management with drag-and-drop and keyboard navigation.
 *
 * Accessibility Features:
 * - Full keyboard navigation (Tab, Arrow keys, Space/Enter)
 * - Screen reader announcements for drag operations
 * - ARIA attributes for drag-and-drop states
 */

import { useState, useRef, useCallback } from 'react';
import { clsx } from 'clsx';
import {
  Circle,
  Clock,
  CheckCircle,
  AlertTriangle,
  Plus,
  Calendar,
  GripVertical,
} from 'lucide-react';
import type { Task, TaskStatus } from '@/types';

interface TaskBoardProps {
  tasks: Task[];
  onTaskClick?: (task: Task) => void;
  onStatusChange?: (taskId: number, status: TaskStatus) => void;
  onAddTask?: (status: TaskStatus) => void;
}

interface Column {
  id: TaskStatus;
  title: string;
  icon: typeof Circle;
  color: string;
  bgColor: string;
  borderColor: string;
}

const columns: Column[] = [
  {
    id: 'todo',
    title: 'To Do',
    icon: Circle,
    color: 'text-gray-500',
    bgColor: 'bg-gray-50',
    borderColor: 'border-gray-200',
  },
  {
    id: 'in_progress',
    title: 'In Progress',
    icon: Clock,
    color: 'text-blue-500',
    bgColor: 'bg-blue-50',
    borderColor: 'border-blue-200',
  },
  {
    id: 'waiting',
    title: 'Waiting',
    icon: Clock,
    color: 'text-yellow-500',
    bgColor: 'bg-yellow-50',
    borderColor: 'border-yellow-200',
  },
  {
    id: 'done',
    title: 'Done',
    icon: CheckCircle,
    color: 'text-green-500',
    bgColor: 'bg-green-50',
    borderColor: 'border-green-200',
  },
];

export default function TaskBoard({
  tasks,
  onTaskClick,
  onStatusChange,
  onAddTask,
}: TaskBoardProps) {
  const [draggedTask, setDraggedTask] = useState<Task | null>(null);
  const [dragOverColumn, setDragOverColumn] = useState<TaskStatus | null>(null);
  const [keyboardDragTask, setKeyboardDragTask] = useState<Task | null>(null);
  const [announcement, setAnnouncement] = useState<string>('');
  const announcementRef = useRef<HTMLDivElement>(null);

  const tasksByStatus = columns.reduce((acc, col) => {
    acc[col.id] = tasks.filter((t) => t.status === col.id);
    return acc;
  }, {} as Record<TaskStatus, Task[]>);

  // Announce to screen readers
  const announce = useCallback((message: string) => {
    setAnnouncement(message);
    // Clear after a brief delay to allow re-announcing the same message
    setTimeout(() => setAnnouncement(''), 100);
  }, []);

  // Mouse drag handlers
  const handleDragStart = (task: Task) => {
    setDraggedTask(task);
    announce(`Picked up task: ${task.title}`);
  };

  const handleDragEnd = () => {
    if (draggedTask) {
      announce(`Dropped task: ${draggedTask.title}`);
    }
    setDraggedTask(null);
    setDragOverColumn(null);
  };

  const handleDragOver = (e: React.DragEvent, columnId: TaskStatus) => {
    e.preventDefault();
    setDragOverColumn(columnId);
  };

  const handleDrop = (columnId: TaskStatus) => {
    if (draggedTask && draggedTask.status !== columnId) {
      const columnTitle = columns.find(c => c.id === columnId)?.title || columnId;
      announce(`Moved ${draggedTask.title} to ${columnTitle}`);
      onStatusChange?.(draggedTask.id, columnId);
    }
    setDraggedTask(null);
    setDragOverColumn(null);
  };

  // Keyboard drag handlers
  const handleKeyboardPickUp = (task: Task) => {
    setKeyboardDragTask(task);
    announce(`Picked up task: ${task.title}. Use left and right arrow keys to move between columns, then press Space or Enter to drop.`);
  };

  const handleKeyboardDrop = (columnId: TaskStatus) => {
    if (keyboardDragTask && keyboardDragTask.status !== columnId) {
      const columnTitle = columns.find(c => c.id === columnId)?.title || columnId;
      announce(`Moved ${keyboardDragTask.title} to ${columnTitle}`);
      onStatusChange?.(keyboardDragTask.id, columnId);
    } else if (keyboardDragTask) {
      announce(`Cancelled. ${keyboardDragTask.title} stays in current column.`);
    }
    setKeyboardDragTask(null);
  };

  const handleKeyboardCancel = () => {
    if (keyboardDragTask) {
      announce(`Cancelled moving ${keyboardDragTask.title}`);
      setKeyboardDragTask(null);
    }
  };

  const handleKeyboardMoveColumn = (direction: 'left' | 'right') => {
    if (!keyboardDragTask) return;

    const currentIndex = columns.findIndex(c => c.id === keyboardDragTask.status);
    const newIndex = direction === 'left'
      ? Math.max(0, currentIndex - 1)
      : Math.min(columns.length - 1, currentIndex + 1);

    if (newIndex !== currentIndex) {
      const newColumn = columns[newIndex];
      announce(`Over ${newColumn.title} column. Press Space or Enter to drop here.`);
      setDragOverColumn(newColumn.id);
    }
  };

  return (
    <div>
      {/* Screen reader announcements */}
      <div
        ref={announcementRef}
        className="sr-only"
        role="status"
        aria-live="assertive"
        aria-atomic="true"
      >
        {announcement}
      </div>

      {/* Keyboard instructions (visible when dragging) */}
      {keyboardDragTask && (
        <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800" role="status">
          <strong>Moving: {keyboardDragTask.title}</strong>
          <span className="ml-2">
            Use ← → to select column, Space/Enter to drop, Escape to cancel
          </span>
        </div>
      )}

      {/* Board columns */}
      <div
        className="flex gap-4 overflow-x-auto pb-4"
        role="region"
        aria-label="Task board"
      >
        {columns.map((column) => (
          <BoardColumn
            key={column.id}
            column={column}
            tasks={tasksByStatus[column.id]}
            isDragOver={dragOverColumn === column.id}
            isKeyboardDragging={!!keyboardDragTask}
            keyboardDragTask={keyboardDragTask}
            onTaskClick={onTaskClick}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
            onDragOver={(e) => handleDragOver(e, column.id)}
            onDrop={() => handleDrop(column.id)}
            onAddTask={() => onAddTask?.(column.id)}
            onKeyboardPickUp={handleKeyboardPickUp}
            onKeyboardDrop={() => handleKeyboardDrop(column.id)}
            onKeyboardCancel={handleKeyboardCancel}
            onKeyboardMoveColumn={handleKeyboardMoveColumn}
          />
        ))}
      </div>
    </div>
  );
}

interface BoardColumnProps {
  column: Column;
  tasks: Task[];
  isDragOver: boolean;
  isKeyboardDragging: boolean;
  keyboardDragTask: Task | null;
  onTaskClick?: (task: Task) => void;
  onDragStart: (task: Task) => void;
  onDragEnd: () => void;
  onDragOver: (e: React.DragEvent) => void;
  onDrop: () => void;
  onAddTask: () => void;
  onKeyboardPickUp: (task: Task) => void;
  onKeyboardDrop: () => void;
  onKeyboardCancel: () => void;
  onKeyboardMoveColumn: (direction: 'left' | 'right') => void;
}

function BoardColumn({
  column,
  tasks,
  isDragOver,
  isKeyboardDragging,
  keyboardDragTask,
  onTaskClick,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDrop,
  onAddTask,
  onKeyboardPickUp,
  onKeyboardDrop,
  onKeyboardCancel,
  onKeyboardMoveColumn,
}: BoardColumnProps) {
  const Icon = column.icon;
  const isDropTarget = isDragOver && keyboardDragTask?.status !== column.id;

  // Handle keyboard events for the column (when keyboard dragging)
  const handleColumnKeyDown = (e: React.KeyboardEvent) => {
    if (!isKeyboardDragging) return;

    switch (e.key) {
      case 'ArrowLeft':
        e.preventDefault();
        onKeyboardMoveColumn('left');
        break;
      case 'ArrowRight':
        e.preventDefault();
        onKeyboardMoveColumn('right');
        break;
      case ' ':
      case 'Enter':
        e.preventDefault();
        if (isDragOver) {
          onKeyboardDrop();
        }
        break;
      case 'Escape':
        e.preventDefault();
        onKeyboardCancel();
        break;
    }
  };

  return (
    <div
      className={clsx(
        'flex-shrink-0 w-80 rounded-xl transition-colors',
        column.bgColor,
        isDropTarget && 'ring-2 ring-primary-500'
      )}
      onDragOver={onDragOver}
      onDrop={onDrop}
      onKeyDown={handleColumnKeyDown}
      role="listbox"
      tabIndex={0}
      aria-label={`${column.title} column, ${tasks.length} tasks`}
      aria-dropeffect={isKeyboardDragging ? 'move' : undefined}
    >
      {/* Column header */}
      <div className={clsx('p-4 border-b', column.borderColor)}>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Icon className={clsx('w-5 h-5', column.color)} aria-hidden="true" />
            <h3 className="font-semibold text-gray-900">{column.title}</h3>
            <span className="text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full">
              {tasks.length}
            </span>
          </div>
          <button
            onClick={onAddTask}
            className="p-1 text-gray-400 hover:text-gray-600 hover:bg-white rounded transition-colors"
            aria-label={`Add task to ${column.title}`}
          >
            <Plus className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>
      </div>

      {/* Task cards */}
      <div className="p-3 space-y-3 min-h-[200px]">
        {tasks.map((task, index) => (
          <TaskCard
            key={task.id}
            task={task}
            index={index}
            totalTasks={tasks.length}
            columnTitle={column.title}
            isBeingDragged={keyboardDragTask?.id === task.id}
            onClick={() => onTaskClick?.(task)}
            onDragStart={() => onDragStart(task)}
            onDragEnd={onDragEnd}
            onKeyboardPickUp={() => onKeyboardPickUp(task)}
            onKeyboardDrop={onKeyboardDrop}
            onKeyboardCancel={onKeyboardCancel}
            onKeyboardMoveColumn={onKeyboardMoveColumn}
          />
        ))}
        {tasks.length === 0 && (
          <div className="text-center py-8 text-gray-400 text-sm">
            <p>No tasks</p>
            <button
              onClick={onAddTask}
              className="mt-2 text-primary-600 hover:text-primary-700"
            >
              + Add a task
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

interface TaskCardProps {
  task: Task;
  index: number;
  totalTasks: number;
  columnTitle: string;
  isBeingDragged: boolean;
  onClick?: () => void;
  onDragStart: () => void;
  onDragEnd: () => void;
  onKeyboardPickUp: () => void;
  onKeyboardDrop: () => void;
  onKeyboardCancel: () => void;
  onKeyboardMoveColumn: (direction: 'left' | 'right') => void;
}

function TaskCard({
  task,
  index,
  totalTasks,
  columnTitle: _columnTitle,
  isBeingDragged,
  onClick,
  onDragStart,
  onDragEnd,
  onKeyboardPickUp,
  onKeyboardDrop,
  onKeyboardCancel,
  onKeyboardMoveColumn,
}: TaskCardProps) {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    switch (e.key) {
      case ' ':
        // Space: Pick up or drop the task
        e.preventDefault();
        if (isBeingDragged) {
          onKeyboardDrop();
        } else {
          onKeyboardPickUp();
        }
        break;
      case 'Enter':
        // Enter: Open task or drop if dragging
        e.preventDefault();
        if (isBeingDragged) {
          onKeyboardDrop();
        } else {
          onClick?.();
        }
        break;
      case 'Escape':
        // Escape: Cancel drag
        if (isBeingDragged) {
          e.preventDefault();
          onKeyboardCancel();
        }
        break;
      case 'ArrowLeft':
        // Move to previous column when dragging
        if (isBeingDragged) {
          e.preventDefault();
          onKeyboardMoveColumn('left');
        }
        break;
      case 'ArrowRight':
        // Move to next column when dragging
        if (isBeingDragged) {
          e.preventDefault();
          onKeyboardMoveColumn('right');
        }
        break;
    }
  };

  return (
    <div
      draggable
      tabIndex={0}
      role="option"
      aria-selected={isBeingDragged}
      aria-grabbed={isBeingDragged}
      aria-label={`${task.title}${task.is_overdue ? ', overdue' : ''}, priority ${task.priority}${task.due_date ? `, due ${formatShortDate(task.due_date)}` : ''}`}
      aria-describedby="drag-instructions"
      aria-setsize={totalTasks}
      aria-posinset={index + 1}
      onDragStart={onDragStart}
      onDragEnd={onDragEnd}
      onClick={onClick}
      onKeyDown={handleKeyDown}
      className={clsx(
        'bg-white rounded-lg border p-3 cursor-pointer transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
        task.is_overdue ? 'border-red-200' : 'border-gray-200',
        isBeingDragged && 'opacity-50 ring-2 ring-primary-500',
        !isBeingDragged && 'hover:shadow-md'
      )}
    >
      {/* Hidden instructions for screen readers */}
      <span id="drag-instructions" className="sr-only">
        Press Space to pick up and move this task, or Enter to open task details
      </span>

      {/* Drag handle */}
      <div className="flex items-start gap-2">
        <GripVertical
          className="w-4 h-4 text-gray-300 flex-shrink-0 mt-0.5 cursor-grab"
          aria-hidden="true"
        />
        <div className="flex-1 min-w-0">
          {/* Title */}
          <div className="flex items-center gap-2">
            <h4
              className={clsx(
                'font-medium text-sm',
                task.status === 'done' ? 'text-gray-400 line-through' : 'text-gray-900'
              )}
            >
              {task.title}
            </h4>
            {task.is_overdue && (
              <AlertTriangle
                className="w-3.5 h-3.5 text-red-500 flex-shrink-0"
                aria-hidden="true"
              />
            )}
          </div>

          {/* Description preview */}
          {task.description && (
            <p className="text-xs text-gray-500 mt-1 line-clamp-2">
              {task.description}
            </p>
          )}

          {/* Meta row */}
          <div className="flex items-center gap-2 mt-2 flex-wrap">
            {/* Priority */}
            <PriorityDot priority={task.priority} />

            {/* Type badge */}
            {task.task_type === 'team' && (
              <span className="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">
                Team
              </span>
            )}

            {/* Due date */}
            {task.due_date && (
              <span
                className={clsx(
                  'flex items-center gap-1 text-xs',
                  task.is_overdue ? 'text-red-600' : 'text-gray-500'
                )}
              >
                <Calendar className="w-3 h-3" aria-hidden="true" />
                {formatShortDate(task.due_date)}
              </span>
            )}

            {/* Stage */}
            {task.stage && (
              <span className="text-xs text-gray-400 capitalize">
                {task.stage.replace('_', ' ')}
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function PriorityDot({ priority }: { priority: string }) {
  const colors: Record<string, string> = {
    low: 'bg-gray-400',
    medium: 'bg-blue-500',
    high: 'bg-yellow-500',
    urgent: 'bg-red-500',
  };

  return (
    <span
      className={clsx('w-2 h-2 rounded-full', colors[priority] || colors.medium)}
      title={`Priority: ${priority}`}
      aria-label={`Priority: ${priority}`}
    />
  );
}

function formatShortDate(dateStr: string): string {
  const date = new Date(dateStr);
  const today = new Date();
  const diff = Math.ceil((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

  if (diff === 0) return 'Today';
  if (diff === 1) return 'Tomorrow';
  if (diff === -1) return 'Yesterday';
  if (diff < -1) return `${Math.abs(diff)}d ago`;
  if (diff <= 7) return `${diff}d`;

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
