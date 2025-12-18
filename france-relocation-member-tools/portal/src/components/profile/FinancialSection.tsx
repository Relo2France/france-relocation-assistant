/**
 * FinancialSection Component
 *
 * Financial information section of the profile form.
 * Handles financial resources and income sources.
 */

import { useState, useEffect } from 'react';
import { AlertCircle } from 'lucide-react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import { FINANCIAL_RANGES } from '@/config/profile';
import type { MemberProfile, FinancialRange } from '@/types';

interface FinancialSectionProps {
  profile: MemberProfile | undefined;
}

export default function FinancialSection({ profile }: FinancialSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    financial_resources: 'under_50k' as FinancialRange,
    income_sources: '',
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        financial_resources: (profile.financial_resources as FinancialRange) || 'under_50k',
        income_sources: profile.income_sources || '',
      });
      setInitialized(true);
    }
  }, [profile, initialized]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-6">
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
          rows={4}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="e.g., Employment, investments, rental income, pension, retirement accounts, etc."
          aria-describedby="income_sources_hint"
        />
        <p id="income_sources_hint" className="mt-1 text-sm text-gray-500">
          Describe your current and expected income sources to support your stay in France
        </p>
      </div>

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
