import { Button, GuideCard, Label, SiteNav, Timeline } from '../components';
import { guides } from '../content/guides';
import { coverage, totalTopics } from '../content/coverage';
import { portalFeatures } from '../content/portal';

const steps = [
  { when: '12 months out', what: 'Choose your visa', note: 'Seven long-stay types. Retired and not working narrows it to one.' },
  { when: '6 months out', what: 'Order apostilles', note: 'Marriage and birth certificates. State processing runs to weeks.', now: true },
  { when: '3 months out', what: 'Book the consulate appointment', note: 'Slots fill first for spring movers.' },
  { when: 'On arrival', what: 'Validate through ANEF', note: 'Within three months, or the visa lapses.' },
];

export function Home() {
  return (
    <>
      <SiteNav />

      <section className="px-7 pt-[50px] pb-10 grid md:grid-cols-[1.05fr_0.95fr] gap-11 items-center">
        <div>
          <Label>For Americans moving to France</Label>
          <h1 className="font-display font-semibold text-[clamp(2rem,4.7vw,2.95rem)] leading-[1.04] tracking-[-0.019em] mt-3 mb-3 text-balance">
            You’re moving to France in March. Here’s what to do first.
          </h1>
          <p className="text-muted text-[1.05rem] max-w-[42ch]">
            Every requirement in the order you need it — re-checked against official
            French sources every week, because the numbers move more often than anyone
            tells you.
          </p>
          <div className="flex gap-[10px] mt-6 flex-wrap items-center">
            <Button href="/pricing/">Plan my move</Button>
            <span className="text-[0.82rem] text-muted">Free to browse · no card</span>
          </div>
        </div>
        <Timeline steps={steps} />
      </section>

      <section className="px-7 py-9 border-t border-rule-soft bg-card-2">
        <Label>What we cover</Label>
        <h2 className="font-display font-semibold text-[1.5rem] tracking-[-0.012em] mt-2 mb-2 text-balance">
          {totalTopics} topics, re-checked every week
        </h2>
        <p className="text-muted max-w-[52ch] mb-6">
          Requirements, fees and thresholds change without announcement — the SMIC
          benchmark moved twice in 2026 alone. Every topic is re-verified against
          official French sources, and anything that changed is flagged.
        </p>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-0 border-t border-rule">
          {coverage.map((area) => (
            <div key={area.name} className="flex items-baseline justify-between gap-4 py-[11px] border-b border-rule-soft">
              <div>
                <span className="font-display font-semibold text-[0.97rem]">{area.name}</span>
                <span className="block text-[0.82rem] text-muted">{area.examples}</span>
              </div>
              <span className="font-mono text-[0.76rem] text-muted tabular-nums">{area.topics}</span>
            </div>
          ))}
        </div>
      </section>

      <section className="px-7 py-9 border-t border-rule-soft">
        <Label tone="honey">Members</Label>
        <h2 className="font-display font-semibold text-[1.5rem] tracking-[-0.012em] mt-2 mb-2 text-balance">
          The guides tell you what is required. The portal does it with you.
        </h2>
        <p className="text-muted max-w-[54ch] mb-6">
          Everything above is free to read and always will be. What members get is the
          same information turned into their own file — dated, tracked, and answerable.
        </p>

        <div className="grid md:grid-cols-2 gap-x-8 gap-y-0 border-t border-rule">
          {portalFeatures.map((feature) => (
            <div key={feature.name} className="py-4 border-b border-rule-soft">
              <h3 className="font-display font-semibold text-[1rem] mb-1 flex items-baseline gap-2 flex-wrap">
                {feature.name}
                {feature.status === 'soon' ? (
                  <span className="font-ui font-bold text-[0.66rem] uppercase tracking-[0.07em] text-muted border border-rule rounded-pill px-[7px] py-px">
                    Coming soon
                  </span>
                ) : null}
              </h3>
              <p className="text-[0.9rem] text-muted mb-2">{feature.what}</p>
              <p className="text-[0.82rem] text-muted/80 italic m-0">{feature.publicVersion}</p>
            </div>
          ))}
        </div>

        <div className="flex gap-[10px] mt-7 flex-wrap items-center">
          <Button href="/pricing/">See what you’d get</Button>
          <span className="text-[0.82rem] text-muted">Lifetime access · no subscription</span>
        </div>
      </section>

      <section className="px-7 py-8 border-t border-rule-soft">
        <Label>Guides, in the order you’ll need them</Label>
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
          {guides.slice(0, 4).map((g) => (
            <GuideCard
              key={g.slug}
              guide={{ when: g.when, title: g.title, summary: g.summary, href: `/guides/${g.slug}/` }}
            />
          ))}
        </div>
      </section>
    </>
  );
}
