<?php
/**
 * Template Name: Auth Page (Full Screen)
 * Template Post Type: page
 *
 * A full-screen template for authentication pages (login, signup, logout).
 * Removes header and footer for a distraction-free auth experience.
 * 
 * Use with France Relocation Assistant shortcodes:
 * - [fra_login_page] - Login form with MemberPress
 * - [fra_signup_page membership_id="123"] - Registration with MemberPress
 * - [fra_logout_page] - Logout confirmation
 *
 * @package Relo2France
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <style id="r2f-auth-template-styles">
        /* ============================================================
           AUTH TEMPLATE CRITICAL STYLES
           These ensure full-screen auth experience regardless of plugins
           ============================================================ */
        
        /* Remove all margins/padding for true full-screen */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Hide WordPress admin bar on auth pages */
        #wpadminbar {
            display: none !important;
        }
        html.wp-toolbar {
            padding-top: 0 !important;
        }
        body.admin-bar {
            margin-top: 0 !important;
        }
        
        /* Auth page content wrapper */
        .r2f-auth-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Ensure auth wrapper from plugin fills screen */
        .r2f-auth-page > .fra-auth-wrapper,
        .r2f-auth-page .fra-auth-wrapper {
            min-height: 100vh;
            flex: 1;
        }
        
        /* Fallback styles if plugin CSS doesn't load */
        .r2f-auth-fallback {
            min-height: 100vh;
            background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .r2f-auth-fallback-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        
        .r2f-auth-fallback-card h1 {
            color: #1e3a8a;
            font-size: 1.5rem;
            margin: 0 0 1rem 0;
        }
        
        .r2f-auth-fallback-card p {
            color: #6b7280;
            margin: 0;
        }
    </style>
</head>
<body <?php body_class('template-auth r2f-auth-body'); ?>>
<?php 
// Skip wp_body_open to avoid any theme injected content
if (function_exists('wp_body_open')) {
    wp_body_open();
}
?>

<div class="r2f-auth-page">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            
            $content = get_the_content();
            
            // Check if content has our shortcodes
            if (has_shortcode($content, 'fra_login_page') || 
                has_shortcode($content, 'fra_signup_page') || 
                has_shortcode($content, 'fra_logout_page') ||
                has_shortcode($content, 'fra_account_page') ||
                has_shortcode($content, 'fra_thankyou_page') ||
                has_shortcode($content, 'mepr-login-form') ||
                has_shortcode($content, 'mepr-account-form')) {
                
                // Output the content with shortcodes processed
                the_content();
                
            } else {
                // Fallback if no recognized shortcode
                ?>
                <div class="r2f-auth-fallback">
                    <div class="r2f-auth-fallback-card">
                        <h1><?php the_title(); ?></h1>
                        <?php the_content(); ?>
                        <p style="margin-top: 1rem;">
                            <a href="<?php echo esc_url(home_url('/')); ?>" style="color: #1e3a8a;">← Back to home</a>
                        </p>
                    </div>
                </div>
                <?php
            }
            
        endwhile;
    else :
        ?>
        <div class="r2f-auth-fallback">
            <div class="r2f-auth-fallback-card">
                <h1>Page Not Found</h1>
                <p>The page you're looking for doesn't exist.</p>
                <p style="margin-top: 1rem;">
                    <a href="<?php echo esc_url(home_url('/')); ?>" style="color: #1e3a8a;">← Back to home</a>
                </p>
            </div>
        </div>
        <?php
    endif;
    ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
