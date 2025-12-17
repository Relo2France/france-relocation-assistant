import { useState } from 'react';
import { clsx } from 'clsx';
import {
  BookOpen,
  Clock,
  ChevronRight,
  FileText,
  Home,
  Briefcase,
  CreditCard,
  Heart,
  GraduationCap,
  Car,
  Shield,
  Users,
  Search,
  Star,
  ArrowRight,
} from 'lucide-react';

interface Guide {
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

const guides: Guide[] = [
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

const categories = [
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

const difficultyColors = {
  beginner: 'bg-green-100 text-green-700',
  intermediate: 'bg-yellow-100 text-yellow-700',
  advanced: 'bg-red-100 text-red-700',
};

export default function GuidesView() {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedGuide, setSelectedGuide] = useState<Guide | null>(null);

  const filteredGuides = guides.filter((guide) => {
    const matchesSearch =
      searchQuery === '' ||
      guide.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      guide.description.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesCategory =
      selectedCategory === 'all' || guide.category === selectedCategory;

    return matchesSearch && matchesCategory;
  });

  const featuredGuides = guides.filter((g) => g.featured);

  if (selectedGuide) {
    return (
      <GuideDetail guide={selectedGuide} onBack={() => setSelectedGuide(null)} />
    );
  }

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Relocation Guides</h1>
        <p className="text-gray-600 mt-1">
          Comprehensive guides to help you navigate every aspect of moving to France
        </p>
      </div>

      {/* Search and filters */}
      <div className="mb-6 flex flex-col sm:flex-row gap-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search guides..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      {/* Category pills */}
      <div className="mb-8 flex flex-wrap gap-2">
        {categories.map((category) => (
          <button
            key={category.id}
            onClick={() => setSelectedCategory(category.id)}
            className={clsx(
              'px-4 py-2 rounded-full text-sm font-medium transition-colors',
              selectedCategory === category.id
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            )}
          >
            {category.label}
          </button>
        ))}
      </div>

      {/* Featured section (only show when no search/filter) */}
      {searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Star className="w-5 h-5 text-yellow-500" />
            Featured Guides
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {featuredGuides.map((guide) => (
              <FeaturedGuideCard
                key={guide.id}
                guide={guide}
                onClick={() => setSelectedGuide(guide)}
              />
            ))}
          </div>
        </div>
      )}

      {/* All guides */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <BookOpen className="w-5 h-5 text-primary-600" />
          {selectedCategory === 'all' ? 'All Guides' : selectedCategory}
          <span className="text-sm font-normal text-gray-500">
            ({filteredGuides.length})
          </span>
        </h2>

        {filteredGuides.length === 0 ? (
          <div className="card p-12 text-center">
            <BookOpen className="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No guides found</h3>
            <p className="text-gray-500">
              Try adjusting your search or filter criteria
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredGuides.map((guide) => (
              <GuideCard
                key={guide.id}
                guide={guide}
                onClick={() => setSelectedGuide(guide)}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

interface GuideCardProps {
  guide: Guide;
  onClick: () => void;
}

function GuideCard({ guide, onClick }: GuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-5 text-left hover:shadow-md transition-shadow group"
    >
      <div className="flex items-start gap-4">
        <div className="p-3 bg-primary-50 rounded-lg group-hover:bg-primary-100 transition-colors">
          <Icon className="w-6 h-6 text-primary-600" />
        </div>
        <div className="flex-1 min-w-0">
          <h3 className="font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
            {guide.title}
          </h3>
          <p className="text-sm text-gray-600 line-clamp-2 mb-3">
            {guide.description}
          </p>
          <div className="flex items-center gap-3 text-xs">
            <span className="flex items-center gap-1 text-gray-500">
              <Clock className="w-3 h-3" />
              {guide.readTime}
            </span>
            <span className={clsx('px-2 py-0.5 rounded-full', difficultyColors[guide.difficulty])}>
              {guide.difficulty}
            </span>
          </div>
        </div>
        <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-primary-600 transition-colors" />
      </div>
    </button>
  );
}

function FeaturedGuideCard({ guide, onClick }: GuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-6 text-left bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200 hover:shadow-md transition-shadow group"
    >
      <div className="flex items-center gap-2 mb-3">
        <div className="p-2 bg-white rounded-lg shadow-sm">
          <Icon className="w-5 h-5 text-primary-600" />
        </div>
        <span className="text-xs font-medium text-primary-600">{guide.category}</span>
      </div>
      <h3 className="font-semibold text-gray-900 mb-2 group-hover:text-primary-700 transition-colors">
        {guide.title}
      </h3>
      <p className="text-sm text-gray-600 line-clamp-2 mb-4">
        {guide.description}
      </p>
      <div className="flex items-center justify-between">
        <span className="flex items-center gap-1 text-xs text-gray-500">
          <Clock className="w-3 h-3" />
          {guide.readTime}
        </span>
        <span className="flex items-center gap-1 text-sm font-medium text-primary-600 group-hover:gap-2 transition-all">
          Read guide
          <ArrowRight className="w-4 h-4" />
        </span>
      </div>
    </button>
  );
}

interface GuideDetailProps {
  guide: Guide;
  onBack: () => void;
}

function GuideDetail({ guide, onBack }: GuideDetailProps) {
  const Icon = guide.icon;

  return (
    <div className="p-6">
      {/* Back button */}
      <button
        onClick={onBack}
        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
      >
        <ChevronRight className="w-4 h-4 rotate-180" />
        Back to guides
      </button>

      <div className="max-w-3xl">
        {/* Header */}
        <div className="card p-6 mb-6">
          <div className="flex items-start gap-4">
            <div className="p-4 bg-primary-50 rounded-xl">
              <Icon className="w-8 h-8 text-primary-600" />
            </div>
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <span className="text-sm font-medium text-primary-600">{guide.category}</span>
                <span className={clsx('px-2 py-0.5 rounded-full text-xs', difficultyColors[guide.difficulty])}>
                  {guide.difficulty}
                </span>
              </div>
              <h1 className="text-2xl font-bold text-gray-900 mb-2">{guide.title}</h1>
              <p className="text-gray-600">{guide.description}</p>
              <div className="flex items-center gap-4 mt-4 text-sm text-gray-500">
                <span className="flex items-center gap-1">
                  <Clock className="w-4 h-4" />
                  {guide.readTime} read
                </span>
                <span className="flex items-center gap-1">
                  <BookOpen className="w-4 h-4" />
                  {guide.sections.length} sections
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Table of contents */}
        <div className="card p-6 mb-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">In this guide</h2>
          <div className="space-y-2">
            {guide.sections.map((section, index) => (
              <div
                key={index}
                className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
              >
                <span className="w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-medium">
                  {index + 1}
                </span>
                <span className="text-gray-700">{section}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Content placeholder */}
        <div className="card p-6">
          <div className="prose max-w-none">
            <p className="text-gray-600">
              Full guide content would be loaded here. This guide covers everything you need
              to know about {guide.title.toLowerCase()}.
            </p>
            <div className="mt-6 p-4 bg-primary-50 rounded-lg border border-primary-200">
              <h3 className="text-primary-900 font-medium mb-2">Need personalized help?</h3>
              <p className="text-primary-700 text-sm">
                Use our AI assistant to get answers specific to your situation. It can help
                you understand requirements based on your visa type and personal circumstances.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
