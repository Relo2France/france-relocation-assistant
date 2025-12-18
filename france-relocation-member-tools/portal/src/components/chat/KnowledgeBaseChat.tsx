import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  MessageSquare,
  Send,
  Bot,
  User,
  Lightbulb,
  FileText,
  Home,
  Heart,
  DollarSign,
  Car,
  Ship,
  Building,
  MapPin,
  Search,
  ChevronLeft,
  ChevronRight,
  Copy,
  Check,
  AlertCircle,
  Loader2,
} from 'lucide-react';
import {
  useChatCategories,
  useSendChatMessage,
  useSearchChatTopics,
} from '@/hooks/useApi';
import type { ChatMessage as ChatMessageType, ChatSource } from '@/types';

// Knowledge base category icons
const categoryIcons = {
  visas: FileText,
  property: Home,
  healthcare: Heart,
  taxes: DollarSign,
  driving: Car,
  shipping: Ship,
  banking: Building,
  settling: MapPin,
};

// Suggested questions for empty state
const suggestedQuestions = [
  'What documents do I need for a long-stay visa?',
  'How do I apply for a Carte Vitale?',
  'What are the requirements for renting property in France?',
  'How does the US-France tax treaty work?',
  'Can I drive in France with my US license?',
  'What are the steps to open a French bank account?',
];

export default function KnowledgeBaseChat() {
  const [messages, setMessages] = useState<ChatMessageType[]>([]);
  const [inputValue, setInputValue] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [includePractice, setIncludePractice] = useState(true);
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [isStreaming, setIsStreaming] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);

  const { data: categories, isLoading: categoriesLoading } = useChatCategories();
  const sendMessage = useSendChatMessage();

  // Auto-scroll to latest message
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, isStreaming]);

  const handleSendMessage = async () => {
    if (!inputValue.trim() || sendMessage.isPending) return;

    const userMessage: ChatMessageType = {
      id: Date.now().toString(),
      role: 'user',
      content: inputValue.trim(),
      timestamp: new Date().toISOString(),
    };

    setMessages((prev) => [...prev, userMessage]);
    setInputValue('');
    setIsStreaming(true);

    try {
      const response = await sendMessage.mutateAsync({
        message: inputValue.trim(),
        context: selectedCategory || undefined,
        include_practice: includePractice,
      });

      if (response.success && response.message) {
        // Simulate streaming effect
        const assistantMessage: ChatMessageType = {
          id: (Date.now() + 1).toString(),
          role: 'assistant',
          content: response.message,
          timestamp: new Date().toISOString(),
          sources: response.sources,
        };

        // Add message with streaming effect
        await streamMessage(assistantMessage);
      } else {
        throw new Error(response.error || 'Failed to get response');
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
      setIsStreaming(false);
    }
  };

  const streamMessage = async (message: ChatMessageType) => {
    const words = message.content.split(' ');
    let currentContent = '';

    // Add empty message first
    const tempMessage: ChatMessageType = {
      ...message,
      content: '',
    };
    setMessages((prev) => [...prev, tempMessage]);

    // Stream words
    for (let i = 0; i < words.length; i++) {
      currentContent += (i > 0 ? ' ' : '') + words[i];
      setMessages((prev) => {
        const updated = [...prev];
        updated[updated.length - 1] = {
          ...message,
          content: currentContent,
        };
        return updated;
      });
      await new Promise((resolve) => setTimeout(resolve, 30));
    }

    // Finalize with sources
    setMessages((prev) => {
      const updated = [...prev];
      updated[updated.length - 1] = message;
      return updated;
    });
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendMessage();
    }
  };

  const handleSuggestedQuestion = (question: string) => {
    setInputValue(question);
    inputRef.current?.focus();
  };

  const handleRetry = () => {
    if (messages.length > 0) {
      const lastUserMessage = [...messages]
        .reverse()
        .find((m) => m.role === 'user');
      if (lastUserMessage) {
        setInputValue(lastUserMessage.content);
        inputRef.current?.focus();
      }
    }
  };

  return (
    <div className="h-[calc(100vh-4rem)] flex bg-gray-50">
      {/* Sidebar */}
      <ChatSidebar
        categories={categories || []}
        selectedCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        onSelectTopic={handleSuggestedQuestion}
        isOpen={sidebarOpen}
        onToggle={() => setSidebarOpen(!sidebarOpen)}
        isLoading={categoriesLoading}
      />

      {/* Main chat area */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Header */}
        <div className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-3">
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="lg:hidden p-2 hover:bg-gray-100 rounded-lg"
          >
            {sidebarOpen ? (
              <ChevronLeft className="w-5 h-5" />
            ) : (
              <ChevronRight className="w-5 h-5" />
            )}
          </button>
          <MessageSquare className="w-6 h-6 text-primary-600" />
          <div>
            <h1 className="text-xl font-bold text-gray-900">Knowledge Base Chat</h1>
            <p className="text-sm text-gray-600">
              {selectedCategory
                ? `Asking about ${
                    categories?.find((c) => c.id === selectedCategory)?.title || selectedCategory
                  }`
                : 'Ask anything about relocating to France'}
            </p>
          </div>
        </div>

        {/* Messages area */}
        <div className="flex-1 overflow-y-auto px-6 py-6">
          {messages.length === 0 ? (
            selectedCategory ? (
              <CategoryTopicsView
                category={categories?.find((c) => c.id === selectedCategory)}
                onSelectTopic={handleSuggestedQuestion}
              />
            ) : (
              <EmptyState onSelectQuestion={handleSuggestedQuestion} />
            )
          ) : (
            <div className="max-w-4xl mx-auto space-y-6">
              {messages.map((message) => (
                <ChatMessage key={message.id} message={message} />
              ))}
              {isStreaming && (
                <div
                  role="status"
                  aria-live="polite"
                  className="flex items-center gap-2 text-gray-500 text-sm"
                >
                  <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                  <span>AI is thinking...</span>
                </div>
              )}
              <div ref={messagesEndRef} />
            </div>
          )}
        </div>

        {/* Input area */}
        <ChatInput
          value={inputValue}
          onChange={setInputValue}
          onSend={handleSendMessage}
          onKeyPress={handleKeyPress}
          includePractice={includePractice}
          onTogglePractice={() => setIncludePractice(!includePractice)}
          isLoading={sendMessage.isPending || isStreaming}
          inputRef={inputRef}
          onRetry={handleRetry}
          hasError={sendMessage.isError}
        />
      </div>
    </div>
  );
}

// Sidebar component
interface ChatSidebarProps {
  categories: Array<{
    id: string;
    title: string;
    icon: string;
    description: string;
    topics: Array<{ id: string; title: string; keywords: string[]; is_premium: boolean }>;
  }>;
  selectedCategory: string | null;
  onSelectCategory: (id: string | null) => void;
  onSelectTopic: (question: string) => void;
  isOpen: boolean;
  onToggle: () => void;
  isLoading: boolean;
}

function ChatSidebar({
  categories,
  selectedCategory,
  onSelectCategory,
  onSelectTopic,
  isOpen,
  isLoading,
}: ChatSidebarProps) {
  const [topicSearch, setTopicSearch] = useState('');
  const { data: searchResults } = useSearchChatTopics(topicSearch);

  // Filter topics based on selected category
  const popularTopics =
    searchResults?.results ||
    (selectedCategory
      ? categories.find((c) => c.id === selectedCategory)?.topics.slice(0, 6) || []
      : categories.flatMap((cat) => cat.topics.slice(0, 2)).slice(0, 6));

  return (
    <div
      className={clsx(
        'bg-white border-r border-gray-200 flex-shrink-0 transition-all duration-300',
        isOpen ? 'w-80' : 'w-0 lg:w-0',
        'overflow-hidden'
      )}
    >
      <div className="p-6 h-full flex flex-col">
        {/* Topic search */}
        <div className="mb-6">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search topics..."
              value={topicSearch}
              onChange={(e) => setTopicSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
          </div>
        </div>

        {/* Categories */}
        <div className="flex-1 overflow-y-auto space-y-2">
          <button
            onClick={() => onSelectCategory(null)}
            className={clsx(
              'w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-left',
              selectedCategory === null
                ? 'bg-primary-50 text-primary-700 font-medium'
                : 'text-gray-700 hover:bg-gray-50'
            )}
          >
            <MessageSquare className="w-5 h-5 flex-shrink-0" />
            <span>All Topics</span>
          </button>

          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3, 4, 5, 6].map((i) => (
                <div key={i} className="h-12 bg-gray-100 rounded-lg animate-pulse" />
              ))}
            </div>
          ) : (
            categories.map((category) => {
              const Icon =
                categoryIcons[category.id as keyof typeof categoryIcons] || MessageSquare;
              return (
                <button
                  key={category.id}
                  onClick={() =>
                    onSelectCategory(selectedCategory === category.id ? null : category.id)
                  }
                  className={clsx(
                    'w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-left',
                    selectedCategory === category.id
                      ? 'bg-primary-50 text-primary-700 font-medium'
                      : 'text-gray-700 hover:bg-gray-50'
                  )}
                  title={category.description}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  <span className="truncate">{category.title}</span>
                </button>
              );
            })
          )}
        </div>

        {/* Popular topics */}
        {!topicSearch && popularTopics && popularTopics.length > 0 && (
          <div className="mt-6 pt-6 border-t border-gray-200">
            <h3 className="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
              <Lightbulb className="w-4 h-4 text-yellow-500" />
              {selectedCategory
                ? `${categories.find((c) => c.id === selectedCategory)?.title || 'Category'} Topics`
                : 'Popular Topics'}
            </h3>
            <div className="space-y-2">
              {popularTopics.slice(0, 6).map((topic, index) => (
                <button
                  key={index}
                  onClick={() => onSelectTopic(`Tell me about ${topic.title}`)}
                  className="text-sm text-gray-600 hover:text-primary-600 cursor-pointer py-1 line-clamp-1 text-left w-full"
                  title={`Ask about: ${topic.title}`}
                >
                  {topic.title}
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// Message component
interface ChatMessageProps {
  message: ChatMessageType;
}

function ChatMessage({ message }: ChatMessageProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(message.content);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const formatTime = (timestamp: string) => {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
    });
  };

  if (message.role === 'user') {
    return (
      <div className="flex items-start gap-3 justify-end">
        <div className="max-w-2xl">
          <div className="bg-primary-600 text-white rounded-2xl rounded-tr-sm px-4 py-3">
            <p className="text-sm leading-relaxed whitespace-pre-wrap">{message.content}</p>
          </div>
          <div className="flex items-center justify-end gap-2 mt-1 px-2">
            <span className="text-xs text-gray-500">{formatTime(message.timestamp)}</span>
          </div>
        </div>
        <div className="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
          <User className="w-4 h-4 text-white" />
        </div>
      </div>
    );
  }

  // Assistant message
  return (
    <div className="flex items-start gap-3">
      <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center flex-shrink-0">
        <Bot className="w-4 h-4 text-white" />
      </div>
      <div className="flex-1 max-w-2xl">
        <div className="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
          <div className="prose prose-sm max-w-none">
            <MessageContent content={message.content} />
          </div>
        </div>

        {/* Sources */}
        {message.sources && message.sources.length > 0 && (
          <div className="mt-3 px-2">
            <div className="text-xs font-medium text-gray-500 mb-2">Sources:</div>
            <div className="flex flex-wrap gap-2">
              {message.sources.map((source, index) => (
                <SourceBadge key={index} source={source} />
              ))}
            </div>
          </div>
        )}

        {/* Footer */}
        <div className="flex items-center justify-between gap-2 mt-1 px-2">
          <span className="text-xs text-gray-500">{formatTime(message.timestamp)}</span>
          <button
            onClick={handleCopy}
            className={clsx(
              'p-1.5 rounded transition-colors',
              copied
                ? 'bg-green-100 text-green-600'
                : 'hover:bg-gray-100 text-gray-400 hover:text-gray-600'
            )}
            title="Copy message"
          >
            {copied ? <Check className="w-3 h-3" /> : <Copy className="w-3 h-3" />}
          </button>
        </div>
      </div>
    </div>
  );
}

// Message content with markdown-like rendering
function MessageContent({ content }: { content: string }) {
  // Simple markdown-like parsing
  const lines = content.split('\n');
  const elements: JSX.Element[] = [];
  let currentList: string[] = [];
  let currentListType: 'ul' | 'ol' | null = null;

  const flushList = () => {
    if (currentList.length > 0) {
      const ListTag = currentListType === 'ol' ? 'ol' : 'ul';
      elements.push(
        <ListTag key={elements.length} className="my-2 ml-4">
          {currentList.map((item, i) => (
            <li key={i} className="text-gray-700">
              {item}
            </li>
          ))}
        </ListTag>
      );
      currentList = [];
      currentListType = null;
    }
  };

  lines.forEach((line, _index) => {
    // Bullet points
    if (line.match(/^[\-\*•]\s/)) {
      if (currentListType !== 'ul') flushList();
      currentListType = 'ul';
      currentList.push(line.replace(/^[\-\*•]\s/, ''));
    }
    // Numbered lists
    else if (line.match(/^\d+\.\s/)) {
      if (currentListType !== 'ol') flushList();
      currentListType = 'ol';
      currentList.push(line.replace(/^\d+\.\s/, ''));
    }
    // Code blocks
    else if (line.startsWith('```')) {
      flushList();
      elements.push(
        <pre key={elements.length} className="bg-gray-100 p-3 rounded-lg overflow-x-auto">
          <code className="text-xs">{line.replace(/```/g, '')}</code>
        </pre>
      );
    }
    // Regular paragraphs
    else if (line.trim()) {
      flushList();
      // Parse bold text **text** safely without dangerouslySetInnerHTML
      const parts = line.split(/(\*\*.*?\*\*)/g);
      const formattedParts = parts.map((part, i) => {
        if (part.startsWith('**') && part.endsWith('**')) {
          return <strong key={i}>{part.slice(2, -2)}</strong>;
        }
        return part;
      });
      elements.push(
        <p
          key={elements.length}
          className="text-gray-700 leading-relaxed mb-2"
        >
          {formattedParts}
        </p>
      );
    }
    // Empty lines
    else {
      flushList();
    }
  });

  flushList(); // Flush any remaining list

  return <>{elements}</>;
}

// Source badge
function SourceBadge({ source }: { source: ChatSource }) {
  const categoryColors: Record<string, string> = {
    visas: 'bg-blue-100 text-blue-700 border-blue-200',
    property: 'bg-green-100 text-green-700 border-green-200',
    healthcare: 'bg-red-100 text-red-700 border-red-200',
    taxes: 'bg-yellow-100 text-yellow-700 border-yellow-200',
    driving: 'bg-purple-100 text-purple-700 border-purple-200',
    shipping: 'bg-indigo-100 text-indigo-700 border-indigo-200',
    banking: 'bg-pink-100 text-pink-700 border-pink-200',
    settling: 'bg-orange-100 text-orange-700 border-orange-200',
  };

  const colorClass = categoryColors[source.category] || 'bg-gray-100 text-gray-700 border-gray-200';

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs border',
        colorClass
      )}
      title={`Relevance: ${Math.round(source.relevance * 100)}%`}
    >
      <span className="font-medium">{source.title}</span>
    </span>
  );
}

// Empty state
interface EmptyStateProps {
  onSelectQuestion: (question: string) => void;
}

function EmptyState({ onSelectQuestion }: EmptyStateProps) {
  return (
    <div className="max-w-3xl mx-auto text-center py-12">
      <div className="w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
        <MessageSquare className="w-8 h-8 text-white" />
      </div>
      <h2 className="text-2xl font-bold text-gray-900 mb-3">
        Welcome to Knowledge Base Chat
      </h2>
      <p className="text-gray-600 mb-8 max-w-2xl mx-auto">
        Ask me anything about relocating to France! I can help with visas, healthcare, taxes,
        property, driving, banking, and more. Get answers based on our comprehensive knowledge
        base and real-world insights.
      </p>

      <div className="mb-6">
        <h3 className="text-sm font-semibold text-gray-900 mb-4 flex items-center justify-center gap-2">
          <Lightbulb className="w-4 h-4 text-yellow-500" />
          Try asking:
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {suggestedQuestions.map((question, index) => (
            <button
              key={index}
              onClick={() => onSelectQuestion(question)}
              className="p-3 bg-white border border-gray-200 rounded-lg text-left text-sm text-gray-700 hover:bg-gray-50 hover:border-primary-300 transition-colors"
            >
              {question}
            </button>
          ))}
        </div>
      </div>

      <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg text-left max-w-xl mx-auto">
        <div className="flex items-start gap-3">
          <Lightbulb className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <h4 className="font-medium text-blue-900 mb-1">Pro Tip</h4>
            <p className="text-sm text-blue-700">
              Toggle "Include real-world insights" to get practical advice from people who have
              actually gone through the relocation process, not just official requirements.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

// Category topics view - shown when a category is selected
interface CategoryTopicsViewProps {
  category?: {
    id: string;
    title: string;
    icon: string;
    description: string;
    topics: Array<{ id: string; title: string; keywords: string[]; is_premium: boolean }>;
  };
  onSelectTopic: (question: string) => void;
}

function CategoryTopicsView({ category, onSelectTopic }: CategoryTopicsViewProps) {
  if (!category) {
    return null;
  }

  const Icon = categoryIcons[category.id as keyof typeof categoryIcons] || MessageSquare;

  return (
    <div className="max-w-3xl mx-auto py-8">
      {/* Category header */}
      <div className="text-center mb-8">
        <div className="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
          <Icon className="w-8 h-8 text-white" />
        </div>
        <h2 className="text-2xl font-bold text-gray-900 mb-2">{category.title}</h2>
        <p className="text-gray-600">{category.description}</p>
      </div>

      {/* Topics grid */}
      <div className="mb-6">
        <h3 className="text-sm font-semibold text-gray-900 mb-4 flex items-center justify-center gap-2">
          <Lightbulb className="w-4 h-4 text-yellow-500" />
          Topics in {category.title}
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          {category.topics.map((topic) => (
            <button
              key={topic.id}
              onClick={() => onSelectTopic(`Tell me about ${topic.title}`)}
              className="p-4 bg-white border border-gray-200 rounded-lg text-left hover:bg-gray-50 hover:border-primary-300 transition-colors group"
            >
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 transition-colors">
                  <FileText className="w-4 h-4 text-primary-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="font-medium text-gray-900 mb-1 line-clamp-1">{topic.title}</h4>
                  {topic.keywords.length > 0 && (
                    <p className="text-xs text-gray-500 line-clamp-1">
                      {topic.keywords.slice(0, 3).join(' • ')}
                    </p>
                  )}
                </div>
                {topic.is_premium && (
                  <span className="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
                    Premium
                  </span>
                )}
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Hint */}
      <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg text-left max-w-xl mx-auto">
        <div className="flex items-start gap-3">
          <Lightbulb className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
          <div>
            <h4 className="font-medium text-blue-900 mb-1">Click a topic to ask about it</h4>
            <p className="text-sm text-blue-700">
              Select any topic above to start a conversation, or type your own question below.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

// Input area
interface ChatInputProps {
  value: string;
  onChange: (value: string) => void;
  onSend: () => void;
  onKeyPress: (e: React.KeyboardEvent) => void;
  includePractice: boolean;
  onTogglePractice: () => void;
  isLoading: boolean;
  inputRef: React.RefObject<HTMLTextAreaElement>;
  onRetry: () => void;
  hasError: boolean;
}

function ChatInput({
  value,
  onChange,
  onSend,
  onKeyPress,
  includePractice,
  onTogglePractice,
  isLoading,
  inputRef,
  onRetry,
  hasError,
}: ChatInputProps) {
  const maxLength = 500;

  return (
    <div className="bg-white border-t border-gray-200 px-6 py-4">
      <div className="max-w-4xl mx-auto">
        {/* Error state */}
        {hasError && (
          <div className="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
            <div className="flex items-center gap-2 text-sm text-red-700">
              <AlertCircle className="w-4 h-4" />
              <span>Failed to send message. Please try again.</span>
            </div>
            <button
              onClick={onRetry}
              className="text-sm font-medium text-red-700 hover:text-red-800"
            >
              Retry
            </button>
          </div>
        )}

        {/* Input controls */}
        <div className="flex items-center gap-3 mb-3">
          <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input
              type="checkbox"
              checked={includePractice}
              onChange={onTogglePractice}
              className="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
            />
            <span>Include real-world insights</span>
          </label>
        </div>

        {/* Input area */}
        <div className="relative">
          <textarea
            ref={inputRef}
            value={value}
            onChange={(e) => onChange(e.target.value)}
            onKeyPress={onKeyPress}
            placeholder="Ask a question about relocating to France..."
            rows={3}
            maxLength={maxLength}
            disabled={isLoading}
            className="w-full px-4 py-3 pr-24 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-50 disabled:text-gray-500"
          />

          {/* Character count */}
          <div className="absolute bottom-3 right-3 flex items-center gap-2">
            <span
              className={clsx(
                'text-xs',
                value.length > maxLength * 0.9 ? 'text-orange-600' : 'text-gray-400'
              )}
            >
              {value.length}/{maxLength}
            </span>

            {/* Send button */}
            <button
              onClick={onSend}
              disabled={!value.trim() || isLoading}
              className={clsx(
                'p-2 rounded-lg transition-colors',
                value.trim() && !isLoading
                  ? 'bg-primary-600 text-white hover:bg-primary-700'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              )}
              title="Send message (Enter)"
            >
              {isLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <Send className="w-4 h-4" />
              )}
            </button>
          </div>
        </div>

        {/* Hint */}
        <p className="text-xs text-gray-500 mt-2">
          Press <kbd className="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded text-xs">Enter</kbd> to send,{' '}
          <kbd className="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded text-xs">Shift+Enter</kbd> for new line
        </p>
      </div>
    </div>
  );
}
