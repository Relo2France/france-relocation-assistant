<?php
/**
 * 404 Template
 *
 * @package Relo2France
 */

get_header();
?>

<div class="content-narrow">
    <section class="error-404 not-found card text-center">
        <header class="page-header">
            <div style="font-size: 6rem; margin-bottom: 1rem;">🗺️</div>
            <h1 class="page-title"><?php esc_html_e('Page Not Found', 'relo2france'); ?></h1>
        </header>

        <div class="page-content">
            <p><?php esc_html_e('It looks like this page has relocated! The page you\'re looking for doesn\'t exist or may have been moved.', 'relo2france'); ?></p>
            
            <div class="mt-3">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                    <?php esc_html_e('Go to Homepage', 'relo2france'); ?>
                </a>
            </div>
            
            <div class="mt-3">
                <p><strong><?php esc_html_e('Looking for something specific?', 'relo2france'); ?></strong></p>
                <?php get_search_form(); ?>
            </div>
        </div>
    </section>
</div>

<?php
get_footer();
