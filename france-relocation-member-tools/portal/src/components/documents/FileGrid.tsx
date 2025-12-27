import { clsx } from 'clsx';
import {
  FileText,
  Image,
  FileSpreadsheet,
  File,
  Download,
  Trash2,
  MoreVertical,
  Eye,
  FolderOpen,
  Shield,
} from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import { VirtualGrid } from '@/components/shared/VirtualList';
import { useVirtualization } from '@/hooks/useVirtualization';
import type { PortalFile, FileType } from '@/types';

interface FileGridProps {
  files: PortalFile[];
  view: 'grid' | 'list';
  onFileClick: (file: PortalFile) => void;
  onDownload: (file: PortalFile) => void;
  onDelete: (file: PortalFile) => void;
  onVerify?: (file: PortalFile) => void;
  isLoading?: boolean;
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
  document: 'text-blue-500 bg-blue-50',
  image: 'text-purple-500 bg-purple-50',
  pdf: 'text-red-500 bg-red-50',
  spreadsheet: 'text-green-500 bg-green-50',
  archive: 'text-yellow-500 bg-yellow-50',
  other: 'text-gray-500 bg-gray-50',
};

export default function FileGrid({
  files,
  view,
  onFileClick,
  onDownload,
  onDelete,
  onVerify,
  isLoading,
}: FileGridProps) {
  const shouldVirtualize = useVirtualization(files.length, 40);

  // Determine column count based on viewport with resize handling
  const [columns, setColumns] = useState(() => {
    if (typeof window === 'undefined') return 4;
    const width = window.innerWidth;
    if (width < 768) return 2;
    if (width < 1024) return 3;
    if (width < 1280) return 4;
    return 5;
  });

  useEffect(() => {
    const updateColumns = () => {
      const width = window.innerWidth;
      if (width < 768) setColumns(2);
      else if (width < 1024) setColumns(3);
      else if (width < 1280) setColumns(4);
      else setColumns(5);
    };

    window.addEventListener('resize', updateColumns);
    return () => window.removeEventListener('resize', updateColumns);
  }, []);

  if (isLoading) {
    return view === 'grid' ? <GridSkeleton /> : <ListSkeleton />;
  }

  if (files.length === 0) {
    return (
      <div className="card p-12 text-center">
        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
          <FolderOpen className="w-8 h-8 text-gray-400" />
        </div>
        <h3 className="text-lg font-semibold text-gray-900 mb-2">No documents yet</h3>
        <p className="text-gray-600">
          Upload your first document to get started
        </p>
      </div>
    );
  }

  if (view === 'grid') {
    // Use virtual scrolling for large file collections
    if (shouldVirtualize) {
      return (
        <VirtualGrid
          items={files}
          columns={columns}
          estimateSize={220}
          gap={16}
          className="max-h-[calc(100vh-200px)]"
          getItemKey={(file) => file.id}
          renderItem={(file) => (
            <FileCardGrid
              file={file}
              onClick={() => onFileClick(file)}
              onDownload={() => onDownload(file)}
              onDelete={() => onDelete(file)}
              onVerify={onVerify ? () => onVerify(file) : undefined}
            />
          )}
        />
      );
    }

    return (
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        {files.map((file) => (
          <FileCardGrid
            key={file.id}
            file={file}
            onClick={() => onFileClick(file)}
            onDownload={() => onDownload(file)}
            onDelete={() => onDelete(file)}
            onVerify={onVerify ? () => onVerify(file) : undefined}
          />
        ))}
      </div>
    );
  }

  return (
    <div className="card overflow-hidden">
      <table className="w-full">
        <thead className="bg-gray-50 border-b border-gray-200">
          <tr>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200">
          {files.map((file) => (
            <FileRowList
              key={file.id}
              file={file}
              onClick={() => onFileClick(file)}
              onDownload={() => onDownload(file)}
              onDelete={() => onDelete(file)}
              onVerify={onVerify ? () => onVerify(file) : undefined}
            />
          ))}
        </tbody>
      </table>
    </div>
  );
}

interface FileItemProps {
  file: PortalFile;
  onClick: () => void;
  onDownload: () => void;
  onDelete: () => void;
  onVerify?: () => void;
}

function FileCardGrid({ file, onClick, onDownload, onDelete, onVerify }: FileItemProps) {
  const [showMenu, setShowMenu] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);
  const Icon = fileTypeIcons[file.file_type] || File;
  const colorClass = fileTypeColors[file.file_type] || fileTypeColors.other;

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setShowMenu(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  return (
    <div className="card group hover:shadow-md transition-shadow">
      {/* Preview area */}
      <div
        onClick={onClick}
        className="aspect-square p-4 flex items-center justify-center cursor-pointer bg-gray-50 border-b border-gray-100"
      >
        {file.thumbnail_url ? (
          <img
            src={file.thumbnail_url}
            alt={file.original_name}
            className="max-w-full max-h-full object-contain rounded"
          />
        ) : (
          <div className={clsx('w-16 h-16 rounded-xl flex items-center justify-center', colorClass)}>
            <Icon className="w-8 h-8" />
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-3">
        <div className="flex items-start justify-between gap-2">
          <div className="min-w-0 flex-1">
            <p className="text-sm font-medium text-gray-900 truncate" title={file.original_name}>
              {file.original_name}
            </p>
            <p className="text-xs text-gray-500 mt-0.5">
              {file.file_size_formatted}
            </p>
          </div>

          {/* Menu */}
          <div className="relative" ref={menuRef}>
            <button
              onClick={() => setShowMenu(!showMenu)}
              className="p-1 text-gray-400 hover:text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <MoreVertical className="w-4 h-4" />
            </button>

            {showMenu && (
              <div className="absolute right-0 top-full mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                <button
                  onClick={() => { onClick(); setShowMenu(false); }}
                  className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                >
                  <Eye className="w-4 h-4" />
                  Preview
                </button>
                <button
                  onClick={() => { onDownload(); setShowMenu(false); }}
                  className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                >
                  <Download className="w-4 h-4" />
                  Download
                </button>
                {onVerify && (
                  <button
                    onClick={() => { onVerify(); setShowMenu(false); }}
                    className="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                  >
                    <Shield className="w-4 h-4" />
                    Verify
                  </button>
                )}
                <hr className="my-1 border-gray-100" />
                <button
                  onClick={() => { onDelete(); setShowMenu(false); }}
                  className="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                >
                  <Trash2 className="w-4 h-4" />
                  Delete
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function FileRowList({ file, onClick, onDownload, onDelete, onVerify }: FileItemProps) {
  const Icon = fileTypeIcons[file.file_type] || File;
  const colorClass = fileTypeColors[file.file_type] || fileTypeColors.other;

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  };

  return (
    <tr className="hover:bg-gray-50 group">
      <td className="px-4 py-3">
        <div className="flex items-center gap-3">
          <div className={clsx('w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', colorClass)}>
            <Icon className="w-5 h-5" />
          </div>
          <div className="min-w-0">
            <button
              onClick={onClick}
              className="text-sm font-medium text-gray-900 hover:text-primary-600 truncate block max-w-xs"
              title={file.original_name}
            >
              {file.original_name}
            </button>
            <p className="text-xs text-gray-500">{file.file_type_label}</p>
          </div>
        </div>
      </td>
      <td className="px-4 py-3">
        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
          {file.category_label}
        </span>
      </td>
      <td className="px-4 py-3 text-sm text-gray-500">
        {file.file_size_formatted}
      </td>
      <td className="px-4 py-3 text-sm text-gray-500">
        {formatDate(file.created_at)}
      </td>
      <td className="px-4 py-3">
        <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <button
            onClick={onClick}
            className="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            title="Preview"
          >
            <Eye className="w-4 h-4" />
          </button>
          <button
            onClick={onDownload}
            className="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            title="Download"
          >
            <Download className="w-4 h-4" />
          </button>
          {onVerify && (
            <button
              onClick={onVerify}
              className="p-1.5 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-primary-50"
              title="Verify Document"
            >
              <Shield className="w-4 h-4" />
            </button>
          )}
          <button
            onClick={onDelete}
            className="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50"
            title="Delete"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      </td>
    </tr>
  );
}

function GridSkeleton() {
  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
      {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
        <div key={i} className="card">
          <div className="aspect-square bg-gray-100 animate-pulse" />
          <div className="p-3 space-y-2">
            <div className="h-4 bg-gray-200 rounded animate-pulse" />
            <div className="h-3 bg-gray-100 rounded animate-pulse w-1/2" />
          </div>
        </div>
      ))}
    </div>
  );
}

function ListSkeleton() {
  return (
    <div className="card overflow-hidden">
      <div className="bg-gray-50 h-12 animate-pulse" />
      <div className="divide-y divide-gray-200">
        {[1, 2, 3, 4, 5].map((i) => (
          <div key={i} className="px-4 py-3 flex items-center gap-4">
            <div className="w-10 h-10 bg-gray-200 rounded-lg animate-pulse" />
            <div className="flex-1 space-y-2">
              <div className="h-4 bg-gray-200 rounded animate-pulse w-1/3" />
              <div className="h-3 bg-gray-100 rounded animate-pulse w-1/4" />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
