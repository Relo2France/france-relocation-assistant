<?php
/**
 * Plugin Name: France Relocation GitHub Sync
 * Plugin URI: https://relo2france.com
 * Description: Custom GitHub sync solution - automatically updates plugins from GitHub without requiring WP Pusher. Includes diagnostics, version tracking, and detailed logging.
 * Version: 2.0.0
 * Author: Relo2France
 * Author URI: https://relo2france.com
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRA_GitHub_Sync {

    private static $instance = null;

    // GitHub repo details
    const GITHUB_REPO = 'Relo2France/france-relocation-assistant';
    const GITHUB_BRANCH = 'main';
    const VERSION = '2.0.0';

    // Plugins to sync (folder name => main plugin file)
    private $plugins = array(
        'france-relocation-assistant-plugin' => array(
            'file' => 'france-relocation-assistant-plugin/france-relocation-assistant.php',
            'name' => 'France Relocation Assistant',
        ),
        'france-relocation-member-tools' => array(
            'file' => 'france-relocation-member-tools/france-relocation-member-tools.php',
            'name' => 'France Relocation Member Tools',
        ),
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Schedule cron event
        add_action('init', array($this, 'schedule_sync'));
        add_filter('cron_schedules', array($this, 'add_cron_interval'));

        // Hook for the cron job
        add_action('fra_github_sync_check', array($this, 'check_for_updates'));

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // AJAX handlers
        add_action('wp_ajax_fra_github_sync', array($this, 'ajax_handler'));

        // Admin notice when updates are available
        add_action('admin_notices', array($this, 'show_update_notice'));

        // Settings
        add_action('admin_init', array($this, 'register_settings'));

        // Cleanup on deactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('fra_github_sync_settings', 'fra_github_token');
        register_setting('fra_github_sync_settings', 'fra_sync_interval');
    }

    /**
     * Schedule the sync check
     */
    public function schedule_sync() {
        $interval = get_option('fra_sync_interval', 'every_15_minutes');

        if (!wp_next_scheduled('fra_github_sync_check')) {
            wp_schedule_event(time(), $interval, 'fra_github_sync_check');
        }
    }

    /**
     * Add custom cron intervals
     */
    public function add_cron_interval($schedules) {
        $schedules['every_5_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => 'Every 5 Minutes',
        );
        $schedules['every_15_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => 'Every 15 Minutes',
        );
        $schedules['every_30_minutes'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => 'Every 30 Minutes',
        );
        $schedules['hourly'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display'  => 'Every Hour',
        );
        return $schedules;
    }

    /**
     * Run system diagnostics
     */
    public function run_diagnostics() {
        $results = array();

        // 1. Check GitHub API connectivity
        $results['github_api'] = $this->test_github_api();

        // 2. Check temp directory
        $results['temp_dir'] = $this->test_temp_directory();

        // 3. Check plugin directory write access
        $results['plugin_dir'] = $this->test_plugin_directory();

        // 4. Check WP_Filesystem availability
        $results['wp_filesystem'] = $this->test_wp_filesystem();

        // 5. Check download_url function
        $results['download_url'] = $this->test_download_url();

        // 6. Check unzip capability
        $results['unzip'] = $this->test_unzip();

        // 7. Check each managed plugin
        $results['plugins'] = $this->test_managed_plugins();

        // 8. Check cron status
        $results['cron'] = $this->test_cron();

        return $results;
    }

    private function test_github_api() {
        $result = array('name' => 'GitHub API Connection', 'status' => 'unknown', 'message' => '');

        $url = sprintf('https://api.github.com/repos/%s/commits/%s', self::GITHUB_REPO, self::GITHUB_BRANCH);
        $headers = array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
        );

        $token = get_option('fra_github_token', '');
        if (!empty($token)) {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($url, array('headers' => $headers, 'timeout' => 15));

        if (is_wp_error($response)) {
            $result['status'] = 'error';
            $result['message'] = 'Connection failed: ' . $response->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && isset($body['sha'])) {
                $result['status'] = 'success';
                $result['message'] = 'Connected. Latest commit: ' . substr($body['sha'], 0, 8);
                $result['commit'] = $body['sha'];
                $result['commit_message'] = $body['commit']['message'] ?? '';
                $result['commit_date'] = $body['commit']['committer']['date'] ?? '';
            } elseif ($code === 403) {
                $result['status'] = 'warning';
                $result['message'] = 'Rate limited. Consider adding a GitHub token.';
            } elseif ($code === 401) {
                $result['status'] = 'error';
                $result['message'] = 'Invalid GitHub token.';
            } else {
                $result['status'] = 'error';
                $result['message'] = "HTTP {$code}: " . ($body['message'] ?? 'Unknown error');
            }
        }

        return $result;
    }

    private function test_temp_directory() {
        $result = array('name' => 'Temp Directory', 'status' => 'unknown', 'message' => '');

        $temp_dir = get_temp_dir();

        if (!is_dir($temp_dir)) {
            $result['status'] = 'error';
            $result['message'] = "Temp directory doesn't exist: {$temp_dir}";
            return $result;
        }

        if (!is_writable($temp_dir)) {
            $result['status'] = 'error';
            $result['message'] = "Temp directory not writable: {$temp_dir}";
            return $result;
        }

        // Try to create a test file
        $test_file = $temp_dir . 'fra-github-sync-test-' . time() . '.txt';
        $write_result = @file_put_contents($test_file, 'test');

        if ($write_result === false) {
            $result['status'] = 'error';
            $result['message'] = "Cannot write to temp directory: {$temp_dir}";
        } else {
            @unlink($test_file);
            $result['status'] = 'success';
            $result['message'] = "Writable: {$temp_dir}";
        }

        return $result;
    }

    private function test_plugin_directory() {
        $result = array('name' => 'Plugin Directory Write Access', 'status' => 'unknown', 'message' => '');

        $plugin_dir = WP_PLUGIN_DIR;

        if (!is_dir($plugin_dir)) {
            $result['status'] = 'error';
            $result['message'] = "Plugin directory doesn't exist";
            return $result;
        }

        // Check if writable using PHP
        if (is_writable($plugin_dir)) {
            $result['status'] = 'success';
            $result['message'] = 'Plugin directory is writable (PHP check)';
        } else {
            $result['status'] = 'warning';
            $result['message'] = 'Plugin directory not directly writable - will try WP_Filesystem';
        }

        // Try to create a test directory
        $test_dir = $plugin_dir . '/fra-github-sync-test-' . time();

        if (@mkdir($test_dir)) {
            @rmdir($test_dir);
            $result['status'] = 'success';
            $result['message'] = 'Can create directories in plugin folder';
        } else {
            // Try with WP_Filesystem
            WP_Filesystem();
            global $wp_filesystem;

            if ($wp_filesystem && $wp_filesystem->mkdir($test_dir)) {
                $wp_filesystem->rmdir($test_dir);
                $result['status'] = 'success';
                $result['message'] = 'Can create directories via WP_Filesystem';
            } else {
                $result['status'] = 'error';
                $result['message'] = 'Cannot create directories in plugin folder. Updates may fail.';
            }
        }

        return $result;
    }

    private function test_wp_filesystem() {
        $result = array('name' => 'WP_Filesystem', 'status' => 'unknown', 'message' => '');

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $creds = request_filesystem_credentials('', '', false, false, null);

        if ($creds === false) {
            $result['status'] = 'warning';
            $result['message'] = 'Filesystem credentials required (FTP/SSH)';
            return $result;
        }

        if (!WP_Filesystem($creds)) {
            $result['status'] = 'error';
            $result['message'] = 'Could not initialize WP_Filesystem';
            return $result;
        }

        global $wp_filesystem;
        $result['status'] = 'success';
        $result['message'] = 'Method: ' . get_class($wp_filesystem);

        return $result;
    }

    private function test_download_url() {
        $result = array('name' => 'File Download', 'status' => 'unknown', 'message' => '');

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Test with a small file
        $test_url = 'https://raw.githubusercontent.com/' . self::GITHUB_REPO . '/' . self::GITHUB_BRANCH . '/README.md';
        $tmp_file = download_url($test_url, 30);

        if (is_wp_error($tmp_file)) {
            $result['status'] = 'error';
            $result['message'] = 'Download failed: ' . $tmp_file->get_error_message();
        } else {
            $size = filesize($tmp_file);
            @unlink($tmp_file);
            $result['status'] = 'success';
            $result['message'] = "Download works. Test file size: {$size} bytes";
        }

        return $result;
    }

    private function test_unzip() {
        $result = array('name' => 'Unzip Capability', 'status' => 'unknown', 'message' => '');

        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Check for ZipArchive
        if (class_exists('ZipArchive')) {
            $result['status'] = 'success';
            $result['message'] = 'ZipArchive available';
        } elseif (function_exists('gzopen')) {
            $result['status'] = 'success';
            $result['message'] = 'PclZip available (fallback)';
        } else {
            $result['status'] = 'error';
            $result['message'] = 'No unzip method available';
        }

        return $result;
    }

    private function test_managed_plugins() {
        $results = array();

        foreach ($this->plugins as $folder => $plugin_info) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_info['file'];
            $plugin_dir = WP_PLUGIN_DIR . '/' . $folder;

            $result = array(
                'name' => $plugin_info['name'],
                'folder' => $folder,
                'status' => 'unknown',
                'message' => '',
                'version' => 'Unknown',
                'active' => false,
            );

            if (!file_exists($plugin_path)) {
                $result['status'] = 'error';
                $result['message'] = 'Plugin file not found';
            } else {
                // Get plugin data
                if (!function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                $plugin_data = get_plugin_data($plugin_path);
                $result['version'] = $plugin_data['Version'] ?? 'Unknown';
                $result['active'] = is_plugin_active($plugin_info['file']);

                // Check if directory is writable
                if (is_writable($plugin_dir)) {
                    $result['status'] = 'success';
                    $result['message'] = 'Writable';
                } else {
                    $result['status'] = 'warning';
                    $result['message'] = 'Directory not directly writable';
                }
            }

            $results[$folder] = $result;
        }

        return $results;
    }

    private function test_cron() {
        $result = array('name' => 'WordPress Cron', 'status' => 'unknown', 'message' => '');

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $result['status'] = 'warning';
            $result['message'] = 'WP_CRON is disabled. Ensure server cron is configured.';
        } else {
            $result['status'] = 'success';
            $result['message'] = 'WP_CRON is enabled';
        }

        $next = wp_next_scheduled('fra_github_sync_check');
        if ($next) {
            $result['next_check'] = date('Y-m-d H:i:s', $next);
            $result['message'] .= '. Next check: ' . $result['next_check'];
        }

        return $result;
    }

    /**
     * Get plugin versions from GitHub
     */
    public function get_github_versions() {
        $versions = array();
        $token = get_option('fra_github_token', '');

        foreach ($this->plugins as $folder => $plugin_info) {
            // Get the main plugin file content from GitHub
            $url = sprintf(
                'https://api.github.com/repos/%s/contents/%s/%s',
                self::GITHUB_REPO,
                $folder,
                basename($plugin_info['file'])
            );

            $headers = array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            );

            if (!empty($token)) {
                $headers['Authorization'] = 'token ' . $token;
            }

            $response = wp_remote_get($url, array('headers' => $headers, 'timeout' => 15));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['content'])) {
                    $content = base64_decode($body['content']);
                    if (preg_match('/Version:\s*([0-9.]+)/i', $content, $matches)) {
                        $versions[$folder] = $matches[1];
                    }
                }
            }
        }

        return $versions;
    }

    /**
     * Check GitHub for new commits
     */
    public function check_for_updates() {
        $this->log('Starting update check...');

        $github_commit = $this->get_latest_github_commit();

        if (!$github_commit) {
            $this->log('ERROR: Failed to fetch GitHub commit');
            return false;
        }

        $stored_commit = get_option('fra_github_last_commit', '');

        $this->log("GitHub: " . substr($github_commit, 0, 8) . " | Stored: " . ($stored_commit ? substr($stored_commit, 0, 8) : 'none'));

        if ($github_commit !== $stored_commit) {
            $this->log('New commit detected!');

            // Set notification flag
            update_option('fra_github_update_available', true);
            update_option('fra_github_new_commit', $github_commit);
            update_option('fra_github_last_check', current_time('mysql'));

            return true;
        }

        update_option('fra_github_last_check', current_time('mysql'));
        $this->log('No new commits');
        return false;
    }

    /**
     * Get latest commit SHA from GitHub
     */
    private function get_latest_github_commit() {
        $url = sprintf(
            'https://api.github.com/repos/%s/commits/%s',
            self::GITHUB_REPO,
            self::GITHUB_BRANCH
        );

        $headers = array(
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
        );

        $token = get_option('fra_github_token', '');
        if (!empty($token)) {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get($url, array('headers' => $headers, 'timeout' => 15));

        if (is_wp_error($response)) {
            $this->log('GitHub API error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['sha'])) {
            return $body['sha'];
        }

        return false;
    }

    /**
     * Update a single plugin from GitHub
     */
    public function update_plugin($folder) {
        $this->log("=== Starting update for: {$folder} ===");

        if (!isset($this->plugins[$folder])) {
            $this->log("ERROR: Unknown plugin: {$folder}");
            return array('success' => false, 'message' => 'Unknown plugin');
        }

        $plugin_info = $this->plugins[$folder];

        // Step 1: Download the repository zip
        $this->log("Step 1: Downloading repository...");

        $zip_url = sprintf(
            'https://github.com/%s/archive/refs/heads/%s.zip',
            self::GITHUB_REPO,
            self::GITHUB_BRANCH
        );

        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $tmp_file = download_url($zip_url, 60);

        if (is_wp_error($tmp_file)) {
            $error = 'Download failed: ' . $tmp_file->get_error_message();
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        $this->log("Download complete: " . filesize($tmp_file) . " bytes");

        // Step 2: Create temp directory and extract
        $this->log("Step 2: Extracting archive...");

        WP_Filesystem();
        global $wp_filesystem;

        $tmp_dir = get_temp_dir() . 'fra-sync-' . time() . '-' . wp_rand();

        if (!wp_mkdir_p($tmp_dir)) {
            @unlink($tmp_file);
            $error = 'Could not create temp directory';
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        $unzip_result = unzip_file($tmp_file, $tmp_dir);
        @unlink($tmp_file);

        if (is_wp_error($unzip_result)) {
            $wp_filesystem->delete($tmp_dir, true);
            $error = 'Unzip failed: ' . $unzip_result->get_error_message();
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        $this->log("Extraction complete");

        // Step 3: Find the extracted folder
        $this->log("Step 3: Locating plugin folder...");

        $extracted_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
        if (empty($extracted_dirs)) {
            $wp_filesystem->delete($tmp_dir, true);
            $error = 'No extracted directory found';
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        $source_dir = $extracted_dirs[0] . '/' . $folder;
        $plugin_dir = WP_PLUGIN_DIR . '/' . $folder;

        $this->log("Source: {$source_dir}");
        $this->log("Destination: {$plugin_dir}");

        if (!is_dir($source_dir)) {
            $wp_filesystem->delete($tmp_dir, true);
            $error = "Plugin folder not found in repository: {$folder}";
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        // Step 4: Deactivate plugin
        $this->log("Step 4: Deactivating plugin...");

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $was_active = is_plugin_active($plugin_info['file']);
        if ($was_active) {
            deactivate_plugins($plugin_info['file'], true);
            $this->log("Plugin deactivated");
        } else {
            $this->log("Plugin was not active");
        }

        // Step 5: Backup and replace
        $this->log("Step 5: Replacing plugin files...");

        $backup_dir = get_temp_dir() . 'fra-backup-' . $folder . '-' . time();

        // Try to backup existing plugin
        if (is_dir($plugin_dir)) {
            $this->log("Backing up existing plugin...");

            // Try direct copy first
            if (@rename($plugin_dir, $backup_dir)) {
                $this->log("Backup created (rename method)");
            } elseif ($wp_filesystem->move($plugin_dir, $backup_dir)) {
                $this->log("Backup created (WP_Filesystem method)");
            } else {
                // Try to delete instead
                $this->log("Could not backup, attempting direct delete...");
                if (!$wp_filesystem->delete($plugin_dir, true)) {
                    $wp_filesystem->delete($tmp_dir, true);
                    $error = 'Could not remove existing plugin directory';
                    $this->log("ERROR: {$error}");

                    // Try to reactivate
                    if ($was_active) {
                        activate_plugin($plugin_info['file'], '', false, true);
                    }

                    return array('success' => false, 'message' => $error);
                }
            }
        }

        // Copy new files
        $this->log("Copying new files...");

        $copy_result = copy_dir($source_dir, $plugin_dir);

        if (is_wp_error($copy_result)) {
            $error = 'Copy failed: ' . $copy_result->get_error_message();
            $this->log("ERROR: {$error}");

            // Try to restore backup
            if (is_dir($backup_dir)) {
                $this->log("Attempting to restore backup...");
                if ($wp_filesystem->move($backup_dir, $plugin_dir)) {
                    $this->log("Backup restored");
                }
            }

            $wp_filesystem->delete($tmp_dir, true);

            if ($was_active) {
                activate_plugin($plugin_info['file'], '', false, true);
            }

            return array('success' => false, 'message' => $error);
        }

        $this->log("Files copied successfully");

        // Step 6: Cleanup
        $this->log("Step 6: Cleaning up...");

        $wp_filesystem->delete($tmp_dir, true);
        if (is_dir($backup_dir)) {
            $wp_filesystem->delete($backup_dir, true);
        }

        // Step 7: Reactivate plugin
        $this->log("Step 7: Reactivating plugin...");

        if ($was_active) {
            $activate_result = activate_plugin($plugin_info['file'], '', false, true);
            if (is_wp_error($activate_result)) {
                $this->log("WARNING: Reactivation failed: " . $activate_result->get_error_message());
            } else {
                $this->log("Plugin reactivated");
            }
        }

        // Get new version
        $new_version = 'Unknown';
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_info['file'])) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_info['file']);
            $new_version = $plugin_data['Version'] ?? 'Unknown';
        }

        $this->log("=== Update complete! New version: {$new_version} ===");

        return array(
            'success' => true,
            'message' => "Updated to version {$new_version}",
            'version' => $new_version,
        );
    }

    /**
     * Update all plugins
     */
    public function update_all_plugins() {
        $results = array();

        foreach ($this->plugins as $folder => $plugin_info) {
            $results[$folder] = $this->update_plugin($folder);
        }

        // Update stored commit after successful updates
        $github_commit = $this->get_latest_github_commit();
        if ($github_commit) {
            update_option('fra_github_last_commit', $github_commit);
            update_option('fra_github_last_update', current_time('mysql'));
            update_option('fra_github_update_available', false);
        }

        return $results;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'GitHub Sync',
            'GitHub Sync',
            'manage_options',
            'fra-github-sync',
            array($this, 'render_admin_page')
        );
    }

    /**
     * AJAX handler
     */
    public function ajax_handler() {
        check_ajax_referer('fra_github_sync', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $action = isset($_POST['sync_action']) ? sanitize_text_field($_POST['sync_action']) : '';

        switch ($action) {
            case 'check_updates':
                $has_updates = $this->check_for_updates();
                wp_send_json_success(array(
                    'has_updates' => $has_updates,
                    'message' => $has_updates ? 'New updates available!' : 'Already up to date',
                ));
                break;

            case 'update_plugin':
                $folder = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
                $result = $this->update_plugin($folder);
                if ($result['success']) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error($result);
                }
                break;

            case 'update_all':
                $results = $this->update_all_plugins();
                wp_send_json_success(array('results' => $results));
                break;

            case 'run_diagnostics':
                $results = $this->run_diagnostics();
                wp_send_json_success(array('diagnostics' => $results));
                break;

            case 'clear_logs':
                delete_option('fra_github_sync_log');
                wp_send_json_success(array('message' => 'Logs cleared'));
                break;

            case 'clear_notice':
                delete_option('fra_github_update_available');
                wp_send_json_success(array('message' => 'Notice cleared'));
                break;

            case 'save_settings':
                $token = isset($_POST['github_token']) ? sanitize_text_field($_POST['github_token']) : '';
                $interval = isset($_POST['sync_interval']) ? sanitize_text_field($_POST['sync_interval']) : 'every_15_minutes';

                update_option('fra_github_token', $token);
                update_option('fra_sync_interval', $interval);

                // Reschedule cron with new interval
                wp_clear_scheduled_hook('fra_github_sync_check');
                wp_schedule_event(time(), $interval, 'fra_github_sync_check');

                wp_send_json_success(array('message' => 'Settings saved'));
                break;

            default:
                wp_send_json_error(array('message' => 'Unknown action'));
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'status';
        $tabs = array(
            'status' => 'Status',
            'plugins' => 'Plugins',
            'diagnostics' => 'Diagnostics',
            'settings' => 'Settings',
            'logs' => 'Logs',
        );
        ?>
        <div class="wrap">
            <h1>GitHub Sync <small style="font-size: 12px; color: #666;">v<?php echo self::VERSION; ?></small></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                    <a href="?page=fra-github-sync&tab=<?php echo $tab_id; ?>"
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                switch ($active_tab) {
                    case 'status':
                        $this->render_status_tab();
                        break;
                    case 'plugins':
                        $this->render_plugins_tab();
                        break;
                    case 'diagnostics':
                        $this->render_diagnostics_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'logs':
                        $this->render_logs_tab();
                        break;
                }
                ?>
            </div>
        </div>

        <style>
            .fra-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-bottom: 20px; max-width: 800px; }
            .fra-card h2 { margin-top: 0; }
            .fra-status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
            .fra-status-success { background: #d4edda; color: #155724; }
            .fra-status-warning { background: #fff3cd; color: #856404; }
            .fra-status-error { background: #f8d7da; color: #721c24; }
            .fra-status-unknown { background: #e2e3e5; color: #383d41; }
            .fra-log-entry { font-family: monospace; font-size: 12px; padding: 3px 0; border-bottom: 1px solid #eee; }
            .fra-log-time { color: #666; }
            .fra-plugin-row { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
            .fra-plugin-row:last-child { border-bottom: none; }
            .fra-plugin-info { flex: 1; }
            .fra-plugin-actions { margin-left: 20px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo wp_create_nonce('fra_github_sync'); ?>';

            function fraAjax(action, data, callback) {
                data = data || {};
                data.action = 'fra_github_sync';
                data.nonce = nonce;
                data.sync_action = action;

                $.post(ajaxurl, data, function(response) {
                    if (callback) callback(response);
                }).fail(function() {
                    alert('Request failed. Please try again.');
                });
            }

            // Check for updates
            $('#fra-check-updates').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Checking...');

                fraAjax('check_updates', {}, function(response) {
                    $btn.prop('disabled', false).text('Check for Updates');
                    if (response.success) {
                        alert(response.data.message);
                        if (response.data.has_updates) {
                            location.reload();
                        }
                    }
                });
            });

            // Update single plugin
            $('.fra-update-plugin').on('click', function() {
                var $btn = $(this);
                var plugin = $btn.data('plugin');
                var $status = $btn.closest('.fra-plugin-row').find('.fra-plugin-status');

                if (!confirm('Update ' + plugin + '? The plugin will be briefly deactivated.')) {
                    return;
                }

                $btn.prop('disabled', true).text('Updating...');
                $status.html('<span class="fra-status-badge fra-status-warning">Updating...</span>');

                fraAjax('update_plugin', { plugin: plugin }, function(response) {
                    $btn.prop('disabled', false).text('Update');
                    if (response.success) {
                        $status.html('<span class="fra-status-badge fra-status-success">Updated to ' + response.data.version + '</span>');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        $status.html('<span class="fra-status-badge fra-status-error">Failed: ' + response.data.message + '</span>');
                    }
                });
            });

            // Update all plugins
            $('#fra-update-all').on('click', function() {
                var $btn = $(this);

                if (!confirm('Update all plugins? They will be briefly deactivated during update.')) {
                    return;
                }

                $btn.prop('disabled', true).text('Updating All...');

                fraAjax('update_all', {}, function(response) {
                    $btn.prop('disabled', false).text('Update All Plugins');
                    if (response.success) {
                        alert('Updates complete! Page will reload.');
                        location.reload();
                    }
                });
            });

            // Run diagnostics
            $('#fra-run-diagnostics').on('click', function() {
                var $btn = $(this);
                var $results = $('#fra-diagnostics-results');

                $btn.prop('disabled', true).text('Running...');
                $results.html('<p>Running diagnostics...</p>');

                fraAjax('run_diagnostics', {}, function(response) {
                    $btn.prop('disabled', false).text('Run Diagnostics');
                    if (response.success) {
                        var html = '';
                        var diag = response.data.diagnostics;

                        // System tests
                        var tests = ['github_api', 'temp_dir', 'plugin_dir', 'wp_filesystem', 'download_url', 'unzip', 'cron'];
                        tests.forEach(function(key) {
                            if (diag[key]) {
                                var d = diag[key];
                                html += '<div style="padding: 10px; border-bottom: 1px solid #eee;">';
                                html += '<strong>' + d.name + '</strong>: ';
                                html += '<span class="fra-status-badge fra-status-' + d.status + '">' + d.status.toUpperCase() + '</span> ';
                                html += '<span style="color: #666;">' + d.message + '</span>';
                                html += '</div>';
                            }
                        });

                        // Plugin tests
                        if (diag.plugins) {
                            html += '<h3 style="margin-top: 20px;">Managed Plugins</h3>';
                            for (var folder in diag.plugins) {
                                var p = diag.plugins[folder];
                                html += '<div style="padding: 10px; border-bottom: 1px solid #eee;">';
                                html += '<strong>' + p.name + '</strong> (v' + p.version + ') ';
                                html += '<span class="fra-status-badge fra-status-' + d.status + '">' + (p.active ? 'Active' : 'Inactive') + '</span> ';
                                html += '<span style="color: #666;">' + p.message + '</span>';
                                html += '</div>';
                            }
                        }

                        $results.html(html);
                    }
                });
            });

            // Save settings
            $('#fra-save-settings').on('click', function() {
                var $btn = $(this);
                var token = $('#fra-github-token').val();
                var interval = $('#fra-sync-interval').val();

                $btn.prop('disabled', true).text('Saving...');

                fraAjax('save_settings', { github_token: token, sync_interval: interval }, function(response) {
                    $btn.prop('disabled', false).text('Save Settings');
                    if (response.success) {
                        alert('Settings saved!');
                    }
                });
            });

            // Clear logs
            $('#fra-clear-logs').on('click', function() {
                fraAjax('clear_logs', {}, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });

            // Clear notice
            $('#fra-clear-notice').on('click', function() {
                fraAjax('clear_notice', {}, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }

    private function render_status_tab() {
        $last_check = get_option('fra_github_last_check', 'Never');
        $last_update = get_option('fra_github_last_update', 'Never');
        $last_commit = get_option('fra_github_last_commit', '');
        $update_available = get_option('fra_github_update_available', false);
        $next_check = wp_next_scheduled('fra_github_sync_check');
        ?>
        <div class="fra-card">
            <h2>Sync Status</h2>

            <?php if ($update_available) : ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <strong>⚠️ Updates Available!</strong> New commits have been detected on GitHub.
                <button type="button" class="button" id="fra-clear-notice" style="margin-left: 10px;">Dismiss</button>
            </div>
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th>Repository</th>
                    <td><code><?php echo esc_html(self::GITHUB_REPO); ?></code></td>
                </tr>
                <tr>
                    <th>Branch</th>
                    <td><code><?php echo esc_html(self::GITHUB_BRANCH); ?></code></td>
                </tr>
                <tr>
                    <th>Last Check</th>
                    <td><?php echo esc_html($last_check); ?></td>
                </tr>
                <tr>
                    <th>Last Update</th>
                    <td><?php echo esc_html($last_update); ?></td>
                </tr>
                <tr>
                    <th>Last Commit</th>
                    <td><code><?php echo $last_commit ? esc_html(substr($last_commit, 0, 8)) : 'Unknown'; ?></code></td>
                </tr>
                <tr>
                    <th>Next Scheduled Check</th>
                    <td><?php echo $next_check ? esc_html(date('Y-m-d H:i:s', $next_check)) : 'Not scheduled'; ?></td>
                </tr>
            </table>

            <p style="margin-top: 20px;">
                <button type="button" class="button button-primary" id="fra-check-updates">Check for Updates</button>
                <button type="button" class="button button-primary" id="fra-update-all" style="margin-left: 10px;">Update All Plugins</button>
            </p>
        </div>

        <div class="fra-card">
            <h2>How It Works</h2>
            <ol>
                <li>This plugin polls GitHub for new commits on the <code>main</code> branch</li>
                <li>When a new commit is detected, you'll see a notification</li>
                <li>Click "Update All Plugins" to download and install the latest code</li>
                <li>Plugins are automatically reactivated after updates</li>
            </ol>
            <p><strong>No WP Pusher required!</strong> This plugin handles everything directly.</p>
        </div>
        <?php
    }

    private function render_plugins_tab() {
        ?>
        <div class="fra-card">
            <h2>Managed Plugins</h2>

            <?php foreach ($this->plugins as $folder => $plugin_info) :
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_info['file'];
                $version = 'Not installed';
                $active = false;

                if (file_exists($plugin_path)) {
                    if (!function_exists('get_plugin_data')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    $plugin_data = get_plugin_data($plugin_path);
                    $version = $plugin_data['Version'] ?? 'Unknown';
                    $active = is_plugin_active($plugin_info['file']);
                }
            ?>
            <div class="fra-plugin-row">
                <div class="fra-plugin-info">
                    <strong><?php echo esc_html($plugin_info['name']); ?></strong><br>
                    <span style="color: #666;">
                        Folder: <code><?php echo esc_html($folder); ?></code> |
                        Version: <code><?php echo esc_html($version); ?></code> |
                        Status: <?php echo $active ? '<span style="color: green;">Active</span>' : '<span style="color: #999;">Inactive</span>'; ?>
                    </span>
                </div>
                <div class="fra-plugin-actions">
                    <span class="fra-plugin-status"></span>
                    <button type="button" class="button fra-update-plugin" data-plugin="<?php echo esc_attr($folder); ?>">
                        Update
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_diagnostics_tab() {
        ?>
        <div class="fra-card">
            <h2>System Diagnostics</h2>
            <p>Run diagnostics to check if your WordPress installation can perform automatic updates from GitHub.</p>

            <p>
                <button type="button" class="button button-primary" id="fra-run-diagnostics">Run Diagnostics</button>
            </p>

            <div id="fra-diagnostics-results" style="margin-top: 20px;">
                <p style="color: #666;">Click "Run Diagnostics" to test your system.</p>
            </div>
        </div>

        <div class="fra-card">
            <h2>What's Being Tested</h2>
            <ul>
                <li><strong>GitHub API Connection</strong> - Can we connect to GitHub and fetch commit info?</li>
                <li><strong>Temp Directory</strong> - Can we create temporary files for downloads?</li>
                <li><strong>Plugin Directory</strong> - Can we write to the WordPress plugins folder?</li>
                <li><strong>WP_Filesystem</strong> - Is WordPress file system abstraction working?</li>
                <li><strong>File Download</strong> - Can we download files from GitHub?</li>
                <li><strong>Unzip Capability</strong> - Can we extract ZIP archives?</li>
                <li><strong>WordPress Cron</strong> - Is the scheduled update check working?</li>
            </ul>
        </div>
        <?php
    }

    private function render_settings_tab() {
        $token = get_option('fra_github_token', '');
        $interval = get_option('fra_sync_interval', 'every_15_minutes');
        ?>
        <div class="fra-card">
            <h2>Settings</h2>

            <table class="form-table">
                <tr>
                    <th>GitHub Personal Access Token</th>
                    <td>
                        <input type="password" id="fra-github-token" value="<?php echo esc_attr($token); ?>" class="regular-text">
                        <p class="description">
                            Optional. Required for private repos or to avoid rate limits.<br>
                            <a href="https://github.com/settings/tokens/new" target="_blank">Create a token</a> with <code>repo</code> scope.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Check Interval</th>
                    <td>
                        <select id="fra-sync-interval">
                            <option value="every_5_minutes" <?php selected($interval, 'every_5_minutes'); ?>>Every 5 minutes</option>
                            <option value="every_15_minutes" <?php selected($interval, 'every_15_minutes'); ?>>Every 15 minutes</option>
                            <option value="every_30_minutes" <?php selected($interval, 'every_30_minutes'); ?>>Every 30 minutes</option>
                            <option value="hourly" <?php selected($interval, 'hourly'); ?>>Every hour</option>
                        </select>
                        <p class="description">How often to check GitHub for new commits.</p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="button" class="button button-primary" id="fra-save-settings">Save Settings</button>
            </p>
        </div>
        <?php
    }

    private function render_logs_tab() {
        $logs = $this->get_logs();
        ?>
        <div class="fra-card">
            <h2>Activity Logs</h2>

            <?php if (empty($logs)) : ?>
                <p style="color: #666;">No logs yet.</p>
            <?php else : ?>
                <div style="max-height: 500px; overflow-y: auto; background: #f5f5f5; padding: 15px; font-family: monospace; font-size: 12px;">
                    <?php foreach (array_reverse($logs) as $log) : ?>
                        <div class="fra-log-entry">
                            <span class="fra-log-time">[<?php echo esc_html($log['time']); ?>]</span>
                            <?php echo esc_html($log['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top: 15px;">
                    <button type="button" class="button" id="fra-clear-logs">Clear Logs</button>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Show admin notice when updates are available
     */
    public function show_update_notice() {
        $update_available = get_option('fra_github_update_available', false);

        if ($update_available && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>🔄 France Relocation plugins have updates available!</strong>
                    <a href="<?php echo admin_url('tools.php?page=fra-github-sync'); ?>" class="button button-primary" style="margin-left: 10px;">
                        Go to GitHub Sync
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Log messages
     */
    private function log($message) {
        $logs = get_option('fra_github_sync_log', array());
        $logs[] = array(
            'time' => current_time('mysql'),
            'message' => $message,
        );
        // Keep only last 100 entries
        $logs = array_slice($logs, -100);
        update_option('fra_github_sync_log', $logs);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FRA GitHub Sync] ' . $message);
        }
    }

    /**
     * Get logs
     */
    public function get_logs() {
        return get_option('fra_github_sync_log', array());
    }

    /**
     * Cleanup on deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('fra_github_sync_check');
    }
}

// Initialize
FRA_GitHub_Sync::get_instance();
