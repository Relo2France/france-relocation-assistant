/**
 * A location report, as a Workflow.
 *
 * Generating one takes three to five minutes with web search, and nothing
 * between the member's browser and WordPress.com will hold a request open
 * that long. So WordPress hands the prompt here, answers the browser at once,
 * and this posts the finished JSON back. The prompt stays WordPress's:
 * this side is deliberately generic.
 */
import { WorkflowEntrypoint, type WorkflowEvent, type WorkflowStep } from 'cloudflare:workers';
import { extractJson, sendMessage } from './anthropic';
import type { Env } from './types';

// Step results must be serialisable, so the content travels as a JSON string.
type Generated =
  | { ok: true; content: string; model: string; web_sources: { url: string; title: string }[]; web_search_errors: string[]; output_tokens: number }
  | { ok: false; error: string; model: string };

export interface ReportParams {
  report_id: number;
  system: string;
  prompt: string;
  max_tokens?: number;
  web_search_uses?: number;
}

async function deliver(env: Env, reportId: number, body: Record<string, unknown>): Promise<void> {
  const url = `${env.WP_BASE_URL}/wp-json/fra/v1/review/reports/${reportId}`;
  const response = await fetch(url, {
    method: 'POST',
    headers: { authorization: `Bearer ${env.WP_SHARED_SECRET}`, 'content-type': 'application/json' },
    body: JSON.stringify(body),
  });
  if (!response.ok) {
    throw new Error(`WordPress rejected the report (${response.status}): ${(await response.text()).slice(0, 200)}`);
  }
}

export class ReportWorkflow extends WorkflowEntrypoint<Env, ReportParams> {
  async run(event: WorkflowEvent<ReportParams>, step: WorkflowStep) {
    const params = event.payload;
    const started = Date.now();

    const generated: Generated = await step.do(
      'generate',
      { retries: { limit: 1, delay: '30 seconds' }, timeout: '20 minutes' },
      async (): Promise<Generated> => {
        const outcome = await sendMessage(this.env, {
          tier: this.env.MODEL_TIER,
          system: params.system,
          prompt: params.prompt,
          maxTokens: params.max_tokens ?? 12000,
          webSearchUses: params.web_search_uses ?? 8,
          continueOnTruncation: false,
        });
        const content = extractJson<Record<string, unknown>>(outcome.text);
        if (!content) {
          return {
            ok: false,
            error: outcome.truncated ? 'Response hit max_tokens before the JSON was complete' : 'No JSON object in the response',
            model: outcome.model,
          };
        }
        return {
          ok: true,
          content: JSON.stringify(content),
          model: outcome.model,
          web_sources: outcome.webSources,
          web_search_errors: outcome.webSearchErrors,
          output_tokens: outcome.usage.output,
        };
      }
    );

    await step.do('deliver', { retries: { limit: 3, delay: '20 seconds', backoff: 'exponential' } }, async () => {
      await deliver(this.env, params.report_id, {
        ...(generated.ok
          ? { content: JSON.parse(generated.content) as Record<string, unknown>, web_sources: generated.web_sources, web_search_errors: generated.web_search_errors }
          : { error: generated.error }),
        model: generated.model,
        duration_ms: Date.now() - started,
      });
    });

    const summary = { report_id: params.report_id, ok: generated.ok, duration_ms: Date.now() - started, model: generated.model };
    console.log('report workflow complete', summary);
    return summary;
  }
}
