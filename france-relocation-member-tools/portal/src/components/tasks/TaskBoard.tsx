import { useState } from 'react';
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

  const tasksByStatus = columns.reduce((acc, col) => {
    acc[col.id] = tasks.filter((t) => t.status === col.id);
    return acc;
  }, {} as Record<TaskStatus, Task[]>);

  const handleDragStart = (task: Task) => {
    setDraggedTask(task);
  };

  const handleDragEnd = () => {
    setDraggedTask(null);
    setDragOverColumn(null);
  };

  const handleDragOver = (e: React.DragEvent, columnId: TaskStatus) => {
    e.preventDefault();
    setDragOverColumn(columnId);
  };

  const handleDrop = (columnId: TaskStatus) => {
    if (draggedTask && draggedTask.status !== columnId) {
      onStatusChange?.(draggedTask.id, columnId);
    }
    setDraggedTask(null);
    setDragOverColumn(null);
  };

  return (
    <div className="flex gap-4 overflow-x-auto pb-4">
      {columns.map((column) => (
        <BoardColumn
          key={column.id}
          column={column}
          tasks={tasksByStatus[column.id]}
          isDragOver={dragOverColumn === column.id}
          onTaskClick={onTaskClick}
          onDragStart={handleDragStart}
          onDragEnd={handleDragEnd}
          onDragOver={(e) => handleDragOver(e, column.id)}
          onDrop={() => handleDrop(column.id)}
          onAddTask={() => onAddTask?.(column.id)}
        />
      ))}
    </div>
  );
}

interface BoardColumnProps {
  column: Column;
  tasks: Task[];
  isDragOver: boolean;
  onTaskClick?: (task: Task) => void;
  onDragStart: (task: Task) => void;
  onDragEnd: () => void;
  onDragOver: (e: React.DragEvent) => void;
  onDrop: () => void;
  onAddTask: () => void;
}

function BoardColumn({
  column,
  tasks,
  isDragOver,
  onTaskClick,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDrop,
  onAddTask,
}: BoardColumnProps) {
  const Icon = column.icon;

  return (
    <div
      className={clsx(
        'flex-shrink-0 w-80 rounded-xl transition-colors',
        column.bgColor,
        isDragOver && 'ring-2 ring-primary-500'
      )}
      onDragOver={onDragOver}
      onDrop={onDrop}
    >
      {/* Column header */}
      <div className={clsx('p-4 border-b', column.borderColor)}>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Icon className={clsx('w-5 h-5', column.color)} />
            <h3 className="font-semibold text-gray-900">{column.title}</h3>
            <span className="text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full">
              {tasks.length}
            </span>
          </div>
          <button
            onClick={onAddTask}
            className="p-1 text-gray-400 hover:text-gray-600 hover:bg-white rounded transition-colors"
          >
            <Plus className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Task cards */}
      <div className="p-3 space-y-3 min-h-[200px]">
        {tasks.map((task) => (
          <TaskCard
            key={task.id}
            task={task}
            onClick={() => onTaskClick?.(task)}
            onDragStart={() => onDragStart(task)}
            onDragEnd={onDragEnd}
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
  onClick?: () => void;
  onDragStart: () => void;
  onDragEnd: () => void;
}

function TaskCard({ task, onClick, onDragStart, onDragEnd }: TaskCardProps) {
  return (
    <div
      draggable
      onDragStart={onDragStart}
      onDragEnd={onDragEnd}
      onClick={onClick}
      className={clsx(
        'bg-white rounded-lg border p-3 cursor-pointer transition-shadow hover:shadow-md',
        task.is_overdue ? 'border-red-200' : 'border-gray-200'
      )}
    >
      {/* Drag handle */}
      <div className="flex items-start gap-2">
        <GripVertical className="w-4 h-4 text-gray-300 flex-shrink-0 mt-0.5 cursor-grab" />
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
              <AlertTriangle className="w-3.5 h-3.5 text-red-500 flex-shrink-0" />
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
                <Calendar className="w-3 h-3" />
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
