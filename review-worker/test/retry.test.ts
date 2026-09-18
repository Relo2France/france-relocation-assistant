/**
 * Exercises the real implementation - the previous version of this test
 * copy-pasted the logic and so proved nothing.
 */
import { describe, expect, it } from 'vitest';
import { isRetryable } from '../src/retry';

describe('isRetryable', () => {
  it('retries transient failures', () => {
    for (const message of [
      'Anthropic returned HTTP 524: error code: 524',
      'Anthropic API error: overloaded_error',
      'Request timed out',
      'HTTP 429 rate limited',
      'network connection reset',
    ]) {
      expect(isRetryable(message), message).toBe(true);
    }
  });

  it('does not retry failures that will repeat identically', () => {
    for (const message of [
      'Unknown topic visas/does_not_exist',
      'No JSON object found in a 1420 character response',
    ]) {
      expect(isRetryable(message), message).toBe(false);
    }
  });

  it('retries a busy WordPress but not a full queue', () => {
    expect(isRetryable('Could not post suggestion: HTTP 409 fra_review_busy Another suggestion is being written.')).toBe(true);
    expect(isRetryable('Could not post suggestion: HTTP 409 fra_review_queue_full The pending review queue is full (200).')).toBe(false);
    expect(isRetryable('Could not post suggestion: HTTP 503 service unavailable')).toBe(true);
  });

  it('does not mistake words that contain "rate" for a rate limit', () => {
    for (const message of [
      'The draft did not name a usable category or topic; could not generate',
      'Could not corroborate the claim',
      'separate failure',
    ]) {
      expect(isRetryable(message), message).toBe(false);
    }
  });

  it('treats an unrecognised failure as permanent rather than looping', () => {
    expect(isRetryable('Something nobody anticipated')).toBe(false);
  });
});
