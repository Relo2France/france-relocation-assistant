import { useEffect } from 'react';
import { clsx } from 'clsx';
import { useCurrentUser } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import Sidebar from '@/components/layout/Sidebar';
import Header from '@/components/layout/Header';
import Dashboard from '@/components/dashboard/Dashboard';
import TasksView from '@/components/tasks/TasksView';
import { DocumentsView } from '@/components/documents';

function MessagesView() {
  return (
    <div className="p-6">
      <div className="card p-8 text-center">
        <h2 className="text-xl font-semibold text-gray-900 mb-2">Messages</h2>
        <p className="text-gray-600">Messages coming in Phase 4</p>
      </div>
    </div>
  );
}


function PlaceholderView({ title }: { title: string }) {
  return (
    <div className="p-6">
      <div className="card p-8 text-center">
        <h2 className="text-xl font-semibold text-gray-900 mb-2">{title}</h2>
        <p className="text-gray-600">This feature is coming soon!</p>
      </div>
    </div>
  );
}

// View router
function ViewRouter() {
  const { activeView } = usePortalStore();

  switch (activeView) {
    case 'dashboard':
      return <Dashboard />;
    case 'tasks':
      return <TasksView />;
    case 'messages':
      return <MessagesView />;
    case 'documents':
      return <DocumentsView />;
    case 'timeline':
      return <PlaceholderView title="Timeline" />;
    case 'guides':
      return <PlaceholderView title="Guides" />;
    case 'files':
      return <PlaceholderView title="Files" />;
    case 'family':
      return <PlaceholderView title="Family Members" />;
    case 'settings':
      return <PlaceholderView title="Settings" />;
    case 'help':
      return <PlaceholderView title="Help & Support" />;
    default:
      return <Dashboard />;
  }
}

export default function App() {
  const { sidebarCollapsed, setUser } = usePortalStore();
  const { data: user } = useCurrentUser();

  // Set user in store when loaded
  useEffect(() => {
    if (user) {
      setUser(user);
    }
  }, [user, setUser]);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Sidebar */}
      <Sidebar />

      {/* Main content */}
      <div
        className={clsx(
          'transition-all duration-300',
          sidebarCollapsed ? 'ml-16' : 'ml-64'
        )}
      >
        {/* Header */}
        <Header />

        {/* Page content */}
        <main className="min-h-[calc(100vh-4rem)]">
          <ViewRouter />
        </main>
      </div>
    </div>
  );
}
