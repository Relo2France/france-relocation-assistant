<?php
/**
 * Dashboard Widget
 *
 * Renders the relocation progress dashboard.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the progress dashboard
 *
 * @return string HTML output
 */
function fra_render_progress_dashboard() {
    if (!is_user_logged_in()) {
        return fra_render_login_prompt();
    }

    $profile = fra_get_user_profile();

    // Show onboarding if not complete
    if (empty($profile['onboarding_complete'])) {
        return fra_render_onboarding();
    }

    $checklist = fra_get_checklist($profile['visa_type']);
    $completion = fra_get_completion_percentage();
    $visa_types = fra_get_visa_types();

    ob_start();
    ?>
    <div class="fra-dashboard" data-nonce="<?php echo esc_attr(wp_create_nonce('fra_dashboard_nonce')); ?>">
        <!-- Progress Overview -->
        <div class="fra-dashboard-header">
            <div class="fra-progress-ring-container">
                <svg class="fra-progress-ring" viewBox="0 0 120 120">
                    <circle class="fra-progress-bg" cx="60" cy="60" r="52" />
                    <circle class="fra-progress-fill" cx="60" cy="60" r="52"
                            stroke-dasharray="326.73"
                            stroke-dashoffset="<?php echo 326.73 - (326.73 * $completion / 100); ?>" />
                </svg>
                <div class="fra-progress-text">
                    <span class="fra-progress-number"><?php echo esc_html($completion); ?></span>
                    <span class="fra-progress-percent">%</span>
                </div>
            </div>
            <div class="fra-dashboard-info">
                <h3 class="fra-dashboard-title">Your Relocation Progress</h3>
                <p class="fra-dashboard-subtitle">
                    <?php echo esc_html($visa_types[$profile['visa_type']] ?? 'General Relocation'); ?>
                    <?php if (!empty($profile['target_move_date'])): ?>
                        &bull; Target: <?php echo esc_html(date('F Y', strtotime($profile['target_move_date']))); ?>
                    <?php endif; ?>
                </p>
                <button type="button" class="fra-edit-profile-btn" data-action="edit-profile">
                    Edit Profile
                </button>
            </div>
        </div>

        <!-- Timeline/Phases -->
        <div class="fra-timeline">
            <?php foreach ($checklist as $index => $phase): ?>
                <?php
                $phase_tasks = count($phase['tasks']);
                $phase_completed = 0;
                foreach ($phase['tasks'] as $task) {
                    if (in_array($task['id'], $profile['completed_tasks'])) {
                        $phase_completed++;
                    }
                }
                $phase_progress = $phase_tasks > 0 ? round(($phase_completed / $phase_tasks) * 100) : 0;
                $is_current = $profile['current_stage'] === $phase['phase'];
                $is_completed = $phase_progress === 100;
                ?>
                <div class="fra-phase <?php echo $is_current ? 'fra-phase-current' : ''; ?> <?php echo $is_completed ? 'fra-phase-completed' : ''; ?>"
                     data-phase="<?php echo esc_attr($phase['phase']); ?>">

                    <div class="fra-phase-header" data-toggle="phase">
                        <div class="fra-phase-indicator">
                            <?php if ($is_completed): ?>
                                <span class="fra-phase-check">✓</span>
                            <?php else: ?>
                                <span class="fra-phase-number"><?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fra-phase-info">
                            <h4 class="fra-phase-title"><?php echo esc_html($phase['title']); ?></h4>
                            <span class="fra-phase-subtitle"><?php echo esc_html($phase['subtitle']); ?></span>
                        </div>
                        <div class="fra-phase-progress">
                            <span class="fra-phase-count"><?php echo $phase_completed; ?>/<?php echo $phase_tasks; ?></span>
                            <span class="fra-phase-toggle">▼</span>
                        </div>
                    </div>

                    <div class="fra-phase-tasks">
                        <?php foreach ($phase['tasks'] as $task): ?>
                            <?php $is_done = in_array($task['id'], $profile['completed_tasks']); ?>
                            <div class="fra-task <?php echo $is_done ? 'fra-task-done' : ''; ?>">
                                <label class="fra-task-label">
                                    <input type="checkbox"
                                           class="fra-task-checkbox"
                                           data-task-id="<?php echo esc_attr($task['id']); ?>"
                                           <?php checked($is_done); ?> />
                                    <span class="fra-task-check"></span>
                                    <span class="fra-task-title"><?php echo esc_html($task['title']); ?></span>
                                </label>
                                <?php if (!empty($task['link'])): ?>
                                    <a href="<?php echo esc_url($task['link']); ?>" class="fra-task-link" title="View guide">
                                        <span class="fra-task-link-icon">→</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Actions -->
        <div class="fra-dashboard-actions">
            <a href="/guides/" class="fra-action-btn fra-action-guides">
                <span class="fra-action-icon">📚</span>
                Browse Guides
            </a>
            <a href="/chat/" class="fra-action-btn fra-action-chat">
                <span class="fra-action-icon">💬</span>
                Ask AI Assistant
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render onboarding form
 *
 * @return string HTML output
 */
function fra_render_onboarding() {
    $visa_types = fra_get_visa_types();

    ob_start();
    ?>
    <div class="fra-onboarding" data-nonce="<?php echo esc_attr(wp_create_nonce('fra_dashboard_nonce')); ?>">
        <div class="fra-onboarding-header">
            <h3 class="fra-onboarding-title">Welcome! Let's Set Up Your Dashboard</h3>
            <p class="fra-onboarding-subtitle">Tell us about your relocation plans so we can personalize your experience.</p>
        </div>

        <form class="fra-onboarding-form" id="fra-onboarding-form">
            <div class="fra-form-group">
                <label for="fra-visa-type" class="fra-form-label">What type of visa are you pursuing?</label>
                <select id="fra-visa-type" name="visa_type" class="fra-form-select" required>
                    <option value="">Select visa type...</option>
                    <?php foreach ($visa_types as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fra-form-group">
                <label for="fra-move-date" class="fra-form-label">Target move date (approximate)</label>
                <input type="month" id="fra-move-date" name="target_move_date" class="fra-form-input" />
            </div>

            <div class="fra-form-group">
                <label for="fra-region" class="fra-form-label">Destination region in France (if known)</label>
                <select id="fra-region" name="destination_region" class="fra-form-select">
                    <option value="">Not sure yet</option>
                    <option value="ile-de-france">Île-de-France (Paris region)</option>
                    <option value="provence">Provence-Alpes-Côte d'Azur</option>
                    <option value="occitanie">Occitanie</option>
                    <option value="nouvelle-aquitaine">Nouvelle-Aquitaine</option>
                    <option value="auvergne-rhone-alpes">Auvergne-Rhône-Alpes</option>
                    <option value="brittany">Brittany</option>
                    <option value="normandy">Normandy</option>
                    <option value="other">Other region</option>
                </select>
            </div>

            <div class="fra-form-group">
                <label class="fra-form-label">Family size</label>
                <div class="fra-form-counter">
                    <button type="button" class="fra-counter-btn" data-action="decrease">−</button>
                    <input type="number" id="fra-family-size" name="family_size" value="1" min="1" max="10" class="fra-counter-input" />
                    <button type="button" class="fra-counter-btn" data-action="increase">+</button>
                </div>
            </div>

            <div class="fra-form-group fra-form-checkboxes">
                <label class="fra-checkbox-label">
                    <input type="checkbox" name="has_job" value="1" />
                    <span class="fra-checkbox-text">I already have a job/contract in France</span>
                </label>
                <label class="fra-checkbox-label">
                    <input type="checkbox" name="has_property" value="1" />
                    <span class="fra-checkbox-text">I already own/have arranged housing in France</span>
                </label>
            </div>

            <button type="submit" class="fra-onboarding-submit">
                Create My Dashboard
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render login prompt
 *
 * @return string HTML output
 */
function fra_render_login_prompt() {
    ob_start();
    ?>
    <div class="fra-login-prompt">
        <div class="fra-login-icon">📊</div>
        <h3 class="fra-login-title">Track Your Relocation Progress</h3>
        <p class="fra-login-text">
            Sign in or create an account to access your personalized relocation dashboard,
            track your progress, and get a customized checklist based on your visa type.
        </p>
        <div class="fra-login-actions">
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="fra-btn fra-btn-primary">
                Sign In
            </a>
            <a href="<?php echo esc_url(wp_registration_url()); ?>" class="fra-btn fra-btn-secondary">
                Create Account
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Register dashboard shortcode
 */
function fra_register_dashboard_shortcode() {
    add_shortcode('fra_progress_dashboard', 'fra_render_progress_dashboard');
}
add_action('init', 'fra_register_dashboard_shortcode');

/**
 * AJAX handler for onboarding form
 */
function fra_ajax_save_onboarding() {
    check_ajax_referer('fra_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 401);
    }

    $data = array(
        'visa_type'          => sanitize_text_field($_POST['visa_type'] ?? ''),
        'target_move_date'   => sanitize_text_field($_POST['target_move_date'] ?? ''),
        'destination_region' => sanitize_text_field($_POST['destination_region'] ?? ''),
        'family_size'        => absint($_POST['family_size'] ?? 1),
        'has_job'            => !empty($_POST['has_job']),
        'has_property'       => !empty($_POST['has_property']),
        'onboarding_complete'=> true,
        'current_stage'      => 'planning'
    );

    $success = fra_save_user_profile($data);

    wp_send_json_success(array(
        'success' => $success,
        'redirect' => get_permalink()
    ));
}
add_action('wp_ajax_fra_save_onboarding', 'fra_ajax_save_onboarding');

/**
 * AJAX handler for updating profile
 */
function fra_ajax_update_dashboard_profile() {
    check_ajax_referer('fra_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 401);
    }

    $data = array();

    if (isset($_POST['visa_type'])) {
        $data['visa_type'] = sanitize_text_field($_POST['visa_type']);
    }
    if (isset($_POST['target_move_date'])) {
        $data['target_move_date'] = sanitize_text_field($_POST['target_move_date']);
    }
    if (isset($_POST['destination_region'])) {
        $data['destination_region'] = sanitize_text_field($_POST['destination_region']);
    }
    if (isset($_POST['family_size'])) {
        $data['family_size'] = absint($_POST['family_size']);
    }
    if (isset($_POST['current_stage'])) {
        $data['current_stage'] = sanitize_text_field($_POST['current_stage']);
    }

    $success = fra_save_user_profile($data);

    wp_send_json_success(array(
        'success' => $success,
        'profile' => fra_get_user_profile()
    ));
}
add_action('wp_ajax_fra_update_dashboard_profile', 'fra_ajax_update_dashboard_profile');
