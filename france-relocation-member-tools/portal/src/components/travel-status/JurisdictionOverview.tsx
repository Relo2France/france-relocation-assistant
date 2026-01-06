/**
 * JurisdictionOverview
 *
 * Shows an overview of all tracked jurisdictions with status cards.
 * Allows users to add/remove jurisdictions they want to track.
 * Supports visa zones, country rules, state rules, and tax residency tracking.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  Calendar,
  ChevronDown,
  Clock,
  Globe,
  Loader2,
  Map,
  Plus,
  Receipt,
  Settings,
  X,
} from 'lucide-react';
import {
  useAddTrackedJurisdiction,
  useJurisdictions,
  useMultiJurisdictionSummary,
  useRemoveTrackedJurisdiction,
  useTrackedJurisdictions,
} from '@/hooks/useApi';
import type {
  JurisdictionCategory,
  JurisdictionRule,
  JurisdictionSummary,
  JurisdictionType,
  MultiFactorRuleConfig,
} from '@/types';
import DayCounter from './DayCounter';
import MultiFactorIndicators from './MultiFactorIndicators';
import StatusBadge from './StatusBadge';
import UKSRTStatus from './UKSRTStatus';
import UKTiesQuestionnaire from './UKTiesQuestionnaire';

interface JurisdictionOverviewProps {
  className?: string;
}

// Category display configuration
const categoryConfig: Record<JurisdictionCategory, { label: string; icon: typeof Globe; color: string }> = {
  visa: { label: 'Visa Rules', icon: Globe, color: 'blue' },
  tax: { label: 'Tax Residency', icon: Receipt, color: 'purple' },
  immigration: { label: 'Immigration', icon: Map, color: 'green' },
  custom: { label: 'Custom Rules', icon: Settings, color: 'gray' },
};

export default function JurisdictionOverview({ className }: JurisdictionOverviewProps) {
  const { data: allJurisdictions, isLoading: loadingAll } = useJurisdictions();
  const { data: trackedJurisdictions, isLoading: loadingTracked } = useTrackedJurisdictions();
  const { data: summaries, isLoading: loadingSummaries } = useMultiJurisdictionSummary();
  const addJurisdiction = useAddTrackedJurisdiction();
  const removeJurisdiction = useRemoveTrackedJurisdiction();

  const [showAddModal, setShowAddModal] = useState(false);
  const [filterType, setFilterType] = useState<JurisdictionType | 'all'>('all');
  const [filterCategory, setFilterCategory] = useState<JurisdictionCategory | 'all'>('all');
  const [viewCategory, setViewCategory] = useState<JurisdictionCategory | 'all'>('all');

  const isLoading = loadingAll || loadingTracked || loadingSummaries;

  // Get tracked jurisdiction codes
  const trackedCodes = new Set(trackedJurisdictions?.map(j => j.code) || []);

  // Filter available jurisdictions (not already tracked)
  const availableJurisdictions = allJurisdictions?.filter(j => !trackedCodes.has(j.code)) || [];

  // Apply type and category filters
  let filteredAvailable = availableJurisdictions;
  if (filterType !== 'all') {
    filteredAvailable = filteredAvailable.filter(j => j.type === filterType);
  }
  if (filterCategory !== 'all') {
    filteredAvailable = filteredAvailable.filter(j => j.category === filterCategory);
  }

  // Group by category then type for display
  const groupedAvailable = filteredAvailable.reduce<Record<string, Record<string, JurisdictionRule[]>>>((acc, j) => {
    const cat = j.category || 'visa';
    const type = j.type;
    if (!acc[cat]) acc[cat] = {};
    if (!acc[cat][type]) acc[cat][type] = [];
    acc[cat][type].push(j);
    return acc;
  }, {});

  // Filter tracked jurisdictions by view category
  const filteredTracked = viewCategory === 'all'
    ? trackedJurisdictions
    : trackedJurisdictions?.filter(j => j.category === viewCategory);

  // Group tracked by category for display
  const trackedByCategory = (filteredTracked || []).reduce<Record<string, JurisdictionRule[]>>((acc, j) => {
    const cat = j.category || 'visa';
    if (!acc[cat]) acc[cat] = [];
    acc[cat].push(j);
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
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-primary-100 rounded-lg">
            <Globe className="w-5 h-5 text-primary-600" aria-hidden="true" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Jurisdiction Tracking</h3>
            <p className="text-sm text-gray-500">
              Track visa rules and tax residency across multiple countries
            </p>
          </div>
        </div>
        <button
          type="button"
          onClick={() => setShowAddModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
        >
          <Plus className="w-4 h-4" aria-hidden="true" />
          Add Jurisdiction
        </button>
      </div>

      {/* Category filter tabs for tracked view */}
      {trackedJurisdictions && trackedJurisdictions.length > 0 && (
        <div className="flex gap-2 flex-wrap border-b border-gray-200 pb-3">
          <button
            type="button"
            onClick={() => setViewCategory('all')}
            className={clsx(
              'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
              viewCategory === 'all'
                ? 'bg-primary-100 text-primary-700'
                : 'text-gray-600 hover:bg-gray-100'
            )}
          >
            All ({trackedJurisdictions.length})
          </button>
          {(Object.keys(categoryConfig) as JurisdictionCategory[]).map((cat) => {
            const count = trackedJurisdictions.filter(j => j.category === cat).length;
            if (count === 0) return null;
            const config = categoryConfig[cat];
            const Icon = config.icon;
            return (
              <button
                type="button"
                key={cat}
                onClick={() => setViewCategory(cat)}
                className={clsx(
                  'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                  viewCategory === cat
                    ? `bg-${config.color}-100 text-${config.color}-700`
                    : 'text-gray-600 hover:bg-gray-100'
                )}
              >
                <Icon className="w-3.5 h-3.5" aria-hidden="true" />
                {config.label} ({count})
              </button>
            );
          })}
        </div>
      )}

      {/* Tracked jurisdictions grouped by category */}
      {filteredTracked && filteredTracked.length > 0 ? (
        <div className="space-y-6">
          {Object.entries(trackedByCategory).map(([category, jurisdictions]) => {
            const config = categoryConfig[category as JurisdictionCategory] || categoryConfig.visa;
            const Icon = config.icon;
            return (
              <div key={category}>
                {viewCategory === 'all' && (
                  <div className="flex items-center gap-2 mb-3">
                    <Icon className={`w-4 h-4 text-${config.color}-600`} aria-hidden="true" />
                    <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                      {config.label}
                    </h4>
                  </div>
                )}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {jurisdictions.map((jurisdiction) => {
                    const summary = summaries?.[jurisdiction.code];
                    return (
                      <JurisdictionCard
                        key={jurisdiction.code}
                        jurisdiction={jurisdiction}
                        summary={summary}
                        onRemove={() => handleRemoveJurisdiction(jurisdiction.code)}
                        isRemoving={removeJurisdiction.isPending}
                        categoryConfig={config}
                      />
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        <div className="card p-8 text-center">
          <Globe className="w-12 h-12 text-gray-300 mx-auto mb-3" aria-hidden="true" />
          <h4 className="text-lg font-medium text-gray-900 mb-2">No Jurisdictions Tracked</h4>
          <p className="text-gray-500 mb-4">
            Add jurisdictions to track your visa rules and tax residency compliance across multiple countries.
          </p>
          <button
            type="button"
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
          <div className="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[85vh] overflow-hidden">
            <div className="flex items-center justify-between p-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">Add Jurisdiction</h3>
              <button
                type="button"
                onClick={() => setShowAddModal(false)}
                className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                aria-label="Close"
              >
                <X className="w-5 h-5" aria-hidden="true" />
              </button>
            </div>

            {/* Category filter tabs */}
            <div className="p-4 border-b border-gray-200 space-y-3">
              <div className="flex gap-2 flex-wrap">
                <span className="text-sm text-gray-500 py-1.5">Category:</span>
                {(['all', 'visa', 'tax', 'immigration', 'custom'] as const).map((cat) => {
                  const config = cat === 'all' ? null : categoryConfig[cat];
                  const Icon = config?.icon || Globe;
                  return (
                    <button
                      type="button"
                      key={cat}
                      onClick={() => setFilterCategory(cat)}
                      className={clsx(
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                        filterCategory === cat
                          ? 'bg-primary-100 text-primary-700'
                          : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                      )}
                    >
                      {cat !== 'all' && <Icon className="w-3.5 h-3.5" aria-hidden="true" />}
                      {cat === 'all' ? 'All' : config?.label}
                    </button>
                  );
                })}
              </div>

              {/* Type filter */}
              <div className="flex gap-2 flex-wrap">
                <span className="text-sm text-gray-500 py-1.5">Type:</span>
                {(['all', 'zone', 'country', 'state'] as const).map((type) => (
                  <button
                    type="button"
                    key={type}
                    onClick={() => setFilterType(type)}
                    className={clsx(
                      'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                      filterType === type
                        ? 'bg-gray-700 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    )}
                  >
                    {type === 'all' ? 'All Types' : type === 'zone' ? 'Zones' : type === 'country' ? 'Countries' : 'States'}
                  </button>
                ))}
              </div>
            </div>

            {/* Jurisdiction list grouped by category */}
            <div className="p-4 overflow-y-auto max-h-[55vh]">
              {Object.keys(groupedAvailable).length === 0 ? (
                <p className="text-center text-gray-500 py-4">
                  No jurisdictions available to add with the current filters.
                </p>
              ) : (
                <div className="space-y-6">
                  {Object.entries(groupedAvailable).map(([category, typeGroups]) => {
                    const catConfig = categoryConfig[category as JurisdictionCategory] || categoryConfig.visa;
                    const CatIcon = catConfig.icon;
                    return (
                      <div key={category}>
                        <div className="flex items-center gap-2 mb-3">
                          <CatIcon className={`w-4 h-4 text-${catConfig.color}-600`} aria-hidden="true" />
                          <h4 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                            {catConfig.label}
                          </h4>
                        </div>
                        <div className="space-y-4 pl-6">
                          {Object.entries(typeGroups).map(([type, jurisdictions]) => (
                            <div key={type}>
                              <h5 className="text-xs text-gray-500 mb-2">
                                {type === 'zone' ? 'Visa Zones' : type === 'country' ? 'Country Rules' : 'State Rules'}
                              </h5>
                              <div className="space-y-2">
                                {jurisdictions.map((j) => (
                                  <button
                                    type="button"
                                    key={j.code}
                                    onClick={(e) => {
                                      e.preventDefault();
                                      handleAddJurisdiction(j.code);
                                      setShowAddModal(false);
                                    }}
                                    disabled={addJurisdiction.isPending}
                                    className="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors text-left group"
                                  >
                                    <div className="flex-1 min-w-0">
                                      <div className="flex items-center gap-2">
                                        {j.flagEmoji && <span className="text-lg">{j.flagEmoji}</span>}
                                        <p className="font-medium text-gray-900">{j.name}</p>
                                      </div>
                                      <p className="text-sm text-gray-500">
                                        {j.countingMethod === 'weighted_multi_year'
                                          ? 'Weighted 3-year calculation'
                                          : j.countingMethod === 'multi_year'
                                            ? `Multi-year (${j.daysAllowed} days)`
                                            : `${j.daysAllowed} days / ${j.windowDays === 365 ? 'calendar year' : j.windowDays === 180 ? '180-day window' : `${j.windowDays} day window`}`}
                                      </p>
                                      {j.description && (
                                        <p className="text-xs text-gray-400 mt-1 truncate">{j.description}</p>
                                      )}
                                    </div>
                                    <Plus className="w-5 h-5 text-primary-600 flex-shrink-0 group-hover:scale-110 transition-transform" aria-hidden="true" />
                                  </button>
                                ))}
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    );
                  })}
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
  categoryConfig: catConfig,
}: {
  jurisdiction: JurisdictionRule;
  summary?: JurisdictionSummary;
  onRemove: () => void;
  isRemoving: boolean;
  categoryConfig?: { label: string; icon: typeof Globe; color: string };
}) {
  const [showDetails, setShowDetails] = useState(false);

  // Map jurisdiction status to the status format used by StatusBadge/DayCounter
  const mapStatus = (status?: string): 'safe' | 'warning' | 'danger' | 'critical' => {
    switch (status) {
      case 'warning': return 'warning';
      case 'danger': return 'danger';
      case 'critical':
      case 'exceeded': return 'critical';
      default: return 'safe';
    }
  };

  const status = mapStatus(summary?.status);
  const daysUsed = summary?.daysUsed ?? 0;
  const daysRemaining = summary?.daysRemaining ?? jurisdiction.daysAllowed;
  const percentage = summary?.percentage ?? 0;
  const isWeighted = jurisdiction.countingMethod === 'weighted_multi_year';
  const isMultiYear = jurisdiction.countingMethod === 'multi_year';
  const isUKSRT = jurisdiction.countingMethod === 'uk_srt';

  // Format date for display
  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  // Get counting method label
  const getCountingLabel = () => {
    switch (summary?.countingMethod || jurisdiction.countingMethod) {
      case 'rolling': return 'Rolling Window';
      case 'calendar_year': return 'Calendar Year';
      case 'fiscal_year': return 'Fiscal Year';
      case 'multi_year': return 'Multi-Year';
      case 'weighted_multi_year': return 'Weighted Multi-Year';
      case 'uk_srt': return 'UK Statutory Residence Test';
      default: return jurisdiction.countingMethod;
    }
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
        <div className="flex items-center gap-2">
          {jurisdiction.flagEmoji && (
            <span className="text-xl" role="img" aria-label={jurisdiction.name}>
              {jurisdiction.flagEmoji}
            </span>
          )}
          <div>
            <h4 className="font-semibold text-gray-900">{jurisdiction.name}</h4>
            <p className="text-xs text-gray-500">
              {catConfig?.label || (jurisdiction.type === 'zone' ? 'Visa Zone' : jurisdiction.type === 'country' ? 'Country Rule' : 'State Tax')}
            </p>
          </div>
        </div>
        <button
          type="button"
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

      {/* Day counter - different display for weighted rules and UK SRT */}
      {isUKSRT && summary?.ukSrtBreakdown ? (
        <div className="mb-3">
          <div className="flex items-center justify-between mb-2">
            <div>
              <span className={clsx(
                'text-sm font-medium',
                summary.ukSrtBreakdown.result === 'resident' ? 'text-red-600' : 'text-green-600'
              )}>
                {summary.ukSrtBreakdown.result === 'resident' ? 'UK Tax Resident' : 'Not UK Tax Resident'}
              </span>
              <p className="text-xs text-gray-500">
                {summary.ukSrtBreakdown.ukDays} days in UK | {summary.ukSrtBreakdown.tieCount} ties
              </p>
            </div>
            <StatusBadge status={status} size="sm" />
          </div>
          <p className="text-xs text-gray-500">
            Tax Year: {summary.ukSrtBreakdown.taxYearLabel}
          </p>
        </div>
      ) : isWeighted && summary?.weightedBreakdown ? (
        <div className="mb-3">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-gray-700">
              {summary.weightedBreakdown.totalWeighted.toFixed(1)} weighted days
            </span>
            <StatusBadge status={status} size="sm" />
          </div>
          {/* Weighted progress bar */}
          <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div
              className={clsx(
                'h-full rounded-full transition-all duration-500',
                status === 'safe' && 'bg-green-500',
                status === 'warning' && 'bg-yellow-500',
                status === 'danger' && 'bg-orange-500',
                status === 'critical' && 'bg-red-500'
              )}
              style={{ width: `${Math.min((summary.weightedBreakdown.totalWeighted / summary.weightedBreakdown.threshold) * 100, 100)}%` }}
            />
          </div>
          <p className="text-xs text-gray-500 mt-1">
            Threshold: {summary.weightedBreakdown.threshold} days
          </p>
        </div>
      ) : (
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
      )}

      {/* Expandable details */}
      <button
        type="button"
        onClick={() => setShowDetails(!showDetails)}
        className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 transition-colors"
      >
        <ChevronDown
          className={clsx('w-4 h-4 transition-transform', showDetails && 'rotate-180')}
          aria-hidden="true"
        />
        {showDetails ? 'Hide details' : 'Show details'}
      </button>

      {showDetails && (
        <div className="mt-3 pt-3 border-t border-gray-100 space-y-2">
          {summary && (
            <>
              <div className="flex items-center gap-2 text-xs text-gray-600">
                <Calendar className="w-3.5 h-3.5" aria-hidden="true" />
                <span>
                  Window: {formatDate(summary.windowStart)} - {formatDate(summary.windowEnd)}
                </span>
              </div>
              <div className="flex items-center gap-2 text-xs text-gray-600">
                <Clock className="w-3.5 h-3.5" aria-hidden="true" />
                <span>Counting: {getCountingLabel()}</span>
              </div>

              {/* Weighted breakdown for US SPT */}
              {isWeighted && summary.weightedBreakdown && (
                <div className="mt-2 p-2 bg-gray-50 rounded-lg space-y-1">
                  <p className="text-xs font-medium text-gray-700">Weighted Days Breakdown:</p>
                  <div className="grid grid-cols-3 gap-2 text-xs">
                    <div className="text-center">
                      <p className="text-gray-500">{summary.weightedBreakdown.currentYear.year}</p>
                      <p className="font-medium">{summary.weightedBreakdown.currentYear.days} × 1.0</p>
                      <p className="text-gray-600">= {summary.weightedBreakdown.currentYear.weighted.toFixed(1)}</p>
                    </div>
                    <div className="text-center">
                      <p className="text-gray-500">{summary.weightedBreakdown.priorYear.year}</p>
                      <p className="font-medium">{summary.weightedBreakdown.priorYear.days} × ⅓</p>
                      <p className="text-gray-600">= {summary.weightedBreakdown.priorYear.weighted.toFixed(1)}</p>
                    </div>
                    <div className="text-center">
                      <p className="text-gray-500">{summary.weightedBreakdown.secondPriorYear.year}</p>
                      <p className="font-medium">{summary.weightedBreakdown.secondPriorYear.days} × ⅙</p>
                      <p className="text-gray-600">= {summary.weightedBreakdown.secondPriorYear.weighted.toFixed(1)}</p>
                    </div>
                  </div>
                  {!summary.weightedBreakdown.meetsCurrentYearMinimum && (
                    <p className="text-xs text-amber-600 flex items-center gap-1 mt-1">
                      <AlertTriangle className="w-3 h-3" aria-hidden="true" />
                      Needs 31+ days in current year to trigger
                    </p>
                  )}
                </div>
              )}

              {/* Multi-year breakdown for Ireland */}
              {isMultiYear && summary.multiYearBreakdown && (
                <div className="mt-2 p-2 bg-gray-50 rounded-lg space-y-1">
                  <p className="text-xs font-medium text-gray-700">Multi-Year Breakdown:</p>
                  <div className="grid grid-cols-2 gap-2 text-xs">
                    <div>
                      <p className="text-gray-500">{summary.multiYearBreakdown.currentYear.year}</p>
                      <p className="font-medium">{summary.multiYearBreakdown.currentYear.days} days</p>
                    </div>
                    <div>
                      <p className="text-gray-500">{summary.multiYearBreakdown.priorYear.year}</p>
                      <p className="font-medium">{summary.multiYearBreakdown.priorYear.days} days</p>
                    </div>
                  </div>
                  <p className="text-xs text-gray-600 mt-1">
                    Combined: {summary.multiYearBreakdown.combinedDays} days
                    {summary.multiYearBreakdown.meetsSecondary && (
                      <span className="text-red-600 ml-1">(exceeds 280)</span>
                    )}
                  </p>
                </div>
              )}

              {/* UK SRT breakdown */}
              {isUKSRT && summary.ukSrtBreakdown && (
                <div className="mt-3 space-y-3">
                  <UKSRTStatus breakdown={summary.ukSrtBreakdown} />
                  <details className="text-sm">
                    <summary className="cursor-pointer text-gray-600 hover:text-gray-800 font-medium">
                      Edit UK Ties
                    </summary>
                    <div className="mt-3 pt-3 border-t border-gray-200">
                      <UKTiesQuestionnaire
                        taxYear={summary.ukSrtBreakdown.taxYear}
                        compact
                      />
                    </div>
                  </details>
                </div>
              )}
            </>
          )}

          {/* Multi-factor indicators for DE, IT, NL tax rules */}
          {jurisdiction.ruleConfig && 'multi_factor' in jurisdiction.ruleConfig && (
            <MultiFactorIndicators
              jurisdictionCode={jurisdiction.code}
              ruleConfig={jurisdiction.ruleConfig as MultiFactorRuleConfig}
              compact
              className="mt-3"
            />
          )}

          {jurisdiction.description && (
            <p className="text-xs text-gray-500 italic">{jurisdiction.description}</p>
          )}
        </div>
      )}
    </div>
  );
}
