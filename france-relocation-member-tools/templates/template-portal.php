<?php
/**
 * Template Name: Members Portal
 * Template Post Type: page
 *
 * Custom page template for the Members Portal React application.
 *
 * @package     FRA_Member_Tools
 * @since       2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if user is logged in
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

// Get current user
$current_user = wp_get_current_user();

// Check for membership (optional - can be configured)
$require_membership = get_option( 'framt_portal_require_membership', false );
if ( $require_membership && class_exists( 'MeprUser' ) ) {
    $mepr_user = new MeprUser( $current_user->ID );
    if ( empty( $mepr_user->active_product_subscriptions() ) ) {
        // Redirect to membership page
        $membership_url = get_option( 'fra_membership_url', '/membership/' );
        wp_redirect( home_url( $membership_url ) );
        exit;
    }
}

// Get portal settings
$portal_settings = class_exists( 'FRAMT_Portal_Settings' )
    ? FRAMT_Portal_Settings::get_settings()
    : array();

// Set defaults if settings class not available
$defaults = array(
    'primary_color'       => '#22c55e',
    'secondary_color'     => '#3b82f6',
    'sidebar_bg_color'    => '#1f2937',
    'sidebar_text_color'  => '#ffffff',
    'header_bg_color'     => '#ffffff',
    'accent_color'        => '#f59e0b',
    'show_wp_header'      => false,
    'show_wp_footer'      => false,
    'show_promo_banner'   => false,
    'sidebar_position'    => 'left',
    'sidebar_collapsed'   => false,
    'portal_title'        => 'Members Portal',
    'logo_url'            => '',
    'menu_dashboard'      => true,
    'menu_tasks'          => true,
    'menu_timeline'       => true,
    'menu_messages'       => true,
    'menu_documents'      => true,
    'menu_guides'         => true,
    'menu_files'          => true,
    'menu_family'         => true,
    'menu_settings'       => true,
    'menu_help'           => true,
    'label_dashboard'     => 'Dashboard',
    'label_tasks'         => 'Tasks',
    'label_timeline'      => 'Timeline',
    'label_messages'      => 'Messages',
    'label_documents'     => 'Documents',
    'label_guides'        => 'Guides',
    'label_files'         => 'Files',
    'label_family'        => 'Family Members',
    'label_settings'      => 'Settings',
    'label_help'          => 'Help & Support',
    'enable_notifications' => true,
    'enable_file_upload'   => true,
    'custom_css'          => '',
);

$settings = wp_parse_args( $portal_settings, $defaults );

// Build menu config for React
$menu_config = array();
$menu_items = array(
    'dashboard' => array( 'icon' => 'LayoutDashboard', 'path' => '/dashboard' ),
    'tasks'     => array( 'icon' => 'CheckSquare', 'path' => '/tasks' ),
    'timeline'  => array( 'icon' => 'Calendar', 'path' => '/timeline' ),
    'messages'  => array( 'icon' => 'MessageSquare', 'path' => '/messages' ),
    'documents' => array( 'icon' => 'FileText', 'path' => '/documents' ),
    'guides'    => array( 'icon' => 'BookOpen', 'path' => '/guides' ),
    'files'     => array( 'icon' => 'FolderOpen', 'path' => '/files' ),
    'family'    => array( 'icon' => 'Users', 'path' => '/family' ),
    'settings'  => array( 'icon' => 'Settings', 'path' => '/settings' ),
    'help'      => array( 'icon' => 'HelpCircle', 'path' => '/help' ),
);

foreach ( $menu_items as $key => $item ) {
    if ( ! empty( $settings[ 'menu_' . $key ] ) ) {
        $menu_config[] = array(
            'id'    => $key,
            'label' => $settings[ 'label_' . $key ],
            'icon'  => $item['icon'],
            'path'  => $item['path'],
        );
    }
}

// Build settings object for React
$react_settings = array(
    'colors' => array(
        'primary'       => $settings['primary_color'],
        'secondary'     => $settings['secondary_color'],
        'accent'        => $settings['accent_color'],
        'sidebarBg'     => $settings['sidebar_bg_color'],
        'sidebarText'   => $settings['sidebar_text_color'],
        'headerBg'      => $settings['header_bg_color'],
    ),
    'layout' => array(
        'showWpHeader'     => (bool) $settings['show_wp_header'],
        'showWpFooter'     => (bool) $settings['show_wp_footer'],
        'showPromoBanner'  => (bool) $settings['show_promo_banner'],
        'sidebarPosition'  => $settings['sidebar_position'],
        'sidebarCollapsed' => (bool) $settings['sidebar_collapsed'],
    ),
    'branding' => array(
        'title'   => $settings['portal_title'],
        'logoUrl' => $settings['logo_url'],
    ),
    'features' => array(
        'notifications' => (bool) $settings['enable_notifications'],
        'fileUpload'    => (bool) $settings['enable_file_upload'],
    ),
    'menu'      => $menu_config,
    'customCss' => $settings['custom_css'],
);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $settings['portal_title'] ); ?> - <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php if ( $settings['show_wp_header'] ) : ?>
        <?php wp_head(); ?>
    <?php endif; ?>

    <style>
        /* Critical CSS for loading state */
        :root {
            --portal-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            --portal-secondary: <?php echo esc_attr( $settings['secondary_color'] ); ?>;
            --portal-accent: <?php echo esc_attr( $settings['accent_color'] ); ?>;
            --portal-sidebar-bg: <?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>;
            --portal-sidebar-text: <?php echo esc_attr( $settings['sidebar_text_color'] ); ?>;
            --portal-header-bg: <?php echo esc_attr( $settings['header_bg_color'] ); ?>;
        }

        #fra-portal-root {
            min-height: 100vh;
            background: #f9fafb;
        }

        .portal-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f9fafb;
        }

        .portal-loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .portal-loading-text {
            margin-top: 16px;
            color: #6b7280;
            font-family: 'Inter', system-ui, sans-serif;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        <?php if ( ! empty( $settings['custom_css'] ) ) : ?>
        /* Custom CSS */
        <?php echo $settings['custom_css']; ?>
        <?php endif; ?>
    </style>
</head>
<body <?php body_class( 'fra-portal-page' ); ?>>
    <?php if ( $settings['show_wp_header'] ) : ?>
        <?php wp_body_open(); ?>
        <?php get_header(); ?>
    <?php endif; ?>

    <!-- Portal settings for React -->
    <script>
        window.PORTAL_SETTINGS = <?php echo wp_json_encode( $react_settings ); ?>;
        window.PORTAL_USER = <?php echo wp_json_encode( array(
            'id'          => $current_user->ID,
            'email'       => $current_user->user_email,
            'displayName' => $current_user->display_name,
            'firstName'   => $current_user->first_name,
            'lastName'    => $current_user->last_name,
            'avatar'      => get_avatar_url( $current_user->ID ),
        ) ); ?>;
        window.PORTAL_API = {
            root: '<?php echo esc_url( rest_url() ); ?>',
            nonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
        };
    </script>

    <!-- Portal root element -->
    <div id="fra-portal-root">
        <!-- Loading state shown before React mounts -->
        <div class="portal-loading">
            <div class="portal-loading-spinner"></div>
            <p class="portal-loading-text">Loading <?php echo esc_html( $settings['portal_title'] ); ?>...</p>
        </div>
    </div>

    <?php if ( $settings['show_wp_footer'] ) : ?>
        <?php get_footer(); ?>
    <?php else : ?>
        <?php wp_footer(); ?>
    <?php endif; ?>
</body>
</html>
