/**
 * DownloadOptions Component
 *
 * Step 4 of the document generation wizard.
 * Displays download format options (PDF/DOCX).
 */

import { Check, FileText, Download, Info, Loader2 } from 'lucide-react';

interface DownloadOptionsProps {
  onDownload: (format: 'pdf' | 'docx') => void;
  isGenerating: boolean;
}

export default function DownloadOptions({
  onDownload,
  isGenerating,
}: DownloadOptionsProps) {
  return (
    <div className="max-w-2xl mx-auto text-center">
      {/* Success icon */}
      <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <Check className="w-8 h-8 text-green-600" aria-hidden="true" />
      </div>

      <h3 className="text-2xl font-bold text-gray-900 mb-2">Document Ready!</h3>
      <p className="text-gray-600 mb-8">
        Your document is ready to download. Choose your preferred format below.
      </p>

      {/* Format options */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8" role="group" aria-label="Download format options">
        <FormatButton
          format="pdf"
          label="Download as PDF"
          description="Recommended for official submissions. Cannot be edited."
          iconBgColor="bg-red-50"
          iconColor="text-red-600"
          onClick={() => onDownload('pdf')}
          isGenerating={isGenerating}
        />

        <FormatButton
          format="docx"
          label="Download as Word"
          description="Editable format. Customize further in Microsoft Word."
          iconBgColor="bg-blue-50"
          iconColor="text-blue-600"
          onClick={() => onDownload('docx')}
          isGenerating={isGenerating}
        />
      </div>

      {/* Info box */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left" role="note">
        <div className="flex gap-3">
          <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <div className="text-sm text-blue-900">
            <p className="font-semibold mb-1">What happens after download?</p>
            <ul className="space-y-1 list-disc list-inside">
              <li>The document will be saved to your Documents section</li>
              <li>You can download it again anytime from the Documents view</li>
              <li>The document is marked as generated (not an uploaded file)</li>
            </ul>
          </div>
        </div>
      </div>

      {/* Generating indicator */}
      {isGenerating && (
        <div className="mt-6 flex items-center justify-center gap-2 text-primary-600" role="status">
          <Loader2 className="w-5 h-5 animate-spin" aria-hidden="true" />
          <span>Generating your document...</span>
        </div>
      )}
    </div>
  );
}

/**
 * Format download button
 */
interface FormatButtonProps {
  format: 'pdf' | 'docx';
  label: string;
  description: string;
  iconBgColor: string;
  iconColor: string;
  onClick: () => void;
  isGenerating: boolean;
}

function FormatButton({
  format,
  label,
  description,
  iconBgColor,
  iconColor,
  onClick,
  isGenerating,
}: FormatButtonProps) {
  return (
    <button
      onClick={onClick}
      disabled={isGenerating}
      className="card p-6 hover:shadow-lg transition-all hover:-translate-y-0.5 text-left group disabled:opacity-50 disabled:cursor-not-allowed"
      aria-label={`Download document as ${format.toUpperCase()}`}
    >
      <div className="flex items-start gap-4">
        <div className={`w-12 h-12 ${iconBgColor} rounded-lg flex items-center justify-center flex-shrink-0`}>
          <FileText className={`w-6 h-6 ${iconColor}`} aria-hidden="true" />
        </div>
        <div className="flex-1">
          <h4 className="text-lg font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
            {label}
          </h4>
          <p className="text-sm text-gray-600">
            {description}
          </p>
          <div className="mt-3 flex items-center text-sm text-primary-600 font-medium">
            <Download className="w-4 h-4 mr-1" aria-hidden="true" />
            {isGenerating ? 'Generating...' : `Download ${format.toUpperCase()}`}
          </div>
        </div>
      </div>
    </button>
  );
}
