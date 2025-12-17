<?php
/**
 * Main Plugin Bridge
 *
 * Integrates Member Tools with the main France Relocation Assistant plugin's
 * new profile, checklist, and API proxy features (v3.6.0+).
 *
 * @package FRA_Member_Tools
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FRAMT_Main_Plugin_Bridge
 *
 * Bridges Member Tools with the main plugin's new features.
 */
class FRAMT_Main_Plugin_Bridge {

    /**
     * Singleton instance
     *
     * @var FRAMT_Main_Plugin_Bridge|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return FRAMT_Main_Plugin_Bridge
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Only initialize if main plugin functions exist
        if (!function_exists('fra_get_user_profile')) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Sync profile data when Member Tools profile is updated
        add_action('framt_profile_updated', array($this, 'sync_to_main_profile'), 10, 2);

        // Sync checklist completions
        add_action('framt_checklist_item_completed', array($this, 'sync_checklist_completion'), 10, 3);
        add_action('framt_checklist_item_uncompleted', array($this, 'sync_checklist_uncompletion'), 10, 3);

        // Pre-fill Member Tools profile from main plugin on first access
        add_filter('framt_profile_defaults', array($this, 'prefill_from_main_profile'), 10, 2);

        // Extend main plugin's rate limits for members
        add_filter('fra_user_rate_limit', array($this, 'extend_member_rate_limits'), 10, 2);

        // Mark main plugin onboarding as complete when Member Tools profile exists
        add_action('framt_profile_created', array($this, 'mark_main_onboarding_complete'));
    }

    /**
     * Sync Member Tools profile data to main plugin profile
     *
     * @param int $user_id User ID
     * @param array $profile Member Tools profile data
     */
    public function sync_to_main_profile($user_id, $profile) {
        if (!function_exists('fra_save_user_profile')) {
            return;
        }

        // Map Member Tools fields to main plugin fields
        $main_profile_data = array();

        // Visa type mapping
        if (!empty($profile['visa_type'])) {
            $visa_map = array(
                'visitor' => 'visitor',
                'talent_passport' => 'talent',
                'student' => 'student',
                'family' => 'family',
                'retiree' => 'retiree',
                'other' => 'other'
            );
            $main_profile_data['visa_type'] = $visa_map[$profile['visa_type']] ?? 'visitor';
        }

        // Target move date
        if (!empty($profile['target_move_date'])) {
            $main_profile_data['target_move_date'] = $profile['target_move_date'];
        }

        // Destination region
        if (!empty($profile['intended_region'])) {
            $main_profile_data['destination_region'] = $profile['intended_region'];
        }

        // Family size from applicants
        if (!empty($profile['applicants'])) {
            $family_map = array(
                'alone' => 1,
                'spouse' => 2,
                'spouse_kids' => 3, // Default, will be overridden if children count known
                'kids_only' => 2
            );
            $main_profile_data['family_size'] = $family_map[$profile['applicants']] ?? 1;

            // Adjust for actual children count if available
            if (!empty($profile['children_count'])) {
                $base = ($profile['applicants'] === 'alone' || $profile['applicants'] === 'kids_only') ? 1 : 2;
                $main_profile_data['family_size'] = $base + intval($profile['children_count']);
            }
        }

        // Property status
        if (!empty($profile['housing_status'])) {
            $main_profile_data['has_property'] = in_array($profile['housing_status'], array('owned', 'purchased', 'rented'));
        }

        // Job status
        if (!empty($profile['employment_status'])) {
            $main_profile_data['has_job'] = in_array($profile['employment_status'], array('employed', 'contract', 'remote'));
        }

        // Current stage based on visa status
        if (!empty($profile['visa_status'])) {
            $stage_map = array(
                'researching' => 'planning',
                'gathering_documents' => 'preparing',
                'appointment_scheduled' => 'preparing',
                'visa_submitted' => 'preparing',
                'visa_approved' => 'moving',
                'in_france' => 'settling'
            );
            $main_profile_data['current_stage'] = $stage_map[$profile['visa_status']] ?? 'planning';
        }

        // Mark onboarding complete since they have Member Tools profile
        $main_profile_data['onboarding_complete'] = true;

        if (!empty($main_profile_data)) {
            fra_save_user_profile($main_profile_data, $user_id);
        }
    }

    /**
     * Sync checklist completion to main plugin
     *
     * @param int $user_id User ID
     * @param string $checklist_type Checklist type
     * @param string $item_id Item ID
     */
    public function sync_checklist_completion($user_id, $checklist_type, $item_id) {
        if (!function_exists('fra_complete_task')) {
            return;
        }

        // Map Member Tools item IDs to main plugin task IDs
        $task_id = $this->map_checklist_item_to_task($checklist_type, $item_id);

        if ($task_id) {
            fra_complete_task($task_id, $user_id);
        }
    }

    /**
     * Sync checklist uncompletion to main plugin
     *
     * @param int $user_id User ID
     * @param string $checklist_type Checklist type
     * @param string $item_id Item ID
     */
    public function sync_checklist_uncompletion($user_id, $checklist_type, $item_id) {
        if (!function_exists('fra_uncomplete_task')) {
            return;
        }

        $task_id = $this->map_checklist_item_to_task($checklist_type, $item_id);

        if ($task_id) {
            fra_uncomplete_task($task_id, $user_id);
        }
    }

    /**
     * Map Member Tools checklist items to main plugin tasks
     *
     * @param string $checklist_type Checklist type
     * @param string $item_id Item ID
     * @return string|null Main plugin task ID or null
     */
    private function map_checklist_item_to_task($checklist_type, $item_id) {
        // Mapping between Member Tools items and main plugin tasks
        $mapping = array(
            'visa-application' => array(
                'passport' => 'v-passport',
                'application-form' => 'v-appointment',
                'cover-letter' => 'v-documents',
                'financial-proof' => 'v-income',
                'accommodation' => 'v-housing',
                'health-insurance' => 'v-insurance',
            ),
            'relocation' => array(
                'bank-statements' => 'v-finances',
            )
        );

        return $mapping[$checklist_type][$item_id] ?? null;
    }

    /**
     * Pre-fill Member Tools profile from main plugin data
     *
     * @param array $defaults Default profile values
     * @param int $user_id User ID
     * @return array Modified defaults
     */
    public function prefill_from_main_profile($defaults, $user_id) {
        if (!function_exists('fra_get_user_profile')) {
            return $defaults;
        }

        $main_profile = fra_get_user_profile($user_id);

        // Only prefill if main profile has data
        if (empty($main_profile['visa_type'])) {
            return $defaults;
        }

        // Map main plugin fields back to Member Tools
        $visa_map = array(
            'visitor' => 'visitor',
            'talent' => 'talent_passport',
            'student' => 'student',
            'family' => 'family',
            'retiree' => 'retiree',
            'other' => 'other'
        );

        if (!empty($main_profile['visa_type'])) {
            $defaults['visa_type'] = $visa_map[$main_profile['visa_type']] ?? 'visitor';
        }

        if (!empty($main_profile['target_move_date'])) {
            $defaults['target_move_date'] = $main_profile['target_move_date'];
        }

        if (!empty($main_profile['destination_region'])) {
            $defaults['intended_region'] = $main_profile['destination_region'];
        }

        return $defaults;
    }

    /**
     * Extend rate limits for members using Member Tools
     *
     * @param array $limits Current rate limits
     * @param int $user_id User ID
     * @return array Modified limits
     */
    public function extend_member_rate_limits($limits, $user_id) {
        // Members with active Member Tools get higher limits
        if ($this->user_has_member_tools_access($user_id)) {
            $limits['per_minute'] = max($limits['per_minute'], 60);
            $limits['per_day'] = max($limits['per_day'], 500);
            $limits['is_member'] = true;
        }

        return $limits;
    }

    /**
     * Check if user has Member Tools access
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function user_has_member_tools_access($user_id) {
        if (!$user_id) {
            return false;
        }

        // Check MemberPress
        if (class_exists('MeprUser')) {
            $mepr_user = new MeprUser($user_id);
            return $mepr_user->is_active();
        }

        // Fallback - check if they have Member Tools profile data
        global $wpdb;
        $table = $wpdb->prefix . 'framt_profiles';
        $has_profile = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        return $has_profile > 0;
    }

    /**
     * Mark main plugin onboarding complete when Member Tools profile created
     *
     * @param int $user_id User ID
     */
    public function mark_main_onboarding_complete($user_id) {
        if (!function_exists('fra_save_user_profile')) {
            return;
        }

        fra_save_user_profile(array('onboarding_complete' => true), $user_id);
    }

    /**
     * Use main plugin's API proxy for requests
     * This provides rate limiting and caching benefits
     *
     * @param string $message Message to send
     * @param string $context Optional context
     * @return array|WP_Error Response or error
     */
    public function proxy_api_request($message, $context = '') {
        if (function_exists('fra_proxy_query')) {
            return fra_proxy_query($message, $context);
        }

        // Fallback to direct API call if proxy not available
        return $this->direct_api_call($message, $context);
    }

    /**
     * Direct API call fallback
     *
     * @param string $message Message
     * @param string $context Context
     * @return array|WP_Error
     */
    private function direct_api_call( $message, $context = '' ) {
        $api_key = France_Relocation_Assistant::get_api_key();
        $model   = get_option( 'fra_api_model', 'claude-sonnet-4-20250514' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key not configured' );
        }

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => array(
                    array('role' => 'user', 'content' => $message)
                )
            ))
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown error');
        }

        return array(
            'response' => $body['content'][0]['text'] ?? '',
            'model' => $model,
            'usage' => $body['usage'] ?? null
        );
    }
}

// Initialize the bridge
add_action('plugins_loaded', function() {
    if (class_exists('France_Relocation_Assistant')) {
        FRAMT_Main_Plugin_Bridge::get_instance();
    }
}, 15);
