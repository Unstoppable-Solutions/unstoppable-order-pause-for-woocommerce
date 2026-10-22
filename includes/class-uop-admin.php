<?php
/**
 * Admin Class
 *
 * Handles admin settings page and functionality.
 *
 * @package UOPWC_Order_Pause
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Class.
 */
class UOPWC_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_uopwc_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_uopwc_toggle_pause', array( $this, 'ajax_toggle_pause' ) );
        add_action( 'wp_ajax_uopwc_quick_pause', array( $this, 'ajax_quick_pause' ) );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'unstoppable-studio',
            __( 'Unstoppable Order Pause for WooCommerce', 'unstoppable-order-pause-for-woocommerce' ),
            __( 'Unstoppable Order Pause for WooCommerce', 'unstoppable-order-pause-for-woocommerce' ),
            'manage_woocommerce',
            'uopwc-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'uopwc-settings' ) === false && strpos( $hook, 'unstoppable-studio' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'uopwc-admin',
            UOPWC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UOPWC_VERSION
        );

        wp_enqueue_script(
            'uopwc-admin',
            UOPWC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            UOPWC_VERSION,
            true
        );

        wp_localize_script( 'uopwc-admin', 'uopwc_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'uopwc_admin_nonce' ),
            'strings'  => array(
                'saving'     => __( 'Saving...', 'unstoppable-order-pause-for-woocommerce' ),
                'saved'      => __( 'Settings saved!', 'unstoppable-order-pause-for-woocommerce' ),
                'error'      => __( 'Error saving settings.', 'unstoppable-order-pause-for-woocommerce' ),
                'pausing'    => __( 'Pausing...', 'unstoppable-order-pause-for-woocommerce' ),
                'resuming'   => __( 'Resuming...', 'unstoppable-order-pause-for-woocommerce' ),
                'confirm_pause' => __( 'Are you sure you want to pause orders?', 'unstoppable-order-pause-for-woocommerce' ),
            ),
        ) );
    }

    /**
     * Get current tab.
     *
     * @return string
     */
    private function get_current_tab() {
        return isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
    }

    /**
     * Get available tabs.
     *
     * @return array
     */
    private function get_tabs() {
        return array(
            'dashboard' => array(
                'label' => __( 'Dashboard', 'unstoppable-order-pause-for-woocommerce' ),
                'icon'  => 'dashicons-dashboard',
            ),
            'settings' => array(
                'label' => __( 'Settings', 'unstoppable-order-pause-for-woocommerce' ),
                'icon'  => 'dashicons-admin-generic',
            ),
            'schedule' => array(
                'label' => __( 'Schedule', 'unstoppable-order-pause-for-woocommerce' ),
                'icon'  => 'dashicons-calendar-alt',
            ),
            'license' => array(
                'label' => __( 'License', 'unstoppable-order-pause-for-woocommerce' ),
                'icon'  => 'dashicons-admin-network',
            ),
        );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        $current_tab = $this->get_current_tab();
        $tabs = $this->get_tabs();
        ?>
        <div class="wrap uopwc-wrap">
            <h1>
                <span class="dashicons dashicons-controls-pause" style="color: #f0b849;"></span>
                <?php esc_html_e( 'Unstoppable Order Pause for WooCommerce', 'unstoppable-order-pause-for-woocommerce' ); ?>
            </h1>
            
            <nav class="uopwc-tabs-nav">
                <?php foreach ( $tabs as $tab_id => $tab ) : 
                    $url = admin_url( 'admin.php?page=uopwc-settings&tab=' . $tab_id );
                    $is_active = $current_tab === $tab_id;
                ?>
                    <a href="<?php echo esc_url( $url ); ?>" 
                       class="uopwc-tab-link <?php echo $is_active ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <div class="uopwc-tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'schedule':
                        $this->render_schedule_tab();
                        break;
                    case 'license':
                        $this->render_license_tab();
                        break;
                    case 'dashboard':
                    default:
                        $this->render_dashboard_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render dashboard tab.
     */
    private function render_dashboard_tab() {
        $is_paused = UOPWC_Pause_Handler::is_orders_paused();
        $pause_until = get_option( 'uopwc_pause_until', '' );
        $pause_reason = get_option( 'uopwc_pause_reason', '' );
        ?>
        <div class="uopwc-dashboard">
            <!-- Status Card -->
            <div class="uopwc-card uopwc-status-card <?php echo $is_paused ? 'paused' : 'active'; ?>">
                <div class="uopwc-status-indicator">
                    <span class="dashicons <?php echo $is_paused ? 'dashicons-controls-pause' : 'dashicons-yes-alt'; ?>"></span>
                </div>
                <div class="uopwc-status-content">
                    <h2>
                        <?php echo $is_paused 
                            ? esc_html__( 'Orders are PAUSED', 'unstoppable-order-pause-for-woocommerce' ) 
                            : esc_html__( 'Orders are ACTIVE', 'unstoppable-order-pause-for-woocommerce' ); 
                        ?>
                    </h2>
                    <?php if ( $is_paused && ! empty( $pause_until ) ) : ?>
                        <p class="uopwc-pause-info">
                            <?php 
                            $pause_time = strtotime( $pause_until );
                            if ( $pause_time && $pause_time > time() ) {
                                printf(
                                    /* translators: %s: formatted resume date and time (e.g. "March 15, 2026 2:30 pm"). */
                                    esc_html__( 'Orders will resume at %s', 'unstoppable-order-pause-for-woocommerce' ),
                                    esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pause_time ) )
                                );
                            }
                            ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $is_paused && ! empty( $pause_reason ) ) : ?>
                        <p class="uopwc-pause-reason">
                            <strong><?php esc_html_e( 'Reason:', 'unstoppable-order-pause-for-woocommerce' ); ?></strong>
                            <?php echo esc_html( $pause_reason ); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="uopwc-status-actions">
                    <button type="button" class="button button-hero <?php echo $is_paused ? 'button-primary' : 'uopwc-pause-btn'; ?>" id="uopwc-toggle-pause">
                        <span class="dashicons <?php echo $is_paused ? 'dashicons-controls-play' : 'dashicons-controls-pause'; ?>"></span>
                        <?php echo $is_paused 
                            ? esc_html__( 'Resume Orders', 'unstoppable-order-pause-for-woocommerce' ) 
                            : esc_html__( 'Pause Orders Now', 'unstoppable-order-pause-for-woocommerce' ); 
                        ?>
                    </button>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="uopwc-quick-actions">
                <h3><?php esc_html_e( 'Quick Pause Options', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <div class="uopwc-quick-buttons">
                    <button type="button" class="button uopwc-quick-pause" data-duration="30">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e( '30 Minutes', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </button>
                    <button type="button" class="button uopwc-quick-pause" data-duration="60">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e( '1 Hour', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </button>
                    <button type="button" class="button uopwc-quick-pause" data-duration="120">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e( '2 Hours', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </button>
                    <button type="button" class="button uopwc-quick-pause" data-duration="day">
                        <span class="dashicons dashicons-calendar"></span>
                        <?php esc_html_e( 'Rest of Day', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </button>
                </div>
            </div>

            <!-- Custom Pause -->
            <div class="uopwc-card uopwc-custom-pause">
                <h3><?php esc_html_e( 'Custom Pause Duration', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <form id="uopwc-custom-pause-form">
                    <div class="uopwc-form-row">
                        <label for="uopwc-pause-until-date"><?php esc_html_e( 'Pause Until:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                        <input type="datetime-local" id="uopwc-pause-until-date" name="pause_until"
                               value="<?php echo esc_attr( $pause_until ? date( 'Y-m-d\TH:i', strtotime( $pause_until ) ) : '' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Form value for datetime-local input; stored and consumed in the same local timezone. ?>">
                    </div>
                    <div class="uopwc-form-row">
                        <label for="uopwc-pause-reason"><?php esc_html_e( 'Reason (optional):', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                        <input type="text" id="uopwc-pause-reason" name="pause_reason" 
                               value="<?php echo esc_attr( $pause_reason ); ?>"
                               placeholder="<?php esc_attr_e( 'e.g., Lunch break, Inventory count', 'unstoppable-order-pause-for-woocommerce' ); ?>">
                    </div>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Set Custom Pause', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings tab.
     */
    private function render_settings_tab() {
        $settings = array(
            'paused_message'   => get_option( 'uopwc_paused_message', __( 'We are currently not accepting orders. Please check back later.', 'unstoppable-order-pause-for-woocommerce' ) ),
            'show_countdown'   => get_option( 'uopwc_show_countdown', 'yes' ),
            'hide_add_to_cart' => get_option( 'uopwc_hide_add_to_cart', 'yes' ),
            'hide_checkout'    => get_option( 'uopwc_hide_checkout', 'yes' ),
            'show_notice_shop' => get_option( 'uopwc_show_notice_shop', 'yes' ),
            'show_notice_cart' => get_option( 'uopwc_show_notice_cart', 'yes' ),
            'notice_style'     => get_option( 'uopwc_notice_style', 'warning' ),
            'show_admin_bar'   => get_option( 'uopwc_show_admin_bar', 'yes' ),
        );
        ?>
        <form id="uopwc-settings-form" class="uopwc-settings-form">
            <?php wp_nonce_field( 'uopwc_save_settings', 'uopwc_nonce' ); ?>
            
            <div class="uopwc-card">
                <h3><?php esc_html_e( 'Customer Message', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'This message will be shown to customers when orders are paused.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                
                <div class="uopwc-form-row">
                    <label for="uopwc-paused-message"><?php esc_html_e( 'Paused Message:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                    <textarea id="uopwc-paused-message" name="paused_message" rows="3" class="large-text"><?php echo esc_textarea( $settings['paused_message'] ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Use {time} to show when orders will resume (if scheduled).', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                </div>
                
                <div class="uopwc-form-row">
                    <label for="uopwc-notice-style"><?php esc_html_e( 'Notice Style:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                    <select id="uopwc-notice-style" name="notice_style">
                        <option value="warning" <?php selected( $settings['notice_style'], 'warning' ); ?>><?php esc_html_e( 'Warning (Yellow)', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                        <option value="error" <?php selected( $settings['notice_style'], 'error' ); ?>><?php esc_html_e( 'Error (Red)', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                        <option value="info" <?php selected( $settings['notice_style'], 'info' ); ?>><?php esc_html_e( 'Info (Blue)', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                        <option value="success" <?php selected( $settings['notice_style'], 'success' ); ?>><?php esc_html_e( 'Success (Green)', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="uopwc-card">
                <h3><?php esc_html_e( 'Display Options', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                
                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="show_countdown" value="yes" <?php checked( $settings['show_countdown'], 'yes' ); ?>>
                        <?php esc_html_e( 'Show countdown timer when resume time is set', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
                
                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="hide_add_to_cart" value="yes" <?php checked( $settings['hide_add_to_cart'], 'yes' ); ?>>
                        <?php esc_html_e( 'Hide "Add to Cart" buttons when paused', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
                
                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="hide_checkout" value="yes" <?php checked( $settings['hide_checkout'], 'yes' ); ?>>
                        <?php esc_html_e( 'Disable checkout page when paused', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
            </div>

            <div class="uopwc-card">
                <h3><?php esc_html_e( 'Notice Locations', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                
                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="show_notice_shop" value="yes" <?php checked( $settings['show_notice_shop'], 'yes' ); ?>>
                        <?php esc_html_e( 'Show notice on shop/product pages', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
                
                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="show_notice_cart" value="yes" <?php checked( $settings['show_notice_cart'], 'yes' ); ?>>
                        <?php esc_html_e( 'Show notice on cart page', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
            </div>

            <div class="uopwc-card">
                <h3><?php esc_html_e( 'Admin Bar', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>

                <div class="uopwc-form-row uopwc-checkbox-row">
                    <label>
                        <input type="checkbox" name="show_admin_bar" value="yes" <?php checked( $settings['show_admin_bar'], 'yes' ); ?>>
                        <?php esc_html_e( 'Show the Order Pause status and quick toggle in the WordPress admin bar', 'unstoppable-order-pause-for-woocommerce' ); ?>
                    </label>
                </div>
            </div>

            <div class="uopwc-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Save Settings', 'unstoppable-order-pause-for-woocommerce' ); ?>
                </button>
                <span class="uopwc-save-status"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render schedule tab.
     */
    private function render_schedule_tab() {
        $settings = array(
            'pause_mode'       => get_option( 'uopwc_pause_mode', 'manual' ),
            'daily_close_time' => get_option( 'uopwc_daily_close_time', '22:00' ),
            'daily_open_time'  => get_option( 'uopwc_daily_open_time', '08:00' ),
            'scheduled_days'   => get_option( 'uopwc_scheduled_days', array() ),
        );
        
        $days_of_week = array(
            'monday'    => __( 'Monday', 'unstoppable-order-pause-for-woocommerce' ),
            'tuesday'   => __( 'Tuesday', 'unstoppable-order-pause-for-woocommerce' ),
            'wednesday' => __( 'Wednesday', 'unstoppable-order-pause-for-woocommerce' ),
            'thursday'  => __( 'Thursday', 'unstoppable-order-pause-for-woocommerce' ),
            'friday'    => __( 'Friday', 'unstoppable-order-pause-for-woocommerce' ),
            'saturday'  => __( 'Saturday', 'unstoppable-order-pause-for-woocommerce' ),
            'sunday'    => __( 'Sunday', 'unstoppable-order-pause-for-woocommerce' ),
        );
        ?>
        <form id="uopwc-schedule-form" class="uopwc-settings-form">
            <?php wp_nonce_field( 'uopwc_save_settings', 'uopwc_nonce' ); ?>
            
            <div class="uopwc-card">
                <h3><?php esc_html_e( 'Pause Mode', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Choose how orders should be paused.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                
                <div class="uopwc-pause-modes">
                    <label class="uopwc-mode-option <?php echo $settings['pause_mode'] === 'manual' ? 'selected' : ''; ?>">
                        <input type="radio" name="pause_mode" value="manual" <?php checked( $settings['pause_mode'], 'manual' ); ?>>
                        <span class="dashicons dashicons-admin-users"></span>
                        <strong><?php esc_html_e( 'Manual', 'unstoppable-order-pause-for-woocommerce' ); ?></strong>
                        <span class="description"><?php esc_html_e( 'Manually toggle pause from dashboard or admin bar', 'unstoppable-order-pause-for-woocommerce' ); ?></span>
                    </label>
                    
                    <label class="uopwc-mode-option <?php echo $settings['pause_mode'] === 'daily' ? 'selected' : ''; ?>">
                        <input type="radio" name="pause_mode" value="daily" <?php checked( $settings['pause_mode'], 'daily' ); ?>>
                        <span class="dashicons dashicons-clock"></span>
                        <strong><?php esc_html_e( 'Daily Schedule', 'unstoppable-order-pause-for-woocommerce' ); ?></strong>
                        <span class="description"><?php esc_html_e( 'Automatically pause at closing time daily', 'unstoppable-order-pause-for-woocommerce' ); ?></span>
                    </label>
                    
                    <label class="uopwc-mode-option <?php echo $settings['pause_mode'] === 'weekly' ? 'selected' : ''; ?>">
                        <input type="radio" name="pause_mode" value="weekly" <?php checked( $settings['pause_mode'], 'weekly' ); ?>>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <strong><?php esc_html_e( 'Weekly Schedule', 'unstoppable-order-pause-for-woocommerce' ); ?></strong>
                        <span class="description"><?php esc_html_e( 'Set specific days and hours for each day', 'unstoppable-order-pause-for-woocommerce' ); ?></span>
                    </label>
                </div>
            </div>

            <div class="uopwc-card uopwc-daily-schedule" style="<?php echo $settings['pause_mode'] !== 'daily' ? 'display:none;' : ''; ?>">
                <h3><?php esc_html_e( 'Daily Operating Hours', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Orders will be automatically paused outside these hours.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                
                <div class="uopwc-time-range">
                    <div class="uopwc-form-row">
                        <label for="uopwc-daily-open"><?php esc_html_e( 'Opening Time:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                        <input type="time" id="uopwc-daily-open" name="daily_open_time" value="<?php echo esc_attr( $settings['daily_open_time'] ); ?>">
                    </div>
                    <div class="uopwc-form-row">
                        <label for="uopwc-daily-close"><?php esc_html_e( 'Closing Time:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                        <input type="time" id="uopwc-daily-close" name="daily_close_time" value="<?php echo esc_attr( $settings['daily_close_time'] ); ?>">
                    </div>
                </div>
                <p class="uopwc-timezone-info">
                    <span class="dashicons dashicons-info"></span>
                    <?php 
                    printf(
                        /* translators: %s: WordPress timezone string (e.g. "America/New_York"). */
                        esc_html__( 'Times are based on your WordPress timezone: %s', 'unstoppable-order-pause-for-woocommerce' ),
                        '<strong>' . esc_html( wp_timezone_string() ) . '</strong>'
                    );
                    ?>
                </p>
            </div>

            <div class="uopwc-card uopwc-weekly-schedule" style="<?php echo $settings['pause_mode'] !== 'weekly' ? 'display:none;' : ''; ?>">
                <h3><?php esc_html_e( 'Weekly Operating Hours', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Set custom hours for each day of the week.', 'unstoppable-order-pause-for-woocommerce' ); ?></p>
                
                <table class="uopwc-schedule-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Day', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Open', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Close', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $days_of_week as $day_key => $day_label ) : 
                            $day_settings = isset( $settings['scheduled_days'][ $day_key ] ) 
                                ? $settings['scheduled_days'][ $day_key ] 
                                : array( 'enabled' => 'yes', 'open' => '08:00', 'close' => '22:00' );
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $day_label ); ?></strong></td>
                                <td>
                                    <select name="scheduled_days[<?php echo esc_attr( $day_key ); ?>][enabled]">
                                        <option value="yes" <?php selected( $day_settings['enabled'] ?? 'yes', 'yes' ); ?>><?php esc_html_e( 'Open', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                                        <option value="no" <?php selected( $day_settings['enabled'] ?? 'yes', 'no' ); ?>><?php esc_html_e( 'Closed All Day', 'unstoppable-order-pause-for-woocommerce' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="scheduled_days[<?php echo esc_attr( $day_key ); ?>][open]" 
                                           value="<?php echo esc_attr( $day_settings['open'] ?? '08:00' ); ?>">
                                </td>
                                <td>
                                    <input type="time" name="scheduled_days[<?php echo esc_attr( $day_key ); ?>][close]" 
                                           value="<?php echo esc_attr( $day_settings['close'] ?? '22:00' ); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="uopwc-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-saved"></span>
                    <?php esc_html_e( 'Save Schedule', 'unstoppable-order-pause-for-woocommerce' ); ?>
                </button>
                <span class="uopwc-save-status"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render license tab.
     */
    private function render_license_tab() {
        $license_handler = new UOPWC_License( UOPWC_PLUGIN_SLUG, UOPWC_OPTION_PREFIX );
        $license_key = $license_handler->get_license_key();
        $license_data = $license_handler->get_license_data();
        $is_active = $license_handler->is_active();
        ?>
        <div class="uopwc-card">
            <h3><?php esc_html_e( 'License Activation', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
            <p class="description">
                <?php esc_html_e( 'Enter your license key to receive updates and support.', 'unstoppable-order-pause-for-woocommerce' ); ?>
                <a href="https://unstoppablesolutions.app/Dashboard" target="_blank"><?php esc_html_e( 'Get your license key', 'unstoppable-order-pause-for-woocommerce' ); ?></a>
            </p>
            
            <div class="uopwc-license-status <?php echo $is_active ? 'active' : 'inactive'; ?>">
                <span class="dashicons <?php echo $is_active ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                <?php echo $is_active 
                    ? esc_html__( 'License Active', 'unstoppable-order-pause-for-woocommerce' ) 
                    : esc_html__( 'License Inactive', 'unstoppable-order-pause-for-woocommerce' ); 
                ?>
            </div>
            
            <form id="uopwc-license-form" class="uopwc-license-form">
                <?php wp_nonce_field( 'uopwc_license_action', 'uopwc_license_nonce' ); ?>
                
                <div class="uopwc-form-row">
                    <label for="uopwc-license-key"><?php esc_html_e( 'License Key:', 'unstoppable-order-pause-for-woocommerce' ); ?></label>
                    <input type="text" id="uopwc-license-key" name="license_key" 
                           value="<?php echo esc_attr( $license_handler->get_masked_key() ); ?>"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           <?php echo $is_active ? 'readonly' : ''; ?>>
                </div>
                
                <?php if ( $is_active && ! empty( $license_data ) ) : ?>
                    <div class="uopwc-license-info">
                        <?php if ( ! empty( $license_data['license_type'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'License Type:', 'unstoppable-order-pause-for-woocommerce' ); ?></strong> <?php echo esc_html( ucfirst( $license_data['license_type'] ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $license_data['expires'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'Expires:', 'unstoppable-order-pause-for-woocommerce' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_data['expires'] ) ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( isset( $license_data['activations_left'] ) ) : ?>
                            <p><strong><?php esc_html_e( 'Activations Left:', 'unstoppable-order-pause-for-woocommerce' ); ?></strong> <?php echo esc_html( $license_data['activations_left'] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="uopwc-license-actions">
                    <?php if ( $is_active ) : ?>
                        <button type="button" class="button" id="uopwc-deactivate-license">
                            <?php esc_html_e( 'Deactivate License', 'unstoppable-order-pause-for-woocommerce' ); ?>
                        </button>
                    <?php else : ?>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Activate License', 'unstoppable-order-pause-for-woocommerce' ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="uopwc-card">
            <h3><?php esc_html_e( 'Plugin Information', 'unstoppable-order-pause-for-woocommerce' ); ?></h3>
            <table class="uopwc-info-table">
                <tr>
                    <th><?php esc_html_e( 'Version:', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                    <td><?php echo esc_html( UOPWC_VERSION ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'WooCommerce:', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                    <td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : __( 'Not detected', 'unstoppable-order-pause-for-woocommerce' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'PHP Version:', 'unstoppable-order-pause-for-woocommerce' ); ?></th>
                    <td><?php echo esc_html( phpversion() ); ?></td>
                </tr>
            </table>
            
            <div class="uopwc-check-updates">
                <button type="button" class="button" id="uopwc-check-updates">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Check for Updates', 'unstoppable-order-pause-for-woocommerce' ); ?>
                </button>
                <span class="uopwc-update-status"></span>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for saving settings.
     */
    public function ajax_save_settings() {
        // Verify nonce
        check_ajax_referer( 'uopwc_admin_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        // Get and sanitize settings
        $settings_to_save = array(
            'paused_message'   => isset( $_POST['paused_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['paused_message'] ) ) : '',
            'show_countdown'   => isset( $_POST['show_countdown'] ) ? 'yes' : 'no',
            'hide_add_to_cart' => isset( $_POST['hide_add_to_cart'] ) ? 'yes' : 'no',
            'hide_checkout'    => isset( $_POST['hide_checkout'] ) ? 'yes' : 'no',
            'show_notice_shop' => isset( $_POST['show_notice_shop'] ) ? 'yes' : 'no',
            'show_notice_cart' => isset( $_POST['show_notice_cart'] ) ? 'yes' : 'no',
            'show_admin_bar'   => isset( $_POST['show_admin_bar'] ) ? 'yes' : 'no',
            'notice_style'     => isset( $_POST['notice_style'] ) ? sanitize_key( wp_unslash( $_POST['notice_style'] ) ) : 'warning',
            'pause_mode'       => isset( $_POST['pause_mode'] ) ? sanitize_key( wp_unslash( $_POST['pause_mode'] ) ) : 'manual',
            'daily_open_time'  => isset( $_POST['daily_open_time'] ) ? sanitize_text_field( wp_unslash( $_POST['daily_open_time'] ) ) : '08:00',
            'daily_close_time' => isset( $_POST['daily_close_time'] ) ? sanitize_text_field( wp_unslash( $_POST['daily_close_time'] ) ) : '22:00',
        );
        
        // Validate notice style
        $allowed_styles = array( 'warning', 'error', 'info', 'success' );
        if ( ! in_array( $settings_to_save['notice_style'], $allowed_styles, true ) ) {
            $settings_to_save['notice_style'] = 'warning';
        }
        
        // Validate pause mode
        $allowed_modes = array( 'manual', 'daily', 'weekly' );
        if ( ! in_array( $settings_to_save['pause_mode'], $allowed_modes, true ) ) {
            $settings_to_save['pause_mode'] = 'manual';
        }
        
        // Handle scheduled days for weekly mode
        if ( isset( $_POST['scheduled_days'] ) && is_array( $_POST['scheduled_days'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in loop below
            $raw_scheduled_days = wp_unslash( $_POST['scheduled_days'] );
            $scheduled_days = array();
            $allowed_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
            
            foreach ( $raw_scheduled_days as $day => $day_data ) {
                $day = sanitize_key( $day );
                if ( in_array( $day, $allowed_days, true ) && is_array( $day_data ) ) {
                    $scheduled_days[ $day ] = array(
                        'enabled' => isset( $day_data['enabled'] ) && sanitize_key( $day_data['enabled'] ) === 'yes' ? 'yes' : 'no',
                        'open'    => isset( $day_data['open'] ) ? sanitize_text_field( $day_data['open'] ) : '08:00',
                        'close'   => isset( $day_data['close'] ) ? sanitize_text_field( $day_data['close'] ) : '22:00',
                    );
                }
            }
            $settings_to_save['scheduled_days'] = $scheduled_days;
        }
        
        // Save settings
        foreach ( $settings_to_save as $key => $value ) {
            update_option( 'uopwc_' . $key, $value );
        }
        
        wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'unstoppable-order-pause-for-woocommerce' ) ) );
    }

    /**
     * AJAX handler for toggling pause.
     */
    public function ajax_toggle_pause() {
        // Verify nonce
        check_ajax_referer( 'uopwc_admin_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        $is_paused = UOPWC_Pause_Handler::is_orders_paused();
        
        if ( $is_paused ) {
            // Resume orders
            UOPWC_Pause_Handler::resume_orders();
            wp_send_json_success( array(
                'paused'  => false,
                'message' => __( 'Orders have been resumed!', 'unstoppable-order-pause-for-woocommerce' ),
            ) );
        } else {
            // Pause orders
            UOPWC_Pause_Handler::pause_orders();
            wp_send_json_success( array(
                'paused'  => true,
                'message' => __( 'Orders have been paused!', 'unstoppable-order-pause-for-woocommerce' ),
            ) );
        }
    }

    /**
     * AJAX handler for quick pause.
     */
    public function ajax_quick_pause() {
        // Verify nonce
        check_ajax_referer( 'uopwc_admin_nonce', 'nonce' );
        
        // Check permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        $duration = isset( $_POST['duration'] ) ? sanitize_text_field( wp_unslash( $_POST['duration'] ) ) : '';
        $reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
        $pause_until = isset( $_POST['pause_until'] ) ? sanitize_text_field( wp_unslash( $_POST['pause_until'] ) ) : '';
        
        if ( ! empty( $pause_until ) ) {
            // Custom pause until time
            $pause_time = strtotime( $pause_until );
            if ( ! $pause_time || $pause_time <= time() ) {
                wp_send_json_error( array( 'message' => __( 'Please select a future date and time.', 'unstoppable-order-pause-for-woocommerce' ) ) );
            }
        } elseif ( $duration === 'day' ) {
            // Rest of day - until midnight
            $pause_time = strtotime( 'tomorrow midnight' );
        } elseif ( is_numeric( $duration ) ) {
            // Duration in minutes
            $minutes = absint( $duration );
            if ( $minutes < 1 || $minutes > 1440 ) {
                wp_send_json_error( array( 'message' => __( 'Invalid duration.', 'unstoppable-order-pause-for-woocommerce' ) ) );
            }
            $pause_time = time() + ( $minutes * 60 );
        } else {
            wp_send_json_error( array( 'message' => __( 'Invalid duration specified.', 'unstoppable-order-pause-for-woocommerce' ) ) );
        }
        
        UOPWC_Pause_Handler::pause_orders( $pause_time, $reason );
        
        wp_send_json_success( array(
            'paused'      => true,
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Local-timezone string matches the same format stored in the uopwc_pause_until option.
            'pause_until' => date( 'Y-m-d H:i:s', $pause_time ),
            'message'     => sprintf(
                /* translators: %s: formatted date and time until which orders are paused. */
                __( 'Orders paused until %s', 'unstoppable-order-pause-for-woocommerce' ),
                date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $pause_time )
            ),
        ) );
    }
}
