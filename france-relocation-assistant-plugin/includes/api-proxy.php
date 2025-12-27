<?php
/**
 * API Proxy for Claude Integration
 *
 * Provides a secure proxy for frontend API calls with
 * rate limiting, caching, and usage tracking.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FRA_API_Proxy
 *
 * Handles secure API requests to Claude with rate limiting and caching.
 */
class FRA_API_Proxy {

    /**
     * Singleton instance
     *
     * @var FRA_API_Proxy|null
     */
    private static $instance = null;

    /**
     * Anthropic API endpoint
     *
     * @var string
     */
    const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /**
     * API version header
     *
     * @var string
     */
    const API_VERSION = '2023-06-01';

    /**
     * Get singleton instance
     *
     * @return FRA_API_Proxy
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Register AJAX handlers
        add_action('wp_ajax_fra_proxy_query', array($this, 'ajax_proxy_query'));
        add_action('wp_ajax_nopriv_fra_proxy_query', array($this, 'ajax_proxy_query'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('fra/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_chat_handler'),
            'permission_callback' => array( $this, 'check_chat_permission' ),
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'context' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                ),
                'conversation_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_key'
                )
            )
        ));

        register_rest_route('fra/v1', '/chat/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_status_handler'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Permission callback for chat endpoint
     *
     * Allows access for logged-in users or rate-limited anonymous users.
     *
     * @return bool|WP_Error True if permitted, WP_Error otherwise
     */
    public function check_chat_permission() {
        // Logged-in users always have access (rate limiting still applies in handler)
        if ( is_user_logged_in() ) {
            return true;
        }

        // Anonymous users: check if rate limiting allows access
        $rate_check = fra_check_rate_limit();
        if ( ! $rate_check['allowed'] ) {
            return new WP_Error(
                'rate_limited',
                $rate_check['message'],
                array(
                    'status' => 429,
                    'retry_after' => $rate_check['retry_after']
                )
            );
        }

        return true;
    }

    /**
     * REST API chat handler
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function rest_chat_handler($request) {
        // Check if API is configured
        if (!fra_is_api_configured()) {
            return new WP_Error(
                'api_not_configured',
                'AI responses are not enabled.',
                array('status' => 503)
            );
        }

        // Check rate limit
        $rate_check = fra_check_rate_limit();
        if (!$rate_check['allowed']) {
            return new WP_Error(
                'rate_limited',
                $rate_check['message'],
                array(
                    'status' => 429,
                    'retry_after' => $rate_check['retry_after']
                )
            );
        }

        // Get parameters
        $message = $request->get_param('message');
        $context = $request->get_param('context');
        $conversation_id = $request->get_param('conversation_id');

        // Process the request
        $result = $this->process_query($message, $context, $conversation_id);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'response' => $result['response'],
            'model' => $result['model'],
            'usage' => $result['usage'],
            'remaining' => array(
                'minute' => $rate_check['remaining_minute'] - 1,
                'day' => $rate_check['remaining_day'] - 1
            )
        ), 200);
    }

    /**
     * REST API status handler
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function rest_status_handler($request) {
        $config = fra_get_api_config();
        $rate_check = fra_check_rate_limit();

        return new WP_REST_Response(array(
            'enabled' => fra_is_api_configured(),
            'model' => $config['api_model'],
            'rate_limit' => array(
                'allowed' => $rate_check['allowed'],
                'remaining_minute' => $rate_check['remaining_minute'] ?? 0,
                'remaining_day' => $rate_check['remaining_day'] ?? 0
            )
        ), 200);
    }

    /**
     * AJAX handler for proxy queries
     */
    public function ajax_proxy_query() {
        check_ajax_referer('fra_nonce', 'nonce');

        // Check if API is configured
        if (!fra_is_api_configured()) {
            wp_send_json_error('AI responses are not enabled', 503);
        }

        // Check rate limit
        $rate_check = fra_check_rate_limit();
        if (!$rate_check['allowed']) {
            wp_send_json_error(array(
                'message' => $rate_check['message'],
                'retry_after' => $rate_check['retry_after']
            ), 429);
        }

        // Get parameters
        $message = sanitize_text_field($_POST['message'] ?? '');
        $context = sanitize_textarea_field($_POST['context'] ?? '');

        if (empty($message)) {
            wp_send_json_error('Message is required', 400);
        }

        // Process the request
        $result = $this->process_query($message, $context);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message(), 500);
        }

        wp_send_json_success(array(
            'response' => $result['response'],
            'model' => $result['model'],
            'usage' => $result['usage']
        ));
    }

    /**
     * Process a query to the Claude API
     *
     * @param string $message User message
     * @param string $context Optional context
     * @param string $conversation_id Optional conversation ID for caching
     * @return array|WP_Error Result or error
     */
    public function process_query($message, $context = '', $conversation_id = '') {
        // Check cache first
        $cache_key = $this->get_cache_key($message, $context);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            // Return cached response but still count toward rate limit
            fra_increment_usage();
            return $cached;
        }

        // Get API configuration
        $config = fra_get_api_config();

        // Build system prompt
        $system_prompt = $this->build_system_prompt();

        // Build user message with context
        $user_message = $message;
        if (!empty($context)) {
            $user_message = "Relevant context:\n" . $context . "\n\nUser question: " . $message;
        }

        // Make API request
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $config['api_key'],
                'anthropic-version' => self::API_VERSION
            ),
            'body' => wp_json_encode(array(
                'model' => $config['api_model'],
                'max_tokens' => $config['max_tokens'],
                'system' => $system_prompt,
                'messages' => array(
                    array('role' => 'user', 'content' => $user_message)
                )
            ))
        ));

        // Handle errors
        if (is_wp_error($response)) {
            return new WP_Error(
                'api_error',
                'API request failed: ' . $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error(
                'api_error',
                'API error: ' . ($body['error']['message'] ?? 'Unknown error')
            );
        }

        if (!isset($body['content'][0]['text'])) {
            return new WP_Error(
                'api_error',
                'Unexpected API response format'
            );
        }

        // Build result
        $result = array(
            'response' => $body['content'][0]['text'],
            'model' => $config['api_model'],
            'usage' => $body['usage'] ?? null
        );

        // Cache the response (5 minutes for similar queries)
        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

        // Increment usage counter
        fra_increment_usage();

        return $result;
    }

    /**
     * Build system prompt for Claude
     *
     * @return string System prompt
     */
    private function build_system_prompt() {
        $is_member = false;
        if (is_user_logged_in() && class_exists('FRA_Membership')) {
            $membership = FRA_Membership::get_instance();
            $is_member = $membership->user_has_access();
        }

        $base_prompt = "You are an expert assistant helping Americans relocate to France on the Relo2France website. You combine official information with practical, real-world insights.

**Your response structure for France relocation questions:**
1. **Official Info First**: State what the law/rules say with current numbers, fees, and requirements
2. Then include a section starting with exactly \"**In Practice**\" on its own line, followed by practical insights

**In Practice section should include:**
- How things actually work vs what the rules say
- Grey areas and enforcement reality
- Common experiences and what surprised people
- Practical tips not in official documentation
- Insider knowledge from expats who've been through it

**Tone**: Knowledgeable friend who's been through it, not a government website. Be conversational but accurate.

**Formatting rules:**
- Always put \"**In Practice**\" as a header on its own line before practical insights
- Use **bold** for sub-headers within the In Practice section
- Keep responses focused and concise

**Always:**
- Cite specific numbers and requirements when known
- Recommend professionals (immigration attorneys, tax advisors, notaires) for complex situations
- Note when information is anecdotal vs widely confirmed
- Be honest about grey areas without explicitly encouraging rule-breaking";

        // Add member-specific capabilities
        if ($is_member) {
            $base_prompt .= "\n\n**MEMBER ACCESS:**
This user is a paid member. You can help them create personalized documents, checklists, timelines, and action plans tailored to their specific situation.";
        } else {
            $base_prompt .= "\n\n**IMPORTANT RESTRICTION:**
You can answer questions and provide information, but you CANNOT create custom documents, checklists, timelines, action plans, or personalized templates for this user. If they request these, politely explain it's a premium member feature and suggest they become a member at /membership/.";
        }

        // Allow filtering by plugins
        return apply_filters('fra_proxy_system_prompt', $base_prompt, $is_member);
    }

    /**
     * Generate cache key for a query
     *
     * @param string $message User message
     * @param string $context Context
     * @return string Cache key
     */
    private function get_cache_key($message, $context) {
        $user_id = get_current_user_id();
        $data = $message . '|' . substr($context, 0, 500) . '|' . $user_id;
        return 'fra_cache_' . md5($data);
    }

    /**
     * Clear all cached responses
     *
     * @return void
     */
    public static function clear_cache() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_fra_cache_%'
             OR option_name LIKE '_transient_timeout_fra_cache_%'"
        );
    }
}

// Initialize the proxy
FRA_API_Proxy::get_instance();

/**
 * Helper function to make proxy queries from PHP
 *
 * @param string $message User message
 * @param string $context Optional context
 * @return array|WP_Error Result or error
 */
function fra_proxy_query($message, $context = '') {
    $proxy = FRA_API_Proxy::get_instance();
    return $proxy->process_query($message, $context);
}
