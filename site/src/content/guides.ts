/**
 * Guide content.
 *
 * Slugs deliberately match the URLs already indexed on relo2france.com, so the
 * cutover needs no redirects for these pages at all. Changing one is a
 * deliberate act with an SEO cost - see guides.test.ts, which pins them.
 */
export interface GuideSection {
  heading: string;
  paragraphs?: string[];
  requirements?: string[];
  caveat?: string;
}

export interface GuideDoc {
  slug: string;
  title: string;
  /** Shown in the index and the timeline. A deadline, not a rule. */
  when: string;
  summary: string;
  /** <meta name="description">, and the gallery/search snippet. */
  description: string;
  verified: string;
  sourceCount: number;
  sections: GuideSection[];
  practice?: { paragraphs: string[]; sources: string };
}

export const guides: GuideDoc[] = [
  {
    slug: 'long-stay-visa-overview',
    title: 'Long-Stay Visa Overview',
    when: '12 months out',
    summary: 'Which of the seven applies to you',
    description:
      'The seven French long-stay visa types for US citizens, and how to tell which one fits your situation.',
    verified: '2026-09',
    sourceCount: 3,
    sections: [
      {
        heading: 'Why the type matters',
        paragraphs: [
          'US citizens need a long-stay visa (visa de long séjour, VLS-TS) to live in France for more than 90 days. The type you apply for governs what you may do once you arrive, and changing it later is harder than choosing correctly now.',
        ],
      },
    ],
  },
  {
    slug: 'visitor-visa-requirements',
    title: 'The Long-Stay Visitor Visa',
    when: '12 months out',
    summary: 'The non-working route, in full',
    description:
      'Requirements for the French VLS-TS Visiteur in 2026, including the SMIC-based income benchmark and the declaration of no professional activity.',
    verified: '2026-09',
    sourceCount: 4,
    sections: [
      {
        heading: 'Who it’s for',
        paragraphs: [
          'For people who want to live in France without working. You must sign a declaration promising not to engage in any professional activity — paid or unpaid.',
          'It suits retirees, people living on savings, pensions or investments, and accompanying spouses who will not be working.',
        ],
      },
      {
        heading: 'What you’ll need in 2026',
        requirements: [
          'A passport valid for {{6+ months}} beyond your intended stay',
          'Proof of accommodation — lease, deed, or host attestation',
          'Financial resources benchmarked to net SMIC: {{€1,478/month}} from June 2026, up from {{€1,443}} in January',
          'Private health insurance covering the full stay',
          'A signed declaration of no professional activity',
        ],
        caveat:
          'That figure is a benchmark, not a legal floor. CESEDA requires only “sufficient means of existence”, and consulates assess case by case.',
      },
    ],
    practice: {
      paragraphs: [
        'Most Americans show 1.5–2× the benchmark, or top up lower income with savings, to avoid extra questions. Social Security alone often gets queried.',
        'The no-work declaration is usually one signed paragraph you write yourself — no notary — and several consulates fold it into the cover letter.',
      ],
      sources: 'r/expats, FrenchEntrée · reported through 2026',
    },
  },
  { slug: 'buying-property-france', title: 'Buying Property in France', when: '9 months out', summary: 'Before you sign anything', description: 'How property purchase works in France for US buyers, and what to settle before signing.', verified: '2026-09', sourceCount: 3, sections: [{ heading: 'Before you sign', paragraphs: ['French property purchase runs through a notaire, not a solicitor, and the compromis de vente commits you earlier than a US contract would.'] }] },
  { slug: 'role-of-notaire', title: 'The Role of the Notaire', when: '9 months out', summary: 'Not a solicitor, and not optional', description: 'What a French notaire does in a property purchase, and why you cannot skip one.', verified: '2026-09', sourceCount: 2, sections: [{ heading: 'What they actually do', paragraphs: ['A notaire is a public officer, not your advocate. They act for the transaction rather than for you, which surprises most American buyers.'] }] },
  { slug: 'french-healthcare-overview', title: 'French Healthcare Overview', when: '3 months out', summary: 'What PUMA covers, and when', description: 'How French public health cover works for new residents, and when you become eligible.', verified: '2026-09', sourceCount: 3, sections: [{ heading: 'Before and after residency', paragraphs: ['Your visa requires private insurance for the full stay. PUMA eligibility comes later, once you are resident — the two do not overlap the way people expect.'] }] },
  { slug: 'carte-vitale-application', title: 'Applying for a Carte Vitale', when: 'On arrival', summary: 'After you land, not before', description: 'How to apply for a French Carte Vitale once resident, and how long it takes.', verified: '2026-09', sourceCount: 2, sections: [{ heading: 'Timing', paragraphs: ['You cannot start this from the US. It follows residency, and the wait is measured in months rather than weeks.'] }] },
  { slug: 'tax-residency-rules', title: 'French Tax Residency Rules', when: 'Year one', summary: 'The 183-day myth', description: 'How French tax residency is actually determined, and why the 183-day rule is not the whole test.', verified: '2026-09', sourceCount: 3, sections: [{ heading: 'More than day counting', paragraphs: ['France applies several tests, and days spent is only one. Your main home, your centre of economic interests and your professional activity all count.'] }] },
  { slug: 'french-bank-account', title: 'Opening a French Bank Account', when: 'On arrival', summary: 'From abroad, and after landing', description: 'Opening a French bank account as a US citizen, including what FATCA changes.', verified: '2026-09', sourceCount: 2, sections: [{ heading: 'What FATCA changes', paragraphs: ['US citizens face extra reporting, and some French banks decline American clients outright rather than carry the compliance burden.'] }] },
];

export function guideBySlug(slug: string): GuideDoc | undefined {
  return guides.find((g) => g.slug === slug);
}

/** Every route this site prerenders. */
export const routes: string[] = ['/', '/guides/', ...guides.map((g) => `/guides/${g.slug}/`)];
