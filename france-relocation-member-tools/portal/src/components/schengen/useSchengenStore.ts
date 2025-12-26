/**
 * Schengen Tracker Store Hook
 *
 * Provides a unified interface for managing Schengen trips using the API.
 * This replaces the previous localStorage implementation (Phase 1) with
 * server-side persistence (Phase 2).
 */

import { useMemo, useCallback } from 'react';
import type { SchengenTrip, SchengenAlertSettings } from '@/types';
import {
  useSchengenTrips,
  useSchengenSettings,
  useCreateSchengenTrip,
  useUpdateSchengenTrip,
  useDeleteSchengenTrip,
  useUpdateSchengenSettings,
} from '@/hooks/useApi';

/**
 * Default settings for new users
 */
const defaultSettings: SchengenAlertSettings = {
  yellowThreshold: 60,
  redThreshold: 80,
  emailAlerts: false,
  upcomingTripReminders: true,
};

/**
 * Hook for managing Schengen trips with API persistence
 */
export function useSchengenStore() {
  // Fetch trips and settings from API
  const {
    data: trips = [],
    isLoading: tripsLoading,
    isError: tripsError,
  } = useSchengenTrips();

  const {
    data: settings = defaultSettings,
    isLoading: settingsLoading,
  } = useSchengenSettings();

  // Mutations
  const createMutation = useCreateSchengenTrip();
  const updateMutation = useUpdateSchengenTrip();
  const deleteMutation = useDeleteSchengenTrip();
  const updateSettingsMutation = useUpdateSchengenSettings();

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
    (tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
      return createMutation.mutateAsync(tripData);
    },
    [createMutation]
  );

  /**
   * Update an existing trip
   */
  const updateTrip = useCallback(
    (id: string, updates: Partial<SchengenTrip>) => {
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
    (updates: Partial<SchengenAlertSettings>) => {
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
