import { useState } from 'react';
import { clsx } from 'clsx';
import {
  CheckCircle,
  Clock,
  AlertTriangle,
  Calendar,
  ArrowRight,
  FileText,
} from 'lucide-react';
import { useDashboard, useUpdateProject } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import ProgressTracker from './ProgressTracker';
import TaskCard from './TaskCard';
import ActivityFeed from './ActivityFeed';
import Modal from '@/components/shared/Modal';

export default function Dashboard() {
  const { data, isLoading, error } = useDashboard();
  const { setActiveView, setSettingsTab } = usePortalStore();
  const [showMoveDateModal, setShowMoveDateModal] = useState(false);
  const [newMoveDate, setNewMoveDate] = useState('');
  const updateProject = useUpdateProject();

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  if (error) {
    return (
      <div className="p-6">
        <div className="card p-8 text-center">
          <AlertTriangle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <h2 className="text-lg font-semibold text-gray-900 mb-2">Error Loading Dashboard</h2>
          <p className="text-gray-600">{(error as Error).message}</p>
        </div>
      </div>
    );
  }

  if (!data) return null;

  const { project, stages, task_stats, profile_visa_label, upcoming_tasks, overdue_tasks, recent_activity } = data;

  return (
    <div className="p-6 space-y-6">
      {/* Welcome header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{project.title}</h1>
          <p className="text-gray-600 mt-1">
            {profile_visa_label ? (
              <span>{profile_visa_label}</span>
            ) : (
              <button
                onClick={() => {
                  setSettingsTab('visa-profile');
                  setActiveView('settings');
                }}
                className="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 hover:underline"
              >
                <FileText className="w-4 h-4" />
                <span>Set your visa type in your profile</span>
              </button>
            )}
            {project.target_move_date && (
              <>
                <span className="mx-2">•</span>
                <Calendar className="w-4 h-4 inline-block mr-1" />
                {project.days_until_move !== null && project.days_until_move > 0 ? (
                  <span>{project.days_until_move} days until move</span>
                ) : (
                  <span>Move date: {new Date(project.target_move_date).toLocaleDateString()}</span>
                )}
              </>
            )}
          </p>
        </div>
        <button
          className="btn btn-primary"
          onClick={() => {
            setNewMoveDate(project.target_move_date || '');
            setShowMoveDateModal(true);
          }}
        >
          <Calendar className="w-4 h-4" />
          Update Move Date
        </button>
      </div>

      {/* Update Move Date Modal */}
      <Modal
        isOpen={showMoveDateModal}
        onClose={() => setShowMoveDateModal(false)}
        title="Update Move Date"
        size="sm"
        footer={
          <>
            <button
              className="btn btn-secondary"
              onClick={() => setShowMoveDateModal(false)}
            >
              Cancel
            </button>
            <button
              className="btn btn-primary"
              onClick={() => {
                if (newMoveDate && project.id) {
                  updateProject.mutate(
                    { id: project.id, data: { target_move_date: newMoveDate } },
                    {
                      onSuccess: () => {
                        setShowMoveDateModal(false);
                      },
                    }
                  );
                }
              }}
              disabled={updateProject.isPending}
            >
              {updateProject.isPending ? 'Saving...' : 'Save'}
            </button>
          </>
        }
      >
        <div className="space-y-4">
          <p className="text-gray-600">
            When are you planning to move to France? This helps us prioritize your tasks and deadlines.
          </p>
          <div>
            <label htmlFor="move-date" className="block text-sm font-medium text-gray-700 mb-1">
              Target Move Date
            </label>
            <input
              id="move-date"
              type="date"
              value={newMoveDate}
              onChange={(e) => setNewMoveDate(e.target.value)}
              className="input"
              min={new Date().toISOString().split('T')[0]}
            />
          </div>
        </div>
      </Modal>

      {/* Progress tracker */}
      <ProgressTracker stages={stages} />

      {/* Stats grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          label="Tasks Completed"
          value={`${task_stats.completed}/${task_stats.total}`}
          subtext={`${task_stats.percentage}% complete`}
          icon={CheckCircle}
          color="green"
          onClick={() => setActiveView('tasks')}
        />
        <StatCard
          label="In Progress"
          value={task_stats.in_progress}
          subtext="Tasks being worked on"
          icon={Clock}
          color="blue"
          onClick={() => setActiveView('tasks')}
        />
        <StatCard
          label="To Do"
          value={task_stats.todo}
          subtext="Tasks remaining"
          icon={Clock}
          color="gray"
          onClick={() => setActiveView('tasks')}
        />
        <StatCard
          label="Overdue"
          value={task_stats.overdue}
          subtext={task_stats.overdue > 0 ? 'Need attention!' : 'All on track'}
          icon={AlertTriangle}
          color={task_stats.overdue > 0 ? 'red' : 'green'}
          onClick={() => setActiveView('tasks')}
        />
      </div>

      {/* Two-column layout */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Tasks column */}
        <div className="lg:col-span-2 space-y-6">
          {/* Overdue tasks */}
          {overdue_tasks.length > 0 && (
            <div className="card">
              <div className="card-header flex items-center justify-between">
                <h2 className="text-lg font-semibold text-red-600 flex items-center gap-2">
                  <AlertTriangle className="w-5 h-5" />
                  Overdue Tasks
                </h2>
                <span className="badge badge-red">{overdue_tasks.length}</span>
              </div>
              <div className="card-body space-y-3">
                {overdue_tasks.slice(0, 5).map((task) => (
                  <TaskCard key={task.id} task={task} />
                ))}
              </div>
            </div>
          )}

          {/* Upcoming tasks */}
          <div className="card">
            <div className="card-header flex items-center justify-between">
              <h2 className="text-lg font-semibold text-gray-900">Upcoming Tasks</h2>
              <button
                onClick={() => setActiveView('tasks')}
                className="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1"
              >
                View all
                <ArrowRight className="w-4 h-4" />
              </button>
            </div>
            <div className="card-body">
              {upcoming_tasks.length > 0 ? (
                <div className="space-y-3">
                  {upcoming_tasks.map((task) => (
                    <TaskCard key={task.id} task={task} />
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500">
                  <CheckCircle className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p>No upcoming tasks due soon!</p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Activity column */}
        <div className="lg:col-span-1">
          <ActivityFeed activities={recent_activity} />
        </div>
      </div>
    </div>
  );
}

// Stat card component
interface StatCardProps {
  label: string;
  value: string | number;
  subtext: string;
  icon: React.ComponentType<{ className?: string }>;
  color: 'green' | 'blue' | 'gray' | 'red' | 'yellow';
  onClick?: () => void;
}

function StatCard({ label, value, subtext, icon: Icon, color, onClick }: StatCardProps) {
  const colorClasses = {
    green: 'bg-green-50 text-green-600',
    blue: 'bg-blue-50 text-blue-600',
    gray: 'bg-gray-50 text-gray-600',
    red: 'bg-red-50 text-red-600',
    yellow: 'bg-yellow-50 text-yellow-600',
  };

  return (
    <button
      type="button"
      onClick={onClick}
      className="card p-5 w-full text-left hover:shadow-md transition-shadow cursor-pointer"
      aria-label={`${label}: ${value}. ${subtext}. Click to view tasks.`}
    >
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500">{label}</p>
          <p className="text-2xl font-bold text-gray-900 mt-1">{value}</p>
          <p className="text-sm text-gray-500 mt-1">{subtext}</p>
        </div>
        <div className={clsx('p-2 rounded-lg', colorClasses[color])}>
          <Icon className="w-5 h-5" />
        </div>
      </div>
    </button>
  );
}

// Loading skeleton
function DashboardSkeleton() {
  return (
    <div className="p-6 space-y-6">
      <div className="h-20 bg-gray-200 rounded-lg animate-pulse" />
      <div className="h-40 bg-gray-200 rounded-lg animate-pulse" />
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="h-28 bg-gray-200 rounded-lg animate-pulse" />
        ))}
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 h-96 bg-gray-200 rounded-lg animate-pulse" />
        <div className="h-96 bg-gray-200 rounded-lg animate-pulse" />
      </div>
    </div>
  );
}
