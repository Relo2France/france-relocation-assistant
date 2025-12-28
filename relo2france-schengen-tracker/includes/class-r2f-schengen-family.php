<?php
/**
 * Family Member management for Schengen Tracker.
 *
 * Handles CRUD operations for family members and their trip associations.
 * Allows tracking Schengen days for each family member independently.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Family member management class.
 */
class R2F_Schengen_Family {

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Family|null
	 */
	private static $instance = null;

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private $namespace = 'r2f-schengen/v1';

	/**
	 * Get singleton instance.
	 *
	 * @return R2F_Schengen_Family
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Family members CRUD.
		register_rest_route(
			$this->namespace,
			'/family',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_family_members' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_family_member' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_family_member_args(),
				),
			)
		);

		// Single family member.
		register_rest_route(
			$this->namespace,
			'/family/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_family_member' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_family_member' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_family_member_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_family_member' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Family member summary (Schengen days).
		register_rest_route(
			$this->namespace,
			'/family/(?P<id>\d+)/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_family_member_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Family member trips.
		register_rest_route(
			$this->namespace,
			'/family/(?P<id>\d+)/trips',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_family_member_trips' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// All family summaries (overview).
		register_rest_route(
			$this->namespace,
			'/family/summaries',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_all_family_summaries' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Trip travelers management.
		register_rest_route(
			$this->namespace,
			'/trips/(?P<trip_id>\d+)/travelers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_trip_travelers' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_trip_travelers' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
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
				'rest_forbidden',
				__( 'You must be logged in.', 'r2f-schengen' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	/**
	 * Get family member REST args.
	 *
	 * @return array
	 */
	private function get_family_member_args() {
		return array(
			'name'            => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'relationship'    => array(
				'type'              => 'string',
				'enum'              => array( 'spouse', 'child', 'parent', 'sibling', 'other' ),
				'default'           => 'spouse',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'birthDate'       => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'nationality'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'passportNumber'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'passportExpiry'  => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'color'           => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#3B82F6',
			),
			'notes'           => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Get all family members for current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_family_members( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND is_active = 1 ORDER BY sort_order ASC, name ASC",
				$user_id
			)
		);

		$formatted = array_map( array( $this, 'format_family_member' ), $members );

		return rest_ensure_response(
			array(
				'success' => true,
				'members' => $formatted,
			)
		);
	}

	/**
	 * Get single family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_family_member( $request ) {
		$member = $this->get_member_by_id( $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'member'  => $this->format_family_member( $member ),
			)
		);
	}

	/**
	 * Create family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_family_member( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		$data = array(
			'user_id'         => $user_id,
			'name'            => $request['name'],
			'relationship'    => $request['relationship'] ?? 'spouse',
			'birth_date'      => $request['birthDate'] ? $request['birthDate'] : null,
			'nationality'     => $request['nationality'] ?? null,
			'passport_number' => $request['passportNumber'] ?? null,
			'passport_expiry' => $request['passportExpiry'] ? $request['passportExpiry'] : null,
			'color'           => $request['color'] ?? '#3B82F6',
			'notes'           => $request['notes'] ?? null,
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$member = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $member_id )
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'member'  => $this->format_family_member( $member ),
				'message' => __( 'Family member added.', 'r2f-schengen' ),
			)
		);
	}

	/**
	 * Update family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_family_member( $request ) {
		global $wpdb;

		$member = $this->get_member_by_id( $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$table = R2F_Schengen_Schema::get_table( 'family_members' );

		$data = array();

		if ( isset( $request['name'] ) ) {
			$data['name'] = $request['name'];
		}
		if ( isset( $request['relationship'] ) ) {
			$data['relationship'] = $request['relationship'];
		}
		if ( isset( $request['birthDate'] ) ) {
			$data['birth_date'] = $request['birthDate'] ? $request['birthDate'] : null;
		}
		if ( isset( $request['nationality'] ) ) {
			$data['nationality'] = $request['nationality'];
		}
		if ( isset( $request['passportNumber'] ) ) {
			$data['passport_number'] = $request['passportNumber'];
		}
		if ( isset( $request['passportExpiry'] ) ) {
			$data['passport_expiry'] = $request['passportExpiry'] ? $request['passportExpiry'] : null;
		}
		if ( isset( $request['color'] ) ) {
			$data['color'] = $request['color'];
		}
		if ( isset( $request['notes'] ) ) {
			$data['notes'] = $request['notes'];
		}

		if ( empty( $data ) ) {
			return new WP_Error(
				'no_data',
				__( 'No data to update.', 'r2f-schengen' ),
				array( 'status' => 400 )
			);
		}

		$wpdb->update( $table, $data, array( 'id' => $member->id ) );

		// Fetch updated member.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $member->id )
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'member'  => $this->format_family_member( $updated ),
				'message' => __( 'Family member updated.', 'r2f-schengen' ),
			)
		);
	}

	/**
	 * Delete (soft) family member.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_family_member( $request ) {
		global $wpdb;

		$member = $this->get_member_by_id( $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$table = R2F_Schengen_Schema::get_table( 'family_members' );

		// Soft delete by setting is_active = 0.
		$wpdb->update(
			$table,
			array( 'is_active' => 0 ),
			array( 'id' => $member->id )
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Family member removed.', 'r2f-schengen' ),
			)
		);
	}

	/**
	 * Get family member summary (Schengen day counts).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_family_member_summary( $request ) {
		$member = $this->get_member_by_id( $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$trips   = $this->get_member_trips( $member->id );
		$summary = $this->calculate_summary( $trips );

		return rest_ensure_response(
			array(
				'success' => true,
				'member'  => $this->format_family_member( $member ),
				'summary' => $summary,
			)
		);
	}

	/**
	 * Get family member trips.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_family_member_trips( $request ) {
		$member = $this->get_member_by_id( $request['id'] );

		if ( is_wp_error( $member ) ) {
			return $member;
		}

		$trips = $this->get_member_trips( $member->id );

		return rest_ensure_response(
			array(
				'success' => true,
				'member'  => $this->format_family_member( $member ),
				'trips'   => $trips,
			)
		);
	}

	/**
	 * Get all family member summaries for overview.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_all_family_summaries( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$members = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE user_id = %d AND is_active = 1 ORDER BY sort_order ASC, name ASC",
				$user_id
			)
		);

		$summaries = array();

		foreach ( $members as $member ) {
			$trips = $this->get_member_trips( $member->id );
			$summaries[] = array(
				'member'  => $this->format_family_member( $member ),
				'summary' => $this->calculate_summary( $trips ),
			);
		}

		// Also include primary user summary.
		$user_trips   = $this->get_primary_user_trips();
		$user_summary = $this->calculate_summary( $user_trips );

		$current_user = wp_get_current_user();

		return rest_ensure_response(
			array(
				'success'     => true,
				'primaryUser' => array(
					'id'      => $current_user->ID,
					'name'    => $current_user->display_name,
					'summary' => $user_summary,
				),
				'family'      => $summaries,
			)
		);
	}

	/**
	 * Get trip travelers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_trip_travelers( $request ) {
		global $wpdb;

		$trip_id = (int) $request['trip_id'];
		$user_id = get_current_user_id();

		// Verify trip belongs to user.
		$trips_table = R2F_Schengen_Schema::get_table( 'trips' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trip = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $trips_table WHERE id = %d AND user_id = %d", $trip_id, $user_id )
		);

		if ( ! $trip ) {
			return new WP_Error(
				'not_found',
				__( 'Trip not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );
		$family_table    = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$travelers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.*, fm.name, fm.relationship, fm.color
				 FROM $travelers_table tt
				 LEFT JOIN $family_table fm ON tt.family_member_id = fm.id
				 WHERE tt.trip_id = %d",
				$trip_id
			)
		);

		$formatted = array();
		foreach ( $travelers as $t ) {
			$formatted[] = array(
				'id'             => (int) $t->id,
				'tripId'         => (int) $t->trip_id,
				'familyMemberId' => $t->family_member_id ? (int) $t->family_member_id : null,
				'isPrimaryUser'  => (bool) $t->is_primary_user,
				'name'           => $t->is_primary_user ? wp_get_current_user()->display_name : $t->name,
				'relationship'   => $t->is_primary_user ? 'self' : $t->relationship,
				'color'          => $t->is_primary_user ? '#6366F1' : $t->color,
			);
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'tripId'    => $trip_id,
				'travelers' => $formatted,
			)
		);
	}

	/**
	 * Update trip travelers.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_trip_travelers( $request ) {
		global $wpdb;

		$trip_id = (int) $request['trip_id'];
		$user_id = get_current_user_id();

		// Verify trip belongs to user.
		$trips_table = R2F_Schengen_Schema::get_table( 'trips' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trip = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $trips_table WHERE id = %d AND user_id = %d", $trip_id, $user_id )
		);

		if ( ! $trip ) {
			return new WP_Error(
				'not_found',
				__( 'Trip not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );

		// Get requested travelers.
		$include_primary   = $request['includePrimaryUser'] ?? true;
		$family_member_ids = $request['familyMemberIds'] ?? array();

		// Clear existing travelers.
		$wpdb->delete( $travelers_table, array( 'trip_id' => $trip_id ) );

		// Add primary user if included.
		if ( $include_primary ) {
			$wpdb->insert(
				$travelers_table,
				array(
					'trip_id'          => $trip_id,
					'family_member_id' => null,
					'is_primary_user'  => 1,
				)
			);
		}

		// Add family members.
		foreach ( $family_member_ids as $member_id ) {
			// Verify member belongs to user.
			$member = $this->get_member_by_id( $member_id );
			if ( ! is_wp_error( $member ) ) {
				$wpdb->insert(
					$travelers_table,
					array(
						'trip_id'          => $trip_id,
						'family_member_id' => (int) $member_id,
						'is_primary_user'  => 0,
					)
				);
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Trip travelers updated.', 'r2f-schengen' ),
			)
		);
	}

	/**
	 * Get member by ID with ownership check.
	 *
	 * @param int $member_id Member ID.
	 * @return object|WP_Error
	 */
	private function get_member_by_id( $member_id ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = R2F_Schengen_Schema::get_table( 'family_members' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$member = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d AND user_id = %d AND is_active = 1",
				$member_id,
				$user_id
			)
		);

		if ( ! $member ) {
			return new WP_Error(
				'not_found',
				__( 'Family member not found.', 'r2f-schengen' ),
				array( 'status' => 404 )
			);
		}

		return $member;
	}

	/**
	 * Get trips for a family member.
	 *
	 * @param int $member_id Family member ID.
	 * @return array
	 */
	private function get_member_trips( $member_id ) {
		global $wpdb;

		$trips_table     = R2F_Schengen_Schema::get_table( 'trips' );
		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM $trips_table t
				 INNER JOIN $travelers_table tt ON t.id = tt.trip_id
				 WHERE tt.family_member_id = %d
				 ORDER BY t.start_date DESC",
				$member_id
			)
		);

		return $trips;
	}

	/**
	 * Get trips for primary user (where is_primary_user = 1 or no travelers set).
	 *
	 * @return array
	 */
	private function get_primary_user_trips() {
		global $wpdb;

		$user_id         = get_current_user_id();
		$trips_table     = R2F_Schengen_Schema::get_table( 'trips' );
		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );

		// Get trips where primary user is included OR no travelers are set (legacy trips).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM $trips_table t
				 LEFT JOIN $travelers_table tt ON t.id = tt.trip_id AND tt.is_primary_user = 1
				 WHERE t.user_id = %d
				   AND (tt.id IS NOT NULL OR NOT EXISTS (
				       SELECT 1 FROM $travelers_table tt2 WHERE tt2.trip_id = t.id
				   ))
				 ORDER BY t.start_date DESC",
				$user_id
			)
		);

		return $trips;
	}

	/**
	 * Calculate Schengen summary from trips.
	 *
	 * @param array $trips Array of trip objects.
	 * @return array Summary data.
	 */
	private function calculate_summary( $trips ) {
		$today        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = clone $today;
		$window_start->modify( '-179 days' );

		$days_used   = 0;
		$trip_count  = 0;
		$next_expire = null;

		foreach ( $trips as $trip ) {
			$start = new DateTime( $trip->start_date );
			$end   = new DateTime( $trip->end_date );

			// Only count days within the 180-day window.
			if ( $end < $window_start ) {
				continue;
			}

			// Adjust start if before window.
			if ( $start < $window_start ) {
				$start = clone $window_start;
			}

			// Adjust end if after today (for future trips).
			if ( $end > $today ) {
				$end = clone $today;
			}

			// Skip if adjusted dates result in no days.
			if ( $start > $end ) {
				continue;
			}

			$interval    = $start->diff( $end );
			$trip_days   = $interval->days + 1; // Include both start and end date.
			$days_used  += $trip_days;
			$trip_count++;

			// Track earliest expiring date.
			if ( null === $next_expire || $start < $next_expire ) {
				$next_expire = clone $start;
			}
		}

		$days_remaining = max( 0, 90 - $days_used );

		// Determine status.
		if ( $days_used >= 90 ) {
			$status = 'critical';
		} elseif ( $days_used >= 80 ) {
			$status = 'danger';
		} elseif ( $days_used >= 60 ) {
			$status = 'warning';
		} else {
			$status = 'safe';
		}

		return array(
			'daysUsed'       => $days_used,
			'daysRemaining'  => $days_remaining,
			'status'         => $status,
			'tripCount'      => $trip_count,
			'windowStart'    => $window_start->format( 'Y-m-d' ),
			'windowEnd'      => $today->format( 'Y-m-d' ),
			'nextExpiration' => $next_expire ? $next_expire->modify( '+180 days' )->format( 'Y-m-d' ) : null,
		);
	}

	/**
	 * Format family member for API response.
	 *
	 * @param object $member Database row.
	 * @return array Formatted member.
	 */
	private function format_family_member( $member ) {
		return array(
			'id'             => (int) $member->id,
			'name'           => $member->name,
			'relationship'   => $member->relationship,
			'birthDate'      => $member->birth_date,
			'nationality'    => $member->nationality,
			'passportNumber' => $member->passport_number,
			'passportExpiry' => $member->passport_expiry,
			'color'          => $member->color ?? '#3B82F6',
			'notes'          => $member->notes,
			'sortOrder'      => (int) $member->sort_order,
			'createdAt'      => $member->created_at,
			'updatedAt'      => $member->updated_at,
		);
	}
}
