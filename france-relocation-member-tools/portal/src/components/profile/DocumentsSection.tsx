/**
 * DocumentsSection Component
 *
 * Document status section of the profile form.
 * Tracks vital documents needed for visa applications.
 */

import { useEffect, useState } from 'react';
import { AlertCircle } from 'lucide-react';
import SaveButton from '@/components/shared/SaveButton';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import type {
  DocumentStatus,
  MarriageDocStatus,
  MemberProfile,
  YesNo,
} from '@/types';

interface DocumentsSectionProps {
  profile: MemberProfile | undefined;
}

const DOCUMENT_STATUS_OPTIONS = [
  { value: 'yes', label: 'Yes, I have it' },
  { value: 'no', label: 'No, need to order' },
  { value: 'unsure', label: "Not sure if it's certified" },
];

const YES_NO_OPTIONS = [
  { value: 'yes', label: 'Yes' },
  { value: 'no', label: 'No' },
];

const MARRIAGE_DOC_OPTIONS = [
  { value: 'yes', label: 'Yes, I have it' },
  { value: 'no', label: 'No, need to order' },
  { value: 'unsure', label: "Not sure if it's certified" },
  { value: 'na', label: 'Not applicable (not married)' },
];

export default function DocumentsSection({ profile }: DocumentsSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    has_birth_cert: 'no' as DocumentStatus,
    birth_cert_apostilled: 'no' as YesNo,
    has_marriage_cert: 'na' as MarriageDocStatus,
    marriage_cert_apostilled: 'no' as YesNo,
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        has_birth_cert: (profile.has_birth_cert as DocumentStatus) || 'no',
        birth_cert_apostilled: (profile.birth_cert_apostilled as YesNo) || 'no',
        has_marriage_cert: (profile.has_marriage_cert as MarriageDocStatus) || 'na',
        marriage_cert_apostilled: (profile.marriage_cert_apostilled as YesNo) || 'no',
      });
      setInitialized(true);
    }
  }, [profile, initialized]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  const showBirthApostille = formData.has_birth_cert === 'yes';
  const showMarriageApostille = formData.has_marriage_cert === 'yes';
  const hasSpouse = profile?.applicants === 'spouse' || profile?.applicants === 'spouse_kids';

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-6">
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4" role="note">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
          <div className="text-sm text-blue-800">
            <p className="font-medium">About Apostilles</p>
            <p className="mt-1">
              Most US documents need an apostille (authentication stamp) for use in France.
              The process varies by state and document type. Check our guides for state-specific instructions.
            </p>
          </div>
        </div>
      </div>

      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Birth Certificate</legend>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label htmlFor="has_birth_cert" className="block text-sm font-medium text-gray-700 mb-1">
              Have certified birth certificate?
            </label>
            <select
              id="has_birth_cert"
              name="has_birth_cert"
              value={formData.has_birth_cert}
              onChange={(e) => setFormData({ ...formData, has_birth_cert: e.target.value as DocumentStatus })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              {DOCUMENT_STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
            <p className="mt-1 text-sm text-gray-500">
              You need a certified copy (not a photocopy)
            </p>
          </div>

          {showBirthApostille && (
            <div>
              <label htmlFor="birth_cert_apostilled" className="block text-sm font-medium text-gray-700 mb-1">
                Is it apostilled?
              </label>
              <select
                id="birth_cert_apostilled"
                name="birth_cert_apostilled"
                value={formData.birth_cert_apostilled}
                onChange={(e) => setFormData({ ...formData, birth_cert_apostilled: e.target.value as YesNo })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                {YES_NO_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <p className="mt-1 text-sm text-gray-500">
                Apostille must be from your birth state
              </p>
            </div>
          )}
        </div>
      </fieldset>

      {hasSpouse && (
        <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <legend className="font-medium text-gray-900 px-2">Marriage Certificate</legend>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label htmlFor="has_marriage_cert" className="block text-sm font-medium text-gray-700 mb-1">
                Have certified marriage certificate?
              </label>
              <select
                id="has_marriage_cert"
                name="has_marriage_cert"
                value={formData.has_marriage_cert}
                onChange={(e) => setFormData({ ...formData, has_marriage_cert: e.target.value as MarriageDocStatus })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                {MARRIAGE_DOC_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            {showMarriageApostille && (
              <div>
                <label htmlFor="marriage_cert_apostilled" className="block text-sm font-medium text-gray-700 mb-1">
                  Is it apostilled?
                </label>
                <select
                  id="marriage_cert_apostilled"
                  name="marriage_cert_apostilled"
                  value={formData.marriage_cert_apostilled}
                  onChange={(e) => setFormData({ ...formData, marriage_cert_apostilled: e.target.value as YesNo })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  {YES_NO_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-sm text-gray-500">
                  Apostille must be from your marriage state
                </p>
              </div>
            )}
          </div>
        </fieldset>
      )}

      {!hasSpouse && (
        <p className="text-sm text-gray-500 italic">
          Marriage certificate section will appear when you indicate a spouse in the Applicant & Family section.
        </p>
      )}

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
