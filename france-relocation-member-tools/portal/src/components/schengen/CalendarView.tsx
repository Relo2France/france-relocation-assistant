/**
 * CalendarView
 *
 * Monthly calendar visualization for Schengen trips.
 * Shows days spent in Schengen zone with color coding.
 */

import { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import { ChevronLeft, ChevronRight, Plane } from 'lucide-react';
import type { SchengenTrip } from '@/types';

interface CalendarViewProps {
  trips: SchengenTrip[];
  windowStart: string;
  windowEnd: string;
}

interface DayInfo {
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
  isInWindow: boolean;
  trip: SchengenTrip | null;
  isFuture: boolean;
}

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

export default function CalendarView({ trips, windowStart, windowEnd }: CalendarViewProps) {
  const today = new Date();
  const [currentMonth, setCurrentMonth] = useState(today.getMonth());
  const [currentYear, setCurrentYear] = useState(today.getFullYear());

  // Build a map of dates to trips for quick lookup
  const tripDays = useMemo(() => {
    const dayMap = new Map<string, SchengenTrip>();

    trips.forEach((trip) => {
      const start = new Date(trip.startDate);
      const end = new Date(trip.endDate);
      const current = new Date(start);

      while (current <= end) {
        const key = current.toISOString().split('T')[0];
        dayMap.set(key, trip);
        current.setDate(current.getDate() + 1);
      }
    });

    return dayMap;
  }, [trips]);

  // Generate calendar days for the current month view
  const calendarDays = useMemo((): DayInfo[] => {
    const days: DayInfo[] = [];
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);

    const windowStartDate = new Date(windowStart);
    const windowEndDate = new Date(windowEnd);
    const todayStr = today.toISOString().split('T')[0];

    // Add days from previous month to fill first week
    const startPadding = firstDay.getDay();
    for (let i = startPadding - 1; i >= 0; i--) {
      const date = new Date(currentYear, currentMonth, -i);
      const dateStr = date.toISOString().split('T')[0];
      days.push({
        date,
        isCurrentMonth: false,
        isToday: false,
        isInWindow: date >= windowStartDate && date <= windowEndDate,
        trip: tripDays.get(dateStr) || null,
        isFuture: date > today,
      });
    }

    // Add days of current month
    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(currentYear, currentMonth, day);
      const dateStr = date.toISOString().split('T')[0];
      days.push({
        date,
        isCurrentMonth: true,
        isToday: dateStr === todayStr,
        isInWindow: date >= windowStartDate && date <= windowEndDate,
        trip: tripDays.get(dateStr) || null,
        isFuture: date > today,
      });
    }

    // Add days from next month to complete last week
    const endPadding = 6 - lastDay.getDay();
    for (let i = 1; i <= endPadding; i++) {
      const date = new Date(currentYear, currentMonth + 1, i);
      const dateStr = date.toISOString().split('T')[0];
      days.push({
        date,
        isCurrentMonth: false,
        isToday: false,
        isInWindow: date >= windowStartDate && date <= windowEndDate,
        trip: tripDays.get(dateStr) || null,
        isFuture: date > today,
      });
    }

    return days;
  }, [currentMonth, currentYear, tripDays, windowStart, windowEnd, today]);

  const goToPreviousMonth = () => {
    if (currentMonth === 0) {
      setCurrentMonth(11);
      setCurrentYear(currentYear - 1);
    } else {
      setCurrentMonth(currentMonth - 1);
    }
  };

  const goToNextMonth = () => {
    if (currentMonth === 11) {
      setCurrentMonth(0);
      setCurrentYear(currentYear + 1);
    } else {
      setCurrentMonth(currentMonth + 1);
    }
  };

  const goToToday = () => {
    setCurrentMonth(today.getMonth());
    setCurrentYear(today.getFullYear());
  };

  return (
    <div className="bg-white rounded-lg border border-gray-200">
      {/* Calendar header */}
      <div className="flex items-center justify-between p-4 border-b border-gray-200">
        <div className="flex items-center gap-2">
          <button
            onClick={goToPreviousMonth}
            className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
            aria-label="Previous month"
          >
            <ChevronLeft className="w-5 h-5 text-gray-600" aria-hidden="true" />
          </button>
          <button
            onClick={goToNextMonth}
            className="p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
            aria-label="Next month"
          >
            <ChevronRight className="w-5 h-5 text-gray-600" aria-hidden="true" />
          </button>
          <h3 className="text-lg font-semibold text-gray-900 ml-2">
            {MONTHS[currentMonth]} {currentYear}
          </h3>
        </div>
        <button
          onClick={goToToday}
          className="px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
        >
          Today
        </button>
      </div>

      {/* Weekday headers */}
      <div className="grid grid-cols-7 border-b border-gray-200">
        {WEEKDAYS.map((day) => (
          <div
            key={day}
            className="py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
          >
            {day}
          </div>
        ))}
      </div>

      {/* Calendar grid */}
      <div className="grid grid-cols-7">
        {calendarDays.map((dayInfo, index) => {
          const { date, isCurrentMonth, isToday, isInWindow, trip, isFuture } = dayInfo;

          return (
            <div
              key={index}
              className={clsx(
                'relative min-h-[60px] p-1 border-b border-r border-gray-100',
                !isCurrentMonth && 'bg-gray-50',
                isToday && 'bg-primary-50'
              )}
            >
              {/* Date number */}
              <span
                className={clsx(
                  'inline-flex items-center justify-center w-6 h-6 text-sm rounded-full',
                  isToday && 'bg-primary-600 text-white font-medium',
                  !isToday && isCurrentMonth && 'text-gray-900',
                  !isToday && !isCurrentMonth && 'text-gray-400'
                )}
              >
                {date.getDate()}
              </span>

              {/* Trip indicator */}
              {trip && (
                <div
                  className={clsx(
                    'mt-1 px-1 py-0.5 rounded text-xs truncate',
                    isFuture
                      ? 'bg-blue-100 text-blue-700 border border-dashed border-blue-300'
                      : isInWindow
                        ? 'bg-primary-100 text-primary-700'
                        : 'bg-gray-100 text-gray-600'
                  )}
                  title={`${trip.country}${isFuture ? ' (planned)' : ''}`}
                >
                  <Plane className="w-3 h-3 inline-block mr-0.5" aria-hidden="true" />
                  <span className="sr-only">Trip to </span>
                  {trip.country.slice(0, 3)}
                </div>
              )}

              {/* Window indicator (small dot) */}
              {isInWindow && !trip && isCurrentMonth && (
                <div
                  className="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-green-400"
                  title="In current 180-day window"
                />
              )}
            </div>
          );
        })}
      </div>

      {/* Legend */}
      <div className="flex items-center gap-4 p-3 border-t border-gray-200 text-xs text-gray-500">
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded bg-primary-100 border border-primary-200" />
          <span>Past trip (in window)</span>
        </div>
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded bg-blue-100 border border-dashed border-blue-300" />
          <span>Future trip</span>
        </div>
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded bg-gray-100 border border-gray-200" />
          <span>Past trip (outside window)</span>
        </div>
      </div>
    </div>
  );
}
