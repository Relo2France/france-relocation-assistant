/**
 * TripForm
 *
 * Form for adding/editing trips with jurisdiction support.
 */

import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import { Calendar, MapPin, Briefcase, FileText, AlertTriangle, Globe } from 'lucide-react';
import { SCHENGEN_COUNTRIES } from '@/types';
import type { SchengenTrip } from '@/types';
import { useTrackedJurisdictions } from '@/hooks/useApi';
import { wouldTripViolate, getTripDuration } from './schengenUtils';

interface TripFormProps {
  trip?: SchengenTrip;
  existingTrips: SchengenTrip[];
  onSubmit: (data: Omit<SchengenTrip, 'id' | 'createdAt' | 'updatedAt'>) => void;
  onCancel: () => void;
  className?: string;
  defaultJurisdiction?: string;
}

// Country lists for different jurisdiction types
const US_STATES = [
  'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut',
  'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa',
  'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan',
  'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
  'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio',
  'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
  'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia',
  'Wisconsin', 'Wyoming', 'District of Columbia'
] as const;

export default function TripForm({
  trip,
  existingTrips,
  onSubmit,
  onCancel,
  className,
  defaultJurisdiction = 'schengen',
}: TripFormProps) {
  const isEditing = !!trip;

  const [startDate, setStartDate] = useState(trip?.startDate || '');
  const [endDate, setEndDate] = useState(trip?.endDate || '');
  const [country, setCountry] = useState<string>(trip?.country || 'France');
  const [jurisdictionCode, setJurisdictionCode] = useState(trip?.jurisdictionCode || defaultJurisdiction);
  const [category, setCategory] = useState<'personal' | 'business'>(trip?.category || 'personal');
  const [notes, setNotes] = useState(trip?.notes || '');

  const { data: trackedJurisdictions } = useTrackedJurisdictions();

  const [warning, setWarning] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Get jurisdiction details
  const currentJurisdiction = trackedJurisdictions?.find(j => j.code === jurisdictionCode);
  const isSchengen = jurisdictionCode === 'schengen';
  const isUSState = jurisdictionCode?.startsWith('us_') && currentJurisdiction?.type === 'state';

  // Get country/location list based on jurisdiction
  const getLocationOptions = (): string[] => {
    if (isSchengen) {
      return [...SCHENGEN_COUNTRIES];
    }
    if (isUSState) {
      return [...US_STATES];
    }
    // For other jurisdictions, use the jurisdiction name as the location
    return currentJurisdiction ? [currentJurisdiction.name] : ['Unknown'];
  };

  // Update country when jurisdiction changes
  useEffect(() => {
    const options = getLocationOptions();
    if (!options.includes(country)) {
      setCountry(options[0] || '');
    }
  }, [jurisdictionCode]);

  // Validate trip against rule limits
  useEffect(() => {
    if (startDate && endDate) {
      // Only validate Schengen trips with the 90-day rule for now
      if (isSchengen) {
        // Filter out current trip if editing
        const otherTrips = isEditing
          ? existingTrips.filter((t) => t.id !== trip.id)
          : existingTrips;

        // Only include Schengen trips in validation
        const schengenTrips = otherTrips.filter(t => !t.jurisdictionCode || t.jurisdictionCode === 'schengen');
        const result = wouldTripViolate(schengenTrips, startDate, endDate);

        if (result.wouldViolate) {
          setWarning(result.message);
        } else if (result.projectedDaysUsed >= 80) {
          setWarning(`This trip will bring you to ${result.projectedDaysUsed}/90 days - approaching the limit.`);
        } else if (result.projectedDaysUsed >= 60) {
          setWarning(`This trip will bring you to ${result.projectedDaysUsed}/90 days.`);
        } else {
          setWarning(null);
        }
      } else if (currentJurisdiction) {
        // Basic validation for other jurisdictions
        const duration = getTripDuration({ startDate, endDate });
        if (duration > currentJurisdiction.daysAllowed) {
          setWarning(`This trip exceeds the ${currentJurisdiction.daysAllowed}-day limit for ${currentJurisdiction.name}.`);
        } else {
          setWarning(null);
        }
      } else {
        setWarning(null);
      }
    } else {
      setWarning(null);
    }
  }, [startDate, endDate, existingTrips, isEditing, trip, jurisdictionCode, currentJurisdiction, isSchengen]);

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

    // Check for excessively long trips based on jurisdiction
    const duration = getTripDuration({ startDate, endDate });
    const maxDays = currentJurisdiction?.daysAllowed || 90;

    if (duration > maxDays) {
      setError(`A single trip cannot exceed ${maxDays} days for ${currentJurisdiction?.name || 'this jurisdiction'}.`);
      return;
    }

    onSubmit({
      startDate,
      endDate,
      country,
      jurisdictionCode,
      category,
      notes: notes.trim() || undefined,
    });
  };

  const duration = startDate && endDate
    ? getTripDuration({ startDate, endDate })
    : null;

  const locationOptions = getLocationOptions();
  const showJurisdictionSelector = trackedJurisdictions && trackedJurisdictions.length > 1;

  return (
    <form onSubmit={handleSubmit} className={clsx('space-y-4', className)}>
      {/* Jurisdiction selector - only show if user tracks multiple jurisdictions */}
      {showJurisdictionSelector && (
        <div>
          <label
            htmlFor="jurisdiction"
            className="block text-sm font-medium text-gray-700 mb-1"
          >
            <Globe className="w-4 h-4 inline mr-1" aria-hidden="true" />
            Jurisdiction
          </label>
          <select
            id="jurisdiction"
            value={jurisdictionCode}
            onChange={(e) => setJurisdictionCode(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {trackedJurisdictions.map((j) => (
              <option key={j.code} value={j.code}>
                {j.name} ({j.daysAllowed}/{j.windowDays} days)
              </option>
            ))}
          </select>
          {currentJurisdiction?.description && (
            <p className="mt-1 text-xs text-gray-500">{currentJurisdiction.description}</p>
          )}
        </div>
      )}

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
          {currentJurisdiction && (
            <span className="text-gray-400 ml-2">
              (max {currentJurisdiction.daysAllowed} for {currentJurisdiction.name})
            </span>
          )}
        </div>
      )}

      {/* Country/Location selector */}
      <div>
        <label
          htmlFor="country"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          <MapPin className="w-4 h-4 inline mr-1" aria-hidden="true" />
          {isUSState ? 'State' : 'Country'}
        </label>
        <select
          id="country"
          value={country}
          onChange={(e) => setCountry(e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
          {locationOptions.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
      </div>

      {/* Category */}
      <div role="radiogroup" aria-labelledby="category-label">
        <span id="category-label" className="block text-sm font-medium text-gray-700 mb-1">
          <Briefcase className="w-4 h-4 inline mr-1" aria-hidden="true" />
          Category
        </span>
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
