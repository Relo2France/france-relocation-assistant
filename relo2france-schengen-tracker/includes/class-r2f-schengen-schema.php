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
	const DB_VERSION = '1.5.0';

	/**
	 * Table definitions.
	 * Keys are used internally, values are table suffixes.
	 *
	 * @var array
	 */
	private static $tables = array(
		'trips'                => 'fra_schengen_trips', // Keep same name for backward compatibility.
		'location_log'         => 'fra_schengen_location_log',
		'calendar_connections' => 'fra_calendar_connections',
		'calendar_events'      => 'fra_calendar_events',
		'jurisdiction_rules'   => 'fra_jurisdiction_rules',
		'push_subscriptions'   => 'fra_push_subscriptions',
		'notifications'        => 'fra_notifications',
		'family_members'       => 'fra_schengen_family_members',
		'trip_travelers'       => 'fra_schengen_trip_travelers',
		'analytics_snapshots'  => 'fra_schengen_analytics',
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

		// Jurisdiction rules table (added in v1.3.0 for multi-jurisdiction support).
		$table_jurisdiction_rules = self::get_table( 'jurisdiction_rules' );
		$sql_jurisdiction_rules = "CREATE TABLE $table_jurisdiction_rules (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(20) NOT NULL,
			name varchar(100) NOT NULL,
			type varchar(20) NOT NULL DEFAULT 'zone',
			parent_code varchar(20) DEFAULT NULL,
			days_allowed int(11) NOT NULL,
			window_days int(11) NOT NULL,
			counting_method varchar(20) DEFAULT 'rolling',
			reset_month int(11) DEFAULT NULL,
			reset_day int(11) DEFAULT NULL,
			description text DEFAULT NULL,
			notes text DEFAULT NULL,
			countries text DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			is_system tinyint(1) DEFAULT 1,
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_code (code),
			KEY idx_type (type),
			KEY idx_active (is_active)
		) $charset_collate;";

		dbDelta( $sql_jurisdiction_rules );

		// Push subscriptions table (added in v1.4.0 for Web Push notifications).
		$table_push_subscriptions = self::get_table( 'push_subscriptions' );
		$sql_push_subscriptions = "CREATE TABLE $table_push_subscriptions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			endpoint text NOT NULL,
			p256dh_key varchar(255) DEFAULT NULL,
			auth_key varchar(255) DEFAULT NULL,
			user_agent varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_used_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_user (user_id)
		) $charset_collate;";

		dbDelta( $sql_push_subscriptions );

		// Notifications table (added in v1.4.0 for in-app notification center).
		$table_notifications = self::get_table( 'notifications' );
		$sql_notifications = "CREATE TABLE $table_notifications (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(50) NOT NULL,
			title varchar(255) NOT NULL,
			body text DEFAULT NULL,
			data longtext DEFAULT NULL,
			action_url varchar(500) DEFAULT NULL,
			icon varchar(50) DEFAULT NULL,
			priority varchar(20) DEFAULT 'normal',
			read_at datetime DEFAULT NULL,
			sent_push_at datetime DEFAULT NULL,
			sent_email_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_read (user_id, read_at),
			KEY idx_user_type (user_id, type),
			KEY idx_created (created_at)
		) $charset_collate;";

		dbDelta( $sql_notifications );

		// Family members table (added in v1.5.0 for family tracking).
		$table_family_members = self::get_table( 'family_members' );
		$sql_family_members = "CREATE TABLE $table_family_members (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			name varchar(100) NOT NULL,
			relationship varchar(20) NOT NULL DEFAULT 'spouse',
			birth_date date DEFAULT NULL,
			nationality varchar(100) DEFAULT NULL,
			passport_number varchar(50) DEFAULT NULL,
			passport_expiry date DEFAULT NULL,
			color varchar(7) DEFAULT '#3B82F6',
			avatar_url varchar(500) DEFAULT NULL,
			notes text DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			sort_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user (user_id),
			KEY idx_user_active (user_id, is_active)
		) $charset_collate;";

		dbDelta( $sql_family_members );

		// Trip travelers linking table (added in v1.5.0 for shared trips).
		$table_trip_travelers = self::get_table( 'trip_travelers' );
		$sql_trip_travelers = "CREATE TABLE $table_trip_travelers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			trip_id bigint(20) unsigned NOT NULL,
			family_member_id bigint(20) unsigned DEFAULT NULL,
			is_primary_user tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_trip_member (trip_id, family_member_id),
			KEY idx_family_member (family_member_id),
			KEY idx_trip (trip_id)
		) $charset_collate;";

		dbDelta( $sql_trip_travelers );

		// Analytics snapshots table (added in v1.5.0 for historical tracking).
		$table_analytics = self::get_table( 'analytics_snapshots' );
		$sql_analytics = "CREATE TABLE $table_analytics (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			family_member_id bigint(20) unsigned DEFAULT NULL,
			jurisdiction_code varchar(20) NOT NULL DEFAULT 'schengen',
			snapshot_date date NOT NULL,
			days_used int(11) NOT NULL DEFAULT 0,
			days_remaining int(11) NOT NULL DEFAULT 90,
			status varchar(20) NOT NULL DEFAULT 'safe',
			trip_count int(11) NOT NULL DEFAULT 0,
			window_start date NOT NULL,
			window_end date NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_user_date_jurisdiction (user_id, family_member_id, jurisdiction_code, snapshot_date),
			KEY idx_user_date (user_id, snapshot_date),
			KEY idx_family_member (family_member_id)
		) $charset_collate;";

		dbDelta( $sql_analytics );

		// Populate default jurisdiction rules if table is empty.
		self::maybe_populate_default_rules();

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

		// Migration from 1.2.x to 1.3.0: Add jurisdiction_code column to trips table.
		if ( version_compare( $current_version, '1.3.0', '<' ) ) {
			$table_trips = self::get_table( 'trips' );

			// Check if jurisdiction_code column exists.
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM $table_trips LIKE %s",
					'jurisdiction_code'
				)
			);

			if ( empty( $column_exists ) ) {
				// Add jurisdiction column - default to 'schengen' for existing trips.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_trips
					ADD COLUMN jurisdiction_code varchar(20) DEFAULT 'schengen' AFTER country"
				);

				// Add index for jurisdiction queries.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_trips
					ADD KEY idx_jurisdiction (jurisdiction_code)"
				);
			}
		}
	}

	/**
	 * Populate default jurisdiction rules if table is empty.
	 */
	private static function maybe_populate_default_rules() {
		global $wpdb;

		$table = self::get_table( 'jurisdiction_rules' );

		// Check if table has any rules.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		if ( $count > 0 ) {
			return;
		}

		// Default jurisdiction rules.
		$default_rules = array(
			// Schengen Zone (primary).
			array(
				'code'            => 'schengen',
				'name'            => 'Schengen Zone',
				'type'            => 'zone',
				'days_allowed'    => 90,
				'window_days'     => 180,
				'counting_method' => 'rolling',
				'description'     => 'Standard 90/180 rule for visa-free travel in the Schengen area.',
				'notes'           => 'Non-EU citizens may stay up to 90 days within any 180-day period.',
				'is_system'       => 1,
				'display_order'   => 1,
			),
			// UK Visitor.
			array(
				'code'            => 'uk_visitor',
				'name'            => 'UK Standard Visitor',
				'type'            => 'country',
				'days_allowed'    => 180,
				'window_days'     => 365,
				'counting_method' => 'rolling',
				'description'     => 'UK Standard Visitor visa allows up to 180 days per visit.',
				'notes'           => 'Maximum 180 days in any 12-month period. Multiple entries allowed.',
				'is_system'       => 1,
				'display_order'   => 2,
			),
			// US Visa Waiver Program (ESTA).
			array(
				'code'            => 'us_vwp',
				'name'            => 'US Visa Waiver (ESTA)',
				'type'            => 'country',
				'days_allowed'    => 90,
				'window_days'     => 180,
				'counting_method' => 'rolling',
				'description'     => 'US Visa Waiver Program allows up to 90 days per visit.',
				'notes'           => '90 days includes time in Canada, Mexico, and Caribbean. No extensions allowed.',
				'is_system'       => 1,
				'display_order'   => 3,
			),
			// US B1/B2 Visa.
			array(
				'code'            => 'us_b1b2',
				'name'            => 'US B1/B2 Visa',
				'type'            => 'country',
				'days_allowed'    => 180,
				'window_days'     => 365,
				'counting_method' => 'rolling',
				'description'     => 'US B1/B2 visa typically allows up to 180 days per visit.',
				'notes'           => 'Actual stay determined by CBP officer. Extensions possible.',
				'is_system'       => 1,
				'display_order'   => 4,
			),
			// US State: New York.
			array(
				'code'            => 'us_ny',
				'name'            => 'New York State',
				'type'            => 'state',
				'parent_code'     => 'us',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'New York statutory residency threshold.',
				'notes'           => '183+ days AND permanent place of abode triggers statutory residency.',
				'is_system'       => 1,
				'display_order'   => 10,
			),
			// US State: California.
			array(
				'code'            => 'us_ca',
				'name'            => 'California',
				'type'            => 'state',
				'parent_code'     => 'us',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'California residency presumption threshold.',
				'notes'           => 'No bright-line test. 183+ days creates presumption of residency.',
				'is_system'       => 1,
				'display_order'   => 11,
			),
			// US State: Florida.
			array(
				'code'            => 'us_fl',
				'name'            => 'Florida',
				'type'            => 'state',
				'parent_code'     => 'us',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Florida residency threshold.',
				'notes'           => 'Domicile + 183 days physical presence for tax residency.',
				'is_system'       => 1,
				'display_order'   => 12,
			),
			// US State: Texas.
			array(
				'code'            => 'us_tx',
				'name'            => 'Texas',
				'type'            => 'state',
				'parent_code'     => 'us',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Texas residency threshold.',
				'notes'           => 'No state income tax, but residency matters for other purposes.',
				'is_system'       => 1,
				'display_order'   => 13,
			),
			// Canada Visitor.
			array(
				'code'            => 'ca_visitor',
				'name'            => 'Canada Visitor',
				'type'            => 'country',
				'days_allowed'    => 180,
				'window_days'     => 365,
				'counting_method' => 'rolling',
				'description'     => 'Canada allows visitors to stay up to 180 days.',
				'notes'           => '6 months per visit. Extensions available in some cases.',
				'is_system'       => 1,
				'display_order'   => 5,
			),
		);

		foreach ( $default_rules as $rule ) {
			$wpdb->insert(
				$table,
				array(
					'code'            => $rule['code'],
					'name'            => $rule['name'],
					'type'            => $rule['type'],
					'parent_code'     => isset( $rule['parent_code'] ) ? $rule['parent_code'] : null,
					'days_allowed'    => $rule['days_allowed'],
					'window_days'     => $rule['window_days'],
					'counting_method' => $rule['counting_method'],
					'reset_month'     => isset( $rule['reset_month'] ) ? $rule['reset_month'] : null,
					'reset_day'       => isset( $rule['reset_day'] ) ? $rule['reset_day'] : null,
					'description'     => $rule['description'],
					'notes'           => isset( $rule['notes'] ) ? $rule['notes'] : null,
					'is_system'       => $rule['is_system'],
					'display_order'   => $rule['display_order'],
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%d', '%d' )
			);
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
		$table_jurisdiction_rules   = self::get_table( 'jurisdiction_rules' );
		$table_push_subscriptions   = self::get_table( 'push_subscriptions' );
		$table_notifications        = self::get_table( 'notifications' );
		$table_family_members       = self::get_table( 'family_members' );
		$table_trip_travelers       = self::get_table( 'trip_travelers' );
		$table_analytics            = self::get_table( 'analytics_snapshots' );

		// Drop in order (child tables first due to foreign key relationships).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_analytics" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_trip_travelers" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_family_members" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_notifications" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_push_subscriptions" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_calendar_events" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_calendar_connections" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_jurisdiction_rules" );
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
