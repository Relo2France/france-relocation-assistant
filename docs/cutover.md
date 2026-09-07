# Cutting relo2france.com over to the new site

## What this achieves

The Worker serves the prerendered public pages and proxies everything
WordPress still owns through the same hostname. The browser sees one origin,
so the member portal keeps working exactly as it does today — it authenticates
with a nonce injected server-side into the page and sent with `same-origin`
credentials, and neither half has to change.

This is also the portal "port". The portal ends up under the new domain with
no auth rewrite.

## The constraint that shapes everything

The site is WordPress.com **Atomic** (the `x-ac` response header contains
`_atomic_`), and the domain is registered at Automattic with nameservers
`ns1-3.wordpress.com`.

WordPress.com serves by `Host` header and 301s any other hostname back to the
primary domain — `relo2france.wordpress.com` redirects straight to
`relo2france.com`. So after cutover:

- Proxying to `relo2france.com` makes the Worker call itself.
- Proxying to `relo2france.wordpress.com` gets a 301 back to `relo2france.com`,
  which is the Worker. Same loop.

`resolveOverride` avoids both. It keeps the `Host` header as `relo2france.com`
so WordPress.com serves the right site with no redirect, while making the
connection to somewhere else. Cloudflare ignores it unless the target is in the
same zone, so the target must be a CNAME inside the zone pointing at the
outside host.

## Current DNS

See `dns-before-cutover.md`. The whole zone is four records, and **there are no
MX records** — no email is served from this domain, so a nameserver move cannot
break mail. That is the usual risk here and it does not apply.

## Steps

1. **Add the zone to Cloudflare** (do not change nameservers yet). Recreate:

   | Type  | Name | Value                                    | Proxy    |
   |-------|------|------------------------------------------|----------|
   | A     | @    | 192.0.78.222                             | DNS only |
   | A     | @    | 192.0.78.171                             | DNS only |
   | CNAME | www  | relo2france.com                          | DNS only |
   | TXT   | @    | `v=spf1 include:_spf.wpcloud.com ~all`   | —        |
   | CNAME | wp   | relo2france.wordpress.com                | DNS only |

   `wp` is the new one, and it is what `resolveOverride` targets. It must stay
   DNS-only: proxying it would send the Worker back through Cloudflare.

2. **Verify before switching.** Point staging at it and confirm the portal
   still works with no dependency on `relo2france.com`:

       npx wrangler deploy --var WP_RESOLVE_OVERRIDE:wp.relo2france.com

   `resolveOverride` only applies once the zone is on Cloudflare, so this step
   proves the record resolves and WordPress.com answers for it.

3. **Change the nameservers at Automattic** to the pair Cloudflare gives you.
   Propagation is usually minutes; WordPress.com says allow up to 72 hours.
   The site keeps serving from WordPress.com throughout, because the A records
   are unchanged and DNS-only.

4. **Point the apex at the Worker.** Add a Workers route for
   `relo2france.com/*` and `www.relo2france.com/*`, and set:

       WP_ORIGIN          = https://relo2france.com
       WP_RESOLVE_OVERRIDE = wp.relo2france.com

   Only now does the public site change. The portal, sign-in and checkout keep
   working through the proxy.

## Rollback

Remove the Workers route. The A records still point at WordPress.com, so the
old site returns immediately — no DNS change and no waiting for propagation.

## What is NOT covered

The WordPress homepage and theme keep their old styling. They stop being served
publicly at step 4, since the Worker answers `/` from the new site, but they
remain reachable for anything still routed to WordPress.
