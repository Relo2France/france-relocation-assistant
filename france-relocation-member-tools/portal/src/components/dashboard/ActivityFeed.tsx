import {
  CheckCircle,
  Plus,
  Pencil,
  Trash,
  ArrowRightLeft,
  Upload,
  FileText,
  MessageSquare,
  User,
  ArrowRight,
} from 'lucide-react';
import type { Activity } from '@/types';

interface ActivityFeedProps {
  activities: Activity[];
}

const iconComponents: Record<string, React.ComponentType<{ className?: string }>> = {
  'check-circle': CheckCircle,
  'plus-circle': Plus,
  'pencil': Pencil,
  'trash': Trash,
  'arrows-right-left': ArrowRightLeft,
  'arrow-up-tray': Upload,
  'document-text': FileText,
  'chat-bubble-bottom-center-text': MessageSquare,
  'user-plus': User,
  'user': User,
  'envelope': MessageSquare,
  'arrow-right': ArrowRight,
  'folder-plus': Plus,
};

export default function ActivityFeed({ activities }: ActivityFeedProps) {
  return (
    <div className="card h-full">
      <div className="card-header">
        <h2 className="text-lg font-semibold text-gray-900">Recent Activity</h2>
      </div>
      <div className="card-body overflow-y-auto max-h-[500px] scrollbar-thin">
        {activities.length > 0 ? (
          <div className="space-y-4">
            {activities.map((activity) => (
              <ActivityItem key={activity.id} activity={activity} />
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-500">
            <MessageSquare className="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>No recent activity</p>
          </div>
        )}
      </div>
    </div>
  );
}

function ActivityItem({ activity }: { activity: Activity }) {
  const Icon = iconComponents[activity.action_icon] || CheckCircle;

  return (
    <div className="flex gap-3">
      {/* Avatar */}
      <div className="flex-shrink-0">
        {activity.user_avatar ? (
          <img
            src={activity.user_avatar}
            alt={activity.user_name}
            className="w-8 h-8 rounded-full"
          />
        ) : (
          <div className="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
            <User className="w-4 h-4 text-gray-500" />
          </div>
        )}
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <Icon className="w-4 h-4 text-gray-400" />
          <p className="text-sm text-gray-900">
            <span className="font-medium">{activity.user_name}</span>{' '}
            <span className="text-gray-600">{activity.action_label.toLowerCase()}</span>
          </p>
        </div>
        {activity.description && activity.description !== activity.action_label && (
          <p className="text-sm text-gray-600 mt-0.5 truncate">
            {activity.description}
          </p>
        )}
        <p className="text-xs text-gray-400 mt-1">{activity.relative_time}</p>
      </div>
    </div>
  );
}
