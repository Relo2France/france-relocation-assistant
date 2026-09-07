export interface Guide {
  when: string;
  title: string;
  summary: string;
  href: string;
}

/** A guide in the index. The "when" is honey because it is a deadline, not a rule. */
export function GuideCard({ guide }: { guide: Guide }) {
  return (
    <a
      href={guide.href}
      className="border border-rule rounded p-4 flex flex-col gap-1 no-underline text-ink hover:border-vine transition-colors"
    >
      <span className="font-ui text-[0.67rem] font-bold uppercase tracking-[0.09em] text-honey">
        {guide.when}
      </span>
      <span className="font-display font-semibold text-[0.99rem]">{guide.title}</span>
      <span className="text-[0.85rem] text-muted">{guide.summary}</span>
    </a>
  );
}
