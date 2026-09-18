<?php
/**
 * Letters for the visa application, drafted from the member's file.
 *
 * Which letters a member gets depends on the route: a visitor or retiree
 * needs the cover letter, the declaration not to work and a statement of
 * resources; a student may need a sponsor's attestation de prise en
 * charge; the spouse of a French national a statement of the relationship;
 * a founder a business plan. Every requirement written here mirrors the
 * knowledge base (visas/*, visa_application_guide/*) or the step how-tos,
 * which are drawn from it. Nothing is a legal template: each letter says
 * what the member must check, sign and attach.
 *
 * The text is kept in a small plain-text markup so the member can edit it
 * and the PDF is rebuilt from their words:
 *   # Title            heading
 *   ## Section         subheading
 *   - item             bullet
 *   > note             small italic note
 *   ---                rule
 *   [signature] Label  a line to sign on
 *   // note            a note to the member, never printed
 *   anything else      a line of text; a blank line starts a new block
 *
 * @package     FRA_Member_Tools
 * @subpackage  Documents
 * @since       2.9.25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FRAMT_Letters {

    /** User meta holding the member's answers, shared across letters. */
    const ANSWERS_META = 'fra_letter_answers';

    /**
     * Benchmarks quoted in the guidance, from the knowledge base. Dated so a
     * stale figure is visible, never silently wrong.
     */
    const SMIC_NET_MONTHLY    = 1477.93; // Net SMIC since 1 June 2026 (visas/overview).
    const SMIC_SINCE          = '1 June 2026';
    const STUDENT_MONTHLY     = 877.50;  // Student minimum from 1 August 2026 (visa_application_guide).
    const STUDENT_SINCE       = '1 August 2026';

    /** Answers kept as profile fields (fra_<key>), shown on the Profile page. */
    const PROFILE_KEYS = array( 'consulate', 'mailing_address' );

    /** The French consulates general that take visa files in the US. */
    const CONSULATES = array( 'Atlanta', 'Boston', 'Chicago', 'Houston', 'Los Angeles', 'Miami', 'New Orleans', 'New York', 'San Francisco', 'Washington' );

    // ------------------------------------------------------------------
    // Context
    // ------------------------------------------------------------------

    /**
     * Everything a letter draws on, in one place.
     *
     * @param int   $user_id  Household owner.
     * @param array $dossier  The route's dossier items (id, title).
     * @return array
     */
    public static function context( $user_id, $dossier = array() ) {
        $m = function ( $key ) use ( $user_id ) {
            return trim( (string) get_user_meta( $user_id, 'fra_' . $key, true ) );
        };
        $user    = get_userdata( $user_id );
        $answers = get_user_meta( $user_id, self::ANSWERS_META, true );
        $answers = is_array( $answers ) ? $answers : array();
        // The consulate and the mailing address live in the profile, where
        // the member sees and edits them; the letters read them from there.
        foreach ( self::PROFILE_KEYS as $key ) {
            $answers[ $key ] = $m( $key );
        }
        // A stay length typed before the menu existed ("renewable") would read
        // badly in the letter; only the menu's wordings are used.
        if ( isset( $answers['stay_length'] ) && ! in_array( $answers['stay_length'], wp_list_pluck( self::stay_options(), 'value' ), true ) ) {
            unset( $answers['stay_length'] );
        }
        $visa    = $m( 'visa_type' );
        $aliases = array( 'talent' => 'talent_passport', 'work' => 'employee' );
        $visa    = $aliases[ $visa ] ?? $visa;

        return array(
            'visa'        => $visa,
            'first'       => $m( 'legal_first_name' ) ?: ( $user ? $user->first_name : '' ),
            'middle'      => $m( 'legal_middle_name' ),
            'last'        => $m( 'legal_last_name' ) ?: ( $user ? $user->last_name : '' ),
            'email'       => $user ? $user->user_email : '',
            'dob'         => $m( 'date_of_birth' ),
            'nationality' => $m( 'nationality' ),
            'passport'    => $m( 'passport_number' ),
            'applicants'  => $m( 'applicants' ),
            'spouse'      => trim( $m( 'spouse_legal_first_name' ) . ' ' . $m( 'spouse_legal_last_name' ) ) ?: $m( 'spouse_name' ),
            'spouse_dob'  => $m( 'spouse_date_of_birth' ),
            'spouse_work' => $m( 'spouse_work_status' ),
            'children'    => (int) $m( 'num_children' ),
            'relationship' => $m( 'relationship_type' ),
            'talent'      => $m( 'talent_category' ),
            'employment'  => $m( 'employment_status' ),
            'work_fr'     => $m( 'work_in_france' ),
            'employer'    => $m( 'employer_name' ),
            'job_title'   => $m( 'job_title' ),
            'city'        => $m( 'current_city' ),
            'state'       => strtoupper( $m( 'current_state' ) ),
            'birth_state' => strtoupper( $m( 'birth_state' ) ),
            'birth_other' => $m( 'birth_state_other' ),
            'target'      => $m( 'target_location' ),
            'housing'     => $m( 'housing_plan' ),
            'move_date'   => substr( $m( 'target_move_date' ), 0, 10 ),
            'income'      => $m( 'income_sources' ),
            'study'       => $m( 'study_length' ),
            'answers'     => $answers,
            'dossier'     => $dossier,
        );
    }

    private static function has_partner( $c ) {
        return in_array( $c['applicants'], array( 'spouse', 'spouse_kids', 'couple', 'family' ), true ) && '' !== $c['spouse'];
    }

    private static function full_name( $c ) {
        return trim( preg_replace( '/\s+/', ' ', $c['first'] . ' ' . $c['middle'] . ' ' . $c['last'] ) );
    }

    // ------------------------------------------------------------------
    // Questions
    // ------------------------------------------------------------------

    /**
     * Every question a letter can ask. Shared across letters: the address
     * in France is typed once and used by the cover letter and the
     * declaration alike.
     *
     * @return array key => definition
     */
    public static function fields() {
        $consulates = array();
        foreach ( self::CONSULATES as $city ) {
            $consulates[] = array( 'value' => $city, 'label' => 'Consulate General of France in ' . $city );
        }
        return array(
            'consulate'          => array( 'label' => 'The French consulate nearest you', 'type' => 'select', 'options' => $consulates, 'hint' => 'Each consulate serves a set of states. France-Visas confirms which one is yours when you start your application.' ),
            'mailing_address'    => array( 'label' => 'Your current mailing address', 'type' => 'textarea', 'hint' => 'Street, city, state and ZIP, as you want it at the top of your letters.' ),
            'address_in_france'  => array( 'label' => 'Where you will live in France', 'type' => 'textarea', 'hint' => 'The address on your lease, deed, booking or host attestation. It must match the accommodation proof in your file.' ),
            'arrival_date'       => array( 'label' => 'Planned arrival date', 'type' => 'date', 'hint' => 'Your move date from your profile; change it here if the letter needs another.' ),
            'stay_length'        => array( 'label' => 'How long you plan to stay', 'type' => 'select', 'options' => self::stay_options(), 'hint' => 'How the letter words it.' ),
            'birth_place'        => array( 'label' => 'Place of birth (city, state)', 'type' => 'text', 'hint' => 'As it appears on your passport.' ),
            'partner_birth_place' => array( 'label' => 'Your partner\'s place of birth (city, state)', 'type' => 'text', 'hint' => 'As it appears on their passport.' ),
            'partner_passport'   => array( 'label' => 'Your partner\'s passport number', 'type' => 'text', 'hint' => '' ),
            'income_rows'        => array( 'label' => 'Your income', 'type' => 'income', 'hint' => 'One row for each source, in dollars or euros, a month or a year. Dollars are converted at the European Central Bank\'s rate.' ),
            'savings'            => array( 'label' => 'Savings and investments you can draw on', 'type' => 'money', 'hint' => 'The total your statements show. Leave it at 0 if you rely on income alone.' ),
            'plans'              => array( 'label' => 'What you will do with your time in France', 'type' => 'textarea', 'hint' => 'A sentence or two in your own words: language classes, family, travel, volunteering.' ),
            'school_name'        => array( 'label' => 'School or university', 'type' => 'text', 'hint' => 'As written on your attestation d\'inscription.' ),
            'programme'          => array( 'label' => 'Programme', 'type' => 'text', 'hint' => 'For example: Master in International Relations.' ),
            'programme_start'    => array( 'label' => 'Programme start date', 'type' => 'date', 'hint' => '' ),
            'programme_end'      => array( 'label' => 'Programme end date', 'type' => 'date', 'hint' => '' ),
            'contract_start'     => array( 'label' => 'Start date in your contract', 'type' => 'date', 'hint' => '' ),
            'talent_detail'      => array( 'label' => 'What your category rests on', 'type' => 'textarea', 'hint' => 'The contract, the project, the hosting agreement or the investment, in a sentence or two.' ),
            'business_name'      => array( 'label' => 'Business name', 'type' => 'text', 'hint' => 'A working name is fine.' ),
            'business_activity'  => array( 'label' => 'What the business does', 'type' => 'textarea', 'hint' => 'One or two sentences.' ),
            'investment_eur'     => array( 'label' => 'Funds you will invest, in euros', 'type' => 'number', 'hint' => '' ),
            'marriage_date'      => array( 'label' => 'Date of marriage or PACS', 'type' => 'date', 'hint' => '' ),
            'marriage_place'     => array( 'label' => 'Where you married or registered the PACS', 'type' => 'text', 'hint' => '' ),
            'together_since'     => array( 'label' => 'Living together since', 'type' => 'date', 'hint' => '' ),
            'how_met'            => array( 'label' => 'How you met and your life together', 'type' => 'textarea', 'hint' => 'Plain facts: when and where you met, where you have lived together, what you share.' ),
            'host_name'          => array( 'label' => 'Your host\'s full name', 'type' => 'text', 'hint' => '' ),
            'host_address'       => array( 'label' => 'Your host\'s address in France', 'type' => 'textarea', 'hint' => '' ),
            'host_relation'      => array( 'label' => 'How you know your host', 'type' => 'text', 'hint' => 'For example: my sister, a friend.' ),
            'host_from'          => array( 'label' => 'Staying with them from', 'type' => 'date', 'hint' => '' ),
            'host_until'         => array( 'label' => 'Until', 'type' => 'text', 'hint' => 'A date, or "until I find a rental".' ),
            'sponsor_name'       => array( 'label' => 'Your sponsor\'s full name', 'type' => 'text', 'hint' => 'Usually a parent.' ),
            'sponsor_address'    => array( 'label' => 'Your sponsor\'s address', 'type' => 'textarea', 'hint' => '' ),
            'sponsor_relation'   => array( 'label' => 'How your sponsor is related to you', 'type' => 'text', 'hint' => 'Their role, in French if you can: père (father), mère (mother), tante (aunt), oncle (uncle).' ),
            'sponsor_monthly_eur' => array( 'label' => 'Monthly amount your sponsor commits, in euros', 'type' => 'number', 'hint' => '' ),
            'employer_address'   => array( 'label' => 'Your employer\'s address', 'type' => 'textarea', 'hint' => '' ),
            'employed_since'     => array( 'label' => 'Employed there since', 'type' => 'date', 'hint' => '' ),
            'annual_salary_usd'  => array( 'label' => 'Annual salary in US dollars', 'type' => 'number', 'hint' => '' ),
            'signer_name'        => array( 'label' => 'Who signs for the employer', 'type' => 'text', 'hint' => 'HR or your manager.' ),
            'signer_title'       => array( 'label' => 'Their job title', 'type' => 'text', 'hint' => '' ),
        );
    }

    /**
     * What the form needs besides the questions: the defaults it shows, the
     * benchmark it measures the income against, and the exchange rate.
     */
    public static function form_context( $c ) {
        $stay = array( 'student' => 'the length of my programme', 'employee' => 'the length of my contract', 'talent_passport' => 'the length of my contract or project' );
        $joint = self::has_partner( $c ) && in_array( $c['visa'], array( 'visitor', 'retiree', 'other' ), true );
        return array(
            'defaults'  => array(
                'arrival_date' => $c['move_date'],
                'stay_length'  => $stay[ $c['visa'] ] ?? 'one year, renewable',
            ),
            'benchmark' => 'student' === $c['visa']
                ? array( 'monthly' => self::STUDENT_MONTHLY, 'since' => self::STUDENT_SINCE, 'label' => 'the student minimum', 'adults' => 1 )
                : array( 'monthly' => self::SMIC_NET_MONTHLY, 'since' => self::SMIC_SINCE, 'label' => 'the French net minimum wage', 'adults' => $joint ? 2 : 1 ),
            'fx'        => self::usd_rate(),
            'groups'    => self::field_groups(),
        );
    }

    /** Where each question sits in the form: the stay, the money, your words, other details. */
    public static function field_groups() {
        return array(
            'address_in_france' => 'stay', 'arrival_date' => 'stay', 'stay_length' => 'stay',
            'income_rows' => 'money', 'savings' => 'money',
            'plans' => 'words', 'how_met' => 'words', 'talent_detail' => 'words', 'business_activity' => 'words',
        );
    }

    /** Stay lengths that read correctly in "to live in France from [date], for …". */
    public static function stay_options() {
        $out = array();
        foreach ( array( 'one year, renewable', 'one year', 'several years, renewing each year', 'the length of my programme', 'the length of my contract', 'the length of my contract or project' ) as $v ) {
            $out[] = array( 'value' => $v, 'label' => ucfirst( $v ) );
        }
        return $out;
    }

    /**
     * US dollars per euro, from the European Central Bank's daily reference
     * rates, cached for twelve hours; the last good rate if the ECB cannot be
     * reached.
     *
     * @return array|null rate (USD per EUR), date (Y-m-d)
     */
    public static function usd_rate() {
        $cached = get_transient( 'framt_ecb_usd' );
        if ( is_array( $cached ) && ! empty( $cached['rate'] ) ) {
            return $cached;
        }
        $res = wp_remote_get( 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml', array( 'timeout' => 8 ) );
        if ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
            $xml = (string) wp_remote_retrieve_body( $res );
            if ( preg_match( "/time=['\"](\d{4}-\d{2}-\d{2})['\"]/", $xml, $d ) && preg_match( "/currency=['\"]USD['\"]\s+rate=['\"]([\d.]+)['\"]/", $xml, $r ) && (float) $r[1] > 0 ) {
                $value = array( 'rate' => (float) $r[1], 'date' => $d[1] );
                set_transient( 'framt_ecb_usd', $value, 12 * HOUR_IN_SECONDS );
                update_option( 'framt_ecb_usd_last', $value, false );
                return $value;
            }
        }
        $last = get_option( 'framt_ecb_usd_last' );
        return is_array( $last ) && ! empty( $last['rate'] ) ? $last : null;
    }

    /**
     * Asked before any letter, since every letter uses them.
     *
     * @return string[]
     */
    public static function first_fields() {
        return array( 'consulate', 'mailing_address' );
    }

    /**
     * Save answers: the profile keys to the profile, the rest to the
     * letters' own store, merged over what was there.
     *
     * @param int   $user_id Household owner.
     * @param array $input   Raw answers.
     * @return void
     */
    public static function save_answers( $user_id, $input ) {
        $clean   = self::clean_answers( $input );
        $current = get_user_meta( $user_id, self::ANSWERS_META, true );
        $current = is_array( $current ) ? $current : array();
        foreach ( $clean as $key => $value ) {
            if ( in_array( $key, self::PROFILE_KEYS, true ) ) {
                update_user_meta( $user_id, 'fra_' . $key, $value );
                update_user_meta( $user_id, 'fra_profile_updated', current_time( 'mysql' ) );
            } else {
                $current[ $key ] = $value;
            }
        }
        foreach ( self::PROFILE_KEYS as $key ) {
            unset( $current[ $key ] );
        }
        update_user_meta( $user_id, self::ANSWERS_META, $current );
    }

    /**
     * Sanitise answers against the field list; unknown keys are dropped.
     *
     * @param array $input Raw answers.
     * @return array
     */
    public static function clean_answers( $input ) {
        $fields = self::fields();
        $out    = array();
        foreach ( (array) $input as $key => $value ) {
            if ( ! isset( $fields[ $key ] ) || is_array( $value ) ) {
                continue;
            }
            $type = $fields[ $key ]['type'];
            if ( 'textarea' === $type ) {
                $out[ $key ] = sanitize_textarea_field( (string) $value );
            } elseif ( 'number' === $type ) {
                $num         = preg_replace( '/[^0-9.]/', '', str_replace( ',', '', (string) $value ) );
                $out[ $key ] = '' === $num ? '' : (string) round( (float) $num, 2 );
            } elseif ( 'date' === $type ) {
                $out[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? (string) $value : '';
            } elseif ( 'select' === $type ) {
                $allowed     = wp_list_pluck( $fields[ $key ]['options'], 'value' );
                $out[ $key ] = in_array( $value, $allowed, true ) ? $value : '';
            } elseif ( 'income' === $type ) {
                $rows = json_decode( (string) $value, true );
                $keep = array();
                foreach ( is_array( $rows ) ? array_slice( $rows, 0, 10 ) : array() as $row ) {
                    if ( ! is_array( $row ) ) {
                        continue;
                    }
                    $amount = round( (float) preg_replace( '/[^0-9.]/', '', str_replace( ',', '', (string) ( $row['amount'] ?? '' ) ) ), 2 );
                    $source = mb_substr( sanitize_text_field( (string) ( $row['source'] ?? '' ) ), 0, 60 );
                    if ( $amount <= 0 && '' === $source ) {
                        continue;
                    }
                    $keep[] = array(
                        'source'   => $source,
                        'amount'   => $amount,
                        'currency' => 'EUR' === ( $row['currency'] ?? '' ) ? 'EUR' : 'USD',
                        'per'      => 'year' === ( $row['per'] ?? '' ) ? 'year' : 'month',
                    );
                }
                $out[ $key ] = empty( $keep ) ? '' : wp_json_encode( $keep );
                continue;
            } elseif ( 'money' === $type ) {
                $m      = json_decode( (string) $value, true );
                $amount = is_array( $m ) ? round( (float) preg_replace( '/[^0-9.]/', '', str_replace( ',', '', (string) ( $m['amount'] ?? '' ) ) ), 2 ) : 0;
                $out[ $key ] = is_array( $m ) && '' !== (string) ( $m['amount'] ?? '' )
                    ? wp_json_encode( array( 'amount' => $amount, 'currency' => 'EUR' === ( $m['currency'] ?? '' ) ? 'EUR' : 'USD' ) )
                    : '';
                continue;
            } else {
                $out[ $key ] = sanitize_text_field( (string) $value );
            }
            $out[ $key ] = mb_substr( $out[ $key ], 0, 2000 );
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Catalogue
    // ------------------------------------------------------------------

    /**
     * The letters this member's route calls for, in filing order.
     *
     * @param array $c Context.
     * @return array[] type, person, title, why, fields, item_id, optional, guidance
     */
    public static function catalogue( $c ) {
        $v       = $c['visa'];
        $partner = self::has_partner( $c );
        $letters = array();
        if ( '' === $v || 'undecided' === $v ) {
            return $letters;
        }

        $add = function ( $type, $person = 'you', $optional = false ) use ( &$letters, $c ) {
            $def = self::definition( $type, $c, $person );
            if ( $def ) {
                $def['type']     = $type;
                $def['person']   = $person;
                $def['optional'] = $optional;
                $letters[]       = $def;
            }
        };

        $add( 'cover-letter' );

        if ( in_array( $v, array( 'visitor', 'retiree' ), true ) ) {
            $add( 'no-work-declaration' );
            if ( $partner ) {
                $add( 'no-work-declaration', 'partner' );
            }
            $add( 'resources-statement' );
            if ( 'yes_remote' === $c['work_fr'] ) {
                $add( 'employer-remote-letter' );
            }
        }
        if ( 'student' === $v ) {
            $add( 'resources-statement' );
            $add( 'sponsor-attestation', 'you', true );
        }
        if ( 'entrepreneur' === $v ) {
            $add( 'business-plan' );
            $add( 'resources-statement' );
        }
        if ( 'talent_passport' === $v && in_array( $c['talent'], array( 'founder', 'investor' ), true ) ) {
            $add( 'business-plan' );
            $add( 'resources-statement' );
        }
        if ( 'spouse_french' === $v ) {
            $add( 'relationship-statement' );
        }
        if ( 'other' === $v ) {
            $add( 'resources-statement', 'you', true );
        }
        $add( 'host-attestation', 'you', true );

        return $letters;
    }

    /**
     * What a letter is, which questions it asks, which dossier item it
     * answers and what the member must know before filing it.
     *
     * @param string $type   Letter type.
     * @param array  $c      Context.
     * @param string $person you|partner
     * @return array|null
     */
    public static function definition( $type, $c, $person = 'you' ) {
        $v       = $c['visa'];
        $partner = self::has_partner( $c );
        $student = 'student' === $v;
        $smic    = self::money( self::SMIC_NET_MONTHLY );

        switch ( $type ) {
            case 'cover-letter':
                $fields = array( 'consulate', 'mailing_address', 'address_in_france', 'arrival_date', 'stay_length' );
                if ( in_array( $v, array( 'visitor', 'retiree', 'other' ), true ) ) {
                    $fields = array_merge( $fields, array( 'income_rows', 'savings', 'plans' ) );
                } elseif ( $student ) {
                    $fields = array_merge( $fields, array( 'school_name', 'programme', 'programme_start', 'programme_end', 'income_rows', 'savings' ) );
                } elseif ( 'employee' === $v ) {
                    $fields[] = 'contract_start';
                } elseif ( 'talent_passport' === $v ) {
                    $fields[] = 'talent_detail';
                } elseif ( 'entrepreneur' === $v ) {
                    $fields = array_merge( $fields, array( 'business_name', 'business_activity', 'income_rows', 'savings' ) );
                } elseif ( 'spouse_french' === $v ) {
                    $fields = array_merge( $fields, array( 'marriage_date', 'marriage_place' ) );
                }
                return array(
                    'title'    => 'Cover letter',
                    'why'      => 'One page that tells the officer who you are, why France, where you will live, how you support yourself and what you will do with your time.',
                    'fields'   => $fields,
                    'item_id'  => 'cover-letter',
                    'guidance' => array_values( array_filter( array(
                        'Every figure and date must agree with the documents behind it: the same address as your accommodation proof, the same income as your statements.',
                        'US consulates accept English. A short French version alongside is a courtesy, not a requirement.',
                        $partner ? 'One letter covers you both. Each adult still signs their own application form.' : '',
                        in_array( $v, array( 'visitor', 'retiree' ), true ) ? 'If your income is close to the benchmark, the letter is where you explain your budget and your savings.' : '',
                        'Sign and date it by hand after printing.',
                    ) ) ),
                );

            case 'no-work-declaration':
                if ( 'partner' === $person && ! $partner ) {
                    return null;
                }
                $remote = 'you' === $person && 'yes_remote' === $c['work_fr'];
                return array(
                    'title'    => 'partner' === $person ? 'Declaration not to work · ' . self::first_word( $c['spouse'] ) : 'Declaration not to work',
                    'why'      => 'The visitor route requires each adult to sign an attestation sur l\'honneur that they will not work in France. French with an English translation below.',
                    'fields'   => 'partner' === $person ? array( 'partner_birth_place', 'partner_passport' ) : array( 'birth_place' ),
                    'item_id'  => 'declaration-no-work',
                    'guidance' => array_values( array_filter( array(
                        'Each adult signs their own. Sign and date it by hand after printing.',
                        'Working for a French employer or French clients is not allowed on this visa.',
                        $remote ? 'You said you will work remotely. In a written answer on 23 June 2026 the Interior Ministry indicated remote work for a foreign employer with no link to the French market may be compatible with visitor status. The guidance is new and consulates apply it unevenly, so this version says exactly that, and your employer letter backs it up. Read the visitor guide before you sign.' : '',
                    ) ) ),
                );

            case 'resources-statement':
                return array(
                    'title'    => 'Statement of resources',
                    'why'      => $student
                        ? 'A one-page summary of how your studies are funded, matching the statements, scholarship or sponsor letter in your file.'
                        : 'A one-page table of your monthly income and savings, matching the statements in your file. Officers read hundreds of files; a summary that matches gets read.',
                    'fields'   => array( 'income_rows', 'savings' ),
                    'item_id'  => $student ? 'proof-funds-studies' : 'proof-funds',
                    'guidance' => $student
                        ? array(
                            sprintf( 'The student minimum is %s a month from %s, indexed to the minimum wage. Check the figure on your France-Visas checklist before you file.', self::money( self::STUDENT_MONTHLY ), self::STUDENT_SINCE ),
                            'Proof can be bank statements, a scholarship award letter, or a sponsor\'s attestation de prise en charge with their bank records.',
                            'Use one exchange rate for the whole file and say which.',
                        )
                        : array(
                            sprintf( 'Consulates benchmark resources against the French net minimum wage, %s a month since %s, per adult. It is a benchmark, not a fixed legal floor, and each consulate applies its own standard.', $smic, self::SMIC_SINCE ),
                            'Many applicants show more than the benchmark in income, or about a year of it in savings, so nobody has to ask.',
                            'Attach three months of statements for every account listed, and the proof of each income source.',
                            'Use one exchange rate for the whole file and say which.',
                        ),
                );

            case 'employer-remote-letter':
                return array(
                    'title'    => 'Employer letter for remote work',
                    'why'      => 'Some consulates now ask remote workers for a letter from the employer confirming the arrangement and that the work has no link to France. This is the draft your employer puts on letterhead and signs.',
                    'fields'   => array( 'employer_address', 'employed_since', 'annual_salary_usd', 'signer_name', 'signer_title' ),
                    'item_id'  => '',
                    'guidance' => array(
                        'Send it to your employer to print on company letterhead, sign and date. A letter you signed yourself carries no weight.',
                        'It must be true: a US employer, no French clients, nothing delivered in France. If any of that is not so, talk to an immigration lawyer before you apply.',
                        'Working from France can also create French tax and social-charge obligations for you and your employer. The tax guide on remote work covers it.',
                    ),
                );

            case 'sponsor-attestation':
                return array(
                    'title'    => 'Sponsor\'s attestation de prise en charge',
                    'why'      => 'When a parent or relative pays for your studies, they sign a statement that they will support you each month. French with an English translation below.',
                    'fields'   => array( 'sponsor_name', 'sponsor_address', 'sponsor_relation', 'sponsor_monthly_eur', 'programme_start', 'programme_end' ),
                    'item_id'  => 'proof-funds-studies',
                    'guidance' => array(
                        'Your sponsor signs and dates it and attaches a copy of their ID and their last three months of bank statements.',
                        sprintf( 'The monthly amount should reach the student minimum, %s a month from %s, on its own or with your own funds.', self::money( self::STUDENT_MONTHLY ), self::STUDENT_SINCE ),
                    ),
                );

            case 'host-attestation':
                return array(
                    'title'    => 'Host\'s attestation d\'hébergement',
                    'why'      => 'Only if you will stay with someone at first. Your host signs that you will live with them; it stands in for a lease as your proof of accommodation.',
                    'fields'   => array( 'host_name', 'host_address', 'host_relation', 'host_from', 'host_until' ),
                    'item_id'  => 'proof-accommodation',
                    'guidance' => array(
                        'Your host signs and dates it and attaches a copy of their ID and a recent proof of their address, such as a utility bill or the deed.',
                        'This is a letter from your host, not the mairie\'s attestation d\'accueil, which is for short visits of under three months.',
                        'The address must match the one in your cover letter.',
                    ),
                );

            case 'relationship-statement':
                return array(
                    'title'    => 'Statement of your life together',
                    'why'      => 'The spouse route asks for proof the relationship is genuine. This statement ties together the evidence you attach: shared addresses, accounts, photos, travel.',
                    'fields'   => array( 'marriage_date', 'marriage_place', 'together_since', 'how_met' ),
                    'item_id'  => 'relationship-proof',
                    'guidance' => array_values( array_filter( array(
                        'Both of you sign it.',
                        'Attach the evidence it mentions: a joint lease or deed, joint accounts, photos over time, travel together.',
                        'pacs' === $c['relationship'] ? 'A PACS supports this route only after 12 months of registered, continuous life together; the administration examines the reality of the shared life.' : '',
                        'If you married outside France, your spouse must have the marriage transcribed in the French civil registry. Ask the consulate what it needs.',
                    ) ) ),
                );

            case 'business-plan':
                return array(
                    'title'    => 'talent_passport' === $v ? 'Business plan outline' : 'Business plan and viability outline',
                    'why'      => 'talent_passport' === $v
                        ? 'The founder category rests on a business plan the consulate can follow and proof of the funds behind it.'
                        : 'The entrepreneur route asks for a business plan showing the business is viable and will pay you at least the minimum wage.',
                    'fields'   => array( 'business_name', 'business_activity', 'investment_eur' ),
                    'item_id'  => 'talent_passport' === $v ? 'category-proof' : 'business-plan',
                    'guidance' => array_values( array_filter( array(
                        'This is an outline with your details filled in and prompts where your figures go. Replace every bracketed line before filing.',
                        'entrepreneur' === $v ? sprintf( 'Projected income for you should reach at least the net minimum wage, %s a month since %s.', $smic, self::SMIC_SINCE ) : '',
                        'talent_passport' === $v ? '€30,000 is the commonly cited funding benchmark for the founder category; a recognised incubator or Bpifrance backing strengthens the file.' : '',
                        'Requirements vary by consulate and by profession. A regulated profession also needs proof of qualification.',
                    ) ) ),
                );
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Letters
    // ------------------------------------------------------------------

    /**
     * Draft a letter.
     *
     * @param string $type   Letter type.
     * @param array  $c      Context.
     * @param string $person you|partner
     * @return array|null title, text, missing, filename
     */
    public static function build( $type, $c, $person = 'you' ) {
        $def = self::definition( $type, $c, $person );
        if ( ! $def ) {
            return null;
        }
        $missing = array();
        $a       = function ( $key, $fallback = '' ) use ( $c, &$missing ) {
            $value = trim( (string) ( $c['answers'][ $key ] ?? '' ) );
            if ( '' === $value ) {
                $value = (string) $fallback;
            }
            if ( '' === $value ) {
                $label              = self::fields()[ $key ]['label'] ?? $key;
                $missing[ $key ]    = $label;
                return '[' . $label . ']';
            }
            return $value;
        };
        $need = function ( $value, $label ) use ( &$missing ) {
            if ( '' === trim( (string) $value ) ) {
                $missing[ $label ] = $label;
                return '[' . $label . ']';
            }
            return $value;
        };

        $method = 'letter_' . str_replace( '-', '_', $type );
        $text   = self::tidy( self::$method( $c, $a, $need, $person ) );
        $name   = 'partner' === $person ? $c['spouse'] : self::full_name( $c );

        return array(
            'title'    => $def['title'],
            'text'     => $text,
            'missing'  => array_values( $missing ),
            'filename' => sanitize_file_name( $def['title'] . ( '' !== $name ? ' - ' . $name : '' ) . '.pdf' ),
        );
    }

    private static function letter_cover_letter( $c, $a, $need, $person ) {
        $v       = $c['visa'];
        $name    = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $partner = self::has_partner( $c );
        $arrival = $a( 'arrival_date', $c['move_date'] );
        $arrival = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $arrival ) ? self::date_en( $arrival ) : $arrival;
        $stay_default = array(
            'student'         => 'the length of my programme',
            'employee'        => 'the length of my contract',
            'talent_passport' => 'the length of my contract or project',
        );
        $stay    = $a( 'stay_length', $stay_default[ $v ] ?? 'one year, renewable' );
        $place   = $a( 'address_in_france' );
        $consul  = trim( (string) ( $c['answers']['consulate'] ?? '' ) );
        $town    = trim( $c['city'] . ( $c['state'] ? ', ' . $c['state'] : '' ), ', ' );

        $route = array(
            'visitor'         => 'long-stay visitor visa (VLS-TS « visiteur »)',
            'retiree'         => 'long-stay visitor visa (VLS-TS « visiteur »)',
            'student'         => 'long-stay student visa (VLS-TS « étudiant »)',
            'employee'        => 'long-stay work visa (VLS-TS « salarié »)',
            'talent_passport' => 'long-stay visa « passeport talent »',
            'entrepreneur'    => 'long-stay visa « entrepreneur / profession libérale »',
            'spouse_french'   => 'long-stay visa as the spouse of a French national (VLS-TS « vie privée et familiale »)',
            'family'          => 'long-stay visa for family reunification',
            'other'           => 'long-stay visa',
        );

        $l   = array();
        $l[] = $name;
        $mail = trim( (string) ( $c['answers']['mailing_address'] ?? '' ) );
        if ( '' !== $mail ) {
            foreach ( preg_split( '/\r?\n/', $mail ) as $mail_line ) {
                if ( '' !== trim( $mail_line ) ) {
                    $l[] = trim( $mail_line );
                }
            }
        } else {
            $l[] = $a( 'mailing_address' );
            if ( '' !== $town ) {
                $l[] = $town;
            }
        }
        if ( $c['email'] ) {
            $l[] = $c['email'];
        }
        $l[] = '';
        $l[] = self::date_en( current_time( 'Y-m-d' ) );
        $l[] = '';
        $l[] = '' !== $consul ? 'Consulate General of France in ' . $consul : 'Consulate General of France';
        $l[] = 'Visa Section';
        $l[] = '';
        $l[] = '## Re: Application for a ' . ( $route[ $v ] ?? 'long-stay visa' ) . ( $c['passport'] ? ', passport no. ' . $c['passport'] : '' );
        $l[] = '';
        $l[] = 'Dear Sir or Madam,';
        $l[] = '';

        // Only routes where both adults apply for the same visa write as "we".
        $joint = $partner && in_array( $v, array( 'visitor', 'retiree', 'other' ), true );
        $we    = $joint ? 'We' : 'I';
        $our   = $joint ? 'our' : 'my';
        $who   = 'I';
        if ( $joint ) {
            $who = 'My ' . ( 'pacs' === $c['relationship'] ? 'partner' : 'spouse' ) . ', ' . $c['spouse'] . ', and I';
        }
        $opening = sprintf( '%s are applying for a %s to live in France from %s, for %s.', $who, $route[ $v ] ?? 'long-stay visa', $arrival, $stay );
        if ( 'I' === $who ) {
            $opening = sprintf( 'I am applying for a %s to live in France from %s, for %s.', $route[ $v ] ?? 'long-stay visa', $arrival, $stay );
        }
        if ( $c['children'] > 0 ) {
            $opening .= sprintf( ' %s %s will come with %s.', ucfirst( $our ), 1 === $c['children'] ? 'child' : $c['children'] . ' children', $joint ? 'us' : 'me' );
        }
        $l[] = $opening;
        $l[] = '';

        switch ( $v ) {
            case 'visitor':
            case 'retiree':
            case 'other':
                $l[] = 'retiree' === $v || 'retired' === $c['employment']
                    ? ( $joint ? 'We are retired and wish to spend this part of our lives in France. ' : 'I am retired and wish to spend this part of my life in France. ' ) . $a( 'plans' )
                    : sprintf( '%s wish to live in France and to take part in daily life here. ', $we ) . $a( 'plans' );
                $l[] = '';
                $l[] = sprintf( '%s will live at %s.', $we, self::one_line( $place ) );
                $l[] = '';
                $l[] = self::resources_sentence( $c, $a );
                $l[] = '';
                if ( 'yes_remote' === $c['work_fr'] && 'other' !== $v ) {
                    $l[] = sprintf( 'I will continue to work remotely for my employer in the United States, %s. The company has no establishment and no clients in France and nothing I do is delivered on French soil; the enclosed letter from my employer confirms this. I will not work for any French employer or French client.', $need( $c['employer'], 'Employer name (profile)' ) );
                } elseif ( 'other' !== $v ) {
                    $l[] = $joint
                        ? 'Neither of us will carry out any professional activity in France during our stay, as stated in the enclosed signed declarations.'
                        : 'I will not carry out any professional activity in France during my stay, as stated in the enclosed signed declaration.';
                }
                break;
            case 'student':
                $l[] = sprintf( 'I have been admitted to %s at %s, starting on %s and ending on %s. The attestation d\'inscription is enclosed.', $a( 'programme' ), $a( 'school_name' ), self::maybe_date( $a( 'programme_start' ) ), self::maybe_date( $a( 'programme_end' ) ) );
                $l[] = '';
                $l[] = sprintf( 'I will live at %s.', self::one_line( $place ) );
                $l[] = '';
                $l[] = self::resources_sentence( $c, $a );
                break;
            case 'employee':
                $l[] = sprintf( 'I have been offered the position of %s with %s, starting on %s. The signed contract and the employer\'s work authorisation are enclosed.', $need( $c['job_title'], 'Job title (profile)' ), $need( $c['employer'], 'Employer name (profile)' ), self::maybe_date( $a( 'contract_start' ) ) );
                $l[] = '';
                $l[] = sprintf( 'I will live at %s.', self::one_line( $place ) );
                break;
            case 'talent_passport':
                $l[] = sprintf( 'I am applying in the %s category. %s', self::talent_label( $c['talent'] ), $a( 'talent_detail' ) );
                $l[] = '';
                $l[] = sprintf( 'I will live at %s.', self::one_line( $place ) );
                if ( $partner ) {
                    $l[] = '';
                    $l[] = 'My family is applying at the same time under the « passeport talent – famille » status.';
                }
                break;
            case 'entrepreneur':
                $l[] = sprintf( 'I intend to set up %s in France. %s The business plan and the proof of funds are enclosed.', $a( 'business_name' ), $a( 'business_activity' ) );
                $l[] = '';
                $l[] = sprintf( 'I will live at %s.', self::one_line( $place ) );
                $l[] = '';
                $l[] = self::resources_sentence( $c, $a );
                break;
            case 'spouse_french':
                $l[] = sprintf( 'I am the %s of %s, a French national. We %s on %s in %s.', 'pacs' === $c['relationship'] ? 'partner' : 'spouse', $need( $c['spouse'], 'Your spouse\'s name (profile)' ), 'pacs' === $c['relationship'] ? 'registered our PACS' : 'married', self::maybe_date( $a( 'marriage_date' ) ), $a( 'marriage_place' ) );
                $l[] = '';
                $l[] = sprintf( 'We will live together at %s.', self::one_line( $place ) );
                break;
            case 'family':
                $l[] = 'My family member in France has obtained approval for family reunification from OFII; the approval is enclosed.';
                $l[] = '';
                $l[] = sprintf( 'I will live at %s.', self::one_line( $place ) );
                break;
        }

        $l[] = '';
        $l[] = sprintf( '%s the documents listed below and remain available for any further information you may need.', $joint ? 'We enclose' : 'I enclose' );
        $l[] = '';
        foreach ( self::enclosures( $c ) as $item ) {
            $l[] = '- ' . $item;
        }
        $l[] = '';
        $l[] = 'Yours faithfully,';
        $l[] = '[signature] ' . $name;
        if ( $joint ) {
            $l[] = '[signature] ' . $c['spouse'];
        }
        return implode( "\n", $l );
    }

    private static function letter_no_work_declaration( $c, $a, $need, $person ) {
        $is_partner = 'partner' === $person;
        $name       = $is_partner ? $need( $c['spouse'], 'Your partner\'s name (profile)' ) : $need( self::full_name( $c ), 'Your legal name (profile)' );
        $dob_raw    = $is_partner ? $c['spouse_dob'] : $c['dob'];
        $dob_fr     = $dob_raw ? self::date_fr( $dob_raw ) : $need( '', $is_partner ? 'Your partner\'s date of birth (profile)' : 'Date of birth (profile)' );
        $dob_en     = $dob_raw ? self::date_en( $dob_raw ) : $dob_fr;
        $born       = $is_partner ? $a( 'partner_birth_place' ) : $a( 'birth_place', self::birth_place_default( $c ) );
        $passport   = $is_partner ? $a( 'partner_passport' ) : $need( $c['passport'], 'Passport number (profile)' );
        $nat        = self::nationality( $c['nationality'] );
        $remote     = ! $is_partner && 'yes_remote' === $c['work_fr'];
        $town       = $c['city'] ?: '[ville]';

        $l   = array();
        $l[] = '# Attestation sur l\'honneur';
        $l[] = '';
        $l[] = sprintf( 'Je soussigné(e), %s, né(e) le %s à %s, de nationalité %s, titulaire du passeport n° %s,', $name, $dob_fr, $born, $nat['fr'], $passport );
        $l[] = '';
        if ( $remote ) {
            $l[] = sprintf( 'déclare sur l\'honneur que, pendant toute la durée de mon séjour en France sous couvert d\'un visa de long séjour « visiteur », je n\'exercerai aucune activité professionnelle pour le compte d\'un employeur ou de clients établis en France. Je poursuivrai uniquement, en télétravail, mon emploi auprès de mon employeur établi aux États-Unis, %s, sans lien avec le marché français.', $need( $c['employer'], 'Employer name (profile)' ) );
        } else {
            $l[] = 'déclare sur l\'honneur n\'exercer aucune activité professionnelle en France pendant toute la durée de mon séjour sous couvert d\'un visa de long séjour « visiteur ».';
        }
        $l[] = '';
        $l[] = sprintf( 'Fait à %s, le %s.', $town, self::date_fr( current_time( 'Y-m-d' ) ) );
        $l[] = '[signature] Signature';
        $l[] = '';
        $l[] = '---';
        $l[] = '## Translation';
        $l[] = sprintf( 'I, the undersigned, %s, born on %s in %s, a %s national, holder of passport no. %s,', $name, $dob_en, $born, $nat['en'], $passport );
        $l[] = '';
        if ( $remote ) {
            $l[] = 'declare on my honour that, throughout my stay in France on a long-stay « visiteur » visa, I will not carry out any professional activity for an employer or clients established in France. I will only continue, remotely, my employment with my employer established in the United States, with no link to the French market.';
        } else {
            $l[] = 'declare on my honour that I will not carry out any professional activity in France throughout my stay on a long-stay « visiteur » visa.';
        }
        $l[] = '';
        $l[] = sprintf( 'Signed at %s, on %s.', $c['city'] ?: '[city]', self::date_en( current_time( 'Y-m-d' ) ) );
        return implode( "\n", $l );
    }

    private static function letter_resources_statement( $c, $a, $need, $person ) {
        $name    = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $lines   = self::income_lines( $c );
        $savings = self::savings_amount( $c );
        $total   = array_sum( array_map( function ( $r ) { return (float) $r['eur']; }, $lines ) );
        $any_usd = ( $savings && $savings['usd'] && ! $savings['zero'] ) || in_array( true, wp_list_pluck( $lines, 'usd' ), true );
        $joint   = self::has_partner( $c ) && in_array( $c['visa'], array( 'visitor', 'retiree', 'other' ), true );

        $l   = array();
        $l[] = '# Statement of financial resources';
        $l[] = $name . ( $joint ? ' and ' . $c['spouse'] : '' );
        $l[] = 'Prepared ' . self::date_en( current_time( 'Y-m-d' ) );
        $l[] = '';
        $l[] = '## Monthly income';
        if ( empty( $lines ) ) {
            $a( 'income_rows' );
            $l[] = '- [Source]: [amount] a month';
        }
        foreach ( $lines as $row ) {
            $l[] = sprintf( '- %s: %s a month', $row['source'], self::both( $row['shown'], $row['eur'], $row['usd'] ) );
        }
        if ( ! empty( $lines ) ) {
            $l[] = sprintf( 'Total: about %s a month, %s a year.', self::money( round( $total ) ), self::money( round( $total * 12 ) ) );
        }
        $l[] = '';
        $l[] = '## Savings and investments';
        if ( null === $savings ) {
            $l[] = $a( 'savings' );
        } elseif ( $savings['zero'] ) {
            $l[] = 'None relied on.';
        } else {
            $l[] = sprintf( '%s available, as shown on the enclosed statements.', self::both( $savings['shown'], $savings['eur'], $savings['usd'] ) );
        }
        $l[] = '';
        $l[] = '## Proof enclosed';
        $l[] = '- The last three months of statements for every account listed';
        $l[] = '- Proof of each income source (award letters, pension statements, pay slips or an employer letter)';
        if ( 'student' === $c['visa'] ) {
            $l[] = '- Where a sponsor contributes, their signed attestation de prise en charge, ID and bank statements';
        }
        if ( $any_usd ) {
            $l[] = '';
            $l[] = '> ' . self::fx_line();
        }
        $l[] = '[signature] ' . $name;
        if ( $joint ) {
            $l[] = '[signature] ' . $c['spouse'];
        }
        return implode( "\n", $l );
    }

    private static function letter_employer_remote_letter( $c, $a, $need, $person ) {
        $name     = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $employer = $need( $c['employer'], 'Employer name (profile)' );
        $salary   = (float) ( $c['answers']['annual_salary_usd'] ?? 0 );

        $l   = array();
        $l[] = '// For your employer: print on company letterhead, date, sign. Lines starting with // never print.';
        $l[] = '';
        $l[] = $employer;
        $l[] = $a( 'employer_address' );
        $l[] = '';
        $l[] = '[Date]';
        $l[] = '';
        $l[] = 'To the Consulate General of France, Visa Section';
        $l[] = '';
        $l[] = '## Re: Employment of ' . $name;
        $l[] = '';
        $l[] = 'Dear Sir or Madam,';
        $l[] = '';
        $l[] = sprintf( 'This letter confirms that %s has been employed by %s since %s as %s, on a permanent basis, with an annual salary of %s.', $name, $employer, self::maybe_date( $a( 'employed_since' ) ), $need( $c['job_title'], 'Job title (profile)' ), $salary > 0 ? '$' . number_format( $salary, 0, '.', ',' ) : $a( 'annual_salary_usd' ) );
        $l[] = '';
        $l[] = sprintf( '%s has our agreement to carry out this work remotely while living in France. The position remains attached to our operations in the United States. %s has no establishment, subsidiary or clients in France, and the work performed has no connection with the French market.', self::first_word( $name ), $employer );
        $l[] = '';
        $l[] = 'Please contact the undersigned if you need any further information.';
        $l[] = '';
        $l[] = 'Yours faithfully,';
        $l[] = '[signature] ' . $a( 'signer_name' ) . ', ' . $a( 'signer_title' );
        return implode( "\n", $l );
    }

    private static function letter_sponsor_attestation( $c, $a, $need, $person ) {
        $student  = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $sponsor  = $a( 'sponsor_name' );
        $address  = self::one_line( $a( 'sponsor_address' ) );
        $monthly  = (float) ( $c['answers']['sponsor_monthly_eur'] ?? 0 );
        $amount   = $monthly > 0 ? self::money( $monthly ) : $a( 'sponsor_monthly_eur' );
        $from     = self::maybe_date( $a( 'programme_start' ), true );
        $until    = self::maybe_date( $a( 'programme_end' ), true );
        $dob_fr   = $c['dob'] ? self::date_fr( $c['dob'] ) : $need( '', 'Date of birth (profile)' );

        $l   = array();
        $l[] = '# Attestation de prise en charge';
        $l[] = '';
        $l[] = sprintf( 'Je soussigné(e), %s, demeurant %s,', $sponsor, $address );
        $l[] = '';
        $l[] = sprintf( 'm\'engage, en qualité de %s, à prendre en charge financièrement %s, né(e) le %s, à hauteur de %s par mois, pendant toute la durée de ses études en France, du %s au %s.', $a( 'sponsor_relation' ), $student, $dob_fr, $amount, $from, $until );
        $l[] = '';
        $l[] = 'Je joins la copie de ma pièce d\'identité et mes trois derniers relevés bancaires.';
        $l[] = '';
        $l[] = sprintf( 'Fait à %s, le %s.', '[ville]', self::date_fr( current_time( 'Y-m-d' ) ) );
        $l[] = '[signature] Signature';
        $l[] = '';
        $l[] = '---';
        $l[] = '## Translation';
        $l[] = sprintf( 'I, the undersigned, %s, residing at %s, related to %s as %s, undertake to support them financially with %s per month throughout their studies in France, from %s to %s.', $sponsor, $address, $student, $a( 'sponsor_relation' ), $amount, self::maybe_date( $a( 'programme_start' ) ), self::maybe_date( $a( 'programme_end' ) ) );
        $l[] = '';
        $l[] = 'I enclose a copy of my ID and my last three bank statements.';
        return implode( "\n", $l );
    }

    private static function letter_host_attestation( $c, $a, $need, $person ) {
        $guest   = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $host    = $a( 'host_name' );
        $address = self::one_line( $a( 'host_address' ) );
        $from_fr = self::maybe_date( $a( 'host_from' ), true );
        $until   = $a( 'host_until' );
        $dob_fr  = $c['dob'] ? self::date_fr( $c['dob'] ) : $need( '', 'Date of birth (profile)' );
        $others  = self::has_partner( $c ) ? ', ainsi que ' . $c['spouse'] : '';
        $kids    = $c['children'] > 0 ? ( $c['children'] > 1 ? ' et leurs enfants' : ' et leur enfant' ) : '';

        $l   = array();
        $l[] = '# Attestation d\'hébergement';
        $l[] = '';
        $l[] = sprintf( 'Je soussigné(e), %s, demeurant %s,', $host, $address );
        $l[] = '';
        $l[] = sprintf( 'atteste sur l\'honneur héberger à mon domicile %s, né(e) le %s%s%s, à compter du %s, jusqu\'à %s.', $guest, $dob_fr, $others, $kids, $from_fr, $until );
        $l[] = '';
        $l[] = 'Je joins la copie de ma pièce d\'identité et un justificatif de domicile récent.';
        $l[] = '';
        $l[] = sprintf( 'Fait à %s, le %s.', '[ville]', self::date_fr( current_time( 'Y-m-d' ) ) );
        $l[] = '[signature] Signature';
        $l[] = '';
        $l[] = '---';
        $l[] = '## Translation';
        $l[] = sprintf( 'I, the undersigned, %s, residing at %s, declare on my honour that I will host %s (%s)%s%s at my home from %s, until %s.', $host, $address, $guest, $a( 'host_relation' ), self::has_partner( $c ) ? ', with ' . $c['spouse'] : '', $c['children'] > 0 ? ' and their children' : '', self::maybe_date( $a( 'host_from' ) ), $until );
        $l[] = '';
        $l[] = 'I enclose a copy of my ID and a recent proof of address.';
        return implode( "\n", $l );
    }

    private static function letter_relationship_statement( $c, $a, $need, $person ) {
        $name   = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $spouse = $need( $c['spouse'], 'Your spouse\'s name (profile)' );
        $pacs   = 'pacs' === $c['relationship'];

        $l   = array();
        $l[] = '# Statement of our life together';
        $l[] = $name . ' and ' . $spouse;
        $l[] = '';
        $l[] = sprintf( 'We %s on %s in %s, and have lived together since %s.', $pacs ? 'registered our PACS' : 'married', self::maybe_date( $a( 'marriage_date' ) ), $a( 'marriage_place' ), self::maybe_date( $a( 'together_since' ) ) );
        $l[] = '';
        $l[] = $a( 'how_met' );
        $l[] = '';
        $l[] = sprintf( '%s is a French national. We intend to live together in France.', self::first_word( $spouse ) );
        $l[] = '';
        $l[] = '## Evidence enclosed';
        $l[] = '- ' . ( $pacs ? 'PACS registration certificate' : 'Marriage certificate, apostilled' );
        $l[] = '- Proof of shared address: [joint lease, deed or utility bills]';
        $l[] = '- Joint financial records: [joint account statements]';
        $l[] = '- Photographs and travel together over time';
        $l[] = '';
        $l[] = '[signature] ' . $name;
        $l[] = '[signature] ' . $spouse;
        return implode( "\n", $l );
    }

    private static function letter_business_plan( $c, $a, $need, $person ) {
        $name     = $need( self::full_name( $c ), 'Your legal name (profile)' );
        $invest   = (float) ( $c['answers']['investment_eur'] ?? 0 );
        $founder  = 'talent_passport' === $c['visa'];

        $l   = array();
        $l[] = '# ' . ( $founder ? 'Business plan' : 'Business plan and viability' ) . ': ' . $a( 'business_name' );
        $l[] = 'Prepared by ' . $name . ', ' . self::date_en( current_time( 'Y-m-d' ) );
        $l[] = '// Replace every line in brackets with your own figures. Lines starting with // are notes to you and never print.';
        $l[] = '';
        $l[] = '## 1. Summary';
        $l[] = $a( 'business_activity' );
        $l[] = '';
        $l[] = '## 2. The founder';
        $l[] = '[Your training and experience in this field, with the diplomas and references enclosed.]';
        $l[] = '';
        $l[] = '## 3. Market and customers';
        $l[] = '[Who buys, where in France, the competition, and why customers will choose you.]';
        $l[] = '';
        $l[] = '## 4. Legal form and premises';
        $l[] = '[Legal structure (for example micro-entreprise, SAS, SARL), where the business will be registered, premises or home office.]';
        $l[] = '';
        $l[] = '## 5. Financing';
        $l[] = $invest > 0 ? sprintf( 'Funds invested by the founder: %s, shown on the enclosed statements.', self::money( $invest ) ) : 'Funds invested by the founder: ' . $a( 'investment_eur' );
        $l[] = '[Other financing: loans, grants, incubator or Bpifrance support.]';
        $l[] = '';
        $l[] = '## 6. Three-year projections';
        $l[] = '- Year 1: revenue [ ], costs [ ], income for the founder [ ]';
        $l[] = '- Year 2: revenue [ ], costs [ ], income for the founder [ ]';
        $l[] = '- Year 3: revenue [ ], costs [ ], income for the founder [ ]';
        if ( ! $founder ) {
            $l[] = sprintf( '// The projected income for the founder should reach at least the French net minimum wage, %s a month since %s.', self::money( self::SMIC_NET_MONTHLY ), self::SMIC_SINCE );
        }
        $l[] = '';
        $l[] = '## 7. Qualifications and permits';
        $l[] = '[For a regulated profession, the French recognition of your qualification; any licence the activity needs.]';
        return implode( "\n", $l );
    }

    // ------------------------------------------------------------------
    // Pieces
    // ------------------------------------------------------------------

    private static function resources_sentence( $c, $a ) {
        $lines   = self::income_lines( $c );
        $total   = array_sum( array_map( function ( $r ) { return (float) $r['eur']; }, $lines ) );
        $savings = self::savings_amount( $c );
        $has_sav = $savings && ! $savings['zero'];
        $who     = self::has_partner( $c ) && in_array( $c['visa'], array( 'visitor', 'retiree', 'other' ), true ) ? 'We support ourselves' : 'I support myself';
        if ( empty( $lines ) && ! $has_sav ) {
            return $who . ' from ' . $a( 'income_rows', $c['income'] ) . '. The enclosed statement of resources and bank statements show the details.';
        }
        $parts = array();
        foreach ( $lines as $row ) {
            $parts[] = self::lower_first( $row['source'] );
        }
        $out = $who;
        if ( $total > 0 ) {
            $out .= sprintf( ' from %s, a total of about %s a month', self::list_words( array_unique( $parts ) ), self::money( round( $total ) ) );
        }
        if ( $has_sav ) {
            $out .= ( $total > 0 ? ', and hold ' : ' from savings of ' ) . sprintf( '%s in savings and investments', self::both( $savings['shown'], $savings['eur'], $savings['usd'] ) );
        }
        return $out . '. The enclosed statement of resources and bank statements show the details.';
    }

    /** "Social Security" stays capitalised; "Rental income" becomes "rental income". */
    private static function lower_first( $text ) {
        $keep = array( 'Social Security', 'IRA', '401(k)', 'US' );
        foreach ( $keep as $k ) {
            if ( 0 === strpos( $text, $k ) ) {
                return $text;
            }
        }
        return mb_strtolower( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 );
    }

    /**
     * The member's income, one row per source, each as a monthly amount in
     * euros alongside what they typed. Dollars convert at the ECB rate; a
     * row that cannot be converted keeps eur = null.
     *
     * @return array[] source, eur (monthly), shown (the original, monthly)
     */
    private static function income_lines( $c ) {
        $rows = array();
        $raw  = json_decode( (string) ( $c['answers']['income_rows'] ?? '' ), true );
        if ( is_array( $raw ) ) {
            $fx = self::usd_rate();
            foreach ( $raw as $row ) {
                $amount = (float) ( $row['amount'] ?? 0 );
                if ( $amount <= 0 ) {
                    continue;
                }
                $monthly = 'year' === ( $row['per'] ?? 'month' ) ? $amount / 12 : $amount;
                $usd     = 'USD' === ( $row['currency'] ?? 'USD' );
                $rows[]  = array(
                    'source' => '' !== trim( (string) ( $row['source'] ?? '' ) ) ? trim( $row['source'] ) : 'Income',
                    'eur'    => $usd ? ( $fx ? $monthly / $fx['rate'] : null ) : $monthly,
                    'shown'  => $usd ? '$' . number_format( $monthly, 0, '.', ',' ) : self::money( $monthly ),
                    'usd'    => $usd,
                );
            }
            return $rows;
        }
        // Answers saved before 2.9.28: "Source: amount" lines in euros.
        foreach ( preg_split( '/\r?\n/', (string) ( $c['answers']['income_lines'] ?? '' ) ) as $line ) {
            if ( preg_match( '/^\s*(.+?)\s*[:\-–]\s*€?\s*([\d.,\s]+)\s*(€|eur|euros)?\s*$/iu', $line, $m ) ) {
                $amount = (float) str_replace( array( ',', ' ', "\xC2\xA0" ), '', $m[2] );
                if ( $amount > 0 ) {
                    $rows[] = array( 'source' => sanitize_text_field( $m[1] ), 'eur' => $amount, 'shown' => self::money( $amount ), 'usd' => false );
                }
            }
        }
        return $rows;
    }

    /**
     * Savings as typed and in euros.
     *
     * @return array|null eur, shown, usd
     */
    private static function savings_amount( $c ) {
        $m = json_decode( (string) ( $c['answers']['savings'] ?? '' ), true );
        if ( is_array( $m ) ) {
            $amount = (float) ( $m['amount'] ?? 0 );
            $usd    = 'USD' === ( $m['currency'] ?? 'USD' );
            $fx     = self::usd_rate();
            return array(
                'eur'   => $usd ? ( $fx ? $amount / $fx['rate'] : null ) : $amount,
                'shown' => $usd ? '$' . number_format( $amount, 0, '.', ',' ) : self::money( $amount ),
                'usd'   => $usd,
                'zero'  => $amount <= 0,
            );
        }
        if ( '' !== (string) ( $c['answers']['savings_eur'] ?? '' ) ) {
            $amount = (float) $c['answers']['savings_eur'];
            return array( 'eur' => $amount, 'shown' => self::money( $amount ), 'usd' => false, 'zero' => $amount <= 0 );
        }
        return null;
    }

    /** "$2,400 (about €2,208)" or "€900". */
    private static function both( $shown, $eur, $usd ) {
        if ( ! $usd ) {
            return $shown;
        }
        return null === $eur ? $shown : $shown . ' (about ' . self::money( round( $eur ) ) . ')';
    }

    /** The exchange-rate line, when any amount was in dollars. */
    private static function fx_line() {
        $fx = self::usd_rate();
        if ( ! $fx ) {
            return 'Dollar amounts are shown as entered; the euro conversion could not be fetched.';
        }
        return sprintf( 'Dollar amounts converted at the European Central Bank reference rate of 1 EUR = %s USD, published %s.', rtrim( rtrim( number_format( $fx['rate'], 4, '.', '' ), '0' ), '.' ), self::date_en( $fx['date'] ) );
    }

    private static function enclosures( $c ) {
        // Dossier items are written as a checklist for the member ("your
        // stay"); in the letter they are the applicant's own documents.
        $labels = array(
            'passport-valid'               => 'Passport',
            'passport-photos'              => 'Passport photos (35 x 45 mm)',
            'application-form'             => 'France-Visas application form, signed',
            'proof-accommodation'          => 'Proof of accommodation in France',
            'proof-funds'                  => 'Bank statements for the last three months',
            'travel-insurance'             => 'Health insurance certificate covering the full stay',
            'birth-certificate-apostilled' => 'Birth certificate, apostilled',
            'declaration-no-work'          => 'Signed declaration not to work (attestation sur l\'honneur)',
            'proof-funds-studies'          => 'Proof of funds for my studies',
            'relationship-proof'           => 'Evidence of our life together',
            'spouse-french-id'             => 'My spouse\'s French identity document',
            'sponsor-permit'               => 'My sponsor\'s residence permit',
            'category-proof'               => 'Documents for my passeport talent category',
        );
        $skip = array( 'cover-letter' );
        $out  = array();
        foreach ( (array) $c['dossier'] as $item ) {
            $id = $item['id'] ?? '';
            if ( in_array( $id, $skip, true ) ) {
                continue;
            }
            $label = $labels[ $id ] ?? preg_replace( '/\byour\b/i', 'my', (string) ( $item['title'] ?? '' ) );
            if ( 'declaration-no-work' === $id && self::has_partner( $c ) ) {
                $label = 'Signed declarations not to work, one for each of us (attestations sur l\'honneur)';
            }
            $out[] = $label;
        }
        if ( in_array( $c['visa'], array( 'visitor', 'retiree' ), true ) && 'yes_remote' === $c['work_fr'] ) {
            $out[] = 'Letter from my employer confirming the remote-work arrangement';
        }
        if ( in_array( $c['visa'], array( 'visitor', 'retiree', 'other', 'student', 'entrepreneur' ), true ) || in_array( $c['talent'], array( 'founder', 'investor' ), true ) ) {
            $out[] = 'Statement of resources';
        }
        return array_values( array_unique( array_filter( $out ) ) );
    }

    private static function talent_label( $key ) {
        $labels = array(
            'qualified_employee' => '« salarié qualifié »',
            'blue_card'          => '« carte bleue européenne »',
            'founder'            => '« création d\'entreprise »',
            'investor'           => '« investisseur »',
            'researcher'         => '« chercheur »',
            'artist'             => '« profession artistique et culturelle »',
        );
        return $labels[ $key ] ?? '[category]';
    }

    private static function birth_place_default( $c ) {
        if ( 'OTHER' === $c['birth_state'] || '' === $c['birth_state'] ) {
            return $c['birth_other'];
        }
        return self::state_name( $c['birth_state'] ) ? self::state_name( $c['birth_state'] ) . ', USA' : '';
    }

    private static function state_name( $code ) {
        $names = array( 'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming' );
        return $names[ $code ] ?? '';
    }

    private static function nationality( $raw ) {
        $r = strtolower( trim( (string) $raw ) );
        if ( '' === $r || preg_match( '/^(us|usa|u\.s\.a?\.?|american|united states.*|america)$/', $r ) ) {
            return array( 'fr' => 'américaine', 'en' => 'US' );
        }
        return array( 'fr' => $raw, 'en' => $raw );
    }

    private static function first_word( $name ) {
        $parts = preg_split( '/\s+/', trim( (string) $name ) );
        return $parts[0] ?? '';
    }

    /** Collapse a doubled full stop left where an answer already ends in one ("Acme Inc.."). */
    private static function tidy( $text ) {
        return preg_replace( '/\.\.(?=\s|$)/', '.', (string) $text );
    }

    private static function one_line( $text ) {
        return trim( preg_replace( '/\s*\r?\n\s*/', ', ', (string) $text ), ', ' );
    }

    private static function list_words( $items ) {
        $items = array_values( array_filter( $items ) );
        if ( count( $items ) <= 1 ) {
            return implode( '', $items );
        }
        $last = array_pop( $items );
        return implode( ', ', $items ) . ' and ' . $last;
    }

    /** €1 477,93 style is French; letters to US consulates read in English: €1,477.93. */
    public static function money( $amount ) {
        $amount = (float) $amount;
        return '€' . number_format( $amount, floor( $amount ) == $amount ? 0 : 2, '.', ',' );
    }

    private static function date_en( $ymd ) {
        $t = strtotime( (string) $ymd );
        return $t ? gmdate( 'j F Y', $t ) : (string) $ymd;
    }

    private static function date_fr( $ymd ) {
        $t = strtotime( (string) $ymd );
        if ( ! $t ) {
            return (string) $ymd;
        }
        $months = array( 1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
        $day    = (int) gmdate( 'j', $t );
        return ( 1 === $day ? '1er' : $day ) . ' ' . $months[ (int) gmdate( 'n', $t ) ] . ' ' . gmdate( 'Y', $t );
    }

    private static function maybe_date( $value, $french = false ) {
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ) {
            return $french ? self::date_fr( $value ) : self::date_en( $value );
        }
        return (string) $value;
    }

    // ------------------------------------------------------------------
    // Staleness and PDF
    // ------------------------------------------------------------------

    /**
     * A fingerprint of what a letter was drafted from, so the portal can say
     * when the profile or the answers have moved on since.
     */
    public static function fingerprint( $type, $c, $person ) {
        $def  = self::definition( $type, $c, $person );
        $keep = array();
        foreach ( (array) ( $def['fields'] ?? array() ) as $key ) {
            $keep[ $key ] = $c['answers'][ $key ] ?? '';
        }
        $profile = $c;
        unset( $profile['answers'], $profile['dossier'] );
        return md5( wp_json_encode( array( $type, $person, $profile, $keep ) ) );
    }

    /**
     * Render the markup as a PDF.
     *
     * @param string $title Document title, for the footer.
     * @param string $text  Letter markup.
     * @return string PDF bytes.
     */
    public static function render_pdf( $title, $text ) {
        $pdf     = new FRAMT_PDF( $title );
        $block   = array();
        $bullets = array();
        $flush   = function () use ( &$block, &$bullets, $pdf ) {
            if ( ! empty( $block ) ) {
                foreach ( $block as $line ) {
                    $pdf->text( $line );
                }
                $pdf->space( 7 );
            }
            if ( ! empty( $bullets ) ) {
                $pdf->bullets( $bullets );
            }
            $block   = array();
            $bullets = array();
        };
        foreach ( preg_split( '/\r?\n/', (string) $text ) as $raw ) {
            $line = rtrim( $raw );
            if ( 0 === strpos( ltrim( $line ), '//' ) ) {
                continue; // a note to the member
            }
            if ( '' === trim( $line ) ) {
                if ( empty( $block ) && empty( $bullets ) ) {
                    $pdf->space( 6 );
                }
                $flush();
                continue;
            }
            if ( 0 === strpos( $line, '- ' ) ) {
                if ( ! empty( $block ) ) {
                    $flush();
                }
                $bullets[] = substr( $line, 2 );
                continue;
            }
            if ( ! empty( $bullets ) ) {
                $flush();
            }
            if ( 0 === strpos( $line, '# ' ) ) {
                $flush();
                $pdf->heading( substr( $line, 2 ) );
            } elseif ( 0 === strpos( $line, '## ' ) ) {
                $flush();
                $pdf->subheading( substr( $line, 3 ) );
            } elseif ( 0 === strpos( $line, '> ' ) ) {
                $flush();
                $pdf->note( substr( $line, 2 ) );
            } elseif ( '---' === trim( $line ) ) {
                $flush();
                $pdf->rule();
            } elseif ( 0 === strpos( $line, '[signature]' ) ) {
                $flush();
                $pdf->sign_line( trim( substr( $line, 11 ) ) );
            } else {
                $block[] = $line;
            }
        }
        $flush();
        return $pdf->output();
    }

}
