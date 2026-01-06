<?php
/**
 * MyTravelStatus Test Integration
 *
 * Creates a test page and integration with the Relo2France site
 * for testing the standalone MyTravelStatus plugin.
 *
 * @package MTS_Tracker
 * @since   1.8.2
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test integration class.
 */
class MTS_Test_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var MTS_Test_Integration
	 */
	private static $instance = null;

	/**
	 * The page slug for the test page.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'my-travel-status';

	/**
	 * Get singleton instance.
	 *
	 * @return MTS_Test_Integration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 */
	private function register_hooks() {
		// Create test page on activation.
		add_action( 'admin_init', array( $this, 'maybe_create_test_page' ) );

		// Handle test data setup action.
		add_action( 'admin_init', array( $this, 'handle_setup_test_data' ) );

		// Add admin menu for quick access.
		add_action( 'admin_menu', array( $this, 'add_test_menu' ) );

		// Add integration link to Member Tools portal.
		add_filter( 'framt_portal_external_links', array( $this, 'add_portal_link' ) );

		// Enable premium for all logged-in users in test mode.
		add_filter( 'mts_premium_check', array( $this, 'enable_test_access' ), 10, 2 );

		// Register REST endpoint for test page URL.
		add_action( 'rest_api_init', array( $this, 'register_test_endpoint' ) );
	}

	/**
	 * Handle the setup test data action.
	 */
	public function handle_setup_test_data() {
		if ( ! isset( $_GET['mts_action'] ) || $_GET['mts_action'] !== 'setup_test_data' ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'mts_setup_test_data' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Run setup.
		$this->setup_test_data();

		// Redirect to test page.
		$url = $this->get_test_page_url();
		if ( $url ) {
			wp_safe_redirect( $url );
			exit;
		}

		wp_safe_redirect( admin_url( 'options-general.php?page=mts-settings&mts_test_data=1' ) );
		exit;
	}

	/**
	 * Set up test data for the current user.
	 */
	public function setup_test_data() {
		global $wpdb;

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		// Enable test mode.
		update_option( 'mts_test_mode_enabled', true );
		update_option( 'mts_global_enabled', '1' );

		// Set up tracked jurisdictions.
		$jurisdictions = array( 'schengen_visa', 'uk_srt', 'ireland_183', 'us_spt' );
		update_user_meta( $user_id, 'mts_tracked_jurisdictions', $jurisdictions );

		// Set up UK ties.
		$current_year = (int) date( 'Y' );
		$uk_ties = array(
			'family_tie'        => false,
			'accommodation_tie' => true,
			'work_tie'          => false,
			'ninety_day_tie'    => true,
			'country_tie'       => false,
		);
		update_user_meta( $user_id, 'mts_uk_ties_' . $current_year, $uk_ties );

		// Create sample trips.
		$table = $wpdb->prefix . 'mts_trips';
		$today = new DateTime();

		$trips = array(
			array( 'country' => 'France', 'start_days' => -15, 'end_days' => -5, 'category' => 'personal', 'notes' => 'Paris vacation' ),
			array( 'country' => 'Spain', 'start_days' => -60, 'end_days' => -46, 'category' => 'personal', 'notes' => 'Barcelona trip' ),
			array( 'country' => 'Germany', 'start_days' => -120, 'end_days' => -110, 'category' => 'business', 'notes' => 'Berlin conference' ),
			array( 'country' => 'United Kingdom', 'start_days' => -45, 'end_days' => -25, 'category' => 'business', 'notes' => 'London meetings' ),
			array( 'country' => 'Ireland', 'start_days' => -90, 'end_days' => -83, 'category' => 'personal', 'notes' => 'Dublin trip' ),
			array( 'country' => 'United States', 'start_days' => -30, 'end_days' => -16, 'category' => 'business', 'notes' => 'NYC business' ),
		);

		foreach ( $trips as $trip ) {
			$start_date = ( clone $today )->modify( "{$trip['start_days']} days" )->format( 'Y-m-d' );
			$end_date = ( clone $today )->modify( "{$trip['end_days']} days" )->format( 'Y-m-d' );

			// Skip if exists.
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND country = %s AND start_date = %s",
				$user_id,
				$trip['country'],
				$start_date
			) );

			if ( $existing ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'user_id'    => $user_id,
					'country'    => $trip['country'],
					'start_date' => $start_date,
					'end_date'   => $end_date,
					'category'   => $trip['category'],
					'notes'      => $trip['notes'],
					'created_at' => current_time( 'mysql' ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		// Invalidate cache.
		if ( class_exists( 'MTS_Jurisdiction' ) ) {
			MTS_Jurisdiction::get_instance()->invalidate_cache( $user_id );
		}
	}

	/**
	 * Create the test page if it doesn't exist.
	 */
	public function maybe_create_test_page() {
		// Only run once per hour max.
		$last_check = get_transient( 'mts_test_page_check' );
		if ( $last_check ) {
			return;
		}
		set_transient( 'mts_test_page_check', true, HOUR_IN_SECONDS );

		// Check if page exists.
		$page = get_page_by_path( self::PAGE_SLUG );
		if ( $page ) {
			return;
		}

		// Create the page.
		$page_id = wp_insert_post( array(
			'post_title'   => 'My Travel Status',
			'post_name'    => self::PAGE_SLUG,
			'post_content' => '[schengen_tracker]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			// Store the page ID for reference.
			update_option( 'mts_test_page_id', $page_id );
		}
	}

	/**
	 * Add admin menu link for quick access to test page.
	 */
	public function add_test_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'MyTravelStatus Test', 'mytravelstatus' ),
			__( 'MyTravelStatus Test', 'mytravelstatus' ),
			'read', // Any logged-in user.
			'mts-test-page',
			array( $this, 'redirect_to_test_page' )
		);
	}

	/**
	 * Show test page dashboard or redirect to test page.
	 */
	public function redirect_to_test_page() {
		$url = $this->get_test_page_url();
		$setup_url = wp_nonce_url(
			admin_url( '?mts_action=setup_test_data' ),
			'mts_setup_test_data'
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'MyTravelStatus Test Dashboard', 'mytravelstatus' ) . '</h1>';

		echo '<div style="max-width: 800px; margin-top: 20px;">';

		// Test page link.
		echo '<div style="background: #fff; border: 1px solid #ccc; border-left: 4px solid #0073aa; padding: 20px; margin-bottom: 20px;">';
		echo '<h2 style="margin-top: 0;">' . esc_html__( 'View MyTravelStatus', 'mytravelstatus' ) . '</h2>';
		if ( $url ) {
			echo '<p>' . esc_html__( 'Click below to view the MyTravelStatus dashboard with multi-jurisdiction tracking.', 'mytravelstatus' ) . '</p>';
			echo '<p><a href="' . esc_url( $url ) . '" class="button button-primary button-hero">';
			echo esc_html__( 'Open MyTravelStatus', 'mytravelstatus' ) . '</a></p>';
		} else {
			echo '<p>' . esc_html__( 'The test page is being created. Refresh to try again.', 'mytravelstatus' ) . '</p>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=mts-test-page' ) ) . '" class="button">';
			echo esc_html__( 'Refresh', 'mytravelstatus' ) . '</a></p>';
		}
		echo '</div>';

		// Setup test data.
		echo '<div style="background: #fff; border: 1px solid #ccc; border-left: 4px solid #00a32a; padding: 20px; margin-bottom: 20px;">';
		echo '<h2 style="margin-top: 0;">' . esc_html__( 'Set Up Test Data', 'mytravelstatus' ) . '</h2>';
		echo '<p>' . esc_html__( 'Click below to set up sample trips and jurisdictions for testing. This will:', 'mytravelstatus' ) . '</p>';
		echo '<ul style="list-style-type: disc; margin-left: 20px;">';
		echo '<li>' . esc_html__( 'Enable tracking for Schengen, UK SRT, Ireland 183-day, and US SPT', 'mytravelstatus' ) . '</li>';
		echo '<li>' . esc_html__( 'Create sample trips to France, Spain, Germany, UK, Ireland, and US', 'mytravelstatus' ) . '</li>';
		echo '<li>' . esc_html__( 'Set up UK ties for SRT calculation', 'mytravelstatus' ) . '</li>';
		echo '</ul>';
		echo '<p><a href="' . esc_url( $setup_url ) . '" class="button button-secondary button-hero">';
		echo esc_html__( 'Set Up Test Data & View', 'mytravelstatus' ) . '</a></p>';
		echo '</div>';

		// Info box.
		echo '<div style="background: #f0f6fc; border: 1px solid #c3c4c7; padding: 15px;">';
		echo '<h3 style="margin-top: 0;">' . esc_html__( 'What You Can Test', 'mytravelstatus' ) . '</h3>';
		echo '<ul style="list-style-type: disc; margin-left: 20px;">';
		echo '<li><strong>' . esc_html__( 'Multi-Jurisdiction Overview:', 'mytravelstatus' ) . '</strong> ' . esc_html__( 'See compliance status for multiple jurisdictions at once', 'mytravelstatus' ) . '</li>';
		echo '<li><strong>' . esc_html__( 'UK Statutory Residence Test:', 'mytravelstatus' ) . '</strong> ' . esc_html__( 'Complex tax residency calculation with ties questionnaire', 'mytravelstatus' ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Schengen 90/180 Rule:', 'mytravelstatus' ) . '</strong> ' . esc_html__( 'Rolling window visa compliance tracking', 'mytravelstatus' ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Ireland 183-Day Rule:', 'mytravelstatus' ) . '</strong> ' . esc_html__( 'Calendar year tax residency', 'mytravelstatus' ) . '</li>';
		echo '<li><strong>' . esc_html__( 'US Substantial Presence Test:', 'mytravelstatus' ) . '</strong> ' . esc_html__( 'Weighted multi-year calculation', 'mytravelstatus' ) . '</li>';
		echo '</ul>';
		echo '</div>';

		echo '</div>'; // max-width container.
		echo '</div>'; // wrap.
	}

	/**
	 * Get the test page URL.
	 *
	 * @return string|null Page URL or null if not found.
	 */
	public function get_test_page_url() {
		$page = get_page_by_path( self::PAGE_SLUG );
		if ( $page ) {
			return get_permalink( $page );
		}

		// Try by stored ID.
		$page_id = get_option( 'mts_test_page_id' );
		if ( $page_id ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}

		return null;
	}

	/**
	 * Add link to Member Tools portal external links.
	 *
	 * @param array $links Existing external links.
	 * @return array Modified links.
	 */
	public function add_portal_link( $links ) {
		$url = $this->get_test_page_url();
		if ( ! $url ) {
			return $links;
		}

		$links['mytravelstatus'] = array(
			'id'          => 'mytravelstatus',
			'label'       => __( 'MyTravelStatus (Beta)', 'mytravelstatus' ),
			'url'         => $url,
			'icon'        => 'Globe',
			'description' => __( 'Multi-jurisdiction travel tracking', 'mytravelstatus' ),
			'external'    => false,
			'section'     => 'resources',
		);

		return $links;
	}

	/**
	 * Enable premium access for all logged-in users in test mode.
	 *
	 * @param bool|null $enabled Current enabled status.
	 * @param int       $user_id User ID.
	 * @return bool|null Modified enabled status.
	 */
	public function enable_test_access( $enabled, $user_id ) {
		// Check if test mode is enabled.
		if ( ! get_option( 'mts_test_mode_enabled', true ) ) {
			return $enabled;
		}

		// Enable for all logged-in users.
		if ( $user_id > 0 ) {
			return true;
		}

		return $enabled;
	}

	/**
	 * Register REST endpoint for test page URL.
	 */
	public function register_test_endpoint() {
		register_rest_route( 'mts/v1', '/test-page', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_test_page' ),
			'permission_callback' => function() {
				return is_user_logged_in();
			},
		) );
	}

	/**
	 * REST endpoint to get test page info.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_test_page() {
		$url = $this->get_test_page_url();

		return rest_ensure_response( array(
			'url'     => $url,
			'exists'  => ! empty( $url ),
			'enabled' => get_option( 'mts_test_mode_enabled', true ),
		) );
	}

	/**
	 * Activate test mode (called during plugin setup).
	 */
	public static function activate_test_mode() {
		update_option( 'mts_test_mode_enabled', true );
		delete_transient( 'mts_test_page_check' );
	}

	/**
	 * Deactivate test mode.
	 */
	public static function deactivate_test_mode() {
		update_option( 'mts_test_mode_enabled', false );
	}
}
