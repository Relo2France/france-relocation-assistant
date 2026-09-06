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

let queue: unknown[] = [];
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

beforeEach(() => {
  queue = [];
  posts = [];
  vi.stubGlobal('fetch', async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = String(input instanceof Request ? input.url : input);
    if (url.includes('/v1/models')) return reply(catalogue);
    posts.push(JSON.parse(String(init?.body ?? '{}')));
    return reply(queue.shift());
  });
});

afterEach(() => vi.unstubAllGlobals());

const text = (t: string) => ({ type: 'text', text: t });

describe('sendMessage', () => {
  it('retries on the replacement when a model is retired', async () => {
    queue = [
      { error: { type: 'not_found_error', message: 'model: claude-opus-6 not found' } },
      { content: [text('recovered')], stop_reason: 'end_turn' },
    ];
    const out = await sendMessage(env, { model: 'claude-opus-6', prompt: 'q' });
    expect(out.text).toBe('recovered');
    expect(out.model).toBe('claude-opus-5');
    expect(posts).toHaveLength(2);
  });

  it('downgrades the web search tool for older models', async () => {
    queue = [
      { error: { type: 'invalid_request_error', message: 'tool type web_search_20260209 is not supported' } },
      { content: [text('ok')], stop_reason: 'end_turn' },
    ];
    await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', webSearchUses: 5 });
    expect(post(0).tools[0].type).toBe('web_search_20260209');
    expect(post(1).tools[0].type).toBe('web_search_20250305');
  });

  it('continues a paused turn and concatenates the text', async () => {
    queue = [
      { content: [text('Part one. '), { type: 'server_tool_use' }], stop_reason: 'pause_turn' },
      {
        content: [
          { type: 'web_search_tool_result', content: [{ url: 'https://service-public.fr', title: 'SP' }] },
          text('Part two.'),
        ],
        stop_reason: 'end_turn',
      },
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', webSearchUses: 5 });
    expect(out.text).toBe('Part one. Part two.');
    expect(out.webSources).toHaveLength(1);
  });

  it('completes an answer cut off at max_tokens when asked to', async () => {
    queue = [
      { content: [text('for minor issues (colds')], stop_reason: 'max_tokens' },
      { content: [text(', sore throats) before a doctor.')], stop_reason: 'end_turn' },
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', continueOnTruncation: true });
    expect(out.text).toBe('for minor issues (colds, sore throats) before a doctor.');
    expect(out.continued).toBe(true);
    expect(out.truncated).toBe(false);
    expect(post(1).messages[2].content).toContain('Continue from exactly where you stopped');
  });

  it('reports truncation rather than continuing when told not to', async () => {
    queue = [{ content: [text('{"a":1')], stop_reason: 'max_tokens' }];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' });
    expect(out.truncated).toBe(true);
    expect(posts).toHaveLength(1);
  });

  it('bounds the continuation budget', async () => {
    queue = Array.from({ length: 6 }, () => ({ content: [text('x')], stop_reason: 'max_tokens' }));
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q', continueOnTruncation: true });
    expect(posts).toHaveLength(3);
    expect(out.truncated).toBe(true);
  });

  it('surfaces a refusal instead of returning empty text', async () => {
    queue = [{ content: [text('')], stop_reason: 'refusal' }];
    await expect(sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' })).rejects.toThrow(/declined/);
  });

  it('accumulates token usage across turns', async () => {
    queue = [
      { content: [text('a')], stop_reason: 'pause_turn', usage: { input_tokens: 100, output_tokens: 20 } },
      { content: [text('b')], stop_reason: 'end_turn', usage: { input_tokens: 150, output_tokens: 30 } },
    ];
    const out = await sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' });
    expect(out.usage).toEqual({ input: 250, output: 50 });
  });
});
