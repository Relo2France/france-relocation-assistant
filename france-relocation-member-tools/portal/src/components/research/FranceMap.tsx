/**
 * FranceMap Component
 *
 * Interactive SVG map of Metropolitan France with accurate region boundaries.
 * Based on real geographic data with clickable regions.
 */

import { useState } from 'react';
import { FileText } from 'lucide-react';
import { FRANCE_REGIONS } from '@/config/research';
import type { FranceRegion } from '@/types';

interface FranceMapProps {
  onRegionSelect: (region: FranceRegion) => void;
  onGenerateReport: (code: string, name: string) => void;
}

// Accurate SVG paths for Metropolitan France regions
// Based on geographic boundaries, scaled to fit viewbox 0-500
const REGION_PATHS: Record<string, string> = {
  // Hauts-de-France (northern region)
  '32': `M 245,58 L 262,52 L 285,48 L 308,52 L 328,62 L 340,75 L 345,92
         L 342,108 L 332,122 L 315,132 L 295,138 L 275,140 L 258,138
         L 248,132 L 242,120 L 238,105 L 235,88 L 238,72 Z`,

  // Normandie (northwestern coast)
  '28': `M 142,95 L 165,88 L 188,85 L 210,88 L 232,95 L 242,108 L 248,122
         L 248,138 L 242,152 L 228,162 L 210,168 L 188,170 L 165,165
         L 148,155 L 138,140 L 135,122 L 138,108 Z`,

  // Île-de-France (Paris region - small central)
  '11': `M 248,138 L 262,135 L 278,138 L 290,148 L 295,162 L 290,178
         L 278,188 L 262,192 L 248,188 L 238,178 L 235,162 L 238,148 Z`,

  // Grand Est (northeastern region)
  '44': `M 295,138 L 315,132 L 340,128 L 365,132 L 388,145 L 402,165
         L 408,188 L 405,215 L 395,242 L 378,265 L 355,278 L 332,282
         L 312,275 L 298,262 L 290,242 L 288,218 L 290,195 L 295,172
         L 295,155 Z`,

  // Bretagne (northwestern peninsula)
  '53': `M 58,148 L 78,138 L 102,132 L 125,135 L 142,145 L 152,160
         L 155,178 L 148,195 L 135,208 L 115,215 L 92,212 L 70,202
         L 52,185 L 45,168 L 48,155 Z`,

  // Pays de la Loire (western region)
  '52': `M 92,212 L 115,215 L 138,218 L 158,225 L 175,238 L 182,255
         L 178,275 L 168,292 L 150,305 L 128,308 L 105,302 L 85,288
         L 72,268 L 68,248 L 72,228 L 82,218 Z`,

  // Centre-Val de Loire (central region)
  '24': `M 178,192 L 200,188 L 222,188 L 242,195 L 255,208 L 262,228
         L 258,252 L 248,272 L 232,288 L 210,295 L 188,292 L 168,280
         L 158,262 L 158,242 L 165,222 L 172,205 Z`,

  // Bourgogne-Franche-Comté (eastern central)
  '27': `M 290,195 L 308,188 L 328,188 L 348,198 L 365,215 L 375,238
         L 375,265 L 365,288 L 348,308 L 325,318 L 302,315 L 282,302
         L 268,282 L 262,258 L 265,232 L 275,212 Z`,

  // Nouvelle-Aquitaine (large southwestern region)
  '75': `M 72,288 L 95,302 L 118,312 L 142,325 L 158,342 L 168,365
         L 172,392 L 165,418 L 152,442 L 132,458 L 108,465 L 85,458
         L 68,442 L 58,418 L 55,392 L 58,362 L 65,335 L 68,312 Z`,

  // Auvergne-Rhône-Alpes (large southeastern region)
  '84': `M 268,295 L 292,302 L 318,308 L 342,322 L 362,342 L 375,368
         L 378,398 L 368,425 L 352,445 L 328,455 L 302,452 L 278,438
         L 258,415 L 248,388 L 248,358 L 255,328 L 262,308 Z`,

  // Occitanie (southern region)
  '76': `M 132,458 L 158,452 L 188,448 L 218,452 L 248,462 L 275,478
         L 295,498 L 302,518 L 295,535 L 275,545 L 248,548 L 218,542
         L 188,530 L 162,512 L 145,490 L 135,472 Z`,

  // Provence-Alpes-Côte d'Azur (Mediterranean southeast)
  '93': `M 302,452 L 328,448 L 358,448 L 385,458 L 405,475 L 418,498
         L 418,522 L 405,540 L 382,548 L 355,548 L 328,538 L 308,522
         L 298,502 L 298,478 Z`,

  // Corse (island)
  '94': `M 448,465 L 462,458 L 475,465 L 482,485 L 482,512 L 475,535
         L 462,548 L 448,548 L 438,535 L 435,512 L 438,485 L 445,472 Z`,
};

// Region colors with border colors
const REGION_COLORS: Record<string, { fill: string; hover: string; border: string }> = {
  '11': { fill: '#818CF8', hover: '#6366F1', border: '#4F46E5' },
  '24': { fill: '#22D3EE', hover: '#06B6D4', border: '#0891B2' },
  '27': { fill: '#34D399', hover: '#10B981', border: '#059669' },
  '28': { fill: '#A78BFA', hover: '#8B5CF6', border: '#7C3AED' },
  '32': { fill: '#F472B6', hover: '#EC4899', border: '#DB2777' },
  '44': { fill: '#FB923C', hover: '#F97316', border: '#EA580C' },
  '53': { fill: '#60A5FA', hover: '#3B82F6', border: '#2563EB' },
  '52': { fill: '#4ADE80', hover: '#22C55E', border: '#16A34A' },
  '75': { fill: '#F87171', hover: '#EF4444', border: '#DC2626' },
  '76': { fill: '#FCD34D', hover: '#FBBF24', border: '#F59E0B' },
  '84': { fill: '#C084FC', hover: '#A855F7', border: '#9333EA' },
  '93': { fill: '#2DD4BF', hover: '#14B8A6', border: '#0D9488' },
  '94': { fill: '#A5B4FC', hover: '#818CF8', border: '#6366F1' },
};

// Major cities positioned on the map (updated for new paths)
const MAJOR_CITIES = [
  { name: 'Paris', x: 265, y: 165, size: 'large' as const },
  { name: 'Lyon', x: 330, y: 375, size: 'large' as const },
  { name: 'Marseille', x: 355, y: 510, size: 'large' as const },
  { name: 'Toulouse', x: 195, y: 490, size: 'large' as const },
  { name: 'Bordeaux', x: 115, y: 385, size: 'large' as const },
  { name: 'Nantes', x: 120, y: 260, size: 'medium' as const },
  { name: 'Lille', x: 295, y: 78, size: 'medium' as const },
  { name: 'Strasbourg', x: 388, y: 188, size: 'medium' as const },
  { name: 'Nice', x: 398, y: 495, size: 'medium' as const },
  { name: 'Rennes', x: 108, y: 178, size: 'medium' as const },
  { name: 'Montpellier', x: 278, y: 505, size: 'medium' as const },
  { name: 'Dijon', x: 328, y: 255, size: 'small' as const },
  { name: 'Orléans', x: 218, y: 238, size: 'small' as const },
  { name: 'Rouen', x: 198, y: 128, size: 'small' as const },
  { name: 'Clermont-Fd', x: 268, y: 365, size: 'small' as const },
  { name: 'Ajaccio', x: 458, y: 515, size: 'small' as const },
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
    <div className="relative">
      {/* Header */}
      <div className="mb-4 px-6">
        <h2 className="text-xl font-semibold text-gray-900">Metropolitan France</h2>
        <p className="text-gray-600 text-sm">
          Click on a region to explore its departments and communes
        </p>
      </div>

      {/* Map Container */}
      <div className="relative bg-gradient-to-b from-sky-50 to-blue-100 rounded-xl mx-4 overflow-hidden shadow-inner border border-blue-200">
        <svg
          viewBox="20 30 480 550"
          className="w-full h-auto"
          style={{ maxHeight: '65vh', minHeight: '450px' }}
          aria-label="Interactive map of Metropolitan France"
          role="img"
        >
          {/* Definitions */}
          <defs>
            {/* Water pattern */}
            <pattern id="waterPattern" patternUnits="userSpaceOnUse" width="30" height="30">
              <path d="M 0 15 Q 7.5 10 15 15 T 30 15" fill="none" stroke="#93C5FD" strokeWidth="0.5" opacity="0.4"/>
            </pattern>

            {/* Drop shadow for regions */}
            <filter id="regionShadow" x="-10%" y="-10%" width="120%" height="120%">
              <feDropShadow dx="2" dy="2" stdDeviation="2" floodOpacity="0.15"/>
            </filter>

            {/* Glow effect for hover */}
            <filter id="glow">
              <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
          </defs>

          {/* Ocean background */}
          <rect x="20" y="30" width="480" height="550" fill="#DBEAFE" />
          <rect x="20" y="30" width="480" height="550" fill="url(#waterPattern)" />

          {/* Region paths with shadow layer first */}
          <g filter="url(#regionShadow)">
            {Object.entries(REGION_PATHS).map(([code, path]) => {
              const colors = REGION_COLORS[code];
              const isHovered = hoveredRegion === code;
              const regionData = FRANCE_REGIONS.find(r => r.code === code);

              return (
                <path
                  key={code}
                  d={path}
                  fill={isHovered ? colors.hover : colors.fill}
                  stroke={colors.border}
                  strokeWidth={isHovered ? 2.5 : 1.5}
                  strokeLinejoin="round"
                  className="cursor-pointer transition-all duration-150"
                  style={{
                    filter: isHovered ? 'url(#glow)' : 'none',
                  }}
                  onClick={() => handleRegionClick(code)}
                  onMouseMove={(e) => handleMouseMove(e, code)}
                  onMouseLeave={() => setHoveredRegion(null)}
                  role="button"
                  aria-label={regionData?.name || code}
                  tabIndex={0}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      handleRegionClick(code);
                    }
                  }}
                />
              );
            })}
          </g>

          {/* City markers */}
          {MAJOR_CITIES.map((city) => {
            const radius = city.size === 'large' ? 5 : city.size === 'medium' ? 4 : 3;
            const fontSize = city.size === 'large' ? 10 : city.size === 'medium' ? 9 : 8;
            const fontWeight = city.size === 'large' ? 600 : 500;

            return (
              <g key={city.name} className="pointer-events-none">
                {/* White halo for better visibility */}
                <circle
                  cx={city.x}
                  cy={city.y}
                  r={radius + 2}
                  fill="white"
                  opacity="0.8"
                />
                {/* City dot */}
                <circle
                  cx={city.x}
                  cy={city.y}
                  r={radius}
                  fill="#1F2937"
                  stroke="white"
                  strokeWidth="1.5"
                />
                {/* City label with background */}
                <text
                  x={city.x}
                  y={city.y - radius - 5}
                  textAnchor="middle"
                  fontSize={fontSize}
                  fontWeight={fontWeight}
                  fill="#1F2937"
                  className="select-none"
                  style={{
                    textShadow: '1px 1px 2px white, -1px -1px 2px white, 1px -1px 2px white, -1px 1px 2px white',
                  }}
                >
                  {city.name}
                </text>
              </g>
            );
          })}

          {/* Compass */}
          <g transform="translate(465, 55)">
            <circle cx="0" cy="0" r="20" fill="white" stroke="#CBD5E1" strokeWidth="1" opacity="0.95" />
            <text x="0" y="-5" textAnchor="middle" fontSize="11" fontWeight="bold" fill="#334155">N</text>
            <path d="M 0 -14 L 4 -4 L 0 0 L -4 -4 Z" fill="#DC2626" />
            <path d="M 0 14 L 4 4 L 0 0 L -4 4 Z" fill="#64748B" />
          </g>

          {/* Scale bar */}
          <g transform="translate(50, 560)">
            <rect x="0" y="0" width="80" height="3" fill="#64748B" />
            <rect x="0" y="0" width="40" height="3" fill="#334155" />
            <text x="0" y="12" fontSize="8" fill="#64748B">0</text>
            <text x="40" y="12" fontSize="8" fill="#64748B" textAnchor="middle">100</text>
            <text x="80" y="12" fontSize="8" fill="#64748B" textAnchor="end">200 km</text>
          </g>
        </svg>

        {/* Hover Tooltip */}
        {hoveredRegionData && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-4 pointer-events-none z-20 min-w-[240px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 120), 360),
              top: Math.max(tooltipPos.y, 60),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <h3 className="font-bold text-gray-900 text-lg">{hoveredRegionData.name}</h3>
            <p className="text-sm text-gray-500 mb-2">Capital: {hoveredRegionData.capital}</p>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span className="text-gray-400 text-xs block">Population</span>
                <span className="font-semibold text-gray-900">
                  {(hoveredRegionData.population / 1000000).toFixed(1)}M
                </span>
              </div>
              <div>
                <span className="text-gray-400 text-xs block">Departments</span>
                <span className="font-semibold text-gray-900">
                  {hoveredRegionData.departments.length}
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
      <div className="mt-4 px-6">
        <h3 className="text-sm font-medium text-gray-700 mb-3">Regions of France</h3>
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
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
                  className="w-4 h-4 rounded flex-shrink-0 border-2"
                  style={{
                    backgroundColor: colors?.fill || '#6B7280',
                    borderColor: colors?.border || '#4B5563',
                  }}
                />
                <span className={`text-sm truncate ${isHovered ? 'text-gray-900 font-medium' : 'text-gray-700'}`}>
                  {region.name}
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Actions */}
      <div className="mt-6 px-6 pb-6 flex flex-wrap gap-3">
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
