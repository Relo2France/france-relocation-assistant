<?php
/**
 * Template part for displaying posts
 *
 * @package Relo2France
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-entry'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('medium_large', array('class' => 'post-thumbnail')); ?>
        </a>
    <?php endif; ?>
    
    <div class="post-content">
        <div class="post-meta">
            <time datetime="<?php echo get_the_date('c'); ?>">
                <?php echo get_the_date(); ?>
            </time>
            <?php if (has_category()) : ?>
                <span class="sep"> | </span>
                <?php the_category(', '); ?>
            <?php endif; ?>
        </div>
        
        <h2 class="entry-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        
        <div class="entry-summary">
            <?php the_excerpt(); ?>
        </div>
        
        <a href="<?php the_permalink(); ?>" class="btn btn-outline">
            <?php esc_html_e('Read More', 'relo2france'); ?> →
        </a>
    </div>
</article>
