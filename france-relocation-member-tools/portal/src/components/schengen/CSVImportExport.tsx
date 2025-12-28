/**
 * CSVImportExport
 *
 * Component for importing and exporting Schengen trips via CSV.
 * Premium feature for bulk trip management.
 */

import { useState, useRef } from 'react';
import { clsx } from 'clsx';
import {
  Upload,
  Download,
  FileSpreadsheet,
  AlertCircle,
  CheckCircle2,
  Loader2,
  Info,
  X,
} from 'lucide-react';
import type { CSVImportResult } from '@/types';
import { useImportTripsCSV, useExportTripsCSV } from '@/hooks/useApi';

interface CSVImportExportProps {
  className?: string;
}

export default function CSVImportExport({ className }: CSVImportExportProps) {
  const [importMode, setImportMode] = useState<'file' | 'paste' | null>(null);
  const [csvText, setCsvText] = useState('');
  const [skipDuplicates, setSkipDuplicates] = useState(true);
  const [importResult, setImportResult] = useState<CSVImportResult | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const importMutation = useImportTripsCSV();
  const exportMutation = useExportTripsCSV();

  const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      const text = event.target?.result as string;
      setCsvText(text);
      setImportMode('file');
    };
    reader.readAsText(file);
  };

  const handleImport = async () => {
    if (!csvText.trim()) return;

    try {
      const result = await importMutation.mutateAsync({
        csv: csvText,
        skipDuplicates,
      });
      setImportResult(result);
    } catch {
      // Error handled by mutation
    }
  };

  const handleExport = async () => {
    try {
      const result = await exportMutation.mutateAsync();

      // Create and download the CSV file
      const blob = new Blob([result.csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = result.filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    } catch {
      // Error handled by mutation
    }
  };

  const resetImport = () => {
    setImportMode(null);
    setCsvText('');
    setImportResult(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const sampleCSV = `start_date,end_date,country,category,notes
2024-01-15,2024-01-20,France,personal,Winter vacation in Paris
2024-03-01,2024-03-05,Germany,business,Berlin conference
2024-06-10,2024-06-18,Italy,personal,Summer trip to Rome`;

  return (
    <div className={clsx('space-y-6', className)}>
      {/* Export Section */}
      <div className="card p-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <Download className="w-5 h-5 text-primary-600" aria-hidden="true" />
              Export Trips
            </h3>
            <p className="text-sm text-gray-500 mt-1">
              Download all your trips as a CSV file for backup or editing.
            </p>
          </div>
          <button
            type="button"
            onClick={handleExport}
            disabled={exportMutation.isPending}
            className="btn-primary flex items-center gap-2"
          >
            {exportMutation.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
            ) : (
              <Download className="w-4 h-4" aria-hidden="true" />
            )}
            Export CSV
          </button>
        </div>
      </div>

      {/* Import Section */}
      <div className="card p-6">
        <h3 className="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
          <Upload className="w-5 h-5 text-primary-600" aria-hidden="true" />
          Import Trips
        </h3>

        {/* Import Result */}
        {importResult && (
          <div
            className={clsx(
              'mb-4 p-4 rounded-lg flex items-start gap-3',
              importResult.imported > 0 ? 'bg-green-50' : 'bg-yellow-50'
            )}
          >
            {importResult.imported > 0 ? (
              <CheckCircle2 className="w-5 h-5 text-green-600 flex-shrink-0" aria-hidden="true" />
            ) : (
              <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0" aria-hidden="true" />
            )}
            <div className="flex-1">
              <p className={clsx(
                'font-medium',
                importResult.imported > 0 ? 'text-green-800' : 'text-yellow-800'
              )}>
                {importResult.message}
              </p>
              {importResult.errors.length > 0 && (
                <ul className="mt-2 text-sm text-gray-600 space-y-1">
                  {importResult.errors.map((error, i) => (
                    <li key={i}>{error}</li>
                  ))}
                </ul>
              )}
            </div>
            <button
              type="button"
              onClick={resetImport}
              className="p-1 text-gray-400 hover:text-gray-600"
              aria-label="Dismiss"
            >
              <X className="w-4 h-4" aria-hidden="true" />
            </button>
          </div>
        )}

        {!importMode && !importResult && (
          <>
            {/* Import Options */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                className="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-400 hover:bg-primary-50 transition-colors text-center"
              >
                <FileSpreadsheet className="w-8 h-8 text-gray-400 mx-auto mb-2" aria-hidden="true" />
                <p className="font-medium text-gray-700">Upload CSV File</p>
                <p className="text-sm text-gray-500">Drag & drop or click to browse</p>
              </button>
              <button
                type="button"
                onClick={() => setImportMode('paste')}
                className="p-6 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-400 hover:bg-primary-50 transition-colors text-center"
              >
                <Upload className="w-8 h-8 text-gray-400 mx-auto mb-2" aria-hidden="true" />
                <p className="font-medium text-gray-700">Paste CSV Data</p>
                <p className="text-sm text-gray-500">Copy & paste your CSV content</p>
              </button>
            </div>
            <input
              ref={fileInputRef}
              type="file"
              accept=".csv,text/csv"
              onChange={handleFileSelect}
              className="hidden"
            />
          </>
        )}

        {/* CSV Input Area */}
        {importMode && !importResult && (
          <div className="space-y-4">
            <div>
              <label htmlFor="csv-input" className="block text-sm font-medium text-gray-700 mb-2">
                CSV Data
              </label>
              <textarea
                id="csv-input"
                value={csvText}
                onChange={(e) => setCsvText(e.target.value)}
                rows={10}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="start_date,end_date,country,category,notes
2024-01-15,2024-01-20,France,personal,My trip"
              />
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="skip-duplicates"
                checked={skipDuplicates}
                onChange={(e) => setSkipDuplicates(e.target.checked)}
                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label htmlFor="skip-duplicates" className="text-sm text-gray-700">
                Skip duplicate trips (same dates and country)
              </label>
            </div>

            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={handleImport}
                disabled={importMutation.isPending || !csvText.trim()}
                className="btn-primary flex items-center gap-2"
              >
                {importMutation.isPending ? (
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Upload className="w-4 h-4" aria-hidden="true" />
                )}
                Import Trips
              </button>
              <button
                type="button"
                onClick={resetImport}
                className="btn-secondary"
              >
                Cancel
              </button>
            </div>
          </div>
        )}

        {/* Format Info */}
        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
          <div className="flex items-start gap-2">
            <Info className="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" aria-hidden="true" />
            <div className="text-sm text-gray-600">
              <p className="font-medium text-gray-700 mb-2">CSV Format Requirements:</p>
              <ul className="list-disc list-inside space-y-1">
                <li><strong>start_date</strong>: Trip start date (YYYY-MM-DD format, required)</li>
                <li><strong>end_date</strong>: Trip end date (YYYY-MM-DD format, required)</li>
                <li><strong>country</strong>: Schengen country name (required)</li>
                <li><strong>category</strong>: "personal" or "business" (optional, defaults to personal)</li>
                <li><strong>notes</strong>: Trip notes (optional)</li>
              </ul>
              <p className="mt-3 font-medium text-gray-700">Sample CSV:</p>
              <pre className="mt-1 p-2 bg-white rounded border text-xs overflow-x-auto">
                {sampleCSV}
              </pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
