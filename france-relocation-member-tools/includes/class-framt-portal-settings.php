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
    /**
     * The brand palette, in one place.
     *
     * Copied from the public site's design tokens so the portal and the site
     * are visibly one product. Changing a colour here is a rebrand, not a
     * preference - the migration below re-applies it to sites still carrying
     * the old green.
     */
    const BRAND = array(
        'primary_color'      => '#2c5346', // vine
        'secondary_color'    => '#5f6e66', // muted
        'sidebar_bg_color'   => '#23332c', // shell
        'sidebar_text_color' => '#ffffff',
        'header_bg_color'    => '#ffffff',
        'accent_color'       => '#b87a21', // honey
    );

    /** Bumped when BRAND changes, to re-apply it once. */
    const BRAND_VERSION = 2;
    const BRAND_VERSION_OPTION = 'framt_portal_brand_version';

    private $defaults = array(
        // Appearance
        // Brand palette, shared with the public site. Vine is the primary,
        // honey the accent, and the sidebar is the site's "shell" tone rather
        // than a neutral slate - see site/src/styles/tokens.css, which is the
        // source these values are copied from.
        'primary_color'       => self::BRAND['primary_color'],
        'secondary_color'     => self::BRAND['secondary_color'],
        'sidebar_bg_color'    => self::BRAND['sidebar_bg_color'],
        'sidebar_text_color'  => self::BRAND['sidebar_text_color'],
        'header_bg_color'     => self::BRAND['header_bg_color'],
        'accent_color'        => self::BRAND['accent_color'],

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
        'menu_schengen'       => true,
        'menu_membership'     => true,
        'menu_settings'       => true,
        'menu_help'           => true,

        // Menu Labels - customizable names
        'label_dashboard'     => 'Where you are',
        'label_tasks'         => 'Steps',
        'label_checklists'    => 'Checklists',
        'label_timeline'      => 'Deadlines',
        'label_messages'      => 'Messages',
        'label_chat'          => 'Ask about my case',
        'label_documents'     => 'Documents & files',
        'label_guides'        => 'Guides',
        'label_glossary'      => 'Glossary',
        'label_research'      => 'Explore France',
        'label_files'         => 'Files',
        'label_profile'       => 'Profile',
        'label_family'        => 'Family plans',
        'label_schengen'      => 'Schengen days',
        'label_membership'    => 'Membership',
        'label_settings'      => 'Settings',
        'label_help'          => 'Help',

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
        'icon_schengen'       => 'Globe',
        'icon_membership'     => 'CreditCard',
        'icon_settings'       => 'Settings',
        'icon_help'           => 'HelpCircle',

        // Menu Section Order (JSON encoded arrays)
        'menu_order_project'   => 'dashboard,tasks,checklists,timeline,messages',
        'menu_order_resources' => 'chat,documents,guides,glossary,research,schengen,files',
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
        'welcome_banner_message'      => 'Start with the five questions under Decide: your route, where in France, what it takes, who is moving, and when. Everything after that is dated from your answers.',
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
        'Headphones'      => 'Headphones',
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
        self::maybe_apply_brand();
        $saved = get_option( self::OPTION_NAME, array() );
        return wp_parse_args( $saved, $instance->defaults );
    }

    /**
     * Re-apply the brand palette once after a rebrand.
     *
     * Saved settings win over defaults, so a site that has ever opened this
     * page keeps the old colours forever - which would leave the portal green
     * while the public site is not. This runs once per BRAND_VERSION and
     * touches only the six colour keys, leaving every other saved setting and
     * any custom CSS alone.
     */
    public static function maybe_apply_brand() {
        if ( (int) get_option( self::BRAND_VERSION_OPTION, 0 ) >= self::BRAND_VERSION ) {
            return;
        }

        $saved = get_option( self::OPTION_NAME, array() );

        if ( is_array( $saved ) && ! empty( $saved ) ) {
            update_option( self::OPTION_NAME, array_merge( $saved, self::BRAND ) );
        }

        update_option( self::BRAND_VERSION_OPTION, self::BRAND_VERSION );
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

        // The Family add-on: a one-time MemberPress product. Setting its ID
        // makes the feature paid; leaving it empty keeps the global switch.
        register_setting(
            'framt_portal_settings_group',
            'framt_family_addon_product_id',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            )
        );
        register_setting(
            'framt_portal_settings_group',
            'framt_family_addon_url',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default'           => '',
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
            'menu_schengen', 'menu_membership', 'menu_settings', 'menu_help'
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
            'label_schengen', 'label_membership', 'label_settings', 'label_help',
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
            'icon_schengen', 'icon_membership', 'icon_settings', 'icon_help'
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
     * The settings screen.
     *
     * Three tabs, and only the controls the portal actually reads. The rail's
     * shape, the names of things and the colours of the page are product
     * decisions made in the portal build; what is left here is what a site
     * owner genuinely changes: sidebar colours, the tab title, which tools
     * are on, membership and the welcome banner.
     */
    public function render_settings_page() {
        $settings   = self::get_settings();
        $requested  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'portal';
        $aliases    = array( 'appearance' => 'portal', 'branding' => 'portal', 'advanced' => 'portal', 'menu' => 'tools', 'features' => 'members' );
        $active_tab = $aliases[ $requested ] ?? $requested;
        if ( ! in_array( $active_tab, array( 'portal', 'tools', 'members' ), true ) ) {
            $active_tab = 'portal';
        }
        $tabs = array( 'portal' => 'Portal', 'tools' => 'Tools', 'members' => 'Members' );
        ?>
        <div class="wrap framt-portal-settings">
            <h1>Portal Settings</h1>

            <nav class="nav-tab-wrapper">
                <?php foreach ( $tabs as $id => $label ) : ?>
                <a href="?page=framt-portal-settings&tab=<?php echo esc_attr( $id ); ?>"
                   class="nav-tab <?php echo $active_tab === $id ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php">
                <?php settings_fields( 'framt_portal_settings_group' ); ?>
                <?php $this->render_hidden_fields( $settings, $active_tab ); ?>

                <div class="tab-content" style="margin-top: 20px;">
                    <?php
                    if ( 'tools' === $active_tab ) {
                        $this->render_tools_tab( $settings );
                    } elseif ( 'members' === $active_tab ) {
                        $this->render_members_tab( $settings );
                    } else {
                        $this->render_portal_tab( $settings );
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>

            <p class="description" style="margin-top: 24px;">
                <button type="button" class="button-link" id="framt-reset-settings" style="color: #b32d2e;">Reset every portal setting to its default</button>
            </p>
        </div>

        <style>
            .framt-settings-card { background: #fff; border: 1px solid #dde3de; border-radius: 8px; padding: 20px 24px; margin-bottom: 20px; max-width: 820px; }
            .framt-settings-card h2 { margin: 0 0 4px; font-size: 1.15em; }
            .framt-settings-card > p.lead { margin: 0 0 16px; color: #5f6e66; }
            .framt-switch-list { list-style: none; margin: 0; padding: 0; }
            .framt-switch-list li { display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-top: 1px solid #ebefeb; }
            .framt-switch-list li:first-child { border-top: 0; }
            .framt-switch-list input[type="checkbox"] { margin-top: 3px; }
            .framt-switch-list strong { display: block; }
            .framt-switch-list span { color: #5f6e66; font-size: 12px; }
            .framt-fixed { color: #5f6e66; font-size: 13px; margin: 6px 0 0; }
            .framt-status td { padding: 6px 12px 6px 0; vertical-align: top; }
            .framt-status th { text-align: left; padding: 6px 16px 6px 0; font-weight: 600; white-space: nowrap; }
            .framt-color-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
            .framt-color-row label { width: 220px; font-weight: 500; }
            .framt-color-row input[type="color"] { width: 44px; height: 32px; padding: 0; border: 1px solid #dde3de; border-radius: 4px; background: none; }
            .framt-color-row code { color: #5f6e66; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#framt-reset-settings').on('click', function() {
                if (!confirm('Reset every portal setting to its default? Membership integration and the family add-on product are kept.')) {
                    return;
                }
                $.post(ajaxurl, {
                    action: 'framt_reset_portal_settings',
                    nonce: '<?php echo wp_create_nonce( 'framt_portal_settings' ); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Could not reset: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Which option keys each tab renders. Everything else is carried through
     * as a hidden field so saving one tab never blanks another.
     */
    private function tab_fields( $tab ) {
        $tools = array_map( function ( $id ) { return 'menu_' . $id; }, array_keys( $this->tool_switches() ) );
        $map = array(
            'portal'  => array( 'sidebar_bg_color', 'sidebar_text_color', 'portal_title' ),
            'tools'   => $tools,
            'members' => array( 'welcome_banner_enabled', 'welcome_banner_title', 'welcome_banner_message' ),
        );
        return $map[ $tab ] ?? array();
    }

    /**
     * Carry every other setting through the form untouched.
     */
    private function render_hidden_fields( $settings, $active_tab ) {
        $visible = $this->tab_fields( $active_tab );
        foreach ( $this->defaults as $key => $default ) {
            if ( in_array( $key, $visible, true ) ) {
                continue;
            }
            $value = $settings[ $key ] ?? $default;
            if ( is_bool( $value ) ) {
                $value = $value ? '1' : '0';
            } elseif ( is_array( $value ) ) {
                continue;
            }
            printf( '<input type="hidden" name="%s[%s]" value="%s">', esc_attr( self::OPTION_NAME ), esc_attr( $key ), esc_attr( (string) $value ) );
        }
    }

    /**
     * The tools a member can be shown or not. Home, the six stages, Profile
     * and Support are not switches: a member always has them.
     */
    private function tool_switches() {
        return array(
            'chat'      => array( 'Ask about my case', 'The assistant, answering against the member\'s file and the knowledge base.' ),
            'documents' => array( 'Documents & files', 'The dossier per person, uploads, generated documents and saved reports.' ),
            'family'    => array( 'Family plans', 'One file per person, the partner sign-in and the $35 add-on offer.' ),
            'timeline'  => array( 'Deadlines', 'Every dated step counted back from the move.' ),
            'schengen'  => array( 'Schengen days', 'The 90/180 tracker, carrying the coming-soon note.' ),
            'research'  => array( 'Explore France', 'Regions, departments and communes, with generated location reports.' ),
            'messages'  => array( 'Messages', 'What the site sends the member: team messages, notices, alerts from their own file.' ),
            'settings'  => array( 'Settings', 'The member\'s own preferences.' ),
            'help'      => array( 'Help', 'The help pages. Support sits beside it and is always shown.' ),
        );
    }

    /* ------------------------------------------------------------------ */

    private function render_portal_tab( $settings ) {
        $manifest = FRAMT_PLUGIN_DIR . 'assets/portal/.vite/manifest.json';
        $bundle   = '';
        if ( file_exists( $manifest ) ) {
            $m = json_decode( (string) file_get_contents( $manifest ), true );
            foreach ( (array) $m as $entry ) {
                if ( ! empty( $entry['isEntry'] ) && ! empty( $entry['file'] ) ) {
                    $bundle = basename( $entry['file'] );
                    break;
                }
            }
        }
        $membership = class_exists( 'FRAMT_Membership' ) ? FRAMT_Membership::get_instance() : null;
        $plugin     = $membership ? $membership->get_plugin() : false;
        $worker     = (string) get_option( 'framt_review_worker_url', '' );
        $worker     = '' !== $worker ? $worker : 'https://relo2france-review.kburrowbridge.workers.dev';
        ?>
        <div class="framt-settings-card">
            <h2>Sidebar</h2>
            <p class="lead">The only colours the portal takes from here. Everything else on the page follows the site\'s design tokens.</p>
            <div class="framt-color-row">
                <label for="sidebar_bg_color">Sidebar background</label>
                <input type="color" id="sidebar_bg_color" name="<?php echo self::OPTION_NAME; ?>[sidebar_bg_color]" value="<?php echo esc_attr( $settings['sidebar_bg_color'] ); ?>">
                <code><?php echo esc_html( $settings['sidebar_bg_color'] ); ?></code>
            </div>
            <div class="framt-color-row">
                <label for="sidebar_text_color">Sidebar text</label>
                <input type="color" id="sidebar_text_color" name="<?php echo self::OPTION_NAME; ?>[sidebar_text_color]" value="<?php echo esc_attr( $settings['sidebar_text_color'] ); ?>">
                <code><?php echo esc_html( $settings['sidebar_text_color'] ); ?></code>
            </div>
        </div>

        <div class="framt-settings-card">
            <h2>Browser tab</h2>
            <p class="lead">Shown as "<em>title</em> – <?php echo esc_html( get_bloginfo( 'name' ) ); ?>" in the tab.</p>
            <input type="text" class="regular-text" name="<?php echo self::OPTION_NAME; ?>[portal_title]" value="<?php echo esc_attr( $settings['portal_title'] ); ?>">
        </div>

        <div class="framt-settings-card">
            <h2>Status</h2>
            <table class="framt-status">
                <tr><th>Member tools</th><td><?php echo esc_html( FRAMT_VERSION ); ?></td></tr>
                <tr><th>Portal build</th><td><?php echo $bundle ? esc_html( $bundle ) : 'not built'; ?></td></tr>
                <tr><th>Membership plugin</th><td><?php echo $plugin ? esc_html( ucfirst( $plugin ) ) . ' detected' : 'none detected'; ?></td></tr>
                <tr><th>Review worker</th><td><code><?php echo esc_html( $worker ); ?></code><br><span style="color:#5f6e66;">Runs the knowledge-base review, gap drafting and location reports. Authenticates with the Review API secret from FR Assistant → API Settings.</span></td></tr>
                <tr><th>Portal page</th><td><a href="<?php echo esc_url( home_url( '/portal/' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/portal/' ) ); ?></a></td></tr>
            </table>
        </div>

        <?php $crashes = get_option( 'framt_client_errors', array() ); $crashes = is_array( $crashes ) ? array_slice( $crashes, 0, 10 ) : array(); ?>
        <div class="framt-settings-card">
            <h2>Portal crashes</h2>
            <p style="color:#5f6e66;margin-top:0;">What a member saw when a page broke. The portal reports these itself; the last twenty are kept.</p>
            <?php if ( empty( $crashes ) ) : ?>
                <p><strong>None recorded.</strong></p>
            <?php else : ?>
                <table class="framt-status">
                    <?php foreach ( $crashes as $c ) : ?>
                        <tr>
                            <th style="white-space:nowrap;"><?php echo esc_html( (string) ( $c['when'] ?? '' ) ); ?><br><span style="font-weight:normal;color:#5f6e66;">user <?php echo (int) ( $c['user_id'] ?? 0 ); ?></span></th>
                            <td>
                                <strong><?php echo esc_html( (string) ( $c['message'] ?? '' ) ); ?></strong><br>
                                <span style="color:#5f6e66;"><?php echo esc_html( (string) ( $c['url'] ?? '' ) ); ?></span>
                                <?php if ( ! empty( $c['component'] ) ) : ?><details><summary>Where</summary><pre style="white-space:pre-wrap;font-size:11px;"><?php echo esc_html( (string) $c['component'] ); ?></pre></details><?php endif; ?>
                                <?php if ( ! empty( $c['stack'] ) ) : ?><details><summary>Stack</summary><pre style="white-space:pre-wrap;font-size:11px;"><?php echo esc_html( (string) $c['stack'] ); ?></pre></details><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_tools_tab( $settings ) {
        ?>
        <div class="framt-settings-card">
            <h2>What members see</h2>
            <p class="lead">The rail is fixed: <strong>Where you are</strong>, the six stages of the move, then the tools below, then the account. Steps, checklists and guides live inside the stage they belong to. Turn a tool off and it disappears from every member\'s rail; nothing else moves.</p>
            <ul class="framt-switch-list">
                <?php foreach ( $this->tool_switches() as $id => $item ) :
                    $key = 'menu_' . $id;
                    $on  = ! empty( $settings[ $key ] ); ?>
                <li>
                    <input type="hidden" name="<?php echo self::OPTION_NAME; ?>[<?php echo esc_attr( $key ); ?>]" value="0">
                    <input type="checkbox" id="<?php echo esc_attr( $key ); ?>" name="<?php echo self::OPTION_NAME; ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $on ); ?>>
                    <label for="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $item[0] ); ?></strong><span><?php echo esc_html( $item[1] ); ?></span></label>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="framt-fixed">Always shown: Where you are, the six stages, Profile, Support.</p>
        </div>
        <?php
    }

    private function render_members_tab( $settings ) {
        $membership = class_exists( 'FRAMT_Membership' ) ? FRAMT_Membership::get_instance() : null;
        $plugin     = $membership ? $membership->get_plugin() : false;
        $demo       = get_option( 'framt_enable_demo_mode', false );
        ?>
        <div class="framt-settings-card">
            <h2>Who gets in</h2>
            <p class="lead"><?php echo $plugin ? esc_html( ucfirst( $plugin ) ) . ' is detected and active.' : 'No membership plugin detected; every signed-in user can open the portal.'; ?></p>
            <ul class="framt-switch-list">
                <li>
                    <label><strong>An active membership is always required</strong><span>Signed-in people without one are sent to the pricing page, and the portal's data answers only members. A partner invited from the Family plan passes on the account holder's membership. Admins always get in.</span></label>
                </li>
                <li>
                    <input type="checkbox" id="framt_enable_demo_mode" name="framt_enable_demo_mode" value="1" <?php checked( $demo ); ?>>
                    <label for="framt_enable_demo_mode"><strong>Demo mode</strong><span style="color:#b32d2e;">Testing only: every signed-in user gets in regardless of membership.</span></label>
                </li>
            </ul>
        </div>

        <div class="framt-settings-card">
            <h2>Family add-on</h2>
            <p class="lead">The $35 one-time MemberPress product for one partner and up to four children. Once its ID is set, only members who bought it can edit family files and invite a partner; everyone else sees the offer.</p>
            <table class="form-table" style="margin-top:0;">
                <tr>
                    <th scope="row"><label for="framt_family_addon_product_id">Product ID</label></th>
                    <td><input type="number" min="0" id="framt_family_addon_product_id" name="framt_family_addon_product_id" class="small-text" value="<?php echo esc_attr( (int) get_option( 'framt_family_addon_product_id', 0 ) ); ?>">
                        <p class="description">0 keeps the feature open to every member.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="framt_family_addon_url">Checkout URL</label></th>
                    <td><input type="url" id="framt_family_addon_url" name="framt_family_addon_url" class="regular-text" value="<?php echo esc_attr( get_option( 'framt_family_addon_url', '' ) ); ?>" placeholder="<?php echo esc_attr( home_url( '/register/family-add-on/' ) ); ?>">
                        <p class="description">Where "Add the Family plan" sends members. Empty means the placeholder.</p></td>
                </tr>
            </table>
        </div>

        <div class="framt-settings-card">
            <h2>Welcome banner</h2>
            <p class="lead">A dismissible note at the top of a new member\'s home. Once dismissed it does not come back for that member.</p>
            <ul class="framt-switch-list">
                <li>
                    <input type="hidden" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_enabled]" value="0">
                    <input type="checkbox" id="welcome_banner_enabled" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_enabled]" value="1" <?php checked( ! empty( $settings['welcome_banner_enabled'] ) ); ?>>
                    <label for="welcome_banner_enabled"><strong>Show the banner to new members</strong></label>
                </li>
            </ul>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="welcome_banner_title">Title</label></th>
                    <td><input type="text" id="welcome_banner_title" class="regular-text" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_title]" value="<?php echo esc_attr( $settings['welcome_banner_title'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="welcome_banner_message">Message</label></th>
                    <td><textarea id="welcome_banner_message" class="large-text" rows="4" name="<?php echo self::OPTION_NAME; ?>[welcome_banner_message]"><?php echo esc_textarea( $settings['welcome_banner_message'] ); ?></textarea></td>
                </tr>
            </table>
        </div>
        <?php
    }

}

// Initialize
new FRAMT_Portal_Settings();
