import { clsx } from 'clsx';
import {
  CheckCircle,
  Circle,
  Clock,
  AlertTriangle,
  Calendar,
  MoreVertical,
  Plus,
} from 'lucide-react';
import VirtualList from '@/components/shared/VirtualList';
import { useVirtualization } from '@/hooks/useVirtualization';
import type { Task, TaskStatus } from '@/types';

interface TaskListProps {
  tasks: Task[];
  groupBy?: 'stage' | 'status' | 'none';
  onTaskClick?: (task: Task) => void;
  onStatusChange?: (taskId: number, status: TaskStatus) => void;
  onAddTask?: (stage?: string) => void;
}

const statusConfig: Record<TaskStatus, { icon: typeof Circle; color: string; bgColor: string }> = {
  todo: { icon: Circle, color: 'text-gray-400', bgColor: 'bg-gray-100' },
  in_progress: { icon: Clock, color: 'text-blue-500', bgColor: 'bg-blue-100' },
  waiting: { icon: Clock, color: 'text-yellow-500', bgColor: 'bg-yellow-100' },
  done: { icon: CheckCircle, color: 'text-green-500', bgColor: 'bg-green-100' },
};

export default function TaskList({
  tasks,
  groupBy = 'none',
  onTaskClick,
  onStatusChange,
  onAddTask,
}: TaskListProps) {
  const shouldVirtualize = useVirtualization(tasks.length, 30);

  if (groupBy === 'none') {
    if (tasks.length === 0) {
      return (
        <div className="text-center py-12 text-gray-500">
          <CheckCircle className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p>No tasks found</p>
        </div>
      );
    }

    // Use virtual scrolling for large lists
    if (shouldVirtualize) {
      return (
        <VirtualList
          items={tasks}
          estimateSize={72}
          className="max-h-[calc(100vh-200px)]"
          getItemKey={(task) => task.id}
          renderItem={(task) => (
            <div className="pb-2">
              <TaskListItem
                task={task}
                onClick={() => onTaskClick?.(task)}
                onStatusChange={onStatusChange}
              />
            </div>
          )}
        />
      );
    }

    // Standard rendering for smaller lists
    return (
      <div className="space-y-2">
        {tasks.map((task) => (
          <TaskListItem
            key={task.id}
            task={task}
            onClick={() => onTaskClick?.(task)}
            onStatusChange={onStatusChange}
          />
        ))}
      </div>
    );
  }

  // Group tasks
  const groups = groupTasks(tasks, groupBy);

  return (
    <div className="space-y-6">
      {Object.entries(groups).map(([groupKey, groupTasks]) => (
        <TaskGroup
          key={groupKey}
          title={formatGroupTitle(groupKey, groupBy)}
          tasks={groupTasks}
          groupKey={groupKey}
          onTaskClick={onTaskClick}
          onStatusChange={onStatusChange}
          onAddTask={onAddTask}
        />
      ))}
    </div>
  );
}

interface TaskGroupProps {
  title: string;
  tasks: Task[];
  groupKey: string;
  onTaskClick?: (task: Task) => void;
  onStatusChange?: (taskId: number, status: TaskStatus) => void;
  onAddTask?: (stage?: string) => void;
}

function TaskGroup({
  title,
  tasks,
  groupKey,
  onTaskClick,
  onStatusChange,
  onAddTask,
}: TaskGroupProps) {
  const completedCount = tasks.filter((t) => t.status === 'done').length;

  return (
    <div className="card">
      <div className="card-header flex items-center justify-between">
        <div className="flex items-center gap-3">
          <h3 className="font-semibold text-gray-900">{title}</h3>
          <span className="text-sm text-gray-500">
            {completedCount}/{tasks.length} completed
          </span>
        </div>
        <div className="flex items-center gap-2">
          <span className="badge badge-gray">{tasks.length}</span>
          {onAddTask && (
            <button
              onClick={() => onAddTask(groupKey)}
              className="btn btn-ghost btn-sm"
            >
              <Plus className="w-4 h-4" />
              Add Task
            </button>
          )}
        </div>
      </div>
      <div className="divide-y divide-gray-100">
        {tasks.map((task) => (
          <TaskListItem
            key={task.id}
            task={task}
            onClick={() => onTaskClick?.(task)}
            onStatusChange={onStatusChange}
            compact
          />
        ))}
        {tasks.length === 0 && (
          <div className="p-6 text-center text-gray-500">
            <p>No tasks in this section</p>
          </div>
        )}
      </div>
    </div>
  );
}

interface TaskListItemProps {
  task: Task;
  onClick?: () => void;
  onStatusChange?: (taskId: number, status: TaskStatus) => void;
  compact?: boolean;
}

function TaskListItem({ task, onClick, onStatusChange, compact }: TaskListItemProps) {
  const config = statusConfig[task.status];
  const StatusIcon = config.icon;

  const handleStatusClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!onStatusChange) return;

    // Cycle through statuses
    const statusOrder: TaskStatus[] = ['todo', 'in_progress', 'waiting', 'done'];
    const currentIndex = statusOrder.indexOf(task.status);
    const nextStatus = statusOrder[(currentIndex + 1) % statusOrder.length];
    onStatusChange(task.id, nextStatus);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (onClick && (e.key === 'Enter' || e.key === ' ')) {
      e.preventDefault();
      onClick();
    }
  };

  return (
    <div
      onClick={onClick}
      onKeyDown={handleKeyDown}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      className={clsx(
        'flex items-center gap-4 transition-colors',
        compact ? 'px-6 py-3' : 'p-4 rounded-lg border border-gray-200',
        task.is_overdue && !compact && 'border-red-200 bg-red-50',
        onClick && 'cursor-pointer hover:bg-gray-50'
      )}
    >
      {/* Status checkbox */}
      <button
        onClick={handleStatusClick}
        className={clsx(
          'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-colors',
          config.bgColor,
          'hover:opacity-80'
        )}
        title={`Status: ${task.status_label}`}
      >
        <StatusIcon className={clsx('w-4 h-4', config.color)} />
      </button>

      {/* Task content */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <h4
            className={clsx(
              'font-medium truncate',
              task.status === 'done' ? 'text-gray-400 line-through' : 'text-gray-900'
            )}
          >
            {task.title}
          </h4>
          {task.is_overdue && (
            <AlertTriangle className="w-4 h-4 text-red-500 flex-shrink-0" />
          )}
        </div>
        {task.description && !compact && (
          <p className="text-sm text-gray-500 truncate mt-0.5">{task.description}</p>
        )}
      </div>

      {/* Meta info */}
      <div className="flex items-center gap-3 flex-shrink-0">
        {/* Priority */}
        <PriorityBadge priority={task.priority} />

        {/* Type */}
        {task.task_type === 'team' && (
          <span className="badge badge-blue text-xs">Team</span>
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
            {formatDate(task.due_date)}
          </span>
        )}

        {/* Menu */}
        <button
          onClick={(e) => e.stopPropagation()}
          className="p-1 text-gray-400 hover:text-gray-600 rounded"
        >
          <MoreVertical className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}

function PriorityBadge({ priority }: { priority: string }) {
  const classes: Record<string, string> = {
    low: 'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-700',
    high: 'bg-yellow-100 text-yellow-700',
    urgent: 'bg-red-100 text-red-700',
  };

  return (
    <span className={clsx('badge text-xs', classes[priority] || classes.medium)}>
      {priority}
    </span>
  );
}

function groupTasks(tasks: Task[], groupBy: 'stage' | 'status'): Record<string, Task[]> {
  return tasks.reduce((acc, task) => {
    const key = groupBy === 'stage' ? (task.stage || 'unassigned') : task.status;
    if (!acc[key]) {
      acc[key] = [];
    }
    acc[key].push(task);
    return acc;
  }, {} as Record<string, Task[]>);
}

function formatGroupTitle(key: string, groupBy: 'stage' | 'status'): string {
  if (groupBy === 'status') {
    const labels: Record<string, string> = {
      todo: 'To Do',
      in_progress: 'In Progress',
      waiting: 'Waiting',
      done: 'Done',
    };
    return labels[key] || key;
  }

  // For stages, capitalize and replace underscores
  if (key === 'unassigned') return 'Unassigned';
  return key
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (date.toDateString() === today.toDateString()) return 'Today';
  if (date.toDateString() === tomorrow.toDateString()) return 'Tomorrow';

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
