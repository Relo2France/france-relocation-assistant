import { Button, Label, Requirement, Requirements, SiteNav } from '../components';
import { external, PRICE, PRICE_NOTE } from '../content/links';
import { portalFeatures } from '../content/portal';
import { coverage, totalTopics } from '../content/coverage';

const questions = [
  {
    q: 'Is there a subscription?',
    a: 'No. One payment of ' + PRICE + ' and the account stays open. There is no renewal, no tier above this one, and no per-document charge.',
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
    q: 'Does it cover my whole family?',
    a: 'Yes. One account tracks every applicant moving with you, with their own documents and deadlines.',
  },
  {
    q: 'Is this legal advice?',
    a: 'No. It is the official requirements, organised and dated, plus tools to work through them. For advice on your specific circumstances you want an immigration lawyer or a French accountant.',
  },
];

export function Pricing() {
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
            <Button href={external.join} full>
              Get started
            </Button>
            <p className="font-ui text-[0.76rem] text-muted text-center mt-3 mb-0">
              Secure checkout · card payment
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
              <Requirement>Every applicant in your household on one account</Requirement>
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
          <Button href={external.join}>Get started — {PRICE}</Button>
          <Button href="/how-it-works/" variant="ghost">
            See how it works
          </Button>
          <span className="text-[0.82rem] text-muted">Already a member? </span>
          <a href={external.signIn} className="text-[0.82rem] text-vine">
            Sign in
          </a>
        </div>
      </section>
    </>
  );
}
