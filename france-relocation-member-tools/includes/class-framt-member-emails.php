<?php
/**
 * The emails the notification switches in Settings promise.
 *
 * Task reminders: a step's date seven days out, the day before, and once
 * when it has passed, gathered into one email a day per person, sent to
 * whoever the step is assigned to (the owner when nobody is). Weekly
 * digest: Monday morning, the week behind and the fortnight ahead.
 * "Email updates" is the master switch: off, and none of these, nor the
 * message and report-ready emails, are sent. Invitations and password
 * emails are not updates and are always sent.
 *
 * Switches are stored per household on the owner (fra_portal_settings),
 * the same record the Settings screen reads.
 *
 * @package     FRA_Member_Tools
 * @subpackage  Notifications
 * @since       2.9.25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FRAMT_Member_Emails {

    const DAILY_HOOK = 'framt_member_emails_daily';
    const SENT_META  = 'framt_reminders_sent';

    /** Defaults match get_user_settings() in the portal API. */
    const DEFAULTS = array(
        'email_notifications' => true,
        'task_reminders'      => true,
        'weekly_digest'       => false,
    );

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'schedule' ) );
        add_action( self::DAILY_HOOK, array( $this, 'run_daily' ) );
    }

    public function schedule() {
        if ( ! wp_next_scheduled( self::DAILY_HOOK ) ) {
            // 13:00 UTC: morning across the US, afternoon in France.
            $next = strtotime( 'today 13:00:00 UTC' );
            if ( $next <= time() ) {
                $next += DAY_IN_SECONDS;
            }
            wp_schedule_event( $next, 'daily', self::DAILY_HOOK );
        }
    }

    /**
     * Whether a household wants a kind of email.
     *
     * @param int    $owner_id Household owner.
     * @param string $kind     email_notifications|task_reminders|weekly_digest
     * @return bool
     */
    public static function wants( $owner_id, $kind ) {
        $settings = get_user_meta( (int) $owner_id, 'fra_portal_settings', true );
        $settings = array_merge( self::DEFAULTS, is_array( $settings ) ? $settings : array() );
        if ( empty( $settings['email_notifications'] ) ) {
            return false;
        }
        return 'email_notifications' === $kind ? true : ! empty( $settings[ $kind ] );
    }

    /**
     * The household owner for any member of it, partner included.
     */
    public static function owner_of( $user_id ) {
        $owner = (int) get_user_meta( (int) $user_id, 'framt_household_owner', true );
        return $owner > 0 ? $owner : (int) $user_id;
    }

    /**
     * The link to the portal, optionally to a view and a step.
     */
    public static function portal_url( $view = '', $args = array() ) {
        $url = home_url( '/portal/' );
        if ( '' !== $view ) {
            $args = array_merge( array( 'view' => $view ), $args );
        }
        return empty( $args ) ? $url : add_query_arg( $args, $url );
    }

    public function run_daily() {
        $this->send_task_reminders();
        // Monday, in the site's timezone.
        if ( '1' === wp_date( 'N' ) ) {
            $this->send_weekly_digests();
        }
    }

    // ------------------------------------------------------------------
    // Task reminders
    // ------------------------------------------------------------------

    /**
     * One email per person per day, listing each step that is seven days
     * out, due tomorrow, or newly past its date. Each (step, due date,
     * kind) is sent once; moving the date re-arms it.
     *
     * @return int Emails sent.
     */
    public function send_task_reminders() {
        global $wpdb;
        if ( ! class_exists( 'FRAMT_Portal_Schema' ) ) {
            return 0;
        }
        $tasks    = FRAMT_Portal_Schema::get_table( 'tasks' );
        $projects = FRAMT_Portal_Schema::get_table( 'projects' );
        $today    = wp_date( 'Y-m-d' );
        $in_seven = wp_date( 'Y-m-d', strtotime( '+7 days', current_time( 'timestamp' ) ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.id, t.user_id, t.title, t.due_date, t.assignee_id
             FROM $tasks t INNER JOIN $projects p ON p.id = t.project_id
             WHERE t.status <> 'done' AND t.due_date IS NOT NULL AND t.parent_task_id IS NULL
               AND t.portal_visible = 1 AND p.status = 'active'
               AND t.due_date >= %s AND t.due_date <= %s",
            wp_date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) ),
            $in_seven
        ) );

        $outbox = array(); // recipient id => items
        $marks  = array(); // owner id => keys to record
        foreach ( (array) $rows as $row ) {
            $days = (int) round( ( strtotime( $row->due_date ) - strtotime( $today ) ) / DAY_IN_SECONDS );
            if ( 7 === $days ) {
                $kind = 'week';
            } elseif ( 1 === $days ) {
                $kind = 'tomorrow';
            } elseif ( $days < 0 ) {
                $kind = 'overdue';
            } else {
                continue;
            }
            $owner = (int) $row->user_id;
            if ( ! self::wants( $owner, 'task_reminders' ) ) {
                continue;
            }
            $key  = $row->id . ':' . $kind . ':' . $row->due_date;
            $sent = get_user_meta( $owner, self::SENT_META, true );
            if ( is_array( $sent ) && isset( $sent[ $key ] ) ) {
                continue;
            }
            $to = $row->assignee_id && $this->in_household( (int) $row->assignee_id, $owner ) ? (int) $row->assignee_id : $owner;
            $outbox[ $to ][]  = array( 'task' => $row, 'kind' => $kind, 'days' => $days );
            $marks[ $owner ][] = $key;
        }

        $count = 0;
        foreach ( $outbox as $recipient => $items ) {
            if ( $this->send_reminder_email( $recipient, $items ) ) {
                $count++;
            }
        }
        // Record what went out, and forget anything older than sixty days.
        foreach ( $marks as $owner => $keys ) {
            $sent = get_user_meta( $owner, self::SENT_META, true );
            $sent = is_array( $sent ) ? $sent : array();
            foreach ( $keys as $key ) {
                $sent[ $key ] = $today;
            }
            $cutoff = wp_date( 'Y-m-d', strtotime( '-60 days', current_time( 'timestamp' ) ) );
            $sent   = array_filter( $sent, function ( $day ) use ( $cutoff ) {
                return $day >= $cutoff;
            } );
            update_user_meta( $owner, self::SENT_META, $sent );
        }
        return $count;
    }

    private function in_household( $user_id, $owner ) {
        if ( $user_id === $owner ) {
            return true;
        }
        $members = get_user_meta( $owner, 'framt_family_members', true );
        foreach ( (array) $members as $member ) {
            if ( is_array( $member ) && (int) ( $member['invitedUserId'] ?? 0 ) === $user_id ) {
                return true;
            }
        }
        return false;
    }

    private function send_reminder_email( $recipient, $items ) {
        $user = get_userdata( $recipient );
        if ( ! $user || ! is_email( $user->user_email ) ) {
            return false;
        }
        usort( $items, function ( $a, $b ) {
            return $a['days'] <=> $b['days'];
        } );
        $first = $user->first_name ?: strtok( $user->display_name, ' ' );
        $rows  = '';
        foreach ( $items as $item ) {
            $t    = $item['task'];
            $when = 'overdue' === $item['kind']
                ? sprintf( 'Was due %s', wp_date( 'j F', strtotime( $t->due_date ) ) )
                : ( 'tomorrow' === $item['kind'] ? 'Due tomorrow' : sprintf( 'Due in a week, %s', wp_date( 'l j F', strtotime( $t->due_date ) ) ) );
            $rows .= '<tr><td style="padding:10px 0;border-top:1px solid #ebefeb;">'
                . '<a href="' . esc_url( self::portal_url( 'tasks', array( 'task' => (int) $t->id ) ) ) . '" style="color:#1c2420;font-weight:600;text-decoration:none;">' . esc_html( $t->title ) . '</a>'
                . '<div style="font-size:14px;color:' . ( 'overdue' === $item['kind'] ? '#8a5a14' : '#5f6e66' ) . ';">' . esc_html( $when ) . '</div></td></tr>';
        }
        $n       = count( $items );
        $overdue = count( array_filter( $items, function ( $i ) {
            return 'overdue' === $i['kind'];
        } ) );
        $title   = 1 === $n ? 'One step needs you' : $n . ' steps need you';
        $lead    = 'Hello ' . ( $first ?: 'there' ) . ', ' . ( $overdue ? 'a date has passed. Past dates are not failures; they are the order to work in.' : 'here is what is coming up in your plan.' );
        $body    = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table>';
        $html    = FRAMT_Messages::render_email( $title, $lead, $body, 'Open your deadlines', self::portal_url( 'deadlines' ), true );
        $subject = 1 === $n ? 'Reminder: ' . $items[0]['task']->title : sprintf( 'Reminder: %d steps in your plan', $n );
        return FRAMT_Messages::send_html( $user->user_email, $subject, $html );
    }

    // ------------------------------------------------------------------
    // Weekly digest
    // ------------------------------------------------------------------

    /**
     * Monday: every owner who switched the digest on.
     *
     * @return int Emails sent.
     */
    public function send_weekly_digests() {
        global $wpdb;
        $owners = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
            'fra_portal_settings',
            '%' . $wpdb->esc_like( '"weekly_digest";b:1' ) . '%'
        ) );
        $count = 0;
        foreach ( array_map( 'intval', (array) $owners ) as $owner ) {
            if ( self::wants( $owner, 'weekly_digest' ) && $this->send_digest( $owner ) ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * The digest for one household.
     *
     * @param int $owner Household owner.
     * @return bool Sent.
     */
    public function send_digest( $owner ) {
        global $wpdb;
        $user = get_userdata( $owner );
        if ( ! $user || ! is_email( $user->user_email ) || ! class_exists( 'FRAMT_Portal_Schema' ) ) {
            return false;
        }
        $tasks    = FRAMT_Portal_Schema::get_table( 'tasks' );
        $projects = FRAMT_Portal_Schema::get_table( 'projects' );
        $project  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $projects WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1", $owner ) );
        if ( ! $project ) {
            return false;
        }
        $today = wp_date( 'Y-m-d' );
        $done  = $wpdb->get_col( $wpdb->prepare(
            "SELECT title FROM $tasks WHERE project_id = %d AND status = 'done' AND parent_task_id IS NULL AND completed_at >= %s ORDER BY completed_at DESC LIMIT 8",
            $project->id,
            wp_date( 'Y-m-d 00:00:00', strtotime( '-7 days', current_time( 'timestamp' ) ) )
        ) );
        $ahead = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, title, due_date FROM $tasks WHERE project_id = %d AND status <> 'done' AND parent_task_id IS NULL AND portal_visible = 1 AND due_date BETWEEN %s AND %s ORDER BY due_date ASC LIMIT 8",
            $project->id,
            $today,
            wp_date( 'Y-m-d', strtotime( '+14 days', current_time( 'timestamp' ) ) )
        ) );
        $overdue = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $tasks WHERE project_id = %d AND status <> 'done' AND parent_task_id IS NULL AND portal_visible = 1 AND due_date < %s",
            $project->id,
            $today
        ) );

        $move = $project->target_move_date ?: (string) get_user_meta( $owner, 'fra_target_move_date', true );
        $lead = 'Your week in the plan.';
        if ( $move ) {
            $days = (int) round( ( strtotime( substr( $move, 0, 10 ) ) - strtotime( $today ) ) / DAY_IN_SECONDS );
            if ( $days > 0 ) {
                $lead = sprintf( '%d days to your move on %s.', $days, wp_date( 'j F Y', strtotime( $move ) ) );
            }
        }

        $section = function ( $heading, $html ) {
            return '<p style="margin:18px 0 6px;font-family:Georgia,\'Times New Roman\',serif;font-size:18px;font-weight:600;color:#1c2420;">' . esc_html( $heading ) . '</p>' . $html;
        };
        $list = function ( $items ) {
            return '<ul style="margin:0;padding-left:20px;">' . implode( '', $items ) . '</ul>';
        };

        $body = '';
        if ( $done ) {
            $body .= $section( 'Done this week', $list( array_map( function ( $t ) {
                return '<li style="margin:0 0 4px;">' . esc_html( $t ) . '</li>';
            }, $done ) ) );
        }
        if ( $ahead ) {
            $body .= $section( 'The next two weeks', $list( array_map( function ( $t ) {
                return '<li style="margin:0 0 4px;"><a href="' . esc_url( self::portal_url( 'tasks', array( 'task' => (int) $t->id ) ) ) . '" style="color:#1c2420;">' . esc_html( $t->title ) . '</a> <span style="color:#5f6e66;">· ' . esc_html( wp_date( 'D j M', strtotime( $t->due_date ) ) ) . '</span></li>';
            }, $ahead ) ) );
        } else {
            $body .= $section( 'The next two weeks', '<p style="margin:0;">Nothing dated. A good week to get ahead on the next stage.</p>' );
        }
        if ( $overdue ) {
            $body .= '<p style="margin:16px 0 0;">' . ( 1 === $overdue ? 'One step is past its date.' : $overdue . ' steps are past their date.' ) . ' Past dates are not failures; they are the order to work in.</p>';
        }

        $first = $user->first_name ?: strtok( $user->display_name, ' ' );
        $html  = FRAMT_Messages::render_email( 'Your week, ' . ( $first ?: 'there' ), $lead, $body, 'Open your plan', self::portal_url( 'dashboard' ), true );
        return FRAMT_Messages::send_html( $user->user_email, 'Your week in the move to France', $html );
    }
}
