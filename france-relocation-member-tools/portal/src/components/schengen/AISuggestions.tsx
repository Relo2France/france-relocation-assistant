/**
 * AISuggestions
 *
 * Displays AI-powered trip planning suggestions based on user's travel history.
 */

import { clsx } from 'clsx';
import {
  Calendar,
  Clock,
  Sparkles,
  TrendingUp,
  ShieldAlert,
  AlertTriangle,
  Lightbulb,
  Loader2,
  RefreshCw,
} from 'lucide-react';
import type { Suggestion, SuggestionPriority } from '@/types';
import { useSchengenSuggestions } from '@/hooks/useApi';

interface AISuggestionsProps {
  className?: string;
  compact?: boolean;
}

const priorityStyles: Record<SuggestionPriority, { bg: string; border: string; text: string; icon: string }> = {
  high: {
    bg: 'bg-red-50',
    border: 'border-red-200',
    text: 'text-red-800',
    icon: 'text-red-600',
  },
  medium: {
    bg: 'bg-amber-50',
    border: 'border-amber-200',
    text: 'text-amber-800',
    icon: 'text-amber-600',
  },
  low: {
    bg: 'bg-blue-50',
    border: 'border-blue-200',
    text: 'text-blue-800',
    icon: 'text-blue-600',
  },
  info: {
    bg: 'bg-gray-50',
    border: 'border-gray-200',
    text: 'text-gray-800',
    icon: 'text-gray-600',
  },
};

function getIcon(iconName: string, className: string) {
  const icons: Record<string, React.ReactNode> = {
    calendar: <Calendar className={className} aria-hidden="true" />,
    clock: <Clock className={className} aria-hidden="true" />,
    sparkles: <Sparkles className={className} aria-hidden="true" />,
    'trending-up': <TrendingUp className={className} aria-hidden="true" />,
    'shield-alert': <ShieldAlert className={className} aria-hidden="true" />,
    'alert-triangle': <AlertTriangle className={className} aria-hidden="true" />,
  };

  return icons[iconName] || <Lightbulb className={className} aria-hidden="true" />;
}

function SuggestionCard({ suggestion, compact = false }: { suggestion: Suggestion; compact?: boolean }) {
  const styles = priorityStyles[suggestion.priority];

  return (
    <div
      className={clsx(
        'rounded-lg border p-4 transition-all hover:shadow-sm',
        styles.bg,
        styles.border,
        compact && 'p-3'
      )}
    >
      <div className="flex items-start gap-3">
        <div className={clsx('flex-shrink-0 mt-0.5', styles.icon)}>
          {getIcon(suggestion.icon, 'w-5 h-5')}
        </div>
        <div className="flex-1 min-w-0">
          <h4 className={clsx('font-medium', styles.text, compact ? 'text-sm' : 'text-base')}>
            {suggestion.title}
          </h4>
          <p className={clsx('mt-1 text-gray-600', compact ? 'text-xs' : 'text-sm')}>
            {suggestion.message}
          </p>
        </div>
      </div>
    </div>
  );
}

export default function AISuggestions({ className, compact = false }: AISuggestionsProps) {
  const { data, isLoading, isError, refetch, isFetching } = useSchengenSuggestions();

  if (isLoading) {
    return (
      <div className={clsx('card p-6', className)}>
        <div className="flex items-center justify-center py-8">
          <Loader2 className="w-6 h-6 text-primary-600 animate-spin" aria-hidden="true" />
          <span className="ml-2 text-gray-500">Analyzing your travel data...</span>
        </div>
      </div>
    );
  }

  if (isError || !data) {
    return (
      <div className={clsx('card p-6', className)}>
        <div className="text-center py-6">
          <p className="text-gray-500">Unable to load suggestions</p>
          <button
            onClick={() => refetch()}
            className="mt-2 text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            Try again
          </button>
        </div>
      </div>
    );
  }

  const { suggestions } = data;

  // Sort by priority: high > medium > low > info
  const priorityOrder: Record<SuggestionPriority, number> = {
    high: 0,
    medium: 1,
    low: 2,
    info: 3,
  };
  const sortedSuggestions = [...suggestions].sort(
    (a, b) => priorityOrder[a.priority] - priorityOrder[b.priority]
  );

  return (
    <div className={clsx('card', className)}>
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-purple-100 rounded-lg">
              <Sparkles className="w-5 h-5 text-purple-600" aria-hidden="true" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Smart Suggestions</h3>
              <p className="text-sm text-gray-500">
                AI-powered insights for your travel planning
              </p>
            </div>
          </div>
          <button
            onClick={() => refetch()}
            disabled={isFetching}
            className={clsx(
              'p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors',
              isFetching && 'animate-spin'
            )}
            aria-label="Refresh suggestions"
          >
            <RefreshCw className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div className={clsx('p-6 space-y-3', compact && 'p-4 space-y-2')}>
        {sortedSuggestions.length === 0 ? (
          <div className="text-center py-6">
            <Lightbulb className="w-10 h-10 text-gray-300 mx-auto mb-2" aria-hidden="true" />
            <p className="text-gray-500">No suggestions available</p>
            <p className="text-sm text-gray-400 mt-1">
              Add more trips to get personalized recommendations
            </p>
          </div>
        ) : (
          sortedSuggestions.map((suggestion, index) => (
            <SuggestionCard
              key={`${suggestion.type}-${index}`}
              suggestion={suggestion}
              compact={compact}
            />
          ))
        )}
      </div>
    </div>
  );
}
