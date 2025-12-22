import { useState } from 'react';
import { clsx } from 'clsx';
import {
  MessageSquare,
  Pin,
  Search,
  StickyNote,
  Plus,
  ArrowLeft,
  Send,
  Clock,
  CheckCircle2,
  AlertCircle,
  Trash2,
  Info,
  Loader2,
  Headphones,
} from 'lucide-react';
import {
  useDashboard,
  useNotes,
  useSupportTickets,
  useSupportTicket,
  useCreateSupportTicket,
  useReplyToSupportTicket,
  useDeleteSupportTicket,
  useSupportUnreadCount,
} from '@/hooks/useApi';
import type { SupportTicket } from '@/types';
import NoteCard from './NoteCard';
import NoteForm from './NoteForm';

type TabType = 'support' | 'notes';
type FilterType = 'all' | 'pinned';
type SupportViewState = 'list' | 'compose' | 'detail';

export default function MessagesView() {
  const [activeTab, setActiveTab] = useState<TabType>('support');
  const { data: unreadData } = useSupportUnreadCount();
  const unreadCount = unreadData?.count || 0;

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Messages</h1>
        <p className="text-gray-600 mt-1">
          Support conversations and personal notes
        </p>
      </div>

      {/* Tab navigation */}
      <div className="flex border-b border-gray-200 mb-6">
        <button
          onClick={() => setActiveTab('support')}
          className={clsx(
            'px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2',
            activeTab === 'support'
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          )}
          aria-selected={activeTab === 'support'}
          role="tab"
        >
          <Headphones className="w-4 h-4" />
          Support
          {unreadCount > 0 && (
            <span className="ml-1 px-2 py-0.5 text-xs font-medium bg-primary-500 text-white rounded-full">
              {unreadCount}
            </span>
          )}
        </button>
        <button
          onClick={() => setActiveTab('notes')}
          className={clsx(
            'px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2',
            activeTab === 'notes'
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          )}
          aria-selected={activeTab === 'notes'}
          role="tab"
        >
          <StickyNote className="w-4 h-4" />
          My Notes
        </button>
      </div>

      {/* Tab content */}
      {activeTab === 'support' ? <SupportTabContent /> : <NotesTabContent />}
    </div>
  );
}

// =============================================================================
// SUPPORT TAB CONTENT
// =============================================================================

function SupportTabContent() {
  const [view, setView] = useState<SupportViewState>('list');
  const [selectedTicketId, setSelectedTicketId] = useState<number | null>(null);

  const handleViewTicket = (ticketId: number) => {
    setSelectedTicketId(ticketId);
    setView('detail');
  };

  const handleBack = () => {
    setSelectedTicketId(null);
    setView('list');
  };

  return (
    <>
      {view === 'list' && (
        <TicketList
          onCompose={() => setView('compose')}
          onViewTicket={handleViewTicket}
        />
      )}
      {view === 'compose' && (
        <ComposeTicket
          onBack={handleBack}
          onSuccess={(ticketId) => {
            setSelectedTicketId(ticketId);
            setView('detail');
          }}
        />
      )}
      {view === 'detail' && selectedTicketId && (
        <TicketDetail ticketId={selectedTicketId} onBack={handleBack} />
      )}
    </>
  );
}

// Ticket List Component
function TicketList({
  onCompose,
  onViewTicket,
}: {
  onCompose: () => void;
  onViewTicket: (id: number) => void;
}) {
  const { data, isLoading } = useSupportTickets();
  const tickets = data?.tickets || [];

  return (
    <>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-lg font-semibold text-gray-900">Customer Support</h2>
          <p className="text-sm text-gray-600 mt-1">
            Contact us for help with your membership or site issues
          </p>
        </div>
        <button
          onClick={onCompose}
          className="btn btn-primary flex items-center gap-2"
        >
          <Plus className="w-4 h-4" />
          New Message
        </button>
      </div>

      {/* Support Scope Notice */}
      <div className="card mb-6 border-l-4 border-l-blue-500 bg-blue-50">
        <div className="p-4 flex gap-3">
          <Info className="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm text-blue-800 font-medium">What we can help with:</p>
            <p className="text-sm text-blue-700 mt-1">
              Membership questions, site errors, or content that needs correction.
            </p>
            <p className="text-xs text-blue-600 mt-2">
              We do not provide visa assistance, as this is a legal question that should be directed to a qualified immigration professional.
              This site is provided as a convenience to assist with the visa application process only.
            </p>
          </div>
        </div>
      </div>

      {/* Tickets List */}
      {isLoading ? (
        <TicketListSkeleton />
      ) : tickets.length === 0 ? (
        <SupportEmptyState onCompose={onCompose} />
      ) : (
        <div className="space-y-3">
          {tickets.map((ticket) => (
            <TicketCard
              key={ticket.id}
              ticket={ticket}
              onClick={() => onViewTicket(ticket.id)}
            />
          ))}
        </div>
      )}
    </>
  );
}

// Single Ticket Card
function TicketCard({
  ticket,
  onClick,
}: {
  ticket: SupportTicket;
  onClick: () => void;
}) {
  return (
    <button
      onClick={onClick}
      className={clsx(
        'card w-full text-left p-4 hover:bg-gray-50 transition-colors',
        ticket.has_unread_user && 'ring-2 ring-primary-500 bg-primary-50'
      )}
    >
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <h3 className="font-medium text-gray-900 truncate">{ticket.subject}</h3>
            {ticket.has_unread_user && (
              <span className="px-2 py-0.5 text-xs font-medium bg-primary-500 text-white rounded-full">
                New Reply
              </span>
            )}
          </div>
          <p className="text-sm text-gray-500 mt-1 line-clamp-1">
            {ticket.initial_message || 'No message content'}
          </p>
          <div className="flex items-center gap-4 mt-2 text-xs text-gray-400">
            <span className="flex items-center gap-1">
              <Clock className="w-3 h-3" />
              {ticket.relative_time}
            </span>
            <span className="flex items-center gap-1">
              <MessageSquare className="w-3 h-3" />
              {ticket.reply_count} {ticket.reply_count === 1 ? 'reply' : 'replies'}
            </span>
          </div>
        </div>
        <StatusBadge status={ticket.status} />
      </div>
    </button>
  );
}

// Status Badge
function StatusBadge({ status }: { status: string }) {
  if (status === 'closed') {
    return (
      <span className="flex items-center gap-1 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
        <CheckCircle2 className="w-3 h-3" />
        Closed
      </span>
    );
  }
  return (
    <span className="flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">
      <AlertCircle className="w-3 h-3" />
      Open
    </span>
  );
}

// Empty State
function SupportEmptyState({ onCompose }: { onCompose: () => void }) {
  return (
    <div className="card p-12 text-center">
      <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
        <MessageSquare className="w-8 h-8 text-gray-400" />
      </div>
      <h3 className="text-lg font-semibold text-gray-900 mb-2">
        No support messages
      </h3>
      <p className="text-gray-600 mb-6">
        Have a question or found an issue? Send us a message and we&apos;ll get back to you.
      </p>
      <button
        onClick={onCompose}
        className="btn btn-primary inline-flex items-center gap-2"
      >
        <Plus className="w-4 h-4" />
        Send Your First Message
      </button>
    </div>
  );
}

// Compose New Ticket
function ComposeTicket({
  onBack,
  onSuccess,
}: {
  onBack: () => void;
  onSuccess: (ticketId: number) => void;
}) {
  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');
  const createTicket = useCreateSupportTicket();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!subject.trim() || !content.trim()) return;

    createTicket.mutate(
      { subject: subject.trim(), content: content.trim() },
      {
        onSuccess: (data) => {
          onSuccess(data.ticket_id);
        },
      }
    );
  };

  return (
    <>
      {/* Header */}
      <div className="mb-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Messages
        </button>
        <h2 className="text-lg font-semibold text-gray-900">New Support Message</h2>
        <p className="text-sm text-gray-600 mt-1">
          Describe your question or issue and we&apos;ll respond as soon as possible
        </p>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit} className="card max-w-2xl">
        <div className="p-6 space-y-6">
          <div>
            <label
              htmlFor="subject"
              className="block text-sm font-medium text-gray-700 mb-2"
            >
              Subject
            </label>
            <input
              id="subject"
              type="text"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="What do you need help with?"
              className="input w-full"
              required
            />
          </div>

          <div>
            <label
              htmlFor="content"
              className="block text-sm font-medium text-gray-700 mb-2"
            >
              Message
            </label>
            <textarea
              id="content"
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder="Describe your question or issue in detail..."
              rows={6}
              className="textarea w-full"
              required
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-4 border-t">
            <button
              type="button"
              onClick={onBack}
              className="btn btn-secondary"
              disabled={createTicket.isPending}
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!subject.trim() || !content.trim() || createTicket.isPending}
              className="btn btn-primary flex items-center gap-2"
            >
              {createTicket.isPending ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  Sending...
                </>
              ) : (
                <>
                  <Send className="w-4 h-4" />
                  Send Message
                </>
              )}
            </button>
          </div>
        </div>
      </form>
    </>
  );
}

// Ticket Detail View
function TicketDetail({
  ticketId,
  onBack,
}: {
  ticketId: number;
  onBack: () => void;
}) {
  const [reply, setReply] = useState('');
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const { data, isLoading, refetch } = useSupportTicket(ticketId);
  const replyMutation = useReplyToSupportTicket();
  const deleteMutation = useDeleteSupportTicket();

  const handleReply = (e: React.FormEvent) => {
    e.preventDefault();
    if (!reply.trim()) return;

    replyMutation.mutate(
      { ticketId, content: reply.trim() },
      {
        onSuccess: () => {
          setReply('');
          refetch();
        },
      }
    );
  };

  const handleDelete = () => {
    deleteMutation.mutate(ticketId, {
      onSuccess: onBack,
    });
  };

  if (isLoading) {
    return <TicketDetailSkeleton />;
  }

  if (!data) {
    return (
      <div className="card p-8 text-center">
        <p className="text-gray-600">Ticket not found.</p>
        <button onClick={onBack} className="btn btn-secondary mt-4">
          Back to Messages
        </button>
      </div>
    );
  }

  const { ticket, replies } = data;

  return (
    <>
      {/* Header */}
      <div className="mb-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Messages
        </button>
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-lg font-semibold text-gray-900">{ticket.subject}</h2>
            <div className="flex items-center gap-4 mt-2">
              <StatusBadge status={ticket.status} />
              <span className="text-sm text-gray-500">
                Created {ticket.relative_time}
              </span>
            </div>
          </div>
          <button
            onClick={() => setShowDeleteConfirm(true)}
            className="btn btn-secondary text-red-600 hover:bg-red-50"
            aria-label="Delete ticket"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Conversation */}
      <div className="card mb-6">
        <div className="p-6 space-y-6">
          {replies.map((r) => (
            <div
              key={r.id}
              className={clsx(
                'p-4 rounded-lg',
                r.is_admin ? 'bg-primary-50 ml-8' : 'bg-gray-50 mr-8'
              )}
            >
              <div className="flex items-center justify-between mb-2">
                <span
                  className={clsx(
                    'text-sm font-medium',
                    r.is_admin ? 'text-primary-700' : 'text-gray-700'
                  )}
                >
                  {r.author_name}
                </span>
                <span className="text-xs text-gray-500">{r.relative_time}</span>
              </div>
              <p className="text-gray-700 whitespace-pre-wrap">{r.content}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Reply Form */}
      {ticket.status !== 'closed' && (
        <form onSubmit={handleReply} className="card">
          <div className="p-4">
            <label htmlFor="reply" className="sr-only">
              Your reply
            </label>
            <textarea
              id="reply"
              value={reply}
              onChange={(e) => setReply(e.target.value)}
              placeholder="Type your reply..."
              rows={4}
              className="textarea w-full"
              required
            />
            <div className="flex justify-end mt-4">
              <button
                type="submit"
                disabled={!reply.trim() || replyMutation.isPending}
                className="btn btn-primary flex items-center gap-2"
              >
                {replyMutation.isPending ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Sending...
                  </>
                ) : (
                  <>
                    <Send className="w-4 h-4" />
                    Send Reply
                  </>
                )}
              </button>
            </div>
          </div>
        </form>
      )}

      {ticket.status === 'closed' && (
        <div className="card bg-gray-50 p-4 text-center">
          <p className="text-gray-600">
            This conversation is closed. Start a new message if you need further assistance.
          </p>
        </div>
      )}

      {/* Delete Confirmation */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg p-6 max-w-md w-full">
            <h3 className="text-lg font-semibold text-gray-900 mb-2">
              Delete this conversation?
            </h3>
            <p className="text-gray-600 mb-6">
              This action cannot be undone. All messages in this conversation will be permanently deleted.
            </p>
            <div className="flex items-center justify-end gap-3">
              <button
                onClick={() => setShowDeleteConfirm(false)}
                className="btn btn-secondary"
                disabled={deleteMutation.isPending}
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                disabled={deleteMutation.isPending}
                className="btn bg-red-600 text-white hover:bg-red-700 flex items-center gap-2"
              >
                {deleteMutation.isPending ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Deleting...
                  </>
                ) : (
                  <>
                    <Trash2 className="w-4 h-4" />
                    Delete
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

// Support Loading Skeletons
function TicketListSkeleton() {
  return (
    <div className="space-y-3">
      {[1, 2, 3].map((i) => (
        <div key={i} className="card p-4">
          <div className="flex items-start justify-between gap-4">
            <div className="flex-1">
              <div className="h-5 w-48 bg-gray-200 rounded animate-pulse mb-2" />
              <div className="h-4 w-full bg-gray-100 rounded animate-pulse mb-3" />
              <div className="flex gap-4">
                <div className="h-3 w-20 bg-gray-100 rounded animate-pulse" />
                <div className="h-3 w-24 bg-gray-100 rounded animate-pulse" />
              </div>
            </div>
            <div className="h-6 w-16 bg-gray-200 rounded-full animate-pulse" />
          </div>
        </div>
      ))}
    </div>
  );
}

function TicketDetailSkeleton() {
  return (
    <div>
      <div className="h-4 w-32 bg-gray-200 rounded animate-pulse mb-4" />
      <div className="h-8 w-64 bg-gray-200 rounded animate-pulse mb-6" />
      <div className="card p-6 space-y-6">
        {[1, 2].map((i) => (
          <div
            key={i}
            className={clsx(
              'p-4 rounded-lg',
              i === 1 ? 'bg-gray-50 mr-8' : 'bg-primary-50 ml-8'
            )}
          >
            <div className="h-4 w-32 bg-gray-200 rounded animate-pulse mb-3" />
            <div className="space-y-2">
              <div className="h-4 bg-gray-200 rounded animate-pulse" />
              <div className="h-4 bg-gray-200 rounded animate-pulse w-3/4" />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// =============================================================================
// NOTES TAB CONTENT
// =============================================================================

function NotesTabContent() {
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
    return <NotesTabSkeleton />;
  }

  if (!dashboard?.project) {
    return (
      <div className="card p-8 text-center">
        <h2 className="text-lg font-semibold text-gray-900 mb-2">No Project Found</h2>
        <p className="text-gray-600">Please set up your relocation project first.</p>
      </div>
    );
  }

  return (
    <>
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
    </>
  );
}

function NotesTabSkeleton() {
  return (
    <>
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
    </>
  );
}
