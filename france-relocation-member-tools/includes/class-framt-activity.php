<?php
/**
 * Activity Model
 *
 * Handles activity logging and timeline for the portal.
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
 * Class FRAMT_Activity
 *
 * Model for activity logging and timeline display.
 *
 * @since 2.0.0
 */
class FRAMT_Activity {

    /**
     * Activity ID
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
     * User ID (who performed the action)
     *
     * @var int
     */
    public $user_id = 0;

    /**
     * Action type
     *
     * @var string
     */
    public $action = '';

    /**
     * Entity type (task, document, note, etc.)
     *
     * @var string
     */
    public $entity_type = '';

    /**
     * Entity ID
     *
     * @var int
     */
    public $entity_id = 0;

    /**
     * Human-readable description
     *
     * @var string
     */
    public $description = '';

    /**
     * Additional metadata
     *
     * @var array
     */
    public $metadata = array();

    /**
     * Created timestamp
     *
     * @var string
     */
    public $created_at = '';

    /**
     * Action labels for display
     *
     * @var array
     */
    public static $action_labels = array(
        'project_created'      => 'Created project',
        'project_updated'      => 'Updated project',
        'task_created'         => 'Added task',
        'task_updated'         => 'Updated task',
        'task_deleted'         => 'Deleted task',
        'task_status_changed'  => 'Changed task status',
        'task_completed'       => 'Completed task',
        'file_uploaded'        => 'Uploaded file',
        'file_deleted'         => 'Deleted file',
        'document_generated'   => 'Generated document',
        'note_added'           => 'Added note',
        'note_updated'         => 'Updated note',
        'stage_changed'        => 'Changed stage',
        'applicant_added'      => 'Added family member',
        'applicant_updated'    => 'Updated family member',
        'message_sent'         => 'Sent message',
    );

    /**
     * Action icons for display
     *
     * @var array
     */
    public static $action_icons = array(
        'project_created'      => 'folder-plus',
        'project_updated'      => 'pencil',
        'task_created'         => 'plus-circle',
        'task_updated'         => 'pencil',
        'task_deleted'         => 'trash',
        'task_status_changed'  => 'arrows-right-left',
        'task_completed'       => 'check-circle',
        'file_uploaded'        => 'arrow-up-tray',
        'file_deleted'         => 'trash',
        'document_generated'   => 'document-text',
        'note_added'           => 'chat-bubble-bottom-center-text',
        'note_updated'         => 'pencil',
        'stage_changed'        => 'arrow-right',
        'applicant_added'      => 'user-plus',
        'applicant_updated'    => 'user',
        'message_sent'         => 'envelope',
    );

    /**
     * Constructor
     *
     * @param int|object|array $data Activity ID, database row, or data array
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->load_by_id( $data );
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate( (array) $data );
        }
    }

    /**
     * Load activity from database
     *
     * @param int $id Activity ID
     * @return bool Success
     */
    public function load_by_id( $id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'activity' );
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
        if ( isset( $data['action'] ) ) {
            $this->action = sanitize_key( $data['action'] );
        }
        if ( isset( $data['entity_type'] ) ) {
            $this->entity_type = sanitize_key( $data['entity_type'] );
        }
        if ( isset( $data['entity_id'] ) ) {
            $this->entity_id = (int) $data['entity_id'];
        }
        if ( isset( $data['description'] ) ) {
            $this->description = sanitize_text_field( $data['description'] );
        }
        if ( isset( $data['metadata'] ) ) {
            $this->metadata = is_string( $data['metadata'] ) ? json_decode( $data['metadata'], true ) : $data['metadata'];
            if ( ! is_array( $this->metadata ) ) {
                $this->metadata = array();
            }
        }
        if ( isset( $data['created_at'] ) ) {
            $this->created_at = $data['created_at'];
        }
    }

    /**
     * Save activity to database
     *
     * @return int|false Activity ID on success, false on failure
     */
    public function save() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'activity' );
        $data  = array(
            'project_id'  => $this->project_id,
            'user_id'     => $this->user_id,
            'action'      => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id,
            'description' => $this->description,
            'metadata'    => wp_json_encode( $this->metadata ),
        );
        $format = array( '%d', '%d', '%s', '%s', '%d', '%s', '%s' );

        $result = $wpdb->insert( $table, $data, $format );
        if ( $result ) {
            $this->id = $wpdb->insert_id;
            return $this->id;
        }

        return false;
    }

    /**
     * Log an activity (static convenience method)
     *
     * @param int    $project_id  Project ID
     * @param int    $user_id     User ID
     * @param string $action      Action type
     * @param string $entity_type Entity type (optional)
     * @param int    $entity_id   Entity ID (optional)
     * @param string $description Description (optional)
     * @param array  $metadata    Additional data (optional)
     * @return int|false Activity ID on success
     */
    public static function log( $project_id, $user_id, $action, $entity_type = null, $entity_id = null, $description = null, $metadata = array() ) {
        $activity              = new self();
        $activity->project_id  = $project_id;
        $activity->user_id     = $user_id;
        $activity->action      = $action;
        $activity->entity_type = $entity_type;
        $activity->entity_id   = $entity_id;
        $activity->description = $description ?: ( self::$action_labels[ $action ] ?? $action );
        $activity->metadata    = $metadata;

        return $activity->save();
    }

    /**
     * Get activities for a project
     *
     * @param int   $project_id Project ID
     * @param array $args       Query arguments
     * @return array Array of FRAMT_Activity objects
     */
    public static function get_by_project( $project_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'action'      => null,
            'entity_type' => null,
            'user_id'     => null,
            'after'       => null,
            'before'      => null,
            'orderby'     => 'created_at',
            'order'       => 'DESC',
            'limit'       => 50,
            'offset'      => 0,
        );
        $args = wp_parse_args( $args, $defaults );

        $table  = FRAMT_Portal_Schema::get_table( 'activity' );
        $where  = array( 'project_id = %d' );
        $values = array( $project_id );

        if ( $args['action'] ) {
            $where[]  = 'action = %s';
            $values[] = $args['action'];
        }

        if ( $args['entity_type'] ) {
            $where[]  = 'entity_type = %s';
            $values[] = $args['entity_type'];
        }

        if ( $args['user_id'] ) {
            $where[]  = 'user_id = %d';
            $values[] = $args['user_id'];
        }

        if ( $args['after'] ) {
            $where[]  = 'created_at >= %s';
            $values[] = $args['after'];
        }

        if ( $args['before'] ) {
            $where[]  = 'created_at <= %s';
            $values[] = $args['before'];
        }

        $orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
        $limit   = (int) $args['limit'];
        $offset  = (int) $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby LIMIT %d OFFSET %d",
            array_merge( $values, array( $limit, $offset ) )
        );

        $rows       = $wpdb->get_results( $sql );
        $activities = array();

        foreach ( $rows as $row ) {
            $activities[] = new self( $row );
        }

        return $activities;
    }

    /**
     * Get recent activity (across all user's projects)
     *
     * @param int $user_id User ID
     * @param int $limit   Maximum activities to return
     * @return array Array of FRAMT_Activity objects
     */
    public static function get_recent_for_user( $user_id, $limit = 20 ) {
        global $wpdb;

        $table    = FRAMT_Portal_Schema::get_table( 'activity' );
        $projects = FRAMT_Portal_Schema::get_table( 'projects' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.* FROM $table a
                INNER JOIN $projects p ON a.project_id = p.id
                WHERE p.user_id = %d
                ORDER BY a.created_at DESC
                LIMIT %d",
                $user_id,
                $limit
            )
        );

        $activities = array();
        foreach ( $rows as $row ) {
            $activities[] = new self( $row );
        }

        return $activities;
    }

    /**
     * Group activities by date for timeline display
     *
     * @param array $activities Array of FRAMT_Activity objects
     * @return array Grouped activities
     */
    public static function group_by_date( $activities ) {
        $grouped = array();

        foreach ( $activities as $activity ) {
            $date = gmdate( 'Y-m-d', strtotime( $activity->created_at ) );

            if ( ! isset( $grouped[ $date ] ) ) {
                $grouped[ $date ] = array(
                    'date'       => $date,
                    'label'      => self::format_date_label( $date ),
                    'activities' => array(),
                );
            }

            $grouped[ $date ]['activities'][] = $activity->to_array();
        }

        return array_values( $grouped );
    }

    /**
     * Format date label for timeline
     *
     * @param string $date Date string (Y-m-d)
     * @return string Formatted label
     */
    private static function format_date_label( $date ) {
        $timestamp = strtotime( $date );
        $today     = strtotime( 'today' );
        $yesterday = strtotime( 'yesterday' );

        if ( $timestamp === $today ) {
            return 'Today';
        } elseif ( $timestamp === $yesterday ) {
            return 'Yesterday';
        } elseif ( $timestamp > strtotime( '-7 days' ) ) {
            return gmdate( 'l', $timestamp ); // Day name
        } else {
            return gmdate( 'F j, Y', $timestamp ); // Full date
        }
    }

    /**
     * Get relative time string
     *
     * @return string Relative time
     */
    public function get_relative_time() {
        $timestamp = strtotime( $this->created_at );
        $diff      = time() - $timestamp;

        if ( $diff < 60 ) {
            return 'Just now';
        } elseif ( $diff < 3600 ) {
            $mins = round( $diff / 60 );
            return sprintf( '%d %s ago', $mins, $mins === 1 ? 'minute' : 'minutes' );
        } elseif ( $diff < 86400 ) {
            $hours = round( $diff / 3600 );
            return sprintf( '%d %s ago', $hours, $hours === 1 ? 'hour' : 'hours' );
        } elseif ( $diff < 604800 ) {
            $days = round( $diff / 86400 );
            return sprintf( '%d %s ago', $days, $days === 1 ? 'day' : 'days' );
        } else {
            return gmdate( 'M j, Y', $timestamp );
        }
    }

    /**
     * Convert to array for API response
     *
     * @return array
     */
    public function to_array() {
        $user = get_userdata( $this->user_id );

        return array(
            'id'            => $this->id,
            'project_id'    => $this->project_id,
            'user_id'       => $this->user_id,
            'user_name'     => $user ? $user->display_name : 'Unknown',
            'user_avatar'   => $user ? get_avatar_url( $this->user_id, array( 'size' => 40 ) ) : '',
            'action'        => $this->action,
            'action_label'  => self::$action_labels[ $this->action ] ?? $this->action,
            'action_icon'   => self::$action_icons[ $this->action ] ?? 'information-circle',
            'entity_type'   => $this->entity_type,
            'entity_id'     => $this->entity_id,
            'description'   => $this->description,
            'metadata'      => $this->metadata,
            'created_at'    => $this->created_at,
            'relative_time' => $this->get_relative_time(),
        );
    }
}
