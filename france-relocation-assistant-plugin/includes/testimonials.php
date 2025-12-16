<?php
/**
 * Testimonials Data and Display Functions
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get testimonials data array
 *
 * @return array Array of testimonial data
 */
function fra_get_testimonials() {
    return array(
        array(
            'name'     => 'Sarah M.',
            'location' => 'California',
            'text'     => 'The 183-day tracker alone saved me from a costly tax mistake. This tool pays for itself many times over.',
            'rating'   => 5,
            'date'     => '2025-11'
        ),
        array(
            'name'     => 'Michael R.',
            'location' => 'Texas',
            'text'     => 'Finally, clear guidance on French visas without paying thousands for a consultant. The AI answered all my questions.',
            'rating'   => 5,
            'date'     => '2025-10'
        ),
        array(
            'name'     => 'Jennifer L.',
            'location' => 'New York',
            'text'     => 'The AI chat answered questions I did not even know to ask. Incredible resource for anyone planning a move to France.',
            'rating'   => 5,
            'date'     => '2025-12'
        ),
        array(
            'name'     => 'David K.',
            'location' => 'Florida',
            'text'     => 'Worth every penny. The step-by-step checklists kept us organized through the entire visa process.',
            'rating'   => 5,
            'date'     => '2025-09'
        ),
        array(
            'name'     => 'Lisa T.',
            'location' => 'Washington',
            'text'     => 'We almost hired a $3,000 relocation consultant. So glad we found this first. Everything we needed was here.',
            'rating'   => 5,
            'date'     => '2025-11'
        )
    );
}

/**
 * Render testimonials HTML
 *
 * @param int $count Number of testimonials to show (default: 3)
 * @return string HTML output
 */
function fra_render_testimonials($count = 3) {
    $testimonials = fra_get_testimonials();
    $testimonials = array_slice($testimonials, 0, $count);

    ob_start();
    ?>
    <section class="fra-testimonials-section" aria-label="Customer testimonials">
        <h3 class="fra-testimonials-title">What Our Members Say</h3>
        <div class="fra-testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
            <article class="fra-testimonial-card">
                <div class="fra-testimonial-stars" aria-label="<?php echo esc_attr($testimonial['rating']); ?> out of 5 stars">
                    <?php echo str_repeat('&#9733;', $testimonial['rating']); ?>
                </div>
                <blockquote class="fra-testimonial-text">
                    "<?php echo esc_html($testimonial['text']); ?>"
                </blockquote>
                <footer class="fra-testimonial-author">
                    <cite>&mdash; <?php echo esc_html($testimonial['name']); ?>, <?php echo esc_html($testimonial['location']); ?></cite>
                </footer>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Render authority badges
 *
 * @return string HTML output
 */
function fra_render_authority_badges() {
    ob_start();
    ?>
    <div class="fra-authority-section">
        <div class="fra-authority-badge">
            <span class="fra-badge-icon">&#127981;</span>
            <span class="fra-badge-text">Information sourced from official French government resources</span>
        </div>
        <div class="fra-source-logos">
            <span class="fra-source-label">Powered by official sources:</span>
            <a href="https://france-visas.gouv.fr" target="_blank" rel="noopener noreferrer">France-Visas.gouv.fr</a>
            <a href="https://www.service-public.fr" target="_blank" rel="noopener noreferrer">Service-Public.fr</a>
            <a href="https://www.ameli.fr" target="_blank" rel="noopener noreferrer">Ameli.fr</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode to display testimonials
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function fra_testimonials_shortcode($atts) {
    $atts = shortcode_atts(array(
        'count' => 3
    ), $atts, 'fra_testimonials');

    return fra_render_testimonials(intval($atts['count']));
}
add_shortcode('fra_testimonials', 'fra_testimonials_shortcode');

/**
 * Shortcode to display authority badges
 *
 * @return string HTML output
 */
function fra_authority_badges_shortcode() {
    return fra_render_authority_badges();
}
add_shortcode('fra_authority_badges', 'fra_authority_badges_shortcode');
