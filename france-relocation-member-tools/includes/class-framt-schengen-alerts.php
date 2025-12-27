<?php
/**
 * Schengen Tracker Email Alerts
 *
 * Handles scheduled email notifications for users approaching their 90-day limit.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Schengen
 * @since       2.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FRAMT_Schengen_Alerts
 *
 * Manages email alerts for Schengen day tracking.
 *
 * @since 2.2.0
 */
class FRAMT_Schengen_Alerts {

    /**
     * Singleton instance
     *
     * @var FRAMT_Schengen_Alerts|null
     */
    private static $instance = null;

    /**
     * Cron hook name
     */
    const CRON_HOOK = 'framt_schengen_daily_alerts';

    /**
     * Alert thresholds (days used)
     */
    const ALERT_THRESHOLDS = array(
        'warning' => 60,  // Yellow - approaching limit
        'danger'  => 80,  // Orange - getting close
        'urgent'  => 85,  // Red - very close
    );

    /**
     * Get singleton instance
     *
     * @return FRAMT_Schengen_Alerts
     */
    public static function get_instance(): FRAMT_Schengen_Alerts {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register cron action
        add_action( self::CRON_HOOK, array( $this, 'process_daily_alerts' ) );

        // Schedule cron on plugin activation
        add_action( 'framt_activate', array( $this, 'schedule_cron' ) );

        // Clear cron on plugin deactivation
        add_action( 'framt_deactivate', array( $this, 'unschedule_cron' ) );

        // Ensure cron is scheduled
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            $this->schedule_cron();
        }
    }

    /**
     * Schedule the daily cron job
     *
     * @return void
     */
    public function schedule_cron(): void {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            // Schedule for 8am UTC daily
            $next_run = strtotime( 'tomorrow 08:00:00 UTC' );
            wp_schedule_event( $next_run, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Unschedule the cron job
     *
     * @return void
     */
    public function unschedule_cron(): void {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }

    /**
     * Process daily alerts for all users
     *
     * @return void
     */
    public function process_daily_alerts(): void {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'schengen_trips' );

        // Get all users with Schengen trips
        $user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM $table" );

        if ( empty( $user_ids ) ) {
            return;
        }

        foreach ( $user_ids as $user_id ) {
            $this->check_and_alert_user( (int) $user_id );
        }
    }

    /**
     * Check a single user's status and send alert if needed
     *
     * @param int $user_id User ID.
     * @return bool Whether an alert was sent.
     */
    public function check_and_alert_user( int $user_id ): bool {
        // Check if user has email alerts enabled
        $settings = $this->get_user_settings( $user_id );
        if ( empty( $settings['email_alerts'] ) ) {
            return false;
        }

        // Get user's current status
        $summary = $this->get_user_summary( $user_id );
        if ( ! $summary ) {
            return false;
        }

        $days_used = $summary['days_used'];

        // Determine alert level
        $alert_level = $this->get_alert_level( $days_used );
        if ( ! $alert_level ) {
            return false;
        }

        // Check if we already sent an alert at this level recently
        $last_alert = get_user_meta( $user_id, 'framt_schengen_last_alert_level', true );
        $last_alert_time = get_user_meta( $user_id, 'framt_schengen_last_alert_time', true );

        // Don't send duplicate alerts within 7 days for same level
        if ( $last_alert === $alert_level && $last_alert_time ) {
            $days_since = ( time() - (int) $last_alert_time ) / DAY_IN_SECONDS;
            if ( $days_since < 7 ) {
                return false;
            }
        }

        // Send the alert
        $sent = $this->send_alert_email( $user_id, $alert_level, $summary );

        if ( $sent ) {
            // Record that we sent this alert
            update_user_meta( $user_id, 'framt_schengen_last_alert_level', $alert_level );
            update_user_meta( $user_id, 'framt_schengen_last_alert_time', time() );
        }

        return $sent;
    }

    /**
     * Get user's Schengen settings
     *
     * @param int $user_id User ID.
     * @return array Settings.
     */
    private function get_user_settings( int $user_id ): array {
        $settings = get_user_meta( $user_id, 'framt_schengen_settings', true );

        return wp_parse_args( $settings, array(
            'yellow_threshold' => 60,
            'red_threshold'    => 80,
            'email_alerts'     => false,
        ) );
    }

    /**
     * Get user's current Schengen summary
     *
     * @param int $user_id User ID.
     * @return array|null Summary data or null if no trips.
     */
    private function get_user_summary( int $user_id ): ?array {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'schengen_trips' );
        $today = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
        $window_start = ( clone $today )->modify( '-179 days' );

        // Get trips in current window
        $trips = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND end_date >= %s",
            $user_id,
            $window_start->format( 'Y-m-d' )
        ) );

        if ( empty( $trips ) ) {
            return null;
        }

        // Calculate days used
        $days = array();
        foreach ( $trips as $trip ) {
            $trip_start = new DateTime( $trip->start_date );
            $trip_end   = new DateTime( $trip->end_date );

            $effective_start = $trip_start < $window_start ? $window_start : $trip_start;
            $effective_end   = $trip_end > $today ? $today : $trip_end;

            if ( $effective_start <= $effective_end ) {
                $current = clone $effective_start;
                while ( $current <= $effective_end ) {
                    $days[ $current->format( 'Y-m-d' ) ] = true;
                    $current->modify( '+1 day' );
                }
            }
        }

        $days_used = count( $days );
        $days_remaining = max( 0, 90 - $days_used );

        // Find next expiration (earliest day that will drop off)
        $next_expiration = null;
        if ( ! empty( $days ) ) {
            $sorted_days = array_keys( $days );
            sort( $sorted_days );
            $earliest = new DateTime( $sorted_days[0] );
            $next_expiration = ( clone $earliest )->modify( '+180 days' )->format( 'Y-m-d' );
        }

        return array(
            'days_used'       => $days_used,
            'days_remaining'  => $days_remaining,
            'window_start'    => $window_start->format( 'Y-m-d' ),
            'window_end'      => $today->format( 'Y-m-d' ),
            'next_expiration' => $next_expiration,
        );
    }

    /**
     * Determine alert level based on days used
     *
     * @param int $days_used Days used in current window.
     * @return string|null Alert level or null if no alert needed.
     */
    private function get_alert_level( int $days_used ): ?string {
        if ( $days_used >= self::ALERT_THRESHOLDS['urgent'] ) {
            return 'urgent';
        }
        if ( $days_used >= self::ALERT_THRESHOLDS['danger'] ) {
            return 'danger';
        }
        if ( $days_used >= self::ALERT_THRESHOLDS['warning'] ) {
            return 'warning';
        }
        return null;
    }

    /**
     * Send alert email to user
     *
     * @param int    $user_id     User ID.
     * @param string $alert_level Alert level.
     * @param array  $summary     Summary data.
     * @return bool Whether email was sent.
     */
    private function send_alert_email( int $user_id, string $alert_level, array $summary ): bool {
        $user = get_userdata( $user_id );
        if ( ! $user || ! $user->user_email ) {
            return false;
        }

        $subject = $this->get_email_subject( $alert_level, $summary );
        $body    = $this->get_email_body( $user, $alert_level, $summary );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        return wp_mail( $user->user_email, $subject, $body, $headers );
    }

    /**
     * Get email subject based on alert level
     *
     * @param string $alert_level Alert level.
     * @param array  $summary     Summary data.
     * @return string Subject line.
     */
    private function get_email_subject( string $alert_level, array $summary ): string {
        $days_remaining = $summary['days_remaining'];

        switch ( $alert_level ) {
            case 'urgent':
                return sprintf( 'URGENT: Only %d Schengen days remaining!', $days_remaining );
            case 'danger':
                return sprintf( 'Warning: %d Schengen days remaining', $days_remaining );
            case 'warning':
            default:
                return sprintf( 'Schengen Tracker: %d days remaining', $days_remaining );
        }
    }

    /**
     * Get email body HTML
     *
     * @param WP_User $user        User object.
     * @param string  $alert_level Alert level.
     * @param array   $summary     Summary data.
     * @return string HTML email body.
     */
    private function get_email_body( WP_User $user, string $alert_level, array $summary ): string {
        $colors = array(
            'warning' => array( 'bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e' ),
            'danger'  => array( 'bg' => '#fed7aa', 'border' => '#f97316', 'text' => '#9a3412' ),
            'urgent'  => array( 'bg' => '#fee2e2', 'border' => '#ef4444', 'text' => '#991b1b' ),
        );

        $color = $colors[ $alert_level ];
        $portal_url = home_url( '/portal/?view=schengen' );

        $heading = $this->get_alert_heading( $alert_level );
        $message = $this->get_alert_message( $alert_level, $summary );

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #1f2937;">
    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #4A7BA7;">
        <h1 style="color: #4A7BA7; margin: 0;">Schengen Tracker</h1>
        <p style="color: #6b7280; margin: 5px 0 0;">relo2france.com</p>
    </div>

    <p style="font-size: 16px;">Hi ' . esc_html( $user->display_name ) . ',</p>

    <div style="background-color: ' . esc_attr( $color['bg'] ) . '; border-left: 4px solid ' . esc_attr( $color['border'] ) . '; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2 style="color: ' . esc_attr( $color['text'] ) . '; margin: 0 0 10px;">' . esc_html( $heading ) . '</h2>
        <p style="color: ' . esc_attr( $color['text'] ) . '; margin: 0; font-size: 15px;">' . esc_html( $message ) . '</p>
    </div>

    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_used'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">Days Used</div>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_remaining'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">Days Remaining</div>
                </td>
            </tr>
        </table>
    </div>

    <p style="font-size: 14px; color: #6b7280;">
        Current 180-day window: ' . esc_html( $summary['window_start'] ) . ' to ' . esc_html( $summary['window_end'] ) . '
    </p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="' . esc_url( $portal_url ) . '" style="display: inline-block; background-color: #4A7BA7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">View Your Schengen Tracker</a>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="font-size: 12px; color: #9ca3af; text-align: center;">
        You are receiving this email because you enabled Schengen alerts in your portal settings.<br>
        <a href="' . esc_url( home_url( '/portal/?view=settings' ) ) . '" style="color: #6b7280;">Manage notification preferences</a>
    </p>
</body>
</html>';
    }

    /**
     * Get heading for alert level
     *
     * @param string $alert_level Alert level.
     * @return string Heading text.
     */
    private function get_alert_heading( string $alert_level ): string {
        switch ( $alert_level ) {
            case 'urgent':
                return 'Urgent: Schengen Limit Almost Reached';
            case 'danger':
                return 'Warning: Approaching Schengen Limit';
            case 'warning':
            default:
                return 'Schengen Days Status Update';
        }
    }

    /**
     * Get message for alert level
     *
     * @param string $alert_level Alert level.
     * @param array  $summary     Summary data.
     * @return string Message text.
     */
    private function get_alert_message( string $alert_level, array $summary ): string {
        $days_remaining = $summary['days_remaining'];

        switch ( $alert_level ) {
            case 'urgent':
                return sprintf(
                    'You have only %d days remaining in your current 180-day Schengen window. Any additional travel may result in overstaying your allowed time.',
                    $days_remaining
                );
            case 'danger':
                return sprintf(
                    'You have used %d of your 90 allowed days. Please plan any future Schengen travel carefully to avoid exceeding the limit.',
                    $summary['days_used']
                );
            case 'warning':
            default:
                return sprintf(
                    'You have used %d of your 90 allowed days in the current 180-day window. This is a friendly reminder to help you plan your travel.',
                    $summary['days_used']
                );
        }
    }

    /**
     * Manually trigger alert check for a user (for testing)
     *
     * @param int $user_id User ID.
     * @return array Result with status and details.
     */
    public function test_alert( int $user_id ): array {
        $settings = $this->get_user_settings( $user_id );
        $summary  = $this->get_user_summary( $user_id );

        if ( ! $summary ) {
            return array(
                'success' => false,
                'message' => 'No trips found for user.',
            );
        }

        $alert_level = $this->get_alert_level( $summary['days_used'] );

        if ( ! $alert_level ) {
            return array(
                'success'     => false,
                'message'     => 'Days used (' . $summary['days_used'] . ') does not meet alert threshold.',
                'days_used'   => $summary['days_used'],
                'thresholds'  => self::ALERT_THRESHOLDS,
            );
        }

        if ( empty( $settings['email_alerts'] ) ) {
            return array(
                'success'     => false,
                'message'     => 'Email alerts are disabled for this user.',
                'alert_level' => $alert_level,
                'days_used'   => $summary['days_used'],
            );
        }

        // Actually send the test alert
        $sent = $this->send_alert_email( $user_id, $alert_level, $summary );

        return array(
            'success'     => $sent,
            'message'     => $sent ? 'Test alert sent successfully.' : 'Failed to send email.',
            'alert_level' => $alert_level,
            'summary'     => $summary,
        );
    }
}
