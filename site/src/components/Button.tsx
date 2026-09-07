import type { ReactNode } from 'react';

type Variant = 'solid' | 'ghost';

interface ButtonProps {
  children: ReactNode;
  href?: string;
  variant?: Variant;
  full?: boolean;
  onClick?: () => void;
}

const base =
  'inline-block font-ui text-[0.83rem] font-semibold px-[17px] py-[9px] rounded-pill border transition-colors';

const variants: Record<Variant, string> = {
  solid: 'bg-vine text-on-brand border-vine hover:opacity-90',
  ghost: 'bg-transparent text-ink border-rule hover:bg-card-2',
};

export function Button({ children, href, variant = 'solid', full, onClick }: ButtonProps) {
  const cls = `${base} ${variants[variant]} ${full ? 'w-full text-center' : ''}`;
  return href ? (
    <a className={cls} href={href}>
      {children}
    </a>
  ) : (
    <button className={cls} type="button" onClick={onClick}>
      {children}
    </button>
  );
}
