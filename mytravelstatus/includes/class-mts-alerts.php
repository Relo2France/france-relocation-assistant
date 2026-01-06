<?php
/**
 * MyTravelStatus Email Alerts
 *
 * Handles scheduled email notifications for users approaching their limits.
 * Supports multi-jurisdiction alerts for visa, tax, and residency rules.
 *
 * @package MTS_Tracker
 * @since   1.0.0
 * @updated 1.8.2 - Added multi-jurisdiction support
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MTS_Alerts
 *
 * Manages email alerts for Schengen day tracking.
 *
 * @since 1.0.0
 */
class MTS_Alerts {

	/**
	 * Singleton instance
	 *
	 * @var MTS_Alerts|null
	 */
	private static $instance = null;

	/**
	 * Cron hook name
	 *
	 * @var string
	 */
	const CRON_HOOK = 'mts_daily_alerts';

	/**
	 * Alert thresholds (days used)
	 *
	 * @var array
	 */
	const ALERT_THRESHOLDS = array(
		'warning' => 60,  // Yellow - approaching limit.
		'danger'  => 80,  // Orange - getting close.
		'urgent'  => 85,  // Red - very close.
	);

	/**
	 * Get singleton instance
	 *
	 * @return MTS_Alerts
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Register cron action.
		add_action( self::CRON_HOOK, array( $this, 'process_daily_alerts' ) );

		// Ensure cron is scheduled.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$this->schedule_cron();
		}
	}

	/**
	 * Schedule the daily cron job
	 *
	 * @return void
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Schedule for 8am UTC daily.
			$next_run = strtotime( 'tomorrow 08:00:00 UTC' );
			wp_schedule_event( $next_run, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cron job
	 *
	 * @return void
	 */
	public function unschedule_cron() {
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
	public function process_daily_alerts() {
		global $wpdb;

		$table = MTS_Schema::get_table( 'trips' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM $table" );

		if ( empty( $user_ids ) ) {
			return;
		}

		foreach ( $user_ids as $user_id ) {
			$this->check_and_alert_user( (int) $user_id );
		}

		// Also process multi-jurisdiction alerts.
		$this->process_multi_jurisdiction_alerts();

		/**
		 * Fires after daily alerts have been processed.
		 *
		 * @param array $user_ids List of user IDs that were checked.
		 */
		do_action( 'mts_alerts_processed', $user_ids );
	}

	/**
	 * Check a single user's status and send alert if needed
	 *
	 * @param int $user_id User ID.
	 * @return bool Whether an alert was sent.
	 */
	public function check_and_alert_user( $user_id ) {
		// Check if user has email alerts enabled.
		$settings = $this->get_user_settings( $user_id );
		if ( empty( $settings['email_alerts'] ) ) {
			return false;
		}

		// Get user's current status.
		$summary = $this->get_user_summary( $user_id );
		if ( ! $summary ) {
			return false;
		}

		$days_used = $summary['days_used'];

		// Determine alert level.
		$alert_level = $this->get_alert_level( $days_used );
		if ( ! $alert_level ) {
			return false;
		}

		// Check if we already sent an alert at this level recently.
		$last_alert = get_user_meta( $user_id, 'mts_last_alert_level', true );
		$last_alert_time = get_user_meta( $user_id, 'mts_last_alert_time', true );

		// Don't send duplicate alerts within 7 days for same level.
		if ( $last_alert === $alert_level && $last_alert_time ) {
			$days_since = ( time() - (int) $last_alert_time ) / DAY_IN_SECONDS;
			if ( $days_since < 7 ) {
				return false;
			}
		}

		// Send the alert.
		$sent = $this->send_alert_email( $user_id, $alert_level, $summary );

		if ( $sent ) {
			// Record that we sent this alert.
			update_user_meta( $user_id, 'mts_last_alert_level', $alert_level );
			update_user_meta( $user_id, 'mts_last_alert_time', time() );

			/**
			 * Fires after an alert email has been sent.
			 *
			 * @param int    $user_id     User ID.
			 * @param string $alert_level Alert level (warning, danger, urgent).
			 * @param array  $summary     Schengen summary data.
			 */
			do_action( 'mts_alert_sent', $user_id, $alert_level, $summary );
		}

		return $sent;
	}

	/**
	 * Get user's Schengen settings
	 *
	 * @param int $user_id User ID.
	 * @return array Settings.
	 */
	public function get_user_settings( $user_id ) {
		$settings = get_user_meta( $user_id, 'mts_settings', true );

		$defaults = array(
			'yellow_threshold' => 60,
			'red_threshold'    => 80,
			'email_alerts'     => false,
		);

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Update user's Schengen settings
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Settings to update.
	 * @return bool True on success.
	 */
	public function update_user_settings( $user_id, $settings ) {
		$current = $this->get_user_settings( $user_id );
		$updated = wp_parse_args( $settings, $current );

		return update_user_meta( $user_id, 'mts_settings', $updated );
	}

	/**
	 * Get user's current Schengen summary
	 *
	 * @param int $user_id User ID.
	 * @return array|null Summary data or null if no trips.
	 */
	public function get_user_summary( $user_id ) {
		global $wpdb;

		$table = MTS_Schema::get_table( 'trips' );
		$today = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_start = ( clone $today )->modify( '-179 days' );

		// Get trips in current window.
		$trips = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM $table WHERE user_id = %d AND end_date >= %s",
			$user_id,
			$window_start->format( 'Y-m-d' )
		) );

		if ( empty( $trips ) ) {
			return null;
		}

		// Calculate days used.
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

		// Find next expiration (earliest day that will drop off).
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
	private function get_alert_level( $days_used ) {
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
	private function send_alert_email( $user_id, $alert_level, $summary ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->user_email ) {
			return false;
		}

		$subject = $this->get_email_subject( $alert_level, $summary );
		$body    = $this->get_email_body( $user, $alert_level, $summary );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		/**
		 * Filter the email headers for Schengen alerts.
		 *
		 * @param array  $headers     Email headers.
		 * @param int    $user_id     User ID.
		 * @param string $alert_level Alert level.
		 */
		$headers = apply_filters( 'mts_alert_email_headers', $headers, $user_id, $alert_level );

		return wp_mail( $user->user_email, $subject, $body, $headers );
	}

	/**
	 * Get email subject based on alert level
	 *
	 * @param string $alert_level Alert level.
	 * @param array  $summary     Summary data.
	 * @return string Subject line.
	 */
	private function get_email_subject( $alert_level, $summary ) {
		$days_remaining = $summary['days_remaining'];

		switch ( $alert_level ) {
			case 'urgent':
				$subject = sprintf(
					/* translators: %d: number of days remaining */
					__( 'URGENT: Only %d Schengen days remaining!', 'mytravelstatus' ),
					$days_remaining
				);
				break;
			case 'danger':
				$subject = sprintf(
					/* translators: %d: number of days remaining */
					__( 'Warning: %d Schengen days remaining', 'mytravelstatus' ),
					$days_remaining
				);
				break;
			case 'warning':
			default:
				$subject = sprintf(
					/* translators: %d: number of days remaining */
					__( 'MyTravelStatus: %d days remaining', 'mytravelstatus' ),
					$days_remaining
				);
				break;
		}

		return $subject;
	}

	/**
	 * Get the tracker URL for email links.
	 *
	 * @return string Tracker URL.
	 */
	private function get_tracker_url() {
		/**
		 * Filter the MyTravelStatus URL used in emails.
		 *
		 * @param string $url Default tracker URL.
		 */
		return apply_filters( 'mts_tracker_url', home_url( '/schengen-tracker/' ) );
	}

	/**
	 * Get the settings URL for email links.
	 *
	 * @return string Settings URL.
	 */
	private function get_settings_url() {
		/**
		 * Filter the settings URL used in emails.
		 *
		 * @param string $url Default settings URL.
		 */
		return apply_filters( 'mts_settings_url', home_url( '/schengen-tracker/' ) );
	}

	/**
	 * Get email body HTML
	 *
	 * @param WP_User $user        User object.
	 * @param string  $alert_level Alert level.
	 * @param array   $summary     Summary data.
	 * @return string HTML email body.
	 */
	private function get_email_body( $user, $alert_level, $summary ) {
		$colors = array(
			'warning' => array(
				'bg'     => '#fef3c7',
				'border' => '#f59e0b',
				'text'   => '#92400e',
			),
			'danger'  => array(
				'bg'     => '#fed7aa',
				'border' => '#f97316',
				'text'   => '#9a3412',
			),
			'urgent'  => array(
				'bg'     => '#fee2e2',
				'border' => '#ef4444',
				'text'   => '#991b1b',
			),
		);

		$color = $colors[ $alert_level ];
		$tracker_url = $this->get_tracker_url();
		$settings_url = $this->get_settings_url();

		$heading = $this->get_alert_heading( $alert_level );
		$message = $this->get_alert_message( $alert_level, $summary );

		/**
		 * Filter the brand color used in email templates.
		 *
		 * @param string $color Hex color code.
		 */
		$brand_color = apply_filters( 'mts_brand_color', '#4A7BA7' );

		/**
		 * Filter the site name used in email templates.
		 *
		 * @param string $name Site name.
		 */
		$site_name = apply_filters( 'mts_site_name', 'relo2france.com' );

		$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #1f2937;">
    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid ' . esc_attr( $brand_color ) . ';">
        <h1 style="color: ' . esc_attr( $brand_color ) . '; margin: 0;">' . esc_html__( 'MyTravelStatus', 'mytravelstatus' ) . '</h1>
        <p style="color: #6b7280; margin: 5px 0 0;">' . esc_html( $site_name ) . '</p>
    </div>

    <p style="font-size: 16px;">' . sprintf(
			/* translators: %s: user display name */
			esc_html__( 'Hi %s,', 'mytravelstatus' ),
			esc_html( $user->display_name )
		) . '</p>

    <div style="background-color: ' . esc_attr( $color['bg'] ) . '; border-left: 4px solid ' . esc_attr( $color['border'] ) . '; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2 style="color: ' . esc_attr( $color['text'] ) . '; margin: 0 0 10px;">' . esc_html( $heading ) . '</h2>
        <p style="color: ' . esc_attr( $color['text'] ) . '; margin: 0; font-size: 15px;">' . esc_html( $message ) . '</p>
    </div>

    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_used'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">' . esc_html__( 'Days Used', 'mytravelstatus' ) . '</div>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_remaining'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">' . esc_html__( 'Days Remaining', 'mytravelstatus' ) . '</div>
                </td>
            </tr>
        </table>
    </div>

    <p style="font-size: 14px; color: #6b7280;">' . sprintf(
			/* translators: 1: window start date, 2: window end date */
			esc_html__( 'Current 180-day window: %1$s to %2$s', 'mytravelstatus' ),
			esc_html( $summary['window_start'] ),
			esc_html( $summary['window_end'] )
		) . '</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="' . esc_url( $tracker_url ) . '" style="display: inline-block; background-color: ' . esc_attr( $brand_color ) . '; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">' . esc_html__( 'View Your MyTravelStatus', 'mytravelstatus' ) . '</a>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="font-size: 12px; color: #9ca3af; text-align: center;">' .
		esc_html__( 'You are receiving this email because you enabled Schengen alerts in your settings.', 'mytravelstatus' ) . '<br>
        <a href="' . esc_url( $settings_url ) . '" style="color: #6b7280;">' . esc_html__( 'Manage notification preferences', 'mytravelstatus' ) . '</a>
    </p>
</body>
</html>';

		/**
		 * Filter the email body HTML.
		 *
		 * @param string  $html        Email HTML.
		 * @param WP_User $user        User object.
		 * @param string  $alert_level Alert level.
		 * @param array   $summary     Summary data.
		 */
		return apply_filters( 'mts_alert_email_body', $html, $user, $alert_level, $summary );
	}

	/**
	 * Get heading for alert level
	 *
	 * @param string $alert_level Alert level.
	 * @return string Heading text.
	 */
	private function get_alert_heading( $alert_level ) {
		switch ( $alert_level ) {
			case 'urgent':
				return __( 'Urgent: Schengen Limit Almost Reached', 'mytravelstatus' );
			case 'danger':
				return __( 'Warning: Approaching Schengen Limit', 'mytravelstatus' );
			case 'warning':
			default:
				return __( 'Schengen Days Status Update', 'mytravelstatus' );
		}
	}

	/**
	 * Get message for alert level
	 *
	 * @param string $alert_level Alert level.
	 * @param array  $summary     Summary data.
	 * @return string Message text.
	 */
	private function get_alert_message( $alert_level, $summary ) {
		$days_remaining = $summary['days_remaining'];

		switch ( $alert_level ) {
			case 'urgent':
				return sprintf(
					/* translators: %d: number of days remaining */
					__( 'You have only %d days remaining in your current 180-day Schengen window. Any additional travel may result in overstaying your allowed time.', 'mytravelstatus' ),
					$days_remaining
				);
			case 'danger':
				return sprintf(
					/* translators: %d: number of days used */
					__( 'You have used %d of your 90 allowed days. Please plan any future Schengen travel carefully to avoid exceeding the limit.', 'mytravelstatus' ),
					$summary['days_used']
				);
			case 'warning':
			default:
				return sprintf(
					/* translators: %d: number of days used */
					__( 'You have used %d of your 90 allowed days in the current 180-day window. This is a friendly reminder to help you plan your travel.', 'mytravelstatus' ),
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
	public function test_alert( $user_id ) {
		$settings = $this->get_user_settings( $user_id );
		$summary  = $this->get_user_summary( $user_id );

		if ( ! $summary ) {
			return array(
				'success' => false,
				'message' => __( 'No trips found for user.', 'mytravelstatus' ),
			);
		}

		$alert_level = $this->get_alert_level( $summary['days_used'] );

		if ( ! $alert_level ) {
			return array(
				'success'    => false,
				'message'    => sprintf(
					/* translators: %d: number of days used */
					__( 'Days used (%d) does not meet alert threshold.', 'mytravelstatus' ),
					$summary['days_used']
				),
				'days_used'  => $summary['days_used'],
				'thresholds' => self::ALERT_THRESHOLDS,
			);
		}

		if ( empty( $settings['email_alerts'] ) ) {
			return array(
				'success'     => false,
				'message'     => __( 'Email alerts are disabled for this user.', 'mytravelstatus' ),
				'alert_level' => $alert_level,
				'days_used'   => $summary['days_used'],
			);
		}

		// Actually send the test alert.
		$sent = $this->send_alert_email( $user_id, $alert_level, $summary );

		return array(
			'success'     => $sent,
			'message'     => $sent
				? __( 'Test alert sent successfully.', 'mytravelstatus' )
				: __( 'Failed to send email.', 'mytravelstatus' ),
			'alert_level' => $alert_level,
			'summary'     => $summary,
		);
	}

	// =========================================================================
	// Multi-Jurisdiction Alert Support (v1.8.2)
	// =========================================================================

	/**
	 * Check and alert user for all tracked jurisdictions
	 *
	 * @since 1.8.2
	 * @param int $user_id User ID.
	 * @return array List of jurisdictions that triggered alerts.
	 */
	public function check_multi_jurisdiction_alerts( $user_id ) {
		$settings = $this->get_user_settings( $user_id );
		if ( empty( $settings['email_alerts'] ) ) {
			return array();
		}

		$jurisdiction = MTS_Jurisdiction::get_instance();
		$overview     = $jurisdiction->get_compliance_overview( $user_id );

		if ( empty( $overview['summaries'] ) ) {
			return array();
		}

		$alerts_sent = array();

		foreach ( $overview['summaries'] as $summary ) {
			$alert_level = $this->get_jurisdiction_alert_level( $summary );

			if ( ! $alert_level ) {
				continue;
			}

			// Check if we already sent an alert for this jurisdiction recently.
			$alert_key  = 'mts_alert_' . $summary['jurisdiction_code'];
			$last_alert = get_user_meta( $user_id, $alert_key . '_level', true );
			$last_time  = get_user_meta( $user_id, $alert_key . '_time', true );

			// Don't send duplicate alerts within 7 days for same level.
			if ( $last_alert === $alert_level && $last_time ) {
				$days_since = ( time() - (int) $last_time ) / DAY_IN_SECONDS;
				if ( $days_since < 7 ) {
					continue;
				}
			}

			// Send the alert.
			$sent = $this->send_jurisdiction_alert_email( $user_id, $summary, $alert_level );

			if ( $sent ) {
				update_user_meta( $user_id, $alert_key . '_level', $alert_level );
				update_user_meta( $user_id, $alert_key . '_time', time() );

				$alerts_sent[] = array(
					'jurisdiction' => $summary['jurisdiction_code'],
					'alert_level'  => $alert_level,
					'days_used'    => $summary['days_used'],
					'days_allowed' => $summary['days_allowed'],
				);

				/**
				 * Fires after a jurisdiction alert email has been sent.
				 *
				 * @since 1.8.2
				 * @param int    $user_id     User ID.
				 * @param array  $summary     Jurisdiction summary.
				 * @param string $alert_level Alert level.
				 */
				do_action( 'mts_jurisdiction_alert_sent', $user_id, $summary, $alert_level );
			}
		}

		return $alerts_sent;
	}

	/**
	 * Get alert level for a jurisdiction summary
	 *
	 * @since 1.8.2
	 * @param array $summary Jurisdiction summary.
	 * @return string|null Alert level or null.
	 */
	private function get_jurisdiction_alert_level( $summary ) {
		$percentage = $summary['percentage'] ?? 0;

		if ( $percentage >= 95 || 'exceeded' === $summary['status'] ) {
			return 'urgent';
		}
		if ( $percentage >= 85 || 'critical' === $summary['status'] ) {
			return 'danger';
		}
		if ( $percentage >= 70 || 'warning' === $summary['status'] ) {
			return 'warning';
		}

		return null;
	}

	/**
	 * Send jurisdiction-specific alert email
	 *
	 * @since 1.8.2
	 * @param int    $user_id     User ID.
	 * @param array  $summary     Jurisdiction summary.
	 * @param string $alert_level Alert level.
	 * @return bool Whether email was sent.
	 */
	private function send_jurisdiction_alert_email( $user_id, $summary, $alert_level ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->user_email ) {
			return false;
		}

		$subject = $this->get_jurisdiction_email_subject( $summary, $alert_level );
		$body    = $this->get_jurisdiction_email_body( $user, $summary, $alert_level );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		/**
		 * Filter the email headers for jurisdiction alerts.
		 *
		 * @since 1.8.2
		 * @param array  $headers     Email headers.
		 * @param int    $user_id     User ID.
		 * @param array  $summary     Jurisdiction summary.
		 * @param string $alert_level Alert level.
		 */
		$headers = apply_filters( 'mts_jurisdiction_alert_email_headers', $headers, $user_id, $summary, $alert_level );

		return wp_mail( $user->user_email, $subject, $body, $headers );
	}

	/**
	 * Get jurisdiction email subject
	 *
	 * @since 1.8.2
	 * @param array  $summary     Jurisdiction summary.
	 * @param string $alert_level Alert level.
	 * @return string Subject line.
	 */
	private function get_jurisdiction_email_subject( $summary, $alert_level ) {
		$name = $summary['jurisdiction_name'];
		$remaining = $summary['days_remaining'];

		switch ( $alert_level ) {
			case 'urgent':
				return sprintf(
					/* translators: 1: jurisdiction name, 2: days remaining */
					__( 'URGENT: Only %2$d days remaining for %1$s!', 'mytravelstatus' ),
					$name,
					$remaining
				);
			case 'danger':
				return sprintf(
					/* translators: 1: jurisdiction name, 2: days remaining */
					__( 'Warning: %2$d days remaining for %1$s', 'mytravelstatus' ),
					$name,
					$remaining
				);
			case 'warning':
			default:
				return sprintf(
					/* translators: 1: jurisdiction name */
					__( 'Travel Status Update: %1$s', 'mytravelstatus' ),
					$name
				);
		}
	}

	/**
	 * Get jurisdiction email body HTML
	 *
	 * @since 1.8.2
	 * @param WP_User $user        User object.
	 * @param array   $summary     Jurisdiction summary.
	 * @param string  $alert_level Alert level.
	 * @return string HTML email body.
	 */
	private function get_jurisdiction_email_body( $user, $summary, $alert_level ) {
		$colors = array(
			'warning' => array(
				'bg'     => '#fef3c7',
				'border' => '#f59e0b',
				'text'   => '#92400e',
			),
			'danger'  => array(
				'bg'     => '#fed7aa',
				'border' => '#f97316',
				'text'   => '#9a3412',
			),
			'urgent'  => array(
				'bg'     => '#fee2e2',
				'border' => '#ef4444',
				'text'   => '#991b1b',
			),
		);

		$color       = $colors[ $alert_level ];
		$tracker_url = $this->get_tracker_url();
		$brand_color = apply_filters( 'mts_brand_color', '#4A7BA7' );
		$site_name   = apply_filters( 'mts_site_name', 'MyTravelStatus' );

		$flag = ! empty( $summary['flag_emoji'] ) ? $summary['flag_emoji'] . ' ' : '';
		$name = $flag . $summary['jurisdiction_name'];

		$heading = $this->get_jurisdiction_alert_heading( $summary, $alert_level );
		$message = $this->get_jurisdiction_alert_message( $summary, $alert_level );

		$category_label = '';
		if ( ! empty( $summary['category'] ) ) {
			$categories = array(
				'visa'      => __( 'Visa Rule', 'mytravelstatus' ),
				'tax'       => __( 'Tax Residency', 'mytravelstatus' ),
				'residency' => __( 'Immigration Rule', 'mytravelstatus' ),
			);
			$category_label = $categories[ $summary['category'] ] ?? ucfirst( $summary['category'] );
		}

		$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #1f2937;">
    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid ' . esc_attr( $brand_color ) . ';">
        <h1 style="color: ' . esc_attr( $brand_color ) . '; margin: 0;">' . esc_html__( 'MyTravelStatus', 'mytravelstatus' ) . '</h1>
        <p style="color: #6b7280; margin: 5px 0 0;">' . esc_html( $site_name ) . '</p>
    </div>

    <p style="font-size: 16px;">' . sprintf(
			/* translators: %s: user display name */
			esc_html__( 'Hi %s,', 'mytravelstatus' ),
			esc_html( $user->display_name )
		) . '</p>

    <div style="background-color: ' . esc_attr( $color['bg'] ) . '; border-left: 4px solid ' . esc_attr( $color['border'] ) . '; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2 style="color: ' . esc_attr( $color['text'] ) . '; margin: 0 0 10px;">' . esc_html( $heading ) . '</h2>
        <p style="color: ' . esc_attr( $color['text'] ) . '; margin: 0; font-size: 15px;">' . esc_html( $message ) . '</p>
    </div>

    <div style="background: #f9fafb; border: 2px solid ' . esc_attr( $color['border'] ) . '; border-radius: 12px; padding: 20px; margin: 20px 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px;">' . esc_html( $name ) . '</h3>
            <span style="font-size: 12px; color: #6b7280; background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">' . esc_html( $category_label ) . '</span>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_used'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">' . esc_html__( 'Days Used', 'mytravelstatus' ) . '</div>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_remaining'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">' . esc_html__( 'Days Remaining', 'mytravelstatus' ) . '</div>
                </td>
                <td style="padding: 10px; text-align: center;">
                    <div style="font-size: 32px; font-weight: bold; color: #1f2937;">' . esc_html( $summary['days_allowed'] ) . '</div>
                    <div style="color: #6b7280; font-size: 14px;">' . esc_html__( 'Max Days', 'mytravelstatus' ) . '</div>
                </td>
            </tr>
        </table>
        <div style="background: #e5e7eb; border-radius: 4px; height: 8px; margin-top: 15px;">
            <div style="background: ' . esc_attr( $color['border'] ) . '; border-radius: 4px; height: 100%; width: ' . esc_attr( min( 100, $summary['percentage'] ) ) . '%;"></div>
        </div>
    </div>

    <p style="font-size: 14px; color: #6b7280;">' . sprintf(
			/* translators: 1: window start date, 2: window end date */
			esc_html__( 'Tracking period: %1$s to %2$s', 'mytravelstatus' ),
			esc_html( $summary['window_start'] ),
			esc_html( $summary['window_end'] )
		) . '</p>

    <p style="font-size: 14px; color: #6b7280;">' . sprintf(
			/* translators: %s: counting method */
			esc_html__( 'Counting method: %s', 'mytravelstatus' ),
			esc_html( ucwords( str_replace( '_', ' ', $summary['counting_method'] ) ) )
		) . '</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="' . esc_url( $tracker_url ) . '" style="display: inline-block; background-color: ' . esc_attr( $brand_color ) . '; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">' . esc_html__( 'View All Jurisdictions', 'mytravelstatus' ) . '</a>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

    <p style="font-size: 12px; color: #9ca3af; text-align: center;">' .
		esc_html__( 'You are receiving this email because you enabled travel alerts in your settings.', 'mytravelstatus' ) . '<br>
        <a href="' . esc_url( $this->get_settings_url() ) . '" style="color: #6b7280;">' . esc_html__( 'Manage notification preferences', 'mytravelstatus' ) . '</a>
    </p>
</body>
</html>';

		/**
		 * Filter the jurisdiction email body HTML.
		 *
		 * @since 1.8.2
		 * @param string  $html        Email HTML.
		 * @param WP_User $user        User object.
		 * @param array   $summary     Jurisdiction summary.
		 * @param string  $alert_level Alert level.
		 */
		return apply_filters( 'mts_jurisdiction_alert_email_body', $html, $user, $summary, $alert_level );
	}

	/**
	 * Get jurisdiction alert heading
	 *
	 * @since 1.8.2
	 * @param array  $summary     Jurisdiction summary.
	 * @param string $alert_level Alert level.
	 * @return string Heading text.
	 */
	private function get_jurisdiction_alert_heading( $summary, $alert_level ) {
		$name = $summary['jurisdiction_name'];

		switch ( $alert_level ) {
			case 'urgent':
				return sprintf(
					/* translators: %s: jurisdiction name */
					__( 'Urgent: %s Limit Almost Reached', 'mytravelstatus' ),
					$name
				);
			case 'danger':
				return sprintf(
					/* translators: %s: jurisdiction name */
					__( 'Warning: Approaching %s Limit', 'mytravelstatus' ),
					$name
				);
			case 'warning':
			default:
				return sprintf(
					/* translators: %s: jurisdiction name */
					__( '%s Status Update', 'mytravelstatus' ),
					$name
				);
		}
	}

	/**
	 * Get jurisdiction alert message
	 *
	 * @since 1.8.2
	 * @param array  $summary     Jurisdiction summary.
	 * @param string $alert_level Alert level.
	 * @return string Message text.
	 */
	private function get_jurisdiction_alert_message( $summary, $alert_level ) {
		$name      = $summary['jurisdiction_name'];
		$remaining = $summary['days_remaining'];
		$used      = $summary['days_used'];
		$allowed   = $summary['days_allowed'];

		switch ( $alert_level ) {
			case 'urgent':
				return sprintf(
					/* translators: 1: days remaining, 2: jurisdiction name */
					__( 'You have only %1$d days remaining for %2$s. Any additional travel may result in exceeding your allowed time.', 'mytravelstatus' ),
					$remaining,
					$name
				);
			case 'danger':
				return sprintf(
					/* translators: 1: days used, 2: days allowed, 3: jurisdiction name */
					__( 'You have used %1$d of your %2$d allowed days for %3$s. Please plan any future travel carefully.', 'mytravelstatus' ),
					$used,
					$allowed,
					$name
				);
			case 'warning':
			default:
				return sprintf(
					/* translators: 1: days used, 2: days allowed, 3: jurisdiction name */
					__( 'You have used %1$d of your %2$d allowed days for %3$s. This is a friendly reminder to help you plan your travel.', 'mytravelstatus' ),
					$used,
					$allowed,
					$name
				);
		}
	}

	/**
	 * Send push notification for jurisdiction alert
	 *
	 * @since 1.8.2
	 * @param int    $user_id     User ID.
	 * @param array  $summary     Jurisdiction summary.
	 * @param string $alert_level Alert level.
	 * @return bool Whether push was sent.
	 */
	public function send_jurisdiction_push_notification( $user_id, $summary, $alert_level ) {
		// Check if push notifications are enabled.
		$settings = $this->get_user_settings( $user_id );
		if ( empty( $settings['push_alerts'] ) ) {
			return false;
		}

		// Get user's registered devices.
		global $wpdb;
		$table   = MTS_Schema::get_table( 'devices' );
		$devices = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM $table WHERE user_id = %d AND is_active = 1",
			$user_id
		) );

		if ( empty( $devices ) ) {
			return false;
		}

		$title = $this->get_jurisdiction_email_subject( $summary, $alert_level );
		$body  = $this->get_jurisdiction_alert_message( $summary, $alert_level );

		$payload = array(
			'title'        => $title,
			'body'         => $body,
			'icon'         => '/wp-content/plugins/mytravelstatus/assets/images/icon-192.png',
			'badge'        => '/wp-content/plugins/mytravelstatus/assets/images/badge-72.png',
			'tag'          => 'mts-alert-' . $summary['jurisdiction_code'],
			'data'         => array(
				'type'         => 'jurisdiction_alert',
				'jurisdiction' => $summary['jurisdiction_code'],
				'alert_level'  => $alert_level,
				'url'          => $this->get_tracker_url(),
			),
		);

		$sent = false;

		foreach ( $devices as $device ) {
			if ( 'web' === $device->platform && ! empty( $device->push_subscription ) ) {
				$result = $this->send_web_push( $device->push_subscription, $payload );
				$sent   = $sent || $result;
			} elseif ( 'ios' === $device->platform || 'android' === $device->platform ) {
				if ( ! empty( $device->push_token ) ) {
					$result = $this->send_firebase_push( $device->push_token, $payload, $device->platform );
					$sent   = $sent || $result;
				}
			}
		}

		if ( $sent ) {
			/**
			 * Fires after a push notification has been sent.
			 *
			 * @since 1.8.2
			 * @param int    $user_id     User ID.
			 * @param array  $summary     Jurisdiction summary.
			 * @param string $alert_level Alert level.
			 */
			do_action( 'mts_push_notification_sent', $user_id, $summary, $alert_level );
		}

		return $sent;
	}

	/**
	 * Send Web Push notification
	 *
	 * @since 1.8.2
	 * @param string $subscription JSON subscription object.
	 * @param array  $payload      Notification payload.
	 * @return bool Success.
	 */
	private function send_web_push( $subscription, $payload ) {
		// Check if web-push library is available.
		if ( ! class_exists( 'Minishlink\\WebPush\\WebPush' ) ) {
			return false;
		}

		$vapid = get_option( 'mts_vapid_keys' );
		if ( empty( $vapid['public'] ) || empty( $vapid['private'] ) ) {
			return false;
		}

		$sub_data = json_decode( $subscription, true );
		if ( ! $sub_data ) {
			return false;
		}

		try {
			$auth = array(
				'VAPID' => array(
					'subject'    => home_url(),
					'publicKey'  => $vapid['public'],
					'privateKey' => $vapid['private'],
				),
			);

			$webPush = new \Minishlink\WebPush\WebPush( $auth );

			$webPush->queueNotification(
				\Minishlink\WebPush\Subscription::create( $sub_data ),
				wp_json_encode( $payload )
			);

			foreach ( $webPush->flush() as $report ) {
				return $report->isSuccess();
			}
		} catch ( Exception $e ) {
			error_log( 'MTS Web Push Error: ' . $e->getMessage() );
			return false;
		}

		return false;
	}

	/**
	 * Send Firebase Cloud Messaging push
	 *
	 * @since 1.8.2
	 * @param string $token    FCM token.
	 * @param array  $payload  Notification payload.
	 * @param string $platform Platform (ios/android).
	 * @return bool Success.
	 */
	private function send_firebase_push( $token, $payload, $platform ) {
		$fcm_key = get_option( 'mts_fcm_server_key' );
		if ( empty( $fcm_key ) ) {
			return false;
		}

		$message = array(
			'to'           => $token,
			'notification' => array(
				'title' => $payload['title'],
				'body'  => $payload['body'],
			),
			'data'         => $payload['data'],
		);

		if ( 'ios' === $platform ) {
			$message['notification']['sound'] = 'default';
			$message['notification']['badge'] = 1;
		} elseif ( 'android' === $platform ) {
			$message['notification']['icon']  = 'ic_notification';
			$message['notification']['color'] = '#4A7BA7';
		}

		$response = wp_remote_post(
			'https://fcm.googleapis.com/fcm/send',
			array(
				'headers' => array(
					'Authorization' => 'key=' . $fcm_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $message ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'MTS FCM Error: ' . $response->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] ) && $body['success'] > 0;
	}

	/**
	 * Process daily multi-jurisdiction alerts
	 *
	 * @since 1.8.2
	 * @return void
	 */
	public function process_multi_jurisdiction_alerts() {
		global $wpdb;

		// Get users with tracked jurisdictions.
		$meta_key = 'mts_tracked_jurisdictions';
		$user_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
			$meta_key
		) );

		if ( empty( $user_ids ) ) {
			return;
		}

		$alerts_summary = array();

		foreach ( $user_ids as $user_id ) {
			$alerts = $this->check_multi_jurisdiction_alerts( (int) $user_id );
			if ( ! empty( $alerts ) ) {
				$alerts_summary[ $user_id ] = $alerts;
			}
		}

		/**
		 * Fires after multi-jurisdiction daily alerts have been processed.
		 *
		 * @since 1.8.2
		 * @param array $alerts_summary Summary of alerts sent per user.
		 */
		do_action( 'mts_multi_jurisdiction_alerts_processed', $alerts_summary );
	}
}
