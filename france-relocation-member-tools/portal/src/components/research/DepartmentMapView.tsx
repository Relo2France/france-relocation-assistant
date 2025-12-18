/**
 * DepartmentMapView Component
 *
 * Displays an interactive map of a department showing all communes
 * (towns and villages) using react-simple-maps with official GeoJSON data.
 * Includes zoom controls and clickable communes for report generation.
 */

import { useState, useMemo, useCallback } from 'react';
import { ComposableMap, Geographies, Geography, ZoomableGroup } from 'react-simple-maps';
import { FRANCE_DEPARTMENTS } from '@/config/research';
import type { FranceCommune } from '@/types';

// Base URL for commune GeoJSON files (per department)
const getCommmuneGeoUrl = (deptCode: string) =>
  `https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements/${deptCode}/communes.geojson`;

// Color palette for communes (alternating to distinguish neighbors)
const COMMUNE_COLORS = [
  '#93C5FD', '#86EFAC', '#FDE68A', '#C4B5FD', '#67E8F9',
  '#FDBA74', '#F9A8D4', '#A5B4FC', '#99F6E4', '#FCA5A5',
  '#D8B4FE', '#FCD34D', '#6EE7B7', '#A5F3FC', '#FBBF24',
];

// Hover color
const HOVER_COLOR = '#3B82F6';

// Department center coordinates and zoom scales
const DEPARTMENT_CENTERS: Record<string, { center: [number, number]; scale: number; minZoom: number; maxZoom: number }> = {
  // Île-de-France
  '75': { center: [2.35, 48.86], scale: 250000, minZoom: 1, maxZoom: 8 },   // Paris
  '77': { center: [2.97, 48.55], scale: 25000, minZoom: 1, maxZoom: 8 },    // Seine-et-Marne
  '78': { center: [1.85, 48.80], scale: 35000, minZoom: 1, maxZoom: 8 },    // Yvelines
  '91': { center: [2.25, 48.53], scale: 40000, minZoom: 1, maxZoom: 8 },    // Essonne
  '92': { center: [2.22, 48.86], scale: 70000, minZoom: 1, maxZoom: 8 },    // Hauts-de-Seine
  '93': { center: [2.45, 48.92], scale: 70000, minZoom: 1, maxZoom: 8 },    // Seine-Saint-Denis
  '94': { center: [2.47, 48.78], scale: 60000, minZoom: 1, maxZoom: 8 },    // Val-de-Marne
  '95': { center: [2.15, 49.05], scale: 40000, minZoom: 1, maxZoom: 8 },    // Val-d'Oise
  // Bretagne
  '22': { center: [-2.98, 48.45], scale: 18000, minZoom: 1, maxZoom: 8 },   // Côtes-d'Armor
  '29': { center: [-4.15, 48.35], scale: 16000, minZoom: 1, maxZoom: 8 },   // Finistère
  '35': { center: [-1.68, 48.10], scale: 18000, minZoom: 1, maxZoom: 8 },   // Ille-et-Vilaine
  '56': { center: [-2.85, 47.75], scale: 18000, minZoom: 1, maxZoom: 8 },   // Morbihan
  // Provence-Alpes-Côte d'Azur
  '04': { center: [6.25, 44.05], scale: 14000, minZoom: 1, maxZoom: 8 },    // Alpes-de-Haute-Provence
  '05': { center: [6.35, 44.65], scale: 18000, minZoom: 1, maxZoom: 8 },    // Hautes-Alpes
  '06': { center: [7.20, 43.85], scale: 20000, minZoom: 1, maxZoom: 8 },    // Alpes-Maritimes
  '13': { center: [5.05, 43.55], scale: 20000, minZoom: 1, maxZoom: 8 },    // Bouches-du-Rhône
  '83': { center: [6.25, 43.45], scale: 18000, minZoom: 1, maxZoom: 8 },    // Var
  '84': { center: [5.15, 44.00], scale: 22000, minZoom: 1, maxZoom: 8 },    // Vaucluse
  // Auvergne-Rhône-Alpes
  '01': { center: [5.35, 46.10], scale: 18000, minZoom: 1, maxZoom: 8 },    // Ain
  '03': { center: [3.35, 46.35], scale: 16000, minZoom: 1, maxZoom: 8 },    // Allier
  '07': { center: [4.45, 44.75], scale: 18000, minZoom: 1, maxZoom: 8 },    // Ardèche
  '15': { center: [2.70, 45.05], scale: 18000, minZoom: 1, maxZoom: 8 },    // Cantal
  '26': { center: [5.15, 44.65], scale: 16000, minZoom: 1, maxZoom: 8 },    // Drôme
  '38': { center: [5.75, 45.25], scale: 14000, minZoom: 1, maxZoom: 8 },    // Isère
  '42': { center: [4.25, 45.75], scale: 20000, minZoom: 1, maxZoom: 8 },    // Loire
  '43': { center: [3.85, 45.05], scale: 20000, minZoom: 1, maxZoom: 8 },    // Haute-Loire
  '63': { center: [3.10, 45.75], scale: 14000, minZoom: 1, maxZoom: 8 },    // Puy-de-Dôme
  '69': { center: [4.65, 45.85], scale: 25000, minZoom: 1, maxZoom: 8 },    // Rhône
  '73': { center: [6.45, 45.50], scale: 16000, minZoom: 1, maxZoom: 8 },    // Savoie
  '74': { center: [6.45, 46.00], scale: 18000, minZoom: 1, maxZoom: 8 },    // Haute-Savoie
  // Occitanie
  '09': { center: [1.55, 42.95], scale: 20000, minZoom: 1, maxZoom: 8 },    // Ariège
  '11': { center: [2.35, 43.15], scale: 18000, minZoom: 1, maxZoom: 8 },    // Aude
  '12': { center: [2.65, 44.25], scale: 14000, minZoom: 1, maxZoom: 8 },    // Aveyron
  '30': { center: [4.20, 44.00], scale: 18000, minZoom: 1, maxZoom: 8 },    // Gard
  '31': { center: [1.45, 43.35], scale: 16000, minZoom: 1, maxZoom: 8 },    // Haute-Garonne
  '32': { center: [0.55, 43.70], scale: 18000, minZoom: 1, maxZoom: 8 },    // Gers
  '34': { center: [3.55, 43.60], scale: 18000, minZoom: 1, maxZoom: 8 },    // Hérault
  '46': { center: [1.65, 44.55], scale: 20000, minZoom: 1, maxZoom: 8 },    // Lot
  '48': { center: [3.50, 44.50], scale: 20000, minZoom: 1, maxZoom: 8 },    // Lozère
  '65': { center: [0.15, 43.05], scale: 20000, minZoom: 1, maxZoom: 8 },    // Hautes-Pyrénées
  '66': { center: [2.55, 42.60], scale: 20000, minZoom: 1, maxZoom: 8 },    // Pyrénées-Orientales
  '81': { center: [2.15, 43.85], scale: 20000, minZoom: 1, maxZoom: 8 },    // Tarn
  '82': { center: [1.35, 44.05], scale: 25000, minZoom: 1, maxZoom: 8 },    // Tarn-et-Garonne
  // Nouvelle-Aquitaine
  '16': { center: [0.20, 45.70], scale: 20000, minZoom: 1, maxZoom: 8 },    // Charente
  '17': { center: [-0.90, 45.90], scale: 16000, minZoom: 1, maxZoom: 8 },   // Charente-Maritime
  '19': { center: [1.85, 45.30], scale: 20000, minZoom: 1, maxZoom: 8 },    // Corrèze
  '23': { center: [2.05, 46.15], scale: 20000, minZoom: 1, maxZoom: 8 },    // Creuse
  '24': { center: [0.85, 45.10], scale: 14000, minZoom: 1, maxZoom: 8 },    // Dordogne
  '33': { center: [-0.60, 44.85], scale: 14000, minZoom: 1, maxZoom: 8 },   // Gironde
  '40': { center: [-0.85, 43.90], scale: 14000, minZoom: 1, maxZoom: 8 },   // Landes
  '47': { center: [0.50, 44.35], scale: 20000, minZoom: 1, maxZoom: 8 },    // Lot-et-Garonne
  '64': { center: [-0.85, 43.25], scale: 16000, minZoom: 1, maxZoom: 8 },   // Pyrénées-Atlantiques
  '79': { center: [-0.40, 46.55], scale: 20000, minZoom: 1, maxZoom: 8 },   // Deux-Sèvres
  '86': { center: [0.55, 46.55], scale: 18000, minZoom: 1, maxZoom: 8 },    // Vienne
  '87': { center: [1.30, 45.90], scale: 20000, minZoom: 1, maxZoom: 8 },    // Haute-Vienne
  // Grand Est
  '08': { center: [4.65, 49.75], scale: 20000, minZoom: 1, maxZoom: 8 },    // Ardennes
  '10': { center: [4.10, 48.30], scale: 20000, minZoom: 1, maxZoom: 8 },    // Aube
  '51': { center: [4.20, 49.00], scale: 14000, minZoom: 1, maxZoom: 8 },    // Marne
  '52': { center: [5.20, 48.10], scale: 18000, minZoom: 1, maxZoom: 8 },    // Haute-Marne
  '54': { center: [6.20, 48.75], scale: 20000, minZoom: 1, maxZoom: 8 },    // Meurthe-et-Moselle
  '55': { center: [5.40, 49.00], scale: 18000, minZoom: 1, maxZoom: 8 },    // Meuse
  '57': { center: [6.65, 49.10], scale: 18000, minZoom: 1, maxZoom: 8 },    // Moselle
  '67': { center: [7.55, 48.65], scale: 20000, minZoom: 1, maxZoom: 8 },    // Bas-Rhin
  '68': { center: [7.25, 47.85], scale: 22000, minZoom: 1, maxZoom: 8 },    // Haut-Rhin
  '88': { center: [6.40, 48.20], scale: 20000, minZoom: 1, maxZoom: 8 },    // Vosges
  // Bourgogne-Franche-Comté
  '21': { center: [4.85, 47.35], scale: 14000, minZoom: 1, maxZoom: 8 },    // Côte-d'Or
  '25': { center: [6.35, 47.15], scale: 20000, minZoom: 1, maxZoom: 8 },    // Doubs
  '39': { center: [5.70, 46.75], scale: 20000, minZoom: 1, maxZoom: 8 },    // Jura
  '58': { center: [3.50, 47.10], scale: 16000, minZoom: 1, maxZoom: 8 },    // Nièvre
  '70': { center: [6.15, 47.65], scale: 20000, minZoom: 1, maxZoom: 8 },    // Haute-Saône
  '71': { center: [4.55, 46.65], scale: 14000, minZoom: 1, maxZoom: 8 },    // Saône-et-Loire
  '89': { center: [3.55, 47.80], scale: 16000, minZoom: 1, maxZoom: 8 },    // Yonne
  '90': { center: [6.90, 47.65], scale: 50000, minZoom: 1, maxZoom: 8 },    // Territoire de Belfort
  // Centre-Val de Loire
  '18': { center: [2.45, 46.95], scale: 16000, minZoom: 1, maxZoom: 8 },    // Cher
  '28': { center: [1.45, 48.30], scale: 18000, minZoom: 1, maxZoom: 8 },    // Eure-et-Loir
  '36': { center: [1.70, 46.75], scale: 18000, minZoom: 1, maxZoom: 8 },    // Indre
  '37': { center: [0.75, 47.25], scale: 18000, minZoom: 1, maxZoom: 8 },    // Indre-et-Loire
  '41': { center: [1.35, 47.55], scale: 18000, minZoom: 1, maxZoom: 8 },    // Loir-et-Cher
  '45': { center: [2.35, 47.90], scale: 16000, minZoom: 1, maxZoom: 8 },    // Loiret
  // Normandie
  '14': { center: [-0.40, 49.10], scale: 20000, minZoom: 1, maxZoom: 8 },   // Calvados
  '27': { center: [0.95, 49.10], scale: 18000, minZoom: 1, maxZoom: 8 },    // Eure
  '50': { center: [-1.30, 49.10], scale: 18000, minZoom: 1, maxZoom: 8 },   // Manche
  '61': { center: [0.15, 48.55], scale: 18000, minZoom: 1, maxZoom: 8 },    // Orne
  '76': { center: [0.95, 49.65], scale: 18000, minZoom: 1, maxZoom: 8 },    // Seine-Maritime
  // Hauts-de-France
  '02': { center: [3.65, 49.55], scale: 16000, minZoom: 1, maxZoom: 8 },    // Aisne
  '59': { center: [3.25, 50.35], scale: 16000, minZoom: 1, maxZoom: 8 },    // Nord
  '60': { center: [2.45, 49.40], scale: 20000, minZoom: 1, maxZoom: 8 },    // Oise
  '62': { center: [2.35, 50.50], scale: 16000, minZoom: 1, maxZoom: 8 },    // Pas-de-Calais
  '80': { center: [2.35, 49.90], scale: 18000, minZoom: 1, maxZoom: 8 },    // Somme
  // Pays de la Loire
  '44': { center: [-1.70, 47.25], scale: 16000, minZoom: 1, maxZoom: 8 },   // Loire-Atlantique
  '49': { center: [-0.55, 47.35], scale: 16000, minZoom: 1, maxZoom: 8 },   // Maine-et-Loire
  '53': { center: [-0.80, 48.10], scale: 20000, minZoom: 1, maxZoom: 8 },   // Mayenne
  '72': { center: [0.25, 47.95], scale: 18000, minZoom: 1, maxZoom: 8 },    // Sarthe
  '85': { center: [-1.35, 46.70], scale: 18000, minZoom: 1, maxZoom: 8 },   // Vendée
  // Corse
  '2A': { center: [8.95, 41.90], scale: 22000, minZoom: 1, maxZoom: 8 },    // Corse-du-Sud
  '2B': { center: [9.25, 42.45], scale: 20000, minZoom: 1, maxZoom: 8 },    // Haute-Corse
};

// Default config for any department not in the list
const DEFAULT_DEPT_CONFIG = { center: [2.5, 46.5] as [number, number], scale: 18000, minZoom: 1, maxZoom: 8 };

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
  const [hoveredCommune, setHoveredCommune] = useState<string | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);

  // Get department's center and scale
  const deptConfig = DEPARTMENT_CENTERS[departmentCode] || DEFAULT_DEPT_CONFIG;

  // GeoJSON URL for this department's communes
  const communeGeoUrl = useMemo(() => getCommmuneGeoUrl(departmentCode), [departmentCode]);

  // Get color for a commune (based on index for visual distinction)
  const getCommuneColor = useCallback((index: number) => {
    return COMMUNE_COLORS[index % COMMUNE_COLORS.length];
  }, []);

  // Find matching commune from our data
  const findCommune = useCallback((communeName: string) => {
    return communes.find(
      (c) => c.name.toLowerCase() === communeName.toLowerCase()
    );
  }, [communes]);

  // Get region info from department data
  const departmentInfo = useMemo(() => {
    return FRANCE_DEPARTMENTS.find(d => d.code === departmentCode);
  }, [departmentCode]);

  // Handle commune click
  const handleCommuneClick = useCallback((communeName: string, communeCode: string) => {
    // First try to find in our existing communes list
    const existingCommune = findCommune(communeName);

    if (existingCommune) {
      onCommuneSelect(existingCommune);
      return;
    }

    // If not found, create a basic commune object from the GeoJSON data
    const newCommune: FranceCommune = {
      code: communeCode,
      name: communeName,
      department_code: departmentCode,
      department_name: departmentName,
      region_code: departmentInfo?.region_code || '',
      region_name: departmentInfo?.region_name || '',
      population: 0,
      postal_codes: [],
      type: 'town', // Default to town for unknown communes
    };

    onCommuneSelect(newCommune);
  }, [findCommune, onCommuneSelect, departmentCode, departmentName, departmentInfo]);

  // Handle mouse enter on commune
  const handleMouseEnter = useCallback((
    communeName: string,
    event: React.MouseEvent
  ) => {
    setHoveredCommune(communeName);
    const rect = event.currentTarget.closest('.map-container')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
      });
    }
  }, []);

  // Handle mouse move for tooltip positioning
  const handleMouseMove = useCallback((event: React.MouseEvent) => {
    const rect = event.currentTarget.closest('.map-container')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
      });
    }
  }, []);

  // Zoom controls
  const handleZoomIn = useCallback(() => {
    setZoom(prev => Math.min(prev * 1.5, deptConfig.maxZoom));
  }, [deptConfig.maxZoom]);

  const handleZoomOut = useCallback(() => {
    setZoom(prev => Math.max(prev / 1.5, deptConfig.minZoom));
  }, [deptConfig.minZoom]);

  const handleResetZoom = useCallback(() => {
    setZoom(1);
  }, []);

  // Handle move end to track zoom level
  const handleMoveEnd = useCallback((pos: { coordinates: [number, number]; zoom: number }) => {
    setZoom(pos.zoom);
  }, []);

  // Get commune data for tooltip
  const hoveredCommuneData = useMemo(() => {
    if (!hoveredCommune) return null;
    return findCommune(hoveredCommune);
  }, [hoveredCommune, findCommune]);

  // Calculate label font size based on zoom
  const labelFontSize = useMemo(() => {
    if (zoom >= 4) return 8;
    if (zoom >= 2.5) return 6;
    if (zoom >= 1.5) return 5;
    return 0; // Don't show labels at base zoom
  }, [zoom]);

  return (
    <div className="relative mb-6">
      <h3 className="text-sm font-medium text-gray-700 mb-3">
        Communes of {departmentName}
      </h3>

      <div className="map-container relative bg-gradient-to-br from-emerald-50 to-green-100 rounded-xl overflow-hidden shadow-inner border border-green-200">
        {/* Zoom Controls */}
        <div className="absolute top-3 right-3 z-10 flex flex-col gap-1">
          <button
            onClick={handleZoomIn}
            className="w-8 h-8 bg-white rounded-lg shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors text-gray-700 font-bold"
            aria-label="Zoom in"
          >
            +
          </button>
          <button
            onClick={handleZoomOut}
            className="w-8 h-8 bg-white rounded-lg shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors text-gray-700 font-bold"
            aria-label="Zoom out"
          >
            −
          </button>
          <button
            onClick={handleResetZoom}
            className="w-8 h-8 bg-white rounded-lg shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors text-gray-500 text-xs"
            aria-label="Reset zoom"
          >
            ↺
          </button>
        </div>

        {/* Zoom Level Indicator */}
        <div className="absolute bottom-3 right-3 z-10 bg-white/80 backdrop-blur-sm px-2 py-1 rounded text-xs text-gray-600">
          {zoom.toFixed(1)}x
        </div>

        {/* Instructions */}
        <div className="absolute top-3 left-3 z-10 bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg text-xs text-gray-600 max-w-[200px]">
          <p className="font-medium text-gray-700 mb-1">Navigate the map:</p>
          <ul className="space-y-0.5">
            <li>• Zoom in to see commune names</li>
            <li>• Click any commune for details</li>
            <li>• Drag to pan around</li>
          </ul>
        </div>

        <ComposableMap
          projection="geoMercator"
          projectionConfig={{
            center: deptConfig.center,
            scale: deptConfig.scale,
          }}
          style={{ width: '100%', height: 'auto', minHeight: '450px', maxHeight: '550px' }}
        >
          <ZoomableGroup
            center={deptConfig.center}
            zoom={zoom}
            minZoom={deptConfig.minZoom}
            maxZoom={deptConfig.maxZoom}
            onMoveEnd={handleMoveEnd}
          >
            <Geographies geography={communeGeoUrl}>
              {({ geographies }) =>
                geographies.map((geo, index) => {
                  const communeName = geo.properties.nom;
                  const communeCode = geo.properties.code;
                  const isHovered = hoveredCommune === communeName;
                  const fillColor = isHovered ? HOVER_COLOR : getCommuneColor(index);

                  return (
                    <Geography
                      key={geo.rsmKey}
                      geography={geo}
                      fill={fillColor}
                      stroke="#FFFFFF"
                      strokeWidth={0.5 / zoom}
                      style={{
                        default: { outline: 'none' },
                        hover: { outline: 'none', cursor: 'pointer' },
                        pressed: { outline: 'none' },
                      }}
                      onClick={() => handleCommuneClick(communeName, communeCode)}
                      onMouseEnter={(e) => handleMouseEnter(communeName, e)}
                      onMouseLeave={() => setHoveredCommune(null)}
                      onMouseMove={handleMouseMove}
                    />
                  );
                })
              }
            </Geographies>

            {/* Commune labels (only visible when zoomed in) */}
            {labelFontSize > 0 && (
              <Geographies geography={communeGeoUrl}>
                {({ geographies }) =>
                  geographies.map((geo) => {
                    const communeName = geo.properties.nom;
                    // Calculate centroid of the commune for label placement
                    // We'll use a simple approximation - the center of the bounding box
                    const bounds = geo.geometry.coordinates;
                    let centerLon = 0, centerLat = 0;

                    try {
                      // Handle both Polygon and MultiPolygon
                      const coords = geo.geometry.type === 'MultiPolygon'
                        ? bounds[0][0]
                        : bounds[0];

                      if (coords && coords.length > 0) {
                        const lons = coords.map((c: number[]) => c[0]);
                        const lats = coords.map((c: number[]) => c[1]);
                        centerLon = (Math.min(...lons) + Math.max(...lons)) / 2;
                        centerLat = (Math.min(...lats) + Math.max(...lats)) / 2;
                      }
                    } catch {
                      return null;
                    }

                    return (
                      <text
                        key={`label-${geo.rsmKey}`}
                        x={centerLon}
                        y={centerLat}
                        textAnchor="middle"
                        fontSize={labelFontSize / zoom}
                        fontWeight="500"
                        fill="#1F2937"
                        className="pointer-events-none select-none"
                        style={{
                          textShadow: '1px 1px 1px white, -1px -1px 1px white',
                          transform: `translate(${centerLon}px, ${centerLat}px)`,
                        }}
                      >
                        {communeName}
                      </text>
                    );
                  })
                }
              </Geographies>
            )}
          </ZoomableGroup>
        </ComposableMap>

        {/* Hover Tooltip */}
        {hoveredCommune && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-3 pointer-events-none z-20 min-w-[180px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 90), 320),
              top: Math.max(tooltipPos.y - 10, 60),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <h4 className="font-bold text-gray-900 mb-1">{hoveredCommune}</h4>
            {hoveredCommuneData ? (
              <>
                {hoveredCommuneData.population > 0 && (
                  <p className="text-sm text-gray-600">
                    Population: {hoveredCommuneData.population.toLocaleString()}
                  </p>
                )}
                {hoveredCommuneData.postal_codes.length > 0 && (
                  <p className="text-sm text-gray-500">
                    Postal: {hoveredCommuneData.postal_codes[0]}
                  </p>
                )}
              </>
            ) : (
              <p className="text-sm text-gray-500">
                {departmentName}
              </p>
            )}
            <div className="mt-2 pt-2 border-t border-gray-100">
              <span className="text-xs text-primary-600 font-medium">
                Click to generate report →
              </span>
            </div>
          </div>
        )}
      </div>

      {/* Legend and Info */}
      <div className="mt-3 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-600">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-sm" style={{ backgroundColor: '#3B82F6' }} />
            <span>Hovered</span>
          </div>
          <div className="flex items-center gap-1.5">
            <div className="flex gap-0.5">
              {COMMUNE_COLORS.slice(0, 4).map((color, i) => (
                <span key={i} className="w-2 h-3 rounded-sm" style={{ backgroundColor: color }} />
              ))}
            </div>
            <span>Communes</span>
          </div>
        </div>
        <div className="text-gray-500">
          Click any commune to view details and generate a relocation report
        </div>
      </div>
    </div>
  );
}
