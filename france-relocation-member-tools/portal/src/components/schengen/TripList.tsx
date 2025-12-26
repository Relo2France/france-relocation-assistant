/**
 * TripList
 *
 * Sortable list of all Schengen trips with edit/delete actions.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import { Pencil, Trash2, MapPin, Calendar, ChevronDown, ChevronUp } from 'lucide-react';
import type { SchengenTrip } from '@/types';
import { formatDateRange, getTripDuration } from './schengenUtils';

interface TripListProps {
  trips: SchengenTrip[];
  currentWindowStart: string;
  onEdit: (trip: SchengenTrip) => void;
  onDelete: (id: string) => void;
  className?: string;
}

type SortField = 'date' | 'country' | 'duration';
type SortDirection = 'asc' | 'desc';

export default function TripList({
  trips,
  currentWindowStart,
  onEdit,
  onDelete,
  className,
}: TripListProps) {
  const [sortField, setSortField] = useState<SortField>('date');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');
  const [deleteConfirmId, setDeleteConfirmId] = useState<string | null>(null);

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'));
    } else {
      setSortField(field);
      setSortDirection('desc');
    }
  };

  const sortedTrips = [...trips].sort((a, b) => {
    let comparison = 0;

    switch (sortField) {
      case 'date':
        comparison = new Date(a.startDate).getTime() - new Date(b.startDate).getTime();
        break;
      case 'country':
        comparison = a.country.localeCompare(b.country);
        break;
      case 'duration':
        comparison = getTripDuration(a) - getTripDuration(b);
        break;
    }

    return sortDirection === 'asc' ? comparison : -comparison;
  });

  const isInCurrentWindow = (trip: SchengenTrip) => {
    return new Date(trip.endDate) >= new Date(currentWindowStart);
  };

  const handleDeleteClick = (id: string) => {
    if (deleteConfirmId === id) {
      onDelete(id);
      setDeleteConfirmId(null);
    } else {
      setDeleteConfirmId(id);
      // Auto-cancel confirmation after 3 seconds
      setTimeout(() => setDeleteConfirmId(null), 3000);
    }
  };

  if (trips.length === 0) {
    return (
      <div className={clsx('text-center py-12', className)}>
        <MapPin className="w-12 h-12 text-gray-300 mx-auto mb-4" aria-hidden="true" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">No trips recorded</h3>
        <p className="text-gray-500">Add your first trip to start tracking your Schengen days.</p>
      </div>
    );
  }

  const SortIcon = ({ field }: { field: SortField }) => {
    if (sortField !== field) return null;
    return sortDirection === 'asc' ? (
      <ChevronUp className="w-4 h-4" />
    ) : (
      <ChevronDown className="w-4 h-4" />
    );
  };

  return (
    <div className={clsx('overflow-hidden', className)}>
      {/* Table header */}
      <div className="grid grid-cols-12 gap-4 px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-medium text-gray-600">
        <button
          onClick={() => handleSort('date')}
          className="col-span-4 flex items-center gap-1 text-left hover:text-gray-900"
        >
          <Calendar className="w-4 h-4" aria-hidden="true" />
          Dates
          <SortIcon field="date" />
        </button>
        <button
          onClick={() => handleSort('country')}
          className="col-span-3 flex items-center gap-1 text-left hover:text-gray-900"
        >
          <MapPin className="w-4 h-4" aria-hidden="true" />
          Country
          <SortIcon field="country" />
        </button>
        <button
          onClick={() => handleSort('duration')}
          className="col-span-2 flex items-center gap-1 text-left hover:text-gray-900"
        >
          Days
          <SortIcon field="duration" />
        </button>
        <div className="col-span-3 text-right">Actions</div>
      </div>

      {/* Trip rows */}
      <div className="divide-y divide-gray-100">
        {sortedTrips.map((trip) => {
          const duration = getTripDuration(trip);
          const inWindow = isInCurrentWindow(trip);

          return (
            <div
              key={trip.id}
              className={clsx(
                'grid grid-cols-12 gap-4 px-4 py-3 items-center',
                inWindow ? 'bg-white' : 'bg-gray-50 opacity-75'
              )}
            >
              {/* Dates */}
              <div className="col-span-4">
                <p className="text-sm font-medium text-gray-900">
                  {formatDateRange(trip.startDate, trip.endDate)}
                </p>
                {trip.notes && (
                  <p className="text-xs text-gray-500 truncate mt-0.5">{trip.notes}</p>
                )}
              </div>

              {/* Country */}
              <div className="col-span-3">
                <span className="text-sm text-gray-700">{trip.country}</span>
                <span
                  className={clsx(
                    'ml-2 text-xs px-1.5 py-0.5 rounded',
                    trip.category === 'business'
                      ? 'bg-blue-100 text-blue-700'
                      : 'bg-gray-100 text-gray-600'
                  )}
                >
                  {trip.category}
                </span>
              </div>

              {/* Duration */}
              <div className="col-span-2">
                <span className="text-sm text-gray-700">
                  {duration} day{duration !== 1 ? 's' : ''}
                </span>
                {!inWindow && (
                  <span className="block text-xs text-gray-400">outside window</span>
                )}
              </div>

              {/* Actions */}
              <div className="col-span-3 flex justify-end gap-2">
                <button
                  onClick={() => onEdit(trip)}
                  className="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                  aria-label={`Edit trip to ${trip.country}`}
                >
                  <Pencil className="w-4 h-4" />
                </button>
                <button
                  onClick={() => handleDeleteClick(trip.id)}
                  className={clsx(
                    'p-2 rounded-lg transition-colors',
                    deleteConfirmId === trip.id
                      ? 'text-white bg-red-600 hover:bg-red-700'
                      : 'text-gray-400 hover:text-red-600 hover:bg-red-50'
                  )}
                  aria-label={
                    deleteConfirmId === trip.id
                      ? 'Confirm delete'
                      : `Delete trip to ${trip.country}`
                  }
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
