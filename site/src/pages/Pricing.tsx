import { Button, Label, Requirement, Requirements, SiteNav } from '../components';
import { external, FAMILY_ADDON_ON_SALE, familyCopy, GUARANTEE, PRICE, PRICE_NOTE, REFUND_DAYS } from '../content/links';
import { LEGAL_PAGES } from '../content/legal';
import { portalFeatures } from '../content/portal';
import { coverage, totalTopics } from '../content/coverage';
import { useMember } from '../member';

const family = familyCopy();

const questions = [
  {
    q: 'Is there a subscription?',
    a: `No. One payment of ${PRICE} and the account stays open. ${family.extras}`,
  },
  {
    q: 'What if the rules change after I join?',
    a: 'Topics are re-checked against official French sources weekly and revised when they change. That is the point of lifetime access — a move takes long enough that the requirements can move under you.',
  },
  {
    q: 'Do I need this if the guides are free?',
    a: 'If you only need to know what is required, no — read the guides and keep your money. The membership is for the sequencing: what to start now, what depends on what, and what your own file still lacks.',
  },
  {
    q: 'What if it is not for me?',
    a: `Email us within ${REFUND_DAYS} days of joining and you get a full refund. No form, no reason needed, no partial amounts. The refund policy page sets out exactly how it works.`,
  },
  {
    q: 'Does it cover my whole family?',
    a: family.answer,
  },
  {
    q: 'Is this legal advice?',
    a: 'No. It is the official requirements, organised and dated, plus tools to work through them. For advice on your specific circumstances you want an immigration lawyer or a French accountant.',
  },
];

/**
 * The one page whose buttons go to checkout. Everywhere else, "Get started"
 * lands here first, so nobody is sent to a payment form before the price.
 * A signed-in member is not sold to again: the buttons open their dossier.
 */
export function Pricing() {
  const member = useMember();
  return (
    <>
      <SiteNav cta={`Join — ${PRICE}`} />

      <header className="px-7 pt-9 pb-7 border-b border-rule-soft">
        <span className="block mb-3">
          <Label tone="vine">Pricing</Label>
        </span>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.02em] m-0 text-balance max-w-[18ch]">
          One price. Paid once.
        </h1>
        <p className="text-muted max-w-[56ch] mt-4 mb-0">
          The guides are free and always will be. Membership is for everything that turns those
          requirements into your own dated, tracked file.
        </p>
      </header>

      <section className="px-7 py-8">
        <div className="grid md:grid-cols-[300px_1fr] gap-8">
          <div className="border border-vine rounded-[12px] px-6 py-7 bg-vine-soft self-start">
            <span className="font-ui text-[0.74rem] font-bold uppercase tracking-[0.08em] text-vine">
              Lifetime access
            </span>
            <p className="font-display font-semibold text-[2.6rem] leading-none tracking-[-0.03em] my-3">
              {PRICE}
            </p>
            <p className="text-[0.88rem] text-muted mt-0 mb-5">{PRICE_NOTE}</p>
            {member ? (
              <Button href={external.portal} full>
                Open my dossier
              </Button>
            ) : (
              <Button href={external.join} full>
                Join — {PRICE}
              </Button>
            )}
            <p className="font-ui text-[0.76rem] text-muted text-center mt-3 mb-0">
              {member ? 'You’re a member. This is what you have.' : 'Secure checkout · card payment'}
            </p>
            {member ? null : (
              <p className="font-ui text-[0.8rem] text-ink text-center mt-3 mb-0 pt-3 border-t border-rule-soft">
                {GUARANTEE}
              </p>
            )}
            <p className="font-ui text-[0.76rem] text-muted mt-4 mb-0 pt-3 border-t border-rule-soft">
              <span className="font-bold text-ink">{family.cardTitle}</span> · {family.cardNote}
              {FAMILY_ADDON_ON_SALE ? (
                <>
                  {' '}
                  <a href={external.familyAddon} className="text-vine font-semibold">Add it to your membership</a>
                </>
              ) : null}
            </p>
            <p className="font-ui text-[0.74rem] text-muted text-center mt-4 mb-0" data-kind="legal-links">
              {LEGAL_PAGES.map((page, i) => (
                <span key={page.path}>
                  {i > 0 ? ' · ' : ''}
                  <a href={page.path} className="text-muted underline hover:text-ink">
                    {page.linkText}
                  </a>
                </span>
              ))}
            </p>
          </div>

          <div>
            <h2 className="font-display font-semibold text-[1.2rem] tracking-[-0.015em] mt-0 mb-3">
              What is included
            </h2>
            <Requirements>
              {portalFeatures.map((f) => (
                <Requirement key={f.name}>
                  {f.name}
                  {f.status === 'soon' ? ' — coming soon' : ''}. {f.what}
                </Requirement>
              ))}
              <Requirement>
                The full knowledge base — {totalTopics} topics across {coverage.length} areas, kept
                current
              </Requirement>
              <Requirement>{family.included}</Requirement>
              <Requirement>
                Told plainly when a step needs a tax professional, a lawyer or a notaire, and why it
                applies to your file
              </Requirement>
            </Requirements>
          </div>
        </div>
      </section>

      <section className="px-7 pb-9">
        <h2 className="font-display font-semibold text-[1.35rem] tracking-[-0.015em] mb-4">
          Questions people ask before joining
        </h2>
        <div className="border-t border-rule">
          {questions.map((item) => (
            <div key={item.q} className="py-4 border-b border-rule-soft">
              <h3 className="font-display font-semibold text-[1rem] mb-[6px]">{item.q}</h3>
              <p className="text-[0.9rem] text-muted m-0 max-w-[70ch]">{item.a}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="px-7 pb-10">
        <div className="flex gap-[10px] flex-wrap items-center">
          {member ? (
            <Button href={external.portal}>Open my dossier</Button>
          ) : (
            <Button href={external.join}>Join — {PRICE}</Button>
          )}
          <Button href="/how-it-works/" variant="ghost">
            See how it works
          </Button>
          {member ? null : (
            <>
              <span className="text-[0.82rem] text-muted">Already a member? </span>
              <a href={external.signIn} className="text-[0.82rem] text-vine">
                Sign in
              </a>
            </>
          )}
        </div>
      </section>
    </>
  );
}
