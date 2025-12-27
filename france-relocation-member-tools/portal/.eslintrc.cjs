/**
 * ESLint Configuration for React Portal
 *
 * Enforces TypeScript best practices, React patterns, accessibility, and code quality.
 */

module.exports = {
  root: true,
  env: {
    browser: true,
    es2020: true,
    node: true,
  },
  extends: [
    'eslint:recommended',
    'plugin:@typescript-eslint/recommended',
    'plugin:react/recommended',
    'plugin:react/jsx-runtime',
    'plugin:react-hooks/recommended',
    'plugin:jsx-a11y/recommended',
  ],
  ignorePatterns: [
    'dist',
    'node_modules',
    '../assets',
    '.eslintrc.cjs',
    'vite.config.ts',
    'tailwind.config.js',
    'postcss.config.js',
  ],
  parser: '@typescript-eslint/parser',
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
    project: ['./tsconfig.json'],
  },
  plugins: ['react-refresh', '@typescript-eslint', 'react', 'jsx-a11y'],
  settings: {
    react: {
      version: 'detect',
    },
  },
  rules: {
    // React Refresh - warn if component can't be fast-refreshed
    'react-refresh/only-export-components': [
      'warn',
      { allowConstantExport: true },
    ],

    // React - disable prop-types (we use TypeScript)
    'react/prop-types': 'off',

    // React - relaxed rules for existing codebase
    'react/no-unescaped-entities': 'warn',

    // Accessibility - set as warnings for existing codebase (upgrade to errors over time)
    'jsx-a11y/click-events-have-key-events': 'warn',
    'jsx-a11y/no-static-element-interactions': 'warn',
    'jsx-a11y/no-autofocus': 'warn',
    'jsx-a11y/label-has-associated-control': 'warn',
    'jsx-a11y/interactive-supports-focus': 'warn',
    'jsx-a11y/no-interactive-element-to-noninteractive-role': 'warn',

    // TypeScript - relaxed rules for pragmatic development
    '@typescript-eslint/no-unused-vars': [
      'warn',
      {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_',
        caughtErrorsIgnorePattern: '^_',
      },
    ],
    '@typescript-eslint/no-explicit-any': 'warn',
    '@typescript-eslint/explicit-function-return-type': 'off',
    '@typescript-eslint/explicit-module-boundary-types': 'off',
    '@typescript-eslint/no-non-null-assertion': 'warn',

    // General code quality
    'no-console': ['warn', { allow: ['warn', 'error'] }],
    'no-debugger': 'warn',
    'prefer-const': 'error',
    'no-var': 'error',
    'eqeqeq': ['error', 'always', { null: 'ignore' }],

    // Disable import sorting (too noisy for existing codebase)
    'sort-imports': 'off',

    // Allow escape characters in regex-like strings (common in markdown parsing)
    'no-useless-escape': 'off',
  },
  overrides: [
    // Test files - more relaxed rules
    {
      files: ['**/*.test.ts', '**/*.test.tsx', '**/*.spec.ts', '**/*.spec.tsx'],
      rules: {
        '@typescript-eslint/no-explicit-any': 'off',
        'no-console': 'off',
      },
    },
  ],
};
