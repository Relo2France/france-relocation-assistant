#!/usr/bin/env node
/**
 * Marketing screenshots of the portal with the invented demo household.
 *
 *   node demo/scripts/shoot.mjs [out_dir]      (from portal/; default demo/screens)
 *
 * Starts the demo dev server on 127.0.0.1:5199 if it is not already up,
 * drives headless Chrome over the DevTools protocol (exact viewport, 2x), waits
 * until fonts are loaded and no skeleton, spinner or "Loading" is left, then
 * saves each PNG. Stops whatever it started. No packages, no live API: every
 * request the portal makes is answered by demo/api.ts in the browser.
 */
import { spawn } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PORTAL = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const OUT = path.resolve(process.argv[2] ?? path.join(PORTAL, 'demo/screens'));
const CHROME = process.env.CHROME ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE = 'http://127.0.0.1:5199/demo/demo.html';
const SCALE = Number(process.env.SCALE ?? 2);
const PORT = 9333;

// Task ids follow fixtures/templates.json order from 201:
// 202 "Get the apostilles from the state", 206 "Write the cover letter".
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;
const SHOTS = [
  ['desktop-dashboard', 1440, 900, 'view=dashboard'],
  ['desktop-stage', 1440, 900, 'view=stage&stage=prepare'],
  ['desktop-step', 1440, 900, 'view=tasks&task=202'],
  ['desktop-deadlines', 1440, 900, 'view=deadlines'],
  ['desktop-documents', 1440, 900, 'view=documents'],
  ['desktop-chat', 1440, 900, 'view=chat'],
  ['desktop-family', 1440, 900, 'view=family'],
  ['desktop-messages', 1440, 900, 'view=messages'],
  ['phone-dashboard', 390, 844, 'view=dashboard'],
  ['phone-step', 390, 844, 'view=tasks&task=206'],
];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const up = async (url) => { try { return (await fetch(url)).ok; } catch { return false; } };
async function waitFor(fn, ms, what) {
  const end = Date.now() + ms;
  while (Date.now() < end) { if (await fn()) return; await sleep(250); }
  throw new Error(`timed out waiting for ${what}`);
}

const children = [];
function stopAll() { for (const c of children) { try { process.kill(-c.pid); } catch { try { c.kill(); } catch { /* gone */ } } } }
process.on('exit', stopAll);
process.on('SIGINT', () => process.exit(130));

if (!(await up(BASE))) {
  const vite = spawn('npx', ['vite', '--config', 'demo/vite.demo.config.ts'], { cwd: PORTAL, stdio: 'ignore', detached: true });
  children.push(vite);
  await waitFor(() => up(BASE), 30_000, 'the demo dev server');
}

const profile = mkdtempSync(path.join(tmpdir(), 'r2f-demo-chrome-'));
const chrome = spawn(CHROME, ['--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run', '--no-default-browser-check',
  `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, 'about:blank'], { stdio: 'ignore', detached: true });
children.push(chrome);
process.on('exit', () => { try { rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 }); } catch { /* Chrome still closing; the OS temp dir clears it */ } });
await waitFor(() => up(`http://127.0.0.1:${PORT}/json/version`), 20_000, 'Chrome');

function connect(wsUrl) {
  const ws = new WebSocket(wsUrl);
  let id = 0;
  const pending = new Map();
  ws.onmessage = (ev) => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) {
      const { resolve, reject } = pending.get(msg.id);
      pending.delete(msg.id);
      msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
    }
  };
  const ready = new Promise((r) => { ws.onopen = r; });
  const send = (method, params = {}) => new Promise((resolve, reject) => {
    const n = ++id;
    pending.set(n, { resolve, reject });
    ws.send(JSON.stringify({ id: n, method, params }));
  });
  return { ready, send, close: () => ws.close() };
}

// True once the page has settled: fonts in, no skeletons, spinners or "Loading".
const SETTLED = `(() => {
  if (document.fonts.status !== 'loaded') return false;
  const root = document.getElementById('fra-portal-root');
  if (!root || root.children.length === 0) return false;
  if (document.querySelector('.animate-pulse, .animate-spin, [role="status"][aria-label^="Loading"]')) return false;
  if (/\\bLoading(\\.\\.\\.|…)/.test(document.body.innerText)) return false;
  return true;
})()`;

mkdirSync(OUT, { recursive: true });
for (const [name, width, height, query] of SHOTS.filter(([n]) => !ONLY || ONLY.includes(n))) {
  const target = await (await fetch(`http://127.0.0.1:${PORT}/json/new?about:blank`, { method: 'PUT' })).json();
  const page = connect(target.webSocketDebuggerUrl);
  await page.ready;
  // mobile:false keeps the layout viewport at exactly this width. With mobile
  // emulation Chrome widens it to fit the wide task board behind the drawer.
  await page.send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: SCALE, mobile: false });
  await page.send('Page.enable');
  await page.send('Page.navigate', { url: `${BASE}?${query}` });
  let stable = 0;
  const end = Date.now() + 30_000;
  while (stable < 4 && Date.now() < end) {
    const { result } = await page.send('Runtime.evaluate', { expression: SETTLED, returnByValue: true });
    stable = result.value ? stable + 1 : 0;
    await sleep(250);
  }
  if (stable < 4) console.warn(`! ${name}: page did not settle, capturing anyway`);
  // Let drawer and fade transitions finish; drop the focus ring a drawer puts on its close button.
  await sleep(700);
  await page.send('Runtime.evaluate', { expression: 'document.activeElement && document.activeElement.blur && document.activeElement.blur()' });
  await sleep(150);
  const { data } = await page.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
  const file = path.join(OUT, `${name}.png`);
  writeFileSync(file, Buffer.from(data, 'base64'));
  console.log(file);
  page.close();
  await fetch(`http://127.0.0.1:${PORT}/json/close/${target.id}`).catch(() => {});
}
process.exit(0);
