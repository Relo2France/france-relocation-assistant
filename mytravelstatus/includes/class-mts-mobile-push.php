<?php
/**
 * Mobile Push Notifications for MyTravelStatus.
 *
 * Handles sending native push notifications to iOS (APNs) and Android (FCM).
 * Works in conjunction with MTS_Notifications for a unified notification system.
 *
 * @package MTS_Tracker
 * @since   1.6.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MTS_Mobile_Push
 *
 * Sends push notifications to registered mobile devices via APNs and FCM.
 */
class MTS_Mobile_Push {

	/**
	 * APNs production gateway.
	 *
	 * @var string
	 */
	const APNS_PRODUCTION = 'https://api.push.apple.com';

	/**
	 * APNs sandbox gateway.
	 *
	 * @var string
	 */
	const APNS_SANDBOX = 'https://api.sandbox.push.apple.com';

	/**
	 * FCM HTTP v1 API endpoint.
	 *
	 * @var string
	 */
	const FCM_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

	/**
	 * Singleton instance.
	 *
	 * @var MTS_Mobile_Push|null
	 */
	private static $instance = null;

	/**
	 * Cached APNs JWT token.
	 *
	 * @var string|null
	 */
	private $apns_jwt = null;

	/**
	 * APNs JWT token expiration.
	 *
	 * @var int
	 */
	private $apns_jwt_expires = 0;

	/**
	 * Cached FCM access token.
	 *
	 * @var string|null
	 */
	private $fcm_token = null;

	/**
	 * FCM token expiration.
	 *
	 * @var int
	 */
	private $fcm_token_expires = 0;

	/**
	 * Get singleton instance.
	 *
	 * @return MTS_Mobile_Push
	 */
	public static function get_instance(): MTS_Mobile_Push {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Listen for notification creation to send mobile pushes.
		add_action( 'mts_notification_created', array( $this, 'on_notification_created' ), 10, 4 );
	}

	/**
	 * Send push notification to a user's mobile devices.
	 *
	 * @param int   $user_id User ID.
	 * @param array $payload Push notification payload.
	 * @return array Results array with 'ios' and 'android' success counts.
	 */
	public function send_to_user( int $user_id, array $payload ): array {
		global $wpdb;

		$results = array(
			'ios_sent'     => 0,
			'ios_failed'   => 0,
			'android_sent' => 0,
			'android_failed' => 0,
		);

		// Get user's active devices.
		$table   = MTS_Schema::get_table( 'devices' );
		$devices = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND is_active = 1 AND push_token IS NOT NULL",
				$user_id
			)
		);

		if ( empty( $devices ) ) {
			return $results;
		}

		foreach ( $devices as $device ) {
			$success = false;

			if ( 'ios' === $device->platform ) {
				$success = $this->send_ios_push( $device->push_token, $payload );
				if ( $success ) {
					$results['ios_sent']++;
				} else {
					$results['ios_failed']++;
				}
			} elseif ( 'android' === $device->platform ) {
				$success = $this->send_android_push( $device->push_token, $payload );
				if ( $success ) {
					$results['android_sent']++;
				} else {
					$results['android_failed']++;
				}
			}

			// Update device last_active on successful push.
			if ( $success ) {
				$wpdb->update(
					$table,
					array( 'last_active' => current_time( 'mysql', true ) ),
					array( 'id' => $device->id ),
					array( '%s' ),
					array( '%d' )
				);
			} else {
				// If push failed, check if we should deactivate device.
				$this->maybe_deactivate_device( $device );
			}
		}

		return $results;
	}

	/**
	 * Send iOS push notification via APNs.
	 *
	 * @param string $device_token APNs device token.
	 * @param array  $payload      Notification payload.
	 * @return bool True on success.
	 */
	public function send_ios_push( string $device_token, array $payload ): bool {
		// Check if APNs is configured.
		$team_id    = get_option( 'mts_apns_team_id', '' );
		$key_id     = get_option( 'mts_apns_key_id', '' );
		$bundle_id  = get_option( 'mts_apns_bundle_id', 'com.mytravelstatus.app' );
		$is_sandbox = get_option( 'mts_apns_sandbox', '0' ) === '1';

		if ( empty( $team_id ) || empty( $key_id ) ) {
			$this->log( 'APNs not configured (missing team_id or key_id)' );
			return false;
		}

		// Get or generate JWT token.
		$jwt = $this->get_apns_jwt( $team_id, $key_id );
		if ( ! $jwt ) {
			return false;
		}

		// Build APNs payload.
		$apns_payload = array(
			'aps' => array(
				'alert' => array(
					'title' => $payload['title'] ?? 'MyTravelStatus',
					'body'  => $payload['body'] ?? '',
				),
				'sound' => $payload['sound'] ?? 'default',
				'badge' => isset( $payload['badge'] ) ? (int) $payload['badge'] : null,
			),
		);

		// Add custom data.
		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			foreach ( $payload['data'] as $key => $value ) {
				$apns_payload[ $key ] = $value;
			}
		}

		// Remove null values.
		if ( null === $apns_payload['aps']['badge'] ) {
			unset( $apns_payload['aps']['badge'] );
		}

		// Determine endpoint.
		$base_url = $is_sandbox ? self::APNS_SANDBOX : self::APNS_PRODUCTION;
		$url      = $base_url . '/3/device/' . $device_token;

		// Make request.
		$response = wp_remote_post(
			$url,
			array(
				'headers'   => array(
					'Authorization' => 'bearer ' . $jwt,
					'apns-topic'    => $bundle_id,
					'apns-push-type' => 'alert',
					'apns-priority' => '10',
					'Content-Type'  => 'application/json',
				),
				'body'      => wp_json_encode( $apns_payload ),
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'APNs request failed: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $status_code ) {
			$this->log( 'APNs push sent successfully' );
			return true;
		}

		$body = wp_remote_retrieve_body( $response );
		$this->log( sprintf( 'APNs push failed with status %d: %s', $status_code, $body ) );

		return false;
	}

	/**
	 * Send Android push notification via FCM.
	 *
	 * @param string $device_token FCM registration token.
	 * @param array  $payload      Notification payload.
	 * @return bool True on success.
	 */
	public function send_android_push( string $device_token, array $payload ): bool {
		// Check if FCM is configured.
		$project_id = get_option( 'mts_fcm_project_id', '' );

		if ( empty( $project_id ) ) {
			$this->log( 'FCM not configured (missing project_id)' );
			return false;
		}

		// Get or generate access token.
		$access_token = $this->get_fcm_access_token();
		if ( ! $access_token ) {
			return false;
		}

		// Build FCM v1 payload.
		$fcm_payload = array(
			'message' => array(
				'token' => $device_token,
				'notification' => array(
					'title' => $payload['title'] ?? 'MyTravelStatus',
					'body'  => $payload['body'] ?? '',
				),
				'android' => array(
					'priority' => 'high',
					'notification' => array(
						'channel_id' => 'travel_alerts',
						'icon'       => 'ic_notification',
						'color'      => '#3B82F6',
					),
				),
			),
		);

		// Add custom data.
		if ( isset( $payload['data'] ) && is_array( $payload['data'] ) ) {
			$fcm_payload['message']['data'] = array_map( 'strval', $payload['data'] );
		}

		// Add URL to data for navigation.
		if ( isset( $payload['url'] ) ) {
			if ( ! isset( $fcm_payload['message']['data'] ) ) {
				$fcm_payload['message']['data'] = array();
			}
			$fcm_payload['message']['data']['url'] = $payload['url'];
		}

		// Make request.
		$url = sprintf( self::FCM_ENDPOINT, $project_id );

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $fcm_payload ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'FCM request failed: ' . $response->get_error_message() );
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $status_code ) {
			$this->log( 'FCM push sent successfully' );
			return true;
		}

		$body = wp_remote_retrieve_body( $response );
		$this->log( sprintf( 'FCM push failed with status %d: %s', $status_code, $body ) );

		return false;
	}

	/**
	 * Get APNs JWT token.
	 *
	 * Generates a new token if needed, caches for reuse.
	 *
	 * @param string $team_id APNs Team ID.
	 * @param string $key_id  APNs Key ID.
	 * @return string|false JWT token or false on failure.
	 */
	private function get_apns_jwt( string $team_id, string $key_id ) {
		// Return cached token if still valid.
		if ( $this->apns_jwt && time() < $this->apns_jwt_expires ) {
			return $this->apns_jwt;
		}

		// Get private key from file or option.
		$private_key = $this->get_apns_private_key();
		if ( ! $private_key ) {
			$this->log( 'APNs private key not found' );
			return false;
		}

		// Generate JWT.
		$header = array(
			'alg' => 'ES256',
			'kid' => $key_id,
		);

		$claims = array(
			'iss' => $team_id,
			'iat' => time(),
		);

		$header_encoded  = $this->base64url_encode( wp_json_encode( $header ) );
		$claims_encoded  = $this->base64url_encode( wp_json_encode( $claims ) );
		$signature_input = $header_encoded . '.' . $claims_encoded;

		// Sign with ECDSA.
		$signature = '';
		$key       = openssl_pkey_get_private( $private_key );

		if ( ! $key ) {
			$this->log( 'Failed to load APNs private key' );
			return false;
		}

		if ( ! openssl_sign( $signature_input, $signature, $key, OPENSSL_ALGO_SHA256 ) ) {
			$this->log( 'Failed to sign APNs JWT' );
			return false;
		}

		// Convert DER signature to raw R+S format for ES256.
		$signature = $this->der_to_raw( $signature );

		$this->apns_jwt         = $signature_input . '.' . $this->base64url_encode( $signature );
		$this->apns_jwt_expires = time() + 3500; // Valid for ~1 hour, refresh at 58 min.

		return $this->apns_jwt;
	}

	/**
	 * Get APNs private key.
	 *
	 * @return string|false Private key content or false.
	 */
	private function get_apns_private_key() {
		// Try file path first.
		$key_path = get_option( 'mts_apns_key_path', '' );
		if ( $key_path && file_exists( $key_path ) ) {
			return file_get_contents( $key_path );
		}

		// Try stored option (encrypted).
		$stored_key = get_option( 'mts_apns_private_key', '' );
		if ( $stored_key ) {
			// In production, this should be decrypted.
			return $stored_key;
		}

		return false;
	}

	/**
	 * Get FCM access token using service account.
	 *
	 * @return string|false Access token or false on failure.
	 */
	private function get_fcm_access_token() {
		// Return cached token if still valid.
		if ( $this->fcm_token && time() < $this->fcm_token_expires ) {
			return $this->fcm_token;
		}

		// Get service account JSON.
		$service_account = $this->get_fcm_service_account();
		if ( ! $service_account ) {
			$this->log( 'FCM service account not configured' );
			return false;
		}

		$credentials = json_decode( $service_account, true );
		if ( ! $credentials || ! isset( $credentials['private_key'] ) ) {
			$this->log( 'Invalid FCM service account JSON' );
			return false;
		}

		// Generate JWT for OAuth2.
		$now = time();
		$jwt = $this->generate_google_jwt(
			$credentials['client_email'],
			$credentials['private_key'],
			$now
		);

		if ( ! $jwt ) {
			return false;
		}

		// Exchange JWT for access token.
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $jwt,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'FCM token exchange failed: ' . $response->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['access_token'] ) ) {
			$this->log( 'FCM token response missing access_token' );
			return false;
		}

		$this->fcm_token         = $body['access_token'];
		$this->fcm_token_expires = $now + ( $body['expires_in'] ?? 3600 ) - 60;

		return $this->fcm_token;
	}

	/**
	 * Get FCM service account JSON.
	 *
	 * @return string|false Service account JSON or false.
	 */
	private function get_fcm_service_account() {
		// Try file path first.
		$json_path = get_option( 'mts_fcm_service_account_path', '' );
		if ( $json_path && file_exists( $json_path ) ) {
			return file_get_contents( $json_path );
		}

		// Try stored option.
		return get_option( 'mts_fcm_service_account', '' );
	}

	/**
	 * Generate Google OAuth2 JWT.
	 *
	 * @param string $email      Service account email.
	 * @param string $private_key RSA private key.
	 * @param int    $now        Current timestamp.
	 * @return string|false JWT or false on failure.
	 */
	private function generate_google_jwt( string $email, string $private_key, int $now ) {
		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT',
		);

		$claims = array(
			'iss'   => $email,
			'sub'   => $email,
			'aud'   => 'https://oauth2.googleapis.com/token',
			'iat'   => $now,
			'exp'   => $now + 3600,
			'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
		);

		$header_encoded = $this->base64url_encode( wp_json_encode( $header ) );
		$claims_encoded = $this->base64url_encode( wp_json_encode( $claims ) );
		$signature_input = $header_encoded . '.' . $claims_encoded;

		$signature = '';
		$key = openssl_pkey_get_private( $private_key );

		if ( ! $key ) {
			$this->log( 'Failed to load FCM private key' );
			return false;
		}

		if ( ! openssl_sign( $signature_input, $signature, $key, OPENSSL_ALGO_SHA256 ) ) {
			$this->log( 'Failed to sign FCM JWT' );
			return false;
		}

		return $signature_input . '.' . $this->base64url_encode( $signature );
	}

	/**
	 * Handle notification creation - send mobile push.
	 *
	 * @param int    $notification_id Notification ID.
	 * @param int    $user_id         User ID.
	 * @param string $type            Notification type.
	 * @param array  $data            Notification data.
	 */
	public function on_notification_created( int $notification_id, int $user_id, string $type, array $data ): void {
		// Skip test notifications (they're handled separately).
		if ( 'test' === $type ) {
			return;
		}

		// Check if user has mobile push enabled.
		$prefs = get_user_meta( $user_id, 'mts_notification_prefs', true );
		if ( isset( $prefs['mobile_push_enabled'] ) && false === $prefs['mobile_push_enabled'] ) {
			return;
		}

		// Check notification type preference.
		if ( isset( $prefs[ $type ] ) && false === $prefs[ $type ] ) {
			return;
		}

		// Build mobile push payload.
		$payload = array(
			'title' => $data['title'] ?? 'MyTravelStatus',
			'body'  => $data['body'] ?? '',
			'data'  => array(
				'notification_id' => (string) $notification_id,
				'type'            => $type,
			),
		);

		// Add URL if available.
		if ( ! empty( $data['action_url'] ) ) {
			$payload['data']['url'] = $data['action_url'];
		}

		// Send to mobile devices.
		$this->send_to_user( $user_id, $payload );
	}

	/**
	 * Maybe deactivate a device after push failure.
	 *
	 * @param object $device Device row.
	 */
	private function maybe_deactivate_device( $device ): void {
		global $wpdb;

		// For now, we don't auto-deactivate. Could implement retry count later.
		// Just log the failure for monitoring.
		$this->log( sprintf(
			'Push failed for device %s (platform: %s)',
			substr( $device->device_id, 0, 10 ) . '...',
			$device->platform
		) );
	}

	/**
	 * Base64 URL encode.
	 *
	 * @param string $data Data to encode.
	 * @return string Encoded data.
	 */
	private function base64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Convert DER signature to raw R+S format.
	 *
	 * Required for ES256 (ECDSA with P-256).
	 *
	 * @param string $der DER-encoded signature.
	 * @return string Raw signature.
	 */
	private function der_to_raw( string $der ): string {
		$pos = 0;
		$size = strlen( $der );

		if ( $size < 2 || $der[0] !== "\x30" ) {
			return $der; // Not a valid DER sequence.
		}

		$pos++;
		$len = ord( $der[ $pos ] );
		$pos++;

		if ( $len & 0x80 ) {
			$pos += $len & 0x7F;
		}

		$components = array();

		for ( $i = 0; $i < 2 && $pos < $size; $i++ ) {
			if ( $der[ $pos ] !== "\x02" ) {
				break;
			}
			$pos++;

			$len = ord( $der[ $pos ] );
			$pos++;

			$component = substr( $der, $pos, $len );
			$pos += $len;

			// Remove leading zero if present (for positive integers).
			if ( strlen( $component ) === 33 && $component[0] === "\x00" ) {
				$component = substr( $component, 1 );
			}

			// Pad to 32 bytes if needed.
			while ( strlen( $component ) < 32 ) {
				$component = "\x00" . $component;
			}

			$components[] = $component;
		}

		if ( count( $components ) !== 2 ) {
			return $der;
		}

		return $components[0] . $components[1];
	}

	/**
	 * Log message for debugging.
	 *
	 * @param string $message Message to log.
	 */
	private function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MTS Mobile Push] ' . $message );
		}
	}

	/**
	 * Send a test push notification to user's devices.
	 *
	 * @param int $user_id User ID.
	 * @return array Results.
	 */
	public function send_test( int $user_id ): array {
		return $this->send_to_user( $user_id, array(
			'title' => __( 'Test Notification', 'mytravelstatus' ),
			'body'  => __( 'Mobile push notifications are working!', 'mytravelstatus' ),
			'data'  => array(
				'type' => 'test',
				'url'  => home_url( '/portal/schengen' ),
			),
		) );
	}

	/**
	 * Check if mobile push is configured.
	 *
	 * @return array Status for each platform.
	 */
	public function get_configuration_status(): array {
		return array(
			'ios' => array(
				'configured' => ! empty( get_option( 'mts_apns_team_id' ) ) &&
				                ! empty( get_option( 'mts_apns_key_id' ) ) &&
				                (bool) $this->get_apns_private_key(),
				'sandbox'    => get_option( 'mts_apns_sandbox', '0' ) === '1',
			),
			'android' => array(
				'configured' => ! empty( get_option( 'mts_fcm_project_id' ) ) &&
				                (bool) $this->get_fcm_service_account(),
			),
		);
	}
}
