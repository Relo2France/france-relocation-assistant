import { clsx } from 'clsx';
import { Check } from 'lucide-react';
import type { StageProgress } from '@/types';

interface ProgressTrackerProps {
  stages: StageProgress[];
  className?: string;
}

export default function ProgressTracker({ stages, className }: ProgressTrackerProps) {
  return (
    <div className={clsx('card', className)}>
      <div className="card-header">
        <h2 className="text-lg font-semibold text-gray-900">Progress Tracker</h2>
      </div>
      <div className="card-body">
        <div className="relative">
          {/* Progress line */}
          <div className="absolute top-5 left-0 right-0 h-0.5 bg-gray-200">
            <div
              className="h-full bg-primary-500 transition-all duration-500"
              style={{
                width: `${calculateOverallProgress(stages)}%`,
              }}
            />
          </div>

          {/* Stage indicators */}
          <div className="relative flex justify-between">
            {stages.map((stage, index) => (
              <div
                key={stage.slug}
                className="flex flex-col items-center"
                style={{ width: `${100 / stages.length}%` }}
              >
                {/* Circle indicator */}
                <div
                  className={clsx(
                    'w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all z-10',
                    stage.status === 'completed' && 'bg-primary-500 border-primary-500',
                    stage.status === 'current' && 'bg-white border-primary-500',
                    stage.status === 'upcoming' && 'bg-white border-gray-300'
                  )}
                >
                  {stage.status === 'completed' ? (
                    <Check className="w-5 h-5 text-white" />
                  ) : (
                    <span
                      className={clsx(
                        'text-sm font-medium',
                        stage.status === 'current' ? 'text-primary-500' : 'text-gray-400'
                      )}
                    >
                      {index + 1}
                    </span>
                  )}
                </div>

                {/* Label */}
                <div className="mt-3 text-center">
                  <p
                    className={clsx(
                      'text-sm font-medium',
                      stage.status === 'completed' && 'text-primary-600',
                      stage.status === 'current' && 'text-gray-900',
                      stage.status === 'upcoming' && 'text-gray-400'
                    )}
                  >
                    {stage.title}
                  </p>
                  {stage.total > 0 && (
                    <p className="text-xs text-gray-500 mt-0.5">
                      {stage.completed}/{stage.total} tasks
                    </p>
                  )}
                </div>

                {/* Progress bar for current stage */}
                {stage.status === 'current' && stage.total > 0 && (
                  <div className="mt-2 w-full max-w-[80px]">
                    <div className="progress-bar h-1">
                      <div
                        className="progress-bar-fill"
                        style={{ width: `${stage.percentage}%` }}
                      />
                    </div>
                    <p className="text-xs text-gray-500 text-center mt-1">
                      {stage.percentage}%
                    </p>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

function calculateOverallProgress(stages: StageProgress[]): number {
  const completedStages = stages.filter((s) => s.status === 'completed').length;
  const currentStage = stages.find((s) => s.status === 'current');
  const currentStageIndex = stages.findIndex((s) => s.status === 'current');

  if (stages.length === 0) return 0;

  // Base progress from completed stages
  const baseProgress = (completedStages / stages.length) * 100;

  // Add partial progress from current stage
  if (currentStage && currentStageIndex >= 0) {
    const stageWeight = 100 / stages.length;
    const currentProgress = (currentStage.percentage / 100) * stageWeight;
    return baseProgress + currentProgress;
  }

  return baseProgress;
}
