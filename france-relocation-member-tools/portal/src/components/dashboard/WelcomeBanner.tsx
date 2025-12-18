import { useState } from 'react';
import { X, Sparkles } from 'lucide-react';
import { useDismissWelcomeBanner } from '@/hooks/useApi';
import type { WelcomeBanner as WelcomeBannerType } from '@/types';

interface WelcomeBannerProps {
  banner: WelcomeBannerType;
}

export default function WelcomeBanner({ banner }: WelcomeBannerProps) {
  const [isVisible, setIsVisible] = useState(true);
  const dismissBanner = useDismissWelcomeBanner();

  const handleDismiss = async () => {
    setIsVisible(false);
    try {
      await dismissBanner.mutateAsync();
    } catch (error) {
      // Banner is already hidden locally, so we don't need to show it again
      console.error('Failed to dismiss banner:', error);
    }
  };

  if (!isVisible) {
    return null;
  }

  return (
    <div
      className="rounded-lg border-2 p-4 md:p-5 mb-6"
      style={{
        backgroundColor: banner.bg_color,
        borderColor: banner.border_color,
      }}
      role="region"
      aria-label="Welcome message"
    >
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <div
            className="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
            style={{ backgroundColor: banner.border_color }}
          >
            <Sparkles className="w-5 h-5 text-white" />
          </div>
          <div className="flex-1 min-w-0">
            <h2 className="text-lg font-semibold text-gray-900 mb-1">
              {banner.title}
            </h2>
            <p className="text-gray-700 leading-relaxed whitespace-pre-line">
              {banner.message}
            </p>
          </div>
        </div>
        <button
          onClick={handleDismiss}
          className="flex-shrink-0 p-1.5 rounded-full hover:bg-black/10 transition-colors"
          aria-label="Dismiss welcome message"
          disabled={dismissBanner.isPending}
        >
          <X className="w-5 h-5 text-gray-600" />
        </button>
      </div>
    </div>
  );
}
