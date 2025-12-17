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
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_current_user' ),
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
}
