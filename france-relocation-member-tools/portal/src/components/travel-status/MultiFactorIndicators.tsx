/**
 * MultiFactorIndicators Component
 *
 * Displays and allows editing of multi-factor residency indicators for
 * jurisdictions like Germany, Italy, and Netherlands that have complex
 * residency tests beyond just day counting.
 */

import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  CheckCircle2,
  XCircle,
  HelpCircle,
  ChevronDown,
  ChevronUp,
  Save,
  Loader2,
} from 'lucide-react';
import { useUserFactors, useUpdateUserFactors } from '@/hooks/useApi';
import type { ResidencyFactor, MultiFactorRuleConfig } from '@/types';

interface MultiFactorIndicatorsProps {
  jurisdictionCode: string;
  ruleConfig?: MultiFactorRuleConfig;
  compact?: boolean;
  className?: string;
}

export default function MultiFactorIndicators({
  jurisdictionCode,
  ruleConfig,
  compact = false,
  className,
}: MultiFactorIndicatorsProps) {
  const { data: factorData, isLoading: isLoadingFactors } = useUserFactors(jurisdictionCode);
  const updateFactors = useUpdateUserFactors();

  const [localResponses, setLocalResponses] = useState<Record<string, boolean>>({});
  const [isExpanded, setIsExpanded] = useState(!compact);
  const [hasChanges, setHasChanges] = useState(false);

  // Sync local state with server data
  useEffect(() => {
    if (factorData?.responses) {
      setLocalResponses(factorData.responses);
      setHasChanges(false);
    }
  }, [factorData?.responses]);

  // Check if rule has multi-factor config
  if (!ruleConfig?.multi_factor) {
    return null;
  }

  const factors = ruleConfig.factors || factorData?.factors || [];
  const factorLogic = ruleConfig.factor_logic || factorData?.factorLogic || 'any';

  if (factors.length === 0) {
    return null;
  }

  const handleToggleFactor = (factorId: string) => {
    const newResponses = {
      ...localResponses,
      [factorId]: !localResponses[factorId],
    };
    setLocalResponses(newResponses);
    setHasChanges(true);
  };

  const handleSave = async () => {
    await updateFactors.mutateAsync({
      code: jurisdictionCode,
      responses: localResponses,
    });
    setHasChanges(false);
  };

  // Calculate how many factors are active
  const activeFactorCount = Object.values(localResponses).filter(Boolean).length;
  const totalFactors = factors.length;

  // Determine overall status based on factor logic
  const getOverallStatus = (): 'ok' | 'warning' | 'danger' => {
    if (factorLogic === 'any') {
      // ANY logic: any true factor triggers residency risk
      return activeFactorCount > 0 ? 'danger' : 'ok';
    } else if (factorLogic === 'all') {
      // ALL logic: all must be true for residency
      return activeFactorCount === totalFactors ? 'danger' : 'ok';
    } else {
      // WEIGHTED: based on percentage
      const percentage = (activeFactorCount / totalFactors) * 100;
      if (percentage >= 66) return 'danger';
      if (percentage >= 33) return 'warning';
      return 'ok';
    }
  };

  const overallStatus = getOverallStatus();

  const statusColors = {
    ok: 'text-green-600 bg-green-50 border-green-200',
    warning: 'text-amber-600 bg-amber-50 border-amber-200',
    danger: 'text-red-600 bg-red-50 border-red-200',
  };

  const StatusIcon = {
    ok: CheckCircle2,
    warning: AlertTriangle,
    danger: XCircle,
  }[overallStatus];

  if (isLoadingFactors) {
    return (
      <div className={clsx('animate-pulse', className)}>
        <div className="h-8 bg-gray-200 rounded w-3/4" />
      </div>
    );
  }

  // Compact view - just show summary
  if (compact && !isExpanded) {
    return (
      <button
        onClick={() => setIsExpanded(true)}
        className={clsx(
          'flex items-center gap-2 text-sm px-3 py-2 rounded-lg border transition-colors w-full',
          statusColors[overallStatus],
          className
        )}
      >
        <StatusIcon className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
        <span className="flex-1 text-left">
          {activeFactorCount} of {totalFactors} factors active
        </span>
        <ChevronDown className="w-4 h-4" aria-hidden="true" />
      </button>
    );
  }

  return (
    <div className={clsx('rounded-lg border', statusColors[overallStatus], className)}>
      {/* Header */}
      <div
        className={clsx(
          'flex items-center gap-3 px-4 py-3',
          compact && 'cursor-pointer'
        )}
        onClick={compact ? () => setIsExpanded(!isExpanded) : undefined}
      >
        <StatusIcon className="w-5 h-5 flex-shrink-0" aria-hidden="true" />
        <div className="flex-1">
          <h4 className="font-medium">Residency Factors</h4>
          <p className="text-sm opacity-75">
            {factorLogic === 'any'
              ? 'Any factor may trigger tax residency'
              : factorLogic === 'all'
              ? 'All factors required for residency'
              : 'Weighted assessment of factors'}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">
            {activeFactorCount}/{totalFactors}
          </span>
          {compact && (
            isExpanded ? (
              <ChevronUp className="w-4 h-4" aria-hidden="true" />
            ) : (
              <ChevronDown className="w-4 h-4" aria-hidden="true" />
            )
          )}
        </div>
      </div>

      {/* Factor List */}
      {isExpanded && (
        <div className="border-t border-current/20 px-4 py-3 space-y-3">
          {factors.map((factor: ResidencyFactor) => (
            <FactorItem
              key={factor.id}
              factor={factor}
              isActive={localResponses[factor.id] || false}
              onToggle={() => handleToggleFactor(factor.id)}
            />
          ))}

          {/* Save Button */}
          {hasChanges && (
            <div className="flex justify-end pt-2 border-t border-current/10">
              <button
                onClick={handleSave}
                disabled={updateFactors.isPending}
                className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors"
              >
                {updateFactors.isPending ? (
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Save className="w-4 h-4" aria-hidden="true" />
                )}
                Save Changes
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

interface FactorItemProps {
  factor: ResidencyFactor;
  isActive: boolean;
  onToggle: () => void;
}

function FactorItem({ factor, isActive, onToggle }: FactorItemProps) {
  const [showHelp, setShowHelp] = useState(false);

  return (
    <div className="space-y-1">
      <div className="flex items-start gap-3">
        {/* Toggle */}
        <button
          onClick={onToggle}
          className={clsx(
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
            isActive ? 'bg-red-500' : 'bg-gray-300'
          )}
          role="switch"
          aria-checked={isActive}
          aria-label={factor.label}
        >
          <span
            className={clsx(
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
              isActive ? 'translate-x-5' : 'translate-x-0'
            )}
          />
        </button>

        {/* Label and help */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className={clsx('font-medium', isActive && 'text-red-700')}>
              {factor.label}
            </span>
            {factor.description && (
              <button
                onClick={() => setShowHelp(!showHelp)}
                className="text-gray-400 hover:text-gray-600"
                aria-label="Show help"
              >
                <HelpCircle className="w-4 h-4" aria-hidden="true" />
              </button>
            )}
          </div>

          {/* Help text */}
          {showHelp && factor.description && (
            <p className="mt-1 text-sm text-gray-600 bg-white/50 rounded p-2">
              {factor.description}
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
