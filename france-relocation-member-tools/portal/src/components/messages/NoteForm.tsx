import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import { Send, X } from 'lucide-react';
import { useCreateNote } from '@/hooks/useApi';
import { usePortalStore } from '@/store';

interface NoteFormProps {
  projectId: number;
  taskId?: number;
  onSuccess?: () => void;
  placeholder?: string;
  className?: string;
}

export default function NoteForm({
  projectId,
  taskId,
  onSuccess,
  placeholder = 'Add a note...',
  className,
}: NoteFormProps) {
  const [content, setContent] = useState('');
  const [isExpanded, setIsExpanded] = useState(false);
  const textareaRef = useRef<HTMLTextAreaElement>(null);
  const { user } = usePortalStore();
  const createNote = useCreateNote(projectId);

  // Focus textarea when expanded
  useEffect(() => {
    if (isExpanded && textareaRef.current) {
      textareaRef.current.focus();
    }
  }, [isExpanded]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim()) return;

    createNote.mutate(
      {
        content: content.trim(),
        task_id: taskId,
        visibility: 'private',
      },
      {
        onSuccess: () => {
          setContent('');
          setIsExpanded(false);
          onSuccess?.();
        },
      }
    );
  };

  const handleCancel = () => {
    setContent('');
    setIsExpanded(false);
  };

  return (
    <form onSubmit={handleSubmit} className={clsx('card', className)}>
      <div className="p-4">
        <div className="flex gap-3">
          {/* Avatar */}
          {user && (
            <img
              src={user.avatar_url}
              alt={user.display_name}
              className="w-10 h-10 rounded-full flex-shrink-0"
            />
          )}

          {/* Input area */}
          <div className="flex-1">
            {isExpanded ? (
              <textarea
                ref={textareaRef}
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder={placeholder}
                className="textarea w-full resize-none"
                rows={4}
              />
            ) : (
              <button
                type="button"
                onClick={() => setIsExpanded(true)}
                className="w-full text-left px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-500 transition-colors"
              >
                {placeholder}
              </button>
            )}

            {/* Actions */}
            {isExpanded && (
              <div className="flex items-center justify-between mt-3">
                <p className="text-xs text-gray-500">
                  Notes are visible only to you
                </p>

                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={handleCancel}
                    className="btn btn-secondary btn-sm"
                  >
                    <X className="w-4 h-4" />
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={!content.trim() || createNote.isPending}
                    className="btn btn-primary btn-sm"
                  >
                    <Send className="w-4 h-4" />
                    {createNote.isPending ? 'Posting...' : 'Post Note'}
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </form>
  );
}
