<?php
/**
 * Regenerate the product-content fixtures for the demo harness from the
 * plugin's own PHP, so the demo shows the real step titles, how-tos, letter
 * catalogue, glossary and chat categories rather than copies that drift.
 *
 * Only product content is read. The household is invented here (the Ellis
 * couple, example.com addresses, passport X0000000); no WordPress, no
 * database, no network.
 *
 *   php demo/scripts/extract-fixtures.php      (from portal/)
 *
 * Writes demo/fixtures/{templates,howto,glossary,chat-categories,letters}.json
 */

define( 'ABSPATH', '/' );

$includes = realpath( __DIR__ . '/../../../includes' );
$out_dir  = realpath( __DIR__ . '/../fixtures' );
$api_src  = file_get_contents( $includes . '/class-framt-portal-api.php' );

// ---------------------------------------------------------------- the invented household
$DEMO_META = array(
	'visa_type'               => 'visitor',
	'legal_first_name'        => 'Jordan',
	'legal_middle_name'       => '',
	'legal_last_name'         => 'Ellis',
	'date_of_birth'           => '1958-04-12',
	'nationality'             => 'US',
	'passport_number'         => 'X0000000',
	'applicants'              => 'spouse',
	'spouse_legal_first_name' => 'Sam',
	'spouse_legal_last_name'  => 'Ellis',
	'spouse_date_of_birth'    => '1960-08-30',
	'spouse_work_status'      => 'retired',
	'num_children'            => '0',
	'relationship_type'       => 'married',
	'talent_category'         => '',
	'employment_status'       => 'retired',
	'work_in_france'          => 'no',
	'employer_name'           => '',
	'job_title'               => '',
	'current_city'            => 'Asheville',
	'current_state'           => 'NC',
	'birth_state'             => 'NC',
	'target_location'         => 'Bordeaux',
	'housing_plan'            => 'renting',
	'french_mortgage'         => 'no',
	'target_move_date'        => '2027-02-15',
	'income_sources'          => 'Social Security and pensions',
	'study_length'            => '',
	'consulate'               => 'Atlanta',
	'mailing_address'         => "123 Sample Street\nAsheville, NC 28801",
);
$DEMO_ANSWERS = array(
	'address_in_france' => "Furnished rental, 8 rue Exemple\n33000 Bordeaux",
	'arrival_date'      => '2027-02-15',
	'stay_length'       => 'one year, renewable',
	'income_rows'       => json_encode( array(
		array( 'source' => 'Social Security', 'amount' => 3100, 'currency' => 'USD', 'per' => 'month' ),
		array( 'source' => 'Pension from a former employer', 'amount' => 26400, 'currency' => 'USD', 'per' => 'year' ),
	) ),
	'savings'           => json_encode( array( 'amount' => 185000, 'currency' => 'USD' ) ),
	'plans'             => 'We plan to take French classes, walk and cycle the Gironde, volunteer with a local community garden, and have our grown children visit.',
);

// ---------------------------------------------------------------- WordPress stubs
function wp_parse_args( $a, $d ) { return array_merge( $d, (array) $a ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_textarea_field( $s ) { return trim( (string) $s ); }
function sanitize_file_name( $s ) { return preg_replace( '/[^A-Za-z0-9 ._\-]/', '', (string) $s ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_list_pluck( $list, $field ) { return array_map( function ( $r ) use ( $field ) { return is_array( $r ) ? ( $r[ $field ] ?? null ) : ( $r->$field ?? null ); }, (array) $list ); }
function rest_ensure_response( $x ) { return $x; }
function current_time( $fmt ) { return 'mysql' === $fmt ? '2026-09-12 10:24:00' : gmdate( $fmt, strtotime( '2026-09-12' ) ); }
function get_transient( $k ) { return 'framt_ecb_usd' === $k ? array( 'rate' => 1.165, 'date' => '2026-09-18' ) : false; }
function get_option( $k, $d = false ) { return $d; }
function get_userdata( $id ) { return (object) array( 'first_name' => 'Jordan', 'last_name' => 'Ellis', 'user_email' => 'jordan@example.com', 'display_name' => 'Jordan Ellis' ); }
function get_user_meta( $u, $k, $single = true ) {
	global $DEMO_META, $DEMO_ANSWERS;
	if ( 'fra_letter_answers' === $k ) {
		return $DEMO_ANSWERS;
	}
	return $DEMO_META[ preg_replace( '/^fra_/', '', $k ) ] ?? '';
}

// ---------------------------------------------------------------- pull private methods out of the API class
function method_source( $src, $name ) {
	$start = strpos( $src, 'function ' . $name . '(' );
	if ( false === $start ) {
		fwrite( STDERR, "missing $name\n" );
		exit( 1 );
	}
	$start = strrpos( substr( $src, 0, $start ), "\n" ) + 1;
	$end   = strpos( $src, "\n    }\n", $start ) + 7;
	return substr( $src, $start, $end - $start );
}

$class = "class Demo_Extract {\n"
	. "  private function get_state_facts( \$u ) { return array( 'state' => 'NC', 'name' => 'North Carolina', 'licence_exchange' => 'no', 'licence_classes' => '', 'verified' => 'September 2026' ); }\n";
foreach ( array( 'get_visa_task_templates', 'get_profile_task_templates', 'get_spouse_task_templates', 'get_glossary_data_raw', 'get_chat_categories', 'get_visa_dossier_items' ) as $m ) {
	$class .= method_source( $api_src, $m ) . "\n";
}
$class .= "  public function call( \$m, ...\$a ) { return \$this->\$m( ...\$a ); }\n}\n";
eval( $class );

require $includes . '/class-framt-task-howto.php';
require $includes . '/class-framt-letters.php';

$x = new Demo_Extract();

$write = function ( $name, $data ) use ( $out_dir ) {
	file_put_contents( $out_dir . '/' . $name, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
	echo "wrote $name\n";
};

$templates = array(
	'visa'    => $x->call( 'get_visa_task_templates', 'visitor' ),
	'profile' => $x->call( 'get_profile_task_templates', 1 ),
	'spouse'  => $x->call( 'get_spouse_task_templates' ),
);
$write( 'templates.json', $templates );

$ctx   = array( 'state' => 'North Carolina', 'state_code' => 'NC', 'licence_exchange' => 'no', 'spouse' => 'Sam' );
$howto = array();
foreach ( $templates as $list ) {
	foreach ( $list as $t ) {
		$h = FRAMT_Task_Howto::for_title( $t['title'], $ctx );
		if ( $h ) {
			$howto[ $t['title'] ] = $h;
		}
	}
}
$write( 'howto.json', $howto );
$write( 'glossary.json', $x->call( 'get_glossary_data_raw' ) );
$write( 'chat-categories.json', $x->call( 'get_chat_categories', null ) );

// Letters: the catalogue for this household and the drafted cover letter.
$dossier = $x->call( 'get_visa_dossier_items', 'visitor' );
$write( 'dossier-items.json', $dossier );
$c       = FRAMT_Letters::context( 1, $dossier );
$letters = array();
foreach ( FRAMT_Letters::catalogue( $c ) as $def ) {
	$letters[] = array(
		'type'     => $def['type'],
		'person'   => $def['person'],
		'title'    => $def['title'],
		'why'      => $def['why'],
		'optional' => (bool) $def['optional'],
		'fields'   => array_values( $def['fields'] ),
		'guidance' => array_values( $def['guidance'] ),
		'item_id'  => $def['item_id'],
		'file'     => null,
	);
}
$answers = $c['answers'];
$write( 'letters.json', array(
	'visa_type' => $c['visa'],
	'letters'   => $letters,
	'fields'    => FRAMT_Letters::fields(),
	'first'     => FRAMT_Letters::first_fields(),
	'form'      => FRAMT_Letters::form_context( $c ),
	'answers'   => $answers,
	'drafts'    => array(
		'cover-letter'        => FRAMT_Letters::build( 'cover-letter', $c ),
		'no-work-declaration' => FRAMT_Letters::build( 'no-work-declaration', $c ),
	),
) );
