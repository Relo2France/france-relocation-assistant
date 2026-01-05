<?php
/**
 * Core plugin class for MyTravelStatus.
 *
 * @package MTS_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core singleton class that initializes all plugin components.
 */
class MTS_Core {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @var MTS_Core
	 */
	private static $instance = null;

	/**
	 * Premium gating handler.
	 *
	 * @var MTS_Premium
	 */
	public $premium;

	/**
	 * REST API handler.
	 *
	 * @var MTS_API
	 */
	public $api;

	/**
	 * Alerts handler.
	 *
	 * @var MTS_Alerts
	 */
	public $alerts;

	/**
	 * Location handler.
	 *
	 * @var MTS_Location
	 */
	public $location;

	/**
	 * Calendar sync handler.
	 *
	 * @var MTS_Calendar
	 */
	public $calendar;

	/**
	 * Jurisdiction rules handler.
	 *
	 * @var MTS_Jurisdiction
	 */
	public $jurisdiction;

	/**
	 * Notifications handler.
	 *
	 * @var MTS_Notifications
	 */
	public $notifications;

	/**
	 * Family members handler.
	 *
	 * @var MTS_Family
	 */
	public $family;

	/**
	 * Mobile API handler.
	 *
	 * @var MTS_Mobile_API
	 */
	public $mobile_api;

	/**
	 * Mobile push notifications handler.
	 *
	 * @var MTS_Mobile_Push
	 */
	public $mobile_push;

	/**
	 * Get singleton instance.
	 *
	 * @return MTS_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		$includes_dir = MTS_PLUGIN_DIR . 'includes/';

		require_once $includes_dir . 'class-mts-schema.php';
		require_once $includes_dir . 'class-mts-premium.php';
		require_once $includes_dir . 'class-mts-api.php';
		require_once $includes_dir . 'class-mts-alerts.php';
		require_once $includes_dir . 'class-mts-location.php';
		require_once $includes_dir . 'class-mts-calendar.php';
		require_once $includes_dir . 'class-mts-jurisdiction.php';
		require_once $includes_dir . 'class-mts-notifications.php';
		require_once $includes_dir . 'class-mts-family.php';
		require_once $includes_dir . 'class-mts-mobile-api.php';
		require_once $includes_dir . 'class-mts-mobile-push.php';
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		$this->premium      = MTS_Premium::get_instance();
		$this->api          = MTS_API::get_instance();
		$this->alerts       = MTS_Alerts::get_instance();
		$this->location     = MTS_Location::get_instance();
		$this->calendar      = MTS_Calendar::get_instance();
		$this->jurisdiction  = MTS_Jurisdiction::get_instance();
		$this->notifications = MTS_Notifications::get_instance();
		$this->family        = MTS_Family::get_instance();
		$this->mobile_api    = MTS_Mobile_API::get_instance();
		$this->mobile_push   = MTS_Mobile_Push::get_instance();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks() {
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Enqueue assets for standalone mode.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Shortcode for standalone dashboard.
		add_shortcode( 'schengen_tracker', array( $this, 'render_shortcode' ) );

		// Check for database updates (must run on all requests, not just admin).
		add_action( 'init', array( $this, 'maybe_upgrade' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'MyTravelStatus Settings', 'mytravelstatus' ),
			__( 'MyTravelStatus', 'mytravelstatus' ),
			'manage_options',
			'mts-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'mts_settings', 'mts_global_enabled' );
		register_setting( 'mts_settings', 'mts_upgrade_url' );
		register_setting( 'mts_settings', 'mts_alert_email' );
		register_setting( 'mts_settings', 'mts_google_client_id' );
		register_setting( 'mts_settings', 'mts_google_client_secret' );
		register_setting( 'mts_settings', 'mts_microsoft_client_id' );
		register_setting( 'mts_settings', 'mts_microsoft_client_secret' );
		register_setting( 'mts_settings', 'mts_vapid_public_key' );
		register_setting( 'mts_settings', 'mts_vapid_private_key' );

		add_settings_section(
			'mts_general',
			__( 'General Settings', 'mytravelstatus' ),
			array( $this, 'render_general_section' ),
			'mts-settings'
		);

		add_settings_field(
			'mts_global_enabled',
			__( 'Enable for All Users', 'mytravelstatus' ),
			array( $this, 'render_global_enabled_field' ),
			'mts-settings',
			'mts_general'
		);

		add_settings_field(
			'mts_upgrade_url',
			__( 'Upgrade URL', 'mytravelstatus' ),
			array( $this, 'render_upgrade_url_field' ),
			'mts-settings',
			'mts_general'
		);

		// Calendar Sync settings section.
		add_settings_section(
			'mts_calendar',
			__( 'Calendar Sync (OAuth)', 'mytravelstatus' ),
			array( $this, 'render_calendar_section' ),
			'mts-settings'
		);

		add_settings_field(
			'mts_google_client_id',
			__( 'Google Client ID', 'mytravelstatus' ),
			array( $this, 'render_google_client_id_field' ),
			'mts-settings',
			'mts_calendar'
		);

		add_settings_field(
			'mts_google_client_secret',
			__( 'Google Client Secret', 'mytravelstatus' ),
			array( $this, 'render_google_client_secret_field' ),
			'mts-settings',
			'mts_calendar'
		);

		add_settings_field(
			'mts_microsoft_client_id',
			__( 'Microsoft Client ID', 'mytravelstatus' ),
			array( $this, 'render_microsoft_client_id_field' ),
			'mts-settings',
			'mts_calendar'
		);

		add_settings_field(
			'mts_microsoft_client_secret',
			__( 'Microsoft Client Secret', 'mytravelstatus' ),
			array( $this, 'render_microsoft_client_secret_field' ),
			'mts-settings',
			'mts_calendar'
		);

		// Push Notifications settings section.
		add_settings_section(
			'mts_push',
			__( 'Push Notifications (VAPID)', 'mytravelstatus' ),
			array( $this, 'render_push_section' ),
			'mts-settings'
		);

		add_settings_field(
			'mts_vapid_public_key',
			__( 'VAPID Public Key', 'mytravelstatus' ),
			array( $this, 'render_vapid_public_key_field' ),
			'mts-settings',
			'mts_push'
		);

		add_settings_field(
			'mts_vapid_private_key',
			__( 'VAPID Private Key', 'mytravelstatus' ),
			array( $this, 'render_vapid_private_key_field' ),
			'mts-settings',
			'mts_push'
		);

		// Mobile Push Notifications settings section.
		add_settings_section(
			'mts_mobile_push',
			__( 'Mobile Push Notifications', 'mytravelstatus' ),
			array( $this, 'render_mobile_push_section' ),
			'mts-settings'
		);

		// APNs settings.
		register_setting( 'mts_settings', 'mts_apns_team_id' );
		register_setting( 'mts_settings', 'mts_apns_key_id' );
		register_setting( 'mts_settings', 'mts_apns_bundle_id' );
		register_setting( 'mts_settings', 'mts_apns_key_path' );
		register_setting( 'mts_settings', 'mts_apns_sandbox' );

		add_settings_field(
			'mts_apns_team_id',
			__( 'APNs Team ID', 'mytravelstatus' ),
			array( $this, 'render_apns_team_id_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		add_settings_field(
			'mts_apns_key_id',
			__( 'APNs Key ID', 'mytravelstatus' ),
			array( $this, 'render_apns_key_id_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		add_settings_field(
			'mts_apns_bundle_id',
			__( 'APNs Bundle ID', 'mytravelstatus' ),
			array( $this, 'render_apns_bundle_id_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		add_settings_field(
			'mts_apns_key_path',
			__( 'APNs Key Path', 'mytravelstatus' ),
			array( $this, 'render_apns_key_path_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		add_settings_field(
			'mts_apns_sandbox',
			__( 'APNs Sandbox Mode', 'mytravelstatus' ),
			array( $this, 'render_apns_sandbox_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		// FCM settings.
		register_setting( 'mts_settings', 'mts_fcm_project_id' );
		register_setting( 'mts_settings', 'mts_fcm_service_account_path' );

		add_settings_field(
			'mts_fcm_project_id',
			__( 'FCM Project ID', 'mytravelstatus' ),
			array( $this, 'render_fcm_project_id_field' ),
			'mts-settings',
			'mts_mobile_push'
		);

		add_settings_field(
			'mts_fcm_service_account_path',
			__( 'FCM Service Account Path', 'mytravelstatus' ),
			array( $this, 'render_fcm_service_account_path_field' ),
			'mts-settings',
			'mts_mobile_push'
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( mts_has_member_tools() ) : ?>
				<div class="notice notice-info">
					<p>
						<?php esc_html_e( 'Relo2France Member Tools is active. Premium access is managed through MemberPress integration.', 'mytravelstatus' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'mts_settings' );
				do_settings_sections( 'mts-settings' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Shortcode Usage', 'mytravelstatus' ); ?></h2>
			<p><?php esc_html_e( 'Use the following shortcode to display the MyTravelStatus on any page:', 'mytravelstatus' ); ?></p>
			<code>[schengen_tracker]</code>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure the MyTravelStatus plugin settings.', 'mytravelstatus' ) . '</p>';
	}

	/**
	 * Render global enabled field.
	 */
	public function render_global_enabled_field() {
		$value = get_option( 'mts_global_enabled', '0' );
		?>
		<label>
			<input type="checkbox" name="mts_global_enabled" value="1" <?php checked( $value, '1' ); ?>>
			<?php esc_html_e( 'Enable MyTravelStatus for all logged-in users (bypasses premium check)', 'mytravelstatus' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, all logged-in users can access the tracker. When disabled, only users with premium access can use it.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render upgrade URL field.
	 */
	public function render_upgrade_url_field() {
		$value = get_option( 'mts_upgrade_url', '' );
		?>
		<input type="url" name="mts_upgrade_url" value="<?php echo esc_url( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'URL where non-premium users can upgrade. Leave empty to use the default.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render calendar section description.
	 */
	public function render_calendar_section() {
		$callback_url = home_url( '/wp-json/fra-portal/v1/schengen/calendar/oauth/callback' );
		?>
		<p><?php esc_html_e( 'Configure OAuth credentials for calendar integrations. Users can sync their Google Calendar or Microsoft Outlook to automatically detect travel events.', 'mytravelstatus' ); ?></p>
		<p><strong><?php esc_html_e( 'OAuth Callback URL:', 'mytravelstatus' ); ?></strong> <code><?php echo esc_html( $callback_url ); ?></code></p>
		<p class="description"><?php esc_html_e( 'Use this URL as the redirect URI when setting up OAuth applications.', 'mytravelstatus' ); ?></p>
		<?php
	}

	/**
	 * Render Google Client ID field.
	 */
	public function render_google_client_id_field() {
		$value = get_option( 'mts_google_client_id', '' );
		?>
		<input type="text" name="mts_google_client_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php
			printf(
				/* translators: %s: Google Cloud Console URL */
				esc_html__( 'Get this from the %s. Enable the Google Calendar API and create OAuth 2.0 credentials.', 'mytravelstatus' ),
				'<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Google Client Secret field.
	 */
	public function render_google_client_secret_field() {
		$value = get_option( 'mts_google_client_secret', '' );
		?>
		<input type="password" name="mts_google_client_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Keep this secret secure. It will be stored encrypted in the database.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Microsoft Client ID field.
	 */
	public function render_microsoft_client_id_field() {
		$value = get_option( 'mts_microsoft_client_id', '' );
		?>
		<input type="text" name="mts_microsoft_client_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php
			printf(
				/* translators: %s: Azure Portal URL */
				esc_html__( 'Get this from the %s. Register an app and add Microsoft Graph Calendar.Read permission.', 'mytravelstatus' ),
				'<a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank">Azure Portal</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Microsoft Client Secret field.
	 */
	public function render_microsoft_client_secret_field() {
		$value = get_option( 'mts_microsoft_client_secret', '' );
		?>
		<input type="password" name="mts_microsoft_client_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Keep this secret secure. It will be stored encrypted in the database.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render push notifications section description.
	 */
	public function render_push_section() {
		?>
		<p><?php esc_html_e( 'Configure VAPID keys for Web Push notifications. VAPID keys are used to authenticate your server with push services.', 'mytravelstatus' ); ?></p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Link to VAPID key generator */
				esc_html__( 'Generate VAPID keys using %s or a similar tool.', 'mytravelstatus' ),
				'<a href="https://vapidkeys.com/" target="_blank">vapidkeys.com</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render VAPID Public Key field.
	 */
	public function render_vapid_public_key_field() {
		$value = get_option( 'mts_vapid_public_key', '' );
		?>
		<input type="text" name="mts_vapid_public_key" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'The public key is shared with browsers to subscribe to push notifications.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render VAPID Private Key field.
	 */
	public function render_vapid_private_key_field() {
		$value = get_option( 'mts_vapid_private_key', '' );
		?>
		<input type="password" name="mts_vapid_private_key" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'Keep this secret secure. It is used to sign push notification requests.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render mobile push section description.
	 */
	public function render_mobile_push_section() {
		$status = $this->mobile_push->get_configuration_status();
		?>
		<p><?php esc_html_e( 'Configure push notification credentials for iOS (APNs) and Android (FCM) mobile apps.', 'mytravelstatus' ); ?></p>
		<table class="widefat" style="max-width: 400px; margin-top: 10px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'iOS (APNs)', 'mytravelstatus' ); ?></strong></td>
					<td>
						<?php if ( $status['ios']['configured'] ) : ?>
							<span style="color: green;">&#10003; <?php esc_html_e( 'Configured', 'mytravelstatus' ); ?></span>
							<?php if ( $status['ios']['sandbox'] ) : ?>
								<em>(<?php esc_html_e( 'Sandbox', 'mytravelstatus' ); ?>)</em>
							<?php endif; ?>
						<?php else : ?>
							<span style="color: gray;">&#10007; <?php esc_html_e( 'Not configured', 'mytravelstatus' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Android (FCM)', 'mytravelstatus' ); ?></strong></td>
					<td>
						<?php if ( $status['android']['configured'] ) : ?>
							<span style="color: green;">&#10003; <?php esc_html_e( 'Configured', 'mytravelstatus' ); ?></span>
						<?php else : ?>
							<span style="color: gray;">&#10007; <?php esc_html_e( 'Not configured', 'mytravelstatus' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render APNs Team ID field.
	 */
	public function render_apns_team_id_field() {
		$value = get_option( 'mts_apns_team_id', '' );
		?>
		<input type="text" name="mts_apns_team_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="XXXXXXXXXX">
		<p class="description">
			<?php esc_html_e( 'Your Apple Developer Team ID (10 characters). Found in Apple Developer account settings.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render APNs Key ID field.
	 */
	public function render_apns_key_id_field() {
		$value = get_option( 'mts_apns_key_id', '' );
		?>
		<input type="text" name="mts_apns_key_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="XXXXXXXXXX">
		<p class="description">
			<?php esc_html_e( 'APNs Key ID from App Store Connect. Create a key with Push Notifications capability.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render APNs Bundle ID field.
	 */
	public function render_apns_bundle_id_field() {
		$value = get_option( 'mts_apns_bundle_id', 'com.mytravelstatus.app' );
		?>
		<input type="text" name="mts_apns_bundle_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="com.mytravelstatus.app">
		<p class="description">
			<?php esc_html_e( 'Your iOS app bundle identifier. Must match the app ID in Xcode.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render APNs Key Path field.
	 */
	public function render_apns_key_path_field() {
		$value = get_option( 'mts_apns_key_path', '' );
		?>
		<input type="text" name="mts_apns_key_path" value="<?php echo esc_attr( $value ); ?>" class="large-text" placeholder="/path/to/AuthKey_XXXXXXXXXX.p8">
		<p class="description">
			<?php esc_html_e( 'Absolute path to your APNs .p8 key file. Store outside web root for security.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render APNs Sandbox field.
	 */
	public function render_apns_sandbox_field() {
		$value = get_option( 'mts_apns_sandbox', '0' );
		?>
		<label>
			<input type="checkbox" name="mts_apns_sandbox" value="1" <?php checked( $value, '1' ); ?>>
			<?php esc_html_e( 'Use APNs Sandbox environment (for development/TestFlight builds)', 'mytravelstatus' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'Enable this for development. Disable for production App Store builds.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render FCM Project ID field.
	 */
	public function render_fcm_project_id_field() {
		$value = get_option( 'mts_fcm_project_id', '' );
		?>
		<input type="text" name="mts_fcm_project_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="mytravelstatus-xxxxx">
		<p class="description">
			<?php esc_html_e( 'Your Firebase project ID. Found in Firebase Console > Project settings.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Render FCM Service Account Path field.
	 */
	public function render_fcm_service_account_path_field() {
		$value = get_option( 'mts_fcm_service_account_path', '' );
		?>
		<input type="text" name="mts_fcm_service_account_path" value="<?php echo esc_attr( $value ); ?>" class="large-text" placeholder="/path/to/firebase-service-account.json">
		<p class="description">
			<?php esc_html_e( 'Path to Firebase service account JSON file. Generate in Firebase Console > Project settings > Service accounts.', 'mytravelstatus' ); ?>
		</p>
		<?php
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		// Only load if shortcode is present or on a Schengen page.
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'schengen_tracker' ) ) {
			return;
		}

		wp_enqueue_style(
			'mts-frontend',
			MTS_PLUGIN_URL . 'assets/css/schengen-frontend.css',
			array(),
			MTS_VERSION
		);

		wp_enqueue_script(
			'mts-frontend',
			MTS_PLUGIN_URL . 'assets/js/schengen-frontend.js',
			array( 'jquery' ),
			MTS_VERSION,
			true
		);

		wp_localize_script( 'mts-frontend', 'r2fSchengen', array(
			'apiUrl'   => rest_url( 'mts/v1/' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'isPremium' => $this->premium->is_feature_enabled( get_current_user_id() ),
		) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_mts-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'mts-admin',
			MTS_PLUGIN_URL . 'assets/css/schengen-admin.css',
			array(),
			MTS_VERSION
		);
	}

	/**
	 * Render shortcode for standalone mode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return '<div class="mts-login-required">'
				. '<p>' . esc_html__( 'Please log in to access the MyTravelStatus.', 'mytravelstatus' ) . '</p>'
				. '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="button">'
				. esc_html__( 'Log In', 'mytravelstatus' )
				. '</a></div>';
		}

		$user_id = get_current_user_id();

		// Check premium access.
		if ( ! $this->premium->is_feature_enabled( $user_id ) ) {
			$status = $this->premium->get_feature_status( $user_id );
			return $this->render_upgrade_prompt( $status );
		}

		// Render dashboard.
		ob_start();
		include MTS_PLUGIN_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Render upgrade prompt for non-premium users.
	 *
	 * @param array $status Feature status array.
	 * @return string HTML output.
	 */
	private function render_upgrade_prompt( $status ) {
		$upgrade_url = $status['upgradeUrl'] ?? home_url( '/pricing/' );
		$message     = $status['message'] ?? __( 'Upgrade to Premium to access the MyTravelStatus.', 'mytravelstatus' );

		ob_start();
		?>
		<div class="mts-upgrade-prompt">
			<div class="mts-upgrade-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"></circle>
					<line x1="2" y1="12" x2="22" y2="12"></line>
					<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
				</svg>
			</div>
			<h3><?php esc_html_e( 'Schengen 90/180 Day Tracker', 'mytravelstatus' ); ?></h3>
			<p><?php echo esc_html( $message ); ?></p>
			<ul class="mts-features">
				<li><?php esc_html_e( 'Automatic day counting with 180-day rolling window', 'mytravelstatus' ); ?></li>
				<li><?php esc_html_e( 'Email alerts when approaching limits', 'mytravelstatus' ); ?></li>
				<li><?php esc_html_e( 'Calendar sync with Google & Outlook', 'mytravelstatus' ); ?></li>
				<li><?php esc_html_e( 'PDF reports for border officials', 'mytravelstatus' ); ?></li>
				<li><?php esc_html_e( '"What If" trip planning tool', 'mytravelstatus' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( $upgrade_url ); ?>" class="mts-upgrade-button">
				<?php esc_html_e( 'Upgrade to Premium', 'mytravelstatus' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check for and run database upgrades.
	 */
	public function maybe_upgrade() {
		$current_version = get_option( 'mts_version', '0.0.0' );

		if ( version_compare( $current_version, MTS_VERSION, '<' ) ) {
			// Run upgrade routines.
			MTS_Schema::create_tables();

			// Update version.
			update_option( 'mts_version', MTS_VERSION );

			/**
			 * Fires after the plugin has been upgraded.
			 *
			 * @param string $current_version The version being upgraded from.
			 * @param string $new_version The version being upgraded to.
			 */
			do_action( 'mts_upgraded', $current_version, MTS_VERSION );
		}
	}
}
