/**
 * ProfileView Component
 *
 * Main profile page that displays collapsible sections for managing
 * personal, family, visa, location, and financial information.
 *
 * Refactored to use extracted sub-components for better maintainability.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  User,
  Users,
  Briefcase,
  MapPin,
  DollarSign,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import { useMemberProfile } from '@/hooks/useApi';
import PersonalSection from './PersonalSection';
import ApplicantSection from './ApplicantSection';
import VisaSection from './VisaSection';
import LocationSection from './LocationSection';
import FinancialSection from './FinancialSection';
import ProfileSkeleton from './ProfileSkeleton';
import type { MemberProfile } from '@/types';

type SectionId = 'personal' | 'applicant' | 'visa' | 'location' | 'financial';

interface ProfileSection {
  id: SectionId;
  label: string;
  icon: typeof User;
  component: React.ComponentType<{ profile: MemberProfile | undefined }>;
}

const sections: ProfileSection[] = [
  { id: 'personal', label: 'Personal Information', icon: User, component: PersonalSection },
  { id: 'applicant', label: 'Applicant & Family', icon: Users, component: ApplicantSection },
  { id: 'visa', label: 'Visa & Employment', icon: Briefcase, component: VisaSection },
  { id: 'location', label: 'Location Information', icon: MapPin, component: LocationSection },
  { id: 'financial', label: 'Financial Information', icon: DollarSign, component: FinancialSection },
];

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
      {profile && <ProfileCompletionCard profile={profile} />}

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
function ProfileCompletionCard({ profile }: { profile: MemberProfile }) {
  const completion = profile.profile_completion || 0;

  return (
    <div className="card p-6 mb-6">
      <div className="flex items-center justify-between mb-2">
        <h2 className="text-lg font-semibold text-gray-900">Profile Completion</h2>
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
      <p className="text-sm text-gray-600 mt-2">
        Complete your profile to help us provide better guidance for your relocation
      </p>
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
