import { useState } from 'react';
import { clsx } from 'clsx';
import {
  X,
  Download,
  Trash2,
  FileText,
  Image,
  FileSpreadsheet,
  File,
  ExternalLink,
  Calendar,
  HardDrive,
  Tag,
  User,
} from 'lucide-react';
import Modal from '@/components/shared/Modal';
import { useDeleteFile, useDownloadFile, useUpdateFile } from '@/hooks/useApi';
import type { PortalFile, FileType, FileCategory } from '@/types';

interface FilePreviewProps {
  file: PortalFile | null;
  isOpen: boolean;
  onClose: () => void;
  projectId: number;
}

const fileTypeIcons: Record<FileType, React.ElementType> = {
  document: FileText,
  image: Image,
  pdf: FileText,
  spreadsheet: FileSpreadsheet,
  archive: File,
  other: File,
};

const fileTypeColors: Record<FileType, string> = {
  document: 'text-blue-500 bg-blue-100',
  image: 'text-purple-500 bg-purple-100',
  pdf: 'text-red-500 bg-red-100',
  spreadsheet: 'text-green-500 bg-green-100',
  archive: 'text-yellow-500 bg-yellow-100',
  other: 'text-gray-500 bg-gray-100',
};

const categoryOptions: { value: FileCategory; label: string }[] = [
  { value: 'identity', label: 'Identity Documents' },
  { value: 'financial', label: 'Financial' },
  { value: 'housing', label: 'Housing' },
  { value: 'employment', label: 'Employment' },
  { value: 'visa', label: 'Visa & Immigration' },
  { value: 'medical', label: 'Medical' },
  { value: 'education', label: 'Education' },
  { value: 'other', label: 'Other' },
];

export default function FilePreview({ file, isOpen, onClose, projectId }: FilePreviewProps) {
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editCategory, setEditCategory] = useState<FileCategory | null>(null);
  const [editDescription, setEditDescription] = useState('');

  const deleteFile = useDeleteFile();
  const updateFile = useUpdateFile();
  const { download } = useDownloadFile();

  if (!file) return null;

  const Icon = fileTypeIcons[file.file_type] || File;
  const colorClass = fileTypeColors[file.file_type] || fileTypeColors.other;

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleDownload = () => {
    download(file.id);
  };

  const handleDelete = () => {
    deleteFile.mutate(
      { id: file.id, projectId },
      {
        onSuccess: () => {
          setShowDeleteConfirm(false);
          onClose();
        },
      }
    );
  };

  const handleStartEdit = () => {
    setEditCategory(file.category);
    setEditDescription(file.description || '');
    setIsEditing(true);
  };

  const handleSaveEdit = () => {
    updateFile.mutate(
      {
        id: file.id,
        data: {
          category: editCategory || undefined,
          description: editDescription || undefined,
        },
      },
      {
        onSuccess: () => {
          setIsEditing(false);
        },
      }
    );
  };

  const canPreview = file.file_type === 'image' || file.file_type === 'pdf';

  return (
    <>
      <Modal
        isOpen={isOpen}
        onClose={onClose}
        title=""
        size="xl"
        showHeader={false}
      >
        <div className="flex flex-col lg:flex-row h-[80vh] max-h-[700px]">
          {/* Preview area */}
          <div className="flex-1 bg-gray-100 flex items-center justify-center p-4 min-h-[300px]">
            {file.file_type === 'image' && file.preview_url ? (
              <img
                src={file.preview_url}
                alt={file.original_name}
                className="max-w-full max-h-full object-contain rounded-lg shadow-sm"
              />
            ) : file.file_type === 'pdf' && file.preview_url ? (
              <iframe
                src={file.preview_url}
                className="w-full h-full rounded-lg border-0"
                title={file.original_name}
              />
            ) : (
              <div className="text-center">
                <div className={clsx('w-24 h-24 rounded-2xl flex items-center justify-center mx-auto mb-4', colorClass)}>
                  <Icon className="w-12 h-12" />
                </div>
                <p className="text-gray-600 mb-4">Preview not available</p>
                <button
                  onClick={handleDownload}
                  className="btn btn-primary"
                >
                  <Download className="w-4 h-4" />
                  Download to View
                </button>
              </div>
            )}
          </div>

          {/* Details panel */}
          <div className="w-full lg:w-80 border-t lg:border-t-0 lg:border-l border-gray-200 flex flex-col">
            {/* Header */}
            <div className="p-4 border-b border-gray-200">
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                  <h3 className="font-semibold text-gray-900 truncate" title={file.original_name}>
                    {file.original_name}
                  </h3>
                  <p className="text-sm text-gray-500">{file.file_type_label}</p>
                </div>
                <button
                  onClick={onClose}
                  className="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
            </div>

            {/* Details */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4">
              {/* Actions */}
              <div className="flex gap-2">
                <button
                  onClick={handleDownload}
                  className="btn btn-secondary flex-1"
                >
                  <Download className="w-4 h-4" />
                  Download
                </button>
                {canPreview && file.preview_url && (
                  <a
                    href={file.preview_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-secondary"
                  >
                    <ExternalLink className="w-4 h-4" />
                  </a>
                )}
              </div>

              {/* Metadata */}
              <div className="space-y-3">
                <div className="flex items-center gap-3 text-sm">
                  <HardDrive className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-600">Size:</span>
                  <span className="text-gray-900">{file.file_size_formatted}</span>
                </div>

                <div className="flex items-center gap-3 text-sm">
                  <Calendar className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-600">Uploaded:</span>
                  <span className="text-gray-900">{formatDate(file.created_at)}</span>
                </div>

                <div className="flex items-center gap-3 text-sm">
                  <User className="w-4 h-4 text-gray-400" />
                  <span className="text-gray-600">By:</span>
                  <span className="text-gray-900">{file.uploaded_by_name}</span>
                </div>

                {/* Category */}
                <div className="flex items-start gap-3 text-sm">
                  <Tag className="w-4 h-4 text-gray-400 mt-0.5" />
                  <span className="text-gray-600">Category:</span>
                  {isEditing ? (
                    <select
                      value={editCategory || ''}
                      onChange={(e) => setEditCategory(e.target.value as FileCategory)}
                      className="select text-sm py-1"
                    >
                      {categoryOptions.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                          {opt.label}
                        </option>
                      ))}
                    </select>
                  ) : (
                    <span className="text-gray-900">{file.category_label}</span>
                  )}
                </div>

                {/* Description */}
                <div className="pt-3 border-t border-gray-100">
                  <label htmlFor="file-description" className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  {isEditing ? (
                    <textarea
                      id="file-description"
                      value={editDescription}
                      onChange={(e) => setEditDescription(e.target.value)}
                      className="textarea text-sm"
                      rows={3}
                      placeholder="Add a description..."
                    />
                  ) : (
                    <p id="file-description" className="text-sm text-gray-600">
                      {file.description || 'No description'}
                    </p>
                  )}
                </div>

                {/* Edit controls */}
                {isEditing ? (
                  <div className="flex gap-2">
                    <button
                      onClick={handleSaveEdit}
                      disabled={updateFile.isPending}
                      className="btn btn-primary btn-sm flex-1"
                    >
                      {updateFile.isPending ? 'Saving...' : 'Save'}
                    </button>
                    <button
                      onClick={() => setIsEditing(false)}
                      className="btn btn-secondary btn-sm"
                    >
                      Cancel
                    </button>
                  </div>
                ) : (
                  <button
                    onClick={handleStartEdit}
                    className="text-sm text-primary-600 hover:text-primary-700"
                  >
                    Edit details
                  </button>
                )}

                {/* Generated badge */}
                {file.is_generated && (
                  <div className="flex items-center gap-2 p-2 bg-primary-50 rounded-lg text-sm">
                    <span className="text-primary-700">Auto-generated document</span>
                  </div>
                )}
              </div>
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-gray-200">
              <button
                onClick={() => setShowDeleteConfirm(true)}
                className="w-full btn btn-secondary text-red-600 hover:bg-red-50 hover:border-red-200"
              >
                <Trash2 className="w-4 h-4" />
                Delete File
              </button>
            </div>
          </div>
        </div>
      </Modal>

      {/* Delete confirmation modal */}
      <Modal
        isOpen={showDeleteConfirm}
        onClose={() => setShowDeleteConfirm(false)}
        title="Delete File"
        size="sm"
        footer={
          <>
            <button
              onClick={() => setShowDeleteConfirm(false)}
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button
              onClick={handleDelete}
              disabled={deleteFile.isPending}
              className="btn bg-red-600 text-white hover:bg-red-700"
            >
              {deleteFile.isPending ? 'Deleting...' : 'Delete'}
            </button>
          </>
        }
      >
        <p className="text-gray-600">
          Are you sure you want to delete <strong>{file.original_name}</strong>? This action cannot be undone.
        </p>
      </Modal>
    </>
  );
}
