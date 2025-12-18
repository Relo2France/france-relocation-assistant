/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Primary color - uses CSS variable from portal settings
        primary: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: 'var(--portal-primary, #22c55e)', // Main - from portal settings
          600: 'var(--portal-primary-dark, #16a34a)',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        // Secondary color - uses CSS variable from portal settings
        secondary: {
          500: 'var(--portal-secondary, #3b82f6)',
        },
        // Accent color - uses CSS variable from portal settings
        accent: {
          500: 'var(--portal-accent, #f59e0b)',
        },
        // Sidebar colors - use CSS variables from portal settings
        sidebar: {
          bg: 'var(--portal-sidebar-bg, #1f2937)',
          hover: 'var(--portal-sidebar-hover, #374151)',
          active: 'var(--portal-sidebar-active, #4b5563)',
          text: 'var(--portal-sidebar-text, #ffffff)',
          textActive: 'var(--portal-sidebar-text-active, #ffffff)',
        },
        // Header background
        header: {
          bg: 'var(--portal-header-bg, #ffffff)',
        },
        // Status colors
        status: {
          todo: '#6b7280',
          progress: '#3b82f6',
          waiting: '#f59e0b',
          done: '#22c55e',
        },
        // Priority colors
        priority: {
          low: '#6b7280',
          medium: '#3b82f6',
          high: '#f59e0b',
          urgent: '#ef4444',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
