import { useState, useMemo } from 'react';
import { useDashboard, useTasks, useUpdateTaskStatus } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import FilterBar from './FilterBar';
import TaskList from './TaskList';
import TaskBoard from './TaskBoard';
import TaskDetail from './TaskDetail';
import TaskForm from './TaskForm';
import type { Task, TaskStatus } from '@/types';

export default function TasksView() {
  // View state
  const [view, setView] = useState<'list' | 'board'>('board');
  const [searchQuery, setSearchQuery] = useState('');
  const { taskFilters, setTaskFilters, resetTaskFilters } = usePortalStore();

  // Modal state
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [showTaskDetail, setShowTaskDetail] = useState(false);
  const [showAddTask, setShowAddTask] = useState(false);
  const [addTaskDefaults, setAddTaskDefaults] = useState<{
    status?: TaskStatus;
    stage?: string;
  }>({});

  // Data
  const { data: dashboard, isLoading: dashboardLoading } = useDashboard();
  const { data: tasks = [], isLoading: tasksLoading } = useTasks(
    dashboard?.project?.id || 0
  );
  const updateTaskStatus = useUpdateTaskStatus();

  // Filter tasks
  const filteredTasks = useMemo(() => {
    let result = [...tasks];

    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      result = result.filter(
        (task) =>
          task.title.toLowerCase().includes(query) ||
          task.description?.toLowerCase().includes(query)
      );
    }

    // Stage filter
    if (taskFilters.stage) {
      result = result.filter((task) => task.stage === taskFilters.stage);
    }

    // Status filter
    if (taskFilters.status) {
      result = result.filter((task) => task.status === taskFilters.status);
    }

    // Task type filter
    if (taskFilters.taskType) {
      result = result.filter((task) => task.task_type === taskFilters.taskType);
    }

    return result;
  }, [tasks, searchQuery, taskFilters]);

  // Task counts for status tabs
  const taskCounts = useMemo(() => {
    return {
      all: tasks.length,
      todo: tasks.filter((t) => t.status === 'todo').length,
      in_progress: tasks.filter((t) => t.status === 'in_progress').length,
      waiting: tasks.filter((t) => t.status === 'waiting').length,
      done: tasks.filter((t) => t.status === 'done').length,
    };
  }, [tasks]);

  // Handlers
  const handleTaskClick = (task: Task) => {
    setSelectedTask(task);
    setShowTaskDetail(true);
  };

  const handleStatusChange = (taskId: number, status: TaskStatus) => {
    updateTaskStatus.mutate({ id: taskId, status });
  };

  const handleAddTask = (status?: TaskStatus, stage?: string) => {
    setAddTaskDefaults({ status, stage });
    setShowAddTask(true);
  };

  const handleCloseTaskDetail = () => {
    setShowTaskDetail(false);
    setSelectedTask(null);
  };

  // Loading state
  if (dashboardLoading || tasksLoading) {
    return <TasksViewSkeleton />;
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

  const { project, stages } = dashboard;

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Tasks</h1>
        <p className="text-gray-600 mt-1">
          Manage your relocation tasks across all stages
        </p>
      </div>

      {/* Filter bar */}
      <div className="card mb-6">
        <div className="p-4">
          <FilterBar
            view={view}
            onViewChange={setView}
            searchQuery={searchQuery}
            onSearchChange={setSearchQuery}
            filters={taskFilters}
            onFilterChange={setTaskFilters}
            onClearFilters={resetTaskFilters}
            stages={stages}
            onAddTask={() => handleAddTask()}
            totalTasks={tasks.length}
            filteredTasks={filteredTasks.length}
          />
        </div>
      </div>

      {/* Task view */}
      {view === 'board' ? (
        <TaskBoard
          tasks={filteredTasks}
          onTaskClick={handleTaskClick}
          onStatusChange={handleStatusChange}
          onAddTask={(status) => handleAddTask(status)}
        />
      ) : (
        <TaskList
          tasks={filteredTasks}
          groupBy="stage"
          onTaskClick={handleTaskClick}
          onStatusChange={handleStatusChange}
          onAddTask={(stage) => handleAddTask(undefined, stage)}
        />
      )}

      {/* Empty state */}
      {filteredTasks.length === 0 && tasks.length > 0 && (
        <div className="card p-8 text-center mt-6">
          <p className="text-gray-500">No tasks match your filters</p>
          <button
            onClick={resetTaskFilters}
            className="mt-2 text-primary-600 hover:text-primary-700"
          >
            Clear filters
          </button>
        </div>
      )}

      {tasks.length === 0 && (
        <div className="card p-12 text-center mt-6">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <svg className="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">No tasks yet</h3>
          <p className="text-gray-600 mb-4">
            Create your first task to start tracking your relocation progress
          </p>
          <button onClick={() => handleAddTask()} className="btn btn-primary">
            Create Your First Task
          </button>
        </div>
      )}

      {/* Task detail drawer */}
      <TaskDetail
        task={selectedTask}
        isOpen={showTaskDetail}
        onClose={handleCloseTaskDetail}
      />

      {/* Add task modal */}
      <TaskForm
        isOpen={showAddTask}
        onClose={() => setShowAddTask(false)}
        projectId={project.id}
        defaultStatus={addTaskDefaults.status}
        defaultStage={addTaskDefaults.stage}
        stages={stages}
      />
    </div>
  );
}

// Loading skeleton
function TasksViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 w-48" />
      <div className="card mb-6">
        <div className="p-4">
          <div className="h-10 bg-gray-200 rounded-lg animate-pulse" />
        </div>
      </div>
      <div className="flex gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="w-80 flex-shrink-0">
            <div className="h-12 bg-gray-200 rounded-t-xl animate-pulse" />
            <div className="bg-gray-100 rounded-b-xl p-4 space-y-3">
              {[1, 2, 3].map((j) => (
                <div key={j} className="h-24 bg-gray-200 rounded-lg animate-pulse" />
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
