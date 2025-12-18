import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  User,
  Bell,
  Save,
  Check,
  Loader2,
  FileText,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import {
  useCurrentUser,
  useUpdateProfile,
  useUserSettings,
  useUpdateSettings,
  useMemberProfile,
  useUpdateMemberProfile,
  useProfileCompletion,
} from '@/hooks/useApi';
import type { UserSettings, MemberProfile } from '@/types';

type SettingsTab = 'portal-account' | 'visa-profile' | 'notifications';

// Field type for Visa Profile sections
interface ProfileField {
  key: string;
  label: string;
  type: 'text' | 'date' | 'number' | 'select' | 'textarea';
  placeholder?: string;
  options?: { value: string; label: string }[];
  conditional?: { field: string; values: string[] };
}

interface ProfileSection {
  id: string;
  title: string;
  description: string;
  fields: ProfileField[];
}

export default function SettingsView() {
  const [activeTab, setActiveTab] = useState<SettingsTab>('portal-account');

  const tabs = [
    { id: 'portal-account' as SettingsTab, label: 'Portal Account', icon: User },
    { id: 'visa-profile' as SettingsTab, label: 'Visa Profile', icon: FileText },
    { id: 'notifications' as SettingsTab, label: 'Notifications', icon: Bell },
  ];

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-600 mt-1">
          Manage your profile and preferences
        </p>
      </div>

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Sidebar navigation */}
        <div className="lg:w-64 flex-shrink-0">
          <nav className="card p-2">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={clsx(
                    'w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors',
                    activeTab === tab.id
                      ? 'bg-primary-50 text-primary-700'
                      : 'text-gray-600 hover:bg-gray-50'
                  )}
                >
                  <Icon className="w-5 h-5" />
                  <span className="font-medium">{tab.label}</span>
                </button>
              );
            })}
          </nav>
        </div>

        {/* Content area */}
        <div className="flex-1">
          {activeTab === 'portal-account' && <PortalAccountSection />}
          {activeTab === 'visa-profile' && <VisaProfileSection />}
          {activeTab === 'notifications' && <NotificationsSection />}
        </div>
      </div>
    </div>
  );
}

function PortalAccountSection() {
  const { data: user, isLoading: userLoading } = useCurrentUser();
  const updateProfile = useUpdateProfile();

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    display_name: '',
  });
  const [initialized, setInitialized] = useState(false);

  // Initialize form when user data loads
  if (user && !initialized) {
    setFormData({
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      display_name: user.display_name || '',
    });
    setInitialized(true);
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  if (userLoading) {
    return <SettingsSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Profile Information card */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Profile Information</h2>

        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Avatar */}
          <div className="flex items-center gap-4">
            <img
              src={user?.avatar_url}
              alt={user?.display_name}
              className="w-20 h-20 rounded-full"
            />
            <div>
              <p className="text-sm text-gray-600">
                Profile photo is managed through your Gravatar account
              </p>
              <a
                href="https://gravatar.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-sm text-primary-600 hover:text-primary-700"
              >
                Change on Gravatar
              </a>
            </div>
          </div>

          {/* Name fields */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label htmlFor="first_name" className="block text-sm font-medium text-gray-700 mb-1">
                First Name
              </label>
              <input
                type="text"
                id="first_name"
                value={formData.first_name}
                onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div>
              <label htmlFor="last_name" className="block text-sm font-medium text-gray-700 mb-1">
                Last Name
              </label>
              <input
                type="text"
                id="last_name"
                value={formData.last_name}
                onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
          </div>

          <div>
            <label htmlFor="display_name" className="block text-sm font-medium text-gray-700 mb-1">
              Display Name
            </label>
            <input
              type="text"
              id="display_name"
              value={formData.display_name}
              onChange={(e) => setFormData({ ...formData, display_name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
            <p className="mt-1 text-sm text-gray-500">
              This is how your name will appear throughout the portal
            </p>
          </div>

          {/* Submit button */}
          <div className="flex items-center gap-3">
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

      {/* Account info card */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-6">Account Information</h2>

        <div className="space-y-4">
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Username</span>
            <span className="font-medium text-gray-900">{user?.username}</span>
          </div>
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Email</span>
            <span className="font-medium text-gray-900">{user?.email}</span>
          </div>
          <div className="flex justify-between py-3 border-b border-gray-100">
            <span className="text-gray-600">Account Type</span>
            <span className="font-medium text-gray-900">
              {user?.is_admin ? 'Administrator' : 'Member'}
            </span>
          </div>
          <div className="flex justify-between py-3">
            <span className="text-gray-600">Membership Status</span>
            <span className={clsx(
              'px-2 py-1 rounded-full text-xs font-medium',
              user?.is_member
                ? 'bg-green-100 text-green-700'
                : 'bg-gray-100 text-gray-700'
            )}>
              {user?.is_member ? 'Active' : 'Inactive'}
            </span>
          </div>
        </div>
      </div>

      {/* Security card */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Security</h2>

        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
            <div>
              <h3 className="font-medium text-gray-900">Password</h3>
              <p className="text-sm text-gray-500">Last changed: Unknown</p>
            </div>
            <a
              href={`${window.fraPortalData?.siteUrl || ''}/wp-admin/profile.php`}
              className="btn btn-secondary text-sm"
            >
              Change Password
            </a>
          </div>
        </div>
      </div>

      {/* Danger zone */}
      <div className="card p-6 border-red-200">
        <h2 className="text-lg font-semibold text-red-600 mb-4">Danger Zone</h2>

        <div className="p-4 bg-red-50 rounded-lg">
          <h3 className="font-medium text-gray-900">Delete Account</h3>
          <p className="text-sm text-gray-600 mt-1">
            Once you delete your account, there is no going back. All your data will be permanently removed.
          </p>
          <p className="text-sm text-gray-600 mt-2">
            To delete your account, please contact support.
          </p>
        </div>
      </div>
    </div>
  );
}

// Visa Profile Section with all relocation fields
function VisaProfileSection() {
  const { data: profile, isLoading } = useMemberProfile();
  const { data: completion } = useProfileCompletion();
  const updateProfile = useUpdateMemberProfile();

  const [formData, setFormData] = useState<Partial<MemberProfile>>({});
  const [expandedSections, setExpandedSections] = useState<Record<string, boolean>>({
    personal: true,
    applicants: false,
    visa: false,
    location: false,
    timeline: false,
    financial: false,
    documents: false,
  });

  useEffect(() => {
    if (profile) {
      setFormData(profile);
    }
  }, [profile]);

  const toggleSection = (section: string) => {
    setExpandedSections(prev => ({ ...prev, [section]: !prev[section] }));
  };

  const handleChange = (field: keyof MemberProfile, value: string | number) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile.mutate(formData);
  };

  if (isLoading) {
    return <SettingsSkeleton />;
  }

  const sections: ProfileSection[] = [
    {
      id: 'personal',
      title: '👤 Personal Information',
      description: 'Legal names as they appear on your passport',
      fields: [
        { key: 'legal_first_name', label: 'Legal First Name', type: 'text', placeholder: 'As shown on passport' },
        { key: 'legal_middle_name', label: 'Legal Middle Name(s)', type: 'text', placeholder: 'Optional' },
        { key: 'legal_last_name', label: 'Legal Last Name', type: 'text', placeholder: 'As shown on passport' },
        { key: 'date_of_birth', label: 'Date of Birth', type: 'date' },
        { key: 'nationality', label: 'Nationality', type: 'text', placeholder: 'e.g., American' },
        { key: 'passport_number', label: 'Passport Number', type: 'text', placeholder: 'Optional - for document pre-fill' },
        { key: 'passport_expiry', label: 'Passport Expiry Date', type: 'date' },
      ],
    },
    {
      id: 'applicants',
      title: "👥 Who's Moving?",
      description: 'Information about family members relocating with you',
      fields: [
        { key: 'applicants', label: 'Applicants', type: 'select', options: [
          { value: 'alone', label: 'Applying alone' },
          { value: 'spouse', label: 'With spouse/partner' },
          { value: 'spouse_kids', label: 'With spouse and children' },
          { value: 'kids_only', label: 'With children (no spouse)' },
        ]},
        { key: 'spouse_legal_first_name', label: 'Spouse Legal First Name', type: 'text', conditional: { field: 'applicants', values: ['spouse', 'spouse_kids'] } },
        { key: 'spouse_legal_last_name', label: 'Spouse Legal Last Name', type: 'text', conditional: { field: 'applicants', values: ['spouse', 'spouse_kids'] } },
        { key: 'spouse_date_of_birth', label: 'Spouse Date of Birth', type: 'date', conditional: { field: 'applicants', values: ['spouse', 'spouse_kids'] } },
        { key: 'num_children', label: 'Number of Children', type: 'number', conditional: { field: 'applicants', values: ['spouse_kids', 'kids_only'] } },
        { key: 'children_ages', label: 'Children Ages', type: 'text', placeholder: 'e.g., 5, 8, 12', conditional: { field: 'applicants', values: ['spouse_kids', 'kids_only'] } },
        { key: 'has_pets', label: 'Do you have pets?', type: 'select', options: [
          { value: 'no', label: 'No' },
          { value: 'dogs', label: 'Dog(s)' },
          { value: 'cats', label: 'Cat(s)' },
          { value: 'both', label: 'Dogs and cats' },
          { value: 'other', label: 'Other pets' },
        ]},
        { key: 'pet_details', label: 'Pet Details', type: 'text', placeholder: 'Number and type of pets', conditional: { field: 'has_pets', values: ['dogs', 'cats', 'both', 'other'] } },
      ],
    },
    {
      id: 'visa',
      title: '📋 Visa & Employment',
      description: 'Your visa type and work situation',
      fields: [
        { key: 'visa_type', label: 'Visa Type', type: 'select', options: [
          { value: 'undecided', label: 'Undecided / Need help choosing' },
          { value: 'visitor', label: 'Visitor Visa (VLS-TS Visiteur)' },
          { value: 'talent_passport', label: 'Talent Passport' },
          { value: 'employee', label: 'Employee Visa' },
          { value: 'entrepreneur', label: 'Entrepreneur Visa' },
          { value: 'student', label: 'Student Visa' },
          { value: 'family', label: 'Family Reunification' },
          { value: 'spouse_french', label: 'Spouse of French National' },
          { value: 'retiree', label: 'Retiree Visa' },
        ]},
        { key: 'employment_status', label: 'Your Employment Status', type: 'select', options: [
          { value: 'employed', label: 'Employed' },
          { value: 'self_employed', label: 'Self-employed' },
          { value: 'retired', label: 'Retired' },
          { value: 'not_working', label: 'Not currently working' },
        ]},
        { key: 'work_in_france', label: 'Planning to work in France?', type: 'select', options: [
          { value: 'no', label: 'No' },
          { value: 'yes_local', label: 'Yes, for a French employer' },
          { value: 'yes_remote', label: 'Yes, remotely for a US company' },
          { value: 'yes_self', label: 'Yes, self-employed' },
          { value: 'undecided', label: 'Undecided' },
        ]},
        { key: 'industry', label: 'Industry/Field', type: 'text' },
        { key: 'employer_name', label: 'Employer Name', type: 'text', conditional: { field: 'employment_status', values: ['employed'] } },
        { key: 'job_title', label: 'Job Title', type: 'text' },
      ],
    },
    {
      id: 'location',
      title: '📍 Locations',
      description: 'Current location and destination in France',
      fields: [
        { key: 'current_state', label: 'Current US State', type: 'select', options: US_STATES },
        { key: 'birth_state', label: 'Your Birth State', type: 'select', options: [...US_STATES, { value: 'other', label: 'Other (not US)' }] },
        { key: 'birth_state_other', label: 'Birth Country/Location', type: 'text', conditional: { field: 'birth_state', values: ['other'] } },
        { key: 'target_location', label: 'Target Location in France', type: 'text', placeholder: 'e.g., Paris, Dordogne, undecided' },
        { key: 'housing_plan', label: 'Housing Plans', type: 'select', options: [
          { value: 'buying', label: 'Buying property' },
          { value: 'renting', label: 'Renting' },
          { value: 'undecided', label: 'Undecided' },
          { value: 'already_own', label: 'Already own property' },
        ]},
      ],
    },
    {
      id: 'timeline',
      title: '📅 Timeline & Plans',
      description: 'When are you planning to move?',
      fields: [
        { key: 'timeline', label: 'Move Timeline', type: 'select', options: [
          { value: 'asap', label: 'As soon as possible' },
          { value: '3_months', label: 'Within 3 months' },
          { value: '6_months', label: '3-6 months' },
          { value: '12_months', label: '6-12 months' },
          { value: 'over_12', label: 'More than 12 months' },
          { value: 'undecided', label: 'Undecided' },
        ]},
        { key: 'target_move_date', label: 'Target Move Date', type: 'date' },
        { key: 'application_location', label: 'Where are you applying from?', type: 'select', options: [
          { value: 'us', label: 'United States (initial application)' },
          { value: 'france', label: 'France (renewal/reapplication)' },
        ]},
      ],
    },
    {
      id: 'financial',
      title: '💰 Financial',
      description: 'Income and financial planning',
      fields: [
        { key: 'french_proficiency', label: 'French Language Proficiency', type: 'select', options: [
          { value: 'none', label: 'None' },
          { value: 'basic', label: 'Basic' },
          { value: 'conversational', label: 'Conversational' },
          { value: 'fluent', label: 'Fluent' },
          { value: 'native', label: 'Native' },
        ]},
        { key: 'french_mortgage', label: 'Planning to get a French mortgage?', type: 'select', options: [
          { value: 'no', label: 'No' },
          { value: 'yes', label: 'Yes' },
          { value: 'maybe', label: 'Maybe/Undecided' },
        ]},
      ],
    },
    {
      id: 'documents',
      title: '📄 Document Status',
      description: 'Track your vital documents',
      fields: [
        { key: 'has_birth_cert', label: 'Have certified birth certificate?', type: 'select', options: [
          { value: 'yes', label: 'Yes' },
          { value: 'no', label: 'No, need to order' },
          { value: 'unsure', label: "Not sure if it's certified" },
        ]},
        { key: 'birth_cert_apostilled', label: 'Is it apostilled?', type: 'select', options: [
          { value: 'yes', label: 'Yes' },
          { value: 'no', label: 'No' },
        ], conditional: { field: 'has_birth_cert', values: ['yes'] } },
        { key: 'has_marriage_cert', label: 'Have certified marriage certificate?', type: 'select', options: [
          { value: 'yes', label: 'Yes' },
          { value: 'no', label: 'No, need to order' },
          { value: 'unsure', label: "Not sure if it's certified" },
          { value: 'na', label: 'Not applicable' },
        ], conditional: { field: 'applicants', values: ['spouse', 'spouse_kids'] } },
        { key: 'marriage_cert_apostilled', label: 'Is marriage certificate apostilled?', type: 'select', options: [
          { value: 'yes', label: 'Yes' },
          { value: 'no', label: 'No' },
        ], conditional: { field: 'has_marriage_cert', values: ['yes'] } },
      ],
    },
  ];

  // Helper to check if field should be shown based on conditional
  const shouldShowField = (field: ProfileField): boolean => {
    if (!field.conditional) return true;
    const currentValue = formData[field.conditional.field as keyof MemberProfile];
    return field.conditional.values.includes(currentValue as string);
  };

  return (
    <div className="space-y-4">
      {/* Progress bar */}
      <div className="card p-4">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-medium text-gray-700">Profile Completion</span>
          <span className="text-sm text-gray-500">{completion?.percentage || 0}%</span>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className="bg-primary-500 h-2 rounded-full transition-all duration-300"
            style={{ width: `${completion?.percentage || 0}%` }}
          />
        </div>
        <p className="text-xs text-gray-500 mt-2">
          Complete your profile to enable document generation and personalized recommendations.
        </p>
      </div>

      <form onSubmit={handleSubmit}>
        {sections.map((section) => (
          <div key={section.id} className="card mb-4 overflow-hidden">
            <button
              type="button"
              onClick={() => toggleSection(section.id)}
              className="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors"
            >
              <div>
                <h3 className="text-lg font-semibold text-gray-900">{section.title}</h3>
                <p className="text-sm text-gray-500">{section.description}</p>
              </div>
              {expandedSections[section.id] ? (
                <ChevronUp className="w-5 h-5 text-gray-400" />
              ) : (
                <ChevronDown className="w-5 h-5 text-gray-400" />
              )}
            </button>

            {expandedSections[section.id] && (
              <div className="p-4 pt-0 border-t border-gray-100">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  {section.fields.filter(shouldShowField).map((field) => (
                    <div key={field.key} className={field.type === 'textarea' ? 'md:col-span-2' : ''}>
                      <label
                        htmlFor={field.key}
                        className="block text-sm font-medium text-gray-700 mb-1"
                      >
                        {field.label}
                      </label>
                      {field.type === 'select' ? (
                        <select
                          id={field.key}
                          value={(formData[field.key as keyof MemberProfile] as string) || ''}
                          onChange={(e) => handleChange(field.key as keyof MemberProfile, e.target.value)}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                          <option value="">-- Select --</option>
                          {field.options?.map((opt) => (
                            <option key={opt.value} value={opt.value}>
                              {opt.label}
                            </option>
                          ))}
                        </select>
                      ) : field.type === 'number' ? (
                        <input
                          type="number"
                          id={field.key}
                          value={(formData[field.key as keyof MemberProfile] as number) || ''}
                          onChange={(e) => handleChange(field.key as keyof MemberProfile, parseInt(e.target.value) || 0)}
                          min="0"
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                      ) : (
                        <input
                          type={field.type}
                          id={field.key}
                          value={(formData[field.key as keyof MemberProfile] as string) || ''}
                          onChange={(e) => handleChange(field.key as keyof MemberProfile, e.target.value)}
                          placeholder={field.placeholder}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        />
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        ))}

        {/* Submit button */}
        <div className="flex items-center gap-3 mt-6">
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
            {updateProfile.isPending ? 'Saving...' : updateProfile.isSuccess ? 'Saved!' : 'Save Visa Profile'}
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

// US States for dropdown
const US_STATES = [
  { value: 'AL', label: 'Alabama' }, { value: 'AK', label: 'Alaska' }, { value: 'AZ', label: 'Arizona' },
  { value: 'AR', label: 'Arkansas' }, { value: 'CA', label: 'California' }, { value: 'CO', label: 'Colorado' },
  { value: 'CT', label: 'Connecticut' }, { value: 'DE', label: 'Delaware' }, { value: 'DC', label: 'District of Columbia' },
  { value: 'FL', label: 'Florida' }, { value: 'GA', label: 'Georgia' }, { value: 'HI', label: 'Hawaii' },
  { value: 'ID', label: 'Idaho' }, { value: 'IL', label: 'Illinois' }, { value: 'IN', label: 'Indiana' },
  { value: 'IA', label: 'Iowa' }, { value: 'KS', label: 'Kansas' }, { value: 'KY', label: 'Kentucky' },
  { value: 'LA', label: 'Louisiana' }, { value: 'ME', label: 'Maine' }, { value: 'MD', label: 'Maryland' },
  { value: 'MA', label: 'Massachusetts' }, { value: 'MI', label: 'Michigan' }, { value: 'MN', label: 'Minnesota' },
  { value: 'MS', label: 'Mississippi' }, { value: 'MO', label: 'Missouri' }, { value: 'MT', label: 'Montana' },
  { value: 'NE', label: 'Nebraska' }, { value: 'NV', label: 'Nevada' }, { value: 'NH', label: 'New Hampshire' },
  { value: 'NJ', label: 'New Jersey' }, { value: 'NM', label: 'New Mexico' }, { value: 'NY', label: 'New York' },
  { value: 'NC', label: 'North Carolina' }, { value: 'ND', label: 'North Dakota' }, { value: 'OH', label: 'Ohio' },
  { value: 'OK', label: 'Oklahoma' }, { value: 'OR', label: 'Oregon' }, { value: 'PA', label: 'Pennsylvania' },
  { value: 'RI', label: 'Rhode Island' }, { value: 'SC', label: 'South Carolina' }, { value: 'SD', label: 'South Dakota' },
  { value: 'TN', label: 'Tennessee' }, { value: 'TX', label: 'Texas' }, { value: 'UT', label: 'Utah' },
  { value: 'VT', label: 'Vermont' }, { value: 'VA', label: 'Virginia' }, { value: 'WA', label: 'Washington' },
  { value: 'WV', label: 'West Virginia' }, { value: 'WI', label: 'Wisconsin' }, { value: 'WY', label: 'Wyoming' },
];

function NotificationsSection() {
  const { data: settings, isLoading } = useUserSettings();
  const updateSettings = useUpdateSettings();

  const handleToggle = (key: keyof UserSettings, value: boolean) => {
    updateSettings.mutate({ [key]: value });
  };

  if (isLoading) {
    return <SettingsSkeleton />;
  }

  const toggles = [
    {
      key: 'email_notifications' as keyof UserSettings,
      label: 'Email Notifications',
      description: 'Receive email updates about your relocation progress',
    },
    {
      key: 'task_reminders' as keyof UserSettings,
      label: 'Task Reminders',
      description: 'Get reminded about upcoming and overdue tasks',
    },
    {
      key: 'weekly_digest' as keyof UserSettings,
      label: 'Weekly Digest',
      description: 'Receive a weekly summary of your progress',
    },
  ];

  return (
    <div className="card p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-6">Notification Preferences</h2>

      <div className="space-y-6">
        {toggles.map((toggle) => (
          <div key={toggle.key} className="flex items-center justify-between">
            <div>
              <h3 className="font-medium text-gray-900">{toggle.label}</h3>
              <p className="text-sm text-gray-500">{toggle.description}</p>
            </div>
            <button
              type="button"
              onClick={() => handleToggle(toggle.key, !settings?.[toggle.key])}
              className={clsx(
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                settings?.[toggle.key] ? 'bg-primary-600' : 'bg-gray-200'
              )}
            >
              <span
                className={clsx(
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  settings?.[toggle.key] ? 'translate-x-5' : 'translate-x-0'
                )}
              />
            </button>
          </div>
        ))}
      </div>

      {/* Language preference */}
      <div className="mt-8 pt-6 border-t border-gray-200">
        <h3 className="font-medium text-gray-900 mb-4">Regional Settings</h3>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="language" className="block text-sm font-medium text-gray-700 mb-1">
              Language
            </label>
            <select
              id="language"
              value={settings?.language || 'en'}
              onChange={(e) => updateSettings.mutate({ language: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="en">English</option>
              <option value="fr">Fran&#231;ais</option>
            </select>
          </div>

          <div>
            <label htmlFor="timezone" className="block text-sm font-medium text-gray-700 mb-1">
              Timezone
            </label>
            <select
              id="timezone"
              value={settings?.timezone || 'Europe/Paris'}
              onChange={(e) => updateSettings.mutate({ timezone: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="Europe/Paris">Paris (CET)</option>
              <option value="Europe/London">London (GMT)</option>
              <option value="America/New_York">New York (EST)</option>
              <option value="America/Los_Angeles">Los Angeles (PST)</option>
              <option value="Asia/Tokyo">Tokyo (JST)</option>
            </select>
          </div>
        </div>
      </div>
    </div>
  );
}

function SettingsSkeleton() {
  return (
    <div className="card p-6">
      <div className="h-6 w-48 bg-gray-200 rounded animate-pulse mb-6" />
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-12 bg-gray-200 rounded animate-pulse" />
        ))}
      </div>
    </div>
  );
}
