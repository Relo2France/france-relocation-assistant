/**
 * LocationTracker
 *
 * Component for tracking location via browser geolocation.
 * Allows users to "check in" their current location for Schengen tracking.
 */

import { useState, useCallback } from 'react';
import { clsx } from 'clsx';
import {
  MapPin,
  Navigation,
  CheckCircle,
  AlertTriangle,
  Loader2,
  History,
  Trash2,
  Globe,
  X,
  RefreshCw,
  Info,
} from 'lucide-react';
import { useGeolocation } from '@/hooks/useGeolocation';
import {
  useSchengenLocationToday,
  useStoreSchengenLocation,
  useSchengenLocationHistory,
  useDeleteSchengenLocation,
  useClearSchengenLocationHistory,
} from '@/hooks/useApi';
import type { SchengenLocation } from '@/types';
import Modal from '@/components/shared/Modal';

interface LocationTrackerProps {
  /** Compact mode for dashboard widget */
  compact?: boolean;
}

export default function LocationTracker({ compact = false }: LocationTrackerProps) {
  const [showHistory, setShowHistory] = useState(false);
  const [showClearConfirm, setShowClearConfirm] = useState(false);

  const {
    error: geoError,
    isLoading: geoLoading,
    permission,
    isSupported,
    getCurrentPosition,
  } = useGeolocation();

  const { data: todayStatus, isLoading: todayLoading } = useSchengenLocationToday();
  const storeLocationMutation = useStoreSchengenLocation();

  const handleCheckIn = useCallback(async () => {
    try {
      const pos = await getCurrentPosition();
      await storeLocationMutation.mutateAsync({
        lat: pos.lat,
        lng: pos.lng,
        accuracy: pos.accuracy,
        source: 'checkin',
      });
    } catch (err) {
      // Error is handled by the mutation or geolocation hook
      console.error('Check-in failed:', err);
    }
  }, [getCurrentPosition, storeLocationMutation]);

  const isCheckedInToday = todayStatus?.hasCheckedInToday ?? false;
  const lastLocation = todayStatus?.lastLocation;
  const isCheckingIn = geoLoading || storeLocationMutation.isPending;

  // Compact widget view
  if (compact) {
    return (
      <div className="card p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div
              className={clsx(
                'p-2 rounded-lg',
                isCheckedInToday ? 'bg-green-100' : 'bg-gray-100'
              )}
            >
              <MapPin
                className={clsx(
                  'w-5 h-5',
                  isCheckedInToday ? 'text-green-600' : 'text-gray-500'
                )}
                aria-hidden="true"
              />
            </div>
            <div>
              <p className="font-medium text-gray-900 text-sm">Location Check-in</p>
              {isCheckedInToday && lastLocation ? (
                <p className="text-xs text-green-600">
                  Checked in: {lastLocation.countryName ?? 'Unknown'}
                  {lastLocation.isSchengen && ' (Schengen)'}
                </p>
              ) : (
                <p className="text-xs text-gray-500">Not checked in today</p>
              )}
            </div>
          </div>

          <button
            onClick={handleCheckIn}
            disabled={isCheckingIn || !isSupported}
            className={clsx(
              'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
              isCheckingIn
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : isCheckedInToday
                  ? 'bg-green-100 text-green-700 hover:bg-green-200'
                  : 'bg-primary-600 text-white hover:bg-primary-700'
            )}
          >
            {isCheckingIn ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                <span className="sr-only">Checking in...</span>
              </>
            ) : isCheckedInToday ? (
              <>
                <CheckCircle className="w-4 h-4" aria-hidden="true" />
                Update
              </>
            ) : (
              <>
                <Navigation className="w-4 h-4" aria-hidden="true" />
                Check In
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

  // Full view
  return (
    <div className="space-y-4">
      {/* Main check-in card */}
      <div className="card p-6">
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center gap-3">
            <div
              className={clsx(
                'p-3 rounded-xl',
                isCheckedInToday ? 'bg-green-100' : 'bg-blue-100'
              )}
            >
              <MapPin
                className={clsx(
                  'w-6 h-6',
                  isCheckedInToday ? 'text-green-600' : 'text-blue-600'
                )}
                aria-hidden="true"
              />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Location Check-in</h3>
              <p className="text-sm text-gray-500">
                Record your current location for Schengen tracking
              </p>
            </div>
          </div>

          <button
            onClick={() => setShowHistory(true)}
            className="inline-flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <History className="w-4 h-4" aria-hidden="true" />
            History
          </button>
        </div>

        {/* Permission warning */}
        {!isSupported && (
          <div className="flex items-center gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
            <AlertTriangle className="w-5 h-5 text-yellow-600 flex-shrink-0" aria-hidden="true" />
            <p className="text-sm text-yellow-800">
              Geolocation is not supported by your browser. Try using a modern browser like Chrome, Firefox, or Safari.
            </p>
          </div>
        )}

        {permission === 'denied' && (
          <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
            <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
            <div>
              <p className="text-sm font-medium text-red-800">Location permission denied</p>
              <p className="text-sm text-red-700">
                Please enable location access in your browser settings to use this feature.
              </p>
            </div>
          </div>
        )}

        {geoError && permission !== 'denied' && (
          <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
            <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
            <p className="text-sm text-red-800">{geoError.message}</p>
          </div>
        )}

        {storeLocationMutation.error && (
          <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
            <AlertTriangle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
            <div className="text-sm text-red-800">
              <p className="font-medium">Failed to save location.</p>
              <p className="mt-1 text-xs">
                {storeLocationMutation.error instanceof Error
                  ? storeLocationMutation.error.message
                  : 'Please try again.'}
              </p>
            </div>
          </div>
        )}

        {/* Current status */}
        {todayLoading ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="w-6 h-6 text-primary-600 animate-spin" aria-hidden="true" />
          </div>
        ) : isCheckedInToday && lastLocation ? (
          <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <div className="flex items-center gap-3">
              <CheckCircle className="w-6 h-6 text-green-600" aria-hidden="true" />
              <div className="flex-1">
                <p className="font-medium text-green-800">Checked in today</p>
                <p className="text-sm text-green-700">
                  {lastLocation.city && `${lastLocation.city}, `}
                  {lastLocation.countryName ?? 'Unknown location'}
                  {lastLocation.isSchengen && (
                    <span className="ml-2 inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                      <Globe className="w-3 h-3 mr-1" aria-hidden="true" />
                      Schengen
                    </span>
                  )}
                </p>
                <p className="text-xs text-green-600 mt-1">
                  at {new Date(lastLocation.recordedAt).toLocaleTimeString()}
                </p>
              </div>
            </div>
          </div>
        ) : (
          <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
            <div className="flex items-center gap-3">
              <Info className="w-5 h-5 text-gray-500" aria-hidden="true" />
              <p className="text-sm text-gray-600">
                You haven't checked in today. Check in to record your location for Schengen tracking.
              </p>
            </div>
          </div>
        )}

        {/* Check-in button */}
        <button
          onClick={handleCheckIn}
          disabled={isCheckingIn || !isSupported || permission === 'denied'}
          className={clsx(
            'w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition-colors',
            isCheckingIn || !isSupported || permission === 'denied'
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-primary-600 text-white hover:bg-primary-700'
          )}
        >
          {isCheckingIn ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" aria-hidden="true" />
              Getting your location...
            </>
          ) : (
            <>
              <Navigation className="w-5 h-5" aria-hidden="true" />
              {isCheckedInToday ? 'Update Location' : 'Check In Now'}
            </>
          )}
        </button>

        {/* Success message */}
        {storeLocationMutation.isSuccess && storeLocationMutation.data && (
          <div className="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div className="flex items-center gap-2 text-green-800">
              <CheckCircle className="w-5 h-5" aria-hidden="true" />
              <span className="font-medium">{storeLocationMutation.data.message}</span>
            </div>
          </div>
        )}

        {/* Info box */}
        <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-start gap-2">
            <Info className="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
            <div className="text-xs text-blue-700">
              <p className="font-medium mb-1">How location tracking works:</p>
              <ul className="space-y-1 list-disc list-inside">
                <li>Your location is detected using your browser's GPS</li>
                <li>We determine which country you're in automatically</li>
                <li>Schengen zone countries are tracked for the 90/180 rule</li>
                <li>You can view and delete your location history at any time</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Location History Modal */}
      <Modal
        isOpen={showHistory}
        onClose={() => setShowHistory(false)}
        title="Location History"
        size="lg"
      >
        <LocationHistoryView onClearRequest={() => setShowClearConfirm(true)} />
      </Modal>

      {/* Clear Confirmation Modal */}
      <Modal
        isOpen={showClearConfirm}
        onClose={() => setShowClearConfirm(false)}
        title="Clear Location History"
        size="sm"
      >
        <ClearHistoryConfirm
          onConfirm={() => {
            setShowClearConfirm(false);
            setShowHistory(false);
          }}
          onCancel={() => setShowClearConfirm(false)}
        />
      </Modal>
    </div>
  );
}

/**
 * Location History View Component
 */
function LocationHistoryView({ onClearRequest }: { onClearRequest: () => void }) {
  const { data, isLoading, refetch, isRefetching } = useSchengenLocationHistory({ limit: 50 });
  const deleteMutation = useDeleteSchengenLocation();

  const handleDelete = async (id: number) => {
    if (confirm('Delete this location entry?')) {
      await deleteMutation.mutateAsync(id);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <Loader2 className="w-6 h-6 text-primary-600 animate-spin" aria-hidden="true" />
      </div>
    );
  }

  const locations = data?.locations ?? [];

  return (
    <div className="space-y-4">
      {/* Actions */}
      <div className="flex items-center justify-between">
        <p className="text-sm text-gray-500">
          {data?.total ?? 0} location{(data?.total ?? 0) !== 1 ? 's' : ''} recorded
        </p>
        <div className="flex items-center gap-2">
          <button
            onClick={() => refetch()}
            disabled={isRefetching}
            className="inline-flex items-center gap-1 px-2 py-1 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors"
          >
            <RefreshCw
              className={clsx('w-4 h-4', isRefetching && 'animate-spin')}
              aria-hidden="true"
            />
            Refresh
          </button>
          {locations.length > 0 && (
            <button
              onClick={onClearRequest}
              className="inline-flex items-center gap-1 px-2 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
            >
              <Trash2 className="w-4 h-4" aria-hidden="true" />
              Clear All
            </button>
          )}
        </div>
      </div>

      {/* Location list */}
      {locations.length === 0 ? (
        <div className="text-center py-8">
          <MapPin className="w-12 h-12 text-gray-300 mx-auto mb-3" aria-hidden="true" />
          <p className="text-gray-500">No location history yet</p>
          <p className="text-sm text-gray-400">Check in to start recording your locations</p>
        </div>
      ) : (
        <div className="space-y-2 max-h-[400px] overflow-y-auto">
          {locations.map((location) => (
            <LocationHistoryItem
              key={location.id}
              location={location}
              onDelete={() => handleDelete(location.id)}
              isDeleting={deleteMutation.isPending}
            />
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * Single Location History Item
 */
function LocationHistoryItem({
  location,
  onDelete,
  isDeleting,
}: {
  location: SchengenLocation;
  onDelete: () => void;
  isDeleting: boolean;
}) {
  const date = new Date(location.recordedAt);

  return (
    <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg group">
      <div className="flex items-center gap-3">
        <div
          className={clsx(
            'p-2 rounded-lg',
            location.isSchengen ? 'bg-blue-100' : 'bg-gray-100'
          )}
        >
          {location.isSchengen ? (
            <Globe className="w-4 h-4 text-blue-600" aria-hidden="true" />
          ) : (
            <MapPin className="w-4 h-4 text-gray-500" aria-hidden="true" />
          )}
        </div>
        <div>
          <p className="font-medium text-gray-900 text-sm">
            {location.city && `${location.city}, `}
            {location.countryName ?? 'Unknown location'}
            {location.isSchengen && (
              <span className="ml-2 text-xs text-blue-600">(Schengen)</span>
            )}
          </p>
          <p className="text-xs text-gray-500">
            {date.toLocaleDateString()} at {date.toLocaleTimeString()}
            {location.accuracy && (
              <span className="ml-2">
                (±{Math.round(location.accuracy)}m)
              </span>
            )}
          </p>
        </div>
      </div>

      <button
        onClick={onDelete}
        disabled={isDeleting}
        className="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-600 transition-all"
        title="Delete"
      >
        <X className="w-4 h-4" aria-hidden="true" />
      </button>
    </div>
  );
}

/**
 * Clear History Confirmation
 */
function ClearHistoryConfirm({
  onConfirm,
  onCancel,
}: {
  onConfirm: () => void;
  onCancel: () => void;
}) {
  const clearMutation = useClearSchengenLocationHistory();

  const handleClear = async () => {
    await clearMutation.mutateAsync();
    onConfirm();
  };

  return (
    <div className="space-y-4">
      <p className="text-sm text-gray-600">
        Are you sure you want to delete all your location history? This action cannot be undone.
      </p>

      {clearMutation.error && (
        <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
          Failed to clear history. Please try again.
        </div>
      )}

      <div className="flex justify-end gap-3">
        <button
          onClick={onCancel}
          disabled={clearMutation.isPending}
          className="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          Cancel
        </button>
        <button
          onClick={handleClear}
          disabled={clearMutation.isPending}
          className={clsx(
            'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors',
            clearMutation.isPending
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-red-600 text-white hover:bg-red-700'
          )}
        >
          {clearMutation.isPending ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
              Clearing...
            </>
          ) : (
            <>
              <Trash2 className="w-4 h-4" aria-hidden="true" />
              Clear All History
            </>
          )}
        </button>
      </div>
    </div>
  );
}
