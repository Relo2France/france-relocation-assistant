/**
 * TimelineSection Component
 *
 * Timeline and planning section of the profile form.
 * Handles move timeline, target dates, and housing plans.
 */

import { useEffect, useState } from 'react';
import SaveButton from '@/components/shared/SaveButton';
import { useUpdateMemberProfile } from '@/hooks/useApi';
import type {
  HousingPlan,
  MemberProfile,
  MoveDateCertainty,
  TimelineType,
} from '@/types';

interface TimelineSectionProps {
  profile: MemberProfile | undefined;
}

const TIMELINE_OPTIONS = [
  { value: 'asap', label: 'As soon as possible' },
  { value: '3_months', label: 'Within 3 months' },
  { value: '6_months', label: '3-6 months' },
  { value: '12_months', label: '6-12 months' },
  { value: 'over_12', label: 'More than 12 months' },
  { value: 'undecided', label: 'Undecided' },
];

const MOVE_DATE_CERTAINTY_OPTIONS = [
  { value: 'fixed', label: 'Fixed - I have tickets/lease starting this date' },
  { value: 'anticipated', label: 'Anticipated - Planning for this date but may adjust' },
  { value: 'flexible', label: 'Flexible - No firm date yet' },
];

const HOUSING_PLAN_OPTIONS = [
  { value: 'buying', label: 'Buying property' },
  { value: 'renting', label: 'Renting' },
  { value: 'undecided', label: 'Undecided' },
  { value: 'already_own', label: 'Already own property in France' },
];

export default function TimelineSection({ profile }: TimelineSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    timeline: 'undecided' as TimelineType,
    target_move_date: '',
    move_date_certainty: 'flexible' as MoveDateCertainty,
    housing_plan: 'undecided' as HousingPlan,
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        timeline: (profile.timeline as TimelineType) || 'undecided',
        target_move_date: profile.target_move_date || '',
        move_date_certainty: (profile.move_date_certainty as MoveDateCertainty) || 'flexible',
        housing_plan: (profile.housing_plan as HousingPlan) || 'undecided',
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
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="timeline" className="block text-sm font-medium text-gray-700 mb-1">
            Move Timeline
          </label>
          <select
            id="timeline"
            name="timeline"
            value={formData.timeline}
            onChange={(e) => setFormData({ ...formData, timeline: e.target.value as TimelineType })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {TIMELINE_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <p className="mt-1 text-sm text-gray-500">
            When are you planning to move to France?
          </p>
        </div>

        <div>
          <label htmlFor="target_move_date" className="block text-sm font-medium text-gray-700 mb-1">
            Target Move Date
          </label>
          <input
            type="date"
            id="target_move_date"
            name="target_move_date"
            value={formData.target_move_date}
            onChange={(e) => setFormData({ ...formData, target_move_date: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
          <p className="mt-1 text-sm text-gray-500">
            Your planned arrival or move date
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="move_date_certainty" className="block text-sm font-medium text-gray-700 mb-1">
            How certain is this date?
          </label>
          <select
            id="move_date_certainty"
            name="move_date_certainty"
            value={formData.move_date_certainty}
            onChange={(e) => setFormData({ ...formData, move_date_certainty: e.target.value as MoveDateCertainty })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {MOVE_DATE_CERTAINTY_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="housing_plan" className="block text-sm font-medium text-gray-700 mb-1">
            Housing Plans
          </label>
          <select
            id="housing_plan"
            name="housing_plan"
            value={formData.housing_plan}
            onChange={(e) => setFormData({ ...formData, housing_plan: e.target.value as HousingPlan })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {HOUSING_PLAN_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <p className="mt-1 text-sm text-gray-500">
            Are you planning to buy or rent in France?
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
