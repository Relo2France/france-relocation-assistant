<?php
/**
 * Launch Validation
 *
 * Ensures all plugin components are properly configured
 * and working before launch.
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class FRA_Launch_Validation
 *
 * Validates plugin configuration and readiness.
 */
class FRA_Launch_Validation {

    /**
     * Validation results
     *
     * @var array
     */
    private $results = array();

    /**
     * Run all validation checks
     *
     * @return array Validation results
     */
    public function run_all_checks() {
        $this->results = array(
            'passed' => array(),
            'warnings' => array(),
            'errors' => array()
        );

        // Core checks
        $this->check_php_version();
        $this->check_wordpress_version();
        $this->check_required_files();
        $this->check_database_tables();

        // Feature checks
        $this->check_knowledge_base();
        $this->check_post_types();
        $this->check_rest_api();
        $this->check_api_configuration();
        $this->check_user_profile_system();
        $this->check_asset_files();

        // Integration checks
        $this->check_memberpress_integration();
        $this->check_permissions();

        return $this->results;
    }

    /**
     * Check PHP version
     */
    private function check_php_version() {
        $required = '7.4';
        $current = phpversion();

        if (version_compare($current, $required, '>=')) {
            $this->results['passed'][] = "PHP version {$current} meets requirement ({$required}+)";
        } else {
            $this->results['errors'][] = "PHP version {$current} is below required {$required}";
        }
    }

    /**
     * Check WordPress version
     */
    private function check_wordpress_version() {
        global $wp_version;
        $required = '5.8';

        if (version_compare($wp_version, $required, '>=')) {
            $this->results['passed'][] = "WordPress version {$wp_version} meets requirement ({$required}+)";
        } else {
            $this->results['errors'][] = "WordPress version {$wp_version} is below required {$required}";
        }
    }

    /**
     * Check required plugin files exist
     */
    private function check_required_files() {
        $required_files = array(
            'includes/user-profile.php',
            'includes/checklist-generator.php',
            'includes/dashboard-widget.php',
            'includes/api-settings.php',
            'includes/api-proxy.php',
            'includes/structured-data.php',
            'includes/migration.php',
            'includes/performance.php',
            'templates/single-kb_article.php',
            'assets/css/dashboard.css',
            'assets/js/dashboard.js',
            'assets/css/article.css'
        );

        $missing = array();
        foreach ($required_files as $file) {
            $path = FRA_PLUGIN_DIR . $file;
            if (!file_exists($path)) {
                $missing[] = $file;
            }
        }

        if (empty($missing)) {
            $this->results['passed'][] = "All required plugin files present (" . count($required_files) . " files)";
        } else {
            $this->results['errors'][] = "Missing required files: " . implode(', ', $missing);
        }
    }

    /**
     * Check database tables and options
     */
    private function check_database_tables() {
        global $wpdb;

        // Check required options exist
        $required_options = array(
            'fra_knowledge_base' => 'Knowledge Base data'
        );

        foreach ($required_options as $option => $description) {
            $value = get_option($option);
            if ($value !== false) {
                $this->results['passed'][] = "{$description} option exists";
            } else {
                $this->results['warnings'][] = "{$description} option not set - will be created on first use";
            }
        }

        // Check user meta table is accessible
        $test_query = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = '_fra_relocation_profile'");
        if ($test_query !== null) {
            $this->results['passed'][] = "User meta table accessible for profile storage";
        } else {
            $this->results['errors'][] = "Cannot access user meta table";
        }
    }

    /**
     * Check knowledge base
     */
    private function check_knowledge_base() {
        $fra = France_Relocation_Assistant::get_instance();
        $kb = $fra->get_knowledge_base();

        if (!empty($kb) && is_array($kb)) {
            $categories = count($kb);
            $topics = 0;
            foreach ($kb as $cat) {
                $topics += count($cat);
            }
            $this->results['passed'][] = "Knowledge base loaded: {$categories} categories, {$topics} topics";
        } else {
            $this->results['warnings'][] = "Knowledge base is empty or not initialized";
        }
    }

    /**
     * Check custom post types
     */
    private function check_post_types() {
        $required_post_types = array('kb_article');

        foreach ($required_post_types as $post_type) {
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                $total = $count->publish ?? 0;
                $this->results['passed'][] = "Post type '{$post_type}' registered ({$total} published)";
            } else {
                $this->results['warnings'][] = "Post type '{$post_type}' not registered - run migration";
            }
        }

        // Check taxonomy
        if (taxonomy_exists('kb_category')) {
            $terms = wp_count_terms('kb_category');
            $this->results['passed'][] = "Taxonomy 'kb_category' registered ({$terms} terms)";
        } else {
            $this->results['warnings'][] = "Taxonomy 'kb_category' not registered";
        }
    }

    /**
     * Check REST API endpoints
     */
    private function check_rest_api() {
        $required_routes = array(
            '/fra/v1/profile',
            '/fra/v1/task',
            '/fra/v1/chat'
        );

        $server = rest_get_server();
        $routes = array_keys($server->get_routes());

        foreach ($required_routes as $route) {
            $found = false;
            foreach ($routes as $registered) {
                if (strpos($registered, $route) !== false) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $this->results['passed'][] = "REST endpoint '{$route}' registered";
            } else {
                $this->results['warnings'][] = "REST endpoint '{$route}' not found";
            }
        }
    }

    /**
     * Check API configuration
     */
    private function check_api_configuration() {
        $config = fra_get_api_config();

        if (!empty($config['api_key'])) {
            if (fra_validate_api_key_format($config['api_key'])) {
                $this->results['passed'][] = "API key configured with valid format";
            } else {
                $this->results['warnings'][] = "API key set but format may be invalid";
            }
        } else {
            $this->results['warnings'][] = "API key not configured - AI features disabled";
        }

        if ($config['enable_ai']) {
            $this->results['passed'][] = "AI responses enabled";
        } else {
            $this->results['warnings'][] = "AI responses disabled";
        }
    }

    /**
     * Check user profile system
     */
    private function check_user_profile_system() {
        // Check functions exist
        $required_functions = array(
            'fra_get_user_profile',
            'fra_save_user_profile',
            'fra_get_checklist',
            'fra_get_completion_percentage'
        );

        foreach ($required_functions as $func) {
            if (function_exists($func)) {
                $this->results['passed'][] = "Function '{$func}' available";
            } else {
                $this->results['errors'][] = "Required function '{$func}' not found";
            }
        }

        // Check shortcode registered
        global $shortcode_tags;
        if (isset($shortcode_tags['fra_progress_dashboard'])) {
            $this->results['passed'][] = "Dashboard shortcode [fra_progress_dashboard] registered";
        } else {
            $this->results['warnings'][] = "Dashboard shortcode not registered";
        }
    }

    /**
     * Check asset files
     */
    private function check_asset_files() {
        $assets = array(
            'assets/css/dashboard.css',
            'assets/js/dashboard.js',
            'assets/css/article.css',
            'assets/css/frontend.css',
            'assets/js/frontend.js'
        );

        $total_size = 0;
        foreach ($assets as $asset) {
            $path = FRA_PLUGIN_DIR . $asset;
            if (file_exists($path)) {
                $total_size += filesize($path);
            }
        }

        $size_kb = round($total_size / 1024, 1);
        $this->results['passed'][] = "Asset files total: {$size_kb} KB";

        if ($size_kb > 500) {
            $this->results['warnings'][] = "Consider minifying assets - total size exceeds 500KB";
        }
    }

    /**
     * Check MemberPress integration
     */
    private function check_memberpress_integration() {
        if (class_exists('MeprUser')) {
            $this->results['passed'][] = "MemberPress detected and available";

            if (class_exists('FRA_Membership')) {
                $this->results['passed'][] = "FRA Membership integration class loaded";
            }
        } else {
            $this->results['warnings'][] = "MemberPress not detected - membership features limited";
        }
    }

    /**
     * Check permissions
     */
    private function check_permissions() {
        // Check upload directory is writable (for potential exports)
        $upload_dir = wp_upload_dir();
        if (wp_is_writable($upload_dir['basedir'])) {
            $this->results['passed'][] = "Upload directory is writable";
        } else {
            $this->results['warnings'][] = "Upload directory not writable - some features may be limited";
        }
    }

    /**
     * Get summary of results
     *
     * @return array Summary with counts
     */
    public function get_summary() {
        return array(
            'passed' => count($this->results['passed']),
            'warnings' => count($this->results['warnings']),
            'errors' => count($this->results['errors']),
            'ready' => empty($this->results['errors'])
        );
    }
}

/**
 * Admin page for launch validation
 */
function fra_render_validation_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $validator = new FRA_Launch_Validation();
    $results = $validator->run_all_checks();
    $summary = $validator->get_summary();

    ?>
    <div class="wrap">
        <h1>France Relocation Assistant - Launch Validation</h1>

        <div class="fra-validation-summary" style="background: <?php echo $summary['ready'] ? '#d4edda' : '#f8d7da'; ?>; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="margin-top: 0;">
                <?php if ($summary['ready']): ?>
                    ✅ Ready for Launch
                <?php else: ?>
                    ⚠️ Issues Found
                <?php endif; ?>
            </h2>
            <p>
                <strong><?php echo $summary['passed']; ?></strong> checks passed |
                <strong><?php echo $summary['warnings']; ?></strong> warnings |
                <strong><?php echo $summary['errors']; ?></strong> errors
            </p>
        </div>

        <?php if (!empty($results['errors'])): ?>
            <div class="fra-validation-section" style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <h3 style="color: #721c24; margin-top: 0;">❌ Errors (Must Fix)</h3>
                <ul>
                    <?php foreach ($results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($results['warnings'])): ?>
            <div class="fra-validation-section" style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <h3 style="color: #856404; margin-top: 0;">⚠️ Warnings (Review Recommended)</h3>
                <ul>
                    <?php foreach ($results['warnings'] as $warning): ?>
                        <li><?php echo esc_html($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($results['passed'])): ?>
            <div class="fra-validation-section" style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <h3 style="color: #155724; margin-top: 0;">✅ Passed (<?php echo count($results['passed']); ?>)</h3>
                <ul>
                    <?php foreach ($results['passed'] as $passed): ?>
                        <li><?php echo esc_html($passed); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <h3>Quick Actions</h3>
            <p>
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-settings'); ?>" class="button">API Settings</a>
                <a href="<?php echo admin_url('tools.php?page=fra-migration'); ?>" class="button">Run Migration</a>
                <button type="button" class="button" onclick="location.reload();">Re-run Validation</button>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Add validation page to admin menu
 */
function fra_add_validation_menu() {
    add_submenu_page(
        'france-relocation-assistant',
        'Launch Validation',
        'Validation',
        'manage_options',
        'france-relocation-assistant-validation',
        'fra_render_validation_page'
    );
}
add_action('admin_menu', 'fra_add_validation_menu');

/**
 * Run validation on plugin activation
 */
function fra_activation_validation() {
    $validator = new FRA_Launch_Validation();
    $results = $validator->run_all_checks();
    $summary = $validator->get_summary();

    // Store results for admin notice
    if (!$summary['ready']) {
        set_transient('fra_activation_issues', $results['errors'], 60);
    }
}
register_activation_hook(FRA_PLUGIN_FILE, 'fra_activation_validation');

/**
 * Show admin notice if activation found issues
 */
function fra_activation_notice() {
    $issues = get_transient('fra_activation_issues');
    if ($issues) {
        delete_transient('fra_activation_issues');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong>France Relocation Assistant:</strong>
                Some configuration issues were found. Please visit
                <a href="<?php echo admin_url('admin.php?page=france-relocation-assistant-validation'); ?>">Launch Validation</a>
                to review.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'fra_activation_notice');
