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

const WEEKDAYS_SHORT = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
const WEEKDAYS_FULL = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

export default function CalendarView({ trips, windowStart, windowEnd }: CalendarViewProps) {
  // Memoize today's date string to avoid unnecessary re-renders
  // Only recalculates when component mounts or date actually changes
  const todayStr = useMemo(() => {
    const now = new Date();
    return now.toISOString().split('T')[0];
  }, []);
  const today = useMemo(() => new Date(todayStr), [todayStr]);

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
  }, [currentMonth, currentYear, tripDays, windowStart, windowEnd, today, todayStr]);

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
    <div className="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
      {/* Calendar header */}
      <div className="bg-gradient-to-r from-gray-50 to-white px-3 sm:px-5 py-3 sm:py-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          {/* Month and Year - prominent display */}
          <div className="flex-1">
            <h3 className="text-lg sm:text-2xl font-bold text-gray-900 tracking-tight">
              {MONTHS[currentMonth]}
            </h3>
            <p className="text-xs sm:text-sm text-gray-500 font-medium">{currentYear}</p>
          </div>

          {/* Navigation controls */}
          <div className="flex items-center gap-1 sm:gap-2">
            <button
              onClick={goToToday}
              className="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Today
            </button>
            <div className="flex items-center bg-gray-100 rounded-lg p-0.5">
              <button
                onClick={goToPreviousMonth}
                className="p-1.5 sm:p-2 rounded-md hover:bg-white hover:shadow-sm transition-all"
                aria-label="Previous month"
              >
                <ChevronLeft className="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" aria-hidden="true" />
              </button>
              <button
                onClick={goToNextMonth}
                className="p-1.5 sm:p-2 rounded-md hover:bg-white hover:shadow-sm transition-all"
                aria-label="Next month"
              >
                <ChevronRight className="w-4 h-4 sm:w-5 sm:h-5 text-gray-600" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Weekday headers */}
      <div className="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
        {WEEKDAYS_FULL.map((day, index) => (
          <div
            key={day}
            className={clsx(
              'py-2 sm:py-3 text-center text-[10px] sm:text-xs font-semibold uppercase tracking-wide',
              index === 0 || index === 6 ? 'text-gray-400' : 'text-gray-600'
            )}
          >
            <span className="hidden sm:inline">{day}</span>
            <span className="sm:hidden">{WEEKDAYS_SHORT[index]}</span>
          </div>
        ))}
      </div>

      {/* Calendar grid */}
      <div className="grid grid-cols-7">
        {calendarDays.map((dayInfo, index) => {
          const { date, isCurrentMonth, isToday, isInWindow, trip, isFuture } = dayInfo;
          const isWeekend = date.getDay() === 0 || date.getDay() === 6;

          return (
            <div
              key={index}
              className={clsx(
                'relative min-h-[48px] sm:min-h-[72px] p-1 sm:p-1.5 border-b border-r border-gray-100 transition-colors',
                !isCurrentMonth && 'bg-gray-50/50',
                isCurrentMonth && isWeekend && 'bg-gray-50/30',
                isToday && 'bg-primary-50/70 ring-1 ring-inset ring-primary-200'
              )}
            >
              {/* Date number */}
              <span
                className={clsx(
                  'inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 text-xs sm:text-sm rounded-full font-medium',
                  isToday && 'bg-primary-600 text-white shadow-sm',
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
                    'mt-1 sm:mt-1.5 px-1 sm:px-1.5 py-0.5 sm:py-1 rounded-md text-[10px] sm:text-xs truncate font-medium',
                    isFuture
                      ? 'bg-blue-50 text-blue-700 border border-dashed border-blue-200'
                      : isInWindow
                        ? 'bg-primary-100 text-primary-700 shadow-sm'
                        : 'bg-gray-100 text-gray-600'
                  )}
                  title={`${trip.country}${isFuture ? ' (planned)' : ''}`}
                >
                  <Plane className="w-3 h-3 sm:w-3.5 sm:h-3.5 inline-block mr-0.5" aria-hidden="true" />
                  <span className="sr-only">Trip to </span>
                  <span className="hidden sm:inline">{trip.country.slice(0, 3)}</span>
                </div>
              )}

              {/* Window indicator (small dot) */}
              {isInWindow && !trip && isCurrentMonth && (
                <div
                  className="absolute bottom-1 sm:bottom-1.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-green-400 shadow-sm"
                  title="In current 180-day window"
                />
              )}
            </div>
          );
        })}
      </div>

      {/* Legend */}
      <div className="flex flex-wrap items-center justify-center gap-3 sm:gap-6 px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-t border-gray-200">
        <div className="flex items-center gap-1.5 sm:gap-2">
          <div className="w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-md bg-primary-100 shadow-sm" />
          <span className="text-[10px] sm:text-xs text-gray-600 font-medium">
            <span className="hidden sm:inline">Past trip (in window)</span>
            <span className="sm:hidden">In window</span>
          </span>
        </div>
        <div className="flex items-center gap-1.5 sm:gap-2">
          <div className="w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-md bg-blue-50 border border-dashed border-blue-200" />
          <span className="text-[10px] sm:text-xs text-gray-600 font-medium">
            <span className="hidden sm:inline">Future trip</span>
            <span className="sm:hidden">Future</span>
          </span>
        </div>
        <div className="flex items-center gap-1.5 sm:gap-2">
          <div className="w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-md bg-gray-100" />
          <span className="text-[10px] sm:text-xs text-gray-600 font-medium">
            <span className="hidden sm:inline">Past trip (outside window)</span>
            <span className="sm:hidden">Outside</span>
          </span>
        </div>
      </div>
    </div>
  );
}
