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
import { Hero } from './pages/Home';
import { MemberContext, daysUntil, type Member } from './member';

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

/**
 * The home hero is two different cards. A first-time visitor must not be
 * shown a countdown that looks like theirs; a member must be sent to their
 * own file, not to the pricing page.
 */
describe('the home hero', () => {
  const member: Member = {
    firstName: 'Kevin', destination: 'Monsac', visaType: 'Visitor', moveDate: '2099-03-15',
    applicants: 2, dossier: { ready: 4, total: 9 }, nextAction: { what: 'x', note: 'y' },
  };

  it('shows a stranger an example, labelled as one, with nothing marked and no way into the portal', () => {
    const { container } = render(<Hero />);
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(/moving to France\?/i);
    expect(screen.getByText(/example · a move/i)).toBeInTheDocument();
    expect(container.querySelector('[aria-current="step"]')).toBeNull();
    expect(screen.getByRole('link', { name: /browse the guides/i })).toHaveAttribute('href', '/guides/');
    expect(screen.getByRole('link', { name: /plan my move/i })).toHaveAttribute('href', '/pricing/');
    expect(screen.queryByRole('link', { name: /dossier/i })).toBeNull();
  });

  it('shows a member their month, their place, and the way into their dossier', () => {
    const { container } = render(
      <MemberContext.Provider value={member}>
        <Hero />
      </MemberContext.Provider>
    );
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(/in March 2099/);
    expect(screen.getByText(/where you are/i)).toBeInTheDocument();
    expect(container.querySelector('[aria-current="step"]')).not.toBeNull();
    expect(screen.getByRole('link', { name: /open my dossier/i })).toHaveAttribute('href', expect.stringContaining('/portal/'));
    expect(screen.queryByRole('link', { name: /plan my move/i })).toBeNull();
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
      for (const leak of [
        'Kevin', 'Monsac', 'DAYS TO MONSAC', 'data-personal="lead"', 'data-personal="next"',
        // The hero may not tell a stranger when they are moving, or where they are.
        'moving to France in', 'you are here', 'data-personal="hero"', 'Open my dossier',
      ]) {
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
