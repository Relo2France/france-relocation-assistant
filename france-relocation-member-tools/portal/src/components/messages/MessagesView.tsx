import { useState } from 'react';
import { clsx } from 'clsx';
import {
  MessageSquare,
  Pin,
  Search,
  StickyNote,
} from 'lucide-react';
import { useDashboard, useNotes } from '@/hooks/useApi';
import NoteCard from './NoteCard';
import NoteForm from './NoteForm';

type FilterType = 'all' | 'pinned';

export default function MessagesView() {
  const [filter, setFilter] = useState<FilterType>('all');
  const [searchQuery, setSearchQuery] = useState('');

  const { data: dashboard, isLoading: dashboardLoading } = useDashboard();
  const projectId = dashboard?.project?.id || 0;

  const { data: notes = [], isLoading: notesLoading } = useNotes(
    projectId,
    filter === 'pinned' ? { pinned: true } : undefined
  );

  // Filter notes by search
  const filteredNotes = searchQuery
    ? notes.filter((note) =>
        note.content.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : notes;

  // Separate pinned and unpinned
  const pinnedNotes = filteredNotes.filter((note) => note.is_pinned);
  const unpinnedNotes = filteredNotes.filter((note) => !note.is_pinned);

  if (dashboardLoading || notesLoading) {
    return <MessagesViewSkeleton />;
  }

  if (!dashboard?.project) {
    return (
      <div className="p-6">
        <div className="card p-8 text-center">
          <h2 className="text-lg font-semibold text-gray-900 mb-2">No Project Found</h2>
          <p className="text-gray-600">Please set up your relocation project first.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Notes & Messages</h1>
        <p className="text-gray-600 mt-1">
          Keep track of important information and thoughts
        </p>
      </div>

      {/* Stats cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
              <StickyNote className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{notes.length}</p>
              <p className="text-sm text-gray-600">Total Notes</p>
            </div>
          </div>
        </div>

        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
              <Pin className="w-5 h-5 text-yellow-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{pinnedNotes.length}</p>
              <p className="text-sm text-gray-600">Pinned</p>
            </div>
          </div>
        </div>
      </div>

      {/* New note form */}
      <NoteForm
        projectId={projectId}
        className="mb-6"
        placeholder="Write a new note..."
      />

      {/* Filters and search */}
      <div className="card mb-6">
        <div className="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          {/* Filter tabs */}
          <div className="flex rounded-lg bg-gray-100 p-1">
            <button
              onClick={() => setFilter('all')}
              className={clsx(
                'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                filter === 'all'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              All Notes
            </button>
            <button
              onClick={() => setFilter('pinned')}
              className={clsx(
                'px-4 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                filter === 'pinned'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              )}
            >
              <Pin className="w-3.5 h-3.5" />
              Pinned
            </button>
          </div>

          {/* Search */}
          <div className="relative w-full sm:w-64">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search notes..."
              className="w-full pl-9 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
        </div>
      </div>

      {/* Notes list */}
      {filteredNotes.length === 0 ? (
        <div className="card p-12 text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <MessageSquare className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            {searchQuery ? 'No notes found' : 'No notes yet'}
          </h3>
          <p className="text-gray-600">
            {searchQuery
              ? 'Try a different search term'
              : 'Start by adding a note above to keep track of important information'}
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {/* Pinned section */}
          {filter === 'all' && pinnedNotes.length > 0 && (
            <div>
              <h3 className="text-sm font-medium text-gray-500 mb-3 flex items-center gap-2">
                <Pin className="w-4 h-4" />
                Pinned Notes
              </h3>
              <div className="space-y-4">
                {pinnedNotes.map((note) => (
                  <NoteCard key={note.id} note={note} projectId={projectId} />
                ))}
              </div>
            </div>
          )}

          {/* All/unpinned notes */}
          {filter === 'all' && unpinnedNotes.length > 0 && (
            <div>
              {pinnedNotes.length > 0 && (
                <h3 className="text-sm font-medium text-gray-500 mb-3 mt-6">
                  Other Notes
                </h3>
              )}
              <div className="space-y-4">
                {unpinnedNotes.map((note) => (
                  <NoteCard key={note.id} note={note} projectId={projectId} />
                ))}
              </div>
            </div>
          )}

          {/* Pinned filter view */}
          {filter === 'pinned' && (
            <div className="space-y-4">
              {filteredNotes.map((note) => (
                <NoteCard key={note.id} note={note} projectId={projectId} />
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

function MessagesViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 w-64" />

      {/* Stats skeleton */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {[1, 2].map((i) => (
          <div key={i} className="card p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-gray-200 rounded-lg animate-pulse" />
              <div className="space-y-2">
                <div className="h-6 w-8 bg-gray-200 rounded animate-pulse" />
                <div className="h-4 w-16 bg-gray-100 rounded animate-pulse" />
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Form skeleton */}
      <div className="card p-4 mb-6">
        <div className="flex gap-3">
          <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse" />
          <div className="flex-1 h-12 bg-gray-100 rounded-lg animate-pulse" />
        </div>
      </div>

      {/* Notes skeleton */}
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="card p-4">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse" />
              <div className="space-y-2">
                <div className="h-4 w-32 bg-gray-200 rounded animate-pulse" />
                <div className="h-3 w-24 bg-gray-100 rounded animate-pulse" />
              </div>
            </div>
            <div className="space-y-2">
              <div className="h-4 bg-gray-100 rounded animate-pulse" />
              <div className="h-4 bg-gray-100 rounded animate-pulse w-3/4" />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
