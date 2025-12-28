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
      icon: <Globe className="w-10 h-10 text-primary-600" aria-hidden="true" />,
      title: 'Welcome to Schengen Tracker',
      description: 'Understand and comply with the 90/180 day rule',
      details: (
        <div className="space-y-6">
          <div className="p-5 bg-blue-50 border border-blue-100 rounded-xl">
            <h4 className="font-semibold text-blue-900 mb-3 text-base">The 90/180 Rule</h4>
            <p className="text-blue-800 leading-relaxed">
              Non-EU citizens may stay up to <strong>90 days</strong> within any{' '}
              <strong>180-day rolling period</strong> in the Schengen area without a long-stay visa.
            </p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-xl text-center">
              <p className="font-semibold text-gray-900 mb-1">Entry & Exit</p>
              <p className="text-sm text-gray-600">Both days count as full days</p>
            </div>
            <div className="p-4 bg-gray-50 rounded-xl text-center">
              <p className="font-semibold text-gray-900 mb-1">Rolling Window</p>
              <p className="text-sm text-gray-600">Days expire after 180 days</p>
            </div>
          </div>
          <p className="text-sm text-gray-500 text-center">
            Overstaying can result in fines, deportation, and future visa denials.
          </p>
        </div>
      ),
    },
    {
      icon: <Plane className="w-10 h-10 text-primary-600" aria-hidden="true" />,
      title: 'Track Your Trips',
      description: 'Add your past and future Schengen travel',
      details: (
        <div className="space-y-5">
          <p className="text-gray-600 text-center">
            Keep an accurate record of all your Schengen zone visits to stay compliant.
          </p>
          <div className="space-y-3">
            <div className="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
              <div className="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                <span className="text-sm font-bold text-white">1</span>
              </div>
              <div className="text-left">
                <p className="font-semibold text-gray-900">Click "Add Trip"</p>
                <p className="text-sm text-gray-600 mt-0.5">Enter your travel dates and destination country</p>
              </div>
            </div>
            <div className="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
              <div className="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                <span className="text-sm font-bold text-white">2</span>
              </div>
              <div className="text-left">
                <p className="font-semibold text-gray-900">Add all past trips</p>
                <p className="text-sm text-gray-600 mt-0.5">Include trips from the last 6 months for accurate tracking</p>
              </div>
            </div>
            <div className="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
              <div className="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                <span className="text-sm font-bold text-white">3</span>
              </div>
              <div className="text-left">
                <p className="font-semibold text-gray-900">Plan future trips</p>
                <p className="text-sm text-gray-600 mt-0.5">Add upcoming travel to see projected compliance</p>
              </div>
            </div>
          </div>
        </div>
      ),
    },
    {
      icon: <MapPin className="w-10 h-10 text-primary-600" aria-hidden="true" />,
      title: 'Smart Location Check-in',
      description: 'Quick daily check-ins keep your tracker current',
      details: (
        <div className="space-y-5">
          <p className="text-gray-600 text-center">
            Use location check-in to automatically record your presence in Schengen countries.
          </p>
          <div className="p-5 bg-green-50 border border-green-100 rounded-xl">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center">
                <Check className="w-5 h-5 text-white" aria-hidden="true" />
              </div>
              <h4 className="font-semibold text-green-900 text-base">Automatic Trip Creation</h4>
            </div>
            <p className="text-green-800 leading-relaxed">
              When you check in from a Schengen country, we automatically create or extend
              your trip record. No manual entry needed!
            </p>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-xl text-center">
              <p className="font-semibold text-gray-900 mb-1">Browser Location</p>
              <p className="text-sm text-gray-600">One-click check-in with GPS</p>
            </div>
            <div className="p-4 bg-gray-50 rounded-xl text-center">
              <p className="font-semibold text-gray-900 mb-1">Smart Detection</p>
              <p className="text-sm text-gray-600">Prompts when timezone changes</p>
            </div>
          </div>
        </div>
      ),
    },
    {
      icon: <Calculator className="w-10 h-10 text-primary-600" aria-hidden="true" />,
      title: 'Plan Future Travel',
      description: 'Use the "What If" calculator before you book',
      details: (
        <div className="space-y-5">
          <p className="text-gray-600 text-center">
            Before booking a trip, check if it would violate the 90/180 rule.
          </p>
          <div className="p-5 bg-primary-50 border border-primary-100 rounded-xl">
            <h4 className="font-semibold text-primary-900 mb-4 text-base">The Planning Tool shows you:</h4>
            <ul className="space-y-3">
              <li className="flex items-center gap-3 text-primary-800">
                <div className="w-6 h-6 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                  <Check className="w-4 h-4 text-white" aria-hidden="true" />
                </div>
                Whether your planned trip would be safe
              </li>
              <li className="flex items-center gap-3 text-primary-800">
                <div className="w-6 h-6 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                  <Check className="w-4 h-4 text-white" aria-hidden="true" />
                </div>
                The earliest safe date to start your trip
              </li>
              <li className="flex items-center gap-3 text-primary-800">
                <div className="w-6 h-6 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                  <Check className="w-4 h-4 text-white" aria-hidden="true" />
                </div>
                Maximum safe trip length from any date
              </li>
            </ul>
          </div>
          {!isPremium && (
            <p className="text-sm text-amber-700 bg-amber-50 border border-amber-100 p-3 rounded-xl text-center">
              ⭐ Planning Tool is a premium feature
            </p>
          )}
        </div>
      ),
    },
    {
      icon: <Bell className="w-10 h-10 text-primary-600" aria-hidden="true" />,
      title: 'Stay Informed with Alerts',
      description: 'Get notified before you approach the limit',
      details: (
        <div className="space-y-5">
          <p className="text-gray-600 text-center">
            Enable email alerts to receive warnings when approaching your 90-day limit.
          </p>
          <div className="space-y-3">
            <div className="flex items-center gap-4 p-4 bg-yellow-50 border border-yellow-100 rounded-xl">
              <div className="w-4 h-4 rounded-full bg-yellow-500 flex-shrink-0" />
              <div className="text-left">
                <p className="font-semibold text-yellow-900">60 days used</p>
                <p className="text-sm text-yellow-700">Friendly reminder to plan ahead</p>
              </div>
            </div>
            <div className="flex items-center gap-4 p-4 bg-orange-50 border border-orange-100 rounded-xl">
              <div className="w-4 h-4 rounded-full bg-orange-500 flex-shrink-0" />
              <div className="text-left">
                <p className="font-semibold text-orange-900">80 days used</p>
                <p className="text-sm text-orange-700">Warning to limit travel</p>
              </div>
            </div>
            <div className="flex items-center gap-4 p-4 bg-red-50 border border-red-100 rounded-xl">
              <div className="w-4 h-4 rounded-full bg-red-500 flex-shrink-0" />
              <div className="text-left">
                <p className="font-semibold text-red-900">85+ days used</p>
                <p className="text-sm text-red-700">Urgent alert to avoid overstay</p>
              </div>
            </div>
          </div>
          <p className="text-sm text-gray-500 text-center">
            Enable alerts in the Settings tab after completing this tour.
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
      size="lg"
    >
      <div className="px-4 py-6 sm:px-8 sm:py-8">
        {/* Step icon */}
        <div className="flex justify-center mb-6">
          <div className="w-20 h-20 bg-primary-50 rounded-2xl flex items-center justify-center">
            {currentStepData.icon}
          </div>
        </div>

        {/* Step content */}
        <div className="text-center mb-8">
          <h2 className="text-2xl font-bold text-gray-900 mb-3">
            {currentStepData.title}
          </h2>
          <p className="text-gray-500 text-lg">
            {currentStepData.description}
          </p>
        </div>

        {/* Step details */}
        <div className="mb-10 max-w-lg mx-auto">
          {currentStepData.details}
        </div>

        {/* Progress dots */}
        <div className="flex justify-center gap-2.5 mb-8">
          {steps.map((_, index) => (
            <button
              key={index}
              onClick={() => setCurrentStep(index)}
              className={clsx(
                'h-2.5 rounded-full transition-all duration-300',
                index === currentStep
                  ? 'bg-primary-600 w-8'
                  : index < currentStep
                    ? 'bg-primary-300 w-2.5'
                    : 'bg-gray-200 w-2.5'
              )}
              aria-label={`Go to step ${index + 1}`}
            />
          ))}
        </div>

        {/* Navigation */}
        <div className="flex items-center justify-between pt-6 border-t border-gray-100">
          <button
            onClick={handleSkip}
            className="text-sm text-gray-400 hover:text-gray-600 transition-colors"
          >
            Skip tour
          </button>
          <div className="flex items-center gap-3">
            {currentStep > 0 && (
              <button
                onClick={handlePrevious}
                className="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <ChevronLeft className="w-4 h-4" aria-hidden="true" />
                Back
              </button>
            )}
            <button
              onClick={handleNext}
              className="inline-flex items-center gap-1.5 px-6 py-2.5 bg-primary-600 text-white rounded-lg font-medium text-sm hover:bg-primary-700 transition-colors shadow-sm"
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
