import { clsx } from 'clsx';
import {
  LayoutList,
  LayoutGrid,
  Filter,
  Search,
  X,
  Plus,
} from 'lucide-react';
import type { StageProgress, TaskStatus } from '@/types';

interface FilterBarProps {
  view: 'list' | 'board';
  onViewChange: (view: 'list' | 'board') => void;
  searchQuery: string;
  onSearchChange: (query: string) => void;
  filters: {
    stage: string | null;
    status: string | null;
    taskType: string | null;
  };
  onFilterChange: (filters: Partial<FilterBarProps['filters']>) => void;
  onClearFilters: () => void;
  stages: StageProgress[];
  onAddTask: () => void;
  totalTasks: number;
  filteredTasks: number;
}

export default function FilterBar({
  view,
  onViewChange,
  searchQuery,
  onSearchChange,
  filters,
  onFilterChange,
  onClearFilters,
  stages,
  onAddTask,
  totalTasks,
  filteredTasks,
}: FilterBarProps) {
  const hasActiveFilters = filters.stage || filters.status || filters.taskType || searchQuery;

  return (
    <div className="space-y-4">
      {/* Main toolbar */}
      <div className="flex items-center justify-between gap-4">
        {/* Left side - View toggle and search */}
        <div className="flex items-center gap-3">
          {/* View toggle */}
          <div className="flex rounded-lg bg-gray-100 p-1">
            <button
              onClick={() => onViewChange('list')}
              className={clsx(
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                view === 'list'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              <LayoutList className="w-4 h-4" />
              List
            </button>
            <button
              onClick={() => onViewChange('board')}
              className={clsx(
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                view === 'board'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              <LayoutGrid className="w-4 h-4" />
              Board
            </button>
          </div>

          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => onSearchChange(e.target.value)}
              placeholder="Search tasks..."
              className="pl-9 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 w-64"
            />
            {searchQuery && (
              <button
                onClick={() => onSearchChange('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>

        {/* Right side - Add task button */}
        <button onClick={onAddTask} className="btn btn-primary">
          <Plus className="w-4 h-4" />
          Add Task
        </button>
      </div>

      {/* Filter row */}
      <div className="flex items-center gap-3">
        <Filter className="w-4 h-4 text-gray-400" />

        {/* Stage filter */}
        <select
          value={filters.stage || ''}
          onChange={(e) => onFilterChange({ stage: e.target.value || null })}
          className="select w-auto text-sm py-1.5"
        >
          <option value="">All Stages</option>
          {stages.map((stage) => (
            <option key={stage.slug} value={stage.slug}>
              {stage.title}
            </option>
          ))}
        </select>

        {/* Status filter */}
        <select
          value={filters.status || ''}
          onChange={(e) => onFilterChange({ status: e.target.value || null })}
          className="select w-auto text-sm py-1.5"
        >
          <option value="">All Statuses</option>
          <option value="todo">To Do</option>
          <option value="in_progress">In Progress</option>
          <option value="waiting">Waiting</option>
          <option value="done">Done</option>
        </select>

        {/* Type filter */}
        <select
          value={filters.taskType || ''}
          onChange={(e) => onFilterChange({ taskType: e.target.value || null })}
          className="select w-auto text-sm py-1.5"
        >
          <option value="">All Types</option>
          <option value="client">Client</option>
          <option value="team">Team</option>
        </select>

        {/* Clear filters */}
        {hasActiveFilters && (
          <button
            onClick={onClearFilters}
            className="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
          >
            <X className="w-3 h-3" />
            Clear filters
          </button>
        )}

        {/* Task count */}
        <span className="text-sm text-gray-500 ml-auto">
          {filteredTasks === totalTasks
            ? `${totalTasks} tasks`
            : `${filteredTasks} of ${totalTasks} tasks`}
        </span>
      </div>
    </div>
  );
}

// Status tabs variant
interface StatusTabsProps {
  activeStatus: TaskStatus | 'all';
  onStatusChange: (status: TaskStatus | 'all') => void;
  counts: Record<string, number>;
}

export function StatusTabs({ activeStatus, onStatusChange, counts }: StatusTabsProps) {
  const tabs: { id: TaskStatus | 'all'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'todo', label: 'To Do' },
    { id: 'in_progress', label: 'In Progress' },
    { id: 'waiting', label: 'Waiting' },
    { id: 'done', label: 'Done' },
  ];

  return (
    <div className="flex border-b border-gray-200">
      {tabs.map((tab) => (
        <button
          key={tab.id}
          onClick={() => onStatusChange(tab.id)}
          className={clsx(
            'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
            activeStatus === tab.id
              ? 'text-primary-600 border-primary-600'
              : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'
          )}
        >
          {tab.label}
          {counts[tab.id] !== undefined && (
            <span
              className={clsx(
                'ml-2 px-2 py-0.5 rounded-full text-xs',
                activeStatus === tab.id
                  ? 'bg-primary-100 text-primary-700'
                  : 'bg-gray-100 text-gray-600'
              )}
            >
              {counts[tab.id]}
            </span>
          )}
        </button>
      ))}
    </div>
  );
}

// Type toggle (All / Team / Client)
interface TypeToggleProps {
  activeType: 'all' | 'client' | 'team';
  onTypeChange: (type: 'all' | 'client' | 'team') => void;
}

export function TypeToggle({ activeType, onTypeChange }: TypeToggleProps) {
  const types: { id: 'all' | 'client' | 'team'; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'team', label: 'Team' },
    { id: 'client', label: 'Client' },
  ];

  return (
    <div className="flex rounded-lg bg-gray-100 p-1">
      {types.map((type) => (
        <button
          key={type.id}
          onClick={() => onTypeChange(type.id)}
          className={clsx(
            'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
            activeType === type.id
              ? 'bg-white text-gray-900 shadow-sm'
              : 'text-gray-600 hover:text-gray-900'
          )}
        >
          {type.label}
        </button>
      ))}
    </div>
  );
}
