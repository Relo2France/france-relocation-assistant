import {
  Caveat, Figure, PersonalLead, PersonalNext, PracticeNote, Requirement, Requirements,
  SiteNav, SourceChip,
} from '../components';
import type { GuideDoc } from '../content/guides';

/** {{...}} marks a number someone would check twice, anywhere in a section. */
function withFigures(text: string) {
  return text.split(/(\{\{[^}]+\}\})/g).map((part, i) =>
    part.startsWith('{{') ? <Figure key={i}>{part.slice(2, -2)}</Figure> : <span key={i}>{part}</span>
  );
}

export function Guide({ guide }: { guide: GuideDoc }) {
  return (
    <>
      <SiteNav />

      <nav className="px-7 py-[14px] text-[0.8rem] text-muted border-b border-rule-soft">
        <a href="/guides/" className="text-muted no-underline hover:text-ink">Guides</a>
        {' / '}
        <span className="text-ink">{guide.title}</span>
      </nav>

      <header className="px-7 pt-8 pb-6">
        <span className="inline-flex items-center gap-2 mb-[14px] bg-honey-soft text-honey rounded-pill px-[14px] py-[6px] font-ui text-[0.78rem] font-bold">
          ◆ Do this {guide.when}
        </span>
        <h1 className="font-display font-semibold text-[clamp(1.7rem,3.6vw,2.2rem)] leading-[1.05] tracking-[-0.019em] m-0 text-balance">
          {guide.title}
        </h1>
        <div className="flex gap-4 flex-wrap mt-[14px] font-mono text-[0.72rem] text-muted uppercase">
          <span>Verified {guide.verified}</span>
          <span>{guide.sourceCount} official sources</span>
        </div>
      </header>

      <div className="grid md:grid-cols-[1fr_252px] border-t border-rule-soft">
        <article className="prose-exact px-7 py-7 pb-9">
          <PersonalLead topic={guide.title.toLowerCase()} />
          {guide.sections.map((section) => (
            <section key={section.heading}>
              <h2 className="font-display text-[1.1rem] mt-[26px] first:mt-0 mb-2 pb-[7px] border-b border-rule-soft">
                {section.heading}
              </h2>
              {section.paragraphs?.map((p) => (
                <p key={p} className="mb-[14px]">{withFigures(p)}</p>
              ))}
              {section.requirements ? (
                <Requirements>
                  {section.requirements.map((r) => (
                    <Requirement key={r}>{withFigures(r)}</Requirement>
                  ))}
                </Requirements>
              ) : null}
              {section.caveat ? <Caveat>{withFigures(section.caveat)}</Caveat> : null}
            </section>
          ))}

          {guide.practice ? (
            <PracticeNote sources={guide.practice.sources}>
              {guide.practice.paragraphs.map((p) => <p key={p}>{p}</p>)}
            </PracticeNote>
          ) : null}

          <div className="flex gap-[6px] flex-wrap mt-4">
            {guide.sources.map((s) => (
              <SourceChip key={s.label} kind={s.kind}>{s.label}</SourceChip>
            ))}
            {/* Community reporting is only ever what the In Practice note cites. */}
            {guide.practice ? (
              <SourceChip kind="community">{guide.practice.sources}</SourceChip>
            ) : null}
          </div>
        </article>

        <aside className="border-t md:border-t-0 md:border-l border-rule-soft px-[22px] py-[26px] bg-card-2">
          <PersonalNext />
        </aside>
      </div>
    </>
  );
}
