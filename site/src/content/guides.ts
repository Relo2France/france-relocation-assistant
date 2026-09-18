/**
 * Guide content.
 *
 * Slugs deliberately match the URLs already indexed on relo2france.com, so the
 * cutover needs no redirects for these pages at all. Changing one is a
 * deliberate act with an SEO cost - see guides.test.ts, which pins them.
 */
export interface GuideSection {
  heading: string;
  /** The walkthrough: what this is, how to approach it, what to watch. Every section has one. */
  paragraphs?: string[];
  requirements?: string[];
  /** True when the requirements are steps in order, rendered numbered. */
  ordered?: boolean;
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
        paragraphs: [
          'Every long-stay applicant lands in one of a handful of categories, and the category decides what you may do once you arrive: work, study, or neither. Read the five below against your own plan rather than your hopes. If you will be earning in France in any way, the visitor route is not yours; if a French employer or institution is involved, the paperwork starts on their side before it starts on yours. The finder at the top of this page walks the same choice one question at a time.',
        ],
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
        paragraphs: [
          'Whichever category you land in, the consulate checks the same foundations first, and a file that fails one of them is refused before the category-specific documents are even read. Get these six settled before you think about anything else. The one that surprises people most is the first: the application is made from the United States, through France-Visas and the consulate that covers your state, and cannot be started after you arrive.',
        ],
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
        paragraphs: [
          'This is the physical file every applicant hands over at the biometric appointment. Two habits keep it out of trouble: start on the slow items first, because an FBI check or an apostille takes weeks while a photo takes an afternoon, and keep everything inside the submission window, since a statement or a certificate that has aged past three months is asked for again. The list is the same for every category; the category then adds its own items on top, covered in the next section.',
        ],
        requirements: [
          'US passport issued within the last {{10 years}}, valid at least {{3 months}} beyond your stay — {{6+ months}} is strongly recommended as a buffer for onward Schengen travel',
          'The France-Visas application, printed and signed, with a recent ICAO-compliant biometric photo',
          'Proof of financial means — typically {{3 months}} of bank statements, at a level set by the category',
          'Insurance meeting the Schengen minimum of {{€30,000}} for medical costs, repatriation and emergency care',
          'Proof of accommodation — a lease, a booking, or a signed {{attestation d’hébergement}}',
          'A biometric appointment, completed at a VFS Global centre',
        ],
        caveat:
          'Submit no earlier than {{3 months}} and no later than {{15 days}} before travel. US consulates take English-language documents as they are; sworn translations ({{traducteur assermenté}}) come after arrival, for the prefecture and CPAM.',
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
        paragraphs: [
          'The visa fee itself is the smallest line. What adds up is everything the file has to carry: an apostille for each certificate, the FBI check, the service and biometric fees at the visa centre, and, after arrival, a sworn translation of each certificate for the prefecture. Budget per document rather than per application, and add the optional extras only if you need them. The note below gives realistic totals for one applicant and for a family.',
        ],
        requirements: [
          'Visa fee: {{€99}} long-stay, {{€50}} student, {{€90}} short-stay',
          'VFS Global or TLScontact service fee: {{$52–68}}, plus biometric enrolment at about {{$35}}',
          'Apostille: {{$38–75}} per document. Sworn translation, after arrival: {{$50–125}} per page',
          'FBI background check: {{$18}} when applying from the US',
          'Optional extras — priority booking, courier return, SMS updates — add {{$22–120}}',
        ],
        caveat:
          'Realistic totals: {{$500–850}} for one long-stay applicant, {{$1,400–2,100}} for a family of four. The visa fee is the small part; the FBI check, the apostilles and, after arrival, the sworn translations are what add up, and all take time as well as money.',
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
        paragraphs: [
          'The visitor route asks for less than the work routes, but it asks for it precisely: the consulate is checking that you can support yourself without working and that you are insured if something goes wrong. Gather the five items below and read the note under them before deciding how much to show, because the resources figure is a benchmark the consulate reasons from, not a line you either clear or fail.',
        ],
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
          'Birth certificate with apostille (the translation comes after arrival, for the prefecture and CPAM)',
          'A clean criminal background check — the FBI Identity History Summary for US citizens',
          'The signed {{attestation sur l’honneur}} promising not to work in France',
        ],
      },
      {
        heading: 'How the application runs',
        paragraphs: [
          'The application runs in five steps, and the order matters: the online account comes first because the appointment cannot be booked without it, and the appointment is where the file, the fingerprints and the fee all happen at once. Book early in the summer months, when waits stretch.',
        ],
        ordered: true,
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
        paragraphs: [
          'A French purchase moves through five fixed stages, each with its own document, and the buyer’s protection sits in the middle: the cooling-off period after the preliminary contract. Everything before it is reversible; everything after it costs money to undo. Plan your financing and your questions for the notaire around that point.',
        ],
        ordered: true,
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
        paragraphs: [
          'The price on the listing is not the price you pay. Five further lines sit on top of it, and the biggest, the so-called notaire fees, is mostly tax collected for the state. Run the numbers on each before you make an offer, and confirm who pays the agency in the contract rather than assuming. The note below explains why the tax varies by département and how to check yours.',
        ],
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
        paragraphs: [
          'Two rules can bring that figure down, and both need to be raised before the contract is drafted rather than after. Ask your notaire about each; neither is applied automatically.',
        ],
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
        paragraphs: [
          'Think of the notaire as the state’s officer for the transaction: every French property sale passes through one, and the six duties below are theirs whether you appoint your own or share the seller’s. Knowing what they do tells you what they will ask you for, and what nobody else in the process is checking.',
        ],
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
        paragraphs: [
          'A mutuelle is priced by who is covered and by age, not by health, so the ranges below are wide but predictable. Get quotes for your household shape rather than per person, and read the note beneath if anyone in the household will be employed in France, because an employer plan changes the arithmetic.',
        ],
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
        paragraphs: [
          'Once you have lived in France for the qualifying period you file one form with your local CPAM, with the supporting documents below attached. Assemble them before you start: an incomplete file is returned rather than held, and the birth certificate in particular needs its apostille and translation ready.',
        ],
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
        paragraphs: [
          'If residency turns on where you spent the year, the count has to be done the way the French tax office does it, not by feel. Keep a simple log of entries and exits from the day you first arrive. The rules below are the ones that decide borderline cases.',
        ],
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
        paragraphs: [
          'FATCA makes a US citizen extra work for a French bank, so some branches say no and others say yes to the same person. Start with the banks below, which are known to take American clients, and expect an online account to be the quickest way to a French IBAN while a traditional branch account follows.',
        ],
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
        paragraphs: [
          'Opening the account creates a duty on the US side. Two separate filings cover foreign accounts, with different thresholds, and one does not stand in for the other. Note both due dates the year you open the account, because the penalties for missing the first are set by law and not small.',
        ],
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
        paragraphs: [
          'A branch will expect the documents below at the appointment. The two that catch Americans out are the W-9, which the bank needs for its own FATCA reporting, and the proof of address, which you may not have yet; a host’s attestation is accepted for that.',
        ],
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
  {
    slug: 'work-visa-salarie',
    title: 'The Salaried Work Visa',
    when: '9 months out',
    summary: 'Your employer moves first',
    description:
      'How the French VLS-TS Salarié works for Americans in 2026: the work authorisation your employer must obtain, the documents, the timeline, and the step after you land.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'france-visas.gouv.fr', kind: 'official' },
      { label: 'administration-etrangers-en-france.interieur.gouv.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Who it’s for',
        paragraphs: [
          'For employment in France by a French company. You must have a job offer before you apply — the visa cannot be used to come and look for work.',
          'The decisive step is not yours. Your employer must obtain a work authorisation ({{autorisation de travail}}) before your visa application can proceed, through the ANEF portal, which routes the request to the regional labour authority (DREETS).',
        ],
      },
      {
        heading: 'How the process runs',
        paragraphs: [
          'The salarié route runs through the employer before it runs through you: their work authorisation is the gate, and nothing at the consulate can start until it is granted. The seven steps below are in order. Where you can save time is at the ends: have your own documents ready while the authorisation is under review, and validate the visa promptly after you land.',
        ],
        ordered: true,
        requirements: [
          'Your French employer offers you a position',
          'The employer applies for work authorisation through the ANEF portal — this has replaced the older paper-based process',
          'The authorisation is reviewed — officially a {{2 month}} instruction period',
          'Once approved, you apply for the visa at the French consulate for your US region',
          'Visa processing typically takes {{3–6 weeks}}',
          'The visa is issued and you may work on arrival',
          'Within {{3 months}} of arrival, validate the visa online through ANEF',
        ],
        caveat:
          'End to end, {{3–6 months}} is a reasonable estimate including the employer’s authorisation step.',
      },
      {
        heading: 'What you’ll need',
        paragraphs: [
          'Your part of the file is the nine items below, most of which you can gather while the employer’s authorisation is pending. The apostilled background check and the diplomas are the slow ones; start them first. Translations are not needed for the consulate.',
        ],
        requirements: [
          'Passport valid {{6+ months}} beyond your stay',
          'Signed work contract or a detailed job offer letter',
          'The employer’s work authorisation approval from DREETS via ANEF',
          'Proof of qualifications — diplomas, in English, apostilled where the checklist asks',
          'Proof of accommodation in France',
          'Health insurance cover',
          'Proof of financial means',
          'A clean criminal background check, apostilled',
          'The visa application fee of {{€99}}',
        ],
        caveat:
          'The validation tax paid online after arrival is a separate cost from the €99 fee and easy to overlook. Its amount depends on contract length and salary level — confirm the current figure on france-visas.gouv.fr.',
      },
      {
        heading: 'Consider the Talent Passport instead',
        paragraphs: [
          'If you have advanced qualifications and a sufficiently high salary, or specialised in-demand skills, the Talent Passport is generally faster to process, carries a card valid for up to {{4 years}}, and automatically covers accompanying family members.',
          'Its salary thresholds differ by sub-category and are indexed to the French minimum wage or an average reference salary, so the exact figure changes. Check the current threshold for your category on france-visas.gouv.fr rather than relying on a single number.',
        ],
      },
    ],
    practice: {
      paragraphs: [
        'Real-world authorisation timelines of 6–10+ weeks are common and vary by region — Paris and Île-de-France are frequently reported as slower than elsewhere.',
        'Many applicants report the full process running past the estimate, especially where the employer is sponsoring someone for the first time and is unfamiliar with the paperwork.',
      ],
      sources: 'Applicant reports held in the knowledge base · 2026',
    },
  },
  {
    slug: 'talent-passport',
    title: 'The Talent Passport',
    when: '9 months out',
    summary: 'Multi-year, and your spouse can work',
    description:
      'The French Passeport Talent for Americans: the categories, what each requires, the four-year validity, family rights, and why the salary thresholds move every January.',
    verified: '2026-09',
    sourceCount: 1,
    sources: [{ label: 'france-visas.gouv.fr', kind: 'official' }],
    sections: [
      {
        heading: 'Who it’s for',
        paragraphs: [
          'A multi-year visa for highly-qualified workers, researchers, company founders and investors, valid for up to {{4 years}} and renewable.',
        ],
        requirements: [
          'Highly-qualified employee — a salary threshold plus a Master’s degree or equivalent experience',
          'Young qualified professional — under 30, Master’s degree, with its own salary threshold',
          'Company founder — an innovative business in France, via a BPI France- or incubator-recognised project',
          'Investor — a minimum {{€300,000}} investment in a French company, with job-creation or job-maintenance commitments',
          'Researcher — a hosting agreement with a French research or higher-education institution',
          'Artist or cultural professional — recognised talent in the arts, literature or culture',
        ],
      },
      {
        heading: 'The thresholds move, so check the date',
        paragraphs: [
          'Salary thresholds are indexed to the SMIC or to a reference average salary and are revised annually, typically each {{1 January}}. With the 2026 SMIC increase they have likely moved higher than in prior years.',
          'Our knowledge base currently carries two figures for the highly-qualified employee route: {{€66,600}} a year, and a range commonly cited near {{€43,000–€58,000}} depending on the sub-route and year. Treat both as approximate and confirm the current number for your category on france-visas.gouv.fr before you rely on it.',
        ],
        caveat:
          'A threshold quoted anywhere, including here, is only as current as the day it was checked. The consulate applies the figure in force when you apply.',
      },
      {
        heading: 'What it gives you',
        paragraphs: [
          'The Talent Passport costs more effort up front and repays it for years: a multi-year card, a working spouse, and fewer of the post-arrival obligations other categories carry. If you are near a threshold, the six benefits below are the reason to reach for it.',
        ],
        requirements: [
          '{{4-year}} validity, renewable, with a simplified renewal process',
          'Your spouse or partner receives automatic work authorisation',
          'Family members apply as “Passeport talent famille”',
          'A path to permanent residency after {{5 years}} of continuous residence',
          'You can change employer within the same category by notification',
          'Exempt from the post-arrival medical visit and the integration contract that other categories are summoned to',
        ],
      },
      {
        heading: 'Applying',
        paragraphs: [
          'The standard visa documents, plus proof that you qualify for your specific category — an employment contract, a business plan, or project documentation. The application fee is {{€225–€250}} depending on the consulate, and processing runs {{2–10 weeks}} depending on consulate and category.',
        ],
      },
    ],
  },
  {
    slug: 'spouse-and-family-visas',
    title: 'Joining a Spouse or Family in France',
    when: '6 months out',
    summary: 'Three routes, very different waits',
    description:
      'Family visas for Americans in 2026: the spouse-of-a-French-citizen route, family reunification, PACS partners, and what changes when both of you move together.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'service-public.fr', kind: 'official' },
      { label: 'france-visas.gouv.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Spouse of a French citizen',
        paragraphs: [
          'You apply for a {{vie privée et familiale}} visa at the French consulate, and you have the right to work immediately on arrival. There is no income requirement on the French spouse.',
        ],
        requirements: [
          'Your marriage certificate, apostilled — digital apostilles are now accepted',
          'Your spouse’s French identity document',
          'Proof that the relationship is genuine',
        ],
        caveat:
          'Processing runs {{2–10 weeks}} depending on the consulate. Family-based visas are often issued as “carte de séjour à solliciter” rather than a VLS-TS, which means a prefecture appointment within {{2 months}} of arrival instead of online validation — see the guide on the two visa types.',
      },
      {
        heading: 'Spouse of a non-EU resident: family reunification',
        paragraphs: [
          'Here the spouse already in France applies for {{regroupement familial}} on your behalf, and only after holding their first residence permit for {{18 months}}.',
        ],
        requirements: [
          'Sponsor income of {{1.3×}} the minimum wage — approximately {{€2,730}} a month as of 2026',
          'Housing of adequate size and condition, verified by the prefecture',
          'Processing of {{6–15 months}} after submission',
        ],
      },
      {
        heading: 'PACS partners',
        paragraphs: [
          'A civil union follows the same vie privée et familiale process as marriage, with more scrutiny. The PACS must have been registered for {{12+ months}} with continuous cohabitation, and you should expect to prove a shared life in depth — joint accounts, lease, travel together, utility bills, insurance policies.',
        ],
      },
      {
        heading: 'Moving together, and children',
        paragraphs: [
          'When a household moves, the applications are separate but the timing is shared. File each adult’s application as its own dossier, lodged together so the consulate sees one household, and attach the children to a parent’s file with the documents below.',
        ],
        requirements: [
          'Each spouse needs their own visa application — there is no joint file',
          'If one of you holds an EU Blue Card or Talent Passport, the other gets automatic work authorisation',
          'Time the applications together for consistency',
          'Minor children under {{18}} are included on a parent’s application, with birth certificates and, where relevant, custody agreements',
        ],
        caveat:
          'Spouses of French nationals are among the categories exempt from the post-arrival visa validation tax. Confirm your category on the ANEF portal, since the exemptions are set by the finance law and can change.',
      },
    ],
    practice: {
      paragraphs: [
        'Delays beyond the 2–10 week range for spouse-of-a-French-citizen files were reported through 2025 and 2026, and family reunification waits have lengthened past the 6–15 month range in reports from the same period.',
      ],
      sources: 'Applicant reports held in the knowledge base · 2025–2026',
    },
  },
  {
    slug: 'validate-your-visa-anef',
    title: 'Validating Your Visa After Arrival',
    when: 'On arrival',
    summary: 'Three months, or the visa lapses',
    description:
      'How to validate a French VLS-TS online through ANEF within three months of arrival: the steps, the stamp, the 2026 tax rise, and what happens if you miss the deadline.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'administration-etrangers-en-france.interieur.gouv.fr', kind: 'official' },
      { label: 'france-visas.gouv.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'The deadline is counted from your entry stamp',
        paragraphs: [
          'A VLS-TS must be validated within {{3 months}} of the date you entered France — the date your passport was stamped at the border, not your lease date, your address registration, or the date printed on the visa. Leaving and re-entering during that window does not reset the clock.',
          'This step is what makes the visa function as a residence permit for its first year. In-person OFII validation of the sticker no longer exists; it is done online for essentially every VLS-TS category.',
        ],
        caveat:
          'Miss the deadline and you are no longer legally present in France, and cannot re-enter the Schengen Area without a new visa — even though the sticker still looks valid. During the first three months you may travel freely without having validated yet; the restriction bites only once the deadline passes.',
      },
      {
        heading: 'The online steps',
        paragraphs: [
          'Validation is done entirely online and takes an evening if the documents are to hand. Follow the six steps in order on the ANEF portal; the account activation and the tax stamp are both time-limited, so do not start unless you can finish the same day.',
        ],
        ordered: true,
        requirements: [
          'Go to administration-etrangers-en-france.interieur.gouv.fr and choose “I validate my visa”',
          'Authenticate via FranceConnect, or create a direct account and activate it within {{24 hours}} of the confirmation email',
          'Enter your visa details, arrival date and French address',
          'Upload your passport photo page, the visa sticker, and proof of address in France',
          'Pay the {{timbre fiscal}} online by card, or buy it at timbres.impots.gouv.fr — the code is single-use and time-limited, so finish the validation the same day',
          'Keep the confirmation email and the PDF “Confirmation de validation” — banks, healthcare and the prefecture will ask for them in your first year',
        ],
      },
      {
        heading: 'The tax went up in 2026',
        paragraphs: [
          'Under Article 128 of the {{loi de finances pour 2026}}, the validation tax for standard VLS-TS categories rose from {{€200}} to {{€300}}, effective {{1 May 2026}}. Reduced-rate categories, students among them, sit lower. Some exemptions remain — spouses of French nationals, certain scholarship holders — so confirm your category on the portal.',
          'The amount is set annually by the finance law and revised, so check the ANEF portal or timbres.impots.gouv.fr on the day you pay rather than trusting any figure written earlier, including this one.',
        ],
      },
      {
        heading: 'If your passport was not stamped',
        paragraphs: [
          'Entering by land or rail from another Schengen country, or on a flight connecting through another Schengen airport, may leave you without an entry stamp. ANEF still requires an entry date, so be ready to document it another way — a boarding pass, a ticket, a lease start date.',
        ],
        requirements: [
          'Some categories are summoned afterwards to an in-person medical visit and/or to sign the Contrat d’Intégration Républicaine; Talent Passport holders are exempt from both',
          'Once validated you can travel within Schengen for the rest of the visa, apply for the Carte Vitale, and open a bank account more easily',
        ],
      },
    ],
  },
  {
    slug: 'vls-ts-or-carte-de-sejour',
    title: 'VLS-TS or Carte de Séjour à Solliciter?',
    when: '12 months out',
    summary: 'You don’t choose. It decides your first months.',
    description:
      'The two kinds of French long-stay visa, why the consulate picks one for you, and how each changes what you must do in your first three months in France.',
    verified: '2026-09',
    sourceCount: 2,
    sources: [
      { label: 'france-visas.gouv.fr', kind: 'official' },
      { label: 'service-public.fr', kind: 'official' },
    ],
    sections: [
      {
        heading: 'Two visas that look alike',
        paragraphs: [
          'A long-stay visa for more than {{90 days}} arrives either as a VLS-TS — a visa that is also a residence permit, usually for one year — or as a visa marked “carte de séjour à solliciter”, which grants entry only and leaves the residence right to be issued by a prefecture after you arrive.',
          'They are not interchangeable, and you do not pick between them. On France-Visas you select only your purpose of stay; the consulate decides the type from the legal category, the risk profile and administrative policy.',
        ],
      },
      {
        heading: 'What a VLS-TS means for you',
        paragraphs: [
          'If the sticker in your passport is a VLS-TS, the four points below are your first year. The one with a deadline is the first: online validation within three months of the entry stamp, which is what turns the visa into a residence permit.',
        ],
        requirements: [
          'Mandatory online validation within {{3 months}} of arrival through the ANEF portal — track the calendar date, not a day count',
          'A validation tax, set by the annual finance law and raised again on {{1 May 2026}} — confirm the current amount on ANEF before paying',
          'No prefecture appointment in the first year',
          'Renewal at the prefecture {{2–4 months}} before expiry',
        ],
      },
      {
        heading: 'What “carte de séjour à solliciter” means for you',
        paragraphs: [
          'If instead the sticker says carte de séjour à solliciter, there is no online step and no validation tax, but there is a prefecture appointment with a shorter deadline, and your status is not settled until the card is in your hand. Book the appointment in your first days.',
        ],
        requirements: [
          'No online validation and no validation tax',
          'A mandatory prefecture appointment — the visa is stamped “à solliciter dans les {{2 mois}}”, the standard national deadline',
          'Your residence status is not secure until the card is issued',
          'Higher risk if the deadline is missed',
        ],
      },
      {
        heading: 'Who tends to get which',
        paragraphs: [
          'Most visitor visas — retirees, the financially independent, second-home owners — are issued as VLS-TS: a low-risk category with no employment and no access to benefits.',
          'Family-based visas — spouse of a French national, parent of a French child, family reunification — are often “à solliciter”, because marriage validity, family ties or cohabitation are verified locally. So are some salaried, self-employed and regulated-profession visas, and some Talent Passport sub-categories, where work authorisation is checked locally.',
        ],
        caveat:
          'Expect a VLS-TS if you are financially independent, not working in France and not relying on family-based rights. Expect “carte de séjour à solliciter” if your stay involves employment, a regulated profession, family reunification or complex personal circumstances.',
      },
    ],
  },
  {
    slug: 'digital-nomad-visa-france',
    title: 'There Is No Digital Nomad Visa',
    when: '12 months out',
    summary: 'Remote workers apply as visitors',
    description:
      'France has no digital nomad visa as of 2026. What remote workers employed abroad actually apply for, what consulates now ask about the arrangement, and where the law is still unsettled.',
    verified: '2026-09',
    sourceCount: 1,
    sources: [{ label: 'france-visas.gouv.fr', kind: 'official' }],
    sections: [
      {
        heading: 'The phrase is marketing, not a category',
        paragraphs: [
          'France still has no legally distinct digital nomad visa as of {{September 2026}}. “Digital nomad visa” is language used by relocation companies and media; it is not a France-Visas category, and you will not find it on the application.',
          'Remote workers employed by non-French companies apply under the standard long-stay visitor visa, with the same documentation as any visitor applicant.',
        ],
      },
      {
        heading: 'What you actually apply for',
        paragraphs: [
          'Because there is no separate category, a remote worker applies as a visitor and meets the visitor file. The five items below are the same as for a retiree, with one difference in emphasis: the consulate will want to see that the income is stable and that the work is for an employer or clients outside France.',
        ],
        requirements: [
          'A valid passport with {{6+ months}} validity beyond the intended stay',
          'Stable monthly resources at or around net SMIC — roughly {{€1,426–€1,500}} a month in 2026 — or savings of roughly {{€17,000–€18,000}} to cover a year',
          'Comprehensive health insurance covering the full stay, including repatriation and emergency care',
          'Proof of accommodation in France',
          'A signed declaration ({{attestation sur l’honneur}}) not to work in France',
        ],
        caveat:
          'Figures you see quoted vary with whether a consulate is referencing gross SMIC, around {{€1,802}} a month, or net. The requirement is “sufficient means”, assessed case by case.',
      },
      {
        heading: 'Consulates now ask about the arrangement',
        paragraphs: [
          'Some consulates now ask for more detail on remote work: an employer letter confirming the arrangement is compatible with French residency, and how your time will be split between France and elsewhere.',
          'Whether the visitor visa permits remote work for a purely foreign employer remains genuinely contested and evolving. It clearly does not permit working for a French employer or French clients. Do not settle the question on the strength of a forum post, in either direction.',
        ],
      },
    ],
  },
  {
    slug: 'visa-application-timeline',
    title: 'When to Start Each Document',
    when: '6 months out',
    summary: 'The FBI check sets the pace',
    description:
      'A working timeline for a French long-stay visa from the US: lead times for the FBI check, copies and apostilles, when to book the consulate, and what the official processing times really say.',
    verified: '2026-09',
    sourceCount: 1,
    sources: [{ label: 'france-visas.gouv.fr', kind: 'official' }],
    sections: [
      {
        heading: 'What the official times say',
        paragraphs: [
          'France-Visas publishes no fixed statutory turnaround for long-stay visas. Consulates commonly process complete dossiers in roughly {{2–6 weeks}}, varying by consulate, category and season. Applications must be submitted between {{3 months}} and {{15 days}} before travel.',
          'In peak season — roughly {{May–August}}, and to a lesser extent December — add {{1–3 weeks}} for both appointments and decisions. An incomplete file pauses the clock entirely until the missing documents arrive. Expedited processing is not a standard service for long-stay visas.',
        ],
      },
      {
        heading: 'Lead times that catch people out',
        paragraphs: [
          'The consulate’s own processing time is the short part. The long part is the paperwork that has to exist before you can book: the FBI check, the certified copies, the apostilles. Translations come later, for the prefecture, not the consulate. The seven lead times below are the ones that decide whether your timeline holds, and the first one is the one to start today.',
        ],
        requirements: [
          'FBI background check: {{10–14 weeks}} by mail direct from the FBI, versus roughly {{3–7 business days}} through an FBI-approved channeler — use the channeler',
          'State apostilles: {{1–2 weeks}} in most states; some offer {{24–48 hour}} expedited service, others by mail can run {{4+ weeks}}',
          'Birth and marriage certificate apostilles: {{1–3 weeks}}, with the same variability',
          'Sworn French translations ({{traducteur assermenté}}): not for the application, which US consulates take in English, but for the prefecture and CPAM after arrival; {{1–2 weeks}} per batch',
          'Official stamped bank statements: allow {{1–2 weeks}}',
          'Academic transcripts and diploma authentication: {{2–3 weeks}}, longer if the institution is slow',
          'Medical examinations, where your category requires one: {{1–2 weeks}} for an appointment',
        ],
      },
      {
        heading: 'A timeline that works',
        paragraphs: [
          'Working back from your travel date, this is the sequence that leaves room for the slow items. Treat the first two milestones as fixed and the rest as the earliest sensible moment; the consulate appointment is booked as soon as the dossier is ready, not on a calendar date.',
        ],
        ordered: true,
        requirements: [
          '{{5–6 months}} before travel: start collecting documents, the FBI check first',
          '{{3–4 months}} before: submit apostille requests, book any medical exam',
          '{{2–3 months}} before: book the consulate appointment as soon as your online dossier is ready — slots vary widely by consulate and season',
          '{{6–8 weeks}} before: submit the complete application at the appointment',
          '{{2–3 weeks}} before: follow up if there is no decision',
        ],
        caveat:
          'The visa fee is {{€99}} for most long-stay categories and is not refunded on a refusal. The post-arrival validation tax is a separate, later cost that is easy to miss because it is not part of the consulate application.',
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
  '/about/',
  '/guides/',
  ...guides.map((g) => `/guides/${g.slug}/`),
];
