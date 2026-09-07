/**
 * Token integrity.
 *
 * The classic broken-artifact bug is a colour whose only definition lives
 * inside a dark-mode block: it silently fails in the un-stamped "system" state
 * most visitors are actually in. These tests make that impossible to ship.
 */
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

// Resolved from the project root: under jsdom, import.meta.url is not a file URL.
const css = readFileSync(resolve(process.cwd(), 'src/styles/tokens.css'), 'utf8');

/** Custom properties declared in a given block. */
function declaredIn(selector: string): Set<string> {
  const start = css.indexOf(selector);
  if (start === -1) return new Set();
  const open = css.indexOf('{', start);
  let depth = 0;
  let end = open;
  for (let i = open; i < css.length; i++) {
    if (css[i] === '{') depth++;
    else if (css[i] === '}') {
      depth--;
      if (depth === 0) { end = i; break; }
    }
  }
  const block = css.slice(open, end);
  return new Set([...block.matchAll(/(--[\w-]+)\s*:/g)].map((m) => m[1]!));
}

const light = declaredIn(':root {');
const systemDark = declaredIn(':root:not([data-theme="light"])');
const explicitDark = declaredIn(':root[data-theme="dark"]');

describe('tokens.css', () => {
  it('declares the full palette on bare :root', () => {
    for (const token of ['--ground', '--card', '--ink', '--muted', '--rule', '--vine', '--honey', '--on-brand']) {
      expect(light.has(token), `${token} missing from :root`).toBe(true);
    }
  });

  it('never introduces a colour that only exists in dark mode', () => {
    for (const token of [...systemDark, ...explicitDark]) {
      expect(light.has(token), `${token} is defined in a dark block but not on :root`).toBe(true);
    }
  });

  it('keeps both dark blocks in step, so the toggle matches the OS', () => {
    expect([...systemDark].sort()).toEqual([...explicitDark].sort());
  });

  it('guards the system-preference block against an explicit light choice', () => {
    expect(css).toContain(':root:not([data-theme="light"])');
  });

  it('defines a readable colour for text on the brand colour', () => {
    // Without this, a vine button renders white-on-pale-green in dark mode.
    expect(light.has('--on-brand')).toBe(true);
    expect(systemDark.has('--on-brand')).toBe(true);
  });
});
