/**
 * Research Tool Configuration
 *
 * Constants, region data, and configuration for the France Research Tool.
 * Data covers Metropolitan France only (13 regions, 96 departments).
 */

import type { FranceRegion, FranceDepartment, ClimateType } from '@/types';

// Climate type labels for display
export const CLIMATE_LABELS: Record<ClimateType, string> = {
  oceanic: 'Oceanic',
  continental: 'Continental',
  mediterranean: 'Mediterranean',
  mountain: 'Mountain',
  'semi-oceanic': 'Semi-Oceanic',
};

// Population formatter
export const formatPopulation = (pop: number): string => {
  if (pop >= 1000000) {
    return `${(pop / 1000000).toFixed(1)}M`;
  }
  if (pop >= 1000) {
    return `${(pop / 1000).toFixed(0)}K`;
  }
  return pop.toString();
};

// Area formatter
export const formatArea = (area: number): string => {
  return `${area.toLocaleString()} km²`;
};

/**
 * Metropolitan France Regions (13 regions)
 * Data from INSEE 2024
 */
export const FRANCE_REGIONS: FranceRegion[] = [
  {
    code: '84',
    name: 'Auvergne-Rhône-Alpes',
    capital: 'Lyon',
    population: 8078652,
    area_km2: 69711,
    departments: ['01', '03', '07', '15', '26', '38', '42', '43', '63', '69', '73', '74'],
    climate: 'continental',
    description: 'A diverse region featuring the French Alps, the Rhône Valley, and historic Lyon. Known for gastronomy, outdoor sports, and a strong economy.',
  },
  {
    code: '27',
    name: 'Bourgogne-Franche-Comté',
    capital: 'Dijon',
    population: 2805580,
    area_km2: 47784,
    departments: ['21', '25', '39', '58', '70', '71', '89', '90'],
    climate: 'continental',
    description: 'Famous for Burgundy wines, medieval heritage, and the Jura mountains. A region of rolling hills, historic towns, and rich culinary traditions.',
  },
  {
    code: '53',
    name: 'Bretagne',
    capital: 'Rennes',
    population: 3373835,
    area_km2: 27208,
    departments: ['22', '29', '35', '56'],
    climate: 'oceanic',
    description: 'A Celtic peninsula with dramatic coastlines, rich maritime heritage, and distinctive culture. Known for seafood, cider, and medieval villages.',
  },
  {
    code: '24',
    name: 'Centre-Val de Loire',
    capital: 'Orléans',
    population: 2576252,
    area_km2: 39151,
    departments: ['18', '28', '36', '37', '41', '45'],
    climate: 'semi-oceanic',
    description: 'The heart of France featuring magnificent Loire Valley châteaux, vineyards, and a gentle landscape. Rich in history and royal heritage.',
  },
  {
    code: '94',
    name: 'Corse',
    capital: 'Ajaccio',
    population: 344679,
    area_km2: 8680,
    departments: ['2A', '2B'],
    climate: 'mediterranean',
    description: 'The "Island of Beauty" with stunning mountains, beaches, and unique culture. A Mediterranean paradise with strong local traditions.',
  },
  {
    code: '44',
    name: 'Grand Est',
    capital: 'Strasbourg',
    population: 5561287,
    area_km2: 57433,
    departments: ['08', '10', '51', '52', '54', '55', '57', '67', '68', '88'],
    climate: 'continental',
    description: 'A region blending French and Germanic influences. Home to Strasbourg, Alsatian wine routes, and the Vosges mountains.',
  },
  {
    code: '32',
    name: 'Hauts-de-France',
    capital: 'Lille',
    population: 6009976,
    area_km2: 31813,
    departments: ['02', '59', '60', '62', '80'],
    climate: 'oceanic',
    description: 'Northern France with Flemish heritage, cathedral cities, and WWI memorial sites. A dynamic region close to Paris, Belgium, and the UK.',
  },
  {
    code: '11',
    name: 'Île-de-France',
    capital: 'Paris',
    population: 12271794,
    area_km2: 12012,
    departments: ['75', '77', '78', '91', '92', '93', '94', '95'],
    climate: 'semi-oceanic',
    description: 'The Paris region - the economic, cultural, and political heart of France. World-class museums, historic monuments, and cosmopolitan lifestyle.',
  },
  {
    code: '28',
    name: 'Normandie',
    capital: 'Rouen',
    population: 3327477,
    area_km2: 29907,
    departments: ['14', '27', '50', '61', '76'],
    climate: 'oceanic',
    description: 'Historic region of D-Day beaches, medieval abbeys, and apple orchards. Known for Camembert, cider, and impressionist landscapes.',
  },
  {
    code: '75',
    name: 'Nouvelle-Aquitaine',
    capital: 'Bordeaux',
    population: 6033952,
    area_km2: 84036,
    departments: ['16', '17', '19', '23', '24', '33', '40', '47', '64', '79', '86', '87'],
    climate: 'oceanic',
    description: 'The largest region in France, featuring Bordeaux wines, Atlantic beaches, the Dordogne, and the Basque Country. Excellent quality of life.',
  },
  {
    code: '76',
    name: 'Occitanie',
    capital: 'Toulouse',
    population: 5973969,
    area_km2: 72724,
    departments: ['09', '11', '12', '30', '31', '32', '34', '46', '48', '65', '66', '81', '82'],
    climate: 'mediterranean',
    description: 'Southern France from the Pyrenees to the Mediterranean. Toulouse aerospace, Cathar castles, beaches, and vibrant cities.',
  },
  {
    code: '52',
    name: 'Pays de la Loire',
    capital: 'Nantes',
    population: 3838614,
    area_km2: 32082,
    departments: ['44', '49', '53', '72', '85'],
    climate: 'oceanic',
    description: 'Atlantic coast region with dynamic Nantes, Loire Valley vineyards, and charming coastal towns. Growing tech hub with high quality of life.',
  },
  {
    code: '93',
    name: "Provence-Alpes-Côte d'Azur",
    capital: 'Marseille',
    population: 5081101,
    area_km2: 31400,
    departments: ['04', '05', '06', '13', '83', '84'],
    climate: 'mediterranean',
    description: 'The French Riviera, lavender fields of Provence, and Alpine peaks. Mediterranean lifestyle, historic cities, and world-famous coastline.',
  },
];

/**
 * Metropolitan France Departments (96 departments)
 * Organized by region
 */
export const FRANCE_DEPARTMENTS: FranceDepartment[] = [
  // Auvergne-Rhône-Alpes (84)
  { code: '01', name: 'Ain', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Bourg-en-Bresse', population: 656955, area_km2: 5762, major_cities: ['Bourg-en-Bresse', 'Oyonnax', 'Ambérieu-en-Bugey'] },
  { code: '03', name: 'Allier', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Moulins', population: 337171, area_km2: 7340, major_cities: ['Montluçon', 'Vichy', 'Moulins'] },
  { code: '07', name: 'Ardèche', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Privas', population: 328278, area_km2: 5529, major_cities: ['Annonay', 'Aubenas', 'Privas'] },
  { code: '15', name: 'Cantal', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Aurillac', population: 144692, area_km2: 5726, major_cities: ['Aurillac', 'Saint-Flour', 'Mauriac'] },
  { code: '26', name: 'Drôme', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Valence', population: 516762, area_km2: 6530, major_cities: ['Valence', 'Montélimar', 'Romans-sur-Isère'] },
  { code: '38', name: 'Isère', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Grenoble', population: 1271166, area_km2: 7431, major_cities: ['Grenoble', 'Saint-Martin-d\'Hères', 'Échirolles'] },
  { code: '42', name: 'Loire', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Saint-Étienne', population: 765634, area_km2: 4781, major_cities: ['Saint-Étienne', 'Roanne', 'Saint-Chamond'] },
  { code: '43', name: 'Haute-Loire', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Le Puy-en-Velay', population: 227339, area_km2: 4977, major_cities: ['Le Puy-en-Velay', 'Monistrol-sur-Loire', 'Yssingeaux'] },
  { code: '63', name: 'Puy-de-Dôme', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Clermont-Ferrand', population: 662152, area_km2: 7970, major_cities: ['Clermont-Ferrand', 'Cournon-d\'Auvergne', 'Riom'] },
  { code: '69', name: 'Rhône', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Lyon', population: 1876051, area_km2: 3249, major_cities: ['Lyon', 'Villeurbanne', 'Vénissieux'] },
  { code: '73', name: 'Savoie', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Chambéry', population: 440347, area_km2: 6028, major_cities: ['Chambéry', 'Aix-les-Bains', 'Albertville'] },
  { code: '74', name: 'Haute-Savoie', region_code: '84', region_name: 'Auvergne-Rhône-Alpes', prefecture: 'Annecy', population: 826105, area_km2: 4388, major_cities: ['Annecy', 'Thonon-les-Bains', 'Annemasse'] },

  // Bourgogne-Franche-Comté (27)
  { code: '21', name: "Côte-d'Or", region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Dijon', population: 534124, area_km2: 8763, major_cities: ['Dijon', 'Beaune', 'Chenôve'] },
  { code: '25', name: 'Doubs', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Besançon', population: 543974, area_km2: 5234, major_cities: ['Besançon', 'Montbéliard', 'Pontarlier'] },
  { code: '39', name: 'Jura', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Lons-le-Saunier', population: 260681, area_km2: 4999, major_cities: ['Lons-le-Saunier', 'Dole', 'Saint-Claude'] },
  { code: '58', name: 'Nièvre', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Nevers', population: 204452, area_km2: 6817, major_cities: ['Nevers', 'Cosne-Cours-sur-Loire', 'Varennes-Vauzelles'] },
  { code: '70', name: 'Haute-Saône', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Vesoul', population: 235313, area_km2: 5360, major_cities: ['Vesoul', 'Héricourt', 'Lure'] },
  { code: '71', name: 'Saône-et-Loire', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Mâcon', population: 551493, area_km2: 8575, major_cities: ['Chalon-sur-Saône', 'Mâcon', 'Le Creusot'] },
  { code: '89', name: 'Yonne', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Auxerre', population: 338291, area_km2: 7427, major_cities: ['Auxerre', 'Sens', 'Joigny'] },
  { code: '90', name: 'Territoire de Belfort', region_code: '27', region_name: 'Bourgogne-Franche-Comté', prefecture: 'Belfort', population: 137252, area_km2: 610, major_cities: ['Belfort', 'Delle', 'Valdoie'] },

  // Bretagne (53)
  { code: '22', name: "Côtes-d'Armor", region_code: '53', region_name: 'Bretagne', prefecture: 'Saint-Brieuc', population: 598953, area_km2: 6878, major_cities: ['Saint-Brieuc', 'Lannion', 'Dinan'] },
  { code: '29', name: 'Finistère', region_code: '53', region_name: 'Bretagne', prefecture: 'Quimper', population: 915090, area_km2: 6733, major_cities: ['Brest', 'Quimper', 'Concarneau'] },
  { code: '35', name: 'Ille-et-Vilaine', region_code: '53', region_name: 'Bretagne', prefecture: 'Rennes', population: 1082052, area_km2: 6775, major_cities: ['Rennes', 'Saint-Malo', 'Fougères'] },
  { code: '56', name: 'Morbihan', region_code: '53', region_name: 'Bretagne', prefecture: 'Vannes', population: 777740, area_km2: 6823, major_cities: ['Lorient', 'Vannes', 'Lanester'] },

  // Centre-Val de Loire (24)
  { code: '18', name: 'Cher', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Bourges', population: 302306, area_km2: 7235, major_cities: ['Bourges', 'Vierzon', 'Saint-Amand-Montrond'] },
  { code: '28', name: 'Eure-et-Loir', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Chartres', population: 433233, area_km2: 5880, major_cities: ['Chartres', 'Dreux', 'Lucé'] },
  { code: '36', name: 'Indre', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Châteauroux', population: 220530, area_km2: 6791, major_cities: ['Châteauroux', 'Issoudun', 'Déols'] },
  { code: '37', name: 'Indre-et-Loire', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Tours', population: 610079, area_km2: 6127, major_cities: ['Tours', 'Joué-lès-Tours', 'Saint-Cyr-sur-Loire'] },
  { code: '41', name: 'Loir-et-Cher', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Blois', population: 331915, area_km2: 6343, major_cities: ['Blois', 'Vendôme', 'Romorantin-Lanthenay'] },
  { code: '45', name: 'Loiret', region_code: '24', region_name: 'Centre-Val de Loire', prefecture: 'Orléans', population: 678189, area_km2: 6775, major_cities: ['Orléans', 'Olivet', 'Saint-Jean-de-Braye'] },

  // Corse (94)
  { code: '2A', name: 'Corse-du-Sud', region_code: '94', region_name: 'Corse', prefecture: 'Ajaccio', population: 159622, area_km2: 4014, major_cities: ['Ajaccio', 'Porto-Vecchio', 'Propriano'] },
  { code: '2B', name: 'Haute-Corse', region_code: '94', region_name: 'Corse', prefecture: 'Bastia', population: 185057, area_km2: 4666, major_cities: ['Bastia', 'Calvi', 'Corte'] },

  // Grand Est (44)
  { code: '08', name: 'Ardennes', region_code: '44', region_name: 'Grand Est', prefecture: 'Charleville-Mézières', population: 270582, area_km2: 5229, major_cities: ['Charleville-Mézières', 'Sedan', 'Rethel'] },
  { code: '10', name: 'Aube', region_code: '44', region_name: 'Grand Est', prefecture: 'Troyes', population: 310242, area_km2: 6004, major_cities: ['Troyes', 'Romilly-sur-Seine', 'La Chapelle-Saint-Luc'] },
  { code: '51', name: 'Marne', region_code: '44', region_name: 'Grand Est', prefecture: 'Châlons-en-Champagne', population: 566855, area_km2: 8162, major_cities: ['Reims', 'Châlons-en-Champagne', 'Épernay'] },
  { code: '52', name: 'Haute-Marne', region_code: '44', region_name: 'Grand Est', prefecture: 'Chaumont', population: 172512, area_km2: 6211, major_cities: ['Chaumont', 'Saint-Dizier', 'Langres'] },
  { code: '54', name: 'Meurthe-et-Moselle', region_code: '44', region_name: 'Grand Est', prefecture: 'Nancy', population: 733481, area_km2: 5246, major_cities: ['Nancy', 'Vandœuvre-lès-Nancy', 'Lunéville'] },
  { code: '55', name: 'Meuse', region_code: '44', region_name: 'Grand Est', prefecture: 'Bar-le-Duc', population: 184083, area_km2: 6211, major_cities: ['Bar-le-Duc', 'Verdun', 'Commercy'] },
  { code: '57', name: 'Moselle', region_code: '44', region_name: 'Grand Est', prefecture: 'Metz', population: 1043522, area_km2: 6216, major_cities: ['Metz', 'Thionville', 'Forbach'] },
  { code: '67', name: 'Bas-Rhin', region_code: '44', region_name: 'Grand Est', prefecture: 'Strasbourg', population: 1140939, area_km2: 4755, major_cities: ['Strasbourg', 'Haguenau', 'Schiltigheim'] },
  { code: '68', name: 'Haut-Rhin', region_code: '44', region_name: 'Grand Est', prefecture: 'Colmar', population: 764030, area_km2: 3525, major_cities: ['Mulhouse', 'Colmar', 'Saint-Louis'] },
  { code: '88', name: 'Vosges', region_code: '44', region_name: 'Grand Est', prefecture: 'Épinal', population: 367673, area_km2: 5874, major_cities: ['Épinal', 'Saint-Dié-des-Vosges', 'Gérardmer'] },

  // Hauts-de-France (32)
  { code: '02', name: 'Aisne', region_code: '32', region_name: 'Hauts-de-France', prefecture: 'Laon', population: 531345, area_km2: 7369, major_cities: ['Saint-Quentin', 'Soissons', 'Laon'] },
  { code: '59', name: 'Nord', region_code: '32', region_name: 'Hauts-de-France', prefecture: 'Lille', population: 2608346, area_km2: 5743, major_cities: ['Lille', 'Roubaix', 'Tourcoing'] },
  { code: '60', name: 'Oise', region_code: '32', region_name: 'Hauts-de-France', prefecture: 'Beauvais', population: 829419, area_km2: 5860, major_cities: ['Beauvais', 'Compiègne', 'Creil'] },
  { code: '62', name: 'Pas-de-Calais', region_code: '32', region_name: 'Hauts-de-France', prefecture: 'Arras', population: 1465278, area_km2: 6671, major_cities: ['Calais', 'Boulogne-sur-Mer', 'Arras'] },
  { code: '80', name: 'Somme', region_code: '32', region_name: 'Hauts-de-France', prefecture: 'Amiens', population: 571632, area_km2: 6170, major_cities: ['Amiens', 'Abbeville', 'Albert'] },

  // Île-de-France (11)
  { code: '75', name: 'Paris', region_code: '11', region_name: 'Île-de-France', prefecture: 'Paris', population: 2145906, area_km2: 105, major_cities: ['Paris'] },
  { code: '77', name: 'Seine-et-Marne', region_code: '11', region_name: 'Île-de-France', prefecture: 'Melun', population: 1421197, area_km2: 5915, major_cities: ['Meaux', 'Chelles', 'Melun'] },
  { code: '78', name: 'Yvelines', region_code: '11', region_name: 'Île-de-France', prefecture: 'Versailles', population: 1441398, area_km2: 2284, major_cities: ['Versailles', 'Sartrouville', 'Mantes-la-Jolie'] },
  { code: '91', name: 'Essonne', region_code: '11', region_name: 'Île-de-France', prefecture: 'Évry-Courcouronnes', population: 1301659, area_km2: 1804, major_cities: ['Évry-Courcouronnes', 'Corbeil-Essonnes', 'Massy'] },
  { code: '92', name: 'Hauts-de-Seine', region_code: '11', region_name: 'Île-de-France', prefecture: 'Nanterre', population: 1624357, area_km2: 176, major_cities: ['Boulogne-Billancourt', 'Nanterre', 'Courbevoie'] },
  { code: '93', name: 'Seine-Saint-Denis', region_code: '11', region_name: 'Île-de-France', prefecture: 'Bobigny', population: 1644903, area_km2: 236, major_cities: ['Montreuil', 'Saint-Denis', 'Aubervilliers'] },
  { code: '94', name: 'Val-de-Marne', region_code: '11', region_name: 'Île-de-France', prefecture: 'Créteil', population: 1407124, area_km2: 245, major_cities: ['Créteil', 'Vitry-sur-Seine', 'Saint-Maur-des-Fossés'] },
  { code: '95', name: "Val-d'Oise", region_code: '11', region_name: 'Île-de-France', prefecture: 'Cergy', population: 1249674, area_km2: 1246, major_cities: ['Argenteuil', 'Sarcelles', 'Cergy'] },

  // Normandie (28)
  { code: '14', name: 'Calvados', region_code: '28', region_name: 'Normandie', prefecture: 'Caen', population: 695536, area_km2: 5548, major_cities: ['Caen', 'Hérouville-Saint-Clair', 'Lisieux'] },
  { code: '27', name: 'Eure', region_code: '28', region_name: 'Normandie', prefecture: 'Évreux', population: 601843, area_km2: 6040, major_cities: ['Évreux', 'Vernon', 'Louviers'] },
  { code: '50', name: 'Manche', region_code: '28', region_name: 'Normandie', prefecture: 'Saint-Lô', population: 495045, area_km2: 5938, major_cities: ['Cherbourg-en-Cotentin', 'Saint-Lô', 'Granville'] },
  { code: '61', name: 'Orne', region_code: '28', region_name: 'Normandie', prefecture: 'Alençon', population: 279942, area_km2: 6103, major_cities: ['Alençon', 'Flers', 'Argentan'] },
  { code: '76', name: 'Seine-Maritime', region_code: '28', region_name: 'Normandie', prefecture: 'Rouen', population: 1254378, area_km2: 6278, major_cities: ['Le Havre', 'Rouen', 'Dieppe'] },

  // Nouvelle-Aquitaine (75)
  { code: '16', name: 'Charente', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Angoulême', population: 352015, area_km2: 5956, major_cities: ['Angoulême', 'Cognac', 'Soyaux'] },
  { code: '17', name: 'Charente-Maritime', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'La Rochelle', population: 651358, area_km2: 6864, major_cities: ['La Rochelle', 'Saintes', 'Rochefort'] },
  { code: '19', name: 'Corrèze', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Tulle', population: 240336, area_km2: 5857, major_cities: ['Brive-la-Gaillarde', 'Tulle', 'Ussel'] },
  { code: '23', name: 'Creuse', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Guéret', population: 116617, area_km2: 5565, major_cities: ['Guéret', 'La Souterraine', 'Aubusson'] },
  { code: '24', name: 'Dordogne', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Périgueux', population: 413223, area_km2: 9060, major_cities: ['Périgueux', 'Bergerac', 'Sarlat-la-Canéda'] },
  { code: '33', name: 'Gironde', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Bordeaux', population: 1623749, area_km2: 10000, major_cities: ['Bordeaux', 'Mérignac', 'Pessac'] },
  { code: '40', name: 'Landes', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Mont-de-Marsan', population: 413690, area_km2: 9243, major_cities: ['Mont-de-Marsan', 'Dax', 'Biscarrosse'] },
  { code: '47', name: 'Lot-et-Garonne', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Agen', population: 331271, area_km2: 5361, major_cities: ['Agen', 'Villeneuve-sur-Lot', 'Marmande'] },
  { code: '64', name: 'Pyrénées-Atlantiques', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Pau', population: 687240, area_km2: 7645, major_cities: ['Pau', 'Bayonne', 'Anglet'] },
  { code: '79', name: 'Deux-Sèvres', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Niort', population: 374351, area_km2: 5999, major_cities: ['Niort', 'Bressuire', 'Thouars'] },
  { code: '86', name: 'Vienne', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Poitiers', population: 438435, area_km2: 6990, major_cities: ['Poitiers', 'Châtellerault', 'Buxerolles'] },
  { code: '87', name: 'Haute-Vienne', region_code: '75', region_name: 'Nouvelle-Aquitaine', prefecture: 'Limoges', population: 374426, area_km2: 5520, major_cities: ['Limoges', 'Saint-Junien', 'Panazol'] },

  // Occitanie (76)
  { code: '09', name: 'Ariège', region_code: '76', region_name: 'Occitanie', prefecture: 'Foix', population: 153287, area_km2: 4890, major_cities: ['Pamiers', 'Foix', 'Saint-Girons'] },
  { code: '11', name: 'Aude', region_code: '76', region_name: 'Occitanie', prefecture: 'Carcassonne', population: 374070, area_km2: 6139, major_cities: ['Narbonne', 'Carcassonne', 'Castelnaudary'] },
  { code: '12', name: 'Aveyron', region_code: '76', region_name: 'Occitanie', prefecture: 'Rodez', population: 279595, area_km2: 8735, major_cities: ['Rodez', 'Millau', 'Villefranche-de-Rouergue'] },
  { code: '30', name: 'Gard', region_code: '76', region_name: 'Occitanie', prefecture: 'Nîmes', population: 748437, area_km2: 5853, major_cities: ['Nîmes', 'Alès', 'Bagnols-sur-Cèze'] },
  { code: '31', name: 'Haute-Garonne', region_code: '76', region_name: 'Occitanie', prefecture: 'Toulouse', population: 1415757, area_km2: 6309, major_cities: ['Toulouse', 'Colomiers', 'Tournefeuille'] },
  { code: '32', name: 'Gers', region_code: '76', region_name: 'Occitanie', prefecture: 'Auch', population: 191377, area_km2: 6257, major_cities: ['Auch', 'Condom', 'Fleurance'] },
  { code: '34', name: 'Hérault', region_code: '76', region_name: 'Occitanie', prefecture: 'Montpellier', population: 1176145, area_km2: 6101, major_cities: ['Montpellier', 'Béziers', 'Sète'] },
  { code: '46', name: 'Lot', region_code: '76', region_name: 'Occitanie', prefecture: 'Cahors', population: 174094, area_km2: 5217, major_cities: ['Cahors', 'Figeac', 'Gourdon'] },
  { code: '48', name: 'Lozère', region_code: '76', region_name: 'Occitanie', prefecture: 'Mende', population: 76601, area_km2: 5167, major_cities: ['Mende', 'Marvejols', 'Saint-Chély-d\'Apcher'] },
  { code: '65', name: 'Hautes-Pyrénées', region_code: '76', region_name: 'Occitanie', prefecture: 'Tarbes', population: 229567, area_km2: 4464, major_cities: ['Tarbes', 'Lourdes', 'Bagnères-de-Bigorre'] },
  { code: '66', name: 'Pyrénées-Orientales', region_code: '76', region_name: 'Occitanie', prefecture: 'Perpignan', population: 479979, area_km2: 4116, major_cities: ['Perpignan', 'Canet-en-Roussillon', 'Saint-Estève'] },
  { code: '81', name: 'Tarn', region_code: '76', region_name: 'Occitanie', prefecture: 'Albi', population: 389844, area_km2: 5758, major_cities: ['Albi', 'Castres', 'Gaillac'] },
  { code: '82', name: 'Tarn-et-Garonne', region_code: '76', region_name: 'Occitanie', prefecture: 'Montauban', population: 262618, area_km2: 3718, major_cities: ['Montauban', 'Castelsarrasin', 'Moissac'] },

  // Pays de la Loire (52)
  { code: '44', name: 'Loire-Atlantique', region_code: '52', region_name: 'Pays de la Loire', prefecture: 'Nantes', population: 1429272, area_km2: 6815, major_cities: ['Nantes', 'Saint-Nazaire', 'Saint-Herblain'] },
  { code: '49', name: 'Maine-et-Loire', region_code: '52', region_name: 'Pays de la Loire', prefecture: 'Angers', population: 818273, area_km2: 7107, major_cities: ['Angers', 'Cholet', 'Saumur'] },
  { code: '53', name: 'Mayenne', region_code: '52', region_name: 'Pays de la Loire', prefecture: 'Laval', population: 307445, area_km2: 5175, major_cities: ['Laval', 'Mayenne', 'Château-Gontier-sur-Mayenne'] },
  { code: '72', name: 'Sarthe', region_code: '52', region_name: 'Pays de la Loire', prefecture: 'Le Mans', population: 566506, area_km2: 6206, major_cities: ['Le Mans', 'La Flèche', 'Sablé-sur-Sarthe'] },
  { code: '85', name: 'Vendée', region_code: '52', region_name: 'Pays de la Loire', prefecture: 'La Roche-sur-Yon', population: 698118, area_km2: 6720, major_cities: ['La Roche-sur-Yon', 'Les Sables-d\'Olonne', 'Challans'] },

  // Provence-Alpes-Côte d'Azur (93)
  { code: '04', name: 'Alpes-de-Haute-Provence', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Digne-les-Bains', population: 164308, area_km2: 6925, major_cities: ['Manosque', 'Digne-les-Bains', 'Sisteron'] },
  { code: '05', name: 'Hautes-Alpes', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Gap', population: 141756, area_km2: 5549, major_cities: ['Gap', 'Briançon', 'Embrun'] },
  { code: '06', name: 'Alpes-Maritimes', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Nice', population: 1083310, area_km2: 4299, major_cities: ['Nice', 'Antibes', 'Cannes'] },
  { code: '13', name: 'Bouches-du-Rhône', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Marseille', population: 2043110, area_km2: 5087, major_cities: ['Marseille', 'Aix-en-Provence', 'Arles'] },
  { code: '83', name: 'Var', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Toulon', population: 1076711, area_km2: 5973, major_cities: ['Toulon', 'Fréjus', 'Hyères'] },
  { code: '84', name: 'Vaucluse', region_code: '93', region_name: "Provence-Alpes-Côte d'Azur", prefecture: 'Avignon', population: 559479, area_km2: 3567, major_cities: ['Avignon', 'Orange', 'Carpentras'] },
];

// Helper to get region by code
export const getRegionByCode = (code: string): FranceRegion | undefined => {
  return FRANCE_REGIONS.find(r => r.code === code);
};

// Helper to get department by code
export const getDepartmentByCode = (code: string): FranceDepartment | undefined => {
  return FRANCE_DEPARTMENTS.find(d => d.code === code);
};

// Helper to get departments by region code
export const getDepartmentsByRegion = (regionCode: string): FranceDepartment[] => {
  return FRANCE_DEPARTMENTS.filter(d => d.region_code === regionCode);
};

// Report sections configuration
export const REPORT_SECTIONS = {
  region: [
    { id: 'overview', title: 'Overview & Geography' },
    { id: 'history', title: 'Brief History' },
    { id: 'climate', title: 'Climate & Weather' },
    { id: 'cities', title: 'Major Cities & Towns' },
    { id: 'economy', title: 'Economy & Industries' },
    { id: 'cost_of_living', title: 'Cost of Living' },
    { id: 'healthcare', title: 'Healthcare Facilities' },
    { id: 'transport', title: 'Transportation' },
    { id: 'culture', title: 'Culture & Lifestyle' },
    { id: 'expats', title: 'Expat Community' },
    { id: 'pros_cons', title: 'Pros & Cons for Relocation' },
  ],
  department: [
    { id: 'overview', title: 'Overview' },
    { id: 'history', title: 'History & Heritage' },
    { id: 'geography', title: 'Geography & Climate' },
    { id: 'cities', title: 'Cities & Towns' },
    { id: 'housing', title: 'Housing Market' },
    { id: 'economy', title: 'Local Economy' },
    { id: 'services', title: 'Public Services' },
    { id: 'education', title: 'Schools & Education' },
    { id: 'transport', title: 'Getting Around' },
    { id: 'lifestyle', title: 'Lifestyle & Recreation' },
    { id: 'recommendations', title: 'Recommended Areas' },
  ],
  commune: [
    { id: 'overview', title: 'Overview' },
    { id: 'history', title: 'Local History' },
    { id: 'neighborhoods', title: 'Neighborhoods' },
    { id: 'housing', title: 'Housing & Property' },
    { id: 'amenities', title: 'Local Amenities' },
    { id: 'healthcare', title: 'Healthcare' },
    { id: 'education', title: 'Schools' },
    { id: 'transport', title: 'Transportation' },
    { id: 'administration', title: 'Administrative Guide' },
    { id: 'lifestyle', title: 'Daily Life' },
    { id: 'community', title: 'Expat & Community' },
    { id: 'practical', title: 'Practical Information' },
  ],
};
