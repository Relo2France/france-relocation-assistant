<?php
/**
 * User Profile Meta Fields
 *
 * Stores user preferences and relocation status.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user relocation profile
 *
 * @param int $user_id User ID (default: current user)
 * @return array Profile data
 */
function fra_get_user_profile($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array();
    }

    $defaults = array(
        'visa_type'          => '',
        'target_move_date'   => '',
        'current_stage'      => 'planning', // planning, preparing, moving, settling
        'destination_region' => '',
        'family_size'        => 1,
        'has_property'       => false,
        'has_job'            => false,
        'completed_tasks'    => array(),
        'onboarding_complete'=> false
    );

    $profile = get_user_meta($user_id, '_fra_relocation_profile', true);

    if (!is_array($profile)) {
        $profile = array();
    }

    return wp_parse_args($profile, $defaults);
}

/**
 * Save user relocation profile
 *
 * @param array $data Profile data
 * @param int $user_id User ID (default: current user)
 * @return bool Success
 */
function fra_save_user_profile($data, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $current = fra_get_user_profile($user_id);
    $updated = wp_parse_args($data, $current);

    // Sanitize
    $updated['visa_type'] = sanitize_text_field($updated['visa_type']);
    $updated['target_move_date'] = sanitize_text_field($updated['target_move_date']);
    $updated['current_stage'] = sanitize_text_field($updated['current_stage']);
    $updated['destination_region'] = sanitize_text_field($updated['destination_region']);
    $updated['family_size'] = absint($updated['family_size']);
    $updated['has_property'] = (bool) $updated['has_property'];
    $updated['has_job'] = (bool) $updated['has_job'];
    $updated['completed_tasks'] = array_map('sanitize_text_field', (array) $updated['completed_tasks']);
    $updated['onboarding_complete'] = (bool) $updated['onboarding_complete'];

    return update_user_meta($user_id, '_fra_relocation_profile', $updated);
}

/**
 * Mark a task as complete
 *
 * @param string $task_id Task identifier
 * @param int $user_id User ID
 * @return bool Success
 */
function fra_complete_task($task_id, $user_id = null) {
    $profile = fra_get_user_profile($user_id);

    if (!in_array($task_id, $profile['completed_tasks'])) {
        $profile['completed_tasks'][] = $task_id;
        return fra_save_user_profile($profile, $user_id);
    }

    return true;
}

/**
 * Mark a task as incomplete
 *
 * @param string $task_id Task identifier
 * @param int $user_id User ID
 * @return bool Success
 */
function fra_uncomplete_task($task_id, $user_id = null) {
    $profile = fra_get_user_profile($user_id);

    $key = array_search($task_id, $profile['completed_tasks']);
    if ($key !== false) {
        unset($profile['completed_tasks'][$key]);
        $profile['completed_tasks'] = array_values($profile['completed_tasks']);
        return fra_save_user_profile($profile, $user_id);
    }

    return true;
}

/**
 * Get completion percentage
 *
 * @param int $user_id User ID
 * @return int Percentage (0-100)
 */
function fra_get_completion_percentage($user_id = null) {
    $profile = fra_get_user_profile($user_id);
    $checklist = fra_get_checklist($profile['visa_type']);

    if (empty($checklist)) {
        return 0;
    }

    $total_tasks = 0;
    foreach ($checklist as $phase) {
        $total_tasks += count($phase['tasks']);
    }

    if ($total_tasks === 0) {
        return 0;
    }

    $completed = count($profile['completed_tasks']);
    return min(100, round(($completed / $total_tasks) * 100));
}

/**
 * REST API: Get profile
 */
function fra_api_get_profile($request) {
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Must be logged in', array('status' => 401));
    }

    return new WP_REST_Response(array(
        'profile' => fra_get_user_profile(),
        'completion' => fra_get_completion_percentage()
    ), 200);
}

/**
 * REST API: Update profile
 */
function fra_api_update_profile($request) {
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Must be logged in', array('status' => 401));
    }

    $data = $request->get_json_params();
    $success = fra_save_user_profile($data);

    return new WP_REST_Response(array(
        'success' => $success,
        'profile' => fra_get_user_profile()
    ), 200);
}

/**
 * REST API: Toggle task
 */
function fra_api_toggle_task($request) {
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Must be logged in', array('status' => 401));
    }

    $task_id = sanitize_text_field($request->get_param('task_id'));
    $complete = (bool) $request->get_param('complete');

    if ($complete) {
        fra_complete_task($task_id);
    } else {
        fra_uncomplete_task($task_id);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'completion' => fra_get_completion_percentage()
    ), 200);
}

/**
 * Register REST endpoints
 */
function fra_register_profile_endpoints() {
    register_rest_route('fra/v1', '/profile', array(
        array(
            'methods' => 'GET',
            'callback' => 'fra_api_get_profile',
            'permission_callback' => 'is_user_logged_in'
        ),
        array(
            'methods' => 'POST',
            'callback' => 'fra_api_update_profile',
            'permission_callback' => 'is_user_logged_in'
        )
    ));

    register_rest_route('fra/v1', '/task', array(
        'methods' => 'POST',
        'callback' => 'fra_api_toggle_task',
        'permission_callback' => 'is_user_logged_in'
    ));
}
add_action('rest_api_init', 'fra_register_profile_endpoints');
