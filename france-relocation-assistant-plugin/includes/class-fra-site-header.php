<?php
/**
 * Custom Site Header
 * 
 * Renders a customizable site header that can replace the theme's default header.
 * All settings are managed through the FR Assistant Customizer.
 * 
 * @package France_Relocation_Assistant
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Site_Header {
    
    /** @var FRA_Site_Header Singleton instance */
    private static $instance = null;
    
    /** @var array Default settings */
    private $defaults = array(
        'site_header_enabled'          => false,
        'site_header_hide_theme_header'=> false,
        'site_header_max_width'        => 1280,
        'site_header_padding'          => 24,
        'site_header_logo'             => '',
        'site_header_logo_size'        => 200,
        'site_header_logo_bg_color'    => '#ffffff',
        'site_header_site_name'        => 'Relo2France',
        'site_header_tagline'          => 'Your Relocation Guide from the US to France, in one easy to use site.',
        'site_header_description'      => 'Updated daily.',
        'site_header_bg_color'         => '#1e3a8a',
        'site_header_utility_bg_color' => '#172554',
        'site_header_text_color'       => '#ffffff',
        'site_header_nav_link_color'   => '#ffffff',
        'site_header_cta_color'        => '#f97316',
        'site_header_utility_text'     => 'Your complete guide to relocating to France',
        'site_header_login_text'       => 'Login',
        'site_header_login_url'        => '/login/',
        'site_header_contact_text'     => 'Contact',
        'site_header_contact_url'      => '/contact/',
        'site_header_cta_text'         => 'Get Lifetime Access',
        'site_header_cta_url'          => '/membership/',
        'site_header_nav_items'        => '',
        'site_header_nav_urls'         => '',
        'site_header_site_name_size'   => 32,
        'site_header_tagline_size'     => 16,
        'site_header_description_size' => 14,
        'site_header_cta_size'         => 15,
        // Maintenance banner
        'maintenance_banner_enabled'   => false,
        'maintenance_banner_message'   => 'We are currently performing scheduled maintenance. Some features may be temporarily unavailable.',
        'maintenance_banner_bg_color'  => '#f97316',
        'maintenance_banner_text_color'=> '#ffffff',
    );
    
    /**
     * Get singleton instance
     * 
     * @return FRA_Site_Header
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Register hooks if header is enabled
     */
    private function __construct() {
        if ($this->is_enabled()) {
            // Output CSS to hide theme header (in <head>)
            add_action('wp_head', array($this, 'output_theme_hide_css'), 5);
            
            // Output header HTML (after <body>)
            add_action('wp_body_open', array($this, 'render'), 1);
            
            // Shortcode for manual placement if wp_body_open not supported
            add_shortcode('fra_site_header', array($this, 'shortcode_render'));
        }
    }
    
    /**
     * Check if custom header is enabled
     * 
     * @return bool
     */
    public function is_enabled() {
        $saved = get_option('fra_customizer', array());
        return !empty($saved['site_header_enabled']);
    }
    
    /**
     * Get a setting value with fallback to default
     * 
     * @param string $key Setting key
     * @return mixed Setting value
     */
    private function get_setting($key) {
        $saved = get_option('fra_customizer', array());
        return isset($saved[$key]) && $saved[$key] !== '' ? $saved[$key] : ($this->defaults[$key] ?? '');
    }
    
    /**
     * Output CSS to hide theme's default header
     * 
     * This runs in wp_head to ensure theme header is hidden before it renders.
     */
    public function output_theme_hide_css() {
        if (!$this->get_setting('site_header_hide_theme_header')) {
            return;
        }
        ?>
        <!-- FR Assistant: Hide Theme Header -->
        <style id="fra-hide-theme-header">
        body > header:not(.fra-site-header-main),
        #masthead,
        .site-header:not(.fra-site-header-main),
        header.header:not(.fra-site-header-main),
        .theme-header,
        #header:not(.fra-site-header-main),
        .ast-above-header,
        .ast-below-header,
        .ast-main-header-wrap {
            display: none !important;
        }
        </style>
        <?php
    }
    
    /**
     * Render the complete header
     * 
     * Main rendering method that outputs the full header HTML with inline styles.
     */
    public function render() {
        // =================================================================
        // GATHER ALL SETTINGS
        // =================================================================
        
        // Branding
        $logo           = $this->get_setting('site_header_logo');
        $logo_size      = intval($this->get_setting('site_header_logo_size'));
        $site_name      = $this->get_setting('site_header_site_name');
        $tagline        = $this->get_setting('site_header_tagline');
        $description    = $this->get_setting('site_header_description');
        
        // Text sizes
        $site_name_size   = intval($this->get_setting('site_header_site_name_size')) ?: 32;
        $tagline_size     = intval($this->get_setting('site_header_tagline_size')) ?: 16;
        $description_size = intval($this->get_setting('site_header_description_size')) ?: 14;
        $cta_size         = intval($this->get_setting('site_header_cta_size')) ?: 15;
        
        // Colors
        $bg_color       = $this->get_setting('site_header_bg_color');
        $utility_bg     = $this->get_setting('site_header_utility_bg_color');
        $text_color     = $this->get_setting('site_header_text_color');
        $nav_link_color = $this->get_setting('site_header_nav_link_color');
        $cta_color      = $this->get_setting('site_header_cta_color');
        $logo_bg_color  = $this->get_setting('site_header_logo_bg_color');
        
        // Utility bar content
        $utility_text   = $this->get_setting('site_header_utility_text');
        $login_text     = $this->get_setting('site_header_login_text');
        $logout_text    = 'Logout'; // Hardcoded for now

        // Dynamic login/logout state
        $is_logged_in   = is_user_logged_in();
        $logout_url     = wp_logout_url(home_url('/?logged_out=1'));

        // CTA button
        $cta_text       = $this->get_setting('site_header_cta_text');
        $cta_url        = $this->get_setting('site_header_cta_url');
        
        // Maintenance banner
        $maintenance_enabled = $this->get_setting('maintenance_banner_enabled');
        $maintenance_message = $this->get_setting('maintenance_banner_message');
        $maintenance_bg      = $this->get_setting('maintenance_banner_bg_color') ?: '#f97316';
        $maintenance_text    = $this->get_setting('maintenance_banner_text_color') ?: '#ffffff';
        
        // Navigation - filter out empty items
        $nav_items_raw  = $this->get_setting('site_header_nav_items');
        $nav_urls_raw   = $this->get_setting('site_header_nav_urls');
        $nav_items      = array_filter(array_map('trim', explode(',', $nav_items_raw)));
        $nav_urls       = array_map('trim', explode(',', $nav_urls_raw));
        
        // =================================================================
        // COMPUTED STYLES
        // =================================================================
        
        // Logo box styling - remove box effect if logo bg matches header bg
        $logo_has_box   = (strtolower($logo_bg_color) !== strtolower($bg_color));
        $logo_shadow    = $logo_has_box ? '0 4px 20px rgba(0,0,0,0.2)' : 'none';
        $logo_radius    = $logo_has_box ? '12px' : '0';
        $logo_padding   = $logo_has_box ? '8px' : '0';
        
        // Container width - customizable to match your theme
        $max_width = intval($this->get_setting('site_header_max_width'));
        if ($max_width < 800) $max_width = 1280; // Fallback
        $container_style = 'max-width: ' . $max_width . 'px; margin: 0 auto; padding: 0 20px;';
        
        // Header padding (vertical)
        $header_padding = intval($this->get_setting('site_header_padding'));
        if ($header_padding < 0) $header_padding = 24; // Fallback
        
        // =================================================================
        // OUTPUT HTML
        // =================================================================
        ?>
        
        <!-- ============================================================
             FR ASSISTANT: CUSTOM SITE HEADER
             ============================================================ -->
        <div class="fra-site-header-wrapper" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; position: relative; z-index: 9999;">
            
            <?php if ($maintenance_enabled && !empty($maintenance_message)) : ?>
            <!-- ========================================================
                 MAINTENANCE BANNER
                 Orange banner displayed above utility bar when enabled
                 ======================================================== -->
            <div class="fra-maintenance-banner" style="background: <?php echo esc_attr($maintenance_bg); ?>; color: <?php echo esc_attr($maintenance_text); ?>; padding: 10px 20px; text-align: center; font-size: 14px; font-weight: 500;">
                <div style="<?php echo $container_style; ?>">
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">🚧</span>
                        <?php echo esc_html($maintenance_message); ?>
                    </span>
                </div>
            </div>
            <!-- /MAINTENANCE BANNER -->
            <?php endif; ?>
            
            <!-- ========================================================
                 UTILITY BAR
                 Small bar above main header with tagline and quick links
                 ======================================================== -->
            <div class="fra-site-header-utility" style="background: <?php echo esc_attr($utility_bg); ?>; color: rgba(255,255,255,0.7); font-size: 12px; padding: 6px 0;">
                <div class="fra-utility-container" style="display: flex; justify-content: space-between; align-items: center; <?php echo $container_style; ?>">
                    
                    <!-- Utility Text (Left) -->
                    <span class="fra-utility-text"><?php echo esc_html($utility_text); ?></span>
                    
                    <!-- Utility Links (Right) - Dynamic Login/Logout -->
                    <div class="fra-utility-links" style="display: flex; gap: 20px;">
                        <?php if ($is_logged_in) : ?>
                            <a href="<?php echo esc_url($logout_url); ?>" style="color: rgba(255,255,255,0.7); text-decoration: none;"><?php echo esc_html($logout_text); ?></a>
                        <?php else : ?>
                            <?php if (!empty($login_text)) : ?>
                                <a href="#" id="fra-utility-login-btn" style="color: rgba(255,255,255,0.7); text-decoration: none; cursor: pointer;"><?php echo esc_html($login_text); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
            <!-- /UTILITY BAR -->
            
            <!-- ========================================================
                 MAIN HEADER
                 Contains logo, site name, navigation, and CTA button
                 ======================================================== -->
            <header class="fra-site-header-main" style="background: <?php echo esc_attr($bg_color); ?>; color: <?php echo esc_attr($text_color); ?>; padding: <?php echo $header_padding; ?>px 0;">
                <div class="fra-header-container" style="display: flex; justify-content: space-between; align-items: center; <?php echo $container_style; ?>">
                    
                    <!-- ================================================
                         BRAND SECTION
                         Logo image + Site name + Tagline + Description
                         ================================================ -->
                    <div class="fra-header-brand" style="display: flex; align-items: center; gap: 24px;">
                        
                        <!-- Logo Image -->
                        <?php if (!empty($logo)) : ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="fra-header-logo" style="width: <?php echo $logo_size; ?>px; height: <?php echo $logo_size; ?>px; background: <?php echo esc_attr($logo_bg_color); ?>; border-radius: <?php echo $logo_radius; ?>; display: flex; align-items: center; justify-content: center; padding: <?php echo $logo_padding; ?>; box-shadow: <?php echo $logo_shadow; ?>; flex-shrink: 0; text-decoration: none;">
                            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </a>
                        <?php endif; ?>
                        
                        <!-- Site Name, Tagline & Description -->
                        <div class="fra-header-text">
                            <h1 style="margin: 0; font-size: <?php echo $site_name_size; ?>px; font-weight: 700; color: <?php echo esc_attr($text_color); ?>; line-height: 1.2;">
                                <a href="<?php echo esc_url(home_url('/')); ?>" style="color: <?php echo esc_attr($text_color); ?>; text-decoration: none;"><?php echo esc_html($site_name); ?></a>
                            </h1>
                            <?php if (!empty($tagline)) : ?>
                            <p style="margin: 6px 0 0; font-size: <?php echo $tagline_size; ?>px; line-height: 1.4; color: <?php echo esc_attr($text_color); ?>; opacity: 0.9;"><?php echo esc_html($tagline); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($description)) : ?>
                            <p style="margin: 2px 0 0; font-size: <?php echo $description_size; ?>px; color: <?php echo esc_attr($text_color); ?>; opacity: 0.7;"><?php echo esc_html($description); ?></p>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    <!-- /BRAND SECTION -->
                    
                    <!-- ================================================
                         NAVIGATION & CTA SECTION
                         Main nav links + Call-to-action button
                         ================================================ -->
                    <div class="fra-header-right" style="display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 16px; flex-shrink: 0;">
                        
                        <!-- Navigation Menu -->
                        <?php if (!empty($nav_items)) : ?>
                        <nav class="fra-header-nav" style="display: flex; gap: 4px;">
                            <?php foreach ($nav_items as $i => $item) : 
                                $url = isset($nav_urls[$i]) ? $nav_urls[$i] : '#';
                            ?>
                            <a href="<?php echo esc_url($url); ?>" style="color: <?php echo esc_attr($nav_link_color); ?>; text-decoration: none; padding: 8px 16px; font-size: 14px; border-radius: 6px; background: transparent;"><?php echo esc_html($item); ?></a>
                            <?php endforeach; ?>
                        </nav>
                        <?php endif; ?>
                        
                        <!-- CTA Button -->
                        <?php if (!empty($cta_text) && !empty($cta_url)) : ?>
                        <a href="<?php echo esc_url($cta_url); ?>" class="fra-header-cta" style="background: <?php echo esc_attr($cta_color); ?>; color: #fff; padding: 12px 24px; border-radius: 8px; font-size: <?php echo $cta_size; ?>px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3); white-space: nowrap;">
                            <?php echo esc_html($cta_text); ?>
                        </a>
                        <?php endif; ?>
                        
                    </div>
                    <!-- /NAVIGATION & CTA SECTION -->
                    
                </div>
            </header>
            <!-- /MAIN HEADER -->
            
        </div>
        <!-- /FR ASSISTANT: CUSTOM SITE HEADER -->
        
        <!-- ============================================================
             RESPONSIVE STYLES
             Handles tablet and mobile layouts
             ============================================================ -->
        <style id="fra-site-header-responsive">
        /* Tablet */
        @media (max-width: 1024px) {
            .fra-header-container {
                flex-direction: column !important;
                gap: 20px !important;
                text-align: center !important;
            }
            .fra-header-brand {
                flex-direction: column !important;
            }
            .fra-header-right {
                align-items: center !important;
            }
            .fra-header-nav {
                flex-wrap: wrap !important;
                justify-content: center !important;
            }
        }
        
        /* Mobile */
        @media (max-width: 768px) {
            .fra-site-header-utility {
                padding: 8px 0 !important;
            }
            .fra-utility-container {
                flex-direction: column !important;
                gap: 6px !important;
                text-align: center !important;
                padding: 0 16px !important;
            }
            .fra-utility-links {
                gap: 16px !important;
            }
            .fra-site-header-main {
                padding: 16px 0 !important;
            }
            .fra-header-container {
                padding: 0 16px !important;
                gap: 16px !important;
            }
            .fra-header-brand {
                gap: 12px !important;
            }
            .fra-header-logo {
                width: <?php echo min($logo_size, 120); ?>px !important;
                height: <?php echo min($logo_size, 120); ?>px !important;
            }
            .fra-header-text h1 {
                font-size: 22px !important;
            }
            .fra-header-text p {
                font-size: 13px !important;
            }
            .fra-header-nav {
                gap: 2px !important;
            }
            .fra-header-nav a {
                padding: 8px 12px !important;
                font-size: 13px !important;
            }
            .fra-header-cta {
                padding: 10px 18px !important;
                font-size: 14px !important;
            }
            .fra-header-right {
                gap: 12px !important;
            }
        }
        
        /* Small Mobile */
        @media (max-width: 480px) {
            .fra-header-logo {
                width: <?php echo min($logo_size, 80); ?>px !important;
                height: <?php echo min($logo_size, 80); ?>px !important;
            }
            .fra-header-text h1 {
                font-size: 18px !important;
            }
            .fra-header-text p {
                font-size: 12px !important;
            }
            .fra-header-nav a {
                padding: 6px 10px !important;
                font-size: 12px !important;
            }
        }
        </style>
        
        <?php
    }
    
    /**
     * Shortcode wrapper for render()
     * 
     * Use [fra_site_header] if your theme doesn't support wp_body_open hook.
     * 
     * @param array $atts Shortcode attributes (unused)
     * @return string Header HTML
     */
    public function shortcode_render($atts = array()) {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
}

// =============================================================================
// INITIALIZE
// =============================================================================
FRA_Site_Header::get_instance();
