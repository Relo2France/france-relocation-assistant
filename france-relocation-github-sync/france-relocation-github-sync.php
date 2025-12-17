<?php
/**
 * Plugin Name: France Relocation GitHub Sync
 * Plugin URI: https://relo2france.com
 * Description: Automatically syncs plugins from GitHub by polling for changes every 15 minutes. Workaround for WordPress.com webhook limitations.
 * Version: 1.0.2
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

    // Plugins to update (WP Pusher package names)
    private $plugins = array(
        'france-relocation-assistant-plugin' => 'france-relocation-assistant-plugin/france-relocation-assistant.php',
        'france-relocation-member-tools' => 'france-relocation-member-tools/france-relocation-member-tools.php',
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

        // Hook for the cron job
        add_action('fra_github_sync_check', array($this, 'check_for_updates'));

        // Admin menu for manual trigger and status
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // AJAX handlers
        add_action('wp_ajax_fra_manual_github_sync', array($this, 'ajax_manual_sync'));
        add_action('wp_ajax_fra_force_github_update', array($this, 'ajax_force_update'));
        add_action('wp_ajax_fra_clear_update_notice', array($this, 'ajax_clear_notice'));

        // Admin notice when updates are available
        add_action('admin_notices', array($this, 'show_update_notice'));

        // Cleanup on deactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Schedule the sync check every 15 minutes
     */
    public function schedule_sync() {
        if (!wp_next_scheduled('fra_github_sync_check')) {
            wp_schedule_event(time(), 'every_15_minutes', 'fra_github_sync_check');
        }

        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
    }

    /**
     * Add 15-minute cron interval
     */
    public function add_cron_interval($schedules) {
        $schedules['every_15_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __('Every 15 Minutes', 'fra-github-sync'),
        );
        return $schedules;
    }

    /**
     * Check GitHub for new commits and trigger update if needed
     */
    public function check_for_updates() {
        $github_commit = $this->get_latest_github_commit();

        if (!$github_commit) {
            $this->log('Failed to fetch GitHub commit');
            return false;
        }

        $stored_commit = get_option('fra_github_last_commit', '');

        $this->log("GitHub commit: {$github_commit}, Stored: {$stored_commit}");

        if ($github_commit !== $stored_commit) {
            $this->log('New commit detected!');

            // Set notification flag for admin notice
            update_option('fra_github_update_available', true);

            // Update stored commit
            update_option('fra_github_last_commit', $github_commit);
            update_option('fra_github_last_check', current_time('mysql'));

            // Try to trigger WP Pusher updates (may not work on WordPress.com)
            $result = $this->trigger_wp_pusher_update();

            if ($result) {
                update_option('fra_github_last_update', current_time('mysql'));
                update_option('fra_github_update_available', false); // Clear flag if update succeeded
                $this->log('Update triggered successfully');

                // Reactivate plugins after update
                $this->reactivate_plugins();
            } else {
                $this->log('Auto-update failed. User should update manually via WP Pusher.');
            }

            return $result;
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

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ),
            'timeout' => 15,
        ));

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
     * Trigger WP Pusher to update plugins
     */
    private function trigger_wp_pusher_update() {
        // Check if WP Pusher is active
        if (!class_exists('WPPusher')) {
            $this->log('WP Pusher not found');
            return false;
        }

        $success = true;

        foreach ($this->plugins as $package_name => $plugin_file) {
            $result = $this->update_single_plugin($package_name);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Update a single plugin via WP Pusher
     */
    private function update_single_plugin($package_name) {
        $this->log("Updating plugin: {$package_name}");

        // Get WP Pusher plugin data from database
        global $wpdb;
        $table = $wpdb->prefix . 'wppusher_plugins';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            $this->log('WP Pusher table not found');
            return $this->pull_from_github($package_name);
        }

        $plugin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE directory = %s",
            $package_name
        ));

        if (!$plugin) {
            $this->log("Plugin not found in WP Pusher: {$package_name}");
            return $this->pull_from_github($package_name);
        }

        $this->log("Found WP Pusher record for: {$package_name}, attempting update...");

        // Try to use WP Pusher's Plugin class directly
        if (class_exists('Pusher\Storage\PluginRepository')) {
            try {
                $container = \Pusher\Pusher::getInstance()->getContainer();
                $repo = $container->resolve('Pusher\Storage\PluginRepository');
                $pusherPlugin = $repo->fromSlug($package_name);

                if ($pusherPlugin) {
                    $installer = $container->resolve('Pusher\Git\PluginInstaller');
                    $installer->install($pusherPlugin);
                    $this->log("WP Pusher update successful for: {$package_name}");
                    return true;
                }
            } catch (Exception $e) {
                $this->log('WP Pusher API error: ' . $e->getMessage());
            }
        }

        // Fallback: Direct file update from GitHub
        $this->log("Falling back to direct GitHub pull for: {$package_name}");
        return $this->pull_from_github($package_name);
    }

    /**
     * Pull plugin directly from GitHub (fallback method)
     */
    private function pull_from_github($package_name) {
        $zip_url = sprintf(
            'https://github.com/%s/archive/refs/heads/%s.zip',
            self::GITHUB_REPO,
            self::GITHUB_BRANCH
        );

        // Download zip
        $tmp_file = download_url($zip_url);

        if (is_wp_error($tmp_file)) {
            $this->log('Failed to download: ' . $tmp_file->get_error_message());
            return false;
        }

        // Extract and copy files
        WP_Filesystem();
        global $wp_filesystem;

        $plugin_dir = WP_PLUGIN_DIR . '/' . $package_name;
        $tmp_dir = get_temp_dir() . 'fra-github-sync-' . time();

        $unzip_result = unzip_file($tmp_file, $tmp_dir);
        @unlink($tmp_file);

        if (is_wp_error($unzip_result)) {
            $this->log('Failed to unzip: ' . $unzip_result->get_error_message());
            return false;
        }

        // Find the extracted folder (usually repo-name-branch)
        $extracted_dirs = glob($tmp_dir . '/*', GLOB_ONLYDIR);
        if (empty($extracted_dirs)) {
            $this->log('No extracted directory found');
            return false;
        }

        $source_dir = $extracted_dirs[0] . '/' . $package_name;

        if (!is_dir($source_dir)) {
            $this->log("Source directory not found: {$source_dir}");
            $wp_filesystem->delete($tmp_dir, true);
            return false;
        }

        // Remove old plugin and copy new
        $wp_filesystem->delete($plugin_dir, true);
        $copy_result = copy_dir($source_dir, $plugin_dir);

        // Cleanup
        $wp_filesystem->delete($tmp_dir, true);

        if (is_wp_error($copy_result)) {
            $this->log('Failed to copy: ' . $copy_result->get_error_message());
            return false;
        }

        $this->log("Successfully updated: {$package_name}");
        return true;
    }

    /**
     * Reactivate plugins after update
     */
    private function reactivate_plugins() {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach ($this->plugins as $package_name => $plugin_file) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file) && !is_plugin_active($plugin_file)) {
                activate_plugin($plugin_file, '', false, true);
                $this->log("Reactivated: {$plugin_file}");
            }
        }
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
     * Render admin page
     */
    public function render_admin_page() {
        $last_check = get_option('fra_github_last_check', 'Never');
        $last_update = get_option('fra_github_last_update', 'Never');
        $last_commit = get_option('fra_github_last_commit', 'None');
        $next_check = wp_next_scheduled('fra_github_sync_check');
        ?>
        <div class="wrap">
            <h1>GitHub Sync Status</h1>

            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2>Sync Status</h2>
                <table class="form-table">
                    <tr>
                        <th>Last Check:</th>
                        <td><?php echo esc_html($last_check); ?></td>
                    </tr>
                    <tr>
                        <th>Last Update:</th>
                        <td><?php echo esc_html($last_update); ?></td>
                    </tr>
                    <tr>
                        <th>Last Commit SHA:</th>
                        <td><code><?php echo esc_html(substr($last_commit, 0, 8)); ?></code></td>
                    </tr>
                    <tr>
                        <th>Next Scheduled Check:</th>
                        <td><?php echo $next_check ? esc_html(date('Y-m-d H:i:s', $next_check)) : 'Not scheduled'; ?></td>
                    </tr>
                </table>

                <p>
                    <button type="button" class="button button-primary" id="fra-manual-sync">
                        Check Now
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=wppusher-plugins'); ?>" class="button" style="margin-left: 10px;">
                        Go to WP Pusher
                    </a>
                    <?php if (get_option('fra_github_update_available', false)) : ?>
                    <button type="button" class="button" id="fra-clear-notification" style="margin-left: 10px;">
                        Clear Update Notice
                    </button>
                    <?php endif; ?>
                    <span id="fra-sync-status" style="margin-left: 10px;"></span>
                </p>
                <?php if (get_option('fra_github_update_available', false)) : ?>
                <p style="color: #d63638; font-weight: bold;">
                    ⚠️ Updates are available! Click "Go to WP Pusher" and click "Update plugin" for each plugin.
                </p>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2>How It Works</h2>
                <ol>
                    <li>Every 15 minutes, this plugin checks GitHub for new commits</li>
                    <li>If a new commit is detected on <code>main</code>, it triggers an update</li>
                    <li>Plugins are automatically reactivated after update</li>
                </ol>
                <p><strong>Repository:</strong> <?php echo esc_html(self::GITHUB_REPO); ?></p>
                <p><strong>Branch:</strong> <?php echo esc_html(self::GITHUB_BRANCH); ?></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#fra-manual-sync').on('click', function() {
                var $btn = $(this);
                var $status = $('#fra-sync-status');

                $btn.prop('disabled', true);
                $status.text('Checking...');

                $.post(ajaxurl, {
                    action: 'fra_manual_github_sync',
                    nonce: '<?php echo wp_create_nonce('fra_github_sync'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $status.html('<span style="color: green;">' + response.data.message + '</span>');
                        if (response.data.updated) {
                            setTimeout(function() { location.reload(); }, 2000);
                        }
                    } else {
                        $status.html('<span style="color: red;">' + response.data.message + '</span>');
                    }
                });
            });

            $('#fra-clear-notification').on('click', function() {
                var $btn = $(this);
                var $status = $('#fra-sync-status');

                $btn.prop('disabled', true);

                $.post(ajaxurl, {
                    action: 'fra_clear_update_notice',
                    nonce: '<?php echo wp_create_nonce('fra_github_sync'); ?>'
                }, function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">Notice cleared!</span>');
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for manual sync
     */
    public function ajax_manual_sync() {
        check_ajax_referer('fra_github_sync', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $updated = $this->check_for_updates();

        if ($updated) {
            wp_send_json_success(array(
                'message' => 'Update triggered! Reloading...',
                'updated' => true,
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'No updates available. Already on latest commit.',
                'updated' => false,
            ));
        }
    }

    /**
     * AJAX handler for force update
     */
    public function ajax_force_update() {
        check_ajax_referer('fra_github_sync', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Clear stored commit to force update detection
        delete_option('fra_github_last_commit');

        // Get latest commit from GitHub
        $github_commit = $this->get_latest_github_commit();

        if (!$github_commit) {
            wp_send_json_error(array('message' => 'Failed to connect to GitHub'));
            return;
        }

        // Trigger updates
        $result = $this->trigger_wp_pusher_update();

        // Store the new commit
        update_option('fra_github_last_commit', $github_commit);
        update_option('fra_github_last_check', current_time('mysql'));
        update_option('fra_github_last_update', current_time('mysql'));

        // Reactivate plugins
        $this->reactivate_plugins();

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Force update completed! Reloading...',
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Update attempted. Check if plugins updated.',
            ));
        }
    }

    /**
     * AJAX handler for clearing update notice
     */
    public function ajax_clear_notice() {
        check_ajax_referer('fra_github_sync', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        delete_option('fra_github_update_available');
        wp_send_json_success(array('message' => 'Notice cleared'));
    }

    /**
     * Show admin notice when updates are available
     */
    public function show_update_notice() {
        $update_available = get_option('fra_github_update_available', false);

        if ($update_available && current_user_can('manage_options')) {
            $wp_pusher_url = admin_url('admin.php?page=wppusher-plugins');
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>🔄 France Relocation plugins have updates available!</strong>
                    <a href="<?php echo esc_url($wp_pusher_url); ?>" class="button button-primary" style="margin-left: 10px;">
                        Update in WP Pusher
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Log messages for debugging
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FRA GitHub Sync] ' . $message);
        }
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
