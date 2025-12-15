<?php
/**
 * Page Template
 *
 * @package Relo2France
 */

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
                the_content();
                
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
