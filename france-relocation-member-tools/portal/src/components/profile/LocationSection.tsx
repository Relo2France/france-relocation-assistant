/**
 * LocationSection Component
 *
 * Location information section of the profile form.
 * Handles current location, birth/marriage states, and target destination.
 */

import { useState, useEffect } from 'react';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import SaveButton from '@/components/shared/SaveButton';
import { US_STATES, APPLICATION_LOCATION_OPTIONS } from '@/config/profile';
import type { MemberProfile, ApplicationLocation } from '@/types';

interface LocationSectionProps {
  profile: MemberProfile | undefined;
}

export default function LocationSection({ profile }: LocationSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    current_country: '',
    current_state: '',
    current_city: '',
    birth_state: '',
    spouse_birth_state: '',
    marriage_state: '',
    marriage_country: '',
    target_location: '',
    application_location: 'us' as ApplicationLocation,
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        current_country: profile.current_country || '',
        current_state: profile.current_state || '',
        current_city: profile.current_city || '',
        birth_state: profile.birth_state || '',
        spouse_birth_state: profile.spouse_birth_state || '',
        marriage_state: profile.marriage_state || '',
        marriage_country: profile.marriage_country || '',
        target_location: profile.target_location || '',
        application_location: (profile.application_location as ApplicationLocation) || 'us',
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
      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Current Location</legend>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label htmlFor="current_country" className="block text-sm font-medium text-gray-700 mb-1">
              Current Country
            </label>
            <input
              type="text"
              id="current_country"
              name="current_country"
              value={formData.current_country}
              onChange={(e) => setFormData({ ...formData, current_country: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., United States"
              autoComplete="country-name"
            />
          </div>

          <div>
            <label htmlFor="current_state" className="block text-sm font-medium text-gray-700 mb-1">
              Current State
            </label>
            <select
              id="current_state"
              name="current_state"
              value={formData.current_state}
              onChange={(e) => setFormData({ ...formData, current_state: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Select a state</option>
              {US_STATES.map((state) => (
                <option key={state.value} value={state.value}>
                  {state.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label htmlFor="current_city" className="block text-sm font-medium text-gray-700 mb-1">
              Current City
            </label>
            <input
              type="text"
              id="current_city"
              name="current_city"
              value={formData.current_city}
              onChange={(e) => setFormData({ ...formData, current_city: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., New York"
              autoComplete="address-level2"
            />
          </div>
        </div>
      </fieldset>

      <fieldset className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <legend className="font-medium text-gray-900 px-2">Birth & Marriage Information</legend>
        <p className="text-sm text-gray-600 -mt-2">Required for apostille processing</p>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="birth_state" className="block text-sm font-medium text-gray-700 mb-1">
              Your Birth State (US)
            </label>
            <select
              id="birth_state"
              name="birth_state"
              value={formData.birth_state}
              onChange={(e) => setFormData({ ...formData, birth_state: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              aria-describedby="birth_state_hint"
            >
              <option value="">Select a state</option>
              {US_STATES.map((state) => (
                <option key={state.value} value={state.value}>
                  {state.label}
                </option>
              ))}
            </select>
            <p id="birth_state_hint" className="mt-1 text-sm text-gray-500">
              Used for birth certificate apostille routing
            </p>
          </div>

          <div>
            <label htmlFor="spouse_birth_state" className="block text-sm font-medium text-gray-700 mb-1">
              Spouse Birth State (US)
            </label>
            <select
              id="spouse_birth_state"
              name="spouse_birth_state"
              value={formData.spouse_birth_state}
              onChange={(e) => setFormData({ ...formData, spouse_birth_state: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Select a state</option>
              {US_STATES.map((state) => (
                <option key={state.value} value={state.value}>
                  {state.label}
                </option>
              ))}
            </select>
            <p className="mt-1 text-sm text-gray-500">If applicable</p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="marriage_state" className="block text-sm font-medium text-gray-700 mb-1">
              Marriage State (US)
            </label>
            <select
              id="marriage_state"
              name="marriage_state"
              value={formData.marriage_state}
              onChange={(e) => setFormData({ ...formData, marriage_state: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="">Select a state</option>
              {US_STATES.map((state) => (
                <option key={state.value} value={state.value}>
                  {state.label}
                </option>
              ))}
            </select>
            <p className="mt-1 text-sm text-gray-500">If married in the US</p>
          </div>

          <div>
            <label htmlFor="marriage_country" className="block text-sm font-medium text-gray-700 mb-1">
              Marriage Country
            </label>
            <input
              type="text"
              id="marriage_country"
              name="marriage_country"
              value={formData.marriage_country}
              onChange={(e) => setFormData({ ...formData, marriage_country: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="If married outside the US"
            />
          </div>
        </div>
      </fieldset>

      <div className="space-y-4">
        <div>
          <label htmlFor="target_location" className="block text-sm font-medium text-gray-700 mb-1">
            Target Location in France
          </label>
          <input
            type="text"
            id="target_location"
            name="target_location"
            value={formData.target_location}
            onChange={(e) => setFormData({ ...formData, target_location: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., Paris, Lyon, Provence"
            aria-describedby="target_location_hint"
          />
          <p id="target_location_hint" className="mt-1 text-sm text-gray-500">
            Where in France are you planning to relocate?
          </p>
        </div>

        <div>
          <label htmlFor="application_location" className="block text-sm font-medium text-gray-700 mb-1">
            Where will you apply for your visa?
          </label>
          <select
            id="application_location"
            name="application_location"
            value={formData.application_location}
            onChange={(e) => setFormData({ ...formData, application_location: e.target.value as ApplicationLocation })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-describedby="application_location_hint"
          >
            {APPLICATION_LOCATION_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <p id="application_location_hint" className="mt-1 text-sm text-gray-500">
            This affects the application process and requirements
          </p>
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
