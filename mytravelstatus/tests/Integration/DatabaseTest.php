<?php
/**
 * Integration tests for MyTravelStatus database operations.
 *
 * Tests database layer functionality including:
 * - Trip CRUD operations
 * - Jurisdiction rules table
 * - Data integrity
 * - Query performance
 *
 * @package MyTravelStatus
 */

/**
 * Database integration test class.
 */
class DatabaseTest extends MTS_TestCase {

	/**
	 * Test trips table exists.
	 */
	public function test_trips_table_exists() {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->trips_table
			)
		);

		$this->assertEquals( $this->trips_table, $result );
	}

	/**
	 * Test jurisdiction rules table exists.
	 */
	public function test_rules_table_exists() {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->rules_table
			)
		);

		$this->assertEquals( $this->rules_table, $result );
	}

	/**
	 * Test trip insertion.
	 */
	public function test_trip_insertion() {
		global $wpdb;

		$trip_id = $this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => '2025-01-01',
				'end_date'   => '2025-01-10',
				'category'   => 'personal',
				'notes'      => 'Test trip to Paris',
			)
		);

		$this->assertGreaterThan( 0, $trip_id );

		// Verify trip was inserted.
		$trip = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table} WHERE id = %d",
				$trip_id
			)
		);

		$this->assertNotNull( $trip );
		$this->assertEquals( 'France', $trip->country );
		$this->assertEquals( '2025-01-01', $trip->start_date );
		$this->assertEquals( '2025-01-10', $trip->end_date );
		$this->assertEquals( 'personal', $trip->category );
		$this->assertEquals( 'Test trip to Paris', $trip->notes );
	}

	/**
	 * Test trip update.
	 */
	public function test_trip_update() {
		global $wpdb;

		$trip_id = $this->create_trip(
			array(
				'country'    => 'Germany',
				'start_date' => '2025-02-01',
				'end_date'   => '2025-02-05',
			)
		);

		// Update the trip.
		$wpdb->update(
			$this->trips_table,
			array(
				'country'  => 'Austria',
				'end_date' => '2025-02-10',
				'notes'    => 'Updated notes',
			),
			array( 'id' => $trip_id )
		);

		// Verify update.
		$trip = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table} WHERE id = %d",
				$trip_id
			)
		);

		$this->assertEquals( 'Austria', $trip->country );
		$this->assertEquals( '2025-02-10', $trip->end_date );
		$this->assertEquals( 'Updated notes', $trip->notes );
	}

	/**
	 * Test trip deletion.
	 */
	public function test_trip_deletion() {
		global $wpdb;

		$trip_id = $this->create_trip();

		// Verify trip exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->trips_table} WHERE id = %d",
				$trip_id
			)
		);
		$this->assertEquals( 1, $exists );

		// Delete trip.
		$wpdb->delete( $this->trips_table, array( 'id' => $trip_id ) );

		// Verify deletion.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->trips_table} WHERE id = %d",
				$trip_id
			)
		);
		$this->assertEquals( 0, $exists );
	}

	/**
	 * Test trips are isolated by user.
	 */
	public function test_trips_isolated_by_user() {
		global $wpdb;

		// Create trip for test user.
		$trip_id = $this->create_trip(
			array(
				'country' => 'France',
			)
		);

		// Create another user.
		$other_user_id = $this->factory->user->create();

		// Query trips for other user.
		$other_trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table} WHERE user_id = %d",
				$other_user_id
			)
		);

		$this->assertEmpty( $other_trips );

		// Query trips for test user.
		$test_trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table} WHERE user_id = %d",
				$this->test_user_id
			)
		);

		$this->assertCount( 1, $test_trips );
	}

	/**
	 * Test date range query.
	 */
	public function test_date_range_query() {
		global $wpdb;

		// Create trips in different date ranges.
		$this->create_trips(
			array(
				array(
					'start_date' => '2025-01-01',
					'end_date'   => '2025-01-10',
				),
				array(
					'start_date' => '2025-02-01',
					'end_date'   => '2025-02-10',
				),
				array(
					'start_date' => '2025-03-01',
					'end_date'   => '2025-03-10',
				),
			)
		);

		// Query trips in February only.
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table}
				WHERE user_id = %d
				AND start_date >= %s
				AND end_date <= %s",
				$this->test_user_id,
				'2025-02-01',
				'2025-02-28'
			)
		);

		$this->assertCount( 1, $trips );
		$this->assertEquals( '2025-02-01', $trips[0]->start_date );
	}

	/**
	 * Test overlapping date query.
	 */
	public function test_overlapping_date_query() {
		global $wpdb;

		// Create a trip from Jan 5-15.
		$this->create_trip(
			array(
				'start_date' => '2025-01-05',
				'end_date'   => '2025-01-15',
			)
		);

		// Query for trips that overlap with Jan 10-20.
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table}
				WHERE user_id = %d
				AND start_date <= %s
				AND end_date >= %s",
				$this->test_user_id,
				'2025-01-20',
				'2025-01-10'
			)
		);

		$this->assertCount( 1, $trips );
	}

	/**
	 * Test jurisdiction rules data integrity.
	 */
	public function test_jurisdiction_rules_integrity() {
		global $wpdb;

		// Get all rules.
		$rules = $wpdb->get_results(
			"SELECT * FROM {$this->rules_table} WHERE is_active = 1"
		);

		$this->assertNotEmpty( $rules );

		foreach ( $rules as $rule ) {
			// Every rule must have required fields.
			$this->assertNotEmpty( $rule->code, 'Rule code is required' );
			$this->assertNotEmpty( $rule->name, 'Rule name is required' );
			$this->assertGreaterThan( 0, $rule->days_allowed, 'days_allowed must be positive' );
			$this->assertGreaterThan( 0, $rule->window_days, 'window_days must be positive' );

			// Counting method must be valid.
			$valid_methods = array(
				'rolling',
				'calendar_year',
				'fiscal_year',
				'multi_year',
				'weighted_multi_year',
				'uk_srt',
			);
			$this->assertContains(
				$rule->counting_method,
				$valid_methods,
				"Invalid counting method: {$rule->counting_method}"
			);
		}
	}

	/**
	 * Test jurisdiction code uniqueness.
	 */
	public function test_jurisdiction_code_unique() {
		global $wpdb;

		// Try to insert duplicate code.
		$result = $wpdb->insert(
			$this->rules_table,
			array(
				'code'            => 'schengen', // Already exists.
				'name'            => 'Duplicate Schengen',
				'days_allowed'    => 90,
				'window_days'     => 180,
				'counting_method' => 'rolling',
			)
		);

		// Should fail due to unique constraint.
		$this->assertFalse( $result );
	}

	/**
	 * Test days calculation from trips table.
	 */
	public function test_days_calculation_query() {
		global $wpdb;

		// Create trips.
		$this->create_trips(
			array(
				array(
					'country'    => 'France',
					'start_date' => '2025-01-01',
					'end_date'   => '2025-01-10', // 10 days
				),
				array(
					'country'    => 'Germany',
					'start_date' => '2025-02-01',
					'end_date'   => '2025-02-05', // 5 days
				),
			)
		);

		// Calculate total days.
		$total_days = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(DATEDIFF(end_date, start_date) + 1)
				FROM {$this->trips_table}
				WHERE user_id = %d",
				$this->test_user_id
			)
		);

		$this->assertEquals( 15, (int) $total_days );
	}

	/**
	 * Test country aggregation query.
	 */
	public function test_country_aggregation_query() {
		global $wpdb;

		// Create trips to multiple countries.
		$this->create_trips(
			array(
				array(
					'country'    => 'France',
					'start_date' => '2025-01-01',
					'end_date'   => '2025-01-10',
				),
				array(
					'country'    => 'France',
					'start_date' => '2025-02-01',
					'end_date'   => '2025-02-05',
				),
				array(
					'country'    => 'Germany',
					'start_date' => '2025-03-01',
					'end_date'   => '2025-03-10',
				),
			)
		);

		// Get days by country.
		$by_country = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country,
				        COUNT(*) as trip_count,
				        SUM(DATEDIFF(end_date, start_date) + 1) as total_days
				FROM {$this->trips_table}
				WHERE user_id = %d
				GROUP BY country
				ORDER BY total_days DESC",
				$this->test_user_id
			)
		);

		$this->assertCount( 2, $by_country );

		// France should have more days.
		$france = $by_country[0];
		$this->assertEquals( 'France', $france->country );
		$this->assertEquals( 2, (int) $france->trip_count );
		$this->assertEquals( 15, (int) $france->total_days );

		// Germany.
		$germany = $by_country[1];
		$this->assertEquals( 'Germany', $germany->country );
		$this->assertEquals( 1, (int) $germany->trip_count );
		$this->assertEquals( 10, (int) $germany->total_days );
	}

	/**
	 * Test index usage for common queries.
	 */
	public function test_index_usage() {
		global $wpdb;

		// Check that user_id index exists.
		$indexes = $wpdb->get_results(
			"SHOW INDEX FROM {$this->trips_table} WHERE Key_name = 'user_id'"
		);

		$this->assertNotEmpty( $indexes, 'user_id index should exist' );

		// Check start_date index.
		$indexes = $wpdb->get_results(
			"SHOW INDEX FROM {$this->trips_table} WHERE Key_name = 'start_date'"
		);

		$this->assertNotEmpty( $indexes, 'start_date index should exist' );
	}

	/**
	 * Test user meta storage for tracked jurisdictions.
	 */
	public function test_user_meta_storage() {
		$jurisdictions = array( 'schengen', 'uk_srt', 'ireland_183' );

		update_user_meta( $this->test_user_id, 'mts_tracked_jurisdictions', $jurisdictions );

		$retrieved = get_user_meta( $this->test_user_id, 'mts_tracked_jurisdictions', true );

		$this->assertEquals( $jurisdictions, $retrieved );
	}

	/**
	 * Test transient caching.
	 */
	public function test_transient_caching() {
		$cache_key = 'mts_test_cache_' . $this->test_user_id;
		$cache_data = array( 'test' => 'data', 'count' => 42 );

		// Set transient.
		set_transient( $cache_key, $cache_data, 300 );

		// Get transient.
		$retrieved = get_transient( $cache_key );

		$this->assertEquals( $cache_data, $retrieved );

		// Delete transient.
		delete_transient( $cache_key );

		// Verify deleted.
		$deleted = get_transient( $cache_key );
		$this->assertFalse( $deleted );
	}

	/**
	 * Test bulk trip insertion performance.
	 */
	public function test_bulk_trip_insertion() {
		global $wpdb;

		$trips = array();
		$start = new DateTime( '-365 days' );

		// Generate 50 trips.
		for ( $i = 0; $i < 50; $i++ ) {
			$trip_start = clone $start;
			$trip_start->modify( "+{$i} weeks" );
			$trip_end = clone $trip_start;
			$trip_end->modify( '+3 days' );

			$trips[] = array(
				'country'    => 'France',
				'start_date' => $trip_start->format( 'Y-m-d' ),
				'end_date'   => $trip_end->format( 'Y-m-d' ),
			);
		}

		// Insert all trips.
		$start_time = microtime( true );
		$this->create_trips( $trips );
		$end_time = microtime( true );

		// Should complete in under 1 second.
		$this->assertLessThan( 1.0, $end_time - $start_time );

		// Verify count.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->trips_table} WHERE user_id = %d",
				$this->test_user_id
			)
		);

		$this->assertEquals( 50, (int) $count );
	}

	/**
	 * Test rolling window query performance.
	 */
	public function test_rolling_window_query_performance() {
		global $wpdb;

		// Create 100 trips over 2 years.
		$trips = array();
		$start = new DateTime( '-730 days' );

		for ( $i = 0; $i < 100; $i++ ) {
			$trip_start = clone $start;
			$trip_start->modify( "+{$i} weeks" );
			$trip_end = clone $trip_start;
			$trip_end->modify( '+2 days' );

			$trips[] = array(
				'country'    => 'Germany',
				'start_date' => $trip_start->format( 'Y-m-d' ),
				'end_date'   => $trip_end->format( 'Y-m-d' ),
			);
		}

		$this->create_trips( $trips );

		// Query trips in 180-day rolling window.
		$window_start = gmdate( 'Y-m-d', strtotime( '-179 days' ) );
		$window_end = gmdate( 'Y-m-d' );

		$start_time = microtime( true );

		$window_trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->trips_table}
				WHERE user_id = %d
				AND end_date >= %s
				AND start_date <= %s",
				$this->test_user_id,
				$window_start,
				$window_end
			)
		);

		$end_time = microtime( true );

		// Query should be fast (under 100ms).
		$this->assertLessThan( 0.1, $end_time - $start_time );
	}
}
