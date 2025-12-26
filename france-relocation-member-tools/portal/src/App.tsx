import { useEffect, lazy, Suspense } from 'react';
import { clsx } from 'clsx';
import { useCurrentUser } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import ErrorBoundary from '@/components/shared/ErrorBoundary';
import Sidebar from '@/components/layout/Sidebar';
import Header from '@/components/layout/Header';

// Eagerly load Dashboard (most common initial view)
import Dashboard from '@/components/dashboard/Dashboard';

// Lazy load all other views for better initial bundle size
const TasksView = lazy(() => import('@/components/tasks/TasksView'));
const DocumentsView = lazy(() => import('@/components/documents/DocumentsView'));
const MessagesView = lazy(() => import('@/components/messages/MessagesView'));
const TimelineView = lazy(() => import('@/components/timeline/TimelineView'));
const SettingsView = lazy(() => import('@/components/settings/SettingsView'));
const HelpView = lazy(() => import('@/components/help/HelpView'));
const GuidesView = lazy(() => import('@/components/guides/GuidesView'));
const FamilyView = lazy(() => import('@/components/family/FamilyView'));
const ProfileView = lazy(() => import('@/components/profile/ProfileView'));
const ChecklistsView = lazy(() => import('@/components/checklists/ChecklistsView'));
const GlossaryView = lazy(() => import('@/components/glossary/GlossaryView'));
const KnowledgeBaseChat = lazy(() => import('@/components/chat/KnowledgeBaseChat'));
const MembershipView = lazy(() => import('@/components/membership/MembershipView'));
const ResearchView = lazy(() => import('@/components/research/ResearchView'));
const SchengenDashboard = lazy(() => import('@/components/schengen/SchengenDashboard'));

// Loading fallback component
function ViewLoadingFallback() {
  return (
    <div className="flex items-center justify-center min-h-[400px]">
      <div className="text-center">
        <div
          className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3"
          role="status"
          aria-label="Loading view"
        />
        <p className="text-gray-500 text-sm">Loading...</p>
      </div>
    </div>
  );
}

// View router with lazy loading support
function ViewRouter() {
  const { activeView } = usePortalStore();

  // Dashboard is eagerly loaded, others are lazy
  if (activeView === 'dashboard') {
    return <Dashboard />;
  }

  // All other views are lazy loaded with Suspense
  const LazyView = () => {
    switch (activeView) {
      case 'tasks':
        return <TasksView />;
      case 'messages':
        return <MessagesView />;
      case 'documents':
      case 'files':
        return <DocumentsView />;
      case 'timeline':
        return <TimelineView />;
      case 'guides':
        return <GuidesView />;
      case 'family':
        return <FamilyView />;
      case 'settings':
        return <SettingsView />;
      case 'help':
        return <HelpView />;
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
      case 'schengen':
        return <SchengenDashboard />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <Suspense fallback={<ViewLoadingFallback />}>
      <LazyView />
    </Suspense>
  );
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
