/**
 * DocumentWizard Component
 *
 * Step 2 of the document generation wizard.
 * Displays questions for the selected document type.
 */

import { AlertCircle, Info } from 'lucide-react';
import { DOCUMENT_QUESTIONS } from '@/config/documents';
import type { GeneratedDocumentType } from '@/types';

interface DocumentWizardProps {
  documentType: GeneratedDocumentType;
  answers: Record<string, string>;
  onAnswerChange: (questionId: string, value: string) => void;
  profileRequirements: { complete: boolean; missing: string[] };
}

export default function DocumentWizard({
  documentType,
  answers,
  onAnswerChange,
  profileRequirements,
}: DocumentWizardProps) {
  const questions = DOCUMENT_QUESTIONS[documentType] || [];
  const requiredCount = questions.filter((q) => q.required).length;
  const answeredCount = questions.filter((q) => q.required && answers[q.id]?.trim()).length;
  const progressPercent = requiredCount > 0 ? (answeredCount / requiredCount) * 100 : 0;

  return (
    <div className="max-w-2xl">
      <p className="text-gray-600 mb-6">
        Answer the following questions to personalize your document.
      </p>

      {/* Profile completion warning */}
      {!profileRequirements.complete && (
        <ProfileIncompleteWarning missing={profileRequirements.missing} />
      )}

      {/* Questions */}
      <div className="space-y-6" role="form" aria-label="Document questions">
        {questions.map((question) => (
          <QuestionField
            key={question.id}
            question={question}
            value={answers[question.id] || ''}
            onChange={(value) => onAnswerChange(question.id, value)}
          />
        ))}
      </div>

      {/* Progress indicator */}
      <div className="mt-8 pt-6 border-t border-gray-200">
        <div className="flex items-center justify-between text-sm text-gray-600 mb-2">
          <span>Question Progress</span>
          <span>
            {answeredCount} of {requiredCount} required
          </span>
        </div>
        <div
          className="w-full bg-gray-200 rounded-full h-2"
          role="progressbar"
          aria-valuenow={progressPercent}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label="Question completion progress"
        >
          <div
            className="bg-primary-600 h-2 rounded-full transition-all duration-300"
            style={{ width: `${progressPercent}%` }}
          />
        </div>
      </div>
    </div>
  );
}

/**
 * Warning displayed when profile is incomplete
 */
function ProfileIncompleteWarning({ missing }: { missing: string[] }) {
  return (
    <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6" role="alert">
      <div className="flex gap-3">
        <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
        <div>
          <h4 className="text-sm font-semibold text-amber-900 mb-1">
            Profile Incomplete
          </h4>
          <p className="text-sm text-amber-800 mb-2">
            Some required profile fields are missing. Please complete your profile first:
          </p>
          <ul className="text-sm text-amber-800 list-disc list-inside space-y-1">
            {missing.map((field) => (
              <li key={field}>
                {field.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}

/**
 * Individual question field renderer
 */
interface QuestionFieldProps {
  question: {
    id: string;
    label: string;
    type: 'text' | 'textarea' | 'select' | 'radio';
    required: boolean;
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
    helpText?: string;
  };
  value: string;
  onChange: (value: string) => void;
}

function QuestionField({ question, value, onChange }: QuestionFieldProps) {
  const inputId = `question-${question.id}`;
  const helpId = question.helpText ? `${inputId}-help` : undefined;

  return (
    <div>
      <label htmlFor={inputId} className="block text-sm font-medium text-gray-900 mb-2">
        {question.label}
        {question.required && <span className="text-red-500 ml-1" aria-label="required">*</span>}
      </label>

      {question.helpText && (
        <p id={helpId} className="text-sm text-gray-500 mb-3 flex items-start gap-2">
          <Info className="w-4 h-4 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <span>{question.helpText}</span>
        </p>
      )}

      {question.type === 'text' && (
        <input
          type="text"
          id={inputId}
          name={question.id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={question.placeholder}
          className="input w-full"
          required={question.required}
          aria-required={question.required}
          aria-describedby={helpId}
        />
      )}

      {question.type === 'textarea' && (
        <textarea
          id={inputId}
          name={question.id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={question.placeholder}
          rows={4}
          className="input w-full"
          required={question.required}
          aria-required={question.required}
          aria-describedby={helpId}
        />
      )}

      {question.type === 'select' && (
        <select
          id={inputId}
          name={question.id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="select w-full"
          required={question.required}
          aria-required={question.required}
          aria-describedby={helpId}
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
        <div className="space-y-3" role="radiogroup" aria-labelledby={inputId} aria-describedby={helpId}>
          {question.options?.map((opt) => (
            <label
              key={opt.value}
              className="flex items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
            >
              <input
                type="radio"
                name={question.id}
                value={opt.value}
                checked={value === opt.value}
                onChange={(e) => onChange(e.target.value)}
                className="mt-0.5"
                required={question.required}
              />
              <span className="text-sm text-gray-900">{opt.label}</span>
            </label>
          ))}
        </div>
      )}
    </div>
  );
}
