<?php
/**
 * Single Knowledge Base Article Template
 *
 * Custom template for kb_article post type with SEO optimization,
 * breadcrumbs, quick facts, and related articles.
 *
 * @package FranceRelocationAssistant
 * @version 3.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get article data
$post_id = get_the_ID();
$categories = get_the_terms($post_id, 'kb_category');
$category = $categories && !is_wp_error($categories) ? $categories[0] : null;
$quick_facts = get_post_meta($post_id, '_fra_quick_facts', true);
$last_verified = get_post_meta($post_id, '_fra_last_verified', true);
$article_icon = get_post_meta($post_id, '_fra_menu_icon', true);

// Parse quick facts into array
$facts_array = $quick_facts ? array_filter(array_map('trim', explode("\n", $quick_facts))) : array();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('fra-article'); ?> itemscope itemtype="https://schema.org/Article">

    <!-- Breadcrumb Navigation -->
    <nav class="fra-article-breadcrumb" aria-label="Breadcrumb">
        <ol class="fra-breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo esc_url(home_url()); ?>">
                    <span itemprop="name">Home</span>
                </a>
                <meta itemprop="position" content="1" />
                <span class="fra-breadcrumb-sep">&rarr;</span>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo esc_url(home_url('/guides/')); ?>">
                    <span itemprop="name">Guides</span>
                </a>
                <meta itemprop="position" content="2" />
                <span class="fra-breadcrumb-sep">&rarr;</span>
            </li>
            <?php if ($category): ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo esc_url(get_term_link($category)); ?>">
                    <span itemprop="name"><?php echo esc_html($category->name); ?></span>
                </a>
                <meta itemprop="position" content="3" />
                <span class="fra-breadcrumb-sep">&rarr;</span>
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name" class="fra-breadcrumb-current"><?php the_title(); ?></span>
                <meta itemprop="position" content="4" />
            </li>
            <?php else: ?>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name" class="fra-breadcrumb-current"><?php the_title(); ?></span>
                <meta itemprop="position" content="3" />
            </li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Article Header -->
    <header class="fra-article-header">
        <?php if ($article_icon): ?>
        <span class="fra-article-icon"><?php echo esc_html($article_icon); ?></span>
        <?php endif; ?>

        <h1 class="fra-article-title" itemprop="headline"><?php the_title(); ?></h1>

        <div class="fra-article-meta">
            <?php if ($category): ?>
            <span class="fra-article-category">
                <a href="<?php echo esc_url(get_term_link($category)); ?>">
                    <?php echo esc_html($category->name); ?>
                </a>
            </span>
            <?php endif; ?>

            <?php if ($last_verified): ?>
            <span class="fra-article-verified">
                <span class="fra-verified-icon">&#10003;</span>
                Last verified:
                <time datetime="<?php echo esc_attr($last_verified); ?>">
                    <?php echo esc_html(date('F j, Y', strtotime($last_verified))); ?>
                </time>
            </span>
            <?php endif; ?>

            <span class="fra-article-reading-time">
                <?php echo esc_html(fra_get_reading_time($post_id)); ?> min read
            </span>
        </div>
    </header>

    <!-- Quick Facts Section -->
    <?php if (!empty($facts_array)): ?>
    <section class="fra-quick-facts" aria-label="Quick Facts">
        <h2 class="fra-quick-facts-title">
            <span class="fra-facts-icon">&#128203;</span>
            Quick Facts
        </h2>
        <ul class="fra-facts-list">
            <?php foreach ($facts_array as $fact): ?>
            <li class="fra-fact-item">
                <span class="fra-fact-bullet">&bull;</span>
                <?php echo esc_html($fact); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <!-- Main Article Content -->
    <div class="fra-article-content" itemprop="articleBody">
        <?php the_content(); ?>
    </div>

    <!-- Related Articles -->
    <?php
    $related_articles = fra_get_related_articles($post_id, 3);
    if ($related_articles->have_posts()):
    ?>
    <aside class="fra-related-articles" aria-label="Related articles">
        <h2 class="fra-related-title">Related Guides</h2>
        <div class="fra-related-grid">
            <?php while ($related_articles->have_posts()): $related_articles->the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="fra-related-card">
                <span class="fra-related-icon">
                    <?php echo esc_html(get_post_meta(get_the_ID(), '_fra_menu_icon', true) ?: '&#128196;'); ?>
                </span>
                <span class="fra-related-card-title"><?php the_title(); ?></span>
            </a>
            <?php endwhile; ?>
        </div>
    </aside>
    <?php
    wp_reset_postdata();
    endif;
    ?>

    <!-- CTA Section -->
    <section class="fra-article-cta">
        <h2 class="fra-cta-title">Have More Questions?</h2>
        <p class="fra-cta-text">Our AI assistant can provide personalized answers based on your specific situation.</p>
        <a href="<?php echo esc_url(home_url('/#chat')); ?>" class="fra-cta-button">
            Ask the AI Assistant &rarr;
        </a>
    </section>

    <!-- Article Footer -->
    <footer class="fra-article-footer">
        <div class="fra-article-tags">
            <?php
            $tags = get_the_terms($post_id, 'kb_tag');
            if ($tags && !is_wp_error($tags)):
                foreach ($tags as $tag):
            ?>
            <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="fra-tag">
                <?php echo esc_html($tag->name); ?>
            </a>
            <?php
                endforeach;
            endif;
            ?>
        </div>

        <p class="fra-article-disclaimer">
            <strong>Disclaimer:</strong> This information is for general guidance only.
            Immigration, tax, and legal matters require professional consultation.
            Always verify with official French government sources.
        </p>
    </footer>

</article>

<?php get_footer(); ?>

<?php
/**
 * Helper: Calculate reading time
 */
if (!function_exists('fra_get_reading_time')) {
    function fra_get_reading_time($post_id) {
        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // 200 words per minute
        return max(1, $reading_time);
    }
}

/**
 * Helper: Get related articles
 */
if (!function_exists('fra_get_related_articles')) {
    function fra_get_related_articles($post_id, $count = 3) {
        $categories = get_the_terms($post_id, 'kb_category');
        $category_ids = $categories ? wp_list_pluck($categories, 'term_id') : array();

        return new WP_Query(array(
            'post_type' => 'kb_article',
            'posts_per_page' => $count,
            'post__not_in' => array($post_id),
            'post_status' => 'publish',
            'tax_query' => !empty($category_ids) ? array(
                array(
                    'taxonomy' => 'kb_category',
                    'field' => 'term_id',
                    'terms' => $category_ids
                )
            ) : array(),
            'orderby' => 'rand'
        ));
    }
}
?>
