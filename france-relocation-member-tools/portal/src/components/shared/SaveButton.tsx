/**
 * SaveButton Component
 *
 * A reusable save button with loading, success, and error states.
 * Used across profile sections and other forms.
 */

import { Save, Check, Loader2 } from 'lucide-react';

interface SaveButtonProps {
  isPending: boolean;
  isSuccess: boolean;
  isError: boolean;
  errorMessage?: string;
  label?: string;
  pendingLabel?: string;
  successLabel?: string;
}

export default function SaveButton({
  isPending,
  isSuccess,
  isError,
  errorMessage = 'Failed to save. Please try again.',
  label = 'Save Changes',
  pendingLabel = 'Saving...',
  successLabel = 'Saved!',
}: SaveButtonProps) {
  return (
    <div className="flex items-center gap-3 pt-4 border-t border-gray-200">
      <button
        type="submit"
        disabled={isPending}
        className="btn btn-primary flex items-center gap-2"
        aria-busy={isPending}
        aria-live="polite"
      >
        {isPending ? (
          <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
        ) : isSuccess ? (
          <Check className="w-4 h-4" aria-hidden="true" />
        ) : (
          <Save className="w-4 h-4" aria-hidden="true" />
        )}
        {isPending ? pendingLabel : isSuccess ? successLabel : label}
      </button>
      {isError && (
        <span className="text-sm text-red-600" role="alert">
          {errorMessage}
        </span>
      )}
    </div>
  );
}
