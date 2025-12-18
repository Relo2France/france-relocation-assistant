/**
 * ApplicantSection Component
 *
 * Applicant and family information section of the profile form.
 * Handles spouse details, children info, and pet information.
 */

import { useState, useEffect } from 'react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import {
  APPLICANT_TYPE_OPTIONS,
  WORK_STATUS_OPTIONS,
  PET_TYPE_OPTIONS,
} from '@/config/profile';
import type {
  MemberProfile,
  ApplicantType,
  WorkStatus,
  PetType,
} from '@/types';

interface ApplicantSectionProps {
  profile: MemberProfile | undefined;
}

export default function ApplicantSection({ profile }: ApplicantSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    applicants: 'alone' as ApplicantType,
    spouse_legal_first_name: '',
    spouse_legal_last_name: '',
    spouse_date_of_birth: '',
    spouse_name: '',
    spouse_work_status: 'not_working' as WorkStatus,
    num_children: 0,
    children_ages: '',
    has_pets: 'no' as PetType,
    pet_details: '',
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        applicants: (profile.applicants as ApplicantType) || 'alone',
        spouse_legal_first_name: profile.spouse_legal_first_name || '',
        spouse_legal_last_name: profile.spouse_legal_last_name || '',
        spouse_date_of_birth: profile.spouse_date_of_birth || '',
        spouse_name: profile.spouse_name || '',
        spouse_work_status: (profile.spouse_work_status as WorkStatus) || 'not_working',
        num_children: profile.num_children || 0,
        children_ages: profile.children_ages || '',
        has_pets: (profile.has_pets as PetType) || 'no',
        pet_details: profile.pet_details || '',
      });
      setInitialized(true);
    }
  }, [profile, initialized]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  const hasSpouse = formData.applicants === 'spouse' || formData.applicants === 'spouse_kids';
  const hasChildren = formData.applicants === 'spouse_kids' || formData.applicants === 'kids_only';
  const hasPets = formData.has_pets !== 'no';

  return (
    <form onSubmit={handleSubmit} className="space-y-6 pt-6">
      <div>
        <label htmlFor="applicants" className="block text-sm font-medium text-gray-700 mb-1">
          Who will be relocating? <span className="text-red-500" aria-label="required">*</span>
        </label>
        <select
          id="applicants"
          name="applicants"
          value={formData.applicants}
          onChange={(e) => setFormData({ ...formData, applicants: e.target.value as ApplicantType })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          required
          aria-required="true"
        >
          {APPLICANT_TYPE_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>

      {/* Spouse fields */}
      {hasSpouse && (
        <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <legend className="font-medium text-gray-900 px-2">Spouse/Partner Information</legend>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="spouse_legal_first_name" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Legal First Name
              </label>
              <input
                type="text"
                id="spouse_legal_first_name"
                name="spouse_legal_first_name"
                value={formData.spouse_legal_first_name}
                onChange={(e) => setFormData({ ...formData, spouse_legal_first_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                autoComplete="off"
              />
            </div>
            <div>
              <label htmlFor="spouse_legal_last_name" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Legal Last Name
              </label>
              <input
                type="text"
                id="spouse_legal_last_name"
                name="spouse_legal_last_name"
                value={formData.spouse_legal_last_name}
                onChange={(e) => setFormData({ ...formData, spouse_legal_last_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                autoComplete="off"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="spouse_date_of_birth" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Date of Birth
              </label>
              <input
                type="date"
                id="spouse_date_of_birth"
                name="spouse_date_of_birth"
                value={formData.spouse_date_of_birth}
                onChange={(e) => setFormData({ ...formData, spouse_date_of_birth: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div>
              <label htmlFor="spouse_name" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Preferred Name
              </label>
              <input
                type="text"
                id="spouse_name"
                name="spouse_name"
                value={formData.spouse_name}
                onChange={(e) => setFormData({ ...formData, spouse_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="If different from legal name"
                autoComplete="off"
              />
            </div>
          </div>

          <div>
            <label htmlFor="spouse_work_status" className="block text-sm font-medium text-gray-700 mb-1">
              Spouse Work Status
            </label>
            <select
              id="spouse_work_status"
              name="spouse_work_status"
              value={formData.spouse_work_status}
              onChange={(e) => setFormData({ ...formData, spouse_work_status: e.target.value as WorkStatus })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              {WORK_STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        </fieldset>
      )}

      {/* Children fields */}
      {hasChildren && (
        <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <legend className="font-medium text-gray-900 px-2">Children Information</legend>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="num_children" className="block text-sm font-medium text-gray-700 mb-1">
                Number of Children
              </label>
              <input
                type="number"
                id="num_children"
                name="num_children"
                min="0"
                max="20"
                value={formData.num_children}
                onChange={(e) => setFormData({ ...formData, num_children: parseInt(e.target.value) || 0 })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div>
              <label htmlFor="children_ages" className="block text-sm font-medium text-gray-700 mb-1">
                Children Ages
              </label>
              <input
                type="text"
                id="children_ages"
                name="children_ages"
                value={formData.children_ages}
                onChange={(e) => setFormData({ ...formData, children_ages: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="e.g., 5, 8, 12"
                aria-describedby="children_ages_hint"
              />
              <p id="children_ages_hint" className="mt-1 text-sm text-gray-500">
                Separate ages with commas
              </p>
            </div>
          </div>
        </fieldset>
      )}

      {/* Pets */}
      <fieldset className="space-y-4">
        <div>
          <label htmlFor="has_pets" className="block text-sm font-medium text-gray-700 mb-1">
            Do you have pets?
          </label>
          <select
            id="has_pets"
            name="has_pets"
            value={formData.has_pets}
            onChange={(e) => setFormData({ ...formData, has_pets: e.target.value as PetType })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {PET_TYPE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        {hasPets && (
          <div>
            <label htmlFor="pet_details" className="block text-sm font-medium text-gray-700 mb-1">
              Pet Details
            </label>
            <textarea
              id="pet_details"
              name="pet_details"
              value={formData.pet_details}
              onChange={(e) => setFormData({ ...formData, pet_details: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Breed, size, age, etc."
              aria-describedby="pet_details_hint"
            />
            <p id="pet_details_hint" className="mt-1 text-sm text-gray-500">
              This information helps with pet relocation planning
            </p>
          </div>
        )}
      </fieldset>

      <SaveButton
        isPending={updateProfile.isPending}
        isSuccess={updateProfile.isSuccess}
        isError={updateProfile.isError}
      />
    </form>
  );
}
