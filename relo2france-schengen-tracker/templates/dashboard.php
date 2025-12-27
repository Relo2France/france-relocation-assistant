<?php
/**
 * Schengen Tracker Dashboard Template
 *
 * This template renders the standalone Schengen Tracker dashboard.
 * It provides a basic interface for tracking Schengen days when used
 * independently from the Member Tools portal.
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$api = R2F_Schengen_API::get_instance();
$alerts = R2F_Schengen_Alerts::get_instance();
$settings = $alerts->get_user_settings( $user_id );
$summary = $alerts->get_user_summary( $user_id );
$countries = R2F_Schengen_Schema::get_schengen_countries();

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
<div class="r2f-schengen-dashboard">
	<div class="r2f-schengen-header">
		<h2><?php esc_html_e( 'Schengen 90/180 Day Tracker', 'r2f-schengen' ); ?></h2>
		<p class="r2f-schengen-subtitle">
			<?php esc_html_e( 'Track your Schengen zone days to stay compliant with the 90/180 day rule.', 'r2f-schengen' ); ?>
		</p>
	</div>

	<!-- Status Summary -->
	<div class="r2f-schengen-summary r2f-schengen-status-<?php echo esc_attr( $status_class ); ?>">
		<div class="r2f-schengen-summary-grid">
			<div class="r2f-schengen-stat">
				<div class="r2f-schengen-stat-value"><?php echo esc_html( $days_used ); ?></div>
				<div class="r2f-schengen-stat-label"><?php esc_html_e( 'Days Used', 'r2f-schengen' ); ?></div>
			</div>
			<div class="r2f-schengen-stat">
				<div class="r2f-schengen-stat-value"><?php echo esc_html( $days_remaining ); ?></div>
				<div class="r2f-schengen-stat-label"><?php esc_html_e( 'Days Remaining', 'r2f-schengen' ); ?></div>
			</div>
			<div class="r2f-schengen-stat">
				<div class="r2f-schengen-stat-value">90</div>
				<div class="r2f-schengen-stat-label"><?php esc_html_e( 'Max Days', 'r2f-schengen' ); ?></div>
			</div>
		</div>
		<div class="r2f-schengen-progress">
			<div class="r2f-schengen-progress-bar" style="width: <?php echo esc_attr( min( 100, ( $days_used / 90 ) * 100 ) ); ?>%"></div>
		</div>
		<?php if ( $summary && $summary['window_start'] ) : ?>
		<p class="r2f-schengen-window">
			<?php
			printf(
				/* translators: 1: window start date, 2: window end date */
				esc_html__( 'Current window: %1$s to %2$s', 'r2f-schengen' ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $summary['window_start'] ) ) ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $summary['window_end'] ) ) )
			);
			?>
		</p>
		<?php endif; ?>
	</div>

	<!-- Add Trip Form -->
	<div class="r2f-schengen-section">
		<h3><?php esc_html_e( 'Add a Trip', 'r2f-schengen' ); ?></h3>
		<form id="r2f-schengen-add-trip" class="r2f-schengen-form">
			<div class="r2f-schengen-form-row">
				<div class="r2f-schengen-field">
					<label for="r2f-trip-country"><?php esc_html_e( 'Country', 'r2f-schengen' ); ?></label>
					<select id="r2f-trip-country" name="country" required>
						<option value=""><?php esc_html_e( 'Select a country', 'r2f-schengen' ); ?></option>
						<?php foreach ( $countries as $code => $name ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="r2f-schengen-field">
					<label for="r2f-trip-start"><?php esc_html_e( 'Start Date', 'r2f-schengen' ); ?></label>
					<input type="date" id="r2f-trip-start" name="start_date" required>
				</div>
				<div class="r2f-schengen-field">
					<label for="r2f-trip-end"><?php esc_html_e( 'End Date', 'r2f-schengen' ); ?></label>
					<input type="date" id="r2f-trip-end" name="end_date" required>
				</div>
			</div>
			<div class="r2f-schengen-form-row">
				<div class="r2f-schengen-field r2f-schengen-field-wide">
					<label for="r2f-trip-notes"><?php esc_html_e( 'Notes (optional)', 'r2f-schengen' ); ?></label>
					<input type="text" id="r2f-trip-notes" name="notes" placeholder="<?php esc_attr_e( 'e.g., Business trip, vacation', 'r2f-schengen' ); ?>">
				</div>
			</div>
			<button type="submit" class="r2f-schengen-button r2f-schengen-button-primary">
				<?php esc_html_e( 'Add Trip', 'r2f-schengen' ); ?>
			</button>
		</form>
	</div>

	<!-- Trips List -->
	<div class="r2f-schengen-section">
		<h3><?php esc_html_e( 'Your Trips', 'r2f-schengen' ); ?></h3>
		<div id="r2f-schengen-trips" class="r2f-schengen-trips">
			<p class="r2f-schengen-loading"><?php esc_html_e( 'Loading trips...', 'r2f-schengen' ); ?></p>
		</div>
	</div>

	<!-- Settings -->
	<div class="r2f-schengen-section r2f-schengen-settings">
		<h3><?php esc_html_e( 'Alert Settings', 'r2f-schengen' ); ?></h3>
		<form id="r2f-schengen-settings-form" class="r2f-schengen-form">
			<div class="r2f-schengen-form-row">
				<div class="r2f-schengen-field">
					<label>
						<input type="checkbox" id="r2f-email-alerts" name="email_alerts" <?php checked( $settings['email_alerts'] ); ?>>
						<?php esc_html_e( 'Email me when approaching my limit', 'r2f-schengen' ); ?>
					</label>
				</div>
			</div>
			<button type="submit" class="r2f-schengen-button">
				<?php esc_html_e( 'Save Settings', 'r2f-schengen' ); ?>
			</button>
		</form>
	</div>
</div>
