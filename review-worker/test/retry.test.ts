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

  it('treats an unrecognised failure as permanent rather than looping', () => {
    expect(isRetryable('Something nobody anticipated')).toBe(false);
  });
});
