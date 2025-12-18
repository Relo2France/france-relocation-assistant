/**
 * FranceMap Component
 *
 * Interactive SVG map of Metropolitan France with clickable regions.
 * Flat color design with hover effects and major city labels.
 */

import { useState } from 'react';
import { FileText } from 'lucide-react';
import { FRANCE_REGIONS } from '@/config/research';
import type { FranceRegion } from '@/types';

interface FranceMapProps {
  onRegionSelect: (region: FranceRegion) => void;
  onGenerateReport: (code: string, name: string) => void;
}

// SVG paths for each Metropolitan France region (using INSEE codes)
// Simplified paths for clean flat design
const REGION_PATHS: Record<string, string> = {
  '11': 'M 280,180 L 310,165 L 340,175 L 345,200 L 330,220 L 295,215 L 275,195 Z', // Île-de-France
  '24': 'M 235,200 L 275,195 L 295,215 L 330,220 L 325,260 L 290,290 L 240,275 L 220,235 Z', // Centre-Val de Loire
  '27': 'M 330,220 L 345,200 L 380,195 L 420,210 L 430,260 L 400,300 L 350,290 L 325,260 Z', // Bourgogne-Franche-Comté
  '28': 'M 180,120 L 230,100 L 280,115 L 280,180 L 235,200 L 200,180 L 170,150 Z', // Normandie
  '32': 'M 280,115 L 310,90 L 360,95 L 380,130 L 380,170 L 340,175 L 310,165 L 280,180 Z', // Hauts-de-France
  '44': 'M 380,130 L 420,120 L 465,140 L 470,190 L 430,210 L 380,195 L 380,170 Z', // Grand Est
  '53': 'M 80,160 L 140,145 L 170,150 L 180,180 L 160,210 L 110,220 L 60,200 Z', // Bretagne
  '52': 'M 110,220 L 160,210 L 180,180 L 200,180 L 220,235 L 200,280 L 140,290 L 100,260 Z', // Pays de la Loire
  '75': 'M 100,260 L 140,290 L 200,280 L 240,275 L 250,340 L 230,400 L 160,420 L 120,380 L 90,320 Z', // Nouvelle-Aquitaine
  '76': 'M 230,400 L 250,340 L 290,290 L 350,290 L 370,340 L 390,400 L 350,440 L 270,450 L 230,430 Z', // Occitanie
  '84': 'M 350,290 L 400,300 L 430,260 L 470,270 L 470,330 L 440,380 L 390,400 L 370,340 Z', // Auvergne-Rhône-Alpes
  '93': 'M 390,400 L 440,380 L 470,330 L 510,360 L 520,420 L 480,450 L 420,440 L 390,420 Z', // Provence-Alpes-Côte d'Azur
  '94': 'M 530,450 L 545,440 L 555,460 L 555,510 L 540,530 L 525,510 L 525,470 Z', // Corse
};

// Region colors (flat design palette) keyed by INSEE region code
const REGION_COLORS: Record<string, { fill: string; hover: string }> = {
  '11': { fill: '#4F46E5', hover: '#4338CA' },  // Île-de-France - Indigo
  '24': { fill: '#0891B2', hover: '#0E7490' },  // Centre-Val de Loire - Cyan
  '27': { fill: '#059669', hover: '#047857' },  // Bourgogne-Franche-Comté - Emerald
  '28': { fill: '#7C3AED', hover: '#6D28D9' },  // Normandie - Violet
  '32': { fill: '#DB2777', hover: '#BE185D' },  // Hauts-de-France - Pink
  '44': { fill: '#EA580C', hover: '#C2410C' },  // Grand Est - Orange
  '53': { fill: '#2563EB', hover: '#1D4ED8' },  // Bretagne - Blue
  '52': { fill: '#16A34A', hover: '#15803D' },  // Pays de la Loire - Green
  '75': { fill: '#DC2626', hover: '#B91C1C' },  // Nouvelle-Aquitaine - Red
  '76': { fill: '#CA8A04', hover: '#A16207' },  // Occitanie - Yellow
  '84': { fill: '#9333EA', hover: '#7E22CE' },  // Auvergne-Rhône-Alpes - Purple
  '93': { fill: '#0D9488', hover: '#0F766E' },  // Provence-Alpes-Côte d'Azur - Teal
  '94': { fill: '#6366F1', hover: '#4F46E5' },  // Corse - Indigo
};

// Major cities with approximate positions on the map
const MAJOR_CITIES = [
  { name: 'Paris', x: 310, y: 190 },
  { name: 'Lyon', x: 420, y: 320 },
  { name: 'Marseille', x: 450, y: 420 },
  { name: 'Toulouse', x: 280, y: 400 },
  { name: 'Bordeaux', x: 170, y: 350 },
  { name: 'Nantes', x: 140, y: 250 },
  { name: 'Lille', x: 340, y: 105 },
  { name: 'Strasbourg', x: 465, y: 160 },
  { name: 'Nice', x: 500, y: 400 },
  { name: 'Rennes', x: 130, y: 180 },
];

export default function FranceMap({ onRegionSelect, onGenerateReport }: FranceMapProps) {
  const [hoveredRegion, setHoveredRegion] = useState<string | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  const handleRegionClick = (regionCode: string) => {
    const region = FRANCE_REGIONS.find(r => r.code === regionCode);
    if (region) {
      onRegionSelect(region);
    }
  };

  const handleMouseMove = (e: React.MouseEvent, regionCode: string) => {
    const rect = e.currentTarget.closest('svg')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: e.clientX - rect.left,
        y: e.clientY - rect.top - 10,
      });
    }
    setHoveredRegion(regionCode);
  };

  const hoveredRegionData = hoveredRegion
    ? FRANCE_REGIONS.find(r => r.code === hoveredRegion)
    : null;

  return (
    <div className="relative p-6">
      {/* Header */}
      <div className="mb-4">
        <h2 className="text-xl font-semibold text-gray-900">Metropolitan France</h2>
        <p className="text-gray-600 text-sm">
          Click on a region to explore departments and communes
        </p>
      </div>

      {/* Map Container */}
      <div className="relative bg-blue-50 rounded-lg overflow-hidden">
        <svg
          viewBox="40 70 540 480"
          className="w-full h-auto max-h-[600px]"
          aria-label="Interactive map of Metropolitan France"
          role="img"
        >
          {/* Background sea */}
          <rect x="40" y="70" width="540" height="480" fill="#E0F2FE" />

          {/* Region paths */}
          {Object.entries(REGION_PATHS).map(([code, path]) => {
            const colors = REGION_COLORS[code];
            const isHovered = hoveredRegion === code;

            return (
              <path
                key={code}
                d={path}
                fill={isHovered ? colors.hover : colors.fill}
                stroke="#fff"
                strokeWidth="2"
                className="cursor-pointer transition-all duration-200"
                onClick={() => handleRegionClick(code)}
                onMouseMove={(e) => handleMouseMove(e, code)}
                onMouseLeave={() => setHoveredRegion(null)}
                role="button"
                aria-label={FRANCE_REGIONS.find(r => r.code === code)?.name || code}
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    handleRegionClick(code);
                  }
                }}
              />
            );
          })}

          {/* Major city markers */}
          {MAJOR_CITIES.map((city) => (
            <g key={city.name}>
              <circle
                cx={city.x}
                cy={city.y}
                r="4"
                fill="#1F2937"
                stroke="#fff"
                strokeWidth="1.5"
              />
              <text
                x={city.x}
                y={city.y - 8}
                textAnchor="middle"
                className="text-[10px] font-medium fill-gray-800"
                style={{ pointerEvents: 'none' }}
              >
                {city.name}
              </text>
            </g>
          ))}
        </svg>

        {/* Tooltip */}
        {hoveredRegionData && (
          <div
            className="absolute bg-white rounded-lg shadow-lg border border-gray-200 p-3 pointer-events-none z-10 min-w-[200px]"
            style={{
              left: tooltipPos.x,
              top: tooltipPos.y,
              transform: 'translate(-50%, -100%)',
            }}
          >
            <h3 className="font-semibold text-gray-900">{hoveredRegionData.name}</h3>
            <p className="text-sm text-gray-600">Capital: {hoveredRegionData.capital}</p>
            <div className="grid grid-cols-2 gap-2 mt-2 text-xs">
              <div>
                <span className="text-gray-500">Population:</span>
                <br />
                <span className="font-medium">{hoveredRegionData.population.toLocaleString()}</span>
              </div>
              <div>
                <span className="text-gray-500">Departments:</span>
                <br />
                <span className="font-medium">{hoveredRegionData.departments.length}</span>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Legend */}
      <div className="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
        {FRANCE_REGIONS.map((region) => (
          <button
            key={region.code}
            onClick={() => onRegionSelect(region)}
            className="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors text-left group"
          >
            <span
              className="w-3 h-3 rounded-sm flex-shrink-0"
              style={{ backgroundColor: REGION_COLORS[region.code]?.fill || '#6B7280' }}
            />
            <span className="text-sm text-gray-700 group-hover:text-gray-900 truncate">
              {region.name}
            </span>
          </button>
        ))}
      </div>

      {/* Quick Actions */}
      <div className="mt-6 flex flex-wrap gap-3">
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
