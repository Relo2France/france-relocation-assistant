/**
 * GuideView
 *
 * A public guide, rendered inside the portal for a member. Same text as the
 * site - the content is imported from the site package, so the sourcing rule
 * and its tests still govern every sentence - but framed by what the portal
 * knows: the member's route, destination and move date at the top, the
 * glossary "?" on every term, and a way to ask about this guide against
 * their own file.
 */
import { type GuideDoc, guideBySlug, guides } from '@site-guides';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import Jargon from '@/components/shared/Jargon';
import VisaFinder from '@/components/decide/VisaFinder';
import { useCurrentUser, useDashboard, useMemberProfile } from '@/hooks/useApi';
import { JOURNEY, timeToGo } from '@/journey/journey';
import { usePortalStore } from '@/store';

/** A requirement shaped "{{Term}} (official) — meaning" becomes a term row. */
const TERM_ITEM = /^\{\{([^}]+)\}\}\s*(\([^)]*\))?\s*—\s*([\s\S]*)$/;

/** {{...}} marks a number someone would check twice. */
function withFigures(text: string) {
  return text.split(/(\{\{[^}]+\}\})/g).map((part, i) =>
    part.startsWith('{{') ? (
      <mark key={i} className="fig font-bold text-inherit">{part.slice(2, -2)}</mark>
    ) : (
      <Jargon key={i} text={part} />
    )
  );
}

function stageOf(slug: string) {
  return JOURNEY.find((s) => s.guides.some((g) => g.slug === slug));
}

export default function GuideView() {
  const { activeGuide, setActiveView, setActiveStage, setChatDraft, setActiveGuide } = usePortalStore();
  const { data } = useDashboard();
  const { data: profile } = useMemberProfile();
  const { data: me } = useCurrentUser();
  const guide: GuideDoc | undefined = guideBySlug(activeGuide ?? '');
  const project = data?.project;
  const stage = activeGuide ? stageOf(activeGuide) : undefined;

  if (!guide) {
    return (
      <div className="p-6 md:p-8">
        <div className="card p-6">
          <p className="font-display font-semibold text-lg">That guide isn’t here.</p>
          <ul className="mt-3 flex flex-col gap-1.5">
            {guides.map((g) => (
              <li key={g.slug}><button onClick={() => setActiveGuide(g.slug)} className="text-primary-500 font-semibold hover:text-primary-700">{g.title}</button></li>
            ))}
          </ul>
        </div>
      </div>
    );
  }

  const p = (profile ?? {}) as { target_location?: string; applicants?: string };
  const route = data?.profile_visa_type && data.profile_visa_type !== 'undecided' ? data.profile_visa_label : null;
  const destination = p.target_location?.trim() || null;
  const applicants = p.applicants ?? '';
  const withFamily = applicants && applicants !== 'self' && applicants !== 'me' && applicants !== 'unknown';

  const ask = () => {
    setChatDraft(`About "${guide.title}": how does this apply to my situation${route ? ` on the ${route} route` : ''}${destination ? ` moving to ${destination}` : ''}?`);
    setActiveView('chat');
  };

  return (
    <div className="flex flex-col">
      <nav className="px-6 md:px-8 py-3 text-[0.8rem] text-gray-500 border-b border-rule-soft flex items-center gap-2">
        {stage ? (
          <button onClick={() => { setActiveStage(stage.id); setActiveView('stage'); }} className="inline-flex items-center gap-1 hover:text-ink">
            <ArrowLeft className="w-3.5 h-3.5" aria-hidden="true" /> {stage.number} · {stage.name}
          </button>
        ) : (
          <button onClick={() => setActiveView('dashboard')} className="inline-flex items-center gap-1 hover:text-ink"><ArrowLeft className="w-3.5 h-3.5" aria-hidden="true" /> Where you are</button>
        )}
        <span>/</span>
        <span className="text-ink">{guide.title}</span>
      </nav>

      <header className="px-6 md:px-8 pt-7 pb-5 bg-card border-b border-rule">
        <span className="badge bg-accent-100 text-accent-500 mb-3">◆ Do this {guide.when}</span>
        <h2 className="font-display text-[1.75rem] font-semibold tracking-[-0.018em] leading-tight">{guide.title}</h2>
        <div className="flex gap-4 flex-wrap mt-3 font-mono text-[0.72rem] text-gray-500 uppercase">
          <span>Verified {guide.verified}</span>
          <span>{guide.sourceCount} official {guide.sourceCount === 1 ? 'source' : 'sources'}</span>
        </div>
      </header>

      <div className="grid md:grid-cols-[minmax(0,1fr)_300px] gap-6 px-6 md:px-8 py-6">
        <article className="max-w-[42rem] font-serif text-[1.06rem] leading-[1.7]">
          {guide.slug === 'long-stay-visa-overview' ? (
            <VisaFinder currentRoute={data?.profile_visa_type ?? null} currentLabel={data?.profile_visa_label ?? null} />
          ) : null}
          {(route || destination || project?.target_move_date) ? (
            <p className="font-sans text-[0.95rem] bg-primary-100 text-ink rounded-lg px-4 py-3 mb-6 border-l-2 border-primary-500">
              <strong>{me?.first_name || me?.display_name || 'You'}</strong>
              {route ? <> — you’re on the <strong>{route}</strong> route</> : <> — no route chosen yet</>}
              {destination ? <> to <strong>{destination}</strong></> : null}
              {project?.target_move_date ? <>, {timeToGo(project)}</> : null}
              . Here’s how {guide.title.toLowerCase()} applies to you{withFamily ? ' and your family' : ''}.
            </p>
          ) : null}

          {guide.sections.map((section) => (
            <section key={section.heading}>
              <h3 className="font-display text-[1.3rem] font-semibold leading-[1.25] tracking-[-0.015em] mt-9 first:mt-0 mb-3">{section.heading}</h3>
              {section.paragraphs?.map((para) => <p key={para} className="mb-[0.95rem]">{withFigures(para)}</p>)}
              {section.requirements ? (
                section.requirements.every((r) => TERM_ITEM.test(r)) ? (
                  <dl className="mt-2 mb-5 grid gap-[14px]">
                    {section.requirements.map((r) => {
                      const m = TERM_ITEM.exec(r)!;
                      return (
                        <div key={r} className="grid sm:grid-cols-[11rem_minmax(0,1fr)] gap-x-[18px] gap-y-1 items-baseline">
                          <dt className="font-sans text-[0.95rem] font-bold leading-[1.35] text-primary-500 m-0">
                            {m[1]}
                            {m[2] ? <span className="block font-mono font-normal text-[0.74rem] text-gray-500 mt-[2px]">{m[2].slice(1, -1)}</span> : null}
                          </dt>
                          <dd className="m-0 text-[1rem]">{withFigures(m[3])}</dd>
                        </div>
                      );
                    })}
                  </dl>
                ) : (
                  <ul className="list-none mt-1 mb-5 p-0 flex flex-col gap-[10px]">
                    {section.requirements.map((r) => (
                      <li key={r} className="flex gap-3 text-[1rem]">
                        <span aria-hidden="true" className="w-[7px] h-[7px] mt-[0.7em] rounded-full bg-primary-500 flex-none" />
                        <span>{withFigures(r)}</span>
                      </li>
                    ))}
                  </ul>
                )
              ) : null}
              {section.caveat ? (
                <aside className="mt-1 mb-6 px-[18px] py-[14px] bg-card-2 border-l-2 border-rule rounded-r-[8px] font-sans text-[0.92rem] leading-[1.55]">
                  <span className="eyebrow block">Worth knowing</span>
                  <p className="mt-[6px] mb-0">{withFigures(section.caveat)}</p>
                </aside>
              ) : null}
            </section>
          ))}

          {guide.practice ? (
            <aside className="mt-8 bg-accent-100 border-l-2 border-accent-500 rounded-r-[10px] px-5 py-4 font-sans">
              <span className="eyebrow text-accent-500 block mb-2">What people actually experience</span>
              <div className="text-[0.93rem] leading-relaxed [&>p]:mb-2 [&>p:last-child]:mb-0">
                {guide.practice.paragraphs.map((para) => <p key={para}>{para}</p>)}
              </div>
              <cite className="not-italic block mt-3 font-mono text-[0.7rem] text-gray-500">{guide.practice.sources}</cite>
            </aside>
          ) : null}

          <div className="flex gap-1.5 flex-wrap mt-5 font-sans">
            {guide.sources.map((s) => (
              <span key={s.label} className="badge bg-primary-100 text-primary-500">Official · {s.label}</span>
            ))}
            {guide.practice ? <span className="badge bg-accent-100 text-accent-500">Community · {guide.practice.sources}</span> : null}
          </div>
        </article>

        <aside className="flex flex-col gap-5">
          <div className="card p-5 flex flex-col gap-2">
            <span className="eyebrow">Ask about this guide</span>
            <p className="text-sm text-gray-600">The assistant answers against your file and the knowledge base, and shows where each answer comes from.</p>
            <button onClick={ask} className="btn btn-primary self-start mt-1 gap-1.5">How does this apply to me? <ArrowRight className="w-4 h-4" aria-hidden="true" /></button>
          </div>
          {stage ? (
            <div className="card p-5 flex flex-col gap-2">
              <span className="eyebrow">Part of stage {stage.number}</span>
              <p className="font-display font-semibold">{stage.name}</p>
              <p className="text-sm text-gray-600">{stage.question}</p>
              <button onClick={() => { setActiveStage(stage.id); setActiveView('stage'); }} className="btn btn-secondary self-start mt-1">Open the stage</button>
            </div>
          ) : null}
          <div className="card p-5 flex flex-col gap-2">
            <span className="eyebrow">More guides</span>
            {guides.filter((g) => g.slug !== guide.slug).slice(0, 6).map((g) => (
              <button key={g.slug} onClick={() => setActiveGuide(g.slug)} className="text-left text-[0.9rem] font-semibold text-primary-500 hover:text-primary-700">{g.title}</button>
            ))}
          </div>
        </aside>
      </div>
    </div>
  );
}
