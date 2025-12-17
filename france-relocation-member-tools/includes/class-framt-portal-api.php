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

        if ( $note->user_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
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
}
