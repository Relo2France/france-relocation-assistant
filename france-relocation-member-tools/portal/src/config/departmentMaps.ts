/**
 * Department Maps Configuration
 *
 * SVG maps for individual departments showing cities, towns, and villages.
 * Each department has a viewBox, outline path, and list of settlements.
 */

export interface SettlementMarker {
  name: string;
  x: number;
  y: number;
  population: number;
  type: 'city' | 'town' | 'village';
  isPrefecture?: boolean;
  isSubprefecture?: boolean;
}

export interface DepartmentDetailMap {
  viewBox: string;
  outline: string;
  settlements: SettlementMarker[];
}

// Settlement marker colors based on type
export const SETTLEMENT_COLORS = {
  city: { fill: '#2563EB', stroke: '#1E40AF', radius: 8 },
  town: { fill: '#D97706', stroke: '#B45309', radius: 6 },
  village: { fill: '#059669', stroke: '#047857', radius: 4 },
};

// Paris (75)
const PARIS: DepartmentDetailMap = {
  viewBox: '0 0 300 280',
  outline: 'M 50,80 Q 90,50 150,50 Q 210,55 250,90 Q 275,130 270,175 Q 260,220 220,245 Q 170,265 120,255 Q 70,240 45,195 Q 30,150 40,110 Z',
  settlements: [
    { name: 'Paris', x: 150, y: 140, population: 2165000, type: 'city', isPrefecture: true },
    { name: 'Montmartre', x: 155, y: 85, population: 0, type: 'town' },
    { name: 'Belleville', x: 195, y: 115, population: 0, type: 'town' },
    { name: 'Le Marais', x: 175, y: 145, population: 0, type: 'town' },
    { name: 'Latin Quarter', x: 155, y: 170, population: 0, type: 'town' },
    { name: 'Montparnasse', x: 130, y: 185, population: 0, type: 'town' },
    { name: 'Auteuil', x: 75, y: 165, population: 0, type: 'town' },
    { name: 'Batignolles', x: 115, y: 95, population: 0, type: 'town' },
  ],
};

// Hauts-de-Seine (92)
const HAUTS_DE_SEINE: DepartmentDetailMap = {
  viewBox: '0 0 300 320',
  outline: 'M 80,40 Q 140,25 200,45 Q 250,75 265,135 Q 275,195 255,250 Q 220,295 160,305 Q 100,300 60,260 Q 35,210 40,150 Q 50,90 80,50 Z',
  settlements: [
    { name: 'Nanterre', x: 115, y: 115, population: 96000, type: 'city', isPrefecture: true },
    { name: 'Boulogne-Billancourt', x: 165, y: 195, population: 121000, type: 'city' },
    { name: 'Colombes', x: 135, y: 75, population: 85000, type: 'city' },
    { name: 'Courbevoie', x: 165, y: 95, population: 82000, type: 'city' },
    { name: 'Rueil-Malmaison', x: 85, y: 150, population: 81000, type: 'city' },
    { name: 'Issy-les-Moulineaux', x: 185, y: 230, population: 69000, type: 'city' },
    { name: 'Levallois-Perret', x: 175, y: 115, population: 65000, type: 'city' },
    { name: 'Neuilly-sur-Seine', x: 195, y: 135, population: 62000, type: 'city' },
    { name: 'Antony', x: 165, y: 290, population: 62000, type: 'city', isSubprefecture: true },
    { name: 'Clamart', x: 145, y: 265, population: 53000, type: 'town' },
    { name: 'Montrouge', x: 195, y: 255, population: 50000, type: 'town' },
    { name: 'Meudon', x: 130, y: 235, population: 46000, type: 'town' },
    { name: 'Suresnes', x: 125, y: 165, population: 49000, type: 'town' },
    { name: 'Puteaux', x: 155, y: 135, population: 45000, type: 'town' },
    { name: 'Gennevilliers', x: 155, y: 55, population: 48000, type: 'town' },
  ],
};

// Seine-Saint-Denis (93)
const SEINE_SAINT_DENIS: DepartmentDetailMap = {
  viewBox: '0 0 320 300',
  outline: 'M 50,60 Q 110,40 175,50 Q 240,65 285,110 Q 310,160 300,215 Q 280,265 225,285 Q 165,295 105,275 Q 55,250 40,195 Q 30,140 45,90 Z',
  settlements: [
    { name: 'Bobigny', x: 200, y: 150, population: 54000, type: 'city', isPrefecture: true },
    { name: 'Saint-Denis', x: 130, y: 95, population: 113000, type: 'city', isSubprefecture: true },
    { name: 'Montreuil', x: 230, y: 215, population: 111000, type: 'city' },
    { name: 'Aulnay-sous-Bois', x: 240, y: 85, population: 86000, type: 'city' },
    { name: 'Aubervilliers', x: 155, y: 115, population: 90000, type: 'city' },
    { name: 'Drancy', x: 210, y: 115, population: 72000, type: 'city' },
    { name: 'Noisy-le-Grand', x: 275, y: 235, population: 69000, type: 'city' },
    { name: 'Pantin', x: 185, y: 145, population: 56000, type: 'city' },
    { name: 'Bondy', x: 235, y: 155, population: 54000, type: 'town' },
    { name: 'Épinay-sur-Seine', x: 115, y: 65, population: 54000, type: 'town' },
    { name: 'Sevran', x: 270, y: 95, population: 51000, type: 'town' },
    { name: 'Le Blanc-Mesnil', x: 220, y: 85, population: 54000, type: 'town' },
    { name: 'Livry-Gargan', x: 265, y: 130, population: 45000, type: 'town' },
  ],
};

// Bouches-du-Rhône (13)
const BOUCHES_DU_RHONE: DepartmentDetailMap = {
  viewBox: '0 0 400 350',
  outline: 'M 60,50 Q 140,30 230,45 Q 320,65 370,120 Q 395,180 380,245 Q 355,305 280,330 Q 195,345 115,320 Q 50,290 35,225 Q 25,160 40,100 Z',
  settlements: [
    { name: 'Marseille', x: 290, y: 265, population: 873000, type: 'city', isPrefecture: true },
    { name: 'Aix-en-Provence', x: 255, y: 145, population: 147000, type: 'city', isSubprefecture: true },
    { name: 'Arles', x: 95, y: 210, population: 53000, type: 'city', isSubprefecture: true },
    { name: 'Martigues', x: 165, y: 270, population: 49000, type: 'city' },
    { name: 'Aubagne', x: 320, y: 225, population: 47000, type: 'city' },
    { name: 'Salon-de-Provence', x: 145, y: 155, population: 46000, type: 'city' },
    { name: 'Istres', x: 120, y: 235, population: 44000, type: 'city' },
    { name: 'La Ciotat', x: 345, y: 280, population: 36000, type: 'town' },
    { name: 'Vitrolles', x: 220, y: 225, population: 35000, type: 'town' },
    { name: 'Marignane', x: 215, y: 250, population: 34000, type: 'town' },
    { name: 'Miramas', x: 110, y: 195, population: 27000, type: 'town' },
    { name: 'Cassis', x: 330, y: 295, population: 8000, type: 'village' },
    { name: 'Les Baux', x: 85, y: 170, population: 400, type: 'village' },
  ],
};

// Var (83)
const VAR: DepartmentDetailMap = {
  viewBox: '0 0 400 320',
  outline: 'M 40,80 Q 120,50 210,65 Q 300,85 360,140 Q 395,200 375,260 Q 340,305 260,315 Q 170,320 95,290 Q 35,250 30,185 Q 30,120 45,85 Z',
  settlements: [
    { name: 'Toulon', x: 175, y: 265, population: 178000, type: 'city', isPrefecture: true },
    { name: 'Draguignan', x: 295, y: 125, population: 42000, type: 'city', isSubprefecture: true },
    { name: 'Fréjus', x: 345, y: 175, population: 55000, type: 'city' },
    { name: 'Hyères', x: 215, y: 280, population: 57000, type: 'city' },
    { name: 'La Seyne-sur-Mer', x: 150, y: 280, population: 65000, type: 'city' },
    { name: 'Six-Fours', x: 125, y: 285, population: 36000, type: 'town' },
    { name: 'Saint-Raphaël', x: 365, y: 200, population: 36000, type: 'town' },
    { name: 'Brignoles', x: 230, y: 150, population: 18000, type: 'town', isSubprefecture: true },
    { name: 'Saint-Maximin', x: 200, y: 115, population: 17000, type: 'town' },
    { name: 'Le Luc', x: 280, y: 165, population: 11000, type: 'town' },
    { name: 'Saint-Tropez', x: 335, y: 235, population: 4500, type: 'village' },
    { name: 'Bormes', x: 275, y: 275, population: 8000, type: 'village' },
    { name: 'Bandol', x: 140, y: 295, population: 9000, type: 'village' },
  ],
};

// Alpes-Maritimes (06)
const ALPES_MARITIMES: DepartmentDetailMap = {
  viewBox: '0 0 380 320',
  outline: 'M 50,100 Q 130,60 220,75 Q 310,95 355,160 Q 380,230 355,285 Q 310,320 230,315 Q 145,305 80,260 Q 35,210 40,150 Z',
  settlements: [
    { name: 'Nice', x: 290, y: 240, population: 342000, type: 'city', isPrefecture: true },
    { name: 'Cannes', x: 150, y: 275, population: 74000, type: 'city' },
    { name: 'Antibes', x: 195, y: 265, population: 74000, type: 'city' },
    { name: 'Grasse', x: 135, y: 225, population: 51000, type: 'city', isSubprefecture: true },
    { name: 'Cagnes-sur-Mer', x: 235, y: 255, population: 52000, type: 'city' },
    { name: 'Menton', x: 350, y: 210, population: 30000, type: 'town' },
    { name: 'Mougins', x: 165, y: 245, population: 19000, type: 'town' },
    { name: 'Vence', x: 215, y: 215, population: 19000, type: 'town' },
    { name: 'Mandelieu', x: 125, y: 265, population: 23000, type: 'town' },
    { name: 'Monaco', x: 325, y: 235, population: 39000, type: 'city' },
    { name: 'Èze', x: 310, y: 225, population: 3000, type: 'village' },
    { name: 'Saint-Paul', x: 225, y: 235, population: 3500, type: 'village' },
    { name: 'Gourdon', x: 155, y: 195, population: 400, type: 'village' },
  ],
};

// Gironde (33)
const GIRONDE: DepartmentDetailMap = {
  viewBox: '0 0 380 400',
  outline: 'M 50,60 Q 140,35 235,55 Q 325,80 360,150 Q 380,230 355,305 Q 310,365 225,385 Q 130,390 65,345 Q 25,290 30,210 Q 35,125 50,70 Z',
  settlements: [
    { name: 'Bordeaux', x: 200, y: 175, population: 261000, type: 'city', isPrefecture: true },
    { name: 'Mérignac', x: 160, y: 185, population: 74000, type: 'city' },
    { name: 'Pessac', x: 165, y: 210, population: 66000, type: 'city' },
    { name: 'Talence', x: 185, y: 205, population: 44000, type: 'city' },
    { name: 'Arcachon', x: 85, y: 315, population: 12000, type: 'town', isSubprefecture: true },
    { name: 'Libourne', x: 290, y: 155, population: 25000, type: 'town', isSubprefecture: true },
    { name: 'Saint-Médard', x: 160, y: 160, population: 32000, type: 'town' },
    { name: 'Villenave', x: 195, y: 225, population: 33000, type: 'town' },
    { name: 'Gradignan', x: 175, y: 215, population: 26000, type: 'town' },
    { name: 'Le Bouscat', x: 190, y: 165, population: 24000, type: 'town' },
    { name: 'Blaye', x: 225, y: 65, population: 5000, type: 'town', isSubprefecture: true },
    { name: 'Langon', x: 270, y: 305, population: 8000, type: 'town', isSubprefecture: true },
    { name: 'Saint-Émilion', x: 305, y: 175, population: 2000, type: 'village' },
    { name: 'Pauillac', x: 175, y: 85, population: 5000, type: 'village' },
  ],
};

// Haute-Garonne (31)
const HAUTE_GARONNE: DepartmentDetailMap = {
  viewBox: '0 0 350 400',
  outline: 'M 80,50 Q 160,30 240,55 Q 310,85 330,155 Q 340,235 310,310 Q 265,370 180,385 Q 95,380 50,320 Q 25,255 35,180 Q 50,105 80,55 Z',
  settlements: [
    { name: 'Toulouse', x: 175, y: 145, population: 493000, type: 'city', isPrefecture: true },
    { name: 'Colomiers', x: 130, y: 135, population: 40000, type: 'city' },
    { name: 'Tournefeuille', x: 125, y: 160, population: 29000, type: 'town' },
    { name: 'Muret', x: 155, y: 225, population: 27000, type: 'town', isSubprefecture: true },
    { name: 'Blagnac', x: 155, y: 115, population: 25000, type: 'town' },
    { name: 'Plaisance', x: 130, y: 175, population: 19000, type: 'town' },
    { name: 'Cugnaux', x: 145, y: 180, population: 19000, type: 'town' },
    { name: 'Ramonville', x: 200, y: 175, population: 14000, type: 'town' },
    { name: 'Saint-Gaudens', x: 95, y: 340, population: 12000, type: 'town', isSubprefecture: true },
    { name: 'Balma', x: 210, y: 145, population: 17000, type: 'town' },
    { name: 'L\'Union', x: 210, y: 120, population: 12000, type: 'town' },
    { name: 'Castanet', x: 195, y: 195, population: 14000, type: 'town' },
    { name: 'Bagnères-de-Luchon', x: 115, y: 375, population: 2500, type: 'village' },
  ],
};

// Rhône (69)
const RHONE: DepartmentDetailMap = {
  viewBox: '0 0 320 350',
  outline: 'M 70,50 Q 145,30 220,55 Q 285,85 300,155 Q 305,230 280,295 Q 240,345 165,355 Q 90,350 50,295 Q 30,235 40,165 Q 55,95 70,55 Z',
  settlements: [
    { name: 'Lyon', x: 175, y: 230, population: 522000, type: 'city', isPrefecture: true },
    { name: 'Villeurbanne', x: 200, y: 210, population: 154000, type: 'city' },
    { name: 'Vénissieux', x: 200, y: 260, population: 66000, type: 'city' },
    { name: 'Saint-Priest', x: 225, y: 250, population: 47000, type: 'city' },
    { name: 'Vaulx-en-Velin', x: 210, y: 195, population: 52000, type: 'city' },
    { name: 'Bron', x: 215, y: 230, population: 43000, type: 'city' },
    { name: 'Villefranche', x: 165, y: 70, population: 37000, type: 'city', isSubprefecture: true },
    { name: 'Caluire', x: 185, y: 190, population: 43000, type: 'town' },
    { name: 'Oullins', x: 165, y: 255, population: 26000, type: 'town' },
    { name: 'Rillieux', x: 195, y: 175, population: 32000, type: 'town' },
    { name: 'Décines', x: 225, y: 205, population: 28000, type: 'town' },
    { name: 'Meyzieu', x: 245, y: 210, population: 34000, type: 'town' },
    { name: 'Tassin', x: 145, y: 230, population: 22000, type: 'town' },
  ],
};

// Loire-Atlantique (44)
const LOIRE_ATLANTIQUE: DepartmentDetailMap = {
  viewBox: '0 0 380 350',
  outline: 'M 40,80 Q 130,50 225,70 Q 320,95 360,165 Q 380,245 350,310 Q 300,355 210,360 Q 115,355 55,300 Q 20,240 25,170 Q 35,105 45,85 Z',
  settlements: [
    { name: 'Nantes', x: 215, y: 200, population: 318000, type: 'city', isPrefecture: true },
    { name: 'Saint-Nazaire', x: 85, y: 245, population: 72000, type: 'city', isSubprefecture: true },
    { name: 'Rezé', x: 210, y: 225, population: 43000, type: 'city' },
    { name: 'Saint-Herblain', x: 185, y: 195, population: 46000, type: 'city' },
    { name: 'Orvault', x: 195, y: 175, population: 27000, type: 'town' },
    { name: 'Vertou', x: 235, y: 225, population: 25000, type: 'town' },
    { name: 'Carquefou', x: 250, y: 190, population: 20000, type: 'town' },
    { name: 'La Baule', x: 55, y: 275, population: 17000, type: 'town' },
    { name: 'Châteaubriant', x: 320, y: 100, population: 12000, type: 'town', isSubprefecture: true },
    { name: 'Guérande', x: 65, y: 255, population: 16000, type: 'town' },
    { name: 'Ancenis', x: 320, y: 185, population: 11000, type: 'town' },
    { name: 'Pornic', x: 90, y: 310, population: 15000, type: 'town' },
    { name: 'Clisson', x: 260, y: 265, population: 7500, type: 'village' },
  ],
};

// Finistère (29)
const FINISTERE: DepartmentDetailMap = {
  viewBox: '0 0 380 320',
  outline: 'M 30,100 Q 110,60 200,75 Q 290,95 345,155 Q 375,220 350,280 Q 305,320 220,315 Q 130,305 65,260 Q 20,205 25,145 Z',
  settlements: [
    { name: 'Quimper', x: 165, y: 260, population: 63000, type: 'city', isPrefecture: true },
    { name: 'Brest', x: 105, y: 130, population: 140000, type: 'city', isSubprefecture: true },
    { name: 'Concarneau', x: 215, y: 275, population: 20000, type: 'town' },
    { name: 'Morlaix', x: 200, y: 95, population: 15000, type: 'town', isSubprefecture: true },
    { name: 'Douarnenez', x: 115, y: 250, population: 14000, type: 'town' },
    { name: 'Landerneau', x: 135, y: 145, population: 16000, type: 'town' },
    { name: 'Quimperlé', x: 275, y: 265, population: 12000, type: 'town' },
    { name: 'Pont-l\'Abbé', x: 135, y: 285, population: 8500, type: 'town' },
    { name: 'Châteaulin', x: 155, y: 210, population: 5500, type: 'town', isSubprefecture: true },
    { name: 'Roscoff', x: 175, y: 65, population: 3500, type: 'village' },
    { name: 'Crozon', x: 85, y: 210, population: 7500, type: 'village' },
    { name: 'Locronan', x: 140, y: 235, population: 800, type: 'village' },
  ],
};

// Ille-et-Vilaine (35)
const ILLE_ET_VILAINE: DepartmentDetailMap = {
  viewBox: '0 0 360 340',
  outline: 'M 50,70 Q 135,45 225,65 Q 310,90 345,160 Q 365,235 335,300 Q 285,340 195,345 Q 105,340 50,290 Q 25,235 30,165 Q 40,100 50,75 Z',
  settlements: [
    { name: 'Rennes', x: 205, y: 185, population: 222000, type: 'city', isPrefecture: true },
    { name: 'Saint-Malo', x: 195, y: 60, population: 46000, type: 'city', isSubprefecture: true },
    { name: 'Fougères', x: 305, y: 130, population: 20000, type: 'city', isSubprefecture: true },
    { name: 'Vitré', x: 300, y: 200, population: 18000, type: 'town' },
    { name: 'Bruz', x: 195, y: 215, population: 18000, type: 'town' },
    { name: 'Cesson-Sévigné', x: 235, y: 180, population: 18000, type: 'town' },
    { name: 'Betton', x: 210, y: 155, population: 12000, type: 'town' },
    { name: 'Chantepie', x: 230, y: 195, population: 11000, type: 'town' },
    { name: 'Dinard', x: 155, y: 65, population: 10000, type: 'town' },
    { name: 'Redon', x: 115, y: 310, population: 9000, type: 'town', isSubprefecture: true },
    { name: 'Cancale', x: 220, y: 50, population: 5500, type: 'village' },
    { name: 'Combourg', x: 175, y: 110, population: 6000, type: 'village' },
  ],
};

// Map of department codes to their map data
export const DEPARTMENT_MAPS: Record<string, DepartmentDetailMap> = {
  '75': PARIS,
  '92': HAUTS_DE_SEINE,
  '93': SEINE_SAINT_DENIS,
  '13': BOUCHES_DU_RHONE,
  '83': VAR,
  '06': ALPES_MARITIMES,
  '33': GIRONDE,
  '31': HAUTE_GARONNE,
  '69': RHONE,
  '44': LOIRE_ATLANTIQUE,
  '29': FINISTERE,
  '35': ILLE_ET_VILAINE,
};

// Check if a department has a detailed map
export function hasDepartmentMap(deptCode: string): boolean {
  return deptCode in DEPARTMENT_MAPS;
}

// Get map data for a department
export function getDepartmentMap(deptCode: string): DepartmentDetailMap | null {
  return DEPARTMENT_MAPS[deptCode] || null;
}
