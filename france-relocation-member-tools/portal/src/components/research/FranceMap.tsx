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

// More accurate SVG paths for Metropolitan France regions
// Based on simplified geographic boundaries, scaled to fit viewbox 0-500
const REGION_PATHS: Record<string, string> = {
  // Île-de-France - small region around Paris
  '11': `M 247,152 Q 252,148 260,146 Q 270,145 278,148 Q 285,152 288,160
         Q 290,168 287,176 Q 283,183 275,187 Q 267,190 258,188
         Q 250,185 245,178 Q 242,170 244,162 Z`,

  // Centre-Val de Loire - Loire valley region
  '24': `M 195,188 Q 210,182 225,180 Q 242,178 258,188 Q 270,195 275,208
         Q 278,222 272,238 Q 265,255 250,268 Q 232,280 212,278
         Q 190,275 175,260 Q 162,245 165,225 Q 170,205 185,195 Z`,

  // Bourgogne-Franche-Comté - eastern central region
  '27': `M 275,187 Q 288,180 305,178 Q 325,178 342,188 Q 358,200 365,218
         Q 370,238 362,258 Q 352,278 335,290 Q 315,300 292,295
         Q 272,290 260,275 Q 250,258 255,238 Q 262,215 275,200 Z`,

  // Normandie - northwestern coast
  '28': `M 128,108 Q 150,95 175,92 Q 202,90 225,100 Q 245,112 252,128
         Q 257,142 252,152 Q 245,160 232,165 Q 215,170 195,168
         Q 172,165 152,155 Q 135,142 130,125 Z`,

  // Hauts-de-France - northern region
  '32': `M 225,100 Q 248,88 275,82 Q 305,78 330,88 Q 350,100 358,118
         Q 362,135 352,150 Q 340,162 322,168 Q 300,172 280,168
         Q 262,162 252,152 Q 245,138 248,122 Q 252,108 265,100 Z`,

  // Grand Est - northeastern region
  '44': `M 322,168 Q 342,158 362,155 Q 385,155 405,168 Q 420,182 425,202
         Q 428,225 418,248 Q 405,268 385,278 Q 362,285 342,278
         Q 325,270 318,252 Q 312,232 318,212 Q 325,192 338,178 Z`,

  // Bretagne - northwestern peninsula
  '53': `M 55,148 Q 78,138 102,135 Q 128,135 148,145 Q 162,158 160,175
         Q 155,192 138,202 Q 118,212 95,210 Q 70,205 52,190
         Q 38,175 42,158 Z`,

  // Pays de la Loire - western region
  '52': `M 95,210 Q 118,205 140,202 Q 162,200 180,208 Q 195,220 195,240
         Q 192,262 175,280 Q 155,295 130,295 Q 105,292 85,278
         Q 68,262 68,240 Q 72,220 85,212 Z`,

  // Nouvelle-Aquitaine - large southwestern region
  '75': `M 85,278 Q 108,285 130,295 Q 155,305 172,320 Q 188,338 190,360
         Q 190,385 178,408 Q 162,430 140,442 Q 115,452 88,445
         Q 65,435 52,412 Q 42,388 48,360 Q 55,332 68,310 Q 78,292 88,282 Z`,

  // Occitanie - southern region
  '76': `M 140,442 Q 165,432 190,422 Q 218,415 248,418 Q 280,422 305,438
         Q 325,455 328,475 Q 325,495 305,508 Q 280,518 250,515
         Q 218,512 188,500 Q 162,485 148,465 Q 138,448 140,440 Z`,

  // Auvergne-Rhône-Alpes - large eastern region with Alps
  '84': `M 260,275 Q 285,268 310,270 Q 338,275 360,290 Q 378,308 385,332
         Q 388,358 375,382 Q 358,405 335,415 Q 308,422 280,418
         Q 255,412 238,395 Q 225,375 228,350 Q 235,322 250,300 Z`,

  // Provence-Alpes-Côte d'Azur - southeastern Mediterranean
  '93': `M 308,415 Q 335,408 360,405 Q 388,405 412,418 Q 432,432 438,452
         Q 440,472 425,488 Q 405,502 378,505 Q 350,505 325,495
         Q 305,482 300,462 Q 298,442 305,425 Z`,

  // Corse - Mediterranean island
  '94': `M 438,435 Q 448,428 460,430 Q 472,435 478,450 Q 482,468 478,488
         Q 472,508 460,518 Q 448,525 438,518 Q 430,508 430,488
         Q 432,465 438,448 Z`,
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

// Major cities positioned on the map
const MAJOR_CITIES = [
  { name: 'Paris', x: 265, y: 165, size: 'large' as const },
  { name: 'Lyon', x: 320, y: 345, size: 'large' as const },
  { name: 'Marseille', x: 365, y: 460, size: 'large' as const },
  { name: 'Toulouse', x: 200, y: 435, size: 'large' as const },
  { name: 'Bordeaux', x: 125, y: 365, size: 'large' as const },
  { name: 'Nantes', x: 115, y: 248, size: 'medium' as const },
  { name: 'Lille', x: 295, y: 95, size: 'medium' as const },
  { name: 'Strasbourg', x: 405, y: 195, size: 'medium' as const },
  { name: 'Nice', x: 420, y: 455, size: 'medium' as const },
  { name: 'Rennes', x: 115, y: 175, size: 'medium' as const },
  { name: 'Montpellier', x: 275, y: 462, size: 'medium' as const },
  { name: 'Dijon', x: 328, y: 232, size: 'small' as const },
  { name: 'Orléans', x: 232, y: 205, size: 'small' as const },
  { name: 'Rouen', x: 195, y: 130, size: 'small' as const },
  { name: 'Clermont-Fd', x: 275, y: 335, size: 'small' as const },
  { name: 'Ajaccio', x: 458, y: 485, size: 'small' as const },
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
          viewBox="20 60 480 480"
          className="w-full h-auto"
          style={{ maxHeight: '60vh', minHeight: '400px' }}
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
          <rect x="20" y="60" width="480" height="480" fill="#DBEAFE" />
          <rect x="20" y="60" width="480" height="480" fill="url(#waterPattern)" />

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
          <g transform="translate(455, 95)">
            <circle cx="0" cy="0" r="20" fill="white" stroke="#CBD5E1" strokeWidth="1" opacity="0.95" />
            <text x="0" y="-5" textAnchor="middle" fontSize="11" fontWeight="bold" fill="#334155">N</text>
            <path d="M 0 -14 L 4 -4 L 0 0 L -4 -4 Z" fill="#DC2626" />
            <path d="M 0 14 L 4 4 L 0 0 L -4 4 Z" fill="#64748B" />
          </g>

          {/* Scale bar */}
          <g transform="translate(50, 520)">
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
