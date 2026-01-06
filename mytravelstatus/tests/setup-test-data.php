<?php
/**
 * MyTravelStatus Test Data Setup Script
 *
 * Run this script via WP-CLI to set up test data for the multi-jurisdiction features.
 *
 * Usage:
 *   wp eval-file mytravelstatus/tests/setup-test-data.php
 *
 * Or access via browser when logged in as admin:
 *   ?mts_setup_test_data=1
 *
 * @package MTS_Tracker
 * @since   1.8.2
 */

// Safety check - only run from WP-CLI or as admin.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// Running from WP-CLI.
	mts_setup_test_data();
} elseif ( defined( 'ABSPATH' ) && is_admin() && current_user_can( 'manage_options' ) ) {
	// Running in admin context.
	if ( isset( $_GET['mts_setup_test_data'] ) && $_GET['mts_setup_test_data'] === '1' ) {
		add_action( 'admin_init', function() {
			mts_setup_test_data();
			wp_redirect( admin_url( 'admin.php?page=mts-test-page' ) );
			exit;
		} );
	}
} else {
	// Not allowed to run.
	return;
}

/**
 * Set up test data for MyTravelStatus.
 */
function mts_setup_test_data() {
	global $wpdb;

	// Get current user.
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		$user_id = 1; // Default to admin.
	}

	echo "Setting up MyTravelStatus test data for user ID: {$user_id}\n\n";

	// Step 1: Enable test mode and access.
	echo "1. Enabling test mode...\n";
	update_option( 'mts_test_mode_enabled', true );
	update_option( 'mts_global_enabled', '1' );
	echo "   Test mode enabled.\n\n";

	// Step 2: Set up tracked jurisdictions.
	echo "2. Setting up tracked jurisdictions...\n";
	$jurisdictions = array( 'schengen_visa', 'uk_srt', 'ireland_183', 'us_spt' );
	update_user_meta( $user_id, 'mts_tracked_jurisdictions', $jurisdictions );
	echo "   Tracking: " . implode( ', ', $jurisdictions ) . "\n\n";

	// Step 3: Set up UK ties for SRT calculation.
	echo "3. Setting up UK ties for SRT calculation...\n";
	$current_year = (int) date( 'Y' );
	$uk_ties = array(
		'family_tie'        => false,
		'accommodation_tie' => true,
		'work_tie'          => false,
		'ninety_day_tie'    => true,
		'country_tie'       => false,
	);
	update_user_meta( $user_id, 'mts_uk_ties_' . $current_year, $uk_ties );
	echo "   UK ties set: accommodation (yes), 90-day (yes), others (no)\n\n";

	// Step 4: Create sample trips.
	echo "4. Creating sample trips...\n";
	$table = $wpdb->prefix . 'mts_trips';

	// Check if table exists.
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table;
	if ( ! $table_exists ) {
		echo "   ERROR: Table {$table} does not exist. Run plugin activation first.\n";
		return;
	}

	// Clear existing trips for this user (optional).
	// $wpdb->delete( $table, array( 'user_id' => $user_id ) );

	// Define sample trips.
	$today = new DateTime();
	$trips = array(
		// Schengen trips.
		array(
			'country'    => 'France',
			'start_days' => -15,
			'end_days'   => -5,
			'category'   => 'personal',
			'notes'      => 'Paris vacation',
		),
		array(
			'country'    => 'Spain',
			'start_days' => -60,
			'end_days'   => -46,
			'category'   => 'personal',
			'notes'      => 'Barcelona trip',
		),
		array(
			'country'    => 'Germany',
			'start_days' => -120,
			'end_days'   => -110,
			'category'   => 'business',
			'notes'      => 'Berlin conference',
		),
		// UK trip.
		array(
			'country'    => 'United Kingdom',
			'start_days' => -45,
			'end_days'   => -25,
			'category'   => 'business',
			'notes'      => 'London meetings',
		),
		// Ireland trip.
		array(
			'country'    => 'Ireland',
			'start_days' => -90,
			'end_days'   => -83,
			'category'   => 'personal',
			'notes'      => 'Dublin trip',
		),
		// US trips (for SPT testing).
		array(
			'country'    => 'United States',
			'start_days' => -30,
			'end_days'   => -16,
			'category'   => 'business',
			'notes'      => 'New York business',
		),
		array(
			'country'    => 'United States',
			'start_days' => -200,
			'end_days'   => -170,
			'category'   => 'personal',
			'notes'      => 'California vacation',
		),
	);

	$created_count = 0;
	foreach ( $trips as $trip ) {
		$start_date = ( clone $today )->modify( "{$trip['start_days']} days" )->format( 'Y-m-d' );
		$end_date = ( clone $today )->modify( "{$trip['end_days']} days" )->format( 'Y-m-d' );

		// Check if similar trip exists.
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND country = %s AND start_date = %s",
			$user_id,
			$trip['country'],
			$start_date
		) );

		if ( $existing ) {
			echo "   Skipping duplicate: {$trip['country']} ({$start_date})\n";
			continue;
		}

		$result = $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'country'    => $trip['country'],
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'category'   => $trip['category'],
				'notes'      => $trip['notes'],
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			$days = ( strtotime( $end_date ) - strtotime( $start_date ) ) / 86400 + 1;
			echo "   Created: {$trip['country']} ({$start_date} to {$end_date}) - {$days} days\n";
			$created_count++;
		}
	}
	echo "   Created {$created_count} trips.\n\n";

	// Step 5: Invalidate cache.
	echo "5. Invalidating cache...\n";
	if ( class_exists( 'MTS_Jurisdiction' ) ) {
		MTS_Jurisdiction::get_instance()->invalidate_cache( $user_id );
		echo "   Cache invalidated.\n\n";
	}

	// Step 6: Display summary.
	echo "6. Summary:\n";
	echo "   ============================================\n";

	// Get trip counts.
	$trip_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
		$user_id
	) );
	echo "   Total trips: {$trip_count}\n";

	// Get days by country.
	$by_country = $wpdb->get_results( $wpdb->prepare(
		"SELECT country,
		        SUM(DATEDIFF(end_date, start_date) + 1) as days,
		        COUNT(*) as trips
		 FROM {$table}
		 WHERE user_id = %d
		 GROUP BY country
		 ORDER BY days DESC",
		$user_id
	) );

	echo "   Days by country:\n";
	foreach ( $by_country as $row ) {
		echo "   - {$row->country}: {$row->days} days ({$row->trips} trips)\n";
	}

	// Display test page URL.
	echo "\n   ============================================\n";
	echo "   Test page URL: " . home_url( '/my-travel-status/' ) . "\n";
	echo "   Admin access: " . admin_url( 'admin.php?page=mts-test-page' ) . "\n";
	echo "   ============================================\n\n";

	echo "Test data setup complete!\n";
}
