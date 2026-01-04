/**
 * AnalyticsDashboard
 *
 * Displays analytics charts and statistics for Schengen travel history.
 * Premium feature with historical visualization, country breakdown,
 * monthly trends, and compliance tracking.
 */

import {
  BarChart,
  Bar,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  AreaChart,
  Area,
} from 'recharts';
import {
  TrendingUp,
  Calendar,
  MapPin,
  Clock,
  Briefcase,
  User,
  AlertTriangle,
} from 'lucide-react';
import { clsx } from 'clsx';
import { useSchengenAnalytics } from '@/hooks/useApi';

// Chart colors
const COLORS = {
  primary: '#3B82F6',
  secondary: '#10B981',
  warning: '#F59E0B',
  danger: '#EF4444',
  purple: '#8B5CF6',
  pink: '#EC4899',
  cyan: '#06B6D4',
  orange: '#F97316',
};

const PIE_COLORS = [
  COLORS.primary,
  COLORS.secondary,
  COLORS.warning,
  COLORS.purple,
  COLORS.pink,
  COLORS.cyan,
  COLORS.orange,
  COLORS.danger,
];

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: 'up' | 'down' | 'neutral';
  trendValue?: string;
}

function StatCard({ title, value, subtitle, icon, trend, trendValue }: StatCardProps) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-4">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-sm text-gray-500">{title}</p>
          <p className="mt-1 text-2xl font-bold text-gray-900">{value}</p>
          {subtitle && <p className="mt-1 text-xs text-gray-400">{subtitle}</p>}
        </div>
        <div className="rounded-lg bg-primary-50 p-2 text-primary-600">{icon}</div>
      </div>
      {trend && trendValue && (
        <div className="mt-2 flex items-center text-xs">
          <span
            className={clsx(
              'font-medium',
              trend === 'up' && 'text-green-600',
              trend === 'down' && 'text-red-600',
              trend === 'neutral' && 'text-gray-500'
            )}
          >
            {trendValue}
          </span>
        </div>
      )}
    </div>
  );
}

interface ChartCardProps {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  className?: string;
}

function ChartCard({ title, subtitle, children, className }: ChartCardProps) {
  return (
    <div className={clsx('bg-white rounded-lg border border-gray-200 p-4', className)}>
      <div className="mb-4">
        <h3 className="font-semibold text-gray-900">{title}</h3>
        {subtitle && <p className="text-sm text-gray-500">{subtitle}</p>}
      </div>
      {children}
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="space-y-6">
      {/* Stats skeleton */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white rounded-lg border border-gray-200 p-4 animate-pulse">
            <div className="h-4 bg-gray-200 rounded w-20 mb-2" />
            <div className="h-8 bg-gray-200 rounded w-16" />
          </div>
        ))}
      </div>
      {/* Charts skeleton */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-white rounded-lg border border-gray-200 p-4 animate-pulse">
            <div className="h-4 bg-gray-200 rounded w-32 mb-4" />
            <div className="h-48 bg-gray-100 rounded" />
          </div>
        ))}
      </div>
    </div>
  );
}

function EmptyState() {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <TrendingUp className="w-12 h-12 text-gray-300 mx-auto mb-4" />
      <h3 className="text-lg font-medium text-gray-900 mb-2">No travel data yet</h3>
      <p className="text-gray-500 text-sm max-w-md mx-auto">
        Start logging your Schengen trips to see analytics and visualizations of your travel
        patterns over time.
      </p>
    </div>
  );
}

export default function AnalyticsDashboard() {
  const { data, isLoading, error } = useSchengenAnalytics();

  // Format date for display
  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  // Custom tooltip for charts
  const CustomTooltip = ({
    active,
    payload,
    label,
  }: {
    active?: boolean;
    payload?: Array<{ value: number; name: string; color: string }>;
    label?: string;
  }) => {
    if (!active || !payload || !payload.length) return null;

    return (
      <div className="bg-white border border-gray-200 rounded-lg shadow-lg p-3">
        <p className="text-sm font-medium text-gray-900 mb-1">{label}</p>
        {payload.map((entry, index) => (
          <p key={index} className="text-sm" style={{ color: entry.color }}>
            {entry.name}: {entry.value}
          </p>
        ))}
      </div>
    );
  };

  if (isLoading) {
    return <LoadingSkeleton />;
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
        <AlertTriangle className="w-5 h-5 inline-block mr-2" />
        Failed to load analytics data. Please try again later.
      </div>
    );
  }

  if (!data || data.summary.totalTrips === 0) {
    return <EmptyState />;
  }

  const { summary, countryBreakdown, monthlyTrends, yearlyTotals, complianceHistory, tripDurations, categoryBreakdown } = data;

  return (
    <div className="space-y-6">
      {/* Summary Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard
          title="Total Trips"
          value={summary.totalTrips}
          subtitle={`${summary.uniqueCountries} countries visited`}
          icon={<Calendar className="w-5 h-5" />}
        />
        <StatCard
          title="Total Days"
          value={summary.totalDays}
          subtitle={`Avg ${summary.avgTripLength} days/trip`}
          icon={<Clock className="w-5 h-5" />}
        />
        <StatCard
          title="Longest Trip"
          value={`${summary.longestTrip} days`}
          subtitle={summary.shortestTrip > 0 ? `Shortest: ${summary.shortestTrip} days` : undefined}
          icon={<TrendingUp className="w-5 h-5" />}
        />
        <StatCard
          title="Countries"
          value={summary.uniqueCountries}
          subtitle={`First trip: ${formatDate(summary.firstTrip)}`}
          icon={<MapPin className="w-5 h-5" />}
        />
      </div>

      {/* Charts Row 1 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Monthly Trends */}
        <ChartCard
          title="Monthly Travel Trends"
          subtitle="Days spent in Schengen area per month"
        >
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={monthlyTrends}>
                <defs>
                  <linearGradient id="colorDays" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor={COLORS.primary} stopOpacity={0.3} />
                    <stop offset="95%" stopColor={COLORS.primary} stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                <XAxis
                  dataKey="month"
                  tick={{ fontSize: 11 }}
                  tickLine={false}
                  axisLine={{ stroke: '#E5E7EB' }}
                />
                <YAxis
                  tick={{ fontSize: 11 }}
                  tickLine={false}
                  axisLine={{ stroke: '#E5E7EB' }}
                />
                <Tooltip content={<CustomTooltip />} />
                <Area
                  type="monotone"
                  dataKey="days"
                  name="Days"
                  stroke={COLORS.primary}
                  strokeWidth={2}
                  fill="url(#colorDays)"
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>

        {/* Country Breakdown */}
        <ChartCard
          title="Country Breakdown"
          subtitle="Days spent per country"
        >
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={countryBreakdown.slice(0, 8) as Array<{ country: string; days: number; trips: number; [key: string]: string | number }>}
                  cx="50%"
                  cy="50%"
                  labelLine={false}
                  label={({ name, percent }: { name?: string; percent?: number }) =>
                    name && percent && percent > 0.05 ? `${name} (${(percent * 100).toFixed(0)}%)` : ''
                  }
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="days"
                  nameKey="country"
                >
                  {countryBreakdown.slice(0, 8).map((_, index) => (
                    <Cell key={`cell-${index}`} fill={PIE_COLORS[index % PIE_COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>
      </div>

      {/* Charts Row 2 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Compliance History */}
        {complianceHistory.length > 0 && (
          <ChartCard
            title="90-Day Compliance History"
            subtitle="Days used in rolling 180-day window over time"
          >
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={complianceHistory}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                  <XAxis
                    dataKey="date"
                    tick={{ fontSize: 10 }}
                    tickLine={false}
                    axisLine={{ stroke: '#E5E7EB' }}
                    tickFormatter={(date) =>
                      new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                    }
                    interval="preserveStartEnd"
                  />
                  <YAxis
                    domain={[0, 90]}
                    tick={{ fontSize: 11 }}
                    tickLine={false}
                    axisLine={{ stroke: '#E5E7EB' }}
                  />
                  <Tooltip
                    content={({ active, payload, label }) => {
                      if (!active || !payload || !payload.length || !label) return null;
                      const dataPoint = payload[0].payload;
                      return (
                        <div className="bg-white border border-gray-200 rounded-lg shadow-lg p-3">
                          <p className="text-sm font-medium text-gray-900 mb-1">
                            {new Date(String(label)).toLocaleDateString('en-US', {
                              month: 'long',
                              day: 'numeric',
                              year: 'numeric',
                            })}
                          </p>
                          <p className="text-sm text-primary-600">
                            Days used: {dataPoint.daysUsed}/90
                          </p>
                          <p className="text-sm text-green-600">
                            Days remaining: {dataPoint.daysRemaining}
                          </p>
                        </div>
                      );
                    }}
                  />
                  {/* Reference line at 90 days */}
                  <Line
                    type="monotone"
                    dataKey={() => 90}
                    stroke={COLORS.danger}
                    strokeDasharray="5 5"
                    strokeWidth={1}
                    dot={false}
                    name="Limit"
                  />
                  {/* Warning threshold at 60 */}
                  <Line
                    type="monotone"
                    dataKey={() => 60}
                    stroke={COLORS.warning}
                    strokeDasharray="3 3"
                    strokeWidth={1}
                    dot={false}
                    name="Warning"
                  />
                  <Line
                    type="monotone"
                    dataKey="daysUsed"
                    name="Days Used"
                    stroke={COLORS.primary}
                    strokeWidth={2}
                    dot={false}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
            <div className="mt-2 flex items-center justify-center gap-4 text-xs text-gray-500">
              <span className="flex items-center gap-1">
                <span className="w-3 h-0.5 bg-red-500" style={{ display: 'inline-block' }} />
                90-day limit
              </span>
              <span className="flex items-center gap-1">
                <span
                  className="w-3 h-0.5 bg-yellow-500"
                  style={{ display: 'inline-block' }}
                />
                Warning (60 days)
              </span>
            </div>
          </ChartCard>
        )}

        {/* Yearly Totals */}
        {yearlyTotals.length > 0 && (
          <ChartCard title="Yearly Summary" subtitle="Trips and days per year">
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={yearlyTotals}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                  <XAxis
                    dataKey="year"
                    tick={{ fontSize: 11 }}
                    tickLine={false}
                    axisLine={{ stroke: '#E5E7EB' }}
                  />
                  <YAxis
                    yAxisId="left"
                    tick={{ fontSize: 11 }}
                    tickLine={false}
                    axisLine={{ stroke: '#E5E7EB' }}
                  />
                  <YAxis
                    yAxisId="right"
                    orientation="right"
                    tick={{ fontSize: 11 }}
                    tickLine={false}
                    axisLine={{ stroke: '#E5E7EB' }}
                  />
                  <Tooltip content={<CustomTooltip />} />
                  <Legend />
                  <Bar yAxisId="left" dataKey="days" name="Days" fill={COLORS.primary} radius={[4, 4, 0, 0]} />
                  <Bar yAxisId="right" dataKey="trips" name="Trips" fill={COLORS.secondary} radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </ChartCard>
        )}
      </div>

      {/* Charts Row 3 */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Trip Duration Distribution */}
        <ChartCard title="Trip Duration Distribution" subtitle="How long are your trips?">
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={tripDurations} layout="vertical">
                <CartesianGrid strokeDasharray="3 3" stroke="#E5E7EB" />
                <XAxis type="number" tick={{ fontSize: 11 }} tickLine={false} axisLine={{ stroke: '#E5E7EB' }} />
                <YAxis
                  type="category"
                  dataKey="label"
                  tick={{ fontSize: 11 }}
                  tickLine={false}
                  axisLine={{ stroke: '#E5E7EB' }}
                  width={80}
                />
                <Tooltip content={<CustomTooltip />} />
                <Bar dataKey="count" name="Trips" fill={COLORS.purple} radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </ChartCard>

        {/* Category Breakdown */}
        <ChartCard title="Travel Purpose" subtitle="Personal vs Business travel">
          <div className="h-64 flex items-center justify-center">
            {categoryBreakdown.length > 0 ? (
              <div className="flex gap-8">
                {categoryBreakdown.map((cat) => (
                  <div key={cat.category} className="text-center">
                    <div
                      className={clsx(
                        'w-24 h-24 rounded-full flex items-center justify-center mb-3',
                        cat.category === 'personal' ? 'bg-blue-100' : 'bg-green-100'
                      )}
                    >
                      {cat.category === 'personal' ? (
                        <User className="w-10 h-10 text-blue-600" />
                      ) : (
                        <Briefcase className="w-10 h-10 text-green-600" />
                      )}
                    </div>
                    <p className="text-lg font-semibold text-gray-900">{cat.trips} trips</p>
                    <p className="text-sm text-gray-500">{cat.days} days</p>
                    <p className="text-xs text-gray-400 capitalize">{cat.category}</p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-gray-500 text-sm">No data available</p>
            )}
          </div>
        </ChartCard>
      </div>

      {/* Top Countries Table */}
      {countryBreakdown.length > 0 && (
        <ChartCard title="Top Countries" subtitle="Countries you've visited most">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200">
                  <th className="text-left py-2 px-3 font-medium text-gray-500">Country</th>
                  <th className="text-right py-2 px-3 font-medium text-gray-500">Trips</th>
                  <th className="text-right py-2 px-3 font-medium text-gray-500">Days</th>
                  <th className="text-right py-2 px-3 font-medium text-gray-500">% of Total</th>
                </tr>
              </thead>
              <tbody>
                {countryBreakdown.slice(0, 10).map((country, index) => {
                  const percentage = summary.totalDays > 0
                    ? ((country.days / summary.totalDays) * 100).toFixed(1)
                    : '0';
                  return (
                    <tr key={country.country} className="border-b border-gray-100">
                      <td className="py-2 px-3">
                        <div className="flex items-center gap-2">
                          <span
                            className="w-2 h-2 rounded-full"
                            style={{ backgroundColor: PIE_COLORS[index % PIE_COLORS.length] }}
                          />
                          {country.country}
                        </div>
                      </td>
                      <td className="text-right py-2 px-3 text-gray-600">{country.trips}</td>
                      <td className="text-right py-2 px-3 text-gray-600">{country.days}</td>
                      <td className="text-right py-2 px-3 text-gray-400">{percentage}%</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </ChartCard>
      )}
    </div>
  );
}
