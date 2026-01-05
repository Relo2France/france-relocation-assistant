/**
 * CalendarSync
 *
 * Component for syncing calendar events from Google Calendar, Microsoft Outlook,
 * or iCal file imports to detect and import travel-related entries as Schengen trips.
 */

import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  Calendar,
  Link,
  Unlink,
  RefreshCw,
  Upload,
  Check,
  X,
  Clock,
  MapPin,
  Plane,
  ChevronDown,
  ChevronUp,
  Loader2,
  CheckCircle,
  XCircle,
  AlertCircle,
} from 'lucide-react';
import type { CalendarConnection, CalendarEvent } from '@/types';
import {
  useCalendarProviders,
  useCalendarConnections,
  useConnectCalendar,
  useDisconnectCalendar,
  useSyncCalendar,
  useCalendarEvents,
  useImportCalendarEvents,
  useSkipCalendarEvents,
  useImportICalFile,
} from '@/hooks/useApi';

interface CalendarSyncProps {
  compact?: boolean;
}

export default function CalendarSync({ compact = false }: CalendarSyncProps) {
  const [selectedEvents, setSelectedEvents] = useState<Set<number>>(new Set());
  const [showAllEvents, setShowAllEvents] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Wrap hooks in try-catch pattern with safe defaults
  const providersQuery = useCalendarProviders();
  const connectionsQuery = useCalendarConnections();
  const eventsQuery = useCalendarEvents('pending');

  const providers = providersQuery.data ?? [];
  const connections = connectionsQuery.data ?? [];
  const pendingEvents = eventsQuery.data ?? [];
  const refetchEvents = eventsQuery.refetch;

  const providersLoading = providersQuery.isLoading;
  const connectionsLoading = connectionsQuery.isLoading;
  const eventsLoading = eventsQuery.isLoading;

  const providersError = providersQuery.error;
  const connectionsError = connectionsQuery.error;
  const eventsError = eventsQuery.error;

  const connectMutation = useConnectCalendar();
  const disconnectMutation = useDisconnectCalendar();
  const syncMutation = useSyncCalendar();
  const importMutation = useImportCalendarEvents();
  const skipMutation = useSkipCalendarEvents();
  const importICalMutation = useImportICalFile();

  // Handle OAuth callback messages from URL params (using window.location)
  useEffect(() => {
    try {
      const searchParams = new URLSearchParams(window.location.search);
      const connected = searchParams.get('calendar_connected');
      const error = searchParams.get('calendar_error');

      if (connected) {
        setSuccessMessage(`Successfully connected ${connected === 'google' ? 'Google Calendar' : 'Microsoft Outlook'}!`);
        // Clear the URL parameter using history API
        searchParams.delete('calendar_connected');
        const newSearch = searchParams.toString();
        const newUrl = window.location.pathname + (newSearch ? `?${newSearch}` : '');
        window.history.replaceState({}, '', newUrl);
        // Refetch events after connection
        refetchEvents();
      }

      if (error) {
        setErrorMessage(decodeURIComponent(error));
        searchParams.delete('calendar_error');
        const newSearch = searchParams.toString();
        const newUrl = window.location.pathname + (newSearch ? `?${newSearch}` : '');
        window.history.replaceState({}, '', newUrl);
      }
    } catch (e) {
      console.error('Error handling URL params:', e);
    }
  }, [refetchEvents]);

  // Auto-clear success/error messages
  useEffect(() => {
    if (successMessage) {
      const timer = setTimeout(() => setSuccessMessage(null), 5000);
      return () => clearTimeout(timer);
    }
  }, [successMessage]);

  useEffect(() => {
    if (errorMessage) {
      const timer = setTimeout(() => setErrorMessage(null), 8000);
      return () => clearTimeout(timer);
    }
  }, [errorMessage]);

  const handleConnect = (providerId: 'google' | 'microsoft') => {
    setErrorMessage(null);
    connectMutation.mutate(providerId, {
      onError: (error) => {
        setErrorMessage(error instanceof Error ? error.message : 'Failed to connect');
      },
    });
  };

  const handleDisconnect = (connectionId: number) => {
    if (confirm('Are you sure you want to disconnect this calendar? Pending events will be removed.')) {
      disconnectMutation.mutate(connectionId, {
        onSuccess: () => {
          setSuccessMessage('Calendar disconnected');
        },
        onError: (error) => {
          setErrorMessage(error instanceof Error ? error.message : 'Failed to disconnect');
        },
      });
    }
  };

  const handleSync = (connectionId: number) => {
    syncMutation.mutate(connectionId, {
      onSuccess: (result) => {
        setSuccessMessage(`Synced! Found ${result.travelDetected} travel events.`);
      },
      onError: (error) => {
        setErrorMessage(error instanceof Error ? error.message : 'Sync failed');
      },
    });
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    importICalMutation.mutate(file, {
      onSuccess: (result) => {
        setSuccessMessage(`Imported ${result.detected} travel events from ${result.parsed} calendar entries.`);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
      },
      onError: (error) => {
        setErrorMessage(error instanceof Error ? error.message : 'Failed to import file');
      },
    });
  };

  const handleToggleEvent = (eventId: number) => {
    const newSelected = new Set(selectedEvents);
    if (newSelected.has(eventId)) {
      newSelected.delete(eventId);
    } else {
      newSelected.add(eventId);
    }
    setSelectedEvents(newSelected);
  };

  const handleSelectAll = () => {
    if (!pendingEvents) return;
    const schengenEvents = pendingEvents.filter(e => e.isSchengen);
    if (selectedEvents.size === schengenEvents.length) {
      setSelectedEvents(new Set());
    } else {
      setSelectedEvents(new Set(schengenEvents.map(e => e.id)));
    }
  };

  const handleImportSelected = () => {
    if (selectedEvents.size === 0) return;

    importMutation.mutate(Array.from(selectedEvents), {
      onSuccess: (result) => {
        setSuccessMessage(`Imported ${result.imported} trips!`);
        setSelectedEvents(new Set());
      },
      onError: (error) => {
        setErrorMessage(error instanceof Error ? error.message : 'Import failed');
      },
    });
  };

  const handleSkipSelected = () => {
    if (selectedEvents.size === 0) return;

    skipMutation.mutate(Array.from(selectedEvents), {
      onSuccess: (result) => {
        setSuccessMessage(`Skipped ${result.skipped} events.`);
        setSelectedEvents(new Set());
      },
      onError: (error) => {
        setErrorMessage(error instanceof Error ? error.message : 'Skip failed');
      },
    });
  };

  const isLoading = providersLoading || connectionsLoading || eventsLoading;
  const hasError = !!(providersError || connectionsError || eventsError);
  const hasConnections = connections.length > 0;
  const hasPendingEvents = pendingEvents.length > 0;
  const schengenPendingEvents = pendingEvents.filter(e => e.isSchengen);
  const nonSchengenEvents = pendingEvents.filter(e => !e.isSchengen);

  // Get error message safely
  const getErrorMessage = (err: unknown): string => {
    if (err instanceof Error) return err.message;
    if (typeof err === 'string') return err;
    return 'Unknown error';
  };

  if (isLoading) {
    return (
      <div className={clsx('card p-6', compact && 'p-4')}>
        <div className="flex items-center justify-center py-8">
          <Loader2 className="w-6 h-6 animate-spin text-primary-600" aria-hidden="true" />
          <span className="ml-2 text-gray-500">Loading calendar sync...</span>
        </div>
      </div>
    );
  }

  if (hasError) {
    const errorMsg = providersError ? getErrorMessage(providersError) :
                     connectionsError ? getErrorMessage(connectionsError) :
                     eventsError ? getErrorMessage(eventsError) :
                     'Failed to load calendar data';
    return (
      <div className={clsx('card p-6', compact && 'p-4')}>
        <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
          <XCircle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
          <div>
            <p className="text-sm font-medium text-red-800">Unable to load calendar sync</p>
            <p className="text-sm text-red-600 mt-1">{errorMsg}</p>
          </div>
        </div>
      </div>
    );
  }

  // Compact view for embedding in dashboard
  if (compact) {
    return (
      <div className="card p-4">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-2">
            <Calendar className="w-5 h-5 text-primary-600" aria-hidden="true" />
            <h3 className="font-medium text-gray-900">Calendar Sync</h3>
          </div>
          {hasPendingEvents && (
            <span className="px-2 py-1 bg-primary-100 text-primary-700 text-xs font-medium rounded-full">
              {schengenPendingEvents.length} to review
            </span>
          )}
        </div>

        {hasConnections ? (
          <div className="space-y-2">
            {connections.map(conn => (
              <div key={conn.id} className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4 h-4 text-green-500" aria-hidden="true" />
                  <span>{conn.providerName}</span>
                </div>
                <button
                  onClick={() => handleSync(conn.id)}
                  disabled={syncMutation.isPending}
                  className="text-primary-600 hover:text-primary-700 text-xs font-medium"
                >
                  {syncMutation.isPending ? 'Syncing...' : 'Sync now'}
                </button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-gray-500">
            Connect your calendar to auto-detect travel.
          </p>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Success/Error Messages */}
      {successMessage && (
        <div className="flex items-center gap-3 p-4 bg-green-50 border border-green-200 rounded-lg">
          <CheckCircle className="w-5 h-5 text-green-600 flex-shrink-0" aria-hidden="true" />
          <p className="text-sm text-green-800">{successMessage}</p>
          <button onClick={() => setSuccessMessage(null)} className="ml-auto text-green-600 hover:text-green-800">
            <X className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      )}

      {errorMessage && (
        <div className="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
          <XCircle className="w-5 h-5 text-red-600 flex-shrink-0" aria-hidden="true" />
          <p className="text-sm text-red-800">{errorMessage}</p>
          <button onClick={() => setErrorMessage(null)} className="ml-auto text-red-600 hover:text-red-800">
            <X className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      )}

      {/* Connected Calendars Section */}
      <div className="card">
        <div className="card-header">
          <h3 className="text-lg font-semibold text-gray-900">Connected Calendars</h3>
        </div>
        <div className="p-6">
          {hasConnections ? (
            <div className="space-y-4">
              {connections.map(conn => (
                <ConnectionCard
                  key={conn.id}
                  connection={conn}
                  onSync={() => handleSync(conn.id)}
                  onDisconnect={() => handleDisconnect(conn.id)}
                  isSyncing={syncMutation.isPending}
                  isDisconnecting={disconnectMutation.isPending}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-6">
              <Calendar className="w-12 h-12 text-gray-300 mx-auto mb-3" aria-hidden="true" />
              <p className="text-gray-500 mb-2">No calendars connected yet</p>
              <p className="text-sm text-gray-400">
                Connect a calendar to automatically detect travel events
              </p>
            </div>
          )}

          {/* Provider Connection Buttons */}
          <div className="mt-6 pt-6 border-t border-gray-200">
            <h4 className="text-sm font-medium text-gray-700 mb-3">Add Calendar</h4>
            <div className="flex flex-wrap gap-3">
              {providers.map(provider => {
                const isConnected = connections.some(c => c.provider === provider.id);
                const canConnect = provider.isConfigured && !isConnected;

                return (
                  <button
                    key={provider.id}
                    onClick={() => canConnect && handleConnect(provider.id as 'google' | 'microsoft')}
                    disabled={!canConnect || connectMutation.isPending}
                    className={clsx(
                      'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                      canConnect
                        ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    )}
                  >
                    {connectMutation.isPending ? (
                      <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                    ) : (
                      <Link className="w-4 h-4" aria-hidden="true" />
                    )}
                    {provider.name}
                    {isConnected && (
                      <span className="text-xs text-green-600">(Connected)</span>
                    )}
                    {!provider.isConfigured && (
                      <span className="text-xs text-gray-400">(Not configured)</span>
                    )}
                  </button>
                );
              })}
            </div>
          </div>

          {/* iCal Upload */}
          <div className="mt-6 pt-6 border-t border-gray-200">
            <h4 className="text-sm font-medium text-gray-700 mb-3">Import iCal File</h4>
            <div className="flex items-center gap-4">
              <input
                ref={fileInputRef}
                type="file"
                accept=".ics,text/calendar"
                onChange={handleFileUpload}
                className="hidden"
                id="ical-upload"
              />
              <label
                htmlFor="ical-upload"
                className={clsx(
                  'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors',
                  importICalMutation.isPending
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                )}
              >
                {importICalMutation.isPending ? (
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Upload className="w-4 h-4" aria-hidden="true" />
                )}
                {importICalMutation.isPending ? 'Uploading...' : 'Upload .ics file'}
              </label>
              <span className="text-sm text-gray-500">
                Export from Apple Calendar, Outlook, or other apps
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Pending Events Section */}
      {hasPendingEvents && (
        <div className="card">
          <div className="card-header flex items-center justify-between">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Detected Travel Events</h3>
              <p className="text-sm text-gray-500 mt-1">
                Review and import these events as Schengen trips
              </p>
            </div>
            {schengenPendingEvents.length > 0 && (
              <div className="flex items-center gap-2">
                <button
                  onClick={handleSelectAll}
                  className="text-sm text-primary-600 hover:text-primary-700 font-medium"
                >
                  {selectedEvents.size === schengenPendingEvents.length ? 'Deselect all' : 'Select all'}
                </button>
              </div>
            )}
          </div>

          <div className="divide-y divide-gray-200">
            {/* Schengen Events */}
            {schengenPendingEvents.length > 0 && (
              <div>
                {schengenPendingEvents.map(event => (
                  <EventRow
                    key={event.id}
                    event={event}
                    isSelected={selectedEvents.has(event.id)}
                    onToggle={() => handleToggleEvent(event.id)}
                  />
                ))}
              </div>
            )}

            {/* Non-Schengen Events (collapsible) */}
            {nonSchengenEvents.length > 0 && (
              <div className="p-4 bg-gray-50">
                <button
                  onClick={() => setShowAllEvents(!showAllEvents)}
                  className="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-800"
                >
                  {showAllEvents ? (
                    <ChevronUp className="w-4 h-4" aria-hidden="true" />
                  ) : (
                    <ChevronDown className="w-4 h-4" aria-hidden="true" />
                  )}
                  {nonSchengenEvents.length} non-Schengen events detected
                </button>

                {showAllEvents && (
                  <div className="mt-4 space-y-2">
                    {nonSchengenEvents.map(event => (
                      <div
                        key={event.id}
                        className="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200"
                      >
                        <div className="flex items-center gap-3">
                          <AlertCircle className="w-4 h-4 text-gray-400" aria-hidden="true" />
                          <div>
                            <p className="text-sm font-medium text-gray-700">{event.title}</p>
                            <p className="text-xs text-gray-500">
                              {formatEventDate(event.startDate)} - {formatEventDate(event.endDate)}
                              {event.location && ` • ${event.location}`}
                            </p>
                          </div>
                        </div>
                        <span className="text-xs text-gray-400">
                          {event.detectedCountry || 'Unknown location'}
                        </span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Actions */}
          {selectedEvents.size > 0 && (
            <div className="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
              <span className="text-sm text-gray-600">
                {selectedEvents.size} event{selectedEvents.size !== 1 ? 's' : ''} selected
              </span>
              <div className="flex items-center gap-3">
                <button
                  onClick={handleSkipSelected}
                  disabled={skipMutation.isPending}
                  className="inline-flex items-center gap-2 px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors disabled:opacity-50"
                >
                  {skipMutation.isPending ? (
                    <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                  ) : (
                    <X className="w-4 h-4" aria-hidden="true" />
                  )}
                  Skip
                </button>
                <button
                  onClick={handleImportSelected}
                  disabled={importMutation.isPending}
                  className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
                >
                  {importMutation.isPending ? (
                    <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                  ) : (
                    <Check className="w-4 h-4" aria-hidden="true" />
                  )}
                  Import as Trips
                </button>
              </div>
            </div>
          )}

          {schengenPendingEvents.length === 0 && (
            <div className="p-6 text-center">
              <CheckCircle className="w-8 h-8 text-green-500 mx-auto mb-2" aria-hidden="true" />
              <p className="text-gray-600">All Schengen travel events have been processed!</p>
            </div>
          )}
        </div>
      )}

      {/* No pending events */}
      {!hasPendingEvents && hasConnections && (
        <div className="card p-6 text-center">
          <CheckCircle className="w-12 h-12 text-green-400 mx-auto mb-3" aria-hidden="true" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">All caught up!</h3>
          <p className="text-gray-500">
            No pending travel events to review. Events will appear here after syncing.
          </p>
        </div>
      )}
    </div>
  );
}

/**
 * Connection card component
 */
function ConnectionCard({
  connection,
  onSync,
  onDisconnect,
  isSyncing,
  isDisconnecting,
}: {
  connection: CalendarConnection;
  onSync: () => void;
  onDisconnect: () => void;
  isSyncing: boolean;
  isDisconnecting: boolean;
}) {
  const statusColors = {
    active: 'bg-green-100 text-green-700',
    error: 'bg-red-100 text-red-700',
    expired: 'bg-yellow-100 text-yellow-700',
  };

  return (
    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
      <div className="flex items-center gap-4">
        <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
          <Calendar className="w-5 h-5 text-primary-600" aria-hidden="true" />
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h4 className="font-medium text-gray-900">{connection.providerName}</h4>
            <span className={clsx('px-2 py-0.5 rounded-full text-xs font-medium', statusColors[connection.syncStatus])}>
              {connection.syncStatus}
            </span>
          </div>
          {connection.lastSyncAt && (
            <p className="text-sm text-gray-500 flex items-center gap-1 mt-1">
              <Clock className="w-3 h-3" aria-hidden="true" />
              Last synced: {formatRelativeTime(connection.lastSyncAt)}
            </p>
          )}
        </div>
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={onSync}
          disabled={isSyncing || connection.syncStatus === 'expired'}
          className={clsx(
            'inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            isSyncing || connection.syncStatus === 'expired'
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-primary-100 text-primary-700 hover:bg-primary-200'
          )}
        >
          {isSyncing ? (
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
          ) : (
            <RefreshCw className="w-4 h-4" aria-hidden="true" />
          )}
          {isSyncing ? 'Syncing...' : 'Sync'}
        </button>
        <button
          onClick={onDisconnect}
          disabled={isDisconnecting}
          className="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        >
          {isDisconnecting ? (
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
          ) : (
            <Unlink className="w-4 h-4" aria-hidden="true" />
          )}
        </button>
      </div>
    </div>
  );
}

/**
 * Event row component
 */
function EventRow({
  event,
  isSelected,
  onToggle,
}: {
  event: CalendarEvent;
  isSelected: boolean;
  onToggle: () => void;
}) {
  const confidenceLabel = event.confidence >= 0.8 ? 'High' : event.confidence >= 0.5 ? 'Medium' : 'Low';
  const confidenceColor = event.confidence >= 0.8 ? 'text-green-600' : event.confidence >= 0.5 ? 'text-yellow-600' : 'text-gray-500';

  return (
    <div
      className={clsx(
        'flex items-center gap-4 p-4 hover:bg-gray-50 cursor-pointer transition-colors',
        isSelected && 'bg-primary-50'
      )}
      onClick={onToggle}
      role="checkbox"
      aria-checked={isSelected}
      tabIndex={0}
      onKeyDown={(e) => {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          onToggle();
        }
      }}
    >
      <div className={clsx(
        'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0',
        isSelected
          ? 'bg-primary-600 border-primary-600'
          : 'border-gray-300'
      )}>
        {isSelected && <Check className="w-3 h-3 text-white" aria-hidden="true" />}
      </div>

      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <Plane className="w-4 h-4 text-primary-500 flex-shrink-0" aria-hidden="true" />
          <h4 className="font-medium text-gray-900 truncate">{event.title}</h4>
        </div>
        <div className="flex items-center gap-4 mt-1 text-sm text-gray-500">
          <span className="flex items-center gap-1">
            <Calendar className="w-3 h-3" aria-hidden="true" />
            {formatEventDate(event.startDate)} - {formatEventDate(event.endDate)}
          </span>
          {event.location && (
            <span className="flex items-center gap-1 truncate">
              <MapPin className="w-3 h-3" aria-hidden="true" />
              {event.location}
            </span>
          )}
        </div>
      </div>

      <div className="flex items-center gap-4 flex-shrink-0">
        {event.detectedCountry && (
          <div className="text-right">
            <span className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
              {event.countryCode && (
                <span className="font-mono">{event.countryCode}</span>
              )}
              {event.detectedCountry}
            </span>
            <p className={clsx('text-xs mt-1', confidenceColor)}>
              {confidenceLabel} confidence
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Format date for display
 */
function formatEventDate(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

/**
 * Format relative time
 */
function formatRelativeTime(dateStr: string): string {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return date.toLocaleDateString();
}
