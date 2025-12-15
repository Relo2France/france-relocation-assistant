<?php
/**
 * Admin Page Template
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-airplane"></span>
        <?php _e('France Relocation Assistant', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('Manage your France Relocation Assistant settings and view knowledge base status.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <div class="fra-admin-grid">
        <!-- Quick Start Card -->
        <div class="fra-card fra-card-highlight">
            <h2><?php _e('🚀 Quick Start', 'france-relocation-assistant'); ?></h2>
            
            <p><?php _e('Add the assistant to any page or post using this shortcode:', 'france-relocation-assistant'); ?></p>
            
            <div class="fra-shortcode-box">
                <code id="fra-shortcode">[france_relocation_assistant]</code>
                <button type="button" class="button fra-copy-btn" onclick="navigator.clipboard.writeText('[france_relocation_assistant]'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000);">
                    <?php _e('Copy', 'france-relocation-assistant'); ?>
                </button>
            </div>
            
            <h4><?php _e('Shortcode Options:', 'france-relocation-assistant'); ?></h4>
            <ul class="fra-shortcode-options">
                <li><code>show_day_counter="true"</code> - <?php _e('Enable/disable day counter tool', 'france-relocation-assistant'); ?></li>
            </ul>
        </div>
        
        <!-- Status Card -->
        <div class="fra-card">
            <h2><?php _e('📊 Status', 'france-relocation-assistant'); ?></h2>
            
            <table class="fra-status-table">
                <tr>
                    <th><?php _e('Plugin Version:', 'france-relocation-assistant'); ?></th>
                    <td><span class="fra-version-badge">v<?php echo FRA_VERSION; ?></span></td>
                </tr>
                <tr>
                    <th><?php _e('Last KB Update:', 'france-relocation-assistant'); ?></th>
                    <td>
                        <?php 
                        if (!empty($last_update['timestamp'])) {
                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_update['timestamp']);
                        } else {
                            _e('Never', 'france-relocation-assistant');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Status:', 'france-relocation-assistant'); ?></th>
                    <td>
                        <span class="fra-status-badge fra-status-<?php echo esc_attr($last_update['status'] ?? 'unknown'); ?>">
                            <?php echo esc_html(ucfirst($last_update['status'] ?? 'Unknown')); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('AI Integration:', 'france-relocation-assistant'); ?></th>
                    <td>
                        <?php 
                        $ai_enabled = get_option('fra_enable_ai', false);
                        $api_key = get_option('fra_api_key', '');
                        // Handle various boolean representations
                        $is_enabled = ($ai_enabled === true || $ai_enabled === '1' || $ai_enabled === 1 || $ai_enabled === 'yes' || $ai_enabled === 'on');
                        if ($is_enabled && !empty($api_key)) {
                            echo '<span class="fra-status-badge fra-status-success">' . __('Enabled', 'france-relocation-assistant') . '</span>';
                        } else {
                            echo '<span class="fra-status-badge fra-status-warning">' . __('Disabled', 'france-relocation-assistant') . '</span>';
                            echo ' <a href="' . admin_url('admin.php?page=france-relocation-assistant-settings') . '">' . __('Configure →', 'france-relocation-assistant') . '</a>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            
            <?php if (!empty($last_update['message'])): ?>
                <div class="fra-message <?php echo $last_update['status'] === 'error' ? 'fra-message-error' : 'fra-message-success'; ?>">
                    <?php echo esc_html($last_update['message']); ?>
                    <?php if (isset($last_update['added_count']) || isset($last_update['updated_count'])): ?>
                        <div style="margin-top: 8px;">
                            <?php if (!empty($last_update['added_count'])): ?>
                                <span class="fra-count-badge fra-count-added">+<?php echo intval($last_update['added_count']); ?> added</span>
                            <?php endif; ?>
                            <?php if (!empty($last_update['updated_count'])): ?>
                                <span class="fra-count-badge fra-count-updated"><?php echo intval($last_update['updated_count']); ?> updated</span>
                            <?php endif; ?>
                            <?php if (empty($last_update['added_count']) && empty($last_update['updated_count']) && $last_update['status'] === 'success'): ?>
                                <span class="fra-count-badge fra-count-none">No changes needed</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Knowledge Base Stats -->
        <div class="fra-card">
            <h2><?php _e('📚 Knowledge Base', 'france-relocation-assistant'); ?></h2>
            
            <?php
            $total_topics = 0;
            $categories_count = 0;
            foreach ($knowledge_base as $category => $topics) {
                $categories_count++;
                $total_topics += count($topics);
            }
            
            // Get AI Review data
            $review_history = get_option('fra_review_history', array());
            $last_review = !empty($review_history) ? end($review_history) : null;
            $pending_reviews = get_option('fra_pending_reviews', array());
            ?>
            
            <div class="fra-stats-grid">
                <div class="fra-stat">
                    <span class="fra-stat-number"><?php echo $categories_count; ?></span>
                    <span class="fra-stat-label"><?php _e('Categories', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-stat">
                    <span class="fra-stat-number"><?php echo $total_topics; ?></span>
                    <span class="fra-stat-label"><?php _e('Topics', 'france-relocation-assistant'); ?></span>
                </div>
            </div>
            
            <!-- AI Review Status -->
            <div class="fra-ai-review-status" style="margin: 15px 0; padding: 12px; background: #f9f9f9; border-radius: 6px;">
                <h4 style="margin: 0 0 8px 0; font-size: 13px;"><?php _e('🤖 AI Review Status', 'france-relocation-assistant'); ?></h4>
                <?php if ($last_review): ?>
                <p style="margin: 0; font-size: 13px; color: #555;">
                    <?php _e('Last review:', 'france-relocation-assistant'); ?> 
                    <strong><?php echo date_i18n(get_option('date_format'), $last_review['timestamp']); ?></strong>
                    — <?php echo intval($last_review['verified']); ?> <?php _e('verified', 'france-relocation-assistant'); ?>, 
                    <?php echo intval($last_review['changes_found']); ?> <?php _e('changes found', 'france-relocation-assistant'); ?>
                </p>
                <?php else: ?>
                <p style="margin: 0; font-size: 13px; color: #888;">
                    <?php _e('No AI reviews yet. Run your first review to verify data.', 'france-relocation-assistant'); ?>
                </p>
                <?php endif; ?>
                
                <?php if (count($pending_reviews) > 0): ?>
                <div style="margin-top: 8px;">
                    <span class="fra-count-badge fra-count-updated" style="font-size: 11px;">
                        <?php echo count($pending_reviews); ?> <?php _e('pending changes to review', 'france-relocation-assistant'); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="fra-actions" style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-ai-review'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-superhero" style="margin-top: 3px;"></span>
                    <?php _e('AI Review', 'france-relocation-assistant'); ?>
                    <?php if (count($pending_reviews) > 0): ?>
                    <span class="fra-pending-badge"><?php echo count($pending_reviews); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-kb'); ?>" class="button">
                    <?php _e('Expand KB', 'france-relocation-assistant'); ?>
                </a>
            </div>
            
            <p class="description" style="margin-top: 10px;">
                <?php _e('Use AI Review to automatically verify and update data points like visa fees, tax thresholds, and other changing information.', 'france-relocation-assistant'); ?>
            </p>
        </div>
        
        <!-- Quick Links -->
        <div class="fra-card">
            <h2><?php _e('⚡ Quick Links', 'france-relocation-assistant'); ?></h2>
            
            <div class="fra-quick-links">
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-settings'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span><?php _e('Settings', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('AI, API keys, general options', 'france-relocation-assistant'); ?></small>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-customizer'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <span><?php _e('Customizer', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('Colors, text, layout', 'france-relocation-assistant'); ?></small>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-kb'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-book"></span>
                    <span><?php _e('Knowledge Base', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('Add/edit topics', 'france-relocation-assistant'); ?></small>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-seo'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-search"></span>
                    <span><?php _e('SEO Settings', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('Schema, meta tags', 'france-relocation-assistant'); ?></small>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-membership'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-money-alt"></span>
                    <span><?php _e('Membership', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('Premium content settings', 'france-relocation-assistant'); ?></small>
                </a>
                
                <?php if (class_exists('Relo2France_AI_Forms')): ?>
                <a href="<?php echo admin_url('admin.php?page=rfai-forms'); ?>" class="fra-quick-link">
                    <span class="dashicons dashicons-format-aside"></span>
                    <span><?php _e('AI Forms', 'france-relocation-assistant'); ?></span>
                    <small><?php _e('Document assistants', 'france-relocation-assistant'); ?></small>
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Data Sources -->
        <div class="fra-card fra-card-full">
            <h2><?php _e('📖 Official Data Sources', 'france-relocation-assistant'); ?></h2>
            
            <p><?php _e('Information in this assistant is compiled from official French and US government sources:', 'france-relocation-assistant'); ?></p>
            
            <div class="fra-sources-grid">
                <div class="fra-source">
                    <strong>🇫🇷 France-Visas.gouv.fr</strong>
                    <span><?php _e('Visa requirements & applications', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-source">
                    <strong>🇫🇷 Service-Public.fr</strong>
                    <span><?php _e('Administrative procedures', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-source">
                    <strong>🇫🇷 Ameli.fr</strong>
                    <span><?php _e('Healthcare enrollment (PUMA)', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-source">
                    <strong>🇫🇷 Impots.gouv.fr</strong>
                    <span><?php _e('French tax obligations', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-source">
                    <strong>🇺🇸 IRS.gov</strong>
                    <span><?php _e('US tax filing requirements', 'france-relocation-assistant'); ?></span>
                </div>
                <div class="fra-source">
                    <strong>🇺🇸 State.gov</strong>
                    <span><?php _e('US passport & travel info', 'france-relocation-assistant'); ?></span>
                </div>
            </div>
            
            <p class="fra-disclaimer">
                <small><?php _e('Note: Laws and requirements change frequently. Always verify current information with official sources and consult qualified professionals.', 'france-relocation-assistant'); ?></small>
            </p>
        </div>
    </div>
    
    <div class="fra-admin-footer">
        <p>
            <?php _e('France Relocation Assistant', 'france-relocation-assistant'); ?> v<?php echo FRA_VERSION; ?>
        </p>
    </div>
</div>

<style>
.fra-card-highlight {
    border-left: 4px solid #ff6b00;
}
.fra-shortcode-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f5f5f5;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
}
.fra-shortcode-box code {
    flex: 1;
    font-size: 14px;
    background: #fff;
    padding: 10px 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}
.fra-copy-btn {
    white-space: nowrap;
}
.fra-shortcode-options {
    margin: 10px 0 0 20px;
}
.fra-shortcode-options li {
    margin-bottom: 5px;
}
.fra-shortcode-options code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
.fra-version-badge {
    background: #1e3a5f;
    color: #fff;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
}
.fra-quick-links {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.fra-quick-link {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    text-decoration: none;
    color: #1e3a5f;
    transition: all 0.2s ease;
    border: 1px solid #eee;
}
.fra-quick-link:hover {
    background: #f0f0f0;
    border-color: #ddd;
    transform: translateY(-2px);
}
.fra-quick-link .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    margin-bottom: 8px;
    color: #ff6b00;
}
.fra-quick-link span:not(.dashicons) {
    font-weight: 600;
    margin-bottom: 4px;
}
.fra-quick-link small {
    color: #666;
    font-size: 12px;
}
.fra-sources-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin: 20px 0;
}
@media (max-width: 900px) {
    .fra-sources-grid { grid-template-columns: repeat(2, 1fr); }
}
.fra-source {
    background: #f9f9f9;
    padding: 12px 15px;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.fra-source strong {
    font-size: 13px;
}
.fra-source span {
    font-size: 12px;
    color: #666;
}
.fra-disclaimer {
    color: #666;
    font-style: italic;
    margin-top: 10px;
}
.fra-count-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    margin-right: 5px;
}
.fra-count-added {
    background: #dcfce7;
    color: #15803d;
}
.fra-count-updated {
    background: #e0f2fe;
    color: #0369a1;
}
.fra-count-none {
    background: #f3f4f6;
    color: #6b7280;
}
.fra-pending-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background: #fff;
    color: #1e3a5f;
    border-radius: 9px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 6px;
}
</style>
