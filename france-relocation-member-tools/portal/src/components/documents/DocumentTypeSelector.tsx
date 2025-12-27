/**
 * DocumentTypeSelector Component
 *
 * Step 1 of the document generation wizard.
 * Displays available document types for selection.
 */

import { clsx } from 'clsx';
import { FileText, ChevronRight } from 'lucide-react';
import { DOCUMENT_ICONS, DOCUMENT_COLORS, DEFAULT_DOCUMENT_TYPES } from '@/config/documents';
import type { GeneratedDocumentType } from '@/types';

interface DocumentTypeSelectorProps {
  documentTypes: Array<{ type: string; label: string; description: string }>;
  isLoading: boolean;
  onSelect: (type: GeneratedDocumentType) => void;
}

export default function DocumentTypeSelector({
  documentTypes,
  isLoading,
  onSelect,
}: DocumentTypeSelectorProps) {
  if (isLoading) {
    return <DocumentTypeSelectorSkeleton />;
  }

  const types = documentTypes.length > 0 ? documentTypes : DEFAULT_DOCUMENT_TYPES;

  return (
    <div>
      <p className="text-gray-600 mb-6">
        Select the type of document you want to generate for your visa application.
      </p>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4" role="list" aria-label="Document types">
        {types.map((docType) => {
          const Icon = DOCUMENT_ICONS[docType.type] || FileText;
          const colorClass = DOCUMENT_COLORS[docType.type] || 'text-gray-600 bg-gray-50';

          return (
            <button
              key={docType.type}
              onClick={() => onSelect(docType.type as GeneratedDocumentType)}
              className="card p-6 text-left hover:shadow-lg transition-all hover:-translate-y-0.5 group"
              aria-label={`Generate ${docType.label}`}
            >
              <div className={clsx('w-12 h-12 rounded-lg flex items-center justify-center mb-4', colorClass)}>
                <Icon className="w-6 h-6" aria-hidden="true" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
                {docType.label}
              </h3>
              <p className="text-sm text-gray-600 leading-relaxed">
                {docType.description}
              </p>
              <div className="mt-4 flex items-center text-sm text-primary-600 font-medium">
                <span>Generate document</span>
                <ChevronRight className="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" aria-hidden="true" />
              </div>
            </button>
          );
        })}
      </div>
    </div>
  );
}

/**
 * Loading skeleton for document type selector
 */
function DocumentTypeSelectorSkeleton() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4" aria-busy="true" aria-label="Loading document types">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="card p-6 animate-pulse">
          <div className="w-12 h-12 bg-gray-200 rounded-lg mb-4" />
          <div className="h-5 bg-gray-200 rounded w-3/4 mb-2" />
          <div className="h-4 bg-gray-100 rounded w-full mb-1" />
          <div className="h-4 bg-gray-100 rounded w-2/3" />
        </div>
      ))}
    </div>
  );
}
