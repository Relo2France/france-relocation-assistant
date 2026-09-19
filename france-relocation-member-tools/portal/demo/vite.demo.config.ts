/**
 * Demo harness for marketing screenshots. Dev only.
 *
 * Serves demo/demo.html, which boots the real portal (src/main.tsx) against
 * invented fixture data answered in the browser. Nothing here reaches a live
 * API, and nothing here is part of `npm run build` (vite.config.ts builds
 * index.html only). If this config is ever built, it writes to demo/dist.
 *
 *   npx vite --config demo/vite.demo.config.ts        (from portal/)
 *   open http://localhost:5199/demo/demo.html
 */
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

const portal = path.resolve(__dirname, '..');

export default defineConfig({
  root: portal,
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(portal, 'src'),
      '@site-guides': path.resolve(portal, '../../site/src/content/guides.ts'),
    },
  },
  server: {
    port: 5199,
    strictPort: true,
    host: '127.0.0.1',
    open: false,
  },
  build: {
    outDir: path.resolve(__dirname, 'dist'),
    emptyOutDir: true,
    rollupOptions: { input: path.resolve(__dirname, 'demo.html') },
  },
});
