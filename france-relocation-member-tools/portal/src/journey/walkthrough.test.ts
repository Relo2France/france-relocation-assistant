import { describe, expect, it } from 'vitest';
import type { Task } from '@/types';
import { JOURNEY } from './journey';
import { routeOf, walkthroughFor } from './walkthrough';
import type { Route } from './walkthrough';

const ROUTES: Route[] = ['visitor', 'retiree', 'employee', 'talent_passport', 'entrepreneur', 'student', 'spouse_french', 'family'];
const base = { project: { target_move_date: '2027-03-01', visa_type: 'visitor' as const }, tasks: [] as Task[], dossier: [], departure: [], arrival: [], members: [] };

describe('every route has a walkthrough for every stage', () => {
  for (const route of ROUTES) {
    for (const stage of JOURNEY) {
      it(`${route} · ${stage.id}`, () => {
        const w = walkthroughFor(stage.id, { ...base, route });
        expect(w.intro.length).toBeGreaterThan(0);
        expect(w.intro.every((p) => p.length > 40)).toBe(true);
        expect(w.milestones.length).toBeGreaterThanOrEqual(4);
        expect(w.readyWhen.length).toBeGreaterThan(20);
      });
    }
  }
});

describe('the route changes what Prepare says', () => {
  it('starts an employee with the employer, and a family case with the sponsor', () => {
    expect(walkthroughFor('prepare', { ...base, route: 'employee' }).milestones[0].title).toMatch(/work authorisation/);
    expect(walkthroughFor('prepare', { ...base, route: 'family' }).milestones[0].title).toMatch(/OFII/);
  });
  it('spares a student the FBI check and gives them the acceptance letter first', () => {
    const w = walkthroughFor('prepare', { ...base, route: 'student' });
    expect(w.milestones[0].title).toMatch(/acceptance letter/);
    expect(w.milestones.some((m) => /FBI/.test(m.title))).toBe(false);
  });
  it('tells the entrepreneur about the three-month filing window', () => {
    expect(walkthroughFor('prepare', { ...base, route: 'entrepreneur' }).readyWhen).toMatch(/three-month window/);
  });
  it('warns the family routes about carte de séjour à solliciter on arrival', () => {
    expect(walkthroughFor('arrive', { ...base, route: 'spouse_french' }).intro[0]).toMatch(/carte de séjour à solliciter/);
    expect(walkthroughFor('arrive', { ...base, route: 'visitor' }).intro[0]).not.toMatch(/carte de séjour à solliciter/);
  });
  it('reads milestone status from the tasks on file', () => {
    const tasks = [{ id: 1, title: 'Get the apostilles from the state', status: 'done', stage: 'prepare', due_date: null } as unknown as Task];
    const w = walkthroughFor('prepare', { ...base, route: 'visitor', tasks });
    expect(w.milestones.find((m) => /Apostilles/.test(m.title))?.done).toBe(true);
    expect(w.milestones.find((m) => /FBI/.test(m.title))?.done).toBeNull();
  });
  it('falls back to undecided for an unknown route', () => {
    expect(routeOf({ route: 'whatever', project: { target_move_date: null, visa_type: 'other' } })).toBe('undecided');
  });
});
