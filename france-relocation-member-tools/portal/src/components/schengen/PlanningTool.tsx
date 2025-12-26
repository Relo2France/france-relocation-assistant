/**
 * PlanningTool
 *
 * "What if" trip planning calculator for Schengen tracker.
 * Allows users to simulate a future trip and see if it would
 * violate the 90/180 day rule.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import { Calculator, Calendar, AlertTriangle, CheckCircle, Loader2, Info } from 'lucide-react';
import { useSimulateSchengenTrip } from '@/hooks/useApi';
import { formatDate } from './schengenUtils';
import type { SchengenSimulationResult } from '@/types';

export default function PlanningTool() {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [result, setResult] = useState<SchengenSimulationResult | null>(null);

  const simulateMutation = useSimulateSchengenTrip();

  const handleSimulate = () => {
    if (!startDate || !endDate) return;

    simulateMutation.mutate(
      { startDate, endDate },
      {
        onSuccess: (data) => {
          setResult(data);
        },
      }
    );
  };

  const handleReset = () => {
    setStartDate('');
    setEndDate('');
    setResult(null);
  };

  const isValid = startDate && endDate && new Date(endDate) >= new Date(startDate);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="p-2 bg-primary-100 rounded-lg">
          <Calculator className="w-5 h-5 text-primary-600" aria-hidden="true" />
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900">Trip Planning Tool</h3>
          <p className="text-sm text-gray-500">
            Check if a planned trip would violate the 90/180 rule
          </p>
        </div>
      </div>

      {/* Input form */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="planning-start-date" className="block text-sm font-medium text-gray-700 mb-1">
            Start Date
          </label>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true" />
            <input
              type="date"
              id="planning-start-date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="pl-10 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
        </div>
        <div>
          <label htmlFor="planning-end-date" className="block text-sm font-medium text-gray-700 mb-1">
            End Date
          </label>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true" />
            <input
              type="date"
              id="planning-end-date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              min={startDate}
              className="pl-10 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            />
          </div>
        </div>
      </div>

      {/* Action buttons */}
      <div className="flex items-center gap-3">
        <button
          onClick={handleSimulate}
          disabled={!isValid || simulateMutation.isPending}
          className={clsx(
            'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-colors',
            isValid && !simulateMutation.isPending
              ? 'bg-primary-600 text-white hover:bg-primary-700'
              : 'bg-gray-100 text-gray-400 cursor-not-allowed'
          )}
        >
          {simulateMutation.isPending ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
              Calculating...
            </>
          ) : (
            <>
              <Calculator className="w-4 h-4" aria-hidden="true" />
              Check Trip
            </>
          )}
        </button>
        {result && (
          <button
            onClick={handleReset}
            className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
          >
            Reset
          </button>
        )}
      </div>

      {/* Results */}
      {result && (
        <div
          className={clsx(
            'rounded-lg p-4 border',
            result.wouldViolate
              ? 'bg-red-50 border-red-200'
              : 'bg-green-50 border-green-200'
          )}
        >
          <div className="flex items-start gap-3">
            {result.wouldViolate ? (
              <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
            ) : (
              <CheckCircle className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
            )}
            <div className="flex-1">
              <h4
                className={clsx(
                  'font-semibold',
                  result.wouldViolate ? 'text-red-800' : 'text-green-800'
                )}
              >
                {result.wouldViolate
                  ? 'This trip would violate the 90/180 rule'
                  : 'This trip is safe'}
              </h4>
              <p
                className={clsx(
                  'text-sm mt-1',
                  result.wouldViolate ? 'text-red-700' : 'text-green-700'
                )}
              >
                {result.wouldViolate
                  ? `You would exceed the limit by ${result.daysOverLimit} day${result.daysOverLimit !== 1 ? 's' : ''}.`
                  : `You would use ${result.maxDaysUsed} of your 90 days.`}
              </p>

              {/* Stats grid */}
              <div className="grid grid-cols-2 gap-4 mt-4">
                <div className="bg-white/60 rounded-lg p-3">
                  <p className="text-xs text-gray-500">Trip Length</p>
                  <p className="text-lg font-semibold text-gray-900">
                    {result.proposedLength} day{result.proposedLength !== 1 ? 's' : ''}
                  </p>
                </div>
                <div className="bg-white/60 rounded-lg p-3">
                  <p className="text-xs text-gray-500">Max Days Used</p>
                  <p className="text-lg font-semibold text-gray-900">{result.maxDaysUsed}</p>
                </div>
              </div>

              {/* Suggestions for violations */}
              {result.wouldViolate && (
                <div className="mt-4 space-y-3">
                  {result.earliestSafeDate && (
                    <div className="flex items-start gap-2 text-sm">
                      <Info className="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
                      <p className="text-gray-700">
                        <span className="font-medium">Earliest safe start date:</span>{' '}
                        {formatDate(result.earliestSafeDate)}
                      </p>
                    </div>
                  )}
                  <div className="flex items-start gap-2 text-sm">
                    <Info className="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
                    <p className="text-gray-700">
                      <span className="font-medium">Max safe trip length from {formatDate(startDate)}:</span>{' '}
                      {result.maxSafeLength} day{result.maxSafeLength !== 1 ? 's' : ''}
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Error state */}
      {simulateMutation.isError && (
        <div className="rounded-lg p-4 bg-red-50 border border-red-200">
          <div className="flex items-center gap-2 text-red-800">
            <AlertTriangle className="w-4 h-4" aria-hidden="true" />
            <p className="text-sm">Failed to simulate trip. Please try again.</p>
          </div>
        </div>
      )}
    </div>
  );
}
