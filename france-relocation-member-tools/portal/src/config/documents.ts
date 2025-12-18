/**
 * Document Generation Configuration
 * Extracted from DocumentGenerator for reusability and maintainability
 */

import { FileText, DollarSign, Home, FileCheck } from 'lucide-react';

// Document type icons
export const DOCUMENT_ICONS: Record<string, typeof FileText> = {
  'cover-letter': FileText,
  'financial-statement': DollarSign,
  'no-work-attestation': FileCheck,
  'accommodation-letter': Home,
};

// Document type color classes
export const DOCUMENT_COLORS: Record<string, string> = {
  'cover-letter': 'text-blue-600 bg-blue-50',
  'financial-statement': 'text-green-600 bg-green-50',
  'no-work-attestation': 'text-purple-600 bg-purple-50',
  'accommodation-letter': 'text-orange-600 bg-orange-50',
};

// Default document types when API doesn't return any
export const DEFAULT_DOCUMENT_TYPES = [
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
] as const;

// Question definition interface
export interface DocumentQuestion {
  id: string;
  label: string;
  type: 'text' | 'textarea' | 'select' | 'radio';
  required: boolean;
  options?: Array<{ value: string; label: string }>;
  placeholder?: string;
  helpText?: string;
  prefillFrom?: string; // Profile field to prefill from
}

// Question definitions for each document type
export const DOCUMENT_QUESTIONS: Record<string, DocumentQuestion[]> = {
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

// Wizard step configuration
export const WIZARD_STEPS = [
  { step: 1, label: 'Select Type' },
  { step: 2, label: 'Answer Questions' },
  { step: 3, label: 'Preview' },
  { step: 4, label: 'Download' },
] as const;

export type WizardStep = 1 | 2 | 3 | 4;
