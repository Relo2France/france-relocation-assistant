import { useState } from 'react';
import { clsx } from 'clsx';
import {
  MoreVertical,
  Pin,
  Edit2,
  Trash2,
  Clock,
} from 'lucide-react';
import { useToggleNotePin, useDeleteNote, useUpdateNote } from '@/hooks/useApi';
import type { Note } from '@/types';

interface NoteCardProps {
  note: Note;
  projectId: number;
}

export default function NoteCard({ note, projectId }: NoteCardProps) {
  const [showMenu, setShowMenu] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editContent, setEditContent] = useState(note.content);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const togglePin = useToggleNotePin();
  const deleteNote = useDeleteNote();
  const updateNote = useUpdateNote();

  const handleTogglePin = () => {
    togglePin.mutate(note.id);
    setShowMenu(false);
  };

  const handleDelete = () => {
    deleteNote.mutate({ id: note.id, projectId });
    setShowDeleteConfirm(false);
  };

  const handleSaveEdit = () => {
    if (editContent.trim() && editContent !== note.content) {
      updateNote.mutate({ id: note.id, data: { content: editContent } });
    }
    setIsEditing(false);
  };

  const handleCancelEdit = () => {
    setEditContent(note.content);
    setIsEditing(false);
  };

  return (
    <div
      className={clsx(
        'card p-4 transition-all',
        note.is_pinned && 'ring-2 ring-yellow-400 bg-yellow-50'
      )}
    >
      {/* Header */}
      <div className="flex items-start justify-between gap-3 mb-3">
        <div className="flex items-center gap-3">
          {/* Avatar */}
          <img
            src={note.user_avatar}
            alt={note.user_name}
            className="w-10 h-10 rounded-full"
          />

          <div>
            <p className="font-medium text-gray-900">{note.user_name}</p>
            <div className="flex items-center gap-2 text-xs text-gray-500">
              <Clock className="w-3 h-3" />
              <span>{note.relative_time}</span>
              {note.is_pinned && (
                <span className="flex items-center gap-1 text-yellow-600">
                  <Pin className="w-3 h-3" />
                  Pinned
                </span>
              )}
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="relative">
          <button
            onClick={() => setShowMenu(!showMenu)}
            className="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
          >
            <MoreVertical className="w-4 h-4" />
          </button>

          {showMenu && (
            <div className="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
              <button
                onClick={handleTogglePin}
                className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
              >
                <Pin className="w-4 h-4" />
                {note.is_pinned ? 'Unpin' : 'Pin to top'}
              </button>
              <button
                onClick={() => { setIsEditing(true); setShowMenu(false); }}
                className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
              >
                <Edit2 className="w-4 h-4" />
                Edit
              </button>
              <hr className="my-1 border-gray-100" />
              <button
                onClick={() => { setShowDeleteConfirm(true); setShowMenu(false); }}
                className="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
              >
                <Trash2 className="w-4 h-4" />
                Delete
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      {isEditing ? (
        <div className="space-y-3">
          <textarea
            value={editContent}
            onChange={(e) => setEditContent(e.target.value)}
            className="textarea w-full"
            rows={4}
            autoFocus
          />
          <div className="flex items-center justify-end gap-2">
            <button
              onClick={handleCancelEdit}
              className="btn btn-secondary btn-sm"
            >
              Cancel
            </button>
            <button
              onClick={handleSaveEdit}
              disabled={updateNote.isPending}
              className="btn btn-primary btn-sm"
            >
              {updateNote.isPending ? 'Saving...' : 'Save'}
            </button>
          </div>
        </div>
      ) : (
        <div
          className="prose prose-sm max-w-none text-gray-700"
          dangerouslySetInnerHTML={{ __html: note.content }}
        />
      )}

      {/* Delete confirmation */}
      {showDeleteConfirm && (
        <div className="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-sm text-red-800 mb-3">
            Are you sure you want to delete this note? This action cannot be undone.
          </p>
          <div className="flex items-center justify-end gap-2">
            <button
              onClick={() => setShowDeleteConfirm(false)}
              className="btn btn-secondary btn-sm"
            >
              Cancel
            </button>
            <button
              onClick={handleDelete}
              disabled={deleteNote.isPending}
              className="btn bg-red-600 text-white hover:bg-red-700 btn-sm"
            >
              {deleteNote.isPending ? 'Deleting...' : 'Delete'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
