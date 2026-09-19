/**
 * GenerateReportModal Component
 *
 * Modal for generating AI-researched relocation reports.
 * Handles report generation, caching status, and saving to user documents.
 */

import { useEffect, useRef, useState } from 'react';
import { AlertCircle, CheckCircle, Clock, FileText, Hourglass, Loader2, MapPin, RefreshCw, Save, X } from 'lucide-react';
import { researchApi } from '@/api/client';
import type { ResearchLevel } from '@/types';
import { formatDate } from '@/utils/dates';

interface ResearchReportData {
  id: number;
  location_type: string;
  location_code: string;
  location_name: string;
  content: Record<string, unknown>;
  version: number;
  generated_at: string;
  updated_at: string;
  download_url: string;
}

interface GenerateReportModalProps {
  isOpen: boolean;
  onClose: () => void;
  locationType: ResearchLevel;
  locationCode: string;
  locationName: string;
}

type ModalState = 'initial' | 'generating' | 'ready' | 'error' | 'saving' | 'saved';

// Get user-friendly message based on placeholder reason
function getPlaceholderMessage(reason: string | null): string {
  switch (reason) {
    case 'api_key_missing':
      return 'The AI service is not configured. Please contact support to enable AI-generated reports with specific local data.';
    case 'api_error':
      return 'Unable to connect to the AI service. Please try again later or contact support.';
    case 'parse_error':
      return 'The AI response could not be processed. Please try regenerating the report.';
    default:
      return 'This is a template report with generic information. Click "Generate AI Report" to get location-specific data.';
  }
}

const REPORT_SECTIONS = [
  'Geography and landscape',
  'Climate and weather patterns',
  'Economy and job market',
  'Housing and real estate',
  'Food and wine culture',
  'Culture and lifestyle',
  'Quality of life: healthcare, education',
  'Transport options',
  'Environment and outdoor activities',
  'Practical information',
];

export default function GenerateReportModal({
  isOpen,
  onClose,
  locationType,
  locationCode,
  locationName,
}: GenerateReportModalProps) {
  const [state, setState] = useState<ModalState>('initial');
  const [report, setReport] = useState<ResearchReportData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isCached, setIsCached] = useState(false);
  const [cacheAge, setCacheAge] = useState<string | null>(null);
  const [isPlaceholder, setIsPlaceholder] = useState(false);
  const [placeholderReason, setPlaceholderReason] = useState<string | null>(null);

  // Reset state when modal opens with new location
  useEffect(() => {
    if (isOpen) {
      setState('initial');
      setReport(null);
      setError(null);
      setIsCached(false);
      setCacheAge(null);
      setIsPlaceholder(false);
      setPlaceholderReason(null);
    }
  }, [isOpen, locationCode]);

  // Generate report. The server answers at once with a row that is being
  // written by the worker; we poll it until the content lands.
  const [elapsed, setElapsed] = useState(0);
  const [autoSaved, setAutoSaved] = useState(false);
  const openRef = useRef(isOpen);
  useEffect(() => { openRef.current = isOpen; }, [isOpen]);
  const handleGenerate = async (forceRefresh = false) => {
    setState('generating');
    setError(null);
    setElapsed(0);
    const started = Date.now();
    const tick = window.setInterval(() => setElapsed(Math.round((Date.now() - started) / 1000)), 1000);

    try {
      let response = await researchApi.generateReport({
        location_type: locationType,
        location_code: locationCode,
        location_name: locationName,
        force_refresh: forceRefresh,
      });

      if (response.generating) {
        const id = response.report.id;
        const deadline = Date.now() + 15 * 60 * 1000;
        let misses = 0;
        for (;;) {
          await new Promise((r) => setTimeout(r, 5000));
          if (!openRef.current) return;
          if (Date.now() > deadline) throw new Error('The report is taking longer than fifteen minutes. Leave this page and come back; it will be here when it is done.');
          let polled;
          try {
            polled = await researchApi.getReport(id);
            misses = 0;
          } catch (e) {
            if (++misses >= 4) throw e;
            continue;
          }
          const status = (polled.report.content as { status?: string; error?: string })?.status;
          if (status === 'failed') throw new Error((polled.report.content as { error?: string }).error || 'The report could not be generated.');
          if (status !== 'generating') {
            response = { ...response, report: polled.report as typeof response.report, generating: false, cached: false };
            break;
          }
        }
      }

      // Every finished report goes into the member's documents, where it can be
      // opened, downloaded or removed later. Failure to save is not failure
      // to generate.
      try {
        await researchApi.saveReport(response.report.id);
        setAutoSaved(true);
      } catch {
        setAutoSaved(false);
      }

      setReport(response.report);
      setIsCached(response.cached);
      setCacheAge(response.cache_age || null);
      setIsPlaceholder(response.is_placeholder || false);
      setPlaceholderReason(response.placeholder_reason || null);
      setState('ready');
    } catch (err) {
      console.error('Report generation failed:', err);
      setError(
        err instanceof Error
          ? err.message
          : 'Failed to generate report. Please try again.'
      );
      setState('error');
    } finally {
      window.clearInterval(tick);
    }
  };

  // Save report to user's documents
  const handleSaveToDocuments = async () => {
    if (!report) return;

    setState('saving');
    try {
      await researchApi.saveReport(report.id);
      setState('saved');
    } catch (err) {
      console.error('Failed to save report:', err);
      setError('Failed to save report to your documents.');
      setState('ready');
    }
  };

  // View HTML report (can be printed/saved as PDF via browser)
  const handleViewReport = () => {
    if (!report?.id) return;
    const wpData = window.fraPortalData || { nonce: '', apiUrl: '/wp-json/fra-portal/v1' };
    const htmlUrl = `${wpData.apiUrl}/research/report/${report.id}/html?_wpnonce=${wpData.nonce}`;
    window.open(htmlUrl, '_blank');
  };

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      role="presentation"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
    >
      <div
        className="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 id="modal-title" className="text-lg font-semibold text-gray-900">
            Create location report
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Close modal"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          {/* Initial State */}
          {state === 'initial' && (
            <div className="text-center">
              <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FileText className="w-8 h-8 text-primary-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                {locationName}
              </h3>
              <p className="text-gray-600 mb-6">
                Generate a comprehensive AI-researched relocation report for{' '}
                {locationType === 'region'
                  ? 'this region'
                  : locationType === 'department'
                  ? 'this department'
                  : 'this area'}
                .
              </p>

              <div className="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <h4 className="font-medium text-gray-900 mb-2">Report includes:</h4>
                <ul className="text-sm text-gray-600 space-y-1">
                  {REPORT_SECTIONS.map((section) => (
                    <li key={section} className="flex items-start gap-2">
                      <MapPin className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-primary-600" aria-hidden="true" />
                      <span>{section}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <button
                onClick={() => handleGenerate(false)}
                className="btn btn-primary w-full"
              >
                <FileText className="w-4 h-4" />
                Generate report
              </button>
            </div>
          )}

          {/* Generating State */}
          {state === 'generating' && (
            <div className="text-center py-8">
              <div className="relative w-16 h-16 mx-auto mb-4" aria-hidden="true">
                <span className="absolute inset-0 rounded-full border-2 border-primary-100" />
                <span className="absolute inset-0 rounded-full border-2 border-primary-500 border-t-transparent animate-spin" />
                <Hourglass className="w-6 h-6 text-primary-500 absolute inset-0 m-auto" />
              </div>
              <h3 className="font-display text-lg font-semibold text-gray-900 mb-1">
                Researching {locationName}
              </h3>
              <p className="font-mono text-2xl tabular-nums text-ink mb-2" aria-live="polite">
                {Math.floor(elapsed / 60)}:{String(elapsed % 60).padStart(2, '0')}
              </p>
              <p className="text-gray-600">
                Reading INSEE, local and official sources, then writing the report. Usually three to five minutes.
              </p>
              <p className="text-sm text-gray-500 mt-3" aria-live="polite">
                Now on: {REPORT_SECTIONS[Math.min(REPORT_SECTIONS.length - 1, Math.floor(elapsed / 25))]}
              </p>
              <p className="text-sm text-gray-600 mt-4 max-w-[40ch] mx-auto">
                You can close this. The report keeps writing, lands in <strong>Documents &amp; files</strong> when it is done, and we email you to say so.
              </p>
            </div>
          )}

          {/* Ready State */}
          {state === 'ready' && report && (
            <div>
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <CheckCircle className="w-8 h-8 text-green-600" />
                </div>
                <h3 className="text-xl font-semibold text-gray-900 mb-2">
                  Report ready
                </h3>

                {isCached && !isPlaceholder && (
                  <div className="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full mb-2">
                    <Clock className="w-3 h-3" />
                    <span>Cached report {cacheAge && `(${cacheAge})`}</span>
                  </div>
                )}

                {isPlaceholder && (
                  <div className="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-700 text-sm rounded-full mb-2">
                    <AlertCircle className="w-3 h-3" />
                    <span>Template report (generic data)</span>
                  </div>
                )}

                <p className="text-gray-600">
                  {isPlaceholder
                    ? getPlaceholderMessage(placeholderReason)
                    : `Your relocation report for ${locationName} is ready.`}
                </p>
              </div>

              {/* Report Preview */}
              <div className="bg-gray-50 rounded-lg p-4 mb-6">
                <div className="flex items-center justify-between mb-2">
                  <h4 className="font-medium text-gray-900">{report.location_name}</h4>
                  <span className="text-xs text-gray-500">
                    Version {report.version}
                  </span>
                </div>
                <p className="text-sm text-gray-600 mb-2">
                  {locationType.charAt(0).toUpperCase() + locationType.slice(1)} Report
                </p>
                <p className="text-xs text-gray-500">
                  Last updated {formatDate(report.updated_at)}
                </p>
              </div>

              {/* Actions */}
              {autoSaved ? (
                <p className="text-sm text-gray-600 mb-3">It is in <strong>Documents &amp; files</strong>. Open it there any time, or remove it if you don’t want to keep it. Reports for the same place are kept and reused for thirty days, so asking again later costs nothing until you choose to refresh.</p>
              ) : null}
              <div className="space-y-3">
                <button
                  onClick={handleViewReport}
                  className="btn btn-primary w-full"
                >
                  <FileText className="w-4 h-4" />
                  View report
                </button>

                <button
                  onClick={handleSaveToDocuments}
                  className="btn btn-outline w-full"
                >
                  <Save className="w-4 h-4" />
                  Save to Documents
                </button>

                {(isCached || isPlaceholder) && (
                  <button
                    onClick={() => handleGenerate(true)}
                    className={`btn w-full text-sm ${isPlaceholder ? 'btn-primary' : 'btn-ghost'}`}
                  >
                    <RefreshCw className="w-4 h-4" />
                    {isPlaceholder ? 'Generate AI Report' : 'Regenerate with fresh data'}
                  </button>
                )}
              </div>
            </div>
          )}

          {/* Saving State */}
          {state === 'saving' && (
            <div className="text-center py-8">
              <Loader2 className="w-12 h-12 animate-spin text-primary-600 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Saving to Documents
              </h3>
              <p className="text-gray-600">
                Adding this report to your document library...
              </p>
            </div>
          )}

          {/* Saved State */}
          {state === 'saved' && report && (
            <div className="text-center">
              <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <CheckCircle className="w-8 h-8 text-green-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                Saved to Documents
              </h3>
              <p className="text-gray-600 mb-6">
                This report is now linked in your documents. You&apos;ll always have
                access to the latest version.
              </p>

              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left">
                <p className="text-sm text-blue-700">
                  <strong>Note:</strong> When this report is updated, your saved
                  link will automatically point to the newest version with the
                  latest information.
                </p>
              </div>

              <div className="space-y-3">
                <button
                  onClick={handleViewReport}
                  className="btn btn-primary w-full"
                >
                  <FileText className="w-4 h-4" />
                  View report
                </button>
                <button onClick={onClose} className="btn btn-outline w-full">
                  Close
                </button>
              </div>
            </div>
          )}

          {/* Error State */}
          {state === 'error' && (
            <div className="text-center">
              <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <AlertCircle className="w-8 h-8 text-red-600" />
              </div>
              <h3 className="text-xl font-semibold text-gray-900 mb-2">
                Generation failed
              </h3>
              <p className="text-gray-600 mb-2">{error}</p>
              <p className="text-sm text-gray-500 mb-6">
                Please try again or contact support if the issue persists.
              </p>

              <div className="space-y-3">
                <button
                  onClick={() => handleGenerate(false)}
                  className="btn btn-primary w-full"
                >
                  Try again
                </button>
                <button onClick={onClose} className="btn btn-outline w-full">
                  Cancel
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
