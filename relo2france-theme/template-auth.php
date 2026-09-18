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

get_header();
?>
<div class="r2f-auth-page content-narrow">
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
                // No shortcode on the page: pick the card by the page's slug so
                // the thank-you and logged-out pages never fall to a bare title.
                $r2f_slug = get_post_field('post_name');
                if ('thank-you' === $r2f_slug) {
                    echo do_shortcode('[fra_thankyou_page]');
                } elseif ('logged-out' === $r2f_slug) {
                    echo do_shortcode('[fra_logout_page]');
                } elseif ('login' === $r2f_slug) {
                    echo do_shortcode('[fra_login_page]');
                } elseif ('account' === $r2f_slug) {
                    echo do_shortcode('[fra_account_page]');
                } else {
                    ?>
                    <article class="card">
                        <h1 class="page-title"><?php the_title(); ?></h1>
                        <div class="entry-content"><?php the_content(); ?></div>
                    </article>
                    <?php
                }
            }
        endwhile;
    else :
        ?>
        <article class="card">
            <h1 class="page-title">That page isn’t here</h1>
            <p><a href="<?php echo esc_url(home_url('/guides/')); ?>">Browse the guides</a></p>
        </article>
        <?php
    endif;
    ?>
</div>
<?php get_footer();
