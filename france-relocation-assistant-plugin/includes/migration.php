<?php
/**
 * Content Migration Script
 *
 * One-time migration of hardcoded knowledge base content
 * to the kb_article custom post type.
 *
 * @package FranceRelocationAssistant
 * @version 3.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add migration admin page
 */
function fra_add_migration_page() {
    add_submenu_page(
        'edit.php?post_type=kb_article',
        'Content Migration',
        'Migration',
        'manage_options',
        'fra-migration',
        'fra_render_migration_page'
    );
}
add_action('admin_menu', 'fra_add_migration_page');

/**
 * Render migration admin page
 */
function fra_render_migration_page() {
    // Check if migration already ran
    $migration_complete = get_option('fra_migration_complete', false);

    // Handle migration action
    if (isset($_POST['fra_run_migration']) && check_admin_referer('fra_migration_nonce')) {
        $results = fra_run_content_migration();
        echo '<div class="notice notice-success"><p>' . esc_html($results) . '</p></div>';
    }

    // Handle reset action (for testing)
    if (isset($_POST['fra_reset_migration']) && check_admin_referer('fra_migration_nonce')) {
        delete_option('fra_migration_complete');
        $migration_complete = false;
        echo '<div class="notice notice-warning"><p>Migration flag reset. You can run migration again.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Knowledge Base Content Migration</h1>

        <?php if ($migration_complete): ?>
        <div class="notice notice-info">
            <p><strong>Migration has already been completed.</strong></p>
            <p>If you need to run it again (e.g., after testing), reset the migration flag first.</p>
        </div>
        <?php endif; ?>

        <div class="card" style="max-width: 600px; padding: 20px;">
            <h2>Migration Status</h2>
            <p>
                <strong>Status:</strong>
                <?php echo $migration_complete ? '&#10003; Complete' : '&#8987; Not yet run'; ?>
            </p>

            <h3>What This Does</h3>
            <ul>
                <li>Creates kb_category taxonomy terms</li>
                <li>Creates kb_article posts from hardcoded data</li>
                <li>Sets custom meta fields (icons, order, quick facts)</li>
                <li>Assigns articles to categories</li>
            </ul>

            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('fra_migration_nonce'); ?>

                <?php if (!$migration_complete): ?>
                <p>
                    <input type="submit"
                           name="fra_run_migration"
                           class="button button-primary"
                           value="Run Migration"
                           onclick="return confirm('Are you sure? This will create posts and categories.');">
                </p>
                <?php else: ?>
                <p>
                    <input type="submit"
                           name="fra_reset_migration"
                           class="button button-secondary"
                           value="Reset Migration Flag"
                           onclick="return confirm('This allows migration to run again. Continue?');">
                </p>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Run the content migration
 *
 * @return string Result message
 */
function fra_run_content_migration() {
    // Prevent re-running
    if (get_option('fra_migration_complete', false)) {
        return 'Migration already completed. Reset flag to run again.';
    }

    $categories_created = 0;
    $articles_created = 0;

    // Define content structure
    // CUSTOMIZE THIS with your actual content
    $content_structure = fra_get_migration_content();

    foreach ($content_structure as $category_data) {
        // Create category
        $category_term = wp_insert_term(
            $category_data['name'],
            'kb_category',
            array(
                'slug' => $category_data['slug'],
                'description' => $category_data['description'] ?? ''
            )
        );

        if (is_wp_error($category_term)) {
            // Category might already exist
            $existing = get_term_by('slug', $category_data['slug'], 'kb_category');
            $category_id = $existing ? $existing->term_id : 0;
        } else {
            $category_id = $category_term['term_id'];
            $categories_created++;

            // Set category icon
            if (!empty($category_data['icon'])) {
                update_term_meta($category_id, '_fra_category_icon', $category_data['icon']);
            }
        }

        // Create articles in this category
        if (!empty($category_data['articles']) && $category_id) {
            foreach ($category_data['articles'] as $order => $article) {
                // Check if article already exists
                $existing_post = get_page_by_path($article['slug'], OBJECT, 'kb_article');
                if ($existing_post) {
                    continue; // Skip existing articles
                }

                // Create the article
                $post_id = wp_insert_post(array(
                    'post_type'    => 'kb_article',
                    'post_title'   => $article['title'],
                    'post_name'    => $article['slug'],
                    'post_content' => $article['content'],
                    'post_excerpt' => $article['excerpt'] ?? '',
                    'post_status'  => 'publish'
                ));

                if ($post_id && !is_wp_error($post_id)) {
                    $articles_created++;

                    // Assign to category
                    wp_set_object_terms($post_id, $category_id, 'kb_category');

                    // Set meta fields
                    if (!empty($article['icon'])) {
                        update_post_meta($post_id, '_fra_menu_icon', $article['icon']);
                    }
                    update_post_meta($post_id, '_fra_menu_order', $order);

                    if (!empty($article['quick_facts'])) {
                        update_post_meta($post_id, '_fra_quick_facts', implode("\n", $article['quick_facts']));
                    }

                    update_post_meta($post_id, '_fra_last_verified', date('Y-m-d'));
                }
            }
        }
    }

    // Mark migration complete
    update_option('fra_migration_complete', true);
    update_option('fra_migration_date', current_time('mysql'));

    // Clear menu cache
    delete_transient('fra_kb_menu_structure');

    return sprintf(
        'Migration complete! Created %d categories and %d articles.',
        $categories_created,
        $articles_created
    );
}

/**
 * Get migration content structure
 *
 * CUSTOMIZE THIS FUNCTION with your actual content.
 * This is a template showing the expected structure.
 *
 * @return array Content structure
 */
function fra_get_migration_content() {
    return array(
        // VISAS CATEGORY
        array(
            'name' => 'Visas & Immigration',
            'slug' => 'visas',
            'icon' => "\xF0\x9F\x93\x8B",
            'description' => 'Visa types, requirements, and application processes',
            'articles' => array(
                array(
                    'title' => 'Long-Stay Visa Overview',
                    'slug' => 'long-stay-visa-overview',
                    'icon' => "\xF0\x9F\x93\x84",
                    'excerpt' => 'Understanding the French long-stay visa requirements and application process.',
                    'content' => '<h2>What is a Long-Stay Visa?</h2>
<p>A long-stay visa (visa de long séjour) is required for stays in France exceeding 90 days. This visa allows you to live, work, or study in France depending on your specific visa type.</p>

<h2>Types of Long-Stay Visas</h2>
<p>France offers several categories of long-stay visas:</p>
<ul>
<li><strong>Visitor Visa</strong> - For those who can support themselves without working</li>
<li><strong>Talent Passport</strong> - For skilled workers and entrepreneurs</li>
<li><strong>Student Visa</strong> - For those enrolled in French educational institutions</li>
<li><strong>Family Reunification</strong> - For joining family members in France</li>
</ul>

<h2>Application Process</h2>
<p>The visa application process typically takes 2-3 months and involves several steps including document preparation, appointment scheduling, and biometric collection.</p>',
                    'quick_facts' => array(
                        'Long-stay visas are required for stays over 90 days',
                        'Processing time is typically 2-3 months',
                        'Apply at the French consulate in your jurisdiction',
                        'Most visas require proof of financial resources',
                        'Some visas allow you to work, others do not'
                    )
                ),
                array(
                    'title' => 'Visitor Visa Requirements',
                    'slug' => 'visitor-visa-requirements',
                    'icon' => "\xF0\x9F\x91\xA4",
                    'excerpt' => 'Complete guide to the French visitor visa for non-working residents.',
                    'content' => '<h2>Visitor Visa Overview</h2>
<p>The visitor visa (visa de long séjour visiteur) is designed for individuals who wish to reside in France without engaging in professional activities.</p>

<h2>Key Requirements</h2>
<ul>
<li>Proof of sufficient financial resources (approximately €1,500/month minimum)</li>
<li>Health insurance coverage valid in France</li>
<li>Accommodation arrangements (rental agreement or property ownership)</li>
<li>Clean criminal record</li>
</ul>

<h2>Financial Requirements</h2>
<p>You must demonstrate that you can support yourself without working. This typically means showing bank statements, investment income, pension, or other passive income sources.</p>',
                    'quick_facts' => array(
                        'Cannot work with this visa type',
                        'Must prove €1,500+/month in income',
                        'Health insurance is mandatory',
                        'Valid for 1 year, renewable',
                        'Leads to carte de séjour visiteur'
                    )
                )
            )
        ),

        // PROPERTY CATEGORY
        array(
            'name' => 'Property & Housing',
            'slug' => 'property',
            'icon' => "\xF0\x9F\x8F\xA0",
            'description' => 'Buying property, renting, and housing in France',
            'articles' => array(
                array(
                    'title' => 'Buying Property in France',
                    'slug' => 'buying-property-france',
                    'icon' => "\xF0\x9F\x8F\xA1",
                    'excerpt' => 'Step-by-step guide to purchasing property in France as an American.',
                    'content' => '<h2>Can Americans Buy Property in France?</h2>
<p>Yes! There are no restrictions on foreigners purchasing property in France. Americans can buy residential or commercial property with the same rights as French citizens.</p>

<h2>The Buying Process</h2>
<p>The French property buying process is highly regulated and involves several key steps:</p>
<ol>
<li>Make an offer (offre d\'achat)</li>
<li>Sign preliminary contract (compromis de vente)</li>
<li>10-day cooling off period</li>
<li>Secure financing if needed</li>
<li>Final signing at notaire office (acte de vente)</li>
</ol>

<h2>Costs to Expect</h2>
<p>Beyond the purchase price, budget for notaire fees (7-8% for older properties), potential agency fees, and various taxes.</p>',
                    'quick_facts' => array(
                        'No restrictions for Americans buying property',
                        'Notaire fees are 7-8% for older properties',
                        '10-day cooling off period after signing',
                        'Process typically takes 2-3 months',
                        'You do not need a visa to own property'
                    )
                ),
                array(
                    'title' => 'Role of the Notaire',
                    'slug' => 'role-of-notaire',
                    'icon' => "\xE2\x9A\x96",
                    'excerpt' => 'Understanding the French notaire and their role in property transactions.',
                    'content' => '<h2>What is a Notaire?</h2>
<p>A notaire is a public official appointed by the French government to authenticate legal documents, particularly in real estate transactions.</p>

<h2>Notaire Responsibilities</h2>
<ul>
<li>Verify the seller\'s ownership and right to sell</li>
<li>Check for any liens or encumbrances</li>
<li>Prepare all legal documents</li>
<li>Collect and remit taxes</li>
<li>Register the sale with land registry</li>
</ul>

<h2>Choosing a Notaire</h2>
<p>You can use the seller\'s notaire or choose your own. Having your own notaire (especially one who speaks English) can be helpful but adds minimal extra cost as fees are shared.</p>',
                    'quick_facts' => array(
                        'Notaire is a government-appointed official',
                        'Required for all property transactions',
                        'Fees are regulated by government',
                        'You can choose your own notaire',
                        'Some notaires speak English'
                    )
                )
            )
        ),

        // HEALTHCARE CATEGORY
        array(
            'name' => 'Healthcare',
            'slug' => 'healthcare',
            'icon' => "\xF0\x9F\x8F\xA5",
            'description' => 'French healthcare system, insurance, and medical care',
            'articles' => array(
                array(
                    'title' => 'French Healthcare System Overview',
                    'slug' => 'french-healthcare-overview',
                    'icon' => "\xE2\x9A\x95",
                    'excerpt' => 'Understanding the French healthcare system and how to access it.',
                    'content' => '<h2>About French Healthcare</h2>
<p>France has one of the best healthcare systems in the world. The system is a mix of public and private care, with the government covering a significant portion of medical costs.</p>

<h2>How It Works</h2>
<p>The French system operates on a reimbursement basis. You typically pay for services upfront and are reimbursed by the state health insurance (Assurance Maladie) at rates of 70-100% depending on the service.</p>

<h2>Getting Covered</h2>
<p>As a legal resident, you can enroll in PUMA (Protection Universelle Maladie), the universal health coverage system, after three months of residency.</p>',
                    'quick_facts' => array(
                        'France has universal healthcare coverage',
                        'System operates on reimbursement basis',
                        'Covers 70-100% of most medical costs',
                        'Eligible after 3 months of residency',
                        'Carte Vitale is your health card'
                    )
                ),
                array(
                    'title' => 'Getting Your Carte Vitale',
                    'slug' => 'carte-vitale-application',
                    'icon' => "\xF0\x9F\x92\xB3",
                    'excerpt' => 'How to apply for and use your French health insurance card.',
                    'content' => '<h2>What is the Carte Vitale?</h2>
<p>The Carte Vitale is a green electronic card that proves your enrollment in the French health insurance system. It contains your health insurance information and allows for automatic reimbursement.</p>

<h2>How to Apply</h2>
<ol>
<li>Register with your local CPAM (Caisse Primaire d\'Assurance Maladie)</li>
<li>Submit required documents (ID, proof of residence, visa)</li>
<li>Wait for your attestation (temporary document)</li>
<li>Receive your Carte Vitale by mail (4-6 weeks)</li>
</ol>

<h2>Using Your Card</h2>
<p>Present your Carte Vitale at pharmacies, doctors, and hospitals for automatic processing of your reimbursements.</p>',
                    'quick_facts' => array(
                        'Green card proving health coverage',
                        'Apply at local CPAM office',
                        'Processing takes 4-6 weeks',
                        'Enables automatic reimbursement',
                        'Update when circumstances change'
                    )
                )
            )
        ),

        // TAXES CATEGORY
        array(
            'name' => 'Taxes & Finance',
            'slug' => 'taxes',
            'icon' => "\xF0\x9F\x92\xB0",
            'description' => 'Tax obligations, banking, and financial considerations',
            'articles' => array(
                array(
                    'title' => 'Tax Residency Rules',
                    'slug' => 'tax-residency-rules',
                    'icon' => "\xF0\x9F\x93\x8A",
                    'excerpt' => 'Understanding when you become a French tax resident.',
                    'content' => '<h2>The 183-Day Rule</h2>
<p>You are generally considered a French tax resident if you spend more than 183 days in France during a calendar year. However, other factors can also establish tax residency.</p>

<h2>Other Determining Factors</h2>
<ul>
<li>Your primary home (foyer) is in France</li>
<li>Your principal professional activity is in France</li>
<li>Your center of economic interests is in France</li>
</ul>

<h2>US-France Tax Treaty</h2>
<p>The tax treaty between the US and France helps prevent double taxation. However, as a US citizen, you remain subject to US tax obligations regardless of where you live.</p>',
                    'quick_facts' => array(
                        '183+ days in France = tax resident',
                        'Multiple factors determine residency',
                        'US-France tax treaty prevents double taxation',
                        'US citizens always file US taxes',
                        'Use our 183-Day Tracker tool!'
                    )
                ),
                array(
                    'title' => 'Opening a French Bank Account',
                    'slug' => 'french-bank-account',
                    'icon' => "\xF0\x9F\x8F\xA6",
                    'excerpt' => 'How to open a bank account in France as an American.',
                    'content' => '<h2>Why You Need a French Account</h2>
<p>A French bank account is essential for paying rent, utilities, receiving salary, and daily transactions. Many landlords and employers require one.</p>

<h2>Required Documents</h2>
<ul>
<li>Valid passport</li>
<li>Proof of address in France (or attestation d\'hébergement)</li>
<li>Visa or titre de séjour</li>
<li>Proof of income or employment</li>
</ul>

<h2>Banking Options</h2>
<p>Traditional banks (BNP Paribas, Société Générale, Crédit Agricole) and online banks (Boursorama, N26, Revolut) all serve expats, with varying requirements.</p>',
                    'quick_facts' => array(
                        'Essential for rent, utilities, salary',
                        'Bring passport, proof of address, visa',
                        'Some banks have English-speaking staff',
                        'Online banks often easier to open',
                        'RIB is your bank account identifier'
                    )
                )
            )
        )
    );
}

/**
 * Admin notice if migration needed
 */
function fra_migration_admin_notice() {
    if (!get_option('fra_migration_complete', false)) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'kb_article') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Content Migration Available:</strong> ';
            echo 'You can migrate existing content to the Knowledge Base. ';
            echo '<a href="' . admin_url('edit.php?post_type=kb_article&page=fra-migration') . '">Run Migration</a></p>';
            echo '</div>';
        }
    }
}
add_action('admin_notices', 'fra_migration_admin_notice');
