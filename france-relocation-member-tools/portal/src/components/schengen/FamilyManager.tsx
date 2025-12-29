/**
 * FamilyManager
 *
 * Manages family members for Schengen tracking.
 * Allows users to add, edit, and remove family members,
 * and displays their individual Schengen compliance status.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Users,
  Plus,
  Edit2,
  Trash2,
  X,
  Check,
  Loader2,
  User,
  AlertTriangle,
  RefreshCw,
} from 'lucide-react';
import type { SchengenFamilyMember, SchengenFamilyMemberCreate, SchengenFamilyRelationship, SchengenFamilyMemberStatus } from '@/types';
import {
  useSchengenFamilyMembers,
  useSchengenFamilySummary,
  useCreateSchengenFamilyMember,
  useUpdateSchengenFamilyMember,
  useDeleteSchengenFamilyMember,
} from '@/hooks/useApi';

interface FamilyManagerProps {
  className?: string;
}

const RELATIONSHIPS: { value: SchengenFamilyRelationship; label: string }[] = [
  { value: 'spouse', label: 'Spouse' },
  { value: 'partner', label: 'Partner' },
  { value: 'child', label: 'Child' },
  { value: 'parent', label: 'Parent' },
  { value: 'sibling', label: 'Sibling' },
  { value: 'other', label: 'Other' },
];

const COLORS = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Amber
  '#EF4444', // Red
  '#8B5CF6', // Purple
  '#EC4899', // Pink
  '#06B6D4', // Cyan
  '#F97316', // Orange
];

function getStatusColor(level: SchengenFamilyMemberStatus['level']) {
  switch (level) {
    case 'danger':
      return 'text-red-600 bg-red-50';
    case 'warning':
      return 'text-amber-600 bg-amber-50';
    default:
      return 'text-green-600 bg-green-50';
  }
}

function StatusBadge({ status }: { status: SchengenFamilyMemberStatus }) {
  return (
    <div className={clsx('px-3 py-1 rounded-full text-sm font-medium', getStatusColor(status.level))}>
      {status.daysUsed}/{status.daysAllowed} days
    </div>
  );
}

function StatusBar({ status }: { status: SchengenFamilyMemberStatus }) {
  return (
    <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
      <div
        className={clsx(
          'h-full rounded-full transition-all duration-300',
          status.level === 'danger' ? 'bg-red-500' :
          status.level === 'warning' ? 'bg-amber-500' : 'bg-green-500'
        )}
        style={{ width: `${Math.min(status.percentage, 100)}%` }}
      />
    </div>
  );
}

interface MemberFormProps {
  member?: SchengenFamilyMember;
  onSave: (data: SchengenFamilyMemberCreate) => void;
  onCancel: () => void;
  isLoading: boolean;
}

function MemberForm({ member, onSave, onCancel, isLoading }: MemberFormProps) {
  const [name, setName] = useState(member?.name || '');
  const [relationship, setRelationship] = useState<SchengenFamilyRelationship | ''>(member?.relationship || '');
  const [nationality, setNationality] = useState(member?.nationality || '');
  const [passportCountry, setPassportCountry] = useState(member?.passportCountry || '');
  const [dateOfBirth, setDateOfBirth] = useState(member?.dateOfBirth || '');
  const [notes, setNotes] = useState(member?.notes || '');
  const [color, setColor] = useState(member?.color || COLORS[0]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;

    onSave({
      name: name.trim(),
      relationship: relationship || undefined,
      nationality: nationality || undefined,
      passportCountry: passportCountry || undefined,
      dateOfBirth: dateOfBirth || undefined,
      notes: notes || undefined,
      color,
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label htmlFor="member-name" className="block text-sm font-medium text-gray-700 mb-1">
          Name <span className="text-red-500">*</span>
        </label>
        <input
          id="member-name"
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="e.g., John Doe"
          required
        />
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="member-relationship" className="block text-sm font-medium text-gray-700 mb-1">
            Relationship
          </label>
          <select
            id="member-relationship"
            value={relationship}
            onChange={(e) => setRelationship(e.target.value as SchengenFamilyRelationship)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            <option value="">Select...</option>
            {RELATIONSHIPS.map((rel) => (
              <option key={rel.value} value={rel.value}>
                {rel.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="member-dob" className="block text-sm font-medium text-gray-700 mb-1">
            Date of Birth
          </label>
          <input
            id="member-dob"
            type="date"
            value={dateOfBirth}
            onChange={(e) => setDateOfBirth(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="member-nationality" className="block text-sm font-medium text-gray-700 mb-1">
            Nationality
          </label>
          <input
            id="member-nationality"
            type="text"
            value={nationality}
            onChange={(e) => setNationality(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., American"
          />
        </div>

        <div>
          <label htmlFor="member-passport" className="block text-sm font-medium text-gray-700 mb-1">
            Passport Country
          </label>
          <input
            id="member-passport"
            type="text"
            value={passportCountry}
            onChange={(e) => setPassportCountry(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., United States"
          />
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Color
        </label>
        <div className="flex gap-2">
          {COLORS.map((c) => (
            <button
              key={c}
              type="button"
              onClick={() => setColor(c)}
              className={clsx(
                'w-8 h-8 rounded-full border-2 transition-all',
                color === c ? 'border-gray-900 scale-110' : 'border-transparent'
              )}
              style={{ backgroundColor: c }}
              aria-label={`Select color ${c}`}
            />
          ))}
        </div>
      </div>

      <div>
        <label htmlFor="member-notes" className="block text-sm font-medium text-gray-700 mb-1">
          Notes
        </label>
        <textarea
          id="member-notes"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={2}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="Any additional notes..."
        />
      </div>

      <div className="flex gap-3 justify-end">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isLoading || !name.trim()}
          className="btn-primary flex items-center gap-2"
        >
          {isLoading ? (
            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
          ) : (
            <Check className="w-4 h-4" aria-hidden="true" />
          )}
          {member ? 'Update' : 'Add'} Member
        </button>
      </div>
    </form>
  );
}

interface MemberCardProps {
  member: SchengenFamilyMember;
  status?: SchengenFamilyMemberStatus;
  onEdit: () => void;
  onDelete: () => void;
  isDeleting: boolean;
}

function MemberCard({ member, status, onEdit, onDelete, isDeleting }: MemberCardProps) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  return (
    <div className="card p-4 border-l-4" style={{ borderLeftColor: member.color }}>
      <div className="flex items-start justify-between">
        <div className="flex items-center gap-3">
          <div
            className="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold"
            style={{ backgroundColor: member.color }}
          >
            {member.name.charAt(0).toUpperCase()}
          </div>
          <div>
            <h4 className="font-medium text-gray-900">{member.name}</h4>
            <p className="text-sm text-gray-500">
              {member.relationship ? RELATIONSHIPS.find(r => r.value === member.relationship)?.label : 'Family member'}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {status && <StatusBadge status={status} />}
          <button
            onClick={onEdit}
            className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            aria-label="Edit member"
          >
            <Edit2 className="w-4 h-4" aria-hidden="true" />
          </button>
          {!showDeleteConfirm ? (
            <button
              onClick={() => setShowDeleteConfirm(true)}
              className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              aria-label="Delete member"
            >
              <Trash2 className="w-4 h-4" aria-hidden="true" />
            </button>
          ) : (
            <div className="flex items-center gap-1">
              <button
                onClick={onDelete}
                disabled={isDeleting}
                className="p-2 text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors"
                aria-label="Confirm delete"
              >
                {isDeleting ? (
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Check className="w-4 h-4" aria-hidden="true" />
                )}
              </button>
              <button
                onClick={() => setShowDeleteConfirm(false)}
                className="p-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                aria-label="Cancel delete"
              >
                <X className="w-4 h-4" aria-hidden="true" />
              </button>
            </div>
          )}
        </div>
      </div>

      {status && (
        <div className="mt-3">
          <StatusBar status={status} />
          <p className="text-xs text-gray-500 mt-1">
            {status.daysRemaining} days remaining in current 180-day window
          </p>
        </div>
      )}
    </div>
  );
}

export default function FamilyManager({ className }: FamilyManagerProps) {
  const [isAddingMember, setIsAddingMember] = useState(false);
  const [editingMember, setEditingMember] = useState<SchengenFamilyMember | null>(null);

  const { data: membersData, isLoading: membersLoading, isError: membersError, refetch } = useSchengenFamilyMembers();
  const { data: summaryData, isLoading: summaryLoading } = useSchengenFamilySummary();

  const createMutation = useCreateSchengenFamilyMember();
  const updateMutation = useUpdateSchengenFamilyMember();
  const deleteMutation = useDeleteSchengenFamilyMember();

  const members = membersData?.members || [];
  const summary = summaryData;

  const handleCreate = async (data: SchengenFamilyMemberCreate) => {
    try {
      await createMutation.mutateAsync(data);
      setIsAddingMember(false);
    } catch {
      // Error handled by mutation
    }
  };

  const handleUpdate = async (data: SchengenFamilyMemberCreate) => {
    if (!editingMember) return;
    try {
      await updateMutation.mutateAsync({ id: editingMember.id, data });
      setEditingMember(null);
    } catch {
      // Error handled by mutation
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteMutation.mutateAsync(id);
    } catch {
      // Error handled by mutation
    }
  };

  const getMemberStatus = (memberId: number) => {
    return summary?.members.find(m => m.id === memberId)?.status;
  };

  if (membersLoading || summaryLoading) {
    return (
      <div className={clsx('card p-6', className)}>
        <div className="flex items-center justify-center py-8">
          <Loader2 className="w-6 h-6 text-primary-600 animate-spin" aria-hidden="true" />
          <span className="ml-2 text-gray-500">Loading family members...</span>
        </div>
      </div>
    );
  }

  if (membersError) {
    return (
      <div className={clsx('card p-6', className)}>
        <div className="text-center py-6">
          <AlertTriangle className="w-10 h-10 text-amber-500 mx-auto mb-2" aria-hidden="true" />
          <p className="text-gray-500">Unable to load family members</p>
          <button
            onClick={() => refetch()}
            className="mt-2 text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            Try again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className={clsx('space-y-6', className)}>
      {/* Header */}
      <div className="card p-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary-100 rounded-lg">
              <Users className="w-5 h-5 text-primary-600" aria-hidden="true" />
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Family Members</h3>
              <p className="text-sm text-gray-500">
                Track Schengen days separately for each family member
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => refetch()}
              className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Refresh"
            >
              <RefreshCw className="w-4 h-4" aria-hidden="true" />
            </button>
            {!isAddingMember && !editingMember && (
              <button
                onClick={() => setIsAddingMember(true)}
                className="btn-primary flex items-center gap-2"
              >
                <Plus className="w-4 h-4" aria-hidden="true" />
                Add Member
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Primary Account Holder */}
      {summary && (
        <div className="card p-4 border-l-4" style={{ borderLeftColor: summary.primary.color }}>
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <div
                className="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold"
                style={{ backgroundColor: summary.primary.color }}
              >
                <User className="w-5 h-5" aria-hidden="true" />
              </div>
              <div>
                <h4 className="font-medium text-gray-900">{summary.primary.name}</h4>
                <p className="text-sm text-gray-500">Primary account holder</p>
              </div>
            </div>
            <StatusBadge status={summary.primary.status} />
          </div>
          <div className="mt-3">
            <StatusBar status={summary.primary.status} />
            <p className="text-xs text-gray-500 mt-1">
              {summary.primary.status.daysRemaining} days remaining in current 180-day window
            </p>
          </div>
        </div>
      )}

      {/* Add Member Form */}
      {isAddingMember && (
        <div className="card p-6">
          <h4 className="font-medium text-gray-900 mb-4">Add Family Member</h4>
          <MemberForm
            onSave={handleCreate}
            onCancel={() => setIsAddingMember(false)}
            isLoading={createMutation.isPending}
          />
        </div>
      )}

      {/* Edit Member Form */}
      {editingMember && (
        <div className="card p-6">
          <h4 className="font-medium text-gray-900 mb-4">Edit Family Member</h4>
          <MemberForm
            member={editingMember}
            onSave={handleUpdate}
            onCancel={() => setEditingMember(null)}
            isLoading={updateMutation.isPending}
          />
        </div>
      )}

      {/* Family Members List */}
      {members.length > 0 ? (
        <div className="space-y-3">
          {members.map((member) => (
            <MemberCard
              key={member.id}
              member={member}
              status={getMemberStatus(member.id)}
              onEdit={() => setEditingMember(member)}
              onDelete={() => handleDelete(member.id)}
              isDeleting={deleteMutation.isPending}
            />
          ))}
        </div>
      ) : (
        !isAddingMember && (
          <div className="card p-8 text-center">
            <Users className="w-12 h-12 text-gray-300 mx-auto mb-3" aria-hidden="true" />
            <h4 className="font-medium text-gray-900 mb-1">No family members yet</h4>
            <p className="text-sm text-gray-500 mb-4">
              Add family members to track their Schengen days separately
            </p>
            <button
              onClick={() => setIsAddingMember(true)}
              className="btn-primary inline-flex items-center gap-2"
            >
              <Plus className="w-4 h-4" aria-hidden="true" />
              Add First Member
            </button>
          </div>
        )
      )}
    </div>
  );
}
