import { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import {
  CheckCircle2,
  Circle,
  ListChecks,
  Filter,
  X,
  FileText,
  Plane,
  ArrowUpDown,
} from 'lucide-react';
import { useDashboard, useChecklists, useUpdateChecklistItem } from '@/hooks/useApi';
import ChecklistItem from './ChecklistItem';
import type { Checklist, ChecklistItem as ChecklistItemType, ChecklistItemStatus } from '@/types';

type FilterType = 'all' | 'pending' | 'complete';
type SortType = 'default' | 'lead_time' | 'status';

export default function ChecklistsView() {
  const [filterType, setFilterType] = useState<FilterType>('all');
  const [sortType, setSortType] = useState<SortType>('default');

  // Data
  const { data: dashboard, isLoading: dashboardLoading } = useDashboard();
  const visaType = dashboard?.project?.visa_type;
  const { data: checklists = [], isLoading: checklistsLoading } = useChecklists(visaType);
  const updateChecklistItem = useUpdateChecklistItem();

  // Separate checklists
  const visaChecklist = checklists.find(c => c.type === 'visa-application');
  const relocationChecklist = checklists.find(c => c.type === 'relocation');

  // Filter and sort items
  const filterAndSortItems = (items: ChecklistItemType[]) => {
    let filtered = [...items];

    // Apply filter
    if (filterType === 'pending') {
      filtered = filtered.filter(item => item.status !== 'complete');
    } else if (filterType === 'complete') {
      filtered = filtered.filter(item => item.status === 'complete');
    }

    // Apply sort
    if (sortType === 'lead_time') {
      // Sort by estimated lead time (parse time strings)
      filtered.sort((a, b) => {
        const parseTime = (time: string) => {
          if (time.includes('week')) {
            const weeks = parseInt(time);
            return weeks * 7 * 24 * 60; // Convert to minutes
          }
          if (time.includes('day')) {
            const days = parseInt(time);
            return days * 24 * 60;
          }
          if (time.includes('hour') || time.includes('min')) {
            const hours = parseInt(time);
            return hours * 60;
          }
          return 0;
        };
        return parseTime(b.lead_time) - parseTime(a.lead_time);
      });
    } else if (sortType === 'status') {
      const statusOrder = { pending: 0, in_progress: 1, complete: 2 };
      filtered.sort((a, b) => statusOrder[a.status] - statusOrder[b.status]);
    } else {
      // Default: by sort_order
      filtered.sort((a, b) => a.sort_order - b.sort_order);
    }

    return filtered;
  };

  const filteredVisaItems = visaChecklist ? filterAndSortItems(visaChecklist.items) : [];
  const filteredRelocationItems = relocationChecklist ? filterAndSortItems(relocationChecklist.items) : [];

  // Overall stats
  const totalItems = (visaChecklist?.items.length || 0) + (relocationChecklist?.items.length || 0);
  const completedItems =
    (visaChecklist?.items.filter(i => i.status === 'complete').length || 0) +
    (relocationChecklist?.items.filter(i => i.status === 'complete').length || 0);
  const overallPercentage = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;

  // Handle item update
  const handleUpdateItem = (
    checklistType: 'visa-application' | 'relocation',
    itemId: string,
    data: { status?: ChecklistItemStatus; handled_own?: boolean; notes?: string }
  ) => {
    updateChecklistItem.mutate({
      checklistType,
      itemId,
      data,
    });
  };

  // Loading state
  if (dashboardLoading || checklistsLoading) {
    return <ChecklistsViewSkeleton />;
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

  const hasActiveFilters = filterType !== 'all' || sortType !== 'default';

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Checklists</h1>
        <p className="text-gray-600 mt-1">
          Track your visa application and relocation document preparation
        </p>
      </div>

      {/* Overall progress card */}
      <div className="card mb-6 bg-gradient-to-br from-primary-50 to-primary-100/50">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-full bg-primary-600 flex items-center justify-center">
                <ListChecks className="w-6 h-6 text-white" />
              </div>
              <div>
                <h2 className="text-lg font-semibold text-gray-900">Overall Progress</h2>
                <p className="text-sm text-gray-600">
                  {completedItems} of {totalItems} items complete
                </p>
              </div>
            </div>

            <div className="text-right">
              <div className="text-3xl font-bold text-primary-700">{overallPercentage}%</div>
              <div className="text-xs text-gray-600">Complete</div>
            </div>
          </div>

          {/* Progress bar */}
          <div className="h-3 bg-white/50 rounded-full overflow-hidden">
            <div
              className="h-full bg-primary-600 rounded-full transition-all duration-500"
              style={{ width: `${overallPercentage}%` }}
            />
          </div>
        </div>
      </div>

      {/* Filters and sorting toolbar */}
      <div className="card mb-6">
        <div className="p-4">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            {/* Left side - Filters */}
            <div className="flex items-center gap-3 w-full sm:w-auto">
              <Filter className="w-4 h-4 text-gray-400" />

              {/* Status filter */}
              <div className="flex rounded-lg bg-gray-100 p-1">
                <button
                  onClick={() => setFilterType('all')}
                  className={clsx(
                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                    filterType === 'all'
                      ? 'bg-white text-gray-900 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  All
                </button>
                <button
                  onClick={() => setFilterType('pending')}
                  className={clsx(
                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                    filterType === 'pending'
                      ? 'bg-white text-gray-900 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  Pending
                </button>
                <button
                  onClick={() => setFilterType('complete')}
                  className={clsx(
                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                    filterType === 'complete'
                      ? 'bg-white text-gray-900 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  Complete
                </button>
              </div>
            </div>

            {/* Right side - Sort */}
            <div className="flex items-center gap-3">
              <ArrowUpDown className="w-4 h-4 text-gray-400" />
              <select
                value={sortType}
                onChange={(e) => setSortType(e.target.value as SortType)}
                className="select text-sm py-1.5"
              >
                <option value="default">Default Order</option>
                <option value="lead_time">Lead Time</option>
                <option value="status">Status</option>
              </select>

              {/* Clear filters */}
              {hasActiveFilters && (
                <button
                  onClick={() => {
                    setFilterType('all');
                    setSortType('default');
                  }}
                  className="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
                >
                  <X className="w-3 h-3" />
                  Clear
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Checklists */}
      <div className="space-y-6">
        {/* Visa Application Checklist */}
        {visaChecklist && (
          <ChecklistSection
            checklist={visaChecklist}
            filteredItems={filteredVisaItems}
            icon={Plane}
            iconColor="text-blue-600"
            iconBg="bg-blue-100"
            onUpdateItem={(itemId, data) => handleUpdateItem('visa-application', itemId, data)}
            isUpdating={updateChecklistItem.isPending}
          />
        )}

        {/* Relocation Document Checklist */}
        {relocationChecklist && (
          <ChecklistSection
            checklist={relocationChecklist}
            filteredItems={filteredRelocationItems}
            icon={FileText}
            iconColor="text-purple-600"
            iconBg="bg-purple-100"
            onUpdateItem={(itemId, data) => handleUpdateItem('relocation', itemId, data)}
            isUpdating={updateChecklistItem.isPending}
          />
        )}
      </div>

      {/* Empty state when filters return no results */}
      {filteredVisaItems.length === 0 && filteredRelocationItems.length === 0 && totalItems > 0 && (
        <div className="card p-8 text-center mt-6">
          <Circle className="w-12 h-12 text-gray-300 mx-auto mb-3" />
          <p className="text-gray-500 mb-2">No items match your filters</p>
          <button
            onClick={() => {
              setFilterType('all');
              setSortType('default');
            }}
            className="text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            Clear filters
          </button>
        </div>
      )}

      {/* No checklists state */}
      {checklists.length === 0 && (
        <div className="card p-12 text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <ListChecks className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">No checklists available</h3>
          <p className="text-gray-600">
            Checklists will be available once your project is set up with a visa type.
          </p>
        </div>
      )}
    </div>
  );
}

// Checklist section component
interface ChecklistSectionProps {
  checklist: Checklist;
  filteredItems: ChecklistItemType[];
  icon: React.ComponentType<{ className?: string }>;
  iconColor: string;
  iconBg: string;
  onUpdateItem: (
    itemId: string,
    data: { status?: ChecklistItemStatus; handled_own?: boolean; notes?: string }
  ) => void;
  isUpdating: boolean;
}

function ChecklistSection({
  checklist,
  filteredItems,
  icon: Icon,
  iconColor,
  iconBg,
  onUpdateItem,
  isUpdating,
}: ChecklistSectionProps) {
  const completed = checklist.items.filter(i => i.status === 'complete').length;
  const total = checklist.items.length;
  const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;

  return (
    <div className="card">
      {/* Checklist header */}
      <div className="p-6 border-b border-gray-100">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className={clsx('w-10 h-10 rounded-lg flex items-center justify-center', iconBg)}>
              <Icon className={clsx('w-5 h-5', iconColor)} />
            </div>
            <div>
              <h2 className="text-lg font-semibold text-gray-900">{checklist.title}</h2>
              <p className="text-sm text-gray-600">
                {completed} of {total} items complete
              </p>
            </div>
          </div>

          <div className="text-right">
            <div className="text-2xl font-bold text-gray-900">{percentage}%</div>
          </div>
        </div>

        {/* Progress bar */}
        <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
          <div
            className={clsx(
              'h-full rounded-full transition-all duration-500',
              iconColor.replace('text-', 'bg-')
            )}
            style={{ width: `${percentage}%` }}
          />
        </div>
      </div>

      {/* Checklist items */}
      <div className="p-6">
        {filteredItems.length > 0 ? (
          <div className="space-y-3">
            {filteredItems.map((item) => (
              <ChecklistItem
                key={item.id}
                item={item}
                onUpdate={onUpdateItem}
                isUpdating={isUpdating}
              />
            ))}
          </div>
        ) : (
          <div className="text-center py-8 text-gray-500 text-sm">
            No items to show with current filters
          </div>
        )}
      </div>
    </div>
  );
}

// Loading skeleton
function ChecklistsViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 w-48" />

      {/* Overall progress skeleton */}
      <div className="card mb-6">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-full bg-gray-200 animate-pulse" />
              <div className="space-y-2">
                <div className="h-5 w-32 bg-gray-200 rounded animate-pulse" />
                <div className="h-4 w-24 bg-gray-100 rounded animate-pulse" />
              </div>
            </div>
            <div className="h-8 w-16 bg-gray-200 rounded animate-pulse" />
          </div>
          <div className="h-3 bg-gray-200 rounded-full animate-pulse" />
        </div>
      </div>

      {/* Toolbar skeleton */}
      <div className="card mb-6">
        <div className="p-4">
          <div className="h-10 bg-gray-200 rounded-lg animate-pulse" />
        </div>
      </div>

      {/* Checklist skeletons */}
      {[1, 2].map((i) => (
        <div key={i} className="card mb-6">
          <div className="p-6 border-b border-gray-100">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-gray-200 animate-pulse" />
                <div className="space-y-2">
                  <div className="h-5 w-40 bg-gray-200 rounded animate-pulse" />
                  <div className="h-4 w-28 bg-gray-100 rounded animate-pulse" />
                </div>
              </div>
              <div className="h-8 w-12 bg-gray-200 rounded animate-pulse" />
            </div>
            <div className="h-2 bg-gray-200 rounded-full animate-pulse" />
          </div>
          <div className="p-6 space-y-3">
            {[1, 2, 3].map((j) => (
              <div key={j} className="card p-4">
                <div className="flex gap-3">
                  <div className="w-5 h-5 rounded-full bg-gray-200 animate-pulse flex-shrink-0 mt-0.5" />
                  <div className="flex-1 space-y-2">
                    <div className="h-5 bg-gray-200 rounded animate-pulse w-3/4" />
                    <div className="h-4 bg-gray-100 rounded animate-pulse w-1/2" />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}
