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

## Progress

- 2026-09-16: zone `45af56a8633c1f2f8fb08a36fc1764a8` created in the
  Kburrowbridge@gmail.com account (`2960d2b441564c5c9c8e93b6360d56bf`), Free
  plan, all seven records DNS-only. Assigned nameservers
  `clara.ns.cloudflare.com` and `sterling.ns.cloudflare.com`.
- 2026-09-16: nameservers moved at WordPress.com; zone active within minutes.
- 2026-09-16: override proven on `test.relo2france.com` routed at the staging
  Worker: pages from assets, portal and `wp-json` from WordPress, bad login
  rejected with cookies intact, category archives 301 to `/guides/`.
- 2026-09-16: **cut over.** `relo2france-site` deployed with the apex route,
  then the two apex A records flipped to Proxied. Same checks pass on
  `relo2france.com`; `www` still 301s to the apex via WordPress.com. The old
  sitemap's 23 URLs: 10 served by the new site, 9 proxied, 4 redirected.

## Steps

1. **Add the zone to Cloudflare** (do not change nameservers yet). Recreate:

   | Type  | Name | Value                                    | Proxy    |
   |-------|------|------------------------------------------|----------|
   | A     | @    | 192.0.78.222                             | DNS only |
   | A     | @    | 192.0.78.171                             | DNS only |
   | CNAME | www  | relo2france.com                          | DNS only |
   | CNAME | portal | lb.wordpress.com                       | DNS only |
   | TXT   | @    | `v=spf1 include:_spf.wpcloud.com ~all`   | —        |
   | TXT   | _dmarc | `v=DMARC1;p=none;`                     | —        |
   | CNAME | wp   | relo2france.wordpress.com                | DNS only |

   `wp` is the new one, and it is what `resolveOverride` targets. It must stay
   DNS-only: a proxied record resolves to Cloudflare's own edge, so the
   Worker's subrequest would arrive back at the apex route and loop.

2. **Change the nameservers at Automattic** to the pair Cloudflare gives you.
   Propagation is usually minutes; WordPress.com says allow up to 72 hours.
   The site keeps serving from WordPress.com throughout, because the A records
   are unchanged and DNS-only.

3. **Prove the override before touching the apex.** `resolveOverride` is
   ignored unless the Worker runs on the zone, so a deploy to the staging
   `workers.dev` address proves nothing: the override is silently dropped and
   the proxy quietly reaches `relo2france.com` by name, exactly as it does
   today. Instead, add a proxied placeholder record and route it at the
   staging Worker:

   | Type | Name | Value     | Proxy   |
   |------|------|-----------|---------|
   | A    | test | 192.0.2.1 | Proxied |

   Route `test.relo2france.com/*` to `relo2france-site-staging`, deployed
   with:

       WP_ORIGIN          = https://relo2france.com
       WP_RESOLVE_OVERRIDE = wp.relo2france.com

   Then check `https://test.relo2france.com/portal/` serves the portal and a
   sign-in round-trips. The Worker is now on the zone, so the override is
   honoured, and the `Host` header is still `relo2france.com`, so WordPress.com
   answers without a redirect. If this loops or 5xxs, the apex step would too.
   Remove the route and the record afterwards.

4. **Point the apex at the Worker.** Two things have to be true, and the
   order matters:

   a. Deploy the production Worker with the route `relo2france.com/*` and

          WP_ORIGIN          = https://relo2france.com
          WP_RESOLVE_OVERRIDE = wp.relo2france.com

      This changes nothing yet. The apex A records are DNS-only, so visitors
      still connect straight to WordPress.com and never reach Cloudflare's
      edge, where the route lives.

   b. Flip the two apex A records to **Proxied**. This is the cutover moment.
      Traffic now arrives at the edge, the route catches it, and the Worker
      answers.

   `www` stays DNS-only and gets no route. WordPress.com already 301s
   `www.relo2france.com` to the apex, and that keeps working because the
   `www` CNAME still resolves to WordPress.com directly.

   The portal, sign-in and checkout keep working through the proxy.

## The bug the checks missed

Every curl check passed and the portal 404ed in a browser. Cloudflare's asset
layer runs before the Worker by default, and for a browser navigation to a
path that is not an asset it answers with the 404 page itself; the Worker's
proxy never runs. Browsers send `Sec-Fetch-Mode: navigate` on document loads
and curl does not, which is the whole difference. `run_worker_first: true` in
the assets config fixes it, a test in `worker.test.ts` pins it, and any future
check of a proxied path must send that header:

    curl -H "Sec-Fetch-Mode: navigate" -H "Sec-Fetch-Dest: document" -I https://relo2france.com/portal/

The second one was `/_static`. WordPress.com concatenates CSS and JS into
`/_static/??...` bundles, and that prefix was not in the Worker's WordPress
path list, so every bundle came back as our 404 page and proxied pages
rendered unstyled. It hid for a day because the theme had a single
stylesheet and nothing to concatenate; adding a second one triggered the
bundling. To audit the list, take every same-origin URL the proxied pages
reference and check its first path segment is in `WORDPRESS_PATHS`:

    curl -s --resolve relo2france.com:443:<edge ip> -H "Sec-Fetch-Mode: navigate" https://relo2france.com/login/ \
      | grep -oE "https://relo2france\.com/[^\"'?# ]+" | awk -F/ '{print "/"$4}' | sort | uniq -c

Also: a home router can keep serving the pre-cutover addresses for a long
while after public resolvers have moved. Pin curl to the edge with
`--resolve relo2france.com:443:<edge ip>` (from `dig @clara.ns.cloudflare.com`)
before concluding anything about the Worker.

## After cutover

The WordPress theme, the plugin's auth cards and the portal were brought
onto the site's design the same day (commits d45ed72 through a8b20fa).
The theme loads a copy of the site's tokens, pinned identical by a test.
WordPress code deploys from `main` via the GitHub Sync plugin, which
polls every 15 minutes or runs from Tools → GitHub Sync in wp-admin.

The staging Worker keeps its `workers.dev` address and no route. The
`test.relo2france.com` A record and its route were removed once the apex
was verified. Wrangler must be logged into the Kburrowbridge@gmail.com
account for any of this; the cgparty.vote account is a different project and
sees none of these Workers or the zone.

## Rollback

Flip the two apex A records back to DNS-only. Visitors connect straight to
WordPress.com again, exactly as before step 4b. Cloudflare's own resolvers pick
the change up within a minute or two, and the values never changed, so there is
no propagation to wait out. Removing the route also works but is a worse
rollback: with the records still proxied, Cloudflare would then proxy to
WordPress.com itself, which is a state nothing here has tested.

## What is NOT covered

The WordPress homepage and theme keep their old styling. They stop being served
publicly at step 4, since the Worker answers `/` from the new site, but they
remain reachable for anything still routed to WordPress.
