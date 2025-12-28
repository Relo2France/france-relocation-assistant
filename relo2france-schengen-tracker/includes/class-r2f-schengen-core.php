<?php
/**
 * Core plugin class for Schengen Tracker.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core singleton class that initializes all plugin components.
 */
class R2F_Schengen_Core {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.0';

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Core
	 */
	private static $instance = null;

	/**
	 * Premium gating handler.
	 *
	 * @var R2F_Schengen_Premium
	 */
	public $premium;

	/**
	 * REST API handler.
	 *
	 * @var R2F_Schengen_API
	 */
	public $api;

	/**
	 * Alerts handler.
	 *
	 * @var R2F_Schengen_Alerts
	 */
	public $alerts;

	/**
	 * Location handler.
	 *
	 * @var R2F_Schengen_Location
	 */
	public $location;

	/**
	 * Calendar sync handler.
	 *
	 * @var R2F_Schengen_Calendar
	 */
	public $calendar;

	/**
	 * Jurisdiction rules handler.
	 *
	 * @var R2F_Schengen_Jurisdiction
	 */
	public $jurisdiction;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Core
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
		$includes_dir = R2F_SCHENGEN_PLUGIN_DIR . 'includes/';

		require_once $includes_dir . 'class-r2f-schengen-schema.php';
		require_once $includes_dir . 'class-r2f-schengen-premium.php';
		require_once $includes_dir . 'class-r2f-schengen-api.php';
		require_once $includes_dir . 'class-r2f-schengen-alerts.php';
		require_once $includes_dir . 'class-r2f-schengen-location.php';
		require_once $includes_dir . 'class-r2f-schengen-calendar.php';
		require_once $includes_dir . 'class-r2f-schengen-jurisdiction.php';
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		$this->premium      = R2F_Schengen_Premium::get_instance();
		$this->api          = R2F_Schengen_API::get_instance();
		$this->alerts       = R2F_Schengen_Alerts::get_instance();
		$this->location     = R2F_Schengen_Location::get_instance();
		$this->calendar     = R2F_Schengen_Calendar::get_instance();
		$this->jurisdiction = R2F_Schengen_Jurisdiction::get_instance();
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
			__( 'Schengen Tracker Settings', 'r2f-schengen' ),
			__( 'Schengen Tracker', 'r2f-schengen' ),
			'manage_options',
			'r2f-schengen-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_global_enabled' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_upgrade_url' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_alert_email' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_google_client_id' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_google_client_secret' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_microsoft_client_id' );
		register_setting( 'r2f_schengen_settings', 'r2f_schengen_microsoft_client_secret' );

		add_settings_section(
			'r2f_schengen_general',
			__( 'General Settings', 'r2f-schengen' ),
			array( $this, 'render_general_section' ),
			'r2f-schengen-settings'
		);

		add_settings_field(
			'r2f_schengen_global_enabled',
			__( 'Enable for All Users', 'r2f-schengen' ),
			array( $this, 'render_global_enabled_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_general'
		);

		add_settings_field(
			'r2f_schengen_upgrade_url',
			__( 'Upgrade URL', 'r2f-schengen' ),
			array( $this, 'render_upgrade_url_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_general'
		);

		// Calendar Sync settings section.
		add_settings_section(
			'r2f_schengen_calendar',
			__( 'Calendar Sync (OAuth)', 'r2f-schengen' ),
			array( $this, 'render_calendar_section' ),
			'r2f-schengen-settings'
		);

		add_settings_field(
			'r2f_schengen_google_client_id',
			__( 'Google Client ID', 'r2f-schengen' ),
			array( $this, 'render_google_client_id_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_calendar'
		);

		add_settings_field(
			'r2f_schengen_google_client_secret',
			__( 'Google Client Secret', 'r2f-schengen' ),
			array( $this, 'render_google_client_secret_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_calendar'
		);

		add_settings_field(
			'r2f_schengen_microsoft_client_id',
			__( 'Microsoft Client ID', 'r2f-schengen' ),
			array( $this, 'render_microsoft_client_id_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_calendar'
		);

		add_settings_field(
			'r2f_schengen_microsoft_client_secret',
			__( 'Microsoft Client Secret', 'r2f-schengen' ),
			array( $this, 'render_microsoft_client_secret_field' ),
			'r2f-schengen-settings',
			'r2f_schengen_calendar'
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

			<?php if ( r2f_schengen_has_member_tools() ) : ?>
				<div class="notice notice-info">
					<p>
						<?php esc_html_e( 'Relo2France Member Tools is active. Premium access is managed through MemberPress integration.', 'r2f-schengen' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'r2f_schengen_settings' );
				do_settings_sections( 'r2f-schengen-settings' );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Shortcode Usage', 'r2f-schengen' ); ?></h2>
			<p><?php esc_html_e( 'Use the following shortcode to display the Schengen Tracker on any page:', 'r2f-schengen' ); ?></p>
			<code>[schengen_tracker]</code>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure the Schengen Tracker plugin settings.', 'r2f-schengen' ) . '</p>';
	}

	/**
	 * Render global enabled field.
	 */
	public function render_global_enabled_field() {
		$value = get_option( 'r2f_schengen_global_enabled', '0' );
		?>
		<label>
			<input type="checkbox" name="r2f_schengen_global_enabled" value="1" <?php checked( $value, '1' ); ?>>
			<?php esc_html_e( 'Enable Schengen Tracker for all logged-in users (bypasses premium check)', 'r2f-schengen' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, all logged-in users can access the tracker. When disabled, only users with premium access can use it.', 'r2f-schengen' ); ?>
		</p>
		<?php
	}

	/**
	 * Render upgrade URL field.
	 */
	public function render_upgrade_url_field() {
		$value = get_option( 'r2f_schengen_upgrade_url', '' );
		?>
		<input type="url" name="r2f_schengen_upgrade_url" value="<?php echo esc_url( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'URL where non-premium users can upgrade. Leave empty to use the default.', 'r2f-schengen' ); ?>
		</p>
		<?php
	}

	/**
	 * Render calendar section description.
	 */
	public function render_calendar_section() {
		$callback_url = home_url( '/wp-json/fra-portal/v1/schengen/calendar/oauth/callback' );
		?>
		<p><?php esc_html_e( 'Configure OAuth credentials for calendar integrations. Users can sync their Google Calendar or Microsoft Outlook to automatically detect travel events.', 'r2f-schengen' ); ?></p>
		<p><strong><?php esc_html_e( 'OAuth Callback URL:', 'r2f-schengen' ); ?></strong> <code><?php echo esc_html( $callback_url ); ?></code></p>
		<p class="description"><?php esc_html_e( 'Use this URL as the redirect URI when setting up OAuth applications.', 'r2f-schengen' ); ?></p>
		<?php
	}

	/**
	 * Render Google Client ID field.
	 */
	public function render_google_client_id_field() {
		$value = get_option( 'r2f_schengen_google_client_id', '' );
		?>
		<input type="text" name="r2f_schengen_google_client_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php
			printf(
				/* translators: %s: Google Cloud Console URL */
				esc_html__( 'Get this from the %s. Enable the Google Calendar API and create OAuth 2.0 credentials.', 'r2f-schengen' ),
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
		$value = get_option( 'r2f_schengen_google_client_secret', '' );
		?>
		<input type="password" name="r2f_schengen_google_client_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Keep this secret secure. It will be stored encrypted in the database.', 'r2f-schengen' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Microsoft Client ID field.
	 */
	public function render_microsoft_client_id_field() {
		$value = get_option( 'r2f_schengen_microsoft_client_id', '' );
		?>
		<input type="text" name="r2f_schengen_microsoft_client_id" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php
			printf(
				/* translators: %s: Azure Portal URL */
				esc_html__( 'Get this from the %s. Register an app and add Microsoft Graph Calendar.Read permission.', 'r2f-schengen' ),
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
		$value = get_option( 'r2f_schengen_microsoft_client_secret', '' );
		?>
		<input type="password" name="r2f_schengen_microsoft_client_secret" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php esc_html_e( 'Keep this secret secure. It will be stored encrypted in the database.', 'r2f-schengen' ); ?>
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
			'r2f-schengen-frontend',
			R2F_SCHENGEN_PLUGIN_URL . 'assets/css/schengen-frontend.css',
			array(),
			R2F_SCHENGEN_VERSION
		);

		wp_enqueue_script(
			'r2f-schengen-frontend',
			R2F_SCHENGEN_PLUGIN_URL . 'assets/js/schengen-frontend.js',
			array( 'jquery' ),
			R2F_SCHENGEN_VERSION,
			true
		);

		wp_localize_script( 'r2f-schengen-frontend', 'r2fSchengen', array(
			'apiUrl'   => rest_url( 'r2f-schengen/v1/' ),
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
		if ( 'settings_page_r2f-schengen-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'r2f-schengen-admin',
			R2F_SCHENGEN_PLUGIN_URL . 'assets/css/schengen-admin.css',
			array(),
			R2F_SCHENGEN_VERSION
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
			return '<div class="r2f-schengen-login-required">'
				. '<p>' . esc_html__( 'Please log in to access the Schengen Tracker.', 'r2f-schengen' ) . '</p>'
				. '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="button">'
				. esc_html__( 'Log In', 'r2f-schengen' )
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
		include R2F_SCHENGEN_PLUGIN_DIR . 'templates/dashboard.php';
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
		$message     = $status['message'] ?? __( 'Upgrade to Premium to access the Schengen Tracker.', 'r2f-schengen' );

		ob_start();
		?>
		<div class="r2f-schengen-upgrade-prompt">
			<div class="r2f-schengen-upgrade-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"></circle>
					<line x1="2" y1="12" x2="22" y2="12"></line>
					<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
				</svg>
			</div>
			<h3><?php esc_html_e( 'Schengen 90/180 Day Tracker', 'r2f-schengen' ); ?></h3>
			<p><?php echo esc_html( $message ); ?></p>
			<ul class="r2f-schengen-features">
				<li><?php esc_html_e( 'Automatic day counting with 180-day rolling window', 'r2f-schengen' ); ?></li>
				<li><?php esc_html_e( 'Email alerts when approaching limits', 'r2f-schengen' ); ?></li>
				<li><?php esc_html_e( 'Calendar sync with Google & Outlook', 'r2f-schengen' ); ?></li>
				<li><?php esc_html_e( 'PDF reports for border officials', 'r2f-schengen' ); ?></li>
				<li><?php esc_html_e( '"What If" trip planning tool', 'r2f-schengen' ); ?></li>
			</ul>
			<a href="<?php echo esc_url( $upgrade_url ); ?>" class="r2f-schengen-upgrade-button">
				<?php esc_html_e( 'Upgrade to Premium', 'r2f-schengen' ); ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check for and run database upgrades.
	 */
	public function maybe_upgrade() {
		$current_version = get_option( 'r2f_schengen_version', '0.0.0' );

		if ( version_compare( $current_version, R2F_SCHENGEN_VERSION, '<' ) ) {
			// Run upgrade routines.
			R2F_Schengen_Schema::create_tables();

			// Update version.
			update_option( 'r2f_schengen_version', R2F_SCHENGEN_VERSION );

			/**
			 * Fires after the plugin has been upgraded.
			 *
			 * @param string $current_version The version being upgraded from.
			 * @param string $new_version The version being upgraded to.
			 */
			do_action( 'r2f_schengen_upgraded', $current_version, R2F_SCHENGEN_VERSION );
		}
	}
}
