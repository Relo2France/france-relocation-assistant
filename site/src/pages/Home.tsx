import { Button, GuideCard, Label, SiteNav, Timeline } from '../components';
import { guides } from '../content/guides';
import { coverage, totalTopics } from '../content/coverage';
import { portalFeatures } from '../content/portal';
import { HERO_STEPS, heroFor } from '../content/hero';
import { daysUntil, useMember } from '../member';
import { external } from '../content/links';

/**
 * Two different cards for two different people.
 *
 * A first-time visitor has no plan yet, so the timeline is shown as what it
 * is: an example of a move in order, labelled as one, with nothing marked.
 * The buttons send them to read, or to see what membership is.
 *
 * A member gets their own month in the headline, their file's date, the
 * marker on the step they are actually at, and a button into the portal.
 * The prerendered page is always the first card; the second appears only
 * after hydration, from their own data.
 */
export function Hero() {
  const member = useMember();
  const { headline, nowIndex } = heroFor(member);
  const steps = HERO_STEPS.map((step, i) => (i === nowIndex ? { ...step, now: true } : step));
  const days = member ? daysUntil(member.moveDate) : null;
  const moveDate = member
    ? new Date(`${member.moveDate}T00:00:00Z`).toLocaleDateString('en-US', {
        month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC',
      })
    : null;

  return (
    <section className="px-7 pt-[50px] pb-10 grid md:grid-cols-[1.05fr_0.95fr] gap-11 items-center">
      <div>
        <Label>{member ? `${member.firstName}, your move` : 'For Americans moving to France'}</Label>
        <h1 className="font-display font-semibold text-[clamp(2rem,4.7vw,2.95rem)] leading-[1.04] tracking-[-0.019em] mt-3 mb-3 text-balance">
          {headline}
        </h1>
        {member ? (
          <p className="text-muted text-[1.05rem] max-w-[42ch]" data-personal="hero">
            {days !== null
              ? `Your file is dated back from ${moveDate}, ${days} days from now. Every requirement, re-checked against official French sources every week.`
              : 'Set your move date in your dossier and this timeline dates itself back from it.'}
          </p>
        ) : (
          <p className="text-muted text-[1.05rem] max-w-[42ch]">
            Every requirement in the order you need it — re-checked against official
            French sources every week, because the numbers move more often than anyone
            tells you.
          </p>
        )}
        <div className="flex gap-[10px] mt-6 flex-wrap items-center">
          {member ? (
            <>
              <Button href={external.portal}>Open my dossier</Button>
              <Button href="/guides/" variant="ghost">Browse the guides</Button>
            </>
          ) : (
            <>
              <Button href="/guides/">Browse the guides</Button>
              <Button href="/pricing/" variant="ghost">Plan my move</Button>
              <span className="text-[0.82rem] text-muted">Free to read · no card</span>
            </>
          )}
        </div>
      </div>
      <div>
        <span className="block mb-3">
          <Label tone={member ? 'honey' : 'muted'}>
            {member ? 'Where you are' : 'What a move looks like, counted back from the date'}
          </Label>
        </span>
        <Timeline steps={steps} />
      </div>
    </section>
  );
}

export function Home() {
  return (
    <>
      <SiteNav />

      <Hero />
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
        <Label tone="vine">Members</Label>
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
              <p className="text-[0.82rem] text-muted italic m-0">{feature.publicVersion}</p>
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
        <p className="mt-4 mb-0">
          <a href="/guides/" className="font-ui text-[0.9rem] font-semibold text-vine no-underline hover:text-ink">
            All {guides.length} guides →
          </a>
        </p>
      </section>
    </>
  );
}
