/**
 * Marks where a claim came from.
 *
 * "official" is a government source and reads in the brand colour. "community"
 * is anecdote and reads honey, because honey means not law. That distinction is
 * the point of the component - it should never be possible to render a Reddit
 * consensus in the same colour as a legal requirement.
 */
export type SourceKind = 'official' | 'community';

export function SourceChip({ kind, children }: { kind: SourceKind; children: string }) {
  const styles =
    kind === 'official' ? 'text-vine bg-vine-soft' : 'text-honey bg-honey-soft';
  return (
    <span
      data-source={kind}
      className={`font-ui text-[0.66rem] font-semibold px-[10px] py-[4px] rounded-pill ${styles}`}
    >
      {kind === 'official' ? 'Official' : 'Community'} · {children}
    </span>
  );
}
