/**
 * resolveTarget decides where a drafted addition lands. A coverage gap lets
 * the model propose a home, and the model must not be able to invent a
 * category - WordPress rejects unknown ones, so a bad proposal would waste a
 * five-minute research call.
 */
import { describe, expect, it } from 'vitest';
import { buildCoveragePrompt, buildDepthPrompt, resolveTarget, type Gap } from '../src/gaps';

const depthGap: Gap = {
  id: 'depth_abc',
  type: 'depth',
  questions: ['What documents do I need for a long-stay visa?'],
  count: 2,
  relevance: 0.82,
  category: 'visa_application_guide',
  topic: 'document_requirements',
  topic_name: 'Document Requirements',
  current_content: 'Existing checklist text.',
};

const coverageGap: Gap = {
  id: 'coverage_xyz',
  type: 'coverage',
  questions: ['How do I register a drone in France?'],
  count: 3,
  relevance: 0,
  categories: ['visas', 'property', 'healthcare'],
};

describe('resolveTarget', () => {
  it('sends a depth draft back to the topic that fell short', () => {
    expect(resolveTarget(depthGap, {})).toEqual({
      category: 'visa_application_guide',
      topic: 'document_requirements',
      title: 'Document Requirements',
    });
  });

  it('honours a proposed category when it exists', () => {
    const t = resolveTarget(coverageGap, { suggested_category: 'healthcare', suggested_topic_key: 'drone_rules' });
    expect(t?.category).toBe('healthcare');
    expect(t?.topic).toBe('drone_rules');
  });

  it('refuses to invent a category', () => {
    const t = resolveTarget(coverageGap, { suggested_category: 'aviation', suggested_topic_key: 'drone_rules' });
    expect(t?.category).toBe('visas'); // falls back to an existing one
  });

  it('normalises a messy topic key', () => {
    const t = resolveTarget(coverageGap, { suggested_category: 'visas', suggested_topic_key: '  Drone Rules & Permits!  ' });
    expect(t?.topic).toBe('drone_rules_permits');
  });

  it('returns null when no topic key can be salvaged', () => {
    expect(resolveTarget(coverageGap, { suggested_category: 'visas', suggested_topic_key: '!!!' })).toBeNull();
  });

  it('returns null when there are no categories to put it in', () => {
    expect(resolveTarget({ ...coverageGap, categories: [] }, { suggested_topic_key: 'x' })).toBeNull();
  });

  it('returns null for a depth gap missing its topic', () => {
    expect(resolveTarget({ ...depthGap, topic: undefined }, {})).toBeNull();
  });
});

describe('prompts', () => {
  it('a depth prompt carries the current content and says it is an edit', () => {
    const p = buildDepthPrompt(depthGap);
    expect(p).toContain('Existing checklist text.');
    expect(p).toContain('Document Requirements');
    expect(p).toContain('This is an edit, not a replacement');
    expect(p).toContain('What documents do I need for a long-stay visa?');
  });

  it('a coverage prompt offers only the existing categories', () => {
    const p = buildCoveragePrompt(coverageGap);
    expect(p).toContain('visas, property, healthcare');
    expect(p).toContain('How do I register a drone in France?');
    expect(p).toContain('suggested_topic_key');
  });

  it('both refuse invented requirements', () => {
    for (const p of [buildDepthPrompt(depthGap), buildCoveragePrompt(coverageGap)]) {
      expect(p).toContain('Only state requirements you can confirm from an official source');
      expect(p).toContain('vary by consulate');
    }
  });
});
