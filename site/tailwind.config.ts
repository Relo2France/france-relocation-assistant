import type { Config } from 'tailwindcss';

/**
 * Tailwind reads the CSS custom properties rather than duplicating hex values,
 * so tokens.css stays the single source of truth and dark mode needs no
 * `dark:` variants - the variables swap underneath.
 */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        ground: 'var(--ground)',
        card: 'var(--card)',
        'card-2': 'var(--card-2)',
        shell: 'var(--shell)',
        ink: 'var(--ink)',
        muted: 'var(--muted)',
        'on-brand': 'var(--on-brand)',
        rule: 'var(--rule)',
        'rule-soft': 'var(--rule-soft)',
        vine: 'var(--vine)',
        'vine-soft': 'var(--vine-soft)',
        honey: 'var(--honey)',
        'honey-soft': 'var(--honey-soft)',
      },
      fontFamily: {
        display: 'var(--font-display)',
        ui: 'var(--font-ui)',
        read: 'var(--font-read)',
        mono: 'var(--font-mono)',
      },
      borderRadius: {
        DEFAULT: 'var(--radius-sm)',
        lg: 'var(--radius)',
        pill: 'var(--radius-pill)',
      },
      maxWidth: { measure: 'var(--measure)' },
    },
  },
  plugins: [],
} satisfies Config;
