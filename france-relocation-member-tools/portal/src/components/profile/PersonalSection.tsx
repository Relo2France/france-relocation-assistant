/**
 * PersonalSection Component
 *
 * Personal information section of the profile form.
 * Handles legal names, DOB, nationality, and passport information.
 */

import { useState, useEffect } from 'react';
import { AlertCircle } from 'lucide-react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import type { MemberProfile } from '@/types';

interface PersonalSectionProps {
  profile: MemberProfile | undefined;
}

export default function PersonalSection({ profile }: PersonalSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    legal_first_name: '',
    legal_middle_name: '',
    legal_last_name: '',
    date_of_birth: '',
    nationality: '',
    passport_number: '',
    passport_expiry: '',
  });
  const [initialized, setInitialized] = useState(false);

  // Initialize form when profile data loads
  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        legal_first_name: profile.legal_first_name || '',
        legal_middle_name: profile.legal_middle_name || '',
        legal_last_name: profile.legal_last_name || '',
        date_of_birth: profile.date_of_birth || '',
        nationality: profile.nationality || '',
        passport_number: profile.passport_number || '',
        passport_expiry: profile.passport_expiry || '',
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
            <p className="font-medium">Important: Legal Names Required</p>
            <p className="mt-1">Use your passport/legal names exactly as they appear on official documents.</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label htmlFor="legal_first_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal First Name <span className="text-red-500" aria-label="required">*</span>
          </label>
          <input
            type="text"
            id="legal_first_name"
            name="legal_first_name"
            value={formData.legal_first_name}
            onChange={(e) => setFormData({ ...formData, legal_first_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
            aria-required="true"
            autoComplete="given-name"
          />
        </div>
        <div>
          <label htmlFor="legal_middle_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal Middle Name
          </label>
          <input
            type="text"
            id="legal_middle_name"
            name="legal_middle_name"
            value={formData.legal_middle_name}
            onChange={(e) => setFormData({ ...formData, legal_middle_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            autoComplete="additional-name"
          />
        </div>
        <div>
          <label htmlFor="legal_last_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal Last Name <span className="text-red-500" aria-label="required">*</span>
          </label>
          <input
            type="text"
            id="legal_last_name"
            name="legal_last_name"
            value={formData.legal_last_name}
            onChange={(e) => setFormData({ ...formData, legal_last_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
            aria-required="true"
            autoComplete="family-name"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700 mb-1">
            Date of Birth <span className="text-red-500" aria-label="required">*</span>
          </label>
          <input
            type="date"
            id="date_of_birth"
            name="date_of_birth"
            value={formData.date_of_birth}
            onChange={(e) => setFormData({ ...formData, date_of_birth: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
            aria-required="true"
            autoComplete="bday"
          />
        </div>
        <div>
          <label htmlFor="nationality" className="block text-sm font-medium text-gray-700 mb-1">
            Nationality <span className="text-red-500" aria-label="required">*</span>
          </label>
          <input
            type="text"
            id="nationality"
            name="nationality"
            value={formData.nationality}
            onChange={(e) => setFormData({ ...formData, nationality: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., American, Canadian"
            required
            aria-required="true"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="passport_number" className="block text-sm font-medium text-gray-700 mb-1">
            Passport Number
          </label>
          <input
            type="text"
            id="passport_number"
            name="passport_number"
            value={formData.passport_number}
            onChange={(e) => setFormData({ ...formData, passport_number: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            autoComplete="off"
          />
        </div>
        <div>
          <label htmlFor="passport_expiry" className="block text-sm font-medium text-gray-700 mb-1">
            Passport Expiry Date
          </label>
          <input
            type="date"
            id="passport_expiry"
            name="passport_expiry"
            value={formData.passport_expiry}
            onChange={(e) => setFormData({ ...formData, passport_expiry: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
