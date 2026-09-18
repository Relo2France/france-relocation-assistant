/**
 * Is a failed topic worth another attempt?
 *
 * Retrying costs about five minutes, so this decides between burning the
 * budget on something permanently broken and giving up on something that
 * would have worked. Getting the second case wrong is how WP-Cron lost a
 * topic for a whole week on a single timeout.
 *
 * Kept in its own module so it can be tested without pulling in
 * cloudflare:workers.
 */
export function isRetryable(message: string): boolean {
  const lower = message.toLowerCase();

  // These fail identically next time.
  if (lower.includes('unknown topic')) return false;
  if (lower.includes('no json object found')) return false;
  if (lower.includes('queue is full') || lower.includes('fra_review_queue_full')) return false;

  return (
    /\btime(d)?[ -]?out\b/.test(lower) ||
    /\b429\b/.test(lower) ||
    /\brate[ _-]?limit/.test(lower) ||
    lower.includes('overloaded') ||
    /\bhttp 5\d\d\b/.test(lower) ||
    lower.includes('network') ||
    // WordPress serialises writes; a busy lock clears in seconds.
    lower.includes('fra_review_busy')
  );
}
