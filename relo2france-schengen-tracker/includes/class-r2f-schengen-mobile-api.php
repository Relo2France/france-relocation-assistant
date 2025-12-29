<?php
/**
 * MyTravelStatus Mobile API
 *
 * Provides REST API endpoints specifically for the native mobile app.
 * Includes batch sync, app status, and device registration.
 *
 * Supports the MyTravelStatus mobile apps for iOS and Android.
 * (Bundle ID / Package: com.mytravelstatus.app)
 *
 * @package R2F_Schengen_Tracker
 * @since   1.6.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_Mobile_API
 *
 * Mobile-specific REST API endpoints for the MyTravelStatus app.
 */
class R2F_Schengen_Mobile_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'r2f-schengen/v1';

	/**
	 * Current minimum app version required.
	 *
	 * @var string
	 */
	const MIN_APP_VERSION = '1.0.0';

	/**
	 * Current latest app version.
	 *
	 * @var string
	 */
	const LATEST_APP_VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Mobile_API|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Mobile_API
	 */
	public static function get_instance(): R2F_Schengen_Mobile_API {
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
	public function register_routes(): void {
		// App status check (public - no auth required).
		register_rest_route(
			self::NAMESPACE,
			'/app/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_app_status' ),
				'permission_callback' => '__return_true',
			)
		);

		// Batch sync endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_batch_sync' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Device registration for push notifications.
		register_rest_route(
			self::NAMESPACE,
			'/device/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'register_device' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Device unregistration.
		register_rest_route(
			self::NAMESPACE,
			'/device/unregister',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'unregister_device' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get changes since timestamp (for pull sync).
		register_rest_route(
			self::NAMESPACE,
			'/changes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_changes' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'since' => array(
						'type'              => 'string',
						'required'          => true,
						'description'       => 'ISO 8601 timestamp to get changes since',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Passport Control Mode - quick status endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/passport-control',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_passport_control_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Batch location upload.
		register_rest_route(
			self::NAMESPACE,
			'/locations/batch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'upload_locations_batch' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
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
	 * Get app status for version checking and maintenance mode.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_app_status( WP_REST_Request $request ): WP_REST_Response {
		$client_version = $request->get_param( 'version' );
		$platform       = $request->get_param( 'platform' ); // ios or android

		// Check if maintenance mode is enabled.
		$maintenance_mode = get_option( 'r2f_schengen_maintenance_mode', false );

		// Check if force update is required.
		$force_update = false;
		if ( $client_version && version_compare( $client_version, self::MIN_APP_VERSION, '<' ) ) {
			$force_update = true;
		}

		// Get platform-specific update URLs.
		$update_urls = array(
			'ios'     => get_option( 'r2f_schengen_ios_app_url', '' ),
			'android' => get_option( 'r2f_schengen_android_app_url', '' ),
		);

		return rest_ensure_response( array(
			'min_version'      => self::MIN_APP_VERSION,
			'latest_version'   => self::LATEST_APP_VERSION,
			'force_update'     => $force_update,
			'maintenance_mode' => (bool) $maintenance_mode,
			'maintenance_message' => $maintenance_mode
				? get_option( 'r2f_schengen_maintenance_message', __( 'The app is temporarily unavailable for maintenance.', 'r2f-schengen' ) )
				: null,
			'update_url'       => $platform && isset( $update_urls[ $platform ] ) ? $update_urls[ $platform ] : null,
			'server_time'      => gmdate( 'c' ),
			'features'         => array(
				'background_gps'   => true,
				'photo_import'     => true,
				'calendar_sync'    => true,
				'family_tracking'  => true,
				'multi_jurisdiction' => false, // Coming soon
			),
		) );
	}

	/**
	 * Handle batch sync from mobile app.
	 *
	 * Accepts local changes and returns server changes since last sync.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_batch_sync( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		$last_sync      = isset( $params['last_sync'] ) ? sanitize_text_field( $params['last_sync'] ) : null;
		$local_changes  = isset( $params['changes'] ) ? $params['changes'] : array();
		$device_id      = isset( $params['device_id'] ) ? sanitize_text_field( $params['device_id'] ) : null;

		$sync_results = array();
		$conflicts    = array();

		// Process local changes.
		foreach ( $local_changes as $change ) {
			$result = $this->process_sync_change( $user_id, $change );

			if ( is_wp_error( $result ) ) {
				if ( 'conflict' === $result->get_error_code() ) {
					$conflicts[] = $result->get_error_data();
				} else {
					$sync_results[] = array(
						'local_id' => $change['local_id'] ?? null,
						'success'  => false,
						'error'    => $result->get_error_message(),
					);
				}
			} else {
				$sync_results[] = array(
					'local_id'   => $change['local_id'] ?? null,
					'server_id'  => $result['id'],
					'success'    => true,
					'action'     => $result['action'],
				);
			}
		}

		// Get server changes since last sync.
		$server_changes = array();
		if ( $last_sync ) {
			$server_changes = $this->get_changes_since( $user_id, $last_sync, $device_id );
		}

		// Update device last sync time.
		if ( $device_id ) {
			$this->update_device_last_sync( $user_id, $device_id );
		}

		return rest_ensure_response( array(
			'success'        => true,
			'sync_results'   => $sync_results,
			'server_changes' => $server_changes,
			'conflicts'      => $conflicts,
			'server_time'    => gmdate( 'c' ),
		) );
	}

	/**
	 * Process a single sync change.
	 *
	 * @param int   $user_id User ID.
	 * @param array $change  Change data.
	 * @return array|WP_Error Result or error.
	 */
	private function process_sync_change( int $user_id, array $change ) {
		global $wpdb;

		$type   = isset( $change['type'] ) ? sanitize_text_field( $change['type'] ) : null;
		$action = isset( $change['action'] ) ? sanitize_text_field( $change['action'] ) : null;
		$data   = isset( $change['data'] ) ? $change['data'] : array();

		if ( ! in_array( $type, array( 'trip', 'location' ), true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid change type.', 'r2f-schengen' ) );
		}

		if ( ! in_array( $action, array( 'create', 'update', 'delete' ), true ) ) {
			return new WP_Error( 'invalid_action', __( 'Invalid action.', 'r2f-schengen' ) );
		}

		if ( 'trip' === $type ) {
			return $this->process_trip_change( $user_id, $action, $data );
		}

		if ( 'location' === $type ) {
			return $this->process_location_change( $user_id, $action, $data );
		}

		return new WP_Error( 'unknown_error', __( 'Unknown error processing change.', 'r2f-schengen' ) );
	}

	/**
	 * Process a trip change.
	 *
	 * @param int    $user_id User ID.
	 * @param string $action  Action (create, update, delete).
	 * @param array  $data    Trip data.
	 * @return array|WP_Error
	 */
	private function process_trip_change( int $user_id, string $action, array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fra_schengen_trips';

		if ( 'create' === $action ) {
			$result = $wpdb->insert(
				$table,
				array(
					'user_id'           => $user_id,
					'start_date'        => sanitize_text_field( $data['start_date'] ?? '' ),
					'end_date'          => sanitize_text_field( $data['end_date'] ?? '' ),
					'country'           => sanitize_text_field( $data['country'] ?? '' ),
					'category'          => sanitize_text_field( $data['category'] ?? 'personal' ),
					'notes'             => sanitize_textarea_field( $data['notes'] ?? '' ),
					'location_source'   => 'mobile_app',
					'location_lat'      => isset( $data['lat'] ) ? (float) $data['lat'] : null,
					'location_lng'      => isset( $data['lng'] ) ? (float) $data['lng'] : null,
					'created_at'        => current_time( 'mysql', true ),
					'updated_at'        => current_time( 'mysql', true ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error( 'db_error', $wpdb->last_error );
			}

			return array(
				'id'     => $wpdb->insert_id,
				'action' => 'created',
			);
		}

		if ( 'update' === $action ) {
			$trip_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

			// Verify ownership.
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$trip_id
			) );

			if ( ! $existing ) {
				return new WP_Error( 'not_found', __( 'Trip not found.', 'r2f-schengen' ) );
			}

			if ( (int) $existing->user_id !== $user_id ) {
				return new WP_Error( 'forbidden', __( 'Access denied.', 'r2f-schengen' ) );
			}

			// Check for conflicts (server version newer than client's last sync).
			if ( isset( $data['last_synced'] ) ) {
				$client_last_sync = strtotime( $data['last_synced'] );
				$server_updated   = strtotime( $existing->updated_at );

				if ( $server_updated > $client_last_sync ) {
					return new WP_Error(
						'conflict',
						__( 'Conflict detected.', 'r2f-schengen' ),
						array(
							'type'           => 'trip',
							'id'             => $trip_id,
							'local_version'  => $data,
							'server_version' => $this->format_trip( $existing ),
						)
					);
				}
			}

			$update_data = array(
				'updated_at' => current_time( 'mysql', true ),
			);
			$update_format = array( '%s' );

			if ( isset( $data['start_date'] ) ) {
				$update_data['start_date'] = sanitize_text_field( $data['start_date'] );
				$update_format[] = '%s';
			}
			if ( isset( $data['end_date'] ) ) {
				$update_data['end_date'] = sanitize_text_field( $data['end_date'] );
				$update_format[] = '%s';
			}
			if ( isset( $data['country'] ) ) {
				$update_data['country'] = sanitize_text_field( $data['country'] );
				$update_format[] = '%s';
			}
			if ( isset( $data['category'] ) ) {
				$update_data['category'] = sanitize_text_field( $data['category'] );
				$update_format[] = '%s';
			}
			if ( isset( $data['notes'] ) ) {
				$update_data['notes'] = sanitize_textarea_field( $data['notes'] );
				$update_format[] = '%s';
			}

			$wpdb->update(
				$table,
				$update_data,
				array( 'id' => $trip_id ),
				$update_format,
				array( '%d' )
			);

			return array(
				'id'     => $trip_id,
				'action' => 'updated',
			);
		}

		if ( 'delete' === $action ) {
			$trip_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

			// Verify ownership.
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT user_id FROM $table WHERE id = %d",
				$trip_id
			) );

			if ( ! $existing || (int) $existing->user_id !== $user_id ) {
				return new WP_Error( 'forbidden', __( 'Access denied.', 'r2f-schengen' ) );
			}

			$wpdb->delete( $table, array( 'id' => $trip_id ), array( '%d' ) );

			return array(
				'id'     => $trip_id,
				'action' => 'deleted',
			);
		}

		return new WP_Error( 'invalid_action', __( 'Invalid action.', 'r2f-schengen' ) );
	}

	/**
	 * Process a location change.
	 *
	 * @param int    $user_id User ID.
	 * @param string $action  Action (create, update, delete).
	 * @param array  $data    Location data.
	 * @return array|WP_Error
	 */
	private function process_location_change( int $user_id, string $action, array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fra_schengen_location_log';

		if ( 'create' === $action ) {
			$result = $wpdb->insert(
				$table,
				array(
					'user_id'      => $user_id,
					'lat'          => (float) ( $data['lat'] ?? 0 ),
					'lng'          => (float) ( $data['lng'] ?? 0 ),
					'accuracy'     => isset( $data['accuracy'] ) ? (float) $data['accuracy'] : null,
					'country_code' => sanitize_text_field( $data['country_code'] ?? '' ),
					'country_name' => sanitize_text_field( $data['country_name'] ?? '' ),
					'city'         => sanitize_text_field( $data['city'] ?? '' ),
					'is_schengen'  => isset( $data['is_schengen'] ) ? (int) $data['is_schengen'] : 0,
					'source'       => 'mobile_gps',
					'recorded_at'  => isset( $data['recorded_at'] )
						? sanitize_text_field( $data['recorded_at'] )
						: current_time( 'mysql', true ),
				),
				array( '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				return new WP_Error( 'db_error', $wpdb->last_error );
			}

			return array(
				'id'     => $wpdb->insert_id,
				'action' => 'created',
			);
		}

		// Locations are typically only created, not updated or deleted from mobile.
		return new WP_Error( 'unsupported', __( 'Only create action is supported for locations.', 'r2f-schengen' ) );
	}

	/**
	 * Get changes since a timestamp.
	 *
	 * @param int         $user_id   User ID.
	 * @param string      $since     ISO 8601 timestamp.
	 * @param string|null $device_id Device ID to exclude own changes.
	 * @return array
	 */
	private function get_changes_since( int $user_id, string $since, ?string $device_id = null ): array {
		global $wpdb;

		$changes = array();

		// Get trip changes.
		$trips_table = $wpdb->prefix . 'fra_schengen_trips';
		$trips = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $trips_table
			 WHERE user_id = %d
			 AND updated_at > %s
			 ORDER BY updated_at ASC",
			$user_id,
			$since
		) );

		foreach ( $trips as $trip ) {
			$changes[] = array(
				'type'       => 'trip',
				'action'     => 'update', // Could be create or update - client should upsert.
				'data'       => $this->format_trip( $trip ),
				'updated_at' => $trip->updated_at,
			);
		}

		return $changes;
	}

	/**
	 * Format a trip for API response.
	 *
	 * @param object $trip Trip database row.
	 * @return array
	 */
	private function format_trip( $trip ): array {
		return array(
			'id'         => (int) $trip->id,
			'start_date' => $trip->start_date,
			'end_date'   => $trip->end_date,
			'country'    => $trip->country,
			'category'   => $trip->category ?? 'personal',
			'notes'      => $trip->notes ?? '',
			'created_at' => $trip->created_at,
			'updated_at' => $trip->updated_at,
		);
	}

	/**
	 * Register a device for push notifications.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function register_device( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		$device_id    = isset( $params['device_id'] ) ? sanitize_text_field( $params['device_id'] ) : null;
		$push_token   = isset( $params['push_token'] ) ? sanitize_text_field( $params['push_token'] ) : null;
		$platform     = isset( $params['platform'] ) ? sanitize_text_field( $params['platform'] ) : null;
		$app_version  = isset( $params['app_version'] ) ? sanitize_text_field( $params['app_version'] ) : null;
		$device_name  = isset( $params['device_name'] ) ? sanitize_text_field( $params['device_name'] ) : null;

		if ( ! $device_id || ! $push_token || ! $platform ) {
			return new WP_Error(
				'missing_params',
				__( 'device_id, push_token, and platform are required.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		if ( ! in_array( $platform, array( 'ios', 'android' ), true ) ) {
			return new WP_Error(
				'invalid_platform',
				__( 'Platform must be ios or android.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$table = $wpdb->prefix . 'fra_schengen_devices';

		// Check if device already registered.
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $table WHERE device_id = %s",
			$device_id
		) );

		if ( $existing ) {
			// Update existing device.
			$wpdb->update(
				$table,
				array(
					'user_id'     => $user_id,
					'push_token'  => $push_token,
					'platform'    => $platform,
					'app_version' => $app_version,
					'device_name' => $device_name,
					'updated_at'  => current_time( 'mysql', true ),
				),
				array( 'device_id' => $device_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
		} else {
			// Insert new device.
			$wpdb->insert(
				$table,
				array(
					'user_id'     => $user_id,
					'device_id'   => $device_id,
					'push_token'  => $push_token,
					'platform'    => $platform,
					'app_version' => $app_version,
					'device_name' => $device_name,
					'created_at'  => current_time( 'mysql', true ),
					'updated_at'  => current_time( 'mysql', true ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		return rest_ensure_response( array(
			'success'   => true,
			'device_id' => $device_id,
			'message'   => __( 'Device registered successfully.', 'r2f-schengen' ),
		) );
	}

	/**
	 * Unregister a device.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function unregister_device( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		$device_id = isset( $params['device_id'] ) ? sanitize_text_field( $params['device_id'] ) : null;

		if ( ! $device_id ) {
			return new WP_Error(
				'missing_device_id',
				__( 'device_id is required.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$table = $wpdb->prefix . 'fra_schengen_devices';

		// Verify ownership before deleting.
		$device = $wpdb->get_row( $wpdb->prepare(
			"SELECT user_id FROM $table WHERE device_id = %s",
			$device_id
		) );

		if ( ! $device || (int) $device->user_id !== $user_id ) {
			return new WP_Error(
				'not_found',
				__( 'Device not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		$wpdb->delete( $table, array( 'device_id' => $device_id ), array( '%s' ) );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Device unregistered successfully.', 'r2f-schengen' ),
		) );
	}

	/**
	 * Get changes since a timestamp (pull sync).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_changes( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$since   = $request->get_param( 'since' );

		$changes = $this->get_changes_since( $user_id, $since );

		return rest_ensure_response( array(
			'changes'     => $changes,
			'server_time' => gmdate( 'c' ),
		) );
	}

	/**
	 * Get data for Passport Control Mode.
	 *
	 * Optimized endpoint for quick border crossing display.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_passport_control_data( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'fra_schengen_trips';
		$today   = current_time( 'Y-m-d' );

		// Calculate 180-day window.
		$window_start = gmdate( 'Y-m-d', strtotime( '-179 days' ) );

		// Get trips in current window.
		$trips = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table
			 WHERE user_id = %d
			 AND end_date >= %s
			 ORDER BY start_date DESC",
			$user_id,
			$window_start
		) );

		// Calculate days used in window.
		$days_used = 0;
		$recent_trips = array();

		foreach ( $trips as $trip ) {
			// Calculate days this trip contributes to current window.
			$trip_start = max( $trip->start_date, $window_start );
			$trip_end   = min( $trip->end_date, $today );

			if ( $trip_start <= $trip_end ) {
				$start_ts = strtotime( $trip_start );
				$end_ts   = strtotime( $trip_end );
				$days     = (int) ( ( $end_ts - $start_ts ) / 86400 ) + 1;
				$days_used += $days;
			}

			// Add to recent trips (limit to 5).
			if ( count( $recent_trips ) < 5 ) {
				$recent_trips[] = array(
					'country'    => $trip->country,
					'start_date' => $trip->start_date,
					'end_date'   => $trip->end_date,
					'days'       => $this->calculate_trip_days( $trip ),
				);
			}
		}

		$days_remaining = max( 0, 90 - $days_used );
		$is_compliant   = $days_used <= 90;

		// Determine status level.
		$status = 'safe';
		if ( $days_used >= 90 ) {
			$status = 'critical';
		} elseif ( $days_used >= 80 ) {
			$status = 'danger';
		} elseif ( $days_used >= 60 ) {
			$status = 'warning';
		}

		return rest_ensure_response( array(
			'is_compliant'   => $is_compliant,
			'days_used'      => $days_used,
			'days_allowed'   => 90,
			'days_remaining' => $days_remaining,
			'status'         => $status,
			'window_start'   => $window_start,
			'window_end'     => $today,
			'recent_trips'   => $recent_trips,
			'last_verified'  => gmdate( 'c' ),
		) );
	}

	/**
	 * Calculate total days in a trip.
	 *
	 * @param object $trip Trip object.
	 * @return int
	 */
	private function calculate_trip_days( $trip ): int {
		$start = strtotime( $trip->start_date );
		$end   = strtotime( $trip->end_date );
		return (int) ( ( $end - $start ) / 86400 ) + 1;
	}

	/**
	 * Upload a batch of location readings.
	 *
	 * Used for syncing multiple GPS readings collected offline.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_locations_batch( WP_REST_Request $request ) {
		global $wpdb;

		$user_id   = get_current_user_id();
		$params    = $request->get_json_params();
		$locations = isset( $params['locations'] ) ? $params['locations'] : array();

		if ( empty( $locations ) ) {
			return new WP_Error(
				'no_locations',
				__( 'No locations provided.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$table     = $wpdb->prefix . 'fra_schengen_location_log';
		$inserted  = 0;
		$errors    = array();

		foreach ( $locations as $index => $loc ) {
			$result = $wpdb->insert(
				$table,
				array(
					'user_id'      => $user_id,
					'lat'          => (float) ( $loc['lat'] ?? 0 ),
					'lng'          => (float) ( $loc['lng'] ?? 0 ),
					'accuracy'     => isset( $loc['accuracy'] ) ? (float) $loc['accuracy'] : null,
					'country_code' => sanitize_text_field( $loc['country_code'] ?? '' ),
					'country_name' => sanitize_text_field( $loc['country_name'] ?? '' ),
					'city'         => sanitize_text_field( $loc['city'] ?? '' ),
					'is_schengen'  => isset( $loc['is_schengen'] ) ? (int) $loc['is_schengen'] : 0,
					'source'       => 'mobile_gps',
					'recorded_at'  => isset( $loc['recorded_at'] )
						? sanitize_text_field( $loc['recorded_at'] )
						: current_time( 'mysql', true ),
				),
				array( '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s' )
			);

			if ( false === $result ) {
				$errors[] = array(
					'index' => $index,
					'error' => $wpdb->last_error,
				);
			} else {
				$inserted++;
			}
		}

		return rest_ensure_response( array(
			'success'  => count( $errors ) === 0,
			'inserted' => $inserted,
			'total'    => count( $locations ),
			'errors'   => $errors,
		) );
	}

	/**
	 * Update device last sync timestamp.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $device_id Device ID.
	 */
	private function update_device_last_sync( int $user_id, string $device_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'fra_schengen_devices';

		$wpdb->update(
			$table,
			array( 'last_sync' => current_time( 'mysql', true ) ),
			array(
				'user_id'   => $user_id,
				'device_id' => $device_id,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}
}
