import { describe, expect, it } from 'vitest';
import type { StageProgress, Task } from '@/types';
import { JOURNEY, currentStage, groupByLeadTime, progressFor, stageForTask, stageWhen, timeToGo } from './journey';

const NOW = new Date('2026-09-16T12:00:00Z');
const project = { current_stage: 'documents', target_move_date: '2027-08-15' };

describe('six stages, in order, every database stage owned', () => {
  it('numbers 1 to 6', () => {
    expect(JOURNEY.map((s) => s.number)).toEqual([1, 2, 3, 4, 5, 6]);
  });
  it('gives every seeded stage a home', () => {
    for (const slug of ['planning', 'documents', 'application', 'approval', 'moving', 'settling']) {
      expect(JOURNEY.some((s) => s.dbStages.includes(slug)), slug).toBe(true);
    }
  });
});

describe('where a task belongs', () => {
  it('folds the wait for a decision into Apply', () => {
    expect(stageForTask({ stage: 'approval', due_date: null }, project)).toBe('apply');
  });
  it('splits settling by the move date: within 90 days is arriving', () => {
    expect(stageForTask({ stage: 'settling', due_date: '2027-09-30' }, project)).toBe('arrive');
    expect(stageForTask({ stage: 'settling', due_date: '2028-03-01' }, project)).toBe('settle');
    expect(stageForTask({ stage: 'settling', due_date: null }, project)).toBe('settle');
  });
});

describe('where the person is', () => {
  it('reads the stored stage', () => {
    expect(currentStage(project, NOW)).toBe('prepare');
  });
  it('is arriving for 90 days after the move, then settling', () => {
    const settling = { current_stage: 'settling', target_move_date: '2026-08-01' };
    expect(currentStage(settling, new Date('2026-09-16T00:00:00Z'))).toBe('arrive');
    expect(currentStage(settling, new Date('2027-01-16T00:00:00Z'))).toBe('settle');
  });
});

describe('labels', () => {
  it('counts months to go', () => {
    expect(timeToGo(project, NOW)).toBe('11 months to go');
    expect(timeToGo({ target_move_date: null })).toBe('no move date yet');
    expect(timeToGo({ target_move_date: '2026-09-01' }, NOW)).toBe('in France');
  });
  it('dates the road from the move', () => {
    expect(stageWhen('move', project, NOW)).toBe('AUG 2027');
    expect(stageWhen('apply', project, NOW)).toBe('MAY 2027');
    expect(stageWhen('settle', project, NOW)).toBe('2028 →');
    expect(stageWhen('move', { target_move_date: null })).toBe('');
  });
});

describe('progress and grouping', () => {
  it('sums the database stages a journey stage holds', () => {
    const stages = [
      { slug: 'application', total: 4, completed: 1 },
      { slug: 'approval', total: 2, completed: 2 },
    ] as StageProgress[];
    expect(progressFor(JOURNEY[2], stages)).toEqual({ total: 6, completed: 3 });
  });
  it('orders a stage as start now, then, undated, done', () => {
    const t = (id: number, due: string | null, status: Task['status'] = 'todo') => ({ id, due_date: due, status } as Task);
    const groups = groupByLeadTime([t(1, '2027-01-01'), t(2, '2026-10-01'), t(3, null), t(4, '2026-09-20', 'done')], NOW);
    expect(groups.map((g) => g.label)).toEqual(['Start now', 'Then', 'When you get to it', 'Done']);
    expect(groups[0].tasks[0].id).toBe(2);
  });
});
