<?php
/**
 * Portal Database Schema
 *
 * Creates and manages database tables for the LaunchBay-style members portal.
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
 * Class FRAMT_Portal_Schema
 *
 * Handles database table creation and migrations for the portal system.
 *
 * @since 2.0.0
 */
class FRAMT_Portal_Schema {

    /**
     * Database version for schema tracking
     *
     * @var string
     */
    const DB_VERSION = '2.1.0';

    /**
     * Option name for tracking database version
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'framt_portal_db_version';

    /**
     * Table names
     *
     * @var array
     */
    private static $tables = array(
        'projects'       => 'fra_projects',
        'tasks'          => 'fra_tasks',
        'task_templates' => 'fra_task_templates',
        'stages'         => 'fra_stages',
        'files'          => 'fra_files',
        'notes'          => 'fra_notes',
        'applicants'     => 'fra_applicants',
        'activity'       => 'fra_activity',
        'schengen_trips' => 'fra_schengen_trips',
    );

    /**
     * Get table name with prefix
     *
     * @param string $table Table key
     * @return string Full table name with prefix
     */
    public static function get_table( $table ) {
        global $wpdb;
        if ( isset( self::$tables[ $table ] ) ) {
            return $wpdb->prefix . self::$tables[ $table ];
        }
        return '';
    }

    /**
     * Create all portal tables
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Projects table - one relocation journey per user
        $table_projects = self::get_table( 'projects' );
        $sql_projects = "CREATE TABLE $table_projects (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            visa_type varchar(50) NOT NULL DEFAULT 'visitor',
            current_stage varchar(50) DEFAULT 'planning',
            target_move_date date DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY visa_type (visa_type)
        ) $charset_collate;";
        dbDelta( $sql_projects );

        // Tasks table - comprehensive task management
        $table_tasks = self::get_table( 'tasks' );
        $sql_tasks = "CREATE TABLE $table_tasks (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            stage varchar(50) DEFAULT NULL,
            status varchar(20) DEFAULT 'todo',
            priority varchar(20) DEFAULT 'medium',
            task_type varchar(20) DEFAULT 'client',
            due_date date DEFAULT NULL,
            assignee_id bigint(20) unsigned DEFAULT NULL,
            portal_visible tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            parent_task_id bigint(20) unsigned DEFAULT NULL,
            metadata longtext,
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY stage (stage),
            KEY due_date (due_date),
            KEY parent_task_id (parent_task_id)
        ) $charset_collate;";
        dbDelta( $sql_tasks );

        // Task templates for reusable task sets per visa type
        $table_templates = self::get_table( 'task_templates' );
        $sql_templates = "CREATE TABLE $table_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            visa_type varchar(50) DEFAULT NULL,
            description text,
            tasks longtext NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY visa_type (visa_type),
            KEY is_default (is_default)
        ) $charset_collate;";
        dbDelta( $sql_templates );

        // Stages/Pipeline definitions per visa type
        $table_stages = self::get_table( 'stages' );
        $sql_stages = "CREATE TABLE $table_stages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            visa_type varchar(50) NOT NULL,
            slug varchar(50) NOT NULL,
            title varchar(100) NOT NULL,
            description text,
            sort_order int(11) DEFAULT 0,
            color varchar(7) DEFAULT '#6366f1',
            icon varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY visa_stage (visa_type, slug),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        dbDelta( $sql_stages );

        // Files/Documents table - enhanced file management
        $table_files = self::get_table( 'files' );
        $sql_files = "CREATE TABLE $table_files (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned NOT NULL,
            task_id bigint(20) unsigned DEFAULT NULL,
            filename varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_type varchar(50) DEFAULT NULL,
            file_size bigint(20) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            file_path varchar(500) DEFAULT NULL,
            category varchar(20) DEFAULT 'upload',
            visibility varchar(20) DEFAULT 'private',
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY task_id (task_id),
            KEY category (category)
        ) $charset_collate;";
        dbDelta( $sql_files );

        // Notes table for project and task notes
        $table_notes = self::get_table( 'notes' );
        $sql_notes = "CREATE TABLE $table_notes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            task_id bigint(20) unsigned DEFAULT NULL,
            content longtext NOT NULL,
            is_pinned tinyint(1) DEFAULT 0,
            visibility varchar(20) DEFAULT 'private',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY task_id (task_id)
        ) $charset_collate;";
        dbDelta( $sql_notes );

        // Applicants - family members on the relocation
        $table_applicants = self::get_table( 'applicants' );
        $sql_applicants = "CREATE TABLE $table_applicants (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            relationship varchar(20) DEFAULT 'primary',
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            date_of_birth date DEFAULT NULL,
            nationality varchar(100) DEFAULT NULL,
            passport_number varchar(50) DEFAULT NULL,
            passport_expiry date DEFAULT NULL,
            profile_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta( $sql_applicants );

        // Activity log for timeline
        $table_activity = self::get_table( 'activity' );
        $sql_activity = "CREATE TABLE $table_activity (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            project_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            action varchar(100) NOT NULL,
            entity_type varchar(50) DEFAULT NULL,
            entity_id bigint(20) unsigned DEFAULT NULL,
            description text,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY action (action)
        ) $charset_collate;";
        dbDelta( $sql_activity );

        // Schengen trips for 90/180 day tracker
        $table_schengen = self::get_table( 'schengen_trips' );
        $sql_schengen = "CREATE TABLE $table_schengen (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            country varchar(100) NOT NULL,
            category varchar(20) DEFAULT 'personal',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";
        dbDelta( $sql_schengen );

        // Update database version
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

        // Insert default stages
        self::insert_default_stages();
    }

    /**
     * Insert default pipeline stages for each visa type
     *
     * @return void
     */
    public static function insert_default_stages() {
        global $wpdb;

        $table = self::get_table( 'stages' );

        // Check if stages already exist
        $existing = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        if ( $existing > 0 ) {
            return;
        }

        // Default stages for visitor visa
        $visitor_stages = array(
            array( 'visitor', 'planning',    'Planning',           'Research and initial preparation',           1, '#6366f1', 'clipboard-list' ),
            array( 'visitor', 'documents',   'Document Prep',      'Gathering and preparing required documents', 2, '#8b5cf6', 'folder' ),
            array( 'visitor', 'application', 'Application',        'Submitting visa application',                3, '#0ea5e9', 'paper-airplane' ),
            array( 'visitor', 'approval',    'Approval',           'Waiting for visa decision',                  4, '#f59e0b', 'clock' ),
            array( 'visitor', 'moving',      'Moving',             'Preparing for and executing the move',       5, '#10b981', 'truck' ),
            array( 'visitor', 'settling',    'Settling In',        'First months in France',                     6, '#06b6d4', 'home' ),
        );

        // Default stages for talent passport
        $talent_stages = array(
            array( 'talent', 'planning',    'Planning',           'Research and eligibility assessment',        1, '#6366f1', 'clipboard-list' ),
            array( 'talent', 'documents',   'Document Prep',      'Gathering employment and qualification docs', 2, '#8b5cf6', 'folder' ),
            array( 'talent', 'employer',    'Employer Process',   'Contract and company registration',          3, '#ec4899', 'building-office' ),
            array( 'talent', 'application', 'Application',        'Submitting talent passport application',     4, '#0ea5e9', 'paper-airplane' ),
            array( 'talent', 'approval',    'Approval',           'Waiting for visa decision',                  5, '#f59e0b', 'clock' ),
            array( 'talent', 'moving',      'Moving',             'Preparing for and executing the move',       6, '#10b981', 'truck' ),
            array( 'talent', 'settling',    'Settling In',        'OFII and administrative tasks',              7, '#06b6d4', 'home' ),
        );

        // Default stages for student visa
        $student_stages = array(
            array( 'student', 'planning',    'Planning',           'School research and preparation',            1, '#6366f1', 'clipboard-list' ),
            array( 'student', 'admission',   'School Admission',   'Applying to French schools',                 2, '#ec4899', 'academic-cap' ),
            array( 'student', 'documents',   'Document Prep',      'Gathering required documents',               3, '#8b5cf6', 'folder' ),
            array( 'student', 'campus',      'Campus France',      'Campus France registration',                 4, '#f97316', 'globe' ),
            array( 'student', 'application', 'Application',        'Submitting student visa application',        5, '#0ea5e9', 'paper-airplane' ),
            array( 'student', 'approval',    'Approval',           'Waiting for visa decision',                  6, '#f59e0b', 'clock' ),
            array( 'student', 'moving',      'Moving',             'Preparing for and executing the move',       7, '#10b981', 'truck' ),
            array( 'student', 'settling',    'Settling In',        'OFII validation and registration',           8, '#06b6d4', 'home' ),
        );

        // Default stages for family visa
        $family_stages = array(
            array( 'family', 'planning',     'Planning',           'Research and eligibility assessment',        1, '#6366f1', 'clipboard-list' ),
            array( 'family', 'documents',    'Document Prep',      'Gathering family and relationship docs',     2, '#8b5cf6', 'folder' ),
            array( 'family', 'sponsor',      'Sponsor Process',    'Sponsor eligibility and housing',            3, '#ec4899', 'users' ),
            array( 'family', 'application',  'Application',        'Submitting family visa application',         4, '#0ea5e9', 'paper-airplane' ),
            array( 'family', 'approval',     'Approval',           'Waiting for visa decision',                  5, '#f59e0b', 'clock' ),
            array( 'family', 'moving',       'Moving',             'Preparing for and executing the move',       6, '#10b981', 'truck' ),
            array( 'family', 'settling',     'Settling In',        'OFII and family integration',                7, '#06b6d4', 'home' ),
        );

        $all_stages = array_merge( $visitor_stages, $talent_stages, $student_stages, $family_stages );

        foreach ( $all_stages as $stage ) {
            $wpdb->insert(
                $table,
                array(
                    'visa_type'   => $stage[0],
                    'slug'        => $stage[1],
                    'title'       => $stage[2],
                    'description' => $stage[3],
                    'sort_order'  => $stage[4],
                    'color'       => $stage[5],
                    'icon'        => $stage[6],
                ),
                array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
            );
        }
    }

    /**
     * Check if schema needs to be updated
     *
     * @return bool
     */
    public static function needs_update() {
        $current_version = get_option( self::DB_VERSION_OPTION, '0' );
        return version_compare( $current_version, self::DB_VERSION, '<' );
    }

    /**
     * Drop all portal tables (for uninstall)
     *
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        foreach ( self::$tables as $table ) {
            $full_name = $wpdb->prefix . $table;
            $wpdb->query( "DROP TABLE IF EXISTS $full_name" );
        }

        delete_option( self::DB_VERSION_OPTION );
    }
}
