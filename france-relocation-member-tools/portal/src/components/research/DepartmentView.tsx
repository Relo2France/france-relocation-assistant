/**
 * DepartmentView Component
 *
 * Displays department details with a searchable list of communes.
 * Allows users to drill down into communes or generate department reports.
 * Loads commune data directly from GeoJSON for comprehensive coverage.
 */

import { useState, useEffect, useCallback } from 'react';
import { ArrowLeft, FileText, MapPin, Users, Search, Loader2, Building, Home, TreeDeciduous } from 'lucide-react';
import DepartmentMapView from './DepartmentMapView';
import type { FranceDepartment, FranceCommune, ResearchLevel } from '@/types';

// Slugify department name for URL (e.g., "Côte-d'Or" -> "cote-d-or")
const slugifyDeptName = (name: string): string => {
  return name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
    .replace(/['']/g, '-')           // Replace apostrophes with hyphens
    .replace(/\s+/g, '-')            // Replace spaces with hyphens
    .replace(/[^a-z0-9-]/g, '')      // Remove other special chars
    .replace(/-+/g, '-')             // Collapse multiple hyphens
    .replace(/^-|-$/g, '');          // Trim hyphens from ends
};

// Build GeoJSON URL for department communes
// Format: departements/{code}-{name}/communes-{code}-{name}.geojson
const getCommuneGeoUrl = (deptCode: string, deptName: string) => {
  const slug = slugifyDeptName(deptName);
  return `https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements/${deptCode}-${slug}/communes-${deptCode}-${slug}.geojson`;
};


interface DepartmentViewProps {
  department: FranceDepartment;
  onCommuneSelect: (commune: FranceCommune) => void;
  onGenerateReport: (type: ResearchLevel, code: string, name: string) => void;
  onBack: () => void;
}

// Commune type icons
const COMMUNE_TYPE_INFO = {
  city: { icon: Building, label: 'City', color: 'text-blue-600 bg-blue-100' },
  town: { icon: Home, label: 'Town', color: 'text-amber-600 bg-amber-100' },
  village: { icon: TreeDeciduous, label: 'Village', color: 'text-emerald-600 bg-emerald-100' },
};

export default function DepartmentView({
  department,
  onCommuneSelect,
  onGenerateReport,
  onBack,
}: DepartmentViewProps) {
  const [communes, setCommunes] = useState<FranceCommune[]>([]);
  const [filteredCommunes, setFilteredCommunes] = useState<FranceCommune[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [filter, setFilter] = useState<'all' | 'city' | 'town' | 'village'>('all');

  // Fetch communes directly from GeoJSON (same source as the map)
  useEffect(() => {
    const fetchCommunes = async () => {
      setLoading(true);
      try {
        const geoUrl = getCommuneGeoUrl(department.code, department.name);
        const response = await fetch(geoUrl);

        if (!response.ok) {
          throw new Error(`Failed to fetch GeoJSON: ${response.status}`);
        }

        const geoJson = await response.json();

        // Extract commune data from GeoJSON features
        const communeData: FranceCommune[] = geoJson.features.map((feature: {
          properties: { code: string; nom: string };
        }) => {
          const { code, nom } = feature.properties;

          // Classify commune type based on name patterns and known major cities
          const isMajorCity = department.major_cities.some(
            (city) => city.toLowerCase() === nom.toLowerCase()
          );

          // Determine type (we don't have population in GeoJSON, use heuristics)
          let type: 'city' | 'town' | 'village' = 'village';
          if (isMajorCity) {
            type = 'city';
          } else if (nom.includes('-') || nom.length > 15) {
            // Longer names with hyphens tend to be towns
            type = 'town';
          }

          return {
            code,
            name: nom,
            postal_codes: [department.code + '000'], // Approximate postal code
            department_code: department.code,
            department_name: department.name,
            region_code: department.region_code,
            region_name: department.region_name,
            population: 0, // Not available in GeoJSON
            type,
          };
        });

        // Sort by name
        communeData.sort((a, b) => a.name.localeCompare(b.name, 'fr'));

        setCommunes(communeData);
        setFilteredCommunes(communeData);
      } catch (error) {
        console.error('Failed to fetch communes from GeoJSON:', error);
        // Fallback to major cities from department data
        const fallbackCommunes: FranceCommune[] = department.major_cities.map((city, index) => ({
          code: `${department.code}${String(index + 1).padStart(3, '0')}`,
          name: city,
          postal_codes: [`${department.code}000`],
          department_code: department.code,
          department_name: department.name,
          region_code: department.region_code,
          region_name: department.region_name,
          population: 0,
          type: 'city' as const,
        }));
        setCommunes(fallbackCommunes);
        setFilteredCommunes(fallbackCommunes);
      } finally {
        setLoading(false);
      }
    };

    fetchCommunes();
  }, [department]);

  // Filter communes based on search and type filter
  useEffect(() => {
    let result = communes;

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      result = result.filter(
        (c) =>
          c.name.toLowerCase().includes(query) ||
          c.postal_codes.some((p) => p.includes(query))
      );
    }

    if (filter !== 'all') {
      result = result.filter((c) => c.type === filter);
    }

    setFilteredCommunes(result);
  }, [communes, searchQuery, filter]);

  const handleSearch = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchQuery(e.target.value);
  }, []);

  // Group communes by type for display
  const communesByType = {
    city: filteredCommunes.filter((c) => c.type === 'city'),
    town: filteredCommunes.filter((c) => c.type === 'town'),
    village: filteredCommunes.filter((c) => c.type === 'village'),
  };

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex items-start justify-between mb-6">
        <div>
          <button
            onClick={onBack}
            className="flex items-center gap-1 text-gray-600 hover:text-gray-900 mb-2 transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            <span className="text-sm">Back to {department.region_name}</span>
          </button>
          <h2 className="text-2xl font-bold text-gray-900">
            {department.name}{' '}
            <span className="text-gray-400 font-normal">({department.code})</span>
          </h2>
          <p className="text-gray-600 mt-1">
            {department.region_name} region
          </p>
        </div>
        <button
          onClick={() => onGenerateReport('department', department.code, department.name)}
          className="btn btn-primary flex items-center gap-2"
        >
          <FileText className="w-4 h-4" />
          Generate Report
        </button>
      </div>

      {/* Department Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <MapPin className="w-4 h-4" />
            Prefecture
          </div>
          <p className="text-lg font-semibold text-gray-900">{department.prefecture}</p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            <Users className="w-4 h-4" />
            Population
          </div>
          <p className="text-lg font-semibold text-gray-900">
            {department.population.toLocaleString()}
          </p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            Area
          </div>
          <p className="text-lg font-semibold text-gray-900">
            {department.area_km2.toLocaleString()} km²
          </p>
        </div>
        <div className="bg-gray-50 rounded-lg p-4">
          <div className="flex items-center gap-2 text-gray-500 text-sm mb-1">
            Density
          </div>
          <p className="text-lg font-semibold text-gray-900">
            {Math.round(department.population / department.area_km2)}/km²
          </p>
        </div>
      </div>

      {/* Department Map - Shows all communes using GeoJSON */}
      {!loading && (
        <DepartmentMapView
          departmentCode={department.code}
          departmentName={department.name}
          communes={communes}
          onCommuneSelect={onCommuneSelect}
        />
      )}

      {/* Major Cities Quick Access */}
      {department.major_cities.length > 0 && (
        <div className="mb-6">
          <h3 className="text-sm font-medium text-gray-700 mb-2">Major Cities</h3>
          <div className="flex flex-wrap gap-2">
            {department.major_cities.map((city) => {
              const commune = communes.find(
                (c) => c.name.toLowerCase() === city.toLowerCase()
              );
              return (
                <button
                  key={city}
                  onClick={() => {
                    if (commune) {
                      onCommuneSelect(commune);
                    }
                  }}
                  disabled={!commune}
                  className={`px-3 py-1.5 rounded-lg text-sm transition-colors ${
                    commune
                      ? 'bg-primary-100 text-primary-700 hover:bg-primary-200'
                      : 'bg-gray-100 text-gray-500 cursor-not-allowed'
                  }`}
                >
                  {city}
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Search and Filter */}
      <div className="flex flex-col sm:flex-row gap-3 mb-6">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={handleSearch}
            placeholder="Search communes by name or postal code..."
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        <div className="flex gap-2">
          {(['all', 'city', 'town', 'village'] as const).map((type) => (
            <button
              key={type}
              onClick={() => setFilter(type)}
              className={`px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                filter === type
                  ? 'bg-primary-100 text-primary-700'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {type === 'all' ? 'All' : type.charAt(0).toUpperCase() + type.slice(1)}s
            </button>
          ))}
        </div>
      </div>

      {/* Communes List */}
      {loading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-6 h-6 animate-spin text-primary-600" />
          <span className="ml-2 text-gray-600">Loading communes...</span>
        </div>
      ) : filteredCommunes.length > 0 ? (
        <div className="space-y-6">
          {/* Cities */}
          {communesByType.city.length > 0 && (filter === 'all' || filter === 'city') && (
            <CommuneSection
              title="Cities"
              communes={communesByType.city}
              type="city"
              onSelect={onCommuneSelect}
            />
          )}

          {/* Towns */}
          {communesByType.town.length > 0 && (filter === 'all' || filter === 'town') && (
            <CommuneSection
              title="Towns"
              communes={communesByType.town}
              type="town"
              onSelect={onCommuneSelect}
            />
          )}

          {/* Villages */}
          {communesByType.village.length > 0 && (filter === 'all' || filter === 'village') && (
            <CommuneSection
              title="Villages"
              communes={communesByType.village}
              type="village"
              onSelect={onCommuneSelect}
            />
          )}
        </div>
      ) : (
        <div className="text-center py-12 text-gray-500">
          <Search className="w-12 h-12 mx-auto mb-3 opacity-50" />
          <p>No communes found matching your search</p>
          {searchQuery && (
            <button
              onClick={() => setSearchQuery('')}
              className="mt-2 text-primary-600 hover:underline"
            >
              Clear search
            </button>
          )}
        </div>
      )}

      {/* Info Box */}
      <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 className="font-medium text-blue-900 mb-2">
          About Department Reports
        </h4>
        <p className="text-blue-700 text-sm">
          Generate a detailed AI-researched report about {department.name} including
          local economy, housing market, transportation, healthcare facilities,
          schools, and practical information for relocating to this area.
        </p>
      </div>
    </div>
  );
}

/**
 * CommuneSection - Displays a group of communes of the same type
 */
function CommuneSection({
  title,
  communes,
  type,
  onSelect,
}: {
  title: string;
  communes: FranceCommune[];
  type: 'city' | 'town' | 'village';
  onSelect: (commune: FranceCommune) => void;
}) {
  const [expanded, setExpanded] = useState(false);
  const typeInfo = COMMUNE_TYPE_INFO[type];
  const Icon = typeInfo.icon;

  const INITIAL_COUNT = 12;
  const displayedCommunes = expanded ? communes : communes.slice(0, INITIAL_COUNT);
  const hasMore = communes.length > INITIAL_COUNT;

  return (
    <div>
      <div className="flex items-center gap-2 mb-3">
        <span className={`p-1.5 rounded-lg ${typeInfo.color}`}>
          <Icon className="w-4 h-4" />
        </span>
        <h3 className="font-medium text-gray-900">{title}</h3>
        <span className="text-sm text-gray-500">({communes.length})</span>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        {displayedCommunes.map((commune) => (
          <button
            key={commune.code}
            onClick={() => onSelect(commune)}
            className="text-left p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-all group"
          >
            <div className="flex items-start justify-between">
              <div>
                <h4 className="font-medium text-gray-900 group-hover:text-primary-700">
                  {commune.name}
                </h4>
                <p className="text-xs text-gray-500 mt-0.5">
                  {commune.postal_codes[0]}
                </p>
              </div>
              {commune.population > 0 && (
                <span className="text-xs text-gray-400">
                  {commune.population.toLocaleString()}
                </span>
              )}
            </div>
          </button>
        ))}
      </div>
      {hasMore && (
        <div className="mt-3 text-center">
          <button
            onClick={() => setExpanded(!expanded)}
            className="text-sm text-primary-600 hover:text-primary-700 hover:underline font-medium"
          >
            {expanded
              ? `Show less`
              : `Show all ${communes.length} ${type === 'city' ? 'cities' : `${type}s`}`}
          </button>
        </div>
      )}
    </div>
  );
}
