<?php
/**
 * Performance Optimization
 *
 * Handles asset optimization, caching, and performance improvements.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FRA_Performance
 *
 * Handles performance optimizations for the plugin.
 */
class FRA_Performance {

    /**
     * Singleton instance
     *
     * @var FRA_Performance|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return FRA_Performance
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
        // Optimize asset loading
        add_action('wp_enqueue_scripts', array($this, 'optimize_asset_loading'), 100);

        // Add preconnect hints for external resources
        add_action('wp_head', array($this, 'add_preconnect_hints'), 1);

        // Add caching headers for REST API
        add_filter('rest_post_dispatch', array($this, 'add_rest_cache_headers'), 10, 3);

        // Defer non-critical JavaScript
        add_filter('script_loader_tag', array($this, 'defer_scripts'), 10, 3);

        // Optimize knowledge base queries
        add_action('init', array($this, 'maybe_cache_knowledge_base'));
    }

    /**
     * Optimize asset loading - only load what's needed
     */
    public function optimize_asset_loading() {
        global $post;

        // Skip on admin
        if (is_admin()) {
            return;
        }

        // Check which assets are actually needed
        $needs_frontend = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'france_relocation_assistant');
        $needs_dashboard = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'fra_progress_dashboard');
        $needs_article = is_singular('kb_article');

        // Dequeue unused styles if not needed
        if (!$needs_frontend && !$needs_dashboard && !$needs_article) {
            wp_dequeue_style('fra-frontend-style');
            wp_dequeue_style('fra-testimonials');
            wp_dequeue_style('fra-breadcrumb');
            wp_dequeue_script('fra-frontend-script');
            wp_dequeue_script('fra-breadcrumb');
        }

        // Only load dashboard assets where needed
        if (!$needs_dashboard) {
            wp_dequeue_style('fra-dashboard');
            wp_dequeue_script('fra-dashboard');
        }

        // Only load article assets on article pages
        if (!$needs_article) {
            wp_dequeue_style('fra-article');
        }
    }

    /**
     * Add preconnect hints for external resources
     */
    public function add_preconnect_hints() {
        // Preconnect to Anthropic API if AI is enabled
        if (fra_is_api_configured()) {
            echo '<link rel="preconnect" href="https://api.anthropic.com" crossorigin>' . "\n";
        }

        // Preconnect to common CDNs used by WordPress
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
    }

    /**
     * Add caching headers for REST API responses
     *
     * @param WP_REST_Response $response Response object
     * @param WP_REST_Server $server Server object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response
     */
    public function add_rest_cache_headers($response, $server, $request) {
        $route = $request->get_route();

        // Cache profile/checklist endpoints for logged-in users
        if (strpos($route, '/fra/v1/profile') !== false) {
            $response->header('Cache-Control', 'private, max-age=60');
        }

        // Cache status endpoint
        if (strpos($route, '/fra/v1/chat/status') !== false) {
            $response->header('Cache-Control', 'public, max-age=300');
        }

        // Don't cache chat responses (they may be rate-limited)
        if (strpos($route, '/fra/v1/chat') !== false && $request->get_method() === 'POST') {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        return $response;
    }

    /**
     * Defer non-critical scripts
     *
     * @param string $tag Script tag
     * @param string $handle Script handle
     * @param string $src Script source
     * @return string Modified tag
     */
    public function defer_scripts($tag, $handle, $src) {
        // Scripts to defer
        $defer_scripts = array(
            'fra-breadcrumb',
            'fra-day-counter'
        );

        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src=', ' defer src=', $tag);
        }

        return $tag;
    }

    /**
     * Cache knowledge base in transient for faster access
     */
    public function maybe_cache_knowledge_base() {
        $cache_key = 'fra_kb_cache';

        if (false === get_transient($cache_key)) {
            $fra = France_Relocation_Assistant::get_instance();
            $kb = $fra->get_knowledge_base();

            // Cache for 1 hour
            set_transient($cache_key, $kb, HOUR_IN_SECONDS);
        }
    }

    /**
     * Get cached knowledge base
     *
     * @return array Knowledge base data
     */
    public static function get_cached_knowledge_base() {
        $cache_key = 'fra_kb_cache';
        $cached = get_transient($cache_key);

        if (false === $cached) {
            $fra = France_Relocation_Assistant::get_instance();
            $cached = $fra->get_knowledge_base();
            set_transient($cache_key, $cached, HOUR_IN_SECONDS);
        }

        return $cached;
    }

    /**
     * Clear all plugin caches
     */
    public static function clear_all_caches() {
        // Clear knowledge base cache
        delete_transient('fra_kb_cache');

        // Clear API response caches
        FRA_API_Proxy::clear_cache();

        // Clear rate limit transients (optional, only for testing)
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_fra_rate_%'
             OR option_name LIKE '_transient_timeout_fra_rate_%'"
        );
    }
}

// Initialize performance optimizations
FRA_Performance::get_instance();

/**
 * Helper function to clear all caches
 */
function fra_clear_caches() {
    FRA_Performance::clear_all_caches();
}
