<?php
/**
 * Integration tests for MyTravelStatus REST API endpoints.
 *
 * Tests all jurisdiction-related REST API endpoints including:
 * - GET /mts/v1/jurisdictions
 * - GET /mts/v1/jurisdictions/{code}
 * - GET /mts/v1/jurisdictions/tracked
 * - POST /mts/v1/jurisdictions/tracked
 * - DELETE /mts/v1/jurisdictions/tracked/{code}
 * - GET /mts/v1/jurisdictions/summary
 * - GET /mts/v1/jurisdictions/{code}/summary
 *
 * @package MyTravelStatus
 */

/**
 * REST API integration test class.
 */
class RestApiTest extends MTS_TestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		// Initialize REST server.
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Test GET /mts/v1/jurisdictions returns all jurisdictions.
	 */
	public function test_get_jurisdictions() {
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		// Check structure of first jurisdiction.
		$first = $data[0];
		$this->assertArrayHasKey( 'code', $first );
		$this->assertArrayHasKey( 'name', $first );
		$this->assertArrayHasKey( 'daysAllowed', $first );
		$this->assertArrayHasKey( 'windowDays', $first );
		$this->assertArrayHasKey( 'countingMethod', $first );
	}

	/**
	 * Test GET /mts/v1/jurisdictions requires authentication.
	 */
	public function test_get_jurisdictions_requires_auth() {
		wp_set_current_user( 0 );

		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions' );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test GET /mts/v1/jurisdictions with type filter.
	 */
	public function test_get_jurisdictions_with_type_filter() {
		$response = $this->api_request(
			'GET',
			'/mts/v1/jurisdictions',
			array( 'type' => 'zone' )
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		foreach ( $data as $jurisdiction ) {
			$this->assertEquals( 'zone', $jurisdiction['type'] );
		}
	}

	/**
	 * Test GET /mts/v1/jurisdictions/{code} returns single jurisdiction.
	 */
	public function test_get_single_jurisdiction() {
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/schengen' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'schengen', $data['code'] );
		$this->assertEquals( 'Schengen Area', $data['name'] );
		$this->assertEquals( 90, $data['daysAllowed'] );
		$this->assertEquals( 180, $data['windowDays'] );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/{code} returns 404 for non-existent.
	 */
	public function test_get_single_jurisdiction_not_found() {
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/nonexistent' );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/tracked returns tracked jurisdictions.
	 */
	public function test_get_tracked_jurisdictions() {
		$this->set_tracked_jurisdictions( array( 'schengen', 'uk_srt' ) );

		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/tracked' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data );

		$codes = array_column( $data, 'code' );
		$this->assertContains( 'schengen', $codes );
		$this->assertContains( 'uk_srt', $codes );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/tracked returns default Schengen.
	 */
	public function test_get_tracked_jurisdictions_default() {
		// No tracked jurisdictions set - should return Schengen.
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/tracked' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( 'schengen', $data[0]['code'] );
	}

	/**
	 * Test POST /mts/v1/jurisdictions/tracked adds jurisdiction.
	 */
	public function test_add_tracked_jurisdiction() {
		$response = $this->api_request(
			'POST',
			'/mts/v1/jurisdictions/tracked',
			array( 'code' => 'uk_srt' )
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertContains( 'uk_srt', $data['tracked'] );
	}

	/**
	 * Test POST /mts/v1/jurisdictions/tracked returns 404 for invalid code.
	 */
	public function test_add_tracked_jurisdiction_invalid_code() {
		$response = $this->api_request(
			'POST',
			'/mts/v1/jurisdictions/tracked',
			array( 'code' => 'invalid_code' )
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test POST /mts/v1/jurisdictions/tracked doesn't duplicate.
	 */
	public function test_add_tracked_jurisdiction_no_duplicate() {
		// Add twice.
		$this->api_request( 'POST', '/mts/v1/jurisdictions/tracked', array( 'code' => 'uk_srt' ) );
		$response = $this->api_request( 'POST', '/mts/v1/jurisdictions/tracked', array( 'code' => 'uk_srt' ) );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$count = array_count_values( $data['tracked'] );
		$this->assertEquals( 1, $count['uk_srt'] ?? 0 );
	}

	/**
	 * Test DELETE /mts/v1/jurisdictions/tracked/{code} removes jurisdiction.
	 */
	public function test_remove_tracked_jurisdiction() {
		$this->set_tracked_jurisdictions( array( 'schengen', 'uk_srt', 'ireland_183' ) );

		$response = $this->api_request( 'DELETE', '/mts/v1/jurisdictions/tracked/uk_srt' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertNotContains( 'uk_srt', $data['tracked'] );
		$this->assertContains( 'schengen', $data['tracked'] );
		$this->assertContains( 'ireland_183', $data['tracked'] );
	}

	/**
	 * Test DELETE /mts/v1/jurisdictions/tracked/schengen fails.
	 */
	public function test_cannot_remove_schengen() {
		$response = $this->api_request( 'DELETE', '/mts/v1/jurisdictions/tracked/schengen' );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/summary returns multi-jurisdiction summary.
	 */
	public function test_get_multi_jurisdiction_summary() {
		$this->set_tracked_jurisdictions( array( 'schengen', 'ireland_183' ) );

		// Create a trip.
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-15 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-6 days' ) ),
			)
		);

		$this->get_jurisdiction()->invalidate_cache( $this->test_user_id );

		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/summary' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'schengen', $data );
		$this->assertArrayHasKey( 'ireland_183', $data );

		// Check Schengen summary structure.
		$schengen = $data['schengen'];
		$this->assertArrayHasKey( 'daysUsed', $schengen );
		$this->assertArrayHasKey( 'daysAllowed', $schengen );
		$this->assertArrayHasKey( 'daysRemaining', $schengen );
		$this->assertArrayHasKey( 'percentage', $schengen );
		$this->assertArrayHasKey( 'status', $schengen );
		$this->assertArrayHasKey( 'rule', $schengen );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/{code}/summary returns single summary.
	 */
	public function test_get_single_jurisdiction_summary() {
		// Create a trip.
		$this->create_trip(
			array(
				'country'    => 'Germany',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-20 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-11 days' ) ),
			)
		);

		$this->get_jurisdiction()->invalidate_cache( $this->test_user_id );

		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/schengen/summary' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 10, $data['daysUsed'] );
		$this->assertEquals( 80, $data['daysRemaining'] );
		$this->assertEquals( 90, $data['daysAllowed'] );
		$this->assertEquals( 'ok', $data['status'] );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/{code}/summary with date param.
	 */
	public function test_get_jurisdiction_summary_with_date() {
		// Create a trip 100 days ago.
		$this->create_trip(
			array(
				'country'    => 'France',
				'start_date' => gmdate( 'Y-m-d', strtotime( '-100 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-91 days' ) ),
			)
		);

		$this->get_jurisdiction()->invalidate_cache( $this->test_user_id );

		// Query from 90 days ago (trip should be in window).
		$past_date = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
		$response = $this->api_request(
			'GET',
			'/mts/v1/jurisdictions/schengen/summary',
			array( 'date' => $past_date )
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 10, $data['daysUsed'] );
	}

	/**
	 * Test GET /mts/v1/jurisdictions/{code}/summary returns 404 for invalid.
	 */
	public function test_get_jurisdiction_summary_not_found() {
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/invalid/summary' );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test legacy API namespace works.
	 */
	public function test_legacy_api_namespace() {
		$response = $this->api_request( 'GET', '/fra-portal/v1/schengen/jurisdictions' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );
	}

	/**
	 * Test legacy tracked endpoint.
	 */
	public function test_legacy_tracked_endpoint() {
		$response = $this->api_request( 'GET', '/fra-portal/v1/schengen/jurisdictions/tracked' );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test legacy summary endpoint.
	 */
	public function test_legacy_summary_endpoint() {
		$response = $this->api_request( 'GET', '/fra-portal/v1/schengen/jurisdictions/summary' );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test API response caching headers.
	 */
	public function test_api_response_structure() {
		$response = $this->api_request( 'GET', '/mts/v1/jurisdictions/schengen' );

		$this->assertEquals( 200, $response->get_status() );

		// Verify response is valid JSON structure.
		$data = $response->get_data();
		$this->assertIsArray( $data );

		// Check all expected fields are present.
		$expected_fields = array(
			'id',
			'code',
			'name',
			'type',
			'category',
			'daysAllowed',
			'windowDays',
			'countingMethod',
			'description',
			'isSystem',
		);

		foreach ( $expected_fields as $field ) {
			$this->assertArrayHasKey( $field, $data, "Missing field: $field" );
		}
	}
}
