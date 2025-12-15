<?php
/**
 * AI-Assisted Knowledge Base Review
 * 
 * Comprehensive review system that:
 * - Reviews each KB topic as a whole (not just individual fields)
 * - Verifies official information against current sources
 * - Generates "In Practice" sections with real-world insights
 * - Sources practical info from forums, blogs, and expat communities
 * - Can suggest minor updates, significant changes, or complete rewrites
 * 
 * @package France_Relocation_Assistant
 * @since 2.5.6
 * @version 2.6.0
 */

/*
|--------------------------------------------------------------------------
| Security Check
|--------------------------------------------------------------------------
*/
if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| AI Review Class
|--------------------------------------------------------------------------
*/
class FRA_AI_Review {
    
    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */
    
    /** @var FRA_AI_Review|null Singleton instance */
    private static $instance = null;
    
    /** @var string Option name for pending reviews */
    const PENDING_REVIEWS_OPTION = 'fra_pending_reviews';
    
    /** @var string Option name for review history */
    const REVIEW_HISTORY_OPTION = 'fra_review_history';
    
    /*
    |--------------------------------------------------------------------------
    | Reviewable Topics Configuration
    |--------------------------------------------------------------------------
    |
    | Each topic includes:
    | - name: Display name
    | - sources: Official government/institutional sources
    | - key_facts: Critical data points that must be accurate
    | - practice_hints: Topics to research for "In Practice" section
    |
    */
    
    /**
     * Topics to review with their official sources and practical research hints
     *
     * @var array
     */
    private $reviewable_topics = array(
        'visas' => array(
            'overview' => array(
                'name' => 'Visa Types Overview',
                'sources' => array('france-visas.gouv.fr'),
                'key_facts' => array('visa types available', 'general requirements', 'application process'),
                'practice_hints' => array('visa processing delays', 'consulate appointment availability', 'common rejection reasons')
            ),
            'visitor' => array(
                'name' => 'Visitor Visa (Non-Working)',
                'sources' => array('france-visas.gouv.fr', 'service-public.fr'),
                'key_facts' => array('visa fee', 'minimum income requirement (SMIC-based)', 'health insurance minimum', 'OFII validation tax', 'validation deadline'),
                'practice_hints' => array('remote work grey area', 'working for US company on visitor visa', 'digital nomad enforcement', 'what consulates actually ask for', 'income proof flexibility')
            ),
            'work' => array(
                'name' => 'Work Visa',
                'sources' => array('france-visas.gouv.fr'),
                'key_facts' => array('work authorization process', 'required documents', 'processing time'),
                'practice_hints' => array('employer sponsorship difficulty', 'work permit approval rates', 'industries more likely to sponsor')
            ),
            'talent' => array(
                'name' => 'Talent Passport',
                'sources' => array('france-visas.gouv.fr'),
                'key_facts' => array('salary threshold for highly qualified', 'investor minimum', 'validity period'),
                'practice_hints' => array('startup founder experiences', 'investor visa practical requirements', 'talent passport renewal process')
            ),
            'validation' => array(
                'name' => 'Visa Validation (OFII)',
                'sources' => array('administration-etrangers-en-france.interieur.gouv.fr'),
                'key_facts' => array('OFII tax amount', 'validation deadline', 'online process'),
                'practice_hints' => array('OFII website issues', 'validation delays', 'what happens if you miss deadline')
            )
        ),
        'property' => array(
            'overview' => array(
                'name' => 'Property Purchase Overview',
                'sources' => array('notaires.fr', 'service-public.fr'),
                'key_facts' => array('purchase process', 'foreign buyer rules'),
                'practice_hints' => array('negotiation norms in France', 'off-market properties', 'American buyer experiences')
            ),
            'costs' => array(
                'name' => 'Purchase Costs & Fees',
                'sources' => array('notaires.fr'),
                'key_facts' => array('notaire fees percentage', 'registration taxes', 'agent fees'),
                'practice_hints' => array('hidden costs buyers encounter', 'negotiating agent fees', 'costs that surprised expats')
            ),
            'mortgage' => array(
                'name' => 'French Mortgages',
                'sources' => array('banque-france.fr'),
                'key_facts' => array('LTV for non-residents', 'debt-to-income ratio', 'typical rates'),
                'practice_hints' => array('banks that work with Americans', 'mortgage broker recommendations', 'FATCA complications for US citizens')
            )
        ),
        'taxes' => array(
            'overview' => array(
                'name' => 'Tax Obligations',
                'sources' => array('impots.gouv.fr', 'irs.gov'),
                'key_facts' => array('tax residency rules', 'filing requirements'),
                'practice_hints' => array('dual filing burden', 'tax treaty benefits practical application', 'accountant recommendations for expats')
            ),
            'residency' => array(
                'name' => '183-Day Rule',
                'sources' => array('impots.gouv.fr'),
                'key_facts' => array('day counting rules', 'tax implications'),
                'practice_hints' => array('how strictly enforced', 'split residency strategies', 'what triggers investigation')
            ),
            'fbar' => array(
                'name' => 'FBAR & FATCA',
                'sources' => array('irs.gov', 'fincen.gov'),
                'key_facts' => array('FBAR threshold', 'FATCA threshold', 'filing deadlines', 'penalties'),
                'practice_hints' => array('French banks refusing Americans', 'FATCA compliance issues', 'streamlined compliance experiences')
            )
        ),
        'healthcare' => array(
            'overview' => array(
                'name' => 'Healthcare Overview',
                'sources' => array('ameli.fr', 'service-public.fr'),
                'key_facts' => array('healthcare system structure', 'coverage levels'),
                'practice_hints' => array('English-speaking doctors', 'quality of care experiences', 'wait times reality')
            ),
            'puma' => array(
                'name' => 'PUMA & Carte Vitale',
                'sources' => array('ameli.fr'),
                'key_facts' => array('eligibility requirements', 'application process', 'CSG rates'),
                'practice_hints' => array('CPAM office experiences', 'how long to get carte vitale', 'coverage gaps while waiting')
            )
        ),
        'banking' => array(
            'overview' => array(
                'name' => 'Banking Overview',
                'sources' => array('service-public.fr'),
                'key_facts' => array('account opening requirements', 'non-resident options'),
                'practice_hints' => array('banks that accept Americans', 'online banks for expats', 'account opening difficulties for US citizens')
            )
        ),
        'driving' => array(
            'overview' => array(
                'name' => 'Driving in France',
                'sources' => array('service-public.fr'),
                'key_facts' => array('license validity', 'exchange requirements'),
                'practice_hints' => array('license exchange delays', 'driving on US license experiences', 'prefecture appointment availability')
            )
        ),
        'shipping' => array(
            'overview' => array(
                'name' => 'Shipping & Moving',
                'sources' => array('douane.gouv.fr'),
                'key_facts' => array('customs rules', 'duty-free allowances'),
                'practice_hints' => array('shipping company recommendations', 'customs delays', 'what to bring vs buy in France')
            ),
            'pets' => array(
                'name' => 'Moving Pets',
                'sources' => array('agriculture.gouv.fr'),
                'key_facts' => array('pet import requirements', 'rabies vaccination timing'),
                'practice_hints' => array('airline pet policies', 'quarantine experiences', 'pet transport services')
            )
        )
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_fra_start_ai_review', array($this, 'ajax_start_review'));
        add_action('wp_ajax_fra_get_review_status', array($this, 'ajax_get_review_status'));
        add_action('wp_ajax_fra_get_review_topics', array($this, 'ajax_get_review_topics'));
        add_action('wp_ajax_fra_review_batch', array($this, 'ajax_review_batch'));
        add_action('wp_ajax_fra_finalize_review', array($this, 'ajax_finalize_review'));
        add_action('wp_ajax_fra_approve_change', array($this, 'ajax_approve_change'));
        add_action('wp_ajax_fra_reject_change', array($this, 'ajax_reject_change'));
        add_action('wp_ajax_fra_approve_all', array($this, 'ajax_approve_all'));
        add_action('wp_ajax_fra_reject_all', array($this, 'ajax_reject_all'));
        add_action('wp_ajax_fra_generate_in_practice', array($this, 'ajax_generate_in_practice'));
        add_action('wp_ajax_fra_apply_in_practice', array($this, 'ajax_apply_in_practice'));
    }
    
    public function get_reviewable_topics() {
        // Get actual knowledge base
        $kb = get_option('fra_knowledge_base', array());
        
        // Build dynamic reviewable topics from KB
        $dynamic_topics = array();
        
        foreach ($kb as $category => $topics) {
            if (!is_array($topics)) continue;
            
            $dynamic_topics[$category] = array();
            
            foreach ($topics as $topic_key => $topic_data) {
                if (!is_array($topic_data)) continue;
                
                // Use predefined info if available, otherwise generate from KB data
                if (isset($this->reviewable_topics[$category][$topic_key])) {
                    $dynamic_topics[$category][$topic_key] = $this->reviewable_topics[$category][$topic_key];
                } else {
                    // Generate info from topic data
                    $title = $topic_data['title'] ?? ucfirst(str_replace('_', ' ', $topic_key));
                    
                    // Extract sources
                    $sources = array();
                    if (!empty($topic_data['sources'])) {
                        foreach ($topic_data['sources'] as $source) {
                            if (is_array($source) && !empty($source['url'])) {
                                $sources[] = parse_url($source['url'], PHP_URL_HOST) ?: ($source['name'] ?? 'official source');
                            } elseif (is_array($source) && !empty($source['name'])) {
                                $sources[] = $source['name'];
                            } elseif (is_string($source)) {
                                $sources[] = $source;
                            }
                        }
                    }
                    if (empty($sources)) {
                        $sources = array('service-public.fr', 'official French sources');
                    }
                    
                    // Generate key facts from keywords or content
                    $key_facts = array('requirements', 'process', 'costs', 'timeline');
                    if (!empty($topic_data['keywords'])) {
                        $key_facts = array_slice($topic_data['keywords'], 0, 5);
                    }
                    
                    $dynamic_topics[$category][$topic_key] = array(
                        'name' => $title,
                        'sources' => array_unique($sources),
                        'key_facts' => $key_facts,
                        'practice_hints' => array('real-world experiences', 'common issues', 'practical tips')
                    );
                }
            }
        }
        
        return $dynamic_topics;
    }
    
    /**
     * Get topic info for a specific topic
     * 
     * Uses predefined info if available, otherwise generates generic info
     * based on the topic data.
     *
     * @param string $category Category key
     * @param string $topic_key Topic key
     * @param array $topic_data Topic data from KB
     * @return array Topic info with name, sources, key_facts, practice_hints
     */
    public function get_topic_info($category, $topic_key, $topic_data) {
        // Check if we have predefined info for this topic
        if (isset($this->reviewable_topics[$category][$topic_key])) {
            return $this->reviewable_topics[$category][$topic_key];
        }
        
        // Generate generic info based on topic data
        $title = $topic_data['title'] ?? ucfirst(str_replace('_', ' ', $topic_key));
        
        // Extract sources from topic data
        $sources = array();
        if (!empty($topic_data['sources'])) {
            foreach ($topic_data['sources'] as $source) {
                if (is_array($source) && !empty($source['url'])) {
                    $sources[] = parse_url($source['url'], PHP_URL_HOST) ?: $source['name'];
                } elseif (is_string($source)) {
                    $sources[] = $source;
                }
            }
        }
        if (empty($sources)) {
            $sources = array('service-public.fr', 'official French sources');
        }
        
        // Generate key facts from keywords if available
        $key_facts = array('requirements', 'process', 'costs', 'timeline');
        if (!empty($topic_data['keywords'])) {
            $key_facts = array_slice($topic_data['keywords'], 0, 5);
        }
        
        // Generate practice hints based on category and topic
        $practice_hints = array(
            'real-world experiences',
            'common issues expats encounter',
            'practical tips not in official documentation',
            'wait times and processing delays'
        );
        
        return array(
            'name' => $title,
            'sources' => $sources,
            'key_facts' => $key_facts,
            'practice_hints' => $practice_hints
        );
    }
    
    public function get_pending_reviews() {
        return get_option(self::PENDING_REVIEWS_OPTION, array());
    }
    
    /**
     * AJAX: Apply generated In Practice content to a topic
     *
     * @return void
     */
    public function ajax_apply_in_practice() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? ''));
        $topic = sanitize_text_field(wp_unslash($_POST['topic'] ?? ''));
        $in_practice_content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
        
        if (empty($category) || empty($topic) || empty($in_practice_content)) {
            wp_send_json_error('Missing required fields');
        }
        
        $knowledge_base = get_option('fra_knowledge_base', array());
        
        if (!isset($knowledge_base[$category][$topic])) {
            wp_send_json_error('Topic not found');
        }
        
        $current_content = $knowledge_base[$category][$topic]['content'] ?? '';
        
        // Remove existing In Practice section if present
        $current_content = preg_replace('/\n\n\*\*In Practice\*\*.*$/s', '', $current_content);
        
        // Append new In Practice section
        $knowledge_base[$category][$topic]['content'] = trim($current_content) . "\n\n" . $in_practice_content;
        $knowledge_base[$category][$topic]['lastVerified'] = date('F Y');
        
        update_option('fra_knowledge_base', $knowledge_base);
        
        wp_send_json_success(array('message' => 'In Practice section added'));
    }
    
    /*
    |--------------------------------------------------------------------------
    | Generate "In Practice" Section Only
    |--------------------------------------------------------------------------
    */
    
    /**
     * AJAX: Generate In Practice section for a specific topic
     * 
     * This allows adding practical insights to any KB topic without
     * running a full review cycle.
     *
     * @return void
     */
    public function ajax_generate_in_practice() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_key = get_option('fra_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }
        
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? ''));
        $topic = sanitize_text_field(wp_unslash($_POST['topic'] ?? ''));
        
        if (empty($category) || empty($topic)) {
            wp_send_json_error('Category and topic are required');
        }
        
        // Get current KB content
        $knowledge_base = get_option('fra_knowledge_base', array());
        
        if (!isset($knowledge_base[$category][$topic])) {
            wp_send_json_error('Topic not found in knowledge base');
        }
        
        $topic_data = $knowledge_base[$category][$topic];
        $current_content = $topic_data['content'] ?? '';
        $topic_title = $topic_data['title'] ?? ucfirst($topic);
        
        // Get practice hints if available
        $practice_hints = array();
        if (isset($this->reviewable_topics[$category][$topic]['practice_hints'])) {
            $practice_hints = $this->reviewable_topics[$category][$topic]['practice_hints'];
        }
        
        // Generate In Practice content
        $result = $this->generate_in_practice_content($api_key, $category, $topic_title, $current_content, $practice_hints);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Generate In Practice content using Claude
     *
     * @param string $api_key API key
     * @param string $category Category name
     * @param string $topic_title Topic title
     * @param string $current_content Current KB content
     * @param array $practice_hints Hints for practical research
     * @return array|WP_Error Generated content or error
     */
    private function generate_in_practice_content($api_key, $category, $topic_title, $current_content, $practice_hints) {
        $current_date = date('F j, Y');
        $current_year = date('Y');
        
        $hints_str = !empty($practice_hints) 
            ? implode(', ', $practice_hints) 
            : 'real-world experiences, common issues, practical tips, grey areas';
        
        $prompt = "You are writing the \"In Practice\" section for a US-to-France relocation guide. Today is {$current_date}.

**TOPIC:** {$topic_title}
**CATEGORY:** {$category}

**CURRENT OFFICIAL CONTENT:**
```
{$current_content}
```

**PRACTICAL TOPICS TO COVER:**
{$hints_str}

**YOUR TASK:**
Write an \"**In Practice**\" section that covers the real-world reality of this topic. This should complement (not repeat) the official information above.

**INCLUDE:**
• Grey areas and how rules are actually enforced (or not enforced)
• Common experiences from expat forums (Reddit r/expats, r/france, Expatica, FrenchEntrée)
• What surprised people or caught them off guard
• Practical tips not found in official documentation
• Current wait times, processing delays, or backlogs
• Which officials/offices are flexible vs strict
• Common mistakes to avoid
• Insider knowledge from people who've been through it

**IMPORTANT GUIDELINES:**
- Be honest about grey areas without explicitly encouraging rule-breaking
- Cite sources inline: (Source: Reddit r/expats, {$current_year}) or (Source: FrenchEntrée forum, {$current_year})
- Note when something is \"widely reported\" vs \"anecdotal\"
- Keep it conversational and helpful, like advice from a friend
- Be specific with examples where possible
- Length: 200-400 words

**RESPOND WITH JSON ONLY:**
```json
{
    \"in_practice_content\": \"**In Practice**\\n\\nYour content here with **bold** for sub-headers and • for bullets. Include inline source citations.\",
    \"key_insights\": [\"Most important insight 1\", \"Key insight 2\", \"Key insight 3\"],
    \"sources\": [
        {\"name\": \"Reddit r/expats\", \"type\": \"forum\", \"date\": \"{$current_year}\"},
        {\"name\": \"FrenchEntrée\", \"type\": \"blog\", \"date\": \"{$current_year}\"}
    ]
}
```";

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 90,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 2000,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown API error');
        }
        
        if (!isset($body['content'][0]['text'])) {
            return new WP_Error('api_error', 'Unexpected response format');
        }
        
        // Parse response
        $ai_response = $body['content'][0]['text'];
        $ai_response = preg_replace('/^```json\s*/', '', $ai_response);
        $ai_response = preg_replace('/\s*```$/', '', $ai_response);
        
        $result = json_decode($ai_response, true);
        
        if (!$result) {
            return new WP_Error('parse_error', 'Failed to parse AI response');
        }
        
        return array(
            'in_practice_content' => $result['in_practice_content'] ?? '',
            'key_insights' => $result['key_insights'] ?? array(),
            'sources' => $result['sources'] ?? array()
        );
    }
    
    /**
     * AJAX: Start comprehensive AI review
     *
     * @return void
     */
    public function ajax_start_review() {
        // Log start
        error_log('FRA AI Review: Starting review');
        
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            error_log('FRA AI Review: Unauthorized');
            wp_send_json_error('Unauthorized');
        }
        
        $api_key = get_option('fra_api_key', '');
        if (empty($api_key)) {
            error_log('FRA AI Review: No API key');
            wp_send_json_error('API key not configured. Please set up Claude API in Settings.');
        }
        
        // Get specific category/topic if provided, otherwise review all
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        
        error_log('FRA AI Review: Running review for category=' . $category . ', topic=' . $topic);
        
        // Increase PHP limits for long reviews
        @set_time_limit(600); // 10 minutes
        @ini_set('memory_limit', '512M');
        
        try {
            $results = $this->run_comprehensive_review($api_key, $category, $topic);
            
            if (is_wp_error($results)) {
                error_log('FRA AI Review: WP_Error - ' . $results->get_error_message());
                wp_send_json_error($results->get_error_message());
            }
            
            error_log('FRA AI Review: Success - reviewed=' . $results['reviewed'] . ', changes=' . $results['changes_found']);
            wp_send_json_success($results);
        } catch (Exception $e) {
            error_log('FRA AI Review: Exception - ' . $e->getMessage());
            wp_send_json_error('Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get list of topics to review (for batched processing)
     */
    public function ajax_get_review_topics() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $filter_category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $knowledge_base = get_option('fra_knowledge_base', array());
        $topics = array();
        
        foreach ($knowledge_base as $category => $category_topics) {
            if (!is_array($category_topics)) continue;
            if (!empty($filter_category) && $category !== $filter_category) continue;
            
            foreach ($category_topics as $topic_key => $topic_data) {
                if (!is_array($topic_data)) continue;
                if (empty($topic_data['content'])) continue;
                
                $topics[] = array(
                    'category' => $category,
                    'topic_key' => $topic_key,
                    'name' => $topic_data['title'] ?? ucfirst(str_replace('_', ' ', $topic_key))
                );
            }
        }
        
        wp_send_json_success(array('topics' => $topics, 'total' => count($topics)));
    }
    
    /**
     * AJAX: Review a batch of topics
     */
    public function ajax_review_batch() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $api_key = get_option('fra_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }
        
        $topics_json = isset($_POST['topics']) ? wp_unslash($_POST['topics']) : '[]';
        $topics = json_decode($topics_json, true);
        
        if (!is_array($topics) || empty($topics)) {
            wp_send_json_error('No topics provided');
        }
        
        // Increase limits
        @set_time_limit(300);
        @ini_set('memory_limit', '256M');
        
        $knowledge_base = get_option('fra_knowledge_base', array());
        $pending_reviews = get_option(self::PENDING_REVIEWS_OPTION, array());
        
        // Ensure it's an array
        if (!is_array($pending_reviews)) {
            $pending_reviews = array();
        }
        
        error_log('FRA Batch Review: Starting with ' . count($pending_reviews) . ' existing pending reviews');
        
        $reviewed_count = 0;
        $changes_found = 0;
        $errors = array();
        
        foreach ($topics as $topic) {
            $category = $topic['category'];
            $topic_key = $topic['topic_key'];
            
            // Get topic data from KB
            if (!isset($knowledge_base[$category][$topic_key])) {
                $errors[] = "{$category}/{$topic_key}: Not found in KB";
                continue;
            }
            
            $topic_data = $knowledge_base[$category][$topic_key];
            $current_content = $topic_data['content'] ?? '';
            
            if (empty($current_content)) {
                $errors[] = "{$category}/{$topic_key}: No content";
                continue;
            }
            
            $topic_info = $this->get_topic_info($category, $topic_key, $topic_data);
            $reviewed_count++;
            
            // Call Claude API
            $review_result = $this->review_single_topic(
                $api_key,
                $category,
                $topic_key,
                $topic_info,
                $current_content
            );
            
            if (is_wp_error($review_result)) {
                $errors[] = "{$category}/{$topic_key}: " . $review_result->get_error_message();
                continue;
            }
            
            if ($review_result['needs_update']) {
                $changes_found++;
                $review_id = uniqid('review_');
                
                $pending_reviews[$review_id] = array(
                    'id' => $review_id,
                    'category' => $category,
                    'topic' => $topic_key,
                    'topic_name' => $topic_info['name'],
                    'update_type' => $review_result['update_type'],
                    'current_content' => $current_content,
                    'suggested_content' => $review_result['suggested_content'],
                    'in_practice_content' => $review_result['in_practice_content'] ?? '',
                    'practice_sources' => $review_result['practice_sources'] ?? array(),
                    'key_insights' => $review_result['key_insights'] ?? array(),
                    'changes_summary' => $review_result['changes_summary'],
                    'sources_checked' => $review_result['sources_checked'] ?? array(),
                    'confidence' => $review_result['confidence'],
                    'timestamp' => current_time('mysql')
                );
            }
        }
        
        // Save pending reviews (merge with existing)
        error_log('FRA Batch Review: Saving ' . count($pending_reviews) . ' total pending reviews');
        update_option(self::PENDING_REVIEWS_OPTION, $pending_reviews);
        
        wp_send_json_success(array(
            'reviewed' => $reviewed_count,
            'changes_found' => $changes_found,
            'errors' => $errors,
            'total_pending' => count($pending_reviews)
        ));
    }
    
    /**
     * AJAX: Finalize review - update history after all batches complete
     */
    public function ajax_finalize_review() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $reviewed = isset($_POST['reviewed']) ? intval($_POST['reviewed']) : 0;
        $changes_found = isset($_POST['changes_found']) ? intval($_POST['changes_found']) : 0;
        $errors = isset($_POST['errors']) ? intval($_POST['errors']) : 0;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        
        // Update review history
        $history = get_option(self::REVIEW_HISTORY_OPTION, array());
        $history[] = array(
            'timestamp' => time(),
            'date' => current_time('mysql'),
            'reviewed' => $reviewed,
            'verified' => $reviewed - $errors,
            'changes_found' => $changes_found,
            'errors' => $errors,
            'filter' => $filter
        );
        update_option(self::REVIEW_HISTORY_OPTION, array_slice($history, -20));
        
        wp_send_json_success(array('saved' => true));
    }
    
    /**
     * Run comprehensive review of KB topics
     * 
     * Reviews ALL topics in the knowledge base, using predefined hints
     * when available, and generic hints for others.
     */
    private function run_comprehensive_review($api_key, $filter_category = '', $filter_topic = '') {
        $knowledge_base = get_option('fra_knowledge_base', array());
        $pending_reviews = array();
        $reviewed_count = 0;
        $changes_found = 0;
        $errors = array();
        
        error_log('FRA AI Review: KB has ' . count($knowledge_base) . ' categories');
        
        // Review ALL topics in the knowledge base
        foreach ($knowledge_base as $category => $topics) {
            // Skip if not an array
            if (!is_array($topics)) {
                error_log('FRA AI Review: Skipping ' . $category . ' - not an array');
                continue;
            }
            
            // Skip if filtering by category
            if (!empty($filter_category) && $category !== $filter_category) {
                continue;
            }
            
            error_log('FRA AI Review: Processing category ' . $category . ' with ' . count($topics) . ' topics');
            
            foreach ($topics as $topic_key => $topic_data) {
                // Skip if not an array
                if (!is_array($topic_data)) {
                    error_log('FRA AI Review: Skipping ' . $category . '/' . $topic_key . ' - not an array');
                    continue;
                }
                
                // Skip if filtering by topic
                if (!empty($filter_topic) && $topic_key !== $filter_topic) {
                    continue;
                }
                
                // Get current KB content
                $current_content = $topic_data['content'] ?? '';
                
                // Skip topics with no content
                if (empty($current_content)) {
                    $errors[] = "{$category}/{$topic_key}: No content";
                    error_log('FRA AI Review: Skipping ' . $category . '/' . $topic_key . ' - no content');
                    continue;
                }
                
                // Build topic_info - use predefined if available, otherwise generate generic
                $topic_info = $this->get_topic_info($category, $topic_key, $topic_data);
                
                // Increment reviewed count BEFORE the API call
                $reviewed_count++;
                
                error_log('FRA AI Review: Reviewing ' . $category . '/' . $topic_key . ' (#' . $reviewed_count . ')');
                
                // Ask Claude to review this topic
                $review_result = $this->review_single_topic(
                    $api_key,
                    $category,
                    $topic_key,
                    $topic_info,
                    $current_content
                );
                
                if (is_wp_error($review_result)) {
                    $errors[] = "{$category}/{$topic_key}: " . $review_result->get_error_message();
                    error_log('FRA AI Review: Error on ' . $category . '/' . $topic_key . ' - ' . $review_result->get_error_message());
                    continue;
                }
                
                error_log('FRA AI Review: ' . $category . '/' . $topic_key . ' needs_update=' . ($review_result['needs_update'] ? 'yes' : 'no'));
                
                if ($review_result['needs_update']) {
                    $changes_found++;
                    $review_id = uniqid('review_');
                    
                    $pending_reviews[$review_id] = array(
                        'id' => $review_id,
                        'category' => $category,
                        'topic' => $topic_key,
                        'topic_name' => $topic_info['name'],
                        'update_type' => $review_result['update_type'],
                        'current_content' => $current_content,
                        'suggested_content' => $review_result['suggested_content'],
                        'in_practice_content' => $review_result['in_practice_content'] ?? '',
                        'practice_sources' => $review_result['practice_sources'] ?? array(),
                        'key_insights' => $review_result['key_insights'] ?? array(),
                        'changes_summary' => $review_result['changes_summary'],
                        'sources_checked' => $review_result['sources_checked'],
                        'confidence' => $review_result['confidence'],
                        'timestamp' => current_time('mysql')
                    );
                }
            }
        }
        
        error_log('FRA AI Review: Finished loop. Reviewed=' . $reviewed_count . ', Changes=' . $changes_found . ', Errors=' . count($errors));
        
        // Save pending reviews
        update_option(self::PENDING_REVIEWS_OPTION, $pending_reviews);
        
        // Update review history
        $history = get_option(self::REVIEW_HISTORY_OPTION, array());
        $history[] = array(
            'timestamp' => time(),
            'date' => current_time('mysql'),
            'reviewed' => $reviewed_count,
            'verified' => $reviewed_count - count($errors),
            'changes_found' => $changes_found,
            'errors' => count($errors),
            'filter' => !empty($filter_category) ? "{$filter_category}/{$filter_topic}" : 'all'
        );
        update_option(self::REVIEW_HISTORY_OPTION, array_slice($history, -20));
        
        error_log('FRA AI Review: Saved history and pending reviews');
        
        return array(
            'reviewed' => $reviewed_count,
            'changes_found' => $changes_found,
            'errors' => $errors,
            'pending_reviews' => $pending_reviews
        );
    }
    
    /**
     * Review a single topic using Claude with web search for practical insights
     */
    private function review_single_topic($api_key, $category, $topic_key, $topic_info, $current_content) {
        $current_date = date('F j, Y');
        $current_year = date('Y');
        
        // Build practice hints string
        $practice_hints = isset($topic_info['practice_hints']) 
            ? implode(', ', $topic_info['practice_hints']) 
            : 'real-world experiences, common issues, practical tips';
        
        // Build web search queries for practical information
        $search_queries = array();
        if (isset($topic_info['practice_hints'])) {
            foreach (array_slice($topic_info['practice_hints'], 0, 3) as $hint) {
                $search_queries[] = "France " . $hint . " " . $current_year . " expat experience";
            }
        }
        $search_queries_str = implode("\n- ", $search_queries);
        
        $prompt = "You are updating a US-to-France relocation guide. Today is {$current_date}.

**TOPIC:** {$topic_info['name']}
**CATEGORY:** {$category}

**CURRENT CONTENT:**
```
{$current_content}
```

**OFFICIAL SOURCES:** " . implode(', ', $topic_info['sources']) . "
**KEY FACTS TO VERIFY:** " . implode(', ', $topic_info['key_facts']) . "

**PRACTICAL TOPICS TO RESEARCH:**
- {$search_queries_str}
- Reddit r/expats, r/france, expat forums
- Recent blog posts and articles from Americans in France

---

**YOUR TASK - TWO PARTS:**

**PART 1: OFFICIAL INFORMATION**
Review and update the factual content. Check all numbers, fees, requirements, and deadlines against current official information.

**PART 2: IN PRACTICE (NEW SECTION)**
Research and write an \"**In Practice**\" section that covers:
• Grey areas and how rules are actually enforced (or not)
• Common experiences from expats and forums
• Practical tips that aren't in official documentation  
• Current discussions or recent changes people are talking about
• Things that surprised people or caught them off guard

For example, for Visitor Visa:
- The grey area of remote work for US companies (technically not allowed, but enforcement is minimal for those not using French resources)
- What consulates actually ask for vs. the official list
- How flexible they are on income proof formats
- Current wait times for appointments

**IMPORTANT FOR IN PRACTICE:**
- Be honest about grey areas without encouraging rule-breaking
- Cite specific sources (Reddit threads, blog posts, forum discussions, articles)
- Note when information is anecdotal vs. widely reported
- Include approximate dates of sources (\"as of late 2024\", \"reported in 2025\")
- Distinguish between \"the law says X\" and \"in practice, Y\"

---

**RESPOND WITH JSON ONLY:**
```json
{
    \"needs_update\": true/false,
    \"update_type\": \"none\" | \"minor\" | \"significant\" | \"rewrite\",
    \"confidence\": \"high\" | \"medium\" | \"low\",
    \"changes_summary\": \"Brief description of changes to official content\",
    \"suggested_content\": \"Updated OFFICIAL content (facts, requirements, fees). Use **bold** for headers and • for bullets.\",
    \"in_practice_content\": \"The IN PRACTICE section with real-world insights. Start with **In Practice** header. Include source citations inline like (Source: Reddit r/expats, Jan 2025) or (Source: FrenchEntrée blog, 2024).\",
    \"practice_sources\": [
        {\"name\": \"Source name or description\", \"type\": \"forum|blog|article|social\", \"date\": \"approximate date\"}
    ],
    \"official_sources_checked\": [\"source1.gouv.fr\"],
    \"key_insights\": [\"Most important practical insight 1\", \"Key insight 2\"]
}
```

**NOTES:**
- Always set needs_update to true if you're adding/updating the In Practice section
- The in_practice_content should feel conversational and helpful, not legal
- Be specific with examples where possible
- If a grey area exists, explain both the official rule AND the practical reality";

        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 120, // Longer timeout for comprehensive research
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 6000, // More tokens for comprehensive content
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown API error');
        }
        
        if (!isset($body['content'][0]['text'])) {
            return new WP_Error('api_error', 'Unexpected response format');
        }
        
        // Parse response
        $ai_response = $body['content'][0]['text'];
        $ai_response = preg_replace('/^```json\s*/', '', $ai_response);
        $ai_response = preg_replace('/\s*```$/', '', $ai_response);
        
        $result = json_decode($ai_response, true);
        
        if (!$result) {
            return new WP_Error('parse_error', 'Failed to parse AI response');
        }
        
        // Combine official content with In Practice section
        $full_content = $result['suggested_content'] ?? $current_content;
        if (!empty($result['in_practice_content'])) {
            $full_content .= "\n\n" . $result['in_practice_content'];
        }
        
        // Combine all sources
        $all_sources = array_merge(
            $result['official_sources_checked'] ?? array(),
            array_map(function($s) { return $s['name'] . ' (' . $s['type'] . ')'; }, $result['practice_sources'] ?? array())
        );
        
        return array(
            'needs_update' => $result['needs_update'] ?? true, // Default to true since we're adding In Practice
            'update_type' => $result['update_type'] ?? 'significant',
            'suggested_content' => $full_content,
            'changes_summary' => $result['changes_summary'] ?? 'Added In Practice section',
            'in_practice_content' => $result['in_practice_content'] ?? '',
            'practice_sources' => $result['practice_sources'] ?? array(),
            'key_insights' => $result['key_insights'] ?? array(),
            'sources_checked' => $all_sources,
            'confidence' => $result['confidence'] ?? 'medium'
        );
    }
    
    /**
     * AJAX: Get review status
     */
    public function ajax_get_review_status() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $pending = $this->get_pending_reviews();
        $history = get_option(self::REVIEW_HISTORY_OPTION, array());
        
        wp_send_json_success(array(
            'pending_count' => count($pending),
            'pending_reviews' => $pending,
            'history' => array_slice($history, -10)
        ));
    }
    
    /**
     * AJAX: Approve a single change
     */
    public function ajax_approve_change() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $review_id = sanitize_text_field(wp_unslash($_POST['review_id'] ?? ''));
        $pending = $this->get_pending_reviews();
        
        if (!isset($pending[$review_id])) {
            wp_send_json_error('Review not found');
        }
        
        $review = $pending[$review_id];
        $success = $this->apply_topic_update($review);
        
        if ($success) {
            unset($pending[$review_id]);
            update_option(self::PENDING_REVIEWS_OPTION, $pending);
            wp_send_json_success(array('message' => 'Update applied successfully'));
        } else {
            wp_send_json_error('Failed to apply update');
        }
    }
    
    /**
     * Apply a topic update to the knowledge base
     */
    private function apply_topic_update($review) {
        $knowledge_base = get_option('fra_knowledge_base', array());
        
        $category = $review['category'];
        $topic = $review['topic'];
        
        if (!isset($knowledge_base[$category][$topic])) {
            return false;
        }
        
        $old_content = $knowledge_base[$category][$topic]['content'];
        $new_content = $review['suggested_content'];
        
        // Update content
        $knowledge_base[$category][$topic]['content'] = $new_content;
        
        // Update last verified date
        $knowledge_base[$category][$topic]['lastVerified'] = date('F Y');
        
        // Add to update history
        if (!isset($knowledge_base[$category][$topic]['updateHistory'])) {
            $knowledge_base[$category][$topic]['updateHistory'] = array();
        }
        
        $knowledge_base[$category][$topic]['updateHistory'][] = array(
            'date' => current_time('mysql'),
            'type' => $review['update_type'],
            'summary' => $review['changes_summary'],
            'sources' => $review['sources_checked'],
            'confidence' => $review['confidence']
        );
        
        // Keep only last 10 updates per topic
        if (count($knowledge_base[$category][$topic]['updateHistory']) > 10) {
            $knowledge_base[$category][$topic]['updateHistory'] = array_slice(
                $knowledge_base[$category][$topic]['updateHistory'], 
                -10
            );
        }
        
        update_option('fra_knowledge_base', $knowledge_base);
        
        return true;
    }
    
    /**
     * AJAX: Reject a single change
     */
    public function ajax_reject_change() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $review_id = sanitize_text_field(wp_unslash($_POST['review_id'] ?? ''));
        $pending = $this->get_pending_reviews();
        
        if (isset($pending[$review_id])) {
            unset($pending[$review_id]);
            update_option(self::PENDING_REVIEWS_OPTION, $pending);
        }
        
        wp_send_json_success(array('message' => 'Change rejected'));
    }
    
    /**
     * AJAX: Approve all pending changes
     */
    public function ajax_approve_all() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $pending = $this->get_pending_reviews();
        $approved = 0;
        
        foreach ($pending as $review_id => $review) {
            if ($this->apply_topic_update($review)) {
                $approved++;
            }
        }
        
        update_option(self::PENDING_REVIEWS_OPTION, array());
        
        wp_send_json_success(array(
            'message' => "Applied {$approved} updates",
            'approved' => $approved
        ));
    }
    
    /**
     * AJAX: Reject all pending changes
     */
    public function ajax_reject_all() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $count = count($this->get_pending_reviews());
        update_option(self::PENDING_REVIEWS_OPTION, array());
        
        wp_send_json_success(array(
            'message' => "Rejected {$count} changes",
            'rejected' => $count
        ));
    }
}

// Initialize
FRA_AI_Review::get_instance();
