/**
 * Covers the rendering faults reported from the live AI Guide Assistant:
 * a literal "---" on screen, wrapped prose broken into choppy fragments,
 * and inline markdown leaking as raw characters.
 */
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import MarkdownMessage from './MarkdownMessage';

describe('MarkdownMessage', () => {
  it('renders a rule as a section break, never as literal text', () => {
    const { container } = render(
      <MarkdownMessage content={'Some text.\n\n---\n\nMore text.'} />
    );
    expect(container.textContent).not.toContain('---');
    expect(container.querySelector('hr')).not.toBeNull();
  });

  it('absorbs a rule that precedes a heading into one section break', () => {
    const { container } = render(
      <MarkdownMessage content={'Body.\n\n---\n\n**In Practice**\n\n- A tip'} />
    );
    expect(container.textContent).not.toContain('---');
    expect(container.querySelectorAll('hr')).toHaveLength(0);
    expect(screen.getByText('In Practice')).toBeDefined();
  });

  it('joins wrapped lines into a single paragraph', () => {
    const { container } = render(
      <MarkdownMessage content={'French pharmacies are easily\nidentified by the green\ncross sign outside.'} />
    );
    const paras = container.querySelectorAll('p');
    expect(paras).toHaveLength(1);
    expect(paras[0].textContent).toBe('French pharmacies are easily identified by the green cross sign outside.');
  });

  it('renders every heading level, including h1', () => {
    const { container } = render(
      <MarkdownMessage content={'# Choosing a Mutuelle\n\n## Structure\n\n### Detail'} />
    );
    expect(container.textContent).not.toContain('#');
    expect(screen.getByText('Choosing a Mutuelle')).toBeDefined();
    expect(screen.getByText('Structure')).toBeDefined();
    expect(screen.getByText('Detail')).toBeDefined();
  });

  it('renders bold, italic and code without leaking markers', () => {
    const { container } = render(
      <MarkdownMessage content={'The **mutuelle** covers the *ticket modérateur* via `PUMA`.'} />
    );
    expect(container.querySelector('strong')?.textContent).toBe('mutuelle');
    expect(container.querySelector('em')?.textContent).toBe('ticket modérateur');
    expect(container.querySelector('code')?.textContent).toBe('PUMA');
    expect(container.textContent).not.toContain('*');
    expect(container.textContent).not.toContain('`');
  });

  it('keeps accented French text intact', () => {
    const { container } = render(
      <MarkdownMessage content={'Sécurité Sociale reimburses 70-80%.'} />
    );
    expect(container.textContent).toContain('Sécurité Sociale');
  });

  it('renders bullet and numbered lists', () => {
    const { container } = render(
      <MarkdownMessage content={'- First\n- Second\n\n1. One\n2. Two'} />
    );
    expect(container.querySelectorAll('ul li')).toHaveLength(2);
    expect(container.querySelectorAll('ol li')).toHaveLength(2);
  });

  it('folds a wrapped list item back into its bullet', () => {
    const { container } = render(
      <MarkdownMessage content={'- Pharmacists are highly trained\n  and play a clinical role.'} />
    );
    const items = container.querySelectorAll('ul li');
    expect(items).toHaveLength(1);
    expect(items[0].textContent).toContain('and play a clinical role.');
  });

  it('only linkifies http(s) targets', () => {
    const { container } = render(
      <MarkdownMessage content={'[safe](https://service-public.fr) and [bad](javascript:alert(1))'} />
    );
    const hrefs = Array.from(container.querySelectorAll('a')).map((a) => a.getAttribute('href'));
    expect(hrefs).toContain('https://service-public.fr');
    expect(hrefs).not.toContain('javascript:alert(1)');
  });
});
