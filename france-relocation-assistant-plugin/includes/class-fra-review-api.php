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

        $pending = get_option('fra_pending_reviews', array());
        if (!is_array($pending)) {
            $pending = array();
        }

        if (count($pending) >= self::MAX_PENDING) {
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
            'key_insights'        => $this->clean_string_list($request->get_param('key_insights')),
            'sources_checked'     => $this->clean_string_list($request->get_param('sources_checked')),
            'web_sources'         => $this->clean_web_sources($request->get_param('web_sources')),
            'model_used'          => sanitize_text_field((string) $request->get_param('model_used')),
            'source'              => 'worker',
            'timestamp'           => current_time('mysql'),
        );

        update_option('fra_pending_reviews', $pending);
        update_option(self::LAST_CALL_OPTION, current_time('mysql'), false);

        return new WP_REST_Response(array(
            'review_id' => $review_id,
            'pending'   => count($pending),
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
        return substr(wp_kses_post($value), 0, self::MAX_CONTENT);
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
