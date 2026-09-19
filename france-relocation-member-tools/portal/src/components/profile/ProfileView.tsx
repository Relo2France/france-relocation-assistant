/**
 * ProfileView Component
 *
 * Main profile page that displays collapsible sections for managing
 * personal, family, visa, location, and financial information.
 *
 * Refactored to use extracted sub-components for better maintainability.
 */

import { useState, useEffect } from 'react';
import { usePortalStore } from '@/store';
import { clsx } from 'clsx';
import {
  Briefcase,
  Calendar,
  ChevronDown,
  ChevronUp,
  DollarSign,
  FileText,
  MapPin,
  User,
  Users,
} from 'lucide-react';
import { useMemberProfile, useProfileCompletion } from '@/hooks/useApi';
import type { MemberProfile } from '@/types';
import ApplicantSection from './ApplicantSection';
import DocumentsSection from './DocumentsSection';
import FinancialSection from './FinancialSection';
import LocationSection from './LocationSection';
import PersonalSection from './PersonalSection';
import ProfileSkeleton from './ProfileSkeleton';
import TimelineSection from './TimelineSection';
import VisaSection from './VisaSection';

type SectionId = 'personal' | 'applicant' | 'visa' | 'location' | 'timeline' | 'financial' | 'documents';

/** The fields the completion score counts, in plain words, and where each one lives. */
const FIELD_LABEL: Record<string, string> = {
  legal_first_name: 'Legal first name',
  legal_last_name: 'Legal last name',
  date_of_birth: 'Date of birth',
  nationality: 'Nationality',
  passport_number: 'Passport number',
  passport_expiry: 'Passport expiry date',
  applicants: 'Who is applying',
  visa_type: 'Visa route',
  employment_status: 'Employment status',
  current_state: 'Current state',
  target_location: 'Target location in France',
  timeline: 'Timeline',
  application_location: 'Where you will apply',
};
const FIELD_SECTION: Record<string, SectionId> = {
  legal_first_name: 'personal',
  legal_last_name: 'personal',
  date_of_birth: 'personal',
  nationality: 'personal',
  passport_number: 'personal',
  passport_expiry: 'personal',
  applicants: 'applicant',
  visa_type: 'visa',
  employment_status: 'visa',
  current_state: 'location',
  target_location: 'location',
  timeline: 'timeline',
  application_location: 'location',
};

interface ProfileSection {
  id: SectionId;
  label: string;
  icon: typeof User;
  component: React.ComponentType<{ profile: MemberProfile | undefined }>;
}

const sections: ProfileSection[] = [
  { id: 'personal', label: 'Personal information', icon: User, component: PersonalSection },
  { id: 'applicant', label: 'Applicant & Family', icon: Users, component: ApplicantSection },
  { id: 'visa', label: 'Visa & employment', icon: Briefcase, component: VisaSection },
  { id: 'location', label: 'Location information', icon: MapPin, component: LocationSection },
  { id: 'timeline', label: 'Timeline & plans', icon: Calendar, component: TimelineSection },
  { id: 'financial', label: 'Financial & language', icon: DollarSign, component: FinancialSection },
  { id: 'documents', label: 'Document status', icon: FileText, component: DocumentsSection },
];

export default function ProfileView() {
  const { data: profile, isLoading: profileLoading } = useMemberProfile();
  const { data: completion } = useProfileCompletion();
  const { profileSection, setProfileSection } = usePortalStore();
  const [openSections, setOpenSections] = useState<Set<SectionId>>(
    new Set(['personal', 'applicant', 'visa', 'location', 'timeline', 'financial', 'documents'])
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

  // Arrived to change one thing ("Change it in my profile"): open that
  // section and bring it into view.
  useEffect(() => {
    if (profileLoading || !profileSection) return;
    const id = profileSection as SectionId;
    setProfileSection(null);
    setOpenSections((prev) => new Set(prev).add(id));
    window.setTimeout(() => document.getElementById(`section-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
  }, [profileLoading, profileSection, setProfileSection]);

  if (profileLoading) {
    return <ProfileSkeleton />;
  }

  const completionPercentage = completion?.percentage ?? profile?.profile_completion ?? 0;
  const missing = completion?.missing_fields ?? [];

  // Open the section a missing field lives in and scroll its input into view.
  const goToField = (field: string) => {
    const sectionId = FIELD_SECTION[field];
    if (sectionId) {
      setOpenSections((prev) => new Set(prev).add(sectionId));
    }
    window.setTimeout(() => {
      const el = document.getElementById(field);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        (el as HTMLElement).focus({ preventScroll: true });
      }
    }, 50);
  };

  return (
    <div className="p-6">
      {/* The title is in the top bar; one line under it. */}
      <p className="text-gray-600 mb-6 max-w-[64ch]">What the plan is built from. Every answer here changes your steps and dates.</p>

      {/* Profile completion indicator */}
      <ProfileCompletionCard completion={completionPercentage} missing={missing} onGoTo={goToField} />

      {/* Sections */}
      <div className="space-y-4">
        {sections.map((section) => (
          <ProfileSectionCard
            key={section.id}
            section={section}
            profile={profile}
            isOpen={openSections.has(section.id)}
            onToggle={() => toggleSection(section.id)}
          />
        ))}
      </div>
    </div>
  );
}

/**
 * Profile completion progress card
 */
function ProfileCompletionCard({ completion, missing, onGoTo }: { completion: number; missing: string[]; onGoTo: (field: string) => void }) {
  return (
    <div className="card p-6 mb-6">
      <div className="flex items-center justify-between mb-2">
        <h2 className="text-lg font-semibold text-gray-900">Profile completion</h2>
        <span className="text-2xl font-bold text-primary-600" aria-label={`${completion}% complete`}>
          {completion}%
        </span>
      </div>
      <div
        className="w-full bg-gray-200 rounded-full h-3"
        role="progressbar"
        aria-valuenow={completion}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label="Profile completion progress"
      >
        <div
          className={clsx(
            'h-3 rounded-full transition-all duration-500',
            completion >= 80 ? 'bg-green-500' :
            completion >= 50 ? 'bg-primary-500' :
            'bg-yellow-500'
          )}
          style={{ width: `${completion}%` }}
        />
      </div>
      {missing.length === 0 ? (
        <p className="text-sm text-gray-600 mt-2">
          Everything we need is here. The rest of the profile is optional detail that sharpens your guides.
        </p>
      ) : (
        <div className="mt-3">
          <p className="text-sm text-gray-600 m-0">
            {missing.length === 1 ? 'One thing still to fill in:' : `${missing.length} things still to fill in:`}
          </p>
          <ul className="list-none m-0 p-0 mt-1.5 flex flex-wrap gap-2">
            {missing.map((field) => (
              <li key={field}>
                <button type="button" onClick={() => onGoTo(field)} className="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 hover:bg-amber-200 transition-colors whitespace-nowrap">
                  {FIELD_LABEL[field] ?? field.replace(/_/g, ' ')}
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}

/**
 * Collapsible profile section card
 */
function ProfileSectionCard({
  section,
  profile,
  isOpen,
  onToggle,
}: {
  section: ProfileSection;
  profile: MemberProfile | undefined;
  isOpen: boolean;
  onToggle: () => void;
}) {
  const Icon = section.icon;
  const Component = section.component;

  return (
    <div className="card overflow-hidden">
      {/* Section header - collapsible */}
      <button
        onClick={onToggle}
        className="w-full flex items-center justify-between p-6 hover:bg-gray-50 transition-colors"
        aria-expanded={isOpen}
        aria-controls={`section-${section.id}`}
      >
        <div className="flex items-center gap-3">
          <Icon className="w-5 h-5 text-primary-600" aria-hidden="true" />
          <h2 className="text-lg font-semibold text-gray-900">{section.label}</h2>
        </div>
        {isOpen ? (
          <ChevronUp className="w-5 h-5 text-gray-400" aria-hidden="true" />
        ) : (
          <ChevronDown className="w-5 h-5 text-gray-400" aria-hidden="true" />
        )}
      </button>

      {/* Section content */}
      {isOpen && (
        <div id={`section-${section.id}`} className="px-6 pb-6 border-t border-gray-100">
          <Component profile={profile} />
        </div>
      )}
    </div>
  );
}
