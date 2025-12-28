<?php
/**
 * Analytics for Schengen Tracker.
 *
 * Provides travel pattern analysis, historical data, and compliance tracking.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics management class.
 */
class R2F_Schengen_Analytics {

	/**
	 * Singleton instance.
	 *
	 * @var R2F_Schengen_Analytics|null
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
	 * @return R2F_Schengen_Analytics
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
		add_action( 'r2f_schengen_daily_cron', array( $this, 'record_daily_snapshot' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Analytics overview.
		register_rest_route(
			$this->namespace,
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics_overview' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'period' => array(
						'type'    => 'string',
						'enum'    => array( '30d', '90d', '180d', '1y', 'all' ),
						'default' => '180d',
					),
				),
			)
		);

		// Travel patterns (countries visited).
		register_rest_route(
			$this->namespace,
			'/analytics/patterns',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_travel_patterns' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'period' => array(
						'type'    => 'string',
						'enum'    => array( '30d', '90d', '180d', '1y', 'all' ),
						'default' => '1y',
					),
				),
			)
		);

		// Historical compliance data.
		register_rest_route(
			$this->namespace,
			'/analytics/history',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_compliance_history' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'period'         => array(
						'type'    => 'string',
						'enum'    => array( '30d', '90d', '180d', '1y', 'all' ),
						'default' => '180d',
					),
					'familyMemberId' => array(
						'type' => 'integer',
					),
				),
			)
		);

		// Monthly breakdown.
		register_rest_route(
			$this->namespace,
			'/analytics/monthly',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_monthly_breakdown' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'year' => array(
						'type'    => 'integer',
						'default' => (int) gmdate( 'Y' ),
					),
				),
			)
		);

		// Export analytics data.
		register_rest_route(
			$this->namespace,
			'/analytics/export',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_analytics' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'format' => array(
						'type'    => 'string',
						'enum'    => array( 'json', 'csv' ),
						'default' => 'json',
					),
					'period' => array(
						'type'    => 'string',
						'enum'    => array( '30d', '90d', '180d', '1y', 'all' ),
						'default' => 'all',
					),
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
	 * Get analytics overview.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_analytics_overview( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$period  = $request['period'] ?? '180d';
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$date_filter = $this->get_date_filter( $period );

		// Total trips.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_trips = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE user_id = %d $date_filter",
				$user_id
			)
		);

		// Total days traveled.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_days = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(DATEDIFF(end_date, start_date) + 1) FROM $table WHERE user_id = %d $date_filter",
				$user_id
			)
		);

		// Unique countries.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$unique_countries = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT country) FROM $table WHERE user_id = %d $date_filter",
				$user_id
			)
		);

		// Average trip length.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$avg_trip_length = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(DATEDIFF(end_date, start_date) + 1) FROM $table WHERE user_id = %d $date_filter",
				$user_id
			)
		);

		// Longest trip.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$longest_trip = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *, DATEDIFF(end_date, start_date) + 1 as days
				 FROM $table
				 WHERE user_id = %d $date_filter
				 ORDER BY days DESC
				 LIMIT 1",
				$user_id
			)
		);

		// Most visited country.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$most_visited = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT country, COUNT(*) as visit_count,
				        SUM(DATEDIFF(end_date, start_date) + 1) as total_days
				 FROM $table
				 WHERE user_id = %d $date_filter
				 GROUP BY country
				 ORDER BY total_days DESC
				 LIMIT 1",
				$user_id
			)
		);

		// Current compliance status.
		$compliance = $this->calculate_current_compliance( $user_id );

		return rest_ensure_response(
			array(
				'success' => true,
				'period'  => $period,
				'stats'   => array(
					'totalTrips'        => (int) $total_trips,
					'totalDays'         => (int) ( $total_days ?? 0 ),
					'uniqueCountries'   => (int) $unique_countries,
					'avgTripLength'     => round( (float) ( $avg_trip_length ?? 0 ), 1 ),
					'longestTrip'       => $longest_trip ? array(
						'country'   => $longest_trip->country,
						'days'      => (int) $longest_trip->days,
						'startDate' => $longest_trip->start_date,
						'endDate'   => $longest_trip->end_date,
					) : null,
					'mostVisited'       => $most_visited ? array(
						'country'    => $most_visited->country,
						'visitCount' => (int) $most_visited->visit_count,
						'totalDays'  => (int) $most_visited->total_days,
					) : null,
				),
				'compliance' => $compliance,
			)
		);
	}

	/**
	 * Get travel patterns (countries visited).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_travel_patterns( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$period  = $request['period'] ?? '1y';
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$date_filter = $this->get_date_filter( $period );

		// Countries breakdown.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$countries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country,
				        COUNT(*) as trip_count,
				        SUM(DATEDIFF(end_date, start_date) + 1) as total_days,
				        MIN(start_date) as first_visit,
				        MAX(end_date) as last_visit
				 FROM $table
				 WHERE user_id = %d $date_filter
				 GROUP BY country
				 ORDER BY total_days DESC",
				$user_id
			)
		);

		$formatted = array();
		$total_days = 0;

		foreach ( $countries as $c ) {
			$total_days += (int) $c->total_days;
		}

		foreach ( $countries as $c ) {
			$formatted[] = array(
				'country'    => $c->country,
				'tripCount'  => (int) $c->trip_count,
				'totalDays'  => (int) $c->total_days,
				'percentage' => $total_days > 0 ? round( ( $c->total_days / $total_days ) * 100, 1 ) : 0,
				'firstVisit' => $c->first_visit,
				'lastVisit'  => $c->last_visit,
			);
		}

		// Travel frequency by month.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$monthly = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(start_date, '%%Y-%%m') as month,
				        COUNT(*) as trip_count,
				        SUM(DATEDIFF(end_date, start_date) + 1) as total_days
				 FROM $table
				 WHERE user_id = %d $date_filter
				 GROUP BY month
				 ORDER BY month ASC",
				$user_id
			)
		);

		$monthly_formatted = array();
		foreach ( $monthly as $m ) {
			$monthly_formatted[] = array(
				'month'     => $m->month,
				'tripCount' => (int) $m->trip_count,
				'totalDays' => (int) $m->total_days,
			);
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'period'    => $period,
				'countries' => $formatted,
				'monthly'   => $monthly_formatted,
			)
		);
	}

	/**
	 * Get compliance history.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_compliance_history( $request ) {
		global $wpdb;

		$user_id          = get_current_user_id();
		$period           = $request['period'] ?? '180d';
		$family_member_id = $request['familyMemberId'] ?? null;
		$table            = R2F_Schengen_Schema::get_table( 'analytics_snapshots' );

		$date_filter = $this->get_date_filter( $period, 'snapshot_date' );

		$member_filter = '';
		if ( $family_member_id ) {
			$member_filter = $wpdb->prepare( ' AND family_member_id = %d', $family_member_id );
		} else {
			$member_filter = ' AND family_member_id IS NULL';
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$history = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				 WHERE user_id = %d $member_filter $date_filter
				 ORDER BY snapshot_date ASC",
				$user_id
			)
		);

		$formatted = array();
		foreach ( $history as $h ) {
			$formatted[] = array(
				'date'          => $h->snapshot_date,
				'daysUsed'      => (int) $h->days_used,
				'daysRemaining' => (int) $h->days_remaining,
				'status'        => $h->status,
				'tripCount'     => (int) $h->trip_count,
			);
		}

		// If no history, calculate from trips.
		if ( empty( $formatted ) ) {
			$formatted = $this->calculate_historical_compliance( $user_id, $period, $family_member_id );
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'period'         => $period,
				'familyMemberId' => $family_member_id,
				'history'        => $formatted,
			)
		);
	}

	/**
	 * Get monthly breakdown for a year.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_monthly_breakdown( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$year    = $request['year'] ?? (int) gmdate( 'Y' );
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		// Get all trips for the year.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				 WHERE user_id = %d
				   AND (YEAR(start_date) = %d OR YEAR(end_date) = %d)
				 ORDER BY start_date ASC",
				$user_id,
				$year,
				$year
			)
		);

		// Calculate days per month.
		$months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$month_key = sprintf( '%d-%02d', $year, $m );
			$months[ $month_key ] = array(
				'month'     => $month_key,
				'label'     => gmdate( 'F', mktime( 0, 0, 0, $m, 1, $year ) ),
				'days'      => 0,
				'tripCount' => 0,
				'countries' => array(),
			);
		}

		foreach ( $trips as $trip ) {
			$start = new DateTime( $trip->start_date );
			$end   = new DateTime( $trip->end_date );

			// Iterate through each day of the trip.
			$current = clone $start;
			while ( $current <= $end ) {
				$trip_year = (int) $current->format( 'Y' );
				if ( $trip_year === $year ) {
					$month_key = $current->format( 'Y-m' );
					if ( isset( $months[ $month_key ] ) ) {
						$months[ $month_key ]['days']++;

						if ( ! in_array( $trip->country, $months[ $month_key ]['countries'], true ) ) {
							$months[ $month_key ]['countries'][] = $trip->country;
						}
					}
				}
				$current->modify( '+1 day' );
			}

			// Count trip if it starts in this year.
			$start_month = $start->format( 'Y-m' );
			if ( isset( $months[ $start_month ] ) ) {
				$months[ $start_month ]['tripCount']++;
			}
		}

		// Convert countries array to count.
		foreach ( $months as &$m ) {
			$m['countryCount'] = count( $m['countries'] );
			$m['countries']    = implode( ', ', $m['countries'] );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'year'    => $year,
				'months'  => array_values( $months ),
			)
		);
	}

	/**
	 * Export analytics data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_analytics( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$format  = $request['format'] ?? 'json';
		$period  = $request['period'] ?? 'all';
		$table   = R2F_Schengen_Schema::get_table( 'trips' );

		$date_filter = $this->get_date_filter( $period );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, start_date, end_date, country, category, notes,
				        DATEDIFF(end_date, start_date) + 1 as days,
				        created_at
				 FROM $table
				 WHERE user_id = %d $date_filter
				 ORDER BY start_date DESC",
				$user_id
			)
		);

		$data = array(
			'exportDate' => gmdate( 'Y-m-d H:i:s' ),
			'period'     => $period,
			'tripCount'  => count( $trips ),
			'trips'      => array(),
		);

		foreach ( $trips as $trip ) {
			$data['trips'][] = array(
				'startDate' => $trip->start_date,
				'endDate'   => $trip->end_date,
				'country'   => $trip->country,
				'category'  => $trip->category,
				'days'      => (int) $trip->days,
				'notes'     => $trip->notes,
				'createdAt' => $trip->created_at,
			);
		}

		if ( 'csv' === $format ) {
			$csv = "Start Date,End Date,Country,Category,Days,Notes\n";
			foreach ( $data['trips'] as $trip ) {
				$csv .= sprintf(
					"%s,%s,\"%s\",%s,%d,\"%s\"\n",
					$trip['startDate'],
					$trip['endDate'],
					str_replace( '"', '""', $trip['country'] ),
					$trip['category'],
					$trip['days'],
					str_replace( '"', '""', $trip['notes'] ?? '' )
				);
			}

			return rest_ensure_response(
				array(
					'success'  => true,
					'format'   => 'csv',
					'filename' => 'schengen-analytics-' . gmdate( 'Y-m-d' ) . '.csv',
					'content'  => $csv,
				)
			);
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'format'   => 'json',
				'filename' => 'schengen-analytics-' . gmdate( 'Y-m-d' ) . '.json',
				'data'     => $data,
			)
		);
	}

	/**
	 * Record daily compliance snapshot (called by cron).
	 */
	public function record_daily_snapshot() {
		global $wpdb;

		$trips_table     = R2F_Schengen_Schema::get_table( 'trips' );
		$analytics_table = R2F_Schengen_Schema::get_table( 'analytics_snapshots' );
		$family_table    = R2F_Schengen_Schema::get_table( 'family_members' );

		// Get all users with trips.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$users = $wpdb->get_col( "SELECT DISTINCT user_id FROM $trips_table" );

		$today = gmdate( 'Y-m-d' );

		foreach ( $users as $user_id ) {
			// Record primary user snapshot.
			$compliance = $this->calculate_current_compliance( $user_id );

			$wpdb->replace(
				$analytics_table,
				array(
					'user_id'           => $user_id,
					'family_member_id'  => null,
					'jurisdiction_code' => 'schengen',
					'snapshot_date'     => $today,
					'days_used'         => $compliance['daysUsed'],
					'days_remaining'    => $compliance['daysRemaining'],
					'status'            => $compliance['status'],
					'trip_count'        => $compliance['tripCount'],
					'window_start'      => $compliance['windowStart'],
					'window_end'        => $compliance['windowEnd'],
				)
			);

			// Record family member snapshots.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$family_members = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM $family_table WHERE user_id = %d AND is_active = 1",
					$user_id
				)
			);

			foreach ( $family_members as $member_id ) {
				$member_compliance = $this->calculate_member_compliance( $user_id, $member_id );

				$wpdb->replace(
					$analytics_table,
					array(
						'user_id'           => $user_id,
						'family_member_id'  => $member_id,
						'jurisdiction_code' => 'schengen',
						'snapshot_date'     => $today,
						'days_used'         => $member_compliance['daysUsed'],
						'days_remaining'    => $member_compliance['daysRemaining'],
						'status'            => $member_compliance['status'],
						'trip_count'        => $member_compliance['tripCount'],
						'window_start'      => $member_compliance['windowStart'],
						'window_end'        => $member_compliance['windowEnd'],
					)
				);
			}
		}
	}

	/**
	 * Get date filter SQL clause.
	 *
	 * @param string $period Period identifier.
	 * @param string $column Column name to filter on.
	 * @return string SQL clause.
	 */
	private function get_date_filter( $period, $column = 'start_date' ) {
		$days = array(
			'30d'  => 30,
			'90d'  => 90,
			'180d' => 180,
			'1y'   => 365,
			'all'  => 0,
		);

		$num_days = $days[ $period ] ?? 180;

		if ( 0 === $num_days ) {
			return '';
		}

		$date = gmdate( 'Y-m-d', strtotime( "-{$num_days} days" ) );

		return " AND $column >= '$date'";
	}

	/**
	 * Calculate current compliance for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array Compliance data.
	 */
	private function calculate_current_compliance( $user_id ) {
		global $wpdb;

		$table = R2F_Schengen_Schema::get_table( 'trips' );

		$today        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = clone $today;
		$window_start->modify( '-179 days' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table
				 WHERE user_id = %d
				   AND end_date >= %s
				   AND start_date <= %s
				 ORDER BY start_date ASC",
				$user_id,
				$window_start->format( 'Y-m-d' ),
				$today->format( 'Y-m-d' )
			)
		);

		$days_used  = 0;
		$trip_count = 0;

		foreach ( $trips as $trip ) {
			$start = new DateTime( $trip->start_date );
			$end   = new DateTime( $trip->end_date );

			// Adjust to window bounds.
			if ( $start < $window_start ) {
				$start = clone $window_start;
			}
			if ( $end > $today ) {
				$end = clone $today;
			}

			if ( $start <= $end ) {
				$interval   = $start->diff( $end );
				$days_used += $interval->days + 1;
				$trip_count++;
			}
		}

		$days_remaining = max( 0, 90 - $days_used );

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
			'daysUsed'      => $days_used,
			'daysRemaining' => $days_remaining,
			'status'        => $status,
			'tripCount'     => $trip_count,
			'windowStart'   => $window_start->format( 'Y-m-d' ),
			'windowEnd'     => $today->format( 'Y-m-d' ),
		);
	}

	/**
	 * Calculate compliance for a family member.
	 *
	 * @param int $user_id   User ID.
	 * @param int $member_id Family member ID.
	 * @return array Compliance data.
	 */
	private function calculate_member_compliance( $user_id, $member_id ) {
		global $wpdb;

		$trips_table     = R2F_Schengen_Schema::get_table( 'trips' );
		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );

		$today        = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = clone $today;
		$window_start->modify( '-179 days' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$trips = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.* FROM $trips_table t
				 INNER JOIN $travelers_table tt ON t.id = tt.trip_id
				 WHERE t.user_id = %d
				   AND tt.family_member_id = %d
				   AND t.end_date >= %s
				   AND t.start_date <= %s
				 ORDER BY t.start_date ASC",
				$user_id,
				$member_id,
				$window_start->format( 'Y-m-d' ),
				$today->format( 'Y-m-d' )
			)
		);

		$days_used  = 0;
		$trip_count = 0;

		foreach ( $trips as $trip ) {
			$start = new DateTime( $trip->start_date );
			$end   = new DateTime( $trip->end_date );

			if ( $start < $window_start ) {
				$start = clone $window_start;
			}
			if ( $end > $today ) {
				$end = clone $today;
			}

			if ( $start <= $end ) {
				$interval   = $start->diff( $end );
				$days_used += $interval->days + 1;
				$trip_count++;
			}
		}

		$days_remaining = max( 0, 90 - $days_used );

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
			'daysUsed'      => $days_used,
			'daysRemaining' => $days_remaining,
			'status'        => $status,
			'tripCount'     => $trip_count,
			'windowStart'   => $window_start->format( 'Y-m-d' ),
			'windowEnd'     => $today->format( 'Y-m-d' ),
		);
	}

	/**
	 * Calculate historical compliance data from trips.
	 *
	 * @param int         $user_id          User ID.
	 * @param string      $period           Period identifier.
	 * @param int|null    $family_member_id Family member ID or null for primary user.
	 * @return array Historical data points.
	 */
	private function calculate_historical_compliance( $user_id, $period, $family_member_id = null ) {
		global $wpdb;

		$trips_table     = R2F_Schengen_Schema::get_table( 'trips' );
		$travelers_table = R2F_Schengen_Schema::get_table( 'trip_travelers' );

		$days = array(
			'30d'  => 30,
			'90d'  => 90,
			'180d' => 180,
			'1y'   => 365,
			'all'  => 365,
		);

		$num_days = $days[ $period ] ?? 180;

		// Get all trips for the user.
		if ( $family_member_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$trips = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.* FROM $trips_table t
					 INNER JOIN $travelers_table tt ON t.id = tt.trip_id
					 WHERE t.user_id = %d AND tt.family_member_id = %d
					 ORDER BY t.start_date ASC",
					$user_id,
					$family_member_id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$trips = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $trips_table WHERE user_id = %d ORDER BY start_date ASC",
					$user_id
				)
			);
		}

		$history = array();
		$today   = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		// Sample every 7 days for longer periods, every day for shorter.
		$interval = $num_days > 90 ? 7 : 1;

		for ( $i = $num_days; $i >= 0; $i -= $interval ) {
			$check_date   = clone $today;
			$check_date->modify( "-{$i} days" );
			$window_start = clone $check_date;
			$window_start->modify( '-179 days' );

			$days_used  = 0;
			$trip_count = 0;

			foreach ( $trips as $trip ) {
				$start = new DateTime( $trip->start_date );
				$end   = new DateTime( $trip->end_date );

				// Skip if outside window.
				if ( $end < $window_start || $start > $check_date ) {
					continue;
				}

				// Adjust to window bounds.
				if ( $start < $window_start ) {
					$start = clone $window_start;
				}
				if ( $end > $check_date ) {
					$end = clone $check_date;
				}

				if ( $start <= $end ) {
					$diff       = $start->diff( $end );
					$days_used += $diff->days + 1;
					$trip_count++;
				}
			}

			$days_remaining = max( 0, 90 - $days_used );

			if ( $days_used >= 90 ) {
				$status = 'critical';
			} elseif ( $days_used >= 80 ) {
				$status = 'danger';
			} elseif ( $days_used >= 60 ) {
				$status = 'warning';
			} else {
				$status = 'safe';
			}

			$history[] = array(
				'date'          => $check_date->format( 'Y-m-d' ),
				'daysUsed'      => $days_used,
				'daysRemaining' => $days_remaining,
				'status'        => $status,
				'tripCount'     => $trip_count,
			);
		}

		return $history;
	}
}
