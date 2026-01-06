<?php
/**
 * Jurisdiction Rules Engine for MyTravelStatus.
 *
 * Handles multi-jurisdiction tracking with different counting methods:
 * - Rolling window (e.g., Schengen 90/180)
 * - Calendar year (e.g., US state residency)
 * - Fiscal year (custom start date)
 *
 * @package MTS_Tracker
 * @since   1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jurisdiction management class.
 */
class MTS_Jurisdiction {

	/**
	 * Singleton instance.
	 *
	 * @var MTS_Jurisdiction
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
	 * @return MTS_Jurisdiction
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
		$namespace = 'mts/v1';

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

		// IMPORTANT: Register specific routes BEFORE parameterized routes
		// to prevent /tracked and /summary from matching as codes.

		// Get user's tracked jurisdictions (GET and POST).
		register_rest_route(
			$namespace,
			'/jurisdictions/tracked',
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
					'args'                => array(
						'code' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
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

		// Get multi-jurisdiction summary (must be before parameterized route).
		register_rest_route(
			$namespace,
			'/jurisdictions/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_multi_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get single jurisdiction rule (parameterized - must be AFTER specific routes).
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

		// Get summary for a specific jurisdiction.
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

		// User jurisdiction settings with multi-factor responses.
		register_rest_route(
			$namespace,
			'/jurisdictions/user/(?P<code>[a-z0-9_]+)/factors',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_user_factors' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'code' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_user_factors' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'code' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Bulk enable/disable jurisdictions.
		register_rest_route(
			$namespace,
			'/jurisdictions/bulk',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_update_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get EU tax jurisdictions for bulk operations.
		register_rest_route(
			$namespace,
			'/jurisdictions/eu-tax',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_eu_tax_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Legacy namespace support (fra-portal/v1/schengen/...).
		$legacy_namespace = 'fra-portal/v1';

		// Get all jurisdictions.
		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdictions' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// IMPORTANT: Register specific routes BEFORE parameterized routes.

		// Tracked jurisdictions (GET and POST).
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

		// Multi-jurisdiction summary.
		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_multi_jurisdiction_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Single jurisdiction (parameterized - AFTER specific routes).
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
			'/schengen/jurisdictions/tracked/(?P<code>[a-z0-9_]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_tracked_jurisdiction' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Single jurisdiction summary.
		register_rest_route(
			$legacy_namespace,
			'/schengen/jurisdictions/(?P<code>[a-z0-9_]+)/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_jurisdiction_summary' ),
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
				__( 'You must be logged in.', 'mytravelstatus' ),
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
				__( 'Jurisdiction not found.', 'mytravelstatus' ),
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
		$tracked  = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );

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
				__( 'Jurisdiction not found.', 'mytravelstatus' ),
				array( 'status' => 404 )
			);
		}

		$tracked = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );
		if ( empty( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		if ( ! in_array( $code, $tracked, true ) ) {
			$tracked[] = $code;
			update_user_meta( $user_id, 'mts_tracked_jurisdictions', $tracked );
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
				__( 'Cannot remove Schengen from tracking.', 'mytravelstatus' ),
				array( 'status' => 400 )
			);
		}

		$tracked = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );
		if ( empty( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		$tracked = array_values( array_diff( $tracked, array( $code ) ) );
		update_user_meta( $user_id, 'mts_tracked_jurisdictions', $tracked );

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
				__( 'Jurisdiction not found.', 'mytravelstatus' ),
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
		$tracked = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );

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

		$table = MTS_Schema::get_table( 'jurisdiction_rules' );

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

		$table = MTS_Schema::get_table( 'jurisdiction_rules' );

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
		$formatted = array(
			'id'             => (int) $rule['id'],
			'code'           => $rule['code'],
			'name'           => $rule['name'],
			'type'           => $rule['type'],
			'category'       => isset( $rule['category'] ) ? $rule['category'] : 'visa',
			'parentCode'     => $rule['parent_code'],
			'daysAllowed'    => (int) $rule['days_allowed'],
			'windowDays'     => (int) $rule['window_days'],
			'countingMethod' => $rule['counting_method'],
			'resetMonth'     => $rule['reset_month'] ? (int) $rule['reset_month'] : null,
			'resetDay'       => $rule['reset_day'] ? (int) $rule['reset_day'] : null,
			'description'    => $rule['description'],
			'notes'          => $rule['notes'],
			'countryCode'    => isset( $rule['country_code'] ) ? $rule['country_code'] : null,
			'flagEmoji'      => isset( $rule['flag_emoji'] ) ? $rule['flag_emoji'] : null,
			'isSystem'       => (bool) $rule['is_system'],
		);

		// Parse JSON rule_config if present.
		if ( ! empty( $rule['rule_config'] ) ) {
			$config = json_decode( $rule['rule_config'], true );
			if ( $config ) {
				$formatted['ruleConfig'] = $config;
			}
		}

		return $formatted;
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

		// Determine status based on percentage thresholds.
		$status = 'ok';
		if ( $percentage >= 100 ) {
			$status = 'exceeded';
		} elseif ( $percentage >= 95 ) {
			$status = 'critical';
		} elseif ( $percentage >= 80 ) {
			$status = 'warning';
		}

		// Calculate window dates.
		$window_start = clone $reference_date;
		$window_end   = clone $reference_date;

		if ( 'rolling' === $rule['countingMethod'] ) {
			$window_start->modify( '-' . ( $rule['windowDays'] - 1 ) . ' days' );
		} elseif ( in_array( $rule['countingMethod'], array( 'calendar_year', 'multi_year', 'weighted_multi_year' ), true ) ) {
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

		$summary = array(
			'jurisdictionCode' => $rule['code'],
			'jurisdictionName' => $rule['name'],
			'category'         => isset( $rule['category'] ) ? $rule['category'] : 'visa',
			'flagEmoji'        => isset( $rule['flagEmoji'] ) ? $rule['flagEmoji'] : null,
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

		// Add breakdown for multi-year calculations.
		if ( 'weighted_multi_year' === $rule['countingMethod'] ) {
			$summary['breakdown'] = $this->get_weighted_breakdown( $trips, $rule, $reference_date );
		} elseif ( 'multi_year' === $rule['countingMethod'] ) {
			$summary['breakdown'] = $this->get_multi_year_breakdown( $trips, $rule, $reference_date );
		}

		return $summary;
	}

	/**
	 * Get breakdown for US SPT weighted calculation.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule.
	 * @param DateTime $reference_date Reference date.
	 * @return array Breakdown by year.
	 */
	private function get_weighted_breakdown( $trips, $rule, $reference_date ) {
		$config = isset( $rule['ruleConfig'] ) ? $rule['ruleConfig'] : array();
		$current_weight      = isset( $config['current_year_weight'] ) ? (float) $config['current_year_weight'] : 1.0;
		$prior_weight        = isset( $config['prior_year_weight'] ) ? (float) $config['prior_year_weight'] : 0.333;
		$second_prior_weight = isset( $config['second_prior_weight'] ) ? (float) $config['second_prior_weight'] : 0.167;

		$current_year = (int) $reference_date->format( 'Y' );
		$current_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		$prior_date = clone $reference_date;
		$prior_date->modify( '-1 year' );
		$prior_days = $this->calculate_calendar_year( $trips, $rule, $prior_date );

		$second_prior_date = clone $reference_date;
		$second_prior_date->modify( '-2 years' );
		$second_prior_days = $this->calculate_calendar_year( $trips, $rule, $second_prior_date );

		return array(
			'currentYear' => array(
				'year'     => $current_year,
				'days'     => $current_days,
				'weight'   => $current_weight,
				'weighted' => round( $current_days * $current_weight, 1 ),
			),
			'priorYear' => array(
				'year'     => $current_year - 1,
				'days'     => $prior_days,
				'weight'   => $prior_weight,
				'weighted' => round( $prior_days * $prior_weight, 1 ),
			),
			'secondPriorYear' => array(
				'year'     => $current_year - 2,
				'days'     => $second_prior_days,
				'weight'   => $second_prior_weight,
				'weighted' => round( $second_prior_days * $second_prior_weight, 1 ),
			),
		);
	}

	/**
	 * Get breakdown for Ireland multi-year calculation.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule.
	 * @param DateTime $reference_date Reference date.
	 * @return array Breakdown by year.
	 */
	private function get_multi_year_breakdown( $trips, $rule, $reference_date ) {
		$config = isset( $rule['ruleConfig'] ) ? $rule['ruleConfig'] : array();
		$secondary_threshold = isset( $config['secondary_threshold'] ) ? (int) $config['secondary_threshold'] : 280;

		$current_year = (int) $reference_date->format( 'Y' );
		$current_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		$prior_date = clone $reference_date;
		$prior_date->modify( '-1 year' );
		$prior_days = $this->calculate_calendar_year( $trips, $rule, $prior_date );

		return array(
			'currentYear' => array(
				'year' => $current_year,
				'days' => $current_days,
			),
			'priorYear' => array(
				'year' => $current_year - 1,
				'days' => $prior_days,
			),
			'combined'          => $current_days + $prior_days,
			'secondaryThreshold' => $secondary_threshold,
		);
	}

	/**
	 * Calculate days used based on counting method.
	 *
	 * @param array    $trips          Array of trips.
	 * @param array    $rule           Jurisdiction rule.
	 * @param DateTime $reference_date Reference date.
	 * @return int|float Days used (float for weighted calculations).
	 */
	public function calculate_days_used( $trips, $rule, $reference_date ) {
		switch ( $rule['countingMethod'] ) {
			case 'rolling':
				return $this->calculate_rolling_window( $trips, $rule['windowDays'], $reference_date );

			case 'calendar_year':
				return $this->calculate_calendar_year( $trips, $rule, $reference_date );

			case 'fiscal_year':
				return $this->calculate_fiscal_year( $trips, $rule, $reference_date );

			case 'multi_year':
				return $this->calculate_multi_year( $trips, $rule, $reference_date );

			case 'weighted_multi_year':
				return $this->calculate_weighted_multi_year( $trips, $rule, $reference_date );

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
	 * Calculate days for multi-year rules (e.g., Ireland 183/280).
	 *
	 * Ireland: Resident if 183+ days in current year OR 280+ days over current + previous year.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule with ruleConfig.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days used (returns the higher of primary or secondary test).
	 */
	private function calculate_multi_year( $trips, $rule, $reference_date ) {
		// Get config for secondary test.
		$config = isset( $rule['ruleConfig'] ) ? $rule['ruleConfig'] : array();
		$secondary_threshold = isset( $config['secondary_threshold'] ) ? (int) $config['secondary_threshold'] : 280;
		$secondary_years     = isset( $config['secondary_years'] ) ? (int) $config['secondary_years'] : 2;
		$min_days_per_year   = isset( $config['min_days_per_year'] ) ? (int) $config['min_days_per_year'] : 31;

		// Calculate current year days.
		$current_year_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		// If current year exceeds threshold, return that.
		if ( $current_year_days >= $rule['daysAllowed'] ) {
			return $current_year_days;
		}

		// Calculate previous year days.
		$previous_date = clone $reference_date;
		$previous_date->modify( '-1 year' );
		$previous_year_days = $this->calculate_calendar_year( $trips, $rule, $previous_date );

		// Check if secondary test applies (Ireland: 280 days over 2 years, min 31 each).
		$combined_days = $current_year_days + $previous_year_days;

		if ( $combined_days >= $secondary_threshold &&
			 $current_year_days >= $min_days_per_year &&
			 $previous_year_days >= $min_days_per_year ) {
			// Return the combined days capped at threshold for display purposes.
			// This indicates the secondary test is triggered.
			return min( $combined_days, $secondary_threshold );
		}

		// Return current year days if no test triggered.
		return $current_year_days;
	}

	/**
	 * Calculate weighted multi-year days (US Substantial Presence Test).
	 *
	 * SPT: Current year × 1 + Prior year × 1/3 + Second prior × 1/6 >= 183.
	 * Also requires 31+ days in current year.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule with ruleConfig.
	 * @param DateTime $reference_date Reference date.
	 * @return float Weighted days used.
	 */
	private function calculate_weighted_multi_year( $trips, $rule, $reference_date ) {
		// Get weights from config.
		$config = isset( $rule['ruleConfig'] ) ? $rule['ruleConfig'] : array();
		$current_weight      = isset( $config['current_year_weight'] ) ? (float) $config['current_year_weight'] : 1.0;
		$prior_weight        = isset( $config['prior_year_weight'] ) ? (float) $config['prior_year_weight'] : 0.333;
		$second_prior_weight = isset( $config['second_prior_weight'] ) ? (float) $config['second_prior_weight'] : 0.167;
		$min_current_days    = isset( $config['min_current_year_days'] ) ? (int) $config['min_current_year_days'] : 31;

		// Calculate current year days.
		$current_year_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		// If current year doesn't meet minimum, return 0 (test doesn't apply).
		if ( $current_year_days < $min_current_days ) {
			return 0;
		}

		// Calculate prior year days.
		$prior_date = clone $reference_date;
		$prior_date->modify( '-1 year' );
		$prior_year_days = $this->calculate_calendar_year( $trips, $rule, $prior_date );

		// Calculate second prior year days.
		$second_prior_date = clone $reference_date;
		$second_prior_date->modify( '-2 years' );
		$second_prior_year_days = $this->calculate_calendar_year( $trips, $rule, $second_prior_date );

		// Calculate weighted total.
		$weighted_total = ( $current_year_days * $current_weight ) +
						  ( $prior_year_days * $prior_weight ) +
						  ( $second_prior_year_days * $second_prior_weight );

		return round( $weighted_total, 1 );
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

		$table = MTS_Schema::get_table( 'trips' );

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

	/**
	 * Get user's multi-factor responses for a jurisdiction.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_user_factors( $request ) {
		$user_id = get_current_user_id();
		$code    = $request->get_param( 'code' );

		$rule = $this->get_rule( $code );
		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'Jurisdiction not found.', 'mytravelstatus' ),
				array( 'status' => 404 )
			);
		}

		// Check if rule has multi-factor config.
		if ( empty( $rule['ruleConfig']['multi_factor'] ) ) {
			return rest_ensure_response(
				array(
					'code'       => $code,
					'hasFactors' => false,
					'factors'    => array(),
					'responses'  => array(),
				)
			);
		}

		$factors   = $rule['ruleConfig']['factors'] ?? array();
		$responses = get_user_meta( $user_id, 'mts_factors_' . $code, true );

		if ( ! is_array( $responses ) ) {
			$responses = array();
		}

		return rest_ensure_response(
			array(
				'code'        => $code,
				'hasFactors'  => true,
				'factors'     => $factors,
				'responses'   => $responses,
				'factorLogic' => $rule['ruleConfig']['factor_logic'] ?? 'any',
			)
		);
	}

	/**
	 * Update user's multi-factor responses for a jurisdiction.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_user_factors( $request ) {
		$user_id   = get_current_user_id();
		$code      = $request->get_param( 'code' );
		$responses = $request->get_json_params();

		$rule = $this->get_rule( $code );
		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'Jurisdiction not found.', 'mytravelstatus' ),
				array( 'status' => 404 )
			);
		}

		// Validate responses against defined factors.
		if ( empty( $rule['ruleConfig']['multi_factor'] ) ) {
			return new WP_Error(
				'no_factors',
				__( 'This jurisdiction does not have multi-factor configuration.', 'mytravelstatus' ),
				array( 'status' => 400 )
			);
		}

		$allowed_factor_ids = array_column( $rule['ruleConfig']['factors'] ?? array(), 'id' );
		$sanitized          = array();

		foreach ( $responses as $factor_id => $value ) {
			if ( in_array( $factor_id, $allowed_factor_ids, true ) ) {
				$sanitized[ sanitize_key( $factor_id ) ] = (bool) $value;
			}
		}

		update_user_meta( $user_id, 'mts_factors_' . $code, $sanitized );

		return rest_ensure_response(
			array(
				'success'   => true,
				'code'      => $code,
				'responses' => $sanitized,
			)
		);
	}

	/**
	 * Bulk enable/disable jurisdictions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_update_jurisdictions( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();
		$action  = isset( $params['action'] ) ? sanitize_text_field( $params['action'] ) : '';
		$codes   = isset( $params['codes'] ) ? array_map( 'sanitize_text_field', $params['codes'] ) : array();

		if ( empty( $action ) || ! in_array( $action, array( 'enable', 'disable' ), true ) ) {
			return new WP_Error(
				'invalid_action',
				__( 'Action must be "enable" or "disable".', 'mytravelstatus' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $codes ) ) {
			return new WP_Error(
				'no_codes',
				__( 'No jurisdiction codes provided.', 'mytravelstatus' ),
				array( 'status' => 400 )
			);
		}

		$tracked = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );
		if ( ! is_array( $tracked ) ) {
			$tracked = array( 'schengen' );
		}

		if ( 'enable' === $action ) {
			// Verify each code exists.
			foreach ( $codes as $code ) {
				$rule = $this->get_rule( $code );
				if ( $rule && ! in_array( $code, $tracked, true ) ) {
					$tracked[] = $code;
				}
			}
		} else {
			// Disable - remove from tracked (except schengen).
			$tracked = array_filter(
				$tracked,
				function ( $code ) use ( $codes ) {
					return 'schengen' === $code || ! in_array( $code, $codes, true );
				}
			);
			$tracked = array_values( $tracked );
		}

		update_user_meta( $user_id, 'mts_tracked_jurisdictions', $tracked );

		return rest_ensure_response(
			array(
				'success' => true,
				'action'  => $action,
				'count'   => count( $codes ),
				'tracked' => $tracked,
			)
		);
	}

	/**
	 * Get EU tax jurisdictions for bulk enable.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_eu_tax_jurisdictions( $request ) {
		$all_rules = $this->get_all_rules();

		// EU country codes (current EU members).
		$eu_codes = array(
			'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
			'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
			'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		);

		$eu_tax_jurisdictions = array();

		foreach ( $all_rules as $rule ) {
			// Match rules that are tax category and have EU country code.
			if ( 'tax' === $rule['category'] && ! empty( $rule['countryCode'] ) ) {
				if ( in_array( $rule['countryCode'], $eu_codes, true ) ) {
					$eu_tax_jurisdictions[] = $rule;
				}
			}
		}

		// Sort by name.
		usort(
			$eu_tax_jurisdictions,
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		return rest_ensure_response(
			array(
				'jurisdictions' => $eu_tax_jurisdictions,
				'codes'         => array_column( $eu_tax_jurisdictions, 'code' ),
			)
		);
	}
}
