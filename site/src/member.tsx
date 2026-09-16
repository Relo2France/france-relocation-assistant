/**
 * Member context.
 *
 * The prerendered HTML is what a stranger and a crawler get: generic, cacheable,
 * identical for everyone. Personalisation happens only after hydration, from
 * data fetched in the browser.
 *
 * That split is not a compromise, it is the requirement. Member data in the
 * prerendered output would be served to whoever asked for that URL next -
 * a privacy failure and a caching failure at once. member.test.ts asserts the
 * built HTML contains none of it.
 */
import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';

export interface Member {
  firstName: string;
  destination: string;
  visaType: string;
  /** ISO date of the intended move. */
  moveDate: string;
  applicants: number;
  dossier: { ready: number; total: number };
  nextAction: { what: string; note: string };
}

/** Exported so tests can render the member view without a network. */
export const MemberContext = createContext<Member | null>(null);

/** Days until the move, or null once it is in the past. */
export function daysUntil(iso: string, now = new Date()): number | null {
  const target = new Date(`${iso}T00:00:00Z`);
  if (Number.isNaN(target.getTime())) return null;
  const days = Math.ceil((target.getTime() - now.getTime()) / 86_400_000);
  return days >= 0 ? days : null;
}

/**
 * A stand-in until the portal API is wired up. `?member=demo` lets the
 * personalised view be seen on staging without auth existing yet.
 */
const DEMO: Member = {
  firstName: 'Kevin',
  destination: 'Monsac',
  visaType: 'Visitor (non-working)',
  moveDate: '2027-03-15',
  applicants: 2,
  dossier: { ready: 4, total: 9 },
  nextAction: {
    what: 'Order your apostilles',
    note: 'Marriage and birth certificates. State processing runs to weeks.',
  },
};

async function loadMember(): Promise<Member | null> {
  if (typeof window === 'undefined') return null;

  if (new URLSearchParams(window.location.search).get('member') === 'demo') {
    return DEMO;
  }

  try {
    // WordPress only trusts the login cookie on a REST request that also
    // carries a nonce, and this static page has none baked in. Core's
    // rest-nonce action hands one out on the strength of the cookie alone;
    // signed out, it answers "0" and the member request would 401 anyway.
    const nonce = await (
      await fetch('/wp-admin/admin-ajax.php?action=rest-nonce', { credentials: 'same-origin' })
    ).text();
    if (!nonce || nonce === '0') return null;

    const response = await fetch('/wp-json/fra-portal/v1/site-member', {
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': nonce.trim() },
    });
    if (!response.ok) return null;
    return (await response.json()) as Member;
  } catch {
    // Signed out, offline, or the endpoint is not there yet. The generic page
    // is a perfectly good page - this must never break it.
    return null;
  }
}

export function MemberProvider({ children }: { children: ReactNode }) {
  const [member, setMember] = useState<Member | null>(null);

  useEffect(() => {
    let live = true;
    loadMember().then((m) => {
      if (live) setMember(m);
    });
    return () => {
      live = false;
    };
  }, []);

  return <MemberContext.Provider value={member}>{children}</MemberContext.Provider>;
}

/** null until loaded, and for everyone who is not signed in. */
export function useMember(): Member | null {
  return useContext(MemberContext);
}
