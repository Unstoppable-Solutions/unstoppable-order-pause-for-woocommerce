<?php
/**
 * Pause Handler Class
 *
 * Handles the logic for pausing and resuming orders.
 *
 * @package UOPWC_Order_Pause
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pause Handler Class.
 */
class UOPWC_Pause_Handler {

    /**
     * Constructor.
     */
    public function __construct() {
        // Check scheduled pause/resume
        add_action( 'init', array( $this, 'check_scheduled_pause' ) );
        
        // Frontend hooks
        add_action( 'wp', array( $this, 'maybe_redirect_checkout' ) );
        add_action( 'woocommerce_before_shop_loop', array( $this, 'display_shop_notice' ) );
        add_action( 'woocommerce_before_single_product', array( $this, 'display_shop_notice' ) );
        add_action( 'woocommerce_before_cart', array( $this, 'display_cart_notice' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'display_checkout_notice' ) );
        
        // Hide add to cart
        add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
        add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_purchasable' ), 10, 2 );
        
        // Disable checkout
        add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_for_checkout' ) );
        
        // Enqueue frontend styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
    }

    /**
     * Check if orders are currently paused.
     *
     * @return bool
     */
    public static function is_orders_paused() {
        $manually_paused = get_option( 'uopwc_manually_paused', 'no' );
        
        // Check manual pause first
        if ( $manually_paused === 'yes' ) {
            // Check if pause has expired
            $pause_until = get_option( 'uopwc_pause_until', '' );
            if ( ! empty( $pause_until ) ) {
                $pause_time = strtotime( $pause_until );
                if ( $pause_time && $pause_time <= time() ) {
                    // Pause has expired, resume orders
                    self::resume_orders();
                    return false;
                }
            }
            return true;
        }
        
        // Check scheduled pause
        return self::is_scheduled_pause_active();
    }

    /**
     * Check if scheduled pause is currently active.
     *
     * @return bool
     */
    public static function is_scheduled_pause_active() {
        $pause_mode = get_option( 'uopwc_pause_mode', 'manual' );
        
        if ( $pause_mode === 'manual' ) {
            return false;
        }
        
        $current_time = current_time( 'H:i' );
        $current_day = strtolower( current_time( 'l' ) );
        
        if ( $pause_mode === 'daily' ) {
            $open_time = get_option( 'uopwc_daily_open_time', '08:00' );
            $close_time = get_option( 'uopwc_daily_close_time', '22:00' );
            
            return ! self::is_within_hours( $current_time, $open_time, $close_time );
        }
        
        if ( $pause_mode === 'weekly' ) {
            $scheduled_days = get_option( 'uopwc_scheduled_days', array() );
            
            if ( empty( $scheduled_days ) || ! isset( $scheduled_days[ $current_day ] ) ) {
                return false;
            }
            
            $day_settings = $scheduled_days[ $current_day ];
            
            // Check if closed all day
            if ( isset( $day_settings['enabled'] ) && $day_settings['enabled'] === 'no' ) {
                return true;
            }
            
            $open_time = isset( $day_settings['open'] ) ? $day_settings['open'] : '08:00';
            $close_time = isset( $day_settings['close'] ) ? $day_settings['close'] : '22:00';
            
            return ! self::is_within_hours( $current_time, $open_time, $close_time );
        }
        
        return false;
    }

    /**
     * Check if current time is within operating hours.
     *
     * @param string $current Current time (H:i format).
     * @param string $open    Opening time (H:i format).
     * @param string $close   Closing time (H:i format).
     * @return bool
     */
    private static function is_within_hours( $current, $open, $close ) {
        $current_mins = self::time_to_minutes( $current );
        $open_mins = self::time_to_minutes( $open );
        $close_mins = self::time_to_minutes( $close );
        
        // Handle overnight hours (e.g., 22:00 to 06:00)
        if ( $close_mins < $open_mins ) {
            return $current_mins >= $open_mins || $current_mins < $close_mins;
        }
        
        return $current_mins >= $open_mins && $current_mins < $close_mins;
    }

    /**
     * Convert time string to minutes.
     *
     * @param string $time Time string (H:i format).
     * @return int
     */
    private static function time_to_minutes( $time ) {
        $parts = explode( ':', $time );
        return ( intval( $parts[0] ) * 60 ) + intval( $parts[1] ?? 0 );
    }

    /**
     * Pause orders.
     *
     * @param int|null $until    Unix timestamp for when to resume (optional).
     * @param string   $reason   Reason for pausing (optional).
     */
    public static function pause_orders( $until = null, $reason = '' ) {
        update_option( 'uopwc_manually_paused', 'yes' );
        
        if ( $until ) {
            update_option( 'uopwc_pause_until', date( 'Y-m-d H:i:s', $until ) );
        } else {
            delete_option( 'uopwc_pause_until' );
        }
        
        update_option( 'uopwc_pause_reason', sanitize_text_field( $reason ) );
        update_option( 'uopwc_pause_started', current_time( 'mysql' ) );
        
        // Clear cart session cache
        if ( function_exists( 'WC' ) && WC()->session ) {
            WC()->session->set( 'cart_totals', null );
        }
        
        do_action( 'uopwc_orders_paused', $until, $reason );
    }

    /**
     * Resume orders.
     */
    public static function resume_orders() {
        update_option( 'uopwc_manually_paused', 'no' );
        delete_option( 'uopwc_pause_until' );
        delete_option( 'uopwc_pause_reason' );
        delete_option( 'uopwc_pause_started' );
        
        do_action( 'uopwc_orders_resumed' );
    }

    /**
     * Get the pause message to display.
     *
     * @return string
     */
    public static function get_pause_message() {
        $message = get_option( 'uopwc_paused_message', __( 'We are currently not accepting orders. Please check back later.', 'unstoppable-order-pause-for-woocommerce' ) );
        $pause_until = get_option( 'uopwc_pause_until', '' );
        
        // Replace {time} placeholder
        if ( ! empty( $pause_until ) && strpos( $message, '{time}' ) !== false ) {
            $resume_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pause_until ) );
            $message = str_replace( '{time}', $resume_time, $message );
        }
        
        return $message;
    }

    /**
     * Get countdown data.
     *
     * @return array|false
     */
    public static function get_countdown_data() {
        if ( get_option( 'uopwc_show_countdown', 'yes' ) !== 'yes' ) {
            return false;
        }
        
        $pause_until = get_option( 'uopwc_pause_until', '' );
        
        if ( empty( $pause_until ) ) {
            // Check if scheduled mode has a resume time
            $pause_mode = get_option( 'uopwc_pause_mode', 'manual' );
            
            if ( $pause_mode === 'daily' ) {
                $open_time = get_option( 'uopwc_daily_open_time', '08:00' );
                $current_time = current_time( 'H:i' );
                
                // Calculate next opening time
                if ( $current_time >= get_option( 'uopwc_daily_close_time', '22:00' ) ) {
                    // After closing, next opening is tomorrow
                    $pause_until = date( 'Y-m-d', strtotime( '+1 day' ) ) . ' ' . $open_time . ':00';
                } else {
                    // Before opening today
                    $pause_until = current_time( 'Y-m-d' ) . ' ' . $open_time . ':00';
                }
            } else {
                return false;
            }
        }
        
        $timestamp = strtotime( $pause_until );
        
        if ( ! $timestamp || $timestamp <= time() ) {
            return false;
        }
        
        return array(
            'timestamp'   => $timestamp,
            'formatted'   => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
            'seconds_left' => $timestamp - time(),
        );
    }

    /**
     * Check scheduled pause and resume.
     */
    public function check_scheduled_pause() {
        // Check if manual pause has expired
        $manually_paused = get_option( 'uopwc_manually_paused', 'no' );
        $pause_until = get_option( 'uopwc_pause_until', '' );
        
        if ( $manually_paused === 'yes' && ! empty( $pause_until ) ) {
            $pause_time = strtotime( $pause_until );
            if ( $pause_time && $pause_time <= time() ) {
                self::resume_orders();
            }
        }
    }

    /**
     * Redirect checkout if orders are paused.
     */
    public function maybe_redirect_checkout() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        if ( get_option( 'uopwc_hide_checkout', 'yes' ) !== 'yes' ) {
            return;
        }
        
        if ( is_checkout() && ! is_wc_endpoint_url() ) {
            wp_safe_redirect( wc_get_cart_url() );
            exit;
        }
    }

    /**
     * Display notice on shop pages.
     */
    public function display_shop_notice() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        if ( get_option( 'uopwc_show_notice_shop', 'yes' ) !== 'yes' ) {
            return;
        }
        
        $this->display_notice();
    }

    /**
     * Display notice on cart page.
     */
    public function display_cart_notice() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        if ( get_option( 'uopwc_show_notice_cart', 'yes' ) !== 'yes' ) {
            return;
        }
        
        $this->display_notice();
    }

    /**
     * Display notice on checkout page.
     */
    public function display_checkout_notice() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        $this->display_notice();
    }

    /**
     * Display the pause notice.
     */
    private function display_notice() {
        static $displayed = false;
        
        // Prevent duplicate notices on the same page
        if ( $displayed ) {
            return;
        }
        $displayed = true;
        
        $message = self::get_pause_message();
        $style = get_option( 'uopwc_notice_style', 'warning' );
        $countdown = self::get_countdown_data();
        
        $notice_class = 'uopwc-pause-notice uopwc-notice-' . esc_attr( $style );
        
        ?>
        <div class="<?php echo esc_attr( $notice_class ); ?>">
            <div class="uopwc-notice-icon">
                <span class="dashicons dashicons-controls-pause"></span>
            </div>
            <div class="uopwc-notice-content">
                <p class="uopwc-notice-message"><?php echo wp_kses_post( $message ); ?></p>
                <?php if ( $countdown ) : ?>
                    <div class="uopwc-countdown" data-timestamp="<?php echo esc_attr( $countdown['timestamp'] ); ?>">
                        <span class="uopwc-countdown-label"><?php esc_html_e( 'Orders resume in:', 'unstoppable-order-pause-for-woocommerce' ); ?></span>
                        <span class="uopwc-countdown-timer"></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Filter product purchasability.
     *
     * @param bool       $purchasable Whether the product is purchasable.
     * @param WC_Product $product     Product object.
     * @return bool
     */
    public function filter_purchasable( $purchasable, $product ) {
        if ( ! self::is_orders_paused() ) {
            return $purchasable;
        }
        
        if ( get_option( 'uopwc_hide_add_to_cart', 'yes' ) !== 'yes' ) {
            return $purchasable;
        }
        
        return false;
    }

    /**
     * Check cart items before checkout.
     */
    public function check_cart_for_checkout() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        if ( get_option( 'uopwc_hide_checkout', 'yes' ) !== 'yes' ) {
            return;
        }
        
        wc_add_notice( self::get_pause_message(), 'error' );
    }

    /**
     * Enqueue frontend styles.
     */
    public function enqueue_frontend_styles() {
        if ( ! self::is_orders_paused() ) {
            return;
        }
        
        wp_enqueue_style(
            'uopwc-frontend',
            UOPWC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            UOPWC_VERSION
        );
        
        // Always add the JavaScript to show message on click
        wp_enqueue_script(
            'uopwc-frontend',
            UOPWC_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            UOPWC_VERSION,
            true
        );
        
        // Pass data to JavaScript
        $pause_message = get_option( 'uopwc_paused_message', __( 'We are currently not accepting orders. Please check back later.', 'unstoppable-order-pause-for-woocommerce' ) );
        $pause_until = get_option( 'uopwc_pause_until', '' );
        
        // Replace {time} placeholder
        if ( ! empty( $pause_until ) && strpos( $pause_message, '{time}' ) !== false ) {
            $resume_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pause_until ) );
            $pause_message = str_replace( '{time}', $resume_time, $pause_message );
        }
        
        wp_localize_script( 'uopwc-frontend', 'uopwc_frontend', array(
            'is_paused'      => true,
            'message'        => $pause_message,
            'hide_buttons'   => get_option( 'uopwc_hide_add_to_cart', 'yes' ),
            'button_text'    => __( 'Orders Paused', 'unstoppable-order-pause-for-woocommerce' ),
            'popup_title'    => __( 'Orders Currently Paused', 'unstoppable-order-pause-for-woocommerce' ),
            'close_text'     => __( 'OK', 'unstoppable-order-pause-for-woocommerce' ),
        ) );
        
        // Add inline CSS for popup and button styling
        $inline_css = '
            /* Paused button styling */
            .uopwc-button-paused {
                opacity: 0.7 !important;
                cursor: not-allowed !important;
                position: relative !important;
            }
            
            /* Popup overlay */
            .uopwc-popup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                z-index: 999999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            /* Popup box */
            .uopwc-popup {
                background: #fff;
                border-radius: 12px;
                max-width: 400px;
                width: 100%;
                padding: 30px;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                animation: uopwc-popup-in 0.3s ease;
            }
            
            @keyframes uopwc-popup-in {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            
            .uopwc-popup-icon {
                font-size: 50px;
                margin-bottom: 15px;
            }
            
            .uopwc-popup-title {
                font-size: 20px;
                font-weight: 600;
                margin: 0 0 15px 0;
                color: #333;
            }
            
            .uopwc-popup-message {
                font-size: 15px;
                color: #666;
                margin: 0 0 20px 0;
                line-height: 1.5;
            }
            
            .uopwc-popup-close {
                background: #f0b849;
                color: #fff;
                border: none;
                padding: 12px 30px;
                font-size: 15px;
                font-weight: 600;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.2s;
            }
            
            .uopwc-popup-close:hover {
                background: #e0a839;
            }
        ';
        
        // If hide buttons is enabled, add CSS to hide them
        if ( get_option( 'uopwc_hide_add_to_cart', 'yes' ) === 'yes' ) {
            $inline_css .= '
                /* Hide all add to cart buttons when orders are paused */
                .add_to_cart_button,
                .single_add_to_cart_button,
                .woocommerce-variation-add-to-cart,
                .cart button[type="submit"],
                form.cart .button,
                .product .button.add_to_cart_button,
                .wc-block-components-product-button,
                .wp-block-button.add_to_cart_button,
                .add-to-cart-button,
                .addtocart,
                button[name="add-to-cart"],
                input[name="add-to-cart"],
                .quantity {
                    display: none !important;
                }
            ';
        }
        
        wp_add_inline_style( 'uopwc-frontend', $inline_css );
        
        // Enqueue countdown script if needed
        $countdown = self::get_countdown_data();
        if ( $countdown ) {
            wp_enqueue_script(
                'uopwc-countdown',
                UOPWC_PLUGIN_URL . 'assets/js/countdown.js',
                array( 'jquery' ),
                UOPWC_VERSION,
                true
            );
            
            wp_localize_script( 'uopwc-countdown', 'uopwc_countdown', array(
                'timestamp' => $countdown['timestamp'],
                'strings'   => array(
                    'days'    => __( 'days', 'unstoppable-order-pause-for-woocommerce' ),
                    'hours'   => __( 'hours', 'unstoppable-order-pause-for-woocommerce' ),
                    'minutes' => __( 'min', 'unstoppable-order-pause-for-woocommerce' ),
                    'seconds' => __( 'sec', 'unstoppable-order-pause-for-woocommerce' ),
                ),
            ) );
        }
    }
}
