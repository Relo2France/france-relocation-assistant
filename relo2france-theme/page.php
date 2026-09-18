<?php
/**
 * Page Template
 *
 * @package Relo2France
 */

// Sign-in, account, logged-out and thank-you draw their own card; the
// full-screen auth template renders them without a second title and card.
if (is_page(array('login', 'account', 'logged-out', 'thank-you'))) {
    include locate_template('template-auth.php');
    return;
}

get_header();
?>

<div class="content-narrow">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('card'); ?>>
            <header class="page-header">
                <h1 class="page-title"><?php the_title(); ?></h1>
            </header>
            
            <div class="entry-content">
                <?php
                // The auth pages are WordPress pages whose content may be a
                // bare MemberPress shortcode, or nothing at all when
                // MemberPress intercepts a signed-out visitor. Render the card
                // that belongs to the slug unless the content already has one.
                $r2f_slug    = get_post_field('post_name');
                $r2f_content = get_the_content();
                $r2f_has_card = has_shortcode($r2f_content, 'fra_login_page')
                    || has_shortcode($r2f_content, 'fra_account_page')
                    || has_shortcode($r2f_content, 'fra_logout_page')
                    || has_shortcode($r2f_content, 'fra_thankyou_page')
                    || has_shortcode($r2f_content, 'fra_signup_page');
                if (!$r2f_has_card && 'login' === $r2f_slug) {
                    echo do_shortcode('[fra_login_page]');
                } elseif (!$r2f_has_card && 'account' === $r2f_slug) {
                    echo do_shortcode('[fra_account_page]');
                } elseif (!$r2f_has_card && 'logged-out' === $r2f_slug) {
                    echo do_shortcode('[fra_logout_page]');
                } elseif (!$r2f_has_card && 'thank-you' === $r2f_slug) {
                    echo do_shortcode('[fra_thankyou_page]');
                } else {
                    the_content();
                }
                
                wp_link_pages(array(
                    'before' => '<div class="page-links">' . __('Pages:', 'relo2france'),
                    'after'  => '</div>',
                ));
                ?>
            </div>
        </article>
        
        <?php
        // If comments are open or there are comments, load the comments template
        if (comments_open() || get_comments_number()) :
            comments_template();
        endif;
        
    endwhile;
    ?>
</div>

<?php
get_footer();
