<?php
/**
 * Plugin Name: Relo2France Schengen Tracker
 * Plugin URI: https://relo2france.com/schengen-tracker
 * Description: Track your Schengen 90/180 day compliance with calendar sync, alerts, and professional reports. Works standalone or integrated with Relo2France Member Portal.
 * Version: 1.0.0
 * Author: Relo2France
 * Author URI: https://relo2france.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: r2f-schengen
 * Domain Path: /languages
 *
 * @package R2F_Schengen_Tracker
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'R2F_SCHENGEN_VERSION', '1.3.0' );
define( 'R2F_SCHENGEN_PLUGIN_FILE', __FILE__ );
define( 'R2F_SCHENGEN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'R2F_SCHENGEN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'R2F_SCHENGEN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Database table prefix (shared with Member Tools for backward compatibility).
define( 'R2F_SCHENGEN_TABLE_PREFIX', 'fra_schengen_' );

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function r2f_schengen_autoloader( $class_name ) {
	// Only handle our classes.
	if ( strpos( $class_name, 'R2F_Schengen_' ) !== 0 ) {
		return;
	}

	// Convert class name to file name.
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	$file_path = R2F_SCHENGEN_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}
spl_autoload_register( 'r2f_schengen_autoloader' );

/**
 * Initialize the plugin.
 */
function r2f_schengen_init() {
	// Load text domain for translations.
	load_plugin_textdomain( 'r2f-schengen', false, dirname( R2F_SCHENGEN_PLUGIN_BASENAME ) . '/languages' );

	// Initialize core.
	R2F_Schengen_Core::get_instance();
}
add_action( 'plugins_loaded', 'r2f_schengen_init', 20 );

/**
 * Plugin activation hook.
 */
function r2f_schengen_activate() {
	// Create database tables.
	require_once R2F_SCHENGEN_PLUGIN_DIR . 'includes/class-r2f-schengen-schema.php';
	R2F_Schengen_Schema::create_tables();

	// Schedule cron jobs.
	require_once R2F_SCHENGEN_PLUGIN_DIR . 'includes/class-r2f-schengen-alerts.php';
	R2F_Schengen_Alerts::get_instance()->schedule_cron();

	// Schedule calendar sync cron.
	require_once R2F_SCHENGEN_PLUGIN_DIR . 'includes/class-r2f-schengen-calendar.php';
	R2F_Schengen_Calendar::get_instance()->schedule_cron();

	// Set default options.
	add_option( 'r2f_schengen_version', R2F_SCHENGEN_VERSION );
	add_option( 'r2f_schengen_global_enabled', '0' ); // Default OFF for standalone.

	// Flush rewrite rules.
	flush_rewrite_rules();

	/**
	 * Fires when the Schengen Tracker plugin is activated.
	 */
	do_action( 'r2f_schengen_activated' );
}
register_activation_hook( __FILE__, 'r2f_schengen_activate' );

/**
 * Plugin deactivation hook.
 */
function r2f_schengen_deactivate() {
	// Unschedule cron jobs.
	require_once R2F_SCHENGEN_PLUGIN_DIR . 'includes/class-r2f-schengen-alerts.php';
	R2F_Schengen_Alerts::get_instance()->unschedule_cron();

	// Unschedule calendar sync cron.
	require_once R2F_SCHENGEN_PLUGIN_DIR . 'includes/class-r2f-schengen-calendar.php';
	R2F_Schengen_Calendar::get_instance()->unschedule_cron();

	// Flush rewrite rules.
	flush_rewrite_rules();

	/**
	 * Fires when the Schengen Tracker plugin is deactivated.
	 */
	do_action( 'r2f_schengen_deactivated' );
}
register_deactivation_hook( __FILE__, 'r2f_schengen_deactivate' );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified action links.
 */
function r2f_schengen_plugin_links( $links ) {
	$settings_url = admin_url( 'options-general.php?page=r2f-schengen-settings' );
	$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'r2f-schengen' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . R2F_SCHENGEN_PLUGIN_BASENAME, 'r2f_schengen_plugin_links' );

/**
 * Helper function to get a database table name.
 *
 * @param string $table Table name without prefix.
 * @return string Full table name with WordPress prefix.
 */
function r2f_schengen_table( $table ) {
	global $wpdb;
	return $wpdb->prefix . R2F_SCHENGEN_TABLE_PREFIX . $table;
}

/**
 * Check if Member Tools plugin is active.
 *
 * @return bool True if Member Tools is active.
 */
function r2f_schengen_has_member_tools() {
	return defined( 'FRAMT_VERSION' ) || class_exists( 'FRAMT_Portal_API' );
}
