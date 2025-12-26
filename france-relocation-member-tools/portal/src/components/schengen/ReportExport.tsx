/**
 * ReportExport
 *
 * Button and modal for exporting Schengen tracker data as PDF report.
 * Uses the browser's print functionality with custom HTML styling.
 */

import { useState, useRef } from 'react';
import { FileText, Download, Loader2, X, Printer } from 'lucide-react';
import Modal from '@/components/shared/Modal';
import { useGenerateSchengenReport } from '@/hooks/useApi';
import type { SchengenReportResponse } from '@/types';

interface ReportExportProps {
  disabled?: boolean;
}

export default function ReportExport({ disabled }: ReportExportProps) {
  const [showPreview, setShowPreview] = useState(false);
  const [reportData, setReportData] = useState<SchengenReportResponse | null>(null);
  const iframeRef = useRef<HTMLIFrameElement>(null);

  const generateMutation = useGenerateSchengenReport();

  const handleGenerate = () => {
    generateMutation.mutate(undefined, {
      onSuccess: (data) => {
        setReportData(data);
        setShowPreview(true);
      },
    });
  };

  const handlePrint = () => {
    if (iframeRef.current?.contentWindow) {
      iframeRef.current.contentWindow.print();
    }
  };

  const handleDownload = () => {
    if (!reportData) return;

    // Create a blob from the HTML and trigger download
    // Users can print to PDF from the browser
    const blob = new Blob([reportData.html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = reportData.filename.replace('.pdf', '.html');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  const handleClose = () => {
    setShowPreview(false);
    setReportData(null);
  };

  return (
    <>
      <button
        onClick={handleGenerate}
        disabled={disabled || generateMutation.isPending}
        className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        aria-label="Export Schengen report as PDF"
      >
        {generateMutation.isPending ? (
          <>
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
            Generating...
          </>
        ) : (
          <>
            <FileText className="w-4 h-4" aria-hidden="true" />
            Export Report
          </>
        )}
      </button>

      {/* Report preview modal */}
      <Modal
        isOpen={showPreview}
        onClose={handleClose}
        title="Schengen Report Preview"
        size="xl"
      >
        <div className="space-y-4">
          {/* Summary stats */}
          {reportData?.summary && (
            <div className="grid grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
              <div className="text-center">
                <p className="text-2xl font-bold text-gray-900">{reportData.summary.daysUsed}</p>
                <p className="text-xs text-gray-500">Days Used</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-gray-900">{reportData.summary.daysRemaining}</p>
                <p className="text-xs text-gray-500">Remaining</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-gray-900 capitalize">{reportData.summary.status}</p>
                <p className="text-xs text-gray-500">Status</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-gray-900">{reportData.summary.tripCount}</p>
                <p className="text-xs text-gray-500">Trips</p>
              </div>
            </div>
          )}

          {/* Report preview iframe */}
          {reportData?.html && (
            <div className="border border-gray-200 rounded-lg overflow-hidden bg-white" style={{ height: '400px' }}>
              <iframe
                ref={iframeRef}
                srcDoc={reportData.html}
                title="Report Preview"
                className="w-full h-full"
                sandbox="allow-same-origin"
              />
            </div>
          )}

          {/* Action buttons */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <button
              onClick={handleClose}
              className="px-4 py-2 text-sm text-gray-600 hover:text-gray-800"
            >
              Close
            </button>
            <button
              onClick={handleDownload}
              className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <Download className="w-4 h-4" aria-hidden="true" />
              Download HTML
            </button>
            <button
              onClick={handlePrint}
              className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700"
            >
              <Printer className="w-4 h-4" aria-hidden="true" />
              Print / Save as PDF
            </button>
          </div>

          <p className="text-xs text-gray-500 text-center">
            Tip: Use your browser's "Print to PDF" option to save as a PDF file
          </p>
        </div>
      </Modal>

      {/* Error toast */}
      {generateMutation.isError && (
        <div className="fixed bottom-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg flex items-center gap-3">
          <p className="text-sm text-red-800">Failed to generate report. Please try again.</p>
          <button
            onClick={() => generateMutation.reset()}
            className="text-red-600 hover:text-red-800"
            aria-label="Dismiss error"
          >
            <X className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      )}
    </>
  );
}
