/**
 * Schengen Tracker Local Storage Hook
 *
 * Provides localStorage persistence for trips during Phase 1.
 * Will be replaced with API calls in Phase 2.
 */

import { useState, useEffect, useCallback } from 'react';
import type { SchengenTrip, SchengenAlertSettings } from '@/types';
import { generateTripId } from './schengenUtils';

const STORAGE_KEY = 'framt_schengen_trips';
const SETTINGS_KEY = 'framt_schengen_settings';

const defaultSettings: SchengenAlertSettings = {
  yellowThreshold: 60,
  redThreshold: 80,
  emailAlerts: false,
  upcomingTripReminders: true,
};

/**
 * Load trips from localStorage
 */
function loadTrips(): SchengenTrip[] {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (e) {
    console.error('Failed to load Schengen trips from localStorage:', e);
  }
  return [];
}

/**
 * Save trips to localStorage
 */
function saveTrips(trips: SchengenTrip[]): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(trips));
  } catch (e) {
    console.error('Failed to save Schengen trips to localStorage:', e);
  }
}

/**
 * Load settings from localStorage
 */
function loadSettings(): SchengenAlertSettings {
  try {
    const stored = localStorage.getItem(SETTINGS_KEY);
    if (stored) {
      return { ...defaultSettings, ...JSON.parse(stored) };
    }
  } catch (e) {
    console.error('Failed to load Schengen settings from localStorage:', e);
  }
  return defaultSettings;
}

/**
 * Save settings to localStorage
 */
function saveSettings(settings: SchengenAlertSettings): void {
  try {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
  } catch (e) {
    console.error('Failed to save Schengen settings to localStorage:', e);
  }
}

/**
 * Hook for managing Schengen trips with localStorage persistence
 */
export function useSchengenStore() {
  const [trips, setTrips] = useState<SchengenTrip[]>([]);
  const [settings, setSettingsState] = useState<SchengenAlertSettings>(defaultSettings);
  const [isLoaded, setIsLoaded] = useState(false);

  // Load from localStorage on mount
  useEffect(() => {
    setTrips(loadTrips());
    setSettingsState(loadSettings());
    setIsLoaded(true);
  }, []);

  // Save trips whenever they change (after initial load)
  useEffect(() => {
    if (isLoaded) {
      saveTrips(trips);
    }
  }, [trips, isLoaded]);

  // Save settings whenever they change (after initial load)
  useEffect(() => {
    if (isLoaded) {
      saveSettings(settings);
    }
  }, [settings, isLoaded]);

  /**
   * Add a new trip
   */
  const addTrip = useCallback((tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
    const now = new Date().toISOString();
    const newTrip: SchengenTrip = {
      ...tripData,
      id: generateTripId(),
      createdAt: now,
      updatedAt: now,
    };

    setTrips((prev) => [...prev, newTrip].sort((a, b) =>
      new Date(b.startDate).getTime() - new Date(a.startDate).getTime()
    ));

    return newTrip;
  }, []);

  /**
   * Update an existing trip
   */
  const updateTrip = useCallback((id: string, updates: Partial<SchengenTrip>) => {
    setTrips((prev) =>
      prev.map((trip) =>
        trip.id === id
          ? { ...trip, ...updates, updatedAt: new Date().toISOString() }
          : trip
      ).sort((a, b) =>
        new Date(b.startDate).getTime() - new Date(a.startDate).getTime()
      )
    );
  }, []);

  /**
   * Delete a trip
   */
  const deleteTrip = useCallback((id: string) => {
    setTrips((prev) => prev.filter((trip) => trip.id !== id));
  }, []);

  /**
   * Update settings
   */
  const updateSettings = useCallback((updates: Partial<SchengenAlertSettings>) => {
    setSettingsState((prev) => ({ ...prev, ...updates }));
  }, []);

  /**
   * Get trips sorted by date (most recent first)
   */
  const sortedTrips = trips.sort((a, b) =>
    new Date(b.startDate).getTime() - new Date(a.startDate).getTime()
  );

  return {
    trips: sortedTrips,
    settings,
    isLoaded,
    addTrip,
    updateTrip,
    deleteTrip,
    updateSettings,
  };
}
