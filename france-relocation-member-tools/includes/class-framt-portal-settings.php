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
        'menu_checklists'     => true,
        'menu_timeline'       => true,
        'menu_messages'       => true,
        'menu_chat'           => true,
        'menu_documents'      => true,
        'menu_guides'         => true,
        'menu_glossary'       => true,
        'menu_research'       => true,
        'menu_files'          => true,
        'menu_profile'        => true,
        'menu_family'         => true,
        'menu_membership'     => true,
        'menu_settings'       => true,
        'menu_help'           => true,

        // Menu Labels - customizable names
        'label_dashboard'     => 'Dashboard',
        'label_tasks'         => 'Tasks',
        'label_checklists'    => 'Checklists',
        'label_timeline'      => 'Timeline',
        'label_messages'      => 'Messages',
        'label_chat'          => 'Ask AI',
        'label_documents'     => 'Documents',
        'label_guides'        => 'Guides',
        'label_glossary'      => 'Glossary',
        'label_research'      => 'Explore France',
        'label_files'         => 'Files',
        'label_profile'       => 'My Profile',
        'label_family'        => 'Family Members',
        'label_membership'    => 'Membership',
        'label_settings'      => 'Settings',
        'label_help'          => 'Help & Support',

        // Menu Icons - customizable icons
        'icon_dashboard'      => 'LayoutDashboard',
        'icon_tasks'          => 'CheckSquare',
        'icon_checklists'     => 'ClipboardList',
        'icon_timeline'       => 'Calendar',
        'icon_messages'       => 'MessageSquare',
        'icon_chat'           => 'Bot',
        'icon_documents'      => 'FileText',
        'icon_guides'         => 'BookOpen',
        'icon_glossary'       => 'BookMarked',
        'icon_research'       => 'MapPin',
        'icon_files'          => 'FolderOpen',
        'icon_profile'        => 'User',
        'icon_family'         => 'Users',
        'icon_membership'     => 'CreditCard',
        'icon_settings'       => 'Settings',
        'icon_help'           => 'HelpCircle',

        // Menu Section Order (JSON encoded arrays)
        'menu_order_project'   => 'dashboard,tasks,checklists,timeline,messages',
        'menu_order_resources' => 'chat,documents,guides,glossary,research,files',
        'menu_order_account'   => 'profile,family,membership,settings,help',

        // Section Labels
        'section_label_project'   => 'PROJECT',
        'section_label_resources' => 'RESOURCES',
        'section_label_account'   => 'ACCOUNT',

        // Features
        'enable_notifications' => true,
        'enable_file_upload'   => true,
        'enable_ai_assistant'  => false,

        // Custom CSS
        'custom_css'          => '',

        // Welcome Banner
        'welcome_banner_enabled'      => true,
        'welcome_banner_title'        => 'Welcome to Your Relocation Portal!',
        'welcome_banner_message'      => 'This portal is your central hub for managing your move to France. Track your tasks, upload documents, access helpful guides, and stay organized throughout your relocation journey. Start by completing your profile and exploring the different sections in the sidebar.',
        'welcome_banner_bg_color'     => '#ecfdf5',
        'welcome_banner_border_color' => '#10b981',
    );

    /**
     * Available icons for menu items.
     *
     * @var array
     */
    private $available_icons = array(
        'LayoutDashboard' => 'Dashboard',
        'CheckSquare'     => 'Check Square',
        'ClipboardList'   => 'Clipboard List',
        'Calendar'        => 'Calendar',
        'MessageSquare'   => 'Message',
        'Bot'             => 'AI Bot',
        'FileText'        => 'Document',
        'BookOpen'        => 'Book Open',
        'BookMarked'      => 'Bookmark',
        'FolderOpen'      => 'Folder',
        'User'            => 'User',
        'Users'           => 'Users',
        'CreditCard'      => 'Credit Card',
        'Settings'        => 'Settings Gear',
        'HelpCircle'      => 'Help Circle',
        'Home'            => 'Home',
        'Star'            => 'Star',
        'Heart'           => 'Heart',
        'Bell'            => 'Bell',
        'Mail'            => 'Mail',
        'Search'          => 'Search',
        'Map'             => 'Map',
        'MapPin'          => 'Map Pin',
        'Briefcase'       => 'Briefcase',
        'GraduationCap'   => 'Graduation Cap',
        'Plane'           => 'Plane',
        'Building'        => 'Building',
        'Globe'           => 'Globe',
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
            'sidebar_text_color', 'header_bg_color', 'accent_color',
            'welcome_banner_bg_color', 'welcome_banner_border_color'
        );
        foreach ( $color_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
            }
        }

        // Booleans (menu visibility)
        $bool_fields = array(
            'show_wp_header', 'show_wp_footer', 'show_promo_banner',
            'sidebar_collapsed', 'enable_notifications', 'enable_file_upload',
            'enable_ai_assistant', 'welcome_banner_enabled',
            'menu_dashboard', 'menu_tasks', 'menu_checklists', 'menu_timeline',
            'menu_messages', 'menu_chat', 'menu_documents', 'menu_guides',
            'menu_glossary', 'menu_research', 'menu_files', 'menu_profile', 'menu_family',
            'menu_membership', 'menu_settings', 'menu_help'
        );
        foreach ( $bool_fields as $field ) {
            $sanitized[ $field ] = ! empty( $input[ $field ] );
        }

        // Text fields (labels and section labels)
        $text_fields = array(
            'portal_title', 'welcome_banner_title',
            'label_dashboard', 'label_tasks', 'label_checklists', 'label_timeline',
            'label_messages', 'label_chat', 'label_documents', 'label_guides',
            'label_glossary', 'label_research', 'label_files', 'label_profile', 'label_family',
            'label_membership', 'label_settings', 'label_help',
            'section_label_project', 'section_label_resources', 'section_label_account'
        );
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        // Textarea fields (allow newlines)
        if ( isset( $input['welcome_banner_message'] ) ) {
            $sanitized['welcome_banner_message'] = sanitize_textarea_field( $input['welcome_banner_message'] );
        }

        // Icon fields - validate against available icons
        $icon_fields = array(
            'icon_dashboard', 'icon_tasks', 'icon_checklists', 'icon_timeline',
            'icon_messages', 'icon_chat', 'icon_documents', 'icon_guides',
            'icon_glossary', 'icon_research', 'icon_files', 'icon_profile', 'icon_family',
            'icon_membership', 'icon_settings', 'icon_help'
        );
        foreach ( $icon_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $icon = sanitize_text_field( $input[ $field ] );
                // Validate icon exists in available icons
                if ( array_key_exists( $icon, $this->available_icons ) ) {
                    $sanitized[ $field ] = $icon;
                }
            }
        }

        // Menu order fields (comma-separated item IDs)
        $order_fields = array( 'menu_order_project', 'menu_order_resources', 'menu_order_account' );
        foreach ( $order_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                // Sanitize as comma-separated list of valid menu item IDs
                $items = array_map( 'sanitize_key', explode( ',', $input[ $field ] ) );
                $sanitized[ $field ] = implode( ',', array_filter( $items ) );
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

        // Enqueue jQuery UI Sortable for drag-and-drop
        wp_enqueue_script( 'jquery-ui-sortable' );

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
                // Visibility
                'menu_dashboard', 'menu_tasks', 'menu_checklists', 'menu_timeline',
                'menu_messages', 'menu_chat', 'menu_documents', 'menu_guides',
                'menu_glossary', 'menu_research', 'menu_files', 'menu_profile', 'menu_family',
                'menu_membership', 'menu_settings', 'menu_help',
                // Labels
                'label_dashboard', 'label_tasks', 'label_checklists', 'label_timeline',
                'label_messages', 'label_chat', 'label_documents', 'label_guides',
                'label_glossary', 'label_research', 'label_files', 'label_profile', 'label_family',
                'label_membership', 'label_settings', 'label_help',
                // Icons
                'icon_dashboard', 'icon_tasks', 'icon_checklists', 'icon_timeline',
                'icon_messages', 'icon_chat', 'icon_documents', 'icon_guides',
                'icon_glossary', 'icon_research', 'icon_files', 'icon_profile', 'icon_family',
                'icon_membership', 'icon_settings', 'icon_help',
                // Order
                'menu_order_project', 'menu_order_resources', 'menu_order_account',
                // Section labels
                'section_label_project', 'section_label_resources', 'section_label_account',
            ),
            'branding' => array(
                'portal_title', 'logo_url', 'favicon_url',
            ),
            'features' => array(
                'enable_notifications', 'enable_file_upload', 'enable_ai_assistant',
                'welcome_banner_enabled', 'welcome_banner_title', 'welcome_banner_message',
                'welcome_banner_bg_color', 'welcome_banner_border_color',
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
            'welcome_banner_enabled',
            'menu_dashboard', 'menu_tasks', 'menu_checklists', 'menu_timeline',
            'menu_messages', 'menu_chat', 'menu_documents', 'menu_guides',
            'menu_glossary', 'menu_research', 'menu_files', 'menu_profile', 'menu_family',
            'menu_membership', 'menu_settings', 'menu_help',
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
        // Define all menu items with their defaults
        $all_menu_items = array(
            'dashboard'  => array( 'default_label' => 'Dashboard', 'default_icon' => 'LayoutDashboard' ),
            'tasks'      => array( 'default_label' => 'Tasks', 'default_icon' => 'CheckSquare' ),
            'checklists' => array( 'default_label' => 'Checklists', 'default_icon' => 'ClipboardList' ),
            'timeline'   => array( 'default_label' => 'Timeline', 'default_icon' => 'Calendar' ),
            'messages'   => array( 'default_label' => 'Messages', 'default_icon' => 'MessageSquare' ),
            'chat'       => array( 'default_label' => 'Ask AI', 'default_icon' => 'Bot' ),
            'documents'  => array( 'default_label' => 'Documents', 'default_icon' => 'FileText' ),
            'guides'     => array( 'default_label' => 'Guides', 'default_icon' => 'BookOpen' ),
            'glossary'   => array( 'default_label' => 'Glossary', 'default_icon' => 'BookMarked' ),
            'research'   => array( 'default_label' => 'Explore France', 'default_icon' => 'MapPin' ),
            'files'      => array( 'default_label' => 'Files', 'default_icon' => 'FolderOpen' ),
            'profile'    => array( 'default_label' => 'My Profile', 'default_icon' => 'User' ),
            'family'     => array( 'default_label' => 'Family Members', 'default_icon' => 'Users' ),
            'membership' => array( 'default_label' => 'Membership', 'default_icon' => 'CreditCard' ),
            'settings'   => array( 'default_label' => 'Settings', 'default_icon' => 'Settings' ),
            'help'       => array( 'default_label' => 'Help & Support', 'default_icon' => 'HelpCircle' ),
        );

        // Define sections
        $sections = array(
            'project'   => array(
                'label'    => $settings['section_label_project'] ?: 'PROJECT',
                'items'    => explode( ',', $settings['menu_order_project'] ),
            ),
            'resources' => array(
                'label'    => $settings['section_label_resources'] ?: 'RESOURCES',
                'items'    => explode( ',', $settings['menu_order_resources'] ),
            ),
            'account'   => array(
                'label'    => $settings['section_label_account'] ?: 'ACCOUNT',
                'items'    => explode( ',', $settings['menu_order_account'] ),
            ),
        );
        ?>

        <div class="framt-settings-card">
            <h2>Menu Configuration</h2>
            <p>Customize your portal sidebar menu. Drag items to reorder within each section. Toggle visibility, customize labels, and choose icons.</p>

            <!-- Section Labels -->
            <div style="margin-bottom: 30px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <h3 style="margin-top: 0;">Section Labels</h3>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div>
                        <label for="section_label_project"><strong>Project Section:</strong></label><br>
                        <input type="text" id="section_label_project"
                               name="<?php echo self::OPTION_NAME; ?>[section_label_project]"
                               value="<?php echo esc_attr( $settings['section_label_project'] ); ?>"
                               placeholder="PROJECT" style="width: 150px;">
                    </div>
                    <div>
                        <label for="section_label_resources"><strong>Resources Section:</strong></label><br>
                        <input type="text" id="section_label_resources"
                               name="<?php echo self::OPTION_NAME; ?>[section_label_resources]"
                               value="<?php echo esc_attr( $settings['section_label_resources'] ); ?>"
                               placeholder="RESOURCES" style="width: 150px;">
                    </div>
                    <div>
                        <label for="section_label_account"><strong>Account Section:</strong></label><br>
                        <input type="text" id="section_label_account"
                               name="<?php echo self::OPTION_NAME; ?>[section_label_account]"
                               value="<?php echo esc_attr( $settings['section_label_account'] ); ?>"
                               placeholder="ACCOUNT" style="width: 150px;">
                    </div>
                </div>
            </div>

            <?php foreach ( $sections as $section_id => $section ) : ?>
            <div class="framt-menu-section" data-section="<?php echo esc_attr( $section_id ); ?>">
                <div class="framt-menu-section-header">
                    <h3><?php echo esc_html( $section['label'] ); ?></h3>
                    <span class="framt-drag-hint">Drag to reorder</span>
                </div>

                <div class="framt-menu-sortable" id="sortable-<?php echo esc_attr( $section_id ); ?>">
                    <?php
                    foreach ( $section['items'] as $item_id ) :
                        $item_id = trim( $item_id );
                        if ( empty( $item_id ) || ! isset( $all_menu_items[ $item_id ] ) ) continue;
                        $item = $all_menu_items[ $item_id ];
                        $is_enabled = ! empty( $settings[ 'menu_' . $item_id ] );
                        $label = $settings[ 'label_' . $item_id ] ?: $item['default_label'];
                        $icon = $settings[ 'icon_' . $item_id ] ?: $item['default_icon'];
                    ?>
                    <div class="framt-menu-item-row <?php echo $is_enabled ? '' : 'framt-menu-disabled'; ?>"
                         data-item-id="<?php echo esc_attr( $item_id ); ?>">
                        <div class="framt-menu-drag-handle">
                            <span class="dashicons dashicons-menu"></span>
                        </div>

                        <div class="framt-menu-visibility">
                            <label class="framt-switch">
                                <input type="checkbox"
                                       name="<?php echo self::OPTION_NAME; ?>[menu_<?php echo esc_attr( $item_id ); ?>]"
                                       value="1"
                                       class="framt-visibility-toggle"
                                       <?php checked( $is_enabled ); ?>>
                                <span class="framt-switch-slider"></span>
                            </label>
                        </div>

                        <div class="framt-menu-icon-select">
                            <select name="<?php echo self::OPTION_NAME; ?>[icon_<?php echo esc_attr( $item_id ); ?>]"
                                    class="framt-icon-dropdown">
                                <?php foreach ( $this->available_icons as $icon_key => $icon_label ) : ?>
                                <option value="<?php echo esc_attr( $icon_key ); ?>"
                                        <?php selected( $icon, $icon_key ); ?>>
                                    <?php echo esc_html( $icon_label ); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="framt-menu-label-input">
                            <input type="text"
                                   name="<?php echo self::OPTION_NAME; ?>[label_<?php echo esc_attr( $item_id ); ?>]"
                                   value="<?php echo esc_attr( $label ); ?>"
                                   placeholder="<?php echo esc_attr( $item['default_label'] ); ?>">
                        </div>

                        <div class="framt-menu-item-id">
                            <code><?php echo esc_html( $item_id ); ?></code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Hidden field to store order -->
                <input type="hidden"
                       name="<?php echo self::OPTION_NAME; ?>[menu_order_<?php echo esc_attr( $section_id ); ?>]"
                       id="menu_order_<?php echo esc_attr( $section_id ); ?>"
                       value="<?php echo esc_attr( implode( ',', $section['items'] ) ); ?>"
                       class="framt-menu-order-field">
            </div>
            <?php endforeach; ?>
        </div>

        <style>
            .framt-menu-section {
                margin-bottom: 30px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                background: #fff;
            }
            .framt-menu-section-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 15px;
                background: #f0f0f1;
                border-bottom: 1px solid #c3c4c7;
            }
            .framt-menu-section-header h3 {
                margin: 0;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #1d2327;
            }
            .framt-drag-hint {
                font-size: 11px;
                color: #787c82;
                font-style: italic;
            }
            .framt-menu-sortable {
                padding: 10px;
            }
            .framt-menu-item-row {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                margin-bottom: 8px;
                background: #f9f9f9;
                border: 1px solid #dcdcde;
                border-radius: 4px;
                cursor: move;
                transition: all 0.2s ease;
            }
            .framt-menu-item-row:hover {
                background: #f0f6fc;
                border-color: #2271b1;
            }
            .framt-menu-item-row.ui-sortable-helper {
                background: #fff;
                box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            }
            .framt-menu-item-row.ui-sortable-placeholder {
                background: #e7f3ff;
                border: 2px dashed #2271b1;
                visibility: visible !important;
            }
            .framt-menu-disabled {
                opacity: 0.5;
                background: #f0f0f1;
            }
            .framt-menu-drag-handle {
                color: #787c82;
                cursor: move;
            }
            .framt-menu-drag-handle .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            .framt-menu-visibility {
                flex-shrink: 0;
            }
            .framt-switch {
                position: relative;
                display: inline-block;
                width: 40px;
                height: 22px;
            }
            .framt-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .framt-switch-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: 0.3s;
                border-radius: 22px;
            }
            .framt-switch-slider:before {
                position: absolute;
                content: "";
                height: 16px;
                width: 16px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
            }
            .framt-switch input:checked + .framt-switch-slider {
                background-color: #2271b1;
            }
            .framt-switch input:checked + .framt-switch-slider:before {
                transform: translateX(18px);
            }
            .framt-menu-icon-select select {
                width: 140px;
                padding: 4px 8px;
            }
            .framt-menu-label-input {
                flex: 1;
            }
            .framt-menu-label-input input {
                width: 100%;
                padding: 6px 10px;
            }
            .framt-menu-item-id {
                flex-shrink: 0;
            }
            .framt-menu-item-id code {
                font-size: 11px;
                color: #787c82;
                background: #f0f0f1;
                padding: 2px 6px;
                border-radius: 3px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Initialize sortable for each section
            $('.framt-menu-sortable').sortable({
                handle: '.framt-menu-drag-handle',
                placeholder: 'framt-menu-item-row ui-sortable-placeholder',
                forcePlaceholderSize: true,
                tolerance: 'pointer',
                update: function(event, ui) {
                    updateMenuOrder($(this));
                }
            });

            // Update hidden field with new order
            function updateMenuOrder($sortable) {
                var sectionId = $sortable.attr('id').replace('sortable-', '');
                var order = [];
                $sortable.find('.framt-menu-item-row').each(function() {
                    order.push($(this).data('item-id'));
                });
                $('#menu_order_' + sectionId).val(order.join(','));
            }

            // Toggle visibility styling
            $(document).on('change', '.framt-visibility-toggle', function() {
                var $row = $(this).closest('.framt-menu-item-row');
                if ($(this).is(':checked')) {
                    $row.removeClass('framt-menu-disabled');
                } else {
                    $row.addClass('framt-menu-disabled');
                }
            });
        });
        </script>
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

        <div class="framt-settings-card">
            <h2>Welcome Banner</h2>
            <p>Display a dismissible welcome message for new members on the dashboard. Once dismissed, it won't appear again for that user.</p>

            <table class="form-table">
                <tr>
                    <th>Enable Welcome Banner</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_enabled]"
                                   value="1" <?php checked( $settings['welcome_banner_enabled'] ); ?>>
                            Show welcome banner for new members
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Banner Title</th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_title]"
                               value="<?php echo esc_attr( $settings['welcome_banner_title'] ); ?>"
                               class="regular-text"
                               placeholder="Welcome to Your Relocation Portal!">
                    </td>
                </tr>
                <tr>
                    <th>Banner Message</th>
                    <td>
                        <textarea name="<?php echo self::OPTION_NAME; ?>[welcome_banner_message]"
                                  rows="4"
                                  class="large-text"
                                  placeholder="Write a helpful getting started message for new members..."><?php echo esc_textarea( $settings['welcome_banner_message'] ); ?></textarea>
                        <p class="description">Explain how the portal works and what members should do first.</p>
                    </td>
                </tr>
                <tr>
                    <th>Background Color</th>
                    <td>
                        <div class="framt-color-row">
                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_bg_color]"
                                   class="framt-color-picker"
                                   value="<?php echo esc_attr( $settings['welcome_banner_bg_color'] ); ?>">
                            <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['welcome_banner_bg_color'] ); ?>"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Border Color</th>
                    <td>
                        <div class="framt-color-row">
                            <input type="text" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_border_color]"
                                   class="framt-color-picker"
                                   value="<?php echo esc_attr( $settings['welcome_banner_border_color'] ); ?>">
                            <div class="framt-color-preview" style="background-color: <?php echo esc_attr( $settings['welcome_banner_border_color'] ); ?>"></div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Preview -->
            <div style="margin-top: 20px;">
                <h4>Preview:</h4>
                <div id="welcome-banner-preview" style="padding: 16px 20px; border-radius: 8px; border-width: 2px; border-style: solid; background-color: <?php echo esc_attr( $settings['welcome_banner_bg_color'] ); ?>; border-color: <?php echo esc_attr( $settings['welcome_banner_border_color'] ); ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <strong style="font-size: 16px; display: block; margin-bottom: 8px;"><?php echo esc_html( $settings['welcome_banner_title'] ); ?></strong>
                            <p style="margin: 0; color: #374151;"><?php echo esc_html( $settings['welcome_banner_message'] ); ?></p>
                        </div>
                        <button type="button" style="background: none; border: none; cursor: pointer; padding: 4px; opacity: 0.6;">✕</button>
                    </div>
                </div>
            </div>
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
