<?php
/**
 * Template part for displaying a message when no posts are found
 *
 * @package Relo2France
 */
?>

<section class="no-results not-found card">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Nothing Found', 'relo2france'); ?></h1>
    </header>

    <div class="page-content">
        <?php if (is_search()) : ?>
            <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'relo2france'); ?></p>
            <?php get_search_form(); ?>
        <?php else : ?>
            <p><?php esc_html_e('It seems we can&rsquo;t find what you&rsquo;re looking for.', 'relo2france'); ?></p>
            <p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                    <?php esc_html_e('Return to Home', 'relo2france'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</section>
