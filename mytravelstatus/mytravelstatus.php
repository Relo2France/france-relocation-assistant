<?php
/**
 * Plugin Name: MyTravelStatus
 * Plugin URI: https://mytravelstatus.com
 * Description: Track visa, tax, and residency days worldwide. Supports Schengen 90/180, UK SRT, US SPT, and 183-day rules. Calendar sync, alerts, and professional reports included.
 * Version: 1.8.2
 * Author: MyTravelStatus
 * Author URI: https://mytravelstatus.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mytravelstatus
 * Domain Path: /languages
 *
 * @package MyTravelStatus
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MTS_VERSION', '1.8.1' );
define( 'MTS_PLUGIN_FILE', __FILE__ );
define( 'MTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Database table prefix (shared with Member Tools for backward compatibility).
define( 'MTS_TABLE_PREFIX', 'mts_' );

// Load Composer autoloader if available (for web-push library).
$composer_autoload = MTS_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function mts_autoloader( $class_name ) {
	// Only handle our classes.
	if ( strpos( $class_name, 'MTS_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name.
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	$file_path = MTS_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}
spl_autoload_register( 'mts_autoloader' );

/**
 * Initialize the plugin.
 */
function mts_init() {
	// Load text domain for translations.
	load_plugin_textdomain( 'mts', false, dirname( MTS_PLUGIN_BASENAME ) . '/languages' );

	// Initialize core.
	MTS_Core::get_instance();
}
add_action( 'plugins_loaded', 'mts_init', 20 );

/**
 * Plugin activation hook.
 */
function mts_activate() {
	// Create database tables.
	require_once MTS_PLUGIN_DIR . 'includes/class-mts-schema.php';
	MTS_Schema::create_tables();

	// Schedule cron jobs.
	require_once MTS_PLUGIN_DIR . 'includes/class-mts-alerts.php';
	MTS_Alerts::get_instance()->schedule_cron();

	// Schedule calendar sync cron.
	require_once MTS_PLUGIN_DIR . 'includes/class-mts-calendar.php';
	MTS_Calendar::get_instance()->schedule_cron();

	// Set default options.
	add_option( 'mts_version', MTS_VERSION );
	add_option( 'mts_global_enabled', '0' ); // Default OFF for standalone.

	// Flush rewrite rules.
	flush_rewrite_rules();

	/**
	 * Fires when the MyTravelStatus plugin is activated.
	 */
	do_action( 'mts_activated' );
}
register_activation_hook( __FILE__, 'mts_activate' );

/**
 * Plugin deactivation hook.
 */
function mts_deactivate() {
	// Unschedule cron jobs.
	require_once MTS_PLUGIN_DIR . 'includes/class-mts-alerts.php';
	MTS_Alerts::get_instance()->unschedule_cron();

	// Unschedule calendar sync cron.
	require_once MTS_PLUGIN_DIR . 'includes/class-mts-calendar.php';
	MTS_Calendar::get_instance()->unschedule_cron();

	// Flush rewrite rules.
	flush_rewrite_rules();

	/**
	 * Fires when the MyTravelStatus plugin is deactivated.
	 */
	do_action( 'mts_deactivated' );
}
register_deactivation_hook( __FILE__, 'mts_deactivate' );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified action links.
 */
function mts_plugin_links( $links ) {
	$settings_url = admin_url( 'options-general.php?page=mts-settings' );
	$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'mytravelstatus' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . MTS_PLUGIN_BASENAME, 'mts_plugin_links' );

/**
 * Helper function to get a database table name.
 *
 * @param string $table Table name without prefix.
 * @return string Full table name with WordPress prefix.
 */
function mts_table( $table ) {
	global $wpdb;
	return $wpdb->prefix . MTS_TABLE_PREFIX . $table;
}

/**
 * Check if Member Tools plugin is active.
 *
 * @return bool True if Member Tools is active.
 */
function mts_has_member_tools() {
	return defined( 'FRAMT_VERSION' ) || class_exists( 'FRAMT_Portal_API' );
}
