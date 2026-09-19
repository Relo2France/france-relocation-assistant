/**
 * WelcomeBanner
 *
 * The first thing a new member sees: one sentence on where to begin and one
 * button that goes there. The title and message are editable in the portal
 * settings; the look is the portal's own, so it cannot drift from it.
 */
import { useState } from 'react';
import { ArrowRight, X } from 'lucide-react';
import { useDismissWelcomeBanner } from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { WelcomeBanner as WelcomeBannerType } from '@/types';

interface WelcomeBannerProps {
  banner: WelcomeBannerType;
}

/** The shipped title, in the old Title Case, replaced where it was never edited. */
const OLD_TITLE = 'Welcome to Your Relocation Portal!';

export default function WelcomeBanner({ banner }: WelcomeBannerProps) {
  const [isVisible, setIsVisible] = useState(true);
  const dismissBanner = useDismissWelcomeBanner();
  const setActiveView = usePortalStore((state) => state.setActiveView);
  const setActiveStage = usePortalStore((state) => state.setActiveStage);

  const handleDismiss = async () => {
    setIsVisible(false);
    try {
      await dismissBanner.mutateAsync();
    } catch (error) {
      // Banner is already hidden locally, so we don't need to show it again
      console.error('Failed to dismiss banner:', error);
    }
  };

  const startWithDecide = () => {
    setActiveStage('decide');
    setActiveView('stage');
  };

  if (!isVisible) {
    return null;
  }

  const title = !banner.title || banner.title === OLD_TITLE ? 'Welcome to your file' : banner.title;

  return (
    <section className="card p-5 md:p-6" aria-label="Welcome message">
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 min-w-0">
          <h2 className="font-display text-[1.25rem] font-semibold tracking-[-0.018em] leading-snug m-0">
            {title}
          </h2>
          <p className="text-gray-700 leading-relaxed whitespace-pre-line mt-2 mb-4">
            {banner.message}
          </p>
          <button type="button" onClick={startWithDecide} className="btn btn-primary">
            Start with Decide
            <ArrowRight className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
        <button
          type="button"
          onClick={handleDismiss}
          className="flex-shrink-0 p-1.5 rounded-full hover:bg-black/5 transition-colors"
          aria-label="Dismiss welcome message"
          disabled={dismissBanner.isPending}
        >
          <X className="w-5 h-5 text-gray-500" aria-hidden="true" />
        </button>
      </div>
    </section>
  );
}
