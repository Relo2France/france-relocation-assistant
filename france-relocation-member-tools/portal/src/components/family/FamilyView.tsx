import { useState } from 'react';
import { clsx } from 'clsx';
import {
  Users,
  UserPlus,
  Edit2,
  Trash2,
  X,
  Calendar,
  Flag,
  FileText,
  CheckCircle,
  AlertCircle,
  User,
  Baby,
  Heart,
} from 'lucide-react';
import { useDashboard } from '@/hooks/useApi';

// Types for family members
interface FamilyMember {
  id: number;
  name: string;
  relationship: 'spouse' | 'child' | 'parent' | 'other';
  birthDate: string;
  nationality: string;
  visaStatus: 'pending' | 'applied' | 'approved' | 'not_required';
  documents: {
    passport: boolean;
    birthCertificate: boolean;
    marriageCertificate?: boolean;
    photos: boolean;
  };
  notes?: string;
}

// Mock data - in real implementation this would come from API
const mockFamilyMembers: FamilyMember[] = [
  {
    id: 1,
    name: 'Sarah Johnson',
    relationship: 'spouse',
    birthDate: '1985-06-15',
    nationality: 'American',
    visaStatus: 'applied',
    documents: {
      passport: true,
      birthCertificate: true,
      marriageCertificate: true,
      photos: true,
    },
  },
  {
    id: 2,
    name: 'Emma Johnson',
    relationship: 'child',
    birthDate: '2015-03-22',
    nationality: 'American',
    visaStatus: 'applied',
    documents: {
      passport: true,
      birthCertificate: true,
      photos: true,
    },
    notes: 'Will need school enrollment documents',
  },
  {
    id: 3,
    name: 'James Johnson',
    relationship: 'child',
    birthDate: '2018-11-08',
    nationality: 'American',
    visaStatus: 'applied',
    documents: {
      passport: true,
      birthCertificate: false,
      photos: true,
    },
    notes: 'Birth certificate translation pending',
  },
];

const relationshipLabels: Record<string, string> = {
  spouse: 'Spouse',
  child: 'Child',
  parent: 'Parent',
  other: 'Other',
};

const relationshipIcons: Record<string, React.ElementType> = {
  spouse: Heart,
  child: Baby,
  parent: User,
  other: User,
};

const visaStatusConfig: Record<string, { label: string; color: string; icon: React.ElementType }> = {
  pending: { label: 'Pending', color: 'bg-gray-100 text-gray-600', icon: AlertCircle },
  applied: { label: 'Applied', color: 'bg-blue-100 text-blue-600', icon: FileText },
  approved: { label: 'Approved', color: 'bg-green-100 text-green-600', icon: CheckCircle },
  not_required: { label: 'Not Required', color: 'bg-gray-100 text-gray-500', icon: CheckCircle },
};

export default function FamilyView() {
  const { data: dashboard, isLoading } = useDashboard();
  const [showAddModal, setShowAddModal] = useState(false);
  const [editingMember, setEditingMember] = useState<FamilyMember | null>(null);

  // Use mock data for now
  const familyMembers = mockFamilyMembers;

  if (isLoading) {
    return <FamilyViewSkeleton />;
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

  const documentsComplete = familyMembers.filter((m) => {
    const docs = m.documents;
    return docs.passport && docs.birthCertificate && docs.photos &&
           (m.relationship !== 'spouse' || docs.marriageCertificate);
  }).length;

  const visaApproved = familyMembers.filter((m) =>
    m.visaStatus === 'approved' || m.visaStatus === 'not_required'
  ).length;

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Family Members</h1>
          <p className="text-gray-600 mt-1">
            Manage your family's relocation details and documents
          </p>
        </div>
        <button
          onClick={() => setShowAddModal(true)}
          className="btn btn-primary flex items-center gap-2"
        >
          <UserPlus className="w-4 h-4" />
          Add Member
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-primary-100 rounded-lg">
              <Users className="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{familyMembers.length}</p>
              <p className="text-sm text-gray-500">Family Members</p>
            </div>
          </div>
        </div>
        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-green-100 rounded-lg">
              <FileText className="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {documentsComplete}/{familyMembers.length}
              </p>
              <p className="text-sm text-gray-500">Documents Complete</p>
            </div>
          </div>
        </div>
        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 rounded-lg">
              <CheckCircle className="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {visaApproved}/{familyMembers.length}
              </p>
              <p className="text-sm text-gray-500">Visas Ready</p>
            </div>
          </div>
        </div>
      </div>

      {/* Family members list */}
      {familyMembers.length === 0 ? (
        <div className="card p-12 text-center">
          <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
            <Users className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">No family members added</h3>
          <p className="text-gray-600 mb-4">
            Add family members to track their visa applications and documents
          </p>
          <button
            onClick={() => setShowAddModal(true)}
            className="btn btn-primary inline-flex items-center gap-2"
          >
            <UserPlus className="w-4 h-4" />
            Add First Member
          </button>
        </div>
      ) : (
        <div className="space-y-4">
          {familyMembers.map((member) => (
            <FamilyMemberCard
              key={member.id}
              member={member}
              onEdit={() => setEditingMember(member)}
            />
          ))}
        </div>
      )}

      {/* Add/Edit Modal */}
      {(showAddModal || editingMember) && (
        <FamilyMemberModal
          member={editingMember}
          onClose={() => {
            setShowAddModal(false);
            setEditingMember(null);
          }}
          onSave={() => {
            setShowAddModal(false);
            setEditingMember(null);
          }}
        />
      )}
    </div>
  );
}

interface FamilyMemberCardProps {
  member: FamilyMember;
  onEdit: () => void;
}

function FamilyMemberCard({ member, onEdit }: FamilyMemberCardProps) {
  const RelationshipIcon = relationshipIcons[member.relationship];
  const visaConfig = visaStatusConfig[member.visaStatus];
  const VisaIcon = visaConfig.icon;

  const calculateAge = (birthDate: string) => {
    const birth = new Date(birthDate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  };

  const documentsList = [
    { key: 'passport', label: 'Passport' },
    { key: 'birthCertificate', label: 'Birth Certificate' },
    ...(member.relationship === 'spouse' ? [{ key: 'marriageCertificate', label: 'Marriage Certificate' }] : []),
    { key: 'photos', label: 'Photos' },
  ];

  const completedDocs = documentsList.filter(
    (doc) => member.documents[doc.key as keyof typeof member.documents]
  ).length;

  return (
    <div className="card p-5">
      <div className="flex items-start gap-4">
        {/* Avatar/Icon */}
        <div className="p-3 bg-primary-50 rounded-full">
          <RelationshipIcon className="w-6 h-6 text-primary-600" />
        </div>

        {/* Main content */}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between">
            <div>
              <h3 className="font-semibold text-gray-900">{member.name}</h3>
              <p className="text-sm text-gray-500">
                {relationshipLabels[member.relationship]} &middot; {calculateAge(member.birthDate)} years old
              </p>
            </div>
            <button
              onClick={onEdit}
              className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <Edit2 className="w-4 h-4" />
            </button>
          </div>

          {/* Info row */}
          <div className="flex flex-wrap items-center gap-4 mt-3 text-sm">
            <div className="flex items-center gap-1 text-gray-600">
              <Flag className="w-4 h-4" />
              {member.nationality}
            </div>
            <div className="flex items-center gap-1 text-gray-600">
              <Calendar className="w-4 h-4" />
              {new Date(member.birthDate).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
              })}
            </div>
            <div className={clsx('flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium', visaConfig.color)}>
              <VisaIcon className="w-3 h-3" />
              {visaConfig.label}
            </div>
          </div>

          {/* Documents progress */}
          <div className="mt-4">
            <div className="flex items-center justify-between text-sm mb-2">
              <span className="text-gray-600">Documents</span>
              <span className="font-medium text-gray-900">
                {completedDocs}/{documentsList.length} complete
              </span>
            </div>
            <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div
                className={clsx(
                  'h-full rounded-full transition-all',
                  completedDocs === documentsList.length ? 'bg-green-500' : 'bg-primary-500'
                )}
                style={{ width: `${(completedDocs / documentsList.length) * 100}%` }}
              />
            </div>
            <div className="flex flex-wrap gap-2 mt-2">
              {documentsList.map((doc) => {
                const isComplete = member.documents[doc.key as keyof typeof member.documents];
                return (
                  <span
                    key={doc.key}
                    className={clsx(
                      'text-xs px-2 py-1 rounded-full',
                      isComplete
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-600'
                    )}
                  >
                    {isComplete ? '✓' : '!'} {doc.label}
                  </span>
                );
              })}
            </div>
          </div>

          {/* Notes */}
          {member.notes && (
            <div className="mt-3 p-3 bg-yellow-50 rounded-lg">
              <p className="text-sm text-yellow-800">{member.notes}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

interface FamilyMemberModalProps {
  member: FamilyMember | null;
  onClose: () => void;
  onSave: () => void;
}

function FamilyMemberModal({ member, onClose, onSave }: FamilyMemberModalProps) {
  const [formData, setFormData] = useState({
    name: member?.name || '',
    relationship: member?.relationship || 'spouse',
    birthDate: member?.birthDate || '',
    nationality: member?.nationality || '',
    notes: member?.notes || '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // In real implementation, this would call an API
    onSave();
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between p-4 border-b">
          <h2 className="text-lg font-semibold text-gray-900">
            {member ? 'Edit Family Member' : 'Add Family Member'}
          </h2>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-gray-600 rounded-lg"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Full Name
            </label>
            <input
              type="text"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Relationship
            </label>
            <select
              value={formData.relationship}
              onChange={(e) => setFormData({ ...formData, relationship: e.target.value as FamilyMember['relationship'] })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option value="spouse">Spouse</option>
              <option value="child">Child</option>
              <option value="parent">Parent</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Date of Birth
            </label>
            <input
              type="date"
              value={formData.birthDate}
              onChange={(e) => setFormData({ ...formData, birthDate: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Nationality
            </label>
            <input
              type="text"
              value={formData.nationality}
              onChange={(e) => setFormData({ ...formData, nationality: e.target.value })}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Notes (optional)
            </label>
            <textarea
              value={formData.notes}
              onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="flex-1 btn btn-primary"
            >
              {member ? 'Save Changes' : 'Add Member'}
            </button>
          </div>

          {member && (
            <button
              type="button"
              className="w-full mt-2 flex items-center justify-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            >
              <Trash2 className="w-4 h-4" />
              Remove Family Member
            </button>
          )}
        </form>
      </div>
    </div>
  );
}

function FamilyViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-8 w-48 bg-gray-200 rounded animate-pulse mb-6" />
      <div className="grid grid-cols-3 gap-4 mb-6">
        {[1, 2, 3].map((i) => (
          <div key={i} className="card p-4">
            <div className="h-12 bg-gray-200 rounded animate-pulse" />
          </div>
        ))}
      </div>
      <div className="space-y-4">
        {[1, 2].map((i) => (
          <div key={i} className="card p-5">
            <div className="h-24 bg-gray-200 rounded animate-pulse" />
          </div>
        ))}
      </div>
    </div>
  );
}
