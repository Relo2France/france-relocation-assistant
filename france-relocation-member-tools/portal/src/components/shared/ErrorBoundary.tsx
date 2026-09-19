/**
 * ErrorBoundary
 *
 * Catches a crash in the part of the portal it wraps, shows the member what
 * broke, and reports it to the server so it appears under Portal crashes on
 * the settings screen. The member is told it was reported only once the
 * report has gone.
 *
 * Usage:
 * <ErrorBoundary compact>…</ErrorBoundary>  for the rail and the top bar
 * <ErrorBoundary>…</ErrorBoundary>          for a page
 */
import { Component, type ErrorInfo, type ReactNode } from 'react';
import { AlertTriangle, Home, RefreshCw } from 'lucide-react';

interface ErrorBoundaryProps {
  children: ReactNode;
  /** A small inline fallback, for the rail and the top bar. */
  compact?: boolean;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  reported: boolean;
}

/** Send a crash to the server. Resolves true when it was recorded. */
async function reportCrash(error: Error, componentStack = ''): Promise<boolean> {
  try {
    const wp = window.fraPortalData;
    if (!wp?.apiUrl) return false;
    const base = wp.apiUrl.endsWith('/') ? wp.apiUrl : `${wp.apiUrl}/`;
    const response = await fetch(`${base}portal/client-error`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': wp.nonce ?? '' },
      body: JSON.stringify({
        message: error.message,
        stack: (error.stack ?? '').slice(0, 2000),
        component: componentStack.slice(0, 1500),
        url: window.location.href,
      }),
    });
    return response.ok;
  } catch {
    return false;
  }
}

export default class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, error: null, reported: false };
  }

  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
    void reportCrash(error, errorInfo.componentStack ?? '').then((ok) => {
      if (ok) this.setState({ reported: true });
    });
    this.props.onError?.(error, errorInfo);
  }

  handleRetry = (): void => {
    this.setState({ hasError: false, error: null, reported: false });
  };

  handleGoHome = (): void => {
    // Views are chosen by ?view=, not the hash.
    const url = new URL(window.location.href);
    url.searchParams.set('view', 'dashboard');
    url.hash = '';
    window.location.assign(url.toString());
  };

  render(): ReactNode {
    if (!this.state.hasError) return this.props.children;
    if (this.props.compact) {
      return <CompactErrorFallback onRetry={this.handleRetry} />;
    }
    return (
      <ErrorFallback
        error={this.state.error}
        reported={this.state.reported}
        onRetry={this.handleRetry}
        onGoHome={this.handleGoHome}
      />
    );
  }
}

interface ErrorFallbackProps {
  error: Error | null;
  reported: boolean;
  onRetry: () => void;
  onGoHome: () => void;
}

function ErrorFallback({ error, reported, onRetry, onGoHome }: ErrorFallbackProps) {
  return (
    <div className="min-h-[400px] flex items-center justify-center p-8" role="alert" aria-live="assertive">
      <div className="text-center max-w-md">
        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <AlertTriangle className="w-8 h-8 text-red-600" aria-hidden="true" />
        </div>
        <h2 className="font-display text-xl font-semibold text-gray-900 mb-2">Something went wrong</h2>
        <p className="text-gray-600 mb-6">
          Something on this page broke.{reported ? ' It has been reported.' : ''} Try again, or go back to Where you are; if it happens twice, tell Support what you clicked.
        </p>
        {error ? (
          <details className="mb-6 text-left">
            <summary className="text-sm text-gray-500 cursor-pointer hover:text-gray-700">Error details</summary>
            <pre className="mt-2 p-3 bg-gray-100 rounded-lg text-xs text-red-600 overflow-auto max-h-32">
              {error.message}
              {error.stack && `\n\n${error.stack}`}
            </pre>
          </details>
        ) : null}
        <div className="flex items-center justify-center gap-3">
          <button onClick={onRetry} className="btn btn-primary flex items-center gap-2">
            <RefreshCw className="w-4 h-4" aria-hidden="true" />
            Try again
          </button>
          <button onClick={onGoHome} className="btn btn-secondary flex items-center gap-2">
            <Home className="w-4 h-4" aria-hidden="true" />
            Where you are
          </button>
        </div>
      </div>
    </div>
  );
}

/**
 * Compact fallback: for the rail, the top bar, and a section whose data
 * failed to load.
 */
export function CompactErrorFallback({ onRetry, message }: { onRetry?: () => void; message?: string }) {
  return (
    <div className="p-4 text-center" role="alert" aria-live="polite">
      <div className="flex items-center justify-center gap-2 text-red-600 mb-1.5">
        <AlertTriangle className="w-4 h-4" aria-hidden="true" />
        <span className="text-sm font-medium">{message ?? 'This could not be loaded.'}</span>
      </div>
      {onRetry ? (
        <button onClick={onRetry} className="text-sm font-semibold text-primary-500 hover:text-primary-700">
          Try again
        </button>
      ) : null}
    </div>
  );
}
