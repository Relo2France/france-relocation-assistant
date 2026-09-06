# Relo2France Review Worker

Runs the knowledge base review on Cloudflare Workers instead of WP-Cron.

**Phase 2 — one topic, on demand.** No cron yet. This phase exists to prove the
prompt, the model resolution and the write-back against real traffic before
anything runs unattended.

## Why

The review is a 31-step job where any step can fail. On WP-Cron it took
2h37m (cron fires on page loads), lost a topic to a 600s timeout with no
retry, and could only be diagnosed from a summary email.

## The seam

The Worker owns **execution**. WordPress keeps owning **content and approval**.

    GET  /wp-json/fra/v1/review/topics       what to review
    POST /wp-json/fra/v1/review/suggestions  one suggested update

Suggestions land in `fra_pending_reviews` and wait for a human, exactly as
before. The Worker cannot publish to the knowledge base.

## Routes

All require `Authorization: Bearer $TRIGGER_SECRET`.

| Route | Purpose |
|---|---|
| `GET /health` | Config check and the currently resolved model. Returns no secrets. |
| `GET /topics` | What is reviewable, with content sizes. |
| `POST /review/:category/:topic` | Review one topic. Add `?dry_run=1` to skip the write-back. |

## Deployed

    https://relo2france-review.kburrowbridge.workers.dev

KV namespace `MODEL_CACHE` (`2e9eda58e4244b3ba10ba42ceedcd622`) is created and
bound. Redeploy with `npm run deploy`, which typechecks and tests first.

## Remaining setup - secrets

Until these are set every route returns 401, which is the intended closed
default:

    npx wrangler secret put ANTHROPIC_API_KEY      # same key WordPress uses
    npx wrangler secret put WP_SHARED_SECRET       # the Review API secret in WP settings
    npx wrangler secret put TRIGGER_SECRET         # any long random string

## Try it

    curl -X POST -H "Authorization: Bearer $TRIGGER_SECRET" \
      "https://relo2france-review.kburrowbridge.workers.dev/review/visas/overview?dry_run=1"

Start with `dry_run=1`: it runs the full review and reports what it would post,
without touching the approval queue.

## Model selection

Never a hardcoded id. Ask for a tier (`MODEL_TIER`), resolve against the live
`GET /v1/models` catalogue, take the newest in that tier. Cached in KV for 24h;
a stale copy is served if the catalogue is unreachable. Same rules as
`FRA_Model_Resolver` in the plugin, so both sides pick the same model.

## Deliberate choices

- **No continuation on truncation here.** Continuing a truncated JSON object
  rarely yields valid JSON. The run fails loudly instead. The conversational
  paths in WordPress do continue, because there the output is prose.
- **The prompt is a verbatim port** of `FRA_Scheduled_Review::call_claude_api()`,
  so the response shape stays identical and the approval screen does not have
  to know which side produced a suggestion.
- **Web search capped at 5 uses.** Search count dominates wall-clock time.

## Next

Phase 3 turns this into a Workflow — one durable step per topic, with retries —
and adds `"triggers": { "crons": ["0 3 * * 0"] }`.
