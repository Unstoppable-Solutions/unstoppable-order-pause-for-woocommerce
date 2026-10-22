<?php
/**
 * Admin Bar Class
 *
 * Adds quick pause toggle to WordPress admin bar under Options Studio.
 *
 * @package UOPWC_Order_Pause
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Bar Class.
 */
class UOPWC_Admin_Bar {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 101 );
        add_action( 'wp_enqueue_scripts', array( $this, 'admin_bar_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_bar_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'admin_bar_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_bar_scripts' ) );
        
        // AJAX handler for admin bar toggle
        add_action( 'wp_ajax_uopwc_admin_bar_toggle', array( $this, 'ajax_admin_bar_toggle' ) );
    }

    /**
     * Add menu items to admin bar.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
     */
    public function add_admin_bar_menu( $wp_admin_bar ) {
        // Only for users who can manage WooCommerce
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Respect the admin-bar visibility setting (enabled by default).
        if ( 'yes' !== get_option( 'uopwc_show_admin_bar', 'yes' ) ) {
            return;
        }

        $is_paused = UOPWC_Pause_Handler::is_orders_paused();
        $pause_until = get_option( 'uopwc_pause_until', '' );
        
        // Status indicator
        $status_text = $is_paused 
            ? __( 'Orders: PAUSED', 'unstoppable-order-pause-for-woocommerce' ) 
            : __( 'Orders: Active', 'unstoppable-order-pause-for-woocommerce' );
        
        $status_class = $is_paused ? 'uopwc-status-paused' : 'uopwc-status-active';
        
        // Add Order Pause item under Options Studio
        $wp_admin_bar->add_node( array(
            'id'     => 'uopwc-order-pause',
            'parent' => 'unstoppable-studio',
            'title'  => '<span class="uopwc-admin-bar-status ' . esc_attr( $status_class ) . '">' . 
                        '<span class="dashicons ' . ( $is_paused ? 'dashicons-controls-pause' : 'dashicons-yes-alt' ) . '"></span>' .
                        esc_html( $status_text ) . '</span>',
            'href'   => admin_url( 'admin.php?page=uopwc-settings' ),
            'meta'   => array(
                'title' => __( 'Order Pause Status', 'unstoppable-order-pause-for-woocommerce' ),
            ),
        ) );
        
        // Add quick toggle submenu
        if ( $is_paused ) {
            // Show resume option
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-resume-orders',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-controls-play"></span>' . __( 'Resume Orders Now', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-resume-action',
                    'onclick' => 'return false;',
                ),
            ) );
            
            // Show pause info if timed
            if ( ! empty( $pause_until ) ) {
                $pause_time = strtotime( $pause_until );
                if ( $pause_time && $pause_time > time() ) {
                    $wp_admin_bar->add_node( array(
                        'id'     => 'uopwc-pause-info',
                        'parent' => 'uopwc-order-pause',
                        'title'  => '<span class="uopwc-pause-until-info">' .
                                    sprintf(
                                        /* translators: %s: formatted resume date and time (e.g. "Mar 15, 2:30 pm"). */
                                        __( 'Resumes: %s', 'unstoppable-order-pause-for-woocommerce' ),
                                        date_i18n( 'M j, g:i a', $pause_time )
                                    ) . '</span>',
                        'href'   => false,
                        'meta'   => array(
                            'class' => 'uopwc-info-item',
                        ),
                    ) );
                }
            }
        } else {
            // Show pause options
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-pause-30',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-clock"></span>' . __( 'Pause 30 Minutes', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-pause-action',
                    'data-duration' => '30',
                    'onclick' => 'return false;',
                ),
            ) );
            
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-pause-60',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-clock"></span>' . __( 'Pause 1 Hour', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-pause-action',
                    'data-duration' => '60',
                    'onclick' => 'return false;',
                ),
            ) );
            
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-pause-120',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-clock"></span>' . __( 'Pause 2 Hours', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-pause-action',
                    'data-duration' => '120',
                    'onclick' => 'return false;',
                ),
            ) );
            
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-pause-day',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-calendar"></span>' . __( 'Pause Rest of Day', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-pause-action',
                    'data-duration' => 'day',
                    'onclick' => 'return false;',
                ),
            ) );
            
            $wp_admin_bar->add_node( array(
                'id'     => 'uopwc-pause-indefinite',
                'parent' => 'uopwc-order-pause',
                'title'  => '<span class="dashicons dashicons-controls-pause"></span>' . __( 'Pause Indefinitely', 'unstoppable-order-pause-for-woocommerce' ),
                'href'   => '#',
                'meta'   => array(
                    'class'   => 'uopwc-admin-bar-action uopwc-pause-action',
                    'data-duration' => 'indefinite',
                    'onclick' => 'return false;',
                ),
            ) );
        }
        
        // Add separator and settings link
        $wp_admin_bar->add_node( array(
            'id'     => 'uopwc-settings-link',
            'parent' => 'uopwc-order-pause',
            'title'  => '<span class="dashicons dashicons-admin-generic"></span>' . __( 'Settings', 'unstoppable-order-pause-for-woocommerce' ),
            'href'   => admin_url( 'admin.php?page=uopwc-settings&tab=settings' ),
        ) );
    }

    /**
     * Admin bar styles.
     * Uses wp_add_inline_style() instead of inline <style> blocks per WordPress.org requirements.
     */
    public function admin_bar_styles() {
        if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_woocommerce' ) || 'yes' !== get_option( 'uopwc_show_admin_bar', 'yes' ) ) {
            return;
        }
        
        // Ensure dashicons are loaded on frontend
        wp_enqueue_style( 'dashicons' );
        
        // Register an empty handle as an anchor for inline styles.
        wp_register_style( 'uopwc-adminbar', false, array( 'dashicons' ), UOPWC_VERSION );
        wp_enqueue_style( 'uopwc-adminbar' );

        $css = '
            /* Ensure dashicons font is used */
            #wpadminbar .uopwc-admin-bar-status .dashicons,
            #wpadminbar .ab-submenu .dashicons {
                font-family: dashicons !important;
                font-size: 16px;
                width: 16px;
                height: 16px;
                line-height: 16px;
                vertical-align: middle;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }
            
            /* Order Pause Admin Bar Styles */
            #wpadminbar .uopwc-admin-bar-status {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            #wpadminbar .uopwc-status-paused {
                color: #dc3545 !important;
            }
            #wpadminbar .uopwc-status-active {
                color: #28a745 !important;
            }
            #wpadminbar #wp-admin-bar-uopwc-order-pause:hover .uopwc-status-paused,
            #wpadminbar #wp-admin-bar-uopwc-order-pause:hover .uopwc-status-active {
                color: #fff !important;
            }
            
            /* Submenu items */
            #wpadminbar .uopwc-admin-bar-action .ab-item .dashicons,
            #wpadminbar #wp-admin-bar-uopwc-settings-link .ab-item .dashicons {
                margin-right: 5px;
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            #wpadminbar .uopwc-resume-action .ab-item {
                color: #28a745 !important;
            }
            #wpadminbar .uopwc-pause-action .ab-item {
                color: #dc3545 !important;
            }
            #wpadminbar .uopwc-pause-action:hover .ab-item,
            #wpadminbar .uopwc-resume-action:hover .ab-item {
                color: #fff !important;
            }
            
            /* Info item (non-clickable) */
            #wpadminbar .uopwc-info-item .ab-item {
                color: #999 !important;
                font-size: 11px;
                cursor: default !important;
            }
            #wpadminbar .uopwc-info-item:hover .ab-item {
                background: transparent !important;
                color: #999 !important;
            }
            
            /* Loading state */
            #wpadminbar .uopwc-loading .ab-item {
                opacity: 0.5;
                pointer-events: none;
            }
        ';

        wp_add_inline_style( 'uopwc-adminbar', $css );
    }

    /**
     * Admin bar JavaScript.
     * Uses wp_enqueue_script() instead of inline <script> blocks per WordPress.org requirements.
     */
    public function admin_bar_scripts() {
        if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_woocommerce' ) || 'yes' !== get_option( 'uopwc_show_admin_bar', 'yes' ) ) {
            return;
        }
        
        wp_enqueue_script(
            'uopwc-adminbar',
            UOPWC_PLUGIN_URL . 'assets/js/admin-bar.js',
            array( 'jquery' ),
            UOPWC_VERSION,
            true
        );
        
        wp_localize_script( 'uopwc-adminbar', 'uopwc_adminbar', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'uopwc_admin_bar_nonce' ),
            'strings'  => array(
                'error'            => __( 'An error occurred.', 'unstoppable-order-pause-for-woocommerce' ),
                'connection_error' => __( 'Connection error. Please try again.', 'unstoppable-order-pause-for-woocommerce' ),
            ),
        ) );
    }

    /**
     * AJAX handler for admin bar toggle.
     */
    public function ajax_admin_bar_toggle() {
        // Verify nonce
        if ( ! check_ajax_referer( 'uopwc_admin_bar_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        $pause_action = isset( $_POST['pause_action'] ) ? sanitize_key( wp_unslash( $_POST['pause_action'] ) ) : '';
        $duration = isset( $_POST['duration'] ) ? sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : '';
        
        if ( $pause_action === 'resume' ) {
            UOPWC_Pause_Handler::resume_orders();
            wp_send_json_success( array(
                'paused'  => false,
                'message' => __( 'Orders resumed!', 'unstoppable-order-pause-for-woocommerce' ),
            ) );
        } elseif ( $pause_action === 'pause' ) {
            $pause_until = null;
            
            if ( $duration === 'day' ) {
                $pause_until = strtotime( 'tomorrow midnight' );
            } elseif ( $duration === 'indefinite' || empty( $duration ) ) {
                $pause_until = null;
            } elseif ( is_numeric( $duration ) ) {
                $minutes = absint( $duration );
                if ( $minutes > 0 && $minutes <= 1440 ) {
                    $pause_until = time() + ( $minutes * 60 );
                }
            }
            
            UOPWC_Pause_Handler::pause_orders( $pause_until );
            
            $message = __( 'Orders paused!', 'unstoppable-order-pause-for-woocommerce' );
            if ( $pause_until ) {
                $message = sprintf(
                    /* translators: %s: formatted time until which orders are paused (e.g. "5:30 pm"). */
                    __( 'Orders paused until %s', 'unstoppable-order-pause-for-woocommerce' ),
                    date_i18n( get_option( 'time_format' ), $pause_until )
                );
            }
            
            wp_send_json_success( array(
                'paused'  => true,
                'message' => $message,
            ) );
        }
        
        wp_send_json_error( array( 'message' => __( 'Invalid action.', 'unstoppable-order-pause-for-woocommerce' ) ) );
    }
}
