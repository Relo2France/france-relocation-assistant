/**
 * Gap drafting, as a Workflow.
 *
 * Same reasoning as the review: minutes per gap, so one durable step each and
 * a failure retries that gap rather than the run.
 */
import { WorkflowEntrypoint, type WorkflowEvent, type WorkflowStep } from 'cloudflare:workers';
import { draftGap, fetchGaps, type GapOutcome } from './gaps';
import { isRetryable } from './retry';
import type { Env } from './types';

export interface GapParams {
  /** Most gaps to draft in one pass. Bounds the cost of a busy week. */
  limit?: number;
}

export class GapWorkflow extends WorkflowEntrypoint<Env, GapParams> {
  async run(event: WorkflowEvent<GapParams>, step: WorkflowStep) {
    const limit = event.payload?.limit ?? 3;

    const gaps = await step.do('list ready gaps', async () => {
      const ready = await fetchGaps(this.env);
      return ready.slice(0, limit);
    });

    if (gaps.length === 0) {
      return { drafted: 0, failed: 0, gaps: [], note: 'no gaps ready' };
    }

    const outcomes: GapOutcome[] = [];

    for (const gap of gaps) {
      const outcome = await step.do(
        `draft ${gap.type} gap ${gap.id}`,
        {
          retries: { limit: 2, delay: '30 seconds', backoff: 'exponential' },
          timeout: '25 minutes',
        },
        async (): Promise<GapOutcome> => {
          try {
            return await draftGap(this.env, gap);
          } catch (error) {
            const message = error instanceof Error ? error.message : String(error);
            // Transient failures retry; anything else is recorded against the
            // gap and the run continues.
            if (isRetryable(message)) throw error;
            return {
              gap_id: gap.id,
              type: gap.type,
              ok: false,
              error: message,
              duration_ms: 0,
            };
          }
        }
      );

      outcomes.push(outcome);
    }

    return await step.do('summarise', async () => {
      const drafted = outcomes.filter((o) => o.ok);
      const failed = outcomes.filter((o) => !o.ok);

      const summary = {
        considered: gaps.length,
        drafted: drafted.length,
        failed: failed.length,
        new_topics: drafted.filter((o) => o.is_new_topic).length,
        // Named, not counted.
        failures: failed.map((o) => ({ gap_id: o.gap_id, error: o.error })),
        total_output_tokens: drafted.reduce((sum, o) => sum + (o.output_tokens ?? 0), 0),
      };

      console.log('gap workflow complete', summary);
      return summary;
    });
  }
}
