<?php
/**
 * Review API
 *
 * Two REST endpoints that let an external worker run the knowledge base
 * review instead of WP-Cron:
 *
 *   GET  /wp-json/fra/v1/review/topics       - what to review
 *   POST /wp-json/fra/v1/review/suggestions  - one suggested update
 *
 * Deliberate boundaries:
 *
 *   - Disabled by default. Until an admin enables it and generates a secret,
 *     both routes return 404 and nothing about this site changes.
 *   - The write route can ONLY add to fra_pending_reviews. It can never touch
 *     fra_knowledge_base. Human approval in wp-admin stays the only path from
 *     a suggestion to published content.
 *   - Authenticated by a shared secret, not a user session, because no user is
 *     present. The secret grants exactly these two capabilities and nothing
 *     else.
 *
 * @package France_Relocation_Assistant
 * @since 3.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Review_API {

    /** @var FRA_Review_API|null Singleton instance */
    private static $instance = null;

    /** @var string REST namespace (shared with the existing chat routes) */
    const NS = 'fra/v1';

    /** @var string Option holding the shared secret */
    const SECRET_OPTION = 'fra_review_api_secret';

    /** @var string Option gating the whole feature */
    const ENABLED_OPTION = 'fra_review_api_enabled';

    /** @var string Option recording the last successful call, for the admin UI */
    const LAST_CALL_OPTION = 'fra_review_api_last_call';

    /** @var string Option used as an advisory write lock */
    const LOCK_OPTION = 'fra_review_api_lock';

    /** @var string Transient prefix for rate limiting */
    const RATE_TRANSIENT = 'fra_review_api_rate';

    /** @var int Maximum writes accepted per hour */
    const RATE_LIMIT = 120;

    /** @var int Maximum pending suggestions held at once */
    const MAX_PENDING = 200;

    /** @var int Maximum accepted length for a content field */
    const MAX_CONTENT = 60000;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register routes
     */
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Is the feature switched on and usable?
     *
     * @return bool
     */
    public static function is_enabled() {
        if (!get_option(self::ENABLED_OPTION, false)) {
            return false;
        }
        return strlen((string) get_option(self::SECRET_OPTION, '')) >= 32;
    }

    /**
     * Generate and store a new shared secret.
     *
     * @return string The new secret (shown to the admin once)
     */
    public static function generate_secret() {
        $secret = wp_generate_password(48, false, false);
        update_option(self::SECRET_OPTION, $secret, false);
        return $secret;
    }

    /**
     * Register both routes.
     */
    public function register_routes() {
        register_rest_route(self::NS, '/review/topics', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_topics'),
            'permission_callback' => array($this, 'authenticate'),
        ));

        register_rest_route(self::NS, '/review/suggestions', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'create_suggestion'),
            'permission_callback' => array($this, 'authenticate'),
        ));

        // Knowledge base gaps waiting to be drafted. The drafting itself -
        // web search plus a model call, minutes per gap - runs on the worker
        // for the same reason the review does.
        register_rest_route(self::NS, '/review/gaps', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_gaps'),
                'permission_callback' => array($this, 'authenticate'),
            ),
            // Raising a gap by hand. The knowledge base should hold as much as
            // it can defend, so anything noticed as missing goes in the queue
            // to be researched - it is not published from here.
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'raise_gap'),
                'permission_callback' => array($this, 'authenticate'),
                'args'                => array(
                    'question' => array('required' => true, 'type' => 'string'),
                    'note'     => array('required' => false, 'type' => 'string'),
                    'category' => array('required' => false, 'type' => 'string'),
                    'topic'    => array('required' => false, 'type' => 'string'),
                ),
            ),
        ));

        register_rest_route(self::NS, '/review/gaps/report', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'report_gap_run'),
            'permission_callback' => array($this, 'authenticate'),
        ));

        register_rest_route(self::NS, '/review/gaps/(?P<id>[A-Za-z0-9_]+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'create_gap_draft'),
            'permission_callback' => array($this, 'authenticate'),
        ));

        // The worker delivers a finished location report here. The row was
        // created by the portal when the member asked for it.
        register_rest_route(self::NS, '/review/reports/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'receive_report'),
            'permission_callback' => array($this, 'authenticate'),
        ));

        // The worker reports the start and end of a run here, so the AI
        // Review screen shows what actually happened rather than the last
        // time WordPress ran the job itself.
        register_rest_route(self::NS, '/review/runs', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'record_run'),
            'permission_callback' => array($this, 'authenticate'),
        ));
    }

    /**
     * Store a location report the worker generated, or the reason it failed.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function receive_report($request) {
        global $wpdb;
        $id    = absint($request->get_param('id'));
        $table = $wpdb->prefix . 'framt_research_reports';
        $row   = $wpdb->get_row($wpdb->prepare("SELECT id, content FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if (!$row) {
            return new WP_Error('report_not_found', 'No such report row.', array('status' => 404));
        }

        $content = $request->get_param('content');
        $error   = (string) $request->get_param('error');
        if (is_array($content) && !empty($content)) {
            $content['generation'] = array(
                'model'              => sanitize_text_field((string) $request->get_param('model')),
                'web_sources'        => count((array) $request->get_param('web_sources')),
                'web_search_errors'  => array_map('sanitize_text_field', (array) $request->get_param('web_search_errors')),
                'duration_ms'        => (int) $request->get_param('duration_ms'),
                'completed_at'       => current_time('mysql'),
            );
            $stored = $content;
        } else {
            $stored = array(
                'status'    => 'failed',
                'error'     => '' !== $error ? sanitize_text_field($error) : 'The report came back empty.',
                'failed_at' => current_time('mysql'),
            );
        }

        $wpdb->update(
            $table,
            array('content' => wp_json_encode($stored), 'updated_at' => current_time('mysql')),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );

        if (!isset($stored['status'])) {
            $this->notify_report_requesters($id);
        } else {
            // Nobody wants a failed report in their folder; the member sees the
            // reason in the portal and can try again.
            $wpdb->delete($wpdb->prefix . 'framt_research_report_links', array('report_id' => $id), array('%d'));
        }

        return rest_ensure_response(array('stored' => isset($stored['status']) ? 'failed' : 'content', 'id' => $id));
    }

    /**
     * Email and message every member who asked for this report in the last
     * day. The link row in their documents was created when they asked.
     *
     * @param int $report_id Report row.
     * @return void
     */
    private function notify_report_requesters($report_id) {
        global $wpdb;
        $report = $wpdb->get_row($wpdb->prepare("SELECT location_name, location_type FROM {$wpdb->prefix}framt_research_reports WHERE id = %d", $report_id), ARRAY_A);
        if (!$report) {
            return;
        }
        $users = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}framt_research_report_links WHERE report_id = %d AND saved_at > %s",
            $report_id,
            date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS)
        ));
        if (empty($users)) {
            return;
        }
        $name       = (string) $report['location_name'];
        $portal     = home_url('/portal/?view=documents');
        $messages   = function_exists('framt') && framt() ? framt()->get_component('messages') : null;
        $admins     = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
        $admin_id   = !empty($admins) ? (int) $admins[0] : 1;
        $body_md    = "Your report on **{$name}** is written and waiting in Documents & files. Open it there, download it as a PDF, or remove it if you don't want to keep it.\n\nReports are kept and reused for thirty days, so asking for {$name} again soon costs nothing; after that you can refresh it.";

        foreach (array_unique(array_map('intval', $users)) as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
                continue;
            }
            if ($messages && method_exists($messages, 'create_admin_message')) {
                // create_admin_message emails the member itself, in the site's layout.
                $messages->create_admin_message($user_id, "Your {$name} report is ready", $body_md, $admin_id);
            } elseif (class_exists('FRAMT_Messages')) {
                $first = $user->first_name ?: strtok($user->display_name, ' ');
                $html  = FRAMT_Messages::render_email("Your {$name} report is ready", 'Hello ' . $first . ',', FRAMT_Messages::markdown_to_html($body_md), 'Open Documents & files', $portal);
                FRAMT_Messages::send_html($user->user_email, "Your {$name} report is ready", $html);
            }
        }
    }

    /**
     * Record a worker run in the same status and history the screen reads.
     *
     * state=started sets the in-progress block; state=completed fills the
     * summary and appends to Review History. Dry runs are labelled and never
     * counted as a real pass.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function record_run($request) {
        $state   = sanitize_key((string) $request->get_param('state'));
        $dry_run = (bool) $request->get_param('dry_run');
        $status  = get_option('fra_review_status', array());
        if (!is_array($status)) {
            $status = array();
        }

        if ('started' === $state) {
            $status = array_merge($status, array(
                'running'        => true,
                'trigger'        => $dry_run ? 'worker (dry run)' : 'worker',
                'total_topics'   => (int) $request->get_param('requested'),
                'processed'      => 0,
                'succeeded'      => 0,
                'changes_found'  => 0,
                'errors'         => 0,
                'current_topic'  => '',
                'started_at'     => current_time('mysql'),
                'completed_at'   => null,
                'error_messages' => array(),
                'instance_id'    => sanitize_text_field((string) $request->get_param('instance_id')),
            ));
            update_option('fra_review_status', $status);
            return rest_ensure_response(array('recorded' => 'started'));
        }

        if ('completed' !== $state) {
            return new WP_Error('bad_state', 'state must be started or completed', array('status' => 400));
        }

        $failures = (array) $request->get_param('failures');
        $messages = array();
        foreach ($failures as $f) {
            if (is_array($f) && !empty($f['topic'])) {
                $messages[] = sanitize_text_field($f['topic'] . ': ' . ($f['error'] ?? 'failed'));
            }
        }
        $attempted = (int) $request->get_param('attempted');
        $reviewed  = (int) $request->get_param('reviewed');
        $posted    = (int) $request->get_param('suggestions_posted');
        $failed    = (int) $request->get_param('failed');

        $status = array_merge($status, array(
            'running'        => false,
            'trigger'        => $dry_run ? 'worker (dry run)' : 'worker',
            'total_topics'   => $attempted,
            'processed'      => $attempted,
            'succeeded'      => $reviewed,
            'changes_found'  => $posted,
            'errors'         => $failed,
            'current_topic'  => '',
            'completed_at'   => current_time('mysql'),
            'error_messages' => $messages,
            'instance_id'    => sanitize_text_field((string) $request->get_param('instance_id')),
        ));
        update_option('fra_review_status', $status);

        $history = get_option('fra_review_history', array());
        if (!is_array($history)) {
            $history = array();
        }
        $only = (array) $request->get_param('only');
        $history[] = array(
            'timestamp'     => time(),
            'date'          => current_time('mysql'),
            'reviewed'      => $reviewed,
            'changes_found' => $posted,
            'errors'        => $failed,
            'filter'        => (!empty($only) ? count($only) . ' topic' . (1 === count($only) ? '' : 's') : 'all') . ($dry_run ? ' (worker, dry run)' : ' (worker)'),
            'trigger'       => $dry_run ? 'worker (dry run)' : 'worker',
        );
        update_option('fra_review_history', array_slice($history, -20));

        return rest_ensure_response(array('recorded' => 'completed', 'pending' => count((array) get_option('fra_pending_reviews', array()))));
    }

    /* ---------------------------------------------------------------------
     * Authentication
     * ------------------------------------------------------------------ */

    /**
     * Verify the shared secret from the Authorization header.
     *
     * While disabled, this reports 404 rather than 401 so the routes do not
     * advertise themselves on sites that never turn the feature on.
     *
     * @param WP_REST_Request $request Request
     * @return true|WP_Error
     */
    public function authenticate($request) {
        if (!self::is_enabled()) {
            return new WP_Error(
                'rest_no_route',
                __('No route was found matching the URL and request method.', 'france-relocation-assistant'),
                array('status' => 404)
            );
        }

        $provided = $this->get_bearer_token($request);
        $expected = (string) get_option(self::SECRET_OPTION, '');

        if ('' === $provided || !hash_equals($expected, $provided)) {
            return new WP_Error(
                'fra_review_forbidden',
                __('Invalid or missing review API credentials.', 'france-relocation-assistant'),
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Pull the bearer token out of the request.
     *
     * Falls back to X-FRA-Review-Key because some hosts strip Authorization.
     *
     * @param WP_REST_Request $request Request
     * @return string
     */
    private function get_bearer_token($request) {
        $header = (string) $request->get_header('authorization');

        if (0 === stripos($header, 'bearer ')) {
            return trim(substr($header, 7));
        }

        return trim((string) $request->get_header('x-fra-review-key'));
    }

    /* ---------------------------------------------------------------------
     * GET /review/topics
     * ------------------------------------------------------------------ */

    /**
     * Return every reviewable topic, with the metadata the prompt needs.
     *
     * The topic map (key facts, practice hints) stays in WordPress and is
     * merged in here, so there is one source of truth for what a topic is.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function get_topics($request) {
        $knowledge_base = get_option('fra_knowledge_base', array());
        $ai_review      = class_exists('FRA_AI_Review') ? FRA_AI_Review::get_instance() : null;
        $topics         = array();

        foreach ($knowledge_base as $category => $category_topics) {
            if (!is_array($category_topics)) {
                continue;
            }

            foreach ($category_topics as $topic_key => $topic_data) {
                if (!is_array($topic_data) || empty($topic_data['content'])) {
                    continue;
                }

                $info = $ai_review
                    ? $ai_review->get_topic_info($category, $topic_key, $topic_data)
                    : array();

                $topics[] = array(
                    'category'       => (string) $category,
                    'topic_key'      => (string) $topic_key,
                    'name'           => isset($info['name'])
                        ? $info['name']
                        : ($topic_data['title'] ?? ucfirst(str_replace('_', ' ', $topic_key))),
                    'content'        => (string) $topic_data['content'],
                    'keywords'       => isset($topic_data['keywords']) ? array_values((array) $topic_data['keywords']) : array(),
                    'last_verified'  => $topic_data['lastVerified'] ?? '',
                    'sources'        => isset($info['sources']) ? array_values((array) $info['sources']) : array(),
                    'key_facts'      => isset($info['key_facts']) ? array_values((array) $info['key_facts']) : array(),
                    'practice_hints' => isset($info['practice_hints']) ? array_values((array) $info['practice_hints']) : array(),
                );
            }
        }

        return new WP_REST_Response(array(
            'topics' => $topics,
            'count'  => count($topics),
        ), 200);
    }

    /* ---------------------------------------------------------------------
     * POST /review/suggestions
     * ------------------------------------------------------------------ */

    /**
     * Accept one suggested update and queue it for human approval.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function create_suggestion($request) {
        $rate = $this->check_rate_limit();
        if (is_wp_error($rate)) {
            return $rate;
        }

        $category  = sanitize_key((string) $request->get_param('category'));
        $topic_key = sanitize_key((string) $request->get_param('topic'));

        if ('' === $category || '' === $topic_key) {
            return new WP_Error(
                'fra_review_bad_request',
                __('category and topic are required.', 'france-relocation-assistant'),
                array('status' => 400)
            );
        }

        // The topic must already exist. This endpoint updates the knowledge
        // base's review queue - it is not a way to introduce new topics.
        $knowledge_base = get_option('fra_knowledge_base', array());
        if (!isset($knowledge_base[$category][$topic_key])) {
            return new WP_Error(
                'fra_review_unknown_topic',
                sprintf(
                    /* translators: 1: category, 2: topic key */
                    __('Unknown topic %1$s/%2$s.', 'france-relocation-assistant'),
                    $category,
                    $topic_key
                ),
                array('status' => 404)
            );
        }

        $suggested = $this->clean_content($request->get_param('suggested_content'));
        $practice  = $this->clean_content($request->get_param('in_practice_content'));

        if ('' === $suggested && '' === $practice) {
            return new WP_Error(
                'fra_review_empty',
                __('A suggestion must contain suggested_content or in_practice_content.', 'france-relocation-assistant'),
                array('status' => 400)
            );
        }

        // Two suggestions arriving together would otherwise read the same
        // array and one would overwrite the other - exactly the race that
        // corrupted the review counters. Serialise the read-modify-write.
        if (!$this->acquire_lock()) {
            return new WP_Error(
                'fra_review_busy',
                __('Another suggestion is being written. Retry shortly.', 'france-relocation-assistant'),
                array('status' => 409)
            );
        }

        $pending = get_option('fra_pending_reviews', array());
        if (!is_array($pending)) {
            $pending = array();
        }

        if (count($pending) >= self::MAX_PENDING) {
            $this->release_lock();
            return new WP_Error(
                'fra_review_queue_full',
                sprintf(
                    /* translators: %d: maximum pending suggestions */
                    __('The pending review queue is full (%d). Approve or dismiss some first.', 'france-relocation-assistant'),
                    self::MAX_PENDING
                ),
                array('status' => 409)
            );
        }

        $topic_data = $knowledge_base[$category][$topic_key];
        $review_id  = uniqid('review_');

        // Field names match what the scheduled review already writes, so the
        // existing approval screen renders these without modification.
        $pending[$review_id] = array(
            'id'                  => $review_id,
            'category'            => $category,
            'topic'               => $topic_key,
            'topic_name'          => sanitize_text_field((string) $request->get_param('topic_name')) ?: ($topic_data['title'] ?? $topic_key),
            'update_type'         => $this->clean_enum($request->get_param('update_type'), array('none', 'minor', 'significant', 'rewrite'), 'minor'),
            'confidence'          => $this->clean_enum($request->get_param('confidence'), array('high', 'medium', 'low'), 'medium'),
            'current_content'     => (string) ($topic_data['content'] ?? ''),
            'suggested_content'   => $suggested,
            'in_practice_content' => $practice,
            'changes_summary'     => sanitize_textarea_field((string) $request->get_param('changes_summary')),
            'practice_sources'    => $this->clean_practice_sources($request->get_param('practice_sources')),
            'practice_withheld'   => sanitize_text_field((string) $request->get_param('practice_withheld')),
            'practice_corroboration' => (int) $request->get_param('practice_corroboration'),
            'key_insights'        => $this->clean_string_list($request->get_param('key_insights')),
            'sources_checked'     => $this->clean_string_list($request->get_param('sources_checked')),
            'web_sources'         => $this->clean_web_sources($request->get_param('web_sources')),
            'model_used'          => sanitize_text_field((string) $request->get_param('model_used')),
            'source'              => 'worker',
            'timestamp'           => current_time('mysql'),
        );

        update_option('fra_pending_reviews', $pending);
        update_option(self::LAST_CALL_OPTION, current_time('mysql'), false);
        $this->release_lock();

        return new WP_REST_Response(array(
            'review_id' => $review_id,
            'pending'   => count($pending),
        ), 201);
    }

    /* ---------------------------------------------------------------------
     * GET /review/gaps
     * ------------------------------------------------------------------ */

    /**
     * Gaps that have recurred often enough to be worth drafting.
     *
     * Returns everything the worker needs to write the addition, so it never
     * has to guess at the knowledge base's shape: for a depth gap, the topic
     * that matched and its current content; for a coverage gap, the categories
     * a new topic could belong to.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    /**
     * Record a gap someone raised deliberately.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    public function raise_gap($request) {
        if (!class_exists('FRA_KB_Gaps')) {
            return new WP_Error(
                'fra_gaps_unavailable',
                __('Knowledge base gap tracking is not available.', 'france-relocation-assistant'),
                array('status' => 503)
            );
        }

        $question = mb_substr(sanitize_text_field((string) $request->get_param('question')), 0, 300);
        $note     = mb_substr(sanitize_textarea_field((string) $request->get_param('note')), 0, 1000);
        $category = sanitize_key((string) $request->get_param('category'));
        $topic    = sanitize_key((string) $request->get_param('topic'));

        $matched = array();
        if ('' !== $category) {
            $matched = array('category' => $category, 'topic' => $topic, 'title' => '');
        }

        $id = FRA_KB_Gaps::raise($question, $note, $matched);

        if (false === $id) {
            return new WP_Error(
                'fra_gap_rejected',
                __('The gap was not recorded. Gap tracking may be disabled, or the question too short.', 'france-relocation-assistant'),
                array('status' => 400)
            );
        }

        return new WP_REST_Response(array('id' => $id, 'status' => 'queued'), 201);
    }

    public function get_gaps($request) {
        if (!class_exists('FRA_KB_Gaps')) {
            return new WP_REST_Response(array('gaps' => array(), 'count' => 0), 200);
        }

        $knowledge_base = get_option('fra_knowledge_base', array());
        $ready          = FRA_KB_Gaps::get_ready();
        $gaps           = array();

        foreach ($ready as $id => $gap) {
            $entry = array(
                'id'        => $id,
                'type'      => $gap['type'],
                'questions' => array_values((array) $gap['questions']),
                'count'     => (int) $gap['count'],
                'relevance' => isset($gap['relevance']) ? (float) $gap['relevance'] : 0.0,
            );

            // A raised gap carries the editor's note, and may name the topic
            // it belongs under - in which case it is drafted as an update.
            if ('raised' === $gap['type']) {
                $entry['note'] = isset($gap['answer']) ? (string) $gap['answer'] : '';
            }

            if ('depth' === $gap['type']
                || ('raised' === $gap['type'] && !empty($gap['matched']['category']))) {
                $category = $gap['matched']['category'] ?? '';
                $topic    = $gap['matched']['topic'] ?? '';

                if (!isset($knowledge_base[$category][$topic])) {
                    // A detected gap pointed at a topic that has since been
                    // renamed or removed - say so rather than sending the
                    // worker after it. A raised gap naming a topic that does
                    // not exist yet is a request for a new one, so let it
                    // through as a new topic inside the named category.
                    if ('raised' === $gap['type'] && isset($knowledge_base[$category])) {
                        $entry['categories'] = array($category);
                    } else {
                        $entry['stale'] = true;
                    }
                } else {
                    $entry['category']        = $category;
                    $entry['topic']           = $topic;
                    $entry['topic_name']      = $gap['matched']['title'] ?? $topic;
                    $entry['current_content'] = (string) $knowledge_base[$category][$topic]['content'];
                }
            } else {
                $entry['categories'] = array_keys($knowledge_base);
            }

            $gaps[] = $entry;
        }

        return new WP_REST_Response(array('gaps' => $gaps, 'count' => count($gaps)), 200);
    }

    /* ---------------------------------------------------------------------
     * POST /review/gaps/{id}
     * ------------------------------------------------------------------ */

    /**
     * Accept a drafted addition for one gap.
     *
     * Separate from /review/suggestions because a coverage gap proposes a
     * topic that does not exist yet, which that endpoint deliberately rejects.
     * Creating one here is still gated: the review must carry is_new_topic,
     * and the category must already exist.
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response|WP_Error
     */
    /**
     * The worker's report on a gap-drafting run. Drafts it withheld for want
     * of verification, and drafts that failed outright, are emailed to the
     * review address as a list to work on; nothing about them enters the
     * approval queue. The report is also kept for the admin screen.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function report_gap_run($request) {
        $deferred = array();
        foreach ((array) $request->get_param('deferred') as $d) {
            if (!is_array($d)) {
                continue;
            }
            $deferred[] = array(
                'gap_id'   => sanitize_text_field((string) ($d['gap_id'] ?? '')),
                'type'     => sanitize_text_field((string) ($d['type'] ?? '')),
                'question' => sanitize_text_field((string) ($d['question'] ?? '')),
                'topic'    => sanitize_text_field((string) ($d['topic'] ?? '')),
                'error'    => sanitize_text_field((string) ($d['error'] ?? '')),
                'errors'   => array_values(array_unique(array_map('sanitize_text_field', (array) ($d['web_search_errors'] ?? array())))),
                'sources'  => (int) ($d['web_sources'] ?? 0),
            );
        }
        $failed = array();
        foreach ((array) $request->get_param('failures') as $f) {
            if (is_array($f)) {
                $failed[] = array('gap_id' => sanitize_text_field((string) ($f['gap_id'] ?? '')), 'error' => sanitize_text_field((string) ($f['error'] ?? '')));
            }
        }
        $practice = array();
        foreach ((array) $request->get_param('practice_withheld') as $p) {
            if (is_array($p)) {
                $practice[] = array('gap_id' => sanitize_text_field((string) ($p['gap_id'] ?? '')), 'question' => sanitize_text_field((string) ($p['question'] ?? '')), 'reason' => sanitize_text_field((string) ($p['reason'] ?? '')));
            }
        }
        $report = array(
            'date'       => current_time('mysql'),
            'considered' => (int) $request->get_param('considered'),
            'drafted'    => (int) $request->get_param('drafted'),
            'deferred'   => $deferred,
            'failed'     => $failed,
            'practice_withheld' => $practice,
        );
        update_option('fra_gap_run_last', $report, false);

        if ($deferred || $failed || $practice) {
            $settings = get_option('fra_scheduled_review_settings', array());
            $email    = sanitize_email((string) ($settings['email_address'] ?? '')) ?: get_option('admin_email');
            $site     = get_bloginfo('name');
            $n        = count($deferred) + count($failed) + count($practice);
            $subject  = sprintf('[%s] %d knowledge-base draft%s need a hand', $site, $n, 1 === $n ? '' : 's');
            $lines    = array();
            $lines[]  = sprintf('The overnight gap run drafted %d of %d. These were not put in the review queue:', $report['drafted'], $report['considered']);
            $lines[]  = '';
            foreach ($deferred as $d) {
                $lines[] = sprintf('WITHHELD, unverified: %s', $d['question'] ?: ($d['topic'] ?: $d['gap_id']));
                $lines[] = sprintf('  %s', $d['error']);
                if ($d['errors']) {
                    $lines[] = sprintf('  Search errors: %s. Web results kept: %d.', implode(', ', $d['errors']), $d['sources']);
                }
                $lines[] = '  It will be tried again automatically (after a day, then weekly from the third try).';
                $lines[] = '';
            }
            foreach ($practice as $p) {
                $lines[] = sprintf('IN PRACTICE WITHHELD (official text queued): %s', $p['question'] ?: $p['gap_id']);
                $lines[] = sprintf('  %s', $p['reason']);
                $lines[] = '  The rule: two independent, dated community sources, or the section stays out.';
                $lines[] = '';
            }
            foreach ($failed as $f) {
                $lines[] = sprintf('FAILED: %s', $f['gap_id']);
                $lines[] = sprintf('  %s', $f['error']);
                $lines[] = '';
            }
            $lines[] = 'Gaps: ' . admin_url('admin.php?page=france-relocation-assistant-ai-review');
            wp_mail($email, $subject, implode("\n", $lines));
        }

        return rest_ensure_response(array('recorded' => 'report', 'deferred' => count($deferred), 'failed' => count($failed)));
    }

    public function create_gap_draft($request) {
        if (!class_exists('FRA_KB_Gaps')) {
            return new WP_Error('fra_gaps_unavailable', __('Gap detection is not available.', 'france-relocation-assistant'), array('status' => 503));
        }

        $rate = $this->check_rate_limit();
        if (is_wp_error($rate)) {
            return $rate;
        }

        $gap_id = sanitize_text_field((string) $request->get_param('id'));
        $gaps   = get_option(FRA_KB_Gaps::GAPS_OPTION, array());

        if (!isset($gaps[$gap_id])) {
            return new WP_Error('fra_gap_unknown', __('Unknown gap.', 'france-relocation-assistant'), array('status' => 404));
        }

        // The worker can report that it could not draft this one. Recorded
        // against the gap rather than thrown away, so a gap that keeps failing
        // is visible instead of looking untouched.
        $error = $request->get_param('error');
        if (!empty($error) && is_string($error)) {
            // A draft the worker could not verify is withheld, not queued: the
            // gap waits for a retry and the run report emails the reason.
            if ((bool) $request->get_param('deferred')) {
                FRA_KB_Gaps::mark_deferred($gap_id, $error, (array) $request->get_param('web_search_errors'), (int) $request->get_param('web_sources'));
                return new WP_REST_Response(array('gap_id' => $gap_id, 'recorded' => 'deferred'), 200);
            }
            FRA_KB_Gaps::mark_failed($gap_id, $error);
            return new WP_REST_Response(array('gap_id' => $gap_id, 'recorded' => 'error'), 200);
        }

        $suggested = $this->clean_content($request->get_param('suggested_content'));
        if ('' === $suggested) {
            return new WP_Error('fra_gap_empty', __('A draft must contain suggested_content.', 'france-relocation-assistant'), array('status' => 400));
        }

        $knowledge_base = get_option('fra_knowledge_base', array());
        $category       = sanitize_key((string) $request->get_param('category'));
        $topic          = sanitize_key((string) $request->get_param('topic'));

        if ('' === $category || '' === $topic || !isset($knowledge_base[$category])) {
            return new WP_Error(
                'fra_gap_bad_target',
                __('category must be an existing category, and topic is required.', 'france-relocation-assistant'),
                array('status' => 400)
            );
        }

        $is_new_topic = !isset($knowledge_base[$category][$topic]);

        $pending = get_option('fra_pending_reviews', array());
        if (!is_array($pending)) {
            $pending = array();
        }

        if (count($pending) >= self::MAX_PENDING) {
            return new WP_Error('fra_review_queue_full', __('The pending review queue is full.', 'france-relocation-assistant'), array('status' => 409));
        }

        $review_id = uniqid('gap_');

        $pending[$review_id] = array(
            'id'                  => $review_id,
            'category'            => $category,
            'topic'               => $topic,
            'topic_name'          => sanitize_text_field((string) $request->get_param('topic_name')) ?: $topic,
            'update_type'         => $this->clean_enum($request->get_param('update_type'), array('none', 'minor', 'significant', 'rewrite'), 'significant'),
            'confidence'          => $this->clean_enum($request->get_param('confidence'), array('high', 'medium', 'low'), 'medium'),
            'current_content'     => $is_new_topic ? '' : (string) ($knowledge_base[$category][$topic]['content'] ?? ''),
            'suggested_content'   => $suggested,
            'in_practice_content' => $this->clean_content($request->get_param('in_practice_content')),
            'changes_summary'     => sanitize_textarea_field((string) $request->get_param('changes_summary')),
            'practice_sources'    => $this->clean_practice_sources($request->get_param('practice_sources')),
            'practice_withheld'   => sanitize_text_field((string) $request->get_param('practice_withheld')),
            'practice_corroboration' => (int) $request->get_param('practice_corroboration'),
            'key_insights'        => $this->clean_string_list($request->get_param('key_insights')),
            'sources_checked'     => $this->clean_string_list($request->get_param('sources_checked')),
            'web_sources'         => $this->clean_web_sources($request->get_param('web_sources')),
            'model_used'          => sanitize_text_field((string) $request->get_param('model_used')),
            'source'              => 'gap-detection',
            'gap_id'              => $gap_id,
            'gap_type'            => $gaps[$gap_id]['type'],
            'gap_questions'       => array_values((array) $gaps[$gap_id]['questions']),
            'is_new_topic'        => $is_new_topic,
            'timestamp'           => current_time('mysql'),
        );

        update_option('fra_pending_reviews', $pending);
        update_option(self::LAST_CALL_OPTION, current_time('mysql'), false);
        FRA_KB_Gaps::mark_drafted($gap_id, $review_id);

        return new WP_REST_Response(array(
            'gap_id'       => $gap_id,
            'review_id'    => $review_id,
            'is_new_topic' => $is_new_topic,
            'pending'      => count($pending),
        ), 201);
    }

    /* ---------------------------------------------------------------------
     * Validation helpers
     * ------------------------------------------------------------------ */

    /**
     * Sanitize a content field, allowing safe formatting HTML only.
     *
     * @param mixed $value Raw value
     * @return string
     */
    private function clean_content($value) {
        if (!is_string($value)) {
            return '';
        }

        // Trim before sanitising, and character-aware: this content is French,
        // and a byte-wise substr() can split an accented character in half and
        // leave invalid UTF-8 in the option.
        $value = function_exists('mb_substr')
            ? mb_substr($value, 0, self::MAX_CONTENT, 'UTF-8')
            : substr($value, 0, self::MAX_CONTENT);

        return wp_kses_post($value);
    }

    /**
     * Constrain a value to a known set.
     *
     * @param mixed  $value    Raw value
     * @param array  $allowed  Permitted values
     * @param string $fallback Default when the value is not permitted
     * @return string
     */
    private function clean_enum($value, $allowed, $fallback) {
        $value = is_string($value) ? strtolower(trim($value)) : '';
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
     * Sanitize a flat list of strings.
     *
     * @param mixed $value Raw value
     * @return array
     */
    private function clean_string_list($value) {
        if (!is_array($value)) {
            return array();
        }

        $clean = array();
        foreach (array_slice($value, 0, 50) as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $item = sanitize_text_field((string) $item);
            if ('' !== $item) {
                $clean[] = $item;
            }
        }

        return $clean;
    }

    /**
     * Sanitize the practice_sources list.
     *
     * @param mixed $value Raw value
     * @return array
     */
    private function clean_practice_sources($value) {
        if (!is_array($value)) {
            return array();
        }

        $clean = array();
        foreach (array_slice($value, 0, 50) as $source) {
            if (is_string($source)) {
                // The gap drafter sends plain descriptions, dated inline.
                $source = array('name' => $source, 'type' => 'forum', 'date' => '');
            }
            if (!is_array($source)) {
                continue;
            }
            $name = sanitize_text_field((string) ($source['name'] ?? ''));
            if ('' === $name) {
                continue;
            }
            $clean[] = array(
                'name' => $name,
                'type' => $this->clean_enum($source['type'] ?? '', array('forum', 'blog', 'article', 'social', 'official'), 'article'),
                'date' => sanitize_text_field((string) ($source['date'] ?? '')),
            );
        }

        return $clean;
    }

    /**
     * Sanitize the web_sources list (pages the model actually fetched).
     *
     * @param mixed $value Raw value
     * @return array
     */
    private function clean_web_sources($value) {
        if (!is_array($value)) {
            return array();
        }

        $clean = array();
        foreach (array_slice($value, 0, 50) as $source) {
            if (!is_array($source)) {
                continue;
            }
            $url = esc_url_raw((string) ($source['url'] ?? ''), array('http', 'https'));
            if ('' === $url) {
                continue;
            }
            $clean[] = array(
                'url'   => $url,
                'title' => sanitize_text_field((string) ($source['title'] ?? $url)),
            );
        }

        return $clean;
    }

    /**
     * Take an advisory write lock.
     *
     * add_option() fails when the row already exists, which makes it a cheap
     * mutex. Advisory, not bulletproof - but it closes the window that matters
     * here, and a caller that loses gets a 409 it can retry.
     *
     * @return bool True if the lock was acquired
     */
    private function acquire_lock() {
        if (add_option(self::LOCK_OPTION, time(), '', false)) {
            return true;
        }

        // Reclaim a lock left behind by a request that died mid-write.
        $held = (int) get_option(self::LOCK_OPTION, 0);
        if (time() - $held > 30) {
            update_option(self::LOCK_OPTION, time(), false);
            return true;
        }

        return false;
    }

    /**
     * Release the advisory write lock.
     */
    private function release_lock() {
        delete_option(self::LOCK_OPTION);
    }

    /**
     * Simple hourly write cap.
     *
     * @return true|WP_Error
     */
    private function check_rate_limit() {
        $key   = self::RATE_TRANSIENT . '_' . gmdate('YmdH');
        $count = (int) get_transient($key);

        if ($count >= self::RATE_LIMIT) {
            return new WP_Error(
                'fra_review_rate_limited',
                __('Too many suggestions this hour.', 'france-relocation-assistant'),
                array('status' => 429)
            );
        }

        set_transient($key, $count + 1, 2 * HOUR_IN_SECONDS);

        return true;
    }
}

FRA_Review_API::get_instance();
