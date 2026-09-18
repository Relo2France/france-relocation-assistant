/**
 * Marks where a claim came from.
 *
 * "official" is a government source and reads in the brand colour. "community"
 * is anecdote and reads honey, because honey means not law. That distinction is
 * the point of the component - it should never be possible to render a Reddit
 * consensus in the same colour as a legal requirement.
 *
 * An official chip whose label names a site we know links to that site, so a
 * reader can check the claim at its source. A community chip never links: it
 * names reporting, not a place to verify it.
 */
export type SourceKind = 'official' | 'community';

/**
 * Official sites a chip may link to. Ordered most specific first, so a label
 * naming the ANEF portal is not matched by the ministry's own domain.
 */
const OFFICIAL_SITES: [domain: string, url: string][] = [
  ['administration-etrangers-en-france.interieur.gouv.fr', 'https://administration-etrangers-en-france.interieur.gouv.fr/'],
  ['visas-fr.tlscontact.com', 'https://visas-fr.tlscontact.com/'],
  ['usa.campusfrance.org', 'https://usa.campusfrance.org/'],
  ['service-public.gouv.fr', 'https://www.service-public.gouv.fr/'],
  ['service-public.fr', 'https://www.service-public.gouv.fr/'],
  ['france-visas.gouv.fr', 'https://france-visas.gouv.fr/'],
  ['legifrance.gouv.fr', 'https://www.legifrance.gouv.fr/'],
  ['interieur.gouv.fr', 'https://www.interieur.gouv.fr/'],
  ['impots.gouv.fr', 'https://www.impots.gouv.fr/'],
  ['banque-france.fr', 'https://www.banque-france.fr/'],
  ['notaires.fr', 'https://www.notaires.fr/'],
  ['ameli.fr', 'https://www.ameli.fr/'],
  ['cleiss.fr', 'https://www.cleiss.fr/'],
  ['justice.fr', 'https://www.justice.fr/'],
  ['fincen.gov', 'https://www.fincen.gov/'],
  ['irs.gov', 'https://www.irs.gov/'],
];

/** The official site a chip label names, or null when it names none we know. */
export function officialUrl(label: string): string | null {
  const lower = label.toLowerCase();
  for (const [domain, url] of OFFICIAL_SITES) {
    if (new RegExp(`(^|[^a-z0-9.-])${domain.replace(/[.-]/g, '\\$&')}($|[^a-z0-9-])`).test(lower)) {
      return url;
    }
  }
  return null;
}

export function SourceChip({ kind, children }: { kind: SourceKind; children: string }) {
  const styles =
    kind === 'official' ? 'text-vine bg-vine-soft' : 'text-honey bg-honey-soft';
  const className = `font-ui text-[0.66rem] font-semibold px-[10px] py-[4px] rounded-pill ${styles}`;
  const text = `${kind === 'official' ? 'Official' : 'Community'} · ${children}`;
  const href = kind === 'official' ? officialUrl(children) : null;

  if (href) {
    return (
      <a
        data-source={kind}
        href={href}
        target="_blank"
        rel="noopener noreferrer"
        className={`${className} no-underline hover:underline`}
      >
        {text}
        <span className="sr-only"> (opens in a new tab)</span>
      </a>
    );
  }

  return (
    <span data-source={kind} className={className}>
      {text}
    </span>
  );
}
