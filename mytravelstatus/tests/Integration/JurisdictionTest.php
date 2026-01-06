<?php
/**
 * Integration tests for MTS_Jurisdiction class.
 *
 * Tests the jurisdiction rules engine including:
 * - Rule retrieval and caching
 * - Day counting calculations
 * - Status determination
 * - Multi-jurisdiction summaries
 *
 * @package MyTravelStatus
 */

/**
 * Jurisdiction integration test class.
 */
class JurisdictionTest extends MTS_TestCase {

	/**
	 * Jurisdiction instance.
	 *
	 * @var MTS_Jurisdiction
	 */
	protected $jurisdiction;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
		$this->jurisdiction = $this->get_jurisdiction();
	}

	/**
	 * Test that get_all_rules returns available jurisdictions.
	 */
	public function test_get_all_rules_returns_jurisdictions() {
		$rules = $this->jurisdiction->get_all_rules();

		$this->assertIsArray( $rules );
		$this->assertNotEmpty( $rules );

		// Check for expected default jurisdictions.
		$codes = array_column( $rules, 'code' );
		$this->assertContains( 'schengen', $codes );
	}

	/**
	 * Test that get_rule returns a specific jurisdiction.
	 */
	public function test_get_rule_returns_jurisdiction() {
		$rule = $this->jurisdiction->get_rule( 'schengen' );

		$this->assertIsArray( $rule );
		$this->assertEquals( 'schengen', $rule['code'] );
		$this->assertEquals( 'Schengen Area', $rule['name'] );
		$this->assertEquals( 90, $rule['daysAllowed'] );
		$this->assertEquals( 180, $rule['windowDays'] );
		$this->assertEquals( 'rolling', $rule['countingMethod'] );
	}

	/**
	 * Test that get_rule returns null for non-existent jurisdiction.
	 */
	public function test_get_rule_returns_null_for_nonexistent() {
		$rule = $this->jurisdiction->get_rule( 'nonexistent_code' );

		$this->assertNull( $rule );
	}

	/**
	 * Test get_user_tracked_jurisdictions returns default Schengen.
	 */
	public function test_get_user_tracked_jurisdictions_default() {
		$tracked = $this->jurisdiction->get_user_tracked_jurisdictions( $this->test_user_id );

		$this->assertIsArray( $tracked );
		$this->assertContains( 'schengen', $tracked );
	}

	/**
	 * Test get_user_tracked_jurisdictions returns custom tracking.
	 */
	public function test_get_user_tracked_jurisdictions_custom() {
		$this->set_tracked_jurisdictions( array( 'schengen', 'uk_srt', 'ireland_183' ) );

		$tracked = $this->jurisdiction->get_user_tracked_jurisdictions( $this->test_user_id );

		$this->assertCount( 3, $tracked );
		$this->assertContains( 'schengen', $tracked );
		$this->assertContains( 'uk_srt', $tracked );
		$this->assertContains( 'ireland_183', $tracked );
	}

	/**
	 * Test calculate_summary with no trips returns zero days.
	 */
	public function test_calculate_summary_no_trips() {
		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertIsArray( $summary );
		$this->assertEquals( 0, $summary['daysUsed'] );
		$this->assertEquals( 90, $summary['daysAllowed'] );
		$this->assertEquals( 90, $summary['daysRemaining'] );
		$this->assertEquals( 'ok', $summary['status'] );
		$this->assertEquals( 0, $summary['tripCount'] );
	}

	/**
	 * Test calculate_summary counts days correctly.
	 */
	public function test_calculate_summary_counts_days() {
		// Create a 10-day trip to France (Schengen).
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-15 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		// Clear cache to get fresh calculation.
		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertEquals( 10, $summary['daysUsed'] );
		$this->assertEquals( 80, $summary['daysRemaining'] );
		$this->assertEquals( 1, $summary['tripCount'] );
		$this->assertEquals( 'ok', $summary['status'] );
	}

	/**
	 * Test calculate_summary warning status at 80%.
	 */
	public function test_calculate_summary_warning_status() {
		// Create trips totaling ~75 days (83% of 90).
		$this->create_trip(
			array(
				'country'    => 'Germany',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-80 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertEquals( 'warning', $summary['status'] );
		$this->assertGreaterThanOrEqual( 80, $summary['percentage'] );
	}

	/**
	 * Test calculate_summary critical status at 95%.
	 */
	public function test_calculate_summary_critical_status() {
		// Create trips totaling ~87 days (97% of 90).
		$this->create_trip(
			array(
				'country'    => 'Spain',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-92 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertEquals( 'critical', $summary['status'] );
		$this->assertGreaterThanOrEqual( 95, $summary['percentage'] );
	}

	/**
	 * Test calculate_summary exceeded status at 100%+.
	 */
	public function test_calculate_summary_exceeded_status() {
		// Create trips totaling ~95 days (over 90 limit).
		$this->create_trip(
			array(
				'country'    => 'Italy',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-100 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertEquals( 'exceeded', $summary['status'] );
		$this->assertGreaterThanOrEqual( 100, $summary['percentage'] );
		$this->assertEquals( 0, $summary['daysRemaining'] );
	}

	/**
	 * Test get_compliance_overview returns aggregate data.
	 */
	public function test_get_compliance_overview() {
		$this->set_tracked_jurisdictions( array( 'schengen', 'ireland_183' ) );

		// Create a Schengen trip.
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-20 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-11 days' ) ),
			)
		);

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$overview = $this->jurisdiction->get_compliance_overview( $this->test_user_id );

		$this->assertIsArray( $overview );
		$this->assertEquals( 2, $overview['total_jurisdictions'] );
		$this->assertArrayHasKey( 'critical_count', $overview );
		$this->assertArrayHasKey( 'warning_count', $overview );
		$this->assertArrayHasKey( 'ok_count', $overview );
		$this->assertArrayHasKey( 'exceeded_count', $overview );
		$this->assertArrayHasKey( 'summaries', $overview );
		$this->assertCount( 2, $overview['summaries'] );
	}

	/**
	 * Test get_compliance_overview caching.
	 */
	public function test_get_compliance_overview_caching() {
		$this->set_tracked_jurisdictions( array( 'schengen' ) );

		// First call calculates.
		$overview1 = $this->jurisdiction->get_compliance_overview( $this->test_user_id );

		// Second call should return cached.
		$overview2 = $this->jurisdiction->get_compliance_overview( $this->test_user_id );

		$this->assertEquals( $overview1, $overview2 );
	}

	/**
	 * Test invalidate_cache clears transients.
	 */
	public function test_invalidate_cache() {
		$this->set_tracked_jurisdictions( array( 'schengen' ) );

		// Get initial overview.
		$overview1 = $this->jurisdiction->get_compliance_overview( $this->test_user_id );

		// Add a trip.
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 days' ) ),
			)
		);

		// Invalidate cache.
		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		// Get new overview - should reflect new trip.
		$overview2 = $this->jurisdiction->get_compliance_overview( $this->test_user_id );

		// Days used should have increased.
		$this->assertGreaterThan(
			$overview1['summaries'][0]['daysUsed'] ?? 0,
			$overview2['summaries'][0]['daysUsed'] ?? 0
		);
	}

	/**
	 * Test rolling window calculation excludes old trips.
	 */
	public function test_rolling_window_excludes_old_trips() {
		// Create an old trip outside the 180-day window.
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-200 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-190 days' ) ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		// Old trip should not be counted.
		$this->assertEquals( 0, $summary['daysUsed'] );
	}

	/**
	 * Test calendar year calculation.
	 */
	public function test_calendar_year_calculation() {
		// Create a trip within this calendar year.
		$this->create_trip(
			array(
				'country'    => 'Ireland',
				'start_date' => gmdate( 'Y-01-15' ),
				'end_date'   => gmdate( 'Y-01-25' ),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'ireland_183' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		$this->assertEquals( 11, $summary['daysUsed'] ); // Jan 15-25 = 11 days.
		$this->assertEquals( 'calendar_year', $summary['countingMethod'] );
	}

	/**
	 * Test get_available_rules returns same as get_all_rules.
	 */
	public function test_get_available_rules() {
		$available = $this->jurisdiction->get_available_rules();
		$all = $this->jurisdiction->get_all_rules();

		$this->assertEquals( $all, $available );
	}

	/**
	 * Test filtering rules by type.
	 */
	public function test_get_all_rules_by_type() {
		$zone_rules = $this->jurisdiction->get_all_rules( 'zone' );
		$country_rules = $this->jurisdiction->get_all_rules( 'country' );

		// All zone rules should have type = zone.
		foreach ( $zone_rules as $rule ) {
			$this->assertEquals( 'zone', $rule['type'] );
		}

		// All country rules should have type = country.
		foreach ( $country_rules as $rule ) {
			$this->assertEquals( 'country', $rule['type'] );
		}
	}

	/**
	 * Test multiple trips are counted correctly.
	 */
	public function test_multiple_trips_counted() {
		// Create multiple trips.
		$this->create_trips(
			array(
				array(
					'country'    => 'France',
					'start_date' => gmdate( 'Y-m-d', strtotime( '-50 days' ) ),
					'end_date'   => gmdate( 'Y-m-d', strtotime( '-41 days' ) ),
				),
				array(
					'country'    => 'Germany',
					'start_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
					'end_date'   => gmdate( 'Y-m-d', strtotime( '-21 days' ) ),
				),
				array(
					'country'    => 'Spain',
					'start_date' => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
					'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 days' ) ),
				),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		// 10 + 10 + 10 = 30 days.
		$this->assertEquals( 30, $summary['daysUsed'] );
		$this->assertEquals( 3, $summary['tripCount'] );
	}

	/**
	 * Test overlapping trips are not double-counted.
	 */
	public function test_overlapping_trips_not_double_counted() {
		// Create overlapping trips.
		$this->create_trips(
			array(
				array(
					'country'    => 'France',
					'start_date' => gmdate( 'Y-m-d', strtotime( '-20 days' ) ),
					'end_date'   => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
				),
				array(
					'country'    => 'France',
					'start_date' => gmdate( 'Y-m-d', strtotime( '-15 days' ) ),
					'end_date'   => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
				),
			)
		);

		$rule = $this->jurisdiction->get_rule( 'schengen' );
		$reference_date = new DateTime();

		$this->jurisdiction->invalidate_cache( $this->test_user_id );

		$summary = $this->jurisdiction->calculate_summary(
			$this->test_user_id,
			$rule,
			$reference_date
		);

		// Should count 16 unique days (day -20 to day -5), not 22 (11+11).
		$this->assertEquals( 16, $summary['daysUsed'] );
	}
}
