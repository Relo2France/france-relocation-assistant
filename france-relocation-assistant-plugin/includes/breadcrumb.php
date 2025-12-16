<?php
/**
 * Breadcrumb Navigation Component
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the breadcrumb container
 * JavaScript will populate the actual breadcrumb trail
 *
 * @return void Outputs HTML directly
 */
function fra_render_breadcrumb_container() {
    ?>
    <nav class="fra-breadcrumb" aria-label="Breadcrumb navigation">
        <ol class="fra-breadcrumb-list" id="fra-breadcrumb-list">
            <!-- JavaScript populates this -->
        </ol>
    </nav>
    <?php
}

/**
 * Get breadcrumb container as string
 *
 * @return string HTML output
 */
function fra_get_breadcrumb_container() {
    ob_start();
    fra_render_breadcrumb_container();
    return ob_get_clean();
}
