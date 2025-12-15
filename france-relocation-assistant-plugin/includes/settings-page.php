<?php
/**
 * API Settings Page Template
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_key = get_option('fra_api_key', '');
$api_model = get_option('fra_api_model', 'claude-sonnet-4-20250514');
$enable_ai = get_option('fra_enable_ai', false);
$github_repo = get_option('fra_github_repo', '');
$update_url = get_option('fra_update_url', '');
$membership_url = get_option('fra_membership_url', '/membership/');

// Handle form submission
if (isset($_POST['fra_save_settings']) && check_admin_referer('fra_settings_nonce')) {
    $api_key = sanitize_text_field(wp_unslash($_POST['fra_api_key'] ?? ''));
    $api_model = sanitize_text_field(wp_unslash($_POST['fra_api_model'] ?? 'claude-sonnet-4-20250514'));
    $enable_ai = isset($_POST['fra_enable_ai']) ? true : false;
    $github_repo = sanitize_text_field(wp_unslash($_POST['fra_github_repo'] ?? ''));
    $update_url = esc_url_raw(wp_unslash($_POST['fra_update_url'] ?? ''));
    $membership_url = esc_url_raw(wp_unslash($_POST['fra_membership_url'] ?? '/membership/'));
    
    update_option('fra_api_key', $api_key);
    update_option('fra_api_model', $api_model);
    update_option('fra_enable_ai', $enable_ai);
    update_option('fra_github_repo', $github_repo);
    update_option('fra_update_url', $update_url);
    update_option('fra_membership_url', $membership_url);
    
    // Clear update cache when settings change
    delete_transient('fra_update_check');
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'france-relocation-assistant') . '</p></div>';
}

// Test API connection if requested
$test_result = null;
if (isset($_POST['fra_test_api']) && check_admin_referer('fra_settings_nonce')) {
    if (empty($api_key)) {
        $test_result = array('success' => false, 'message' => 'Please enter an API key first.');
    } else {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $api_model,
                'max_tokens' => 50,
                'messages' => array(
                    array('role' => 'user', 'content' => 'Say "API connection successful" and nothing else.')
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            $test_result = array('success' => false, 'message' => 'Connection failed: ' . $response->get_error_message());
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['error'])) {
                $test_result = array('success' => false, 'message' => 'API Error: ' . ($body['error']['message'] ?? 'Unknown error'));
            } elseif (isset($body['content'][0]['text'])) {
                $test_result = array('success' => true, 'message' => 'Connection successful! Response: ' . $body['content'][0]['text']);
            } else {
                $test_result = array('success' => false, 'message' => 'Unexpected response format');
            }
        }
    }
}
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-admin-generic"></span>
        <?php _e('API Settings', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('Configure the Anthropic Claude API to enable AI-powered responses when the knowledge base doesn\'t have a confident answer.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <?php if ($test_result): ?>
        <div class="notice <?php echo $test_result['success'] ? 'notice-success' : 'notice-error'; ?> is-dismissible">
            <p><?php echo esc_html($test_result['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="fra-admin-grid">
        <div class="fra-card fra-card-full">
            <h2><?php _e('Claude API Configuration', 'france-relocation-assistant'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('fra_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fra_enable_ai"><?php _e('Enable AI Responses', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="fra_enable_ai" id="fra_enable_ai" value="1" <?php checked($enable_ai, true); ?>>
                                <?php _e('Enable Tier 2 AI responses when knowledge base confidence is low', 'france-relocation-assistant'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, queries that don\'t match the knowledge base will be sent to Claude for a response. This incurs API costs.', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fra_api_key"><?php _e('Anthropic API Key', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="fra_api_key" id="fra_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off">
                            <button type="button" class="button" onclick="toggleApiKeyVisibility()" id="toggle-api-key">
                                <?php _e('Show', 'france-relocation-assistant'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Get your API key from', 'france-relocation-assistant'); ?> 
                                <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fra_api_model"><?php _e('Claude Model', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="fra_api_model" id="fra_api_model">
                                <option value="claude-sonnet-4-20250514" <?php selected($api_model, 'claude-sonnet-4-20250514'); ?>>
                                    Claude Sonnet 4 (Recommended - Best balance)
                                </option>
                                <option value="claude-haiku-4-20250514" <?php selected($api_model, 'claude-haiku-4-20250514'); ?>>
                                    Claude Haiku 4 (Faster, cheaper)
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Sonnet: ~$0.015/query | Haiku: ~$0.004/query', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fra_membership_url"><?php _e('Membership Signup URL', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="fra_membership_url" id="fra_membership_url" value="<?php echo esc_attr($membership_url); ?>" class="regular-text" placeholder="/membership/">
                            <p class="description">
                                <?php _e('URL where users can sign up for membership. Used in upsell messages when non-members request premium features like custom document creation.', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="fra_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'france-relocation-assistant'); ?>">
                    <input type="submit" name="fra_test_api" class="button" value="<?php _e('Test API Connection', 'france-relocation-assistant'); ?>">
                </p>
            </form>
        </div>
        
        <div class="fra-card">
            <h2><?php _e('How It Works', 'france-relocation-assistant'); ?></h2>
            
            <h3><?php _e('Tier 1: Knowledge Base (Free)', 'france-relocation-assistant'); ?></h3>
            <p><?php _e('Most queries are answered instantly from the built-in knowledge base at no cost. This covers:', 'france-relocation-assistant'); ?></p>
            <ul>
                <li><?php _e('Visa requirements and processes', 'france-relocation-assistant'); ?></li>
                <li><?php _e('Property purchase procedures', 'france-relocation-assistant'); ?></li>
                <li><?php _e('Healthcare enrollment', 'france-relocation-assistant'); ?></li>
                <li><?php _e('Tax obligations', 'france-relocation-assistant'); ?></li>
                <li><?php _e('And more...', 'france-relocation-assistant'); ?></li>
            </ul>
            
            <h3><?php _e('Tier 2: AI Responses (API Cost)', 'france-relocation-assistant'); ?></h3>
            <p><?php _e('When the knowledge base doesn\'t have a confident answer, the query can be sent to Claude for a personalized response. This requires:', 'france-relocation-assistant'); ?></p>
            <ul>
                <li><?php _e('An Anthropic API key', 'france-relocation-assistant'); ?></li>
                <li><?php _e('API credits (pay-as-you-go)', 'france-relocation-assistant'); ?></li>
            </ul>
        </div>
        
        <div class="fra-card">
            <h2><?php _e('Estimated Costs', 'france-relocation-assistant'); ?></h2>
            
            <table class="fra-cost-table">
                <thead>
                    <tr>
                        <th><?php _e('Usage Level', 'france-relocation-assistant'); ?></th>
                        <th><?php _e('Sonnet', 'france-relocation-assistant'); ?></th>
                        <th><?php _e('Haiku', 'france-relocation-assistant'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Personal (100 queries/mo)', 'france-relocation-assistant'); ?></td>
                        <td>~$1.50</td>
                        <td>~$0.40</td>
                    </tr>
                    <tr>
                        <td><?php _e('Moderate (500 queries/mo)', 'france-relocation-assistant'); ?></td>
                        <td>~$7.50</td>
                        <td>~$2.00</td>
                    </tr>
                    <tr>
                        <td><?php _e('High (2,000 queries/mo)', 'france-relocation-assistant'); ?></td>
                        <td>~$30</td>
                        <td>~$8.00</td>
                    </tr>
                </tbody>
            </table>
            
            <p class="description">
                <?php _e('Note: 70-80% of queries are typically answered by the knowledge base at no cost.', 'france-relocation-assistant'); ?>
            </p>
        </div>
        
        <!-- Plugin Updates Section -->
        <div class="fra-card fra-card-full">
            <h2><?php _e('🔄 Plugin Updates', 'france-relocation-assistant'); ?></h2>
            
            <p class="description">
                <?php _e('Configure automatic updates from GitHub or a custom update server. This allows you to receive updates without reinstalling the plugin.', 'france-relocation-assistant'); ?>
            </p>
            
            <form method="post" action="">
                <?php wp_nonce_field('fra_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fra_github_repo"><?php _e('GitHub Repository', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="fra_github_repo" id="fra_github_repo" value="<?php echo esc_attr($github_repo); ?>" class="regular-text" placeholder="username/repository">
                            <p class="description">
                                <?php _e('Enter in format: username/repository (e.g., yourusername/france-relocation-assistant)', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="fra_update_url"><?php _e('Custom Update URL', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="fra_update_url" id="fra_update_url" value="<?php echo esc_attr($update_url); ?>" class="large-text" placeholder="https://yoursite.com/wp-content/uploads/fra-update.json">
                            <p class="description">
                                <?php _e('Alternative: URL to a JSON file with update information. Takes priority over GitHub.', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Current Version', 'france-relocation-assistant'); ?></th>
                        <td>
                            <strong>v<?php echo FRA_VERSION; ?></strong>
                            <button type="button" class="button" id="fra-check-update-btn" style="margin-left: 10px;">
                                <?php _e('Check for Updates', 'france-relocation-assistant'); ?>
                            </button>
                            <span id="fra-update-status" style="margin-left: 10px;"></span>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="fra_save_settings" class="button button-primary" value="<?php _e('Save All Settings', 'france-relocation-assistant'); ?>">
                </p>
            </form>
            
            <hr style="margin: 25px 0;">
            
            <h3><?php _e('Manual Update JSON Format', 'france-relocation-assistant'); ?></h3>
            <p class="description"><?php _e('If using a custom update URL, create a JSON file with this structure:', 'france-relocation-assistant'); ?></p>
            <pre style="background: #f0f0f0; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px;">{
    "version": "2.1.0",
    "package": "https://yoursite.com/downloads/france-relocation-assistant-2.1.0.zip",
    "url": "https://yoursite.com/changelog/",
    "requires": "6.0",
    "tested": "6.9",
    "requires_php": "7.4",
    "changelog": "- New feature 1\n- Bug fix 2"
}</pre>
            <p class="description"><?php _e('Upload the JSON file and new plugin ZIP to your server, then WordPress will detect the update.', 'france-relocation-assistant'); ?></p>
        </div>
    </div>
</div>

<style>
.fra-cost-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.fra-cost-table th,
.fra-cost-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.fra-cost-table th {
    background: #f0f0f1;
    font-weight: 500;
}
</style>

<script>
function toggleApiKeyVisibility() {
    var input = document.getElementById('fra_api_key');
    var button = document.getElementById('toggle-api-key');
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '<?php _e('Hide', 'france-relocation-assistant'); ?>';
    } else {
        input.type = 'password';
        button.textContent = '<?php _e('Show', 'france-relocation-assistant'); ?>';
    }
}

// Check for updates
document.getElementById('fra-check-update-btn').addEventListener('click', function() {
    var btn = this;
    var status = document.getElementById('fra-update-status');
    
    btn.disabled = true;
    status.innerHTML = '<span class="spinner is-active" style="float: none;"></span> <?php _e('Checking...', 'france-relocation-assistant'); ?>';
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'fra_check_update',
            nonce: '<?php echo wp_create_nonce('fra_admin_nonce'); ?>'
        },
        success: function(response) {
            btn.disabled = false;
            if (response.success) {
                if (response.data.has_update) {
                    status.innerHTML = '<span style="color: #2271b1; font-weight: bold;">✓ ' + response.data.message + '</span> <a href="plugins.php"><?php _e('Update Now', 'france-relocation-assistant'); ?></a>';
                } else {
                    status.innerHTML = '<span style="color: #00a32a;">✓ ' + response.data.message + '</span>';
                }
            } else {
                status.innerHTML = '<span style="color: #d63638;">✗ <?php _e('Error checking for updates', 'france-relocation-assistant'); ?></span>';
            }
        },
        error: function() {
            btn.disabled = false;
            status.innerHTML = '<span style="color: #d63638;">✗ <?php _e('Connection error', 'france-relocation-assistant'); ?></span>';
        }
    });
});
</script>
