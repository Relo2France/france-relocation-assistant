/**
 * The 524 that broke the first live dry run: a review with web search runs
 * past Cloudflare's 125s proxy read timeout, and the edge returns a plain
 * text body that JSON.parse chokes on.
 */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { sendMessage } from '../src/anthropic';
import { readMessageStream } from '../src/stream';
import type { Env } from '../src/types';

const env = {
  ANTHROPIC_API_KEY: 'k',
  MODEL_TIER: 'sonnet',
  MODEL_CACHE: { get: async () => null, put: async () => undefined },
} as unknown as Env;

const sse = (frames: object[]) =>
  new Response(frames.map((f) => `data: ${JSON.stringify(f)}`).join('\n\n') + '\n\n', {
    status: 200,
    headers: { 'content-type': 'text/event-stream' },
  });

describe('readMessageStream', () => {
  it('reassembles text deltas into whole blocks', async () => {
    const out = await readMessageStream(
      sse([
        { type: 'message_start', message: { usage: { input_tokens: 42 } } },
        { type: 'content_block_start', index: 0, content_block: { type: 'text', text: '' } },
        { type: 'content_block_delta', index: 0, delta: { type: 'text_delta', text: 'Fee rose ' } },
        { type: 'content_block_delta', index: 0, delta: { type: 'text_delta', text: 'to 99 EUR.' } },
        { type: 'message_delta', delta: { stop_reason: 'end_turn' }, usage: { output_tokens: 7 } },
      ])
    );
    expect(out.content).toEqual([{ type: 'text', text: 'Fee rose to 99 EUR.' }]);
    expect(out.stop_reason).toBe('end_turn');
    expect(out.usage).toEqual({ input_tokens: 42, output_tokens: 7 });
  });

  it('keeps search result blocks alongside text', async () => {
    const out = await readMessageStream(
      sse([
        {
          type: 'content_block_start',
          index: 0,
          content_block: { type: 'web_search_tool_result', content: [{ url: 'https://service-public.fr', title: 'SP' }] },
        },
        { type: 'content_block_start', index: 1, content_block: { type: 'text', text: '' } },
        { type: 'content_block_delta', index: 1, delta: { type: 'text_delta', text: 'done' } },
        { type: 'message_delta', delta: { stop_reason: 'end_turn' } },
      ])
    );
    expect(out.content).toHaveLength(2);
    expect(out.content[1]).toEqual({ type: 'text', text: 'done' });
  });

  it('surfaces an error event mid-stream', async () => {
    await expect(
      readMessageStream(sse([{ type: 'error', error: { message: 'overloaded' } }]))
    ).rejects.toThrow(/overloaded/);
  });

  it('tolerates a frame it cannot parse', async () => {
    const body =
      'data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}\n\n' +
      'data: {not json\n\n' +
      'data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"ok"}}\n\n' +
      'data: {"type":"message_delta","delta":{"stop_reason":"end_turn"}}\n\n';
    const out = await readMessageStream(new Response(body, { status: 200 }));
    expect(out.content).toEqual([{ type: 'text', text: 'ok' }]);
  });
});

describe('non-JSON error bodies', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', async (input: RequestInfo | URL) => {
      const url = String(input instanceof Request ? input.url : input);
      if (url.includes('/v1/models')) {
        return new Response(JSON.stringify({ data: [], has_more: false }), { status: 200 });
      }
      // What Cloudflare's edge returns when the read timeout fires.
      return new Response('error code: 524\n', { status: 524 });
    });
  });
  afterEach(() => vi.unstubAllGlobals());

  it('reports the real status instead of a JSON parse error', async () => {
    await expect(sendMessage(env, { model: 'claude-sonnet-5', prompt: 'q' })).rejects.toThrow(
      /HTTP 524.*error code: 524/s
    );
  });
});
