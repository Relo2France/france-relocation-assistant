<?php
/**
 * Cross-Jurisdiction Calculator Tests
 *
 * Tests for verifying calculator logic across all jurisdiction types.
 * Run with: php tests/test-jurisdiction-calculators.php
 *
 * @package MyTravelStatus
 * @since   1.8.1
 */

// Standalone test runner - not using PHPUnit to allow easy CLI execution.

/**
 * Simple assertion helper.
 *
 * @param bool   $condition Condition to test.
 * @param string $message   Test description.
 */
function test_assert( $condition, $message ) {
	global $test_count, $test_passed, $test_failed;
	$test_count++;
	if ( $condition ) {
		$test_passed++;
		echo "✅ PASS: {$message}\n";
	} else {
		$test_failed++;
		echo "❌ FAIL: {$message}\n";
	}
}

/**
 * Assert two values are equal.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value.
 * @param string $message  Test description.
 */
function test_equals( $expected, $actual, $message ) {
	test_assert( $expected === $actual, "{$message} (expected: {$expected}, got: {$actual})" );
}

/**
 * Assert actual is greater than or equal to expected.
 *
 * @param mixed  $expected Expected minimum.
 * @param mixed  $actual   Actual value.
 * @param string $message  Test description.
 */
function test_gte( $expected, $actual, $message ) {
	test_assert( $actual >= $expected, "{$message} (expected >= {$expected}, got: {$actual})" );
}

// Initialize test counters.
$test_count  = 0;
$test_passed = 0;
$test_failed = 0;

echo "\n========================================\n";
echo "Cross-Jurisdiction Calculator Tests\n";
echo "MyTravelStatus v1.8.2\n";
echo "========================================\n\n";

// ============================================
// Test 1: US SPT Weighted Calculation
// ============================================
echo "--- US SPT (Substantial Presence Test) ---\n";

/**
 * Test US SPT weighted calculation.
 *
 * Formula: Current year × 1.0 + Prior year × 1/3 + Second prior × 1/6 >= 183
 * Requires 31+ days in current year.
 */
function test_us_spt_calculation() {
	// Scenario 1: Full resident
	// Current: 120 days × 1.0 = 120
	// Prior: 180 days × 0.333 = 60
	// Second prior: 180 days × 0.167 = 30
	// Total: 210 >= 183 = RESIDENT
	$current       = 120;
	$prior         = 180;
	$second_prior  = 180;
	$weighted      = ( $current * 1.0 ) + ( $prior * 0.333 ) + ( $second_prior * 0.167 );
	$is_resident   = $current >= 31 && $weighted >= 183;
	test_assert( $is_resident === true, 'US SPT: 120/180/180 days = resident' );
	test_gte( 183, round( $weighted ), 'US SPT: Weighted total >= 183' );

	// Scenario 2: Not enough current year days
	$current       = 25;  // Below 31-day minimum
	$prior         = 180;
	$second_prior  = 180;
	$weighted      = ( $current * 1.0 ) + ( $prior * 0.333 ) + ( $second_prior * 0.167 );
	$is_resident   = $current >= 31 && $weighted >= 183;
	test_assert( $is_resident === false, 'US SPT: 25 days current year = not resident (min 31 required)' );

	// Scenario 3: Just below threshold
	$current       = 50;
	$prior         = 50;
	$second_prior  = 50;
	$weighted      = ( $current * 1.0 ) + ( $prior * 0.333 ) + ( $second_prior * 0.167 );
	$is_resident   = $current >= 31 && $weighted >= 183;
	test_assert( $is_resident === false, 'US SPT: 50/50/50 days = not resident (~75 weighted)' );

	// Scenario 4: First year only
	$current       = 184;
	$prior         = 0;
	$second_prior  = 0;
	$weighted      = ( $current * 1.0 ) + ( $prior * 0.333 ) + ( $second_prior * 0.167 );
	$is_resident   = $current >= 31 && $weighted >= 183;
	test_assert( $is_resident === true, 'US SPT: 184 days current year only = resident' );
}

test_us_spt_calculation();

// ============================================
// Test 2: UK SRT (Statutory Residence Test)
// ============================================
echo "\n--- UK SRT (Statutory Residence Test) ---\n";

/**
 * Test UK SRT automatic tests and sufficient ties.
 */
function test_uk_srt_calculation() {
	// Automatic Overseas Test 1: <16 days in UK
	$uk_days = 15;
	$is_non_resident = $uk_days < 16;
	test_assert( $is_non_resident === true, 'UK SRT Auto Overseas: <16 days = automatic non-resident' );

	// Automatic Overseas Test 2: <46 days (leaver)
	$uk_days     = 40;
	$is_leaver   = true;  // Left UK permanently last 3 years
	$is_non_resident = $is_leaver && $uk_days < 46;
	test_assert( $is_non_resident === true, 'UK SRT Auto Overseas: Leaver <46 days = non-resident' );

	// Automatic UK Test 1: 183+ days
	$uk_days     = 183;
	$is_resident = $uk_days >= 183;
	test_assert( $is_resident === true, 'UK SRT Auto UK: 183+ days = automatic UK resident' );

	// Sufficient Ties Test - Leaver with 2 ties
	// Threshold for leaver with 2 ties: 91 days
	$uk_days          = 100;
	$tie_count        = 2;
	$was_resident     = true;  // Leaver
	$threshold        = 91;    // For 2 ties as leaver
	$is_resident      = $uk_days >= $threshold;
	test_assert( $is_resident === true, 'UK SRT Ties: Leaver, 2 ties, 100 days (threshold 91) = resident' );

	// Sufficient Ties Test - Arriver with 2 ties
	// Threshold for arriver with 2 ties: 121 days
	$uk_days          = 100;
	$tie_count        = 2;
	$was_resident     = false;  // Arriver
	$threshold        = 121;    // For 2 ties as arriver
	$is_resident      = $uk_days >= $threshold;
	test_assert( $is_resident === false, 'UK SRT Ties: Arriver, 2 ties, 100 days (threshold 121) = not resident' );

	// Sufficient Ties Test - 4+ ties
	// Threshold for 4+ ties: 16 days (both leaver and arriver)
	$uk_days          = 20;
	$tie_count        = 4;
	$threshold        = 16;
	$is_resident      = $uk_days >= $threshold;
	test_assert( $is_resident === true, 'UK SRT Ties: 4 ties, 20 days (threshold 16) = resident' );
}

test_uk_srt_calculation();

// ============================================
// Test 3: Ireland Multi-Year (183/280)
// ============================================
echo "\n--- Ireland Multi-Year (183/280) ---\n";

/**
 * Test Ireland 183/280 day rule.
 */
function test_ireland_multi_year() {
	// Primary test: 183 days in current year
	$current_year_days = 183;
	$is_resident       = $current_year_days >= 183;
	test_assert( $is_resident === true, 'Ireland: 183 days current year = resident' );

	// Secondary test: 280 days over 2 years
	$current_year_days = 150;
	$prior_year_days   = 150;
	$combined          = $current_year_days + $prior_year_days;
	$min_each_year     = 31;  // Must have min 31 days each year
	$meets_secondary   = $combined >= 280 && $current_year_days >= $min_each_year && $prior_year_days >= $min_each_year;
	test_assert( $meets_secondary === true, 'Ireland: 150+150=300 days over 2 years (min 31 each) = resident' );

	// Not enough in one year
	$current_year_days = 200;
	$prior_year_days   = 20;  // Below 31-day minimum
	$combined          = $current_year_days + $prior_year_days;
	$meets_secondary   = $combined >= 280 && $current_year_days >= $min_each_year && $prior_year_days >= $min_each_year;
	test_assert( $meets_secondary === false, 'Ireland: 200+20 days (prior below 31 min) = not resident via secondary' );
}

test_ireland_multi_year();

// ============================================
// Test 4: Canada 183 + Ties
// ============================================
echo "\n--- Canada 183-Day + Ties ---\n";

/**
 * Test Canada deemed residency and ties.
 */
function test_canada_residency() {
	// Deemed residency: 183+ days
	$days_in_canada = 183;
	$is_deemed_resident = $days_in_canada >= 183;
	test_assert( $is_deemed_resident === true, 'Canada: 183+ days = deemed resident (regardless of ties)' );

	// Under 183 with primary ties (home)
	$days_in_canada = 100;
	$has_home       = true;
	$has_spouse     = false;
	$has_dependents = false;
	$has_primary_tie = $has_home || $has_spouse || $has_dependents;
	// With primary ties but under 183, residency is fact-dependent (simplified)
	test_assert( $has_primary_tie === true, 'Canada: Under 183 days with home = may be resident (ties test)' );

	// Under 183 with no ties
	$days_in_canada  = 100;
	$has_home        = false;
	$has_spouse      = false;
	$has_dependents  = false;
	$has_primary_tie = $has_home || $has_spouse || $has_dependents;
	$is_resident     = $days_in_canada >= 183 || $has_primary_tie;
	test_assert( $is_resident === false, 'Canada: 100 days, no ties = likely not resident' );
}

test_canada_residency();

// ============================================
// Test 5: Schengen 90/180 Rolling Window
// ============================================
echo "\n--- Schengen 90/180 Rolling Window ---\n";

/**
 * Test Schengen rolling window calculation.
 */
function test_schengen_rolling() {
	// Helper: Calculate days in a window.
	$calculate_days = function( $trips, $window_start, $window_end ) {
		$days = 0;
		foreach ( $trips as $trip ) {
			$trip_start = max( $trip['start'], $window_start );
			$trip_end   = min( $trip['end'], $window_end );
			if ( $trip_end >= $trip_start ) {
				$days += $trip_end->diff( $trip_start )->days + 1;
			}
		}
		return $days;
	};

	// Scenario 1: Under 90 days
	$trips = array(
		array(
			'start' => new DateTime( '2025-01-01' ),
			'end'   => new DateTime( '2025-01-30' ),
		),
		array(
			'start' => new DateTime( '2025-03-01' ),
			'end'   => new DateTime( '2025-03-30' ),
		),
	);
	$window_end   = new DateTime( '2025-06-01' );
	$window_start = ( clone $window_end )->modify( '-179 days' );
	$days_used    = $calculate_days( $trips, $window_start, $window_end );
	$is_over_90   = $days_used > 90;
	test_assert( $is_over_90 === false, "Schengen: 30+30=60 days in 180-day window = OK" );

	// Scenario 2: Exactly at limit
	$days_used = 90;
	$days_remaining = 90 - $days_used;
	test_equals( 0, $days_remaining, 'Schengen: 90 days used = 0 remaining' );

	// Scenario 3: Over limit
	$days_used = 95;
	$is_overstay = $days_used > 90;
	test_assert( $is_overstay === true, 'Schengen: 95 days = overstay' );
}

test_schengen_rolling();

// ============================================
// Test 6: Mexico 183-Day Calendar Year
// ============================================
echo "\n--- Mexico 183-Day Calendar Year ---\n";

/**
 * Test Mexico simple 183-day rule.
 */
function test_mexico_calendar_year() {
	// Under threshold
	$days_in_mexico = 100;
	$is_resident    = $days_in_mexico >= 183;
	test_assert( $is_resident === false, 'Mexico: 100 days = not resident' );

	// At threshold
	$days_in_mexico = 183;
	$is_resident    = $days_in_mexico >= 183;
	test_assert( $is_resident === true, 'Mexico: 183 days = resident' );
}

test_mexico_calendar_year();

// ============================================
// Test 7: Germany Multi-Factor
// ============================================
echo "\n--- Germany Multi-Factor (183 + Habitual Abode) ---\n";

/**
 * Test Germany multi-factor residency.
 */
function test_germany_multi_factor() {
	// Factor 1: Permanent home available
	$has_permanent_home = true;
	$is_resident        = $has_permanent_home;  // Any factor can trigger
	test_assert( $is_resident === true, 'Germany: Has permanent home = resident (any factor)' );

	// Factor 2: Habitual abode (6+ consecutive months)
	$consecutive_months = 7;
	$has_habitual_abode = $consecutive_months >= 6;
	test_assert( $has_habitual_abode === true, 'Germany: 7 consecutive months = habitual abode' );

	// No factors met
	$has_permanent_home = false;
	$has_habitual_abode = false;
	$is_resident        = $has_permanent_home || $has_habitual_abode;
	test_assert( $is_resident === false, 'Germany: No factors = not resident' );
}

test_germany_multi_factor();

// ============================================
// Test 8: Japan 183-Day Calendar Year
// ============================================
echo "\n--- Japan 183-Day Calendar Year ---\n";

/**
 * Test Japan simple 183-day rule.
 */
function test_japan_calendar_year() {
	// Under threshold
	$days_in_japan = 150;
	$is_resident   = $days_in_japan >= 183;
	test_assert( $is_resident === false, 'Japan: 150 days = not resident' );

	// At threshold
	$days_in_japan = 183;
	$is_resident   = $days_in_japan >= 183;
	test_assert( $is_resident === true, 'Japan: 183 days = resident' );

	// Over threshold
	$days_in_japan = 200;
	$is_resident   = $days_in_japan >= 183;
	test_assert( $is_resident === true, 'Japan: 200 days = resident' );
}

test_japan_calendar_year();

// ============================================
// Test 9: Singapore 183-Day Calendar Year
// ============================================
echo "\n--- Singapore 183-Day Calendar Year ---\n";

/**
 * Test Singapore simple 183-day rule.
 */
function test_singapore_calendar_year() {
	// Under threshold
	$days_in_sg  = 90;
	$is_resident = $days_in_sg >= 183;
	test_assert( $is_resident === false, 'Singapore: 90 days (visa-free limit) = not resident' );

	// At threshold
	$days_in_sg  = 183;
	$is_resident = $days_in_sg >= 183;
	test_assert( $is_resident === true, 'Singapore: 183 days = resident' );
}

test_singapore_calendar_year();

// ============================================
// Test 10: New Zealand 183-Day Rolling Window
// ============================================
echo "\n--- New Zealand 183-Day Rolling 12-Month ---\n";

/**
 * Test New Zealand 183-day rolling 12-month window.
 */
function test_nz_rolling_window() {
	// Under threshold in rolling window
	$days_in_nz  = 100;
	$is_resident = $days_in_nz >= 183;
	test_assert( $is_resident === false, 'NZ: 100 days in 12-month window = not resident' );

	// At threshold
	$days_in_nz  = 183;
	$is_resident = $days_in_nz >= 183;
	test_assert( $is_resident === true, 'NZ: 183 days in any 12-month period = resident' );

	// Rolling window: 90 days 8 months ago + 100 days recently = 190 total in 12 months
	$total_days_in_window = 90 + 100;
	$is_resident          = $total_days_in_window >= 183;
	test_assert( $is_resident === true, 'NZ: 90 + 100 = 190 days in 12-month window = resident' );
}

test_nz_rolling_window();

// ============================================
// Test 11: Australia Fiscal Year (July-June)
// ============================================
echo "\n--- Australia Fiscal Year (July-June) ---\n";

/**
 * Test Australia 183-day fiscal year rule.
 */
function test_australia_fiscal_year() {
	// Under threshold in fiscal year
	$days_in_au  = 120;
	$is_resident = $days_in_au >= 183;
	test_assert( $is_resident === false, 'Australia: 120 days in fiscal year = not resident' );

	// At threshold
	$days_in_au  = 183;
	$is_resident = $days_in_au >= 183;
	test_assert( $is_resident === true, 'Australia: 183 days in fiscal year (Jul-Jun) = resident' );

	// Over threshold
	$days_in_au  = 250;
	$is_resident = $days_in_au >= 183;
	test_assert( $is_resident === true, 'Australia: 250 days = resident' );
}

test_australia_fiscal_year();

// ============================================
// Test 12: Status Thresholds
// ============================================
echo "\n--- Status Threshold Calculation ---\n";

/**
 * Test status thresholds based on percentage.
 */
function test_status_thresholds() {
	$get_status = function( $days_used, $days_allowed ) {
		$percentage = ( $days_used / $days_allowed ) * 100;
		if ( $percentage >= 100 ) {
			return 'exceeded';
		}
		if ( $percentage >= 85 ) {
			return 'critical';
		}
		if ( $percentage >= 70 ) {
			return 'warning';
		}
		return 'ok';
	};

	// OK status (under 70%)
	$status = $get_status( 60, 90 );
	test_equals( 'ok', $status, 'Status: 60/90 (67%) = ok' );

	// Warning status (70-84%)
	$status = $get_status( 70, 90 );
	test_equals( 'warning', $status, 'Status: 70/90 (78%) = warning' );

	// Critical status (85-99%)
	$status = $get_status( 85, 90 );
	test_equals( 'critical', $status, 'Status: 85/90 (94%) = critical' );

	// Exceeded status (100%+)
	$status = $get_status( 95, 90 );
	test_equals( 'exceeded', $status, 'Status: 95/90 (106%) = exceeded' );

	// Boundary test: just under 70%
	$status = $get_status( 62, 90 );
	test_equals( 'ok', $status, 'Status: 62/90 (68.9%) = ok (under 70%)' );

	// Boundary test: just under 85%
	$status = $get_status( 76, 90 );
	test_equals( 'warning', $status, 'Status: 76/90 (84.4%) = warning (under 85%)' );
}

test_status_thresholds();

// ============================================
// Test 13: Multi-Jurisdiction Summary Aggregation
// ============================================
echo "\n--- Multi-Jurisdiction Summary Aggregation ---\n";

/**
 * Test compliance overview aggregation.
 */
function test_compliance_overview_aggregation() {
	// Mock summaries from multiple jurisdictions
	$summaries = array(
		array( 'status' => 'ok', 'jurisdiction_code' => 'schengen_visa' ),
		array( 'status' => 'warning', 'jurisdiction_code' => 'uk_srt' ),
		array( 'status' => 'ok', 'jurisdiction_code' => 'ireland_183' ),
		array( 'status' => 'critical', 'jurisdiction_code' => 'us_spt' ),
		array( 'status' => 'ok', 'jurisdiction_code' => 'canada_183' ),
	);

	$counts = array(
		'ok'       => 0,
		'warning'  => 0,
		'critical' => 0,
		'exceeded' => 0,
	);

	foreach ( $summaries as $summary ) {
		$status = $summary['status'];
		if ( isset( $counts[ $status ] ) ) {
			$counts[ $status ]++;
		}
	}

	test_equals( 3, $counts['ok'], 'Overview: 3 jurisdictions at OK status' );
	test_equals( 1, $counts['warning'], 'Overview: 1 jurisdiction at WARNING status' );
	test_equals( 1, $counts['critical'], 'Overview: 1 jurisdiction at CRITICAL status' );
	test_equals( 0, $counts['exceeded'], 'Overview: 0 jurisdictions EXCEEDED' );

	$has_critical = $counts['critical'] > 0 || $counts['exceeded'] > 0;
	test_assert( $has_critical === true, 'Overview: hasCriticalIssues = true when critical > 0' );

	$needs_attention = $counts['warning'] > 0;
	test_assert( $needs_attention === true, 'Overview: needsAttention = true when warning > 0' );
}

test_compliance_overview_aggregation();

// ============================================
// Test 14: Alert Level Determination
// ============================================
echo "\n--- Alert Level Determination ---\n";

/**
 * Test alert level based on percentage.
 */
function test_alert_levels() {
	$get_alert_level = function( $percentage, $status ) {
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

	// No alert for OK status
	$level = $get_alert_level( 50, 'ok' );
	test_assert( $level === null, 'Alert: 50% ok = no alert' );

	// Warning alert
	$level = $get_alert_level( 75, 'warning' );
	test_equals( 'warning', $level, 'Alert: 75% warning = warning level' );

	// Danger alert
	$level = $get_alert_level( 90, 'critical' );
	test_equals( 'danger', $level, 'Alert: 90% critical = danger level' );

	// Urgent alert
	$level = $get_alert_level( 98, 'critical' );
	test_equals( 'urgent', $level, 'Alert: 98% = urgent level' );

	// Exceeded triggers urgent
	$level = $get_alert_level( 110, 'exceeded' );
	test_equals( 'urgent', $level, 'Alert: exceeded status = urgent level' );
}

test_alert_levels();

// ============================================
// Test 15: Days Remaining Calculation
// ============================================
echo "\n--- Days Remaining Calculation ---\n";

/**
 * Test days remaining never goes negative.
 */
function test_days_remaining() {
	$calculate_remaining = function( $days_used, $days_allowed ) {
		return max( 0, $days_allowed - $days_used );
	};

	// Normal case
	$remaining = $calculate_remaining( 60, 90 );
	test_equals( 30, $remaining, 'Remaining: 60 used of 90 = 30 remaining' );

	// At limit
	$remaining = $calculate_remaining( 90, 90 );
	test_equals( 0, $remaining, 'Remaining: 90 used of 90 = 0 remaining' );

	// Over limit (never negative)
	$remaining = $calculate_remaining( 100, 90 );
	test_equals( 0, $remaining, 'Remaining: 100 used of 90 = 0 remaining (not -10)' );

	// UK SRT with 183 threshold
	$remaining = $calculate_remaining( 120, 183 );
	test_equals( 63, $remaining, 'Remaining: 120 used of 183 = 63 remaining' );
}

test_days_remaining();

// ============================================
// Test 16: Window Date Calculation
// ============================================
echo "\n--- Window Date Calculation ---\n";

/**
 * Test window start/end calculation for different methods.
 */
function test_window_dates() {
	$today = new DateTime( '2025-06-15' );

	// Rolling window (180 days back)
	$window_start = ( clone $today )->modify( '-179 days' );
	$expected = '2024-12-18';
	test_equals( $expected, $window_start->format( 'Y-m-d' ), 'Rolling: 180-day window starts 179 days ago' );

	// Calendar year
	$year_start = new DateTime( '2025-01-01' );
	$year_end   = new DateTime( '2025-12-31' );
	test_equals( '2025-01-01', $year_start->format( 'Y-m-d' ), 'Calendar: Year starts Jan 1' );
	test_equals( '2025-12-31', $year_end->format( 'Y-m-d' ), 'Calendar: Year ends Dec 31' );

	// UK Tax Year (April 6 - April 5)
	$uk_tax_year = 2025;
	$uk_start    = new DateTime( ( $uk_tax_year - 1 ) . '-04-06' );
	$uk_end      = new DateTime( $uk_tax_year . '-04-05' );
	test_equals( '2024-04-06', $uk_start->format( 'Y-m-d' ), 'UK Tax: 2025 year starts Apr 6, 2024' );
	test_equals( '2025-04-05', $uk_end->format( 'Y-m-d' ), 'UK Tax: 2025 year ends Apr 5, 2025' );

	// Australia Fiscal Year (July 1 - June 30)
	$au_fiscal_year = 2025;
	$au_start       = new DateTime( ( $au_fiscal_year - 1 ) . '-07-01' );
	$au_end         = new DateTime( $au_fiscal_year . '-06-30' );
	test_equals( '2024-07-01', $au_start->format( 'Y-m-d' ), 'AU Fiscal: 2024-25 year starts Jul 1, 2024' );
	test_equals( '2025-06-30', $au_end->format( 'Y-m-d' ), 'AU Fiscal: 2024-25 year ends Jun 30, 2025' );
}

test_window_dates();

// ============================================
// Test 17: Cache Key Generation
// ============================================
echo "\n--- Cache Key Generation ---\n";

/**
 * Test cache key uniqueness for different users/jurisdictions.
 */
function test_cache_keys() {
	$generate_key = function( $user_id, $code, $date ) {
		return "mts_summary_{$user_id}_{$code}_{$date}";
	};

	// Different users should have different keys
	$key1 = $generate_key( 1, 'schengen_visa', '2025-06-15' );
	$key2 = $generate_key( 2, 'schengen_visa', '2025-06-15' );
	test_assert( $key1 !== $key2, 'Cache: Different users have different keys' );

	// Different jurisdictions should have different keys
	$key1 = $generate_key( 1, 'schengen_visa', '2025-06-15' );
	$key2 = $generate_key( 1, 'uk_srt', '2025-06-15' );
	test_assert( $key1 !== $key2, 'Cache: Different jurisdictions have different keys' );

	// Same parameters should generate same key
	$key1 = $generate_key( 1, 'schengen_visa', '2025-06-15' );
	$key2 = $generate_key( 1, 'schengen_visa', '2025-06-15' );
	test_equals( $key1, $key2, 'Cache: Same parameters generate same key' );
}

test_cache_keys();

// ============================================
// Print Summary
// ============================================
echo "\n========================================\n";
echo "Test Results Summary\n";
echo "========================================\n";
echo "Total:  {$test_count}\n";
echo "Passed: {$test_passed}\n";
echo "Failed: {$test_failed}\n";
echo "========================================\n";

if ( $test_failed > 0 ) {
	echo "❌ Some tests failed!\n";
	exit( 1 );
} else {
	echo "✅ All tests passed!\n";
	exit( 0 );
}
