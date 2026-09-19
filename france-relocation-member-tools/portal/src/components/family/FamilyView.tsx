/**
 * Family plans
 *
 * One file per person. The account holder can work the whole household's
 * file alone, or give their partner a sign-in and hand over the partner's
 * steps and the children's. The profile decides who is moving; this view
 * shows what each of them still needs and who is doing it.
 *
 * The Family add-on ($20, once) covers one partner and up to four children.
 * Until it is bought the household is shown read-only from the profile.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { ArrowRight, Baby, CheckCircle2, Circle, Heart, Mail, Trash2, UserPlus } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import {
  useCreateFamilyMember,
  useDashboard,
  useDeleteFamilyMember,
  useFamilyMembers,
  useInviteFamilyMember,
  useRevokeFamilyInvite,
  useTasks,
  useUpdateFamilyMember,
  useUpdateTaskStatus,
} from '@/hooks/useApi';
import { usePortalStore } from '@/store';
import type { FamilyMember, Household, Task } from '@/types';
import { AssignSelect, PersonChip, personOf } from './Assign';

const DOCS: { key: keyof FamilyMember['documents']; label: string; partnerOnly?: boolean }[] = [
  { key: 'passport', label: 'Passport, 6+ months valid' },
  { key: 'birthCertificate', label: 'Birth certificate, apostilled' },
  { key: 'marriageCertificate', label: 'Marriage certificate, apostilled', partnerOnly: true },
  { key: 'photos', label: 'Visa photos (35×45mm)' },
];

function tasksFor(member: FamilyMember, tasks: Task[]): Task[] {
  if (member.relationship === 'spouse') return tasks.filter((t) => personOf(t) === 'partner');
  return tasks.filter((t) => personOf(t) === 'children' || personOf(t) === `child:${member.id}`);
}

function dueLabel(task: Task): string {
  if (task.status === 'done') return 'DONE';
  if (!task.due_date) return '';
  const d = new Date(`${task.due_date.slice(0, 10)}T00:00:00Z`);
  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', timeZone: 'UTC' }).toUpperCase();
}

export default function FamilyView() {
  const { data, isLoading, error, refetch, isFetching } = useFamilyMembers();
  const { data: dashboard } = useDashboard();
  const { data: tasks = [] } = useTasks(dashboard?.project?.id ?? 0);
  const { setActiveView } = usePortalStore();
  const [adding, setAdding] = useState<'spouse' | 'child' | null>(null);

  if (isLoading) return <div className="p-6 md:p-8"><div className="card h-40 animate-pulse" /></div>;
  if (error || !data) {
    return (
      <div className="p-6 md:p-8">
        <div className="card p-6">
          <p className="font-display font-semibold text-lg">Family plans could not load.</p>
          <p className="text-sm text-gray-600 mt-1">Try again. If it keeps happening, tell us from Support.</p>
          <button type="button" onClick={() => void refetch()} disabled={isFetching} className="btn btn-secondary mt-3">
            {isFetching ? 'Trying…' : 'Try again'}
          </button>
        </div>
      </div>
    );
  }

  const { members, featureEnabled, canEdit, household, profile, addon } = data;
  const partner = members.find((m) => m.relationship === 'spouse') ?? null;
  const children = members.filter((m) => m.relationship === 'child');
  const isPartner = household?.role === 'partner';

  return (
    <div className="flex flex-col">
      <header className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 px-6 md:px-8 pt-6 pb-5 bg-card border-b border-rule">
        <div className="flex flex-col gap-1.5">
          <span className="eyebrow">Family plans</span>
          <h2 className="font-display text-[1.75rem] font-semibold tracking-[-0.018em] leading-tight">One file per person.</h2>
          <p className="text-ink/80 max-w-[64ch]">
            {isPartner
              ? `You are working on ${household.ownerName}'s household file. The steps marked for you are yours; everything else is shared.`
              : 'Do the whole household yourself, or give your partner their own sign-in and hand them their steps and the children’s.'}
          </p>
        </div>
        {featureEnabled && canEdit ? (
          <div className="flex gap-2">
            {!partner && adding !== 'spouse' ? (
              <button className="btn btn-secondary" onClick={() => setAdding('spouse')}><Heart className="w-4 h-4" /> Add your partner</button>
            ) : null}
            {children.length < addon.limits.children && adding !== 'child' ? (
              <button className="btn btn-secondary" onClick={() => setAdding('child')}><Baby className="w-4 h-4" /> Add a child</button>
            ) : null}
          </div>
        ) : null}
      </header>

      <div className="px-6 md:px-8 py-5 flex flex-col gap-5">
        {!featureEnabled ? <LockedHousehold profile={profile} addonUrl={addon.url} price={addon.price} priceNote={addon.priceNote} /> : null}

        {adding ? <AddPersonForm kind={adding} onDone={() => setAdding(null)} /> : null}

        {featureEnabled ? (
          <div className="grid md:grid-cols-2 gap-5">
            <OwnerCard household={household} tasks={tasks} />
            {partner ? <PersonCard member={partner} tasks={tasksFor(partner, tasks)} household={household} canEdit={canEdit} /> : null}
            {children.map((child) => (
              <PersonCard key={child.id} member={child} tasks={tasksFor(child, tasks)} household={household} canEdit={canEdit} />
            ))}
            {!partner && children.length === 0 && !adding ? (
              <div className="card p-5 border-dashed flex flex-col gap-2">
                <span className="eyebrow">Nobody else yet</span>
                <p className="text-sm text-gray-600">Say who is moving with you in your profile and their files appear here, or add them directly.</p>
                <button onClick={() => setActiveView('profile')} className="text-sm font-semibold text-primary-500 hover:text-primary-700 self-start">Open profile</button>
              </div>
            ) : null}
          </div>
        ) : null}

        {featureEnabled ? <HouseholdSteps members={members} tasks={tasks} household={household} /> : null}
      </div>
    </div>
  );
}

function LockedHousehold({ profile, addonUrl, price, priceNote }: { profile: FamilyMembersResponseProfile; addonUrl: string; price: string; priceNote: string }) {
  const { setActiveView } = usePortalStore();
  const rows: string[] = ['You · account holder'];
  if (profile.hasPartner) rows.push(`${profile.partnerName || 'Your partner'} · partner`);
  for (let i = 0; i < profile.children; i++) {
    const age = profile.childrenAges[i];
    rows.push(`Child ${i + 1}${age ? ` · age ${age}` : ''}`);
  }
  return (
    <div className="grid md:grid-cols-[minmax(0,1fr)_320px] gap-5">
      <div className="card p-5 flex flex-col gap-3">
        <span className="eyebrow">Who’s moving, from your profile</span>
        <ul className="divide-y divide-rule-soft">
          {rows.map((r) => (
            <li key={r} className="py-2 text-[0.95rem]">{r}</li>
          ))}
        </ul>
        <p className="text-[0.82rem] text-gray-500">Each person applies separately and each needs their own documents. Their steps are already on your calendar; the add-on gives each of them a file of their own.</p>
        <button onClick={() => setActiveView('profile')} className="text-sm font-semibold text-primary-500 hover:text-primary-700 self-start">Change who’s moving</button>
      </div>
      <div className="card p-6 border-primary-500 bg-primary-100/30 flex flex-col gap-3 self-start">
        <span className="eyebrow text-primary-500">Family add-on</span>
        <p className="font-display font-semibold text-[2.2rem] leading-none tracking-[-0.03em] m-0">{price}</p>
        <p className="text-[0.85rem] text-gray-600 m-0">{priceNote}</p>
        <ul className="text-sm flex flex-col gap-1.5 mt-1">
          <li className="flex gap-2"><CheckCircle2 className="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /> Your partner’s own file and their own sign-in</li>
          <li className="flex gap-2"><CheckCircle2 className="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /> A file for each child, up to four</li>
          <li className="flex gap-2"><CheckCircle2 className="w-4 h-4 text-primary-500 flex-shrink-0 mt-0.5" /> Hand any step to either of you</li>
        </ul>
        <a href={addonUrl} className="btn btn-primary mt-2 justify-center">Add the Family plan <ArrowRight className="w-4 h-4" /></a>
        <p className="text-[0.74rem] text-gray-500 text-center m-0">One payment. Covers this move.</p>
      </div>
    </div>
  );
}

type FamilyMembersResponseProfile = NonNullable<ReturnType<typeof useFamilyMembers>['data']>['profile'];

function OwnerCard({ household, tasks }: { household: Household; tasks: Task[] }) {
  const own = tasks.filter((t) => personOf(t) === 'you' || personOf(t) === '');
  const done = own.filter((t) => t.status === 'done').length;
  const mine = household.role === 'owner';
  return (
    <div className="card p-5 flex flex-col gap-3">
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 rounded-full bg-primary-100 text-primary-500 font-bold flex items-center justify-center">{mine ? 'You' : household.ownerName.slice(0, 1)}</div>
        <div className="flex flex-col min-w-0">
          <span className="font-display font-semibold text-[1.05rem]">{mine ? 'You' : household.ownerName}</span>
          <span className="text-[0.8rem] text-gray-500">Account holder · main applicant</span>
        </div>
      </div>
      <div className="flex justify-between items-baseline"><span className="eyebrow">Steps</span><span className="font-mono text-xs text-gray-500">{done} / {own.length}</span></div>
      <div className="progress-bar"><div className="progress-bar-fill" style={{ width: `${own.length ? Math.round((done / own.length) * 100) : 0}%` }} /></div>
      <p className="text-[0.82rem] text-gray-500 m-0">The main visa file. Everyone else’s application hangs off it.</p>
    </div>
  );
}

function PersonCard({ member, tasks, household, canEdit }: { member: FamilyMember; tasks: Task[]; household: Household; canEdit: boolean }) {
  const update = useUpdateFamilyMember();
  const remove = useDeleteFamilyMember();
  const invite = useInviteFamilyMember();
  const revoke = useRevokeFamilyInvite();
  const [email, setEmail] = useState(member.email ?? '');
  const [editing, setEditing] = useState(false);
  const [name, setName] = useState(member.name);
  const [age, setAge] = useState(member.age ?? '');
  const [birthDate, setBirthDate] = useState(member.birthDate ?? '');
  const isPartner = member.relationship === 'spouse';
  const docs = DOCS.filter((d) => !d.partnerOnly || isPartner);
  const docsDone = docs.filter((d) => member.documents[d.key]).length;
  const done = tasks.filter((t) => t.status === 'done').length;
  const inviteError = invite.error instanceof Error ? invite.error.message : null;

  const toggleDoc = (key: keyof FamilyMember['documents']) => {
    if (!canEdit) return;
    update.mutate({ memberId: member.id, data: { documents: { ...member.documents, [key]: !member.documents[key] } } });
  };

  const saveDetails = () => {
    update.mutate({ memberId: member.id, data: { name: name.trim() || member.name, age, birthDate } }, { onSuccess: () => setEditing(false) });
  };

  return (
    <div className="card p-5 flex flex-col gap-3">
      <div className="flex items-start gap-3">
        <div className="w-10 h-10 rounded-full bg-card-2 text-ink font-bold flex items-center justify-center flex-shrink-0">{member.name.slice(0, 1).toUpperCase()}</div>
        <div className="flex flex-col min-w-0 flex-1">
          {editing ? (
            <div className="flex flex-col gap-2">
              <input className="input" value={name} onChange={(e) => setName(e.target.value)} aria-label="Name" placeholder="Name" />
              {isPartner ? (
                <input className="input" type="date" value={birthDate} onChange={(e) => setBirthDate(e.target.value)} aria-label="Date of birth" />
              ) : (
                <input className="input" value={age} onChange={(e) => setAge(e.target.value)} aria-label="Age" placeholder="Age" />
              )}
              <div className="flex gap-2">
                <button className="btn btn-primary" onClick={saveDetails} disabled={update.isPending}>Save</button>
                <button className="btn btn-ghost" onClick={() => setEditing(false)}>Cancel</button>
              </div>
            </div>
          ) : (
            <>
              <span className="font-display font-semibold text-[1.05rem] truncate">{member.name}</span>
              <span className="text-[0.8rem] text-gray-500">
                {isPartner ? 'Partner' : 'Child'}
                {isPartner && member.birthDate ? ` · born ${new Date(`${member.birthDate.slice(0, 10)}T00:00:00Z`).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' })}` : ''}
                {!isPartner && member.age ? ` · age ${member.age}` : ''}
                {' · own visa file'}
              </span>
            </>
          )}
        </div>
        {canEdit && !editing ? (
          <div className="flex gap-1">
            <button className="text-xs text-gray-500 hover:text-ink" onClick={() => setEditing(true)}>Edit</button>
            <button
              className="text-gray-400 hover:text-accent-500"
              aria-label={`Remove ${member.name}`}
              onClick={() => { if (window.confirm(`Remove ${member.name}'s file? Their steps stay on the calendar.`)) remove.mutate(member.id); }}
            >
              <Trash2 className="w-4 h-4" />
            </button>
          </div>
        ) : null}
      </div>

      <div>
        <div className="flex justify-between items-baseline"><span className="eyebrow">Their documents</span><span className="font-mono text-xs text-gray-500">{docsDone} / {docs.length}</span></div>
        <ul className="mt-1.5 flex flex-col gap-1">
          {docs.map((d) => {
            const has = member.documents[d.key];
            return (
              <li key={d.key}>
                <button onClick={() => toggleDoc(d.key)} disabled={!canEdit} className="flex items-center gap-2 text-left text-[0.9rem] disabled:cursor-default">
                  {has ? <CheckCircle2 className="w-4 h-4 text-primary-500" /> : <Circle className="w-4 h-4 text-gray-300" />}
                  <span className={clsx(has && 'text-gray-500 line-through')}><Jargon text={d.label} /></span>
                </button>
              </li>
            );
          })}
        </ul>
      </div>

      <div className="flex justify-between items-baseline"><span className="eyebrow">Steps</span><span className="font-mono text-xs text-gray-500">{done} / {tasks.length}</span></div>
      <div className="progress-bar"><div className="progress-bar-fill" style={{ width: `${tasks.length ? Math.round((done / tasks.length) * 100) : 0}%` }} /></div>

      {isPartner && household.role === 'owner' ? (
        <div className="border-t border-rule-soft pt-3 flex flex-col gap-2">
          <span className="eyebrow">Their sign-in</span>
          {member.inviteStatus === 'joined' ? (
            <div className="flex items-center justify-between gap-2">
              <span className="text-sm text-gray-600 flex items-center gap-1.5"><CheckCircle2 className="w-4 h-4 text-primary-500" /> Signed in as {member.email}</span>
              <button className="text-xs text-gray-500 hover:text-accent-500" onClick={() => revoke.mutate(member.id)} disabled={revoke.isPending}>Remove access</button>
            </div>
          ) : member.inviteStatus === 'invited' ? (
            <div className="flex items-center justify-between gap-2">
              <span className="text-sm text-gray-600 flex items-center gap-1.5"><Mail className="w-4 h-4 text-gray-400" /> Invited · {member.email}</span>
              <div className="flex gap-2">
                <button className="text-xs text-primary-500 hover:text-primary-700" onClick={() => invite.mutate({ memberId: member.id, email: member.email ?? '' })} disabled={invite.isPending}>Resend</button>
                <button className="text-xs text-gray-500 hover:text-accent-500" onClick={() => revoke.mutate(member.id)} disabled={revoke.isPending}>Cancel</button>
              </div>
            </div>
          ) : (
            <form
              className="flex gap-2"
              onSubmit={(e) => { e.preventDefault(); if (email.trim()) invite.mutate({ memberId: member.id, email: email.trim() }); }}
            >
              <input className="input flex-1" type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="their@email.com" aria-label={`${member.name}'s email`} required />
              <button className="btn btn-secondary" type="submit" disabled={invite.isPending}><UserPlus className="w-4 h-4" /> Invite</button>
            </form>
          )}
          {inviteError ? <p className="text-xs text-accent-500 m-0">{inviteError}</p> : null}
          {member.inviteStatus === 'none' ? <p className="text-[0.78rem] text-gray-500 m-0">They get their own password and see this same file, with their steps marked. You can also just do it all yourself.</p> : null}
        </div>
      ) : null}
    </div>
  );
}

function AddPersonForm({ kind, onDone }: { kind: 'spouse' | 'child'; onDone: () => void }) {
  const create = useCreateFamilyMember();
  const [name, setName] = useState('');
  const [age, setAge] = useState('');
  const [birthDate, setBirthDate] = useState('');
  const err = create.error instanceof Error ? create.error.message : null;
  return (
    <form
      className="card p-5 flex flex-col gap-3"
      onSubmit={(e) => {
        e.preventDefault();
        create.mutate(
          {
            name: name.trim(),
            relationship: kind,
            birthDate,
            age,
            nationality: '',
            visaStatus: 'pending',
            documents: { passport: false, birthCertificate: false, marriageCertificate: false, photos: false },
          },
          { onSuccess: onDone }
        );
      }}
    >
      <span className="eyebrow">{kind === 'spouse' ? 'Your partner' : 'A child'}</span>
      <div className="grid sm:grid-cols-2 gap-3">
        <input className="input" value={name} onChange={(e) => setName(e.target.value)} placeholder="Full name as on their passport" aria-label="Name" required />
        {kind === 'spouse' ? (
          <input className="input" type="date" value={birthDate} onChange={(e) => setBirthDate(e.target.value)} aria-label="Date of birth" />
        ) : (
          <input className="input" value={age} onChange={(e) => setAge(e.target.value)} placeholder="Age" aria-label="Age" />
        )}
      </div>
      {err ? <p className="text-xs text-accent-500 m-0">{err}</p> : null}
      <div className="flex gap-2">
        <button className="btn btn-primary" type="submit" disabled={create.isPending}>Add</button>
        <button className="btn btn-ghost" type="button" onClick={onDone}>Cancel</button>
      </div>
    </form>
  );
}

/**
 * Every step that belongs to someone other than the account holder, with the
 * control to hand it to the partner. This is the "assign the kids' actions"
 * table: the family view is where the split of work is decided.
 */
function HouseholdSteps({ members, tasks, household }: { members: FamilyMember[]; tasks: Task[]; household: Household }) {
  const updateStatus = useUpdateTaskStatus();
  const others = tasks.filter((t) => personOf(t) !== 'you' && personOf(t) !== '').sort((a, b) => (a.due_date ?? '9').localeCompare(b.due_date ?? '9'));
  if (others.length === 0) return null;
  const hasPartner = Boolean(household.partner && household.partner.userId > 0);
  return (
    <div className="card overflow-hidden">
      <div className="flex justify-between items-baseline px-5 py-3.5 bg-card-2 border-b border-rule">
        <span className="font-display font-semibold">Their steps, on the shared calendar</span>
        <span className="font-mono text-[0.7rem] text-gray-500 uppercase">{others.filter((t) => t.status === 'done').length} of {others.length} done</span>
      </div>
      {!hasPartner && household.role === 'owner' ? (
        <p className="px-5 pt-3 text-[0.82rem] text-gray-500 m-0">Invite your partner above and a “who does this” choice appears on each step.</p>
      ) : null}
      <ul className="divide-y divide-rule-soft">
        {others.map((task) => {
          const done = task.status === 'done';
          return (
            <li key={task.id} className="flex items-center gap-3.5 px-5 py-3">
              <button
                onClick={() => updateStatus.mutate({ id: task.id, status: done ? 'todo' : 'done' })}
                aria-label={done ? `Mark "${task.title}" not done` : `Mark "${task.title}" done`}
                className="flex-shrink-0 text-primary-500"
              >
                {done ? <CheckCircle2 className="w-5 h-5" /> : <Circle className={clsx('w-5 h-5', task.is_overdue ? 'text-accent-500' : 'text-gray-300')} />}
              </button>
              <div className="flex flex-col min-w-0 flex-1">
                <span className={clsx('text-[0.95rem]', done ? 'text-gray-500 line-through' : 'font-semibold')}><Jargon text={task.title} /></span>
                <PersonChip task={task} members={members} household={household} />
              </div>
              <span className={clsx('font-mono text-[0.7rem]', task.is_overdue && !done ? 'text-accent-500' : 'text-gray-500')}>{dueLabel(task)}</span>
              <AssignSelect task={task} household={household} />
            </li>
          );
        })}
      </ul>
    </div>
  );
}
