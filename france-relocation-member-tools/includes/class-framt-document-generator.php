<?php
/**
 * Document Generator
 *
 * Generates Word and PDF documents based on user data.
 *
 * @package FRA_Member_Tools
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRAMT_Document_Generator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Generate document content
     */
    public function generate($document_type, $answers, $profile) {
        $language = ($profile['application_location'] ?? 'us') === 'france' ? 'fr' : 'en';
        
        switch ($document_type) {
            case 'cover-letter':
                return $this->generate_cover_letter($answers, $profile, $language);
            case 'financial-statement':
                return $this->generate_financial_statement($answers, $profile, $language);
            case 'no-work-attestation':
                return $this->generate_no_work_attestation($answers, $profile, $language);
            case 'accommodation-letter':
                return $this->generate_accommodation_letter($answers, $profile, $language);
            default:
                return null;
        }
    }

    /**
     * Generate cover letter
     */
    private function generate_cover_letter($answers, $profile, $language) {
        $use_placeholders = $this->use_placeholders($answers, $profile);
        
        // Get names
        $applicant_name = $use_placeholders ? '[YOUR FULL NAME]' : $this->get_user_name();
        $spouse_name = $use_placeholders ? '[SPOUSE NAME]' : ($profile['spouse_name'] ?? '[SPOUSE NAME]');
        
        // Get location info
        $consulate = $this->get_consulate_info($profile['current_state'] ?? '');
        $target_location = $profile['target_location'] ?? '[LOCATION IN FRANCE]';
        
        // Get visa type display
        $visa_types = array(
            'visitor' => $language === 'fr' ? 'visa long séjour visiteur (VLS-TS Visiteur)' : 'long-stay visitor visa (VLS-TS Visiteur)',
            'talent_passport' => $language === 'fr' ? 'visa Passeport Talent' : 'Talent Passport visa',
        );
        $visa_display = $visa_types[$profile['visa_type'] ?? 'visitor'] ?? $visa_types['visitor'];

        // Build content based on language
        if ($language === 'fr') {
            $content = $this->build_french_cover_letter($applicant_name, $spouse_name, $visa_display, $target_location, $answers, $profile);
        } else {
            $content = $this->build_english_cover_letter($applicant_name, $spouse_name, $visa_display, $target_location, $consulate, $answers, $profile);
        }

        return array(
            'type' => 'cover-letter',
            'language' => $language,
            'content' => $content,
            'meta' => array(
                'visa_type' => $profile['visa_type'] ?? 'visitor',
                'applicants' => ($profile['applicants'] ?? 'alone') === 'alone' ? 1 : 2,
                'language' => strtoupper($language),
            ),
        );
    }

    /**
     * Build English cover letter content
     */
    private function build_english_cover_letter($name, $spouse, $visa, $location, $consulate, $answers, $profile) {
        $date = '[DATE]';
        $has_spouse = in_array($profile['applicants'] ?? '', array('spouse', 'spouse_kids'));
        
        $content = array(
            'header' => array(
                'date' => $date,
                'recipient' => array(
                    $consulate['name'],
                    'Visa Section',
                    $consulate['city'] . ', ' . $consulate['state'],
                ),
            ),
            'subject' => "Long-Stay Visitor Visa Application ({$visa})",
            'salutation' => 'Dear Visa Officer,',
            'paragraphs' => array(),
            'closing' => 'Respectfully submitted,',
            'signature' => array(
                'line' => '_________________________________',
                'name' => $name,
                'date_line' => 'Date: _________________',
            ),
        );

        // Opening paragraph
        $opening = "I am writing to submit my application for a {$visa} to France.";
        if ($has_spouse) {
            $opening .= " My spouse, {$spouse}, is simultaneously submitting their own application, as we intend to relocate together.";
        }
        $content['paragraphs'][] = $opening;

        // Property paragraph if applicable
        $property_status = $answers['property_status'] ?? 'none';
        if ($property_status === 'purchased' || $property_status === 'purchasing') {
            $content['paragraphs'][] = $this->get_property_paragraph($property_status, $location, $answers);
        }

        // Reason for move
        if (!empty($answers['move_reason'])) {
            $content['paragraphs'][] = $answers['move_reason'];
        }

        // Employment/Status paragraph
        $content['paragraphs'][] = $this->get_status_paragraph($profile);

        // Financial resources paragraph
        $content['paragraphs'][] = $this->get_financial_paragraph($profile, $has_spouse);

        // Health insurance paragraph
        $content['paragraphs'][] = "I have obtained comprehensive private health insurance that covers the full twelve-month duration of my visa. The policy meets all French requirements for medical coverage, hospitalization, and repatriation.";

        // Closing paragraph
        $content['paragraphs'][] = "I am genuinely excited about the opportunity to live in France. I have prepared carefully for this transition, ensuring that I have the financial means, accommodation, and health coverage to support myself without relying on French public resources. I respectfully request that you approve my application. I am happy to provide any additional documentation or clarification that may be helpful.";

        return $content;
    }

    /**
     * Build French cover letter content
     */
    private function build_french_cover_letter($name, $spouse, $visa, $location, $answers, $profile) {
        $date = '[DATE]';
        
        $content = array(
            'header' => array(
                'date' => $date,
                'recipient' => array(
                    'Préfecture de [DÉPARTEMENT]',
                    'Service des Étrangers',
                    '[VILLE], France',
                ),
            ),
            'subject' => "Demande de renouvellement de visa long séjour",
            'salutation' => 'Madame, Monsieur,',
            'paragraphs' => array(
                "J'ai l'honneur de solliciter le renouvellement de mon {$visa}.",
                "Je réside actuellement à {$location} et souhaite continuer à y résider.",
                "Je dispose des ressources financières suffisantes pour subvenir à mes besoins sans recourir aux aides publiques françaises.",
                "Je reste à votre disposition pour tout renseignement complémentaire.",
            ),
            'closing' => 'Je vous prie d\'agréer, Madame, Monsieur, l\'expression de mes salutations distinguées.',
            'signature' => array(
                'line' => '_________________________________',
                'name' => $name,
                'date_line' => 'Date : _________________',
            ),
        );

        return $content;
    }

    /**
     * Get property paragraph
     */
    private function get_property_paragraph($status, $location, $answers) {
        if ($status === 'purchased') {
            return "I have purchased property in {$location}, France. This property will serve as my primary residence.";
        } elseif ($status === 'purchasing') {
            return "I am in the process of purchasing property in {$location}, France. I have signed a preliminary sales agreement (compromis de vente) and the transaction is progressing toward completion.";
        }
        return '';
    }

    /**
     * Get status paragraph
     */
    private function get_status_paragraph($profile) {
        $status = $profile['employment_status'] ?? 'retired';
        
        if ($status === 'retired') {
            return "I am retired and do not engage in any professional activity. I have no intention of seeking employment in France or conducting any business activities during my stay. I confirm that I will not exercise any professional activity in France during the validity of my visa.";
        } elseif ($status === 'employed') {
            $employer = $profile['employer_name'] ?? '[EMPLOYER NAME]';
            return "I am employed by {$employer}, a U.S.-based company. My employment is stable and ongoing, providing consistent income to support my stay in France.";
        }
        
        return "I have no intention of seeking employment in France during my stay.";
    }

    /**
     * Get financial paragraph
     */
    private function get_financial_paragraph($profile, $has_spouse) {
        $prefix = $has_spouse ? "My spouse and I are" : "I am";
        return "{$prefix} financially self-sufficient and will place no burden on the French social system. Our combined household resources include employment income, retirement savings, liquid bank assets, and investment accounts. Detailed documentation of these resources is provided in our Financial Resources Statement and supporting bank statements.";
    }

    /**
     * Generate financial statement
     */
    private function generate_financial_statement($answers, $profile, $language) {
        $use_placeholders = $this->use_placeholders($answers, $profile);
        $include_table = ($answers['include_table'] ?? 'yes') === 'yes';

        $content = array(
            'title' => $language === 'fr' ? 'Attestation de Ressources Financières' : 'Statement of Financial Resources',
            'sections' => array(),
            'include_table' => $include_table,
            'placeholders' => $use_placeholders,
        );

        // Add sections based on income sources
        // The portal stores income sources as free text; the older onboarding
        // form stored a list of keys. Map words in the text onto the known
        // sections and fall back to savings so the statement is never empty.
        $income_sources = $profile['income_sources'] ?? array('savings');
        if (!is_array($income_sources)) {
            $text = strtolower((string) $income_sources);
            $income_sources = array();
            foreach (array('employment' => array('employ', 'salary', 'job', 'work'), 'retirement' => array('retire', 'pension', 'invest', 'dividend', '401', 'ira'), 'savings' => array('saving', 'bank', 'cash')) as $key => $words) {
                foreach ($words as $word) {
                    if (false !== strpos($text, $word)) {
                        $income_sources[] = $key;
                        break;
                    }
                }
            }
            if (empty($income_sources)) {
                $income_sources = array('savings');
            }
        }

        foreach ($income_sources as $source) {
            $content['sections'][] = $this->get_income_section($source, $use_placeholders, $language);
        }

        return array(
            'type' => 'financial-statement',
            'language' => $language,
            'content' => $content,
            'meta' => array(
                'language' => strtoupper($language),
            ),
        );
    }

    /**
     * Get income section content
     */
    private function get_income_section($source, $placeholders, $language) {
        $sections = array(
            'employment' => array(
                'title' => $language === 'fr' ? 'Revenus d\'Emploi' : 'Employment Income',
                'amount' => $placeholders ? '$[AMOUNT]' : '',
            ),
            'retirement' => array(
                'title' => $language === 'fr' ? 'Comptes de Retraite' : 'Retirement & Investment Accounts',
                'amount' => $placeholders ? '$[AMOUNT]' : '',
            ),
            'savings' => array(
                'title' => $language === 'fr' ? 'Actifs Liquides' : 'Liquid Assets – Bank Accounts',
                'amount' => $placeholders ? '$[AMOUNT]' : '',
            ),
        );

        return $sections[$source] ?? $sections['savings'];
    }

    /**
     * Generate no work attestation
     */
    private function generate_no_work_attestation($answers, $profile, $language) {
        $name = $this->get_user_name();
        $activities = $answers['activities'] ?? 'managing my household and learning French';

        // Always generate in English (French title in parentheses for reference)
        $content = array(
            'title' => 'Attestation on Honor (Attestation sur l\'Honneur)',
            'body' => "I, {$name}, hereby declare on my honor that I do not and will not exercise any professional activity in France during the validity of my visa.\n\nDuring my stay in France, I intend to focus on {$activities}.",
        );

        return array(
            'type' => 'no-work-attestation',
            'language' => 'en',
            'content' => $content,
            'meta' => array('language' => 'EN'),
        );
    }

    /**
     * Generate accommodation letter
     */
    private function generate_accommodation_letter($answers, $profile, $language) {
        $accommodation_type = $answers['accommodation_type'] ?? 'rental';
        
        $content = array(
            'title' => $language === 'fr' ? 'Justificatif d\'Hébergement' : 'Proof of Accommodation',
            'type' => $accommodation_type,
        );

        return array(
            'type' => 'accommodation-letter',
            'language' => $language,
            'content' => $content,
            'meta' => array('language' => strtoupper($language)),
        );
    }

    /**
     * Get consulate info by state
     */
    private function get_consulate_info($state) {
        $consulates = array(
            'default' => array('name' => 'Consulate General of France', 'city' => 'Washington', 'state' => 'D.C.'),
            'CA' => array('name' => 'Consulate General of France', 'city' => 'Los Angeles', 'state' => 'CA'),
            'NY' => array('name' => 'Consulate General of France', 'city' => 'New York', 'state' => 'NY'),
            'TX' => array('name' => 'Consulate General of France', 'city' => 'Houston', 'state' => 'TX'),
            'FL' => array('name' => 'Consulate General of France', 'city' => 'Miami', 'state' => 'FL'),
            'IL' => array('name' => 'Consulate General of France', 'city' => 'Chicago', 'state' => 'IL'),
        );

        return $consulates[$state] ?? $consulates['default'];
    }

    /**
     * Placeholders or real details. The member's explicit answer wins. With
     * no answer, a profile that holds a legal first and last name gets the
     * real details, since the letter is theirs to file; anything less gets
     * placeholders so nothing half-filled goes to a consulate.
     *
     * @param array $answers Answers collected for this document
     * @param array $profile Portal profile
     * @return bool True to use placeholders
     */
    private function use_placeholders($answers, $profile) {
        $choice = $answers['privacy_choice'] ?? '';
        if ('placeholders' === $choice) {
            return true;
        }
        if ('actual' === $choice) {
            return false;
        }
        $has_legal_name = !empty($profile['legal_first_name']) && !empty($profile['legal_last_name']);
        return !$has_legal_name;
    }

    /**
     * Get current user's name: the legal name from the profile first, since
     * that is what the passport says, then the WordPress name.
     */
    private function get_user_name() {
        $user = wp_get_current_user();
        $first = (string) get_user_meta($user->ID, 'fra_legal_first_name', true);
        $last  = (string) get_user_meta($user->ID, 'fra_legal_last_name', true);
        if ($first && $last) {
            return $first . ' ' . $last;
        }
        if ($user->first_name && $user->last_name) {
            return $user->first_name . ' ' . $user->last_name;
        }
        return $user->display_name ?: '[YOUR NAME]';
    }

    /**
     * AJAX: Generate document
     */
    public function ajax_generate_document() {
        check_ajax_referer('framt_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'fra-member-tools'));
        }

        $document_type = sanitize_key($_POST['document_type'] ?? '');
        $answers = json_decode(stripslashes($_POST['answers'] ?? '{}'), true);
        // Use portal profile (user meta) for accurate data
        $profile = FRAMT_Profile::get_portal_profile(get_current_user_id());

        $result = $this->generate($document_type, $answers, $profile);

        if ($result) {
            // Save document to database
            $doc_data = array(
                'type' => $document_type,
                'title' => $this->get_document_title($document_type),
                'content' => $result['content'],
                'meta' => $result['meta'],
            );
            $doc_id = FRAMT_Documents::get_instance()->save_document($doc_data, get_current_user_id());
            
            // Generate preview text
            $preview = $this->generate_preview($result);
            
            wp_send_json_success(array(
                'id' => $doc_id,
                'title' => $this->get_document_title($document_type),
                'preview' => $preview,
            ));
        }

        wp_send_json_error(__('Failed to generate document.', 'fra-member-tools'));
    }
    
    /**
     * Get document title
     */
    private function get_document_title($document_type) {
        $titles = array(
            'cover-letter' => __('Visa Cover Letter', 'fra-member-tools'),
            'financial-statement' => __('Financial Statement', 'fra-member-tools'),
            'no-work-attestation' => __('No Work Attestation', 'fra-member-tools'),
            'accommodation-letter' => __('Accommodation Letter', 'fra-member-tools'),
        );
        return $titles[$document_type] ?? __('Document', 'fra-member-tools');
    }
    
    /**
     * Generate preview text from document content
     */
    private function generate_preview($result) {
        $content = $result['content'];
        $preview = '';
        
        if (isset($content['paragraphs'])) {
            $preview = implode("\n\n", array_slice($content['paragraphs'], 0, 2));
            if (count($content['paragraphs']) > 2) {
                $preview .= "\n\n[...]";
            }
        } elseif (isset($content['body'])) {
            $preview = substr($content['body'], 0, 500);
            if (strlen($content['body']) > 500) {
                $preview .= '...';
            }
        } elseif (isset($content['title'])) {
            $preview = $content['title'];
        }
        
        return nl2br(esc_html($preview));
    }
    
    /**
     * AJAX: Download document
     *
     * Answers with a link back to admin-ajax that streams the file. Nothing
     * is written to the public uploads folder, so there is no copy anyone
     * else could fetch.
     */
    public function ajax_download_document() {
        check_ajax_referer('framt_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in.', 'fra-member-tools'));
        }
        
        $document_id = intval($_POST['document_id'] ?? 0);
        $format = 'pdf' === sanitize_key($_POST['format'] ?? 'word') ? 'pdf' : 'word';
        $user_id = get_current_user_id();
        
        // Get the document
        $document = FRAMT_Documents::get_instance()->get_document($document_id, $user_id);
        
        if (!$document) {
            wp_send_json_error(__('Document not found.', 'fra-member-tools'));
        }
        
        $file_url = add_query_arg(
            array(
                'action'      => 'framt_fetch_document',
                'document_id' => $document_id,
                'format'      => $format,
                '_wpnonce'    => wp_create_nonce('framt_fetch_document_' . $document_id),
            ),
            admin_url('admin-ajax.php')
        );
        
        wp_send_json_success(array('url' => $file_url));
    }

    /**
     * AJAX (GET): Stream a generated document to the member who owns it.
     *
     * @return void
     */
    public function ajax_fetch_document() {
        $document_id = isset($_GET['document_id']) ? absint($_GET['document_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!is_user_logged_in() || !$document_id || !wp_verify_nonce($nonce, 'framt_fetch_document_' . $document_id)) {
            status_header(403);
            exit(esc_html__('This link has expired. Open the document from your account again.', 'fra-member-tools'));
        }
        $format = 'pdf' === sanitize_key(wp_unslash($_GET['format'] ?? 'word')) ? 'pdf' : 'word';
        $document = FRAMT_Documents::get_instance()->get_document($document_id, get_current_user_id());
        if (!$document) {
            status_header(404);
            exit(esc_html__('Document not found.', 'fra-member-tools'));
        }

        $bytes = 'word' === $format
            ? $this->build_word_bytes($document['content'], $document)
            : $this->build_pdf_bytes($document['content'], $document);
        $filename = sanitize_file_name($document['title'] . '-' . gmdate('Y-m-d')) . ('word' === $format ? '.doc' : '.pdf');

        nocache_headers();
        header('Content-Type: ' . ('word' === $format ? 'application/msword' : 'application/pdf'));
        header('Content-Length: ' . strlen($bytes));
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: sandbox');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput -- file bytes.
        exit;
    }
    
    /**
     * Build a Word document (HTML that Word can open)
     *
     * @return string
     */
    private function build_word_bytes($content, $document) {
        $html = $this->content_to_html($content, $document);
        
        $word_html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
<meta charset="utf-8">
<meta name="ProgId" content="Word.Document">
<style>
body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.6; margin: 1in; }
h1 { font-size: 14pt; font-weight: bold; margin-bottom: 1em; }
p { margin-bottom: 1em; text-align: justify; }
.signature { margin-top: 2em; }
.date { margin-bottom: 2em; }
</style>
</head>
<body>' . $html . '</body></html>';
        
        return $word_html;
    }
    
    /**
     * Build the PDF bytes
     *
     * @return string
     */
    private function build_pdf_bytes($content, $document) {
        // The multi-page writer; it keeps the old one-page writer's calls.
        if (!class_exists('FRAMT_PDF')) {
            require_once FRAMT_PLUGIN_DIR . 'includes/class-framt-pdf.php';
        }
        
        $pdf = new FRAMT_PDF(isset($content['title']) ? (string) $content['title'] : '');
        $pdf->addPage();
        
        // Add title if exists
        if (isset($content['title'])) {
            $pdf->writeTitle($content['title']);
            $pdf->addSpace(1);
        }
        
        // Handle cover letter format
        if (isset($content['header'])) {
            // Date
            $pdf->write($content['header']['date']);
            $pdf->addSpace(1);
            
            // Recipient address
            if (isset($content['header']['recipient'])) {
                foreach ($content['header']['recipient'] as $line) {
                    $pdf->write($line);
                }
            }
            $pdf->addSpace(1);
        }
        
        // Subject line
        if (isset($content['subject'])) {
            $pdf->write('Re: ' . $content['subject'], true);
            $pdf->addSpace(1);
        }
        
        // Salutation
        if (isset($content['salutation'])) {
            $pdf->write($content['salutation']);
            $pdf->addSpace(1);
        }
        
        // Paragraphs
        if (isset($content['paragraphs'])) {
            foreach ($content['paragraphs'] as $para) {
                $pdf->write($para);
                $pdf->addSpace(1);
            }
        }
        
        // Body (for attestations)
        if (isset($content['body'])) {
            $lines = explode("\n", $content['body']);
            foreach ($lines as $line) {
                if (trim($line)) {
                    $pdf->write(trim($line));
                }
                $pdf->addSpace(1);
            }
        }
        
        // Financial statement sections
        if (isset($content['sections'])) {
            foreach ($content['sections'] as $section) {
                $pdf->write($section['title'], true);
                if (!empty($section['amount'])) {
                    $pdf->write('Amount: ' . $section['amount']);
                }
                $pdf->addSpace(1);
            }
        }
        
        // Closing
        if (isset($content['closing'])) {
            $pdf->write($content['closing']);
            $pdf->addSpace(2);
        }
        
        // Signature block
        if (isset($content['signature'])) {
            $pdf->write($content['signature']['line']);
            $pdf->write($content['signature']['name']);
            $pdf->write($content['signature']['date_line']);
        }
        
        return $pdf->output();
    }
    
    /**
     * Convert document content to HTML
     */
    private function content_to_html($content, $document) {
        $html = '';
        
        // Handle cover letter format
        if (isset($content['header'])) {
            $html .= '<p class="date">' . esc_html($content['header']['date']) . '</p>';
            
            if (isset($content['header']['recipient'])) {
                $html .= '<p>' . implode('<br>', array_map('esc_html', $content['header']['recipient'])) . '</p>';
            }
        }
        
        if (isset($content['subject'])) {
            $html .= '<p><strong>Re: ' . esc_html($content['subject']) . '</strong></p>';
        }
        
        if (isset($content['salutation'])) {
            $html .= '<p>' . esc_html($content['salutation']) . '</p>';
        }
        
        if (isset($content['paragraphs'])) {
            foreach ($content['paragraphs'] as $para) {
                $html .= '<p>' . esc_html($para) . '</p>';
            }
        }
        
        if (isset($content['closing'])) {
            $html .= '<p>' . esc_html($content['closing']) . '</p>';
        }
        
        if (isset($content['signature'])) {
            $html .= '<div class="signature">';
            $html .= '<p>' . esc_html($content['signature']['line']) . '</p>';
            $html .= '<p>' . esc_html($content['signature']['name']) . '</p>';
            $html .= '<p>' . esc_html($content['signature']['date_line']) . '</p>';
            $html .= '</div>';
        }
        
        // Handle simple body format (attestations)
        if (isset($content['title']) && isset($content['body'])) {
            $html .= '<h1>' . esc_html($content['title']) . '</h1>';
            $html .= '<p>' . nl2br(esc_html($content['body'])) . '</p>';
        }
        
        // Handle financial statement format
        if (isset($content['sections'])) {
            $html .= '<h1>' . esc_html($content['title'] ?? 'Financial Statement') . '</h1>';
            foreach ($content['sections'] as $section) {
                $html .= '<h2>' . esc_html($section['title']) . '</h2>';
                if (!empty($section['amount'])) {
                    $html .= '<p>Amount: ' . esc_html($section['amount']) . '</p>';
                }
            }
        }
        
        return $html;
    }
}
