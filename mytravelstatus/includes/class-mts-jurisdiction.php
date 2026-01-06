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
	 * Cached summaries (per-request).
	 *
	 * @var array
	 */
	private $summary_cache = array();

	/**
	 * Cache TTL in seconds (5 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 300;

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

		// UK SRT ties endpoints.
		register_rest_route(
			$namespace,
			'/jurisdictions/uk-srt/ties',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_uk_ties_api' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'tax_year' => array(
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_uk_ties_api' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// UK SRT result endpoint.
		register_rest_route(
			$namespace,
			'/jurisdictions/uk-srt/result',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_uk_srt_result_api' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'date' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
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
		// Generate cache key.
		$date_key  = $reference_date->format( 'Y-m-d' );
		$cache_key = "mts_summary_{$user_id}_{$rule['code']}_{$date_key}";

		// Check per-request cache first.
		if ( isset( $this->summary_cache[ $cache_key ] ) ) {
			return $this->summary_cache[ $cache_key ];
		}

		// Check transient cache.
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			$this->summary_cache[ $cache_key ] = $cached;
			return $cached;
		}

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
		} elseif ( 'uk_srt' === $rule['countingMethod'] ) {
			// UK tax year: April 6 to April 5.
			$window_start = $this->get_uk_tax_year_start( $reference_date );
			$window_end   = clone $window_start;
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
			$summary['weightedBreakdown'] = $this->get_weighted_breakdown( $trips, $rule, $reference_date );
		} elseif ( 'multi_year' === $rule['countingMethod'] ) {
			$summary['multiYearBreakdown'] = $this->get_multi_year_breakdown( $trips, $rule, $reference_date );
		} elseif ( 'uk_srt' === $rule['countingMethod'] ) {
			$summary['ukSrtBreakdown'] = $this->get_uk_srt_breakdown( $user_id, $trips, $rule, $reference_date );
			// For UK SRT, status is determined by the SRT result, not day count percentage.
			$srt_result = $summary['ukSrtBreakdown']['result'];
			$summary['status'] = ( 'resident' === $srt_result ) ? 'exceeded' : 'ok';
		}

		// Store in per-request cache.
		$this->summary_cache[ $cache_key ] = $summary;

		// Store in transient cache (5 minutes).
		set_transient( $cache_key, $summary, self::CACHE_TTL );

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
		$min_current_days    = isset( $config['min_current_year_days'] ) ? (int) $config['min_current_year_days'] : 31;
		$threshold           = isset( $rule['daysAllowed'] ) ? (int) $rule['daysAllowed'] : 183;

		$current_year = (int) $reference_date->format( 'Y' );
		$current_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		$prior_date = clone $reference_date;
		$prior_date->modify( '-1 year' );
		$prior_days = $this->calculate_calendar_year( $trips, $rule, $prior_date );

		$second_prior_date = clone $reference_date;
		$second_prior_date->modify( '-2 years' );
		$second_prior_days = $this->calculate_calendar_year( $trips, $rule, $second_prior_date );

		// Calculate weighted totals.
		$current_weighted      = round( $current_days * $current_weight, 1 );
		$prior_weighted        = round( $prior_days * $prior_weight, 1 );
		$second_prior_weighted = round( $second_prior_days * $second_prior_weight, 1 );
		$total_weighted        = $current_weighted + $prior_weighted + $second_prior_weighted;

		// Check if tests are met.
		$meets_current_year_minimum = $current_days >= $min_current_days;
		$meets_threshold            = $meets_current_year_minimum && $total_weighted >= $threshold;

		return array(
			'currentYear' => array(
				'year'     => $current_year,
				'days'     => $current_days,
				'weight'   => $current_weight,
				'weighted' => $current_weighted,
			),
			'priorYear' => array(
				'year'     => $current_year - 1,
				'days'     => $prior_days,
				'weight'   => $prior_weight,
				'weighted' => $prior_weighted,
			),
			'secondPriorYear' => array(
				'year'     => $current_year - 2,
				'days'     => $second_prior_days,
				'weight'   => $second_prior_weight,
				'weighted' => $second_prior_weighted,
			),
			'totalWeighted'            => round( $total_weighted, 1 ),
			'threshold'                => $threshold,
			'meetsThreshold'           => $meets_threshold,
			'meetsCurrentYearMinimum'  => $meets_current_year_minimum,
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
		$primary_threshold   = isset( $rule['daysAllowed'] ) ? (int) $rule['daysAllowed'] : 183;
		$secondary_threshold = isset( $config['secondary_threshold'] ) ? (int) $config['secondary_threshold'] : 280;
		$min_days_per_year   = isset( $config['min_days_per_year'] ) ? (int) $config['min_days_per_year'] : 31;

		$current_year = (int) $reference_date->format( 'Y' );
		$current_days = $this->calculate_calendar_year( $trips, $rule, $reference_date );

		$prior_date = clone $reference_date;
		$prior_date->modify( '-1 year' );
		$prior_days = $this->calculate_calendar_year( $trips, $rule, $prior_date );

		$combined_days    = $current_days + $prior_days;
		$meets_primary    = $current_days >= $primary_threshold;
		$meets_secondary  = $combined_days >= $secondary_threshold
			&& $current_days >= $min_days_per_year
			&& $prior_days >= $min_days_per_year;

		return array(
			'currentYear' => array(
				'year' => $current_year,
				'days' => $current_days,
			),
			'priorYear' => array(
				'year' => $current_year - 1,
				'days' => $prior_days,
			),
			'combinedDays'       => $combined_days,
			'primaryThreshold'   => $primary_threshold,
			'secondaryThreshold' => $secondary_threshold,
			'meetsPrimary'       => $meets_primary,
			'meetsSecondary'     => $meets_secondary,
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

			case 'uk_srt':
				return $this->calculate_uk_srt_days( $trips, $rule, $reference_date );

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
	 * Calculate days for UK Statutory Residence Test.
	 *
	 * The UK SRT uses midnight rule: a day counts if you're in UK at midnight.
	 * UK tax year runs April 6 - April 5.
	 *
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule with ruleConfig.
	 * @param DateTime $reference_date Reference date.
	 * @return int Days in UK (using midnight rule).
	 */
	private function calculate_uk_srt_days( $trips, $rule, $reference_date ) {
		// UK tax year: April 6 to April 5.
		$tax_year_start = $this->get_uk_tax_year_start( $reference_date );
		$tax_year_end   = clone $tax_year_start;
		$tax_year_end->modify( '+1 year -1 day' );

		$days_used = 0;

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip['start_date'] );
			$trip_end   = new DateTime( $trip['end_date'] );

			// Skip trips entirely outside the tax year.
			if ( $trip_end < $tax_year_start || $trip_start > $tax_year_end ) {
				continue;
			}

			// Clamp to tax year and reference date.
			$effective_start = max( $trip_start, $tax_year_start );
			$effective_end   = min( $trip_end, $reference_date, $tax_year_end );

			// UK midnight rule: count nights, not days.
			// Day of arrival doesn't count, day of departure does.
			// So a trip from Jan 1 to Jan 3 counts as 2 days (nights of Jan 1-2, Jan 2-3).
			$interval = $effective_start->diff( $effective_end );
			$days_used += $interval->days; // Not +1 because of midnight rule.
		}

		return $days_used;
	}

	/**
	 * Get UK tax year start date for a reference date.
	 *
	 * UK tax year runs April 6 to April 5.
	 *
	 * @param DateTime $reference_date Reference date.
	 * @return DateTime Start of tax year.
	 */
	private function get_uk_tax_year_start( $reference_date ) {
		$year = (int) $reference_date->format( 'Y' );
		$tax_year_start = new DateTime();
		$tax_year_start->setDate( $year, 4, 6 ); // April 6.
		$tax_year_start->setTime( 0, 0, 0 );

		// If reference date is before April 6, use previous year.
		if ( $reference_date < $tax_year_start ) {
			$tax_year_start->modify( '-1 year' );
		}

		return $tax_year_start;
	}

	/**
	 * Get UK tax year from a reference date.
	 *
	 * Returns the tax year in format "2024/25" for April 6 2024 - April 5 2025.
	 *
	 * @param DateTime $reference_date Reference date.
	 * @return int The starting year of the tax year.
	 */
	private function get_uk_tax_year( $reference_date ) {
		$tax_year_start = $this->get_uk_tax_year_start( $reference_date );
		return (int) $tax_year_start->format( 'Y' );
	}

	/**
	 * Calculate full UK SRT result for a user and tax year.
	 *
	 * This evaluates:
	 * 1. Automatic Overseas Tests (if any pass -> non-resident)
	 * 2. Automatic UK Tests (if any pass -> resident)
	 * 3. Sufficient Ties Test (day count vs. number of ties)
	 *
	 * @param int      $user_id        User ID.
	 * @param array    $trips          Trips for UK jurisdiction.
	 * @param array    $rule           Jurisdiction rule.
	 * @param DateTime $reference_date Reference date.
	 * @return array Full SRT result.
	 */
	public function calculate_uk_srt_result( $user_id, $trips, $rule, $reference_date ) {
		$tax_year = $this->get_uk_tax_year( $reference_date );
		$uk_days  = $this->calculate_uk_srt_days( $trips, $rule, $reference_date );

		// Get UK ties for this tax year.
		$ties = $this->get_uk_ties( $user_id, $tax_year );

		// Get prior year residency status.
		$was_resident_prior_year = $this->was_uk_resident_prior_years( $user_id, $reference_date );

		// Parse rule config.
		$config = isset( $rule['ruleConfig'] ) ? $rule['ruleConfig'] : array();

		// Test 1: Automatic Overseas Tests (if any pass -> definitely non-resident).
		$overseas_result = $this->test_automatic_overseas( $uk_days, $ties, $was_resident_prior_year, $config );
		if ( $overseas_result['passed'] ) {
			return array(
				'taxYear'           => $tax_year,
				'taxYearLabel'      => $tax_year . '/' . substr( $tax_year + 1, 2 ),
				'ukDays'            => $uk_days,
				'result'            => 'non_resident',
				'resultReason'      => 'automatic_overseas',
				'testPassed'        => $overseas_result['test'],
				'testDescription'   => $overseas_result['description'],
				'ties'              => $ties,
				'tieCount'          => $this->count_uk_ties( $ties ),
				'wasResidentPrior'  => $was_resident_prior_year,
				'automaticOverseas' => $overseas_result,
				'automaticUK'       => array( 'passed' => false, 'tests' => array() ),
				'sufficientTies'    => null,
			);
		}

		// Test 2: Automatic UK Tests (if any pass -> definitely resident).
		$uk_result = $this->test_automatic_uk( $uk_days, $ties, $config );
		if ( $uk_result['passed'] ) {
			return array(
				'taxYear'           => $tax_year,
				'taxYearLabel'      => $tax_year . '/' . substr( $tax_year + 1, 2 ),
				'ukDays'            => $uk_days,
				'result'            => 'resident',
				'resultReason'      => 'automatic_uk',
				'testPassed'        => $uk_result['test'],
				'testDescription'   => $uk_result['description'],
				'ties'              => $ties,
				'tieCount'          => $this->count_uk_ties( $ties ),
				'wasResidentPrior'  => $was_resident_prior_year,
				'automaticOverseas' => $overseas_result,
				'automaticUK'       => $uk_result,
				'sufficientTies'    => null,
			);
		}

		// Test 3: Sufficient Ties Test (compare days vs. ties).
		$ties_result = $this->test_sufficient_ties( $uk_days, $ties, $was_resident_prior_year, $config );

		return array(
			'taxYear'           => $tax_year,
			'taxYearLabel'      => $tax_year . '/' . substr( $tax_year + 1, 2 ),
			'ukDays'            => $uk_days,
			'result'            => $ties_result['resident'] ? 'resident' : 'non_resident',
			'resultReason'      => 'sufficient_ties',
			'testPassed'        => null,
			'testDescription'   => $ties_result['description'],
			'ties'              => $ties,
			'tieCount'          => $this->count_uk_ties( $ties ),
			'wasResidentPrior'  => $was_resident_prior_year,
			'automaticOverseas' => $overseas_result,
			'automaticUK'       => $uk_result,
			'sufficientTies'    => $ties_result,
		);
	}

	/**
	 * Test Automatic Overseas Tests.
	 *
	 * If ANY of these tests pass, the person is automatically non-resident.
	 *
	 * @param int   $uk_days               Days in UK.
	 * @param array $ties                  UK ties data.
	 * @param bool  $was_resident_prior    Was UK resident in any of prior 3 years.
	 * @param array $config                Rule configuration.
	 * @return array Test result.
	 */
	private function test_automatic_overseas( $uk_days, $ties, $was_resident_prior, $config ) {
		$tests = array();

		// Test 1: Resident in UK for 1+ of 3 prior years AND <16 days in UK.
		$test1_passed = $was_resident_prior && $uk_days < 16;
		$tests[] = array(
			'id'          => 'resident_3yr_under_16',
			'label'       => 'Resident prior + <16 days',
			'description' => 'Resident in UK for 1+ of 3 prior years AND <16 days in UK this year.',
			'passed'      => $test1_passed,
		);

		// Test 2: Not resident in UK for any of 3 prior years AND <46 days in UK.
		$test2_passed = ! $was_resident_prior && $uk_days < 46;
		$tests[] = array(
			'id'          => 'not_resident_3yr_under_46',
			'label'       => 'Not resident prior + <46 days',
			'description' => 'Not resident in UK for any of 3 prior years AND <46 days in UK this year.',
			'passed'      => $test2_passed,
		);

		// Test 3: Leaving UK permanently during year AND <16 days in UK after departure date.
		// This requires user input about leaving permanently.
		$leaving_uk = isset( $ties['leaving_uk_permanently'] ) && $ties['leaving_uk_permanently'];
		$test3_passed = $leaving_uk && $uk_days < 16;
		$tests[] = array(
			'id'          => 'leaving_uk_under_16',
			'label'       => 'Leaving UK + <16 days',
			'description' => 'Leaving UK permanently during year AND <16 days in UK after departure date.',
			'passed'      => $test3_passed,
			'requiresInput' => true,
		);

		// Check if any test passed.
		$passed = $test1_passed || $test2_passed || $test3_passed;
		$passed_test = null;
		$description = 'No automatic overseas test conditions met.';

		foreach ( $tests as $test ) {
			if ( $test['passed'] ) {
				$passed_test = $test['id'];
				$description = $test['description'];
				break;
			}
		}

		return array(
			'passed'      => $passed,
			'test'        => $passed_test,
			'description' => $description,
			'tests'       => $tests,
		);
	}

	/**
	 * Test Automatic UK Tests.
	 *
	 * If ANY of these tests pass, the person is automatically UK resident.
	 *
	 * @param int   $uk_days Days in UK.
	 * @param array $ties    UK ties data.
	 * @param array $config  Rule configuration.
	 * @return array Test result.
	 */
	private function test_automatic_uk( $uk_days, $ties, $config ) {
		$tests = array();

		// Test 1: 183+ days in UK (midnight rule applies).
		$test1_passed = $uk_days >= 183;
		$tests[] = array(
			'id'          => '183_days',
			'label'       => '183+ days in UK',
			'description' => 'Present in UK for 183+ days (midnight rule).',
			'passed'      => $test1_passed,
		);

		// Test 2: Only home is in UK AND present for 30+ days.
		$only_home = isset( $ties['only_home_in_uk'] ) && $ties['only_home_in_uk'];
		$test2_passed = $only_home && $uk_days >= 30;
		$tests[] = array(
			'id'          => 'only_home',
			'label'       => 'Only home in UK',
			'description' => 'Only home is in UK AND present for 30+ days.',
			'passed'      => $test2_passed,
			'requiresInput' => true,
		);

		// Test 3: Full-time work in UK (35+ hours/week for 365 days).
		$full_time_work = isset( $ties['full_time_work_uk'] ) && $ties['full_time_work_uk'];
		$tests[] = array(
			'id'          => 'full_time_work',
			'label'       => 'Full-time work in UK',
			'description' => 'Full-time work in UK (35+ hours/week for 365 days with no significant break).',
			'passed'      => $full_time_work,
			'requiresInput' => true,
		);

		// Check if any test passed.
		$passed = $test1_passed || $test2_passed || $full_time_work;
		$passed_test = null;
		$description = 'No automatic UK test conditions met.';

		foreach ( $tests as $test ) {
			if ( $test['passed'] ) {
				$passed_test = $test['id'];
				$description = $test['description'];
				break;
			}
		}

		return array(
			'passed'      => $passed,
			'test'        => $passed_test,
			'description' => $description,
			'tests'       => $tests,
		);
	}

	/**
	 * Test Sufficient Ties Test.
	 *
	 * Compare number of UK ties against day count thresholds.
	 * Thresholds differ based on whether person was UK resident in prior 3 years.
	 *
	 * @param int   $uk_days              Days in UK.
	 * @param array $ties                 UK ties data.
	 * @param bool  $was_resident_prior   Was UK resident in any of prior 3 years.
	 * @param array $config               Rule configuration.
	 * @return array Test result.
	 */
	private function test_sufficient_ties( $uk_days, $ties, $was_resident_prior, $config ) {
		$tie_count = $this->count_uk_ties( $ties );

		// Sufficient Ties Test thresholds.
		// If was_resident_prior: fewer days needed to be resident (stricter).
		// If not resident prior: more days needed to be resident (more lenient).
		$thresholds = isset( $config['tie_thresholds'] ) ? $config['tie_thresholds'] : array(
			'0' => array( 'not_resident_prior' => 183, 'resident_prior' => 183 ), // Handled by automatic test.
			'1' => array( 'not_resident_prior' => 121, 'resident_prior' => 91 ),
			'2' => array( 'not_resident_prior' => 91, 'resident_prior' => 61 ),
			'3' => array( 'not_resident_prior' => 46, 'resident_prior' => 46 ),
			'4' => array( 'not_resident_prior' => 16, 'resident_prior' => 16 ),
			'5' => array( 'not_resident_prior' => 16, 'resident_prior' => 16 ), // Country tie only for arrivers.
		);

		// Get the threshold for this tie count.
		$tie_key   = min( (string) $tie_count, '5' );
		$threshold = $thresholds[ $tie_key ];
		$day_threshold = $was_resident_prior ? $threshold['resident_prior'] : $threshold['not_resident_prior'];

		// Resident if days >= threshold.
		$is_resident = $uk_days >= $day_threshold;

		// Build tie breakdown.
		$tie_breakdown = array(
			'family'        => isset( $ties['family_tie'] ) && $ties['family_tie'],
			'accommodation' => isset( $ties['accommodation_tie'] ) && $ties['accommodation_tie'],
			'work'          => isset( $ties['work_tie'] ) && $ties['work_tie'],
			'ninety_day'    => isset( $ties['ninety_day_tie'] ) && $ties['ninety_day_tie'],
			'country'       => isset( $ties['country_tie'] ) && $ties['country_tie'],
		);

		$description = sprintf(
			'With %d UK %s and %d days in UK%s, you %s the %d-day threshold.',
			$tie_count,
			$tie_count === 1 ? 'tie' : 'ties',
			$uk_days,
			$was_resident_prior ? ' (was resident in prior years)' : ' (not resident in prior years)',
			$is_resident ? 'meet or exceed' : 'are below',
			$day_threshold
		);

		return array(
			'resident'      => $is_resident,
			'tieCount'      => $tie_count,
			'tieBreakdown'  => $tie_breakdown,
			'dayThreshold'  => $day_threshold,
			'daysInUK'      => $uk_days,
			'wasResidentPrior' => $was_resident_prior,
			'description'   => $description,
		);
	}

	/**
	 * Count number of UK ties that are active.
	 *
	 * @param array $ties UK ties data.
	 * @return int Number of ties.
	 */
	private function count_uk_ties( $ties ) {
		$count = 0;

		if ( isset( $ties['family_tie'] ) && $ties['family_tie'] ) {
			$count++;
		}
		if ( isset( $ties['accommodation_tie'] ) && $ties['accommodation_tie'] ) {
			$count++;
		}
		if ( isset( $ties['work_tie'] ) && $ties['work_tie'] ) {
			$count++;
		}
		if ( isset( $ties['ninety_day_tie'] ) && $ties['ninety_day_tie'] ) {
			$count++;
		}
		if ( isset( $ties['country_tie'] ) && $ties['country_tie'] ) {
			$count++;
		}

		return $count;
	}

	/**
	 * Get UK ties for a user and tax year.
	 *
	 * @param int $user_id  User ID.
	 * @param int $tax_year Tax year (starting year).
	 * @return array UK ties data.
	 */
	public function get_uk_ties( $user_id, $tax_year ) {
		global $wpdb;

		$table = MTS_Schema::get_table( 'uk_ties' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND tax_year = %d",
				$user_id,
				$tax_year
			),
			ARRAY_A
		);

		if ( ! $row ) {
			// Return default values.
			return array(
				'family_tie'              => false,
				'family_tie_details'      => '',
				'accommodation_tie'       => false,
				'accommodation_tie_details' => '',
				'work_tie'                => false,
				'work_tie_details'        => '',
				'ninety_day_tie'          => false,
				'country_tie'             => false,
				'only_home_in_uk'         => false,
				'full_time_work_uk'       => false,
				'leaving_uk_permanently'  => false,
				'notes'                   => '',
			);
		}

		return array(
			'id'                       => (int) $row['id'],
			'family_tie'               => (bool) $row['family_tie'],
			'family_tie_details'       => $row['family_tie_details'] ?? '',
			'accommodation_tie'        => (bool) $row['accommodation_tie'],
			'accommodation_tie_details' => $row['accommodation_tie_details'] ?? '',
			'work_tie'                 => (bool) $row['work_tie'],
			'work_tie_details'         => $row['work_tie_details'] ?? '',
			'ninety_day_tie'           => (bool) $row['ninety_day_tie'],
			'country_tie'              => (bool) $row['country_tie'],
			'only_home_in_uk'          => (bool) $row['only_home_in_uk'],
			'full_time_work_uk'        => (bool) $row['full_time_work_uk'],
			'leaving_uk_permanently'   => (bool) $row['leaving_uk_permanently'],
			'notes'                    => $row['notes'] ?? '',
		);
	}

	/**
	 * Update UK ties for a user and tax year.
	 *
	 * @param int   $user_id  User ID.
	 * @param int   $tax_year Tax year (starting year).
	 * @param array $ties     UK ties data.
	 * @return bool Success.
	 */
	public function update_uk_ties( $user_id, $tax_year, $ties ) {
		global $wpdb;

		$table = MTS_Schema::get_table( 'uk_ties' );

		// Check if record exists.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE user_id = %d AND tax_year = %d",
				$user_id,
				$tax_year
			)
		);

		$data = array(
			'family_tie'               => isset( $ties['family_tie'] ) ? (int) $ties['family_tie'] : 0,
			'family_tie_details'       => isset( $ties['family_tie_details'] ) ? sanitize_textarea_field( $ties['family_tie_details'] ) : null,
			'accommodation_tie'        => isset( $ties['accommodation_tie'] ) ? (int) $ties['accommodation_tie'] : 0,
			'accommodation_tie_details' => isset( $ties['accommodation_tie_details'] ) ? sanitize_textarea_field( $ties['accommodation_tie_details'] ) : null,
			'work_tie'                 => isset( $ties['work_tie'] ) ? (int) $ties['work_tie'] : 0,
			'work_tie_details'         => isset( $ties['work_tie_details'] ) ? sanitize_textarea_field( $ties['work_tie_details'] ) : null,
			'ninety_day_tie'           => isset( $ties['ninety_day_tie'] ) ? (int) $ties['ninety_day_tie'] : 0,
			'country_tie'              => isset( $ties['country_tie'] ) ? (int) $ties['country_tie'] : 0,
			'only_home_in_uk'          => isset( $ties['only_home_in_uk'] ) ? (int) $ties['only_home_in_uk'] : 0,
			'full_time_work_uk'        => isset( $ties['full_time_work_uk'] ) ? (int) $ties['full_time_work_uk'] : 0,
			'leaving_uk_permanently'   => isset( $ties['leaving_uk_permanently'] ) ? (int) $ties['leaving_uk_permanently'] : 0,
			'notes'                    => isset( $ties['notes'] ) ? sanitize_textarea_field( $ties['notes'] ) : null,
		);

		$format = array( '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s' );

		if ( $exists ) {
			return false !== $wpdb->update(
				$table,
				$data,
				array(
					'user_id'  => $user_id,
					'tax_year' => $tax_year,
				),
				$format,
				array( '%d', '%d' )
			);
		}

		$data['user_id']  = $user_id;
		$data['tax_year'] = $tax_year;
		$format[] = '%d';
		$format[] = '%d';

		return false !== $wpdb->insert( $table, $data, $format );
	}

	/**
	 * Check if user was UK resident in any of the prior 3 years.
	 *
	 * @param int      $user_id        User ID.
	 * @param DateTime $reference_date Reference date.
	 * @return bool Was UK resident in prior years.
	 */
	private function was_uk_resident_prior_years( $user_id, $reference_date ) {
		// Check stored SRT results for prior 3 years.
		global $wpdb;

		$table = MTS_Schema::get_table( 'uk_ties' );
		$current_tax_year = $this->get_uk_tax_year( $reference_date );

		// Check the prior 3 tax years.
		$prior_years = array(
			$current_tax_year - 1,
			$current_tax_year - 2,
			$current_tax_year - 3,
		);

		foreach ( $prior_years as $year ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT srt_result FROM $table WHERE user_id = %d AND tax_year = %d",
					$user_id,
					$year
				)
			);

			if ( 'resident' === $result ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get UK SRT breakdown for summary display.
	 *
	 * @param int      $user_id        User ID.
	 * @param array    $trips          Trips.
	 * @param array    $rule           Rule.
	 * @param DateTime $reference_date Reference date.
	 * @return array UK SRT breakdown.
	 */
	private function get_uk_srt_breakdown( $user_id, $trips, $rule, $reference_date ) {
		$result = $this->calculate_uk_srt_result( $user_id, $trips, $rule, $reference_date );

		return array(
			'taxYear'           => $result['taxYear'],
			'taxYearLabel'      => $result['taxYearLabel'],
			'ukDays'            => $result['ukDays'],
			'result'            => $result['result'],
			'resultReason'      => $result['resultReason'],
			'tieCount'          => $result['tieCount'],
			'wasResidentPrior'  => $result['wasResidentPrior'],
			'automaticOverseas' => $result['automaticOverseas'],
			'automaticUK'       => $result['automaticUK'],
			'sufficientTies'    => $result['sufficientTies'],
		);
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
	 * Get UK ties for API.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_uk_ties_api( $request ) {
		$user_id  = get_current_user_id();
		$tax_year = $request->get_param( 'tax_year' );

		// If no tax year provided, use current.
		if ( ! $tax_year ) {
			$tax_year = $this->get_uk_tax_year( new DateTime() );
		}

		$ties = $this->get_uk_ties( $user_id, (int) $tax_year );

		return rest_ensure_response(
			array(
				'success' => true,
				'taxYear' => (int) $tax_year,
				'taxYearLabel' => $tax_year . '/' . substr( $tax_year + 1, 2 ),
				'ties'    => $ties,
			)
		);
	}

	/**
	 * Update UK ties via API.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_uk_ties_api( $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		$tax_year = isset( $params['tax_year'] ) ? (int) $params['tax_year'] : $this->get_uk_tax_year( new DateTime() );
		$ties     = isset( $params['ties'] ) ? $params['ties'] : $params;

		// Validate tax year.
		$current_year = (int) gmdate( 'Y' );
		if ( $tax_year < 2013 || $tax_year > $current_year + 1 ) {
			return new WP_Error(
				'invalid_tax_year',
				__( 'Invalid tax year.', 'mytravelstatus' ),
				array( 'status' => 400 )
			);
		}

		$success = $this->update_uk_ties( $user_id, $tax_year, $ties );

		if ( ! $success ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update UK ties.', 'mytravelstatus' ),
				array( 'status' => 500 )
			);
		}

		// Re-fetch to return updated data.
		$updated_ties = $this->get_uk_ties( $user_id, $tax_year );

		return rest_ensure_response(
			array(
				'success' => true,
				'taxYear' => $tax_year,
				'taxYearLabel' => $tax_year . '/' . substr( $tax_year + 1, 2 ),
				'ties'    => $updated_ties,
			)
		);
	}

	/**
	 * Get UK SRT result via API.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_uk_srt_result_api( $request ) {
		$user_id = get_current_user_id();
		$date    = $request->get_param( 'date' );

		$reference_date = $date ? new DateTime( $date ) : new DateTime();

		// Get the UK SRT rule.
		$rule = $this->get_rule( 'uk_srt' );
		if ( ! $rule ) {
			return new WP_Error(
				'not_found',
				__( 'UK SRT jurisdiction not found.', 'mytravelstatus' ),
				array( 'status' => 404 )
			);
		}

		// Get trips for UK.
		$trips = $this->get_trips_for_jurisdiction( $user_id, 'uk_srt' );

		// Calculate full SRT result.
		$result = $this->calculate_uk_srt_result( $user_id, $trips, $rule, $reference_date );

		return rest_ensure_response(
			array(
				'success' => true,
				'result'  => $result,
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

	/**
	 * Invalidate summary cache for a user.
	 *
	 * Call this when trips are created, updated, or deleted.
	 *
	 * @param int         $user_id User ID.
	 * @param string|null $code    Optional jurisdiction code to invalidate, or null for all.
	 */
	public function invalidate_cache( $user_id, $code = null ) {
		global $wpdb;

		// Clear per-request cache.
		if ( $code ) {
			// Clear specific jurisdiction cache.
			foreach ( $this->summary_cache as $key => $value ) {
				if ( strpos( $key, "mts_summary_{$user_id}_{$code}_" ) === 0 ) {
					unset( $this->summary_cache[ $key ] );
				}
			}
		} else {
			// Clear all user cache.
			foreach ( $this->summary_cache as $key => $value ) {
				if ( strpos( $key, "mts_summary_{$user_id}_" ) === 0 ) {
					unset( $this->summary_cache[ $key ] );
				}
			}
		}

		// Clear transient cache.
		// Generate date keys for past 7 days (common reference dates).
		$dates = array();
		for ( $i = 0; $i <= 7; $i++ ) {
			$dates[] = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		}

		if ( $code ) {
			// Clear specific jurisdiction transients.
			foreach ( $dates as $date ) {
				delete_transient( "mts_summary_{$user_id}_{$code}_{$date}" );
			}
		} else {
			// Clear all jurisdictions for user.
			$tracked = get_user_meta( $user_id, 'mts_tracked_jurisdictions', true );
			if ( empty( $tracked ) ) {
				$tracked = array( 'schengen' );
			}

			foreach ( $tracked as $jurisdiction_code ) {
				foreach ( $dates as $date ) {
					delete_transient( "mts_summary_{$user_id}_{$jurisdiction_code}_{$date}" );
				}
			}
		}
	}

	/**
	 * Clear all caches (for admin use).
	 */
	public function clear_all_caches() {
		global $wpdb;

		// Clear per-request cache.
		$this->summary_cache = array();
		$this->rules_cache   = array();

		// Clear all MTS transients.
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_mts_summary_%'
			OR option_name LIKE '_transient_timeout_mts_summary_%'"
		);
	}
}
