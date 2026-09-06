/**
 * WordPress client.
 *
 * The whole integration surface: read the topics, post back a suggestion.
 * WordPress keeps owning the content and the approval screen.
 */
import type { Env, ReviewResult, Topic, WebSource } from './types';

function authHeaders(env: Env): HeadersInit {
  return {
    authorization: `Bearer ${env.WP_SHARED_SECRET}`,
    'content-type': 'application/json',
  };
}

export async function fetchTopics(env: Env): Promise<Topic[]> {
  const url = `${env.WP_BASE_URL}/wp-json/fra/v1/review/topics`;
  const response = await fetch(url, { headers: authHeaders(env) });

  if (!response.ok) {
    throw new Error(`Could not read topics: HTTP ${response.status}`);
  }

  const body = (await response.json()) as { topics?: Topic[] };
  return body.topics ?? [];
}

export async function findTopic(env: Env, category: string, topicKey: string): Promise<Topic | null> {
  const topics = await fetchTopics(env);
  return topics.find((t) => t.category === category && t.topic_key === topicKey) ?? null;
}

export interface SuggestionPayload {
  topic: Topic;
  result: ReviewResult;
  webSources: WebSource[];
  model: string;
}

export async function postSuggestion(
  env: Env,
  { topic, result, webSources, model }: SuggestionPayload
): Promise<{ review_id: string; pending: number }> {
  const url = `${env.WP_BASE_URL}/wp-json/fra/v1/review/suggestions`;

  const response = await fetch(url, {
    method: 'POST',
    headers: authHeaders(env),
    body: JSON.stringify({
      category: topic.category,
      topic: topic.topic_key,
      topic_name: topic.name,
      update_type: result.update_type,
      confidence: result.confidence,
      changes_summary: result.changes_summary,
      suggested_content: result.suggested_content,
      in_practice_content: result.in_practice_content,
      practice_sources: result.practice_sources,
      key_insights: result.key_insights,
      sources_checked: result.official_sources_checked,
      web_sources: webSources,
      model_used: model,
    }),
  });

  const body = (await response.json()) as {
    review_id?: string;
    pending?: number;
    message?: string;
    code?: string;
  };

  if (!response.ok || !body.review_id) {
    throw new Error(
      `Could not post suggestion: HTTP ${response.status} ${body.code ?? ''} ${body.message ?? ''}`.trim()
    );
  }

  return { review_id: body.review_id, pending: body.pending ?? 0 };
}
