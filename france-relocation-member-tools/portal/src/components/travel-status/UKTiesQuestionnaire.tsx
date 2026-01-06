/**
 * UKTiesQuestionnaire Component
 *
 * Collects UK connection tie information for the Statutory Residence Test.
 * Includes 5 connection ties (family, accommodation, work, 90-day, country)
 * plus automatic test conditions (only home, full-time work, leaving UK).
 */

import { useEffect, useState } from 'react';
import { clsx } from 'clsx';
import {
  Briefcase,
  Building,
  Calendar,
  ChevronDown,
  ChevronUp,
  Globe,
  HelpCircle,
  Home,
  Loader2,
  Plane,
  Save,
  Users,
} from 'lucide-react';
import { useUKTies, useUpdateUKTies } from '@/hooks/useApi';
import type { UKTies } from '@/types';

interface TieDefinition {
  id: keyof UKTies;
  label: string;
  description: string;
  Icon: typeof Users;
  detailsField?: keyof UKTies;
  detailsLabel?: string;
  category: 'tie' | 'automatic';
}

const TIE_DEFINITIONS: TieDefinition[] = [
  // Connection Ties (5 types)
  {
    id: 'family_tie',
    label: 'Family Tie',
    description: 'You have a spouse/civil partner or minor child who is UK resident.',
    Icon: Users,
    detailsField: 'family_tie_details',
    detailsLabel: 'Details about family in UK',
    category: 'tie',
  },
  {
    id: 'accommodation_tie',
    label: 'Accommodation Tie',
    description:
      'You have a place to live in the UK available for a continuous period of 91+ days, and you stay there for at least one night during the tax year.',
    Icon: Home,
    detailsField: 'accommodation_tie_details',
    detailsLabel: 'Details about UK accommodation',
    category: 'tie',
  },
  {
    id: 'work_tie',
    label: 'Work Tie',
    description:
      'You do substantive work in the UK for 40+ days during the tax year (3+ hours/day).',
    Icon: Briefcase,
    detailsField: 'work_tie_details',
    detailsLabel: 'Details about work in UK',
    category: 'tie',
  },
  {
    id: 'ninety_day_tie',
    label: '90-Day Tie',
    description:
      'You spent more than 90 days in the UK in either or both of the previous two tax years.',
    Icon: Calendar,
    category: 'tie',
  },
  {
    id: 'country_tie',
    label: 'Country Tie',
    description:
      'The country in which you were present at midnight for the greatest number of days in the tax year is the UK. (Only applies to "arrivers")',
    Icon: Globe,
    category: 'tie',
  },
  // Automatic Test Conditions
  {
    id: 'only_home_in_uk',
    label: 'Only Home in UK',
    description:
      'You have a home in the UK and have no home overseas, or have a home overseas but you are present there for fewer than 30 days in the tax year.',
    Icon: Building,
    category: 'automatic',
  },
  {
    id: 'full_time_work_uk',
    label: 'Full-Time Work in UK',
    description:
      'You work full-time in the UK (35+ hours/week on average) for 365 days with no significant break (31+ days).',
    Icon: Briefcase,
    category: 'automatic',
  },
  {
    id: 'leaving_uk_permanently',
    label: 'Leaving UK Permanently',
    description:
      'You ceased to have a home in the UK during the tax year and subsequently had no UK home.',
    Icon: Plane,
    category: 'automatic',
  },
];

interface UKTiesQuestionnaireProps {
  taxYear?: number;
  compact?: boolean;
  className?: string;
  onUpdate?: () => void;
}

export default function UKTiesQuestionnaire({
  taxYear: propTaxYear,
  compact = false,
  className,
  onUpdate,
}: UKTiesQuestionnaireProps) {
  // Calculate current UK tax year if not provided
  const getCurrentUKTaxYear = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1; // 0-indexed
    const day = now.getDate();
    // UK tax year starts April 6
    return month < 4 || (month === 4 && day < 6) ? year - 1 : year;
  };

  const taxYear = propTaxYear ?? getCurrentUKTaxYear();

  const { data, isLoading } = useUKTies(taxYear);
  const updateTies = useUpdateUKTies();

  const [localTies, setLocalTies] = useState<Partial<UKTies>>({});
  const [hasChanges, setHasChanges] = useState(false);
  const [expandedSection, setExpandedSection] = useState<'ties' | 'automatic' | null>(
    compact ? null : 'ties'
  );
  const [showHelp, setShowHelp] = useState<string | null>(null);

  // Sync local state with server data
  useEffect(() => {
    if (data?.ties) {
      setLocalTies(data.ties);
      setHasChanges(false);
    }
  }, [data?.ties]);

  const handleToggleTie = (id: keyof UKTies) => {
    setLocalTies((prev) => ({
      ...prev,
      [id]: !prev[id],
    }));
    setHasChanges(true);
  };

  const handleDetailsChange = (id: keyof UKTies, value: string) => {
    setLocalTies((prev) => ({
      ...prev,
      [id]: value,
    }));
    setHasChanges(true);
  };

  const handleSave = async () => {
    await updateTies.mutateAsync({
      taxYear,
      ties: localTies,
    });
    setHasChanges(false);
    onUpdate?.();
  };

  // Count active ties
  const connectionTies = TIE_DEFINITIONS.filter((t) => t.category === 'tie');
  const automaticConditions = TIE_DEFINITIONS.filter((t) => t.category === 'automatic');
  const activeTieCount = connectionTies.filter(
    (t) => localTies[t.id] === true
  ).length;

  if (isLoading) {
    return (
      <div className={clsx('animate-pulse p-4', className)}>
        <div className="h-6 bg-gray-200 rounded w-1/3 mb-4" />
        <div className="space-y-3">
          {[1, 2, 3, 4, 5].map((i) => (
            <div key={i} className="h-12 bg-gray-200 rounded" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className={clsx('space-y-4', className)}>
      {/* Tax Year Header */}
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-lg font-semibold text-gray-900">UK Connection Ties</h3>
          <p className="text-sm text-gray-500">
            Tax Year: {taxYear}/{(taxYear + 1).toString().slice(2)} (April 6 {taxYear} - April 5{' '}
            {taxYear + 1})
          </p>
        </div>
        <div className="flex items-center gap-2">
          <span
            className={clsx(
              'px-3 py-1 rounded-full text-sm font-medium',
              activeTieCount === 0
                ? 'bg-green-100 text-green-700'
                : activeTieCount <= 2
                ? 'bg-amber-100 text-amber-700'
                : 'bg-red-100 text-red-700'
            )}
          >
            {activeTieCount} {activeTieCount === 1 ? 'tie' : 'ties'}
          </span>
        </div>
      </div>

      {/* Connection Ties Section */}
      <div className="border border-gray-200 rounded-lg overflow-hidden">
        <button
          onClick={() => setExpandedSection(expandedSection === 'ties' ? null : 'ties')}
          className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors"
        >
          <div className="flex items-center gap-3">
            <Users className="w-5 h-5 text-gray-600" aria-hidden="true" />
            <span className="font-medium text-gray-900">Connection Ties (5 types)</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-500">{activeTieCount}/5 active</span>
            {expandedSection === 'ties' ? (
              <ChevronUp className="w-5 h-5 text-gray-400" />
            ) : (
              <ChevronDown className="w-5 h-5 text-gray-400" />
            )}
          </div>
        </button>

        {expandedSection === 'ties' && (
          <div className="divide-y divide-gray-100">
            {connectionTies.map((tie) => (
              <TieRow
                key={tie.id}
                tie={tie}
                value={localTies[tie.id] as boolean}
                details={
                  tie.detailsField
                    ? (localTies[tie.detailsField] as string | undefined)
                    : undefined
                }
                onToggle={() => handleToggleTie(tie.id)}
                onDetailsChange={
                  tie.detailsField
                    ? (value) => {
                        if (tie.detailsField) {
                          handleDetailsChange(tie.detailsField, value);
                        }
                      }
                    : undefined
                }
                showHelp={showHelp === tie.id}
                onToggleHelp={() => setShowHelp(showHelp === tie.id ? null : tie.id)}
              />
            ))}
          </div>
        )}
      </div>

      {/* Automatic Test Conditions */}
      <div className="border border-gray-200 rounded-lg overflow-hidden">
        <button
          onClick={() =>
            setExpandedSection(expandedSection === 'automatic' ? null : 'automatic')
          }
          className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors"
        >
          <div className="flex items-center gap-3">
            <Building className="w-5 h-5 text-gray-600" aria-hidden="true" />
            <span className="font-medium text-gray-900">Automatic Test Conditions</span>
          </div>
          {expandedSection === 'automatic' ? (
            <ChevronUp className="w-5 h-5 text-gray-400" />
          ) : (
            <ChevronDown className="w-5 h-5 text-gray-400" />
          )}
        </button>

        {expandedSection === 'automatic' && (
          <div className="p-4 space-y-1 text-sm text-gray-600">
            <p className="mb-3">
              These conditions are used for the Automatic UK/Overseas tests and are separate
              from the connection ties.
            </p>
            <div className="divide-y divide-gray-100">
              {automaticConditions.map((condition) => (
                <TieRow
                  key={condition.id}
                  tie={condition}
                  value={localTies[condition.id] as boolean}
                  onToggle={() => handleToggleTie(condition.id)}
                  showHelp={showHelp === condition.id}
                  onToggleHelp={() =>
                    setShowHelp(showHelp === condition.id ? null : condition.id)
                  }
                />
              ))}
            </div>
          </div>
        )}
      </div>

      {/* Notes Field */}
      <div>
        <label
          htmlFor="uk-ties-notes"
          className="block text-sm font-medium text-gray-700 mb-1"
        >
          Additional Notes
        </label>
        <textarea
          id="uk-ties-notes"
          value={(localTies.notes as string) || ''}
          onChange={(e) => handleDetailsChange('notes', e.target.value)}
          placeholder="Any additional notes about your UK circumstances..."
          rows={3}
          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        />
      </div>

      {/* Save Button */}
      {hasChanges && (
        <div className="flex justify-end">
          <button
            onClick={handleSave}
            disabled={updateTies.isPending}
            className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors"
          >
            {updateTies.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
            ) : (
              <Save className="w-4 h-4" aria-hidden="true" />
            )}
            Save Changes
          </button>
        </div>
      )}
    </div>
  );
}

interface TieRowProps {
  tie: TieDefinition;
  value: boolean;
  details?: string;
  onToggle: () => void;
  onDetailsChange?: (value: string) => void;
  showHelp: boolean;
  onToggleHelp: () => void;
}

function TieRow({
  tie,
  value,
  details,
  onToggle,
  onDetailsChange,
  showHelp,
  onToggleHelp,
}: TieRowProps) {
  const { Icon, label, description, detailsLabel } = tie;

  return (
    <div className="px-4 py-3">
      <div className="flex items-start gap-3">
        {/* Toggle Switch */}
        <button
          onClick={onToggle}
          className={clsx(
            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 mt-0.5',
            value ? 'bg-red-500' : 'bg-gray-300'
          )}
          role="switch"
          aria-checked={value}
          aria-label={label}
        >
          <span
            className={clsx(
              'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
              value ? 'translate-x-5' : 'translate-x-0'
            )}
          />
        </button>

        {/* Icon and Label */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <Icon
              className={clsx('w-5 h-5', value ? 'text-red-600' : 'text-gray-400')}
              aria-hidden="true"
            />
            <span className={clsx('font-medium', value && 'text-red-700')}>{label}</span>
            <button
              onClick={onToggleHelp}
              className="text-gray-400 hover:text-gray-600"
              aria-label="Show help"
            >
              <HelpCircle className="w-4 h-4" aria-hidden="true" />
            </button>
          </div>

          {/* Help Text */}
          {showHelp && (
            <p className="mt-2 text-sm text-gray-600 bg-gray-50 rounded p-2">{description}</p>
          )}

          {/* Details Input */}
          {value && onDetailsChange && detailsLabel && (
            <input
              type="text"
              value={details || ''}
              onChange={(e) => onDetailsChange(e.target.value)}
              placeholder={detailsLabel}
              className="mt-2 w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            />
          )}
        </div>
      </div>
    </div>
  );
}
