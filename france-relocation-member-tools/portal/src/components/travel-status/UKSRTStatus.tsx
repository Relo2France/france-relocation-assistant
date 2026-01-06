/**
 * UKSRTStatus Component
 *
 * Displays the UK Statutory Residence Test result with full breakdown:
 * - Automatic Overseas Tests
 * - Automatic UK Tests
 * - Sufficient Ties Test
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Briefcase,
  Calendar,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Globe,
  HelpCircle,
  Home,
  MapPin,
  Users,
  XCircle,
} from 'lucide-react';
import type {
  UKSRTAutomaticTestResult,
  UKSRTBreakdown,
  UKSRTSufficientTiesResult,
} from '@/types';

interface UKSRTStatusProps {
  breakdown: UKSRTBreakdown;
  className?: string;
}

export default function UKSRTStatus({ breakdown, className }: UKSRTStatusProps) {
  const [expandedSection, setExpandedSection] = useState<string | null>(null);

  const isResident = breakdown.result === 'resident';

  // Get the appropriate status colors
  const statusColors = isResident
    ? {
        bg: 'bg-red-50',
        border: 'border-red-200',
        text: 'text-red-700',
        badge: 'bg-red-100 text-red-700',
      }
    : {
        bg: 'bg-green-50',
        border: 'border-green-200',
        text: 'text-green-700',
        badge: 'bg-green-100 text-green-700',
      };

  const ResultIcon = isResident ? XCircle : CheckCircle2;

  // Get reason label
  const getReasonLabel = () => {
    switch (breakdown.resultReason) {
      case 'automatic_overseas':
        return 'Automatic Overseas Test';
      case 'automatic_uk':
        return 'Automatic UK Test';
      case 'sufficient_ties':
        return 'Sufficient Ties Test';
      default:
        return 'Unknown';
    }
  };

  return (
    <div className={clsx('rounded-lg border', statusColors.border, statusColors.bg, className)}>
      {/* Main Result Header */}
      <div className="p-4 border-b border-current/10">
        <div className="flex items-start gap-4">
          <ResultIcon
            className={clsx('w-8 h-8 flex-shrink-0', statusColors.text)}
            aria-hidden="true"
          />
          <div className="flex-1">
            <div className="flex items-center gap-3 flex-wrap">
              <h3 className={clsx('text-lg font-semibold', statusColors.text)}>
                {isResident ? 'UK Tax Resident' : 'Not UK Tax Resident'}
              </h3>
              <span className={clsx('px-2 py-0.5 rounded text-xs font-medium', statusColors.badge)}>
                Tax Year {breakdown.taxYearLabel}
              </span>
            </div>
            <p className="text-sm text-gray-600 mt-1">
              Determined by: <span className="font-medium">{getReasonLabel()}</span>
            </p>
            {breakdown.testDescription && (
              <p className="text-sm text-gray-600 mt-1">{breakdown.testDescription}</p>
            )}
          </div>
        </div>

        {/* Summary Stats */}
        <div className="mt-4 grid grid-cols-3 gap-4 text-center">
          <div className="bg-white/50 rounded-lg p-2">
            <div className="text-2xl font-bold text-gray-900">{breakdown.ukDays}</div>
            <div className="text-xs text-gray-500">Days in UK</div>
          </div>
          <div className="bg-white/50 rounded-lg p-2">
            <div className="text-2xl font-bold text-gray-900">{breakdown.tieCount}</div>
            <div className="text-xs text-gray-500">UK Ties</div>
          </div>
          <div className="bg-white/50 rounded-lg p-2">
            <div className="text-2xl font-bold text-gray-900">
              {breakdown.wasResidentPrior ? 'Yes' : 'No'}
            </div>
            <div className="text-xs text-gray-500">Prior Resident</div>
          </div>
        </div>
      </div>

      {/* Test Breakdown Sections */}
      <div className="divide-y divide-current/10">
        {/* Automatic Overseas Test */}
        <TestSection
          title="Automatic Overseas Test"
          subtitle="If ANY condition is met, you are automatically non-resident"
          result={breakdown.automaticOverseas}
          isExpanded={expandedSection === 'overseas'}
          onToggle={() =>
            setExpandedSection(expandedSection === 'overseas' ? null : 'overseas')
          }
          isActive={breakdown.resultReason === 'automatic_overseas'}
        />

        {/* Automatic UK Test */}
        <TestSection
          title="Automatic UK Test"
          subtitle="If ANY condition is met, you are automatically UK resident"
          result={breakdown.automaticUK}
          isExpanded={expandedSection === 'uk'}
          onToggle={() => setExpandedSection(expandedSection === 'uk' ? null : 'uk')}
          isActive={breakdown.resultReason === 'automatic_uk'}
        />

        {/* Sufficient Ties Test */}
        {breakdown.sufficientTies && (
          <SufficientTiesSection
            result={breakdown.sufficientTies}
            isExpanded={expandedSection === 'ties'}
            onToggle={() => setExpandedSection(expandedSection === 'ties' ? null : 'ties')}
            isActive={breakdown.resultReason === 'sufficient_ties'}
          />
        )}
      </div>
    </div>
  );
}

interface TestSectionProps {
  title: string;
  subtitle: string;
  result: UKSRTAutomaticTestResult;
  isExpanded: boolean;
  onToggle: () => void;
  isActive: boolean;
}

function TestSection({
  title,
  subtitle,
  result,
  isExpanded,
  onToggle,
  isActive,
}: TestSectionProps) {
  const StatusIcon = result.passed ? CheckCircle2 : XCircle;

  return (
    <div className={clsx(isActive && 'bg-white/30')}>
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between px-4 py-3 hover:bg-white/20 transition-colors"
      >
        <div className="flex items-center gap-3">
          <StatusIcon
            className={clsx(
              'w-5 h-5',
              result.passed ? 'text-green-600' : 'text-gray-400'
            )}
            aria-hidden="true"
          />
          <div className="text-left">
            <div className="flex items-center gap-2">
              <span className="font-medium text-gray-900">{title}</span>
              {isActive && (
                <span className="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs rounded">
                  Determining Factor
                </span>
              )}
            </div>
            <p className="text-xs text-gray-500">{subtitle}</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <span
            className={clsx(
              'text-sm font-medium',
              result.passed ? 'text-green-600' : 'text-gray-500'
            )}
          >
            {result.passed ? 'Passed' : 'Not Met'}
          </span>
          {isExpanded ? (
            <ChevronUp className="w-5 h-5 text-gray-400" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-400" />
          )}
        </div>
      </button>

      {isExpanded && (
        <div className="px-4 pb-4 space-y-2">
          {result.tests.map((test) => (
            <div
              key={test.id}
              className={clsx(
                'flex items-start gap-3 p-3 rounded-lg',
                test.passed ? 'bg-green-50' : 'bg-white/50'
              )}
            >
              {test.passed ? (
                <CheckCircle2 className="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" />
              ) : (
                <XCircle className="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />
              )}
              <div>
                <div className="flex items-center gap-2">
                  <span
                    className={clsx(
                      'font-medium text-sm',
                      test.passed ? 'text-green-700' : 'text-gray-700'
                    )}
                  >
                    {test.label}
                  </span>
                  {test.requiresInput && (
                    <span className="text-xs text-amber-600 flex items-center gap-1">
                      <HelpCircle className="w-3 h-3" />
                      Requires input
                    </span>
                  )}
                </div>
                <p className="text-xs text-gray-600 mt-1">{test.description}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

interface SufficientTiesSectionProps {
  result: UKSRTSufficientTiesResult;
  isExpanded: boolean;
  onToggle: () => void;
  isActive: boolean;
}

function SufficientTiesSection({
  result,
  isExpanded,
  onToggle,
  isActive,
}: SufficientTiesSectionProps) {
  const tieIcons: Record<string, typeof Users> = {
    family: Users,
    accommodation: Home,
    work: Briefcase,
    ninety_day: Calendar,
    country: Globe,
  };

  const tieLabels: Record<string, string> = {
    family: 'Family Tie',
    accommodation: 'Accommodation Tie',
    work: 'Work Tie',
    ninety_day: '90-Day Tie',
    country: 'Country Tie',
  };

  return (
    <div className={clsx(isActive && 'bg-white/30')}>
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between px-4 py-3 hover:bg-white/20 transition-colors"
      >
        <div className="flex items-center gap-3">
          <MapPin
            className={clsx(
              'w-5 h-5',
              result.resident ? 'text-red-600' : 'text-green-600'
            )}
            aria-hidden="true"
          />
          <div className="text-left">
            <div className="flex items-center gap-2">
              <span className="font-medium text-gray-900">Sufficient Ties Test</span>
              {isActive && (
                <span className="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs rounded">
                  Determining Factor
                </span>
              )}
            </div>
            <p className="text-xs text-gray-500">
              Compares UK days against ties to determine residency
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <span
            className={clsx(
              'text-sm font-medium',
              result.resident ? 'text-red-600' : 'text-green-600'
            )}
          >
            {result.resident ? 'Resident' : 'Not Resident'}
          </span>
          {isExpanded ? (
            <ChevronUp className="w-5 h-5 text-gray-400" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-400" />
          )}
        </div>
      </button>

      {isExpanded && (
        <div className="px-4 pb-4 space-y-4">
          {/* Day vs Threshold Comparison */}
          <div className="bg-white/50 rounded-lg p-4">
            <div className="flex items-center justify-between mb-2">
              <span className="text-sm text-gray-600">Days in UK</span>
              <span className="text-sm text-gray-600">Threshold</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-2xl font-bold text-gray-900">{result.daysInUK}</span>
              <div className="flex-1 mx-4 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                  className={clsx(
                    'h-full transition-all',
                    result.resident ? 'bg-red-500' : 'bg-green-500'
                  )}
                  style={{
                    width: `${Math.min((result.daysInUK / result.dayThreshold) * 100, 100)}%`,
                  }}
                />
              </div>
              <span className="text-2xl font-bold text-gray-900">{result.dayThreshold}</span>
            </div>
            <p className="text-xs text-gray-500 text-center mt-2">
              {result.wasResidentPrior
                ? 'Threshold based on prior UK residency (stricter)'
                : 'Threshold based on no prior UK residency (more lenient)'}
            </p>
          </div>

          {/* Tie Breakdown */}
          <div>
            <h4 className="text-sm font-medium text-gray-700 mb-2">
              Connection Ties ({result.tieCount} active)
            </h4>
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
              {Object.entries(result.tieBreakdown).map(([key, isActive]) => {
                const Icon = tieIcons[key] || Users;
                return (
                  <div
                    key={key}
                    className={clsx(
                      'flex items-center gap-2 p-2 rounded',
                      isActive ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500'
                    )}
                  >
                    <Icon className="w-4 h-4" aria-hidden="true" />
                    <span className="text-xs font-medium">{tieLabels[key]}</span>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Explanation */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <p className="text-sm text-blue-800">{result.description}</p>
          </div>

          {/* Reference Table */}
          <details className="text-sm">
            <summary className="cursor-pointer text-gray-600 hover:text-gray-800">
              View day thresholds table
            </summary>
            <div className="mt-2 overflow-x-auto">
              <table className="w-full text-xs">
                <thead>
                  <tr className="bg-gray-100">
                    <th className="px-2 py-1 text-left"># Ties</th>
                    <th className="px-2 py-1 text-center">Was Resident</th>
                    <th className="px-2 py-1 text-center">Not Resident</th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    { ties: '0', resident: '183+', notResident: '183+' },
                    { ties: '1', resident: '91+', notResident: '121+' },
                    { ties: '2', resident: '61+', notResident: '91+' },
                    { ties: '3', resident: '46+', notResident: '46+' },
                    { ties: '4+', resident: '16+', notResident: '16+' },
                  ].map((row) => (
                    <tr
                      key={row.ties}
                      className={clsx(
                        result.tieCount.toString() === row.ties ||
                          (result.tieCount >= 4 && row.ties === '4+')
                          ? 'bg-primary-50 font-medium'
                          : ''
                      )}
                    >
                      <td className="px-2 py-1 border-t">{row.ties}</td>
                      <td className="px-2 py-1 border-t text-center">{row.resident}</td>
                      <td className="px-2 py-1 border-t text-center">{row.notResident}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </details>
        </div>
      )}
    </div>
  );
}
