/**
 * Profile configuration constants
 * Extracted from ProfileView for reusability and maintainability
 */

// US States dropdown data
export const US_STATES = [
  { value: 'AL', label: 'Alabama' },
  { value: 'AK', label: 'Alaska' },
  { value: 'AZ', label: 'Arizona' },
  { value: 'AR', label: 'Arkansas' },
  { value: 'CA', label: 'California' },
  { value: 'CO', label: 'Colorado' },
  { value: 'CT', label: 'Connecticut' },
  { value: 'DE', label: 'Delaware' },
  { value: 'DC', label: 'District of Columbia' },
  { value: 'FL', label: 'Florida' },
  { value: 'GA', label: 'Georgia' },
  { value: 'HI', label: 'Hawaii' },
  { value: 'ID', label: 'Idaho' },
  { value: 'IL', label: 'Illinois' },
  { value: 'IN', label: 'Indiana' },
  { value: 'IA', label: 'Iowa' },
  { value: 'KS', label: 'Kansas' },
  { value: 'KY', label: 'Kentucky' },
  { value: 'LA', label: 'Louisiana' },
  { value: 'ME', label: 'Maine' },
  { value: 'MD', label: 'Maryland' },
  { value: 'MA', label: 'Massachusetts' },
  { value: 'MI', label: 'Michigan' },
  { value: 'MN', label: 'Minnesota' },
  { value: 'MS', label: 'Mississippi' },
  { value: 'MO', label: 'Missouri' },
  { value: 'MT', label: 'Montana' },
  { value: 'NE', label: 'Nebraska' },
  { value: 'NV', label: 'Nevada' },
  { value: 'NH', label: 'New Hampshire' },
  { value: 'NJ', label: 'New Jersey' },
  { value: 'NM', label: 'New Mexico' },
  { value: 'NY', label: 'New York' },
  { value: 'NC', label: 'North Carolina' },
  { value: 'ND', label: 'North Dakota' },
  { value: 'OH', label: 'Ohio' },
  { value: 'OK', label: 'Oklahoma' },
  { value: 'OR', label: 'Oregon' },
  { value: 'PA', label: 'Pennsylvania' },
  { value: 'PR', label: 'Puerto Rico' },
  { value: 'RI', label: 'Rhode Island' },
  { value: 'SC', label: 'South Carolina' },
  { value: 'SD', label: 'South Dakota' },
  { value: 'TN', label: 'Tennessee' },
  { value: 'TX', label: 'Texas' },
  { value: 'UT', label: 'Utah' },
  { value: 'VT', label: 'Vermont' },
  { value: 'VI', label: 'Virgin Islands' },
  { value: 'VA', label: 'Virginia' },
  { value: 'WA', label: 'Washington' },
  { value: 'WV', label: 'West Virginia' },
  { value: 'WI', label: 'Wisconsin' },
  { value: 'WY', label: 'Wyoming' },
] as const;

// Visa type options with descriptions
export const VISA_TYPES = [
  { value: 'undecided', label: 'Undecided', description: 'Still exploring visa options' },
  { value: 'visitor', label: 'Visitor visa', description: 'Long stay without working (VLS-TS visiteur)' },
  { value: 'talent_passport', label: 'Talent Passport', description: 'For highly skilled professionals' },
  { value: 'employee', label: 'Employee visa', description: 'Working for a French company' },
  { value: 'entrepreneur', label: 'Entrepreneur visa', description: 'Starting a business in France' },
  { value: 'student', label: 'Student visa', description: 'Studying at a French institution' },
  { value: 'family', label: 'Family reunification', description: 'Joining family members in France' },
  { value: 'spouse_french', label: 'Spouse of French citizen', description: 'Married to a French national' },
  { value: 'retiree', label: 'Visitor visa, retiring', description: 'France has no separate retiree visa: retirees use the visitor visa' },
  { value: 'other', label: 'Another long-stay route', description: 'Intern, temporary worker, ICT, au pair or another category' },
] as const;

export const TALENT_CATEGORY_OPTIONS = [
  { value: 'unsure', label: 'Not sure yet' },
  { value: 'qualified_employee', label: 'Qualified employee (salaried, at the reference salary)' },
  { value: 'blue_card', label: 'EU Blue Card (1.5 times the reference salary)' },
  { value: 'founder', label: 'Company founder / innovative project' },
  { value: 'investor', label: 'Investor' },
  { value: 'researcher', label: 'Researcher with a hosting agreement' },
  { value: 'artist', label: 'Artist or cultural professional' },
] as const;

export const RELATIONSHIP_TYPE_OPTIONS = [
  { value: 'married', label: 'Married' },
  { value: 'pacs', label: 'PACS (civil union)' },
] as const;

export const STUDY_LENGTH_OPTIONS = [
  { value: 'one_year', label: 'One year or less' },
  { value: 'multi_year', label: 'More than one year' },
] as const;

// Financial resource ranges
export const FINANCIAL_RANGES = [
  { value: 'under_50k', label: 'Under $50,000' },
  { value: '50k_100k', label: '$50,000 - $100,000' },
  { value: '100k_200k', label: '$100,000 - $200,000' },
  { value: '200k_500k', label: '$200,000 - $500,000' },
  { value: 'over_500k', label: 'Over $500,000' },
] as const;

// Work status options
export const WORK_STATUS_OPTIONS = [
  { value: 'employed', label: 'Employed' },
  { value: 'self_employed', label: 'Self-employed' },
  { value: 'retired', label: 'Retired' },
  { value: 'not_working', label: 'Not working' },
] as const;

// Applicant type options
export const APPLICANT_TYPE_OPTIONS = [
  { value: 'alone', label: 'Just me (alone)' },
  { value: 'spouse', label: 'Me and my spouse/partner' },
  { value: 'spouse_kids', label: 'Me, spouse/partner, and children' },
  { value: 'kids_only', label: 'Me and children (no spouse/partner)' },
] as const;

// Pet type options
export const PET_TYPE_OPTIONS = [
  { value: 'no', label: 'No pets' },
  { value: 'dogs', label: 'Dog(s)' },
  { value: 'cats', label: 'Cat(s)' },
  { value: 'both', label: 'Both dogs and cats' },
  { value: 'other', label: 'Other pets' },
] as const;

// Work in France options
export const WORK_IN_FRANCE_OPTIONS = [
  { value: 'undecided', label: 'Undecided' },
  { value: 'no', label: 'No, not planning to work' },
  { value: 'yes_local', label: 'Yes, for a French company' },
  { value: 'yes_remote', label: 'Yes, remote work for non-French company' },
  { value: 'yes_self', label: 'Yes, self-employed/freelance' },
] as const;

// Application location options
/** The French consulates general that take visa files in the US; matches FRAMT_Letters::CONSULATES. */
export const FRENCH_CONSULATES = [
  'Atlanta',
  'Boston',
  'Chicago',
  'Houston',
  'Los Angeles',
  'Miami',
  'New Orleans',
  'New York',
  'San Francisco',
  'Washington',
].map((city) => ({ value: city, label: `Consulate General of France in ${city}` }));

export const APPLICATION_LOCATION_OPTIONS = [
  { value: 'us', label: 'United States (French consulate)' },
  { value: 'france', label: 'France (Already in France)' },
] as const;
