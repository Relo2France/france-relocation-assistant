/**
 * What the knowledge base actually covers.
 *
 * Real counts from the live knowledge base as of September 2026. If these are
 * ever generated from the API rather than hand-kept, better - but a stale
 * number here is worse than none, so coverage.test.ts pins the total against
 * the parts.
 */
export interface CoverageArea {
  name: string;
  topics: number;
  examples: string;
}

export const coverage: CoverageArea[] = [
  { name: 'Visas', topics: 7, examples: 'Visitor, work, talent passport, spouse, validation' },
  { name: 'The application', topics: 7, examples: 'Documents, timelines, consulates, OFII validation' },
  { name: 'Property', topics: 5, examples: 'Buying, notaires, fees, mortgages' },
  { name: 'Healthcare', topics: 3, examples: 'PUMA, Carte Vitale, mutuelles' },
  { name: 'Tax', topics: 3, examples: 'Residency, treaties, filing' },
  { name: 'Driving', topics: 2, examples: 'Licence exchange, importing a car' },
  { name: 'Shipping & pets', topics: 2, examples: 'Movers, customs, animal health certificates' },
  { name: 'Banking', topics: 1, examples: 'Accounts, FATCA' },
  { name: 'Settling in', topics: 1, examples: 'Registration, utilities, schools' },
];

export const totalTopics = coverage.reduce((sum, area) => sum + area.topics, 0);
