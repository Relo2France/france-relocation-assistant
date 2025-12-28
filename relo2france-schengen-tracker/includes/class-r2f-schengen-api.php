<?php
/**
 * Schengen Tracker REST API
 *
 * Provides REST API endpoints for the Schengen 90/180 day tracker.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_API
 *
 * REST API endpoints for Schengen trip tracking.
 */
class R2F_Schengen_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'r2f-schengen/v1';

	/**
	 * Legacy namespace for backward compatibility.
	 *
	 * @var string
	 */
	const LEGACY_NAMESPACE = 'fra-portal/v1';

	/**
	 * Valid Schengen countries.
	 *
	 * @var array
	 */
	const SCHENGEN_COUNTRIES = array(
		'Austria',
		'Belgium',
		'Bulgaria',
		'Croatia',
		'Czech Republic',
		'Denmark',
		'Estonia',
		'Finland',
		'France',
		'Germany',
		'Greece',
		'Hungary',
		'Iceland',
		'Italy',
		'Latvia',
		'Liechtenstein',
		'Lithuania',
		'Luxembourg',
		'Malta',
		'Netherlands',
		'Norway',
		'Poland',
		'Portugal',
		'Romania',
		'Slovakia',
		'Slovenia',
		'Spain',
		'Sweden',
		'Switzerland',
	);

	/**
	 * Free tier trip limit.
	 *
	 * @var int
	 */
	const FREE_TRIP_LIMIT = 3;

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_API|null
	 */
	private static $instance = null;

	/**
	 * Premium handler.
	 *
	 * @var R2F_Schengen_Premium
	 */
	private $premium;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_API
	 */
	public static function get_instance(): R2F_Schengen_API {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->premium = R2F_Schengen_Premium::get_instance();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		// Register primary routes.
		$this->register_route_group( self::NAMESPACE );

		// Register legacy routes for backward compatibility with Member Tools.
		if ( ! r2f_schengen_has_member_tools() ) {
			$this->register_route_group( self::LEGACY_NAMESPACE, '/schengen' );
		}
	}

	/**
	 * Register route group under a namespace.
	 *
	 * @param string $namespace API namespace.
	 * @param string $prefix    Route prefix (empty for primary, '/schengen' for legacy).
	 */
	private function register_route_group( string $namespace, string $prefix = '' ): void {
		// Get all trips / Create trip.
		register_rest_route(
			$namespace,
			$prefix . '/trips',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_trips' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_trip' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_trip_args(),
				),
			)
		);

		// Get/Update/Delete single trip.
		register_rest_route(
			$namespace,
			$prefix . '/trips/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_trip' ),
					'permission_callback' => array( $this, 'check_trip_permission' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_trip' ),
					'permission_callback' => array( $this, 'check_trip_permission' ),
					'args'                => $this->get_trip_args(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_trip' ),
					'permission_callback' => array( $this, 'check_trip_permission' ),
				),
			)
		);

		// Get summary/stats.
		register_rest_route(
			$namespace,
			$prefix . '/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get/Update settings.
		register_rest_route(
			$namespace,
			$prefix . '/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Get feature status (premium gating).
		register_rest_route(
			$namespace,
			$prefix . '/feature-status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_feature_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Generate PDF report (premium feature).
		register_rest_route(
			$namespace,
			$prefix . '/report',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'generate_report' ),
				'permission_callback' => array( $this, 'check_premium_permission' ),
			)
		);

		// Planning tool - simulate future trip (premium feature).
		register_rest_route(
			$namespace,
			$prefix . '/simulate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'simulate_trip' ),
				'permission_callback' => array( $this, 'check_premium_permission' ),
			)
		);

		// Test email alert.
		register_rest_route(
			$namespace,
			$prefix . '/test-alert',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'test_alert' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// CSV import (premium feature).
		register_rest_route(
			$namespace,
			$prefix . '/trips/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_trips_csv' ),
				'permission_callback' => array( $this, 'check_premium_permission' ),
			)
		);

		// CSV export.
		register_rest_route(
			$namespace,
			$prefix . '/trips/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_trips_csv' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get argument schema for trip endpoints.
	 *
	 * @return array
	 */
	private function get_trip_args(): array {
		return array(
			'start_date' => array(
				'type'              => 'string',
				'format'            => 'date',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_date' => array(
				'type'              => 'string',
				'format'            => 'date',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'country' => array(
				'type'              => 'string',
				'required'          => true,
				'enum'              => self::SCHENGEN_COUNTRIES,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'category' => array(
				'type'              => 'string',
				'enum'              => array( 'personal', 'business' ),
				'default'           => 'personal',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'notes' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	// ========================================
	// Permission Callbacks
	// ========================================

	/**
	 * Check if user is logged in.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You must be logged in to access this resource.', 'r2f-schengen' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Check if user owns the trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_trip_permission( WP_REST_Request $request ) {
		$permission = $this->check_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$trip_id = (int) $request->get_param( 'id' );
		$trip    = $this->get_trip_by_id( $trip_id );

		if ( ! $trip ) {
			return new WP_Error(
				'not_found',
				__( 'Trip not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $trip->user_id !== get_current_user_id() ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to access this trip.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if user has premium access.
	 *
	 * @return bool|WP_Error
	 */
	public function check_premium_permission() {
		$permission = $this->check_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$user_id = get_current_user_id();
		if ( ! $this->premium->is_feature_enabled( $user_id ) ) {
			return new WP_Error(
				'premium_required',
				__( 'This feature requires a premium subscription.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	// ========================================
	// Trip CRUD Operations
	// ========================================

	/**
	 * Get trip by ID.
	 *
	 * @param int $trip_id Trip ID.
	 * @return object|null
	 */
	private function get_trip_by_id( int $trip_id ) {
		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'trips' );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $trip_id )
		);
	}

	/**
	 * Get all trips for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_trips( WP_REST_Request $request ) {
		global $wpdb;
		$table   = R2F_Schengen_Schema::get_table( 'trips' );
		$user_id = get_current_user_id();

		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
				$user_id
			)
		);

		return rest_ensure_response( array_map( array( $this, 'format_trip' ), $trips ) );
	}

	/**
	 * Get single trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_trip( WP_REST_Request $request ) {
		$trip_id = (int) $request->get_param( 'id' );
		$trip    = $this->get_trip_by_id( $trip_id );

		return rest_ensure_response( $this->format_trip( $trip ) );
	}

	/**
	 * Create a new trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_trip( WP_REST_Request $request ) {
		global $wpdb;

		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		// Check free tier limit.
		if ( ! $this->premium->is_feature_enabled( $user_id ) ) {
			$table      = R2F_Schengen_Schema::get_table( 'trips' );
			$trip_count = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id )
			);

			if ( $trip_count >= self::FREE_TRIP_LIMIT ) {
				return new WP_Error(
					'trip_limit_reached',
					sprintf(
						__( 'Free tier is limited to %d trips. Upgrade to Premium for unlimited trips.', 'r2f-schengen' ),
						self::FREE_TRIP_LIMIT
					),
					array( 'status' => 403 )
				);
			}
		}

		// Validate dates.
		$validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$table  = R2F_Schengen_Schema::get_table( 'trips' );
		$result = $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'start_date' => sanitize_text_field( $params['start_date'] ),
				'end_date'   => sanitize_text_field( $params['end_date'] ),
				'country'    => sanitize_text_field( $params['country'] ),
				'category'   => isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : 'personal',
				'notes'      => isset( $params['notes'] ) ? sanitize_textarea_field( $params['notes'] ) : null,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to create trip.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$trip = $this->get_trip_by_id( $wpdb->insert_id );

		/**
		 * Fires after a trip is created.
		 *
		 * @param int   $user_id User ID.
		 * @param array $trip_data Trip data.
		 */
		do_action( 'r2f_schengen_trip_created', $user_id, $this->format_trip( $trip ) );

		return rest_ensure_response( $this->format_trip( $trip ) );
	}

	/**
	 * Update an existing trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_trip( WP_REST_Request $request ) {
		global $wpdb;

		$trip_id = (int) $request->get_param( 'id' );
		$params  = $request->get_json_params();

		// Validate dates if provided.
		if ( isset( $params['start_date'] ) && isset( $params['end_date'] ) ) {
			$validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		$table  = R2F_Schengen_Schema::get_table( 'trips' );
		$data   = array();
		$format = array();

		if ( isset( $params['start_date'] ) ) {
			$data['start_date'] = sanitize_text_field( $params['start_date'] );
			$format[]           = '%s';
		}
		if ( isset( $params['end_date'] ) ) {
			$data['end_date'] = sanitize_text_field( $params['end_date'] );
			$format[]         = '%s';
		}
		if ( isset( $params['country'] ) ) {
			$data['country'] = sanitize_text_field( $params['country'] );
			$format[]        = '%s';
		}
		if ( isset( $params['category'] ) ) {
			$data['category'] = sanitize_text_field( $params['category'] );
			$format[]         = '%s';
		}
		if ( array_key_exists( 'notes', $params ) ) {
			$data['notes'] = $params['notes'] ? sanitize_textarea_field( $params['notes'] ) : null;
			$format[]      = '%s';
		}

		if ( empty( $data ) ) {
			return new WP_Error(
				'no_data',
				__( 'No data provided for update.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $trip_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to update trip.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$trip = $this->get_trip_by_id( $trip_id );

		/**
		 * Fires after a trip is updated.
		 *
		 * @param int   $user_id User ID.
		 * @param array $trip_data Updated trip data.
		 */
		do_action( 'r2f_schengen_trip_updated', get_current_user_id(), $this->format_trip( $trip ) );

		return rest_ensure_response( $this->format_trip( $trip ) );
	}

	/**
	 * Delete a trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_trip( WP_REST_Request $request ) {
		global $wpdb;

		$trip_id = (int) $request->get_param( 'id' );
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$result = $wpdb->delete(
			$table,
			array( 'id' => $trip_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to delete trip.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after a trip is deleted.
		 *
		 * @param int $user_id User ID.
		 * @param int $trip_id Deleted trip ID.
		 */
		do_action( 'r2f_schengen_trip_deleted', get_current_user_id(), $trip_id );

		return rest_ensure_response( array( 'deleted' => true, 'id' => $trip_id ) );
	}

	// ========================================
	// Summary & Settings
	// ========================================

	/**
	 * Get Schengen summary for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_summary( WP_REST_Request $request ) {
		global $wpdb;

		$table   = R2F_Schengen_Schema::get_table( 'trips' );
		$user_id = get_current_user_id();

		$settings = $this->get_user_settings( $user_id );

		// Calculate the 180-day window.
		$today        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = ( clone $today )->modify( '-179 days' );

		// Get all trips that overlap with the window.
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE user_id = %d
				AND end_date >= %s
				AND start_date <= %s
				ORDER BY start_date ASC",
				$user_id,
				$window_start->format( 'Y-m-d' ),
				$today->format( 'Y-m-d' )
			)
		);

		$days_used       = $this->calculate_days_in_window( $trips, $window_start, $today );
		$next_expiration = $this->find_next_expiration( $trips, $window_start, $today );
		$status          = $this->get_status( $days_used, $settings['yellow_threshold'], $settings['red_threshold'] );

		return rest_ensure_response( array(
			'daysUsed'         => $days_used,
			'daysRemaining'    => max( 0, 90 - $days_used ),
			'windowStart'      => $window_start->format( 'Y-m-d' ),
			'windowEnd'        => $today->format( 'Y-m-d' ),
			'status'           => $status,
			'nextExpiration'   => $next_expiration,
			'statusThresholds' => array(
				'yellow' => $settings['yellow_threshold'],
				'red'    => $settings['red_threshold'],
			),
		) );
	}

	/**
	 * Get user settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ) {
		$user_id  = get_current_user_id();
		$settings = $this->get_user_settings( $user_id );

		return rest_ensure_response( $this->format_settings_response( $settings ) );
	}

	/**
	 * Update user settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		$settings = $this->get_user_settings( $user_id );

		$yellow = isset( $params['yellowThreshold'] )
			? max( 1, min( 89, (int) $params['yellowThreshold'] ) )
			: $settings['yellow_threshold'];

		$red = isset( $params['redThreshold'] )
			? max( 1, min( 90, (int) $params['redThreshold'] ) )
			: $settings['red_threshold'];

		if ( $yellow >= $red ) {
			return new WP_Error(
				'invalid_thresholds',
				__( 'Yellow threshold must be less than red threshold.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$settings['yellow_threshold'] = $yellow;
		$settings['red_threshold']    = $red;

		if ( isset( $params['emailAlerts'] ) ) {
			$settings['email_alerts'] = (bool) $params['emailAlerts'];
		}
		if ( isset( $params['upcomingTripReminders'] ) ) {
			$settings['upcoming_trip_reminders'] = (bool) $params['upcomingTripReminders'];
		}

		update_user_meta( $user_id, 'fra_schengen_settings', $settings );

		return rest_ensure_response( $this->format_settings_response( $settings ) );
	}

	/**
	 * Get feature status for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_feature_status( WP_REST_Request $request ) {
		global $wpdb;

		$user_id    = get_current_user_id();
		$is_premium = $this->premium->is_feature_enabled( $user_id );

		$table      = R2F_Schengen_Schema::get_table( 'trips' );
		$trip_count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id )
		);

		return rest_ensure_response( array(
			'isPremium'      => $is_premium,
			'tripLimit'      => $is_premium ? null : self::FREE_TRIP_LIMIT,
			'tripCount'      => $trip_count,
			'canAddTrip'     => $is_premium || $trip_count < self::FREE_TRIP_LIMIT,
			'canUsePlanning' => $is_premium,
			'canExportPdf'   => $is_premium,
			'upgradeUrl'     => $is_premium ? null : $this->premium->get_upgrade_url(),
			'upgradeMessage' => $is_premium ? null : __( 'Upgrade to Premium for unlimited trips, planning tools, and PDF reports.', 'r2f-schengen' ),
		) );
	}

	// ========================================
	// Planning Tool (Premium)
	// ========================================

	/**
	 * Simulate a future trip.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function simulate_trip( WP_REST_Request $request ) {
		global $wpdb;

		$params = $request->get_json_params();

		if ( empty( $params['start_date'] ) || empty( $params['end_date'] ) ) {
			return new WP_Error(
				'missing_dates',
				__( 'Start date and end date are required.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$existing_trips = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id )
		);

		$proposed_start = new DateTime( $params['start_date'] );
		$proposed_end   = new DateTime( $params['end_date'] );
		$trip_length    = $proposed_start->diff( $proposed_end )->days + 1;

		$violations    = array();
		$max_days_used = 0;
		$check_date    = clone $proposed_start;

		while ( $check_date <= $proposed_end ) {
			$days_on_date = $this->calculate_days_with_proposed(
				$existing_trips,
				$proposed_start,
				$check_date,
				$check_date
			);

			if ( $days_on_date > 90 ) {
				$violations[] = $check_date->format( 'Y-m-d' );
			}

			$max_days_used = max( $max_days_used, $days_on_date );
			$check_date->modify( '+1 day' );
		}

		$would_violate      = count( $violations ) > 0;
		$earliest_safe_date = $would_violate ? $this->find_earliest_safe_date( $existing_trips, $trip_length ) : null;
		$max_safe_length    = $this->find_max_safe_length( $existing_trips, $proposed_start );

		return rest_ensure_response( array(
			'wouldViolate'     => $would_violate,
			'violations'       => $violations,
			'maxDaysUsed'      => $max_days_used,
			'proposedLength'   => $trip_length,
			'earliestSafeDate' => $earliest_safe_date,
			'maxSafeLength'    => $max_safe_length,
			'daysOverLimit'    => $would_violate ? $max_days_used - 90 : 0,
		) );
	}

	// ========================================
	// Report Generation (Premium)
	// ========================================

	/**
	 * Generate PDF report.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function generate_report( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
				$user_id
			)
		);

		$settings     = $this->get_user_settings( $user_id );
		$today        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = ( clone $today )->modify( '-179 days' );

		$window_trips = array_filter( $trips, function( $trip ) use ( $window_start ) {
			$trip_end = new DateTime( $trip->end_date );
			return $trip_end >= $window_start;
		} );

		$days_used = $this->calculate_days_in_window( $window_trips, $window_start, $today );
		$status    = $this->get_status( $days_used, $settings['yellow_threshold'], $settings['red_threshold'] );

		$html = $this->build_report_html( array(
			'user'           => $user,
			'trips'          => $trips,
			'days_used'      => $days_used,
			'days_remaining' => max( 0, 90 - $days_used ),
			'status'         => $status,
			'window_start'   => $window_start->format( 'Y-m-d' ),
			'window_end'     => $today->format( 'Y-m-d' ),
			'generated_at'   => $today->format( 'Y-m-d H:i:s' ),
		) );

		return rest_ensure_response( array(
			'html'     => $html,
			'filename' => 'schengen-report-' . $today->format( 'Y-m-d' ) . '.pdf',
			'summary'  => array(
				'daysUsed'      => $days_used,
				'daysRemaining' => max( 0, 90 - $days_used ),
				'status'        => $status,
				'tripCount'     => count( $trips ),
			),
		) );
	}

	// ========================================
	// Test Alert
	// ========================================

	/**
	 * Test email alert for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function test_alert( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		if ( ! class_exists( 'R2F_Schengen_Alerts' ) ) {
			return new WP_Error(
				'alerts_unavailable',
				__( 'Schengen alerts system is not available.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$alerts = R2F_Schengen_Alerts::get_instance();
		$result = $alerts->test_alert( $user_id );

		return rest_ensure_response( $result );
	}

	// ========================================
	// CSV Import/Export
	// ========================================

	/**
	 * Import trips from CSV data.
	 *
	 * Expected CSV format:
	 * start_date,end_date,country,category,notes
	 * 2024-01-15,2024-01-20,France,personal,Business trip
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_trips_csv( WP_REST_Request $request ) {
		global $wpdb;

		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		if ( empty( $params['csv'] ) ) {
			return new WP_Error(
				'missing_csv',
				__( 'CSV data is required.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$csv_data = sanitize_textarea_field( $params['csv'] );
		$lines    = explode( "\n", $csv_data );

		if ( count( $lines ) < 2 ) {
			return new WP_Error(
				'invalid_csv',
				__( 'CSV must contain a header row and at least one data row.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		// Parse header to determine column mapping.
		$header_line = str_getcsv( array_shift( $lines ) );
		$header      = array_map( 'trim', array_map( 'strtolower', $header_line ) );

		// Required columns.
		$required = array( 'start_date', 'end_date', 'country' );
		foreach ( $required as $col ) {
			if ( ! in_array( $col, $header, true ) ) {
				return new WP_Error(
					'missing_column',
					sprintf( __( 'Required column "%s" not found in CSV header.', 'r2f-schengen' ), $col ),
					array( 'status' => 400 )
				);
			}
		}

		// Column indices.
		$idx_start    = array_search( 'start_date', $header, true );
		$idx_end      = array_search( 'end_date', $header, true );
		$idx_country  = array_search( 'country', $header, true );
		$idx_category = array_search( 'category', $header, true );
		$idx_notes    = array_search( 'notes', $header, true );

		$table       = R2F_Schengen_Schema::get_table( 'trips' );
		$imported    = 0;
		$skipped     = 0;
		$errors      = array();
		$skip_duplicates = isset( $params['skip_duplicates'] ) && $params['skip_duplicates'];

		foreach ( $lines as $line_num => $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			$row        = str_getcsv( $line );
			$row_number = $line_num + 2; // 1-indexed + header row.

			// Extract values.
			$start_date = isset( $row[ $idx_start ] ) ? trim( $row[ $idx_start ] ) : '';
			$end_date   = isset( $row[ $idx_end ] ) ? trim( $row[ $idx_end ] ) : '';
			$country    = isset( $row[ $idx_country ] ) ? trim( $row[ $idx_country ] ) : '';
			$category   = ( false !== $idx_category && isset( $row[ $idx_category ] ) )
				? strtolower( trim( $row[ $idx_category ] ) )
				: 'personal';
			$notes      = ( false !== $idx_notes && isset( $row[ $idx_notes ] ) )
				? trim( $row[ $idx_notes ] )
				: '';

			// Validate start_date.
			if ( empty( $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
				$errors[] = sprintf( __( 'Row %d: Invalid start_date format (expected YYYY-MM-DD).', 'r2f-schengen' ), $row_number );
				$skipped++;
				continue;
			}

			// Validate end_date.
			if ( empty( $end_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
				$errors[] = sprintf( __( 'Row %d: Invalid end_date format (expected YYYY-MM-DD).', 'r2f-schengen' ), $row_number );
				$skipped++;
				continue;
			}

			// Validate date logic.
			$validation = $this->validate_trip_dates( $start_date, $end_date );
			if ( is_wp_error( $validation ) ) {
				$errors[] = sprintf( __( 'Row %d: %s', 'r2f-schengen' ), $row_number, $validation->get_error_message() );
				$skipped++;
				continue;
			}

			// Validate country - try to match case-insensitively.
			$matched_country = null;
			foreach ( self::SCHENGEN_COUNTRIES as $valid_country ) {
				if ( strtolower( $valid_country ) === strtolower( $country ) ) {
					$matched_country = $valid_country;
					break;
				}
			}

			if ( ! $matched_country ) {
				$errors[] = sprintf( __( 'Row %d: Invalid country "%s".', 'r2f-schengen' ), $row_number, $country );
				$skipped++;
				continue;
			}

			// Validate category.
			if ( ! in_array( $category, array( 'personal', 'business' ), true ) ) {
				$category = 'personal';
			}

			// Check for duplicates if enabled.
			if ( $skip_duplicates ) {
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM $table WHERE user_id = %d AND start_date = %s AND end_date = %s AND country = %s",
						$user_id,
						$start_date,
						$end_date,
						$matched_country
					)
				);

				if ( $existing ) {
					$skipped++;
					continue;
				}
			}

			// Insert the trip.
			$result = $wpdb->insert(
				$table,
				array(
					'user_id'    => $user_id,
					'start_date' => $start_date,
					'end_date'   => $end_date,
					'country'    => $matched_country,
					'category'   => $category,
					'notes'      => $notes ?: null,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				$errors[] = sprintf( __( 'Row %d: Database error.', 'r2f-schengen' ), $row_number );
				$skipped++;
			} else {
				$imported++;
			}
		}

		return rest_ensure_response( array(
			'success'  => true,
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => array_slice( $errors, 0, 10 ), // Limit errors to first 10.
			'message'  => sprintf(
				__( 'Imported %d trips. %d rows skipped.', 'r2f-schengen' ),
				$imported,
				$skipped
			),
		) );
	}

	/**
	 * Export trips to CSV format.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function export_trips_csv( WP_REST_Request $request ) {
		global $wpdb;

		$table   = R2F_Schengen_Schema::get_table( 'trips' );
		$user_id = get_current_user_id();

		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
				$user_id
			)
		);

		// Build CSV content.
		$lines   = array();
		$lines[] = 'start_date,end_date,country,category,notes';

		foreach ( $trips as $trip ) {
			$notes = str_replace( '"', '""', $trip->notes ?? '' );
			$lines[] = sprintf(
				'%s,%s,%s,%s,"%s"',
				$trip->start_date,
				$trip->end_date,
				$trip->country,
				$trip->category,
				$notes
			);
		}

		return rest_ensure_response( array(
			'csv'      => implode( "\n", $lines ),
			'filename' => 'schengen-trips-' . date( 'Y-m-d' ) . '.csv',
			'count'    => count( $trips ),
		) );
	}

	// ========================================
	// Helper Methods
	// ========================================

	/**
	 * Get user's Schengen settings.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_user_settings( int $user_id ): array {
		$defaults = array(
			'yellow_threshold'        => 60,
			'red_threshold'           => 80,
			'email_alerts'            => false,
			'upcoming_trip_reminders' => true,
		);

		$saved = get_user_meta( $user_id, 'fra_schengen_settings', true );

		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $saved );
	}

	/**
	 * Format settings for API response.
	 *
	 * @param array $settings Settings array.
	 * @return array
	 */
	private function format_settings_response( array $settings ): array {
		return array(
			'yellowThreshold'       => $settings['yellow_threshold'],
			'redThreshold'          => $settings['red_threshold'],
			'emailAlerts'           => $settings['email_alerts'],
			'upcomingTripReminders' => $settings['upcoming_trip_reminders'],
		);
	}

	/**
	 * Calculate days spent in Schengen within the 180-day window.
	 *
	 * @param array    $trips        Trips overlapping the window.
	 * @param DateTime $window_start Start of the 180-day window.
	 * @param DateTime $window_end   End of the window (today).
	 * @return int
	 */
	private function calculate_days_in_window( array $trips, DateTime $window_start, DateTime $window_end ): int {
		$days = array();

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip->start_date );
			$trip_end   = new DateTime( $trip->end_date );

			$effective_start = $trip_start < $window_start ? $window_start : $trip_start;
			$effective_end   = $trip_end > $window_end ? $window_end : $trip_end;

			if ( $effective_start <= $effective_end ) {
				$current = clone $effective_start;
				while ( $current <= $effective_end ) {
					$days[ $current->format( 'Y-m-d' ) ] = true;
					$current->modify( '+1 day' );
				}
			}
		}

		return count( $days );
	}

	/**
	 * Find when the next day will drop off the 180-day window.
	 *
	 * @param array    $trips        Trips in the window.
	 * @param DateTime $window_start Start of window.
	 * @param DateTime $window_end   End of window.
	 * @return string|null
	 */
	private function find_next_expiration( array $trips, DateTime $window_start, DateTime $window_end ): ?string {
		$days = array();

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip->start_date );
			$trip_end   = new DateTime( $trip->end_date );

			$effective_start = $trip_start < $window_start ? $window_start : $trip_start;
			$effective_end   = $trip_end > $window_end ? $window_end : $trip_end;

			if ( $effective_start <= $effective_end ) {
				$current = clone $effective_start;
				while ( $current <= $effective_end ) {
					$days[] = $current->format( 'Y-m-d' );
					$current->modify( '+1 day' );
				}
			}
		}

		if ( empty( $days ) ) {
			return null;
		}

		sort( $days );
		$earliest = new DateTime( $days[0] );
		$earliest->modify( '+180 days' );

		return $earliest->format( 'Y-m-d' );
	}

	/**
	 * Get compliance status based on days used.
	 *
	 * @param int $days_used        Days used.
	 * @param int $yellow_threshold Yellow threshold.
	 * @param int $red_threshold    Red threshold.
	 * @return string
	 */
	private function get_status( int $days_used, int $yellow_threshold, int $red_threshold ): string {
		if ( $days_used >= 90 ) {
			return 'critical';
		}
		if ( $days_used >= $red_threshold ) {
			return 'danger';
		}
		if ( $days_used >= $yellow_threshold ) {
			return 'warning';
		}
		return 'safe';
	}

	/**
	 * Validate trip dates.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return true|WP_Error
	 */
	private function validate_trip_dates( string $start_date, string $end_date ) {
		$start = strtotime( $start_date );
		$end   = strtotime( $end_date );

		if ( false === $start || false === $end ) {
			return new WP_Error(
				'invalid_date',
				__( 'Invalid date format. Use YYYY-MM-DD.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		if ( $end < $start ) {
			return new WP_Error(
				'invalid_date_range',
				__( 'End date must be on or after start date.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$days = ( $end - $start ) / ( 60 * 60 * 24 ) + 1;
		if ( $days > 90 ) {
			return new WP_Error(
				'trip_too_long',
				__( 'A single trip cannot exceed 90 days.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Format trip for API response.
	 *
	 * @param object $trip Trip database row.
	 * @return array
	 */
	private function format_trip( $trip ): array {
		return array(
			'id'        => (string) $trip->id,
			'startDate' => $trip->start_date,
			'endDate'   => $trip->end_date,
			'country'   => $trip->country,
			'category'  => $trip->category,
			'notes'     => $trip->notes,
			'createdAt' => $trip->created_at,
			'updatedAt' => $trip->updated_at,
		);
	}

	/**
	 * Calculate days used including a proposed trip.
	 *
	 * @param array    $existing_trips Existing trips.
	 * @param DateTime $proposed_start Start of proposed trip.
	 * @param DateTime $proposed_end   End of proposed trip.
	 * @param DateTime $reference_date Reference date for 180-day window.
	 * @return int
	 */
	private function calculate_days_with_proposed(
		array $existing_trips,
		DateTime $proposed_start,
		DateTime $proposed_end,
		DateTime $reference_date
	): int {
		$window_start = ( clone $reference_date )->modify( '-179 days' );
		$days         = array();

		foreach ( $existing_trips as $trip ) {
			$trip_start = new DateTime( $trip->start_date );
			$trip_end   = new DateTime( $trip->end_date );

			$effective_start = $trip_start < $window_start ? $window_start : $trip_start;
			$effective_end   = $trip_end > $reference_date ? $reference_date : $trip_end;

			if ( $effective_start <= $effective_end ) {
				$current = clone $effective_start;
				while ( $current <= $effective_end ) {
					$days[ $current->format( 'Y-m-d' ) ] = true;
					$current->modify( '+1 day' );
				}
			}
		}

		$effective_start = $proposed_start < $window_start ? $window_start : $proposed_start;
		$effective_end   = $proposed_end > $reference_date ? $reference_date : $proposed_end;

		if ( $effective_start <= $effective_end ) {
			$current = clone $effective_start;
			while ( $current <= $effective_end ) {
				$days[ $current->format( 'Y-m-d' ) ] = true;
				$current->modify( '+1 day' );
			}
		}

		return count( $days );
	}

	/**
	 * Find earliest safe entry date for a trip of given length.
	 *
	 * @param array $existing_trips Existing trips.
	 * @param int   $trip_length    Desired trip length in days.
	 * @return string|null
	 */
	private function find_earliest_safe_date( array $existing_trips, int $trip_length ): ?string {
		$check_date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$max_search = 365;

		for ( $i = 0; $i < $max_search; $i++ ) {
			$proposed_end  = ( clone $check_date )->modify( '+' . ( $trip_length - 1 ) . ' days' );
			$would_violate = false;
			$current       = clone $check_date;

			while ( $current <= $proposed_end ) {
				$days_used = $this->calculate_days_with_proposed(
					$existing_trips,
					$check_date,
					$current,
					$current
				);

				if ( $days_used > 90 ) {
					$would_violate = true;
					break;
				}
				$current->modify( '+1 day' );
			}

			if ( ! $would_violate ) {
				return $check_date->format( 'Y-m-d' );
			}

			$check_date->modify( '+1 day' );
		}

		return null;
	}

	/**
	 * Find maximum safe trip length starting from a given date.
	 *
	 * @param array    $existing_trips Existing trips.
	 * @param DateTime $start_date     Start date.
	 * @return int
	 */
	private function find_max_safe_length( array $existing_trips, DateTime $start_date ): int {
		$max_length = 0;

		for ( $length = 1; $length <= 90; $length++ ) {
			$proposed_end  = ( clone $start_date )->modify( '+' . ( $length - 1 ) . ' days' );
			$would_violate = false;
			$current       = clone $start_date;

			while ( $current <= $proposed_end ) {
				$days_used = $this->calculate_days_with_proposed(
					$existing_trips,
					$start_date,
					$current,
					$current
				);

				if ( $days_used > 90 ) {
					$would_violate = true;
					break;
				}
				$current->modify( '+1 day' );
			}

			if ( $would_violate ) {
				break;
			}

			$max_length = $length;
		}

		return $max_length;
	}

	/**
	 * Build HTML content for PDF report.
	 *
	 * @param array $data Report data.
	 * @return string
	 */
	private function build_report_html( array $data ): string {
		$user           = $data['user'];
		$trips          = $data['trips'];
		$days_used      = $data['days_used'];
		$days_remaining = $data['days_remaining'];
		$status         = $data['status'];

		$status_colors = array(
			'safe'     => '#22c55e',
			'warning'  => '#eab308',
			'danger'   => '#f97316',
			'critical' => '#ef4444',
		);
		$status_color = $status_colors[ $status ] ?? '#6b7280';

		$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>' . esc_html__( 'Schengen Tracker Report', 'r2f-schengen' ) . '</title>
	<style>
		body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px; color: #1f2937; }
		.header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #4A7BA7; padding-bottom: 20px; }
		.header h1 { color: #4A7BA7; margin-bottom: 5px; }
		.header .subtitle { color: #6b7280; }
		.summary { display: flex; justify-content: space-around; margin-bottom: 40px; }
		.stat-card { text-align: center; padding: 20px; background: #f3f4f6; border-radius: 8px; min-width: 150px; }
		.stat-value { font-size: 36px; font-weight: bold; color: #1f2937; }
		.stat-label { color: #6b7280; font-size: 14px; margin-top: 5px; }
		.status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; color: white; font-weight: bold; }
		.trips-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		.trips-table th { background: #4A7BA7; color: white; padding: 12px; text-align: left; }
		.trips-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
		.trips-table tr:nth-child(even) { background: #f9fafb; }
		.footer { margin-top: 40px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
		@media print { body { padding: 20px; } }
	</style>
</head>
<body>
	<div class="header">
		<h1>' . esc_html__( 'Schengen 90/180 Day Report', 'r2f-schengen' ) . '</h1>
		<div class="subtitle">' . esc_html( sprintf( __( 'Prepared for %s', 'r2f-schengen' ), $user->display_name ) ) . '</div>
		<div class="subtitle">' . esc_html( sprintf( __( 'Generated: %s', 'r2f-schengen' ), $data['generated_at'] ) ) . '</div>
	</div>

	<div class="summary">
		<div class="stat-card">
			<div class="stat-value">' . esc_html( $days_used ) . '</div>
			<div class="stat-label">' . esc_html__( 'Days Used', 'r2f-schengen' ) . '</div>
		</div>
		<div class="stat-card">
			<div class="stat-value">' . esc_html( $days_remaining ) . '</div>
			<div class="stat-label">' . esc_html__( 'Days Remaining', 'r2f-schengen' ) . '</div>
		</div>
		<div class="stat-card">
			<span class="status-badge" style="background-color: ' . esc_attr( $status_color ) . ';">' . esc_html( ucfirst( $status ) ) . '</span>
			<div class="stat-label" style="margin-top: 10px;">' . esc_html__( 'Current Status', 'r2f-schengen' ) . '</div>
		</div>
	</div>

	<div class="window-info">
		<p><strong>' . esc_html__( 'Current 180-Day Window:', 'r2f-schengen' ) . '</strong> ' . esc_html( $data['window_start'] ) . ' to ' . esc_html( $data['window_end'] ) . '</p>
	</div>

	<h2>' . esc_html__( 'Trip History', 'r2f-schengen' ) . '</h2>';

		if ( empty( $trips ) ) {
			$html .= '<p>' . esc_html__( 'No trips recorded.', 'r2f-schengen' ) . '</p>';
		} else {
			$html .= '
	<table class="trips-table">
		<thead>
			<tr>
				<th>' . esc_html__( 'Dates', 'r2f-schengen' ) . '</th>
				<th>' . esc_html__( 'Country', 'r2f-schengen' ) . '</th>
				<th>' . esc_html__( 'Duration', 'r2f-schengen' ) . '</th>
				<th>' . esc_html__( 'Category', 'r2f-schengen' ) . '</th>
				<th>' . esc_html__( 'Notes', 'r2f-schengen' ) . '</th>
			</tr>
		</thead>
		<tbody>';

			foreach ( $trips as $trip ) {
				$start    = new DateTime( $trip->start_date );
				$end      = new DateTime( $trip->end_date );
				$duration = $start->diff( $end )->days + 1;

				$html .= '
			<tr>
				<td>' . esc_html( $start->format( 'M j, Y' ) ) . ' - ' . esc_html( $end->format( 'M j, Y' ) ) . '</td>
				<td>' . esc_html( $trip->country ) . '</td>
				<td>' . esc_html( sprintf( _n( '%d day', '%d days', $duration, 'r2f-schengen' ), $duration ) ) . '</td>
				<td>' . esc_html( ucfirst( $trip->category ) ) . '</td>
				<td>' . esc_html( $trip->notes ?: '-' ) . '</td>
			</tr>';
			}

			$html .= '
		</tbody>
	</table>';
		}

		$html .= '
	<div class="footer">
		<p>' . esc_html__( 'This report was generated by Relo2France Schengen Tracker.', 'r2f-schengen' ) . '</p>
		<p>' . esc_html__( 'The 90/180 rule: Non-EU citizens may stay up to 90 days within any 180-day period in the Schengen area.', 'r2f-schengen' ) . '</p>
	</div>
</body>
</html>';

		return $html;
	}
}
