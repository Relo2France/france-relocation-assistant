import { external } from '../content/links';
import { Button, Label } from './index';
import { daysUntil, useMember } from '../member';

/**
 * The personalised layer.
 *
 * Every component here renders nothing at all when there is no member, so the
 * generic page stands on its own and the prerendered HTML is unaffected. What
 * appears after hydration is the difference between a reference article and
 * somebody's own file.
 */

/** A line at the top of a guide, tying it to this member's actual move. */
export function PersonalLead({ topic }: { topic: string }) {
  const member = useMember();
  if (!member) return null;

  const days = daysUntil(member.moveDate);
  const when = days === null ? 'your move' : `your move in ${days} days`;

  return (
    <p
      data-personal="lead"
      className="font-ui text-[0.95rem] bg-vine-soft text-ink rounded px-4 py-3 mb-5 border-l-2 border-vine"
    >
      <strong>{member.firstName}</strong> — you&rsquo;re on the{' '}
      <strong>{member.visaType}</strong> route to <strong>{member.destination}</strong>, with{' '}
      {when}. Here&rsquo;s how {topic} applies to you
      {member.applicants > 1 ? ` and your ${member.applicants - 1 === 1 ? 'spouse' : 'family'}` : ''}.
    </p>
  );
}

/** Replaces the generic "next in your timeline" card with their actual next step. */
export function PersonalNext() {
  const member = useMember();

  // Not signed in: show what the member view actually looks like, plainly
  // labelled as a preview. Never dressed up as this visitor's own data - a
  // fake progress bar that turns out to be nobody's is worse than no bar.
  if (!member) {
    return (
      <div data-personal="preview" className="border border-rule rounded overflow-hidden bg-card">
        <div className="px-4 py-2 bg-card-2 border-b border-rule-soft">
          <Label tone="honey">Preview · what members see here</Label>
        </div>
        <div className="p-4">
          <p className="font-display font-semibold text-[0.97rem] text-ink mb-1">
            Order your apostilles
          </p>
          <p className="text-[0.86rem] text-muted m-0">
            Dated back from your own move, with the documents you still owe.
          </p>

          <div className="mt-4 pt-3 border-t border-rule-soft" aria-hidden="true">
            <div className="flex items-baseline justify-between mb-[6px]">
              <Label>Your dossier</Label>
              <span className="font-mono text-[0.72rem] text-muted">4 / 9</span>
            </div>
            <div className="h-[5px] bg-rule-soft rounded-pill overflow-hidden">
              <span className="block h-full bg-vine/40 rounded-pill w-[44%]" />
            </div>
            <p className="font-mono text-[0.72rem] text-muted mt-3 mb-0">183 DAYS TO YOUR MOVE</p>
          </div>

          <div className="mt-4 flex flex-col gap-2">
            <Button href="/pricing/" full>See what you&rsquo;d get</Button>
            <Button href={external.signIn} variant="ghost" full>Sign in</Button>
          </div>
        </div>
      </div>
    );
  }

  const days = daysUntil(member.moveDate);
  const { ready, total } = member.dossier;
  const pct = total > 0 ? Math.round((ready / total) * 100) : 0;

  return (
    <div data-personal="next" className="border border-rule rounded p-4 bg-card">
      <span className="block mb-2"><Label tone="honey">Do this next</Label></span>
      <p className="font-display font-semibold text-[0.97rem] text-ink mb-1">
        {member.nextAction.what}
      </p>
      <p className="text-[0.86rem] text-muted m-0">{member.nextAction.note}</p>

      <div className="mt-4 pt-3 border-t border-rule-soft">
        <div className="flex items-baseline justify-between mb-[6px]">
          <Label>Your dossier</Label>
          <span className="font-mono text-[0.72rem] text-muted">{ready} / {total}</span>
        </div>
        <div className="h-[5px] bg-rule-soft rounded-pill overflow-hidden">
          <span className="block h-full bg-vine rounded-pill" style={{ width: `${pct}%` }} />
        </div>
        {days !== null ? (
          <p className="font-mono text-[0.72rem] text-muted mt-3 mb-0">
            {days} DAYS TO {member.destination.toUpperCase()}
          </p>
        ) : null}
      </div>

      <div className="mt-4">
        <Button href="/portal" full>Open my dossier</Button>
      </div>
    </div>
  );
}
