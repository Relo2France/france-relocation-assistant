<?php
/**
 * How to do each generated step.
 *
 * A task title says what; this says how: the order to do it in, where to
 * go with the official link, what to bring, how long it takes and what it
 * costs. It is attached to a task's metadata when the task is generated and
 * shown in the task panel. Facts come from the knowledge base topics as
 * verified September 2026; anything state-specific points at the official
 * directory for that state rather than guessing the office.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Portal
 * @since       2.9.13
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FRAMT_Task_Howto {

    /**
     * The how-to for a template title, with the member's own words filled in.
     *
     * @param string $title Task title.
     * @param array  $ctx   state (name), state_code, licence_exchange, licence_classes, spouse, verified.
     * @return array|null steps[], bring[], time, cost, links[] or null when there is none.
     */
    public static function for_title( $title, $ctx = array() ) {
        $ctx   = wp_parse_args( $ctx, array( 'state' => 'your state', 'state_code' => '', 'licence_exchange' => 'unknown', 'licence_classes' => '', 'spouse' => 'your spouse', 'verified' => 'September 2026' ) );
        $all   = self::all();
        $howto = null;
        if ( isset( $all[ $title ] ) ) {
            $howto = $all[ $title ];
        } elseif ( 0 === strpos( $title, 'Exchange your ' ) && false !== strpos( $title, 'driving licence through ANTS' ) ) {
            $howto = $all['__licence_exchange'];
        } elseif ( 0 === strpos( $title, 'Ask ' ) && false !== strpos( $title, 'for the consulate paperwork' ) ) {
            $howto = $all['__employer_paperwork'];
        }
        if ( null === $howto ) {
            return null;
        }
        $fill = function ( $s ) use ( $ctx ) {
            return str_replace( array( '{state}', '{spouse}', '{verified}', '{classes}' ), array( $ctx['state'], $ctx['spouse'], $ctx['verified'], $ctx['licence_classes'] ), $s );
        };
        $out = array( 'steps' => array(), 'bring' => array(), 'time' => '', 'cost' => '', 'links' => array() );
        // State-specific facts, once the knowledge base holds them, lead the how-to.
        $state_step = self::state_step( $title, $ctx );
        if ( $state_step ) {
            $out['steps'][] = $state_step;
        }
        foreach ( $howto['steps'] ?? array() as $step ) {
            $out['steps'][] = array(
                'title'  => $fill( $step[0] ),
                'detail' => $fill( $step[1] ),
                'url'    => $step[2] ?? '',
            );
        }
        foreach ( $howto['bring'] ?? array() as $b ) {
            $out['bring'][] = $fill( $b );
        }
        $out['time'] = $fill( $howto['time'] ?? '' );
        $out['cost'] = $fill( $howto['cost'] ?? '' );
        foreach ( $howto['links'] ?? array() as $l ) {
            $out['links'][] = array( 'label' => $fill( $l[0] ), 'url' => $l[1] );
        }
        return $out;
    }

    /**
     * A first step written from the member's state row in the knowledge base,
     * for the steps that vary by state. Null until the row exists.
     *
     * @param string $title Task title.
     * @param array  $ctx   Context with apostille / vital_records / tax_domicile rows.
     * @return array|null
     */
    private static function state_step( $title, $ctx ) {
        $row = null; $lead = '';
        if ( in_array( $title, array( 'Get the apostilles from the state', 'Get marriage certificate apostilled', 'Get birth certificates apostilled', 'Apostille the marriage certificate' ), true ) ) {
            $row = $ctx['apostille'] ?? null; $lead = 'Apostilles in ' . $ctx['state'];
        } elseif ( 'Order certified copies of your civil records' === $title ) {
            $row = $ctx['vital_records'] ?? null; $lead = 'Certified copies in ' . $ctx['state'];
        } elseif ( 'Talk to a cross-border tax professional before you move' === $title ) {
            $row = $ctx['tax_domicile'] ?? null; $lead = 'Leaving ' . $ctx['state'] . ' for tax purposes';
        }
        if ( ! is_array( $row ) || empty( $row ) ) {
            return null;
        }
        $parts = array();
        foreach ( $row as $k => $v ) {
            if ( in_array( $k, array( 'state', 'url' ), true ) || '' === $v ) {
                continue;
            }
            $parts[] = ucfirst( str_replace( '_', ' ', $k ) ) . ': ' . $v;
        }
        return array( 'title' => $lead, 'detail' => implode( '. ', $parts ) . '.', 'url' => $row['url'] ?? '' );
    }

    /**
     * Titles that have a how-to.
     *
     * @return string[]
     */
    public static function titles() {
        return array_keys( self::all() );
    }

    /**
     * Every how-to, keyed by task title. Steps are [title, detail, url].
     *
     * @return array
     */
    private static function all() {
        $fv   = 'https://france-visas.gouv.fr/';
        $tls  = 'https://visas-fr.tlscontact.com/';
        $anef = 'https://administration-etrangers-en-france.interieur.gouv.fr/';
        $nass = 'https://www.nass.org/business-services/apostillesdocument-authentication-services';
        $cdc  = 'https://www.cdc.gov/nchs/w2w/index.htm';
        $fbi  = 'https://www.fbi.gov/how-we-can-help-you/more-fbi-services-and-information/identity-history-summary-checks';
        $chan = 'https://www.fbi.gov/how-we-can-help-you/more-fbi-services-and-information/identity-history-summary-checks/list-of-fbi-approved-channelers';
        $dos  = 'https://travel.state.gov/content/travel/en/records-and-authentications/authenticate-your-document/apostille-requirements.html';
        $sp   = 'https://www.service-public.gouv.fr/';
        $ants = 'https://permisdeconduire.ants.gouv.fr/';
        $ameli = 'https://www.ameli.fr/';

        return array(

            // ---------------- Prepare: the dossier ----------------
            'Order certified copies of your civil records' => array(
                'steps' => array(
                    array( 'Find the office that holds each record', 'Birth certificates come from the vital records office of the state where you were born; marriage certificates from the state or, in some states, the county where the licence was issued. The CDC keeps the directory for every state, with addresses, fees and whether you can order online.', $cdc ),
                    array( 'Order the long-form certified copy', 'Ask for the certified, long-form (sometimes "vault" or "full") copy with the raised or embossed seal, not the short abstract or a wallet card. Apostille offices refuse photocopies and notarised copies; consulates like a copy issued within the last six months.' ),
                    array( 'Order two of each', 'One goes off for the apostille and stays in the French file for years; the second is your spare. Ordering later from France is slow.' ),
                    array( 'Check the copy when it arrives', 'Names spelled exactly as in the passport, the seal present, the registrar\'s signature legible. A record with a discrepancy is the one that gets refused in France.' ),
                ),
                'bring' => array( 'Government ID', 'Payment card', 'Parents\' names and the place and date of the event, for the order form' ),
                'time'  => 'A few days to four weeks, depending on the state and whether you order online or by post.',
                'cost'  => 'Usually $10 to $35 per copy, plus a processing fee for online orders.',
                'links' => array( array( 'Where to write for vital records (CDC)', $cdc ) ),
            ),

            'Get the apostilles from the state' => array(
                'steps' => array(
                    array( 'Find the apostille office for {state}', 'Each state\'s Secretary of State (or equivalent) apostilles the records that state issued. The National Association of Secretaries of State keeps the directory: office, fee, mail or walk-in, current turnaround.', $nass ),
                    array( 'Check whether a county step comes first', 'In a few states a record signed by a county or city registrar must be certified by the county clerk before the state will apostille it. The state\'s apostille page says so; the CDC directory notes it for vital records.', $cdc ),
                    array( 'Send or bring the certified copy with the request form', 'The original certified copy, the state\'s apostille request form naming France as the destination country, the fee, and a prepaid return envelope if by mail. Never send a photocopy.' ),
                    array( 'Keep the apostille attached', 'The apostille is a stapled or glued page. Do not remove it, and do not laminate anything. Scan the whole thing front and back once it comes home; the scan is what the translator and, later, French offices work from.' ),
                ),
                'bring' => array( 'The certified copy of each record', 'The state\'s request form', 'Fee (card or money order)', 'Prepaid return envelope with tracking' ),
                'time'  => 'One to three weeks in most states; some offer same-day counter service, others take four or more by mail.',
                'cost'  => 'Roughly $5 to $40 per document, set by the state.',
                'links' => array( array( 'Apostille offices by state (NASS)', $nass ), array( 'Where to write for vital records (CDC)', $cdc ) ),
            ),

            'Request your FBI background check' => array(
                'steps' => array(
                    array( 'Pick an FBI-approved channeler', 'A channeler takes your fingerprints electronically and returns the Identity History Summary in days; the FBI\'s own postal route takes ten to fourteen weeks. The FBI publishes the approved list.', $chan ),
                    array( 'Get fingerprinted and submit', 'Book with the channeler, bring government ID, and ask for the result on FBI letterhead as a PDF and on paper. Some channelers will also arrange the apostille; ask.' ),
                    array( 'Apostille it at the US Department of State', 'A federal document is apostilled by the Office of Authentications in Washington, not by your state. Mail the FBI result with form DS-4194 and the fee, or use the channeler\'s apostille service.', $dos ),
                    array( 'Watch the six-month clock', 'Consulates want the check issued within six months of your appointment. Count back from the appointment date and order accordingly; too early and you do it twice.' ),
                ),
                'bring' => array( 'Government photo ID', 'Fingerprints (taken by the channeler)', 'Form DS-4194 for the apostille' ),
                'time'  => 'Days for the check through a channeler; the federal apostille adds several weeks by mail.',
                'cost'  => '$18 FBI fee plus the channeler\'s fee, and $20 per document for the federal apostille.',
                'links' => array( array( 'Identity History Summary (FBI)', $fbi ), array( 'Approved channelers', $chan ), array( 'Federal apostille (US Department of State)', $dos ) ),
            ),

            'Buy health insurance for the whole first year' => array(
                'steps' => array(
                    array( 'Know what the consulate is checking', 'Cover for the full visa period, at least €30,000 for medical costs, repatriation included, no exclusion for pre-existing conditions, valid in France. Ordinary travel insurance and US domestic plans are refused.' ),
                    array( 'Compare expatriate policies', 'Insurers who sell "visa long séjour" or "expatriate" policies issue an attestation written for French consulates, in French or English, with the dates and the amounts on it. That letter is the document; the policy booklet is not.' ),
                    array( 'Buy for the visa dates, cancellable', 'Cover should start on the day the visa starts. Once French health cover opens after three months of residence you can cut it back or cancel, so look for a policy that allows it.' ),
                    array( 'Print the attestation', 'One per applicant, each named. It goes in the dossier and travels with you.' ),
                ),
                'bring' => array( 'Passport details for every applicant', 'The visa dates' ),
                'time'  => 'A day or two to arrange; the letter is usually immediate.',
                'cost'  => 'Varies with age and cover; budget from a few hundred to a few thousand dollars a year.',
                'links' => array(),
            ),

            'Line up where you will live for the first months' => array(
                'steps' => array(
                    array( 'Choose the kind of proof', 'A signed lease, a deed, a hotel or rental booking for the first weeks, or an attestation d\'hébergement from a host in France with a copy of their ID and a recent utility bill.' ),
                    array( 'Check what your consulate accepts', 'The France-Visas wizard lists the accepted proofs for your route; most US consulates accept a booking covering the first weeks.', $fv ),
                    array( 'Get it in writing, dated', 'A booking confirmation with your name, the address and the dates; a lease signed by both sides; a host letter signed and dated.' ),
                ),
                'bring' => array( 'The booking, lease or host attestation with the host\'s ID and utility bill' ),
                'time'  => 'A day for a booking; weeks for a lease at a distance.',
                'cost'  => 'The booking itself.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Get passport photos taken (35 x 45 mm)' => array(
                'steps' => array(
                    array( 'Ask for French visa photos, not US passport photos', 'French specification: 35 x 45 mm, taken within six months, plain light grey or white background, neutral expression, no glasses glare. A US 2 x 2 inch photo is the wrong size and gets rejected at the counter.' ),
                    array( 'Get several', 'Two go in the dossier; keep spares for the validation and the residence card later. TLScontact centres sell them too, for a fee.' ),
                ),
                'bring' => array(),
                'time'  => 'Minutes.',
                'cost'  => 'A few dollars a set.',
                'links' => array(),
            ),

            'Write the cover letter' => array(
                'steps' => array(
                    array( 'Say who you are and why France', 'One page, dated and signed. Where you will live, for how long, how you support yourself, and what you will do with your time. For a couple, each writes their own or both sign one.' ),
                    array( 'Match the file', 'Every figure and date in the letter must agree with the documents behind it: the same address as the accommodation proof, the same income as the statements.' ),
                    array( 'Let the portal draft it', 'Documents has a draft written for your route from your profile. Check every line, print it, sign and date it.' ),
                    array( 'French or English', 'US consulates accept English. A short French version alongside is a courtesy, not a requirement.' ),
                ),
                'bring' => array(),
                'time'  => 'An evening.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Pull three months of bank statements' => array(
                'steps' => array(
                    array( 'Download official statements for the last three months', 'From each account you are relying on: checking, savings, investment. Official PDFs with the bank\'s name, your name and the account number, not screenshots.' ),
                    array( 'Add the proof of income', 'Pay slips or an employer letter, the Social Security award letter, pension statements, a rental income ledger: whatever shows the money keeps coming.' ),
                    array( 'Date it to the appointment', 'Statements older than three months get asked for again. Pull them in the last two weeks before the appointment and add a fresh balance printout on the day.' ),
                ),
                'bring' => array( 'Statements from every account you rely on', 'Proof of income' ),
                'time'  => 'An hour, once.',
                'cost'  => 'Nothing; some banks charge for stamped copies.',
                'links' => array(),
            ),

            'Prepare proof of financial resources' => array(
                'steps' => array(
                    array( 'Know the benchmark', 'Consulates measure a visitor against the French net minimum wage, about €1,478 a month in 2026, per adult. Many US applicants show one and a half to two times that in monthly income, or a year\'s worth in savings, so nobody has to ask.' ),
                    array( 'Assemble the proof', 'Three months of statements for each account, investment balances, pension or Social Security letters, rental income, an employer letter if the income is a salary earned abroad.' ),
                    array( 'Write a one-page summary', 'A simple table: source, monthly amount, where the proof is. Officers read hundreds of files; a summary that matches the statements gets read.' ),
                ),
                'bring' => array( 'Statements', 'Income letters', 'The summary sheet' ),
                'time'  => 'An afternoon.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Document pension income' => array(
                'steps' => array(
                    array( 'Get the award letters', 'Your Social Security benefit verification letter comes from your my Social Security account online; pension plans issue an annual statement or a letter on request.', 'https://www.ssa.gov/myaccount/' ),
                    array( 'Show the retirement accounts', 'Year-end statements for IRAs and 401(k)s and last year\'s distributions (Form 1099-R). Balances count as savings; distributions count as income.' ),
                    array( 'Convert to euros once', 'A one-line note with the exchange rate and date you used. The consulate benchmarks in euros, about €1,478 a month in 2026 per adult.' ),
                ),
                'bring' => array( 'Social Security letter', 'Pension statements', 'IRA / 401(k) statements and 1099-R' ),
                'time'  => 'An afternoon; the Social Security letter is immediate online.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'my Social Security', 'https://www.ssa.gov/myaccount/' ) ),
            ),

            'Sign declaration not to work' => array(
                'steps' => array(
                    array( 'Write the attestation sur l\'honneur', 'A short signed statement: "I, [name], born [date], declare on my honour that I will not carry out any professional activity in France during my stay." Dated and signed by each applicant. France-Visas gives the wording for the visitor route.', $fv ),
                    array( 'Understand what it covers', 'Working for a French employer or French clients is out. On remote work for a US employer, the Interior Ministry indicated in a written answer on 23 June 2026 that it may be compatible when the work has no link to the French market; consulates apply this unevenly. Read the visitor guide before you sign, and if it applies to you, say so plainly in the cover letter. The letters in Documents include a remote-work version and an employer letter.' ),
                ),
                'bring' => array(),
                'time'  => 'Ten minutes.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Decide how your pension and Social Security get paid in France' => array(
                'steps' => array(
                    array( 'Tell Social Security you are moving', 'Benefits are paid abroad to US citizens. You can keep a US bank account or have payments sent to a French account through the international direct deposit programme; report the change of address on my Social Security or through the Federal Benefits Unit at the US Embassy in Paris.', 'https://www.ssa.gov/foreign/' ),
                    array( 'Keep at least one US account open', 'Pensions, IRA custodians and the IRS all want a US account; some US banks close accounts with a foreign address, so tell yours and ask before you go.' ),
                    array( 'Plan the transfers', 'A specialist transfer service or a bank with euro accounts moves money more cheaply than a wire each month. Set it up before the first French rent is due.' ),
                ),
                'bring' => array(),
                'time'  => 'A few phone calls.',
                'cost'  => 'Transfer fees vary; compare.',
                'links' => array( array( 'Social Security payments outside the US', 'https://www.ssa.gov/foreign/' ) ),
            ),

            // ---------------- Apply ----------------
            'Complete the France-Visas application online' => array(
                'steps' => array(
                    array( 'Create the account and run the wizard', 'On france-visas.gouv.fr, answer with your real nationality, country of residence, purpose of stay and length. The wizard tells you the visa category and produces the document list for your route.', $fv ),
                    array( 'Fill in the long-stay form', 'One application per adult; children are separate applications too. Save as you go. Spelling of names and passport numbers exactly as in the passport.' ),
                    array( 'Print, sign, keep the receipt', 'The signed form and the receipt page with the France-Visas reference number are the first two things checked at the appointment.' ),
                ),
                'bring' => array( 'Passport', 'Details of your accommodation in France', 'Your route\'s document list' ),
                'time'  => 'An hour per applicant.',
                'cost'  => 'Nothing at this stage.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Book the TLScontact appointment' => array(
                'steps' => array(
                    array( 'Create the TLScontact account', 'TLScontact has run French visa intake in the US since April 2025. Link your France-Visas reference to the account.', $tls ),
                    array( 'Pick any of the ten centres', 'New York, Boston, Washington, Atlanta, Miami, Chicago, Houston, Los Angeles, San Francisco or Seattle; you are not tied to the one nearest you. Slots open a few weeks ahead and go fast from May to August.' ),
                    array( 'Book everyone on the same day', 'A couple or a family books consecutive slots so the files travel together.' ),
                    array( 'Pay the service fee online', 'About €220 per long-stay applicant, charged in dollars, non-refundable. Optional extras (priority slot, courier return) change comfort, not the decision.' ),
                ),
                'bring' => array( 'France-Visas reference number', 'Passport details', 'Payment card' ),
                'time'  => 'Twenty minutes; the wait for a slot is what takes time.',
                'cost'  => 'About €220 service fee per long-stay applicant.',
                'links' => array( array( 'TLScontact France', $tls ) ),
            ),

            'Attend the appointment: dossier, biometrics, fee' => array(
                'steps' => array(
                    array( 'Order the file the way the list reads', 'Follow the France-Visas document list top to bottom: originals in one pile, one photocopy of each in another. Missing pieces mean the file goes home and you rebook.' ),
                    array( 'Arrive early with everyone applying', 'Each applicant appears in person, children included. Fingerprints and a photo are taken for everyone over twelve.' ),
                    array( 'Pay the visa fee', '€99 for most long-stay categories, charged in dollars; the student rate is lower and spouses of French nationals pay nothing. Non-refundable whatever the decision.' ),
                    array( 'Hand over the passport', 'It leaves with the file. Ask for the receipt and the tracking reference; nothing that needs the passport can be booked until it is back.' ),
                ),
                'bring' => array( 'Passport', 'Signed France-Visas form and receipt', 'Two photos 35 x 45 mm', 'Every document on your list, original plus one copy', 'Payment card' ),
                'time'  => 'About an hour at the centre.',
                'cost'  => '€99 visa fee (reduced for students, none for spouses of French citizens).',
                'links' => array( array( 'TLScontact France', $tls ), array( 'France-Visas', $fv ) ),
            ),

            'Collect your passport and check the visa' => array(
                'steps' => array(
                    array( 'Track the file', 'The TLScontact account shows when the passport is back from the consulate. Two to six weeks is typical; longer in summer or when extra checks are made.' ),
                    array( 'Read the sticker before you leave', 'Your name spelled correctly, the dates covering your move, the number of entries, and the mention: "VLS-TS" means you validate online after arrival; "carte de séjour à solliciter" means a prefecture appointment within two months instead.' ),
                    array( 'Copy it', 'Scan the visa page and the passport photo page; the scans go into the validation and every French office afterwards.' ),
                ),
                'bring' => array( 'The appointment receipt', 'ID' ),
                'time'  => 'Two to six weeks after the appointment.',
                'cost'  => 'Courier return if chosen.',
                'links' => array( array( 'TLScontact France', $tls ) ),
            ),

            'File no earlier than three months before your arrival date' => array(
                'steps' => array(
                    array( 'Count back three months from your arrival date', 'The entrepreneur / profession libérale application is not accepted earlier. Have the whole dossier finished before that day.' ),
                    array( 'Book on the day the window opens', 'Complete France-Visas and book TLScontact the same day; slots go fast and the decision can take four to eight weeks.' ),
                ),
                'bring' => array(),
                'time'  => 'Timing only.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            // ---------------- Move ----------------
            'Arrange shipping, or sell what stays' => array(
                'steps' => array(
                    array( 'Decide what crosses the ocean', 'Container shipping is priced by volume and takes six to ten weeks door to door; air freight for a few boxes takes days and costs more per kilo. Most people ship less than they planned.' ),
                    array( 'Get three quotes from international movers', 'Ask for door-to-door including French customs clearance. An inventory in English and French, valued, is required for customs.' ),
                    array( 'Claim the duty-free household move', 'Used personal goods owned for six months or more enter France duty-free when you transfer residence, on presentation of the visa, proof of the old and new addresses and the signed inventory. The mover prepares the customs form.' ),
                    array( 'Sell or store the rest', 'What stays should be sold, given or in storage before the last month; the last month is for the file, not the garage.' ),
                ),
                'bring' => array( 'Signed, valued inventory', 'Visa and passport copies', 'Proof of the US and French addresses' ),
                'time'  => 'Quotes in a week; shipping six to ten weeks by sea.',
                'cost'  => 'Thousands of dollars for a container; hundreds for boxes by air.',
                'links' => array(),
            ),

            'Book flights for on or after the visa start date' => array(
                'steps' => array(
                    array( 'Land on or after the visa start date', 'A day earlier and you enter as a tourist, and the 90-day validation clock is tied to the entry stamp of the correct entry.' ),
                    array( 'Fly direct into France if you can', 'A connection through another Schengen airport means the entry stamp is from that country; it is allowed, but keep the boarding passes to prove the French arrival date.' ),
                    array( 'Keep everything', 'Boarding passes and the e-ticket prove your arrival date for validation if the passport is not stamped.' ),
                ),
                'bring' => array(),
                'time'  => 'An hour.',
                'cost'  => 'The fares.',
                'links' => array(),
            ),

            'Book temporary accommodation' => array(
                'steps' => array(
                    array( 'Book the first four to six weeks', 'Long enough to find a home and receive post. A furnished rental with a proper address (not a hotel room) makes the bank and the validation easier.' ),
                    array( 'Get an address you can use', 'The validation, the bank and the SIM all want a proof of address: the rental agreement or a host\'s attestation plus their utility bill.' ),
                ),
                'bring' => array(),
                'time'  => 'An evening.',
                'cost'  => 'The rental.',
                'links' => array(),
            ),

            'Tidy the US side: mail, prescriptions, bank, phone' => array(
                'steps' => array(
                    array( 'Forward the mail', 'USPS forwards to a US address, not abroad; use a family member or a mail-scanning service and file the change of address online.', 'https://www.usps.com/manage/forward.htm' ),
                    array( 'Fill three months of prescriptions', 'Carry them in original packaging with the prescription; French pharmacies need a French prescription to refill, so book a doctor early after arrival.' ),
                    array( 'Tell the bank and the card issuers', 'Travel notices, a US address on file, and online banking that works without a US phone.' ),
                    array( 'Keep a US number alive', 'Two-factor codes from US banks, Social Security and the IRS go to a US number. A cheap plan or a number-parking service keeps it.' ),
                ),
                'bring' => array(),
                'time'  => 'A day of calls.',
                'cost'  => 'Small monthly fees for mail and number services.',
                'links' => array( array( 'USPS change of address', 'https://www.usps.com/manage/forward.htm' ) ),
            ),

            'Pack the originals in hand luggage' => array(
                'steps' => array(
                    array( 'One folder, in the cabin', 'Passport with the visa, every apostilled original, the insurance attestation, the accommodation proof, the pet certificate, and a copy of the France-Visas file. Nothing in the hold.' ),
                    array( 'Scans in the cloud too', 'Every page scanned and reachable from your phone; French offices accept uploads and you will be asked for the same documents again and again.' ),
                ),
                'bring' => array(),
                'time'  => 'An hour.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            // ---------------- Arrive ----------------
            'Get a French SIM' => array(
                'steps' => array(
                    array( 'Buy a prepaid SIM in the first days', 'Any carrier shop or supermarket; passport as ID. Move to a contract once you have a French bank account and address.' ),
                    array( 'Use the French number everywhere', 'Bank, ANEF, ameli, the prefecture: every French account sends its codes to a French number.' ),
                ),
                'bring' => array( 'Passport' ),
                'time'  => 'Half an hour.',
                'cost'  => 'A few euros for the SIM; €10 to €30 a month.',
                'links' => array(),
            ),

            'Validate your visa online within 90 days' => array(
                'steps' => array(
                    array( 'Go to the ANEF portal', 'Choose "I validate my VLS-TS". Create an account with your email (activate the link within 24 hours) or sign in with FranceConnect.', $anef ),
                    array( 'Enter the visa and the arrival', 'Visa number, the date you entered France (the entry stamp, not the visa date) and your French address.' ),
                    array( 'Upload the proofs', 'Passport photo page, the visa sticker, and a proof of address in France (lease, rental agreement, or a host attestation with their bill).' ),
                    array( 'Pay the validation tax', 'By card on the portal, or buy the timbre fiscal at timbres.impots.gouv.fr and enter the code the same day. €300 for most categories since May 2026, €150 for students; confirm the amount shown.', 'https://timbres.impots.gouv.fr/' ),
                    array( 'Save the confirmation', 'The email and the PDF "confirmation de validation" are your proof of status for the year; the bank, CPAM and the prefecture ask for it.' ),
                ),
                'bring' => array( 'Passport and visa scans', 'Proof of address', 'Payment card' ),
                'time'  => 'Half an hour, within 90 days of entry.',
                'cost'  => '€300 (standard) or €150 (student) validation tax, as of May 2026.',
                'links' => array( array( 'ANEF portal', $anef ), array( 'Timbres fiscaux', 'https://timbres.impots.gouv.fr/' ) ),
            ),

            'Read the visa sticker: VLS-TS or carte de séjour à solliciter' => array(
                'steps' => array(
                    array( 'Find the mention on the sticker', 'Under the visa type: "VLS-TS" or "carte de séjour à solliciter dans les 2 mois".' ),
                    array( 'VLS-TS: validate online', 'Within three months of entry, on the ANEF portal, with the validation tax.', $anef ),
                    array( 'Carte de séjour à solliciter: book the prefecture', 'Within two months of entry, apply for the residence card at the prefecture of your département, mostly online through ANEF or the prefecture\'s booking page. No validation tax; your residence right is not settled until the card is issued, so keep the récépissé.' ),
                ),
                'bring' => array( 'Passport and visa', 'Proof of address', 'The dossier originals' ),
                'time'  => 'Half an hour online, or a prefecture appointment within two months.',
                'cost'  => 'Validation tax for a VLS-TS; stamp duty when a card is issued.',
                'links' => array( array( 'ANEF portal', $anef ) ),
            ),

            'Open French bank account' => array(
                'steps' => array(
                    array( 'Pick a bank that takes newcomers', 'Traditional banks want an appointment and a proof of address; online banks open in days with a passport and a French address. Being a US citizen adds FATCA paperwork; some banks decline Americans, so ask first.' ),
                    array( 'Bring the file', 'Passport, the visa and its validation confirmation, proof of address (lease or host attestation with bill), and often a US tax number and a proof of income.' ),
                    array( 'Get the RIB', 'The RIB (bank details slip) is what every French service asks for: rent, utilities, CPAM, the phone. The card follows by post in a week or two.' ),
                    array( 'Keep the US account', 'For Social Security, pensions, US cards and the IRS.' ),
                ),
                'bring' => array( 'Passport and visa', 'Validation confirmation', 'Proof of address', 'US tax number (SSN) for FATCA' ),
                'time'  => 'Days to two weeks.',
                'cost'  => 'Usually a small monthly fee.',
                'links' => array(),
            ),

            'Get sworn French translations' => array(
                'steps' => array(
                    array( 'Know when they are needed', 'Not for the consulate, which took your file in English. The prefecture (renewal) and CPAM (health cover) want sworn French translations of the apostilled birth and marriage certificates.' ),
                    array( 'Find a traducteur assermenté', 'Sworn translators are registered as experts with a French cour d\'appel; each court publishes its list, and many work from scans by email. Only their stamp counts.' ),
                    array( 'Send the full scan', 'The certificate and its apostille together, front and back. The translation comes back stamped and dated; keep it with the original.' ),
                ),
                'bring' => array( 'Scans of each apostilled record' ),
                'time'  => 'A week or two per batch.',
                'cost'  => 'Roughly €30 to €80 per document.',
                'links' => array(),
            ),

            'Find permanent housing' => array(
                'steps' => array(
                    array( 'Build the rental dossier', 'French landlords ask for a dossier: ID, the visa, proof of income (three months), a tax return or the US equivalent, and often a guarantor. Newcomers without French pay slips offer several months\' rent or a guarantee service instead.' ),
                    array( 'Look where the listings are', 'Agencies, the big listing sites, and the mairie\'s noticeboard in smaller towns. Visits are quick; decisions are the same day in tight markets.' ),
                    array( 'Sign, and do the état des lieux', 'The lease (bail) plus the inventory of condition on entry; photograph everything. The lease becomes your proof of address for every office.' ),
                ),
                'bring' => array( 'Passport and visa', 'Proof of income', 'Bank RIB', 'Deposit' ),
                'time'  => 'Weeks.',
                'cost'  => 'Deposit of one month (unfurnished) or two (furnished), plus agency fees.',
                'links' => array(),
            ),

            'Set up utilities' => array(
                'steps' => array(
                    array( 'Electricity and gas', 'Open a contract with any supplier online; you need the meter number (on the meter or the previous bill), the RIB and the move-in date.' ),
                    array( 'Water', 'Often through the commune or a regional company; the landlord or agency says which.' ),
                    array( 'Internet and phone', 'Fibre or ADSL by address; a contract needs a French RIB. Installation takes one to three weeks.' ),
                    array( 'Keep the first bills', 'Each becomes a proof of address for the next office.' ),
                ),
                'bring' => array( 'Meter numbers', 'RIB', 'Lease' ),
                'time'  => 'A week; internet longer.',
                'cost'  => 'Monthly.',
                'links' => array(),
            ),

            'Apply for French health cover (PUMa)' => array(
                'steps' => array(
                    array( 'Wait three months of residence', 'Cover on the residence criterion opens after three months of stable, regular residence for those not working; workers are covered from the first payslip.' ),
                    array( 'Fill in form 736', 'The "demande d\'affiliation au régime général sur critère de résidence", from ameli.fr, sent to the CPAM of your département by post or through your ameli account once you have one.', $ameli ),
                    array( 'Attach the file', 'Passport and visa with the validation confirmation, proof of address, birth certificate with a sworn French translation, a RIB, and a proof of income or a statement of no income.' ),
                    array( 'Wait for the number', 'A provisional social security number first, then the permanent one; the carte Vitale is requested once the number is permanent. Keep paying and keeping receipts meanwhile.' ),
                ),
                'bring' => array( 'Form 736', 'Passport, visa, validation', 'Proof of address', 'Birth certificate, apostilled and translated', 'RIB' ),
                'time'  => 'Weeks to months after the file is complete.',
                'cost'  => 'Free; a means-tested contribution may apply to high capital income.',
                'links' => array( array( 'ameli.fr', $ameli ) ),
            ),

            'Enroll in French language classes' => array(
                'steps' => array(
                    array( 'Start before you need it', 'The mairie, the local association network and private schools all run classes; the integration contract (CIR) on family routes includes free ones.' ),
                    array( 'Aim for A2 then B1', 'A2 is referenced for some family-route steps; B1 is the citizenship benchmark. Both are ordinary evening-class goals over a year or two.' ),
                ),
                'bring' => array(),
                'time'  => 'Ongoing.',
                'cost'  => 'Free to a few hundred euros a term.',
                'links' => array(),
            ),

            'Watch for the OFII convocation' => array(
                'steps' => array(
                    array( 'Keep your address current', 'After validation, OFII writes by post or email to the address you validated with. Weeks to months later; moving without telling ANEF means the letter is lost.' ),
                    array( 'Attend the medical visit', 'A chest X-ray and a short screening at the OFII office of your region. Bring the passport, the validation confirmation and the vaccination record if you have one.' ),
                    array( 'Family routes: sign the CIR', 'The integration contract comes with a language assessment and civics days; attendance counts at renewal.' ),
                ),
                'bring' => array( 'Passport', 'Validation confirmation', 'The convocation letter' ),
                'time'  => 'Half a day, when summoned.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'OFII', 'https://www.ofii.fr/' ) ),
            ),

            'Sign the integration contract (CIR) when convoked' => array(
                'steps' => array(
                    array( 'Attend the OFII welcome interview', 'A language test places you; the contract sets the hours of French classes and the civics training.' ),
                    array( 'Do the training', 'Free, in your region; attendance is recorded and asked about at the multi-year card.' ),
                ),
                'bring' => array( 'Passport', 'Residence permit or récépissé', 'The convocation' ),
                'time'  => 'A day for the interview; classes over months.',
                'cost'  => 'Free.',
                'links' => array( array( 'OFII', 'https://www.ofii.fr/' ) ),
            ),

            // ---------------- Settle ----------------
            'Get Carte Vitale' => array(
                'steps' => array(
                    array( 'Wait for the permanent number', 'The card is requested once CPAM has issued the permanent social security number; ameli sends the request or you do it in your ameli account.', $ameli ),
                    array( 'Send the photo and the ID', 'A passport-style photo and a copy of your ID, online or on the paper form.' ),
                    array( 'Use the attestation meanwhile', 'The attestation de droits from your ameli account works at the doctor and the pharmacy until the card arrives.' ),
                ),
                'bring' => array( 'Photo', 'ID copy' ),
                'time'  => 'Weeks to months after the number.',
                'cost'  => 'Free.',
                'links' => array( array( 'ameli.fr', $ameli ) ),
            ),

            'File French tax return' => array(
                'steps' => array(
                    array( 'Know the calendar', 'The French return covers the calendar year and is filed the following spring (April to June). Your first one covers the year you arrived, from the arrival date.' ),
                    array( 'Get a tax number', 'A first return is filed on paper (form 2042) at your local tax office, which then issues the numéro fiscal for online filing the year after.', 'https://www.impots.gouv.fr/' ),
                    array( 'Declare worldwide income and foreign accounts', 'US income goes on the return with the treaty credits; every foreign bank account is listed on form 3916. This is the return a cross-border professional should prepare, at least the first time.' ),
                    array( 'Keep filing in the US', 'The US return and the FBAR continue. The two returns are prepared together.' ),
                ),
                'bring' => array( 'Income statements for both countries', 'French bank details', 'The list of foreign accounts' ),
                'time'  => 'Spring, every year.',
                'cost'  => 'Professional fees if you use one.',
                'links' => array( array( 'impots.gouv.fr', 'https://www.impots.gouv.fr/' ) ),
            ),

            'Start the renewal four months before the visa expires' => array(
                'steps' => array(
                    array( 'Count four months back from the expiry date', 'Renewal is filed between four and two months before the VLS-TS expires, online through ANEF for most categories. Late filing after expiry costs a €180 surcharge and leaves you without status.', $anef ),
                    array( 'Rebuild the file', 'Passport, the current visa and validation, proof of address, proof of resources for the year, insurance or health cover, the sworn translations of your civil records, photos. The prefecture\'s page lists the exact set for your card.' ),
                    array( 'Keep the récépissé', 'The receipt keeps you legal while the card is made; prefecture delays are the usual bottleneck.' ),
                ),
                'bring' => array( 'Passport, visa, validation', 'Proof of address', 'Proof of resources', 'Translated civil records', 'Photos' ),
                'time'  => 'File four months out; the card takes months.',
                'cost'  => 'Stamp duty on the card, set annually.',
                'links' => array( array( 'ANEF portal', $anef ), array( 'service-public.gouv.fr', $sp ) ),
            ),

            '__licence_exchange' => array(
                'steps' => array(
                    array( 'Confirm {state} is still on the list', 'As of {verified} {state} has a reciprocal agreement with France ({classes} transfers). The list changes; check the official page before filing.', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ),
                    array( 'Gather the file', 'Scans of the US licence front and back, passport and visa or residence permit, a proof of address under three months old, and an ANTS-format digital photo (photo booths and photographers marked "agréé ANTS" produce the code). A sworn translation of the licence if asked; many prefectures accept English.' ),
                    array( 'Apply online at ANTS', 'Create the account, choose "échange de permis étranger", upload the file and pay the €40 stamp introduced in May 2026.', $ants ),
                    array( 'Post the original licence', 'By lettre recommandée avec accusé de réception to the address ANTS gives. Keep the tracking slip: it is your only proof the licence is with them.' ),
                    array( 'Drive on the attestation', 'The attestation de dépôt keeps you legal while the national centre in Nantes processes the exchange, three to six months officially, up to a year in practice. File within your first year of residence; the filing date is what protects you.' ),
                ),
                'bring' => array( 'US licence (original, posted)', 'Passport and residence permit', 'Proof of address', 'ANTS digital photo' ),
                'time'  => 'File inside the first year; three to twelve months to process.',
                'cost'  => '€40 stamp, plus a translation if required.',
                'links' => array( array( 'ANTS: exchange a foreign licence', $ants ), array( 'service-public.gouv.fr: échange de permis', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ), array( 'International Driving Permit (AAA)', 'https://www.aaa.com/vacation/idpf.html' ) ),
            ),

            'Pass the French driving test before your US licence stops counting' => array(
                'steps' => array(
                    array( 'Check the list once more', '{state} has no reciprocal agreement as of {verified}. If that changes before your first year ends, the exchange is the easier path.', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ),
                    array( 'Get an International Driving Permit before you leave', 'From AAA in the US; it carries the French translation of your licence for the first year, when your US licence is still valid.', 'https://www.aaa.com/vacation/idpf.html' ),
                    array( 'Register: driving school or candidat libre', 'An auto-école handles the paperwork, lessons and exam bookings (roughly €1,200 to €3,800, at least 20 hours of lessons in most areas). Candidat libre means registering yourself on ANTS and booking exams directly, cheaper and slower.', $ants ),
                    array( 'Pass the code de la route', 'Forty questions at an approved centre, about €30. The exam is in French; the vocabulary is limited and learnable.' ),
                    array( 'Pass the practical', 'Booked through the school or ANTS, €32 to €50 depending on the prefecture; waits for a slot run weeks to months. On passing, a provisional certificate, then the card, with a three-year probation and a lower alcohol limit.' ),
                    array( 'Do it inside the first year', 'After a year of residence an unexchanged US licence is no longer valid in France. Start in the first months.' ),
                ),
                'bring' => array( 'Passport and residence permit', 'Proof of address', 'ANTS digital photo' ),
                'time'  => 'Months; start early in the first year.',
                'cost'  => '€1,200 to €3,800 through a school; exam fees on top.',
                'links' => array( array( 'ANTS', $ants ), array( 'service-public.gouv.fr: échange de permis', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ), array( 'International Driving Permit (AAA)', 'https://www.aaa.com/vacation/idpf.html' ) ),
            ),

            'Check whether your state driving licence can be exchanged' => array(
                'steps' => array(
                    array( 'Set your current state in your profile', 'This step then turns into the exchange through ANTS or the French driving test, with the process laid out.' ),
                    array( 'Or check the list yourself', 'Eighteen states have an agreement as of {verified}; the official page lists them.', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ),
                ),
                'bring' => array(),
                'time'  => 'A minute.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'service-public.gouv.fr: échange de permis', 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1460' ) ),
            ),

            // ---------------- Route-specific ----------------
            'Ask your employer to file the work authorisation on ANEF' => array(
                'steps' => array(
                    array( 'Send the employer the link', 'The autorisation de travail is requested by the employer on the ANEF employer portal, with the contract, the job description and, unless the job is on the shortage list, proof the post was advertised in France.', $anef ),
                    array( 'Know the clock', 'The regional labour office (DREETS) has two months to decide; six to ten weeks is common, slower in Paris. Chase politely at six weeks.' ),
                    array( 'Get the approval letter', 'The approval PDF is a consulate document; ask for it the day it lands.' ),
                ),
                'bring' => array(),
                'time'  => 'Six to ten weeks, the employer\'s side.',
                'cost'  => 'The employer pays a tax on hiring a foreign worker.',
                'links' => array( array( 'ANEF', $anef ) ),
            ),

            '__employer_paperwork' => array(
                'steps' => array(
                    array( 'Ask for the two documents', 'The signed contract (or a detailed offer with salary, hours and start date) and the work authorisation approval. Both come from the employer; the consulate does not open a file without them.' ),
                    array( 'Ask early', 'The authorisation is the slow part; start the ask five to six months before the move.' ),
                ),
                'bring' => array(),
                'time'  => 'Weeks, on the employer\'s side.',
                'cost'  => 'Nothing to you.',
                'links' => array(),
            ),

            'Get the signed contract and the authorisation approval' => array(
                'steps' => array(
                    array( 'Contract signed by both sides', 'Employer and employee signatures, salary, hours, start date and place of work. A detailed offer letter is accepted by some consulates; the contract is safer.' ),
                    array( 'The DREETS approval', 'The PDF the employer received from ANEF, in your name.' ),
                    array( 'Check the salary against your route', 'For a Talent application the salary must meet the reference threshold in force when the contract is signed.' ),
                ),
                'bring' => array(),
                'time'  => 'When the employer has them.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Gather your diplomas and proof of qualifications' => array(
                'steps' => array(
                    array( 'Collect the originals', 'Degree certificates and, where relevant, professional licences. The consulate in the US takes them in English.' ),
                    array( 'Apostille where the checklist asks', 'A diploma is apostilled by the state where the institution sits, after a notarised copy in most states; the NASS directory shows the office.', $nass ),
                    array( 'Keep transcripts handy', 'Some categories ask for them; ordering takes two to three weeks.' ),
                ),
                'bring' => array( 'Diplomas', 'Transcripts' ),
                'time'  => 'Two to four weeks if an apostille is needed.',
                'cost'  => 'Apostille and notary fees.',
                'links' => array( array( 'Apostille offices by state (NASS)', $nass ) ),
            ),

            'Check your first payslip and health affiliation' => array(
                'steps' => array(
                    array( 'Read the payslip', 'French payslips list every social contribution; the health line means you are affiliated from the first month, no three-month wait.' ),
                    array( 'Register on ameli', 'Create the ameli account with the social security number your employer\'s payroll obtained, then request the carte Vitale.', $ameli ),
                    array( 'Ask about the mutuelle', 'Employers must offer a complementary health plan and pay at least half of it.' ),
                ),
                'bring' => array(),
                'time'  => 'The first month.',
                'cost'  => 'Your share of the mutuelle.',
                'links' => array( array( 'ameli.fr', $ameli ) ),
            ),

            'Confirm your Talent category and its threshold' => array(
                'steps' => array(
                    array( 'Match your case to a category', 'Qualified employee (€39,582 gross a year in 2026), EU Blue Card (€59,373), company founder, investor (€300,000), researcher with a hosting agreement, artist. France-Visas describes each.', $fv ),
                    array( 'Confirm the threshold in force', 'Set by ministerial order and revised; the figure that counts is the one in force when your contract is signed. service-public.gouv.fr carries the current text.', $sp ),
                ),
                'bring' => array(),
                'time'  => 'An hour of reading.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'France-Visas', $fv ), array( 'service-public.gouv.fr', $sp ) ),
            ),

            'Gather the category proof' => array(
                'steps' => array(
                    array( 'Employee categories', 'The signed contract at or above the reference salary, the employer\'s Kbis, and your master\'s degree or proof of experience.' ),
                    array( 'Researcher', 'The convention d\'accueil signed by the French institution.' ),
                    array( 'Founder or investor', 'The business plan with funding proof, or the investment file, with any recognition (incubator, BPI).' ),
                ),
                'bring' => array(),
                'time'  => 'Weeks, depending on the category.',
                'cost'  => 'Nothing beyond the underlying deal.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Write the business plan and viability file' => array(
                'steps' => array(
                    array( 'Write for a reader who decides in ten minutes', 'What the business does, who the customers are, what they pay, the first two years of income and costs, and why France. Business France publishes what the administration looks for.', 'https://www.businessfrance.fr/' ),
                    array( 'Attach the proof', 'Contracts or letters of intent, past accounts if the business exists, personal savings to carry the first year.' ),
                    array( 'Show the income line', 'Projected income at least equal to the French minimum wage, about €21,000 gross a year in 2026, is the benchmark for commercial activity.' ),
                ),
                'bring' => array(),
                'time'  => 'Weeks.',
                'cost'  => 'Nothing, or an accountant\'s fee.',
                'links' => array( array( 'Business France', 'https://www.businessfrance.fr/' ) ),
            ),

            'Check whether your profession is regulated in France' => array(
                'steps' => array(
                    array( 'Look your profession up', 'Law, accounting, medicine, architecture, and many trades are regulated; the national directory of regulated professions says which and what it takes.', 'https://www.service-public.gouv.fr/' ),
                    array( 'Prove the qualification', 'A regulated profession needs the same diploma or experience as a French national, recognised by the professional body; start that recognition early, it can take months.' ),
                ),
                'bring' => array( 'Diplomas and licences' ),
                'time'  => 'Days to check; months for recognition.',
                'cost'  => 'Recognition fees vary.',
                'links' => array( array( 'service-public.gouv.fr', $sp ) ),
            ),

            'Show income projections at or above the SMIC' => array(
                'steps' => array(
                    array( 'Put the figures in a table', 'Monthly income and costs for the first twenty-four months, with the assumptions behind them.' ),
                    array( 'Back them up', 'Contracts, letters of intent, past invoices, and personal statements showing the reserve that carries the first year.' ),
                ),
                'bring' => array(),
                'time'  => 'A few days.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Register the business (Kbis or self-employed affiliation)' => array(
                'steps' => array(
                    array( 'Choose the status with an expert-comptable', 'Micro-entrepreneur for small turnover, an EI or a company beyond that; the status changes the social charges, the tax and the US filing.' ),
                    array( 'Register on the guichet unique', 'All business registrations go through the single online counter; you need the French address, a RIB and ID.', 'https://formalites.entreprises.gouv.fr/' ),
                    array( 'Keep the proof', 'The Kbis extract or the affiliation certificate from the self-employed scheme is what the prefecture asks for before finalising the card.' ),
                ),
                'bring' => array( 'ID', 'Proof of address', 'RIB' ),
                'time'  => 'Days online; weeks for the paperwork to follow.',
                'cost'  => 'Free for a micro-entreprise; fees for a company.',
                'links' => array( array( 'Guichet unique des formalités', 'https://formalites.entreprises.gouv.fr/' ), array( 'URSSAF', 'https://www.urssaf.fr/' ) ),
            ),

            'Set up business bank account' => array(
                'steps' => array(
                    array( 'Open a professional account', 'Required for a company, strongly advised for the rest; online business banks open in days with the registration proof.' ),
                ),
                'bring' => array( 'Kbis or registration proof', 'ID', 'Proof of address' ),
                'time'  => 'Days.',
                'cost'  => 'Monthly fee.',
                'links' => array(),
            ),

            'Apply for the entrepreneur carte de séjour with proof of registration' => array(
                'steps' => array(
                    array( 'File four months before expiry', 'Online through ANEF; the entrepreneur / profession libérale card, or the multi-year talent card for a project holder.', $anef ),
                    array( 'Show the business exists', 'Kbis or affiliation certificate, the first accounts or turnover declarations, and proof the income holds.' ),
                ),
                'bring' => array( 'Passport, visa, validation', 'Registration proof', 'Accounts', 'Proof of address' ),
                'time'  => 'Four months before expiry; months to issue.',
                'cost'  => 'Stamp duty on the card.',
                'links' => array( array( 'ANEF portal', $anef ) ),
            ),

            'Get university acceptance letter' => array(
                'steps' => array(
                    array( 'Apply to the institution', 'Direct to the university or school, or through their international office; deadlines run from January to May for a September start.' ),
                    array( 'Ask for the attestation d\'inscription', 'The official letter naming you, the programme, the dates and the fees; a conditional admission is accepted by some consulates, a firm one by all.' ),
                ),
                'bring' => array( 'Transcripts', 'Diplomas', 'Language test results where required' ),
                'time'  => 'Months; the longest lead time on the route.',
                'cost'  => 'Application fees.',
                'links' => array( array( 'Campus France USA', 'https://usa.campusfrance.org/' ) ),
            ),

            'Complete the Campus France Études en France application' => array(
                'steps' => array(
                    array( 'Wait for the acceptance', 'The procedure is for students already admitted to a degree, study-abroad, dual-degree or other academic programme in France. Have the acceptance letter before you start.' ),
                    array( 'Open your Études en France account', 'Campus France USA runs the pre-consular procedure for US applicants. Create the account, fill in your file, upload the acceptance letter, passport and the documents it asks for, and pay the application fee shown on the Campus France USA site.', 'https://www.usa.campusfrance.org/etudes-en-france-guideline' ),
                    array( 'Then book the visa', 'Campus France USA says the procedure must be completed before you apply for the student visa. When your file is processed you get the confirmation email; only then book the TLScontact appointment.', 'https://www.usa.campusfrance.org/student-visa-guide' ),
                ),
                'bring' => array( 'Acceptance letter', 'Passport', 'Transcripts and diplomas the file asks for' ),
                'time'  => 'Allow several weeks before your visa appointment.',
                'cost'  => 'The Études en France application fee, shown on the Campus France USA site.',
                'links' => array( array( 'Études en France guide', 'https://www.usa.campusfrance.org/etudes-en-france-guideline' ), array( 'Student visa guide', 'https://www.usa.campusfrance.org/student-visa-guide' ) ),
            ),

            'Apply for CROUS housing' => array(
                'steps' => array(
                    array( 'Apply through messervices.etudiant.gouv.fr', 'The national student services portal takes the housing request; international students apply through their institution\'s international office where CROUS rooms are reserved for them.', 'https://www.messervices.etudiant.gouv.fr/' ),
                    array( 'Keep the confirmation', 'It doubles as the proof of accommodation for the visa.' ),
                ),
                'bring' => array( 'Admission letter', 'ID' ),
                'time'  => 'Spring for a September room.',
                'cost'  => 'Low rents; a deposit.',
                'links' => array( array( 'Mes services étudiant', 'https://www.messervices.etudiant.gouv.fr/' ) ),
            ),

            'Prove financial resources for studies' => array(
                'steps' => array(
                    array( 'Know the monthly minimum', 'Set by decree and revised; about €877.50 a month from August 2026, for the length of the stay. Confirm the figure on France-Visas.', $fv ),
                    array( 'Show it', 'Bank statements, a scholarship award letter, or a sponsor\'s attestation de prise en charge with their statements and ID.' ),
                ),
                'bring' => array( 'Statements', 'Scholarship or sponsor letter' ),
                'time'  => 'A day.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Complete university enrollment' => array(
                'steps' => array(
                    array( 'Go to the inscription in person', 'With the passport, the visa, the admission letter, the CVEC receipt and photos; the student card follows.' ),
                ),
                'bring' => array( 'Passport and visa', 'Admission letter', 'Photos' ),
                'time'  => 'A morning in September.',
                'cost'  => 'Tuition and the CVEC contribution.',
                'links' => array(),
            ),

            'Register for student health cover on ameli' => array(
                'steps' => array(
                    array( 'Register online', 'etudiant-etranger.ameli.fr, after arrival, with the passport, the visa, the enrolment certificate and a RIB.', 'https://etudiant-etranger.ameli.fr/' ),
                    array( 'Wait for the number', 'A provisional number first; the carte Vitale once it is permanent.' ),
                ),
                'bring' => array( 'Passport and visa', 'Enrolment certificate', 'RIB' ),
                'time'  => 'Half an hour to file; weeks to process.',
                'cost'  => 'Free for students.',
                'links' => array( array( 'ameli for foreign students', 'https://etudiant-etranger.ameli.fr/' ) ),
            ),

            'Apply for CAF housing aid (APL)' => array(
                'steps' => array(
                    array( 'Apply on caf.fr once the lease exists', 'Students in rented or CROUS housing usually qualify. You need the lease, the landlord\'s details, a RIB and the residence permit.', 'https://www.caf.fr/' ),
                ),
                'bring' => array( 'Lease', 'RIB', 'Residence permit' ),
                'time'  => 'An hour; paid from the month after.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'CAF', 'https://www.caf.fr/' ) ),
            ),

            'Apostille the marriage certificate' => array(
                'steps' => array(
                    array( 'Order a certified copy', 'From the state or county that issued the marriage licence; the CDC directory names the office.', $cdc ),
                    array( 'Apostille it', 'At the Secretary of State of that state; digital apostilles are accepted for this route.', $nass ),
                    array( 'Married in France?', 'The French acte de mariage from the mairie is used instead; no apostille.' ),
                ),
                'bring' => array( 'The certified copy', 'Request form and fee' ),
                'time'  => 'Two to five weeks.',
                'cost'  => 'Copy plus apostille fees.',
                'links' => array( array( 'Where to write for vital records (CDC)', $cdc ), array( 'Apostille offices by state (NASS)', $nass ) ),
            ),

            'Get your spouse\'s French ID and proof of nationality' => array(
                'steps' => array(
                    array( 'Copy the passport or identity card', 'Both sides, in colour.' ),
                    array( 'Certificate of nationality if asked', 'Some consulates want the certificat de nationalité française, issued by the tribunal judiciaire; it takes weeks, so ask early whether your consulate requires it.' ),
                ),
                'bring' => array(),
                'time'  => 'A day; weeks for a certificate.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Collect proof the relationship is genuine' => array(
                'steps' => array(
                    array( 'Gather what shows a shared life', 'Joint bank statements, a lease or deed in both names, travel bookings together, photographs across the years, correspondence, insurance naming each other.' ),
                    array( 'For a PACS', 'The PACS certificate plus twelve months of documented cohabitation before the application counts.' ),
                    array( 'Order it', 'Chronologically, with a one-page index. The officer should see the story in two minutes.' ),
                ),
                'bring' => array(),
                'time'  => 'An afternoon.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Apply for the multi-year card before the first one expires' => array(
                'steps' => array(
                    array( 'File four months out', 'Through ANEF; a multi-year vie privée et familiale card.', $anef ),
                    array( 'The civic exam', 'Since 2026 a civic exam applies at this step; the prefecture page says how to book it.' ),
                    array( 'Show the life continues', 'Proof of shared residence and the spouse\'s French ID again.' ),
                ),
                'bring' => array( 'Passport and permit', 'Proof of shared address', 'Spouse\'s ID', 'CIR attendance' ),
                'time'  => 'Four months before expiry.',
                'cost'  => 'Stamp duty on the card.',
                'links' => array( array( 'ANEF portal', $anef ) ),
            ),

            'Your sponsor applies to OFII for family reunification' => array(
                'steps' => array(
                    array( 'The sponsor files from France', 'On the OFII site or at the OFII office of their département, with the family records, their residence permit, income and housing proof.', 'https://www.ofii.fr/' ),
                    array( 'The conditions', 'Eighteen months of legal residence on a permit valid a year or more; stable income around the minimum wage over twelve months (more for larger families); housing that meets the size and condition standards of the zone.' ),
                    array( 'Expect a home visit', 'The mairie or OFII checks the housing.' ),
                ),
                'bring' => array( 'Sponsor\'s permit', 'Twelve months of income', 'Lease or deed and housing details', 'Family records' ),
                'time'  => 'Six to fifteen months for the whole route.',
                'cost'  => 'Stamp duty when the family\'s cards are issued.',
                'links' => array( array( 'OFII', 'https://www.ofii.fr/' ) ),
            ),

            'Sponsor gathers proof of residence, income and housing' => array(
                'steps' => array(
                    array( 'Residence', 'Copies of the permits covering the last eighteen months.' ),
                    array( 'Income', 'Twelve months of pay slips or accounts, the last tax notice.' ),
                    array( 'Housing', 'Lease or deed, floor plan or surface area, and a recent bill.' ),
                ),
                'bring' => array(),
                'time'  => 'Weeks.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'OFII', 'https://www.ofii.fr/' ) ),
            ),

            'Apostille and translate the family records' => array(
                'steps' => array(
                    array( 'Certified copies', 'Marriage certificate and each child\'s birth certificate from the issuing state.', $cdc ),
                    array( 'Apostilles', 'At each issuing state\'s Secretary of State.', $nass ),
                    array( 'Sworn translations', 'OFII and the prefecture work in French: a traducteur assermenté for each record, from scans.' ),
                ),
                'bring' => array(),
                'time'  => 'Four to eight weeks.',
                'cost'  => 'Copies, apostilles and translations.',
                'links' => array( array( 'Where to write for vital records (CDC)', $cdc ), array( 'Apostille offices by state (NASS)', $nass ) ),
            ),

            'Wait for the OFII and prefecture decision' => array(
                'steps' => array(
                    array( 'Track through the sponsor', 'OFII checks the file and the prefecture decides; five months at this step is reported in some départements.' ),
                    array( 'Answer requests fast', 'An incomplete file adds two to four months.' ),
                ),
                'bring' => array(),
                'time'  => 'Months.',
                'cost'  => 'Nothing.',
                'links' => array(),
            ),

            'Apply for the visa once OFII approves' => array(
                'steps' => array(
                    array( 'With the approval, go to France-Visas', 'The long-stay visa for family reunification; the approval decision is the key document.', $fv ),
                    array( 'Book TLScontact', 'Issuance usually follows within two to four weeks of the appointment.', $tls ),
                ),
                'bring' => array( 'OFII approval', 'Passport', 'Family records' ),
                'time'  => 'Two to four weeks after the appointment.',
                'cost'  => 'Visa fee and TLScontact service fee.',
                'links' => array( array( 'France-Visas', $fv ), array( 'TLScontact France', $tls ) ),
            ),

            'Confirm the document list for your category with the consulate' => array(
                'steps' => array(
                    array( 'Run the wizard with the real category', 'France-Visas produces the list for interns, temporary and seasonal workers, posted employees and au pairs.', $fv ),
                    array( 'Get the category document early', 'The stamped internship agreement, the fixed-term contract with its work permit, the host-family agreement: each is issued by someone else and is the slow part.' ),
                ),
                'bring' => array(),
                'time'  => 'An hour to check; weeks for the category document.',
                'cost'  => 'Nothing.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            // ---------------- Route sub-cases ----------------
            'Assemble the qualified-employee proof: contract at the reference salary and your degree' => array(
                'steps' => array(
                    array( 'Confirm the reference salary in force', 'Set by ministerial order (€39,582 gross a year from 31 August 2025); the figure that counts is the one in force when the contract is signed.', 'https://www.service-public.gouv.fr/' ),
                    array( 'Get the contract and the employer documents', 'A contract of three months or more at or above the reference salary, and the employer\'s Kbis extract.' ),
                    array( 'Prove the qualification', 'A master\'s degree or equivalent, or the professional experience the category accepts instead; in English is fine for the consulate.' ),
                ),
                'bring' => array( 'Signed contract', 'Employer Kbis', 'Degree' ),
                'time'  => 'Weeks, mostly the employer\'s.', 'cost' => 'Nothing.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ) ),
            ),
            'Assemble the EU Blue Card proof: a contract of three months or more at 1.5 times the reference salary' => array(
                'steps' => array(
                    array( 'Check the salary line', '1.5 times the reference: €59,373 gross a year in 2026. Below it, the qualified-employee category may still fit.' ),
                    array( 'Contract and qualification', 'A contract of three months or more, and a degree of three years or more of higher education, or five years of comparable professional experience.' ),
                ),
                'bring' => array( 'Signed contract', 'Degree or experience letters' ),
                'time'  => 'Weeks.', 'cost' => 'Nothing.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ) ),
            ),
            'Assemble the founder file: the innovative project, its recognition and the funds' => array(
                'steps' => array(
                    array( 'Get the project recognised', 'A public body or incubator letter (BPI, a French Tech programme, an accredited incubator) saying the project is innovative is the heart of the file.' ),
                    array( 'Write the plan and show the funds', 'A business plan the consulate can follow, and proof of funds; €30,000 is the commonly cited benchmark for the founder track.' ),
                ),
                'bring' => array( 'Recognition letter', 'Business plan', 'Proof of funds' ),
                'time'  => 'Weeks to months for the recognition.', 'cost' => 'Nothing beyond the project.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ), array( 'Business France', 'https://www.businessfrance.fr/' ) ),
            ),
            'Assemble the investor file: the investment and the jobs it carries' => array(
                'steps' => array(
                    array( 'Document the investment', 'At least €300,000 invested directly, or through a company you control, in a French company.' ),
                    array( 'Document the jobs', 'The jobs created or kept in France over the four years of the card.' ),
                ),
                'bring' => array( 'Investment proof', 'Company documents', 'Job commitments' ),
                'time'  => 'Weeks.', 'cost' => 'The investment itself.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ) ),
            ),
            'Get the hosting agreement (convention d\'accueil) from your French institution' => array(
                'steps' => array(
                    array( 'Ask the international office', 'The convention d\'accueil is drawn up by the host university or research body and stamped by the prefecture; it names you, the research and the dates.' ),
                    array( 'Allow weeks', 'It is the category proof, and nothing else in the file substitutes for it.' ),
                ),
                'bring' => array( 'Passport details', 'Degree', 'The research project' ),
                'time'  => 'Weeks.', 'cost' => 'Nothing.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ) ),
            ),
            'Assemble the artist file: contracts, income and recognition of your work' => array(
                'steps' => array(
                    array( 'Contracts and income', 'Engagements or contracts in France and proof that your work supports the stay.' ),
                    array( 'Recognition', 'Press, prizes, exhibitions, performances: the record that shows an established practice.' ),
                ),
                'bring' => array( 'Contracts', 'Income proof', 'Portfolio and press' ),
                'time'  => 'Weeks.', 'cost' => 'Nothing.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ) ),
            ),
            'Assemble twelve months of PACS cohabitation proof' => array(
                'steps' => array(
                    array( 'Register the PACS, then count twelve months', 'Free at the mairie, or through a notaire for a customised agreement. The clock for the family route starts at registration and needs twelve months of shared life.' ),
                    array( 'Collect the proof month by month', 'Joint lease or deed, joint accounts, utility bills at one address, travel together, insurance naming each other. A folder that shows a continuous shared life, not a single snapshot.' ),
                    array( 'Expect scrutiny', 'A March 2025 circular asks that the PACS be effective, stable and not fraudulent; the file is read for that.' ),
                ),
                'bring' => array( 'PACS certificate', 'Twelve months of joint documents' ),
                'time'  => 'Twelve months, by definition.', 'cost' => 'Free at the mairie; €250 to €450 through a notaire.',
                'links' => array( array( 'service-public.gouv.fr', 'https://www.service-public.gouv.fr/' ) ),
            ),
            'Plan the yearly renewals of a multi-year student permit' => array(
                'steps' => array(
                    array( 'Renew before each academic year', 'Through ANEF, two to four months before the current permit expires: enrolment for the coming year, funds at the decree minimum, and proof you attended and passed.', 'https://administration-etrangers-en-france.interieur.gouv.fr/' ),
                    array( 'Ask for the multi-year card', 'After the first year, a student card covering the rest of the programme can be requested at renewal; it saves a renewal a year.' ),
                ),
                'bring' => array( 'Enrolment certificate', 'Proof of funds', 'Transcripts' ),
                'time'  => 'Each summer.', 'cost' => 'Stamp duty on each card.',
                'links' => array( array( 'ANEF portal', 'https://administration-etrangers-en-france.interieur.gouv.fr/' ) ),
            ),

            // ---------------- Household ----------------
            'Get marriage certificate apostilled' => array(
                'steps' => array(
                    array( 'Order a certified copy', 'From the state or county where the licence was issued; the CDC directory names the office and whether it is state or county.', $cdc ),
                    array( 'Apostille it at that state\'s Secretary of State', 'The NASS directory has the office, fee and turnaround. Send the certified copy with the request form naming France; keep the apostille attached when it returns.', $nass ),
                    array( 'Scan it', 'Front and back, apostille included; the scan is what the translator and French offices use later.' ),
                ),
                'bring' => array( 'Certified copy', 'Request form and fee', 'Return envelope' ),
                'time'  => 'Two to five weeks.',
                'cost'  => 'Copy plus apostille fees.',
                'links' => array( array( 'Where to write for vital records (CDC)', $cdc ), array( 'Apostille offices by state (NASS)', $nass ) ),
            ),

            'Translate marriage certificate' => array(
                'steps' => array(
                    array( 'After arrival, not for the consulate', 'The consulate takes the certificate in English; the prefecture and CPAM want a sworn French translation.' ),
                    array( 'Use a traducteur assermenté', 'Registered with a French cour d\'appel; works from a scan of the apostilled certificate.' ),
                ),
                'bring' => array( 'Scan of the apostilled certificate' ),
                'time'  => 'A week or two.',
                'cost'  => '€30 to €80.',
                'links' => array(),
            ),

            'Register spouse for social security' => array(
                'steps' => array(
                    array( 'Wait the same three months', 'Residence-based cover opens for each adult separately after three months of stable residence; there is no dependant status for a spouse.' ),
                    array( 'File form 736 in {spouse}\'s name', 'The same form as yours, sent to the same CPAM, with {spouse}\'s passport and visa, validation confirmation, proof of the shared address, their apostilled birth certificate with a sworn translation, and a RIB (a joint account is fine).', $ameli ),
                    array( 'Two numbers, two cards', 'Each of you gets a social security number and, later, a carte Vitale.' ),
                ),
                'bring' => array( 'Form 736', '{spouse}\'s passport, visa and validation', 'Proof of address', 'Birth certificate, apostilled and translated', 'RIB' ),
                'time'  => 'After three months of residence; weeks to months to process.',
                'cost'  => 'Free.',
                'links' => array( array( 'ameli.fr', $ameli ) ),
            ),

            'Gather spouse documents' => array(
                'steps' => array(
                    array( 'Build {spouse}\'s dossier in parallel', 'Their own passport, photos, France-Visas form, proof of accommodation, insurance attestation, financial proof (shared accounts are fine, named on both), and their own certified, apostilled birth certificate.' ),
                    array( 'One appointment, two files', 'Book consecutive TLScontact slots; each file is judged on its own.' ),
                ),
                'bring' => array(),
                'time'  => 'Runs with yours.',
                'cost'  => 'A second set of fees.',
                'links' => array( array( 'France-Visas', $fv ) ),
            ),

            'Apply for spouse visa' => array(
                'steps' => array(
                    array( 'A separate France-Visas application', 'In {spouse}\'s name, same route and dates as yours.', $fv ),
                    array( 'Same TLScontact day', 'Consecutive slots; the fee is per applicant.', $tls ),
                ),
                'bring' => array( '{spouse}\'s complete dossier' ),
                'time'  => 'With yours.',
                'cost'  => '€99 plus the service fee.',
                'links' => array( array( 'France-Visas', $fv ), array( 'TLScontact France', $tls ) ),
            ),

            'Get birth certificates apostilled' => array(
                'steps' => array(
                    array( 'Certified copies for each child', 'From the state of birth; long-form, with both parents named.', $cdc ),
                    array( 'Apostille each at its issuing state', 'Children born in different states mean different offices.', $nass ),
                    array( 'Custody papers where they apply', 'If a child has another parent not moving, a notarised consent and, if relevant, the custody order, apostilled.' ),
                ),
                'bring' => array( 'Certified copies', 'Request forms and fees' ),
                'time'  => 'Two to five weeks.',
                'cost'  => 'Copy plus apostille fees per child.',
                'links' => array( array( 'Where to write for vital records (CDC)', $cdc ), array( 'Apostille offices by state (NASS)', $nass ) ),
            ),

            'Translate birth certificates' => array(
                'steps' => array(
                    array( 'After arrival, not for the consulate', 'The consulate takes each child\'s apostilled birth certificate in English. The school, CPAM and the prefecture want a sworn French translation.' ),
                    array( 'Use a traducteur assermenté', 'Registered with a French cour d\'appel; works from scans of the certificate and its apostille.' ),
                ),
                'bring' => array( 'Scans of each apostilled certificate' ),
                'time'  => 'A week or two.', 'cost' => 'Roughly €30 to €80 per certificate.',
                'links' => array(),
            ),
            'Gather children vaccination records' => array(
                'steps' => array(
                    array( 'Get the full record from your paediatrician', 'Every vaccine with its date, on the practice\'s letterhead. French schools and crèches check vaccinations at enrolment.' ),
                    array( 'Compare with the French schedule', 'France makes a set of childhood vaccines compulsory for school; a French doctor can say whether a catch-up dose is needed. service-public.gouv.fr lists the schedule.', 'https://www.service-public.gouv.fr/' ),
                    array( 'Carry it with the originals', 'Hand luggage, with the birth certificates.' ),
                ),
                'bring' => array( 'Vaccination record for each child' ),
                'time'  => 'A call to the paediatrician.', 'cost' => 'Usually nothing.',
                'links' => array( array( 'service-public.gouv.fr', 'https://www.service-public.gouv.fr/' ) ),
            ),
            'Research French schools' => array(
                'steps' => array(
                    array( 'Know the three kinds', 'Public schools are free and assigned by address through the mairie; private schools under contract with the state follow the national curriculum for modest fees; international and bilingual schools charge more and have waiting lists.' ),
                    array( 'Check the address rule', 'For a public school, your address decides the school. Settle where you will live with that in mind.' ),
                    array( 'Ask about support for non-French speakers', 'Many areas run classes for newly arrived children who do not yet speak French; ask the mairie or the rectorat for your area.' ),
                ),
                'bring' => array(),
                'time'  => 'A few evenings.', 'cost' => 'Nothing.',
                'links' => array( array( 'service-public.gouv.fr', 'https://www.service-public.gouv.fr/' ) ),
            ),
            'Apply for child visas' => array(
                'steps' => array(
                    array( 'One application per child', 'On France-Visas, in the child\'s name, same route and dates as the parent\'s.', 'https://france-visas.gouv.fr/' ),
                    array( 'Same appointment as a parent', 'Book consecutive TLScontact slots; children over twelve give fingerprints.', 'https://visas-fr.tlscontact.com/' ),
                    array( 'Bring the family papers', 'The apostilled birth certificate naming both parents and, if one parent is not moving, their notarised consent.' ),
                ),
                'bring' => array( 'Child\'s passport', 'Apostilled birth certificate', 'Parental consent if needed' ),
                'time'  => 'With the parent\'s.', 'cost' => 'The visa fee and service fee per child.',
                'links' => array( array( 'France-Visas', 'https://france-visas.gouv.fr/' ), array( 'TLScontact France', 'https://visas-fr.tlscontact.com/' ) ),
            ),
            'Enroll children in school' => array(
                'steps' => array(
                    array( 'Register at the mairie', 'For a public primary school, the mairie of your commune registers the child and names the school; for collège and lycée, the school or the rectorat does.' ),
                    array( 'Bring the file', 'Proof of address, the child\'s birth certificate (translated), the vaccination record, and your ID.' ),
                    array( 'Then the school itself', 'The school confirms the place and the start date.' ),
                ),
                'bring' => array( 'Proof of address', 'Birth certificate with translation', 'Vaccination record', 'Parent ID' ),
                'time'  => 'A visit or two in the first week.', 'cost' => 'Free for public school.',
                'links' => array( array( 'service-public.gouv.fr', 'https://www.service-public.gouv.fr/' ) ),
            ),
            'Apply for family CAF benefits' => array(
                'steps' => array(
                    array( 'Check what applies', 'Family allowances depend on the number of children and on residence; CAF\'s simulator on caf.fr says what your family may receive.', 'https://www.caf.fr/' ),
                    array( 'Apply online', 'With your residence permit, the children\'s birth certificates, proof of address and a RIB.' ),
                ),
                'bring' => array( 'Residence permit', 'Children\'s birth certificates', 'Proof of address', 'RIB' ),
                'time'  => 'An hour to file.', 'cost' => 'Nothing.',
                'links' => array( array( 'CAF', 'https://www.caf.fr/' ) ),
            ),
            'Register children for health coverage' => array(
                'steps' => array(
                    array( 'No waiting period for children', 'Minors are affiliated to French health cover from arrival, without the three months of residence adults need.' ),
                    array( 'Add them to a parent\'s file at CPAM', 'Through your ameli account or by post, with each child\'s birth certificate (translated) and passport.', 'https://www.ameli.fr/' ),
                ),
                'bring' => array( 'Birth certificates with translations', 'Passports' ),
                'time'  => 'Weeks to process.', 'cost' => 'Free.',
                'links' => array( array( 'ameli.fr', 'https://www.ameli.fr/' ) ),
            ),

            // ---------------- Pets ----------------
            'Research pet travel requirements' => array(
                'steps' => array(
                    array( 'Read the USDA APHIS page for France', 'The requirements for dogs, cats and ferrets entering the EU: ISO microchip, rabies vaccination after the chip and at least 21 days before travel, and the EU health certificate endorsed by USDA within ten days of arrival.', 'https://www.aphis.usda.gov/pet-travel' ),
                    array( 'Check the airline', 'Cabin, hold or cargo rules, crate sizes, breed restrictions and seasonal embargoes differ by airline.' ),
                    array( 'Find a USDA-accredited vet', 'Only an accredited vet can complete the certificate.' ),
                ),
                'bring' => array(),
                'time'  => 'An evening; start five months out.',
                'cost'  => 'Nothing to read; hundreds to thousands to fly.',
                'links' => array( array( 'USDA APHIS pet travel', 'https://www.aphis.usda.gov/pet-travel' ) ),
            ),

            'Get pet microchipped' => array(
                'steps' => array(
                    array( 'ISO 11784/11785 chip, 15 digits', 'At the vet; if the pet has an older 9- or 10-digit chip, add an ISO one or carry your own scanner. The chip must go in before the rabies shot that counts.' ),
                ),
                'bring' => array(),
                'time'  => 'One visit.',
                'cost'  => '$25 to $60.',
                'links' => array( array( 'USDA APHIS pet travel', 'https://www.aphis.usda.gov/pet-travel' ) ),
            ),

            'Update pet vaccinations' => array(
                'steps' => array(
                    array( 'Rabies after the chip, at least 21 days before travel', 'If the current shot was given before the chip was implanted, it does not count; revaccinate and wait 21 days.' ),
                    array( 'Keep the certificate', 'Vaccine name, batch, date and validity, signed by the vet.' ),
                ),
                'bring' => array( 'Chip number' ),
                'time'  => 'One visit, plus the 21-day wait.',
                'cost'  => 'A vet visit.',
                'links' => array( array( 'USDA APHIS pet travel', 'https://www.aphis.usda.gov/pet-travel' ) ),
            ),

            'Get EU pet passport or health certificate' => array(
                'steps' => array(
                    array( 'The EU health certificate, within ten days of arrival', 'Completed by a USDA-accredited vet, then endorsed by the USDA APHIS office, by mail or in person, dated within ten days of entry into the EU.', 'https://www.aphis.usda.gov/pet-travel' ),
                    array( 'Carry it in the cabin', 'With the rabies certificate; French customs check it on arrival. Once in France a French vet issues an EU pet passport for later travel.' ),
                ),
                'bring' => array( 'Rabies certificate', 'Chip number', 'Flight details' ),
                'time'  => 'The last ten days.',
                'cost'  => 'Vet fee plus USDA endorsement (about $38 per certificate).',
                'links' => array( array( 'USDA APHIS pet travel', 'https://www.aphis.usda.gov/pet-travel' ) ),
            ),

            'Book pet-friendly accommodation' => array(
                'steps' => array(
                    array( 'Say so when booking', 'Most French rentals allow pets by law in unfurnished leases; furnished and short-term lets set their own rules, so confirm in writing.' ),
                ),
                'bring' => array(),
                'time'  => 'With the accommodation search.',
                'cost'  => 'Sometimes a pet deposit.',
                'links' => array(),
            ),

            'Find a French veterinarian' => array(
                'steps' => array(
                    array( 'Register in the first weeks', 'The vet transfers the microchip registration to the French I-CAD database and issues the EU pet passport for future travel.' ),
                ),
                'bring' => array( 'The US certificates', 'Chip number' ),
                'time'  => 'One visit.',
                'cost'  => 'A consultation and the passport fee.',
                'links' => array(),
            ),

            // ---------------- Professionals ----------------
            'Talk to a cross-border tax professional before you move' => array(
                'steps' => array(
                    array( 'Find someone who does both countries', 'A US CPA or enrolled agent who prepares French returns too, or a French expert-comptable with US clients. Ask how many US-French clients they file for each year.' ),
                    array( 'Bring the whole picture', 'Every income source, every account, retirement plans, property in both countries, the move date, and the visa route. The year you become French tax resident is the year to plan.' ),
                    array( 'Decide the move date with them', 'Where in the year you arrive changes the first French return and the US foreign earned income and credit positions.' ),
                ),
                'bring' => array( 'Last two US returns', 'List of accounts and assets', 'Income sources', 'Planned move date' ),
                'time'  => 'One or two meetings before the move.',
                'cost'  => 'Hundreds to low thousands of dollars.',
                'links' => array(),
            ),

            'Ask how your US pension and retirement accounts are taxed in France' => array(
                'steps' => array(
                    array( 'List every account and benefit', 'Social Security, private pensions, IRAs, 401(k)s, Roth accounts, annuities.' ),
                    array( 'Ask the treaty questions', 'Which are taxed only in the US under the treaty, which France taxes with a credit, how Roth withdrawals are treated, and what the French return needs to show so the treaty applies.' ),
                    array( 'Plan withdrawals around it', 'Timing distributions before or after the move can change the bill; decide with the professional, not after.' ),
                ),
                'bring' => array( 'Account statements', 'Benefit letters' ),
                'time'  => 'One meeting.',
                'cost'  => 'Part of the tax consultation.',
                'links' => array(),
            ),

            'Get a mortgage agreement in principle (accord de principe)' => array(
                'steps' => array(
                    array( 'Talk to a courtier', 'A mortgage broker compares French banks that lend to newcomers; expect a larger deposit (often 20 to 30 percent) and proof of stable income.' ),
                    array( 'Assemble the French dossier', 'Three months of statements, income proof, tax returns, ID, the visa, and a list of assets and debts.' ),
                    array( 'Get the accord de principe in writing', 'Sellers and agents ask to see it with an offer; it is not the loan, but it opens doors.' ),
                ),
                'bring' => array( 'Statements', 'Income proof', 'Tax returns', 'ID and visa' ),
                'time'  => 'Two to six weeks.',
                'cost'  => 'Broker fee at completion.',
                'links' => array(),
            ),

            'Budget for the notaire on a purchase' => array(
                'steps' => array(
                    array( 'Ask for the full cost before signing', 'The notaire\'s fees and the transfer taxes come on top of the price, roughly seven to eight percent on an older property; the notaire gives an estimate on request.' ),
                    array( 'Consider your own notaire', 'The notaire is neutral; buyers often appoint their own at no extra cost, the fee being shared.' ),
                    array( 'Read the compromis with a translator', 'The compromis de vente binds you after a ten-day cooling-off period; get it explained before you sign.' ),
                ),
                'bring' => array(),
                'time'  => 'Before the compromis.',
                'cost'  => 'About seven to eight percent of the price, taxes included.',
                'links' => array(),
            ),

            'Check how owning French property affects your taxes and estate' => array(
                'steps' => array(
                    array( 'Two questions for a notaire', 'How the property is taxed once you are resident (taxe foncière, wealth tax on real estate above the threshold, capital gains), and how French forced-heirship rules apply to it.' ),
                    array( 'Bring the deed', 'And your US will; the notaire says whether an election of US law or a change of ownership structure is worth it.' ),
                ),
                'bring' => array( 'Deed', 'US will' ),
                'time'  => 'One meeting.',
                'cost'  => 'A notaire\'s consultation.',
                'links' => array(),
            ),

            'Confirm how remote work for a US employer is treated in France' => array(
                'steps' => array(
                    array( 'Ask the tax professional two things', 'How your salary is taxed once you are French tax resident, and whether the employer has payroll or social-charge obligations in France.' ),
                    array( 'Ask the employer one thing', 'Whether they will support the arrangement in writing; some consulates now ask for an employer letter with a visitor application.' ),
                ),
                'bring' => array( 'Employment contract', 'Pay slips' ),
                'time'  => 'One meeting.',
                'cost'  => 'Part of the tax consultation.',
                'links' => array(),
            ),

            'Choose a French business status and register with URSSAF' => array(
                'steps' => array(
                    array( 'Pick the status with an expert-comptable', 'Micro-entrepreneur, entreprise individuelle or a company; each changes social charges, VAT and the US filing.' ),
                    array( 'Register on the guichet unique', 'The single online counter registers the activity and affiliates you with URSSAF.', 'https://formalites.entreprises.gouv.fr/' ),
                ),
                'bring' => array( 'ID', 'Proof of address', 'RIB' ),
                'time'  => 'Days.',
                'cost'  => 'Free for a micro-entreprise.',
                'links' => array( array( 'Guichet unique des formalités', 'https://formalites.entreprises.gouv.fr/' ), array( 'URSSAF', 'https://www.urssaf.fr/' ) ),
            ),

            'Check how French inheritance rules affect your estate plan' => array(
                'steps' => array(
                    array( 'See a notaire or estate lawyer', 'France reserves a share of the estate for children regardless of a will; a US will may not do what you expect for assets in France.' ),
                    array( 'Ask about the election of US law', 'The EU succession regulation lets you elect the law of your nationality in your will; the notaire says whether it helps and how to word it.' ),
                ),
                'bring' => array( 'US will', 'List of assets by country' ),
                'time'  => 'One meeting.',
                'cost'  => 'A consultation.',
                'links' => array(),
            ),
        );
    }
}
