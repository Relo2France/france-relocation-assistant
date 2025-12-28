/**
 * FamilyTracker
 *
 * Tracks Schengen compliance for family members.
 * Allows adding/editing family members and viewing individual day counts.
 */

import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Users,
  Plus,
  Edit2,
  Trash2,
  User,
  Loader2,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import type {
  SchengenFamilyMember,
  SchengenFamilyOverview,
  SchengenRelationship,
} from '@/types';
import {
  useSchengenFamilySummaries,
  useCreateSchengenFamilyMember,
  useUpdateSchengenFamilyMember,
  useDeleteSchengenFamilyMember,
} from '@/hooks/useApi';
import DayCounter from './DayCounter';
import StatusBadge from './StatusBadge';
import Modal from '@/components/shared/Modal';

interface FamilyTrackerProps {
  compact?: boolean;
}

export default function FamilyTracker({ compact = false }: FamilyTrackerProps) {
  const { data, isLoading, error, refetch } = useSchengenFamilySummaries();
  const createMutation = useCreateSchengenFamilyMember();
  const updateMutation = useUpdateSchengenFamilyMember();
  const deleteMutation = useDeleteSchengenFamilyMember();

  const [showAddForm, setShowAddForm] = useState(false);
  const [editingMember, setEditingMember] = useState<SchengenFamilyMember | null>(null);
  const [expandedMembers, setExpandedMembers] = useState<Set<number>>(new Set());

  const toggleExpanded = (id: number) => {
    const next = new Set(expandedMembers);
    if (next.has(id)) {
      next.delete(id);
    } else {
      next.add(id);
    }
    setExpandedMembers(next);
  };

  const handleCreate = async (formData: Partial<SchengenFamilyMember>) => {
    await createMutation.mutateAsync(formData);
    setShowAddForm(false);
    refetch();
  };

  const handleUpdate = async (id: number, formData: Partial<SchengenFamilyMember>) => {
    await updateMutation.mutateAsync({ id, data: formData });
    setEditingMember(null);
    refetch();
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Remove this family member from tracking?')) return;
    await deleteMutation.mutateAsync(id);
    refetch();
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Loader2 className="w-6 h-6 animate-spin text-primary-500" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
        Failed to load family data
      </div>
    );
  }

  const overview = data as SchengenFamilyOverview | undefined;

  if (compact) {
    return (
      <div className="card p-4">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Users className="w-5 h-5 text-primary-600" aria-hidden="true" />
            <h3 className="font-semibold text-gray-900">Family Overview</h3>
          </div>
          <span className="text-sm text-gray-500">
            {(overview?.family?.length ?? 0) + 1} travelers
          </span>
        </div>

        <div className="space-y-2">
          {/* Primary user */}
          {overview?.primaryUser && (
            <div className="flex items-center justify-between p-2 bg-gray-50 rounded">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-indigo-500" />
                <span className="text-sm font-medium">{overview.primaryUser.name}</span>
                <span className="text-xs text-gray-400">(You)</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm font-mono">
                  {overview.primaryUser.summary.daysUsed}/90
                </span>
                <StatusBadge status={overview.primaryUser.summary.status} size="sm" />
              </div>
            </div>
          )}

          {/* Family members */}
          {overview?.family?.map((item) => (
            <div
              key={item.member.id}
              className="flex items-center justify-between p-2 bg-gray-50 rounded"
            >
              <div className="flex items-center gap-2">
                <div
                  className="w-2 h-2 rounded-full"
                  style={{ backgroundColor: item.member.color }}
                />
                <span className="text-sm font-medium">{item.member.name}</span>
                <span className="text-xs text-gray-400 capitalize">
                  ({item.member.relationship})
                </span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm font-mono">
                  {item.summary.daysUsed}/90
                </span>
                <StatusBadge status={item.summary.status} size="sm" />
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-primary-100 rounded-lg">
            <Users className="w-5 h-5 text-primary-600" aria-hidden="true" />
          </div>
          <div>
            <h2 className="text-lg font-semibold text-gray-900">Family Tracking</h2>
            <p className="text-sm text-gray-500">
              Track Schengen days for each family member
            </p>
          </div>
        </div>
        <button
          onClick={() => setShowAddForm(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
        >
          <Plus className="w-4 h-4" aria-hidden="true" />
          Add Member
        </button>
      </div>

      {/* Primary User Card */}
      {overview?.primaryUser && (
        <div className="card p-6 border-l-4 border-l-indigo-500">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <User className="w-6 h-6 text-indigo-600" aria-hidden="true" />
              </div>
              <div>
                <h3 className="font-semibold text-gray-900">
                  {overview.primaryUser.name}
                </h3>
                <p className="text-sm text-gray-500">Primary Account Holder</p>
              </div>
            </div>
            <div className="flex items-center gap-6">
              <DayCounter
                daysUsed={overview.primaryUser.summary.daysUsed}
                daysRemaining={overview.primaryUser.summary.daysRemaining}
                status={overview.primaryUser.summary.status}
                size="sm"
              />
              <StatusBadge status={overview.primaryUser.summary.status} size="lg" />
            </div>
          </div>
        </div>
      )}

      {/* Family Members */}
      {overview?.family && overview.family.length > 0 ? (
        <div className="space-y-4">
          {overview.family.map((item) => (
            <FamilyMemberCard
              key={item.member.id}
              member={item.member}
              summary={item.summary}
              isExpanded={expandedMembers.has(item.member.id)}
              onToggle={() => toggleExpanded(item.member.id)}
              onEdit={() => setEditingMember(item.member)}
              onDelete={() => handleDelete(item.member.id)}
            />
          ))}
        </div>
      ) : (
        <div className="card p-8 text-center">
          <Users className="w-12 h-12 text-gray-300 mx-auto mb-4" aria-hidden="true" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Family Members</h3>
          <p className="text-gray-500 mb-4">
            Add family members to track their Schengen days separately
          </p>
          <button
            onClick={() => setShowAddForm(true)}
            className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700"
          >
            <Plus className="w-4 h-4" aria-hidden="true" />
            Add First Member
          </button>
        </div>
      )}

      {/* Add/Edit Modal */}
      <Modal
        isOpen={showAddForm || !!editingMember}
        onClose={() => {
          setShowAddForm(false);
          setEditingMember(null);
        }}
        title={editingMember ? 'Edit Family Member' : 'Add Family Member'}
        size="md"
      >
        <FamilyMemberForm
          member={editingMember}
          onSubmit={(data) =>
            editingMember
              ? handleUpdate(editingMember.id, data)
              : handleCreate(data)
          }
          onCancel={() => {
            setShowAddForm(false);
            setEditingMember(null);
          }}
          isLoading={createMutation.isPending || updateMutation.isPending}
        />
      </Modal>
    </div>
  );
}

/**
 * Family member card component
 */
function FamilyMemberCard({
  member,
  summary,
  isExpanded,
  onToggle,
  onEdit,
  onDelete,
}: {
  member: SchengenFamilyMember;
  summary: { daysUsed: number; daysRemaining: number; status: string; tripCount: number };
  isExpanded: boolean;
  onToggle: () => void;
  onEdit: () => void;
  onDelete: () => void;
}) {
  return (
    <div
      className="card overflow-hidden"
      style={{ borderLeftWidth: '4px', borderLeftColor: member.color }}
    >
      <div className="p-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div
              className="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold"
              style={{ backgroundColor: member.color }}
            >
              {member.name.charAt(0).toUpperCase()}
            </div>
            <div>
              <h3 className="font-semibold text-gray-900">{member.name}</h3>
              <p className="text-sm text-gray-500 capitalize">{member.relationship}</p>
            </div>
          </div>
          <div className="flex items-center gap-4">
            <div className="text-right">
              <p className="text-2xl font-bold text-gray-900">{summary.daysUsed}</p>
              <p className="text-xs text-gray-500">of 90 days</p>
            </div>
            <StatusBadge status={summary.status as 'safe' | 'warning' | 'danger' | 'critical'} />
            <button
              onClick={onToggle}
              className="p-1 text-gray-400 hover:text-gray-600"
              aria-label={isExpanded ? 'Collapse' : 'Expand'}
            >
              {isExpanded ? (
                <ChevronUp className="w-5 h-5" />
              ) : (
                <ChevronDown className="w-5 h-5" />
              )}
            </button>
          </div>
        </div>
      </div>

      {isExpanded && (
        <div className="border-t border-gray-100 p-4 bg-gray-50">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
              <p className="text-xs text-gray-500 mb-1">Days Remaining</p>
              <p className="font-semibold">{summary.daysRemaining}</p>
            </div>
            <div>
              <p className="text-xs text-gray-500 mb-1">Trips</p>
              <p className="font-semibold">{summary.tripCount}</p>
            </div>
            {member.nationality && (
              <div>
                <p className="text-xs text-gray-500 mb-1">Nationality</p>
                <p className="font-semibold">{member.nationality}</p>
              </div>
            )}
            {member.passportExpiry && (
              <div>
                <p className="text-xs text-gray-500 mb-1">Passport Expiry</p>
                <p className="font-semibold">{member.passportExpiry}</p>
              </div>
            )}
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={onEdit}
              className="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
            >
              <Edit2 className="w-4 h-4" aria-hidden="true" />
              Edit
            </button>
            <button
              onClick={onDelete}
              className="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded"
            >
              <Trash2 className="w-4 h-4" aria-hidden="true" />
              Remove
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Family member form component
 */
function FamilyMemberForm({
  member,
  onSubmit,
  onCancel,
  isLoading,
}: {
  member: SchengenFamilyMember | null;
  onSubmit: (data: Partial<SchengenFamilyMember>) => void;
  onCancel: () => void;
  isLoading: boolean;
}) {
  const [name, setName] = useState(member?.name ?? '');
  const [relationship, setRelationship] = useState<SchengenRelationship>(
    member?.relationship ?? 'spouse'
  );
  const [birthDate, setBirthDate] = useState(member?.birthDate ?? '');
  const [nationality, setNationality] = useState(member?.nationality ?? '');
  const [passportNumber, setPassportNumber] = useState(member?.passportNumber ?? '');
  const [passportExpiry, setPassportExpiry] = useState(member?.passportExpiry ?? '');
  const [color, setColor] = useState(member?.color ?? '#3B82F6');
  const [notes, setNotes] = useState(member?.notes ?? '');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit({
      name,
      relationship,
      birthDate: birthDate || null,
      nationality: nationality || null,
      passportNumber: passportNumber || null,
      passportExpiry: passportExpiry || null,
      color,
      notes: notes || null,
    });
  };

  const colors = [
    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
    '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1',
  ];

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Name *
        </label>
        <input
          type="text"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="Full name"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Relationship
        </label>
        <select
          value={relationship}
          onChange={(e) => setRelationship(e.target.value as SchengenRelationship)}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        >
          <option value="spouse">Spouse</option>
          <option value="child">Child</option>
          <option value="parent">Parent</option>
          <option value="sibling">Sibling</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Birth Date
          </label>
          <input
            type="date"
            value={birthDate}
            onChange={(e) => setBirthDate(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Nationality
          </label>
          <input
            type="text"
            value={nationality}
            onChange={(e) => setNationality(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            placeholder="e.g., American"
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Passport Number
          </label>
          <input
            type="text"
            value={passportNumber}
            onChange={(e) => setPassportNumber(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Passport Expiry
          </label>
          <input
            type="date"
            value={passportExpiry}
            onChange={(e) => setPassportExpiry(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Color Tag
        </label>
        <div className="flex flex-wrap gap-2">
          {colors.map((c) => (
            <button
              key={c}
              type="button"
              onClick={() => setColor(c)}
              className={clsx(
                'w-8 h-8 rounded-full transition-all',
                color === c ? 'ring-2 ring-offset-2 ring-gray-400' : ''
              )}
              style={{ backgroundColor: c }}
              aria-label={`Select color ${c}`}
            />
          ))}
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Notes
        </label>
        <textarea
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          rows={2}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          placeholder="Optional notes..."
        />
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={isLoading || !name}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50"
        >
          {isLoading && <Loader2 className="w-4 h-4 animate-spin" />}
          {member ? 'Save Changes' : 'Add Member'}
        </button>
      </div>
    </form>
  );
}
