import { useEffect, useRef, lazy, Suspense, useMemo } from 'react';
import { clsx } from 'clsx';
import { useCurrentUser } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import ErrorBoundary from '@/components/shared/ErrorBoundary';
import PWAPrompt from '@/components/shared/PWAPrompt';
import Sidebar from '@/components/layout/Sidebar';
import Header from '@/components/layout/Header';

// Views that should auto-collapse the sidebar for more content space
const IMMERSIVE_VIEWS = ['chat', 'research', 'schengen', 'guides'];

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
const TravelStatusDashboard = lazy(() => import('@/components/travel-status/TravelStatusDashboard'));

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

  // Memoize the view element to prevent recreation on every render
  // Dashboard is eagerly loaded, others are lazy loaded via Suspense
  const viewElement = useMemo(() => {
    switch (activeView) {
      case 'dashboard':
        return <Dashboard />;
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
        return <TravelStatusDashboard />;
      default:
        return <Dashboard />;
    }
  }, [activeView]);

  return (
    <Suspense fallback={<ViewLoadingFallback />}>
      {viewElement}
    </Suspense>
  );
}

export default function App() {
  const {
    sidebarCollapsed,
    setSidebarCollapsed,
    sidebarManuallyExpanded,
    activeView,
    setUser,
  } = usePortalStore();
  const { data: user } = useCurrentUser();
  const prevViewRef = useRef<string>(activeView);

  // Set user in store when loaded
  useEffect(() => {
    if (user) {
      setUser(user);
    }
  }, [user, setUser]);

  // Auto-collapse sidebar when entering immersive views, restore when leaving
  useEffect(() => {
    const isImmersive = IMMERSIVE_VIEWS.includes(activeView);
    const wasImmersive = IMMERSIVE_VIEWS.includes(prevViewRef.current);

    if (isImmersive && !wasImmersive) {
      // Entering immersive view - collapse sidebar
      setSidebarCollapsed(true);
    } else if (!isImmersive && wasImmersive && sidebarManuallyExpanded) {
      // Leaving immersive view - restore sidebar if user prefers expanded
      setSidebarCollapsed(false);
    }

    prevViewRef.current = activeView;
  }, [activeView, setSidebarCollapsed, sidebarManuallyExpanded]);

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

      {/* PWA Install Prompt & Update Banner */}
      <PWAPrompt />
    </div>
  );
}
