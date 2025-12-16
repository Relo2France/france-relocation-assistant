<?php
/**
 * Custom Meta Fields for Knowledge Base Articles
 *
 * Adds custom fields for menu order, icons, quick facts,
 * and verification dates.
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register meta box for article settings
 */
function fra_add_kb_meta_boxes() {
    add_meta_box(
        'fra_kb_article_meta',
        'Article Settings',
        'fra_render_kb_meta_box',
        'kb_article',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'fra_add_kb_meta_boxes');

/**
 * Render the meta box content
 *
 * @param WP_Post $post Current post object
 */
function fra_render_kb_meta_box($post) {
    // Security nonce
    wp_nonce_field('fra_kb_meta_nonce_action', 'fra_kb_meta_nonce');

    // Get existing values
    $menu_icon     = get_post_meta($post->ID, '_fra_menu_icon', true);
    $menu_order    = get_post_meta($post->ID, '_fra_menu_order', true);
    $quick_facts   = get_post_meta($post->ID, '_fra_quick_facts', true);
    $last_verified = get_post_meta($post->ID, '_fra_last_verified', true);
    ?>

    <p>
        <label for="fra_menu_icon"><strong>Menu Icon (emoji):</strong></label><br>
        <input type="text"
               id="fra_menu_icon"
               name="fra_menu_icon"
               value="<?php echo esc_attr($menu_icon); ?>"
               placeholder="&#128196;"
               style="width: 80px; font-size: 1.5em;">
        <br><small>Single emoji for menu display</small>
    </p>

    <p>
        <label for="fra_menu_order"><strong>Menu Order:</strong></label><br>
        <input type="number"
               id="fra_menu_order"
               name="fra_menu_order"
               value="<?php echo esc_attr($menu_order); ?>"
               placeholder="0"
               min="0"
               style="width: 80px;">
        <br><small>Lower numbers appear first</small>
    </p>

    <p>
        <label for="fra_quick_facts"><strong>Quick Facts:</strong></label><br>
        <textarea id="fra_quick_facts"
                  name="fra_quick_facts"
                  rows="5"
                  style="width: 100%;"
                  placeholder="One fact per line"><?php echo esc_textarea($quick_facts); ?></textarea>
        <br><small>Enter 3-5 key facts, one per line. These appear in progressive disclosure.</small>
    </p>

    <p>
        <label for="fra_last_verified"><strong>Last Verified:</strong></label><br>
        <input type="date"
               id="fra_last_verified"
               name="fra_last_verified"
               value="<?php echo esc_attr($last_verified); ?>">
        <br><small>When was this information last verified?</small>
    </p>

    <?php
}

/**
 * Save meta box data
 *
 * @param int $post_id Post ID being saved
 */
function fra_save_kb_meta_box($post_id) {
    // Security checks
    if (!isset($_POST['fra_kb_meta_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['fra_kb_meta_nonce'], 'fra_kb_meta_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save each field
    $fields = array(
        'fra_menu_icon',
        'fra_menu_order',
        'fra_quick_facts',
        'fra_last_verified'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, '_' . $field, $value);
        }
    }
}
add_action('save_post_kb_article', 'fra_save_kb_meta_box');

/**
 * Add category icon field to taxonomy
 */
function fra_add_category_icon_field($term) {
    $icon = '';
    if (is_object($term)) {
        $icon = get_term_meta($term->term_id, '_fra_category_icon', true);
    }
    ?>
    <tr class="form-field">
        <th scope="row"><label for="fra_category_icon">Category Icon (emoji)</label></th>
        <td>
            <input type="text"
                   name="fra_category_icon"
                   id="fra_category_icon"
                   value="<?php echo esc_attr($icon); ?>"
                   style="width: 80px; font-size: 1.5em;">
            <p class="description">Single emoji for menu display (e.g., &#128706; &#127968; &#127973;)</p>
        </td>
    </tr>
    <?php
}
add_action('kb_category_edit_form_fields', 'fra_add_category_icon_field');
add_action('kb_category_add_form_fields', 'fra_add_category_icon_field');

/**
 * Save category icon
 */
function fra_save_category_icon($term_id) {
    if (isset($_POST['fra_category_icon'])) {
        update_term_meta(
            $term_id,
            '_fra_category_icon',
            sanitize_text_field($_POST['fra_category_icon'])
        );
    }
}
add_action('edited_kb_category', 'fra_save_category_icon');
add_action('created_kb_category', 'fra_save_category_icon');
