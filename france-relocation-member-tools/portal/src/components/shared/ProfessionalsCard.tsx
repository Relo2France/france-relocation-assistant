/**
 * When to bring in a professional.
 *
 * The portal walks a member through the paperwork; it does not give tax or
 * legal advice. This card is where it says so out loud, per stage, with the
 * reason it applies to this member's file, so nobody finds out from a tax
 * bill that they should have asked someone.
 */
import { Scale } from 'lucide-react';
import type { ProfessionalPrompt } from '@/types';

const KIND: Record<ProfessionalPrompt['kind'], string> = {
  tax: 'Tax',
  law: 'Law',
  notaire: 'Property & estate',
  accountant: 'Accounting',
  courtier: 'Mortgage',
};

export default function ProfessionalsCard({ prompts, compact = false }: { prompts: ProfessionalPrompt[]; compact?: boolean }) {
  if (prompts.length === 0) return null;
  return (
    <div className="card p-5 flex flex-col gap-3 border-l-2 border-l-accent-500">
      <div className="flex items-center gap-2">
        <Scale className="w-4 h-4 text-accent-500" aria-hidden="true" />
        <span className="eyebrow">When to bring in a professional</span>
      </div>
      {!compact ? (
        <p className="text-[0.82rem] text-gray-600 m-0">Relo2France organises the requirements. It is not tax or legal advice. These are the points on your route where someone qualified should look.</p>
      ) : null}
      <ul className={compact ? 'grid sm:grid-cols-2 gap-3' : 'flex flex-col gap-3'}>
        {prompts.map((p) => (
          <li key={p.id} className="flex flex-col gap-0.5">
            <span className="font-mono text-[0.66rem] uppercase tracking-wide text-accent-500">{KIND[p.kind]} · {p.when}</span>
            <span className="font-semibold text-[0.95rem]">{p.who}</span>
            <span className="text-[0.85rem] text-gray-600">{p.why}</span>
            <span className="text-[0.76rem] text-gray-500 italic">{p.trigger}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
