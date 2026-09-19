/**
 * Screenshots of the member portal: a short tour for the home page, a full
 * gallery for How it works. Images are lazy-loaded and sized, so the layout
 * does not jump as they arrive.
 */
import { SAMPLE_NOTE, SCREENS, type Screen } from '../content/screens';

function Shot({ screen, eager = false }: { screen: Screen; eager?: boolean }) {
  return (
    <img
      src={screen.src}
      alt={screen.alt}
      width={screen.width}
      height={screen.height}
      loading={eager ? 'eager' : 'lazy'}
      decoding="async"
      className="block w-full h-auto rounded-[10px] border border-rule bg-card shadow-[0_1px_2px_rgba(28,36,32,0.06),0_8px_24px_rgba(28,36,32,0.08)]"
    />
  );
}

function Caption({ screen }: { screen: Screen }) {
  return (
    <figcaption className="mt-3">
      <span className="font-display font-semibold text-[0.98rem] text-ink block">{screen.title}</span>
      <span className="text-[0.86rem] text-muted block mt-0.5">{screen.caption}</span>
    </figcaption>
  );
}

/** Home page: the dashboard large, three screens beside it. */
export function PortalTour() {
  const small = [SCREENS.step, SCREENS.documents, SCREENS.chat];
  return (
    <div className="mt-2 mb-8" data-kind="portal-tour">
      <figure className="m-0">
        <Shot screen={SCREENS.dashboard} />
        <Caption screen={SCREENS.dashboard} />
      </figure>
      <div className="grid sm:grid-cols-3 gap-6 mt-7">
        {small.map((s) => (
          <figure key={s.src} className="m-0">
            <Shot screen={s} />
            <Caption screen={s} />
          </figure>
        ))}
      </div>
      <p className="text-[0.78rem] text-muted mt-5 mb-0">{SAMPLE_NOTE}</p>
    </div>
  );
}

/** How it works: every screen, two to a row, with the phone view. */
export function PortalGallery() {
  const desktop = [SCREENS.dashboard, SCREENS.stage, SCREENS.step, SCREENS.documents, SCREENS.chat, SCREENS.family, SCREENS.deadlines];
  return (
    <div data-kind="portal-gallery">
      <div className="grid md:grid-cols-2 gap-x-8 gap-y-9">
        {desktop.map((s) => (
          <figure key={s.src} className="m-0">
            <Shot screen={s} />
            <Caption screen={s} />
          </figure>
        ))}
        <figure className="m-0 flex flex-col items-center md:items-start">
          <div className="w-[min(240px,70%)]">
            <Shot screen={SCREENS.phone} />
          </div>
          <Caption screen={SCREENS.phone} />
        </figure>
      </div>
      <p className="text-[0.78rem] text-muted mt-6 mb-0">{SAMPLE_NOTE}</p>
    </div>
  );
}
