import React, { useState, useMemo } from 'react';
import { clsx } from 'clsx';
import {
  Book,
  Search,
  ChevronDown,
  ChevronRight,
  Copy,
  Check,
  FileText,
  Home as HomeIcon,
  Heart,
  AlertCircle,
  Landmark,
  Building2,
  Coffee,
} from 'lucide-react';
import { useGlossary } from '@/hooks/useApi';
import type { GlossaryTerm, GlossaryCategory } from '@/types';

// Transform API data format to match our expected format
// API returns: { term, definition, pronunciation }
// We expect: { id, title, french, short, full, category }
interface ApiTerm {
  term?: string;
  definition?: string;
  pronunciation?: string;
}

interface ApiCategory {
  id?: string;
  title?: string;
  terms?: ApiTerm[];
}

function transformApiData(apiData: ApiCategory[] | undefined): GlossaryCategory[] | null {
  if (!apiData || !Array.isArray(apiData)) return null;

  try {
    return apiData.map((cat, catIndex) => ({
      id: cat.id || `category-${catIndex}`,
      title: cat.title || 'Unknown Category',
      terms: (cat.terms || []).map((term, termIndex) => ({
        id: `${cat.id || 'cat'}-${termIndex}`,
        title: term.term || 'Unknown Term',
        french: term.term || '',
        short: term.definition || '',
        full: term.pronunciation ? `Pronunciation: ${term.pronunciation}` : undefined,
        category: cat.id || `category-${catIndex}`,
      })),
    }));
  } catch {
    return null;
  }
}

// Hardcoded glossary data (fallback)
const hardcodedCategories: GlossaryCategory[] = [
  {
    id: 'document-legal',
    title: 'Document & Legal Terms',
    terms: [
      {
        id: 'apostille',
        title: 'Apostille',
        french: 'Apostille',
        short: 'Official certificate authenticating documents for international use',
        full: 'An apostille is a form of authentication issued to documents for use in countries that participate in the Hague Convention of 1961. It certifies that a document is a true copy of an original, making it legally valid in other member countries without additional certification.',
        category: 'document-legal',
      },
      {
        id: 'certified-copy',
        title: 'Certified Copy',
        french: 'Copie certifiée conforme',
        short: 'An official copy verified by the issuing authority',
        full: 'A certified copy is an official copy of a document that has been verified as a true and accurate reproduction of the original by an authorized person or entity, such as a notary public, government official, or the issuing authority.',
        category: 'document-legal',
      },
      {
        id: 'attestation',
        title: 'Sworn Statement',
        french: 'Attestation sur l\'honneur',
        short: 'A sworn statement declaring something to be true',
        full: 'A sworn statement or affidavit declaring that certain facts or statements are true to the best of your knowledge. In France, this is a legally binding document where you attest to the veracity of information under penalty of perjury. Commonly required for administrative procedures.',
        category: 'document-legal',
      },
      {
        id: 'compromis',
        title: 'Preliminary Sales Agreement',
        french: 'Compromis de vente',
        short: 'Preliminary sales agreement for property purchase',
        full: 'The preliminary contract for buying property in France, signed by both buyer and seller. This legally binding agreement sets out the terms of the sale and is typically signed several weeks before the final deed of sale (acte de vente). You have a 10-day cooling-off period after signing.',
        category: 'document-legal',
      },
      {
        id: 'acte-vente',
        title: 'Final Deed of Sale',
        french: 'Acte de vente',
        short: 'Final deed of sale transferring property ownership',
        full: 'The final deed of sale for property purchase in France, signed before a notary (notaire). This document officially transfers ownership from seller to buyer and must be registered with the land registry.',
        category: 'document-legal',
      },
      {
        id: 'procuration',
        title: 'Power of Attorney',
        french: 'Procuration',
        short: 'Power of attorney authorizing someone to act on your behalf',
        full: 'A legal document granting someone the authority to act on your behalf in specific matters. In France, procurations are commonly used for property purchases, bank transactions, or administrative procedures when you cannot be physically present.',
        category: 'document-legal',
      },
      {
        id: 'notaire',
        title: 'Notary',
        french: 'Notaire',
        short: 'Public official who authenticates legal documents',
        full: 'A notaire is a highly qualified legal professional appointed by the state. Unlike US notaries, French notaires have extensive legal training and are required for property transactions, inheritances, marriage contracts, and company formations. They ensure documents are legally valid and properly registered.',
        category: 'document-legal',
      },
      {
        id: 'livret-famille',
        title: 'Family Record Book',
        french: 'Livret de famille',
        short: 'Official booklet recording family civil status events',
        full: 'An official document issued at marriage or birth of a first child, recording all civil status events for a family (marriages, births, deaths, divorces). This booklet is frequently required for administrative procedures in France and serves as proof of family relationships.',
        category: 'document-legal',
      },
      {
        id: 'extrait-acte-naissance',
        title: 'Birth Certificate Extract',
        french: 'Extrait d\'acte de naissance',
        short: 'Official extract of birth certificate',
        full: 'An official extract from the civil registry containing birth information. France distinguishes between "copie intégrale" (full copy) and "extrait" (extract with or without filiation). Many administrative procedures require a recent extract (less than 3-6 months old).',
        category: 'document-legal',
      },
      {
        id: 'casier-judiciaire',
        title: 'Criminal Record',
        french: 'Casier judiciaire',
        short: 'Official criminal record certificate',
        full: 'An official document showing criminal convictions. Bulletin n°3 (the most common) shows only serious convictions and can be requested by the individual. Often required for employment, visa applications, or adopting. US equivalent would be an FBI background check.',
        category: 'document-legal',
      },
      {
        id: 'traducteur-assermente',
        title: 'Sworn Translator',
        french: 'Traducteur assermenté',
        short: 'Court-certified translator for official documents',
        full: 'A translator officially recognized by French courts to provide certified translations of legal documents. Only translations by sworn translators are accepted for official procedures like visa applications, court cases, and administrative filings.',
        category: 'document-legal',
      },
    ],
  },
  {
    id: 'visa-residency',
    title: 'Visa & Residency Terms',
    terms: [
      {
        id: 'vls-ts',
        title: 'Long-Stay Visa (Residence Permit)',
        french: 'Visa de Long Séjour valant Titre de Séjour (VLS-TS)',
        short: 'Long-stay visa equivalent to residence permit',
        full: 'A long-stay visa that serves as a residence permit for the first year in France. After entering France with a VLS-TS, you must validate it with OFII within 3 months. This validation makes the visa equivalent to a residence permit for one year.',
        category: 'visa-residency',
      },
      {
        id: 'titre-sejour',
        title: 'Residence Permit',
        french: 'Titre de séjour',
        short: 'Residence permit card allowing you to live in France',
        full: 'A residence permit card issued by the Prefecture that allows you to legally reside in France for a specified period. After your first year with a VLS-TS, you\'ll need to apply for a titre de séjour renewal at your local prefecture.',
        category: 'visa-residency',
      },
      {
        id: 'ofii',
        title: 'Immigration Office',
        french: 'Office Français de l\'Immigration et de l\'Intégration (OFII)',
        short: 'French Immigration and Integration Office',
        full: 'The French Office for Immigration and Integration. OFII is responsible for validating long-stay visas, organizing mandatory integration programs, and managing certain aspects of legal immigration to France. You must complete OFII validation within 3 months of arrival.',
        category: 'visa-residency',
      },
      {
        id: 'prefecture',
        title: 'Prefecture',
        french: 'Préfecture',
        short: 'Regional government office handling residence permits',
        full: 'The prefecture is the local government office representing the French state in each department. It handles residence permit applications, renewals, driver\'s license exchanges, and various other administrative procedures for foreign residents.',
        category: 'visa-residency',
      },
      {
        id: 'recepisse',
        title: 'Receipt/Temporary Permit',
        french: 'Récépissé',
        short: 'Temporary receipt while waiting for documents',
        full: 'A temporary document issued by the Prefecture acknowledging that you have submitted an application for a residence permit. It serves as proof that you are legally allowed to stay in France while your application is being processed and may authorize you to work.',
        category: 'visa-residency',
      },
      {
        id: 'carte-resident',
        title: 'Resident Card',
        french: 'Carte de résident',
        short: '10-year residence permit',
        full: 'A 10-year renewable residence permit granting extensive rights in France, including unlimited work authorization. Available after 5 years of legal residence (3 years in some cases) or through family ties to French citizens. Provides more stability than annual permits.',
        category: 'visa-residency',
      },
      {
        id: 'regroupement-familial',
        title: 'Family Reunification',
        french: 'Regroupement familial',
        short: 'Process to bring family members to France',
        full: 'The legal procedure allowing a foreign resident in France to bring their spouse and minor children to join them. Requires stable income, adequate housing, and at least 18 months of legal residence. Different from accompanying family on initial visa.',
        category: 'visa-residency',
      },
      {
        id: 'passeport-talent',
        title: 'Talent Passport',
        french: 'Passeport talent',
        short: 'Multi-year residence permit for skilled workers',
        full: 'A residence permit for highly skilled workers, researchers, artists, investors, and entrepreneurs. Valid up to 4 years and renewable. Categories include employees on assignment, researchers, company founders, investors, and artists. Family members receive automatic permits.',
        category: 'visa-residency',
      },
      {
        id: 'autorisation-travail',
        title: 'Work Authorization',
        french: 'Autorisation de travail',
        short: 'Permission to work legally in France',
        full: 'Official authorization to work in France. Some residence permits include automatic work authorization, while others require a separate application. Employers must verify work authorization before hiring. Working without authorization is illegal for both employee and employer.',
        category: 'visa-residency',
      },
      {
        id: 'cir',
        title: 'Republican Integration Contract',
        french: 'Contrat d\'Intégration Républicaine (CIR)',
        short: 'Mandatory integration program for new residents',
        full: 'A mandatory integration program for most long-term visa holders. Includes civic training about French values, language assessment, and French classes if needed. Completion is required for residence permit renewal and eventual naturalization.',
        category: 'visa-residency',
      },
    ],
  },
  {
    id: 'healthcare',
    title: 'Healthcare Terms',
    terms: [
      {
        id: 'puma',
        title: 'Universal Health Coverage',
        french: 'Protection Universelle Maladie (PUMA)',
        short: 'Universal health coverage system',
        full: 'Protection Universelle Maladie (Universal Health Protection) is France\'s universal healthcare coverage system. If you live in France in a stable and regular manner (at least 3 months per year), you are entitled to healthcare coverage under PUMA, managed by the national health insurance (Assurance Maladie).',
        category: 'healthcare',
      },
      {
        id: 'carte-vitale',
        title: 'Health Insurance Card',
        french: 'Carte Vitale',
        short: 'French health insurance card',
        full: 'The green electronic health insurance card containing your personal information and social security number. It allows healthcare providers to directly bill your treatments to the national health insurance system. You receive this card after registering with CPAM.',
        category: 'healthcare',
      },
      {
        id: 'mutuelle',
        title: 'Supplementary Insurance',
        french: 'Mutuelle / Complémentaire santé',
        short: 'Supplementary private health insurance',
        full: 'Complementary private health insurance that covers expenses not fully reimbursed by the national health insurance system (typically 70% of costs). A mutuelle typically covers the remaining 30% (called the "ticket modérateur") plus additional services like dental, optical, and alternative medicine.',
        category: 'healthcare',
      },
      {
        id: 'cpam',
        title: 'Health Insurance Office',
        french: 'Caisse Primaire d\'Assurance Maladie (CPAM)',
        short: 'Local health insurance office',
        full: 'The local branch of the French national health insurance system. CPAM handles registration, reimbursements, and issues the Carte Vitale. Each department has its own CPAM office where you must register after obtaining your residence permit.',
        category: 'healthcare',
      },
      {
        id: 'medecin-traitant',
        title: 'Primary Care Doctor',
        french: 'Médecin traitant',
        short: 'Designated primary care physician',
        full: 'Your designated primary care doctor who coordinates your healthcare. You must declare a médecin traitant to receive full reimbursement rates. Seeing specialists without a referral from your médecin traitant results in lower reimbursement (except for certain specialists like gynecologists and ophthalmologists).',
        category: 'healthcare',
      },
      {
        id: 'numero-secu',
        title: 'Social Security Number',
        french: 'Numéro de sécurité sociale',
        short: 'French social security identification number',
        full: 'A 15-digit number assigned to everyone registered with French social security. Unlike US SSN, it encodes gender, birth year/month, department, and commune of birth. Required for healthcare, employment, taxes, and most administrative procedures.',
        category: 'healthcare',
      },
      {
        id: 'pharmacie',
        title: 'Pharmacy',
        french: 'Pharmacie',
        short: 'Regulated pharmacy with green cross sign',
        full: 'French pharmacies (marked by green crosses) are the only places to buy medications, even common ones like ibuprofen. Pharmacists are highly trained and can provide medical advice, administer vaccines, and suggest treatments for minor ailments. A "pharmacie de garde" provides 24/7 service on rotation.',
        category: 'healthcare',
      },
      {
        id: 'ordonnance',
        title: 'Prescription',
        french: 'Ordonnance',
        short: 'Medical prescription from a doctor',
        full: 'A written prescription from a doctor required for most medications in France. Prescriptions are valid for varying periods (3-12 months) and often specify the number of renewals. Keep your ordonnance as it\'s needed for reimbursement and pharmacy refills.',
        category: 'healthcare',
      },
      {
        id: 'tiers-payant',
        title: 'Third-Party Payment',
        french: 'Tiers payant',
        short: 'Direct billing to insurance (no upfront payment)',
        full: 'A system where healthcare providers bill the insurance directly, so you don\'t pay upfront. Available at pharmacies (for prescriptions) and increasingly at doctors\' offices. Without tiers payant, you pay the full amount and await reimbursement.',
        category: 'healthcare',
      },
      {
        id: 'arret-maladie',
        title: 'Sick Leave Certificate',
        french: 'Arrêt maladie / Arrêt de travail',
        short: 'Official sick leave document from a doctor',
        full: 'An official document from a doctor certifying you are unable to work due to illness. Must be sent to your employer within 48 hours. Social security pays daily allowances (indemnités journalières) after a 3-day waiting period, often supplemented by employer.',
        category: 'healthcare',
      },
    ],
  },
  {
    id: 'banking',
    title: 'Banking & Financial Terms',
    terms: [
      {
        id: 'rib',
        title: 'Bank Account Details',
        french: 'Relevé d\'Identité Bancaire (RIB)',
        short: 'Document with your bank account information',
        full: 'A document containing your bank account details including IBAN and BIC/SWIFT codes. Essential for setting up direct debits, receiving salary, and any bank transfers. You can download it from online banking or get it from your bank branch.',
        category: 'banking',
      },
      {
        id: 'prelevement',
        title: 'Direct Debit',
        french: 'Prélèvement automatique',
        short: 'Automatic payment from your bank account',
        full: 'Automatic recurring payments debited directly from your bank account. Common for utilities, rent, insurance, and subscriptions. You authorize by providing your RIB and signing a SEPA mandate. You can contest unauthorized debits within 13 months.',
        category: 'banking',
      },
      {
        id: 'virement',
        title: 'Bank Transfer',
        french: 'Virement bancaire',
        short: 'Transfer money between bank accounts',
        full: 'Transferring money from one bank account to another. SEPA transfers within Europe are typically free and take 1 business day. You need the recipient\'s IBAN to initiate a transfer. Can be one-time or set up as recurring (virement permanent).',
        category: 'banking',
      },
      {
        id: 'carte-bancaire',
        title: 'Debit Card',
        french: 'Carte bancaire (CB)',
        short: 'French bank debit card',
        full: 'French bank cards function as debit cards with immediate or deferred debit. "Débit immédiat" debits immediately; "débit différé" accumulates purchases and debits monthly. Cards use chip-and-PIN exclusively. Contactless payment is standard for amounts under €50.',
        category: 'banking',
      },
      {
        id: 'chequier',
        title: 'Checkbook',
        french: 'Chéquier / Carnet de chèques',
        short: 'Book of personal checks',
        full: 'Despite declining use globally, checks remain common in France for rent, medical consultations, and some services. Banks provide checkbooks free of charge. Bouncing a check is a serious offense that can result in being banned from having a checkbook (interdit bancaire).',
        category: 'banking',
      },
      {
        id: 'livret-a',
        title: 'Tax-Free Savings Account',
        french: 'Livret A',
        short: 'Regulated tax-free savings account',
        full: 'A government-regulated savings account available to all French residents. Interest is tax-free with a current cap of €22,950. The interest rate is set by the government. Every bank offers Livret A, and you can only have one per person.',
        category: 'banking',
      },
      {
        id: 'impots',
        title: 'Taxes',
        french: 'Impôts',
        short: 'French tax system and tax office',
        full: 'French income tax (impôt sur le revenu) is now withheld at source (prélèvement à la source). You must file an annual declaration even if tax is withheld. The tax office (Centre des Impôts/Service des Impôts des Particuliers) handles all tax matters.',
        category: 'banking',
      },
      {
        id: 'avis-imposition',
        title: 'Tax Notice',
        french: 'Avis d\'imposition',
        short: 'Official document showing your tax status',
        full: 'An official document from the tax authority showing your declared income and tax calculated or paid. This crucial document is required for many administrative procedures: renting an apartment, applying for social benefits, obtaining a residence permit, and more.',
        category: 'banking',
      },
      {
        id: 'caf',
        title: 'Family Allowance Office',
        french: 'Caisse d\'Allocations Familiales (CAF)',
        short: 'Agency managing family and housing benefits',
        full: 'Government agency managing various social benefits including housing assistance (APL/ALS), family allowances, childcare subsidies, and income support (RSA). Legal residents can apply for housing benefits which can significantly reduce rent costs.',
        category: 'banking',
      },
      {
        id: 'apl',
        title: 'Housing Assistance',
        french: 'Aide Personnalisée au Logement (APL)',
        short: 'Government housing benefit',
        full: 'A housing subsidy paid to eligible residents to help cover rent costs. Amount depends on income, rent, location, and family situation. Paid directly to landlord or tenant monthly. Apply through CAF after signing your lease.',
        category: 'banking',
      },
    ],
  },
  {
    id: 'housing',
    title: 'Housing & Accommodation Terms',
    terms: [
      {
        id: 'bail',
        title: 'Lease Agreement',
        french: 'Bail / Contrat de location',
        short: 'Rental contract between landlord and tenant',
        full: 'The rental agreement between landlord (bailleur) and tenant (locataire). Standard unfurnished leases are 3 years minimum; furnished leases are 1 year (9 months for students). The lease defines rent, charges, deposit, and conditions. Strong tenant protections exist in French law.',
        category: 'housing',
      },
      {
        id: 'depot-garantie',
        title: 'Security Deposit',
        french: 'Dépôt de garantie',
        short: 'Refundable deposit paid when signing lease',
        full: 'A refundable deposit paid when signing a lease, limited by law to 1 month rent (unfurnished) or 2 months (furnished). Must be returned within 1-2 months after leaving, minus any justified deductions for damages or unpaid rent.',
        category: 'housing',
      },
      {
        id: 'charges',
        title: 'Service Charges',
        french: 'Charges locatives',
        short: 'Additional costs on top of base rent',
        full: 'Monthly costs in addition to base rent, covering shared building expenses like heating, water, elevator maintenance, cleaning, and trash collection. Can be "provisions" (estimated monthly, adjusted annually) or "forfait" (fixed amount). Listed separately from rent.',
        category: 'housing',
      },
      {
        id: 'etat-lieux',
        title: 'Property Inspection Report',
        french: 'État des lieux',
        short: 'Detailed inspection at move-in and move-out',
        full: 'A detailed written report documenting the condition of a rental property at move-in and move-out. Both landlord and tenant sign it. Differences between entry and exit reports determine deposit deductions. Always be thorough and note any existing damage.',
        category: 'housing',
      },
      {
        id: 'garant',
        title: 'Guarantor',
        french: 'Garant / Caution',
        short: 'Person who guarantees rent payment',
        full: 'A person (or organization like Visale) who commits to paying rent if the tenant cannot. French landlords often require a guarantor earning 3-4x the rent. Non-French residents can use Visale (free government guarantee) or paid guarantee services.',
        category: 'housing',
      },
      {
        id: 'dossier-location',
        title: 'Rental Application File',
        french: 'Dossier de location',
        short: 'Documents required to rent an apartment',
        full: 'The complete set of documents required when applying for a rental: ID, proof of income (3 pay slips, tax notice, employment contract), proof of address, and guarantor documents. French landlords are legally limited in what they can request.',
        category: 'housing',
      },
      {
        id: 'preavis',
        title: 'Notice Period',
        french: 'Préavis',
        short: 'Required notice before leaving a rental',
        full: 'The advance notice required before terminating a lease. For tenants: 3 months (unfurnished) or 1 month (furnished, or unfurnished in "zone tendue" tight housing markets). Landlords have 6 months notice and can only terminate for specific legal reasons.',
        category: 'housing',
      },
      {
        id: 'copropriete',
        title: 'Condominium/Co-ownership',
        french: 'Copropriété',
        short: 'Shared ownership of building common areas',
        full: 'The legal structure for apartment buildings where individual units are privately owned but common areas are collectively owned. The copropriété has a syndic (management company), annual meetings, and charges for maintenance. Buyers receive the carnet d\'entretien (maintenance log).',
        category: 'housing',
      },
      {
        id: 'syndic',
        title: 'Building Management Company',
        french: 'Syndic de copropriété',
        short: 'Professional company managing a building',
        full: 'A professional or volunteer entity managing a copropriété building: collecting charges, organizing maintenance, holding annual meetings, and enforcing building rules. The syndic is elected by co-owners and their fees are part of the charges.',
        category: 'housing',
      },
      {
        id: 'taxe-habitation',
        title: 'Housing Tax',
        french: 'Taxe d\'habitation',
        short: 'Annual tax on primary residence occupants',
        full: 'An annual local tax paid by whoever occupies a residence on January 1st. Being phased out for primary residences but still applies to second homes. Separate from taxe foncière (property tax paid by owners). Amount varies by commune.',
        category: 'housing',
      },
    ],
  },
  {
    id: 'daily-life',
    title: 'Daily Life & Services',
    terms: [
      {
        id: 'mairie',
        title: 'Town Hall',
        french: 'Mairie / Hôtel de ville',
        short: 'Local government office for civil and administrative matters',
        full: 'The local town hall handles birth/death/marriage certificates, voter registration, school enrollment, various permits, and local services. Many documents like certified copies and attestations must be obtained from your local mairie.',
        category: 'daily-life',
      },
      {
        id: 'carte-identite',
        title: 'National ID Card',
        french: 'Carte nationale d\'identité',
        short: 'Official French identity document',
        full: 'The official French national ID card, available only to French citizens. Valid for 15 years. Unlike the US, France has a national ID separate from driver\'s licenses. For non-citizens, your residence permit serves as your ID in France.',
        category: 'daily-life',
      },
      {
        id: 'justificatif-domicile',
        title: 'Proof of Address',
        french: 'Justificatif de domicile',
        short: 'Document proving where you live',
        full: 'A recent document (less than 3-6 months old) proving your address. Accepted documents include utility bills (electricity, gas, internet), rent receipts, tax notices, or home insurance certificates. Required for nearly every administrative procedure.',
        category: 'daily-life',
      },
      {
        id: 'permis-conduire',
        title: 'Driver\'s License',
        french: 'Permis de conduire',
        short: 'Official license to drive',
        full: 'French driver\'s license, now a credit card-sized format. US licenses are valid for one year after establishing residence, then must be exchanged (no test required for most US states) or a French license obtained. Apply for exchange at ANTS website.',
        category: 'daily-life',
      },
      {
        id: 'carte-grise',
        title: 'Vehicle Registration',
        french: 'Carte grise / Certificat d\'immatriculation',
        short: 'Vehicle registration document',
        full: 'The official vehicle registration certificate required to drive in France. Must be obtained within 1 month of purchasing a vehicle or moving to France with a foreign vehicle. Applied for online through ANTS. Includes technical inspection (contrôle technique) requirements.',
        category: 'daily-life',
      },
      {
        id: 'la-poste',
        title: 'Post Office',
        french: 'La Poste',
        short: 'French postal service',
        full: 'The French postal service handles mail, packages, and basic banking services (Banque Postale). Post offices offer services like registered mail (recommandé), which is required for many official communications. Yellow mailboxes are for regular mail.',
        category: 'daily-life',
      },
      {
        id: 'recommande',
        title: 'Registered Mail',
        french: 'Lettre recommandée avec accusé de réception',
        short: 'Tracked mail with delivery confirmation',
        full: 'A tracked letter with proof of delivery, required for important legal communications like lease termination, employer resignation, or formal complaints. The "accusé de réception" (delivery receipt) provides legal proof the recipient received the letter.',
        category: 'daily-life',
      },
      {
        id: 'ants',
        title: 'Online Government Services',
        french: 'Agence Nationale des Titres Sécurisés (ANTS)',
        short: 'Website for official documents',
        full: 'The official government website (ants.gouv.fr) for applying for driver\'s licenses, vehicle registration, and passports/ID cards (for French citizens). Most procedures are now done online through ANTS rather than at prefecture counters.',
        category: 'daily-life',
      },
      {
        id: 'france-connect',
        title: 'Digital Identity Login',
        french: 'FranceConnect',
        short: 'Unified login for government websites',
        full: 'A single sign-on system allowing access to many French government websites using one set of credentials (from your tax account, health insurance, or postal ID). Simplifies access to online administrative services.',
        category: 'daily-life',
      },
      {
        id: 'ameli',
        title: 'Health Insurance Website',
        french: 'Ameli',
        short: 'Online portal for health insurance',
        full: 'The online portal (ameli.fr) for the French health insurance system. Create an account to track reimbursements, download your attestation de droits (proof of coverage), update your information, and declare your médecin traitant.',
        category: 'daily-life',
      },
    ],
  },
];

// Category colors for visual coding
const categoryColors = {
  'document-legal': {
    bg: 'bg-blue-50',
    border: 'border-blue-200',
    text: 'text-blue-700',
    icon: 'text-blue-600',
    hover: 'hover:bg-blue-100',
  },
  'visa-residency': {
    bg: 'bg-purple-50',
    border: 'border-purple-200',
    text: 'text-purple-700',
    icon: 'text-purple-600',
    hover: 'hover:bg-purple-100',
  },
  healthcare: {
    bg: 'bg-red-50',
    border: 'border-red-200',
    text: 'text-red-700',
    icon: 'text-red-600',
    hover: 'hover:bg-red-100',
  },
  banking: {
    bg: 'bg-green-50',
    border: 'border-green-200',
    text: 'text-green-700',
    icon: 'text-green-600',
    hover: 'hover:bg-green-100',
  },
  housing: {
    bg: 'bg-amber-50',
    border: 'border-amber-200',
    text: 'text-amber-700',
    icon: 'text-amber-600',
    hover: 'hover:bg-amber-100',
  },
  'daily-life': {
    bg: 'bg-teal-50',
    border: 'border-teal-200',
    text: 'text-teal-700',
    icon: 'text-teal-600',
    hover: 'hover:bg-teal-100',
  },
};

// Category icons
const categoryIcons = {
  'document-legal': FileText,
  'visa-residency': HomeIcon,
  healthcare: Heart,
  banking: Landmark,
  housing: Building2,
  'daily-life': Coffee,
};

export default function GlossaryView() {
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedCategories, setExpandedCategories] = useState<string[]>([
    'document-legal',
    'visa-residency',
    'healthcare',
    'banking',
    'housing',
    'daily-life',
  ]);
  const [expandedTerms, setExpandedTerms] = useState<string[]>([]);
  const [copiedTerm, setCopiedTerm] = useState<string | null>(null);

  // Fetch API data but use hardcoded as primary (more comprehensive)
  const { data: apiData } = useGlossary();

  // Transform API data if available - could be used for supplemental data in future
  const transformedApiData = transformApiData(apiData as ApiCategory[] | undefined);

  // Use hardcoded data as primary (61 terms) - API data available if needed
  // Could merge: [...hardcodedCategories, ...(transformedApiData || [])]
  const categories = transformedApiData && transformedApiData.length > 0
    ? hardcodedCategories // Prefer hardcoded - more comprehensive
    : hardcodedCategories;
  const isLoading = false; // Never show loading since we have hardcoded data
  const searchLoading = false;

  // Filter and sort terms
  const filteredCategories = useMemo(() => {
    // Safely get terms array with fallback
    const getTerms = (category: GlossaryCategory) => {
      if (!category?.terms || !Array.isArray(category.terms)) return [];
      return category.terms.filter((term) => term && typeof term.title === 'string');
    };

    // Safe sort function
    const sortTerms = (terms: GlossaryTerm[]) => {
      return [...terms].sort((a, b) => {
        const titleA = a?.title || '';
        const titleB = b?.title || '';
        return titleA.localeCompare(titleB);
      });
    };

    if (!searchQuery) {
      // No search - show all categories with sorted terms
      return categories.map((category) => ({
        ...category,
        terms: sortTerms(getTerms(category)),
      }));
    }

    // With search - filter and highlight
    const query = searchQuery.toLowerCase();
    return categories
      .map((category) => {
        const terms = getTerms(category);
        const matchingTerms = terms
          .filter(
            (term) =>
              (term.title?.toLowerCase() || '').includes(query) ||
              (term.french?.toLowerCase() || '').includes(query) ||
              (term.short?.toLowerCase() || '').includes(query) ||
              (term.full?.toLowerCase() || '').includes(query)
          );

        return {
          ...category,
          terms: sortTerms(matchingTerms),
        };
      })
      .filter((category) => category.terms.length > 0);
  }, [categories, searchQuery]);

  const toggleCategory = (categoryId: string) => {
    setExpandedCategories((prev) =>
      prev.includes(categoryId)
        ? prev.filter((id) => id !== categoryId)
        : [...prev, categoryId]
    );
  };

  const toggleTerm = (termId: string) => {
    setExpandedTerms((prev) =>
      prev.includes(termId) ? prev.filter((id) => id !== termId) : [...prev, termId]
    );
  };

  const copyToClipboard = async (term: GlossaryTerm) => {
    const text = term.french || term.title;
    try {
      await navigator.clipboard.writeText(text);
      setCopiedTerm(term.id);
      setTimeout(() => setCopiedTerm(null), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const highlightText = (text: string) => {
    if (!searchQuery) return text;

    try {
      // Escape special regex characters
      const escapedQuery = searchQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const parts = text.split(new RegExp(`(${escapedQuery})`, 'gi'));
      return parts.map((part, i) =>
        part.toLowerCase() === searchQuery.toLowerCase() ? (
          <mark key={i} className="bg-yellow-200 text-gray-900">
            {part}
          </mark>
        ) : (
          part
        )
      );
    } catch {
      // If regex fails for any reason, return plain text
      return text;
    }
  };

  return (
    <div className="p-6">
      {/* Page header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <Book className="w-7 h-7 text-primary-600" />
          French Glossary
        </h1>
        <p className="text-gray-600 mt-1">
          Essential French terms and definitions for your relocation journey
        </p>
      </div>

      {/* Search bar */}
      <div className="mb-6">
        <div className="relative max-w-2xl">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search terms across all categories..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          />
        </div>
        {searchQuery && (
          <p className="text-sm text-gray-600 mt-2">
            Found {filteredCategories.reduce((acc, cat) => acc + cat.terms.length, 0)} term(s)
            in {filteredCategories.length} categor{filteredCategories.length === 1 ? 'y' : 'ies'}
          </p>
        )}
      </div>

      {/* Loading state */}
      {(isLoading || (searchQuery && searchLoading)) && (
        <div className="space-y-4">
          {[1, 2, 3].map((i) => (
            <div key={i} className="card p-6 animate-pulse">
              <div className="h-6 bg-gray-200 rounded w-1/4 mb-4" />
              <div className="space-y-3">
                <div className="h-4 bg-gray-100 rounded w-full" />
                <div className="h-4 bg-gray-100 rounded w-5/6" />
                <div className="h-4 bg-gray-100 rounded w-4/6" />
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Empty state */}
      {!isLoading && !searchLoading && filteredCategories.length === 0 && (
        <div className="card p-12 text-center">
          <AlertCircle className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No terms found</h3>
          <p className="text-gray-500">
            {searchQuery
              ? `No glossary terms match "${searchQuery}". Try a different search term.`
              : 'No glossary terms available at the moment.'}
          </p>
        </div>
      )}

      {/* Categories */}
      {!isLoading && !searchLoading && filteredCategories.length > 0 && (
        <div className="space-y-4">
          {filteredCategories.map((category) => {
            const isExpanded = expandedCategories.includes(category.id);
            const colors = categoryColors[category.id as keyof typeof categoryColors] || {
              bg: 'bg-gray-50',
              border: 'border-gray-200',
              text: 'text-gray-700',
              icon: 'text-gray-600',
              hover: 'hover:bg-gray-100',
            };
            const Icon =
              categoryIcons[category.id as keyof typeof categoryIcons] || FileText;

            return (
              <div
                key={category.id}
                className={clsx('card overflow-hidden', colors.border)}
              >
                {/* Category header */}
                <button
                  onClick={() => toggleCategory(category.id)}
                  className={clsx(
                    'w-full flex items-center justify-between px-6 py-4 transition-colors',
                    colors.bg,
                    colors.hover
                  )}
                >
                  <div className="flex items-center gap-3">
                    <div
                      className={clsx(
                        'p-2 bg-white rounded-lg shadow-sm',
                        colors.border,
                        'border'
                      )}
                    >
                      <Icon className={clsx('w-5 h-5', colors.icon)} />
                    </div>
                    <div className="text-left">
                      <h2 className={clsx('text-lg font-semibold', colors.text)}>
                        {category.title}
                      </h2>
                      <p className="text-sm text-gray-600">
                        {category.terms.length} term{category.terms.length !== 1 ? 's' : ''}
                      </p>
                    </div>
                  </div>
                  {isExpanded ? (
                    <ChevronDown className={clsx('w-5 h-5', colors.icon)} />
                  ) : (
                    <ChevronRight className={clsx('w-5 h-5', colors.icon)} />
                  )}
                </button>

                {/* Terms list */}
                {isExpanded && (
                  <div className="p-4 space-y-3">
                    {category.terms.map((term) => (
                      <TermCard
                        key={term.id}
                        term={term}
                        isExpanded={expandedTerms.includes(term.id)}
                        onToggle={() => toggleTerm(term.id)}
                        onCopy={() => copyToClipboard(term)}
                        isCopied={copiedTerm === term.id}
                        highlightText={highlightText}
                        colors={colors}
                      />
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Help text */}
      {!searchQuery && !isLoading && filteredCategories.length > 0 && (
        <div className="mt-8 p-4 bg-primary-50 border border-primary-200 rounded-lg">
          <div className="flex items-start gap-3">
            <Book className="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5" />
            <div>
              <h3 className="font-medium text-primary-900 mb-1">
                Need more information?
              </h3>
              <p className="text-sm text-primary-700">
                Use the search bar to find specific terms, or click on any category to browse
                all available terms. Click on a term to see its full definition.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

interface TermCardProps {
  term: GlossaryTerm;
  isExpanded: boolean;
  onToggle: () => void;
  onCopy: () => void;
  isCopied: boolean;
  highlightText: (text: string) => React.ReactNode;
  colors: {
    bg: string;
    border: string;
    text: string;
    icon: string;
    hover: string;
  };
}

function TermCard({
  term,
  isExpanded,
  onToggle,
  onCopy,
  isCopied,
  highlightText,
  colors,
}: TermCardProps) {
  return (
    <div
      className={clsx(
        'border rounded-lg overflow-hidden transition-all',
        isExpanded ? clsx(colors.border, colors.bg) : 'border-gray-200 bg-white'
      )}
    >
      {/* Term header */}
      <div className="flex items-start gap-3 p-4">
        <button
          onClick={onToggle}
          className="flex-1 text-left min-w-0 group"
        >
          <div className="flex items-start gap-2">
            {isExpanded ? (
              <ChevronDown className={clsx('w-5 h-5 flex-shrink-0 mt-0.5', colors.icon)} />
            ) : (
              <ChevronRight className="w-5 h-5 flex-shrink-0 mt-0.5 text-gray-400 group-hover:text-gray-600" />
            )}
            <div className="flex-1 min-w-0">
              {/* French term (bold) */}
              {term.french && (
                <div className="font-bold text-gray-900 mb-1">
                  {highlightText(term.french)}
                </div>
              )}
              {/* English title */}
              <div
                className={clsx(
                  term.french ? 'text-sm text-gray-700' : 'font-semibold text-gray-900'
                )}
              >
                {highlightText(term.title)}
              </div>
              {/* Short definition */}
              <p className="text-sm text-gray-600 mt-1">{highlightText(term.short)}</p>
              {/* Expand hint */}
              {term.full && !isExpanded && (
                <span className="text-xs text-primary-600 mt-2 inline-block group-hover:underline">
                  Click to read full definition
                </span>
              )}
            </div>
          </div>
        </button>

        {/* Copy button */}
        <button
          onClick={onCopy}
          className={clsx(
            'p-2 rounded-lg transition-colors flex-shrink-0',
            isCopied
              ? 'bg-green-100 text-green-600'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          )}
          title={`Copy "${term.french || term.title}"`}
        >
          {isCopied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
        </button>
      </div>

      {/* Full definition (expanded) */}
      {isExpanded && term.full && (
        <div className="px-4 pb-4 pl-11">
          <div className="p-4 bg-white border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-700 leading-relaxed">{term.full}</p>
          </div>
        </div>
      )}
    </div>
  );
}
