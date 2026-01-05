/**
 * MyTravelStatus Store Hook
 *
 * Provides a unified interface for managing travel status trips using the API.
 * This replaces the previous localStorage implementation (Phase 1) with
 * server-side persistence (Phase 2).
 */

import { useCallback, useMemo } from 'react';
import {
  useCreateTravelStatusTrip,
  useDeleteTravelStatusTrip,
  useTravelStatusSettings,
  useTravelStatusTrips,
  useUpdateTravelStatusSettings,
  useUpdateTravelStatusTrip,
} from '@/hooks/useApi';
import type { TravelStatusAlertSettings, TravelStatusTrip } from '@/types';

/**
 * Default settings for new users
 */
const defaultSettings: TravelStatusAlertSettings = {
  yellowThreshold: 60,
  redThreshold: 80,
  emailAlerts: false,
  upcomingTripReminders: true,
};

/**
 * Hook for managing travel status trips with API persistence
 */
export function useTravelStatusStore() {
  // Fetch trips and settings from API
  const {
    data: trips = [],
    isLoading: tripsLoading,
    isError: tripsError,
  } = useTravelStatusTrips();

  const {
    data: settings = defaultSettings,
    isLoading: settingsLoading,
  } = useTravelStatusSettings();

  // Mutations
  const createMutation = useCreateTravelStatusTrip();
  const updateMutation = useUpdateTravelStatusTrip();
  const deleteMutation = useDeleteTravelStatusTrip();
  const updateSettingsMutation = useUpdateTravelStatusSettings();

  // Combined loading state
  const isLoaded = !tripsLoading && !settingsLoading;
  const isError = tripsError;

  // Sort trips by start date (most recent first)
  const sortedTrips = useMemo(() => {
    return [...trips].sort((a, b) =>
      new Date(b.startDate).getTime() - new Date(a.startDate).getTime()
    );
  }, [trips]);

  /**
   * Add a new trip
   */
  const addTrip = useCallback(
    (tripData: Omit<TravelStatusTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
      return createMutation.mutateAsync(tripData);
    },
    [createMutation]
  );

  /**
   * Update an existing trip
   */
  const updateTrip = useCallback(
    (id: string, updates: Partial<TravelStatusTrip>) => {
      return updateMutation.mutateAsync({ id, data: updates });
    },
    [updateMutation]
  );

  /**
   * Delete a trip
   */
  const deleteTrip = useCallback(
    (id: string) => {
      return deleteMutation.mutateAsync(id);
    },
    [deleteMutation]
  );

  /**
   * Update settings
   */
  const updateSettings = useCallback(
    (updates: Partial<TravelStatusAlertSettings>) => {
      return updateSettingsMutation.mutateAsync(updates);
    },
    [updateSettingsMutation]
  );

  // Check if any mutation is in progress
  const isSaving =
    createMutation.isPending ||
    updateMutation.isPending ||
    deleteMutation.isPending ||
    updateSettingsMutation.isPending;

  return {
    trips: sortedTrips,
    settings,
    isLoaded,
    isError,
    isSaving,
    addTrip,
    updateTrip,
    deleteTrip,
    updateSettings,
  };
}
