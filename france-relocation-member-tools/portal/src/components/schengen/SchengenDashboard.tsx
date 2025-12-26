/**
 * SchengenDashboard
 *
 * Main dashboard view for the Schengen 90/180 day tracker.
 * Shows compliance status, day counter, and trip management.
 */

import { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import { Plus, Calendar, Clock, AlertTriangle, Info } from 'lucide-react';
import type { SchengenTrip } from '@/types';
import { useSchengenStore } from './useSchengenStore';
import {
  getSchengenSummary,
  formatDate,
} from './schengenUtils';
import DayCounter from './DayCounter';
import StatusBadge from './StatusBadge';
import TripForm from './TripForm';
import TripList from './TripList';
import Modal from '@/components/shared/Modal';

export default function SchengenDashboard() {
  const { trips, settings, isLoaded, addTrip, updateTrip, deleteTrip } = useSchengenStore();
  const [showTripForm, setShowTripForm] = useState(false);
  const [editingTrip, setEditingTrip] = useState<SchengenTrip | null>(null);

  // Calculate summary from trips
  const summary = useMemo(() => {
    return getSchengenSummary(trips, {
      yellow: settings.yellowThreshold,
      red: settings.redThreshold,
    });
  }, [trips, settings.yellowThreshold, settings.redThreshold]);

  const handleAddTrip = async (tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
    try {
      await addTrip(tripData);
      setShowTripForm(false);
    } catch (error) {
      console.error('Failed to add trip:', error);
    }
  };

  const handleEditTrip = async (tripData: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => {
    if (editingTrip) {
      try {
        await updateTrip(editingTrip.id, tripData);
        setEditingTrip(null);
      } catch (error) {
        console.error('Failed to update trip:', error);
      }
    }
  };

  const handleDeleteTrip = async (id: string) => {
    try {
      await deleteTrip(id);
    } catch (error) {
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

  if (!isLoaded) {
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
        <button
          onClick={() => setShowTripForm(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
        >
          <Plus className="w-5 h-5" aria-hidden="true" />
          Add Trip
        </button>
      </div>

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

      {/* Warning banner if approaching limit */}
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

      {/* Trip list */}
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
    </div>
  );
}
