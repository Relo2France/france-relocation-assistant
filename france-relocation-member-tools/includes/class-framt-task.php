<?php
/**
 * Task Model
 *
 * Handles task data and operations for the portal.
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
 * Class FRAMT_Task
 *
 * Model for managing tasks within relocation projects.
 *
 * @since 2.0.0
 */
class FRAMT_Task {

    /**
     * Task ID
     *
     * @var int
     */
    public $id = 0;

    /**
     * Project ID
     *
     * @var int
     */
    public $project_id = 0;

    /**
     * User ID
     *
     * @var int
     */
    public $user_id = 0;

    /**
     * Task title
     *
     * @var string
     */
    public $title = '';

    /**
     * Task description
     *
     * @var string
     */
    public $description = '';

    /**
     * Stage slug
     *
     * @var string
     */
    public $stage = '';

    /**
     * Task status
     *
     * @var string
     */
    public $status = 'todo';

    /**
     * Task priority
     *
     * @var string
     */
    public $priority = 'medium';

    /**
     * Task type (client, team, system)
     *
     * @var string
     */
    public $task_type = 'client';

    /**
     * Due date
     *
     * @var string|null
     */
    public $due_date = null;

    /**
     * Assignee user ID
     *
     * @var int|null
     */
    public $assignee_id = null;

    /**
     * Portal visibility
     *
     * @var bool
     */
    public $portal_visible = true;

    /**
     * Sort order
     *
     * @var int
     */
    public $sort_order = 0;

    /**
     * Parent task ID for subtasks
     *
     * @var int|null
     */
    public $parent_task_id = null;

    /**
     * Task metadata
     *
     * @var array
     */
    public $metadata = array();

    /**
     * Completed timestamp
     *
     * @var string|null
     */
    public $completed_at = null;

    /**
     * Created timestamp
     *
     * @var string
     */
    public $created_at = '';

    /**
     * Updated timestamp
     *
     * @var string
     */
    public $updated_at = '';

    /**
     * Valid statuses
     *
     * @var array
     */
    public static $statuses = array(
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'waiting'     => 'Waiting',
        'done'        => 'Done',
    );

    /**
     * Valid priorities
     *
     * @var array
     */
    public static $priorities = array(
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    );

    /**
     * Valid task types
     *
     * @var array
     */
    public static $task_types = array(
        'client' => 'Client',
        'team'   => 'Team',
        'system' => 'System',
    );

    /**
     * Constructor
     *
     * @param int|object|array $data Task ID, database row, or data array
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->load( $data );
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate( (array) $data );
        }
    }

    /**
     * Load task from database
     *
     * @param int $id Task ID
     * @return bool Success
     */
    public function load( $id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

        if ( $row ) {
            $this->populate( (array) $row );
            return true;
        }

        return false;
    }

    /**
     * Populate object from array
     *
     * @param array $data Data array
     * @return void
     */
    protected function populate( $data ) {
        if ( isset( $data['id'] ) ) {
            $this->id = (int) $data['id'];
        }
        if ( isset( $data['project_id'] ) ) {
            $this->project_id = (int) $data['project_id'];
        }
        if ( isset( $data['user_id'] ) ) {
            $this->user_id = (int) $data['user_id'];
        }
        if ( isset( $data['title'] ) ) {
            $this->title = sanitize_text_field( $data['title'] );
        }
        if ( isset( $data['description'] ) ) {
            $this->description = wp_kses_post( $data['description'] );
        }
        if ( isset( $data['stage'] ) ) {
            $this->stage = sanitize_key( $data['stage'] );
        }
        if ( isset( $data['status'] ) ) {
            $this->status = sanitize_key( $data['status'] );
        }
        if ( isset( $data['priority'] ) ) {
            $this->priority = sanitize_key( $data['priority'] );
        }
        if ( isset( $data['task_type'] ) ) {
            $this->task_type = sanitize_key( $data['task_type'] );
        }
        if ( isset( $data['due_date'] ) ) {
            $this->due_date = $data['due_date'];
        }
        if ( isset( $data['assignee_id'] ) ) {
            $this->assignee_id = $data['assignee_id'] ? (int) $data['assignee_id'] : null;
        }
        if ( isset( $data['portal_visible'] ) ) {
            $this->portal_visible = (bool) $data['portal_visible'];
        }
        if ( isset( $data['sort_order'] ) ) {
            $this->sort_order = (int) $data['sort_order'];
        }
        if ( isset( $data['parent_task_id'] ) ) {
            $this->parent_task_id = $data['parent_task_id'] ? (int) $data['parent_task_id'] : null;
        }
        if ( isset( $data['metadata'] ) ) {
            $this->metadata = is_string( $data['metadata'] ) ? json_decode( $data['metadata'], true ) : $data['metadata'];
            if ( ! is_array( $this->metadata ) ) {
                $this->metadata = array();
            }
        }
        if ( isset( $data['completed_at'] ) ) {
            $this->completed_at = $data['completed_at'];
        }
        if ( isset( $data['created_at'] ) ) {
            $this->created_at = $data['created_at'];
        }
        if ( isset( $data['updated_at'] ) ) {
            $this->updated_at = $data['updated_at'];
        }
    }

    /**
     * Save task to database
     *
     * @return int|false Task ID on success, false on failure
     */
    public function save() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $data  = array(
            'project_id'     => $this->project_id,
            'user_id'        => $this->user_id,
            'title'          => $this->title,
            'description'    => $this->description,
            'stage'          => $this->stage,
            'status'         => $this->status,
            'priority'       => $this->priority,
            'task_type'      => $this->task_type,
            'due_date'       => $this->due_date,
            'assignee_id'    => $this->assignee_id,
            'portal_visible' => $this->portal_visible ? 1 : 0,
            'sort_order'     => $this->sort_order,
            'parent_task_id' => $this->parent_task_id,
            'metadata'       => wp_json_encode( $this->metadata ),
            'completed_at'   => $this->completed_at,
        );
        $format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' );

        if ( $this->id > 0 ) {
            // Update existing
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
            if ( false !== $result ) {
                FRAMT_Activity::log( $this->project_id, $this->user_id, 'task_updated', 'task', $this->id, $this->title );
                return $this->id;
            }
        } else {
            // Insert new
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
                FRAMT_Activity::log( $this->project_id, $this->user_id, 'task_created', 'task', $this->id, $this->title );
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Delete task
     *
     * @return bool Success
     */
    public function delete() {
        global $wpdb;

        if ( $this->id <= 0 ) {
            return false;
        }

        // Delete subtasks
        $table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $wpdb->delete( $table, array( 'parent_task_id' => $this->id ), array( '%d' ) );

        // Delete related notes
        $notes_table = FRAMT_Portal_Schema::get_table( 'notes' );
        $wpdb->delete( $notes_table, array( 'task_id' => $this->id ), array( '%d' ) );

        // Delete related files
        $files_table = FRAMT_Portal_Schema::get_table( 'files' );
        $wpdb->delete( $files_table, array( 'task_id' => $this->id ), array( '%d' ) );

        // Log activity
        FRAMT_Activity::log( $this->project_id, $this->user_id, 'task_deleted', 'task', $this->id, $this->title );

        // Delete task
        $result = $wpdb->delete( $table, array( 'id' => $this->id ), array( '%d' ) );

        return false !== $result;
    }

    /**
     * Update task status
     *
     * @param string $new_status New status
     * @return bool Success
     */
    public function update_status( $new_status ) {
        if ( ! array_key_exists( $new_status, self::$statuses ) ) {
            return false;
        }

        $old_status   = $this->status;
        $this->status = $new_status;

        // Set completed_at timestamp
        if ( 'done' === $new_status && 'done' !== $old_status ) {
            $this->completed_at = current_time( 'mysql' );
        } elseif ( 'done' !== $new_status && $this->completed_at ) {
            $this->completed_at = null;
        }

        $result = $this->save();

        if ( $result ) {
            FRAMT_Activity::log(
                $this->project_id,
                $this->user_id,
                'task_status_changed',
                'task',
                $this->id,
                sprintf( '%s: %s → %s', $this->title, self::$statuses[ $old_status ], self::$statuses[ $new_status ] )
            );
        }

        return (bool) $result;
    }

    /**
     * Get tasks by project ID
     *
     * @param int   $project_id Project ID
     * @param array $args       Query arguments
     * @return array Array of FRAMT_Task objects
     */
    public static function get_by_project( $project_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'stage'          => null,
            'status'         => null,
            'task_type'      => null,
            'portal_visible' => null,
            'parent_only'    => false,
            'orderby'        => 'sort_order',
            'order'          => 'ASC',
            'limit'          => 500,
        );
        $args = wp_parse_args( $args, $defaults );

        $table  = FRAMT_Portal_Schema::get_table( 'tasks' );
        $where  = array( 'project_id = %d' );
        $values = array( $project_id );

        if ( $args['stage'] ) {
            $where[]  = 'stage = %s';
            $values[] = $args['stage'];
        }

        if ( $args['status'] ) {
            if ( is_array( $args['status'] ) ) {
                $placeholders = implode( ', ', array_fill( 0, count( $args['status'] ), '%s' ) );
                $where[]      = "status IN ($placeholders)";
                $values       = array_merge( $values, $args['status'] );
            } else {
                $where[]  = 'status = %s';
                $values[] = $args['status'];
            }
        }

        if ( $args['task_type'] ) {
            $where[]  = 'task_type = %s';
            $values[] = $args['task_type'];
        }

        if ( null !== $args['portal_visible'] ) {
            $where[]  = 'portal_visible = %d';
            $values[] = $args['portal_visible'] ? 1 : 0;
        }

        if ( $args['parent_only'] ) {
            $where[] = 'parent_task_id IS NULL';
        }

        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        $limit   = (int) $args['limit'];

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby LIMIT %d",
            array_merge( $values, array( $limit ) )
        );

        $rows  = $wpdb->get_results( $sql );
        $tasks = array();

        foreach ( $rows as $row ) {
            $tasks[] = new self( $row );
        }

        return $tasks;
    }

    /**
     * Get subtasks of this task
     *
     * @return array Array of FRAMT_Task objects
     */
    public function get_subtasks() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE parent_task_id = %d ORDER BY sort_order ASC",
                $this->id
            )
        );

        $tasks = array();
        foreach ( $rows as $row ) {
            $tasks[] = new self( $row );
        }

        return $tasks;
    }

    /**
     * Get upcoming tasks (due soon)
     *
     * @param int $project_id Project ID
     * @param int $days       Days ahead to look
     * @param int $limit      Maximum tasks to return
     * @return array Array of FRAMT_Task objects
     */
    public static function get_upcoming( $project_id, $days = 14, $limit = 10 ) {
        global $wpdb;

        $table     = FRAMT_Portal_Schema::get_table( 'tasks' );
        $date_end  = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE project_id = %d
                AND status != 'done'
                AND due_date IS NOT NULL
                AND due_date <= %s
                ORDER BY due_date ASC, priority DESC
                LIMIT %d",
                $project_id,
                $date_end,
                $limit
            )
        );

        $tasks = array();
        foreach ( $rows as $row ) {
            $tasks[] = new self( $row );
        }

        return $tasks;
    }

    /**
     * Get overdue tasks
     *
     * @param int $project_id Project ID
     * @return array Array of FRAMT_Task objects
     */
    public static function get_overdue( $project_id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $today = gmdate( 'Y-m-d' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE project_id = %d
                AND status != 'done'
                AND due_date IS NOT NULL
                AND due_date < %s
                ORDER BY due_date ASC",
                $project_id,
                $today
            )
        );

        $tasks = array();
        foreach ( $rows as $row ) {
            $tasks[] = new self( $row );
        }

        return $tasks;
    }

    /**
     * Check if task is overdue
     *
     * @return bool
     */
    public function is_overdue() {
        if ( empty( $this->due_date ) || 'done' === $this->status ) {
            return false;
        }

        return strtotime( $this->due_date ) < strtotime( 'today' );
    }

    /**
     * Get days until due (negative if overdue)
     *
     * @return int|null Days until due, or null if no due date
     */
    public function get_days_until_due() {
        if ( empty( $this->due_date ) ) {
            return null;
        }

        $due   = new DateTime( $this->due_date );
        $today = new DateTime( 'today' );
        $diff  = $today->diff( $due );

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Bulk update sort order
     *
     * @param array $task_order Array of task_id => sort_order
     * @return bool Success
     */
    public static function bulk_update_order( $task_order ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );

        foreach ( $task_order as $task_id => $order ) {
            $wpdb->update(
                $table,
                array( 'sort_order' => (int) $order ),
                array( 'id' => (int) $task_id ),
                array( '%d' ),
                array( '%d' )
            );
        }

        return true;
    }

    /**
     * Convert to array for API response
     *
     * @return array
     */
    public function to_array() {
        $assignee_name = null;
        if ( $this->assignee_id ) {
            $assignee = get_userdata( $this->assignee_id );
            if ( $assignee ) {
                $assignee_name = $assignee->display_name;
            }
        }

        return array(
            'id'              => $this->id,
            'project_id'      => $this->project_id,
            'user_id'         => $this->user_id,
            'title'           => $this->title,
            'description'     => $this->description,
            'stage'           => $this->stage,
            'status'          => $this->status,
            'status_label'    => self::$statuses[ $this->status ] ?? $this->status,
            'priority'        => $this->priority,
            'priority_label'  => self::$priorities[ $this->priority ] ?? $this->priority,
            'task_type'       => $this->task_type,
            'task_type_label' => self::$task_types[ $this->task_type ] ?? $this->task_type,
            'due_date'        => $this->due_date,
            'days_until_due'  => $this->get_days_until_due(),
            'is_overdue'      => $this->is_overdue(),
            'assignee_id'     => $this->assignee_id,
            'assignee_name'   => $assignee_name,
            'portal_visible'  => $this->portal_visible,
            'sort_order'      => $this->sort_order,
            'parent_task_id'  => $this->parent_task_id,
            'metadata'        => $this->metadata,
            'completed_at'    => $this->completed_at,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        );
    }
}
