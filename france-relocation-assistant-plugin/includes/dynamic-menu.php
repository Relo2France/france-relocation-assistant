<?php
/**
 * Dynamic Menu Generator
 *
 * Generates knowledge base menu structure from database
 * instead of hardcoded arrays.
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get complete knowledge base menu structure
 *
 * Returns hierarchical array of categories and their articles,
 * with caching for performance.
 *
 * @return array Menu structure
 */
function fra_get_kb_menu() {
    // Check transient cache first
    $cache_key = 'fra_kb_menu_structure';
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $menu = array();

    // Get all categories
    $categories = get_terms(array(
        'taxonomy'   => 'kb_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC'
    ));

    if (is_wp_error($categories)) {
        return array();
    }

    foreach ($categories as $category) {
        $cat_icon = get_term_meta($category->term_id, '_fra_category_icon', true);

        // Get articles in this category
        $articles = get_posts(array(
            'post_type'      => 'kb_article',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_fra_menu_order',
            'order'          => 'ASC',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'kb_category',
                    'field'    => 'term_id',
                    'terms'    => $category->term_id
                )
            )
        ));

        // Build article list
        $article_items = array();
        foreach ($articles as $article) {
            $quick_facts = get_post_meta($article->ID, '_fra_quick_facts', true);
            $facts_array = $quick_facts ? array_filter(array_map('trim', explode("\n", $quick_facts))) : array();

            $article_items[] = array(
                'id'          => $article->ID,
                'title'       => $article->post_title,
                'slug'        => $article->post_name,
                'url'         => get_permalink($article->ID),
                'icon'        => get_post_meta($article->ID, '_fra_menu_icon', true),
                'quick_facts' => $facts_array,
                'excerpt'     => $article->post_excerpt,
                'verified'    => get_post_meta($article->ID, '_fra_last_verified', true)
            );
        }

        $menu[] = array(
            'id'       => $category->term_id,
            'name'     => $category->name,
            'slug'     => $category->slug,
            'icon'     => $cat_icon ?: '&#128196;',
            'count'    => count($article_items),
            'articles' => $article_items
        );
    }

    // Cache for 1 hour
    set_transient($cache_key, $menu, HOUR_IN_SECONDS);

    return $menu;
}

/**
 * Clear menu cache when content changes
 *
 * @param int $post_id Post ID that was modified
 */
function fra_clear_kb_menu_cache($post_id) {
    if (get_post_type($post_id) === 'kb_article') {
        delete_transient('fra_kb_menu_structure');
    }
}
add_action('save_post', 'fra_clear_kb_menu_cache');
add_action('delete_post', 'fra_clear_kb_menu_cache');
add_action('trash_post', 'fra_clear_kb_menu_cache');
add_action('untrash_post', 'fra_clear_kb_menu_cache');

/**
 * Clear menu cache when taxonomy terms change
 */
function fra_clear_kb_menu_cache_term($term_id, $tt_id, $taxonomy) {
    if ($taxonomy === 'kb_category') {
        delete_transient('fra_kb_menu_structure');
    }
}
add_action('edited_term', 'fra_clear_kb_menu_cache_term', 10, 3);
add_action('created_term', 'fra_clear_kb_menu_cache_term', 10, 3);
add_action('delete_term', 'fra_clear_kb_menu_cache_term', 10, 3);

/**
 * Render the dynamic knowledge base menu
 *
 * @return string HTML output
 */
function fra_render_kb_menu() {
    $menu = fra_get_kb_menu();

    if (empty($menu)) {
        return '<p class="fra-menu-empty">No knowledge base content yet.</p>';
    }

    ob_start();
    ?>
    <nav class="fra-kb-menu" aria-label="Knowledge base navigation">
        <?php foreach ($menu as $category): ?>
        <div class="fra-menu-category" data-category="<?php echo esc_attr($category['slug']); ?>">
            <button class="fra-menu-category-header"
                    aria-expanded="false"
                    aria-controls="fra-cat-<?php echo esc_attr($category['id']); ?>">
                <span class="fra-menu-icon"><?php echo esc_html($category['icon']); ?></span>
                <span class="fra-menu-title"><?php echo esc_html($category['name']); ?></span>
                <span class="fra-menu-toggle" aria-hidden="true">+</span>
            </button>
            <div class="fra-menu-articles"
                 id="fra-cat-<?php echo esc_attr($category['id']); ?>"
                 hidden>
                <?php foreach ($category['articles'] as $article): ?>
                <a href="<?php echo esc_url($article['url']); ?>"
                   class="fra-menu-article"
                   data-article-id="<?php echo esc_attr($article['id']); ?>">
                    <?php if ($article['icon']): ?>
                    <span class="fra-article-icon"><?php echo esc_html($article['icon']); ?></span>
                    <?php endif; ?>
                    <span class="fra-article-title"><?php echo esc_html($article['title']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </nav>
    <?php
    return ob_get_clean();
}
