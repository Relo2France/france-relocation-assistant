/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Match LaunchBay's color scheme
        primary: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e', // Main green
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        sidebar: {
          bg: '#0f172a',      // Dark navy
          hover: '#1e293b',
          active: '#334155',
          text: '#94a3b8',
          textActive: '#f8fafc',
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
