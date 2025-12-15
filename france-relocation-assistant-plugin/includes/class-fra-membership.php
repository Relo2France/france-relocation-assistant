<?php
/**
 * Membership Integration Class
 * 
 * Handles premium content restriction and membership plugin integration
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Membership {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Get instance
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
        $this->settings = $this->get_settings();
        
        // Register shortcodes
        add_shortcode('fra_premium', array($this, 'premium_content_shortcode'));
        add_shortcode('fra_premium_resources', array($this, 'premium_resources_shortcode'));
        add_shortcode('fra_upgrade_button', array($this, 'upgrade_button_shortcode'));
        add_shortcode('fra_member_only', array($this, 'member_only_shortcode'));
        add_shortcode('fra_non_member_only', array($this, 'non_member_only_shortcode'));
        
        // Add premium indicator to assistant
        add_filter('fra_topic_content', array($this, 'filter_premium_topics'), 10, 3);
    }
    
    /**
     * Get settings with defaults
     */
    private function get_settings() {
        $defaults = array(
            'enabled' => false,
            'plugin' => 'pmp',
            'membership_level' => '',
            'price' => '4.00',
            'currency' => 'USD',
            'teaser_message' => 'This premium content is available to members. Get lifetime access for just $4!',
            'upgrade_button_text' => 'Get Lifetime Access - $4',
            'upgrade_url' => '',
            'premium_resources' => array()
        );
        
        $settings = get_option('fra_membership', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Check if membership features are enabled
     */
    public function is_enabled() {
        return !empty($this->settings['enabled']);
    }
    
    /**
     * Check if current user has premium access
     */
    public function user_has_access() {
        // If membership not enabled, everyone has access
        if (!$this->is_enabled()) {
            return true;
        }
        
        // Must be logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        $plugin = $this->settings['plugin'];
        $level = $this->settings['membership_level'];
        
        switch ($plugin) {
            case 'pmp':
                return $this->check_pmp_access($level);
                
            case 'woocommerce':
                return $this->check_woo_access($level);
                
            case 'restrict_content':
                return $this->check_rcp_access($level);
                
            case 'memberpress':
                return $this->check_mepr_access($level);
                
            case 'manual':
                return $this->check_role_access($level);
                
            default:
                return false;
        }
    }
    
    /**
     * Check Paid Memberships Pro access
     */
    private function check_pmp_access($level) {
        if (!function_exists('pmpro_hasMembershipLevel')) {
            return false;
        }
        
        if (empty($level)) {
            // Any membership level
            return pmpro_hasMembershipLevel();
        }
        
        // Specific level(s)
        $levels = array_map('trim', explode(',', $level));
        return pmpro_hasMembershipLevel($levels);
    }
    
    /**
     * Check WooCommerce Memberships access
     */
    private function check_woo_access($plan_id) {
        if (!function_exists('wc_memberships_is_user_active_member')) {
            return false;
        }
        
        if (empty($plan_id)) {
            // Check any active membership
            return wc_memberships_is_user_active_member();
        }
        
        return wc_memberships_is_user_active_member(get_current_user_id(), $plan_id);
    }
    
    /**
     * Check Restrict Content Pro access
     */
    private function check_rcp_access($level) {
        if (!function_exists('rcp_user_has_active_membership')) {
            return false;
        }
        
        return rcp_user_has_active_membership();
    }
    
    /**
     * Check MemberPress access
     */
    private function check_mepr_access($level) {
        if (!class_exists('MeprUser')) {
            return false;
        }
        
        $user = new MeprUser(get_current_user_id());
        
        // If no specific level, check if user has any active membership
        if (empty($level)) {
            return $user->is_active();
        }
        
        // Support comma-separated membership IDs
        $levels = array_map('trim', explode(',', $level));
        
        foreach ($levels as $membership_id) {
            if ($user->is_active_on_membership((int) $membership_id)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check user role access (manual mode)
     */
    private function check_role_access($role) {
        if (empty($role)) {
            return false;
        }
        
        $user = wp_get_current_user();
        return in_array($role, (array) $user->roles);
    }
    
    /**
     * Get formatted price
     */
    public function get_formatted_price() {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        );
        
        $symbol = $symbols[$this->settings['currency']] ?? '$';
        return $symbol . $this->settings['price'];
    }
    
    /**
     * Premium content shortcode [fra_premium]...[/fra_premium]
     */
    public function premium_content_shortcode($atts, $content = null) {
        if (!$this->is_enabled()) {
            return do_shortcode($content);
        }
        
        if ($this->user_has_access()) {
            return do_shortcode($content);
        }
        
        // Show teaser
        return $this->render_teaser();
    }
    
    /**
     * Member only shortcode (hidden for non-members)
     */
    public function member_only_shortcode($atts, $content = null) {
        if (!$this->is_enabled() || $this->user_has_access()) {
            return do_shortcode($content);
        }
        return '';
    }
    
    /**
     * Non-member only shortcode
     */
    public function non_member_only_shortcode($atts, $content = null) {
        if ($this->is_enabled() && !$this->user_has_access()) {
            return do_shortcode($content);
        }
        return '';
    }
    
    /**
     * Upgrade button shortcode
     */
    public function upgrade_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'class' => '',
            'text' => ''
        ), $atts);
        
        $text = !empty($atts['text']) ? $atts['text'] : $this->settings['upgrade_button_text'];
        $url = $this->settings['upgrade_url'];
        $class = 'fra-upgrade-btn ' . esc_attr($atts['class']);
        
        if (empty($url)) {
            return '';
        }
        
        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            esc_url($url),
            $class,
            esc_html($text)
        );
    }
    
    /**
     * Premium resources grid shortcode
     */
    public function premium_resources_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '', // Filter by category
            'columns' => '3'
        ), $atts);
        
        if (!$this->is_enabled()) {
            return '<p>' . __('Premium resources are not currently available.', 'france-relocation-assistant') . '</p>';
        }
        
        $resources = $this->settings['premium_resources'];
        $has_access = $this->user_has_access();
        
        // Filter by category if specified
        if (!empty($atts['category'])) {
            $resources = array_filter($resources, function($r) use ($atts) {
                return ($r['category'] ?? '') === $atts['category'];
            });
        }
        
        // Filter to enabled only
        $resources = array_filter($resources, function($r) {
            return !empty($r['enabled']);
        });
        
        if (empty($resources)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="fra-premium-resources" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php if (!$has_access): ?>
                <div class="fra-premium-header">
                    <div class="fra-premium-lock">🔒</div>
                    <h3><?php _e('Premium Resources', 'france-relocation-assistant'); ?></h3>
                    <p><?php echo esc_html($this->settings['teaser_message']); ?></p>
                    <?php echo $this->upgrade_button_shortcode(array()); ?>
                </div>
            <?php endif; ?>
            
            <div class="fra-resources-list <?php echo !$has_access ? 'fra-locked' : ''; ?>">
                <?php 
                $categories = array(
                    'visa' => '📋 Visa & Immigration',
                    'property' => '🏠 Property & Finance',
                    'planning' => '📅 Relocation Planning',
                    'taxes' => '💰 Tax Strategy'
                );
                
                $grouped = array();
                foreach ($resources as $key => $resource) {
                    $cat = $resource['category'] ?? 'other';
                    if (!isset($grouped[$cat])) $grouped[$cat] = array();
                    $grouped[$cat][$key] = $resource;
                }
                
                foreach ($grouped as $cat => $cat_resources):
                    $cat_label = $categories[$cat] ?? ucfirst($cat);
                ?>
                <div class="fra-resource-category">
                    <h4><?php echo esc_html($cat_label); ?></h4>
                    
                    <?php foreach ($cat_resources as $key => $resource): ?>
                    <div class="fra-resource-card">
                        <div class="fra-resource-icon">📄</div>
                        <div class="fra-resource-info">
                            <h5><?php echo esc_html($resource['title']); ?></h5>
                            <p><?php echo esc_html($resource['description']); ?></p>
                        </div>
                        <?php if ($has_access && !empty($resource['file'])): ?>
                            <a href="<?php echo esc_url($resource['file']); ?>" class="fra-download-btn" download>
                                <?php _e('Download', 'france-relocation-assistant'); ?> ⬇
                            </a>
                        <?php else: ?>
                            <span class="fra-locked-badge">🔒</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .fra-premium-resources {
            margin: 20px 0;
        }
        .fra-premium-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #fed7aa;
        }
        .fra-premium-lock {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .fra-premium-header h3 {
            margin: 0 0 10px 0;
            color: #c2410c;
        }
        .fra-premium-header p {
            color: #9a3412;
            margin-bottom: 15px;
        }
        .fra-upgrade-btn {
            display: inline-block;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white !important;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        .fra-upgrade-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.4);
        }
        .fra-resources-list.fra-locked {
            opacity: 0.6;
            pointer-events: none;
            filter: blur(1px);
        }
        .fra-resource-category {
            margin-bottom: 25px;
        }
        .fra-resource-category h4 {
            font-size: 16px;
            color: #374151;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .fra-resource-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        .fra-resource-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .fra-resource-icon {
            font-size: 28px;
            flex-shrink: 0;
        }
        .fra-resource-info {
            flex: 1;
        }
        .fra-resource-info h5 {
            margin: 0 0 4px 0;
            font-size: 15px;
            color: #111827;
        }
        .fra-resource-info p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        .fra-download-btn {
            display: inline-block;
            background: #10b981;
            color: white !important;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.2s ease;
        }
        .fra-download-btn:hover {
            background: #059669;
        }
        .fra-locked-badge {
            font-size: 20px;
            opacity: 0.5;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render teaser message
     */
    private function render_teaser() {
        ob_start();
        ?>
        <div class="fra-premium-teaser">
            <div class="fra-teaser-icon">🔒</div>
            <div class="fra-teaser-content">
                <h4><?php _e('Premium Content', 'france-relocation-assistant'); ?></h4>
                <p><?php echo esc_html($this->settings['teaser_message']); ?></p>
                <?php echo $this->upgrade_button_shortcode(array()); ?>
            </div>
        </div>
        
        <style>
        .fra-premium-teaser {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 12px;
            border: 2px solid #fcd34d;
            margin: 20px 0;
        }
        .fra-teaser-icon {
            font-size: 40px;
            flex-shrink: 0;
        }
        .fra-teaser-content h4 {
            margin: 0 0 8px 0;
            color: #92400e;
        }
        .fra-teaser-content p {
            margin: 0 0 15px 0;
            color: #a16207;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Filter topic content for premium topics
     */
    public function filter_premium_topics($content, $category, $topic) {
        // Define which topics are premium
        $premium_topics = array(
            'visas' => array('checklist', 'application_guide'),
            'property' => array('mortgage_guide', 'bank_ratings'),
            'taxes' => array('strategy_guide', 'filing_guide')
        );
        
        // Check if this topic is premium
        if (isset($premium_topics[$category]) && in_array($topic, $premium_topics[$category])) {
            if ($this->is_enabled() && !$this->user_has_access()) {
                return $this->render_teaser();
            }
        }
        
        return $content;
    }
}

// Initialize
function fra_membership_init() {
    return FRA_Membership::get_instance();
}
add_action('init', 'fra_membership_init');
