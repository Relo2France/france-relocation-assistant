/**
 * TripForm
 *
 * Form for adding/editing Schengen trips.
 */

import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import { X, Calendar, MapPin, Briefcase, FileText, AlertTriangle } from 'lucide-react';
import { SCHENGEN_COUNTRIES } from '@/types';
import type { SchengenTrip, SchengenCountry } from '@/types';
import { wouldTripViolate, getTripDuration } from './schengenUtils';

interface TripFormProps {
  trip?: SchengenTrip;
  existingTrips: SchengenTrip[];
  onSubmit: (data: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => void;
  onCancel: () => void;
  className?: string;
}

export default function TripForm({
  trip,
  existingTrips,
  onSubmit,
  onCancel,
  className,
}: TripFormProps) {
  const isEditing = !!trip;

  const [startDate, setStartDate] = useState(trip?.startDate || '');
  const [endDate, setEndDate] = useState(trip?.endDate || '');
  const [country, setCountry] = useState<SchengenCountry>(trip?.country || 'France');
  const [category, setCategory] = useState<'personal' | 'business'>(trip?.category || 'personal');
  const [notes, setNotes] = useState(trip?.notes || '');

  const [warning, setWarning] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Validate trip against 90-day rule
  useEffect(() => {
    if (startDate && endDate) {
      // Filter out current trip if editing
      const otherTrips = isEditing
        ? existingTrips.filter((t) => t.id !== trip.id)
        : existingTrips;

      const result = wouldTripViolate(otherTrips, startDate, endDate);

      if (result.wouldViolate) {
        setWarning(result.message);
      } else if (result.projectedDaysUsed >= 80) {
        setWarning(`This trip will bring you to ${result.projectedDaysUsed}/90 days - approaching the limit.`);
      } else if (result.projectedDaysUsed >= 60) {
        setWarning(`This trip will bring you to ${result.projectedDaysUsed}/90 days.`);
      } else {
        setWarning(null);
      }
    } else {
      setWarning(null);
    }
  }, [startDate, endDate, existingTrips, isEditing, trip]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    // Validate required fields
    if (!startDate || !endDate) {
      setError('Please enter both start and end dates.');
      return;
    }

    // Validate date order
    if (new Date(endDate) < new Date(startDate)) {
      setError('End date must be on or after start date.');
      return;
    }

    // Check for excessively long trips
    const duration = getTripDuration({
      startDate,
      endDate,
    } as SchengenTrip);

    if (duration > 90) {
      setError('A single trip cannot exceed 90 days.');
      return;
    }

    onSubmit({
      startDate,
      endDate,
      country,
      category,
      notes: notes.trim() || undefined,
    });
  };

  const duration = startDate && endDate
    ? getTripDuration({ startDate, endDate } as SchengenTrip)
    : null;

  return (
    <form onSubmit={handleSubmit} className={clsx('space-y-4', className)}>
      {/* Header */}
      <div className="flex items-center justify-between pb-4 border-b border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900">
          {isEditing ? 'Edit Trip' : 'Add Trip'}
        </h3>
        <button
          type="button"
          onClick={onCancel}
          className="text-gray-400 hover:text-gray-600 transition-colors"
          aria-label="Close"
        >
          <X className="w-5 h-5" />
        </button>
      </div>

      {/* Date fields */}
      <div className="grid grid-cols-2 gap-4">
        <div>
          <label
            htmlFor="startDate"
            className="block text-sm font-medium text-gray-700 mb-1"
          >
            <Calendar className="w-4 h-4 inline mr-1" aria-hidden="true" />
            Start Date
          </label>
          <input
            type="date"
            id="startDate"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
          />
        </div>
        <div>
          <label
            htmlFor="endDate"
            className="block text-sm font-medium text-gray-700 mb-1"
          >
            <Calendar className="w-4 h-4 inline mr-1" aria-hidden="true" />
            End Date
          </label>
          <input
            type="date"
            id="endDate"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            min={startDate}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
          />
        </div>
      </div>

      {/* Duration display */}
      {duration !== null && (
        <div className="text-sm text-gray-600 bg-gray-50 px-3 py-2 rounded-lg">
          Duration: <span className="font-medium">{duration} day{duration !== 1 ? 's' : ''}</span>
        </div>
      )}

      {/* Country selector */}
      <div>
        <label
          htmlFor="country"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          <MapPin className="w-4 h-4 inline mr-1" aria-hidden="true" />
          Country
        </label>
        <select
          id="country"
          value={country}
          onChange={(e) => setCountry(e.target.value as SchengenCountry)}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
          {SCHENGEN_COUNTRIES.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
      </div>

      {/* Category */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          <Briefcase className="w-4 h-4 inline mr-1" aria-hidden="true" />
          Category
        </label>
        <div className="flex gap-4">
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              name="category"
              value="personal"
              checked={category === 'personal'}
              onChange={() => setCategory('personal')}
              className="text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-gray-700">Personal</span>
          </label>
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              name="category"
              value="business"
              checked={category === 'business'}
              onChange={() => setCategory('business')}
              className="text-primary-600 focus:ring-primary-500"
            />
            <span className="text-sm text-gray-700">Business</span>
          </label>
        </div>
      </div>

      {/* Notes */}
      <div>
        <label
          htmlFor="notes"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          <FileText className="w-4 h-4 inline mr-1" aria-hidden="true" />
          Notes (optional)
        </label>
        <textarea
          id="notes"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={2}
          placeholder="e.g., Visa appointment, apartment hunting..."
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
        />
      </div>

      {/* Warning message */}
      {warning && (
        <div className="flex gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
          <AlertTriangle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <p className="text-sm text-amber-800">{warning}</p>
        </div>
      )}

      {/* Error message */}
      {error && (
        <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-sm text-red-800">{error}</p>
        </div>
      )}

      {/* Buttons */}
      <div className="flex gap-3 pt-4 border-t border-gray-200">
        <button
          type="button"
          onClick={onCancel}
          className="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors"
        >
          Cancel
        </button>
        <button
          type="submit"
          className="flex-1 px-4 py-2 text-white bg-primary-600 hover:bg-primary-700 rounded-lg font-medium transition-colors"
        >
          {isEditing ? 'Save Changes' : 'Add Trip'}
        </button>
      </div>
    </form>
  );
}
