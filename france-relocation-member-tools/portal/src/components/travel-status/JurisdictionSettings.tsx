/**
 * JurisdictionSettings Component
 *
 * Settings panel for managing tracked jurisdictions including:
 * - Enable/disable individual jurisdictions
 * - Bulk enable all EU tax jurisdictions
 * - Per-jurisdiction alert thresholds
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  Check,
  ChevronDown,
  ChevronUp,
  Loader2,
  Settings,
  X,
} from 'lucide-react';
import {
  useAddTrackedJurisdiction,
  useBulkUpdateJurisdictions,
  useEUTaxJurisdictions,
  useJurisdictionsByCategory,
  useRemoveTrackedJurisdiction,
  useTrackedJurisdictions,
} from '@/hooks/useApi';
import type { JurisdictionCategory, JurisdictionRule } from '@/types';

interface JurisdictionSettingsProps {
  onClose?: () => void;
  className?: string;
}

export default function JurisdictionSettings({ onClose, className }: JurisdictionSettingsProps) {
  const { data: tracked, isLoading: isLoadingTracked } = useTrackedJurisdictions();
  const { data: euTaxData, isLoading: isLoadingEU } = useEUTaxJurisdictions();
  const { data: allJurisdictions } = useJurisdictionsByCategory();

  const addJurisdiction = useAddTrackedJurisdiction();
  const removeJurisdiction = useRemoveTrackedJurisdiction();
  const bulkUpdate = useBulkUpdateJurisdictions();

  const [activeTab, setActiveTab] = useState<JurisdictionCategory | 'all'>('all');
  const [showBulkConfirm, setShowBulkConfirm] = useState<'enable' | 'disable' | null>(null);

  const trackedCodes = tracked?.map((j) => j.code) || [];
  const euCodes = euTaxData?.codes || [];

  // Check how many EU jurisdictions are currently tracked
  const trackedEUCount = euCodes.filter((code) => trackedCodes.includes(code)).length;
  const allEUTracked = trackedEUCount === euCodes.length && euCodes.length > 0;
  const someEUTracked = trackedEUCount > 0 && !allEUTracked;

  const handleToggleJurisdiction = async (code: string, isTracked: boolean) => {
    if (code === 'schengen') return; // Cannot remove Schengen

    if (isTracked) {
      await removeJurisdiction.mutateAsync(code);
    } else {
      await addJurisdiction.mutateAsync(code);
    }
  };

  const handleBulkEUToggle = async (action: 'enable' | 'disable') => {
    setShowBulkConfirm(null);
    await bulkUpdate.mutateAsync({ action, codes: euCodes });
  };

  // Filter jurisdictions by category
  const getFilteredJurisdictions = (): JurisdictionRule[] => {
    if (!allJurisdictions) return [];

    if (activeTab === 'all') {
      return [
        ...(allJurisdictions.categories?.visa || []),
        ...(allJurisdictions.categories?.tax || []),
        ...(allJurisdictions.categories?.immigration || []),
        ...(allJurisdictions.categories?.custom || []),
      ];
    }

    return allJurisdictions.categories?.[activeTab] || [];
  };

  const filteredJurisdictions = getFilteredJurisdictions();

  if (isLoadingTracked || isLoadingEU) {
    return (
      <div className={clsx('p-6', className)}>
        <div className="flex items-center justify-center gap-3 text-gray-500">
          <Loader2 className="w-5 h-5 animate-spin" aria-hidden="true" />
          <span>Loading jurisdiction settings...</span>
        </div>
      </div>
    );
  }

  return (
    <div className={clsx('bg-white rounded-lg border border-gray-200', className)}>
      {/* Header */}
      <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <div className="flex items-center gap-3">
          <Settings className="w-5 h-5 text-gray-500" aria-hidden="true" />
          <h3 className="text-lg font-semibold text-gray-900">Jurisdiction Settings</h3>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
            aria-label="Close settings"
          >
            <X className="w-5 h-5" aria-hidden="true" />
          </button>
        )}
      </div>

      {/* EU Bulk Toggle */}
      <div className="px-6 py-4 bg-blue-50 border-b border-blue-100">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="text-2xl" aria-hidden="true">
              🇪🇺
            </span>
            <div>
              <h4 className="font-medium text-blue-900">EU Tax Jurisdictions</h4>
              <p className="text-sm text-blue-700">
                {trackedEUCount} of {euCodes.length} enabled
              </p>
            </div>
          </div>

          <div className="flex gap-2">
            {!allEUTracked && (
              <button
                onClick={() => setShowBulkConfirm('enable')}
                disabled={bulkUpdate.isPending}
                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors text-sm"
              >
                {bulkUpdate.isPending ? (
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Check className="w-4 h-4" aria-hidden="true" />
                )}
                Enable All EU
              </button>
            )}
            {someEUTracked && (
              <button
                onClick={() => setShowBulkConfirm('disable')}
                disabled={bulkUpdate.isPending}
                className="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 transition-colors text-sm"
              >
                <X className="w-4 h-4" aria-hidden="true" />
                Disable All
              </button>
            )}
          </div>
        </div>

        {/* Bulk Confirm Dialog */}
        {showBulkConfirm && (
          <div className="mt-4 p-4 bg-white rounded-lg border border-blue-200">
            <div className="flex items-start gap-3">
              <AlertTriangle className="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" aria-hidden="true" />
              <div className="flex-1">
                <p className="font-medium text-gray-900">
                  {showBulkConfirm === 'enable'
                    ? `Enable all ${euCodes.length} EU tax jurisdictions?`
                    : `Disable all EU tax jurisdictions?`}
                </p>
                <p className="text-sm text-gray-600 mt-1">
                  {showBulkConfirm === 'enable'
                    ? 'This will add tax residency tracking for France, Germany, Italy, Spain, Portugal, Netherlands, Ireland, and other EU countries.'
                    : 'This will remove all EU tax jurisdictions from your tracking. Schengen visa tracking will remain active.'}
                </p>
                <div className="flex gap-2 mt-3">
                  <button
                    onClick={() => handleBulkEUToggle(showBulkConfirm)}
                    className={clsx(
                      'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                      showBulkConfirm === 'enable'
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'bg-gray-600 text-white hover:bg-gray-700'
                    )}
                  >
                    {showBulkConfirm === 'enable' ? 'Enable All' : 'Disable All'}
                  </button>
                  <button
                    onClick={() => setShowBulkConfirm(null)}
                    className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Category Tabs */}
      <div className="px-6 py-3 border-b border-gray-200">
        <div className="flex gap-2 overflow-x-auto">
          {(['all', 'visa', 'tax', 'immigration'] as const).map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={clsx(
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap',
                activeTab === tab
                  ? 'bg-primary-100 text-primary-700'
                  : 'text-gray-600 hover:bg-gray-100'
              )}
            >
              {tab === 'all' ? 'All' : tab.charAt(0).toUpperCase() + tab.slice(1)}
            </button>
          ))}
        </div>
      </div>

      {/* Jurisdiction List */}
      <div className="max-h-96 overflow-y-auto">
        {filteredJurisdictions.length === 0 ? (
          <div className="px-6 py-8 text-center text-gray-500">
            No jurisdictions available in this category.
          </div>
        ) : (
          <ul className="divide-y divide-gray-100">
            {filteredJurisdictions.map((jurisdiction) => (
              <JurisdictionRow
                key={jurisdiction.code}
                jurisdiction={jurisdiction}
                isTracked={trackedCodes.includes(jurisdiction.code)}
                isLoading={
                  addJurisdiction.isPending || removeJurisdiction.isPending
                }
                onToggle={() =>
                  handleToggleJurisdiction(
                    jurisdiction.code,
                    trackedCodes.includes(jurisdiction.code)
                  )
                }
              />
            ))}
          </ul>
        )}
      </div>

      {/* Footer */}
      <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 text-sm text-gray-600">
        <p>
          Tracking {trackedCodes.length} jurisdiction{trackedCodes.length !== 1 ? 's' : ''}.
          Changes are saved automatically.
        </p>
      </div>
    </div>
  );
}

interface JurisdictionRowProps {
  jurisdiction: JurisdictionRule;
  isTracked: boolean;
  isLoading: boolean;
  onToggle: () => void;
}

function JurisdictionRow({
  jurisdiction,
  isTracked,
  isLoading,
  onToggle,
}: JurisdictionRowProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const isSchengen = jurisdiction.code === 'schengen';

  const categoryColors: Record<string, string> = {
    visa: 'bg-purple-100 text-purple-700',
    tax: 'bg-blue-100 text-blue-700',
    immigration: 'bg-green-100 text-green-700',
    custom: 'bg-gray-100 text-gray-700',
  };

  return (
    <li className="px-6 py-4 hover:bg-gray-50">
      <div className="flex items-center gap-4">
        {/* Flag/Emoji */}
        <span className="text-2xl flex-shrink-0" aria-hidden="true">
          {jurisdiction.flagEmoji || '🌍'}
        </span>

        {/* Info */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className="font-medium text-gray-900">{jurisdiction.name}</span>
            <span className={clsx('px-2 py-0.5 rounded text-xs', categoryColors[jurisdiction.category])}>
              {jurisdiction.category}
            </span>
          </div>
          <p className="text-sm text-gray-500 truncate">{jurisdiction.description}</p>
        </div>

        {/* Toggle */}
        <div className="flex items-center gap-3">
          <button
            onClick={onToggle}
            disabled={isLoading || isSchengen}
            className={clsx(
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
              isTracked ? 'bg-primary-600' : 'bg-gray-300',
              (isLoading || isSchengen) && 'opacity-50 cursor-not-allowed'
            )}
            role="switch"
            aria-checked={isTracked}
            aria-label={`Toggle ${jurisdiction.name}`}
            title={isSchengen ? 'Schengen tracking cannot be disabled' : undefined}
          >
            <span
              className={clsx(
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                isTracked ? 'translate-x-5' : 'translate-x-0'
              )}
            />
          </button>

          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="p-1 text-gray-400 hover:text-gray-600 rounded"
            aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
          >
            {isExpanded ? (
              <ChevronUp className="w-4 h-4" aria-hidden="true" />
            ) : (
              <ChevronDown className="w-4 h-4" aria-hidden="true" />
            )}
          </button>
        </div>
      </div>

      {/* Expanded Details */}
      {isExpanded && (
        <div className="mt-4 pl-12 space-y-2 text-sm">
          <div className="grid grid-cols-2 gap-4 text-gray-600">
            <div>
              <span className="text-gray-500">Threshold:</span>{' '}
              {jurisdiction.daysAllowed} days
            </div>
            <div>
              <span className="text-gray-500">Window:</span>{' '}
              {jurisdiction.windowDays} days
            </div>
            <div>
              <span className="text-gray-500">Counting:</span>{' '}
              {jurisdiction.countingMethod.replace(/_/g, ' ')}
            </div>
            {jurisdiction.resetMonth && (
              <div>
                <span className="text-gray-500">Resets:</span>{' '}
                {new Date(2024, jurisdiction.resetMonth - 1, jurisdiction.resetDay || 1).toLocaleDateString(
                  undefined,
                  { month: 'short', day: 'numeric' }
                )}
              </div>
            )}
          </div>
          {jurisdiction.notes && (
            <p className="text-gray-600 bg-gray-50 p-3 rounded-lg mt-2">
              {jurisdiction.notes}
            </p>
          )}
        </div>
      )}
    </li>
  );
}
