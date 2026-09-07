/**
 * The site Worker.
 *
 * Two jobs. Static prerendered pages are served from the assets binding, and
 * everything WordPress still owns - the portal, sign-in, checkout, the REST
 * API and wp-content - is proxied through to the WordPress origin.
 *
 * The proxy is what makes the portal work at all. It authenticates with a
 * nonce injected server-side into the page and sent with `credentials:
 * "same-origin"`, so the moment the portal is served from a different origin
 * than the API, both halves break. Proxying keeps the browser seeing one
 * origin, which means the portal moves under the new domain with no change to
 * how it authenticates.
 */

/** Prefixes WordPress owns. Everything else is ours. */
const WORDPRESS_PATHS = [
  '/portal',
  '/login',
  '/logged-out',
  '/account',
  '/register',
  '/thank-you',
  '/about',
  '/my-travel-status',
  '/travel-status-test',
  '/wp-admin',
  '/wp-content',
  '/wp-includes',
  '/wp-json',
  '/wp-login.php',
  '/wp-cron.php',
  '/xmlrpc.php',
  '/feed',
];

export function isWordPressPath(pathname: string): boolean {
  return WORDPRESS_PATHS.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`) || pathname.startsWith(`${prefix}?`)
  );
}

interface Env {
  ASSETS: { fetch: (request: Request) => Promise<Response> };
  /** Origin that still runs WordPress, e.g. https://relo2france.com */
  WP_ORIGIN?: string;
}

async function proxy(request: Request, origin: string): Promise<Response> {
  const url = new URL(request.url);
  const target = new URL(url.pathname + url.search, origin);

  const headers = new Headers(request.headers);
  // Let fetch set Host from the target, and drop hop-by-hop hints that would
  // make WordPress serve a cached or differently-encoded body.
  headers.delete('host');
  headers.delete('accept-encoding');
  // WordPress uses these to build absolute URLs and to log the real client.
  headers.set('X-Forwarded-Host', url.host);
  headers.set('X-Forwarded-Proto', url.protocol.replace(':', ''));

  const upstream = await fetch(target.toString(), {
    method: request.method,
    headers,
    body: request.method === 'GET' || request.method === 'HEAD' ? undefined : request.body,
    redirect: 'manual',
  });

  // Rewrite redirects that point back at the origin so the browser stays on
  // this host - otherwise signing in bounces the user off the new domain.
  const response = new Response(upstream.body, upstream);
  const location = response.headers.get('location');
  if (location?.startsWith(origin)) {
    response.headers.set('location', location.slice(origin.length) || '/');
  }
  return response;
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const url = new URL(request.url);

    if (isWordPressPath(url.pathname)) {
      const origin = env.WP_ORIGIN;
      if (!origin) {
        return new Response('WP_ORIGIN is not configured', { status: 503 });
      }
      return proxy(request, origin.replace(/\/$/, ''));
    }

    return env.ASSETS.fetch(request);
  },
};
