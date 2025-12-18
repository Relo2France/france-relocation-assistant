/**
 * ErrorBoundary Component
 *
 * Catches JavaScript errors in child component tree and displays a fallback UI.
 * Prevents the entire app from crashing when a component fails.
 *
 * Usage:
 * <ErrorBoundary fallback={<ErrorFallback />}>
 *   <YourComponent />
 * </ErrorBoundary>
 */

import { Component, type ReactNode, type ErrorInfo } from 'react';
import { AlertTriangle, RefreshCw, Home } from 'lucide-react';

interface ErrorBoundaryProps {
  children: ReactNode;
  fallback?: ReactNode;
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
}

export default class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    // Log error to console in development
    console.error('ErrorBoundary caught an error:', error, errorInfo);

    // Call optional error handler
    if (this.props.onError) {
      this.props.onError(error, errorInfo);
    }
  }

  handleRetry = (): void => {
    this.setState({ hasError: false, error: null });
  };

  handleGoHome = (): void => {
    this.setState({ hasError: false, error: null });
    // Navigate to dashboard
    window.location.hash = '#dashboard';
    window.location.reload();
  };

  render(): ReactNode {
    if (this.state.hasError) {
      // Render custom fallback if provided
      if (this.props.fallback) {
        return this.props.fallback;
      }

      // Default fallback UI
      return (
        <ErrorFallback
          error={this.state.error}
          onRetry={this.handleRetry}
          onGoHome={this.handleGoHome}
        />
      );
    }

    return this.props.children;
  }
}

/**
 * Default error fallback UI
 */
interface ErrorFallbackProps {
  error: Error | null;
  onRetry: () => void;
  onGoHome: () => void;
}

function ErrorFallback({ error, onRetry, onGoHome }: ErrorFallbackProps) {
  return (
    <div
      className="min-h-[400px] flex items-center justify-center p-8"
      role="alert"
      aria-live="assertive"
    >
      <div className="text-center max-w-md">
        {/* Error icon */}
        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <AlertTriangle className="w-8 h-8 text-red-600" aria-hidden="true" />
        </div>

        {/* Error message */}
        <h2 className="text-xl font-semibold text-gray-900 mb-2">
          Something went wrong
        </h2>
        <p className="text-gray-600 mb-6">
          We encountered an unexpected error. Please try again or return to the dashboard.
        </p>

        {/* Error details (development only) */}
        {error && import.meta.env.DEV && (
          <details className="mb-6 text-left">
            <summary className="text-sm text-gray-500 cursor-pointer hover:text-gray-700">
              Error details
            </summary>
            <pre className="mt-2 p-3 bg-gray-100 rounded-lg text-xs text-red-600 overflow-auto max-h-32">
              {error.message}
              {error.stack && `\n\n${error.stack}`}
            </pre>
          </details>
        )}

        {/* Action buttons */}
        <div className="flex items-center justify-center gap-3">
          <button
            onClick={onRetry}
            className="btn btn-primary flex items-center gap-2"
          >
            <RefreshCw className="w-4 h-4" aria-hidden="true" />
            Try Again
          </button>
          <button
            onClick={onGoHome}
            className="btn btn-secondary flex items-center gap-2"
          >
            <Home className="w-4 h-4" aria-hidden="true" />
            Go to Dashboard
          </button>
        </div>
      </div>
    </div>
  );
}

/**
 * Compact error fallback for smaller sections
 */
export function CompactErrorFallback({
  onRetry,
}: {
  onRetry?: () => void;
}) {
  return (
    <div
      className="p-6 text-center"
      role="alert"
      aria-live="polite"
    >
      <div className="flex items-center justify-center gap-2 text-red-600 mb-2">
        <AlertTriangle className="w-5 h-5" aria-hidden="true" />
        <span className="font-medium">Failed to load</span>
      </div>
      {onRetry && (
        <button
          onClick={onRetry}
          className="text-sm text-primary-600 hover:text-primary-700 underline"
        >
          Try again
        </button>
      )}
    </div>
  );
}
