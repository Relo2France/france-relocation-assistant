/**
 * DayCounter
 *
 * Circular progress display showing days used out of 90.
 */

import { clsx } from 'clsx';
import type { SchengenStatus } from '@/types';

interface DayCounterProps {
  daysUsed: number;
  daysRemaining: number;
  status: SchengenStatus;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const statusColors: Record<SchengenStatus, {
  stroke: string;
  text: string;
  bg: string;
}> = {
  safe: {
    stroke: 'stroke-green-500',
    text: 'text-green-600',
    bg: 'bg-green-50',
  },
  warning: {
    stroke: 'stroke-yellow-500',
    text: 'text-yellow-600',
    bg: 'bg-yellow-50',
  },
  danger: {
    stroke: 'stroke-orange-500',
    text: 'text-orange-600',
    bg: 'bg-orange-50',
  },
  critical: {
    stroke: 'stroke-red-500',
    text: 'text-red-600',
    bg: 'bg-red-50',
  },
};

const sizeConfig = {
  sm: {
    container: 'w-24 h-24',
    svgSize: 96,
    strokeWidth: 6,
    radius: 42,
    textSize: 'text-2xl',
    labelSize: 'text-xs',
  },
  md: {
    container: 'w-36 h-36',
    svgSize: 144,
    strokeWidth: 8,
    radius: 64,
    textSize: 'text-4xl',
    labelSize: 'text-sm',
  },
  lg: {
    container: 'w-48 h-48',
    svgSize: 192,
    strokeWidth: 10,
    radius: 86,
    textSize: 'text-5xl',
    labelSize: 'text-base',
  },
};

export default function DayCounter({
  daysUsed,
  daysRemaining,
  status,
  size = 'md',
  className,
}: DayCounterProps) {
  const colors = statusColors[status];
  const config = sizeConfig[size];

  const percentage = Math.min((daysUsed / 90) * 100, 100);
  const circumference = 2 * Math.PI * config.radius;
  const strokeDashoffset = circumference - (percentage / 100) * circumference;

  return (
    <div className={clsx('relative inline-flex', config.container, className)}>
      {/* Background circle */}
      <svg
        className="transform -rotate-90"
        width={config.svgSize}
        height={config.svgSize}
        aria-hidden="true"
      >
        <circle
          cx={config.svgSize / 2}
          cy={config.svgSize / 2}
          r={config.radius}
          fill="none"
          strokeWidth={config.strokeWidth}
          className="stroke-gray-200"
        />
        <circle
          cx={config.svgSize / 2}
          cy={config.svgSize / 2}
          r={config.radius}
          fill="none"
          strokeWidth={config.strokeWidth}
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={strokeDashoffset}
          className={clsx('transition-all duration-500', colors.stroke)}
        />
      </svg>

      {/* Center content */}
      <div className="absolute inset-0 flex flex-col items-center justify-center">
        <span
          className={clsx('font-bold', config.textSize, colors.text)}
          aria-label={`${daysUsed} days used`}
        >
          {daysUsed}
        </span>
        <span className={clsx('text-gray-500', config.labelSize)}>
          of 90 days
        </span>
      </div>

      {/* Screen reader description */}
      <div
        role="progressbar"
        aria-valuenow={daysUsed}
        aria-valuemin={0}
        aria-valuemax={90}
        aria-label={`Schengen days: ${daysUsed} used, ${daysRemaining} remaining`}
        className="sr-only"
      />
    </div>
  );
}
