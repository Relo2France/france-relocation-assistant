import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  User,
  Users,
  Briefcase,
  MapPin,
  DollarSign,
  ChevronDown,
  ChevronUp,
  Save,
  Check,
  Loader2,
  AlertCircle,
} from 'lucide-react';
import { useMemberProfile, useUpdateMemberProfile } from '@/hooks/useApi';
import type {
  MemberProfile,
  ApplicantType,
  WorkStatus,
  PetType,
  ProfileVisaType,
  WorkInFranceType,
  ApplicationLocation,
  FinancialRange,
} from '@/types';

// US States dropdown data
const US_STATES = [
  { value: 'AL', label: 'Alabama' },
  { value: 'AK', label: 'Alaska' },
  { value: 'AZ', label: 'Arizona' },
  { value: 'AR', label: 'Arkansas' },
  { value: 'CA', label: 'California' },
  { value: 'CO', label: 'Colorado' },
  { value: 'CT', label: 'Connecticut' },
  { value: 'DE', label: 'Delaware' },
  { value: 'DC', label: 'District of Columbia' },
  { value: 'FL', label: 'Florida' },
  { value: 'GA', label: 'Georgia' },
  { value: 'HI', label: 'Hawaii' },
  { value: 'ID', label: 'Idaho' },
  { value: 'IL', label: 'Illinois' },
  { value: 'IN', label: 'Indiana' },
  { value: 'IA', label: 'Iowa' },
  { value: 'KS', label: 'Kansas' },
  { value: 'KY', label: 'Kentucky' },
  { value: 'LA', label: 'Louisiana' },
  { value: 'ME', label: 'Maine' },
  { value: 'MD', label: 'Maryland' },
  { value: 'MA', label: 'Massachusetts' },
  { value: 'MI', label: 'Michigan' },
  { value: 'MN', label: 'Minnesota' },
  { value: 'MS', label: 'Mississippi' },
  { value: 'MO', label: 'Missouri' },
  { value: 'MT', label: 'Montana' },
  { value: 'NE', label: 'Nebraska' },
  { value: 'NV', label: 'Nevada' },
  { value: 'NH', label: 'New Hampshire' },
  { value: 'NJ', label: 'New Jersey' },
  { value: 'NM', label: 'New Mexico' },
  { value: 'NY', label: 'New York' },
  { value: 'NC', label: 'North Carolina' },
  { value: 'ND', label: 'North Dakota' },
  { value: 'OH', label: 'Ohio' },
  { value: 'OK', label: 'Oklahoma' },
  { value: 'OR', label: 'Oregon' },
  { value: 'PA', label: 'Pennsylvania' },
  { value: 'PR', label: 'Puerto Rico' },
  { value: 'RI', label: 'Rhode Island' },
  { value: 'SC', label: 'South Carolina' },
  { value: 'SD', label: 'South Dakota' },
  { value: 'TN', label: 'Tennessee' },
  { value: 'TX', label: 'Texas' },
  { value: 'UT', label: 'Utah' },
  { value: 'VT', label: 'Vermont' },
  { value: 'VI', label: 'Virgin Islands' },
  { value: 'VA', label: 'Virginia' },
  { value: 'WA', label: 'Washington' },
  { value: 'WV', label: 'West Virginia' },
  { value: 'WI', label: 'Wisconsin' },
  { value: 'WY', label: 'Wyoming' },
];

// Visa type options with descriptions
const VISA_TYPES = [
  { value: 'undecided', label: 'Undecided', description: 'Still exploring visa options' },
  { value: 'visitor', label: 'Visitor Visa', description: 'Short-term stay (up to 90 days)' },
  { value: 'talent_passport', label: 'Talent Passport', description: 'For highly skilled professionals' },
  { value: 'employee', label: 'Employee Visa', description: 'Working for a French company' },
  { value: 'entrepreneur', label: 'Entrepreneur Visa', description: 'Starting a business in France' },
  { value: 'student', label: 'Student Visa', description: 'Studying at a French institution' },
  { value: 'family', label: 'Family Reunification', description: 'Joining family members in France' },
  { value: 'spouse_french', label: 'Spouse of French Citizen', description: 'Married to a French national' },
  { value: 'retiree', label: 'Retiree Visa', description: 'Retiring in France' },
];

type SectionId = 'personal' | 'applicant' | 'visa' | 'location' | 'financial';

export default function ProfileView() {
  const { data: profile, isLoading: profileLoading } = useMemberProfile();
  const [openSections, setOpenSections] = useState<Set<SectionId>>(
    new Set(['personal', 'applicant', 'visa', 'location', 'financial'])
  );

  const toggleSection = (sectionId: SectionId) => {
    setOpenSections((prev) => {
      const next = new Set(prev);
      if (next.has(sectionId)) {
        next.delete(sectionId);
      } else {
        next.add(sectionId);
      }
      return next;
    });
  };

  const sections = [
    { id: 'personal' as SectionId, label: 'Personal Information', icon: User, component: PersonalSection },
    { id: 'applicant' as SectionId, label: 'Applicant & Family', icon: Users, component: ApplicantSection },
    { id: 'visa' as SectionId, label: 'Visa & Employment', icon: Briefcase, component: VisaSection },
    { id: 'location' as SectionId, label: 'Location Information', icon: MapPin, component: LocationSection },
    { id: 'financial' as SectionId, label: 'Financial Information', icon: DollarSign, component: FinancialSection },
  ];

  if (profileLoading) {
    return <ProfileSkeleton />;
  }

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">My Profile</h1>
        <p className="text-gray-600 mt-1">
          Manage your personal information for visa applications
        </p>
      </div>

      {/* Profile completion indicator */}
      {profile && (
        <div className="card p-6 mb-6">
          <div className="flex items-center justify-between mb-2">
            <h2 className="text-lg font-semibold text-gray-900">Profile Completion</h2>
            <span className="text-2xl font-bold text-primary-600">
              {profile.profile_completion || 0}%
            </span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3">
            <div
              className={clsx(
                'h-3 rounded-full transition-all duration-500',
                profile.profile_completion >= 80 ? 'bg-green-500' :
                profile.profile_completion >= 50 ? 'bg-primary-500' :
                'bg-yellow-500'
              )}
              style={{ width: `${profile.profile_completion || 0}%` }}
            />
          </div>
          <p className="text-sm text-gray-600 mt-2">
            Complete your profile to help us provide better guidance for your relocation
          </p>
        </div>
      )}

      {/* Sections */}
      <div className="space-y-4">
        {sections.map((section) => {
          const Icon = section.icon;
          const isOpen = openSections.has(section.id);
          const Component = section.component;

          return (
            <div key={section.id} className="card overflow-hidden">
              {/* Section header - collapsible */}
              <button
                onClick={() => toggleSection(section.id)}
                className="w-full flex items-center justify-between p-6 hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-center gap-3">
                  <Icon className="w-5 h-5 text-primary-600" />
                  <h2 className="text-lg font-semibold text-gray-900">{section.label}</h2>
                </div>
                {isOpen ? (
                  <ChevronUp className="w-5 h-5 text-gray-400" />
                ) : (
                  <ChevronDown className="w-5 h-5 text-gray-400" />
                )}
              </button>

              {/* Section content */}
              {isOpen && (
                <div className="px-6 pb-6 border-t border-gray-100">
                  <Component profile={profile} />
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

// Personal Information Section
function PersonalSection({ profile }: { profile: MemberProfile | undefined }) {
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
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div className="text-sm text-blue-800">
            <p className="font-medium">Important: Legal Names Required</p>
            <p className="mt-1">Use your passport/legal names exactly as they appear on official documents.</p>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label htmlFor="legal_first_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal First Name <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="legal_first_name"
            value={formData.legal_first_name}
            onChange={(e) => setFormData({ ...formData, legal_first_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
          />
        </div>
        <div>
          <label htmlFor="legal_middle_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal Middle Name
          </label>
          <input
            type="text"
            id="legal_middle_name"
            value={formData.legal_middle_name}
            onChange={(e) => setFormData({ ...formData, legal_middle_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        <div>
          <label htmlFor="legal_last_name" className="block text-sm font-medium text-gray-700 mb-1">
            Legal Last Name <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="legal_last_name"
            value={formData.legal_last_name}
            onChange={(e) => setFormData({ ...formData, legal_last_name: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="date_of_birth" className="block text-sm font-medium text-gray-700 mb-1">
            Date of Birth <span className="text-red-500">*</span>
          </label>
          <input
            type="date"
            id="date_of_birth"
            value={formData.date_of_birth}
            onChange={(e) => setFormData({ ...formData, date_of_birth: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
          />
        </div>
        <div>
          <label htmlFor="nationality" className="block text-sm font-medium text-gray-700 mb-1">
            Nationality <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="nationality"
            value={formData.nationality}
            onChange={(e) => setFormData({ ...formData, nationality: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., American, Canadian"
            required
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
            value={formData.passport_number}
            onChange={(e) => setFormData({ ...formData, passport_number: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        <div>
          <label htmlFor="passport_expiry" className="block text-sm font-medium text-gray-700 mb-1">
            Passport Expiry Date
          </label>
          <input
            type="date"
            id="passport_expiry"
            value={formData.passport_expiry}
            onChange={(e) => setFormData({ ...formData, passport_expiry: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      {/* Submit button */}
      <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
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
  );
}

// Applicant & Family Section
function ApplicantSection({ profile }: { profile: MemberProfile | undefined }) {
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
          Who will be relocating? <span className="text-red-500">*</span>
        </label>
        <select
          id="applicants"
          value={formData.applicants}
          onChange={(e) => setFormData({ ...formData, applicants: e.target.value as ApplicantType })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          required
        >
          <option value="alone">Just me (alone)</option>
          <option value="spouse">Me and my spouse/partner</option>
          <option value="spouse_kids">Me, spouse/partner, and children</option>
          <option value="kids_only">Me and children (no spouse/partner)</option>
        </select>
      </div>

      {/* Spouse fields */}
      {hasSpouse && (
        <div className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <h3 className="font-medium text-gray-900">Spouse/Partner Information</h3>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="spouse_legal_first_name" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Legal First Name
              </label>
              <input
                type="text"
                id="spouse_legal_first_name"
                value={formData.spouse_legal_first_name}
                onChange={(e) => setFormData({ ...formData, spouse_legal_first_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div>
              <label htmlFor="spouse_legal_last_name" className="block text-sm font-medium text-gray-700 mb-1">
                Spouse Legal Last Name
              </label>
              <input
                type="text"
                id="spouse_legal_last_name"
                value={formData.spouse_legal_last_name}
                onChange={(e) => setFormData({ ...formData, spouse_legal_last_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
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
                value={formData.spouse_name}
                onChange={(e) => setFormData({ ...formData, spouse_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="If different from legal name"
              />
            </div>
          </div>

          <div>
            <label htmlFor="spouse_work_status" className="block text-sm font-medium text-gray-700 mb-1">
              Spouse Work Status
            </label>
            <select
              id="spouse_work_status"
              value={formData.spouse_work_status}
              onChange={(e) => setFormData({ ...formData, spouse_work_status: e.target.value as WorkStatus })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="employed">Employed</option>
              <option value="self_employed">Self-Employed</option>
              <option value="retired">Retired</option>
              <option value="not_working">Not Working</option>
            </select>
          </div>
        </div>
      )}

      {/* Children fields */}
      {hasChildren && (
        <div className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <h3 className="font-medium text-gray-900">Children Information</h3>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="num_children" className="block text-sm font-medium text-gray-700 mb-1">
                Number of Children
              </label>
              <input
                type="number"
                id="num_children"
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
                value={formData.children_ages}
                onChange={(e) => setFormData({ ...formData, children_ages: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="e.g., 5, 8, 12"
              />
            </div>
          </div>
        </div>
      )}

      {/* Pets */}
      <div className="space-y-4">
        <div>
          <label htmlFor="has_pets" className="block text-sm font-medium text-gray-700 mb-1">
            Do you have pets?
          </label>
          <select
            id="has_pets"
            value={formData.has_pets}
            onChange={(e) => setFormData({ ...formData, has_pets: e.target.value as PetType })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="no">No pets</option>
            <option value="dogs">Dog(s)</option>
            <option value="cats">Cat(s)</option>
            <option value="both">Both dogs and cats</option>
            <option value="other">Other pets</option>
          </select>
        </div>

        {hasPets && (
          <div>
            <label htmlFor="pet_details" className="block text-sm font-medium text-gray-700 mb-1">
              Pet Details
            </label>
            <textarea
              id="pet_details"
              value={formData.pet_details}
              onChange={(e) => setFormData({ ...formData, pet_details: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Breed, size, age, etc."
            />
          </div>
        )}
      </div>

      {/* Submit button */}
      <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
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
  );
}

// Visa & Employment Section
function VisaSection({ profile }: { profile: MemberProfile | undefined }) {
  const updateProfile = useUpdateMemberProfile();
  const [formData, setFormData] = useState({
    visa_type: 'undecided' as ProfileVisaType,
    employment_status: 'not_working' as WorkStatus,
    work_in_france: 'undecided' as WorkInFranceType,
    industry: '',
    employer_name: '',
  });
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (profile && !initialized) {
      setFormData({
        visa_type: (profile.visa_type as ProfileVisaType) || 'undecided',
        employment_status: (profile.employment_status as WorkStatus) || 'not_working',
        work_in_france: (profile.work_in_france as WorkInFranceType) || 'undecided',
        industry: profile.industry || '',
        employer_name: profile.employer_name || '',
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
          Visa Type <span className="text-red-500">*</span>
        </label>
        <select
          id="visa_type"
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
          Select the visa type that best matches your situation. We'll help you refine this choice.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="employment_status" className="block text-sm font-medium text-gray-700 mb-1">
            Current Employment Status
          </label>
          <select
            id="employment_status"
            value={formData.employment_status}
            onChange={(e) => setFormData({ ...formData, employment_status: e.target.value as WorkStatus })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="employed">Employed</option>
            <option value="self_employed">Self-Employed</option>
            <option value="retired">Retired</option>
            <option value="not_working">Not Working</option>
          </select>
        </div>

        <div>
          <label htmlFor="work_in_france" className="block text-sm font-medium text-gray-700 mb-1">
            Work Plans in France
          </label>
          <select
            id="work_in_france"
            value={formData.work_in_france}
            onChange={(e) => setFormData({ ...formData, work_in_france: e.target.value as WorkInFranceType })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="undecided">Undecided</option>
            <option value="no">No, not planning to work</option>
            <option value="yes_local">Yes, for a French company</option>
            <option value="yes_remote">Yes, remote work for non-French company</option>
            <option value="yes_self">Yes, self-employed/freelance</option>
          </select>
        </div>
      </div>

      {isEmployed && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="industry" className="block text-sm font-medium text-gray-700 mb-1">
              Industry/Field
            </label>
            <input
              type="text"
              id="industry"
              value={formData.industry}
              onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., Technology, Healthcare, Finance"
            />
          </div>

          <div>
            <label htmlFor="employer_name" className="block text-sm font-medium text-gray-700 mb-1">
              Current Employer
            </label>
            <input
              type="text"
              id="employer_name"
              value={formData.employer_name}
              onChange={(e) => setFormData({ ...formData, employer_name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="Company name"
            />
          </div>
        </div>
      )}

      {/* Submit button */}
      <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
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
  );
}

// Location Section
function LocationSection({ profile }: { profile: MemberProfile | undefined }) {
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
      <div className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 className="font-medium text-gray-900">Current Location</h3>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label htmlFor="current_country" className="block text-sm font-medium text-gray-700 mb-1">
              Current Country
            </label>
            <input
              type="text"
              id="current_country"
              value={formData.current_country}
              onChange={(e) => setFormData({ ...formData, current_country: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., United States"
            />
          </div>

          <div>
            <label htmlFor="current_state" className="block text-sm font-medium text-gray-700 mb-1">
              Current State
            </label>
            <select
              id="current_state"
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
              value={formData.current_city}
              onChange={(e) => setFormData({ ...formData, current_city: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="e.g., New York"
            />
          </div>
        </div>
      </div>

      <div className="space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 className="font-medium text-gray-900">Birth & Marriage Information</h3>
        <p className="text-sm text-gray-600">Required for apostille processing</p>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="birth_state" className="block text-sm font-medium text-gray-700 mb-1">
              Your Birth State (US)
            </label>
            <select
              id="birth_state"
              value={formData.birth_state}
              onChange={(e) => setFormData({ ...formData, birth_state: e.target.value })}
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
            <label htmlFor="spouse_birth_state" className="block text-sm font-medium text-gray-700 mb-1">
              Spouse Birth State (US)
            </label>
            <select
              id="spouse_birth_state"
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
              value={formData.marriage_country}
              onChange={(e) => setFormData({ ...formData, marriage_country: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              placeholder="If married outside the US"
            />
          </div>
        </div>
      </div>

      <div className="space-y-4">
        <div>
          <label htmlFor="target_location" className="block text-sm font-medium text-gray-700 mb-1">
            Target Location in France
          </label>
          <input
            type="text"
            id="target_location"
            value={formData.target_location}
            onChange={(e) => setFormData({ ...formData, target_location: e.target.value })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., Paris, Lyon, Provence"
          />
          <p className="mt-1 text-sm text-gray-500">
            Where in France are you planning to relocate?
          </p>
        </div>

        <div>
          <label htmlFor="application_location" className="block text-sm font-medium text-gray-700 mb-1">
            Where will you apply for your visa?
          </label>
          <select
            id="application_location"
            value={formData.application_location}
            onChange={(e) => setFormData({ ...formData, application_location: e.target.value as ApplicationLocation })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="us">United States (US Consulate)</option>
            <option value="france">France (Already in France)</option>
          </select>
          <p className="mt-1 text-sm text-gray-500">
            This affects the application process and requirements
          </p>
        </div>
      </div>

      {/* Submit button */}
      <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
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
  );
}

// Financial Section
function FinancialSection({ profile }: { profile: MemberProfile | undefined }) {
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
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div className="flex gap-2">
          <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
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
          value={formData.financial_resources}
          onChange={(e) => setFormData({ ...formData, financial_resources: e.target.value as FinancialRange })}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
          <option value="under_50k">Under $50,000</option>
          <option value="50k_100k">$50,000 - $100,000</option>
          <option value="100k_200k">$100,000 - $200,000</option>
          <option value="200k_500k">$200,000 - $500,000</option>
          <option value="over_500k">Over $500,000</option>
        </select>
        <p className="mt-1 text-sm text-gray-500">
          Combined savings, investments, and liquid assets available for relocation
        </p>
      </div>

      <div>
        <label htmlFor="income_sources" className="block text-sm font-medium text-gray-700 mb-1">
          Income Sources
        </label>
        <textarea
          id="income_sources"
          value={formData.income_sources}
          onChange={(e) => setFormData({ ...formData, income_sources: e.target.value })}
          rows={4}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="e.g., Employment, investments, rental income, pension, retirement accounts, etc."
        />
        <p className="mt-1 text-sm text-gray-500">
          Describe your current and expected income sources to support your stay in France
        </p>
      </div>

      {/* Submit button */}
      <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
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
  );
}

// Loading Skeleton
function ProfileSkeleton() {
  return (
    <div className="p-6">
      <div className="mb-6">
        <div className="h-8 w-48 bg-gray-200 rounded animate-pulse mb-2" />
        <div className="h-4 w-96 bg-gray-200 rounded animate-pulse" />
      </div>

      <div className="card p-6 mb-6">
        <div className="h-6 w-64 bg-gray-200 rounded animate-pulse mb-4" />
        <div className="h-3 w-full bg-gray-200 rounded animate-pulse" />
      </div>

      <div className="space-y-4">
        {[1, 2, 3, 4, 5].map((i) => (
          <div key={i} className="card p-6">
            <div className="h-6 w-48 bg-gray-200 rounded animate-pulse" />
          </div>
        ))}
      </div>
    </div>
  );
}
