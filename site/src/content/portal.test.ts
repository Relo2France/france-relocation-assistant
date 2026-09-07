import { describe, expect, it } from 'vitest';
import { portalFeatures } from './portal';

describe('portal features', () => {
  /**
   * The site's whole argument is that the guides are trustworthy. Promising a
   * portal capability that does not exist would undo that faster than any
   * inaccuracy in the guides themselves.
   */
  it('contrasts each feature with what the free site already gives', () => {
    for (const f of portalFeatures) {
      expect(f.what.length, `${f.name} needs a real description`).toBeGreaterThan(60);
      expect(f.publicVersion.length, `${f.name} needs its free counterpart`).toBeGreaterThan(20);
      expect(f.publicVersion).not.toBe(f.what);
    }
  });

  it('does not over-promise on quantity', () => {
    // Six concrete things beats a wall nobody reads.
    expect(portalFeatures.length).toBeGreaterThanOrEqual(4);
    expect(portalFeatures.length).toBeLessThanOrEqual(8);
  });
});
