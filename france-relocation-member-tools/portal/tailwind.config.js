/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {

        /*
         * Palette remap, not a rename.
         *
         * The components carry ~2,900 hardcoded utility classes - gray-700,
         * green-600, blue-50 and so on. Rewriting every call site would be a
         * huge diff with a real chance of missing some and shipping a portal
         * that is half green. Redefining the palettes themselves rebrands
         * every existing class at once, and keeps the semantic distinctions
         * the components rely on: green still reads as success, red as error,
         * amber as warning - just in this site's hues rather than Tailwind's.
         */

        // Neutrals, warmed toward the site's ink and rules.
        gray: {
          50: '#fafbfa', 100: '#f4f6f4', 200: '#ebefeb', 300: '#dde3de',
          400: '#b3beb7', 500: '#8a9992', 600: '#5f6e66', 700: '#44514a',
          800: '#2f3a34', 900: '#1c2420', 950: '#111815',
        },
        // Success, and the brand itself: vine.
        green: {
          50: '#f2f7f4', 100: '#e2ede7', 200: '#c2dbd0', 300: '#99c1b0',
          400: '#6ba28c', 500: '#46806c', 600: '#2c5346', 700: '#24443a',
          800: '#1d372f', 900: '#172b25', 950: '#0f1c17',
        },
        emerald: {
          50: '#f2f7f4', 100: '#e2ede7', 200: '#c2dbd0', 300: '#99c1b0',
          400: '#6ba28c', 500: '#46806c', 600: '#2c5346', 700: '#24443a',
          800: '#1d372f', 900: '#172b25', 950: '#0f1c17',
        },
        // Information: a teal that sits beside vine without merging into it.
        blue: {
          50: '#f0f6f6', 100: '#dcebeb', 200: '#b8d6d6', 300: '#8ababa',
          400: '#589a9a', 500: '#357c7c', 600: '#2a6363', 700: '#245050',
          800: '#1e4141', 900: '#193434', 950: '#0f2222',
        },
        sky: {
          50: '#f0f6f6', 100: '#dcebeb', 200: '#b8d6d6', 300: '#8ababa',
          400: '#589a9a', 500: '#357c7c', 600: '#2a6363', 700: '#245050',
          800: '#1e4141', 900: '#193434', 950: '#0f2222',
        },
        teal: {
          50: '#f0f6f6', 100: '#dcebeb', 200: '#b8d6d6', 300: '#8ababa',
          400: '#589a9a', 500: '#357c7c', 600: '#2a6363', 700: '#245050',
          800: '#1e4141', 900: '#193434', 950: '#0f2222',
        },
        cyan: {
          50: '#f0f6f6', 100: '#dcebeb', 200: '#b8d6d6', 300: '#8ababa',
          400: '#589a9a', 500: '#357c7c', 600: '#2a6363', 700: '#245050',
          800: '#1e4141', 900: '#193434', 950: '#0f2222',
        },
        // Warning: honey.
        amber: {
          50: '#fdf8ef', 100: '#fbf1df', 200: '#f5e0b8', 300: '#eaca8b',
          400: '#d8a95a', 500: '#c58c31', 600: '#b87a21', 700: '#94611a',
          800: '#754c15', 900: '#5e3d11', 950: '#3d280b',
        },
        yellow: {
          50: '#fdf8ef', 100: '#fbf1df', 200: '#f5e0b8', 300: '#eaca8b',
          400: '#d8a95a', 500: '#c58c31', 600: '#b87a21', 700: '#94611a',
          800: '#754c15', 900: '#5e3d11', 950: '#3d280b',
        },
        orange: {
          50: '#fdf6f0', 100: '#fbe9db', 200: '#f5cfb0', 300: '#eaad7c',
          400: '#dd8a4e', 500: '#c96c2c', 600: '#ab5622', 700: '#8a441c',
          800: '#6f3717', 900: '#5a2d13', 950: '#3a1d0c',
        },
        // Error: brick, warmer and less shrill than Tailwind red.
        red: {
          50: '#fdf4f2', 100: '#fae7e3', 200: '#f2c9c1', 300: '#e6a294',
          400: '#d47461', 500: '#c05038', 600: '#b4432f', 700: '#8f3425',
          800: '#742a1f', 900: '#5f241b', 950: '#3c1511',
        },
        rose: {
          50: '#fdf4f2', 100: '#fae7e3', 200: '#f2c9c1', 300: '#e6a294',
          400: '#d47461', 500: '#c05038', 600: '#b4432f', 700: '#8f3425',
          800: '#742a1f', 900: '#5f241b', 950: '#3c1511',
        },
        pink: {
          50: '#fdf4f2', 100: '#fae7e3', 200: '#f2c9c1', 300: '#e6a294',
          400: '#d47461', 500: '#c05038', 600: '#b4432f', 700: '#8f3425',
          800: '#742a1f', 900: '#5f241b', 950: '#3c1511',
        },
        // Categorical accents, muted into the same world.
        purple: {
          50: '#f6f4f8', 100: '#ece7f0', 200: '#d6cde0', 300: '#b8a9c8',
          400: '#9682ac', 500: '#78638f', 600: '#5f4e73', 700: '#4c3e5c',
          800: '#3e334a', 900: '#332a3d', 950: '#211b28',
        },
        violet: {
          50: '#f6f4f8', 100: '#ece7f0', 200: '#d6cde0', 300: '#b8a9c8',
          400: '#9682ac', 500: '#78638f', 600: '#5f4e73', 700: '#4c3e5c',
          800: '#3e334a', 900: '#332a3d', 950: '#211b28',
        },
        indigo: {
          50: '#f0f6f6', 100: '#dcebeb', 200: '#b8d6d6', 300: '#8ababa',
          400: '#589a9a', 500: '#357c7c', 600: '#2a6363', 700: '#245050',
          800: '#1e4141', 900: '#193434', 950: '#0f2222',
        },
        // Primary color - uses CSS variable from portal settings
        // Vine, the public site's primary. The scale is stepped around it so
        // primary-100 badges and primary-700 hovers stay in the same family -
        // leaving the old green tints here was what made the first rebrand
        // attempt look broken rather than restyled.
        primary: {
          50: '#f3f7f4',
          100: '#e7efea',
          200: '#c8ddd2',
          300: '#9cc0af',
          400: '#639a83',
          500: 'var(--portal-primary, #2c5346)',
          600: 'var(--portal-primary-dark, #23332c)',
          700: '#24443a',
          800: '#1d372f',
          900: '#172b25',
        },
        // Secondary color - uses CSS variable from portal settings
        secondary: {
          500: 'var(--portal-secondary, #5f6e66)',
        },
        // Accent color - uses CSS variable from portal settings
        accent: {
          100: '#fbf1df',
          500: 'var(--portal-accent, #b87a21)',
        },
        // Sidebar colors - use CSS variables from portal settings
        sidebar: {
          bg: 'var(--portal-sidebar-bg, #23332c)',
          hover: 'var(--portal-sidebar-hover, #2c3832)',
          active: 'var(--portal-sidebar-active, #2c5346)',
          text: 'var(--portal-sidebar-text, #ffffff)',
          textActive: 'var(--portal-sidebar-text-active, #ffffff)',
        },
        // Header background
        header: {
          bg: 'var(--portal-header-bg, #ffffff)',
        },
        // Surfaces, shared with the public site's tokens.
        ground: 'var(--portal-ground, #fcfcfb)',
        card: 'var(--portal-card, #ffffff)',
        'card-2': 'var(--portal-card-2, #f4f6f4)',
        ink: 'var(--portal-ink, #1c2420)',
        rule: 'var(--portal-rule, #dde3de)',

        // Status colors
        status: {
          todo: '#5f6e66',
          progress: '#2c5346',
          waiting: '#b87a21',
          done: '#639a83',
        },
        // Priority colors
        priority: {
          low: '#5f6e66',
          medium: '#2c5346',
          high: '#b87a21',
          urgent: '#b4432f',
        },
      },
      fontFamily: {
        sans: ['Karla', 'Helvetica Neue', 'Arial', 'system-ui', 'sans-serif'],
        display: ['Fraunces', 'Georgia', 'Times New Roman', 'serif'],
        mono: ['IBM Plex Mono', 'ui-monospace', 'SF Mono', 'Menlo', 'monospace'],
      },
    },
  },
  plugins: [],
}
