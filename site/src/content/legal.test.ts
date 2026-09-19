import { describe, expect, it } from 'vitest';
import { LEGAL_PAGES, LEGAL_UPDATED } from './legal';
import { routes } from './guides';
import { resolveRoute } from '../router';
import { FAMILY_ADDON_NOTE, FAMILY_ADDON_ON_SALE, FAMILY_ADDON_PRICE, familyCopy } from './links';

describe('legal pages', () => {
  it('are routed, prerendered and in the sitemap', () => {
    for (const path of ['/terms/', '/privacy/', '/refund-policy/']) {
      expect(routes, path).toContain(path);
      const route = resolveRoute(path);
      expect(route, path).not.toBeNull();
      expect(route!.meta.noindex, path).toBeFalsy();
    }
  });

  it('carry a date and describe the service as it runs', () => {
    expect(LEGAL_UPDATED).toBe('18 September 2026');
    const all = JSON.stringify(LEGAL_PAGES);
    for (const fact of ['WordPress.com', 'Cloudflare', 'Stripe', 'Anthropic', 'passport numbers', 'Settings', 'not affiliated with the French government', '30-day money-back guarantee']) {
      expect(all, fact).toContain(fact);
    }
  });
});

describe('family add-on', () => {
  it('is on sale now that the MemberPress product exists (Family plan, product 560)', () => {
    expect(FAMILY_ADDON_ON_SALE).toBe(true);
  });

  it('says it is included during launch when switched off', () => {
    const copy = familyCopy(false);
    const text = Object.values(copy).join(' ');
    expect(text).not.toContain(FAMILY_ADDON_PRICE);
    expect(text).toMatch(/partner and up to four children/);
    expect(text).toMatch(/launch/);
  });

  it('brings the priced copy back when it goes on sale, without contradicting itself', () => {
    const copy = familyCopy(true);
    expect(copy.answer).toContain(`(${FAMILY_ADDON_PRICE}, ${FAMILY_ADDON_NOTE})`);
    expect(copy.answer).not.toMatch(/\.\)/);
    expect(copy.extras).not.toMatch(/no tier above/);
    expect(copy.extras).toContain('Family add-on');
  });
});
