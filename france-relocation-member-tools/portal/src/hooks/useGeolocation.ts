/**
 * useGeolocation Hook
 *
 * Provides access to the browser's Geolocation API with state management,
 * error handling, and permission status tracking.
 *
 * @package R2F_Member_Tools
 * @since   2.2.0
 */

import { useState, useCallback, useEffect } from 'react';

export type GeolocationPermission = 'prompt' | 'granted' | 'denied' | 'unavailable';

export interface GeolocationPosition {
  lat: number;
  lng: number;
  accuracy: number;
  timestamp: number;
}

export interface GeolocationError {
  code: number;
  message: string;
}

export interface UseGeolocationOptions {
  enableHighAccuracy?: boolean;
  timeout?: number;
  maximumAge?: number;
}

export interface UseGeolocationResult {
  /** Current position if available */
  position: GeolocationPosition | null;
  /** Error if geolocation failed */
  error: GeolocationError | null;
  /** Whether geolocation is currently being fetched */
  isLoading: boolean;
  /** Browser permission status */
  permission: GeolocationPermission;
  /** Whether geolocation is supported by the browser */
  isSupported: boolean;
  /** Request current position */
  getCurrentPosition: () => Promise<GeolocationPosition>;
  /** Clear stored position */
  clearPosition: () => void;
}

const DEFAULT_OPTIONS: UseGeolocationOptions = {
  enableHighAccuracy: true,
  timeout: 10000, // 10 seconds
  maximumAge: 60000, // 1 minute cache
};

/**
 * Custom hook for browser geolocation
 */
export function useGeolocation(options: UseGeolocationOptions = {}): UseGeolocationResult {
  const mergedOptions = { ...DEFAULT_OPTIONS, ...options };

  const [position, setPosition] = useState<GeolocationPosition | null>(null);
  const [error, setError] = useState<GeolocationError | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [permission, setPermission] = useState<GeolocationPermission>('prompt');

  // Check if geolocation is supported
  const isSupported = typeof navigator !== 'undefined' && 'geolocation' in navigator;

  // Check permission status on mount
  useEffect(() => {
    if (!isSupported) {
      setPermission('unavailable');
      return;
    }

    // Check permission status if available
    if ('permissions' in navigator) {
      navigator.permissions
        .query({ name: 'geolocation' })
        .then((result) => {
          setPermission(result.state as GeolocationPermission);

          // Listen for permission changes
          result.addEventListener('change', () => {
            setPermission(result.state as GeolocationPermission);
          });
        })
        .catch(() => {
          // Permissions API not supported, keep as 'prompt'
          setPermission('prompt');
        });
    }
  }, [isSupported]);

  /**
   * Get current position using the Geolocation API
   */
  const getCurrentPosition = useCallback((): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
      if (!isSupported) {
        const err: GeolocationError = {
          code: 0,
          message: 'Geolocation is not supported by this browser.',
        };
        setError(err);
        reject(err);
        return;
      }

      setIsLoading(true);
      setError(null);

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const newPosition: GeolocationPosition = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
            timestamp: pos.timestamp,
          };

          setPosition(newPosition);
          setError(null);
          setIsLoading(false);
          setPermission('granted');
          resolve(newPosition);
        },
        (err) => {
          const geoError: GeolocationError = {
            code: err.code,
            message: getErrorMessage(err.code),
          };

          setError(geoError);
          setIsLoading(false);

          if (err.code === err.PERMISSION_DENIED) {
            setPermission('denied');
          }

          reject(geoError);
        },
        {
          enableHighAccuracy: mergedOptions.enableHighAccuracy,
          timeout: mergedOptions.timeout,
          maximumAge: mergedOptions.maximumAge,
        }
      );
    });
  }, [isSupported, mergedOptions.enableHighAccuracy, mergedOptions.timeout, mergedOptions.maximumAge]);

  /**
   * Clear stored position
   */
  const clearPosition = useCallback(() => {
    setPosition(null);
    setError(null);
  }, []);

  return {
    position,
    error,
    isLoading,
    permission,
    isSupported,
    getCurrentPosition,
    clearPosition,
  };
}

/**
 * Get user-friendly error message
 */
function getErrorMessage(code: number): string {
  switch (code) {
    case 1: // PERMISSION_DENIED
      return 'Location permission denied. Please enable location access in your browser settings.';
    case 2: // POSITION_UNAVAILABLE
      return 'Unable to determine your location. Please try again.';
    case 3: // TIMEOUT
      return 'Location request timed out. Please try again.';
    default:
      return 'An unknown error occurred while getting your location.';
  }
}

export default useGeolocation;
