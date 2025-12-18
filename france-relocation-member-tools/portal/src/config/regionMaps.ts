/**
 * Regional Maps Configuration
 *
 * SVG paths for departments within each region of France.
 * Each region has its own viewBox and department paths.
 */

export interface DepartmentMapData {
  code: string;
  name: string;
  path: string;
  labelPosition: { x: number; y: number };
  cities?: { name: string; x: number; y: number; isCapital?: boolean }[];
}

export interface RegionMapData {
  viewBox: string;
  departments: DepartmentMapData[];
  backgroundColor?: string;
}

// Color palette for departments (will cycle through)
export const DEPARTMENT_MAP_COLORS = [
  { fill: '#93C5FD', hover: '#60A5FA', border: '#3B82F6' },  // Blue
  { fill: '#86EFAC', hover: '#4ADE80', border: '#22C55E' },  // Green
  { fill: '#FCA5A5', hover: '#F87171', border: '#EF4444' },  // Red
  { fill: '#FCD34D', hover: '#FBBF24', border: '#F59E0B' },  // Yellow
  { fill: '#C4B5FD', hover: '#A78BFA', border: '#8B5CF6' },  // Purple
  { fill: '#67E8F9', hover: '#22D3EE', border: '#06B6D4' },  // Cyan
  { fill: '#FDBA74', hover: '#FB923C', border: '#F97316' },  // Orange
  { fill: '#F9A8D4', hover: '#F472B6', border: '#EC4899' },  // Pink
];

// Île-de-France (8 departments)
const ILE_DE_FRANCE: RegionMapData = {
  viewBox: '0 0 400 350',
  departments: [
    {
      code: '75',
      name: 'Paris',
      path: 'M 180,140 Q 195,130 215,130 Q 235,135 245,150 Q 250,170 240,185 Q 225,198 205,195 Q 185,190 175,175 Q 172,158 180,145 Z',
      labelPosition: { x: 210, y: 165 },
      cities: [{ name: 'Paris', x: 210, y: 162, isCapital: true }],
    },
    {
      code: '92',
      name: 'Hauts-de-Seine',
      path: 'M 145,145 Q 160,135 175,140 Q 182,150 180,165 Q 175,180 160,185 Q 145,182 138,170 Q 135,155 145,145 Z',
      labelPosition: { x: 160, y: 165 },
      cities: [{ name: 'Nanterre', x: 155, y: 158, isCapital: true }],
    },
    {
      code: '93',
      name: 'Seine-Saint-Denis',
      path: 'M 215,120 Q 240,115 260,125 Q 272,140 268,160 Q 258,175 240,180 Q 222,178 215,165 Q 212,145 215,125 Z',
      labelPosition: { x: 242, y: 148 },
      cities: [{ name: 'Bobigny', x: 248, y: 145, isCapital: true }],
    },
    {
      code: '94',
      name: 'Val-de-Marne',
      path: 'M 205,195 Q 225,192 245,200 Q 258,215 255,235 Q 242,252 220,250 Q 198,245 190,228 Q 188,210 200,198 Z',
      labelPosition: { x: 225, y: 225 },
      cities: [{ name: 'Créteil', x: 230, y: 225, isCapital: true }],
    },
    {
      code: '77',
      name: 'Seine-et-Marne',
      path: 'M 260,125 Q 300,110 340,120 Q 375,140 385,180 Q 388,225 370,270 Q 340,305 290,310 Q 250,300 230,265 Q 220,230 235,195 Q 250,160 260,130 Z',
      labelPosition: { x: 310, y: 210 },
      cities: [
        { name: 'Melun', x: 295, y: 250, isCapital: true },
        { name: 'Meaux', x: 300, y: 145 },
        { name: 'Fontainebleau', x: 290, y: 290 },
      ],
    },
    {
      code: '78',
      name: 'Yvelines',
      path: 'M 50,120 Q 90,100 130,110 Q 155,125 160,150 Q 158,180 140,205 Q 115,225 80,220 Q 45,208 30,175 Q 25,142 45,120 Z',
      labelPosition: { x: 95, y: 165 },
      cities: [
        { name: 'Versailles', x: 130, y: 175, isCapital: true },
        { name: 'St-Germain', x: 100, y: 140 },
      ],
    },
    {
      code: '91',
      name: 'Essonne',
      path: 'M 100,225 Q 140,215 180,225 Q 210,245 215,280 Q 205,315 170,330 Q 125,335 90,315 Q 65,290 70,255 Q 80,232 100,225 Z',
      labelPosition: { x: 145, y: 275 },
      cities: [{ name: 'Évry', x: 160, y: 265, isCapital: true }],
    },
    {
      code: '95',
      name: "Val-d'Oise",
      path: 'M 120,50 Q 170,35 220,45 Q 260,60 275,95 Q 278,125 260,145 Q 235,160 200,158 Q 165,155 140,140 Q 115,120 110,90 Q 108,65 120,50 Z',
      labelPosition: { x: 190, y: 95 },
      cities: [{ name: 'Pontoise', x: 140, y: 85, isCapital: true }],
    },
  ],
};

// Provence-Alpes-Côte d'Azur (6 departments)
const PROVENCE_ALPES_COTE_AZUR: RegionMapData = {
  viewBox: '0 0 500 400',
  departments: [
    {
      code: '13',
      name: 'Bouches-du-Rhône',
      path: 'M 80,200 Q 120,180 170,190 Q 210,205 230,240 Q 235,280 210,310 Q 170,335 120,325 Q 75,305 60,265 Q 55,225 75,200 Z',
      labelPosition: { x: 145, y: 260 },
      cities: [
        { name: 'Marseille', x: 175, y: 295, isCapital: true },
        { name: 'Aix-en-Provence', x: 160, y: 235 },
        { name: 'Arles', x: 90, y: 270 },
      ],
    },
    {
      code: '83',
      name: 'Var',
      path: 'M 230,240 Q 280,220 330,235 Q 375,255 395,295 Q 400,340 370,370 Q 325,390 275,375 Q 235,355 220,315 Q 215,275 230,245 Z',
      labelPosition: { x: 305, y: 305 },
      cities: [
        { name: 'Toulon', x: 290, y: 355, isCapital: true },
        { name: 'Draguignan', x: 340, y: 280 },
        { name: 'St-Tropez', x: 365, y: 340 },
      ],
    },
    {
      code: '06',
      name: 'Alpes-Maritimes',
      path: 'M 380,180 Q 420,160 460,175 Q 490,200 495,245 Q 485,290 455,320 Q 415,345 375,330 Q 345,305 345,265 Q 350,220 375,190 Z',
      labelPosition: { x: 420, y: 255 },
      cities: [
        { name: 'Nice', x: 455, y: 290, isCapital: true },
        { name: 'Cannes', x: 395, y: 315 },
        { name: 'Antibes', x: 420, y: 305 },
      ],
    },
    {
      code: '84',
      name: 'Vaucluse',
      path: 'M 80,90 Q 130,70 175,85 Q 210,105 220,145 Q 218,185 190,210 Q 150,230 105,215 Q 65,195 55,155 Q 50,115 75,90 Z',
      labelPosition: { x: 140, y: 150 },
      cities: [
        { name: 'Avignon', x: 105, y: 175, isCapital: true },
        { name: 'Orange', x: 115, y: 105 },
      ],
    },
    {
      code: '04',
      name: 'Alpes-de-Haute-Provence',
      path: 'M 220,80 Q 280,55 340,75 Q 385,100 395,150 Q 390,200 355,230 Q 310,255 260,240 Q 220,220 210,175 Q 205,125 220,85 Z',
      labelPosition: { x: 300, y: 155 },
      cities: [{ name: 'Digne', x: 310, y: 165, isCapital: true }],
    },
    {
      code: '05',
      name: 'Hautes-Alpes',
      path: 'M 290,20 Q 345,10 390,35 Q 425,65 430,115 Q 420,160 385,185 Q 340,205 295,190 Q 255,170 250,125 Q 252,75 285,40 Z',
      labelPosition: { x: 345, y: 105 },
      cities: [{ name: 'Gap', x: 340, y: 130, isCapital: true }],
    },
  ],
};

// Bretagne (4 departments)
const BRETAGNE: RegionMapData = {
  viewBox: '0 0 450 320',
  departments: [
    {
      code: '29',
      name: 'Finistère',
      path: 'M 20,100 Q 60,70 110,80 Q 155,95 175,135 Q 180,175 155,210 Q 115,240 65,235 Q 25,220 15,175 Q 10,135 20,100 Z',
      labelPosition: { x: 95, y: 155 },
      cities: [
        { name: 'Quimper', x: 85, y: 195, isCapital: true },
        { name: 'Brest', x: 55, y: 115 },
      ],
    },
    {
      code: '22',
      name: "Côtes-d'Armor",
      path: 'M 155,60 Q 210,45 265,60 Q 310,80 325,125 Q 328,170 300,200 Q 255,225 200,215 Q 155,200 145,160 Q 140,115 155,70 Z',
      labelPosition: { x: 235, y: 135 },
      cities: [
        { name: 'St-Brieuc', x: 255, y: 105, isCapital: true },
        { name: 'Dinan', x: 290, y: 135 },
      ],
    },
    {
      code: '56',
      name: 'Morbihan',
      path: 'M 90,215 Q 145,200 200,215 Q 245,235 260,280 Q 255,320 215,340 Q 160,355 105,335 Q 60,310 55,265 Q 60,225 90,215 Z',
      labelPosition: { x: 160, y: 275 },
      cities: [
        { name: 'Vannes', x: 175, y: 285, isCapital: true },
        { name: 'Lorient', x: 105, y: 260 },
      ],
    },
    {
      code: '35',
      name: 'Ille-et-Vilaine',
      path: 'M 265,60 Q 330,50 385,75 Q 430,105 440,160 Q 438,215 400,255 Q 350,285 290,275 Q 245,260 235,215 Q 230,170 245,125 Q 255,85 265,65 Z',
      labelPosition: { x: 345, y: 165 },
      cities: [
        { name: 'Rennes', x: 365, y: 180, isCapital: true },
        { name: 'St-Malo', x: 355, y: 90 },
      ],
    },
  ],
};

// Nouvelle-Aquitaine (12 departments) - simplified
const NOUVELLE_AQUITAINE: RegionMapData = {
  viewBox: '0 0 450 500',
  departments: [
    {
      code: '33',
      name: 'Gironde',
      path: 'M 40,150 Q 90,130 140,145 Q 180,165 190,210 Q 185,260 150,295 Q 100,320 50,305 Q 15,280 10,230 Q 15,180 40,150 Z',
      labelPosition: { x: 100, y: 220 },
      cities: [
        { name: 'Bordeaux', x: 115, y: 200, isCapital: true },
        { name: 'Arcachon', x: 55, y: 260 },
      ],
    },
    {
      code: '40',
      name: 'Landes',
      path: 'M 30,295 Q 80,280 130,300 Q 165,330 170,380 Q 160,435 115,465 Q 60,485 20,455 Q -5,415 5,360 Q 15,315 30,295 Z',
      labelPosition: { x: 90, y: 380 },
      cities: [{ name: 'Mont-de-Marsan', x: 115, y: 365, isCapital: true }],
    },
    {
      code: '64',
      name: 'Pyrénées-Atlantiques',
      path: 'M 25,455 Q 75,440 125,460 Q 165,485 175,530 Q 165,575 120,595 Q 65,605 25,575 Q -5,545 5,500 Q 15,465 25,455 Z',
      labelPosition: { x: 95, y: 520 },
      cities: [
        { name: 'Pau', x: 125, y: 510, isCapital: true },
        { name: 'Bayonne', x: 45, y: 530 },
      ],
    },
    {
      code: '24',
      name: 'Dordogne',
      path: 'M 140,130 Q 200,115 255,135 Q 300,160 310,210 Q 305,265 265,300 Q 215,325 160,310 Q 115,290 110,240 Q 115,185 140,145 Z',
      labelPosition: { x: 210, y: 220 },
      cities: [{ name: 'Périgueux', x: 210, y: 200, isCapital: true }],
    },
    {
      code: '47',
      name: 'Lot-et-Garonne',
      path: 'M 165,305 Q 220,290 270,310 Q 310,340 315,390 Q 305,440 260,465 Q 205,480 155,455 Q 120,425 125,375 Q 135,325 165,305 Z',
      labelPosition: { x: 220, y: 385 },
      cities: [{ name: 'Agen', x: 215, y: 395, isCapital: true }],
    },
    {
      code: '17',
      name: 'Charente-Maritime',
      path: 'M 45,40 Q 100,25 150,45 Q 190,70 195,120 Q 185,170 145,195 Q 95,210 50,190 Q 15,165 15,115 Q 20,65 45,40 Z',
      labelPosition: { x: 110, y: 120 },
      cities: [{ name: 'La Rochelle', x: 70, y: 95, isCapital: true }],
    },
    {
      code: '16',
      name: 'Charente',
      path: 'M 150,45 Q 210,30 265,55 Q 305,85 310,140 Q 300,195 255,220 Q 200,238 150,215 Q 115,185 115,135 Q 125,80 150,50 Z',
      labelPosition: { x: 215, y: 135 },
      cities: [{ name: 'Angoulême', x: 225, y: 145, isCapital: true }],
    },
    {
      code: '86',
      name: 'Vienne',
      path: 'M 215,20 Q 275,5 330,30 Q 375,60 380,115 Q 370,170 325,200 Q 270,222 215,200 Q 175,175 175,125 Q 182,70 215,30 Z',
      labelPosition: { x: 280, y: 115 },
      cities: [{ name: 'Poitiers', x: 295, y: 100, isCapital: true }],
    },
  ],
};

// Centre-Val de Loire (6 departments)
const CENTRE_VAL_DE_LOIRE: RegionMapData = {
  viewBox: '0 0 450 400',
  departments: [
    {
      code: '45',
      name: 'Loiret',
      path: 'M 200,30 Q 270,20 330,45 Q 380,80 390,140 Q 385,200 340,240 Q 280,270 215,255 Q 160,235 150,180 Q 150,115 185,65 Z',
      labelPosition: { x: 270, y: 145 },
      cities: [{ name: 'Orléans', x: 240, y: 130, isCapital: true }],
    },
    {
      code: '28',
      name: 'Eure-et-Loir',
      path: 'M 80,25 Q 145,15 200,35 Q 240,60 245,110 Q 235,160 190,185 Q 135,200 85,180 Q 45,155 40,105 Q 45,55 80,30 Z',
      labelPosition: { x: 145, y: 110 },
      cities: [{ name: 'Chartres', x: 145, y: 100, isCapital: true }],
    },
    {
      code: '41',
      name: 'Loir-et-Cher',
      path: 'M 60,180 Q 120,165 175,185 Q 220,210 225,265 Q 215,320 165,350 Q 105,370 55,345 Q 20,310 25,255 Q 35,200 60,180 Z',
      labelPosition: { x: 125, y: 265 },
      cities: [{ name: 'Blois', x: 140, y: 245, isCapital: true }],
    },
    {
      code: '37',
      name: 'Indre-et-Loire',
      path: 'M 15,270 Q 65,250 115,270 Q 155,295 160,350 Q 150,405 100,430 Q 45,445 10,415 Q -15,380 -5,325 Q 5,285 15,270 Z',
      labelPosition: { x: 80, y: 350 },
      cities: [{ name: 'Tours', x: 95, y: 340, isCapital: true }],
    },
    {
      code: '36',
      name: 'Indre',
      path: 'M 115,350 Q 175,335 230,360 Q 275,390 280,445 Q 270,500 220,525 Q 160,540 110,515 Q 70,485 75,430 Q 85,375 115,355 Z',
      labelPosition: { x: 180, y: 440 },
      cities: [{ name: 'Châteauroux', x: 185, y: 430, isCapital: true }],
    },
    {
      code: '18',
      name: 'Cher',
      path: 'M 230,240 Q 300,225 360,255 Q 410,295 415,360 Q 400,425 345,460 Q 280,485 220,460 Q 175,425 180,365 Q 195,300 230,250 Z',
      labelPosition: { x: 300, y: 355 },
      cities: [{ name: 'Bourges', x: 300, y: 345, isCapital: true }],
    },
  ],
};

// Normandie (5 departments)
const NORMANDIE: RegionMapData = {
  viewBox: '0 0 450 300',
  departments: [
    {
      code: '76',
      name: 'Seine-Maritime',
      path: 'M 230,20 Q 300,10 360,35 Q 410,70 420,130 Q 415,185 370,215 Q 315,240 255,225 Q 205,205 200,150 Q 200,90 230,45 Z',
      labelPosition: { x: 315, y: 125 },
      cities: [
        { name: 'Rouen', x: 305, y: 140, isCapital: true },
        { name: 'Le Havre', x: 255, y: 85 },
      ],
    },
    {
      code: '27',
      name: 'Eure',
      path: 'M 200,150 Q 255,135 305,155 Q 345,180 350,235 Q 340,285 290,305 Q 235,320 185,295 Q 150,265 155,215 Q 165,170 200,150 Z',
      labelPosition: { x: 255, y: 230 },
      cities: [{ name: 'Évreux', x: 240, y: 215, isCapital: true }],
    },
    {
      code: '14',
      name: 'Calvados',
      path: 'M 60,100 Q 125,85 185,105 Q 230,130 235,185 Q 225,240 175,265 Q 115,280 60,255 Q 20,225 20,170 Q 30,120 60,100 Z',
      labelPosition: { x: 135, y: 180 },
      cities: [
        { name: 'Caen', x: 135, y: 165, isCapital: true },
        { name: 'Deauville', x: 175, y: 145 },
      ],
    },
    {
      code: '50',
      name: 'Manche',
      path: 'M 10,60 Q 55,40 100,55 Q 135,80 140,130 Q 130,185 85,215 Q 35,235 0,205 Q -25,170 -15,115 Q -5,75 10,60 Z',
      labelPosition: { x: 60, y: 140 },
      cities: [
        { name: 'Saint-Lô', x: 75, y: 145, isCapital: true },
        { name: 'Cherbourg', x: 40, y: 70 },
      ],
    },
    {
      code: '61',
      name: 'Orne',
      path: 'M 85,210 Q 145,195 200,220 Q 245,250 250,305 Q 240,360 190,385 Q 130,400 80,375 Q 45,345 50,290 Q 60,235 85,215 Z',
      labelPosition: { x: 155, y: 295 },
      cities: [{ name: 'Alençon', x: 175, y: 305, isCapital: true }],
    },
  ],
};

// Hauts-de-France (5 departments)
const HAUTS_DE_FRANCE: RegionMapData = {
  viewBox: '0 0 400 400',
  departments: [
    {
      code: '59',
      name: 'Nord',
      path: 'M 120,15 Q 195,5 260,30 Q 315,65 325,130 Q 320,195 270,235 Q 205,265 140,250 Q 85,225 80,165 Q 85,100 115,50 Z',
      labelPosition: { x: 205, y: 135 },
      cities: [
        { name: 'Lille', x: 205, y: 115, isCapital: true },
        { name: 'Dunkerque', x: 185, y: 45 },
        { name: 'Valenciennes', x: 260, y: 175 },
      ],
    },
    {
      code: '62',
      name: 'Pas-de-Calais',
      path: 'M 20,50 Q 75,30 130,50 Q 170,80 175,140 Q 165,200 120,230 Q 65,250 25,220 Q -10,185 0,130 Q 10,75 20,55 Z',
      labelPosition: { x: 95, y: 145 },
      cities: [
        { name: 'Arras', x: 110, y: 170, isCapital: true },
        { name: 'Calais', x: 70, y: 55 },
        { name: 'Boulogne', x: 35, y: 95 },
      ],
    },
    {
      code: '80',
      name: 'Somme',
      path: 'M 25,220 Q 85,200 145,225 Q 195,255 200,315 Q 190,370 140,395 Q 80,410 35,380 Q 5,345 10,290 Q 15,240 25,220 Z',
      labelPosition: { x: 110, y: 305 },
      cities: [{ name: 'Amiens', x: 120, y: 290, isCapital: true }],
    },
    {
      code: '60',
      name: 'Oise',
      path: 'M 145,300 Q 210,285 270,310 Q 320,345 325,405 Q 310,460 255,485 Q 190,500 140,470 Q 105,435 110,380 Q 120,325 145,305 Z',
      labelPosition: { x: 220, y: 390 },
      cities: [{ name: 'Beauvais', x: 200, y: 370, isCapital: true }],
    },
    {
      code: '02',
      name: 'Aisne',
      path: 'M 270,230 Q 335,215 390,245 Q 435,285 440,350 Q 425,415 370,450 Q 305,475 250,450 Q 210,415 215,355 Q 230,290 270,240 Z',
      labelPosition: { x: 335, y: 345 },
      cities: [{ name: 'Laon', x: 335, y: 320, isCapital: true }],
    },
  ],
};

// Pays de la Loire (5 departments)
const PAYS_DE_LA_LOIRE: RegionMapData = {
  viewBox: '0 0 450 400',
  departments: [
    {
      code: '44',
      name: 'Loire-Atlantique',
      path: 'M 30,130 Q 95,110 155,135 Q 200,165 205,225 Q 195,285 145,315 Q 85,335 35,305 Q 0,270 5,210 Q 15,155 30,130 Z',
      labelPosition: { x: 110, y: 220 },
      cities: [
        { name: 'Nantes', x: 125, y: 215, isCapital: true },
        { name: 'St-Nazaire', x: 55, y: 185 },
      ],
    },
    {
      code: '49',
      name: 'Maine-et-Loire',
      path: 'M 155,135 Q 225,120 290,150 Q 340,185 345,250 Q 335,315 280,350 Q 215,375 155,350 Q 110,315 115,255 Q 125,190 155,150 Z',
      labelPosition: { x: 230, y: 245 },
      cities: [{ name: 'Angers', x: 225, y: 230, isCapital: true }],
    },
    {
      code: '53',
      name: 'Mayenne',
      path: 'M 170,25 Q 235,15 295,45 Q 340,80 345,145 Q 335,210 280,245 Q 220,270 165,245 Q 125,210 130,150 Q 140,90 170,45 Z',
      labelPosition: { x: 240, y: 145 },
      cities: [{ name: 'Laval', x: 240, y: 135, isCapital: true }],
    },
    {
      code: '72',
      name: 'Sarthe',
      path: 'M 290,45 Q 360,30 420,65 Q 465,105 470,175 Q 460,245 405,285 Q 340,315 280,290 Q 240,255 245,195 Q 255,130 290,70 Z',
      labelPosition: { x: 365, y: 175 },
      cities: [{ name: 'Le Mans', x: 365, y: 165, isCapital: true }],
    },
    {
      code: '85',
      name: 'Vendée',
      path: 'M 25,290 Q 90,270 150,300 Q 195,335 200,400 Q 185,460 130,490 Q 65,510 20,475 Q -10,435 0,375 Q 10,315 25,295 Z',
      labelPosition: { x: 105, y: 390 },
      cities: [{ name: 'La Roche-sur-Yon', x: 105, y: 375, isCapital: true }],
    },
  ],
};

// Grand Est (10 departments)
const GRAND_EST: RegionMapData = {
  viewBox: '0 0 500 450',
  departments: [
    {
      code: '67',
      name: 'Bas-Rhin',
      path: 'M 380,50 Q 430,40 470,70 Q 500,105 500,160 Q 490,215 450,245 Q 400,270 355,250 Q 320,225 325,175 Q 335,115 365,75 Z',
      labelPosition: { x: 415, y: 155 },
      cities: [{ name: 'Strasbourg', x: 455, y: 165, isCapital: true }],
    },
    {
      code: '68',
      name: 'Haut-Rhin',
      path: 'M 360,245 Q 410,230 455,260 Q 490,295 490,355 Q 475,410 425,435 Q 370,455 325,430 Q 295,400 300,345 Q 315,285 355,255 Z',
      labelPosition: { x: 395, y: 345 },
      cities: [{ name: 'Colmar', x: 425, y: 325, isCapital: true }],
    },
    {
      code: '57',
      name: 'Moselle',
      path: 'M 260,25 Q 325,15 380,50 Q 420,90 420,155 Q 405,220 350,255 Q 285,280 225,255 Q 185,220 190,160 Q 205,95 250,50 Z',
      labelPosition: { x: 305, y: 145 },
      cities: [{ name: 'Metz', x: 320, y: 145, isCapital: true }],
    },
    {
      code: '54',
      name: 'Meurthe-et-Moselle',
      path: 'M 175,155 Q 230,140 280,165 Q 320,195 325,255 Q 315,315 265,345 Q 205,365 150,340 Q 115,305 120,250 Q 135,195 175,160 Z',
      labelPosition: { x: 225, y: 255 },
      cities: [{ name: 'Nancy', x: 225, y: 245, isCapital: true }],
    },
    {
      code: '88',
      name: 'Vosges',
      path: 'M 265,295 Q 320,280 370,310 Q 410,345 410,410 Q 395,470 340,495 Q 280,510 230,480 Q 200,445 205,390 Q 220,335 265,305 Z',
      labelPosition: { x: 310, y: 395 },
      cities: [{ name: 'Épinal', x: 315, y: 385, isCapital: true }],
    },
    {
      code: '55',
      name: 'Meuse',
      path: 'M 90,145 Q 145,130 195,155 Q 235,185 235,245 Q 220,305 170,335 Q 115,355 70,330 Q 40,295 45,240 Q 60,180 90,150 Z',
      labelPosition: { x: 145, y: 245 },
      cities: [{ name: 'Bar-le-Duc', x: 130, y: 275, isCapital: true }],
    },
    {
      code: '08',
      name: 'Ardennes',
      path: 'M 75,20 Q 135,10 185,40 Q 225,75 225,135 Q 210,195 160,225 Q 105,245 60,220 Q 30,185 35,130 Q 50,70 75,35 Z',
      labelPosition: { x: 135, y: 125 },
      cities: [{ name: 'Charleville', x: 140, y: 115, isCapital: true }],
    },
    {
      code: '51',
      name: 'Marne',
      path: 'M 10,180 Q 65,165 115,190 Q 155,220 155,280 Q 140,340 90,370 Q 35,390 0,360 Q -25,325 -15,270 Q 0,210 10,185 Z',
      labelPosition: { x: 70, y: 275 },
      cities: [
        { name: 'Châlons', x: 75, y: 270, isCapital: true },
        { name: 'Reims', x: 50, y: 215 },
      ],
    },
    {
      code: '52',
      name: 'Haute-Marne',
      path: 'M 115,330 Q 170,315 220,345 Q 260,380 260,440 Q 245,495 195,520 Q 140,535 100,505 Q 70,470 75,420 Q 90,365 115,340 Z',
      labelPosition: { x: 170, y: 425 },
      cities: [{ name: 'Chaumont', x: 180, y: 420, isCapital: true }],
    },
    {
      code: '10',
      name: 'Aube',
      path: 'M 15,345 Q 70,330 120,360 Q 160,395 160,455 Q 145,510 95,535 Q 45,550 10,520 Q -15,485 -5,430 Q 5,375 15,350 Z',
      labelPosition: { x: 80, y: 440 },
      cities: [{ name: 'Troyes', x: 85, y: 430, isCapital: true }],
    },
  ],
};

// Bourgogne-Franche-Comté (8 departments)
const BOURGOGNE_FRANCHE_COMTE: RegionMapData = {
  viewBox: '0 0 450 500',
  departments: [
    {
      code: '21',
      name: "Côte-d'Or",
      path: 'M 50,100 Q 115,85 175,115 Q 225,150 230,215 Q 220,280 165,315 Q 100,340 50,310 Q 15,275 20,215 Q 30,150 50,105 Z',
      labelPosition: { x: 130, y: 210 },
      cities: [{ name: 'Dijon', x: 155, y: 185, isCapital: true }],
    },
    {
      code: '89',
      name: 'Yonne',
      path: 'M 15,15 Q 75,5 130,35 Q 175,70 175,135 Q 160,200 105,230 Q 45,250 10,220 Q -15,185 -5,125 Q 5,65 15,30 Z',
      labelPosition: { x: 90, y: 125 },
      cities: [{ name: 'Auxerre', x: 95, y: 120, isCapital: true }],
    },
    {
      code: '58',
      name: 'Nièvre',
      path: 'M 5,205 Q 60,190 110,220 Q 150,255 150,315 Q 135,375 80,405 Q 25,425 -5,395 Q -25,360 -15,305 Q -5,250 5,215 Z',
      labelPosition: { x: 75, y: 310 },
      cities: [{ name: 'Nevers', x: 70, y: 350, isCapital: true }],
    },
    {
      code: '71',
      name: 'Saône-et-Loire',
      path: 'M 55,310 Q 120,295 180,325 Q 230,360 235,425 Q 220,490 165,520 Q 100,540 50,510 Q 20,475 25,420 Q 40,360 55,320 Z',
      labelPosition: { x: 140, y: 420 },
      cities: [{ name: 'Mâcon', x: 175, y: 475, isCapital: true }],
    },
    {
      code: '25',
      name: 'Doubs',
      path: 'M 280,190 Q 345,175 400,210 Q 445,250 450,320 Q 435,390 380,425 Q 315,450 260,420 Q 225,385 230,325 Q 245,260 280,205 Z',
      labelPosition: { x: 345, y: 310 },
      cities: [{ name: 'Besançon', x: 335, y: 295, isCapital: true }],
    },
    {
      code: '39',
      name: 'Jura',
      path: 'M 210,345 Q 265,330 315,360 Q 355,395 355,460 Q 340,520 285,550 Q 225,570 180,540 Q 150,505 155,450 Q 170,395 210,355 Z',
      labelPosition: { x: 260, y: 450 },
      cities: [{ name: 'Lons-le-Saunier', x: 265, y: 445, isCapital: true }],
    },
    {
      code: '70',
      name: 'Haute-Saône',
      path: 'M 215,95 Q 275,80 330,115 Q 375,155 375,220 Q 360,285 305,315 Q 245,335 200,305 Q 170,270 175,215 Q 185,155 215,110 Z',
      labelPosition: { x: 280, y: 205 },
      cities: [{ name: 'Vesoul', x: 290, y: 190, isCapital: true }],
    },
    {
      code: '90',
      name: 'Territoire de Belfort',
      path: 'M 365,145 Q 400,135 430,160 Q 455,190 455,235 Q 445,280 415,300 Q 380,315 350,295 Q 330,265 335,220 Q 345,175 365,155 Z',
      labelPosition: { x: 395, y: 225 },
      cities: [{ name: 'Belfort', x: 400, y: 220, isCapital: true }],
    },
  ],
};

// Occitanie (13 departments)
const OCCITANIE: RegionMapData = {
  viewBox: '0 0 550 400',
  departments: [
    {
      code: '31',
      name: 'Haute-Garonne',
      path: 'M 120,150 Q 170,135 220,160 Q 260,190 265,250 Q 255,310 205,340 Q 150,360 105,335 Q 75,300 80,245 Q 90,190 120,160 Z',
      labelPosition: { x: 170, y: 250 },
      cities: [{ name: 'Toulouse', x: 175, y: 235, isCapital: true }],
    },
    {
      code: '34',
      name: 'Hérault',
      path: 'M 320,200 Q 375,185 425,215 Q 465,250 465,315 Q 450,375 395,400 Q 335,415 290,385 Q 260,350 265,295 Q 280,240 320,210 Z',
      labelPosition: { x: 370, y: 300 },
      cities: [
        { name: 'Montpellier', x: 400, y: 310, isCapital: true },
        { name: 'Béziers', x: 340, y: 335 },
      ],
    },
    {
      code: '30',
      name: 'Gard',
      path: 'M 425,145 Q 480,130 525,165 Q 555,205 555,270 Q 540,330 485,360 Q 425,380 380,350 Q 355,315 360,260 Q 375,200 420,160 Z',
      labelPosition: { x: 465, y: 255 },
      cities: [{ name: 'Nîmes', x: 490, y: 280, isCapital: true }],
    },
    {
      code: '11',
      name: 'Aude',
      path: 'M 225,275 Q 280,260 330,290 Q 370,325 370,385 Q 355,440 300,465 Q 240,480 195,450 Q 170,415 175,360 Q 190,305 225,280 Z',
      labelPosition: { x: 275, y: 370 },
      cities: [{ name: 'Carcassonne', x: 275, y: 360, isCapital: true }],
    },
    {
      code: '66',
      name: 'Pyrénées-Orientales',
      path: 'M 270,380 Q 325,365 375,395 Q 415,430 415,490 Q 400,545 345,570 Q 285,585 240,555 Q 215,520 220,465 Q 235,410 270,385 Z',
      labelPosition: { x: 325, y: 475 },
      cities: [{ name: 'Perpignan', x: 350, y: 460, isCapital: true }],
    },
    {
      code: '09',
      name: 'Ariège',
      path: 'M 115,330 Q 165,315 215,345 Q 255,380 255,440 Q 240,495 185,520 Q 125,535 85,505 Q 60,470 65,420 Q 80,365 115,340 Z',
      labelPosition: { x: 165, y: 430 },
      cities: [{ name: 'Foix', x: 170, y: 425, isCapital: true }],
    },
    {
      code: '65',
      name: 'Hautes-Pyrénées',
      path: 'M 30,280 Q 80,265 130,295 Q 170,330 170,390 Q 155,445 100,470 Q 45,485 15,455 Q -5,420 0,370 Q 15,315 30,290 Z',
      labelPosition: { x: 90, y: 375 },
      cities: [{ name: 'Tarbes', x: 95, y: 365, isCapital: true }],
    },
    {
      code: '32',
      name: 'Gers',
      path: 'M 40,135 Q 95,120 145,150 Q 185,185 185,245 Q 170,300 115,330 Q 55,350 20,320 Q -5,285 0,230 Q 15,175 40,145 Z',
      labelPosition: { x: 95, y: 235 },
      cities: [{ name: 'Auch', x: 100, y: 225, isCapital: true }],
    },
    {
      code: '81',
      name: 'Tarn',
      path: 'M 215,130 Q 270,115 325,145 Q 365,180 365,245 Q 350,305 295,335 Q 235,355 190,325 Q 165,290 170,235 Q 185,175 215,145 Z',
      labelPosition: { x: 270, y: 235 },
      cities: [{ name: 'Albi', x: 295, y: 205, isCapital: true }],
    },
    {
      code: '82',
      name: 'Tarn-et-Garonne',
      path: 'M 125,65 Q 175,50 225,80 Q 265,115 265,175 Q 250,230 195,260 Q 135,280 95,250 Q 70,215 75,160 Q 90,105 125,75 Z',
      labelPosition: { x: 175, y: 165 },
      cities: [{ name: 'Montauban', x: 180, y: 155, isCapital: true }],
    },
    {
      code: '12',
      name: 'Aveyron',
      path: 'M 280,45 Q 345,30 405,65 Q 450,105 455,175 Q 440,245 385,280 Q 320,305 265,275 Q 230,240 235,180 Q 250,115 280,65 Z',
      labelPosition: { x: 350, y: 165 },
      cities: [{ name: 'Rodez', x: 355, y: 155, isCapital: true }],
    },
    {
      code: '46',
      name: 'Lot',
      path: 'M 180,5 Q 240,-5 295,25 Q 340,60 345,125 Q 330,190 275,220 Q 215,240 170,210 Q 140,175 145,120 Q 160,60 180,20 Z',
      labelPosition: { x: 250, y: 120 },
      cities: [{ name: 'Cahors', x: 250, y: 145, isCapital: true }],
    },
    {
      code: '48',
      name: 'Lozère',
      path: 'M 390,65 Q 445,50 490,85 Q 525,125 525,190 Q 510,250 455,280 Q 395,300 350,270 Q 325,235 330,180 Q 345,120 385,80 Z',
      labelPosition: { x: 435, y: 175 },
      cities: [{ name: 'Mende', x: 440, y: 170, isCapital: true }],
    },
  ],
};

// Auvergne-Rhône-Alpes (12 departments)
const AUVERGNE_RHONE_ALPES: RegionMapData = {
  viewBox: '0 0 500 500',
  departments: [
    {
      code: '69',
      name: 'Rhône',
      path: 'M 145,175 Q 195,160 245,190 Q 285,225 285,290 Q 270,350 215,380 Q 155,400 115,370 Q 90,335 95,280 Q 110,220 145,185 Z',
      labelPosition: { x: 195, y: 280 },
      cities: [{ name: 'Lyon', x: 200, y: 305, isCapital: true }],
    },
    {
      code: '42',
      name: 'Loire',
      path: 'M 55,200 Q 110,185 160,215 Q 200,250 200,315 Q 185,375 130,405 Q 70,425 35,395 Q 15,360 20,305 Q 35,250 55,210 Z',
      labelPosition: { x: 115, y: 305 },
      cities: [{ name: 'St-Étienne', x: 105, y: 340, isCapital: true }],
    },
    {
      code: '63',
      name: 'Puy-de-Dôme',
      path: 'M 15,145 Q 70,130 125,160 Q 170,195 170,260 Q 155,320 100,350 Q 40,370 5,340 Q -20,305 -10,250 Q 0,195 15,155 Z',
      labelPosition: { x: 85, y: 250 },
      cities: [{ name: 'Clermont-Fd', x: 90, y: 240, isCapital: true }],
    },
    {
      code: '03',
      name: 'Allier',
      path: 'M 45,40 Q 110,25 170,60 Q 220,100 220,170 Q 200,235 145,265 Q 80,285 35,255 Q 5,220 10,160 Q 25,100 45,55 Z',
      labelPosition: { x: 125, y: 155 },
      cities: [{ name: 'Moulins', x: 135, y: 120, isCapital: true }],
    },
    {
      code: '43',
      name: 'Haute-Loire',
      path: 'M 60,365 Q 115,350 165,380 Q 205,415 205,475 Q 190,530 135,555 Q 75,570 40,540 Q 20,505 25,455 Q 40,405 60,370 Z',
      labelPosition: { x: 120, y: 460 },
      cities: [{ name: 'Le Puy', x: 130, y: 445, isCapital: true }],
    },
    {
      code: '15',
      name: 'Cantal',
      path: 'M 5,305 Q 60,290 110,320 Q 150,355 150,415 Q 135,470 80,495 Q 25,510 -5,480 Q -25,445 -15,395 Q -5,345 5,315 Z',
      labelPosition: { x: 70, y: 400 },
      cities: [{ name: 'Aurillac', x: 60, y: 420, isCapital: true }],
    },
    {
      code: '01',
      name: 'Ain',
      path: 'M 200,105 Q 260,90 315,125 Q 360,165 360,235 Q 345,300 290,335 Q 225,360 180,330 Q 155,295 160,240 Q 175,175 200,125 Z',
      labelPosition: { x: 265, y: 220 },
      cities: [{ name: 'Bourg-en-B.', x: 270, y: 210, isCapital: true }],
    },
    {
      code: '74',
      name: 'Haute-Savoie',
      path: 'M 330,75 Q 385,60 435,95 Q 475,135 475,200 Q 460,260 405,290 Q 345,310 305,280 Q 280,245 285,195 Q 300,140 330,95 Z',
      labelPosition: { x: 385, y: 185 },
      cities: [
        { name: 'Annecy', x: 380, y: 210, isCapital: true },
        { name: 'Chamonix', x: 435, y: 160 },
      ],
    },
    {
      code: '73',
      name: 'Savoie',
      path: 'M 305,280 Q 365,265 420,300 Q 465,340 465,410 Q 450,475 395,505 Q 330,525 285,495 Q 260,460 265,405 Q 280,345 305,295 Z',
      labelPosition: { x: 370, y: 390 },
      cities: [{ name: 'Chambéry', x: 360, y: 365, isCapital: true }],
    },
    {
      code: '38',
      name: 'Isère',
      path: 'M 230,300 Q 295,285 355,320 Q 405,360 405,430 Q 385,495 325,525 Q 260,545 210,515 Q 180,480 185,425 Q 200,365 230,315 Z',
      labelPosition: { x: 305, y: 415 },
      cities: [{ name: 'Grenoble', x: 320, y: 420, isCapital: true }],
    },
    {
      code: '26',
      name: 'Drôme',
      path: 'M 155,415 Q 215,400 270,435 Q 315,475 315,545 Q 295,605 240,635 Q 175,655 130,625 Q 105,590 110,535 Q 125,475 155,430 Z',
      labelPosition: { x: 220, y: 530 },
      cities: [{ name: 'Valence', x: 190, y: 495, isCapital: true }],
    },
    {
      code: '07',
      name: 'Ardèche',
      path: 'M 100,455 Q 155,440 205,475 Q 245,515 245,585 Q 225,645 170,675 Q 110,695 70,665 Q 50,630 55,575 Q 70,515 100,470 Z',
      labelPosition: { x: 155, y: 570 },
      cities: [{ name: 'Privas', x: 170, y: 540, isCapital: true }],
    },
  ],
};

// Corse (2 departments)
const CORSE: RegionMapData = {
  viewBox: '0 0 200 350',
  departments: [
    {
      code: '2B',
      name: 'Haute-Corse',
      path: 'M 55,20 Q 100,10 140,35 Q 170,70 175,125 Q 165,180 125,210 Q 80,235 45,210 Q 20,180 25,125 Q 35,70 55,35 Z',
      labelPosition: { x: 100, y: 120 },
      cities: [
        { name: 'Bastia', x: 140, y: 75, isCapital: true },
        { name: 'Corte', x: 95, y: 155 },
      ],
    },
    {
      code: '2A',
      name: 'Corse-du-Sud',
      path: 'M 45,200 Q 95,185 140,215 Q 175,250 175,315 Q 160,375 110,405 Q 55,425 25,395 Q 5,360 10,305 Q 25,250 45,210 Z',
      labelPosition: { x: 100, y: 305 },
      cities: [
        { name: 'Ajaccio', x: 55, y: 290, isCapital: true },
        { name: 'Porto-Vecchio', x: 145, y: 355 },
      ],
    },
  ],
};

// Map of region codes to their map data
export const REGION_MAPS: Record<string, RegionMapData> = {
  '11': ILE_DE_FRANCE,
  '24': CENTRE_VAL_DE_LOIRE,
  '27': BOURGOGNE_FRANCHE_COMTE,
  '28': NORMANDIE,
  '32': HAUTS_DE_FRANCE,
  '44': GRAND_EST,
  '52': PAYS_DE_LA_LOIRE,
  '53': BRETAGNE,
  '75': NOUVELLE_AQUITAINE,
  '76': OCCITANIE,
  '84': AUVERGNE_RHONE_ALPES,
  '93': PROVENCE_ALPES_COTE_AZUR,
  '94': CORSE,
};

// Check if a region has a detailed map
export function hasRegionMap(regionCode: string): boolean {
  return regionCode in REGION_MAPS;
}

// Get map data for a region
export function getRegionMap(regionCode: string): RegionMapData | null {
  return REGION_MAPS[regionCode] || null;
}
