import { GuideCard, Label, SiteNav } from '../components';
import { guides } from '../content/guides';

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
      <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 px-7 pb-10">
        {guides.map((g) => (
          <GuideCard
            key={g.slug}
            guide={{ when: g.when, title: g.title, summary: g.summary, href: `/guides/${g.slug}/` }}
          />
        ))}
      </div>
    </>
  );
}
