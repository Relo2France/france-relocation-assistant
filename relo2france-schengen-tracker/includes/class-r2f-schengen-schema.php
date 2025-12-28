<?php
/**
 * Database schema for Schengen Tracker.
 *
 * Creates and manages the schengen_trips table.
 * Uses the same table name (fra_schengen_trips) for backward compatibility
 * with Member Tools plugin data.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema management class.
 */
class R2F_Schengen_Schema {

	/**
	 * Database version for migrations.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.2.0';

	/**
	 * Table definitions.
	 * Keys are used internally, values are table suffixes.
	 *
	 * @var array
	 */
	private static $tables = array(
		'trips'               => 'fra_schengen_trips', // Keep same name for backward compatibility.
		'location_log'        => 'fra_schengen_location_log',
		'calendar_connections' => 'fra_calendar_connections',
		'calendar_events'      => 'fra_calendar_events',
	);

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * @param string $table Table key (e.g., 'trips').
	 * @return string Full table name with prefix.
	 */
	public static function get_table( $table ) {
		global $wpdb;

		if ( ! isset( self::$tables[ $table ] ) ) {
			return $wpdb->prefix . 'fra_schengen_' . $table;
		}

		return $wpdb->prefix . self::$tables[ $table ];
	}

	/**
	 * Create all database tables.
	 *
	 * Uses dbDelta for safe table creation/updates.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Schengen trips table (with location columns added in v1.1.0).
		$table_trips = self::get_table( 'trips' );
		$sql_trips = "CREATE TABLE $table_trips (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			country varchar(100) NOT NULL,
			category varchar(20) DEFAULT 'personal',
			notes text,
			location_source varchar(20) DEFAULT 'manual',
			location_lat decimal(10,8) DEFAULT NULL,
			location_lng decimal(11,8) DEFAULT NULL,
			location_accuracy float DEFAULT NULL,
			location_timestamp datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY start_date (start_date),
			KEY end_date (end_date)
		) $charset_collate;";

		dbDelta( $sql_trips );

		// Location log table (added in v1.1.0 for location history tracking).
		$table_location_log = self::get_table( 'location_log' );
		$sql_location_log = "CREATE TABLE $table_location_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			lat decimal(10,8) NOT NULL,
			lng decimal(11,8) NOT NULL,
			accuracy float DEFAULT NULL,
			country_code varchar(2) DEFAULT NULL,
			country_name varchar(100) DEFAULT NULL,
			city varchar(100) DEFAULT NULL,
			is_schengen tinyint(1) DEFAULT 0,
			source varchar(20) DEFAULT 'browser',
			recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_date (user_id, recorded_at),
			KEY idx_user_country (user_id, country_code)
		) $charset_collate;";

		dbDelta( $sql_location_log );

		// Calendar connections table (added in v1.2.0 for calendar sync).
		$table_calendar_connections = self::get_table( 'calendar_connections' );
		$sql_calendar_connections = "CREATE TABLE $table_calendar_connections (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			provider varchar(20) NOT NULL,
			access_token text DEFAULT NULL,
			refresh_token text DEFAULT NULL,
			token_expires_at datetime DEFAULT NULL,
			calendar_id varchar(255) DEFAULT NULL,
			calendar_name varchar(255) DEFAULT NULL,
			last_sync_at datetime DEFAULT NULL,
			sync_status varchar(20) DEFAULT 'active',
			settings longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_user_provider (user_id, provider),
			KEY idx_sync_status (sync_status)
		) $charset_collate;";

		dbDelta( $sql_calendar_connections );

		// Calendar events table (added in v1.2.0 for detected travel events).
		$table_calendar_events = self::get_table( 'calendar_events' );
		$sql_calendar_events = "CREATE TABLE $table_calendar_events (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			connection_id bigint(20) unsigned NOT NULL,
			external_event_id varchar(255) DEFAULT NULL,
			title varchar(500) DEFAULT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			location varchar(500) DEFAULT NULL,
			detected_country varchar(100) DEFAULT NULL,
			detected_country_code varchar(2) DEFAULT NULL,
			is_schengen tinyint(1) DEFAULT 0,
			imported_as_trip_id bigint(20) unsigned DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			confidence_score float DEFAULT NULL,
			raw_data longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_status (user_id, status),
			KEY idx_connection (connection_id),
			KEY idx_external_id (external_event_id),
			KEY idx_dates (start_date, end_date)
		) $charset_collate;";

		dbDelta( $sql_calendar_events );

		// Run migrations for existing installations.
		self::maybe_migrate();

		// Store database version.
		update_option( 'r2f_schengen_db_version', self::DB_VERSION );

		/**
		 * Fires after database tables are created/updated.
		 *
		 * @param string $db_version The database version.
		 */
		do_action( 'r2f_schengen_tables_created', self::DB_VERSION );
	}

	/**
	 * Run database migrations if needed.
	 */
	private static function maybe_migrate() {
		global $wpdb;

		$current_version = get_option( 'r2f_schengen_db_version', '1.0.0' );

		// Migration from 1.0.0 to 1.1.0: Add location columns to trips table.
		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			$table_trips = self::get_table( 'trips' );

			// Check if location_source column exists.
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM $table_trips LIKE %s",
					'location_source'
				)
			);

			if ( empty( $column_exists ) ) {
				// Add location columns.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_trips
					ADD COLUMN location_source varchar(20) DEFAULT 'manual' AFTER notes,
					ADD COLUMN location_lat decimal(10,8) DEFAULT NULL AFTER location_source,
					ADD COLUMN location_lng decimal(11,8) DEFAULT NULL AFTER location_lat,
					ADD COLUMN location_accuracy float DEFAULT NULL AFTER location_lng,
					ADD COLUMN location_timestamp datetime DEFAULT NULL AFTER location_accuracy"
				);
			}
		}
	}

	/**
	 * Check if tables exist.
	 *
	 * @return bool True if all tables exist.
	 */
	public static function tables_exist() {
		global $wpdb;

		$table = self::get_table( 'trips' );
		$result = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table
		) );

		return $table === $result;
	}

	/**
	 * Drop all plugin tables.
	 *
	 * WARNING: This permanently deletes all data!
	 * Only used during uninstall if user opts to remove data.
	 */
	public static function drop_tables() {
		global $wpdb;

		// Safety check: only allow if explicitly enabled.
		$remove_data = get_option( 'r2f_schengen_remove_data_on_uninstall', '0' );
		if ( '1' !== $remove_data ) {
			return;
		}

		$table_trips                = self::get_table( 'trips' );
		$table_location_log         = self::get_table( 'location_log' );
		$table_calendar_connections = self::get_table( 'calendar_connections' );
		$table_calendar_events      = self::get_table( 'calendar_events' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_calendar_events" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_calendar_connections" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_trips" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_location_log" );

		// Remove options.
		delete_option( 'r2f_schengen_version' );
		delete_option( 'r2f_schengen_db_version' );
		delete_option( 'r2f_schengen_global_enabled' );
		delete_option( 'r2f_schengen_upgrade_url' );
		delete_option( 'r2f_schengen_remove_data_on_uninstall' );

		/**
		 * Fires after database tables are dropped.
		 */
		do_action( 'r2f_schengen_tables_dropped' );
	}

	/**
	 * Get list of Schengen countries.
	 *
	 * @return array Array of country codes and names.
	 */
	public static function get_schengen_countries() {
		return array(
			'AT' => __( 'Austria', 'r2f-schengen' ),
			'BE' => __( 'Belgium', 'r2f-schengen' ),
			'BG' => __( 'Bulgaria', 'r2f-schengen' ),
			'HR' => __( 'Croatia', 'r2f-schengen' ),
			'CZ' => __( 'Czech Republic', 'r2f-schengen' ),
			'DK' => __( 'Denmark', 'r2f-schengen' ),
			'EE' => __( 'Estonia', 'r2f-schengen' ),
			'FI' => __( 'Finland', 'r2f-schengen' ),
			'FR' => __( 'France', 'r2f-schengen' ),
			'DE' => __( 'Germany', 'r2f-schengen' ),
			'GR' => __( 'Greece', 'r2f-schengen' ),
			'HU' => __( 'Hungary', 'r2f-schengen' ),
			'IS' => __( 'Iceland', 'r2f-schengen' ),
			'IT' => __( 'Italy', 'r2f-schengen' ),
			'LV' => __( 'Latvia', 'r2f-schengen' ),
			'LI' => __( 'Liechtenstein', 'r2f-schengen' ),
			'LT' => __( 'Lithuania', 'r2f-schengen' ),
			'LU' => __( 'Luxembourg', 'r2f-schengen' ),
			'MT' => __( 'Malta', 'r2f-schengen' ),
			'NL' => __( 'Netherlands', 'r2f-schengen' ),
			'NO' => __( 'Norway', 'r2f-schengen' ),
			'PL' => __( 'Poland', 'r2f-schengen' ),
			'PT' => __( 'Portugal', 'r2f-schengen' ),
			'RO' => __( 'Romania', 'r2f-schengen' ),
			'SK' => __( 'Slovakia', 'r2f-schengen' ),
			'SI' => __( 'Slovenia', 'r2f-schengen' ),
			'ES' => __( 'Spain', 'r2f-schengen' ),
			'SE' => __( 'Sweden', 'r2f-schengen' ),
			'CH' => __( 'Switzerland', 'r2f-schengen' ),
		);
	}

	/**
	 * Check if a country is in the Schengen zone.
	 *
	 * @param string $country Country name or code.
	 * @return bool True if country is in Schengen zone.
	 */
	public static function is_schengen_country( $country ) {
		$countries = self::get_schengen_countries();

		// Check if it's a code.
		if ( isset( $countries[ strtoupper( $country ) ] ) ) {
			return true;
		}

		// Check if it's a name.
		return in_array( $country, $countries, true );
	}
}
