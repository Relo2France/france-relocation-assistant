<?php
/**
 * Relo2France Theme Functions
 *
 * Custom WordPress theme for the France Relocation Assistant website.
 * Provides styling, layouts, and customizer options for the relocation platform.
 *
 * @package     Relo2France
 * @author      Relo2France
 * @copyright   2024 Relo2France
 * @license     GPL-2.0-or-later
 * @version     1.2.4
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Theme version.
define( 'R2F_VERSION', '1.2.4' );

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * @since 1.0.0
 * @return void
 */
function relo2france_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 400,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'relo2france'),
        'footer'  => __('Footer Menu', 'relo2france'),
    ));
    
    // Set content width
    if (!isset($content_width)) {
        $content_width = 900;
    }
}
add_action('after_setup_theme', 'relo2france_setup');

/**
 * Enqueue Scripts and Styles
 */
function relo2france_scripts() {
    // Main stylesheet
    wp_enqueue_style(
        'relo2france-style',
        get_stylesheet_uri(),
        array(),
        R2F_VERSION
    );
    
    // Theme JavaScript
    wp_enqueue_script(
        'relo2france-script',
        get_template_directory_uri() . '/assets/js/theme.js',
        array(),
        R2F_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'relo2france_scripts');

/**
 * Register Widget Areas
 */
function relo2france_widgets_init() {
    register_sidebar(array(
        'name'          => __('Footer Widget 1', 'relo2france'),
        'id'            => 'footer-1',
        'description'   => __('Add widgets here to appear in the footer.', 'relo2france'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget 2', 'relo2france'),
        'id'            => 'footer-2',
        'description'   => __('Add widgets here to appear in the footer.', 'relo2france'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
    
    register_sidebar(array(
        'name'          => __('Footer Widget 3', 'relo2france'),
        'id'            => 'footer-3',
        'description'   => __('Add widgets here to appear in the footer.', 'relo2france'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'relo2france_widgets_init');

/**
 * Custom template tags
 */

/**
 * Display site logo or title
 */
function relo2france_site_logo() {
    if (has_custom_logo()) {
        the_custom_logo();
    } else {
        ?>
        <div class="site-branding">
            <span class="site-logo">🇫🇷</span>
            <div>
                <h1 class="site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                </h1>
                <?php 
                $description = get_bloginfo('description', 'display');
                if ($description || is_customize_preview()) : ?>
                    <p class="site-description"><?php echo $description; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Display navigation menu
 */
function relo2france_primary_menu() {
    if (has_nav_menu('primary')) {
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'menu_class'     => 'primary-menu',
            'container'      => false,
            'fallback_cb'    => false,
        ));
    }
}

/**
 * Display footer menu
 */
function relo2france_footer_menu() {
    if (has_nav_menu('footer')) {
        wp_nav_menu(array(
            'theme_location' => 'footer',
            'menu_class'     => 'footer-menu',
            'container'      => false,
            'fallback_cb'    => false,
            'depth'          => 1,
        ));
    }
}

/**
 * Add custom body classes
 */
function relo2france_body_classes($classes) {
    // Add class if France Relocation Assistant plugin is active
    if (class_exists('France_Relocation_Assistant')) {
        $classes[] = 'fra-plugin-active';
    }
    
    // Add class for pages without sidebar
    if (!is_active_sidebar('sidebar-1')) {
        $classes[] = 'no-sidebar';
    }
    
    // Add class for auth page template
    if (is_page_template('template-auth.php')) {
        $classes[] = 'r2f-auth-page-active';
        // Remove admin-bar class on auth pages
        $classes = array_diff($classes, array('admin-bar'));
    }
    
    return $classes;
}
add_filter('body_class', 'relo2france_body_classes');

/**
 * Hide admin bar on auth pages
 */
function relo2france_hide_admin_bar_on_auth() {
    if (is_page_template('template-auth.php')) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action('template_redirect', 'relo2france_hide_admin_bar_on_auth');

/**
 * Customize excerpt length
 */
function relo2france_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'relo2france_excerpt_length');

/**
 * Customize excerpt more text
 */
function relo2france_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'relo2france_excerpt_more');

/**
 * Add theme customizer options
 */
function relo2france_customize_register($wp_customize) {
    // Header Section (New)
    $wp_customize->add_section('relo2france_header', array(
        'title'    => __('Header Settings', 'relo2france'),
        'priority' => 25,
    ));
    
    // Header Tagline
    $wp_customize->add_setting('header_tagline', array(
        'default'           => __('Your Complete US to France Relocation Resource', 'relo2france'),
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('header_tagline', array(
        'label'       => __('Header Tagline', 'relo2france'),
        'description' => __('The orange tagline shown below the site title', 'relo2france'),
        'section'     => 'relo2france_header',
        'type'        => 'text',
    ));
    
    // Header Description
    $wp_customize->add_setting('header_description', array(
        'default'           => __('Moving from America to France? Get AI-powered answers about visa requirements, buying property, enrolling in healthcare, understanding taxes, and settling into French life — all based on official government sources.', 'relo2france'),
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('header_description', array(
        'label'       => __('Header Description', 'relo2france'),
        'description' => __('SEO-friendly description shown on the homepage', 'relo2france'),
        'section'     => 'relo2france_header',
        'type'        => 'textarea',
    ));
    
    // Hero Section (Legacy - kept for backwards compatibility)
    $wp_customize->add_section('relo2france_hero', array(
        'title'    => __('Hero Section (Legacy)', 'relo2france'),
        'priority' => 30,
    ));
    
    // Hero Title
    $wp_customize->add_setting('hero_title', array(
        'default'           => __('Welcome to Relo2France', 'relo2france'),
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('hero_title', array(
        'label'   => __('Hero Title', 'relo2france'),
        'section' => 'relo2france_hero',
        'type'    => 'text',
    ));
    
    // Hero Subtitle
    $wp_customize->add_setting('hero_subtitle', array(
        'default'           => __('Your comprehensive guide to relocating from the United States to France', 'relo2france'),
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('hero_subtitle', array(
        'label'   => __('Hero Subtitle', 'relo2france'),
        'section' => 'relo2france_hero',
        'type'    => 'textarea',
    ));
    
    // Show Hero on Front Page
    $wp_customize->add_setting('show_hero', array(
        'default'           => false,
        'sanitize_callback' => 'relo2france_sanitize_checkbox',
    ));
    
    $wp_customize->add_control('show_hero', array(
        'label'       => __('Show Legacy Hero Section', 'relo2france'),
        'description' => __('Enable the old-style hero section (disabled by default)', 'relo2france'),
        'section'     => 'relo2france_hero',
        'type'        => 'checkbox',
    ));
    
    // Footer Section
    $wp_customize->add_section('relo2france_footer', array(
        'title'    => __('Footer Settings', 'relo2france'),
        'priority' => 90,
    ));
    
    // Footer Text
    $wp_customize->add_setting('footer_text', array(
        'default'           => __('Information provided for general guidance only. Always consult qualified professionals.', 'relo2france'),
        'sanitize_callback' => 'wp_kses_post',
    ));
    
    $wp_customize->add_control('footer_text', array(
        'label'   => __('Footer Disclaimer Text', 'relo2france'),
        'section' => 'relo2france_footer',
        'type'    => 'textarea',
    ));
}
add_action('customize_register', 'relo2france_customize_register');

/**
 * Sanitize checkbox
 */
function relo2france_sanitize_checkbox($checked) {
    return ((isset($checked) && true == $checked) ? true : false);
}

/**
 * Check if France Relocation Assistant plugin is active
 */
function relo2france_fra_active() {
    return class_exists('France_Relocation_Assistant');
}

/**
 * Admin notice if plugin is not active
 */
function relo2france_admin_notice() {
    if (!relo2france_fra_active()) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('Relo2France Theme:', 'relo2france'); ?></strong>
                <?php _e('This theme is designed to work with the France Relocation Assistant plugin. Please install and activate it for the best experience.', 'relo2france'); ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'relo2france_admin_notice');
