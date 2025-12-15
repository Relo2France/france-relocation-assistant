<?php
/**
 * Usage Analytics
 * 
 * Tracks basic usage metrics for the France Relocation Assistant.
 * All data is stored locally in WordPress options - no external services.
 * 
 * Tracked metrics:
 * - Page views (daily, monthly, all-time)
 * - Chat messages sent
 * - Topic clicks (which KB topics are popular)
 * - Help modal opens
 * - Day counter modal opens
 * 
 * @package France_Relocation_Assistant
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Analytics {
    
    /** @var FRA_Analytics Singleton instance */
    private static $instance = null;
    
    /** @var string Option name for storing analytics data */
    private $option_name = 'fra_analytics';
    
    /**
     * Get singleton instance
     * 
     * @return FRA_Analytics
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Register hooks
     */
    private function __construct() {
        // AJAX handlers for tracking events
        add_action('wp_ajax_fra_track_event', array($this, 'ajax_track_event'));
        add_action('wp_ajax_nopriv_fra_track_event', array($this, 'ajax_track_event'));
        
        // Track page view when shortcode is rendered
        add_action('fra_shortcode_rendered', array($this, 'track_page_view'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
    }
    
    /**
     * Add analytics page to admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'france-relocation-assistant',
            __('Analytics', 'france-relocation-assistant'),
            __('📊 Analytics', 'france-relocation-assistant'),
            'manage_options',
            'fra-analytics',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Get analytics data
     * 
     * @return array
     */
    public function get_data() {
        $defaults = array(
            'page_views' => array(
                'total' => 0,
                'daily' => array(),
                'monthly' => array(),
            ),
            'chat_messages' => array(
                'total' => 0,
                'daily' => array(),
            ),
            'topic_clicks' => array(),
            'help_modal_opens' => 0,
            'day_counter_opens' => 0,
            'first_tracked' => current_time('mysql'),
            'last_updated' => current_time('mysql'),
        );
        
        $data = get_option($this->option_name, $defaults);
        return wp_parse_args($data, $defaults);
    }
    
    /**
     * Save analytics data
     * 
     * @param array $data
     */
    private function save_data($data) {
        $data['last_updated'] = current_time('mysql');
        update_option($this->option_name, $data);
    }
    
    /**
     * Track a page view
     */
    public function track_page_view() {
        $data = $this->get_data();
        $today = current_time('Y-m-d');
        $month = current_time('Y-m');
        
        // Increment total
        $data['page_views']['total']++;
        
        // Increment daily
        if (!isset($data['page_views']['daily'][$today])) {
            $data['page_views']['daily'][$today] = 0;
        }
        $data['page_views']['daily'][$today]++;
        
        // Increment monthly
        if (!isset($data['page_views']['monthly'][$month])) {
            $data['page_views']['monthly'][$month] = 0;
        }
        $data['page_views']['monthly'][$month]++;
        
        // Clean up old daily data (keep last 90 days)
        $data['page_views']['daily'] = $this->cleanup_old_data($data['page_views']['daily'], 90);
        
        // Clean up old monthly data (keep last 24 months)
        $data['page_views']['monthly'] = $this->cleanup_old_data($data['page_views']['monthly'], 24, 'monthly');
        
        $this->save_data($data);
    }
    
    /**
     * Track a chat message
     */
    public function track_chat_message() {
        $data = $this->get_data();
        $today = current_time('Y-m-d');
        
        $data['chat_messages']['total']++;
        
        if (!isset($data['chat_messages']['daily'][$today])) {
            $data['chat_messages']['daily'][$today] = 0;
        }
        $data['chat_messages']['daily'][$today]++;
        
        // Clean up old daily data
        $data['chat_messages']['daily'] = $this->cleanup_old_data($data['chat_messages']['daily'], 90);
        
        $this->save_data($data);
    }
    
    /**
     * Track a topic click
     * 
     * @param string $category
     * @param string $topic
     */
    public function track_topic_click($category, $topic) {
        $data = $this->get_data();
        $key = $category . ':' . $topic;
        
        if (!isset($data['topic_clicks'][$key])) {
            $data['topic_clicks'][$key] = 0;
        }
        $data['topic_clicks'][$key]++;
        
        $this->save_data($data);
    }
    
    /**
     * Track modal opens
     * 
     * @param string $modal 'help' or 'day_counter'
     */
    public function track_modal_open($modal) {
        $data = $this->get_data();
        
        if ($modal === 'help') {
            $data['help_modal_opens']++;
        } elseif ($modal === 'day_counter') {
            $data['day_counter_opens']++;
        }
        
        $this->save_data($data);
    }
    
    /**
     * AJAX handler for tracking events from frontend
     */
    public function ajax_track_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'fra_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $event = isset($_POST['event']) ? sanitize_text_field($_POST['event']) : '';
        
        switch ($event) {
            case 'chat_message':
                $this->track_chat_message();
                break;
                
            case 'topic_click':
                $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
                $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
                if ($category && $topic) {
                    $this->track_topic_click($category, $topic);
                }
                break;
                
            case 'modal_open':
                $modal = isset($_POST['modal']) ? sanitize_text_field($_POST['modal']) : '';
                if ($modal) {
                    $this->track_modal_open($modal);
                }
                break;
                
            default:
                wp_send_json_error('Unknown event');
        }
        
        wp_send_json_success();
    }
    
    /**
     * Clean up old data entries
     * 
     * @param array $data Date-keyed array
     * @param int $keep_count Number of entries to keep
     * @param string $type 'daily' or 'monthly'
     * @return array
     */
    private function cleanup_old_data($data, $keep_count, $type = 'daily') {
        if (count($data) <= $keep_count) {
            return $data;
        }
        
        // Sort by date key descending
        krsort($data);
        
        // Keep only the most recent entries
        return array_slice($data, 0, $keep_count, true);
    }
    
    /**
     * Get summary statistics
     * 
     * @return array
     */
    public function get_summary() {
        $data = $this->get_data();
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this_month = current_time('Y-m');
        $last_month = date('Y-m', strtotime('-1 month'));
        
        // Calculate 7-day totals
        $last_7_days_views = 0;
        $last_7_days_messages = 0;
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $last_7_days_views += isset($data['page_views']['daily'][$date]) ? $data['page_views']['daily'][$date] : 0;
            $last_7_days_messages += isset($data['chat_messages']['daily'][$date]) ? $data['chat_messages']['daily'][$date] : 0;
        }
        
        // Top topics
        $topic_clicks = $data['topic_clicks'];
        arsort($topic_clicks);
        $top_topics = array_slice($topic_clicks, 0, 5, true);
        
        return array(
            'page_views' => array(
                'total' => $data['page_views']['total'],
                'today' => isset($data['page_views']['daily'][$today]) ? $data['page_views']['daily'][$today] : 0,
                'yesterday' => isset($data['page_views']['daily'][$yesterday]) ? $data['page_views']['daily'][$yesterday] : 0,
                'last_7_days' => $last_7_days_views,
                'this_month' => isset($data['page_views']['monthly'][$this_month]) ? $data['page_views']['monthly'][$this_month] : 0,
                'last_month' => isset($data['page_views']['monthly'][$last_month]) ? $data['page_views']['monthly'][$last_month] : 0,
            ),
            'chat_messages' => array(
                'total' => $data['chat_messages']['total'],
                'today' => isset($data['chat_messages']['daily'][$today]) ? $data['chat_messages']['daily'][$today] : 0,
                'last_7_days' => $last_7_days_messages,
            ),
            'top_topics' => $top_topics,
            'help_modal_opens' => $data['help_modal_opens'],
            'day_counter_opens' => $data['day_counter_opens'],
            'first_tracked' => $data['first_tracked'],
            'last_updated' => $data['last_updated'],
        );
    }
    
    /**
     * Reset all analytics data
     */
    public function reset_data() {
        delete_option($this->option_name);
    }
    
    /**
     * Render admin analytics page
     */
    public function render_admin_page() {
        // Handle reset action
        if (isset($_POST['fra_reset_analytics']) && check_admin_referer('fra_reset_analytics')) {
            $this->reset_data();
            echo '<div class="notice notice-success"><p>' . __('Analytics data has been reset.', 'france-relocation-assistant') . '</p></div>';
        }
        
        $summary = $this->get_summary();
        $data = $this->get_data();
        ?>
        <div class="wrap">
            <h1><?php _e('📊 Usage Analytics', 'france-relocation-assistant'); ?></h1>
            <p class="description" style="font-size: 14px; max-width: 700px;">
                <?php _e('Track how visitors engage with your France Relocation Assistant. This complements Jetpack Stats or Google Analytics by tracking <strong>in-app engagement</strong> that page-level analytics can\'t see.', 'france-relocation-assistant'); ?>
            </p>
            
            <!-- Summary Cards -->
            <div class="fra-analytics-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin: 24px 0;">
                
                <!-- Assistant Loads Card -->
                <div class="fra-analytics-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 24px; border-radius: 12px; color: #fff;">
                    <h3 style="margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">📄 Assistant Loads</h3>
                    <div style="font-size: 42px; font-weight: 700;"><?php echo number_format($summary['page_views']['total']); ?></div>
                    <div style="font-size: 13px; margin-top: 12px; opacity: 0.85;">
                        <span style="display: inline-block; margin-right: 16px;">Today: <strong><?php echo number_format($summary['page_views']['today']); ?></strong></span>
                        <span>7 days: <strong><?php echo number_format($summary['page_views']['last_7_days']); ?></strong></span>
                    </div>
                </div>
                
                <!-- Chat Messages Card -->
                <div class="fra-analytics-card" style="background: linear-gradient(135deg, #059669 0%, #34d399 100%); padding: 24px; border-radius: 12px; color: #fff;">
                    <h3 style="margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">💬 Questions Asked</h3>
                    <div style="font-size: 42px; font-weight: 700;"><?php echo number_format($summary['chat_messages']['total']); ?></div>
                    <div style="font-size: 13px; margin-top: 12px; opacity: 0.85;">
                        <span style="display: inline-block; margin-right: 16px;">Today: <strong><?php echo number_format($summary['chat_messages']['today']); ?></strong></span>
                        <span>7 days: <strong><?php echo number_format($summary['chat_messages']['last_7_days']); ?></strong></span>
                    </div>
                </div>
                
                <!-- Engagement Rate Card -->
                <div class="fra-analytics-card" style="background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); padding: 24px; border-radius: 12px; color: #fff;">
                    <h3 style="margin: 0 0 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">📈 Engagement Rate</h3>
                    <?php 
                    $engagement_rate = $summary['page_views']['total'] > 0 
                        ? round(($summary['chat_messages']['total'] / $summary['page_views']['total']) * 100, 1) 
                        : 0;
                    ?>
                    <div style="font-size: 42px; font-weight: 700;"><?php echo $engagement_rate; ?>%</div>
                    <div style="font-size: 13px; margin-top: 12px; opacity: 0.85;">
                        Visitors who ask questions
                    </div>
                </div>
                
            </div>
            
            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 24px 0;">
            
                <!-- Top Topics -->
                <div class="fra-analytics-section" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 16px; font-size: 16px;">🔥 Most Popular Topics</h3>
                    <?php if (!empty($summary['top_topics'])) : ?>
                    <table class="widefat striped" style="border: none;">
                        <thead>
                            <tr>
                                <th style="padding: 10px 12px;"><?php _e('Topic', 'france-relocation-assistant'); ?></th>
                                <th style="width: 80px; text-align: right; padding: 10px 12px;"><?php _e('Clicks', 'france-relocation-assistant'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['top_topics'] as $key => $clicks) : 
                                $parts = explode(':', $key);
                                $cat = isset($parts[0]) ? $parts[0] : '';
                                $topic = isset($parts[1]) ? $parts[1] : '';
                            ?>
                            <tr>
                                <td style="padding: 10px 12px;">
                                    <span style="color: #666;"><?php echo esc_html(ucfirst($cat)); ?></span>
                                    <span style="color: #999; margin: 0 4px;">→</span>
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $topic))); ?></strong>
                                </td>
                                <td style="text-align: right; padding: 10px 12px; font-weight: 600; color: #1e3a8a;"><?php echo number_format($clicks); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <p style="color: #666; margin: 0;"><?php _e('No topic clicks recorded yet. Data will appear as visitors browse the Knowledge Base.', 'france-relocation-assistant'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Tool Usage -->
                <div class="fra-analytics-section" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin: 0 0 16px; font-size: 16px;">🛠️ Tool Usage</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #1e3a8a;"><?php echo number_format($summary['day_counter_opens']); ?></div>
                            <div style="font-size: 13px; color: #666; margin-top: 4px;">📅 Day Counter Opens</div>
                        </div>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 32px; font-weight: 700; color: #059669;"><?php echo number_format($summary['help_modal_opens']); ?></div>
                            <div style="font-size: 13px; color: #666; margin-top: 4px;">💡 Help Tips Opens</div>
                        </div>
                    </div>
                    <p style="color: #888; font-size: 12px; margin: 16px 0 0; padding-top: 12px; border-top: 1px solid #eee;">
                        Track which tools visitors find most useful.
                    </p>
                </div>
            
            </div>
            
            <!-- Monthly Breakdown -->
            <div class="fra-analytics-section" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin: 24px 0;">
                <h3 style="margin: 0 0 16px; font-size: 16px;">📅 Monthly Trends</h3>
                <?php if (!empty($data['page_views']['monthly'])) : 
                    $monthly = $data['page_views']['monthly'];
                    krsort($monthly);
                    $monthly = array_slice($monthly, 0, 6, true);
                    $max_views = max($monthly);
                ?>
                <div style="display: flex; align-items: flex-end; gap: 12px; height: 120px; padding: 0 20px;">
                    <?php foreach (array_reverse($monthly, true) as $month => $views) : 
                        $height = $max_views > 0 ? ($views / $max_views) * 100 : 0;
                    ?>
                    <div style="flex: 1; text-align: center;">
                        <div style="background: linear-gradient(180deg, #3b82f6 0%, #1e3a8a 100%); height: <?php echo max($height, 4); ?>px; border-radius: 4px 4px 0 0; margin-bottom: 8px;"></div>
                        <div style="font-size: 11px; color: #666;"><?php echo esc_html(date('M', strtotime($month . '-01'))); ?></div>
                        <div style="font-size: 12px; font-weight: 600; color: #1e3a8a;"><?php echo number_format($views); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p style="color: #666; margin: 0;"><?php _e('Monthly data will appear after your first full month of tracking.', 'france-relocation-assistant'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Data Info & Reset -->
            <div class="fra-analytics-section" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 24px 0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <p style="margin: 0 0 4px; color: #666; font-size: 13px;">
                        <strong><?php _e('Tracking since:', 'france-relocation-assistant'); ?></strong> 
                        <?php echo esc_html(date('F j, Y', strtotime($summary['first_tracked']))); ?>
                        &nbsp;·&nbsp;
                        <strong><?php _e('Last activity:', 'france-relocation-assistant'); ?></strong> 
                        <?php echo esc_html(human_time_diff(strtotime($summary['last_updated'])) . ' ago'); ?>
                    </p>
                    <p style="margin: 0; color: #888; font-size: 12px;">
                        <?php _e('All data stored locally in your WordPress database. No external tracking services used.', 'france-relocation-assistant'); ?>
                    </p>
                </div>
                
                <form method="post" style="margin: 0;">
                    <?php wp_nonce_field('fra_reset_analytics'); ?>
                    <button type="submit" name="fra_reset_analytics" class="button" onclick="return confirm('<?php _e('Are you sure you want to reset all analytics data? This cannot be undone.', 'france-relocation-assistant'); ?>');">
                        <?php _e('Reset Data', 'france-relocation-assistant'); ?>
                    </button>
                </form>
            </div>
            
        </div>
        <?php
    }
}

// =============================================================================
// INITIALIZE
// =============================================================================
FRA_Analytics::get_instance();
