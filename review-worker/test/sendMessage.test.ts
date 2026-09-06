/**
 * The recovery ladder, mirroring the PHP harness so both sides behave alike.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { sendMessage } from '../src/anthropic';
import type { Env } from '../src/types';

const catalogue = {
  data: [
    { id: 'claude-opus-5', display_name: 'Opus 5', created_at: '2026-04-01T00:00:00Z' },
    { id: 'claude-sonnet-5', display_name: 'Sonnet 5', created_at: '2026-03-01T00:00:00Z' },
  ],
  has_more: false,
};

let queue: Response[] = [];
let posts: Array<Record<string, any>> = [];

/** posts[n] with the strict-index check satisfied. */
const post = (index: number): Record<string, any> => {
  const entry = posts[index];
  if (!entry) throw new Error(`no request was made at index ${index}`);
  return entry;
};

const env = {
  ANTHROPIC_API_KEY: 'test-key',
  MODEL_TIER: 'sonnet',
  MODEL_CACHE: {
    get: async () => catalogue.data,
    put: async () => undefined,
  },
} as unknown as Env;

const reply = (body: unknown) =>
  new Response(JSON.stringify(body), { status: 200, headers: { 'content-type': 'application/json' } });

/** An Anthropic error: non-200 with a JSON body, as the real API returns. */
const errorReply = (error: { type?: string; message?: string }) =>
  new Response(JSON.stringify({ error }), { status: 400, headers: { 'content-type': 'application/json' } });

/** A successful response, as the SSE stream the client now reads. */
const streamReply = (
  blocks: Array<Record<string, unknown>>,
  stopReason: string,
  usage?: { input_tokens?: number; output_tokens?: number }
) => {
  const frames: string[] = [
    `data: ${JSON.stringify({ type: 'message_start', message: { usage: { input_tokens: usage?.input_tokens ?? 0 } } })}`,
  ];

  blocks.forEach((block, index) => {
    if (block.type === 'text') {
      frames.push(
        `data: ${JSON.stringify({ type: 'content_block_start', index, content_block: { type: 'text', text: '' } })}`
      );
      frames.push(
        `data: ${JSON.stringify({ type: 'content_block_delta', index, delta: { type: 'text_delta', text: block.text } })}`
      );
    } else {
      frames.push(`data: ${JSON.stringify({ type: 'content_block_start', index, content_block: block })}`);
    }
  });

  frames.push(
    `data: ${JSON.stringify({ type: 'message_delta', delta: { stop_reason: stopReason }, usage: { output_tokens: usage?.output_tokens ?? 0 } })}`
  );

  return new Response(frames.join('\n\n') + '\n\n', {
    status: 200,
    headers: { 'content-type': 'text/event-stream' },
  });
};

beforeEach(() => {
  queue = [];
  posts = [];
  vi.stubGlobal('fetch', async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = String(input instanceof Request ? input.url : input);
    if (url.includes('/v1/models')) return reply(catalogue);
    posts.push(JSON.parse(String(init?.body ?? '{}')));
    return queue.shift() as Response;
  });
});

afterEach(() => vi.unstubAllGlobals());

const text = (t: string) => ({ type: 'text', text: t });

describe('sendMessage', () => {
  it('retries on the replacement when a model is retired', async () => {
    queue = [
      errorReply({ type: 'not_found_error', message: 'model: claude-opus-6 not found' }),
      streamReply([text('recovered')], 'end_turn'),
    ];
    const out = await sendMessage(env, { model: 'claude-opus-6', prompt: 'q' });
    expect(out.text).toBe('recovered');
    expect(out.model).toBe('claude-opus-5');
    expect(posts).toHaveLength(2);
  });

  it('downgrades the web search tool for older models', async () => {
    queue = [
      errorReply({ type: 'invalid_request_error', message: 'tool type web_search_20260209 is not supported' }),
      streamReply([text('ok')], 'end_turn'),
    ];
    await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', webSearchUses: 5 });
    expect(post(0).tools[0].type).toBe('web_search_20260209');
    expect(post(1).tools[0].type).toBe('web_search_20250305');
  });

  it('continues a paused turn and concatenates the text', async () => {
    queue = [
      streamReply([text('Part one. '), { type: 'server_tool_use' }], 'pause_turn'),
      streamReply(
        [
          { type: 'web_search_tool_result', content: [{ url: 'https://service-public.fr', title: 'SP' }] },
          text('Part two.'),
        ],
        'end_turn'
      ),
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', webSearchUses: 5 });
    expect(out.text).toBe('Part one. Part two.');
    expect(out.webSources).toHaveLength(1);
  });

  it('completes an answer cut off at max_tokens when asked to', async () => {
    queue = [
      streamReply([text('for minor issues (colds')], 'max_tokens'),
      streamReply([text(', sore throats) before a doctor.')], 'end_turn'),
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', continueOnTruncation: true });
    expect(out.text).toBe('for minor issues (colds, sore throats) before a doctor.');
    expect(out.continued).toBe(true);
    expect(out.truncated).toBe(false);
    expect(post(1).messages[2].content).toContain('Continue from exactly where you stopped');
  });

  it('reports truncation rather than continuing when told not to', async () => {
    queue = [streamReply([text('{"a":1')], 'max_tokens')];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' });
    expect(out.truncated).toBe(true);
    expect(posts).toHaveLength(1);
  });

  it('bounds the continuation budget', async () => {
    queue = Array.from({ length: 6 }, () => streamReply([text('x')], 'max_tokens'));
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', continueOnTruncation: true });
    expect(posts).toHaveLength(3);
    expect(out.truncated).toBe(true);
  });

  it('surfaces a refusal instead of returning empty text', async () => {
    queue = [streamReply([text('')], 'refusal')];
    await expect(sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' })).rejects.toThrow(/declined/);
  });

  it('accumulates token usage across turns', async () => {
    queue = [
      streamReply([text('a')], 'pause_turn', { input_tokens: 100, output_tokens: 20 }),
      streamReply([text('b')], 'end_turn', { input_tokens: 150, output_tokens: 30 }),
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' });
    expect(out.usage).toEqual({ input: 250, output: 50 });
  });
});
