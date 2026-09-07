/**
 * The rule these components exist to enforce: honey means "not law".
 *
 * In the current site an "In Practice" note and a legal requirement render
 * identically, so a Reddit consensus can read as a rule. If that distinction
 * ever collapses, these fail.
 */
import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import {
  Button, Dossier, Figure, GuideCard, PracticeNote, Requirement, Requirements, SourceChip, Timeline,
} from './index';

describe('the official / anecdotal distinction', () => {
  it('marks a practice note as anecdote, not requirement', () => {
    const { container } = render(<PracticeNote sources="r/expats">
      <p>Most Americans show 1.5x the benchmark.</p>
    </PracticeNote>);

    const note = container.querySelector('[data-kind="practice"]');
    expect(note).not.toBeNull();
    expect(note!.className).toContain('honey');
    expect(screen.getByText(/what people actually experience/i)).toBeInTheDocument();
    expect(screen.getByText('r/expats')).toBeInTheDocument();
  });

  it('never gives requirements the honey treatment', () => {
    const { container } = render(
      <Requirements><Requirement>A valid passport</Requirement></Requirements>
    );
    const list = container.querySelector('[data-kind="requirements"]')!;
    expect(list.className).not.toContain('honey');
    expect(list.innerHTML).not.toContain('honey');
  });

  it('colours source chips by how much they can be trusted', () => {
    const { container } = render(
      <>
        <SourceChip kind="official">service-public.fr</SourceChip>
        <SourceChip kind="community">r/expats</SourceChip>
      </>
    );
    const official = container.querySelector('[data-source="official"]')!;
    const community = container.querySelector('[data-source="community"]')!;
    expect(official.className).toContain('vine');
    expect(official.className).not.toContain('honey');
    expect(community.className).toContain('honey');
  });
});

describe('Figure', () => {
  it('sets numbers people will check twice in mono', () => {
    const { container } = render(<Figure>€1,478/month</Figure>);
    expect(container.firstElementChild!.className).toContain('font-mono');
  });
});

describe('Timeline', () => {
  const steps = [
    { when: '12 months out', what: 'Choose your visa' },
    { when: '6 months out', what: 'Order apostilles', now: true },
    { when: 'On arrival', what: 'Validate through ANEF' },
  ];

  it('marks where the reader is, for assistive tech too', () => {
    const { container } = render(<Timeline steps={steps} />);
    const current = container.querySelectorAll('[aria-current="step"]');
    expect(current).toHaveLength(1);
    expect(within(current[0] as HTMLElement).getByText(/you are here/i)).toBeInTheDocument();
  });

  it('renders every step in order', () => {
    render(<Timeline steps={steps} />);
    const items = screen.getByTestId('timeline').querySelectorAll('li');
    expect(items).toHaveLength(3);
    expect(items[0]!.textContent).toContain('Choose your visa');
    expect(items[2]!.textContent).toContain('Validate through ANEF');
  });
});

describe('Dossier', () => {
  const pieces = [
    { name: 'Passport', meta: 'ok', done: true },
    { name: 'Marriage certificate', meta: 'apostilled', done: true },
    { name: 'Financial resources', meta: '€1,478/mo' },
  ];

  it('counts what is actually ready', () => {
    render(<Dossier title="Visitor visa" pieces={pieces} />);
    expect(screen.getByTestId('dossier-count')).toHaveTextContent('2 / 3 ready');
  });

  it('does not rely on colour alone to show completion', () => {
    const { container } = render(<Dossier title="Visitor visa" pieces={pieces} />);
    expect(container.querySelectorAll('[data-done="true"]')).toHaveLength(2);
    expect(container.querySelectorAll('[data-done="false"]')).toHaveLength(1);
  });
});

describe('Button', () => {
  it('renders an anchor when given a destination', () => {
    render(<Button href="/start">Plan my move</Button>);
    expect(screen.getByRole('link', { name: 'Plan my move' })).toHaveAttribute('href', '/start');
  });

  it('uses the on-brand token so it stays legible in dark mode', () => {
    const { container } = render(<Button>Go</Button>);
    expect(container.firstElementChild!.className).toContain('text-on-brand');
  });
});

describe('GuideCard', () => {
  it('shows when to act, in honey, because it is a deadline not a rule', () => {
    const { container } = render(
      <GuideCard guide={{ when: '12 months out', title: 'Visitor visa', summary: 'The non-working route', href: '/g' }} />
    );
    const when = screen.getByText('12 months out');
    expect(when.className).toContain('honey');
    expect(container.querySelector('a')).toHaveAttribute('href', '/g');
  });
});
