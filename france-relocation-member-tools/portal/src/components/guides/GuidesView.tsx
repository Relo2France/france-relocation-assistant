import { useState, useRef, useEffect } from 'react';
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
  Sparkles,
  AlertCircle,
  Loader2,
  MapPin,
  DollarSign,
  ExternalLink,
  PawPrint,
  Send,
  Bot,
  User,
  MessageSquare,
} from 'lucide-react';
import {
  useGuides,
  usePersonalizedGuide,
  useGenerateAIGuide,
  useMemberProfile,
  useProfileCompletion,
  useSendChatMessage,
} from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { GuideType, ChatMessage as ChatMessageType } from '@/types';

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
  const [selectedPersonalizedGuide, setSelectedPersonalizedGuide] = useState<GuideType | null>(null);

  // Store hook for navigation
  const { setActiveView } = usePortalStore();

  // API hooks
  const { data: _apiGuides, isLoading: _guidesLoading } = useGuides();
  const { data: profile } = useMemberProfile();
  const { data: profileCompletion } = useProfileCompletion();

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

  // Determine which personalized guides to show based on profile
  const getPersonalizedGuides = (): { type: GuideType; title: string; description: string; icon: React.ElementType }[] => {
    if (!profile) return [];

    const personalizedGuides: { type: GuideType; title: string; description: string; icon: React.ElementType }[] = [];

    // Visa Application Guide (if they have a visa type)
    if (profile.visa_type && profile.visa_type !== 'undecided') {
      personalizedGuides.push({
        type: 'visa-application',
        title: 'Your Visa Application Guide',
        description: `Personalized guide for your ${profile.visa_type} visa application`,
        icon: FileText,
      });
    }

    // Apostille Guide (if they have US states for documents)
    if (profile.birth_state || profile.spouse_birth_state || profile.marriage_state) {
      personalizedGuides.push({
        type: 'apostille',
        title: 'Your Apostille Guide',
        description: 'State-specific apostille information for your documents',
        icon: Shield,
      });
    }

    // Pet Relocation Guide (if they have pets)
    if (profile.has_pets && profile.has_pets !== 'no') {
      personalizedGuides.push({
        type: 'pet-relocation',
        title: 'Your Pet Relocation Guide',
        description: `Personalized guide for relocating with ${profile.has_pets}`,
        icon: PawPrint,
      });
    }

    return personalizedGuides;
  };

  const personalizedGuides = getPersonalizedGuides();

  if (selectedPersonalizedGuide) {
    return (
      <PersonalizedGuideDetail
        guideType={selectedPersonalizedGuide}
        onBack={() => setSelectedPersonalizedGuide(null)}
      />
    );
  }

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

      {/* Personalized Guides Section */}
      {personalizedGuides.length > 0 && searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Sparkles className="w-5 h-5 text-purple-600" />
            Personalized for You
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {personalizedGuides.map((guide) => (
              <PersonalizedGuideCard
                key={guide.type}
                guide={guide}
                onClick={() => setSelectedPersonalizedGuide(guide.type)}
              />
            ))}
          </div>
        </div>
      )}

      {/* Profile Completion Notice */}
      {profileCompletion && profileCompletion.percentage < 50 && searchQuery === '' && selectedCategory === 'all' && (
        <div className="mb-8 card p-4 bg-blue-50 border-blue-200">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-medium text-blue-900 mb-1">
                Complete your profile for personalized guides
              </h3>
              <p className="text-sm text-blue-700 mb-3">
                Fill out your profile to get customized guides based on your visa type, location, and personal circumstances.
                Your profile is currently {profileCompletion.percentage}% complete.
              </p>
              <button
                onClick={() => setActiveView('settings')}
                className="text-sm font-medium text-blue-600 hover:text-blue-700 underline"
              >
                Complete your profile
              </button>
            </div>
          </div>
        </div>
      )}

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

// Personalized Guide Card Component
interface PersonalizedGuideCardProps {
  guide: {
    type: GuideType;
    title: string;
    description: string;
    icon: React.ElementType;
  };
  onClick: () => void;
}

function PersonalizedGuideCard({ guide, onClick }: PersonalizedGuideCardProps) {
  const Icon = guide.icon;

  return (
    <button
      onClick={onClick}
      className="card p-6 text-left bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 hover:shadow-md transition-shadow group"
    >
      <div className="flex items-start justify-between mb-3">
        <div className="p-2 bg-white rounded-lg shadow-sm">
          <Icon className="w-5 h-5 text-purple-600" />
        </div>
        <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded-full flex items-center gap-1">
          <Sparkles className="w-3 h-3" />
          Personalized
        </span>
      </div>
      <h3 className="font-semibold text-gray-900 mb-2 group-hover:text-purple-700 transition-colors">
        {guide.title}
      </h3>
      <p className="text-sm text-gray-600 line-clamp-2 mb-4">
        {guide.description}
      </p>
      <div className="flex items-center justify-between">
        <span className="text-xs text-purple-600 font-medium">
          Based on your profile
        </span>
        <span className="flex items-center gap-1 text-sm font-medium text-purple-600 group-hover:gap-2 transition-all">
          View guide
          <ArrowRight className="w-4 h-4" />
        </span>
      </div>
    </button>
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
  const [messages, setMessages] = useState<ChatMessageType[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [activeSection, setActiveSection] = useState<number | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const latestResponseRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);
  const sendMessage = useSendChatMessage();

  // Scroll to the top of the latest response (not bottom)
  useEffect(() => {
    if (messages.length > 0 && !isLoading && latestResponseRef.current) {
      setTimeout(() => {
        latestResponseRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
  }, [messages, isLoading]);

  // Generate suggested questions based on guide
  const suggestedQuestions = getSuggestedQuestionsForGuide(guide.id);

  const handleSendMessage = async (messageText?: string) => {
    const text = messageText || inputValue.trim();
    if (!text || sendMessage.isPending) return;

    const userMessage: ChatMessageType = {
      id: Date.now().toString(),
      role: 'user',
      content: text,
      timestamp: new Date().toISOString(),
    };

    setMessages((prev) => [...prev, userMessage]);
    setInputValue('');
    setIsLoading(true);

    try {
      const response = await sendMessage.mutateAsync({
        message: text,
        context: guide.id, // Use guide ID as context
        include_practice: true,
      });

      if (response.success && response.message) {
        const assistantMessage: ChatMessageType = {
          id: (Date.now() + 1).toString(),
          role: 'assistant',
          content: response.message,
          timestamp: new Date().toISOString(),
          sources: response.sources,
        };
        setMessages((prev) => [...prev, assistantMessage]);
      }
    } catch (error) {
      const errorMessage: ChatMessageType = {
        id: (Date.now() + 1).toString(),
        role: 'assistant',
        content: 'Sorry, I encountered an error. Please try again.',
        timestamp: new Date().toISOString(),
      };
      setMessages((prev) => [...prev, errorMessage]);
    } finally {
      setIsLoading(false);
    }
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

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

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-12rem)]">
        {/* Left column - Guide info and sections */}
        <div className="lg:col-span-1 space-y-6 overflow-y-auto">
          {/* Header */}
          <div className="card p-6">
            <div className="flex items-start gap-4">
              <div className="p-3 bg-primary-50 rounded-xl">
                <Icon className="w-6 h-6 text-primary-600" />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-xs font-medium text-primary-600">{guide.category}</span>
                  <span className={clsx('px-2 py-0.5 rounded-full text-xs', difficultyColors[guide.difficulty])}>
                    {guide.difficulty}
                  </span>
                </div>
                <h1 className="text-xl font-bold text-gray-900 mb-2">{guide.title}</h1>
                <p className="text-sm text-gray-600">{guide.description}</p>
              </div>
            </div>
          </div>

          {/* Table of contents */}
          <div className="card p-6">
            <h2 className="text-sm font-semibold text-gray-900 mb-4">Guide Sections</h2>
            <div className="space-y-1">
              {guide.sections.map((section, index) => (
                <button
                  key={index}
                  onClick={() => {
                    setActiveSection(index);
                    handleSendMessage(`Tell me about "${section}" for ${guide.title.toLowerCase()}`);
                  }}
                  className={clsx(
                    'w-full flex items-center gap-3 p-3 rounded-lg text-left transition-colors',
                    activeSection === index
                      ? 'bg-primary-50 text-primary-700'
                      : 'hover:bg-gray-50 text-gray-700'
                  )}
                >
                  <span className="w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xs font-medium flex-shrink-0">
                    {index + 1}
                  </span>
                  <span className="text-sm">{section}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Quick questions */}
          <div className="card p-6">
            <h2 className="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Sparkles className="w-4 h-4 text-yellow-500" />
              Quick Questions
            </h2>
            <div className="space-y-2">
              {suggestedQuestions.map((question, index) => (
                <button
                  key={index}
                  onClick={() => handleSendMessage(question)}
                  className="w-full text-left text-sm text-gray-600 hover:text-primary-600 p-2 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  {question}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Right column - AI Chat */}
        <div className="lg:col-span-2">
          <div className="card h-full flex flex-col">
            {/* Chat header */}
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                  <Bot className="w-5 h-5 text-white" />
                </div>
                <div>
                  <h3 className="font-semibold text-gray-900">AI Guide Assistant</h3>
                  <p className="text-xs text-gray-500">Ask questions about {guide.title.toLowerCase()}</p>
                </div>
              </div>
            </div>

            {/* Messages area */}
            <div className="flex-1 overflow-y-auto px-6 py-4">
              {messages.length === 0 ? (
                <div className="h-full flex flex-col items-center justify-center text-center">
                  <div className="w-16 h-16 rounded-full bg-primary-50 flex items-center justify-center mb-4">
                    <MessageSquare className="w-8 h-8 text-primary-600" />
                  </div>
                  <h3 className="font-semibold text-gray-900 mb-2">Start a conversation</h3>
                  <p className="text-sm text-gray-600 max-w-sm mb-4">
                    Click on a section from the guide, ask a quick question, or type your own question below.
                  </p>
                  <p className="text-xs text-gray-500">
                    I can help you understand requirements, timelines, and next steps.
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {messages.map((message, index) => {
                    // Add ref to the latest assistant message for scrolling to top
                    const isLatestAssistant =
                      message.role === 'assistant' &&
                      index === messages.length - 1;

                    if (message.role === 'user') {
                      return (
                        <div
                          key={message.id}
                          className="flex gap-3 justify-end"
                        >
                          <div className="max-w-[80%] rounded-2xl rounded-tr-sm px-4 py-3 bg-primary-600 text-white">
                            <p className="text-sm whitespace-pre-wrap">{message.content}</p>
                          </div>
                          <div className="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                            <User className="w-4 h-4 text-white" />
                          </div>
                        </div>
                      );
                    }

                    // Assistant message with professional card layout
                    return (
                      <div
                        key={message.id}
                        ref={isLatestAssistant ? latestResponseRef : undefined}
                        className="flex gap-3"
                      >
                        <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center flex-shrink-0">
                          <Bot className="w-4 h-4 text-white" />
                        </div>
                        <div className="flex-1 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                          <div className="px-5 py-4">
                            <GuideMessageContent content={message.content} />
                          </div>
                        </div>
                      </div>
                    );
                  })}
                  {isLoading && (
                    <div className="flex gap-3">
                      <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center flex-shrink-0">
                        <Bot className="w-4 h-4 text-white" />
                      </div>
                      <div className="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3">
                        <div className="flex items-center gap-2">
                          <Loader2 className="w-4 h-4 animate-spin text-gray-500" />
                          <span className="text-sm text-gray-500">Thinking...</span>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Input area */}
            <div className="px-6 py-4 border-t border-gray-200">
              <div className="flex gap-3">
                <textarea
                  ref={inputRef}
                  value={inputValue}
                  onChange={(e) => setInputValue(e.target.value)}
                  onKeyPress={handleKeyPress}
                  placeholder="Ask a question about this guide..."
                  rows={1}
                  className="flex-1 px-4 py-3 border border-gray-300 rounded-xl resize-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                  disabled={sendMessage.isPending}
                />
                <button
                  onClick={() => handleSendMessage()}
                  disabled={!inputValue.trim() || sendMessage.isPending}
                  className={clsx(
                    'px-4 py-3 rounded-xl transition-colors flex items-center justify-center',
                    inputValue.trim() && !sendMessage.isPending
                      ? 'bg-primary-600 text-white hover:bg-primary-700'
                      : 'bg-gray-200 text-gray-400 cursor-not-allowed'
                  )}
                >
                  <Send className="w-5 h-5" />
                </button>
              </div>
              <p className="text-xs text-gray-500 mt-2">
                Press Enter to send • Shift+Enter for new line
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// Professional message content renderer with markdown parsing
function GuideMessageContent({ content }: { content: string }) {
  const lines = content.split('\n');
  const elements: JSX.Element[] = [];
  let currentList: { text: string; formatted: JSX.Element }[] = [];
  let currentListType: 'ul' | 'ol' | null = null;

  // Parse inline formatting (bold, links)
  const parseInlineFormatting = (text: string): JSX.Element => {
    const parts: (string | JSX.Element)[] = [];
    let remaining = text;
    let keyIndex = 0;

    while (remaining.length > 0) {
      const linkMatch = remaining.match(/\[([^\]]+)\]\(([^)]+)\)/);
      const boldMatch = remaining.match(/\*\*([^*]+)\*\*/);

      const matches = [
        linkMatch ? { type: 'link', match: linkMatch, index: remaining.indexOf(linkMatch[0]) } : null,
        boldMatch ? { type: 'bold', match: boldMatch, index: remaining.indexOf(boldMatch[0]) } : null,
      ].filter(Boolean).sort((a, b) => (a?.index ?? Infinity) - (b?.index ?? Infinity));

      if (matches.length === 0 || matches[0] === null) {
        parts.push(remaining);
        break;
      }

      const earliest = matches[0];
      if (earliest.index > 0) {
        parts.push(remaining.slice(0, earliest.index));
      }

      if (earliest.type === 'link' && earliest.match) {
        parts.push(
          <a
            key={keyIndex++}
            href={earliest.match[2]}
            target="_blank"
            rel="noopener noreferrer"
            className="text-primary-600 hover:text-primary-700 underline font-medium"
          >
            {earliest.match[1]}
          </a>
        );
        remaining = remaining.slice(earliest.index + earliest.match[0].length);
      } else if (earliest.type === 'bold' && earliest.match) {
        parts.push(
          <strong key={keyIndex++} className="font-semibold text-gray-900">
            {earliest.match[1]}
          </strong>
        );
        remaining = remaining.slice(earliest.index + earliest.match[0].length);
      }
    }

    return <>{parts}</>;
  };

  const flushList = () => {
    if (currentList.length > 0) {
      const isOrdered = currentListType === 'ol';
      elements.push(
        isOrdered ? (
          <ol key={elements.length} className="my-3 ml-5 space-y-1.5 list-decimal text-sm">
            {currentList.map((item, i) => (
              <li key={i} className="text-gray-700 leading-relaxed pl-1">
                {item.formatted}
              </li>
            ))}
          </ol>
        ) : (
          <ul key={elements.length} className="my-3 ml-5 space-y-1.5 text-sm">
            {currentList.map((item, i) => (
              <li key={i} className="text-gray-700 leading-relaxed flex items-start gap-2">
                <span className="text-primary-500 mt-0.5">•</span>
                <span>{item.formatted}</span>
              </li>
            ))}
          </ul>
        )
      );
      currentList = [];
      currentListType = null;
    }
  };

  lines.forEach((line) => {
    const trimmedLine = line.trim();

    // H2 headers (## Header)
    if (trimmedLine.startsWith('## ')) {
      flushList();
      elements.push(
        <h3 key={elements.length} className="text-base font-semibold text-gray-900 mt-4 mb-2 pb-1 border-b border-gray-100">
          {parseInlineFormatting(trimmedLine.slice(3))}
        </h3>
      );
    }
    // H3 headers (### Header)
    else if (trimmedLine.startsWith('### ')) {
      flushList();
      elements.push(
        <h4 key={elements.length} className="text-sm font-semibold text-gray-800 mt-3 mb-1.5">
          {parseInlineFormatting(trimmedLine.slice(4))}
        </h4>
      );
    }
    // Bold-only lines as subheadings
    else if (trimmedLine.startsWith('**') && trimmedLine.endsWith('**') && !trimmedLine.slice(2, -2).includes('**')) {
      flushList();
      elements.push(
        <h4 key={elements.length} className="text-sm font-semibold text-gray-800 mt-3 mb-1.5">
          {trimmedLine.slice(2, -2)}
        </h4>
      );
    }
    // Bullet points
    else if (trimmedLine.match(/^[\-\*•]\s/)) {
      if (currentListType !== 'ul') flushList();
      currentListType = 'ul';
      const itemText = trimmedLine.replace(/^[\-\*•]\s/, '');
      currentList.push({ text: itemText, formatted: parseInlineFormatting(itemText) });
    }
    // Numbered lists
    else if (trimmedLine.match(/^\d+\.\s/)) {
      if (currentListType !== 'ol') flushList();
      currentListType = 'ol';
      const itemText = trimmedLine.replace(/^\d+\.\s/, '');
      currentList.push({ text: itemText, formatted: parseInlineFormatting(itemText) });
    }
    // Regular paragraphs
    else if (trimmedLine) {
      flushList();
      elements.push(
        <p key={elements.length} className="text-sm text-gray-700 leading-relaxed mb-2">
          {parseInlineFormatting(trimmedLine)}
        </p>
      );
    }
    // Empty lines
    else {
      flushList();
    }
  });

  flushList();

  return <div className="guide-message-content">{elements}</div>;
}

// Helper function to get suggested questions based on guide
function getSuggestedQuestionsForGuide(guideId: string): string[] {
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

// Personalized Guide Detail Component
interface PersonalizedGuideDetailProps {
  guideType: GuideType;
  onBack: () => void;
}

function PersonalizedGuideDetail({ guideType, onBack }: PersonalizedGuideDetailProps) {
  const { data: guide, isLoading, error } = usePersonalizedGuide(guideType);
  const { data: _profile } = useMemberProfile();
  const generateGuide = useGenerateAIGuide();

  const handleGenerateGuide = () => {
    generateGuide.mutate(guideType);
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="flex items-center justify-center py-12">
          <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="card p-12 text-center">
          <AlertCircle className="w-12 h-12 text-red-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Failed to load guide</h3>
          <p className="text-gray-500 mb-4">
            We could not load your personalized guide. Please try again.
          </p>
          <button
            onClick={handleGenerateGuide}
            disabled={generateGuide.isPending}
            className="btn-primary"
          >
            {generateGuide.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Generating...
              </>
            ) : (
              <>
                <Sparkles className="w-4 h-4" />
                Generate AI Guide
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

  if (!guide) {
    return (
      <div className="p-6">
        <button
          onClick={onBack}
          className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 transition-colors"
        >
          <ChevronRight className="w-4 h-4 rotate-180" />
          Back to guides
        </button>
        <div className="card p-12 text-center">
          <BookOpen className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Guide not available</h3>
          <p className="text-gray-500 mb-4">
            This personalized guide has not been generated yet.
          </p>
          <button
            onClick={handleGenerateGuide}
            disabled={generateGuide.isPending}
            className="btn-primary"
          >
            {generateGuide.isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Generating...
              </>
            ) : (
              <>
                <Sparkles className="w-4 h-4" />
                Generate AI Guide
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

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
        <div className="card p-6 mb-6 bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
          <div className="flex items-start justify-between gap-4">
            <div className="flex items-start gap-4 flex-1">
              <div className="p-4 bg-white rounded-xl shadow-sm">
                <Sparkles className="w-8 h-8 text-purple-600" />
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded-full">
                    Personalized
                  </span>
                  {guide.metadata?.last_updated && (
                    <span className="text-xs text-purple-600">
                      Updated {new Date(guide.metadata.last_updated).toLocaleDateString()}
                    </span>
                  )}
                </div>
                <h1 className="text-2xl font-bold text-gray-900 mb-2">{guide.title}</h1>
                <p className="text-gray-700">{guide.description}</p>
              </div>
            </div>
            {!guide.is_personalized && (
              <button
                onClick={handleGenerateGuide}
                disabled={generateGuide.isPending}
                className="btn-primary whitespace-nowrap"
              >
                {generateGuide.isPending ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Generating...
                  </>
                ) : (
                  <>
                    <Sparkles className="w-4 h-4" />
                    Regenerate
                  </>
                )}
              </button>
            )}
          </div>
        </div>

        {/* State-specific Apostille Information */}
        {guideType === 'apostille' && guide.metadata?.states && guide.metadata.states.length > 0 && (
          <div className="mb-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Your State-Specific Information
            </h2>
            <div className="space-y-4">
              {guide.metadata.states.map((state) => (
                <div key={state.state} className="card p-5">
                  <div className="flex items-start justify-between mb-4">
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <MapPin className="w-4 h-4 text-primary-600" />
                        <h3 className="font-semibold text-gray-900">{state.state_name}</h3>
                      </div>
                      <p className="text-sm text-gray-600">{state.document}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Agency</h4>
                      <p className="text-sm text-gray-900">{state.agency}</p>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Method</h4>
                      <p className="text-sm text-gray-900">{state.method}</p>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Cost</h4>
                      <div className="flex items-center gap-1">
                        <DollarSign className="w-3 h-3 text-green-600" />
                        <p className="text-sm text-gray-900">{state.cost}</p>
                      </div>
                    </div>
                    <div>
                      <h4 className="text-xs font-medium text-gray-500 uppercase mb-1">Processing Time</h4>
                      <div className="flex items-center gap-1">
                        <Clock className="w-3 h-3 text-blue-600" />
                        <p className="text-sm text-gray-900">{state.processing_time}</p>
                      </div>
                    </div>
                  </div>

                  {state.website && (
                    <a
                      href={state.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium"
                    >
                      Visit agency website
                      <ExternalLink className="w-3 h-3" />
                    </a>
                  )}

                  {state.notes && (
                    <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                      <p className="text-xs text-yellow-800">{state.notes}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Guide Sections */}
        {guide.sections && guide.sections.length > 0 && (
          <div className="space-y-6">
            {guide.sections.map((section, index) => (
              <div key={section.id} className="card p-6">
                <div className="flex items-start gap-3 mb-4">
                  <span className="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-sm font-semibold flex-shrink-0">
                    {index + 1}
                  </span>
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900 mb-3">
                      {section.title}
                    </h3>
                    <div
                      className="prose prose-sm max-w-none text-gray-700"
                      dangerouslySetInnerHTML={{ __html: section.content }}
                    />

                    {section.tips && section.tips.length > 0 && (
                      <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 className="text-sm font-medium text-blue-900 mb-2 flex items-center gap-2">
                          <Star className="w-4 h-4" />
                          Tips
                        </h4>
                        <ul className="space-y-1">
                          {section.tips.map((tip, tipIndex) => (
                            <li key={tipIndex} className="text-sm text-blue-800">
                              {tip}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {section.warnings && section.warnings.length > 0 && (
                      <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 className="text-sm font-medium text-yellow-900 mb-2 flex items-center gap-2">
                          <AlertCircle className="w-4 h-4" />
                          Important Notes
                        </h4>
                        <ul className="space-y-1">
                          {section.warnings.map((warning, warnIndex) => (
                            <li key={warnIndex} className="text-sm text-yellow-800">
                              {warning}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
