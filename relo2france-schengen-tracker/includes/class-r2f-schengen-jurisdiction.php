<?php
/**
 * Jurisdiction Rules Engine for Schengen Tracker.
 *
 * Handles multi-jurisdiction tracking with different counting methods:
 * - Rolling window (e.g., Schengen 90/180)
 * - Calendar year (e.g., US state residency)
 * - Fiscal year (custom start date)
 *
 * @package R2F_Schengen_Tracker
 * @since   1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jurisdiction management class.
 */
class R2F_Schengen_Jurisdiction {

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Jurisdiction
	 */
	private static $instance = null;

	/**
	 * Cached rules.
	 *
	 * @var array
	 */
	private $rules_cache = array();

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Jurisdiction
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$namespace = 'r2f-schengen/v1';

		// Get all jurisdiction rules.
		register_rest_route(
			$namespace,
			'/jurisdictions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get single jurisdiction rule.
		register_rest_route(
			$namespace,
			'/jurisdictions/(?P<code>[a-z0-9_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'code' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get user's tracked jurisdictions.
		register_rest_route(
			$namespace,
			'/jurisdictions/tracked',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tracked_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Add jurisdiction to tracking.
		register_rest_route(
			$namespace,
			'/jurisdictions/tracked',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_tracked_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'code' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Remove jurisdiction from tracking.
		register_rest_route(
			$namespace,
			'/jurisdictions/tracked/(?P<code>[a-z0-9_]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_tracked_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'code' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get summary for a jurisdiction.
		register_rest_route(
			$namespace,
			'/jurisdictions/(?P<code>[a-z0-9_]+)/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'code' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get multi-jurisdiction summary.
		register_rest_route(
			$namespace,
			'/jurisdictions/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_multi_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Legacy namespace support.
		$legacy_namespace = 'fra-portal/v1';

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/(?P<code>[a-z0-9_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/tracked',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_tracked_jurisdictions' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'add_tracked_jurisdiction' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/tracked/(?P<code>[a-z0-9_]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_tracked_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/(?P<code>[a-z0-9_]+)/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_multi_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check if user has permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in.', 'r2f-schengen' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get all jurisdiction rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_jurisdictions( $request ) {
		$type = $request->get_param( 'type' );

		$rules = $this->get_all_rules( $type );

		return rest_ensure_response( $rules );
	}

	/**
	 * Get single jurisdiction rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_jurisdiction( $request ) {
		$code = $request->get_param( 'code' );
		$rule = $this->get_rule( $code );

		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'Jurisdiction not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $rule );
	}

	/**
	 * Get user's tracked jurisdictions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_tracked_jurisdictions( $request ) {
		$user_id  = get_current_user_id();
		$tracked  = get_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', true );

		if ( empty( $tracked ) ) {
			// Default to Schengen.
			$tracked = array( 'schengen' );
		}

		// Get full rule data for each tracked jurisdiction.
		$jurisdictions = array();
		foreach ( $tracked as $code ) {
			$rule = $this->get_rule( $code );
			if ( $rule ) {
				$jurisdictions[] = $rule;
			}
		}

		return rest_ensure_response( $jurisdictions );
	}

	/**
	 * Add jurisdiction to tracking.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_tracked_jurisdiction( $request ) {
		$user_id = get_current_user_id();
		$code    = $request->get_param( 'code' );

		// Verify jurisdiction exists.
		$rule = $this->get_rule( $code );
		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'Jurisdiction not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		$tracked = get_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', true );
		if ( empty( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		if ( ! in_array( $code, $tracked, true ) ) {
			$tracked[] = $code;
			update_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', $tracked );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'tracked' => $tracked,
			)
		);
	}

	/**
	 * Remove jurisdiction from tracking.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_tracked_jurisdiction( $request ) {
		$user_id = get_current_user_id();
		$code    = $request->get_param( 'code' );

		// Don't allow removing Schengen (primary).
		if ( 'schengen' === $code ) {
			return new WP_Error(
				'cannot_remove',
				__( 'Cannot remove Schengen from tracking.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$tracked = get_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', true );
		if ( empty( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		$tracked = array_values( array_diff( $tracked, array( $code ) ) );
		update_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', $tracked );

		return rest_ensure_response(
			array(
				'success' => true,
				'tracked' => $tracked,
			)
		);
	}

	/**
	 * Get summary for a specific jurisdiction.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_jurisdiction_summary( $request ) {
		$user_id = get_current_user_id();
		$code    = $request->get_param( 'code' );
		$date    = $request->get_param( 'date' );

		$rule = $this->get_rule( $code );
		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'Jurisdiction not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		$reference_date = $date ? new DateTime( $date ) : new DateTime();
		$summary        = $this->calculate_summary( $user_id, $rule, $reference_date );

		return rest_ensure_response( $summary );
	}

	/**
	 * Get multi-jurisdiction summary for all tracked jurisdictions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_multi_jurisdiction_summary( $request ) {
		$user_id = get_current_user_id();
		$tracked = get_user_meta( $user_id, 'r2f_schengen_tracked_jurisdictions', true );

		if ( empty( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		$reference_date = new DateTime();
		$summaries      = array();

		foreach ( $tracked as $code ) {
			$rule = $this->get_rule( $code );
			if ( $rule ) {
				$summary              = $this->calculate_summary( $user_id, $rule, $reference_date );
				$summary['rule']      = $rule;
				$summaries[ $code ] = $summary;
			}
		}

		return rest_ensure_response( $summaries );
	}

	/**
	 * Get all jurisdiction rules from database.
	 *
	 * @param string|null $type Filter by type (zone, country, state).
	 * @return array
	 */
	public function get_all_rules( $type = null ) {
		global $wpdb;

		$cache_key = 'all_rules_' . ( $type ?? 'all' );

		if ( isset( $this->rules_cache[ $cache_key ] ) ) {
			return $this->rules_cache[ $cache_key ];
		}

		$table = R2F_Schengen_Schema::get_table( 'jurisdiction_rules' );

		if ( $type ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rules = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE is_active = 1 AND type = %s ORDER BY display_order ASC, name ASC",
					$type
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rules = $wpdb->get_results(
				"SELECT * FROM $table WHERE is_active = 1 ORDER BY display_order ASC, name ASC",
				ARRAY_A
			);
		}

		// Convert to proper types.
		$rules = array_map( array( $this, 'format_rule' ), $rules );

		$this->rules_cache[ $cache_key ] = $rules;

		return $rules;
	}

	/**
	 * Get single rule by code.
	 *
	 * @param string $code Jurisdiction code.
	 * @return array|null
	 */
	public function get_rule( $code ) {
		global $wpdb;

		if ( isset( $this->rules_cache[ $code ] ) ) {
			return $this->rules_cache[ $code ];
		}

		$table = R2F_Schengen_Schema::get_table( 'jurisdiction_rules' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE code = %s AND is_active = 1",
				$code
			),
			ARRAY_A
		);

		if ( ! $rule ) {
			return null;
		}

		$rule = $this->format_rule( $rule );

		$this->rules_cache[ $code ] = $rule;

		return $rule;
	}

	/**
	 * Format rule data for API response.
	 *
	 * @param array $rule Raw rule data.
	 * @return array Formatted rule.
	 */
	private function format_rule( $rule ) {
		return array(
			'id'             => (int) $rule['id'],
			'code'           => $rule['code'],
			'name'           => $rule['name'],
			'type'           => $rule['type'],
			'parentCode'     => $rule['parent_code'],
			'daysAllowed'    => (int) $rule['days_allowed'],
			'windowDays'     => (int) $rule['window_days'],
			'countingMethod' => $rule['counting_method'],
			'resetMonth'     => $rule['reset_month'] ? (int) $rule['reset_month'] : null,
			'resetDay'       => $rule['reset_day'] ? (int) $rule['reset_day'] : null,
			'description'    => $rule['description'],
			'notes'          => $rule['notes'],
			'isSystem'       => (bool) $rule['is_system'],
		);
	}

	/**
	 * Calculate summary for a jurisdiction.
	 *
	 * @param int      $user_id        User ID.
	 * @param array    $rule           Jurisdiction rule.
	 * @param DateTime $reference_date Reference date for calculation.
	 * @return array Summary data.
	 */
	public function calculate_summary( $user_id, $rule, $reference_date ) {
		$trips = $this->get_trips_for_jurisdiction( $user_id, $rule['code'] );

		$days_used = $this->calculate_days_used(
			$trips,
			$rule,
			$reference_date
		);

		$days_remaining = max( 0, $rule['daysAllowed'] - $days_used );
		$percentage     = ( $days_used / $rule['daysAllowed'] ) * 100;

		// Determine status.
		$status = 'safe';
		if ( $percentage >= 100 ) {
			$status = 'exceeded';
		} elseif ( $percentage >= 94 ) { // ~85/90 for Schengen.
			$status = 'critical';
		} elseif ( $percentage >= 89 ) { // ~80/90 for Schengen.
			$status = 'danger';
		} elseif ( $percentage >= 67 ) { // ~60/90 for Schengen.
			$status = 'warning';
		}

		// Calculate window dates.
		$window_start = clone $reference_date;
		$window_end   = clone $reference_date;

		if ( 'rolling' === $rule['countingMethod'] ) {
			$window_start->modify( '-' . ( $rule['windowDays'] - 1 ) . ' days' );
		} elseif ( 'calendar_year' === $rule['countingMethod'] ) {
			$window_start->setDate(
				(int) $reference_date->format( 'Y' ),
				$rule['resetMonth'] ?? 1,
				$rule['resetDay'] ?? 1
			);
			$window_end = clone $window_start;
			$window_end->modify( '+1 year -1 day' );
		} elseif ( 'fiscal_year' === $rule['countingMethod'] ) {
			// Calculate fiscal year start based on reset date.
			$fiscal_start_month = $rule['resetMonth'] ?? 4; // Default April.
			$fiscal_start_day   = $rule['resetDay'] ?? 1;

			$current_year = (int) $reference_date->format( 'Y' );
			$window_start->setDate( $current_year, $fiscal_start_month, $fiscal_start_day );

			// If reference date is before fiscal year start, use previous year.
			if ( $reference_date < $window_start ) {
				$window_start->modify( '-1 year' );
			}

			$window_end = clone $window_start;
			$window_end->modify( '+1 year -1 day' );
		}

		// Find next expiring days (for rolling window).
		$next_expiring_date  = null;
		$next_expiring_count = 0;

		if ( 'rolling' === $rule['countingMethod'] && ! empty( $trips ) ) {
			$expiring = $this->calculate_next_expiring_days( $trips, $rule, $reference_date );
			$next_expiring_date  = $expiring['date'];
			$next_expiring_count = $expiring['count'];
		}

		return array(
			'jurisdictionCode' => $rule['code'],
			'daysUsed'         => $days_used,
			'daysAllowed'      => $rule['daysAllowed'],
			'daysRemaining'    => $days_remaining,
			'percentage'       => round( $percentage, 1 ),
			'status'           => $status,
			'windowStart'      => $window_start->format( 'Y-m-d' ),
			'windowEnd'        => $window_end->format( 'Y-m-d' ),
			'referenceDate'    => $reference_date->format( 'Y-m-d' ),
			'countingMethod'   => $rule['countingMethod'],
			'nextExpiringDate' => $next_expiring_date,
			'nextExpiringDays' => $next_expiring_count,
			'tripCount'        => count( $trips ),
		);
	}

	/**
	 * Calculate days used based on counting method.
	 *
	 * @param array    $trips          Array of trips.
	 * @param array    $rule           Jurisdiction rule.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days used.
	 */
	public function calculate_days_used( $trips, $rule, $reference_date ) {
		switch ( $rule['countingMethod'] ) {
			case 'rolling':
				return $this->calculate_rolling_window( $trips, $rule['windowDays'], $reference_date );

			case 'calendar_year':
				return $this->calculate_calendar_year( $trips, $rule, $reference_date );

			case 'fiscal_year':
				return $this->calculate_fiscal_year( $trips, $rule, $reference_date );

			default:
				return $this->calculate_rolling_window( $trips, $rule['windowDays'], $reference_date );
		}
	}

	/**
	 * Calculate days in rolling window.
	 *
	 * @param array    $trips          Trips.
	 * @param int      $window_days    Window size in days.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days used.
	 */
	private function calculate_rolling_window( $trips, $window_days, $reference_date ) {
		$window_start = clone $reference_date;
		$window_start->modify( '-' . ( $window_days - 1 ) . ' days' );

		$days_used = 0;

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip['start_date'] );
			$trip_end   = new DateTime( $trip['end_date'] );

			// Skip trips entirely outside the window.
			if ( $trip_end < $window_start || $trip_start > $reference_date ) {
				continue;
			}

			// Clamp trip dates to window.
			$effective_start = max( $trip_start, $window_start );
			$effective_end   = min( $trip_end, $reference_date );

			// Calculate days (inclusive).
			$interval   = $effective_start->diff( $effective_end );
			$days_used += $interval->days + 1;
		}

		return $days_used;
	}

	/**
	 * Calculate days in calendar year.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule with reset date info.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days used.
	 */
	private function calculate_calendar_year( $trips, $rule, $reference_date ) {
		$reset_month = $rule['resetMonth'] ?? 1;
		$reset_day   = $rule['resetDay'] ?? 1;

		$year_start = new DateTime();
		$year_start->setDate(
			(int) $reference_date->format( 'Y' ),
			$reset_month,
			$reset_day
		);
		$year_start->setTime( 0, 0, 0 );

		// If reference date is before year start, use previous year.
		if ( $reference_date < $year_start ) {
			$year_start->modify( '-1 year' );
		}

		$year_end = clone $year_start;
		$year_end->modify( '+1 year -1 day' );

		$days_used = 0;

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip['start_date'] );
			$trip_end   = new DateTime( $trip['end_date'] );

			// Skip trips entirely outside the year.
			if ( $trip_end < $year_start || $trip_start > $year_end ) {
				continue;
			}

			// Clamp to year and reference date.
			$effective_start = max( $trip_start, $year_start );
			$effective_end   = min( $trip_end, $reference_date, $year_end );

			// Calculate days (inclusive).
			$interval   = $effective_start->diff( $effective_end );
			$days_used += $interval->days + 1;
		}

		return $days_used;
	}

	/**
	 * Calculate days in fiscal year.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule with reset date info.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days used.
	 */
	private function calculate_fiscal_year( $trips, $rule, $reference_date ) {
		// Same logic as calendar year with custom start.
		return $this->calculate_calendar_year( $trips, $rule, $reference_date );
	}

	/**
	 * Calculate next expiring days for rolling window.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule.
	 * @param DateTime $reference_date Reference date.
	 * @return array Array with 'date' and 'count'.
	 */
	private function calculate_next_expiring_days( $trips, $rule, $reference_date ) {
		$window_start = clone $reference_date;
		$window_start->modify( '-' . ( $rule['windowDays'] - 1 ) . ' days' );

		// Find the earliest trip day in the current window.
		$earliest_date = null;
		$expiring_days = 0;

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip['start_date'] );
			$trip_end   = new DateTime( $trip['end_date'] );

			// Only consider trips in the window.
			if ( $trip_end < $window_start || $trip_start > $reference_date ) {
				continue;
			}

			$effective_start = max( $trip_start, $window_start );

			if ( null === $earliest_date || $effective_start < $earliest_date ) {
				$earliest_date = $effective_start;
			}
		}

		if ( $earliest_date ) {
			// The expiring date is when this day falls out of the window.
			$expiring_date = clone $earliest_date;
			$expiring_date->modify( '+' . $rule['windowDays'] . ' days' );

			// Count how many days expire on that date.
			foreach ( $trips as $trip ) {
				$trip_start = new DateTime( $trip['start_date'] );
				$trip_end   = new DateTime( $trip['end_date'] );

				$effective_start = max( $trip_start, $window_start );

				if ( $effective_start->format( 'Y-m-d' ) === $earliest_date->format( 'Y-m-d' ) ) {
					$expiring_days++;
				}
			}

			return array(
				'date'  => $expiring_date->format( 'Y-m-d' ),
				'count' => $expiring_days,
			);
		}

		return array(
			'date'  => null,
			'count' => 0,
		);
	}

	/**
	 * Get trips for a specific jurisdiction.
	 *
	 * @param int    $user_id           User ID.
	 * @param string $jurisdiction_code Jurisdiction code.
	 * @return array Trips.
	 */
	private function get_trips_for_jurisdiction( $user_id, $jurisdiction_code ) {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'trips' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE user_id = %d
				AND (jurisdiction_code = %s OR jurisdiction_code IS NULL OR jurisdiction_code = '')
				ORDER BY start_date ASC",
				$user_id,
				$jurisdiction_code
			),
			ARRAY_A
		);

		// For backward compatibility, if jurisdiction_code is empty/null, assume 'schengen'.
		if ( 'schengen' === $jurisdiction_code ) {
			return $trips;
		}

		// Filter to only trips explicitly for this jurisdiction.
		return array_filter(
			$trips,
			function ( $trip ) use ( $jurisdiction_code ) {
				return $trip['jurisdiction_code'] === $jurisdiction_code;
			}
		);
	}
}
