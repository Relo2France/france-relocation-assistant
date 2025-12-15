<?php
/**
 * AI Review Admin Page v2
 * Comprehensive topic-level review interface
 * 
 * @package France_Relocation_Assistant
 * @since 2.5.6
 */

if (!defined('ABSPATH')) {
    exit;
}

$ai_review = FRA_AI_Review::get_instance();
$pending_reviews = $ai_review->get_pending_reviews();
$reviewable_topics = $ai_review->get_reviewable_topics();
$review_history = get_option('fra_review_history', array());
$api_key_configured = !empty(get_option('fra_api_key', ''));

// Count total reviewable topics
$total_topics = 0;
foreach ($reviewable_topics as $cat => $topics) {
    $total_topics += count($topics);
}
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-superhero"></span>
        <?php _e('AI Knowledge Base Review', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('AI-powered comprehensive review of your knowledge base. Claude will check each topic against current information and suggest updates - from simple number changes to complete rewrites when policies change.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <?php if (!$api_key_configured): ?>
    <div class="notice notice-error">
        <p>
            <strong><?php _e('API Key Required', 'france-relocation-assistant'); ?></strong><br>
            <?php _e('Please configure your Claude API key in', 'france-relocation-assistant'); ?>
            <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-settings'); ?>"><?php _e('Settings', 'france-relocation-assistant'); ?></a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="fra-review-grid">
        <!-- Start Review Card -->
        <div class="fra-card fra-card-highlight">
            <h2><?php _e('🔍 Start Comprehensive Review', 'france-relocation-assistant'); ?></h2>
            
            <p><?php _e('Claude will review each topic in your knowledge base:', 'france-relocation-assistant'); ?></p>
            
            <ul class="fra-check-list">
                <li>✓ <?php _e('Compare content against current official information', 'france-relocation-assistant'); ?></li>
                <li>✓ <?php _e('Check all numbers, fees, thresholds, and deadlines', 'france-relocation-assistant'); ?></li>
                <li>✓ <?php _e('Detect policy changes that need content rewrites', 'france-relocation-assistant'); ?></li>
                <li>✓ <?php _e('Suggest minor fixes OR complete updates as needed', 'france-relocation-assistant'); ?></li>
            </ul>
            
            <div class="fra-review-scope">
                <label><strong><?php _e('Review Scope:', 'france-relocation-assistant'); ?></strong></label>
                <select id="fra-review-scope" style="margin-left: 10px;">
                    <option value="all"><?php printf(__('All Topics (%d)', 'france-relocation-assistant'), $total_topics); ?></option>
                    <optgroup label="<?php _e('By Category', 'france-relocation-assistant'); ?>">
                        <?php foreach ($reviewable_topics as $cat_key => $topics): ?>
                        <option value="<?php echo esc_attr($cat_key); ?>">
                            <?php echo esc_html(ucfirst($cat_key)); ?> (<?php echo count($topics); ?> topics)
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div class="fra-review-actions" style="margin-top: 20px;">
                <button type="button" id="fra-start-review" class="button button-primary button-hero" <?php echo !$api_key_configured ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-search" style="margin-top: 5px;"></span>
                    <?php _e('Start AI Review', 'france-relocation-assistant'); ?>
                </button>
                <div id="fra-review-progress" style="display: none; margin-top: 15px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <span id="fra-review-status-text"><?php _e('Reviewing topics...', 'france-relocation-assistant'); ?></span>
                </div>
            </div>
            
            <p class="fra-note" style="margin-top: 15px; font-size: 12px; color: #666;">
                <?php _e('Note: Review may take 30-60 seconds per topic. Reviewing all topics at once may take several minutes.', 'france-relocation-assistant'); ?>
            </p>
        </div>
        
        <!-- Review History Card -->
        <div class="fra-card">
            <h2><?php _e('📊 Review History', 'france-relocation-assistant'); ?></h2>
            
            <?php if (empty($review_history)): ?>
            <p class="fra-empty-state"><?php _e('No reviews yet. Start your first AI review to see history.', 'france-relocation-assistant'); ?></p>
            <?php else: ?>
            <table class="fra-history-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'france-relocation-assistant'); ?></th>
                        <th><?php _e('Scope', 'france-relocation-assistant'); ?></th>
                        <th><?php _e('Reviewed', 'france-relocation-assistant'); ?></th>
                        <th><?php _e('Updates', 'france-relocation-assistant'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse(array_slice($review_history, -10)) as $entry): 
                        // Handle both old and new format
                        $reviewed = $entry['reviewed'] ?? $entry['topics_reviewed'] ?? 0;
                        $changes = $entry['changes_found'] ?? 0;
                        $date_str = isset($entry['timestamp']) ? date_i18n('M j, g:i a', $entry['timestamp']) : date_i18n('M j, g:i a', strtotime($entry['date']));
                    ?>
                    <tr>
                        <td><?php echo esc_html($date_str); ?></td>
                        <td><code><?php echo esc_html($entry['filter'] ?? 'all'); ?></code></td>
                        <td><?php echo intval($reviewed); ?></td>
                        <td>
                            <?php if ($changes > 0): ?>
                            <span class="fra-badge fra-badge-warning"><?php echo intval($changes); ?></span>
                            <?php else: ?>
                            <span class="fra-badge fra-badge-success">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scheduled Background Review -->
    <?php
    $scheduled_review = FRA_Scheduled_Review::get_instance();
    $schedule_settings = $scheduled_review->get_settings();
    $bg_status = $scheduled_review->get_status();
    $next_run = wp_next_scheduled(FRA_Scheduled_Review::CRON_HOOK);
    ?>
    <div class="fra-card">
        <h2><?php _e('⏰ Scheduled Background Review', 'france-relocation-assistant'); ?></h2>
        
        <p style="color: #666; margin-bottom: 15px;">
            <?php _e('Set up automatic weekly reviews. The plugin will review all topics in the background and email you when updates are ready.', 'france-relocation-assistant'); ?>
        </p>
        
        <?php if ($bg_status['running']): ?>
        <!-- Background Review In Progress -->
        <div class="fra-bg-status fra-bg-running" style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px; color: #856404;">🔄 Background Review In Progress</h4>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #856404;"><?php echo intval($bg_status['processed']); ?>/<?php echo intval($bg_status['total_topics']); ?></div>
                    <div style="font-size: 11px; color: #856404;">Topics Processed</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo intval($bg_status['changes_found']); ?></div>
                    <div style="font-size: 11px; color: #666;">Changes Found</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo intval($bg_status['errors']); ?></div>
                    <div style="font-size: 11px; color: #666;">Errors</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 14px; font-weight: bold; color: #856404;"><?php echo esc_html($bg_status['current_topic']); ?></div>
                    <div style="font-size: 11px; color: #666;">Current Topic</div>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div style="flex: 1; background: #e9ecef; border-radius: 4px; height: 8px;">
                    <div style="background: #ffc107; height: 100%; border-radius: 4px; width: <?php echo $bg_status['total_topics'] > 0 ? round(($bg_status['processed'] / $bg_status['total_topics']) * 100) : 0; ?>%;"></div>
                </div>
                <button type="button" id="fra-cancel-background" class="button" style="color: #c00;">Cancel</button>
                <button type="button" id="fra-refresh-status" class="button">↻ Refresh</button>
            </div>
        </div>
        <?php elseif ($bg_status['completed_at'] && !$bg_status['running']): ?>
        <!-- Last Run Summary -->
        <?php 
        // Get ACTUAL current pending count (not stale bg_status count)
        $actual_pending_count = count($pending_reviews);
        ?>
        <div class="fra-bg-status fra-bg-complete" style="background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px; color: #155724;">✓ Last Background Review Complete</h4>
            <p style="margin: 0; color: #155724;">
                Completed: <?php echo esc_html($bg_status['completed_at']); ?> • 
                <?php echo intval($bg_status['processed']); ?> topics reviewed • 
                <strong><?php echo intval($actual_pending_count); ?> updates pending</strong>
                <?php if ($bg_status['errors'] > 0): ?>
                • <?php echo intval($bg_status['errors']); ?> errors
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Schedule Settings -->
        <div class="fra-schedule-settings" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4 style="margin: 0 0 10px;"><?php _e('Schedule', 'france-relocation-assistant'); ?></h4>
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" id="fra-schedule-enabled" <?php checked($schedule_settings['enabled']); ?>>
                    <span style="margin-left: 8px;"><?php _e('Enable weekly automatic review', 'france-relocation-assistant'); ?></span>
                </label>
                
                <div class="fra-schedule-time" style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                    <select id="fra-schedule-day">
                        <?php 
                        $days = array('sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday', 
                                     'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday');
                        foreach ($days as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php selected($schedule_settings['day'], $value); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span>at</span>
                    <select id="fra-schedule-hour">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                        <option value="<?php echo $h; ?>" <?php selected($schedule_settings['hour'], $h); ?>><?php echo sprintf('%02d', $h); ?></option>
                        <?php endfor; ?>
                    </select>
                    <span>:</span>
                    <select id="fra-schedule-minute">
                        <?php foreach (array(0, 15, 30, 45) as $m): ?>
                        <option value="<?php echo $m; ?>" <?php selected($schedule_settings['minute'], $m); ?>><?php echo sprintf('%02d', $m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($next_run): ?>
                <p style="font-size: 12px; color: #666; margin: 0;">
                    <?php _e('Next scheduled run:', 'france-relocation-assistant'); ?> 
                    <strong><?php echo date_i18n('F j, Y \a\t g:i a', $next_run); ?></strong>
                </p>
                <?php endif; ?>
            </div>
            
            <div>
                <h4 style="margin: 0 0 10px;"><?php _e('Notifications', 'france-relocation-assistant'); ?></h4>
                <label style="display: flex; align-items: center; margin-bottom: 10px;">
                    <input type="checkbox" id="fra-email-notification" <?php checked($schedule_settings['email_notification']); ?>>
                    <span style="margin-left: 8px;"><?php _e('Email me when review completes', 'france-relocation-assistant'); ?></span>
                </label>
                
                <input type="email" id="fra-email-address" value="<?php echo esc_attr($schedule_settings['email_address']); ?>" 
                       placeholder="your@email.com" style="width: 100%;">
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="button" id="fra-save-schedule" class="button button-primary">
                <?php _e('💾 Save Schedule', 'france-relocation-assistant'); ?>
            </button>
            <button type="button" id="fra-start-background-now" class="button" <?php echo !$api_key_configured || $bg_status['running'] ? 'disabled' : ''; ?>>
                <?php _e('▶ Run Now (Background)', 'france-relocation-assistant'); ?>
            </button>
        </div>
        
        <p style="font-size: 12px; color: #666; margin-top: 10px;">
            <?php _e('Background review processes 1 topic every 30 seconds to avoid server timeouts. A full review of 40 topics takes about 20-30 minutes.', 'france-relocation-assistant'); ?>
        </p>
    </div>

    <!-- Pending Reviews Section -->
    <div class="fra-card fra-card-full" style="margin-top: 20px;">
        <div class="fra-card-header">
            <h2>
                <?php _e('📝 Pending Updates', 'france-relocation-assistant'); ?>
                <?php if (!empty($pending_reviews)): ?>
                <span class="fra-count-badge"><?php echo count($pending_reviews); ?></span>
                <?php endif; ?>
            </h2>
            
            <?php if (!empty($pending_reviews)): ?>
            <div class="fra-bulk-actions">
                <button type="button" id="fra-approve-all" class="button button-primary">
                    <?php _e('✓ Approve All', 'france-relocation-assistant'); ?>
                </button>
                <button type="button" id="fra-reject-all" class="button">
                    <?php _e('✗ Reject All', 'france-relocation-assistant'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="fra-pending-reviews-container">
            <?php if (empty($pending_reviews)): ?>
            <div class="fra-empty-state">
                <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #00a32a;"></span>
                <p><?php _e('No pending updates. Your knowledge base is up to date!', 'france-relocation-assistant'); ?></p>
            </div>
            <?php else: ?>
            <div class="fra-reviews-list">
                <?php foreach ($pending_reviews as $review): ?>
                <div class="fra-review-item fra-review-topic" data-review-id="<?php echo esc_attr($review['id']); ?>">
                    <div class="fra-review-header">
                        <div class="fra-review-label">
                            <span class="fra-review-category"><?php echo esc_html(ucfirst($review['category'])); ?></span>
                            <h4><?php echo esc_html($review['topic_name']); ?></h4>
                        </div>
                        <div class="fra-review-badges">
                            <?php 
                            $type_class = 'fra-type-minor';
                            $type_label = 'Minor Update';
                            if ($review['update_type'] === 'significant') {
                                $type_class = 'fra-type-significant';
                                $type_label = 'Significant Update';
                            } elseif ($review['update_type'] === 'rewrite') {
                                $type_class = 'fra-type-rewrite';
                                $type_label = 'Rewrite Needed';
                            }
                            ?>
                            <span class="fra-type-badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                            <span class="fra-confidence-badge fra-confidence-<?php echo esc_attr($review['confidence']); ?>">
                                <?php echo ucfirst($review['confidence']); ?> confidence
                            </span>
                        </div>
                    </div>
                    
                    <div class="fra-review-summary">
                        <strong><?php _e('Changes:', 'france-relocation-assistant'); ?></strong>
                        <?php echo esc_html($review['changes_summary']); ?>
                    </div>
                    
                    <?php if (!empty($review['key_insights'])): ?>
                    <div class="fra-key-insights">
                        <strong><?php _e('💡 Key Insights:', 'france-relocation-assistant'); ?></strong>
                        <ul>
                            <?php foreach ($review['key_insights'] as $insight): ?>
                            <li><?php echo esc_html($insight); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['practice_sources'])): ?>
                    <div class="fra-practice-sources">
                        <strong><?php _e('📚 Practical Sources:', 'france-relocation-assistant'); ?></strong>
                        <div class="fra-source-tags">
                            <?php foreach ($review['practice_sources'] as $source): ?>
                            <span class="fra-source-tag fra-source-<?php echo esc_attr($source['type'] ?? 'other'); ?>">
                                <?php echo esc_html($source['name']); ?>
                                <?php if (!empty($source['date'])): ?>
                                <small>(<?php echo esc_html($source['date']); ?>)</small>
                                <?php endif; ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['sources_checked'])): ?>
                    <div class="fra-review-sources">
                        <strong><?php _e('Sources checked:', 'france-relocation-assistant'); ?></strong>
                        <?php echo esc_html(implode(', ', $review['sources_checked'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="fra-review-diff">
                        <div class="fra-diff-toggle">
                            <button type="button" class="button fra-show-diff"><?php _e('Show Full Content', 'france-relocation-assistant'); ?></button>
                            <?php if (!empty($review['in_practice_content'])): ?>
                            <span class="fra-has-practice-badge">✨ <?php _e('Includes "In Practice" section', 'france-relocation-assistant'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fra-diff-content" style="display: none;">
                            <div class="fra-diff-panels">
                                <div class="fra-diff-panel fra-diff-current">
                                    <h5><?php _e('Current Content', 'france-relocation-assistant'); ?></h5>
                                    <div class="fra-diff-text"><?php echo nl2br(esc_html($review['current_content'])); ?></div>
                                </div>
                                <div class="fra-diff-panel fra-diff-suggested">
                                    <h5><?php _e('Suggested Update', 'france-relocation-assistant'); ?></h5>
                                    <div class="fra-diff-text"><?php echo nl2br(esc_html($review['suggested_content'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fra-review-actions-row">
                        <button type="button" class="button button-primary fra-approve-single" data-id="<?php echo esc_attr($review['id']); ?>">
                            <?php _e('✓ Apply Update', 'france-relocation-assistant'); ?>
                        </button>
                        <button type="button" class="button fra-reject-single" data-id="<?php echo esc_attr($review['id']); ?>">
                            <?php _e('✗ Reject', 'france-relocation-assistant'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- What Gets Reviewed -->
    <div class="fra-card fra-card-full" style="margin-top: 20px;">
        <h2><?php _e('📋 Reviewable Topics', 'france-relocation-assistant'); ?></h2>
        <p><?php _e('The following topics are configured for AI review:', 'france-relocation-assistant'); ?></p>
        
        <div class="fra-topics-grid">
            <?php foreach ($reviewable_topics as $cat_key => $topics): ?>
            <div class="fra-topic-category">
                <h4><?php echo esc_html(ucfirst($cat_key)); ?></h4>
                <ul>
                    <?php foreach ($topics as $topic_key => $topic_info): ?>
                    <li>
                        <strong><?php echo esc_html($topic_info['name']); ?></strong>
                        <span class="fra-topic-facts"><?php echo esc_html(implode(', ', array_slice($topic_info['key_facts'], 0, 3))); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.fra-review-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.fra-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.fra-card-highlight {
    border-color: #2271b1;
    border-width: 2px;
}

.fra-card-full {
    grid-column: 1 / -1;
}

.fra-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.fra-check-list {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.fra-check-list li {
    padding: 5px 0;
    color: #555;
}

.fra-review-scope {
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.fra-count-badge {
    background: #d63638;
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 10px;
}

.fra-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.fra-history-table {
    width: 100%;
    border-collapse: collapse;
}

.fra-history-table th,
.fra-history-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.fra-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.fra-badge-success { background: #d4edda; color: #155724; }
.fra-badge-warning { background: #fff3cd; color: #856404; }

.fra-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.fra-review-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fafafa;
}

.fra-review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.fra-review-category {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.fra-review-label h4 {
    margin: 5px 0 0;
    font-size: 16px;
}

.fra-type-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-right: 8px;
}

.fra-type-minor { background: #e7f3ff; color: #0066cc; }
.fra-type-significant { background: #fff3cd; color: #856404; }
.fra-type-rewrite { background: #f8d7da; color: #721c24; }

.fra-confidence-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
}

.fra-confidence-high { background: #d4edda; color: #155724; }
.fra-confidence-medium { background: #fff3cd; color: #856404; }
.fra-confidence-low { background: #f8d7da; color: #721c24; }

.fra-review-summary,
.fra-review-sources {
    margin: 10px 0;
    font-size: 14px;
    color: #555;
}

.fra-diff-toggle {
    margin: 15px 0;
}

.fra-diff-panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}

.fra-diff-panel {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}

.fra-diff-panel h5 {
    margin: 0 0 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.fra-diff-current { background: #fff5f5; }
.fra-diff-suggested { background: #f0fff0; }

.fra-diff-text {
    font-size: 13px;
    line-height: 1.6;
    white-space: pre-wrap;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.fra-review-actions-row {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.fra-topics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.fra-topic-category {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
}

.fra-topic-category h4 {
    margin: 0 0 10px;
    color: #1e3a8a;
}

.fra-topic-category ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.fra-topic-category li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

.fra-topic-category li:last-child {
    border-bottom: none;
}

.fra-topic-facts {
    display: block;
    font-size: 11px;
    color: #888;
    margin-top: 3px;
}

/* Key Insights */
.fra-key-insights {
    margin: 15px 0;
    padding: 12px 15px;
    background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
    border-radius: 6px;
    border-left: 4px solid #ffc107;
}

.fra-key-insights ul {
    margin: 8px 0 0 0;
    padding-left: 20px;
}

.fra-key-insights li {
    margin: 5px 0;
    font-size: 13px;
    color: #555;
}

/* Practice Sources */
.fra-practice-sources {
    margin: 15px 0;
}

.fra-source-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.fra-source-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    background: #e9ecef;
    color: #495057;
}

.fra-source-tag small {
    opacity: 0.7;
    font-size: 10px;
}

.fra-source-forum { background: #ff6b3514; border: 1px solid #ff6b35; color: #c44d1c; }
.fra-source-blog { background: #4a90d914; border: 1px solid #4a90d9; color: #2d6cb3; }
.fra-source-article { background: #28a74514; border: 1px solid #28a745; color: #1e7b34; }
.fra-source-social { background: #6f42c114; border: 1px solid #6f42c1; color: #5a32a3; }

/* In Practice Badge */
.fra-has-practice-badge {
    display: inline-block;
    margin-left: 10px;
    padding: 4px 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

@media (max-width: 782px) {
    .fra-review-grid {
        grid-template-columns: 1fr;
    }
    
    .fra-diff-panels {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Batched AI Review System - Step by Step
    var reviewState = {
        topics: [],
        currentBatch: 0,
        batchSize: 1,  // Process 1 topic at a time for reliability
        totalReviewed: 0,
        totalChanges: 0,
        errors: [],
        isRunning: false,
        filter: 'all'
    };
    
    // Start review - get list of topics
    $('#fra-start-review').on('click', function() {
        var $btn = $(this);
        var scope = $('#fra-review-scope').val();
        reviewState.filter = scope === 'all' ? 'all' : scope;
        var category = scope === 'all' ? '' : scope;
        
        $btn.prop('disabled', true).text('Loading...');
        $('#fra-review-progress').show();
        $('#fra-review-status-text').html('Preparing review...');
        
        // Reset state
        reviewState = {
            topics: [],
            currentBatch: 0,
            batchSize: 1,  // 1 topic at a time for WordPress.com reliability
            totalReviewed: 0,
            totalChanges: 0,
            errors: [],
            isRunning: true,
            filter: reviewState.filter
        };
        
        // Get list of topics
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_get_review_topics',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                category: category
            },
            success: function(response) {
                if (response.success && response.data.topics) {
                    reviewState.topics = response.data.topics;
                    var totalTopics = reviewState.topics.length;
                    
                    if (totalTopics === 0) {
                        $('#fra-review-status-text').html('No topics to review.');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start AI Review');
                        return;
                    }
                    
                    var totalBatches = Math.ceil(totalTopics / reviewState.batchSize);
                    showBatchPrompt(totalTopics, totalBatches);
                } else {
                    $('#fra-review-status-text').html('Error: ' + (response.data || 'Could not get topics'));
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start AI Review');
                }
            },
            error: function() {
                $('#fra-review-status-text').html('Failed to get topic list.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start AI Review');
            }
        });
    });
    
    function showBatchPrompt(totalTopics, totalBatches) {
        console.log('FRA showBatchPrompt called', reviewState.currentBatch, totalBatches);
        
        var startIdx = reviewState.currentBatch * reviewState.batchSize;
        var endIdx = Math.min(startIdx + reviewState.batchSize, reviewState.topics.length);
        var batchTopics = reviewState.topics.slice(startIdx, endIdx);
        var currentBatchNum = reviewState.currentBatch + 1;
        
        console.log('FRA batchTopics', batchTopics);
        
        if (batchTopics.length === 0) {
            finishReview();
            return;
        }
        
        var topicNames = batchTopics.map(function(t) { return t.name; }).join('<br>• ');
        
        var html = '<div style="text-align:left; padding: 15px; background: #fff; border: 1px solid #ccc; border-radius: 8px;">' +
            '<strong style="font-size: 16px;">📦 Batch ' + currentBatchNum + ' of ' + totalBatches + '</strong><br><br>' +
            '<div style="background:#f5f5f5; padding:10px; border-radius:6px; margin:10px 0; font-size:13px;">' +
            '• ' + topicNames + '</div>' +
            '<div style="margin-top:15px;">' +
            '<button type="button" id="fra-run-batch" class="button button-primary" style="margin-right:10px;">▶ Review This Batch</button>' +
            '<button type="button" id="fra-skip-batch" class="button" style="margin-right:10px;">Skip Batch</button>' +
            '<button type="button" id="fra-stop-review" class="button" style="color:#c00;">Stop Review</button>' +
            '</div>';
        
        if (reviewState.totalReviewed > 0) {
            html += '<div style="margin-top:10px; font-size:12px; color:#666;">' +
                'Progress so far: ' + reviewState.totalReviewed + ' reviewed, ' + 
                reviewState.totalChanges + ' changes found</div>';
        }
        
        html += '</div>';
        
        console.log('FRA setting HTML for batch prompt');
        $('#fra-review-status-text').html(html);
        
        // Bind buttons with delay to ensure DOM is ready
        setTimeout(function() {
            console.log('FRA binding buttons');
            $('#fra-run-batch').off('click').on('click', function() {
                console.log('FRA run batch clicked');
                $(this).prop('disabled', true).text('Reviewing...');
                $('#fra-skip-batch, #fra-stop-review').prop('disabled', true);
                runCurrentBatch();
            });
            
            $('#fra-skip-batch').off('click').on('click', function() {
                console.log('FRA skip batch clicked');
                reviewState.currentBatch++;
                var newTotalBatches = Math.ceil(reviewState.topics.length / reviewState.batchSize);
                showBatchPrompt(reviewState.topics.length, newTotalBatches);
            });
            
            $('#fra-stop-review').off('click').on('click', function() {
                console.log('FRA stop review clicked');
                finishReview();
            });
        }, 100);
    }
    
    function runCurrentBatch() {
        var startIdx = reviewState.currentBatch * reviewState.batchSize;
        var endIdx = Math.min(startIdx + reviewState.batchSize, reviewState.topics.length);
        var batchTopics = reviewState.topics.slice(startIdx, endIdx);
        var currentBatchNum = reviewState.currentBatch + 1;
        var totalBatches = Math.ceil(reviewState.topics.length / reviewState.batchSize);
        
        $('#fra-review-status-text').html(
            '<div style="text-align:center;">' +
            '<div class="spinner is-active" style="float:none; margin:0 auto 10px;"></div>' +
            '<strong>Processing Batch ' + currentBatchNum + ' of ' + totalBatches + '...</strong><br>' +
            '<small style="color:#666;">This may take 2-3 minutes</small>' +
            '</div>'
        );
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 300000, // 5 min timeout per batch
            data: {
                action: 'fra_review_batch',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                topics: JSON.stringify(batchTopics)
            },
            success: function(response) {
                console.log('FRA Batch Response:', response);
                
                if (response.success) {
                    reviewState.totalReviewed += response.data.reviewed || 0;
                    reviewState.totalChanges += response.data.changes_found || 0;
                    if (response.data.errors && response.data.errors.length > 0) {
                        reviewState.errors = reviewState.errors.concat(response.data.errors);
                    }
                    
                    // Show batch results
                    var resultsHtml = '<div style="text-align:left;">' +
                        '<strong>✓ Batch ' + currentBatchNum + ' Complete</strong><br><br>' +
                        '<div style="background:#d4edda; padding:10px; border-radius:6px; margin:10px 0;">' +
                        '• Reviewed: ' + response.data.reviewed + ' topics<br>' +
                        '• Changes found: ' + response.data.changes_found + '</div>';
                    
                    if (response.data.errors && response.data.errors.length > 0) {
                        resultsHtml += '<div style="background:#f8d7da; padding:10px; border-radius:6px; margin:10px 0; font-size:12px;">' +
                            '<strong>Errors:</strong><br>' + response.data.errors.join('<br>') + '</div>';
                    }
                    
                    // Check if more batches
                    reviewState.currentBatch++;
                    if (reviewState.currentBatch * reviewState.batchSize < reviewState.topics.length) {
                        resultsHtml += '<div style="margin-top:15px;">' +
                            '<button type="button" id="fra-next-batch" class="button button-primary">▶ Continue to Next Batch</button> ' +
                            '<button type="button" id="fra-stop-now" class="button">Stop & Save Progress</button>' +
                            '</div>';
                        
                        $('#fra-review-status-text').html(resultsHtml);
                        
                        $('#fra-next-batch').off('click').on('click', function() {
                            showBatchPrompt(reviewState.topics.length, totalBatches);
                        });
                        
                        $('#fra-stop-now').off('click').on('click', function() {
                            finishReview();
                        });
                    } else {
                        // All done
                        finishReview();
                    }
                } else {
                    reviewState.errors.push('Batch failed: ' + (response.data || 'Unknown error'));
                    showBatchError(currentBatchNum, response.data || 'Unknown error', totalBatches);
                }
            },
            error: function(xhr, status, error) {
                console.log('FRA Batch Error:', status, error, xhr.responseText);
                var errMsg = status === 'timeout' ? 'Request timed out' : (error || 'Request failed');
                reviewState.errors.push('Batch ' + currentBatchNum + ': ' + errMsg);
                showBatchError(currentBatchNum, errMsg, totalBatches);
            }
        });
    }
    
    function showBatchError(batchNum, error, totalBatches) {
        var html = '<div style="text-align:left;">' +
            '<strong style="color:#c00;">✗ Batch ' + batchNum + ' Failed</strong><br><br>' +
            '<div style="background:#f8d7da; padding:10px; border-radius:6px; margin:10px 0;">' +
            error + '</div>' +
            '<div style="margin-top:15px;">' +
            '<button type="button" id="fra-retry-batch" class="button button-primary">↻ Retry This Batch</button> ' +
            '<button type="button" id="fra-skip-failed" class="button">Skip & Continue</button> ' +
            '<button type="button" id="fra-stop-failed" class="button">Stop Review</button>' +
            '</div></div>';
        
        $('#fra-review-status-text').html(html);
        
        $('#fra-retry-batch').off('click').on('click', function() {
            runCurrentBatch();
        });
        
        $('#fra-skip-failed').off('click').on('click', function() {
            reviewState.currentBatch++;
            showBatchPrompt(reviewState.topics.length, totalBatches);
        });
        
        $('#fra-stop-failed').off('click').on('click', function() {
            finishReview();
        });
    }
    
    function finishReview() {
        reviewState.isRunning = false;
        
        // Save to history
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_finalize_review',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                reviewed: reviewState.totalReviewed,
                changes_found: reviewState.totalChanges,
                errors: reviewState.errors.length,
                filter: reviewState.filter
            }
        });
        
        var html = '<div style="text-align:center;">' +
            '<div style="font-size:48px; margin-bottom:10px;">✅</div>' +
            '<strong>Review Complete!</strong><br><br>' +
            '<div style="font-size:16px;">' +
            reviewState.totalReviewed + ' topics reviewed<br>' +
            '<strong style="color:#28a745;">' + reviewState.totalChanges + ' updates suggested</strong></div>';
        
        if (reviewState.errors.length > 0) {
            html += '<div style="margin-top:10px; font-size:12px; color:#c00;">' + 
                reviewState.errors.length + ' errors occurred</div>';
            console.log('AI Review errors:', reviewState.errors);
        }
        
        html += '</div>';
        
        $('#fra-review-status-text').html(html);
        $('#fra-start-review').prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start AI Review');
        
        if (reviewState.totalChanges > 0) {
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
    }
    
    // Toggle diff view
    $('.fra-show-diff').on('click', function() {
        var $content = $(this).closest('.fra-review-diff').find('.fra-diff-content');
        $content.slideToggle();
        $(this).text($content.is(':visible') ? 'Hide Changes' : 'Show Changes');
    });
    
    // Approve single
    $('.fra-approve-single').on('click', function() {
        var $btn = $(this);
        var reviewId = $btn.data('id');
        var $item = $btn.closest('.fra-review-item');
        
        $btn.prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_approve_change',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                review_id: reviewId
            },
            success: function(response) {
                if (response.success) {
                    $item.slideUp(function() { $(this).remove(); });
                } else {
                    alert('Error: ' + response.data);
                    $btn.prop('disabled', false).text('✓ Apply Update');
                }
            }
        });
    });
    
    // Reject single
    $('.fra-reject-single').on('click', function() {
        var $btn = $(this);
        var reviewId = $btn.data('id');
        var $item = $btn.closest('.fra-review-item');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_reject_change',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                review_id: reviewId
            },
            success: function(response) {
                $item.slideUp(function() { $(this).remove(); });
            }
        });
    });
    
    // Approve all
    $('#fra-approve-all').on('click', function() {
        if (!confirm('Apply all pending updates?')) return;
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_approve_all',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // Reject all
    $('#fra-reject-all').on('click', function() {
        if (!confirm('Reject all pending updates?')) return;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_reject_all',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>'
            },
            success: function(response) {
                location.reload();
            }
        });
    });
    
    // =====================
    // SCHEDULED REVIEW JS
    // =====================
    
    // Save schedule settings
    $('#fra-save-schedule').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_save_schedule',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>',
                enabled: $('#fra-schedule-enabled').is(':checked') ? 1 : 0,
                day: $('#fra-schedule-day').val(),
                hour: $('#fra-schedule-hour').val(),
                minute: $('#fra-schedule-minute').val(),
                email_notification: $('#fra-email-notification').is(':checked') ? 1 : 0,
                email_address: $('#fra-email-address').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('Schedule saved! Next run: ' + response.data.next_run);
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Could not save schedule'));
                }
            },
            error: function() {
                alert('Failed to save schedule');
            },
            complete: function() {
                $btn.prop('disabled', false).text('💾 Save Schedule');
            }
        });
    });
    
    // Start background review now
    $('#fra-start-background-now').on('click', function() {
        if (!confirm('Start a background review of all topics? This will run in the background and email you when complete.')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Starting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_start_background_review',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Background review started! Processing ' + response.data.total_topics + ' topics. You will receive an email when complete.');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Could not start background review'));
                    $btn.prop('disabled', false).text('▶ Run Now (Background)');
                }
            },
            error: function() {
                alert('Failed to start background review');
                $btn.prop('disabled', false).text('▶ Run Now (Background)');
            }
        });
    });
    
    // Cancel background review
    $('#fra-cancel-background').on('click', function() {
        if (!confirm('Cancel the running background review?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fra_cancel_background_review',
                nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>'
            },
            success: function(response) {
                location.reload();
            }
        });
    });
    
    // Refresh status
    $('#fra-refresh-status').on('click', function() {
        location.reload();
    });
    
    // Auto-refresh if background review is running
    <?php if ($bg_status['running']): ?>
    setTimeout(function() {
        location.reload();
    }, 30000); // Refresh every 30 seconds
    <?php endif; ?>
});
</script>
