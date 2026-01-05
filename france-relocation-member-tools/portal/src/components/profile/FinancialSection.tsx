/**
 * FinancialSection Component
 *
 * Financial and language information section of the profile form.
 * Handles financial resources, income sources, and French language proficiency.
 */

import { useEffect, useState } from 'react';
import { AlertCircle } from 'lucide-react';
import SaveButton from '@/components/shared/SaveButton';
import { FINANCIAL_RANGES } from '@/config/profile';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import type {
  FinancialRange,
  FrenchProficiency,
  MemberProfile,
  YesNoMaybe,
} from '@/types';

interface FinancialSectionProps {
  profile: MemberProfile | undefined;
}

const FRENCH_PROFICIENCY_OPTIONS = [
  { value: 'none', label: 'None' },
  { value: 'basic', label: 'Basic (A1-A2)' },
  { value: 'conversational', label: 'Conversational (B1-B2)' },
  { value: 'fluent', label: 'Fluent (C1-C2)' },
  { value: 'native', label: 'Native' },
];

const YES_NO_MAYBE_OPTIONS = [
  { value: 'no', label: 'No' },
  { value: 'yes', label: 'Yes' },
  { value: 'maybe', label: 'Maybe/Undecided' },
];

export default function FinancialSection({ profile }: FinancialSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    financial_resources: 'under_50k' as FinancialRange,
    income_sources: '',
    french_proficiency: 'none' as FrenchProficiency,
    french_mortgage: 'no' as YesNoMaybe,
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        financial_resources: (profile.financial_resources as FinancialRange) || 'under_50k',
        income_sources: profile.income_sources || '',
        french_proficiency: (profile.french_proficiency as FrenchProficiency) || 'none',
        french_mortgage: (profile.french_mortgage as YesNoMaybe) || 'no',
      });
      setInitialized(true);
    }
  }, [profile, initialized]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-8 pt-6">
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4" role="note">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <div className="text-sm text-blue-800">
            <p className="font-medium">Financial Information</p>
            <p className="mt-1">
              This helps us provide accurate guidance on visa requirements and financial proof needed.
              Your information is confidential.
            </p>
          </div>
        </div>
      </div>

      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Financial Resources</legend>

        <div>
          <label htmlFor="financial_resources" className="block text-sm font-medium text-gray-700 mb-1">
            Available Financial Resources
          </label>
          <select
            id="financial_resources"
            name="financial_resources"
            value={formData.financial_resources}
            onChange={(e) => setFormData({ ...formData, financial_resources: e.target.value as FinancialRange })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-describedby="financial_resources_hint"
          >
            {FINANCIAL_RANGES.map((range) => (
              <option key={range.value} value={range.value}>
                {range.label}
              </option>
            ))}
          </select>
          <p id="financial_resources_hint" className="mt-1 text-sm text-gray-500">
            Combined savings, investments, and liquid assets available for relocation
          </p>
        </div>

        <div>
          <label htmlFor="income_sources" className="block text-sm font-medium text-gray-700 mb-1">
            Income Sources
          </label>
          <textarea
            id="income_sources"
            name="income_sources"
            value={formData.income_sources}
            onChange={(e) => setFormData({ ...formData, income_sources: e.target.value })}
            rows={3}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., Employment, investments, rental income, pension, retirement accounts, etc."
            aria-describedby="income_sources_hint"
          />
          <p id="income_sources_hint" className="mt-1 text-sm text-gray-500">
            Describe your current and expected income sources to support your stay in France
          </p>
        </div>

        <div>
          <label htmlFor="french_mortgage" className="block text-sm font-medium text-gray-700 mb-1">
            Planning to get a French mortgage?
          </label>
          <select
            id="french_mortgage"
            name="french_mortgage"
            value={formData.french_mortgage}
            onChange={(e) => setFormData({ ...formData, french_mortgage: e.target.value as YesNoMaybe })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {YES_NO_MAYBE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <p className="mt-1 text-sm text-gray-500">
            French mortgages are available to non-residents and often have favorable rates
          </p>
        </div>
      </fieldset>

      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Language</legend>

        <div>
          <label htmlFor="french_proficiency" className="block text-sm font-medium text-gray-700 mb-1">
            French Language Proficiency
          </label>
          <select
            id="french_proficiency"
            name="french_proficiency"
            value={formData.french_proficiency}
            onChange={(e) => setFormData({ ...formData, french_proficiency: e.target.value as FrenchProficiency })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {FRENCH_PROFICIENCY_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <p className="mt-1 text-sm text-gray-500">
            Your current French language level (not required for most visa types)
          </p>
        </div>
      </fieldset>

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
