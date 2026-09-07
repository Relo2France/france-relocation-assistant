import { Button, Label, PracticeNote, Requirement, Requirements, SiteNav } from '../components';
import { external, PRICE } from '../content/links';
import { portalFeatures } from '../content/portal';

const steps = [
  {
    n: '01',
    title: 'Read the guides, free',
    body:
      'Every guide states what France requires, drawn from official government sources and dated so you can see when it was last checked. Nothing is behind a login, and nothing ever will be.',
  },
  {
    n: '02',
    title: 'Tell us your situation once',
    body:
      'Your route, your dates, who is coming with you, and what you have already gathered. It takes a few minutes and it is what turns a general requirement into your specific task list.',
  },
  {
    n: '03',
    title: 'Work the list, dated from your move',
    body:
      'Requirements become tasks counted back from the date you intend to arrive — so you find out an apostille takes weeks while there is still time to order one.',
  },
  {
    n: '04',
    title: 'Ask when something is unclear',
    body:
      'The assistant answers against your own file, not a generic FAQ. When the knowledge base cannot answer well, that question is logged and the gap gets researched and reviewed.',
  },
];

export function HowItWorks() {
  return (
    <>
      <SiteNav cta="Get started" />

      <header className="px-7 pt-9 pb-7 border-b border-rule-soft">
        <span className="block mb-3">
          <Label tone="vine">How it works</Label>
        </span>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.02em] m-0 text-balance max-w-[20ch]">
          The requirements are public. The sequence is the hard part.
        </h1>
        <p className="text-muted max-w-[58ch] mt-4 mb-0">
          France publishes what it wants. What it does not publish is the order, the lead times, or
          which pieces depend on each other. That is the whole job, and it is what this does.
        </p>
      </header>

      <section className="px-7 py-8">
        <div className="grid gap-x-8 gap-y-0 md:grid-cols-2 border-t border-rule">
          {steps.map((s) => (
            <div key={s.n} className="py-5 border-b border-rule-soft">
              <span className="font-mono text-[0.72rem] text-vine font-semibold">{s.n}</span>
              <h2 className="font-display font-semibold text-[1.1rem] mt-1 mb-[6px]">{s.title}</h2>
              <p className="text-[0.9rem] text-muted m-0">{s.body}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="px-7 pb-8">
        <h2 className="font-display font-semibold text-[1.35rem] tracking-[-0.015em] mb-1">
          What the free guides do, and where they stop
        </h2>
        <p className="text-muted max-w-[56ch] mb-5">
          Each row is the same subject seen twice: what anyone can read, and what your own file
          does with it.
        </p>
        <div className="border-t border-rule">
          {portalFeatures.map((f) => (
            <div key={f.name} className="grid md:grid-cols-2 gap-x-8 py-4 border-b border-rule-soft">
              <div>
                <h3 className="font-display font-semibold text-[0.98rem] mb-1 flex items-baseline gap-2 flex-wrap">
                  {f.name}
                  {f.status === 'soon' ? (
                    <span className="font-ui font-bold text-[0.66rem] uppercase tracking-[0.07em] text-muted border border-rule rounded-pill px-[7px] py-px">
                      Coming soon
                    </span>
                  ) : null}
                </h3>
                <p className="text-[0.88rem] text-muted m-0">{f.what}</p>
              </div>
              <p className="text-[0.85rem] text-muted/80 italic m-0 md:pt-6">{f.publicVersion}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="px-7 pb-8">
        <h2 className="font-display font-semibold text-[1.35rem] tracking-[-0.015em] mb-3">
          How the information stays current
        </h2>
        <Requirements>
          <Requirement>
            Every topic is re-checked against official French sources on a weekly schedule
          </Requirement>
          <Requirement>
            Proposed changes are reviewed by a person before anything is published — nothing
            updates itself
          </Requirement>
          <Requirement>
            Each guide shows the month it was last verified and the sources it rests on
          </Requirement>
          <Requirement>
            Questions the knowledge base answers badly are logged, researched, and reviewed the
            same way
          </Requirement>
        </Requirements>
        <PracticeNote sources="How this site is maintained">
          <p>
            Guides state only what an official source confirms. Anything drawn from people
            reporting their own experience appears in a note like this one, labelled, so you always
            know which kind of claim you are reading.
          </p>
        </PracticeNote>
      </section>

      <section className="px-7 pb-10">
        <div className="border border-rule rounded-[12px] px-6 py-7 bg-card-2">
          <h2 className="font-display font-semibold text-[1.25rem] tracking-[-0.015em] mt-0 mb-2">
            {PRICE} once. No subscription.
          </h2>
          <p className="text-muted max-w-[52ch] mb-5">
            One payment, lifetime access. Most moves take a year or more, and renewals in the
            middle of one are the last thing anybody needs.
          </p>
          <div className="flex gap-[10px] flex-wrap items-center">
            <Button href={external.join}>Get started — {PRICE}</Button>
            <Button href="/pricing/" variant="ghost">
              What is included
            </Button>
          </div>
        </div>
      </section>
    </>
  );
}
