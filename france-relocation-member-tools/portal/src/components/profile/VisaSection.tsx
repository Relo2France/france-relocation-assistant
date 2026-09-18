/**
 * VisaSection Component
 *
 * Visa and employment information section of the profile form.
 * Handles visa type, employment status, and work plans.
 */

import { useEffect, useState } from 'react';
import SaveButton from '@/components/shared/SaveButton';
import { RELATIONSHIP_TYPE_OPTIONS, STUDY_LENGTH_OPTIONS, TALENT_CATEGORY_OPTIONS, VISA_TYPES, WORK_IN_FRANCE_OPTIONS, WORK_STATUS_OPTIONS } from '@/config/profile';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type {
  MemberProfile,
  ProfileVisaType,
  WorkInFranceType,
  WorkStatus,
  RelationshipType,
  StudyLength,
  TalentCategory,
} from '@/types';

interface VisaSectionProps {
  profile: MemberProfile | undefined;
}

export default function VisaSection({ profile }: VisaSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const { setActiveStage, setActiveView } = usePortalStore();
  const [formData, setFormData] = useState({
    visa_type: 'undecided' as ProfileVisaType,
    talent_category: '' as TalentCategory,
    relationship_type: '' as RelationshipType,
    study_length: '' as StudyLength,
    employment_status: 'not_working' as WorkStatus,
    work_in_france: 'undecided' as WorkInFranceType,
    industry: '',
    employer_name: '',
    job_title: '',
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        visa_type: (profile.visa_type as ProfileVisaType) || 'undecided',
        talent_category: (profile.talent_category as TalentCategory) || '',
        relationship_type: (profile.relationship_type as RelationshipType) || '',
        study_length: (profile.study_length as StudyLength) || '',
        employment_status: (profile.employment_status as WorkStatus) || 'not_working',
        work_in_france: (profile.work_in_france as WorkInFranceType) || 'undecided',
        industry: profile.industry || '',
        employer_name: profile.employer_name || '',
        job_title: profile.job_title || '',
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
        >
          {VISA_TYPES.map((type) => (
            <option key={type.value} value={type.value}>
              {type.label} - {type.description}
            </option>
          ))}
        </select>
        <div className="mt-2 flex flex-wrap items-center justify-between gap-2">
          <p className="text-sm text-gray-500 m-0">
            Select the visa type that best matches your situation.
          </p>
          <button
            type="button"
            onClick={() => { setActiveStage('decide'); setActiveView('stage'); }}
            className="btn btn-secondary py-1.5 text-sm"
          >
            Not sure? Discover the visa types
          </button>
        </div>
      </div>

      {formData.visa_type === 'talent_passport' ? (
        <div>
          <label htmlFor="talent_category" className="block text-sm font-medium text-gray-700 mb-1">Talent category</label>
          <select id="talent_category" name="talent_category" value={formData.talent_category} onChange={(e) => setFormData({ ...formData, talent_category: e.target.value as TalentCategory })} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">Choose one</option>
            {TALENT_CATEGORY_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
          <p className="mt-1 text-sm text-gray-500">The category decides the proof the consulate wants. Choosing it adds that step to your plan.</p>
        </div>
      ) : null}
      {formData.visa_type === 'spouse_french' ? (
        <div>
          <label htmlFor="relationship_type" className="block text-sm font-medium text-gray-700 mb-1">Married or PACS</label>
          <select id="relationship_type" name="relationship_type" value={formData.relationship_type} onChange={(e) => setFormData({ ...formData, relationship_type: e.target.value as RelationshipType })} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">Choose one</option>
            {RELATIONSHIP_TYPE_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
          <p className="mt-1 text-sm text-gray-500">A PACS needs twelve months of documented shared life first; choosing it adds that step.</p>
        </div>
      ) : null}
      {formData.visa_type === 'student' ? (
        <div>
          <label htmlFor="study_length" className="block text-sm font-medium text-gray-700 mb-1">Length of studies</label>
          <select id="study_length" name="study_length" value={formData.study_length} onChange={(e) => setFormData({ ...formData, study_length: e.target.value as StudyLength })} className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">Choose one</option>
            {STUDY_LENGTH_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
          <p className="mt-1 text-sm text-gray-500">A programme longer than a year means yearly renewals; choosing it adds that step.</p>
        </div>
      ) : null}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
          />
        </div>

        <div>
          <label htmlFor="job_title" className="block text-sm font-medium text-gray-700 mb-1">
            Job Title
          </label>
          <input
            type="text"
            id="job_title"
            name="job_title"
            value={formData.job_title}
            onChange={(e) => setFormData({ ...formData, job_title: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., Software Engineer, Marketing Director"
          />
        </div>
      </div>

      {isEmployed && (
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
          />
        </div>
      )}

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
