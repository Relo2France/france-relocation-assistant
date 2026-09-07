export interface Env {
  MODEL_CACHE: KVNamespace;
  REVIEW_WORKFLOW: Workflow;
  GAP_WORKFLOW: Workflow;
  ANTHROPIC_API_KEY: string;
  WP_SHARED_SECRET: string;
  TRIGGER_SECRET: string;
  WP_BASE_URL: string;
  MODEL_TIER: string;
  ENVIRONMENT: string;
}

/** A reviewable topic, as returned by GET /fra/v1/review/topics. */
export interface Topic {
  category: string;
  topic_key: string;
  name: string;
  content: string;
  keywords: string[];
  last_verified: string;
  sources: string[];
  key_facts: string[];
  practice_hints: string[];
}

/** What the model is asked to return for a reviewed topic. */
export interface ReviewResult {
  needs_update: boolean;
  update_type: 'none' | 'minor' | 'significant' | 'rewrite';
  confidence: 'high' | 'medium' | 'low';
  changes_summary: string;
  suggested_content: string;
  in_practice_content: string;
  practice_sources: Array<{ name: string; type: string; date: string }>;
  official_sources_checked: string[];
  key_insights: string[];
}

export interface WebSource {
  url: string;
  title: string;
}

/** Anthropic Messages API response, narrowed to what we read. */
export interface MessagesResponse {
  content: ContentBlock[];
  stop_reason?: string;
  usage?: { input_tokens?: number; output_tokens?: number };
}

export type ContentBlock =
  | { type: 'text'; text: string }
  | { type: 'server_tool_use'; name?: string }
  | { type: 'web_search_tool_result'; content?: Array<{ url?: string; title?: string }> }
  | { type: string; [key: string]: unknown };

export interface AnthropicModel {
  id: string;
  display_name: string;
  created_at: string;
}
