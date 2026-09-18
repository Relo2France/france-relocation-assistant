import { Label, SiteNav } from '../components';
import { LEGAL_PAGES, LEGAL_UPDATED, type LegalPage } from '../content/legal';

/**
 * Terms, privacy and refunds: plain pages, readable at a sitting. The date is
 * at the top because it is the first thing anyone checking a policy looks for.
 */
export function Legal({ page }: { page: LegalPage }) {
  const others = LEGAL_PAGES.filter((p) => p.path !== page.path);
  return (
    <>
      <SiteNav />
      <article className="px-7 pt-10 pb-12 max-w-[68ch]">
        <Label>Legal</Label>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.019em] mt-2 mb-2 text-balance">
          {page.title}
        </h1>
        <p className="font-mono text-[0.74rem] text-muted uppercase tracking-[0.04em] mt-0 mb-6" data-kind="updated">
          Last updated {LEGAL_UPDATED}
        </p>
        <div className="prose-exact">
          <p className="mb-4">{page.intro}</p>
          {page.sections.map((section) => (
            <section key={section.heading}>
              <h2 className="font-display text-[1.15rem] font-semibold mt-7 mb-2 pb-2 border-b border-rule-soft">
                {section.heading}
              </h2>
              {section.paragraphs.map((p) => (
                <p key={p} className="mb-4">{p}</p>
              ))}
              {section.list ? (
                <ul className="mb-4 pl-5 list-disc flex flex-col gap-[6px]">
                  {section.list.map((item) => (
                    <li key={item}>{item}</li>
                  ))}
                </ul>
              ) : null}
            </section>
          ))}
        </div>
        <p className="font-ui text-[0.85rem] text-muted mt-8 mb-0 pt-4 border-t border-rule-soft">
          See also:{' '}
          {others.map((p, i) => (
            <span key={p.path}>
              {i > 0 ? ' · ' : ''}
              <a href={p.path} className="text-vine">{p.title}</a>
            </span>
          ))}
        </p>
      </article>
    </>
  );
}
