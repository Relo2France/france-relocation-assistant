<?php
/**
 * Database schema for MyTravelStatus.
 *
 * Creates and manages the schengen_trips table.
 * Uses the same table name (mts_trips) for backward compatibility
 * with Member Tools plugin data.
 *
 * @package MTS_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema management class.
 */
class MTS_Schema {

	/**
	 * Database version for migrations.
	 *
	 * @var string
	 */
	const DB_VERSION = '1.8.1';

	/**
	 * Table definitions.
	 * Keys are used internally, values are table suffixes.
	 *
	 * @var array
	 */
	private static $tables = array(
		'trips'                 => 'mts_trips', // Keep same name for backward compatibility.
		'location_log'          => 'mts_location_log',
		'calendar_connections'  => 'mts_calendar_connections',
		'calendar_events'       => 'mts_calendar_events',
		'jurisdiction_rules'    => 'mts_jurisdiction_rules',
		'user_jurisdictions'    => 'mts_user_jurisdictions', // Added in v1.7.0 for user jurisdiction settings.
		'compliance_snapshots'  => 'mts_compliance_snapshots', // Added in v1.7.0 for compliance history.
		'uk_ties'               => 'mts_uk_ties', // Added in v1.8.0 for UK SRT ties tracking.
		'push_subscriptions'    => 'mts_push_subscriptions',
		'notifications'         => 'mts_notifications',
		'family_members'        => 'mts_family_members',
		'devices'               => 'mts_devices', // Added in v1.6.0 for mobile app.
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
			return $wpdb->prefix . 'mts_' . $table;
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

		// Jurisdiction rules table (added in v1.3.0, expanded in v1.7.0 for tax residency).
		$table_jurisdiction_rules = self::get_table( 'jurisdiction_rules' );
		$sql_jurisdiction_rules = "CREATE TABLE $table_jurisdiction_rules (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(20) NOT NULL,
			name varchar(100) NOT NULL,
			type varchar(20) NOT NULL DEFAULT 'zone',
			category varchar(20) DEFAULT 'visa',
			parent_code varchar(20) DEFAULT NULL,
			days_allowed int(11) NOT NULL,
			window_days int(11) NOT NULL,
			counting_method varchar(30) DEFAULT 'rolling',
			reset_month int(11) DEFAULT NULL,
			reset_day int(11) DEFAULT NULL,
			description text DEFAULT NULL,
			notes text DEFAULT NULL,
			countries text DEFAULT NULL,
			rule_config longtext DEFAULT NULL,
			country_code varchar(2) DEFAULT NULL,
			flag_emoji varchar(10) DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			is_system tinyint(1) DEFAULT 1,
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_code (code),
			KEY idx_type (type),
			KEY idx_category (category),
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
			relationship varchar(50) DEFAULT NULL,
			nationality varchar(100) DEFAULT NULL,
			passport_country varchar(100) DEFAULT NULL,
			date_of_birth date DEFAULT NULL,
			notes text DEFAULT NULL,
			color varchar(20) DEFAULT '#3B82F6',
			is_active tinyint(1) DEFAULT 1,
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user (user_id),
			KEY idx_active (user_id, is_active)
		) $charset_collate;";

		dbDelta( $sql_family_members );

		// Devices table (added in v1.6.0 for mobile app push notifications).
		$table_devices = self::get_table( 'devices' );
		$sql_devices = "CREATE TABLE $table_devices (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			device_id varchar(255) NOT NULL,
			push_token text DEFAULT NULL,
			platform varchar(20) NOT NULL,
			app_version varchar(20) DEFAULT NULL,
			device_name varchar(100) DEFAULT NULL,
			os_version varchar(50) DEFAULT NULL,
			last_sync datetime DEFAULT NULL,
			last_active datetime DEFAULT NULL,
			is_active tinyint(1) DEFAULT 1,
			settings longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_device_id (device_id),
			KEY idx_user (user_id),
			KEY idx_platform (platform),
			KEY idx_active (user_id, is_active)
		) $charset_collate;";

		dbDelta( $sql_devices );

		// User jurisdictions table (added in v1.7.0 for user-specific jurisdiction settings).
		$table_user_jurisdictions = self::get_table( 'user_jurisdictions' );
		$sql_user_jurisdictions = "CREATE TABLE $table_user_jurisdictions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			jurisdiction_code varchar(20) NOT NULL,
			enabled tinyint(1) DEFAULT 1,
			alert_threshold int(11) DEFAULT 80,
			custom_config longtext DEFAULT NULL,
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_user_jurisdiction (user_id, jurisdiction_code),
			KEY idx_user_enabled (user_id, enabled)
		) $charset_collate;";

		dbDelta( $sql_user_jurisdictions );

		// Compliance snapshots table (added in v1.7.0 for compliance history tracking).
		$table_compliance_snapshots = self::get_table( 'compliance_snapshots' );
		$sql_compliance_snapshots = "CREATE TABLE $table_compliance_snapshots (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			jurisdiction_code varchar(20) NOT NULL,
			snapshot_date date NOT NULL,
			days_used int(11) NOT NULL,
			days_remaining int(11) NOT NULL,
			status varchar(20) NOT NULL,
			calculation_data longtext NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user_jurisdiction_date (user_id, jurisdiction_code, snapshot_date),
			KEY idx_user_date (user_id, snapshot_date)
		) $charset_collate;";

		dbDelta( $sql_compliance_snapshots );

		// UK Ties table (added in v1.8.0 for UK SRT tracking).
		$table_uk_ties = self::get_table( 'uk_ties' );
		$sql_uk_ties = "CREATE TABLE $table_uk_ties (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			tax_year int(4) NOT NULL,
			family_tie tinyint(1) NOT NULL DEFAULT 0,
			family_tie_details text DEFAULT NULL,
			accommodation_tie tinyint(1) NOT NULL DEFAULT 0,
			accommodation_tie_details text DEFAULT NULL,
			work_tie tinyint(1) NOT NULL DEFAULT 0,
			work_tie_details text DEFAULT NULL,
			ninety_day_tie tinyint(1) NOT NULL DEFAULT 0,
			country_tie tinyint(1) NOT NULL DEFAULT 0,
			only_home_in_uk tinyint(1) NOT NULL DEFAULT 0,
			full_time_work_uk tinyint(1) NOT NULL DEFAULT 0,
			leaving_uk_permanently tinyint(1) NOT NULL DEFAULT 0,
			srt_result varchar(50) DEFAULT NULL,
			notes text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY idx_user_tax_year (user_id, tax_year),
			KEY idx_user_id (user_id)
		) $charset_collate;";

		dbDelta( $sql_uk_ties );

		// Populate default jurisdiction rules if table is empty.
		self::maybe_populate_default_rules();

		// Run migrations for existing installations.
		self::maybe_migrate();

		// Store database version.
		update_option( 'mts_db_version', self::DB_VERSION );

		/**
		 * Fires after database tables are created/updated.
		 *
		 * @param string $db_version The database version.
		 */
		do_action( 'mts_tables_created', self::DB_VERSION );
	}

	/**
	 * Run database migrations if needed.
	 */
	private static function maybe_migrate() {
		global $wpdb;

		$current_version = get_option( 'mts_db_version', '1.0.0' );

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

		// Migration from 1.4.x to 1.5.0: Add family_member_id column to trips table.
		if ( version_compare( $current_version, '1.5.0', '<' ) ) {
			$table_trips = self::get_table( 'trips' );

			// Check if family_member_id column exists.
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM $table_trips LIKE %s",
					'family_member_id'
				)
			);

			if ( empty( $column_exists ) ) {
				// Add family_member_id column - NULL means the primary account holder.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_trips
					ADD COLUMN family_member_id bigint(20) unsigned DEFAULT NULL AFTER user_id"
				);

				// Add index for family member queries.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_trips
					ADD KEY idx_family_member (family_member_id)"
				);
			}
		}

		// Migration from 1.6.x to 1.7.0: Add tax residency columns to jurisdiction_rules.
		if ( version_compare( $current_version, '1.7.0', '<' ) ) {
			$table_rules = self::get_table( 'jurisdiction_rules' );

			// Check if category column exists.
			$column_exists = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM $table_rules LIKE %s",
					'category'
				)
			);

			if ( empty( $column_exists ) ) {
				// Add new columns for tax residency support.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_rules
					ADD COLUMN category varchar(20) DEFAULT 'visa' AFTER type,
					ADD COLUMN rule_config longtext DEFAULT NULL AFTER countries,
					ADD COLUMN country_code varchar(2) DEFAULT NULL AFTER rule_config,
					ADD COLUMN flag_emoji varchar(10) DEFAULT NULL AFTER country_code"
				);

				// Add index for category queries.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query(
					"ALTER TABLE $table_rules
					ADD KEY idx_category (category)"
				);

				// Update existing Schengen rule with flag.
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->update(
					$table_rules,
					array( 'flag_emoji' => '🇪🇺' ),
					array( 'code' => 'schengen' ),
					array( '%s' ),
					array( '%s' )
				);
			}

			// Add tax residency rules.
			self::maybe_populate_tax_residency_rules();
		}

		// Migration from 1.7.0 to 1.7.1: Add multi-factor rule_config to DE, IT, NL.
		if ( version_compare( $current_version, '1.7.1', '<' ) ) {
			self::maybe_update_multi_factor_rules();
		}

		// Migration from 1.8.0 to 1.8.1: Add multi-factor rule_config to Canada.
		if ( version_compare( $current_version, '1.8.1', '<' ) ) {
			self::maybe_update_canada_multi_factor();
		}
	}

	/**
	 * Update existing rules with multi-factor configurations (v1.7.1).
	 * For Germany, Italy, Netherlands.
	 */
	private static function maybe_update_multi_factor_rules() {
		global $wpdb;

		$table = self::get_table( 'jurisdiction_rules' );

		// Germany multi-factor config.
		$de_config = wp_json_encode( array(
			'multi_factor'         => true,
			'factors'              => array(
				array(
					'id'          => 'permanent_home',
					'label'       => 'Permanent Home in Germany',
					'description' => 'Do you maintain a permanent home (Wohnung) in Germany that is available for your use?',
					'weight'      => 1,
				),
				array(
					'id'          => 'habitual_abode',
					'label'       => 'Habitual Abode',
					'description' => 'Have you stayed in Germany for more than 6 consecutive months (habitual abode)?',
					'weight'      => 1,
				),
			),
			'factor_logic'         => 'any',
			'day_threshold_applies' => false,
		) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			array( 'rule_config' => $de_config ),
			array( 'code' => 'de_tax' ),
			array( '%s' ),
			array( '%s' )
		);

		// Italy multi-factor config.
		$it_config = wp_json_encode( array(
			'multi_factor'         => true,
			'factors'              => array(
				array(
					'id'          => 'registered_residence',
					'label'       => 'Registered Residence (Anagrafe)',
					'description' => 'Are you registered in the Italian civil registry (Anagrafe) as a resident?',
					'weight'      => 1,
				),
				array(
					'id'          => 'domicile',
					'label'       => 'Domicile in Italy',
					'description' => 'Is Italy the center of your vital interests (family, economic, social)?',
					'weight'      => 1,
				),
				array(
					'id'          => 'presence_183',
					'label'       => '183+ Days Presence',
					'description' => 'Have you been physically present in Italy for 183+ days this calendar year?',
					'weight'      => 1,
				),
			),
			'factor_logic'         => 'any',
			'day_threshold_applies' => true,
		) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			array( 'rule_config' => $it_config ),
			array( 'code' => 'it_tax' ),
			array( '%s' ),
			array( '%s' )
		);

		// Netherlands multi-factor config.
		$nl_config = wp_json_encode( array(
			'multi_factor'         => true,
			'factors'              => array(
				array(
					'id'          => 'permanent_home',
					'label'       => 'Permanent Home',
					'description' => 'Do you have a permanent home (duurzaam tehuis) available in the Netherlands?',
					'weight'      => 1,
				),
				array(
					'id'          => 'vital_interests',
					'label'       => 'Center of Vital Interests',
					'description' => 'Are your personal and economic ties (family, work, investments) centered in the Netherlands?',
					'weight'      => 1,
				),
				array(
					'id'          => 'habitual_abode',
					'label'       => 'Habitual Abode',
					'description' => 'Is the Netherlands where you habitually live (gewoonlijke verblijfplaats)?',
					'weight'      => 1,
				),
			),
			'factor_logic'         => 'weighted',
			'day_threshold_applies' => false,
		) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			array( 'rule_config' => $nl_config ),
			array( 'code' => 'nl_tax' ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Update Canada rule with multi-factor configuration (v1.8.1).
	 * Canadian significant residential ties for tax residency.
	 */
	private static function maybe_update_canada_multi_factor() {
		global $wpdb;

		$table = self::get_table( 'jurisdiction_rules' );

		// Canada multi-factor config with significant residential ties.
		$ca_config = wp_json_encode( array(
			'multi_factor'          => true,
			'factors'               => array(
				array(
					'id'          => 'dwelling',
					'label'       => 'Home in Canada',
					'description' => 'Do you maintain a dwelling (house, apartment, leased accommodation) available for your use in Canada?',
					'weight'      => 1,
				),
				array(
					'id'          => 'spouse_partner',
					'label'       => 'Spouse/Partner in Canada',
					'description' => 'Does your spouse or common-law partner reside in Canada?',
					'weight'      => 1,
				),
				array(
					'id'          => 'dependents',
					'label'       => 'Dependents in Canada',
					'description' => 'Do you have dependents (minor children) residing in Canada?',
					'weight'      => 1,
				),
				array(
					'id'          => 'personal_property',
					'label'       => 'Personal Property',
					'description' => 'Do you have substantial personal property in Canada (furniture, vehicle, etc.)?',
					'weight'      => 0.5,
				),
				array(
					'id'          => 'social_ties',
					'label'       => 'Social/Economic Ties',
					'description' => 'Do you have significant social ties (memberships, clubs) or economic ties (bank accounts, business interests) in Canada?',
					'weight'      => 0.5,
				),
			),
			'factor_logic'          => 'any',
			'day_threshold_applies' => true,
		) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$table,
			array( 'rule_config' => $ca_config ),
			array( 'code' => 'ca_tax' ),
			array( '%s' ),
			array( '%s' )
		);
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
	 * Populate tax residency jurisdiction rules (added in v1.7.0).
	 * These are distinct from visa/visitor rules.
	 */
	private static function maybe_populate_tax_residency_rules() {
		global $wpdb;

		$table = self::get_table( 'jurisdiction_rules' );

		// Tax residency rules to add.
		$tax_rules = array(
			// France 183-day tax residency.
			array(
				'code'            => 'fr_tax',
				'name'            => 'France Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'French tax residency threshold (183-day rule).',
				'notes'           => '183+ days in France during a calendar year may trigger French tax residency. Other factors include principal residence (foyer), professional activity, and center of economic interests.',
				'country_code'    => 'FR',
				'flag_emoji'      => '🇫🇷',
				'is_system'       => 1,
				'display_order'   => 100,
			),
			// Spain 183-day tax residency.
			array(
				'code'            => 'es_tax',
				'name'            => 'Spain Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Spanish tax residency threshold (183-day rule).',
				'notes'           => '183+ days in Spain during a calendar year triggers tax residency. Includes days of arrival and departure.',
				'country_code'    => 'ES',
				'flag_emoji'      => '🇪🇸',
				'is_system'       => 1,
				'display_order'   => 101,
			),
			// Portugal 183-day tax residency.
			array(
				'code'            => 'pt_tax',
				'name'            => 'Portugal Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Portuguese tax residency threshold (183-day rule).',
				'notes'           => '183+ days in Portugal during any 12-month period starting or ending in the tax year triggers residency.',
				'country_code'    => 'PT',
				'flag_emoji'      => '🇵🇹',
				'is_system'       => 1,
				'display_order'   => 102,
			),
			// Germany 183-day tax residency (multi-factor).
			array(
				'code'            => 'de_tax',
				'name'            => 'Germany Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'German tax residency threshold.',
				'notes'           => 'Residency primarily determined by habitual abode (more than 6 months) or permanent home in Germany.',
				'rule_config'     => '{"multi_factor":true,"factors":[{"id":"permanent_home","label":"Permanent Home in Germany","description":"Do you maintain a permanent home (Wohnung) in Germany that is available for your use?","weight":1},{"id":"habitual_abode","label":"Habitual Abode","description":"Have you stayed in Germany for more than 6 consecutive months (habitual abode)?","weight":1}],"factor_logic":"any","day_threshold_applies":false}',
				'country_code'    => 'DE',
				'flag_emoji'      => '🇩🇪',
				'is_system'       => 1,
				'display_order'   => 103,
			),
			// Italy 183-day tax residency (multi-factor).
			array(
				'code'            => 'it_tax',
				'name'            => 'Italy Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Italian tax residency threshold.',
				'notes'           => 'Tax residency triggered by: registered residence in Italy, domicile (center of interests), OR 183+ days presence.',
				'rule_config'     => '{"multi_factor":true,"factors":[{"id":"registered_residence","label":"Registered Residence (Anagrafe)","description":"Are you registered in the Italian civil registry (Anagrafe) as a resident?","weight":1},{"id":"domicile","label":"Domicile in Italy","description":"Is Italy the center of your vital interests (family, economic, social)?","weight":1},{"id":"presence_183","label":"183+ Days Presence","description":"Have you been physically present in Italy for 183+ days this calendar year?","weight":1}],"factor_logic":"any","day_threshold_applies":true}',
				'country_code'    => 'IT',
				'flag_emoji'      => '🇮🇹',
				'is_system'       => 1,
				'display_order'   => 104,
			),
			// Netherlands 183-day tax residency (multi-factor).
			array(
				'code'            => 'nl_tax',
				'name'            => 'Netherlands Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Dutch tax residency threshold.',
				'notes'           => 'Residency based on permanent home, vital interests, and habitual abode. 183 days is an indicator but not determinative.',
				'rule_config'     => '{"multi_factor":true,"factors":[{"id":"permanent_home","label":"Permanent Home","description":"Do you have a permanent home (duurzaam tehuis) available in the Netherlands?","weight":1},{"id":"vital_interests","label":"Center of Vital Interests","description":"Are your personal and economic ties (family, work, investments) centered in the Netherlands?","weight":1},{"id":"habitual_abode","label":"Habitual Abode","description":"Is the Netherlands where you habitually live (gewoonlijke verblijfplaats)?","weight":1}],"factor_logic":"weighted","day_threshold_applies":false}',
				'country_code'    => 'NL',
				'flag_emoji'      => '🇳🇱',
				'is_system'       => 1,
				'display_order'   => 105,
			),
			// Ireland 183/280 tax residency.
			array(
				'code'            => 'ie_tax',
				'name'            => 'Ireland Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'multi_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Irish tax residency (183/280-day rule).',
				'notes'           => 'Tax resident if: 183+ days in current year, OR 280+ days combined over current and previous year (min 31 days each year).',
				'rule_config'     => '{"secondary_threshold":280,"secondary_years":2,"min_days_per_year":31}',
				'country_code'    => 'IE',
				'flag_emoji'      => '🇮🇪',
				'is_system'       => 1,
				'display_order'   => 106,
			),
			// US Substantial Presence Test.
			array(
				'code'            => 'us_spt',
				'name'            => 'US Substantial Presence Test',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 1095, // 3 years
				'counting_method' => 'weighted_multi_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'US Substantial Presence Test for tax residency.',
				'notes'           => 'SPT calculation: Current year days × 1 + Prior year × 1/3 + Second prior × 1/6. Must be 31+ days in current year AND total ≥ 183.',
				'rule_config'     => '{"current_year_weight":1,"prior_year_weight":0.333,"second_prior_weight":0.167,"min_current_year_days":31}',
				'country_code'    => 'US',
				'flag_emoji'      => '🇺🇸',
				'is_system'       => 1,
				'display_order'   => 110,
			),
			// Mexico 183-day tax residency.
			array(
				'code'            => 'mx_tax',
				'name'            => 'Mexico Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Mexican tax residency threshold.',
				'notes'           => 'Tax resident if primary home in Mexico OR 183+ days present in calendar year.',
				'country_code'    => 'MX',
				'flag_emoji'      => '🇲🇽',
				'is_system'       => 1,
				'display_order'   => 111,
			),
			// Japan 183-day tax residency.
			array(
				'code'            => 'jp_tax',
				'name'            => 'Japan Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Japanese tax residency threshold.',
				'notes'           => 'Resident status based on domicile (jusho) or residence for 1+ year. 183 days is a reference point.',
				'country_code'    => 'JP',
				'flag_emoji'      => '🇯🇵',
				'is_system'       => 1,
				'display_order'   => 112,
			),
			// Singapore 183-day tax residency.
			array(
				'code'            => 'sg_tax',
				'name'            => 'Singapore Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Singapore tax residency threshold.',
				'notes'           => 'Tax resident if physically present 183+ days in calendar year, OR employed in Singapore (excluding director).',
				'country_code'    => 'SG',
				'flag_emoji'      => '🇸🇬',
				'is_system'       => 1,
				'display_order'   => 113,
			),
			// Australia 183-day tax residency.
			array(
				'code'            => 'au_tax',
				'name'            => 'Australia Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'fiscal_year',
				'reset_month'     => 7,
				'reset_day'       => 1,
				'description'     => 'Australian tax residency (fiscal year July-June).',
				'notes'           => 'Residency determined by: resides test, domicile test, 183-day test, or superannuation test. Complex multi-factor assessment.',
				'country_code'    => 'AU',
				'flag_emoji'      => '🇦🇺',
				'is_system'       => 1,
				'display_order'   => 114,
			),
			// New Zealand 183-day tax residency.
			array(
				'code'            => 'nz_tax',
				'name'            => 'New Zealand Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'rolling',
				'description'     => 'New Zealand tax residency (183-day rolling).',
				'notes'           => 'Tax resident if present 183+ days in any 12-month period, OR have a permanent place of abode.',
				'country_code'    => 'NZ',
				'flag_emoji'      => '🇳🇿',
				'is_system'       => 1,
				'display_order'   => 115,
			),
			// Canada 183-day tax residency (multi-factor with significant ties).
			array(
				'code'            => 'ca_tax',
				'name'            => 'Canada Tax Residency',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Canadian tax residency threshold.',
				'notes'           => 'Residency determined by significant residential ties (home, spouse, dependents). 183+ days creates deemed residency for that year regardless of ties.',
				'rule_config'     => '{"multi_factor":true,"factors":[{"id":"dwelling","label":"Home in Canada","description":"Do you maintain a dwelling (house, apartment, leased accommodation) available for your use in Canada?","weight":1},{"id":"spouse_partner","label":"Spouse/Partner in Canada","description":"Does your spouse or common-law partner reside in Canada?","weight":1},{"id":"dependents","label":"Dependents in Canada","description":"Do you have dependents (minor children) residing in Canada?","weight":1},{"id":"personal_property","label":"Personal Property","description":"Do you have substantial personal property in Canada (furniture, vehicle, etc.)?","weight":0.5},{"id":"social_ties","label":"Social/Economic Ties","description":"Do you have significant social ties (memberships, clubs) or economic ties (bank accounts, business interests) in Canada?","weight":0.5}],"factor_logic":"any","day_threshold_applies":true}',
				'country_code'    => 'CA',
				'flag_emoji'      => '🇨🇦',
				'is_system'       => 1,
				'display_order'   => 116,
			),
			// UK Statutory Residence Test.
			array(
				'code'            => 'uk_srt',
				'name'            => 'UK Statutory Residence Test',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'uk_srt',
				'reset_month'     => 4,
				'reset_day'       => 6,
				'description'     => 'UK Statutory Residence Test (SRT) for tax residency.',
				'notes'           => 'Complex three-part test: Automatic Overseas Test (non-resident), Automatic UK Test (resident), Sufficient Ties Test (day count vs ties). UK tax year runs April 6 - April 5.',
				'rule_config'     => '{"uk_srt":true,"automatic_overseas_tests":[{"id":"resident_3yr_under_16","description":"Resident in UK for 1+ of 3 prior years AND <16 days in UK"},{"id":"not_resident_3yr_under_46","description":"Not resident in UK for any of 3 prior years AND <46 days in UK"},{"id":"leaving_uk_under_16","description":"Leaving UK permanently during year AND <16 days in UK after departure date"}],"automatic_uk_tests":[{"id":"183_days","description":"183+ days in UK (midnight rule applies)"},{"id":"only_home","description":"Only home is in UK AND present for 30+ days"},{"id":"full_time_work","description":"Full-time work in UK (35+ hours/week for 365 days with no significant break)"},{"id":"deceased_183","description":"Deceased, and would have had 183+ days if lived"}],"ties":["family","accommodation","work","90_day","country"],"tie_thresholds":{"0":{"not_resident_prior":183,"resident_prior":183},"1":{"not_resident_prior":121,"resident_prior":91},"2":{"not_resident_prior":91,"resident_prior":61},"3":{"not_resident_prior":46,"resident_prior":46},"4":{"not_resident_prior":16,"resident_prior":16},"5":{"not_resident_prior":16,"resident_prior":16}}}',
				'country_code'    => 'GB',
				'flag_emoji'      => '🇬🇧',
				'is_system'       => 1,
				'display_order'   => 107,
			),
		);

		foreach ( $tax_rules as $rule ) {
			// Check if rule already exists.
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table WHERE code = %s",
					$rule['code']
				)
			);

			if ( $exists > 0 ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'code'            => $rule['code'],
					'name'            => $rule['name'],
					'type'            => $rule['type'],
					'category'        => $rule['category'],
					'days_allowed'    => $rule['days_allowed'],
					'window_days'     => $rule['window_days'],
					'counting_method' => $rule['counting_method'],
					'reset_month'     => isset( $rule['reset_month'] ) ? $rule['reset_month'] : null,
					'reset_day'       => isset( $rule['reset_day'] ) ? $rule['reset_day'] : null,
					'description'     => $rule['description'],
					'notes'           => isset( $rule['notes'] ) ? $rule['notes'] : null,
					'rule_config'     => isset( $rule['rule_config'] ) ? $rule['rule_config'] : null,
					'country_code'    => isset( $rule['country_code'] ) ? $rule['country_code'] : null,
					'flag_emoji'      => isset( $rule['flag_emoji'] ) ? $rule['flag_emoji'] : null,
					'is_system'       => $rule['is_system'],
					'display_order'   => $rule['display_order'],
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
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
		$remove_data = get_option( 'mts_remove_data_on_uninstall', '0' );
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
		$table_devices              = self::get_table( 'devices' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS $table_devices" );
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
		delete_option( 'mts_version' );
		delete_option( 'mts_db_version' );
		delete_option( 'mts_global_enabled' );
		delete_option( 'mts_upgrade_url' );
		delete_option( 'mts_remove_data_on_uninstall' );

		/**
		 * Fires after database tables are dropped.
		 */
		do_action( 'mts_tables_dropped' );
	}

	/**
	 * Get list of Schengen countries.
	 *
	 * @return array Array of country codes and names.
	 */
	public static function get_schengen_countries() {
		return array(
			'AT' => __( 'Austria', 'mytravelstatus' ),
			'BE' => __( 'Belgium', 'mytravelstatus' ),
			'BG' => __( 'Bulgaria', 'mytravelstatus' ),
			'HR' => __( 'Croatia', 'mytravelstatus' ),
			'CZ' => __( 'Czech Republic', 'mytravelstatus' ),
			'DK' => __( 'Denmark', 'mytravelstatus' ),
			'EE' => __( 'Estonia', 'mytravelstatus' ),
			'FI' => __( 'Finland', 'mytravelstatus' ),
			'FR' => __( 'France', 'mytravelstatus' ),
			'DE' => __( 'Germany', 'mytravelstatus' ),
			'GR' => __( 'Greece', 'mytravelstatus' ),
			'HU' => __( 'Hungary', 'mytravelstatus' ),
			'IS' => __( 'Iceland', 'mytravelstatus' ),
			'IT' => __( 'Italy', 'mytravelstatus' ),
			'LV' => __( 'Latvia', 'mytravelstatus' ),
			'LI' => __( 'Liechtenstein', 'mytravelstatus' ),
			'LT' => __( 'Lithuania', 'mytravelstatus' ),
			'LU' => __( 'Luxembourg', 'mytravelstatus' ),
			'MT' => __( 'Malta', 'mytravelstatus' ),
			'NL' => __( 'Netherlands', 'mytravelstatus' ),
			'NO' => __( 'Norway', 'mytravelstatus' ),
			'PL' => __( 'Poland', 'mytravelstatus' ),
			'PT' => __( 'Portugal', 'mytravelstatus' ),
			'RO' => __( 'Romania', 'mytravelstatus' ),
			'SK' => __( 'Slovakia', 'mytravelstatus' ),
			'SI' => __( 'Slovenia', 'mytravelstatus' ),
			'ES' => __( 'Spain', 'mytravelstatus' ),
			'SE' => __( 'Sweden', 'mytravelstatus' ),
			'CH' => __( 'Switzerland', 'mytravelstatus' ),
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
