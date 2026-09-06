/**
 * Anthropic Messages client.
 *
 * Ports the behaviour proved out in FRA_Model_Resolver: retry once on a
 * retired model, downgrade the web search tool for older models, continue a
 * turn the server paused, continue an answer cut off at max_tokens, and dig
 * the JSON out of a response that also contains search narration.
 */
import { resolveModel, tierOf } from './models';
import { readMessageStream } from './stream';
import type { ContentBlock, Env, MessagesResponse, WebSource } from './types';

const MESSAGES_ENDPOINT = 'https://api.anthropic.com/v1/messages';
const API_VERSION = '2023-06-01';

export interface MessageOptions {
  model?: string;
  tier?: string;
  system?: string;
  prompt: string;
  maxTokens?: number;
  webSearchUses?: number;
  continueOnTruncation?: boolean;
  maxContinuations?: number;
}

export interface MessageOutcome {
  text: string;
  model: string;
  truncated: boolean;
  continued: boolean;
  webSources: WebSource[];
  usage: { input: number; output: number };
}

type Message = { role: 'user' | 'assistant'; content: string | ContentBlock[] };

export function extractText(blocks: ContentBlock[]): string {
  return blocks
    .filter((b): b is { type: 'text'; text: string } => b.type === 'text' && typeof (b as { text?: unknown }).text === 'string')
    .map((b) => b.text)
    .join('');
}

export function extractWebSources(blocks: ContentBlock[]): WebSource[] {
  const sources: WebSource[] = [];

  for (const block of blocks) {
    if (block.type !== 'web_search_tool_result') continue;
    const results = (block as { content?: Array<{ url?: string; title?: string }> }).content;
    // An error result is an object, not a list.
    if (!Array.isArray(results)) continue;
    for (const result of results) {
      if (result?.url) sources.push({ url: result.url, title: result.title ?? result.url });
    }
  }

  return sources;
}

/**
 * Recover a JSON object from a response that may also contain prose.
 *
 * With web search on, the model narrates between searches, so the text is
 * commentary *and* JSON and a plain parse fails.
 */
export function extractJson<T>(text: string): T | null {
  const trimmed = text.trim();
  if (!trimmed) return null;

  const fenced = trimmed.match(/```(?:json)?\s*(\{[\s\S]*\})\s*```/);
  if (fenced?.[1]) {
    try {
      return JSON.parse(fenced[1]) as T;
    } catch { /* fall through */ }
  }

  try {
    return JSON.parse(trimmed) as T;
  } catch { /* fall through */ }

  const start = trimmed.indexOf('{');
  const end = trimmed.lastIndexOf('}');
  if (start !== -1 && end > start) {
    try {
      return JSON.parse(trimmed.slice(start, end + 1)) as T;
    } catch { /* fall through */ }
  }

  return null;
}

export async function sendMessage(env: Env, options: MessageOptions): Promise<MessageOutcome> {
  let model = options.model ?? (await resolveModel(env, options.tier));
  let searchType = 'web_search_20260209';

  const messages: Message[] = [{ role: 'user', content: options.prompt }];
  const collected: ContentBlock[] = [];

  let modelRetried = false;
  let toolsRetried = false;
  let continuations = 0;
  let continued = false;
  let truncated = false;
  let inputTokens = 0;
  let outputTokens = 0;

  const maxContinuations = options.maxContinuations ?? 2;

  for (let turn = 0; turn < 10; turn++) {
    const payload: Record<string, unknown> = {
      model,
      max_tokens: options.maxTokens ?? 8000,
      messages,
    };
    if (options.system) payload.system = options.system;
    if (options.webSearchUses) {
      payload.tools = [{ type: searchType, name: 'web_search', max_uses: options.webSearchUses }];
    }

    // Stream. A review with web search runs past Cloudflare's 125s proxy read
    // timeout in front of api.anthropic.com; streaming keeps bytes flowing so
    // the read timeout never fires.
    payload.stream = true;

    const response = await fetch(MESSAGES_ENDPOINT, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'x-api-key': env.ANTHROPIC_API_KEY,
        'anthropic-version': API_VERSION,
      },
      body: JSON.stringify(payload),
    });

    let body: MessagesResponse & { error?: { type?: string; message?: string } };

    if (!response.ok) {
      // An error response is JSON; anything else (an edge timeout page, say)
      // must not be fed to JSON.parse - that turns a clear failure into a
      // baffling "Unexpected token" message.
      const raw = await response.text();
      try {
        body = JSON.parse(raw) as typeof body;
      } catch {
        throw new Error(
          `Anthropic returned HTTP ${response.status}: ${raw.slice(0, 200).trim() || '(empty body)'}`
        );
      }
    } else {
      body = await readMessageStream(response);
    }

    if (body.error) {
      const message = body.error.message ?? 'Unknown API error';

      // The model is gone. Refresh the catalogue and try its replacement.
      if (!modelRetried && isModelError(body.error.type, message)) {
        modelRetried = true;
        const replacement = await resolveModel(env, tierOf(model));
        if (replacement !== model) {
          model = replacement;
          continue;
        }
      }

      // This model is too old for the current web search tool.
      if (!toolsRetried && options.webSearchUses && isToolError(message)) {
        toolsRetried = true;
        searchType = 'web_search_20250305';
        continue;
      }

      throw new Error(`Anthropic API error: ${message}`);
    }

    if (!Array.isArray(body.content)) {
      throw new Error('Unexpected Anthropic response shape');
    }

    collected.push(...body.content);
    inputTokens += body.usage?.input_tokens ?? 0;
    outputTokens += body.usage?.output_tokens ?? 0;

    if (body.stop_reason === 'refusal') {
      throw new Error('The model declined to answer this request');
    }

    // Server-side tools paused the turn - send it straight back.
    if (body.stop_reason === 'pause_turn') {
      messages.push({ role: 'assistant', content: body.content });
      continue;
    }

    // Cut off mid-sentence: ask for the rest instead of serving a fragment.
    if (
      body.stop_reason === 'max_tokens' &&
      options.continueOnTruncation &&
      continuations < maxContinuations
    ) {
      continuations++;
      continued = true;
      messages.push({ role: 'assistant', content: body.content });
      messages.push({
        role: 'user',
        content:
          'Continue from exactly where you stopped. Do not repeat any text you have already written, do not restate the question, and do not add a preamble - just carry straight on.',
      });
      continue;
    }

    truncated = body.stop_reason === 'max_tokens';

    return {
      text: extractText(collected),
      model,
      truncated,
      continued,
      webSources: extractWebSources(collected),
      usage: { input: inputTokens, output: outputTokens },
    };
  }

  throw new Error('The model did not finish its response');
}

function isModelError(type: string | undefined, message: string): boolean {
  if (type === 'not_found_error') return true;
  const lower = message.toLowerCase();
  return (
    lower.includes('model') &&
    ['not found', 'does not exist', 'deprecated', 'retired', 'invalid'].some((s) => lower.includes(s))
  );
}

function isToolError(message: string): boolean {
  const lower = message.toLowerCase();
  return lower.includes('tool') && ['not supported', 'unsupported', 'invalid'].some((s) => lower.includes(s));
}
