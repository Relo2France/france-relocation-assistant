<?php
/**
 * Front-End Customizer Page - Enhanced Version
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current customization settings
$defaults = array(
    // Header Section
    'header_bg_color' => '#1e3a5f',
    'header_text_color' => '#ffffff',
    'header_title' => 'France Relocation',
    'header_subtitle' => 'US → France Guide',
    'header_show_flag' => true,
    
    // Main Colors
    'color_primary' => '#1a1a1a',
    'color_accent' => '#ff6b00',
    'color_background' => '#f5f5f5',
    'color_card' => '#ffffff',
    'color_text' => '#1a1a1a',
    'color_text_light' => '#666666',
    'color_border' => '#e0e0e0',
    
    // Navigation Colors
    'color_nav_bg' => '#ffffff',
    'color_nav_text' => '#1a1a1a',
    'color_nav_hover' => '#ff6b00',
    'color_nav_active_bg' => '#fff7ed',
    
    // Chat Area Colors
    'color_chat_bg' => '#f9fafb',
    'color_chat_input_bg' => '#ffffff',
    'color_chat_input_border' => '#e5e7eb',
    'color_user_msg_bg' => '#f3f4f6',
    'color_ai_msg_bg' => '#ffffff',
    'color_ai_header_bg' => '#f9fafb',
    
    // Button Colors
    'color_btn_primary_bg' => '#ff6b00',
    'color_btn_primary_text' => '#ffffff',
    'color_btn_secondary_bg' => '#f3f4f6',
    'color_btn_secondary_text' => '#374151',
    'color_send_btn_bg' => '#ff6b00',
    'color_send_btn_text' => '#ffffff',
    
    // Status Colors
    'color_success' => '#2e7d32',
    'color_warning' => '#e65100',
    'color_danger' => '#c62828',
    
    // Day Counter Colors
    'color_france' => '#0055A4',
    'color_us' => '#B22234',
    'color_other' => '#666666',
    
    // Member Area Colors
    'color_member_header_bg' => '#1a1a1a',
    'color_member_header_text' => '#fafaf8',
    'color_member_header_accent' => '#d4a853',
    'color_profile_header_bg' => '#1a1a1a',
    'color_profile_header_text' => '#fafaf8',
    'color_profile_progress_bg' => '#3a3a3a',
    'color_profile_progress_fill' => '#d4a853',
    
    // Welcome Screen
    'welcome_icon' => '🇫🇷',
    'welcome_title' => 'Welcome to France Relocation Assistant',
    'welcome_subtitle' => 'Select a topic from the menu or ask a question below. AI-powered answers based on official French sources.',
    'show_quick_topics' => true,
    
    // Sidebar Display Options
    'show_free_badge' => true,
    'show_members_preview' => true,
    
    // Quick Topic Buttons
    'quick_topic_1_label' => '📋 Visa Requirements',
    'quick_topic_1_cat' => 'visas',
    'quick_topic_1_topic' => 'overview',
    'quick_topic_2_label' => '🏠 Buying Property',
    'quick_topic_2_cat' => 'property',
    'quick_topic_2_topic' => 'overview',
    'quick_topic_3_label' => '🏥 Healthcare',
    'quick_topic_3_cat' => 'healthcare',
    'quick_topic_3_topic' => 'overview',
    'quick_topic_4_label' => '📅 183-Day Counter',
    'quick_topic_4_cat' => '_day_counter',
    'quick_topic_4_topic' => '',
    
    // Site Header (WordPress Theme Header)
    'site_header_enabled' => false,
    'site_header_max_width' => '1280',
    'site_header_padding' => '24',
    'site_header_logo' => '',
    'site_header_logo_size' => '200',
    'site_header_logo_bg_color' => '#ffffff',
    'site_header_site_name' => 'Relo2France',
    'site_header_site_name_size' => '32',
    'site_header_tagline' => 'Your Relocation Guide from the US to France, in one easy to use site.',
    'site_header_tagline_size' => '16',
    'site_header_description' => 'Updated daily.',
    'site_header_description_size' => '14',
    'site_header_bg_color' => '#1e3a8a',
    'site_header_utility_bg_color' => '#172554',
    'site_header_text_color' => '#ffffff',
    'site_header_nav_link_color' => '#ffffff',
    'site_header_utility_text' => 'Your complete guide to relocating to France',
    'site_header_cta_text' => 'Get Lifetime Access',
    'site_header_cta_url' => '/membership/',
    'site_header_cta_color' => '#f97316',
    'site_header_cta_size' => '15',
    'site_header_login_text' => 'Login',
    'site_header_login_url' => '/login/',
    'site_header_contact_text' => 'Contact',
    'site_header_contact_url' => '/contact/',
    'site_header_nav_items' => '',
    'site_header_nav_urls' => '',
    'site_header_hide_theme_header' => false,
    
    // Maintenance Banner
    'maintenance_banner_enabled' => false,
    'maintenance_banner_message' => 'We are currently performing scheduled maintenance. Some features may be temporarily unavailable.',
    'maintenance_banner_bg_color' => '#f97316',
    'maintenance_banner_text_color' => '#ffffff',
    
    // Auth Pages (Login, Signup, Logout)
    'auth_pages_enabled' => false,
    'auth_bg_color_start' => '#172554',
    'auth_bg_color_end' => '#3b82f6',
    'auth_card_bg' => '#ffffff',
    'auth_text_color' => '#111827',
    'auth_text_muted' => '#6b7280',
    'auth_btn_color' => '#f97316',
    'auth_btn_text' => '#ffffff',
    'auth_link_color' => '#1e3a8a',
    'auth_input_border' => '#e5e7eb',
    'auth_logo_url' => '',
    'auth_site_name' => 'Relo2France',
    'auth_login_title' => 'Welcome Back',
    'auth_login_subtitle' => 'Sign in to access your relocation dashboard',
    'auth_signup_title' => 'Start Your France Journey',
    'auth_signup_subtitle' => 'Create your account and get lifetime access',
    'auth_signup_price' => '$35 Lifetime Access',
    'auth_signup_price_note' => 'One-time payment, forever access',
    'auth_signup_benefits' => "AI-powered visa guidance\nStep-by-step relocation checklists\nDocument templates & generators\n183-day Schengen counter\nPriority email support",
    'auth_logout_title' => "You've Been Logged Out",
    'auth_logout_subtitle' => 'Thanks for using Relo2France! Your session has been securely ended.',
    'auth_account_title' => 'Your Account',
    'auth_account_subtitle' => 'Manage your membership and profile settings',
    'auth_thankyou_title' => 'Welcome to Relo2France!',
    'auth_thankyou_subtitle' => 'Your account has been created successfully. You now have full access to all member tools.',
    'auth_title_size' => '24',
    'auth_subtitle_size' => '15',
    
    // In-Chat Auth (Login/Signup in chat window)
    'inchat_auth_enabled' => true,
    'inchat_auth_login_btn_text' => 'Login',
    'inchat_auth_signup_btn_text' => 'Get Access',
    'inchat_auth_logout_btn_text' => 'Logout',
    'inchat_auth_btn_bg' => '#ea580c',
    'inchat_auth_btn_text' => '#ffffff',
    'inchat_auth_btn_size' => '14',
    'inchat_auth_membership_id' => '',
    'inchat_auth_welcome_message' => 'Welcome! Complete your profile to get personalized recommendations.',
    'inchat_auth_dashboard_title' => 'Member Dashboard',
    'inchat_auth_signup_redirect' => '', // URL to redirect after signup (empty = no redirect)
    
    // Chat Input
    'chat_placeholder' => 'Ask AI about relocating to France...',
    'chat_hint' => 'AI answers powered by Claude • Information from official French sources',
    
    // Navigation Categories (labels only - icons are set separately)
    'nav_cat_visas' => 'Visas & Immigration',
    'nav_cat_property' => 'Property Purchase',
    'nav_cat_healthcare' => 'Healthcare',
    'nav_cat_taxes' => 'Taxes',
    'nav_cat_driving' => 'Driving',
    'nav_cat_shipping' => 'Shipping & Pets',
    'nav_cat_banking' => 'Banking',
    'nav_cat_settling' => 'Settling In',
    
    // Layout
    'max_width' => '1680',
    'min_height' => '850',
    'max_height' => '1200',
    'border_radius' => '12',
    'nav_width' => '280',
    
    // Typography
    'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    'font_size_base' => '14',
    'font_size_title' => '18',
    
    // Toolbar
    'show_day_counter_btn' => true,
    'show_search_bar' => true,
    'search_placeholder' => 'Search topics...',
);

$settings = get_option('fra_customizer', array());
$settings = wp_parse_args($settings, $defaults);

// Handle form submission
if (isset($_POST['fra_save_customizer']) && check_admin_referer('fra_customizer_nonce')) {
    $new_settings = array();
    
    // Define which fields are checkboxes (booleans)
    $checkbox_fields = array(
        'header_show_flag',
        'show_quick_topics',
        'show_day_counter_btn',
        'show_search_bar',
        'site_header_enabled',
        'site_header_hide_theme_header',
        'show_free_badge',
        'show_members_preview',
        'maintenance_banner_enabled',
    );
    
    // Define color fields explicitly
    $color_fields = array(
        'header_bg_color',
        'header_text_color',
        'color_primary',
        'color_accent',
        'color_background',
        'color_card',
        'color_text',
        'color_text_light',
        'color_border',
        'color_nav_bg',
        'color_nav_text',
        'color_nav_hover',
        'color_nav_active_bg',
        'color_chat_bg',
        'color_chat_input_bg',
        'color_chat_input_border',
        'color_user_msg_bg',
        'color_ai_msg_bg',
        'color_ai_header_bg',
        'color_btn_primary_bg',
        'color_btn_primary_text',
        'color_btn_secondary_bg',
        'color_btn_secondary_text',
        'color_send_btn_bg',
        'color_send_btn_text',
        'color_success',
        'color_warning',
        'color_danger',
        'color_france',
        'color_us',
        'color_other',
        'color_member_header_bg',
        'color_member_header_text',
        'color_member_header_accent',
        'color_profile_header_bg',
        'color_profile_header_text',
        'color_profile_progress_bg',
        'color_profile_progress_fill',
        'site_header_bg_color',
        'site_header_utility_bg_color',
        'site_header_text_color',
        'site_header_cta_color',
        'site_header_logo_bg_color',
        'site_header_nav_link_color',
        'maintenance_banner_bg_color',
        'maintenance_banner_text_color',
    );
    
    // Process all fields
    foreach ($defaults as $key => $default) {
        // Check if it's a checkbox field
        if (in_array($key, $checkbox_fields)) {
            $new_settings[$key] = isset($_POST[$key]);
        }
        // Color fields
        elseif (in_array($key, $color_fields)) {
            $color_value = isset($_POST[$key]) ? sanitize_hex_color($_POST[$key]) : null;
            $new_settings[$key] = $color_value ? $color_value : $default;
        }
        // Numeric fields
        elseif (in_array($key, array('max_width', 'min_height', 'max_height', 'border_radius', 'nav_width', 'font_size_base', 'font_size_title', 'site_header_logo_size', 'site_header_max_width', 'site_header_padding', 'site_header_site_name_size', 'site_header_tagline_size', 'site_header_description_size', 'site_header_cta_size', 'auth_title_size', 'auth_subtitle_size'))) {
            $new_settings[$key] = absint($_POST[$key] ?? $default);
        }
        // Logo URL field
        elseif ($key === 'site_header_logo') {
            $new_settings[$key] = esc_url_raw($_POST[$key] ?? $settings[$key] ?? '');
        }
        // Navigation fields - allow empty values
        elseif (in_array($key, array('site_header_nav_items', 'site_header_nav_urls'))) {
            $new_settings[$key] = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        }
        // All other text fields
        else {
            $value = $_POST[$key] ?? $default;
            $new_settings[$key] = sanitize_textarea_field(wp_unslash($value));
        }
    }
    
    update_option('fra_customizer', $new_settings);
    $settings = $new_settings;
    
    // Save KB order and settings
    if (isset($_POST['kb_order']) && is_array($_POST['kb_order'])) {
        $kb_order = array_map('sanitize_key', $_POST['kb_order']);
        update_option('fra_kb_order', $kb_order);
    }
    
    if (isset($_POST['kb_settings']) && is_array($_POST['kb_settings'])) {
        $kb_settings = array();
        foreach ($_POST['kb_settings'] as $key => $item) {
            $key = sanitize_key($key);
            $kb_settings[$key] = array(
                'enabled' => !empty($item['enabled']),
                'icon' => sanitize_text_field(wp_unslash($item['icon'] ?? '')),
                'label' => sanitize_text_field(wp_unslash($item['label'] ?? '')),
            );
        }
        update_option('fra_kb_settings', $kb_settings);
    }
    
    // Save Member Tools order and settings
    if (isset($_POST['mt_order']) && is_array($_POST['mt_order'])) {
        $mt_order = array_map('sanitize_key', $_POST['mt_order']);
        update_option('fra_member_tools_order', $mt_order);
    }
    
    if (isset($_POST['mt_settings']) && is_array($_POST['mt_settings'])) {
        $mt_settings = array();
        foreach ($_POST['mt_settings'] as $key => $item) {
            $key = sanitize_key($key);
            $mt_settings[$key] = array(
                'enabled' => !empty($item['enabled']),
                'icon' => sanitize_text_field(wp_unslash($item['icon'] ?? '')),
                'label' => sanitize_text_field(wp_unslash($item['label'] ?? '')),
            );
        }
        update_option('fra_member_tools_settings', $mt_settings);
    }
    
    // Save Member Tools teaser message
    if (isset($_POST['mt_teaser_message'])) {
        update_option('fra_mt_teaser_message', sanitize_text_field(wp_unslash($_POST['mt_teaser_message'])));
    }
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('✓ Customization settings saved!', 'france-relocation-assistant') . '</p></div>';
}

// Handle reset
if (isset($_POST['fra_reset_customizer']) && check_admin_referer('fra_customizer_nonce')) {
    delete_option('fra_customizer');
    $settings = $defaults;
    echo '<div class="notice notice-warning is-dismissible"><p>' . __('Settings reset to defaults.', 'france-relocation-assistant') . '</p></div>';
}
?>

<div class="wrap fra-admin-wrap">
    <h1>
        <span class="dashicons dashicons-admin-appearance"></span>
        <?php _e('Appearance Customizer', 'france-relocation-assistant'); ?>
    </h1>
    
    <div class="fra-admin-header">
        <p class="fra-description">
            <?php _e('Customize colors, text, and layout of your France Relocation Assistant.', 'france-relocation-assistant'); ?>
        </p>
    </div>
    
    <form method="post" class="fra-customizer-form">
        <?php wp_nonce_field('fra_customizer_nonce'); ?>
        
        <!-- Tab Navigation -->
        <div class="fra-tabs">
            <button type="button" class="fra-tab active" data-tab="site-header"><?php _e('🌐 Site Header', 'france-relocation-assistant'); ?></button>
            <button type="button" class="fra-tab" data-tab="auth-pages"><?php _e('🔐 Auth Pages', 'france-relocation-assistant'); ?></button>
            <button type="button" class="fra-tab" data-tab="colors"><?php _e('🎨 Colors', 'france-relocation-assistant'); ?></button>
            <button type="button" class="fra-tab" data-tab="text"><?php _e('✏️ Text & Labels', 'france-relocation-assistant'); ?></button>
            <button type="button" class="fra-tab" data-tab="layout"><?php _e('📐 Layout', 'france-relocation-assistant'); ?></button>
            <button type="button" class="fra-tab" data-tab="navigation"><?php _e('📂 Navigation', 'france-relocation-assistant'); ?></button>
        </div>
        
        <!-- Site Header Tab -->
        <div class="fra-tab-content active" data-tab="site-header" style="display: block;">
            <div class="fra-customizer-grid">
                <!-- Enable/Disable -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('🌐 WordPress Site Header', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Customize your site\'s main header that appears on all pages. This replaces your theme\'s default header.', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_enabled"><?php _e('Enable Custom Header', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <label class="fra-toggle">
                                    <input type="checkbox" name="site_header_enabled" id="site_header_enabled" <?php checked($settings['site_header_enabled']); ?>>
                                    <span class="fra-toggle-slider"></span>
                                </label>
                                <span class="description" style="margin-left: 10px;"><?php _e('Enable the custom header on your site', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_hide_theme_header"><?php _e('Hide Theme Header', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <label class="fra-toggle">
                                    <input type="checkbox" name="site_header_hide_theme_header" id="site_header_hide_theme_header" <?php checked($settings['site_header_hide_theme_header']); ?>>
                                    <span class="fra-toggle-slider"></span>
                                </label>
                                <span class="description" style="margin-left: 10px;"><?php _e('Hide your theme\'s default header/hero section', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Logo Upload -->
                <div class="fra-card">
                    <h2><?php _e('📷 Logo', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-logo-upload-area">
                        <div class="fra-logo-preview" id="logo-preview">
                            <?php if (!empty($settings['site_header_logo'])): ?>
                                <img src="<?php echo esc_url($settings['site_header_logo']); ?>" alt="Logo">
                            <?php else: ?>
                                <div class="fra-logo-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p><?php _e('No logo uploaded', 'france-relocation-assistant'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="site_header_logo" id="site_header_logo" value="<?php echo esc_url($settings['site_header_logo']); ?>">
                        <div class="fra-logo-buttons">
                            <button type="button" id="upload-logo-btn" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
                                <?php _e('Upload Logo', 'france-relocation-assistant'); ?>
                            </button>
                            <button type="button" id="remove-logo-btn" class="button" <?php echo empty($settings['site_header_logo']) ? 'style="display:none;"' : ''; ?>>
                                <?php _e('Remove', 'france-relocation-assistant'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_logo_size"><?php _e('Logo Size', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="site_header_logo_size" id="site_header_logo_size" value="<?php echo esc_attr($settings['site_header_logo_size']); ?>" min="50" max="300" step="10" style="width: 80px;">
                                <span class="description">px (<?php _e('width & height', 'france-relocation-assistant'); ?>)</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_max_width"><?php _e('Header Max Width', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="site_header_max_width" id="site_header_max_width" value="<?php echo esc_attr($settings['site_header_max_width']); ?>" min="800" max="1920" step="10" style="width: 100px;">
                                <span class="description">px</span>
                                <p class="description" style="margin-top: 4px;"><?php _e('Match to your content width. Default: 1280px. Use ~1240px to align with the assistant below.', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_padding"><?php _e('Header Padding (Height)', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="site_header_padding" id="site_header_padding" value="<?php echo esc_attr(isset($settings['site_header_padding']) ? $settings['site_header_padding'] : 24); ?>" min="0" max="60" step="2" style="width: 80px;">
                                <span class="description">px</span>
                                <p class="description" style="margin-top: 4px;"><?php _e('Vertical padding (top & bottom). Default: 24px. Use 12-16px for a more compact header.', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Site Name & Tagline -->
                <div class="fra-card">
                    <h2><?php _e('✏️ Site Name & Tagline', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_site_name"><?php _e('Site Name', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_site_name" id="site_header_site_name" value="<?php echo esc_attr($settings['site_header_site_name']); ?>" class="regular-text">
                                <input type="number" name="site_header_site_name_size" id="site_header_site_name_size" value="<?php echo esc_attr(isset($settings['site_header_site_name_size']) ? $settings['site_header_site_name_size'] : 32); ?>" min="16" max="48" style="width: 60px; margin-left: 10px;">
                                <span class="description">px</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_tagline"><?php _e('Tagline (Line 1)', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_tagline" id="site_header_tagline" value="<?php echo esc_attr($settings['site_header_tagline']); ?>" class="large-text" style="width: 100%; max-width: 400px;">
                                <input type="number" name="site_header_tagline_size" id="site_header_tagline_size" value="<?php echo esc_attr(isset($settings['site_header_tagline_size']) ? $settings['site_header_tagline_size'] : 16); ?>" min="10" max="24" style="width: 60px; margin-left: 10px;">
                                <span class="description">px</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_description"><?php _e('Description (Line 2)', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_description" id="site_header_description" value="<?php echo esc_attr($settings['site_header_description']); ?>" class="large-text" style="width: 100%; max-width: 400px;">
                                <input type="number" name="site_header_description_size" id="site_header_description_size" value="<?php echo esc_attr(isset($settings['site_header_description_size']) ? $settings['site_header_description_size'] : 14); ?>" min="10" max="20" style="width: 60px; margin-left: 10px;">
                                <span class="description">px</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Colors -->
                <div class="fra-card">
                    <h2><?php _e('🎨 Header Colors', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label for="site_header_bg_color"><?php _e('Header Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_bg_color" id="site_header_bg_color" value="<?php echo esc_attr($settings['site_header_bg_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label for="site_header_utility_bg_color"><?php _e('Utility Bar Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_utility_bg_color" id="site_header_utility_bg_color" value="<?php echo esc_attr($settings['site_header_utility_bg_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label for="site_header_text_color"><?php _e('Text Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_text_color" id="site_header_text_color" value="<?php echo esc_attr($settings['site_header_text_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label for="site_header_nav_link_color"><?php _e('Nav Link Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_nav_link_color" id="site_header_nav_link_color" value="<?php echo esc_attr($settings['site_header_nav_link_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label for="site_header_cta_color"><?php _e('CTA Button Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_cta_color" id="site_header_cta_color" value="<?php echo esc_attr($settings['site_header_cta_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label for="site_header_logo_bg_color"><?php _e('Logo Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="site_header_logo_bg_color" id="site_header_logo_bg_color" value="<?php echo esc_attr($settings['site_header_logo_bg_color']); ?>">
                            <p class="description" style="font-size: 11px; margin-top: 4px;"><?php _e('Set to same as header for no box', 'france-relocation-assistant'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Banner -->
                <div class="fra-card">
                    <h2><?php _e('🚧 Maintenance Banner', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Display an orange banner above the utility bar when your site is under maintenance', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="maintenance_banner_enabled"><?php _e('Enable Banner', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <label class="fra-toggle">
                                    <input type="checkbox" name="maintenance_banner_enabled" id="maintenance_banner_enabled" <?php checked($settings['maintenance_banner_enabled']); ?>>
                                    <span class="fra-toggle-slider"></span>
                                </label>
                                <span style="margin-left: 10px; color: #666;"><?php _e('Show maintenance banner on the site', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="maintenance_banner_message"><?php _e('Banner Message', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="maintenance_banner_message" id="maintenance_banner_message" value="<?php echo esc_attr($settings['maintenance_banner_message']); ?>" class="large-text">
                                <p class="description"><?php _e('The message displayed in the maintenance banner', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php _e('Banner Colors', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <div style="display: flex; gap: 20px; align-items: center;">
                                    <div>
                                        <label for="maintenance_banner_bg_color"><?php _e('Background', 'france-relocation-assistant'); ?></label>
                                        <input type="color" name="maintenance_banner_bg_color" id="maintenance_banner_bg_color" value="<?php echo esc_attr($settings['maintenance_banner_bg_color']); ?>">
                                    </div>
                                    <div>
                                        <label for="maintenance_banner_text_color"><?php _e('Text', 'france-relocation-assistant'); ?></label>
                                        <input type="color" name="maintenance_banner_text_color" id="maintenance_banner_text_color" value="<?php echo esc_attr($settings['maintenance_banner_text_color']); ?>">
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Utility Bar -->
                <div class="fra-card">
                    <h2><?php _e('📢 Utility Bar', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('The small bar above the main header', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_utility_text"><?php _e('Utility Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_utility_text" id="site_header_utility_text" value="<?php echo esc_attr($settings['site_header_utility_text']); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_login_text"><?php _e('Login Link Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_login_text" id="site_header_login_text" value="<?php echo esc_attr($settings['site_header_login_text']); ?>" style="width: 120px;">
                                <input type="text" name="site_header_login_url" id="site_header_login_url" value="<?php echo esc_attr($settings['site_header_login_url']); ?>" placeholder="URL" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_contact_text"><?php _e('Contact Link Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_contact_text" id="site_header_contact_text" value="<?php echo esc_attr($settings['site_header_contact_text']); ?>" style="width: 120px;">
                                <input type="text" name="site_header_contact_url" id="site_header_contact_url" value="<?php echo esc_attr($settings['site_header_contact_url']); ?>" placeholder="URL" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- CTA Button -->
                <div class="fra-card">
                    <h2><?php _e('🔘 Call-to-Action Button', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_cta_text"><?php _e('Button Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_cta_text" id="site_header_cta_text" value="<?php echo esc_attr($settings['site_header_cta_text']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_cta_url"><?php _e('Button URL', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_cta_url" id="site_header_cta_url" value="<?php echo esc_attr($settings['site_header_cta_url']); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Navigation -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('🧭 Navigation Menu', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Enter menu items as comma-separated values. Leave blank to hide navigation. The order of items and URLs must match.', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="site_header_nav_items"><?php _e('Menu Items', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_nav_items" id="site_header_nav_items" value="<?php echo esc_attr($settings['site_header_nav_items']); ?>" class="large-text">
                                <p class="description"><?php _e('Example: Guide, Visa, Property, Taxes, Tools', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="site_header_nav_urls"><?php _e('Menu URLs', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="site_header_nav_urls" id="site_header_nav_urls" value="<?php echo esc_attr($settings['site_header_nav_urls']); ?>" class="large-text">
                                <p class="description"><?php _e('Example: /guide/, /visa/, /property/, /taxes/, /tools/', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th></th>
                            <td>
                                <button type="button" class="button button-secondary" onclick="document.getElementById('site_header_nav_items').value=''; document.getElementById('site_header_nav_urls').value=''; alert('Fields cleared! Click Save Settings to apply.');"><?php _e('Clear Navigation', 'france-relocation-assistant'); ?></button>
                                <span class="description" style="margin-left: 10px;"><?php _e('Click to clear both fields above', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Auth Pages Tab -->
        <div class="fra-tab-content" data-tab="auth-pages">
            <div class="fra-customizer-grid">
                <!-- Enable Auth Pages -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('🔐 Auth Pages Styling', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Style your Login, Signup, and Logout pages to match your site. Use the shortcodes below on your WordPress pages.', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Enable Auth Page Styles', 'france-relocation-assistant'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auth_pages_enabled" value="1" <?php checked(!empty($settings['auth_pages_enabled'])); ?>>
                                    <?php _e('Output auth page CSS on frontend', 'france-relocation-assistant'); ?>
                                </label>
                                <p class="description"><?php _e('When enabled, the auth page styles will be available site-wide.', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <strong><?php _e('📋 Shortcodes:', 'france-relocation-assistant'); ?></strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem;">
                            <li><code>[fra_login_page]</code> - <?php _e('Login page wrapper', 'france-relocation-assistant'); ?></li>
                            <li><code>[fra_signup_page membership_id="123"]</code> - <?php _e('Signup page (replace 123 with your membership ID)', 'france-relocation-assistant'); ?></li>
                            <li><code>[fra_logout_page]</code> - <?php _e('Logout confirmation page', 'france-relocation-assistant'); ?></li>
                            <li><code>[fra_account_page]</code> - <?php _e('Member account/dashboard page', 'france-relocation-assistant'); ?></li>
                            <li><code>[fra_thankyou_page]</code> - <?php _e('Thank you/welcome page after signup', 'france-relocation-assistant'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Colors -->
                <div class="fra-card">
                    <h2><?php _e('🎨 Colors', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Background Start', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_bg_color_start" value="<?php echo esc_attr($settings['auth_bg_color_start']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Background End', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_bg_color_end" value="<?php echo esc_attr($settings['auth_bg_color_end']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Card Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_card_bg" value="<?php echo esc_attr($settings['auth_card_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Text Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_text_color" value="<?php echo esc_attr($settings['auth_text_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Muted Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_text_muted" value="<?php echo esc_attr($settings['auth_text_muted']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Button Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_btn_color" value="<?php echo esc_attr($settings['auth_btn_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Button Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_btn_text" value="<?php echo esc_attr($settings['auth_btn_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Link Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_link_color" value="<?php echo esc_attr($settings['auth_link_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Input Border', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="auth_input_border" value="<?php echo esc_attr($settings['auth_input_border']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Branding -->
                <div class="fra-card">
                    <h2><?php _e('🏷️ Branding', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_logo_url"><?php _e('Logo URL', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="url" name="auth_logo_url" id="auth_logo_url" value="<?php echo esc_attr($settings['auth_logo_url']); ?>" class="large-text" placeholder="https://yoursite.com/logo.png">
                                <p class="description"><?php _e('Leave empty to show site name text only', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_site_name"><?php _e('Site Name', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_site_name" id="auth_site_name" value="<?php echo esc_attr($settings['auth_site_name']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_title_size"><?php _e('Title Size', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="auth_title_size" id="auth_title_size" value="<?php echo esc_attr($settings['auth_title_size']); ?>" min="16" max="48" style="width: 80px;"> px
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_subtitle_size"><?php _e('Subtitle Size', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="auth_subtitle_size" id="auth_subtitle_size" value="<?php echo esc_attr($settings['auth_subtitle_size']); ?>" min="12" max="24" style="width: 80px;"> px
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Login Page -->
                <div class="fra-card">
                    <h2><?php _e('🔑 Login Page', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_login_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_login_title" id="auth_login_title" value="<?php echo esc_attr($settings['auth_login_title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_login_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_login_subtitle" id="auth_login_subtitle" value="<?php echo esc_attr($settings['auth_login_subtitle']); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Signup Page -->
                <div class="fra-card">
                    <h2><?php _e('📝 Signup Page', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_signup_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_signup_title" id="auth_signup_title" value="<?php echo esc_attr($settings['auth_signup_title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_signup_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_signup_subtitle" id="auth_signup_subtitle" value="<?php echo esc_attr($settings['auth_signup_subtitle']); ?>" class="large-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_signup_price"><?php _e('Price Badge', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_signup_price" id="auth_signup_price" value="<?php echo esc_attr($settings['auth_signup_price']); ?>" class="regular-text" placeholder="$35 Lifetime Access">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_signup_price_note"><?php _e('Price Note', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_signup_price_note" id="auth_signup_price_note" value="<?php echo esc_attr($settings['auth_signup_price_note']); ?>" class="regular-text" placeholder="One-time payment, forever access">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_signup_benefits"><?php _e('Benefits List', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <textarea name="auth_signup_benefits" id="auth_signup_benefits" rows="5" class="large-text"><?php echo esc_textarea($settings['auth_signup_benefits']); ?></textarea>
                                <p class="description"><?php _e('One benefit per line', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Logout Page -->
                <div class="fra-card">
                    <h2><?php _e('👋 Logout Page', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_logout_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_logout_title" id="auth_logout_title" value="<?php echo esc_attr($settings['auth_logout_title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_logout_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_logout_subtitle" id="auth_logout_subtitle" value="<?php echo esc_attr($settings['auth_logout_subtitle']); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Account Page -->
                <div class="fra-card">
                    <h2><?php _e('👤 Account Page', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_account_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_account_title" id="auth_account_title" value="<?php echo esc_attr(isset($settings['auth_account_title']) ? $settings['auth_account_title'] : 'Your Account'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_account_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_account_subtitle" id="auth_account_subtitle" value="<?php echo esc_attr(isset($settings['auth_account_subtitle']) ? $settings['auth_account_subtitle'] : 'Manage your membership and profile settings'); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Thank You Page -->
                <div class="fra-card">
                    <h2><?php _e('🎉 Thank You Page', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="auth_thankyou_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_thankyou_title" id="auth_thankyou_title" value="<?php echo esc_attr(isset($settings['auth_thankyou_title']) ? $settings['auth_thankyou_title'] : 'Welcome to Relo2France!'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="auth_thankyou_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="auth_thankyou_subtitle" id="auth_thankyou_subtitle" value="<?php echo esc_attr(isset($settings['auth_thankyou_subtitle']) ? $settings['auth_thankyou_subtitle'] : 'Your account has been created successfully. You now have full access to all member tools.'); ?>" class="large-text">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- ============================================================
                     IN-CHAT AUTHENTICATION
                     Login/Signup/Dashboard directly in the chat window
                     ============================================================ -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('💬 In-Chat Authentication', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Enable login, signup, and member dashboard directly in the chat window. Users never leave the main interface.', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Enable In-Chat Auth', 'france-relocation-assistant'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="inchat_auth_enabled" value="1" <?php checked(!empty($settings['inchat_auth_enabled'])); ?>>
                                    <?php _e('Show Login/Signup buttons in sidebar and handle auth in chat window', 'france-relocation-assistant'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="inchat_auth_membership_id"><?php _e('Membership ID', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="inchat_auth_membership_id" id="inchat_auth_membership_id" value="<?php echo esc_attr($settings['inchat_auth_membership_id'] ?? ''); ?>" class="regular-text" placeholder="e.g., 123">
                                <p class="description"><?php _e('Required for signup. Find this in MemberPress > Memberships (hover over membership name, look for post=XXX in URL)', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="inchat_auth_signup_redirect"><?php _e('After Signup Redirect', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="url" name="inchat_auth_signup_redirect" id="inchat_auth_signup_redirect" value="<?php echo esc_attr($settings['inchat_auth_signup_redirect'] ?? ''); ?>" class="regular-text" placeholder="<?php echo esc_attr(home_url('/')); ?>">
                                <p class="description"><?php _e('URL to redirect users after successful signup. Leave empty to stay on MemberPress thank-you page.', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Sidebar Button Settings -->
                <div class="fra-card">
                    <h2><?php _e('🔘 Sidebar Buttons', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Customize the auth buttons at the bottom of the sidebar', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="inchat_auth_login_btn_text"><?php _e('Login Button Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="inchat_auth_login_btn_text" id="inchat_auth_login_btn_text" value="<?php echo esc_attr($settings['inchat_auth_login_btn_text'] ?? 'Login'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="inchat_auth_signup_btn_text"><?php _e('Signup Button Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="inchat_auth_signup_btn_text" id="inchat_auth_signup_btn_text" value="<?php echo esc_attr($settings['inchat_auth_signup_btn_text'] ?? 'Get Access'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="inchat_auth_logout_btn_text"><?php _e('Logout Button Text', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="inchat_auth_logout_btn_text" id="inchat_auth_logout_btn_text" value="<?php echo esc_attr($settings['inchat_auth_logout_btn_text'] ?? 'Logout'); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                    
                    <div class="fra-color-grid" style="margin-top: 1rem;">
                        <div class="fra-color-item">
                            <label><?php _e('Button Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="inchat_auth_btn_bg" value="<?php echo esc_attr($settings['inchat_auth_btn_bg'] ?? '#ea580c'); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Button Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="inchat_auth_btn_text" value="<?php echo esc_attr($settings['inchat_auth_btn_text'] ?? '#ffffff'); ?>">
                        </div>
                    </div>
                    
                    <table class="form-table" style="margin-top: 1rem;">
                        <tr>
                            <th><label for="inchat_auth_btn_size"><?php _e('Button Font Size', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="number" name="inchat_auth_btn_size" id="inchat_auth_btn_size" value="<?php echo esc_attr($settings['inchat_auth_btn_size'] ?? '14'); ?>" min="10" max="20" style="width: 80px;"> px
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Dashboard Settings -->
                <div class="fra-card">
                    <h2><?php _e('📊 Member Dashboard', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Shown in the chat window after login', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="inchat_auth_dashboard_title"><?php _e('Dashboard Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="inchat_auth_dashboard_title" id="inchat_auth_dashboard_title" value="<?php echo esc_attr($settings['inchat_auth_dashboard_title'] ?? 'Member Dashboard'); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="inchat_auth_welcome_message"><?php _e('Welcome Message', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <textarea name="inchat_auth_welcome_message" id="inchat_auth_welcome_message" class="large-text" rows="2"><?php echo esc_textarea($settings['inchat_auth_welcome_message'] ?? 'Welcome! Complete your profile to get personalized recommendations.'); ?></textarea>
                                <p class="description"><?php _e('Shown to new users after first login', 'france-relocation-assistant'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Colors Tab -->
        <div class="fra-tab-content" data-tab="colors">
            <div class="fra-customizer-grid">
                <!-- Header Section -->
                <div class="fra-card">
                    <h2><?php _e('Header Section', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('The dark header bar with title and subtitle', 'france-relocation-assistant'); ?></p>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Header Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="header_bg_color" value="<?php echo esc_attr($settings['header_bg_color']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Header Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="header_text_color" value="<?php echo esc_attr($settings['header_text_color']); ?>">
                        </div>
                    </div>
                    
                    <table class="form-table" style="margin-top: 15px;">
                        <tr>
                            <th><label for="header_title"><?php _e('Header Title', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="header_title" id="header_title" value="<?php echo esc_attr($settings['header_title']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="header_subtitle"><?php _e('Header Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <input type="text" name="header_subtitle" id="header_subtitle" value="<?php echo esc_attr($settings['header_subtitle']); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="header_show_flag"><?php _e('Show Flag Icon', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="header_show_flag" id="header_show_flag" <?php checked($settings['header_show_flag']); ?>>
                                    <?php _e('Display French flag next to title', 'france-relocation-assistant'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Main Colors -->
                <div class="fra-card">
                    <h2><?php _e('Main Colors', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_background" value="<?php echo esc_attr($settings['color_background']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Cards/Panels', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_card" value="<?php echo esc_attr($settings['color_card']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Primary Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_text" value="<?php echo esc_attr($settings['color_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Secondary Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_text_light" value="<?php echo esc_attr($settings['color_text_light']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Accent (Orange)', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_accent" value="<?php echo esc_attr($settings['color_accent']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Borders', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_border" value="<?php echo esc_attr($settings['color_border']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Colors -->
                <div class="fra-card">
                    <h2><?php _e('Navigation Sidebar', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Nav Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_nav_bg" value="<?php echo esc_attr($settings['color_nav_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Nav Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_nav_text" value="<?php echo esc_attr($settings['color_nav_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Hover Color', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_nav_hover" value="<?php echo esc_attr($settings['color_nav_hover']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Active Item BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_nav_active_bg" value="<?php echo esc_attr($settings['color_nav_active_bg']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Chat Area Colors -->
                <div class="fra-card">
                    <h2><?php _e('Chat Area', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Chat Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_chat_bg" value="<?php echo esc_attr($settings['color_chat_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Input Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_chat_input_bg" value="<?php echo esc_attr($settings['color_chat_input_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('User Message BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_user_msg_bg" value="<?php echo esc_attr($settings['color_user_msg_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('AI Message BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_ai_msg_bg" value="<?php echo esc_attr($settings['color_ai_msg_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('AI Header BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_ai_header_bg" value="<?php echo esc_attr($settings['color_ai_header_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Input Border', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_chat_input_border" value="<?php echo esc_attr($settings['color_chat_input_border']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Button Colors -->
                <div class="fra-card">
                    <h2><?php _e('Buttons', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Primary Button BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_btn_primary_bg" value="<?php echo esc_attr($settings['color_btn_primary_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Primary Button Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_btn_primary_text" value="<?php echo esc_attr($settings['color_btn_primary_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Secondary Button BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_btn_secondary_bg" value="<?php echo esc_attr($settings['color_btn_secondary_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Secondary Button Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_btn_secondary_text" value="<?php echo esc_attr($settings['color_btn_secondary_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Send Button BG', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_send_btn_bg" value="<?php echo esc_attr($settings['color_send_btn_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Send Button Icon', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_send_btn_text" value="<?php echo esc_attr($settings['color_send_btn_text']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Status & Day Counter Colors -->
                <div class="fra-card">
                    <h2><?php _e('Status & Day Counter', 'france-relocation-assistant'); ?></h2>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Success', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_success" value="<?php echo esc_attr($settings['color_success']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Warning', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_warning" value="<?php echo esc_attr($settings['color_warning']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Danger', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_danger" value="<?php echo esc_attr($settings['color_danger']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('France (Calendar)', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_france" value="<?php echo esc_attr($settings['color_france']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('US (Calendar)', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_us" value="<?php echo esc_attr($settings['color_us']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Other (Calendar)', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_other" value="<?php echo esc_attr($settings['color_other']); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Member Area Colors -->
                <div class="fra-card">
                    <h2><?php _e('Member Area Colors', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Customize the member dropdown and profile header colors (Braun aesthetic by default).', 'france-relocation-assistant'); ?></p>
                    
                    <div class="fra-color-grid">
                        <div class="fra-color-item">
                            <label><?php _e('Member Header Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_member_header_bg" value="<?php echo esc_attr($settings['color_member_header_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Member Header Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_member_header_text" value="<?php echo esc_attr($settings['color_member_header_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Member Header Accent', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_member_header_accent" value="<?php echo esc_attr($settings['color_member_header_accent']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Profile Header Background', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_profile_header_bg" value="<?php echo esc_attr($settings['color_profile_header_bg']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Profile Header Text', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_profile_header_text" value="<?php echo esc_attr($settings['color_profile_header_text']); ?>">
                        </div>
                        <div class="fra-color-item">
                            <label><?php _e('Progress Bar Fill', 'france-relocation-assistant'); ?></label>
                            <input type="color" name="color_profile_progress_fill" value="<?php echo esc_attr($settings['color_profile_progress_fill']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Text & Labels Tab -->
        <div class="fra-tab-content" data-tab="text">
            <div class="fra-customizer-grid">
                <!-- Welcome Screen -->
                <div class="fra-card">
                    <h2><?php _e('Welcome Screen', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="welcome_icon"><?php _e('Icon/Emoji', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="text" name="welcome_icon" id="welcome_icon" value="<?php echo esc_attr($settings['welcome_icon']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="welcome_title"><?php _e('Title', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="text" name="welcome_title" id="welcome_title" value="<?php echo esc_attr($settings['welcome_title']); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th><label for="welcome_subtitle"><?php _e('Subtitle', 'france-relocation-assistant'); ?></label></th>
                            <td><textarea name="welcome_subtitle" id="welcome_subtitle" rows="2" class="large-text"><?php echo esc_textarea($settings['welcome_subtitle']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><?php _e('Quick Topics', 'france-relocation-assistant'); ?></th>
                            <td><label><input type="checkbox" name="show_quick_topics" <?php checked($settings['show_quick_topics']); ?>> <?php _e('Show quick topic buttons', 'france-relocation-assistant'); ?></label></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Quick Topic Buttons -->
                <div class="fra-card">
                    <h2><?php _e('Quick Topic Buttons', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Customize the quick access buttons on the welcome screen.', 'france-relocation-assistant'); ?></p>
                    
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="fra-quick-topic-row">
                        <strong><?php printf(__('Button %d:', 'france-relocation-assistant'), $i); ?></strong>
                        <input type="text" name="quick_topic_<?php echo $i; ?>_label" value="<?php echo esc_attr($settings["quick_topic_{$i}_label"]); ?>" placeholder="<?php _e('Label', 'france-relocation-assistant'); ?>" class="regular-text">
                        <input type="text" name="quick_topic_<?php echo $i; ?>_cat" value="<?php echo esc_attr($settings["quick_topic_{$i}_cat"]); ?>" placeholder="<?php _e('Category', 'france-relocation-assistant'); ?>" class="small-text">
                        <input type="text" name="quick_topic_<?php echo $i; ?>_topic" value="<?php echo esc_attr($settings["quick_topic_{$i}_topic"]); ?>" placeholder="<?php _e('Topic', 'france-relocation-assistant'); ?>" class="small-text">
                    </div>
                    <?php endfor; ?>
                    <p class="description"><?php _e('Use "_day_counter" as category for the day counter button.', 'france-relocation-assistant'); ?></p>
                </div>
                
                <!-- Chat Input -->
                <div class="fra-card">
                    <h2><?php _e('Chat Input Area', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="chat_placeholder"><?php _e('Input Placeholder', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="text" name="chat_placeholder" id="chat_placeholder" value="<?php echo esc_attr($settings['chat_placeholder']); ?>" class="large-text"></td>
                        </tr>
                        <tr>
                            <th><label for="chat_hint"><?php _e('Hint Text', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="text" name="chat_hint" id="chat_hint" value="<?php echo esc_attr($settings['chat_hint']); ?>" class="large-text"></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Search Bar -->
                <div class="fra-card">
                    <h2><?php _e('Search Bar', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Show Search', 'france-relocation-assistant'); ?></th>
                            <td><label><input type="checkbox" name="show_search_bar" <?php checked($settings['show_search_bar']); ?>> <?php _e('Show search bar in navigation', 'france-relocation-assistant'); ?></label></td>
                        </tr>
                        <tr>
                            <th><label for="search_placeholder"><?php _e('Placeholder', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="text" name="search_placeholder" id="search_placeholder" value="<?php echo esc_attr($settings['search_placeholder']); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Layout Tab -->
        <div class="fra-tab-content" data-tab="layout">
            <div class="fra-customizer-grid">
                <!-- Dimensions -->
                <div class="fra-card">
                    <h2><?php _e('Dimensions', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="max_width"><?php _e('Max Width', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="max_width" id="max_width" value="<?php echo esc_attr($settings['max_width']); ?>" min="800" max="2000" step="10"> px</td>
                        </tr>
                        <tr>
                            <th><label for="min_height"><?php _e('Min Height', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="min_height" id="min_height" value="<?php echo esc_attr($settings['min_height']); ?>" min="400" max="1200" step="10"> px</td>
                        </tr>
                        <tr>
                            <th><label for="max_height"><?php _e('Max Height', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="max_height" id="max_height" value="<?php echo esc_attr($settings['max_height']); ?>" min="600" max="1600" step="10"> px</td>
                        </tr>
                        <tr>
                            <th><label for="nav_width"><?php _e('Navigation Width', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="nav_width" id="nav_width" value="<?php echo esc_attr($settings['nav_width']); ?>" min="200" max="400" step="10"> px</td>
                        </tr>
                        <tr>
                            <th><label for="border_radius"><?php _e('Border Radius', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="border_radius" id="border_radius" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="24" step="1"> px</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Typography -->
                <div class="fra-card">
                    <h2><?php _e('Typography', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="font_family"><?php _e('Font Family', 'france-relocation-assistant'); ?></label></th>
                            <td>
                                <select name="font_family" id="font_family" class="regular-text">
                                    <option value='-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' <?php selected($settings['font_family'], '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'); ?>>System Default</option>
                                    <option value='"Helvetica Neue", Helvetica, Arial, sans-serif' <?php selected($settings['font_family'], '"Helvetica Neue", Helvetica, Arial, sans-serif'); ?>>Helvetica Neue</option>
                                    <option value='"Segoe UI", Tahoma, Geneva, sans-serif' <?php selected($settings['font_family'], '"Segoe UI", Tahoma, Geneva, sans-serif'); ?>>Segoe UI</option>
                                    <option value='Georgia, "Times New Roman", serif' <?php selected($settings['font_family'], 'Georgia, "Times New Roman", serif'); ?>>Georgia (Serif)</option>
                                    <option value='"Inter", sans-serif' <?php selected($settings['font_family'], '"Inter", sans-serif'); ?>>Inter</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="font_size_base"><?php _e('Base Font Size', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="font_size_base" id="font_size_base" value="<?php echo esc_attr($settings['font_size_base']); ?>" min="12" max="18" step="1"> px</td>
                        </tr>
                        <tr>
                            <th><label for="font_size_title"><?php _e('Title Font Size', 'france-relocation-assistant'); ?></label></th>
                            <td><input type="number" name="font_size_title" id="font_size_title" value="<?php echo esc_attr($settings['font_size_title']); ?>" min="14" max="24" step="1"> px</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Toolbar Options -->
                <div class="fra-card">
                    <h2><?php _e('Toolbar', 'france-relocation-assistant'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Day Counter', 'france-relocation-assistant'); ?></th>
                            <td><label><input type="checkbox" name="show_day_counter_btn" <?php checked($settings['show_day_counter_btn']); ?>> <?php _e('Show 183-Day Counter button', 'france-relocation-assistant'); ?></label></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Sidebar Display Options -->
                <div class="fra-card">
                    <h2><?php _e('📋 Sidebar Display', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Control what appears in the navigation sidebar', 'france-relocation-assistant'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><?php _e('FREE Badge', 'france-relocation-assistant'); ?></th>
                            <td>
                                <label class="fra-toggle">
                                    <input type="checkbox" name="show_free_badge" <?php checked($settings['show_free_badge']); ?>>
                                    <span class="fra-toggle-slider"></span>
                                </label>
                                <span class="description" style="margin-left: 10px;"><?php _e('Show "✓ FREE Knowledge Base" label above topics', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Members Preview', 'france-relocation-assistant'); ?></th>
                            <td>
                                <label class="fra-toggle">
                                    <input type="checkbox" name="show_members_preview" <?php checked($settings['show_members_preview']); ?>>
                                    <span class="fra-toggle-slider"></span>
                                </label>
                                <span class="description" style="margin-left: 10px;"><?php _e('Show locked member pages to non-members (visible but not clickable)', 'france-relocation-assistant'); ?></span>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top: 15px; padding: 12px; background: #e7f5ff; border-left: 3px solid #0077cc; border-radius: 0 6px 6px 0;">
                        <p style="margin: 0; font-size: 13px;">
                            <strong><?php _e('Tip:', 'france-relocation-assistant'); ?></strong> 
                            <?php _e('Showing locked member pages to visitors helps them see the value of membership before signing up.', 'france-relocation-assistant'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tab -->
        <div class="fra-tab-content" data-tab="navigation">
            <div class="fra-customizer-grid">
                
                <!-- Knowledge Base Categories - Drag & Drop -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('📚 Knowledge Base Categories', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Drag to reorder categories. Edit icons and titles as needed.', 'france-relocation-assistant'); ?></p>
                    
                    <?php
                    // Get current KB order and settings
                    $kb_order = get_option('fra_kb_order', array('visas', 'property', 'healthcare', 'taxes', 'driving', 'shipping', 'banking', 'settling'));
                    $kb_settings = get_option('fra_kb_settings', array());
                    
                    // Default icons and labels
                    $kb_defaults = array(
                        'visas' => array('icon' => '🛂', 'label' => 'Visas & Immigration'),
                        'property' => array('icon' => '🏠', 'label' => 'Property Purchase'),
                        'healthcare' => array('icon' => '🏥', 'label' => 'Healthcare & Retirement'),
                        'taxes' => array('icon' => '📋', 'label' => 'Taxes & Law'),
                        'driving' => array('icon' => '🚗', 'label' => 'Driving'),
                        'shipping' => array('icon' => '📦', 'label' => 'Shipping & Pets'),
                        'banking' => array('icon' => '🏦', 'label' => 'Banking'),
                        'settling' => array('icon' => '🏡', 'label' => 'Settling In'),
                    );
                    ?>
                    
                    <div class="fra-sortable-list" id="fra-kb-sortable">
                        <?php foreach ($kb_order as $index => $key) : 
                            $defaults = isset($kb_defaults[$key]) ? $kb_defaults[$key] : array('icon' => '📄', 'label' => ucfirst($key));
                            $item_settings = isset($kb_settings[$key]) ? $kb_settings[$key] : array();
                            $icon = isset($item_settings['icon']) ? $item_settings['icon'] : $defaults['icon'];
                            $label = isset($item_settings['label']) ? $item_settings['label'] : $defaults['label'];
                            $enabled = isset($item_settings['enabled']) ? $item_settings['enabled'] : true;
                        ?>
                        <div class="fra-sortable-item" data-key="<?php echo esc_attr($key); ?>">
                            <div class="fra-drag-handle">☰</div>
                            <input type="hidden" name="kb_order[]" value="<?php echo esc_attr($key); ?>">
                            <input type="checkbox" 
                                   name="kb_settings[<?php echo esc_attr($key); ?>][enabled]" 
                                   value="1" 
                                   <?php checked($enabled); ?>
                                   class="fra-item-enabled">
                            <input type="text" 
                                   name="kb_settings[<?php echo esc_attr($key); ?>][icon]" 
                                   value="<?php echo esc_attr($icon); ?>" 
                                   class="fra-item-icon"
                                   placeholder="📄">
                            <input type="text" 
                                   name="kb_settings[<?php echo esc_attr($key); ?>][label]" 
                                   value="<?php echo esc_attr($label); ?>" 
                                   class="fra-item-label regular-text"
                                   placeholder="<?php echo esc_attr($defaults['label']); ?>">
                            <span class="fra-item-key">(<?php echo esc_html($key); ?>)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="description" style="margin-top: 15px;">
                        <strong><?php _e('Tip:', 'france-relocation-assistant'); ?></strong> 
                        <?php _e('Uncheck items to hide them from the sidebar. The knowledge base content will still be searchable.', 'france-relocation-assistant'); ?>
                    </p>
                </div>
                
                <!-- Member Tools - Drag & Drop -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('🔒 Member Tools', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Drag to reorder member tools. Edit icons and titles as needed. These appear locked for non-members.', 'france-relocation-assistant'); ?></p>
                    
                    <?php
                    // Get current member tools order and settings
                    $mt_order = get_option('fra_member_tools_order', array('dashboard', 'my-checklists', 'create-documents', 'upload-verify', 'glossary', 'guides'));
                    $mt_settings = get_option('fra_member_tools_settings', array());
                    
                    // Default icons and labels
                    $mt_defaults = array(
                        'dashboard' => array('icon' => '📊', 'label' => 'Dashboard'),
                        'my-checklists' => array('icon' => '📋', 'label' => 'My Checklists'),
                        'create-documents' => array('icon' => '📄', 'label' => 'Create Documents'),
                        'upload-verify' => array('icon' => '📎', 'label' => 'Upload & Verify'),
                        'glossary' => array('icon' => '📚', 'label' => 'Glossary'),
                        'guides' => array('icon' => '📖', 'label' => 'Guides'),
                    );
                    ?>
                    
                    <div class="fra-sortable-list" id="fra-mt-sortable">
                        <?php foreach ($mt_order as $index => $key) : 
                            $defaults = isset($mt_defaults[$key]) ? $mt_defaults[$key] : array('icon' => '📄', 'label' => ucfirst(str_replace('-', ' ', $key)));
                            $item_settings = isset($mt_settings[$key]) ? $mt_settings[$key] : array();
                            $icon = isset($item_settings['icon']) ? $item_settings['icon'] : $defaults['icon'];
                            $label = isset($item_settings['label']) ? $item_settings['label'] : $defaults['label'];
                            $enabled = isset($item_settings['enabled']) ? $item_settings['enabled'] : true;
                        ?>
                        <div class="fra-sortable-item" data-key="<?php echo esc_attr($key); ?>">
                            <div class="fra-drag-handle">☰</div>
                            <input type="hidden" name="mt_order[]" value="<?php echo esc_attr($key); ?>">
                            <input type="checkbox" 
                                   name="mt_settings[<?php echo esc_attr($key); ?>][enabled]" 
                                   value="1" 
                                   <?php checked($enabled); ?>
                                   class="fra-item-enabled">
                            <input type="text" 
                                   name="mt_settings[<?php echo esc_attr($key); ?>][icon]" 
                                   value="<?php echo esc_attr($icon); ?>" 
                                   class="fra-item-icon"
                                   placeholder="📄">
                            <input type="text" 
                                   name="mt_settings[<?php echo esc_attr($key); ?>][label]" 
                                   value="<?php echo esc_attr($label); ?>" 
                                   class="fra-item-label regular-text"
                                   placeholder="<?php echo esc_attr($defaults['label']); ?>">
                            <span class="fra-item-key">(<?php echo esc_html($key); ?>)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid #667eea;">
                        <h4 style="margin: 0 0 10px 0;"><?php _e('Member Tools Teaser', 'france-relocation-assistant'); ?></h4>
                        <p class="description" style="margin-bottom: 10px;"><?php _e('Message shown to non-members below the locked tools:', 'france-relocation-assistant'); ?></p>
                        <input type="text" 
                               name="mt_teaser_message" 
                               value="<?php echo esc_attr(get_option('fra_mt_teaser_message', 'Unlock personalized documents, checklists, and guides')); ?>" 
                               class="large-text">
                    </div>
                </div>
                
                <!-- Common Icons Reference -->
                <div class="fra-card fra-card-full">
                    <h2><?php _e('😀 Icon Reference', 'france-relocation-assistant'); ?></h2>
                    <p class="description"><?php _e('Click an icon to copy it to your clipboard:', 'france-relocation-assistant'); ?></p>
                    
                    <div class="fra-icon-grid">
                        <?php
                        $icons = array(
                            'Documents' => array('📄', '📋', '📝', '📑', '🗂️', '📁', '📂', '🗃️'),
                            'Location' => array('🏠', '🏡', '🏢', '🏦', '🏥', '✈️', '🚗', '🚢'),
                            'People' => array('👤', '👥', '👨‍👩‍👧', '🧑‍💼', '👮', '🧑‍⚕️', '🧑‍💻', '🧑‍🎓'),
                            'Actions' => array('✅', '❌', '🔒', '🔓', '📤', '📥', '🔍', '💡'),
                            'Finance' => array('💰', '💳', '💵', '📊', '📈', '🏧', '💱', '🧾'),
                            'Misc' => array('🛂', '📦', '📚', '📖', '🎯', '⭐', '🔑', '🎓'),
                        );
                        foreach ($icons as $category => $icon_list) :
                        ?>
                        <div class="fra-icon-category">
                            <h4><?php echo esc_html($category); ?></h4>
                            <div class="fra-icon-buttons">
                                <?php foreach ($icon_list as $icon) : ?>
                                <button type="button" class="fra-copy-icon" data-icon="<?php echo esc_attr($icon); ?>" title="<?php _e('Click to copy', 'france-relocation-assistant'); ?>">
                                    <?php echo $icon; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Save Actions -->
        <div class="fra-customizer-actions">
            <input type="submit" name="fra_save_customizer" class="button button-primary button-hero" value="<?php _e('💾 Save Changes', 'france-relocation-assistant'); ?>">
            <input type="submit" name="fra_reset_customizer" class="button button-secondary" value="<?php _e('↩️ Reset to Defaults', 'france-relocation-assistant'); ?>" onclick="return confirm('<?php _e('Reset all customizations to default values?', 'france-relocation-assistant'); ?>');">
        </div>
    </form>
</div>

<style>
/* Tabs */
.fra-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    border-bottom: 2px solid #ddd;
    padding-bottom: 0;
}
.fra-tab {
    padding: 12px 20px;
    background: #f5f5f5;
    border: none;
    border-radius: 8px 8px 0 0;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.2s ease;
}
.fra-tab:hover {
    background: #eee;
    color: #333;
}
.fra-tab.active {
    background: #fff;
    color: #1e3a5f;
    border: 2px solid #ddd;
    border-bottom: 2px solid #fff;
    margin-bottom: -2px;
}
.fra-tab-content {
    display: none !important;
}
.fra-tab-content.active {
    display: block !important;
}

/* Grid */
.fra-customizer-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
@media (max-width: 1200px) {
    .fra-customizer-grid { grid-template-columns: 1fr; }
}
.fra-card-full { grid-column: 1 / -1; }

/* Color Grid */
.fra-color-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
@media (max-width: 600px) {
    .fra-color-grid { grid-template-columns: repeat(2, 1fr); }
}
.fra-color-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.fra-color-item label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}
.fra-color-item input[type="color"] {
    width: 100%;
    height: 40px;
    padding: 2px;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
}

/* Quick Topic Rows */
.fra-quick-topic-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 6px;
    margin-bottom: 10px;
}
.fra-quick-topic-row strong {
    min-width: 70px;
}
.fra-quick-topic-row input {
    flex: 1;
}
.fra-quick-topic-row .small-text {
    flex: 0 0 120px;
}

/* Sortable/Drag-Drop Lists */
.fra-sortable-list {
    margin-top: 15px;
}
.fra-sortable-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
}
.fra-sortable-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}
.fra-sortable-item.dragging {
    opacity: 0.5;
    border: 2px dashed #667eea;
}
.fra-sortable-item.drag-over {
    border-top: 3px solid #667eea;
    margin-top: -3px;
}
.fra-drag-handle {
    cursor: grab;
    font-size: 18px;
    color: #999;
    padding: 5px;
    user-select: none;
}
.fra-drag-handle:hover {
    color: #667eea;
}
.fra-drag-handle:active {
    cursor: grabbing;
}
.fra-item-enabled {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.fra-item-icon {
    width: 50px !important;
    text-align: center;
    font-size: 18px;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.fra-item-label {
    flex: 1;
}
.fra-item-key {
    font-size: 11px;
    color: #999;
    font-family: monospace;
    min-width: 100px;
}

/* Icon Grid */
.fra-icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}
.fra-icon-category h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    color: #666;
}
.fra-icon-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.fra-copy-icon {
    padding: 8px 12px;
    font-size: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.fra-copy-icon:hover {
    background: #f0f0f0;
    border-color: #667eea;
    transform: scale(1.1);
}
.fra-copy-icon.copied {
    background: #d4edda;
    border-color: #28a745;
}

/* Actions */
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

/* Nav labels table */
.fra-nav-labels-table th {
    width: 120px;
}

/* Logo Upload Area */
.fra-logo-upload-area {
    text-align: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 15px;
}
.fra-logo-preview {
    width: 200px;
    height: 200px;
    margin: 0 auto 15px;
    border: 2px dashed #ddd;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    overflow: hidden;
}
.fra-logo-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.fra-logo-placeholder {
    text-align: center;
    color: #999;
}
.fra-logo-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
}
.fra-logo-placeholder p {
    margin: 10px 0 0;
    font-size: 13px;
}
.fra-logo-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
}

/* Toggle Switch */
.fra-toggle {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
    vertical-align: middle;
}
.fra-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}
.fra-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
    border-radius: 26px;
}
.fra-toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
.fra-toggle input:checked + .fra-toggle-slider {
    background-color: #22c55e;
}
.fra-toggle input:checked + .fra-toggle-slider:before {
    transform: translateX(24px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    document.querySelectorAll('.fra-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetTab = this.getAttribute('data-tab');
            
            // Update tab buttons
            document.querySelectorAll('.fra-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.fra-tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            document.querySelector('.fra-tab-content[data-tab="' + targetTab + '"]').classList.add('active');
        });
    });
    
    // Icon picker - copy to clipboard and show feedback
    document.querySelectorAll('.fra-icon-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var icon = this.getAttribute('data-icon');
            
            // Copy to clipboard
            navigator.clipboard.writeText(icon).then(function() {
                // Visual feedback
                var originalText = btn.innerHTML;
                btn.innerHTML = '✓';
                btn.style.background = '#dcfce7';
                btn.style.borderColor = '#22c55e';
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = '#fff';
                    btn.style.borderColor = '#ddd';
                }, 800);
            });
        });
    });
    
    // Auto-check enable checkbox when user types in a field
    document.querySelectorAll('.fra-member-page-row').forEach(function(row) {
        var enableCheckbox = row.querySelector('.fra-page-enabled');
        var titleInput = row.querySelector('.fra-page-title');
        var urlInput = row.querySelector('.fra-page-url');
        
        [titleInput, urlInput].forEach(function(input) {
            if (input) {
                input.addEventListener('input', function() {
                    if (this.value.trim() !== '') {
                        enableCheckbox.checked = true;
                    }
                });
            }
        });
    });
    
    // Media uploader for logo
    var logoFrame;
    var uploadBtn = document.getElementById('upload-logo-btn');
    var removeBtn = document.getElementById('remove-logo-btn');
    var logoInput = document.getElementById('site_header_logo');
    var logoPreview = document.getElementById('logo-preview');
    
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // If frame exists, open it
            if (logoFrame) {
                logoFrame.open();
                return;
            }
            
            // Create media frame
            logoFrame = wp.media({
                title: '<?php _e('Select or Upload Logo', 'france-relocation-assistant'); ?>',
                button: {
                    text: '<?php _e('Use as Logo', 'france-relocation-assistant'); ?>'
                },
                multiple: false
            });
            
            // When image selected
            logoFrame.on('select', function() {
                var attachment = logoFrame.state().get('selection').first().toJSON();
                logoInput.value = attachment.url;
                logoPreview.innerHTML = '<img src="' + attachment.url + '" alt="Logo">';
                removeBtn.style.display = 'inline-block';
            });
            
            logoFrame.open();
        });
    }
    
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logoInput.value = '';
            logoPreview.innerHTML = '<div class="fra-logo-placeholder"><span class="dashicons dashicons-format-image"></span><p><?php _e('No logo uploaded', 'france-relocation-assistant'); ?></p></div>';
            this.style.display = 'none';
        });
    }
    
    // Drag and Drop for sortable lists
    document.querySelectorAll('.fra-sortable-list').forEach(function(list) {
        var draggedItem = null;
        
        list.querySelectorAll('.fra-sortable-item').forEach(function(item) {
            var handle = item.querySelector('.fra-drag-handle');
            
            // Make item draggable via handle
            handle.addEventListener('mousedown', function() {
                item.setAttribute('draggable', 'true');
            });
            
            item.addEventListener('dragend', function() {
                item.setAttribute('draggable', 'false');
            });
            
            item.addEventListener('dragstart', function(e) {
                draggedItem = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                list.querySelectorAll('.fra-sortable-item').forEach(function(i) {
                    i.classList.remove('drag-over');
                });
                draggedItem = null;
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (this !== draggedItem) {
                    this.classList.add('drag-over');
                }
            });
            
            item.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                if (this !== draggedItem) {
                    // Insert dragged item before this item
                    var items = Array.from(list.querySelectorAll('.fra-sortable-item'));
                    var draggedIndex = items.indexOf(draggedItem);
                    var targetIndex = items.indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        this.parentNode.insertBefore(draggedItem, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedItem, this);
                    }
                }
            });
        });
    });
    
    // Icon copy buttons (new version)
    document.querySelectorAll('.fra-copy-icon').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var icon = this.getAttribute('data-icon');
            
            navigator.clipboard.writeText(icon).then(function() {
                btn.classList.add('copied');
                setTimeout(function() {
                    btn.classList.remove('copied');
                }, 600);
            });
        });
    });
});
</script>
