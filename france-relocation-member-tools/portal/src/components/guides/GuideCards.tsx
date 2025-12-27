/**
 * Guide Card Components
 *
 * Card components for displaying guides in the guides list view.
 */

import { clsx } from 'clsx';
import { Clock, ChevronRight, ArrowRight, Sparkles } from 'lucide-react';
import { difficultyColors } from './guidesData';
import type { Guide } from './guidesData';
import type { GuideType } from '@/types';

interface GuideCardProps {
  guide: Guide;
  onClick: () => void;
}

export function GuideCard({ guide, onClick }: GuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-5 text-left hover:shadow-md transition-shadow group"
    >
      <div className="flex items-start gap-4">
        <div className="p-3 bg-primary-50 rounded-lg group-hover:bg-primary-100 transition-colors">
          <Icon className="w-6 h-6 text-primary-600" />
        </div>
        <div className="flex-1 min-w-0">
          <h3 className="font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
            {guide.title}
          </h3>
          <p className="text-sm text-gray-600 line-clamp-2 mb-3">
            {guide.description}
          </p>
          <div className="flex items-center gap-3 text-xs">
            <span className="flex items-center gap-1 text-gray-500">
              <Clock className="w-3 h-3" />
              {guide.readTime}
            </span>
            <span className={clsx('px-2 py-0.5 rounded-full', difficultyColors[guide.difficulty])}>
              {guide.difficulty}
            </span>
          </div>
        </div>
        <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-primary-600 transition-colors" />
      </div>
    </button>
  );
}

export function FeaturedGuideCard({ guide, onClick }: GuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-6 text-left bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200 hover:shadow-md transition-shadow group"
    >
      <div className="flex items-center gap-2 mb-3">
        <div className="p-2 bg-white rounded-lg shadow-sm">
          <Icon className="w-5 h-5 text-primary-600" />
        </div>
        <span className="text-xs font-medium text-primary-600">{guide.category}</span>
      </div>
      <h3 className="font-semibold text-gray-900 mb-2 group-hover:text-primary-700 transition-colors">
        {guide.title}
      </h3>
      <p className="text-sm text-gray-600 line-clamp-2 mb-4">
        {guide.description}
      </p>
      <div className="flex items-center justify-between">
        <span className="flex items-center gap-1 text-xs text-gray-500">
          <Clock className="w-3 h-3" />
          {guide.readTime}
        </span>
        <span className="flex items-center gap-1 text-sm font-medium text-primary-600 group-hover:gap-2 transition-all">
          Read guide
          <ArrowRight className="w-4 h-4" />
        </span>
      </div>
    </button>
  );
}

interface PersonalizedGuideCardProps {
  guide: {
    type: GuideType;
    title: string;
    description: string;
    icon: React.ElementType;
  };
  onClick: () => void;
}

export function PersonalizedGuideCard({ guide, onClick }: PersonalizedGuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-6 text-left bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-md transition-shadow group"
    >
      <div className="flex items-start justify-between mb-3">
        <div className="p-2 bg-white rounded-lg shadow-sm">
          <Icon className="w-5 h-5 text-purple-600" />
        </div>
        <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded-full flex items-center gap-1">
          <Sparkles className="w-3 h-3" />
          Personalized
        </span>
      </div>
      <h3 className="font-semibold text-gray-900 mb-2 group-hover:text-purple-700 transition-colors">
        {guide.title}
      </h3>
      <p className="text-sm text-gray-600 line-clamp-2 mb-4">
        {guide.description}
      </p>
      <div className="flex items-center justify-between">
        <span className="text-xs text-purple-600 font-medium">
          Based on your profile
        </span>
        <span className="flex items-center gap-1 text-sm font-medium text-purple-600 group-hover:gap-2 transition-all">
          View guide
          <ArrowRight className="w-4 h-4" />
        </span>
      </div>
    </button>
  );
}
