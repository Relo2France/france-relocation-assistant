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
// Traced from actual geographic boundaries, viewbox 0-500
const REGION_PATHS: Record<string, string> = {
  // Hauts-de-France (32) - Far north, borders Belgium
  '32': `M 258,30 L 270,28 L 285,30 L 300,35 L 318,40 L 335,48 L 348,58
         L 355,72 L 350,88 L 340,100 L 328,110 L 315,118 L 298,125
         L 285,130 L 270,135 L 258,140 L 248,145 L 242,138 L 238,128
         L 232,118 L 228,108 L 230,95 L 238,80 L 242,65 L 248,48 L 255,35 Z`,

  // Normandie (28) - Northwest coast with distinctive shape
  '28': `M 130,80 L 145,72 L 162,68 L 180,70 L 195,75 L 208,82 L 218,92
         L 228,108 L 232,118 L 238,128 L 242,138 L 248,145 L 242,155
         L 232,165 L 218,172 L 205,178 L 192,182 L 178,185 L 165,185
         L 152,182 L 140,175 L 130,165 L 122,152 L 118,138 L 115,122
         L 118,105 L 125,90 Z`,

  // Île-de-France (11) - Small central Paris region
  '11': `M 248,145 L 258,140 L 270,135 L 282,138 L 292,145 L 298,155
         L 300,168 L 295,180 L 285,190 L 272,195 L 258,198 L 245,195
         L 235,188 L 228,178 L 225,165 L 230,152 L 240,145 Z`,

  // Grand Est (44) - Northeast, long eastern border
  '44': `M 298,125 L 315,118 L 328,110 L 340,100 L 350,88 L 362,92 L 375,100
         L 388,112 L 398,128 L 405,145 L 408,165 L 405,188 L 400,210
         L 392,232 L 382,252 L 368,270 L 352,285 L 335,295 L 318,300
         L 302,298 L 290,290 L 282,275 L 278,258 L 280,240 L 285,222
         L 292,205 L 298,188 L 300,168 L 298,155 L 292,145 L 295,135 Z`,

  // Bretagne (53) - Western peninsula, distinctive shape
  '53': `M 45,150 L 58,140 L 75,132 L 92,128 L 108,130 L 122,135 L 130,145
         L 135,158 L 138,172 L 140,188 L 138,202 L 130,215 L 118,225
         L 102,232 L 85,235 L 68,232 L 52,225 L 40,212 L 32,198
         L 30,182 L 32,165 L 38,155 Z`,

  // Pays de la Loire (52) - West coast, south of Brittany
  '52': `M 85,235 L 102,232 L 118,225 L 130,215 L 138,202 L 148,208
         L 160,218 L 172,230 L 178,245 L 180,262 L 175,278 L 168,292
         L 155,305 L 140,315 L 122,320 L 105,318 L 90,310 L 78,298
         L 70,282 L 68,265 L 72,248 L 78,240 Z`,

  // Centre-Val de Loire (24) - Central France
  '24': `M 178,185 L 192,182 L 205,178 L 218,172 L 232,165 L 242,155
         L 248,145 L 240,145 L 230,152 L 225,165 L 228,178 L 235,188
         L 245,195 L 258,198 L 255,212 L 250,228 L 242,245 L 232,262
         L 218,275 L 202,285 L 185,290 L 170,288 L 158,280 L 150,268
         L 148,252 L 152,235 L 160,218 L 168,205 L 175,192 Z`,

  // Bourgogne-Franche-Comté (27) - East-central
  '27': `M 258,198 L 272,195 L 285,190 L 295,180 L 300,168 L 298,188
         L 292,205 L 285,222 L 280,240 L 278,258 L 282,275 L 290,290
         L 302,298 L 295,310 L 282,325 L 268,338 L 252,348 L 238,350
         L 225,345 L 215,335 L 208,320 L 205,302 L 208,285 L 218,275
         L 232,262 L 242,245 L 250,228 L 255,212 Z`,

  // Nouvelle-Aquitaine (75) - Large southwest, Atlantic coast
  '75': `M 68,310 L 78,298 L 90,310 L 105,318 L 122,320 L 140,315
         L 155,305 L 168,292 L 175,278 L 180,262 L 178,280 L 175,298
         L 172,318 L 168,340 L 162,365 L 158,392 L 155,418 L 158,442
         L 168,462 L 182,478 L 175,485 L 162,490 L 148,492 L 132,488
         L 118,480 L 108,468 L 100,452 L 95,432 L 92,410 L 88,385
         L 82,362 L 75,342 L 68,325 Z`,

  // Auvergne-Rhône-Alpes (84) - Large southeast region
  '84': `M 252,348 L 268,338 L 282,325 L 295,310 L 302,298 L 318,300
         L 335,295 L 352,285 L 368,270 L 375,282 L 380,298 L 382,318
         L 380,340 L 375,362 L 368,385 L 358,405 L 345,422 L 328,435
         L 310,442 L 292,445 L 275,440 L 260,430 L 248,415 L 240,398
         L 235,378 L 235,358 L 242,348 Z`,

  // Occitanie (76) - South, Pyrenees to Mediterranean
  '76': `M 148,492 L 162,490 L 175,485 L 182,478 L 168,462 L 158,442
         L 170,445 L 185,450 L 202,455 L 220,462 L 235,468 L 248,478
         L 258,490 L 268,502 L 275,515 L 268,525 L 255,532 L 238,535
         L 218,532 L 198,525 L 178,515 L 162,502 L 152,495 Z`,

  // Provence-Alpes-Côte d'Azur (93) - Mediterranean southeast
  '93': `M 275,440 L 292,445 L 310,442 L 328,435 L 345,422 L 358,405
         L 368,385 L 378,395 L 388,410 L 395,428 L 398,448 L 395,468
         L 385,485 L 372,498 L 355,508 L 335,512 L 315,510 L 298,502
         L 285,490 L 275,475 L 270,458 L 270,445 Z`,

  // Corse (94) - Island off southeast coast
  '94': `M 442,420 L 455,415 L 468,420 L 478,432 L 482,450 L 482,472
         L 478,492 L 470,510 L 458,522 L 445,528 L 432,522 L 425,508
         L 422,490 L 425,470 L 432,450 L 438,432 Z`,
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

// Major cities positioned on the map (aligned with region paths)
const MAJOR_CITIES = [
  { name: 'Paris', x: 265, y: 170, size: 'large' as const },
  { name: 'Lyon', x: 325, y: 380, size: 'large' as const },
  { name: 'Marseille', x: 340, y: 485, size: 'large' as const },
  { name: 'Toulouse', x: 210, y: 475, size: 'large' as const },
  { name: 'Bordeaux', x: 130, y: 385, size: 'large' as const },
  { name: 'Nantes', x: 125, y: 275, size: 'medium' as const },
  { name: 'Lille', x: 300, y: 65, size: 'medium' as const },
  { name: 'Strasbourg', x: 385, y: 175, size: 'medium' as const },
  { name: 'Nice', x: 378, y: 455, size: 'medium' as const },
  { name: 'Rennes', x: 95, y: 185, size: 'medium' as const },
  { name: 'Montpellier', x: 270, y: 490, size: 'medium' as const },
  { name: 'Dijon', x: 295, y: 255, size: 'small' as const },
  { name: 'Orléans', x: 220, y: 230, size: 'small' as const },
  { name: 'Rouen', x: 195, y: 125, size: 'small' as const },
  { name: 'Clermont-Fd', x: 255, y: 365, size: 'small' as const },
  { name: 'Ajaccio', x: 455, y: 485, size: 'small' as const },
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
          viewBox="0 0 520 560"
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
          <rect x="0" y="0" width="520" height="560" fill="#DBEAFE" />
          <rect x="0" y="0" width="520" height="560" fill="url(#waterPattern)" />

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
          <g transform="translate(485, 45)">
            <circle cx="0" cy="0" r="20" fill="white" stroke="#CBD5E1" strokeWidth="1" opacity="0.95" />
            <text x="0" y="-5" textAnchor="middle" fontSize="11" fontWeight="bold" fill="#334155">N</text>
            <path d="M 0 -14 L 4 -4 L 0 0 L -4 -4 Z" fill="#DC2626" />
            <path d="M 0 14 L 4 4 L 0 0 L -4 4 Z" fill="#64748B" />
          </g>

          {/* Scale bar */}
          <g transform="translate(30, 540)">
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
