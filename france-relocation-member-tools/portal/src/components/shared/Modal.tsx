import { useEffect, useId, useRef } from 'react';
import type { RefObject } from 'react';
import { clsx } from 'clsx';
import { X } from 'lucide-react';

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * What every dialog here shares: Escape closes it, the page behind stops
 * scrolling, focus moves into the dialog on open and goes back to whatever
 * opened it on close.
 *
 * onClose is read through a ref so a parent passing a fresh arrow function
 * each render does not re-run the effect (which would bounce focus).
 */
/** Open dialogs, newest last: Escape closes only the one on top. */
const openDialogs: symbol[] = [];

function useDialog(isOpen: boolean, onClose: () => void, panelRef: RefObject<HTMLDivElement>) {
  const onCloseRef = useRef(onClose);
  onCloseRef.current = onClose;

  useEffect(() => {
    if (!isOpen) return undefined;

    const opener = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    // A child may already have focused its own field (a form's first input);
    // only step in when focus is still outside the dialog.
    const panel = panelRef.current;
    if (panel && !panel.contains(document.activeElement)) {
      const first = panel.querySelector<HTMLElement>(FOCUSABLE);
      (first ?? panel).focus();
    }

    const token = Symbol('dialog');
    openDialogs.push(token);
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && openDialogs[openDialogs.length - 1] === token) {
        onCloseRef.current();
      }
    };
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      const at = openDialogs.indexOf(token);
      if (at >= 0) openDialogs.splice(at, 1);
      document.body.style.overflow = previousOverflow;
      if (opener && document.contains(opener)) {
        opener.focus();
      }
    };
  }, [isOpen, panelRef]);
}

interface ModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  children: React.ReactNode;
  footer?: React.ReactNode;
  showHeader?: boolean;
}

const sizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-[90vw]',
};

export default function Modal({
  isOpen,
  onClose,
  title,
  size = 'md',
  children,
  footer,
  showHeader = true,
}: ModalProps) {
  const overlayRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const titleId = useId();
  const hasTitle = showHeader && !!title;

  useDialog(isOpen, onClose, panelRef);

  // Handle click outside
  const handleOverlayClick = (e: React.MouseEvent) => {
    if (e.target === overlayRef.current) {
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div
      ref={overlayRef}
      role="presentation"
      onClick={handleOverlayClick}
      className="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 bg-black/50 animate-in fade-in duration-200"
    >
      {/* The panel never outgrows the screen: the body scrolls and the footer stays reachable. */}
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby={hasTitle ? titleId : undefined}
        aria-label={!hasTitle && title ? title : undefined}
        tabIndex={-1}
        className={clsx(
          'w-full flex flex-col max-h-[calc(100dvh-2rem)] bg-white rounded-xl shadow-xl animate-in zoom-in-95 duration-200 focus:outline-none',
          sizeClasses[size]
        )}
      >
        {/* Header */}
        {hasTitle && (
          <div className="flex shrink-0 items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id={titleId} className="text-lg font-semibold text-gray-900">{title}</h2>
            <button
              type="button"
              onClick={onClose}
              className="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
              aria-label="Close"
            >
              <X className="w-5 h-5" aria-hidden="true" />
            </button>
          </div>
        )}

        {/* Body */}
        <div className={clsx('min-h-0 flex-1 overflow-y-auto', hasTitle ? 'px-6 py-4' : 'relative')}>
          {children}
        </div>

        {/* Footer */}
        {footer && (
          <div className="flex shrink-0 flex-wrap items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}

// Slide-over drawer variant
interface DrawerProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  side?: 'left' | 'right';
  width?: 'sm' | 'md' | 'lg';
}

const drawerWidths = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
};

export function Drawer({
  isOpen,
  onClose,
  title,
  children,
  footer,
  side = 'right',
  width = 'md',
}: DrawerProps) {
  const overlayRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const titleId = useId();

  useDialog(isOpen, onClose, panelRef);

  const handleOverlayClick = (e: React.MouseEvent) => {
    if (e.target === overlayRef.current) {
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div
      ref={overlayRef}
      role="presentation"
      onClick={handleOverlayClick}
      className="fixed inset-0 z-50 bg-black/50 animate-in fade-in duration-200"
    >
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        tabIndex={-1}
        className={clsx(
          'fixed inset-y-0 w-full bg-white shadow-xl flex flex-col focus:outline-none',
          drawerWidths[width],
          side === 'right' ? 'right-0 animate-in slide-in-from-right' : 'left-0 animate-in slide-in-from-left',
          'duration-300'
        )}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 id={titleId} className="text-lg font-semibold text-gray-900">{title || 'Details'}</h2>
          <button
            type="button"
            onClick={onClose}
            className="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
            aria-label="Close"
          >
            <X className="w-5 h-5" aria-hidden="true" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-6 py-4">
          {children}
        </div>

        {/* Footer */}
        {footer && (
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            {footer}
          </div>
        )}
      </div>
    </div>
  );
}
