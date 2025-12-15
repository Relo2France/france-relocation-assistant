<?php
/**
 * France Relocation Assistant - SEO Module
 * 
 * Provides comprehensive SEO features including:
 * - Schema.org JSON-LD structured data (FAQ, HowTo, Article)
 * - Meta tag generation
 * - Open Graph and Twitter Card support
 * - Keyword optimization
 * - Compatibility with Yoast SEO and Rank Math
 * 
 * @package France_Relocation_Assistant
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_SEO {
    
    /**
     * Target keywords for France relocation
     */
    private static $primary_keywords = array(
        'moving to France from USA',
        'American expat France',
        'US citizen move to France',
        'France visa for Americans',
        'retire in France American',
        'buy property France American',
        'France long stay visa',
        'expat France guide',
    );
    
    private static $long_tail_keywords = array(
        // Visa-related
        'how to get French visa American citizen',
        'France visitor visa requirements USA',
        'long stay visa France from USA',
        'France work visa for US citizens',
        'retire in France visa requirements',
        'spouse visa France American',
        'France talent passport visa',
        
        // Property-related
        'buying property in France as American',
        'French mortgage for US citizens',
        'buy house France non resident',
        'property purchase process France',
        'notaire France property purchase',
        'compromis de vente explained',
        
        // Healthcare-related
        'French healthcare for expats',
        'carte vitale American expat',
        'health insurance France American',
        'PUMA French healthcare system',
        
        // Tax-related
        'US France tax treaty expats',
        'French taxes for Americans',
        '183 day rule France tax residency',
        'FBAR requirements France',
        'double taxation France USA',
        
        // Driving-related
        'exchange US drivers license France',
        'driving in France American license',
        'import car to France from USA',
        
        // Banking-related
        'French bank account for Americans',
        'open bank account France non resident',
        'FATCA France banking',
        
        // Shipping/Moving
        'shipping belongings to France',
        'moving pets to France from USA',
        'customs importing household goods France',
        
        // General
        'cost of living France vs USA',
        'best places to live France American expats',
        'American expat community France',
        'learn French before moving',
    );
    
    /**
     * Initialize SEO features
     */
    public static function init() {
        // Add schema markup to head
        add_action('wp_head', array(__CLASS__, 'output_schema_markup'), 1);
        
        // Add meta tags
        add_action('wp_head', array(__CLASS__, 'output_meta_tags'), 2);
        
        // Add Open Graph tags
        add_action('wp_head', array(__CLASS__, 'output_open_graph'), 3);
        
        // Filter document title
        add_filter('document_title_parts', array(__CLASS__, 'filter_title'), 10, 1);
        
        // Add canonical URL
        add_action('wp_head', array(__CLASS__, 'output_canonical'), 4);
    }
    
    /**
     * Output Schema.org JSON-LD structured data
     */
    public static function output_schema_markup() {
        // Only output on pages with our shortcode
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'france_relocation_assistant')) {
            return;
        }
        
        $schema = self::build_schema();
        if (!empty($schema)) {
            echo "\n<!-- France Relocation Assistant SEO Schema -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo "\n</script>\n";
        }
    }
    
    /**
     * Build comprehensive schema markup
     */
    private static function build_schema() {
        $site_url = home_url();
        $page_url = get_permalink();
        $site_name = get_bloginfo('name');
        
        // Main graph
        $schema = array(
            '@context' => 'https://schema.org',
            '@graph' => array()
        );
        
        // 1. WebSite schema
        $schema['@graph'][] = array(
            '@type' => 'WebSite',
            '@id' => $site_url . '/#website',
            'url' => $site_url,
            'name' => $site_name,
            'description' => 'Comprehensive guide for Americans relocating to France',
            'publisher' => array(
                '@id' => $site_url . '/#organization'
            ),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $page_url . '?q={search_term_string}'
                ),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        // 2. Organization schema
        $schema['@graph'][] = array(
            '@type' => 'Organization',
            '@id' => $site_url . '/#organization',
            'name' => $site_name,
            'url' => $site_url,
            'description' => 'Expert resources for US citizens moving to France',
        );
        
        // 3. WebPage schema
        $schema['@graph'][] = array(
            '@type' => 'WebPage',
            '@id' => $page_url . '#webpage',
            'url' => $page_url,
            'name' => 'France Relocation Assistant - Complete Guide for Americans Moving to France',
            'description' => 'Free comprehensive guide for US citizens relocating to France. Covers visas, property purchase, healthcare, taxes, driving, and more.',
            'isPartOf' => array(
                '@id' => $site_url . '/#website'
            ),
            'about' => array(
                '@type' => 'Thing',
                'name' => 'Relocating from USA to France'
            ),
            'audience' => array(
                '@type' => 'Audience',
                'audienceType' => 'American expatriates and US citizens planning to move to France'
            )
        );
        
        // 4. FAQPage schema - most important for rich snippets
        $schema['@graph'][] = self::build_faq_schema();
        
        // 5. HowTo schema for property purchase process
        $schema['@graph'][] = self::build_howto_schema();
        
        // 6. Article/Guide schema
        $schema['@graph'][] = array(
            '@type' => 'Article',
            '@id' => $page_url . '#article',
            'headline' => 'Complete Guide to Moving to France from the USA',
            'description' => 'Everything American expats need to know about relocating to France: visas, property, healthcare, taxes, and settling in.',
            'author' => array(
                '@id' => $site_url . '/#organization'
            ),
            'publisher' => array(
                '@id' => $site_url . '/#organization'
            ),
            'mainEntityOfPage' => array(
                '@id' => $page_url . '#webpage'
            ),
            'articleSection' => array(
                'Visas & Immigration',
                'Property Purchase',
                'Healthcare',
                'Taxes',
                'Driving',
                'Banking',
                'Shipping & Pets'
            ),
            'keywords' => implode(', ', array_slice(self::$long_tail_keywords, 0, 15))
        );
        
        return $schema;
    }
    
    /**
     * Build FAQ schema from knowledge base
     */
    private static function build_faq_schema() {
        $faqs = array(
            array(
                'question' => 'What visa do I need to move to France from the USA?',
                'answer' => 'US citizens need a Long-Stay Visa (visa de long séjour) to live in France for more than 90 days. Common types include the Visitor Visa (non-working), Work Visa (salarié), Talent Passport for skilled workers, and retirement/independent means visas. Apply through the French consulate serving your US residence.'
            ),
            array(
                'question' => 'Can Americans buy property in France?',
                'answer' => 'Yes, US citizens can freely purchase property in France with no restrictions. The process involves making an offer (offre d\'achat), signing a preliminary contract (compromis de vente) with a 10-day cooling-off period, and completing the sale through a notaire within 2-3 months. Non-residents can obtain French mortgages at 70-80% loan-to-value.'
            ),
            array(
                'question' => 'How does French healthcare work for American expats?',
                'answer' => 'After 3 months of stable residence, expats can enroll in PUMA (Protection Universelle Maladie), France\'s universal healthcare system. You\'ll receive a Carte Vitale that covers approximately 70% of medical costs. Most expats also obtain a mutuelle (supplemental insurance) for full coverage. Private insurance is required during the initial visa period.'
            ),
            array(
                'question' => 'What is the 183-day rule for French tax residency?',
                'answer' => 'If you spend 183 days or more in France during a calendar year, you are generally considered a French tax resident and must declare worldwide income to French authorities. The US-France tax treaty prevents double taxation, but you must still file US taxes as a citizen. FBAR and FATCA reporting requirements also apply for foreign accounts.'
            ),
            array(
                'question' => 'Can I exchange my US driver\'s license in France?',
                'answer' => 'Only residents from specific US states with reciprocal agreements can exchange their license directly. These include: Arkansas, Colorado, Connecticut, Delaware, Florida, Illinois, Iowa, Kansas, Kentucky, Maryland, Massachusetts, Michigan, New Hampshire, Ohio, Oklahoma, Pennsylvania, South Carolina, Texas, Virginia, and Wisconsin. Others must take the French driving test.'
            ),
            array(
                'question' => 'How much money do I need to move to France?',
                'answer' => 'For a long-stay visa, you must demonstrate financial resources equivalent to the French minimum wage (SMIC), approximately €1,400-1,700 per month. This can come from income, pension, or savings. Additionally, budget €3,000-6,000 for moving costs, plus deposits and initial setup expenses.'
            ),
            array(
                'question' => 'Can I work in France on a visitor visa?',
                'answer' => 'No, the Visitor Visa (visa de long séjour visiteur) explicitly prohibits any professional activity in France. You must sign an attestation declaring you will not work. If you want to work, you need a work visa (visa salarié) or Talent Passport, which requires employer sponsorship or meeting specific criteria.'
            ),
            array(
                'question' => 'How do I open a French bank account as an American?',
                'answer' => 'Opening a French bank account as a US citizen can be challenging due to FATCA compliance requirements. Major banks like BNP Paribas, Crédit Agricole, and Société Générale accept American clients. You\'ll need your passport, visa, proof of address (even temporary), and proof of income. Some online banks like Boursorama also accept US citizens.'
            ),
        );
        
        $faq_schema = array(
            '@type' => 'FAQPage',
            '@id' => get_permalink() . '#faq',
            'mainEntity' => array()
        );
        
        foreach ($faqs as $faq) {
            $faq_schema['mainEntity'][] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                )
            );
        }
        
        return $faq_schema;
    }
    
    /**
     * Build HowTo schema for property purchase
     */
    private static function build_howto_schema() {
        return array(
            '@type' => 'HowTo',
            '@id' => get_permalink() . '#howto-property',
            'name' => 'How to Buy Property in France as an American',
            'description' => 'Step-by-step guide to purchasing real estate in France as a US citizen, from finding a property to completing the sale.',
            'totalTime' => 'P3M',
            'estimatedCost' => array(
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => '7-10% of purchase price (notaire fees and taxes)'
            ),
            'step' => array(
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Find a Property',
                    'text' => 'Search properties through French real estate websites (SeLoger, LeBonCoin) or work with a local agent (agent immobilier). Consider location, condition, and renovation needs.',
                    'position' => 1
                ),
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Make an Offer',
                    'text' => 'Submit a written offer (offre d\'achat) specifying the price and conditions. This can be done directly or through your agent.',
                    'position' => 2
                ),
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Sign the Compromis de Vente',
                    'text' => 'Sign the preliminary sales agreement with the notaire. You have a 10-day cooling-off period (délai de rétractation) to withdraw without penalty.',
                    'position' => 3
                ),
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Arrange Financing',
                    'text' => 'If needed, secure a French mortgage. Non-residents typically receive 70-80% loan-to-value. Include a financing condition (condition suspensive) in your contract.',
                    'position' => 4
                ),
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Complete Due Diligence',
                    'text' => 'The notaire conducts title searches and verifies property diagnostics (DPE, lead, asbestos, etc.). This takes 2-3 months.',
                    'position' => 5
                ),
                array(
                    '@type' => 'HowToStep',
                    'name' => 'Sign the Acte de Vente',
                    'text' => 'Complete the sale at the notaire\'s office by signing the final deed (acte authentique). Pay the balance and receive the keys.',
                    'position' => 6
                )
            )
        );
    }
    
    /**
     * Output meta tags
     */
    public static function output_meta_tags() {
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'france_relocation_assistant')) {
            return;
        }
        
        // Don't output if Yoast or Rank Math is active (they handle this)
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return;
        }
        
        $description = 'Free comprehensive guide for Americans moving to France. Expert information on visas, property purchase, healthcare, taxes, driving licenses, and settling in. Updated weekly from official French sources.';
        $keywords = implode(', ', array_merge(
            array_slice(self::$primary_keywords, 0, 5),
            array_slice(self::$long_tail_keywords, 0, 10)
        ));
        
        echo "\n<!-- France Relocation Assistant Meta Tags -->\n";
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large" />' . "\n";
        echo '<meta name="author" content="Relo2France" />' . "\n";
    }
    
    /**
     * Output Open Graph and Twitter Card tags
     */
    public static function output_open_graph() {
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'france_relocation_assistant')) {
            return;
        }
        
        // Don't output if Yoast or Rank Math is active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return;
        }
        
        $title = 'France Relocation Assistant - Complete Guide for Americans Moving to France';
        $description = 'Free comprehensive guide for US citizens relocating to France. Covers visas, property, healthcare, taxes, and more.';
        $url = get_permalink();
        $site_name = get_bloginfo('name');
        
        echo "\n<!-- Open Graph Tags -->\n";
        echo '<meta property="og:locale" content="en_US" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
        
        echo "\n<!-- Twitter Card Tags -->\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
    }
    
    /**
     * Filter document title
     */
    public static function filter_title($title_parts) {
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'france_relocation_assistant')) {
            return $title_parts;
        }
        
        // Don't override if SEO plugin is active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return $title_parts;
        }
        
        $title_parts['title'] = 'France Relocation Guide for Americans';
        $title_parts['tagline'] = 'Visas, Property, Healthcare & More';
        
        return $title_parts;
    }
    
    /**
     * Output canonical URL
     */
    public static function output_canonical() {
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'france_relocation_assistant')) {
            return;
        }
        
        // Don't output if Yoast or Rank Math is active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return;
        }
        
        echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
    }
    
    /**
     * Get target keywords for content optimization
     */
    public static function get_keywords() {
        return array(
            'primary' => self::$primary_keywords,
            'long_tail' => self::$long_tail_keywords
        );
    }
    
    /**
     * Generate SEO-friendly content suggestions
     */
    public static function get_content_suggestions() {
        return array(
            'blog_post_ideas' => array(
                '10 Things I Wish I Knew Before Moving to France from the USA',
                'Complete Guide to French Long-Stay Visas for Americans (2025)',
                'How Much Does It Really Cost to Move to France?',
                'Buying Property in France: An American\'s Step-by-Step Guide',
                'French Healthcare for Expats: Understanding PUMA and Carte Vitale',
                'US-France Tax Treaty Explained for American Expats',
                'Best Regions in France for American Retirees',
                'Opening a French Bank Account as an American: What You Need to Know',
                'The 183-Day Rule: Understanding French Tax Residency',
                'Exchanging Your US Driver\'s License in France',
            ),
            'category_structure' => array(
                'visas-immigration' => 'Visas & Immigration',
                'property-purchase' => 'Buying Property in France',
                'healthcare-france' => 'French Healthcare System',
                'taxes-expats' => 'Taxes for American Expats',
                'living-in-france' => 'Living in France',
                'moving-logistics' => 'Moving & Logistics',
            ),
            'internal_linking' => array(
                'Link visa pages to property pages (visa needed to buy)',
                'Link healthcare to visa pages (insurance requirements)',
                'Link tax pages to 183-day counter tool',
                'Link property to banking (mortgage information)',
            )
        );
    }
}

// Initialize SEO module
FRA_SEO::init();
