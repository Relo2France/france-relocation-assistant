import { describe, expect, it } from 'vitest';
import { coverage, totalTopics } from './coverage';

describe('coverage', () => {
  it('adds up to the live knowledge base total', () => {
    // 42 topics as returned by GET /wp-json/fra/v1/review/topics on 2026-09-18.
    expect(totalTopics).toBe(42);
    expect(coverage.reduce((s, a) => s + a.topics, 0)).toBe(totalTopics);
  });

  it('describes every area, so no row reads as a bare number', () => {
    for (const area of coverage) {
      expect(area.examples.length, `${area.name} has no examples`).toBeGreaterThan(10);
      expect(area.topics).toBeGreaterThan(0);
    }
  });
});
