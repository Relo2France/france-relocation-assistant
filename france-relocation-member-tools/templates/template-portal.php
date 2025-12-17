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

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> - Members Portal</title>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php wp_head(); ?>

    <style>
        /* Critical CSS for loading state */
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
            border-top-color: #22c55e;
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
    </style>
</head>
<body <?php body_class( 'fra-portal-page' ); ?>>
    <?php wp_body_open(); ?>

    <!-- Portal root element -->
    <div id="fra-portal-root">
        <!-- Loading state shown before React mounts -->
        <div class="portal-loading">
            <div class="portal-loading-spinner"></div>
            <p class="portal-loading-text">Loading Members Portal...</p>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
