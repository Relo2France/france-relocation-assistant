<?php
/**
 * Structured Data (Schema.org JSON-LD) Generator
 *
 * Generates structured data for SEO including Article schema,
 * FAQ schema, BreadcrumbList, and Organization.
 *
 * @package FranceRelocationAssistant
 * @version 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate Article schema for kb_article posts
 *
 * @param int $post_id Post ID
 * @return array Schema data
 */
function fra_get_article_schema($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'kb_article') {
        return array();
    }

    $categories = get_the_terms($post_id, 'kb_category');
    $category_name = $categories && !is_wp_error($categories) ? $categories[0]->name : 'Guides';

    $last_verified = get_post_meta($post_id, '_fra_last_verified', true);
    $modified_date = $last_verified ?: get_the_modified_date('c', $post_id);

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->post_title,
        'description' => $post->post_excerpt ?: wp_trim_words($post->post_content, 30),
        'author' => array(
            '@type' => 'Organization',
            'name' => 'Relo2France',
            'url' => home_url()
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => 'Relo2France',
            'url' => home_url()
        ),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => $modified_date,
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => get_permalink($post_id)
        ),
        'articleSection' => $category_name,
        'inLanguage' => 'en-US'
    );
}

/**
 * Generate FAQ schema from quick facts
 *
 * @param int $post_id Post ID
 * @return array|null FAQ schema or null if no facts
 */
function fra_get_faq_schema($post_id) {
    $quick_facts = get_post_meta($post_id, '_fra_quick_facts', true);

    if (empty($quick_facts)) {
        return null;
    }

    $facts = array_filter(array_map('trim', explode("\n", $quick_facts)));

    if (count($facts) < 2) {
        return null;
    }

    $faq_items = array();
    $post = get_post($post_id);
    $topic = $post->post_title;

    foreach ($facts as $index => $fact) {
        // Generate a question from the fact
        $question = fra_fact_to_question($fact, $topic, $index);

        $faq_items[] = array(
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => $fact
            )
        );
    }

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faq_items
    );
}

/**
 * Convert a fact into a question
 *
 * @param string $fact The fact text
 * @param string $topic The article topic
 * @param int $index Position in list
 * @return string Generated question
 */
function fra_fact_to_question($fact, $topic, $index) {
    // Simple question generation patterns
    $patterns = array(
        'What should I know about %s?',
        'What is important regarding %s?',
        'What are the key details about %s?',
        'How does %s work?',
        'What do I need to know about %s?'
    );

    // Extract a key phrase from the fact for the question
    $words = explode(' ', $fact);
    $key_phrase = implode(' ', array_slice($words, 0, min(5, count($words))));

    $pattern_index = $index % count($patterns);
    return sprintf($patterns[$pattern_index], strtolower($key_phrase));
}

/**
 * Generate BreadcrumbList schema
 *
 * @param int $post_id Post ID
 * @return array Breadcrumb schema
 */
function fra_get_breadcrumb_schema($post_id) {
    $post = get_post($post_id);
    $categories = get_the_terms($post_id, 'kb_category');

    $items = array(
        array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => home_url()
        ),
        array(
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Guides',
            'item' => home_url('/guides/')
        )
    );

    $position = 3;

    if ($categories && !is_wp_error($categories)) {
        $category = $categories[0];
        $items[] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $category->name,
            'item' => get_term_link($category)
        );
        $position++;
    }

    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position,
        'name' => $post->post_title
    );

    return array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items
    );
}

/**
 * Generate Organization schema
 *
 * @return array Organization schema
 */
function fra_get_organization_schema() {
    return array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Relo2France',
        'url' => home_url(),
        'description' => 'AI-powered relocation assistance for Americans moving to France',
        'areaServed' => array(
            array('@type' => 'Country', 'name' => 'United States'),
            array('@type' => 'Country', 'name' => 'France')
        ),
        'serviceType' => 'Relocation Assistance'
    );
}

/**
 * Output all structured data for current page
 */
function fra_output_structured_data() {
    if (!is_singular('kb_article')) {
        return;
    }

    $post_id = get_the_ID();
    $schemas = array();

    // Always include Article and Breadcrumb
    $schemas[] = fra_get_article_schema($post_id);
    $schemas[] = fra_get_breadcrumb_schema($post_id);

    // Include FAQ if we have quick facts
    $faq_schema = fra_get_faq_schema($post_id);
    if ($faq_schema) {
        $schemas[] = $faq_schema;
    }

    // Output each schema
    foreach ($schemas as $schema) {
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "\n</script>\n";
        }
    }
}
add_action('wp_head', 'fra_output_structured_data');

/**
 * Output Organization schema on home page
 */
function fra_output_organization_schema() {
    if (!is_front_page()) {
        return;
    }

    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode(fra_get_organization_schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
}
add_action('wp_head', 'fra_output_organization_schema');
