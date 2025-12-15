<?php
/**
 * The main template file
 *
 * @package Relo2France
 */

get_header();
?>

<div class="content-area">
    <?php
    if (have_posts()) :
        
        // If on front page and plugin is active, show the assistant
        if (is_front_page() && relo2france_fra_active()) :
            echo do_shortcode('[france_relocation_assistant]');
        else :
            // Regular blog loop
            ?>
            <div class="posts-wrapper">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content', get_post_type());
                endwhile;
                ?>
            </div>
            
            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size'  => 2,
                'prev_text' => __('&larr; Previous', 'relo2france'),
                'next_text' => __('Next &rarr;', 'relo2france'),
            ));
            ?>
            <?php
        endif;
        
    else :
        get_template_part('template-parts/content', 'none');
    endif;
    ?>
</div>

<?php
get_footer();
