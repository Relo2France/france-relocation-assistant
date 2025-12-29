<?php
/**
 * Family Members API for Schengen Tracker.
 *
 * Handles family member CRUD and per-member Schengen tracking.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class R2F_Schengen_Family
 *
 * Manages family member profiles and tracking.
 */
class R2F_Schengen_Family {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'r2f-schengen/v1';

	/**
	 * Legacy namespace for backward compatibility.
	 *
	 * @var string
	 */
	const LEGACY_NAMESPACE = 'fra-portal/v1';

	/**
	 * Relationship options.
	 *
	 * @var array
	 */
	const RELATIONSHIPS = array(
		'spouse',
		'partner',
		'child',
		'parent',
		'sibling',
		'other',
	);

	/**
	 * Default colors for family members.
	 *
	 * @var array
	 */
	const COLORS = array(
		'#3B82F6', // Blue
		'#10B981', // Green
		'#F59E0B', // Amber
		'#EF4444', // Red
		'#8B5CF6', // Purple
		'#EC4899', // Pink
		'#06B6D4', // Cyan
		'#F97316', // Orange
	);

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Family|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Family
	 */
	public static function get_instance(): R2F_Schengen_Family {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		// Register primary routes.
		$this->register_route_group( self::NAMESPACE );

		// Register legacy routes for backward compatibility.
		$this->register_route_group( self::LEGACY_NAMESPACE, '/schengen' );
	}

	/**
	 * Register route group under a namespace.
	 *
	 * @param string $namespace API namespace.
	 * @param string $prefix    Route prefix.
	 */
	private function register_route_group( string $namespace, string $prefix = '' ): void {
		// List family members.
		register_rest_route(
			$namespace,
			$prefix . '/family',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_family_members' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Create family member.
		register_rest_route(
			$namespace,
			$prefix . '/family',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_family_member' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_create_args(),
			)
		);

		// Get single family member.
		register_rest_route(
			$namespace,
			$prefix . '/family/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_family_member' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Update family member.
		register_rest_route(
			$namespace,
			$prefix . '/family/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_family_member' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => $this->get_update_args(),
			)
		);

		// Delete family member.
		register_rest_route(
			$namespace,
			$prefix . '/family/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_family_member' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get family summary (all members' Schengen status).
		register_rest_route(
			$namespace,
			$prefix . '/family/summary',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_family_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Get relationship options.
		register_rest_route(
			$namespace,
			$prefix . '/family/relationships',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_relationships' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Check user permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'unauthorized',
				__( 'You must be logged in to access this resource.', 'r2f-schengen' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get create endpoint arguments.
	 *
	 * @return array
	 */
	private function get_create_args(): array {
		return array(
			'name'             => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'relationship'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'nationality'      => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'passport_country' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'date_of_birth'    => array(
				'type'              => 'string',
				'validate_callback' => array( $this, 'validate_date' ),
			),
			'notes'            => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'color'            => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
			),
		);
	}

	/**
	 * Get update endpoint arguments.
	 *
	 * @return array
	 */
	private function get_update_args(): array {
		$args             = $this->get_create_args();
		$args['name']['required'] = false;
		$args['is_active'] = array(
			'type' => 'boolean',
		);
		$args['display_order'] = array(
			'type' => 'integer',
		);
		return $args;
	}

	/**
	 * Validate date format.
	 *
	 * @param string $value Date string.
	 * @return bool
	 */
	public function validate_date( $value ): bool {
		if ( empty( $value ) ) {
			return true;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value;
	}

	/**
	 * Get all family members for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_family_members( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d ORDER BY display_order ASC, name ASC",
				$user_id
			),
			ARRAY_A
		);

		$formatted = array_map( array( $this, 'format_member' ), $members );

		return rest_ensure_response( array(
			'members' => $formatted,
			'total'   => count( $formatted ),
		) );
	}

	/**
	 * Get single family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_family_member( WP_REST_Request $request ) {
		$member = $this->get_member_if_owner( (int) $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		return rest_ensure_response( $this->format_member( $member ) );
	}

	/**
	 * Create a new family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_family_member( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );
		$params  = $request->get_params();

		// Get next display order.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max_order = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(display_order) FROM $table WHERE user_id = %d",
				$user_id
			)
		);
		$next_order = ( (int) $max_order ) + 1;

		// Get a default color based on count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE user_id = %d",
				$user_id
			)
		);
		$default_color = self::COLORS[ $count % count( self::COLORS ) ];

		$data = array(
			'user_id'          => $user_id,
			'name'             => $params['name'],
			'relationship'     => isset( $params['relationship'] ) ? $params['relationship'] : null,
			'nationality'      => isset( $params['nationality'] ) ? $params['nationality'] : null,
			'passport_country' => isset( $params['passport_country'] ) ? $params['passport_country'] : null,
			'date_of_birth'    => isset( $params['date_of_birth'] ) ? $params['date_of_birth'] : null,
			'notes'            => isset( $params['notes'] ) ? $params['notes'] : null,
			'color'            => isset( $params['color'] ) ? $params['color'] : $default_color,
			'display_order'    => $next_order,
		);

		$result = $wpdb->insert( $table, $data );

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to create family member.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$member_id = $wpdb->insert_id;
		$member    = $this->get_member_by_id( $member_id );

		return rest_ensure_response( $this->format_member( $member ) );
	}

	/**
	 * Update a family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_family_member( WP_REST_Request $request ) {
		global $wpdb;

		$member = $this->get_member_if_owner( (int) $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$table  = R2F_Schengen_Schema::get_table( 'family_members' );
		$params = $request->get_params();

		$allowed_fields = array(
			'name',
			'relationship',
			'nationality',
			'passport_country',
			'date_of_birth',
			'notes',
			'color',
			'is_active',
			'display_order',
		);

		$data = array();
		foreach ( $allowed_fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$data[ $field ] = $params[ $field ];
			}
		}

		if ( empty( $data ) ) {
			return new WP_Error(
				'no_data',
				__( 'No data to update.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$result = $wpdb->update(
			$table,
			$data,
			array( 'id' => $member['id'] )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to update family member.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		$updated = $this->get_member_by_id( $member['id'] );

		return rest_ensure_response( $this->format_member( $updated ) );
	}

	/**
	 * Delete a family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_family_member( WP_REST_Request $request ) {
		global $wpdb;

		$member = $this->get_member_if_owner( (int) $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$table = R2F_Schengen_Schema::get_table( 'family_members' );

		// First, unassign any trips from this family member.
		$trips_table = R2F_Schengen_Schema::get_table( 'trips' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->update(
			$trips_table,
			array( 'family_member_id' => null ),
			array( 'family_member_id' => $member['id'] )
		);

		// Delete the family member.
		$result = $wpdb->delete( $table, array( 'id' => $member['id'] ) );

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to delete family member.', 'r2f-schengen' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array(
			'deleted' => true,
			'id'      => $member['id'],
		) );
	}

	/**
	 * Get family summary with Schengen status for each member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_family_summary( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND is_active = 1 ORDER BY display_order ASC, name ASC",
				$user_id
			),
			ARRAY_A
		);

		// Get Schengen status for the primary account holder (family_member_id = NULL).
		$primary_status = $this->calculate_schengen_status( $user_id, null );

		$summary = array(
			'primary' => array(
				'name'   => __( 'Me (Primary)', 'r2f-schengen' ),
				'color'  => '#6B7280', // Gray for primary.
				'status' => $primary_status,
			),
			'members' => array(),
		);

		// Calculate status for each family member.
		foreach ( $members as $member ) {
			$status              = $this->calculate_schengen_status( $user_id, (int) $member['id'] );
			$summary['members'][] = array(
				'id'           => (int) $member['id'],
				'name'         => $member['name'],
				'relationship' => $member['relationship'],
				'color'        => $member['color'],
				'status'       => $status,
			);
		}

		return rest_ensure_response( $summary );
	}

	/**
	 * Get relationship options.
	 *
	 * @return WP_REST_Response
	 */
	public function get_relationships(): WP_REST_Response {
		$relationships = array();
		foreach ( self::RELATIONSHIPS as $rel ) {
			$relationships[] = array(
				'value' => $rel,
				'label' => ucfirst( $rel ),
			);
		}
		return rest_ensure_response( $relationships );
	}

	/**
	 * Calculate Schengen status for a specific family member.
	 *
	 * @param int      $user_id          User ID.
	 * @param int|null $family_member_id Family member ID (null for primary).
	 * @return array Status summary.
	 */
	private function calculate_schengen_status( int $user_id, ?int $family_member_id ): array {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'trips' );
		$today = gmdate( 'Y-m-d' );

		// Calculate the 180-day window.
		$window_start = gmdate( 'Y-m-d', strtotime( '-180 days' ) );

		// Build query based on family member.
		if ( null === $family_member_id ) {
			// Primary account holder: trips with NULL family_member_id.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$trips = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT start_date, end_date FROM $table
					WHERE user_id = %d
					AND family_member_id IS NULL
					AND jurisdiction_code = 'schengen'
					AND end_date >= %s
					ORDER BY start_date ASC",
					$user_id,
					$window_start
				),
				ARRAY_A
			);
		} else {
			// Specific family member.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$trips = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT start_date, end_date FROM $table
					WHERE user_id = %d
					AND family_member_id = %d
					AND jurisdiction_code = 'schengen'
					AND end_date >= %s
					ORDER BY start_date ASC",
					$user_id,
					$family_member_id,
					$window_start
				),
				ARRAY_A
			);
		}

		// Calculate days used in the rolling 180-day window.
		$days_used = 0;
		$window_end = new DateTime( $today );

		foreach ( $trips as $trip ) {
			$trip_start = new DateTime( $trip['start_date'] );
			$trip_end   = new DateTime( $trip['end_date'] );

			// Clip to window.
			$window_start_date = new DateTime( $window_start );
			if ( $trip_start < $window_start_date ) {
				$trip_start = $window_start_date;
			}
			if ( $trip_end > $window_end ) {
				$trip_end = $window_end;
			}

			if ( $trip_start <= $trip_end ) {
				$diff = $trip_start->diff( $trip_end );
				$days_used += $diff->days + 1;
			}
		}

		$days_remaining = max( 0, 90 - $days_used );
		$days_allowed   = 90;

		// Determine status level.
		$percentage = ( $days_used / $days_allowed ) * 100;
		if ( $percentage >= 90 ) {
			$level = 'danger';
		} elseif ( $percentage >= 75 ) {
			$level = 'warning';
		} else {
			$level = 'ok';
		}

		return array(
			'days_used'      => $days_used,
			'days_remaining' => $days_remaining,
			'days_allowed'   => $days_allowed,
			'percentage'     => round( $percentage, 1 ),
			'level'          => $level,
		);
	}

	/**
	 * Get member if current user is owner.
	 *
	 * @param int $member_id Member ID.
	 * @return array|WP_Error
	 */
	private function get_member_if_owner( int $member_id ) {
		$member  = $this->get_member_by_id( $member_id );
		$user_id = get_current_user_id();

		if ( ! $member ) {
			return new WP_Error(
				'not_found',
				__( 'Family member not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $member['user_id'] !== $user_id ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to access this family member.', 'r2f-schengen' ),
				array( 'status' => 403 )
			);
		}

		return $member;
	}

	/**
	 * Get member by ID.
	 *
	 * @param int $member_id Member ID.
	 * @return array|null
	 */
	private function get_member_by_id( int $member_id ): ?array {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$member = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$member_id
			),
			ARRAY_A
		);

		return $member ?: null;
	}

	/**
	 * Format member for API response.
	 *
	 * @param array $member Raw member data.
	 * @return array Formatted member.
	 */
	private function format_member( array $member ): array {
		return array(
			'id'              => (int) $member['id'],
			'name'            => $member['name'],
			'relationship'    => $member['relationship'],
			'nationality'     => $member['nationality'],
			'passportCountry' => $member['passport_country'],
			'dateOfBirth'     => $member['date_of_birth'],
			'notes'           => $member['notes'],
			'color'           => $member['color'],
			'isActive'        => (bool) $member['is_active'],
			'displayOrder'    => (int) $member['display_order'],
			'createdAt'       => $member['created_at'],
			'updatedAt'       => $member['updated_at'],
		);
	}
}
