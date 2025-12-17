<?php
/**
 * Project Model
 *
 * Handles relocation project/journey data and operations.
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
 * Class FRAMT_Project
 *
 * Model for managing relocation projects/journeys.
 *
 * @since 2.0.0
 */
class FRAMT_Project {

    /**
     * Project ID
     *
     * @var int
     */
    public $id = 0;

    /**
     * User ID (owner)
     *
     * @var int
     */
    public $user_id = 0;

    /**
     * Project title
     *
     * @var string
     */
    public $title = '';

    /**
     * Project description
     *
     * @var string
     */
    public $description = '';

    /**
     * Visa type
     *
     * @var string
     */
    public $visa_type = 'visitor';

    /**
     * Current stage slug
     *
     * @var string
     */
    public $current_stage = 'planning';

    /**
     * Target move date
     *
     * @var string|null
     */
    public $target_move_date = null;

    /**
     * Project status
     *
     * @var string
     */
    public $status = 'active';

    /**
     * Project settings (JSON)
     *
     * @var array
     */
    public $settings = array();

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
     * Valid visa types
     *
     * @var array
     */
    public static $visa_types = array(
        'visitor' => 'Visitor Visa (Long Stay)',
        'talent'  => 'Talent Passport',
        'student' => 'Student Visa',
        'family'  => 'Family Visa',
        'work'    => 'Work Visa',
        'other'   => 'Other',
    );

    /**
     * Valid statuses
     *
     * @var array
     */
    public static $statuses = array(
        'active'    => 'Active',
        'completed' => 'Completed',
        'paused'    => 'Paused',
        'archived'  => 'Archived',
    );

    /**
     * Constructor
     *
     * @param int|object|array $data Project ID, database row, or data array
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->load( $data );
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate( (array) $data );
        }
    }

    /**
     * Load project from database
     *
     * @param int $id Project ID
     * @return bool Success
     */
    public function load( $id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'projects' );
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
        if ( isset( $data['user_id'] ) ) {
            $this->user_id = (int) $data['user_id'];
        }
        if ( isset( $data['title'] ) ) {
            $this->title = sanitize_text_field( $data['title'] );
        }
        if ( isset( $data['description'] ) ) {
            $this->description = sanitize_textarea_field( $data['description'] );
        }
        if ( isset( $data['visa_type'] ) ) {
            $this->visa_type = sanitize_key( $data['visa_type'] );
        }
        if ( isset( $data['current_stage'] ) ) {
            $this->current_stage = sanitize_key( $data['current_stage'] );
        }
        if ( isset( $data['target_move_date'] ) ) {
            $this->target_move_date = $data['target_move_date'];
        }
        if ( isset( $data['status'] ) ) {
            $this->status = sanitize_key( $data['status'] );
        }
        if ( isset( $data['settings'] ) ) {
            $this->settings = is_string( $data['settings'] ) ? json_decode( $data['settings'], true ) : $data['settings'];
            if ( ! is_array( $this->settings ) ) {
                $this->settings = array();
            }
        }
        if ( isset( $data['created_at'] ) ) {
            $this->created_at = $data['created_at'];
        }
        if ( isset( $data['updated_at'] ) ) {
            $this->updated_at = $data['updated_at'];
        }
    }

    /**
     * Save project to database
     *
     * @return int|false Project ID on success, false on failure
     */
    public function save() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'projects' );
        $data  = array(
            'user_id'          => $this->user_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'visa_type'        => $this->visa_type,
            'current_stage'    => $this->current_stage,
            'target_move_date' => $this->target_move_date,
            'status'           => $this->status,
            'settings'         => wp_json_encode( $this->settings ),
        );
        $format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

        if ( $this->id > 0 ) {
            // Update existing
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
            if ( false !== $result ) {
                // Log activity
                FRAMT_Activity::log( $this->id, $this->user_id, 'project_updated', 'project', $this->id );
                return $this->id;
            }
        } else {
            // Insert new
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
                // Log activity
                FRAMT_Activity::log( $this->id, $this->user_id, 'project_created', 'project', $this->id );
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Delete project and related data
     *
     * @return bool Success
     */
    public function delete() {
        global $wpdb;

        if ( $this->id <= 0 ) {
            return false;
        }

        // Delete related tasks
        $tasks_table = FRAMT_Portal_Schema::get_table( 'tasks' );
        $wpdb->delete( $tasks_table, array( 'project_id' => $this->id ), array( '%d' ) );

        // Delete related files
        $files_table = FRAMT_Portal_Schema::get_table( 'files' );
        $wpdb->delete( $files_table, array( 'project_id' => $this->id ), array( '%d' ) );

        // Delete related notes
        $notes_table = FRAMT_Portal_Schema::get_table( 'notes' );
        $wpdb->delete( $notes_table, array( 'project_id' => $this->id ), array( '%d' ) );

        // Delete related applicants
        $applicants_table = FRAMT_Portal_Schema::get_table( 'applicants' );
        $wpdb->delete( $applicants_table, array( 'project_id' => $this->id ), array( '%d' ) );

        // Delete related activity
        $activity_table = FRAMT_Portal_Schema::get_table( 'activity' );
        $wpdb->delete( $activity_table, array( 'project_id' => $this->id ), array( '%d' ) );

        // Delete project
        $table  = FRAMT_Portal_Schema::get_table( 'projects' );
        $result = $wpdb->delete( $table, array( 'id' => $this->id ), array( '%d' ) );

        return false !== $result;
    }

    /**
     * Get project by user ID
     *
     * @param int $user_id User ID
     * @return FRAMT_Project|null
     */
    public static function get_by_user( $user_id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'projects' );
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND status = 'active' ORDER BY created_at DESC LIMIT 1",
                $user_id
            )
        );

        if ( $row ) {
            return new self( $row );
        }

        return null;
    }

    /**
     * Get or create project for user
     *
     * @param int    $user_id   User ID
     * @param string $visa_type Visa type if creating
     * @return FRAMT_Project
     */
    public static function get_or_create( $user_id, $visa_type = 'visitor' ) {
        $project = self::get_by_user( $user_id );

        if ( ! $project ) {
            $user    = get_userdata( $user_id );
            $project = new self();
            $project->user_id   = $user_id;
            $project->title     = $user ? sprintf( "%s's France Relocation", $user->display_name ) : 'My France Relocation';
            $project->visa_type = $visa_type;
            $project->save();
        }

        return $project;
    }

    /**
     * Get all projects for user
     *
     * @param int   $user_id User ID
     * @param array $args    Query arguments
     * @return array Array of FRAMT_Project objects
     */
    public static function get_all_by_user( $user_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'  => null,
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 50,
        );
        $args = wp_parse_args( $args, $defaults );

        $table = FRAMT_Portal_Schema::get_table( 'projects' );
        $where = array( 'user_id = %d' );
        $values = array( $user_id );

        if ( $args['status'] ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        $limit   = (int) $args['limit'];

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby LIMIT %d",
            array_merge( $values, array( $limit ) )
        );

        $rows = $wpdb->get_results( $sql );
        $projects = array();

        foreach ( $rows as $row ) {
            $projects[] = new self( $row );
        }

        return $projects;
    }

    /**
     * Get stages for this project's visa type
     *
     * @return array Array of stage objects
     */
    public function get_stages() {
        return FRAMT_Stage::get_by_visa_type( $this->visa_type );
    }

    /**
     * Get tasks for this project
     *
     * @param array $args Query arguments
     * @return array Array of FRAMT_Task objects
     */
    public function get_tasks( $args = array() ) {
        return FRAMT_Task::get_by_project( $this->id, $args );
    }

    /**
     * Get task statistics
     *
     * @return array Task stats
     */
    public function get_task_stats() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'tasks' );

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
                    SUM(CASE WHEN due_date < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) as overdue
                FROM $table WHERE project_id = %d",
                $this->id
            ),
            ARRAY_A
        );

        return array(
            'total'       => (int) $stats['total'],
            'completed'   => (int) $stats['completed'],
            'in_progress' => (int) $stats['in_progress'],
            'todo'        => (int) $stats['todo'],
            'overdue'     => (int) $stats['overdue'],
            'percentage'  => $stats['total'] > 0 ? round( ( $stats['completed'] / $stats['total'] ) * 100 ) : 0,
        );
    }

    /**
     * Get stage progress for each stage
     *
     * @return array Stage progress data
     */
    public function get_stage_progress() {
        global $wpdb;

        $stages = $this->get_stages();
        $tasks_table = FRAMT_Portal_Schema::get_table( 'tasks' );

        $progress = array();

        foreach ( $stages as $stage ) {
            $stats = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed
                    FROM $tasks_table
                    WHERE project_id = %d AND stage = %s",
                    $this->id,
                    $stage->slug
                ),
                ARRAY_A
            );

            $is_current   = $this->current_stage === $stage->slug;
            $is_completed = false;

            // Determine if stage is completed
            if ( $stats['total'] > 0 && $stats['completed'] === $stats['total'] ) {
                $is_completed = true;
            }

            $progress[] = array(
                'slug'        => $stage->slug,
                'title'       => $stage->title,
                'description' => $stage->description,
                'color'       => $stage->color,
                'icon'        => $stage->icon,
                'sort_order'  => $stage->sort_order,
                'total'       => (int) $stats['total'],
                'completed'   => (int) $stats['completed'],
                'percentage'  => $stats['total'] > 0 ? round( ( $stats['completed'] / $stats['total'] ) * 100 ) : 0,
                'is_current'  => $is_current,
                'is_completed' => $is_completed,
                'status'      => $is_completed ? 'completed' : ( $is_current ? 'current' : 'upcoming' ),
            );
        }

        return $progress;
    }

    /**
     * Calculate days until target move date
     *
     * @return int|null Days until move, or null if no date set
     */
    public function get_days_until_move() {
        if ( empty( $this->target_move_date ) ) {
            return null;
        }

        $target = new DateTime( $this->target_move_date );
        $today  = new DateTime( 'today' );
        $diff   = $today->diff( $target );

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Convert to array for API response
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'visa_type'        => $this->visa_type,
            'visa_type_label'  => self::$visa_types[ $this->visa_type ] ?? $this->visa_type,
            'current_stage'    => $this->current_stage,
            'target_move_date' => $this->target_move_date,
            'days_until_move'  => $this->get_days_until_move(),
            'status'           => $this->status,
            'status_label'     => self::$statuses[ $this->status ] ?? $this->status,
            'settings'         => $this->settings,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        );
    }
}
