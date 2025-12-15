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
           Works within your existing site header/footer
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
        
        /* === AUTH CONTAINER === */
        .fra-auth-container {
            max-width: 480px;
            margin: 2rem auto;
            padding: 0 1rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        
        .fra-auth-container * {
            box-sizing: border-box;
        }
        
        .fra-auth-container-wide {
            max-width: 540px;
        }
        
        /* === CARD === */
        .fra-auth-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            border: 1px solid #e5e7eb;
        }
        
        /* === CARD HEADER === */
        .fra-auth-card-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .fra-auth-card-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a5f;
            margin: 0 0 0.5rem 0;
            line-height: 1.3;
        }
        
        .fra-auth-card-header p {
            color: #6b7280;
            font-size: 0.9375rem;
            margin: 0;
            line-height: 1.5;
        }
        
        /* === FORM STYLES === */
        .fra-auth-form-wrap {
            margin: 0;
        }
        
        /* MemberPress form resets */
        .fra-auth-form-wrap form,
        .fra-auth-form-wrap .mepr-login-form,
        .fra-auth-form-wrap .mp-form {
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }
        
        .fra-auth-form-wrap h3,
        .fra-auth-form-wrap .mp-form-label {
            display: none !important;
        }
        
        .fra-auth-form-wrap .mp-form-row,
        .fra-auth-form-wrap .mepr-form-row {
            margin-bottom: 1rem !important;
        }
        
        .fra-auth-form-wrap label {
            display: block !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
            margin-bottom: 0.375rem !important;
        }
        
        .fra-auth-form-wrap input[type="text"],
        .fra-auth-form-wrap input[type="email"],
        .fra-auth-form-wrap input[type="password"],
        .fra-auth-form-wrap input[type="tel"],
        .fra-auth-form-wrap textarea,
        .fra-auth-form-wrap select {
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            font-size: 1rem !important;
            font-family: inherit !important;
            background: #fff !important;
            color: #1f2937 !important;
            transition: border-color 0.15s, box-shadow 0.15s !important;
        }
        
        .fra-auth-form-wrap input:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        .fra-auth-form-wrap input::placeholder {
            color: #9ca3af !important;
        }
        
        /* Checkbox */
        .fra-auth-form-wrap input[type="checkbox"] {
            width: 1rem !important;
            height: 1rem !important;
            margin: 0 0.5rem 0 0 !important;
            accent-color: #1e3a5f !important;
        }
        
        .fra-auth-form-wrap .mp-form-row-checkbox {
            display: flex !important;
            align-items: center !important;
        }
        
        .fra-auth-form-wrap .mp-form-row-checkbox label {
            display: inline !important;
            font-weight: 400 !important;
            margin: 0 !important;
        }
        
        /* Submit button - Orange like site CTA */
        .fra-auth-form-wrap input[type="submit"],
        .fra-auth-form-wrap button[type="submit"],
        .fra-auth-form-wrap .mepr-submit {
            width: 100% !important;
            padding: 0.875rem 1.5rem !important;
            background: #ea580c !important;
            color: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: background 0.15s !important;
            margin-top: 0.5rem !important;
        }
        
        .fra-auth-form-wrap input[type="submit"]:hover,
        .fra-auth-form-wrap button[type="submit"]:hover {
            background: #c2410c !important;
        }
        
        /* Links in form */
        .fra-auth-form-wrap a {
            color: #1e3a5f !important;
            text-decoration: none !important;
        }
        
        .fra-auth-form-wrap a:hover {
            text-decoration: underline !important;
        }
        
        /* Hide ugly elements and duplicate links */
        .fra-auth-form-wrap .mp-hide-pw,
        .fra-auth-form-wrap .dashicons,
        .fra-auth-form-wrap .mepr-forgot-password,
        .fra-auth-form-wrap a[href*="forgot_password"],
        .fra-auth-form-wrap a[href*="lost-password"] {
            display: none !important;
        }
        
        /* === CARD FOOTER LINKS === */
        .fra-auth-card-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .fra-auth-card-footer a {
            color: #1e3a5f;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .fra-auth-card-footer a:hover {
            text-decoration: underline;
        }
        
        .fra-auth-card-footer p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.5rem 0;
        }
        
        /* === PRICE BADGE === */
        .fra-auth-price {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        
        .fra-auth-price-badge {
            display: inline-block;
            background: #ea580c;
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
        }
        
        .fra-auth-price-note {
            display: block;
            color: #6b7280;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
        
        /* === BENEFITS LIST === */
        .fra-auth-benefits {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .fra-auth-benefits-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }
        
        .fra-auth-benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .fra-auth-benefits li {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #374151;
            padding: 0.25rem 0;
        }
        
        .fra-auth-benefits li::before {
            content: '✓';
            color: #16a34a;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        /* === SUCCESS/ICON === */
        .fra-auth-icon {
            width: 64px;
            height: 64px;
            background: #dcfce7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.75rem;
        }
        
        .fra-auth-icon-blue {
            background: #dbeafe;
        }
        
        /* === ACTION BUTTONS === */
        .fra-auth-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .fra-auth-btn {
            display: block;
            width: 100%;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.15s;
            border: none;
            cursor: pointer;
        }
        
        .fra-auth-btn-primary {
            background: #ea580c;
            color: #fff;
        }
        
        .fra-auth-btn-primary:hover {
            background: #c2410c;
            color: #fff;
            text-decoration: none;
        }
        
        .fra-auth-btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .fra-auth-btn-secondary:hover {
            background: #e5e7eb;
            color: #374151;
            text-decoration: none;
        }
        
        .fra-auth-security {
            text-align: center;
            color: #9ca3af;
            font-size: 0.75rem;
            margin-top: 1rem;
        }
        
        /* === RESPONSIVE === */
        @media (max-width: 480px) {
            .fra-auth-container {
                margin: 1rem auto;
            }
            
            .fra-auth-card {
                padding: 1.5rem;
            }
            
            .fra-auth-card-header h1 {
                font-size: 1.25rem;
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
                        echo '<p style="color: #dc2626; text-align: center; padding: 1rem; background: #fef2f2; border-radius: 8px;">Add membership_id to shortcode</p>';
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
            <div class="fra-auth-card" style="text-align: center;">
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
                    <span style="color: #d1d5db; margin: 0 0.5rem;">|</span>
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
            <div class="fra-auth-card" style="text-align: center;">
                <div class="fra-auth-icon fra-auth-icon-blue">🎉</div>
                
                <div class="fra-auth-card-header">
                    <h1><?php echo esc_html($title); ?></h1>
                    <p><?php echo esc_html($subtitle); ?></p>
                </div>
                
                <div class="fra-auth-benefits" style="text-align: left;">
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
