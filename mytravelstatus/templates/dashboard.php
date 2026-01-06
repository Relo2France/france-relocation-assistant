<?php
/**
 * MyTravelStatus Dashboard Template
 *
 * This template renders the standalone MyTravelStatus dashboard.
 * It provides a comprehensive interface for tracking visa, tax, and residency
 * days across multiple jurisdictions.
 *
 * @package MTS_Tracker
 * @since   1.0.0
 * @updated 1.8.2 - Added multi-jurisdiction support
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$api = MTS_API::get_instance();
$alerts = MTS_Alerts::get_instance();
$jurisdiction = MTS_Jurisdiction::get_instance();
$settings = $alerts->get_user_settings( $user_id );
$summary = $alerts->get_user_summary( $user_id );
$countries = MTS_Schema::get_schengen_countries();

// Get multi-jurisdiction data.
$tracked_jurisdictions = $jurisdiction->get_user_tracked_jurisdictions( $user_id );
$compliance_overview = $jurisdiction->get_compliance_overview( $user_id );
$available_rules = $jurisdiction->get_available_rules();
$has_multi_jurisdiction = ! empty( $tracked_jurisdictions ) && count( $tracked_jurisdictions ) > 1;

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
<div class="mts-dashboard" data-multi-jurisdiction="<?php echo $has_multi_jurisdiction ? 'true' : 'false'; ?>">
	<div class="mts-header">
		<h2><?php esc_html_e( 'Travel Compliance Tracker', 'mytravelstatus' ); ?></h2>
		<p class="mts-subtitle">
			<?php
			if ( $has_multi_jurisdiction ) {
				esc_html_e( 'Track your visa, tax, and residency days across multiple jurisdictions.', 'mytravelstatus' );
			} else {
				esc_html_e( 'Track your Schengen zone days to stay compliant with the 90/180 day rule.', 'mytravelstatus' );
			}
			?>
		</p>
	</div>

	<?php if ( $has_multi_jurisdiction && $compliance_overview ) : ?>
	<!-- Multi-Jurisdiction Compliance Overview -->
	<div class="mts-compliance-overview">
		<div class="mts-overview-header">
			<h3><?php esc_html_e( 'Compliance Overview', 'mytravelstatus' ); ?></h3>
			<div class="mts-overview-stats">
				<?php if ( $compliance_overview['critical_count'] > 0 || $compliance_overview['exceeded_count'] > 0 ) : ?>
				<span class="mts-badge mts-badge-danger">
					<?php
					printf(
						/* translators: %d: number of critical issues */
						esc_html__( '%d Critical', 'mytravelstatus' ),
						$compliance_overview['critical_count'] + $compliance_overview['exceeded_count']
					);
					?>
				</span>
				<?php endif; ?>
				<?php if ( $compliance_overview['warning_count'] > 0 ) : ?>
				<span class="mts-badge mts-badge-warning">
					<?php
					printf(
						/* translators: %d: number of warnings */
						esc_html__( '%d Warning', 'mytravelstatus' ),
						$compliance_overview['warning_count']
					);
					?>
				</span>
				<?php endif; ?>
				<?php if ( $compliance_overview['ok_count'] > 0 ) : ?>
				<span class="mts-badge mts-badge-ok">
					<?php
					printf(
						/* translators: %d: number of OK jurisdictions */
						esc_html__( '%d OK', 'mytravelstatus' ),
						$compliance_overview['ok_count']
					);
					?>
				</span>
				<?php endif; ?>
			</div>
		</div>

		<div class="mts-jurisdiction-cards">
			<?php foreach ( $compliance_overview['summaries'] as $j_summary ) : ?>
			<div class="mts-jurisdiction-card mts-status-<?php echo esc_attr( $j_summary['status'] ); ?>" data-jurisdiction="<?php echo esc_attr( $j_summary['jurisdiction_code'] ); ?>">
				<div class="mts-card-header">
					<?php if ( ! empty( $j_summary['flag_emoji'] ) ) : ?>
					<span class="mts-flag"><?php echo esc_html( $j_summary['flag_emoji'] ); ?></span>
					<?php endif; ?>
					<span class="mts-jurisdiction-name"><?php echo esc_html( $j_summary['jurisdiction_name'] ); ?></span>
					<span class="mts-status-indicator"></span>
				</div>
				<div class="mts-card-body">
					<div class="mts-days-display">
						<span class="mts-days-used"><?php echo esc_html( $j_summary['days_used'] ); ?></span>
						<span class="mts-days-separator">/</span>
						<span class="mts-days-allowed"><?php echo esc_html( $j_summary['days_allowed'] ); ?></span>
						<span class="mts-days-label"><?php esc_html_e( 'days', 'mytravelstatus' ); ?></span>
					</div>
					<div class="mts-card-progress">
						<div class="mts-progress-bar" style="width: <?php echo esc_attr( min( 100, $j_summary['percentage'] ) ); ?>%"></div>
					</div>
					<div class="mts-card-meta">
						<span class="mts-remaining">
							<?php
							printf(
								/* translators: %d: days remaining */
								esc_html__( '%d days remaining', 'mytravelstatus' ),
								$j_summary['days_remaining']
							);
							?>
						</span>
						<?php if ( ! empty( $j_summary['category'] ) ) : ?>
						<span class="mts-category"><?php echo esc_html( ucfirst( $j_summary['category'] ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php
				// Show special breakdowns for complex rules.
				if ( ! empty( $j_summary['uk_srt_breakdown'] ) ) :
					$srt = $j_summary['uk_srt_breakdown'];
				?>
				<div class="mts-card-breakdown mts-srt-breakdown">
					<span class="mts-breakdown-label"><?php esc_html_e( 'SRT Result:', 'mytravelstatus' ); ?></span>
					<span class="mts-breakdown-value mts-srt-<?php echo esc_attr( $srt['result'] ); ?>">
						<?php echo 'resident' === $srt['result'] ? esc_html__( 'UK Resident', 'mytravelstatus' ) : esc_html__( 'Non-Resident', 'mytravelstatus' ); ?>
					</span>
				</div>
				<?php elseif ( ! empty( $j_summary['weighted_breakdown'] ) ) :
					$weighted = $j_summary['weighted_breakdown'];
				?>
				<div class="mts-card-breakdown mts-weighted-breakdown">
					<span class="mts-breakdown-label"><?php esc_html_e( 'SPT Weighted:', 'mytravelstatus' ); ?></span>
					<span class="mts-breakdown-value">
						<?php echo esc_html( number_format( $weighted['total_weighted'], 1 ) ); ?>/<?php echo esc_html( $weighted['threshold'] ); ?>
					</span>
				</div>
				<?php elseif ( ! empty( $j_summary['multi_year_breakdown'] ) ) :
					$multi = $j_summary['multi_year_breakdown'];
				?>
				<div class="mts-card-breakdown mts-multi-year-breakdown">
					<span class="mts-breakdown-label"><?php esc_html_e( 'Combined:', 'mytravelstatus' ); ?></span>
					<span class="mts-breakdown-value">
						<?php echo esc_html( $multi['combined_days'] ); ?>/<?php echo esc_html( $multi['secondary_threshold'] ); ?>
					</span>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Manage Jurisdictions Button -->
		<div class="mts-manage-jurisdictions">
			<button type="button" id="mts-manage-jurisdictions-btn" class="mts-button">
				<?php esc_html_e( 'Manage Tracked Jurisdictions', 'mytravelstatus' ); ?>
			</button>
		</div>
	</div>
	<?php endif; ?>

	<!-- Status Summary (Schengen or Primary Jurisdiction) -->
	<div class="mts-summary mts-status-<?php echo esc_attr( $status_class ); ?>"<?php echo $has_multi_jurisdiction ? ' style="display:none;"' : ''; ?>>
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

<!-- Jurisdiction Management Modal -->
<div id="mts-jurisdiction-modal" class="mts-modal" style="display:none;">
	<div class="mts-modal-backdrop"></div>
	<div class="mts-modal-content">
		<div class="mts-modal-header">
			<h3><?php esc_html_e( 'Manage Tracked Jurisdictions', 'mytravelstatus' ); ?></h3>
			<button type="button" class="mts-modal-close" aria-label="<?php esc_attr_e( 'Close', 'mytravelstatus' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="mts-modal-body">
			<p class="mts-modal-description">
				<?php esc_html_e( 'Select the jurisdictions you want to track. Your trips will be automatically counted against each selected rule.', 'mytravelstatus' ); ?>
			</p>

			<!-- Category Tabs -->
			<div class="mts-jurisdiction-tabs">
				<button type="button" class="mts-tab-btn active" data-category="visa">
					<?php esc_html_e( 'Visa Rules', 'mytravelstatus' ); ?>
				</button>
				<button type="button" class="mts-tab-btn" data-category="tax">
					<?php esc_html_e( 'Tax Residency', 'mytravelstatus' ); ?>
				</button>
				<button type="button" class="mts-tab-btn" data-category="residency">
					<?php esc_html_e( 'Immigration', 'mytravelstatus' ); ?>
				</button>
			</div>

			<!-- Jurisdiction List -->
			<div class="mts-jurisdiction-list">
				<?php
				// Group rules by category.
				$rules_by_category = array(
					'visa'      => array(),
					'tax'       => array(),
					'residency' => array(),
				);

				foreach ( $available_rules as $rule ) {
					$cat = $rule['category'] ?? 'visa';
					if ( isset( $rules_by_category[ $cat ] ) ) {
						$rules_by_category[ $cat ][] = $rule;
					}
				}

				foreach ( $rules_by_category as $category => $rules ) :
					$is_active = 'visa' === $category ? 'active' : '';
				?>
				<div class="mts-jurisdiction-category <?php echo esc_attr( $is_active ); ?>" data-category="<?php echo esc_attr( $category ); ?>">
					<?php if ( empty( $rules ) ) : ?>
					<p class="mts-no-rules"><?php esc_html_e( 'No rules available in this category.', 'mytravelstatus' ); ?></p>
					<?php else : ?>
					<?php foreach ( $rules as $rule ) :
						$is_tracked = in_array( $rule['code'], $tracked_jurisdictions, true );
					?>
					<label class="mts-jurisdiction-item <?php echo $is_tracked ? 'checked' : ''; ?>">
						<input type="checkbox" name="jurisdictions[]" value="<?php echo esc_attr( $rule['code'] ); ?>" <?php checked( $is_tracked ); ?>>
						<span class="mts-item-content">
							<?php if ( ! empty( $rule['flag_emoji'] ) ) : ?>
							<span class="mts-flag"><?php echo esc_html( $rule['flag_emoji'] ); ?></span>
							<?php endif; ?>
							<span class="mts-item-details">
								<span class="mts-item-name"><?php echo esc_html( $rule['name'] ); ?></span>
								<span class="mts-item-rule">
									<?php
									$rule_desc = sprintf(
										/* translators: 1: days allowed, 2: window in days or method */
										esc_html__( '%1$d days / %2$s', 'mytravelstatus' ),
										$rule['days_allowed'],
										'rolling' === $rule['counting_method']
											? sprintf( esc_html__( '%d-day window', 'mytravelstatus' ), $rule['window_days'] )
											: esc_html( ucwords( str_replace( '_', ' ', $rule['counting_method'] ) ) )
									);
									echo esc_html( $rule_desc );
									?>
								</span>
							</span>
						</span>
					</label>
					<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="mts-modal-footer">
			<button type="button" class="mts-button" id="mts-modal-cancel">
				<?php esc_html_e( 'Cancel', 'mytravelstatus' ); ?>
			</button>
			<button type="button" class="mts-button mts-button-primary" id="mts-modal-save">
				<?php esc_html_e( 'Save Changes', 'mytravelstatus' ); ?>
			</button>
		</div>
	</div>
</div>

<script>
(function() {
	// Multi-jurisdiction modal handling
	var modal = document.getElementById('mts-jurisdiction-modal');
	var openBtn = document.getElementById('mts-manage-jurisdictions-btn');
	var closeBtn = modal ? modal.querySelector('.mts-modal-close') : null;
	var cancelBtn = document.getElementById('mts-modal-cancel');
	var saveBtn = document.getElementById('mts-modal-save');
	var backdrop = modal ? modal.querySelector('.mts-modal-backdrop') : null;
	var tabs = modal ? modal.querySelectorAll('.mts-tab-btn') : [];
	var categories = modal ? modal.querySelectorAll('.mts-jurisdiction-category') : [];

	function openModal() {
		if (modal) modal.style.display = 'flex';
	}

	function closeModal() {
		if (modal) modal.style.display = 'none';
	}

	if (openBtn) openBtn.addEventListener('click', openModal);
	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
	if (backdrop) backdrop.addEventListener('click', closeModal);

	// Tab switching
	tabs.forEach(function(tab) {
		tab.addEventListener('click', function() {
			var category = this.getAttribute('data-category');
			tabs.forEach(function(t) { t.classList.remove('active'); });
			this.classList.add('active');
			categories.forEach(function(cat) {
				cat.classList.toggle('active', cat.getAttribute('data-category') === category);
			});
		});
	});

	// Checkbox styling
	var checkboxes = modal ? modal.querySelectorAll('input[type="checkbox"]') : [];
	checkboxes.forEach(function(cb) {
		cb.addEventListener('change', function() {
			this.closest('.mts-jurisdiction-item').classList.toggle('checked', this.checked);
		});
	});

	// Save jurisdictions
	if (saveBtn) {
		saveBtn.addEventListener('click', function() {
			var selected = [];
			checkboxes.forEach(function(cb) {
				if (cb.checked) selected.push(cb.value);
			});

			// AJAX call to save
			var xhr = new XMLHttpRequest();
			xhr.open('POST', '<?php echo esc_url( rest_url( 'mts/v1/jurisdictions/tracked' ) ); ?>', true);
			xhr.setRequestHeader('Content-Type', 'application/json');
			xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>');
			xhr.onload = function() {
				if (xhr.status >= 200 && xhr.status < 300) {
					closeModal();
					window.location.reload();
				} else {
					alert('<?php echo esc_js( __( 'Failed to save. Please try again.', 'mytravelstatus' ) ); ?>');
				}
			};
			xhr.send(JSON.stringify({ jurisdictions: selected }));
		});
	}
})();
</script>
