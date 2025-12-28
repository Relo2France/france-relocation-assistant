/**
 * PWAPrompt
 *
 * Displays install prompt and update notifications for the PWA.
 */

import { useState, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  Download,
  X,
  RefreshCw,
  Smartphone,
  WifiOff,
} from 'lucide-react';
import { usePWA } from '@/hooks/usePWA';

interface PWAPromptProps {
  className?: string;
}

export default function PWAPrompt({ className }: PWAPromptProps) {
  const {
    isInstallable,
    isInstalled,
    isOffline,
    hasUpdate,
    promptInstall,
    updateApp,
    dismissUpdate,
  } = usePWA();

  const [showInstallPrompt, setShowInstallPrompt] = useState(false);
  const [dismissed, setDismissed] = useState(false);

  // Show install prompt after a delay if installable
  useEffect(() => {
    if (isInstallable && !dismissed) {
      const timer = setTimeout(() => {
        setShowInstallPrompt(true);
      }, 30000); // Show after 30 seconds

      return () => clearTimeout(timer);
    }
  }, [isInstallable, dismissed]);

  // Check if user has previously dismissed
  useEffect(() => {
    const dismissedTime = localStorage.getItem('pwa-install-dismissed');
    if (dismissedTime) {
      const dismissedDate = new Date(parseInt(dismissedTime, 10));
      const daysSinceDismissed = (Date.now() - dismissedDate.getTime()) / (1000 * 60 * 60 * 24);
      // Show again after 7 days
      if (daysSinceDismissed < 7) {
        setDismissed(true);
      }
    }
  }, []);

  const handleInstall = async () => {
    const installed = await promptInstall();
    if (installed) {
      setShowInstallPrompt(false);
    }
  };

  const handleDismiss = () => {
    setShowInstallPrompt(false);
    setDismissed(true);
    localStorage.setItem('pwa-install-dismissed', Date.now().toString());
  };

  // Offline banner
  if (isOffline) {
    return (
      <div
        className={clsx(
          'fixed bottom-0 left-0 right-0 z-50 bg-yellow-500 text-white px-4 py-2 flex items-center justify-center gap-2 text-sm',
          className
        )}
      >
        <WifiOff className="w-4 h-4" aria-hidden="true" />
        <span>You're offline. Some features may be unavailable.</span>
      </div>
    );
  }

  // Update banner
  if (hasUpdate) {
    return (
      <div
        className={clsx(
          'fixed bottom-0 left-0 right-0 z-50 bg-primary-600 text-white px-4 py-3',
          className
        )}
      >
        <div className="max-w-3xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-3">
            <RefreshCw className="w-5 h-5" aria-hidden="true" />
            <p className="text-sm font-medium">
              A new version is available!
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={dismissUpdate}
              className="px-3 py-1 text-sm text-white/80 hover:text-white"
            >
              Later
            </button>
            <button
              onClick={updateApp}
              className="px-4 py-1 bg-white text-primary-600 rounded-lg text-sm font-medium hover:bg-gray-100"
            >
              Update Now
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Install prompt
  if (showInstallPrompt && isInstallable && !isInstalled) {
    return (
      <div
        className={clsx(
          'fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 z-50 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden',
          className
        )}
      >
        <div className="p-4">
          <div className="flex items-start gap-3">
            <div className="p-2 bg-primary-100 rounded-lg">
              <Smartphone className="w-6 h-6 text-primary-600" aria-hidden="true" />
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-gray-900">Install Relo2France</h3>
              <p className="text-sm text-gray-600 mt-1">
                Install our app for quick access, offline support, and push notifications.
              </p>
            </div>
            <button
              onClick={handleDismiss}
              className="p-1 text-gray-400 hover:text-gray-600"
              aria-label="Dismiss"
            >
              <X className="w-5 h-5" aria-hidden="true" />
            </button>
          </div>
          <div className="flex items-center gap-2 mt-4">
            <button
              onClick={handleDismiss}
              className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Not now
            </button>
            <button
              onClick={handleInstall}
              className="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2"
            >
              <Download className="w-4 h-4" aria-hidden="true" />
              Install
            </button>
          </div>
        </div>
      </div>
    );
  }

  return null;
}
