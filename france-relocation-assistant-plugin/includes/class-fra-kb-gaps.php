<?php
/**
 * Knowledge Base Gap Detection
 *
 * Notices when the knowledge base failed to answer a member's question well,
 * and turns repeated failures into drafted additions awaiting approval.
 *
 * Two gap signatures, because they fail differently:
 *
 *   COVERAGE - nothing in the KB matched. Easy to spot: no results, or the
 *   best relevance is near the floor.
 *
 *   DEPTH - a topic matched *well*, but the answer still had to lean on web
 *   sources, which means the topic is missing something. This is the one that
 *   matters: when a member asked which documents a long-stay visa needs, the
 *   documents topic matched strongly and simply omitted the declaration of not
 *   seeking employment. A relevance-only detector sees a healthy match and
 *   notices nothing.
 *
 * Nothing here publishes. Gaps are drafted into fra_pending_reviews and wait
 * for a human, the same as every other suggestion. This is immigration
 * guidance that members act on irreversibly, and the chat's own prompt refuses
 * to invent requirements - auto-publishing would route around that rather than
 * reinforce it.
 *
 * @package France_Relocation_Assistant
 * @since 3.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_KB_Gaps {

    /** @var FRA_KB_Gaps|null Singleton instance */
    private static $instance = null;

    /** @var string Option holding detected gaps */
    const GAPS_OPTION = 'fra_kb_gaps';

    /** @var string Cron hook that drafts additions for ready gaps */
    const PROMOTE_HOOK = 'fra_promote_kb_gaps';

    /** @var int Most gaps to retain */
    const MAX_GAPS = 200;

    /** @var int Times a coverage gap must recur before it is drafted */
    const COVERAGE_THRESHOLD = 3;

    /** @var int Times a depth gap must recur before it is drafted */
    const DEPTH_THRESHOLD = 2;

    /**
     * A gap raised deliberately by an editor is ready on the first sighting.
     * Traffic thresholds exist to filter noise from one-off questions; a
     * person naming a missing subject is not noise.
     */
    const RAISED_THRESHOLD = 1;

    /** @var float Relevance below which the KB is considered not to have matched */
    const COVERAGE_CEILING = 0.35;

    /** @var float Relevance at or above which a match counts as a real hit */
    const DEPTH_FLOOR = 0.45;

    /** @var int Gaps drafted per cron run, to bound cost */
    const PROMOTE_PER_RUN = 3;

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
     * Constructor - register the promotion cron
     */
    private function __construct() {
        add_action(self::PROMOTE_HOOK, array(__CLASS__, 'promote_ready'));
        add_action('init', array(__CLASS__, 'maybe_schedule'));
    }

    /**
     * Schedule the daily promotion pass.
     */
    public static function maybe_schedule() {
        // Gap drafting is the same shape of job as the review - web search
        // plus a model call, minutes at a time - so it follows the same
        // ownership switch. WP-Cron cannot run this reliably.
        if (!self::wordpress_owns_promotion()) {
            self::unschedule();
            return;
        }

        if (!wp_next_scheduled(self::PROMOTE_HOOK)) {
            wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'daily', self::PROMOTE_HOOK);
        }
    }

    /**
     * Remove the scheduled pass (plugin deactivation).
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled(self::PROMOTE_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::PROMOTE_HOOK);
        }
    }

    /**
     * Is gap detection switched on?
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option('fra_kb_gaps_enabled', true);
    }

    /* ---------------------------------------------------------------------
     * Detection
     * ------------------------------------------------------------------ */

    /**
     * Record the outcome of one answered question.
     *
     * Called after the chat has replied. Cheap: no API call, just a
     * classification and an option write when something looks wrong.
     *
     * @param array $args {
     *     @type string $question   What the member asked
     *     @type array  $kb_results Results from search_knowledge_base(), sorted
     *     @type bool   $used_web   Whether web sources contributed to the answer
     *     @type string $answer     The answer that was served
     * }
     * @return string|false Gap type recorded, or false if the answer looked fine
     */
    public static function record($args) {
        if (!self::is_enabled()) {
            return false;
        }

        $question = trim((string) ($args['question'] ?? ''));
        if (strlen($question) < 8) {
            return false;
        }

        $kb_results = isset($args['kb_results']) && is_array($args['kb_results']) ? $args['kb_results'] : array();
        $used_web   = !empty($args['used_web']);
        $top        = isset($kb_results[0]) ? $kb_results[0] : null;
        $relevance  = $top && isset($top['relevance']) ? (float) $top['relevance'] : 0.0;

        $type    = '';
        $matched = array();

        if (!$top || $relevance < self::COVERAGE_CEILING) {
            // Nothing useful in the knowledge base.
            $type = 'coverage';
        } elseif ($relevance >= self::DEPTH_FLOOR && $used_web) {
            // A topic matched well, yet the answer still needed outside
            // sources - the topic is thin for this question.
            $type    = 'depth';
            $matched = array(
                'category' => isset($top['category']) ? $top['category'] : '',
                'topic'    => isset($top['topic_id']) ? $top['topic_id'] : '',
                'title'    => isset($top['title']) ? $top['title'] : '',
            );
        }

        if ('' === $type) {
            return false;
        }

        self::store($type, $question, $relevance, $matched, (string) ($args['answer'] ?? ''));

        return $type;
    }

    /**
     * Raise a gap deliberately, rather than inferring one from traffic.
     *
     * Used when a person notices the knowledge base is missing something -
     * a claim seen elsewhere, a subject a guide had to work around, a visa
     * category that is simply absent. It enters the same queue as a detected
     * gap and is drafted, reviewed and approved the same way: nothing here
     * publishes anything.
     *
     * @param string $question The subject to research, phrased as a question
     * @param string $note     Why it was raised - carried into the draft prompt
     * @param array  $matched  Optional category/topic it belongs under
     * @return string|false The gap id, or false if it was rejected
     */
    public static function raise($question, $note = '', $matched = array()) {
        if (!self::is_enabled()) {
            return false;
        }

        $question = trim((string) $question);
        if (strlen($question) < 8) {
            return false;
        }

        $matched = is_array($matched) ? $matched : array();

        self::store('raised', $question, 0.0, $matched, trim((string) $note));

        return self::signature('raised', $question, $matched);
    }

    /**
     * Add or increment a gap record.
     *
     * @param string $type      coverage|depth
     * @param string $question  The question asked
     * @param float  $relevance Best KB relevance for it
     * @param array  $matched   Matched topic, for depth gaps
     * @param string $answer    The served answer
     */
    private static function store($type, $question, $relevance, $matched, $answer) {
        $gaps = get_option(self::GAPS_OPTION, array());
        if (!is_array($gaps)) {
            $gaps = array();
        }

        $key = self::signature($type, $question, $matched);

        if (isset($gaps[$key])) {
            $gaps[$key]['count']++;
            $gaps[$key]['last_seen'] = current_time('mysql');
            // Keep a few phrasings - useful when drafting the addition.
            if (count($gaps[$key]['questions']) < 5
                && !in_array($question, $gaps[$key]['questions'], true)) {
                $gaps[$key]['questions'][] = $question;
            }
        } else {
            $gaps[$key] = array(
                'id'         => $key,
                'type'       => $type,
                'questions'  => array($question),
                'relevance'  => round($relevance, 3),
                'matched'    => $matched,
                'answer'     => self::excerpt($answer),
                'count'      => 1,
                'status'     => 'open',
                'first_seen' => current_time('mysql'),
                'last_seen'  => current_time('mysql'),
            );
        }

        $gaps = self::prune($gaps);

        update_option(self::GAPS_OPTION, $gaps, false);
    }

    /**
     * A stable key so re-phrasings of the same question aggregate.
     *
     * Lowercased, stripped of punctuation and common words, remaining words
     * sorted so word order does not matter.
     *
     * @param string $type     Gap type
     * @param string $question The question
     * @param array  $matched  Matched topic, for depth gaps
     * @return string
     */
    private static function signature($type, $question, $matched) {
        $stop = array(
            'the','a','an','and','or','but','if','of','to','in','for','on','at','by','with',
            'is','are','was','were','be','do','does','did','i','my','me','we','our','you','your',
            'what','which','how','when','where','who','why','can','could','should','would','will',
            'need','needs','about','from','this','that','there','any','all','get','got','have','has',
        );

        $text  = strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $question));
        $words = array_filter(preg_split('/\s+/', $text), function ($w) use ($stop) {
            return strlen($w) > 2 && !in_array($w, $stop, true);
        });
        // "register" and "registering" must land in the same bucket, or one
        // recurring question splits into several and never crosses a
        // threshold. Accuracy matters less than being consistent.
        $words = array_map(array(__CLASS__, 'stem'), $words);

        $words = array_unique($words);
        sort($words);

        // A depth gap belongs to its topic, so different questions exposing the
        // same thin topic aggregate together.
        $scope = ('depth' === $type && !empty($matched['topic']))
            ? $matched['category'] . '/' . $matched['topic']
            : implode(' ', array_slice($words, 0, 8));

        return $type . '_' . substr(md5($type . '|' . $scope), 0, 12);
    }

    /**
     * Crude suffix stripping, for grouping only.
     *
     * Not a real stemmer and does not need to be - it exists so that
     * rephrasings of one question hash to one key. "addres" would be a poor
     * dictionary entry but is a perfectly good bucket label.
     *
     * @param string $word Lowercased word
     * @return string
     */
    private static function stem($word) {
        foreach (array('ing', 'ies', 'ed', 'es', 's') as $suffix) {
            $len = strlen($suffix);
            if (strlen($word) > $len + 2 && substr($word, -$len) === $suffix) {
                $root = substr($word, 0, -$len);
                return ('ies' === $suffix) ? $root . 'y' : $root;
            }
        }
        return $word;
    }

    /**
     * Keep the option bounded: drop resolved and least-seen records first.
     *
     * @param array $gaps All gaps
     * @return array
     */
    private static function prune($gaps) {
        if (count($gaps) <= self::MAX_GAPS) {
            return $gaps;
        }

        uasort($gaps, function ($a, $b) {
            $a_done = in_array($a['status'], array('drafted', 'dismissed'), true) ? 1 : 0;
            $b_done = in_array($b['status'], array('drafted', 'dismissed'), true) ? 1 : 0;
            if ($a_done !== $b_done) {
                return $a_done - $b_done;
            }
            if ($a['count'] !== $b['count']) {
                return $b['count'] - $a['count'];
            }
            return strcmp($b['last_seen'], $a['last_seen']);
        });

        return array_slice($gaps, 0, self::MAX_GAPS, true);
    }

    /**
     * Trim an answer down to something worth storing.
     *
     * @param string $answer Full answer
     * @return string
     */
    private static function excerpt($answer) {
        $answer = trim(wp_strip_all_tags($answer));
        return function_exists('mb_substr')
            ? mb_substr($answer, 0, 1500, 'UTF-8')
            : substr($answer, 0, 1500);
    }

    /* ---------------------------------------------------------------------
     * Reading
     * ------------------------------------------------------------------ */

    /**
     * All recorded gaps, most-seen first.
     *
     * @param string $status Filter by status, or '' for all
     * @return array
     */
    public static function get_all($status = '') {
        $gaps = get_option(self::GAPS_OPTION, array());
        if (!is_array($gaps)) {
            return array();
        }

        if ('' !== $status) {
            $gaps = array_filter($gaps, function ($gap) use ($status) {
                return isset($gap['status']) && $status === $gap['status'];
            });
        }

        uasort($gaps, function ($a, $b) {
            if ($a['count'] !== $b['count']) {
                return $b['count'] - $a['count'];
            }
            return strcmp($b['last_seen'], $a['last_seen']);
        });

        return $gaps;
    }

    /**
     * Mark a gap dismissed so it stops being drafted.
     *
     * @param string $id Gap id
     * @return bool
     */
    public static function dismiss($id) {
        $gaps = get_option(self::GAPS_OPTION, array());
        if (!isset($gaps[$id])) {
            return false;
        }
        $gaps[$id]['status'] = 'dismissed';
        update_option(self::GAPS_OPTION, $gaps, false);
        return true;
    }

    /**
     * Does WordPress still draft gap additions itself?
     *
     * Shares the review's runner setting - it makes no sense for one AI job to
     * be on the worker and the other on WP-Cron.
     *
     * @return bool
     */
    public static function wordpress_owns_promotion() {
        if (!class_exists('FRA_Scheduled_Review')) {
            return true;
        }
        return FRA_Scheduled_Review::wordpress_owns_review();
    }

    /**
     * Record that a gap has been drafted into the approval queue.
     *
     * @param string $id        Gap id
     * @param string $review_id The pending review it produced
     * @return bool
     */
    public static function mark_drafted($id, $review_id) {
        $gaps = get_option(self::GAPS_OPTION, array());
        if (!isset($gaps[$id])) {
            return false;
        }

        $gaps[$id]['status']    = 'drafted';
        $gaps[$id]['review_id'] = $review_id;
        update_option(self::GAPS_OPTION, $gaps, false);

        return true;
    }

    /**
     * Record that drafting a gap failed, without consuming it.
     *
     * @param string $id      Gap id
     * @param string $message Why
     * @return bool
     */
    public static function mark_failed($id, $message) {
        $gaps = get_option(self::GAPS_OPTION, array());
        if (!isset($gaps[$id])) {
            return false;
        }

        $gaps[$id]['last_error'] = substr((string) $message, 0, 300);
        update_option(self::GAPS_OPTION, $gaps, false);

        return true;
    }

    /**
     * Which gaps have recurred often enough to be worth drafting?
     *
     * @return array
     */
    public static function get_ready() {
        $ready = array();

        foreach (self::get_all('open') as $id => $gap) {
            if ('raised' === $gap['type']) {
                $threshold = self::RAISED_THRESHOLD;
            } elseif ('depth' === $gap['type']) {
                $threshold = self::DEPTH_THRESHOLD;
            } else {
                $threshold = self::COVERAGE_THRESHOLD;
            }
            if ($gap['count'] >= $threshold) {
                $ready[$id] = $gap;
            }
        }

        return $ready;
    }

    /* ---------------------------------------------------------------------
     * Promotion
     * ------------------------------------------------------------------ */

    /**
     * Draft knowledge base additions for gaps that have recurred enough.
     *
     * Runs daily. Bounded to PROMOTE_PER_RUN so a bad day of traffic cannot
     * turn into an expensive research run. Drafts land in fra_pending_reviews
     * for approval; nothing is published here.
     */
    public static function promote_ready() {
        if (!self::is_enabled() || !class_exists('FRA_Model_Resolver')) {
            return;
        }

        if (!self::wordpress_owns_promotion()) {
            error_log('FRA: gap promotion skipped - the Cloudflare worker owns this job');
            return;
        }

        $ready = self::get_ready();
        if (empty($ready)) {
            return;
        }

        $gaps      = get_option(self::GAPS_OPTION, array());
        $pending   = get_option('fra_pending_reviews', array());
        $processed = 0;

        if (!is_array($pending)) {
            $pending = array();
        }

        foreach ($ready as $id => $gap) {
            if ($processed >= self::PROMOTE_PER_RUN) {
                break;
            }
            $processed++;

            $draft = self::draft_addition($gap);

            if (is_wp_error($draft)) {
                $gaps[$id]['status']     = 'open';
                $gaps[$id]['last_error'] = $draft->get_error_message();
                continue;
            }

            $review_id = uniqid('gap_');

            $pending[$review_id] = array(
                'id'                  => $review_id,
                'category'            => $draft['category'],
                'topic'               => $draft['topic'],
                'topic_name'          => $draft['topic_name'],
                'update_type'         => ('depth' === $gap['type']) ? 'minor' : 'significant',
                'confidence'          => $draft['confidence'],
                'current_content'     => $draft['current_content'],
                'suggested_content'   => $draft['suggested_content'],
                'in_practice_content' => '',
                'changes_summary'     => $draft['changes_summary'],
                'practice_sources'    => array(),
                'key_insights'        => $draft['key_insights'],
                'sources_checked'     => $draft['sources_checked'],
                'web_sources'         => $draft['web_sources'],
                'model_used'          => $draft['model_used'],
                'source'              => 'gap-detection',
                'gap_id'              => $id,
                'gap_type'            => $gap['type'],
                'gap_questions'       => $gap['questions'],
                'is_new_topic'        => $draft['is_new_topic'],
                'timestamp'           => current_time('mysql'),
            );

            $gaps[$id]['status']    = 'drafted';
            $gaps[$id]['review_id'] = $review_id;
        }

        update_option('fra_pending_reviews', $pending);
        update_option(self::GAPS_OPTION, $gaps, false);
    }

    /**
     * Research and write a proposed addition for one gap.
     *
     * @param array $gap Gap record
     * @return array|WP_Error
     */
    private static function draft_addition($gap) {
        $questions = implode("\n- ", $gap['questions']);
        $is_depth  = ('depth' === $gap['type']);

        if ($is_depth) {
            $knowledge_base  = get_option('fra_knowledge_base', array());
            $category        = $gap['matched']['category'];
            $topic           = $gap['matched']['topic'];
            $current_content = isset($knowledge_base[$category][$topic]['content'])
                ? $knowledge_base[$category][$topic]['content']
                : '';

            if ('' === $current_content) {
                return new WP_Error('missing_topic', 'The matched topic no longer exists.');
            }

            $prompt = "You maintain a knowledge base for Americans relocating to France.\n\n"
                . "Members asked these questions:\n- " . $questions . "\n\n"
                . "Our existing topic \"" . $gap['matched']['title'] . "\" matched their question, "
                . "but the answer still had to be drawn from outside sources - so this topic is "
                . "missing something it should cover.\n\n"
                . "CURRENT TOPIC CONTENT:\n```\n" . $current_content . "\n```\n\n"
                . "Research the current official position using web search, then rewrite this "
                . "topic so it answers those questions completely.\n\n"
                . "RULES:\n"
                . "- Keep everything already correct. This is an edit, not a replacement.\n"
                . "- Only state requirements you can confirm from an official source.\n"
                . "- Prefer service-public.fr, france-visas.gouv.fr and consulate sites.\n"
                . "- Note where requirements vary by consulate rather than picking one.\n"
                . "- Match the existing formatting: ** for headers, bullets for lists.\n\n"
                . "Respond with ONLY this JSON:\n"
                . '{"suggested_content": "the full updated topic text", '
                . '"changes_summary": "one sentence on what was missing", '
                . '"key_insights": ["what was added"], '
                . '"official_sources_checked": ["service-public.fr"], '
                . '"confidence": "high|medium|low"}';
        } else {
            $prompt = "You maintain a knowledge base for Americans relocating to France.\n\n"
                . "Members asked these questions and we have no topic covering them:\n- "
                . $questions . "\n\n"
                . "Research the current official position using web search, then write a new "
                . "knowledge base topic that answers them.\n\n"
                . "RULES:\n"
                . "- Only state requirements you can confirm from an official source.\n"
                . "- Prefer service-public.fr, france-visas.gouv.fr and consulate sites.\n"
                . "- Note where requirements vary by consulate rather than picking one.\n"
                . "- Write for Americans applying from the United States.\n"
                . "- 300-500 words. Use ** for headers and bullets for lists.\n"
                . "- suggested_topic_key must be lowercase with underscores.\n\n"
                . "Existing categories: " . implode(', ', array_keys(get_option('fra_knowledge_base', array()))) . "\n\n"
                . "Respond with ONLY this JSON:\n"
                . '{"suggested_category": "one of the existing categories", '
                . '"suggested_topic_key": "short_key", "suggested_title": "Topic Title", '
                . '"suggested_content": "the topic text", '
                . '"changes_summary": "one sentence on what this covers", '
                . '"key_insights": ["what it answers"], '
                . '"official_sources_checked": ["service-public.fr"], '
                . '"confidence": "high|medium|low"}';
        }

        $body = FRA_Model_Resolver::message(array(
            'purpose'    => 'review',
            'max_tokens' => 6000,
            'timeout'    => 300,
            'tools'      => array(FRA_Model_Resolver::web_search_tool(5)),
            'messages'   => array(array('role' => 'user', 'content' => $prompt)),
        ));

        if (is_wp_error($body)) {
            return $body;
        }

        $result = FRA_Model_Resolver::extract_json($body);

        if (!is_array($result) || empty($result['suggested_content'])) {
            return new WP_Error(
                'draft_failed',
                'Could not draft an addition' . FRA_Model_Resolver::parse_failure_reason($body)
            );
        }

        $common = array(
            'suggested_content' => (string) $result['suggested_content'],
            'changes_summary'   => (string) ($result['changes_summary'] ?? ''),
            'key_insights'      => isset($result['key_insights']) ? (array) $result['key_insights'] : array(),
            'sources_checked'   => isset($result['official_sources_checked']) ? (array) $result['official_sources_checked'] : array(),
            'web_sources'       => FRA_Model_Resolver::extract_sources($body),
            'confidence'        => (string) ($result['confidence'] ?? 'medium'),
            'model_used'        => isset($body['fra_model']) ? $body['fra_model'] : '',
        );

        if ($is_depth) {
            return array_merge($common, array(
                'category'        => $gap['matched']['category'],
                'topic'           => $gap['matched']['topic'],
                'topic_name'      => $gap['matched']['title'],
                'current_content' => $current_content,
                'is_new_topic'    => false,
            ));
        }

        $knowledge_base = get_option('fra_knowledge_base', array());
        $category       = sanitize_key((string) ($result['suggested_category'] ?? ''));

        // Never invent a category - a new topic goes into an existing one.
        if ('' === $category || !isset($knowledge_base[$category])) {
            $categories = array_keys($knowledge_base);
            if (empty($categories)) {
                return new WP_Error('no_categories', 'The knowledge base has no categories.');
            }
            $category = $categories[0];
        }

        $topic_key = sanitize_key((string) ($result['suggested_topic_key'] ?? ''));
        if ('' === $topic_key) {
            $topic_key = 'gap_' . substr($gap['id'], -8);
        }

        return array_merge($common, array(
            'category'        => $category,
            'topic'           => $topic_key,
            'topic_name'      => (string) ($result['suggested_title'] ?? ucfirst(str_replace('_', ' ', $topic_key))),
            'current_content' => '',
            'is_new_topic'    => !isset($knowledge_base[$category][$topic_key]),
        ));
    }
}

FRA_KB_Gaps::get_instance();
