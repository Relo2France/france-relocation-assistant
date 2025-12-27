<?php
/**
 * Premium feature gating for Schengen Tracker.
 *
 * Handles premium access checks with support for:
 * 1. User meta override (admin can enable/disable per user)
 * 2. Filter hook for external plugins (Member Tools integration)
 * 3. Global setting fallback
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Premium gating singleton class.
 */
class R2F_Schengen_Premium {

	/**
	 * User meta key for per-user override.
	 *
	 * @var string
	 */
	const USER_META_KEY = 'r2f_schengen_enabled';

	/**
	 * Option key for global setting.
	 *
	 * @var string
	 */
	const GLOBAL_OPTION = 'r2f_schengen_global_enabled';

	/**
	 * Option key for upgrade URL.
	 *
	 * @var string
	 */
	const UPGRADE_URL_OPTION = 'r2f_schengen_upgrade_url';

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Premium
	 */
	private static $instance = null;

	/**
	 * Cache for feature status.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Premium
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
		// Clear cache on user meta update.
		add_action( 'updated_user_meta', array( $this, 'clear_user_cache' ), 10, 4 );
		add_action( 'deleted_user_meta', array( $this, 'clear_user_cache' ), 10, 4 );
	}

	/**
	 * Check if the Schengen Tracker feature is enabled for a user.
	 *
	 * Priority:
	 * 1. User meta override (admin can enable/disable per user)
	 * 2. Filter hook for external plugins (Member Tools hooks here)
	 * 3. Global setting fallback
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return bool True if feature is enabled for user.
	 */
	public function is_feature_enabled( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Return false for non-logged-in users.
		if ( 0 === $user_id ) {
			return false;
		}

		// Check cache.
		if ( isset( $this->cache[ $user_id ] ) ) {
			return $this->cache[ $user_id ];
		}

		// Priority 1: Check user meta override (admin can enable/disable per user).
		$override = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( '1' === $override ) {
			$this->cache[ $user_id ] = true;
			return true;
		}
		if ( '0' === $override ) {
			$this->cache[ $user_id ] = false;
			return false;
		}

		// Priority 2: Filter for external plugins (Member Tools hooks here).
		// Pass null as default so we can detect if a filter actually set a value.
		$filtered = apply_filters( 'r2f_schengen_premium_check', null, $user_id );
		if ( null !== $filtered ) {
			$this->cache[ $user_id ] = (bool) $filtered;
			return $this->cache[ $user_id ];
		}

		// Priority 3: Global fallback (default OFF for standalone).
		$global_enabled = get_option( self::GLOBAL_OPTION, '0' );
		$this->cache[ $user_id ] = '1' === $global_enabled;

		return $this->cache[ $user_id ];
	}

	/**
	 * Get feature status for frontend display.
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return array Feature status array with 'enabled', 'upgradeUrl', and 'message'.
	 */
	public function get_feature_status( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$enabled = $this->is_feature_enabled( $user_id );

		return array(
			'enabled'    => $enabled,
			'upgradeUrl' => $enabled ? null : $this->get_upgrade_url(),
			'message'    => $enabled ? null : $this->get_upgrade_message(),
		);
	}

	/**
	 * Get the upgrade URL.
	 *
	 * @return string Upgrade URL.
	 */
	public function get_upgrade_url() {
		// Allow other plugins to provide upgrade URL.
		$url = apply_filters( 'r2f_schengen_upgrade_url', null );

		if ( null !== $url ) {
			return $url;
		}

		// Check option.
		$option_url = get_option( self::UPGRADE_URL_OPTION, '' );
		if ( ! empty( $option_url ) ) {
			return $option_url;
		}

		// Default fallback.
		return home_url( '/pricing/' );
	}

	/**
	 * Get the upgrade message.
	 *
	 * @return string Upgrade message.
	 */
	public function get_upgrade_message() {
		return apply_filters(
			'r2f_schengen_upgrade_message',
			__( 'Schengen Tracker is a premium feature. Upgrade to access full functionality.', 'r2f-schengen' )
		);
	}

	/**
	 * Manually enable the feature for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function enable_for_user( $user_id ) {
		$result = update_user_meta( $user_id, self::USER_META_KEY, '1' );
		$this->clear_user_cache( null, $user_id );
		return false !== $result;
	}

	/**
	 * Manually disable the feature for a user.
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function disable_for_user( $user_id ) {
		$result = update_user_meta( $user_id, self::USER_META_KEY, '0' );
		$this->clear_user_cache( null, $user_id );
		return false !== $result;
	}

	/**
	 * Remove the manual override for a user (use default logic).
	 *
	 * @param int $user_id User ID.
	 * @return bool True on success.
	 */
	public function reset_for_user( $user_id ) {
		$result = delete_user_meta( $user_id, self::USER_META_KEY );
		$this->clear_user_cache( null, $user_id );
		return $result;
	}

	/**
	 * Clear cache for a user.
	 *
	 * @param int|null $meta_id     Meta ID.
	 * @param int      $user_id     User ID.
	 * @param string   $meta_key    Meta key (optional).
	 * @param mixed    $meta_value  Meta value (optional).
	 */
	public function clear_user_cache( $meta_id, $user_id, $meta_key = '', $meta_value = '' ) {
		if ( '' === $meta_key || self::USER_META_KEY === $meta_key ) {
			unset( $this->cache[ $user_id ] );
		}
	}

	/**
	 * Clear all cache.
	 */
	public function clear_cache() {
		$this->cache = array();
	}
}
