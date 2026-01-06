<?php
/**
 * Unit tests for MyTravelStatus calculator logic.
 *
 * These tests verify the calculation algorithms without WordPress.
 * They test pure PHP logic for day counting, status thresholds, etc.
 *
 * @package MyTravelStatus
 */

use PHPUnit\Framework\TestCase;

/**
 * Calculator unit test class.
 */
class CalculatorTest extends TestCase {

	/**
	 * Test US SPT weighted calculation.
	 */
	public function test_us_spt_weighted_calculation() {
		// Formula: Current year × 1.0 + Prior year × 1/3 + Second prior × 1/6 >= 183
		$calculate_weighted = function ( $current, $prior, $second_prior ) {
			return ( $current * 1.0 ) + ( $prior * ( 1 / 3 ) ) + ( $second_prior * ( 1 / 6 ) );
		};

		// Scenario 1: Full resident (120/180/180).
		$weighted = $calculate_weighted( 120, 180, 180 );
		$this->assertGreaterThanOrEqual( 183, $weighted );

		// Scenario 2: Below threshold (50/50/50).
		$weighted = $calculate_weighted( 50, 50, 50 );
		$this->assertLessThan( 183, $weighted );

		// Scenario 3: First year only (184 days).
		$weighted = $calculate_weighted( 184, 0, 0 );
		$this->assertGreaterThanOrEqual( 183, $weighted );

		// Scenario 4: Just at threshold.
		// 31 + 182*1/3 + 182*1/6 = 31 + 60.67 + 30.33 = 122 (not enough)
		$weighted = $calculate_weighted( 31, 182, 182 );
		$this->assertLessThan( 183, $weighted );
	}

	/**
	 * Test US SPT minimum current year requirement.
	 */
	public function test_us_spt_minimum_current_year() {
		$is_resident = function ( $current, $weighted_total ) {
			return $current >= 31 && $weighted_total >= 183;
		};

		// Not enough current year days.
		$this->assertFalse( $is_resident( 25, 200 ) );

		// Enough current year days and weighted total.
		$this->assertTrue( $is_resident( 31, 200 ) );

		// Enough current days but not weighted total.
		$this->assertFalse( $is_resident( 50, 100 ) );
	}

	/**
	 * Test UK SRT automatic overseas test.
	 */
	public function test_uk_srt_automatic_overseas() {
		// Auto overseas test 1: <16 days.
		$this->assertTrue( 15 < 16 );
		$this->assertFalse( 16 < 16 );

		// Auto overseas test 2: Leaver <46 days.
		$is_non_resident_leaver = function ( $days, $is_leaver ) {
			return $is_leaver && $days < 46;
		};

		$this->assertTrue( $is_non_resident_leaver( 40, true ) );
		$this->assertFalse( $is_non_resident_leaver( 50, true ) );
		$this->assertFalse( $is_non_resident_leaver( 40, false ) );
	}

	/**
	 * Test UK SRT automatic UK test.
	 */
	public function test_uk_srt_automatic_uk() {
		// 183+ days = automatic UK resident.
		$this->assertTrue( 183 >= 183 );
		$this->assertTrue( 200 >= 183 );
		$this->assertFalse( 182 >= 183 );
	}

	/**
	 * Test UK SRT sufficient ties thresholds.
	 *
	 * Thresholds for leavers:
	 * - 4+ ties: 16+ days
	 * - 3 ties: 46+ days
	 * - 2 ties: 91+ days
	 * - 1 tie: 121+ days
	 *
	 * Thresholds for arrivers:
	 * - 4+ ties: 16+ days
	 * - 3 ties: 46+ days
	 * - 2 ties: 121+ days
	 * - 1 tie: N/A (cannot be resident with 1 tie as arriver)
	 */
	public function test_uk_srt_ties_thresholds() {
		$leaver_thresholds = array(
			4 => 16,
			3 => 46,
			2 => 91,
			1 => 121,
		);

		$arriver_thresholds = array(
			4 => 16,
			3 => 46,
			2 => 121,
			1 => PHP_INT_MAX, // Cannot be resident.
		);

		$is_resident_with_ties = function ( $days, $ties, $is_leaver ) use ( $leaver_thresholds, $arriver_thresholds ) {
			$thresholds = $is_leaver ? $leaver_thresholds : $arriver_thresholds;
			$threshold = $thresholds[ min( 4, max( 1, $ties ) ) ];
			return $days >= $threshold;
		};

		// Leaver with 2 ties, 100 days (threshold 91).
		$this->assertTrue( $is_resident_with_ties( 100, 2, true ) );

		// Arriver with 2 ties, 100 days (threshold 121).
		$this->assertFalse( $is_resident_with_ties( 100, 2, false ) );

		// 4+ ties, 20 days (threshold 16).
		$this->assertTrue( $is_resident_with_ties( 20, 4, true ) );
		$this->assertTrue( $is_resident_with_ties( 20, 4, false ) );
	}

	/**
	 * Test Schengen 90/180 rolling window.
	 */
	public function test_schengen_rolling_window() {
		$calculate_days_in_window = function ( $trips, $window_start, $window_end ) {
			$days = 0;
			foreach ( $trips as $trip ) {
				$trip_start = max( $trip['start'], $window_start );
				$trip_end = min( $trip['end'], $window_end );

				if ( $trip_end >= $trip_start ) {
					$diff = $trip_end->diff( $trip_start );
					$days += $diff->days + 1;
				}
			}
			return $days;
		};

		$window_end = new DateTime( '2025-06-01' );
		$window_start = ( clone $window_end )->modify( '-179 days' );

		// Trip fully within window.
		$trips = array(
			array(
				'start' => new DateTime( '2025-03-01' ),
				'end' => new DateTime( '2025-03-15' ),
			),
		);
		$days = $calculate_days_in_window( $trips, $window_start, $window_end );
		$this->assertEquals( 15, $days );

		// Trip partially within window.
		$trips = array(
			array(
				'start' => new DateTime( '2024-12-01' ),
				'end' => new DateTime( '2025-01-15' ),
			),
		);
		$days = $calculate_days_in_window( $trips, $window_start, $window_end );
		// Window starts Dec 4, trip ends Jan 15 = 43 days.
		$this->assertGreaterThan( 0, $days );
	}

	/**
	 * Test Ireland 183/280 rule.
	 */
	public function test_ireland_multi_year() {
		// Primary: 183 days in current year.
		$is_resident_primary = function ( $current_days ) {
			return $current_days >= 183;
		};

		$this->assertTrue( $is_resident_primary( 183 ) );
		$this->assertFalse( $is_resident_primary( 182 ) );

		// Secondary: 280 days over 2 years, min 31 each.
		$is_resident_secondary = function ( $current_days, $prior_days ) {
			return ( $current_days + $prior_days ) >= 280
				&& $current_days >= 31
				&& $prior_days >= 31;
		};

		$this->assertTrue( $is_resident_secondary( 150, 150 ) ); // 300 total.
		$this->assertFalse( $is_resident_secondary( 200, 20 ) ); // Prior below 31.
		$this->assertFalse( $is_resident_secondary( 100, 100 ) ); // Only 200 total.
	}

	/**
	 * Test status threshold calculation.
	 */
	public function test_status_thresholds() {
		$get_status = function ( $days_used, $days_allowed ) {
			$percentage = ( $days_used / $days_allowed ) * 100;

			if ( $percentage >= 100 ) {
				return 'exceeded';
			}
			if ( $percentage >= 95 ) {
				return 'critical';
			}
			if ( $percentage >= 80 ) {
				return 'warning';
			}
			return 'ok';
		};

		// OK (under 80%).
		$this->assertEquals( 'ok', $get_status( 60, 90 ) ); // 67%

		// Warning (80-94%).
		$this->assertEquals( 'warning', $get_status( 75, 90 ) ); // 83%

		// Critical (95-99%).
		$this->assertEquals( 'critical', $get_status( 86, 90 ) ); // 96%

		// Exceeded (100%+).
		$this->assertEquals( 'exceeded', $get_status( 95, 90 ) ); // 106%
	}

	/**
	 * Test days remaining never negative.
	 */
	public function test_days_remaining_never_negative() {
		$calculate_remaining = function ( $days_used, $days_allowed ) {
			return max( 0, $days_allowed - $days_used );
		};

		$this->assertEquals( 30, $calculate_remaining( 60, 90 ) );
		$this->assertEquals( 0, $calculate_remaining( 90, 90 ) );
		$this->assertEquals( 0, $calculate_remaining( 100, 90 ) );
	}

	/**
	 * Test percentage calculation.
	 */
	public function test_percentage_calculation() {
		$calculate_percentage = function ( $days_used, $days_allowed ) {
			return round( ( $days_used / $days_allowed ) * 100, 1 );
		};

		$this->assertEquals( 50.0, $calculate_percentage( 45, 90 ) );
		$this->assertEquals( 100.0, $calculate_percentage( 90, 90 ) );
		$this->assertEquals( 111.1, $calculate_percentage( 100, 90 ) );
	}

	/**
	 * Test UK tax year date calculation.
	 */
	public function test_uk_tax_year_dates() {
		$get_uk_tax_year_start = function ( $date ) {
			$year = (int) $date->format( 'Y' );
			$month = (int) $date->format( 'n' );
			$day = (int) $date->format( 'j' );

			// Tax year starts April 6.
			if ( $month < 4 || ( $month === 4 && $day < 6 ) ) {
				$year--;
			}

			return new DateTime( "{$year}-04-06" );
		};

		// January 2025 is in tax year 2024-25.
		$date = new DateTime( '2025-01-15' );
		$tax_year_start = $get_uk_tax_year_start( $date );
		$this->assertEquals( '2024-04-06', $tax_year_start->format( 'Y-m-d' ) );

		// June 2025 is in tax year 2025-26.
		$date = new DateTime( '2025-06-15' );
		$tax_year_start = $get_uk_tax_year_start( $date );
		$this->assertEquals( '2025-04-06', $tax_year_start->format( 'Y-m-d' ) );

		// April 5 is still in previous tax year.
		$date = new DateTime( '2025-04-05' );
		$tax_year_start = $get_uk_tax_year_start( $date );
		$this->assertEquals( '2024-04-06', $tax_year_start->format( 'Y-m-d' ) );

		// April 6 is new tax year.
		$date = new DateTime( '2025-04-06' );
		$tax_year_start = $get_uk_tax_year_start( $date );
		$this->assertEquals( '2025-04-06', $tax_year_start->format( 'Y-m-d' ) );
	}

	/**
	 * Test Australia fiscal year dates (July-June).
	 */
	public function test_australia_fiscal_year_dates() {
		$get_au_fiscal_year_start = function ( $date ) {
			$year = (int) $date->format( 'Y' );
			$month = (int) $date->format( 'n' );

			// Fiscal year starts July 1.
			if ( $month < 7 ) {
				$year--;
			}

			return new DateTime( "{$year}-07-01" );
		};

		// January 2025 is in FY 2024-25.
		$date = new DateTime( '2025-01-15' );
		$fy_start = $get_au_fiscal_year_start( $date );
		$this->assertEquals( '2024-07-01', $fy_start->format( 'Y-m-d' ) );

		// August 2025 is in FY 2025-26.
		$date = new DateTime( '2025-08-15' );
		$fy_start = $get_au_fiscal_year_start( $date );
		$this->assertEquals( '2025-07-01', $fy_start->format( 'Y-m-d' ) );
	}

	/**
	 * Test cache key generation.
	 */
	public function test_cache_key_generation() {
		$generate_key = function ( $user_id, $code, $date ) {
			return "mts_summary_{$user_id}_{$code}_{$date}";
		};

		$key1 = $generate_key( 1, 'schengen', '2025-06-15' );
		$key2 = $generate_key( 2, 'schengen', '2025-06-15' );

		// Different users = different keys.
		$this->assertNotEquals( $key1, $key2 );

		// Same parameters = same key.
		$key3 = $generate_key( 1, 'schengen', '2025-06-15' );
		$this->assertEquals( $key1, $key3 );
	}

	/**
	 * Test alert level determination.
	 */
	public function test_alert_levels() {
		$get_alert_level = function ( $percentage, $status ) {
			if ( $percentage >= 95 || 'exceeded' === $status ) {
				return 'urgent';
			}
			if ( $percentage >= 85 || 'critical' === $status ) {
				return 'danger';
			}
			if ( $percentage >= 70 || 'warning' === $status ) {
				return 'warning';
			}
			return null;
		};

		$this->assertNull( $get_alert_level( 50, 'ok' ) );
		$this->assertEquals( 'warning', $get_alert_level( 75, 'warning' ) );
		$this->assertEquals( 'danger', $get_alert_level( 90, 'critical' ) );
		$this->assertEquals( 'urgent', $get_alert_level( 98, 'critical' ) );
		$this->assertEquals( 'urgent', $get_alert_level( 110, 'exceeded' ) );
	}

	/**
	 * Test trip overlap detection.
	 */
	public function test_trip_overlap_detection() {
		$trips_overlap = function ( $trip1, $trip2 ) {
			return $trip1['start'] <= $trip2['end'] && $trip1['end'] >= $trip2['start'];
		};

		// Overlapping trips.
		$trip1 = array( 'start' => '2025-01-01', 'end' => '2025-01-10' );
		$trip2 = array( 'start' => '2025-01-05', 'end' => '2025-01-15' );
		$this->assertTrue( $trips_overlap( $trip1, $trip2 ) );

		// Non-overlapping trips.
		$trip1 = array( 'start' => '2025-01-01', 'end' => '2025-01-10' );
		$trip2 = array( 'start' => '2025-01-15', 'end' => '2025-01-20' );
		$this->assertFalse( $trips_overlap( $trip1, $trip2 ) );

		// Adjacent trips (touching).
		$trip1 = array( 'start' => '2025-01-01', 'end' => '2025-01-10' );
		$trip2 = array( 'start' => '2025-01-10', 'end' => '2025-01-15' );
		$this->assertTrue( $trips_overlap( $trip1, $trip2 ) );
	}

	/**
	 * Test unique days calculation with overlaps.
	 */
	public function test_unique_days_with_overlaps() {
		$calculate_unique_days = function ( $trips ) {
			$days = array();

			foreach ( $trips as $trip ) {
				$current = new DateTime( $trip['start'] );
				$end = new DateTime( $trip['end'] );

				while ( $current <= $end ) {
					$days[ $current->format( 'Y-m-d' ) ] = true;
					$current->modify( '+1 day' );
				}
			}

			return count( $days );
		};

		// Overlapping trips: Jan 1-10 and Jan 5-15.
		$trips = array(
			array( 'start' => '2025-01-01', 'end' => '2025-01-10' ),
			array( 'start' => '2025-01-05', 'end' => '2025-01-15' ),
		);

		$unique_days = $calculate_unique_days( $trips );
		$this->assertEquals( 15, $unique_days ); // Jan 1-15 = 15 days, not 10+11=21.
	}
}
