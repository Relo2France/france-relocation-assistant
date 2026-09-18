import { useState } from 'react';
import { clsx } from 'clsx';
import {
  AlertCircle,
  ArrowLeft,
  CheckCircle2,
  Clock,
  Info,
  Loader2,
  MessageSquare,
  Plus,
  Send,
  Trash2,
} from 'lucide-react';
import {
  useCreateSupportTicket,
  useDeleteSupportTicket,
  useReplyToSupportTicket,
  useSupportTicket,
  useSupportTickets,
} from '@/hooks/useApi';
import type { SupportTicket } from '@/types';

type ViewState = 'list' | 'compose' | 'detail';

export default function SupportView() {
  const [view, setView] = useState<ViewState>('list');
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
    <div className="p-6">
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
        <TicketDetail
          ticketId={selectedTicketId}
          onBack={handleBack}
        />
      )}
    </div>
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
  // Site-originated threads (reports ready, welcome) belong to Messages.
  const tickets = (data?.tickets || []).filter((t) => !t.from_site);

  return (
    <>
      {/* The title is in the top bar. What Support is for, and the one button. */}
      <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div className="flex flex-col gap-1 max-w-[64ch]">
          <span className="eyebrow">To Relo2France</span>
          <p className="text-gray-600 m-0">
            Write to us about your membership, something on the site that looks wrong, or a page that broke. We answer within a day. Questions about your own move go to Ask about my case; what we send you is in Messages.
          </p>
        </div>
        <button onClick={onCompose} className="btn btn-primary flex items-center gap-2 self-start whitespace-nowrap">
          <Plus className="w-4 h-4" aria-hidden="true" />
          Write to us
        </button>
      </div>

      <div className="card mb-6 px-4 py-3 flex gap-3 items-start">
        <Info className="w-4 h-4 text-gray-500 flex-shrink-0 mt-0.5" aria-hidden="true" />
        <p className="text-[0.85rem] text-gray-600 m-0">
          We are not lawyers or tax advisers, so we cannot answer legal questions about your visa. Where a step needs a professional, the portal says so and why.
        </p>
      </div>

      {/* Tickets List */}
      {isLoading ? (
        <TicketListSkeleton />
      ) : tickets.length === 0 ? (
        <EmptyState onCompose={onCompose} />
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
        'card w-full text-left p-4 hover:bg-card-2 transition-colors',
        ticket.has_unread_user && 'border-primary-500'
      )}
    >
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <h3 className={clsx('text-gray-900 line-clamp-2 m-0', ticket.has_unread_user ? 'font-semibold' : 'font-medium')} title={ticket.subject}>{ticket.subject}</h3>
            {ticket.has_unread_user && (
              <span className="badge bg-primary-500 text-white whitespace-nowrap">New reply</span>
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
function EmptyState({ onCompose }: { onCompose: () => void }) {
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
          Back to Support
        </button>
        <h1 className="text-2xl font-bold text-gray-900">New Support Message</h1>
        <p className="text-gray-600 mt-1">
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
          Back to Support
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
          Back to Support
        </button>
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{ticket.subject}</h1>
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
                r.is_admin
                  ? 'bg-primary-50 ml-8'
                  : 'bg-gray-50 mr-8'
              )}
            >
              <div className="flex items-center justify-between mb-2">
                <span className={clsx(
                  'text-sm font-medium',
                  r.is_admin ? 'text-primary-700' : 'text-gray-700'
                )}>
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

// Loading Skeletons
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
          <div key={i} className={clsx('p-4 rounded-lg', i === 1 ? 'bg-gray-50 mr-8' : 'bg-primary-50 ml-8')}>
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
