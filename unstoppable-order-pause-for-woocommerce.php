<?php
/**
 * Plugin Name:       Unstoppable Order Pause for WooCommerce
 * Plugin URI:        https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-order-pause-for-woocommerce
 * Description:       Pause orders for the day or schedule breaks during busy hours. Quick toggle from admin bar under Options Studio.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Unstoppable Solutions
 * Author URI:        https://unstoppablesolutions.app
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unstoppable-order-pause-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   9.4
 *
 * @package UOPWC_Order_Pause
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Declare WooCommerce HPOS and Cart/Checkout Blocks compatibility.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

// Plugin constants
define( 'UOPWC_VERSION', '1.2.0' );
define( 'UOPWC_PLUGIN_FILE', __FILE__ );
define( 'UOPWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UOPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UOPWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'UOPWC_PLUGIN_SLUG', 'unstoppable-order-pause-for-woocommerce' );
define( 'UOPWC_OPTION_PREFIX', 'uopwc' );

/*
 * ============================================================================
 * UNSTOPPABLE OPTIONS STUDIO - SHARED SHELL SYSTEM
 * ============================================================================
 * 
 * This code creates the "Options Studio" parent menu that all Unstoppable Solutions
 * plugins share. Each plugin includes this same code block - the first plugin
 * to load creates the shell, others just add their submenus.
 * ============================================================================
 */

if ( ! function_exists( 'unstoppable_options_studio_init' ) ) {
    /**
     * Initialize the Options Studio shell menu.
     * This function is shared across all Unstoppable Solutions plugins.
     * Only the first plugin to load will create the parent menu.
     */
    function unstoppable_options_studio_init() {
        add_action( 'admin_menu', 'unstoppable_options_studio_create_menu', 9 );
        add_action( 'admin_enqueue_scripts', 'unstoppable_options_studio_menu_icon_css' );
        add_action( 'admin_bar_menu', 'unstoppable_options_studio_admin_bar', 100 );
        add_action( 'wp_enqueue_scripts', 'unstoppable_options_studio_admin_bar_css' );
        add_action( 'admin_enqueue_scripts', 'unstoppable_options_studio_admin_bar_css' );
    }

    /**
     * Create the Options Studio parent menu.
     */
    function unstoppable_options_studio_create_menu() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // Check if parent menu already exists (another Unstoppable plugin may have created it)
        global $menu;
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && $item[2] === 'unstoppable-studio' ) {
                return; // Menu already exists
            }
        }

        // Create the parent menu
        add_menu_page(
            __( 'Options Studio', 'unstoppable-order-pause-for-woocommerce' ),
            __( 'Options Studio', 'unstoppable-order-pause-for-woocommerce' ),
            'manage_woocommerce',
            'unstoppable-studio',
            'unstoppable_options_studio_dashboard',
            'dashicons-screenoptions',
            56 // Position after WooCommerce
        );

        // Add dashboard as first submenu (replaces the duplicate parent link)
        add_submenu_page(
            'unstoppable-studio',
            __( 'Dashboard', 'unstoppable-order-pause-for-woocommerce' ),
            __( 'Dashboard', 'unstoppable-order-pause-for-woocommerce' ),
            'manage_woocommerce',
            'unstoppable-studio',
            'unstoppable_options_studio_dashboard'
        );
    }

    /**
     * Options Studio Dashboard page.
     * Shows overview of all installed Unstoppable Solutions plugins.
     */
    function unstoppable_options_studio_dashboard() {
        $plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );
        ?>
        <div class="wrap unstoppable-studio-dashboard">
            <h1><?php esc_html_e( 'Options Studio', 'unstoppable-order-pause-for-woocommerce' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Your suite of WooCommerce enhancement tools by Unstoppable Solutions.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
            
            <div class="unstoppable-studio-plugins" style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <?php if ( empty( $plugins ) ) : ?>
                    <p><?php esc_html_e( 'No plugins registered yet.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $plugins as $plugin ) : ?>
                        <div class="unstoppable-plugin-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px;">
                            <h3 style="margin-top: 0;"><?php echo esc_html( $plugin['name'] ); ?></h3>
                            <p style="color: #666;"><?php echo esc_html( $plugin['description'] ); ?></p>
                            <?php if ( ! empty( $plugin['menu_slug'] ) ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $plugin['menu_slug'] ) ); ?>" class="button button-primary">
                                    <?php esc_html_e( 'Open', 'unstoppable-order-pause-for-woocommerce' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="unstoppable-studio-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
                <p>
                    <a href="https://unstoppablesolutions.app" target="_blank"><?php esc_html_e( 'Visit Unstoppable Solutions', 'unstoppable-order-pause-for-woocommerce' ); ?></a> | 
                    <a href="https://unstoppablesolutions.app/support" target="_blank"><?php esc_html_e( 'Get Support', 'unstoppable-order-pause-for-woocommerce' ); ?></a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Add custom styling for the sidebar menu icon (gold/yellow color).
     * Uses wp_add_inline_style() instead of inline <style> blocks per WordPress.org requirements.
     */
    function unstoppable_options_studio_menu_icon_css() {
        // Register an empty handle as an anchor for inline styles.
        wp_register_style( 'unstoppable-studio-admin', false, array(), '1.0.0' );
        wp_enqueue_style( 'unstoppable-studio-admin' );

        $css = '
            #adminmenu .toplevel_page_unstoppable-studio .wp-menu-image:before {
                color: #f0b849;
            }
            #adminmenu .toplevel_page_unstoppable-studio:hover .wp-menu-image:before,
            #adminmenu .toplevel_page_unstoppable-studio.current .wp-menu-image:before {
                color: #fff;
            }
        ';

        wp_add_inline_style( 'unstoppable-studio-admin', $css );
    }

    /**
     * Add Options Studio to WordPress admin bar.
     */
    function unstoppable_options_studio_admin_bar( $wp_admin_bar ) {
        // Only show for users who can manage WooCommerce
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        
        // Main Options Studio node
        $wp_admin_bar->add_node( array(
            'id'    => 'unstoppable-studio',
            'title' => '<span class="ab-icon dashicons dashicons-screenoptions"></span>' . __( 'Options Studio', 'unstoppable-order-pause-for-woocommerce' ),
            'href'  => admin_url( 'admin.php?page=unstoppable-studio' ),
            'meta'  => array(
                'title' => __( 'Options Studio Dashboard', 'unstoppable-order-pause-for-woocommerce' ),
            ),
        ) );
        
        // Add registered plugins as sub-menu items
        $plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );
        
        foreach ( $plugins as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['menu_slug'] ) ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'unstoppable-studio-' . sanitize_key( $plugin_id ),
                    'parent' => 'unstoppable-studio',
                    'title'  => $plugin['name'],
                    'href'   => admin_url( 'admin.php?page=' . $plugin['menu_slug'] ),
                ) );
            }
        }
    }

    /**
     * Admin bar styles for Options Studio icon.
     * Uses wp_add_inline_style() instead of inline <style> blocks per WordPress.org requirements.
     */
    function unstoppable_options_studio_admin_bar_css() {
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        // Register an empty handle as an anchor for inline styles.
        wp_register_style( 'unstoppable-studio-adminbar', false, array(), '1.0.0' );
        wp_enqueue_style( 'unstoppable-studio-adminbar' );

        $css = '
            #wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon:before {
                content: "\\f180";
                top: 2px;
            }
            #wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon {
                margin-right: 6px;
            }
            #wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item {
                color: #f0b849 !important;
            }
            #wpadminbar #wp-admin-bar-unstoppable-studio:hover > .ab-item {
                color: #fff !important;
                background: #32373c !important;
            }
        ';

        wp_add_inline_style( 'unstoppable-studio-adminbar', $css );
    }
}

// Initialize the Options Studio shell
unstoppable_options_studio_init();

/*
 * ============================================================================
 * END SHARED SHELL SYSTEM
 * ============================================================================
 */

/**
 * Main Plugin Class
 */
final class UOPWC_Order_Pause {

    /**
     * Single instance of the class.
     *
     * @var UOPWC_Order_Pause
     */
    private static $instance = null;

    /**
     * Main Instance.
     *
     * @return UOPWC_Order_Pause
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        require_once UOPWC_PLUGIN_DIR . 'includes/class-uop-admin.php';
        require_once UOPWC_PLUGIN_DIR . 'includes/class-uop-pause-handler.php';
        require_once UOPWC_PLUGIN_DIR . 'includes/class-uop-admin-bar.php';
        require_once UOPWC_PLUGIN_DIR . 'includes/class-uop-license.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ) );
        add_filter( 'unstoppable_studio_registered_plugins', array( $this, 'register_with_studio' ) );
        
        // Initialize components
        add_action( 'init', array( $this, 'init_components' ) );
        
        // Activation hook
        register_activation_hook( UOPWC_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( UOPWC_PLUGIN_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Check if WooCommerce is active.
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        }
    }

    /**
     * WooCommerce missing notice.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Unstoppable Order Pause', 'unstoppable-order-pause-for-woocommerce' ); ?></strong> 
                <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'unstoppable-order-pause-for-woocommerce' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Initialize plugin components.
     */
    public function init_components() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        
        new UOPWC_Admin();
        new UOPWC_Pause_Handler();
        new UOPWC_Admin_Bar();
    }

    /**
     * Register with Options Studio.
     *
     * @param array $plugins Registered plugins.
     * @return array
     */
    public function register_with_studio( $plugins ) {
        $plugins['order-pause'] = array(
            'name'        => __( 'Unstoppable Order Pause for WooCommerce', 'unstoppable-order-pause-for-woocommerce' ),
            'description' => __( 'Pause orders for the day or during busy hours.', 'unstoppable-order-pause-for-woocommerce' ),
            'menu_slug'   => 'uopwc-settings',
            'version'     => UOPWC_VERSION,
        );
        return $plugins;
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Ensure orders are NOT paused on fresh install
        if ( false === get_option( 'uopwc_manually_paused' ) ) {
            update_option( 'uopwc_manually_paused', 'no' );
        }
        
        // Set default options
        $defaults = array(
            'enabled'              => 'no',
            'pause_mode'           => 'manual', // manual, scheduled, daily
            'paused_message'       => __( 'We are currently not accepting orders. Please check back later.', 'unstoppable-order-pause-for-woocommerce' ),
            'show_countdown'       => 'yes',
            'hide_add_to_cart'     => 'yes',
            'hide_checkout'        => 'yes',
            'schedule_start'       => '',
            'schedule_end'         => '',
            'daily_close_time'     => '22:00',
            'daily_open_time'      => '08:00',
            'scheduled_days'       => array(),
            'pause_until'          => '',
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( 'uopwc_' . $key ) ) {
                update_option( 'uopwc_' . $key, $value );
            }
        }
        
        // Set install date for review notice
        if ( ! get_option( 'uopwc_install_date' ) ) {
            update_option( 'uopwc_install_date', time() );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook( 'uopwc_scheduled_pause_check' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Returns the main instance of UOPWC_Order_Pause.
 *
 * @return UOPWC_Order_Pause
 */
function UOPWC() {
    return UOPWC_Order_Pause::instance();
}

// Initialize the plugin
UOPWC();
