/**
 * PersonalSection Component
 *
 * Personal information section of the profile form.
 * Handles legal names, DOB, nationality, and passport information.
 * Uses dynamic field rendering for consistent sizing.
 */

import { useState, useEffect, useRef } from 'react';
import { AlertCircle } from 'lucide-react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import type { MemberProfile } from '@/types';

interface PersonalSectionProps {
  profile: MemberProfile | undefined;
}

type FieldSize = 'compact' | 'short' | 'medium';

interface FieldConfig {
  name: keyof typeof initialFormData;
  label: string;
  type: 'text' | 'date';
  size: FieldSize;
  required?: boolean;
  placeholder?: string;
  autoComplete?: string;
}

const initialFormData = {
  legal_first_name: '',
  legal_middle_name: '',
  legal_last_name: '',
  date_of_birth: '',
  nationality: '',
  passport_number: '',
  passport_expiry: '',
};

// Field configuration for identity & passport section
const identityFields: FieldConfig[] = [
  {
    name: 'date_of_birth',
    label: 'Date of Birth',
    type: 'date',
    size: 'compact',
    required: true,
    autoComplete: 'bday',
  },
  {
    name: 'nationality',
    label: 'Nationality',
    type: 'text',
    size: 'short',
    required: true,
    placeholder: 'e.g., American',
  },
  {
    name: 'passport_number',
    label: 'Passport Number',
    type: 'text',
    size: 'medium',
    autoComplete: 'off',
  },
  {
    name: 'passport_expiry',
    label: 'Passport Expiry',
    type: 'date',
    size: 'compact',
  },
];

// All fields use consistent width in grid
const fieldClass = 'w-full';

export default function PersonalSection({ profile }: PersonalSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const initializedRef = useRef(false);
  const [formData, setFormData] = useState(initialFormData);

  // Sync form with profile data when first loaded
  useEffect(() => {
    if (profile && !initializedRef.current) {
      setFormData({
        legal_first_name: profile.legal_first_name || '',
        legal_middle_name: profile.legal_middle_name || '',
        legal_last_name: profile.legal_last_name || '',
        date_of_birth: profile.date_of_birth || '',
        nationality: profile.nationality || '',
        passport_number: profile.passport_number || '',
        passport_expiry: profile.passport_expiry || '',
      });
      initializedRef.current = true;
    }
  }, [profile]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  const handleFieldChange = (name: keyof typeof formData, value: string) => {
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const renderField = (field: FieldConfig) => {
    const value = formData[field.name];

    return (
      <div key={field.name}>
        <label htmlFor={field.name} className="block text-sm font-medium text-gray-700 mb-1">
          {field.label}
          {field.required && <span className="text-red-500 ml-1" aria-label="required">*</span>}
        </label>
        <input
          type={field.type}
          id={field.name}
          name={field.name}
          value={value}
          onChange={(e) => handleFieldChange(field.name, e.target.value)}
          className={`${fieldClass} px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500`}
          required={field.required}
          aria-required={field.required}
          placeholder={field.placeholder}
          autoComplete={field.autoComplete}
        />
      </div>
    );
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-6">
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4" role="note">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <div className="text-sm text-blue-800">
            <p className="font-medium">Important: Legal Names Required</p>
            <p className="mt-1">Use your passport/legal names exactly as they appear on official documents.</p>
          </div>
        </div>
      </div>

      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Legal Name</legend>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label htmlFor="legal_first_name" className="block text-sm font-medium text-gray-700 mb-1">
              First Name <span className="text-red-500" aria-label="required">*</span>
            </label>
            <input
              type="text"
              id="legal_first_name"
              name="legal_first_name"
              value={formData.legal_first_name}
              onChange={(e) => handleFieldChange('legal_first_name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
              required
              aria-required="true"
              autoComplete="given-name"
            />
          </div>
          <div>
            <label htmlFor="legal_middle_name" className="block text-sm font-medium text-gray-700 mb-1">
              Middle Name
            </label>
            <input
              type="text"
              id="legal_middle_name"
              name="legal_middle_name"
              value={formData.legal_middle_name}
              onChange={(e) => handleFieldChange('legal_middle_name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
              autoComplete="additional-name"
            />
          </div>
          <div>
            <label htmlFor="legal_last_name" className="block text-sm font-medium text-gray-700 mb-1">
              Last Name <span className="text-red-500" aria-label="required">*</span>
            </label>
            <input
              type="text"
              id="legal_last_name"
              name="legal_last_name"
              value={formData.legal_last_name}
              onChange={(e) => handleFieldChange('legal_last_name', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
              required
              aria-required="true"
              autoComplete="family-name"
            />
          </div>
        </div>
      </fieldset>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {identityFields.map(renderField)}
      </div>

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
