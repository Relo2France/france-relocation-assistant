import { useState } from 'react';
import { clsx } from 'clsx';
import {
  CheckCircle2,
  Circle,
  Clock,
  ChevronDown,
  ChevronUp,
  Edit2,
  Check,
  X,
} from 'lucide-react';
import type { ChecklistItem as ChecklistItemType, ChecklistItemStatus } from '@/types';

interface ChecklistItemProps {
  item: ChecklistItemType;
  onUpdate: (itemId: string, data: {
    status?: ChecklistItemStatus;
    handled_own?: boolean;
    notes?: string;
  }) => void;
  isUpdating?: boolean;
}

const statusConfig = {
  pending: {
    icon: Circle,
    color: 'text-gray-400',
    bgColor: 'bg-gray-50',
    borderColor: 'border-gray-200',
    label: 'Not Started',
  },
  in_progress: {
    icon: Circle,
    color: 'text-yellow-500',
    bgColor: 'bg-yellow-50',
    borderColor: 'border-yellow-200',
    label: 'In Progress',
  },
  complete: {
    icon: CheckCircle2,
    color: 'text-green-500',
    bgColor: 'bg-green-50',
    borderColor: 'border-green-200',
    label: 'Complete',
  },
};

export default function ChecklistItem({
  item,
  onUpdate,
  isUpdating = false,
}: ChecklistItemProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [isEditingNotes, setIsEditingNotes] = useState(false);
  const [notesValue, setNotesValue] = useState(item.notes || '');

  const config = statusConfig[item.status];
  const StatusIcon = config.icon;

  const handleStatusClick = () => {
    const nextStatus: Record<ChecklistItemStatus, ChecklistItemStatus> = {
      pending: 'in_progress',
      in_progress: 'complete',
      complete: 'pending',
    };
    onUpdate(item.id, { status: nextStatus[item.status] });
  };

  const handleStatusChange = (status: ChecklistItemStatus) => {
    onUpdate(item.id, { status });
  };

  const handleHandledOwnToggle = () => {
    onUpdate(item.id, { handled_own: !item.handled_own });
  };

  const handleSaveNotes = () => {
    onUpdate(item.id, { notes: notesValue });
    setIsEditingNotes(false);
  };

  const handleCancelNotes = () => {
    setNotesValue(item.notes || '');
    setIsEditingNotes(false);
  };

  return (
    <div
      className={clsx(
        'card transition-all',
        config.borderColor,
        'border-l-4',
        isExpanded && 'ring-2 ring-primary-100'
      )}
    >
      <div className="p-4">
        {/* Main row */}
        <div className="flex items-start gap-3">
          {/* Status icon (clickable) */}
          <button
            onClick={handleStatusClick}
            disabled={isUpdating}
            className={clsx(
              'flex-shrink-0 mt-0.5 transition-all hover:scale-110',
              config.color,
              isUpdating && 'opacity-50 cursor-not-allowed'
            )}
          >
            <StatusIcon className={clsx('w-5 h-5', item.status === 'in_progress' && 'fill-current')} />
          </button>

          {/* Content */}
          <div className="flex-1 min-w-0">
            {/* Title and lead time */}
            <div className="flex items-start justify-between gap-3 mb-1">
              <h3
                className={clsx(
                  'font-medium text-gray-900',
                  item.status === 'complete' && 'line-through text-gray-500'
                )}
              >
                {item.title}
              </h3>

              {/* Lead time badge */}
              <div className="flex items-center gap-1.5 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-full whitespace-nowrap">
                <Clock className="w-3 h-3" />
                {item.lead_time}
              </div>
            </div>

            {/* Description */}
            {item.description && (
              <p className="text-sm text-gray-600 mb-2">{item.description}</p>
            )}

            {/* Status dropdown and handled own toggle */}
            <div className="flex flex-wrap items-center gap-3 mt-2">
              {/* Status dropdown */}
              <select
                value={item.status}
                onChange={(e) => handleStatusChange(e.target.value as ChecklistItemStatus)}
                disabled={isUpdating}
                className={clsx(
                  'text-xs px-2 py-1 rounded-md border transition-colors',
                  config.borderColor,
                  config.bgColor,
                  config.color,
                  'font-medium cursor-pointer',
                  isUpdating && 'opacity-50 cursor-not-allowed'
                )}
              >
                <option value="pending">Not Started</option>
                <option value="in_progress">In Progress</option>
                <option value="complete">Complete</option>
              </select>

              {/* Handled own toggle */}
              <label className="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                <input
                  type="checkbox"
                  checked={item.handled_own}
                  onChange={handleHandledOwnToggle}
                  disabled={isUpdating}
                  className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span>I handled this myself</span>
              </label>

              {/* Expand/collapse button */}
              <button
                onClick={() => setIsExpanded(!isExpanded)}
                className="ml-auto text-xs text-gray-500 hover:text-gray-700 flex items-center gap-1"
              >
                {isExpanded ? (
                  <>
                    <ChevronUp className="w-3 h-3" />
                    Less
                  </>
                ) : (
                  <>
                    <ChevronDown className="w-3 h-3" />
                    Notes
                  </>
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Expanded notes section */}
        {isExpanded && (
          <div className="mt-4 pt-4 border-t border-gray-100">
            <div className="flex items-start justify-between mb-2">
              <label className="text-sm font-medium text-gray-700">Notes</label>
              {!isEditingNotes && (
                <button
                  onClick={() => setIsEditingNotes(true)}
                  className="text-xs text-primary-600 hover:text-primary-700 flex items-center gap-1"
                >
                  <Edit2 className="w-3 h-3" />
                  Edit
                </button>
              )}
            </div>

            {isEditingNotes ? (
              <div className="space-y-2">
                <textarea
                  value={notesValue}
                  onChange={(e) => setNotesValue(e.target.value)}
                  placeholder="Add notes about this item..."
                  rows={3}
                  className="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                />
                <div className="flex items-center gap-2">
                  <button
                    onClick={handleSaveNotes}
                    disabled={isUpdating}
                    className="btn btn-primary btn-sm"
                  >
                    <Check className="w-3 h-3" />
                    Save
                  </button>
                  <button
                    onClick={handleCancelNotes}
                    disabled={isUpdating}
                    className="btn btn-secondary btn-sm"
                  >
                    <X className="w-3 h-3" />
                    Cancel
                  </button>
                </div>
              </div>
            ) : (
              <div className="text-sm text-gray-600">
                {item.notes ? (
                  <p className="whitespace-pre-wrap bg-gray-50 rounded-lg p-3">
                    {item.notes}
                  </p>
                ) : (
                  <p className="text-gray-400 italic">No notes yet</p>
                )}
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
