/**
 * StatusBadge
 *
 * Displays compliance status with color-coded badge (green/yellow/red).
 */

import { clsx } from 'clsx';
import { Check, AlertTriangle, AlertCircle, XCircle } from 'lucide-react';
import type { SchengenStatus } from '@/types';

interface StatusBadgeProps {
  status: SchengenStatus;
  showLabel?: boolean;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const statusConfig: Record<SchengenStatus, {
  label: string;
  bgColor: string;
  textColor: string;
  borderColor: string;
  Icon: typeof Check;
}> = {
  safe: {
    label: 'Safe',
    bgColor: 'bg-green-50',
    textColor: 'text-green-700',
    borderColor: 'border-green-200',
    Icon: Check,
  },
  warning: {
    label: 'Warning',
    bgColor: 'bg-yellow-50',
    textColor: 'text-yellow-700',
    borderColor: 'border-yellow-200',
    Icon: AlertTriangle,
  },
  danger: {
    label: 'Danger',
    bgColor: 'bg-orange-50',
    textColor: 'text-orange-700',
    borderColor: 'border-orange-200',
    Icon: AlertCircle,
  },
  critical: {
    label: 'Critical',
    bgColor: 'bg-red-50',
    textColor: 'text-red-700',
    borderColor: 'border-red-200',
    Icon: XCircle,
  },
};

const sizeConfig = {
  sm: {
    badge: 'px-2 py-0.5 text-xs',
    icon: 'w-3 h-3',
  },
  md: {
    badge: 'px-2.5 py-1 text-sm',
    icon: 'w-4 h-4',
  },
  lg: {
    badge: 'px-3 py-1.5 text-base',
    icon: 'w-5 h-5',
  },
};

export default function StatusBadge({
  status,
  showLabel = true,
  size = 'md',
  className,
}: StatusBadgeProps) {
  const config = statusConfig[status];
  const sizes = sizeConfig[size];
  const Icon = config.Icon;

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1 rounded-full border font-medium',
        config.bgColor,
        config.textColor,
        config.borderColor,
        sizes.badge,
        className
      )}
    >
      <Icon className={sizes.icon} aria-hidden="true" />
      {showLabel && <span>{config.label}</span>}
    </span>
  );
}
