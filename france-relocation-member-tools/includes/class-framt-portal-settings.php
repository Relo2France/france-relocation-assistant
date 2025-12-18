<?php
/**
 * Portal Settings Admin Page
 *
 * Provides customization options for the Members Portal appearance,
 * menu items, colors, and layout.
 *
 * @package     FRA_Member_Tools
 * @since       2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FRAMT_Portal_Settings
 *
 * Handles admin settings for portal customization.
 */
class FRAMT_Portal_Settings {

    /**
     * Option name for portal settings.
     *
     * @var string
     */
    const OPTION_NAME = 'framt_portal_settings';

    /**
     * Default settings.
     *
     * @var array
     */
    private $defaults = array(
        // Appearance
        'primary_color'       => '#22c55e',
        'secondary_color'     => '#3b82f6',
        'sidebar_bg_color'    => '#1f2937',
        'sidebar_text_color'  => '#ffffff',
        'header_bg_color'     => '#ffffff',
        'accent_color'        => '#f59e0b',

        // Layout
        'show_wp_header'      => false,
        'show_wp_footer'      => false,
        'show_promo_banner'   => false,
        'sidebar_position'    => 'left',
        'sidebar_collapsed'   => false,

        // Branding
        'portal_title'        => 'Members Portal',
        'logo_url'            => '',
        'favicon_url'         => '',

        // Menu Items - visibility
        'menu_dashboard'      => true,
        'menu_tasks'          => true,
        'menu_timeline'       => true,
        'menu_messages'       => true,
        'menu_documents'      => true,
        'menu_guides'         => true,
        'menu_files'          => true,
        'menu_family'         => true,
        'menu_settings'       => true,
        'menu_help'           => true,

        // Menu Labels - customizable names
        'label_dashboard'     => 'Dashboard',
        'label_tasks'         => 'Tasks',
        'label_timeline'      => 'Timeline',
        'label_messages'      => 'Messages',
        'label_documents'     => 'Documents',
        'label_guides'        => 'Guides',
        'label_files'         => 'Files',
        'label_family'        => 'Family Members',
        'label_settings'      => 'Settings',
        'label_help'          => 'Help & Support',

        // Features
        'enable_notifications' => true,
        'enable_file_upload'   => true,
        'enable_ai_assistant'  => false,

        // Custom CSS
        'custom_css'          => '',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_framt_reset_portal_settings', array( $this, 'ajax_reset_settings' ) );
    }

    /**
     * Get all settings with defaults.
     *
     * @return array Settings array.
     */
    public static function get_settings() {
        $instance = new self();
        $saved    = get_option( self::OPTION_NAME, array() );
        return wp_parse_args( $saved, $instance->defaults );
    }

    /**
     * Get a single setting.
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value if not set.
     * @return mixed Setting value.
     */
    public static function get( $key, $default = null ) {
        $settings = self::get_settings();
        if ( isset( $settings[ $key ] ) ) {
            return $settings[ $key ];
        }
        return $default;
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_settings_page() {
        // Add under france-relocation-assistant menu (main FR Assistant plugin)
        add_submenu_page(
            'france-relocation-assistant',
            'Portal Settings',
            'Portal Settings',
            'manage_options',
            'framt-portal-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'framt_portal_settings_group',
            self::OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );

        // Register membership-related options
        register_setting(
            'framt_portal_settings_group',
            'framt_portal_require_membership',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            )
        );

        register_setting(
            'framt_portal_settings_group',
            'framt_enable_demo_mode',
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            )
        );
    }

    /**
     * Sanitize settings before save.
     *
     * @param array $input Input array.
     * @return array Sanitized array.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Colors
        $color_fields = array(
            'primary_color', 'secondary_color', 'sidebar_bg_color',
            'sidebar_text_color', 'header_bg_color', 'accent_color'
        );
        foreach ( $color_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
            }
        }

        // Booleans
        $bool_fields = array(
            'show_wp_header', 'show_wp_footer', 'show_promo_banner',
            'sidebar_collapsed', 'enable_notifications', 'enable_file_upload',
            'enable_ai_assistant', 'menu_dashboard', 'menu_tasks', 'menu_timeline',
            'menu_messages', 'menu_documents', 'menu_guides', 'menu_files',
            'menu_family', 'menu_settings', 'menu_help'
        );
        foreach ( $bool_fields as $field ) {
            $sanitized[ $field ] = ! empty( $input[ $field ] );
        }

        // Text fields
        $text_fields = array(
            'portal_title', 'label_dashboard', 'label_tasks', 'label_timeline',
            'label_messages', 'label_documents', 'label_guides', 'label_files',
            'label_family', 'label_settings', 'label_help'
        );
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        // URLs
        $url_fields = array( 'logo_url', 'favicon_url' );
        foreach ( $url_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = esc_url_raw( $input[ $field ] );
            }
        }

        // Select fields
        if ( isset( $input['sidebar_position'] ) ) {
            $sanitized['sidebar_position'] = in_array( $input['sidebar_position'], array( 'left', 'right' ), true )
                ? $input['sidebar_position']
                : 'left';
        }

        // Custom CSS - allow style tags
        if ( isset( $input['custom_css'] ) ) {
            $sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
        }

        return $sanitized;
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Check for various possible hook names depending on menu structure
        $valid_hooks = array(
            'fra-member-tools_page_framt-portal-settings',
            'france-relocation-assistant_page_framt-portal-settings',
            'admin_page_framt-portal-settings',
        );

        // Also check if hook contains our page slug
        $is_our_page = in_array( $hook, $valid_hooks, true ) || strpos( $hook, 'framt-portal-settings' ) !== false;

        if ( ! $is_our_page ) {
            return;
        }

        // Enqueue WordPress color picker with its dependencies
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script(
            'wp-color-picker',
            false, // Use WordPress default
            array( 'jquery', 'wp-color-picker' ),
            false,
            true
        );

        // Enqueue media library for image uploads
        wp_enqueue_media();

        // Register and enqueue our custom admin script
        wp_register_script(
            'framt-portal-settings',
            '', // No external file, we'll add inline script
            array( 'jquery', 'wp-color-picker', 'media-upload' ),
            FRAMT_VERSION,
            true
        );

        $inline_script = "
            jQuery(document).ready(function($) {
                // Check if wpColorPicker is available before initializing
                if (typeof $.fn.wpColorPicker === 'function') {
                    $('.framt-color-picker').wpColorPicker({
                        change: function(event, ui) {
                            $(this).closest('.framt-color-row').find('.framt-color-preview').css('background-color', ui.color.toString());
                        },
                        clear: function() {
                            $(this).closest('.framt-color-row').find('.framt-color-preview').css('background-color', '');
                        }
                    });
                } else {
                    console.warn('WordPress Color Picker not available');
                }

                // Media uploader for logo/favicon
                $(document).on('click', '.framt-upload-button', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var targetInput = $(button.data('target'));

                    if (typeof wp !== 'undefined' && typeof wp.media === 'function') {
                        var frame = wp.media({
                            title: 'Select Image',
                            button: { text: 'Use this image' },
                            multiple: false
                        });

                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            targetInput.val(attachment.url);
                            button.siblings('.framt-image-preview').attr('src', attachment.url).show();
                        });

                        frame.open();
                    } else {
                        alert('Media library not available. Please refresh the page.');
                    }
                });
            });
        ";

        wp_add_inline_script( 'wp-color-picker', $inline_script );
    }

    /**
     * AJAX handler to reset settings.
     */
    public function ajax_reset_settings() {
        check_ajax_referer( 'framt_portal_settings', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        delete_option( self::OPTION_NAME );

        wp_send_json_success( array( 'message' => 'Settings reset to defaults' ) );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        $settings    = self::get_settings();
        $active_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'appearance';
        ?>
        <div class="wrap">
            <h1>Portal Settings</h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=framt-portal-settings&tab=appearance"
                   class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    Appearance
                </a>
                <a href="?page=framt-portal-settings&tab=menu"
                   class="nav-tab <?php echo $active_tab === 'menu' ? 'nav-tab-active' : ''; ?>">
                    Menu Items
                </a>
                <a href="?page=framt-portal-settings&tab=branding"
                   class="nav-tab <?php echo $active_tab === 'branding' ? 'nav-tab-active' : ''; ?>">
                    Branding
                </a>
                <a href="?page=framt-portal-settings&tab=features"
                   class="nav-tab <?php echo $active_tab === 'features' ? 'nav-tab-active' : ''; ?>">
                    Features
                </a>
                <a href="?page=framt-portal-settings&tab=advanced"
                   class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    Advanced
                </a>
            </nav>

            <form method="post" action="options.php">
                <?php settings_fields( 'framt_portal_settings_group' ); ?>

                <?php
                // Output hidden fields for all settings to preserve values from other tabs
                $this->render_hidden_fields( $settings, $active_tab );
                ?>

                <div class="tab-content" style="margin-top: 20px;">
                    <?php
                    switch ( $active_tab ) {
                        case 'appearance':
                            $this->render_appearance_tab( $settings );
                            break;
                        case 'menu':
                            $this->render_menu_tab( $settings );
                            break;
                        case 'branding':
                            $this->render_branding_tab( $settings );
                            break;
                        case 'features':
                            $this->render_features_tab( $settings );
                            break;
                        case 'advanced':
                            $this->render_advanced_tab( $settings );
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>

            <hr>
            <h3>Reset Settings</h3>
            <p>Reset all portal settings to their default values.</p>
            <button type="button" class="button" id="framt-reset-settings">Reset to Defaults</button>
        </div>

        <style>
            .framt-settings-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                margin-bottom: 20px;
                max-width: 800px;
            }
            .framt-settings-card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .framt-color-row {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
            }
            .framt-color-row label {
                width: 200px;
                font-weight: 500;
            }
            .framt-color-preview {
                width: 30px;
                height: 30px;
                border-radius: 4px;
                margin-left: 10px;
                border: 1px solid #ccc;
            }
            .framt-menu-item {
                display: flex;
                align-items: center;
                padding: 12px;
                background: #f9f9f9;
                margin-bottom: 8px;
                border-radius: 4px;
            }
            .framt-menu-item label {
                margin-left: 10px;
                flex: 1;
            }
            .framt-menu-item input[type="text"] {
                width: 200px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Reset settings button handler
            $('#framt-reset-settings').on('click', function() {
                if (!confirm('Are you sure you want to reset all portal settings to defaults?')) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'framt_reset_portal_settings',
                    nonce: '<?php echo wp_create_nonce( 'framt_portal_settings' ); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Settings reset! Page will reload.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render hidden fields for settings not on the current tab.
     * This preserves all settings when saving from any tab.
     *
     * @param array  $settings   Current settings.
     * @param string $active_tab Currently active tab.
     */
    private function render_hidden_fields( $settings, $active_tab ) {
        // Define which fields belong to which tab
        $tab_fields = array(
            'appearance' => array(
                'primary_color', 'secondary_color', 'sidebar_bg_color',
                'sidebar_text_color', 'header_bg_color', 'accent_color',
                'show_wp_header', 'show_wp_footer', 'show_promo_banner',
                'sidebar_position', 'sidebar_collapsed',
            ),
            'menu' => array(
                'menu_dashboard', 'menu_tasks', 'menu_timeline', 'menu_messages',
                'menu_documents', 'menu_guides', 'menu_files', 'menu_family',
                'menu_settings', 'menu_help',
                'label_dashboard', 'label_tasks', 'label_timeline', 'label_messages',
                'label_documents', 'label_guides', 'label_files', 'label_family',
                'label_settings', 'label_help',
            ),
            'branding' => array(
                'portal_title', 'logo_url', 'favicon_url',
            ),
            'features' => array(
                'enable_notifications', 'enable_file_upload', 'enable_ai_assistant',
            ),
            'advanced' => array(
                'custom_css',
            ),
        );

        // Get fields that are NOT on the current tab
        $hidden_fields = array();
        foreach ( $tab_fields as $tab => $fields ) {
            if ( $tab !== $active_tab ) {
                $hidden_fields = array_merge( $hidden_fields, $fields );
            }
        }

        // Boolean fields that need special handling
        $bool_fields = array(
            'show_wp_header', 'show_wp_footer', 'show_promo_banner', 'sidebar_collapsed',
            'enable_notifications', 'enable_file_upload', 'enable_ai_assistant',
            'menu_dashboard', 'menu_tasks', 'menu_timeline', 'menu_messages',
            'menu_documents', 'menu_guides', 'menu_files', 'menu_family',
            'menu_settings', 'menu_help',
        );

        // Output hidden fields
        foreach ( $hidden_fields as $field ) {
            $value = isset( $settings[ $field ] ) ? $settings[ $field ] : '';

            if ( in_array( $field, $bool_fields, true ) ) {
                // For boolean fields, only output if true (checkboxes)
                if ( $value ) {
                    echo '<input type="hidden" name="' . esc_attr( self::OPTION_NAME ) . '[' . esc_attr( $field ) . ']" value="1">';
                }
            } else {
                // For other fields, output the value
                echo '<input type="hidden" name="' . esc_attr( self::OPTION_NAME ) . '[' . esc_attr( $field ) . ']" value="' . esc_attr( $value ) . '">';
            }
        }
    }

    /**
     * Render appearance tab.
     *
     * @param array $settings Current settings.
     */
    private function render_appearance_tab( $settings ) {
        ?>
        <div class="framt-settings-card">
            <h2>Colors</h2>
            <p>Customize the portal color scheme to match your brand.</p>

            <div class="framt-color-row">
                <label for="primary_color">Primary Color</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[primary_color]"
                       id="primary_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['primary_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['primary_color'] ); ?>"></div>
                <span style="margin-left: 10px; color: #666;">Buttons, links, progress bars</span>
            </div>

            <div class="framt-color-row">
                <label for="secondary_color">Secondary Color</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[secondary_color]"
                       id="secondary_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['secondary_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['secondary_color'] ); ?>"></div>
                <span style="margin-left: 10px; color: #666;">Secondary buttons, badges</span>
            </div>

            <div class="framt-color-row">
                <label for="accent_color">Accent Color</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[accent_color]"
                       id="accent_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['accent_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['accent_color'] ); ?>"></div>
                <span style="margin-left: 10px; color: #666;">Highlights, warnings</span>
            </div>

            <div class="framt-color-row">
                <label for="sidebar_bg_color">Sidebar Background</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[sidebar_bg_color]"
                       id="sidebar_bg_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>"></div>
            </div>

            <div class="framt-color-row">
                <label for="sidebar_text_color">Sidebar Text</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[sidebar_text_color]"
                       id="sidebar_text_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['sidebar_text_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['sidebar_text_color'] ); ?>"></div>
            </div>

            <div class="framt-color-row">
                <label for="header_bg_color">Header Background</label>
                <input type="text" name="<?php echo self::OPTION_NAME; ?>[header_bg_color]"
                       id="header_bg_color" class="framt-color-picker"
                       value="<?php echo esc_attr( $settings['header_bg_color'] ); ?>">
                <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['header_bg_color'] ); ?>"></div>
            </div>
        </div>

        <div class="framt-settings-card">
            <h2>Layout Options</h2>

            <table class="form-table">
                <tr>
                    <th>WordPress Theme Header</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[show_wp_header]"
                                   value="1" <?php checked( $settings['show_wp_header'] ); ?>>
                            Show WordPress theme header above portal
                        </label>
                        <p class="description">Enable if you want to keep your theme's navigation visible.</p>
                    </td>
                </tr>
                <tr>
                    <th>WordPress Theme Footer</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[show_wp_footer]"
                                   value="1" <?php checked( $settings['show_wp_footer'] ); ?>>
                            Show WordPress theme footer below portal
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Promotional Banner</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[show_promo_banner]"
                                   value="1" <?php checked( $settings['show_promo_banner'] ); ?>>
                            Show site branding/promotional banner
                        </label>
                        <p class="description">The banner with your logo and tagline at the top of the portal.</p>
                    </td>
                </tr>
                <tr>
                    <th>Sidebar Position</th>
                    <td>
                        <select name="<?php echo self::OPTION_NAME; ?>[sidebar_position]">
                            <option value="left" <?php selected( $settings['sidebar_position'], 'left' ); ?>>Left</option>
                            <option value="right" <?php selected( $settings['sidebar_position'], 'right' ); ?>>Right</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Sidebar Default State</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[sidebar_collapsed]"
                                   value="1" <?php checked( $settings['sidebar_collapsed'] ); ?>>
                            Start with sidebar collapsed (icons only)
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render menu tab.
     *
     * @param array $settings Current settings.
     */
    private function render_menu_tab( $settings ) {
        $menu_items = array(
            'dashboard' => array( 'icon' => '📊', 'default' => 'Dashboard' ),
            'tasks'     => array( 'icon' => '✓', 'default' => 'Tasks' ),
            'timeline'  => array( 'icon' => '📅', 'default' => 'Timeline' ),
            'messages'  => array( 'icon' => '💬', 'default' => 'Messages' ),
            'documents' => array( 'icon' => '📄', 'default' => 'Documents' ),
            'guides'    => array( 'icon' => '📚', 'default' => 'Guides' ),
            'files'     => array( 'icon' => '📁', 'default' => 'Files' ),
            'family'    => array( 'icon' => '👥', 'default' => 'Family Members' ),
            'settings'  => array( 'icon' => '⚙️', 'default' => 'Settings' ),
            'help'      => array( 'icon' => '❓', 'default' => 'Help & Support' ),
        );
        ?>
        <div class="framt-settings-card">
            <h2>Menu Items</h2>
            <p>Enable or disable menu items, and customize their labels.</p>

            <?php foreach ( $menu_items as $key => $item ) : ?>
            <div class="framt-menu-item">
                <input type="checkbox"
                       name="<?php echo self::OPTION_NAME; ?>[menu_<?php echo $key; ?>]"
                       id="menu_<?php echo $key; ?>"
                       value="1"
                       <?php checked( $settings[ 'menu_' . $key ] ); ?>>
                <label for="menu_<?php echo $key; ?>">
                    <span style="margin-right: 8px;"><?php echo $item['icon']; ?></span>
                    <?php echo esc_html( $item['default'] ); ?>
                </label>
                <input type="text"
                       name="<?php echo self::OPTION_NAME; ?>[label_<?php echo $key; ?>]"
                       value="<?php echo esc_attr( $settings[ 'label_' . $key ] ); ?>"
                       placeholder="<?php echo esc_attr( $item['default'] ); ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render branding tab.
     *
     * @param array $settings Current settings.
     */
    private function render_branding_tab( $settings ) {
        ?>
        <div class="framt-settings-card">
            <h2>Branding</h2>

            <table class="form-table">
                <tr>
                    <th>Portal Title</th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_NAME; ?>[portal_title]"
                               value="<?php echo esc_attr( $settings['portal_title'] ); ?>"
                               class="regular-text">
                        <p class="description">Shown in the browser tab and header.</p>
                    </td>
                </tr>
                <tr>
                    <th>Portal Logo</th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_NAME; ?>[logo_url]"
                               id="logo_url"
                               value="<?php echo esc_attr( $settings['logo_url'] ); ?>"
                               class="regular-text">
                        <button type="button" class="button framt-upload-button" data-target="#logo_url">
                            Select Image
                        </button>
                        <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                        <br><img src="<?php echo esc_url( $settings['logo_url'] ); ?>"
                                 class="framt-image-preview"
                                 style="max-width: 200px; margin-top: 10px;">
                        <?php endif; ?>
                        <p class="description">Logo displayed in the sidebar (recommended: 150x40px).</p>
                    </td>
                </tr>
                <tr>
                    <th>Favicon</th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_NAME; ?>[favicon_url]"
                               id="favicon_url"
                               value="<?php echo esc_attr( $settings['favicon_url'] ); ?>"
                               class="regular-text">
                        <button type="button" class="button framt-upload-button" data-target="#favicon_url">
                            Select Image
                        </button>
                        <p class="description">Custom favicon for the portal page (32x32px).</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render features tab.
     *
     * @param array $settings Current settings.
     */
    private function render_features_tab( $settings ) {
        // Get membership plugin status
        $membership = class_exists( 'FRAMT_Membership' ) ? FRAMT_Membership::get_instance() : null;
        $detected_plugin = $membership ? $membership->get_plugin() : false;
        $require_membership = get_option( 'framt_portal_require_membership', false );
        $demo_mode = get_option( 'framt_enable_demo_mode', false );
        ?>
        <div class="framt-settings-card">
            <h2>Membership Integration</h2>
            <p>Configure membership requirements for portal access.</p>

            <table class="form-table">
                <tr>
                    <th>Detected Plugin</th>
                    <td>
                        <?php if ( $detected_plugin ) : ?>
                            <span style="color: #46b450;">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <strong><?php echo esc_html( ucfirst( $detected_plugin ) ); ?></strong> detected and active
                            </span>
                        <?php else : ?>
                            <span style="color: #dc3232;">
                                <span class="dashicons dashicons-warning"></span>
                                No membership plugin detected
                            </span>
                            <p class="description">
                                Supported plugins: MemberPress (recommended), Paid Memberships Pro, Restrict Content Pro, WooCommerce Memberships
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Require Membership</th>
                    <td>
                        <label>
                            <input type="checkbox" name="framt_portal_require_membership"
                                   value="1" <?php checked( $require_membership ); ?>>
                            Require active membership to access the portal
                        </label>
                        <p class="description">
                            When enabled, users without an active membership will be redirected to the membership page.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Demo Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" name="framt_enable_demo_mode"
                                   value="1" <?php checked( $demo_mode ); ?>>
                            Enable demo mode (bypass membership check)
                        </label>
                        <p class="description">
                            <strong style="color: #dc3232;">For testing only!</strong> When enabled, all logged-in users can access the portal regardless of membership status.
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="framt-settings-card">
            <h2>Portal Features</h2>

            <table class="form-table">
                <tr>
                    <th>Notifications</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enable_notifications]"
                                   value="1" <?php checked( $settings['enable_notifications'] ); ?>>
                            Enable notification bell and alerts
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>File Upload</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enable_file_upload]"
                                   value="1" <?php checked( $settings['enable_file_upload'] ); ?>>
                            Allow members to upload documents
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>AI Assistant</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enable_ai_assistant]"
                                   value="1" <?php checked( $settings['enable_ai_assistant'] ); ?>>
                            Enable AI assistant chat widget
                        </label>
                        <p class="description">Requires AI integration to be configured in the main plugin settings.</p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render advanced tab.
     *
     * @param array $settings Current settings.
     */
    private function render_advanced_tab( $settings ) {
        ?>
        <div class="framt-settings-card">
            <h2>Custom CSS</h2>
            <p>Add custom CSS to further customize the portal appearance.</p>

            <textarea name="<?php echo self::OPTION_NAME; ?>[custom_css]"
                      rows="15"
                      style="width: 100%; font-family: monospace;"
                      placeholder="/* Your custom CSS here */
.sidebar { }
.header { }
.main-content { }"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>

            <h3 style="margin-top: 30px;">CSS Reference</h3>
            <p>Common selectors you can target:</p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><code>.fra-portal-sidebar</code> - The sidebar container</li>
                <li><code>.fra-portal-header</code> - The top header bar</li>
                <li><code>.fra-portal-content</code> - The main content area</li>
                <li><code>.fra-portal-nav-item</code> - Sidebar navigation items</li>
                <li><code>.fra-portal-card</code> - Content cards</li>
                <li><code>.fra-btn-primary</code> - Primary buttons</li>
            </ul>
        </div>

        <div class="framt-settings-card">
            <h2>Debug Information</h2>
            <table class="form-table">
                <tr>
                    <th>Portal Template</th>
                    <td><code>template-portal.php</code></td>
                </tr>
                <tr>
                    <th>React App</th>
                    <td><code>/assets/portal/</code></td>
                </tr>
                <tr>
                    <th>Settings Option</th>
                    <td><code><?php echo self::OPTION_NAME; ?></code></td>
                </tr>
            </table>
        </div>
        <?php
    }
}

// Initialize
new FRAMT_Portal_Settings();
