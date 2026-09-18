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
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;
$login_error = '';

// A member with an account of their own joins a household only by using
// the link from the invitation, signed in as themselves.
if ( $is_logged_in && ! empty( $_GET['accept_household'] ) ) {
    $pending = get_user_meta( $current_user->ID, 'framt_household_invite', true );
    $token   = sanitize_text_field( wp_unslash( $_GET['accept_household'] ) );
    if ( is_array( $pending ) && ! empty( $pending['token'] ) && hash_equals( (string) $pending['token'], $token ) ) {
        update_user_meta( $current_user->ID, 'framt_household_owner', (int) $pending['owner_id'] );
        delete_user_meta( $current_user->ID, 'framt_household_invite' );
    }
    wp_safe_redirect( home_url( '/portal/' ) );
    exit;
}

// Check for membership (optional - can be configured) - only if logged in
$require_membership = get_option( 'framt_portal_require_membership', false );
if ( $is_logged_in && $require_membership && class_exists( 'MeprUser' ) ) {
    // A partner invited from the Family plan is covered by the owner's membership.
    $household_owner = (int) get_user_meta( $current_user->ID, 'framt_household_owner', true );
    $mepr_user       = new MeprUser( $household_owner > 0 ? $household_owner : $current_user->ID );
    // The Family add-on is an extra on top of membership, not a membership:
    // holding only the add-on does not open the portal.
    $addon_id = (int) get_option( 'framt_family_addon_product_id', 0 );
    $active   = array_map( 'intval', (array) $mepr_user->active_product_subscriptions( 'ids' ) );
    $active   = array_diff( $active, array( $addon_id ) );
    if ( empty( $active ) ) {
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
    // Menu visibility
    'menu_dashboard'      => true,
    'menu_tasks'          => true,
    'menu_checklists'     => true,
    'menu_timeline'       => true,
    'menu_messages'       => true,
    'menu_chat'           => true,
    'menu_documents'      => true,
    'menu_guides'         => true,
    'menu_glossary'       => true,
    'menu_research'       => true,
    'menu_files'          => true,
    'menu_profile'        => true,
    'menu_family'         => true,
    'menu_schengen'       => true,
    'menu_membership'     => true,
    'menu_settings'       => true,
    'menu_help'           => true,
    // Menu labels
    'label_dashboard'     => 'Dashboard',
    'label_tasks'         => 'Tasks',
    'label_checklists'    => 'Checklists',
    'label_timeline'      => 'Timeline',
    'label_messages'      => 'Messages',
    'label_chat'          => 'Ask AI',
    'label_documents'     => 'Documents',
    'label_guides'        => 'Guides',
    'label_glossary'      => 'Glossary',
    'label_research'      => 'Explore France',
    'label_files'         => 'Files',
    'label_profile'       => 'My Profile',
    'label_family'        => 'Family Members',
    'label_schengen'      => 'Schengen Tracker',
    'label_membership'    => 'Membership',
    'label_settings'      => 'Settings',
    'label_help'          => 'Help & Support',
    // Menu icons
    'icon_dashboard'      => 'LayoutDashboard',
    'icon_tasks'          => 'CheckSquare',
    'icon_checklists'     => 'ClipboardList',
    'icon_timeline'       => 'Calendar',
    'icon_messages'       => 'MessageSquare',
    'icon_chat'           => 'Bot',
    'icon_documents'      => 'FileText',
    'icon_guides'         => 'BookOpen',
    'icon_glossary'       => 'BookMarked',
    'icon_research'       => 'MapPin',
    'icon_files'          => 'FolderOpen',
    'icon_profile'        => 'User',
    'icon_family'         => 'Users',
    'icon_schengen'       => 'Globe',
    'icon_membership'     => 'CreditCard',
    'icon_settings'       => 'Settings',
    'icon_help'           => 'HelpCircle',
    // Features
    'enable_notifications' => true,
    'enable_file_upload'   => true,
    'custom_css'          => '',
);

$settings = wp_parse_args( $portal_settings, $defaults );

// Helper function to lighten a hex color
if ( ! function_exists( 'framt_lighten_color' ) ) {
    function framt_lighten_color( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = min( 255, $r + ( 255 - $r ) * $percent / 100 );
        $g = min( 255, $g + ( 255 - $g ) * $percent / 100 );
        $b = min( 255, $b + ( 255 - $b ) * $percent / 100 );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }
}

// Helper function to darken a hex color
if ( ! function_exists( 'framt_darken_color' ) ) {
    function framt_darken_color( $hex, $percent ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, $r * ( 100 - $percent ) / 100 );
        $g = max( 0, $g * ( 100 - $percent ) / 100 );
        $b = max( 0, $b * ( 100 - $percent ) / 100 );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }
}

// Calculate derived colors
$sidebar_hover  = framt_lighten_color( $settings['sidebar_bg_color'], 10 );
$sidebar_active = framt_lighten_color( $settings['sidebar_bg_color'], 20 );
$primary_dark   = framt_darken_color( $settings['primary_color'], 15 );

// Build menu config for React - all available menu items
$menu_config = array();
$menu_items = array(
    'dashboard'  => '/dashboard',
    'tasks'      => '/tasks',
    'checklists' => '/checklists',
    'timeline'   => '/timeline',
    'messages'   => '/messages',
    'chat'       => '/chat',
    'documents'  => '/documents',
    'guides'     => '/guides',
    'glossary'   => '/glossary',
    'research'   => '/research',
    'schengen'   => '/schengen',
    'files'      => '/files',
    'profile'    => '/profile',
    'family'     => '/family',
    'membership' => '/membership',
    'settings'   => '/settings',
    'help'       => '/help',
);

foreach ( $menu_items as $key => $path ) {
    if ( ! empty( $settings[ 'menu_' . $key ] ) ) {
        $menu_config[] = array(
            'id'    => $key,
            'label' => $settings[ 'label_' . $key ],
            'icon'  => $settings[ 'icon_' . $key ],
            'path'  => $path,
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
    <?php
    // One <title>: when the theme supports title-tag, wp_head() prints it,
    // so set its text instead of printing a second one here.
    $r2f_portal_title = $settings['portal_title'] . ' - ' . get_bloginfo( 'name' );
    if ( current_theme_supports( 'title-tag' ) ) {
        add_filter( 'pre_get_document_title', function () use ( $r2f_portal_title ) {
            return $r2f_portal_title;
        }, 100 );
    } else {
        echo '<title>' . esc_html( $r2f_portal_title ) . '</title>' . "\n";
    }
    // The portal is an app behind a sign-in: keep it out of search indexes.
    // Printed by wp_head() through wp_robots, whatever the theme does.
    add_filter( 'wp_robots', function ( $robots ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        return $robots;
    }, 100 );
    ?>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php // Same faces as the public site, so the two read as one product. ?>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Karla:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- PWA Support -->
    <link rel="manifest" href="<?php echo esc_url( FRAMT_PLUGIN_URL . 'assets/portal/manifest.json' ); ?>">
    <meta name="theme-color" content="<?php echo esc_attr( $settings['primary_color'] ); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Relo2France">
    <link rel="apple-touch-icon" href="<?php echo esc_url( FRAMT_PLUGIN_URL . 'assets/images/pwa-icon-192.png' ); ?>">

    <?php
    // The portal is an app, not a WordPress page: no admin bar for anyone,
    // admins included. wp-admin is one URL away.
    add_filter( 'show_admin_bar', '__return_false' );

    // The theme's stylesheet is for WordPress pages, not this app. Since the
    // theme moved onto the site's tokens its generic classes (.card, .btn)
    // follow dark mode, and they collide with the portal's own classes of
    // the same name - a dark card with dark text. Drop them before wp_head
    // prints styles; the portal ships everything it needs.
    add_action( 'wp_enqueue_scripts', function () {
        foreach ( array( 'relo2france-style', 'relo2france-tokens', 'relo2france-fonts' ) as $handle ) {
            wp_dequeue_style( $handle );
        }
    }, 100 );

    // Always call wp_head to load required scripts and styles
    wp_head();
    ?>

    <style>
        /* Critical CSS for loading state */
        :root {
            --portal-primary: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            --portal-primary-dark: <?php echo esc_attr( $primary_dark ); ?>;
            --portal-secondary: <?php echo esc_attr( $settings['secondary_color'] ); ?>;
            --portal-accent: <?php echo esc_attr( $settings['accent_color'] ); ?>;
            --portal-sidebar-bg: <?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>;
            --portal-sidebar-hover: <?php echo esc_attr( $sidebar_hover ); ?>;
            --portal-sidebar-active: <?php echo esc_attr( $sidebar_active ); ?>;
            --portal-sidebar-text: <?php echo esc_attr( $settings['sidebar_text_color'] ); ?>;
            --portal-sidebar-text-active: <?php echo esc_attr( $settings['sidebar_text_color'] ); ?>;
            --portal-header-bg: <?php echo esc_attr( $settings['header_bg_color'] ); ?>;
            --portal-ground: #fcfcfb;
        }

        #fra-portal-root {
            min-height: 100vh;
            background: var(--portal-ground, #fcfcfb);
        }

        .portal-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--portal-ground, #fcfcfb);
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
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Login form styles */
        .portal-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: <?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>;
            padding: 20px;
        }

        .portal-login-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #dde3de;
            padding: 48px;
            width: 100%;
            max-width: 420px;
        }

        .portal-login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 16px;
            color: #6b7280;
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 13px;
        }
        .portal-login-divider::before,
        .portal-login-divider::after {
            content: '';
            flex: 1;
            border-top: 1px solid #e5e7eb;
        }

        .portal-login-wordmark {
            display: block;
            text-align: center;
            font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #1c2420;
            text-decoration: none;
            margin: 0 auto 20px;
        }
        .portal-login-wordmark span {
            color: #2c5346;
        }

        .portal-login-title {
            font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.018em;
            color: #111827;
            text-align: center;
            margin: 0 0 8px;
        }

        .portal-login-subtitle {
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            color: #6b7280;
            text-align: center;
            margin: 0 0 32px;
        }

        .portal-login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
        }

        .portal-login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .portal-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .portal-form-group label {
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .portal-form-group input {
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .portal-form-group input:focus {
            border-color: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            box-shadow: 0 0 0 3px <?php echo esc_attr( $settings['primary_color'] ); ?>20;
        }

        .portal-form-group input::placeholder {
            color: #9ca3af;
        }

        .portal-form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
        }

        .portal-remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            cursor: pointer;
        }

        .portal-remember-me input {
            width: 16px;
            height: 16px;
            accent-color: <?php echo esc_attr( $settings['primary_color'] ); ?>;
        }

        .portal-forgot-password {
            color: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            text-decoration: none;
            font-weight: 500;
        }

        .portal-forgot-password:hover {
            text-decoration: underline;
        }

        .portal-login-button {
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: white;
            background: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            border: none;
            border-radius: 8px;
            padding: 14px 24px;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .portal-login-button:hover:not(:disabled) {
            background: <?php echo esc_attr( $primary_dark ); ?>;
        }

        .portal-login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .portal-login-button .animate-spin {
            animation: spin 1s linear infinite;
        }

        .portal-register-link {
            font-family: 'Karla', 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
            margin: 24px 0 0;
        }

        .portal-register-link a {
            color: <?php echo esc_attr( $settings['primary_color'] ); ?>;
            text-decoration: none;
            font-weight: 500;
        }

        .portal-register-link a:hover {
            text-decoration: underline;
        }

        <?php if ( ! empty( $settings['custom_css'] ) ) : ?>
        /* Custom CSS */
        <?php echo wp_strip_all_tags( $settings['custom_css'] ); ?>
        <?php endif; ?>
    </style>
</head>
<body <?php body_class( 'fra-portal-page' ); ?>>
    <?php if ( $settings['show_wp_header'] ) : ?>
        <?php wp_body_open(); ?>
        <?php get_header(); ?>
    <?php endif; ?>

    <?php if ( $is_logged_in ) : ?>
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
            window.fraPortalData = {
                apiUrl: '<?php echo esc_url( rest_url( 'fra-portal/v1' ) ); ?>',
                nonce: '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
                userId: <?php echo (int) get_current_user_id(); ?>,
                siteUrl: '<?php echo esc_url( home_url() ); ?>',
                pluginUrl: '<?php echo esc_url( FRAMT_PLUGIN_URL ); ?>',
                isAdmin: <?php echo current_user_can( 'manage_options' ) ? 'true' : 'false'; ?>,
                // Nonced by WordPress; signs out and returns to the home page.
                // wp_logout_url() returns "&amp;" for HTML; a script needs "&".
                logoutUrl: <?php echo wp_json_encode( str_replace( '&amp;', '&', wp_logout_url( home_url( '/portal/?signed_out=1' ) ) ) ); ?>
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

        <?php
        // Load portal script directly to bypass WordPress.com script combining
        $manifest_path = FRAMT_PLUGIN_DIR . 'assets/portal/.vite/manifest.json';
        if ( file_exists( $manifest_path ) ) {
            $manifest = json_decode( file_get_contents( $manifest_path ), true );
            if ( isset( $manifest['index.html']['file'] ) ) {
                $script_url = FRAMT_PLUGIN_URL . 'assets/portal/' . $manifest['index.html']['file'];
                ?>
                <script type="module" src="<?php echo esc_url( $script_url ); ?>"></script>
                <?php
            }
        }
        ?>
    <?php else : ?>
        <!-- Login Form -->
        <div class="portal-login-container">
            <div class="portal-login-card">
                <?php // The wordmark, as in the site header and the portal rail; the old uploaded logo image is retired. ?>
                <a class="portal-login-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>">Relo<span>2</span>France</a>

                <?php
                // One card for signing in and signing out, worded for each.
                $r2f_signed_out = isset( $_GET['signed_out'] ); // phpcs:ignore WordPress.Security.NonceVerification
                ?>
                <h1 class="portal-login-title"><?php echo $r2f_signed_out ? 'You’re signed out' : 'Welcome back'; ?></h1>
                <p class="portal-login-subtitle"><?php echo $r2f_signed_out ? 'Sign back in any time. Your file is where you left it.' : 'Sign in to your file. It is where you left it.'; ?></p>

                <div id="portal-login-error" class="portal-login-error" style="display: none;"></div>
                <?php if ( isset( $_GET['link'] ) && 'expired' === $_GET['link'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
                    <div class="portal-login-error">That sign-in link has expired or was already used. Ask for a new one below, or sign in with your password.</div>
                <?php endif; ?>

                <form id="portal-login-form" class="portal-login-form" method="post">
                    <div class="portal-form-group">
                        <label for="portal-username">Email or Username</label>
                        <input type="text" id="portal-username" name="username" required autocomplete="username" placeholder="Enter your email or username">
                    </div>

                    <div class="portal-form-group">
                        <label for="portal-password">Password</label>
                        <input type="password" id="portal-password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                    </div>

                    <div class="portal-form-options">
                        <label class="portal-remember-me">
                            <input type="checkbox" name="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        <a href="<?php echo esc_url( wp_lostpassword_url( get_permalink() ) ); ?>" class="portal-forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="portal-login-button" id="portal-login-submit">
                        <span class="button-text">Sign In</span>
                        <span class="button-loading" style="display: none;">
                            <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" stroke-opacity="0.25"></circle>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                            </svg>
                        </span>
                    </button>

                    <?php wp_nonce_field( 'portal_login_nonce', 'portal_nonce' ); ?>
                </form>

                <?php if ( class_exists( 'FRAMT_Magic_Link' ) ) : ?>
                    <div class="portal-login-divider"><span>or, without a password</span></div>
                    <?php echo FRAMT_Magic_Link::form_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the class. ?>
                <?php endif; ?>

                <?php
                // Check if registration is enabled
                $registration_enabled = get_option( 'users_can_register' );
                $registration_url = wp_registration_url();
                ?>
                <?php if ( $registration_enabled ) : ?>
                    <p class="portal-register-link">
                        Don't have an account? <a href="<?php echo esc_url( $registration_url ); ?>">Create one</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function() {
            var form = document.getElementById('portal-login-form');
            var errorDiv = document.getElementById('portal-login-error');
            var submitBtn = document.getElementById('portal-login-submit');
            var buttonText = submitBtn.querySelector('.button-text');
            var buttonLoading = submitBtn.querySelector('.button-loading');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Show loading state
                buttonText.style.display = 'none';
                buttonLoading.style.display = 'inline-flex';
                submitBtn.disabled = true;
                errorDiv.style.display = 'none';

                var formData = new FormData();
                formData.append('action', 'framt_portal_login');
                formData.append('username', document.getElementById('portal-username').value);
                formData.append('password', document.getElementById('portal-password').value);
                formData.append('remember', form.querySelector('[name="remember"]').checked ? '1' : '0');
                formData.append('nonce', document.querySelector('[name="portal_nonce"]').value);
                // Where the member was going before sign-in (validated server-side).
                var redirectTo = new URLSearchParams(window.location.search).get('redirect_to');
                if (redirectTo) {
                    formData.append('redirect_to', redirectTo);
                }

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    // If something upstream turned this into a redirect or an
                    // HTML error page, say so instead of failing silently.
                    return response.text().then(function(body) {
                        try {
                            return JSON.parse(body);
                        } catch (e) {
                            return {
                                success: false,
                                data: 'The server returned an unexpected response (HTTP ' +
                                    response.status + '). Please try again, or contact support if this continues.'
                            };
                        }
                    });
                })
                .then(function(data) {
                    if (data.success) {
                        // Go where the member was headed (same-site only,
                        // checked by the server), else reload to show the portal.
                        var target = data.data && data.data.redirect;
                        if (target && target.indexOf(window.location.origin + '/') === 0) {
                            window.location.href = target;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        // Show error
                        errorDiv.textContent = data.data || 'Invalid username or password. Please try again.';
                        errorDiv.style.display = 'block';
                        buttonText.style.display = 'inline';
                        buttonLoading.style.display = 'none';
                        submitBtn.disabled = false;
                    }
                })
                .catch(function(error) {
                    errorDiv.textContent = 'An error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                    buttonText.style.display = 'inline';
                    buttonLoading.style.display = 'none';
                    submitBtn.disabled = false;
                });
            });
        })();
        </script>
    <?php endif; ?>

    <?php if ( $settings['show_wp_footer'] ) : ?>
        <?php get_footer(); ?>
    <?php else : ?>
        <?php wp_footer(); ?>
    <?php endif; ?>
</body>
</html>
