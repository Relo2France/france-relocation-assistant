/// <reference types="vitest" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import fs from 'fs';

// Plugin to copy PWA files after build
function copyPWAFiles() {
  return {
    name: 'copy-pwa-files',
    closeBundle() {
      const publicDir = path.resolve(__dirname, 'public');
      const outDir = path.resolve(__dirname, '../assets/portal');

      // Copy manifest.json
      if (fs.existsSync(path.join(publicDir, 'manifest.json'))) {
        fs.copyFileSync(
          path.join(publicDir, 'manifest.json'),
          path.join(outDir, 'manifest.json')
        );
      }

      // Copy service-worker.js
      if (fs.existsSync(path.join(publicDir, 'service-worker.js'))) {
        fs.copyFileSync(
          path.join(publicDir, 'service-worker.js'),
          path.join(outDir, 'service-worker.js')
        );
      }
    },
  };
}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react(), copyPWAFiles()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.{test,spec}.{ts,tsx}'],
  },
  build: {
    // Output to WordPress plugin assets directory
    outDir: '../assets/portal',
    emptyOutDir: true,
    // Generate manifest for WordPress to locate files
    manifest: true,
    // Optimize chunk size
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'index.html'),
      },
      output: {
        // Ensure consistent file names for WordPress enqueue
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'css/[name]-[hash][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
        // Manual chunk splitting for better caching
        manualChunks: {
          // Vendor chunks - rarely change, cached longer
          'vendor-react': ['react', 'react-dom'],
          'vendor-router': ['react-router-dom'],
          'vendor-query': ['@tanstack/react-query'],
          'vendor-virtual': ['@tanstack/react-virtual'],
          'vendor-utils': ['clsx', 'zustand'],
        },
      },
    },
  },
  // Use relative paths for WordPress compatibility
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    // For development with WordPress
    cors: true,
    port: 5173,
  },
});
