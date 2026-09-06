<?php
/**
 * Anthropic Model Resolver
 *
 * Keeps the site off hardcoded Claude model IDs. Instead of pinning a string
 * like "claude-sonnet-4-20250514" (which stops working the moment Anthropic
 * retires it), every AI feature asks for a *tier* - opus, sonnet or haiku -
 * and this class resolves that to whatever model is actually live on the
 * account right now, using the Anthropic Models API (GET /v1/models).
 *
 * That means:
 *   - A newly released model is adopted automatically (newest in tier wins).
 *   - A retired model disappears from the catalog and is never sent again.
 *   - If a retired model is still cached and the API rejects it mid-request,
 *     the catalog is force-refreshed and the request is retried once.
 *
 * @package France_Relocation_Assistant
 * @since 3.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Model_Resolver {

    /** @var FRA_Model_Resolver|null Singleton instance */
    private static $instance = null;

    /** @var string Models API endpoint */
    const MODELS_ENDPOINT = 'https://api.anthropic.com/v1/models';

    /** @var string Messages API endpoint */
    const MESSAGES_ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /** @var string Anthropic API version header */
    const API_VERSION = '2023-06-01';

    /** @var string Transient holding the live model catalog */
    const CATALOG_TRANSIENT = 'fra_model_catalog';

    /** @var string Option holding the last known-good catalog (survives transient eviction) */
    const CATALOG_OPTION = 'fra_model_catalog_backup';

    /** @var string Option holding the last catalog fetch error */
    const ERROR_OPTION = 'fra_model_catalog_error';

    /** @var string Transient that suppresses catalog refetching after a failure */
    const BACKOFF_TRANSIENT = 'fra_model_catalog_backoff';

    /** @var int How long to wait before retrying a failed catalog fetch */
    const BACKOFF_TTL = 900;

    /** @var string Option recording models that vanished from the catalog */
    const RETIRED_OPTION = 'fra_model_retired_notice';

    /** @var string Daily cron hook that refreshes the catalog */
    const REFRESH_HOOK = 'fra_refresh_model_catalog';

    /** @var int How long the catalog stays hot before a background refresh */
    const CACHE_TTL = DAY_IN_SECONDS;

    /**
     * Last-resort model IDs.
     *
     * Only used when the Models API is unreachable AND no catalog has ever
     * been cached (e.g. a brand new install with a bad key). Everything else
     * resolves from live data.
     *
     * @var array
     */
    private static $emergency_models = array(
        'opus'   => 'claude-opus-5',
        'sonnet' => 'claude-sonnet-5',
        'haiku'  => 'claude-haiku-4-5',
    );

    /**
     * Where to look next when a tier has no live models at all.
     *
     * @var array
     */
    private static $tier_fallbacks = array(
        'opus'   => array('opus', 'sonnet', 'haiku'),
        'sonnet' => array('sonnet', 'opus', 'haiku'),
        'haiku'  => array('haiku', 'sonnet', 'opus'),
        'fable'  => array('fable', 'opus', 'sonnet'),
        'mythos' => array('mythos', 'opus', 'sonnet'),
    );

    /**
     * Default tier per purpose.
     *
     * chat   - high volume, user facing (kept on the tier the site already ran)
     * review - weekly law/policy review, AI content review (accuracy matters most)
     * docs   - guide generation, document verification, KB expansion
     *
     * @var array
     */
    private static $purpose_defaults = array(
        'chat'   => 'sonnet',
        'review' => 'opus',
        'docs'   => 'sonnet',
    );

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
     * Constructor - register the refresh cron
     */
    private function __construct() {
        add_action(self::REFRESH_HOOK, array(__CLASS__, 'refresh_catalog'));
        add_action('init', array(__CLASS__, 'maybe_schedule_refresh'));
        add_action('admin_notices', array(__CLASS__, 'render_retirement_notice'));
    }

    /**
     * Make sure the daily catalog refresh is scheduled.
     *
     * A daily refresh is what picks up newly released and newly retired
     * models without waiting for a request to fail.
     */
    public static function maybe_schedule_refresh() {
        if (!wp_next_scheduled(self::REFRESH_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::REFRESH_HOOK);
        }
    }

    /**
     * Clear the scheduled refresh (called on plugin deactivation).
     */
    public static function unschedule_refresh() {
        $timestamp = wp_next_scheduled(self::REFRESH_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::REFRESH_HOOK);
        }
    }

    /* ---------------------------------------------------------------------
     * Catalog
     * ------------------------------------------------------------------ */

    /**
     * Fetch the live model list from the Anthropic Models API.
     *
     * Paginates through all results. On success the catalog is cached in both
     * a transient (hot path) and an option (last known good). On failure the
     * previous catalog is left intact so an API blip never takes AI offline.
     *
     * @return array|WP_Error Catalog array on success
     */
    public static function refresh_catalog() {
        $api_key = self::get_api_key();

        if (empty($api_key)) {
            self::record_failure('Anthropic API key is not configured.');
            return new WP_Error('no_api_key', 'Anthropic API key is not configured.');
        }

        $models   = array();
        $after_id = null;
        $pages    = 0;
        $has_more = false;

        do {
            $url = add_query_arg('limit', 100, self::MODELS_ENDPOINT);
            if ($after_id) {
                $url = add_query_arg('after_id', $after_id, $url);
            }

            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => self::API_VERSION,
                ),
            ));

            if (is_wp_error($response)) {
                self::record_failure($response->get_error_message());
                return $response;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                $message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown Models API error';
                self::record_failure($message);
                return new WP_Error('models_api_error', $message);
            }

            if (empty($body['data']) || !is_array($body['data'])) {
                break;
            }

            foreach ($body['data'] as $model) {
                if (empty($model['id'])) {
                    continue;
                }
                $models[] = array(
                    'id'           => $model['id'],
                    'display_name' => isset($model['display_name']) ? $model['display_name'] : $model['id'],
                    'created_at'   => isset($model['created_at']) ? $model['created_at'] : '',
                    'tier'         => self::tier_of($model['id']),
                );
            }

            $has_more = !empty($body['has_more']);
            $after_id = isset($body['last_id']) ? $body['last_id'] : null;
            $pages++;
        } while ($has_more && $after_id && $pages < 10);

        if (empty($models)) {
            self::record_failure('The Models API returned no models.');
            return new WP_Error('empty_catalog', 'The Models API returned no models.');
        }

        // Newest first - this is what makes new releases get picked up.
        usort($models, array(__CLASS__, 'compare_by_created_desc'));

        $catalog = array(
            'models'     => $models,
            'fetched_at' => time(),
        );

        self::note_retirements($catalog);

        set_transient(self::CATALOG_TRANSIENT, $catalog, self::CACHE_TTL);
        update_option(self::CATALOG_OPTION, $catalog, false);
        delete_option(self::ERROR_OPTION);
        delete_transient(self::BACKOFF_TRANSIENT);

        return $catalog;
    }

    /**
     * Record a catalog fetch failure and start the retry backoff.
     *
     * Without this, a down or misconfigured API would mean every single page
     * load re-attempts the fetch and waits on the HTTP timeout.
     *
     * @param string $message Failure message
     */
    private static function record_failure($message) {
        update_option(self::ERROR_OPTION, array(
            'message' => $message,
            'time'    => time(),
        ), false);
        set_transient(self::BACKOFF_TRANSIENT, 1, self::BACKOFF_TTL);
    }

    /**
     * Sort comparator: newest created_at first.
     */
    private static function compare_by_created_desc($a, $b) {
        $a_time = empty($a['created_at']) ? 0 : strtotime($a['created_at']);
        $b_time = empty($b['created_at']) ? 0 : strtotime($b['created_at']);

        if ($a_time === $b_time) {
            return strcmp($b['id'], $a['id']);
        }
        return ($b_time < $a_time) ? -1 : 1;
    }

    /**
     * Get the catalog, refreshing if the cache has expired.
     *
     * @param bool $force Force a live refresh
     * @return array Catalog (may be a stale backup if the API is down)
     */
    public static function get_catalog($force = false) {
        if (!$force) {
            $cached = get_transient(self::CATALOG_TRANSIENT);
            if (!empty($cached['models'])) {
                return $cached;
            }
        }

        // A recent failure is still in its backoff window - don't re-attempt
        // the fetch (and block the request) on every page load. A forced
        // refresh bypasses this: it is the recovery path after a request has
        // already failed on a retired model.
        if ($force || !get_transient(self::BACKOFF_TRANSIENT)) {
            $fresh = self::refresh_catalog();
            if (!is_wp_error($fresh)) {
                return $fresh;
            }
        }

        // API unavailable - fall back to the last known-good catalog.
        $backup = get_option(self::CATALOG_OPTION, array());
        if (!empty($backup['models'])) {
            return $backup;
        }

        return array('models' => array(), 'fetched_at' => 0);
    }

    /**
     * Record any model IDs the site is configured to use that have vanished
     * from the catalog, so an admin notice can explain the automatic switch.
     *
     * @param array $catalog Newly fetched catalog
     */
    private static function note_retirements($catalog) {
        $pinned = get_option('fra_api_model', '');

        if (empty($pinned) || 'auto' === $pinned) {
            delete_option(self::RETIRED_OPTION);
            return;
        }

        $live_ids = wp_list_pluck($catalog['models'], 'id');

        if (in_array($pinned, $live_ids, true)) {
            delete_option(self::RETIRED_OPTION);
            return;
        }

        update_option(self::RETIRED_OPTION, array(
            'model' => $pinned,
            'time'  => time(),
        ), false);
    }

    /**
     * Admin notice shown when a pinned model has been retired.
     */
    public static function render_retirement_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notice = get_option(self::RETIRED_OPTION, array());
        if (empty($notice['model'])) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
            esc_html__('France Relocation Assistant:', 'france-relocation-assistant'),
            sprintf(
                /* translators: 1: retired model ID, 2: replacement model ID */
                esc_html__('The Claude model "%1$s" is no longer available on your Anthropic account. Requests are automatically using "%2$s" instead. Set the model to "Automatic" in settings to stop seeing this notice.', 'france-relocation-assistant'),
                esc_html($notice['model']),
                esc_html(self::resolve(self::tier_of($notice['model'])))
            )
        );
    }

    /* ---------------------------------------------------------------------
     * Resolution
     * ------------------------------------------------------------------ */

    /**
     * Work out which tier a model ID belongs to.
     *
     * Matches the tier keyword anywhere in the ID so it handles both the
     * current naming (claude-opus-5) and the legacy naming
     * (claude-3-5-sonnet-20241022).
     *
     * @param string $model_id Model identifier
     * @return string Tier name, or 'unknown'
     */
    public static function tier_of($model_id) {
        foreach (array('fable', 'mythos', 'opus', 'sonnet', 'haiku') as $tier) {
            if (false !== stripos($model_id, $tier)) {
                return $tier;
            }
        }
        return 'unknown';
    }

    /**
     * Resolve a tier to the newest live model ID in that tier.
     *
     * @param string $tier  Desired tier (opus|sonnet|haiku|fable|mythos)
     * @param bool   $force Force a catalog refresh first
     * @return string Model ID
     */
    public static function resolve($tier = 'sonnet', $force = false) {
        $tier    = strtolower($tier);
        $catalog = self::get_catalog($force);

        $order = isset(self::$tier_fallbacks[$tier])
            ? self::$tier_fallbacks[$tier]
            : array($tier, 'sonnet', 'opus', 'haiku');

        $excluded = apply_filters('fra_model_excluded_keywords', array('preview'));

        foreach ($order as $candidate_tier) {
            foreach ($catalog['models'] as $model) {
                if ($model['tier'] !== $candidate_tier) {
                    continue;
                }
                foreach ($excluded as $keyword) {
                    if ('' !== $keyword && false !== stripos($model['id'], $keyword)) {
                        continue 2;
                    }
                }
                // Catalog is sorted newest first, so the first match is the newest.
                return $model['id'];
            }
        }

        // No catalog at all - use the emergency default for the requested tier.
        if (isset(self::$emergency_models[$tier])) {
            return self::$emergency_models[$tier];
        }
        return self::$emergency_models['sonnet'];
    }

    /**
     * Resolve the model to use for a given purpose.
     *
     * Honours an explicit pin in fra_api_model, but only if that model is
     * still live. A retired pin silently falls back to tier resolution.
     *
     * @param string $purpose chat|review|docs
     * @return string Model ID
     */
    public static function for_purpose($purpose = 'chat') {
        $default_tier = isset(self::$purpose_defaults[$purpose])
            ? self::$purpose_defaults[$purpose]
            : 'sonnet';

        $tier = get_option('fra_model_tier_' . $purpose, $default_tier);

        // An explicit pin only applies to chat, and only while it is still live.
        if ('chat' === $purpose) {
            $pinned = get_option('fra_api_model', '');
            if (!empty($pinned) && 'auto' !== $pinned) {
                if (self::is_live($pinned)) {
                    return $pinned;
                }
                $tier = self::tier_of($pinned);
            }
        }

        return self::resolve($tier);
    }

    /**
     * Is this model ID present in the live catalog?
     *
     * @param string $model_id Model identifier
     * @return bool
     */
    public static function is_live($model_id) {
        $catalog = self::get_catalog();

        if (empty($catalog['models'])) {
            // No catalog to check against - don't block the request.
            return true;
        }

        return in_array($model_id, wp_list_pluck($catalog['models'], 'id'), true);
    }

    /**
     * Model choices for the settings dropdown, grouped by tier.
     *
     * @return array tier => array of ['id' => ..., 'label' => ...]
     */
    public static function get_choices() {
        $catalog = self::get_catalog();
        $grouped = array();

        foreach ($catalog['models'] as $model) {
            $grouped[$model['tier']][] = array(
                'id'    => $model['id'],
                'label' => $model['display_name'],
            );
        }

        return $grouped;
    }

    /* ---------------------------------------------------------------------
     * Requests
     * ------------------------------------------------------------------ */

    /**
     * Get the Anthropic API key.
     *
     * @return string
     */
    private static function get_api_key() {
        if (class_exists('France_Relocation_Assistant')
            && method_exists('France_Relocation_Assistant', 'get_api_key')) {
            return France_Relocation_Assistant::get_api_key();
        }
        return (string) get_option('fra_api_key', '');
    }

    /**
     * Send a Messages API request with automatic model recovery.
     *
     * The request body is deliberately minimal (model, max_tokens, system,
     * messages, tools). Parameters like `thinking`, `output_config.effort` and
     * `budget_tokens` are model-generation specific and would start returning
     * 400s the moment the resolver picks a model that doesn't accept them, so
     * they are left off - each model's own defaults apply.
     *
     * Recovery ladder, in order:
     *   1. Unknown/retired model  -> refresh catalog, re-resolve, retry once.
     *   2. Unsupported tool type  -> retry with the older web search variant.
     *   3. stop_reason pause_turn -> continue the turn (server tool paging).
     *
     * @param array $args {
     *     @type string $purpose    chat|review|docs (ignored if $model given)
     *     @type string $model      Explicit model ID
     *     @type string $system     System prompt
     *     @type array  $messages   Messages array
     *     @type int    $max_tokens Max output tokens
     *     @type array  $tools      Tool definitions
     *     @type int    $timeout    HTTP timeout in seconds
     * }
     * @return array|WP_Error Decoded response body, with 'fra_model' added
     */
    public static function message($args) {
        $args = wp_parse_args($args, array(
            'purpose'    => 'chat',
            'model'      => '',
            'system'     => '',
            'messages'   => array(),
            'max_tokens' => 4096,
            'tools'      => array(),
            'timeout'    => 120,
        ));

        $api_key = self::get_api_key();
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Anthropic API key is not configured.');
        }

        $model = !empty($args['model']) ? $args['model'] : self::for_purpose($args['purpose']);
        $tools = $args['tools'];

        $model_retried = false;
        $tools_retried = false;
        $messages      = $args['messages'];
        $collected     = array();
        $turns         = 0;

        while ($turns < 6) {
            $turns++;

            $payload = array(
                'model'      => $model,
                'max_tokens' => (int) $args['max_tokens'],
                'messages'   => $messages,
            );

            if (!empty($args['system'])) {
                $payload['system'] = $args['system'];
            }
            if (!empty($tools)) {
                $payload['tools'] = $tools;
            }

            $response = wp_remote_post(self::MESSAGES_ENDPOINT, array(
                'timeout' => (int) $args['timeout'],
                'headers' => array(
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $api_key,
                    'anthropic-version' => self::API_VERSION,
                ),
                'body'    => wp_json_encode($payload),
            ));

            if (is_wp_error($response)) {
                return $response;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                $message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown API error';
                $type    = isset($body['error']['type']) ? $body['error']['type'] : '';

                // 1. The model is gone. Refresh the catalog and try the replacement.
                if (!$model_retried && self::is_model_error($type, $message)) {
                    $model_retried = true;
                    $replacement   = self::resolve(self::tier_of($model), true);

                    if ($replacement !== $model) {
                        $model = $replacement;
                        continue;
                    }
                }

                // 2. This model is too old for the current web search tool.
                if (!$tools_retried && !empty($tools) && self::is_tool_error($message)) {
                    $tools_retried = true;
                    $downgraded    = self::downgrade_tools($tools);

                    if ($downgraded !== $tools) {
                        $tools = $downgraded;
                        continue;
                    }
                }

                return new WP_Error('api_error', $message);
            }

            if (empty($body['content']) || !is_array($body['content'])) {
                return new WP_Error('api_error', 'Unexpected API response format');
            }

            $collected = array_merge($collected, $body['content']);

            $stop_reason = isset($body['stop_reason']) ? $body['stop_reason'] : '';

            // Safety classifiers declined the request (HTTP 200, not an error).
            if ('refusal' === $stop_reason) {
                return new WP_Error(
                    'api_refusal',
                    'The model declined to answer this request.'
                );
            }

            // Server-side tools paused the turn - send it straight back to continue.
            if ('pause_turn' === $stop_reason) {
                $messages[] = array('role' => 'assistant', 'content' => $body['content']);
                continue;
            }

            $body['content']   = $collected;
            $body['fra_model'] = $model;
            return $body;
        }

        return new WP_Error('api_error', 'The model did not finish its response.');
    }

    /**
     * Does this API error mean the model ID is unknown or retired?
     *
     * @param string $type    Error type
     * @param string $message Error message
     * @return bool
     */
    private static function is_model_error($type, $message) {
        if ('not_found_error' === $type) {
            return true;
        }
        return (false !== stripos($message, 'model'))
            && (false !== stripos($message, 'not found')
                || false !== stripos($message, 'does not exist')
                || false !== stripos($message, 'deprecated')
                || false !== stripos($message, 'retired')
                || false !== stripos($message, 'invalid'));
    }

    /**
     * Does this API error mean the tool type is unsupported on this model?
     *
     * @param string $message Error message
     * @return bool
     */
    private static function is_tool_error($message) {
        return (false !== stripos($message, 'tool'))
            && (false !== stripos($message, 'not supported')
                || false !== stripos($message, 'unsupported')
                || false !== stripos($message, 'invalid'));
    }

    /**
     * Swap current-generation server tools for their older variants.
     *
     * @param array $tools Tool definitions
     * @return array
     */
    private static function downgrade_tools($tools) {
        $map = array(
            'web_search_20260209' => 'web_search_20250305',
            'web_fetch_20260209'  => 'web_fetch_20250910',
        );

        foreach ($tools as $i => $tool) {
            if (isset($tool['type']) && isset($map[$tool['type']])) {
                $tools[$i]['type'] = $map[$tool['type']];
            }
        }

        return $tools;
    }

    /**
     * Web search tool definition.
     *
     * Uses the current dynamic-filtering variant. If the resolved model is too
     * old for it, message() automatically retries with the basic variant.
     *
     * @param int   $max_uses Maximum searches per request
     * @param array $allowed_domains Optional domain allowlist
     * @return array Tool definition
     */
    public static function web_search_tool($max_uses = 8, $allowed_domains = array()) {
        $tool = array(
            'type'     => 'web_search_20260209',
            'name'     => 'web_search',
            'max_uses' => (int) $max_uses,
        );

        if (!empty($allowed_domains)) {
            $tool['allowed_domains'] = array_values($allowed_domains);
        }

        return $tool;
    }

    /**
     * Concatenate every text block in a response.
     *
     * Responses that use server-side tools contain server_tool_use and
     * web_search_tool_result blocks alongside the text, so reading
     * content[0]['text'] is not safe.
     *
     * @param array $body Decoded response body
     * @return string
     */
    public static function extract_text($body) {
        if (empty($body['content']) || !is_array($body['content'])) {
            return '';
        }

        $parts = array();
        foreach ($body['content'] as $block) {
            if (isset($block['type']) && 'text' === $block['type'] && isset($block['text'])) {
                $parts[] = $block['text'];
            }
        }

        return implode('', $parts);
    }

    /**
     * Collect the URLs the model actually visited via web search.
     *
     * @param array $body Decoded response body
     * @return array List of ['url' => ..., 'title' => ...]
     */
    public static function extract_sources($body) {
        $sources = array();

        if (empty($body['content']) || !is_array($body['content'])) {
            return $sources;
        }

        foreach ($body['content'] as $block) {
            if (!isset($block['type']) || 'web_search_tool_result' !== $block['type']) {
                continue;
            }
            // An error result is an object, not a list - skip it.
            if (empty($block['content']) || !isset($block['content'][0])) {
                continue;
            }
            foreach ($block['content'] as $result) {
                if (!empty($result['url'])) {
                    $sources[] = array(
                        'url'   => $result['url'],
                        'title' => isset($result['title']) ? $result['title'] : $result['url'],
                    );
                }
            }
        }

        return $sources;
    }
}

FRA_Model_Resolver::get_instance();
