import { useState, useCallback, useRef } from 'react';
import { clsx } from 'clsx';
import { Upload, X, File, CheckCircle, AlertCircle } from 'lucide-react';
import { useUploadFile } from '@/hooks/useApi';
import type { FileCategory } from '@/types';

interface FileUploadProps {
  projectId: number;
  onUploadComplete?: () => void;
  className?: string;
}

interface UploadingFile {
  id: string;
  file: File;
  progress: number;
  status: 'pending' | 'uploading' | 'success' | 'error';
  error?: string;
  category?: FileCategory;
}

const ALLOWED_TYPES = [
  'application/pdf',
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'text/plain',
  'text/csv',
];

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

export default function FileUpload({ projectId, onUploadComplete, className }: FileUploadProps) {
  const [isDragOver, setIsDragOver] = useState(false);
  const [uploadQueue, setUploadQueue] = useState<UploadingFile[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const uploadFile = useUploadFile(projectId);

  const validateFile = (file: File): string | null => {
    if (!ALLOWED_TYPES.includes(file.type)) {
      return 'File type not allowed';
    }
    if (file.size > MAX_FILE_SIZE) {
      return 'File size exceeds 10MB limit';
    }
    return null;
  };

  const handleFiles = useCallback((files: FileList | File[]) => {
    const fileArray = Array.from(files);

    const newUploads: UploadingFile[] = fileArray.map((file) => {
      const error = validateFile(file);
      return {
        id: `${file.name}-${Date.now()}`,
        file,
        progress: 0,
        status: error ? 'error' : 'pending',
        error: error || undefined,
      };
    });

    setUploadQueue((prev) => [...prev, ...newUploads]);

    // Start uploading valid files
    newUploads.filter((u) => u.status === 'pending').forEach((upload) => {
      processUpload(upload);
    });
  }, []);

  const processUpload = async (upload: UploadingFile) => {
    setUploadQueue((prev) =>
      prev.map((u) =>
        u.id === upload.id ? { ...u, status: 'uploading', progress: 10 } : u
      )
    );

    try {
      await uploadFile.mutateAsync({
        file: upload.file,
        data: { category: upload.category },
      });

      setUploadQueue((prev) =>
        prev.map((u) =>
          u.id === upload.id ? { ...u, status: 'success', progress: 100 } : u
        )
      );

      // Remove successful upload after delay
      setTimeout(() => {
        setUploadQueue((prev) => prev.filter((u) => u.id !== upload.id));
      }, 2000);

      onUploadComplete?.();
    } catch (error) {
      setUploadQueue((prev) =>
        prev.map((u) =>
          u.id === upload.id
            ? {
                ...u,
                status: 'error',
                error: error instanceof Error ? error.message : 'Upload failed',
              }
            : u
        )
      );
    }
  };

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    if (e.dataTransfer.files) {
      handleFiles(e.dataTransfer.files);
    }
  }, [handleFiles]);

  const handleClick = () => {
    fileInputRef.current?.click();
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      handleFiles(e.target.files);
    }
    // Reset input
    e.target.value = '';
  };

  const removeFromQueue = (id: string) => {
    setUploadQueue((prev) => prev.filter((u) => u.id !== id));
  };

  return (
    <div className={className}>
      {/* Drop zone */}
      <div
        onClick={handleClick}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        className={clsx(
          'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors',
          isDragOver
            ? 'border-primary-500 bg-primary-50'
            : 'border-gray-300 hover:border-primary-400 hover:bg-gray-50'
        )}
      >
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept={ALLOWED_TYPES.join(',')}
          onChange={handleFileSelect}
          className="hidden"
        />

        <div className="flex flex-col items-center">
          <div className={clsx(
            'w-12 h-12 rounded-full flex items-center justify-center mb-4',
            isDragOver ? 'bg-primary-100' : 'bg-gray-100'
          )}>
            <Upload className={clsx(
              'w-6 h-6',
              isDragOver ? 'text-primary-600' : 'text-gray-500'
            )} />
          </div>

          <p className="text-gray-700 font-medium mb-1">
            {isDragOver ? 'Drop files here' : 'Drag and drop files here'}
          </p>
          <p className="text-sm text-gray-500 mb-3">
            or click to browse
          </p>
          <p className="text-xs text-gray-400">
            PDF, Images, Word, Excel up to 10MB
          </p>
        </div>
      </div>

      {/* Upload queue */}
      {uploadQueue.length > 0 && (
        <div className="mt-4 space-y-2">
          {uploadQueue.map((upload) => (
            <div
              key={upload.id}
              className={clsx(
                'flex items-center gap-3 p-3 rounded-lg border',
                upload.status === 'error' ? 'border-red-200 bg-red-50' :
                upload.status === 'success' ? 'border-green-200 bg-green-50' :
                'border-gray-200 bg-gray-50'
              )}
            >
              {/* Icon */}
              {upload.status === 'success' ? (
                <CheckCircle className="w-5 h-5 text-green-500 flex-shrink-0" />
              ) : upload.status === 'error' ? (
                <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
              ) : (
                <File className="w-5 h-5 text-gray-400 flex-shrink-0" />
              )}

              {/* Info */}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate">
                  {upload.file.name}
                </p>
                {upload.status === 'error' ? (
                  <p className="text-xs text-red-600">{upload.error}</p>
                ) : upload.status === 'uploading' ? (
                  <div className="mt-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-primary-500 transition-all"
                      style={{ width: `${upload.progress}%` }}
                    />
                  </div>
                ) : upload.status === 'success' ? (
                  <p className="text-xs text-green-600">Uploaded successfully</p>
                ) : null}
              </div>

              {/* Remove button */}
              {(upload.status === 'error' || upload.status === 'pending') && (
                <button
                  onClick={() => removeFromQueue(upload.id)}
                  className="p-1 text-gray-400 hover:text-gray-600"
                >
                  <X className="w-4 h-4" />
                </button>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
