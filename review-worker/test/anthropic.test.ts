/**
 * The JSON recovery cases that caused 21 of 23 review failures in production:
 * with web search on, the model narrates around the JSON.
 */
import { describe, expect, it } from 'vitest';
import { extractJson, extractText, extractWebSources } from '../src/anthropic';
import type { ContentBlock } from '../src/types';

const text = (t: string): ContentBlock => ({ type: 'text', text: t });
const json = '{"needs_update":true,"changes_summary":"Fee rose to 99 EUR"}';

describe('extractText', () => {
  it('joins every text block, skipping tool blocks', () => {
    expect(
      extractText([
        text('Part one. '),
        { type: 'server_tool_use', name: 'web_search' },
        { type: 'web_search_tool_result', content: [] },
        text('Part two.'),
      ])
    ).toBe('Part one. Part two.');
  });
});

describe('extractJson', () => {
  it('parses bare JSON', () => {
    expect(extractJson<{ changes_summary: string }>(json)?.changes_summary).toBe('Fee rose to 99 EUR');
  });

  it('parses a fenced block', () => {
    expect(extractJson<{ needs_update: boolean }>('```json\n' + json + '\n```')?.needs_update).toBe(true);
  });

  it('recovers JSON buried in search narration', () => {
    const withProse = `I'll check service-public.fr for the current fee.\n\nBased on the results:\n\n${json}`;
    expect(extractJson<{ changes_summary: string }>(withProse)?.changes_summary).toBe('Fee rose to 99 EUR');
  });

  it('recovers JSON with prose on both sides of a fence', () => {
    expect(
      extractJson<{ needs_update: boolean }>('Let me check.\n\n```json\n' + json + '\n```\n\nThat covers it.')
        ?.needs_update
    ).toBe(true);
  });

  it('keeps nested objects intact', () => {
    const nested = '{"a":{"b":{"c":1}},"d":"}"}';
    expect(extractJson<{ a: { b: { c: number } } }>('Here:\n' + nested)?.a.b.c).toBe(1);
  });

  it('returns null for truncated JSON', () => {
    expect(extractJson('{"needs_update":true,"suggested_content":"The fee ')).toBeNull();
  });

  it('returns null for prose with no JSON', () => {
    expect(extractJson('I could not find current information.')).toBeNull();
  });
});

describe('extractWebSources', () => {
  it('collects the pages actually visited', () => {
    expect(
      extractWebSources([
        { type: 'web_search_tool_result', content: [{ url: 'https://service-public.fr/visa', title: 'Visa' }] },
        text('done'),
      ])
    ).toEqual([{ url: 'https://service-public.fr/visa', title: 'Visa' }]);
  });

  it('ignores an error result, which is an object not a list', () => {
    expect(
      extractWebSources([{ type: 'web_search_tool_result', content: { error_code: 'max_uses_exceeded' } } as ContentBlock])
    ).toEqual([]);
  });
});
