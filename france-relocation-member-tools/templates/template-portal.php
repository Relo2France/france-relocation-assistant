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

// Check for membership (optional - can be configured) - only if logged in
$require_membership = get_option( 'framt_portal_require_membership', false );
if ( $is_logged_in && $require_membership && class_exists( 'MeprUser' ) ) {
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
    <title><?php echo esc_html( $settings['portal_title'] ); ?> - <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php
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

        /* Login form styles */
        .portal-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, <?php echo esc_attr( $settings['sidebar_bg_color'] ); ?> 0%, <?php echo esc_attr( framt_darken_color( $settings['sidebar_bg_color'], 20 ) ); ?> 100%);
            padding: 20px;
        }

        .portal-login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 48px;
            width: 100%;
            max-width: 420px;
        }

        .portal-login-logo {
            display: block;
            max-height: 60px;
            max-width: 200px;
            margin: 0 auto 24px;
        }

        .portal-login-title {
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin: 0 0 8px;
        }

        .portal-login-subtitle {
            font-family: 'Inter', system-ui, sans-serif;
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
            font-family: 'Inter', system-ui, sans-serif;
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
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }

        .portal-form-group input {
            font-family: 'Inter', system-ui, sans-serif;
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
            font-family: 'Inter', system-ui, sans-serif;
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
            font-family: 'Inter', system-ui, sans-serif;
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
            font-family: 'Inter', system-ui, sans-serif;
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
        <?php echo $settings['custom_css']; ?>
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
                isAdmin: <?php echo current_user_can( 'manage_options' ) ? 'true' : 'false'; ?>
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
                <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="<?php echo esc_attr( $settings['portal_title'] ); ?>" class="portal-login-logo">
                <?php endif; ?>

                <h1 class="portal-login-title"><?php echo esc_html( $settings['portal_title'] ); ?></h1>
                <p class="portal-login-subtitle">Sign in to access your member portal</p>

                <div id="portal-login-error" class="portal-login-error" style="display: none;"></div>

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

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        // Reload page to show portal
                        window.location.reload();
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
