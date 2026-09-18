/**
 * Reviewing one topic, end to end.
 */
import { researchInPractice, vetInPractice } from './practice';
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
  web_search_errors: string[];
  practice_withheld?: string;
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
    // Generous because we stream: the old 8000 ceiling cut the JSON off
    // mid-object on a real topic. Streaming removes the timeout pressure that
    // made a small cap tempting, and unused headroom costs nothing.
    maxTokens: 32000,
    webSearchUses: 8,
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
    web_search_errors: outcome.webSearchErrors,
    truncated: outcome.truncated,
    continued: outcome.continued,
    usage: outcome.usage,
    duration_ms: Date.now() - started,
  };

  if (options.dryRun) {
    return base;
  }

  // An update nothing verified does not go to the queue. The run report
  // names it and WordPress emails it; the topic is checked again next week.
  if (result.needs_update && outcome.webSources.length === 0) {
    const codes = Array.from(new Set(outcome.webSearchErrors));
    throw new Error(
      `Update withheld: web search returned no results${codes.length ? ` (${codes.join(', ')})` : ''}, so the suggested change could not be verified`
    );
  }

  // The In Practice layer gets its own research call and search budget. The
  // official review's own attempt counts only when it already passes the bar;
  // otherwise a call that starts at the community sources takes over.
  let practice = vetInPractice(result.in_practice_content, result.practice_sources);
  let practiceSearchErrors: string[] = [];
  if (!practice.content) {
    try {
      const researched = await researchInPractice(env, { title: topic.name, official: result.suggested_content || topic.content, hints: topic.practice_hints ?? [] });
      practiceSearchErrors = researched.webSearchErrors;
      const vetted = vetInPractice(researched.content, researched.sources);
      if (vetted.content || !practice.withheld) practice = vetted;
    } catch (error) {
      practiceSearchErrors = [error instanceof Error ? error.message : String(error)];
    }
  }

  // A verified In Practice section is an update in its own right, even when
  // the official text needed nothing.
  const practiceIsNew = !!practice.content && !topic.content.includes(practice.content.slice(0, 80));
  if (!result.needs_update && !practiceIsNew) {
    return { ...base, web_search_errors: [...base.web_search_errors, ...practiceSearchErrors], practice_withheld: practice.withheld || undefined };
  }
  if (!result.needs_update) {
    result.needs_update = true;
    result.update_type = 'minor';
    result.suggested_content = topic.content;
    result.changes_summary = 'In Practice section added from community research; official text unchanged.';
  }

  const posted = await postSuggestion(env, {
    topic,
    result: { ...result, in_practice_content: practice.content, practice_sources: practice.sources },
    practiceWithheld: practice.withheld,
    practiceCorroboration: practice.corroboration,
    webSources: outcome.webSources,
    model: outcome.model,
  });

  return { ...base, posted: true, review_id: posted.review_id, practice_withheld: practice.withheld || undefined, duration_ms: Date.now() - started };
}
