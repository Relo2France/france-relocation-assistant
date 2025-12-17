<?php
/**
 * API Settings Management
 *
 * Handles API configuration, rate limiting, and usage tracking.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get API configuration
 *
 * @return array API configuration settings
 */
function fra_get_api_config() {
    return array(
        'api_key'           => France_Relocation_Assistant::get_api_key(),
        'api_model'         => get_option('fra_api_model', 'claude-sonnet-4-20250514'),
        'enable_ai'         => get_option('fra_enable_ai', false),
        'rate_limit'        => get_option('fra_rate_limit', 20), // requests per minute
        'daily_limit'       => get_option('fra_daily_limit', 100), // requests per day for non-members
        'member_multiplier' => get_option('fra_member_rate_multiplier', 3), // members get 3x limits
        'max_tokens'        => get_option('fra_max_tokens', 1024),
        'temperature'       => get_option('fra_temperature', 0.7)
    );
}

/**
 * Check if API is properly configured
 *
 * @return bool True if API is configured and enabled
 */
function fra_is_api_configured() {
    $config = fra_get_api_config();
    return !empty($config['api_key']) && $config['enable_ai'];
}

/**
 * Get rate limit for a user
 *
 * @param int|null $user_id User ID (null for current user)
 * @return array Rate limit configuration
 */
function fra_get_user_rate_limit($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $config = fra_get_api_config();
    $is_member = false;

    // Check membership status
    if ($user_id && class_exists('FRA_Membership')) {
        $membership = FRA_Membership::get_instance();
        $is_member = $membership->user_has_access($user_id);
    }

    $multiplier = $is_member ? $config['member_multiplier'] : 1;

    return array(
        'per_minute' => $config['rate_limit'] * $multiplier,
        'per_day'    => $config['daily_limit'] * $multiplier,
        'is_member'  => $is_member
    );
}

/**
 * Check if user has exceeded rate limit
 *
 * @param int|null $user_id User ID
 * @return array Status with 'allowed' boolean and 'message' string
 */
function fra_check_rate_limit($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    // Get or create user identifier (user ID or IP for guests)
    $identifier = $user_id ?: 'guest_' . fra_get_client_ip_hash();

    $limits = fra_get_user_rate_limit($user_id);
    $usage = fra_get_usage_stats($identifier);

    // Check per-minute limit
    if ($usage['minute_count'] >= $limits['per_minute']) {
        return array(
            'allowed' => false,
            'message' => 'Rate limit exceeded. Please wait a moment before trying again.',
            'retry_after' => 60
        );
    }

    // Check daily limit
    if ($usage['day_count'] >= $limits['per_day']) {
        return array(
            'allowed' => false,
            'message' => $limits['is_member']
                ? 'Daily limit reached. Your limit resets at midnight UTC.'
                : 'Daily limit reached. Become a member for higher limits!',
            'retry_after' => fra_seconds_until_midnight()
        );
    }

    return array(
        'allowed' => true,
        'remaining_minute' => $limits['per_minute'] - $usage['minute_count'],
        'remaining_day' => $limits['per_day'] - $usage['day_count']
    );
}

/**
 * Get usage statistics for a user/identifier
 *
 * @param string $identifier User ID or guest hash
 * @return array Usage stats
 */
function fra_get_usage_stats($identifier) {
    $minute_key = 'fra_rate_' . $identifier . '_' . date('YmdHi');
    $day_key = 'fra_rate_' . $identifier . '_' . date('Ymd');

    return array(
        'minute_count' => (int) get_transient($minute_key),
        'day_count' => (int) get_transient($day_key)
    );
}

/**
 * Increment usage counter
 *
 * @param string|null $identifier User identifier (auto-detected if null)
 * @return void
 */
function fra_increment_usage($identifier = null) {
    if (!$identifier) {
        $user_id = get_current_user_id();
        $identifier = $user_id ?: 'guest_' . fra_get_client_ip_hash();
    }

    $minute_key = 'fra_rate_' . $identifier . '_' . date('YmdHi');
    $day_key = 'fra_rate_' . $identifier . '_' . date('Ymd');

    // Increment minute counter (expires in 60 seconds)
    $minute_count = (int) get_transient($minute_key);
    set_transient($minute_key, $minute_count + 1, 60);

    // Increment day counter (expires at midnight)
    $day_count = (int) get_transient($day_key);
    set_transient($day_key, $day_count + 1, fra_seconds_until_midnight());

    // Track overall usage for admin stats
    fra_track_api_usage();
}

/**
 * Track overall API usage for admin statistics
 *
 * @return void
 */
function fra_track_api_usage() {
    $stats = get_option('fra_api_usage_stats', array(
        'total_requests' => 0,
        'monthly_requests' => 0,
        'month' => date('Ym'),
        'daily_breakdown' => array()
    ));

    // Reset monthly counter if new month
    if ($stats['month'] !== date('Ym')) {
        $stats['monthly_requests'] = 0;
        $stats['month'] = date('Ym');
        $stats['daily_breakdown'] = array();
    }

    // Increment counters
    $stats['total_requests']++;
    $stats['monthly_requests']++;

    // Track daily breakdown (keep last 30 days)
    $today = date('Y-m-d');
    if (!isset($stats['daily_breakdown'][$today])) {
        $stats['daily_breakdown'][$today] = 0;
    }
    $stats['daily_breakdown'][$today]++;

    // Trim old days
    $cutoff = date('Y-m-d', strtotime('-30 days'));
    foreach (array_keys($stats['daily_breakdown']) as $date) {
        if ($date < $cutoff) {
            unset($stats['daily_breakdown'][$date]);
        }
    }

    update_option('fra_api_usage_stats', $stats);
}

/**
 * Get admin usage statistics
 *
 * @return array Usage statistics for admin display
 */
function fra_get_admin_usage_stats() {
    $stats = get_option('fra_api_usage_stats', array(
        'total_requests' => 0,
        'monthly_requests' => 0,
        'month' => date('Ym'),
        'daily_breakdown' => array()
    ));

    // Calculate cost estimates
    $config = fra_get_api_config();
    $cost_per_request = ($config['api_model'] === 'claude-haiku-4-20250514') ? 0.004 : 0.015;

    return array(
        'total_requests' => $stats['total_requests'],
        'monthly_requests' => $stats['monthly_requests'],
        'estimated_monthly_cost' => round($stats['monthly_requests'] * $cost_per_request, 2),
        'daily_breakdown' => $stats['daily_breakdown'],
        'average_daily' => count($stats['daily_breakdown']) > 0
            ? round(array_sum($stats['daily_breakdown']) / count($stats['daily_breakdown']), 1)
            : 0
    );
}

/**
 * Get hashed client IP for guest rate limiting
 *
 * @return string Hashed IP address
 */
function fra_get_client_ip_hash() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Hash the IP for privacy
    return substr(md5($ip . AUTH_SALT), 0, 12);
}

/**
 * Get seconds until midnight UTC
 *
 * @return int Seconds remaining
 */
function fra_seconds_until_midnight() {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $tomorrow = new DateTime('tomorrow', new DateTimeZone('UTC'));
    return $tomorrow->getTimestamp() - $now->getTimestamp();
}

/**
 * Validate API key format
 *
 * @param string $key API key to validate
 * @return bool True if valid format
 */
function fra_validate_api_key_format($key) {
    // Anthropic API keys start with 'sk-ant-'
    return !empty($key) && strpos($key, 'sk-ant-') === 0;
}

/**
 * Register additional settings for API configuration
 */
function fra_register_api_settings() {
    register_setting('fra_settings', 'fra_rate_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 20
    ));

    register_setting('fra_settings', 'fra_daily_limit', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 100
    ));

    register_setting('fra_settings', 'fra_member_rate_multiplier', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 3
    ));

    register_setting('fra_settings', 'fra_max_tokens', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1024
    ));

    register_setting('fra_settings', 'fra_temperature', array(
        'type' => 'number',
        'sanitize_callback' => function($val) {
            return max(0, min(1, floatval($val)));
        },
        'default' => 0.7
    ));
}
add_action('admin_init', 'fra_register_api_settings');
