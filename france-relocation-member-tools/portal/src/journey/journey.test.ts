import { describe, expect, it } from 'vitest';
import type { Task } from '@/types';
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
  it('reads the template vocabulary: pre-arrival splits by what the task is', () => {
    expect(stageForTask({ stage: 'pre-arrival', due_date: null, title: 'Get documents apostilled' }, project)).toBe('prepare');
    expect(stageForTask({ stage: 'pre-arrival', due_date: null, title: 'Apply for spouse visa' }, project)).toBe('apply');
    expect(stageForTask({ stage: 'pre-arrival', due_date: null, title: 'Get pet microchipped' }, project)).toBe('move');
    expect(stageForTask({ stage: 'pre-arrival', due_date: null, title: 'Book temporary accommodation' }, project)).toBe('move');
    expect(stageForTask({ stage: 'pre-arrival', due_date: null, title: 'Book the TLScontact appointment' }, project)).toBe('apply');
  });
  it('shows a template step carrying a journey stage where it says, whatever the date', () => {
    expect(stageForTask({ stage: 'apply', due_date: '2027-05-01', title: 'Collect your passport and check the visa' }, project)).toBe('apply');
    expect(stageForTask({ stage: 'settle', due_date: '2027-11-18', title: 'Apply for French health cover (PUMa)' }, project)).toBe('settle');
    expect(stageForTask({ stage: 'prepare', due_date: '2026-07-11', title: 'Your sponsor applies to OFII for family reunification' }, project)).toBe('prepare');
  });
  it('puts every post-move template into arriving or settling by date', () => {
    expect(stageForTask({ stage: 'arrival', due_date: null, title: 'Set up utilities' }, project)).toBe('arrive');
    expect(stageForTask({ stage: 'settlement', due_date: '2027-10-14', title: 'Get Carte Vitale' }, project)).toBe('arrive');
    expect(stageForTask({ stage: 'integration', due_date: '2028-02-11', title: 'File French tax return' }, project)).toBe('settle');
    expect(stageForTask({ stage: 'settlement', due_date: null, title: 'Exchange driving license' }, project)).toBe('settle');
  });
});

describe('where the person is', () => {
  it('is deciding until a visa route is chosen', () => {
    expect(currentStage(project, null, NOW)).toBe('decide');
    expect(currentStage(project, 'undecided', NOW)).toBe('decide');
  });
  it('is preparing with a route but no date, or far out', () => {
    expect(currentStage({ target_move_date: null }, 'visitor', NOW)).toBe('prepare');
    expect(currentStage(project, 'visitor', NOW)).toBe('prepare');
  });
  it('applies inside four months, moves in the last month', () => {
    expect(currentStage({ target_move_date: '2026-12-01' }, 'visitor', NOW)).toBe('apply');
    expect(currentStage({ target_move_date: '2026-10-01' }, 'visitor', NOW)).toBe('move');
  });
  it('is arriving for 90 days after the move, then settling', () => {
    expect(currentStage({ target_move_date: '2026-08-01' }, 'visitor', NOW)).toBe('arrive');
    expect(currentStage({ target_move_date: '2026-03-01' }, 'visitor', NOW)).toBe('settle');
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
    expect(stageWhen('apply', project, NOW)).toBe('APR 2027'); // 120 days out, when currentStage switches to Apply
    expect(stageWhen('settle', project, NOW)).toBe('2028 →');
    expect(stageWhen('move', { target_move_date: null })).toBe('');
  });
});

describe('progress and grouping', () => {
  it('counts progress from the tasks that map to a stage', () => {
    const tasks = [
      { stage: 'pre-arrival', due_date: null, status: 'done', title: 'Gather all required documents' },
      { stage: 'pre-arrival', due_date: null, status: 'todo', title: 'Get documents translated' },
      { stage: 'pre-arrival', due_date: null, status: 'todo', title: 'Get pet microchipped' },
    ] as Task[];
    expect(progressFor(JOURNEY[1], tasks, project)).toEqual({ total: 2, completed: 1 });
    expect(progressFor(JOURNEY[3], tasks, project)).toEqual({ total: 1, completed: 0 });
  });
  it('orders a stage as start now, then, undated, done', () => {
    const t = (id: number, due: string | null, status: Task['status'] = 'todo') => ({ id, due_date: due, status } as Task);
    const groups = groupByLeadTime([t(1, '2027-01-01'), t(2, '2026-10-01'), t(3, null), t(4, '2026-09-20', 'done')], NOW);
    expect(groups.map((g) => g.label)).toEqual(['Start now', 'Then', 'When you get to it', 'Done']);
    expect(groups[0].tasks[0].id).toBe(2);
  });
});


describe('stageForTask, journey vocabulary', () => {
  const project = { target_move_date: '2027-03-01' };
  it('journey ids pass through unchanged', () => {
    for (const id of ['decide', 'prepare', 'apply', 'move', 'arrive', 'settle'] as const) {
      expect(stageForTask({ stage: id, due_date: null }, project)).toBe(id);
    }
  });
  it('keeps a moving task due on move day in Move', () => {
    expect(stageForTask({ stage: 'moving', due_date: '2027-03-01' }, project)).toBe('move');
    expect(stageForTask({ stage: 'moving', due_date: '2027-03-02' }, project)).toBe('arrive');
  });
});
