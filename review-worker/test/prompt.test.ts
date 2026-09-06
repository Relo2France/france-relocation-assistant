import { describe, expect, it } from 'vitest';
import { buildReviewPrompt } from '../src/prompt';
import type { Topic } from '../src/types';

const topic: Topic = {
  category: 'visas',
  topic_key: 'application_timeline',
  name: 'Application Timeline',
  content: 'Current KB text about timelines.',
  keywords: ['visa', 'timeline'],
  last_verified: 'September 2026',
  sources: ['france-visas.gouv.fr'],
  key_facts: ['processing time', 'appointment wait'],
  practice_hints: ['visa processing delays', 'consulate appointment availability'],
};

describe('buildReviewPrompt', () => {
  const prompt = buildReviewPrompt(topic, new Date('2026-09-06T00:00:00Z'));

  it('carries the topic and its current content', () => {
    expect(prompt).toContain('**TOPIC:** Application Timeline');
    expect(prompt).toContain('Current KB text about timelines.');
  });

  it('carries the official sources and key facts to verify', () => {
    expect(prompt).toContain('france-visas.gouv.fr');
    expect(prompt).toContain('processing time, appointment wait');
  });

  it('builds dated research queries from the practice hints', () => {
    expect(prompt).toContain('France visa processing delays 2026 expat experience');
  });

  it('asks for exactly the JSON fields the approval screen renders', () => {
    for (const field of [
      'needs_update', 'update_type', 'confidence', 'changes_summary',
      'suggested_content', 'in_practice_content', 'practice_sources',
      'official_sources_checked', 'key_insights',
    ]) {
      expect(prompt).toContain(`"${field}"`);
    }
  });

  it('states the current date so the model does not assume its cutoff', () => {
    expect(prompt).toContain('September 6, 2026');
  });
});
