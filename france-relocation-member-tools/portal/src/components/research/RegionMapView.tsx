/**
 * RegionMapView Component
 *
 * Displays an SVG map of a region showing its departments.
 * Departments are clickable to drill down further.
 */

import { useState } from 'react';
import { getRegionMap, DEPARTMENT_MAP_COLORS, type DepartmentMapData } from '@/config/regionMaps';
import type { FranceDepartment } from '@/types';

interface RegionMapViewProps {
  regionCode: string;
  regionName: string;
  departments: FranceDepartment[];
  onDepartmentSelect: (department: FranceDepartment) => void;
}

export default function RegionMapView({
  regionCode,
  regionName,
  departments,
  onDepartmentSelect,
}: RegionMapViewProps) {
  const [hoveredDept, setHoveredDept] = useState<string | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  const mapData = getRegionMap(regionCode);

  if (!mapData) {
    return null;
  }

  const handleDepartmentClick = (deptCode: string) => {
    const dept = departments.find((d) => d.code === deptCode);
    if (dept) {
      onDepartmentSelect(dept);
    }
  };

  const handleMouseMove = (e: React.MouseEvent, deptCode: string) => {
    const rect = e.currentTarget.closest('svg')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: e.clientX - rect.left,
        y: e.clientY - rect.top - 10,
      });
    }
    setHoveredDept(deptCode);
  };

  const hoveredDeptData = hoveredDept
    ? departments.find((d) => d.code === hoveredDept)
    : null;

  const hoveredMapData = hoveredDept
    ? mapData.departments.find((d) => d.code === hoveredDept)
    : null;

  return (
    <div className="relative mb-6">
      <h3 className="text-sm font-medium text-gray-700 mb-3">
        Map of {regionName}
      </h3>

      <div className="relative bg-gradient-to-br from-sky-50 to-blue-100 rounded-xl overflow-hidden shadow-inner border border-blue-200">
        <svg
          viewBox={mapData.viewBox}
          className="w-full h-auto"
          style={{ maxHeight: '400px', minHeight: '280px' }}
          aria-label={`Map of ${regionName} showing departments`}
          role="img"
        >
          {/* Definitions */}
          <defs>
            <filter id="deptShadow" x="-10%" y="-10%" width="120%" height="120%">
              <feDropShadow dx="1" dy="1" stdDeviation="2" floodOpacity="0.15" />
            </filter>
            <filter id="deptGlow">
              <feGaussianBlur stdDeviation="2" result="coloredBlur" />
              <feMerge>
                <feMergeNode in="coloredBlur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>

          {/* Ocean/background */}
          <rect
            x="-10"
            y="-10"
            width="120%"
            height="120%"
            fill="#DBEAFE"
          />

          {/* Department paths */}
          <g filter="url(#deptShadow)">
            {mapData.departments.map((deptMap: DepartmentMapData, index: number) => {
              const colors = DEPARTMENT_MAP_COLORS[index % DEPARTMENT_MAP_COLORS.length];
              const isHovered = hoveredDept === deptMap.code;
              const deptData = departments.find((d) => d.code === deptMap.code);

              return (
                <g key={deptMap.code}>
                  <path
                    d={deptMap.path}
                    fill={isHovered ? colors.hover : colors.fill}
                    stroke={colors.border}
                    strokeWidth={isHovered ? 2.5 : 1.5}
                    strokeLinejoin="round"
                    className="cursor-pointer transition-all duration-150"
                    style={{
                      filter: isHovered ? 'url(#deptGlow)' : 'none',
                    }}
                    onClick={() => handleDepartmentClick(deptMap.code)}
                    onMouseMove={(e) => handleMouseMove(e, deptMap.code)}
                    onMouseLeave={() => setHoveredDept(null)}
                    role="button"
                    aria-label={deptData?.name || deptMap.name}
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleDepartmentClick(deptMap.code);
                      }
                    }}
                  />

                  {/* Department label */}
                  <text
                    x={deptMap.labelPosition.x}
                    y={deptMap.labelPosition.y}
                    textAnchor="middle"
                    fontSize="10"
                    fontWeight="600"
                    fill="#1F2937"
                    className="pointer-events-none select-none"
                    style={{
                      textShadow: '1px 1px 2px white, -1px -1px 2px white',
                    }}
                  >
                    {deptMap.code}
                  </text>
                </g>
              );
            })}
          </g>

          {/* City markers */}
          {mapData.departments.map((deptMap: DepartmentMapData) =>
            deptMap.cities?.map((city) => (
              <g key={`${deptMap.code}-${city.name}`} className="pointer-events-none">
                <circle
                  cx={city.x}
                  cy={city.y}
                  r={city.isCapital ? 4 : 3}
                  fill={city.isCapital ? '#DC2626' : '#1F2937'}
                  stroke="white"
                  strokeWidth="1.5"
                />
                <text
                  x={city.x}
                  y={city.y - 6}
                  textAnchor="middle"
                  fontSize={city.isCapital ? '9' : '8'}
                  fontWeight={city.isCapital ? '600' : '500'}
                  fill="#374151"
                  className="select-none"
                  style={{
                    textShadow: '1px 1px 1px white, -1px -1px 1px white',
                  }}
                >
                  {city.name}
                </text>
              </g>
            ))
          )}
        </svg>

        {/* Hover Tooltip */}
        {hoveredDeptData && hoveredMapData && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-3 pointer-events-none z-20 min-w-[200px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 100), 300),
              top: Math.max(tooltipPos.y, 40),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <div className="flex items-center gap-2 mb-1">
              <span className="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">
                {hoveredDeptData.code}
              </span>
              <h4 className="font-bold text-gray-900">{hoveredDeptData.name}</h4>
            </div>
            <p className="text-sm text-gray-500 mb-2">
              Prefecture: {hoveredDeptData.prefecture}
            </p>
            <div className="grid grid-cols-2 gap-2 text-sm">
              <div>
                <span className="text-gray-400 text-xs block">Population</span>
                <span className="font-semibold text-gray-900">
                  {hoveredDeptData.population.toLocaleString()}
                </span>
              </div>
              <div>
                <span className="text-gray-400 text-xs block">Area</span>
                <span className="font-semibold text-gray-900">
                  {hoveredDeptData.area_km2.toLocaleString()} km²
                </span>
              </div>
            </div>
            <div className="mt-2 pt-2 border-t border-gray-100">
              <span className="text-xs text-primary-600 font-medium">
                Click to explore cities →
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Map Legend */}
      <div className="mt-3 flex flex-wrap gap-2">
        {mapData.departments.map((deptMap: DepartmentMapData, index: number) => {
          const colors = DEPARTMENT_MAP_COLORS[index % DEPARTMENT_MAP_COLORS.length];
          const deptData = departments.find((d) => d.code === deptMap.code);
          const isHovered = hoveredDept === deptMap.code;

          return (
            <button
              key={deptMap.code}
              onClick={() => handleDepartmentClick(deptMap.code)}
              onMouseEnter={() => setHoveredDept(deptMap.code)}
              onMouseLeave={() => setHoveredDept(null)}
              className={`flex items-center gap-1.5 px-2 py-1 rounded text-xs transition-all ${
                isHovered ? 'bg-gray-100 shadow-sm' : 'hover:bg-gray-50'
              }`}
            >
              <span
                className="w-3 h-3 rounded-sm border"
                style={{
                  backgroundColor: colors.fill,
                  borderColor: colors.border,
                }}
              />
              <span className="font-mono text-gray-500">{deptMap.code}</span>
              <span className={`truncate ${isHovered ? 'font-medium' : ''}`}>
                {deptData?.name || deptMap.name}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
