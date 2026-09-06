/**
 * Mirrors the PHP resolver harness, so both sides provably pick the same model.
 */
import { describe, expect, it } from 'vitest';
import { pickFromCatalogue, tierOf } from '../src/models';
import type { AnthropicModel } from '../src/types';

const catalogue: AnthropicModel[] = [
  { id: 'claude-opus-6-preview', display_name: 'Opus 6 Preview', created_at: '2026-08-01T00:00:00Z' },
  { id: 'claude-opus-5', display_name: 'Opus 5', created_at: '2026-04-01T00:00:00Z' },
  { id: 'claude-sonnet-5', display_name: 'Sonnet 5', created_at: '2026-03-01T00:00:00Z' },
  { id: 'claude-opus-4-8', display_name: 'Opus 4.8', created_at: '2026-01-10T00:00:00Z' },
  { id: 'claude-haiku-4-5', display_name: 'Haiku 4.5', created_at: '2025-10-01T00:00:00Z' },
  { id: 'claude-sonnet-4-20250514', display_name: 'Sonnet 4', created_at: '2025-05-14T00:00:00Z' },
];

describe('tierOf', () => {
  it('reads current naming', () => expect(tierOf('claude-opus-5')).toBe('opus'));
  it('reads legacy naming', () => expect(tierOf('claude-3-5-sonnet-20241022')).toBe('sonnet'));
  it('returns unknown for foreign ids', () => expect(tierOf('gpt-4')).toBe('unknown'));
});

describe('pickFromCatalogue', () => {
  it('takes the newest in the tier', () => {
    expect(pickFromCatalogue(catalogue, 'opus')).toBe('claude-opus-5');
    expect(pickFromCatalogue(catalogue, 'sonnet')).toBe('claude-sonnet-5');
    expect(pickFromCatalogue(catalogue, 'haiku')).toBe('claude-haiku-4-5');
  });

  it('never auto-adopts a preview model', () => {
    expect(pickFromCatalogue(catalogue, 'opus')).not.toBe('claude-opus-6-preview');
  });

  it('adopts a newer release with no code change', () => {
    const withNewer = [
      { id: 'claude-opus-7', display_name: 'Opus 7', created_at: '2026-09-01T00:00:00Z' },
      ...catalogue,
    ];
    expect(pickFromCatalogue(withNewer, 'opus')).toBe('claude-opus-7');
  });

  it('falls back through tiers when one is empty', () => {
    const noOpus = catalogue.filter((m) => tierOf(m.id) !== 'opus');
    expect(pickFromCatalogue(noOpus, 'opus')).toBe('claude-sonnet-5');
  });

  it('returns null when the catalogue is empty', () => {
    expect(pickFromCatalogue([], 'opus')).toBeNull();
  });
});
