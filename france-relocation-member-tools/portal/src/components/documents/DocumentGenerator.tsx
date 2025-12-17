/**
 * DocumentGenerator Component
 *
 * A comprehensive document generation wizard for France relocation visa applications.
 * Supports generating cover letters, financial statements, no-work attestations, and accommodation letters.
 *
 * Features:
 * - 4-step wizard flow (Select Type → Answer Questions → Preview → Download)
 * - Auto-prefills from member profile data
 * - Bilingual support (English/French)
 * - Real-time document preview
 * - Multiple export formats (PDF, DOCX)
 * - Profile completion validation
 *
 * Usage:
 * <DocumentGenerator
 *   isOpen={showGenerator}
 *   onClose={() => setShowGenerator(false)}
 *   onSuccess={(documentId) => console.log('Generated:', documentId)}
 * />
 */

import { useState, useMemo, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  FileText,
  DollarSign,
  Home,
  FileCheck,
  ChevronRight,
  ChevronLeft,
  Download,
  Check,
  AlertCircle,
  Loader2,
  X,
  Info,
} from 'lucide-react';
import Modal from '@/components/shared/Modal';
import {
  useDocumentTypes,
  usePreviewDocument,
  useGenerateDocument,
  useMemberProfile,
  useDashboard,
} from '@/hooks/useApi';
import type { GeneratedDocumentType, DocumentPreview } from '@/types';

// Document type metadata
const DOCUMENT_ICONS: Record<string, typeof FileText> = {
  'cover-letter': FileText,
  'financial-statement': DollarSign,
  'no-work-attestation': FileCheck,
  'accommodation-letter': Home,
};

const DOCUMENT_COLORS: Record<string, string> = {
  'cover-letter': 'text-blue-600 bg-blue-50',
  'financial-statement': 'text-green-600 bg-green-50',
  'no-work-attestation': 'text-purple-600 bg-purple-50',
  'accommodation-letter': 'text-orange-600 bg-orange-50',
};

// Question definitions for each document type
const DOCUMENT_QUESTIONS: Record<string, Array<{
  id: string;
  label: string;
  type: 'text' | 'textarea' | 'select' | 'radio';
  required: boolean;
  options?: Array<{ value: string; label: string }>;
  placeholder?: string;
  helpText?: string;
  prefillFrom?: string; // Profile field to prefill from
}>> = {
  'cover-letter': [
    {
      id: 'property_status',
      label: 'Property Status',
      type: 'select',
      required: true,
      options: [
        { value: 'purchased', label: 'I have purchased a property in France' },
        { value: 'renting', label: 'I am renting in France' },
        { value: 'searching', label: 'I am currently searching for housing' },
      ],
      helpText: 'Select your current housing situation in France',
    },
    {
      id: 'move_reason',
      label: 'Primary Reason for Move',
      type: 'textarea',
      required: true,
      placeholder: 'Explain why you want to relocate to France...',
      helpText: 'A brief explanation of your motivation for moving to France',
    },
    {
      id: 'privacy_choice',
      label: 'Document Privacy Level',
      type: 'radio',
      required: true,
      options: [
        { value: 'real', label: 'Use my real information from profile' },
        { value: 'placeholder', label: 'Use placeholders (I will fill in later)' },
      ],
      helpText: 'Choose whether to use your actual profile data or placeholder text',
    },
  ],
  'financial-statement': [
    {
      id: 'statement_period',
      label: 'Statement Period',
      type: 'select',
      required: true,
      options: [
        { value: '3months', label: 'Last 3 months' },
        { value: '6months', label: 'Last 6 months' },
        { value: '12months', label: 'Last 12 months' },
      ],
    },
    {
      id: 'additional_notes',
      label: 'Additional Financial Information',
      type: 'textarea',
      required: false,
      placeholder: 'Any additional financial details to include...',
      helpText: 'Optional: Add any additional context about your financial situation',
    },
  ],
  'no-work-attestation': [
    {
      id: 'visa_type',
      label: 'Visa Type',
      type: 'select',
      required: true,
      options: [
        { value: 'visitor', label: 'Visitor Visa (Long-stay)' },
        { value: 'retiree', label: 'Retiree Visa' },
      ],
      prefillFrom: 'visa_type',
    },
    {
      id: 'intended_duration',
      label: 'Intended Stay Duration',
      type: 'select',
      required: true,
      options: [
        { value: '1year', label: '1 year' },
        { value: '2years', label: '2 years' },
        { value: '3years', label: '3 years' },
        { value: '4years', label: '4 years' },
      ],
    },
  ],
  'accommodation-letter': [
    {
      id: 'property_type',
      label: 'Property Type',
      type: 'select',
      required: true,
      options: [
        { value: 'apartment', label: 'Apartment' },
        { value: 'house', label: 'House' },
        { value: 'studio', label: 'Studio' },
      ],
    },
    {
      id: 'property_address',
      label: 'Property Address in France',
      type: 'textarea',
      required: true,
      placeholder: 'Full address including postal code...',
      prefillFrom: 'target_location',
    },
    {
      id: 'ownership_status',
      label: 'Ownership Status',
      type: 'radio',
      required: true,
      options: [
        { value: 'owner', label: 'I own this property' },
        { value: 'renting', label: 'I am renting this property' },
        { value: 'family', label: 'Staying with family/friends' },
      ],
    },
  ],
};

type WizardStep = 1 | 2 | 3 | 4;

interface DocumentGeneratorProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: (documentId: number) => void;
}

export default function DocumentGenerator({
  isOpen,
  onClose,
  onSuccess,
}: DocumentGeneratorProps) {
  const [currentStep, setCurrentStep] = useState<WizardStep>(1);
  const [selectedType, setSelectedType] = useState<GeneratedDocumentType | null>(null);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [language, setLanguage] = useState<'en' | 'fr'>('en');
  const [preview, setPreview] = useState<DocumentPreview | null>(null);

  // Hooks
  const { data: dashboard } = useDashboard();
  const { data: profile } = useMemberProfile();
  const { data: documentTypes = [], isLoading: typesLoading } = useDocumentTypes();
  const previewMutation = usePreviewDocument();
  const generateMutation = useGenerateDocument(dashboard?.project?.id || 0);

  // Auto-prefill answers from profile when type is selected
  useEffect(() => {
    if (selectedType && profile) {
      const questions = DOCUMENT_QUESTIONS[selectedType] || [];
      const prefilled: Record<string, string> = {};

      questions.forEach((q) => {
        if (q.prefillFrom && profile[q.prefillFrom as keyof typeof profile]) {
          prefilled[q.id] = String(profile[q.prefillFrom as keyof typeof profile]);
        }
      });

      setAnswers((prev: Record<string, string>) => ({ ...prev, ...prefilled }));
    }
  }, [selectedType, profile]);

  // Auto-set language based on application location
  useEffect(() => {
    if (profile?.application_location) {
      setLanguage(profile.application_location === 'france' ? 'fr' : 'en');
    }
  }, [profile?.application_location]);

  const handleTypeSelect = (type: GeneratedDocumentType) => {
    setSelectedType(type);
    setCurrentStep(2);
  };

  const handleAnswerChange = (questionId: string, value: string) => {
    setAnswers((prev: Record<string, string>) => ({ ...prev, [questionId]: value }));
  };

  const handleNext = () => {
    if (currentStep === 2) {
      // Validate answers before proceeding
      const questions = DOCUMENT_QUESTIONS[selectedType || ''] || [];
      const allRequiredAnswered = questions
        .filter((q) => q.required)
        .every((q) => answers[q.id]?.trim());

      if (!allRequiredAnswered) {
        return; // Don't proceed if required questions not answered
      }

      // Generate preview
      previewMutation.mutate(
        {
          document_type: selectedType!,
          language,
          answers,
        },
        {
          onSuccess: (response: { preview?: DocumentPreview }) => {
            if (response.preview) {
              setPreview(response.preview);
              setCurrentStep(3);
            }
          },
        }
      );
    } else if (currentStep === 3) {
      setCurrentStep(4);
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep((prev: WizardStep) => (prev - 1) as WizardStep);
    }
  };

  const handleGenerate = (format: 'pdf' | 'docx') => {
    generateMutation.mutate(
      {
        document_type: selectedType!,
        language,
        answers,
      },
      {
        onSuccess: (response: { document?: { id: number } }) => {
          if (response.document) {
            // Auto-download
            const url = `/wp-json/fra-portal/v1/documents/generated/${response.document.id}/download?format=${format}&_wpnonce=${window.fraPortalData?.nonce}`;
            window.open(url, '_blank');

            if (onSuccess) {
              onSuccess(response.document.id);
            }

            // Reset and close
            handleReset();
            onClose();
          }
        },
      }
    );
  };

  const handleReset = () => {
    setCurrentStep(1);
    setSelectedType(null);
    setAnswers({});
    setPreview(null);
  };

  const handleCloseModal = () => {
    handleReset();
    onClose();
  };

  // Check profile completion for selected document
  const profileRequirements = useMemo(() => {
    if (!selectedType) return { complete: true, missing: [] };

    const typeInfo = documentTypes.find((t: { type: string }) => t.type === selectedType);
    if (!typeInfo || !typeInfo.requires_profile) {
      return { complete: true, missing: [] };
    }

    const requiredFields = typeInfo.requires_profile;
    const missing = requiredFields.filter(
      (field: string) => !profile?.[field as keyof typeof profile]
    );

    return {
      complete: missing.length === 0,
      missing,
    };
  }, [selectedType, documentTypes, profile]);

  return (
    <Modal isOpen={isOpen} onClose={handleCloseModal} size="xl" showHeader={false}>
      <div className="relative">
        {/* Close button */}
        <button
          onClick={handleCloseModal}
          className="absolute top-4 right-4 z-10 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>

        {/* Header with stepper */}
        <div className="px-8 py-6 border-b border-gray-200">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Document Generator</h2>

          {/* Stepper */}
          <div className="flex items-center justify-between max-w-2xl">
            {[
              { step: 1, label: 'Select Type' },
              { step: 2, label: 'Answer Questions' },
              { step: 3, label: 'Preview' },
              { step: 4, label: 'Download' },
            ].map((item, index) => (
              <div key={item.step} className="flex items-center flex-1">
                <div className="flex items-center">
                  <div
                    className={clsx(
                      'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
                      currentStep >= item.step
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-200 text-gray-500'
                    )}
                  >
                    {currentStep > item.step ? (
                      <Check className="w-4 h-4" />
                    ) : (
                      item.step
                    )}
                  </div>
                  <span
                    className={clsx(
                      'ml-2 text-sm font-medium',
                      currentStep >= item.step ? 'text-gray-900' : 'text-gray-500'
                    )}
                  >
                    {item.label}
                  </span>
                </div>
                {index < 3 && (
                  <div
                    className={clsx(
                      'flex-1 h-0.5 mx-4',
                      currentStep > item.step ? 'bg-primary-600' : 'bg-gray-200'
                    )}
                  />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Content area */}
        <div className="px-8 py-6 min-h-[400px]">
          {currentStep === 1 && (
            <DocumentTypeSelector
              documentTypes={documentTypes}
              isLoading={typesLoading}
              onSelect={handleTypeSelect}
            />
          )}

          {currentStep === 2 && selectedType && (
            <DocumentWizard
              documentType={selectedType}
              answers={answers}
              onAnswerChange={handleAnswerChange}
              profileRequirements={profileRequirements}
            />
          )}

          {currentStep === 3 && preview && (
            <DocumentPreviewStep
              preview={preview}
              language={language}
              onLanguageChange={setLanguage}
              isLoading={previewMutation.isPending}
              onEditAnswers={() => setCurrentStep(2)}
            />
          )}

          {currentStep === 4 && (
            <DownloadOptions
              documentType={selectedType!}
              language={language}
              onDownload={handleGenerate}
              isGenerating={generateMutation.isPending}
            />
          )}
        </div>

        {/* Footer with navigation */}
        <div className="px-8 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
          <div>
            {currentStep > 1 && (
              <button
                onClick={handleBack}
                disabled={previewMutation.isPending || generateMutation.isPending}
                className="btn btn-secondary"
              >
                <ChevronLeft className="w-4 h-4" />
                Back
              </button>
            )}
          </div>

          <div className="flex items-center gap-3">
            {previewMutation.isError && (
              <p className="text-sm text-red-600">
                Failed to generate preview. Please try again.
              </p>
            )}

            {generateMutation.isError && (
              <p className="text-sm text-red-600">
                Failed to generate document. Please try again.
              </p>
            )}

            {currentStep === 2 && (
              <button
                onClick={handleNext}
                disabled={
                  !profileRequirements.complete ||
                  previewMutation.isPending ||
                  !DOCUMENT_QUESTIONS[selectedType || '']
                    ?.filter((q) => q.required)
                    .every((q) => answers[q.id]?.trim())
                }
                className="btn btn-primary"
              >
                {previewMutation.isPending ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Generating Preview...
                  </>
                ) : (
                  <>
                    Preview Document
                    <ChevronRight className="w-4 h-4" />
                  </>
                )}
              </button>
            )}

            {currentStep === 3 && (
              <button onClick={handleNext} className="btn btn-primary">
                Continue to Download
                <ChevronRight className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>
      </div>
    </Modal>
  );
}

// Step 1: Document Type Selector
interface DocumentTypeSelectorProps {
  documentTypes: Array<{ type: string; label: string; description: string }>;
  isLoading: boolean;
  onSelect: (type: GeneratedDocumentType) => void;
}

function DocumentTypeSelector({
  documentTypes,
  isLoading,
  onSelect,
}: DocumentTypeSelectorProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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

  const defaultTypes = [
    {
      type: 'cover-letter',
      label: 'Cover Letter',
      description: 'Personalized visa application cover letter in English or French',
    },
    {
      type: 'financial-statement',
      label: 'Financial Statement',
      description: 'Attestation of sufficient financial means for visa application',
    },
    {
      type: 'no-work-attestation',
      label: 'No Work Attestation',
      description: 'Declaration that you will not work in France (for visitor visa)',
    },
    {
      type: 'accommodation-letter',
      label: 'Accommodation Letter',
      description: 'Proof of housing in France for visa application',
    },
  ];

  const types = documentTypes.length > 0 ? documentTypes : defaultTypes;

  return (
    <div>
      <p className="text-gray-600 mb-6">
        Select the type of document you want to generate for your visa application.
      </p>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {types.map((docType) => {
          const Icon = DOCUMENT_ICONS[docType.type] || FileText;
          const colorClass = DOCUMENT_COLORS[docType.type] || 'text-gray-600 bg-gray-50';

          return (
            <button
              key={docType.type}
              onClick={() => onSelect(docType.type as GeneratedDocumentType)}
              className="card p-6 text-left hover:shadow-lg transition-all hover:-translate-y-0.5 group"
            >
              <div className={clsx('w-12 h-12 rounded-lg flex items-center justify-center mb-4', colorClass)}>
                <Icon className="w-6 h-6" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
                {docType.label}
              </h3>
              <p className="text-sm text-gray-600 leading-relaxed">
                {docType.description}
              </p>
              <div className="mt-4 flex items-center text-sm text-primary-600 font-medium">
                <span>Generate document</span>
                <ChevronRight className="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
              </div>
            </button>
          );
        })}
      </div>
    </div>
  );
}

// Step 2: Document Wizard
interface DocumentWizardProps {
  documentType: GeneratedDocumentType;
  answers: Record<string, string>;
  onAnswerChange: (questionId: string, value: string) => void;
  profileRequirements: { complete: boolean; missing: string[] };
}

function DocumentWizard({
  documentType,
  answers,
  onAnswerChange,
  profileRequirements,
}: DocumentWizardProps) {
  const questions = DOCUMENT_QUESTIONS[documentType] || [];

  return (
    <div className="max-w-2xl">
      <p className="text-gray-600 mb-6">
        Answer the following questions to personalize your document.
      </p>

      {/* Profile completion warning */}
      {!profileRequirements.complete && (
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
          <div className="flex gap-3">
            <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
            <div>
              <h4 className="text-sm font-semibold text-amber-900 mb-1">
                Profile Incomplete
              </h4>
              <p className="text-sm text-amber-800 mb-2">
                Some required profile fields are missing. Please complete your profile first:
              </p>
              <ul className="text-sm text-amber-800 list-disc list-inside space-y-1">
                {profileRequirements.missing.map((field) => (
                  <li key={field}>{field.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())}</li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      )}

      {/* Questions */}
      <div className="space-y-6">
        {questions.map((question, index) => (
          <div key={question.id}>
            <label className="block text-sm font-medium text-gray-900 mb-2">
              {question.label}
              {question.required && <span className="text-red-500 ml-1">*</span>}
            </label>

            {question.helpText && (
              <p className="text-sm text-gray-500 mb-3 flex items-start gap-2">
                <Info className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>{question.helpText}</span>
              </p>
            )}

            {question.type === 'text' && (
              <input
                type="text"
                value={answers[question.id] || ''}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => onAnswerChange(question.id, e.target.value)}
                placeholder={question.placeholder}
                className="input w-full"
                required={question.required}
              />
            )}

            {question.type === 'textarea' && (
              <textarea
                value={answers[question.id] || ''}
                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => onAnswerChange(question.id, e.target.value)}
                placeholder={question.placeholder}
                rows={4}
                className="input w-full"
                required={question.required}
              />
            )}

            {question.type === 'select' && (
              <select
                value={answers[question.id] || ''}
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => onAnswerChange(question.id, e.target.value)}
                className="select w-full"
                required={question.required}
              >
                <option value="">-- Select an option --</option>
                {question.options?.map((opt) => (
                  <option key={opt.value} value={opt.value}>
                    {opt.label}
                  </option>
                ))}
              </select>
            )}

            {question.type === 'radio' && (
              <div className="space-y-3">
                {question.options?.map((opt) => (
                  <label
                    key={opt.value}
                    className="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                  >
                    <input
                      type="radio"
                      name={question.id}
                      value={opt.value}
                      checked={answers[question.id] === opt.value}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => onAnswerChange(question.id, e.target.value)}
                      className="mt-0.5"
                      required={question.required}
                    />
                    <span className="text-sm text-gray-900">{opt.label}</span>
                  </label>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>

      {/* Progress indicator */}
      <div className="mt-8 pt-6 border-t border-gray-200">
        <div className="flex items-center justify-between text-sm text-gray-600 mb-2">
          <span>Question Progress</span>
          <span>
            {questions.filter((q) => q.required && answers[q.id]?.trim()).length} of{' '}
            {questions.filter((q) => q.required).length} required
          </span>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className="bg-primary-600 h-2 rounded-full transition-all duration-300"
            style={{
              width: `${
                (questions.filter((q) => q.required && answers[q.id]?.trim()).length /
                  questions.filter((q) => q.required).length) *
                100
              }%`,
            }}
          />
        </div>
      </div>
    </div>
  );
}

// Step 3: Document Preview
interface DocumentPreviewStepProps {
  preview: DocumentPreview;
  language: 'en' | 'fr';
  onLanguageChange: (lang: 'en' | 'fr') => void;
  isLoading: boolean;
  onEditAnswers: () => void;
}

function DocumentPreviewStep({
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
          <div className="flex rounded-lg bg-gray-100 p-1">
            <button
              onClick={() => onLanguageChange('en')}
              className={clsx(
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                language === 'en'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              English
            </button>
            <button
              onClick={() => onLanguageChange('fr')}
              className={clsx(
                'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                language === 'fr'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              Français
            </button>
          </div>

          <button onClick={onEditAnswers} className="text-sm text-primary-600 hover:text-primary-700">
            Edit answers
          </button>
        </div>
      </div>

      {/* Preview content */}
      <div className="bg-white border border-gray-200 rounded-lg p-8 shadow-sm max-h-[500px] overflow-y-auto">
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
      </div>

      {isLoading && (
        <div className="absolute inset-0 bg-white/80 flex items-center justify-center">
          <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
        </div>
      )}
    </div>
  );
}

// Step 4: Download Options
interface DownloadOptionsProps {
  documentType: GeneratedDocumentType;
  language: 'en' | 'fr';
  onDownload: (format: 'pdf' | 'docx') => void;
  isGenerating: boolean;
}

function DownloadOptions({
  onDownload,
  isGenerating,
}: DownloadOptionsProps) {
  return (
    <div className="max-w-2xl mx-auto text-center">
      <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <Check className="w-8 h-8 text-green-600" />
      </div>

      <h3 className="text-2xl font-bold text-gray-900 mb-2">Document Ready!</h3>
      <p className="text-gray-600 mb-8">
        Your document is ready to download. Choose your preferred format below.
      </p>

      {/* Format options */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <button
          onClick={() => onDownload('pdf')}
          disabled={isGenerating}
          className="card p-6 hover:shadow-lg transition-all hover:-translate-y-0.5 text-left group"
        >
          <div className="flex items-start gap-4">
            <div className="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
              <FileText className="w-6 h-6 text-red-600" />
            </div>
            <div className="flex-1">
              <h4 className="text-lg font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
                Download as PDF
              </h4>
              <p className="text-sm text-gray-600">
                Recommended for official submissions. Cannot be edited.
              </p>
              <div className="mt-3 flex items-center text-sm text-primary-600 font-medium">
                <Download className="w-4 h-4 mr-1" />
                {isGenerating ? 'Generating...' : 'Download PDF'}
              </div>
            </div>
          </div>
        </button>

        <button
          onClick={() => onDownload('docx')}
          disabled={isGenerating}
          className="card p-6 hover:shadow-lg transition-all hover:-translate-y-0.5 text-left group"
        >
          <div className="flex items-start gap-4">
            <div className="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
              <FileText className="w-6 h-6 text-blue-600" />
            </div>
            <div className="flex-1">
              <h4 className="text-lg font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
                Download as Word
              </h4>
              <p className="text-sm text-gray-600">
                Editable format. Customize further in Microsoft Word.
              </p>
              <div className="mt-3 flex items-center text-sm text-primary-600 font-medium">
                <Download className="w-4 h-4 mr-1" />
                {isGenerating ? 'Generating...' : 'Download DOCX'}
              </div>
            </div>
          </div>
        </button>
      </div>

      {/* Info box */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
        <div className="flex gap-3">
          <Info className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
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

      {isGenerating && (
        <div className="mt-6 flex items-center justify-center gap-2 text-primary-600">
          <Loader2 className="w-5 h-5 animate-spin" />
          <span>Generating your document...</span>
        </div>
      )}
    </div>
  );
}
