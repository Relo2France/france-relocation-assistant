/**
 * Personalized Guide Detail Component
 *
 * Displays AI-generated personalized guides based on user profile.
 */

import {
  ChevronRight,
  Sparkles,
  Loader2,
  AlertCircle,
  BookOpen,
  MapPin,
  DollarSign,
  Clock,
  ExternalLink,
  Star,
} from 'lucide-react';
import {
  usePersonalizedGuide,
  useGenerateAIGuide,
  useMemberProfile,
} from '@/hooks/useApi';
import SafeHtml from '@/components/shared/SafeHtml';
import type { GuideType } from '@/types';

interface PersonalizedGuideDetailProps {
  guideType: GuideType;
  onBack: () => void;
}

export default function PersonalizedGuideDetail({ guideType, onBack }: PersonalizedGuideDetailProps) {
  const { data: guide, isLoading, error } = usePersonalizedGuide(guideType);
  const { data: _profile } = useMemberProfile();
  const generateGuide = useGenerateAIGuide();

  const handleGenerateGuide = () => {
    generateGuide.mutate(guideType);
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="card p-12 text-center">
          <AlertCircle className="w-12 h-12 text-red-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Failed to load guide</h3>
          <p className="text-gray-500 mb-4">
            We could not load your personalized guide. Please try again.
          </p>
          <button
            onClick={handleGenerateGuide}
            disabled={generateGuide.isPending}
            className="btn btn-primary"
          >
            {generateGuide.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Generating...
              </>
            ) : (
              <>
                <Sparkles className="w-4 h-4" />
                Generate AI Guide
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

  if (!guide) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="card p-12 text-center">
          <BookOpen className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Guide not available</h3>
          <p className="text-gray-500 mb-4">
            This personalized guide has not been generated yet.
          </p>
          <button
            onClick={handleGenerateGuide}
            disabled={generateGuide.isPending}
            className="btn btn-primary"
          >
            {generateGuide.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Generating...
              </>
            ) : (
              <>
                <Sparkles className="w-4 h-4" />
                Generate AI Guide
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Back button */}
      <button
        onClick={onBack}
        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
      >
        <ChevronRight className="w-4 h-4 rotate-180" />
        Back to guides
      </button>

      <div className="max-w-3xl">
        {/* Header */}
        <div className="card p-6 mb-6 bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
          <div className="flex items-start justify-between gap-4">
            <div className="flex items-start gap-4 flex-1">
              <div className="p-4 bg-white rounded-xl shadow-sm">
                <Sparkles className="w-8 h-8 text-purple-600" />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded-full">
                    Personalized
                  </span>
                  {guide.metadata?.last_updated && (
                    <span className="text-xs text-purple-600">
                      Updated {new Date(guide.metadata.last_updated).toLocaleDateString()}
                    </span>
                  )}
                </div>
                <h1 className="text-2xl font-bold text-gray-900 mb-2">{guide.title}</h1>
                <p className="text-gray-700">{guide.description}</p>
              </div>
            </div>
            {!guide.is_personalized && (
              <button
                onClick={handleGenerateGuide}
                disabled={generateGuide.isPending}
                className="btn btn-primary whitespace-nowrap"
              >
                {generateGuide.isPending ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Generating...
                  </>
                ) : (
                  <>
                    <Sparkles className="w-4 h-4" />
                    Regenerate
                  </>
                )}
              </button>
            )}
          </div>
        </div>

        {/* State-specific Apostille Information */}
        {guideType === 'apostille' && guide.metadata?.states && guide.metadata.states.length > 0 && (
          <div className="mb-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Your State-Specific Information
            </h2>
            <div className="space-y-4">
              {guide.metadata.states.map((state) => (
                <div key={state.state} className="card p-5">
                  <div className="flex items-start justify-between mb-4">
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <MapPin className="w-4 h-4 text-primary-600" />
                        <h3 className="font-semibold text-gray-900">{state.state_name}</h3>
                      </div>
                      <p className="text-sm text-gray-600">{state.document}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Agency</h4>
                      <p className="text-sm text-gray-900">{state.agency}</p>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Method</h4>
                      <p className="text-sm text-gray-900">{state.method}</p>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Cost</h4>
                      <div className="flex items-center gap-1">
                        <DollarSign className="w-3 h-3 text-green-600" />
                        <p className="text-sm text-gray-900">{state.cost}</p>
                      </div>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Processing Time</h4>
                      <div className="flex items-center gap-1">
                        <Clock className="w-3 h-3 text-blue-600" />
                        <p className="text-sm text-gray-900">{state.processing_time}</p>
                      </div>
                    </div>
                  </div>

                  {state.website && (
                    <a
                      href={state.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium"
                    >
                      Visit agency website
                      <ExternalLink className="w-3 h-3" />
                    </a>
                  )}

                  {state.notes && (
                    <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                      <p className="text-xs text-yellow-800">{state.notes}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Guide Sections */}
        {guide.sections && guide.sections.length > 0 && (
          <div className="space-y-6">
            {guide.sections.map((section, index) => (
              <div key={section.id} className="card p-6">
                <div className="flex items-start gap-3 mb-4">
                  <span className="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                    {index + 1}
                  </span>
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">
                      {section.title}
                    </h3>
                    <SafeHtml
                      html={section.content}
                      className="prose prose-sm max-w-none text-gray-700"
                    />

                    {section.tips && section.tips.length > 0 && (
                      <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 className="text-sm font-medium text-blue-900 mb-2 flex items-center gap-2">
                          <Star className="w-4 h-4" />
                          Tips
                        </h4>
                        <ul className="space-y-1">
                          {section.tips.map((tip, tipIndex) => (
                            <li key={tipIndex} className="text-sm text-blue-800">
                              {tip}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {section.warnings && section.warnings.length > 0 && (
                      <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 className="text-sm font-medium text-yellow-900 mb-2 flex items-center gap-2">
                          <AlertCircle className="w-4 h-4" />
                          Important Notes
                        </h4>
                        <ul className="space-y-1">
                          {section.warnings.map((warning, warnIndex) => (
                            <li key={warnIndex} className="text-sm text-yellow-800">
                              {warning}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
