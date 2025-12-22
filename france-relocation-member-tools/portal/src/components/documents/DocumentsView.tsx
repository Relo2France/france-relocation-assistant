import { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import {
  LayoutGrid,
  LayoutList,
  Search,
  Filter,
  X,
  Plus,
  MapPin,
  Download,
  FileText,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';
import { useDashboard, useFiles, useDownloadFile, useSavedReports } from '@/hooks/useApi';
import FileUpload from './FileUpload';
import FileGrid from './FileGrid';
import FilePreview from './FilePreview';
import { AIVerificationModal } from './AIVerification';
import Modal from '@/components/shared/Modal';
import type { PortalFile, FileCategory, FileType } from '@/types';

const categoryOptions: { value: FileCategory | ''; label: string }[] = [
  { value: '', label: 'All Categories' },
  { value: 'identity', label: 'Identity Documents' },
  { value: 'financial', label: 'Financial' },
  { value: 'housing', label: 'Housing' },
  { value: 'employment', label: 'Employment' },
  { value: 'visa', label: 'Visa & Immigration' },
  { value: 'medical', label: 'Medical' },
  { value: 'education', label: 'Education' },
  { value: 'other', label: 'Other' },
];

const fileTypeOptions: { value: FileType | ''; label: string }[] = [
  { value: '', label: 'All Types' },
  { value: 'document', label: 'Documents' },
  { value: 'pdf', label: 'PDFs' },
  { value: 'image', label: 'Images' },
  { value: 'spreadsheet', label: 'Spreadsheets' },
  { value: 'other', label: 'Other' },
];

export default function DocumentsView() {
  // View state
  const [view, setView] = useState<'grid' | 'list'>('grid');
  const [searchQuery, setSearchQuery] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<FileCategory | ''>('');
  const [typeFilter, setTypeFilter] = useState<FileType | ''>('');

  // Modal state
  const [showUpload, setShowUpload] = useState(false);
  const [selectedFile, setSelectedFile] = useState<PortalFile | null>(null);
  const [showPreview, setShowPreview] = useState(false);
  const [showVerification, setShowVerification] = useState(false);
  const [fileToVerify, setFileToVerify] = useState<PortalFile | null>(null);
  const [showSavedReports, setShowSavedReports] = useState(true);

  // Data
  const { data: dashboard, isLoading: dashboardLoading } = useDashboard();
  const projectId = dashboard?.project?.id || 0;
  const { data: savedReportsData } = useSavedReports();
  const savedReports = savedReportsData?.reports || [];

  const { data: files = [], isLoading: filesLoading, refetch: refetchFiles } = useFiles(
    projectId,
    {
      category: categoryFilter || undefined,
      file_type: typeFilter || undefined,
    }
  );

  const { download } = useDownloadFile();

  // Filter files by search
  const filteredFiles = useMemo(() => {
    if (!searchQuery) return files;
    const query = searchQuery.toLowerCase();
    return files.filter((file) =>
      file.original_name.toLowerCase().includes(query) ||
      file.description?.toLowerCase().includes(query)
    );
  }, [files, searchQuery]);

  // Group files by category for summary
  const categorySummary = useMemo(() => {
    const summary: Record<string, number> = {};
    files.forEach((file) => {
      summary[file.category] = (summary[file.category] || 0) + 1;
    });
    return summary;
  }, [files]);

  const hasActiveFilters = categoryFilter || typeFilter || searchQuery;

  const handleFileClick = (file: PortalFile) => {
    setSelectedFile(file);
    setShowPreview(true);
  };

  const handleDownload = (file: PortalFile) => {
    download(file.id);
  };

  const handleDelete = (file: PortalFile) => {
    // Open preview with delete confirmation
    setSelectedFile(file);
    setShowPreview(true);
  };

  const handleVerify = (file: PortalFile) => {
    setFileToVerify(file);
    setShowVerification(true);
  };

  const handleClosePreview = () => {
    setShowPreview(false);
    setSelectedFile(null);
  };

  const handleCloseVerification = () => {
    setShowVerification(false);
    setFileToVerify(null);
  };

  const clearFilters = () => {
    setSearchQuery('');
    setCategoryFilter('');
    setTypeFilter('');
  };

  // Loading state
  if (dashboardLoading || filesLoading) {
    return <DocumentsViewSkeleton />;
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
        <h1 className="text-2xl font-bold text-gray-900">Documents</h1>
        <p className="text-gray-600 mt-1">
          Manage and organize your relocation documents
        </p>
      </div>

      {/* Saved Research Reports Section */}
      {savedReports.length > 0 && (
        <div className="card mb-6">
          <button
            onClick={() => setShowSavedReports(!showSavedReports)}
            className="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors rounded-t-xl"
          >
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                <MapPin className="w-5 h-5 text-primary-600" />
              </div>
              <div>
                <h2 className="font-semibold text-gray-900">Saved Relocation Reports</h2>
                <p className="text-sm text-gray-500">{savedReports.length} report{savedReports.length !== 1 ? 's' : ''} saved</p>
              </div>
            </div>
            {showSavedReports ? (
              <ChevronUp className="w-5 h-5 text-gray-400" />
            ) : (
              <ChevronDown className="w-5 h-5 text-gray-400" />
            )}
          </button>

          {showSavedReports && (
            <div className="border-t border-gray-100">
              <div className="p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {savedReports.map((report) => (
                  <div
                    key={report.id}
                    className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                      <FileText className="w-5 h-5 text-primary-600" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-gray-900 truncate">{report.location_name}</p>
                      <p className="text-xs text-gray-500 capitalize">
                        {report.location_type} Report • {new Date(report.updated_at).toLocaleDateString()}
                      </p>
                    </div>
                    <a
                      href={report.download_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                      title="Download report"
                    >
                      <Download className="w-5 h-5" />
                    </a>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Category summary cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
        {categoryOptions.slice(1).map((cat) => (
          <button
            key={cat.value}
            onClick={() => setCategoryFilter(categoryFilter === cat.value ? '' : cat.value as FileCategory)}
            className={clsx(
              'card p-3 text-center transition-all hover:shadow-md',
              categoryFilter === cat.value
                ? 'ring-2 ring-primary-500 bg-primary-50'
                : 'hover:bg-gray-50'
            )}
          >
            <div className="text-2xl font-bold text-gray-900">
              {categorySummary[cat.value] || 0}
            </div>
            <div className="text-xs text-gray-600 truncate">{cat.label}</div>
          </button>
        ))}
      </div>

      {/* Toolbar */}
      <div className="card mb-6">
        <div className="p-4">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            {/* Left side - View toggle and search */}
            <div className="flex items-center gap-3 w-full sm:w-auto">
              {/* View toggle */}
              <div className="flex rounded-lg bg-gray-100 p-1">
                <button
                  onClick={() => setView('grid')}
                  className={clsx(
                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                    view === 'grid'
                      ? 'bg-white text-gray-900 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  <LayoutGrid className="w-4 h-4" />
                  Grid
                </button>
                <button
                  onClick={() => setView('list')}
                  className={clsx(
                    'px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center gap-1.5',
                    view === 'list'
                      ? 'bg-white text-gray-900 shadow-sm'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  <LayoutList className="w-4 h-4" />
                  List
                </button>
              </div>

              {/* Search */}
              <div className="relative flex-1 sm:flex-initial">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search files..."
                  className="pl-9 pr-4 py-2 bg-gray-100 border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 w-full sm:w-64"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X className="w-4 h-4" />
                  </button>
                )}
              </div>
            </div>

            {/* Right side - Upload button */}
            <button
              onClick={() => setShowUpload(true)}
              className="btn btn-primary w-full sm:w-auto"
            >
              <Plus className="w-4 h-4" />
              Upload Files
            </button>
          </div>

          {/* Filter row */}
          <div className="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
            <Filter className="w-4 h-4 text-gray-400" />

            {/* Category filter */}
            <select
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value as FileCategory | '')}
              className="select w-auto text-sm py-1.5"
            >
              {categoryOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>

            {/* Type filter */}
            <select
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value as FileType | '')}
              className="select w-auto text-sm py-1.5"
            >
              {fileTypeOptions.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>

            {/* Clear filters */}
            {hasActiveFilters && (
              <button
                onClick={clearFilters}
                className="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
              >
                <X className="w-3 h-3" />
                Clear filters
              </button>
            )}

            {/* File count */}
            <span className="text-sm text-gray-500 ml-auto">
              {filteredFiles.length === files.length
                ? `${files.length} files`
                : `${filteredFiles.length} of ${files.length} files`}
            </span>
          </div>
        </div>
      </div>

      {/* File grid/list */}
      <FileGrid
        files={filteredFiles}
        view={view}
        onFileClick={handleFileClick}
        onDownload={handleDownload}
        onDelete={handleDelete}
        onVerify={handleVerify}
        isLoading={filesLoading}
      />

      {/* Empty state when filters return no results */}
      {filteredFiles.length === 0 && files.length > 0 && (
        <div className="card p-8 text-center mt-6">
          <p className="text-gray-500">No files match your filters</p>
          <button
            onClick={clearFilters}
            className="mt-2 text-primary-600 hover:text-primary-700"
          >
            Clear filters
          </button>
        </div>
      )}

      {/* Upload modal */}
      <Modal
        isOpen={showUpload}
        onClose={() => setShowUpload(false)}
        title="Upload Documents"
        size="lg"
      >
        <FileUpload
          projectId={projectId}
          onUploadComplete={() => {
            refetchFiles();
          }}
        />
        <div className="mt-6 pt-4 border-t border-gray-100">
          <h4 className="text-sm font-medium text-gray-900 mb-2">Tips for organizing documents:</h4>
          <ul className="text-sm text-gray-600 space-y-1">
            <li>• Keep passport and ID scans in Identity Documents</li>
            <li>• Bank statements and tax returns go in Financial</li>
            <li>• Rental agreements and utility bills in Housing</li>
            <li>• Work contracts and pay stubs in Employment</li>
          </ul>
        </div>
      </Modal>

      {/* File preview */}
      <FilePreview
        file={selectedFile}
        isOpen={showPreview}
        onClose={handleClosePreview}
        projectId={projectId}
      />

      {/* AI Verification */}
      <AIVerificationModal
        isOpen={showVerification}
        onClose={handleCloseVerification}
        projectId={projectId}
        existingFileId={fileToVerify?.id}
      />
    </div>
  );
}

function DocumentsViewSkeleton() {
  return (
    <div className="p-6">
      <div className="h-12 bg-gray-200 rounded-lg animate-pulse mb-6 w-48" />

      {/* Category cards skeleton */}
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
        {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
          <div key={i} className="card p-3">
            <div className="h-8 bg-gray-200 rounded animate-pulse mb-1" />
            <div className="h-3 bg-gray-100 rounded animate-pulse w-2/3 mx-auto" />
          </div>
        ))}
      </div>

      {/* Toolbar skeleton */}
      <div className="card mb-6">
        <div className="p-4">
          <div className="h-10 bg-gray-200 rounded-lg animate-pulse" />
        </div>
      </div>

      {/* Grid skeleton */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        {[1, 2, 3, 4, 5, 6].map((i) => (
          <div key={i} className="card">
            <div className="aspect-square bg-gray-100 animate-pulse" />
            <div className="p-3 space-y-2">
              <div className="h-4 bg-gray-200 rounded animate-pulse" />
              <div className="h-3 bg-gray-100 rounded animate-pulse w-1/2" />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
