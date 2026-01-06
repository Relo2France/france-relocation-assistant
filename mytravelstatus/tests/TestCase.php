<?php
/**
 * Base test case for MyTravelStatus plugin tests.
 *
 * @package MyTravelStatus
 */

/**
 * Base test case class.
 */
class MTS_TestCase extends WP_UnitTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected $test_user_id;

	/**
	 * Trips table name.
	 *
	 * @var string
	 */
	protected $trips_table;

	/**
	 * Jurisdiction rules table name.
	 *
	 * @var string
	 */
	protected $rules_table;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;

		// Create test user.
		$this->test_user_id = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'mts_test_user',
				'user_email' => 'mtstest@example.com',
			)
		);

		// Set current user.
		wp_set_current_user( $this->test_user_id );

		// Table names.
		$this->trips_table = $wpdb->prefix . 'mts_trips';
		$this->rules_table = $wpdb->prefix . 'mts_jurisdiction_rules';

		// Ensure tables exist.
		$this->maybe_create_tables();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		global $wpdb;

		// Clean up test trips.
		$wpdb->delete( $this->trips_table, array( 'user_id' => $this->test_user_id ) );

		// Clean up user meta.
		delete_user_meta( $this->test_user_id, 'mts_tracked_jurisdictions' );

		// Delete transients.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_mts_%'" );

		parent::tear_down();
	}

	/**
	 * Create database tables if they don't exist.
	 */
	protected function maybe_create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Trips table.
		$trips_table = $this->trips_table;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$trips_table'" ) !== $trips_table ) {
			$sql = "CREATE TABLE $trips_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				country varchar(100) NOT NULL,
				start_date date NOT NULL,
				end_date date NOT NULL,
				category varchar(50) DEFAULT 'personal',
				notes text,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY start_date (start_date),
				KEY end_date (end_date)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Jurisdiction rules table.
		$rules_table = $this->rules_table;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$rules_table'" ) !== $rules_table ) {
			$sql = "CREATE TABLE $rules_table (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code varchar(50) NOT NULL,
				name varchar(100) NOT NULL,
				type varchar(20) NOT NULL DEFAULT 'zone',
				category varchar(20) NOT NULL DEFAULT 'visa',
				parent_code varchar(50) DEFAULT NULL,
				days_allowed int NOT NULL DEFAULT 90,
				window_days int NOT NULL DEFAULT 180,
				counting_method varchar(30) NOT NULL DEFAULT 'rolling',
				reset_month tinyint DEFAULT NULL,
				reset_day tinyint DEFAULT NULL,
				description text,
				notes text,
				country_code varchar(10) DEFAULT NULL,
				flag_emoji varchar(10) DEFAULT NULL,
				rule_config text,
				is_active tinyint(1) NOT NULL DEFAULT 1,
				is_system tinyint(1) NOT NULL DEFAULT 0,
				display_order int NOT NULL DEFAULT 100,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY code (code),
				KEY type (type),
				KEY is_active (is_active)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// Insert default jurisdictions if empty.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $rules_table" );
		if ( 0 === (int) $count ) {
			$this->insert_default_jurisdictions();
		}
	}

	/**
	 * Insert default jurisdiction rules for testing.
	 */
	protected function insert_default_jurisdictions() {
		global $wpdb;

		$jurisdictions = array(
			array(
				'code'            => 'schengen',
				'name'            => 'Schengen Area',
				'type'            => 'zone',
				'category'        => 'visa',
				'days_allowed'    => 90,
				'window_days'     => 180,
				'counting_method' => 'rolling',
				'description'     => '90 days in any 180-day rolling window',
				'flag_emoji'      => '🇪🇺',
				'is_system'       => 1,
				'display_order'   => 1,
			),
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
				'description'     => 'UK tax residency determination via SRT',
				'country_code'    => 'GB',
				'flag_emoji'      => '🇬🇧',
				'is_system'       => 1,
				'display_order'   => 10,
			),
			array(
				'code'            => 'us_spt',
				'name'            => 'US Substantial Presence Test',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'weighted_multi_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'US tax residency via weighted 3-year calculation',
				'country_code'    => 'US',
				'flag_emoji'      => '🇺🇸',
				'is_system'       => 1,
				'display_order'   => 20,
			),
			array(
				'code'            => 'ireland_183',
				'name'            => 'Ireland 183-Day Rule',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'Ireland tax residency via calendar year day count',
				'country_code'    => 'IE',
				'flag_emoji'      => '🇮🇪',
				'is_system'       => 1,
				'display_order'   => 30,
			),
			array(
				'code'            => 'france_183',
				'name'            => 'France 183-Day Rule',
				'type'            => 'country',
				'category'        => 'tax',
				'days_allowed'    => 183,
				'window_days'     => 365,
				'counting_method' => 'calendar_year',
				'reset_month'     => 1,
				'reset_day'       => 1,
				'description'     => 'France tax residency via calendar year',
				'country_code'    => 'FR',
				'flag_emoji'      => '🇫🇷',
				'is_system'       => 1,
				'display_order'   => 40,
			),
		);

		foreach ( $jurisdictions as $jurisdiction ) {
			$wpdb->insert( $this->rules_table, $jurisdiction );
		}
	}

	/**
	 * Create a test trip.
	 *
	 * @param array $args Trip arguments.
	 * @return int Trip ID.
	 */
	protected function create_trip( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'    => $this->test_user_id,
			'country'    => 'France',
			'start_date' => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
			'end_date'   => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
			'category'   => 'personal',
			'notes'      => 'Test trip',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $args, $defaults );

		$wpdb->insert( $this->trips_table, $data );

		return $wpdb->insert_id;
	}

	/**
	 * Create multiple test trips.
	 *
	 * @param array $trips Array of trip data.
	 * @return array Trip IDs.
	 */
	protected function create_trips( $trips ) {
		$ids = array();
		foreach ( $trips as $trip ) {
			$ids[] = $this->create_trip( $trip );
		}
		return $ids;
	}

	/**
	 * Set tracked jurisdictions for test user.
	 *
	 * @param array $codes Jurisdiction codes.
	 */
	protected function set_tracked_jurisdictions( $codes ) {
		update_user_meta( $this->test_user_id, 'mts_tracked_jurisdictions', $codes );
	}

	/**
	 * Get Jurisdiction instance.
	 *
	 * @return MTS_Jurisdiction
	 */
	protected function get_jurisdiction() {
		return MTS_Jurisdiction::get_instance();
	}

	/**
	 * Make a REST API request.
	 *
	 * @param string $method  HTTP method.
	 * @param string $route   API route.
	 * @param array  $params  Request parameters.
	 * @return WP_REST_Response
	 */
	protected function api_request( $method, $route, $params = array() ) {
		$request = new WP_REST_Request( $method, $route );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );

		return $response;
	}
}
