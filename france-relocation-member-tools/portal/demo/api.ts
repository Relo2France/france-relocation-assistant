/**
 * The demo API: answers every request the portal makes to
 * /demo-api/fra-portal/v1/... from the invented fixtures, in the browser.
 * Unknown routes get an empty 200 so no screen shows an error state. Any
 * other request (the Vite dev server, Google Fonts) passes through.
 */
import * as data from './fixtures/data';

export const API_BASE = '/demo-api/fra-portal/v1';

type Handler = (match: RegExpMatchArray, body: Record<string, unknown>, url: URL) => unknown;

const GET: [RegExp, Handler][] = [
  [/^\/me$/, () => data.ME],
  [/^\/me\/settings$/, () => ({ email_notifications: true, task_reminders: true, weekly_digest: false, language: 'en', timezone: 'America/New_York', date_format: 'F j, Y' })],
  [/^\/dashboard$/, () => data.dashboard()],
  [/^\/projects$/, () => [data.project()]],
  [/^\/projects\/\d+$/, () => data.project()],
  [/^\/projects\/\d+\/tasks$/, () => data.tasks()],
  [/^\/projects\/\d+\/files$/, (_m, _b, url) => {
    const category = url.searchParams.get('category');
    return data.files().filter((f) => !category || f.category === category);
  }],
  [/^\/projects\/\d+\/(notes|activity|verify\/history)$/, () => []],
  [/^\/tasks\/(\d+)$/, (m) => data.tasks().find((t) => t.id === Number(m[1])) ?? {}],
  [/^\/tasks\/(\d+)\/checklist$/, (m) => ({ task_id: Number(m[1]), items: [] })],
  [/^\/profile$/, () => data.PROFILE],
  [/^\/profile\/completion$/, () => ({ percentage: 92, missing_fields: [] })],
  [/^\/checklists$/, () => ['visa-application', 'pre-departure', 'arrival'].map((t) => data.checklist(t))],
  [/^\/checklists\/([\w-]+)$/, (m) => data.checklist(m[1])],
  [/^\/family$/, () => data.family()],
  [/^\/family\/feature-status$/, () => ({ enabled: true, upgradeUrl: null, message: null })],
  [/^\/letters$/, () => data.letters()],
  [/^\/research\/saved$/, () => data.savedReports()],
  [/^\/chat\/categories$/, () => data.CHAT_CATEGORIES],
  [/^\/chat\/history$/, () => data.chatHistory()],
  [/^\/chat\/search$/, () => ({ results: [] })],
  [/^\/glossary$/, () => data.GLOSSARY],
  [/^\/support\/tickets$/, () => data.supportTickets()],
  [/^\/support\/tickets\/(\d+)$/, (m) => data.supportTicket(Number(m[1]))],
  [/^\/support\/unread-count$/, () => ({ count: 1 })],
  [/^\/guides$/, () => []],
];

const WRITE: [RegExp, Handler][] = [
  [/^\/tasks\/(\d+)\/status$/, (m, b) => data.updateTask(Number(m[1]), { status: b.status as never }) ?? {}],
  [/^\/tasks\/(\d+)$/, (m, b) => data.updateTask(Number(m[1]), b as never) ?? {}],
  [/^\/letters(\/.*)?$/, () => data.letters()],
  [/^\/chat$/, () => ({ success: true, message: 'This is a demo. Answers here are written from the knowledge base for signed-in members.', sources: [] })],
];

function json(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body ?? {}), { status, headers: { 'Content-Type': 'application/json' } });
}

export function installDemoApi(): void {
  const realFetch = window.fetch.bind(window);
  window.fetch = async (input: RequestInfo | URL, init?: RequestInit) => {
    const raw = typeof input === 'string' ? input : input instanceof URL ? input.href : input.url;
    const url = new URL(raw, window.location.href);
    if (url.origin !== window.location.origin || !url.pathname.startsWith(API_BASE)) {
      return realFetch(input, init);
    }
    const path = url.pathname.slice(API_BASE.length) || '/';
    const method = (init?.method ?? (input instanceof Request ? input.method : 'GET')).toUpperCase();
    let body: Record<string, unknown> = {};
    if (typeof init?.body === 'string') {
      try { body = JSON.parse(init.body); } catch { body = {}; }
    }
    const table = method === 'GET' ? GET : WRITE;
    // A short, steady delay so loading states resolve the way they do live.
    await new Promise((r) => setTimeout(r, 30));
    for (const [re, handler] of table) {
      const m = path.match(re);
      if (m) return json(handler(m, body, url));
    }
    // Anything else: an empty success, never an error screen.
    console.warn('[demo-api] unhandled', method, path);
    return json(method === 'GET' ? {} : { success: true });
  };
}
