<?php
/**
 * France Relocation GitHub Sync
 *
 * Custom GitHub sync solution for WordPress - automatically updates plugins
 * and themes from GitHub without requiring WP Pusher. Includes diagnostics,
 * version tracking, local backups, and detailed logging.
 *
 * @package     FranceRelocation
 * @subpackage  GitHubSync
 * @author      Relo2France
 * @copyright   2024 Relo2France
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: France Relocation GitHub Sync
 * Plugin URI:  https://relo2france.com
 * Description: Custom GitHub sync solution - automatically updates plugins from GitHub without requiring WP Pusher. Includes diagnostics, version tracking, local backups, and detailed logging.
 * Version:     2.2.0
 * Author:      Relo2France
 * Author URI:  https://relo2france.com
 * License:     GPL v2 or later
 * Text Domain: fra-github-sync
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class using Singleton pattern.
 *
 * Handles GitHub synchronization for plugins and themes including
 * automatic updates, backups, and restore functionality.
 *
 * @since 1.0.0
 */
class FRA_GitHub_Sync {

    /**
     * Singleton instance.
     *
     * @since 1.0.0
     * @var FRA_GitHub_Sync|null
     */
    private static $instance = null;

    /**
     * GitHub repository path.
     *
     * @since 1.0.0
     * @var string
     */
    const GITHUB_REPO = 'Relo2France/france-relocation-assistant';

    /**
     * GitHub branch to sync from.
     *
     * @since 1.0.0
     * @var string
     */
    const GITHUB_BRANCH = 'main';

    /**
     * Plugin version.
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '2.2.0';

    /**
     * Backup folder name within uploads directory.
     *
     * @since 2.0.0
     * @var string
     */
    const BACKUP_FOLDER = 'fra-github-sync-backups';

    /**
     * Encryption method for token storage.
     *
     * @since 2.2.0
     * @var string
     */
    const ENCRYPT_METHOD = 'AES-256-CBC';

    /**
     * Plugins to sync from GitHub.
     *
     * @since 1.0.0
     * @var array
     */
    private $plugins = array(
        'france-relocation-assistant-plugin' => array(
            'file' => 'france-relocation-assistant-plugin/france-relocation-assistant.php',
            'name' => 'France Relocation Assistant',
        ),
        'france-relocation-member-tools' => array(
            'file' => 'france-relocation-member-tools/france-relocation-member-tools.php',
            'name' => 'France Relocation Member Tools',
        ),
        'france-relocation-github-sync' => array(
            'file' => 'france-relocation-github-sync/france-relocation-github-sync.php',
            'name' => 'France Relocation GitHub Sync',
        ),
        'relo2france-schengen-tracker' => array(
            'file' => 'relo2france-schengen-tracker/relo2france-schengen-tracker.php',
            'name' => 'Relo2France Schengen Tracker',
        ),
    );

    /**
     * Themes to sync from GitHub.
     *
     * @since 2.1.0
     * @var array
     */
    private $themes = array(
        'relo2france-theme' => array(
            'name' => 'Relo2France Theme',
        ),
    );

    /**
     * Get singleton instance.
     *
     * @since 1.0.0
     * @return FRA_GitHub_Sync Plugin instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - sets up hooks and actions.
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Schedule cron event.
        add_action( 'init', array( $this, 'schedule_sync' ) );
        add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

        // Hook for the cron job.
        add_action( 'fra_github_sync_check', array( $this, 'check_for_updates' ) );

        // Admin menu.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_fra_github_sync', array( $this, 'ajax_handler' ) );

        // Admin notice when updates are available.
        add_action( 'admin_notices', array( $this, 'show_update_notice' ) );

        // Settings.
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // Cleanup on deactivation.
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Register plugin settings.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting( 'fra_github_sync_settings', 'fra_github_token_encrypted' );
        register_setting( 'fra_github_sync_settings', 'fra_sync_interval' );
        register_setting( 'fra_github_sync_settings', 'fra_keep_backups' );
    }

    /**
     * Encrypt a value for secure storage.
     *
     * @since 2.2.0
     * @param string $value Value to encrypt.
     * @return string Encrypted value (base64 encoded).
     */
    private function encrypt_value( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $key = wp_salt( 'auth' );
        $iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::ENCRYPT_METHOD ) );

        $encrypted = openssl_encrypt( $value, self::ENCRYPT_METHOD, $key, 0, $iv );

        // Combine IV and encrypted data.
        return base64_encode( $iv . '::' . $encrypted );
    }

    /**
     * Decrypt a stored value.
     *
     * @since 2.2.0
     * @param string $encrypted_value Encrypted value (base64 encoded).
     * @return string Decrypted value.
     */
    private function decrypt_value( $encrypted_value ) {
        if ( empty( $encrypted_value ) ) {
            return '';
        }

        $key  = wp_salt( 'auth' );
        $data = base64_decode( $encrypted_value );

        if ( false === strpos( $data, '::' ) ) {
            // Fallback for unencrypted legacy tokens.
            return $encrypted_value;
        }

        list( $iv, $encrypted ) = explode( '::', $data, 2 );

        return openssl_decrypt( $encrypted, self::ENCRYPT_METHOD, $key, 0, $iv );
    }

    /**
     * Get the GitHub token (decrypted).
     *
     * @since 2.2.0
     * @return string Decrypted GitHub token.
     */
    private function get_github_token() {
        // Try new encrypted option first.
        $encrypted = get_option( 'fra_github_token_encrypted', '' );
        if ( ! empty( $encrypted ) ) {
            return $this->decrypt_value( $encrypted );
        }

        // Fallback to old unencrypted option and migrate.
        $legacy_token = get_option( 'fra_github_token', '' );
        if ( ! empty( $legacy_token ) ) {
            $this->save_github_token( $legacy_token );
            delete_option( 'fra_github_token' );
            return $legacy_token;
        }

        return '';
    }

    /**
     * Save the GitHub token (encrypted).
     *
     * @since 2.2.0
     * @param string $token GitHub token to save.
     * @return bool Success status.
     */
    private function save_github_token( $token ) {
        if ( empty( $token ) ) {
            delete_option( 'fra_github_token_encrypted' );
            return true;
        }

        return update_option( 'fra_github_token_encrypted', $this->encrypt_value( $token ) );
    }

    /**
     * Get backup directory path.
     *
     * @since 2.0.0
     * @return string Full path to backup directory.
     */
    private function get_backup_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/' . self::BACKUP_FOLDER;
    }

    /**
     * Ensure backup directory exists with security measures.
     *
     * @since 2.0.0
     * @return string Full path to backup directory.
     */
    private function ensure_backup_dir() {
        $backup_dir = $this->get_backup_dir();

        if ( ! is_dir( $backup_dir ) ) {
            wp_mkdir_p( $backup_dir );

            // Add index.php for security.
            $index_file = $backup_dir . '/index.php';
            if ( ! file_exists( $index_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                file_put_contents( $index_file, '<?php // Silence is golden' );
            }

            // Add .htaccess for Apache servers.
            $htaccess_file = $backup_dir . '/.htaccess';
            if ( ! file_exists( $htaccess_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
                file_put_contents( $htaccess_file, 'Deny from all' );
            }
        }

        return $backup_dir;
    }

    /**
     * Get list of backups for a plugin.
     *
     * @since 2.0.0
     * @param string|null $folder Optional. Plugin folder to filter by.
     * @return array List of backup information arrays.
     */
    public function get_backups( $folder = null ) {
        $backup_dir = $this->get_backup_dir();
        $backups = array();

        if (!is_dir($backup_dir)) {
            return $backups;
        }

        $files = glob($backup_dir . '/*.zip');
        foreach ($files as $file) {
            $filename = basename($file);
            // Format: pluginfolder-version-timestamp.zip
            if (preg_match('/^(.+?)-(\d+\.\d+\.\d+)-(\d+)\.zip$/', $filename, $matches)) {
                $plugin_folder = $matches[1];

                if ($folder !== null && $plugin_folder !== $folder) {
                    continue;
                }

                $backups[] = array(
                    'file' => $file,
                    'filename' => $filename,
                    'folder' => $plugin_folder,
                    'version' => $matches[2],
                    'timestamp' => intval($matches[3]),
                    'date' => date('Y-m-d H:i:s', intval($matches[3])),
                    'size' => filesize($file),
                );
            }
        }

        // Sort by timestamp descending (newest first)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Create backup of a plugin before updating
     */
    public function create_backup($folder) {
        $this->log("Creating backup for: {$folder}");

        $plugin_dir = WP_PLUGIN_DIR . '/' . $folder;

        if (!is_dir($plugin_dir)) {
            $this->log("ERROR: Plugin directory not found for backup");
            return false;
        }

        // Get current version
        $plugin_info = $this->plugins[$folder] ?? null;
        $version = 'unknown';

        if ($plugin_info && file_exists(WP_PLUGIN_DIR . '/' . $plugin_info['file'])) {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_info['file']);
            $version = $plugin_data['Version'] ?? 'unknown';
        }

        $backup_dir = $this->ensure_backup_dir();
        $backup_file = $backup_dir . '/' . $folder . '-' . $version . '-' . time() . '.zip';

        // Create zip file
        if (!class_exists('ZipArchive')) {
            $this->log("ERROR: ZipArchive not available for backup");
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->log("ERROR: Could not create backup zip file");
            return false;
        }

        // Add all files from plugin directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $folder . '/' . substr($filePath, strlen($plugin_dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $this->log("Backup created: " . basename($backup_file) . " (" . round(filesize($backup_file) / 1024) . " KB)");

        // Clean old backups (keep last 3 per plugin)
        $this->cleanup_old_backups($folder, 3);

        return $backup_file;
    }

    /**
     * Cleanup old backups, keeping only the most recent ones
     */
    private function cleanup_old_backups($folder, $keep = 3) {
        $backups = $this->get_backups($folder);

        if (count($backups) > $keep) {
            $to_delete = array_slice($backups, $keep);
            foreach ($to_delete as $backup) {
                @unlink($backup['file']);
                $this->log("Deleted old backup: " . $backup['filename']);
            }
        }
    }

    /**
     * Restore a plugin from backup
     */
    public function restore_backup($backup_file) {
        $this->log("=== Restoring from backup: " . basename($backup_file) . " ===");

        if (!file_exists($backup_file)) {
            $this->log("ERROR: Backup file not found");
            return array('success' => false, 'message' => 'Backup file not found');
        }

        // Extract folder name from filename
        $filename = basename($backup_file);
        if (!preg_match('/^(.+?)-\d+\.\d+\.\d+-\d+\.zip$/', $filename, $matches)) {
            $this->log("ERROR: Invalid backup filename format");
            return array('success' => false, 'message' => 'Invalid backup filename');
        }

        $folder = $matches[1];
        $plugin_info = $this->plugins[$folder] ?? null;

        if (!$plugin_info) {
            $this->log("ERROR: Unknown plugin folder: {$folder}");
            return array('success' => false, 'message' => 'Unknown plugin');
        }

        // Deactivate plugin
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $was_active = is_plugin_active($plugin_info['file']);
        if ($was_active) {
            deactivate_plugins($plugin_info['file'], true);
            $this->log("Plugin deactivated");
        }

        // Remove current plugin
        WP_Filesystem();
        global $wp_filesystem;

        $plugin_dir = WP_PLUGIN_DIR . '/' . $folder;

        if (is_dir($plugin_dir)) {
            $wp_filesystem->delete($plugin_dir, true);
            $this->log("Existing plugin directory removed");
        }

        // Extract backup
        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $result = unzip_file($backup_file, WP_PLUGIN_DIR);

        if (is_wp_error($result)) {
            $this->log("ERROR: Restore failed: " . $result->get_error_message());
            return array('success' => false, 'message' => $result->get_error_message());
        }

        $this->log("Backup extracted successfully");

        // Reactivate plugin
        if ($was_active) {
            activate_plugin($plugin_info['file'], '', false, true);
            $this->log("Plugin reactivated");
        }

        // Get restored version
        $version = 'Unknown';
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_info['file'])) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_info['file']);
            $version = $plugin_data['Version'] ?? 'Unknown';
        }

        $this->log("=== Restore complete! Version: {$version} ===");

        return array(
            'success' => true,
            'message' => "Restored to version {$version}",
            'version' => $version,
        );
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
     * Run system diagnostics with logging
     */
    public function run_diagnostics() {
        $results = array();
        $this->log("[DIAG] Starting diagnostics...");

        // 1. Check GitHub API connectivity
        $this->log("[DIAG] Testing GitHub API...");
        $results['github_api'] = $this->test_github_api();
        $this->log("[DIAG] GitHub API: " . $results['github_api']['status']);

        // 2. Check temp directory
        $this->log("[DIAG] Testing temp directory...");
        $results['temp_dir'] = $this->test_temp_directory();
        $this->log("[DIAG] Temp dir: " . $results['temp_dir']['status']);

        // 3. Check plugin directory write access
        $this->log("[DIAG] Testing plugin directory...");
        $results['plugin_dir'] = $this->test_plugin_directory();
        $this->log("[DIAG] Plugin dir: " . $results['plugin_dir']['status']);

        // 4. Check WP_Filesystem availability (simplified - no credentials check)
        $this->log("[DIAG] Testing WP_Filesystem...");
        $results['wp_filesystem'] = $this->test_wp_filesystem_simple();
        $this->log("[DIAG] WP_Filesystem: " . $results['wp_filesystem']['status']);

        // 5. Check unzip capability (quick check, no download)
        $this->log("[DIAG] Testing unzip capability...");
        $results['unzip'] = $this->test_unzip();
        $this->log("[DIAG] Unzip: " . $results['unzip']['status']);

        // 6. Check backup directory
        $this->log("[DIAG] Testing backup directory...");
        $results['backup_dir'] = $this->test_backup_directory();
        $this->log("[DIAG] Backup dir: " . $results['backup_dir']['status']);

        // 7. Check each managed plugin
        $this->log("[DIAG] Testing managed plugins...");
        $results['plugins'] = $this->test_managed_plugins();
        $this->log("[DIAG] Plugins checked");

        // 8. Check cron status
        $this->log("[DIAG] Testing cron...");
        $results['cron'] = $this->test_cron();
        $this->log("[DIAG] Cron: " . $results['cron']['status']);

        $this->log("[DIAG] Diagnostics complete!");

        return $results;
    }

    private function test_github_api() {
        $result = array('name' => 'GitHub API Connection', 'status' => 'unknown', 'message' => '');

        try {
            $url = sprintf('https://api.github.com/repos/%s/commits/%s', self::GITHUB_REPO, self::GITHUB_BRANCH);
            $headers = array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            );

            $token = $this->get_github_token();
            if ( ! empty( $token ) ) {
                $headers['Authorization'] = 'token ' . $token;
            }

            $response = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 10 ) );

            if (is_wp_error($response)) {
                $result['status'] = 'error';
                $result['message'] = 'Connection failed: ' . $response->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $body = json_decode(wp_remote_retrieve_body($response), true);

                if ($code === 200 && isset($body['sha'])) {
                    $result['status'] = 'success';
                    $result['message'] = 'Connected. Latest commit: ' . substr($body['sha'], 0, 8);
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
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_temp_directory() {
        $result = array('name' => 'Temp Directory', 'status' => 'unknown', 'message' => '');

        try {
            $temp_dir = get_temp_dir();

            if (!is_dir($temp_dir)) {
                $result['status'] = 'error';
                $result['message'] = "Temp directory doesn't exist";
                return $result;
            }

            if (!is_writable($temp_dir)) {
                $result['status'] = 'error';
                $result['message'] = "Temp directory not writable";
                return $result;
            }

            // Quick write test
            $test_file = $temp_dir . 'fra-test-' . time() . '.txt';
            if (@file_put_contents($test_file, 'test') !== false) {
                @unlink($test_file);
                $result['status'] = 'success';
                $result['message'] = "Writable: {$temp_dir}";
            } else {
                $result['status'] = 'error';
                $result['message'] = "Cannot write to temp directory";
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_plugin_directory() {
        $result = array('name' => 'Plugin Directory', 'status' => 'unknown', 'message' => '');

        try {
            $plugin_dir = WP_PLUGIN_DIR;

            // Try to create a test directory
            $test_dir = $plugin_dir . '/fra-test-' . time();

            if (@mkdir($test_dir)) {
                @rmdir($test_dir);
                $result['status'] = 'success';
                $result['message'] = 'Can create directories in plugin folder';
            } else {
                $result['status'] = 'warning';
                $result['message'] = 'Cannot directly create directories (may work via WP_Filesystem)';
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_wp_filesystem_simple() {
        $result = array('name' => 'WP_Filesystem', 'status' => 'unknown', 'message' => '');

        try {
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            // Simple initialization without credentials prompt
            WP_Filesystem();
            global $wp_filesystem;

            if ($wp_filesystem) {
                $result['status'] = 'success';
                $result['message'] = 'Available. Method: ' . get_class($wp_filesystem);
            } else {
                $result['status'] = 'warning';
                $result['message'] = 'Could not initialize (may require FTP credentials)';
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_unzip() {
        $result = array('name' => 'Unzip Capability', 'status' => 'unknown', 'message' => '');

        try {
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
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_backup_directory() {
        $result = array('name' => 'Backup Directory', 'status' => 'unknown', 'message' => '');

        try {
            $backup_dir = $this->ensure_backup_dir();

            if (is_dir($backup_dir) && is_writable($backup_dir)) {
                $backups = $this->get_backups();
                $result['status'] = 'success';
                $result['message'] = "Ready. " . count($backups) . " backup(s) stored.";
            } else {
                $result['status'] = 'error';
                $result['message'] = "Cannot write to backup directory";
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
    }

    private function test_managed_plugins() {
        $results = array();

        foreach ($this->plugins as $folder => $plugin_info) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_info['file'];

            $result = array(
                'name' => $plugin_info['name'],
                'folder' => $folder,
                'status' => 'unknown',
                'message' => '',
                'version' => 'Unknown',
                'active' => false,
            );

            try {
                if (!file_exists($plugin_path)) {
                    $result['status'] = 'error';
                    $result['message'] = 'Not installed';
                } else {
                    if (!function_exists('get_plugin_data')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }

                    $plugin_data = get_plugin_data($plugin_path);
                    $result['version'] = $plugin_data['Version'] ?? 'Unknown';
                    $result['active'] = is_plugin_active($plugin_info['file']);
                    $result['status'] = 'success';
                    $result['message'] = $result['active'] ? 'Active' : 'Inactive';
                }
            } catch (Exception $e) {
                $result['status'] = 'error';
                $result['message'] = 'Exception: ' . $e->getMessage();
            }

            $results[$folder] = $result;
        }

        return $results;
    }

    private function test_cron() {
        $result = array('name' => 'WordPress Cron', 'status' => 'unknown', 'message' => '');

        try {
            if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                $result['status'] = 'warning';
                $result['message'] = 'WP_CRON disabled. Using server cron?';
            } else {
                $result['status'] = 'success';
                $result['message'] = 'WP_CRON enabled';
            }

            $next = wp_next_scheduled('fra_github_sync_check');
            if ($next) {
                $result['message'] .= '. Next: ' . date('H:i:s', $next);
            }
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = 'Exception: ' . $e->getMessage();
        }

        return $result;
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
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
        );

        $token = $this->get_github_token();
        if ( ! empty( $token ) ) {
            $headers['Authorization'] = 'token ' . $token;
        }

        $response = wp_remote_get( $url, array( 'headers' => $headers, 'timeout' => 15 ) );

        if (is_wp_error($response)) {
            $this->log('GitHub API error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['sha'] ?? false;
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

        // Step 0: Create backup
        $keep_backups = get_option('fra_keep_backups', true);
        if ($keep_backups) {
            $this->log("Step 0: Creating backup...");
            $backup_file = $this->create_backup($folder);
            if (!$backup_file) {
                $this->log("WARNING: Backup failed, continuing anyway...");
            }
        }

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

        // Step 5: Replace plugin files
        $this->log("Step 5: Replacing plugin files...");

        if (is_dir($plugin_dir)) {
            $this->log("Removing existing plugin...");
            if (!$wp_filesystem->delete($plugin_dir, true)) {
                $wp_filesystem->delete($tmp_dir, true);
                $error = 'Could not remove existing plugin directory';
                $this->log("ERROR: {$error}");
                if ($was_active) {
                    activate_plugin($plugin_info['file'], '', false, true);
                }
                return array('success' => false, 'message' => $error);
            }
        }

        $this->log("Copying new files...");
        $copy_result = copy_dir($source_dir, $plugin_dir);

        if (is_wp_error($copy_result)) {
            $error = 'Copy failed: ' . $copy_result->get_error_message();
            $this->log("ERROR: {$error}");
            $wp_filesystem->delete($tmp_dir, true);
            return array('success' => false, 'message' => $error);
        }

        $this->log("Files copied successfully");

        // Step 6: Cleanup
        $this->log("Step 6: Cleaning up...");
        $wp_filesystem->delete($tmp_dir, true);

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

        // Update stored commit after updates
        $github_commit = $this->get_latest_github_commit();
        if ($github_commit) {
            update_option('fra_github_last_commit', $github_commit);
            update_option('fra_github_last_update', current_time('mysql'));
            update_option('fra_github_update_available', false);
        }

        return $results;
    }

    /**
     * Update a theme from GitHub
     */
    public function update_theme($folder) {
        $this->log("=== Starting theme update for: {$folder} ===");

        if (!isset($this->themes[$folder])) {
            $this->log("ERROR: Unknown theme: {$folder}");
            return array('success' => false, 'message' => 'Unknown theme');
        }

        $theme_info = $this->themes[$folder];
        $theme_dir = get_theme_root() . '/' . $folder;

        // Step 0: Create backup
        $keep_backups = get_option('fra_keep_backups', true);
        if ($keep_backups) {
            $this->log("Step 0: Creating theme backup...");
            $backup_file = $this->create_theme_backup($folder);
            if (!$backup_file) {
                $this->log("WARNING: Theme backup failed, continuing anyway...");
            }
        }

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

        $tmp_dir = get_temp_dir() . 'fra-sync-theme-' . time() . '-' . wp_rand();

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
        $this->log("Step 3: Locating theme folder...");

        $extracted_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
        if (empty($extracted_dirs)) {
            $wp_filesystem->delete($tmp_dir, true);
            $error = 'No extracted directory found';
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        $source_dir = $extracted_dirs[0] . '/' . $folder;

        $this->log("Source: {$source_dir}");
        $this->log("Destination: {$theme_dir}");

        if (!is_dir($source_dir)) {
            $wp_filesystem->delete($tmp_dir, true);
            $error = "Theme folder not found in repository: {$folder}";
            $this->log("ERROR: {$error}");
            return array('success' => false, 'message' => $error);
        }

        // Step 4: Check if theme is active
        $this->log("Step 4: Checking theme status...");

        $active_theme = get_stylesheet();
        $is_active = ($active_theme === $folder);
        $this->log($is_active ? "Theme is currently active" : "Theme is not active");

        // Step 5: Replace theme files
        $this->log("Step 5: Replacing theme files...");

        if (is_dir($theme_dir)) {
            $this->log("Removing existing theme...");
            if (!$wp_filesystem->delete($theme_dir, true)) {
                $wp_filesystem->delete($tmp_dir, true);
                $error = 'Could not remove existing theme directory';
                $this->log("ERROR: {$error}");
                return array('success' => false, 'message' => $error);
            }
        }

        $this->log("Copying new files...");
        $copy_result = copy_dir($source_dir, $theme_dir);

        if (is_wp_error($copy_result)) {
            $error = 'Copy failed: ' . $copy_result->get_error_message();
            $this->log("ERROR: {$error}");
            $wp_filesystem->delete($tmp_dir, true);
            return array('success' => false, 'message' => $error);
        }

        $this->log("Files copied successfully");

        // Step 6: Cleanup
        $this->log("Step 6: Cleaning up...");
        $wp_filesystem->delete($tmp_dir, true);

        // Get new version from style.css
        $new_version = 'Unknown';
        $style_file = $theme_dir . '/style.css';
        if (file_exists($style_file)) {
            $theme_data = get_file_data($style_file, array('Version' => 'Version'));
            $new_version = $theme_data['Version'] ?? 'Unknown';
        }

        $this->log("=== Theme update complete! New version: {$new_version} ===");

        return array(
            'success' => true,
            'message' => "Updated to version {$new_version}",
            'version' => $new_version,
        );
    }

    /**
     * Create backup of a theme before updating
     */
    public function create_theme_backup($folder) {
        $this->log("Creating theme backup for: {$folder}");

        $theme_dir = get_theme_root() . '/' . $folder;

        if (!is_dir($theme_dir)) {
            $this->log("ERROR: Theme directory not found for backup");
            return false;
        }

        // Get current version from style.css
        $version = 'unknown';
        $style_file = $theme_dir . '/style.css';
        if (file_exists($style_file)) {
            $theme_data = get_file_data($style_file, array('Version' => 'Version'));
            $version = $theme_data['Version'] ?? 'unknown';
        }

        $backup_dir = $this->ensure_backup_dir();
        $backup_file = $backup_dir . '/theme-' . $folder . '-' . $version . '-' . time() . '.zip';

        if (!class_exists('ZipArchive')) {
            $this->log("ERROR: ZipArchive not available for backup");
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->log("ERROR: Could not create backup zip file");
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($theme_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $folder . '/' . substr($filePath, strlen($theme_dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $this->log("Theme backup created: " . basename($backup_file) . " (" . round(filesize($backup_file) / 1024) . " KB)");

        // Clean old theme backups (keep last 3)
        $this->cleanup_old_theme_backups($folder, 3);

        return $backup_file;
    }

    /**
     * Cleanup old theme backups
     */
    private function cleanup_old_theme_backups($folder, $keep = 3) {
        $backups = $this->get_theme_backups($folder);

        if (count($backups) > $keep) {
            $to_delete = array_slice($backups, $keep);
            foreach ($to_delete as $backup) {
                @unlink($backup['file']);
                $this->log("Deleted old theme backup: " . $backup['filename']);
            }
        }
    }

    /**
     * Get list of theme backups
     */
    public function get_theme_backups($folder = null) {
        $backup_dir = $this->get_backup_dir();
        $backups = array();

        if (!is_dir($backup_dir)) {
            return $backups;
        }

        $files = glob($backup_dir . '/theme-*.zip');
        foreach ($files as $file) {
            $filename = basename($file);
            // Format: theme-foldername-version-timestamp.zip
            if (preg_match('/^theme-(.+?)-(\d+\.\d+\.\d+)-(\d+)\.zip$/', $filename, $matches)) {
                $theme_folder = $matches[1];

                if ($folder !== null && $theme_folder !== $folder) {
                    continue;
                }

                $backups[] = array(
                    'file' => $file,
                    'filename' => $filename,
                    'folder' => $theme_folder,
                    'version' => $matches[2],
                    'timestamp' => intval($matches[3]),
                    'date' => date('Y-m-d H:i:s', intval($matches[3])),
                    'size' => filesize($file),
                    'type' => 'theme',
                );
            }
        }

        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Restore a theme from backup
     */
    public function restore_theme_backup($backup_file) {
        $this->log("=== Restoring theme from backup: " . basename($backup_file) . " ===");

        if (!file_exists($backup_file)) {
            $this->log("ERROR: Backup file not found");
            return array('success' => false, 'message' => 'Backup file not found');
        }

        $filename = basename($backup_file);
        if (!preg_match('/^theme-(.+?)-\d+\.\d+\.\d+-\d+\.zip$/', $filename, $matches)) {
            $this->log("ERROR: Invalid theme backup filename format");
            return array('success' => false, 'message' => 'Invalid backup filename');
        }

        $folder = $matches[1];
        $theme_dir = get_theme_root() . '/' . $folder;

        WP_Filesystem();
        global $wp_filesystem;

        if (is_dir($theme_dir)) {
            $wp_filesystem->delete($theme_dir, true);
            $this->log("Existing theme directory removed");
        }

        if (!function_exists('unzip_file')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $result = unzip_file($backup_file, get_theme_root());

        if (is_wp_error($result)) {
            $this->log("ERROR: Theme restore failed: " . $result->get_error_message());
            return array('success' => false, 'message' => $result->get_error_message());
        }

        $this->log("Theme backup extracted successfully");

        $version = 'Unknown';
        $style_file = $theme_dir . '/style.css';
        if (file_exists($style_file)) {
            $theme_data = get_file_data($style_file, array('Version' => 'Version'));
            $version = $theme_data['Version'] ?? 'Unknown';
        }

        $this->log("=== Theme restore complete! Version: {$version} ===");

        return array(
            'success' => true,
            'message' => "Restored to version {$version}",
            'version' => $version,
        );
    }

    /**
     * Update all (plugins and themes)
     */
    public function update_all() {
        $results = array('plugins' => array(), 'themes' => array());

        foreach ($this->plugins as $folder => $plugin_info) {
            $results['plugins'][$folder] = $this->update_plugin($folder);
        }

        foreach ($this->themes as $folder => $theme_info) {
            $results['themes'][$folder] = $this->update_theme($folder);
        }

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
                $results = $this->update_all();
                wp_send_json_success(array('results' => $results));
                break;

            case 'update_theme':
                $folder = isset($_POST['theme']) ? sanitize_text_field($_POST['theme']) : '';
                $result = $this->update_theme($folder);
                if ($result['success']) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error($result);
                }
                break;

            case 'restore_theme_backup':
                $file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
                $backup_dir = $this->get_backup_dir();
                $backup_file = $backup_dir . '/' . basename($file);
                $result = $this->restore_theme_backup($backup_file);
                if ($result['success']) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error($result);
                }
                break;

            case 'run_diagnostics':
                $results = $this->run_diagnostics();
                wp_send_json_success(array('diagnostics' => $results));
                break;

            case 'restore_backup':
                $file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
                $backup_dir = $this->get_backup_dir();
                $backup_file = $backup_dir . '/' . basename($file);
                $result = $this->restore_backup($backup_file);
                if ($result['success']) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error($result);
                }
                break;

            case 'delete_backup':
                $file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
                $backup_dir = $this->get_backup_dir();
                $backup_file = $backup_dir . '/' . basename($file);
                if (file_exists($backup_file) && @unlink($backup_file)) {
                    $this->log("Deleted backup: " . basename($file));
                    wp_send_json_success(array('message' => 'Backup deleted'));
                } else {
                    wp_send_json_error(array('message' => 'Could not delete backup'));
                }
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
                $token        = isset( $_POST['github_token'] ) ? sanitize_text_field( wp_unslash( $_POST['github_token'] ) ) : '';
                $interval     = isset( $_POST['sync_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['sync_interval'] ) ) : 'every_15_minutes';
                $keep_backups = isset( $_POST['keep_backups'] ) ? (bool) $_POST['keep_backups'] : true;

                // Validate interval is a known value.
                $valid_intervals = array( 'every_5_minutes', 'every_15_minutes', 'every_30_minutes', 'hourly' );
                if ( ! in_array( $interval, $valid_intervals, true ) ) {
                    $interval = 'every_15_minutes';
                }

                // Only update token if a new one is provided (not empty).
                if ( ! empty( $token ) ) {
                    $this->save_github_token( $token );
                }

                update_option( 'fra_sync_interval', $interval );
                update_option( 'fra_keep_backups', $keep_backups );

                // Reschedule cron with new interval.
                wp_clear_scheduled_hook( 'fra_github_sync_check' );
                wp_schedule_event( time(), $interval, 'fra_github_sync_check' );

                $this->log( 'Settings saved' );
                wp_send_json_success( array( 'message' => 'Settings saved' ) );
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
            'themes' => 'Themes',
            'backups' => 'Backups',
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
                    case 'themes':
                        $this->render_themes_tab();
                        break;
                    case 'backups':
                        $this->render_backups_tab();
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
            .fra-backup-row { padding: 12px; border-bottom: 1px solid #eee; display: flex; align-items: center; }
            .fra-backup-row:last-child { border-bottom: none; }
            .fra-backup-info { flex: 1; }
            .fra-backup-actions { margin-left: 10px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo wp_create_nonce('fra_github_sync'); ?>';

            function fraAjax(action, data, callback, timeout) {
                data = data || {};
                data.action = 'fra_github_sync';
                data.nonce = nonce;
                data.sync_action = action;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    timeout: timeout || 60000,
                    success: function(response) {
                        if (callback) callback(response);
                    },
                    error: function(xhr, status, error) {
                        alert('Request failed: ' + (error || status));
                    }
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

                if (!confirm('Update ' + plugin + '?')) return;

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
                }, 120000);
            });

            // Update single theme
            $('.fra-update-theme').on('click', function() {
                var $btn = $(this);
                var theme = $btn.data('theme');
                var $status = $btn.closest('.fra-plugin-row').find('.fra-plugin-status');

                if (!confirm('Update theme ' + theme + '?')) return;

                $btn.prop('disabled', true).text('Updating...');
                $status.html('<span class="fra-status-badge fra-status-warning">Updating...</span>');

                fraAjax('update_theme', { theme: theme }, function(response) {
                    $btn.prop('disabled', false).text('Update');
                    if (response.success) {
                        $status.html('<span class="fra-status-badge fra-status-success">Updated to ' + response.data.version + '</span>');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        $status.html('<span class="fra-status-badge fra-status-error">Failed: ' + response.data.message + '</span>');
                    }
                }, 120000);
            });

            // Update all plugins
            $('#fra-update-all').on('click', function() {
                var $btn = $(this);
                if (!confirm('Update all plugins?')) return;

                $btn.prop('disabled', true).text('Updating All...');

                fraAjax('update_all', {}, function(response) {
                    $btn.prop('disabled', false).text('Update All Plugins');
                    if (response.success) {
                        alert('Updates complete!');
                        location.reload();
                    }
                }, 180000);
            });

            // Run diagnostics
            $('#fra-run-diagnostics').on('click', function() {
                var $btn = $(this);
                var $results = $('#fra-diagnostics-results');

                $btn.prop('disabled', true).text('Running...');
                $results.html('<p>Running diagnostics... Check the Logs tab for progress.</p>');

                fraAjax('run_diagnostics', {}, function(response) {
                    $btn.prop('disabled', false).text('Run Diagnostics');
                    if (response.success) {
                        var html = '';
                        var diag = response.data.diagnostics;

                        var tests = ['github_api', 'temp_dir', 'plugin_dir', 'wp_filesystem', 'unzip', 'backup_dir', 'cron'];
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

                        if (diag.plugins) {
                            html += '<h3 style="margin-top: 20px;">Managed Plugins</h3>';
                            for (var folder in diag.plugins) {
                                var p = diag.plugins[folder];
                                html += '<div style="padding: 10px; border-bottom: 1px solid #eee;">';
                                html += '<strong>' + p.name + '</strong> (v' + p.version + ') ';
                                html += '<span class="fra-status-badge fra-status-' + p.status + '">' + p.message + '</span>';
                                html += '</div>';
                            }
                        }

                        $results.html(html);
                    } else {
                        $results.html('<p style="color: red;">Diagnostics failed. Check the Logs tab.</p>');
                    }
                }, 30000);
            });

            // Restore plugin backup
            $('.fra-restore-backup').on('click', function() {
                var $btn = $(this);
                var file = $btn.data('file');

                if (!confirm('Restore from this backup? Current version will be replaced.')) return;

                $btn.prop('disabled', true).text('Restoring...');

                fraAjax('restore_backup', { file: file }, function(response) {
                    $btn.prop('disabled', false).text('Restore');
                    if (response.success) {
                        alert('Restored to version ' + response.data.version);
                        location.reload();
                    } else {
                        alert('Restore failed: ' + response.data.message);
                    }
                }, 60000);
            });

            // Restore theme backup
            $('.fra-restore-theme-backup').on('click', function() {
                var $btn = $(this);
                var file = $btn.data('file');

                if (!confirm('Restore theme from this backup? Current version will be replaced.')) return;

                $btn.prop('disabled', true).text('Restoring...');

                fraAjax('restore_theme_backup', { file: file }, function(response) {
                    $btn.prop('disabled', false).text('Restore');
                    if (response.success) {
                        alert('Theme restored to version ' + response.data.version);
                        location.reload();
                    } else {
                        alert('Restore failed: ' + response.data.message);
                    }
                }, 60000);
            });

            // Delete backup
            $('.fra-delete-backup').on('click', function() {
                var $btn = $(this);
                var file = $btn.data('file');

                if (!confirm('Delete this backup?')) return;

                fraAjax('delete_backup', { file: file }, function(response) {
                    if (response.success) {
                        $btn.closest('.fra-backup-row').fadeOut(function() { $(this).remove(); });
                    } else {
                        alert('Delete failed: ' + response.data.message);
                    }
                });
            });

            // Save settings
            $('#fra-save-settings').on('click', function() {
                var $btn = $(this);
                var token = $('#fra-github-token').val();
                var interval = $('#fra-sync-interval').val();
                var keepBackups = $('#fra-keep-backups').is(':checked') ? 1 : 0;

                // Don't send placeholder dots as actual token.
                if (token === '••••••••••••••••') {
                    token = '';
                }

                $btn.prop('disabled', true).text('Saving...');

                fraAjax('save_settings', {
                    github_token: token,
                    sync_interval: interval,
                    keep_backups: keepBackups
                }, function(response) {
                    $btn.prop('disabled', false).text('Save Settings');
                    if (response.success) {
                        alert('Settings saved!');
                        location.reload();
                    }
                });
            });

            // Clear logs
            $('#fra-clear-logs').on('click', function() {
                fraAjax('clear_logs', {}, function(response) {
                    if (response.success) location.reload();
                });
            });

            // Clear notice
            $('#fra-clear-notice').on('click', function() {
                fraAjax('clear_notice', {}, function(response) {
                    if (response.success) location.reload();
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
                <strong>⚠️ Updates Available!</strong>
                <button type="button" class="button" id="fra-clear-notice" style="margin-left: 10px;">Dismiss</button>
            </div>
            <?php endif; ?>

            <table class="form-table">
                <tr><th>Repository</th><td><code><?php echo esc_html(self::GITHUB_REPO); ?></code></td></tr>
                <tr><th>Branch</th><td><code><?php echo esc_html(self::GITHUB_BRANCH); ?></code></td></tr>
                <tr><th>Last Check</th><td><?php echo esc_html($last_check); ?></td></tr>
                <tr><th>Last Update</th><td><?php echo esc_html($last_update); ?></td></tr>
                <tr><th>Last Commit</th><td><code><?php echo $last_commit ? esc_html(substr($last_commit, 0, 8)) : 'Unknown'; ?></code></td></tr>
                <tr><th>Next Check</th><td><?php echo $next_check ? esc_html(date('Y-m-d H:i:s', $next_check)) : 'Not scheduled'; ?></td></tr>
            </table>

            <p style="margin-top: 20px;">
                <button type="button" class="button button-primary" id="fra-check-updates">Check for Updates</button>
                <button type="button" class="button button-primary" id="fra-update-all" style="margin-left: 10px;">Update All (Plugins + Theme)</button>
            </p>
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
                        Version: <code><?php echo esc_html($version); ?></code> |
                        <?php echo $active ? '<span style="color: green;">Active</span>' : '<span style="color: #999;">Inactive</span>'; ?>
                    </span>
                </div>
                <div class="fra-plugin-actions">
                    <span class="fra-plugin-status"></span>
                    <button type="button" class="button fra-update-plugin" data-plugin="<?php echo esc_attr($folder); ?>">Update</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_themes_tab() {
        ?>
        <div class="fra-card">
            <h2>Managed Themes</h2>
            <?php foreach ($this->themes as $folder => $theme_info) :
                $theme_dir = get_theme_root() . '/' . $folder;
                $version = 'Not installed';
                $active = false;

                if (is_dir($theme_dir)) {
                    $style_file = $theme_dir . '/style.css';
                    if (file_exists($style_file)) {
                        $theme_data = get_file_data($style_file, array('Version' => 'Version'));
                        $version = $theme_data['Version'] ?? 'Unknown';
                    }
                    $active = (get_stylesheet() === $folder);
                }
            ?>
            <div class="fra-plugin-row">
                <div class="fra-plugin-info">
                    <strong><?php echo esc_html($theme_info['name']); ?></strong><br>
                    <span style="color: #666;">
                        Version: <code><?php echo esc_html($version); ?></code> |
                        <?php echo $active ? '<span style="color: green;">Active</span>' : '<span style="color: #999;">Inactive</span>'; ?>
                    </span>
                </div>
                <div class="fra-plugin-actions">
                    <span class="fra-plugin-status"></span>
                    <button type="button" class="button fra-update-theme" data-theme="<?php echo esc_attr($folder); ?>">Update</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_backups_tab() {
        $plugin_backups = $this->get_backups();
        $theme_backups = $this->get_theme_backups();
        ?>
        <div class="fra-card">
            <h2>Plugin Backups</h2>
            <p>Backups are automatically created before each update. You can restore to any previous version.</p>

            <?php if (empty($plugin_backups)) : ?>
                <p style="color: #666;">No plugin backups yet.</p>
            <?php else : ?>
                <?php foreach ($plugin_backups as $backup) : ?>
                <div class="fra-backup-row">
                    <div class="fra-backup-info">
                        <strong><?php echo esc_html($this->plugins[$backup['folder']]['name'] ?? $backup['folder']); ?></strong>
                        v<?php echo esc_html($backup['version']); ?><br>
                        <span style="color: #666; font-size: 12px;">
                            <?php echo esc_html($backup['date']); ?> |
                            <?php echo round($backup['size'] / 1024); ?> KB
                        </span>
                    </div>
                    <div class="fra-backup-actions">
                        <button type="button" class="button fra-restore-backup" data-file="<?php echo esc_attr($backup['filename']); ?>">Restore</button>
                        <button type="button" class="button fra-delete-backup" data-file="<?php echo esc_attr($backup['filename']); ?>" style="color: #a00;">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="fra-card">
            <h2>Theme Backups</h2>
            <?php if (empty($theme_backups)) : ?>
                <p style="color: #666;">No theme backups yet.</p>
            <?php else : ?>
                <?php foreach ($theme_backups as $backup) : ?>
                <div class="fra-backup-row">
                    <div class="fra-backup-info">
                        <strong><?php echo esc_html($this->themes[$backup['folder']]['name'] ?? $backup['folder']); ?></strong>
                        v<?php echo esc_html($backup['version']); ?><br>
                        <span style="color: #666; font-size: 12px;">
                            <?php echo esc_html($backup['date']); ?> |
                            <?php echo round($backup['size'] / 1024); ?> KB
                        </span>
                    </div>
                    <div class="fra-backup-actions">
                        <button type="button" class="button fra-restore-theme-backup" data-file="<?php echo esc_attr($backup['filename']); ?>">Restore</button>
                        <button type="button" class="button fra-delete-backup" data-file="<?php echo esc_attr($backup['filename']); ?>" style="color: #a00;">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_diagnostics_tab() {
        ?>
        <div class="fra-card">
            <h2>System Diagnostics</h2>
            <p>Run diagnostics to check if updates can be performed. Progress is logged to the Logs tab.</p>
            <p>
                <button type="button" class="button button-primary" id="fra-run-diagnostics">Run Diagnostics</button>
            </p>
            <div id="fra-diagnostics-results" style="margin-top: 20px;">
                <p style="color: #666;">Click "Run Diagnostics" to test your system.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the settings tab.
     *
     * @since 1.0.0
     * @return void
     */
    private function render_settings_tab() {
        $token        = $this->get_github_token();
        $has_token    = ! empty( $token );
        $interval     = get_option( 'fra_sync_interval', 'every_15_minutes' );
        $keep_backups = get_option( 'fra_keep_backups', true );
        ?>
        <div class="fra-card">
            <h2>Settings</h2>
            <table class="form-table">
                <tr>
                    <th>GitHub Token</th>
                    <td>
                        <input type="password" id="fra-github-token" value="<?php echo $has_token ? '••••••••••••••••' : ''; ?>" class="regular-text" placeholder="<?php echo $has_token ? 'Token saved (enter new to replace)' : 'Enter token'; ?>">
                        <p class="description">
                            Optional. For private repos or to avoid rate limits.
                            <?php if ( $has_token ) : ?>
                                <br><span style="color: green;">✓ Token is saved (encrypted)</span>
                            <?php endif; ?>
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
                    </td>
                </tr>
                <tr>
                    <th>Auto Backups</th>
                    <td>
                        <label>
                            <input type="checkbox" id="fra-keep-backups" <?php checked($keep_backups); ?>>
                            Create backup before each update (recommended)
                        </label>
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
        if (get_option('fra_github_update_available', false) && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>🔄 France Relocation plugins have updates!</strong>
                    <a href="<?php echo admin_url('tools.php?page=fra-github-sync'); ?>" class="button button-primary" style="margin-left: 10px;">Update Now</a>
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
        $logs = array_slice($logs, -100);
        update_option('fra_github_sync_log', $logs);
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
