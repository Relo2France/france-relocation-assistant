/**
 * Figures that must not drift.
 *
 * Each one is a number a reader acts on and that the knowledge base settled
 * after it had been wrong on this site: the June 2026 SMIC, the Talent
 * Passport thresholds of the Arrêté of August 21, 2025, and TLScontact having
 * replaced VFS Global in April 2025. If the KB moves one of them, change it
 * here on purpose, with the guide, in the same edit.
 */
import { describe, expect, it } from 'vitest';
import { guideBySlug, guides } from './guides';
import { HERO_STEPS } from './hero';

const text = (slug: string) => {
  const g = guideBySlug(slug);
  if (!g) throw new Error(`no guide ${slug}`);
  return [
    g.description,
    ...g.sections.flatMap((s) => [s.heading, ...(s.paragraphs ?? []), ...(s.requirements ?? []), s.caveat ?? '']),
    ...(g.practice?.paragraphs ?? []),
  ].join(' ');
};
const all = () => guides.map((g) => text(g.slug)).join(' ');

describe('SMIC since June 1, 2026 (KB visas/overview, visas/visitor)', () => {
  it('states the net and gross figures', () => {
    expect(text('digital-nomad-visa-france')).toContain('€1,477.93');
    expect(text('digital-nomad-visa-france')).toContain('€1,867.02');
    expect(text('digital-nomad-visa-france')).toContain('€17,700–€18,000');
    expect(text('visitor-visa-requirements')).toContain('€1,478/month');
  });

  it('carries no superseded SMIC figure', () => {
    for (const stale of ['€1,802', '€1,426', '€17,000–€18,000']) {
      expect(all(), stale).not.toContain(stale);
    }
  });
});

describe('Talent Passport thresholds (KB visas/talent, Arrêté of August 21, 2025)', () => {
  it('states the qualified-employee and EU Blue Card figures', () => {
    for (const slug of ['talent-passport', 'work-visa-salarie']) {
      expect(text(slug), slug).toContain('€39,582');
      expect(text(slug), slug).toContain('€59,373');
      expect(text(slug), slug).toContain('August 21, 2025');
    }
  });

  it('carries no withdrawn or pre-reform threshold, and no SMIC indexing', () => {
    for (const stale of ['€66,600', '€43,000', '€58,000', 'Young qualified professional', 'knowledge base currently carries']) {
      expect(all(), stale).not.toContain(stale);
    }
    expect(text('talent-passport')).not.toMatch(/are indexed to the SMIC|revised annually|1 January|January 1|every January/);
    expect(text('work-visa-salarie')).not.toMatch(/indexed to the French minimum wage/);
  });
});

describe('the US visa centre is TLScontact (KB visa_application_guide/tlscontact_info)', () => {
  it('never sends anyone to VFS Global', () => {
    // VFS may be named only as the operator TLScontact replaced.
    const text = all();
    expect((text.match(/VFS/g) ?? []).length).toBe((text.match(/replaced VFS Global/g) ?? []).length);
  });

  it('states the long-stay service fee and the changeover date', () => {
    expect(text('long-stay-visa-overview')).toContain('€220');
    expect(text('long-stay-visa-overview')).toContain('April 18, 2025');
  });
});

describe('other settled figures', () => {
  it('uses the sliding family-reunification scale, not the withdrawn 1.3× rule', () => {
    expect(text('spouse-and-family-visas')).not.toContain('1.3×');
    expect(text('spouse-and-family-visas')).toContain('€2,054');
  });

  it('gives students the Campus France step and the August 2026 resources floor', () => {
    expect(text('long-stay-visa-overview')).toContain('Études en France');
    expect(text('long-stay-visa-overview')).toContain('€877.50');
  });

  it('uses the KB visitor processing range', () => {
    expect(text('visitor-visa-requirements')).toContain('2–6 weeks');
  });

  it('counts the long-stay categories the same way everywhere', () => {
    expect(HERO_STEPS[0]!.note).toMatch(/^Five main/);
    expect(text('long-stay-visa-overview')).toContain('the five below');
  });

  it('keeps the owner\'s rule: no French translations for the first application', () => {
    // Kevin (owner), 2026-09-18: translations are needed after arrival and for
    // renewals, not for the first application at a US consulate. A KB gap is
    // raised to confirm; until it is answered, the guides follow this rule.
    const all = guides.map((g) => JSON.stringify(g)).join(' ');
    expect(all).not.toMatch(/certified French translations? (?:by a sworn translator )?(?:are|is) listed/i);
    expect(all).not.toMatch(/diplomas with certified French translations/i);
    expect(all).toMatch(/not for the first application/i);
  });
});
