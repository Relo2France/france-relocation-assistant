import { useEffect } from 'react';
import { clsx } from 'clsx';
import { useCurrentUser } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import ErrorBoundary from '@/components/shared/ErrorBoundary';
import Sidebar from '@/components/layout/Sidebar';
import Header from '@/components/layout/Header';
import Dashboard from '@/components/dashboard/Dashboard';
import TasksView from '@/components/tasks/TasksView';
import { DocumentsView } from '@/components/documents';
import { MessagesView } from '@/components/messages';
import { TimelineView } from '@/components/timeline';
import { SettingsView } from '@/components/settings';
import { HelpView } from '@/components/help';
import { GuidesView } from '@/components/guides';
import { FamilyView } from '@/components/family';
// New imports for added features
import { ProfileView } from '@/components/profile';
import { ChecklistsView } from '@/components/checklists';
import { GlossaryView } from '@/components/glossary';
import { KnowledgeBaseChat } from '@/components/chat';
import { MembershipView } from '@/components/membership';
import { ResearchView } from '@/components/research';

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
      return <TimelineView />;
    case 'guides':
      return <GuidesView />;
    case 'files':
      return <DocumentsView />;
    case 'family':
      return <FamilyView />;
    case 'settings':
      return <SettingsView />;
    case 'help':
      return <HelpView />;
    // New routes for added features
    case 'profile':
      return <ProfileView />;
    case 'checklists':
      return <ChecklistsView />;
    case 'glossary':
      return <GlossaryView />;
    case 'chat':
      return <KnowledgeBaseChat />;
    case 'membership':
      return <MembershipView />;
    case 'research':
      return <ResearchView />;
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
          <ErrorBoundary>
            <ViewRouter />
          </ErrorBoundary>
        </main>
      </div>
    </div>
  );
}
