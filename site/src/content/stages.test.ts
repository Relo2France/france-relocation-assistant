import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { guides } from './guides';
import { STAGES, guidesByStage, stageOfGuide } from './stages';

describe('guides grouped by stage', () => {
  it('places every guide exactly once, and never hides one', () => {
    const seen = guidesByStage().flatMap((g) => g.guides.map((x) => x.slug));
    expect([...seen].sort()).toEqual(guides.map((g) => g.slug).sort());
  });

  it('lists only real slugs in the stage map', () => {
    const real = new Set(guides.map((g) => g.slug));
    for (const s of STAGES) for (const slug of s.slugs) expect(real.has(slug), `${s.id}: ${slug}`).toBe(true);
  });

  it('knows a guide’s stage', () => {
    expect(stageOfGuide('visitor-visa-requirements')?.id).toBe('prepare');
    expect(stageOfGuide('nope')).toBeUndefined();
  });
});

const DIST = resolve(process.cwd(), 'dist');
(existsSync(resolve(DIST, 'guides/index.html')) ? describe : describe.skip)('the built guides index', () => {
  it('links every guide exactly once', () => {
    const html = readFileSync(resolve(DIST, 'guides/index.html'), 'utf8');
    for (const g of guides) {
      // Only the cards: the footer lists a few guides too, and that is fine.
      const hits = html.match(new RegExp(`data-kind="guide-card" href="/guides/${g.slug}/"`, 'g')) ?? [];
      expect(hits.length, g.slug).toBe(1);
    }
  });
});
