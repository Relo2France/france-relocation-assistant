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
 * Refactored to use extracted sub-components for better maintainability.
 */

import { useState, useMemo, useEffect } from 'react';
import { clsx } from 'clsx';
import { ChevronRight, ChevronLeft, Loader2, X, Check } from 'lucide-react';
import Modal from '@/components/shared/Modal';
import {
  useDocumentTypes,
  usePreviewDocument,
  useGenerateDocument,
  useMemberProfile,
  useDashboard,
} from '@/hooks/useApi';
import { DOCUMENT_QUESTIONS, WIZARD_STEPS, type WizardStep } from '@/config/documents';
import DocumentTypeSelector from './DocumentTypeSelector';
import DocumentWizard from './DocumentWizard';
import DocumentPreviewStep from './DocumentPreviewStep';
import DownloadOptions from './DownloadOptions';
import type { GeneratedDocumentType, DocumentPreview } from '@/types';

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

      // Generate preview - selectedType is guaranteed non-null when currentStep === 2
      if (!selectedType) return;
      previewMutation.mutate(
        {
          document_type: selectedType,
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
    // selectedType is guaranteed non-null when reaching step 4
    if (!selectedType) return;
    generateMutation.mutate(
      {
        document_type: selectedType,
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

  // Check if all required questions are answered
  const allRequiredAnswered = useMemo(() => {
    if (!selectedType) return false;
    const questions = DOCUMENT_QUESTIONS[selectedType] || [];
    return questions
      .filter((q) => q.required)
      .every((q) => answers[q.id]?.trim());
  }, [selectedType, answers]);

  return (
    <Modal isOpen={isOpen} onClose={handleCloseModal} size="xl" showHeader={false}>
      <div className="relative">
        {/* Close button */}
        <button
          onClick={handleCloseModal}
          className="absolute top-4 right-4 z-10 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
          aria-label="Close document generator"
        >
          <X className="w-5 h-5" aria-hidden="true" />
        </button>

        {/* Header with stepper */}
        <WizardHeader currentStep={currentStep} />

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
              onDownload={handleGenerate}
              isGenerating={generateMutation.isPending}
            />
          )}
        </div>

        {/* Footer with navigation */}
        <WizardFooter
          currentStep={currentStep}
          onBack={handleBack}
          onNext={handleNext}
          canProceed={profileRequirements.complete && allRequiredAnswered}
          isPreviewLoading={previewMutation.isPending}
          isGenerating={generateMutation.isPending}
          previewError={previewMutation.isError}
          generateError={generateMutation.isError}
        />
      </div>
    </Modal>
  );
}

/**
 * Wizard header with step indicator
 */
function WizardHeader({ currentStep }: { currentStep: WizardStep }) {
  return (
    <div className="px-8 py-6 border-b border-gray-200">
      <h2 className="text-2xl font-bold text-gray-900 mb-4">Document Generator</h2>

      {/* Stepper */}
      <nav aria-label="Progress steps">
        <ol className="flex items-center justify-between max-w-2xl">
          {WIZARD_STEPS.map((item, index) => (
            <li key={item.step} className="flex items-center flex-1">
              <div className="flex items-center">
                <div
                  className={clsx(
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors',
                    currentStep >= item.step
                      ? 'bg-primary-600 text-white'
                      : 'bg-gray-200 text-gray-500'
                  )}
                  aria-current={currentStep === item.step ? 'step' : undefined}
                >
                  {currentStep > item.step ? (
                    <Check className="w-4 h-4" aria-hidden="true" />
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
              {index < WIZARD_STEPS.length - 1 && (
                <div
                  className={clsx(
                    'flex-1 h-0.5 mx-4',
                    currentStep > item.step ? 'bg-primary-600' : 'bg-gray-200'
                  )}
                  aria-hidden="true"
                />
              )}
            </li>
          ))}
        </ol>
      </nav>
    </div>
  );
}

/**
 * Wizard footer with navigation buttons
 */
function WizardFooter({
  currentStep,
  onBack,
  onNext,
  canProceed,
  isPreviewLoading,
  isGenerating,
  previewError,
  generateError,
}: {
  currentStep: WizardStep;
  onBack: () => void;
  onNext: () => void;
  canProceed: boolean;
  isPreviewLoading: boolean;
  isGenerating: boolean;
  previewError: boolean;
  generateError: boolean;
}) {
  return (
    <div className="px-8 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
      <div>
        {currentStep > 1 && (
          <button
            onClick={onBack}
            disabled={isPreviewLoading || isGenerating}
            className="btn btn-secondary"
          >
            <ChevronLeft className="w-4 h-4" aria-hidden="true" />
            Back
          </button>
        )}
      </div>

      <div className="flex items-center gap-3">
        {previewError && (
          <p className="text-sm text-red-600" role="alert">
            Failed to generate preview. Please try again.
          </p>
        )}

        {generateError && (
          <p className="text-sm text-red-600" role="alert">
            Failed to generate document. Please try again.
          </p>
        )}

        {currentStep === 2 && (
          <button
            onClick={onNext}
            disabled={!canProceed || isPreviewLoading}
            className="btn btn-primary"
          >
            {isPreviewLoading ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                Generating Preview...
              </>
            ) : (
              <>
                Preview Document
                <ChevronRight className="w-4 h-4" aria-hidden="true" />
              </>
            )}
          </button>
        )}

        {currentStep === 3 && (
          <button onClick={onNext} className="btn btn-primary">
            Continue to Download
            <ChevronRight className="w-4 h-4" aria-hidden="true" />
          </button>
        )}
      </div>
    </div>
  );
}
