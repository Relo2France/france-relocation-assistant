<?php
/**
 * Plugin Name: Auto-Reactivate France Relocation Plugins
 * Description: Must-use plugin that ensures France Relocation plugins stay active after updates.
 * 
 * INSTALLATION: Upload this file to wp-content/mu-plugins/
 */

add_action('upgrader_process_complete', function($upgrader, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        $plugins_to_check = array(
            'france-relocation-assistant-plugin/france-relocation-assistant.php',
            'france-relocation-member-tools/france-relocation-member-tools.php',
        );
        
        foreach ($plugins_to_check as $plugin) {
            if (!is_plugin_active($plugin) && file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                activate_plugin($plugin);
            }
        }
    }
}, 10, 2);

// Also check on admin_init as a fallback
add_action('admin_init', function() {
    if (!current_user_can('activate_plugins')) return;
    
    $plugins_to_check = array(
        'france-relocation-assistant-plugin/france-relocation-assistant.php',
        'france-relocation-member-tools/france-relocation-member-tools.php',
    );
    
    foreach ($plugins_to_check as $plugin) {
        if (!is_plugin_active($plugin) && file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            activate_plugin($plugin);
        }
    }
}, 1);
