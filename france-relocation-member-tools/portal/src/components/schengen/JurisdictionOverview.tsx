/**
 * JurisdictionOverview
 *
 * Shows an overview of all tracked jurisdictions with status cards.
 * Allows users to add/remove jurisdictions they want to track.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Globe,
  Plus,
  X,
  Clock,
  Calendar,
  Loader2,
  ChevronDown,
} from 'lucide-react';
import type { JurisdictionRule, JurisdictionSummary, JurisdictionType } from '@/types';
import {
  useJurisdictions,
  useTrackedJurisdictions,
  useAddTrackedJurisdiction,
  useRemoveTrackedJurisdiction,
  useMultiJurisdictionSummary,
} from '@/hooks/useApi';
import DayCounter from './DayCounter';
import StatusBadge from './StatusBadge';

interface JurisdictionOverviewProps {
  className?: string;
}

export default function JurisdictionOverview({ className }: JurisdictionOverviewProps) {
  const { data: allJurisdictions, isLoading: loadingAll } = useJurisdictions();
  const { data: trackedJurisdictions, isLoading: loadingTracked } = useTrackedJurisdictions();
  const { data: summaries, isLoading: loadingSummaries } = useMultiJurisdictionSummary();
  const addJurisdiction = useAddTrackedJurisdiction();
  const removeJurisdiction = useRemoveTrackedJurisdiction();

  const [showAddModal, setShowAddModal] = useState(false);
  const [filterType, setFilterType] = useState<JurisdictionType | 'all'>('all');

  const isLoading = loadingAll || loadingTracked || loadingSummaries;

  // Get tracked jurisdiction codes
  const trackedCodes = new Set(trackedJurisdictions?.map(j => j.code) || []);

  // Filter available jurisdictions (not already tracked)
  const availableJurisdictions = allJurisdictions?.filter(j => !trackedCodes.has(j.code)) || [];

  // Apply type filter
  const filteredAvailable = filterType === 'all'
    ? availableJurisdictions
    : availableJurisdictions.filter(j => j.type === filterType);

  // Group by type for display
  const groupedAvailable = filteredAvailable.reduce<Record<string, JurisdictionRule[]>>((acc, j) => {
    const key = j.type;
    if (!acc[key]) acc[key] = [];
    acc[key].push(j);
    return acc;
  }, {});

  const handleAddJurisdiction = async (code: string) => {
    try {
      await addJurisdiction.mutateAsync(code);
    } catch (error) {
      console.error('Failed to add jurisdiction:', error);
    }
  };

  const handleRemoveJurisdiction = async (code: string) => {
    try {
      await removeJurisdiction.mutateAsync(code);
    } catch (error) {
      console.error('Failed to remove jurisdiction:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[200px]">
        <div className="text-center">
          <div
            className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3"
            role="status"
            aria-label="Loading"
          />
          <p className="text-gray-500 text-sm">Loading jurisdictions...</p>
        </div>
      </div>
    );
  }

  return (
    <div className={clsx('space-y-6', className)}>
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-primary-100 rounded-lg">
            <Globe className="w-5 h-5 text-primary-600" aria-hidden="true" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Jurisdiction Tracking</h3>
            <p className="text-sm text-gray-500">
              Track multiple visa and residency rules
            </p>
          </div>
        </div>
        <button
          onClick={() => setShowAddModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
        >
          <Plus className="w-4 h-4" aria-hidden="true" />
          Add Jurisdiction
        </button>
      </div>

      {/* Tracked jurisdictions grid */}
      {trackedJurisdictions && trackedJurisdictions.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {trackedJurisdictions.map((jurisdiction) => {
            const summary = summaries?.[jurisdiction.code];
            return (
              <JurisdictionCard
                key={jurisdiction.code}
                jurisdiction={jurisdiction}
                summary={summary}
                onRemove={() => handleRemoveJurisdiction(jurisdiction.code)}
                isRemoving={removeJurisdiction.isPending}
              />
            );
          })}
        </div>
      ) : (
        <div className="card p-8 text-center">
          <Globe className="w-12 h-12 text-gray-300 mx-auto mb-3" aria-hidden="true" />
          <h4 className="text-lg font-medium text-gray-900 mb-2">No Jurisdictions Tracked</h4>
          <p className="text-gray-500 mb-4">
            Add jurisdictions to track your visa and residency compliance across multiple countries.
          </p>
          <button
            onClick={() => setShowAddModal(true)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
          >
            <Plus className="w-4 h-4" aria-hidden="true" />
            Add Your First Jurisdiction
          </button>
        </div>
      )}

      {/* Add jurisdiction modal */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[80vh] overflow-hidden">
            <div className="flex items-center justify-between p-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">Add Jurisdiction</h3>
              <button
                onClick={() => setShowAddModal(false)}
                className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                aria-label="Close"
              >
                <X className="w-5 h-5" aria-hidden="true" />
              </button>
            </div>

            {/* Filter tabs */}
            <div className="p-4 border-b border-gray-200">
              <div className="flex gap-2 flex-wrap">
                {(['all', 'zone', 'country', 'state'] as const).map((type) => (
                  <button
                    key={type}
                    onClick={() => setFilterType(type)}
                    className={clsx(
                      'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                      filterType === type
                        ? 'bg-primary-100 text-primary-700'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    )}
                  >
                    {type === 'all' ? 'All' : type === 'zone' ? 'Zones' : type === 'country' ? 'Countries' : 'States'}
                  </button>
                ))}
              </div>
            </div>

            {/* Jurisdiction list */}
            <div className="p-4 overflow-y-auto max-h-[50vh]">
              {Object.keys(groupedAvailable).length === 0 ? (
                <p className="text-center text-gray-500 py-4">
                  No jurisdictions available to add.
                </p>
              ) : (
                <div className="space-y-4">
                  {Object.entries(groupedAvailable).map(([type, jurisdictions]) => (
                    <div key={type}>
                      <h4 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                        {type === 'zone' ? 'Visa Zones' : type === 'country' ? 'Country Rules' : 'State Rules'}
                      </h4>
                      <div className="space-y-2">
                        {jurisdictions.map((j) => (
                          <button
                            key={j.code}
                            onClick={() => {
                              handleAddJurisdiction(j.code);
                              setShowAddModal(false);
                            }}
                            disabled={addJurisdiction.isPending}
                            className="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-left"
                          >
                            <div>
                              <p className="font-medium text-gray-900">{j.name}</p>
                              <p className="text-sm text-gray-500">
                                {j.daysAllowed} days / {j.windowDays} day window
                              </p>
                              {j.description && (
                                <p className="text-xs text-gray-400 mt-1">{j.description}</p>
                              )}
                            </div>
                            <Plus className="w-5 h-5 text-primary-600 flex-shrink-0" aria-hidden="true" />
                          </button>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Individual jurisdiction status card
 */
function JurisdictionCard({
  jurisdiction,
  summary,
  onRemove,
  isRemoving,
}: {
  jurisdiction: JurisdictionRule;
  summary?: JurisdictionSummary;
  onRemove: () => void;
  isRemoving: boolean;
}) {
  const [showDetails, setShowDetails] = useState(false);

  // Map jurisdiction status to the status format used by StatusBadge/DayCounter
  const mapStatus = (status?: string): 'safe' | 'warning' | 'danger' | 'critical' => {
    switch (status) {
      case 'warning': return 'warning';
      case 'danger': return 'danger';
      case 'critical':
      case 'violation': return 'critical';
      default: return 'safe';
    }
  };

  const status = mapStatus(summary?.status);
  const daysUsed = summary?.daysUsed ?? 0;
  const daysRemaining = summary?.daysRemaining ?? jurisdiction.daysAllowed;
  const percentage = summary?.percentage ?? 0;

  // Format date for display
  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  return (
    <div className={clsx(
      'card p-4 border-l-4',
      status === 'safe' && 'border-l-green-500',
      status === 'warning' && 'border-l-yellow-500',
      status === 'danger' && 'border-l-orange-500',
      status === 'critical' && 'border-l-red-500'
    )}>
      {/* Header */}
      <div className="flex items-start justify-between mb-3">
        <div>
          <h4 className="font-semibold text-gray-900">{jurisdiction.name}</h4>
          <p className="text-xs text-gray-500">
            {jurisdiction.type === 'zone' ? 'Visa Zone' : jurisdiction.type === 'country' ? 'Country Rule' : 'State Tax'}
          </p>
        </div>
        <button
          onClick={onRemove}
          disabled={isRemoving}
          className="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
          aria-label={`Remove ${jurisdiction.name}`}
        >
          {isRemoving ? (
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
          ) : (
            <X className="w-4 h-4" aria-hidden="true" />
          )}
        </button>
      </div>

      {/* Day counter (compact) */}
      <div className="flex items-center gap-4 mb-3">
        <DayCounter
          daysUsed={daysUsed}
          daysRemaining={daysRemaining}
          status={status}
          size="sm"
        />
        <div className="flex-1">
          <div className="flex items-center justify-between mb-1">
            <span className="text-sm text-gray-600">{daysUsed} / {jurisdiction.daysAllowed} days</span>
            <StatusBadge status={status} size="sm" />
          </div>
          {/* Progress bar */}
          <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div
              className={clsx(
                'h-full rounded-full transition-all duration-500',
                status === 'safe' && 'bg-green-500',
                status === 'warning' && 'bg-yellow-500',
                status === 'danger' && 'bg-orange-500',
                status === 'critical' && 'bg-red-500'
              )}
              style={{ width: `${Math.min(percentage, 100)}%` }}
            />
          </div>
        </div>
      </div>

      {/* Expandable details */}
      <button
        onClick={() => setShowDetails(!showDetails)}
        className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 transition-colors"
      >
        <ChevronDown
          className={clsx('w-4 h-4 transition-transform', showDetails && 'rotate-180')}
          aria-hidden="true"
        />
        {showDetails ? 'Hide details' : 'Show details'}
      </button>

      {showDetails && summary && (
        <div className="mt-3 pt-3 border-t border-gray-100 space-y-2">
          <div className="flex items-center gap-2 text-xs text-gray-600">
            <Calendar className="w-3.5 h-3.5" aria-hidden="true" />
            <span>
              Window: {formatDate(summary.windowStart)} - {formatDate(summary.windowEnd)}
            </span>
          </div>
          <div className="flex items-center gap-2 text-xs text-gray-600">
            <Clock className="w-3.5 h-3.5" aria-hidden="true" />
            <span>
              Counting: {summary.countingMethod === 'rolling' ? 'Rolling Window' : summary.countingMethod === 'calendar_year' ? 'Calendar Year' : 'Fiscal Year'}
            </span>
          </div>
          {jurisdiction.description && (
            <p className="text-xs text-gray-500 italic">{jurisdiction.description}</p>
          )}
        </div>
      )}
    </div>
  );
}
