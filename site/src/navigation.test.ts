/**
 * Link interception. Easy to get wrong in ways that quietly break cmd-click,
 * middle-click and downloads - the failures nobody reports, they just stop
 * using it.
 */
import { describe, expect, it } from 'vitest';
import { shouldIntercept } from './navigation';

const ORIGIN = 'https://relo2france.com';
const known = (p: string) => p === '/' || p === '/guides/' || p.startsWith('/guides/visitor');
const plainClick = { button: 0, metaKey: false, ctrlKey: false, shiftKey: false, altKey: false, defaultPrevented: false };

describe('shouldIntercept', () => {
  it('handles a plain click on an internal route', () => {
    expect(shouldIntercept(plainClick, { href: '/guides/' }, ORIGIN, known)).toBe(true);
  });

  it('leaves modified clicks to the browser', () => {
    for (const mod of ['metaKey', 'ctrlKey', 'shiftKey', 'altKey'] as const) {
      expect(
        shouldIntercept({ ...plainClick, [mod]: true }, { href: '/guides/' }, ORIGIN, known),
        `${mod} should open a new tab or window`
      ).toBe(false);
    }
  });

  it('leaves middle and right clicks alone', () => {
    expect(shouldIntercept({ ...plainClick, button: 1 }, { href: '/guides/' }, ORIGIN, known)).toBe(false);
    expect(shouldIntercept({ ...plainClick, button: 2 }, { href: '/guides/' }, ORIGIN, known)).toBe(false);
  });

  it('does not hijack an already-handled click', () => {
    expect(shouldIntercept({ ...plainClick, defaultPrevented: true }, { href: '/guides/' }, ORIGIN, known)).toBe(false);
  });

  it('leaves downloads and new-window links alone', () => {
    expect(shouldIntercept(plainClick, { href: '/f.pdf', hasDownload: true }, ORIGIN, known)).toBe(false);
    expect(shouldIntercept(plainClick, { href: '/guides/', target: '_blank' }, ORIGIN, known)).toBe(false);
  });

  it('never intercepts another origin', () => {
    expect(shouldIntercept(plainClick, { href: 'https://service-public.fr/x' }, ORIGIN, known)).toBe(false);
  });

  it('lets the browser handle routes this app cannot render', () => {
    // /start and /sign-in are real destinations that do not exist here yet.
    expect(shouldIntercept(plainClick, { href: '/start' }, ORIGIN, known)).toBe(false);
    expect(shouldIntercept(plainClick, { href: '/sign-in' }, ORIGIN, known)).toBe(false);
  });

  it('survives a malformed href', () => {
    expect(shouldIntercept(plainClick, { href: 'http://[' }, ORIGIN, known)).toBe(false);
  });
});
