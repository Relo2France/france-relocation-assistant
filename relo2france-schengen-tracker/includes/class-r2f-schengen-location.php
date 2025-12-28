<?php
/**
 * Schengen Tracker Location Handling
 *
 * Provides location tracking via browser geolocation, reverse geocoding,
 * and location history management.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_Location
 *
 * Handles location check-ins, reverse geocoding, and location history.
 */
class R2F_Schengen_Location {

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Location|null
	 */
	private static $instance = null;

	/**
	 * Nominatim API endpoint.
	 *
	 * @var string
	 */
	const NOMINATIM_API = 'https://nominatim.openstreetmap.org/reverse';

	/**
	 * Cache duration for geocoding results (1 hour).
	 *
	 * @var int
	 */
	const GEOCODE_CACHE_DURATION = 3600;

	/**
	 * Location sources.
	 *
	 * @var array
	 */
	const LOCATION_SOURCES = array(
		'browser'  => 'Browser Geolocation',
		'manual'   => 'Manual Entry',
		'calendar' => 'Calendar Import',
		'checkin'  => 'Check-in Button',
	);

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Location
	 */
	public static function get_instance(): R2F_Schengen_Location {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes for location.
	 */
	public function register_routes(): void {
		// Register under the namespace that Member Tools portal uses.
		$namespace = 'fra-portal/v1';
		$base      = '/schengen/location';

		// Store current location - simplified for debugging.
		register_rest_route(
			$namespace,
			$base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'store_location_debug' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Today status - simplified.
		register_rest_route(
			$namespace,
			$base . '/today',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_today_debug' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Detect - simplified.
		register_rest_route(
			$namespace,
			$base . '/detect',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'detect_debug' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Debug: Simple store location.
	 */
	public function store_location_debug( $request ) {
		return rest_ensure_response( array(
			'success'  => true,
			'message'  => 'Location recorded (debug mode).',
			'location' => array(
				'id'          => 1,
				'lat'         => 48.8566,
				'lng'         => 2.3522,
				'countryCode' => 'FR',
				'countryName' => 'France',
				'city'        => 'Paris',
				'isSchengen'  => true,
				'source'      => 'browser',
				'recordedAt'  => current_time( 'mysql' ),
			),
		) );
	}

	/**
	 * Debug: Simple today status.
	 */
	public function get_today_debug( $request ) {
		return rest_ensure_response( array(
			'hasCheckedInToday' => false,
			'todayLocations'    => array(),
			'lastLocation'      => null,
		) );
	}

	/**
	 * Debug: Simple IP detect.
	 */
	public function detect_debug( $request ) {
		return rest_ensure_response( array(
			'detected'    => false,
			'reason'      => 'debug',
			'countryCode' => null,
		) );
	}

	/**
	 * DISABLED - Original routes.
	 */
	private function register_routes_disabled(): void {
		$namespace = 'fra-portal/v1';
		$base      = '/schengen/location';
		// Store current location (check-in).
		register_rest_route(
			$namespace,
			$base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'store_location' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_location_args(),
				),
			)
		);

		// Get location history.
		register_rest_route(
			$namespace,
			$base . '/history',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_location_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'limit' => array(
						'type'    => 'integer',
						'default' => 30,
						'minimum' => 1,
						'maximum' => 100,
					),
					'offset' => array(
						'type'    => 'integer',
						'default' => 0,
						'minimum' => 0,
					),
				),
			)
		);

		// Get today's location status.
		register_rest_route(
			$namespace,
			$base . '/today',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_today_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Reverse geocode coordinates.
		register_rest_route(
			$namespace,
			$base . '/geocode',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reverse_geocode' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'lat' => array(
						'type'     => 'number',
						'required' => true,
					),
					'lng' => array(
						'type'     => 'number',
						'required' => true,
					),
				),
			)
		);

		// Delete location history entry.
		register_rest_route(
			$namespace,
			$base . '/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_location' ),
				'permission_callback' => array( $this, 'check_location_permission' ),
			)
		);

		// Clear all location history (privacy).
		register_rest_route(
			$namespace,
			$base . '/clear',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_location_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get location settings.
		register_rest_route(
			$namespace,
			$base . '/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_location_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_location_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Detect country from IP (fallback when geolocation unavailable).
		register_rest_route(
			$namespace,
			$base . '/detect',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'detect_from_ip' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get location endpoint arguments.
	 *
	 * @return array
	 */
	private function get_location_args(): array {
		return array(
			'lat' => array(
				'type'              => 'number',
				'required'          => true,
				'sanitize_callback' => 'floatval',
			),
			'lng' => array(
				'type'              => 'number',
				'required'          => true,
				'sanitize_callback' => 'floatval',
			),
			'accuracy' => array(
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
			),
			'source' => array(
				'type'    => 'string',
				'enum'    => array_keys( self::LOCATION_SOURCES ),
				'default' => 'browser',
			),
		);
	}

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
	 * Check if user owns the location entry.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_location_permission( WP_REST_Request $request ) {
		$permission = $this->check_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$location_id = (int) $request->get_param( 'id' );
		$location    = $this->get_location_by_id( $location_id );

		if ( ! $location ) {
			return new WP_Error(
				'not_found',
				__( 'Location entry not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $location->user_id !== get_current_user_id() ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to access this location entry.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get location entry by ID.
	 *
	 * @param int $location_id Location ID.
	 * @return object|null
	 */
	private function get_location_by_id( int $location_id ) {
		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'location_log' );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $location_id )
		);
	}

	/**
	 * Store a location check-in.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function store_location( WP_REST_Request $request ) {
		global $wpdb;

		$user_id  = get_current_user_id();
		$params   = $request->get_json_params();
		$lat      = (float) $params['lat'];
		$lng      = (float) $params['lng'];
		$accuracy = isset( $params['accuracy'] ) ? (float) $params['accuracy'] : null;
		$source   = isset( $params['source'] ) ? sanitize_text_field( $params['source'] ) : 'browser';

		// Validate coordinates.
		if ( $lat < -90 || $lat > 90 ) {
			return new WP_Error(
				'invalid_latitude',
				__( 'Latitude must be between -90 and 90.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}
		if ( $lng < -180 || $lng > 180 ) {
			return new WP_Error(
				'invalid_longitude',
				__( 'Longitude must be between -180 and 180.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		// Reverse geocode to get country.
		$geocode_result = $this->perform_reverse_geocode( $lat, $lng );
		$country_code   = $geocode_result['country_code'] ?? null;
		$country_name   = $geocode_result['country_name'] ?? null;
		$city           = $geocode_result['city'] ?? null;
		$is_schengen    = $country_code ? $this->is_schengen_country_code( $country_code ) : false;

		$table  = R2F_Schengen_Schema::get_table( 'location_log' );
		$result = $wpdb->insert(
			$table,
			array(
				'user_id'      => $user_id,
				'lat'          => $lat,
				'lng'          => $lng,
				'accuracy'     => $accuracy,
				'country_code' => $country_code,
				'country_name' => $country_name,
				'city'         => $city,
				'is_schengen'  => $is_schengen ? 1 : 0,
				'source'       => $source,
				'recorded_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to store location.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$location = $this->get_location_by_id( $wpdb->insert_id );

		/**
		 * Fires after a location is recorded.
		 *
		 * @param int    $user_id       User ID.
		 * @param object $location      Location data.
		 * @param bool   $is_schengen   Whether the location is in Schengen zone.
		 */
		do_action( 'r2f_schengen_location_recorded', $user_id, $location, $is_schengen );

		return rest_ensure_response( array(
			'success'  => true,
			'location' => $this->format_location( $location ),
			'message'  => $is_schengen
				? sprintf(
					/* translators: %s: country name */
					__( 'Location recorded in %s (Schengen zone).', 'r2f-schengen' ),
					$country_name
				)
				: sprintf(
					/* translators: %s: country name */
					__( 'Location recorded in %s.', 'r2f-schengen' ),
					$country_name ?: __( 'Unknown location', 'r2f-schengen' )
				),
		) );
	}

	/**
	 * Get location history for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_location_history( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$limit   = (int) $request->get_param( 'limit' );
		$offset  = (int) $request->get_param( 'offset' );
		$table   = R2F_Schengen_Schema::get_table( 'location_log' );

		// Get total count.
		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id )
		);

		// Get locations.
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE user_id = %d
				ORDER BY recorded_at DESC
				LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			)
		);

		return rest_ensure_response( array(
			'locations' => array_map( array( $this, 'format_location' ), $locations ),
			'total'     => $total,
			'limit'     => $limit,
			'offset'    => $offset,
		) );
	}

	/**
	 * Get today's location status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_today_status( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'location_log' );
		$today   = current_time( 'Y-m-d' );

		// Get today's check-ins.
		$today_locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE user_id = %d
				AND DATE(recorded_at) = %s
				ORDER BY recorded_at DESC",
				$user_id,
				$today
			)
		);

		// Get the most recent location regardless of date.
		$last_location = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table
				WHERE user_id = %d
				ORDER BY recorded_at DESC
				LIMIT 1",
				$user_id
			)
		);

		// Check if user has checked in today.
		$has_checked_in_today = ! empty( $today_locations );

		// Get location settings.
		$settings = $this->get_user_location_settings( $user_id );

		return rest_ensure_response( array(
			'hasCheckedInToday' => $has_checked_in_today,
			'todayLocations'    => array_map( array( $this, 'format_location' ), $today_locations ),
			'lastLocation'      => $last_location ? $this->format_location( $last_location ) : null,
			'reminderEnabled'   => $settings['daily_reminder'],
			'trackingEnabled'   => $settings['tracking_enabled'],
		) );
	}

	/**
	 * Reverse geocode coordinates.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reverse_geocode( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$lat    = (float) $params['lat'];
		$lng    = (float) $params['lng'];

		$result = $this->perform_reverse_geocode( $lat, $lng );

		if ( isset( $result['error'] ) ) {
			return new WP_Error(
				'geocode_error',
				$result['error'],
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Perform reverse geocoding using OpenStreetMap Nominatim.
	 *
	 * @param float $lat Latitude.
	 * @param float $lng Longitude.
	 * @return array
	 */
	private function perform_reverse_geocode( float $lat, float $lng ): array {
		// Check cache first.
		$cache_key = 'r2f_geocode_' . md5( $lat . '_' . $lng );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Make request to Nominatim.
		$url = add_query_arg(
			array(
				'format' => 'json',
				'lat'    => $lat,
				'lon'    => $lng,
				'zoom'   => 10,
			),
			self::NOMINATIM_API
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'User-Agent' => 'Relo2France-Schengen-Tracker/1.0 (contact@relo2france.com)',
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || isset( $data['error'] ) ) {
			return array(
				'error' => isset( $data['error'] ) ? $data['error'] : __( 'Geocoding failed.', 'r2f-schengen' ),
			);
		}

		$address = $data['address'] ?? array();

		$result = array(
			'country_code' => isset( $address['country_code'] ) ? strtoupper( $address['country_code'] ) : null,
			'country_name' => $address['country'] ?? null,
			'city'         => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
			'state'        => $address['state'] ?? null,
			'display_name' => $data['display_name'] ?? null,
			'is_schengen'  => isset( $address['country_code'] )
				? $this->is_schengen_country_code( strtoupper( $address['country_code'] ) )
				: false,
		);

		// Cache for 1 hour.
		set_transient( $cache_key, $result, self::GEOCODE_CACHE_DURATION );

		return $result;
	}

	/**
	 * Delete a location entry.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_location( WP_REST_Request $request ) {
		global $wpdb;

		$location_id = (int) $request->get_param( 'id' );
		$table       = R2F_Schengen_Schema::get_table( 'location_log' );

		$result = $wpdb->delete(
			$table,
			array( 'id' => $location_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to delete location.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array(
			'deleted' => true,
			'id'      => $location_id,
		) );
	}

	/**
	 * Clear all location history for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function clear_location_history( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'location_log' );

		$result = $wpdb->delete(
			$table,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to clear location history.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * Fires after location history is cleared.
		 *
		 * @param int $user_id User ID.
		 */
		do_action( 'r2f_schengen_location_history_cleared', $user_id );

		return rest_ensure_response( array(
			'cleared' => true,
			'message' => __( 'Location history has been cleared.', 'r2f-schengen' ),
		) );
	}

	/**
	 * Get location settings for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_location_settings( WP_REST_Request $request ) {
		$user_id  = get_current_user_id();
		$settings = $this->get_user_location_settings( $user_id );

		return rest_ensure_response( $settings );
	}

	/**
	 * Update location settings for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_location_settings( WP_REST_Request $request ) {
		$user_id  = get_current_user_id();
		$params   = $request->get_json_params();
		$settings = $this->get_user_location_settings( $user_id );

		if ( isset( $params['trackingEnabled'] ) ) {
			$settings['tracking_enabled'] = (bool) $params['trackingEnabled'];
		}
		if ( isset( $params['dailyReminder'] ) ) {
			$settings['daily_reminder'] = (bool) $params['dailyReminder'];
		}
		if ( isset( $params['autoDetect'] ) ) {
			$settings['auto_detect'] = (bool) $params['autoDetect'];
		}

		update_user_meta( $user_id, 'r2f_schengen_location_settings', $settings );

		return rest_ensure_response( $settings );
	}

	/**
	 * Get user's location settings.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_user_location_settings( int $user_id ): array {
		$defaults = array(
			'tracking_enabled' => true,
			'daily_reminder'   => false,
			'auto_detect'      => false,
		);

		$saved = get_user_meta( $user_id, 'r2f_schengen_location_settings', true );

		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $saved );
	}

	/**
	 * Format location for API response.
	 *
	 * @param object $location Location database row.
	 * @return array
	 */
	private function format_location( $location ): array {
		return array(
			'id'          => (int) $location->id,
			'lat'         => (float) $location->lat,
			'lng'         => (float) $location->lng,
			'accuracy'    => $location->accuracy ? (float) $location->accuracy : null,
			'countryCode' => $location->country_code,
			'countryName' => $location->country_name,
			'city'        => $location->city,
			'isSchengen'  => (bool) $location->is_schengen,
			'source'      => $location->source,
			'recordedAt'  => $location->recorded_at,
		);
	}

	/**
	 * Check if a country code is in the Schengen zone.
	 *
	 * @param string $country_code Two-letter country code.
	 * @return bool
	 */
	private function is_schengen_country_code( string $country_code ): bool {
		$schengen_codes = array(
			'AT', // Austria
			'BE', // Belgium
			'BG', // Bulgaria
			'HR', // Croatia
			'CZ', // Czech Republic
			'DK', // Denmark
			'EE', // Estonia
			'FI', // Finland
			'FR', // France
			'DE', // Germany
			'GR', // Greece
			'HU', // Hungary
			'IS', // Iceland
			'IT', // Italy
			'LV', // Latvia
			'LI', // Liechtenstein
			'LT', // Lithuania
			'LU', // Luxembourg
			'MT', // Malta
			'NL', // Netherlands
			'NO', // Norway
			'PL', // Poland
			'PT', // Portugal
			'RO', // Romania
			'SK', // Slovakia
			'SI', // Slovenia
			'ES', // Spain
			'SE', // Sweden
			'CH', // Switzerland
		);

		return in_array( strtoupper( $country_code ), $schengen_codes, true );
	}

	/**
	 * Detect country from user's IP address.
	 *
	 * Uses ip-api.com (free, no API key, 45 req/min limit).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function detect_from_ip( WP_REST_Request $request ) {
		// Get user's IP address.
		$ip = $this->get_client_ip();

		if ( ! $ip || $ip === '127.0.0.1' || strpos( $ip, '192.168.' ) === 0 || strpos( $ip, '10.' ) === 0 ) {
			return rest_ensure_response( array(
				'detected'    => false,
				'reason'      => 'local_ip',
				'message'     => __( 'Cannot detect location from local IP address.', 'r2f-schengen' ),
				'countryCode' => null,
				'countryName' => null,
				'city'        => null,
				'isSchengen'  => false,
			) );
		}

		// Check cache first.
		$cache_key = 'r2f_ip_geo_' . md5( $ip );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		// Use ip-api.com (free, no key required).
		$url = 'http://ip-api.com/json/' . $ip . '?fields=status,message,country,countryCode,city,query';

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( array(
				'detected'    => false,
				'reason'      => 'api_error',
				'message'     => __( 'Failed to detect location from IP.', 'r2f-schengen' ),
				'countryCode' => null,
				'countryName' => null,
				'city'        => null,
				'isSchengen'  => false,
			) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ( isset( $data['status'] ) && 'fail' === $data['status'] ) ) {
			return rest_ensure_response( array(
				'detected'    => false,
				'reason'      => 'lookup_failed',
				'message'     => $data['message'] ?? __( 'IP lookup failed.', 'r2f-schengen' ),
				'countryCode' => null,
				'countryName' => null,
				'city'        => null,
				'isSchengen'  => false,
			) );
		}

		$country_code = $data['countryCode'] ?? null;
		$is_schengen  = $country_code ? $this->is_schengen_country_code( $country_code ) : false;

		$result = array(
			'detected'    => true,
			'reason'      => 'ip_lookup',
			'message'     => sprintf(
				/* translators: %s: country name */
				__( 'Detected location: %s', 'r2f-schengen' ),
				$data['country'] ?? __( 'Unknown', 'r2f-schengen' )
			),
			'countryCode' => $country_code,
			'countryName' => $data['country'] ?? null,
			'city'        => $data['city'] ?? null,
			'isSchengen'  => $is_schengen,
			'ip'          => $data['query'] ?? null,
		);

		// Cache for 1 hour.
		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return rest_ensure_response( $result );
	}

	/**
	 * Get the client's IP address.
	 *
	 * @return string|null
	 */
	private function get_client_ip(): ?string {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return null;
	}

	/**
	 * Get Schengen countries with their codes.
	 *
	 * @return array
	 */
	public static function get_schengen_country_codes(): array {
		return array(
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'BG' => 'Bulgaria',
			'HR' => 'Croatia',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'GR' => 'Greece',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IT' => 'Italy',
			'LV' => 'Latvia',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MT' => 'Malta',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
		);
	}
}
