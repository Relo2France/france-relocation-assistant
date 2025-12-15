<?php
/**
 * Plugin Name: France Relocation Assistant
 * Plugin URI: https://relo2france.com
 * Description: AI-powered US to France relocation guidance with visa info, property guides, healthcare, taxes, and practical insights. Features weekly auto-updates, "In Practice" real-world advice, and comprehensive knowledge base.
 * Version: 2.9.84
 * Author: Relo2France
 * Author URI: https://relo2france.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: france-relocation-assistant
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * 
 * @package France_Relocation_Assistant
 * @since 1.0.0
 * 
 * CHANGELOG:
 * 2.9.0 - Member Tools integration hooks, filter system for add-on plugins
 * 2.6.2 - Fixed AI Review to scan ALL KB topics, not just predefined ones
 * 2.6.1 - Individual "In Practice" generator for any topic
 * 2.6.0 - Code cleanup, documentation, AI "In Practice" responses
 * 2.5.x - AI Review system, dynamic KB menu, mobile header fixes
 * 2.4.x - Site header, SEO, analytics features
 * 2.3.x - Membership system, customizer
 * 2.0.0 - Complete rewrite with AI integration
 * 1.0.0 - Initial release
 */

/*
|--------------------------------------------------------------------------
| Security Check
|--------------------------------------------------------------------------
*/
if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/
define('FRA_VERSION', '2.9.83');
define('FRA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FRA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/*
|--------------------------------------------------------------------------
| Main Plugin Class
|--------------------------------------------------------------------------
|
| This is the core class that handles all plugin functionality including
| the knowledge base, AI chat, admin interface, and scheduled updates.
|
*/
class France_Relocation_Assistant {
    
    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */
    
    /**
     * Singleton instance
     *
     * @var France_Relocation_Assistant|null
     */
    private static $instance = null;
    
    /**
     * Knowledge base option name in wp_options
     *
     * @var string
     */
    const KNOWLEDGE_BASE_OPTION = 'fra_knowledge_base';
    
    /**
     * Last update status option name
     *
     * @var string
     */
    const LAST_UPDATE_OPTION = 'fra_last_update';
    
    /**
     * WP-Cron hook name for weekly updates
     *
     * @var string
     */
    const CRON_HOOK = 'fra_weekly_update';
    
    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    */
    
    /**
     * Get singleton instance
     *
     * @return France_Relocation_Assistant
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Register all hooks and filters
     */
    private function __construct() {
        // Core initialization
        add_action('init', array($this, 'init'));
        
        // Activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Frontend shortcode
        add_shortcode('france_relocation_assistant', array($this, 'render_shortcode'));
        
        // Custom MemberPress shortcodes for in-chat account views
        add_shortcode('fra_mepr_subscriptions', array($this, 'render_mepr_subscriptions'));
        add_shortcode('fra_mepr_payments', array($this, 'render_mepr_payments'));
        
        // Scheduled tasks
        add_action(self::CRON_HOOK, array($this, 'run_weekly_update'));
        
        // Admin interface
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers (logged-in and public)
        add_action('wp_ajax_fra_search', array($this, 'ajax_search'));
        add_action('wp_ajax_nopriv_fra_search', array($this, 'ajax_search'));
        add_action('wp_ajax_fra_manual_update', array($this, 'ajax_manual_update'));
        add_action('wp_ajax_fra_ai_query', array($this, 'ajax_ai_query'));
        add_action('wp_ajax_nopriv_fra_ai_query', array($this, 'ajax_ai_query'));
        
        // Asset loading
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // MemberPress signup redirect (backup - primary redirect handled on thank-you page)
        add_action('wp_head', array($this, 'maybe_redirect_after_signup'));
        
        // Login redirect - go to home page with welcome message
        add_filter('login_redirect', array($this, 'login_redirect'), 999, 3);
        
        // Login failed - redirect back to home with error
        add_action('wp_login_failed', array($this, 'login_failed_redirect'), 999, 2);
        
        // Also catch authentication errors before they redirect
        add_filter('authenticate', array($this, 'catch_login_error'), 999, 3);
    }
    
    /**
     * Redirect users after MemberPress signup if configured
     *
     * NOTE: Primary redirect logic is now handled by JavaScript on the
     * thank-you page itself. This method is kept as a backup fallback
     * in case the page-level JS doesn't fire.
     *
     * @return void
     */
    public function maybe_redirect_after_signup() {
        // Primary redirect is handled by JS on the thank-you page
        // This is just a backup if that doesn't work
        
        if (is_admin() || !is_user_logged_in()) {
            return;
        }
        
        // Check if this looks like a MemberPress thank-you page with signup params
        if (!is_page('thank-you') || !isset($_GET['membership']) || !isset($_GET['trans_num'])) {
            return;
        }
        
        // Get redirect URL from settings
        $customizer = get_option('fra_customizer', array());
        $redirect_url = $customizer['inchat_auth_signup_redirect'] ?? '';
        
        if (empty($redirect_url)) {
            return;
        }
        
        // Add new_signup flag to URL so homepage knows to show welcome
        $redirect_url = add_query_arg('new_signup', '1', $redirect_url);
        
        // Output JavaScript redirect as backup
        ?>
        <script>
        (function() {
            setTimeout(function() {
                window.location.href = <?php echo wp_json_encode(esc_url($redirect_url)); ?>;
            }, 1500);
        })();
        </script>
        <?php
    }
    
    /**
     * Redirect users to home page after login
     *
     * Ensures users are redirected to the home page with a logged_in flag
     * so the chat interface can show a welcome message.
     *
     * @param string $redirect_to Default redirect URL
     * @param string $requested_redirect_to Requested redirect URL
     * @param WP_User $user User object
     * @return string Modified redirect URL
     */
    public function login_redirect($redirect_to, $requested_redirect_to, $user) {
        // Don't redirect admins - let them go to dashboard
        if (is_object($user) && !empty($user->roles) && in_array('administrator', $user->roles)) {
            return $redirect_to;
        }
        
        return home_url('/?logged_in=1');
    }
    
    /**
     * Redirect users to home page after failed login
     *
     * @param string $username Username that was attempted
     * @param WP_Error $error Error object
     */
    public function login_failed_redirect($username, $error = null) {
        // Get the error message
        $error_code = 'login_failed';
        if (is_wp_error($error) && $error->get_error_code()) {
            $error_code = $error->get_error_code();
        }
        
        // Store error in transient for retrieval on home page
        $error_message = $this->get_login_error_message($error_code);
        set_transient('fra_login_error_' . sanitize_key($username), $error_message, 60);
        
        // Redirect to home with error flag
        wp_safe_redirect(home_url('/?login_error=1&user=' . urlencode($username)));
        exit;
    }
    
    /**
     * Catch authentication errors and store them
     *
     * @param WP_User|WP_Error $user User object or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error
     */
    public function catch_login_error($user, $username, $password) {
        // Only process if we have an error and a username
        if (is_wp_error($user) && !empty($username)) {
            $error_code = $user->get_error_code();
            $error_message = $this->get_login_error_message($error_code);
            set_transient('fra_login_error_' . sanitize_key($username), $error_message, 60);
        }
        
        return $user;
    }
    
    /**
     * Get user-friendly error message for login errors
     *
     * @param string $error_code WordPress error code
     * @return string User-friendly message
     */
    private function get_login_error_message($error_code) {
        $messages = array(
            'invalid_username' => 'We couldn\'t find an account with that email or username.',
            'invalid_email' => 'We couldn\'t find an account with that email address.',
            'incorrect_password' => 'The password you entered is incorrect. Please try again.',
            'empty_username' => 'Please enter your email or username.',
            'empty_password' => 'Please enter your password.',
            'authentication_failed' => 'Login failed. Please check your credentials.',
        );
        
        return isset($messages[$error_code]) ? $messages[$error_code] : 'Login failed. Please try again.';
    }
    
    /**
     * Render MemberPress subscriptions table
     * 
     * Custom shortcode [fra_mepr_subscriptions] to display just the
     * subscriptions table from MemberPress account page.
     *
     * @return string HTML output
     */
    public function render_mepr_subscriptions() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your subscriptions.', 'france-relocation-assistant') . '</p>';
        }
        
        if (!class_exists('MeprAccountCtrl')) {
            return '<p>' . __('MemberPress is not active.', 'france-relocation-assistant') . '</p>';
        }
        
        // Temporarily remove action parameter to ensure we show the list
        // (prevents form submissions from other pages affecting this view)
        $saved_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        $saved_get_action = isset($_GET['action']) ? $_GET['action'] : null;
        $saved_post_action = isset($_POST['action']) ? $_POST['action'] : null;
        unset($_REQUEST['action'], $_GET['action'], $_POST['action']);
        
        ob_start();
        
        $acct_ctrl = new MeprAccountCtrl();
        $acct_ctrl->subscriptions();
        
        $output = ob_get_clean();
        
        // Restore action parameters
        if ($saved_action !== null) $_REQUEST['action'] = $saved_action;
        if ($saved_get_action !== null) $_GET['action'] = $saved_get_action;
        if ($saved_post_action !== null) $_POST['action'] = $saved_post_action;
        
        return $output;
    }
    
    /**
     * Render MemberPress payments table
     * 
     * Custom shortcode [fra_mepr_payments] to display just the
     * payments/transactions table from MemberPress account page.
     *
     * @return string HTML output
     */
    public function render_mepr_payments() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your payment history.', 'france-relocation-assistant') . '</p>';
        }
        
        if (!class_exists('MeprAccountCtrl')) {
            return '<p>' . __('MemberPress is not active.', 'france-relocation-assistant') . '</p>';
        }
        
        // Temporarily remove action parameter to ensure we show the list
        $saved_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        $saved_get_action = isset($_GET['action']) ? $_GET['action'] : null;
        $saved_post_action = isset($_POST['action']) ? $_POST['action'] : null;
        unset($_REQUEST['action'], $_GET['action'], $_POST['action']);
        
        ob_start();
        
        $acct_ctrl = new MeprAccountCtrl();
        $acct_ctrl->payments();
        
        $output = ob_get_clean();
        
        // Restore action parameters
        if ($saved_action !== null) $_REQUEST['action'] = $saved_action;
        if ($saved_get_action !== null) $_GET['action'] = $saved_get_action;
        if ($saved_post_action !== null) $_POST['action'] = $saved_post_action;
        
        return $output;
    }
    
    /**
     * Initialize plugin - Load text domain
     *
     * @return void
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('france-relocation-assistant', false, dirname(FRA_PLUGIN_BASENAME) . '/languages');
        
        // Fire action for add-on plugins to hook into
        do_action('fra_loaded', $this);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Add-on Integration Hooks (v2.9.0+)
    |--------------------------------------------------------------------------
    |
    | These methods provide integration points for add-on plugins like
    | France Relocation Member Tools
    |
    */
    
    /**
     * Check if user is a member (via MemberPress or other membership plugin)
     *
     * @param int|null $user_id User ID (null for current user)
     * @return bool
     */
    public function is_member($user_id = null) {
        // Allow add-ons to override membership check
        $is_member = apply_filters('fra_is_member', null, $user_id);
        
        if ($is_member !== null) {
            return $is_member;
        }
        
        // Fallback to built-in membership check
        if (class_exists('FRA_Membership')) {
            $membership = FRA_Membership::get_instance();
            return $membership->user_has_access($user_id);
        }
        
        return false;
    }
    
    /**
     * Get navigation items with filter for add-ons
     *
     * @return array Navigation items
     */
    public function get_navigation_items() {
        $items = array();
        
        // Allow add-ons to add navigation items
        return apply_filters('fra_navigation_items', $items);
    }
    
    /**
     * Get content sections with filter for add-ons
     *
     * @return array Content section callbacks
     */
    public function get_content_sections() {
        $sections = array();
        
        // Allow add-ons to add content sections
        return apply_filters('fra_content_sections', $sections);
    }
    
    /**
     * Get user registration data from MemberPress/WordPress
     *
     * Used by Member Tools to pre-fill profile on first access.
     * Returns data from WordPress user meta and MemberPress fields.
     *
     * @param int $user_id WordPress user ID
     * @return array Registration data for pre-filling profile
     */
    public function get_user_registration_data($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }
        
        $data = array(
            'legal_first_name' => $user->first_name,
            'legal_last_name' => $user->last_name,
            'email' => $user->user_email,
        );
        
        // Try to get MemberPress address fields
        $mepr_country = get_user_meta($user_id, 'mepr-address-country', true);
        $mepr_city = get_user_meta($user_id, 'mepr-address-city', true);
        $mepr_state = get_user_meta($user_id, 'mepr-address-state', true);
        
        if (!empty($mepr_country)) {
            $data['current_country'] = $mepr_country;
        }
        if (!empty($mepr_city)) {
            $data['current_city'] = $mepr_city;
        }
        if (!empty($mepr_state)) {
            $data['current_state'] = $mepr_state;
        }
        
        // Filter for extensibility
        return apply_filters('fra_user_registration_data', $data, $user_id);
    }
    
    /**
     * Get AI capabilities with filter for add-ons
     *
     * @param int|null $user_id User ID
     * @return array AI capabilities
     */
    public function get_ai_capabilities($user_id = null) {
        $is_member = $this->is_member($user_id);
        
        $capabilities = array(
            'knowledge_base' => true,
            'ai_responses' => get_option('fra_enable_ai', false),
            'document_creation' => false,
            'personalized_checklists' => false,
            'custom_timelines' => false,
            'unlimited_queries' => false,
        );
        
        // Allow add-ons to extend capabilities
        return apply_filters('fra_ai_capabilities', $capabilities, $is_member);
    }
    
    /**
     * Get system prompt with filter for member context
     *
     * @param int|null $user_id User ID
     * @return string System prompt
     */
    public function get_system_prompt($user_id = null) {
        $base_prompt = $this->get_base_system_prompt();
        
        // Allow add-ons to add member context
        return apply_filters('fra_system_prompt', $base_prompt, $user_id);
    }
    
    /**
     * Get base system prompt for AI
     *
     * @return string
     */
    private function get_base_system_prompt() {
        return "You are a knowledgeable France Relocation Assistant helping Americans move to France.";
    }
    
    /*
    |--------------------------------------------------------------------------
    | Activation & Deactivation
    |--------------------------------------------------------------------------
    */
    
    /**
     * Plugin activation - Initialize KB and schedule cron
     *
     * @return void
     */
    public function activate() {
        // Initialize knowledge base if not exists
        if (!get_option(self::KNOWLEDGE_BASE_OPTION)) {
            $this->initialize_knowledge_base();
        }
        
        // Schedule weekly cron job for Sunday at 1:00 AM
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $next_sunday = $this->get_next_sunday_1am();
            wp_schedule_event($next_sunday, 'weekly', self::CRON_HOOK);
        }
        
        // Log activation
        update_option(self::LAST_UPDATE_OPTION, array(
            'timestamp' => current_time('timestamp'),
            'status' => 'activated',
            'message' => 'Plugin activated successfully'
        ));
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation - Clean up cron jobs
     *
     * @return void
     */
    public function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }
    
    /**
     * Calculate next Sunday at 1:00 AM for cron scheduling
     *
     * @return int Unix timestamp
     */
    private function get_next_sunday_1am() {
        $timezone = get_option('timezone_string') ?: 'America/New_York';
        $dt = new DateTime('now', new DateTimeZone($timezone));
        
        // Find next Sunday
        $days_until_sunday = (7 - $dt->format('w')) % 7;
        if ($days_until_sunday === 0 && $dt->format('H') >= 1) {
            $days_until_sunday = 7; // If it's Sunday after 1 AM, schedule for next Sunday
        }
        
        $dt->modify("+{$days_until_sunday} days");
        $dt->setTime(1, 0, 0);
        
        return $dt->getTimestamp();
    }
    
    /*
    |--------------------------------------------------------------------------
    | Asset Loading (CSS/JS)
    |--------------------------------------------------------------------------
    */
    
    /**
     * Enqueue frontend assets hook (deferred to shortcode)
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Assets enqueued from shortcode for better performance
    }
    
    /**
     * Enqueue frontend assets when shortcode is rendered
     *
     * @return void
     */
    private function enqueue_shortcode_assets() {
        wp_enqueue_style(
            'fra-frontend-style',
            FRA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            FRA_VERSION
        );
        
        wp_enqueue_script(
            'fra-frontend-script',
            FRA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            FRA_VERSION,
            true
        );
        
        // Check for login error
        $login_error = '';
        if (isset($_GET['login_error']) && isset($_GET['user'])) {
            $username = sanitize_key($_GET['user']);
            $error = get_transient('fra_login_error_' . $username);
            if ($error) {
                $login_error = $error;
                delete_transient('fra_login_error_' . $username);
            }
        }
        
        wp_localize_script('fra-frontend-script', 'fraData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fra_nonce'),
            'lastUpdate' => get_option(self::LAST_UPDATE_OPTION),
            'knowledgeBase' => $this->get_knowledge_base(),
            'aiEnabled' => get_option('fra_enable_ai', false) && !empty(get_option('fra_api_key', '')),
            'isLoggedIn' => is_user_logged_in(),
            'logoutUrl' => wp_logout_url(home_url('/?logged_out=1')),
            'loginError' => $login_error,
        ));
    }
    
    /**
     * Enqueue admin assets on plugin pages
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'france-relocation-assistant') === false) {
            return;
        }
        
        wp_enqueue_style(
            'fra-admin-style',
            FRA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FRA_VERSION
        );
        
        wp_enqueue_script(
            'fra-admin-script',
            FRA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            FRA_VERSION,
            true
        );
        
        wp_localize_script('fra-admin-script', 'fraAdminData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fra_admin_nonce')
        ));
        
        // Load media uploader on customizer page
        if (strpos($hook, 'customizer') !== false) {
            wp_enqueue_media();
        }
    }
    
    /*
    |--------------------------------------------------------------------------
    | Admin Menu & Pages
    |--------------------------------------------------------------------------
    */
    
    /**
     * Register admin menu and submenus
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('France Relocation Assistant', 'france-relocation-assistant'),
            __('FR Assistant', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant',
            array($this, 'render_admin_page'),
            'dashicons-airplane',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('API Settings', 'france-relocation-assistant'),
            __('API Settings', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-settings',
            array($this, 'render_settings_page')
        );
        
        // KB expansion submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('Expand Knowledge Base', 'france-relocation-assistant'),
            __('Expand KB', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-kb',
            array($this, 'render_kb_expansion_page')
        );
        
        // Customizer submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('Front-End Customizer', 'france-relocation-assistant'),
            __('Customizer', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-customizer',
            array($this, 'render_customizer_page')
        );
        
        // SEO submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('SEO Settings', 'france-relocation-assistant'),
            __('SEO', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-seo',
            array($this, 'render_seo_page')
        );
        
        // Membership submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('Membership Settings', 'france-relocation-assistant'),
            __('Membership', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-membership',
            array($this, 'render_membership_page')
        );
        
        // AI Review submenu
        add_submenu_page(
            'france-relocation-assistant',
            __('AI Review', 'france-relocation-assistant'),
            __('AI Review', 'france-relocation-assistant'),
            'manage_options',
            'france-relocation-assistant-ai-review',
            array($this, 'render_ai_review_page')
        );
    }
    
    /*
    |--------------------------------------------------------------------------
    | Admin Page Renderers
    |--------------------------------------------------------------------------
    */
    
    /**
     * Render AI Review admin page
     *
     * @return void
     */
    public function render_ai_review_page() {
        include FRA_PLUGIN_DIR . 'includes/ai-review-page.php';
    }
    
    /**
     * Render membership admin page
     */
    public function render_membership_page() {
        // Enqueue media uploader
        wp_enqueue_media();
        include FRA_PLUGIN_DIR . 'includes/membership-page.php';
    }
    
    /**
     * Render customizer page
     */
    public function render_customizer_page() {
        include FRA_PLUGIN_DIR . 'includes/customizer-page.php';
    }
    
    /**
     * Render SEO page
     */
    public function render_seo_page() {
        include FRA_PLUGIN_DIR . 'includes/seo-page.php';
    }
    
    /**
     * Render KB expansion page
     */
    public function render_kb_expansion_page() {
        include FRA_PLUGIN_DIR . 'includes/kb-expansion-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include FRA_PLUGIN_DIR . 'includes/settings-page.php';
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $last_update = get_option(self::LAST_UPDATE_OPTION, array());
        $next_scheduled = wp_next_scheduled(self::CRON_HOOK);
        $knowledge_base = $this->get_knowledge_base();
        
        include FRA_PLUGIN_DIR . 'includes/admin-page.php';
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        // Enqueue assets when shortcode is rendered
        $this->enqueue_shortcode_assets();
        
        // Track page view for analytics
        do_action('fra_shortcode_rendered');
        
        $atts = shortcode_atts(array(
            'theme' => 'light',
            'show_day_counter' => 'true'
        ), $atts, 'france_relocation_assistant');
        
        ob_start();
        include FRA_PLUGIN_DIR . 'includes/shortcode-template.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX search handler
     */
    public function ajax_search() {
        check_ajax_referer('fra_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        $results = $this->search_knowledge_base($query);
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX manual update handler (admin only)
     */
    public function ajax_manual_update() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = $this->run_weekly_update();
        
        wp_send_json_success($result);
    }
    
    /**
     * Search knowledge base
     */
    public function search_knowledge_base($query) {
        $knowledge_base = $this->get_knowledge_base();
        $query_lower = strtolower($query);
        $results = array();
        
        foreach ($knowledge_base as $category => $topics) {
            foreach ($topics as $topic_key => $topic) {
                $score = 0;
                
                // Check keywords
                foreach ($topic['keywords'] as $keyword) {
                    if (strpos($query_lower, strtolower($keyword)) !== false) {
                        $score += 10;
                    }
                    // Partial match
                    if (strpos(strtolower($keyword), $query_lower) !== false) {
                        $score += 5;
                    }
                }
                
                // Check title
                if (strpos(strtolower($topic['title']), $query_lower) !== false) {
                    $score += 8;
                }
                
                // Check content
                $words = array_filter(explode(' ', $query_lower), function($w) {
                    return strlen($w) > 3;
                });
                foreach ($words as $word) {
                    if (strpos(strtolower($topic['content']), $word) !== false) {
                        $score += 2;
                    }
                }
                
                if ($score > 0) {
                    $results[] = array_merge($topic, array(
                        'score' => $score,
                        'category' => $category,
                        'topicKey' => $topic_key
                    ));
                }
            }
        }
        
        // Sort by score descending
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return $results;
    }
    
    /**
     * Run weekly update
     */
    public function run_weekly_update() {
        $update_log = array(
            'timestamp' => current_time('timestamp'),
            'status' => 'running',
            'updates' => array(),
            'added_count' => 0,
            'updated_count' => 0
        );
        
        try {
            // Fetch updates from official sources
            $updates = $this->fetch_official_updates();
            
            // Update knowledge base
            $knowledge_base = $this->get_knowledge_base();
            
            foreach ($updates as $category => $category_updates) {
                foreach ($category_updates as $topic_key => $topic_updates) {
                    if (isset($knowledge_base[$category][$topic_key])) {
                        // Track if anything actually changed
                        $changed = false;
                        foreach ($topic_updates as $field => $value) {
                            if (!isset($knowledge_base[$category][$topic_key][$field]) || 
                                $knowledge_base[$category][$topic_key][$field] !== $value) {
                                $knowledge_base[$category][$topic_key][$field] = $value;
                                $changed = true;
                            }
                        }
                        if ($changed) {
                            $knowledge_base[$category][$topic_key]['lastVerified'] = date('F Y');
                            $update_log['updates'][] = "{$category}/{$topic_key}: Updated";
                            $update_log['updated_count']++;
                        }
                    } else {
                        // New topic
                        if (!isset($knowledge_base[$category])) {
                            $knowledge_base[$category] = array();
                        }
                        $knowledge_base[$category][$topic_key] = $topic_updates;
                        $knowledge_base[$category][$topic_key]['lastVerified'] = date('F Y');
                        $update_log['updates'][] = "{$category}/{$topic_key}: Added";
                        $update_log['added_count']++;
                    }
                }
            }
            
            // Save updated knowledge base
            update_option(self::KNOWLEDGE_BASE_OPTION, $knowledge_base);
            
            $update_log['status'] = 'success';
            $total = $update_log['added_count'] + $update_log['updated_count'];
            if ($total > 0) {
                $update_log['message'] = sprintf(
                    'Knowledge base updated: %d added, %d updated',
                    $update_log['added_count'],
                    $update_log['updated_count']
                );
            } else {
                $update_log['message'] = 'Knowledge base is already up to date';
            }
            
        } catch (Exception $e) {
            $update_log['status'] = 'error';
            $update_log['message'] = $e->getMessage();
        }
        
        // Save update log
        update_option(self::LAST_UPDATE_OPTION, $update_log);
        
        // Log to WordPress
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('France Relocation Assistant: Weekly update completed - ' . $update_log['status']);
        }
        
        return $update_log;
    }
    
    /**
     * Fetch updates from official sources
     */
    private function fetch_official_updates() {
        $updates = array();
        
        // France-Visas.gouv.fr - Visa fees and requirements
        $visa_data = $this->fetch_visa_updates();
        if ($visa_data) {
            $updates['visas'] = $visa_data;
        }
        
        // Service-Public.fr - General administrative info
        $admin_data = $this->fetch_admin_updates();
        if ($admin_data) {
            $updates = array_merge_recursive($updates, $admin_data);
        }
        
        // SMIC (minimum wage) for financial requirements
        $smic_data = $this->fetch_smic_update();
        if ($smic_data) {
            $updates['visas']['visiteur']['financial_requirement'] = $smic_data;
        }
        
        return $updates;
    }
    
    /**
     * Fetch visa updates from France-Visas
     */
    private function fetch_visa_updates() {
        $updates = array();
        
        // Note: In production, you would use proper API calls or web scraping
        // This is a placeholder that checks for cached/updated data
        
        $response = wp_remote_get('https://france-visas.gouv.fr/en/long-stay-visa', array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'France Relocation Assistant WordPress Plugin/' . FRA_VERSION
            )
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            
            // Parse for visa fee (€99 as of 2025)
            if (preg_match('/visa fee[:\s]*€?(\d+)/i', $body, $matches)) {
                $updates['visiteur']['visa_fee'] = '€' . $matches[1];
            }
            
            // Mark as verified
            $updates['visiteur']['lastVerified'] = date('F Y');
        }
        
        return $updates;
    }
    
    /**
     * Fetch administrative updates from Service-Public
     */
    private function fetch_admin_updates() {
        $updates = array();
        
        // OFII validation fee check
        $response = wp_remote_get('https://www.service-public.fr/particuliers/vosdroits/F16162', array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'France Relocation Assistant WordPress Plugin/' . FRA_VERSION
            )
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            
            // Parse for OFII tax amount
            if (preg_match('/€?(\d{2,3})\s*(?:euros?|€)?\s*(?:tax|timbre|stamp)/i', $body, $matches)) {
                $updates['visas']['ofiiValidation']['tax_amount'] = '€' . $matches[1];
            }
        }
        
        return $updates;
    }
    
    /**
     * Fetch current SMIC (minimum wage)
     */
    private function fetch_smic_update() {
        // SMIC is updated annually on January 1
        // 2025 SMIC net: approximately €1,450/month
        
        $response = wp_remote_get('https://www.service-public.fr/particuliers/vosdroits/F2300', array(
            'timeout' => 30
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            
            // Parse for SMIC mensuel net
            if (preg_match('/(\d[\d\s,\.]+)\s*€?\s*(?:net|mensuel)/i', $body, $matches)) {
                $smic = str_replace(array(' ', ','), array('', '.'), $matches[1]);
                return '€' . number_format((float)$smic, 0);
            }
        }
        
        return null;
    }
    
    /*
    |--------------------------------------------------------------------------
    | Settings Registration
    |--------------------------------------------------------------------------
    */
    
    /**
     * Register plugin settings with WordPress
     *
     * @return void
     */
    public function register_settings() {
        // API Key
        register_setting('fra_settings', 'fra_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // AI Model selection
        register_setting('fra_settings', 'fra_api_model', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'claude-sonnet-4-20250514'
        ));
        
        // Enable AI toggle
        register_setting('fra_settings', 'fra_enable_ai', array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false
        ));
        
        // GitHub Pages URL for embedding
        register_setting('fra_embed_settings', 'fra_github_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
    }
    
    /*
    |--------------------------------------------------------------------------
    | AI Chat Handler
    |--------------------------------------------------------------------------
    |
    | Handles AI-powered responses combining official information with
    | practical "In Practice" insights for a more helpful user experience.
    |
    */
    
    /**
     * AJAX handler for AI chat queries
     * 
     * Processes user questions and returns responses that blend
     * official information with practical, real-world insights.
     *
     * @return void Sends JSON response
     */
    public function ajax_ai_query() {
        check_ajax_referer('fra_nonce', 'nonce');
        
        // Accept both 'query' and 'message' for API compatibility
        $query = sanitize_text_field($_POST['message'] ?? $_POST['query'] ?? '');
        $context = sanitize_textarea_field($_POST['context'] ?? '');
        
        if (empty($query)) {
            wp_send_json_error('Query is required');
        }
        
        // Verify AI is enabled and configured
        if (!get_option('fra_enable_ai', false)) {
            wp_send_json_error('AI responses are not enabled');
        }
        
        $api_key = get_option('fra_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }
        
        // Check if user is requesting document/checklist creation
        $is_document_request = $this->is_document_creation_request($query);
        
        // Check membership status
        $is_member = false;
        if (is_user_logged_in()) {
            $membership = FRA_Membership::get_instance();
            $is_member = $membership->user_has_access();
        }
        
        // If requesting document creation but not a member, return upsell
        if ($is_document_request && !$is_member) {
            $membership_url = esc_url(get_option('fra_membership_url', '/membership/'));
            $doc_type = $this->get_document_type($query) ?: 'document';
            $upsell_response = $this->build_upsell_response($doc_type, $membership_url);
            
            wp_send_json_success(array(
                'response' => $upsell_response,
                'model' => 'knowledge_base',
                'usage' => null,
                'is_upsell' => true,
                'membership_url' => $membership_url
            ));
            return;
        }
        
        $model = get_option('fra_api_model', 'claude-sonnet-4-20250514');
        
        // Build the prompt with context - emphasizing both official AND practical info
        $system_prompt = "You are an expert assistant helping Americans relocate to France on the Relo2France website. You combine official information with practical, real-world insights.

**ABOUT THIS SITE (Relo2France.com):**
This site helps Americans relocate to France. It offers:
- FREE Knowledge Base: Information about visas, property purchase, healthcare, banking, taxes, driving, shipping/pets, and settling in France
- AI Chat Assistant (that's you!): Answer questions about moving to France
- MEMBER TOOLS (paid membership): Premium features including:
  • Personalized Documents: AI-generated visa cover letters, financial attestations, and other documents customized to their situation
  • Custom Guides: Detailed personalized guides for French mortgages, pet relocation, apostilles, and banking
  • Interactive Checklists: Track progress through visa applications and relocation tasks
  • AI Document Verification: Upload health insurance to verify it meets French visa requirements
  • French Glossary: Administrative terms explained in plain English

If someone asks about the site, member tools, what you can do, or what's available here, explain these features. Direct non-members to /membership/ if they want premium features.

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
- Always put \"**In Practice**\" as a header on its own line before practical insights (for France-related questions)
- Use **bold** for sub-headers within the In Practice section
- Keep responses focused and concise
- For questions about the site itself, just answer directly without the \"In Practice\" format

**Always:**
- Cite specific numbers and requirements when known
- Recommend professionals (immigration attorneys, tax advisors, notaires) for complex situations
- Note when information is anecdotal vs widely confirmed
- Be honest about grey areas without explicitly encouraging rule-breaking";

        // Add restriction for non-members
        if (!$is_member) {
            $system_prompt .= "\n\n**IMPORTANT RESTRICTION:**
You can answer questions and provide information, but you CANNOT create custom documents, checklists, timelines, action plans, or personalized templates for this user.

If the user asks you to create, generate, make, or produce any kind of document, checklist, timeline, plan, template, or personalized content, politely explain that custom document creation is a premium member feature, briefly mention what members can get, and suggest they become a member at /membership/. Then offer to answer questions instead.";
        }
        
        $user_message = $query;
        if (!empty($context)) {
            // Only include context if it seems relevant to the question
            // Check if the question is about the site itself (meta question)
            $meta_keywords = array('member', 'membership', 'tools', 'site', 'website', 'features', 'premium', 'what can you', 'what do you', 'help me with', 'this site', 'relo2france');
            $is_meta_question = false;
            $query_lower = strtolower($query);
            foreach ($meta_keywords as $keyword) {
                if (strpos($query_lower, $keyword) !== false) {
                    $is_meta_question = true;
                    break;
                }
            }
            
            // Don't include KB context for meta questions about the site
            if (!$is_meta_question) {
                $user_message = "The following knowledge base content may be relevant. Use it if helpful, but focus on answering the user's actual question:\n\n---\n" . $context . "\n---\n\nUser question: " . $query;
            }
        }
        
        // Call Anthropic API
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => 1024,
                'system' => $system_prompt,
                'messages' => array(
                    array('role' => 'user', 'content' => $user_message)
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            wp_send_json_error('API error: ' . ($body['error']['message'] ?? 'Unknown error'));
        }
        
        if (isset($body['content'][0]['text'])) {
            wp_send_json_success(array(
                'response' => $body['content'][0]['text'],
                'model' => $model,
                'usage' => $body['usage'] ?? null
            ));
        } else {
            wp_send_json_error('Unexpected API response format');
        }
    }
    
    /**
     * Check if query is requesting document/checklist creation
     * 
     * Detects when a user is asking for personalized document creation,
     * which is a premium member feature.
     *
     * @param string $query The user's query text
     * @return bool True if the query requests document creation
     */
    private function is_document_creation_request($query) {
        if (empty($query)) {
            return false;
        }
        
        $query_lower = strtolower($query);
        
        // Action words that indicate creation
        $creation_verbs = array(
            'create', 'make', 'generate', 'build', 'write', 'draft',
            'prepare', 'produce', 'give me', 'can you make', 'i need',
            'put together', 'compile', 'design', 'develop', 'craft'
        );
        
        // Document types that require premium access
        $document_types = array(
            'checklist', 'check list', 'check-list',
            'document', 'template', 'form',
            'timeline', 'time line', 'time-line', 'schedule',
            'plan', 'action plan', 'roadmap', 'road map',
            'guide', 'step-by-step', 'steps',
            'list', 'to-do', 'todo', 'to do',
            'summary', 'overview', 'breakdown',
            'spreadsheet', 'tracker', 'worksheet',
            'comparison', 'chart', 'table',
            'budget', 'cost breakdown', 'estimate',
            'itinerary', 'agenda', 'calendar',
            'personalized', 'customized', 'custom', 'tailored',
            'my own', 'for me', 'for my situation', 'specific to'
        );
        
        // Check for combination of creation verb + document type
        foreach ($creation_verbs as $verb) {
            if (strpos($query_lower, $verb) !== false) {
                foreach ($document_types as $doc_type) {
                    if (strpos($query_lower, $doc_type) !== false) {
                        return true;
                    }
                }
            }
        }
        
        // Also catch direct requests like "a checklist for..." or "my visa checklist"
        $direct_patterns = array(
            '/\b(a|my|the)\s+(personalized|custom|tailored)?\s*(checklist|timeline|plan|document|template|guide)/i',
            '/checklist\s+(for|of)\s+/i',
            '/timeline\s+(for|of)\s+/i',
            '/\bstep.?by.?step\s+(guide|plan|checklist)/i',
            '/\baction\s+plan\b/i',
            '/\bmoving\s+(checklist|timeline|plan)\b/i',
            '/\bvisa\s+(checklist|timeline|plan)\b/i',
            '/\brelocation\s+(checklist|timeline|plan)\b/i'
        );
        
        foreach ($direct_patterns as $pattern) {
            if (preg_match($pattern, $query_lower)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the type of document being requested
     * 
     * Identifies the specific document type for personalized upsell messaging.
     *
     * @param string $query The user's query text
     * @return string|null The document type or null if not identified
     */
    private function get_document_type($query) {
        if (empty($query)) {
            return null;
        }
        
        $query_lower = strtolower($query);
        
        // Map document types to their keyword variations
        $types = array(
            'checklist' => array('checklist', 'check list', 'check-list', 'to-do', 'todo'),
            'timeline' => array('timeline', 'time line', 'schedule', 'calendar'),
            'action plan' => array('action plan', 'plan', 'roadmap', 'road map'),
            'guide' => array('guide', 'step-by-step', 'walkthrough'),
            'template' => array('template', 'form', 'document'),
            'budget' => array('budget', 'cost breakdown', 'estimate', 'cost estimate'),
            'comparison' => array('comparison', 'compare', 'versus', 'vs')
        );
        
        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($query_lower, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Build the upsell response for non-members requesting document creation
     *
     * @param string $doc_type The type of document requested
     * @param string $membership_url The URL to the membership signup page
     * @return string The formatted upsell message
     */
    private function build_upsell_response($doc_type, $membership_url) {
        $response = "**📋 Custom Documents & Checklists - Premium Feature**\n\n";
        $response .= "I'd love to create a personalized {$doc_type} for you!\n\n";
        $response .= "Custom document creation is a **member-exclusive feature**. As a member, you can get:\n\n";
        $response .= "• **Personalized checklists** tailored to your specific situation\n";
        $response .= "• **Custom timelines** based on your visa type and move date\n";
        $response .= "• **Document templates** pre-filled with your details\n";
        $response .= "• **Step-by-step action plans** for your unique circumstances\n";
        $response .= "• **Comparison documents** analyzing your specific options\n\n";
        $response .= "**In Practice**\n\n";
        $response .= "Members tell us these personalized documents save them hours of research and help them stay organized during what can be an overwhelming process.\n\n";
        $response .= "👉 %%MEMBERLINK%% to unlock custom document creation and other premium features!\n\n";
        $response .= "---\n\n";
        $response .= "*In the meantime, I'm happy to answer any questions about the relocation process. Just ask!*";
        
        return $response;
    }
    
    /*
    |--------------------------------------------------------------------------
    | Knowledge Base Management
    |--------------------------------------------------------------------------
    */
    
    /**
     * Get knowledge base from database
     *
     * @return array Knowledge base data
     */
    public function get_knowledge_base() {
        $knowledge_base = get_option(self::KNOWLEDGE_BASE_OPTION);
        $kb_version = get_option('fra_kb_version', '1.0');
        
        // Force reload if version mismatch
        if (!$knowledge_base || version_compare($kb_version, '2.0', '<')) {
            $this->initialize_knowledge_base();
            update_option('fra_kb_version', '2.0');
            $knowledge_base = get_option(self::KNOWLEDGE_BASE_OPTION);
        }
        
        return $knowledge_base;
    }
    
    /**
     * Initialize knowledge base with default data
     *
     * @return void
     */
    private function initialize_knowledge_base() {
        $knowledge_base = $this->get_default_knowledge_base();
        update_option(self::KNOWLEDGE_BASE_OPTION, $knowledge_base);
    }
    
    /**
     * Get default knowledge base from file
     *
     * @return array Default knowledge base data
     */
    private function get_default_knowledge_base() {
        // Try JSON file first
        $json_file = FRA_PLUGIN_DIR . 'includes/knowledge-base.json';
        
        if (file_exists($json_file)) {
            $json = file_get_contents($json_file);
            return json_decode($json, true);
        }
        
        // Fallback to PHP file
        return include FRA_PLUGIN_DIR . 'includes/knowledge-base-default.php';
    }
}

/*
|--------------------------------------------------------------------------
| Module Loading
|--------------------------------------------------------------------------
*/

// Core modules
require_once FRA_PLUGIN_DIR . 'includes/class-fra-seo.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-membership.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-updater.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-analytics.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-ai-review.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-scheduled-review.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-site-header.php';
require_once FRA_PLUGIN_DIR . 'includes/class-fra-auth-pages.php';

// Initialize AI Review (registers AJAX handlers)
FRA_AI_Review::get_instance();

// Initialize Scheduled Review (registers cron and AJAX handlers)
FRA_Scheduled_Review::get_instance();

/*
|--------------------------------------------------------------------------
| Plugin Initialization
|--------------------------------------------------------------------------
*/

/**
 * Initialize the plugin
 *
 * @return France_Relocation_Assistant
 */
function fra_init() {
    return France_Relocation_Assistant::get_instance();
}
add_action('plugins_loaded', 'fra_init');

/**
 * Add weekly cron schedule
 *
 * @param array $schedules Existing schedules
 * @return array Modified schedules
 */
function fra_add_cron_schedule($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = array(
            'interval' => 604800,
            'display' => __('Once Weekly', 'france-relocation-assistant')
        );
    }
    return $schedules;
}
add_filter('cron_schedules', 'fra_add_cron_schedule');
