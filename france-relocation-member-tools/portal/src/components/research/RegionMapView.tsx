/**
 * RegionMapView Component
 *
 * Displays an interactive map of a region showing its departments
 * using react-simple-maps with official GeoJSON data.
 */

import { useState, useMemo } from 'react';
import { ComposableMap, Geographies, Geography, ZoomableGroup } from 'react-simple-maps';
import type { FranceDepartment } from '@/types';

// GeoJSON URL for French departments (from gregoiredavid/france-geojson)
const FRANCE_DEPARTMENTS_GEO_URL =
  'https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements.geojson';

// Color palette for departments
const DEPARTMENT_COLORS = [
  { fill: '#93C5FD', hover: '#60A5FA' }, // Blue
  { fill: '#86EFAC', hover: '#4ADE80' }, // Green
  { fill: '#FCA5A5', hover: '#F87171' }, // Red
  { fill: '#FCD34D', hover: '#FBBF24' }, // Yellow
  { fill: '#C4B5FD', hover: '#A78BFA' }, // Purple
  { fill: '#67E8F9', hover: '#22D3EE' }, // Cyan
  { fill: '#FDBA74', hover: '#FB923C' }, // Orange
  { fill: '#F9A8D4', hover: '#F472B6' }, // Pink
  { fill: '#A5B4FC', hover: '#818CF8' }, // Indigo
  { fill: '#99F6E4', hover: '#5EEAD4' }, // Teal
  { fill: '#FDE68A', hover: '#FCD34D' }, // Amber
  { fill: '#D8B4FE', hover: '#C084FC' }, // Violet
];

// Region center coordinates and zoom scales
const REGION_CENTERS: Record<string, { center: [number, number]; scale: number }> = {
  '11': { center: [2.5, 48.7], scale: 18000 },      // Île-de-France
  '24': { center: [1.7, 47.3], scale: 8000 },       // Centre-Val de Loire
  '27': { center: [5.0, 47.0], scale: 7000 },       // Bourgogne-Franche-Comté
  '28': { center: [-0.3, 49.0], scale: 9000 },      // Normandie
  '32': { center: [2.8, 49.9], scale: 9000 },       // Hauts-de-France
  '44': { center: [5.5, 48.7], scale: 6000 },       // Grand Est
  '52': { center: [-0.8, 47.5], scale: 9000 },      // Pays de la Loire
  '53': { center: [-3.0, 48.2], scale: 10000 },     // Bretagne
  '75': { center: [0.0, 45.5], scale: 5500 },       // Nouvelle-Aquitaine
  '76': { center: [2.0, 43.8], scale: 5500 },       // Occitanie
  '84': { center: [4.5, 45.5], scale: 5500 },       // Auvergne-Rhône-Alpes
  '93': { center: [6.0, 44.0], scale: 8000 },       // Provence-Alpes-Côte d'Azur
  '94': { center: [9.1, 42.0], scale: 12000 },      // Corse
};

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
  const [tooltipContent, setTooltipContent] = useState<FranceDepartment | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  // Get region's center and scale
  const regionConfig = REGION_CENTERS[regionCode] || { center: [2.5, 46.5], scale: 5000 };

  // Get department codes for this region
  const regionDeptCodes = useMemo(() => {
    return departments.map(d => d.code);
  }, [departments]);

  // Map department code to color
  const getDeptColor = (code: string) => {
    const index = regionDeptCodes.indexOf(code);
    return DEPARTMENT_COLORS[index % DEPARTMENT_COLORS.length];
  };

  const handleDepartmentClick = (deptCode: string) => {
    const dept = departments.find((d) => d.code === deptCode);
    if (dept) {
      onDepartmentSelect(dept);
    }
  };

  const handleMouseEnter = (
    geo: { properties: { code: string } },
    event: React.MouseEvent
  ) => {
    const code = geo.properties.code;
    setHoveredDept(code);
    const dept = departments.find((d) => d.code === code);
    if (dept) {
      setTooltipContent(dept);
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
    setHoveredDept(null);
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
    <div className="relative mb-6">
      <h3 className="text-sm font-medium text-gray-700 mb-3">
        Departments of {regionName}
      </h3>

      <div className="map-container relative bg-gradient-to-br from-sky-50 to-blue-100 rounded-xl overflow-hidden shadow-inner border border-blue-200">
        <ComposableMap
          projection="geoMercator"
          projectionConfig={{
            center: regionConfig.center,
            scale: regionConfig.scale,
          }}
          style={{ width: '100%', height: 'auto', minHeight: '350px', maxHeight: '450px' }}
        >
          <ZoomableGroup center={regionConfig.center} zoom={1}>
            <Geographies geography={FRANCE_DEPARTMENTS_GEO_URL}>
              {({ geographies }) =>
                geographies
                  .filter((geo) => regionDeptCodes.includes(geo.properties.code))
                  .map((geo) => {
                    const deptCode = geo.properties.code;
                    const colors = getDeptColor(deptCode);
                    const isHovered = hoveredDept === deptCode;

                    return (
                      <Geography
                        key={geo.rsmKey}
                        geography={geo}
                        fill={isHovered ? colors.hover : colors.fill}
                        stroke="#FFFFFF"
                        strokeWidth={1.5}
                        style={{
                          default: { outline: 'none' },
                          hover: { outline: 'none', cursor: 'pointer' },
                          pressed: { outline: 'none' },
                        }}
                        onClick={() => handleDepartmentClick(deptCode)}
                        onMouseEnter={(event) => handleMouseEnter(geo, event)}
                        onMouseLeave={handleMouseLeave}
                        onMouseMove={handleMouseMove}
                      />
                    );
                  })
              }
            </Geographies>
          </ZoomableGroup>
        </ComposableMap>

        {/* Hover Tooltip */}
        {tooltipContent && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-3 pointer-events-none z-20 min-w-[220px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 110), 320),
              top: Math.max(tooltipPos.y - 10, 60),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <div className="flex items-center gap-2 mb-1">
              <span className="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">
                {tooltipContent.code}
              </span>
              <h4 className="font-bold text-gray-900">{tooltipContent.name}</h4>
            </div>
            <p className="text-sm text-gray-500 mb-2">
              Prefecture: {tooltipContent.prefecture}
            </p>
            <div className="grid grid-cols-2 gap-2 text-sm">
              <div>
                <span className="text-gray-400 text-xs block">Population</span>
                <span className="font-semibold text-gray-900">
                  {tooltipContent.population.toLocaleString()}
                </span>
              </div>
              <div>
                <span className="text-gray-400 text-xs block">Area</span>
                <span className="font-semibold text-gray-900">
                  {tooltipContent.area_km2.toLocaleString()} km²
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
        {departments.map((dept) => {
          const colors = getDeptColor(dept.code);
          const isHovered = hoveredDept === dept.code;

          return (
            <button
              key={dept.code}
              onClick={() => onDepartmentSelect(dept)}
              onMouseEnter={() => setHoveredDept(dept.code)}
              onMouseLeave={() => setHoveredDept(null)}
              className={`flex items-center gap-1.5 px-2 py-1 rounded text-xs transition-all ${
                isHovered ? 'bg-gray-100 shadow-sm' : 'hover:bg-gray-50'
              }`}
            >
              <span
                className="w-3 h-3 rounded-sm border border-white shadow-sm"
                style={{
                  backgroundColor: colors.fill,
                }}
              />
              <span className="font-mono text-gray-500">{dept.code}</span>
              <span className={`truncate ${isHovered ? 'font-medium' : ''}`}>
                {dept.name}
              </span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
