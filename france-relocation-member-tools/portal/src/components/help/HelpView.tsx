/**
 * HelpView
 *
 * How the portal works, in the member's terms: what each part is for, the
 * questions people actually ask, where to write to us, and the official
 * sites every step points at. No system status, no filler.
 */
import { useState } from 'react';
import { clsx } from 'clsx';
import { ChevronDown, ChevronUp, ExternalLink, MessageSquare } from 'lucide-react';
import { JOURNEY } from '@/journey/journey';
import { usePortalStore } from '@/store';

const PARTS: { title: string; body: string; view: string; label: string }[] = [
  { title: 'Where you are', body: 'Your home page. The road across the top, the current stage told in plain words with the order it goes in, and the next few dated steps. It changes as your file does.', view: 'dashboard', label: 'Open' },
  { title: 'The six stages', body: `${JOURNEY.map((s) => s.name).join(', ')}. Each stage page lists its steps in order, its guides, and when to bring in a professional. Steps are dated back from your move date, so change the date and the plan moves with it.`, view: 'stage', label: 'Open the current stage' },
  { title: 'Steps and how-tos', body: 'Every generated step opens with "How to do this": the order, where to go with the official link, what to bring, how long and what it costs. Tick a step done and the walkthrough on your home page ticks with it.', view: 'tasks', label: 'All steps' },
  { title: 'Documents & files', body: 'Upload a document and the portal recognises it, checks it against the requirement (passport validity, statement age, apostille), and ticks the dossier line it satisfies. Your Explore France reports are kept here too.', view: 'documents', label: 'Open' },
  { title: 'Checklists', body: 'The document lists for each stage. "Ready" means the document is on file or you said you handled it yourself; the dossier count on your home page comes from here.', view: 'checklists', label: 'Open' },
  { title: 'Family plans', body: 'One file per person. Add your partner and children, hand steps to your partner, or give them their own sign-in to complete their part. Their steps sit on the same calendar as yours.', view: 'family', label: 'Open' },
  { title: 'Deadlines', body: 'Every dated step across all six stages, overdue first, then month by month.', view: 'deadlines', label: 'Open' },
  { title: 'Ask about my case', body: 'Questions answered against your own file and the knowledge base, which is built from official sources and re-checked weekly. Turn on real-world insights to see what people who made the move report.', view: 'chat', label: 'Ask' },
  { title: 'Explore France', body: 'Compare a region, department or town with the rest of France and generate a report on the place you are considering. Reports land in Documents and are updated, not duplicated, when you ask again.', view: 'research', label: 'Open' },
  { title: 'Messages and Support', body: 'Messages is what the site and the team send you, plus the alerts your own file raises. Support is what you send us. They are different doors on purpose.', view: 'messages', label: 'Messages' },
];

const FAQ: { q: string; a: string }[] = [
  { q: 'Why does my stage say "Prepare" when I have five months to go?', a: 'The stages are worked out from your move date, not from what you have ticked. You are preparing until four months out, applying until the last month, moving in that month, arriving for ninety days, then settling. Open any stage from the sidebar whenever you like.' },
  { q: 'I changed my move date. What happens?', a: 'Every generated step is re-dated from the new date at once. Steps you wrote yourself keep their dates. The stage you are in may change too.' },
  { q: 'I changed my visa route. What happens?', a: 'The old route\'s steps still to do are removed, the new route\'s steps are added, and the dossier list changes to match. Steps you have done are kept.' },
  { q: 'What does "Ready" mean on the dossier?', a: 'The document is on file and passed its check, or you ticked "I handled this myself". "Waiting" means it is in progress; "Not started" means nothing is on file yet.' },
  { q: 'Do I need French translations for the application?', a: 'Not for the first application: consulates in the US take English documents. Sworn translations are for the prefecture at renewal and for CPAM, so that step sits after arrival.' },
  { q: 'Why are some steps in my file marked for my partner?', a: 'Each adult applies separately and the partner\'s documents are theirs. Steps carry a person; the filter chips on each stage show yours, theirs, or the children\'s. Hand a step over from Family plans.' },
  { q: 'Where did my Explore France report go?', a: 'Into Documents & files, under Saved reports. Open it there, download it as a PDF, or remove it. Asking again for the same place updates the report rather than making a second one.' },
  { q: 'When should I bring in a professional?', a: 'The portal says so on the stage it applies to, with the reason from your profile: a cross-border tax professional before the year you move for every US citizen, a notaire if you are buying, an immigration lawyer if the consulate refuses. It never gives tax or legal advice itself.' },
  { q: 'Can I add my own steps?', a: 'Yes, from any stage page or the steps list. Your own steps are never re-dated or removed by the plan.' },
  { q: 'How do I get a refund?', a: 'Write to Support within thirty days of joining and say so. No questions.' },
];

const OFFICIAL: { label: string; url: string; note: string }[] = [
  { label: 'France-Visas', url: 'https://france-visas.gouv.fr/', note: 'The application, the wizard and the document list for your route.' },
  { label: 'TLScontact', url: 'https://visas-fr.tlscontact.com/', note: 'The appointment, at any of ten US centres.' },
  { label: 'ANEF', url: 'https://administration-etrangers-en-france.interieur.gouv.fr/', note: 'Validate the visa after arrival; renewals.' },
  { label: 'service-public.fr', url: 'https://www.service-public.fr/', note: 'The official guide to every French procedure.' },
  { label: 'ameli.fr', url: 'https://www.ameli.fr/', note: 'Health cover and the carte Vitale.' },
  { label: 'ANTS', url: 'https://permisdeconduire.ants.gouv.fr/', note: 'Exchanging a driving licence.' },
  { label: 'Where to write for vital records (CDC)', url: 'https://www.cdc.gov/nchs/w2w/index.htm', note: 'Certified copies of US birth and marriage records, by state.' },
  { label: 'Apostille offices by state (NASS)', url: 'https://www.nass.org/can-I-help-you/apostilles-document-authentication', note: 'Who apostilles what, and the fee.' },
];

export default function HelpView() {
  const { setActiveView, setActiveStage } = usePortalStore();
  const [open, setOpen] = useState<number | null>(0);

  const go = (view: string) => {
    if (view === 'stage') setActiveStage('prepare');
    setActiveView(view);
  };

  return (
    <div className="p-6 md:p-8 flex flex-col gap-8">
      <header className="flex flex-col gap-1">
        <span className="eyebrow">Help</span>
        <h1 className="font-display text-[1.6rem] font-semibold tracking-[-0.018em] leading-tight m-0">How the portal works</h1>
        <p className="text-gray-600 m-0 max-w-[60ch]">What each part is for, the questions people ask, and where to write to us.</p>
      </header>

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 flex flex-col gap-8">
          <section aria-labelledby="parts-title" className="flex flex-col gap-3">
            <h2 id="parts-title" className="font-display text-[1.2rem] font-semibold m-0">The parts</h2>
            <ul className="list-none m-0 p-0 grid sm:grid-cols-2 gap-3">
              {PARTS.map((p) => (
                <li key={p.title} className="card p-4 flex flex-col gap-1.5">
                  <span className="font-semibold text-[0.95rem]">{p.title}</span>
                  <span className="text-[0.85rem] text-gray-600 leading-snug">{p.body}</span>
                  <button onClick={() => go(p.view)} className="self-start text-sm font-semibold text-primary-500 hover:text-primary-700 mt-1">{p.label} →</button>
                </li>
              ))}
            </ul>
          </section>

          <section aria-labelledby="faq-title" className="flex flex-col gap-3">
            <h2 id="faq-title" className="font-display text-[1.2rem] font-semibold m-0">Questions people ask</h2>
            <ul className="list-none m-0 p-0 card divide-y divide-rule-soft">
              {FAQ.map((f, i) => {
                const isOpen = open === i;
                return (
                  <li key={f.q}>
                    <button
                      onClick={() => setOpen(isOpen ? null : i)}
                      className="w-full flex items-start justify-between gap-4 px-5 py-3.5 text-left hover:bg-card-2 transition-colors"
                      aria-expanded={isOpen}
                    >
                      <span className={clsx('text-[0.95rem]', isOpen ? 'font-semibold' : 'font-medium')}>{f.q}</span>
                      {isOpen ? <ChevronUp className="w-4 h-4 text-gray-400 flex-shrink-0 mt-1" aria-hidden="true" /> : <ChevronDown className="w-4 h-4 text-gray-400 flex-shrink-0 mt-1" aria-hidden="true" />}
                    </button>
                    {isOpen ? <p className="px-5 pb-4 m-0 text-[0.9rem] text-gray-600 leading-relaxed max-w-[64ch]">{f.a}</p> : null}
                  </li>
                );
              })}
            </ul>
          </section>
        </div>

        <div className="flex flex-col gap-5">
          <section className="card p-5 flex flex-col gap-3" aria-labelledby="contact-title">
            <h2 id="contact-title" className="font-display text-[1.1rem] font-semibold m-0">Write to us</h2>
            <p className="text-[0.9rem] text-gray-600 m-0 leading-snug">Membership, the site, or something on a page that looks wrong. We answer within a day. For questions about your own move, ask the assistant; it knows your file.</p>
            <div className="flex flex-wrap gap-2">
              <button onClick={() => setActiveView('support')} className="btn btn-primary gap-1.5"><MessageSquare className="w-4 h-4" aria-hidden="true" /> Write to Support</button>
              <button onClick={() => setActiveView('chat')} className="btn btn-secondary">Ask about my case</button>
            </div>
            <p className="text-[0.8rem] text-gray-500 m-0 leading-snug">We are not lawyers or tax advisers. Where a step needs one, the portal says so and why.</p>
          </section>

          <section className="card p-5 flex flex-col gap-3" aria-labelledby="official-title">
            <h2 id="official-title" className="font-display text-[1.1rem] font-semibold m-0">The official sites</h2>
            <ul className="list-none m-0 p-0 flex flex-col divide-y divide-rule-soft">
              {OFFICIAL.map((o) => (
                <li key={o.url} className="py-2.5 flex flex-col gap-0.5">
                  <a href={o.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 text-[0.9rem] font-semibold text-primary-500 hover:text-primary-700">
                    {o.label} <ExternalLink className="w-3.5 h-3.5" aria-hidden="true" />
                  </a>
                  <span className="text-[0.8rem] text-gray-500 leading-snug">{o.note}</span>
                </li>
              ))}
            </ul>
          </section>
        </div>
      </div>
    </div>
  );
}
