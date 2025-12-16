<?php
/**
 * Shortcode Template - France Relocation Assistant Chat Interface
 * 
 * Renders the main AI-powered chat interface with:
 * - Left sidebar with knowledge base categories and members area
 * - Right panel with chat messages and input
 * 
 * Usage: [france_relocation_assistant]
 * 
 * @package France_Relocation_Assistant
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// KNOWLEDGE BASE CATEGORIES
// Dynamically built from database - new topics appear automatically
// =============================================================================

// Get customizer settings for labels
$customizer_settings = get_option('fra_customizer', array());

// Get KB order and settings from new options
$kb_order = get_option('fra_kb_order', array('visas', 'property', 'healthcare', 'taxes', 'driving', 'shipping', 'banking', 'settling'));
$kb_settings = get_option('fra_kb_settings', array());

// Default category metadata (icons and labels)
$category_defaults = array(
    'visas' => array('icon' => '🛂', 'label' => 'Visas & Immigration'),
    'property' => array('icon' => '🏠', 'label' => 'Property Purchase'),
    'healthcare' => array('icon' => '🏥', 'label' => 'Healthcare & Retirement'),
    'taxes' => array('icon' => '📋', 'label' => 'Taxes & Law'),
    'driving' => array('icon' => '🚗', 'label' => 'Driving'),
    'shipping' => array('icon' => '📦', 'label' => 'Shipping & Pets'),
    'banking' => array('icon' => '🏦', 'label' => 'Banking'),
    'settling' => array('icon' => '🏡', 'label' => 'Settling In'),
);

// Build category_meta from settings, falling back to defaults
$category_meta = array();
foreach ($category_defaults as $key => $defaults) {
    $settings = isset($kb_settings[$key]) ? $kb_settings[$key] : array();
    $category_meta[$key] = array(
        'icon' => !empty($settings['icon']) ? $settings['icon'] : $defaults['icon'],
        'label' => !empty($settings['label']) ? $settings['label'] : $defaults['label'],
        'enabled' => isset($settings['enabled']) ? $settings['enabled'] : true,
    );
}

// Use the saved order
$category_order = $kb_order;

// Get knowledge base from database
$kb = get_option('fra_knowledge_base', array());

// Build categories dynamically from KB
$categories = array();
$processed_cats = array();

// First, add categories in saved order
foreach ($category_order as $cat_key) {
    // Skip if disabled
    if (isset($category_meta[$cat_key]['enabled']) && !$category_meta[$cat_key]['enabled']) {
        $processed_cats[] = $cat_key;
        continue;
    }
    
    if (isset($kb[$cat_key]) && is_array($kb[$cat_key]) && !empty($kb[$cat_key])) {
        $meta = isset($category_meta[$cat_key]) ? $category_meta[$cat_key] : array(
            'icon' => '📄',
            'label' => ucfirst(str_replace('_', ' ', $cat_key))
        );
        
        $subtopics = array();
        foreach ($kb[$cat_key] as $topic_key => $topic) {
            if (isset($topic['title'])) {
                $subtopics[$topic_key] = $topic['title'];
            }
        }
        
        if (!empty($subtopics)) {
            $categories[$cat_key] = array(
                'icon' => $meta['icon'],
                'label' => $meta['label'],
                'subtopics' => $subtopics
            );
        }
        $processed_cats[] = $cat_key;
    }
}

// Then add any additional categories not in the saved order
foreach ($kb as $cat_key => $topics) {
    if (in_array($cat_key, $processed_cats)) continue;
    if (!is_array($topics) || empty($topics)) continue;
    
    $meta = isset($category_meta[$cat_key]) ? $category_meta[$cat_key] : array(
        'icon' => '📄',
        'label' => ucfirst(str_replace('_', ' ', $cat_key))
    );
    
    $subtopics = array();
    foreach ($topics as $topic_key => $topic) {
        if (isset($topic['title'])) {
            $subtopics[$topic_key] = $topic['title'];
        }
    }
    
    if (!empty($subtopics)) {
        $categories[$cat_key] = array(
            'icon' => $meta['icon'],
            'label' => $meta['label'],
            'subtopics' => $subtopics
        );
    }
}

// =============================================================================
// QUICK TOPIC BUTTONS
// Shortcut buttons shown in the welcome screen
// =============================================================================
$quick_topics = array(
    array('cat' => 'visas', 'topic' => 'overview', 'label' => 'What visa do I need?'),
    array('cat' => 'property', 'topic' => 'overview', 'label' => 'Buying property'),
    array('cat' => 'taxes', 'topic' => 'residency', 'label' => '183-day rule'),
    array('cat' => 'healthcare', 'topic' => 'puma', 'label' => 'French healthcare'),
);

// =============================================================================
// LOAD CUSTOMIZER SETTINGS
// =============================================================================
$customizer = get_option('fra_customizer', array());

// Sidebar header settings
$header_bg           = $customizer['header_bg_color'] ?? '#1e3a5f';
$header_text         = $customizer['header_text_color'] ?? '#ffffff';
$header_title        = $customizer['header_title'] ?? 'France Relocation';
$header_subtitle     = $customizer['header_subtitle'] ?? 'US → France Guide';
$header_show_flag    = $customizer['header_show_flag'] ?? true;

// Member area color settings
$member_header_bg    = $customizer['color_member_header_bg'] ?? '#1a1a1a';
$member_header_text  = $customizer['color_member_header_text'] ?? '#fafaf8';
$member_header_accent = $customizer['color_member_header_accent'] ?? '#d4a853';
$profile_header_bg   = $customizer['color_profile_header_bg'] ?? '#1a1a1a';
$profile_header_text = $customizer['color_profile_header_text'] ?? '#fafaf8';
$profile_progress_fill = $customizer['color_profile_progress_fill'] ?? '#d4a853';

// Display toggles
$show_free_badge     = $customizer['show_free_badge'] ?? true;
$show_members_preview = $customizer['show_members_preview'] ?? true;

// In-chat auth settings (needed early for upgrade buttons)
$inchat_auth_enabled = $customizer['inchat_auth_enabled'] ?? true;
?>

<!-- ==========================================================================
     FRANCE RELOCATION ASSISTANT - MAIN CONTAINER
     ========================================================================== -->
<div id="fra-app" class="fra-container">
    
    <!-- Mobile Header (visible on small screens only) -->
    <div class="fra-mobile-header" style="background-color: <?php echo esc_attr($header_bg); ?>; color: <?php echo esc_attr($header_text); ?>;">
        <button class="fra-mobile-menu-btn" id="fra-mobile-menu-btn" aria-label="Open menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <h1><?php echo $header_show_flag ? '🇫🇷 ' : ''; ?><?php echo esc_html($header_title); ?></h1>
        <div style="width: 40px;"></div><!-- Spacer for centering -->
    </div>
    
    <!-- Mobile Overlay (for closing sidebar) -->
    <div class="fra-mobile-overlay" id="fra-mobile-overlay"></div>
    
    <div class="fra-app">
        
        <!-- ====================================================================
             LEFT PANEL - NAVIGATION SIDEBAR
             Contains: Header, Knowledge Base categories, Members Area, Tools
             ==================================================================== -->
        <aside class="fra-nav-panel" id="fra-nav-panel">
            
            <!-- Mobile Close Button -->
            <button class="fra-mobile-close-btn" id="fra-mobile-close-btn" aria-label="Close menu">×</button>
            
            <!-- Sidebar Header -->
            <div class="fra-nav-header" style="background-color: <?php echo esc_attr($header_bg); ?>; color: <?php echo esc_attr($header_text); ?>;">
                <h1><?php echo $header_show_flag ? '🇫🇷 ' : ''; ?><?php echo esc_html($header_title); ?></h1>
                <p><?php echo esc_html($header_subtitle); ?></p>
            </div>
            
            <!-- Scrollable Content Area (Knowledge Base + Members) -->
            <div class="fra-nav-scrollable">
            
                <!-- FREE Badge Label -->
                <?php if ($show_free_badge) : ?>
                <div class="fra-section-label fra-free-section">
                    <span class="fra-free-badge">✓ FREE</span>
                    <span class="fra-section-text"><?php _e('Knowledge Base', 'france-relocation-assistant'); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Knowledge Base Navigation Menu -->
                <nav class="fra-nav-menu">
                    <?php foreach ($categories as $cat_key => $category) : ?>
                        <div class="fra-nav-category" data-category="<?php echo esc_attr($cat_key); ?>">
                            <button class="fra-category-header" data-category="<?php echo esc_attr($cat_key); ?>">
                                <span class="fra-category-left">
                                    <span class="fra-category-icon"><?php echo $category['icon']; ?></span>
                                    <span><?php echo esc_html($category['label']); ?></span>
                                </span>
                                <span class="fra-category-arrow fra-expand-icon">+</span>
                            </button>
                            <div class="fra-subtopics" data-category="<?php echo esc_attr($cat_key); ?>">
                                <?php foreach ($category['subtopics'] as $topic_key => $topic_label) : ?>
                                    <button class="fra-subtopic-btn" 
                                            data-category="<?php echo esc_attr($cat_key); ?>" 
                                            data-topic="<?php echo esc_attr($topic_key); ?>">
                                        <?php echo esc_html($topic_label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </nav>
                <!-- /Knowledge Base Navigation Menu -->
                
                <!-- ============================================================
                     MEMBER TOOLS SECTION (from add-on plugins)
                     Rendered by France Relocation Member Tools add-on if active
                     ============================================================ -->
                <?php
                // Allow add-on plugins to render member navigation
                $member_nav_items = apply_filters('fra_navigation_items', array());
                
                // Extract metadata if present
                $member_meta = isset($member_nav_items['_member_tools_meta']) ? $member_nav_items['_member_tools_meta'] : null;
                unset($member_nav_items['_member_tools_meta']);
                
                // Determine if we should show this section
                $is_member = $member_meta ? $member_meta['is_member'] : false;
                
                // Only show if: has items AND (user is member OR preview is enabled)
                if (!empty($member_nav_items) && ($is_member || $show_members_preview)) :
                    $upgrade_url = $member_meta ? $member_meta['upgrade_url'] : get_option('fra_membership_url', '/membership/');
                    $teaser_message = $member_meta ? $member_meta['teaser_message'] : __('Get access to exclusive member tools!', 'france-relocation-assistant');
                ?>
                <div class="fra-member-tools-section">
                    
                    <!-- Section Header -->
                    <div class="fra-section-label <?php echo $is_member ? 'fra-members-label' : 'fra-locked-label'; ?>">
                        <span class="<?php echo $is_member ? 'fra-unlock-badge' : 'fra-lock-badge'; ?>"><?php echo $is_member ? '🔓' : '🔒'; ?></span>
                        <span class="fra-section-text"><?php _e('Member Tools', 'france-relocation-assistant'); ?></span>
                    </div>
                    
                    <?php if ($is_member && is_user_logged_in()) : 
                        // User dropdown for logged-in members
                        $current_user = wp_get_current_user();
                        
                        // Try to get name from: 1) Member Tools profile, 2) WP first_name, 3) display_name
                        $display_name = '';
                        
                        // Check Member Tools profile first
                        global $wpdb;
                        $profile_table = $wpdb->prefix . 'framt_profiles';
                        $profile_data = $wpdb->get_var($wpdb->prepare(
                            "SELECT profile_data FROM {$profile_table} WHERE user_id = %d",
                            $current_user->ID
                        ));
                        if ($profile_data) {
                            $profile = json_decode($profile_data, true);
                            if (!empty($profile['legal_first_name'])) {
                                $display_name = $profile['legal_first_name'];
                            }
                        }
                        
                        // Fall back to WordPress user fields
                        if (empty($display_name)) {
                            $display_name = $current_user->first_name ?: $current_user->display_name;
                        }
                        
                        // Final fallback - extract first word from display_name if it looks like "First Last"
                        if (strpos($display_name, ' ') !== false && empty($current_user->first_name)) {
                            $parts = explode(' ', $display_name);
                            $display_name = $parts[0];
                        }
                    ?>
                    <div class="fra-user-dropdown">
                        <button class="fra-user-dropdown-btn" id="fra-user-dropdown-btn" style="background: <?php echo esc_attr($member_header_bg); ?>; color: <?php echo esc_attr($member_header_text); ?>;">
                            <span class="fra-user-icon" style="background: <?php echo esc_attr($member_header_accent); ?>; color: <?php echo esc_attr($member_header_bg); ?>;">👤</span>
                            <span class="fra-user-name"><?php echo esc_html($display_name); ?></span>
                            <span class="fra-dropdown-arrow">▼</span>
                        </button>
                        <div class="fra-user-dropdown-menu" id="fra-user-dropdown-menu">
                            <a href="#" data-section="profile" class="fra-dropdown-item">My Visa Profile</a>
                            <a href="#" data-section="my-documents" class="fra-dropdown-item">My Visa Documents</a>
                            <a href="#" data-section="messages" class="fra-dropdown-item" id="fra-messages-link">
                                <span>My Messages</span>
                                <span class="fra-unread-badge" id="fra-messages-badge" style="display: none;"></span>
                            </a>
                            <div class="fra-dropdown-group">
                                <a href="#" id="fra-dropdown-account" class="fra-dropdown-item fra-dropdown-parent">My Membership Account</a>
                                <div class="fra-dropdown-children">
                                    <a href="#" data-account-section="subscriptions" class="fra-dropdown-item fra-dropdown-child">Subscriptions</a>
                                    <a href="#" data-account-section="payments" class="fra-dropdown-item fra-dropdown-child">Payments</a>
                                </div>
                            </div>
                            <a href="<?php echo esc_url(wp_logout_url(home_url('/?logged_out=1'))); ?>" class="fra-dropdown-item fra-dropdown-logout">Log Out</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Member Navigation Items -->
                    <nav class="fra-member-nav <?php echo !$is_member ? 'fra-locked-nav' : ''; ?>">
                        <?php foreach ($member_nav_items as $key => $item) : 
                            $is_locked = !empty($item['locked']);
                        ?>
                                <button class="fra-member-nav-btn <?php echo $is_locked ? 'fra-locked-item' : ''; ?>" data-section="<?php echo esc_attr($key); ?>" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                    <?php if (!empty($item['icon'])) : ?>
                                        <span class="fra-nav-icon"><?php echo esc_html($item['icon']); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html($item['label']); ?></span>
                                    <?php if ($is_locked) : ?>
                                        <span class="fra-lock-icon">🔒</span>
                                    <?php else : ?>
                                        <span class="fra-direct-arrow">→</span>
                                    <?php endif; ?>
                                </button>
                        <?php endforeach; ?>
                    </nav>
                    
                    <?php if (!$is_member) : ?>
                    <!-- Upgrade Prompt for Non-Members -->
                    <div class="fra-upgrade-prompt">
                        <p><?php echo esc_html($teaser_message); ?></p>
                        <?php if ($inchat_auth_enabled) : ?>
                        <button type="button" class="fra-upgrade-btn fra-inchat-signup-trigger">
                            <?php _e('Get Member Access', 'france-relocation-assistant'); ?>
                        </button>
                        <?php else : ?>
                        <a href="<?php echo esc_url($upgrade_url); ?>" class="fra-upgrade-btn">
                            <?php _e('Get Member Access', 'france-relocation-assistant'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
                <!-- ============================================================
                     MEMBERS AREA SECTION (Legacy - from membership-page.php settings)
                     Shows member pages (unlocked for members, locked preview for non-members)
                     ============================================================ -->
                <?php
                $membership = FRA_Membership::get_instance();
                $member_settings = get_option('fra_membership', array());
                $member_pages = isset($member_settings['member_pages']) ? $member_settings['member_pages'] : array();
                $has_access = $membership->user_has_access();
                $is_enabled = $membership->is_enabled();
                
                // Only show legacy section if membership is enabled AND there are member pages configured
                // AND the new member tools section is not active (to avoid duplication)
                if ($is_enabled && !empty($member_pages) && empty($member_nav_items)) :
                ?>
                <div class="fra-members-section">
                    <?php if ($has_access) : ?>
                    
                    <!-- MEMBER VIEW: Unlocked pages -->
                    <div class="fra-section-label fra-members-label">
                        <span class="fra-unlock-badge">🔓</span>
                        <span class="fra-section-text"><?php _e('Members Area', 'france-relocation-assistant'); ?></span>
                    </div>
                    <div class="fra-members-links">
                        <?php foreach ($member_pages as $page) : 
                            if (empty($page['title']) || empty($page['url'])) continue;
                        ?>
                            <a href="<?php echo esc_url($page['url']); ?>" class="fra-member-link">
                                <?php if (!empty($page['icon'])) : ?>
                                    <span class="fra-member-link-icon"><?php echo esc_html($page['icon']); ?></span>
                                <?php endif; ?>
                                <span><?php echo esc_html($page['title']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php else : ?>
                    
                    <!-- NON-MEMBER VIEW: Locked preview + upgrade prompt -->
                    <div class="fra-section-label fra-members-label fra-locked-label">
                        <span class="fra-lock-badge">🔒</span>
                        <span class="fra-section-text"><?php _e('Members Area', 'france-relocation-assistant'); ?></span>
                    </div>
                    
                    <?php if ($show_members_preview) : ?>
                    <!-- Locked Page Preview (visible but not clickable) -->
                    <div class="fra-members-links fra-locked-links">
                        <?php foreach ($member_pages as $page) : 
                            if (empty($page['title']) || empty($page['url'])) continue;
                        ?>
                            <div class="fra-member-link fra-member-link-locked">
                                <?php if (!empty($page['icon'])) : ?>
                                    <span class="fra-member-link-icon"><?php echo esc_html($page['icon']); ?></span>
                                <?php endif; ?>
                                <span><?php echo esc_html($page['title']); ?></span>
                                <span class="fra-link-lock-icon">🔒</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Upgrade Prompt -->
                    <div class="fra-upgrade-prompt">
                        <p><?php echo esc_html($member_settings['teaser_message'] ?? 'Get access to exclusive member resources!'); ?></p>
                        <?php if ($inchat_auth_enabled) : ?>
                        <button type="button" class="fra-upgrade-btn fra-inchat-signup-trigger">
                            <?php echo esc_html($member_settings['upgrade_button_text'] ?? 'Get Access'); ?>
                        </button>
                        <?php elseif (!empty($member_settings['upgrade_url'])) : ?>
                            <a href="<?php echo esc_url($member_settings['upgrade_url']); ?>" class="fra-upgrade-btn">
                                <?php echo esc_html($member_settings['upgrade_button_text'] ?? 'Get Access'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <!-- /MEMBERS AREA SECTION -->
                
            </div>
            <!-- /Scrollable Content Area -->
            
            <!-- ============================================================
                 AUTH BUTTONS SECTION (Login/Get Access or Logout)
                 Shown at the bottom of the sidebar
                 ============================================================ -->
            <?php
            $inchat_auth_enabled = $customizer['inchat_auth_enabled'] ?? true;
            if ($inchat_auth_enabled) :
                $is_logged_in = is_user_logged_in();
                $auth_btn_bg = $customizer['inchat_auth_btn_bg'] ?? '#ea580c';
                $auth_btn_text = $customizer['inchat_auth_btn_text'] ?? '#ffffff';
                $auth_btn_size = ($customizer['inchat_auth_btn_size'] ?? 14) . 'px';
                $login_btn_text = $customizer['inchat_auth_login_btn_text'] ?? 'Login';
                $signup_btn_text = $customizer['inchat_auth_signup_btn_text'] ?? 'Sign Up';
                $logout_btn_text = $customizer['inchat_auth_logout_btn_text'] ?? 'Logout';
            ?>
            <div class="fra-auth-buttons" style="padding: 0.75rem; border-top: 1px solid #e5e7eb; background: #f9fafb;">
                <?php if (!$is_logged_in) : ?>
                    <!-- Logged Out: Show Login and Get Access buttons -->
                    <div style="display: flex; gap: 0.5rem;">
                        <button 
                            type="button"
                            id="fra-inchat-login-btn" 
                            class="fra-inchat-auth-btn"
                            style="flex: 1; padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 6px; font-size: <?php echo esc_attr($auth_btn_size); ?>; font-weight: 500; cursor: pointer; transition: all 0.15s;"
                        >
                            <?php echo esc_html($login_btn_text); ?>
                        </button>
                        <button 
                            type="button"
                            id="fra-inchat-signup-btn" 
                            class="fra-inchat-auth-btn fra-inchat-auth-btn-primary"
                            style="flex: 1; padding: 0.625rem 1rem; border: none; background: <?php echo esc_attr($auth_btn_bg); ?>; color: <?php echo esc_attr($auth_btn_text); ?>; border-radius: 6px; font-size: <?php echo esc_attr($auth_btn_size); ?>; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                        >
                            <?php echo esc_html($signup_btn_text); ?>
                        </button>
                    </div>
                <?php else : ?>
                    <!-- Logged In: Show Logout button -->
                    <button 
                        type="button"
                        id="fra-inchat-logout-btn" 
                        class="fra-inchat-auth-btn"
                        style="width: 100%; padding: 0.625rem 1rem; border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 6px; font-size: <?php echo esc_attr($auth_btn_size); ?>; font-weight: 500; cursor: pointer; transition: all 0.15s;"
                    >
                        🚪 <?php echo esc_html($logout_btn_text); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Tools Section (fixed at bottom) -->
            <div class="fra-nav-tools">
                <?php if ($is_member && is_user_logged_in()) : ?>
                <button id="fra-day-counter-btn" class="fra-tool-btn" style="background: <?php echo esc_attr($member_header_bg); ?>; color: <?php echo esc_attr($member_header_text); ?>; border-color: <?php echo esc_attr($member_header_accent); ?>;">
                    📅 183-Day Counter
                </button>
                <?php else : ?>
                <div class="fra-tool-locked">
                    <span class="fra-tool-locked-label">🔒 📅 183-Day Counter</span>
                    <span class="fra-tool-locked-hint">Member feature</span>
                </div>
                <?php endif; ?>
            </div>
            
        </aside>
        <!-- /LEFT PANEL -->
        
        <!-- ====================================================================
             RIGHT PANEL - CHAT INTERFACE
             Contains: Message area, Welcome screen, Chat input, Member Tools
             ==================================================================== -->
        <main class="fra-chat-panel">
            
            <!-- Member Tools Content Container (hidden by default, shown when navigating) -->
            <div id="fra-member-content" class="fra-member-content" style="display: none;">
                <div class="fra-member-content-header">
                    <button id="fra-back-to-chat" class="fra-back-btn">
                        ← Back to Chat
                    </button>
                    <h2 id="fra-member-content-title">Member Tools</h2>
                </div>
                <div id="fra-member-content-body" class="fra-member-content-body">
                    <!-- Content loaded dynamically by Member Tools plugin -->
                    <div class="fra-loading">Loading...</div>
                </div>
            </div>
            <!-- /Member Tools Content Container -->
            
            <!-- ============================================================
                 IN-CHAT AUTH CONTAINERS
                 Login, Signup, Dashboard, Account views in chat window
                 ============================================================ -->
            <?php if ($inchat_auth_enabled) : 
                $membership_id = $customizer['inchat_auth_membership_id'] ?? '';
                $auth_login_title = $customizer['auth_login_title'] ?? 'Welcome Back';
                $auth_login_subtitle = $customizer['auth_login_subtitle'] ?? 'Sign in to access your relocation dashboard';
                $auth_signup_title = $customizer['auth_signup_title'] ?? 'Start Your France Journey';
                $auth_signup_subtitle = $customizer['auth_signup_subtitle'] ?? 'Create your account and get lifetime access';
                $auth_signup_price = $customizer['auth_signup_price'] ?? '$35 Lifetime Access';
                $auth_signup_price_note = $customizer['auth_signup_price_note'] ?? 'One-time payment, forever access';
                $auth_signup_benefits = $customizer['auth_signup_benefits'] ?? "AI-powered visa guidance\nStep-by-step relocation checklists\nDocument templates & generators\n183-day Schengen counter\nPriority email support";
                $dashboard_title = $customizer['inchat_auth_dashboard_title'] ?? 'Member Dashboard';
                $welcome_message = $customizer['inchat_auth_welcome_message'] ?? 'Welcome! Complete your profile to get personalized recommendations.';
                $account_title = $customizer['auth_account_title'] ?? 'Your Account';
                $account_subtitle = $customizer['auth_account_subtitle'] ?? 'Manage your membership and profile settings';
            ?>
            
            <!-- LOGIN VIEW -->
            <div id="fra-inchat-login" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-auth-card">
                        <h2><?php echo esc_html($auth_login_title); ?></h2>
                        <p class="fra-inchat-subtitle"><?php echo esc_html($auth_login_subtitle); ?></p>
                        
                        <div class="fra-inchat-form-wrap">
                            <?php echo do_shortcode('[mepr-login-form]'); ?>
                        </div>
                        
                        <div class="fra-inchat-auth-footer">
                            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot your password?</a>
                            <p>Don't have an account? <a href="#" id="fra-inchat-switch-to-signup"><strong>Sign Up</strong></a></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SIGNUP VIEW -->
            <div id="fra-inchat-signup" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-auth-card">
                        <h2><?php echo esc_html($auth_signup_title); ?></h2>
                        <p class="fra-inchat-subtitle"><?php echo esc_html($auth_signup_subtitle); ?></p>
                        
                        <?php if (!empty($auth_signup_price)) : ?>
                        <div class="fra-inchat-price-badge">
                            <span><?php echo esc_html($auth_signup_price); ?></span>
                            <?php if (!empty($auth_signup_price_note)) : ?>
                                <small><?php echo esc_html($auth_signup_price_note); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $benefits = array_filter(array_map('trim', explode("\n", $auth_signup_benefits)));
                        if (!empty($benefits)) : 
                        ?>
                        <div class="fra-inchat-benefits">
                            <div class="fra-inchat-benefits-title">What's Included</div>
                            <ul>
                                <?php foreach ($benefits as $benefit) : ?>
                                    <li><?php echo esc_html($benefit); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="fra-inchat-form-wrap">
                            <?php 
                            if (!empty($membership_id)) {
                                echo do_shortcode('[mepr-membership-registration-form id="' . esc_attr($membership_id) . '"]');
                            } else {
                                echo '<p style="color: #dc2626; text-align: center; padding: 1rem; background: #fef2f2; border-radius: 8px;">⚠️ Configure Membership ID in Customizer → Auth Pages → In-Chat Authentication</p>';
                            }
                            ?>
                        </div>
                        
                        <p class="fra-inchat-security">🔒 Secure payment via Stripe</p>
                        
                        <div class="fra-inchat-auth-footer">
                            <p>Already have an account? <a href="#" id="fra-inchat-switch-to-login"><strong>Sign In</strong></a></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MEMBER DASHBOARD VIEW -->
            <div id="fra-inchat-dashboard" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back to Chat</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-dashboard">
                        <?php if (is_user_logged_in()) : 
                            $current_user = wp_get_current_user();
                            $display_name = $current_user->first_name ?: $current_user->display_name;
                        ?>
                        <div class="fra-inchat-dashboard-header">
                            <div class="fra-inchat-welcome-icon">👋</div>
                            <h2>Welcome, <?php echo esc_html($display_name); ?>!</h2>
                            <p class="fra-inchat-welcome-msg"><?php echo esc_html($welcome_message); ?></p>
                        </div>
                        
                        <div class="fra-inchat-dashboard-grid">
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="dashboard">
                                <span class="fra-tile-icon">🗃️</span>
                                <span class="fra-tile-label">Dashboard</span>
                            </button>
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="custom-guides">
                                <span class="fra-tile-icon">📖</span>
                                <span class="fra-tile-label">My Guides</span>
                            </button>
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="my-documents">
                                <span class="fra-tile-icon">🗂️</span>
                                <span class="fra-tile-label">Documents</span>
                            </button>
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="checklists">
                                <span class="fra-tile-icon">📋</span>
                                <span class="fra-tile-label">Checklists</span>
                            </button>
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="day-counter">
                                <span class="fra-tile-icon">📅</span>
                                <span class="fra-tile-label">183-Day Counter</span>
                            </button>
                            <button type="button" class="fra-inchat-dashboard-tile" data-section="profile">
                                <span class="fra-tile-icon">👤</span>
                                <span class="fra-tile-label">My Visa Profile</span>
                            </button>
                        </div>
                        
                        <div class="fra-inchat-dashboard-footer">
                            <button type="button" id="fra-inchat-account-btn" class="fra-inchat-text-btn">
                                ⚙️ Account Settings
                            </button>
                            <button type="button" id="fra-inchat-dashboard-logout" class="fra-inchat-text-btn">
                                🚪 Logout
                            </button>
                        </div>
                        <?php else : ?>
                        <p>Please log in to access the dashboard.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ACCOUNT SETTINGS VIEW (Profile/Home) -->
            <div id="fra-inchat-account" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back to Chat</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-auth-card fra-inchat-card-wide">
                        <h2><?php _e('My Membership Account', 'france-relocation-assistant'); ?></h2>
                        <p class="fra-inchat-subtitle"><?php _e('Update your billing profile and account settings', 'france-relocation-assistant'); ?></p>
                        
                        <div class="fra-inchat-form-wrap fra-inchat-account-form">
                            <?php echo do_shortcode('[mepr-account-form]'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SUBSCRIPTIONS VIEW -->
            <div id="fra-inchat-subscriptions" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back to Chat</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-auth-card fra-inchat-card-wide">
                        <h2><?php _e('My Subscriptions', 'france-relocation-assistant'); ?></h2>
                        <p class="fra-inchat-subtitle"><?php _e('View and manage your membership subscriptions', 'france-relocation-assistant'); ?></p>
                        
                        <div class="fra-inchat-form-wrap fra-inchat-subscriptions-wrap">
                            <?php echo do_shortcode('[fra_mepr_subscriptions]'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PAYMENTS VIEW -->
            <div id="fra-inchat-payments" class="fra-inchat-auth-view" style="display: none;">
                <div class="fra-inchat-auth-header">
                    <button type="button" class="fra-inchat-back-btn" data-back="chat">← Back to Chat</button>
                </div>
                <div class="fra-inchat-auth-content">
                    <div class="fra-inchat-auth-card fra-inchat-card-wide">
                        <h2><?php _e('Payment History', 'france-relocation-assistant'); ?></h2>
                        <p class="fra-inchat-subtitle"><?php _e('View your payment history and transactions', 'france-relocation-assistant'); ?></p>
                        
                        <div class="fra-inchat-form-wrap fra-inchat-payments-wrap">
                            <?php echo do_shortcode('[fra_mepr_payments]'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            <!-- /IN-CHAT AUTH CONTAINERS -->
            
            <!-- Chat Messages Container -->
            <div id="fra-chat-messages" class="fra-chat-messages">
                
                <!-- Welcome State (shown before first message) -->
                <div id="fra-welcome" class="fra-welcome">
                    <div class="fra-welcome-icon">🇫🇷</div>
                    <h2>Welcome to France Relocation Assistant</h2>
                    <p>Select a topic from the menu or ask a question below. AI-powered answers based on official French sources.</p>
                    
                    <!-- Quick Topic Buttons -->
                    <div class="fra-quick-topics">
                        <?php foreach ($quick_topics as $qt) : ?>
                            <button class="fra-quick-topic" data-category="<?php echo esc_attr($qt['cat']); ?>" data-topic="<?php echo esc_attr($qt['topic']); ?>">
                                <?php echo esc_html($qt['label']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- /Welcome State -->
                
            </div>
            <!-- /Chat Messages Container -->
            
            <!-- Chat Input Area -->
            <div class="fra-chat-input-area">
                <div class="fra-chat-input-wrapper">
                    <textarea 
                        id="fra-chat-input" 
                        class="fra-chat-input" 
                        placeholder="Ask AI about relocating to France..."
                        rows="1"
                    ></textarea>
                    <button id="fra-chat-send" class="fra-chat-send" aria-label="Send">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
                <div class="fra-input-footer">
                    <div class="fra-input-hint">
                        AI answers powered by Claude • Information from official French sources
                    </div>
                    <!-- Help Icon - Opens prompting tips -->
                    <button id="fra-help-btn" class="fra-help-btn" aria-label="Tips for asking questions">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </button>
                    <!-- Hidden version - select/highlight to see -->
                    <span style="font-size:9px;color:#f5f5f5;background:#f5f5f5;padding:2px 4px;margin-left:8px;user-select:all;">v<?php echo defined('FRA_VERSION') ? FRA_VERSION : '?'; ?></span>
                </div>
            </div>
            <!-- /Chat Input Area -->
            
        </main>
        <!-- /RIGHT PANEL -->
        
    </div>
</div>
<!-- /FRANCE RELOCATION ASSISTANT -->

<!-- ==========================================================================
     TESTIMONIALS & TRUST SIGNALS
     ========================================================================== -->
<?php echo fra_render_testimonials(3); ?>
<?php echo fra_render_authority_badges(); ?>

<!-- ==========================================================================
     PROMPTING TIPS MODAL
     Helps users understand how to ask better questions
     ========================================================================== -->
<div id="fra-help-modal" class="fra-modal-overlay fra-help-modal-overlay">
    <div class="fra-modal fra-help-modal">
        
        <!-- Modal Header -->
        <div class="fra-modal-header fra-help-modal-header">
            <h3>💡 Tips for Better Answers</h3>
            <button id="fra-close-help-modal" class="fra-close-btn">×</button>
        </div>
        
        <div class="fra-modal-content fra-help-modal-content">
            
            <p class="fra-help-intro">
                Getting the most helpful answers depends on how you ask your questions. Here are some tips to get better results:
            </p>
            
            <!-- Tip 1 -->
            <div class="fra-help-tip">
                <div class="fra-help-tip-header">
                    <span class="fra-help-tip-icon">🎯</span>
                    <strong>Be specific about your situation</strong>
                </div>
                <p>Include relevant details like your nationality, visa type, timeline, or location in France.</p>
                <div class="fra-help-example">
                    <span class="fra-example-label">Example:</span>
                    <em>"I'm a US citizen planning to buy property in Dordogne. What taxes will I pay as a non-resident?"</em>
                </div>
            </div>
            
            <!-- Tip 2 -->
            <div class="fra-help-tip">
                <div class="fra-help-tip-header">
                    <span class="fra-help-tip-icon">📋</span>
                    <strong>Ask one question at a time</strong>
                </div>
                <p>Break complex topics into focused questions for clearer, more detailed answers.</p>
                <div class="fra-help-example">
                    <span class="fra-example-label">Instead of:</span>
                    <em>"Tell me everything about moving to France"</em>
                    <br>
                    <span class="fra-example-label fra-example-better">Try:</span>
                    <em>"What are the steps to get a long-stay visitor visa?"</em>
                </div>
            </div>
            
            <!-- Tip 3 -->
            <div class="fra-help-tip">
                <div class="fra-help-tip-header">
                    <span class="fra-help-tip-icon">🔍</span>
                    <strong>Mention what you already know</strong>
                </div>
                <p>This helps avoid repeating basics and gets you more advanced information.</p>
                <div class="fra-help-example">
                    <span class="fra-example-label">Example:</span>
                    <em>"I know I need to validate my visa with OFII. What documents should I prepare for the appointment?"</em>
                </div>
            </div>
            
            <!-- Tip 4 -->
            <div class="fra-help-tip">
                <div class="fra-help-tip-header">
                    <span class="fra-help-tip-icon">📅</span>
                    <strong>Include your timeline</strong>
                </div>
                <p>Deadlines and dates help prioritize what's most urgent for your situation.</p>
                <div class="fra-help-example">
                    <span class="fra-example-label">Example:</span>
                    <em>"I'm closing on a property in February. What do I need to have ready before then?"</em>
                </div>
            </div>
            
            <!-- Quick Start Topics -->
            <div class="fra-help-quickstart">
                <h4>Popular Topics to Explore</h4>
                <div class="fra-help-topics">
                    <button class="fra-help-topic-btn" data-question="What visa do I need to live in France as a US citizen?">Visa options</button>
                    <button class="fra-help-topic-btn" data-question="What are the steps to buy property in France?">Buying property</button>
                    <button class="fra-help-topic-btn" data-question="How does the 183-day tax residency rule work?">Tax residency</button>
                    <button class="fra-help-topic-btn" data-question="How do I get healthcare coverage in France?">Healthcare</button>
                    <button class="fra-help-topic-btn" data-question="How do I exchange my US driver's license for a French one?">Driver's license</button>
                </div>
            </div>
            
        </div>
    </div>
</div>
<!-- /PROMPTING TIPS MODAL -->
<div id="fra-day-counter-modal" class="fra-modal-overlay">
    <div class="fra-modal fra-dc-modal">

        <!-- Modal Header -->
        <div class="fra-modal-header">
            <h3>📅 183-Day Tax Residency Tracker</h3>
            <button id="fra-close-modal" class="fra-close-btn">×</button>
        </div>

        <div class="fra-modal-content">

            <!-- Main Status Card -->
            <div class="fra-dc-status-card">
                <!-- Status Message (OK/Warning/Danger) -->
                <div id="fra-status-message" class="fra-status-message fra-status-ok">
                    <strong>✓ OK:</strong> Add trips to start tracking.
                </div>

                <!-- Progress Bar (Rolling 183-Day Window) -->
                <div class="fra-progress-container">
                    <div class="fra-progress-labels">
                        <span>Rolling 183-Day Window</span>
                        <span><span id="fra-rolling-used">0</span> / 183 days in France</span>
                    </div>
                    <div class="fra-progress-track">
                        <div id="fra-progress-bar" class="fra-progress-bar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout: Stats + Quick Add -->
            <div class="fra-dc-top-row">
                <!-- Day Statistics by Location -->
                <div class="fra-dc-stats">
                    <div class="fra-dc-stat fra-stat-france">
                        <span id="fra-stat-france" class="fra-stat-number">0</span>
                        <span class="fra-stat-label">🇫🇷 France</span>
                    </div>
                    <div class="fra-dc-stat fra-stat-us">
                        <span id="fra-stat-us" class="fra-stat-number">0</span>
                        <span class="fra-stat-label">🇺🇸 US</span>
                    </div>
                    <div class="fra-dc-stat fra-stat-other">
                        <span id="fra-stat-other" class="fra-stat-number">0</span>
                        <span class="fra-stat-label">🌍 Other</span>
                    </div>
                    <div class="fra-dc-stat">
                        <span id="fra-stat-untracked" class="fra-stat-number">0</span>
                        <span class="fra-stat-label">Untracked</span>
                    </div>
                </div>
            </div>

            <!-- Collapsible Calendar Section -->
            <details class="fra-dc-section" open>
                <summary class="fra-dc-section-header">
                    <span>📆 Calendar View</span>
                    <span class="fra-dc-toggle-icon"></span>
                </summary>
                <div class="fra-dc-section-content">
                    <!-- Calendar Year Navigation -->
                    <div class="fra-calendar-nav">
                        <button id="fra-prev-year" class="fra-nav-btn">◀</button>
                        <span id="fra-current-year" class="fra-year-label">2025</span>
                        <button id="fra-next-year" class="fra-nav-btn">▶</button>
                    </div>

                    <!-- Calendar Grid (populated by JavaScript) -->
                    <div id="fra-calendar-grid" class="fra-calendar-grid"></div>

                    <!-- Calendar Legend -->
                    <div class="fra-calendar-legend">
                        <span class="fra-legend-item"><span class="fra-legend-color fra-legend-france"></span> France</span>
                        <span class="fra-legend-item"><span class="fra-legend-color fra-legend-us"></span> US</span>
                        <span class="fra-legend-item"><span class="fra-legend-color fra-legend-other"></span> Other</span>
                        <span class="fra-legend-item"><span class="fra-legend-color fra-legend-today"></span> Today</span>
                    </div>
                </div>
            </details>

            <!-- Trips Management Section -->
            <details class="fra-dc-section" open>
                <summary class="fra-dc-section-header">
                    <span>✈️ My Trips</span>
                    <span class="fra-dc-toggle-icon"></span>
                </summary>
                <div class="fra-dc-section-content">
                    <!-- Add Trip Button -->
                    <div class="fra-add-trip-section">
                        <button id="fra-add-trip-btn" class="fra-btn-primary">+ Add Trip</button>
                    </div>

                    <!-- Add Trip Form (hidden by default) -->
                    <div id="fra-add-trip-form" class="fra-add-trip-form">
                        <div class="fra-form-row">
                            <div class="fra-form-group">
                                <label for="fra-trip-start">Start Date</label>
                                <input type="date" id="fra-trip-start" required>
                            </div>
                            <div class="fra-form-group">
                                <label for="fra-trip-end">End Date</label>
                                <input type="date" id="fra-trip-end" required>
                            </div>
                            <div class="fra-form-group">
                                <label for="fra-trip-location">Location</label>
                                <select id="fra-trip-location">
                                    <option value="france">🇫🇷 France</option>
                                    <option value="us">🇺🇸 United States</option>
                                    <option value="other">🌍 Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="fra-form-group fra-form-full">
                            <label for="fra-trip-notes">Notes (optional)</label>
                            <input type="text" id="fra-trip-notes" placeholder="e.g., Business trip to Paris">
                        </div>
                        <div class="fra-form-actions">
                            <button id="fra-save-trip" class="fra-btn-primary">Save Trip</button>
                            <button id="fra-cancel-trip" class="fra-btn-secondary">Cancel</button>
                        </div>
                    </div>

                    <!-- Trip List -->
                    <div class="fra-trip-list-section">
                        <div id="fra-trip-list" class="fra-trip-list"></div>
                    </div>
                </div>
            </details>

            <!-- Data Management Footer -->
            <div class="fra-dc-footer">
                <div class="fra-data-management">
                    <button id="fra-export-data" class="fra-btn-secondary fra-btn-sm">📤 Export</button>
                    <label class="fra-btn-secondary fra-btn-sm fra-import-label">
                        📥 Import
                        <input type="file" id="fra-import-data" accept=".json" style="display:none;">
                    </label>
                    <button id="fra-clear-all-data" class="fra-btn-danger fra-btn-sm">🗑️ Clear All</button>
                </div>

                <!-- Disclaimer -->
                <p class="fra-dc-disclaimer">
                    <strong>Note:</strong> This tool is for personal tracking only. French tax residency depends on multiple factors beyond physical presence. Consult a tax professional for advice specific to your situation.
                </p>
            </div>
            
        </div>
    </div>
</div>
<!-- /183-DAY TAX RESIDENCY TRACKER MODAL -->
