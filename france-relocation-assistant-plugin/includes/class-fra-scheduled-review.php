<?php
/**
 * Scheduled Background AI Review
 * 
 * Runs AI review automatically on a schedule (e.g., weekly on Sunday at 3am)
 * Processes topics one at a time with delays to avoid timeouts
 * Sends email notification when complete
 * 
 * @package France_Relocation_Assistant
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Scheduled_Review {
    
    /** @var FRA_Scheduled_Review|null Singleton instance */
    private static $instance = null;
    
    /** @var string Option for schedule settings */
    const SCHEDULE_OPTION = 'fra_review_schedule';
    
    /** @var string Option for background queue */
    const QUEUE_OPTION = 'fra_review_queue';
    
    /** @var string Option for background status */
    const STATUS_OPTION = 'fra_review_status';
    
    /** @var string Cron hook name */
    const CRON_HOOK = 'fra_scheduled_review';
    
    /** @var string Process hook name */
    const PROCESS_HOOK = 'fra_process_review_queue';
    
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
     * Constructor - register hooks
     */
    private function __construct() {
        // Cron hooks
        add_action(self::CRON_HOOK, array($this, 'start_scheduled_review'));
        add_action(self::PROCESS_HOOK, array($this, 'process_next_topic'));
        
        // AJAX handlers
        add_action('wp_ajax_fra_save_schedule', array($this, 'ajax_save_schedule'));
        add_action('wp_ajax_fra_start_background_review', array($this, 'ajax_start_background_review'));
        add_action('wp_ajax_fra_get_background_status', array($this, 'ajax_get_background_status'));
        add_action('wp_ajax_fra_cancel_background_review', array($this, 'ajax_cancel_background_review'));
        
        // Setup schedule on settings save
        add_action('update_option_' . self::SCHEDULE_OPTION, array($this, 'reschedule_cron'), 10, 2);
    }
    
    /**
     * Get default schedule settings
     */
    public function get_default_settings() {
        return array(
            'enabled' => false,
            'day' => 'sunday',
            'hour' => 3,
            'minute' => 0,
            'email_notification' => true,
            'email_address' => get_option('admin_email'),
            'last_run' => null,
            'next_run' => null
        );
    }
    
    /**
     * Get current schedule settings
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $settings = get_option(self::SCHEDULE_OPTION, array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Get background review status
     */
    public function get_status() {
        $default = array(
            'running' => false,
            'total_topics' => 0,
            'processed' => 0,
            'changes_found' => 0,
            'errors' => 0,
            'current_topic' => '',
            'started_at' => null,
            'completed_at' => null,
            'error_messages' => array()
        );
        $status = get_option(self::STATUS_OPTION, array());
        return wp_parse_args($status, $default);
    }
    
    /**
     * Update status
     */
    private function update_status($updates) {
        $status = $this->get_status();
        $status = array_merge($status, $updates);
        update_option(self::STATUS_OPTION, $status);
        return $status;
    }
    
    /**
     * AJAX: Save schedule settings
     */
    public function ajax_save_schedule() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $settings = array(
            'enabled' => !empty($_POST['enabled']),
            'day' => sanitize_text_field($_POST['day'] ?? 'sunday'),
            'hour' => intval($_POST['hour'] ?? 3),
            'minute' => intval($_POST['minute'] ?? 0),
            'email_notification' => !empty($_POST['email_notification']),
            'email_address' => sanitize_email($_POST['email_address'] ?? get_option('admin_email'))
        );
        
        // Validate
        $valid_days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
        if (!in_array($settings['day'], $valid_days)) {
            $settings['day'] = 'sunday';
        }
        $settings['hour'] = max(0, min(23, $settings['hour']));
        $settings['minute'] = max(0, min(59, $settings['minute']));
        
        update_option(self::SCHEDULE_OPTION, $settings);
        
        // Reschedule cron
        $this->setup_cron($settings);
        
        // Get next run time
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        
        wp_send_json_success(array(
            'settings' => $settings,
            'next_run' => $next_run ? date_i18n('F j, Y \a\t g:i a', $next_run) : 'Not scheduled'
        ));
    }
    
    /**
     * Setup/update cron schedule
     */
    public function setup_cron($settings = null) {
        if ($settings === null) {
            $settings = $this->get_settings();
        }
        
        // Clear existing schedule
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
        
        if (!$settings['enabled']) {
            return;
        }
        
        // Calculate next run time
        $next_run = $this->calculate_next_run($settings['day'], $settings['hour'], $settings['minute']);
        
        // Schedule weekly recurring event
        wp_schedule_event($next_run, 'weekly', self::CRON_HOOK);
        
        // Update settings with next run
        $settings['next_run'] = $next_run;
        update_option(self::SCHEDULE_OPTION, $settings);
    }
    
    /**
     * Calculate next run timestamp
     */
    private function calculate_next_run($day, $hour, $minute) {
        $days = array(
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        );
        
        $target_day = $days[$day];
        $current_day = intval(date('w'));
        $days_until = ($target_day - $current_day + 7) % 7;
        
        // If today is the target day, check if the time has passed
        if ($days_until === 0) {
            $target_time = strtotime("today {$hour}:{$minute}");
            if ($target_time <= time()) {
                $days_until = 7; // Next week
            }
        }
        
        $target_date = strtotime("+{$days_until} days");
        return strtotime(date('Y-m-d', $target_date) . " {$hour}:{$minute}:00");
    }
    
    /**
     * Reschedule when option changes
     */
    public function reschedule_cron($old_value, $new_value) {
        $this->setup_cron($new_value);
    }
    
    /**
     * Start scheduled review (called by cron)
     */
    public function start_scheduled_review() {
        $this->start_background_review('scheduled');
    }
    
    /**
     * AJAX: Start background review manually
     */
    public function ajax_start_background_review() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = $this->start_background_review('manual');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => 'Background review started',
            'total_topics' => $result['total_topics']
        ));
    }
    
    /**
     * Start background review
     */
    public function start_background_review($trigger = 'manual') {
        // Check if already running
        $status = $this->get_status();
        if ($status['running']) {
            return new WP_Error('already_running', 'A background review is already in progress');
        }
        
        // Check API key
        $api_key = get_option('fra_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'API key not configured');
        }
        
        // Get all topics
        $knowledge_base = get_option('fra_knowledge_base', array());
        $queue = array();
        
        foreach ($knowledge_base as $category => $topics) {
            if (!is_array($topics)) continue;
            
            foreach ($topics as $topic_key => $topic_data) {
                if (!is_array($topic_data)) continue;
                if (empty($topic_data['content'])) continue;
                
                $queue[] = array(
                    'category' => $category,
                    'topic_key' => $topic_key,
                    'name' => $topic_data['title'] ?? ucfirst(str_replace('_', ' ', $topic_key))
                );
            }
        }
        
        if (empty($queue)) {
            return new WP_Error('no_topics', 'No topics to review');
        }
        
        // Clear any existing pending reviews for fresh start
        update_option('fra_pending_reviews', array());
        
        // Save queue
        update_option(self::QUEUE_OPTION, $queue);
        
        // Update status
        $this->update_status(array(
            'running' => true,
            'trigger' => $trigger,
            'total_topics' => count($queue),
            'processed' => 0,
            'changes_found' => 0,
            'errors' => 0,
            'current_topic' => $queue[0]['name'],
            'started_at' => current_time('mysql'),
            'completed_at' => null,
            'error_messages' => array()
        ));
        
        // Schedule first topic processing (with 5 second delay)
        wp_schedule_single_event(time() + 5, self::PROCESS_HOOK);
        
        // Update schedule last run
        $settings = $this->get_settings();
        $settings['last_run'] = current_time('mysql');
        update_option(self::SCHEDULE_OPTION, $settings);
        
        return array('total_topics' => count($queue));
    }
    
    /**
     * Process next topic in queue (called by cron)
     */
    public function process_next_topic() {
        $queue = get_option(self::QUEUE_OPTION, array());
        $status = $this->get_status();
        
        if (empty($queue) || !$status['running']) {
            $this->complete_review();
            return;
        }
        
        // Get next topic
        $topic = array_shift($queue);
        update_option(self::QUEUE_OPTION, $queue);
        
        // Update status
        $this->update_status(array(
            'current_topic' => $topic['name'],
            'processed' => $status['processed'] + 1
        ));
        
        // Process this topic
        $result = $this->review_single_topic($topic);
        
        // Update status based on result
        $status = $this->get_status();
        if (is_wp_error($result)) {
            $errors = $status['error_messages'];
            $errors[] = $topic['name'] . ': ' . $result->get_error_message();
            $this->update_status(array(
                'errors' => $status['errors'] + 1,
                'error_messages' => array_slice($errors, -10) // Keep last 10 errors
            ));
        } elseif ($result['needs_update']) {
            $this->update_status(array(
                'changes_found' => $status['changes_found'] + 1
            ));
        }
        
        // Schedule next topic (with 30 second delay to avoid rate limits)
        if (!empty($queue)) {
            wp_schedule_single_event(time() + 30, self::PROCESS_HOOK);
        } else {
            // All done
            $this->complete_review();
        }
    }
    
    /**
     * Review a single topic
     */
    private function review_single_topic($topic) {
        $api_key = get_option('fra_api_key', '');
        $knowledge_base = get_option('fra_knowledge_base', array());
        
        if (!isset($knowledge_base[$topic['category']][$topic['topic_key']])) {
            return new WP_Error('not_found', 'Topic not found in KB');
        }
        
        $topic_data = $knowledge_base[$topic['category']][$topic['topic_key']];
        $current_content = $topic_data['content'] ?? '';
        
        if (empty($current_content)) {
            return new WP_Error('no_content', 'Topic has no content');
        }
        
        // Get AI Review instance for helper methods
        $ai_review = FRA_AI_Review::get_instance();
        $topic_info = $ai_review->get_topic_info($topic['category'], $topic['topic_key'], $topic_data);
        
        // Call Claude API
        $review_result = $this->call_claude_api($api_key, $topic['category'], $topic['topic_key'], $topic_info, $current_content);
        
        if (is_wp_error($review_result)) {
            return $review_result;
        }
        
        // Save to pending reviews if update needed
        if ($review_result['needs_update']) {
            $pending_reviews = get_option('fra_pending_reviews', array());
            $review_id = uniqid('review_');
            
            $pending_reviews[$review_id] = array(
                'id' => $review_id,
                'category' => $topic['category'],
                'topic' => $topic['topic_key'],
                'topic_name' => $topic_info['name'],
                'update_type' => $review_result['update_type'],
                'current_content' => $current_content,
                'suggested_content' => $review_result['suggested_content'],
                'in_practice_content' => $review_result['in_practice_content'] ?? '',
                'practice_sources' => $review_result['practice_sources'] ?? array(),
                'key_insights' => $review_result['key_insights'] ?? array(),
                'changes_summary' => $review_result['changes_summary'],
                'sources_checked' => $review_result['sources_checked'] ?? array(),
                'confidence' => $review_result['confidence'],
                'timestamp' => current_time('mysql')
            );
            
            update_option('fra_pending_reviews', $pending_reviews);
        }
        
        return $review_result;
    }
    
    /**
     * Call Claude API for review
     */
    private function call_claude_api($api_key, $category, $topic_key, $topic_info, $current_content) {
        $current_date = date('F j, Y');
        $current_year = date('Y');
        
        $practice_hints = isset($topic_info['practice_hints']) 
            ? implode(', ', $topic_info['practice_hints']) 
            : 'real-world experiences, common issues, practical tips';
        
        $search_queries = array();
        if (isset($topic_info['practice_hints'])) {
            foreach (array_slice($topic_info['practice_hints'], 0, 3) as $hint) {
                $search_queries[] = "France " . $hint . " " . $current_year . " expat experience";
            }
        }
        $search_queries_str = implode("\n- ", $search_queries);
        
        $prompt = "You are updating a US-to-France relocation guide. Today is {$current_date}.

**TOPIC:** {$topic_info['name']}
**CATEGORY:** {$category}

**CURRENT CONTENT:**
```
{$current_content}
```

**OFFICIAL SOURCES:** " . implode(', ', $topic_info['sources']) . "
**KEY FACTS TO VERIFY:** " . implode(', ', $topic_info['key_facts']) . "

**PRACTICAL TOPICS TO RESEARCH:**
- {$search_queries_str}
- Reddit r/expats, r/france, expat forums
- Recent blog posts and articles from Americans in France

---

**YOUR TASK - TWO PARTS:**

**PART 1: OFFICIAL INFORMATION**
Review and update the factual content. Check all numbers, fees, requirements, and deadlines against current official information.

**PART 2: IN PRACTICE (NEW SECTION)**
Research and write an \"**In Practice**\" section that covers:
• Grey areas and how rules are actually enforced (or not)
• Common experiences from expats and forums
• Practical tips that aren't in official documentation  
• Current discussions or recent changes people are talking about
• Things that surprised people or caught them off guard

**IMPORTANT FOR IN PRACTICE:**
- Be honest about grey areas without encouraging rule-breaking
- Cite specific sources (Reddit threads, blog posts, forum discussions, articles)
- Note when information is anecdotal vs. widely reported
- Include approximate dates of sources (\"as of late 2024\", \"reported in 2025\")
- Distinguish between \"the law says X\" and \"in practice, Y\"

---

**RESPOND WITH JSON ONLY:**
```json
{
    \"needs_update\": true/false,
    \"update_type\": \"none\" | \"minor\" | \"significant\" | \"rewrite\",
    \"confidence\": \"high\" | \"medium\" | \"low\",
    \"changes_summary\": \"Brief description of changes to official content\",
    \"suggested_content\": \"Updated OFFICIAL content (facts, requirements, fees). Use **bold** for headers and • for bullets.\",
    \"in_practice_content\": \"The IN PRACTICE section with real-world insights. Start with **In Practice** header. Include source citations inline like (Source: Reddit r/expats, Jan 2025) or (Source: FrenchEntrée blog, 2024).\",
    \"practice_sources\": [
        {\"name\": \"Source name or description\", \"type\": \"forum|blog|article|social\", \"date\": \"approximate date\"}
    ],
    \"official_sources_checked\": [\"source1.gouv.fr\"],
    \"key_insights\": [\"Most important practical insight 1\", \"Key insight 2\"]
}
```

**NOTES:**
- Always set needs_update to true if you're adding/updating the In Practice section
- The in_practice_content should feel conversational and helpful, not legal
- Be specific with examples where possible
- If a grey area exists, explain both the official rule AND the practical reality";

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 120,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 6000,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown API error');
        }
        
        if (!isset($body['content'][0]['text'])) {
            return new WP_Error('api_error', 'Unexpected response format');
        }
        
        $ai_response = $body['content'][0]['text'];
        $ai_response = preg_replace('/^```json\s*/', '', $ai_response);
        $ai_response = preg_replace('/\s*```$/', '', $ai_response);
        
        $result = json_decode($ai_response, true);
        
        if (!$result) {
            return new WP_Error('parse_error', 'Failed to parse AI response');
        }
        
        // Add sources_checked from response
        $result['sources_checked'] = $result['official_sources_checked'] ?? array();
        
        return $result;
    }
    
    /**
     * Complete the review process
     */
    private function complete_review() {
        $status = $this->get_status();
        
        // Update final status
        $this->update_status(array(
            'running' => false,
            'current_topic' => '',
            'completed_at' => current_time('mysql')
        ));
        
        // Clear queue
        delete_option(self::QUEUE_OPTION);
        
        // Update review history
        $history = get_option('fra_review_history', array());
        $history[] = array(
            'timestamp' => time(),
            'date' => current_time('mysql'),
            'reviewed' => $status['processed'],
            'changes_found' => $status['changes_found'],
            'errors' => $status['errors'],
            'filter' => 'all (background)',
            'trigger' => $status['trigger'] ?? 'unknown'
        );
        update_option('fra_review_history', array_slice($history, -20));
        
        // Send email notification
        $settings = $this->get_settings();
        if ($settings['email_notification'] && !empty($settings['email_address'])) {
            $this->send_notification_email($status, $settings['email_address']);
        }
    }
    
    /**
     * Send completion email
     */
    private function send_notification_email($status, $email) {
        $site_name = get_bloginfo('name');
        $admin_url = admin_url('admin.php?page=france-relocation-assistant-ai-review');
        
        $subject = "[{$site_name}] AI Review Complete - {$status['changes_found']} Updates Pending";
        
        $message = "Hello,\n\n";
        $message .= "Your scheduled AI Knowledge Base review has completed.\n\n";
        $message .= "📊 REVIEW SUMMARY\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Topics Reviewed: {$status['processed']}\n";
        $message .= "Updates Suggested: {$status['changes_found']}\n";
        $message .= "Errors: {$status['errors']}\n";
        $message .= "Started: {$status['started_at']}\n";
        $message .= "Completed: {$status['completed_at']}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        if ($status['changes_found'] > 0) {
            $message .= "🔔 ACTION REQUIRED\n";
            $message .= "You have {$status['changes_found']} pending updates to review.\n\n";
            $message .= "Review them here:\n{$admin_url}\n\n";
        } else {
            $message .= "✅ Your knowledge base is up to date!\n\n";
        }
        
        if ($status['errors'] > 0) {
            $message .= "⚠️ ERRORS\n";
            foreach ($status['error_messages'] as $error) {
                $message .= "• {$error}\n";
            }
            $message .= "\n";
        }
        
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "This is an automated message from {$site_name}\n";
        $message .= "Manage your review schedule: " . admin_url('admin.php?page=france-relocation-assistant-ai-review') . "\n";
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * AJAX: Get background status
     */
    public function ajax_get_background_status() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $status = $this->get_status();
        $settings = $this->get_settings();
        
        // Get next scheduled run
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        
        wp_send_json_success(array(
            'status' => $status,
            'settings' => $settings,
            'next_run' => $next_run ? date_i18n('F j, Y \a\t g:i a', $next_run) : null
        ));
    }
    
    /**
     * AJAX: Cancel background review
     */
    public function ajax_cancel_background_review() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Clear queue
        delete_option(self::QUEUE_OPTION);
        
        // Clear any scheduled processing
        wp_clear_scheduled_hook(self::PROCESS_HOOK);
        
        // Update status
        $this->update_status(array(
            'running' => false,
            'current_topic' => '',
            'completed_at' => current_time('mysql') . ' (cancelled)'
        ));
        
        wp_send_json_success(array('message' => 'Background review cancelled'));
    }
}
