<?php
/**
 * Stage Model
 *
 * Handles pipeline stage data and operations.
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
 * Class FRAMT_Stage
 *
 * Model for managing pipeline stages per visa type.
 *
 * @since 2.0.0
 */
class FRAMT_Stage {

    /**
     * Stage ID
     *
     * @var int
     */
    public $id = 0;

    /**
     * Visa type
     *
     * @var string
     */
    public $visa_type = '';

    /**
     * Stage slug
     *
     * @var string
     */
    public $slug = '';

    /**
     * Stage title
     *
     * @var string
     */
    public $title = '';

    /**
     * Stage description
     *
     * @var string
     */
    public $description = '';

    /**
     * Sort order
     *
     * @var int
     */
    public $sort_order = 0;

    /**
     * Stage color (hex)
     *
     * @var string
     */
    public $color = '#6366f1';

    /**
     * Icon name
     *
     * @var string
     */
    public $icon = '';

    /**
     * Created timestamp
     *
     * @var string
     */
    public $created_at = '';

    /**
     * Constructor
     *
     * @param int|object|array $data Stage ID, database row, or data array
     */
    public function __construct( $data = null ) {
        if ( is_numeric( $data ) ) {
            $this->load( $data );
        } elseif ( is_object( $data ) || is_array( $data ) ) {
            $this->populate( (array) $data );
        }
    }

    /**
     * Load stage from database
     *
     * @param int $id Stage ID
     * @return bool Success
     */
    public function load( $id ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
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
        if ( isset( $data['visa_type'] ) ) {
            $this->visa_type = sanitize_key( $data['visa_type'] );
        }
        if ( isset( $data['slug'] ) ) {
            $this->slug = sanitize_key( $data['slug'] );
        }
        if ( isset( $data['title'] ) ) {
            $this->title = sanitize_text_field( $data['title'] );
        }
        if ( isset( $data['description'] ) ) {
            $this->description = sanitize_text_field( $data['description'] );
        }
        if ( isset( $data['sort_order'] ) ) {
            $this->sort_order = (int) $data['sort_order'];
        }
        if ( isset( $data['color'] ) ) {
            $this->color = sanitize_hex_color( $data['color'] ) ?: '#6366f1';
        }
        if ( isset( $data['icon'] ) ) {
            $this->icon = sanitize_text_field( $data['icon'] );
        }
        if ( isset( $data['created_at'] ) ) {
            $this->created_at = $data['created_at'];
        }
    }

    /**
     * Save stage to database
     *
     * @return int|false Stage ID on success, false on failure
     */
    public function save() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        $data  = array(
            'visa_type'   => $this->visa_type,
            'slug'        => $this->slug,
            'title'       => $this->title,
            'description' => $this->description,
            'sort_order'  => $this->sort_order,
            'color'       => $this->color,
            'icon'        => $this->icon,
        );
        $format = array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' );

        if ( $this->id > 0 ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $this->id ), $format, array( '%d' ) );
            return false !== $result ? $this->id : false;
        } else {
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result ) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Delete stage
     *
     * @return bool Success
     */
    public function delete() {
        global $wpdb;

        if ( $this->id <= 0 ) {
            return false;
        }

        $table  = FRAMT_Portal_Schema::get_table( 'stages' );
        $result = $wpdb->delete( $table, array( 'id' => $this->id ), array( '%d' ) );

        return false !== $result;
    }

    /**
     * Get stages by visa type
     *
     * @param string $visa_type Visa type
     * @return array Array of FRAMT_Stage objects
     */
    public static function get_by_visa_type( $visa_type ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE visa_type = %s ORDER BY sort_order ASC",
                $visa_type
            )
        );

        $stages = array();
        foreach ( $rows as $row ) {
            $stages[] = new self( $row );
        }

        return $stages;
    }

    /**
     * Get stage by visa type and slug
     *
     * @param string $visa_type Visa type
     * @param string $slug      Stage slug
     * @return FRAMT_Stage|null
     */
    public static function get_by_slug( $visa_type, $slug ) {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE visa_type = %s AND slug = %s",
                $visa_type,
                $slug
            )
        );

        if ( $row ) {
            return new self( $row );
        }

        return null;
    }

    /**
     * Get all unique visa types that have stages defined
     *
     * @return array Array of visa types
     */
    public static function get_visa_types() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        return $wpdb->get_col( "SELECT DISTINCT visa_type FROM $table ORDER BY visa_type" );
    }

    /**
     * Get next stage in the pipeline
     *
     * @return FRAMT_Stage|null
     */
    public function get_next() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE visa_type = %s AND sort_order > %d ORDER BY sort_order ASC LIMIT 1",
                $this->visa_type,
                $this->sort_order
            )
        );

        if ( $row ) {
            return new self( $row );
        }

        return null;
    }

    /**
     * Get previous stage in the pipeline
     *
     * @return FRAMT_Stage|null
     */
    public function get_previous() {
        global $wpdb;

        $table = FRAMT_Portal_Schema::get_table( 'stages' );
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE visa_type = %s AND sort_order < %d ORDER BY sort_order DESC LIMIT 1",
                $this->visa_type,
                $this->sort_order
            )
        );

        if ( $row ) {
            return new self( $row );
        }

        return null;
    }

    /**
     * Convert to array for API response
     *
     * @return array
     */
    public function to_array() {
        return array(
            'id'          => $this->id,
            'visa_type'   => $this->visa_type,
            'slug'        => $this->slug,
            'title'       => $this->title,
            'description' => $this->description,
            'sort_order'  => $this->sort_order,
            'color'       => $this->color,
            'icon'        => $this->icon,
            'created_at'  => $this->created_at,
        );
    }
}
