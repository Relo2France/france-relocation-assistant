<?php
/**
 * Membership Settings Page
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$defaults = array(
    'enabled' => false,
    'plugin' => 'memberpress', // Default to MemberPress
    'membership_level' => '',
    'price' => '4.00',
    'currency' => 'USD',
    'teaser_message' => 'This premium content is available to members. Get lifetime access for just $4!',
    'upgrade_button_text' => 'Get Lifetime Access - $4',
    'upgrade_url' => '',
    'premium_resources' => array(
        'visa_checklist' => array(
            'enabled' => true,
            'title' => 'Visa Application Checklist',
            'description' => 'Complete checklist for French long-stay visa applications',
            'file' => '',
            'category' => 'visa'
        ),
        'cover_letter' => array(
            'enabled' => true,
            'title' => 'Visa Cover Letter Template',
            'description' => 'Professional cover letter template for visa applications',
            'file' => '',
            'category' => 'visa'
        ),
        'attestation_honor' => array(
            'enabled' => true,
            'title' => 'Attestation on Honor (No Work)',
            'description' => 'Declaration that you will not work in France',
            'file' => '',
            'category' => 'visa'
        ),
        'proof_accommodation' => array(
            'enabled' => true,
            'title' => 'Proof of Accommodation Letter',
            'description' => 'Template for proving your French accommodation',
            'file' => '',
            'category' => 'visa'
        ),
        'financial_resources' => array(
            'enabled' => true,
            'title' => 'Financial Resources Statement',
            'description' => 'Template for proving sufficient financial means',
            'file' => '',
            'category' => 'visa'
        ),
        'mortgage_guide' => array(
            'enabled' => true,
            'title' => 'French Mortgage Evaluation Guide',
            'description' => 'How to evaluate and compare French mortgage offers',
            'file' => '',
            'category' => 'property'
        ),
        'bank_ratings' => array(
            'enabled' => true,
            'title' => 'French Bank Ratings',
            'description' => 'Comparison of French banks for expats',
            'file' => '',
            'category' => 'property'
        ),
        'relocation_checklist' => array(
            'enabled' => true,
            'title' => 'Complete Relocation Checklist',
            'description' => 'Step-by-step checklist for your move to France',
            'file' => '',
            'category' => 'planning'
        ),
        'timeline_guide' => array(
            'enabled' => true,
            'title' => 'Relocation Timeline Guide',
            'description' => 'Detailed timeline for planning your move',
            'file' => '',
            'category' => 'planning'
        ),
        'shipping_customs' => array(
            'enabled' => true,
            'title' => 'Shipping & Customs Guide',
            'description' => 'Guide to shipping belongings and customs procedures',
            'file' => '',
            'category' => 'planning'
        ),
        'tax_strategy' => array(
            'enabled' => true,
            'title' => 'US-France Tax Strategy Guide',
            'description' => 'Tax planning strategies for US citizens in France',
            'file' => '',
            'category' => 'taxes'
        ),
    )
);

$settings = get_option('fra_membership', array());
$settings = wp_parse_args($settings, $defaults);

// Handle form submission
if (isset($_POST['fra_save_membership']) && check_admin_referer('fra_membership_nonce')) {
    $new_settings = array(
        'enabled' => isset($_POST['enabled']),
        'plugin' => sanitize_text_field(wp_unslash($_POST['plugin'] ?? 'pmp')),
        'membership_level' => sanitize_text_field(wp_unslash($_POST['membership_level'] ?? '')),
        'price' => sanitize_text_field(wp_unslash($_POST['price'] ?? '4.00')),
        'currency' => sanitize_text_field(wp_unslash($_POST['currency'] ?? 'USD')),
        'teaser_message' => sanitize_textarea_field(wp_unslash($_POST['teaser_message'] ?? '')),
        'upgrade_button_text' => sanitize_text_field(wp_unslash($_POST['upgrade_button_text'] ?? '')),
        'upgrade_url' => esc_url_raw(wp_unslash($_POST['upgrade_url'] ?? '')),
        'premium_resources' => array(),
        'member_pages' => array()
    );
    
    // Save AI Forms setting separately (used by add-on plugin)
    update_option('fra_enable_ai_forms', isset($_POST['enable_ai_forms']));
    
    // Process member pages
    if (isset($_POST['member_pages']) && is_array($_POST['member_pages'])) {
        foreach ($_POST['member_pages'] as $index => $page) {
            $new_settings['member_pages'][$index] = array(
                'icon' => sanitize_text_field(wp_unslash($page['icon'] ?? '')),
                'title' => sanitize_text_field(wp_unslash($page['title'] ?? '')),
                'url' => esc_url_raw(wp_unslash($page['url'] ?? ''))
            );
        }
        // Remove empty entries
        $new_settings['member_pages'] = array_filter($new_settings['member_pages'], function($page) {
            return !empty($page['title']) && !empty($page['url']);
        });
        $new_settings['member_pages'] = array_values($new_settings['member_pages']); // Re-index
    }
    
    // Process premium resources
    foreach ($defaults['premium_resources'] as $key => $resource) {
        $new_settings['premium_resources'][$key] = array(
            'enabled' => isset($_POST['resource_' . $key . '_enabled']),
            'title' => sanitize_text_field(wp_unslash($_POST['resource_' . $key . '_title'] ?? $resource['title'])),
            'description' => sanitize_textarea_field(wp_unslash($_POST['resource_' . $key . '_description'] ?? $resource['description'])),
            'file' => esc_url_raw(wp_unslash($_POST['resource_' . $key . '_file'] ?? '')),
            'category' => sanitize_text_field(wp_unslash($_POST['resource_' . $key . '_category'] ?? $resource['category']))
        );
    }
    
    update_option('fra_membership', $new_settings);
    $settings = $new_settings;
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('✓ Membership settings saved!', 'france-relocation-assistant') . '</p></div>';
}

// Check for detected plugins
$detected_plugins = array();
if (defined('PMPRO_VERSION')) $detected_plugins[] = 'Paid Memberships Pro';
if (class_exists('WooCommerce') && class_exists('WC_Memberships')) $detected_plugins[] = 'WooCommerce Memberships';
if (class_exists('Restrict_Content_Pro')) $detected_plugins[] = 'Restrict Content Pro';
if (defined('MEPR_VERSION')) $detected_plugins[] = 'MemberPress';
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-money-alt"></span>
        <?php _e('Membership Settings', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('Configure premium membership features. Members get access to downloadable templates, guides, and checklists.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <?php if (empty($detected_plugins)): ?>
    <div class="notice notice-warning">
        <p><strong><?php _e('No membership plugin detected!', 'france-relocation-assistant'); ?></strong></p>
        <p><?php _e('We recommend installing <a href="https://memberpress.com" target="_blank">MemberPress</a> to manage memberships. It offers excellent one-time payment options perfect for lifetime access.', 'france-relocation-assistant'); ?></p>
    </div>
    <?php elseif (in_array('MemberPress', $detected_plugins)): ?>
    <div class="notice notice-success">
        <p><strong><?php _e('✓ MemberPress detected!', 'france-relocation-assistant'); ?></strong> <?php _e('This is our recommended membership plugin.', 'france-relocation-assistant'); ?></p>
    </div>
    <?php else: ?>
    <div class="notice notice-info">
        <p><strong><?php _e('Detected membership plugins:', 'france-relocation-assistant'); ?></strong> <?php echo implode(', ', $detected_plugins); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post">
        <?php wp_nonce_field('fra_membership_nonce'); ?>
        
        <div class="fra-membership-grid">
            <!-- General Settings -->
            <div class="fra-card">
                <h2><?php _e('⚙️ General Settings', 'france-relocation-assistant'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Membership', 'france-relocation-assistant'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" <?php checked($settings['enabled']); ?>>
                                <?php _e('Enable premium membership features', 'france-relocation-assistant'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, certain content will be restricted to members only.', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('AI Form Assistants', 'france-relocation-assistant'); ?></th>
                        <td>
                            <?php $ai_forms_enabled = get_option('fra_enable_ai_forms', false); ?>
                            <label>
                                <input type="checkbox" name="enable_ai_forms" <?php checked($ai_forms_enabled); ?>>
                                <?php _e('Enable AI-powered document assistants', 'france-relocation-assistant'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Requires the <strong>Relo2France AI Forms</strong> add-on plugin.', 'france-relocation-assistant'); ?>
                                <?php if (class_exists('Relo2France_AI_Forms')): ?>
                                    <span style="color: #00a32a;">✓ <?php _e('Add-on installed', 'france-relocation-assistant'); ?></span>
                                <?php else: ?>
                                    <span style="color: #dba617;">⚠ <?php _e('Add-on not installed', 'france-relocation-assistant'); ?></span>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plugin"><?php _e('Membership Plugin', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <select name="plugin" id="plugin">
                                <option value="memberpress" <?php selected($settings['plugin'], 'memberpress'); ?>>MemberPress (Recommended)</option>
                                <option value="pmp" <?php selected($settings['plugin'], 'pmp'); ?>>Paid Memberships Pro</option>
                                <option value="woocommerce" <?php selected($settings['plugin'], 'woocommerce'); ?>>WooCommerce Memberships</option>
                                <option value="restrict_content" <?php selected($settings['plugin'], 'restrict_content'); ?>>Restrict Content Pro</option>
                                <option value="manual" <?php selected($settings['plugin'], 'manual'); ?>>Manual (User Role Based)</option>
                            </select>
                            <p class="description" id="plugin-help"></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="membership_level"><?php _e('Membership Level/ID', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="membership_level" id="membership_level" value="<?php echo esc_attr($settings['membership_level']); ?>" class="regular-text">
                            <p class="description" id="level-help">
                                <?php _e('For MemberPress: Enter the Membership ID (found in MemberPress → Memberships, look at the ID column). Leave blank to allow any active membership.', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Pricing Display -->
            <div class="fra-card">
                <h2><?php _e('💰 Pricing Display', 'france-relocation-assistant'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><label for="price"><?php _e('Price', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="price" id="price" value="<?php echo esc_attr($settings['price']); ?>" class="small-text">
                            <select name="currency" style="width: auto;">
                                <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD ($)</option>
                                <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR (€)</option>
                                <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>GBP (£)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="upgrade_url"><?php _e('Upgrade/Checkout URL', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="url" name="upgrade_url" id="upgrade_url" value="<?php echo esc_attr($settings['upgrade_url']); ?>" class="large-text">
                            <p class="description"><?php _e('URL to your membership checkout page.', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="upgrade_button_text"><?php _e('Upgrade Button Text', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <input type="text" name="upgrade_button_text" id="upgrade_button_text" value="<?php echo esc_attr($settings['upgrade_button_text']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="teaser_message"><?php _e('Teaser Message', 'france-relocation-assistant'); ?></label></th>
                        <td>
                            <textarea name="teaser_message" id="teaser_message" rows="3" class="large-text"><?php echo esc_textarea($settings['teaser_message']); ?></textarea>
                            <p class="description"><?php _e('Shown to non-members when they try to access premium content.', 'france-relocation-assistant'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Member Pages Link -->
            <div class="fra-card">
                <h2><?php _e('📄 Member Pages Navigation', 'france-relocation-assistant'); ?></h2>
                <p><?php _e('Configure the member-only links that appear in the assistant sidebar.', 'france-relocation-assistant'); ?></p>
                
                <?php
                $member_pages = isset($settings['member_pages']) ? $settings['member_pages'] : array();
                $page_count = count(array_filter($member_pages, function($p) { return !empty($p['title']); }));
                ?>
                
                <div style="margin: 15px 0; padding: 12px; background: #f0f0f1; border-radius: 6px;">
                    <span style="font-size: 24px; margin-right: 10px;">⭐</span>
                    <strong><?php echo $page_count; ?></strong> <?php _e('member pages configured', 'france-relocation-assistant'); ?>
                </div>
                
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-customizer'); ?>" class="button button-primary">
                    <?php _e('Edit Member Pages in Customizer →', 'france-relocation-assistant'); ?>
                </a>
                <p class="description" style="margin-top: 10px;">
                    <?php _e('Member pages are now configured in the Customizer under the Navigation tab.', 'france-relocation-assistant'); ?>
                </p>
            </div>
            
            <!-- Premium Resources -->
            <div class="fra-card fra-card-full">
                <h2><?php _e('📦 Premium Resources', 'france-relocation-assistant'); ?></h2>
                <p class="description"><?php _e('Configure the downloadable resources available to members. Upload files to your Media Library and paste the URLs here.', 'france-relocation-assistant'); ?></p>
                
                <div class="fra-resources-grid">
                    <?php 
                    $categories = array(
                        'visa' => '📋 Visa & Immigration',
                        'property' => '🏠 Property & Finance',
                        'planning' => '📅 Relocation Planning',
                        'taxes' => '💰 Tax Strategy'
                    );
                    
                    foreach ($categories as $cat_key => $cat_label):
                        $cat_resources = array_filter($settings['premium_resources'], function($r) use ($cat_key) {
                            return ($r['category'] ?? '') === $cat_key;
                        });
                    ?>
                    <div class="fra-resource-category">
                        <h3><?php echo $cat_label; ?></h3>
                        
                        <?php foreach ($settings['premium_resources'] as $key => $resource): 
                            if (($resource['category'] ?? '') !== $cat_key) continue;
                        ?>
                        <div class="fra-resource-item">
                            <div class="fra-resource-header">
                                <label>
                                    <input type="checkbox" name="resource_<?php echo $key; ?>_enabled" <?php checked($resource['enabled']); ?>>
                                    <strong><?php echo esc_html($resource['title']); ?></strong>
                                </label>
                            </div>
                            <div class="fra-resource-fields">
                                <input type="hidden" name="resource_<?php echo $key; ?>_category" value="<?php echo esc_attr($resource['category']); ?>">
                                <input type="text" name="resource_<?php echo $key; ?>_title" value="<?php echo esc_attr($resource['title']); ?>" placeholder="Title" class="regular-text">
                                <input type="text" name="resource_<?php echo $key; ?>_description" value="<?php echo esc_attr($resource['description']); ?>" placeholder="Description" class="large-text">
                                <div class="fra-file-input">
                                    <input type="url" name="resource_<?php echo $key; ?>_file" value="<?php echo esc_attr($resource['file']); ?>" placeholder="File URL (upload to Media Library)" class="large-text">
                                    <button type="button" class="button fra-upload-btn" data-target="resource_<?php echo $key; ?>_file"><?php _e('Upload', 'france-relocation-assistant'); ?></button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shortcodes Reference -->
            <div class="fra-card fra-card-full">
                <h2><?php _e('📝 Shortcodes', 'france-relocation-assistant'); ?></h2>
                
                <table class="fra-shortcode-table">
                    <thead>
                        <tr>
                            <th><?php _e('Shortcode', 'france-relocation-assistant'); ?></th>
                            <th><?php _e('Description', 'france-relocation-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[fra_premium]...[/fra_premium]</code></td>
                            <td><?php _e('Wrap content to restrict to members only. Non-members see the teaser message.', 'france-relocation-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[fra_premium_resources]</code></td>
                            <td><?php _e('Display a grid of all premium downloadable resources (for members) or upgrade prompt (for non-members).', 'france-relocation-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[fra_upgrade_button]</code></td>
                            <td><?php _e('Display the upgrade button/link.', 'france-relocation-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[fra_member_only]...[/fra_member_only]</code></td>
                            <td><?php _e('Content only visible to logged-in members (hidden completely for others).', 'france-relocation-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[fra_non_member_only]...[/fra_non_member_only]</code></td>
                            <td><?php _e('Content only visible to non-members (e.g., promotional messages).', 'france-relocation-assistant'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Integration Guide -->
            <div class="fra-card fra-card-full">
                <h2><?php _e('🔧 Setup Guide (MemberPress)', 'france-relocation-assistant'); ?></h2>
                
                <div class="fra-setup-steps">
                    <div class="fra-step">
                        <div class="fra-step-number">1</div>
                        <div class="fra-step-content">
                            <h4><?php _e('Install MemberPress', 'france-relocation-assistant'); ?></h4>
                            <p><?php _e('Purchase and install MemberPress from memberpress.com, then activate the plugin.', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                    
                    <div class="fra-step">
                        <div class="fra-step-number">2</div>
                        <div class="fra-step-content">
                            <h4><?php _e('Create a Membership', 'france-relocation-assistant'); ?></h4>
                            <p><?php _e('Go to MemberPress → Memberships → Add New. Name it "Lifetime Access", set a one-time price (e.g., $4.00), and publish.', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                    
                    <div class="fra-step">
                        <div class="fra-step-number">3</div>
                        <div class="fra-step-content">
                            <h4><?php _e('Configure Payment Gateway', 'france-relocation-assistant'); ?></h4>
                            <p><?php _e('Go to MemberPress → Settings → Payments. Add Stripe or PayPal and connect your account.', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                    
                    <div class="fra-step">
                        <div class="fra-step-number">4</div>
                        <div class="fra-step-content">
                            <h4><?php _e('Get Your Membership ID', 'france-relocation-assistant'); ?></h4>
                            <p><?php _e('Go to MemberPress → Memberships. Note the ID number in the first column. Enter it in "Membership Level/ID" above.', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                    
                    <div class="fra-step">
                        <div class="fra-step-number">5</div>
                        <div class="fra-step-content">
                            <h4><?php _e('Set Checkout URL', 'france-relocation-assistant'); ?></h4>
                            <p><?php _e('Copy the registration URL from your Membership edit page and paste it in "Upgrade/Checkout URL" above.', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="fra-customizer-actions">
            <input type="submit" name="fra_save_membership" class="button button-primary button-hero" value="<?php _e('💾 Save Membership Settings', 'france-relocation-assistant'); ?>">
        </div>
    </form>
</div>

<style>
.fra-membership-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
@media (max-width: 1200px) {
    .fra-membership-grid { grid-template-columns: 1fr; }
}
.fra-card-full { grid-column: 1 / -1; }

.fra-resources-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 15px;
}
@media (max-width: 1000px) {
    .fra-resources-grid { grid-template-columns: 1fr; }
}

.fra-resource-category {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}
.fra-resource-category h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #ddd;
}

.fra-resource-item {
    background: #fff;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 10px;
    border: 1px solid #e0e0e0;
}
.fra-resource-header {
    margin-bottom: 10px;
}
.fra-resource-fields {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.fra-file-input {
    display: flex;
    gap: 8px;
}
.fra-file-input input {
    flex: 1;
}

.fra-shortcode-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
.fra-shortcode-table th,
.fra-shortcode-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
.fra-shortcode-table th {
    background: #f5f5f5;
    font-weight: 600;
}
.fra-shortcode-table code {
    background: #f0f0f0;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
}

.fra-setup-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}
.fra-step {
    display: flex;
    gap: 15px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}
.fra-step-number {
    width: 32px;
    height: 32px;
    background: #ff6b00;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}
.fra-step-content h4 {
    margin: 0 0 5px 0;
}
.fra-step-content p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.fra-customizer-actions {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    position: sticky;
    bottom: 20px;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Media uploader for file fields
    $('.fra-upload-btn').on('click', function(e) {
        e.preventDefault();
        var targetField = $(this).data('target');
        var $input = $('input[name="' + targetField + '"]');
        
        var mediaUploader = wp.media({
            title: '<?php _e('Select or Upload File', 'france-relocation-assistant'); ?>',
            button: { text: '<?php _e('Use This File', 'france-relocation-assistant'); ?>' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // Dynamic help text based on selected membership plugin
    var helpTexts = {
        'memberpress': {
            'level': '<?php _e('For MemberPress: Enter the Membership ID (found in MemberPress → Memberships, look at the ID column). Leave blank to allow any active membership. Supports comma-separated IDs.', 'france-relocation-assistant'); ?>',
            'plugin': '<?php _e('MemberPress is our recommended plugin. Supports one-time lifetime payments with Stripe or PayPal.', 'france-relocation-assistant'); ?>'
        },
        'pmp': {
            'level': '<?php _e('For Paid Memberships Pro: Enter the level ID (e.g., "1"). Leave blank for any level.', 'france-relocation-assistant'); ?>',
            'plugin': '<?php _e('Paid Memberships Pro is a popular free option with paid add-ons.', 'france-relocation-assistant'); ?>'
        },
        'woocommerce': {
            'level': '<?php _e('For WooCommerce Memberships: Enter the plan ID. Leave blank for any plan.', 'france-relocation-assistant'); ?>',
            'plugin': '<?php _e('Requires WooCommerce and WooCommerce Memberships extension.', 'france-relocation-assistant'); ?>'
        },
        'restrict_content': {
            'level': '<?php _e('For Restrict Content Pro: Level filtering not required - checks any active membership.', 'france-relocation-assistant'); ?>',
            'plugin': '<?php _e('Restrict Content Pro is a premium plugin with good documentation.', 'france-relocation-assistant'); ?>'
        },
        'manual': {
            'level': '<?php _e('For Manual mode: Enter the user role (e.g., "premium_member" or "subscriber").', 'france-relocation-assistant'); ?>',
            'plugin': '<?php _e('Manual mode uses WordPress user roles. Create custom roles with a plugin like Members.', 'france-relocation-assistant'); ?>'
        }
    };
    
    function updateHelpText() {
        var plugin = $('#plugin').val();
        if (helpTexts[plugin]) {
            $('#level-help').text(helpTexts[plugin].level);
            $('#plugin-help').text(helpTexts[plugin].plugin);
        }
    }
    
    $('#plugin').on('change', updateHelpText);
    updateHelpText(); // Run on page load
});
</script>
