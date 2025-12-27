<?php
/**
 * Schengen Tracker Bridge
 *
 * Provides integration between the standalone Relo2France Schengen Tracker plugin
 * and the Member Tools Portal. Handles premium gating via MemberPress and provides
 * portal URL routing.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Schengen
 * @since       2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FRAMT_Schengen_Bridge
 *
 * Bridges the Relo2France Schengen Tracker plugin with Member Tools.
 *
 * @since 2.2.0
 */
class FRAMT_Schengen_Bridge {

	/**
	 * Singleton instance
	 *
	 * @var FRAMT_Schengen_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return FRAMT_Schengen_Bridge
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register all hooks for Schengen integration.
	 *
	 * @return void
	 */
	private function register_hooks() {
		// Premium gating - provide membership check.
		add_filter( 'r2f_schengen_premium_check', array( $this, 'check_premium_access' ), 10, 2 );

		// Upgrade URL - link to MemberPress pricing.
		add_filter( 'r2f_schengen_upgrade_url', array( $this, 'get_upgrade_url' ) );

		// Tracker URL - point to portal.
		add_filter( 'r2f_schengen_tracker_url', array( $this, 'get_tracker_url' ) );

		// Settings URL - point to portal settings.
		add_filter( 'r2f_schengen_settings_url', array( $this, 'get_settings_url' ) );

		// Brand customization.
		add_filter( 'r2f_schengen_site_name', array( $this, 'get_site_name' ) );

		// React portal data.
		add_filter( 'framt_portal_data', array( $this, 'add_portal_data' ) );

		// Forward plugin events to Member Tools logs.
		add_action( 'r2f_schengen_trip_created', array( $this, 'log_trip_created' ), 10, 2 );
		add_action( 'r2f_schengen_trip_deleted', array( $this, 'log_trip_deleted' ), 10, 2 );
		add_action( 'r2f_schengen_alert_sent', array( $this, 'log_alert_sent' ), 10, 3 );
	}

	/**
	 * Check if user has premium access via MemberPress.
	 *
	 * @param mixed $default  Default value (null = no decision yet).
	 * @param int   $user_id  User ID.
	 * @return bool|null True if premium, false if not, null to defer.
	 */
	public function check_premium_access( $default, $user_id ) {
		// Use Member Tools membership check.
		$membership = FRAMT_Membership::get_instance();

		if ( $membership->is_member( $user_id ) ) {
			return true;
		}

		// Return null to allow other checks (user meta, global setting).
		return null;
	}

	/**
	 * Get the upgrade URL for non-premium users.
	 *
	 * @param string $default Default URL.
	 * @return string Upgrade URL.
	 */
	public function get_upgrade_url( $default ) {
		// Use MemberPress pricing page if available.
		$pricing_page = get_option( 'framt_pricing_page_id' );

		if ( $pricing_page ) {
			$url = get_permalink( $pricing_page );
			if ( $url ) {
				return $url;
			}
		}

		// Fallback to standard pricing URL.
		return home_url( '/pricing/' );
	}

	/**
	 * Get the Schengen Tracker URL in the portal.
	 *
	 * @param string $default Default URL.
	 * @return string Portal URL with Schengen view.
	 */
	public function get_tracker_url( $default ) {
		return home_url( '/portal/?view=schengen' );
	}

	/**
	 * Get the settings URL in the portal.
	 *
	 * @param string $default Default URL.
	 * @return string Portal settings URL.
	 */
	public function get_settings_url( $default ) {
		return home_url( '/portal/?view=settings' );
	}

	/**
	 * Get the site name for email branding.
	 *
	 * @param string $default Default site name.
	 * @return string Site name.
	 */
	public function get_site_name( $default ) {
		return 'relo2france.com';
	}

	/**
	 * Add Schengen data to portal initialization.
	 *
	 * @param array $data Portal data array.
	 * @return array Modified data.
	 */
	public function add_portal_data( $data ) {
		$user_id = get_current_user_id();

		// Check if Schengen plugin is active.
		if ( ! $this->is_schengen_plugin_active() ) {
			$data['schengen'] = array(
				'enabled'    => false,
				'message'    => __( 'Schengen Tracker plugin is not active.', 'fra-member-tools' ),
			);
			return $data;
		}

		// Get premium status from the Schengen plugin.
		$premium = R2F_Schengen_Premium::get_instance();
		$status = $premium->get_feature_status( $user_id );

		$data['schengen'] = array(
			'enabled'    => $status['enabled'],
			'upgradeUrl' => $status['upgradeUrl'],
			'message'    => $status['message'],
			'apiBase'    => rest_url( 'r2f-schengen/v1/' ),
		);

		return $data;
	}

	/**
	 * Check if the Schengen Tracker plugin is active.
	 *
	 * @return bool True if active.
	 */
	public function is_schengen_plugin_active() {
		return defined( 'R2F_SCHENGEN_VERSION' ) && class_exists( 'R2F_Schengen_Premium' );
	}

	/**
	 * Log trip created event.
	 *
	 * @param array $trip    Trip data.
	 * @param int   $user_id User ID.
	 * @return void
	 */
	public function log_trip_created( $trip, $user_id ) {
		if ( ! class_exists( 'FRAMT_Activity_Log' ) ) {
			return;
		}

		$start = isset( $trip['start_date'] ) ? $trip['start_date'] : '';
		$end = isset( $trip['end_date'] ) ? $trip['end_date'] : '';
		$country = isset( $trip['country'] ) ? $trip['country'] : '';

		FRAMT_Activity_Log::log(
			$user_id,
			'schengen_trip_added',
			sprintf(
				/* translators: 1: country, 2: start date, 3: end date */
				__( 'Added Schengen trip to %1$s (%2$s - %3$s)', 'fra-member-tools' ),
				$country,
				$start,
				$end
			)
		);
	}

	/**
	 * Log trip deleted event.
	 *
	 * @param int $trip_id Trip ID.
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function log_trip_deleted( $trip_id, $user_id ) {
		if ( ! class_exists( 'FRAMT_Activity_Log' ) ) {
			return;
		}

		FRAMT_Activity_Log::log(
			$user_id,
			'schengen_trip_deleted',
			sprintf(
				/* translators: %d: trip ID */
				__( 'Deleted Schengen trip #%d', 'fra-member-tools' ),
				$trip_id
			)
		);
	}

	/**
	 * Log alert sent event.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $alert_level Alert level.
	 * @param array  $summary     Summary data.
	 * @return void
	 */
	public function log_alert_sent( $user_id, $alert_level, $summary ) {
		if ( ! class_exists( 'FRAMT_Activity_Log' ) ) {
			return;
		}

		FRAMT_Activity_Log::log(
			$user_id,
			'schengen_alert_sent',
			sprintf(
				/* translators: 1: alert level, 2: days used */
				__( 'Sent %1$s alert (days used: %2$d/90)', 'fra-member-tools' ),
				$alert_level,
				$summary['days_used']
			)
		);
	}

	/**
	 * Migrate user settings from old Member Tools format.
	 *
	 * Call this during plugin upgrade to migrate existing settings.
	 *
	 * @param int $user_id User ID.
	 * @return bool True if migration occurred.
	 */
	public function migrate_user_settings( $user_id ) {
		// Check for old settings format.
		$old_settings = get_user_meta( $user_id, 'framt_schengen_settings', true );

		if ( empty( $old_settings ) ) {
			return false;
		}

		// Already migrated?
		$new_settings = get_user_meta( $user_id, 'r2f_schengen_settings', true );
		if ( ! empty( $new_settings ) ) {
			return false;
		}

		// Migrate settings.
		update_user_meta( $user_id, 'r2f_schengen_settings', $old_settings );

		// Migrate alert tracking.
		$last_level = get_user_meta( $user_id, 'framt_schengen_last_alert_level', true );
		$last_time = get_user_meta( $user_id, 'framt_schengen_last_alert_time', true );

		if ( $last_level ) {
			update_user_meta( $user_id, 'r2f_schengen_last_alert_level', $last_level );
		}
		if ( $last_time ) {
			update_user_meta( $user_id, 'r2f_schengen_last_alert_time', $last_time );
		}

		return true;
	}

	/**
	 * Migrate all users' settings.
	 *
	 * @return int Number of users migrated.
	 */
	public function migrate_all_user_settings() {
		global $wpdb;

		$user_ids = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
			 WHERE meta_key = 'framt_schengen_settings'"
		);

		$migrated = 0;
		foreach ( $user_ids as $user_id ) {
			if ( $this->migrate_user_settings( (int) $user_id ) ) {
				$migrated++;
			}
		}

		return $migrated;
	}
}
