import { useMemo, useState } from 'react';
import { useDashboard, useTasks, useUpdateTaskStatus } from '@/hooks/useApi';
import { JOURNEY, stageForTask } from '@/journey/journey';
import { usePortalStore } from '@/store';
import type { Task, TaskStatus } from '@/types';
import FilterBar from './FilterBar';
import TaskBoard from './TaskBoard';
import TaskDetail from './TaskDetail';
import TaskForm from './TaskForm';
import TaskList from './TaskList';

export default function TasksView() {
  // View state
  const [view, setView] = useState<'list' | 'board'>('board');
  const [searchQuery, setSearchQuery] = useState('');
  const { taskFilters, setTaskFilters, resetTaskFilters, setActiveView, setActiveStage } = usePortalStore();

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
      const wanted = taskFilters.stage;
      const project = dashboard?.project;
      // A journey stage id, or a raw template/database stage from an "Open" link.
      result = result.filter((task) =>
        task.stage === wanted || (project ? stageForTask(task, project) === wanted : false)
      );
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
  }, [tasks, searchQuery, taskFilters, dashboard?.project]);

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

  const { project } = dashboard;
  const journeyStages = JOURNEY.map((j) => ({ slug: j.id, title: `${j.number} · ${j.name}` }));

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="font-display text-2xl font-semibold tracking-[-0.018em] text-ink">Tasks</h1>
        <p className="text-gray-600 mt-1">
          Every step, across all six stages
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
            stages={journeyStages}
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
          resolveStage={(task) => stageForTask(task, project)}
          stageTitles={Object.fromEntries(JOURNEY.map((j) => [j.id, `${j.number} · ${j.name}`]))}
          stageOrder={JOURNEY.map((j) => j.id)}
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
          <h3 className="font-display text-lg font-semibold text-ink mb-2">No steps yet</h3>
          <p className="text-gray-600 mb-4">
            Your steps appear here once you set a route and a move date.
          </p>
          <button onClick={() => { setActiveStage('decide'); setActiveView('stage'); }} className="btn btn-primary">
            Start with Decide
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
        stages={journeyStages}
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
