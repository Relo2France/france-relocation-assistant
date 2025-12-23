<?php
/**
 * Portal REST API
 *
 * Provides REST API endpoints for the React portal frontend.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Portal
 * @since       2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FRAMT_Portal_API
 *
 * REST API endpoints for the members portal.
 *
 * @since 2.0.0
 */
class FRAMT_Portal_API {

    /**
     * API namespace
     *
     * @var string
     */
    const NAMESPACE = 'fra-portal/v1';

    /**
     * Rate limiting constants
     */
    const RATE_LIMIT_CHAT_REQUESTS = 20;      // Max requests per minute for chat
    const RATE_LIMIT_CHAT_WINDOW   = 60;      // Window in seconds
    const RATE_LIMIT_AI_REQUESTS   = 10;      // Max AI/verification requests per minute
    const RATE_LIMIT_AI_WINDOW     = 60;      // Window in seconds
    const MAX_CHAT_HISTORY         = 100;     // Max chat messages to store
    const MAX_UPLOAD_SIZE          = 10485760; // 10MB in bytes

    /**
     * Cache duration constants (in seconds)
     */
    const CACHE_GLOSSARY_DURATION   = 3600;   // 1 hour
    const CACHE_CHECKLISTS_DURATION = 3600;   // 1 hour
    const CACHE_GUIDES_DURATION     = 1800;   // 30 minutes

    /**
     * Singleton instance
     *
     * @var FRAMT_Portal_API|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return FRAMT_Portal_API
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
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Check rate limit for a specific action
     *
     * @param int    $user_id     User ID.
     * @param string $action      Action identifier (e.g., 'chat', 'ai_verify').
     * @param int    $max_requests Maximum requests allowed.
     * @param int    $window       Time window in seconds.
     * @return bool|WP_Error True if allowed, WP_Error if rate limited.
     */
    private function check_rate_limit( int $user_id, string $action, int $max_requests, int $window ) {
        $transient_key   = "fra_rate_{$action}_{$user_id}";
        $request_count   = (int) get_transient( $transient_key );

        if ( $request_count >= $max_requests ) {
            $this->log_security_event( 'rate_limit_exceeded', $user_id, array(
                'action'       => $action,
                'count'        => $request_count,
                'max_requests' => $max_requests,
            ) );

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    'Rate limit exceeded. Maximum %d requests per %d seconds allowed.',
                    $max_requests,
                    $window
                ),
                array( 'status' => 429 )
            );
        }

        // Increment counter
        if ( false === $request_count ) {
            set_transient( $transient_key, 1, $window );
        } else {
            set_transient( $transient_key, $request_count + 1, $window );
        }

        return true;
    }

    /**
     * Log security-related events
     *
     * @param string $event_type Event type identifier.
     * @param int    $user_id    User ID (0 for anonymous).
     * @param array  $details    Additional event details.
     * @return void
     */
    private function log_security_event( string $event_type, int $user_id, array $details = array() ): void {
        $log_entry = array(
            'timestamp'  => current_time( 'mysql' ),
            'event_type' => $event_type,
            'user_id'    => $user_id,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
            'details'    => $details,
        );

        // Log to error log for monitoring
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[FRA Security] %s: User %d from %s - %s',
                $event_type,
                $user_id,
                $log_entry['ip_address'],
                wp_json_encode( $details )
            ) );
        }

        // Store in transient for recent events (last 100)
        $recent_events = get_transient( 'fra_security_events' ) ?: array();
        array_unshift( $recent_events, $log_entry );
        $recent_events = array_slice( $recent_events, 0, 100 );
        set_transient( 'fra_security_events', $recent_events, DAY_IN_SECONDS );
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address.
     */
    private function get_client_ip(): string {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // Handle comma-separated IPs (X-Forwarded-For)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip  = trim( $ips[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get cached data or execute callback to fetch fresh data
     *
     * @param string   $cache_key Cache key.
     * @param callable $callback  Callback to fetch data if not cached.
     * @param int      $duration  Cache duration in seconds.
     * @return mixed Cached or fresh data.
     */
    private function get_cached_data( string $cache_key, callable $callback, int $duration = 3600 ) {
        $cached = wp_cache_get( $cache_key, 'fra_portal' );

        if ( false !== $cached ) {
            return $cached;
        }

        // Try transient as fallback for persistent cache
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            // Also set in object cache for faster subsequent access
            wp_cache_set( $cache_key, $cached, 'fra_portal', $duration );
            return $cached;
        }

        // Fetch fresh data
        $data = $callback();

        // Cache in both layers
        wp_cache_set( $cache_key, $data, 'fra_portal', $duration );
        set_transient( $cache_key, $data, $duration );

        return $data;
    }

    /**
     * Clear cached data
     *
     * @param string $cache_key Cache key to clear.
     * @return void
     */
    private function clear_cache( string $cache_key ): void {
        wp_cache_delete( $cache_key, 'fra_portal' );
        delete_transient( $cache_key );
    }

    /**
     * Register REST API routes
     *
     * @return void
     */
    public function register_routes() {
        // Dashboard endpoint
        register_rest_route(
            self::NAMESPACE,
            '/dashboard',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_dashboard' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Welcome banner dismiss endpoint
        register_rest_route(
            self::NAMESPACE,
            '/welcome-banner/dismiss',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'dismiss_welcome_banner' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Projects endpoints
        register_rest_route(
            self::NAMESPACE,
            '/projects',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_projects' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_project' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_project' ),
                    'permission_callback' => array( $this, 'check_project_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_project' ),
                    'permission_callback' => array( $this, 'check_project_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_project' ),
                    'permission_callback' => array( $this, 'check_project_permission' ),
                ),
            )
        );

        // Tasks endpoints
        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/tasks',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_tasks' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_task' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/tasks/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_task' ),
                    'permission_callback' => array( $this, 'check_task_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_task' ),
                    'permission_callback' => array( $this, 'check_task_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_task' ),
                    'permission_callback' => array( $this, 'check_task_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/tasks/(?P<id>\d+)/status',
            array(
                'methods'             => 'PATCH',
                'callback'            => array( $this, 'update_task_status' ),
                'permission_callback' => array( $this, 'check_task_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/tasks/reorder',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'reorder_tasks' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Stages endpoint
        register_rest_route(
            self::NAMESPACE,
            '/stages/(?P<visa_type>[a-z]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_stages' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Activity/Timeline endpoint
        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/activity',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_activity' ),
                'permission_callback' => array( $this, 'check_project_permission_by_param' ),
            )
        );

        // User profile endpoint
        register_rest_route(
            self::NAMESPACE,
            '/me',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_current_user' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_current_user' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
            )
        );

        // User settings endpoint
        register_rest_route(
            self::NAMESPACE,
            '/me/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_user_settings' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_user_settings' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
            )
        );

        // Files endpoints
        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/files',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_files' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'upload_file' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/files/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_file' ),
                    'permission_callback' => array( $this, 'check_file_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_file' ),
                    'permission_callback' => array( $this, 'check_file_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/files/(?P<id>\d+)/download',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'download_file' ),
                'permission_callback' => array( $this, 'check_file_permission' ),
            )
        );

        // Notes endpoints
        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/notes',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_notes' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_note' ),
                    'permission_callback' => array( $this, 'check_project_permission_by_param' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/notes/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_note' ),
                    'permission_callback' => array( $this, 'check_note_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_note' ),
                    'permission_callback' => array( $this, 'check_note_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_note' ),
                    'permission_callback' => array( $this, 'check_note_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/notes/(?P<id>\d+)/pin',
            array(
                'methods'             => 'PATCH',
                'callback'            => array( $this, 'toggle_note_pin' ),
                'permission_callback' => array( $this, 'check_note_permission' ),
            )
        );

        // ============================================
        // Profile endpoints (full 30+ fields)
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/profile',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_member_profile' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_member_profile' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/profile/completion',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_profile_completion' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Account deletion endpoint
        register_rest_route(
            self::NAMESPACE,
            '/account/delete',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'delete_account' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Family Members endpoints (paid add-on ready)
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/family',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_family_members' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_family_member' ),
                    'permission_callback' => array( $this, 'check_family_feature_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/family/(?P<member_id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_family_member' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_family_member' ),
                    'permission_callback' => array( $this, 'check_family_feature_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_family_member' ),
                    'permission_callback' => array( $this, 'check_family_feature_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/family/feature-status',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_family_feature_status' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Checklists endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/checklists',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_checklists' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/checklists/(?P<type>[a-z-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_checklist' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/checklists/(?P<type>[a-z-]+)/items/(?P<item_id>[a-z-]+)',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'update_checklist_item' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // Task-specific checklists
        register_rest_route(
            self::NAMESPACE,
            '/tasks/(?P<task_id>\d+)/checklist',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_task_checklist' ),
                    'permission_callback' => array( $this, 'check_task_permission_by_param' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'add_task_checklist_item' ),
                    'permission_callback' => array( $this, 'check_task_permission_by_param' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/tasks/(?P<task_id>\d+)/checklist/(?P<item_id>[a-z0-9-]+)',
            array(
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_task_checklist_item' ),
                    'permission_callback' => array( $this, 'check_task_permission_by_param' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_task_checklist_item' ),
                    'permission_callback' => array( $this, 'check_task_permission_by_param' ),
                ),
            )
        );

        // ============================================
        // Document Generator endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/documents/generator/types',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_document_types' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/documents/generator/preview',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'preview_document' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/documents/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'generate_document' ),
                'permission_callback' => array( $this, 'check_project_permission_by_param' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/documents/generated',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_generated_documents' ),
                'permission_callback' => array( $this, 'check_project_permission_by_param' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/documents/generated/(?P<id>\d+)/download',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'download_generated_document' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Glossary endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/glossary',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_glossary' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/glossary/search',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'search_glossary' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/glossary/category/(?P<category_id>[a-z-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_glossary_category' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // AI Verification endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/verify',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'verify_document' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/verify',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'verify_project_document' ),
                'permission_callback' => array( $this, 'check_project_permission_by_param' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/projects/(?P<project_id>\d+)/verify/history',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_verification_history' ),
                'permission_callback' => array( $this, 'check_project_permission_by_param' ),
            )
        );

        // ============================================
        // Guides endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/guides',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_guides' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/guides/(?P<type>[a-z-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_guide' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/guides/(?P<type>[a-z-]+)/personalized',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_personalized_guide' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/guides/(?P<type>[a-z-]+)/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'generate_ai_guide' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Chat/Knowledge Base endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/chat',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'send_chat_message' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/chat/categories',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_chat_categories' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/chat/search',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'search_chat_topics' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Membership endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/membership',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_membership_info' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/subscriptions',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_subscriptions' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/payments',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_payments' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/subscriptions/(?P<id>\d+)/cancel',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'cancel_subscription' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/subscriptions/(?P<id>\d+)/suspend',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'suspend_subscription' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/subscriptions/(?P<id>\d+)/resume',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'resume_subscription' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/membership/upgrade-options',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_upgrade_options' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // France Research Tool endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/research/regions',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_france_regions' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/regions/(?P<code>[0-9A-Za-z]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_france_region' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/departments',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_france_departments' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/departments/(?P<code>[0-9A-Za-z]+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_france_department' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/communes/search',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'search_communes' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/report/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'generate_research_report' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/report/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_research_report' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/report/(?P<id>\d+)/download',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'download_research_report' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // HTML report view (styled, printable)
        register_rest_route(
            self::NAMESPACE,
            '/research/report/(?P<id>\d+)/html',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'view_research_report_html' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/report/(?P<id>\d+)/save',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'save_report_to_documents' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/research/saved',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_saved_research_reports' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );

        // ============================================
        // Support Ticket endpoints
        // ============================================
        register_rest_route(
            self::NAMESPACE,
            '/support/tickets',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_support_tickets' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_support_ticket' ),
                    'permission_callback' => array( $this, 'check_member_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/support/tickets/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_support_ticket' ),
                    'permission_callback' => array( $this, 'check_support_ticket_permission' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_support_ticket' ),
                    'permission_callback' => array( $this, 'check_support_ticket_permission' ),
                ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/support/tickets/(?P<id>\d+)/reply',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'reply_to_support_ticket' ),
                'permission_callback' => array( $this, 'check_support_ticket_permission' ),
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/support/unread-count',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_support_unread_count' ),
                'permission_callback' => array( $this, 'check_member_permission' ),
            )
        );
    }

    /**
     * Check if current user is a member
     *
     * @return bool|WP_Error
     */
    public function check_member_permission() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_not_logged_in',
                'You must be logged in to access this resource.',
                array( 'status' => 401 )
            );
        }

        // Check if user has active membership (optional - can be customized)
        $user_id = get_current_user_id();

        // For now, any logged-in user can access the portal
        // Add MemberPress checks here if needed
        return true;
    }

    /**
     * Check if user has permission to access a project
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_project_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $project_id = (int) $request->get_param( 'id' );
        $project    = new FRAMT_Project( $project_id );
        $user_id    = get_current_user_id();

        if ( ! $project->id ) {
            return new WP_Error(
                'rest_project_not_found',
                'Project not found.',
                array( 'status' => 404 )
            );
        }

        if ( (int) $project->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
            // Log unauthorized access attempt
            $this->log_security_event( 'unauthorized_project_access', $user_id, array(
                'project_id'    => $project_id,
                'project_owner' => $project->user_id,
            ) );

            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this project.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check project permission by project_id parameter
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_project_permission_by_param( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $project_id = $request->get_param( 'project_id' );
        $project    = new FRAMT_Project( $project_id );

        if ( ! $project->id ) {
            return new WP_Error(
                'rest_project_not_found',
                'Project not found.',
                array( 'status' => 404 )
            );
        }

        if ( $project->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this project.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check if user has permission to access a task
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_task_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $task_id = $request->get_param( 'id' );
        $task    = new FRAMT_Task( $task_id );

        if ( ! $task->id ) {
            return new WP_Error(
                'rest_task_not_found',
                'Task not found.',
                array( 'status' => 404 )
            );
        }

        if ( $task->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this task.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check task permission by task_id parameter
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_task_permission_by_param( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $task_id = $request->get_param( 'task_id' );
        $task    = new FRAMT_Task( $task_id );

        if ( ! $task->id ) {
            return new WP_Error(
                'rest_task_not_found',
                'Task not found.',
                array( 'status' => 404 )
            );
        }

        if ( $task->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this task.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get dashboard data
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_dashboard( $request ) {
        $user_id = get_current_user_id();
        $project = FRAMT_Project::get_or_create( $user_id );

        // Get user's visa type from their profile
        $profile_visa_type = get_user_meta( $user_id, 'fra_visa_type', true );
        $visa_type_labels  = array(
            'undecided'       => 'Undecided / Need help choosing',
            'visitor'         => 'Visitor Visa (VLS-TS Visiteur)',
            'talent_passport' => 'Talent Passport',
            'employee'        => 'Employee Visa',
            'entrepreneur'    => 'Entrepreneur Visa',
            'student'         => 'Student Visa',
            'family'          => 'Family Reunification',
            'spouse_french'   => 'Spouse of French National',
            'retiree'         => 'Retiree Visa',
        );

        // Get welcome banner settings
        $portal_settings = FRAMT_Portal_Settings::get_settings();
        $banner_dismissed = get_user_meta( $user_id, 'framt_welcome_banner_dismissed', true );

        $welcome_banner = null;
        if ( ! empty( $portal_settings['welcome_banner_enabled'] ) && ! $banner_dismissed ) {
            $welcome_banner = array(
                'title'        => $portal_settings['welcome_banner_title'] ?? '',
                'message'      => $portal_settings['welcome_banner_message'] ?? '',
                'bg_color'     => $portal_settings['welcome_banner_bg_color'] ?? '#ecfdf5',
                'border_color' => $portal_settings['welcome_banner_border_color'] ?? '#10b981',
            );
        }

        $response = array(
            'project'              => $project->to_array(),
            'stages'               => $project->get_stage_progress(),
            'task_stats'           => $project->get_task_stats(),
            'profile_visa_type'    => $profile_visa_type ?: null,
            'profile_visa_label'   => ! empty( $profile_visa_type ) && isset( $visa_type_labels[ $profile_visa_type ] )
                ? $visa_type_labels[ $profile_visa_type ]
                : null,
            'welcome_banner'       => $welcome_banner,
            'upcoming_tasks'       => array_map(
                function( $task ) {
                    return $task->to_array();
                },
                FRAMT_Task::get_upcoming( $project->id, 14, 5 )
            ),
            'overdue_tasks'        => array_map(
                function( $task ) {
                    return $task->to_array();
                },
                FRAMT_Task::get_overdue( $project->id )
            ),
            'recent_activity'      => array_map(
                function( $activity ) {
                    return $activity->to_array();
                },
                FRAMT_Activity::get_by_project( $project->id, array( 'limit' => 10 ) )
            ),
        );

        return rest_ensure_response( $response );
    }

    /**
     * Dismiss welcome banner for the current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function dismiss_welcome_banner( $request ) {
        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'framt_welcome_banner_dismissed', true );

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Get all projects for current user
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_projects( $request ) {
        $user_id  = get_current_user_id();
        $projects = FRAMT_Project::get_all_by_user( $user_id );

        return rest_ensure_response(
            array_map(
                function( $project ) {
                    return $project->to_array();
                },
                $projects
            )
        );
    }

    /**
     * Get a single project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_project( $request ) {
        $project_id = $request->get_param( 'id' );
        $project    = new FRAMT_Project( $project_id );

        $response = $project->to_array();
        $response['stages']     = $project->get_stage_progress();
        $response['task_stats'] = $project->get_task_stats();

        return rest_ensure_response( $response );
    }

    /**
     * Create a new project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_project( $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        $project              = new FRAMT_Project();
        $project->user_id     = $user_id;
        $project->title       = sanitize_text_field( $params['title'] ?? 'My France Relocation' );
        $project->description = sanitize_textarea_field( $params['description'] ?? '' );
        $project->visa_type   = sanitize_key( $params['visa_type'] ?? 'visitor' );

        if ( isset( $params['target_move_date'] ) ) {
            $project->target_move_date = sanitize_text_field( $params['target_move_date'] );
        }

        $result = $project->save();

        if ( ! $result ) {
            return new WP_Error(
                'rest_project_create_failed',
                'Failed to create project.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $project->to_array() );
    }

    /**
     * Update a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_project( $request ) {
        $project_id = $request->get_param( 'id' );
        $project    = new FRAMT_Project( $project_id );
        $params     = $request->get_json_params();

        if ( isset( $params['title'] ) ) {
            $project->title = sanitize_text_field( $params['title'] );
        }
        if ( isset( $params['description'] ) ) {
            $project->description = sanitize_textarea_field( $params['description'] );
        }
        if ( isset( $params['visa_type'] ) ) {
            $project->visa_type = sanitize_key( $params['visa_type'] );
        }
        if ( isset( $params['current_stage'] ) ) {
            $project->current_stage = sanitize_key( $params['current_stage'] );
        }
        if ( isset( $params['target_move_date'] ) ) {
            $project->target_move_date = sanitize_text_field( $params['target_move_date'] );
        }
        if ( isset( $params['status'] ) ) {
            $project->status = sanitize_key( $params['status'] );
        }
        if ( isset( $params['settings'] ) && is_array( $params['settings'] ) ) {
            $project->settings = $params['settings'];
        }

        $result = $project->save();

        if ( ! $result ) {
            return new WP_Error(
                'rest_project_update_failed',
                'Failed to update project.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $project->to_array() );
    }

    /**
     * Delete a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_project( $request ) {
        $project_id = $request->get_param( 'id' );
        $project    = new FRAMT_Project( $project_id );

        $result = $project->delete();

        if ( ! $result ) {
            return new WP_Error(
                'rest_project_delete_failed',
                'Failed to delete project.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Get tasks for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_tasks( $request ) {
        $project_id = $request->get_param( 'project_id' );

        $args = array(
            'stage'     => $request->get_param( 'stage' ),
            'status'    => $request->get_param( 'status' ),
            'task_type' => $request->get_param( 'task_type' ),
        );

        $tasks = FRAMT_Task::get_by_project( $project_id, $args );

        return rest_ensure_response(
            array_map(
                function( $task ) {
                    return $task->to_array();
                },
                $tasks
            )
        );
    }

    /**
     * Get a single task
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_task( $request ) {
        $task_id = $request->get_param( 'id' );
        $task    = new FRAMT_Task( $task_id );

        $response             = $task->to_array();
        $response['subtasks'] = array_map(
            function( $subtask ) {
                return $subtask->to_array();
            },
            $task->get_subtasks()
        );

        return rest_ensure_response( $response );
    }

    /**
     * Create a new task
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_task( $request ) {
        $project_id = $request->get_param( 'project_id' );
        $params     = $request->get_json_params();

        $task             = new FRAMT_Task();
        $task->project_id = $project_id;
        $task->user_id    = get_current_user_id();
        $task->title      = sanitize_text_field( $params['title'] ?? '' );

        if ( empty( $task->title ) ) {
            return new WP_Error(
                'rest_task_title_required',
                'Task title is required.',
                array( 'status' => 400 )
            );
        }

        if ( isset( $params['description'] ) ) {
            $task->description = wp_kses_post( $params['description'] );
        }
        if ( isset( $params['stage'] ) ) {
            $task->stage = sanitize_key( $params['stage'] );
        }
        if ( isset( $params['status'] ) ) {
            $task->status = sanitize_key( $params['status'] );
        }
        if ( isset( $params['priority'] ) ) {
            $task->priority = sanitize_key( $params['priority'] );
        }
        if ( isset( $params['task_type'] ) ) {
            $task->task_type = sanitize_key( $params['task_type'] );
        }
        if ( isset( $params['due_date'] ) ) {
            $task->due_date = sanitize_text_field( $params['due_date'] );
        }
        if ( isset( $params['parent_task_id'] ) ) {
            $task->parent_task_id = (int) $params['parent_task_id'];
        }

        $result = $task->save();

        if ( ! $result ) {
            return new WP_Error(
                'rest_task_create_failed',
                'Failed to create task.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $task->to_array() );
    }

    /**
     * Update a task
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_task( $request ) {
        $task_id = $request->get_param( 'id' );
        $task    = new FRAMT_Task( $task_id );
        $params  = $request->get_json_params();

        if ( isset( $params['title'] ) ) {
            $task->title = sanitize_text_field( $params['title'] );
        }
        if ( isset( $params['description'] ) ) {
            $task->description = wp_kses_post( $params['description'] );
        }
        if ( isset( $params['stage'] ) ) {
            $task->stage = sanitize_key( $params['stage'] );
        }
        if ( isset( $params['status'] ) ) {
            $task->status = sanitize_key( $params['status'] );
            if ( 'done' === $task->status && ! $task->completed_at ) {
                $task->completed_at = current_time( 'mysql' );
            } elseif ( 'done' !== $task->status ) {
                $task->completed_at = null;
            }
        }
        if ( isset( $params['priority'] ) ) {
            $task->priority = sanitize_key( $params['priority'] );
        }
        if ( isset( $params['task_type'] ) ) {
            $task->task_type = sanitize_key( $params['task_type'] );
        }
        if ( isset( $params['due_date'] ) ) {
            $task->due_date = $params['due_date'] ? sanitize_text_field( $params['due_date'] ) : null;
        }
        if ( isset( $params['portal_visible'] ) ) {
            $task->portal_visible = (bool) $params['portal_visible'];
        }
        if ( isset( $params['sort_order'] ) ) {
            $task->sort_order = (int) $params['sort_order'];
        }

        $result = $task->save();

        if ( ! $result ) {
            return new WP_Error(
                'rest_task_update_failed',
                'Failed to update task.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $task->to_array() );
    }

    /**
     * Update task status (quick update)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_task_status( $request ) {
        $task_id = $request->get_param( 'id' );
        $task    = new FRAMT_Task( $task_id );
        $params  = $request->get_json_params();

        if ( ! isset( $params['status'] ) ) {
            return new WP_Error(
                'rest_status_required',
                'Status is required.',
                array( 'status' => 400 )
            );
        }

        $result = $task->update_status( sanitize_key( $params['status'] ) );

        if ( ! $result ) {
            return new WP_Error(
                'rest_task_status_update_failed',
                'Failed to update task status.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $task->to_array() );
    }

    /**
     * Delete a task
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_task( $request ) {
        $task_id = $request->get_param( 'id' );
        $task    = new FRAMT_Task( $task_id );

        $result = $task->delete();

        if ( ! $result ) {
            return new WP_Error(
                'rest_task_delete_failed',
                'Failed to delete task.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Reorder tasks
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function reorder_tasks( $request ) {
        $params = $request->get_json_params();
        $order  = $params['order'] ?? array();

        if ( ! empty( $order ) && is_array( $order ) ) {
            FRAMT_Task::bulk_update_order( $order );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * Get stages for a visa type
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_stages( $request ) {
        $visa_type = $request->get_param( 'visa_type' );
        $stages    = FRAMT_Stage::get_by_visa_type( $visa_type );

        return rest_ensure_response(
            array_map(
                function( $stage ) {
                    return $stage->to_array();
                },
                $stages
            )
        );
    }

    /**
     * Get activity for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_activity( $request ) {
        $project_id = $request->get_param( 'project_id' );
        $grouped    = $request->get_param( 'grouped' ) === 'true';

        $args = array(
            'limit'  => $request->get_param( 'limit' ) ?: 50,
            'offset' => $request->get_param( 'offset' ) ?: 0,
        );

        $activities = FRAMT_Activity::get_by_project( $project_id, $args );

        if ( $grouped ) {
            return rest_ensure_response( FRAMT_Activity::group_by_date( $activities ) );
        }

        return rest_ensure_response(
            array_map(
                function( $activity ) {
                    return $activity->to_array();
                },
                $activities
            )
        );
    }

    /**
     * Get current user data
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_current_user( $request ) {
        $user = wp_get_current_user();

        $response = array(
            'id'           => $user->ID,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
            'roles'        => $user->roles,
            'is_admin'     => current_user_can( 'manage_options' ),
        );

        // Add membership info if MemberPress is active
        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user                     = new MeprUser( $user->ID );
            $response['active_memberships'] = $mepr_user->active_product_subscriptions();
            $response['is_member']          = ! empty( $response['active_memberships'] );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Update current user profile
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_current_user( $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        $user_data = array( 'ID' => $user_id );

        // Allowed fields to update
        if ( isset( $params['first_name'] ) ) {
            $user_data['first_name'] = sanitize_text_field( $params['first_name'] );
        }
        if ( isset( $params['last_name'] ) ) {
            $user_data['last_name'] = sanitize_text_field( $params['last_name'] );
        }
        if ( isset( $params['display_name'] ) ) {
            $user_data['display_name'] = sanitize_text_field( $params['display_name'] );
        }

        // Update user
        $result = wp_update_user( $user_data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Return updated user data
        return $this->get_current_user( $request );
    }

    /**
     * Get user settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_user_settings( $request ) {
        $user_id = get_current_user_id();

        $defaults = array(
            'email_notifications' => true,
            'task_reminders'      => true,
            'weekly_digest'       => false,
            'language'            => 'en',
            'timezone'            => wp_timezone_string(),
            'date_format'         => 'M j, Y',
            'menu_order'          => null, // Custom menu order (null = use defaults)
        );

        $settings = get_user_meta( $user_id, 'fra_portal_settings', true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return rest_ensure_response( array_merge( $defaults, $settings ) );
    }

    /**
     * Update user settings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_user_settings( $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        // Get existing settings
        $settings = get_user_meta( $user_id, 'fra_portal_settings', true );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        // Allowed settings to update
        $allowed = array(
            'email_notifications',
            'task_reminders',
            'weekly_digest',
            'language',
            'timezone',
            'date_format',
        );

        foreach ( $allowed as $key ) {
            if ( isset( $params[ $key ] ) ) {
                if ( is_bool( $params[ $key ] ) || in_array( $key, array( 'email_notifications', 'task_reminders', 'weekly_digest' ), true ) ) {
                    $settings[ $key ] = (bool) $params[ $key ];
                } else {
                    $settings[ $key ] = sanitize_text_field( $params[ $key ] );
                }
            }
        }

        // Handle menu_order separately (it's an object/array)
        if ( isset( $params['menu_order'] ) && is_array( $params['menu_order'] ) ) {
            $menu_order = array();
            $allowed_sections = array( 'project', 'resources', 'account' );

            foreach ( $allowed_sections as $section ) {
                if ( isset( $params['menu_order'][ $section ] ) && is_array( $params['menu_order'][ $section ] ) ) {
                    // Sanitize each item ID in the section
                    $menu_order[ $section ] = array_map( 'sanitize_key', $params['menu_order'][ $section ] );
                }
            }

            // Only save if we have valid data
            if ( ! empty( $menu_order ) ) {
                $settings['menu_order'] = $menu_order;
            }
        }

        // Save settings
        update_user_meta( $user_id, 'fra_portal_settings', $settings );

        // Return updated settings
        return $this->get_user_settings( $request );
    }

    /**
     * Check if user has permission to access a file
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_file_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $file_id = $request->get_param( 'id' );
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'files' );
        $file  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        if ( ! $file ) {
            return new WP_Error(
                'rest_file_not_found',
                'File not found.',
                array( 'status' => 404 )
            );
        }

        if ( (int) $file->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this file.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get files for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_files( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $category   = $request->get_param( 'category' );

        $table  = FRAMT_Portal_Schema::get_table( 'files' );
        $where  = array( 'project_id = %d' );
        $values = array( $project_id );

        if ( $category ) {
            $where[]  = 'category = %s';
            $values[] = $category;
        }

        $sql   = $wpdb->prepare(
            "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC",
            $values
        );
        $files = $wpdb->get_results( $sql );

        $response = array();
        foreach ( $files as $file ) {
            $response[] = $this->format_file_response( $file );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get a single file
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_file( $request ) {
        global $wpdb;

        $file_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'files' );
        $file    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        return rest_ensure_response( $this->format_file_response( $file ) );
    }

    /**
     * Upload a file
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function upload_file( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $user_id    = get_current_user_id();
        $files      = $request->get_file_params();

        if ( empty( $files['file'] ) ) {
            return new WP_Error(
                'rest_no_file',
                'No file was uploaded.',
                array( 'status' => 400 )
            );
        }

        $uploaded_file = $files['file'];

        // Validate file type
        $allowed_types = array(
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        );

        $finfo     = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type = finfo_file( $finfo, $uploaded_file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! in_array( $mime_type, $allowed_types, true ) ) {
            return new WP_Error(
                'rest_invalid_file_type',
                'This file type is not allowed.',
                array( 'status' => 400 )
            );
        }

        // Check file size (max 10MB)
        if ( $uploaded_file['size'] > 10 * 1024 * 1024 ) {
            return new WP_Error(
                'rest_file_too_large',
                'File size must be less than 10MB.',
                array( 'status' => 400 )
            );
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $portal_dir = $upload_dir['basedir'] . '/fra-portal/' . $user_id;

        if ( ! file_exists( $portal_dir ) ) {
            wp_mkdir_p( $portal_dir );
            // Add .htaccess for security
            file_put_contents( $portal_dir . '/.htaccess', 'deny from all' );
        }

        // Generate unique filename
        $ext      = pathinfo( $uploaded_file['name'], PATHINFO_EXTENSION );
        $filename = wp_unique_filename( $portal_dir, sanitize_file_name( $uploaded_file['name'] ) );
        $filepath = $portal_dir . '/' . $filename;

        // Move file
        if ( ! move_uploaded_file( $uploaded_file['tmp_name'], $filepath ) ) {
            return new WP_Error(
                'rest_upload_failed',
                'Failed to save the uploaded file.',
                array( 'status' => 500 )
            );
        }

        // Get category from request params
        $params   = $request->get_params();
        $category = sanitize_key( $params['category'] ?? 'upload' );
        $task_id  = isset( $params['task_id'] ) ? (int) $params['task_id'] : null;

        // Get file type from extension
        $file_type = $this->get_file_type_from_extension( $ext );

        // Insert into database
        $table = FRAMT_Portal_Schema::get_table( 'files' );
        $wpdb->insert(
            $table,
            array(
                'project_id'    => $project_id,
                'user_id'       => $user_id,
                'task_id'       => $task_id,
                'filename'      => $filename,
                'original_name' => sanitize_file_name( $uploaded_file['name'] ),
                'file_type'     => $file_type,
                'file_size'     => $uploaded_file['size'],
                'mime_type'     => $mime_type,
                'file_path'     => $filepath,
                'category'      => $category,
                'visibility'    => 'private',
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        $file_id = $wpdb->insert_id;

        // Log activity
        FRAMT_Activity::log( $project_id, $user_id, 'file_uploaded', 'file', $file_id, $uploaded_file['name'] );

        // Get the inserted file
        $file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        return rest_ensure_response( $this->format_file_response( $file ) );
    }

    /**
     * Delete a file
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_file( $request ) {
        global $wpdb;

        $file_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'files' );
        $file    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        // Delete physical file
        if ( $file->file_path && file_exists( $file->file_path ) ) {
            unlink( $file->file_path );
        }

        // Log activity
        FRAMT_Activity::log( $file->project_id, get_current_user_id(), 'file_deleted', 'file', $file_id, $file->original_name );

        // Delete from database
        $wpdb->delete( $table, array( 'id' => $file_id ), array( '%d' ) );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Download a file
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function download_file( $request ) {
        global $wpdb;

        $file_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'files' );
        $file    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        if ( ! $file->file_path || ! file_exists( $file->file_path ) ) {
            return new WP_Error(
                'rest_file_not_found',
                'File not found on server.',
                array( 'status' => 404 )
            );
        }

        // Return file URL (for download handling on frontend)
        // In a real implementation, you'd generate a signed URL or serve the file directly
        return rest_ensure_response(
            array(
                'download_url' => $this->get_secure_download_url( $file ),
                'filename'     => $file->original_name,
                'mime_type'    => $file->mime_type,
            )
        );
    }

    /**
     * Format file for API response
     *
     * @param object $file Database row
     * @return array
     */
    private function format_file_response( $file ) {
        $metadata = $file->metadata ? json_decode( $file->metadata, true ) : array();

        return array(
            'id'            => (int) $file->id,
            'project_id'    => (int) $file->project_id,
            'user_id'       => (int) $file->user_id,
            'task_id'       => $file->task_id ? (int) $file->task_id : null,
            'filename'      => $file->filename,
            'original_name' => $file->original_name,
            'file_type'     => $file->file_type,
            'file_size'     => (int) $file->file_size,
            'file_size_formatted' => size_format( $file->file_size ),
            'mime_type'     => $file->mime_type,
            'category'      => $file->category,
            'category_label' => $this->get_category_label( $file->category ),
            'visibility'    => $file->visibility,
            'thumbnail_url' => $this->get_file_thumbnail( $file ),
            'preview_url'   => $this->get_file_preview_url( $file ),
            'metadata'      => $metadata,
            'created_at'    => $file->created_at,
            'updated_at'    => $file->updated_at,
        );
    }

    /**
     * Get file type from extension
     *
     * @param string $ext File extension
     * @return string File type
     */
    private function get_file_type_from_extension( $ext ) {
        $types = array(
            'pdf'  => 'pdf',
            'doc'  => 'document',
            'docx' => 'document',
            'xls'  => 'spreadsheet',
            'xlsx' => 'spreadsheet',
            'jpg'  => 'image',
            'jpeg' => 'image',
            'png'  => 'image',
            'gif'  => 'image',
            'txt'  => 'text',
        );

        return $types[ strtolower( $ext ) ] ?? 'other';
    }

    /**
     * Get category label
     *
     * @param string $category Category key
     * @return string Category label
     */
    private function get_category_label( $category ) {
        $labels = array(
            'upload'    => 'Uploaded',
            'generated' => 'Generated',
            'template'  => 'Template',
            'passport'  => 'Passport',
            'visa'      => 'Visa Documents',
            'financial' => 'Financial',
            'housing'   => 'Housing',
            'medical'   => 'Medical',
            'other'     => 'Other',
        );

        return $labels[ $category ] ?? ucfirst( $category );
    }

    /**
     * Get file thumbnail URL
     *
     * @param object $file File object
     * @return string|null Thumbnail URL
     */
    private function get_file_thumbnail( $file ) {
        // For images, return the file itself (in production, you'd generate thumbnails)
        if ( in_array( $file->file_type, array( 'image' ), true ) ) {
            return $this->get_file_preview_url( $file );
        }

        // For other types, return a placeholder based on type
        return null;
    }

    /**
     * Get file preview URL
     *
     * @param object $file File object
     * @return string Preview URL
     */
    private function get_file_preview_url( $file ) {
        // Generate a secure preview URL
        $nonce = wp_create_nonce( 'fra_file_preview_' . $file->id );
        return add_query_arg(
            array(
                'action'  => 'fra_preview_file',
                'file_id' => $file->id,
                'nonce'   => $nonce,
            ),
            admin_url( 'admin-ajax.php' )
        );
    }

    /**
     * Get secure download URL
     *
     * @param object $file File object
     * @return string Download URL
     */
    private function get_secure_download_url( $file ) {
        $nonce = wp_create_nonce( 'fra_file_download_' . $file->id );
        return add_query_arg(
            array(
                'action'  => 'fra_download_file',
                'file_id' => $file->id,
                'nonce'   => $nonce,
            ),
            admin_url( 'admin-ajax.php' )
        );
    }

    // =========================================================================
    // Notes Methods
    // =========================================================================

    /**
     * Check if user has permission to access a note
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_note_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        global $wpdb;
        $note_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'notes' );
        $note    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        if ( ! $note ) {
            return new WP_Error(
                'rest_note_not_found',
                'Note not found.',
                array( 'status' => 404 )
            );
        }

        if ( (int) $note->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this note.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get notes for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_notes( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $task_id    = $request->get_param( 'task_id' );
        $pinned     = $request->get_param( 'pinned' );
        $table      = FRAMT_Portal_Schema::get_table( 'notes' );

        $where = array( 'project_id = %d' );
        $args  = array( $project_id );

        if ( $task_id ) {
            $where[] = 'task_id = %d';
            $args[]  = $task_id;
        }

        if ( $pinned === 'true' || $pinned === '1' ) {
            $where[] = 'is_pinned = 1';
        }

        $where_clause = implode( ' AND ', $where );
        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY is_pinned DESC, created_at DESC";

        $notes = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

        $formatted = array_map( array( $this, 'format_note_response' ), $notes );

        return rest_ensure_response( $formatted );
    }

    /**
     * Get single note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_note( $request ) {
        global $wpdb;

        $note_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'notes' );
        $note    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        if ( ! $note ) {
            return new WP_Error(
                'rest_note_not_found',
                'Note not found.',
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( $this->format_note_response( $note ) );
    }

    /**
     * Create a new note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_note( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $content    = $request->get_param( 'content' );
        $task_id    = $request->get_param( 'task_id' );
        $visibility = $request->get_param( 'visibility' ) ?: 'private';

        if ( empty( $content ) ) {
            return new WP_Error(
                'rest_invalid_note',
                'Note content is required.',
                array( 'status' => 400 )
            );
        }

        $table = FRAMT_Portal_Schema::get_table( 'notes' );

        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => $project_id,
                'user_id'    => get_current_user_id(),
                'task_id'    => $task_id ?: null,
                'content'    => wp_kses_post( $content ),
                'visibility' => $visibility,
                'is_pinned'  => 0,
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'rest_note_create_failed',
                'Failed to create note.',
                array( 'status' => 500 )
            );
        }

        $note_id = $wpdb->insert_id;
        $note    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        // Log activity
        FRAMT_Activity::log(
            $project_id,
            get_current_user_id(),
            'note_created',
            'note',
            $note_id,
            'Added a new note'
        );

        return rest_ensure_response( $this->format_note_response( $note ) );
    }

    /**
     * Update a note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_note( $request ) {
        global $wpdb;

        $note_id    = $request->get_param( 'id' );
        $content    = $request->get_param( 'content' );
        $visibility = $request->get_param( 'visibility' );

        $table = FRAMT_Portal_Schema::get_table( 'notes' );
        $note  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        $update_data   = array( 'updated_at' => current_time( 'mysql' ) );
        $update_format = array( '%s' );

        if ( $content !== null ) {
            $update_data['content'] = wp_kses_post( $content );
            $update_format[]        = '%s';
        }

        if ( $visibility !== null ) {
            $update_data['visibility'] = $visibility;
            $update_format[]           = '%s';
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $note_id ),
            $update_format,
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'rest_note_update_failed',
                'Failed to update note.',
                array( 'status' => 500 )
            );
        }

        $note = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        return rest_ensure_response( $this->format_note_response( $note ) );
    }

    /**
     * Delete a note
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_note( $request ) {
        global $wpdb;

        $note_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'notes' );

        $result = $wpdb->delete( $table, array( 'id' => $note_id ), array( '%d' ) );

        if ( false === $result ) {
            return new WP_Error(
                'rest_note_delete_failed',
                'Failed to delete note.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Toggle note pinned status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function toggle_note_pin( $request ) {
        global $wpdb;

        $note_id = $request->get_param( 'id' );
        $table   = FRAMT_Portal_Schema::get_table( 'notes' );
        $note    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        $new_pinned = $note->is_pinned ? 0 : 1;

        $result = $wpdb->update(
            $table,
            array(
                'is_pinned'  => $new_pinned,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $note_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        if ( false === $result ) {
            return new WP_Error(
                'rest_note_pin_failed',
                'Failed to update pin status.',
                array( 'status' => 500 )
            );
        }

        $note = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $note_id ) );

        return rest_ensure_response( $this->format_note_response( $note ) );
    }

    /**
     * Format note response
     *
     * @param object $note Note object
     * @return array Formatted note data
     */
    private function format_note_response( $note ) {
        $user = get_userdata( $note->user_id );

        return array(
            'id'             => (int) $note->id,
            'project_id'     => (int) $note->project_id,
            'user_id'        => (int) $note->user_id,
            'user_name'      => $user ? $user->display_name : 'Unknown',
            'user_avatar'    => $user ? get_avatar_url( $user->ID, array( 'size' => 48 ) ) : '',
            'task_id'        => $note->task_id ? (int) $note->task_id : null,
            'content'        => $note->content,
            'is_pinned'      => (bool) $note->is_pinned,
            'visibility'     => $note->visibility,
            'created_at'     => $note->created_at,
            'updated_at'     => $note->updated_at,
            'relative_time'  => human_time_diff( strtotime( $note->created_at ), current_time( 'timestamp' ) ) . ' ago',
        );
    }

    // =========================================================================
    // Profile Methods (Full 30+ fields for visa applications)
    // =========================================================================

    /**
     * Get member profile with all visa application fields
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_member_profile( $request ) {
        $user_id = get_current_user_id();
        $user    = get_userdata( $user_id );

        // Get all profile meta - using frontend field names
        $profile = array(
            // Personal Information
            'legal_first_name'      => get_user_meta( $user_id, 'fra_legal_first_name', true ),
            'legal_middle_name'     => get_user_meta( $user_id, 'fra_legal_middle_name', true ),
            'legal_last_name'       => get_user_meta( $user_id, 'fra_legal_last_name', true ),
            'date_of_birth'         => get_user_meta( $user_id, 'fra_date_of_birth', true ),
            'nationality'           => get_user_meta( $user_id, 'fra_nationality', true ),
            'passport_number'       => get_user_meta( $user_id, 'fra_passport_number', true ),
            'passport_expiry'       => get_user_meta( $user_id, 'fra_passport_expiry', true ),

            // Applicant Information
            'applicants'                => get_user_meta( $user_id, 'fra_applicants', true ),
            'spouse_legal_first_name'   => get_user_meta( $user_id, 'fra_spouse_legal_first_name', true ),
            'spouse_legal_last_name'    => get_user_meta( $user_id, 'fra_spouse_legal_last_name', true ),
            'spouse_date_of_birth'      => get_user_meta( $user_id, 'fra_spouse_date_of_birth', true ),
            'num_children'              => (int) get_user_meta( $user_id, 'fra_num_children', true ),
            'children_ages'             => get_user_meta( $user_id, 'fra_children_ages', true ),
            'has_pets'                  => get_user_meta( $user_id, 'fra_has_pets', true ),
            'pet_details'               => get_user_meta( $user_id, 'fra_pet_details', true ),

            // Visa & Employment
            'visa_type'             => get_user_meta( $user_id, 'fra_visa_type', true ),
            'employment_status'     => get_user_meta( $user_id, 'fra_employment_status', true ),
            'work_in_france'        => get_user_meta( $user_id, 'fra_work_in_france', true ),
            'industry'              => get_user_meta( $user_id, 'fra_industry', true ),
            'employer_name'         => get_user_meta( $user_id, 'fra_employer_name', true ),
            'job_title'             => get_user_meta( $user_id, 'fra_job_title', true ),

            // Location Information
            'current_state'         => get_user_meta( $user_id, 'fra_current_state', true ),
            'birth_state'           => get_user_meta( $user_id, 'fra_birth_state', true ),
            'birth_state_other'     => get_user_meta( $user_id, 'fra_birth_state_other', true ),
            'target_location'       => get_user_meta( $user_id, 'fra_target_location', true ),
            'housing_plan'          => get_user_meta( $user_id, 'fra_housing_plan', true ),

            // Timeline
            'timeline'              => get_user_meta( $user_id, 'fra_timeline', true ),
            'target_move_date'      => get_user_meta( $user_id, 'fra_target_move_date', true ),
            'application_location'  => get_user_meta( $user_id, 'fra_application_location', true ),

            // Financial
            'french_proficiency'    => get_user_meta( $user_id, 'fra_french_proficiency', true ),
            'french_mortgage'       => get_user_meta( $user_id, 'fra_french_mortgage', true ),

            // Documents
            'has_birth_cert'        => get_user_meta( $user_id, 'fra_has_birth_cert', true ),
            'birth_cert_apostilled' => get_user_meta( $user_id, 'fra_birth_cert_apostilled', true ),
            'has_marriage_cert'     => get_user_meta( $user_id, 'fra_has_marriage_cert', true ),
            'marriage_cert_apostilled' => get_user_meta( $user_id, 'fra_marriage_cert_apostilled', true ),

            // Metadata
            'updated_at'            => get_user_meta( $user_id, 'fra_profile_updated', true ),
        );

        return rest_ensure_response( $profile );
    }

    /**
     * Update member profile
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_member_profile( $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        // All fields that can be updated - matches frontend field names
        $allowed_fields = array(
            // Personal Information
            'legal_first_name',
            'legal_middle_name',
            'legal_last_name',
            'date_of_birth',
            'nationality',
            'passport_number',
            'passport_expiry',

            // Applicant Information
            'applicants',
            'spouse_legal_first_name',
            'spouse_legal_last_name',
            'spouse_date_of_birth',
            'num_children',
            'children_ages',
            'has_pets',
            'pet_details',

            // Visa & Employment
            'visa_type',
            'employment_status',
            'work_in_france',
            'industry',
            'employer_name',
            'job_title',

            // Location Information
            'current_state',
            'birth_state',
            'birth_state_other',
            'target_location',
            'housing_plan',

            // Timeline
            'timeline',
            'target_move_date',
            'application_location',

            // Financial
            'french_proficiency',
            'french_mortgage',

            // Documents
            'has_birth_cert',
            'birth_cert_apostilled',
            'has_marriage_cert',
            'marriage_cert_apostilled',
        );

        // Track old values for fields that trigger task generation
        $old_visa_type  = get_user_meta( $user_id, 'fra_visa_type', true );
        $old_has_pets   = get_user_meta( $user_id, 'fra_has_pets', true );
        $old_applicants = get_user_meta( $user_id, 'fra_applicants', true );

        // Update each field if provided
        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $value = $params[ $field ];
                // Sanitize based on type
                if ( is_numeric( $value ) ) {
                    $value = intval( $value );
                } elseif ( is_string( $value ) ) {
                    $value = sanitize_text_field( $value );
                }
                update_user_meta( $user_id, 'fra_' . $field, $value );
            }
        }

        // Update timestamp
        update_user_meta( $user_id, 'fra_profile_updated', current_time( 'mysql' ) );

        // Get new values
        $new_visa_type  = get_user_meta( $user_id, 'fra_visa_type', true );
        $new_has_pets   = get_user_meta( $user_id, 'fra_has_pets', true );
        $new_applicants = get_user_meta( $user_id, 'fra_applicants', true );

        // Sync project visa type and generate tasks if visa type changed
        if ( ! empty( $new_visa_type ) && $new_visa_type !== $old_visa_type ) {
            $this->sync_project_visa_type( $user_id, $new_visa_type );
            $this->generate_visa_tasks( $user_id, $new_visa_type );
        }

        // Generate conditional tasks based on profile changes
        $this->generate_conditional_tasks( $user_id, array(
            'old_has_pets'   => $old_has_pets,
            'new_has_pets'   => $new_has_pets,
            'old_applicants' => $old_applicants,
            'new_applicants' => $new_applicants,
        ) );

        return $this->get_member_profile( $request );
    }

    /**
     * Get profile completion status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_profile_completion( $request ) {
        $user_id = get_current_user_id();

        // Define required fields that count toward completion - using new field names
        $required_fields = array(
            // Personal - most important
            'legal_first_name',
            'legal_last_name',
            'date_of_birth',
            'nationality',
            'passport_number',
            'passport_expiry',

            // Applicant info
            'applicants',

            // Visa & Employment
            'visa_type',
            'employment_status',

            // Location
            'current_state',
            'target_location',

            // Timeline
            'timeline',
            'application_location',
        );

        $filled_fields  = 0;
        $total_fields   = count( $required_fields );
        $missing_fields = array();

        foreach ( $required_fields as $field ) {
            $value = get_user_meta( $user_id, 'fra_' . $field, true );

            if ( ! empty( $value ) ) {
                $filled_fields++;
            } else {
                $missing_fields[] = $field;
            }
        }

        $percentage = $total_fields > 0 ? round( ( $filled_fields / $total_fields ) * 100 ) : 0;

        // Response format matches frontend expectation
        $response = array(
            'percentage'     => $percentage,
            'missing_fields' => $missing_fields,
        );

        return rest_ensure_response( $response );
    }

    // =========================================================================
    // Checklists Methods
    // =========================================================================

    /**
     * Get all available checklists
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_checklists( $request ) {
        $user_id = get_current_user_id();

        // Define available checklist types
        $checklist_types = array(
            'visa-application' => array(
                'id'          => 'visa-application',
                'title'       => 'Visa Application Checklist',
                'description' => 'Documents and steps needed for your visa application',
                'icon'        => 'FileText',
                'category'    => 'visa',
            ),
            'pre-departure' => array(
                'id'          => 'pre-departure',
                'title'       => 'Pre-Departure Checklist',
                'description' => 'Things to complete before leaving your home country',
                'icon'        => 'Plane',
                'category'    => 'travel',
            ),
            'arrival' => array(
                'id'          => 'arrival',
                'title'       => 'Arrival Checklist',
                'description' => 'First steps after arriving in France',
                'icon'        => 'MapPin',
                'category'    => 'settlement',
            ),
            'administrative' => array(
                'id'          => 'administrative',
                'title'       => 'Administrative Setup',
                'description' => 'French administrative tasks and registrations',
                'icon'        => 'Building',
                'category'    => 'admin',
            ),
            'banking' => array(
                'id'          => 'banking',
                'title'       => 'Banking & Finance',
                'description' => 'Setting up French bank accounts and finances',
                'icon'        => 'CreditCard',
                'category'    => 'finance',
            ),
            'healthcare' => array(
                'id'          => 'healthcare',
                'title'       => 'Healthcare Setup',
                'description' => 'Registering for French healthcare system',
                'icon'        => 'Heart',
                'category'    => 'health',
            ),
        );

        // Get user progress for each checklist
        $response = array();
        foreach ( $checklist_types as $type => $checklist ) {
            $progress = get_user_meta( $user_id, 'fra_checklist_' . $type, true ) ?: array();
            $items    = $this->get_checklist_items( $type );

            $completed = 0;
            foreach ( $items as $item ) {
                if ( ! empty( $progress[ $item['id'] ]['completed'] ) ) {
                    $completed++;
                }
            }

            $checklist['total_items']     = count( $items );
            $checklist['completed_items'] = $completed;
            $checklist['percentage']      = count( $items ) > 0 ? round( ( $completed / count( $items ) ) * 100 ) : 0;

            $response[] = $checklist;
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get a specific checklist with items
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_checklist( $request ) {
        $user_id = get_current_user_id();
        $type    = $request->get_param( 'type' );

        $items    = $this->get_checklist_items( $type );
        $progress = get_user_meta( $user_id, 'fra_checklist_' . $type, true ) ?: array();

        if ( empty( $items ) ) {
            return new WP_Error(
                'rest_checklist_not_found',
                'Checklist type not found.',
                array( 'status' => 404 )
            );
        }

        // Merge progress with items
        foreach ( $items as &$item ) {
            $item['completed']    = ! empty( $progress[ $item['id'] ]['completed'] );
            $item['completed_at'] = $progress[ $item['id'] ]['completed_at'] ?? null;
            $item['notes']        = $progress[ $item['id'] ]['notes'] ?? '';
        }

        return rest_ensure_response( array(
            'type'  => $type,
            'items' => $items,
        ) );
    }

    /**
     * Update a checklist item
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_checklist_item( $request ) {
        $user_id = get_current_user_id();
        $type    = $request->get_param( 'type' );
        $item_id = $request->get_param( 'item_id' );
        $params  = $request->get_json_params();

        $progress = get_user_meta( $user_id, 'fra_checklist_' . $type, true ) ?: array();

        if ( ! isset( $progress[ $item_id ] ) ) {
            $progress[ $item_id ] = array();
        }

        if ( isset( $params['completed'] ) ) {
            $progress[ $item_id ]['completed'] = (bool) $params['completed'];
            $progress[ $item_id ]['completed_at'] = $params['completed'] ? current_time( 'mysql' ) : null;
        }

        if ( isset( $params['notes'] ) ) {
            $progress[ $item_id ]['notes'] = sanitize_textarea_field( $params['notes'] );
        }

        update_user_meta( $user_id, 'fra_checklist_' . $type, $progress );

        return rest_ensure_response( array(
            'item_id'   => $item_id,
            'completed' => $progress[ $item_id ]['completed'],
            'notes'     => $progress[ $item_id ]['notes'] ?? '',
        ) );
    }

    /**
     * Get checklist items for a type (with caching)
     *
     * @param string $type Checklist type.
     * @return array Checklist items.
     */
    private function get_checklist_items( string $type ): array {
        $cache_key = 'fra_checklist_items_' . sanitize_key( $type );

        return $this->get_cached_data( $cache_key, function() use ( $type ) {
            return $this->get_checklist_items_data( $type );
        }, self::CACHE_CHECKLISTS_DURATION );
    }

    /**
     * Get raw checklist items data
     *
     * @param string $type Checklist type.
     * @return array Checklist items.
     */
    private function get_checklist_items_data( string $type ): array {
        $checklists = array(
            'visa-application' => array(
                array( 'id' => 'passport-valid', 'title' => 'Valid passport (6+ months)', 'lead_time' => 180, 'priority' => 'high' ),
                array( 'id' => 'passport-photos', 'title' => 'Passport photos (35x45mm)', 'lead_time' => 14, 'priority' => 'high' ),
                array( 'id' => 'application-form', 'title' => 'Completed application form', 'lead_time' => 7, 'priority' => 'high' ),
                array( 'id' => 'proof-accommodation', 'title' => 'Proof of accommodation in France', 'lead_time' => 30, 'priority' => 'high' ),
                array( 'id' => 'proof-funds', 'title' => 'Proof of sufficient funds', 'lead_time' => 30, 'priority' => 'high' ),
                array( 'id' => 'travel-insurance', 'title' => 'Travel/health insurance certificate', 'lead_time' => 14, 'priority' => 'high' ),
                array( 'id' => 'flight-reservation', 'title' => 'Flight reservation/itinerary', 'lead_time' => 14, 'priority' => 'medium' ),
                array( 'id' => 'cover-letter', 'title' => 'Cover letter explaining purpose', 'lead_time' => 7, 'priority' => 'medium' ),
                array( 'id' => 'employment-proof', 'title' => 'Employment/income proof', 'lead_time' => 14, 'priority' => 'medium' ),
                array( 'id' => 'bank-statements', 'title' => 'Bank statements (3-6 months)', 'lead_time' => 7, 'priority' => 'high' ),
            ),
            'pre-departure' => array(
                array( 'id' => 'visa-received', 'title' => 'Visa received and verified', 'lead_time' => 30, 'priority' => 'high' ),
                array( 'id' => 'flights-booked', 'title' => 'Flights booked', 'lead_time' => 60, 'priority' => 'high' ),
                array( 'id' => 'accommodation-first', 'title' => 'First accommodation arranged', 'lead_time' => 30, 'priority' => 'high' ),
                array( 'id' => 'phone-plan', 'title' => 'International phone plan/SIM', 'lead_time' => 7, 'priority' => 'medium' ),
                array( 'id' => 'bank-notify', 'title' => 'Notify bank of travel', 'lead_time' => 7, 'priority' => 'medium' ),
                array( 'id' => 'important-docs', 'title' => 'Copies of important documents', 'lead_time' => 7, 'priority' => 'high' ),
                array( 'id' => 'prescriptions', 'title' => 'Prescription medications (3 months)', 'lead_time' => 14, 'priority' => 'medium' ),
                array( 'id' => 'power-attorney', 'title' => 'Power of attorney (if needed)', 'lead_time' => 30, 'priority' => 'low' ),
            ),
            'arrival' => array(
                array( 'id' => 'validate-visa', 'title' => 'Validate visa online (VLS-TS)', 'lead_time' => 90, 'priority' => 'high' ),
                array( 'id' => 'french-sim', 'title' => 'Get French SIM card', 'lead_time' => 3, 'priority' => 'high' ),
                array( 'id' => 'transport-card', 'title' => 'Get transport card (Navigo, etc.)', 'lead_time' => 7, 'priority' => 'medium' ),
                array( 'id' => 'grocery-essentials', 'title' => 'Stock up on essentials', 'lead_time' => 3, 'priority' => 'medium' ),
                array( 'id' => 'neighborhood-explore', 'title' => 'Explore neighborhood', 'lead_time' => 7, 'priority' => 'low' ),
            ),
            'administrative' => array(
                array( 'id' => 'ofii-appointment', 'title' => 'Schedule OFII appointment', 'lead_time' => 90, 'priority' => 'high' ),
                array( 'id' => 'titre-sejour', 'title' => 'Apply for titre de séjour', 'lead_time' => 60, 'priority' => 'high' ),
                array( 'id' => 'caf-register', 'title' => 'Register with CAF (if eligible)', 'lead_time' => 30, 'priority' => 'medium' ),
                array( 'id' => 'tax-registration', 'title' => 'Register with tax authorities', 'lead_time' => 90, 'priority' => 'medium' ),
            ),
            'banking' => array(
                array( 'id' => 'bank-account', 'title' => 'Open French bank account', 'lead_time' => 14, 'priority' => 'high' ),
                array( 'id' => 'rib-obtained', 'title' => 'Obtain RIB (bank details)', 'lead_time' => 7, 'priority' => 'high' ),
                array( 'id' => 'card-received', 'title' => 'Receive debit card', 'lead_time' => 14, 'priority' => 'medium' ),
                array( 'id' => 'transfer-setup', 'title' => 'Set up money transfer method', 'lead_time' => 7, 'priority' => 'medium' ),
            ),
            'healthcare' => array(
                array( 'id' => 'cpam-register', 'title' => 'Register with CPAM (Ameli)', 'lead_time' => 90, 'priority' => 'high' ),
                array( 'id' => 'carte-vitale', 'title' => 'Apply for Carte Vitale', 'lead_time' => 60, 'priority' => 'high' ),
                array( 'id' => 'medecin-traitant', 'title' => 'Choose médecin traitant', 'lead_time' => 30, 'priority' => 'medium' ),
                array( 'id' => 'mutuelle', 'title' => 'Consider complementary insurance', 'lead_time' => 30, 'priority' => 'low' ),
            ),
        );

        return $checklists[ $type ] ?? array();
    }

    // =========================================================================
    // Task Checklists Methods
    // =========================================================================

    /**
     * Get checklist items for a task
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_task_checklist( $request ) {
        $task_id = $request->get_param( 'task_id' );
        $task    = new FRAMT_Task( $task_id );

        $checklist = $task->meta['checklist'] ?? array();

        return rest_ensure_response( $checklist );
    }

    /**
     * Add item to task checklist
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function add_task_checklist_item( $request ) {
        $task_id = $request->get_param( 'task_id' );
        $params  = $request->get_json_params();
        $task    = new FRAMT_Task( $task_id );

        if ( empty( $params['title'] ) ) {
            return new WP_Error(
                'rest_item_title_required',
                'Checklist item title is required.',
                array( 'status' => 400 )
            );
        }

        $checklist = $task->meta['checklist'] ?? array();

        $new_item = array(
            'id'        => wp_generate_uuid4(),
            'title'     => sanitize_text_field( $params['title'] ),
            'completed' => false,
            'created_at' => current_time( 'mysql' ),
        );

        $checklist[] = $new_item;
        $task->meta['checklist'] = $checklist;
        $task->save();

        return rest_ensure_response( $new_item );
    }

    /**
     * Update task checklist item
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_task_checklist_item( $request ) {
        $task_id = $request->get_param( 'task_id' );
        $item_id = $request->get_param( 'item_id' );
        $params  = $request->get_json_params();
        $task    = new FRAMT_Task( $task_id );

        $checklist = $task->meta['checklist'] ?? array();
        $found     = false;

        foreach ( $checklist as &$item ) {
            if ( $item['id'] === $item_id ) {
                if ( isset( $params['title'] ) ) {
                    $item['title'] = sanitize_text_field( $params['title'] );
                }
                if ( isset( $params['completed'] ) ) {
                    $item['completed'] = (bool) $params['completed'];
                    if ( $item['completed'] ) {
                        $item['completed_at'] = current_time( 'mysql' );
                    } else {
                        unset( $item['completed_at'] );
                    }
                }
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error(
                'rest_item_not_found',
                'Checklist item not found.',
                array( 'status' => 404 )
            );
        }

        $task->meta['checklist'] = $checklist;
        $task->save();

        return rest_ensure_response( $item );
    }

    /**
     * Delete task checklist item
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_task_checklist_item( $request ) {
        $task_id = $request->get_param( 'task_id' );
        $item_id = $request->get_param( 'item_id' );
        $task    = new FRAMT_Task( $task_id );

        $checklist = $task->meta['checklist'] ?? array();
        $new_checklist = array();
        $found = false;

        foreach ( $checklist as $item ) {
            if ( $item['id'] === $item_id ) {
                $found = true;
            } else {
                $new_checklist[] = $item;
            }
        }

        if ( ! $found ) {
            return new WP_Error(
                'rest_item_not_found',
                'Checklist item not found.',
                array( 'status' => 404 )
            );
        }

        $task->meta['checklist'] = $new_checklist;
        $task->save();

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // =========================================================================
    // Document Generator Methods
    // =========================================================================

    /**
     * Get available document types for generation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_document_types( $request ) {
        $types = array(
            array(
                'id'          => 'cover-letter',
                'title'       => 'Visa Cover Letter',
                'description' => 'Professional cover letter for your visa application',
                'fields'      => array( 'purpose', 'duration', 'destination_city' ),
                'category'    => 'visa',
            ),
            array(
                'id'          => 'attestation-hebergement',
                'title'       => 'Attestation d\'hébergement',
                'description' => 'Accommodation certificate from your host',
                'fields'      => array( 'host_name', 'host_address', 'stay_dates' ),
                'category'    => 'accommodation',
            ),
            array(
                'id'          => 'lettre-motivation',
                'title'       => 'Lettre de Motivation',
                'description' => 'Motivation letter for studies or work',
                'fields'      => array( 'purpose', 'background', 'goals' ),
                'category'    => 'work',
            ),
            array(
                'id'          => 'employment-attestation',
                'title'       => 'Employment Attestation',
                'description' => 'Letter confirming your employment status',
                'fields'      => array( 'employer_name', 'position', 'salary', 'start_date' ),
                'category'    => 'work',
            ),
            array(
                'id'          => 'financial-attestation',
                'title'       => 'Financial Self-Sufficiency Letter',
                'description' => 'Declaration of financial resources',
                'fields'      => array( 'income_sources', 'monthly_amount', 'savings' ),
                'category'    => 'financial',
            ),
        );

        return rest_ensure_response( $types );
    }

    /**
     * Preview a document before generation
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function preview_document( $request ) {
        $params   = $request->get_json_params();
        $type     = $params['type'] ?? '';
        $data     = $params['data'] ?? array();
        $user_id  = get_current_user_id();

        // Get user profile for merge
        $user = get_userdata( $user_id );
        $profile = array(
            'full_name'   => trim( $user->first_name . ' ' . $user->last_name ),
            'email'       => $user->user_email,
            'address'     => get_user_meta( $user_id, 'fra_current_address', true ),
            'city'        => get_user_meta( $user_id, 'fra_current_city', true ),
            'country'     => get_user_meta( $user_id, 'fra_current_country', true ),
            'nationality' => get_user_meta( $user_id, 'fra_nationality', true ),
            'passport'    => get_user_meta( $user_id, 'fra_passport_number', true ),
        );

        $content = $this->generate_document_content( $type, array_merge( $profile, $data ) );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        return rest_ensure_response( array(
            'type'    => $type,
            'content' => $content,
            'preview' => true,
        ) );
    }

    /**
     * Generate and save a document
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function generate_document( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $params     = $request->get_json_params();
        $type       = $params['type'] ?? '';
        $data       = $params['data'] ?? array();
        $user_id    = get_current_user_id();

        // Get user profile
        $user    = get_userdata( $user_id );
        $profile = array(
            'full_name'   => trim( $user->first_name . ' ' . $user->last_name ),
            'email'       => $user->user_email,
            'address'     => get_user_meta( $user_id, 'fra_current_address', true ),
            'city'        => get_user_meta( $user_id, 'fra_current_city', true ),
            'country'     => get_user_meta( $user_id, 'fra_current_country', true ),
            'nationality' => get_user_meta( $user_id, 'fra_nationality', true ),
            'passport'    => get_user_meta( $user_id, 'fra_passport_number', true ),
        );

        $content = $this->generate_document_content( $type, array_merge( $profile, $data ) );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        // Save document record
        $table = FRAMT_Portal_Schema::get_table( 'generated_documents' );

        // Check if table exists, if not use files table
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( $table_exists ) {
            $wpdb->insert(
                $table,
                array(
                    'project_id'    => $project_id,
                    'user_id'       => $user_id,
                    'document_type' => $type,
                    'title'         => $this->get_document_title( $type ),
                    'content'       => $content,
                    'data'          => wp_json_encode( $data ),
                    'status'        => 'generated',
                    'created_at'    => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
            );

            $doc_id = $wpdb->insert_id;
        } else {
            // Fallback: store as file metadata
            $doc_id = wp_insert_post( array(
                'post_type'    => 'fra_document',
                'post_title'   => $this->get_document_title( $type ),
                'post_content' => $content,
                'post_status'  => 'private',
                'post_author'  => $user_id,
                'meta_input'   => array(
                    'project_id'    => $project_id,
                    'document_type' => $type,
                    'document_data' => $data,
                ),
            ) );
        }

        // Log activity
        FRAMT_Activity::log(
            $project_id,
            $user_id,
            'document_generated',
            'document',
            $doc_id,
            'Generated ' . $this->get_document_title( $type )
        );

        return rest_ensure_response( array(
            'id'      => $doc_id,
            'type'    => $type,
            'title'   => $this->get_document_title( $type ),
            'content' => $content,
        ) );
    }

    /**
     * Get generated documents for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_generated_documents( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $table      = FRAMT_Portal_Schema::get_table( 'generated_documents' );

        // Check if custom table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( $table_exists ) {
            $docs = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table WHERE project_id = %d ORDER BY created_at DESC",
                $project_id
            ) );

            $response = array();
            foreach ( $docs as $doc ) {
                $response[] = array(
                    'id'         => (int) $doc->id,
                    'type'       => $doc->document_type,
                    'title'      => $doc->title,
                    'status'     => $doc->status,
                    'created_at' => $doc->created_at,
                    'is_generated' => true,
                );
            }
        } else {
            // Fallback: get from posts
            $posts = get_posts( array(
                'post_type'   => 'fra_document',
                'post_status' => 'private',
                'meta_query'  => array(
                    array(
                        'key'   => 'project_id',
                        'value' => $project_id,
                    ),
                ),
                'numberposts' => -1,
            ) );

            $response = array();
            foreach ( $posts as $post ) {
                $response[] = array(
                    'id'         => $post->ID,
                    'type'       => get_post_meta( $post->ID, 'document_type', true ),
                    'title'      => $post->post_title,
                    'status'     => 'generated',
                    'created_at' => $post->post_date,
                    'is_generated' => true,
                );
            }
        }

        return rest_ensure_response( $response );
    }

    /**
     * Download a generated document
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function download_generated_document( $request ) {
        global $wpdb;

        $doc_id = $request->get_param( 'id' );
        $table  = FRAMT_Portal_Schema::get_table( 'generated_documents' );

        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( $table_exists ) {
            $doc = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $doc_id ) );

            if ( ! $doc || (int) $doc->user_id !== get_current_user_id() ) {
                return new WP_Error( 'rest_doc_not_found', 'Document not found.', array( 'status' => 404 ) );
            }

            return rest_ensure_response( array(
                'id'      => (int) $doc->id,
                'title'   => $doc->title,
                'content' => $doc->content,
                'type'    => $doc->document_type,
            ) );
        } else {
            $post = get_post( $doc_id );

            if ( ! $post || (int) $post->post_author !== get_current_user_id() ) {
                return new WP_Error( 'rest_doc_not_found', 'Document not found.', array( 'status' => 404 ) );
            }

            return rest_ensure_response( array(
                'id'      => $post->ID,
                'title'   => $post->post_title,
                'content' => $post->post_content,
                'type'    => get_post_meta( $post->ID, 'document_type', true ),
            ) );
        }
    }

    /**
     * Generate document content based on type
     *
     * @param string $type Document type
     * @param array  $data Merge data
     * @return string|WP_Error Document content
     */
    private function generate_document_content( $type, $data ) {
        $templates = array(
            'cover-letter' => "
[Your Name]
[Your Address]
[City, Country]
[Date]

To Whom It May Concern,

I am writing to apply for a [visa_type] visa to France. My name is {full_name}, and I am a citizen of {nationality} with passport number {passport}.

The purpose of my visit is {purpose}. I plan to stay in France for {duration}, primarily in {destination_city}.

I have arranged accommodation and have sufficient financial resources to support myself during my stay. I have also obtained travel health insurance as required.

I am committed to complying with all visa regulations and will return to my home country before my authorized stay expires.

Thank you for considering my application.

Sincerely,
{full_name}
",
            'attestation-hebergement' => "
ATTESTATION D'HÉBERGEMENT

Je soussigné(e), {host_name},
Demeurant à: {host_address}

Certifie sur l'honneur héberger à mon domicile:

Nom: {full_name}
Nationalité: {nationality}
Numéro de passeport: {passport}

Pour la période du {stay_start} au {stay_end}.

Fait à _____________, le {date}

Signature:
",
        );

        $template = $templates[ $type ] ?? null;

        if ( ! $template ) {
            return new WP_Error( 'invalid_type', 'Invalid document type.', array( 'status' => 400 ) );
        }

        // Replace placeholders with sanitized values
        $data['date'] = date_i18n( 'F j, Y' );

        foreach ( $data as $key => $value ) {
            // Sanitize value to prevent XSS - use esc_html for text content
            $safe_value = is_string( $value ) ? esc_html( $value ) : esc_html( (string) $value );
            $template   = str_replace( '{' . $key . '}', $safe_value, $template );
            $template   = str_replace( '[' . $key . ']', $safe_value, $template );
        }

        return trim( $template );
    }

    /**
     * Get document title by type
     *
     * @param string $type Document type
     * @return string Title
     */
    private function get_document_title( $type ) {
        $titles = array(
            'cover-letter'           => 'Visa Cover Letter',
            'attestation-hebergement' => 'Attestation d\'hébergement',
            'lettre-motivation'      => 'Lettre de Motivation',
            'employment-attestation' => 'Employment Attestation',
            'financial-attestation'  => 'Financial Self-Sufficiency Letter',
        );

        return $titles[ $type ] ?? 'Generated Document';
    }

    // =========================================================================
    // Glossary Methods
    // =========================================================================

    /**
     * Get full glossary
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_glossary( $request ) {
        $glossary = $this->get_glossary_data();

        return rest_ensure_response( $glossary );
    }

    /**
     * Search glossary terms
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function search_glossary( $request ) {
        $query    = strtolower( $request->get_param( 'q' ) ?? '' );
        $glossary = $this->get_glossary_data();
        $results  = array();

        if ( empty( $query ) ) {
            return rest_ensure_response( array() );
        }

        foreach ( $glossary as $category ) {
            foreach ( $category['terms'] as $term ) {
                if (
                    strpos( strtolower( $term['term'] ), $query ) !== false ||
                    strpos( strtolower( $term['definition'] ), $query ) !== false
                ) {
                    $term['category'] = $category['id'];
                    $results[] = $term;
                }
            }
        }

        return rest_ensure_response( $results );
    }

    /**
     * Get glossary by category
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_glossary_category( $request ) {
        $category_id = $request->get_param( 'category_id' );
        $glossary    = $this->get_glossary_data();

        foreach ( $glossary as $category ) {
            if ( $category['id'] === $category_id ) {
                return rest_ensure_response( $category );
            }
        }

        return new WP_Error( 'category_not_found', 'Category not found.', array( 'status' => 404 ) );
    }

    /**
     * Get glossary data (with caching)
     *
     * @return array Glossary categories and terms.
     */
    private function get_glossary_data(): array {
        return $this->get_cached_data( 'fra_glossary_data', function() {
            return $this->get_glossary_data_raw();
        }, self::CACHE_GLOSSARY_DURATION );
    }

    /**
     * Get raw glossary data
     *
     * @return array Glossary categories and terms.
     */
    private function get_glossary_data_raw(): array {
        return array(
            array(
                'id'    => 'visa-immigration',
                'title' => 'Visa & Immigration',
                'terms' => array(
                    array( 'term' => 'VLS-TS', 'definition' => 'Visa de Long Séjour valant Titre de Séjour - Long-stay visa equivalent to residence permit', 'pronunciation' => 'vay-el-ess tay-ess' ),
                    array( 'term' => 'Titre de séjour', 'definition' => 'Residence permit required for stays over 90 days', 'pronunciation' => 'tee-truh duh say-zhoor' ),
                    array( 'term' => 'OFII', 'definition' => 'Office Français de l\'Immigration et de l\'Intégration - French immigration office', 'pronunciation' => 'oh-fee' ),
                    array( 'term' => 'Préfecture', 'definition' => 'Government administrative office handling residence permits', 'pronunciation' => 'pray-fek-toor' ),
                    array( 'term' => 'Récépissé', 'definition' => 'Temporary receipt while awaiting official documents', 'pronunciation' => 'ray-say-pee-say' ),
                ),
            ),
            array(
                'id'    => 'administrative',
                'title' => 'Administrative Terms',
                'terms' => array(
                    array( 'term' => 'Mairie', 'definition' => 'Town hall / Mayor\'s office', 'pronunciation' => 'meh-ree' ),
                    array( 'term' => 'Carte d\'identité', 'definition' => 'National identity card', 'pronunciation' => 'kart dee-don-tee-tay' ),
                    array( 'term' => 'Justificatif de domicile', 'definition' => 'Proof of address document', 'pronunciation' => 'zhoos-tee-fee-ka-teef duh doh-mee-seel' ),
                    array( 'term' => 'Acte de naissance', 'definition' => 'Birth certificate', 'pronunciation' => 'akt duh nay-sahns' ),
                    array( 'term' => 'Timbre fiscal', 'definition' => 'Tax stamp required for official documents', 'pronunciation' => 'tom-bruh fees-kal' ),
                ),
            ),
            array(
                'id'    => 'healthcare',
                'title' => 'Healthcare',
                'terms' => array(
                    array( 'term' => 'Carte Vitale', 'definition' => 'French health insurance card', 'pronunciation' => 'kart vee-tal' ),
                    array( 'term' => 'CPAM', 'definition' => 'Caisse Primaire d\'Assurance Maladie - Primary health insurance fund', 'pronunciation' => 'say-pam' ),
                    array( 'term' => 'Médecin traitant', 'definition' => 'Primary care doctor / GP', 'pronunciation' => 'mayd-san tray-ton' ),
                    array( 'term' => 'Mutuelle', 'definition' => 'Complementary health insurance', 'pronunciation' => 'moo-too-el' ),
                    array( 'term' => 'Ordonnance', 'definition' => 'Medical prescription', 'pronunciation' => 'or-doh-nahns' ),
                ),
            ),
            array(
                'id'    => 'banking',
                'title' => 'Banking & Finance',
                'terms' => array(
                    array( 'term' => 'RIB', 'definition' => 'Relevé d\'Identité Bancaire - Bank account details document', 'pronunciation' => 'reeb' ),
                    array( 'term' => 'IBAN', 'definition' => 'International Bank Account Number', 'pronunciation' => 'ee-bon' ),
                    array( 'term' => 'Virement', 'definition' => 'Bank transfer', 'pronunciation' => 'veer-mon' ),
                    array( 'term' => 'Prélèvement', 'definition' => 'Direct debit', 'pronunciation' => 'pray-lev-mon' ),
                    array( 'term' => 'Chèque', 'definition' => 'Check / Cheque (still commonly used in France)', 'pronunciation' => 'shek' ),
                ),
            ),
            array(
                'id'    => 'housing',
                'title' => 'Housing',
                'terms' => array(
                    array( 'term' => 'Bail', 'definition' => 'Lease agreement', 'pronunciation' => 'bye' ),
                    array( 'term' => 'Caution', 'definition' => 'Security deposit', 'pronunciation' => 'koh-syon' ),
                    array( 'term' => 'État des lieux', 'definition' => 'Property inventory/condition report', 'pronunciation' => 'ay-tah day lyuh' ),
                    array( 'term' => 'Propriétaire', 'definition' => 'Landlord / Property owner', 'pronunciation' => 'proh-pree-ay-tair' ),
                    array( 'term' => 'Charges', 'definition' => 'Additional fees (utilities, maintenance)', 'pronunciation' => 'sharzh' ),
                ),
            ),
        );
    }

    // =========================================================================
    // AI Verification Methods
    // =========================================================================

    /**
     * Verify a document using AI
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function verify_document( $request ) {
        $user_id = get_current_user_id();

        // Rate limiting check for AI operations
        $rate_check = $this->check_rate_limit(
            $user_id,
            'ai_verify',
            self::RATE_LIMIT_AI_REQUESTS,
            self::RATE_LIMIT_AI_WINDOW
        );
        if ( is_wp_error( $rate_check ) ) {
            return $rate_check;
        }

        $params = $request->get_json_params();
        $type   = isset( $params['document_type'] ) ? sanitize_text_field( $params['document_type'] ) : '';
        $data   = $params['document_data'] ?? array();

        // Log AI verification request
        $this->log_security_event( 'ai_verification_request', $user_id, array(
            'document_type' => $type,
        ) );

        // Simulate AI verification (in production, this would call an AI service)
        $result = $this->perform_verification( $type, $data );

        return rest_ensure_response( $result );
    }

    /**
     * Verify a project document
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_project_document( $request ) {
        global $wpdb;

        $project_id = $request->get_param( 'project_id' );
        $params     = $request->get_json_params();
        $file_id    = $params['file_id'] ?? 0;
        $type       = $params['document_type'] ?? '';

        // Get file info
        $table = FRAMT_Portal_Schema::get_table( 'files' );
        $file  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $file_id ) );

        if ( ! $file ) {
            return new WP_Error( 'file_not_found', 'File not found.', array( 'status' => 404 ) );
        }

        // Perform verification
        $result = $this->perform_verification( $type, array( 'file' => $file ) );

        // Store verification result
        $verifications = get_user_meta( get_current_user_id(), 'fra_verifications', true ) ?: array();
        $verifications[] = array(
            'file_id'    => $file_id,
            'project_id' => $project_id,
            'type'       => $type,
            'result'     => $result,
            'created_at' => current_time( 'mysql' ),
        );
        update_user_meta( get_current_user_id(), 'fra_verifications', $verifications );

        return rest_ensure_response( $result );
    }

    /**
     * Get verification history for a project
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_verification_history( $request ) {
        $project_id    = $request->get_param( 'project_id' );
        $verifications = get_user_meta( get_current_user_id(), 'fra_verifications', true ) ?: array();

        // Filter by project
        $filtered = array_filter( $verifications, function( $v ) use ( $project_id ) {
            return isset( $v['project_id'] ) && $v['project_id'] == $project_id;
        } );

        return rest_ensure_response( array_values( $filtered ) );
    }

    /**
     * Perform document verification
     *
     * @param string $type Document type
     * @param array  $data Document data
     * @return array Verification result
     */
    private function perform_verification( $type, $data ) {
        // This is a simplified verification simulation
        // In production, integrate with AI service

        $checks = array();

        switch ( $type ) {
            case 'health-insurance':
                $checks = array(
                    array( 'name' => 'Policy Number Present', 'status' => 'pass', 'message' => 'Valid policy number found' ),
                    array( 'name' => 'Coverage Period', 'status' => 'pass', 'message' => 'Coverage dates are valid' ),
                    array( 'name' => 'Schengen Compliance', 'status' => 'pass', 'message' => 'Meets minimum €30,000 coverage' ),
                    array( 'name' => 'Repatriation Coverage', 'status' => 'warning', 'message' => 'Verify repatriation coverage included' ),
                );
                break;

            case 'passport':
                $checks = array(
                    array( 'name' => 'Validity Period', 'status' => 'pass', 'message' => 'Passport valid for required duration' ),
                    array( 'name' => 'Empty Pages', 'status' => 'pass', 'message' => 'Sufficient blank pages available' ),
                    array( 'name' => 'Photo Quality', 'status' => 'pass', 'message' => 'Photo meets requirements' ),
                );
                break;

            default:
                $checks = array(
                    array( 'name' => 'Document Quality', 'status' => 'pass', 'message' => 'Document is readable' ),
                    array( 'name' => 'Format Check', 'status' => 'pass', 'message' => 'Document format accepted' ),
                );
        }

        $pass_count = count( array_filter( $checks, fn( $c ) => $c['status'] === 'pass' ) );
        $overall    = $pass_count === count( $checks ) ? 'pass' : ( $pass_count > 0 ? 'warning' : 'fail' );

        return array(
            'status'     => $overall,
            'score'      => round( ( $pass_count / count( $checks ) ) * 100 ),
            'checks'     => $checks,
            'verified_at' => current_time( 'mysql' ),
            'type'       => $type,
        );
    }

    // =========================================================================
    // Guides Methods
    // =========================================================================

    /**
     * Get all available guides
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_guides( $request ) {
        $guides = array(
            array(
                'id'          => 'visa-application',
                'title'       => 'Visa Application Guide',
                'description' => 'Step-by-step guide to applying for your French visa',
                'category'    => 'visa',
                'read_time'   => '15 min',
            ),
            array(
                'id'          => 'healthcare-system',
                'title'       => 'French Healthcare System',
                'description' => 'Understanding and enrolling in French healthcare',
                'category'    => 'healthcare',
                'read_time'   => '12 min',
            ),
            array(
                'id'          => 'banking-finance',
                'title'       => 'Banking in France',
                'description' => 'Opening accounts and managing finances',
                'category'    => 'finance',
                'read_time'   => '10 min',
            ),
            array(
                'id'          => 'housing-guide',
                'title'       => 'Finding Housing',
                'description' => 'Renting and housing regulations in France',
                'category'    => 'housing',
                'read_time'   => '18 min',
            ),
            array(
                'id'          => 'administrative-setup',
                'title'       => 'Administrative Setup',
                'description' => 'Essential registrations and paperwork',
                'category'    => 'admin',
                'read_time'   => '20 min',
            ),
        );

        return rest_ensure_response( $guides );
    }

    /**
     * Get a specific guide
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_guide( $request ) {
        $type = $request->get_param( 'type' );

        $content = $this->get_guide_content( $type );

        if ( ! $content ) {
            return new WP_Error( 'guide_not_found', 'Guide not found.', array( 'status' => 404 ) );
        }

        return rest_ensure_response( $content );
    }

    /**
     * Get personalized guide based on user profile
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_personalized_guide( $request ) {
        $type    = $request->get_param( 'type' );
        $user_id = get_current_user_id();

        // Get user's visa type and situation
        $visa_type   = get_user_meta( $user_id, 'fra_visa_type_applying', true ) ?: 'visitor';
        $nationality = get_user_meta( $user_id, 'fra_nationality', true );

        $guide = $this->get_guide_content( $type );

        if ( ! $guide ) {
            return new WP_Error( 'guide_not_found', 'Guide not found.', array( 'status' => 404 ) );
        }

        // Add personalized sections based on user profile
        $guide['personalized'] = true;
        $guide['visa_type']    = $visa_type;
        $guide['tips']         = $this->get_personalized_tips( $type, $visa_type, $nationality );

        return rest_ensure_response( $guide );
    }

    /**
     * Generate AI-powered guide content
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function generate_ai_guide( $request ) {
        $type   = $request->get_param( 'type' );
        $params = $request->get_json_params();
        $question = $params['question'] ?? '';

        // In production, this would call an AI service
        // For now, return contextual information

        $response = array(
            'type'     => $type,
            'question' => $question,
            'answer'   => 'Based on your situation, here are some personalized recommendations...',
            'sources'  => array(
                'Official French government website',
                'France Visa official portal',
            ),
            'generated_at' => current_time( 'mysql' ),
        );

        return rest_ensure_response( $response );
    }

    /**
     * Get guide content by type
     *
     * @param string $type Guide type
     * @return array|null Guide content
     */
    private function get_guide_content( $type ) {
        $guides = array(
            'visa-application' => array(
                'id'       => 'visa-application',
                'title'    => 'Complete Visa Application Guide',
                'sections' => array(
                    array(
                        'title'   => 'Before You Apply',
                        'content' => 'Gather all required documents including valid passport, photos, proof of accommodation...',
                    ),
                    array(
                        'title'   => 'Application Process',
                        'content' => 'Submit your application through TLS Contact or VFS Global...',
                    ),
                    array(
                        'title'   => 'After Approval',
                        'content' => 'Validate your VLS-TS visa online within 3 months of arrival...',
                    ),
                ),
            ),
            'healthcare-system' => array(
                'id'       => 'healthcare-system',
                'title'    => 'French Healthcare System Guide',
                'sections' => array(
                    array(
                        'title'   => 'Understanding the System',
                        'content' => 'France has a universal healthcare system (Sécurité Sociale)...',
                    ),
                    array(
                        'title'   => 'Registering with CPAM',
                        'content' => 'Create an account on ameli.fr and submit your registration...',
                    ),
                    array(
                        'title'   => 'Choosing a Doctor',
                        'content' => 'Select a médecin traitant (primary care physician)...',
                    ),
                ),
            ),
        );

        return $guides[ $type ] ?? null;
    }

    /**
     * Get personalized tips based on user situation
     *
     * @param string $guide_type Guide type
     * @param string $visa_type Visa type
     * @param string $nationality User nationality
     * @return array Tips
     */
    private function get_personalized_tips( $guide_type, $visa_type, $nationality ) {
        $tips = array();

        if ( 'visa-application' === $guide_type ) {
            if ( 'visitor' === $visa_type ) {
                $tips[] = 'As a visitor visa applicant, focus on demonstrating strong ties to your home country.';
            } elseif ( 'student' === $visa_type ) {
                $tips[] = 'Student visa applicants should have acceptance letter and proof of sufficient funds.';
            }
        }

        return $tips;
    }

    // =========================================================================
    // Chat/Knowledge Base Methods
    // =========================================================================

    /**
     * Send a chat message and get AI response
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function send_chat_message( $request ): WP_REST_Response {
        $user_id = get_current_user_id();

        // Rate limiting check
        $rate_check = $this->check_rate_limit(
            $user_id,
            'chat',
            self::RATE_LIMIT_CHAT_REQUESTS,
            self::RATE_LIMIT_CHAT_WINDOW
        );
        if ( is_wp_error( $rate_check ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => $rate_check->get_error_message(),
            ) );
        }

        $params           = $request->get_json_params();
        $message          = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';
        $context          = isset( $params['context'] ) ? sanitize_text_field( $params['context'] ) : 'general';
        $include_practice = $params['include_practice'] ?? true;

        if ( empty( $message ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => 'Message is required.',
            ) );
        }

        // Get comprehensive user context for personalized responses
        $user_context = $this->get_user_portal_context( $user_id );

        // Get chat history to determine if this is a follow-up
        $history       = get_user_meta( $user_id, 'fra_chat_history', true ) ?: array();

        // Check if profile has changed since last chat - if so, treat as new conversation
        $last_profile_hash = get_user_meta( $user_id, 'fra_chat_profile_hash', true );
        $current_profile_hash = md5( json_encode( $user_context['profile'] ) );

        if ( $last_profile_hash !== $current_profile_hash ) {
            // Profile changed - clear chat history to avoid stale context
            $history = array();
            update_user_meta( $user_id, 'fra_chat_profile_hash', $current_profile_hash );
        }

        $is_follow_up  = $this->is_follow_up_question( $history, $message );

        // Generate response based on whether this is initial or follow-up
        $kb_results    = array();
        $sources       = array();

        if ( $is_follow_up ) {
            // Follow-up: Use AI directly with conversation context
            $response_text = $this->generate_follow_up_response( $message, $context, $include_practice, $history, $user_context );
            // Keep sources from previous response if available
            $last_assistant = $this->get_last_assistant_message( $history );
            $sources = $last_assistant['sources'] ?? array();
        } else {
            // Initial question: Search KB first, then enhance with AI + web search
            $kb_results    = $this->search_knowledge_base( $message, $context );
            $response_text = $this->generate_kb_enhanced_response( $message, $context, $include_practice, $kb_results, $user_context );

            // Combine KB sources with web sources
            $sources = $this->format_kb_sources( $kb_results );

            // Add web sources if "Include real-world insights" is enabled
            if ( $include_practice ) {
                $web_results = $this->search_web_for_info( $message, $context );
                $web_sources = $this->format_web_sources( $web_results );
                $sources = array_merge( $sources, $web_sources );
            }
        }

        // Save chat history
        $history[] = array(
            'role'      => 'user',
            'message'   => $message,
            'context'   => $context,
            'timestamp' => current_time( 'mysql' ),
        );
        $history[] = array(
            'role'      => 'assistant',
            'message'   => $response_text,
            'context'   => $context,
            'sources'   => $sources,
            'timestamp' => current_time( 'mysql' ),
        );

        // Keep last 100 messages
        if ( count( $history ) > 100 ) {
            $history = array_slice( $history, -100 );
        }
        update_user_meta( $user_id, 'fra_chat_history', $history );

        return rest_ensure_response( array(
            'success'   => true,
            'message'   => $response_text,
            'sources'   => $sources,
            'timestamp' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Check if this is a follow-up question in the same conversation
     *
     * @param array  $history Chat history
     * @param string $message Current message
     * @return bool True if follow-up
     */
    private function is_follow_up_question( $history, $message ) {
        if ( empty( $history ) ) {
            return false;
        }

        // Get the last message timestamp
        $last_message = end( $history );
        if ( empty( $last_message['timestamp'] ) ) {
            return false;
        }

        // If last message was within 30 minutes, consider it a follow-up
        $last_time    = strtotime( $last_message['timestamp'] );
        $current_time = current_time( 'timestamp' );
        $time_diff    = $current_time - $last_time;

        if ( $time_diff > 1800 ) { // 30 minutes
            return false;
        }

        // Check for follow-up indicators in the message
        $lower_message     = strtolower( $message );
        $follow_up_phrases = array(
            'what about', 'how about', 'and what', 'also', 'additionally',
            'follow up', 'followup', 'more about', 'tell me more',
            'can you explain', 'what if', 'but what', 'however',
            'in that case', 'so if', 'does that mean', 'is that',
        );

        foreach ( $follow_up_phrases as $phrase ) {
            if ( strpos( $lower_message, $phrase ) !== false ) {
                return true;
            }
        }

        // If message is short (likely a clarification), treat as follow-up
        if ( str_word_count( $message ) <= 5 ) {
            return true;
        }

        return false;
    }

    /**
     * Get the last assistant message from history
     *
     * @param array $history Chat history
     * @return array|null Last assistant message or null
     */
    private function get_last_assistant_message( $history ) {
        for ( $i = count( $history ) - 1; $i >= 0; $i-- ) {
            if ( isset( $history[ $i ]['role'] ) && $history[ $i ]['role'] === 'assistant' ) {
                return $history[ $i ];
            }
        }
        return null;
    }

    /**
     * Search the knowledge base for relevant articles
     *
     * @param string $message User message
     * @param string $context Category context
     * @return array Matching KB articles with relevance scores
     */
    private function search_knowledge_base( $message, $context = 'general' ) {
        // Load the knowledge base
        $kb_file = WP_PLUGIN_DIR . '/france-relocation-assistant/includes/knowledge-base-default.php';
        if ( ! file_exists( $kb_file ) ) {
            // Try alternate path
            $kb_file = ABSPATH . 'wp-content/plugins/france-relocation-assistant-plugin/includes/knowledge-base-default.php';
        }

        if ( ! file_exists( $kb_file ) ) {
            return array();
        }

        $knowledge_base = include $kb_file;
        if ( ! is_array( $knowledge_base ) ) {
            return array();
        }

        $results       = array();
        $lower_message = strtolower( $message );
        $message_words = preg_split( '/\s+/', $lower_message );

        // If context is specified, prioritize that category
        $categories_to_search = array_keys( $knowledge_base );
        if ( $context !== 'general' && isset( $knowledge_base[ $context ] ) ) {
            // Put the specified context first
            $categories_to_search = array_merge(
                array( $context ),
                array_diff( $categories_to_search, array( $context ) )
            );
        }

        foreach ( $categories_to_search as $category ) {
            if ( ! isset( $knowledge_base[ $category ] ) ) {
                continue;
            }

            foreach ( $knowledge_base[ $category ] as $topic_id => $article ) {
                $relevance = $this->calculate_kb_relevance( $lower_message, $message_words, $article, $category === $context );

                if ( $relevance > 0.2 ) { // Minimum relevance threshold
                    $results[] = array(
                        'category'  => $category,
                        'topic_id'  => $topic_id,
                        'title'     => $article['title'] ?? ucfirst( $topic_id ),
                        'content'   => $article['content'] ?? '',
                        'keywords'  => $article['keywords'] ?? array(),
                        'sources'   => $article['sources'] ?? array(),
                        'relevance' => $relevance,
                    );
                }
            }
        }

        // Sort by relevance (highest first)
        usort( $results, function( $a, $b ) {
            return $b['relevance'] <=> $a['relevance'];
        } );

        // Return top 3 most relevant articles
        return array_slice( $results, 0, 3 );
    }

    /**
     * Calculate relevance score for a KB article
     *
     * @param string $lower_message Lowercase user message
     * @param array  $message_words Words from the message
     * @param array  $article KB article
     * @param bool   $is_category_match Whether this is from the user's selected category
     * @return float Relevance score 0-1
     */
    private function calculate_kb_relevance( $lower_message, $message_words, $article, $is_category_match = false ) {
        $score = 0;

        // Check keyword matches
        $keywords = $article['keywords'] ?? array();
        foreach ( $keywords as $keyword ) {
            $keyword_lower = strtolower( $keyword );
            if ( strpos( $lower_message, $keyword_lower ) !== false ) {
                $score += 0.3;
            }
        }

        // Check title match
        $title_lower = strtolower( $article['title'] ?? '' );
        foreach ( $message_words as $word ) {
            if ( strlen( $word ) > 3 && strpos( $title_lower, $word ) !== false ) {
                $score += 0.15;
            }
        }

        // Check content match (lighter weight)
        $content_lower = strtolower( $article['content'] ?? '' );
        foreach ( $message_words as $word ) {
            if ( strlen( $word ) > 4 && strpos( $content_lower, $word ) !== false ) {
                $score += 0.05;
            }
        }

        // Bonus for category match
        if ( $is_category_match ) {
            $score += 0.2;
        }

        // Cap at 1.0
        return min( 1.0, $score );
    }

    /**
     * Search the web for additional information
     * Uses Brave Search API or falls back to curated source search
     *
     * @param string $message User's question
     * @param string $context Category context
     * @return array Web search results
     */
    private function search_web_for_info( $message, $context = 'general' ) {
        $results = array(
            'official' => array(),
            'community' => array(),
        );

        // Get search API key (stored in main plugin options)
        $search_api_key = get_option( 'fra_search_api_key', '' );

        // Build search queries based on context
        $base_query = $this->build_search_query( $message, $context );

        if ( ! empty( $search_api_key ) ) {
            // Use Brave Search API for comprehensive results
            $results['official'] = $this->brave_search( $search_api_key, $base_query . ' site:service-public.fr OR site:france-visas.gouv.fr OR site:impots.gouv.fr OR site:ameli.fr', 3 );
            $results['community'] = $this->brave_search( $search_api_key, $base_query . ' France expat experience reddit OR forum', 3 );
        } else {
            // Fallback: Use Claude's knowledge with explicit web source prompting
            $results = $this->get_web_context_from_ai( $message, $context );
        }

        return $results;
    }

    /**
     * Build optimized search query from user message
     *
     * @param string $message User message
     * @param string $context Category context
     * @return string Optimized search query
     */
    private function build_search_query( $message, $context ) {
        // Extract key terms from message
        $query = preg_replace( '/[^\w\s]/', '', strtolower( $message ) );

        // Remove common stop words
        $stop_words = array( 'how', 'do', 'i', 'what', 'is', 'the', 'a', 'an', 'in', 'to', 'for', 'can', 'tell', 'me', 'about' );
        $words = explode( ' ', $query );
        $words = array_diff( $words, $stop_words );
        $query = implode( ' ', $words );

        // Add context-specific terms
        $context_terms = array(
            'visas' => 'visa France',
            'healthcare' => 'healthcare France carte vitale',
            'property' => 'property buying France',
            'banking' => 'bank account France',
            'taxes' => 'taxes France expat',
            'driving' => 'driving license France',
            'settling' => 'living France expat',
        );

        if ( isset( $context_terms[ $context ] ) ) {
            $query .= ' ' . $context_terms[ $context ];
        } else {
            $query .= ' France relocation';
        }

        // Add current year for fresh results
        $query .= ' ' . date( 'Y' );

        return trim( $query );
    }

    /**
     * Search using Brave Search API
     *
     * @param string $api_key Brave Search API key
     * @param string $query Search query
     * @param int $count Number of results
     * @return array Search results
     */
    private function brave_search( $api_key, $query, $count = 3 ) {
        $response = wp_remote_get(
            'https://api.search.brave.com/res/v1/web/search?' . http_build_query( array(
                'q' => $query,
                'count' => $count,
                'freshness' => 'py', // Past year
            ) ),
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                    'X-Subscription-Token' => $api_key,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $results = array();

        if ( isset( $body['web']['results'] ) ) {
            foreach ( array_slice( $body['web']['results'], 0, $count ) as $result ) {
                $results[] = array(
                    'title' => $result['title'] ?? '',
                    'url' => $result['url'] ?? '',
                    'snippet' => $result['description'] ?? '',
                    'date' => $result['page_age'] ?? date( 'Y' ),
                );
            }
        }

        return $results;
    }

    /**
     * Get web-sourced context using AI when no search API is available
     * Claude will synthesize from its training data with source awareness
     *
     * @param string $message User message
     * @param string $context Category context
     * @return array Simulated web results
     */
    private function get_web_context_from_ai( $message, $context ) {
        // Define reliable sources by category
        $official_sources = array(
            'visas' => array( 'france-visas.gouv.fr', 'service-public.fr' ),
            'healthcare' => array( 'ameli.fr', 'service-public.fr' ),
            'property' => array( 'notaires.fr', 'service-public.fr' ),
            'banking' => array( 'banque-france.fr', 'service-public.fr' ),
            'taxes' => array( 'impots.gouv.fr', 'irs.gov' ),
            'driving' => array( 'service-public.fr', 'securite-routiere.gouv.fr' ),
            'settling' => array( 'service-public.fr', 'diplomatie.gouv.fr' ),
        );

        $community_sources = array(
            'Reddit r/expats',
            'Reddit r/france',
            'Reddit r/IWantOut',
            'Expat.com forums',
            'FrenchEntrée forums',
            'Toytown Germany France section',
            'AngloINFO France',
        );

        return array(
            'official' => array(
                array(
                    'title' => 'Official French Sources',
                    'url' => 'https://' . ( $official_sources[ $context ][0] ?? 'service-public.fr' ),
                    'snippet' => 'Check official French government sources for current requirements and procedures.',
                    'date' => date( 'Y' ),
                ),
            ),
            'community' => array(
                array(
                    'title' => 'Expat Community Discussions',
                    'url' => 'https://reddit.com/r/expats',
                    'snippet' => 'Real experiences from Americans who have relocated to France.',
                    'date' => date( 'Y' ),
                ),
            ),
            'sources_to_cite' => array_merge(
                $official_sources[ $context ] ?? array( 'service-public.fr' ),
                array_slice( $community_sources, 0, 3 )
            ),
        );
    }

    /**
     * Format web search results for AI context
     *
     * @param array $web_results Web search results
     * @return string Formatted context string
     */
    private function format_web_results_for_ai( $web_results ) {
        $context = "";

        if ( ! empty( $web_results['official'] ) ) {
            $context .= "\n\n**OFFICIAL SOURCES (verify current info):**\n";
            foreach ( $web_results['official'] as $result ) {
                $context .= "- [{$result['title']}]({$result['url']}): {$result['snippet']}\n";
            }
        }

        if ( ! empty( $web_results['community'] ) ) {
            $context .= "\n\n**COMMUNITY EXPERIENCES (real-world insights):**\n";
            foreach ( $web_results['community'] as $result ) {
                $context .= "- [{$result['title']}]({$result['url']}): {$result['snippet']}\n";
            }
        }

        if ( ! empty( $web_results['sources_to_cite'] ) ) {
            $context .= "\n\n**When answering, consider referencing these sources:**\n";
            $context .= implode( ', ', $web_results['sources_to_cite'] );
        }

        return $context;
    }

    /**
     * Generate response using KB content enhanced with AI
     *
     * @param string $message          User message
     * @param string $context          Context/category
     * @param bool   $include_practice Include real-world tips
     * @param array  $kb_results       KB search results
     * @param array  $user_context     User portal context (profile, docs, tasks)
     * @return string Response
     */
    private function generate_kb_enhanced_response( $message, $context, $include_practice, $kb_results, $user_context = array() ) {
        // Build context from KB articles
        $kb_context = "";
        if ( ! empty( $kb_results ) ) {
            $kb_context = "**VERIFIED KNOWLEDGE BASE (use this as primary source):**\n\n";
            foreach ( $kb_results as $result ) {
                $kb_context .= "---\n";
                $kb_context .= "**{$result['title']}** (Category: {$result['category']})\n";
                $kb_context .= $result['content'] . "\n";
            }
        }

        // Search web for additional info (official sources + community experiences)
        $web_results = array();
        if ( $include_practice ) {
            $web_results = $this->search_web_for_info( $message, $context );
        }
        $web_context = $this->format_web_results_for_ai( $web_results );

        // Add user portal context for personalization
        $user_context_text = "";
        if ( ! empty( $user_context['summary'] ) ) {
            $user_context_text = "\n\n**THIS MEMBER'S CURRENT STATUS:**\n" . $user_context['summary'] . "\n";
        }

        // Combine KB, web, and user context
        $full_context = $kb_context . $web_context . $user_context_text;

        // If no KB results and no web results, fall back to basic AI
        if ( empty( $kb_results ) && empty( $web_results['official'] ) && empty( $web_results['community'] ) ) {
            return $this->generate_chat_response( $message, $context, $include_practice );
        }

        // Try AI-enhanced response with both KB and web context
        $ai_response = $this->call_ai_with_full_context( $message, $context, $include_practice, $full_context, ! empty( $web_results ), $user_context );

        if ( ! is_wp_error( $ai_response ) && ! empty( $ai_response ) ) {
            return $ai_response;
        }

        // Fallback: Return the most relevant KB article content directly
        if ( ! empty( $kb_results ) ) {
            $best_match = $kb_results[0];
            $response   = $best_match['content'];

            if ( $include_practice ) {
                $response .= "\n\n**Practical tip:** For the most current information and real-world experiences, check official French government websites and expat communities like Reddit r/expats or Expat.com forums.";
            }

            return $response;
        }

        return "I couldn't find specific information about this topic. Please check official French sources like service-public.fr or france-visas.gouv.fr for accurate information.";
    }

    /**
     * Call AI with full context (KB + web sources)
     *
     * @param string $message          User message
     * @param string $context          Category context
     * @param bool   $include_practice Include real-world tips
     * @param string $full_context     Combined KB and web context
     * @param bool   $has_web_results  Whether web results were found
     * @param array  $user_context     User portal context for personalization
     * @return string|WP_Error AI response or error
     */
    private function call_ai_with_full_context( $message, $context, $include_practice, $full_context, $has_web_results = false, $user_context = array() ) {
        if ( ! class_exists( 'FRAMT_Main_Plugin_Bridge' ) ) {
            return new WP_Error( 'no_bridge', 'AI bridge not available' );
        }

        $bridge = FRAMT_Main_Plugin_Bridge::get_instance();

        $practice_instruction = $include_practice
            ? "\n\n**REAL-WORLD INSIGHTS:** Include an \"**In Practice**\" section covering:\n- Common experiences from expats and forums\n- Practical tips not in official documentation\n- Grey areas and how rules are actually applied\n- Things that surprise or catch people off guard\nCite sources like \"(Source: Reddit r/expats)\" or \"(Source: expat forums)\" when sharing community insights."
            : '';

        // Build personalization instructions based on user context
        $personalization = "";
        if ( ! empty( $user_context['profile'] ) ) {
            $profile = $user_context['profile'];
            $visa_type = $profile['visa_type'] ?? '';

            $personalization = "\n\n**PERSONALIZATION REQUIREMENTS (THIS IS A PREMIUM MEMBERSHIP PORTAL):**
You are providing PERSONALIZED guidance to this specific member. Be proactive and helpful:

1. **Reference their profile data** - Use their name if available, reference their visa type, and tailor advice to their situation.

2. **If they have a visa type selected**: Explain how the information specifically applies to their {$visa_type} visa. If they ask about visa types, confirm their current selection and explain why it may or may not be the best fit.

3. **If NO visa type selected**: Proactively help them choose. Ask clarifying questions about their situation (employment, retirement, family) to recommend the best visa type.

4. **Reference their progress**:
   - If they have documents uploaded, acknowledge this and identify what's still needed
   - If they have NO documents, encourage them to start gathering required documents
   - If they have tasks completed, congratulate their progress
   - If tasks are pending, gently remind them of next steps

5. **Be conversational and supportive** - This is a premium service. Make them feel supported, not like they're reading a FAQ.

6. **Ask confirming questions** - \"Based on your profile, you're applying for a Visitor visa. Is that still your plan?\" or \"I see you haven't uploaded any documents yet. Would you like me to explain what you'll need to gather first?\"

7. **Guide to next steps** - Always end with a clear next action they should take.";
        }

        $system_prompt = "You are a helpful assistant for AMERICANS relocating to France. This platform specifically serves US citizens applying for French visas from the United States.

**VERIFICATION REQUIREMENTS (CRITICAL - READ CAREFULLY):**
Before answering ANY legal, technical, or procedural question:
1. Check the VERIFIED KNOWLEDGE BASE below first - this is curated for Americans
2. Check OFFICIAL SOURCES from web search (if included below) - these are current government/official sites
3. If found in knowledge base OR official web sources: Use that verified information and cite the source
4. If NOT found: Say \"I recommend checking with your French consulate or [relevant official site]\"
5. NEVER use your training data for specific requirements - ONLY use sources provided below
6. When requirements VARY BY CONSULATE, say \"Check your specific consulate's requirements\"

**USE WEB SEARCH RESULTS:**
The context below may include OFFICIAL SOURCES from web search with current information from:
- French government sites (france-visas.gouv.fr, service-public.fr)
- US State Department
- French consulate websites
USE these official sources to provide accurate, current information. Cite URLs when available.

**DO NOT INVENT:**
- Requirements not found in knowledge base OR official web sources below
- Fees, timelines, or procedures not explicitly stated in the sources
- Legal requirements from your training data - use ONLY the verified sources

**AMERICAN-SPECIFIC RULES (VERIFIED):**
- Document TRANSLATIONS: Americans applying FROM THE US do NOT need translations for English documents. French consulates accept English documents. Translations are ONLY needed: (1) for non-English/French documents, OR (2) when RENEWING from France.
- Background checks: Requirements VARY BY CONSULATE. Most require FBI only. Do NOT say state/local checks are required unless member's specific consulate requires them.
- Apostilles ARE required for US vital documents (birth certificates, marriage certificates, etc.).
{$personalization}
{$practice_instruction}

**FORMAT:**
- Use clear headers with ## for main sections
- Use **bold** for important terms and key facts
- Use bullet points for lists
- Include relevant official website URLs
- Keep paragraphs short and scannable
- End with a \"**Next Steps**\" section with 1-3 actionable items

{$full_context}

Now answer the following question:";

        $full_prompt = $system_prompt . "\n\nUser question: " . $message;

        $result = $bridge->proxy_api_request( $full_prompt, $context );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $result['response'] ?? new WP_Error( 'empty_response', 'AI returned empty response' );
    }

    /**
     * Call AI with KB context
     *
     * @param string $message          User message
     * @param string $context          Category context
     * @param bool   $include_practice Include real-world tips
     * @param string $kb_context       Knowledge base context
     * @return string|WP_Error AI response or error
     */
    private function call_ai_with_kb_context( $message, $context, $include_practice, $kb_context ) {
        if ( ! class_exists( 'FRAMT_Main_Plugin_Bridge' ) ) {
            return new WP_Error( 'no_bridge', 'AI bridge not available' );
        }

        $bridge = FRAMT_Main_Plugin_Bridge::get_instance();

        $practice_instruction = $include_practice
            ? ' Include practical, real-world tips based on the knowledge base content and common experiences.'
            : '';

        $system_prompt = "You are a helpful assistant for AMERICANS relocating to France. This platform specifically serves US citizens applying for French visas from the United States.

**VERIFICATION REQUIREMENTS (CRITICAL):**
1. ONLY state facts that appear in the VERIFIED KNOWLEDGE BASE below
2. Do NOT use your training data for specific facts about regulations, fees, requirements, or procedures
3. If the knowledge base lists specific items, use EXACTLY that list - do not add, remove, or modify
4. If something isn't in the knowledge base, say \"I recommend checking with your French consulate\" rather than guessing
5. NEVER invent requirements, timelines, fees, or procedures

**DO NOT MAKE UP:**
- Document requirements not in the knowledge base
- Background check requirements (these VARY BY CONSULATE)
- Processing times or fees not explicitly stated
- Legal requirements from your training data

**AMERICAN-SPECIFIC RULES (VERIFIED):**
- Document TRANSLATIONS: Americans applying FROM THE US do NOT need translations for English documents. Translations only needed for non-English/French docs OR when renewing from France.
- Background checks: VARY BY CONSULATE. Most require FBI only. Do NOT prescribe state/local checks.
- Apostilles ARE required for US vital documents.
{$practice_instruction}

Keep your response concise but comprehensive. Use **bold** for important terms. If the knowledge base mentions official websites, include them.

{$kb_context}

Now answer the following question using ONLY the information above:";

        $full_prompt = $system_prompt . "\n\nUser question: " . $message;

        $result = $bridge->proxy_api_request( $full_prompt, $context );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $result['response'] ?? new WP_Error( 'empty_response', 'AI returned empty response' );
    }

    /**
     * Generate follow-up response using AI directly with conversation history
     *
     * @param string $message          User message
     * @param string $context          Category context
     * @param bool   $include_practice Include real-world tips
     * @param array  $history          Conversation history
     * @param array  $user_context     User portal context for personalization
     * @return string Response
     */
    private function generate_follow_up_response( $message, $context, $include_practice, $history, $user_context = array() ) {
        if ( ! class_exists( 'FRAMT_Main_Plugin_Bridge' ) ) {
            return $this->generate_fallback_response( $message, $context, $include_practice );
        }

        $bridge = FRAMT_Main_Plugin_Bridge::get_instance();

        // Build conversation context from recent history (last 6 messages)
        $recent_history    = array_slice( $history, -6 );
        $conversation_text = "";

        foreach ( $recent_history as $msg ) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $conversation_text .= "{$role}: {$msg['message']}\n\n";
        }

        $practice_instruction = $include_practice
            ? ' Include practical real-world tips where relevant.'
            : '';

        // Include user context summary for personalization
        $user_context_text = "";
        if ( ! empty( $user_context['summary'] ) ) {
            $user_context_text = "\n\nMEMBER'S CURRENT STATUS:\n" . $user_context['summary'] . "\n";
        }

        $system_prompt = "You are a helpful assistant for AMERICANS relocating to France. This platform specifically serves US citizens. Continue the following conversation naturally, answering the user's follow-up question based on the previous context.{$practice_instruction}

**VERIFICATION RULES (CRITICAL):**
- Do NOT make up specific facts about regulations, fees, timelines, or requirements
- If the previous conversation contained verified facts, use those
- If you don't have verified information, say \"I recommend checking with your French consulate\" rather than guessing
- Requirements often VARY BY CONSULATE - always encourage members to verify with their specific consulate

**DO NOT INVENT:**
- Background check requirements (vary by consulate - most require FBI only)
- Document requirements not previously discussed
- Processing times or fees you're not certain about
- Legal requirements from your training data

**AMERICAN-SPECIFIC (VERIFIED):**
- Translations NOT required for English documents when applying from US
- Background checks: VARY BY CONSULATE. Do NOT prescribe state/local checks unless verified.

**PERSONALIZATION:**
- Be helpful, supportive, and reference the member's situation
- Tailor responses to their visa type if selected
- Reference their progress when relevant
- End with a helpful next step
{$user_context_text}

Keep your response concise but helpful. Use **bold** for important terms. Use ## headers for sections.

Previous conversation:
{$conversation_text}

Now respond to this follow-up:";

        $full_prompt = $system_prompt . "\n\nUser: " . $message;

        $result = $bridge->proxy_api_request( $full_prompt, $context );

        if ( is_wp_error( $result ) || empty( $result['response'] ) ) {
            return $this->generate_fallback_response( $message, $context, $include_practice );
        }

        return $result['response'];
    }

    /**
     * Format KB search results as sources for the response
     *
     * @param array $kb_results KB search results
     * @return array Formatted sources
     */
    private function format_kb_sources( $kb_results ) {
        $sources = array();

        foreach ( $kb_results as $result ) {
            $source = array(
                'title'     => $result['title'],
                'category'  => $result['category'],
                'relevance' => round( $result['relevance'], 2 ),
            );

            // Include official source URLs if available
            if ( ! empty( $result['sources'] ) && is_array( $result['sources'] ) ) {
                $source['url'] = $result['sources'][0]['url'] ?? '';
            }

            $sources[] = $source;
        }

        return $sources;
    }

    /**
     * Format web search results as sources
     *
     * @param array $web_results Web search results
     * @return array Formatted sources
     */
    private function format_web_sources( $web_results ) {
        $sources = array();

        // Add official sources
        if ( ! empty( $web_results['official'] ) ) {
            foreach ( $web_results['official'] as $result ) {
                $sources[] = array(
                    'title'    => $result['title'] ?? 'Official Source',
                    'category' => 'official',
                    'url'      => $result['url'] ?? '',
                    'type'     => 'official',
                );
            }
        }

        // Add community sources
        if ( ! empty( $web_results['community'] ) ) {
            foreach ( $web_results['community'] as $result ) {
                $sources[] = array(
                    'title'    => $result['title'] ?? 'Community Experience',
                    'category' => 'community',
                    'url'      => $result['url'] ?? '',
                    'type'     => 'community',
                );
            }
        }

        return $sources;
    }

    /**
     * Get comprehensive user context for personalized guide responses
     *
     * Gathers profile, documents, tasks, and checklist data to enable
     * proactive, personalized AI responses in the guides.
     *
     * @param int $user_id User ID
     * @return array User context data
     */
    private function get_user_portal_context( $user_id ) {
        $context = array(
            'profile'    => array(),
            'documents'  => array(),
            'tasks'      => array(),
            'checklists' => array(),
            'summary'    => '',
        );

        // Get user profile
        if ( class_exists( 'FRAMT_Profile' ) ) {
            $profile_instance = FRAMT_Profile::get_instance();
            $profile = $profile_instance->get_profile( $user_id );

            if ( ! empty( $profile ) ) {
                $context['profile'] = array(
                    'visa_type'           => $profile['visa_type'] ?? '',
                    'legal_first_name'    => $profile['legal_first_name'] ?? '',
                    'legal_last_name'     => $profile['legal_last_name'] ?? '',
                    'nationality'         => $profile['nationality'] ?? 'American',
                    'target_region'       => $profile['target_region'] ?? '',
                    'target_city'         => $profile['target_city'] ?? '',
                    'planned_move_date'   => $profile['planned_move_date'] ?? '',
                    'applicants'          => $profile['applicants'] ?? 'single',
                    'has_children'        => $profile['has_children'] ?? '',
                    'has_pets'            => $profile['has_pets'] ?? '',
                    'employment_status'   => $profile['employment_status'] ?? '',
                    'income_source'       => $profile['income_source'] ?? '',
                    'french_proficiency'  => $profile['french_proficiency'] ?? '',
                    'birth_state'         => $profile['birth_state'] ?? '',
                    'marriage_state'      => $profile['marriage_state'] ?? '',
                );
            }
        }

        // Get user's uploaded documents
        global $wpdb;
        $files_table = $wpdb->prefix . 'framt_files';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$files_table}'" ) === $files_table ) {
            $documents = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, original_name, category, uploaded_at FROM {$files_table} WHERE user_id = %d ORDER BY uploaded_at DESC",
                $user_id
            ), ARRAY_A );

            $context['documents'] = array(
                'count'      => count( $documents ),
                'categories' => array(),
                'recent'     => array(),
            );

            foreach ( $documents as $doc ) {
                $cat = $doc['category'] ?? 'general';
                if ( ! isset( $context['documents']['categories'][ $cat ] ) ) {
                    $context['documents']['categories'][ $cat ] = 0;
                }
                $context['documents']['categories'][ $cat ]++;
            }

            // Get 5 most recent document names
            $context['documents']['recent'] = array_slice( array_map( function( $d ) {
                return $d['original_name'];
            }, $documents ), 0, 5 );
        }

        // Get user's tasks and completion status
        $tasks_table = $wpdb->prefix . 'framt_tasks';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tasks_table}'" ) === $tasks_table ) {
            $tasks = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, title, status, category FROM {$tasks_table} WHERE user_id = %d",
                $user_id
            ), ARRAY_A );

            $context['tasks'] = array(
                'total'     => count( $tasks ),
                'completed' => 0,
                'pending'   => 0,
                'by_status' => array(),
                'by_category' => array(),
            );

            foreach ( $tasks as $task ) {
                $status = $task['status'] ?? 'pending';
                $cat = $task['category'] ?? 'general';

                if ( $status === 'completed' || $status === 'done' ) {
                    $context['tasks']['completed']++;
                } else {
                    $context['tasks']['pending']++;
                }

                if ( ! isset( $context['tasks']['by_status'][ $status ] ) ) {
                    $context['tasks']['by_status'][ $status ] = 0;
                }
                $context['tasks']['by_status'][ $status ]++;

                if ( ! isset( $context['tasks']['by_category'][ $cat ] ) ) {
                    $context['tasks']['by_category'][ $cat ] = array( 'total' => 0, 'completed' => 0 );
                }
                $context['tasks']['by_category'][ $cat ]['total']++;
                if ( $status === 'completed' || $status === 'done' ) {
                    $context['tasks']['by_category'][ $cat ]['completed']++;
                }
            }
        }

        // Get checklist progress for visa-related checklists
        $checklist_types = array( 'visitor', 'talent-passport', 'student', 'work', 'entrepreneur', 'family' );
        foreach ( $checklist_types as $type ) {
            $progress = get_user_meta( $user_id, "fra_checklist_{$type}_progress", true );
            if ( ! empty( $progress ) && is_array( $progress ) ) {
                $checked = count( array_filter( $progress ) );
                $total = count( $progress );
                $context['checklists'][ $type ] = array(
                    'checked' => $checked,
                    'total'   => $total,
                    'percent' => $total > 0 ? round( ( $checked / $total ) * 100 ) : 0,
                );
            }
        }

        // Build a human-readable summary for the AI
        $context['summary'] = $this->build_user_context_summary( $context );

        return $context;
    }

    /**
     * Build a human-readable summary of user context for AI prompts
     *
     * @param array $context User context data
     * @return string Formatted summary
     */
    private function build_user_context_summary( $context ) {
        $lines = array();
        $profile = $context['profile'];

        // Profile summary
        if ( ! empty( $profile['legal_first_name'] ) ) {
            $lines[] = "Member name: {$profile['legal_first_name']} {$profile['legal_last_name']}";
        }

        if ( ! empty( $profile['visa_type'] ) ) {
            $visa_labels = array(
                'visitor' => 'Long-Stay Visitor Visa (VLS-TS Visiteur)',
                'talent-passport' => 'Talent Passport (Passeport Talent)',
                'student' => 'Student Visa',
                'work' => 'Work Visa (Salarié)',
                'entrepreneur' => 'Entrepreneur Visa',
                'family' => 'Family Reunification Visa',
                'undecided' => 'Not yet decided',
            );
            $visa_label = $visa_labels[ $profile['visa_type'] ] ?? ucfirst( $profile['visa_type'] );
            $lines[] = "Selected visa type: {$visa_label}";
        } else {
            $lines[] = "Visa type: NOT YET SELECTED - member needs guidance on choosing";
        }

        if ( ! empty( $profile['applicants'] ) ) {
            $applicant_labels = array(
                'single' => 'Applying alone',
                'couple' => 'Applying with spouse/partner',
                'family' => 'Applying with family (includes children)',
            );
            $lines[] = $applicant_labels[ $profile['applicants'] ] ?? $profile['applicants'];
        }

        if ( ! empty( $profile['target_region'] ) || ! empty( $profile['target_city'] ) ) {
            $location = trim( ( $profile['target_city'] ?? '' ) . ', ' . ( $profile['target_region'] ?? '' ), ', ' );
            $lines[] = "Target location in France: {$location}";
        }

        if ( ! empty( $profile['planned_move_date'] ) ) {
            $lines[] = "Planned move date: {$profile['planned_move_date']}";
        }

        if ( ! empty( $profile['employment_status'] ) ) {
            $lines[] = "Employment status: {$profile['employment_status']}";
        }

        if ( ! empty( $profile['has_pets'] ) && $profile['has_pets'] !== 'no' ) {
            $lines[] = "Has pets: {$profile['has_pets']} (will need pet relocation guidance)";
        }

        // Document summary
        $docs = $context['documents'];
        if ( $docs['count'] > 0 ) {
            $lines[] = "\nDocuments uploaded: {$docs['count']} total";
            if ( ! empty( $docs['categories'] ) ) {
                $cat_list = array();
                foreach ( $docs['categories'] as $cat => $count ) {
                    $cat_list[] = "{$cat}: {$count}";
                }
                $lines[] = "Document categories: " . implode( ', ', $cat_list );
            }
        } else {
            $lines[] = "\nDocuments uploaded: NONE - member should start gathering documents";
        }

        // Task summary
        $tasks = $context['tasks'];
        if ( $tasks['total'] > 0 ) {
            $lines[] = "\nTasks: {$tasks['completed']} of {$tasks['total']} completed (" . round( ( $tasks['completed'] / $tasks['total'] ) * 100 ) . "%)";
            if ( $tasks['pending'] > 0 ) {
                $lines[] = "Pending tasks: {$tasks['pending']}";
            }
        } else {
            $lines[] = "\nTasks: None created yet - member should review task checklist";
        }

        // Checklist summary
        $visa_type = $profile['visa_type'] ?? '';
        if ( ! empty( $visa_type ) && isset( $context['checklists'][ $visa_type ] ) ) {
            $cl = $context['checklists'][ $visa_type ];
            $lines[] = "\nVisa checklist progress ({$visa_type}): {$cl['checked']} of {$cl['total']} items completed ({$cl['percent']}%)";
        }

        return implode( "\n", $lines );
    }

    /**
     * Get chat categories
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_chat_categories( $request ) {
        $categories = array(
            array(
                'id'          => 'visas',
                'title'       => 'Visa & Immigration',
                'icon'        => 'FileText',
                'description' => 'Questions about French visas, residence permits, and immigration procedures',
                'topics'      => array(
                    array( 'id' => 'vls-ts', 'title' => 'VLS-TS Long Stay Visa', 'keywords' => array( 'visa', 'vls', 'long stay' ), 'is_premium' => false ),
                    array( 'id' => 'ofii', 'title' => 'OFII Validation Process', 'keywords' => array( 'ofii', 'validate', 'stamp' ), 'is_premium' => false ),
                    array( 'id' => 'renewal', 'title' => 'Visa Renewal', 'keywords' => array( 'renew', 'extend', 'titre de séjour' ), 'is_premium' => true ),
                ),
            ),
            array(
                'id'          => 'healthcare',
                'title'       => 'Healthcare',
                'icon'        => 'Heart',
                'description' => 'French healthcare system, Carte Vitale, and medical coverage',
                'topics'      => array(
                    array( 'id' => 'carte-vitale', 'title' => 'Carte Vitale Registration', 'keywords' => array( 'carte vitale', 'cpam', 'health card' ), 'is_premium' => false ),
                    array( 'id' => 'mutuelle', 'title' => 'Complementary Insurance (Mutuelle)', 'keywords' => array( 'mutuelle', 'insurance', 'top-up' ), 'is_premium' => false ),
                    array( 'id' => 'doctors', 'title' => 'Finding Doctors', 'keywords' => array( 'doctor', 'médecin', 'appointment' ), 'is_premium' => false ),
                ),
            ),
            array(
                'id'          => 'property',
                'title'       => 'Housing & Property',
                'icon'        => 'Home',
                'description' => 'Renting, buying property, and understanding French housing',
                'topics'      => array(
                    array( 'id' => 'renting', 'title' => 'Renting in France', 'keywords' => array( 'rent', 'apartment', 'lease', 'bail' ), 'is_premium' => false ),
                    array( 'id' => 'buying', 'title' => 'Buying Property', 'keywords' => array( 'buy', 'purchase', 'notaire', 'mortgage' ), 'is_premium' => true ),
                    array( 'id' => 'utilities', 'title' => 'Setting Up Utilities', 'keywords' => array( 'electricity', 'gas', 'water', 'internet' ), 'is_premium' => false ),
                ),
            ),
            array(
                'id'          => 'banking',
                'title'       => 'Banking & Finance',
                'icon'        => 'Building',
                'description' => 'French bank accounts, money transfers, and financial matters',
                'topics'      => array(
                    array( 'id' => 'bank-account', 'title' => 'Opening a Bank Account', 'keywords' => array( 'bank', 'account', 'rib' ), 'is_premium' => false ),
                    array( 'id' => 'transfers', 'title' => 'International Transfers', 'keywords' => array( 'transfer', 'wire', 'exchange' ), 'is_premium' => false ),
                    array( 'id' => 'credit', 'title' => 'Credit in France', 'keywords' => array( 'credit', 'loan', 'mortgage' ), 'is_premium' => true ),
                ),
            ),
            array(
                'id'          => 'taxes',
                'title'       => 'Taxes',
                'icon'        => 'DollarSign',
                'description' => 'French taxation, US-France tax treaty, and filing requirements',
                'topics'      => array(
                    array( 'id' => 'tax-residency', 'title' => 'Tax Residency', 'keywords' => array( 'residency', 'fiscal', 'domicile' ), 'is_premium' => false ),
                    array( 'id' => 'tax-treaty', 'title' => 'US-France Tax Treaty', 'keywords' => array( 'treaty', 'double taxation', 'totalization' ), 'is_premium' => true ),
                    array( 'id' => 'filing', 'title' => 'Tax Filing', 'keywords' => array( 'file', 'declaration', 'impôts' ), 'is_premium' => true ),
                ),
            ),
            array(
                'id'          => 'driving',
                'title'       => 'Driving',
                'icon'        => 'Car',
                'description' => 'Driving licenses, car registration, and transportation',
                'topics'      => array(
                    array( 'id' => 'license-exchange', 'title' => 'License Exchange', 'keywords' => array( 'license', 'exchange', 'permis' ), 'is_premium' => false ),
                    array( 'id' => 'car-registration', 'title' => 'Car Registration', 'keywords' => array( 'registration', 'carte grise', 'immatriculation' ), 'is_premium' => false ),
                    array( 'id' => 'insurance', 'title' => 'Car Insurance', 'keywords' => array( 'insurance', 'assurance', 'vehicle' ), 'is_premium' => false ),
                ),
            ),
            array(
                'id'          => 'settling',
                'title'       => 'Settling In',
                'icon'        => 'MapPin',
                'description' => 'Daily life, culture, language, and practical tips for living in France',
                'topics'      => array(
                    array( 'id' => 'language', 'title' => 'Learning French', 'keywords' => array( 'french', 'language', 'learn', 'class' ), 'is_premium' => false ),
                    array( 'id' => 'culture', 'title' => 'French Culture', 'keywords' => array( 'culture', 'customs', 'etiquette' ), 'is_premium' => false ),
                    array( 'id' => 'community', 'title' => 'Expat Communities', 'keywords' => array( 'expat', 'community', 'groups', 'meetup' ), 'is_premium' => false ),
                ),
            ),
        );

        return rest_ensure_response( $categories );
    }

    /**
     * Search chat topics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function search_chat_topics( $request ) {
        $query = $request->get_param( 'q' ) ?? '';

        $topics = array(
            array( 'id' => '1', 'title' => 'How to validate VLS-TS visa?', 'category' => 'visas', 'is_premium' => false ),
            array( 'id' => '2', 'title' => 'Opening a French bank account', 'category' => 'banking', 'is_premium' => false ),
            array( 'id' => '3', 'title' => 'Registering for Carte Vitale', 'category' => 'healthcare', 'is_premium' => false ),
            array( 'id' => '4', 'title' => 'Finding rental accommodation', 'category' => 'property', 'is_premium' => false ),
            array( 'id' => '5', 'title' => 'OFII appointment process', 'category' => 'visas', 'is_premium' => false ),
            array( 'id' => '6', 'title' => 'US-France tax treaty overview', 'category' => 'taxes', 'is_premium' => true ),
            array( 'id' => '7', 'title' => 'Exchanging driving license', 'category' => 'driving', 'is_premium' => false ),
            array( 'id' => '8', 'title' => 'International money transfers', 'category' => 'banking', 'is_premium' => false ),
        );

        if ( ! empty( $query ) ) {
            $topics = array_filter( $topics, function( $t ) use ( $query ) {
                return stripos( $t['title'], $query ) !== false;
            } );
        }

        return rest_ensure_response( array(
            'results' => array_values( $topics ),
        ) );
    }

    /**
     * Generate chat response based on message and context
     *
     * @param string $message          User message
     * @param string $context          Context/category
     * @param bool   $include_practice Include real-world practice insights
     * @return string Response
     */
    private function generate_chat_response( $message, $context, $include_practice = true ) {
        // Try to use the AI through the main plugin bridge
        $ai_response = $this->call_ai_for_chat( $message, $context, $include_practice );

        if ( ! is_wp_error( $ai_response ) && ! empty( $ai_response ) ) {
            return $ai_response;
        }

        // Fallback to keyword-based responses if AI is unavailable
        return $this->generate_fallback_response( $message, $context, $include_practice );
    }

    /**
     * Call the AI service for chat responses
     *
     * @param string $message          User message
     * @param string $context          Context/category
     * @param bool   $include_practice Include real-world practice insights
     * @return string|WP_Error AI response or error
     */
    private function call_ai_for_chat( $message, $context, $include_practice = true ) {
        // Check if the main plugin bridge is available
        if ( ! class_exists( 'FRAMT_Main_Plugin_Bridge' ) ) {
            return new WP_Error( 'no_bridge', 'AI bridge not available' );
        }

        $bridge = FRAMT_Main_Plugin_Bridge::get_instance();

        // Build the prompt with context
        $category_context = '';
        if ( ! empty( $context ) && $context !== 'general' ) {
            $category_labels = array(
                'visas'     => 'visa and immigration',
                'healthcare' => 'French healthcare system',
                'property'  => 'housing and property',
                'banking'   => 'banking and finance',
                'taxes'     => 'taxation',
                'driving'   => 'driving and transportation',
                'settling'  => 'settling in and daily life',
            );
            $category_context = isset( $category_labels[ $context ] )
                ? " The user is specifically asking about {$category_labels[ $context ]}."
                : '';
        }

        $practice_instruction = $include_practice
            ? ' Include practical, real-world tips from people who have gone through the process - not just official requirements.'
            : '';

        $system_prompt = "You are a helpful assistant specializing in helping people relocate to France.{$category_context}{$practice_instruction}

IMPORTANT ACCURACY RULES:
1. For specific facts about regulations, fees, eligible states/countries, or official requirements - be conservative. If you're not certain, say \"I recommend checking the official source\" rather than stating potentially outdated information.
2. For license exchange specifically: Some US states DO have reciprocal agreements with France (including PA, SC, TX, FL, and others). Do NOT claim that no US states have agreements - this is incorrect.
3. Regulations change frequently. Always recommend users verify current requirements with official French government websites.
4. Use **bold** for important terms. If mentioning official websites, use their full URLs.

Focus on practical advice while being careful not to state incorrect facts. When uncertain about specifics, refer users to Service-Public.fr or France-Visas.gouv.fr.";

        $full_prompt = $system_prompt . "\n\nUser question: " . $message;

        $result = $bridge->proxy_api_request( $full_prompt, $context );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( ! empty( $result['response'] ) ) {
            return $result['response'];
        }

        return new WP_Error( 'empty_response', 'AI returned empty response' );
    }

    /**
     * Generate fallback keyword-based response when AI is unavailable
     *
     * @param string $message          User message
     * @param string $context          Context/category
     * @param bool   $include_practice Include real-world practice insights
     * @return string Response
     */
    private function generate_fallback_response( $message, $context, $include_practice = true ) {
        $lower_message = strtolower( $message );
        $practice_note = $include_practice ? "\n\n**Real-world tip:** " : '';

        if ( strpos( $lower_message, 'vls-ts' ) !== false || strpos( $lower_message, 'validate' ) !== false ) {
            $response = 'To validate your VLS-TS visa, you must complete the online validation within 3 months of arriving in France. Visit the official website at administration-etrangers-en-france.interieur.gouv.fr, create an account, and follow the validation process. You\'ll need to pay the OFII fee online.';
            if ( $include_practice ) {
                $response .= $practice_note . 'Many users report the website can be slow or have issues. Try early morning (French time) for best results. Keep screenshots of your confirmation as backup.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'bank' ) !== false || strpos( $lower_message, 'account' ) !== false ) {
            $response = 'To open a French bank account, you\'ll typically need: valid ID/passport, proof of address (even a hotel confirmation works initially), and proof of income or student status. Consider online banks like Boursorama, N26, or Revolut for easier onboarding, or traditional banks like BNP Paribas or Société Générale for full services.';
            if ( $include_practice ) {
                $response .= $practice_note . 'Online banks like Boursorama often have the easiest onboarding for newcomers. Traditional banks may require an appointment and can be stricter about documentation.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'carte vitale' ) !== false || strpos( $lower_message, 'health' ) !== false ) {
            $response = 'To get your Carte Vitale, first register with CPAM (ameli.fr). You\'ll need: your validated visa, birth certificate (translated), proof of address, and RIB. The process can take 2-3 months. In the meantime, you can get an attestation from Ameli to access healthcare.';
            if ( $include_practice ) {
                $response .= $practice_note . 'Create your Ameli account as soon as you have your CPAM number. The attestation (paper) works just as well as the card for healthcare reimbursements.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'document' ) !== false || strpos( $lower_message, 'visa' ) !== false ) {
            $response = 'For your visa application, you\'ll need several key documents: passport valid for at least 3 months beyond your planned stay, proof of financial resources, proof of accommodation, health insurance, and completed application forms. Each visa type has specific additional requirements.';
            if ( $include_practice ) {
                $response .= $practice_note . 'Always bring extra copies of everything. French administration loves paperwork, and having duplicates can save you from making multiple trips.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'tax' ) !== false || strpos( $lower_message, 'impôt' ) !== false ) {
            $response = 'As a resident in France, you\'ll be subject to French income tax. The tax year runs from January to December, and declarations are typically due in May-June for the previous year. The US-France tax treaty helps prevent double taxation.';
            if ( $include_practice ) {
                $response .= $practice_note . 'Consider working with a tax professional who understands both US and French tax systems, especially for your first year. The FEIE (Foreign Earned Income Exclusion) can be valuable for Americans.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'license' ) !== false || strpos( $lower_message, 'drive' ) !== false || strpos( $lower_message, 'permis' ) !== false || strpos( $lower_message, 'exchange' ) !== false ) {
            $response = "**US License Exchange with France**\n\nUS driving licenses can be used in France for up to one year. After that, you can exchange it for a French license IF your state has a reciprocal agreement.\n\n**Eligible US States (2025):**\nArkansas, Colorado, Connecticut, Delaware, Florida, Illinois, Iowa, Kansas, Kentucky, Maryland, Massachusetts, Michigan, New Hampshire, Ohio, Oklahoma, Pennsylvania, South Carolina, Texas, Virginia, Wisconsin\n\n**If your state is eligible:** Apply within first year, submit license, residence proof, passport, photos. Fee ~€25, processing 2-6 months.\n\n**If NOT eligible:** Must pass French driving test (code + conduite). Cost €1,500-2,500 with driving school.\n\nSee: Service-Public.fr for current list.";
            if ( $include_practice ) {
                $response .= $practice_note . 'Start the exchange process early - it can take several months. Gather your documents before the 1-year deadline to avoid having to retake the driving test.';
            }
            return $response;
        }

        if ( strpos( $lower_message, 'rent' ) !== false || strpos( $lower_message, 'apartment' ) !== false || strpos( $lower_message, 'housing' ) !== false ) {
            $response = 'Renting in France requires a dossier with: ID, proof of income (3x rent is typical), employer letter, previous landlord references, and a French guarantor (garant). Without a French work contract, consider services like GarantMe or Visale.';
            if ( $include_practice ) {
                $response .= $practice_note . 'The rental market moves fast, especially in Paris. Be ready with your complete dossier and respond quickly to listings. Some landlords prefer international guarantor services to French garants.';
            }
            return $response;
        }

        // Default response
        $category_display = ! empty( $context ) && $context !== 'general' ? $context : 'relocating to France';
        return 'Thank you for your question about ' . $category_display . '. I can help you with various aspects of relocating to France including visa applications, healthcare registration, banking, housing, and administrative procedures. Could you provide more details about what specific information you\'re looking for?';
    }

    // =========================================================================
    // Membership Methods (MemberPress Integration)
    // =========================================================================

    /**
     * Get membership information
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_membership_info( $request ) {
        $user_id = get_current_user_id();

        $response = array(
            'user_id'     => $user_id,
            'memberships' => array(),
            'is_active'   => false,
        );

        // Check if MemberPress is active
        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user    = new MeprUser( $user_id );
            $active_subs  = $mepr_user->active_product_subscriptions();
            $transactions = $mepr_user->transactions();

            $response['is_active'] = ! empty( $active_subs );

            foreach ( $active_subs as $product_id ) {
                $product = new MeprProduct( $product_id );
                $response['memberships'][] = array(
                    'id'          => $product_id,
                    'name'        => $product->post_title,
                    'description' => $product->post_excerpt,
                    'price'       => $product->price,
                    'period'      => $product->period,
                    'period_type' => $product->period_type,
                );
            }
        } else {
            // Fallback for when MemberPress is not installed
            $response['memberships'][] = array(
                'id'     => 0,
                'name'   => 'Basic Access',
                'status' => 'active',
            );
            $response['is_active'] = true;
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get user subscriptions
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_subscriptions( $request ) {
        $user_id = get_current_user_id();

        $subscriptions = array();

        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user = new MeprUser( $user_id );
            $subs      = $mepr_user->subscriptions();

            foreach ( $subs as $sub ) {
                $product = new MeprProduct( $sub->product_id );
                $subscriptions[] = array(
                    'id'            => $sub->id,
                    'product_id'    => $sub->product_id,
                    'product_name'  => $product->post_title,
                    'status'        => $sub->status,
                    'created_at'    => $sub->created_at,
                    'expires_at'    => $sub->expires_at,
                    'trial_ends'    => $sub->trial_ends ?? null,
                    'price'         => $sub->price,
                    'period'        => $sub->period,
                    'period_type'   => $sub->period_type,
                );
            }
        }

        return rest_ensure_response( $subscriptions );
    }

    /**
     * Get payment history
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_payments( $request ) {
        $user_id = get_current_user_id();

        $payments = array();

        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user    = new MeprUser( $user_id );
            $transactions = $mepr_user->transactions();

            foreach ( $transactions as $txn ) {
                $product = new MeprProduct( $txn->product_id );
                $payments[] = array(
                    'id'           => $txn->id,
                    'product_name' => $product->post_title,
                    'amount'       => $txn->amount,
                    'total'        => $txn->total,
                    'tax_amount'   => $txn->tax_amount,
                    'status'       => $txn->status,
                    'created_at'   => $txn->created_at,
                    'trans_num'    => $txn->trans_num,
                    'gateway'      => $txn->gateway,
                );
            }
        }

        return rest_ensure_response( $payments );
    }

    /**
     * Cancel a subscription
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function cancel_subscription( $request ) {
        $sub_id  = $request->get_param( 'id' );
        $user_id = get_current_user_id();

        if ( ! class_exists( 'MeprSubscription' ) ) {
            return new WP_Error( 'memberpress_not_active', 'Membership system not available.', array( 'status' => 503 ) );
        }

        $subscription = new MeprSubscription( $sub_id );

        if ( (int) $subscription->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', 'You cannot cancel this subscription.', array( 'status' => 403 ) );
        }

        $subscription->status = MeprSubscription::$cancelled_str;
        $subscription->store();

        return rest_ensure_response( array(
            'id'      => $sub_id,
            'status'  => 'cancelled',
            'message' => 'Subscription has been cancelled.',
        ) );
    }

    /**
     * Suspend a subscription
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function suspend_subscription( $request ) {
        $sub_id  = $request->get_param( 'id' );
        $user_id = get_current_user_id();

        if ( ! class_exists( 'MeprSubscription' ) ) {
            return new WP_Error( 'memberpress_not_active', 'Membership system not available.', array( 'status' => 503 ) );
        }

        $subscription = new MeprSubscription( $sub_id );

        if ( (int) $subscription->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', 'You cannot suspend this subscription.', array( 'status' => 403 ) );
        }

        $subscription->status = MeprSubscription::$suspended_str;
        $subscription->store();

        return rest_ensure_response( array(
            'id'      => $sub_id,
            'status'  => 'suspended',
            'message' => 'Subscription has been suspended.',
        ) );
    }

    /**
     * Resume a suspended subscription
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function resume_subscription( $request ) {
        $sub_id  = $request->get_param( 'id' );
        $user_id = get_current_user_id();

        if ( ! class_exists( 'MeprSubscription' ) ) {
            return new WP_Error( 'memberpress_not_active', 'Membership system not available.', array( 'status' => 503 ) );
        }

        $subscription = new MeprSubscription( $sub_id );

        if ( (int) $subscription->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', 'You cannot resume this subscription.', array( 'status' => 403 ) );
        }

        $subscription->status = MeprSubscription::$active_str;
        $subscription->store();

        return rest_ensure_response( array(
            'id'      => $sub_id,
            'status'  => 'active',
            'message' => 'Subscription has been resumed.',
        ) );
    }

    /**
     * Get available upgrade options
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_upgrade_options( $request ) {
        $user_id = get_current_user_id();
        $options = array();

        if ( class_exists( 'MeprProduct' ) ) {
            // Get all active products
            $products = MeprProduct::get_all();

            foreach ( $products as $product ) {
                if ( $product->is_active() ) {
                    $options[] = array(
                        'id'          => $product->ID,
                        'name'        => $product->post_title,
                        'description' => $product->post_excerpt,
                        'price'       => $product->price,
                        'period'      => $product->period,
                        'period_type' => $product->period_type,
                        'features'    => $this->get_product_features( $product->ID ),
                    );
                }
            }
        } else {
            // Fallback options
            $options = array(
                array(
                    'id'          => 1,
                    'name'        => 'Basic Plan',
                    'description' => 'Essential relocation support',
                    'price'       => 29,
                    'period'      => 1,
                    'period_type' => 'month',
                    'features'    => array( 'Task management', 'Document storage', 'Basic guides' ),
                ),
                array(
                    'id'          => 2,
                    'name'        => 'Premium Plan',
                    'description' => 'Full relocation assistance',
                    'price'       => 79,
                    'period'      => 1,
                    'period_type' => 'month',
                    'features'    => array( 'Everything in Basic', 'AI Chat support', 'Document verification', 'Priority support' ),
                ),
            );
        }

        return rest_ensure_response( $options );
    }

    /**
     * Get product features
     *
     * @param int $product_id Product ID
     * @return array Features
     */
    private function get_product_features( $product_id ) {
        $features = get_post_meta( $product_id, '_fra_product_features', true );
        return is_array( $features ) ? $features : array();
    }

    /**
     * Delete user account
     *
     * Permanently deletes the current user's account and all associated data.
     * Requires confirmation phrase to prevent accidental deletion.
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_account( $request ) {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return new WP_Error(
                'rest_not_logged_in',
                'You must be logged in to delete your account.',
                array( 'status' => 401 )
            );
        }

        // Get confirmation phrase from request
        $confirmation = sanitize_text_field( $request->get_param( 'confirmation' ) );

        // Require exact phrase "DELETE MY ACCOUNT" for confirmation
        if ( 'DELETE MY ACCOUNT' !== $confirmation ) {
            return new WP_Error(
                'rest_confirmation_required',
                'Please type "DELETE MY ACCOUNT" to confirm account deletion.',
                array( 'status' => 400 )
            );
        }

        // Prevent admin account deletion through this endpoint
        $user = get_userdata( $user_id );
        if ( $user && in_array( 'administrator', $user->roles, true ) ) {
            return new WP_Error(
                'rest_admin_delete_forbidden',
                'Administrator accounts cannot be deleted through this endpoint.',
                array( 'status' => 403 )
            );
        }

        // Cancel any MemberPress subscriptions
        if ( class_exists( 'MeprUser' ) ) {
            $mepr_user = new MeprUser( $user_id );
            $subscriptions = $mepr_user->active_product_subscriptions();
            foreach ( $subscriptions as $subscription_id ) {
                $subscription = new MeprSubscription( $subscription_id );
                if ( $subscription->id ) {
                    $subscription->status = MeprSubscription::$cancelled_str;
                    $subscription->store();
                }
            }
        }

        // Delete user's portal data
        global $wpdb;

        // Get user's projects
        $projects = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}framt_projects WHERE user_id = %d",
                $user_id
            )
        );

        // Delete tasks, notes, files, and task checklists for user's projects
        if ( ! empty( $projects ) ) {
            $project_ids = implode( ',', array_map( 'intval', $projects ) );

            // Delete task checklists for tasks in user's projects
            $wpdb->query(
                "DELETE tc FROM {$wpdb->prefix}framt_task_checklists tc
                 INNER JOIN {$wpdb->prefix}framt_tasks t ON tc.task_id = t.id
                 WHERE t.project_id IN ($project_ids)"
            );

            // Delete tasks
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}framt_tasks WHERE project_id IN ($project_ids)"
                )
            );

            // Delete notes
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}framt_notes WHERE project_id IN ($project_ids)"
                )
            );

            // Delete files
            $files = $wpdb->get_results(
                "SELECT id, file_path FROM {$wpdb->prefix}framt_files WHERE project_id IN ($project_ids)"
            );
            foreach ( $files as $file ) {
                // Delete physical file
                if ( ! empty( $file->file_path ) && file_exists( $file->file_path ) ) {
                    wp_delete_file( $file->file_path );
                }
            }
            $wpdb->query(
                "DELETE FROM {$wpdb->prefix}framt_files WHERE project_id IN ($project_ids)"
            );

            // Delete projects
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}framt_projects WHERE user_id = %d",
                    $user_id
                )
            );
        }

        // Delete support messages and replies
        $messages = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}framt_messages WHERE user_id = %d",
                $user_id
            )
        );
        if ( ! empty( $messages ) ) {
            $message_ids = implode( ',', array_map( 'intval', $messages ) );
            $wpdb->query( "DELETE FROM {$wpdb->prefix}framt_message_replies WHERE message_id IN ($message_ids)" );
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}framt_messages WHERE user_id = %d",
                    $user_id
                )
            );
        }

        // Delete user meta (profile data, checklist progress, etc.)
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE 'fra_%%'",
                $user_id
            )
        );

        // Log out the user before deletion
        wp_logout();

        // Finally, delete the WordPress user
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $deleted = wp_delete_user( $user_id );

        if ( ! $deleted ) {
            return new WP_Error(
                'rest_delete_failed',
                'Failed to delete account. Please contact support.',
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response(
            array(
                'deleted' => true,
                'message' => 'Your account has been permanently deleted.',
            )
        );
    }

    /**
     * Recursively sanitize array values
     *
     * @param array $array Array to sanitize
     * @return array Sanitized array
     */
    private function sanitize_array_recursive( $array ) {
        $sanitized = array();

        foreach ( $array as $key => $value ) {
            $sanitized_key = sanitize_key( $key );

            if ( is_array( $value ) ) {
                $sanitized[ $sanitized_key ] = $this->sanitize_array_recursive( $value );
            } elseif ( is_string( $value ) ) {
                $sanitized[ $sanitized_key ] = sanitize_text_field( $value );
            } elseif ( is_int( $value ) ) {
                $sanitized[ $sanitized_key ] = absint( $value );
            } elseif ( is_float( $value ) ) {
                $sanitized[ $sanitized_key ] = floatval( $value );
            } elseif ( is_bool( $value ) ) {
                $sanitized[ $sanitized_key ] = (bool) $value;
            } else {
                $sanitized[ $sanitized_key ] = sanitize_text_field( (string) $value );
            }
        }

        return $sanitized;
    }

    // =========================================================================
    // Task Generation Helper Methods
    // =========================================================================

    /**
     * Sync project visa type with profile visa type
     *
     * @param int    $user_id   User ID
     * @param string $visa_type Visa type
     * @return bool Success
     */
    private function sync_project_visa_type( $user_id, $visa_type ) {
        $project = FRAMT_Project::get_or_create( $user_id );

        if ( ! $project || ! $project->id ) {
            return false;
        }

        $project->visa_type = sanitize_key( $visa_type );
        return $project->save();
    }

    /**
     * Generate pre-defined tasks for a visa type
     *
     * @param int    $user_id   User ID
     * @param string $visa_type Visa type
     * @return int Number of tasks created
     */
    private function generate_visa_tasks( $user_id, $visa_type ) {
        $project = FRAMT_Project::get_or_create( $user_id );

        if ( ! $project || ! $project->id ) {
            return 0;
        }

        // Get task templates for this visa type
        $templates = $this->get_visa_task_templates( $visa_type );

        if ( empty( $templates ) ) {
            return 0;
        }

        $tasks_created = 0;

        foreach ( $templates as $template ) {
            // Check if task with same title already exists for this project
            if ( $this->task_exists( $project->id, $template['title'] ) ) {
                continue;
            }

            $task             = new FRAMT_Task();
            $task->project_id = $project->id;
            $task->user_id    = $user_id;
            $task->title      = sanitize_text_field( $template['title'] );
            $task->description = wp_kses_post( $template['description'] ?? '' );
            $task->stage      = sanitize_key( $template['stage'] ?? 'pre-arrival' );
            $task->status     = 'todo';
            $task->priority   = sanitize_key( $template['priority'] ?? 'medium' );
            $task->task_type  = sanitize_key( $template['task_type'] ?? 'task' );

            if ( $task->save() ) {
                $tasks_created++;
            }
        }

        return $tasks_created;
    }

    /**
     * Generate conditional tasks based on profile changes
     *
     * @param int   $user_id User ID
     * @param array $changes Array of old/new values
     * @return int Number of tasks created
     */
    private function generate_conditional_tasks( $user_id, $changes ) {
        $project = FRAMT_Project::get_or_create( $user_id );

        if ( ! $project || ! $project->id ) {
            return 0;
        }

        $tasks_created = 0;

        // Pet-related tasks
        if ( ! empty( $changes['new_has_pets'] ) && $changes['new_has_pets'] !== $changes['old_has_pets'] ) {
            $pet_tasks = $this->get_pet_task_templates();
            foreach ( $pet_tasks as $template ) {
                if ( ! $this->task_exists( $project->id, $template['title'] ) ) {
                    $tasks_created += $this->create_task_from_template( $project->id, $user_id, $template );
                }
            }
        }

        // Family/applicant-related tasks
        $old_applicants = $changes['old_applicants'] ?? '';
        $new_applicants = $changes['new_applicants'] ?? '';

        if ( $new_applicants !== $old_applicants ) {
            // Check for spouse
            if ( in_array( $new_applicants, array( 'spouse', 'family' ), true ) ) {
                $spouse_tasks = $this->get_spouse_task_templates();
                foreach ( $spouse_tasks as $template ) {
                    if ( ! $this->task_exists( $project->id, $template['title'] ) ) {
                        $tasks_created += $this->create_task_from_template( $project->id, $user_id, $template );
                    }
                }
            }

            // Check for children (family includes children)
            if ( 'family' === $new_applicants ) {
                $child_tasks = $this->get_children_task_templates();
                foreach ( $child_tasks as $template ) {
                    if ( ! $this->task_exists( $project->id, $template['title'] ) ) {
                        $tasks_created += $this->create_task_from_template( $project->id, $user_id, $template );
                    }
                }
            }
        }

        return $tasks_created;
    }

    /**
     * Check if a task with given title already exists for a project
     *
     * @param int    $project_id Project ID
     * @param string $title      Task title
     * @return bool
     */
    private function task_exists( $project_id, $title ) {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}framt_tasks WHERE project_id = %d AND title = %s",
                $project_id,
                $title
            )
        );

        return (int) $exists > 0;
    }

    /**
     * Create a task from a template array
     *
     * @param int   $project_id Project ID
     * @param int   $user_id    User ID
     * @param array $template   Task template
     * @return int 1 on success, 0 on failure
     */
    private function create_task_from_template( $project_id, $user_id, $template ) {
        $task             = new FRAMT_Task();
        $task->project_id = $project_id;
        $task->user_id    = $user_id;
        $task->title      = sanitize_text_field( $template['title'] );
        $task->description = wp_kses_post( $template['description'] ?? '' );
        $task->stage      = sanitize_key( $template['stage'] ?? 'pre-arrival' );
        $task->status     = 'todo';
        $task->priority   = sanitize_key( $template['priority'] ?? 'medium' );
        $task->task_type  = sanitize_key( $template['task_type'] ?? 'task' );

        return $task->save() ? 1 : 0;
    }

    /**
     * Get task templates for a visa type
     *
     * @param string $visa_type Visa type
     * @return array Task templates
     */
    private function get_visa_task_templates( $visa_type ) {
        // Common tasks for all visa types
        $common_tasks = array(
            // Pre-arrival stage
            array(
                'title'       => 'Gather all required documents',
                'description' => 'Collect passport, birth certificate, marriage certificate (if applicable), proof of income, and other supporting documents.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Get documents apostilled',
                'description' => 'Have your official documents apostilled for French recognition.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Get documents translated',
                'description' => 'Have all documents translated by a certified translator.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Open French bank account',
                'description' => 'Research and open a French bank account. Some can be opened remotely before arrival.',
                'stage'       => 'pre-arrival',
                'priority'    => 'medium',
                'task_type'   => 'financial',
            ),
            array(
                'title'       => 'Research health insurance options',
                'description' => 'Understand French healthcare and research private insurance options for the interim period.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Book temporary accommodation',
                'description' => 'Arrange initial housing for your first weeks in France.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),

            // Arrival stage
            array(
                'title'       => 'Validate visa (if VLS-TS)',
                'description' => 'Validate your long-stay visa within 3 months of arrival at the OFII.',
                'stage'       => 'arrival',
                'priority'    => 'high',
                'task_type'   => 'appointment',
            ),
            array(
                'title'       => 'Register for social security',
                'description' => 'Apply for your French social security number (numéro de sécurité sociale).',
                'stage'       => 'arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Find permanent housing',
                'description' => 'Search for and secure long-term accommodation. Gather required dossier documents.',
                'stage'       => 'arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Set up utilities',
                'description' => 'Arrange electricity, gas, internet, and other utilities for your new home.',
                'stage'       => 'arrival',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),

            // Settlement stage
            array(
                'title'       => 'Get Carte Vitale',
                'description' => 'Once registered with social security, apply for your Carte Vitale health card.',
                'stage'       => 'settlement',
                'priority'    => 'medium',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Apply for CAF benefits',
                'description' => 'Apply for housing assistance (APL) and other applicable CAF benefits.',
                'stage'       => 'settlement',
                'priority'    => 'medium',
                'task_type'   => 'financial',
            ),
            array(
                'title'       => 'Exchange driving license',
                'description' => 'Apply to exchange your foreign driving license for a French one.',
                'stage'       => 'settlement',
                'priority'    => 'low',
                'task_type'   => 'document',
            ),

            // Integration stage
            array(
                'title'       => 'Enroll in French language classes',
                'description' => 'Sign up for French language courses to improve integration.',
                'stage'       => 'integration',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'File French tax return',
                'description' => 'Understand your tax obligations and file your first French tax return.',
                'stage'       => 'integration',
                'priority'    => 'medium',
                'task_type'   => 'financial',
            ),
        );

        // Visa-specific tasks
        $visa_specific_tasks = array();

        switch ( $visa_type ) {
            case 'visitor':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Prepare proof of financial resources',
                        'description' => 'Document sufficient funds to support yourself without working (bank statements, investments, pension, etc.).',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Sign declaration not to work',
                        'description' => 'Prepare the attestation that you will not engage in professional activity in France.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Arrange comprehensive health insurance',
                        'description' => 'Obtain private health insurance covering the entire stay as required for visitor visa.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                );
                break;

            case 'talent_passport':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Gather employment documentation',
                        'description' => 'Collect employment contract, company registration, salary details meeting the threshold requirements.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Prepare diploma/qualification proof',
                        'description' => 'Get your master\'s degree or equivalent qualifications certified and translated.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Register with URSSAF',
                        'description' => 'If self-employed, register your business activity with URSSAF.',
                        'stage'       => 'arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                );
                break;

            case 'employee':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Obtain work permit (if required)',
                        'description' => 'Employer must obtain work authorization from DIRECCTE before visa application.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Get employment contract certified',
                        'description' => 'Have your French employment contract reviewed and certified.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Understand French labor rights',
                        'description' => 'Learn about French employment law, RTT, mutuelle, and employee rights.',
                        'stage'       => 'arrival',
                        'priority'    => 'medium',
                        'task_type'   => 'task',
                    ),
                );
                break;

            case 'entrepreneur':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Prepare business plan',
                        'description' => 'Create a detailed business plan demonstrating viability and economic contribution.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Prove business funding',
                        'description' => 'Document minimum investment capital and financial resources for your business.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'financial',
                    ),
                    array(
                        'title'       => 'Register business in France',
                        'description' => 'Register your business with the appropriate French authorities (CFE, INSEE).',
                        'stage'       => 'arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                    array(
                        'title'       => 'Set up business bank account',
                        'description' => 'Open a professional bank account for your French business.',
                        'stage'       => 'arrival',
                        'priority'    => 'high',
                        'task_type'   => 'financial',
                    ),
                );
                break;

            case 'student':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Get university acceptance letter',
                        'description' => 'Secure admission to a French educational institution.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Register on Campus France',
                        'description' => 'Complete the Campus France procedure for your country.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                    array(
                        'title'       => 'Prove financial resources for studies',
                        'description' => 'Document sufficient funds (€615/month minimum) for your study period.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'financial',
                    ),
                    array(
                        'title'       => 'Apply for CROUS housing',
                        'description' => 'Apply for student housing through CROUS if available.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'medium',
                        'task_type'   => 'task',
                    ),
                    array(
                        'title'       => 'Complete university enrollment',
                        'description' => 'Finalize your inscription at the university upon arrival.',
                        'stage'       => 'arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                );
                break;

            case 'retiree':
                $visa_specific_tasks = array(
                    array(
                        'title'       => 'Document pension income',
                        'description' => 'Gather proof of pension or retirement income meeting French requirements.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'document',
                    ),
                    array(
                        'title'       => 'Arrange pension transfer',
                        'description' => 'Set up international transfer of pension payments to France.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'medium',
                        'task_type'   => 'financial',
                    ),
                    array(
                        'title'       => 'Research S1 health form',
                        'description' => 'If from EU/UK, investigate S1 form for healthcare coverage.',
                        'stage'       => 'pre-arrival',
                        'priority'    => 'high',
                        'task_type'   => 'task',
                    ),
                );
                break;

            default:
                // For undecided or other types, just use common tasks
                break;
        }

        return array_merge( $common_tasks, $visa_specific_tasks );
    }

    /**
     * Get pet-related task templates
     *
     * @return array Task templates for pet owners
     */
    private function get_pet_task_templates() {
        return array(
            array(
                'title'       => 'Get pet microchipped',
                'description' => 'Ensure your pet has an ISO-compliant microchip (15 digits).',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Update pet vaccinations',
                'description' => 'Ensure rabies vaccination is current (administered at least 21 days before travel).',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'appointment',
            ),
            array(
                'title'       => 'Get EU pet passport or health certificate',
                'description' => 'Obtain required pet documentation from your veterinarian.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Research pet travel requirements',
                'description' => 'Check airline pet policies and French import requirements for your pet type.',
                'stage'       => 'pre-arrival',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Book pet-friendly accommodation',
                'description' => 'Ensure your French housing allows pets.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Find a French veterinarian',
                'description' => 'Research and register with a local veterinarian in France.',
                'stage'       => 'arrival',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),
        );
    }

    /**
     * Get spouse-related task templates
     *
     * @return array Task templates for applicants with spouse
     */
    private function get_spouse_task_templates() {
        return array(
            array(
                'title'       => 'Get marriage certificate apostilled',
                'description' => 'Have your marriage certificate apostilled for French recognition.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Translate marriage certificate',
                'description' => 'Get a certified French translation of your marriage certificate.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Gather spouse documents',
                'description' => 'Collect passport, birth certificate, and other required documents for your spouse.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Apply for spouse visa',
                'description' => 'Submit visa application for your spouse (if required).',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Register spouse for social security',
                'description' => 'Register your spouse as an ayant droit for health coverage.',
                'stage'       => 'arrival',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),
        );
    }

    /**
     * Get children-related task templates
     *
     * @return array Task templates for applicants with children
     */
    private function get_children_task_templates() {
        return array(
            array(
                'title'       => 'Get birth certificates apostilled',
                'description' => 'Have children\'s birth certificates apostilled for French recognition.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Translate birth certificates',
                'description' => 'Get certified French translations of children\'s birth certificates.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Gather children vaccination records',
                'description' => 'Collect immunization records - France requires specific vaccinations for school.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'document',
            ),
            array(
                'title'       => 'Research French schools',
                'description' => 'Research public, private, and international school options in your target area.',
                'stage'       => 'pre-arrival',
                'priority'    => 'medium',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Apply for child visas',
                'description' => 'Submit visa applications for dependent children.',
                'stage'       => 'pre-arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Enroll children in school',
                'description' => 'Complete school registration with your local mairie or chosen private school.',
                'stage'       => 'arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
            array(
                'title'       => 'Apply for family CAF benefits',
                'description' => 'Apply for allocations familiales and other family benefits through CAF.',
                'stage'       => 'settlement',
                'priority'    => 'medium',
                'task_type'   => 'financial',
            ),
            array(
                'title'       => 'Register children for health coverage',
                'description' => 'Add children as ayants droit for French health insurance.',
                'stage'       => 'arrival',
                'priority'    => 'high',
                'task_type'   => 'task',
            ),
        );
    }

    // ============================================
    // France Research Tool Methods
    // ============================================

    /**
     * Get all France regions
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_france_regions( $request ) {
        $regions = $this->get_regions_data();

        return rest_ensure_response( array(
            'success' => true,
            'regions' => $regions,
        ) );
    }

    /**
     * Get a single France region with its departments
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_france_region( $request ) {
        $code = sanitize_text_field( $request->get_param( 'code' ) );
        $regions = $this->get_regions_data();

        $region = null;
        foreach ( $regions as $r ) {
            if ( $r['code'] === $code ) {
                $region = $r;
                break;
            }
        }

        if ( ! $region ) {
            return new WP_Error(
                'rest_region_not_found',
                'Region not found.',
                array( 'status' => 404 )
            );
        }

        // Get departments for this region
        $all_departments = $this->get_departments_data();
        $departments = array_filter( $all_departments, function( $d ) use ( $code ) {
            return $d['region_code'] === $code;
        } );

        $region['departments_data'] = array_values( $departments );

        return rest_ensure_response( array(
            'success' => true,
            'region'  => $region,
        ) );
    }

    /**
     * Get all France departments
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_france_departments( $request ) {
        $region_code = $request->get_param( 'region' );
        $departments = $this->get_departments_data();

        if ( $region_code ) {
            $region_code = sanitize_text_field( $region_code );
            $departments = array_filter( $departments, function( $d ) use ( $region_code ) {
                return $d['region_code'] === $region_code;
            } );
            $departments = array_values( $departments );
        }

        return rest_ensure_response( array(
            'success'     => true,
            'departments' => $departments,
        ) );
    }

    /**
     * Get a single France department
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_france_department( $request ) {
        $code = sanitize_text_field( $request->get_param( 'code' ) );
        $departments = $this->get_departments_data();

        $department = null;
        foreach ( $departments as $d ) {
            if ( $d['code'] === $code ) {
                $department = $d;
                break;
            }
        }

        if ( ! $department ) {
            return new WP_Error(
                'rest_department_not_found',
                'Department not found.',
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'success'    => true,
            'department' => $department,
        ) );
    }

    /**
     * Search communes by name or postal code
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function search_communes( $request ) {
        $query = sanitize_text_field( $request->get_param( 'q' ) );
        $department_code = sanitize_text_field( $request->get_param( 'department' ) );
        $limit = absint( $request->get_param( 'limit' ) ) ?: 20;

        // Allow either a search query OR a department filter
        if ( strlen( $query ) < 2 && empty( $department_code ) ) {
            return rest_ensure_response( array(
                'success'  => true,
                'communes' => array(),
                'message'  => 'Provide a search query (2+ characters) or department code.',
            ) );
        }

        $communes = $this->search_communes_data( $query, $department_code, $limit );

        return rest_ensure_response( array(
            'success'  => true,
            'communes' => $communes,
            'total'    => count( $communes ),
        ) );
    }

    /**
     * Generate a research report for a location
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function generate_research_report( $request ) {
        $location_type = sanitize_text_field( $request->get_param( 'location_type' ) );
        $location_code = sanitize_text_field( $request->get_param( 'location_code' ) );
        $provided_name = sanitize_text_field( $request->get_param( 'location_name' ) );
        $save_to_docs  = (bool) $request->get_param( 'save_to_documents' );
        $force_regenerate = (bool) $request->get_param( 'force_regenerate' ) || (bool) $request->get_param( 'force_refresh' );

        if ( ! in_array( $location_type, array( 'region', 'department', 'commune' ), true ) ) {
            return new WP_Error(
                'invalid_location_type',
                'Invalid location type. Must be region, department, or commune.',
                array( 'status' => 400 )
            );
        }

        // Use provided name if available (for communes loaded from GeoJSON), otherwise look it up
        $location_name = ! empty( $provided_name ) ? $provided_name : $this->get_location_name( $location_type, $location_code );
        if ( ! $location_name ) {
            return new WP_Error(
                'location_not_found',
                'Location not found. Please provide a location name.',
                array( 'status' => 404 )
            );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'framt_research_reports';

        $this->maybe_create_research_tables();

        // Check for cached report
        $cached_report = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE location_type = %s AND location_code = %s ORDER BY version DESC LIMIT 1",
                $location_type,
                $location_code
            ),
            ARRAY_A
        );

        // Check if cached report is a placeholder (template report without real AI data)
        $is_placeholder = false;
        if ( $cached_report ) {
            $cached_content = json_decode( $cached_report['content'], true );
            // Placeholder reports have a note field indicating they're templates
            if ( isset( $cached_content['footer']['note'] ) && strpos( $cached_content['footer']['note'], 'template report' ) !== false ) {
                $is_placeholder = true;
            }
        }

        // Return cached if less than 30 days old, not forcing regeneration, AND not a placeholder
        // (placeholders should always attempt regeneration if API key is available)
        $ai_api_key = class_exists( 'France_Relocation_Assistant' ) ? France_Relocation_Assistant::get_api_key() : '';
        $should_use_cache = ! $force_regenerate && $cached_report
            && strtotime( $cached_report['updated_at'] ) > strtotime( '-30 days' )
            && ( ! $is_placeholder || empty( $ai_api_key ) );

        if ( $should_use_cache ) {
            $report = $this->format_report_response( $cached_report );
            $cached_placeholder_reason = $cached_content['footer']['placeholder_reason'] ?? '';

            $document_id = null;
            if ( $save_to_docs ) {
                $document_id = $this->save_report_link_to_documents( $cached_report['id'], get_current_user_id() );
            }

            return rest_ensure_response( array(
                'success'            => true,
                'report'             => $report,
                'cached'             => true,
                'is_placeholder'     => $is_placeholder,
                'placeholder_reason' => $cached_placeholder_reason,
                'saved_to_documents' => $save_to_docs,
                'document_id'        => $document_id,
            ) );
        }

        // Generate new report
        $report_content = $this->generate_ai_report( $location_type, $location_code, $location_name );

        if ( is_wp_error( $report_content ) ) {
            return $report_content;
        }

        $report_data = array(
            'location_type' => $location_type,
            'location_code' => $location_code,
            'location_name' => $location_name,
            'content'       => wp_json_encode( $report_content ),
            'version'       => $cached_report ? ( (int) $cached_report['version'] + 1 ) : 1,
            'updated_at'    => current_time( 'mysql' ),
        );

        if ( $cached_report ) {
            $wpdb->update( $table_name, $report_data, array( 'id' => $cached_report['id'] ) );
            $report_id = $cached_report['id'];
        } else {
            $report_data['generated_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_name, $report_data );
            $report_id = $wpdb->insert_id;
        }

        $report = $this->format_report_response( array_merge( $report_data, array( 'id' => $report_id ) ) );

        // Check if newly generated report is a placeholder
        $new_is_placeholder = isset( $report_content['footer']['note'] ) && strpos( $report_content['footer']['note'], 'template report' ) !== false;
        $placeholder_reason = $report_content['footer']['placeholder_reason'] ?? '';

        $document_id = null;
        if ( $save_to_docs ) {
            $document_id = $this->save_report_link_to_documents( $report_id, get_current_user_id() );
        }

        return rest_ensure_response( array(
            'success'            => true,
            'report'             => $report,
            'cached'             => false,
            'is_placeholder'     => $new_is_placeholder,
            'placeholder_reason' => $placeholder_reason,
            'saved_to_documents' => $save_to_docs,
            'document_id'        => $document_id,
        ) );
    }

    /**
     * Get a specific research report
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_research_report( $request ) {
        $report_id = absint( $request->get_param( 'id' ) );

        global $wpdb;
        $table_name = $wpdb->prefix . 'framt_research_reports';

        $report = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $report_id ),
            ARRAY_A
        );

        if ( ! $report ) {
            return new WP_Error( 'report_not_found', 'Research report not found.', array( 'status' => 404 ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'report'  => $this->format_report_response( $report ),
        ) );
    }

    /**
     * Download a research report as PDF
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function download_research_report( $request ) {
        $report_id = absint( $request->get_param( 'id' ) );

        global $wpdb;
        $table_name = $wpdb->prefix . 'framt_research_reports';

        $report = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $report_id ),
            ARRAY_A
        );

        if ( ! $report ) {
            return new WP_Error( 'report_not_found', 'Research report not found.', array( 'status' => 404 ) );
        }

        // Parse the report content
        $content = is_string( $report['content'] ) ? json_decode( $report['content'], true ) : $report['content'];

        // Load PDF class
        require_once FRAMT_PLUGIN_DIR . 'vendor/class-simple-pdf.php';

        $pdf = new FRAMT_Simple_PDF();
        $pdf->addPage();

        // Write header
        $title = $content['header']['title'] ?? $report['location_name'];
        $pdf->writeTitle( $title );

        if ( ! empty( $content['header']['tagline'] ) ) {
            $pdf->write( $content['header']['tagline'] );
        }
        $pdf->addSpace( 1 );

        // Write key stats if available
        if ( ! empty( $content['header']['key_stats'] ) ) {
            $pdf->write( 'Key Statistics:', true );
            foreach ( $content['header']['key_stats'] as $key => $value ) {
                if ( $value ) {
                    $label = ucwords( str_replace( '_', ' ', $key ) );
                    $formatted = is_numeric( $value ) ? number_format( $value ) : $value;
                    $pdf->write( "  {$label}: {$formatted}" );
                }
            }
            $pdf->addSpace( 1 );
        }

        // Write sections
        if ( ! empty( $content['sections'] ) ) {
            foreach ( $content['sections'] as $section_id => $section ) {
                // Section title
                $pdf->write( $section['title'] ?? ucwords( str_replace( '_', ' ', $section_id ) ), true );
                $pdf->addSpace( 0.5 );

                // Section content
                if ( ! empty( $section['content'] ) ) {
                    $pdf->write( $section['content'] );
                    $pdf->addSpace( 0.5 );
                }

                // Subsections
                if ( ! empty( $section['subsections'] ) ) {
                    foreach ( $section['subsections'] as $sub_id => $subsection ) {
                        if ( ! empty( $subsection['title'] ) ) {
                            $pdf->write( '  ' . $subsection['title'], true );
                        }
                        if ( ! empty( $subsection['content'] ) ) {
                            $pdf->write( '  ' . $subsection['content'] );
                        }
                        $pdf->addSpace( 0.5 );
                    }
                }

                $pdf->addSpace( 1 );
            }
        }

        // Footer
        $pdf->addSpace( 2 );
        $pdf->write( 'Generated: ' . gmdate( 'F j, Y' ) );
        if ( ! empty( $content['footer']['data_sources'] ) ) {
            $pdf->write( 'Data sources: ' . implode( ', ', $content['footer']['data_sources'] ) );
        }

        // Generate PDF content
        $pdf_content = $pdf->output();
        $filename = sanitize_file_name( $report['location_name'] . '-relocation-report.pdf' );

        // Output PDF directly
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $pdf_content ) );
        header( 'Cache-Control: private, max-age=0, must-revalidate' );
        header( 'Pragma: public' );

        echo $pdf_content;
        exit;
    }

    /**
     * View research report as styled HTML (printable to PDF)
     *
     * @param WP_REST_Request $request Request object.
     * @return void Outputs HTML directly
     */
    public function view_research_report_html( $request ) {
        try {
            $report_id = absint( $request->get_param( 'id' ) );

            global $wpdb;
            $table_name = $wpdb->prefix . 'framt_research_reports';

            $report = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $report_id ),
                ARRAY_A
            );

            if ( ! $report ) {
                wp_die( 'Research report not found.', 'Report Not Found', array( 'response' => 404 ) );
            }

            // Parse the report content
            $content = is_string( $report['content'] ) ? json_decode( $report['content'], true ) : $report['content'];

            // Ensure content is valid
            if ( ! is_array( $content ) ) {
                wp_die( 'Report content is invalid or corrupted. JSON decode error: ' . esc_html( json_last_error_msg() ), 'Invalid Report', array( 'response' => 500 ) );
            }

            // Generate and output HTML
            $html = $this->generate_report_html( $report, $content );

            header( 'Content-Type: text/html; charset=utf-8' );
            echo $html;
            exit;
        } catch ( \Exception $e ) {
            wp_die( 'Error generating report: ' . esc_html( $e->getMessage() ), 'Report Error', array( 'response' => 500 ) );
        } catch ( \Error $e ) {
            error_log( 'FRA Report Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine() );
            wp_die( 'Fatal error generating report: ' . esc_html( $e->getMessage() ) . ' in ' . esc_html( basename( $e->getFile() ) ) . ' line ' . esc_html( $e->getLine() ), 'Report Error', array( 'response' => 500 ) );
        }
    }

    /**
     * Generate styled HTML report matching relo2france template
     *
     * @param array $report Report database row.
     * @param array $content Parsed report content.
     * @return string Complete HTML document
     */
    private function generate_report_html( $report, $content ) {
        // Ensure content has expected structure with defaults
        $header = is_array( $content['header'] ?? null ) ? $content['header'] : array();
        $title = $header['title'] ?? $report['location_name'];
        $subtitle = $header['subtitle'] ?? '';
        $tagline = $header['tagline'] ?? 'A Comprehensive Guide for Those Considering Relocation';
        $secondary_tagline = $header['secondary_tagline'] ?? '';
        $french_name = $subtitle; // Use subtitle as French name/location context (e.g., "Department 24 • Nouvelle-Aquitaine")
        $header_stat_cards = is_array( $header['stat_cards'] ?? null ) ? $header['stat_cards'] : array();
        $sections = is_array( $content['sections'] ?? null ) ? $content['sections'] : array();
        $footer = is_array( $content['footer'] ?? null ) ? $content['footer'] : array();
        $location_type = ucfirst( $report['location_type'] );

        // Check if this is a placeholder/template report
        $is_placeholder = isset( $footer['note'] ) && strpos( $footer['note'], 'template report' ) !== false;

        // relo2france brand colors (from france-overview.jsx template)
        $brand_blue = '#4A7BA7';
        $brand_gold = '#E5A54B';
        $dark_text = '#2D3748';
        $light_gray = '#718096';
        $light_blue = '#EBF4FA';

        // Section icons mapping
        $section_icons = array(
            'geography'        => '🗺️',
            'climate'          => '🌡️',
            'demographics'     => '👥',
            'economy'          => '📈',
            'language'         => '💬',
            'gastronomy'       => '🍷',
            'food_wine'        => '🍷',
            'culture'          => '🎭',
            'culture_lifestyle' => '🎭',
            'administrative'   => '🗺️',
            'subdivisions'     => '🏛️',
            'quality_of_life'  => '❤️',
            'environment'      => '🌲',
            'housing'          => '🏠',
            'transportation'   => '🚆',
            'practical_info'   => 'ℹ️',
            'local_life'       => '🏘️',
        );

        // Build header stat cards HTML
        $stats_html = '';
        if ( ! empty( $header_stat_cards ) && is_array( $header_stat_cards ) ) {
            foreach ( $header_stat_cards as $stat ) {
                if ( is_array( $stat ) && isset( $stat['value'] ) ) {
                    $value = esc_html( $stat['value'] );
                    $label = esc_html( $stat['label'] ?? '' );
                    $sublabel = isset( $stat['sublabel'] ) ? '<span class="stat-sublabel">' . esc_html( $stat['sublabel'] ) . '</span>' : '';
                    $stats_html .= '<div class="stat-card"><span class="stat-value">' . $value . '</span><span class="stat-label">' . $label . '</span>' . $sublabel . '</div>';
                }
            }
        }

        // Build sections HTML with collapsible structure
        $sections_html = '';
        $section_index = 0;
        foreach ( $sections as $section_id => $section ) {
            // Skip if section is not an array
            if ( ! is_array( $section ) ) {
                continue;
            }
            $section_title = $section['title'] ?? ucwords( str_replace( '_', ' ', $section_id ) );
            $section_intro = $section['intro'] ?? $section['content'] ?? '';
            $section_icon = $section_icons[ $section_id ] ?? '📍';
            $is_first = $section_index === 0;
            $section_index++;

            $sections_html .= '<details class="report-section" ' . ( $is_first ? 'open' : '' ) . '>';
            $sections_html .= '<summary class="section-header"><span class="section-icon">' . $section_icon . '</span><span class="section-title">' . esc_html( $section_title ) . '</span><span class="toggle-icon">▼</span></summary>';
            $sections_html .= '<div class="section-body">';

            // Intro paragraph
            if ( $section_intro ) {
                $sections_html .= '<p class="section-intro">' . wp_kses_post( $section_intro ) . '</p>';
            }

            // Section stat cards (these ARE expected - see Gironde template)
            if ( ! empty( $section['stat_cards'] ) && is_array( $section['stat_cards'] ) ) {
                $sections_html .= '<div class="stat-cards-grid">';
                foreach ( $section['stat_cards'] as $stat ) {
                    if ( is_array( $stat ) && isset( $stat['value'] ) ) {
                        $sections_html .= '<div class="stat-card-small">';
                        $sections_html .= '<span class="stat-value">' . esc_html( $stat['value'] ) . '</span>';
                        $sections_html .= '<span class="stat-label">' . esc_html( $stat['label'] ?? '' ) . '</span>';
                        if ( isset( $stat['sublabel'] ) ) {
                            $sections_html .= '<span class="stat-sublabel">' . esc_html( $stat['sublabel'] ) . '</span>';
                        }
                        $sections_html .= '</div>';
                    }
                }
                $sections_html .= '</div>';
            }

            // Info box - render as gold heading + bullet list (NO background box)
            if ( ! empty( $section['info_box'] ) && is_array( $section['info_box'] ) ) {
                $info_box = $section['info_box'];
                if ( isset( $info_box['title'] ) ) {
                    $sections_html .= '<h4 class="subsection-heading">' . esc_html( $info_box['title'] ) . '</h4>';
                }
                // Data rows as bullet list
                if ( ! empty( $info_box['data_rows'] ) && is_array( $info_box['data_rows'] ) ) {
                    $sections_html .= '<ul class="bullet-list">';
                    foreach ( $info_box['data_rows'] as $row ) {
                        if ( ! is_array( $row ) ) continue;
                        $sections_html .= '<li><strong>' . esc_html( $row['label'] ?? '' ) . ':</strong> ' . esc_html( $row['value'] ?? '' ) . '</li>';
                    }
                    $sections_html .= '</ul>';
                }
                // Grid items as bullet list
                if ( ! empty( $info_box['grid_items'] ) && is_array( $info_box['grid_items'] ) ) {
                    $sections_html .= '<ul class="bullet-list">';
                    foreach ( $info_box['grid_items'] as $item ) {
                        if ( ! is_array( $item ) ) continue;
                        $sections_html .= '<li><strong>' . esc_html( $item['label'] ?? '' ) . ':</strong> ' . esc_html( $item['description'] ?? '' ) . '</li>';
                    }
                    $sections_html .= '</ul>';
                }
                // Regular items as bullet list
                if ( ! empty( $info_box['items'] ) && is_array( $info_box['items'] ) ) {
                    $sections_html .= '<ul class="bullet-list">';
                    foreach ( $info_box['items'] as $item ) {
                        if ( ! is_array( $item ) ) continue;
                        $sections_html .= '<li><strong>' . esc_html( $item['label'] ?? '' ) . ':</strong> ' . esc_html( $item['description'] ?? $item['value'] ?? '' ) . '</li>';
                    }
                    $sections_html .= '</ul>';
                }
            }

            // Callout box - render as subsection title + content (matching PDF template)
            if ( ! empty( $section['callout_box'] ) && is_array( $section['callout_box'] ) ) {
                $callout = $section['callout_box'];
                if ( isset( $callout['title'] ) ) {
                    $sections_html .= '<h4 class="subsection-heading">' . esc_html( $callout['title'] ) . '</h4>';
                }
                if ( ! empty( $callout['items'] ) && is_array( $callout['items'] ) ) {
                    foreach ( $callout['items'] as $item ) {
                        if ( ! is_array( $item ) ) continue;
                        // Render each seasonal item as a small subsection
                        $sections_html .= '<h5 class="subsection-subheading">' . esc_html( $item['label'] ?? '' ) . '</h5>';
                        $sections_html .= '<p class="section-para">' . esc_html( $item['description'] ?? '' ) . '</p>';
                    }
                }
            }

            // Highlight box - render as subsection heading + content (matching PDF template)
            if ( ! empty( $section['highlight_box'] ) && is_array( $section['highlight_box'] ) ) {
                $highlight = $section['highlight_box'];
                if ( isset( $highlight['title'] ) ) {
                    $sections_html .= '<h4 class="subsection-heading">' . esc_html( $highlight['title'] ) . '</h4>';
                }
                if ( isset( $highlight['content'] ) ) {
                    $sections_html .= '<p class="section-para">' . wp_kses_post( $highlight['content'] ) . '</p>';
                }
                if ( ! empty( $highlight['items'] ) && is_array( $highlight['items'] ) ) {
                    $sections_html .= '<ul class="bullet-list">';
                    foreach ( $highlight['items'] as $item ) {
                        if ( ! is_array( $item ) ) continue;
                        $sections_html .= '<li><strong>' . esc_html( $item['label'] ?? '' ) . ':</strong> ' . esc_html( $item['description'] ?? '' ) . '</li>';
                    }
                    $sections_html .= '</ul>';
                }
            }

            // Paragraphs
            if ( ! empty( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ) {
                $para_count = count( $section['paragraphs'] );
                foreach ( $section['paragraphs'] as $i => $para ) {
                    if ( ! is_string( $para ) && ! is_numeric( $para ) ) continue;
                    $para_class = $i === $para_count - 1 ? 'section-para light' : 'section-para';
                    $sections_html .= '<p class="' . $para_class . '">' . wp_kses_post( (string) $para ) . '</p>';
                }
            }

            // Considerations box (gold warning style)
            // Considerations - render as subsection heading + paragraph (matching PDF)
            if ( ! empty( $section['considerations_box'] ) && is_array( $section['considerations_box'] ) ) {
                $considerations = $section['considerations_box'];
                $sections_html .= '<h4 class="subsection-heading">Considerations</h4>';
                $sections_html .= '<p class="section-para">' . esc_html( $considerations['content'] ?? '' ) . '</p>';
            }

            // Footer paragraphs
            if ( ! empty( $section['paragraphs_footer'] ) && is_array( $section['paragraphs_footer'] ) ) {
                foreach ( $section['paragraphs_footer'] as $para ) {
                    if ( ! is_string( $para ) && ! is_numeric( $para ) ) continue;
                    $sections_html .= '<p class="section-para">' . wp_kses_post( $para ) . '</p>';
                }
            }

            // Legacy: Handle old format items as bullet list (matching PDF)
            if ( ! empty( $section['items'] ) && is_array( $section['items'] ) && empty( $section['info_box'] ) ) {
                $sections_html .= '<ul class="bullet-list">';
                foreach ( $section['items'] as $item ) {
                    if ( ! is_array( $item ) ) {
                        continue;
                    }
                    $sections_html .= '<li><strong>' . esc_html( $item['label'] ?? '' ) . ':</strong> ' . esc_html( $item['value'] ?? '' ) . '</li>';
                }
                $sections_html .= '</ul>';
            }

            // Legacy subsections - render as gold headings + paragraphs (matching PDF)
            if ( ! empty( $section['subsections'] ) && is_array( $section['subsections'] ) ) {
                foreach ( $section['subsections'] as $sub_id => $subsection ) {
                    if ( ! is_array( $subsection ) ) {
                        continue;
                    }
                    $sub_title = $subsection['title'] ?? ucwords( str_replace( '_', ' ', $sub_id ) );
                    $sub_content = $subsection['content'] ?? '';
                    $sections_html .= '<h4 class="subsection-heading">' . esc_html( $sub_title ) . '</h4>';
                    if ( $sub_content ) {
                        $sections_html .= '<p class="section-para">' . wp_kses_post( $sub_content ) . '</p>';
                    }
                    if ( ! empty( $subsection['items'] ) && is_array( $subsection['items'] ) ) {
                        $sections_html .= '<ul class="bullet-list">';
                        foreach ( $subsection['items'] as $item ) {
                            if ( ! is_array( $item ) ) {
                                continue;
                            }
                            $sections_html .= '<li><strong>' . esc_html( $item['label'] ?? '' ) . ':</strong> ' . esc_html( $item['value'] ?? '' ) . '</li>';
                        }
                        $sections_html .= '</ul>';
                    }
                }
            }

            // Handle legacy highlights as bullet list (matching PDF)
            if ( ! empty( $section['highlights'] ) && is_array( $section['highlights'] ) && empty( $section['highlight_box'] ) ) {
                $sections_html .= '<ul class="bullet-list">';
                foreach ( $section['highlights'] as $highlight ) {
                    if ( is_string( $highlight ) ) {
                        $sections_html .= '<li>' . esc_html( $highlight ) . '</li>';
                    }
                }
                $sections_html .= '</ul>';
            }

            $sections_html .= '</div></details>';
        }

        // Build footer HTML - ensure data_sources is always an array
        $data_sources = $footer['data_sources'] ?? array( 'INSEE', 'Eurostat', 'French government sources' );
        if ( ! is_array( $data_sources ) ) {
            $data_sources = is_string( $data_sources ) ? array( $data_sources ) : array( 'INSEE', 'Eurostat', 'French government sources' );
        }
        $generated_date = $footer['generated_date'] ?? gmdate( 'F j, Y' );
        $version = $footer['version'] ?? $report['version'] ?? 1;

        // Logo URL - use the uploaded logo from plugin assets
        $logo_url = plugins_url( 'assets/images/relo2france_updated_logo.png', dirname( __FILE__ ) );

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_attr( $title ) . ' - Relocation Report | relo2france</title>
    <style>
        /* Reset and Base - relo2france brand styling */
        :root {
            --brand-blue: ' . $brand_blue . ';
            --brand-gold: ' . $brand_gold . ';
            --dark-text: ' . $dark_text . ';
            --light-gray: ' . $light_gray . ';
            --light-blue: ' . $light_blue . ';
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: var(--dark-text);
            background: #f8fafc;
        }

        /* Print wrapper with side branding */
        .print-wrapper {
            display: flex;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Side branding strip (visible in print) */
        .side-branding {
            display: none;
            width: 30px;
            background: linear-gradient(180deg, var(--brand-blue) 0%, var(--brand-gold) 100%);
            flex-shrink: 0;
            position: relative;
        }

        .side-branding::after {
            content: "relo2france.com";
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%) rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
            font-size: 10px;
            color: white;
            letter-spacing: 2px;
            font-weight: 600;
        }

        /* Main container */
        .report-container {
            flex: 1;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
            margin: 20px;
        }

        /* Placeholder/template report banner */
        .placeholder-banner {
            background: linear-gradient(135deg, #FEF3CD 0%, #FFF3CD 100%);
            border-bottom: 2px solid #E5A54B;
            padding: 12px 20px;
            text-align: center;
        }

        .placeholder-banner-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .placeholder-banner-icon {
            font-size: 18px;
        }

        .placeholder-banner-text {
            color: #856404;
            font-size: 14px;
            font-weight: 500;
        }

        .placeholder-banner-note {
            color: #856404;
            font-size: 12px;
            margin-top: 4px;
            opacity: 0.9;
        }

        @media print {
            .placeholder-banner {
                display: none;
            }
        }

        /* Header with brand colors */
        .report-header {
            background: linear-gradient(135deg, var(--brand-blue) 0%, #3a6a94 100%);
            color: white;
            padding: 35px 40px;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo-img {
            height: 40px;
            width: auto;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 600;
        }

        .logo-text .blue { color: white; }
        .logo-text .gold { color: var(--brand-gold); font-weight: 700; }

        .report-type-badge {
            background: var(--brand-gold);
            color: var(--dark-text);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .report-title {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .french-name {
            font-size: 18px;
            font-style: italic;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .tagline {
            font-size: 15px;
            opacity: 0.85;
            max-width: 550px;
        }

        /* Key Stats Bar */
        .key-stats {
            display: flex;
            background: var(--brand-gold);
            padding: 18px 40px;
        }

        .stat-item {
            flex: 1;
            text-align: center;
            color: var(--dark-text);
            border-right: 1px solid rgba(0,0,0,0.1);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-value {
            display: block;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .stat-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        /* Content */
        .report-content {
            padding: 30px 40px;
        }

        /* Collapsible Sections */
        .report-section {
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: var(--light-blue);
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            color: var(--brand-blue);
            list-style: none;
            user-select: none;
        }

        .section-header::-webkit-details-marker {
            display: none;
        }

        .section-icon {
            font-size: 20px;
        }

        .section-title {
            flex: 1;
        }

        .toggle-icon {
            font-size: 12px;
            transition: transform 0.2s ease;
            color: var(--light-gray);
        }

        details[open] .toggle-icon {
            transform: rotate(180deg);
        }

        details[open] .section-header {
            border-bottom: 1px solid #e2e8f0;
        }

        .section-body {
            padding: 20px;
        }

        .section-content {
            margin-bottom: 15px;
            text-align: justify;
            line-height: 1.7;
        }

        /* Highlights box */
        .highlights-box {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }

        .highlight-tag {
            background: var(--light-blue);
            color: var(--brand-blue);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        /* Data grid for key-value pairs */
        .data-grid {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-row:last-child {
            border-bottom: none;
        }

        .data-label {
            color: var(--light-gray);
            font-size: 14px;
        }

        .data-value {
            font-weight: 600;
            color: var(--dark-text);
            text-align: right;
        }

        /* Subsections */
        .subsection {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-left: 3px solid var(--brand-gold);
        }

        .subsection-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--brand-blue);
            margin-bottom: 10px;
        }

        .subsection-content {
            color: var(--dark-text);
            line-height: 1.7;
        }

        /* Subsection headings (gold, matching PDF template) */
        .subsection-heading {
            font-size: 17px;
            font-weight: 600;
            color: var(--brand-gold);
            margin: 20px 0 12px 0;
        }

        .subsection-subheading {
            font-size: 15px;
            font-weight: 600;
            color: var(--brand-gold);
            margin: 16px 0 8px 0;
        }

        /* Bullet list (matching PDF template) */
        .bullet-list {
            margin: 12px 0;
            padding-left: 24px;
            list-style: disc;
        }

        .bullet-list li {
            margin-bottom: 8px;
            line-height: 1.6;
            color: var(--dark-text);
        }

        .bullet-list li strong {
            color: var(--dark-text);
        }

        /* Section intro and paragraphs */
        .section-intro {
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 16px;
            color: var(--dark-text);
        }

        .section-para {
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 12px;
            color: var(--dark-text);
        }

        .section-para.light {
            color: var(--light-gray);
            font-size: 13px;
        }

        .section-para strong {
            color: var(--brand-blue);
            font-weight: 600;
        }

        /* Stat cards grid for sections */
        .stat-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin: 16px 0;
        }

        @media (max-width: 768px) {
            .stat-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-card-small {
            background: var(--light-blue);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .stat-card-small .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--brand-blue);
            display: block;
        }

        .stat-card-small .stat-label {
            font-size: 11px;
            color: var(--dark-text);
            font-weight: 500;
            display: block;
        }

        .stat-card-small .stat-sublabel {
            font-size: 10px;
            color: var(--light-gray);
            display: block;
        }

        /* Info box (brand blue style) */
        .info-box {
            background: var(--light-blue);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }

        .info-box-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--brand-blue);
            margin-bottom: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-grid-item {
            font-size: 13px;
            line-height: 1.5;
        }

        .info-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
        }

        .item-label {
            font-weight: 600;
            color: var(--brand-blue);
        }

        .item-desc {
            color: var(--dark-text);
        }

        .data-rows {
            display: flex;
            flex-direction: column;
        }

        /* Callout box (gold/seasonal style) */
        .callout-box {
            background: #FEF3E2;
            border: 1px solid #f5d9a8;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }

        .callout-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--brand-gold);
            margin-bottom: 12px;
        }

        .callout-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .callout-items p {
            font-size: 13px;
            margin: 0;
        }

        .callout-label {
            font-weight: 600;
            color: #c78b2b;
        }

        /* Highlight box with color variants */
        .highlight-box {
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            border: 1px solid;
        }

        .highlight-emerald {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        .highlight-emerald .highlight-title { color: #065f46; }
        .highlight-emerald .highlight-label { color: #047857; font-weight: 600; }
        .highlight-emerald .highlight-content,
        .highlight-emerald .highlight-items p { color: #047857; }

        .highlight-amber {
            background: #fffbeb;
            border-color: #fcd34d;
        }
        .highlight-amber .highlight-title { color: #92400e; }
        .highlight-amber .highlight-label { color: #b45309; font-weight: 600; }
        .highlight-amber .highlight-content,
        .highlight-amber .highlight-items p { color: #b45309; }

        .highlight-teal {
            background: #f0fdfa;
            border-color: #99f6e4;
        }
        .highlight-teal .highlight-title { color: #115e59; }
        .highlight-teal .highlight-label { color: #0d9488; font-weight: 600; }
        .highlight-teal .highlight-content,
        .highlight-teal .highlight-items p { color: #0d9488; }

        .highlight-blue {
            background: var(--light-blue);
            border-color: #bfdbfe;
        }
        .highlight-blue .highlight-title { color: var(--brand-blue); }
        .highlight-blue .highlight-label { color: var(--brand-blue); font-weight: 600; }
        .highlight-blue .highlight-content,
        .highlight-blue .highlight-items p { color: #1e40af; }

        .highlight-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .highlight-content {
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        .highlight-items {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .highlight-items p {
            font-size: 13px;
            margin: 0;
        }

        .highlight-label {
            font-weight: 600;
        }

        /* Considerations box (gold warning style) */
        .considerations-box {
            background: #FEF3E2;
            border: 1px solid #f5d9a8;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 16px 0;
        }

        .considerations-box p {
            font-size: 13px;
            margin: 0;
        }

        .considerations-label {
            font-weight: 600;
            color: var(--brand-gold);
        }

        /* Footer */
        .report-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 25px 40px;
            text-align: center;
        }

        .footer-sources {
            font-size: 12px;
            color: var(--light-gray);
            margin-bottom: 10px;
        }

        .footer-brand {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .footer-brand .blue { color: var(--brand-blue); }
        .footer-brand .gold { color: var(--brand-gold); font-weight: 700; }

        .footer-meta {
            font-size: 11px;
            color: var(--light-gray);
        }

        /* Action Bar */
        .action-bar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .action-btn.primary {
            background: var(--brand-blue);
            color: white;
        }

        .action-btn.primary:hover {
            background: #3a6a94;
        }

        .action-btn.secondary {
            background: white;
            color: var(--brand-blue);
            border: 1px solid var(--brand-blue);
        }

        .action-btn.secondary:hover {
            background: var(--light-blue);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                font-size: 11pt;
            }

            .action-bar {
                display: none !important;
            }

            .print-wrapper {
                max-width: none;
                margin: 0;
            }

            .side-branding {
                display: block !important;
                width: 25px;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .report-container {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .report-header,
            .key-stats,
            .section-header,
            .data-grid,
            .subsection,
            .highlights-box,
            .stat-cards-grid,
            .stat-card-small,
            .subsection-heading,
            .subsection-subheading {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            /* Ensure stat cards print in grid */
            .stat-cards-grid {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin: 12px 0;
            }

            .stat-card-small {
                background: #EBF4FA !important;
                border: 1px solid #ddd;
                padding: 10px;
                text-align: center;
            }

            /* Bullet lists for print */
            .bullet-list {
                margin: 10px 0;
                padding-left: 20px;
            }

            .bullet-list li {
                margin-bottom: 6px;
                page-break-inside: avoid;
            }

            /* Subsection headings print with gold color */
            .subsection-heading {
                color: #E5A54B !important;
                margin: 16px 0 10px 0;
                page-break-after: avoid;
            }

            .subsection-subheading {
                color: #E5A54B !important;
                margin: 12px 0 8px 0;
                page-break-after: avoid;
            }

            /* Expand all sections for print - force details open */
            .report-section,
            details.report-section {
                border: 1px solid #ccc;
                margin-bottom: 15px;
            }

            /* Force all details elements to show content when printing */
            details.report-section > .section-body {
                display: block !important;
            }

            details.report-section:not([open]) > .section-body {
                display: block !important;
            }

            .report-section[open] .section-body,
            .report-section .section-body {
                display: block !important;
            }

            .toggle-icon {
                display: none;
            }

            .section-header {
                background: var(--light-blue) !important;
            }

            .report-section {
                page-break-inside: avoid;
            }

            @page {
                margin: 0.4in 0.4in 0.4in 0;
                size: letter;
            }

            @page :first {
                margin-top: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .report-header {
                padding: 25px 20px;
            }

            .report-title {
                font-size: 28px;
            }

            .key-stats {
                flex-wrap: wrap;
                padding: 15px 20px;
            }

            .stat-item {
                flex: 0 0 50%;
                padding: 10px 0;
                border-right: none;
            }

            .report-content {
                padding: 20px;
            }

            .section-body {
                padding: 15px;
            }

            .action-bar {
                flex-direction: column;
                padding: 10px;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="action-bar">
        <button class="action-btn primary" onclick="window.print()">
            🖨️ Print / Save as PDF
        </button>
        <button class="action-btn secondary" onclick="expandAll()">
            📂 Expand All Sections
        </button>
    </div>

    <div class="print-wrapper">
        <div class="side-branding"></div>

        <div class="report-container">';

        // Add placeholder banner if this is a template report
        if ( $is_placeholder ) {
            $placeholder_reason = $footer['placeholder_reason'] ?? '';
            $reason_messages = array(
                'api_key_missing' => 'The OpenAI API key is not configured. Please add your API key in the plugin settings.',
                'api_error'       => 'Unable to connect to the AI service. Please try again later.',
                'parse_error'     => 'The AI response could not be processed. Please try regenerating the report.',
            );
            $reason_note = isset( $reason_messages[ $placeholder_reason ] )
                ? $reason_messages[ $placeholder_reason ]
                : 'AI-generated reports with specific local data are available when the service is configured.';

            $html .= '
            <div class="placeholder-banner">
                <div class="placeholder-banner-content">
                    <span class="placeholder-banner-icon">⚠️</span>
                    <span class="placeholder-banner-text">This is a template report with generic information</span>
                </div>
                <p class="placeholder-banner-note">' . esc_html( $reason_note ) . '</p>
            </div>';
        }

        $html .= '
            <header class="report-header">
                <div class="header-top">
                    <img src="' . esc_url( $logo_url ) . '" alt="relo2france" class="logo-img" onerror="this.outerHTML=\'<span class=logo-text><span class=blue>relo</span><span class=gold>2</span><span class=blue>france</span></span>\'">
                    <span class="report-type-badge">' . esc_html( $location_type ) . ' Report</span>
                </div>
                <h1 class="report-title">' . esc_html( $title ) . '</h1>';

        if ( $french_name ) {
            $html .= '<p class="french-name">' . esc_html( $french_name ) . '</p>';
        }

        $html .= '<p class="tagline">' . esc_html( $tagline ) . '</p>
            </header>';

        if ( $stats_html ) {
            $html .= '<div class="key-stats">' . $stats_html . '</div>';
        }

        $html .= '<main class="report-content">' . $sections_html . '</main>

            <footer class="report-footer">
                <p class="footer-sources">Data from ' . esc_html( implode( ', ', $data_sources ) ) . '</p>
                <p class="footer-brand">
                    <span class="blue">relo</span><span class="gold">2</span><span class="blue">france</span>.com
                </p>
                <p class="footer-meta">
                    Generated: ' . esc_html( $generated_date ) . ' • Version ' . esc_html( $version ) . ' • © ' . esc_html( gmdate( 'Y' ) ) . '
                </p>
            </footer>
        </div>
    </div>

    <script>
        function expandAll() {
            document.querySelectorAll(".report-section").forEach(function(section) {
                section.open = true;
            });
        }

        // Expand all sections before print
        window.addEventListener("beforeprint", function() {
            expandAll();
        });
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Save a research report link to user's documents
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function save_report_to_documents( $request ) {
        $report_id = absint( $request->get_param( 'id' ) );
        $user_id   = get_current_user_id();

        global $wpdb;
        $table_name = $wpdb->prefix . 'framt_research_reports';

        $report = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $report_id ),
            ARRAY_A
        );

        if ( ! $report ) {
            return new WP_Error( 'report_not_found', 'Research report not found.', array( 'status' => 404 ) );
        }

        $document_id = $this->save_report_link_to_documents( $report_id, $user_id );

        if ( ! $document_id ) {
            return new WP_Error( 'save_failed', 'Failed to save report to documents.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array(
            'success'     => true,
            'document_id' => $document_id,
            'message'     => 'Report saved to your documents.',
        ) );
    }

    /**
     * Get user's saved research reports
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_saved_research_reports( $request ) {
        $user_id = get_current_user_id();

        global $wpdb;
        $links_table   = $wpdb->prefix . 'framt_research_report_links';
        $reports_table = $wpdb->prefix . 'framt_research_reports';

        $this->maybe_create_research_tables();

        $saved = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.id, l.report_id, l.saved_at, r.location_type, r.location_code, r.location_name, r.updated_at
                 FROM {$links_table} l
                 JOIN {$reports_table} r ON l.report_id = r.id
                 WHERE l.user_id = %d
                 ORDER BY l.saved_at DESC",
                $user_id
            ),
            ARRAY_A
        );

        // Format reports for frontend - use 'reports' key to match TypeScript interface
        $reports = array();
        if ( $saved ) {
            foreach ( $saved as $item ) {
                $reports[] = array(
                    'id'            => (int) $item['report_id'],
                    'location_name' => $item['location_name'],
                    'location_type' => $item['location_type'],
                    'updated_at'    => $item['updated_at'],
                    'download_url'  => rest_url( self::NAMESPACE . '/research/report/' . $item['report_id'] . '/download' ),
                );
            }
        }

        return rest_ensure_response( array(
            'reports' => $reports,
        ) );
    }

    /**
     * Get regions data
     *
     * @return array
     */
    private function get_regions_data() {
        return array(
            array( 'code' => '84', 'name' => 'Auvergne-Rhône-Alpes', 'capital' => 'Lyon', 'population' => 8078652, 'area_km2' => 69711, 'climate' => 'continental', 'description' => 'Features the French Alps, the Rhône Valley, and historic Lyon.' ),
            array( 'code' => '27', 'name' => 'Bourgogne-Franche-Comté', 'capital' => 'Dijon', 'population' => 2805580, 'area_km2' => 47784, 'climate' => 'continental', 'description' => 'Famous for Burgundy wines and medieval heritage.' ),
            array( 'code' => '53', 'name' => 'Bretagne', 'capital' => 'Rennes', 'population' => 3373835, 'area_km2' => 27208, 'climate' => 'oceanic', 'description' => 'Celtic peninsula with dramatic coastlines.' ),
            array( 'code' => '24', 'name' => 'Centre-Val de Loire', 'capital' => 'Orléans', 'population' => 2576252, 'area_km2' => 39151, 'climate' => 'semi-oceanic', 'description' => 'Loire Valley châteaux and vineyards.' ),
            array( 'code' => '94', 'name' => 'Corse', 'capital' => 'Ajaccio', 'population' => 344679, 'area_km2' => 8680, 'climate' => 'mediterranean', 'description' => 'The Island of Beauty with mountains and beaches.' ),
            array( 'code' => '44', 'name' => 'Grand Est', 'capital' => 'Strasbourg', 'population' => 5561287, 'area_km2' => 57433, 'climate' => 'continental', 'description' => 'Blends French and Germanic influences.' ),
            array( 'code' => '32', 'name' => 'Hauts-de-France', 'capital' => 'Lille', 'population' => 6009976, 'area_km2' => 31813, 'climate' => 'oceanic', 'description' => 'Northern France with Flemish heritage.' ),
            array( 'code' => '11', 'name' => 'Île-de-France', 'capital' => 'Paris', 'population' => 12271794, 'area_km2' => 12012, 'climate' => 'semi-oceanic', 'description' => 'The Paris region - heart of France.' ),
            array( 'code' => '28', 'name' => 'Normandie', 'capital' => 'Rouen', 'population' => 3327477, 'area_km2' => 29907, 'climate' => 'oceanic', 'description' => 'D-Day beaches and medieval abbeys.' ),
            array( 'code' => '75', 'name' => 'Nouvelle-Aquitaine', 'capital' => 'Bordeaux', 'population' => 6033952, 'area_km2' => 84036, 'climate' => 'oceanic', 'description' => 'Largest region with Bordeaux wines and beaches.' ),
            array( 'code' => '76', 'name' => 'Occitanie', 'capital' => 'Toulouse', 'population' => 5973969, 'area_km2' => 72724, 'climate' => 'mediterranean', 'description' => 'From the Pyrenees to the Mediterranean.' ),
            array( 'code' => '52', 'name' => 'Pays de la Loire', 'capital' => 'Nantes', 'population' => 3838614, 'area_km2' => 32082, 'climate' => 'oceanic', 'description' => 'Atlantic coast with dynamic Nantes.' ),
            array( 'code' => '93', 'name' => "Provence-Alpes-Côte d'Azur", 'capital' => 'Marseille', 'population' => 5081101, 'area_km2' => 31400, 'climate' => 'mediterranean', 'description' => 'The French Riviera and Provence lavender.' ),
        );
    }

    /**
     * Get departments data (subset for common areas)
     *
     * @return array
     */
    private function get_departments_data() {
        return array(
            array( 'code' => '75', 'name' => 'Paris', 'region_code' => '11', 'region_name' => 'Île-de-France', 'prefecture' => 'Paris', 'population' => 2145906, 'area_km2' => 105, 'major_cities' => array( 'Paris' ) ),
            array( 'code' => '92', 'name' => 'Hauts-de-Seine', 'region_code' => '11', 'region_name' => 'Île-de-France', 'prefecture' => 'Nanterre', 'population' => 1624357, 'area_km2' => 176, 'major_cities' => array( 'Boulogne-Billancourt', 'Nanterre' ) ),
            array( 'code' => '33', 'name' => 'Gironde', 'region_code' => '75', 'region_name' => 'Nouvelle-Aquitaine', 'prefecture' => 'Bordeaux', 'population' => 1623749, 'area_km2' => 10000, 'major_cities' => array( 'Bordeaux', 'Mérignac', 'Pessac' ) ),
            array( 'code' => '24', 'name' => 'Dordogne', 'region_code' => '75', 'region_name' => 'Nouvelle-Aquitaine', 'prefecture' => 'Périgueux', 'population' => 413223, 'area_km2' => 9060, 'major_cities' => array( 'Périgueux', 'Bergerac', 'Sarlat-la-Canéda' ) ),
            array( 'code' => '06', 'name' => 'Alpes-Maritimes', 'region_code' => '93', 'region_name' => "Provence-Alpes-Côte d'Azur", 'prefecture' => 'Nice', 'population' => 1083310, 'area_km2' => 4299, 'major_cities' => array( 'Nice', 'Antibes', 'Cannes' ) ),
            array( 'code' => '13', 'name' => 'Bouches-du-Rhône', 'region_code' => '93', 'region_name' => "Provence-Alpes-Côte d'Azur", 'prefecture' => 'Marseille', 'population' => 2043110, 'area_km2' => 5087, 'major_cities' => array( 'Marseille', 'Aix-en-Provence' ) ),
            array( 'code' => '31', 'name' => 'Haute-Garonne', 'region_code' => '76', 'region_name' => 'Occitanie', 'prefecture' => 'Toulouse', 'population' => 1415757, 'area_km2' => 6309, 'major_cities' => array( 'Toulouse', 'Colomiers' ) ),
            array( 'code' => '34', 'name' => 'Hérault', 'region_code' => '76', 'region_name' => 'Occitanie', 'prefecture' => 'Montpellier', 'population' => 1176145, 'area_km2' => 6101, 'major_cities' => array( 'Montpellier', 'Béziers', 'Sète' ) ),
            array( 'code' => '69', 'name' => 'Rhône', 'region_code' => '84', 'region_name' => 'Auvergne-Rhône-Alpes', 'prefecture' => 'Lyon', 'population' => 1876051, 'area_km2' => 3249, 'major_cities' => array( 'Lyon', 'Villeurbanne' ) ),
            array( 'code' => '74', 'name' => 'Haute-Savoie', 'region_code' => '84', 'region_name' => 'Auvergne-Rhône-Alpes', 'prefecture' => 'Annecy', 'population' => 826105, 'area_km2' => 4388, 'major_cities' => array( 'Annecy', 'Annemasse' ) ),
            array( 'code' => '35', 'name' => 'Ille-et-Vilaine', 'region_code' => '53', 'region_name' => 'Bretagne', 'prefecture' => 'Rennes', 'population' => 1082052, 'area_km2' => 6775, 'major_cities' => array( 'Rennes', 'Saint-Malo' ) ),
            array( 'code' => '44', 'name' => 'Loire-Atlantique', 'region_code' => '52', 'region_name' => 'Pays de la Loire', 'prefecture' => 'Nantes', 'population' => 1429272, 'area_km2' => 6815, 'major_cities' => array( 'Nantes', 'Saint-Nazaire' ) ),
            array( 'code' => '67', 'name' => 'Bas-Rhin', 'region_code' => '44', 'region_name' => 'Grand Est', 'prefecture' => 'Strasbourg', 'population' => 1140939, 'area_km2' => 4755, 'major_cities' => array( 'Strasbourg', 'Haguenau' ) ),
            array( 'code' => '59', 'name' => 'Nord', 'region_code' => '32', 'region_name' => 'Hauts-de-France', 'prefecture' => 'Lille', 'population' => 2608346, 'area_km2' => 5743, 'major_cities' => array( 'Lille', 'Roubaix', 'Tourcoing' ) ),
        );
    }

    /**
     * Search communes data
     *
     * @param string $query Search query.
     * @param string $department_code Optional department filter.
     * @param int    $limit Max results.
     * @return array
     */
    private function search_communes_data( $query, $department_code = '', $limit = 20 ) {
        $communes = array(
            array( 'code' => '33063', 'name' => 'Bordeaux', 'postal_codes' => array( '33000' ), 'department_code' => '33', 'department_name' => 'Gironde', 'region_code' => '75', 'region_name' => 'Nouvelle-Aquitaine', 'population' => 260958, 'type' => 'city' ),
            array( 'code' => '75056', 'name' => 'Paris', 'postal_codes' => array( '75001' ), 'department_code' => '75', 'department_name' => 'Paris', 'region_code' => '11', 'region_name' => 'Île-de-France', 'population' => 2145906, 'type' => 'city' ),
            array( 'code' => '13055', 'name' => 'Marseille', 'postal_codes' => array( '13001' ), 'department_code' => '13', 'department_name' => 'Bouches-du-Rhône', 'region_code' => '93', 'region_name' => "Provence-Alpes-Côte d'Azur", 'population' => 870731, 'type' => 'city' ),
            array( 'code' => '69123', 'name' => 'Lyon', 'postal_codes' => array( '69001' ), 'department_code' => '69', 'department_name' => 'Rhône', 'region_code' => '84', 'region_name' => 'Auvergne-Rhône-Alpes', 'population' => 522969, 'type' => 'city' ),
            array( 'code' => '31555', 'name' => 'Toulouse', 'postal_codes' => array( '31000' ), 'department_code' => '31', 'department_name' => 'Haute-Garonne', 'region_code' => '76', 'region_name' => 'Occitanie', 'population' => 493465, 'type' => 'city' ),
            array( 'code' => '06088', 'name' => 'Nice', 'postal_codes' => array( '06000' ), 'department_code' => '06', 'department_name' => 'Alpes-Maritimes', 'region_code' => '93', 'region_name' => "Provence-Alpes-Côte d'Azur", 'population' => 342669, 'type' => 'city' ),
            array( 'code' => '44109', 'name' => 'Nantes', 'postal_codes' => array( '44000' ), 'department_code' => '44', 'department_name' => 'Loire-Atlantique', 'region_code' => '52', 'region_name' => 'Pays de la Loire', 'population' => 320732, 'type' => 'city' ),
            array( 'code' => '67482', 'name' => 'Strasbourg', 'postal_codes' => array( '67000' ), 'department_code' => '67', 'department_name' => 'Bas-Rhin', 'region_code' => '44', 'region_name' => 'Grand Est', 'population' => 287228, 'type' => 'city' ),
            array( 'code' => '24322', 'name' => 'Monsac', 'postal_codes' => array( '24440' ), 'department_code' => '24', 'department_name' => 'Dordogne', 'region_code' => '75', 'region_name' => 'Nouvelle-Aquitaine', 'population' => 385, 'type' => 'village' ),
        );

        $query_lower = strtolower( $query );
        $results = array_filter( $communes, function( $c ) use ( $query_lower, $department_code ) {
            $name_match = strpos( strtolower( $c['name'] ), $query_lower ) !== false;
            if ( $department_code && $c['department_code'] !== $department_code ) {
                return false;
            }
            return $name_match;
        } );

        return array_slice( array_values( $results ), 0, $limit );
    }

    /**
     * Get location name by type and code
     *
     * @param string $type Location type.
     * @param string $code Location code.
     * @return string|null
     */
    private function get_location_name( $type, $code ) {
        if ( 'region' === $type ) {
            foreach ( $this->get_regions_data() as $r ) {
                if ( $r['code'] === $code ) {
                    return $r['name'];
                }
            }
        } elseif ( 'department' === $type ) {
            foreach ( $this->get_departments_data() as $d ) {
                if ( $d['code'] === $code ) {
                    return $d['name'];
                }
            }
        } elseif ( 'commune' === $type ) {
            foreach ( $this->search_communes_data( '', '', 100 ) as $c ) {
                if ( $c['code'] === $code ) {
                    return $c['name'];
                }
            }
        }
        return null;
    }

    /**
     * Create research tables if they don't exist
     */
    private function maybe_create_research_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $reports_table = $wpdb->prefix . 'framt_research_reports';
        $links_table   = $wpdb->prefix . 'framt_research_report_links';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$reports_table}'" ) !== $reports_table ) {
            $sql = "CREATE TABLE {$reports_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                location_type varchar(20) NOT NULL,
                location_code varchar(10) NOT NULL,
                location_name varchar(255) NOT NULL,
                content longtext NOT NULL,
                version int(11) NOT NULL DEFAULT 1,
                generated_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY location_idx (location_type, location_code)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$links_table}'" ) !== $links_table ) {
            $sql = "CREATE TABLE {$links_table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                report_id bigint(20) unsigned NOT NULL,
                saved_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY user_report_idx (user_id, report_id)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }

    /**
     * Format report response
     *
     * @param array $report Raw report data.
     * @return array
     */
    private function format_report_response( $report ) {
        $content = is_string( $report['content'] ) ? json_decode( $report['content'], true ) : $report['content'];
        return array(
            'id'            => (int) $report['id'],
            'location_type' => $report['location_type'],
            'location_code' => $report['location_code'],
            'location_name' => $report['location_name'],
            'content'       => $content,
            'version'       => (int) ( $report['version'] ?? 1 ),
            'generated_at'  => $report['generated_at'] ?? $report['updated_at'],
            'updated_at'    => $report['updated_at'],
            'download_url'  => rest_url( self::NAMESPACE . '/research/report/' . $report['id'] . '/download' ),
        );
    }

    /**
     * Generate AI report content
     *
     * @param string $type Location type.
     * @param string $code Location code.
     * @param string $name Location name.
     * @return array|WP_Error
     */
    private function generate_ai_report( $type, $code, $name ) {
        // Use the main plugin's API key retrieval (encrypted storage)
        if ( ! class_exists( 'France_Relocation_Assistant' ) ) {
            error_log( 'FRA Report: Main plugin class not available' );
            return $this->generate_placeholder_report( $type, $code, $name, 'api_key_missing' );
        }

        $ai_api_key = France_Relocation_Assistant::get_api_key();

        if ( empty( $ai_api_key ) ) {
            error_log( 'FRA Report: API key not configured' );
            return $this->generate_placeholder_report( $type, $code, $name, 'api_key_missing' );
        }

        $prompt = $this->build_report_prompt( $type, $code, $name );

        $system_message = <<<SYSTEM
You are an expert on French geography, demographics, and relocation. You create comprehensive, data-driven reports for people considering relocating to France.

Your reports must:
1. Use accurate, current data from INSEE, Eurostat, and French government sources
2. Include specific numbers, prices (in euros), and statistics
3. Be practical and informative for someone planning to relocate
4. Cover both positives and realistic challenges/considerations

CRITICAL: You must respond with ONLY valid JSON. No markdown, no code blocks, no explanation text - just the raw JSON object starting with { and ending with }.
SYSTEM;

        // Get model from settings, default to claude-sonnet
        $model = get_option( 'fra_api_model', 'claude-sonnet-4-20250514' );

        // Call Anthropic Claude API (same as main plugin)
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => 120,
                'headers' => array(
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $ai_api_key,
                    'anthropic-version' => '2023-06-01',
                ),
                'body' => wp_json_encode( array(
                    'model'      => $model,
                    'max_tokens' => 8000,
                    'system'     => $system_message,
                    'messages'   => array(
                        array( 'role' => 'user', 'content' => $prompt ),
                    ),
                ) ),
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( 'FRA Report: API request failed - ' . $response->get_error_message() );
            return $this->generate_placeholder_report( $type, $code, $name, 'api_error' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // Check for API errors (invalid key, rate limit, etc.)
        if ( isset( $body['error'] ) ) {
            error_log( 'FRA Report: Claude API error - ' . ( $body['error']['message'] ?? 'Unknown error' ) );
            return $this->generate_placeholder_report( $type, $code, $name, 'api_error' );
        }

        // Claude API response format: content[0].text
        if ( ! isset( $body['content'][0]['text'] ) ) {
            error_log( 'FRA Report: Invalid API response structure - ' . wp_json_encode( $body ) );
            return $this->generate_placeholder_report( $type, $code, $name, 'api_error' );
        }

        $raw_text = $body['content'][0]['text'];

        // Extract JSON from response (Claude might wrap it in markdown code blocks)
        $json_text = $raw_text;
        if ( preg_match( '/```(?:json)?\s*([\s\S]*?)\s*```/', $raw_text, $matches ) ) {
            $json_text = $matches[1];
        }
        // Also try to find JSON object directly
        if ( preg_match( '/\{[\s\S]*\}/', $json_text, $matches ) ) {
            $json_text = $matches[0];
        }

        $content = json_decode( $json_text, true );
        if ( ! $content ) {
            error_log( 'FRA Report: Failed to parse AI response as JSON. Raw: ' . substr( $raw_text, 0, 500 ) );
            return $this->generate_placeholder_report( $type, $code, $name, 'parse_error' );
        }

        return $content;
    }

    /**
     * Build AI prompt for report generation
     *
     * Uses the relo2france template format (based on france-overview.jsx) with
     * sections adapted per location level. Produces detailed, location-specific
     * content with the depth expected for relocation research.
     *
     * @param string $type Location type (region, department, commune).
     * @param string $code Location code.
     * @param string $name Location name.
     * @return string
     */
    private function build_report_prompt( $type, $code, $name ) {
        $type_label = ucfirst( $type );
        $current_date = gmdate( 'F j, Y' );

        // Key stats to include based on level - formatted as array of objects
        $key_stats_format = array(
            'region'     => '{"value": "[X,XXX]", "label": "Area", "sublabel": "km²"},
      {"value": "[X.XM]", "label": "Population", "sublabel": "2024 estimate"},
      {"value": "[City]", "label": "Capital", "sublabel": "regional capital"},
      {"value": "[Key Value]", "label": "[Key Metric]", "sublabel": "[context]"}',
            'department' => '{"value": "[X,XXX]", "label": "Area", "sublabel": "km²"},
      {"value": "[XXX,XXX]", "label": "Population", "sublabel": "2024 estimate"},
      {"value": "[City]", "label": "Préfecture", "sublabel": "administrative center"},
      {"value": "[Key Value]", "label": "[Key Metric]", "sublabel": "[context]"}',
            'commune'    => '{"value": "[XX]", "label": "Area", "sublabel": "km²"},
      {"value": "[XX,XXX]", "label": "Population", "sublabel": "2024 estimate"},
      {"value": "[Xm]", "label": "Altitude", "sublabel": "elevation"},
      {"value": "[Key Value]", "label": "[Key Metric]", "sublabel": "[context]"}',
        );

        $taglines = array(
            'region'     => 'A Comprehensive Guide for Those Considering Relocation',
            'department' => 'Your Guide to Living and Working in This Area',
            'commune'    => 'Everything You Need to Know About This Community',
        );

        $prompt = <<<PROMPT
Generate a comprehensive, deeply researched relocation report for {$name} ({$type_label} in France).

This report follows the relo2france template format with StatCards, DataRows, and detailed structured content. It's for Americans and English speakers considering relocating to France.

REQUIRED JSON FORMAT:
{
  "header": {
    "title": "{$name}",
    "subtitle": "[Department ## • Region Name] or [Region, France]",
    "tagline": "[Primary distinguishing characteristic - one compelling sentence]",
    "secondary_tagline": "[Notable features or appeal]",
    "stat_cards": [
      {$key_stats_format[$type]}
    ]
  },
  "sections": {
    "geography": {
      "title": "Geography & Landscape",
      "icon": "Mountain",
      "intro": "[2-3 sentences describing physical location and defining characteristics. Include <strong>bold key facts</strong> inline.]",
      "stat_cards": [
        {"value": "[X,XXX]", "label": "[Metric]", "sublabel": "[context]"},
        {"value": "[XXX]", "label": "[Metric]", "sublabel": "[context]"},
        {"value": "[XX]", "label": "[Metric]", "sublabel": "[context]"},
        {"value": "[X]", "label": "[Metric]", "sublabel": "[context]"}
      ],
      "info_box": {
        "title": "Major Geographic Features",
        "items": [
          {"label": "[River/Mountain/etc]", "description": "[Brief description]"},
          {"label": "[Feature]", "description": "[Brief description]"}
        ]
      },
      "paragraphs": [
        "[Additional geographic details, terrain types, landscape zones - 2-3 sentences]",
        "[Supplementary information, interesting facts - 1-2 sentences, shown in lighter text]"
      ]
    },
    "climate": {
      "title": "Climate",
      "icon": "Thermometer",
      "intro": "[Opening describing climate type and general characteristics]",
      "stat_cards": [
        {"value": "[X°C]", "label": "Avg. Annual", "sublabel": "[descriptor]"},
        {"value": "[X-X°C]", "label": "Summer High", "sublabel": "Jun-Aug"},
        {"value": "[X-X°C]", "label": "Winter Low", "sublabel": "Dec-Feb"},
        {"value": "[Xmm]", "label": "Rainfall", "sublabel": "annual average"}
      ],
      "callout_box": {
        "title": "Seasonal Characteristics",
        "items": [
          {"label": "Spring (Mar-May)", "description": "[Specific description]"},
          {"label": "Summer (Jun-Aug)", "description": "[Specific description]"},
          {"label": "Autumn (Sep-Nov)", "description": "[Specific description]"},
          {"label": "Winter (Dec-Feb)", "description": "[Specific description]"}
        ]
      },
      "paragraphs": ["[Regional variations, microclimates]", "[Climate considerations for residents]"]
    },
    "demographics": {
      "title": "Population & Demographics",
      "icon": "Users",
      "intro": "[Opening with population figures and growth context]",
      "stat_cards": [
        {"value": "[X.XM]", "label": "Population", "sublabel": "2024 estimate"},
        {"value": "[X/km²]", "label": "Density", "sublabel": "[vs national avg]"},
        {"value": "[X.XM]", "label": "[Urban Area]", "sublabel": "metropolitan area"},
        {"value": "[±X.X%]", "label": "Growth", "sublabel": "annual"}
      ],
      "info_box": {
        "title": "Major Population Centers",
        "data_rows": [
          {"label": "[City 1]", "value": "~[XXX,000]"},
          {"label": "[City 2]", "value": "~[XX,000]"},
          {"label": "[City 3]", "value": "~[XX,000]"}
        ]
      },
      "paragraphs": ["[Population distribution, urban vs rural]", "[Age demographics, migration patterns, expat communities]"]
    },
    "economy": {
      "title": "Economy",
      "icon": "TrendingUp",
      "intro": "[Opening describing economic character and major industries]",
      "stat_cards": [
        {"value": "€[XX,000]", "label": "GDP/capita", "sublabel": "[vs national]"},
        {"value": "€[X.X]B", "label": "[Key Export]", "sublabel": "annual value"},
        {"value": "[XXX,000]", "label": "Employment", "sublabel": "total jobs"},
        {"value": "[X]M+", "label": "Tourism", "sublabel": "visitors annually"}
      ],
      "info_box": {
        "title": "Key Economic Sectors",
        "items": [
          {"label": "[Sector 1]", "description": "[Description with figures]"},
          {"label": "[Sector 2]", "description": "[Description with figures]"},
          {"label": "[Sector 3]", "description": "[Description with figures]"}
        ]
      },
      "paragraphs": ["[Recent developments, major employers]", "[Employment distribution, outlook]"]
    },
    "language": {
      "title": "Language & Identity",
      "icon": "Languages",
      "intro": "[Opening about linguistic landscape]",
      "info_box": {
        "title": "Linguistic Heritage",
        "items": [
          {"label": "[Regional language/dialect]", "description": "[Current status and usage]"},
          {"label": "Regional French", "description": "[Accent and vocabulary notes]"},
          {"label": "English Usage", "description": "[Proficiency level, expat communities]"}
        ]
      },
      "paragraphs": ["[Regional identity, cultural distinctiveness]", "[Historical context, place name etymology]"]
    },
    "gastronomy": {
      "title": "Gastronomy",
      "icon": "Utensils",
      "intro": "[Opening describing culinary character and influences]",
      "info_box": {
        "title": "Signature Dishes & Products",
        "grid_items": [
          {"label": "[Dish 1]", "description": "[Brief description]"},
          {"label": "[Dish 2]", "description": "[Brief description]"},
          {"label": "[Dish 3]", "description": "[Brief description]"},
          {"label": "[Product 1]", "description": "[Brief description]"},
          {"label": "[Wine/Beverage]", "description": "[Brief description]"},
          {"label": "[Specialty]", "description": "[Brief description]"}
        ]
      },
      "highlight_box": {
        "title": "[Signature Item Name]",
        "content": "[Detailed description of an iconic local food or drink - 2-3 sentences]",
        "color": "amber"
      },
      "paragraphs": ["[Local food production, markets, AOC products]", "[Restaurant scene, Michelin recognition]"]
    },
    "culture": {
      "title": "Culture & Lifestyle",
      "icon": "Music",
      "intro": "[Opening describing cultural character and lifestyle]",
      "info_box": {
        "title": "Cultural Highlights",
        "items": [
          {"label": "UNESCO World Heritage", "description": "[Sites if applicable]"},
          {"label": "[Museum/Institution]", "description": "[Description]"},
          {"label": "[Festival/Event]", "description": "[Description with dates if known]"}
        ]
      },
      "paragraphs": ["[Architectural heritage, arts scene]", "[Lifestyle, pace of life, recreational culture]", "[Major events, sports culture]"]
    },
    "administrative": {
      "title": "Administrative Geography",
      "icon": "MapPin",
      "intro": "[Opening about administrative structure]",
      "info_box": {
        "title": "[Number] Arrondissements/Cantons",
        "grid_items": [
          {"label": "[Name 1]", "description": "[Brief description]"},
          {"label": "[Name 2]", "description": "[Brief description]"},
          {"label": "[Name 3]", "description": "[Brief description]"}
        ]
      },
      "highlight_box": {
        "title": "Notable Communes",
        "items": [
          {"label": "[Commune 1]", "description": "[Why notable]"},
          {"label": "[Commune 2]", "description": "[Why notable]"},
          {"label": "[Commune 3]", "description": "[Why notable]"}
        ],
        "color": "teal"
      },
      "paragraphs": ["[Local governance structure]", "[Administrative details]"]
    },
    "quality_of_life": {
      "title": "Quality of Life",
      "icon": "Heart",
      "intro": "[Opening about overall quality of life and appeal]",
      "info_box": {
        "title": "Key Quality Indicators",
        "data_rows": [
          {"label": "TGV to Paris", "value": "[X hours]"},
          {"label": "Nearest Airport", "value": "[Name] - [X km]"},
          {"label": "Universities", "value": "[Number/Names]"},
          {"label": "Major Hospitals", "value": "[Number/Names]"}
        ]
      },
      "paragraphs": [
        "<strong>Healthcare:</strong> [Hospital system, specialist availability - 1-2 sentences]",
        "<strong>Education:</strong> [Universities, international schools - 1-2 sentences]",
        "<strong>Transport:</strong> [Rail, road, public transit - 1-2 sentences]"
      ],
      "considerations_box": {
        "content": "[Honest assessment of challenges - property prices, climate issues, bureaucracy, rural limitations - 2-3 sentences]"
      }
    },
    "environment": {
      "title": "Environment & Natural Heritage",
      "icon": "TreePine",
      "intro": "[Opening about environmental diversity and natural assets]",
      "paragraphs": ["<strong>[Major Natural Feature]:</strong> [Detailed description - 2-3 sentences]"],
      "highlight_box": {
        "title": "Natural Highlights",
        "items": [
          {"label": "[Park/Reserve]", "description": "[Description]"},
          {"label": "[Natural Feature]", "description": "[Description]"},
          {"label": "[Outdoor Activity]", "description": "[Description]"}
        ],
        "color": "emerald"
      },
      "paragraphs_footer": ["<strong>Conservation:</strong> [Environmental issues, protection efforts]", "[Outdoor recreation, hiking trails, seasonal activities]"]
    }
  },
  "footer": {
    "data_sources": "Data from INSEE, [regional-website.fr], and official French government sources (2024-2025)",
    "reference_links": ["insee.fr", "[regional-site.fr]", "service-public.fr"],
    "generated_date": "{$current_date}",
    "version": 1
  }
}

CRITICAL CONTENT REQUIREMENTS:

1. **ALL stat_cards MUST have real, specific data** - not placeholders
   - Use INSEE data for populations, densities
   - Use actual figures or reasonable estimates based on known data

2. **info_box items must be location-specific**
   - Name actual rivers, mountains, cities, dishes, wines
   - Reference real festivals, institutions, landmarks

3. **Every paragraph should contain specific facts**
   - "The Dordogne River runs 483km through the region" NOT "The area has several rivers"
   - "Temperatures reach 30-35°C in July-August" NOT "Summers are warm"

4. **For {$type_label} "{$name}", include:**
   - Exact population with year (e.g., "423,000 - 2024 INSEE")
   - Specific major cities/towns with populations
   - Named regional dishes and products
   - Real transportation times (TGV to Paris: X hours)
   - Actual climate data (avg temp, rainfall)

WRITING STYLE:
- Informative but accessible for relocators
- Practical focus with specific data points
- Include both advantages AND honest considerations
- Format numbers: 1,234 for thousands, €1,500 for currency

Return ONLY valid JSON. No markdown, no explanation.
PROMPT;

        return $prompt;
    }

    /**
     * Get section requirements based on location type
     *
     * @param string $type Location type.
     * @param string $name Location name.
     * @return string
     */
    private function get_section_requirements_for_prompt( $type, $name ) {
        $base_sections = <<<SECTIONS
1. geography - Geography & Landscape
   Required: terrain description, borders, notable features, rivers/mountains
   Include items array with: Region, Borders, Notable Features

2. climate - Climate
   Required: climate zone, seasonal temperatures, rainfall, special phenomena
   Include subsections for each season with temperature ranges

3. economy - Economy
   Required: key indicators, major industries, job market
   Include items array with: GDP/Income, Unemployment, Top Industries

4. food_wine - Food & Wine Culture
   Required: 3-5 named regional dishes, local wines, market culture
   Include items array with specific dishes and descriptions

5. culture_lifestyle - Culture & Lifestyle
   Required: work-life balance, social customs, festivals, expat presence
   Include subsections for customs and cultural touchstones

6. quality_of_life - Quality of Life
   Required: healthcare facilities, education options, infrastructure
   Include items array with specific metrics and facility names

7. transportation - Transportation
   Required: train service, public transit, airports, driving
   Include items array with journey times and connections

8. environment - Environment & Natural Heritage
   Required: parks, nature reserves, outdoor activities
   Include specific named locations and activities
SECTIONS;

        // Add type-specific sections
        if ( 'region' === $type ) {
            $base_sections .= <<<SECTIONS

9. demographics - Demographics & Population
   Required: population, density, major cities, age distribution
   Include items array with Population, Density, Major Cities, Median Age

10. language - Language & Communication
    Required: regional languages/dialects, English proficiency
    Include any notable linguistic features of the region

11. subdivisions - Departments & Key Areas
    Required: list departments with brief descriptions
    Include notable cities in each department
SECTIONS;
        } elseif ( 'department' === $type ) {
            $base_sections .= <<<SECTIONS

9. demographics - Demographics & Population
   Required: population, density, major communes
   Include items with population figures for top 3-4 communes

10. housing - Housing & Real Estate
    Required: specific rent ranges, property prices per m², neighborhood descriptions
    Include items with: Studio Rent, 1-Bed Rent, 2-Bed Rent, House Rent, Buying Price

11. practical_info - Practical Information
    Required: préfecture location, key administrative offices, banks, emergency
    Include specific addresses and contact information where possible
SECTIONS;
        } else {
            $base_sections .= <<<SECTIONS

9. housing - Housing & Real Estate
   Required: specific rent ranges, property prices per m², neighborhood descriptions
   Include items with: Studio Rent, 1-Bed Rent, 2-Bed Rent, House Rent, Buying Price

10. practical_info - Practical Information
    Required: mairie location/hours, administrative services, banks, emergency contacts
    Include specific addresses and useful practical details

11. local_life - Local Life & Community
    Required: community character, shops, amenities, what makes {$name} special
    Include specific named establishments and local favorites
SECTIONS;
        }

        return $base_sections;
    }

    /**
     * Generate placeholder report when AI unavailable
     *
     * Follows the relo2france template format with sections adapted per location level.
     *
     * @param string $type Location type (region, department, commune).
     * @param string $code Location code.
     * @param string $name Location name.
     * @param string $reason Optional reason why placeholder is being used.
     * @return array
     */
    private function generate_placeholder_report( $type, $code, $name, $reason = '' ) {
        $taglines = array(
            'region'     => 'A Comprehensive Guide for Those Considering Relocation',
            'department' => 'Your Guide to Living and Working in This Area',
            'commune'    => 'Everything You Need to Know About This Community',
        );

        $type_label = ucfirst( $type );

        // Build sections based on location type
        $sections = array(
            'geography' => array(
                'title'   => 'Geography & Landscape',
                'content' => "{$name} is located in France with diverse terrain ranging from plains to hills. The landscape offers a variety of natural features that make this area attractive for both residents and visitors. The area is well-connected to surrounding regions and offers easy access to major transportation routes.",
                'subsections' => array(
                    'natural_features' => array(
                        'title'   => 'Natural Features',
                        'content' => 'The area features a mix of natural landscapes typical of this part of France.',
                    ),
                ),
            ),
            'climate' => array(
                'title'   => 'Climate',
                'content' => 'The climate is temperate with four distinct seasons. Summers are warm (20-30°C) and winters are cool to cold (2-10°C). Rainfall is distributed throughout the year with slightly wetter periods in autumn and spring.',
                'subsections' => array(
                    'seasonal' => array(
                        'title'   => 'Seasonal Overview',
                        'content' => 'Spring (March-May) brings mild temperatures and flowering landscapes. Summer (June-August) is warm and ideal for outdoor activities. Autumn (September-November) offers beautiful colors and harvest festivals. Winter (December-February) is cool with occasional frost.',
                    ),
                ),
            ),
            'economy' => array(
                'title'   => 'Economy',
                'content' => "The local economy is diverse with opportunities in various sectors. France's strong social safety net and labor protections apply throughout the country, providing stability for workers and businesses alike.",
                'subsections' => array(
                    'key_industries' => array(
                        'title'   => 'Key Industries',
                        'content' => 'The area has a mix of traditional and modern industries including services, retail, healthcare, and public sector employment.',
                    ),
                    'job_market' => array(
                        'title'   => 'Job Market',
                        'content' => 'Employment opportunities vary by sector. Knowledge of French is typically required for most positions, though some international companies may operate in English.',
                    ),
                ),
            ),
            'food_wine' => array(
                'title'   => 'Food & Wine Culture',
                'content' => 'French gastronomy is central to daily life. Local markets, boulangeries, and restaurants offer high-quality food. The French typically enjoy a light breakfast, substantial lunch, and dinner as the main social meal.',
                'subsections' => array(
                    'regional_specialties' => array(
                        'title'   => 'Regional Specialties',
                        'content' => 'Each area of France has its own culinary traditions. You\'ll find local cheeses, charcuterie, pastries, and seasonal dishes that reflect the region\'s agricultural heritage.',
                    ),
                    'markets' => array(
                        'title'   => 'Markets & Shopping',
                        'content' => 'Weekly markets are a cornerstone of French life, offering fresh produce, meats, cheeses, and local specialties directly from producers.',
                    ),
                ),
            ),
            'culture_lifestyle' => array(
                'title'   => 'Culture & Lifestyle',
                'content' => 'French culture emphasizes quality of life, with the legal 35-hour workweek and minimum 5 weeks paid vacation. Sunday closures are common, and August sees many businesses close for summer holidays.',
                'subsections' => array(
                    'social_customs' => array(
                        'title'   => 'Social Customs',
                        'content' => 'Greeting etiquette is important—always say "Bonjour" when entering shops. La bise (cheek kisses) varies by region. The French value privacy and friendships develop slowly but deeply.',
                    ),
                    'expat_community' => array(
                        'title'   => 'Expat Community',
                        'content' => 'France has established expat communities, particularly in major cities. English-speaking groups, international clubs, and online communities can help newcomers settle in.',
                    ),
                ),
            ),
            'quality_of_life' => array(
                'title'   => 'Quality of Life',
                'content' => 'France consistently ranks highly in quality of life indices, particularly for healthcare, work-life balance, infrastructure, and cultural offerings.',
                'subsections' => array(
                    'healthcare' => array(
                        'title'   => 'Healthcare',
                        'content' => 'Universal healthcare coverage through Assurance Maladie reimburses 70-100% of most care. Hospital care, prescription drugs, and specialist visits are affordable by international standards.',
                    ),
                    'education' => array(
                        'title'   => 'Education',
                        'content' => 'Free public education from age 3 through university. The école maternelle, primaire, collège, lycée system is standardized nationwide. International schools are available in larger cities.',
                    ),
                    'infrastructure' => array(
                        'title'   => 'Infrastructure',
                        'content' => 'Reliable postal service, expanding fiber internet coverage, and strong mobile networks. Administrative processes can be paper-heavy but are increasingly digitized.',
                    ),
                ),
            ),
            'transportation' => array(
                'title'   => 'Transportation',
                'content' => 'France has excellent transportation infrastructure including the TGV high-speed rail network, well-maintained roads, and reliable public transit in urban areas.',
                'subsections' => array(
                    'public_transit' => array(
                        'title'   => 'Public Transit',
                        'content' => 'Major cities have comprehensive bus and tram networks. Smaller towns typically have bus services connecting to larger centers.',
                    ),
                    'train' => array(
                        'title'   => 'Train Services',
                        'content' => 'SNCF operates regional TER trains and the TGV high-speed network. Many areas have good rail connections to Paris and other major cities.',
                    ),
                ),
            ),
            'environment' => array(
                'title'   => 'Environment & Natural Heritage',
                'content' => 'France maintains extensive protected areas including national parks and regional nature parks. Environmental consciousness has grown significantly with widespread recycling and growing organic farming.',
                'subsections' => array(
                    'outdoor_activities' => array(
                        'title'   => 'Outdoor Activities',
                        'content' => 'Hiking trails (sentiers de grande randonnée) crisscross the country. Cycling infrastructure continues expanding, and outdoor recreation is deeply embedded in French culture.',
                    ),
                ),
            ),
        );

        // Add level-specific sections
        if ( 'region' === $type ) {
            $sections['demographics'] = array(
                'title'   => 'Demographics & Population',
                'content' => "This region is home to a diverse population. Like much of France, approximately 81% of residents live in urban areas. The median age reflects France's aging population, though urban centers tend to be younger.",
                'subsections' => array(
                    'major_cities' => array(
                        'title'   => 'Major Cities',
                        'content' => 'The region contains several significant urban centers offering different lifestyles from vibrant city life to quieter provincial towns.',
                    ),
                ),
            );
            $sections['language'] = array(
                'title'   => 'Language & Communication',
                'content' => 'French is the official language. Some regions have historical regional languages (Occitan, Breton, Alsatian, etc.) that may still be spoken locally. English proficiency has improved among younger generations but daily life requires functional French.',
            );
            $sections['subdivisions'] = array(
                'title'   => 'Departments',
                'content' => "This region is divided into several departments, each with its own préfecture and distinct character. Each department has a two-digit code used in postcodes and license plates.",
            );
        }

        if ( in_array( $type, array( 'department', 'commune' ), true ) ) {
            $sections['housing'] = array(
                'title'   => 'Housing & Real Estate',
                'content' => 'The housing market varies significantly by location. Rental and buying prices depend on proximity to city centers, transportation, and local amenities. Long-term rentals typically require French guarantors or deposit insurance.',
                'subsections' => array(
                    'rental_market' => array(
                        'title'   => 'Rental Market',
                        'content' => 'Rental prices vary by property type and location. Studios and small apartments are most common in city centers, while houses are more available in suburban and rural areas.',
                    ),
                    'neighborhoods' => array(
                        'title'   => 'Neighborhoods',
                        'content' => 'Each area has distinct neighborhoods with their own character, from historic centers to modern developments. Research specific quartiers to find the best fit for your lifestyle.',
                    ),
                ),
            );
            $sections['practical_info'] = array(
                'title'   => 'Practical Information',
                'content' => 'The mairie (town hall) is the center of local civic life, handling civil registration, elections, building permits, and local services. The préfecture handles department-level administration including residence permits.',
                'subsections' => array(
                    'administrative' => array(
                        'title'   => 'Administrative Offices',
                        'content' => 'Key offices include the mairie for local services, préfecture for residence permits, CAF for family benefits, and CPAM for health insurance.',
                    ),
                    'emergency' => array(
                        'title'   => 'Emergency Services',
                        'content' => 'Emergency numbers: 15 (SAMU medical), 17 (Police), 18 (Fire/Pompiers), 112 (European emergency). Pharmacies display green crosses and provide first-line medical advice.',
                    ),
                ),
            );
        }

        // Build key stats based on level
        $key_stats = array();
        if ( 'region' === $type ) {
            $key_stats = array(
                'area_km2'        => 0,
                'population'      => 0,
                'gdp_per_capita'  => 0,
                'life_expectancy' => 83.0,
            );
        } elseif ( 'department' === $type ) {
            $key_stats = array(
                'area_km2'          => 0,
                'population'        => 0,
                'median_income'     => 0,
                'unemployment_rate' => 7.0,
            );
        } else {
            $key_stats = array(
                'area_km2'        => 0,
                'population'      => 0,
                'density_per_km2' => 0,
                'average_rent'    => 0,
            );
        }

        return array(
            'header' => array(
                'title'       => $name,
                'french_name' => $name,
                'tagline'     => $taglines[ $type ],
                'key_stats'   => $key_stats,
            ),
            'sections' => $sections,
            'footer'   => array(
                'data_sources'      => array( 'INSEE', 'Eurostat', 'service-public.fr' ),
                'generated_date'    => gmdate( 'Y-m-d' ),
                'version'           => 1,
                'note'              => 'This is a template report. AI-generated reports will include specific local data and details.',
                'placeholder_reason' => $reason,
            ),
        );
    }

    /**
     * Save report link to user's documents
     *
     * @param int $report_id Report ID.
     * @param int $user_id User ID.
     * @return int|null
     */
    private function save_report_link_to_documents( $report_id, $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'framt_research_report_links';

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE user_id = %d AND report_id = %d",
            $user_id, $report_id
        ) );

        if ( $existing ) {
            $wpdb->update( $table_name, array( 'saved_at' => current_time( 'mysql' ) ), array( 'id' => $existing ) );
            return (int) $existing;
        }

        $result = $wpdb->insert( $table_name, array(
            'user_id'   => $user_id,
            'report_id' => $report_id,
            'saved_at'  => current_time( 'mysql' ),
        ) );

        return $result ? $wpdb->insert_id : null;
    }

    // ============================================
    // Support Ticket Methods
    // ============================================

    /**
     * Check if user has permission to access a support ticket
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_support_ticket_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        global $wpdb;
        $ticket_id = (int) $request->get_param( 'id' );
        $user_id   = get_current_user_id();

        $ticket_user = $wpdb->get_var( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}framt_messages WHERE id = %d",
            $ticket_id
        ) );

        if ( ! $ticket_user ) {
            return new WP_Error(
                'rest_ticket_not_found',
                'Support ticket not found.',
                array( 'status' => 404 )
            );
        }

        if ( (int) $ticket_user !== $user_id && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this ticket.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get all support tickets for the current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_support_tickets( $request ) {
        global $wpdb;
        $user_id = get_current_user_id();
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        $tickets = $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*,
                    (SELECT COUNT(*) FROM {$table_replies} WHERE message_id = m.id) as reply_count,
                    (SELECT content FROM {$table_replies} WHERE message_id = m.id ORDER BY created_at ASC LIMIT 1) as initial_message,
                    (SELECT created_at FROM {$table_replies} WHERE message_id = m.id ORDER BY created_at DESC LIMIT 1) as last_reply_at
             FROM {$table_messages} m
             WHERE m.user_id = %d
             ORDER BY m.updated_at DESC",
            $user_id
        ), ARRAY_A );

        // Format tickets
        foreach ( $tickets as &$ticket ) {
            $ticket['id']              = (int) $ticket['id'];
            $ticket['user_id']         = (int) $ticket['user_id'];
            $ticket['has_unread_user'] = (bool) $ticket['has_unread_user'];
            $ticket['reply_count']     = (int) $ticket['reply_count'];
            $ticket['relative_time']   = human_time_diff( strtotime( $ticket['updated_at'] ) ) . ' ago';
        }

        return rest_ensure_response( array(
            'tickets'      => $tickets,
            'unread_count' => $this->count_user_unread_tickets( $user_id ),
        ) );
    }

    /**
     * Get a single support ticket with replies
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_support_ticket( $request ) {
        global $wpdb;
        $ticket_id      = (int) $request->get_param( 'id' );
        $user_id        = get_current_user_id();
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        // Get ticket
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_messages} WHERE id = %d",
            $ticket_id
        ), ARRAY_A );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        // Mark as read by user
        $wpdb->update(
            $table_messages,
            array( 'has_unread_user' => 0 ),
            array( 'id' => $ticket_id ),
            array( '%d' ),
            array( '%d' )
        );

        // Get replies
        $replies = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name as author_name
             FROM {$table_replies} r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.message_id = %d
             ORDER BY r.created_at ASC",
            $ticket_id
        ), ARRAY_A );

        // Format replies
        foreach ( $replies as &$reply ) {
            $reply['id']            = (int) $reply['id'];
            $reply['message_id']    = (int) $reply['message_id'];
            $reply['user_id']       = (int) $reply['user_id'];
            $reply['is_admin']      = (bool) $reply['is_admin'];
            $reply['relative_time'] = human_time_diff( strtotime( $reply['created_at'] ) ) . ' ago';
            $reply['author_name']   = $reply['is_admin'] ? 'Relo2France Support' : $reply['author_name'];
        }

        // Format ticket
        $ticket['id']              = (int) $ticket['id'];
        $ticket['user_id']         = (int) $ticket['user_id'];
        $ticket['has_unread_user'] = false; // Just marked as read
        $ticket['reply_count']     = count( $replies );

        return rest_ensure_response( array(
            'ticket'  => $ticket,
            'replies' => $replies,
        ) );
    }

    /**
     * Create a new support ticket
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_support_ticket( $request ) {
        global $wpdb;
        $user_id        = get_current_user_id();
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        $subject = sanitize_text_field( $request->get_param( 'subject' ) );
        $content = sanitize_textarea_field( $request->get_param( 'content' ) );

        if ( empty( $subject ) || empty( $content ) ) {
            return new WP_Error(
                'missing_fields',
                'Subject and message content are required.',
                array( 'status' => 400 )
            );
        }

        // Create the ticket
        $inserted = $wpdb->insert(
            $table_messages,
            array(
                'user_id'          => $user_id,
                'subject'          => $subject,
                'status'           => 'open',
                'priority'         => 'normal',
                'has_unread_admin' => 1,
                'has_unread_user'  => 0,
                'created_at'       => current_time( 'mysql' ),
                'updated_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', 'Failed to create ticket.', array( 'status' => 500 ) );
        }

        $ticket_id = $wpdb->insert_id;

        // Add initial message as first reply
        $wpdb->insert(
            $table_replies,
            array(
                'message_id' => $ticket_id,
                'user_id'    => $user_id,
                'content'    => $content,
                'is_admin'   => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );

        // Send email notification to admin
        $this->send_support_admin_notification( $ticket_id, 'new' );

        return rest_ensure_response( array(
            'success'   => true,
            'message'   => 'Support ticket created successfully.',
            'ticket_id' => $ticket_id,
        ) );
    }

    /**
     * Reply to a support ticket
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function reply_to_support_ticket( $request ) {
        global $wpdb;
        $ticket_id      = (int) $request->get_param( 'id' );
        $user_id        = get_current_user_id();
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        $content = sanitize_textarea_field( $request->get_param( 'content' ) );

        if ( empty( $content ) ) {
            return new WP_Error(
                'missing_content',
                'Reply content is required.',
                array( 'status' => 400 )
            );
        }

        // Verify ticket exists and belongs to user
        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_messages} WHERE id = %d AND user_id = %d",
            $ticket_id, $user_id
        ), ARRAY_A );

        if ( ! $ticket ) {
            return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        }

        // Add reply
        $inserted = $wpdb->insert(
            $table_replies,
            array(
                'message_id' => $ticket_id,
                'user_id'    => $user_id,
                'content'    => $content,
                'is_admin'   => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', 'Failed to add reply.', array( 'status' => 500 ) );
        }

        // Update ticket timestamp and unread flags
        $wpdb->update(
            $table_messages,
            array(
                'updated_at'       => current_time( 'mysql' ),
                'has_unread_admin' => 1,
                'has_unread_user'  => 0,
                'status'           => 'open', // Reopen if closed
            ),
            array( 'id' => $ticket_id ),
            array( '%s', '%d', '%d', '%s' ),
            array( '%d' )
        );

        // Send email notification to admin
        $this->send_support_admin_notification( $ticket_id, 'reply' );

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Reply sent successfully.',
        ) );
    }

    /**
     * Delete a support ticket
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_support_ticket( $request ) {
        global $wpdb;
        $ticket_id      = (int) $request->get_param( 'id' );
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        // Delete replies first
        $wpdb->delete( $table_replies, array( 'message_id' => $ticket_id ), array( '%d' ) );

        // Delete ticket
        $deleted = $wpdb->delete( $table_messages, array( 'id' => $ticket_id ), array( '%d' ) );

        if ( ! $deleted ) {
            return new WP_Error( 'db_error', 'Failed to delete ticket.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Ticket deleted successfully.',
        ) );
    }

    /**
     * Get unread support ticket count
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_support_unread_count( $request ) {
        $user_id = get_current_user_id();
        return rest_ensure_response( array(
            'count' => $this->count_user_unread_tickets( $user_id ),
        ) );
    }

    /**
     * Count unread tickets for a user
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function count_user_unread_tickets( $user_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}framt_messages WHERE user_id = %d AND has_unread_user = 1",
            $user_id
        ) );
    }

    /**
     * Send admin notification for support ticket
     *
     * @param int    $ticket_id Ticket ID.
     * @param string $type      Notification type (new, reply).
     * @return void
     */
    private function send_support_admin_notification( $ticket_id, $type = 'new' ) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'framt_messages';
        $table_replies  = $wpdb->prefix . 'framt_message_replies';

        $ticket = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table_messages} WHERE id = %d",
            $ticket_id
        ), ARRAY_A );

        if ( ! $ticket ) {
            return;
        }

        $user = get_user_by( 'id', $ticket['user_id'] );
        if ( ! $user ) {
            return;
        }

        // Get the latest reply content
        $latest_reply = $wpdb->get_row( $wpdb->prepare(
            "SELECT content FROM {$table_replies} WHERE message_id = %d ORDER BY created_at DESC LIMIT 1",
            $ticket_id
        ), ARRAY_A );

        $message_content = $latest_reply ? $latest_reply['content'] : '';
        $admin_email     = get_option( 'admin_email' );

        $subject = 'new' === $type
            ? '🔔 [Relo2France] New Support Message: ' . $ticket['subject']
            : '💬 [Relo2France] Member Reply: ' . $ticket['subject'];

        // Build HTML email
        $body = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1e3a5f; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; font-size: 18px; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .message-box { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #e85a1b; margin: 15px 0; }
        .meta { color: #6b7280; font-size: 14px; margin-bottom: 10px; }
        .meta strong { color: #1e3a5f; }
        .button { display: inline-block; background: #e85a1b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
        .footer { padding: 15px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>' . ( 'new' === $type ? '📩 New Support Message' : '💬 New Reply to Support Ticket' ) . '</h2>
        </div>
        <div class="content">
            <div class="meta">
                <strong>From:</strong> ' . esc_html( $user->display_name ) . ' (' . esc_html( $user->user_email ) . ')<br>
                <strong>Subject:</strong> ' . esc_html( $ticket['subject'] ) . '<br>
                <strong>Status:</strong> ' . ucfirst( $ticket['status'] ) . '
            </div>

            <div class="message-box">
                <strong>Message:</strong><br><br>
                ' . nl2br( esc_html( $message_content ) ) . '
            </div>

            <a href="' . admin_url( 'admin.php?page=fra-messages&message_id=' . $ticket_id ) . '" class="button">
                View & Respond
            </a>
        </div>
        <div class="footer">
            This is an automated notification from Relo2France Member Support System.
        </div>
    </div>
</body>
</html>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $admin_email, $subject, $body, $headers );
    }

    // =========================================================================
    // Family Members Methods (Paid Add-on Ready)
    // =========================================================================

    /**
     * Check if user has access to family management feature
     * This is the toggle point for paid add-on functionality
     *
     * @return bool|WP_Error True if allowed, WP_Error otherwise
     */
    public function check_family_feature_permission() {
        // First check basic member permission
        if ( ! $this->check_member_permission() ) {
            return new WP_Error(
                'rest_forbidden',
                'You must be logged in to access this resource.',
                array( 'status' => 401 )
            );
        }

        // Check if family feature is enabled for this user
        // TOGGLE POINT: Change this logic to check membership level
        $user_id = get_current_user_id();
        $feature_enabled = $this->is_family_feature_enabled( $user_id );

        if ( ! $feature_enabled ) {
            return new WP_Error(
                'feature_not_available',
                'Family management is a premium feature. Please upgrade your membership to access it.',
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check if family feature is enabled for user
     * TOGGLE POINT: Modify this to integrate with MemberPress or other membership plugins
     *
     * @param int $user_id User ID.
     * @return bool
     */
    private function is_family_feature_enabled( $user_id ) {
        // Option 1: Check user meta flag (manual override)
        $manual_override = get_user_meta( $user_id, 'framt_family_feature_enabled', true );
        if ( '1' === $manual_override ) {
            return true;
        }
        if ( '0' === $manual_override ) {
            return false;
        }

        // Option 2: Check global setting (enable for all members during beta)
        $global_enabled = get_option( 'framt_family_feature_enabled', '1' ); // Default ON for now
        if ( '1' === $global_enabled ) {
            return true;
        }

        // Option 3: Check MemberPress membership level (for future integration)
        // Uncomment and modify when ready to integrate with MemberPress:
        /*
        if ( function_exists( 'mepr_get_current_user' ) ) {
            $mepr_user = mepr_get_current_user();
            $premium_levels = array( 'premium', 'family-plan', 'professional' );
            foreach ( $mepr_user->active_product_subscriptions() as $product_id ) {
                $product = new MeprProduct( $product_id );
                if ( in_array( $product->post_name, $premium_levels, true ) ) {
                    return true;
                }
            }
        }
        */

        return false;
    }

    /**
     * Get family feature status for frontend
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_family_feature_status( $request ) {
        $user_id = get_current_user_id();
        $enabled = $this->is_family_feature_enabled( $user_id );

        return rest_ensure_response( array(
            'enabled'     => $enabled,
            'upgrade_url' => $enabled ? null : home_url( '/membership/' ),
            'message'     => $enabled ? null : 'Upgrade to Premium to manage family members.',
        ) );
    }

    /**
     * Get all family members for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_family_members( $request ) {
        $user_id = get_current_user_id();
        $members = get_user_meta( $user_id, 'framt_family_members', true );

        if ( empty( $members ) || ! is_array( $members ) ) {
            $members = array();
        }

        // Add feature status to response
        $feature_enabled = $this->is_family_feature_enabled( $user_id );

        return rest_ensure_response( array(
            'members'         => array_values( $members ),
            'feature_enabled' => $feature_enabled,
            'can_edit'        => $feature_enabled,
        ) );
    }

    /**
     * Get single family member
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_family_member( $request ) {
        $user_id   = get_current_user_id();
        $member_id = (int) $request->get_param( 'member_id' );
        $members   = get_user_meta( $user_id, 'framt_family_members', true );

        if ( empty( $members ) || ! is_array( $members ) ) {
            return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
        }

        foreach ( $members as $member ) {
            if ( (int) $member['id'] === $member_id ) {
                return rest_ensure_response( $member );
            }
        }

        return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
    }

    /**
     * Create new family member
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_family_member( $request ) {
        $user_id = get_current_user_id();
        $params  = $request->get_json_params();

        // Validate required fields
        if ( empty( $params['name'] ) || empty( $params['relationship'] ) ) {
            return new WP_Error( 'missing_fields', 'Name and relationship are required.', array( 'status' => 400 ) );
        }

        // Get existing members
        $members = get_user_meta( $user_id, 'framt_family_members', true );
        if ( empty( $members ) || ! is_array( $members ) ) {
            $members = array();
        }

        // Generate new ID
        $max_id = 0;
        foreach ( $members as $m ) {
            if ( isset( $m['id'] ) && $m['id'] > $max_id ) {
                $max_id = $m['id'];
            }
        }

        $new_member = array(
            'id'           => $max_id + 1,
            'name'         => sanitize_text_field( $params['name'] ),
            'relationship' => sanitize_text_field( $params['relationship'] ),
            'birthDate'    => sanitize_text_field( $params['birthDate'] ?? '' ),
            'nationality'  => sanitize_text_field( $params['nationality'] ?? '' ),
            'visaStatus'   => sanitize_text_field( $params['visaStatus'] ?? 'pending' ),
            'documents'    => array(
                'passport'           => ! empty( $params['documents']['passport'] ),
                'birthCertificate'   => ! empty( $params['documents']['birthCertificate'] ),
                'marriageCertificate' => ! empty( $params['documents']['marriageCertificate'] ),
                'photos'             => ! empty( $params['documents']['photos'] ),
            ),
            'notes'        => sanitize_textarea_field( $params['notes'] ?? '' ),
            'created_at'   => current_time( 'mysql' ),
            'updated_at'   => current_time( 'mysql' ),
        );

        $members[] = $new_member;
        update_user_meta( $user_id, 'framt_family_members', $members );

        return rest_ensure_response( $new_member );
    }

    /**
     * Update family member
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_family_member( $request ) {
        $user_id   = get_current_user_id();
        $member_id = (int) $request->get_param( 'member_id' );
        $params    = $request->get_json_params();
        $members   = get_user_meta( $user_id, 'framt_family_members', true );

        if ( empty( $members ) || ! is_array( $members ) ) {
            return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
        }

        $found = false;
        foreach ( $members as $key => $member ) {
            if ( (int) $member['id'] === $member_id ) {
                // Update fields
                $members[ $key ] = array_merge( $member, array(
                    'name'         => sanitize_text_field( $params['name'] ?? $member['name'] ),
                    'relationship' => sanitize_text_field( $params['relationship'] ?? $member['relationship'] ),
                    'birthDate'    => sanitize_text_field( $params['birthDate'] ?? $member['birthDate'] ),
                    'nationality'  => sanitize_text_field( $params['nationality'] ?? $member['nationality'] ),
                    'visaStatus'   => sanitize_text_field( $params['visaStatus'] ?? $member['visaStatus'] ),
                    'notes'        => sanitize_textarea_field( $params['notes'] ?? $member['notes'] ),
                    'updated_at'   => current_time( 'mysql' ),
                ) );

                // Update documents if provided
                if ( isset( $params['documents'] ) ) {
                    $members[ $key ]['documents'] = array(
                        'passport'           => ! empty( $params['documents']['passport'] ),
                        'birthCertificate'   => ! empty( $params['documents']['birthCertificate'] ),
                        'marriageCertificate' => ! empty( $params['documents']['marriageCertificate'] ),
                        'photos'             => ! empty( $params['documents']['photos'] ),
                    );
                }

                $found = $members[ $key ];
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
        }

        update_user_meta( $user_id, 'framt_family_members', $members );

        return rest_ensure_response( $found );
    }

    /**
     * Delete family member
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_family_member( $request ) {
        $user_id   = get_current_user_id();
        $member_id = (int) $request->get_param( 'member_id' );
        $members   = get_user_meta( $user_id, 'framt_family_members', true );

        if ( empty( $members ) || ! is_array( $members ) ) {
            return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
        }

        $found = false;
        foreach ( $members as $key => $member ) {
            if ( (int) $member['id'] === $member_id ) {
                unset( $members[ $key ] );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return new WP_Error( 'not_found', 'Family member not found.', array( 'status' => 404 ) );
        }

        // Re-index array
        $members = array_values( $members );
        update_user_meta( $user_id, 'framt_family_members', $members );

        return rest_ensure_response( array( 'success' => true, 'message' => 'Family member deleted.' ) );
    }
}
