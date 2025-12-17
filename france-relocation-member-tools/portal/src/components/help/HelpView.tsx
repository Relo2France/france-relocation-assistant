import { useState } from 'react';
import { clsx } from 'clsx';
import {
  HelpCircle,
  ChevronDown,
  ChevronRight,
  Book,
  MessageCircle,
  Mail,
  ExternalLink,
  FileText,
  Clock,
  CheckCircle,
  AlertCircle,
  Plane,
  Home,
  Briefcase,
  GraduationCap,
} from 'lucide-react';

interface FAQItem {
  question: string;
  answer: string;
  category: string;
}

const faqData: FAQItem[] = [
  // Getting Started
  {
    category: 'Getting Started',
    question: 'How do I start my relocation journey?',
    answer: 'Begin by completing your profile with accurate information about your situation, including your visa type, target move date, and family details. Our system will then generate a personalized task list and timeline based on your specific circumstances.',
  },
  {
    category: 'Getting Started',
    question: 'What visa type should I choose?',
    answer: 'The visa type depends on your purpose for moving to France. Common options include: VLS-TS Visiteur (for retirees/passive income), Passeport Talent (for entrepreneurs/skilled workers), Student Visa (for education), and Family Visa (for joining French citizens/residents). Use our AI assistant for personalized guidance.',
  },
  {
    category: 'Getting Started',
    question: 'How long does the relocation process typically take?',
    answer: 'The timeline varies based on your visa type and personal situation. Generally, expect 3-6 months from initial application to arrival in France. Long-stay visa processing typically takes 2-8 weeks. Our timeline feature helps you track each step.',
  },
  // Documents
  {
    category: 'Documents',
    question: 'What documents do I need for my visa application?',
    answer: 'Required documents vary by visa type but typically include: valid passport, passport photos, proof of accommodation, proof of income/financial resources, health insurance, and a completed application form. Check your personalized task list for specific requirements.',
  },
  {
    category: 'Documents',
    question: 'Do my documents need to be translated?',
    answer: 'Yes, most documents must be translated into French by a certified/sworn translator (traducteur assermenté). Some documents may also require apostille certification. We can help generate cover letters and provide translator recommendations.',
  },
  {
    category: 'Documents',
    question: 'How do I get an apostille for my documents?',
    answer: 'Apostilles authenticate documents for international use. In the US, contact your state\'s Secretary of State office. In the UK, use the Foreign, Commonwealth & Development Office. Processing typically takes 1-4 weeks.',
  },
  // Tasks & Progress
  {
    category: 'Tasks & Progress',
    question: 'How do I mark a task as complete?',
    answer: 'Click on any task to open its details, then use the status dropdown to change it to "Done". You can also drag and drop tasks between columns in the Kanban view. Completed tasks are automatically logged in your timeline.',
  },
  {
    category: 'Tasks & Progress',
    question: 'Can I add my own custom tasks?',
    answer: 'Yes! Click the "Add Task" button in the Tasks view to create custom tasks. You can set due dates, priorities, and assign them to specific stages of your relocation journey.',
  },
  {
    category: 'Tasks & Progress',
    question: 'What happens if I miss a deadline?',
    answer: 'Overdue tasks are highlighted in red on your dashboard and task list. While missing internal deadlines won\'t directly affect your application, we recommend staying on schedule. Official government deadlines (like visa appointments) should never be missed.',
  },
  // Account & Membership
  {
    category: 'Account',
    question: 'How do I upgrade my membership?',
    answer: 'Visit the Membership page from the main menu or your account settings. Choose from our available plans and complete the checkout process. Upgrades take effect immediately.',
  },
  {
    category: 'Account',
    question: 'Can I share my account with family members?',
    answer: 'Each account is for individual use, but our Family plan allows you to add family members to your relocation project. This lets everyone track their own tasks while staying coordinated.',
  },
  {
    category: 'Account',
    question: 'How do I export my data?',
    answer: 'You can download your documents from the Documents section. For a complete data export including tasks and notes, please contact our support team.',
  },
];

const resourceLinks = [
  {
    title: 'France-Visas Official Portal',
    description: 'Official French government visa application website',
    url: 'https://france-visas.gouv.fr',
    icon: Plane,
  },
  {
    title: 'Service-Public.fr',
    description: 'Official guide to French administrative procedures',
    url: 'https://www.service-public.fr',
    icon: FileText,
  },
  {
    title: 'CAF (Family Allowances)',
    description: 'Information about French family benefits',
    url: 'https://www.caf.fr',
    icon: Home,
  },
  {
    title: 'URSSAF (Social Security)',
    description: 'French social security contributions for workers',
    url: 'https://www.urssaf.fr',
    icon: Briefcase,
  },
  {
    title: 'Campus France',
    description: 'Resource for students planning to study in France',
    url: 'https://www.campusfrance.org',
    icon: GraduationCap,
  },
];

const quickGuides = [
  {
    title: 'Visa Application Checklist',
    description: 'Step-by-step guide for preparing your visa documents',
    time: '10 min read',
  },
  {
    title: 'Opening a French Bank Account',
    description: 'Options and requirements for banking in France',
    time: '8 min read',
  },
  {
    title: 'Finding Housing in France',
    description: 'Tips for apartment hunting and rental requirements',
    time: '12 min read',
  },
  {
    title: 'French Healthcare System',
    description: 'Understanding CPAM, mutuelle, and medical care',
    time: '15 min read',
  },
];

export default function HelpView() {
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedCategories, setExpandedCategories] = useState<string[]>(['Getting Started']);
  const [expandedQuestions, setExpandedQuestions] = useState<string[]>([]);

  // Group FAQ by category
  const categories = [...new Set(faqData.map((item) => item.category))];

  // Filter FAQ based on search
  const filteredFaq = searchQuery
    ? faqData.filter(
        (item) =>
          item.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
          item.answer.toLowerCase().includes(searchQuery.toLowerCase())
      )
    : faqData;

  const toggleCategory = (category: string) => {
    setExpandedCategories((prev) =>
      prev.includes(category)
        ? prev.filter((c) => c !== category)
        : [...prev, category]
    );
  };

  const toggleQuestion = (question: string) => {
    setExpandedQuestions((prev) =>
      prev.includes(question)
        ? prev.filter((q) => q !== question)
        : [...prev, question]
    );
  };

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Help & Support</h1>
        <p className="text-gray-600 mt-1">
          Find answers, guides, and resources for your relocation journey
        </p>
      </div>

      {/* Search */}
      <div className="mb-8">
        <div className="relative max-w-xl">
          <HelpCircle className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search for help..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* FAQ Section - Main column */}
        <div className="lg:col-span-2 space-y-6">
          {/* FAQ */}
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <MessageCircle className="w-5 h-5 text-primary-600" />
              Frequently Asked Questions
            </h2>

            {searchQuery ? (
              // Search results
              <div className="space-y-3">
                {filteredFaq.length === 0 ? (
                  <p className="text-gray-500 text-center py-8">
                    No results found for "{searchQuery}"
                  </p>
                ) : (
                  filteredFaq.map((item) => (
                    <FAQAccordion
                      key={item.question}
                      item={item}
                      isExpanded={expandedQuestions.includes(item.question)}
                      onToggle={() => toggleQuestion(item.question)}
                      showCategory
                    />
                  ))
                )}
              </div>
            ) : (
              // Categorized FAQ
              <div className="space-y-4">
                {categories.map((category) => {
                  const categoryItems = faqData.filter((item) => item.category === category);
                  const isExpanded = expandedCategories.includes(category);

                  return (
                    <div key={category} className="border border-gray-200 rounded-lg overflow-hidden">
                      <button
                        onClick={() => toggleCategory(category)}
                        className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors"
                      >
                        <span className="font-medium text-gray-900">{category}</span>
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-gray-500">
                            {categoryItems.length} questions
                          </span>
                          {isExpanded ? (
                            <ChevronDown className="w-5 h-5 text-gray-400" />
                          ) : (
                            <ChevronRight className="w-5 h-5 text-gray-400" />
                          )}
                        </div>
                      </button>

                      {isExpanded && (
                        <div className="p-3 space-y-2">
                          {categoryItems.map((item) => (
                            <FAQAccordion
                              key={item.question}
                              item={item}
                              isExpanded={expandedQuestions.includes(item.question)}
                              onToggle={() => toggleQuestion(item.question)}
                            />
                          ))}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {/* Quick Guides */}
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Book className="w-5 h-5 text-primary-600" />
              Quick Guides
            </h2>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {quickGuides.map((guide) => (
                <div
                  key={guide.title}
                  className="p-4 border border-gray-200 rounded-lg hover:border-primary-300 hover:bg-primary-50/50 transition-colors cursor-pointer"
                >
                  <h3 className="font-medium text-gray-900 mb-1">{guide.title}</h3>
                  <p className="text-sm text-gray-600 mb-2">{guide.description}</p>
                  <div className="flex items-center gap-1 text-xs text-gray-500">
                    <Clock className="w-3 h-3" />
                    {guide.time}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Contact Support */}
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Mail className="w-5 h-5 text-primary-600" />
              Contact Support
            </h2>

            <div className="space-y-4">
              <p className="text-sm text-gray-600">
                Can't find what you're looking for? Our support team is here to help.
              </p>

              <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div className="flex items-center gap-2 text-green-700 mb-1">
                  <CheckCircle className="w-4 h-4" />
                  <span className="font-medium text-sm">AI Assistant Available</span>
                </div>
                <p className="text-sm text-green-600">
                  Use our AI chat for instant answers about French visas and relocation.
                </p>
              </div>

              <a
                href="mailto:support@relo2france.com"
                className="btn btn-primary w-full flex items-center justify-center gap-2"
              >
                <Mail className="w-4 h-4" />
                Email Support
              </a>

              <div className="flex items-start gap-2 text-sm text-gray-500">
                <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>Response time: Usually within 24 hours</span>
              </div>
            </div>
          </div>

          {/* Useful Links */}
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <ExternalLink className="w-5 h-5 text-primary-600" />
              Official Resources
            </h2>

            <div className="space-y-3">
              {resourceLinks.map((link) => {
                const Icon = link.icon;
                return (
                  <a
                    key={link.title}
                    href={link.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors group"
                  >
                    <div className="p-2 bg-gray-100 rounded-lg group-hover:bg-primary-100 transition-colors">
                      <Icon className="w-4 h-4 text-gray-600 group-hover:text-primary-600" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-1">
                        <span className="font-medium text-gray-900 text-sm">
                          {link.title}
                        </span>
                        <ExternalLink className="w-3 h-3 text-gray-400" />
                      </div>
                      <p className="text-xs text-gray-500 truncate">
                        {link.description}
                      </p>
                    </div>
                  </a>
                );
              })}
            </div>
          </div>

          {/* Status */}
          <div className="card p-6 bg-gradient-to-br from-primary-50 to-primary-100 border-primary-200">
            <h3 className="font-semibold text-primary-900 mb-2">System Status</h3>
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
              <span className="text-sm text-primary-700">All systems operational</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

interface FAQAccordionProps {
  item: FAQItem;
  isExpanded: boolean;
  onToggle: () => void;
  showCategory?: boolean;
}

function FAQAccordion({ item, isExpanded, onToggle, showCategory }: FAQAccordionProps) {
  return (
    <div className={clsx(
      'border rounded-lg overflow-hidden',
      isExpanded ? 'border-primary-200 bg-primary-50/30' : 'border-gray-200'
    )}>
      <button
        onClick={onToggle}
        className="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-gray-50 transition-colors"
      >
        {isExpanded ? (
          <ChevronDown className="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" />
        ) : (
          <ChevronRight className="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
        )}
        <div className="flex-1">
          {showCategory && (
            <span className="text-xs text-primary-600 font-medium">{item.category}</span>
          )}
          <span className={clsx(
            'block font-medium',
            isExpanded ? 'text-primary-900' : 'text-gray-900'
          )}>
            {item.question}
          </span>
        </div>
      </button>

      {isExpanded && (
        <div className="px-4 pb-4 pl-12">
          <p className="text-sm text-gray-600 leading-relaxed">{item.answer}</p>
        </div>
      )}
    </div>
  );
}
