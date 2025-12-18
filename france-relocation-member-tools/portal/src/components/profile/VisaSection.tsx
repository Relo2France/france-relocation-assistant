/**
 * VisaSection Component
 *
 * Visa and employment information section of the profile form.
 * Handles visa type, employment status, and work plans.
 */

import { useState, useEffect } from 'react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import {
  VISA_TYPES,
  WORK_STATUS_OPTIONS,
  WORK_IN_FRANCE_OPTIONS,
} from '@/config/profile';
import type {
  MemberProfile,
  ProfileVisaType,
  WorkStatus,
  WorkInFranceType,
} from '@/types';

interface VisaSectionProps {
  profile: MemberProfile | undefined;
}

export default function VisaSection({ profile }: VisaSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    visa_type: 'undecided' as ProfileVisaType,
    employment_status: 'not_working' as WorkStatus,
    work_in_france: 'undecided' as WorkInFranceType,
    industry: '',
    employer_name: '',
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        visa_type: (profile.visa_type as ProfileVisaType) || 'undecided',
        employment_status: (profile.employment_status as WorkStatus) || 'not_working',
        work_in_france: (profile.work_in_france as WorkInFranceType) || 'undecided',
        industry: profile.industry || '',
        employer_name: profile.employer_name || '',
      });
      setInitialized(true);
    }
  }, [profile, initialized]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  const isEmployed = formData.employment_status === 'employed' || formData.employment_status === 'self_employed';

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-6">
      <div>
        <label htmlFor="visa_type" className="block text-sm font-medium text-gray-700 mb-1">
          Visa Type <span className="text-red-500" aria-label="required">*</span>
        </label>
        <select
          id="visa_type"
          name="visa_type"
          value={formData.visa_type}
          onChange={(e) => setFormData({ ...formData, visa_type: e.target.value as ProfileVisaType })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          required
          aria-required="true"
          aria-describedby="visa_type_hint"
        >
          {VISA_TYPES.map((type) => (
            <option key={type.value} value={type.value}>
              {type.label} - {type.description}
            </option>
          ))}
        </select>
        <p id="visa_type_hint" className="mt-1 text-sm text-gray-500">
          Select the visa type that best matches your situation. We'll help you refine this choice.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="employment_status" className="block text-sm font-medium text-gray-700 mb-1">
            Current Employment Status
          </label>
          <select
            id="employment_status"
            name="employment_status"
            value={formData.employment_status}
            onChange={(e) => setFormData({ ...formData, employment_status: e.target.value as WorkStatus })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {WORK_STATUS_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="work_in_france" className="block text-sm font-medium text-gray-700 mb-1">
            Work Plans in France
          </label>
          <select
            id="work_in_france"
            name="work_in_france"
            value={formData.work_in_france}
            onChange={(e) => setFormData({ ...formData, work_in_france: e.target.value as WorkInFranceType })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {WORK_IN_FRANCE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>
      </div>

      {isEmployed && (
        <fieldset className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="industry" className="block text-sm font-medium text-gray-700 mb-1">
              Industry/Field
            </label>
            <input
              type="text"
              id="industry"
              name="industry"
              value={formData.industry}
              onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., Technology, Healthcare, Finance"
              autoComplete="organization-title"
            />
          </div>

          <div>
            <label htmlFor="employer_name" className="block text-sm font-medium text-gray-700 mb-1">
              Current Employer
            </label>
            <input
              type="text"
              id="employer_name"
              name="employer_name"
              value={formData.employer_name}
              onChange={(e) => setFormData({ ...formData, employer_name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Company name"
              autoComplete="organization"
            />
          </div>
        </fieldset>
      )}

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
