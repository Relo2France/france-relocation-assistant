import { Button, GuideCard, Label, SiteNav, Timeline } from '../components';
import { guides } from '../content/guides';

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
            <Button href="/start">Plan my move</Button>
            <span className="text-[0.82rem] text-muted">Free to browse · no card</span>
          </div>
        </div>
        <Timeline steps={steps} />
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
