/**
 * The weekly review, as a Workflow.
 *
 * Phase 2 proved one topic works but also showed why a plain loop will not do:
 * a single topic takes about five minutes, so 31 topics is a couple of hours.
 * A cron-triggered Worker caps at 15 minutes.
 *
 * Workflows fit exactly: each step has unlimited wall time, failures retry the
 * step rather than the run, and a cron-triggered instance that exceeds its
 * one-hour concurrency budget yields and resumes instead of failing.
 *
 * One step per topic, so a topic that times out or errors is retried on its
 * own and the other thirty are unaffected. That is the property WP-Cron never
 * had: there, one failure meant one topic silently lost for the week.
 */
import { WorkflowEntrypoint, type WorkflowEvent, type WorkflowStep } from 'cloudflare:workers';
import { isRetryable } from './retry';
import { reviewTopic } from './review';
import { fetchTopics } from './wordpress';
import type { Env } from './types';

export interface ReviewParams {
  /** Limit the run to these "category/topic" pairs. Empty means everything. */
  only?: string[];
  /** Run without writing suggestions back. */
  dryRun?: boolean;
}

interface TopicOutcome {
  topic: string;
  ok: boolean;
  posted?: boolean;
  needs_update?: boolean;
  review_id?: string;
  error?: string;
  duration_ms?: number;
  output_tokens?: number;
}

export class ReviewWorkflow extends WorkflowEntrypoint<Env, ReviewParams> {
  async run(event: WorkflowEvent<ReviewParams>, step: WorkflowStep) {
    const params = event.payload ?? {};

    // Steps persist their return value, so keep this to identifiers rather
    // than the topic bodies - the content is large and is not needed again.
    const targets = await step.do('list topics', async () => {
      const topics = await fetchTopics(this.env);
      const wanted = topics.map((t) => `${t.category}/${t.topic_key}`);
      return params.only?.length ? wanted.filter((t) => params.only!.includes(t)) : wanted;
    });

    const outcomes: TopicOutcome[] = [];

    for (const target of targets) {
      const [category, topicKey] = target.split('/');
      if (!category || !topicKey) continue;

      // Sequential on purpose. Running these in parallel would be faster but
      // would also multiply the Anthropic request rate and the cost spike; a
      // weekly overnight job does not need the wall-clock saving.
      const outcome = await step.do(
        `review ${target}`,
        {
          retries: { limit: 2, delay: '30 seconds', backoff: 'exponential' },
          // A topic took 341s live. 15 minutes leaves real headroom without
          // letting a wedged request hold the run open indefinitely.
          timeout: '15 minutes',
        },
        async (): Promise<TopicOutcome> => {
          try {
            const result = await reviewTopic(this.env, category, topicKey, {
              dryRun: params.dryRun,
            });
            return {
              topic: target,
              ok: true,
              posted: result.posted,
              needs_update: result.needs_update,
              review_id: result.review_id,
              duration_ms: result.duration_ms,
              output_tokens: result.usage.output,
            };
          } catch (error) {
            // Returned, not thrown: a topic we genuinely cannot review should
            // not consume the retry budget or stop the other thirty. Transient
            // failures still throw from inside reviewTopic and do retry.
            const message = error instanceof Error ? error.message : String(error);
            if (isRetryable(message)) throw error;
            return { topic: target, ok: false, error: message };
          }
        }
      );

      outcomes.push(outcome);
    }

    return await step.do('summarise', async () => {
      const reviewed = outcomes.filter((o) => o.ok);
      const failed = outcomes.filter((o) => !o.ok);
      const posted = outcomes.filter((o) => o.posted);

      const summary = {
        attempted: outcomes.length,
        reviewed: reviewed.length,
        suggestions_posted: posted.length,
        failed: failed.length,
        // Named, not counted: a silent failure is the thing that hid the
        // WP-Cron problems for weeks.
        failures: failed.map((o) => ({ topic: o.topic, error: o.error })),
        total_output_tokens: reviewed.reduce((sum, o) => sum + (o.output_tokens ?? 0), 0),
        total_duration_ms: reviewed.reduce((sum, o) => sum + (o.duration_ms ?? 0), 0),
        dry_run: Boolean(params.dryRun),
      };

      console.log('review workflow complete', summary);
      return summary;
    });
  }
}
