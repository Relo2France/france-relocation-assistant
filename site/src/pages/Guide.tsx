import {
  Caveat, Figure, PersonalLead, PersonalNext, PracticeNote, Requirement, Requirements, Step, TERM_ITEM, Term, TermList,
  SiteNav, SourceChip,
} from '../components';
import type { GuideDoc } from '../content/guides';
import { guides } from '../content/guides';
import { stageOfGuide } from '../content/stages';

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
          <span>{guide.sourceCount} official {guide.sourceCount === 1 ? 'source' : 'sources'}</span>
        </div>
      </header>

      <div className="grid md:grid-cols-[1fr_284px] border-t border-rule-soft">
        <article className="prose-exact px-7 md:px-9 py-7 pb-9">
          <PersonalLead topic={guide.title.toLowerCase()} />
          {guide.sections.map((section) => (
            <section key={section.heading}>
              <h2 className="font-display text-[1.3rem] leading-[1.25] tracking-[-0.015em] mt-9 first:mt-0 mb-3">
                {section.heading}
              </h2>
              {section.paragraphs?.map((p) => (
                <p key={p} className="mb-[14px]">{withFigures(p)}</p>
              ))}
              {section.requirements ? (
                section.requirements.every((r) => TERM_ITEM.test(r)) ? (
                  <TermList>
                    {section.requirements.map((r) => {
                      const m = TERM_ITEM.exec(r)!;
                      return <Term key={r} term={m[1] ?? ''} official={m[2]?.slice(1, -1)}>{withFigures(m[3] ?? '')}</Term>;
                    })}
                  </TermList>
                ) : (
                  <Requirements ordered={section.ordered}>
                    {section.requirements.map((r, n) => (
                      section.ordered ? <Step key={r} n={n + 1}>{withFigures(r)}</Step> : <Requirement key={r}>{withFigures(r)}</Requirement>
                    ))}
                  </Requirements>
                )
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
          <StageGuides slug={guide.slug} />
        </aside>
      </div>
    </>
  );
}

/** The other guides on the same stage, so a reader can keep going in order. */
function StageGuides({ slug }: { slug: string }) {
  const stage = stageOfGuide(slug);
  if (!stage) return null;
  const others = stage.slugs.filter((x) => x !== slug).map((x) => guides.find((g) => g.slug === x)).filter((g): g is GuideDoc => !!g);
  if (others.length === 0) return null;
  return (
    <div className="mt-6 pt-5 border-t border-rule-soft" data-kind="stage-guides">
      <span className="block mb-2 font-ui text-[0.67rem] font-bold uppercase tracking-[0.15em] text-muted">
        Guides on this stage · {stage.name}
      </span>
      <ul className="list-none m-0 p-0 flex flex-col gap-[7px]">
        {others.map((g) => (
          <li key={g.slug}>
            <a href={`/guides/${g.slug}/`} className="font-ui text-[0.88rem] font-semibold text-vine no-underline hover:text-ink">
              {g.title}
            </a>
          </li>
        ))}
      </ul>
    </div>
  );
}
