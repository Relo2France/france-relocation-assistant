/**
 * LocationDetectionBanner
 *
 * Smart banner that prompts users to check in when:
 * - Timezone change is detected (potential travel)
 * - Daily reminder time has arrived
 * - IP-based detection suggests a different country
 *
 * @package R2F_Member_Tools
 * @since   2.2.0
 */

import { useState, useCallback, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  MapPin,
  Navigation,
  AlertCircle,
  X,
  Loader2,
  Globe,
  Plane,
  Clock,
  CheckCircle,
} from 'lucide-react';
import { useLocationDetection } from '@/hooks/useLocationDetection';
import { useIPDetection, useStoreSchengenLocation } from '@/hooks/useApi';
import { useGeolocation } from '@/hooks/useGeolocation';

interface LocationDetectionBannerProps {
  /** Whether the banner is enabled */
  enabled?: boolean;
  /** Callback when user checks in */
  onCheckin?: () => void;
  /** Callback when user dismisses the banner */
  onDismiss?: () => void;
}

/**
 * Country flag emoji helper
 */
function getCountryFlag(countryCode: string): string {
  // Convert country code to regional indicator symbols
  const codePoints = countryCode
    .toUpperCase()
    .split('')
    .map((char) => 127397 + char.charCodeAt(0));
  return String.fromCodePoint(...codePoints);
}

export default function LocationDetectionBanner({
  enabled = true,
  onCheckin,
  onDismiss,
}: LocationDetectionBannerProps) {
  const [checkinSuccess, setCheckinSuccess] = useState(false);

  const {
    state: locationState,
    markCheckedIn,
    dismissPrompt,
  } = useLocationDetection({ enableReminder: enabled });

  const { data: ipData } = useIPDetection();
  const { getCurrentPosition, isLoading: geoLoading, isSupported } = useGeolocation();
  const storeLocationMutation = useStoreSchengenLocation();

  // Reset success state after showing
  useEffect(() => {
    if (checkinSuccess) {
      const timer = setTimeout(() => setCheckinSuccess(false), 3000);
      return () => clearTimeout(timer);
    }
  }, [checkinSuccess]);

  const handleCheckin = useCallback(async () => {
    try {
      const pos = await getCurrentPosition();
      await storeLocationMutation.mutateAsync({
        lat: pos.lat,
        lng: pos.lng,
        accuracy: pos.accuracy,
        source: 'checkin',
      });
      markCheckedIn();
      setCheckinSuccess(true);
      onCheckin?.();
    } catch (err) {
      console.error('Check-in failed:', err);
    }
  }, [getCurrentPosition, storeLocationMutation, markCheckedIn, onCheckin]);

  const handleDismiss = useCallback(() => {
    dismissPrompt();
    onDismiss?.();
  }, [dismissPrompt, onDismiss]);

  // Don't show if disabled or already checked in today
  if (!enabled || !locationState.shouldPromptCheckin || locationState.checkedInToday) {
    return null;
  }

  const isLoading = geoLoading || storeLocationMutation.isPending;

  // Determine banner type and content
  let bannerIcon = Clock;
  let bannerTitle = 'Daily Location Check-in';
  let bannerMessage = "Don't forget to check in your location for accurate Schengen tracking.";
  let bannerColor = 'blue';

  // Priority 1: Timezone change detected
  if (locationState.timezoneChanged) {
    bannerIcon = Plane;
    bannerTitle = 'Travel Detected';
    bannerMessage = `Your timezone changed from ${locationState.storedTimezone} to ${locationState.currentTimezone}. Are you traveling?`;
    bannerColor = 'amber';
  }
  // Priority 2: IP-based detection shows different country
  else if (
    ipData?.detected &&
    ipData.countryCode &&
    locationState.likelyCountry &&
    ipData.countryCode !== locationState.likelyCountry
  ) {
    bannerIcon = Globe;
    bannerTitle = 'Location Check';
    bannerMessage = `Your IP suggests you're in ${ipData.countryName || ipData.countryCode}. Check in to confirm your location.`;
    bannerColor = 'purple';
  }

  // Show success message
  if (checkinSuccess) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-green-100 rounded-lg">
            <CheckCircle className="w-5 h-5 text-green-600" aria-hidden="true" />
          </div>
          <div>
            <p className="font-medium text-green-800">Location recorded!</p>
            <p className="text-sm text-green-700">Your location has been saved for Schengen tracking.</p>
          </div>
        </div>
      </div>
    );
  }

  const Icon = bannerIcon;
  const colorClasses = {
    blue: {
      bg: 'bg-blue-50',
      border: 'border-blue-200',
      icon: 'bg-blue-100 text-blue-600',
      title: 'text-blue-800',
      message: 'text-blue-700',
      button: 'bg-blue-600 hover:bg-blue-700 text-white',
      dismiss: 'text-blue-500 hover:text-blue-700 hover:bg-blue-100',
    },
    amber: {
      bg: 'bg-amber-50',
      border: 'border-amber-200',
      icon: 'bg-amber-100 text-amber-600',
      title: 'text-amber-800',
      message: 'text-amber-700',
      button: 'bg-amber-600 hover:bg-amber-700 text-white',
      dismiss: 'text-amber-500 hover:text-amber-700 hover:bg-amber-100',
    },
    purple: {
      bg: 'bg-purple-50',
      border: 'border-purple-200',
      icon: 'bg-purple-100 text-purple-600',
      title: 'text-purple-800',
      message: 'text-purple-700',
      button: 'bg-purple-600 hover:bg-purple-700 text-white',
      dismiss: 'text-purple-500 hover:text-purple-700 hover:bg-purple-100',
    },
  };

  const colors = colorClasses[bannerColor as keyof typeof colorClasses];

  return (
    <div className={clsx('border rounded-lg p-4 mb-4', colors.bg, colors.border)}>
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <div className={clsx('p-2 rounded-lg', colors.icon)}>
            <Icon className="w-5 h-5" aria-hidden="true" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <p className={clsx('font-medium', colors.title)}>{bannerTitle}</p>
              {ipData?.detected && ipData.countryCode && (
                <span className="text-lg" role="img" aria-label={ipData.countryName || ipData.countryCode}>
                  {getCountryFlag(ipData.countryCode)}
                </span>
              )}
            </div>
            <p className={clsx('text-sm mt-0.5', colors.message)}>{bannerMessage}</p>

            {/* Show detected location info */}
            {locationState.likelySchengen && (
              <div className="flex items-center gap-1 mt-2 text-xs">
                <Globe className="w-3 h-3 text-blue-500" aria-hidden="true" />
                <span className="text-blue-600">
                  Based on timezone: Likely in Schengen zone ({locationState.likelyCountry})
                </span>
              </div>
            )}

            {/* Action buttons */}
            <div className="flex items-center gap-3 mt-3">
              <button
                onClick={handleCheckin}
                disabled={isLoading || !isSupported}
                className={clsx(
                  'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                  isLoading || !isSupported ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : colors.button
                )}
              >
                {isLoading ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                    Getting location...
                  </>
                ) : (
                  <>
                    <Navigation className="w-4 h-4" aria-hidden="true" />
                    Check In Now
                  </>
                )}
              </button>

              <button
                onClick={handleDismiss}
                className={clsx(
                  'px-3 py-2 rounded-lg text-sm transition-colors',
                  colors.dismiss
                )}
              >
                Maybe later
              </button>
            </div>

            {/* Error state */}
            {storeLocationMutation.error && (
              <div className="flex items-center gap-2 mt-3 text-sm text-red-600">
                <AlertCircle className="w-4 h-4" aria-hidden="true" />
                Failed to save location. Please try again.
              </div>
            )}

            {/* Geolocation not supported */}
            {!isSupported && (
              <div className="flex items-center gap-2 mt-3 text-xs text-gray-500">
                <AlertCircle className="w-3 h-3" aria-hidden="true" />
                Geolocation is not supported by your browser.
              </div>
            )}
          </div>
        </div>

        {/* Close button */}
        <button
          onClick={handleDismiss}
          className={clsx('p-1 rounded transition-colors', colors.dismiss)}
          aria-label="Dismiss"
        >
          <X className="w-4 h-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  );
}

/**
 * Compact version of the detection banner for smaller spaces
 */
export function LocationDetectionBannerCompact({
  enabled = true,
  onCheckin,
  onDismiss,
}: LocationDetectionBannerProps) {
  const {
    state: locationState,
    markCheckedIn,
    dismissPrompt,
  } = useLocationDetection({ enableReminder: enabled });

  const { data: ipData } = useIPDetection();
  const { getCurrentPosition, isLoading: geoLoading, isSupported } = useGeolocation();
  const storeLocationMutation = useStoreSchengenLocation();

  const handleCheckin = useCallback(async () => {
    try {
      const pos = await getCurrentPosition();
      await storeLocationMutation.mutateAsync({
        lat: pos.lat,
        lng: pos.lng,
        accuracy: pos.accuracy,
        source: 'checkin',
      });
      markCheckedIn();
      onCheckin?.();
    } catch (err) {
      console.error('Check-in failed:', err);
    }
  }, [getCurrentPosition, storeLocationMutation, markCheckedIn, onCheckin]);

  const handleDismiss = useCallback(() => {
    dismissPrompt();
    onDismiss?.();
  }, [dismissPrompt, onDismiss]);

  if (!enabled || !locationState.shouldPromptCheckin || locationState.checkedInToday) {
    return null;
  }

  const isLoading = geoLoading || storeLocationMutation.isPending;
  const isTimezoneChange = locationState.timezoneChanged;

  return (
    <div
      className={clsx(
        'flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-sm',
        isTimezoneChange ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200'
      )}
    >
      <div className="flex items-center gap-2">
        {isTimezoneChange ? (
          <Plane
            className="w-4 h-4 text-amber-600 flex-shrink-0"
            aria-hidden="true"
          />
        ) : (
          <MapPin
            className="w-4 h-4 text-blue-600 flex-shrink-0"
            aria-hidden="true"
          />
        )}
        <span className={isTimezoneChange ? 'text-amber-800' : 'text-blue-800'}>
          {isTimezoneChange
            ? 'Timezone change detected'
            : ipData?.countryName
              ? `In ${ipData.countryName}?`
              : 'Check in today'}
        </span>
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={handleCheckin}
          disabled={isLoading || !isSupported}
          className={clsx(
            'inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium transition-colors',
            isLoading || !isSupported
              ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
              : isTimezoneChange
                ? 'bg-amber-600 text-white hover:bg-amber-700'
                : 'bg-blue-600 text-white hover:bg-blue-700'
          )}
        >
          {isLoading ? (
            <Loader2 className="w-3 h-3 animate-spin" aria-hidden="true" />
          ) : (
            <Navigation className="w-3 h-3" aria-hidden="true" />
          )}
          Check In
        </button>
        <button
          onClick={handleDismiss}
          className="p-1 text-gray-400 hover:text-gray-600"
          aria-label="Dismiss"
        >
          <X className="w-3 h-3" aria-hidden="true" />
        </button>
      </div>
    </div>
  );
}
