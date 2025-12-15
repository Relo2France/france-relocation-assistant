<?php
/**
 * Plugin Auto-Updater
 * 
 * Enables automatic updates from GitHub releases or a custom update server
 * 
 * @package France_Relocation_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_Updater {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Plugin info
     */
    private $plugin_slug;
    private $plugin_file;
    private $version;
    
    /**
     * Update source settings
     */
    private $update_url;
    private $github_repo;
    
    /**
     * Cache key
     */
    private $cache_key = 'fra_update_check';
    private $cache_expiry = 43200; // 12 hours
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_slug = 'france-relocation-assistant';
        $this->plugin_file = FRA_PLUGIN_BASENAME;
        $this->version = FRA_VERSION;
        
        // Get update settings
        $this->update_url = get_option('fra_update_url', '');
        $this->github_repo = get_option('fra_github_repo', '');
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_action('upgrader_process_complete', array($this, 'after_update'), 10, 2);
        
        // Admin notice for updates
        add_action('admin_notices', array($this, 'update_notice'));
        
        // AJAX handler for manual update check
        add_action('wp_ajax_fra_check_update', array($this, 'ajax_check_update'));
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Check cache first
        $update_data = get_transient($this->cache_key);
        
        if (false === $update_data) {
            $update_data = $this->fetch_update_info();
            
            if ($update_data) {
                set_transient($this->cache_key, $update_data, $this->cache_expiry);
            }
        }
        
        if ($update_data && version_compare($this->version, $update_data['version'], '<')) {
            $transient->response[$this->plugin_file] = (object) array(
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_file,
                'new_version' => $update_data['version'],
                'url' => $update_data['url'] ?? '',
                'package' => $update_data['package'] ?? '',
                'icons' => array(),
                'banners' => array(),
                'requires' => $update_data['requires'] ?? '6.0',
                'tested' => $update_data['tested'] ?? '6.9',
                'requires_php' => $update_data['requires_php'] ?? '7.4',
            );
        }
        
        return $transient;
    }
    
    /**
     * Fetch update info from source
     */
    private function fetch_update_info() {
        // Try custom update URL first
        if (!empty($this->update_url)) {
            $response = wp_remote_get($this->update_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            ));
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($data['version'])) {
                    return $data;
                }
            }
        }
        
        // Try GitHub releases
        if (!empty($this->github_repo)) {
            return $this->fetch_github_release();
        }
        
        return false;
    }
    
    /**
     * Fetch latest release from GitHub
     */
    private function fetch_github_release() {
        $api_url = sprintf(
            'https://api.github.com/repos/%s/releases/latest',
            $this->github_repo
        );
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $release = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($release['tag_name'])) {
            return false;
        }
        
        // Parse version from tag (remove 'v' prefix if present)
        $version = ltrim($release['tag_name'], 'v');
        
        // Find zip asset
        $package = '';
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $package = $asset['browser_download_url'];
                    break;
                }
            }
        }
        
        // Fallback to zipball
        if (empty($package)) {
            $package = $release['zipball_url'] ?? '';
        }
        
        return array(
            'version' => $version,
            'url' => $release['html_url'] ?? '',
            'package' => $package,
            'changelog' => $release['body'] ?? '',
            'requires' => '6.0',
            'tested' => '6.9',
            'requires_php' => '7.4'
        );
    }
    
    /**
     * Plugin info for WordPress plugin details popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $update_data = get_transient($this->cache_key);
        
        if (!$update_data) {
            $update_data = $this->fetch_update_info();
        }
        
        if (!$update_data) {
            return $result;
        }
        
        return (object) array(
            'name' => 'France Relocation Assistant',
            'slug' => $this->plugin_slug,
            'version' => $update_data['version'],
            'author' => '<a href="https://relo2france.com">Relo2France</a>',
            'homepage' => 'https://relo2france.com',
            'download_link' => $update_data['package'] ?? '',
            'requires' => $update_data['requires'] ?? '6.0',
            'tested' => $update_data['tested'] ?? '6.9',
            'requires_php' => $update_data['requires_php'] ?? '7.4',
            'sections' => array(
                'description' => 'A comprehensive AI-powered tool for US to France relocation guidance.',
                'changelog' => $update_data['changelog'] ?? ''
            )
        );
    }
    
    /**
     * After update cleanup
     */
    public function after_update($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            if (isset($options['plugins']) && in_array($this->plugin_file, $options['plugins'])) {
                // Clear cache
                delete_transient($this->cache_key);
                
                // Log update
                update_option('fra_last_plugin_update', array(
                    'timestamp' => current_time('timestamp'),
                    'version' => FRA_VERSION
                ));
            }
        }
    }
    
    /**
     * Show update notice
     */
    public function update_notice() {
        // Only on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'france-relocation') === false) {
            return;
        }
        
        $update_data = get_transient($this->cache_key);
        
        if ($update_data && version_compare($this->version, $update_data['version'], '<')) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong><?php _e('France Relocation Assistant Update Available!', 'france-relocation-assistant'); ?></strong>
                    <?php printf(
                        __('Version %s is available. You are running version %s.', 'france-relocation-assistant'),
                        esc_html($update_data['version']),
                        esc_html($this->version)
                    ); ?>
                    <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e('Update now', 'france-relocation-assistant'); ?></a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for manual update check
     */
    public function ajax_check_update() {
        check_ajax_referer('fra_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Clear cache and fetch fresh
        delete_transient($this->cache_key);
        $update_data = $this->fetch_update_info();
        
        if (!$update_data) {
            wp_send_json_success(array(
                'has_update' => false,
                'message' => __('No updates available. You are running the latest version.', 'france-relocation-assistant'),
                'current_version' => $this->version
            ));
        }
        
        $has_update = version_compare($this->version, $update_data['version'], '<');
        
        wp_send_json_success(array(
            'has_update' => $has_update,
            'current_version' => $this->version,
            'new_version' => $update_data['version'],
            'message' => $has_update 
                ? sprintf(__('Version %s is available!', 'france-relocation-assistant'), $update_data['version'])
                : __('You are running the latest version.', 'france-relocation-assistant')
        ));
    }
    
    /**
     * Force update check
     */
    public function force_check() {
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
}

// Initialize
function fra_updater_init() {
    if (is_admin()) {
        return FRA_Updater::get_instance();
    }
}
add_action('plugins_loaded', 'fra_updater_init');
