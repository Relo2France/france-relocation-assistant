/**
 * Guides View
 *
 * Main view for browsing and accessing relocation guides.
 * Displays both static guides and personalized AI-generated guides.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  BookOpen,
  Search,
  Star,
  Sparkles,
  AlertCircle,
  FileText,
  Shield,
  PawPrint,
} from 'lucide-react';
import {
  useGuides,
  useMemberProfile,
  useProfileCompletion,
} from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import { GuideCard, FeaturedGuideCard, PersonalizedGuideCard } from './GuideCards';
import GuideDetail from './GuideDetail';
import PersonalizedGuideDetail from './PersonalizedGuideDetail';
import { guides, categories } from './guidesData';
import type { Guide } from './guidesData';
import type { GuideType } from '@/types';

export default function GuidesView() {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedGuide, setSelectedGuide] = useState<Guide | null>(null);
  const [selectedPersonalizedGuide, setSelectedPersonalizedGuide] = useState<GuideType | null>(null);

  // Store hook for navigation
  const { setActiveView } = usePortalStore();

  // API hooks
  const { data: _apiGuides, isLoading: _guidesLoading } = useGuides();
  const { data: profile } = useMemberProfile();
  const { data: profileCompletion } = useProfileCompletion();

  const filteredGuides = guides.filter((guide) => {
    const matchesSearch =
      searchQuery === '' ||
      guide.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      guide.description.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesCategory =
      selectedCategory === 'all' || guide.category === selectedCategory;

    return matchesSearch && matchesCategory;
  });

  const featuredGuides = guides.filter((g) => g.featured);

  // Determine which personalized guides to show based on profile
  const getPersonalizedGuides = (): { type: GuideType; title: string; description: string; icon: React.ElementType }[] => {
    if (!profile) return [];

    const personalizedGuides: { type: GuideType; title: string; description: string; icon: React.ElementType }[] = [];

    // Visa Application Guide (if they have a visa type)
    if (profile.visa_type && profile.visa_type !== 'undecided') {
      personalizedGuides.push({
        type: 'visa-application',
        title: 'Your Visa Application Guide',
        description: `Personalized guide for your ${profile.visa_type} visa application`,
        icon: FileText,
      });
    }

    // Apostille Guide (if they have US states for documents)
    if (profile.birth_state || profile.spouse_birth_state || profile.marriage_state) {
      personalizedGuides.push({
        type: 'apostille',
        title: 'Your Apostille Guide',
        description: 'State-specific apostille information for your documents',
        icon: Shield,
      });
    }

    // Pet Relocation Guide (if they have pets)
    if (profile.has_pets && profile.has_pets !== 'no') {
      personalizedGuides.push({
        type: 'pet-relocation',
        title: 'Your Pet Relocation Guide',
        description: `Personalized guide for relocating with ${profile.has_pets}`,
        icon: PawPrint,
      });
    }

    return personalizedGuides;
  };

  const personalizedGuides = getPersonalizedGuides();

  if (selectedPersonalizedGuide) {
    return (
      <PersonalizedGuideDetail
        guideType={selectedPersonalizedGuide}
        onBack={() => setSelectedPersonalizedGuide(null)}
      />
    );
  }

  if (selectedGuide) {
    return (
      <GuideDetail guide={selectedGuide} onBack={() => setSelectedGuide(null)} />
    );
  }

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Relocation Guides</h1>
        <p className="text-gray-600 mt-1">
          Comprehensive guides to help you navigate every aspect of moving to France
        </p>
      </div>

      {/* Personalized Guides Section */}
      {personalizedGuides.length > 0 && searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-purple-600" />
            Personalized for You
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {personalizedGuides.map((guide) => (
              <PersonalizedGuideCard
                key={guide.type}
                guide={guide}
                onClick={() => setSelectedPersonalizedGuide(guide.type)}
              />
            ))}
          </div>
        </div>
      )}

      {/* Profile Completion Notice */}
      {profileCompletion && profileCompletion.percentage < 50 && searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8 card p-4 bg-blue-50 border-blue-200">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-medium text-blue-900 mb-1">
                Complete your profile for personalized guides
              </h3>
              <p className="text-sm text-blue-700 mb-3">
                Fill out your profile to get customized guides based on your visa type, location, and personal circumstances.
                Your profile is currently {profileCompletion.percentage}% complete.
              </p>
              <button
                onClick={() => setActiveView('settings')}
                className="text-sm font-medium text-blue-600 hover:text-blue-700 underline"
              >
                Complete your profile
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Search and filters */}
      <div className="mb-6 flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search guides..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      {/* Category pills */}
      <div className="mb-8 flex flex-wrap gap-2">
        {categories.map((category) => (
          <button
            key={category.id}
            onClick={() => setSelectedCategory(category.id)}
            className={clsx(
              'px-4 py-2 rounded-full text-sm font-medium transition-colors',
              selectedCategory === category.id
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            )}
          >
            {category.label}
          </button>
        ))}
      </div>

      {/* Featured section (only show when no search/filter) */}
      {searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Star className="w-5 h-5 text-yellow-500" />
            Featured Guides
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {featuredGuides.map((guide) => (
              <FeaturedGuideCard
                key={guide.id}
                guide={guide}
                onClick={() => setSelectedGuide(guide)}
              />
            ))}
          </div>
        </div>
      )}

      {/* All guides */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <BookOpen className="w-5 h-5 text-primary-600" />
          {selectedCategory === 'all' ? 'All Guides' : selectedCategory}
          <span className="text-sm font-normal text-gray-500">
            ({filteredGuides.length})
          </span>
        </h2>

        {filteredGuides.length === 0 ? (
          <div className="card p-12 text-center">
            <BookOpen className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No guides found</h3>
            <p className="text-gray-500">
              Try adjusting your search or filter criteria
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredGuides.map((guide) => (
              <GuideCard
                key={guide.id}
                guide={guide}
                onClick={() => setSelectedGuide(guide)}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
