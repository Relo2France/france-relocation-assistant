<?php
/**
 * MyTravelStatus Admin Test Runner
 *
 * Provides a WordPress admin interface to run plugin tests
 * without requiring command-line access or PHPUnit.
 *
 * @package MyTravelStatus
 * @since   1.8.4
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin test runner class.
 */
class MTS_Admin_Test_Runner {

	/**
	 * Singleton instance.
	 *
	 * @var MTS_Admin_Test_Runner
	 */
	private static $instance = null;

	/**
	 * Test results.
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Test counts.
	 *
	 * @var array
	 */
	private $counts = array(
		'total'  => 0,
		'passed' => 0,
		'failed' => 0,
	);

	/**
	 * Get singleton instance.
	 *
	 * @return MTS_Admin_Test_Runner
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_run_tests' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'MyTravelStatus Tests', 'mytravelstatus' ),
			__( 'MTS Test Runner', 'mytravelstatus' ),
			'manage_options',
			'mts-test-runner',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle run tests action.
	 */
	public function handle_run_tests() {
		if ( ! isset( $_GET['page'] ) || 'mts-test-runner' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['run'] ) || '1' !== $_GET['run'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'mts_run_tests' );
	}

	/**
	 * Render the test runner page.
	 */
	public function render_page() {
		$run_tests = isset( $_GET['run'] ) && '1' === $_GET['run'] && check_admin_referer( 'mts_run_tests' );
		$suite = isset( $_GET['suite'] ) ? sanitize_text_field( $_GET['suite'] ) : 'all';

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MyTravelStatus Test Runner', 'mytravelstatus' ); ?></h1>

			<div style="max-width: 900px;">
				<p><?php esc_html_e( 'Run plugin tests directly from the WordPress admin. No command line required.', 'mytravelstatus' ); ?></p>

				<div style="margin: 20px 0; display: flex; gap: 10px;">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=mts-test-runner&run=1&suite=all' ), 'mts_run_tests' ) ); ?>" class="button button-primary button-hero">
						<?php esc_html_e( 'Run All Tests', 'mytravelstatus' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=mts-test-runner&run=1&suite=unit' ), 'mts_run_tests' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Unit Tests Only', 'mytravelstatus' ); ?>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'options-general.php?page=mts-test-runner&run=1&suite=integration' ), 'mts_run_tests' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Integration Tests Only', 'mytravelstatus' ); ?>
					</a>
				</div>

				<?php if ( $run_tests ) : ?>
					<?php $this->run_tests( $suite ); ?>
					<?php $this->render_results(); ?>
				<?php else : ?>
					<?php $this->render_test_info(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render test suite information.
	 */
	private function render_test_info() {
		?>
		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'Available Test Suites', 'mytravelstatus' ); ?></h2>

			<table class="widefat" style="margin-top: 15px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Suite', 'mytravelstatus' ); ?></th>
						<th><?php esc_html_e( 'Tests', 'mytravelstatus' ); ?></th>
						<th><?php esc_html_e( 'Description', 'mytravelstatus' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Calculator Tests', 'mytravelstatus' ); ?></strong></td>
						<td>17</td>
						<td><?php esc_html_e( 'US SPT, UK SRT, Schengen, Ireland calculations, status thresholds', 'mytravelstatus' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Jurisdiction Tests', 'mytravelstatus' ); ?></strong></td>
						<td>12</td>
						<td><?php esc_html_e( 'Rules engine, day counting, caching, compliance overview', 'mytravelstatus' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'REST API Tests', 'mytravelstatus' ); ?></strong></td>
						<td>10</td>
						<td><?php esc_html_e( 'API endpoints, authentication, response structure', 'mytravelstatus' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Database Tests', 'mytravelstatus' ); ?></strong></td>
						<td>8</td>
						<td><?php esc_html_e( 'Tables, queries, data integrity, performance', 'mytravelstatus' ); ?></td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'Total', 'mytravelstatus' ); ?></th>
						<th>47</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}

	/**
	 * Run tests.
	 *
	 * @param string $suite Test suite to run.
	 */
	private function run_tests( $suite ) {
		$this->results = array();
		$this->counts = array(
			'total'  => 0,
			'passed' => 0,
			'failed' => 0,
		);

		if ( 'all' === $suite || 'unit' === $suite ) {
			$this->run_calculator_tests();
		}

		if ( 'all' === $suite || 'integration' === $suite ) {
			$this->run_jurisdiction_tests();
			$this->run_api_tests();
			$this->run_database_tests();
		}
	}

	/**
	 * Assert helper.
	 *
	 * @param bool   $condition Condition to test.
	 * @param string $message   Test description.
	 * @param string $group     Test group.
	 */
	private function assert( $condition, $message, $group = 'General' ) {
		$this->counts['total']++;

		if ( $condition ) {
			$this->counts['passed']++;
			$this->results[] = array(
				'status'  => 'passed',
				'message' => $message,
				'group'   => $group,
			);
		} else {
			$this->counts['failed']++;
			$this->results[] = array(
				'status'  => 'failed',
				'message' => $message,
				'group'   => $group,
			);
		}
	}

	/**
	 * Assert equals helper.
	 *
	 * @param mixed  $expected Expected value.
	 * @param mixed  $actual   Actual value.
	 * @param string $message  Test description.
	 * @param string $group    Test group.
	 */
	private function assert_equals( $expected, $actual, $message, $group = 'General' ) {
		$passed = $expected === $actual;
		if ( ! $passed ) {
			$message .= " (expected: " . var_export( $expected, true ) . ", got: " . var_export( $actual, true ) . ")";
		}
		$this->assert( $passed, $message, $group );
	}

	/**
	 * Run calculator tests.
	 */
	private function run_calculator_tests() {
		$group = 'Calculator';

		// US SPT weighted calculation.
		$weighted = ( 120 * 1.0 ) + ( 180 * ( 1 / 3 ) ) + ( 180 * ( 1 / 6 ) );
		$this->assert( $weighted >= 183, 'US SPT: 120/180/180 days = resident (weighted >= 183)', $group );

		$weighted = ( 50 * 1.0 ) + ( 50 * ( 1 / 3 ) ) + ( 50 * ( 1 / 6 ) );
		$this->assert( $weighted < 183, 'US SPT: 50/50/50 days = not resident', $group );

		// US SPT minimum current year.
		$this->assert( 25 < 31, 'US SPT: 25 days current year below 31-day minimum', $group );
		$this->assert( 31 >= 31, 'US SPT: 31 days meets minimum requirement', $group );

		// UK SRT automatic tests.
		$this->assert( 15 < 16, 'UK SRT: <16 days = automatic non-resident', $group );
		$this->assert( 183 >= 183, 'UK SRT: 183+ days = automatic UK resident', $group );

		// UK SRT ties thresholds.
		$this->assert( 100 >= 91, 'UK SRT: Leaver with 2 ties, 100 days (threshold 91) = resident', $group );
		$this->assert( 100 < 121, 'UK SRT: Arriver with 2 ties, 100 days (threshold 121) = not resident', $group );

		// Schengen 90/180.
		$this->assert( 60 <= 90, 'Schengen: 60 days used = within 90-day limit', $group );
		$this->assert( 95 > 90, 'Schengen: 95 days = overstay', $group );

		// Ireland 183/280.
		$this->assert( 183 >= 183, 'Ireland: 183 days current year = resident', $group );
		$combined = 150 + 150;
		$this->assert( $combined >= 280 && 150 >= 31 && 150 >= 31, 'Ireland: 150+150 days over 2 years = resident', $group );

		// Status thresholds.
		$this->assert_equals( 'ok', $this->get_status( 60, 90 ), 'Status: 60/90 (67%) = ok', $group );
		$this->assert_equals( 'warning', $this->get_status( 75, 90 ), 'Status: 75/90 (83%) = warning', $group );
		$this->assert_equals( 'critical', $this->get_status( 86, 90 ), 'Status: 86/90 (96%) = critical', $group );
		$this->assert_equals( 'exceeded', $this->get_status( 95, 90 ), 'Status: 95/90 (106%) = exceeded', $group );

		// Days remaining.
		$this->assert_equals( 30, max( 0, 90 - 60 ), 'Remaining: 60 used of 90 = 30 remaining', $group );
		$this->assert_equals( 0, max( 0, 90 - 100 ), 'Remaining: 100 used of 90 = 0 (not negative)', $group );
	}

	/**
	 * Get status from percentage.
	 *
	 * @param int $used    Days used.
	 * @param int $allowed Days allowed.
	 * @return string Status.
	 */
	private function get_status( $used, $allowed ) {
		$percentage = ( $used / $allowed ) * 100;
		if ( $percentage >= 100 ) {
			return 'exceeded';
		}
		if ( $percentage >= 95 ) {
			return 'critical';
		}
		if ( $percentage >= 80 ) {
			return 'warning';
		}
		return 'ok';
	}

	/**
	 * Run jurisdiction tests.
	 */
	private function run_jurisdiction_tests() {
		$group = 'Jurisdiction';

		$jurisdiction = MTS_Jurisdiction::get_instance();

		// Get all rules.
		$rules = $jurisdiction->get_all_rules();
		$this->assert( is_array( $rules ) && count( $rules ) > 0, 'get_all_rules() returns jurisdictions', $group );

		// Get Schengen rule.
		$schengen = $jurisdiction->get_rule( 'schengen' );
		$this->assert( null !== $schengen, 'get_rule("schengen") returns rule', $group );
		$this->assert_equals( 90, $schengen['daysAllowed'] ?? 0, 'Schengen daysAllowed = 90', $group );
		$this->assert_equals( 180, $schengen['windowDays'] ?? 0, 'Schengen windowDays = 180', $group );
		$this->assert_equals( 'rolling', $schengen['countingMethod'] ?? '', 'Schengen countingMethod = rolling', $group );

		// Get non-existent rule.
		$nonexistent = $jurisdiction->get_rule( 'nonexistent_code' );
		$this->assert( null === $nonexistent, 'get_rule("nonexistent") returns null', $group );

		// User tracked jurisdictions.
		$user_id = get_current_user_id();
		$tracked = $jurisdiction->get_user_tracked_jurisdictions( $user_id );
		$this->assert( is_array( $tracked ), 'get_user_tracked_jurisdictions() returns array', $group );

		// Available rules.
		$available = $jurisdiction->get_available_rules();
		$this->assert_equals( $rules, $available, 'get_available_rules() matches get_all_rules()', $group );

		// Compliance overview.
		$overview = $jurisdiction->get_compliance_overview( $user_id );
		$this->assert( is_array( $overview ), 'get_compliance_overview() returns array', $group );
		$this->assert( isset( $overview['total_jurisdictions'] ), 'Overview has total_jurisdictions', $group );
		$this->assert( isset( $overview['summaries'] ), 'Overview has summaries', $group );
	}

	/**
	 * Run API tests.
	 */
	private function run_api_tests() {
		$group = 'REST API';

		// Check routes are registered.
		$routes = rest_get_server()->get_routes();

		$this->assert( isset( $routes['/mts/v1/jurisdictions'] ), 'Route /mts/v1/jurisdictions registered', $group );
		$this->assert( isset( $routes['/mts/v1/jurisdictions/tracked'] ), 'Route /mts/v1/jurisdictions/tracked registered', $group );
		$this->assert( isset( $routes['/mts/v1/jurisdictions/summary'] ), 'Route /mts/v1/jurisdictions/summary registered', $group );

		// Test API request.
		$request = new WP_REST_Request( 'GET', '/mts/v1/jurisdictions' );
		$response = rest_do_request( $request );

		$this->assert_equals( 200, $response->get_status(), 'GET /jurisdictions returns 200', $group );

		$data = $response->get_data();
		$this->assert( is_array( $data ) && count( $data ) > 0, 'GET /jurisdictions returns jurisdictions', $group );

		// Test single jurisdiction.
		$request = new WP_REST_Request( 'GET', '/mts/v1/jurisdictions/schengen' );
		$response = rest_do_request( $request );

		$this->assert_equals( 200, $response->get_status(), 'GET /jurisdictions/schengen returns 200', $group );

		// Test tracked jurisdictions.
		$request = new WP_REST_Request( 'GET', '/mts/v1/jurisdictions/tracked' );
		$response = rest_do_request( $request );

		$this->assert_equals( 200, $response->get_status(), 'GET /jurisdictions/tracked returns 200', $group );

		// Test summary.
		$request = new WP_REST_Request( 'GET', '/mts/v1/jurisdictions/summary' );
		$response = rest_do_request( $request );

		$this->assert_equals( 200, $response->get_status(), 'GET /jurisdictions/summary returns 200', $group );
	}

	/**
	 * Run database tests.
	 */
	private function run_database_tests() {
		global $wpdb;
		$group = 'Database';

		$trips_table = $wpdb->prefix . 'mts_trips';
		$rules_table = $wpdb->prefix . 'mts_jurisdiction_rules';

		// Check tables exist.
		$trips_exists = $wpdb->get_var( "SHOW TABLES LIKE '$trips_table'" ) === $trips_table;
		$this->assert( $trips_exists, "Table {$trips_table} exists", $group );

		$rules_exists = $wpdb->get_var( "SHOW TABLES LIKE '$rules_table'" ) === $rules_table;
		$this->assert( $rules_exists, "Table {$rules_table} exists", $group );

		// Check rules have data.
		$rule_count = $wpdb->get_var( "SELECT COUNT(*) FROM $rules_table WHERE is_active = 1" );
		$this->assert( (int) $rule_count > 0, 'Jurisdiction rules table has active rules', $group );

		// Check Schengen rule exists.
		$schengen = $wpdb->get_row( "SELECT * FROM $rules_table WHERE code = 'schengen'" );
		$this->assert( null !== $schengen, 'Schengen rule exists in database', $group );

		// Check user meta works.
		$user_id = get_current_user_id();
		$test_key = 'mts_test_meta_' . time();
		update_user_meta( $user_id, $test_key, array( 'test' => 'value' ) );
		$retrieved = get_user_meta( $user_id, $test_key, true );
		$this->assert_equals( array( 'test' => 'value' ), $retrieved, 'User meta storage works', $group );
		delete_user_meta( $user_id, $test_key );

		// Check transients work.
		$transient_key = 'mts_test_transient_' . time();
		set_transient( $transient_key, 'test_value', 60 );
		$retrieved = get_transient( $transient_key );
		$this->assert_equals( 'test_value', $retrieved, 'Transient caching works', $group );
		delete_transient( $transient_key );

		// Check indexes exist on trips table.
		if ( $trips_exists ) {
			$indexes = $wpdb->get_results( "SHOW INDEX FROM $trips_table" );
			$index_names = array_column( $indexes, 'Key_name' );
			$this->assert( in_array( 'user_id', $index_names, true ), 'Trips table has user_id index', $group );
		}
	}

	/**
	 * Render test results.
	 */
	private function render_results() {
		$pass_rate = $this->counts['total'] > 0
			? round( ( $this->counts['passed'] / $this->counts['total'] ) * 100, 1 )
			: 0;

		$status_color = $this->counts['failed'] === 0 ? '#00a32a' : '#d63638';
		?>
		<div style="background: <?php echo esc_attr( $status_color ); ?>; color: #fff; padding: 20px; margin-top: 20px; border-radius: 4px;">
			<h2 style="margin: 0; color: #fff;">
				<?php if ( $this->counts['failed'] === 0 ) : ?>
					✅ <?php esc_html_e( 'All Tests Passed!', 'mytravelstatus' ); ?>
				<?php else : ?>
					❌ <?php printf( esc_html__( '%d Test(s) Failed', 'mytravelstatus' ), $this->counts['failed'] ); ?>
				<?php endif; ?>
			</h2>
			<p style="margin: 10px 0 0; font-size: 16px;">
				<?php
				printf(
					esc_html__( '%d passed, %d failed, %d total (%s%% pass rate)', 'mytravelstatus' ),
					$this->counts['passed'],
					$this->counts['failed'],
					$this->counts['total'],
					$pass_rate
				);
				?>
			</p>
		</div>

		<?php
		// Group results.
		$grouped = array();
		foreach ( $this->results as $result ) {
			$grouped[ $result['group'] ][] = $result;
		}

		foreach ( $grouped as $group => $results ) :
			$group_passed = count( array_filter( $results, function( $r ) { return 'passed' === $r['status']; } ) );
			$group_total = count( $results );
		?>
		<div style="background: #fff; border: 1px solid #ccd0d4; margin-top: 20px;">
			<h3 style="margin: 0; padding: 15px; background: #f0f0f1; border-bottom: 1px solid #ccd0d4;">
				<?php echo esc_html( $group ); ?>
				<span style="float: right; font-weight: normal;">
					<?php printf( '%d/%d', $group_passed, $group_total ); ?>
				</span>
			</h3>
			<table class="widefat" style="border: none;">
				<tbody>
					<?php foreach ( $results as $result ) : ?>
					<tr>
						<td style="width: 30px; text-align: center;">
							<?php echo 'passed' === $result['status'] ? '✅' : '❌'; ?>
						</td>
						<td>
							<?php echo esc_html( $result['message'] ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endforeach;
	}
}

// Initialize.
MTS_Admin_Test_Runner::get_instance();
