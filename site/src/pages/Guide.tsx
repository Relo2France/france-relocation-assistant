import {
  Caveat, Figure, Label, PracticeNote, Requirement, Requirements, SiteNav, SourceChip,
} from '../components';

/**
 * A guide article - the page the system exists to get right.
 *
 * Chrome above the article is warm. From the article element down it goes
 * monochrome and serif, because this is the part people act on. Content is the
 * live knowledge base text, not placeholder.
 */
export function Guide() {
  return (
    <>
      <SiteNav />

      <nav className="px-7 py-[14px] text-[0.8rem] text-muted border-b border-rule-soft">
        Guides / Visas / <span className="text-ink">Visitor visa</span>
      </nav>

      <header className="px-7 pt-8 pb-6">
        <span className="inline-flex items-center gap-2 mb-[14px] bg-honey-soft text-honey rounded-pill px-[14px] py-[6px] font-ui text-[0.78rem] font-bold">
          ◆ Do this 12 months out
        </span>
        <h1 className="font-display font-semibold text-[clamp(1.7rem,3.6vw,2.2rem)] leading-[1.05] tracking-[-0.019em] m-0 text-balance">
          The Long-Stay Visitor Visa
        </h1>
        <div className="flex gap-4 flex-wrap mt-[14px] font-mono text-[0.72rem] text-muted uppercase">
          <span>VLS-TS Visiteur</span>
          <span>Verified 2026-09</span>
          <span>4 official sources</span>
        </div>
      </header>

      <div className="grid md:grid-cols-[1fr_252px] border-t border-rule-soft">
        <article className="prose-exact px-7 py-7 pb-9">
          <h2 className="font-display text-[1.1rem] mt-0 mb-2 pb-[7px] border-b border-rule-soft">
            Who it&rsquo;s for
          </h2>
          <p className="mb-[14px]">
            For people who want to live in France without working. You must sign a
            declaration promising not to engage in any professional activity &mdash;
            paid or unpaid.
          </p>
          <p className="mb-[14px]">
            It suits retirees, people living on savings, pensions or investments, and
            accompanying spouses who will not be working.
          </p>

          <h2 className="font-display text-[1.1rem] mt-[26px] mb-2 pb-[7px] border-b border-rule-soft">
            What you&rsquo;ll need in 2026
          </h2>
          <Requirements>
            <Requirement>
              A passport valid for <Figure>6+ months</Figure> beyond your intended stay
            </Requirement>
            <Requirement>Proof of accommodation &mdash; lease, deed, or host attestation</Requirement>
            <Requirement>
              Financial resources benchmarked to net SMIC: <Figure>€1,478/month</Figure> from
              June 2026, up from <Figure>€1,443</Figure> in January
            </Requirement>
            <Requirement>Private health insurance covering the full stay</Requirement>
            <Requirement>A signed declaration of no professional activity</Requirement>
          </Requirements>
          <Caveat>
            That figure is a benchmark, not a legal floor. CESEDA requires only
            &ldquo;sufficient means of existence&rdquo;, and consulates assess case by case.
          </Caveat>

          <PracticeNote sources="r/expats, FrenchEntrée · reported through 2026">
            <p>
              Most Americans show <strong>1.5&ndash;2&times;</strong> the benchmark, or top up
              lower income with savings, to avoid extra questions. Social Security alone
              often gets queried.
            </p>
            <p>
              The no-work declaration is usually one signed paragraph you write yourself
              &mdash; no notary &mdash; and several consulates fold it into the cover letter.
            </p>
          </PracticeNote>

          <div className="flex gap-[6px] flex-wrap mt-4">
            <SourceChip kind="official">service-public.fr</SourceChip>
            <SourceChip kind="community">r/expats</SourceChip>
          </div>
        </article>

        <aside className="border-t md:border-t-0 md:border-l border-rule-soft px-[22px] py-[26px] bg-card-2">
          <div className="border border-rule rounded p-4 bg-card">
            <span className="block mb-2"><Label>Next in your timeline</Label></span>
            <p className="font-display font-semibold text-[0.97rem] text-ink mb-1">
              Order your apostilles
            </p>
            <p className="text-[0.86rem] text-muted m-0">
              Marriage and birth certificates. Start now &mdash; state processing runs to weeks.
            </p>
          </div>
        </aside>
      </div>
    </>
  );
}
