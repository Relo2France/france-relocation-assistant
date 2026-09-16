<?php
/**
 * Single Post Template
 *
 * @package Relo2France
 */

// A MemberPress product (the checkout page) is a post type, so WordPress
// routes it here, but it is a page in every sense that matters: no date, no
// previous/next post, and never a comment form under the payment form.
if (get_post_type() === 'memberpressproduct') {
    get_header();
    ?>
    <div class="content-narrow">
        <?php
        while (have_posts()) :
            the_post();
            $r2f_content = get_the_content();
            // The product's own content is the bare MemberPress form. Unless
            // someone has placed the signup card shortcode on it, render the
            // card: the price framed, what is included, then the form.
            if (has_shortcode($r2f_content, 'fra_signup_page')) {
                the_content();
            } else {
                echo do_shortcode('[fra_signup_page membership_id="' . (int) get_the_ID() . '"]');
            }
        endwhile;
        ?>
    </div>
    <?php
    get_footer();
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
            <header class="entry-header">
                <div class="post-meta">
                    <time datetime="<?php echo get_the_date('c'); ?>">
                        <?php echo get_the_date(); ?>
                    </time>
                    <?php if (has_category()) : ?>
                        <span class="sep"> | </span>
                        <?php the_category(', '); ?>
                    <?php endif; ?>
                </div>
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="entry-content">
                <?php
                the_content();
                
                wp_link_pages(array(
                    'before' => '<div class="page-links">' . __('Pages:', 'relo2france'),
                    'after'  => '</div>',
                ));
                ?>
            </div>
            
            <footer class="entry-footer">
                <?php if (has_tag()) : ?>
                    <div class="post-tags">
                        <strong><?php esc_html_e('Tags:', 'relo2france'); ?></strong>
                        <?php the_tags('', ', '); ?>
                    </div>
                <?php endif; ?>
            </footer>
        </article>
        
        <nav class="post-navigation card">
            <div class="nav-links">
                <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();
                ?>
                
                <?php if ($prev_post) : ?>
                    <div class="nav-previous">
                        <span class="nav-label"><?php esc_html_e('Previous Post', 'relo2france'); ?></span>
                        <a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>">
                            &larr; <?php echo esc_html( get_the_title( $prev_post ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($next_post) : ?>
                    <div class="nav-next">
                        <span class="nav-label"><?php esc_html_e('Next Post', 'relo2france'); ?></span>
                        <a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>">
                            <?php echo esc_html( get_the_title( $next_post ) ); ?> &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
        
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
