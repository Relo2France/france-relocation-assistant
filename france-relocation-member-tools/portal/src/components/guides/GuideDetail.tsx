/**
 * Guide Detail Component
 *
 * Displays a guide with interactive AI chat functionality.
 */

import { useState, useRef, useEffect } from 'react';
import { clsx } from 'clsx';
import {
  ChevronRight,
  Sparkles,
  Loader2,
  Send,
  Bot,
  User,
  MessageSquare,
} from 'lucide-react';
import { useSendChatMessage } from '@/hooks/useApi';
import { difficultyColors, getSuggestedQuestionsForGuide } from './guidesData';
import GuideMessageContent from './GuideMessageContent';
import type { Guide } from './guidesData';
import type { ChatMessage as ChatMessageType } from '@/types';

interface GuideDetailProps {
  guide: Guide;
  onBack: () => void;
}

export default function GuideDetail({ guide, onBack }: GuideDetailProps) {
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
    } catch {
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
