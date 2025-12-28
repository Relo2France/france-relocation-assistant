/**
 * SchengenOnboarding
 *
 * First-time user onboarding modal for the Schengen Tracker.
 * Walks users through the 90/180 rule and key features.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Globe,
  Calculator,
  Bell,
  MapPin,
  ChevronRight,
  ChevronLeft,
  Check,
  Plane,
} from 'lucide-react';
import Modal from '@/components/shared/Modal';

interface SchengenOnboardingProps {
  isOpen: boolean;
  onComplete: () => void;
  isPremium?: boolean;
}

interface OnboardingStep {
  icon: React.ReactNode;
  title: string;
  description: string;
  details: React.ReactNode;
}

const STORAGE_KEY = 'r2f_schengen_onboarding_complete';

/**
 * Check if user has completed onboarding
 */
export function hasCompletedOnboarding(): boolean {
  return localStorage.getItem(STORAGE_KEY) === 'true';
}

/**
 * Mark onboarding as complete
 */
export function markOnboardingComplete(): void {
  localStorage.setItem(STORAGE_KEY, 'true');
}

/**
 * Reset onboarding (for testing)
 */
export function resetOnboarding(): void {
  localStorage.removeItem(STORAGE_KEY);
}

export default function SchengenOnboarding({
  isOpen,
  onComplete,
  isPremium = false,
}: SchengenOnboardingProps) {
  const [currentStep, setCurrentStep] = useState(0);

  const steps: OnboardingStep[] = [
    {
      icon: <Globe className="w-8 h-8 text-primary-600" aria-hidden="true" />,
      title: 'Welcome to Schengen Tracker',
      description: 'Understand and comply with the 90/180 day rule',
      details: (
        <div className="space-y-4 text-left">
          <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 className="font-semibold text-blue-900 mb-2">The 90/180 Rule</h4>
            <p className="text-sm text-blue-800">
              Non-EU citizens may stay up to <strong>90 days</strong> within any{' '}
              <strong>180-day rolling period</strong> in the Schengen area without a long-stay visa.
            </p>
          </div>
          <div className="grid grid-cols-2 gap-3 text-sm">
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="font-medium text-gray-900">Entry & Exit</p>
              <p className="text-gray-600">Both days count as full days</p>
            </div>
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="font-medium text-gray-900">Rolling Window</p>
              <p className="text-gray-600">Days expire after 180 days</p>
            </div>
          </div>
          <p className="text-xs text-gray-500">
            Overstaying can result in fines, deportation, and future visa denials.
          </p>
        </div>
      ),
    },
    {
      icon: <Plane className="w-8 h-8 text-primary-600" aria-hidden="true" />,
      title: 'Track Your Trips',
      description: 'Add your past and future Schengen travel',
      details: (
        <div className="space-y-4 text-left">
          <p className="text-sm text-gray-600">
            Keep an accurate record of all your Schengen zone visits to stay compliant.
          </p>
          <div className="space-y-3">
            <div className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
              <div className="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span className="text-xs font-bold text-primary-700">1</span>
              </div>
              <div>
                <p className="font-medium text-gray-900">Click "Add Trip"</p>
                <p className="text-sm text-gray-600">Enter your travel dates and destination country</p>
              </div>
            </div>
            <div className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
              <div className="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span className="text-xs font-bold text-primary-700">2</span>
              </div>
              <div>
                <p className="font-medium text-gray-900">Add all past trips</p>
                <p className="text-sm text-gray-600">Include trips from the last 6 months for accurate tracking</p>
              </div>
            </div>
            <div className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
              <div className="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                <span className="text-xs font-bold text-primary-700">3</span>
              </div>
              <div>
                <p className="font-medium text-gray-900">Plan future trips</p>
                <p className="text-sm text-gray-600">Add upcoming travel to see projected compliance</p>
              </div>
            </div>
          </div>
        </div>
      ),
    },
    {
      icon: <MapPin className="w-8 h-8 text-primary-600" aria-hidden="true" />,
      title: 'Smart Location Check-in',
      description: 'Quick daily check-ins to keep your tracker current',
      details: (
        <div className="space-y-4 text-left">
          <p className="text-sm text-gray-600">
            Use the location check-in feature to automatically record your presence in Schengen countries.
          </p>
          <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div className="flex items-center gap-2 mb-2">
              <Check className="w-5 h-5 text-green-600" aria-hidden="true" />
              <h4 className="font-semibold text-green-900">Automatic Trip Creation</h4>
            </div>
            <p className="text-sm text-green-800">
              When you check in from a Schengen country, we automatically create or extend
              your trip record. No manual entry needed!
            </p>
          </div>
          <div className="grid grid-cols-2 gap-3 text-sm">
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="font-medium text-gray-900">Browser Location</p>
              <p className="text-gray-600">One-click check-in with GPS</p>
            </div>
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="font-medium text-gray-900">Smart Detection</p>
              <p className="text-gray-600">Prompts when timezone changes</p>
            </div>
          </div>
        </div>
      ),
    },
    {
      icon: <Calculator className="w-8 h-8 text-primary-600" aria-hidden="true" />,
      title: 'Plan Future Travel',
      description: 'Use the "What If" calculator before you book',
      details: (
        <div className="space-y-4 text-left">
          <p className="text-sm text-gray-600">
            Before booking a trip, use the Planning Tool to check if it would violate the 90/180 rule.
          </p>
          <div className="p-4 bg-primary-50 border border-primary-200 rounded-lg">
            <h4 className="font-semibold text-primary-900 mb-2">The Planning Tool shows you:</h4>
            <ul className="space-y-2 text-sm text-primary-800">
              <li className="flex items-center gap-2">
                <Check className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                Whether your planned trip would be safe
              </li>
              <li className="flex items-center gap-2">
                <Check className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                The earliest safe date to start your trip
              </li>
              <li className="flex items-center gap-2">
                <Check className="w-4 h-4 flex-shrink-0" aria-hidden="true" />
                Maximum safe trip length from any date
              </li>
            </ul>
          </div>
          {!isPremium && (
            <p className="text-xs text-amber-700 bg-amber-50 p-2 rounded">
              ⭐ Planning Tool is a premium feature
            </p>
          )}
        </div>
      ),
    },
    {
      icon: <Bell className="w-8 h-8 text-primary-600" aria-hidden="true" />,
      title: 'Stay Informed with Alerts',
      description: 'Get notified before you approach the limit',
      details: (
        <div className="space-y-4 text-left">
          <p className="text-sm text-gray-600">
            Enable email alerts to receive warnings when you're approaching your 90-day limit.
          </p>
          <div className="space-y-2">
            <div className="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
              <div className="w-3 h-3 rounded-full bg-yellow-500" />
              <div>
                <p className="font-medium text-yellow-900">60 days used</p>
                <p className="text-xs text-yellow-700">Friendly reminder to plan ahead</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-orange-50 border border-orange-200 rounded-lg">
              <div className="w-3 h-3 rounded-full bg-orange-500" />
              <div>
                <p className="font-medium text-orange-900">80 days used</p>
                <p className="text-xs text-orange-700">Warning to limit travel</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-red-50 border border-red-200 rounded-lg">
              <div className="w-3 h-3 rounded-full bg-red-500" />
              <div>
                <p className="font-medium text-red-900">85+ days used</p>
                <p className="text-xs text-red-700">Urgent alert to avoid overstay</p>
              </div>
            </div>
          </div>
          <p className="text-xs text-gray-500">
            You can enable alerts in the Settings tab after completing this tour.
          </p>
        </div>
      ),
    },
  ];

  const handleNext = () => {
    if (currentStep < steps.length - 1) {
      setCurrentStep(currentStep + 1);
    } else {
      markOnboardingComplete();
      onComplete();
    }
  };

  const handlePrevious = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleSkip = () => {
    markOnboardingComplete();
    onComplete();
  };

  const currentStepData = steps[currentStep];
  const isLastStep = currentStep === steps.length - 1;

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleSkip}
      title=""
      size="md"
    >
      <div className="text-center py-2">
        {/* Step icon */}
        <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-5">
          {currentStepData.icon}
        </div>

        {/* Step content */}
        <h2 className="text-xl font-bold text-gray-900 mb-2">
          {currentStepData.title}
        </h2>
        <p className="text-gray-600 mb-6">
          {currentStepData.description}
        </p>

        {/* Step details */}
        <div className="mb-8">
          {currentStepData.details}
        </div>

        {/* Progress dots */}
        <div className="flex justify-center gap-2 mb-6">
          {steps.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrentStep(index)}
              className={clsx(
                'w-2.5 h-2.5 rounded-full transition-all',
                index === currentStep
                  ? 'bg-primary-600 w-6'
                  : index < currentStep
                    ? 'bg-primary-300'
                    : 'bg-gray-200'
              )}
              aria-label={`Go to step ${index + 1}`}
            />
          ))}
        </div>

        {/* Navigation */}
        <div className="flex items-center justify-between pt-4 border-t border-gray-200">
          <button
            onClick={handleSkip}
            className="text-sm text-gray-500 hover:text-gray-700"
          >
            Skip tour
          </button>
          <div className="flex items-center gap-3">
            {currentStep > 0 && (
              <button
                onClick={handlePrevious}
                className="inline-flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
              >
                <ChevronLeft className="w-4 h-4" aria-hidden="true" />
                Back
              </button>
            )}
            <button
              onClick={handleNext}
              className="inline-flex items-center gap-1 px-6 py-2 bg-primary-600 text-white rounded-lg font-medium text-sm hover:bg-primary-700 transition-colors"
            >
              {isLastStep ? (
                <>
                  Get Started
                  <Check className="w-4 h-4" aria-hidden="true" />
                </>
              ) : (
                <>
                  Next
                  <ChevronRight className="w-4 h-4" aria-hidden="true" />
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </Modal>
  );
}
