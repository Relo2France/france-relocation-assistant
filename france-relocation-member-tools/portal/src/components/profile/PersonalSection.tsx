/**
 * PersonalSection Component
 *
 * Personal information section of the profile form.
 */

import { useState, useEffect, useRef } from 'react';
import { AlertCircle } from 'lucide-react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import type { MemberProfile } from '@/types';

interface PersonalSectionProps {
  profile: MemberProfile | undefined;
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

export default function PersonalSection({ profile }: PersonalSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const initializedRef = useRef(false);
  const [formData, setFormData] = useState(initialFormData);

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

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500";
  const labelClass = "block text-sm font-medium text-gray-700 mb-1";

  return (
    <form onSubmit={handleSubmit} className="pt-6">
      {/* Notice */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6" role="note">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <div className="text-sm text-blue-800">
            <p className="font-medium">Important: Legal Names Required</p>
            <p className="mt-1">Use your passport/legal names exactly as they appear on official documents.</p>
          </div>
        </div>
      </div>

      {/* All fields in a simple 2-column grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <label htmlFor="legal_first_name" className={labelClass}>
            First Name <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="legal_first_name"
            name="legal_first_name"
            value={formData.legal_first_name}
            onChange={handleChange}
            className={inputClass}
            required
            autoComplete="given-name"
          />
        </div>

        <div>
          <label htmlFor="legal_last_name" className={labelClass}>
            Last Name <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="legal_last_name"
            name="legal_last_name"
            value={formData.legal_last_name}
            onChange={handleChange}
            className={inputClass}
            required
            autoComplete="family-name"
          />
        </div>

        <div>
          <label htmlFor="legal_middle_name" className={labelClass}>
            Middle Name
          </label>
          <input
            type="text"
            id="legal_middle_name"
            name="legal_middle_name"
            value={formData.legal_middle_name}
            onChange={handleChange}
            className={inputClass}
            autoComplete="additional-name"
          />
        </div>

        <div>
          <label htmlFor="date_of_birth" className={labelClass}>
            Date of Birth <span className="text-red-500">*</span>
          </label>
          <input
            type="date"
            id="date_of_birth"
            name="date_of_birth"
            value={formData.date_of_birth}
            onChange={handleChange}
            className={inputClass}
            required
            autoComplete="bday"
          />
        </div>

        <div>
          <label htmlFor="nationality" className={labelClass}>
            Nationality <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="nationality"
            name="nationality"
            value={formData.nationality}
            onChange={handleChange}
            className={inputClass}
            placeholder="e.g., American"
            required
          />
        </div>

        <div>
          <label htmlFor="passport_number" className={labelClass}>
            Passport Number
          </label>
          <input
            type="text"
            id="passport_number"
            name="passport_number"
            value={formData.passport_number}
            onChange={handleChange}
            className={inputClass}
            autoComplete="off"
          />
        </div>

        <div>
          <label htmlFor="passport_expiry" className={labelClass}>
            Passport Expiry
          </label>
          <input
            type="date"
            id="passport_expiry"
            name="passport_expiry"
            value={formData.passport_expiry}
            onChange={handleChange}
            className={inputClass}
          />
        </div>
      </div>

      <div className="mt-6">
        <SaveButton
          isPending={updateProfile.isPending}
          isSuccess={updateProfile.isSuccess}
          isError={updateProfile.isError}
        />
      </div>
    </form>
  );
}
