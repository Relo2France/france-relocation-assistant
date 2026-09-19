/**
 * Demo entry: freeze the clock, set the globals WordPress would print, answer
 * the API from invented fixtures, then boot the real portal unchanged.
 */
import { API_BASE, installDemoApi } from './api';

// 1. The clock. The plan is dated relative to "today"; pin today to
//    September 19, 2026 so every regeneration shows the same stage and dates.
//    Time still moves forward from there, so timers and animations work.
const DEMO_NOW = Date.parse('2026-09-19T14:00:00Z');
const RealDate = Date;
const offset = DEMO_NOW - RealDate.now();
class DemoDate extends RealDate {
  constructor(...args: unknown[]) {
    if (args.length === 0) super(RealDate.now() + offset);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    else super(...(args as [any]));
  }
  static now(): number {
    return RealDate.now() + offset;
  }
}
(globalThis as unknown as { Date: DateConstructor }).Date = DemoDate as unknown as DateConstructor;

// 2. What template-portal.php prints before the bundle.
window.fraPortalData = {
  apiUrl: API_BASE,
  nonce: 'demo',
  userId: 1,
  siteUrl: 'http://localhost',
  pluginUrl: '',
  isAdmin: false,
  logoutUrl: '#',
};

const menu = [
  ['dashboard', 'Where you are', 'LayoutDashboard'],
  ['tasks', 'Steps', 'CheckSquare'],
  ['checklists', 'Checklists', 'ClipboardList'],
  ['timeline', 'Deadlines', 'Calendar'],
  ['messages', 'Messages', 'MessageSquare'],
  ['chat', 'Ask about my case', 'Bot'],
  ['documents', 'Documents & files', 'FileText'],
  ['guides', 'Guides', 'BookOpen'],
  ['glossary', 'Glossary', 'BookMarked'],
  ['research', 'Explore France', 'MapPin'],
  ['schengen', 'Schengen days', 'Globe'],
  ['files', 'Files', 'FolderOpen'],
  ['profile', 'Profile', 'User'],
  ['family', 'Family', 'Users'],
  ['membership', 'Membership', 'CreditCard'],
  ['settings', 'Settings', 'Settings'],
  ['help', 'Help', 'HelpCircle'],
].map(([id, label, icon]) => ({ id, label, icon, path: `/${id}` }));

// The brand palette from FRAMT_Portal_Settings::BRAND.
window.PORTAL_SETTINGS = {
  colors: { primary: '#2c5346', secondary: '#5f6e66', accent: '#b87a21', sidebarBg: '#23332c', sidebarText: '#ffffff', headerBg: '#ffffff' },
  layout: { showWpHeader: false, showWpFooter: false, showPromoBanner: false, sidebarPosition: 'left', sidebarCollapsed: false },
  branding: { title: 'Members Portal', logoUrl: '' },
  features: { notifications: true, fileUpload: true },
  menu,
  customCss: '',
} as unknown as NonNullable<Window['PORTAL_SETTINGS']>;

window.PORTAL_USER = {
  id: 1,
  email: 'jordan@example.com',
  displayName: 'Jordan Ellis',
  firstName: 'Jordan',
  lastName: 'Ellis',
  avatar: '',
} as unknown as NonNullable<Window['PORTAL_USER']>;

window.PORTAL_API = { root: '/demo-api/', nonce: 'demo' } as unknown as NonNullable<Window['PORTAL_API']>;

// 3. No service worker in the demo.
if ('serviceWorker' in navigator) {
  Object.defineProperty(navigator.serviceWorker, 'register', {
    value: () => Promise.reject(new Error('demo: no service worker')),
  });
}

// 4. Screens open at the top. Live, the chat scrolls the latest answer to
//    the top of its pane; for a still picture the question should show too.
Element.prototype.scrollIntoView = function scrollIntoView() { /* demo: stay put */ };

installDemoApi();

// 5. The portal itself, untouched.
await import('../src/main.tsx');
