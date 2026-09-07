import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * The portal and the public site are one product, so they must not drift
 * apart in colour.
 *
 * This reads the site's own token file rather than repeating hex values, so
 * changing a token there fails here instead of quietly leaving the portal on
 * last season's palette. Both files are read as text: tailwind.config.js is
 * plain JS with no type declarations, and importing it breaks `tsc`, which
 * the build runs before vite.
 */
const TOKENS = resolve(__dirname, '../../../../site/src/styles/tokens.css');
const CONFIG = resolve(__dirname, '../../tailwind.config.js');

const tokensCss = readFileSync(TOKENS, 'utf8');
const configJs = readFileSync(CONFIG, 'utf8');

/** The first definition of a token is the light palette on bare :root. */
function token(name: string): string {
  const match = tokensCss.match(new RegExp(`--${name}:\\s*(#[0-9a-fA-F]{6})`));
  if (!match) throw new Error(`token --${name} not found in ${TOKENS}`);
  return match[1]!.toLowerCase();
}

/** One shade out of a named palette in the Tailwind config. */
function shade(family: string, step: number): string {
  const block = configJs.match(new RegExp(`\\b${family}:\\s*\\{([\\s\\S]*?)\\}`));
  if (!block) throw new Error(`palette "${family}" not found in tailwind.config.js`);
  const match = block[1]!.match(new RegExp(`\\b${step}:\\s*'([^']+)'`));
  if (!match) throw new Error(`${family}-${step} not found`);
  return match[1]!.toLowerCase();
}

describe('brand palette', () => {
  it('uses the site vine as the portal primary', () => {
    expect(shade('primary', 500)).toContain(token('vine'));
    expect(shade('green', 600)).toBe(token('vine'));
  });

  it('uses the site honey as the accent and as warning', () => {
    expect(shade('accent', 500)).toContain(token('honey'));
    expect(shade('amber', 600)).toBe(token('honey'));
  });

  it('uses the site shell for the sidebar', () => {
    expect(configJs).toContain(`var(--portal-sidebar-bg, ${token('shell')})`);
  });

  it('uses the site ink for the darkest neutral', () => {
    expect(shade('gray', 900)).toBe(token('ink'));
  });

  it('leaves no stock Tailwind brand colours in the remapped palettes', () => {
    // The families the components already use by name. If one is reset to a
    // Tailwind default the portal goes half-green again, silently.
    const stock = ['#22c55e', '#16a34a', '#3b82f6', '#2563eb', '#f59e0b', '#1f2937', '#6b7280'];
    for (const family of ['gray', 'green', 'blue', 'amber', 'yellow', 'red', 'orange', 'purple']) {
      for (const step of [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]) {
        expect(stock, `${family}-${step}`).not.toContain(shade(family, step));
      }
    }
  });
});
