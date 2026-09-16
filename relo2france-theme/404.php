<?php
/**
 * 404 Template
 *
 * @package Relo2France
 */

get_header();
?>

<div class="content-narrow">
    <article class="error-404 not-found card">
        <span class="eyebrow">404</span>
        <h1 class="page-title"><?php esc_html_e('That page isn’t here', 'relo2france'); ?></h1>
        <p><?php esc_html_e('The link may be out of date, or the guide may have been renamed. The guides cover everything from choosing a visa to opening a bank account.', 'relo2france'); ?></p>
        <p class="mt-2">
            <a href="<?php echo esc_url(home_url('/guides/')); ?>" class="btn btn-primary"><?php esc_html_e('Browse the guides', 'relo2france'); ?></a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline"><?php esc_html_e('Home', 'relo2france'); ?></a>
        </p>
    </article>
</div>

<?php
get_footer();
