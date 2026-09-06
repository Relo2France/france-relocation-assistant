<?php
/**
 * API Settings Page Template
 *
 * @package France_Relocation_Assistant
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current values (API key is now encrypted).
$api_key        = France_Relocation_Assistant::get_api_key();
$has_api_key    = ! empty( $api_key );
$api_model      = get_option( 'fra_api_model', 'auto' );
$model_tiers    = array(
	'chat'   => get_option( 'fra_model_tier_chat', 'sonnet' ),
	'review' => get_option( 'fra_model_tier_review', 'opus' ),
	'docs'   => get_option( 'fra_model_tier_docs', 'sonnet' ),
);
$enable_ai      = get_option( 'fra_enable_ai', false );
$review_api_on  = (bool) get_option( FRA_Review_API::ENABLED_OPTION, false );
$review_secret_set = strlen( (string) get_option( FRA_Review_API::SECRET_OPTION, '' ) ) >= 32;
$new_review_secret = '';
$github_repo    = get_option( 'fra_github_repo', '' );
$update_url     = get_option( 'fra_update_url', '' );
$membership_url = get_option( 'fra_membership_url', '/membership/' );

// Which form was submitted. This page renders two forms that share one nonce
// and one submit name, so without this marker saving the GitHub form ran the
// API branch too - and because its fields are absent from that POST, an
// unchecked-by-omission "Enable AI" switched AI off and the membership URL
// reset to its default. Each branch now only touches its own form's fields.
$posted_section = isset( $_POST['fra_settings_section'] )
    ? sanitize_key( wp_unslash( $_POST['fra_settings_section'] ) )
    : '';
$has_section_marker = ( '' !== $posted_section );

// The Review API toggle rides on any submit of the API form - Save, Generate
// secret, or Test connection - because the checkbox is posted every time.
//
// This deliberately requires the explicit marker rather than the inferred
// section below: an unchecked checkbox is indistinguishable from one that was
// never on the page, so acting on a form we only *think* is the API form would
// let a stale browser tab silently switch the endpoints off.
if ( $has_section_marker && 'api' === $posted_section && check_admin_referer( 'fra_settings_nonce' ) ) {
    $review_api_on = isset( $_POST['fra_review_api_enabled'] );
    update_option( FRA_Review_API::ENABLED_OPTION, $review_api_on );
}

// A page rendered before the marker existed - a browser tab left open across
// this deploy - posts without it. Infer the section from the fields actually
// present so an old form still saves, instead of silently doing nothing while
// reporting success.
if ( ! $has_section_marker ) {
    if ( isset( $_POST['fra_api_model'] ) || isset( $_POST['fra_membership_url'] ) ) {
        $posted_section = 'api';
    } elseif ( isset( $_POST['fra_github_repo'] ) || isset( $_POST['fra_update_url'] ) ) {
        $posted_section = 'github';
    }
}

// Handle form submission.
if ( isset( $_POST['fra_save_settings'] ) && check_admin_referer( 'fra_settings_nonce' ) ) {

    if ( 'api' === $posted_section ) {
        $new_api_key    = isset( $_POST['fra_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['fra_api_key'] ) ) : '';
        $api_model      = isset( $_POST['fra_api_model'] ) ? sanitize_text_field( wp_unslash( $_POST['fra_api_model'] ) ) : 'auto';
        $enable_ai      = isset( $_POST['fra_enable_ai'] );
        $membership_url = isset( $_POST['fra_membership_url'] ) ? esc_url_raw( wp_unslash( $_POST['fra_membership_url'] ) ) : '/membership/';

        // Only update API key if a new one is provided (not the placeholder).
        if ( ! empty( $new_api_key ) && '••••••••••••••••' !== $new_api_key ) {
            France_Relocation_Assistant::save_api_key( $new_api_key );
            $api_key     = $new_api_key;
            $has_api_key = true;
        }

        update_option( 'fra_api_model', $api_model );

        // Per-purpose model tiers. The exact model ID is resolved at call time.
        $allowed_tiers = array( 'opus', 'sonnet', 'haiku' );
        foreach ( array( 'chat', 'review', 'docs' ) as $purpose ) {
            $field = 'fra_model_tier_' . $purpose;
            if ( isset( $_POST[ $field ] ) ) {
                $tier = sanitize_key( wp_unslash( $_POST[ $field ] ) );
                if ( in_array( $tier, $allowed_tiers, true ) ) {
                    $model_tiers[ $purpose ] = $tier;
                    update_option( $field, $tier );
                }
            }
        }

        // Settings changed - pull a fresh model catalog.
        if ( class_exists( 'FRA_Model_Resolver' ) ) {
            FRA_Model_Resolver::refresh_catalog();
        }
        update_option( 'fra_enable_ai', $enable_ai );
        update_option( 'fra_membership_url', $membership_url );
    }

    if ( 'github' === $posted_section ) {
        $github_repo = isset( $_POST['fra_github_repo'] ) ? sanitize_text_field( wp_unslash( $_POST['fra_github_repo'] ) ) : '';
        $update_url  = isset( $_POST['fra_update_url'] ) ? esc_url_raw( wp_unslash( $_POST['fra_update_url'] ) ) : '';

        update_option( 'fra_github_repo', $github_repo );
        update_option( 'fra_update_url', $update_url );
    }

    // Clear update cache when settings change.
    delete_transient( 'fra_update_check' );

    if ( '' !== $posted_section ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'france-relocation-assistant' ) . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Nothing was saved - the form could not be identified. Reload this page and try again.', 'france-relocation-assistant' ) . '</p></div>';
    }
}

// Generate a review API secret if requested. Displayed once and never again.
if ( isset( $_POST['fra_generate_review_secret'] ) && check_admin_referer( 'fra_settings_nonce' ) ) {
    $new_review_secret = FRA_Review_API::generate_secret();
    $review_secret_set = true;
}

// Test API connection if requested
$test_result = null;
if (isset($_POST['fra_test_api']) && check_admin_referer('fra_settings_nonce')) {
    if (empty($api_key)) {
        $test_result = array('success' => false, 'message' => 'Please enter an API key first.');
    } else {
        $body = FRA_Model_Resolver::message(array(
            'purpose'    => 'chat',
            'max_tokens' => 50,
            'timeout'    => 30,
            'messages'   => array(
                array('role' => 'user', 'content' => 'Say "API connection successful" and nothing else.')
            ),
        ));
        
        if (is_wp_error($body)) {
            $test_result = array('success' => false, 'message' => 'Connection failed: ' . $body->get_error_message());
        } else {
            $text = FRA_Model_Resolver::extract_text($body);
            if ('' !== $text) {
                $test_result = array(
                    'success' => true,
                    'message' => sprintf(
                        'Connection successful using %s. Response: %s',
                        isset($body['fra_model']) ? $body['fra_model'] : 'the resolved model',
                        $text
                    ),
                );
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
                <input type="hidden" name="fra_settings_section" value="api">
                
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
                            <label for="fra_api_key"><?php esc_html_e( 'Anthropic API Key', 'france-relocation-assistant' ); ?></label>
                        </th>
                        <td>
                            <input type="password" name="fra_api_key" id="fra_api_key" value="<?php echo $has_api_key ? '••••••••••••••••' : ''; ?>" class="regular-text" autocomplete="off" placeholder="<?php echo $has_api_key ? esc_attr__( 'Key saved (enter new to replace)', 'france-relocation-assistant' ) : esc_attr__( 'Enter API key', 'france-relocation-assistant' ); ?>">
                            <?php if ( $has_api_key ) : ?>
                                <p class="description" style="color: green;">✓ <?php esc_html_e( 'API key is saved (encrypted)', 'france-relocation-assistant' ); ?></p>
                            <?php endif; ?>
                            <button type="button" class="button" onclick="toggleApiKeyVisibility()" id="toggle-api-key"
                                <?php echo $has_api_key ? 'disabled' : ''; ?>>
                                <?php _e('Show', 'france-relocation-assistant'); ?>
                            </button>
                            <?php if ( $has_api_key ) : ?>
                                <p class="description">
                                    <?php _e('The saved key is never sent to your browser, so it cannot be displayed here. Show only reveals a key you have just typed. To use this key elsewhere, create a separate key at', 'france-relocation-assistant'); ?>
                                    <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a><?php _e(' - a second key can be revoked on its own without breaking this site.', 'france-relocation-assistant'); ?>
                                </p>
                            <?php else : ?>
                                <p class="description">
                                    <?php _e('Get your API key from', 'france-relocation-assistant'); ?> 
                                    <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <?php
                    $tier_labels = array(
                        'opus'   => __('Opus - most capable, highest cost', 'france-relocation-assistant'),
                        'sonnet' => __('Sonnet - balanced (recommended)', 'france-relocation-assistant'),
                        'haiku'  => __('Haiku - fastest and cheapest', 'france-relocation-assistant'),
                    );
                    $purpose_labels = array(
                        'chat'   => __('Visitor chat', 'france-relocation-assistant'),
                        'review' => __('Weekly law &amp; policy review', 'france-relocation-assistant'),
                        'docs'   => __('Guides, documents &amp; knowledge base', 'france-relocation-assistant'),
                    );
                    $model_choices = class_exists('FRA_Model_Resolver') ? FRA_Model_Resolver::get_choices() : array();
                    ?>

                    <?php foreach ($purpose_labels as $purpose => $purpose_label) : ?>
                    <tr>
                        <th scope="row">
                            <label for="fra_model_tier_<?php echo esc_attr($purpose); ?>"><?php echo esc_html($purpose_label); ?></label>
                        </th>
                        <td>
                            <select name="fra_model_tier_<?php echo esc_attr($purpose); ?>" id="fra_model_tier_<?php echo esc_attr($purpose); ?>">
                                <?php foreach ($tier_labels as $tier => $tier_label) : ?>
                                    <option value="<?php echo esc_attr($tier); ?>" <?php selected($model_tiers[$purpose], $tier); ?>>
                                        <?php echo esc_html($tier_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php
                                if (class_exists('FRA_Model_Resolver')) {
                                    printf(
                                        /* translators: %s: resolved model ID */
                                        esc_html__('Currently resolves to: %s', 'france-relocation-assistant'),
                                        '<code>' . esc_html(FRA_Model_Resolver::resolve($model_tiers[$purpose])) . '</code>'
                                    );
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <tr>
                        <th scope="row">
                            <label for="fra_api_model"><?php _e('Pin a specific model', 'france-relocation-assistant'); ?></label>
                        </th>
                        <td>
                            <select name="fra_api_model" id="fra_api_model">
                                <option value="auto" <?php selected($api_model, 'auto'); ?>>
                                    <?php _e('Automatic (recommended) - always use the newest model in the tier', 'france-relocation-assistant'); ?>
                                </option>
                                <?php foreach ($model_choices as $tier => $models) : ?>
                                    <optgroup label="<?php echo esc_attr(ucfirst($tier)); ?>">
                                        <?php foreach ($models as $choice) : ?>
                                            <option value="<?php echo esc_attr($choice['id']); ?>" <?php selected($api_model, $choice['id']); ?>>
                                                <?php echo esc_html($choice['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                                <?php if (!empty($api_model) && 'auto' !== $api_model && class_exists('FRA_Model_Resolver') && !FRA_Model_Resolver::is_live($api_model)) : ?>
                                    <optgroup label="<?php esc_attr_e('Retired', 'france-relocation-assistant'); ?>">
                                        <option value="<?php echo esc_attr($api_model); ?>" selected>
                                            <?php echo esc_html($api_model); ?> <?php esc_html_e('(no longer available)', 'france-relocation-assistant'); ?>
                                        </option>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                <?php _e('This list is pulled live from your Anthropic account and refreshes daily. Leave on Automatic unless you need to lock the visitor chat to one exact model - a pinned model that Anthropic retires will fall back to the newest model in the same tier.', 'france-relocation-assistant'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Review API', 'france-relocation-assistant'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="fra_review_api_enabled" value="1" <?php checked($review_api_on); ?>>
                                <?php _e('Allow an external worker to run the knowledge base review', 'france-relocation-assistant'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Exposes two authenticated endpoints so the review can run on a scheduler outside WordPress. The worker can only add suggestions to the approval queue - it can never publish to the knowledge base directly. Off by default; both routes return 404 while disabled.', 'france-relocation-assistant'); ?>
                            </p>

                            <?php $api_live = FRA_Review_API::is_enabled(); ?>
                            <p style="margin:10px 0 0;font-weight:600;color:<?php echo $api_live ? '#008a20' : '#646970'; ?>;">
                                <?php echo $api_live
                                    ? esc_html__('Endpoints are LIVE and accepting authenticated requests.', 'france-relocation-assistant')
                                    : esc_html__('Endpoints are CLOSED. They return 404 until this is enabled and a secret exists.', 'france-relocation-assistant'); ?>
                            </p>

                            <?php if ($new_review_secret) : ?>
                                <div class="notice notice-success inline" style="margin:12px 0;padding:10px 12px;">
                                    <p style="margin:0 0 6px;"><strong><?php _e('Copy this secret now - it will not be shown again:', 'france-relocation-assistant'); ?></strong></p>
                                    <code style="display:block;padding:8px;word-break:break-all;background:#f6f7f7;"><?php echo esc_html($new_review_secret); ?></code>
                                </div>
                            <?php endif; ?>

                            <p style="margin-top:10px;">
                                <button type="submit" name="fra_generate_review_secret" value="1" class="button">
                                    <?php echo $review_secret_set
                                        ? esc_html__('Regenerate secret', 'france-relocation-assistant')
                                        : esc_html__('Generate secret', 'france-relocation-assistant'); ?>
                                </button>
                                <span style="margin-left:8px;color:<?php echo $review_secret_set ? '#008a20' : '#996800'; ?>;">
                                    <?php echo $review_secret_set
                                        ? esc_html__('A secret is set.', 'france-relocation-assistant')
                                        : esc_html__('No secret yet - the endpoints stay closed until one exists.', 'france-relocation-assistant'); ?>
                                </span>
                            </p>
                            <?php if ($review_secret_set) : ?>
                                <p class="description" style="margin-top:8px;">
                                    <?php _e('Regenerating immediately invalidates the old secret.', 'france-relocation-assistant'); ?>
                                    <?php $last = get_option(FRA_Review_API::LAST_CALL_OPTION, ''); ?>
                                    <?php if ($last) : ?>
                                        <br><?php printf(esc_html__('Last accepted suggestion: %s', 'france-relocation-assistant'), esc_html($last)); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="description" style="margin-top:8px;">
                                    <code><?php echo esc_html(rest_url('fra/v1/review/topics')); ?></code><br>
                                    <code><?php echo esc_html(rest_url('fra/v1/review/suggestions')); ?></code>
                                </p>
                            <?php endif; ?>
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
                <input type="hidden" name="fra_settings_section" value="github">
                
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
// The saved key is never rendered into the page - the field holds bullet
// characters, not the key - so Show has nothing to reveal until the admin
// types a new one. Enable it at that point rather than leaving a button that
// appears to do nothing.
(function () {
    var input = document.getElementById('fra_api_key');
    var button = document.getElementById('toggle-api-key');
    if (!input || !button) return;
    input.addEventListener('input', function () {
        button.disabled = input.value === '';
    });
})();

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
