import { clsx } from 'clsx';
import { Calendar, AlertTriangle } from 'lucide-react';
import type { Task } from '@/types';

interface TaskCardProps {
  task: Task;
  onClick?: () => void;
}

export default function TaskCard({ task, onClick }: TaskCardProps) {
  const statusClasses: Record<string, string> = {
    todo: 'status-todo',
    in_progress: 'status-in-progress',
    waiting: 'status-waiting',
    done: 'status-done',
  };

  const priorityClasses: Record<string, string> = {
    low: 'priority-low',
    medium: 'priority-medium',
    high: 'priority-high',
    urgent: 'priority-urgent',
  };

  return (
    <div
      onClick={onClick}
      className={clsx(
        'flex items-center justify-between p-3 rounded-lg border transition-colors',
        task.is_overdue ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white hover:bg-gray-50',
        onClick && 'cursor-pointer'
      )}
    >
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <h3 className="font-medium text-gray-900 truncate">{task.title}</h3>
          {task.is_overdue && (
            <AlertTriangle className="w-4 h-4 text-red-500 flex-shrink-0" />
          )}
        </div>
        <div className="flex items-center gap-3 mt-1">
          <span className={clsx('badge text-xs', statusClasses[task.status])}>
            {task.status_label}
          </span>
          <span className={clsx('badge text-xs', priorityClasses[task.priority])}>
            {task.priority_label}
          </span>
          {task.due_date && (
            <span
              className={clsx(
                'flex items-center gap-1 text-xs',
                task.is_overdue ? 'text-red-600' : 'text-gray-500'
              )}
            >
              <Calendar className="w-3 h-3" />
              {formatDueDate(task.due_date, task.days_until_due)}
            </span>
          )}
        </div>
      </div>
      {task.task_type === 'team' && (
        <span className="badge badge-blue text-xs ml-2">Team</span>
      )}
    </div>
  );
}

function formatDueDate(dateStr: string, daysUntilDue: number | null): string {
  if (daysUntilDue === null) {
    return new Date(dateStr).toLocaleDateString();
  }

  if (daysUntilDue === 0) return 'Today';
  if (daysUntilDue === 1) return 'Tomorrow';
  if (daysUntilDue === -1) return 'Yesterday';
  if (daysUntilDue < 0) return `${Math.abs(daysUntilDue)} days ago`;
  if (daysUntilDue <= 7) return `In ${daysUntilDue} days`;

  return new Date(dateStr).toLocaleDateString();
}
