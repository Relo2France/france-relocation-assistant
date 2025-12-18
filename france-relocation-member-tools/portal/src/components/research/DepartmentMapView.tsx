/**
 * DepartmentMapView Component
 *
 * Displays an interactive map of a department showing its outline
 * and major cities using react-simple-maps with official GeoJSON data.
 */

import { useState, useMemo } from 'react';
import { ComposableMap, Geographies, Geography, Marker, ZoomableGroup } from 'react-simple-maps';
import type { FranceCommune } from '@/types';

// GeoJSON URL for French departments (from gregoiredavid/france-geojson)
const FRANCE_DEPARTMENTS_GEO_URL =
  'https://raw.githubusercontent.com/gregoiredavid/france-geojson/master/departements.geojson';

// Settlement type colors
const SETTLEMENT_COLORS = {
  city: { fill: '#3B82F6', stroke: '#1D4ED8', radius: 7 },
  town: { fill: '#22C55E', stroke: '#16A34A', radius: 5 },
  village: { fill: '#F59E0B', stroke: '#D97706', radius: 4 },
};

// Department center coordinates and zoom scales
// Using approximate centers for each department
const DEPARTMENT_CENTERS: Record<string, { center: [number, number]; scale: number }> = {
  // Île-de-France
  '75': { center: [2.35, 48.86], scale: 150000 },  // Paris
  '77': { center: [2.97, 48.55], scale: 30000 },   // Seine-et-Marne
  '78': { center: [1.85, 48.80], scale: 40000 },   // Yvelines
  '91': { center: [2.25, 48.53], scale: 45000 },   // Essonne
  '92': { center: [2.22, 48.86], scale: 80000 },   // Hauts-de-Seine
  '93': { center: [2.45, 48.92], scale: 80000 },   // Seine-Saint-Denis
  '94': { center: [2.47, 48.78], scale: 70000 },   // Val-de-Marne
  '95': { center: [2.15, 49.05], scale: 45000 },   // Val-d'Oise
  // Bretagne
  '22': { center: [-2.98, 48.45], scale: 22000 },  // Côtes-d'Armor
  '29': { center: [-4.15, 48.35], scale: 20000 },  // Finistère
  '35': { center: [-1.68, 48.10], scale: 22000 },  // Ille-et-Vilaine
  '56': { center: [-2.85, 47.75], scale: 22000 },  // Morbihan
  // Provence-Alpes-Côte d'Azur
  '04': { center: [6.25, 44.05], scale: 18000 },   // Alpes-de-Haute-Provence
  '05': { center: [6.35, 44.65], scale: 22000 },   // Hautes-Alpes
  '06': { center: [7.20, 43.85], scale: 25000 },   // Alpes-Maritimes
  '13': { center: [5.05, 43.55], scale: 25000 },   // Bouches-du-Rhône
  '83': { center: [6.25, 43.45], scale: 22000 },   // Var
  '84': { center: [5.15, 44.00], scale: 28000 },   // Vaucluse
  // Auvergne-Rhône-Alpes
  '01': { center: [5.35, 46.10], scale: 22000 },   // Ain
  '03': { center: [3.35, 46.35], scale: 20000 },   // Allier
  '07': { center: [4.45, 44.75], scale: 22000 },   // Ardèche
  '15': { center: [2.70, 45.05], scale: 22000 },   // Cantal
  '26': { center: [5.15, 44.65], scale: 20000 },   // Drôme
  '38': { center: [5.75, 45.25], scale: 18000 },   // Isère
  '42': { center: [4.25, 45.75], scale: 25000 },   // Loire
  '43': { center: [3.85, 45.05], scale: 25000 },   // Haute-Loire
  '63': { center: [3.10, 45.75], scale: 18000 },   // Puy-de-Dôme
  '69': { center: [4.65, 45.85], scale: 30000 },   // Rhône
  '73': { center: [6.45, 45.50], scale: 20000 },   // Savoie
  '74': { center: [6.45, 46.00], scale: 22000 },   // Haute-Savoie
  // Occitanie
  '09': { center: [1.55, 42.95], scale: 25000 },   // Ariège
  '11': { center: [2.35, 43.15], scale: 22000 },   // Aude
  '12': { center: [2.65, 44.25], scale: 18000 },   // Aveyron
  '30': { center: [4.20, 44.00], scale: 22000 },   // Gard
  '31': { center: [1.45, 43.35], scale: 20000 },   // Haute-Garonne
  '32': { center: [0.55, 43.70], scale: 22000 },   // Gers
  '34': { center: [3.55, 43.60], scale: 22000 },   // Hérault
  '46': { center: [1.65, 44.55], scale: 25000 },   // Lot
  '48': { center: [3.50, 44.50], scale: 25000 },   // Lozère
  '65': { center: [0.15, 43.05], scale: 25000 },   // Hautes-Pyrénées
  '66': { center: [2.55, 42.60], scale: 25000 },   // Pyrénées-Orientales
  '81': { center: [2.15, 43.85], scale: 25000 },   // Tarn
  '82': { center: [1.35, 44.05], scale: 30000 },   // Tarn-et-Garonne
  // Nouvelle-Aquitaine
  '16': { center: [0.20, 45.70], scale: 25000 },   // Charente
  '17': { center: [-0.90, 45.90], scale: 20000 },  // Charente-Maritime
  '19': { center: [1.85, 45.30], scale: 25000 },   // Corrèze
  '23': { center: [2.05, 46.15], scale: 25000 },   // Creuse
  '24': { center: [0.85, 45.10], scale: 18000 },   // Dordogne
  '33': { center: [-0.60, 44.85], scale: 18000 },  // Gironde
  '40': { center: [-0.85, 43.90], scale: 18000 },  // Landes
  '47': { center: [0.50, 44.35], scale: 25000 },   // Lot-et-Garonne
  '64': { center: [-0.85, 43.25], scale: 20000 },  // Pyrénées-Atlantiques
  '79': { center: [-0.40, 46.55], scale: 25000 },  // Deux-Sèvres
  '86': { center: [0.55, 46.55], scale: 22000 },   // Vienne
  '87': { center: [1.30, 45.90], scale: 25000 },   // Haute-Vienne
  // Grand Est
  '08': { center: [4.65, 49.75], scale: 25000 },   // Ardennes
  '10': { center: [4.10, 48.30], scale: 25000 },   // Aube
  '51': { center: [4.20, 49.00], scale: 18000 },   // Marne
  '52': { center: [5.20, 48.10], scale: 22000 },   // Haute-Marne
  '54': { center: [6.20, 48.75], scale: 25000 },   // Meurthe-et-Moselle
  '55': { center: [5.40, 49.00], scale: 22000 },   // Meuse
  '57': { center: [6.65, 49.10], scale: 22000 },   // Moselle
  '67': { center: [7.55, 48.65], scale: 25000 },   // Bas-Rhin
  '68': { center: [7.25, 47.85], scale: 28000 },   // Haut-Rhin
  '88': { center: [6.40, 48.20], scale: 25000 },   // Vosges
  // Bourgogne-Franche-Comté
  '21': { center: [4.85, 47.35], scale: 18000 },   // Côte-d'Or
  '25': { center: [6.35, 47.15], scale: 25000 },   // Doubs
  '39': { center: [5.70, 46.75], scale: 25000 },   // Jura
  '58': { center: [3.50, 47.10], scale: 20000 },   // Nièvre
  '70': { center: [6.15, 47.65], scale: 25000 },   // Haute-Saône
  '71': { center: [4.55, 46.65], scale: 18000 },   // Saône-et-Loire
  '89': { center: [3.55, 47.80], scale: 20000 },   // Yonne
  '90': { center: [6.90, 47.65], scale: 60000 },   // Territoire de Belfort
  // Centre-Val de Loire
  '18': { center: [2.45, 46.95], scale: 20000 },   // Cher
  '28': { center: [1.45, 48.30], scale: 22000 },   // Eure-et-Loir
  '36': { center: [1.70, 46.75], scale: 22000 },   // Indre
  '37': { center: [0.75, 47.25], scale: 22000 },   // Indre-et-Loire
  '41': { center: [1.35, 47.55], scale: 22000 },   // Loir-et-Cher
  '45': { center: [2.35, 47.90], scale: 20000 },   // Loiret
  // Normandie
  '14': { center: [-0.40, 49.10], scale: 25000 },  // Calvados
  '27': { center: [0.95, 49.10], scale: 22000 },   // Eure
  '50': { center: [-1.30, 49.10], scale: 22000 },  // Manche
  '61': { center: [0.15, 48.55], scale: 22000 },   // Orne
  '76': { center: [0.95, 49.65], scale: 22000 },   // Seine-Maritime
  // Hauts-de-France
  '02': { center: [3.65, 49.55], scale: 20000 },   // Aisne
  '59': { center: [3.25, 50.35], scale: 20000 },   // Nord
  '60': { center: [2.45, 49.40], scale: 25000 },   // Oise
  '62': { center: [2.35, 50.50], scale: 20000 },   // Pas-de-Calais
  '80': { center: [2.35, 49.90], scale: 22000 },   // Somme
  // Pays de la Loire
  '44': { center: [-1.70, 47.25], scale: 20000 },  // Loire-Atlantique
  '49': { center: [-0.55, 47.35], scale: 20000 },  // Maine-et-Loire
  '53': { center: [-0.80, 48.10], scale: 25000 },  // Mayenne
  '72': { center: [0.25, 47.95], scale: 22000 },   // Sarthe
  '85': { center: [-1.35, 46.70], scale: 22000 },  // Vendée
  // Corse
  '2A': { center: [8.95, 41.90], scale: 28000 },   // Corse-du-Sud
  '2B': { center: [9.25, 42.45], scale: 25000 },   // Haute-Corse
};

// Major city coordinates by department
const DEPARTMENT_CITIES: Record<string, Array<{
  name: string;
  coordinates: [number, number];
  type: 'city' | 'town' | 'village';
  isPrefecture?: boolean;
  isSubprefecture?: boolean;
  population?: number;
}>> = {
  // Paris
  '75': [
    { name: 'Paris', coordinates: [2.35, 48.86], type: 'city', isPrefecture: true, population: 2145906 },
  ],
  // Rhône
  '69': [
    { name: 'Lyon', coordinates: [4.84, 45.76], type: 'city', isPrefecture: true, population: 522969 },
    { name: 'Villeurbanne', coordinates: [4.88, 45.77], type: 'city', population: 154781 },
    { name: 'Vénissieux', coordinates: [4.89, 45.70], type: 'town', population: 66256 },
  ],
  // Bouches-du-Rhône
  '13': [
    { name: 'Marseille', coordinates: [5.37, 43.30], type: 'city', isPrefecture: true, population: 870731 },
    { name: 'Aix-en-Provence', coordinates: [5.45, 43.53], type: 'city', isSubprefecture: true, population: 147122 },
    { name: 'Arles', coordinates: [4.63, 43.68], type: 'town', isSubprefecture: true, population: 52510 },
  ],
  // Gironde
  '33': [
    { name: 'Bordeaux', coordinates: [-0.58, 44.84], type: 'city', isPrefecture: true, population: 260958 },
    { name: 'Mérignac', coordinates: [-0.65, 44.84], type: 'city', population: 73197 },
    { name: 'Pessac', coordinates: [-0.63, 44.80], type: 'town', population: 65447 },
  ],
  // Nord
  '59': [
    { name: 'Lille', coordinates: [3.06, 50.63], type: 'city', isPrefecture: true, population: 236234 },
    { name: 'Roubaix', coordinates: [3.18, 50.69], type: 'city', isSubprefecture: true, population: 98828 },
    { name: 'Tourcoing', coordinates: [3.16, 50.72], type: 'city', population: 99029 },
    { name: 'Dunkerque', coordinates: [2.38, 51.03], type: 'city', isSubprefecture: true, population: 86279 },
  ],
  // Haute-Garonne
  '31': [
    { name: 'Toulouse', coordinates: [1.44, 43.60], type: 'city', isPrefecture: true, population: 498003 },
    { name: 'Colomiers', coordinates: [1.34, 43.61], type: 'town', population: 40318 },
    { name: 'Tournefeuille', coordinates: [1.35, 43.59], type: 'town', population: 28904 },
  ],
  // Alpes-Maritimes
  '06': [
    { name: 'Nice', coordinates: [7.26, 43.71], type: 'city', isPrefecture: true, population: 342669 },
    { name: 'Antibes', coordinates: [7.12, 43.58], type: 'city', population: 72999 },
    { name: 'Cannes', coordinates: [7.01, 43.55], type: 'city', isSubprefecture: true, population: 74545 },
  ],
  // Loire-Atlantique
  '44': [
    { name: 'Nantes', coordinates: [-1.55, 47.22], type: 'city', isPrefecture: true, population: 320732 },
    { name: 'Saint-Nazaire', coordinates: [-2.21, 47.27], type: 'city', isSubprefecture: true, population: 72299 },
    { name: 'Saint-Herblain', coordinates: [-1.65, 47.21], type: 'town', population: 47977 },
  ],
  // Bas-Rhin
  '67': [
    { name: 'Strasbourg', coordinates: [7.75, 48.57], type: 'city', isPrefecture: true, population: 290576 },
    { name: 'Haguenau', coordinates: [7.79, 48.82], type: 'town', isSubprefecture: true, population: 35353 },
    { name: 'Schiltigheim', coordinates: [7.75, 48.61], type: 'town', population: 34162 },
  ],
  // Hérault
  '34': [
    { name: 'Montpellier', coordinates: [3.88, 43.61], type: 'city', isPrefecture: true, population: 299096 },
    { name: 'Béziers', coordinates: [3.21, 43.34], type: 'city', isSubprefecture: true, population: 79041 },
    { name: 'Sète', coordinates: [3.70, 43.40], type: 'town', population: 44270 },
  ],
  // Ille-et-Vilaine
  '35': [
    { name: 'Rennes', coordinates: [-1.68, 48.11], type: 'city', isPrefecture: true, population: 222485 },
    { name: 'Saint-Malo', coordinates: [-2.00, 48.65], type: 'town', isSubprefecture: true, population: 46342 },
    { name: 'Fougères', coordinates: [-1.20, 48.35], type: 'town', isSubprefecture: true, population: 20078 },
  ],
  // Finistère
  '29': [
    { name: 'Quimper', coordinates: [-4.10, 47.99], type: 'city', isPrefecture: true, population: 63929 },
    { name: 'Brest', coordinates: [-4.49, 48.39], type: 'city', isSubprefecture: true, population: 139342 },
    { name: 'Concarneau', coordinates: [-3.92, 47.87], type: 'town', population: 19034 },
  ],
};

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
  const [hoveredCity, setHoveredCity] = useState<string | null>(null);
  const [tooltipPos, setTooltipPos] = useState({ x: 0, y: 0 });

  // Get department's center and scale
  const deptConfig = DEPARTMENT_CENTERS[departmentCode] || { center: [2.5, 46.5], scale: 20000 };

  // Get cities for this department
  const cities = DEPARTMENT_CITIES[departmentCode] || [];

  // Find matching commune for a city
  const findCommune = (cityName: string) => {
    return communes.find(
      (c) => c.name.toLowerCase() === cityName.toLowerCase()
    );
  };

  const handleCityClick = (cityName: string) => {
    const commune = findCommune(cityName);
    if (commune) {
      onCommuneSelect(commune);
    }
  };

  const handleMouseEnter = (cityName: string, event: React.MouseEvent) => {
    setHoveredCity(cityName);
    const rect = event.currentTarget.closest('.map-container')?.getBoundingClientRect();
    if (rect) {
      setTooltipPos({
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
      });
    }
  };

  const hoveredCityData = useMemo(() => {
    return cities.find(c => c.name === hoveredCity);
  }, [cities, hoveredCity]);

  return (
    <div className="relative mb-6">
      <h3 className="text-sm font-medium text-gray-700 mb-3">
        Map of {departmentName}
      </h3>

      <div className="map-container relative bg-gradient-to-br from-emerald-50 to-green-100 rounded-xl overflow-hidden shadow-inner border border-green-200">
        <ComposableMap
          projection="geoMercator"
          projectionConfig={{
            center: deptConfig.center,
            scale: deptConfig.scale,
          }}
          style={{ width: '100%', height: 'auto', minHeight: '350px', maxHeight: '450px' }}
        >
          <ZoomableGroup center={deptConfig.center} zoom={1}>
            {/* Department boundary */}
            <Geographies geography={FRANCE_DEPARTMENTS_GEO_URL}>
              {({ geographies }) =>
                geographies
                  .filter((geo) => geo.properties.code === departmentCode)
                  .map((geo) => (
                    <Geography
                      key={geo.rsmKey}
                      geography={geo}
                      fill="#BBF7D0"
                      stroke="#22C55E"
                      strokeWidth={2}
                      style={{
                        default: { outline: 'none' },
                        hover: { outline: 'none' },
                        pressed: { outline: 'none' },
                      }}
                    />
                  ))
              }
            </Geographies>

            {/* City markers */}
            {cities.map((city) => {
              const colors = SETTLEMENT_COLORS[city.type];
              const isHovered = hoveredCity === city.name;
              const commune = findCommune(city.name);
              const isClickable = !!commune;
              const markerRadius = isHovered ? colors.radius + 2 : colors.radius;

              return (
                <Marker key={city.name} coordinates={city.coordinates}>
                  <circle
                    r={markerRadius}
                    fill={colors.fill}
                    stroke={city.isPrefecture ? '#DC2626' : city.isSubprefecture ? '#9333EA' : colors.stroke}
                    strokeWidth={city.isPrefecture ? 3 : city.isSubprefecture ? 2.5 : 2}
                    className={`transition-all duration-150 ${isClickable ? 'cursor-pointer' : 'cursor-default'}`}
                    onClick={() => isClickable && handleCityClick(city.name)}
                    onMouseEnter={(e) => handleMouseEnter(city.name, e as unknown as React.MouseEvent)}
                    onMouseLeave={() => setHoveredCity(null)}
                  />
                  {city.isPrefecture && (
                    <text
                      y={4}
                      textAnchor="middle"
                      fontSize="8"
                      fontWeight="bold"
                      fill="white"
                      className="pointer-events-none"
                    >
                      ★
                    </text>
                  )}
                  <text
                    y={-markerRadius - 4}
                    textAnchor="middle"
                    fontSize={city.type === 'city' ? 10 : city.type === 'town' ? 9 : 8}
                    fontWeight={city.type === 'city' ? 600 : 500}
                    fill="#1F2937"
                    className="pointer-events-none"
                    style={{
                      textShadow: '1px 1px 2px white, -1px -1px 2px white, 1px -1px 2px white, -1px 1px 2px white',
                    }}
                  >
                    {city.name}
                  </text>
                </Marker>
              );
            })}
          </ZoomableGroup>
        </ComposableMap>

        {/* Hover Tooltip */}
        {hoveredCityData && (
          <div
            className="absolute bg-white rounded-lg shadow-xl border border-gray-200 p-3 pointer-events-none z-20 min-w-[180px]"
            style={{
              left: Math.min(Math.max(tooltipPos.x, 90), 280),
              top: Math.max(tooltipPos.y - 10, 60),
              transform: 'translate(-50%, -100%)',
            }}
          >
            <div className="flex items-center gap-2 mb-1">
              <span
                className="w-3 h-3 rounded-full"
                style={{
                  backgroundColor: SETTLEMENT_COLORS[hoveredCityData.type].fill,
                }}
              />
              <h4 className="font-bold text-gray-900">{hoveredCityData.name}</h4>
            </div>
            <p className="text-sm text-gray-500 capitalize mb-1">
              {hoveredCityData.type}
              {hoveredCityData.isPrefecture && ' (Prefecture)'}
              {hoveredCityData.isSubprefecture && ' (Sub-prefecture)'}
            </p>
            {hoveredCityData.population && (
              <p className="text-sm text-gray-600">
                Pop: {hoveredCityData.population.toLocaleString()}
              </p>
            )}
            <div className="mt-2 pt-2 border-t border-gray-100">
              <span className="text-xs text-primary-600 font-medium">
                {findCommune(hoveredCityData.name)
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

      {/* City list for departments without predefined markers */}
      {cities.length === 0 && communes.length > 0 && (
        <div className="mt-4">
          <h4 className="text-sm font-medium text-gray-700 mb-2">Major Cities</h4>
          <div className="flex flex-wrap gap-2">
            {communes.slice(0, 10).map((commune) => (
              <button
                key={commune.code}
                onClick={() => onCommuneSelect(commune)}
                className="px-3 py-1.5 text-sm bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors"
              >
                {commune.name}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
