import { Button, GuideCard, Label, SiteNav } from '../components';
import { guides } from '../content/guides';

/**
 * A 404 that does something useful.
 *
 * Most people land here from a stale link or a renamed guide, so the page
 * offers the guides rather than apologising and stopping.
 */
export function NotFound() {
  return (
    <>
      <SiteNav />
      <section className="px-7 pt-12 pb-8">
        <Label>404</Label>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.019em] mt-2 mb-3 text-balance">
          That page isn’t here
        </h1>
        <p className="text-muted max-w-[46ch] mb-6">
          The link may be out of date, or the guide may have been renamed. The guides
          below cover everything from choosing a visa to opening a bank account.
        </p>
        <Button href="/guides/">Browse the guides</Button>
      </section>
      <section className="px-7 pb-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        {guides.slice(0, 4).map((g) => (
          <GuideCard
            key={g.slug}
            guide={{ when: g.when, title: g.title, summary: g.summary, href: `/guides/${g.slug}/` }}
          />
        ))}
      </section>
    </>
  );
}
