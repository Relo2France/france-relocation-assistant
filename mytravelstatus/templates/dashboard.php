<?php
/**
 * MyTravelStatus Dashboard Template
 *
 * This template renders the standalone MyTravelStatus dashboard.
 * It provides a basic interface for tracking Schengen days when used
 * independently from the Member Tools portal.
 *
 * @package MTS_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$api = MTS_API::get_instance();
$alerts = MTS_Alerts::get_instance();
$settings = $alerts->get_user_settings( $user_id );
$summary = $alerts->get_user_summary( $user_id );
$countries = MTS_Schema::get_schengen_countries();

// Calculate status.
$days_used = $summary ? $summary['days_used'] : 0;
$days_remaining = $summary ? $summary['days_remaining'] : 90;
$status_class = 'safe';
if ( $days_used >= 85 ) {
	$status_class = 'danger';
} elseif ( $days_used >= 75 ) {
	$status_class = 'warning';
} elseif ( $days_used >= 60 ) {
	$status_class = 'caution';
}
?>
<div class="mts-dashboard">
	<div class="mts-header">
		<h2><?php esc_html_e( 'Schengen 90/180 Day Tracker', 'mytravelstatus' ); ?></h2>
		<p class="mts-subtitle">
			<?php esc_html_e( 'Track your Schengen zone days to stay compliant with the 90/180 day rule.', 'mytravelstatus' ); ?>
		</p>
	</div>

	<!-- Status Summary -->
	<div class="mts-summary mts-status-<?php echo esc_attr( $status_class ); ?>">
		<div class="mts-summary-grid">
			<div class="mts-stat">
				<div class="mts-stat-value"><?php echo esc_html( $days_used ); ?></div>
				<div class="mts-stat-label"><?php esc_html_e( 'Days Used', 'mytravelstatus' ); ?></div>
			</div>
			<div class="mts-stat">
				<div class="mts-stat-value"><?php echo esc_html( $days_remaining ); ?></div>
				<div class="mts-stat-label"><?php esc_html_e( 'Days Remaining', 'mytravelstatus' ); ?></div>
			</div>
			<div class="mts-stat">
				<div class="mts-stat-value">90</div>
				<div class="mts-stat-label"><?php esc_html_e( 'Max Days', 'mytravelstatus' ); ?></div>
			</div>
		</div>
		<div class="mts-progress">
			<div class="mts-progress-bar" style="width: <?php echo esc_attr( min( 100, ( $days_used / 90 ) * 100 ) ); ?>%"></div>
		</div>
		<?php if ( $summary && $summary['window_start'] ) : ?>
		<p class="mts-window">
			<?php
			printf(
				/* translators: 1: window start date, 2: window end date */
				esc_html__( 'Current window: %1$s to %2$s', 'mytravelstatus' ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $summary['window_start'] ) ) ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $summary['window_end'] ) ) )
			);
			?>
		</p>
		<?php endif; ?>
	</div>

	<!-- Add Trip Form -->
	<div class="mts-section">
		<h3><?php esc_html_e( 'Add a Trip', 'mytravelstatus' ); ?></h3>
		<form id="mts-add-trip" class="mts-form">
			<div class="mts-form-row">
				<div class="mts-field">
					<label for="r2f-trip-country"><?php esc_html_e( 'Country', 'mytravelstatus' ); ?></label>
					<select id="r2f-trip-country" name="country" required>
						<option value=""><?php esc_html_e( 'Select a country', 'mytravelstatus' ); ?></option>
						<?php foreach ( $countries as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="mts-field">
					<label for="r2f-trip-start"><?php esc_html_e( 'Start Date', 'mytravelstatus' ); ?></label>
					<input type="date" id="r2f-trip-start" name="start_date" required>
				</div>
				<div class="mts-field">
					<label for="r2f-trip-end"><?php esc_html_e( 'End Date', 'mytravelstatus' ); ?></label>
					<input type="date" id="r2f-trip-end" name="end_date" required>
				</div>
			</div>
			<div class="mts-form-row">
				<div class="mts-field mts-field-wide">
					<label for="r2f-trip-notes"><?php esc_html_e( 'Notes (optional)', 'mytravelstatus' ); ?></label>
					<input type="text" id="r2f-trip-notes" name="notes" placeholder="<?php esc_attr_e( 'e.g., Business trip, vacation', 'mytravelstatus' ); ?>">
				</div>
			</div>
			<button type="submit" class="mts-button mts-button-primary">
				<?php esc_html_e( 'Add Trip', 'mytravelstatus' ); ?>
			</button>
		</form>
	</div>

	<!-- Trips List -->
	<div class="mts-section">
		<h3><?php esc_html_e( 'Your Trips', 'mytravelstatus' ); ?></h3>
		<div id="mts-trips" class="mts-trips">
			<p class="mts-loading"><?php esc_html_e( 'Loading trips...', 'mytravelstatus' ); ?></p>
		</div>
	</div>

	<!-- Settings -->
	<div class="mts-section mts-settings">
		<h3><?php esc_html_e( 'Alert Settings', 'mytravelstatus' ); ?></h3>
		<form id="mts-settings-form" class="mts-form">
			<div class="mts-form-row">
				<div class="mts-field">
					<label>
						<input type="checkbox" id="r2f-email-alerts" name="email_alerts" <?php checked( $settings['email_alerts'] ); ?>>
						<?php esc_html_e( 'Email me when approaching my limit', 'mytravelstatus' ); ?>
					</label>
				</div>
			</div>
			<button type="submit" class="mts-button">
				<?php esc_html_e( 'Save Settings', 'mytravelstatus' ); ?>
			</button>
		</form>
	</div>
</div>
