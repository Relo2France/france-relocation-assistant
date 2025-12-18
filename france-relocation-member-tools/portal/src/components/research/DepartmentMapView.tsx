/**
 * DepartmentMapView Component
 *
 * Displays an SVG map of a department showing cities, towns, and villages.
 * Settlements are clickable to drill down further.
 */

import { useState } from 'react';
import { getDepartmentMap, SETTLEMENT_COLORS, type SettlementMarker } from '@/config/departmentMaps';
import type { FranceCommune } from '@/types';

interface DepartmentMapViewProps {
  departmentCode: string;
  departmentName: string;
  communes: FranceCommune[];
  onCommuneSelect: (commune: FranceCommune) => void;
}

export default function DepartmentMapView({
  departmentCode,
  departmentName,
  communes,
  onCommuneSelect,
}: DepartmentMapViewProps) {
  const [hoveredSettlement, setHoveredSettlement] = useState<SettlementMarker | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  const mapData = getDepartmentMap(departmentCode);

  if (!mapData) {
    return null;
  }

  const handleSettlementClick = (settlement: SettlementMarker) => {
    // Find matching commune
    const commune = communes.find(
      (c) => c.name.toLowerCase() === settlement.name.toLowerCase()
    );
    if (commune) {
      onCommuneSelect(commune);
    }
  };

  const handleMouseMove = (e: React.MouseEvent, settlement: SettlementMarker) => {
    const rect = e.currentTarget.closest('svg')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: e.clientX - rect.left,
        y: e.clientY - rect.top - 10,
      });
    }
    setHoveredSettlement(settlement);
  };

  // Sort settlements by population (largest first) for rendering order
  const sortedSettlements = [...mapData.settlements].sort(
    (a, b) => (b.population || 0) - (a.population || 0)
  );

  return (
    <div className="relative mb-6">
      <h3 className="text-sm font-medium text-gray-700 mb-3">
        Map of {departmentName}
      </h3>

      <div className="relative bg-gradient-to-br from-emerald-50 to-green-100 rounded-xl overflow-hidden shadow-inner border border-green-200">
        <svg
          viewBox={mapData.viewBox}
          className="w-full h-auto"
          style={{ maxHeight: '400px', minHeight: '280px' }}
          aria-label={`Map of ${departmentName} showing settlements`}
          role="img"
        >
          {/* Definitions */}
          <defs>
            <filter id="deptOutlineShadow" x="-10%" y="-10%" width="120%" height="120%">
              <feDropShadow dx="2" dy="2" stdDeviation="3" floodOpacity="0.2" />
            </filter>
            <filter id="settlementGlow">
              <feGaussianBlur stdDeviation="2" result="coloredBlur" />
              <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
            {/* Pattern for department fill */}
            <pattern id="deptPattern" patternUnits="userSpaceOnUse" width="8" height="8">
              <path
                d="M 0,4 l 4,-4 M 4,8 l 4,-4 M -1,1 l 2,-2 M 7,9 l 2,-2"
                stroke="#86EFAC"
                strokeWidth="0.5"
              />
            </pattern>
          </defs>

          {/* Background */}
          <rect
            x="-10"
            y="-10"
            width="120%"
            height="120%"
            fill="#DCFCE7"
          />

          {/* Department outline */}
          <g filter="url(#deptOutlineShadow)">
            <path
              d={mapData.outline}
              fill="#BBF7D0"
              stroke="#22C55E"
              strokeWidth="2"
              strokeLinejoin="round"
            />
            <path
              d={mapData.outline}
              fill="url(#deptPattern)"
              opacity="0.3"
            />
          </g>

          {/* Settlement markers - render smaller ones first, then larger on top */}
          {sortedSettlements.reverse().map((settlement) => {
            const colors = SETTLEMENT_COLORS[settlement.type];
            const isHovered = hoveredSettlement?.name === settlement.name;
            const commune = communes.find(
              (c) => c.name.toLowerCase() === settlement.name.toLowerCase()
            );
            const isClickable = !!commune;

            return (
              <g key={settlement.name}>
                {/* Settlement marker */}
                <circle
                  cx={settlement.x}
                  cy={settlement.y}
                  r={isHovered ? colors.radius + 2 : colors.radius}
                  fill={colors.fill}
                  stroke={settlement.isPrefecture ? '#DC2626' : settlement.isSubprefecture ? '#9333EA' : colors.stroke}
                  strokeWidth={settlement.isPrefecture ? 3 : settlement.isSubprefecture ? 2.5 : 2}
                  className={`transition-all duration-150 ${isClickable ? 'cursor-pointer' : 'cursor-default'}`}
                  style={{
                    filter: isHovered ? 'url(#settlementGlow)' : 'none',
                  }}
                  onClick={() => isClickable && handleSettlementClick(settlement)}
                  onMouseMove={(e) => handleMouseMove(e, settlement)}
                  onMouseLeave={() => setHoveredSettlement(null)}
                  role={isClickable ? 'button' : undefined}
                  aria-label={settlement.name}
                  tabIndex={isClickable ? 0 : undefined}
                  onKeyDown={(e) => {
                    if (isClickable && (e.key === 'Enter' || e.key === ' ')) {
                      e.preventDefault();
                      handleSettlementClick(settlement);
                    }
                  }}
                />

                {/* Prefecture star indicator */}
                {settlement.isPrefecture && (
                  <text
                    x={settlement.x}
                    y={settlement.y + 4}
                    textAnchor="middle"
                    fontSize="10"
                    fontWeight="bold"
                    fill="white"
                    className="pointer-events-none select-none"
                  >
                    ★
                  </text>
                )}

                {/* Settlement label */}
                <text
                  x={settlement.x}
                  y={settlement.y - colors.radius - 4}
                  textAnchor="middle"
                  fontSize={settlement.type === 'city' ? '10' : settlement.type === 'town' ? '9' : '8'}
                  fontWeight={settlement.type === 'city' ? '600' : '500'}
                  fill="#1F2937"
                  className="pointer-events-none select-none"
                  style={{
                    textShadow: '1px 1px 2px white, -1px -1px 2px white, 1px -1px 2px white, -1px 1px 2px white',
                  }}
                >
                  {settlement.name}
                </text>
              </g>
            );
          })}
        </svg>

        {/* Hover Tooltip */}
        {hoveredSettlement && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-3 pointer-events-none z-20 min-w-[180px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 90), 280),
              top: Math.max(tooltipPos.y, 50),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <div className="flex items-center gap-2 mb-1">
              <span
                className="w-3 h-3 rounded-full"
                style={{
                  backgroundColor: SETTLEMENT_COLORS[hoveredSettlement.type].fill,
                }}
              />
              <h4 className="font-bold text-gray-900">{hoveredSettlement.name}</h4>
            </div>
            <p className="text-sm text-gray-500 capitalize mb-1">
              {hoveredSettlement.type}
              {hoveredSettlement.isPrefecture && ' (Prefecture)'}
              {hoveredSettlement.isSubprefecture && ' (Sub-prefecture)'}
            </p>
            {hoveredSettlement.population > 0 && (
              <p className="text-sm text-gray-600">
                Pop: {hoveredSettlement.population.toLocaleString()}
              </p>
            )}
            <div className="mt-2 pt-2 border-t border-gray-100">
              <span className="text-xs text-primary-600 font-medium">
                {communes.find((c) => c.name.toLowerCase() === hoveredSettlement.name.toLowerCase())
                  ? 'Click for details →'
                  : 'Details coming soon'}
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Legend */}
      <div className="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-600">
        <div className="flex items-center gap-1.5">
          <span
            className="w-3 h-3 rounded-full"
            style={{ backgroundColor: SETTLEMENT_COLORS.city.fill }}
          />
          <span>City</span>
        </div>
        <div className="flex items-center gap-1.5">
          <span
            className="w-3 h-3 rounded-full"
            style={{ backgroundColor: SETTLEMENT_COLORS.town.fill }}
          />
          <span>Town</span>
        </div>
        <div className="flex items-center gap-1.5">
          <span
            className="w-3 h-3 rounded-full"
            style={{ backgroundColor: SETTLEMENT_COLORS.village.fill }}
          />
          <span>Village</span>
        </div>
        <div className="flex items-center gap-1.5 ml-2 pl-2 border-l border-gray-300">
          <span className="w-3 h-3 rounded-full border-2 border-red-600 bg-blue-500" />
          <span>Prefecture</span>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="w-3 h-3 rounded-full border-2 border-purple-600 bg-blue-500" />
          <span>Sub-prefecture</span>
        </div>
      </div>
    </div>
  );
}
