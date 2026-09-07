export interface Piece {
  name: string;
  meta?: string;
  done?: boolean;
}

/**
 * The folder the member is assembling. Appears on the marketing site as proof
 * the product works, and in the portal as the thing they actually tick off.
 */
export function Dossier({ title, pieces }: { title: string; pieces: Piece[] }) {
  const done = pieces.filter((p) => p.done).length;

  return (
    <section className="border border-rule rounded bg-card" data-testid="dossier">
      <header className="flex items-center justify-between px-[14px] py-[10px] border-b border-rule">
        <span className="font-ui text-[0.67rem] font-bold uppercase tracking-[0.15em] text-muted">
          {title}
        </span>
        <span className="font-mono text-[0.68rem] text-muted" data-testid="dossier-count">
          {done} / {pieces.length} ready
        </span>
      </header>

      <ul className="list-none m-0 p-0">
        {pieces.map((piece) => (
          <li
            key={piece.name}
            data-done={piece.done ? 'true' : 'false'}
            className="grid grid-cols-[20px_1fr_auto] gap-[11px] items-start px-[14px] py-[11px] border-b border-rule-soft last:border-b-0 text-[0.88rem]"
          >
            <span
              aria-hidden="true"
              className={`w-[15px] h-[15px] border-[1.5px] rounded-[4px] mt-[3px] grid place-items-center text-[10px] font-bold leading-none ${
                piece.done ? 'bg-vine border-vine text-card' : 'border-rule bg-card'
              }`}
            >
              {piece.done ? '✓' : ''}
            </span>
            <span className={piece.done ? 'text-muted line-through' : 'font-medium'}>
              {piece.name}
            </span>
            <span className="font-mono text-[0.66rem] text-muted whitespace-nowrap pt-[3px]">
              {piece.meta ?? ''}
            </span>
          </li>
        ))}
      </ul>
    </section>
  );
}
