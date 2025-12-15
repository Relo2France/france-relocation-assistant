<?php
/**
 * Default Knowledge Base Data
 * Version: 2.0.0
 * 
 * Structure: Categories with overview + subtopics
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    'visas' => array(
        'overview' => array(
            'title' => 'French Visa Types Overview',
            'content' => "**Types of French Long-Stay Visas for Americans**\n\nUS citizens need a Long-Stay Visa (visa de long séjour) to live in France for more than 90 days. Here are the main categories:\n\n**Visitor Visa (VLS-TS Visiteur)** - For those who will NOT work in France. Requires proof of income/savings and health insurance. Popular for retirees.\n\n**Work Visa (VLS-TS Salarié)** - For employment in France. Requires a job offer from a French employer.\n\n**Talent Passport (Passeport Talent)** - For highly-skilled workers, company founders, investors. Multi-year validity.\n\n**Spouse/Family Visa** - For spouses of French citizens or family reunification.\n\n**All visas require:** Valid passport, application form, photos, proof of accommodation, financial resources, health insurance.",
            'keywords' => array('visa types', 'which visa', 'visa overview', 'visa options', 'all visas', 'types of visas', 'visa categories'),
            'sources' => array(array('name' => 'France-Visas', 'url' => 'https://france-visas.gouv.fr'))
        ),
        'visitor' => array(
            'title' => 'Visitor Visa (Non-Working)',
            'content' => "**Long-Stay Visitor Visa (VLS-TS Visiteur)**\n\nFor those who wish to live in France WITHOUT working. You must sign a declaration promising not to engage in any professional activity.\n\n**Ideal for:** Retirees, those living on savings/investments, accompanying non-working spouses.\n\n**Key Requirements (2025):**\n• Valid passport with 3+ months validity\n• Proof of accommodation in France\n• Proof of financial resources: minimum €1,450/month\n• International health insurance (€30,000+ coverage)\n• Birth certificate with apostille + French translation\n• Attestation sur l'honneur — written declaration promising not to work\n\n**Application Process:**\n1. Create account on France-Visas.gouv.fr\n2. Complete online application\n3. Schedule appointment at French Consulate\n4. Submit documents, pay €99 fee\n5. Wait 2-4 weeks\n6. Receive passport with visa sticker\n\n**Post-Arrival:** Validate visa online within 3 months, pay €225 tax.\n\n**Important:** This visa does NOT permit any work in France.",
            'keywords' => array('visitor visa', 'visiteur', 'non-working', 'retire', 'retirement', 'retiree', 'no work', 'not working', 'savings'),
            'sources' => array(array('name' => 'France-Visas', 'url' => 'https://france-visas.gouv.fr'))
        ),
        'work' => array(
            'title' => 'Work Visa (Employment)',
            'content' => "**Long-Stay Work Visa (VLS-TS Salarié)**\n\nAllows you to be employed in France by a French company.\n\n**Key Requirement:** You must have a job offer BEFORE applying. Your French employer must obtain work authorization.\n\n**Process:**\n1. French employer offers you a position\n2. Employer applies for work authorization (autorisation de travail)\n3. Once approved, you apply for the visa\n4. Visa issued; you can work upon arrival\n\n**Required Documents:**\n• Valid passport\n• Work contract or job offer letter\n• Employer's work authorization approval\n• Proof of qualifications (diplomas)\n• Proof of accommodation\n• Health insurance\n\n**Processing Time:** 2-3 months total\n\n**Alternative:** If you earn €56,000+/year or have specialized skills, consider the Talent Passport instead - faster processing and longer validity.",
            'keywords' => array('work visa', 'employment visa', 'job', 'work in france', 'get hired', 'employment', 'salarie', 'working', 'employer', 'work permit'),
            'sources' => array(array('name' => 'France-Visas', 'url' => 'https://france-visas.gouv.fr'))
        ),
        'talent' => array(
            'title' => 'Talent Passport (Skilled Workers)',
            'content' => "**Passeport Talent - Multi-Year Skilled Worker Visa**\n\nPremium visa for highly-qualified individuals with faster processing and multi-year validity.\n\n**Categories:**\n• **Highly-Qualified Employee** - Salary €56,000+/year, Master's degree\n• **Company Founder** - Creating business in France, €30,000+ investment\n• **Investor** - €300,000+ investment in French company\n• **Researcher** - Hosting agreement with French research institution\n\n**Benefits:**\n• 4-year validity (vs 1 year for standard)\n• Spouse gets automatic work authorization\n• Simplified renewal\n• Path to permanent residency\n\n**Requirements:** Standard visa documents plus proof of qualification for specific category.",
            'keywords' => array('talent passport', 'passeport talent', 'skilled worker', 'highly qualified', 'entrepreneur', 'investor', 'high salary', 'startup'),
            'sources' => array(array('name' => 'France-Visas', 'url' => 'https://france-visas.gouv.fr'))
        ),
        'spouse' => array(
            'title' => 'Spouse & Family Visas',
            'content' => "**Family Visas for France**\n\n**Spouse of French Citizen:**\n• Apply for \"vie privée et familiale\" visa\n• Immediate right to work\n• Required: Marriage certificate, spouse's French ID\n\n**Spouse of Non-EU Resident (Family Reunification):**\n• Your spouse applies for regroupement familial\n• 18-month wait after they obtained residency\n• Income and housing requirements apply\n\n**PACS Partners (Civil Union):**\n• PACS registered 1+ year\n• Proof of shared life required\n\n**Both Spouses Moving Together:**\n• Each needs own visa\n• Talent Passport: Spouse automatically gets work rights\n\n**Children:** Minor children included on parent's application.",
            'keywords' => array('spouse visa', 'family visa', 'husband', 'wife', 'married', 'partner', 'pacs', 'family reunification', 'children'),
            'sources' => array(array('name' => 'Service-Public.fr', 'url' => 'https://www.service-public.fr'))
        ),
        'validation' => array(
            'title' => 'Visa Validation (OFII)',
            'content' => "**Post-Arrival Visa Validation**\n\nAfter arriving in France with a VLS-TS visa, you MUST validate it within 3 months.\n\n**Online Process:**\n1. Go to administration-etrangers-en-france.interieur.gouv.fr\n2. Create account, enter visa information\n3. Upload: Passport photo page, visa sticker, proof of address\n4. Pay €225 timbre fiscal online\n5. Receive confirmation email\n\n**Important:**\n• Must validate within 3 months of arrival\n• Cannot leave Schengen zone until validated\n• Failure to validate = illegal stay\n\n**After Validation:**\n• Can apply for Carte Vitale\n• Open bank account more easily\n• Sign up for utilities",
            'keywords' => array('ofii', 'validation', 'validate visa', 'after arrival', 'timbre fiscal'),
            'sources' => array(array('name' => 'ANEF Portal', 'url' => 'https://administration-etrangers-en-france.interieur.gouv.fr'))
        )
    ),
    
    'property' => array(
        'overview' => array(
            'title' => 'Buying Property Overview',
            'content' => "**Property Purchase for Americans**\n\nUS citizens can freely purchase property in France with no restrictions!\n\n**The French Process:**\n1. **Find Property** - Through agents or direct listings\n2. **Make Offer** (Offre d'achat) - Written offer\n3. **Sign Preliminary Contract** (Compromis de vente) - 10-day cooling off period\n4. **Due Diligence** - 2-3 months for notaire to verify title\n5. **Final Signing** (Acte authentique) - At notaire's office\n\n**Costs:** Budget 7-10% on top of purchase price for fees.\n\n**Financing:** Non-residents can get French mortgages at 70-80% LTV.",
            'keywords' => array('buy property', 'buying property', 'real estate', 'house', 'purchase overview', 'how to buy'),
            'sources' => array(array('name' => 'Notaires de France', 'url' => 'https://www.notaires.fr'))
        ),
        'process' => array(
            'title' => 'Purchase Process Steps',
            'content' => "**Step-by-Step Property Purchase**\n\n**Step 1: Find a Property**\nUse SeLoger, LeBonCoin, Bien'ici, or work with local agent.\n\n**Step 2: Make an Offer (Offre d'achat)**\nWritten offer specifying price and conditions.\n\n**Step 3: Sign Compromis de Vente**\nPreliminary contract with 5-10% deposit. **10-day cooling-off period** - you can withdraw for ANY reason.\n\n**Step 4: Conditions Period (2-3 months)**\nSecure financing, notaire verifies title, review diagnostics.\n\n**Step 5: Sign Acte Authentique**\nFinal signing at notaire office. Pay balance + fees, receive keys.\n\n**Timeline:** 2-4 months from accepted offer to closing.",
            'keywords' => array('purchase process', 'buying process', 'steps', 'compromis', 'acte', 'closing', 'offer'),
            'sources' => array(array('name' => 'Notaires de France', 'url' => 'https://www.notaires.fr'))
        ),
        'mortgage' => array(
            'title' => 'French Mortgages',
            'content' => "**French Mortgages for Americans**\n\nNon-residents CAN obtain French mortgages.\n\n**Typical Terms:**\n• LTV: 70-80% maximum (vs 90-100% for residents)\n• Down payment: 20-30% required\n• Duration: 15-25 years\n• Rates: Generally fixed\n• Debt-to-income: Max 33-35%\n\n**Required Documents:**\n• Passport, proof of address\n• 3 years tax returns\n• 3 months bank statements\n• Employment/income proof\n• Property details\n\n**Key Considerations:**\n• Currency risk (loan in euros, income in dollars)\n• Early repayment penalties - check terms\n• Life insurance usually required\n\n**Banks:** BNP Paribas, Crédit Agricole, Société Générale, CIC, LCL\n\n**Tip:** Use a mortgage broker (courtier) specializing in non-residents.",
            'keywords' => array('mortgage', 'financing', 'loan', 'bank loan', 'french mortgage', 'home loan'),
            'sources' => array(array('name' => 'Banque de France', 'url' => 'https://www.banque-france.fr'))
        ),
        'notaire' => array(
            'title' => 'Role of the Notaire',
            'content' => "**Understanding the French Notaire**\n\nThe notaire is a public official handling property transactions - different from a US attorney.\n\n**Key Points:**\n• Represents the TRANSACTION, not buyer or seller\n• Neutral - ensures legal compliance for both parties\n• Fees set by government (not negotiable)\n\n**What Notaire Does:**\n• Drafts and registers contracts\n• Verifies ownership and title\n• Checks for liens, mortgages\n• Holds deposit in escrow\n• Registers sale with land registry\n• Collects taxes for government\n\n**Fees (Frais de notaire):**\n• Existing property: ~7-8%\n• New construction: ~2-3%\n\n**Can You Choose Your Own?** Yes! Buyer can appoint their own at no extra cost.",
            'keywords' => array('notaire', 'notary', 'lawyer', 'attorney', 'legal', 'fees'),
            'sources' => array(array('name' => 'Notaires de France', 'url' => 'https://www.notaires.fr'))
        ),
        'costs' => array(
            'title' => 'Purchase Costs & Fees',
            'content' => "**Total Costs When Buying**\n\n**Notaire Fees - 7-10%**\n• Registration taxes: ~5.8%\n• Land registry: ~0.1%\n• Notaire's fee: ~1%\n• Administrative: ~0.5%\n\nNew construction: Only 2-3%\n\n**Agent Fees - 3-8%**\nUsually included in listed price (FAI).\n\n**Mortgage Costs (if financing)**\n• Application: €500-2,000\n• Mortgage notaire fees: ~1%\n• Valuation: €200-500\n\n**Example for €300,000 Property:**\n• Purchase: €300,000\n• Notaire fees (~8%): €24,000\n• Total: ~€324,000+",
            'keywords' => array('costs', 'fees', 'expenses', 'how much', 'budget', 'closing costs'),
            'sources' => array(array('name' => 'Notaires de France', 'url' => 'https://www.notaires.fr'))
        )
    ),
    
    'healthcare' => array(
        'overview' => array(
            'title' => 'Healthcare Overview',
            'content' => "**French Healthcare for Americans**\n\nFrance has excellent healthcare. As a legal resident, you can access it.\n\n**The System:**\n• **PUMA**: Universal healthcare for all legal residents\n• **Carte Vitale**: Health card, covers ~70% of costs\n• **Mutuelle**: Supplemental insurance for remaining 30%\n\n**Timeline:**\n1. Months 0-3: Use private insurance (required for visa)\n2. Month 3+: Apply for PUMA/Carte Vitale\n3. Month 6-12: Receive Carte Vitale, get mutuelle\n\n**What's Covered:** Doctor visits, hospital, prescriptions, labs.\n\n**What You Pay:**\n• Carte Vitale only: ~30%\n• Carte Vitale + mutuelle: 0-10%",
            'keywords' => array('healthcare', 'health insurance', 'medical', 'doctor', 'hospital', 'overview'),
            'sources' => array(array('name' => 'Ameli.fr', 'url' => 'https://www.ameli.fr'))
        ),
        'puma' => array(
            'title' => 'PUMA & Carte Vitale',
            'content' => "**Enrolling in French Healthcare (PUMA)**\n\n**Eligibility:**\n• Legal residence in France (valid visa)\n• Stable residence (3+ months)\n\n**How to Apply:**\n1. After 3 months, visit local CPAM or apply on ameli.fr\n2. Submit: Passport with visa, proof of address, birth certificate, bank details (RIB)\n\n**Carte Vitale:**\n• Plastic health insurance card\n• Present at doctors, pharmacies, hospitals\n• Auto-processes reimbursement\n• Takes 2-4 months to receive\n\n**Before Receiving Card:**\nPay upfront, submit paper forms for reimbursement.\n\n**Cost:** PUMA is free if earning below ~€25,000/year.",
            'keywords' => array('puma', 'carte vitale', 'cpam', 'enroll', 'register', 'health card', 'social security'),
            'sources' => array(array('name' => 'Ameli.fr', 'url' => 'https://www.ameli.fr'))
        ),
        'mutuelle' => array(
            'title' => 'Mutuelle (Supplemental)',
            'content' => "**Mutuelle - Supplemental Insurance**\n\nCovers the ~30% not covered by Carte Vitale.\n\n**What It Covers:**\n• The 30% co-pay\n• Better dental and vision\n• Private hospital rooms\n\n**Typical Costs:**\n• Individual: €30-100/month\n• Couple: €60-150/month\n• Family: €100-200/month\n\n**Providers:**\n• MGEN, Harmonie Mutuelle\n• April International (English-speaking)\n• Allianz\n\n**Recommended:** Not required but highly advised. Without it, you pay 30% of all costs.",
            'keywords' => array('mutuelle', 'supplemental insurance', 'private insurance', 'additional coverage'),
            'sources' => array(array('name' => 'Ameli.fr', 'url' => 'https://www.ameli.fr'))
        )
    ),
    
    'taxes' => array(
        'overview' => array(
            'title' => 'Tax Obligations Overview',
            'content' => "**Taxes for Americans in France**\n\nYou may have obligations in BOTH countries.\n\n**French Taxes (if French tax resident):**\n• Income tax on worldwide income\n• Property taxes\n• Wealth tax (real estate over €1.3M)\n\n**US Taxes (always, as US citizen):**\n• Must file US return regardless of residence\n• Foreign Earned Income Exclusion may help\n• Foreign Tax Credit prevents double taxation\n• FBAR/FATCA reporting for foreign accounts\n\n**US-France Tax Treaty:** Prevents double taxation.\n\n**Recommendation:** Use a cross-border tax advisor familiar with both systems.",
            'keywords' => array('taxes', 'tax', 'tax obligations', 'us taxes', 'french taxes', 'overview'),
            'sources' => array(array('name' => 'Impots.gouv.fr', 'url' => 'https://www.impots.gouv.fr'))
        ),
        'residency' => array(
            'title' => '183-Day Rule',
            'content' => "**French Tax Residency - 183-Day Rule**\n\n**You're a French Tax Resident if ANY apply:**\n1. 183+ days in France during calendar year\n2. Principal home is in France\n3. Primary work is in France\n4. Center of economic interests is in France\n\n**Counting Days:**\n• Any part of day counts as full day\n• January 1 - December 31\n• Don't need to be consecutive\n\n**Consequences:**\n• Declare worldwide income to France\n• Pay French taxes on global income\n• Still file US taxes (as citizen)\n• Use tax treaty to avoid double taxation\n\n**Track your days with the Day Counter tool!**",
            'keywords' => array('183 day rule', 'tax residency', 'days in france', 'tax resident', 'counting days'),
            'sources' => array(array('name' => 'Impots.gouv.fr', 'url' => 'https://www.impots.gouv.fr'))
        ),
        'fbar' => array(
            'title' => 'FBAR & FATCA',
            'content' => "**US Reporting for Foreign Accounts**\n\n**FBAR (FinCEN 114)**\nRequired if foreign accounts exceed $10,000 total.\n• Report all bank and investment accounts\n• Deadline: April 15 (auto-extended to Oct 15)\n• File electronically at FinCEN BSA E-Filing\n• Penalties: Up to $12,500+ per violation\n\n**FATCA (Form 8938)**\nRequired if foreign assets exceed:\n• Living abroad: $200,000 end of year\n• Filed with tax return\n• Penalties: $10,000 for failure to file\n\n**Important:** French banks report US citizens' accounts to IRS. File properly.",
            'keywords' => array('fbar', 'fatca', 'foreign accounts', 'reporting', 'bank accounts'),
            'sources' => array(array('name' => 'IRS', 'url' => 'https://www.irs.gov'))
        )
    ),
    
    'driving' => array(
        'overview' => array(
            'title' => 'Driving Overview',
            'content' => "**Driving in France as an American**\n\n**Short-Term (under 1 year):**\n• US license valid\n• International Driving Permit recommended\n\n**Long-Term (residents):**\n• Must exchange license OR take French test\n• Exchange only from certain US states\n• Must do this within first year\n\n**Key Rules:**\n• Drive on the RIGHT\n• Priority to the right at unmarked intersections\n• Speed in km/h\n• Strict drink-driving laws (0.05% limit)",
            'keywords' => array('driving', 'license', 'car', 'drive in france', 'overview'),
            'sources' => array(array('name' => 'Service-Public.fr', 'url' => 'https://www.service-public.fr'))
        ),
        'exchange' => array(
            'title' => 'License Exchange',
            'content' => "**Exchanging US License for French License**\n\nOnly from states with reciprocal agreements:\n\n**Eligible States:**\nArkansas, Colorado, Connecticut, Delaware, Florida, Illinois, Iowa, Kansas, Kentucky, Maryland, Massachusetts, Michigan, New Hampshire, Ohio, Oklahoma, Pennsylvania, South Carolina, Texas, Virginia, Wisconsin\n\n**If Eligible:**\n• Apply within first year\n• Submit: License, residence proof, passport, photos\n• Processing: 2-6 months\n• Fee: ~€25\n\n**If NOT Eligible:**\n• Must pass French driving test\n• Written + practical exam\n• Cost: €1,500-2,500 with driving school\n\n**Important:** After 1 year of residency, US license no longer valid in France.",
            'keywords' => array('license exchange', 'exchange license', 'which states', 'eligible states', 'convert license'),
            'sources' => array(array('name' => 'Service-Public.fr', 'url' => 'https://www.service-public.fr'))
        )
    ),
    
    'shipping' => array(
        'overview' => array(
            'title' => 'Shipping Overview',
            'content' => "**Moving Belongings to France**\n\n**Options:**\n• Full container (FCL): Your own container\n• Shared container (LCL): Share space, cheaper\n• Air freight: Fast but expensive\n\n**Duty-Free Import:**\nAs new resident, import household goods duty-free if:\n• Items owned 6+ months\n• Lived outside EU 12+ months\n• Items for personal use\n\n**Timeline:**\n• Sea freight: 4-8 weeks\n• Air freight: 1-2 weeks\n\n**Cost Estimates:**\n• 20ft container: $3,000-6,000\n• Shared container: $1,500-3,000",
            'keywords' => array('shipping', 'moving', 'belongings', 'customs', 'import', 'container'),
            'sources' => array(array('name' => 'French Customs', 'url' => 'https://www.douane.gouv.fr'))
        ),
        'pets' => array(
            'title' => 'Moving Pets',
            'content' => "**Bringing Pets to France**\n\n**Requirements:**\n1. **Microchip** - ISO 15-digit, implanted BEFORE rabies vaccine\n2. **Rabies Vaccination** - After microchip, 21+ days before travel\n3. **EU Health Certificate** - From USDA-accredited vet, within 10 days, endorsed by USDA APHIS\n\n**Process:**\n1. Get microchip\n2. Get rabies vaccine\n3. Wait 21 days\n4. Get health certificate from vet\n5. Send to USDA for endorsement\n6. Travel\n\n**No quarantine** if documents are correct!",
            'keywords' => array('pets', 'dog', 'cat', 'animals', 'pet travel', 'rabies', 'microchip'),
            'sources' => array(array('name' => 'USDA APHIS', 'url' => 'https://www.aphis.usda.gov'))
        )
    ),
    
    'banking' => array(
        'overview' => array(
            'title' => 'Banking Overview',
            'content' => "**French Banking for Americans**\n\nOpening a French account is essential but can be challenging due to FATCA.\n\n**Why You Need One:**\n• Required for utilities, phone contracts\n• Receive salary, pay rent\n• Lower transaction fees\n\n**Banks That Accept Americans:**\n• BNP Paribas (most US-friendly)\n• Crédit Agricole\n• Société Générale\n• Boursorama (online)\n\n**Required Documents:**\n• Passport, visa\n• Proof of French address\n• Proof of income\n• US SSN, W-9 form\n\n**Tips:**\n• Start with bank giving your mortgage\n• Be upfront about US citizenship\n• Bring extra documents",
            'keywords' => array('bank', 'banking', 'bank account', 'open account', 'french bank'),
            'sources' => array(array('name' => 'Banque de France', 'url' => 'https://www.banque-france.fr'))
        )
    ),
    
    'settling' => array(
        'overview' => array(
            'title' => 'Settling In',
            'content' => "**Getting Established in France**\n\n**First Week:**\n• Get French phone number\n• Set up internet\n• Find local shops, pharmacy\n\n**First Month:**\n• Open bank account\n• Validate visa online (OFII)\n• Register at mairie if required\n\n**First 3 Months:**\n• Apply for Carte Vitale\n• Exchange driver's license\n• Start French lessons\n\n**Useful Apps:** Doctolib (doctors), Améli (health), SNCF Connect (trains)\n\n**Tips:**\n• Learn basic French - \"Bonjour\" and \"Merci\" matter\n• Patience with bureaucracy is essential",
            'keywords' => array('settling', 'getting started', 'first steps', 'utilities', 'establish'),
            'sources' => array(array('name' => 'Service-Public.fr', 'url' => 'https://www.service-public.fr'))
        )
    )
);
