import { describe, expect, it } from 'vitest';
import { GENERIC_HEADLINE, heroFor, monthsUntil, stepFor } from './hero';

const NOW = new Date('2026-09-16T12:00:00Z');

describe('the hero never claims what the site does not know', () => {
  it('is generic for a stranger: no month, no "you are here"', () => {
    expect(heroFor(null, NOW)).toEqual({ headline: GENERIC_HEADLINE, nowIndex: null });
  });

  it('is generic when the move date is unusable', () => {
    expect(heroFor({ moveDate: 'soon' }, NOW).nowIndex).toBeNull();
  });
});

describe('with a member', () => {
  it('names their month and marks the step they are on', () => {
    expect(heroFor({ moveDate: '2027-03-15' }, NOW)).toEqual({
      headline: 'You’re moving to France in March. Here’s what to do next.',
      nowIndex: 1,
    });
  });

  it('adds the year when the move is more than a year out', () => {
    expect(heroFor({ moveDate: '2028-03-15' }, NOW).headline).toContain('in March 2028');
    expect(heroFor({ moveDate: '2028-03-15' }, NOW).nowIndex).toBe(0);
  });

  it('turns into arrival once they have moved', () => {
    const h = heroFor({ moveDate: '2026-08-01' }, NOW);
    expect(h.headline).toMatch(/in France/);
    expect(h.nowIndex).toBe(3);
  });
});

describe('the step windows', () => {
  it('map months out onto the four steps', () => {
    expect(monthsUntil('2027-03-15', NOW)).toBe(6);
    expect([12, 10].map(stepFor)).toEqual([0, 0]);
    expect([9, 4].map(stepFor)).toEqual([1, 1]);
    expect([3, 1].map(stepFor)).toEqual([2, 2]);
    expect([0, -2].map(stepFor)).toEqual([3, 3]);
  });
});
