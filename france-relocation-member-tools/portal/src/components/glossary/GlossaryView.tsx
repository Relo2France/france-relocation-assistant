import { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import {
  Book,
  Search,
  ChevronDown,
  ChevronRight,
  Copy,
  Check,
  FileText,
  Home as HomeIcon,
  Heart,
  AlertCircle,
} from 'lucide-react';
import { useGlossary, useGlossarySearch } from '@/hooks/useApi';
import type { GlossaryTerm, GlossaryCategory } from '@/types';

// Hardcoded glossary data (fallback)
const hardcodedCategories: GlossaryCategory[] = [
  {
    id: 'document-legal',
    title: 'Document & Legal Terms',
    terms: [
      {
        id: 'apostille',
        title: 'Apostille',
        french: 'Apostille',
        short: 'Official certificate authenticating documents for international use',
        full: 'An apostille is a form of authentication issued to documents for use in countries that participate in the Hague Convention of 1961. It certifies that a document is a true copy of an original, making it legally valid in other member countries without additional certification.',
        category: 'document-legal',
      },
      {
        id: 'certified-copy',
        title: 'Certified Copy',
        french: 'Copie certifiée conforme',
        short: 'An official copy verified by the issuing authority',
        full: 'A certified copy is an official copy of a document that has been verified as a true and accurate reproduction of the original by an authorized person or entity, such as a notary public, government official, or the issuing authority.',
        category: 'document-legal',
      },
      {
        id: 'attestation',
        title: 'Attestation sur l\'honneur',
        french: 'Attestation sur l\'honneur',
        short: 'A sworn statement declaring something to be true',
        full: 'A sworn statement or affidavit declaring that certain facts or statements are true to the best of your knowledge. In France, this is a legally binding document where you attest to the veracity of information under penalty of perjury.',
        category: 'document-legal',
      },
      {
        id: 'compromis',
        title: 'Compromis de vente',
        french: 'Compromis de vente',
        short: 'Preliminary sales agreement for property purchase',
        full: 'The preliminary contract for buying property in France, signed by both buyer and seller. This legally binding agreement sets out the terms of the sale and is typically signed several weeks before the final deed of sale (acte de vente). You have a 10-day cooling-off period after signing.',
        category: 'document-legal',
      },
      {
        id: 'acte-vente',
        title: 'Acte de vente',
        french: 'Acte de vente',
        short: 'Final deed of sale transferring property ownership',
        full: 'The final deed of sale for property purchase in France, signed before a notary (notaire). This document officially transfers ownership from seller to buyer and must be registered with the land registry.',
        category: 'document-legal',
      },
      {
        id: 'procuration',
        title: 'Procuration',
        french: 'Procuration',
        short: 'Power of attorney authorizing someone to act on your behalf',
        full: 'A legal document granting someone the authority to act on your behalf in specific matters. In France, procurations are commonly used for property purchases, bank transactions, or administrative procedures when you cannot be physically present.',
        category: 'document-legal',
      },
    ],
  },
  {
    id: 'visa-residency',
    title: 'Visa & Residency Terms',
    terms: [
      {
        id: 'vls-ts',
        title: 'VLS-TS',
        french: 'Visa de Long Séjour valant Titre de Séjour',
        short: 'Long-stay visa equivalent to residence permit',
        full: 'A long-stay visa that serves as a residence permit for the first year in France. After entering France with a VLS-TS, you must validate it with OFII within 3 months. This validation makes the visa equivalent to a residence permit for one year.',
        category: 'visa-residency',
      },
      {
        id: 'titre-sejour',
        title: 'Titre de séjour',
        french: 'Titre de séjour',
        short: 'Residence permit allowing you to live in France',
        full: 'A residence permit card issued by the Prefecture that allows you to legally reside in France for a specified period. After your first year with a VLS-TS, you\'ll need to apply for a titre de séjour renewal at your local prefecture.',
        category: 'visa-residency',
      },
      {
        id: 'ofii',
        title: 'OFII',
        french: 'Office Français de l\'Immigration et de l\'Intégration',
        short: 'French Immigration and Integration Office',
        full: 'The French Office for Immigration and Integration. OFII is responsible for validating long-stay visas, organizing mandatory integration programs, and managing certain aspects of legal immigration to France. You must complete OFII validation within 3 months of arrival.',
        category: 'visa-residency',
      },
      {
        id: 'prefecture',
        title: 'Préfecture',
        french: 'Préfecture',
        short: 'Regional government office handling residence permits',
        full: 'The prefecture is the local government office representing the French state in each department. It handles residence permit applications, renewals, driver\'s license exchanges, and various other administrative procedures for foreign residents.',
        category: 'visa-residency',
      },
      {
        id: 'recepisse',
        title: 'Récépissé',
        french: 'Récépissé',
        short: 'Temporary receipt while waiting for documents',
        full: 'A temporary document issued by the Prefecture acknowledging that you have submitted an application for a residence permit. It serves as proof that you are legally allowed to stay in France while your application is being processed and may authorize you to work.',
        category: 'visa-residency',
      },
    ],
  },
  {
    id: 'healthcare',
    title: 'Healthcare Terms',
    terms: [
      {
        id: 'puma',
        title: 'PUMA',
        french: 'Protection Universelle Maladie',
        short: 'Universal health coverage system',
        full: 'Protection Universelle Maladie (Universal Health Protection) is France\'s universal healthcare coverage system. If you live in France in a stable and regular manner (at least 3 months per year), you are entitled to healthcare coverage under PUMA, managed by the national health insurance (Assurance Maladie).',
        category: 'healthcare',
      },
      {
        id: 'carte-vitale',
        title: 'Carte Vitale',
        french: 'Carte Vitale',
        short: 'French health insurance card',
        full: 'The green electronic health insurance card containing your personal information and social security number. It allows healthcare providers to directly bill your treatments to the national health insurance system. You receive this card after registering with CPAM (Caisse Primaire d\'Assurance Maladie).',
        category: 'healthcare',
      },
      {
        id: 'mutuelle',
        title: 'Mutuelle',
        french: 'Mutuelle',
        short: 'Supplementary private health insurance',
        full: 'Complementary private health insurance that covers expenses not fully reimbursed by the national health insurance system (typically 70% of costs). A mutuelle typically covers the remaining 30% (called the "ticket modérateur") plus additional services like dental, optical, and alternative medicine.',
        category: 'healthcare',
      },
    ],
  },
];

// Category colors for visual coding
const categoryColors = {
  'document-legal': {
    bg: 'bg-blue-50',
    border: 'border-blue-200',
    text: 'text-blue-700',
    icon: 'text-blue-600',
    hover: 'hover:bg-blue-100',
  },
  'visa-residency': {
    bg: 'bg-purple-50',
    border: 'border-purple-200',
    text: 'text-purple-700',
    icon: 'text-purple-600',
    hover: 'hover:bg-purple-100',
  },
  healthcare: {
    bg: 'bg-red-50',
    border: 'border-red-200',
    text: 'text-red-700',
    icon: 'text-red-600',
    hover: 'hover:bg-red-100',
  },
};

// Category icons
const categoryIcons = {
  'document-legal': FileText,
  'visa-residency': HomeIcon,
  healthcare: Heart,
};

export default function GlossaryView() {
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedCategories, setExpandedCategories] = useState<string[]>([
    'document-legal',
    'visa-residency',
    'healthcare',
  ]);
  const [expandedTerms, setExpandedTerms] = useState<string[]>([]);
  const [copiedTerm, setCopiedTerm] = useState<string | null>(null);

  // Data fetching - use API if available, fallback to hardcoded data
  const { data: apiCategories, isLoading } = useGlossary();
  const { data: searchResults, isLoading: searchLoading } = useGlossarySearch(searchQuery);

  // Use API data if available, otherwise use hardcoded data
  const categories = searchQuery
    ? searchResults || hardcodedCategories
    : apiCategories || hardcodedCategories;

  // Filter and sort terms
  const filteredCategories = useMemo(() => {
    if (!searchQuery) {
      // No search - show all categories with sorted terms
      return categories.map((category) => ({
        ...category,
        terms: [...category.terms].sort((a, b) => a.title.localeCompare(b.title)),
      }));
    }

    // With search - filter and highlight
    const query = searchQuery.toLowerCase();
    return categories
      .map((category) => {
        const matchingTerms = category.terms
          .filter(
            (term) =>
              term.title.toLowerCase().includes(query) ||
              term.french?.toLowerCase().includes(query) ||
              term.short.toLowerCase().includes(query) ||
              term.full?.toLowerCase().includes(query)
          )
          .sort((a, b) => a.title.localeCompare(b.title));

        return {
          ...category,
          terms: matchingTerms,
        };
      })
      .filter((category) => category.terms.length > 0);
  }, [categories, searchQuery]);

  const toggleCategory = (categoryId: string) => {
    setExpandedCategories((prev) =>
      prev.includes(categoryId)
        ? prev.filter((id) => id !== categoryId)
        : [...prev, categoryId]
    );
  };

  const toggleTerm = (termId: string) => {
    setExpandedTerms((prev) =>
      prev.includes(termId) ? prev.filter((id) => id !== termId) : [...prev, termId]
    );
  };

  const copyToClipboard = async (term: GlossaryTerm) => {
    const text = term.french || term.title;
    try {
      await navigator.clipboard.writeText(text);
      setCopiedTerm(term.id);
      setTimeout(() => setCopiedTerm(null), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const highlightText = (text: string) => {
    if (!searchQuery) return text;

    const parts = text.split(new RegExp(`(${searchQuery})`, 'gi'));
    return parts.map((part, i) =>
      part.toLowerCase() === searchQuery.toLowerCase() ? (
        <mark key={i} className="bg-yellow-200 text-gray-900">
          {part}
        </mark>
      ) : (
        part
      )
    );
  };

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Book className="w-7 h-7 text-primary-600" />
          French Glossary
        </h1>
        <p className="text-gray-600 mt-1">
          Essential French terms and definitions for your relocation journey
        </p>
      </div>

      {/* Search bar */}
      <div className="mb-6">
        <div className="relative max-w-2xl">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search terms across all categories..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        {searchQuery && (
          <p className="text-sm text-gray-600 mt-2">
            Found {filteredCategories.reduce((acc, cat) => acc + cat.terms.length, 0)} term(s)
            in {filteredCategories.length} categor{filteredCategories.length === 1 ? 'y' : 'ies'}
          </p>
        )}
      </div>

      {/* Loading state */}
      {(isLoading || (searchQuery && searchLoading)) && (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="card p-6 animate-pulse">
              <div className="h-6 bg-gray-200 rounded w-1/4 mb-4" />
              <div className="space-y-3">
                <div className="h-4 bg-gray-100 rounded w-full" />
                <div className="h-4 bg-gray-100 rounded w-5/6" />
                <div className="h-4 bg-gray-100 rounded w-4/6" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Empty state */}
      {!isLoading && !searchLoading && filteredCategories.length === 0 && (
        <div className="card p-12 text-center">
          <AlertCircle className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No terms found</h3>
          <p className="text-gray-500">
            {searchQuery
              ? `No glossary terms match "${searchQuery}". Try a different search term.`
              : 'No glossary terms available at the moment.'}
          </p>
        </div>
      )}

      {/* Categories */}
      {!isLoading && !searchLoading && filteredCategories.length > 0 && (
        <div className="space-y-4">
          {filteredCategories.map((category) => {
            const isExpanded = expandedCategories.includes(category.id);
            const colors = categoryColors[category.id as keyof typeof categoryColors] || {
              bg: 'bg-gray-50',
              border: 'border-gray-200',
              text: 'text-gray-700',
              icon: 'text-gray-600',
              hover: 'hover:bg-gray-100',
            };
            const Icon =
              categoryIcons[category.id as keyof typeof categoryIcons] || FileText;

            return (
              <div
                key={category.id}
                className={clsx('card overflow-hidden', colors.border)}
              >
                {/* Category header */}
                <button
                  onClick={() => toggleCategory(category.id)}
                  className={clsx(
                    'w-full flex items-center justify-between px-6 py-4 transition-colors',
                    colors.bg,
                    colors.hover
                  )}
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={clsx(
                        'p-2 bg-white rounded-lg shadow-sm',
                        colors.border,
                        'border'
                      )}
                    >
                      <Icon className={clsx('w-5 h-5', colors.icon)} />
                    </div>
                    <div className="text-left">
                      <h2 className={clsx('text-lg font-semibold', colors.text)}>
                        {category.title}
                      </h2>
                      <p className="text-sm text-gray-600">
                        {category.terms.length} term{category.terms.length !== 1 ? 's' : ''}
                      </p>
                    </div>
                  </div>
                  {isExpanded ? (
                    <ChevronDown className={clsx('w-5 h-5', colors.icon)} />
                  ) : (
                    <ChevronRight className={clsx('w-5 h-5', colors.icon)} />
                  )}
                </button>

                {/* Terms list */}
                {isExpanded && (
                  <div className="p-4 space-y-3">
                    {category.terms.map((term) => (
                      <TermCard
                        key={term.id}
                        term={term}
                        isExpanded={expandedTerms.includes(term.id)}
                        onToggle={() => toggleTerm(term.id)}
                        onCopy={() => copyToClipboard(term)}
                        isCopied={copiedTerm === term.id}
                        highlightText={highlightText}
                        colors={colors}
                      />
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Help text */}
      {!searchQuery && !isLoading && filteredCategories.length > 0 && (
        <div className="mt-8 p-4 bg-primary-50 border border-primary-200 rounded-lg">
          <div className="flex items-start gap-3">
            <Book className="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 className="font-medium text-primary-900 mb-1">
                Need more information?
              </h3>
              <p className="text-sm text-primary-700">
                Use the search bar to find specific terms, or click on any category to browse
                all available terms. Click on a term to see its full definition.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

interface TermCardProps {
  term: GlossaryTerm;
  isExpanded: boolean;
  onToggle: () => void;
  onCopy: () => void;
  isCopied: boolean;
  highlightText: (text: string) => React.ReactNode;
  colors: {
    bg: string;
    border: string;
    text: string;
    icon: string;
    hover: string;
  };
}

function TermCard({
  term,
  isExpanded,
  onToggle,
  onCopy,
  isCopied,
  highlightText,
  colors,
}: TermCardProps) {
  return (
    <div
      className={clsx(
        'border rounded-lg overflow-hidden transition-all',
        isExpanded ? clsx(colors.border, colors.bg) : 'border-gray-200 bg-white'
      )}
    >
      {/* Term header */}
      <div className="flex items-start gap-3 p-4">
        <button
          onClick={onToggle}
          className="flex-1 text-left min-w-0 group"
        >
          <div className="flex items-start gap-2">
            {isExpanded ? (
              <ChevronDown className={clsx('w-5 h-5 flex-shrink-0 mt-0.5', colors.icon)} />
            ) : (
              <ChevronRight className="w-5 h-5 flex-shrink-0 mt-0.5 text-gray-400 group-hover:text-gray-600" />
            )}
            <div className="flex-1 min-w-0">
              {/* French term (bold) */}
              {term.french && (
                <div className="font-bold text-gray-900 mb-1">
                  {highlightText(term.french)}
                </div>
              )}
              {/* English title */}
              <div
                className={clsx(
                  term.french ? 'text-sm text-gray-700' : 'font-semibold text-gray-900'
                )}
              >
                {highlightText(term.title)}
              </div>
              {/* Short definition */}
              <p className="text-sm text-gray-600 mt-1">{highlightText(term.short)}</p>
              {/* Expand hint */}
              {term.full && !isExpanded && (
                <span className="text-xs text-primary-600 mt-2 inline-block group-hover:underline">
                  Click to read full definition
                </span>
              )}
            </div>
          </div>
        </button>

        {/* Copy button */}
        <button
          onClick={onCopy}
          className={clsx(
            'p-2 rounded-lg transition-colors flex-shrink-0',
            isCopied
              ? 'bg-green-100 text-green-600'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          )}
          title={`Copy "${term.french || term.title}"`}
        >
          {isCopied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
        </button>
      </div>

      {/* Full definition (expanded) */}
      {isExpanded && term.full && (
        <div className="px-4 pb-4 pl-11">
          <div className="p-4 bg-white border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-700 leading-relaxed">{term.full}</p>
          </div>
        </div>
      )}
    </div>
  );
}
