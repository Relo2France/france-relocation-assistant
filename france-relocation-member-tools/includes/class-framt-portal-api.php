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
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function check_project_permission( $request ) {
        $base_check = $this->check_member_permission();
        if ( is_wp_error( $base_check ) ) {
            return $base_check;
        }

        $project_id = $request->get_param( 'id' );
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

        $response = array(
            'project'         => $project->to_array(),
            'stages'          => $project->get_stage_progress(),
            'task_stats'      => $project->get_task_stats(),
            'upcoming_tasks'  => array_map(
                function( $task ) {
                    return $task->to_array();
                },
                FRAMT_Task::get_upcoming( $project->id, 14, 5 )
            ),
            'overdue_tasks'   => array_map(
                function( $task ) {
                    return $task->to_array();
                },
                FRAMT_Task::get_overdue( $project->id )
            ),
            'recent_activity' => array_map(
                function( $activity ) {
                    return $activity->to_array();
                },
                FRAMT_Activity::get_by_project( $project->id, array( 'limit' => 10 ) )
            ),
        );

        return rest_ensure_response( $response );
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

        // Get all profile meta
        $profile_fields = array(
            // Personal Information
            'first_name'           => $user->first_name,
            'last_name'            => $user->last_name,
            'email'                => $user->user_email,
            'date_of_birth'        => get_user_meta( $user_id, 'fra_date_of_birth', true ),
            'place_of_birth'       => get_user_meta( $user_id, 'fra_place_of_birth', true ),
            'country_of_birth'     => get_user_meta( $user_id, 'fra_country_of_birth', true ),
            'nationality'          => get_user_meta( $user_id, 'fra_nationality', true ),
            'secondary_nationality' => get_user_meta( $user_id, 'fra_secondary_nationality', true ),
            'gender'               => get_user_meta( $user_id, 'fra_gender', true ),
            'marital_status'       => get_user_meta( $user_id, 'fra_marital_status', true ),

            // Passport Information
            'passport_number'      => get_user_meta( $user_id, 'fra_passport_number', true ),
            'passport_issue_date'  => get_user_meta( $user_id, 'fra_passport_issue_date', true ),
            'passport_expiry_date' => get_user_meta( $user_id, 'fra_passport_expiry_date', true ),
            'passport_issue_place' => get_user_meta( $user_id, 'fra_passport_issue_place', true ),

            // Contact Information
            'phone'                => get_user_meta( $user_id, 'fra_phone', true ),
            'phone_secondary'      => get_user_meta( $user_id, 'fra_phone_secondary', true ),

            // Current Address
            'current_address'      => get_user_meta( $user_id, 'fra_current_address', true ),
            'current_city'         => get_user_meta( $user_id, 'fra_current_city', true ),
            'current_state'        => get_user_meta( $user_id, 'fra_current_state', true ),
            'current_postal_code'  => get_user_meta( $user_id, 'fra_current_postal_code', true ),
            'current_country'      => get_user_meta( $user_id, 'fra_current_country', true ),

            // French Address (if applicable)
            'french_address'       => get_user_meta( $user_id, 'fra_french_address', true ),
            'french_city'          => get_user_meta( $user_id, 'fra_french_city', true ),
            'french_postal_code'   => get_user_meta( $user_id, 'fra_french_postal_code', true ),
            'french_department'    => get_user_meta( $user_id, 'fra_french_department', true ),

            // Employment/Financial
            'occupation'           => get_user_meta( $user_id, 'fra_occupation', true ),
            'employer'             => get_user_meta( $user_id, 'fra_employer', true ),
            'annual_income'        => get_user_meta( $user_id, 'fra_annual_income', true ),
            'income_currency'      => get_user_meta( $user_id, 'fra_income_currency', true ),

            // Family Information
            'spouse_name'          => get_user_meta( $user_id, 'fra_spouse_name', true ),
            'spouse_nationality'   => get_user_meta( $user_id, 'fra_spouse_nationality', true ),
            'spouse_dob'           => get_user_meta( $user_id, 'fra_spouse_dob', true ),
            'number_of_dependents' => get_user_meta( $user_id, 'fra_number_of_dependents', true ),
            'dependents'           => get_user_meta( $user_id, 'fra_dependents', true ) ?: array(),

            // Visa/Immigration
            'visa_type_applying'   => get_user_meta( $user_id, 'fra_visa_type_applying', true ),
            'previous_visas'       => get_user_meta( $user_id, 'fra_previous_visas', true ) ?: array(),
            'previous_france_visits' => get_user_meta( $user_id, 'fra_previous_france_visits', true ),
            'schengen_history'     => get_user_meta( $user_id, 'fra_schengen_history', true ),

            // Health Insurance
            'health_insurance_provider' => get_user_meta( $user_id, 'fra_health_insurance_provider', true ),
            'health_insurance_policy'   => get_user_meta( $user_id, 'fra_health_insurance_policy', true ),
            'health_insurance_expiry'   => get_user_meta( $user_id, 'fra_health_insurance_expiry', true ),

            // Emergency Contact
            'emergency_contact_name'    => get_user_meta( $user_id, 'fra_emergency_contact_name', true ),
            'emergency_contact_phone'   => get_user_meta( $user_id, 'fra_emergency_contact_phone', true ),
            'emergency_contact_relation' => get_user_meta( $user_id, 'fra_emergency_contact_relation', true ),

            // Preferences
            'preferred_language'   => get_user_meta( $user_id, 'fra_preferred_language', true ) ?: 'en',
            'timezone'             => get_user_meta( $user_id, 'fra_timezone', true ) ?: wp_timezone_string(),
        );

        $response = array(
            'id'         => $user_id,
            'avatar_url' => get_avatar_url( $user_id, array( 'size' => 200 ) ),
            'fields'     => $profile_fields,
            'updated_at' => get_user_meta( $user_id, 'fra_profile_updated', true ),
        );

        return rest_ensure_response( $response );
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

        // Fields that map to WP user table
        $wp_user_fields = array( 'first_name', 'last_name', 'email' );

        // Update WP user fields
        $user_data = array( 'ID' => $user_id );
        foreach ( $wp_user_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                if ( 'email' === $field ) {
                    $user_data['user_email'] = sanitize_email( $params[ $field ] );
                } else {
                    $user_data[ $field ] = sanitize_text_field( $params[ $field ] );
                }
            }
        }

        if ( count( $user_data ) > 1 ) {
            $result = wp_update_user( $user_data );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        // All other fields go to user meta
        $meta_fields = array(
            'date_of_birth', 'place_of_birth', 'country_of_birth', 'nationality',
            'secondary_nationality', 'gender', 'marital_status',
            'passport_number', 'passport_issue_date', 'passport_expiry_date', 'passport_issue_place',
            'phone', 'phone_secondary',
            'current_address', 'current_city', 'current_state', 'current_postal_code', 'current_country',
            'french_address', 'french_city', 'french_postal_code', 'french_department',
            'occupation', 'employer', 'annual_income', 'income_currency',
            'spouse_name', 'spouse_nationality', 'spouse_dob', 'number_of_dependents',
            'visa_type_applying', 'previous_france_visits', 'schengen_history',
            'health_insurance_provider', 'health_insurance_policy', 'health_insurance_expiry',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'preferred_language', 'timezone',
        );

        foreach ( $meta_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $value = is_array( $params[ $field ] ) ? $params[ $field ] : sanitize_text_field( $params[ $field ] );
                update_user_meta( $user_id, 'fra_' . $field, $value );
            }
        }

        // Handle array fields separately with deep sanitization
        if ( isset( $params['dependents'] ) && is_array( $params['dependents'] ) ) {
            update_user_meta( $user_id, 'fra_dependents', $this->sanitize_array_recursive( $params['dependents'] ) );
        }
        if ( isset( $params['previous_visas'] ) && is_array( $params['previous_visas'] ) ) {
            update_user_meta( $user_id, 'fra_previous_visas', $this->sanitize_array_recursive( $params['previous_visas'] ) );
        }

        // Update timestamp
        update_user_meta( $user_id, 'fra_profile_updated', current_time( 'mysql' ) );

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

        // Define required fields by category
        $categories = array(
            'personal' => array(
                'label'  => 'Personal Information',
                'fields' => array( 'first_name', 'last_name', 'date_of_birth', 'nationality', 'gender' ),
            ),
            'passport' => array(
                'label'  => 'Passport Details',
                'fields' => array( 'passport_number', 'passport_issue_date', 'passport_expiry_date' ),
            ),
            'contact' => array(
                'label'  => 'Contact Information',
                'fields' => array( 'phone', 'current_address', 'current_city', 'current_country' ),
            ),
            'employment' => array(
                'label'  => 'Employment & Financial',
                'fields' => array( 'occupation', 'annual_income' ),
            ),
            'insurance' => array(
                'label'  => 'Health Insurance',
                'fields' => array( 'health_insurance_provider', 'health_insurance_policy' ),
            ),
            'emergency' => array(
                'label'  => 'Emergency Contact',
                'fields' => array( 'emergency_contact_name', 'emergency_contact_phone' ),
            ),
        );

        $user           = get_userdata( $user_id );
        $completion     = array();
        $total_fields   = 0;
        $filled_fields  = 0;

        foreach ( $categories as $key => $category ) {
            $cat_filled = 0;
            $cat_total  = count( $category['fields'] );
            $missing    = array();

            foreach ( $category['fields'] as $field ) {
                $total_fields++;
                $value = '';

                if ( in_array( $field, array( 'first_name', 'last_name' ), true ) ) {
                    $value = $user->$field;
                } else {
                    $value = get_user_meta( $user_id, 'fra_' . $field, true );
                }

                if ( ! empty( $value ) ) {
                    $cat_filled++;
                    $filled_fields++;
                } else {
                    $missing[] = $field;
                }
            }

            $completion[ $key ] = array(
                'label'      => $category['label'],
                'completed'  => $cat_filled,
                'total'      => $cat_total,
                'percentage' => $cat_total > 0 ? round( ( $cat_filled / $cat_total ) * 100 ) : 0,
                'missing'    => $missing,
            );
        }

        $response = array(
            'overall_percentage' => $total_fields > 0 ? round( ( $filled_fields / $total_fields ) * 100 ) : 0,
            'filled_fields'      => $filled_fields,
            'total_fields'       => $total_fields,
            'categories'         => $completion,
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
     * Get checklist items for a type
     *
     * @param string $type Checklist type
     * @return array Checklist items
     */
    private function get_checklist_items( $type ) {
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
     * Get glossary data
     *
     * @return array Glossary categories and terms
     */
    private function get_glossary_data() {
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
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_document( $request ) {
        $params = $request->get_json_params();
        $type   = $params['document_type'] ?? '';
        $data   = $params['document_data'] ?? array();

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
    public function send_chat_message( $request ) {
        $params           = $request->get_json_params();
        $message          = $params['message'] ?? '';
        $context          = $params['context'] ?? 'general';
        $include_practice = $params['include_practice'] ?? true;
        $user_id          = get_current_user_id();

        if ( empty( $message ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'error'   => 'Message is required.',
            ) );
        }

        // In production, this would call an AI service
        // For now, return contextual responses based on context
        $response_text = $this->generate_chat_response( $message, $context, $include_practice );

        // Generate relevant sources based on the query
        $sources = $this->get_chat_sources( $message, $context );

        // Save chat history
        $history = get_user_meta( $user_id, 'fra_chat_history', true ) ?: array();
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
        // Simple keyword-based responses for now
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

        if ( strpos( $lower_message, 'license' ) !== false || strpos( $lower_message, 'drive' ) !== false || strpos( $lower_message, 'permis' ) !== false ) {
            $response = 'US driving licenses can be used in France for up to one year. After that, you\'ll need to exchange it for a French license. Not all US states have reciprocal agreements with France, so check if your state qualifies for direct exchange.';
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
        $category_display = ! empty( $context ) ? $context : 'relocating to France';
        return 'Thank you for your question about ' . $category_display . '. I can help you with various aspects of relocating to France including visa applications, healthcare registration, banking, housing, and administrative procedures. Could you provide more details about what specific information you\'re looking for?';
    }

    /**
     * Get relevant sources for a chat response
     *
     * @param string $message User message
     * @param string $context Context/category
     * @return array Sources array
     */
    private function get_chat_sources( $message, $context ) {
        $lower_message = strtolower( $message );
        $sources       = array();

        // Match sources based on message content
        if ( strpos( $lower_message, 'vls-ts' ) !== false || strpos( $lower_message, 'validate' ) !== false || strpos( $lower_message, 'visa' ) !== false ) {
            $sources[] = array(
                'title'     => 'VLS-TS Validation Guide',
                'category'  => 'visas',
                'relevance' => 0.95,
            );
            $sources[] = array(
                'title'     => 'OFII Appointment Process',
                'category'  => 'visas',
                'relevance' => 0.80,
            );
        }

        if ( strpos( $lower_message, 'bank' ) !== false || strpos( $lower_message, 'account' ) !== false ) {
            $sources[] = array(
                'title'     => 'French Banking Guide',
                'category'  => 'banking',
                'relevance' => 0.92,
            );
            $sources[] = array(
                'title'     => 'Opening Your First Account',
                'category'  => 'banking',
                'relevance' => 0.85,
            );
        }

        if ( strpos( $lower_message, 'carte vitale' ) !== false || strpos( $lower_message, 'health' ) !== false ) {
            $sources[] = array(
                'title'     => 'French Healthcare System',
                'category'  => 'healthcare',
                'relevance' => 0.93,
            );
            $sources[] = array(
                'title'     => 'CPAM Registration Guide',
                'category'  => 'healthcare',
                'relevance' => 0.88,
            );
        }

        if ( strpos( $lower_message, 'tax' ) !== false ) {
            $sources[] = array(
                'title'     => 'US-France Tax Treaty',
                'category'  => 'taxes',
                'relevance' => 0.90,
            );
        }

        if ( strpos( $lower_message, 'license' ) !== false || strpos( $lower_message, 'drive' ) !== false ) {
            $sources[] = array(
                'title'     => 'License Exchange Process',
                'category'  => 'driving',
                'relevance' => 0.91,
            );
        }

        if ( strpos( $lower_message, 'rent' ) !== false || strpos( $lower_message, 'apartment' ) !== false ) {
            $sources[] = array(
                'title'     => 'Renting in France Guide',
                'category'  => 'property',
                'relevance' => 0.89,
            );
        }

        // If no specific sources matched, add a general one based on context
        if ( empty( $sources ) && ! empty( $context ) ) {
            $sources[] = array(
                'title'     => ucfirst( $context ) . ' Overview',
                'category'  => $context,
                'relevance' => 0.70,
            );
        }

        return $sources;
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
}
