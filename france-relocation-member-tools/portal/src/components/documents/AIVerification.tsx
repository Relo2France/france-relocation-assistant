import { useState, useCallback } from 'react';
import { clsx } from 'clsx';
import {
  Shield,
  ShieldCheck,
  ShieldX,
  ShieldAlert,
  Upload,
  FileText,
  AlertCircle,
  CheckCircle,
  Clock,
  ChevronDown,
  ChevronUp,
  X,
} from 'lucide-react';
import { useVerifyDocument, useVerificationHistory } from '@/hooks/useApi';
import Modal from '@/components/shared/Modal';
import type { VerificationResult, VerificationType, VerificationStatus } from '@/types';

interface AIVerificationProps {
  projectId: number;
  existingFileId?: number;
  onClose?: () => void;
}

const verificationTypes: { value: VerificationType; label: string; description: string }[] = [
  {
    value: 'health-insurance',
    label: 'Health Insurance',
    description: 'Verify coverage meets French visa requirements',
  },
  {
    value: 'financial',
    label: 'Financial Documents',
    description: 'Verify bank statements and proof of funds (Coming Soon)',
  },
  {
    value: 'accommodation',
    label: 'Accommodation Proof',
    description: 'Verify housing documents (Coming Soon)',
  },
];

const statusConfig: Record<VerificationStatus, {
  color: string;
  bgColor: string;
  icon: React.ElementType;
  label: string
}> = {
  passed: {
    color: 'text-green-600',
    bgColor: 'bg-green-50 border-green-200',
    icon: ShieldCheck,
    label: 'Passed',
  },
  failed: {
    color: 'text-red-600',
    bgColor: 'bg-red-50 border-red-200',
    icon: ShieldX,
    label: 'Failed',
  },
  needs_review: {
    color: 'text-yellow-600',
    bgColor: 'bg-yellow-50 border-yellow-200',
    icon: ShieldAlert,
    label: 'Needs Review',
  },
  error: {
    color: 'text-gray-600',
    bgColor: 'bg-gray-50 border-gray-200',
    icon: AlertCircle,
    label: 'Error',
  },
};

export default function AIVerification({
  projectId,
  existingFileId: _existingFileId,
  onClose,
}: AIVerificationProps) {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [verificationType, setVerificationType] = useState<VerificationType>('health-insurance');
  const [dragActive, setDragActive] = useState(false);
  const [showHistory, setShowHistory] = useState(false);
  const [expandedDetails, setExpandedDetails] = useState(false);

  const { mutate: verifyDocument, isPending, data: verificationResult, reset } = useVerifyDocument(projectId);
  const { data: history = [], isLoading: historyLoading } = useVerificationHistory(projectId);

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      setSelectedFile(e.dataTransfer.files[0]);
      reset();
    }
  }, [reset]);

  const handleFileSelect = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setSelectedFile(e.target.files[0]);
      reset();
    }
  }, [reset]);

  const handleVerify = useCallback(() => {
    if (!selectedFile) return;

    verifyDocument({
      file: selectedFile,
      verificationType,
    });
  }, [selectedFile, verificationType, verifyDocument]);

  const handleReset = useCallback(() => {
    setSelectedFile(null);
    reset();
    setExpandedDetails(false);
  }, [reset]);

  const isDisabledType = verificationType !== 'health-insurance';

  return (
    <div className="space-y-6">
      {/* Verification Type Selector */}
      <div role="group" aria-labelledby="verification-type-label">
        <span id="verification-type-label" className="block text-sm font-medium text-gray-700 mb-2">
          Verification Type
        </span>
        <div className="grid grid-cols-1 gap-3">
          {verificationTypes.map((type) => (
            <button
              key={type.value}
              onClick={() => setVerificationType(type.value)}
              disabled={type.value !== 'health-insurance'}
              className={clsx(
                'text-left p-4 rounded-lg border-2 transition-all',
                verificationType === type.value
                  ? 'border-primary-500 bg-primary-50'
                  : 'border-gray-200 hover:border-gray-300 bg-white',
                type.value !== 'health-insurance' && 'opacity-50 cursor-not-allowed'
              )}
            >
              <div className="flex items-start gap-3">
                <div
                  className={clsx(
                    'w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center',
                    verificationType === type.value
                      ? 'border-primary-500 bg-primary-500'
                      : 'border-gray-300 bg-white'
                  )}
                >
                  {verificationType === type.value && (
                    <div className="w-2 h-2 rounded-full bg-white" />
                  )}
                </div>
                <div className="flex-1">
                  <div className="font-medium text-gray-900">{type.label}</div>
                  <div className="text-sm text-gray-500 mt-0.5">{type.description}</div>
                </div>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* File Upload Area */}
      {!selectedFile && (
        <div>
          <label htmlFor="verification-file-input" className="block text-sm font-medium text-gray-700 mb-2">
            Upload Document
          </label>
          <div
            onDragEnter={handleDrag}
            onDragLeave={handleDrag}
            onDragOver={handleDrag}
            onDrop={handleDrop}
            className={clsx(
              'relative border-2 border-dashed rounded-lg p-8 text-center transition-colors',
              dragActive
                ? 'border-primary-500 bg-primary-50'
                : 'border-gray-300 hover:border-gray-400 bg-gray-50'
            )}
          >
            <input
              id="verification-file-input"
              type="file"
              onChange={handleFileSelect}
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              disabled={isDisabledType}
            />
            <Upload className="w-12 h-12 mx-auto mb-3 text-gray-400" />
            <p className="text-sm font-medium text-gray-700 mb-1">
              {isDisabledType
                ? 'This verification type is not available yet'
                : 'Drop file here or click to upload'}
            </p>
            <p className="text-xs text-gray-500">
              PDF, JPG, PNG, DOC, DOCX up to 10MB
            </p>
          </div>
        </div>
      )}

      {/* Selected File */}
      {selectedFile && !verificationResult && (
        <div className="card p-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
              <FileText className="w-5 h-5 text-blue-600" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {selectedFile.name}
              </p>
              <p className="text-xs text-gray-500">
                {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
              </p>
            </div>
            <button
              onClick={handleReset}
              className="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        </div>
      )}

      {/* Verify Button */}
      {selectedFile && !verificationResult && (
        <button
          onClick={handleVerify}
          disabled={isPending || isDisabledType}
          className="btn btn-primary w-full"
        >
          {isPending ? (
            <>
              <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
              Analyzing Document...
            </>
          ) : (
            <>
              <Shield className="w-4 h-4" />
              Verify Document
            </>
          )}
        </button>
      )}

      {/* Verification Result */}
      {verificationResult && (
        <div className="space-y-4">
          <div
            className={clsx(
              'card border-2 p-6',
              statusConfig[verificationResult.status].bgColor
            )}
          >
            {/* Status Header */}
            <div className="flex items-start gap-4 mb-4">
              <div className={clsx('p-3 rounded-full', statusConfig[verificationResult.status].bgColor)}>
                {(() => {
                  const Icon = statusConfig[verificationResult.status].icon;
                  return <Icon className={clsx('w-6 h-6', statusConfig[verificationResult.status].color)} />;
                })()}
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h3 className={clsx('text-lg font-semibold', statusConfig[verificationResult.status].color)}>
                    {statusConfig[verificationResult.status].label}
                  </h3>
                </div>
                <p className="text-gray-700">{verificationResult.message}</p>
              </div>
            </div>

            {/* Details Section */}
            {verificationResult.details && (
              <div className="mt-4">
                <button
                  onClick={() => setExpandedDetails(!expandedDetails)}
                  className="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 mb-3"
                >
                  {expandedDetails ? (
                    <ChevronUp className="w-4 h-4" />
                  ) : (
                    <ChevronDown className="w-4 h-4" />
                  )}
                  View Details
                </button>

                {expandedDetails && (
                  <div className="space-y-3 pl-6 border-l-2 border-gray-200">
                    {verificationResult.details.coverage_type && (
                      <DetailItem
                        label="Coverage Type"
                        value={verificationResult.details.coverage_type}
                      />
                    )}
                    {verificationResult.details.coverage_territory && (
                      <DetailItem
                        label="Coverage Territory"
                        value={verificationResult.details.coverage_territory}
                      />
                    )}
                    {verificationResult.details.coverage_duration && (
                      <DetailItem
                        label="Coverage Duration"
                        value={verificationResult.details.coverage_duration}
                      />
                    )}
                    {verificationResult.details.provider && (
                      <DetailItem
                        label="Provider"
                        value={verificationResult.details.provider}
                      />
                    )}
                    {verificationResult.details.start_date && (
                      <DetailItem
                        label="Start Date"
                        value={verificationResult.details.start_date}
                      />
                    )}
                    {verificationResult.details.end_date && (
                      <DetailItem
                        label="End Date"
                        value={verificationResult.details.end_date}
                      />
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Issues */}
            {verificationResult.details?.issues && verificationResult.details.issues.length > 0 && (
              <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div className="flex items-start gap-2">
                  <AlertCircle className="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" />
                  <div className="flex-1">
                    <h4 className="text-sm font-medium text-red-900 mb-2">Issues Found</h4>
                    <ul className="text-sm text-red-700 space-y-1">
                      {verificationResult.details.issues.map((issue, index) => (
                        <li key={index} className="flex items-start gap-1">
                          <span className="text-red-600">•</span>
                          <span>{issue}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>
              </div>
            )}

            {/* Recommendations */}
            {verificationResult.details?.recommendations && verificationResult.details.recommendations.length > 0 && (
              <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div className="flex items-start gap-2">
                  <CheckCircle className="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" />
                  <div className="flex-1">
                    <h4 className="text-sm font-medium text-blue-900 mb-2">Recommendations</h4>
                    <ul className="text-sm text-blue-700 space-y-1">
                      {verificationResult.details.recommendations.map((rec, index) => (
                        <li key={index} className="flex items-start gap-1">
                          <span className="text-blue-600">•</span>
                          <span>{rec}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Action Buttons */}
          <div className="flex gap-3">
            <button
              onClick={handleReset}
              className="btn btn-secondary flex-1"
            >
              Verify Another Document
            </button>
            {onClose && (
              <button
                onClick={onClose}
                className="btn btn-primary flex-1"
              >
                Done
              </button>
            )}
          </div>
        </div>
      )}

      {/* Verification History */}
      <div className="pt-6 border-t border-gray-200">
        <button
          onClick={() => setShowHistory(!showHistory)}
          className="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 mb-3"
        >
          <Clock className="w-4 h-4" />
          Verification History
          {showHistory ? (
            <ChevronUp className="w-4 h-4" />
          ) : (
            <ChevronDown className="w-4 h-4" />
          )}
        </button>

        {showHistory && (
          <div className="space-y-3">
            {historyLoading ? (
              <div className="text-center py-8 text-gray-500">
                <div className="w-6 h-6 border-2 border-gray-300 border-t-primary-500 rounded-full animate-spin mx-auto mb-2" />
                Loading history...
              </div>
            ) : history.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                No verification history yet
              </div>
            ) : (
              history.map((item, index) => (
                <HistoryItem key={index} result={item} />
              ))
            )}
          </div>
        )}
      </div>
    </div>
  );
}

function DetailItem({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <dt className="text-xs font-medium text-gray-500 mb-0.5">{label}</dt>
      <dd className="text-sm text-gray-900">{value}</dd>
    </div>
  );
}

function HistoryItem({ result }: { result: VerificationResult }) {
  const config = statusConfig[result.status];
  const Icon = config.icon;

  return (
    <div className={clsx('card p-4 border', config.bgColor)}>
      <div className="flex items-start gap-3">
        <div className={clsx('p-2 rounded-lg', config.bgColor)}>
          <Icon className={clsx('w-4 h-4', config.color)} />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className={clsx('text-sm font-medium', config.color)}>
              {config.label}
            </span>
          </div>
          <p className="text-sm text-gray-700 line-clamp-2">
            {result.message}
          </p>
        </div>
      </div>
    </div>
  );
}

// Modal wrapper for standalone usage
interface AIVerificationModalProps {
  isOpen: boolean;
  onClose: () => void;
  projectId: number;
  existingFileId?: number;
}

export function AIVerificationModal({
  isOpen,
  onClose,
  projectId,
  existingFileId,
}: AIVerificationModalProps) {
  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="AI Document Verification"
      size="lg"
    >
      <AIVerification
        projectId={projectId}
        existingFileId={existingFileId}
        onClose={onClose}
      />
    </Modal>
  );
}
