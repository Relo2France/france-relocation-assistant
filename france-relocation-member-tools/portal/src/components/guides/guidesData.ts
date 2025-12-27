/**
 * Guides Data
 *
 * Static data for relocation guides including guide definitions,
 * categories, and helper functions.
 */

import {
  FileText,
  Home,
  CreditCard,
  Heart,
  Briefcase,
  GraduationCap,
  Car,
  Shield,
  Users,
} from 'lucide-react';

export interface Guide {
  id: string;
  title: string;
  description: string;
  category: string;
  readTime: string;
  difficulty: 'beginner' | 'intermediate' | 'advanced';
  featured?: boolean;
  icon: React.ElementType;
  sections: string[];
}

export const guides: Guide[] = [
  {
    id: 'visa-application',
    title: 'Complete Visa Application Guide',
    description: 'Step-by-step walkthrough of the French visa application process, from choosing the right visa type to attending your appointment.',
    category: 'Visa & Immigration',
    readTime: '25 min',
    difficulty: 'beginner',
    featured: true,
    icon: FileText,
    sections: [
      'Choosing the right visa type',
      'Required documents checklist',
      'Filling out the application form',
      'Booking your appointment',
      'What to expect at the consulate',
      'After approval: next steps',
    ],
  },
  {
    id: 'housing-france',
    title: 'Finding Housing in France',
    description: 'Navigate the French rental market with confidence. Learn about dossier requirements, guarantor options, and tenant rights.',
    category: 'Housing',
    readTime: '20 min',
    difficulty: 'intermediate',
    featured: true,
    icon: Home,
    sections: [
      'Understanding the rental market',
      'Building your dossier locatif',
      'Guarantor options (Visale, GLI)',
      'Viewing apartments',
      'Signing your lease',
      'Tenant rights and responsibilities',
    ],
  },
  {
    id: 'banking-finance',
    title: 'Banking & Finance Setup',
    description: 'Open a French bank account, understand the banking system, and manage your finances as an expat.',
    category: 'Finance',
    readTime: '15 min',
    difficulty: 'beginner',
    icon: CreditCard,
    sections: [
      'Choosing a bank (traditional vs online)',
      'Documents needed to open an account',
      'Understanding French banking fees',
      'Setting up automatic payments',
      'International transfers',
      'Tax considerations',
    ],
  },
  {
    id: 'healthcare-system',
    title: 'French Healthcare System',
    description: 'Understand how French healthcare works, register with CPAM, and choose complementary health insurance (mutuelle).',
    category: 'Healthcare',
    readTime: '18 min',
    difficulty: 'intermediate',
    featured: true,
    icon: Heart,
    sections: [
      'How French healthcare works',
      'Registering with CPAM',
      'Getting your Carte Vitale',
      'Choosing a mutuelle',
      'Finding doctors and specialists',
      'Pharmacy system',
    ],
  },
  {
    id: 'working-france',
    title: 'Working in France',
    description: 'Employment contracts, workplace culture, employee rights, and setting up as a freelancer or entrepreneur.',
    category: 'Employment',
    readTime: '22 min',
    difficulty: 'intermediate',
    icon: Briefcase,
    sections: [
      'Types of employment contracts',
      'Understanding your payslip',
      'Employee rights and benefits',
      'Setting up as auto-entrepreneur',
      'Workplace culture tips',
      'Networking and job hunting',
    ],
  },
  {
    id: 'education-schools',
    title: 'Education & Schools',
    description: 'Navigate the French education system for your children, from maternelle to university options.',
    category: 'Education',
    readTime: '16 min',
    difficulty: 'beginner',
    icon: GraduationCap,
    sections: [
      'French education system overview',
      'Public vs private schools',
      'International schools',
      'Enrolling your child',
      'After-school activities',
      'Higher education options',
    ],
  },
  {
    id: 'driving-transport',
    title: 'Driving & Transportation',
    description: 'Exchange your driving license, understand French driving rules, and navigate public transportation.',
    category: 'Transportation',
    readTime: '14 min',
    difficulty: 'beginner',
    icon: Car,
    sections: [
      'Exchanging your driving license',
      'Buying or leasing a car',
      'Car insurance requirements',
      'Public transportation options',
      'Getting a Navigo pass',
      'French driving rules',
    ],
  },
  {
    id: 'social-security',
    title: 'Social Security & Benefits',
    description: 'Register for French social security, understand your contributions, and access family benefits.',
    category: 'Administration',
    readTime: '20 min',
    difficulty: 'advanced',
    icon: Shield,
    sections: [
      'Social security system overview',
      'Registration process',
      'Understanding contributions',
      'Family benefits (CAF)',
      'Retirement system',
      'Unemployment benefits',
    ],
  },
  {
    id: 'family-relocation',
    title: 'Relocating with Family',
    description: 'Special considerations when moving to France with spouse and children, including visa requirements and settling in.',
    category: 'Family',
    readTime: '18 min',
    difficulty: 'intermediate',
    icon: Users,
    sections: [
      'Family visa requirements',
      'Spouse work authorization',
      'Childcare options',
      'Family healthcare coverage',
      'Activities and integration',
      'Support networks',
    ],
  },
];

export const categories = [
  { id: 'all', label: 'All Guides' },
  { id: 'Visa & Immigration', label: 'Visa & Immigration' },
  { id: 'Housing', label: 'Housing' },
  { id: 'Finance', label: 'Finance' },
  { id: 'Healthcare', label: 'Healthcare' },
  { id: 'Employment', label: 'Employment' },
  { id: 'Education', label: 'Education' },
  { id: 'Transportation', label: 'Transportation' },
  { id: 'Administration', label: 'Administration' },
  { id: 'Family', label: 'Family' },
];

export const difficultyColors = {
  beginner: 'bg-green-100 text-green-700',
  intermediate: 'bg-yellow-100 text-yellow-700',
  advanced: 'bg-red-100 text-red-700',
} as const;

/**
 * Get suggested questions for a specific guide
 */
export function getSuggestedQuestionsForGuide(guideId: string): string[] {
  const questionsByGuide: Record<string, string[]> = {
    'visa-application': [
      'What documents do I need for my visa application?',
      'How long does the visa process take?',
      'What are the financial requirements?',
      'Can I work while my visa is being processed?',
    ],
    'housing': [
      'What documents do renters need in France?',
      'How do I find a guarantor (garant)?',
      'What is a dossier de location?',
      'Should I buy or rent when first arriving?',
    ],
    'healthcare': [
      'How do I register for the French healthcare system?',
      'What is a Carte Vitale and how do I get one?',
      'Do I need private health insurance?',
      'How do I find an English-speaking doctor?',
    ],
    'banking': [
      'Which French bank should I choose?',
      'What documents do I need to open an account?',
      'Can I open an account before moving?',
      'How do transfers from the US work?',
    ],
    'taxes': [
      'When do I become a French tax resident?',
      'How does the US-France tax treaty work?',
      'Do I need to file taxes in both countries?',
      'What deductions can I claim?',
    ],
    'driving': [
      'Can I drive with my US license in France?',
      'How do I exchange my license?',
      'Do I need an international driving permit?',
      'What are the rules for buying a car?',
    ],
  };

  return questionsByGuide[guideId] || [
    'What are the first steps I should take?',
    'What documents will I need?',
    'How long does this process typically take?',
    'What are common mistakes to avoid?',
  ];
}
