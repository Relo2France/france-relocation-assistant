<?php
/**
 * The emails the notification switches in Settings promise.
 *
 * Step reminders: only for stages the member has started (a step in it is
 * in progress, waiting or done). Upcoming: the next two dated steps there
 * that are not yet due, a week before and the day before. Overdue steps are
 * tracked separately, so a missed date never hides what is coming: each is
 * reminded once per due date, and an email lists at most the two most
 * recently overdue.
 * One email a day at most, to whoever the step is assigned to (the owner
 * when nobody is). Weekly digest: Monday morning, the week behind and the
 * next steps ahead.
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

    /** @var array|null Upcoming and overdue steps, worked out once per run. */
    private $next_cache = null;

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
        $this->next_cache = null;
        $this->send_task_reminders();
        // Monday, in the site's timezone.
        if ( '1' === wp_date( 'N' ) ) {
            $this->send_weekly_digests();
        }
    }

    // ------------------------------------------------------------------
    // Task reminders
    // ------------------------------------------------------------------

    /** How many of the next (not yet due) steps a reminder looks at. */
    const NEXT_STEPS = 2;

    /** How many overdue steps one email lists: the most recently overdue. */
    const OVERDUE_STEPS = 2;

    /**
     * Stages a household has started: any stage where at least one step
     * is in progress, waiting or done. Until then the stage stays quiet.
     *
     * @return array project id => stage keys
     */
    private function started_stages() {
        global $wpdb;
        $tasks = FRAMT_Portal_Schema::get_table( 'tasks' );
        $rows  = $wpdb->get_results(
            "SELECT project_id, stage FROM $tasks
             WHERE parent_task_id IS NULL AND stage IS NOT NULL AND stage <> ''
               AND status IN ('in_progress','waiting','done')
             GROUP BY project_id, stage"
        );
        $out = array();
        foreach ( (array) $rows as $row ) {
            $out[ (int) $row->project_id ][ (string) $row->stage ] = true;
        }
        return $out;
    }

    /**
     * Dated steps for each household, in started stages only.
     *
     * @return array project id => array(
     *     'upcoming'      => the next NEXT_STEPS steps not yet due, earliest first,
     *     'overdue'       => the OVERDUE_STEPS most recently overdue steps, latest first,
     *     'overdue_count' => every open overdue step in started stages,
     * )
     */
    private function dated_steps() {
        global $wpdb;
        if ( null !== $this->next_cache ) {
            return $this->next_cache;
        }
        $tasks    = FRAMT_Portal_Schema::get_table( 'tasks' );
        $projects = FRAMT_Portal_Schema::get_table( 'projects' );
        $started  = $this->started_stages();
        if ( empty( $started ) ) {
            $this->next_cache = array();
            return array();
        }
        $rows = $wpdb->get_results(
            "SELECT t.id, t.project_id, t.user_id, t.title, t.due_date, t.assignee_id, t.stage
             FROM $tasks t INNER JOIN $projects p ON p.id = t.project_id
             WHERE t.status <> 'done' AND t.due_date IS NOT NULL AND t.parent_task_id IS NULL
               AND t.portal_visible = 1 AND p.status = 'active'
             ORDER BY t.project_id, t.due_date ASC, t.sort_order ASC, t.id ASC"
        );
        $today = wp_date( 'Y-m-d' );
        $out   = array();
        foreach ( (array) $rows as $row ) {
            $pid = (int) $row->project_id;
            if ( empty( $started[ $pid ][ (string) $row->stage ] ) ) {
                continue;
            }
            if ( ! isset( $out[ $pid ] ) ) {
                $out[ $pid ] = array( 'upcoming' => array(), 'overdue' => array(), 'overdue_count' => 0 );
            }
            if ( substr( (string) $row->due_date, 0, 10 ) < $today ) {
                // Rows come earliest first; keep the latest overdue at the front.
                array_unshift( $out[ $pid ]['overdue'], $row );
                $out[ $pid ]['overdue_count']++;
            } elseif ( count( $out[ $pid ]['upcoming'] ) < self::NEXT_STEPS ) {
                $out[ $pid ]['upcoming'][] = $row;
            }
        }
        foreach ( $out as $pid => $group ) {
            $out[ $pid ]['overdue'] = array_slice( $group['overdue'], 0, self::OVERDUE_STEPS );
        }
        $this->next_cache = $out;
        return $out;
    }

    /**
     * The next dated steps not yet due for each household, in started
     * stages only, earliest first.
     *
     * @return array project id => task rows (at most NEXT_STEPS)
     */
    public function next_steps() {
        $out = array();
        foreach ( $this->dated_steps() as $pid => $group ) {
            if ( ! empty( $group['upcoming'] ) ) {
                $out[ $pid ] = $group['upcoming'];
            }
        }
        return $out;
    }

    /**
     * Reminders. A stage the member has not started sends nothing. For each
     * of the next two steps not yet due: once a week before, once the day
     * before. Separately, each of the two most recently overdue steps is
     * reminded once for its due date. At most one email per person per day;
     * moving a date re-arms its reminders.
     *
     * @return int Emails sent.
     */
    public function send_task_reminders() {
        if ( ! class_exists( 'FRAMT_Portal_Schema' ) ) {
            return 0;
        }
        $today  = wp_date( 'Y-m-d' );
        $outbox = array(); // recipient id => items
        $marks  = array(); // owner id => keys to record

        foreach ( $this->dated_steps() as $group ) {
            $candidates = array();
            foreach ( $group['upcoming'] as $row ) {
                $candidates[] = $row;
            }
            foreach ( $group['overdue'] as $row ) {
                $candidates[] = $row;
            }
            foreach ( $candidates as $row ) {
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
                $key  = $row->id . ':' . $kind . ':' . substr( (string) $row->due_date, 0, 10 );
                $sent = get_user_meta( $owner, self::SENT_META, true );
                if ( is_array( $sent ) && isset( $sent[ $key ] ) ) {
                    continue;
                }
                $to = $row->assignee_id && $this->in_household( (int) $row->assignee_id, $owner ) ? (int) $row->assignee_id : $owner;
                $outbox[ $to ][]   = array( 'task' => $row, 'kind' => $kind, 'days' => $days, 'owner' => $owner, 'key' => $key );
            }
        }

        $count = 0;
        foreach ( $outbox as $recipient => $items ) {
            // One email per person: the upcoming steps, and at most the two
            // most recently overdue steps across their projects.
            $upcoming = array_values( array_filter( $items, function ( $i ) {
                return 'overdue' !== $i['kind'];
            } ) );
            $overdue  = array_values( array_filter( $items, function ( $i ) {
                return 'overdue' === $i['kind'];
            } ) );
            usort( $overdue, function ( $a, $b ) {
                return $b['days'] <=> $a['days'];
            } );
            $items = array_merge( $upcoming, array_slice( $overdue, 0, self::OVERDUE_STEPS ) );
            if ( $this->send_reminder_email( $recipient, $items ) ) {
                $count++;
                foreach ( $items as $item ) {
                    $marks[ $item['owner'] ][] = $item['key'];
                }
            }
        }
        // Record what went out. Keys are pruned by the due date they carry,
        // not by when they were sent: an overdue step stays open (and stays
        // among the most recently overdue) for as long as the member leaves
        // it, and pruning by send date would re-send its reminder every
        // sixty days. A moved date makes a new key anyway, and a done step
        // is never a candidate, so a key only has to outlive any plausible
        // open date: keep those whose due date is within the last 400 days.
        $cutoff = wp_date( 'Y-m-d', strtotime( '-400 days', current_time( 'timestamp' ) ) );
        foreach ( $marks as $owner => $keys ) {
            $sent = get_user_meta( $owner, self::SENT_META, true );
            $sent = is_array( $sent ) ? $sent : array();
            foreach ( $keys as $key ) {
                $sent[ $key ] = $today;
            }
            $sent = array_filter( $sent, function ( $day, $key ) use ( $cutoff ) {
                $parts = explode( ':', (string) $key );
                $due   = isset( $parts[2] ) ? $parts[2] : (string) $day;
                return $due >= $cutoff;
            }, ARRAY_FILTER_USE_BOTH );
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
                ? sprintf( 'Was due %s', wp_date( 'M j', strtotime( $t->due_date ) ) )
                : ( 'tomorrow' === $item['kind'] ? 'Due tomorrow' : sprintf( 'Due in a week, %s', wp_date( 'l, M j', strtotime( $t->due_date ) ) ) );
            $rows .= '<tr><td style="padding:10px 0;border-top:1px solid #ebefeb;">'
                . '<a href="' . esc_url( self::portal_url( 'tasks', array( 'task' => (int) $t->id ) ) ) . '" style="color:#1c2420;font-weight:600;text-decoration:none;">' . esc_html( $t->title ) . '</a>'
                . '<div style="font-size:14px;color:' . ( 'overdue' === $item['kind'] ? '#8a5a14' : '#5f6e66' ) . ';">' . esc_html( $when ) . '</div></td></tr>';
        }
        $n       = count( $items );
        $overdue = count( array_filter( $items, function ( $i ) {
            return 'overdue' === $i['kind'];
        } ) );
        $title   = 1 === $n ? 'Your next step' : 'Your next steps';
        $lead    = 'Hello ' . ( $first ?: 'there' ) . ', ' . ( $overdue ? 'a date has passed. Past dates are not failures; they are the order to work in.' : 'here is what comes next in the part of the plan you are working on.' );
        $body    = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table>';
        $html    = FRAMT_Messages::render_email( $title, $lead, $body, 'Open the step', self::portal_url( 'tasks', array( 'task' => (int) $items[0]['task']->id ) ), true );
        $subject = 'Next step: ' . $items[0]['task']->title;
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
        $groups  = $this->dated_steps();
        $group   = $groups[ (int) $project->id ] ?? array();
        $ahead   = $group['upcoming'] ?? array();
        $overdue = (int) ( $group['overdue_count'] ?? 0 );
        $move = $project->target_move_date ?: (string) get_user_meta( $owner, 'fra_target_move_date', true );
        $lead = 'Your week in the plan.';
        if ( $move ) {
            $days = (int) round( ( strtotime( substr( $move, 0, 10 ) ) - strtotime( $today ) ) / DAY_IN_SECONDS );
            if ( $days > 0 ) {
                $lead = sprintf( '%d days to your move on %s.', $days, wp_date( 'M j, Y', strtotime( $move ) ) );
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
            $body .= $section( 'Your next steps', $list( array_map( function ( $t ) {
                return '<li style="margin:0 0 4px;"><a href="' . esc_url( self::portal_url( 'tasks', array( 'task' => (int) $t->id ) ) ) . '" style="color:#1c2420;">' . esc_html( $t->title ) . '</a> <span style="color:#5f6e66;">· ' . esc_html( wp_date( 'D, M j', strtotime( $t->due_date ) ) ) . '</span></li>';
            }, $ahead ) ) );
        } else {
            $body .= $section( 'Your next steps', '<p style="margin:0;">Nothing dated in the part of the plan you are working on. When you start the next stage, its steps show here.</p>' );
        }
        if ( $overdue > 0 ) {
            $body .= '<p style="margin:10px 0 0;color:#8a5a14;">' . esc_html(
                sprintf(
                    1 === $overdue ? '%d step is past its date. Past dates are not failures; they are the order to work in.' : '%d steps are past their dates. Past dates are not failures; they are the order to work in.',
                    $overdue
                )
            ) . '</p>';
        }
        $first = $user->first_name ?: strtok( $user->display_name, ' ' );
        $html  = FRAMT_Messages::render_email( 'Your week, ' . ( $first ?: 'there' ), $lead, $body, 'Open your plan', self::portal_url( 'dashboard' ), true );
        return FRAMT_Messages::send_html( $user->user_email, 'Your week in the move to France', $html );
    }
}
