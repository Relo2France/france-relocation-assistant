import { Label, SiteNav } from '../components';
import { totalTopics } from '../content/coverage';
import { PRICE } from '../content/links';

/**
 * What this is, what it rests on, and what it is not. No biography and no
 * claims about outcomes: the page says how the information is produced and
 * kept, which is the only thing a reader can hold us to.
 */
export function About() {
  return (
    <>
      <SiteNav />
      <article className="px-7 pt-10 pb-12 max-w-[64ch]">
        <Label>About</Label>
        <h1 className="font-display font-semibold text-[clamp(1.8rem,4vw,2.4rem)] leading-[1.05] tracking-[-0.019em] mt-2 mb-4 text-balance">
          A move to France, written down in the order it happens
        </h1>
        <div className="prose-exact">
          <p className="mb-4">
            Relo2France is for Americans moving to France. It holds every requirement of the move
            in one place, in the order you will meet it, from choosing a visa route to renewing a
            residence permit years later.
          </p>
          <h2 className="font-display text-[1.15rem] font-semibold mt-7 mb-2 pb-2 border-b border-rule-soft">Where the information comes from</h2>
          <p className="mb-4">
            The knowledge base behind the guides and the portal holds {totalTopics} topics. Each
            one states only what an official French source states, names that source, and carries
            the month it was last checked. Every week the topics are re-checked against those
            sources, and anything that has changed is flagged for review before it is published.
          </p>
          <p className="mb-4">
            Where people report what actually happens at a consulate or a prefecture, that is kept
            separate and labelled as lived experience. It is useful, and it is not the rule.
          </p>
          <h2 className="font-display text-[1.15rem] font-semibold mt-7 mb-2 pb-2 border-b border-rule-soft">What membership is</h2>
          <p className="mb-4">
            The guides are free to read and always will be. Membership is {PRICE}, once, for life. It
            turns the same information into your own file: a dated plan counted back from your move,
            a dossier per person, documents drafted with your details, and an assistant that answers
            about your situation rather than about visas in general.
          </p>
          <h2 className="font-display text-[1.15rem] font-semibold mt-7 mb-2 pb-2 border-b border-rule-soft">What it is not</h2>
          <p className="mb-4">
            Relo2France is not affiliated with the French government and is not legal or tax advice.
            Requirements change and vary by consulate. For advice on your specific circumstances,
            you want an immigration lawyer or a French accountant, and the guides say so wherever
            that line is reached.
          </p>
          <p className="mb-0">
            Questions or corrections: <a href="mailto:support@relo2france.com">support@relo2france.com</a>.
          </p>
        </div>
      </article>
    </>
  );
}
