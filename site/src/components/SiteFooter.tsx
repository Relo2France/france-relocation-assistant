import { guides } from '../content/guides';
import { external, PRICE } from '../content/links';

function Column({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div>
      <h2 className="font-ui text-[0.72rem] font-bold uppercase tracking-[0.09em] text-muted mb-[10px]">
        {title}
      </h2>
      <ul className="list-none m-0 p-0 flex flex-col gap-[7px]">{children}</ul>
    </div>
  );
}

function Item({ href, children }: { href: string; children: React.ReactNode }) {
  return (
    <li>
      <a href={href} className="font-ui text-[0.85rem] text-muted no-underline hover:text-ink">
        {children}
      </a>
    </li>
  );
}

/**
 * The footer carries the site's claim about itself, so it says what the claim
 * rests on: official sources, re-checked weekly, dated. A footer full of links
 * and no accountability would be the wrong kind of polish.
 */
export function SiteFooter() {
  const year = new Date().getUTCFullYear();

  return (
    <footer className="border-t border-rule bg-card-2 px-7 pt-9 pb-7">
      <div className="grid gap-8 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
          <span className="font-display font-bold text-[1.05rem] tracking-[-0.02em]">
            Relo<span className="text-vine">2</span>France
          </span>
          <p className="text-[0.85rem] text-muted mt-2 mb-0 max-w-[34ch]">
            Every requirement for an American moving to France, in the order you need it.
          </p>
          <p className="font-mono text-[0.7rem] text-muted uppercase tracking-[0.04em] mt-3 mb-0">
            Checked against official French sources weekly
          </p>
        </div>

        <Column title="Guides">
          {guides.slice(0, 5).map((g) => (
            <Item key={g.slug} href={`/guides/${g.slug}/`}>
              {g.title}
            </Item>
          ))}
          <Item href="/guides/">All guides</Item>
        </Column>

        <Column title="Membership">
          <Item href="/how-it-works/">How it works</Item>
          <Item href="/pricing/">Pricing — {PRICE} for life</Item>
          <Item href={external.join}>Join</Item>
          <Item href={external.signIn}>Sign in</Item>
          <Item href={external.account}>Your account</Item>
        </Column>

        <Column title="Relo2France">
          <Item href={external.about}>About</Item>
          <Item href={external.portal}>Member portal</Item>
        </Column>
      </div>

      <div className="border-t border-rule-soft mt-8 pt-[18px] flex flex-wrap gap-x-6 gap-y-2 justify-between items-baseline">
        <p className="font-ui text-[0.78rem] text-muted m-0">
          © {year} Relo2France. Not affiliated with the French government.
        </p>
        <p className="font-ui text-[0.78rem] text-muted m-0 max-w-[62ch]">
          Information here is drawn from official French government sources and is general, not
          legal or tax advice. Requirements change and vary by consulate — confirm your own case
          before you act on it.
        </p>
      </div>
    </footer>
  );
}
