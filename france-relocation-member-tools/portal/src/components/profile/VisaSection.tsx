/**
 * VisaSection Component
 *
 * Comprehensive visa and relocation information section of the profile form.
 * Handles visa type, employment status, timeline, financial readiness, and document status.
 */

import { useState, useEffect, useRef } from 'react';
import { clsx } from 'clsx';
import { ChevronDown, ChevronUp, Save, Check, Loader2 } from 'lucide-react';
import { useUpdateMemberProfile, useProfileCompletion } from '@/hooks/useApi';
import {
  VISA_TYPES,
  WORK_STATUS_OPTIONS,
  WORK_IN_FRANCE_OPTIONS,
  US_STATES,
  APPLICATION_LOCATION_OPTIONS,
} from '@/config/profile';
import type {
  MemberProfile,
  ProfileVisaType,
  WorkStatus,
  WorkInFranceType,
  TimelineType,
  MoveDateCertainty,
  ApplicationLocation,
  FrenchProficiency,
  YesNoMaybe,
  DocumentStatus,
  MarriageDocStatus,
  YesNo,
  HousingPlan,
} from '@/types';

interface VisaSectionProps {
  profile: MemberProfile | undefined;
}

// Timeline options
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
  { value: 'already_own', label: 'Already own property' },
];

const FRENCH_PROFICIENCY_OPTIONS = [
  { value: 'none', label: 'None' },
  { value: 'basic', label: 'Basic' },
  { value: 'conversational', label: 'Conversational' },
  { value: 'fluent', label: 'Fluent' },
  { value: 'native', label: 'Native' },
];

const YES_NO_MAYBE_OPTIONS = [
  { value: 'no', label: 'No' },
  { value: 'yes', label: 'Yes' },
  { value: 'maybe', label: 'Maybe/Undecided' },
];

const DOCUMENT_STATUS_OPTIONS = [
  { value: 'yes', label: 'Yes' },
  { value: 'no', label: 'No, need to order' },
  { value: 'unsure', label: "Not sure if it's certified" },
];

const YES_NO_OPTIONS = [
  { value: 'yes', label: 'Yes' },
  { value: 'no', label: 'No' },
];

const MARRIAGE_DOC_OPTIONS = [
  { value: 'yes', label: 'Yes' },
  { value: 'no', label: 'No, need to order' },
  { value: 'unsure', label: "Not sure if it's certified" },
  { value: 'na', label: 'Not applicable' },
];

interface FormData {
  // Visa & Employment
  visa_type: ProfileVisaType;
  employment_status: WorkStatus;
  work_in_france: WorkInFranceType;
  industry: string;
  employer_name: string;
  job_title: string;
  // Location
  target_location: string;
  housing_plan: HousingPlan;
  birth_state: string;
  birth_state_other: string;
  // Timeline
  timeline: TimelineType;
  target_move_date: string;
  move_date_certainty: MoveDateCertainty;
  application_location: ApplicationLocation;
  // Financial & Language
  french_proficiency: FrenchProficiency;
  french_mortgage: YesNoMaybe;
  // Documents
  has_birth_cert: DocumentStatus;
  birth_cert_apostilled: YesNo;
  has_marriage_cert: MarriageDocStatus;
  marriage_cert_apostilled: YesNo;
}

export default function VisaSection({ profile }: VisaSectionProps) {
  const updateProfile = useUpdateMemberProfile();
  const { data: completion, refetch: refetchCompletion } = useProfileCompletion();
  const initializedRef = useRef(false);

  const [expandedSections, setExpandedSections] = useState({
    visa: true,
    timeline: true,
    financial: true,
    documents: true,
  });

  const [formData, setFormData] = useState<FormData>({
    // Visa & Employment
    visa_type: 'undecided',
    employment_status: 'not_working',
    work_in_france: 'undecided',
    industry: '',
    employer_name: '',
    job_title: '',
    // Location
    target_location: '',
    housing_plan: 'undecided',
    birth_state: '',
    birth_state_other: '',
    // Timeline
    timeline: 'undecided',
    target_move_date: '',
    move_date_certainty: 'flexible',
    application_location: 'us',
    // Financial & Language
    french_proficiency: 'none',
    french_mortgage: 'no',
    // Documents
    has_birth_cert: 'no',
    birth_cert_apostilled: 'no',
    has_marriage_cert: 'na',
    marriage_cert_apostilled: 'no',
  });

  // Sync form with profile data when first loaded
  useEffect(() => {
    if (profile && !initializedRef.current) {
      setFormData({
        // Visa & Employment
        visa_type: (profile.visa_type as ProfileVisaType) || 'undecided',
        employment_status: (profile.employment_status as WorkStatus) || 'not_working',
        work_in_france: (profile.work_in_france as WorkInFranceType) || 'undecided',
        industry: profile.industry || '',
        employer_name: profile.employer_name || '',
        job_title: profile.job_title || '',
        // Location
        target_location: profile.target_location || '',
        housing_plan: (profile.housing_plan as HousingPlan) || 'undecided',
        birth_state: profile.birth_state || '',
        birth_state_other: profile.birth_state_other || '',
        // Timeline
        timeline: (profile.timeline as TimelineType) || 'undecided',
        target_move_date: profile.target_move_date || '',
        move_date_certainty: (profile.move_date_certainty as MoveDateCertainty) || 'flexible',
        application_location: (profile.application_location as ApplicationLocation) || 'us',
        // Financial & Language
        french_proficiency: (profile.french_proficiency as FrenchProficiency) || 'none',
        french_mortgage: (profile.french_mortgage as YesNoMaybe) || 'no',
        // Documents
        has_birth_cert: (profile.has_birth_cert as DocumentStatus) || 'no',
        birth_cert_apostilled: (profile.birth_cert_apostilled as YesNo) || 'no',
        has_marriage_cert: (profile.has_marriage_cert as MarriageDocStatus) || 'na',
        marriage_cert_apostilled: (profile.marriage_cert_apostilled as YesNo) || 'no',
      });
      initializedRef.current = true;
    }
  }, [profile]);

  const toggleSection = (section: keyof typeof expandedSections) => {
    setExpandedSections(prev => ({ ...prev, [section]: !prev[section] }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await updateProfile.mutateAsync(formData);
    // Refetch completion percentage after save
    refetchCompletion();
  };

  const isEmployed = formData.employment_status === 'employed' || formData.employment_status === 'self_employed';
  const showBirthStateOther = formData.birth_state === 'other';
  const showMarriageCert = profile?.applicants === 'spouse' || profile?.applicants === 'spouse_kids';
  const showApostilleQuestion = formData.has_birth_cert === 'yes';
  const showMarriageApostille = formData.has_marriage_cert === 'yes';

  const completionPercentage = completion?.percentage || profile?.profile_completion || 0;

  return (
    <div className="space-y-6 pt-6">
      {/* Profile Completion Progress Bar */}
      <div className="bg-gray-50 rounded-lg p-4">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-medium text-gray-700">Profile Completion</span>
          <span className="text-sm font-semibold text-primary-600">{completionPercentage}%</span>
        </div>
        <div
          className="w-full bg-gray-200 rounded-full h-2"
          role="progressbar"
          aria-valuenow={completionPercentage}
          aria-valuemin={0}
          aria-valuemax={100}
        >
          <div
            className={clsx(
              'h-2 rounded-full transition-all duration-500',
              completionPercentage >= 80 ? 'bg-green-500' :
              completionPercentage >= 50 ? 'bg-primary-500' :
              'bg-yellow-500'
            )}
            style={{ width: `${completionPercentage}%` }}
          />
        </div>
        <p className="text-xs text-gray-500 mt-2">
          Complete your profile to enable document generation and personalized recommendations.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Visa & Employment Section */}
        <CollapsibleSection
          title="Visa & Employment"
          isOpen={expandedSections.visa}
          onToggle={() => toggleSection('visa')}
        >
          <div className="space-y-6">
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
              <p className="mt-1 text-sm text-gray-500">
                Select the visa type that best matches your situation.
              </p>
            </div>

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
          </div>
        </CollapsibleSection>

        {/* Timeline Section */}
        <CollapsibleSection
          title="Timeline & Plans"
          isOpen={expandedSections.timeline}
          onToggle={() => toggleSection('timeline')}
        >
          <div className="space-y-6">
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
                <label htmlFor="application_location" className="block text-sm font-medium text-gray-700 mb-1">
                  Where are you applying from?
                </label>
                <select
                  id="application_location"
                  name="application_location"
                  value={formData.application_location}
                  onChange={(e) => setFormData({ ...formData, application_location: e.target.value as ApplicationLocation })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  {APPLICATION_LOCATION_OPTIONS.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                  placeholder="e.g., Paris, Dordogne, undecided"
                />
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
              </div>
            </div>
          </div>
        </CollapsibleSection>

        {/* Financial & Language Section */}
        <CollapsibleSection
          title="Financial & Language"
          isOpen={expandedSections.financial}
          onToggle={() => toggleSection('financial')}
        >
          <div className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label htmlFor="birth_state" className="block text-sm font-medium text-gray-700 mb-1">
                  Your Birth State
                </label>
                <select
                  id="birth_state"
                  name="birth_state"
                  value={formData.birth_state}
                  onChange={(e) => setFormData({ ...formData, birth_state: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                  <option value="">-- Select --</option>
                  {US_STATES.map((state) => (
                    <option key={state.value} value={state.value}>
                      {state.label}
                    </option>
                  ))}
                  <option value="other">Other (not US)</option>
                </select>
                <p className="mt-1 text-sm text-gray-500">
                  Needed for apostille instructions
                </p>
              </div>

              {showBirthStateOther && (
                <div>
                  <label htmlFor="birth_state_other" className="block text-sm font-medium text-gray-700 mb-1">
                    Birth Country/Location
                  </label>
                  <input
                    type="text"
                    id="birth_state_other"
                    name="birth_state_other"
                    value={formData.birth_state_other}
                    onChange={(e) => setFormData({ ...formData, birth_state_other: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    placeholder="e.g., Canada, UK, Mexico"
                  />
                </div>
              )}
            </div>
          </div>
        </CollapsibleSection>

        {/* Documents Section */}
        <CollapsibleSection
          title="Document Status"
          isOpen={expandedSections.documents}
          onToggle={() => toggleSection('documents')}
        >
          <div className="space-y-6">
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
              </div>

              {showApostilleQuestion && (
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
                </div>
              )}
            </div>

            {showMarriageCert && (
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
                      Is marriage certificate apostilled?
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
                  </div>
                )}
              </div>
            )}

            <div className="p-4 bg-blue-50 rounded-lg">
              <p className="text-sm text-blue-800">
                <strong>Tip:</strong> Most US documents need an apostille (authentication stamp) for use in France.
                The process varies by state and document type. Check our guides for state-specific instructions.
              </p>
            </div>
          </div>
        </CollapsibleSection>

        {/* Save Button */}
        <div className="flex items-center gap-3 pt-4">
          <button
            type="submit"
            disabled={updateProfile.isPending}
            className="btn btn-primary flex items-center gap-2"
          >
            {updateProfile.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : updateProfile.isSuccess ? (
              <Check className="w-4 h-4" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            {updateProfile.isPending ? 'Saving...' : updateProfile.isSuccess ? 'Saved!' : 'Save Changes'}
          </button>
          {updateProfile.isError && (
            <span className="text-sm text-red-600">
              Failed to save. Please try again.
            </span>
          )}
        </div>
      </form>
    </div>
  );
}

/**
 * Collapsible section component
 */
function CollapsibleSection({
  title,
  isOpen,
  onToggle,
  children,
}: {
  title: string;
  isOpen: boolean;
  onToggle: () => void;
  children: React.ReactNode;
}) {
  return (
    <div className="border border-gray-200 rounded-lg overflow-hidden">
      <button
        type="button"
        onClick={onToggle}
        className="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 transition-colors"
        aria-expanded={isOpen}
      >
        <h3 className="text-base font-semibold text-gray-900">{title}</h3>
        {isOpen ? (
          <ChevronUp className="w-5 h-5 text-gray-500" aria-hidden="true" />
        ) : (
          <ChevronDown className="w-5 h-5 text-gray-500" aria-hidden="true" />
        )}
      </button>
      {isOpen && (
        <div className="p-4 border-t border-gray-200">
          {children}
        </div>
      )}
    </div>
  );
}
