/**
 * FranceMap Component
 *
 * Interactive map of Metropolitan France using react-simple-maps
 * with accurate region boundaries from official GeoJSON data.
 */

import { useState, memo } from 'react';
import { ComposableMap, Geographies, Geography, Marker } from 'react-simple-maps';
import { FileText } from 'lucide-react';
import { FRANCE_REGIONS } from '@/config/research';
import type { FranceRegion } from '@/types';

// GeoJSON URL for French regions (from gregoiredavid/france-geojson)
const FRANCE_REGIONS_GEO_URL =
  'https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/regions.geojson';

interface FranceMapProps {
  onRegionSelect: (region: FranceRegion) => void;
  onGenerateReport: (code: string, name: string) => void;
}

// Region colors by INSEE code
const REGION_COLORS: Record<string, { fill: string; hover: string }> = {
  '11': { fill: '#818CF8', hover: '#6366F1' }, // Île-de-France
  '24': { fill: '#22D3EE', hover: '#06B6D4' }, // Centre-Val de Loire
  '27': { fill: '#34D399', hover: '#10B981' }, // Bourgogne-Franche-Comté
  '28': { fill: '#A78BFA', hover: '#8B5CF6' }, // Normandie
  '32': { fill: '#F472B6', hover: '#EC4899' }, // Hauts-de-France
  '44': { fill: '#FB923C', hover: '#F97316' }, // Grand Est
  '52': { fill: '#4ADE80', hover: '#22C55E' }, // Pays de la Loire
  '53': { fill: '#60A5FA', hover: '#3B82F6' }, // Bretagne
  '75': { fill: '#F87171', hover: '#EF4444' }, // Nouvelle-Aquitaine
  '76': { fill: '#FCD34D', hover: '#FBBF24' }, // Occitanie
  '84': { fill: '#C084FC', hover: '#A855F7' }, // Auvergne-Rhône-Alpes
  '93': { fill: '#2DD4BF', hover: '#14B8A6' }, // Provence-Alpes-Côte d'Azur
  '94': { fill: '#A5B4FC', hover: '#818CF8' }, // Corse
};

// Major cities with coordinates [longitude, latitude]
const MAJOR_CITIES = [
  { name: 'Paris', coordinates: [2.3522, 48.8566] as [number, number], size: 'large' as const },
  { name: 'Lyon', coordinates: [4.8357, 45.7640] as [number, number], size: 'large' as const },
  { name: 'Marseille', coordinates: [5.3698, 43.2965] as [number, number], size: 'large' as const },
  { name: 'Toulouse', coordinates: [1.4442, 43.6047] as [number, number], size: 'large' as const },
  { name: 'Bordeaux', coordinates: [-0.5792, 44.8378] as [number, number], size: 'large' as const },
  { name: 'Nantes', coordinates: [-1.5536, 47.2184] as [number, number], size: 'medium' as const },
  { name: 'Lille', coordinates: [3.0573, 50.6292] as [number, number], size: 'medium' as const },
  { name: 'Strasbourg', coordinates: [7.7521, 48.5734] as [number, number], size: 'medium' as const },
  { name: 'Nice', coordinates: [7.2620, 43.7102] as [number, number], size: 'medium' as const },
  { name: 'Rennes', coordinates: [-1.6778, 48.1173] as [number, number], size: 'medium' as const },
];

function FranceMap({ onRegionSelect, onGenerateReport }: FranceMapProps) {
  const [hoveredRegion, setHoveredRegion] = useState<string | null>(null);
  const [tooltipContent, setTooltipContent] = useState<FranceRegion | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  const handleRegionClick = (regionCode: string) => {
    const region = FRANCE_REGIONS.find((r) => r.code === regionCode);
    if (region) {
      onRegionSelect(region);
    }
  };

  const handleMouseEnter = (geo: { properties: { code: string } }, event: React.MouseEvent) => {
    const code = geo.properties.code;
    setHoveredRegion(code);
    const region = FRANCE_REGIONS.find((r) => r.code === code);
    if (region) {
      setTooltipContent(region);
      const rect = event.currentTarget.closest('.map-container')?.getBoundingClientRect();
      if (rect) {
        setTooltipPos({
          x: event.clientX - rect.left,
          y: event.clientY - rect.top,
        });
      }
    }
  };

  const handleMouseLeave = () => {
    setHoveredRegion(null);
    setTooltipContent(null);
  };

  const handleMouseMove = (event: React.MouseEvent) => {
    const rect = event.currentTarget.closest('.map-container')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
      });
    }
  };

  return (
    <div className="relative h-full flex flex-col">
      {/* Header */}
      <div className="mb-3 px-4">
        <h2 className="text-xl font-semibold text-gray-900">Metropolitan France</h2>
        <p className="text-gray-600 text-sm">
          Click on a region to explore its departments and communes
        </p>
      </div>

      {/* Map Container */}
      <div className="map-container relative flex-1 bg-gradient-to-b from-sky-50 to-blue-100 rounded-xl mx-4 overflow-hidden shadow-inner border border-blue-200">
        <ComposableMap
          projection="geoMercator"
          projectionConfig={{
            center: [2.5, 46.5],
            scale: 3200,
          }}
          style={{
            width: '100%',
            height: 'auto',
            minHeight: '500px',
            aspectRatio: '4/3',
          }}
        >
          <Geographies geography={FRANCE_REGIONS_GEO_URL}>
            {({ geographies }) =>
              geographies.map((geo) => {
                const regionCode = geo.properties.code;
                const colors = REGION_COLORS[regionCode] || { fill: '#CBD5E1', hover: '#94A3B8' };
                const isHovered = hoveredRegion === regionCode;

                return (
                  <Geography
                    key={geo.rsmKey}
                    geography={geo}
                    fill={isHovered ? colors.hover : colors.fill}
                    stroke="#FFFFFF"
                    strokeWidth={1}
                    style={{
                      default: { outline: 'none' },
                      hover: { outline: 'none', cursor: 'pointer' },
                      pressed: { outline: 'none' },
                    }}
                    onClick={() => handleRegionClick(regionCode)}
                    onMouseEnter={(event) => handleMouseEnter(geo, event)}
                    onMouseLeave={handleMouseLeave}
                    onMouseMove={handleMouseMove}
                  />
                );
              })
            }
          </Geographies>

          {/* City markers */}
          {MAJOR_CITIES.map((city) => {
            const markerSize = city.size === 'large' ? 5 : city.size === 'medium' ? 4 : 3;
            return (
              <Marker key={city.name} coordinates={city.coordinates}>
                <circle r={markerSize} fill="#1F2937" stroke="#FFFFFF" strokeWidth={1.5} />
                <text
                  textAnchor="middle"
                  y={-markerSize - 5}
                  style={{
                    fontFamily: 'system-ui',
                    fontSize: city.size === 'large' ? 10 : 9,
                    fontWeight: city.size === 'large' ? 600 : 500,
                    fill: '#1F2937',
                  }}
                >
                  {city.name}
                </text>
              </Marker>
            );
          })}
        </ComposableMap>

        {/* Hover Tooltip */}
        {tooltipContent && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-4 pointer-events-none z-20 min-w-[240px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 120), 360),
              top: Math.max(tooltipPos.y - 10, 60),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <h3 className="font-bold text-gray-900 text-lg">{tooltipContent.name}</h3>
            <p className="text-sm text-gray-500 mb-2">Capital: {tooltipContent.capital}</p>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span className="text-gray-400 text-xs block">Population</span>
                <span className="font-semibold text-gray-900">
                  {(tooltipContent.population / 1000000).toFixed(1)}M
                </span>
              </div>
              <div>
                <span className="text-gray-400 text-xs block">Departments</span>
                <span className="font-semibold text-gray-900">
                  {tooltipContent.departments.length}
                </span>
              </div>
            </div>
            <div className="mt-2 pt-2 border-t border-gray-100">
              <span className="text-xs text-primary-600 font-medium">Click to explore →</span>
            </div>
          </div>
        )}
      </div>

      {/* Region Legend */}
      <div className="mt-3 px-4">
        <h3 className="text-sm font-medium text-gray-700 mb-2">Regions of France</h3>
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-1.5">
          {FRANCE_REGIONS.map((region) => {
            const colors = REGION_COLORS[region.code];
            const isHovered = hoveredRegion === region.code;
            return (
              <button
                key={region.code}
                onClick={() => onRegionSelect(region)}
                onMouseEnter={() => setHoveredRegion(region.code)}
                onMouseLeave={() => setHoveredRegion(null)}
                className={`flex items-center gap-2 px-3 py-2 rounded-lg transition-all text-left ${
                  isHovered ? 'bg-gray-100 shadow-sm' : 'hover:bg-gray-50'
                }`}
              >
                <span
                  className="w-4 h-4 rounded flex-shrink-0 border"
                  style={{
                    backgroundColor: colors?.fill || '#6B7280',
                    borderColor: colors?.hover || '#4B5563',
                  }}
                />
                <span
                  className={`text-sm truncate ${isHovered ? 'text-gray-900 font-medium' : 'text-gray-700'}`}
                >
                  {region.name}
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Actions */}
      <div className="mt-4 px-4 pb-4 flex flex-wrap gap-3">
        <button
          onClick={() => onGenerateReport('FR', 'France')}
          className="btn btn-outline flex items-center gap-2"
        >
          <FileText className="w-4 h-4" />
          Generate France Overview Report
        </button>
      </div>
    </div>
  );
}

export default memo(FranceMap);
