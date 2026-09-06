/**
 * Reviewing one topic, end to end.
 */
import { extractJson, sendMessage } from './anthropic';
import { buildReviewPrompt } from './prompt';
import { findTopic, postSuggestion } from './wordpress';
import type { Env, ReviewResult } from './types';

export interface ReviewOutcome {
  category: string;
  topic: string;
  model: string;
  needs_update: boolean;
  posted: boolean;
  review_id?: string;
  changes_summary?: string;
  web_sources: number;
  truncated: boolean;
  continued: boolean;
  usage: { input: number; output: number };
  duration_ms: number;
}

export async function reviewTopic(
  env: Env,
  category: string,
  topicKey: string,
  options: { dryRun?: boolean } = {}
): Promise<ReviewOutcome> {
  const started = Date.now();

  const topic = await findTopic(env, category, topicKey);
  if (!topic) {
    throw new Error(`Unknown topic ${category}/${topicKey}`);
  }

  const outcome = await sendMessage(env, {
    tier: env.MODEL_TIER,
    prompt: buildReviewPrompt(topic),
    maxTokens: 8000,
    webSearchUses: 5,
    // Deliberately off: continuing a truncated JSON object rarely yields
    // valid JSON. Truncation is reported instead, and the run fails loudly.
    continueOnTruncation: false,
  });

  const result = extractJson<ReviewResult>(outcome.text);

  if (!result || typeof result.suggested_content !== 'string') {
    throw new Error(
      outcome.truncated
        ? 'Response hit max_tokens before the JSON was complete'
        : `No JSON object found in a ${outcome.text.length} character response`
    );
  }

  const base: ReviewOutcome = {
    category,
    topic: topicKey,
    model: outcome.model,
    needs_update: Boolean(result.needs_update),
    posted: false,
    changes_summary: result.changes_summary,
    web_sources: outcome.webSources.length,
    truncated: outcome.truncated,
    continued: outcome.continued,
    usage: outcome.usage,
    duration_ms: Date.now() - started,
  };

  if (!result.needs_update || options.dryRun) {
    return base;
  }

  const posted = await postSuggestion(env, {
    topic,
    result,
    webSources: outcome.webSources,
    model: outcome.model,
  });

  return { ...base, posted: true, review_id: posted.review_id, duration_ms: Date.now() - started };
}
