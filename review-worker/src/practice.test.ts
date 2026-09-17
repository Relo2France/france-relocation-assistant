import { describe, expect, it } from 'vitest';
import { vetInPractice } from './practice';

describe('the In Practice gate', () => {
  it('lets a section through with two independent dated sources', () => {
    const v = vetInPractice('**In Practice**\n\nWaits run 6 weeks.', [
      { name: 'Reddit r/expats', type: 'forum', date: 'Jan 2026' },
      { name: 'FrenchEntrée forum', type: 'forum', date: 'Mar 2026' },
    ]);
    expect(v.withheld).toBe('');
    expect(v.content).toContain('Waits');
    expect(v.corroboration).toBe(2);
  });
  it('withholds one account, however well dated', () => {
    const v = vetInPractice('One person waited 9 months.', ['Reddit r/expats thread, Feb 2026', 'Reddit r/expats post, Feb 2026']);
    expect(v.content).toBe('');
    expect(v.withheld).toMatch(/only 1 independent/);
  });
  it('withholds undated sources', () => {
    const v = vetInPractice('People say X.', [{ name: 'a forum', type: 'forum', date: '' }, { name: 'a blog', type: 'blog', date: '' }]);
    expect(v.withheld).toMatch(/none dated/);
  });
  it('withholds a section with no sources at all', () => {
    expect(vetInPractice('People say X.', []).withheld).toMatch(/no community sources/);
  });
  it('is silent about an empty section', () => {
    const v = vetInPractice('', []);
    expect(v.withheld).toBe('');
    expect(v.content).toBe('');
  });
});
