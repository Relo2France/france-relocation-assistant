export interface TimelineStep {
  when: string;
  what: string;
  note?: string;
  /** Where the reader is now. At most one step should carry this. */
  now?: boolean;
}

/**
 * The move, as a countdown. The spine runs vine to honey because the far end
 * is a deadline, and the "you are here" marker is honey for the same reason.
 */
export function Timeline({ steps }: { steps: TimelineStep[] }) {
  return (
    <ol className="relative pl-[26px] list-none m-0" data-testid="timeline">
      <span
        aria-hidden="true"
        className="absolute left-[7px] top-2 bottom-2 w-[2px] rounded-[2px]"
        style={{ background: 'linear-gradient(var(--vine), var(--honey))' }}
      />
      {steps.map((step) => (
        <li
          key={step.what}
          data-now={step.now ? 'true' : undefined}
          aria-current={step.now ? 'step' : undefined}
          className="relative pb-5 last:pb-0"
        >
          <span
            aria-hidden="true"
            className={`absolute left-[-23px] top-[7px] w-[10px] h-[10px] rounded-full border-2 ${
              step.now ? 'bg-honey border-honey ring-4 ring-honey-soft' : 'bg-card border-vine'
            }`}
          />
          <span
            className={`font-ui text-[0.71rem] font-bold uppercase tracking-[0.1em] ${
              step.now ? 'text-honey' : 'text-vine'
            }`}
          >
            {step.when}
            {step.now ? ' · you are here' : ''}
          </span>
          <span className="block font-display font-semibold text-[1rem] mt-px">{step.what}</span>
          {step.note ? <span className="block text-[0.86rem] text-muted">{step.note}</span> : null}
        </li>
      ))}
    </ol>
  );
}
