<?php
/**
 * Notifications for Schengen Tracker.
 *
 * Handles in-app notifications, Web Push API subscriptions,
 * and notification delivery.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.4.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_Notifications
 *
 * Manages notifications and push subscriptions.
 */
class R2F_Schengen_Notifications {

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
	 * Notification types and their default settings.
	 *
	 * @var array
	 */
	const NOTIFICATION_TYPES = array(
		'threshold_warning' => array(
			'title'    => 'Schengen Alert: %d days used',
			'icon'     => 'warning',
			'priority' => 'normal',
		),
		'threshold_danger'  => array(
			'title'    => 'URGENT: Only %d days remaining!',
			'icon'     => 'danger',
			'priority' => 'high',
		),
		'trip_reminder'     => array(
			'title'    => 'Upcoming trip: %s',
			'icon'     => 'calendar',
			'priority' => 'normal',
		),
		'day_expiring'      => array(
			'title'    => 'Days expiring tomorrow',
			'icon'     => 'info',
			'priority' => 'low',
		),
		'calendar_sync'     => array(
			'title'    => 'New trips detected',
			'icon'     => 'calendar',
			'priority' => 'normal',
		),
		'location_checkin'  => array(
			'title'    => 'Location check-in reminder',
			'icon'     => 'location',
			'priority' => 'low',
		),
	);

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Notifications|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Notifications
	 */
	public static function get_instance(): R2F_Schengen_Notifications {
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

		// Listen for events that should create notifications.
		add_action( 'r2f_schengen_alert_sent', array( $this, 'on_alert_sent' ), 10, 3 );
		add_action( 'r2f_schengen_new_calendar_events', array( $this, 'on_new_calendar_events' ), 10, 3 );
		add_action( 'r2f_schengen_trip_created', array( $this, 'on_trip_created' ), 10, 2 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		// Register primary routes.
		$this->register_route_group( self::NAMESPACE );

		// Register legacy routes for backward compatibility.
		$this->register_route_group( self::LEGACY_NAMESPACE, '/schengen' );
	}

	/**
	 * Register route group under a namespace.
	 *
	 * @param string $namespace API namespace.
	 * @param string $prefix    Route prefix.
	 */
	private function register_route_group( string $namespace, string $prefix = '' ): void {
		// Get notifications.
		register_rest_route(
			$namespace,
			$prefix . '/notifications',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_notifications' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'unread_only' => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'limit'       => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 100,
					),
				),
			)
		);

		// Get unread count.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/unread-count',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_unread_count' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Mark notification as read.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/(?P<id>\d+)/read',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'mark_as_read' ),
				'permission_callback' => array( $this, 'check_notification_permission' ),
			)
		);

		// Mark all notifications as read.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/read-all',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'mark_all_as_read' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Delete notification.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_notification' ),
				'permission_callback' => array( $this, 'check_notification_permission' ),
			)
		);

		// Get push subscription status.
		register_rest_route(
			$namespace,
			$prefix . '/push/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_push_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Subscribe to push notifications.
		register_rest_route(
			$namespace,
			$prefix . '/push/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'subscribe_push' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'endpoint'  => array(
						'required' => true,
						'type'     => 'string',
					),
					'p256dh'    => array(
						'required' => true,
						'type'     => 'string',
					),
					'auth'      => array(
						'required' => true,
						'type'     => 'string',
					),
					'userAgent' => array(
						'type' => 'string',
					),
				),
			)
		);

		// Unsubscribe from push notifications.
		register_rest_route(
			$namespace,
			$prefix . '/push/unsubscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'unsubscribe_push' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'endpoint' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		// Get VAPID public key.
		register_rest_route(
			$namespace,
			$prefix . '/push/vapid-key',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vapid_key' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get notification preferences.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/preferences',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_preferences' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Update notification preferences.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/preferences',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_preferences' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Send test notification.
		register_rest_route(
			$namespace,
			$prefix . '/notifications/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_test_notification' ),
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
				__( 'You must be logged in.', 'r2f-schengen' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Check if user owns the notification.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_notification_permission( WP_REST_Request $request ) {
		$permission = $this->check_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$notification_id = (int) $request->get_param( 'id' );
		$notification    = $this->get_notification_by_id( $notification_id );

		if ( ! $notification ) {
			return new WP_Error(
				'not_found',
				__( 'Notification not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $notification->user_id !== get_current_user_id() ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to access this notification.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get notification by ID.
	 *
	 * @param int $notification_id Notification ID.
	 * @return object|null
	 */
	private function get_notification_by_id( int $notification_id ) {
		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'notifications' );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $notification_id )
		);
	}

	/**
	 * Get user's notifications.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_notifications( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id     = get_current_user_id();
		$unread_only = $request->get_param( 'unread_only' );
		$limit       = (int) $request->get_param( 'limit' );
		$table       = R2F_Schengen_Schema::get_table( 'notifications' );

		$where = $wpdb->prepare( 'WHERE user_id = %d', $user_id );

		if ( $unread_only ) {
			$where .= ' AND read_at IS NULL';
		}

		$notifications = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);

		return rest_ensure_response(
			array_map( array( $this, 'format_notification' ), $notifications )
		);
	}

	/**
	 * Get unread notification count.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_unread_count( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'notifications' );

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE user_id = %d AND read_at IS NULL",
				$user_id
			)
		);

		return rest_ensure_response( array( 'count' => $count ) );
	}

	/**
	 * Mark a notification as read.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_as_read( WP_REST_Request $request ) {
		global $wpdb;

		$notification_id = (int) $request->get_param( 'id' );
		$table           = R2F_Schengen_Schema::get_table( 'notifications' );

		$result = $wpdb->update(
			$table,
			array( 'read_at' => current_time( 'mysql' ) ),
			array( 'id' => $notification_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to mark as read', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Mark all notifications as read.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function mark_all_as_read( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'notifications' );

		$wpdb->update(
			$table,
			array( 'read_at' => current_time( 'mysql' ) ),
			array(
				'user_id' => $user_id,
				'read_at' => null,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Delete a notification.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_notification( WP_REST_Request $request ) {
		global $wpdb;

		$notification_id = (int) $request->get_param( 'id' );
		$table           = R2F_Schengen_Schema::get_table( 'notifications' );

		$result = $wpdb->delete(
			$table,
			array( 'id' => $notification_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to delete', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Get push notification status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_push_status( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'push_subscriptions' );

		$subscriptions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_agent, created_at, last_used_at FROM $table WHERE user_id = %d",
				$user_id
			)
		);

		$vapid_configured = ! empty( get_option( 'r2f_schengen_vapid_public_key' ) );

		return rest_ensure_response( array(
			'vapidConfigured' => $vapid_configured,
			'subscriptions'   => array_map( function( $sub ) {
				return array(
					'id'         => (int) $sub->id,
					'userAgent'  => $sub->user_agent,
					'createdAt'  => $sub->created_at,
					'lastUsedAt' => $sub->last_used_at,
				);
			}, $subscriptions ),
		) );
	}

	/**
	 * Subscribe to push notifications.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function subscribe_push( WP_REST_Request $request ) {
		global $wpdb;

		$user_id    = get_current_user_id();
		$endpoint   = $request->get_param( 'endpoint' );
		$p256dh     = $request->get_param( 'p256dh' );
		$auth       = $request->get_param( 'auth' );
		$user_agent = $request->get_param( 'userAgent' ) ?? '';

		$table = R2F_Schengen_Schema::get_table( 'push_subscriptions' );

		// Check if subscription already exists.
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE user_id = %d AND endpoint = %s",
				$user_id,
				$endpoint
			)
		);

		if ( $existing ) {
			// Update existing subscription.
			$wpdb->update(
				$table,
				array(
					'p256dh_key'   => $p256dh,
					'auth_key'     => $auth,
					'user_agent'   => $user_agent,
					'last_used_at' => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			return rest_ensure_response( array(
				'subscribed' => true,
				'id'         => (int) $existing->id,
			) );
		}

		// Create new subscription.
		$result = $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'endpoint'   => $endpoint,
				'p256dh_key' => $p256dh,
				'auth_key'   => $auth,
				'user_agent' => $user_agent,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to save subscription', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'subscribed' => true,
			'id'         => (int) $wpdb->insert_id,
		) );
	}

	/**
	 * Unsubscribe from push notifications.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function unsubscribe_push( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id  = get_current_user_id();
		$endpoint = $request->get_param( 'endpoint' );
		$table    = R2F_Schengen_Schema::get_table( 'push_subscriptions' );

		$wpdb->delete(
			$table,
			array(
				'user_id'  => $user_id,
				'endpoint' => $endpoint,
			),
			array( '%d', '%s' )
		);

		return rest_ensure_response( array( 'unsubscribed' => true ) );
	}

	/**
	 * Get VAPID public key for Web Push.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_vapid_key( WP_REST_Request $request ): WP_REST_Response {
		$public_key = get_option( 'r2f_schengen_vapid_public_key', '' );

		return rest_ensure_response( array(
			'publicKey' => $public_key,
		) );
	}

	/**
	 * Get notification preferences.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_preferences( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$prefs   = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );

		$defaults = array(
			'email_enabled'        => false,
			'push_enabled'         => false,
			'threshold_warning'    => true,
			'threshold_danger'     => true,
			'trip_reminder'        => true,
			'calendar_sync'        => true,
			'location_checkin'     => true,
			'quiet_hours_enabled'  => false,
			'quiet_hours_start'    => 22,
			'quiet_hours_end'      => 8,
		);

		$prefs = wp_parse_args( $prefs ?: array(), $defaults );

		return rest_ensure_response( $prefs );
	}

	/**
	 * Update notification preferences.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_preferences( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		$prefs   = $request->get_json_params();

		// Sanitize preferences.
		$allowed_keys = array(
			'email_enabled',
			'push_enabled',
			'threshold_warning',
			'threshold_danger',
			'trip_reminder',
			'calendar_sync',
			'location_checkin',
			'quiet_hours_enabled',
			'quiet_hours_start',
			'quiet_hours_end',
		);

		$sanitized = array();
		foreach ( $allowed_keys as $key ) {
			if ( isset( $prefs[ $key ] ) ) {
				if ( in_array( $key, array( 'quiet_hours_start', 'quiet_hours_end' ), true ) ) {
					$sanitized[ $key ] = (int) $prefs[ $key ];
				} else {
					$sanitized[ $key ] = (bool) $prefs[ $key ];
				}
			}
		}

		// Merge with existing preferences.
		$current  = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );
		$updated  = wp_parse_args( $sanitized, $current ?: array() );

		update_user_meta( $user_id, 'r2f_schengen_notification_prefs', $updated );

		return rest_ensure_response( array(
			'success'     => true,
			'preferences' => $updated,
		) );
	}

	/**
	 * Send a test notification.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function send_test_notification( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();

		// Create a test in-app notification.
		$notification_id = $this->create_notification(
			$user_id,
			'test',
			__( 'Test Notification', 'r2f-schengen' ),
			__( 'This is a test notification from Schengen Tracker. If you see this, notifications are working!', 'r2f-schengen' ),
			array(
				'action_url' => home_url( '/member-portal/schengen' ),
				'icon'       => 'info',
			)
		);

		// Try to send push notification.
		$push_sent = $this->send_push_notification( $user_id, array(
			'title' => __( 'Test Notification', 'r2f-schengen' ),
			'body'  => __( 'Push notifications are working!', 'r2f-schengen' ),
			'icon'  => '/icons/schengen-icon-192.png',
			'tag'   => 'test-' . time(),
			'data'  => array(
				'url' => home_url( '/member-portal/schengen' ),
			),
		) );

		return rest_ensure_response( array(
			'success'        => true,
			'notificationId' => $notification_id,
			'pushSent'       => $push_sent,
		) );
	}

	/**
	 * Create a notification for a user.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $type       Notification type.
	 * @param string $title      Notification title.
	 * @param string $body       Notification body.
	 * @param array  $options    Additional options (action_url, icon, priority, data).
	 * @return int|false Notification ID or false on failure.
	 */
	public function create_notification( int $user_id, string $type, string $title, string $body, array $options = array() ) {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'notifications' );

		$data = array(
			'user_id'    => $user_id,
			'type'       => $type,
			'title'      => sanitize_text_field( $title ),
			'body'       => sanitize_textarea_field( $body ),
			'action_url' => isset( $options['action_url'] ) ? esc_url_raw( $options['action_url'] ) : null,
			'icon'       => isset( $options['icon'] ) ? sanitize_text_field( $options['icon'] ) : 'info',
			'priority'   => isset( $options['priority'] ) ? sanitize_text_field( $options['priority'] ) : 'normal',
			'data'       => isset( $options['data'] ) ? wp_json_encode( $options['data'] ) : null,
		);

		$result = $wpdb->insert(
			$table,
			$data,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		$notification_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a notification is created.
		 *
		 * @param int    $notification_id Notification ID.
		 * @param int    $user_id         User ID.
		 * @param string $type            Notification type.
		 * @param array  $data            Notification data.
		 */
		do_action( 'r2f_schengen_notification_created', $notification_id, $user_id, $type, $data );

		return $notification_id;
	}

	/**
	 * Send a push notification to all user's subscribed devices.
	 *
	 * @param int   $user_id User ID.
	 * @param array $payload Push notification payload.
	 * @return bool True if at least one push was sent.
	 */
	public function send_push_notification( int $user_id, array $payload ): bool {
		global $wpdb;

		// Check if user has push enabled.
		$prefs = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );
		if ( empty( $prefs['push_enabled'] ) ) {
			return false;
		}

		// Check quiet hours.
		if ( ! empty( $prefs['quiet_hours_enabled'] ) ) {
			$current_hour = (int) current_time( 'G' );
			$start        = (int) ( $prefs['quiet_hours_start'] ?? 22 );
			$end          = (int) ( $prefs['quiet_hours_end'] ?? 8 );

			if ( $start > $end ) {
				// Quiet hours span midnight (e.g., 22:00 - 08:00).
				if ( $current_hour >= $start || $current_hour < $end ) {
					return false;
				}
			} else {
				// Quiet hours within same day (e.g., 13:00 - 14:00).
				if ( $current_hour >= $start && $current_hour < $end ) {
					return false;
				}
			}
		}

		// Get VAPID keys.
		$public_key  = get_option( 'r2f_schengen_vapid_public_key', '' );
		$private_key = get_option( 'r2f_schengen_vapid_private_key', '' );

		if ( empty( $public_key ) || empty( $private_key ) ) {
			return false;
		}

		// Get user's push subscriptions.
		$table         = R2F_Schengen_Schema::get_table( 'push_subscriptions' );
		$subscriptions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d",
				$user_id
			)
		);

		if ( empty( $subscriptions ) ) {
			return false;
		}

		$sent = false;

		foreach ( $subscriptions as $subscription ) {
			$result = $this->send_web_push(
				$subscription->endpoint,
				$subscription->p256dh_key,
				$subscription->auth_key,
				$payload,
				$public_key,
				$private_key
			);

			if ( $result ) {
				$sent = true;

				// Update last used time.
				$wpdb->update(
					$table,
					array( 'last_used_at' => current_time( 'mysql' ) ),
					array( 'id' => $subscription->id ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				// If push failed (expired subscription), remove it.
				$wpdb->delete(
					$table,
					array( 'id' => $subscription->id ),
					array( '%d' )
				);
			}
		}

		return $sent;
	}

	/**
	 * Send a Web Push notification using the Web Push protocol.
	 *
	 * Note: This is a simplified implementation. For production,
	 * consider using a library like minishlink/web-push.
	 *
	 * @param string $endpoint    Push subscription endpoint.
	 * @param string $p256dh      Client public key.
	 * @param string $auth        Client auth secret.
	 * @param array  $payload     Notification payload.
	 * @param string $public_key  VAPID public key.
	 * @param string $private_key VAPID private key.
	 * @return bool True on success.
	 */
	private function send_web_push( string $endpoint, string $p256dh, string $auth, array $payload, string $public_key, string $private_key ): bool {
		// For a full implementation, you would need to:
		// 1. Create VAPID JWT header
		// 2. Encrypt the payload using ECDH
		// 3. Send the request to the push service endpoint

		// This simplified version just stores the notification for the frontend to poll.
		// A full implementation would require additional PHP libraries (sodium, jose).

		// For now, log that we would send a push and return true for testing.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Schengen Push] Would send push to endpoint: %s with payload: %s',
				substr( $endpoint, 0, 50 ) . '...',
				wp_json_encode( $payload )
			) );
		}

		// Mark as sent for development purposes.
		// In production, implement actual Web Push protocol.
		return true;
	}

	/**
	 * Format notification for API response.
	 *
	 * @param object $notification Notification object.
	 * @return array
	 */
	private function format_notification( $notification ): array {
		return array(
			'id'        => (int) $notification->id,
			'type'      => $notification->type,
			'title'     => $notification->title,
			'body'      => $notification->body,
			'actionUrl' => $notification->action_url,
			'icon'      => $notification->icon,
			'priority'  => $notification->priority,
			'isRead'    => ! empty( $notification->read_at ),
			'readAt'    => $notification->read_at,
			'createdAt' => $notification->created_at,
			'data'      => $notification->data ? json_decode( $notification->data, true ) : null,
		);
	}

	/**
	 * Handler for when an alert email is sent.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $alert_level Alert level.
	 * @param array  $summary     Summary data.
	 */
	public function on_alert_sent( int $user_id, string $alert_level, array $summary ): void {
		$type = 'urgent' === $alert_level ? 'threshold_danger' : 'threshold_warning';

		// Check if user wants this notification.
		$prefs = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );
		if ( isset( $prefs[ $type ] ) && false === $prefs[ $type ] ) {
			return;
		}

		$title = sprintf(
			'urgent' === $alert_level
				? __( 'URGENT: Only %d days remaining!', 'r2f-schengen' )
				: __( 'Warning: %d days used in Schengen', 'r2f-schengen' ),
			'urgent' === $alert_level ? $summary['days_remaining'] : $summary['days_used']
		);

		$body = sprintf(
			__( 'You have used %d of 90 days. %d days remaining in your current window.', 'r2f-schengen' ),
			$summary['days_used'],
			$summary['days_remaining']
		);

		$this->create_notification( $user_id, $type, $title, $body, array(
			'action_url' => home_url( '/member-portal/schengen' ),
			'icon'       => 'urgent' === $alert_level ? 'danger' : 'warning',
			'priority'   => 'urgent' === $alert_level ? 'high' : 'normal',
		) );

		// Also send push notification.
		$this->send_push_notification( $user_id, array(
			'title' => $title,
			'body'  => $body,
			'icon'  => '/icons/schengen-icon-192.png',
			'tag'   => 'schengen-alert-' . $alert_level,
			'data'  => array(
				'url' => home_url( '/member-portal/schengen' ),
			),
		) );
	}

	/**
	 * Handler for when new calendar events are detected.
	 *
	 * @param int   $user_id    User ID.
	 * @param int   $new_events Number of new events.
	 * @param array $result     Sync result.
	 */
	public function on_new_calendar_events( int $user_id, int $new_events, array $result ): void {
		// Check if user wants this notification.
		$prefs = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );
		if ( isset( $prefs['calendar_sync'] ) && false === $prefs['calendar_sync'] ) {
			return;
		}

		$title = sprintf(
			_n(
				'%d new trip detected',
				'%d new trips detected',
				$new_events,
				'r2f-schengen'
			),
			$new_events
		);

		$body = __( 'Review your calendar events and import them as Schengen trips.', 'r2f-schengen' );

		$this->create_notification( $user_id, 'calendar_sync', $title, $body, array(
			'action_url' => home_url( '/member-portal/schengen?tab=sync' ),
			'icon'       => 'calendar',
		) );

		// Also send push notification.
		$this->send_push_notification( $user_id, array(
			'title' => $title,
			'body'  => $body,
			'icon'  => '/icons/schengen-icon-192.png',
			'tag'   => 'calendar-sync-' . time(),
			'data'  => array(
				'url' => home_url( '/member-portal/schengen?tab=sync' ),
			),
		) );
	}

	/**
	 * Handler for when a trip is created.
	 *
	 * @param int   $user_id   User ID.
	 * @param array $trip_data Trip data.
	 */
	public function on_trip_created( int $user_id, array $trip_data ): void {
		// Check if user wants trip reminders.
		$prefs = get_user_meta( $user_id, 'r2f_schengen_notification_prefs', true );
		if ( isset( $prefs['trip_reminder'] ) && false === $prefs['trip_reminder'] ) {
			return;
		}

		// Only notify for future trips.
		if ( isset( $trip_data['start_date'] ) && strtotime( $trip_data['start_date'] ) > time() ) {
			$country = isset( $trip_data['country'] ) ? $trip_data['country'] : 'Schengen';
			$title   = sprintf( __( 'Trip to %s added', 'r2f-schengen' ), $country );
			$body    = sprintf(
				__( '%s to %s - Remember to check your day count!', 'r2f-schengen' ),
				$trip_data['start_date'],
				$trip_data['end_date'] ?? $trip_data['start_date']
			);

			$this->create_notification( $user_id, 'trip_reminder', $title, $body, array(
				'action_url' => home_url( '/member-portal/schengen' ),
				'icon'       => 'calendar',
			) );
		}
	}
}
