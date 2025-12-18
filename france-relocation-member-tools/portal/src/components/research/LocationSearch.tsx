/**
 * LocationSearch Component
 *
 * Search input for finding regions, departments, and communes across France.
 * Provides autocomplete suggestions with type indicators.
 */

import { useState, useEffect, useRef, useCallback } from 'react';
import { Search, MapPin, Building2, Home, X, Loader2 } from 'lucide-react';
import { FRANCE_REGIONS, FRANCE_DEPARTMENTS } from '@/config/research';
import { researchApi } from '@/api/client';
import type { FranceRegion, FranceDepartment, FranceCommune, ResearchLevel } from '@/types';

interface LocationSearchProps {
  onSelect: (
    result: FranceRegion | FranceDepartment | FranceCommune,
    type: ResearchLevel
  ) => void;
}

interface SearchResult {
  type: ResearchLevel;
  code: string;
  name: string;
  subtitle: string;
  data: FranceRegion | FranceDepartment | FranceCommune;
}

export default function LocationSearch({ onSelect }: LocationSearchProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [selectedIndex, setSelectedIndex] = useState(-1);
  const inputRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const debounceRef = useRef<ReturnType<typeof setTimeout>>();

  // Search function
  const performSearch = useCallback(async (searchQuery: string) => {
    if (!searchQuery.trim()) {
      setResults([]);
      return;
    }

    setLoading(true);
    const searchResults: SearchResult[] = [];
    const normalizedQuery = searchQuery.toLowerCase();

    // Search regions
    FRANCE_REGIONS.forEach((region) => {
      if (
        region.name.toLowerCase().includes(normalizedQuery) ||
        region.capital.toLowerCase().includes(normalizedQuery)
      ) {
        searchResults.push({
          type: 'region',
          code: region.code,
          name: region.name,
          subtitle: `Region · ${region.capital}`,
          data: region,
        });
      }
    });

    // Search departments
    FRANCE_DEPARTMENTS.forEach((dept) => {
      if (
        dept.name.toLowerCase().includes(normalizedQuery) ||
        dept.prefecture.toLowerCase().includes(normalizedQuery) ||
        dept.code.includes(normalizedQuery)
      ) {
        searchResults.push({
          type: 'department',
          code: dept.code,
          name: dept.name,
          subtitle: `Department · ${dept.region_name}`,
          data: dept,
        });
      }
    });

    // Search communes via API (if query is long enough)
    if (searchQuery.length >= 3) {
      try {
        const response = await researchApi.searchCommunes({ q: searchQuery, limit: 10 });
        if (response.communes) {
          response.communes.forEach((commune: FranceCommune) => {
            searchResults.push({
              type: 'commune',
              code: commune.code,
              name: commune.name,
              subtitle: `${commune.type.charAt(0).toUpperCase() + commune.type.slice(1)} · ${commune.department_name}`,
              data: commune,
            });
          });
        }
      } catch (error) {
        console.error('Commune search failed:', error);
      }
    }

    // Sort results: regions first, then departments, then communes
    const typeOrder = { region: 0, department: 1, commune: 2 };
    searchResults.sort((a, b) => {
      const orderDiff = typeOrder[a.type] - typeOrder[b.type];
      if (orderDiff !== 0) return orderDiff;
      return a.name.localeCompare(b.name);
    });

    setResults(searchResults.slice(0, 15));
    setLoading(false);
  }, []);

  // Debounced search
  useEffect(() => {
    if (debounceRef.current) {
      clearTimeout(debounceRef.current);
    }

    if (query.trim()) {
      debounceRef.current = setTimeout(() => {
        performSearch(query);
      }, 300);
    } else {
      setResults([]);
    }

    return () => {
      if (debounceRef.current) {
        clearTimeout(debounceRef.current);
      }
    };
  }, [query, performSearch]);

  // Handle selection
  const handleSelect = (result: SearchResult) => {
    onSelect(result.data, result.type);
    setQuery('');
    setResults([]);
    setIsOpen(false);
    setSelectedIndex(-1);
  };

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen || results.length === 0) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setSelectedIndex((prev) =>
          prev < results.length - 1 ? prev + 1 : 0
        );
        break;
      case 'ArrowUp':
        e.preventDefault();
        setSelectedIndex((prev) =>
          prev > 0 ? prev - 1 : results.length - 1
        );
        break;
      case 'Enter':
        e.preventDefault();
        if (selectedIndex >= 0 && selectedIndex < results.length) {
          handleSelect(results[selectedIndex]);
        }
        break;
      case 'Escape':
        e.preventDefault();
        setIsOpen(false);
        setSelectedIndex(-1);
        break;
    }
  };

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(e.target as Node) &&
        !inputRef.current?.contains(e.target as Node)
      ) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  // Get icon for result type
  const getTypeIcon = (type: ResearchLevel) => {
    switch (type) {
      case 'region':
        return <MapPin className="w-4 h-4 text-primary-600" />;
      case 'department':
        return <Building2 className="w-4 h-4 text-amber-600" />;
      case 'commune':
        return <Home className="w-4 h-4 text-emerald-600" />;
    }
  };

  return (
    <div className="relative">
      {/* Search Input */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={(e) => {
            setQuery(e.target.value);
            setIsOpen(true);
            setSelectedIndex(-1);
          }}
          onFocus={() => setIsOpen(true)}
          onKeyDown={handleKeyDown}
          placeholder="Search regions, departments, towns..."
          className="w-64 pl-10 pr-8 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          aria-label="Search locations"
          aria-expanded={isOpen}
          aria-haspopup="listbox"
          aria-controls="location-search-results"
          role="combobox"
        />
        {query && (
          <button
            onClick={() => {
              setQuery('');
              setResults([]);
              inputRef.current?.focus();
            }}
            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            aria-label="Clear search"
          >
            <X className="w-4 h-4" />
          </button>
        )}
      </div>

      {/* Results Dropdown */}
      {isOpen && (query || results.length > 0) && (
        <div
          ref={dropdownRef}
          id="location-search-results"
          role="listbox"
          className="absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden z-50"
        >
          {loading ? (
            <div className="flex items-center justify-center py-4">
              <Loader2 className="w-5 h-5 animate-spin text-primary-600" />
              <span className="ml-2 text-sm text-gray-600">Searching...</span>
            </div>
          ) : results.length > 0 ? (
            <ul className="max-h-80 overflow-y-auto">
              {results.map((result, index) => (
                <li key={`${result.type}-${result.code}`}>
                  <button
                    onClick={() => handleSelect(result)}
                    onMouseEnter={() => setSelectedIndex(index)}
                    className={`w-full text-left px-4 py-3 flex items-start gap-3 transition-colors ${
                      index === selectedIndex
                        ? 'bg-primary-50'
                        : 'hover:bg-gray-50'
                    }`}
                    role="option"
                    aria-selected={index === selectedIndex}
                  >
                    <span className="mt-0.5">{getTypeIcon(result.type)}</span>
                    <div>
                      <p className="font-medium text-gray-900">{result.name}</p>
                      <p className="text-sm text-gray-500">{result.subtitle}</p>
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          ) : query.length >= 2 ? (
            <div className="py-4 text-center text-gray-500 text-sm">
              No results found for "{query}"
            </div>
          ) : (
            <div className="py-4 text-center text-gray-500 text-sm">
              Type to search locations...
            </div>
          )}
        </div>
      )}
    </div>
  );
}
