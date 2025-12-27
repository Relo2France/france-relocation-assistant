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
     * Valid Schengen countries
     *
     * @var array
     */
    const SCHENGEN_COUNTRIES = array(
        'Austria',
        'Belgium',
        'Bulgaria',
        'Croatia',
        'Czech Republic',
        'Denmark',
        'Estonia',
        'Finland',
        'France',
        'Germany',
        'Greece',
        'Hungary',
        'Iceland',
        'Italy',
        'Latvia',
        'Liechtenstein',
        'Lithuania',
        'Luxembourg',
        'Malta',
        'Netherlands',
        'Norway',
        'Poland',
        'Portugal',
        'Romania',
        'Slovakia',
        'Slovenia',
        'Spain',
        'Sweden',
        'Switzerland',
    );

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

        // Get feature status (premium gating)
        register_rest_route(
            self::NAMESPACE,
            '/schengen/feature-status',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_feature_status' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Generate PDF report (premium feature)
        register_rest_route(
            self::NAMESPACE,
            '/schengen/report',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'generate_report' ),
                'permission_callback' => array( $this, 'check_premium_permission' ),
            )
        );

        // Planning tool - simulate future trip (premium feature)
        register_rest_route(
            self::NAMESPACE,
            '/schengen/simulate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'simulate_trip' ),
                'permission_callback' => array( $this, 'check_premium_permission' ),
            )
        );

        // Test email alert (for debugging)
        register_rest_route(
            self::NAMESPACE,
            '/schengen/test-alert',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'test_alert' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // ============================================
        // Location Tracking (Phase 1.1)
        // ============================================

        // Store current location (check-in)
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'store_location' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'lat' => array(
                        'type'              => 'number',
                        'required'          => true,
                        'sanitize_callback' => 'floatval',
                    ),
                    'lng' => array(
                        'type'              => 'number',
                        'required'          => true,
                        'sanitize_callback' => 'floatval',
                    ),
                    'accuracy' => array(
                        'type'              => 'number',
                        'sanitize_callback' => 'floatval',
                    ),
                    'source' => array(
                        'type'    => 'string',
                        'enum'    => array( 'browser', 'manual', 'calendar', 'checkin' ),
                        'default' => 'browser',
                    ),
                ),
            )
        );

        // Get location history
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/history',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_location_history' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'limit' => array(
                        'type'    => 'integer',
                        'default' => 30,
                        'minimum' => 1,
                        'maximum' => 100,
                    ),
                    'offset' => array(
                        'type'    => 'integer',
                        'default' => 0,
                        'minimum' => 0,
                    ),
                ),
            )
        );

        // Get today's location status
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/today',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_location_today' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Reverse geocode coordinates
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/geocode',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'reverse_geocode' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'lat' => array(
                        'type'     => 'number',
                        'required' => true,
                    ),
                    'lng' => array(
                        'type'     => 'number',
                        'required' => true,
                    ),
                ),
            )
        );

        // Delete location entry
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'delete_location' ),
                'permission_callback' => array( $this, 'check_location_permission' ),
            )
        );

        // Clear all location history
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/clear',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'clear_location_history' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        // Get/Update location settings
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_location_settings' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_location_settings' ),
                    'permission_callback' => array( $this, 'check_permission' ),
                ),
            )
        );

        // Detect country from IP
        register_rest_route(
            self::NAMESPACE,
            '/schengen/location/detect',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'detect_from_ip' ),
                'permission_callback' => array( $this, 'check_permission' ),
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
                'enum'              => self::SCHENGEN_COUNTRIES,
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

        // Get proposed threshold values (or use current)
        $yellow = isset( $params['yellowThreshold'] )
            ? max( 1, min( 89, (int) $params['yellowThreshold'] ) )
            : $settings['yellow_threshold'];

        $red = isset( $params['redThreshold'] )
            ? max( 1, min( 90, (int) $params['redThreshold'] ) )
            : $settings['red_threshold'];

        // Validate that yellow threshold is less than red threshold
        if ( $yellow >= $red ) {
            return new WP_Error(
                'invalid_thresholds',
                'Yellow threshold must be less than red threshold.',
                array( 'status' => 400 )
            );
        }

        $settings['yellow_threshold'] = $yellow;
        $settings['red_threshold']    = $red;

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

    // ============================================
    // Premium Feature Gating
    // ============================================

    /**
     * Free tier trip limit
     */
    const FREE_TRIP_LIMIT = 3;

    /**
     * Check if user has premium access to Schengen features
     *
     * @return bool|WP_Error
     */
    public function check_premium_permission() {
        $permission = $this->check_permission();
        if ( is_wp_error( $permission ) ) {
            return $permission;
        }

        $user_id = get_current_user_id();
        if ( ! $this->is_schengen_premium_enabled( $user_id ) ) {
            return new WP_Error(
                'premium_required',
                'This feature requires a premium subscription.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check if Schengen premium features are enabled for user
     *
     * Priority order:
     * 1. User meta manual override (admin can enable/disable per user)
     * 2. MemberPress membership check (if installed)
     * 3. Global beta setting fallback
     *
     * @param int $user_id User ID.
     * @return bool
     */
    private function is_schengen_premium_enabled( int $user_id ): bool {
        // Priority 1: Check user meta flag (manual override by admin)
        $manual_override = get_user_meta( $user_id, 'framt_schengen_premium_enabled', true );
        if ( '1' === $manual_override ) {
            return true;
        }
        if ( '0' === $manual_override ) {
            return false;
        }

        // Priority 2: Check MemberPress membership level
        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user = new \MeprUser( $user_id );

            // Check if user has any active membership
            if ( $mepr_user->is_active() ) {
                // Get configured premium membership IDs (comma-separated)
                // Default to empty, meaning ANY active membership grants access
                $premium_membership_ids = get_option( 'framt_schengen_premium_memberships', '' );

                if ( empty( $premium_membership_ids ) ) {
                    // No specific memberships configured = any active membership gets access
                    return true;
                }

                // Check specific membership IDs
                $membership_ids = array_map( 'trim', explode( ',', $premium_membership_ids ) );
                foreach ( $membership_ids as $membership_id ) {
                    if ( $mepr_user->is_active_on_membership( (int) $membership_id ) ) {
                        return true;
                    }
                }
            }

            // MemberPress is installed but user doesn't have qualifying membership
            return false;
        }

        // Priority 3: Fallback - check global setting (for non-MemberPress sites or beta testing)
        $global_enabled = get_option( 'framt_schengen_premium_enabled', '0' ); // Default OFF
        if ( '1' === $global_enabled ) {
            return true;
        }

        return false;
    }

    /**
     * Get feature status for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_feature_status( WP_REST_Request $request ) {
        global $wpdb;

        $user_id    = get_current_user_id();
        $is_premium = $this->is_schengen_premium_enabled( $user_id );

        // Get trip count for free tier limit check
        $table     = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $trip_count = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id )
        );

        return rest_ensure_response( array(
            'isPremium'       => $is_premium,
            'tripLimit'       => $is_premium ? null : self::FREE_TRIP_LIMIT,
            'tripCount'       => $trip_count,
            'canAddTrip'      => $is_premium || $trip_count < self::FREE_TRIP_LIMIT,
            'canUsePlanning'  => $is_premium,
            'canExportPdf'    => $is_premium,
            'upgradeUrl'      => $is_premium ? null : $this->get_upgrade_url(),
            'upgradeMessage'  => $is_premium ? null : 'Upgrade to Premium for unlimited trips, planning tools, and PDF reports.',
        ) );
    }

    /**
     * Get the upgrade URL for premium features
     *
     * Checks in order:
     * 1. Custom setting for Schengen upgrade URL
     * 2. MemberPress registration page (if installed)
     * 3. Site membership page fallback
     *
     * @return string Upgrade URL.
     */
    private function get_upgrade_url(): string {
        // Check for custom Schengen upgrade URL setting
        $custom_url = get_option( 'framt_schengen_upgrade_url', '' );
        if ( ! empty( $custom_url ) ) {
            return $custom_url;
        }

        // Try to get MemberPress registration page
        if ( class_exists( 'MeprOptions' ) ) {
            $mepr_options = \MeprOptions::fetch();
            if ( ! empty( $mepr_options->account_page_id ) ) {
                $account_url = get_permalink( $mepr_options->account_page_id );
                if ( $account_url ) {
                    return $account_url;
                }
            }
        }

        // Fallback to site membership page
        $membership_url = get_option( 'fra_membership_url', '/membership/' );
        return home_url( $membership_url );
    }

    // ============================================
    // Planning Tool (Premium Feature)
    // ============================================

    /**
     * Simulate a future trip to check if it would violate the 90-day rule
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function simulate_trip( WP_REST_Request $request ) {
        global $wpdb;

        $params = $request->get_json_params();

        // Validate required params
        if ( empty( $params['start_date'] ) || empty( $params['end_date'] ) ) {
            return new WP_Error(
                'missing_dates',
                'Start date and end date are required.',
                array( 'status' => 400 )
            );
        }

        $validation = $this->validate_trip_dates( $params['start_date'], $params['end_date'] );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        $user_id = get_current_user_id();
        $table   = FRAMT_Portal_Schema::get_table( 'schengen_trips' );

        // Get existing trips
        $existing_trips = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d", $user_id )
        );

        $proposed_start = new DateTime( $params['start_date'] );
        $proposed_end   = new DateTime( $params['end_date'] );

        // Calculate trip length
        $trip_length = $proposed_start->diff( $proposed_end )->days + 1;

        // Check each day of the proposed trip
        $violations      = array();
        $max_days_used   = 0;
        $check_date      = clone $proposed_start;

        while ( $check_date <= $proposed_end ) {
            // Calculate days used on this date including the proposed trip
            $days_on_date = $this->calculate_days_with_proposed(
                $existing_trips,
                $proposed_start,
                $check_date,
                $check_date
            );

            if ( $days_on_date > 90 ) {
                $violations[] = $check_date->format( 'Y-m-d' );
            }

            $max_days_used = max( $max_days_used, $days_on_date );
            $check_date->modify( '+1 day' );
        }

        $would_violate = count( $violations ) > 0;

        // Find earliest safe entry date if trip would violate
        $earliest_safe_date = null;
        if ( $would_violate ) {
            $earliest_safe_date = $this->find_earliest_safe_date( $existing_trips, $trip_length );
        }

        // Find maximum safe trip length from proposed start date
        $max_safe_length = $this->find_max_safe_length( $existing_trips, $proposed_start );

        return rest_ensure_response( array(
            'wouldViolate'      => $would_violate,
            'violations'        => $violations,
            'maxDaysUsed'       => $max_days_used,
            'proposedLength'    => $trip_length,
            'earliestSafeDate'  => $earliest_safe_date,
            'maxSafeLength'     => $max_safe_length,
            'daysOverLimit'     => $would_violate ? $max_days_used - 90 : 0,
        ) );
    }

    /**
     * Calculate days used including a proposed trip
     *
     * @param array    $existing_trips Existing trips from database.
     * @param DateTime $proposed_start Start of proposed trip.
     * @param DateTime $proposed_end   End of proposed trip.
     * @param DateTime $reference_date Reference date for 180-day window.
     * @return int Days used.
     */
    private function calculate_days_with_proposed(
        array $existing_trips,
        DateTime $proposed_start,
        DateTime $proposed_end,
        DateTime $reference_date
    ): int {
        $window_start = ( clone $reference_date )->modify( '-179 days' );
        $days = array();

        // Add existing trips
        foreach ( $existing_trips as $trip ) {
            $trip_start = new DateTime( $trip->start_date );
            $trip_end   = new DateTime( $trip->end_date );

            $effective_start = $trip_start < $window_start ? $window_start : $trip_start;
            $effective_end   = $trip_end > $reference_date ? $reference_date : $trip_end;

            if ( $effective_start <= $effective_end ) {
                $current = clone $effective_start;
                while ( $current <= $effective_end ) {
                    $days[ $current->format( 'Y-m-d' ) ] = true;
                    $current->modify( '+1 day' );
                }
            }
        }

        // Add proposed trip
        $effective_start = $proposed_start < $window_start ? $window_start : $proposed_start;
        $effective_end   = $proposed_end > $reference_date ? $reference_date : $proposed_end;

        if ( $effective_start <= $effective_end ) {
            $current = clone $effective_start;
            while ( $current <= $effective_end ) {
                $days[ $current->format( 'Y-m-d' ) ] = true;
                $current->modify( '+1 day' );
            }
        }

        return count( $days );
    }

    /**
     * Find earliest safe entry date for a trip of given length
     *
     * @param array $existing_trips Existing trips.
     * @param int   $trip_length    Desired trip length in days.
     * @return string|null ISO date string or null if not found within a year.
     */
    private function find_earliest_safe_date( array $existing_trips, int $trip_length ): ?string {
        $check_date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $max_search = 365;

        for ( $i = 0; $i < $max_search; $i++ ) {
            $proposed_end = ( clone $check_date )->modify( '+' . ( $trip_length - 1 ) . ' days' );

            // Check each day of the hypothetical trip
            $would_violate = false;
            $current = clone $check_date;

            while ( $current <= $proposed_end ) {
                $days_used = $this->calculate_days_with_proposed(
                    $existing_trips,
                    $check_date,
                    $current,
                    $current
                );

                if ( $days_used > 90 ) {
                    $would_violate = true;
                    break;
                }
                $current->modify( '+1 day' );
            }

            if ( ! $would_violate ) {
                return $check_date->format( 'Y-m-d' );
            }

            $check_date->modify( '+1 day' );
        }

        return null;
    }

    /**
     * Find maximum safe trip length starting from a given date
     *
     * @param array    $existing_trips Existing trips.
     * @param DateTime $start_date     Start date.
     * @return int Maximum safe trip length in days.
     */
    private function find_max_safe_length( array $existing_trips, DateTime $start_date ): int {
        $max_length = 0;

        for ( $length = 1; $length <= 90; $length++ ) {
            $proposed_end = ( clone $start_date )->modify( '+' . ( $length - 1 ) . ' days' );

            // Check each day
            $would_violate = false;
            $current = clone $start_date;

            while ( $current <= $proposed_end ) {
                $days_used = $this->calculate_days_with_proposed(
                    $existing_trips,
                    $start_date,
                    $current,
                    $current
                );

                if ( $days_used > 90 ) {
                    $would_violate = true;
                    break;
                }
                $current->modify( '+1 day' );
            }

            if ( $would_violate ) {
                break;
            }

            $max_length = $length;
        }

        return $max_length;
    }

    // ============================================
    // PDF Report Generation (Premium Feature)
    // ============================================

    /**
     * Generate PDF report of Schengen trips
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_report( WP_REST_Request $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $user    = get_userdata( $user_id );
        $table   = FRAMT_Portal_Schema::get_table( 'schengen_trips' );

        // Get all trips
        $trips = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY start_date DESC",
                $user_id
            )
        );

        // Get current summary
        $settings = $this->get_user_settings( $user_id );
        $today    = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $window_start = ( clone $today )->modify( '-179 days' );

        // Get trips in current window
        $window_trips = array_filter( $trips, function( $trip ) use ( $window_start, $today ) {
            $trip_end = new DateTime( $trip->end_date );
            return $trip_end >= $window_start;
        } );

        $days_used = $this->calculate_days_in_window( $window_trips, $window_start, $today );
        $status    = $this->get_status( $days_used, $settings['yellow_threshold'], $settings['red_threshold'] );

        // Build HTML for PDF
        $html = $this->build_report_html( array(
            'user'         => $user,
            'trips'        => $trips,
            'days_used'    => $days_used,
            'days_remaining' => max( 0, 90 - $days_used ),
            'status'       => $status,
            'window_start' => $window_start->format( 'Y-m-d' ),
            'window_end'   => $today->format( 'Y-m-d' ),
            'generated_at' => $today->format( 'Y-m-d H:i:s' ),
        ) );

        // Return HTML for now (PDF generation would require a library like TCPDF or Dompdf)
        // The frontend can use this HTML with browser print or a client-side PDF library
        return rest_ensure_response( array(
            'html'       => $html,
            'filename'   => 'schengen-report-' . $today->format( 'Y-m-d' ) . '.pdf',
            'summary'    => array(
                'daysUsed'      => $days_used,
                'daysRemaining' => max( 0, 90 - $days_used ),
                'status'        => $status,
                'tripCount'     => count( $trips ),
            ),
        ) );
    }

    /**
     * Build HTML content for PDF report
     *
     * @param array $data Report data.
     * @return string HTML content.
     */
    private function build_report_html( array $data ): string {
        $user         = $data['user'];
        $trips        = $data['trips'];
        $days_used    = $data['days_used'];
        $days_remaining = $data['days_remaining'];
        $status       = $data['status'];

        $status_colors = array(
            'safe'     => '#22c55e',
            'warning'  => '#eab308',
            'danger'   => '#f97316',
            'critical' => '#ef4444',
        );
        $status_color = $status_colors[ $status ] ?? '#6b7280';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Schengen Tracker Report</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 40px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #4A7BA7; padding-bottom: 20px; }
        .header h1 { color: #4A7BA7; margin-bottom: 5px; }
        .header .subtitle { color: #6b7280; }
        .summary { display: flex; justify-content: space-around; margin-bottom: 40px; }
        .stat-card { text-align: center; padding: 20px; background: #f3f4f6; border-radius: 8px; min-width: 150px; }
        .stat-value { font-size: 36px; font-weight: bold; color: #1f2937; }
        .stat-label { color: #6b7280; font-size: 14px; margin-top: 5px; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; color: white; font-weight: bold; }
        .trips-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .trips-table th { background: #4A7BA7; color: white; padding: 12px; text-align: left; }
        .trips-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .trips-table tr:nth-child(even) { background: #f9fafb; }
        .footer { margin-top: 40px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
        @media print { body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Schengen 90/180 Day Report</h1>
        <div class="subtitle">Prepared for ' . esc_html( $user->display_name ) . '</div>
        <div class="subtitle">Generated: ' . esc_html( $data['generated_at'] ) . '</div>
    </div>

    <div class="summary">
        <div class="stat-card">
            <div class="stat-value">' . esc_html( $days_used ) . '</div>
            <div class="stat-label">Days Used</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">' . esc_html( $days_remaining ) . '</div>
            <div class="stat-label">Days Remaining</div>
        </div>
        <div class="stat-card">
            <span class="status-badge" style="background-color: ' . esc_attr( $status_color ) . ';">' . esc_html( ucfirst( $status ) ) . '</span>
            <div class="stat-label" style="margin-top: 10px;">Current Status</div>
        </div>
    </div>

    <div class="window-info">
        <p><strong>Current 180-Day Window:</strong> ' . esc_html( $data['window_start'] ) . ' to ' . esc_html( $data['window_end'] ) . '</p>
    </div>

    <h2>Trip History</h2>';

        if ( empty( $trips ) ) {
            $html .= '<p>No trips recorded.</p>';
        } else {
            $html .= '
    <table class="trips-table">
        <thead>
            <tr>
                <th>Dates</th>
                <th>Country</th>
                <th>Duration</th>
                <th>Category</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';

            foreach ( $trips as $trip ) {
                $start    = new DateTime( $trip->start_date );
                $end      = new DateTime( $trip->end_date );
                $duration = $start->diff( $end )->days + 1;

                $html .= '
            <tr>
                <td>' . esc_html( $start->format( 'M j, Y' ) ) . ' - ' . esc_html( $end->format( 'M j, Y' ) ) . '</td>
                <td>' . esc_html( $trip->country ) . '</td>
                <td>' . esc_html( $duration ) . ' day' . ( $duration !== 1 ? 's' : '' ) . '</td>
                <td>' . esc_html( ucfirst( $trip->category ) ) . '</td>
                <td>' . esc_html( $trip->notes ?: '-' ) . '</td>
            </tr>';
            }

            $html .= '
        </tbody>
    </table>';
        }

        $html .= '
    <div class="footer">
        <p>This report was generated by relo2france.com Schengen Tracker.</p>
        <p>The 90/180 rule: Non-EU citizens may stay up to 90 days within any 180-day period in the Schengen area.</p>
    </div>
</body>
</html>';

        return $html;
    }

    // ============================================
    // Email Alert Testing
    // ============================================

    /**
     * Test email alert for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function test_alert( WP_REST_Request $request ) {
        $user_id = get_current_user_id();

        if ( ! class_exists( 'FRAMT_Schengen_Alerts' ) ) {
            return new WP_Error(
                'alerts_unavailable',
                'Schengen alerts system is not available.',
                array( 'status' => 500 )
            );
        }

        $alerts = FRAMT_Schengen_Alerts::get_instance();
        $result = $alerts->test_alert( $user_id );

        return rest_ensure_response( $result );
    }

    // ============================================
    // Location Tracking Methods (Phase 1.1)
    // ============================================

    /**
     * Schengen country codes
     *
     * @var array
     */
    private static $schengen_codes = array(
        'AT', 'BE', 'BG', 'HR', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE',
        'GR', 'HU', 'IS', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL',
        'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH',
    );

    /**
     * Get location table name
     *
     * @return string
     */
    private function get_location_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'fra_schengen_location_log';
    }

    /**
     * Ensure location table exists
     */
    private function ensure_location_table(): void {
        global $wpdb;

        $table = $this->get_location_table();
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );

        if ( $table_exists !== $table ) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table (
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

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }

    /**
     * Check if country code is in Schengen zone
     *
     * @param string $code Country code.
     * @return bool
     */
    private function is_schengen_code( string $code ): bool {
        return in_array( strtoupper( $code ), self::$schengen_codes, true );
    }

    /**
     * Reverse geocode using OpenStreetMap Nominatim
     *
     * @param float $lat Latitude.
     * @param float $lng Longitude.
     * @return array
     */
    private function perform_geocode( float $lat, float $lng ): array {
        // Check cache
        $cache_key = 'framt_geocode_' . md5( $lat . '_' . $lng );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $url = add_query_arg(
            array(
                'format' => 'json',
                'lat'    => $lat,
                'lon'    => $lng,
                'zoom'   => 10,
            ),
            'https://nominatim.openstreetmap.org/reverse'
        );

        $response = wp_remote_get( $url, array(
            'headers' => array(
                'User-Agent' => 'Relo2France-Portal/2.0 (contact@relo2france.com)',
            ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'error' => $response->get_error_message() );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data ) || isset( $data['error'] ) ) {
            return array( 'error' => $data['error'] ?? 'Geocoding failed' );
        }

        $address = $data['address'] ?? array();
        $result = array(
            'country_code' => isset( $address['country_code'] ) ? strtoupper( $address['country_code'] ) : null,
            'country_name' => $address['country'] ?? null,
            'city'         => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            'is_schengen'  => isset( $address['country_code'] ) && $this->is_schengen_code( $address['country_code'] ),
        );

        set_transient( $cache_key, $result, HOUR_IN_SECONDS );

        return $result;
    }

    /**
     * Format location for API response
     *
     * @param object $location Location row.
     * @return array
     */
    private function format_location( $location ): array {
        return array(
            'id'          => (int) $location->id,
            'lat'         => (float) $location->lat,
            'lng'         => (float) $location->lng,
            'accuracy'    => $location->accuracy ? (float) $location->accuracy : null,
            'countryCode' => $location->country_code,
            'countryName' => $location->country_name,
            'city'        => $location->city,
            'isSchengen'  => (bool) $location->is_schengen,
            'source'      => $location->source,
            'recordedAt'  => $location->recorded_at,
        );
    }

    /**
     * Check permission for location entry
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_location_permission( WP_REST_Request $request ) {
        $permission = $this->check_permission();
        if ( is_wp_error( $permission ) ) {
            return $permission;
        }

        global $wpdb;
        $location_id = (int) $request->get_param( 'id' );
        $table = $this->get_location_table();

        $location = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $location_id )
        );

        if ( ! $location ) {
            return new WP_Error( 'not_found', 'Location not found.', array( 'status' => 404 ) );
        }

        if ( (int) $location->user_id !== get_current_user_id() ) {
            return new WP_Error( 'forbidden', 'Access denied.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Store a location check-in
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function store_location( WP_REST_Request $request ) {
        global $wpdb;

        $this->ensure_location_table();

        $user_id = get_current_user_id();

        // Try JSON params first, then fall back to regular params
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        // Debug: log what we received
        error_log( 'FRAMT Location Store - Params: ' . print_r( $params, true ) );

        $lat      = isset( $params['lat'] ) ? (float) $params['lat'] : null;
        $lng      = isset( $params['lng'] ) ? (float) $params['lng'] : null;
        $accuracy = isset( $params['accuracy'] ) ? (float) $params['accuracy'] : null;
        $source   = sanitize_text_field( $params['source'] ?? 'browser' );

        // Check if coordinates are present
        if ( null === $lat || null === $lng ) {
            return new WP_Error(
                'missing_coords',
                'Missing latitude or longitude. Received: lat=' . var_export( $lat, true ) . ', lng=' . var_export( $lng, true ),
                array( 'status' => 400 )
            );
        }

        // Validate coordinates
        if ( $lat < -90 || $lat > 90 ) {
            return new WP_Error( 'invalid_lat', 'Latitude must be between -90 and 90.', array( 'status' => 400 ) );
        }
        if ( $lng < -180 || $lng > 180 ) {
            return new WP_Error( 'invalid_lng', 'Longitude must be between -180 and 180.', array( 'status' => 400 ) );
        }

        // Reverse geocode
        $geo = $this->perform_geocode( $lat, $lng );
        $country_code = $geo['country_code'] ?? null;
        $country_name = $geo['country_name'] ?? null;
        $city         = $geo['city'] ?? null;
        $is_schengen  = $geo['is_schengen'] ?? false;

        $table = $this->get_location_table();
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'      => $user_id,
                'lat'          => $lat,
                'lng'          => $lng,
                'accuracy'     => $accuracy,
                'country_code' => $country_code,
                'country_name' => $country_name,
                'city'         => $city,
                'is_schengen'  => $is_schengen ? 1 : 0,
                'source'       => $source,
                'recorded_at'  => current_time( 'mysql', true ),
            ),
            array( '%d', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false === $result ) {
            error_log( 'FRAMT Location Store - DB Error: ' . $wpdb->last_error );
            error_log( 'FRAMT Location Store - Last Query: ' . $wpdb->last_query );
            return new WP_Error(
                'db_error',
                'Failed to store location: ' . $wpdb->last_error,
                array( 'status' => 500 )
            );
        }

        $insert_id = $wpdb->insert_id;
        error_log( 'FRAMT Location Store - Success, ID: ' . $insert_id );

        $location = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $insert_id )
        );

        if ( ! $location ) {
            return new WP_Error(
                'fetch_error',
                'Location saved but could not be retrieved.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array(
            'success'  => true,
            'location' => $this->format_location( $location ),
            'message'  => $is_schengen
                ? sprintf( 'Location recorded in %s (Schengen zone).', $country_name )
                : sprintf( 'Location recorded in %s.', $country_name ?: 'Unknown location' ),
        ) );
    }

    /**
     * Get location history
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_location_history( WP_REST_Request $request ) {
        global $wpdb;

        $this->ensure_location_table();

        $user_id = get_current_user_id();
        $limit   = (int) $request->get_param( 'limit' );
        $offset  = (int) $request->get_param( 'offset' );
        $table   = $this->get_location_table();

        $total = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id )
        );

        $locations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY recorded_at DESC LIMIT %d OFFSET %d",
                $user_id, $limit, $offset
            )
        );

        return rest_ensure_response( array(
            'locations' => array_map( array( $this, 'format_location' ), $locations ),
            'total'     => $total,
            'limit'     => $limit,
            'offset'    => $offset,
        ) );
    }

    /**
     * Get today's location status
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_location_today( WP_REST_Request $request ) {
        global $wpdb;

        $this->ensure_location_table();

        $user_id = get_current_user_id();
        $table   = $this->get_location_table();
        $today   = current_time( 'Y-m-d' );

        $today_locations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND DATE(recorded_at) = %s ORDER BY recorded_at DESC",
                $user_id, $today
            )
        );

        $last_location = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY recorded_at DESC LIMIT 1",
                $user_id
            )
        );

        return rest_ensure_response( array(
            'hasCheckedInToday' => ! empty( $today_locations ),
            'todayLocations'    => array_map( array( $this, 'format_location' ), $today_locations ),
            'lastLocation'      => $last_location ? $this->format_location( $last_location ) : null,
            'reminderEnabled'   => false,
            'trackingEnabled'   => true,
        ) );
    }

    /**
     * Reverse geocode endpoint
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function reverse_geocode( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $lat = (float) $params['lat'];
        $lng = (float) $params['lng'];

        $result = $this->perform_geocode( $lat, $lng );

        if ( isset( $result['error'] ) ) {
            return new WP_Error( 'geocode_error', $result['error'], array( 'status' => 500 ) );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Delete a location entry
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_location( WP_REST_Request $request ) {
        global $wpdb;

        $location_id = (int) $request->get_param( 'id' );
        $table = $this->get_location_table();

        $result = $wpdb->delete( $table, array( 'id' => $location_id ), array( '%d' ) );

        if ( false === $result ) {
            return new WP_Error( 'db_error', 'Failed to delete location.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'deleted' => true, 'id' => $location_id ) );
    }

    /**
     * Clear all location history
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function clear_location_history( WP_REST_Request $request ) {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $this->get_location_table();

        $result = $wpdb->delete( $table, array( 'user_id' => $user_id ), array( '%d' ) );

        if ( false === $result ) {
            return new WP_Error( 'db_error', 'Failed to clear history.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'cleared' => true, 'message' => 'Location history cleared.' ) );
    }

    /**
     * Get location settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_location_settings( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $settings = get_user_meta( $user_id, 'framt_schengen_location_settings', true );

        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return rest_ensure_response( array_merge(
            array(
                'tracking_enabled' => true,
                'daily_reminder'   => false,
                'auto_detect'      => false,
            ),
            $settings
        ) );
    }

    /**
     * Update location settings
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function update_location_settings( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $settings = get_user_meta( $user_id, 'framt_schengen_location_settings', true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        if ( isset( $params['trackingEnabled'] ) ) {
            $settings['tracking_enabled'] = (bool) $params['trackingEnabled'];
        }
        if ( isset( $params['dailyReminder'] ) ) {
            $settings['daily_reminder'] = (bool) $params['dailyReminder'];
        }
        if ( isset( $params['autoDetect'] ) ) {
            $settings['auto_detect'] = (bool) $params['autoDetect'];
        }

        update_user_meta( $user_id, 'framt_schengen_location_settings', $settings );

        return rest_ensure_response( array_merge(
            array(
                'tracking_enabled' => true,
                'daily_reminder'   => false,
                'auto_detect'      => false,
            ),
            $settings
        ) );
    }

    /**
     * Detect country from IP
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function detect_from_ip( WP_REST_Request $request ) {
        // Get IP
        $ip = null;
        $headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    break;
                }
                $ip = null;
            }
        }

        // Local IP check
        if ( ! $ip || $ip === '127.0.0.1' || strpos( $ip, '192.168.' ) === 0 || strpos( $ip, '10.' ) === 0 ) {
            return rest_ensure_response( array(
                'detected'    => false,
                'reason'      => 'local_ip',
                'message'     => 'Cannot detect from local IP.',
                'countryCode' => null,
                'countryName' => null,
                'city'        => null,
                'isSchengen'  => false,
            ) );
        }

        // Check cache
        $cache_key = 'framt_ip_geo_' . md5( $ip );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return rest_ensure_response( $cached );
        }

        // Call ip-api.com
        $response = wp_remote_get(
            'http://ip-api.com/json/' . $ip . '?fields=status,message,country,countryCode,city,query',
            array( 'timeout' => 5 )
        );

        if ( is_wp_error( $response ) ) {
            return rest_ensure_response( array(
                'detected'    => false,
                'reason'      => 'api_error',
                'message'     => 'IP lookup failed.',
                'countryCode' => null,
                'countryName' => null,
                'city'        => null,
                'isSchengen'  => false,
            ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data ) || ( isset( $data['status'] ) && 'fail' === $data['status'] ) ) {
            return rest_ensure_response( array(
                'detected'    => false,
                'reason'      => 'lookup_failed',
                'message'     => $data['message'] ?? 'IP lookup failed.',
                'countryCode' => null,
                'countryName' => null,
                'city'        => null,
                'isSchengen'  => false,
            ) );
        }

        $country_code = $data['countryCode'] ?? null;

        $result = array(
            'detected'    => true,
            'reason'      => 'ip_lookup',
            'message'     => sprintf( 'Detected: %s', $data['country'] ?? 'Unknown' ),
            'countryCode' => $country_code,
            'countryName' => $data['country'] ?? null,
            'city'        => $data['city'] ?? null,
            'isSchengen'  => $country_code ? $this->is_schengen_code( $country_code ) : false,
        );

        set_transient( $cache_key, $result, HOUR_IN_SECONDS );

        return rest_ensure_response( $result );
    }
}
