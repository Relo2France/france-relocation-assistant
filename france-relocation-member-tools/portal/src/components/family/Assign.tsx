/**
 * Who a step is for, and who is doing it.
 *
 * Every task can carry a person (`metadata.person`: 'you', 'partner',
 * 'children' or 'child:<id>') and an assignee (a user id: the account holder
 * or the invited partner). The chip shows both; the select changes the
 * assignee. Shared by the stage view, the task board and the family plan so
 * the vocabulary is the same everywhere.
 */
import { useUpdateTask } from '@/hooks/useApi';
import type { FamilyMember, Household, Task } from '@/types';

export function personOf(task: Task): string {
  const person = task.metadata?.person;
  return typeof person === 'string' ? person : 'you';
}

function personLabel(task: Task, members: FamilyMember[]): string {
  const person = personOf(task);
  if (person === 'partner') {
    const partner = members.find((m) => m.relationship === 'spouse');
    return partner?.name || 'Partner';
  }
  if (person === 'children') return members.filter((m) => m.relationship === 'child').length === 1 ? 'Child' : 'Children';
  if (person.startsWith('child:')) {
    const id = Number(person.slice(6));
    return members.find((m) => m.id === id)?.name || 'Child';
  }
  return 'You';
}

export function PersonChip({ task, members, household }: { task: Task; members: FamilyMember[]; household?: Household | null }) {
  const forWhom = personLabel(task, members);
  const partner = household?.partner;
  const assignedToPartner = partner && partner.userId > 0 && task.assignee_id === partner.userId;
  const isOwnerTask = forWhom === 'You';
  if (isOwnerTask && !assignedToPartner) return null;
  return (
    <span className="inline-flex items-center gap-1 font-mono text-[0.66rem] uppercase tracking-wide text-gray-500 whitespace-nowrap">
      {!isOwnerTask ? <span>For {forWhom}</span> : null}
      {assignedToPartner ? <span className="text-primary-500">· {partner.name.split(' ')[0]} is on it</span> : null}
    </span>
  );
}

/**
 * The account holder decides who does what. Only shown when a partner has
 * been invited, because until then there is nobody else to hand it to.
 */
export function AssignSelect({ task, household }: { task: Task; household?: Household | null }) {
  const update = useUpdateTask();
  const partner = household?.partner;
  if (!household || household.role !== 'owner' || !partner || partner.userId <= 0) return null;
  const value = task.assignee_id === partner.userId ? 'partner' : 'me';
  return (
    <label className="inline-flex items-center gap-1.5 text-[0.75rem] text-gray-500">
      <span className="sr-only">Who does this</span>
      <select
        className="input h-7 py-0 px-2 text-[0.75rem] w-auto"
        value={value}
        disabled={update.isPending}
        onChange={(e) => update.mutate({ id: task.id, data: { assignee_id: e.target.value === 'partner' ? partner.userId : 0 } })}
        aria-label={`Who does "${task.title}"`}
      >
        <option value="me">You</option>
        <option value="partner">{partner.name.split(' ')[0] || 'Partner'}</option>
      </select>
    </label>
  );
}
