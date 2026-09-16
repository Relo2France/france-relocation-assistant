<?php
/**
 * Auth Pages - Styled authentication pages within regular site template
 *
 * @package France_Relocation_Assistant
 * @since 2.9.22
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Auth_Pages {
    
    private static $instance = null;
    private $settings = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_settings();
        
        add_shortcode('fra_login_page', array($this, 'render_login_page'));
        add_shortcode('fra_signup_page', array($this, 'render_signup_page'));
        add_shortcode('fra_logout_page', array($this, 'render_logout_page'));
        add_shortcode('fra_account_page', array($this, 'render_account_page'));
        add_shortcode('fra_thankyou_page', array($this, 'render_thankyou_page'));
        
        if (!empty($this->settings['auth_pages_enabled'])) {
            add_action('wp_head', array($this, 'output_css'), 999);
        }
    }
    
    private function load_settings() {
        $saved = get_option('fra_customizer', array());
        
        $defaults = array(
            'auth_pages_enabled' => false,
            'auth_logo_url' => '',
            'auth_site_name' => 'relo2France',
            'auth_login_title' => 'Welcome Back',
            'auth_login_subtitle' => 'Sign in to access your relocation dashboard',
            'auth_signup_title' => 'Start Your France Journey',
            'auth_signup_subtitle' => 'Create your account and get lifetime access',
            'auth_signup_price' => '$35 Lifetime Access',
            'auth_signup_price_note' => 'One-time payment, forever access',
            'auth_signup_benefits' => "AI-powered visa guidance\nStep-by-step relocation checklists\nDocument templates & generators\n183-day Schengen counter\nPriority email support",
            'auth_logout_title' => "You've Been Logged Out",
            'auth_logout_subtitle' => 'Thanks for using Relo2France! Your session has been securely ended.',
            'auth_account_title' => 'Your Account',
            'auth_account_subtitle' => 'Manage your membership and profile settings',
            'auth_thankyou_title' => 'Welcome to Relo2France!',
            'auth_thankyou_subtitle' => 'Your account has been created successfully.',
        );
        
        $this->settings = wp_parse_args($saved, $defaults);
    }
    
    private function get($key) {
        return isset($this->settings[$key]) ? $this->settings[$key] : '';
    }

    /**
     * Output CSS for auth cards within regular template
     */
    public function output_css() {
        ?>
        <style id="fra-auth-pages-css">
        /* ============================================================
           RELO2FRANCE AUTH PAGES - In-Template Cards
           Styled on the public site's tokens (relo2france-theme loads
           them on :root). Every var() carries a fallback so the cards
           still read correctly if the theme is ever swapped.

           Chrome is warm, content is exact. Vine is the only action
           colour. Honey is never a price, a button or a badge.
           ============================================================ */

        /* === HIDE DUPLICATE MEMBERPRESS FORMS === */
        /* Hide any MemberPress login forms that appear AFTER our container */
        .fra-auth-container ~ .mepr-login-form,
        .fra-auth-container ~ form.mepr-login-form,
        .fra-auth-container ~ div > .mepr-login-form,
        .entry-content > .mepr-login-form:not(.fra-auth-form-wrap .mepr-login-form),
        .site-content .mepr-login-form:not(.fra-auth-form-wrap .mepr-login-form),
        /* Hide forms that are siblings or outside our wrapper */
        .fra-auth-container + .mepr-login-form,
        .fra-auth-container + div:has(.mepr-login-form),
        /* Target the unstyled form below */
        body:has(.fra-auth-container) .entry-content > .mepr-login-form,
        body:has(.fra-auth-container) .site-main > .mepr-login-form,
        body:has(.fra-auth-container) article > .mepr-login-form {
            display: none !important;
        }

        /* Alternative: hide all MemberPress forms except ours */
        body:has(.fra-auth-container) .mepr-login-form {
            display: none !important;
        }
        body:has(.fra-auth-container) .fra-auth-form-wrap .mepr-login-form {
            display: block !important;
        }

        /* === CONTAINER === */
        .fra-auth-container {
            max-width: 440px;
            margin: 40px auto;
            padding: 0 1rem;
            font-family: var(--font-ui, Karla, "Helvetica Neue", Arial, sans-serif);
            color: var(--ink, #1c2420);
            -webkit-font-smoothing: antialiased;
        }

        .fra-auth-container * {
            box-sizing: border-box;
        }

        .fra-auth-container-wide {
            max-width: 560px;
        }

        /* === CARD === */
        .fra-auth-card {
            background: var(--card, #ffffff);
            border: 1px solid var(--rule, #dde3de);
            border-radius: var(--radius, 12px);
            padding: 32px;
        }

        /* === CARD HEADER === */
        .fra-auth-card-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .fra-auth-card-header h1 {
            font-family: var(--font-display, Fraunces, Georgia, serif);
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            line-height: 1.2;
            color: var(--ink, #1c2420);
            margin: 0 0 8px 0;
        }

        .fra-auth-card-header p {
            font-family: var(--font-ui, Karla, Arial, sans-serif);
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--muted, #5f6e66);
            margin: 0;
        }

        /* === EYEBROW === */
        .fra-auth-eyebrow,
        .fra-auth-benefits-title {
            font-family: var(--font-ui, Karla, Arial, sans-serif);
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted, #5f6e66);
        }

        /* === FORM WRAP === */
        .fra-auth-form-wrap {
            margin: 0;
        }

        /* MemberPress form resets */
        .fra-auth-form-wrap form,
        .fra-auth-form-wrap .mepr-form,
        .fra-auth-form-wrap .mepr-login-form,
        .fra-auth-form-wrap .mp-form {
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        .fra-auth-form-wrap h3 {
            display: none !important;
        }

        .fra-auth-form-wrap .mp-form-row,
        .fra-auth-form-wrap .mepr-form-row {
            margin: 0 0 16px 0 !important;
            padding: 0 !important;
        }

        .fra-auth-form-wrap .mp-spacer,
        .fra-auth-form-wrap .mepr_spacer {
            height: 8px;
        }

        .fra-auth-form-wrap .mp-form-label {
            display: block !important;
            margin: 0 0 6px 0 !important;
        }

        .fra-auth-form-wrap label {
            display: block !important;
            font-family: var(--font-ui, Karla, Arial, sans-serif) !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: var(--ink, #1c2420) !important;
            margin: 0 0 6px 0 !important;
        }

        .fra-auth-form-wrap .mepr-field-required label::after {
            content: " *";
            color: var(--muted, #5f6e66);
            font-weight: 400;
        }

        .fra-auth-form-wrap input[type="text"],
        .fra-auth-form-wrap input[type="email"],
        .fra-auth-form-wrap input[type="password"],
        .fra-auth-form-wrap input[type="tel"],
        .fra-auth-form-wrap input[type="url"],
        .fra-auth-form-wrap input[type="number"],
        .fra-auth-form-wrap textarea,
        .fra-auth-form-wrap select,
        .fra-auth-form-wrap .mepr-form-input {
            width: 100% !important;
            padding: 10px 14px !important;
            border: 1px solid var(--rule, #dde3de) !important;
            border-radius: var(--radius-sm, 10px) !important;
            font-family: inherit !important;
            font-size: 1rem !important;
            line-height: 1.4 !important;
            background: var(--card, #ffffff) !important;
            color: var(--ink, #1c2420) !important;
            box-shadow: none !important;
            transition: border-color 0.15s !important;
        }

        .fra-auth-form-wrap input:focus,
        .fra-auth-form-wrap textarea:focus,
        .fra-auth-form-wrap select:focus {
            outline: 2px solid var(--vine, #2c5346) !important;
            outline-offset: 2px !important;
            border-color: var(--rule, #dde3de) !important;
            box-shadow: none !important;
        }

        .fra-auth-form-wrap input::placeholder,
        .fra-auth-form-wrap textarea::placeholder {
            color: var(--muted, #5f6e66) !important;
            opacity: 0.7 !important;
        }

        /* Checkbox and radio */
        .fra-auth-form-wrap input[type="checkbox"],
        .fra-auth-form-wrap input[type="radio"] {
            width: 1rem !important;
            height: 1rem !important;
            margin: 0 8px 0 0 !important;
            accent-color: var(--vine, #2c5346) !important;
            vertical-align: middle;
        }

        .fra-auth-form-wrap .mp-form-row-checkbox,
        .fra-auth-form-wrap .mepr-form-row-checkbox {
            display: flex !important;
            align-items: center !important;
        }

        .fra-auth-form-wrap .mp-form-row-checkbox label,
        .fra-auth-form-wrap .mepr-form-row-checkbox label {
            display: inline !important;
            font-weight: 400 !important;
            margin: 0 !important;
        }

        /* Hide the "show password" toggle and stray dashicons */
        .fra-auth-form-wrap .mp-hide-pw,
        .fra-auth-form-wrap .dashicons {
            display: none !important;
        }

        /* Password strength meter */
        .fra-auth-form-wrap .mp-password-strength-display,
        .fra-auth-form-wrap .mp-pass-strength {
            font-size: 0.78rem !important;
            color: var(--muted, #5f6e66) !important;
            margin-top: 6px !important;
        }

        /* === BUTTONS === */
        .fra-auth-form-wrap input[type="submit"],
        .fra-auth-form-wrap button[type="submit"],
        .fra-auth-form-wrap .mepr-submit,
        .fra-auth-form-wrap .mepr-share-button,
        .fra-auth-btn {
            display: inline-block;
            width: 100% !important;
            padding: 10px 18px !important;
            border: 1px solid var(--vine, #2c5346) !important;
            border-radius: var(--radius-pill, 100px) !important;
            background: var(--vine, #2c5346) !important;
            color: var(--on-brand, #ffffff) !important;
            font-family: var(--font-ui, Karla, Arial, sans-serif) !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
            text-align: center !important;
            text-decoration: none !important;
            cursor: pointer !important;
            box-shadow: none !important;
            transition: opacity 0.15s !important;
            margin-top: 8px !important;
        }

        .fra-auth-form-wrap input[type="submit"]:hover,
        .fra-auth-form-wrap button[type="submit"]:hover,
        .fra-auth-form-wrap .mepr-submit:hover,
        .fra-auth-btn-primary:hover {
            opacity: 0.9;
            color: var(--on-brand, #ffffff) !important;
            text-decoration: none !important;
        }

        .fra-auth-form-wrap .mp-form-submit {
            margin-top: 8px !important;
        }

        .fra-auth-btn-primary {
            background: var(--vine, #2c5346);
            color: var(--on-brand, #ffffff);
        }

        .fra-auth-btn-secondary {
            background: transparent !important;
            color: var(--ink, #1c2420) !important;
            border-color: var(--rule, #dde3de) !important;
        }

        .fra-auth-btn-secondary:hover {
            background: var(--card-2, #f4f6f4) !important;
            color: var(--ink, #1c2420) !important;
            text-decoration: none !important;
        }

        .fra-auth-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 24px;
        }

        /* === LINKS === */
        .fra-auth-form-wrap a,
        .fra-auth-form-wrap .mepr-login-actions a {
            color: var(--vine, #2c5346) !important;
            text-decoration: none !important;
            font-size: 0.85rem;
        }

        .fra-auth-form-wrap a:hover {
            text-decoration: underline !important;
        }

        .fra-auth-form-wrap .mepr-login-actions {
            text-align: center;
            margin-top: 12px;
        }

        /* We link "Forgot your password?" ourselves in the card footer */
        .fra-auth-form-wrap .mepr-forgot-password,
        .fra-auth-form-wrap a[href*="forgot_password"],
        .fra-auth-form-wrap a[href*="lost-password"] {
            display: none !important;
        }

        /* === CARD FOOTER === */
        .fra-auth-card-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--rule-soft, #ebefeb);
        }

        .fra-auth-card-footer a {
            color: var(--vine, #2c5346);
            font-size: 0.85rem;
            text-decoration: none;
        }

        .fra-auth-card-footer a:hover {
            text-decoration: underline;
        }

        .fra-auth-card-footer p {
            color: var(--muted, #5f6e66);
            font-size: 0.85rem;
            margin: 8px 0;
        }

        .fra-auth-card-footer strong {
            font-weight: 600;
        }

        .fra-auth-sep {
            color: var(--rule, #dde3de);
            margin: 0 8px;
        }

        /* === PRICE === */
        .fra-auth-price {
            text-align: center;
            margin-bottom: 20px;
        }

        .fra-auth-price-badge {
            display: inline-block;
            background: var(--vine-soft, #e7efea);
            color: var(--vine, #2c5346);
            padding: 6px 16px;
            border-radius: var(--radius-pill, 100px);
            font-family: var(--font-ui, Karla, Arial, sans-serif);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .fra-auth-price-note {
            display: block;
            color: var(--muted, #5f6e66);
            font-size: 0.8rem;
            margin-top: 6px;
        }

        /* === BENEFITS === */
        .fra-auth-benefits {
            background: var(--card-2, #f4f6f4);
            border: 1px solid var(--rule, #dde3de);
            border-radius: var(--radius-sm, 10px);
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .fra-auth-benefits-title {
            margin-bottom: 10px;
        }

        .fra-auth-benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .fra-auth-benefits li {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.875rem;
            line-height: 1.5;
            color: var(--ink, #1c2420);
            padding: 3px 0;
        }

        .fra-auth-benefits li::before {
            content: '✓';
            color: var(--vine, #2c5346);
            font-weight: 700;
            flex-shrink: 0;
        }

        /* === ICON === */
        .fra-auth-icon,
        .fra-auth-icon-blue {
            width: 64px;
            height: 64px;
            background: var(--vine-soft, #e7efea);
            color: var(--vine, #2c5346);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.75rem;
        }

        /* === SECURITY NOTE === */
        .fra-auth-security {
            text-align: center;
            font-family: var(--font-mono, "IBM Plex Mono", ui-monospace, Menlo, monospace);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted, #5f6e66);
            margin-top: 16px;
        }

        /* === NOTICES === */
        .fra-auth-notice,
        .fra-auth-form-wrap .mepr-unauthorized-message {
            background: var(--card-2, #f4f6f4);
            border: 1px solid var(--rule, #dde3de);
            border-radius: var(--radius-sm, 10px);
            padding: 12px 16px;
            font-size: 0.875rem;
            color: var(--ink, #1c2420);
            text-align: center;
            margin: 0 0 16px 0;
        }

        /* Errors: brick, matching the portal's red - never honey */
        .fra-auth-error,
        .fra-auth-form-wrap .mepr-form-has-errors,
        .fra-auth-form-wrap .mepr_error,
        .fra-auth-form-wrap .mepr-error,
        .fra-auth-form-wrap .mepr-stripe-card-errors:not(:empty),
        .fra-auth-form-wrap .cc-error:not(:empty) {
            display: block;
            background: #fdf4f2;
            border: 1px solid #f2c9c1;
            border-radius: var(--radius-sm, 10px);
            color: #b4432f;
            font-size: 0.85rem;
            padding: 10px 14px;
            margin: 0 0 16px 0;
        }

        .fra-auth-form-wrap .mepr-form-has-errors ul {
            margin: 0;
            padding-left: 18px;
        }

        .fra-auth-form-wrap .cc-error:not(:empty) {
            padding: 6px 10px;
            margin: 6px 0 0 0;
        }

        /* === MEMBERPRESS CHECKOUT === */
        /* Price line at the top of the signup form */
        .fra-auth-form-wrap .mp-form-row.mepr_price {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
            padding: 12px 0 !important;
            border-top: 1px solid var(--rule-soft, #ebefeb);
            border-bottom: 1px solid var(--rule-soft, #ebefeb);
            margin-bottom: 20px !important;
            font-weight: 600;
        }

        .fra-auth-form-wrap .mepr_price_cell_label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted, #5f6e66);
        }

        .fra-auth-form-wrap .mepr_price_cell,
        .fra-auth-form-wrap .mp-currency-cell {
            font-family: var(--font-mono, "IBM Plex Mono", ui-monospace, Menlo, monospace);
            font-size: 0.95rem;
            color: var(--ink, #1c2420);
        }

        /* Invoice table */
        .fra-auth-form-wrap .mepr-transaction-invoice-wrapper {
            margin: 0 0 20px 0;
        }

        .fra-auth-form-wrap table.mp-table,
        .fra-auth-form-wrap table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: none !important;
            font-size: 0.85rem !important;
            background: transparent !important;
        }

        .fra-auth-form-wrap table th,
        .fra-auth-form-wrap table td {
            padding: 8px 4px !important;
            border: none !important;
            border-bottom: 1px solid var(--rule-soft, #ebefeb) !important;
            text-align: left !important;
            vertical-align: top !important;
            background: transparent !important;
            color: var(--ink, #1c2420);
        }

        .fra-auth-form-wrap table th {
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted, #5f6e66);
        }

        .fra-auth-form-wrap table td.mp-currency-cell,
        .fra-auth-form-wrap table td.mepr_price_cell {
            text-align: right !important;
        }

        /* Payment method block */
        .fra-auth-form-wrap .mepr-payment-methods-wrapper {
            margin: 4px 0 16px 0;
        }

        .fra-auth-form-wrap .mepr-payment-method {
            border: 1px solid var(--rule, #dde3de);
            border-radius: var(--radius-sm, 10px);
            padding: 14px 16px;
            background: var(--card, #ffffff);
        }

        .fra-auth-form-wrap .mepr-payment-option-label {
            display: flex !important;
            align-items: center !important;
            gap: 8px;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            margin: 0 0 10px 0 !important;
        }

        .fra-auth-form-wrap .mepr-payment-method-icon img,
        .fra-auth-form-wrap .mepr-payment-methods-icons img {
            height: 20px;
            width: auto;
        }

        .fra-auth-form-wrap .mepr-payment-method-desc-text {
            font-size: 0.8rem;
            color: var(--muted, #5f6e66);
            margin: 0 0 10px 0;
        }

        .fra-auth-form-wrap .mepr-stripe-card-element,
        .fra-auth-form-wrap .mepr-stripe-elements {
            padding: 10px 14px;
            border: 1px solid var(--rule, #dde3de);
            border-radius: var(--radius-sm, 10px);
            background: var(--card, #ffffff);
        }

        /* Coupon row: quieter than the rest */
        .fra-auth-form-wrap .mp-form-row.mepr_coupon label {
            color: var(--muted, #5f6e66) !important;
            font-weight: 500 !important;
        }

        .fra-auth-form-wrap .mepr-loading-gif,
        .fra-auth-form-wrap .mepr-coupon-loader,
        .fra-auth-form-wrap .mepr-invoice-loader {
            margin-left: 8px;
        }

        /* === MEMBERPRESS ACCOUNT === */
        .fra-auth-form-wrap #mepr-account-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 18px;
            justify-content: center;
            margin: 0 0 24px 0;
            padding: 0 0 16px 0;
            border-bottom: 1px solid var(--rule-soft, #ebefeb);
        }

        .fra-auth-form-wrap #mepr-account-nav .mepr-nav-item {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .fra-auth-form-wrap #mepr-account-nav .mepr-nav-item a {
            color: var(--muted, #5f6e66) !important;
            font-size: 0.85rem;
        }

        .fra-auth-form-wrap #mepr-account-nav .mepr-nav-item a:hover,
        .fra-auth-form-wrap #mepr-account-nav .mepr-active-nav-tab a {
            color: var(--ink, #1c2420) !important;
            text-decoration: none !important;
        }

        .fra-auth-form-wrap #mepr-account-nav .mepr-active-nav-tab a {
            font-weight: 600;
            border-bottom: 2px solid var(--vine, #2c5346);
            padding-bottom: 4px;
        }

        .fra-auth-form-wrap .mepr-account-change-password,
        .fra-auth-form-wrap #mepr-account-change-password {
            margin-top: 16px;
        }

        .fra-auth-form-wrap .mepr-account-change-password a {
            font-size: 0.85rem;
        }

        .fra-auth-form-wrap .mp-table-wrap {
            overflow-x: auto;
        }

        .fra-auth-card-centered {
            text-align: center;
        }

        .fra-auth-card-centered .fra-auth-benefits {
            text-align: left;
        }

        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .fra-auth-container {
                margin: 16px auto;
            }

            .fra-auth-card {
                padding: 24px;
            }

            .fra-auth-card-header h1 {
                font-size: 1.35rem;
            }

            .fra-auth-form-wrap #mepr-account-nav {
                gap: 4px 14px;
            }
        }
        </style>
        <?php
    }

    /**
     * Render login page
     */
    public function render_login_page($atts = array()) {
        $title = $this->get('auth_login_title');
        $subtitle = $this->get('auth_login_subtitle');
        
        ob_start();
        ?>
        <div class="fra-auth-container">
            <div class="fra-auth-card">
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <div class="fra-auth-form-wrap">
                    <?php echo do_shortcode('[mepr-login-form]'); ?>
                </div>
                
                <div class="fra-auth-card-footer">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot your password?</a>
                    <p>Don't have an account? <a href="<?php echo esc_url(home_url('/register/lifetime-membership/')); ?>"><strong>Get Started</strong></a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render signup page
     */
    public function render_signup_page($atts = array()) {
        $atts = shortcode_atts(array('membership_id' => ''), $atts);
        
        $title = $this->get('auth_signup_title');
        $subtitle = $this->get('auth_signup_subtitle');
        $price = $this->get('auth_signup_price');
        $price_note = $this->get('auth_signup_price_note');
        $benefits = array_filter(array_map('trim', explode("\n", $this->get('auth_signup_benefits'))));
        
        ob_start();
        ?>
        <div class="fra-auth-container fra-auth-container-wide">
            <div class="fra-auth-card">
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <?php if (!empty($price)) : ?>
                <div class="fra-auth-price">
                    <span class="fra-auth-price-badge"><?php echo esc_html($price); ?></span>
                    <?php if (!empty($price_note)) : ?>
                        <span class="fra-auth-price-note"><?php echo esc_html($price_note); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($benefits)) : ?>
                <div class="fra-auth-benefits">
                    <div class="fra-auth-benefits-title">What's Included</div>
                    <ul>
                        <?php foreach ($benefits as $benefit) : ?>
                            <li><?php echo esc_html($benefit); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="fra-auth-form-wrap">
                    <?php 
                    if (!empty($atts['membership_id'])) {
                        echo do_shortcode('[mepr-membership-registration-form id="' . esc_attr($atts['membership_id']) . '"]');
                    } else {
                        echo '<p class="fra-auth-error">Add membership_id to shortcode</p>';
                    }
                    ?>
                </div>
                
                <div class="fra-auth-security">🔒 Secure payment via Stripe</div>
                
                <div class="fra-auth-card-footer">
                    <p>Already have an account? <a href="<?php echo esc_url(home_url('/login/')); ?>"><strong>Sign In</strong></a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render logout page
     */
    public function render_logout_page($atts = array()) {
        $title = $this->get('auth_logout_title');
        $subtitle = $this->get('auth_logout_subtitle');
        
        ob_start();
        ?>
        <div class="fra-auth-container">
            <div class="fra-auth-card fra-auth-card-centered">
                <div class="fra-auth-icon">✓</div>
                
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <div class="fra-auth-actions">
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="fra-auth-btn fra-auth-btn-primary">Sign Back In</a>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="fra-auth-btn fra-auth-btn-secondary">Go to Homepage</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render account page
     */
    public function render_account_page($atts = array()) {
        $title = $this->get('auth_account_title');
        $subtitle = $this->get('auth_account_subtitle');
        
        ob_start();
        ?>
        <div class="fra-auth-container fra-auth-container-wide">
            <div class="fra-auth-card">
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <div class="fra-auth-form-wrap">
                    <?php echo do_shortcode('[mepr-account-form]'); ?>
                </div>
                
                <div class="fra-auth-card-footer">
                    <a href="<?php echo esc_url(home_url('/')); ?>">← Back to Home</a>
                    <span class="fra-auth-sep">|</span>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/logged-out/'))); ?>">Log Out</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render thank you page
     */
    public function render_thankyou_page($atts = array()) {
        $title = $this->get('auth_thankyou_title');
        $subtitle = $this->get('auth_thankyou_subtitle');
        
        ob_start();
        ?>
        <div class="fra-auth-container">
            <div class="fra-auth-card fra-auth-card-centered">
                <div class="fra-auth-icon fra-auth-icon-blue">🎉</div>
                
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <div class="fra-auth-benefits">
                    <div class="fra-auth-benefits-title">What's Next</div>
                    <ul>
                        <li>Explore the AI-powered relocation guide</li>
                        <li>Set up your 183-day Schengen counter</li>
                        <li>Start your visa application checklist</li>
                        <li>Generate document templates</li>
                    </ul>
                </div>
                
                <div class="fra-auth-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="fra-auth-btn fra-auth-btn-primary">Start Exploring</a>
                    <a href="<?php echo esc_url(home_url('/account/')); ?>" class="fra-auth-btn fra-auth-btn-secondary">View My Account</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

FRA_Auth_Pages::get_instance();
