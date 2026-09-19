# Schengen app inside Relo2France: integration requirements

**For:** the team building the standalone Schengen tracker (its own site and app).
**From:** Relo2France (relo2france.com), 19 September 2026.
**Supersedes:** the WordPress-integration sections of `SCHENGEN-APP-HANDOFF.md`, which assumed the tracker would share Relo2France's WordPress backend. It will not: the tracker is its own service with its own data, and Relo2France embeds it.

## 1. What we want

A Relo2France member opens **Schengen days** in the member portal and sees the tracker immediately:

- already signed in, with no login screen, no password and no second account to create;
- showing their own trips and their household;
- looking like part of the portal, not a website inside a website;
- free for as long as their Relo2France membership is active.

The tracker is also sold on its own site to people who are not Relo2France members. Relo2France members are one channel into it.

## 2. How it fits together

```
Member's browser
 └─ relo2france.com/portal/?view=schengen          (Relo2France portal, React)
     └─ <iframe src="https://APP/embed/relo2france">  (the tracker's embed mode)
          ▲  postMessage: token, theme, resize, events
          │
Relo2France WordPress ──signed token (JWT)──► tracker backend verifies with our public key
Relo2France WordPress ──signed webhooks─────► tracker backend (membership ended, account deleted)
Relo2France WordPress ◄─server-to-server─── tracker backend: day-count summary for our alerts
```

**Relo2France is the identity provider** for its members. The tracker trusts a short-lived token that Relo2France signs, and keeps its own account for the member, keyed on our user ID.

### Why an iframe and tokens, not cookies

- An iframe keeps the tracker's code, styles and data fully separate from ours. Neither side can break the other.
- Browsers block third-party cookies in iframes (Safari always, Chrome increasingly). **Do not rely on cookies inside the embed.** The session must come from the token we pass, held in memory or in the iframe's own (partitioned) storage.
- A JavaScript widget loaded into our page was considered and rejected: it would share our page, our CSP and our styles, and one side's update could break the other.

## 3. Sign-in: the token

### 3.1 How the member gets signed in

1. The portal loads the iframe at `https://APP/embed/relo2france` with **no credentials in the URL**.
2. The embed page posts `{"type":"r2f:ready"}` to its parent.
3. The portal asks our backend for a token (`GET /wp-json/fra-portal/v1/schengen/embed-token`; Relo2France builds this).
4. The portal posts `{"type":"r2f:token","token":"<JWT>"}` into the iframe, targeting the tracker's exact origin.
5. The embed page sends the token to the tracker's backend. The backend verifies it (section 3.3) and returns a tracker session scoped to the embed.
6. Five minutes before the tracker session ends, or if a call is rejected, the embed posts `{"type":"r2f:token-expired"}`. The portal fetches a fresh token and posts it again. The member never sees this.

**Never put the token in a query string.** Tokens in URLs leak into logs, browser history and referrers.

### 3.2 The token (JWT)

Signed by Relo2France with **EdDSA (Ed25519)** or **RS256**, with a key ID. Our public keys are published at `https://relo2france.com/.well-known/r2f-jwks.json`.

```json
{
  "iss": "https://relo2france.com",
  "aud": "https://APP",
  "sub": "r2f:1234",
  "iat": 1790000000,
  "exp": 1790000300,
  "jti": "5b7f0c2e-…",
  "entitlement": "r2f_member",
  "name": "Jordan",
  "email": "jordan@example.com",
  "household": [
    { "id": "self",    "name": "Jordan", "role": "owner" },
    { "id": "p-88",    "name": "Sam",    "role": "partner" },
    { "id": "c-12",    "name": "Alex",   "role": "child", "birth_year": 2014 }
  ],
  "residence": {
    "status": "planning",
    "move_date": "2027-02-15",
    "french_long_stay_from": null
  },
  "locale": "en-US",
  "timezone": "America/New_York"
}
```

- `sub` is permanent and never reused. Key the tracker account on `(iss, sub)`, **never on email**; members change emails.
- `household` lists the people whose days the member tracks. The tracker counts days per person; the member can assign trips to one or more of them. The IDs are stable.
- `residence` exists because **days spent in France stop counting toward the 90/180 limit once the person holds a French long-stay visa or residence permit**. Days in other Schengen countries still count. `status` is one of `planning`, `visa_holder` or `resident`. `french_long_stay_from` is the date that status began, or `null`. The tracker must apply this rule, or members will see false "limit reached" warnings.
- `email` and `name` are for display and receipts only. The tracker may not use them for marketing unless the member separately opts in on the tracker's own site.
- Tokens live **5 minutes**. Each `jti` is accepted once.

### 3.3 What the tracker's backend must check

- The signature, against our JWKS (cache it; refetch when an unknown `kid` appears).
- `iss` equals `https://relo2france.com`, and `aud` equals the tracker's own origin.
- `exp` is in the future and `iat` is not in the future, allowing up to 60 seconds of clock skew.
- The `jti` has not been seen before; keep seen `jti`s for 10 minutes.
- `entitlement` equals `r2f_member`. Anything else is refused with a clear message.

### 3.4 The first visit (account linking)

- **No tracker account for `(iss, sub)`:** create one silently from the token. No sign-up form, no email verification, no password.
- **The member already bought the tracker on its own site with the same email:** do **not** merge automatically. Show a one-time "You already have an account; link it?" prompt that proves ownership with an emailed code. Linking stops their standalone billing, because Relo2France now covers them.
- **Later, outside Relo2France:** a member can reach the same account on the tracker's own site or app, for example with "Sign in with Relo2France" (the same token flow, as an OAuth-style redirect) or by setting a password. That is the tracker team's call. It must land on the same account.

## 4. Entitlement: free while a member

- The token is the source of truth at each visit: `entitlement: r2f_member` means free, full access.
- Relo2France also sends **signed webhooks** so access can end between visits:

| Event | When | What the tracker does |
|---|---|---|
| `member.deactivated` | Membership refunded, cancelled or expired | End free access. Keep the data 90 days, then offer the standalone plan. |
| `member.reactivated` | Membership restored | Restore free access. |
| `member.deleted` | Member deleted their Relo2France account | Delete the tracker account and its data within 30 days, unless the member linked a paid standalone account. |
| `household.updated` | Partner or children added, removed or renamed | Update the household list. Never delete trips because a person was removed; hide them instead. |

- Webhooks are `POST https://APP/webhooks/relo2france` with a JSON body. Each carries an `X-R2F-Signature` header: an HMAC-SHA256 of the raw body with a shared secret, plus a timestamp. Reject anything older than 5 minutes. Treat events as idempotent by `event_id`.
- The tracker never charges a Relo2France member while `r2f_member` holds. No upsells inside the embed.

## 5. The embed page (`/embed/relo2france`)

### 5.1 What it shows

- Only the tracker: trips, the day counter, per-person status, planning ("can I take this trip?") and alerts.
- **None of these:**
  - the tracker's own site header, footer or navigation
  - marketing, pricing, upgrade prompts or app-store badges
  - cookie banners (it sets no tracking cookies)
  - login or sign-up screens
- An empty state that invites a first trip, not a tour of the product.
- A small "Open in the app" link to the tracker's own site or app is fine, taking the same account.

### 5.2 Fitting the portal

- **Width:** fluid, from 320px to 1200px. It must work at phone width (390px) with no horizontal scroll. Relo2France's portal is used on phones.
- **Height:** the iframe has no scroll of its own. The embed posts its content height whenever it changes (`{"type":"r2f:resize","height":1234}`) and the portal sizes the iframe to match, so the page scrolls as one.
- **Look:** on load, the portal posts `{"type":"r2f:theme", "tokens":{…}}`, and the embed applies it through CSS variables. Current values:

| Token | Value | Use |
|---|---|---|
| `--ground` | `#fcfcfb` | page background |
| `--card` | `#ffffff` | cards |
| `--card-2` | `#f4f6f4` | subtle panels |
| `--ink` | `#1c2420` | text |
| `--muted` | `#5f6e66` | secondary text |
| `--rule` | `#dde3de` | borders |
| `--vine` | `#2c5346` | primary actions, links |
| `--honey` | `#b87a21` | warnings (never prices or buttons) |
| `--danger` | `#b42318` | limit exceeded |
| fonts | Fraunces (headings), Karla (text) | Google Fonts |
| radius | 12px cards, 10px inputs, pill buttons | |

- No emoji as icons. Line icons at 16–20px, as in the portal.
- Colour must never be the only signal. Pair every status colour with words ("12 days left", "Over the limit").

### 5.3 Messages between the portal and the embed

Both sides check `event.origin` exactly and ignore anything else. The portal accepts messages only from `https://APP`; the embed only from `https://relo2france.com`.

| Direction | `type` | Payload | Meaning |
|---|---|---|---|
| embed → portal | `r2f:ready` | – | Loaded; send the token and theme |
| portal → embed | `r2f:token` | `token` | Sign in, or refresh the session |
| portal → embed | `r2f:theme` | `tokens` | Apply the portal's look |
| embed → portal | `r2f:token-expired` | – | Please send a fresh token |
| embed → portal | `r2f:resize` | `height` | Content height changed |
| embed → portal | `r2f:status` | `people:[{id, days_used, days_left, next_reset}]` | Latest counts, for the portal's alerts |
| embed → portal | `r2f:navigate` | `url` (https only, allow-listed) | Open a link in the top window |
| embed → portal | `r2f:error` | `code`, `message` | Show a portal-styled error with Retry |

### 5.4 Security headers on the embed

- `Content-Security-Policy: frame-ancestors https://relo2france.com` on `/embed/*`. Only our portal may frame it.
- `/embed/*` must never be framable by any other site.
- HTTPS only. No third-party analytics or trackers inside the embed.

## 6. Data we share, and the one-time move

- **Relo2France to the tracker:** only what is in the token and webhooks above. We send no passport numbers, documents or addresses.
- **The tracker to Relo2France:** only day-count summaries, for the portal's alerts ("You have 12 Schengen days left").
  - **Server to server:** we call `GET https://APP/api/r2f/summary?sub=r2f:1234`, authenticated with a signed JWT from Relo2France (`aud` = the tracker, scope `summary:read`).
  - **Response:** `people:[{id, days_used, days_left, window_end, status}]`.
- **Existing trips:** members already logged trips in the portal's current tracker. Those live in WordPress table `fra_schengen_trips`, with columns `user_id`, `start_date`, `end_date`, `country`, `category`, `notes`, `created_at`, `updated_at`.
  - **Import:** the tracker exposes `POST /api/r2f/import`, which accepts trips for a `sub` and de-duplicates by dates plus country.
  - **Handover:** Relo2France pushes each member's trips once, at launch.
  - **Retirement:** our old tracker then becomes read-only, and then retires.
- **Privacy:** the tracker is a data processor for Relo2France members. Needed before launch:
  - a data processing agreement
  - EU or US hosting stated in the tracker's privacy policy
  - deletion on request, as above
  - a record of the webhooks received

## 7. Failure behaviour

- If the tracker is down or slow (no `r2f:ready` within 8 seconds), the portal shows its own message, "The tracker isn't responding. Try again in a minute", with a Retry button. The embed must not render a raw error page.
- The member's session never ends mid-edit because a token expired. Refresh early, as in 3.1 step 6.
- A version mismatch in messages must fail safe. Ignore unknown `type`s; never throw.

## 8. What each side builds

**The tracker team:**

- [ ] `/embed/relo2france` page in embed mode (sections 5.1–5.4)
- [ ] JWT verification against our JWKS, with the checks in 3.3
- [ ] Silent account creation and one-time linking (3.4)
- [ ] Household and per-person counting, including the French-residence rule (3.2)
- [ ] Webhook endpoint (section 4)
- [ ] Summary API and import API (section 6)
- [ ] `frame-ancestors` header, and origin checks on every message

**Relo2France (we build this once the tracker's embed URL exists):**

- [ ] `GET /fra-portal/v1/schengen/embed-token`, which signs the token for the signed-in member (the household owner's `sub`, even when a partner is signed in)
- [ ] JWKS at `/.well-known/r2f-jwks.json`, with key rotation
- [ ] The portal's Schengen days view hosts the iframe and runs the message protocol (5.3)
- [ ] Webhooks fired on refund, cancellation, deletion and household changes
- [ ] The portal's alerts read the summary API
- [ ] A one-time trip export to `/api/r2f/import`, then the old tracker retires
- [ ] The Schengen app's URL and shared secrets stored in Portal Settings, never in code

## 9. Test plan before launch

1. A new member opens Schengen days and is signed in within 2 seconds, with no login screen.
2. A partner signed in to the household sees the same household in the tracker.
3. A token reused or replayed is refused, and an expired one is refreshed silently.
4. The embed framed by any site other than relo2france.com is refused.
5. A member refunded within 30 days loses free access within a minute of the webhook.
6. The page works at 390px, 768px and 1440px, with no double scrollbars.
7. A French resident's days in France do not count; their days in Italy do.
8. With the tracker down, the portal shows its own message and Retry works.
9. Deleting a Relo2France account deletes the tracker account, unless it was linked to a paid standalone account.
