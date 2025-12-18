/**
 * GenerateReportModal Component
 *
 * Modal for generating AI-researched relocation reports.
 * Handles report generation, caching status, and saving to user documents.
 */

import { useState, useEffect } from 'react';
import { X, FileText, Download, Save, Loader2, CheckCircle, Clock, AlertCircle, RefreshCw } from 'lucide-react';
import { researchApi } from '@/api/client';
import type { ResearchLevel } from '@/types';

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

  // Reset state when modal opens with new location
  useEffect(() => {
    if (isOpen) {
      setState('initial');
      setReport(null);
      setError(null);
      setIsCached(false);
      setCacheAge(null);
    }
  }, [isOpen, locationCode]);

  // Generate report
  const handleGenerate = async (forceRefresh = false) => {
    setState('generating');
    setError(null);

    try {
      const response = await researchApi.generateReport({
        location_type: locationType,
        location_code: locationCode,
        location_name: locationName,
        force_refresh: forceRefresh,
      });

      setReport(response.report);
      setIsCached(response.cached);
      setCacheAge(response.cache_age || null);
      setState('ready');
    } catch (err) {
      console.error('Report generation failed:', err);
      setError(
        err instanceof Error
          ? err.message
          : 'Failed to generate report. Please try again.'
      );
      setState('error');
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

  // Download PDF
  const handleDownload = () => {
    if (!report?.download_url) return;
    window.open(report.download_url, '_blank');
  };

  if (!isOpen) return null;

  return (
    <div
      className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
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
            Generate Relocation Report
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
                  <li>• Brief history and cultural overview</li>
                  <li>• Cost of living analysis</li>
                  <li>• Housing market information</li>
                  <li>• Healthcare facilities</li>
                  <li>• Education options</li>
                  <li>• Transportation and infrastructure</li>
                  <li>• Climate and lifestyle</li>
                  <li>• Practical relocation tips</li>
                </ul>
              </div>

              <button
                onClick={() => handleGenerate(false)}
                className="btn btn-primary w-full"
              >
                <FileText className="w-4 h-4" />
                Generate Report
              </button>
            </div>
          )}

          {/* Generating State */}
          {state === 'generating' && (
            <div className="text-center py-8">
              <Loader2 className="w-12 h-12 animate-spin text-primary-600 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">
                Generating Report
              </h3>
              <p className="text-gray-600">
                Our AI is researching and compiling information about {locationName}...
              </p>
              <p className="text-sm text-gray-500 mt-4">
                This may take a minute or two.
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
                  Report Ready
                </h3>

                {isCached && (
                  <div className="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full mb-2">
                    <Clock className="w-3 h-3" />
                    <span>Cached report {cacheAge && `(${cacheAge})`}</span>
                  </div>
                )}

                <p className="text-gray-600">
                  Your relocation report for {locationName} is ready.
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
                  Last updated: {new Date(report.updated_at).toLocaleDateString()}
                </p>
              </div>

              {/* Actions */}
              <div className="space-y-3">
                <button
                  onClick={handleDownload}
                  className="btn btn-primary w-full"
                >
                  <Download className="w-4 h-4" />
                  Download PDF
                </button>

                <button
                  onClick={handleSaveToDocuments}
                  className="btn btn-outline w-full"
                >
                  <Save className="w-4 h-4" />
                  Save to My Documents
                </button>

                {isCached && (
                  <button
                    onClick={() => handleGenerate(true)}
                    className="btn btn-ghost w-full text-sm"
                  >
                    <RefreshCw className="w-4 h-4" />
                    Regenerate with fresh data
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
                This report is now linked in your documents. You'll always have
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
                  onClick={handleDownload}
                  className="btn btn-primary w-full"
                >
                  <Download className="w-4 h-4" />
                  Download PDF
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
                Generation Failed
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
                  Try Again
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
