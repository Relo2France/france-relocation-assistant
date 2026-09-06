/**
 * Streaming reader for the Messages API.
 *
 * A review with web search runs well past two minutes, and a non-streaming
 * request dies at Cloudflare's 125s proxy read timeout in front of
 * api.anthropic.com - returning an HTML-ish "error code: 524" body rather
 * than JSON. Streaming keeps bytes moving, so the read timeout never fires.
 *
 * Reassembles the SSE events back into the same shape a non-streaming
 * response has, so callers do not need to know the difference.
 */
import type { ContentBlock, MessagesResponse } from './types';

interface StreamEvent {
  type: string;
  index?: number;
  delta?: { type?: string; text?: string; stop_reason?: string };
  content_block?: ContentBlock;
  message?: { usage?: { input_tokens?: number } };
  usage?: { output_tokens?: number };
  error?: { type?: string; message?: string };
}

export async function readMessageStream(response: Response): Promise<MessagesResponse> {
  if (!response.body) throw new Error('Anthropic returned no response body');

  const reader = response.body.getReader();
  const decoder = new TextDecoder();

  const blocks: ContentBlock[] = [];
  const textParts = new Map<number, string[]>();
  let stopReason: string | undefined;
  let inputTokens = 0;
  let outputTokens = 0;
  let buffer = '';

  const handle = (event: StreamEvent) => {
    switch (event.type) {
      case 'message_start':
        inputTokens += event.message?.usage?.input_tokens ?? 0;
        break;

      case 'content_block_start':
        if (event.content_block && typeof event.index === 'number') {
          blocks[event.index] = event.content_block;
          if (event.content_block.type === 'text') textParts.set(event.index, []);
        }
        break;

      case 'content_block_delta':
        if (typeof event.index === 'number' && event.delta?.type === 'text_delta') {
          const parts = textParts.get(event.index);
          if (parts) parts.push(event.delta.text ?? '');
        }
        break;

      case 'message_delta':
        if (event.delta?.stop_reason) stopReason = event.delta.stop_reason;
        outputTokens += event.usage?.output_tokens ?? 0;
        break;

      case 'error':
        throw new Error(`Anthropic stream error: ${event.error?.message ?? 'unknown'}`);
    }
  };

  for (;;) {
    const { done, value } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });

    // SSE frames are separated by a blank line.
    let split: number;
    while ((split = buffer.indexOf('\n\n')) !== -1) {
      const frame = buffer.slice(0, split);
      buffer = buffer.slice(split + 2);

      for (const line of frame.split('\n')) {
        if (!line.startsWith('data:')) continue;
        const payload = line.slice(5).trim();
        if (!payload || payload === '[DONE]') continue;
        try {
          handle(JSON.parse(payload) as StreamEvent);
        } catch (error) {
          if (error instanceof Error && error.message.startsWith('Anthropic stream error')) throw error;
          // A frame we cannot parse is not worth failing the run over.
        }
      }
    }
  }

  // Fold the accumulated text back into its blocks.
  for (const [index, parts] of textParts) {
    const block = blocks[index];
    if (block && block.type === 'text') {
      (block as { type: 'text'; text: string }).text = parts.join('');
    }
  }

  return {
    content: blocks.filter(Boolean),
    stop_reason: stopReason,
    usage: { input_tokens: inputTokens, output_tokens: outputTokens },
  };
}
