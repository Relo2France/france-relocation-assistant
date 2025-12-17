import { useMemo } from 'react';
import { clsx } from 'clsx';
import {
  CheckCircle,
  Circle,
  Clock,
  MessageSquare,
  Upload,
  UserPlus,
  Settings,
  Flag,
} from 'lucide-react';
import { useDashboard, useActivity } from '@/hooks/useApi';
import type { Activity } from '@/types';

const actionIcons: Record<string, React.ElementType> = {
  task_created: Circle,
  task_completed: CheckCircle,
  task_updated: Settings,
  file_uploaded: Upload,
  note_created: MessageSquare,
  stage_changed: Flag,
  project_created: UserPlus,
  default: Clock,
};

const actionColors: Record<string, string> = {
  task_created: 'bg-blue-100 text-blue-600',
  task_completed: 'bg-green-100 text-green-600',
  task_updated: 'bg-gray-100 text-gray-600',
  file_uploaded: 'bg-purple-100 text-purple-600',
  note_created: 'bg-yellow-100 text-yellow-600',
  stage_changed: 'bg-indigo-100 text-indigo-600',
  project_created: 'bg-primary-100 text-primary-600',
  default: 'bg-gray-100 text-gray-600',
};

interface ActivityGroup {
  date: string;
  label: string;
  activities: Activity[];
}

export default function TimelineView() {
  const { data: dashboard, isLoading: dashboardLoading } = useDashboard();
  const projectId = dashboard?.project?.id || 0;

  const { data: activities = [], isLoading: activitiesLoading } = useActivity(
    projectId,
    { limit: 100 }
  );

  // Group activities by date
  const groupedActivities = useMemo(() => {
    const groups: Record<string, ActivityGroup> = {};

    activities.forEach((activity) => {
      const date = new Date(activity.created_at);
      const dateKey = date.toISOString().split('T')[0];

      if (!groups[dateKey]) {
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        let label: string;
        if (dateKey === today.toISOString().split('T')[0]) {
          label = 'Today';
        } else if (dateKey === yesterday.toISOString().split('T')[0]) {
          label = 'Yesterday';
        } else {
          label = date.toLocaleDateString('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
          });
        }

        groups[dateKey] = {
          date: dateKey,
          label,
          activities: [],
        };
      }

      groups[dateKey].activities.push(activity);
    });

    return Object.values(groups).sort(
      (a, b) => new Date(b.date).getTime() - new Date(a.date).getTime()
    );
  }, [activities]);

  if (dashboardLoading || activitiesLoading) {
    return <TimelineViewSkeleton />;
  }

  if (!dashboard?.project) {
    return (
      <div className="p-6">
        <div className="card p-8 text-center">
          <h2 className="text-lg font-semibold text-gray-900 mb-2">No Project Found</h2>
          <p className="text-gray-600">Please set up your relocation project first.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Timeline</h1>
        <p className="text-gray-600 mt-1">
          Track your relocation journey progress
        </p>
      </div>

      {/* Timeline */}
      {activities.length === 0 ? (
        <div className="card p-12 text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <Clock className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">No activity yet</h3>
          <p className="text-gray-600">
            Your activity timeline will appear here as you progress through your relocation
          </p>
        </div>
      ) : (
        <div className="space-y-8">
          {groupedActivities.map((group) => (
            <div key={group.date}>
              {/* Date header */}
              <div className="flex items-center gap-4 mb-4">
                <h3 className="text-sm font-semibold text-gray-900">{group.label}</h3>
                <div className="flex-1 h-px bg-gray-200" />
                <span className="text-xs text-gray-500">
                  {group.activities.length} {group.activities.length === 1 ? 'event' : 'events'}
                </span>
              </div>

              {/* Activities */}
              <div className="relative">
                {/* Timeline line */}
                <div className="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200" />

                <div className="space-y-4">
                  {group.activities.map((activity) => (
                    <ActivityItem
                      key={activity.id}
                      activity={activity}
                    />
                  ))}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

interface ActivityItemProps {
  activity: Activity;
}

function ActivityItem({ activity }: ActivityItemProps) {
  const Icon = actionIcons[activity.action] || actionIcons.default;
  const colorClass = actionColors[activity.action] || actionColors.default;

  const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
    });
  };

  return (
    <div className="relative flex gap-4 pl-0">
      {/* Icon */}
      <div
        className={clsx(
          'relative z-10 w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
          colorClass
        )}
      >
        <Icon className="w-5 h-5" />
      </div>

      {/* Content */}
      <div className="flex-1 card p-4 -mt-1">
        <div className="flex items-start justify-between gap-4">
          <div className="flex items-center gap-3">
            {/* Avatar */}
            <img
              src={activity.user_avatar}
              alt={activity.user_name}
              className="w-8 h-8 rounded-full"
            />

            <div>
              <p className="text-sm">
                <span className="font-medium text-gray-900">{activity.user_name}</span>
                {' '}
                <span className="text-gray-600">{activity.description}</span>
              </p>
            </div>
          </div>

          <span className="text-xs text-gray-500 flex-shrink-0">
            {formatTime(activity.created_at)}
          </span>
        </div>
      </div>
    </div>
  );
}

function TimelineViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 w-48" />

      <div className="space-y-8">
        {[1, 2].map((group) => (
          <div key={group}>
            <div className="flex items-center gap-4 mb-4">
              <div className="h-5 w-24 bg-gray-200 rounded animate-pulse" />
              <div className="flex-1 h-px bg-gray-200" />
            </div>

            <div className="relative">
              <div className="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200" />

              <div className="space-y-4">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="flex gap-4">
                    <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse" />
                    <div className="flex-1 card p-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-gray-200 rounded-full animate-pulse" />
                        <div className="flex-1 space-y-2">
                          <div className="h-4 bg-gray-200 rounded animate-pulse w-3/4" />
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
