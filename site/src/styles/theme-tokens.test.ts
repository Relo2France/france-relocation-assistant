import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * The WordPress theme carries its own copy of tokens.css, because WordPress
 * cannot import from this package. Sign-in, checkout and the account page are
 * rendered by that theme, so if the two copies drift the site and its own
 * sign-in page stop matching - and nothing in either diff would show it,
 * since each file is internally consistent. This is the only thing that does.
 *
 * vitest runs in jsdom, where import.meta.url is not a file URL; cwd is the
 * site folder.
 */
describe('theme tokens', () => {
  it('are a byte-for-byte copy of the site tokens', () => {
    const site = readFileSync(resolve(process.cwd(), 'src/styles/tokens.css'), 'utf8');
    const theme = readFileSync(
      resolve(process.cwd(), '../relo2france-theme/assets/css/tokens.css'),
      'utf8'
    );
    expect(theme).toBe(site);
  });
});
