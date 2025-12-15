<?php
/**
 * Front Page Template
 *
 * @package Relo2France
 */

get_header();
?>

<div class="front-page-content">
    <?php
    // Check if France Relocation Assistant plugin is active
    if (relo2france_fra_active()) :
        // Display the assistant
        echo do_shortcode('[france_relocation_assistant]');
    else :
        // Show a message if plugin is not active
        ?>
        <div class="card text-center">
            <h2><?php esc_html_e('France Relocation Assistant', 'relo2france'); ?></h2>
            <p><?php esc_html_e('The France Relocation Assistant plugin needs to be installed and activated to display the relocation guide.', 'relo2france'); ?></p>
            <?php if (current_user_can('install_plugins')) : ?>
                <p>
                    <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="btn btn-primary">
                        <?php esc_html_e('Go to Plugins', 'relo2france'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php
        // Show regular page content if it exists
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                ?>
                <div class="card">
                    <?php the_content(); ?>
                </div>
                <?php
            endwhile;
        endif;
    endif;
    ?>
</div>

<?php
get_footer();
