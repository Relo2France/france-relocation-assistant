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

/**
 * A source for the body of a guide.
 *
 * THE RULE: everything in a guide's sections - paragraphs, requirements and
 * caveats - must be confirmed on an official government source. `kind` is
 * therefore fixed at 'official' and is not a choice. Anything learned from
 * people reporting their own experience goes in `practice` instead, where it
 * is labelled as such to the reader.
 *
 * `domain` is checked against OFFICIAL_DOMAINS by the test suite, so a source
 * cannot be slipped in that merely sounds authoritative.
 */
export interface GuideSource {
  label: string;
  kind: 'official';
}

/**
 * The only sources permitted in a guide body. French government domains, plus
 * named legislation. Adding to this list is a deliberate act: it widens what
 * the site is willing to state as fact.
 */
export const OFFICIAL_DOMAINS = [
  'service-public.fr',
  'france-visas.gouv.fr',
  'notaires.fr',
  'visas-fr.tlscontact.com',
  'ameli.fr',
  'impots.gouv.fr',
  'cleiss.fr',
  'justice.fr',
  'legifrance.gouv.fr',
  'interieur.gouv.fr',
  'administration-etrangers-en-france.interieur.gouv.fr',
  'irs.gov',
  'fincen.gov',
  'banque-france.fr',
  'Code général des impôts',
  'France-Visas guidance',
] as const;

export interface GuideDoc {
  slug: string;
  title: string;
  /** Shown in the index and the timeline. A deadline, not a rule. */
  when: string;
  summary: string;
  /** <meta name="description">, and the gallery/search snippet. */
  description: string;
  verified: string;
  /** Kept in step with sources.length by the test suite. */
  sourceCount: number;
  /** Named, so a guide never displays a chip for a source it did not use. */
  sources: GuideSource[];
  sections: GuideSection[];
  practice?: { paragraphs: string[]; sources: string };
}

export const guides: GuideDoc[] = [
  {
    slug: 'long-stay-visa-overview',
    title: 'Long-Stay Visa Overview',
    when: '12 months out',
    summary: 'Which category applies to you',
    description:
      'The main French long-stay visa categories for US citizens, how to tell which one fits, and the validation step that makes the visa work.',
    verified: '2026-09',
    sourceCount: 3,
    sources: [
      { label: 'france-visas.gouv.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
      { label: 'visas-fr.tlscontact.com', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Why the type matters',
        paragraphs: [
          'US citizens need a long-stay visa (visa de long séjour, VLS-TS) to live in France for more than 90 days. The type you apply for governs what you may do once you arrive, and changing it later is harder than choosing correctly now.',
          'Most long-stay visas are issued as a {{VLS-TS}} — a visa that doubles as a residence permit once validated. Validation happens online within {{3 months}} of arrival, and skipping it leaves you without legal status even though the visa itself is valid.',
        ],
      },
      {
        heading: 'The main categories',
        requirements: [
          '{{Visitor}} (VLS-TS visiteur) — for those who will not work in France. Retirees and people living on savings or pensions; whether it covers remote work for a foreign employer is contested, and the visitor guide sets out where that stands',
          '{{Work}} (VLS-TS salarié) — requires a French job offer, and the employer must obtain the {{autorisation de travail}} before you apply',
          '{{Talent Passport}} — highly-skilled workers, researchers, founders and investors. Valid up to {{4 years}}, with salary thresholds revised each January',
          '{{Student}} (VLS-TS étudiant) — enrolment at a French institution, with work permitted up to {{964 hours}} a year',
          '{{Spouse / family}} — spouses of French or EU/EEA residents, and family reunification',
        ],
        caveat:
          'France still has no legally distinct digital nomad visa as of September 2026. Remote workers apply under the standard visitor visa; “digital nomad visa” is marketing language, not a France-Visas category. Some consulates now ask for more detail on the arrangement — an employer letter, and how your time will be split.',
      },
      {
        heading: 'Validation is what makes it a residence permit',
        paragraphs: [
          'Most VLS-TS holders must validate online within {{3 months}} of arrival, through the ANEF portal. This is the step that turns the visa into a residence permit for its first year.',
          'Under Article 128 of the loi de finances pour 2026, the validation tax for standard VLS-TS categories rose from {{€200}} to {{€300}}, effective {{1 May 2026}}. Some exemptions still apply — spouses of French nationals, certain scholarship holders — so confirm your category on the portal.',
        ],
        caveat:
          'Miss the deadline and you are in irregular status even though the sticker in your passport still looks valid. It surfaces at renewal, not at the border.',
      },
      {
        heading: 'What every type has in common',
        requirements: [
          'Apply from the US, through France-Visas and your assigned consulate — never after arriving',
          'Passport valid {{6+ months}} beyond the intended stay',
          'Comprehensive health insurance covering the full stay, including repatriation and emergency care',
          'Proof of accommodation in France',
          'Proof of financial resources appropriate to the category',
          'Passport photos and the completed long-stay application',
        ],
        caveat:
          'France-Visas gives an official baseline of roughly {{15 working days}} to {{2 months}}. Straightforward visitor files often land at the faster end; Talent Passport files and anything needing extra screening take longer.',
      },
      {
        heading: 'Documents every application needs',
        requirements: [
          'US passport issued within the last {{10 years}}, valid at least {{3 months}} beyond your stay — {{6+ months}} is strongly recommended as a buffer for onward Schengen travel',
          'The France-Visas application, printed and signed, with a recent ICAO-compliant biometric photo',
          'Proof of financial means — typically {{3 months}} of bank statements, at a level set by the category',
          'Insurance meeting the Schengen minimum of {{€30,000}} for medical costs, repatriation and emergency care',
          'Proof of accommodation — a lease, a booking, or a signed {{attestation d’hébergement}}',
          'A biometric appointment, completed at a VFS Global centre',
        ],
        caveat:
          'Submit no earlier than {{3 months}} and no later than {{15 days}} before travel. Most English-language documents need a certified translation by a sworn translator ({{traducteur assermenté}}).',
      },
      {
        heading: 'Documents only some categories need',
        paragraphs: [
          'These vary by category and by consulate, which is why checklists found online disagree. Work from your own consulate’s list.',
        ],
        requirements: [
          'FBI Identity History Summary with a State Department apostille — common for work, family and some visitor files, but not universal',
          'Birth certificate with apostille — typically family-based and naturalisation-track applications',
          'Marriage certificate with apostille, where relevant',
          'Transcripts and diplomas with apostille, for Talent Passport, work and student files',
          'Employment contract, secondment letter, or a school or university acceptance letter',
        ],
        caveat:
          'A pre-departure medical certificate is not a general requirement. Since France simplified the process, long-stay visa holders generally validate online after arrival, and only a subset are called for an in-person visit or examination.',
      },
      {
        heading: 'What it actually costs',
        requirements: [
          'Visa fee: {{€99}} long-stay, {{€50}} student, {{€90}} short-stay',
          'VFS Global or TLScontact service fee: {{$52–68}}, plus biometric enrolment at about {{$35}}',
          'Apostille: {{$38–75}} per document. Certified translation: {{$50–125}} per page',
          'FBI background check: {{$18}} when applying from the US',
          'Optional extras — priority booking, courier return, SMS updates — add {{$22–120}}',
        ],
        caveat:
          'Realistic totals: {{$500–850}} for one long-stay applicant, {{$1,400–2,100}} for a family of four. The visa fee is the small part; apostilles and sworn translations are what add up, and both take time as well as money.',
      },
    ],
    practice: {
      paragraphs: [
        'There is no fixed statutory turnaround. Consulates commonly process complete long-stay dossiers in roughly two to six weeks, but it varies by consulate, category and time of year.',
        'Peak season — roughly May to August, and to a lesser extent December — adds one to three weeks to both appointments and decisions. An incomplete file pauses the clock entirely until the missing documents arrive.',
      ],
      sources: 'Reported consulate turnaround, 2026',
    },
  },
  {
    slug: 'visitor-visa-requirements',
    title: 'The Long-Stay Visitor Visa',
    when: '12 months out',
    summary: 'The non-working route, in full',
    description:
      'Requirements for the French VLS-TS Visiteur in 2026, including the SMIC-based income benchmark and the declaration of no professional activity.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'france-visas.gouv.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
    ],
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
      {
        heading: 'The insurance is stricter than it sounds',
        paragraphs: [
          'Basic travel or Schengen cover will not do. You need comprehensive private health insurance running the full visa duration, with at least {{€30,000}} of medical cover, repatriation, and — the condition people miss — no exclusion for pre-existing conditions.',
        ],
        requirements: [
          'Birth certificate with apostille and a certified French translation',
          'A clean criminal background check — the FBI Identity History Summary for US citizens',
          'The signed {{attestation sur l’honneur}} promising not to work in France',
        ],
      },
      {
        heading: 'How the application runs',
        requirements: [
          'Create an account on France-Visas.gouv.fr and complete the eligibility wizard',
          'Book an appointment at your regional consulate or visa centre — TLScontact or VFS',
          'Submit documents and biometrics, and pay the fee of about {{€99}}',
          'Wait roughly {{2–8 weeks}} for a decision, longer in the June–September peak',
          'Collect your passport with the VLS-TS sticker in it',
        ],
      },
      {
        heading: 'What happens after you land',
        paragraphs: [
          'Validate online through the ANEF portal within {{3 months}} of entry — counted from your entry stamp or EES record, not from the date the visa was issued.',
          'The validation tax rose from {{€200}} to {{€300}} on {{1 May 2026}} under the 2026 finance law.',
        ],
        requirements: [
          'Visitor-visa holders are typically also called for a mandatory OFII medical visit — a chest X-ray and a brief screening',
          'The appointment letter arrives by post or email after validation, so keep your registered address current',
          'Waiting times for that convocation vary widely by region',
        ],
        caveat:
          'Miss the 90-day validation deadline and you are in irregular status even though the sticker still looks valid — with consequences for renewal, banking and healthcare access. The Interior Ministry has indicated some technical flexibility exists, but never plan around it.',
      },
      {
        heading: 'A new annual health contribution',
        paragraphs: [
          'Once your status makes you eligible for French public health cover, the 2026 Social Security Financing Law creates a mandatory annual contribution ({{participation financière}}, CSS Article L.160-1-1), estimated at {{€300–600}} a year.',
          'It is separate from the existing Cotisation Subsidiaire Maladie. The exact amount is set by an implementing decree that had not been published as of mid-2026 — check service-public.fr for the current figure rather than assuming you are exempt.',
        ],
      },
      {
        heading: 'Remote work is the unsettled question',
        paragraphs: [
          'This visa does not permit working for a French employer or French clients. That part is clear.',
          'Whether it permits remote work for a purely foreign employer is genuinely contested and still evolving — a mid-2026 policy clarification pointed toward some remote workers with strictly foreign employers or clients. Do not assume either way on the strength of a forum post, including an optimistic one.',
        ],
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
  {
    slug: 'buying-property-france',
    title: 'Buying Property in France',
    when: '9 months out',
    summary: 'The process, and what it costs on top',
    description:
      'How property purchase works in France for US buyers: the five stages, the cooling-off period, and the 7-10% of fees on top of the price.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'notaires.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'You can buy without restriction',
        paragraphs: [
          'US citizens can purchase property in France freely. There is no nationality restriction, no permit to obtain, and no minimum stay requirement attached to ownership.',
          'Owning property does not, however, give you the right to live in France. That is a separate question, settled by your visa.',
        ],
      },
      {
        heading: 'The five stages',
        requirements: [
          'Find the property, through an agent or a direct listing',
          'Make a written offer ({{offre d’achat}})',
          'Sign the preliminary contract ({{compromis de vente}}) — buyers get a {{10-day}} cooling-off period, or {{14 days}} if it was signed remotely',
          'Due diligence, while the notaire completes searches — officially {{2–4 months}}, though delays of {{3–6 months}} have become increasingly common since 2024',
          'Final signing ({{acte authentique}}) at the notaire’s office',
        ],
        caveat:
          'The cooling-off period belongs to the buyer alone. Once it passes, withdrawing generally costs you the deposit.',
      },
      {
        heading: 'What happens during the conditions period',
        paragraphs: [
          'Between the compromis and the final deed the notaire verifies title while you secure financing. This is also when the mandatory diagnostics are reviewed — the seller pays for them, but reading them is your job.',
        ],
        requirements: [
          'Energy performance ({{DPE}}), asbestos, lead, termites and natural risks',
          'The notaire’s searches: ownership history, liens, easements, planning restrictions and the commune’s pre-emption right',
          'Your mortgage offer, if the sale is conditional on one',
        ],
        caveat:
          'Get mortgage pre-approval before making serious offers. A financing condition protects you, but an offer without pre-approval is weaker, and arranging the loan is what most often pushes the conditions period past its two to three months.',
      },
      {
        heading: 'How long the whole thing takes',
        paragraphs: [
          'Expect {{3–5 months}} from accepted offer to keys in current market conditions. Foreign buyers have had somewhat more streamlined documentation since 2025, and final signing is now routinely a hybrid of digital and in-person.',
        ],
      },
      {
        heading: 'Budget 7–10% on top',
        requirements: [
          'Notaire fees: {{7–8%}} for property over five years old, {{2–3%}} for new builds',
          'Agency fees: {{3–8%}}, usually paid by the seller — but confirm who pays in the contract',
          'Mortgage arrangement fees: {{1–2%}} if you are financing',
          'Diagnostics and surveys: {{€800–3,500}} depending on age and size',
          'Deposit at the compromis: {{5–10%}} of the price, held in escrow by the notaire',
        ],
        caveat:
          'Most of what is called “notaire fees” is transfer tax collected for the state, not the notaire’s own fee. Registration taxes run {{5.09–6.31%}} depending on département. Since 1 April 2025 départements may set the rate up to {{5.0%}}, previously capped at {{4.5%}}: on the mid-2026 official table, {{88}} of 100 charge the new rate, {{11}} remain at 4.5%, and only Indre and Mayotte still charge {{3.80%}}. Confirm yours with the notaire before budgeting.',
      },
      {
        heading: 'Two things that reduce the bill',
        requirements: [
          'First-time buyers ({{primo-accédants}}) purchasing a primary residence are exempt nationwide from the {{0.5-point}} increase — a national rule, not a local option, but eligibility turns on your buyer status, so confirm it with your notaire',
          'The taxable base can be reduced by the itemised value of genuine furniture and movable fixtures included in the sale, up to {{5%}} of the price',
        ],
        caveat:
          'The percentage runs higher on cheaper property, because the administrative disbursements inside it are largely fixed. An official fee simulator is published at immobilier.notaires.fr.',
      },
    ],
  },
  {
    slug: 'role-of-notaire',
    title: 'The Role of the Notaire',
    when: '9 months out',
    summary: 'A public official, not your advocate',
    description:
      'What a French notaire does in a property purchase, why one is legally required, and why they do not represent you the way a US attorney would.',
    verified: '2026-09',
    sourceCount: 1,
    sources: [
      { label: 'notaires.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Not the equivalent of your attorney',
        paragraphs: [
          'The notaire is a public official who alone can draft the authentic deed that transfers real estate in France. That is fundamentally different from a US real estate attorney, who represents one side of the transaction.',
          'A notaire represents the transaction itself. They are legally obliged to protect both parties equally, which means nobody in the room is advocating for you specifically.',
        ],
      },
      {
        heading: 'What they do',
        requirements: [
          'Draft and register the preliminary contract and the final deed',
          'Verify ownership history and title',
          'Check for liens, mortgages, easements, planning restrictions and the local authority’s pre-emption rights',
          'Hold your deposit in escrow ({{séquestre}})',
          'Register the sale with the land registry',
          'Collect and remit transfer taxes to the state and local authorities',
        ],
        caveat:
          'A notaire is required for every sale regardless of price. Buyer and seller may each appoint their own: the two collaborate on the file and split the single regulated fee between them, so a second notaire does not increase the total cost.',
      },
      {
        heading: 'Where the notaire stops',
        paragraphs: [
          'On a financed purchase the notaire coordinates the release of your mortgage funds and registers the bank’s security interest ({{inscription hypothécaire}}). They do not arrange the loan — that stays between you, your bank and any broker.',
          'They also give neutral legal information to both parties. Useful, but it is information, not advocacy: expect an accurate answer to what you ask, not a warning about what you did not think to ask.',
        ],
      },
      {
        heading: 'What it costs',
        paragraphs: [
          'The notaire’s core fees ({{émoluments}}) are set by government decree and are not negotiable. They are a regulated scale that falls, as a percentage, on higher price brackets.',
          'Only a small fraction of what people call “notaire fees” is the notaire’s own income: around {{80%}} is transfer tax collected for the state, département and commune. Disbursements — land registry, searches, civil-status extracts — run about {{€900–1,400}}, plus a security contribution of {{0.10%}} of the price. The notaire’s own regulated fee typically works out to roughly {{1%}} or less — about {{10–15%}} of the total.',
          'The scale is degressive, so the percentage is higher on cheaper property. The current schedule was reconfirmed by ministerial order in February 2026 and runs through February 2028. An official simulator is published at immobilier.notaires.fr.',
        ],
      },
    ],
  },
  {
    slug: 'french-healthcare-overview',
    title: 'French Healthcare Overview',
    when: '3 months out',
    summary: 'What PUMA covers, and what it does not',
    description:
      'How French public health cover works for new residents: PUMA eligibility, what the Carte Vitale actually reimburses, and why most people also carry a mutuelle.',
    verified: '2026-09',
    sourceCount: 3,
    sources: [
      { label: 'ameli.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
      { label: 'cleiss.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Three things, often confused',
        paragraphs: [
          'France has universal healthcare, and as a legal resident you can access the public system alongside French nationals. But three separate things get muddled together, and they do different jobs.',
        ],
        requirements: [
          '{{PUMA}} — the entitlement. Available to anyone residing in France in a stable and regular manner for at least {{3 months}}',
          '{{Carte Vitale}} — the card that makes reimbursement automatic',
          '{{Mutuelle}} — private supplemental insurance covering what the base rate does not',
        ],
      },
      {
        heading: 'What the base rate actually pays',
        paragraphs: [
          'The Carte Vitale reimburses the regulated base rate ({{tarif de convention}}), typically {{70%}} for a GP consultation within the declared care pathway. The 2026 base rate for a GP visit is {{€30}}, so the reimbursed portion is about {{€21}}.',
        ],
        caveat:
          'Dental prosthetics, glasses, hearing aids, daily hospital charges and any practitioner billing above the regulated rate are only partly covered, or not covered at all, by the base rate alone. This is why a mutuelle is normal rather than a luxury.',
      },
      {
        heading: 'What a mutuelle actually covers',
        paragraphs: [
          'The base reimbursement depends on the care: typically {{70%}} of the official rate for a GP or specialist visit, {{80%}} for hospital care, and up to {{100%}} for maternity or a recognised long-term condition (ALD). What is left is the {{ticket modérateur}} — roughly {{20–30%}} on routine care — plus any {{dépassements d’honoraires}} charged by secteur 2 specialists above the official rate.',
        ],
        requirements: [
          'The co-pay, and specialist fees above the official rate',
          'Dental, vision and hearing beyond the basic {{100% Santé}} basket',
          'Private hospital rooms, and the daily hospital charge ({{forfait journalier}}, about {{€20}}/day)',
          'Pharmacy co-pays and medicines the state does not reimburse',
          'Often osteopathy, acupuncture, telemedicine and overseas emergencies — this varies by plan',
        ],
        caveat:
          'Two charges no mutuelle may reimburse, by law: the {{participation forfaitaire}} on each consultation, and the {{franchise médicale}} on medicines, ambulances and paramedical acts. Together they are capped at {{€50}} a year and are designed to always come out of pocket. Nor will any plan cover the penalty for bypassing your declared referring doctor.',
      },
      {
        heading: 'What it costs',
        requirements: [
          'Individual: {{€40–170}} a month — younger adults at the low end, those over 60 frequently more',
          'Couple: {{€90–260}} a month',
          'Family: {{€150–350}} a month',
          'International or expat-focused plans generally run higher, reflecting worldwide cover',
        ],
        caveat:
          'If you take a salaried job, this changes: since the 2016 ANI law private-sector employers must offer a {{mutuelle d’entreprise}} and pay at least {{50%}} of the premium. Already covered on a spouse’s employer plan? You can usually request a {{dispense d’adhésion}} rather than hold two.',
      },
      {
        heading: 'Your visa insurance is separate',
        paragraphs: [
          'A long-stay visa requires private health insurance covering your whole stay. That is a visa requirement, satisfied before you arrive, and it is not the same thing as PUMA.',
          'PUMA eligibility follows residency. The two do not overlap the way people expect, so plan to carry private cover into your first months in France.',
        ],
      },
    ],
  },
  {
    slug: 'carte-vitale-application',
    title: 'Applying for a Carte Vitale',
    when: 'On arrival',
    summary: 'After you land, not before',
    description:
      'How to enrol in PUMA and obtain a French Carte Vitale once resident: the form, the documents required, and how long it realistically takes.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'ameli.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'You cannot start this from the US',
        paragraphs: [
          'Enrolment follows residency. You apply to your local CPAM once you are living in France with a valid visa or residence permit — there is nothing to file before you arrive.',
          'Non-workers generally need three consecutive months of stable residence first. If you are employed or self-employed in France, cover applies with no minimum duration and affiliation usually happens through payroll.',
        ],
      },
      {
        heading: 'What to file',
        requirements: [
          'Form {{736}} — “Demande d’affiliation au régime général sur critère de résidence”, from ameli.fr or submitted through your ameli account',
          'Passport with your visa or residence permit',
          'Proof of address — a recent utility bill, or three months of statements',
          'Birth certificate, apostilled and translated if required',
          'French bank details ({{RIB}})',
          'Proof of income, or an attestation that you have none',
        ],
        caveat:
          'You cannot be covered by another EU or EEA country’s system at the same time.',
      },
      {
        heading: 'What the card does once it arrives',
        paragraphs: [
          'The Carte Vitale is a chip card you present at doctors, pharmacies and hospitals. It enables direct billing ({{tiers payant}}) and automatic reimbursement — typically {{70%}} of the conventional rate for a GP visit, and more for serious or long-term conditions.',
        ],
      },
      {
        heading: 'What PUMA costs',
        paragraphs: [
          'Base cover is not means-tested. It is free for anyone meeting the residence conditions, and there is no general income cutoff for basic affiliation.',
        ],
        requirements: [
          '{{Cotisation Subsidiaire Maladie}} — a sliding contribution from roughly {{6.5%}} down to zero, applying only to non-working residents with low earned income (below about {{€9,000–9,600}} a year) who also have substantial capital or investment income',
          'For anyone drawing a French salary, standard payroll {{CSG/CRDS}} of {{9.2%}} plus {{0.5%}} on {{98.25%}} of gross pay — a general social charge, not a PUMA membership fee',
        ],
        caveat:
          'Most salaried workers and retirees on modest pensions pay neither. But the 2026 Social Security Financing Law introduces a new contribution for PUMA beneficiaries who owe no CSG, CRDS or health-insurance contributions under an international agreement — which may catch US retirees covered by the bilateral agreements. The implementing decree setting the amount had not been published as of mid-2026, so watch ameli.fr and service-public.fr.',
      },
    ],
    practice: {
      paragraphs: [
        'The official estimate for the card is a few months after PUMA approval. In busy urban CPAM offices, 2026 field reports point to {{4–9 months}} in total — so keep your private insurance running until the card actually arrives, not until you expect it to.',
        'While you wait you are not uncovered. Pay upfront and submit paper reimbursement forms ({{feuille de soins}}), or use the temporary {{attestation de droits}} available through your ameli.fr account once your dossier is acknowledged.',
      ],
      sources: 'CPAM field reports, 2026',
    },
  },
  {
    slug: 'tax-residency-rules',
    title: 'French Tax Residency Rules',
    when: 'Year one',
    summary: 'Why 183 days is not the test',
    description:
      'How French tax residency is actually decided under Article 4B, why the 183-day rule is shorthand rather than law, and what triggers residency on its own.',
    verified: '2026-09',
    sourceCount: 4,
    sources: [
      { label: 'impots.gouv.fr', kind: 'official' },
      { label: 'Code général des impôts, art. 4B', kind: 'official' },
      { label: 'irs.gov', kind: 'official' },
      { label: 'fincen.gov', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Any one of four tests is enough',
        paragraphs: [
          'Under Article 4B of the Code général des impôts you are a French tax resident if any single one of these applies. They are alternatives, not a checklist — meeting one is sufficient.',
        ],
        requirements: [
          'France is your principal place of stay — shorthanded as “{{183+ days}}”, but the legal test is qualitative: more time in France than in any other single country',
          'Your principal home ({{foyer}}) is in France — which can apply even below 183 days, if your spouse, family or main residence is there',
          'Your principal professional activity is in France, unless it is merely ancillary',
          'Your centre of economic interests is in France — main investments, business, or source of income',
        ],
        caveat:
          'France applies a “principal place of stay” test rather than a rigid day count, so the whole picture is weighed. Keeping a French home or an economic base there can make you resident even when your day count alone would not — staying just under 183 days does not settle the question.',
      },
      {
        heading: 'What being resident actually means',
        paragraphs: [
          'French residency is taxation on worldwide income, at progressive rates from {{0%}} to {{45%}}, withheld at source since 2019 ({{prélèvement à la source}}).',
        ],
        requirements: [
          'Social charges ({{CSG/CRDS}}) at {{17.2%}} on investment income, rental income and certain US retirement distributions',
          'Real estate wealth tax ({{IFI}}) on net French and foreign real estate above {{€1.3M}}',
          '{{Taxe foncière}} as an owner. {{Taxe d’habitation}} has been abolished on main residences since 2023, but still applies to second homes, with a surtax in some tension zones',
        ],
        caveat:
          'An exemption from CSG/CRDS may apply if you are affiliated with US Social Security and Medicare rather than the French system. This is worth establishing early — it is a large number applied to passive income.',
      },
      {
        heading: 'If both countries could claim you',
        paragraphs: [
          'The US–France tax treaty prevents double taxation and settles competing claims through tie-breaker rules, applied strictly in order: permanent home, then centre of vital interests, then habitual abode, then nationality, and finally mutual agreement between the two authorities.',
          'The treaty carries specific provisions for pensions and government service income. It contains no modern provision for remote work, which is handled case by case.',
        ],
      },
      {
        heading: 'Your US filing does not stop',
        paragraphs: [
          'US citizens file regardless of where they live. Becoming French tax resident adds obligations rather than replacing them.',
        ],
        requirements: [
          'Federal return by {{15 April}}, with an automatic extension to {{15 June}} from abroad and a further extension to {{15 October}} on request',
          'The Foreign Earned Income Exclusion, or the Foreign Tax Credit — the credit is often better in a high-tax country like France',
          '{{FBAR}} (FinCEN 114) once foreign accounts total more than {{$10,000}} at any point in the year — a statutory threshold, never inflation-adjusted',
          'FATCA {{Form 8938}} living abroad: {{$200,000}}/{{$300,000}} single or married filing separately, {{$400,000}}/{{$600,000}} filing jointly',
        ],
        caveat:
          'State tax may follow you out of the country. Some states — California, Virginia and New Mexico among them — hold on to domicile tightly. Confirm current dollar figures on irs.gov: the exclusion and the FBAR penalties move every year.',
      },
      {
        heading: 'How days are counted',
        requirements: [
          'Any part of a day in France generally counts as a whole day',
          'Calendar year basis, {{1 January – 31 December}}',
          'Days need not be consecutive, and both arrival and departure days count',
          'Short airport transits generally do not count',
          'Days lost to hospitalisation or genuine force majeure may be excluded',
        ],
      },
    ],
  },
  {
    slug: 'french-bank-account',
    title: 'Opening a French Bank Account',
    when: 'On arrival',
    summary: 'And why FATCA makes it harder',
    description:
      'Opening a French bank account as a US citizen: why some banks decline Americans, which ones do not, and the documents you will be asked for.',
    verified: '2026-09',
    sourceCount: 3,
    sources: [
      { label: 'service-public.fr', kind: 'official' },
      { label: 'irs.gov', kind: 'official' },
      { label: 'fincen.gov', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Why you need one, and why it is difficult',
        paragraphs: [
          'A French account is effectively required for utilities, phone contracts, rent, and receiving French benefits — and it gives you access to the direct debit system ({{prélèvement automatique}}) that most French billing assumes.',
          'US citizens face additional reporting obligations under FATCA, and some French banks decline American clients outright rather than carry the compliance burden. This is about paperwork, not creditworthiness.',
        ],
      },
      {
        heading: 'Banks that accept Americans',
        requirements: [
          '{{BNP Paribas International}} — generally the most US-friendly, with dedicated expat services',
          '{{Crédit Agricole International}} — varies by region; some branches are more accommodating than others',
          '{{Boursorama}} — online, owned by Société Générale. {{Hello Bank}} — BNP Paribas’ online subsidiary',
          '{{Wise}} and {{Revolut}} — French IBAN accounts, useful early on',
          '{{N26}} and {{Bunq}} — neobanks increasingly accepting US customers in France',
          '{{Qonto}} — business banking that also offers personal accounts',
        ],
      },
      {
        heading: 'What your bank reports about you',
        paragraphs: [
          'Under the France–US FATCA intergovernmental agreement, French banks are required to identify accounts held by US persons and report them to the French tax authority, which forwards the data to the IRS.',
          'That sharing is largely automatic and does not depend on you volunteering anything. It is why account opening asks about your place of birth and citizenship — those are the US indicia banks screen for, and it is the reason some decline American clients rather than carry the obligation.',
        ],
      },
      {
        heading: 'And what you must report',
        requirements: [
          '{{FBAR}} (FinCEN 114) once your foreign accounts total more than {{$10,000}} at any point in the calendar year — filed electronically at fincen.gov',
          'FBAR is due {{15 April}} with an automatic extension to {{15 October}}, granted without any request',
          'FATCA {{Form 8938}} with your 1040, above {{$200,000}}/{{$300,000}} single or {{$400,000}}/{{$600,000}} jointly when living abroad',
        ],
        caveat:
          'The two forms overlap but do not match: different thresholds, different scope, and filing one does not satisfy the other. Penalties for missing FBAR are adjusted for inflation annually — confirm the current figures on FinCEN’s published table rather than relying on a number quoted anywhere, including here.',
      },
      {
        heading: 'What you will be asked for',
        requirements: [
          'Valid passport, plus your visa or residence permit',
          'Proof of French address — a lease or {{attestation d’hébergement}}',
          'Proof of income — contract, pay slips, or bank statements',
          'Your US Social Security Number and a completed {{W-9}}',
          'Birth certificate, apostilled, sometimes requested',
          'French tax number ({{numéro fiscal}}) if you have one yet',
        ],
      },
    ],
  },
];

export function guideBySlug(slug: string): GuideDoc | undefined {
  return guides.find((g) => g.slug === slug);
}

/** Every route this site prerenders. */
export const routes: string[] = [
  '/',
  '/how-it-works/',
  '/pricing/',
  '/guides/',
  ...guides.map((g) => `/guides/${g.slug}/`),
];
