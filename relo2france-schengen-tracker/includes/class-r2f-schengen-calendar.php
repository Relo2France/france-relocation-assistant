<?php
/**
 * Calendar Sync for Schengen Tracker.
 *
 * Handles OAuth connections to Google Calendar and Microsoft Outlook,
 * syncs events, and detects travel-related entries.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_Calendar
 *
 * Manages calendar provider connections and event syncing.
 */
class R2F_Schengen_Calendar {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'r2f-schengen/v1';

	/**
	 * Cron hook name for background sync.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'r2f_schengen_calendar_sync';

	/**
	 * Legacy namespace for backward compatibility.
	 *
	 * @var string
	 */
	const LEGACY_NAMESPACE = 'fra-portal/v1';

	/**
	 * Supported calendar providers.
	 *
	 * @var array
	 */
	const PROVIDERS = array(
		'google' => array(
			'name'       => 'Google Calendar',
			'auth_url'   => 'https://accounts.google.com/o/oauth2/v2/auth',
			'token_url'  => 'https://oauth2.googleapis.com/token',
			'api_url'    => 'https://www.googleapis.com/calendar/v3',
			'scopes'     => array( 'https://www.googleapis.com/auth/calendar.readonly' ),
		),
		'microsoft' => array(
			'name'       => 'Microsoft Outlook',
			'auth_url'   => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
			'token_url'  => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
			'api_url'    => 'https://graph.microsoft.com/v1.0',
			'scopes'     => array( 'Calendars.Read', 'offline_access' ),
		),
	);

	/**
	 * Travel keywords for event detection.
	 *
	 * @var array
	 */
	const TRAVEL_KEYWORDS = array(
		// Transport.
		'flight',
		'fly',
		'flying',
		'airport',
		'airline',
		'train',
		'eurostar',
		'thalys',
		'tgv',
		// Accommodation.
		'hotel',
		'airbnb',
		'hostel',
		'apartment',
		'booking',
		'reservation',
		// Travel.
		'trip',
		'travel',
		'vacation',
		'holiday',
		'visit',
		'visiting',
		// Schengen country names.
		'austria',
		'belgium',
		'bulgaria',
		'croatia',
		'czech',
		'denmark',
		'estonia',
		'finland',
		'france',
		'germany',
		'greece',
		'hungary',
		'iceland',
		'italy',
		'latvia',
		'liechtenstein',
		'lithuania',
		'luxembourg',
		'malta',
		'netherlands',
		'norway',
		'poland',
		'portugal',
		'romania',
		'slovakia',
		'slovenia',
		'spain',
		'sweden',
		'switzerland',
		// Major cities.
		'paris',
		'berlin',
		'rome',
		'madrid',
		'barcelona',
		'amsterdam',
		'vienna',
		'prague',
		'lisbon',
		'brussels',
		'munich',
		'milan',
		'frankfurt',
		'zurich',
		'geneva',
		'nice',
		'lyon',
		'marseille',
		'copenhagen',
		'stockholm',
		'oslo',
		'helsinki',
		'warsaw',
		'budapest',
		'athens',
	);

	/**
	 * Country name to code mapping.
	 *
	 * @var array
	 */
	const COUNTRY_CODES = array(
		'austria'       => 'AT',
		'belgium'       => 'BE',
		'bulgaria'      => 'BG',
		'croatia'       => 'HR',
		'czech'         => 'CZ',
		'czech republic' => 'CZ',
		'denmark'       => 'DK',
		'estonia'       => 'EE',
		'finland'       => 'FI',
		'france'        => 'FR',
		'germany'       => 'DE',
		'greece'        => 'GR',
		'hungary'       => 'HU',
		'iceland'       => 'IS',
		'italy'         => 'IT',
		'latvia'        => 'LV',
		'liechtenstein' => 'LI',
		'lithuania'     => 'LT',
		'luxembourg'    => 'LU',
		'malta'         => 'MT',
		'netherlands'   => 'NL',
		'norway'        => 'NO',
		'poland'        => 'PL',
		'portugal'      => 'PT',
		'romania'       => 'RO',
		'slovakia'      => 'SK',
		'slovenia'      => 'SI',
		'spain'         => 'ES',
		'sweden'        => 'SE',
		'switzerland'   => 'CH',
	);

	/**
	 * City to country mapping.
	 *
	 * @var array
	 */
	const CITY_COUNTRIES = array(
		'paris'       => 'France',
		'nice'        => 'France',
		'lyon'        => 'France',
		'marseille'   => 'France',
		'berlin'      => 'Germany',
		'munich'      => 'Germany',
		'frankfurt'   => 'Germany',
		'rome'        => 'Italy',
		'milan'       => 'Italy',
		'venice'      => 'Italy',
		'florence'    => 'Italy',
		'madrid'      => 'Spain',
		'barcelona'   => 'Spain',
		'seville'     => 'Spain',
		'amsterdam'   => 'Netherlands',
		'vienna'      => 'Austria',
		'prague'      => 'Czech Republic',
		'lisbon'      => 'Portugal',
		'brussels'    => 'Belgium',
		'zurich'      => 'Switzerland',
		'geneva'      => 'Switzerland',
		'copenhagen'  => 'Denmark',
		'stockholm'   => 'Sweden',
		'oslo'        => 'Norway',
		'helsinki'    => 'Finland',
		'warsaw'      => 'Poland',
		'budapest'    => 'Hungary',
		'athens'      => 'Greece',
	);

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Calendar|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Calendar
	 */
	public static function get_instance(): R2F_Schengen_Calendar {
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
		add_action( 'init', array( $this, 'handle_oauth_callback' ) );

		// Register cron action for background sync.
		add_action( self::CRON_HOOK, array( $this, 'process_background_sync' ) );

		// Ensure cron is scheduled.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$this->schedule_cron();
		}
	}

	/**
	 * Schedule the background sync cron job.
	 *
	 * Runs every 6 hours to sync all active calendar connections.
	 */
	public function schedule_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cron job.
	 */
	public function unschedule_cron(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Process background sync for all active connections.
	 *
	 * Called by WordPress cron.
	 */
	public function process_background_sync(): void {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );

		// Get all active connections that haven't synced in the last 4 hours.
		$connections = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, provider FROM $table
				WHERE sync_status = %s
				AND (last_sync_at IS NULL OR last_sync_at < %s)
				ORDER BY last_sync_at ASC
				LIMIT 50",
				'active',
				gmdate( 'Y-m-d H:i:s', time() - 4 * HOUR_IN_SECONDS )
			)
		);

		if ( empty( $connections ) ) {
			return;
		}

		$synced = 0;
		$errors = 0;

		foreach ( $connections as $connection ) {
			$result = $this->sync_calendar( (int) $connection->id );

			if ( is_wp_error( $result ) ) {
				$errors++;

				// Log error for debugging.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf(
						'[Schengen Calendar Sync] Failed to sync connection %d for user %d: %s',
						$connection->id,
						$connection->user_id,
						$result->get_error_message()
					) );
				}
			} else {
				$synced++;

				// If new travel events detected, trigger action for notifications.
				if ( ! empty( $result['newEvents'] ) && $result['newEvents'] > 0 ) {
					/**
					 * Fires when new travel events are detected during background sync.
					 *
					 * @param int   $user_id    User ID.
					 * @param int   $new_events Number of new events detected.
					 * @param array $result     Full sync result.
					 */
					do_action( 'r2f_schengen_new_calendar_events', $connection->user_id, $result['newEvents'], $result );
				}
			}

			// Small delay between syncs to avoid rate limiting.
			usleep( 500000 ); // 0.5 seconds.
		}

		/**
		 * Fires after background calendar sync has completed.
		 *
		 * @param int $synced Number of connections synced successfully.
		 * @param int $errors Number of connections that failed.
		 */
		do_action( 'r2f_schengen_calendar_sync_completed', $synced, $errors );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Schengen Calendar Sync] Background sync completed: %d synced, %d errors',
				$synced,
				$errors
			) );
		}
	}

	/**
	 * Register REST API routes for calendar sync.
	 */
	public function register_routes(): void {
		// Register primary routes.
		$this->register_route_group( self::NAMESPACE );

		// Register legacy routes for backward compatibility with Member Tools.
		$this->register_route_group( self::LEGACY_NAMESPACE, '/schengen' );
	}

	/**
	 * Register route group under a namespace.
	 *
	 * @param string $namespace API namespace.
	 * @param string $prefix    Route prefix (empty for primary, '/schengen' for legacy).
	 */
	private function register_route_group( string $namespace, string $prefix = '' ): void {
		// Get available providers.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/providers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_providers' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get user's connections.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/connections',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_connections' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Start OAuth flow (returns auth URL).
		register_rest_route(
			$namespace,
			$prefix . '/calendar/connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'start_connection' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'provider' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => array_keys( self::PROVIDERS ),
					),
				),
			)
		);

		// Disconnect a provider.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/connections/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'disconnect' ),
				'permission_callback' => array( $this, 'check_connection_permission' ),
			)
		);

		// Trigger sync for a connection.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/connections/(?P<id>\d+)/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sync_connection' ),
				'permission_callback' => array( $this, 'check_connection_permission' ),
			)
		);

		// Get detected events.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/events',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_events' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status' => array(
						'type'    => 'string',
						'enum'    => array( 'pending', 'imported', 'skipped', 'all' ),
						'default' => 'pending',
					),
				),
			)
		);

		// Import events as trips.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/events/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_events' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'event_ids' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array( 'type' => 'integer' ),
					),
				),
			)
		);

		// Skip events.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/events/skip',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'skip_events' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'event_ids' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array( 'type' => 'integer' ),
					),
				),
			)
		);

		// Upload iCal file.
		register_rest_route(
			$namespace,
			$prefix . '/calendar/import-ical',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'import_ical' ),
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
	 * Check if user owns the connection.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_connection_permission( WP_REST_Request $request ) {
		$permission = $this->check_permission();
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$connection_id = (int) $request->get_param( 'id' );
		$connection    = $this->get_connection_by_id( $connection_id );

		if ( ! $connection ) {
			return new WP_Error(
				'not_found',
				__( 'Connection not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $connection->user_id !== get_current_user_id() ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to access this connection.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Get available calendar providers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_providers( WP_REST_Request $request ): WP_REST_Response {
		$providers = array();

		foreach ( self::PROVIDERS as $id => $provider ) {
			$providers[] = array(
				'id'          => $id,
				'name'        => $provider['name'],
				'isConfigured' => $this->is_provider_configured( $id ),
			);
		}

		return rest_ensure_response( $providers );
	}

	/**
	 * Check if a provider has API credentials configured.
	 *
	 * @param string $provider Provider ID.
	 * @return bool
	 */
	private function is_provider_configured( string $provider ): bool {
		if ( 'google' === $provider ) {
			return ! empty( get_option( 'r2f_schengen_google_client_id' ) );
		}

		if ( 'microsoft' === $provider ) {
			return ! empty( get_option( 'r2f_schengen_microsoft_client_id' ) );
		}

		return false;
	}

	/**
	 * Get user's calendar connections.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_connections( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'calendar_connections' );

		$connections = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);

		return rest_ensure_response(
			array_map( array( $this, 'format_connection' ), $connections )
		);
	}

	/**
	 * Get connection by ID.
	 *
	 * @param int $connection_id Connection ID.
	 * @return object|null
	 */
	private function get_connection_by_id( int $connection_id ) {
		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $connection_id )
		);
	}

	/**
	 * Start OAuth connection flow.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function start_connection( WP_REST_Request $request ) {
		$provider = $request->get_param( 'provider' );

		if ( ! $this->is_provider_configured( $provider ) ) {
			return new WP_Error(
				'provider_not_configured',
				sprintf(
					/* translators: %s: Provider name */
					__( '%s integration is not configured. Please contact the administrator.', 'r2f-schengen' ),
					self::PROVIDERS[ $provider ]['name']
				),
				array( 'status' => 400 )
			);
		}

		$auth_url = $this->get_oauth_url( $provider );

		return rest_ensure_response( array(
			'authUrl' => $auth_url,
		) );
	}

	/**
	 * Generate OAuth authorization URL.
	 *
	 * @param string $provider Provider ID.
	 * @return string
	 */
	private function get_oauth_url( string $provider ): string {
		$provider_config = self::PROVIDERS[ $provider ];
		$user_id         = get_current_user_id();

		// Generate state token for security.
		$state = wp_generate_password( 32, false );
		set_transient( "r2f_schengen_oauth_state_{$user_id}", $state, HOUR_IN_SECONDS );

		$redirect_uri = home_url( '/wp-json/r2f-schengen/v1/calendar/callback' );

		if ( 'google' === $provider ) {
			$client_id = get_option( 'r2f_schengen_google_client_id' );

			return add_query_arg(
				array(
					'client_id'     => $client_id,
					'redirect_uri'  => $redirect_uri,
					'response_type' => 'code',
					'scope'         => implode( ' ', $provider_config['scopes'] ),
					'access_type'   => 'offline',
					'prompt'        => 'consent',
					'state'         => $provider . '|' . $state . '|' . $user_id,
				),
				$provider_config['auth_url']
			);
		}

		if ( 'microsoft' === $provider ) {
			$client_id = get_option( 'r2f_schengen_microsoft_client_id' );

			return add_query_arg(
				array(
					'client_id'     => $client_id,
					'redirect_uri'  => $redirect_uri,
					'response_type' => 'code',
					'scope'         => implode( ' ', $provider_config['scopes'] ),
					'response_mode' => 'query',
					'state'         => $provider . '|' . $state . '|' . $user_id,
				),
				$provider_config['auth_url']
			);
		}

		return '';
	}

	/**
	 * Handle OAuth callback (runs on init).
	 */
	public function handle_oauth_callback(): void {
		// Check if this is an OAuth callback.
		if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
			return;
		}

		// Check if this is our callback.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( strpos( $request_uri, '/calendar/callback' ) === false ) {
			return;
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );

		// Parse state: provider|token|user_id.
		$state_parts = explode( '|', $state );
		if ( count( $state_parts ) !== 3 ) {
			$this->redirect_with_error( 'Invalid state parameter' );
			return;
		}

		list( $provider, $state_token, $user_id ) = $state_parts;
		$user_id = (int) $user_id;

		// Verify state token.
		$stored_state = get_transient( "r2f_schengen_oauth_state_{$user_id}" );
		if ( $stored_state !== $state_token ) {
			$this->redirect_with_error( 'State mismatch. Please try again.' );
			return;
		}

		// Delete state transient.
		delete_transient( "r2f_schengen_oauth_state_{$user_id}" );

		// Exchange code for tokens.
		$tokens = $this->exchange_code_for_tokens( $provider, $code );
		if ( is_wp_error( $tokens ) ) {
			$this->redirect_with_error( $tokens->get_error_message() );
			return;
		}

		// Store connection.
		$connection_id = $this->store_connection( $user_id, $provider, $tokens );
		if ( is_wp_error( $connection_id ) ) {
			$this->redirect_with_error( $connection_id->get_error_message() );
			return;
		}

		// Initial sync.
		$this->sync_calendar( $connection_id );

		// Redirect to success page.
		$portal_path  = apply_filters( 'r2f_schengen_portal_path', '/portal/' );
		$redirect_url = home_url( trailingslashit( $portal_path ) . 'schengen?calendar_connected=' . $provider );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Redirect with error message.
	 *
	 * @param string $error_message Error message.
	 */
	private function redirect_with_error( string $error_message ): void {
		$portal_path  = apply_filters( 'r2f_schengen_portal_path', '/portal/' );
		$redirect_url = add_query_arg(
			'calendar_error',
			urlencode( $error_message ),
			home_url( trailingslashit( $portal_path ) . 'schengen' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Exchange OAuth code for access and refresh tokens.
	 *
	 * @param string $provider Provider ID.
	 * @param string $code     Authorization code.
	 * @return array|WP_Error
	 */
	private function exchange_code_for_tokens( string $provider, string $code ) {
		$provider_config = self::PROVIDERS[ $provider ];
		$redirect_uri    = home_url( '/wp-json/r2f-schengen/v1/calendar/callback' );

		$body = array(
			'code'          => $code,
			'redirect_uri'  => $redirect_uri,
			'grant_type'    => 'authorization_code',
		);

		if ( 'google' === $provider ) {
			$body['client_id']     = get_option( 'r2f_schengen_google_client_id' );
			$body['client_secret'] = get_option( 'r2f_schengen_google_client_secret' );
		} elseif ( 'microsoft' === $provider ) {
			$body['client_id']     = get_option( 'r2f_schengen_microsoft_client_id' );
			$body['client_secret'] = get_option( 'r2f_schengen_microsoft_client_secret' );
		}

		$response = wp_remote_post( $provider_config['token_url'], array(
			'body'    => $body,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error(
				'oauth_error',
				$body['error_description'] ?? $body['error']
			);
		}

		if ( empty( $body['access_token'] ) ) {
			return new WP_Error( 'oauth_error', 'No access token received' );
		}

		return array(
			'access_token'  => $body['access_token'],
			'refresh_token' => $body['refresh_token'] ?? null,
			'expires_in'    => $body['expires_in'] ?? 3600,
		);
	}

	/**
	 * Store calendar connection.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $provider Provider ID.
	 * @param array  $tokens   OAuth tokens.
	 * @return int|WP_Error Connection ID or error.
	 */
	private function store_connection( int $user_id, string $provider, array $tokens ) {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );

		// Check if connection already exists.
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM $table WHERE user_id = %d AND provider = %s",
				$user_id,
				$provider
			)
		);

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + (int) $tokens['expires_in'] );

		$data = array(
			'user_id'          => $user_id,
			'provider'         => $provider,
			'access_token'     => $this->encrypt_token( $tokens['access_token'] ),
			'refresh_token'    => $tokens['refresh_token'] ? $this->encrypt_token( $tokens['refresh_token'] ) : null,
			'token_expires_at' => $expires_at,
			'sync_status'      => 'active',
		);

		if ( $existing ) {
			// Update existing connection.
			$result = $wpdb->update(
				$table,
				$data,
				array( 'id' => $existing->id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			return $result !== false ? (int) $existing->id : new WP_Error( 'db_error', 'Failed to update connection' );
		}

		// Insert new connection.
		$result = $wpdb->insert(
			$table,
			$data,
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result !== false ? (int) $wpdb->insert_id : new WP_Error( 'db_error', 'Failed to create connection' );
	}

	/**
	 * Encrypt token for storage.
	 *
	 * @param string $token Token to encrypt.
	 * @return string
	 */
	private function encrypt_token( string $token ): string {
		$key = wp_salt( 'auth' );
		return base64_encode( openssl_encrypt( $token, 'AES-256-CBC', $key, 0, substr( md5( $key ), 0, 16 ) ) );
	}

	/**
	 * Decrypt stored token.
	 *
	 * @param string $encrypted Encrypted token.
	 * @return string
	 */
	private function decrypt_token( string $encrypted ): string {
		$key = wp_salt( 'auth' );
		return openssl_decrypt( base64_decode( $encrypted ), 'AES-256-CBC', $key, 0, substr( md5( $key ), 0, 16 ) );
	}

	/**
	 * Disconnect a calendar provider.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function disconnect( WP_REST_Request $request ) {
		global $wpdb;

		$connection_id = (int) $request->get_param( 'id' );

		// Delete associated events.
		$events_table = R2F_Schengen_Schema::get_table( 'calendar_events' );
		$wpdb->delete( $events_table, array( 'connection_id' => $connection_id ), array( '%d' ) );

		// Delete connection.
		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );
		$result = $wpdb->delete( $table, array( 'id' => $connection_id ), array( '%d' ) );

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to disconnect', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'disconnected' => true,
			'id'           => $connection_id,
		) );
	}

	/**
	 * Sync a calendar connection.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function sync_connection( WP_REST_Request $request ) {
		$connection_id = (int) $request->get_param( 'id' );

		$result = $this->sync_calendar( $connection_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Sync calendar events for a connection.
	 *
	 * @param int $connection_id Connection ID.
	 * @return array|WP_Error
	 */
	public function sync_calendar( int $connection_id ) {
		global $wpdb;

		$connection = $this->get_connection_by_id( $connection_id );
		if ( ! $connection ) {
			return new WP_Error( 'not_found', 'Connection not found' );
		}

		// Refresh token if needed.
		$access_token = $this->get_valid_access_token( $connection );
		if ( is_wp_error( $access_token ) ) {
			// Mark connection as expired.
			$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );
			$wpdb->update(
				$table,
				array( 'sync_status' => 'expired' ),
				array( 'id' => $connection_id ),
				array( '%s' ),
				array( '%d' )
			);
			return $access_token;
		}

		// Fetch events.
		$events = $this->fetch_calendar_events( $connection->provider, $access_token );
		if ( is_wp_error( $events ) ) {
			return $events;
		}

		// Process and store events.
		$processed = $this->process_events( $connection, $events );

		// Update last sync time.
		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );
		$wpdb->update(
			$table,
			array(
				'last_sync_at' => current_time( 'mysql' ),
				'sync_status'  => 'active',
			),
			array( 'id' => $connection_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return array(
			'synced'         => true,
			'eventsFound'    => count( $events ),
			'travelDetected' => $processed['detected'],
			'newEvents'      => $processed['new'],
		);
	}

	/**
	 * Get a valid access token, refreshing if needed.
	 *
	 * @param object $connection Connection object.
	 * @return string|WP_Error
	 */
	private function get_valid_access_token( $connection ) {
		$expires_at = strtotime( $connection->token_expires_at );

		// If token is still valid, return it.
		if ( $expires_at > time() + 300 ) {
			return $this->decrypt_token( $connection->access_token );
		}

		// Refresh token.
		if ( empty( $connection->refresh_token ) ) {
			return new WP_Error( 'no_refresh_token', 'Session expired. Please reconnect.' );
		}

		$tokens = $this->refresh_access_token( $connection->provider, $this->decrypt_token( $connection->refresh_token ) );
		if ( is_wp_error( $tokens ) ) {
			return $tokens;
		}

		// Update stored tokens.
		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'calendar_connections' );
		$wpdb->update(
			$table,
			array(
				'access_token'     => $this->encrypt_token( $tokens['access_token'] ),
				'token_expires_at' => gmdate( 'Y-m-d H:i:s', time() + (int) $tokens['expires_in'] ),
			),
			array( 'id' => $connection->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return $tokens['access_token'];
	}

	/**
	 * Refresh access token.
	 *
	 * @param string $provider      Provider ID.
	 * @param string $refresh_token Refresh token.
	 * @return array|WP_Error
	 */
	private function refresh_access_token( string $provider, string $refresh_token ) {
		$provider_config = self::PROVIDERS[ $provider ];

		$body = array(
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		);

		if ( 'google' === $provider ) {
			$body['client_id']     = get_option( 'r2f_schengen_google_client_id' );
			$body['client_secret'] = get_option( 'r2f_schengen_google_client_secret' );
		} elseif ( 'microsoft' === $provider ) {
			$body['client_id']     = get_option( 'r2f_schengen_microsoft_client_id' );
			$body['client_secret'] = get_option( 'r2f_schengen_microsoft_client_secret' );
		}

		$response = wp_remote_post( $provider_config['token_url'], array(
			'body'    => $body,
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'refresh_error', $body['error_description'] ?? 'Token refresh failed' );
		}

		return array(
			'access_token' => $body['access_token'],
			'expires_in'   => $body['expires_in'] ?? 3600,
		);
	}

	/**
	 * Fetch calendar events from provider.
	 *
	 * @param string $provider     Provider ID.
	 * @param string $access_token Access token.
	 * @return array|WP_Error
	 */
	private function fetch_calendar_events( string $provider, string $access_token ) {
		$provider_config = self::PROVIDERS[ $provider ];

		// Get events for next 6 months.
		$now    = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$future = ( clone $now )->modify( '+6 months' );

		if ( 'google' === $provider ) {
			return $this->fetch_google_events( $access_token, $now, $future );
		}

		if ( 'microsoft' === $provider ) {
			return $this->fetch_microsoft_events( $access_token, $now, $future );
		}

		return new WP_Error( 'unsupported_provider', 'Provider not supported' );
	}

	/**
	 * Fetch events from Google Calendar.
	 *
	 * @param string   $access_token Access token.
	 * @param DateTime $start        Start date.
	 * @param DateTime $end          End date.
	 * @return array|WP_Error
	 */
	private function fetch_google_events( string $access_token, DateTime $start, DateTime $end ) {
		$url = add_query_arg(
			array(
				'timeMin'      => $start->format( 'c' ),
				'timeMax'      => $end->format( 'c' ),
				'singleEvents' => 'true',
				'orderBy'      => 'startTime',
				'maxResults'   => 250,
			),
			'https://www.googleapis.com/calendar/v3/calendars/primary/events'
		);

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'google_api_error', $body['error']['message'] ?? 'Failed to fetch events' );
		}

		// Normalize events.
		$events = array();
		foreach ( ( $body['items'] ?? array() ) as $item ) {
			$events[] = array(
				'external_id' => $item['id'],
				'title'       => $item['summary'] ?? '',
				'location'    => $item['location'] ?? '',
				'start_date'  => $this->parse_google_date( $item['start'] ?? array() ),
				'end_date'    => $this->parse_google_date( $item['end'] ?? array() ),
				'raw_data'    => $item,
			);
		}

		return $events;
	}

	/**
	 * Parse Google date format.
	 *
	 * @param array $date Google date object.
	 * @return string
	 */
	private function parse_google_date( array $date ): string {
		if ( isset( $date['date'] ) ) {
			return $date['date'];
		}

		if ( isset( $date['dateTime'] ) ) {
			$dt = new DateTime( $date['dateTime'] );
			return $dt->format( 'Y-m-d' );
		}

		return '';
	}

	/**
	 * Fetch events from Microsoft Outlook.
	 *
	 * @param string   $access_token Access token.
	 * @param DateTime $start        Start date.
	 * @param DateTime $end          End date.
	 * @return array|WP_Error
	 */
	private function fetch_microsoft_events( string $access_token, DateTime $start, DateTime $end ) {
		$url = add_query_arg(
			array(
				'startDateTime' => $start->format( 'c' ),
				'endDateTime'   => $end->format( 'c' ),
				'$top'          => 250,
				'$orderby'      => 'start/dateTime',
			),
			'https://graph.microsoft.com/v1.0/me/calendar/calendarView'
		);

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'microsoft_api_error', $body['error']['message'] ?? 'Failed to fetch events' );
		}

		// Normalize events.
		$events = array();
		foreach ( ( $body['value'] ?? array() ) as $item ) {
			$events[] = array(
				'external_id' => $item['id'],
				'title'       => $item['subject'] ?? '',
				'location'    => $item['location']['displayName'] ?? '',
				'start_date'  => substr( $item['start']['dateTime'] ?? '', 0, 10 ),
				'end_date'    => substr( $item['end']['dateTime'] ?? '', 0, 10 ),
				'raw_data'    => $item,
			);
		}

		return $events;
	}

	/**
	 * Process and store fetched events.
	 *
	 * @param object $connection Connection object.
	 * @param array  $events     Raw events.
	 * @return array
	 */
	private function process_events( $connection, array $events ): array {
		global $wpdb;

		$table        = R2F_Schengen_Schema::get_table( 'calendar_events' );
		$detected     = 0;
		$new_events   = 0;

		foreach ( $events as $event ) {
			// Skip if dates are invalid.
			if ( empty( $event['start_date'] ) || empty( $event['end_date'] ) ) {
				continue;
			}

			// Check if event already exists.
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, status FROM $table WHERE connection_id = %d AND external_event_id = %s",
					$connection->id,
					$event['external_id']
				)
			);

			// Skip if already processed.
			if ( $existing && in_array( $existing->status, array( 'imported', 'skipped' ), true ) ) {
				continue;
			}

			// Detect travel.
			$travel_info = $this->detect_travel( $event );

			if ( $travel_info['is_travel'] ) {
				$detected++;

				$data = array(
					'user_id'               => $connection->user_id,
					'connection_id'         => $connection->id,
					'external_event_id'     => $event['external_id'],
					'title'                 => sanitize_text_field( $event['title'] ),
					'start_date'            => $event['start_date'],
					'end_date'              => $event['end_date'],
					'location'              => sanitize_text_field( $event['location'] ),
					'detected_country'      => $travel_info['country'],
					'detected_country_code' => $travel_info['country_code'],
					'is_schengen'           => $travel_info['is_schengen'] ? 1 : 0,
					'confidence_score'      => $travel_info['confidence'],
					'status'                => 'pending',
					'raw_data'              => wp_json_encode( $event['raw_data'] ),
				);

				if ( $existing ) {
					$wpdb->update( $table, $data, array( 'id' => $existing->id ) );
				} else {
					$wpdb->insert( $table, $data );
					$new_events++;
				}
			}
		}

		return array(
			'detected' => $detected,
			'new'      => $new_events,
		);
	}

	/**
	 * Detect if an event is travel-related and extract location.
	 *
	 * @param array $event Event data.
	 * @return array
	 */
	private function detect_travel( array $event ): array {
		$title    = strtolower( $event['title'] ?? '' );
		$location = strtolower( $event['location'] ?? '' );
		$combined = $title . ' ' . $location;

		$result = array(
			'is_travel'    => false,
			'country'      => null,
			'country_code' => null,
			'is_schengen'  => false,
			'confidence'   => 0.0,
		);

		// Check for country names.
		foreach ( self::COUNTRY_CODES as $country_name => $code ) {
			if ( strpos( $combined, $country_name ) !== false ) {
				$result['is_travel']    = true;
				$result['country']      = ucfirst( $country_name );
				$result['country_code'] = $code;
				$result['is_schengen']  = R2F_Schengen_Schema::is_schengen_country( $code );
				$result['confidence']   = 0.9;
				return $result;
			}
		}

		// Check for city names.
		foreach ( self::CITY_COUNTRIES as $city => $country ) {
			if ( strpos( $combined, $city ) !== false ) {
				$country_lower = strtolower( $country );
				$result['is_travel']    = true;
				$result['country']      = $country;
				$result['country_code'] = self::COUNTRY_CODES[ $country_lower ] ?? null;
				$result['is_schengen']  = R2F_Schengen_Schema::is_schengen_country( $country );
				$result['confidence']   = 0.85;
				return $result;
			}
		}

		// Check for travel keywords (lower confidence without specific location).
		foreach ( self::TRAVEL_KEYWORDS as $keyword ) {
			if ( strpos( $combined, $keyword ) !== false ) {
				$result['is_travel']  = true;
				$result['confidence'] = 0.5;
				break;
			}
		}

		return $result;
	}

	/**
	 * Get detected calendar events.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_events( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$status  = $request->get_param( 'status' );
		$table   = R2F_Schengen_Schema::get_table( 'calendar_events' );

		$where = $wpdb->prepare( 'WHERE user_id = %d', $user_id );

		if ( 'all' !== $status ) {
			$where .= $wpdb->prepare( ' AND status = %s', $status );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$events = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY start_date ASC" );

		return rest_ensure_response(
			array_map( array( $this, 'format_event' ), $events )
		);
	}

	/**
	 * Import calendar events as trips.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_events( WP_REST_Request $request ) {
		global $wpdb;

		$event_ids = $request->get_param( 'event_ids' );
		$user_id   = get_current_user_id();

		$events_table = R2F_Schengen_Schema::get_table( 'calendar_events' );
		$trips_table  = R2F_Schengen_Schema::get_table( 'trips' );

		$imported = 0;
		$skipped  = 0;

		foreach ( $event_ids as $event_id ) {
			$event = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $events_table WHERE id = %d AND user_id = %d",
					$event_id,
					$user_id
				)
			);

			if ( ! $event ) {
				$skipped++;
				continue;
			}

			// Skip if no country detected.
			if ( empty( $event->detected_country ) || ! $event->is_schengen ) {
				$skipped++;
				continue;
			}

			// Create trip.
			$result = $wpdb->insert(
				$trips_table,
				array(
					'user_id'    => $user_id,
					'start_date' => $event->start_date,
					'end_date'   => $event->end_date,
					'country'    => $event->detected_country,
					'category'   => 'personal',
					'notes'      => sprintf( 'Imported from calendar: %s', $event->title ),
					'location_source' => 'calendar',
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( $result ) {
				$trip_id = $wpdb->insert_id;

				// Update event status.
				$wpdb->update(
					$events_table,
					array(
						'status'             => 'imported',
						'imported_as_trip_id' => $trip_id,
					),
					array( 'id' => $event_id ),
					array( '%s', '%d' ),
					array( '%d' )
				);

				$imported++;

				/**
				 * Fires after a trip is created from calendar.
				 *
				 * @param int $user_id User ID.
				 * @param int $trip_id Trip ID.
				 * @param int $event_id Calendar event ID.
				 */
				do_action( 'r2f_schengen_trip_imported_from_calendar', $user_id, $trip_id, $event_id );
			} else {
				$skipped++;
			}
		}

		return rest_ensure_response( array(
			'imported' => $imported,
			'skipped'  => $skipped,
		) );
	}

	/**
	 * Skip calendar events.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function skip_events( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$event_ids = $request->get_param( 'event_ids' );
		$user_id   = get_current_user_id();

		$table   = R2F_Schengen_Schema::get_table( 'calendar_events' );
		$skipped = 0;

		foreach ( $event_ids as $event_id ) {
			$result = $wpdb->update(
				$table,
				array( 'status' => 'skipped' ),
				array(
					'id'      => $event_id,
					'user_id' => $user_id,
				),
				array( '%s' ),
				array( '%d', '%d' )
			);

			if ( $result ) {
				$skipped++;
			}
		}

		return rest_ensure_response( array( 'skipped' => $skipped ) );
	}

	/**
	 * Import iCal file.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_ical( WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'no_file', 'No file uploaded', array( 'status' => 400 ) );
		}

		$file = $files['file'];

		// Validate file type.
		$allowed_types = array( 'text/calendar', 'application/ics' );
		if ( ! in_array( $file['type'], $allowed_types, true ) && ! preg_match( '/\.ics$/i', $file['name'] ) ) {
			return new WP_Error( 'invalid_file', 'Please upload a valid iCal (.ics) file', array( 'status' => 400 ) );
		}

		// Read file content.
		$content = file_get_contents( $file['tmp_name'] );
		if ( false === $content ) {
			return new WP_Error( 'read_error', 'Failed to read file', array( 'status' => 500 ) );
		}

		// Parse iCal.
		$events = $this->parse_ical( $content );
		if ( is_wp_error( $events ) ) {
			return $events;
		}

		// Process and store events (use a pseudo-connection for iCal imports).
		$user_id  = get_current_user_id();
		$detected = 0;

		global $wpdb;
		$table = R2F_Schengen_Schema::get_table( 'calendar_events' );

		foreach ( $events as $event ) {
			$travel_info = $this->detect_travel( $event );

			if ( $travel_info['is_travel'] ) {
				$wpdb->insert(
					$table,
					array(
						'user_id'               => $user_id,
						'connection_id'         => 0, // 0 indicates iCal import.
						'external_event_id'     => $event['external_id'] ?? md5( $event['title'] . $event['start_date'] ),
						'title'                 => sanitize_text_field( $event['title'] ),
						'start_date'            => $event['start_date'],
						'end_date'              => $event['end_date'],
						'location'              => sanitize_text_field( $event['location'] ?? '' ),
						'detected_country'      => $travel_info['country'],
						'detected_country_code' => $travel_info['country_code'],
						'is_schengen'           => $travel_info['is_schengen'] ? 1 : 0,
						'confidence_score'      => $travel_info['confidence'],
						'status'                => 'pending',
					)
				);

				$detected++;
			}
		}

		return rest_ensure_response( array(
			'parsed'   => count( $events ),
			'detected' => $detected,
		) );
	}

	/**
	 * Parse iCal content.
	 *
	 * @param string $content iCal content.
	 * @return array|WP_Error
	 */
	private function parse_ical( string $content ) {
		$events = array();
		$lines  = preg_split( '/\r?\n/', $content );

		$in_event    = false;
		$event_data  = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( 'BEGIN:VEVENT' === $line ) {
				$in_event   = true;
				$event_data = array();
				continue;
			}

			if ( 'END:VEVENT' === $line ) {
				$in_event = false;

				if ( ! empty( $event_data['start_date'] ) ) {
					$events[] = array(
						'external_id' => $event_data['uid'] ?? '',
						'title'       => $event_data['summary'] ?? '',
						'location'    => $event_data['location'] ?? '',
						'start_date'  => $event_data['start_date'],
						'end_date'    => $event_data['end_date'] ?? $event_data['start_date'],
						'raw_data'    => $event_data,
					);
				}

				continue;
			}

			if ( ! $in_event ) {
				continue;
			}

			// Parse property.
			if ( strpos( $line, ':' ) === false ) {
				continue;
			}

			list( $property, $value ) = explode( ':', $line, 2 );

			// Handle properties with parameters (e.g., DTSTART;VALUE=DATE:20250115).
			$property_parts = explode( ';', $property );
			$property_name  = $property_parts[0];

			switch ( $property_name ) {
				case 'UID':
					$event_data['uid'] = $value;
					break;

				case 'SUMMARY':
					$event_data['summary'] = $this->decode_ical_text( $value );
					break;

				case 'LOCATION':
					$event_data['location'] = $this->decode_ical_text( $value );
					break;

				case 'DTSTART':
					$event_data['start_date'] = $this->parse_ical_date( $value );
					break;

				case 'DTEND':
					$event_data['end_date'] = $this->parse_ical_date( $value );
					break;
			}
		}

		return $events;
	}

	/**
	 * Decode iCal text escaping.
	 *
	 * @param string $text Encoded text.
	 * @return string
	 */
	private function decode_ical_text( string $text ): string {
		$text = str_replace( '\\n', "\n", $text );
		$text = str_replace( '\\,', ',', $text );
		$text = str_replace( '\\;', ';', $text );
		$text = str_replace( '\\\\', '\\', $text );
		return $text;
	}

	/**
	 * Parse iCal date.
	 *
	 * @param string $date iCal date string.
	 * @return string
	 */
	private function parse_ical_date( string $date ): string {
		// Handle YYYYMMDD format.
		if ( preg_match( '/^(\d{4})(\d{2})(\d{2})/', $date, $matches ) ) {
			return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
		}

		return '';
	}

	/**
	 * Format connection for API response.
	 *
	 * @param object $connection Connection object.
	 * @return array
	 */
	private function format_connection( $connection ): array {
		return array(
			'id'           => (int) $connection->id,
			'provider'     => $connection->provider,
			'providerName' => self::PROVIDERS[ $connection->provider ]['name'] ?? $connection->provider,
			'calendarName' => $connection->calendar_name,
			'syncStatus'   => $connection->sync_status,
			'lastSyncAt'   => $connection->last_sync_at,
			'createdAt'    => $connection->created_at,
		);
	}

	/**
	 * Format event for API response.
	 *
	 * @param object $event Event object.
	 * @return array
	 */
	private function format_event( $event ): array {
		return array(
			'id'              => (int) $event->id,
			'title'           => $event->title,
			'startDate'       => $event->start_date,
			'endDate'         => $event->end_date,
			'location'        => $event->location,
			'detectedCountry' => $event->detected_country,
			'countryCode'     => $event->detected_country_code,
			'isSchengen'      => (bool) $event->is_schengen,
			'status'          => $event->status,
			'confidence'      => (float) $event->confidence_score,
			'importedAsTripId' => $event->imported_as_trip_id ? (int) $event->imported_as_trip_id : null,
			'createdAt'       => $event->created_at,
		);
	}
}
