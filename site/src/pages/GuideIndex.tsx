import { GuideCard, Label, SiteNav } from '../components';
import { guidesByStage } from '../content/stages';

export function GuideIndex() {
  return (
    <>
      <SiteNav />
      <header className="px-7 pt-10 pb-6">
        <Label>Guides</Label>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.019em] mt-2 mb-2 text-balance">
          Everything, in the order you’ll need it
        </h1>
        <p className="text-muted max-w-[46ch] m-0">
          Re-checked against official French sources every week, because the numbers move
          more often than anyone tells you.
        </p>
      </header>
      {guidesByStage().map(({ stage, guides }, i) => (
        <section key={stage.id} className="px-7 pb-8" data-stage={stage.id}>
          <div className="flex items-baseline gap-3 flex-wrap mb-3 pt-2 border-t border-rule-soft">
            <span className="font-mono text-[0.72rem] text-muted">{stage.id === 'more' ? '' : `${i + 1} ·`}</span>
            <Label tone="vine">{stage.name}</Label>
            {stage.question ? <span className="text-[0.9rem] text-muted">{stage.question}</span> : null}
          </div>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {guides.map((g) => (
              <GuideCard
                key={g.slug}
                guide={{ when: g.when, title: g.title, summary: g.summary, href: `/guides/${g.slug}/` }}
              />
            ))}
          </div>
        </section>
      ))}
    </>
  );
}
