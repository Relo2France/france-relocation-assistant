<?php
/**
 * Member notices
 *
 * When a knowledge-base topic changes, the members it touches should hear
 * about it, but not without a human reading the note first. This class
 * turns an approved topic update into a draft notice addressed to the
 * members whose profile the topic applies to, keeps the drafts until Kevin
 * approves them, and emails him one digest a day listing what is waiting.
 * Approving a draft sends it as a normal message to every member on it,
 * with the usual email.
 *
 * @package FRAMT
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRAMT_Member_Notices {

    const OPTION    = 'framt_member_notice_drafts';
    const CRON_HOOK = 'framt_member_notice_digest';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // The assistant plugin fires this after an approved review lands.
        add_action('fra_topic_updated', array($this, 'on_topic_updated'), 10, 5);

        add_action('init', array($this, 'schedule'));
        add_action(self::CRON_HOOK, array($this, 'send_digest'));

        // Approve / discard from the digest email or the messages page.
        add_action('admin_init', array($this, 'handle_action'));
        add_action('framt_messages_admin_top', array($this, 'render_admin_box'));
        add_action('admin_notices', array($this, 'admin_notice'));
    }

    // ------------------------------------------------------------------
    // Drafting
    // ------------------------------------------------------------------

    /**
     * An approved topic update becomes a draft notice for the members it
     * applies to. Nothing is sent here.
     *
     * @param string $category    KB category key.
     * @param string $topic       Topic key.
     * @param array  $review      The review record that was approved.
     * @param string $old_content Content before.
     * @param string $new_content Content after.
     * @return void
     */
    public function on_topic_updated($category, $topic, $review, $old_content, $new_content) {
        if ((string) $old_content === (string) $new_content) {
            return;
        }
        $audience = $this->audience_for($category, $topic);
        if (empty($audience['users'])) {
            return;
        }

        $kb    = get_option('fra_knowledge_base', array());
        $title = isset($kb[$category][$topic]['title']) ? $kb[$category][$topic]['title'] : (isset($review['topic_name']) ? $review['topic_name'] : ucfirst(str_replace('_', ' ', $topic)));
        $summary = trim((string) ($review['changes_summary'] ?? ''));
        $sources = array_filter(array_map('strval', (array) ($review['sources_checked'] ?? array())));

        $body  = 'The topic **' . $title . '** was re-checked against official sources and updated on ' . date_i18n('j F Y') . ".\n\n";
        $body .= "**What changed**\n\n" . ('' !== $summary ? $summary : 'The wording was brought in line with the current official text.') . "\n\n";
        $body .= '**Why you are getting this**' . "\n\n" . $audience['reason'] . "\n\n";
        if (!empty($sources)) {
            $body .= "**Sources checked**\n\n";
            foreach (array_slice($sources, 0, 5) as $src) {
                $body .= '- ' . $src . "\n";
            }
            $body .= "\n";
        }
        $body .= 'Ask about my case has the updated text, and any step or dossier item it feeds now reads the new way.';

        $drafts   = $this->drafts();
        $id       = 'n' . time() . substr(md5($category . $topic . wp_rand()), 0, 6);
        $drafts[$id] = array(
            'id'        => $id,
            'created'   => current_time('mysql'),
            'category'  => (string) $category,
            'topic'     => (string) $topic,
            'title'     => (string) $title,
            'subject'   => 'Update: ' . $title,
            'body'      => $body,
            'audience'  => array_values(array_map('intval', $audience['users'])),
            'reason'    => $audience['reason'],
            'status'    => 'pending',
        );
        update_option(self::OPTION, $drafts, false);
    }

    /**
     * Which members a topic applies to, from their profile, and the sentence
     * that tells them why.
     *
     * @param string $category KB category key.
     * @param string $topic    Topic key.
     * @return array users => int[], reason => string
     */
    private function audience_for($category, $topic) {
        $visa_routes = array(
            'visitor' => array('visitor'),
            'work'    => array('employee'),
            'talent'  => array('talent_passport'),
            'spouse'  => array('spouse_french', 'family'),
        );

        if ('visas' === $category && isset($visa_routes[$topic])) {
            return array(
                'users'  => $this->members_where('fra_visa_type', $visa_routes[$topic]),
                'reason' => 'Your profile says this is your visa route.',
            );
        }
        if ('property' === $category) {
            $buyers   = $this->members_where('fra_housing_plan', array('buying', 'already_own'));
            $mortgage = $this->members_where('fra_french_mortgage', array('yes', 'maybe'));
            return array(
                'users'  => array_values(array_unique(array_merge($buyers, $mortgage))),
                'reason' => 'Your profile says you plan to buy, or already own, in France.',
            );
        }
        if ('shipping' === $category && 'pets' === $topic) {
            $all  = $this->members_where('fra_has_pets', array('dogs', 'cats', 'both', 'other'));
            return array('users' => $all, 'reason' => 'Your profile says you are moving with pets.');
        }
        if ('visas' === $category || 'visa_application_guide' === $category) {
            return array(
                'users'  => $this->all_members(),
                'reason' => 'It applies to every long-stay visa application from the United States.',
            );
        }
        return array(
            'users'  => $this->all_members(),
            'reason' => 'It applies to everyone moving to France, whatever the route.',
        );
    }

    /**
     * Members with a profile whose meta key has one of the given values.
     *
     * @param string $meta_key Profile meta key.
     * @param array  $values   Accepted values.
     * @return int[]
     */
    private function members_where($meta_key, $values) {
        $users = get_users(array(
            'fields'     => 'ID',
            'number'     => 5000,
            'meta_query' => array(
                array('key' => $meta_key, 'value' => $values, 'compare' => 'IN'),
            ),
        ));
        return array_map('intval', (array) $users);
    }

    /**
     * Every member with a profile: anyone who has set a visa route.
     *
     * @return int[]
     */
    private function all_members() {
        $users = get_users(array(
            'fields'     => 'ID',
            'number'     => 5000,
            'meta_query' => array(
                array('key' => 'fra_visa_type', 'value' => '', 'compare' => '!='),
            ),
        ));
        return array_map('intval', (array) $users);
    }

    // ------------------------------------------------------------------
    // Storage
    // ------------------------------------------------------------------

    private function drafts() {
        $drafts = get_option(self::OPTION, array());
        return is_array($drafts) ? $drafts : array();
    }

    public function pending() {
        return array_filter($this->drafts(), function ($d) {
            return 'pending' === ($d['status'] ?? '');
        });
    }

    // ------------------------------------------------------------------
    // Approve / discard
    // ------------------------------------------------------------------

    /**
     * Send a draft to everyone on it, as a normal message from the team.
     *
     * @param string $id Draft id.
     * @return int Members it went to, or -1 if the draft was not pending.
     */
    public function approve($id) {
        $drafts = $this->drafts();
        if (!isset($drafts[$id]) || 'pending' !== $drafts[$id]['status']) {
            return -1;
        }
        $messages = framt()->get_component('messages');
        if (!$messages) {
            return -1;
        }
        $admin_id = get_current_user_id();
        if (!$admin_id) {
            $admins   = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
            $admin_id = !empty($admins) ? (int) $admins[0] : 1;
        }
        $sent = 0;
        foreach ($drafts[$id]['audience'] as $user_id) {
            if ($messages->create_admin_message((int) $user_id, $drafts[$id]['subject'], $drafts[$id]['body'], $admin_id)) {
                $sent++;
            }
        }
        $drafts[$id]['status']   = 'sent';
        $drafts[$id]['sent']     = $sent;
        $drafts[$id]['sent_at']  = current_time('mysql');
        update_option(self::OPTION, $this->trim($drafts), false);
        return $sent;
    }

    public function discard($id) {
        $drafts = $this->drafts();
        if (!isset($drafts[$id])) {
            return false;
        }
        $drafts[$id]['status'] = 'discarded';
        update_option(self::OPTION, $this->trim($drafts), false);
        return true;
    }

    /** Keep the last 50 resolved drafts so the record stays short. */
    private function trim($drafts) {
        $resolved = array_filter($drafts, function ($d) {
            return 'pending' !== $d['status'];
        });
        if (count($resolved) > 50) {
            uasort($resolved, function ($a, $b) {
                return strcmp($a['created'], $b['created']);
            });
            foreach (array_slice(array_keys($resolved), 0, count($resolved) - 50) as $old) {
                unset($drafts[$old]);
            }
        }
        return $drafts;
    }

    /**
     * Links in the digest email and the admin box land here.
     */
    public function handle_action() {
        if (empty($_GET['framt_notice']) || empty($_GET['framt_notice_action'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        $id     = sanitize_key($_GET['framt_notice']);
        $action = sanitize_key($_GET['framt_notice_action']);
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'framt_notice_' . $id)) {
            wp_die('That link has expired. Open Member Messages in wp-admin and approve the notice there.');
        }
        $result = 'approve' === $action ? $this->approve($id) : ($this->discard($id) ? 'discarded' : 'missing');
        wp_safe_redirect(add_query_arg(array('page' => 'fra-messages', 'framt_notice_done' => is_int($result) ? 'sent-' . $result : $result), admin_url('admin.php')));
        exit;
    }

    public function action_url($id, $action) {
        return wp_nonce_url(
            add_query_arg(array('page' => 'fra-messages', 'framt_notice' => $id, 'framt_notice_action' => $action), admin_url('admin.php')),
            'framt_notice_' . $id
        );
    }

    public function admin_notice() {
        if (empty($_GET['framt_notice_done'])) {
            return;
        }
        $done = sanitize_text_field($_GET['framt_notice_done']);
        if (0 === strpos($done, 'sent-')) {
            $n = (int) substr($done, 5);
            $text = $n < 0 ? 'That notice was already handled.' : sprintf('Notice sent to %d member%s.', $n, 1 === $n ? '' : 's');
        } elseif ('discarded' === $done) {
            $text = 'Notice discarded.';
        } else {
            $text = 'That notice no longer exists.';
        }
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($text) . '</p></div>';
    }

    // ------------------------------------------------------------------
    // Admin box on the messages page
    // ------------------------------------------------------------------

    public function render_admin_box() {
        $pending = $this->pending();
        if (empty($pending)) {
            return;
        }
        echo '<div class="framt-notice-drafts" style="background:#fff;border:1px solid #dde3de;border-left:4px solid #b87a21;padding:16px 20px;margin:16px 0;">';
        echo '<h2 style="margin:0 0 4px;">Notices waiting for your approval</h2>';
        echo '<p style="margin:0 0 12px;color:#5f6e66;">A knowledge-base topic changed. Each draft below goes to the members it applies to when you approve it. Nothing is sent until you do.</p>';
        foreach ($pending as $d) {
            echo '<details style="border-top:1px solid #ebefeb;padding:10px 0;"><summary style="cursor:pointer;font-weight:600;">' . esc_html($d['subject']) . ' <span style="font-weight:400;color:#5f6e66;">· ' . count($d['audience']) . ' member' . (1 === count($d['audience']) ? '' : 's') . ' · drafted ' . esc_html(mysql2date('j M', $d['created'])) . '</span></summary>';
            echo '<div style="max-width:640px;padding:8px 0 4px;">' . wp_kses_post(FRAMT_Messages::markdown_to_html($d['body'])) . '</div>';
            echo '<p style="margin:8px 0 0;"><a class="button button-primary" href="' . esc_url($this->action_url($d['id'], 'approve')) . '">Approve and send</a> ';
            echo '<a class="button" href="' . esc_url($this->action_url($d['id'], 'discard')) . '" style="margin-left:6px;">Discard</a></p>';
            echo '</details>';
        }
        echo '</div>';
    }

    // ------------------------------------------------------------------
    // Daily digest
    // ------------------------------------------------------------------

    public function schedule() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(strtotime('tomorrow 08:00:00 UTC'), 'daily', self::CRON_HOOK);
        }
    }

    /**
     * One email a day, only when something is waiting.
     */
    public function send_digest() {
        $pending = $this->pending();
        if (empty($pending)) {
            return;
        }
        $n    = count($pending);
        $body = '';
        foreach ($pending as $d) {
            $body .= '<div style="border:1px solid #dde3de;border-radius:10px;padding:16px 18px;margin:0 0 16px;">';
            $body .= '<p style="margin:0 0 2px;font-weight:600;">' . esc_html($d['subject']) . '</p>';
            $body .= '<p style="margin:0 0 10px;color:#5f6e66;font-size:14px;">To ' . count($d['audience']) . ' member' . (1 === count($d['audience']) ? '' : 's') . ' · ' . esc_html($d['reason']) . '</p>';
            $body .= '<div style="font-size:15px;">' . FRAMT_Messages::markdown_to_html($d['body']) . '</div>';
            $body .= '<p style="margin:12px 0 0;"><a href="' . esc_url($this->action_url($d['id'], 'approve')) . '" style="display:inline-block;background:#2c5346;color:#fff;text-decoration:none;font-weight:600;padding:9px 16px;border-radius:999px;font-size:14px;">Approve and send</a> ';
            $body .= '<a href="' . esc_url($this->action_url($d['id'], 'discard')) . '" style="display:inline-block;color:#5f6e66;text-decoration:none;padding:9px 12px;font-size:14px;">Discard</a></p>';
            $body .= '</div>';
        }
        $html = FRAMT_Messages::render_email(
            1 === $n ? 'One notice to approve' : $n . ' notices to approve',
            'Knowledge-base changes drafted into member notices. Approve links work for 24 hours; after that, use the messages page.',
            $body,
            'Open Member Messages',
            admin_url('admin.php?page=fra-messages')
        );
        FRAMT_Messages::send_html(get_option('admin_email'), (1 === $n ? 'One member notice' : $n . ' member notices') . ' waiting for approval', $html);
    }
}
