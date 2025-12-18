/**
 * Report Templates Configuration
 *
 * Defines the structure for AI-generated relocation reports based on the
 * relo2france template format. Adapts sections based on location level
 * (region, department, commune).
 */

import type { ResearchLevel } from '@/types';

/**
 * Report section configuration
 */
export interface ReportSection {
  id: string;
  title: string;
  description: string;
  subsections?: {
    id: string;
    title: string;
    description: string;
  }[];
  /** Whether this section applies to the given location level */
  appliesTo: ResearchLevel[];
  /** Section order for display */
  order: number;
}

/**
 * Key statistics displayed in the report header
 */
export interface ReportKeyStats {
  area_km2: number;
  population: number;
  gdp_per_capita?: number;
  median_income?: number;
  life_expectancy?: number;
  density_per_km2?: number;
  unemployment_rate?: number;
  average_rent?: number;
}

/**
 * Report header configuration
 */
export interface ReportHeader {
  title: string;
  french_name?: string;
  tagline: string;
  key_stats: ReportKeyStats;
}

/**
 * Complete report structure
 */
export interface ReportTemplate {
  header: ReportHeader;
  sections: Record<string, {
    title: string;
    content: string;
    subsections?: Record<string, {
      title: string;
      content: string;
      items?: Array<{ label: string; value: string }>;
    }>;
    items?: Array<{ label: string; value: string }>;
  }>;
  footer: {
    data_sources: string[];
    generated_date: string;
    version: number;
  };
}

/**
 * Section definitions for all report types
 * Based on the relo2france France Overview template
 */
export const REPORT_SECTIONS: ReportSection[] = [
  {
    id: 'geography',
    title: 'Geography & Landscape',
    description: 'Physical description, terrain, borders, and notable geographic features',
    subsections: [
      { id: 'major_rivers', title: 'Major Rivers', description: 'Primary rivers and waterways' },
      { id: 'natural_features', title: 'Natural Features', description: 'Mountains, valleys, coastlines' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 1,
  },
  {
    id: 'climate',
    title: 'Climate',
    description: 'Climate zones, seasonal temperatures, and weather patterns',
    subsections: [
      { id: 'seasonal', title: 'Seasonal Overview', description: 'Temperature ranges by season' },
      { id: 'special_conditions', title: 'Special Conditions', description: 'Notable weather phenomena' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 2,
  },
  {
    id: 'demographics',
    title: 'Demographics & Population',
    description: 'Population statistics, urban areas, and major cities',
    subsections: [
      { id: 'population_density', title: 'Population Density', description: 'Population distribution' },
      { id: 'major_cities', title: 'Major Cities', description: 'Key urban centers' },
    ],
    appliesTo: ['region', 'department'],
    order: 3,
  },
  {
    id: 'economy',
    title: 'Economy',
    description: 'Local economy, key industries, and employment',
    subsections: [
      { id: 'key_indicators', title: 'Key Economic Indicators', description: 'GDP, unemployment, wages' },
      { id: 'key_industries', title: 'Key Industries', description: 'Major employers and sectors' },
      { id: 'job_market', title: 'Job Market', description: 'Employment opportunities' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 4,
  },
  {
    id: 'housing',
    title: 'Housing & Real Estate',
    description: 'Property market, rental prices, and neighborhoods',
    subsections: [
      { id: 'rental_market', title: 'Rental Market', description: 'Average rents by property type' },
      { id: 'buying_market', title: 'Buying Market', description: 'Property prices and trends' },
      { id: 'neighborhoods', title: 'Neighborhoods', description: 'Key areas and characteristics' },
    ],
    appliesTo: ['department', 'commune'],
    order: 5,
  },
  {
    id: 'language',
    title: 'Language & Communication',
    description: 'Official language, regional dialects, and English proficiency',
    appliesTo: ['region'],
    order: 6,
  },
  {
    id: 'food_wine',
    title: 'Food & Wine Culture',
    description: 'Regional cuisine, specialties, and wine',
    subsections: [
      { id: 'regional_specialties', title: 'Regional Specialties', description: 'Local dishes and products' },
      { id: 'wine_regions', title: 'Wine & Beverages', description: 'Local wines and drinks' },
      { id: 'markets', title: 'Markets & Shopping', description: 'Local markets and food shopping' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 7,
  },
  {
    id: 'culture_lifestyle',
    title: 'Culture & Lifestyle',
    description: 'Local culture, customs, and way of life',
    subsections: [
      { id: 'work_life_balance', title: 'Work-Life Balance', description: 'Work culture and hours' },
      { id: 'social_customs', title: 'Social Customs', description: 'Local etiquette and norms' },
      { id: 'expat_community', title: 'Expat Community', description: 'International community presence' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 8,
  },
  {
    id: 'subdivisions',
    title: 'Departments & Communes',
    description: 'Administrative subdivisions and key areas',
    appliesTo: ['region'],
    order: 9,
  },
  {
    id: 'quality_of_life',
    title: 'Quality of Life',
    description: 'Healthcare, education, infrastructure, and daily life considerations',
    subsections: [
      { id: 'healthcare', title: 'Healthcare', description: 'Hospitals, clinics, and medical services' },
      { id: 'education', title: 'Education', description: 'Schools, universities, international options' },
      { id: 'infrastructure', title: 'Infrastructure', description: 'Internet, utilities, services' },
      { id: 'considerations', title: 'Considerations', description: 'Practical aspects to consider' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 10,
  },
  {
    id: 'transportation',
    title: 'Transportation',
    description: 'Public transit, roads, and connectivity',
    subsections: [
      { id: 'public_transit', title: 'Public Transit', description: 'Buses, trams, metro' },
      { id: 'train', title: 'Train Services', description: 'TGV, regional trains' },
      { id: 'airports', title: 'Airports', description: 'Nearest airports and connections' },
      { id: 'driving', title: 'Driving', description: 'Roads, parking, car requirements' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 11,
  },
  {
    id: 'environment',
    title: 'Environment & Natural Heritage',
    description: 'Parks, natural areas, and outdoor activities',
    subsections: [
      { id: 'natural_highlights', title: 'Natural Highlights', description: 'Notable natural attractions' },
      { id: 'outdoor_activities', title: 'Outdoor Activities', description: 'Hiking, cycling, recreation' },
    ],
    appliesTo: ['region', 'department', 'commune'],
    order: 12,
  },
  {
    id: 'practical_info',
    title: 'Practical Information',
    description: 'Essential practical information for newcomers',
    subsections: [
      { id: 'administrative', title: 'Administrative Offices', description: 'Préfecture, mairie, services' },
      { id: 'banking', title: 'Banking', description: 'Banks and financial services' },
      { id: 'emergency', title: 'Emergency Services', description: 'Police, fire, medical emergency' },
    ],
    appliesTo: ['department', 'commune'],
    order: 13,
  },
];

/**
 * Get sections applicable to a specific location level
 */
export function getSectionsForLevel(level: ResearchLevel): ReportSection[] {
  return REPORT_SECTIONS
    .filter((section) => section.appliesTo.includes(level))
    .sort((a, b) => a.order - b.order);
}

/**
 * Key statistics to display based on location level
 */
export const KEY_STATS_BY_LEVEL: Record<ResearchLevel, (keyof ReportKeyStats)[]> = {
  region: ['area_km2', 'population', 'gdp_per_capita', 'life_expectancy'],
  department: ['area_km2', 'population', 'median_income', 'unemployment_rate'],
  commune: ['area_km2', 'population', 'density_per_km2', 'average_rent'],
};

/**
 * Get display label for a key stat
 */
export const KEY_STAT_LABELS: Record<keyof ReportKeyStats, { label: string; unit: string }> = {
  area_km2: { label: 'Area', unit: 'km²' },
  population: { label: 'Population', unit: '' },
  gdp_per_capita: { label: 'GDP per capita', unit: '€' },
  median_income: { label: 'Median Income', unit: '€/year' },
  life_expectancy: { label: 'Life Expectancy', unit: 'years' },
  density_per_km2: { label: 'Density', unit: '/km²' },
  unemployment_rate: { label: 'Unemployment', unit: '%' },
  average_rent: { label: 'Avg. Rent', unit: '€/month' },
};

/**
 * Format a statistic value for display
 */
export function formatStatValue(key: keyof ReportKeyStats, value: number): string {
  const config = KEY_STAT_LABELS[key];

  if (key === 'population' || key === 'area_km2') {
    return `${value.toLocaleString()} ${config.unit}`.trim();
  }

  if (key === 'gdp_per_capita' || key === 'median_income' || key === 'average_rent') {
    return `€${value.toLocaleString()}`;
  }

  if (key === 'life_expectancy') {
    return `${value.toFixed(1)} ${config.unit}`;
  }

  if (key === 'unemployment_rate') {
    return `${value.toFixed(1)}${config.unit}`;
  }

  if (key === 'density_per_km2') {
    return `${Math.round(value)}${config.unit}`;
  }

  return `${value} ${config.unit}`.trim();
}

/**
 * Report taglines by location level
 */
export const REPORT_TAGLINES: Record<ResearchLevel, string> = {
  region: 'A Comprehensive Guide for Those Considering Relocation',
  department: 'Your Guide to Living and Working in This Area',
  commune: 'Everything You Need to Know About This Community',
};

/**
 * Data source citations
 */
export const DATA_SOURCES = [
  'INSEE (Institut national de la statistique et des études économiques)',
  'Eurostat',
  'French government sources (service-public.fr)',
];
