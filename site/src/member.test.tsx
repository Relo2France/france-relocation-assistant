/**
 * The split that makes personalisation safe: the prerendered HTML is generic,
 * and member data appears only after hydration.
 *
 * If member data ever reaches the prerendered output it gets served to whoever
 * requests that URL next - a privacy failure and a caching failure at once.
 */
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PersonalLead, PersonalNext } from './components';
import { daysUntil } from './member';

describe('daysUntil', () => {
  it('counts forward to the move', () => {
    expect(daysUntil('2027-03-15', new Date('2026-09-07T00:00:00Z'))).toBe(189);
  });

  it('returns null once the date has passed, rather than a negative countdown', () => {
    expect(daysUntil('2026-01-01', new Date('2026-09-07T00:00:00Z'))).toBeNull();
  });

  it('survives a malformed date', () => {
    expect(daysUntil('not-a-date')).toBeNull();
  });
});

describe('without a member', () => {
  it('renders no personal lead at all', () => {
    const { container } = render(<PersonalLead topic="the visitor visa" />);
    expect(container.innerHTML).toBe('');
  });

  it('shows a preview that is plainly labelled, not passed off as their data', () => {
    render(<PersonalNext />);
    expect(screen.getByText(/preview · what members see here/i)).toBeInTheDocument();
    // The sample figures are decorative and hidden from assistive tech.
    expect(document.querySelector('[aria-hidden="true"]')).not.toBeNull();
  });
});

const DIST = resolve(process.cwd(), 'dist');
const built = existsSync(resolve(DIST, 'index.html'));
(built ? describe : describe.skip)('prerendered output', () => {
  const files = [
    'index.html',
    'guides/visitor-visa-requirements/index.html',
    'guides/tax-residency-rules/index.html',
  ];

  it('contains no member data', () => {
    for (const file of files) {
      const html = readFileSync(resolve(DIST, file), 'utf8');
      for (const leak of ['Kevin', 'Monsac', 'DAYS TO MONSAC', 'data-personal="lead"', 'data-personal="next"']) {
        expect(html.includes(leak), `${file} leaked "${leak}" into static HTML`).toBe(false);
      }
    }
  });

  it('still ships the generic preview, which is safe to cache', () => {
    const html = readFileSync(resolve(DIST, 'guides/visitor-visa-requirements/index.html'), 'utf8');
    expect(html).toContain('data-personal="preview"');
    expect(html).toContain('what members see here');
  });
});
