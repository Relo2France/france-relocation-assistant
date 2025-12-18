/**
 * DocumentPreviewStep Component
 *
 * Step 3 of the document generation wizard.
 * Displays a preview of the generated document with language toggle.
 */

import { clsx } from 'clsx';
import { Loader2 } from 'lucide-react';
import type { DocumentPreview } from '@/types';

interface DocumentPreviewStepProps {
  preview: DocumentPreview;
  language: 'en' | 'fr';
  onLanguageChange: (lang: 'en' | 'fr') => void;
  isLoading: boolean;
  onEditAnswers: () => void;
}

export default function DocumentPreviewStep({
  preview,
  language,
  onLanguageChange,
  isLoading,
  onEditAnswers,
}: DocumentPreviewStepProps) {
  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-lg font-semibold text-gray-900">Document Preview</h3>

        <div className="flex items-center gap-4">
          {/* Language toggle */}
          <LanguageToggle language={language} onChange={onLanguageChange} />

          <button
            onClick={onEditAnswers}
            className="text-sm text-primary-600 hover:text-primary-700"
          >
            Edit answers
          </button>
        </div>
      </div>

      {/* Preview content */}
      <div className="relative bg-white border border-gray-200 rounded-lg p-8 shadow-sm max-h-[500px] overflow-y-auto">
        <DocumentContent preview={preview} />

        {isLoading && (
          <div className="absolute inset-0 bg-white/80 flex items-center justify-center" role="status">
            <Loader2 className="w-8 h-8 text-primary-600 animate-spin" aria-hidden="true" />
            <span className="sr-only">Loading preview...</span>
          </div>
        )}
      </div>
    </div>
  );
}

/**
 * Language toggle buttons
 */
function LanguageToggle({
  language,
  onChange,
}: {
  language: 'en' | 'fr';
  onChange: (lang: 'en' | 'fr') => void;
}) {
  return (
    <div className="flex rounded-lg bg-gray-100 p-1" role="group" aria-label="Document language">
      <button
        onClick={() => onChange('en')}
        className={clsx(
          'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
          language === 'en'
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-600 hover:text-gray-900'
        )}
        aria-pressed={language === 'en'}
      >
        English
      </button>
      <button
        onClick={() => onChange('fr')}
        className={clsx(
          'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
          language === 'fr'
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-600 hover:text-gray-900'
        )}
        aria-pressed={language === 'fr'}
      >
        Français
      </button>
    </div>
  );
}

/**
 * Document content renderer
 */
function DocumentContent({ preview }: { preview: DocumentPreview }) {
  return (
    <div className="prose max-w-none">
      {/* Header */}
      {preview.content.header && (
        <div className="mb-6">
          <p className="text-sm text-gray-600 mb-2">{preview.content.header.date}</p>
          {preview.content.header.recipient.map((line: string, i: number) => (
            <p key={i} className="text-sm text-gray-900 mb-1">
              {line}
            </p>
          ))}
        </div>
      )}

      {/* Subject */}
      {preview.content.subject && (
        <p className="font-semibold text-gray-900 mb-4">
          {preview.content.subject}
        </p>
      )}

      {/* Salutation */}
      {preview.content.salutation && (
        <p className="text-gray-900 mb-4">{preview.content.salutation}</p>
      )}

      {/* Paragraphs */}
      {preview.content.paragraphs.map((para: string, i: number) => (
        <p key={i} className="text-gray-900 mb-4 leading-relaxed">
          {para}
        </p>
      ))}

      {/* Closing */}
      {preview.content.closing && (
        <p className="text-gray-900 mb-6">{preview.content.closing}</p>
      )}

      {/* Signature */}
      {preview.content.signature && (
        <div className="mt-8">
          <p className="text-gray-900 mb-1">{preview.content.signature.line}</p>
          <p className="text-gray-900 font-semibold">{preview.content.signature.name}</p>
          {preview.content.signature.date_line && (
            <p className="text-sm text-gray-600 mt-2">{preview.content.signature.date_line}</p>
          )}
        </div>
      )}
    </div>
  );
}
