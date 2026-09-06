<?php
/**
 * AI Client Shim
 *
 * Member Tools does not talk to Anthropic directly any more. Every request
 * goes through the main plugin's FRA_Model_Resolver, which resolves the model
 * ID from the live Anthropic model catalog at call time instead of using a
 * hardcoded ID that breaks when Anthropic retires the model.
 *
 * This class exists so Member Tools degrades gracefully if the main plugin is
 * deactivated: it falls back to a direct API call using a current model ID.
 *
 * @package FRA_Member_Tools
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRAMT_AI_Client {

    /** @var string Messages API endpoint (fallback path only) */
    const API_ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /** @var string Anthropic API version (fallback path only) */
    const API_VERSION = '2023-06-01';

    /** @var string Model used only when the main plugin is unavailable */
    const FALLBACK_MODEL = 'claude-sonnet-5';

    /**
     * Is the resolver from the main plugin available?
     *
     * @return bool
     */
    public static function has_resolver() {
        return class_exists('FRA_Model_Resolver');
    }

    /**
     * Send a Messages API request.
     *
     * Same argument shape as FRA_Model_Resolver::message().
     *
     * @param array $args Request arguments
     * @return array|WP_Error Decoded response body
     */
    public static function message($args) {
        if (self::has_resolver()) {
            return FRA_Model_Resolver::message($args);
        }

        return self::fallback_message($args);
    }

    /**
     * Extract all text blocks from a response.
     *
     * @param array $body Decoded response body
     * @return string
     */
    public static function extract_text($body) {
        if (self::has_resolver()) {
            return FRA_Model_Resolver::extract_text($body);
        }

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
     * Pull a JSON object out of a response.
     *
     * @param array $body Decoded response body
     * @return array|null
     */
    public static function extract_json($body) {
        if (self::has_resolver()) {
            return FRA_Model_Resolver::extract_json($body);
        }

        $text = trim(self::extract_text($body));
        if ('' === $text) {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if (false !== $start && false !== $end && $end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * The web search tool definition, when the resolver can supply one.
     *
     * Returns an empty array without the main plugin, so callers simply run
     * without web search rather than sending a tool the fallback can't retry.
     *
     * @param int $max_uses Maximum searches per request
     * @return array
     */
    public static function web_search_tool($max_uses = 8) {
        if (self::has_resolver()) {
            return array(FRA_Model_Resolver::web_search_tool($max_uses));
        }
        return array();
    }

    /**
     * Direct API call used only when the main plugin is not active.
     *
     * @param array $args Request arguments
     * @return array|WP_Error
     */
    private static function fallback_message($args) {
        $args = wp_parse_args($args, array(
            'system'     => '',
            'messages'   => array(),
            'max_tokens' => 4096,
            'tools'      => array(),
            'timeout'    => 120,
        ));

        $api_key = '';
        if (class_exists('France_Relocation_Assistant')
            && method_exists('France_Relocation_Assistant', 'get_api_key')) {
            $api_key = France_Relocation_Assistant::get_api_key();
        } else {
            $api_key = (string) get_option('fra_api_key', '');
        }

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Anthropic API key is not configured.');
        }

        $payload = array(
            'model'      => self::FALLBACK_MODEL,
            'max_tokens' => (int) $args['max_tokens'],
            'messages'   => $args['messages'],
        );

        if (!empty($args['system'])) {
            $payload['system'] = $args['system'];
        }
        if (!empty($args['tools'])) {
            $payload['tools'] = $args['tools'];
        }

        $response = wp_remote_post(self::API_ENDPOINT, array(
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
            return new WP_Error('api_error', $message);
        }

        if (empty($body['content']) || !is_array($body['content'])) {
            return new WP_Error('api_error', 'Unexpected API response format');
        }

        $body['fra_model'] = self::FALLBACK_MODEL;
        return $body;
    }
}
