<?php
/**
 * Schengen Tracker REST API
 *
 * Provides REST API endpoints for the Schengen 90/180 day tracker.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Schengen
 * @since       2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FRAMT_Schengen_API
 *
 * REST API endpoints for Schengen trip tracking.
 *
 * @since 2.1.0
 */
class FRAMT_Schengen_API {

    /**
     * API namespace
     *
     * @var string
     */
    const NAMESPACE = 'fra-portal/v1';

    /**
     * Singleton instance
     *
     * @var FRAMT_Schengen_API|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return FRAMT_Schengen_API
     */
    public static function get_instance(): FRAMT_Schengen_API {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Get all trips / Create trip
        register_rest_route(
            self::NAMESPACE,
            '/schengen/trips',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_trips' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_trip' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                    'args'                => $this->get_trip_args(),
                ),
            )
        );

        // Get/Update/Delete single trip
        register_rest_route(
            self::NAMESPACE,
            '/schengen/trips/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_trip' ),
                    'permission_callback' => array( $this, 'check_trip_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_trip' ),
                    'permission_callback' => array( $this, 'check_trip_permission' ),
                    'args'                => $this->get_trip_args(),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_trip' ),
                    'permission_callback' => array( $this, 'check_trip_permission' ),
                ),
            )
        );

        // Get summary/stats
        register_rest_route(
            self::NAMESPACE,
            '/schengen/summary',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_summary' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Get/Update settings
        register_rest_route(
            self::NAMESPACE,
            '/schengen/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_settings' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_settings' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
            )
        );
    }

    /**
     * Get argument schema for trip endpoints
     *
     * @return array
     */
    private function get_trip_args(): array {
        return array(
            'start_date' => array(
                'type'              => 'string',
                'format'            => 'date',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'end_date' => array(
                'type'              => 'string',
                'format'            => 'date',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'country' => array(
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'category' => array(
                'type'              => 'string',
                'enum'              => array( 'personal', 'business' ),
                'default'           => 'personal',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'notes' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        );
    }

    /**
     * Check if user is logged in
     *
     * @return bool|WP_Error
     */
    public function check_permission() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                'You must be logged in to access this resource.',
                array( 'status' => 401 )
            );
        }
        return true;
    }

    /**
     * Check if user owns the trip
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_trip_permission( WP_REST_Request $request ) {
        $permission = $this->check_permission();
        if ( is_wp_error( $permission ) ) {
            return $permission;
        }

        $trip_id = (int) $request->get_param( 'id' );
        $trip    = $this->get_trip_by_id( $trip_id );

        if ( ! $trip ) {
            return new WP_Error(
                'not_found',
                'Trip not found.',
                array( 'status' => 404 )
            );
        }

        if ( (int) $trip->user_id !== get_current_user_id() ) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to access this trip.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get trip by ID
     *
     * @param int $trip_id Trip ID.
     * @return object|null
     */
    private function get_trip_by_id( int $trip_id ) {
        global $wpdb;
        $table = FRAMT_Portal_Schema::get_table( 'schengen_trips' );

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $trip_id )
        );
    }

    /**
     * Get all trips for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_trips( WP_REST_Request $request ) {
        global $wpdb;
        $table   = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $user_id = get_current_user_id();

        $trips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
                $user_id
            )
        );

        return rest_ensure_response( array_map( array( $this, 'format_trip' ), $trips ) );
    }

    /**
     * Get single trip
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_trip( WP_REST_Request $request ) {
        $trip_id = (int) $request->get_param( 'id' );
        $trip    = $this->get_trip_by_id( $trip_id );

        return rest_ensure_response( $this->format_trip( $trip ) );
    }

    /**
     * Create a new trip
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_trip( WP_REST_Request $request ) {
        global $wpdb;

        $params = $request->get_json_params();

        // Validate dates
        $validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $table  = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'    => get_current_user_id(),
                'start_date' => sanitize_text_field( $params['start_date'] ),
                'end_date'   => sanitize_text_field( $params['end_date'] ),
                'country'    => sanitize_text_field( $params['country'] ),
                'category'   => isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : 'personal',
                'notes'      => isset( $params['notes'] ) ? sanitize_textarea_field( $params['notes'] ) : null,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'db_error',
                'Failed to create trip.',
                array( 'status' => 500 )
            );
        }

        $trip = $this->get_trip_by_id( $wpdb->insert_id );
        return rest_ensure_response( $this->format_trip( $trip ) );
    }

    /**
     * Update an existing trip
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_trip( WP_REST_Request $request ) {
        global $wpdb;

        $trip_id = (int) $request->get_param( 'id' );
        $params  = $request->get_json_params();

        // Validate dates if provided
        if ( isset( $params['start_date'] ) && isset( $params['end_date'] ) ) {
            $validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
            if ( is_wp_error( $validation ) ) {
                return $validation;
            }
        }

        $table = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $data  = array();
        $format = array();

        if ( isset( $params['start_date'] ) ) {
            $data['start_date'] = sanitize_text_field( $params['start_date'] );
            $format[] = '%s';
        }
        if ( isset( $params['end_date'] ) ) {
            $data['end_date'] = sanitize_text_field( $params['end_date'] );
            $format[] = '%s';
        }
        if ( isset( $params['country'] ) ) {
            $data['country'] = sanitize_text_field( $params['country'] );
            $format[] = '%s';
        }
        if ( isset( $params['category'] ) ) {
            $data['category'] = sanitize_text_field( $params['category'] );
            $format[] = '%s';
        }
        if ( array_key_exists( 'notes', $params ) ) {
            $data['notes'] = $params['notes'] ? sanitize_textarea_field( $params['notes'] ) : null;
            $format[] = '%s';
        }

        if ( empty( $data ) ) {
            return new WP_Error(
                'no_data',
                'No data provided for update.',
                array( 'status' => 400 )
            );
        }

        $result = $wpdb->update(
            $table,
            $data,
            array( 'id' => $trip_id ),
            $format,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'db_error',
                'Failed to update trip.',
                array( 'status' => 500 )
            );
        }

        $trip = $this->get_trip_by_id( $trip_id );
        return rest_ensure_response( $this->format_trip( $trip ) );
    }

    /**
     * Delete a trip
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_trip( WP_REST_Request $request ) {
        global $wpdb;

        $trip_id = (int) $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'schengen_trips' );

        $result = $wpdb->delete(
            $table,
            array( 'id' => $trip_id ),
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'db_error',
                'Failed to delete trip.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'deleted' => true, 'id' => $trip_id ) );
    }

    /**
     * Get Schengen summary for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_summary( WP_REST_Request $request ) {
        global $wpdb;

        $table   = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $user_id = get_current_user_id();

        // Get settings for thresholds
        $settings = $this->get_user_settings( $user_id );

        // Calculate the 180-day window
        $today       = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $window_start = ( clone $today )->modify( '-179 days' );

        // Get all trips that overlap with the window
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

        // Calculate days used
        $days_used = $this->calculate_days_in_window( $trips, $window_start, $today );

        // Find next expiration date
        $next_expiration = $this->find_next_expiration( $trips, $window_start, $today );

        // Determine status
        $status = $this->get_status( $days_used, $settings['yellow_threshold'], $settings['red_threshold'] );

        return rest_ensure_response( array(
            'daysUsed'         => $days_used,
            'daysRemaining'    => max( 0, 90 - $days_used ),
            'windowStart'      => $window_start->format( 'Y-m-d' ),
            'windowEnd'        => $today->format( 'Y-m-d' ),
            'status'           => $status,
            'nextExpiration'   => $next_expiration,
            'statusThresholds' => array(
                'yellow' => $settings['yellow_threshold'],
                'red'    => $settings['red_threshold'],
            ),
        ) );
    }

    /**
     * Get user settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_settings( WP_REST_Request $request ) {
        $user_id  = get_current_user_id();
        $settings = $this->get_user_settings( $user_id );

        return rest_ensure_response( $this->format_settings_response( $settings ) );
    }

    /**
     * Update user settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_settings( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        $settings = $this->get_user_settings( $user_id );

        if ( isset( $params['yellowThreshold'] ) ) {
            $settings['yellow_threshold'] = max( 1, min( 89, (int) $params['yellowThreshold'] ) );
        }
        if ( isset( $params['redThreshold'] ) ) {
            $settings['red_threshold'] = max( 1, min( 90, (int) $params['redThreshold'] ) );
        }
        if ( isset( $params['emailAlerts'] ) ) {
            $settings['email_alerts'] = (bool) $params['emailAlerts'];
        }
        if ( isset( $params['upcomingTripReminders'] ) ) {
            $settings['upcoming_trip_reminders'] = (bool) $params['upcomingTripReminders'];
        }

        update_user_meta( $user_id, 'fra_schengen_settings', $settings );

        return rest_ensure_response( $this->format_settings_response( $settings ) );
    }

    /**
     * Format settings for API response (snake_case to camelCase)
     *
     * @param array $settings Settings array.
     * @return array Formatted settings.
     */
    private function format_settings_response( array $settings ): array {
        return array(
            'yellowThreshold'        => $settings['yellow_threshold'],
            'redThreshold'           => $settings['red_threshold'],
            'emailAlerts'            => $settings['email_alerts'],
            'upcomingTripReminders'  => $settings['upcoming_trip_reminders'],
        );
    }

    /**
     * Get user's Schengen settings
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_user_settings( int $user_id ): array {
        $defaults = array(
            'yellow_threshold'        => 60,
            'red_threshold'           => 80,
            'email_alerts'            => false,
            'upcoming_trip_reminders' => true,
        );

        $saved = get_user_meta( $user_id, 'fra_schengen_settings', true );

        if ( ! is_array( $saved ) ) {
            return $defaults;
        }

        return array_merge( $defaults, $saved );
    }

    /**
     * Calculate days spent in Schengen within the 180-day window
     *
     * @param array    $trips        Trips overlapping the window.
     * @param DateTime $window_start Start of the 180-day window.
     * @param DateTime $window_end   End of the window (today).
     * @return int Number of days.
     */
    private function calculate_days_in_window( array $trips, DateTime $window_start, DateTime $window_end ): int {
        $days = array();

        foreach ( $trips as $trip ) {
            $trip_start = new DateTime( $trip->start_date );
            $trip_end   = new DateTime( $trip->end_date );

            // Clamp to window
            $effective_start = $trip_start < $window_start ? $window_start : $trip_start;
            $effective_end   = $trip_end > $window_end ? $window_end : $trip_end;

            if ( $effective_start <= $effective_end ) {
                $current = clone $effective_start;
                while ( $current <= $effective_end ) {
                    $days[ $current->format( 'Y-m-d' ) ] = true;
                    $current->modify( '+1 day' );
                }
            }
        }

        return count( $days );
    }

    /**
     * Find when the next day will drop off the 180-day window
     *
     * @param array    $trips        Trips in the window.
     * @param DateTime $window_start Start of window.
     * @param DateTime $window_end   End of window.
     * @return string|null ISO date string or null.
     */
    private function find_next_expiration( array $trips, DateTime $window_start, DateTime $window_end ): ?string {
        $days = array();

        foreach ( $trips as $trip ) {
            $trip_start = new DateTime( $trip->start_date );
            $trip_end   = new DateTime( $trip->end_date );

            $effective_start = $trip_start < $window_start ? $window_start : $trip_start;
            $effective_end   = $trip_end > $window_end ? $window_end : $trip_end;

            if ( $effective_start <= $effective_end ) {
                $current = clone $effective_start;
                while ( $current <= $effective_end ) {
                    $days[] = $current->format( 'Y-m-d' );
                    $current->modify( '+1 day' );
                }
            }
        }

        if ( empty( $days ) ) {
            return null;
        }

        // Find earliest day
        sort( $days );
        $earliest = new DateTime( $days[0] );

        // It expires 180 days after that day
        $earliest->modify( '+180 days' );

        return $earliest->format( 'Y-m-d' );
    }

    /**
     * Get compliance status based on days used
     *
     * @param int $days_used       Days used.
     * @param int $yellow_threshold Yellow threshold.
     * @param int $red_threshold    Red threshold.
     * @return string Status: safe, warning, danger, or critical.
     */
    private function get_status( int $days_used, int $yellow_threshold, int $red_threshold ): string {
        if ( $days_used >= 90 ) {
            return 'critical';
        }
        if ( $days_used >= $red_threshold ) {
            return 'danger';
        }
        if ( $days_used >= $yellow_threshold ) {
            return 'warning';
        }
        return 'safe';
    }

    /**
     * Validate trip dates
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return true|WP_Error
     */
    private function validate_trip_dates( string $start_date, string $end_date ) {
        $start = strtotime( $start_date );
        $end   = strtotime( $end_date );

        if ( false === $start || false === $end ) {
            return new WP_Error(
                'invalid_date',
                'Invalid date format. Use YYYY-MM-DD.',
                array( 'status' => 400 )
            );
        }

        if ( $end < $start ) {
            return new WP_Error(
                'invalid_date_range',
                'End date must be on or after start date.',
                array( 'status' => 400 )
            );
        }

        // Check if trip exceeds 90 days
        $days = ( $end - $start ) / ( 60 * 60 * 24 ) + 1;
        if ( $days > 90 ) {
            return new WP_Error(
                'trip_too_long',
                'A single trip cannot exceed 90 days.',
                array( 'status' => 400 )
            );
        }

        return true;
    }

    /**
     * Format trip for API response
     *
     * @param object $trip Trip database row.
     * @return array
     */
    private function format_trip( $trip ): array {
        return array(
            'id'        => (string) $trip->id,
            'startDate' => $trip->start_date,
            'endDate'   => $trip->end_date,
            'country'   => $trip->country,
            'category'  => $trip->category,
            'notes'     => $trip->notes,
            'createdAt' => $trip->created_at,
            'updatedAt' => $trip->updated_at,
        );
    }
}
