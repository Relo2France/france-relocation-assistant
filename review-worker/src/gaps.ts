/**
 * Knowledge base gap drafting.
 *
 * WordPress notices when the knowledge base answered a member badly and
 * records it. Turning that into a proposed addition means web search and a
 * model call - minutes per gap - which is the same shape of job as the review
 * and fails on WP-Cron for the same reasons. So WordPress detects; the worker
 * drafts; a human still approves.
 */
import { extractJson, sendMessage } from './anthropic';
import type { Env, WebSource } from './types';

export interface Gap {
  id: string;
  type: 'coverage' | 'depth';
  questions: string[];
  count: number;
  relevance: number;
  /** Depth gaps only - the topic that matched but came up short. */
  category?: string;
  topic?: string;
  topic_name?: string;
  current_content?: string;
  /** Coverage gaps only - where a new topic could live. */
  categories?: string[];
  /** The matched topic has since been renamed or removed. */
  stale?: boolean;
}

interface DraftJson {
  suggested_content?: string;
  changes_summary?: string;
  key_insights?: string[];
  official_sources_checked?: string[];
  confidence?: string;
  suggested_category?: string;
  suggested_topic_key?: string;
  suggested_title?: string;
}

export interface GapOutcome {
  gap_id: string;
  type: string;
  ok: boolean;
  review_id?: string;
  is_new_topic?: boolean;
  error?: string;
  duration_ms: number;
  output_tokens?: number;
}

function authHeaders(env: Env): HeadersInit {
  return {
    authorization: `Bearer ${env.WP_SHARED_SECRET}`,
    'content-type': 'application/json',
  };
}

export async function fetchGaps(env: Env): Promise<Gap[]> {
  const response = await fetch(`${env.WP_BASE_URL}/wp-json/fra/v1/review/gaps`, {
    headers: authHeaders(env),
  });

  if (!response.ok) {
    throw new Error(`Could not read gaps: HTTP ${response.status}`);
  }

  const body = (await response.json()) as { gaps?: Gap[] };
  return body.gaps ?? [];
}

/** Report a draft, or a failure, back against the gap. */
async function postGapResult(
  env: Env,
  gapId: string,
  payload: Record<string, unknown>
): Promise<{ review_id?: string; is_new_topic?: boolean }> {
  const response = await fetch(`${env.WP_BASE_URL}/wp-json/fra/v1/review/gaps/${gapId}`, {
    method: 'POST',
    headers: authHeaders(env),
    body: JSON.stringify(payload),
  });

  const body = (await response.json()) as {
    review_id?: string;
    is_new_topic?: boolean;
    message?: string;
    code?: string;
  };

  if (!response.ok) {
    throw new Error(`Could not post draft: HTTP ${response.status} ${body.code ?? ''} ${body.message ?? ''}`.trim());
  }

  return body;
}

export function buildDepthPrompt(gap: Gap): string {
  const questions = gap.questions.join('\n- ');

  return `You maintain a knowledge base for Americans relocating to France.

Members asked these questions:
- ${questions}

Our existing topic "${gap.topic_name}" matched their question, but the answer still had to be drawn from outside sources - so this topic is missing something it should cover.

CURRENT TOPIC CONTENT:
\`\`\`
${gap.current_content ?? ''}
\`\`\`

Research the current official position using web search, then rewrite this topic so it answers those questions completely.

RULES:
- Keep everything already correct. This is an edit, not a replacement.
- Only state requirements you can confirm from an official source.
- Prefer service-public.fr, france-visas.gouv.fr and consulate sites.
- Note where requirements vary by consulate rather than picking one.
- Match the existing formatting: ** for headers, bullets for lists.

Respond with ONLY this JSON:
{"suggested_content": "the full updated topic text", "changes_summary": "one sentence on what was missing", "key_insights": ["what was added"], "official_sources_checked": ["service-public.fr"], "confidence": "high|medium|low"}`;
}

export function buildCoveragePrompt(gap: Gap): string {
  const questions = gap.questions.join('\n- ');
  const categories = (gap.categories ?? []).join(', ');

  return `You maintain a knowledge base for Americans relocating to France.

Members asked these questions and we have no topic covering them:
- ${questions}

Research the current official position using web search, then write a new knowledge base topic that answers them.

RULES:
- Only state requirements you can confirm from an official source.
- Prefer service-public.fr, france-visas.gouv.fr and consulate sites.
- Note where requirements vary by consulate rather than picking one.
- Write for Americans applying from the United States.
- 300-500 words. Use ** for headers and bullets for lists.
- suggested_topic_key must be lowercase with underscores.

Existing categories: ${categories}

Respond with ONLY this JSON:
{"suggested_category": "one of the existing categories", "suggested_topic_key": "short_key", "suggested_title": "Topic Title", "suggested_content": "the topic text", "changes_summary": "one sentence on what this covers", "key_insights": ["what it answers"], "official_sources_checked": ["service-public.fr"], "confidence": "high|medium|low"}`;
}

export async function draftGap(env: Env, gap: Gap): Promise<GapOutcome> {
  const started = Date.now();
  const base = { gap_id: gap.id, type: gap.type };

  // The topic this gap referred to is gone. Record it and move on rather than
  // spending five minutes researching an update to something that no longer
  // exists.
  if (gap.stale) {
    await postGapResult(env, gap.id, { error: 'The matched topic no longer exists in the knowledge base.' });
    return { ...base, ok: false, error: 'stale topic', duration_ms: Date.now() - started };
  }

  const isDepth = gap.type === 'depth';
  const prompt = isDepth ? buildDepthPrompt(gap) : buildCoveragePrompt(gap);

  let outcome;
  try {
    outcome = await sendMessage(env, {
      tier: env.MODEL_TIER,
      prompt,
      maxTokens: 32000,
      webSearchUses: 5,
      // Same reasoning as the review: continuing a truncated JSON object
      // rarely yields valid JSON, so fail loudly instead.
      continueOnTruncation: false,
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    await postGapResult(env, gap.id, { error: message }).catch(() => undefined);
    throw error;
  }

  const draft = extractJson<DraftJson>(outcome.text);

  if (!draft?.suggested_content) {
    const why = outcome.truncated
      ? 'Response hit max_tokens before the JSON was complete'
      : `No JSON object found in a ${outcome.text.length} character response`;
    await postGapResult(env, gap.id, { error: why }).catch(() => undefined);
    return { ...base, ok: false, error: why, duration_ms: Date.now() - started };
  }

  const target = resolveTarget(gap, draft);
  if (!target) {
    const why = 'The draft did not name a usable category or topic';
    await postGapResult(env, gap.id, { error: why }).catch(() => undefined);
    return { ...base, ok: false, error: why, duration_ms: Date.now() - started };
  }

  const posted = await postGapResult(env, gap.id, {
    category: target.category,
    topic: target.topic,
    topic_name: target.title,
    update_type: isDepth ? 'minor' : 'significant',
    confidence: draft.confidence ?? 'medium',
    changes_summary: draft.changes_summary ?? '',
    suggested_content: draft.suggested_content,
    key_insights: draft.key_insights ?? [],
    sources_checked: draft.official_sources_checked ?? [],
    web_sources: outcome.webSources as WebSource[],
    model_used: outcome.model,
  });

  return {
    ...base,
    ok: true,
    review_id: posted.review_id,
    is_new_topic: posted.is_new_topic,
    duration_ms: Date.now() - started,
    output_tokens: outcome.usage.output,
  };
}

/**
 * Where should this draft go?
 *
 * A depth gap already knows. A coverage gap proposes a home, but the category
 * must be one that exists - WordPress rejects anything else, and inventing
 * categories is not the worker's job.
 */
export function resolveTarget(
  gap: Gap,
  draft: DraftJson
): { category: string; topic: string; title: string } | null {
  if (gap.type === 'depth') {
    if (!gap.category || !gap.topic) return null;
    return { category: gap.category, topic: gap.topic, title: gap.topic_name ?? gap.topic };
  }

  const available = gap.categories ?? [];
  if (available.length === 0) return null;

  const proposed = (draft.suggested_category ?? '').toLowerCase().trim();
  const category = available.includes(proposed) ? proposed : available[0]!;

  const topic = (draft.suggested_topic_key ?? '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9_]/g, '_')
    .replace(/_+/g, '_')
    .replace(/^_|_$/g, '');

  if (!topic) return null;

  return {
    category,
    topic,
    title: draft.suggested_title ?? topic.replace(/_/g, ' '),
  };
}
