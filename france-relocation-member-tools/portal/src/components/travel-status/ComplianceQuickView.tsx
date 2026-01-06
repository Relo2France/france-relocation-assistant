/**
 * ComplianceQuickView
 *
 * Compact stacked card view showing compliance status across all tracked jurisdictions.
 * Shows alerts for jurisdictions approaching limits and provides quick access to details.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertTriangle,
  CheckCircle,
  ChevronRight,
  Globe,
  Receipt,
  Shield,
  XCircle,
} from 'lucide-react';
import { useComplianceOverview } from '@/hooks/useApi';
import type { ComplianceAlert, JurisdictionSummary } from '@/types';

interface ComplianceQuickViewProps {
  className?: string;
  onSelectJurisdiction?: (code: string) => void;
  compact?: boolean;
}

export default function ComplianceQuickView({
  className,
  onSelectJurisdiction,
  compact = false,
}: ComplianceQuickViewProps) {
  const { data: overview, isLoading, error } = useComplianceOverview();
  const [expandedAlerts, setExpandedAlerts] = useState(false);

  if (isLoading) {
    return (
      <div className={clsx('card p-4', className)}>
        <div className="flex items-center gap-3 animate-pulse">
          <div className="w-10 h-10 bg-gray-200 rounded-lg" />
          <div className="flex-1">
            <div className="h-4 bg-gray-200 rounded w-1/3 mb-2" />
            <div className="h-3 bg-gray-200 rounded w-1/2" />
          </div>
        </div>
      </div>
    );
  }

  if (error || !overview) {
    return (
      <div className={clsx('card p-4', className)}>
        <div className="flex items-center gap-3 text-gray-500">
          <Shield className="w-5 h-5" aria-hidden="true" />
          <span className="text-sm">Unable to load compliance status</span>
        </div>
      </div>
    );
  }

  const { jurisdictions, alerts } = overview;
  const hasAlerts = alerts.length > 0;
  const criticalCount = alerts.filter(a => a.type === 'exceeded' || a.type === 'danger').length;
  const warningCount = alerts.filter(a => a.type === 'warning').length;

  // Overall status
  const overallStatus = criticalCount > 0 ? 'danger' : warningCount > 0 ? 'warning' : 'safe';

  if (compact) {
    return (
      <div className={clsx('card p-3', className)}>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className={clsx(
              'p-1.5 rounded-lg',
              overallStatus === 'safe' && 'bg-green-100',
              overallStatus === 'warning' && 'bg-yellow-100',
              overallStatus === 'danger' && 'bg-red-100'
            )}>
              {overallStatus === 'safe' ? (
                <CheckCircle className="w-4 h-4 text-green-600" aria-hidden="true" />
              ) : overallStatus === 'warning' ? (
                <AlertTriangle className="w-4 h-4 text-yellow-600" aria-hidden="true" />
              ) : (
                <XCircle className="w-4 h-4 text-red-600" aria-hidden="true" />
              )}
            </div>
            <div>
              <p className="text-sm font-medium text-gray-900">
                {jurisdictions.length} Jurisdictions Tracked
              </p>
              {hasAlerts && (
                <p className={clsx(
                  'text-xs',
                  criticalCount > 0 ? 'text-red-600' : 'text-yellow-600'
                )}>
                  {criticalCount > 0 ? `${criticalCount} critical` : `${warningCount} warning`}
                </p>
              )}
            </div>
          </div>
          {onSelectJurisdiction && (
            <button
              type="button"
              onClick={() => onSelectJurisdiction('overview')}
              className="p-1 text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="View details"
            >
              <ChevronRight className="w-5 h-5" aria-hidden="true" />
            </button>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className={clsx('card', className)}>
      {/* Header */}
      <div className="p-4 border-b border-gray-100">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className={clsx(
              'p-2 rounded-lg',
              overallStatus === 'safe' && 'bg-green-100',
              overallStatus === 'warning' && 'bg-yellow-100',
              overallStatus === 'danger' && 'bg-red-100'
            )}>
              <Shield className={clsx(
                'w-5 h-5',
                overallStatus === 'safe' && 'text-green-600',
                overallStatus === 'warning' && 'text-yellow-600',
                overallStatus === 'danger' && 'text-red-600'
              )} aria-hidden="true" />
            </div>
            <div>
              <h3 className="font-semibold text-gray-900">Compliance Overview</h3>
              <p className="text-sm text-gray-500">
                Tracking {jurisdictions.length} jurisdiction{jurisdictions.length !== 1 ? 's' : ''}
              </p>
            </div>
          </div>
          <div className={clsx(
            'px-3 py-1 rounded-full text-sm font-medium',
            overallStatus === 'safe' && 'bg-green-100 text-green-700',
            overallStatus === 'warning' && 'bg-yellow-100 text-yellow-700',
            overallStatus === 'danger' && 'bg-red-100 text-red-700'
          )}>
            {overallStatus === 'safe' ? 'All Clear' : overallStatus === 'warning' ? 'Attention Needed' : 'Action Required'}
          </div>
        </div>
      </div>

      {/* Alerts section */}
      {hasAlerts && (
        <div className="p-4 bg-amber-50 border-b border-amber-100">
          <button
            type="button"
            onClick={() => setExpandedAlerts(!expandedAlerts)}
            className="w-full flex items-center justify-between text-left"
          >
            <div className="flex items-center gap-2">
              <AlertTriangle className="w-4 h-4 text-amber-600" aria-hidden="true" />
              <span className="text-sm font-medium text-amber-800">
                {alerts.length} Alert{alerts.length !== 1 ? 's' : ''}
              </span>
            </div>
            <ChevronRight className={clsx(
              'w-4 h-4 text-amber-600 transition-transform',
              expandedAlerts && 'rotate-90'
            )} aria-hidden="true" />
          </button>
          {expandedAlerts && (
            <div className="mt-3 space-y-2">
              {alerts.map((alert, idx) => (
                <AlertItem
                  key={`${alert.jurisdictionCode}-${idx}`}
                  alert={alert}
                  onClick={() => onSelectJurisdiction?.(alert.jurisdictionCode)}
                />
              ))}
            </div>
          )}
        </div>
      )}

      {/* Jurisdictions list */}
      <div className="divide-y divide-gray-100">
        {jurisdictions.length === 0 ? (
          <div className="p-8 text-center">
            <Globe className="w-10 h-10 text-gray-300 mx-auto mb-2" aria-hidden="true" />
            <p className="text-gray-500 text-sm">No jurisdictions tracked yet</p>
          </div>
        ) : (
          jurisdictions.map((summary) => (
            <JurisdictionRow
              key={summary.jurisdictionCode}
              summary={summary}
              onClick={() => onSelectJurisdiction?.(summary.jurisdictionCode)}
            />
          ))
        )}
      </div>
    </div>
  );
}

/**
 * Alert item row
 */
function AlertItem({
  alert,
  onClick,
}: {
  alert: ComplianceAlert;
  onClick?: () => void;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={clsx(
        'w-full flex items-center gap-3 p-2 rounded-lg text-left transition-colors',
        alert.type === 'exceeded' && 'bg-red-100 hover:bg-red-200',
        alert.type === 'danger' && 'bg-orange-100 hover:bg-orange-200',
        alert.type === 'warning' && 'bg-yellow-100 hover:bg-yellow-200'
      )}
    >
      {alert.type === 'exceeded' || alert.type === 'danger' ? (
        <XCircle className={clsx(
          'w-4 h-4 flex-shrink-0',
          alert.type === 'exceeded' ? 'text-red-600' : 'text-orange-600'
        )} aria-hidden="true" />
      ) : (
        <AlertTriangle className="w-4 h-4 text-yellow-600 flex-shrink-0" aria-hidden="true" />
      )}
      <div className="flex-1 min-w-0">
        <p className={clsx(
          'text-sm font-medium truncate',
          alert.type === 'exceeded' ? 'text-red-800' : alert.type === 'danger' ? 'text-orange-800' : 'text-yellow-800'
        )}>
          {alert.jurisdictionName}
        </p>
        <p className={clsx(
          'text-xs truncate',
          alert.type === 'exceeded' ? 'text-red-600' : alert.type === 'danger' ? 'text-orange-600' : 'text-yellow-600'
        )}>
          {alert.message}
        </p>
      </div>
      <span className={clsx(
        'text-sm font-bold',
        alert.type === 'exceeded' ? 'text-red-700' : alert.type === 'danger' ? 'text-orange-700' : 'text-yellow-700'
      )}>
        {alert.percentage}%
      </span>
    </button>
  );
}

/**
 * Jurisdiction row with mini progress bar
 */
function JurisdictionRow({
  summary,
  onClick,
}: {
  summary: JurisdictionSummary;
  onClick?: () => void;
}) {
  const status = summary.status === 'exceeded' || summary.status === 'critical'
    ? 'danger'
    : summary.status === 'danger'
      ? 'danger'
      : summary.status === 'warning'
        ? 'warning'
        : 'safe';

  const isTax = summary.category === 'tax';
  const Icon = isTax ? Receipt : Globe;

  return (
    <button
      type="button"
      onClick={onClick}
      className="w-full flex items-center gap-3 p-4 hover:bg-gray-50 transition-colors text-left"
    >
      {/* Flag or icon */}
      <div className="flex-shrink-0">
        {summary.flagEmoji ? (
          <span className="text-xl" role="img" aria-label={summary.jurisdictionCode}>
            {summary.flagEmoji}
          </span>
        ) : (
          <Icon className="w-5 h-5 text-gray-400" aria-hidden="true" />
        )}
      </div>

      {/* Name and details */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <p className="font-medium text-gray-900 truncate">
            {summary.rule?.name || summary.jurisdictionCode}
          </p>
          {isTax && (
            <span className="text-xs px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded">
              Tax
            </span>
          )}
        </div>
        {/* Mini progress bar */}
        <div className="flex items-center gap-2 mt-1">
          <div className="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              className={clsx(
                'h-full rounded-full transition-all',
                status === 'safe' && 'bg-green-500',
                status === 'warning' && 'bg-yellow-500',
                status === 'danger' && 'bg-red-500'
              )}
              style={{ width: `${Math.min(summary.percentage, 100)}%` }}
            />
          </div>
          <span className="text-xs text-gray-500 tabular-nums">
            {summary.daysUsed}/{summary.daysAllowed}
          </span>
        </div>
      </div>

      {/* Status indicator */}
      <div className={clsx(
        'flex items-center gap-1 text-sm font-medium',
        status === 'safe' && 'text-green-600',
        status === 'warning' && 'text-yellow-600',
        status === 'danger' && 'text-red-600'
      )}>
        {status === 'safe' ? (
          <CheckCircle className="w-4 h-4" aria-hidden="true" />
        ) : status === 'warning' ? (
          <AlertTriangle className="w-4 h-4" aria-hidden="true" />
        ) : (
          <XCircle className="w-4 h-4" aria-hidden="true" />
        )}
        <span>{summary.daysRemaining}</span>
      </div>

      <ChevronRight className="w-4 h-4 text-gray-400 flex-shrink-0" aria-hidden="true" />
    </button>
  );
}
