/**
 * NotificationCenter
 *
 * In-app notification center for Schengen Tracker.
 * Shows notifications in a dropdown panel with bell icon badge.
 */

import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  Bell,
  CheckCheck,
  Trash2,
  X,
  AlertTriangle,
  Calendar,
  MapPin,
  Info,
  Settings,
  Loader2,
} from 'lucide-react';
import type { NotificationItem, NotificationType } from '@/types';
import {
  useNotifications,
  useNotificationUnreadCount,
  useMarkNotificationRead,
  useMarkAllNotificationsRead,
  useDeleteNotification,
} from '@/hooks/useApi';

interface NotificationCenterProps {
  className?: string;
}

export default function NotificationCenter({ className }: NotificationCenterProps) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const { data: notifications, isLoading } = useNotifications();
  const { data: unreadData } = useNotificationUnreadCount();
  const markRead = useMarkNotificationRead();
  const markAllRead = useMarkAllNotificationsRead();
  const deleteNotification = useDeleteNotification();

  const unreadCount = unreadData?.count ?? 0;

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleNotificationClick = (notification: NotificationItem) => {
    // Mark as read if not already
    if (!notification.isRead) {
      markRead.mutate(notification.id);
    }

    // Navigate to action URL if provided
    if (notification.actionUrl) {
      window.location.href = notification.actionUrl;
    }
  };

  const handleDelete = (e: React.MouseEvent, notificationId: number) => {
    e.stopPropagation();
    deleteNotification.mutate(notificationId);
  };

  const handleMarkAllRead = () => {
    markAllRead.mutate(undefined);
  };

  const getNotificationIcon = (type: NotificationType) => {
    switch (type) {
      case 'threshold_warning':
      case 'threshold_danger':
        return <AlertTriangle className="w-4 h-4 text-amber-500" aria-hidden="true" />;
      case 'trip_reminder':
      case 'calendar_sync':
      case 'day_expiring':
        return <Calendar className="w-4 h-4 text-primary-500" aria-hidden="true" />;
      case 'location_checkin':
        return <MapPin className="w-4 h-4 text-purple-500" aria-hidden="true" />;
      default:
        return <Info className="w-4 h-4 text-gray-500" aria-hidden="true" />;
    }
  };

  const formatTimeAgo = (dateStr: string) => {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  return (
    <div ref={dropdownRef} className={clsx('relative', className)}>
      {/* Bell button with badge */}
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className={clsx(
          'relative p-2 rounded-lg transition-colors',
          isOpen ? 'bg-primary-100 text-primary-600' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
        )}
        aria-label={`Notifications${unreadCount > 0 ? ` (${unreadCount} unread)` : ''}`}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <Bell className="w-5 h-5" aria-hidden="true" />
        {unreadCount > 0 && (
          <span className="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full transform translate-x-1/4 -translate-y-1/4">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Dropdown panel */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 className="font-semibold text-gray-900">Notifications</h3>
            <div className="flex items-center gap-2">
              {unreadCount > 0 && (
                <button
                  type="button"
                  onClick={handleMarkAllRead}
                  disabled={markAllRead.isPending}
                  className="text-xs text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1"
                >
                  {markAllRead.isPending ? (
                    <Loader2 className="w-3 h-3 animate-spin" aria-hidden="true" />
                  ) : (
                    <CheckCheck className="w-3 h-3" aria-hidden="true" />
                  )}
                  Mark all read
                </button>
              )}
              <button
                type="button"
                onClick={() => setIsOpen(false)}
                className="p-1 text-gray-400 hover:text-gray-600 rounded"
                aria-label="Close notifications"
              >
                <X className="w-4 h-4" aria-hidden="true" />
              </button>
            </div>
          </div>

          {/* Notification list */}
          <div className="max-h-96 overflow-y-auto">
            {isLoading ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="w-6 h-6 text-primary-600 animate-spin" aria-hidden="true" />
              </div>
            ) : notifications && notifications.length > 0 ? (
              <div className="divide-y divide-gray-100">
                {notifications.map((notification) => (
                  <div
                    key={notification.id}
                    className={clsx(
                      'px-4 py-3 cursor-pointer transition-colors hover:bg-gray-50',
                      !notification.isRead && 'bg-primary-50'
                    )}
                    onClick={() => handleNotificationClick(notification)}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        handleNotificationClick(notification);
                      }
                    }}
                  >
                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 mt-0.5">
                        {getNotificationIcon(notification.type)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                          <p className={clsx(
                            'text-sm font-medium truncate',
                            notification.isRead ? 'text-gray-900' : 'text-primary-900'
                          )}>
                            {notification.title}
                          </p>
                          <div className="flex items-center gap-1 flex-shrink-0">
                            {!notification.isRead && (
                              <span className="w-2 h-2 bg-primary-500 rounded-full" title="Unread" />
                            )}
                            <button
                              type="button"
                              onClick={(e) => handleDelete(e, notification.id)}
                              disabled={deleteNotification.isPending}
                              className="p-1 text-gray-400 hover:text-red-600 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                              aria-label="Delete notification"
                            >
                              <Trash2 className="w-3 h-3" aria-hidden="true" />
                            </button>
                          </div>
                        </div>
                        {notification.body && (
                          <p className="text-sm text-gray-600 mt-0.5 line-clamp-2">
                            {notification.body}
                          </p>
                        )}
                        <p className="text-xs text-gray-400 mt-1">
                          {formatTimeAgo(notification.createdAt)}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-8 text-center">
                <Bell className="w-8 h-8 text-gray-300 mx-auto mb-2" aria-hidden="true" />
                <p className="text-sm text-gray-500">No notifications</p>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="px-4 py-2 border-t border-gray-200 bg-gray-50">
            <a
              href="/member-portal/schengen?tab=settings"
              className="flex items-center justify-center gap-1 text-xs text-gray-500 hover:text-gray-700"
            >
              <Settings className="w-3 h-3" aria-hidden="true" />
              Notification settings
            </a>
          </div>
        </div>
      )}
    </div>
  );
}
