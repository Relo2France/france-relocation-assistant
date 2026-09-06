/**
 * Relo2France review worker - phase 2.
 *
 * One manually triggered route that reviews a single topic and posts the
 * suggestion back to WordPress. No cron yet: this phase exists to prove the
 * prompt, the model resolution and the write-back against real traffic before
 * anything runs unattended.
 *
 *   POST /review/:category/:topic     review one topic
 *   GET  /topics                      list what is reviewable
 *   GET  /health                      config check, no secrets returned
 *
 * Every route requires the trigger secret. This worker can write to the site's
 * review queue, so it is not left open.
 */
import { ReviewWorkflow, type ReviewParams } from './workflow';
import { reviewTopic } from './review';
import { resolveModel } from './models';
import { fetchTopics } from './wordpress';
import type { Env } from './types';

export { ReviewWorkflow };

const json = (body: unknown, status = 200): Response =>
  new Response(JSON.stringify(body, null, 2), {
    status,
    headers: { 'content-type': 'application/json; charset=utf-8' },
  });

function authorised(request: Request, env: Env): boolean {
  const header = request.headers.get('authorization') ?? '';
  const provided = header.toLowerCase().startsWith('bearer ') ? header.slice(7).trim() : '';
  // Trim the stored value too. A secret pasted into the dashboard can pick up
  // a trailing newline, and surrounding whitespace is never meaningful in a
  // token - without this the mismatch is invisible and reads as a wrong key.
  const expected = (env.TRIGGER_SECRET ?? '').trim();

  if (!provided || !expected || provided.length !== expected.length) return false;

  // Constant-time comparison - the lengths already match here.
  let diff = 0;
  for (let i = 0; i < expected.length; i++) {
    diff |= provided.charCodeAt(i) ^ expected.charCodeAt(i);
  }
  return diff === 0;
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const url = new URL(request.url);

    if (!authorised(request, env)) {
      return json({ error: 'Unauthorised' }, 401);
    }

    try {
      if (url.pathname === '/health') {
        return json({
          ok: true,
          environment: env.ENVIRONMENT,
          wp_base_url: env.WP_BASE_URL,
          model_tier: env.MODEL_TIER,
          resolved_model: await resolveModel(env),
          // Presence only - never the values.
          has_anthropic_key: Boolean(env.ANTHROPIC_API_KEY),
          has_wp_secret: Boolean(env.WP_SHARED_SECRET),
        });
      }

      if (url.pathname === '/topics') {
        const topics = await fetchTopics(env);
        return json({
          count: topics.length,
          topics: topics.map((t) => ({
            category: t.category,
            topic_key: t.topic_key,
            name: t.name,
            content_chars: t.content.length,
            last_verified: t.last_verified,
          })),
        });
      }

      // Start a full run. Returns immediately with an instance id - the run
      // itself takes hours, so it must not be tied to this request.
      if (url.pathname === '/run' && request.method === 'POST') {
        const params: ReviewParams = {
          dryRun: url.searchParams.get('dry_run') === '1',
        };
        const only = url.searchParams.get('only');
        if (only) params.only = only.split(',').map((s) => s.trim()).filter(Boolean);

        const instance = await env.REVIEW_WORKFLOW.create({ params });
        return json({
          started: true,
          instance_id: instance.id,
          status: await instance.status(),
          dry_run: Boolean(params.dryRun),
          topics: params.only ?? 'all',
        });
      }

      // Progress of a run.
      const runStatus = url.pathname.match(/^\/run\/([\w-]+)$/);
      if (runStatus && request.method === 'GET') {
        const instance = await env.REVIEW_WORKFLOW.get(runStatus[1]!);
        return json({ instance_id: runStatus[1], status: await instance.status() });
      }

      const match = url.pathname.match(/^\/review\/([a-z0-9_-]+)\/([a-z0-9_-]+)$/i);
      if (match && request.method === 'POST') {
        const [, category, topicKey] = match;
        const dryRun = url.searchParams.get('dry_run') === '1';
        const outcome = await reviewTopic(env, category!, topicKey!, { dryRun });
        return json(outcome);
      }

      return json({ error: 'Not found' }, 404);
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      console.error('review worker failed', { path: url.pathname, message });
      return json({ error: message }, 500);
    }
  },

  /**
   * Weekly cron. Starts a Workflow instance and returns - the run outlives
   * this invocation, which is the whole point of using a Workflow.
   */
  async scheduled(event: ScheduledController, env: Env): Promise<void> {
    const instance = await env.REVIEW_WORKFLOW.create({ params: {} });
    console.log('scheduled review started', { cron: event.cron, instance_id: instance.id });
  },
};
