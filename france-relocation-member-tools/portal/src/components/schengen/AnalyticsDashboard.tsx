/**
 * AnalyticsDashboard
 *
 * Analytics and insights for Schengen travel history.
 * Shows travel patterns, compliance history, and monthly breakdowns.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  BarChart3,
  TrendingUp,
  MapPin,
  Calendar,
  Clock,
  Plane,
  Globe,
  Loader2,
  ChevronDown,
} from 'lucide-react';
import type {
  AnalyticsOverview,
  TravelPattern,
  ComplianceHistoryPoint,
  MonthlyBreakdown,
} from '@/types';
import {
  useAnalyticsOverview,
  useTravelPatterns,
  useComplianceHistory,
  useMonthlyBreakdown,
} from '@/hooks/useApi';
import StatusBadge from './StatusBadge';

type Period = '30d' | '90d' | '180d' | '1y' | 'all';
type AnalyticsTab = 'overview' | 'countries' | 'history' | 'monthly';

export default function AnalyticsDashboard() {
  const [period, setPeriod] = useState<Period>('180d');
  const [activeTab, setActiveTab] = useState<AnalyticsTab>('overview');
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());

  const overviewQuery = useAnalyticsOverview(period);
  const patternsQuery = useTravelPatterns(period);
  const historyQuery = useComplianceHistory(period);
  const monthlyQuery = useMonthlyBreakdown(selectedYear);

  const periodOptions: { value: Period; label: string }[] = [
    { value: '30d', label: 'Last 30 days' },
    { value: '90d', label: 'Last 90 days' },
    { value: '180d', label: 'Last 180 days' },
    { value: '1y', label: 'Last year' },
    { value: 'all', label: 'All time' },
  ];

  const tabs = [
    { id: 'overview' as const, label: 'Overview', icon: BarChart3 },
    { id: 'countries' as const, label: 'Countries', icon: Globe },
    { id: 'history' as const, label: 'Compliance', icon: TrendingUp },
    { id: 'monthly' as const, label: 'Monthly', icon: Calendar },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-primary-100 rounded-lg">
            <BarChart3 className="w-5 h-5 text-primary-600" aria-hidden="true" />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-gray-900">Travel Analytics</h2>
            <p className="text-sm text-gray-500">Insights into your travel patterns</p>
          </div>
        </div>

        {/* Period Selector */}
        <div className="relative">
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value as Period)}
            className="appearance-none px-4 py-2 pr-10 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {periodOptions.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="border-b border-gray-200">
        <nav className="flex gap-6">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={clsx(
                'pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2',
                activeTab === tab.id
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              )}
            >
              <tab.icon className="w-4 h-4" aria-hidden="true" />
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <OverviewTab data={overviewQuery.data} isLoading={overviewQuery.isLoading} />
      )}
      {activeTab === 'countries' && (
        <CountriesTab data={patternsQuery.data} isLoading={patternsQuery.isLoading} />
      )}
      {activeTab === 'history' && (
        <HistoryTab data={historyQuery.data} isLoading={historyQuery.isLoading} />
      )}
      {activeTab === 'monthly' && (
        <MonthlyTab
          data={monthlyQuery.data}
          isLoading={monthlyQuery.isLoading}
          year={selectedYear}
          onYearChange={setSelectedYear}
        />
      )}
    </div>
  );
}

/**
 * Overview Tab
 */
function OverviewTab({
  data,
  isLoading,
}: {
  data: AnalyticsOverview | undefined;
  isLoading: boolean;
}) {
  if (isLoading) {
    return <LoadingState />;
  }

  if (!data) {
    return <EmptyState message="No analytics data available" />;
  }

  const { stats, compliance } = data;

  return (
    <div className="space-y-6">
      {/* Stats Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard
          icon={Plane}
          label="Total Trips"
          value={stats.totalTrips}
          color="blue"
        />
        <StatCard
          icon={Calendar}
          label="Days Traveled"
          value={stats.totalDays}
          color="green"
        />
        <StatCard
          icon={Globe}
          label="Countries"
          value={stats.uniqueCountries}
          color="purple"
        />
        <StatCard
          icon={Clock}
          label="Avg Trip Length"
          value={`${stats.avgTripLength} days`}
          color="orange"
        />
      </div>

      {/* Current Compliance Card */}
      <div className="card p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Current Compliance</h3>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-6">
            <div className="text-center">
              <p className="text-4xl font-bold text-gray-900">{compliance.daysUsed}</p>
              <p className="text-sm text-gray-500">Days Used</p>
            </div>
            <div className="text-center">
              <p className="text-4xl font-bold text-gray-900">{compliance.daysRemaining}</p>
              <p className="text-sm text-gray-500">Days Remaining</p>
            </div>
          </div>
          <StatusBadge status={compliance.status} size="lg" />
        </div>
        <div className="mt-4 pt-4 border-t">
          <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
            <div
              className={clsx(
                'h-full rounded-full transition-all',
                compliance.status === 'safe' && 'bg-green-500',
                compliance.status === 'warning' && 'bg-yellow-500',
                compliance.status === 'danger' && 'bg-orange-500',
                compliance.status === 'critical' && 'bg-red-500'
              )}
              style={{ width: `${Math.min(100, (compliance.daysUsed / 90) * 100)}%` }}
            />
          </div>
        </div>
      </div>

      {/* Highlights */}
      <div className="grid md:grid-cols-2 gap-4">
        {stats.longestTrip && (
          <div className="card p-4">
            <h4 className="text-sm font-medium text-gray-500 mb-2">Longest Trip</h4>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-100 rounded-lg">
                <MapPin className="w-5 h-5 text-blue-600" aria-hidden="true" />
              </div>
              <div>
                <p className="font-semibold text-gray-900">{stats.longestTrip.country}</p>
                <p className="text-sm text-gray-500">{stats.longestTrip.days} days</p>
              </div>
            </div>
          </div>
        )}
        {stats.mostVisited && (
          <div className="card p-4">
            <h4 className="text-sm font-medium text-gray-500 mb-2">Most Visited</h4>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-purple-100 rounded-lg">
                <Globe className="w-5 h-5 text-purple-600" aria-hidden="true" />
              </div>
              <div>
                <p className="font-semibold text-gray-900">{stats.mostVisited.country}</p>
                <p className="text-sm text-gray-500">
                  {stats.mostVisited.visitCount} trips, {stats.mostVisited.totalDays} days
                </p>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Countries Tab
 */
function CountriesTab({
  data,
  isLoading,
}: {
  data: { countries: TravelPattern[]; monthly: Array<{ month: string; tripCount: number; totalDays: number }> } | undefined;
  isLoading: boolean;
}) {
  if (isLoading) {
    return <LoadingState />;
  }

  if (!data || data.countries.length === 0) {
    return <EmptyState message="No travel data for this period" />;
  }

  return (
    <div className="space-y-6">
      {/* Countries List */}
      <div className="card">
        <div className="p-4 border-b">
          <h3 className="font-semibold text-gray-900">Countries Visited</h3>
        </div>
        <div className="divide-y">
          {data.countries.map((country, idx) => (
            <div key={country.country} className="p-4 flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-sm font-medium text-gray-600">
                  {idx + 1}
                </div>
                <div>
                  <p className="font-medium text-gray-900">{country.country}</p>
                  <p className="text-sm text-gray-500">
                    {country.tripCount} {country.tripCount === 1 ? 'trip' : 'trips'}
                  </p>
                </div>
              </div>
              <div className="text-right">
                <p className="font-semibold text-gray-900">{country.totalDays} days</p>
                <p className="text-sm text-gray-500">{country.percentage}%</p>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Distribution Chart (simplified bar chart) */}
      <div className="card p-6">
        <h3 className="font-semibold text-gray-900 mb-4">Days Distribution</h3>
        <div className="space-y-3">
          {data.countries.slice(0, 5).map((country) => (
            <div key={country.country}>
              <div className="flex items-center justify-between text-sm mb-1">
                <span className="text-gray-700">{country.country}</span>
                <span className="text-gray-500">{country.totalDays} days</span>
              </div>
              <div className="h-4 bg-gray-100 rounded-full overflow-hidden">
                <div
                  className="h-full bg-primary-500 rounded-full"
                  style={{ width: `${country.percentage}%` }}
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/**
 * History Tab
 */
function HistoryTab({
  data,
  isLoading,
}: {
  data: { history: ComplianceHistoryPoint[] } | undefined;
  isLoading: boolean;
}) {
  if (isLoading) {
    return <LoadingState />;
  }

  if (!data || data.history.length === 0) {
    return <EmptyState message="No compliance history available" />;
  }

  // Simple line chart representation
  const maxDays = 90;
  const points = data.history;

  return (
    <div className="space-y-6">
      {/* Chart Area */}
      <div className="card p-6">
        <h3 className="font-semibold text-gray-900 mb-4">Compliance Over Time</h3>
        <div className="relative h-64">
          {/* Y-axis labels */}
          <div className="absolute left-0 top-0 bottom-0 w-12 flex flex-col justify-between text-xs text-gray-500 py-2">
            <span>90</span>
            <span>60</span>
            <span>30</span>
            <span>0</span>
          </div>

          {/* Chart area */}
          <div className="ml-14 h-full relative">
            {/* Grid lines */}
            <div className="absolute inset-0 flex flex-col justify-between">
              <div className="border-t border-gray-100" />
              <div className="border-t border-gray-100" />
              <div className="border-t border-gray-100" />
              <div className="border-t border-gray-200" />
            </div>

            {/* Warning threshold line */}
            <div
              className="absolute left-0 right-0 border-t-2 border-dashed border-yellow-400"
              style={{ top: `${100 - (60 / maxDays) * 100}%` }}
            />

            {/* Danger threshold line */}
            <div
              className="absolute left-0 right-0 border-t-2 border-dashed border-red-400"
              style={{ top: `${100 - (80 / maxDays) * 100}%` }}
            />

            {/* Data points */}
            <svg className="absolute inset-0 w-full h-full overflow-visible">
              <polyline
                fill="none"
                stroke="#3B82F6"
                strokeWidth="2"
                points={points
                  .map((p, i) => {
                    const x = (i / (points.length - 1)) * 100;
                    const y = 100 - (p.daysUsed / maxDays) * 100;
                    return `${x}%,${y}%`;
                  })
                  .join(' ')}
              />
            </svg>
          </div>
        </div>

        {/* Legend */}
        <div className="flex items-center gap-4 mt-4 text-sm">
          <div className="flex items-center gap-2">
            <div className="w-3 h-0.5 bg-yellow-400" />
            <span className="text-gray-600">Warning (60 days)</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-3 h-0.5 bg-red-400" />
            <span className="text-gray-600">Danger (80 days)</span>
          </div>
        </div>
      </div>

      {/* History Table */}
      <div className="card">
        <div className="p-4 border-b">
          <h3 className="font-semibold text-gray-900">History Details</h3>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Date
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Days Used
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Remaining
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Status
                </th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {points.slice(-10).reverse().map((point) => (
                <tr key={point.date}>
                  <td className="px-4 py-3 text-sm text-gray-900">{point.date}</td>
                  <td className="px-4 py-3 text-sm font-medium text-gray-900">
                    {point.daysUsed}
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-600">{point.daysRemaining}</td>
                  <td className="px-4 py-3">
                    <StatusBadge status={point.status} size="sm" />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

/**
 * Monthly Tab
 */
function MonthlyTab({
  data,
  isLoading,
  year,
  onYearChange,
}: {
  data: { months: MonthlyBreakdown[] } | undefined;
  isLoading: boolean;
  year: number;
  onYearChange: (year: number) => void;
}) {
  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

  if (isLoading) {
    return <LoadingState />;
  }

  return (
    <div className="space-y-6">
      {/* Year Selector */}
      <div className="flex items-center gap-3">
        <span className="text-sm text-gray-600">Year:</span>
        <div className="flex gap-2">
          {years.map((y) => (
            <button
              key={y}
              onClick={() => onYearChange(y)}
              className={clsx(
                'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                year === y
                  ? 'bg-primary-100 text-primary-700'
                  : 'text-gray-600 hover:bg-gray-100'
              )}
            >
              {y}
            </button>
          ))}
        </div>
      </div>

      {/* Monthly Grid */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {data?.months.map((month) => (
          <div
            key={month.month}
            className={clsx(
              'card p-4',
              month.days > 0 ? 'bg-white' : 'bg-gray-50'
            )}
          >
            <p className="text-sm font-medium text-gray-500 mb-2">{month.label}</p>
            <p className="text-2xl font-bold text-gray-900">{month.days}</p>
            <p className="text-xs text-gray-500">
              {month.days === 1 ? 'day' : 'days'} in Schengen
            </p>
            {month.tripCount > 0 && (
              <p className="text-xs text-gray-400 mt-1">
                {month.tripCount} {month.tripCount === 1 ? 'trip' : 'trips'}
              </p>
            )}
            {month.countries && (
              <p className="text-xs text-gray-400 truncate" title={month.countries}>
                {month.countries}
              </p>
            )}
          </div>
        ))}
      </div>

      {/* Summary */}
      {data && (
        <div className="card p-6">
          <h3 className="font-semibold text-gray-900 mb-4">
            {year} Summary
          </h3>
          <div className="grid grid-cols-3 gap-6 text-center">
            <div>
              <p className="text-3xl font-bold text-gray-900">
                {data.months.reduce((sum, m) => sum + m.days, 0)}
              </p>
              <p className="text-sm text-gray-500">Total Days</p>
            </div>
            <div>
              <p className="text-3xl font-bold text-gray-900">
                {data.months.reduce((sum, m) => sum + m.tripCount, 0)}
              </p>
              <p className="text-sm text-gray-500">Total Trips</p>
            </div>
            <div>
              <p className="text-3xl font-bold text-gray-900">
                {new Set(data.months.flatMap((m) => m.countries.split(', ').filter(Boolean))).size}
              </p>
              <p className="text-sm text-gray-500">Countries</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Stat Card Component
 */
function StatCard({
  icon: Icon,
  label,
  value,
  color,
}: {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  value: number | string;
  color: 'blue' | 'green' | 'purple' | 'orange';
}) {
  const colorClasses = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    purple: 'bg-purple-100 text-purple-600',
    orange: 'bg-orange-100 text-orange-600',
  };

  return (
    <div className="card p-4">
      <div className="flex items-center gap-3 mb-2">
        <div className={clsx('p-2 rounded-lg', colorClasses[color])}>
          <Icon className="w-5 h-5" aria-hidden="true" />
        </div>
      </div>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
      <p className="text-sm text-gray-500">{label}</p>
    </div>
  );
}

function LoadingState() {
  return (
    <div className="flex items-center justify-center p-12">
      <Loader2 className="w-8 h-8 animate-spin text-primary-500" />
    </div>
  );
}

function EmptyState({ message }: { message: string }) {
  return (
    <div className="card p-12 text-center">
      <BarChart3 className="w-12 h-12 text-gray-300 mx-auto mb-4" aria-hidden="true" />
      <p className="text-gray-500">{message}</p>
    </div>
  );
}
