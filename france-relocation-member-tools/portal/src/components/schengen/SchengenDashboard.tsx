/**
 * SchengenDashboard
 *
 * Main dashboard view for the Schengen 90/180 day tracker.
 * Shows compliance status, day counter, and trip management.
 * Includes premium features: calendar view, planning tool, PDF export.
 */

import { useState, useMemo, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  Plus,
  Calendar,
  Clock,
  AlertTriangle,
  Info,
  Crown,
  Calculator,
  CalendarDays,
  Lock,
  Settings,
  Bell,
  Send,
  Loader2,
  CheckCircle,
  MapPin,
  HelpCircle,
} from 'lucide-react';
import type { SchengenTrip } from '@/types';
import { useSchengenStore } from './useSchengenStore';
import { useSchengenFeatureStatus, useTestSchengenAlert } from '@/hooks/useApi';
import {
  getSchengenSummary,
  formatDate,
} from './schengenUtils';
import DayCounter from './DayCounter';
import StatusBadge from './StatusBadge';
import TripForm from './TripForm';
import TripList from './TripList';
import PlanningTool from './PlanningTool';
import CalendarView from './CalendarView';
import ReportExport from './ReportExport';
import LocationTracker from './LocationTracker';
import LocationDetectionBanner from './LocationDetectionBanner';
import SchengenOnboarding, { hasCompletedOnboarding } from './SchengenOnboarding';
import Modal from '@/components/shared/Modal';

type ViewTab = 'trips' | 'calendar' | 'planning' | 'location' | 'settings';

export default function SchengenDashboard() {
  const { trips, settings, isLoaded, addTrip, updateTrip, deleteTrip, updateSettings } = useSchengenStore();
  const { data: featureStatus, isLoading: featureLoading } = useSchengenFeatureStatus();
  const testAlertMutation = useTestSchengenAlert();

  const [showTripForm, setShowTripForm] = useState(false);
  const [editingTrip, setEditingTrip] = useState<SchengenTrip | null>(null);
  const [activeTab, setActiveTab] = useState<ViewTab>('trips');
  const [testAlertMessage, setTestAlertMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [showOnboarding, setShowOnboarding] = useState(false);

  // Check if first-time user on mount
  useEffect(() => {
    if (isLoaded && !hasCompletedOnboarding()) {
      setShowOnboarding(true);
    }
  }, [isLoaded]);

  // Calculate summary from trips
  const summary = useMemo(() => {
    return getSchengenSummary(trips, {
      yellow: settings.yellowThreshold,
      red: settings.redThreshold,
    });
  }, [trips, settings.yellowThreshold, settings.redThreshold]);

  const handleAddTrip = async (tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
    setActionError(null);
    try {
      await addTrip(tripData);
      setShowTripForm(false);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to add trip';
      setActionError(message);
      console.error('Failed to add trip:', error);
    }
  };

  const handleEditTrip = async (tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
    if (editingTrip) {
      setActionError(null);
      try {
        await updateTrip(editingTrip.id, tripData);
        setEditingTrip(null);
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Failed to update trip';
        setActionError(message);
        console.error('Failed to update trip:', error);
      }
    }
  };

  const handleDeleteTrip = async (id: string) => {
    setActionError(null);
    try {
      await deleteTrip(id);
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Failed to delete trip';
      setActionError(message);
      console.error('Failed to delete trip:', error);
    }
  };

  const handleOpenEdit = (trip: SchengenTrip) => {
    setEditingTrip(trip);
  };

  const handleCloseForm = () => {
    setShowTripForm(false);
    setEditingTrip(null);
  };

  const canAddTrip = featureStatus?.canAddTrip ?? true;
  const isPremium = featureStatus?.isPremium ?? true;

  if (!isLoaded || featureLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <div
            className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3"
            role="status"
            aria-label="Loading"
          />
          <p className="text-gray-500 text-sm">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Page header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Schengen Tracker</h1>
          <p className="text-gray-600 mt-1">
            Track your 90/180 day Schengen zone compliance
          </p>
        </div>
        <div className="flex items-center gap-3">
          {/* Help button to reopen onboarding */}
          <button
            onClick={() => setShowOnboarding(true)}
            className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            aria-label="Show help tour"
            title="Show help tour"
          >
            <HelpCircle className="w-5 h-5" aria-hidden="true" />
          </button>

          {/* PDF Export (premium) */}
          {isPremium && <ReportExport />}

          {/* Add trip button */}
          <button
            onClick={() => setShowTripForm(true)}
            disabled={!canAddTrip}
            className={clsx(
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors',
              canAddTrip
                ? 'bg-primary-600 text-white hover:bg-primary-700'
                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
            )}
          >
            <Plus className="w-5 h-5" aria-hidden="true" />
            Add Trip
          </button>
        </div>
      </div>

      {/* Free tier limit warning */}
      {!isPremium && featureStatus && (
        <div className="flex items-center justify-between p-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg">
          <div className="flex items-center gap-3">
            <Crown className="w-5 h-5 text-amber-600" aria-hidden="true" />
            <div>
              <p className="font-medium text-amber-800">
                Free Plan: {featureStatus.tripCount} of {featureStatus.tripLimit} trips used
              </p>
              <p className="text-sm text-amber-700">
                Upgrade to Premium for unlimited trips, planning tools, and PDF reports
              </p>
            </div>
          </div>
          {featureStatus.upgradeUrl && (
            <a
              href={featureStatus.upgradeUrl}
              className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors"
            >
              <Crown className="w-4 h-4" aria-hidden="true" />
              Upgrade
            </a>
          )}
        </div>
      )}

      {/* Status cards row */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Main day counter card */}
        <div className="card p-6 flex flex-col items-center justify-center">
          <DayCounter
            daysUsed={summary.daysUsed}
            daysRemaining={summary.daysRemaining}
            status={summary.status}
            size="lg"
          />
          <div className="mt-4">
            <StatusBadge status={summary.status} size="lg" />
          </div>
        </div>

        {/* Stats cards */}
        <div className="space-y-4">
          {/* Days remaining card */}
          <div
            className={clsx(
              'card p-4 border-l-4',
              summary.status === 'safe' && 'border-l-green-500',
              summary.status === 'warning' && 'border-l-yellow-500',
              summary.status === 'danger' && 'border-l-orange-500',
              summary.status === 'critical' && 'border-l-red-500'
            )}
          >
            <div className="flex items-center gap-3">
              <div className="p-2 bg-gray-100 rounded-lg">
                <Clock className="w-5 h-5 text-gray-600" aria-hidden="true" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Days Remaining</p>
                <p className="text-2xl font-bold text-gray-900">{summary.daysRemaining}</p>
              </div>
            </div>
          </div>

          {/* Current window card */}
          <div className="card p-4">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-gray-100 rounded-lg">
                <Calendar className="w-5 h-5 text-gray-600" aria-hidden="true" />
              </div>
              <div>
                <p className="text-sm text-gray-500">Current 180-Day Window</p>
                <p className="text-sm font-medium text-gray-900">
                  {formatDate(summary.windowStart)} - {formatDate(summary.windowEnd)}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Next expiration & info card */}
        <div className="space-y-4">
          {/* Next expiration */}
          {summary.nextExpiration && (
            <div className="card p-4 bg-blue-50 border-blue-200">
              <div className="flex items-start gap-3">
                <div className="p-2 bg-blue-100 rounded-lg">
                  <Calendar className="w-5 h-5 text-blue-600" aria-hidden="true" />
                </div>
                <div>
                  <p className="text-sm text-blue-700 font-medium">Next Day Expires</p>
                  <p className="text-lg font-bold text-blue-900">
                    {formatDate(summary.nextExpiration)}
                  </p>
                  <p className="text-xs text-blue-600 mt-1">
                    A day will drop off the 180-day window
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Info box */}
          <div className="card p-4 bg-gray-50">
            <div className="flex items-start gap-3">
              <Info className="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" aria-hidden="true" />
              <div className="text-xs text-gray-600">
                <p className="font-medium text-gray-700 mb-1">The 90/180 Rule</p>
                <p>
                  Non-EU citizens may stay up to 90 days within any 180-day period
                  in the Schengen area without a visa.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Smart location detection banner - always enabled, can be dismissed */}
      <LocationDetectionBanner enabled />

      {/* Quick location check-in widget */}
      <LocationTracker compact />

      {/* Warning banners */}
      {summary.status === 'warning' && (
        <div className="flex items-center gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <AlertTriangle className="w-5 h-5 text-yellow-600 flex-shrink-0" aria-hidden="true" />
          <p className="text-sm text-yellow-800">
            <span className="font-medium">Approaching limit:</span> You have used {summary.daysUsed} of your 90 days.
            Plan future travel carefully to avoid overstaying.
          </p>
        </div>
      )}

      {summary.status === 'danger' && (
        <div className="flex items-center gap-3 p-4 bg-orange-50 border border-orange-200 rounded-lg">
          <AlertTriangle className="w-5 h-5 text-orange-600 flex-shrink-0" aria-hidden="true" />
          <p className="text-sm text-orange-800">
            <span className="font-medium">Warning:</span> You have used {summary.daysUsed} of your 90 days.
            Limited travel remaining in this window.
          </p>
        </div>
      )}

      {summary.status === 'critical' && (
        <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
          <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
          <p className="text-sm text-red-800">
            <span className="font-medium">Limit reached:</span> You have used all 90 days allowed in this
            180-day window. Additional travel may result in overstay violations.
          </p>
        </div>
      )}

      {/* Action error display */}
      {actionError && (
        <div className="flex items-center justify-between gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
          <div className="flex items-center gap-3">
            <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
            <p className="text-sm text-red-800">{actionError}</p>
          </div>
          <button
            onClick={() => setActionError(null)}
            className="text-red-600 hover:text-red-800 text-sm font-medium"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* Tab navigation */}
      <div className="border-b border-gray-200">
        <nav className="flex gap-6" aria-label="Tabs">
          <button
            onClick={() => setActiveTab('trips')}
            className={clsx(
              'pb-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'trips'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            )}
          >
            <span className="flex items-center gap-2">
              <Calendar className="w-4 h-4" aria-hidden="true" />
              Trip List
            </span>
          </button>
          <button
            onClick={() => setActiveTab('calendar')}
            className={clsx(
              'pb-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'calendar'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            )}
          >
            <span className="flex items-center gap-2">
              <CalendarDays className="w-4 h-4" aria-hidden="true" />
              Calendar View
              {!isPremium && <Lock className="w-3 h-3 text-gray-400" aria-hidden="true" />}
            </span>
          </button>
          <button
            onClick={() => setActiveTab('planning')}
            className={clsx(
              'pb-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'planning'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            )}
          >
            <span className="flex items-center gap-2">
              <Calculator className="w-4 h-4" aria-hidden="true" />
              Planning Tool
              {!isPremium && <Lock className="w-3 h-3 text-gray-400" aria-hidden="true" />}
            </span>
          </button>
          <button
            onClick={() => setActiveTab('location')}
            className={clsx(
              'pb-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'location'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            )}
          >
            <span className="flex items-center gap-2">
              <MapPin className="w-4 h-4" aria-hidden="true" />
              Location
            </span>
          </button>
          <button
            onClick={() => setActiveTab('settings')}
            className={clsx(
              'pb-3 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'settings'
                ? 'border-primary-600 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            )}
          >
            <span className="flex items-center gap-2">
              <Settings className="w-4 h-4" aria-hidden="true" />
              Settings
            </span>
          </button>
        </nav>
      </div>

      {/* Tab content */}
      {activeTab === 'trips' && (
        <div className="card">
          <div className="card-header flex items-center justify-between">
            <h2 className="text-lg font-semibold text-gray-900">Your Trips</h2>
            <span className="text-sm text-gray-500">{trips.length} trip{trips.length !== 1 ? 's' : ''}</span>
          </div>
          <TripList
            trips={trips}
            currentWindowStart={summary.windowStart}
            onEdit={handleOpenEdit}
            onDelete={handleDeleteTrip}
          />
        </div>
      )}

      {activeTab === 'calendar' && (
        isPremium ? (
          <CalendarView
            trips={trips}
            windowStart={summary.windowStart}
            windowEnd={summary.windowEnd}
          />
        ) : (
          <PremiumFeaturePrompt
            feature="Calendar View"
            description="Visualize your trips on an interactive calendar to better understand your travel patterns and plan future trips."
            upgradeUrl={featureStatus?.upgradeUrl}
          />
        )
      )}

      {activeTab === 'planning' && (
        isPremium ? (
          <div className="card p-6">
            <PlanningTool />
          </div>
        ) : (
          <PremiumFeaturePrompt
            feature="Planning Tool"
            description="Use the 'What If' calculator to check if a planned trip would violate the 90/180 rule before you book."
            upgradeUrl={featureStatus?.upgradeUrl}
          />
        )
      )}

      {activeTab === 'location' && (
        <LocationTracker />
      )}

      {activeTab === 'settings' && (
        <div className="card p-6 space-y-6">
          {/* Header */}
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary-100 rounded-lg">
              <Settings className="w-5 h-5 text-primary-600" aria-hidden="true" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Tracker Settings</h3>
              <p className="text-sm text-gray-500">
                Configure alerts and notification preferences
              </p>
            </div>
          </div>

          {/* Email Alerts Toggle */}
          <div className="border-t border-gray-200 pt-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-blue-100 rounded-lg">
                  <Bell className="w-5 h-5 text-blue-600" aria-hidden="true" />
                </div>
                <div>
                  <h4 className="font-medium text-gray-900">Email Alerts</h4>
                  <p className="text-sm text-gray-500">
                    Receive email notifications when approaching your 90-day limit
                  </p>
                </div>
              </div>
              <button
                onClick={() => updateSettings({ emailAlerts: !settings.emailAlerts })}
                className={clsx(
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                  settings.emailAlerts ? 'bg-primary-600' : 'bg-gray-200'
                )}
                role="switch"
                aria-checked={settings.emailAlerts}
              >
                <span
                  className={clsx(
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    settings.emailAlerts ? 'translate-x-5' : 'translate-x-0'
                  )}
                />
              </button>
            </div>

            {/* Alert info when enabled */}
            {settings.emailAlerts && (
              <div className="mt-4 ml-14 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p className="text-sm text-blue-800">
                  <span className="font-medium">Alert thresholds:</span> You will receive email alerts when you reach 60 days (warning), 80 days (danger), and 85 days (urgent).
                </p>
              </div>
            )}

            {/* Test Alert Button */}
            {settings.emailAlerts && (
              <div className="mt-4 ml-14">
                <button
                  onClick={() => {
                    setTestAlertMessage(null);
                    testAlertMutation.mutate(undefined, {
                      onSuccess: (result) => {
                        setTestAlertMessage({
                          type: result.success ? 'success' : 'error',
                          text: result.message,
                        });
                      },
                      onError: (error) => {
                        setTestAlertMessage({
                          type: 'error',
                          text: error instanceof Error ? error.message : 'Failed to send test alert',
                        });
                      },
                    });
                  }}
                  disabled={testAlertMutation.isPending}
                  className={clsx(
                    'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-colors',
                    testAlertMutation.isPending
                      ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                      : 'bg-blue-600 text-white hover:bg-blue-700'
                  )}
                >
                  {testAlertMutation.isPending ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                      Sending...
                    </>
                  ) : (
                    <>
                      <Send className="w-4 h-4" aria-hidden="true" />
                      Send Test Alert
                    </>
                  )}
                </button>

                {/* Test alert result message */}
                {testAlertMessage && (
                  <div
                    className={clsx(
                      'mt-3 p-3 rounded-lg flex items-center gap-2 text-sm',
                      testAlertMessage.type === 'success'
                        ? 'bg-green-50 text-green-800 border border-green-200'
                        : 'bg-red-50 text-red-800 border border-red-200'
                    )}
                  >
                    {testAlertMessage.type === 'success' ? (
                      <CheckCircle className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                    ) : (
                      <AlertTriangle className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                    )}
                    {testAlertMessage.text}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Threshold Settings (display only) */}
          <div className="border-t border-gray-200 pt-6">
            <h4 className="font-medium text-gray-900 mb-4">Warning Thresholds</h4>
            <div className="grid grid-cols-2 gap-4">
              <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p className="text-sm font-medium text-yellow-800">Warning Level</p>
                <p className="text-lg font-bold text-yellow-900">{settings.yellowThreshold} days</p>
                <p className="text-xs text-yellow-700 mt-1">Status turns yellow</p>
              </div>
              <div className="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <p className="text-sm font-medium text-orange-800">Danger Level</p>
                <p className="text-lg font-bold text-orange-900">{settings.redThreshold} days</p>
                <p className="text-xs text-orange-700 mt-1">Status turns red</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Add trip modal */}
      <Modal
        isOpen={showTripForm}
        onClose={handleCloseForm}
        title="Add Trip"
        size="md"
      >
        <TripForm
          existingTrips={trips}
          onSubmit={handleAddTrip}
          onCancel={handleCloseForm}
        />
      </Modal>

      {/* Edit trip modal */}
      <Modal
        isOpen={!!editingTrip}
        onClose={handleCloseForm}
        title="Edit Trip"
        size="md"
      >
        {editingTrip && (
          <TripForm
            trip={editingTrip}
            existingTrips={trips}
            onSubmit={handleEditTrip}
            onCancel={handleCloseForm}
          />
        )}
      </Modal>

      {/* Onboarding modal for first-time users */}
      <SchengenOnboarding
        isOpen={showOnboarding}
        onComplete={() => setShowOnboarding(false)}
        isPremium={isPremium}
      />
    </div>
  );
}

/**
 * Premium feature upgrade prompt
 */
function PremiumFeaturePrompt({
  feature,
  description,
  upgradeUrl,
}: {
  feature: string;
  description: string;
  upgradeUrl?: string | null;
}) {
  return (
    <div className="card p-8 text-center">
      <div className="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <Crown className="w-8 h-8 text-amber-600" aria-hidden="true" />
      </div>
      <h3 className="text-lg font-semibold text-gray-900 mb-2">
        {feature} is a Premium Feature
      </h3>
      <p className="text-gray-600 mb-6 max-w-md mx-auto">
        {description}
      </p>
      {upgradeUrl && (
        <a
          href={upgradeUrl}
          className="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors"
        >
          <Crown className="w-5 h-5" aria-hidden="true" />
          Upgrade to Premium
        </a>
      )}
    </div>
  );
}
