<?php
/**
 * Custom Post Types for Knowledge Base
 *
 * Registers the kb_article post type and kb_category taxonomy
 * for database-driven knowledge base content.
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Knowledge Base Article post type
 */
function fra_register_post_types() {

    $labels = array(
        'name'               => 'Knowledge Base',
        'singular_name'      => 'Article',
        'menu_name'          => 'Knowledge Base',
        'name_admin_bar'     => 'KB Article',
        'add_new'            => 'Add Article',
        'add_new_item'       => 'Add New Article',
        'new_item'           => 'New Article',
        'edit_item'          => 'Edit Article',
        'view_item'          => 'View Article',
        'all_items'          => 'All Articles',
        'search_items'       => 'Search Articles',
        'not_found'          => 'No articles found.',
        'not_found_in_trash' => 'No articles found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'query_var'          => true,
        'rewrite'            => array(
            'slug'       => 'guides',
            'with_front' => false
        ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-book-alt',
        'supports'           => array(
            'title',
            'editor',
            'excerpt',
            'thumbnail',
            'custom-fields',
            'revisions'
        )
    );

    register_post_type('kb_article', $args);
}
add_action('init', 'fra_register_post_types');

/**
 * Register Knowledge Base Category taxonomy
 */
function fra_register_taxonomies() {

    $labels = array(
        'name'              => 'KB Categories',
        'singular_name'     => 'KB Category',
        'menu_name'         => 'Categories',
        'all_items'         => 'All Categories',
        'edit_item'         => 'Edit Category',
        'view_item'         => 'View Category',
        'update_item'       => 'Update Category',
        'add_new_item'      => 'Add New Category',
        'new_item_name'     => 'New Category Name',
        'search_items'      => 'Search Categories',
        'parent_item'       => 'Parent Category',
        'parent_item_colon' => 'Parent Category:'
    );

    $args = array(
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array(
            'slug'         => 'guides/category',
            'with_front'   => false,
            'hierarchical' => true
        )
    );

    register_taxonomy('kb_category', 'kb_article', $args);

    // Also register tags for cross-cutting topics
    register_taxonomy('kb_tag', 'kb_article', array(
        'labels' => array(
            'name'          => 'KB Tags',
            'singular_name' => 'KB Tag',
            'menu_name'     => 'Tags'
        ),
        'public'       => true,
        'hierarchical' => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rewrite'      => array('slug' => 'guides/tag')
    ));
}
add_action('init', 'fra_register_taxonomies');

/**
 * Flush rewrite rules on plugin activation
 *
 * IMPORTANT: After deploying via WP Pusher, you must also go to
 * Settings -> Permalinks and click Save to flush rules.
 */
function fra_flush_rewrite_rules() {
    fra_register_post_types();
    fra_register_taxonomies();
    flush_rewrite_rules();
}
