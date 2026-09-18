/**
 * Toaster
 *
 * One small notice at the foot of the screen for things that happened
 * somewhere else on the page: a save that failed, mostly. Any code can raise
 * one with notify(); the query client raises one for every failed mutation
 * that does not handle its own error, so a failed delete or reply is never
 * silent.
 */
import { useEffect, useState } from 'react';
import { clsx } from 'clsx';
import { AlertTriangle, CheckCircle2, X } from 'lucide-react';

type Tone = 'error' | 'success';
interface Toast { id: number; tone: Tone; text: string }

const EVENT = 'framt:toast';
let counter = 0;

export function notify(text: string, tone: Tone = 'error'): void {
  window.dispatchEvent(new CustomEvent<Toast>(EVENT, { detail: { id: ++counter, tone, text } }));
}

export default function Toaster() {
  const [toasts, setToasts] = useState<Toast[]>([]);

  useEffect(() => {
    const onToast = (e: Event) => {
      const t = (e as CustomEvent<Toast>).detail;
      setToasts((prev) => [...prev.filter((x) => x.text !== t.text), t].slice(-3));
      window.setTimeout(() => setToasts((prev) => prev.filter((x) => x.id !== t.id)), 7000);
    };
    window.addEventListener(EVENT, onToast);
    return () => window.removeEventListener(EVENT, onToast);
  }, []);

  if (toasts.length === 0) return null;
  return (
    <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm" role="status" aria-live="polite">
      {toasts.map((t) => (
        <div
          key={t.id}
          className={clsx(
            'flex items-start gap-2.5 rounded-lg border px-4 py-3 shadow-sm bg-card text-sm',
            t.tone === 'error' ? 'border-red-200' : 'border-primary-100'
          )}
        >
          {t.tone === 'error'
            ? <AlertTriangle className="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5" aria-hidden="true" />
            : <CheckCircle2 className="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" aria-hidden="true" />}
          <span className="flex-1 text-ink">{t.text}</span>
          <button
            onClick={() => setToasts((prev) => prev.filter((x) => x.id !== t.id))}
            className="text-gray-400 hover:text-gray-700"
            aria-label="Dismiss notice"
          >
            <X className="w-4 h-4" aria-hidden="true" />
          </button>
        </div>
      ))}
    </div>
  );
}
