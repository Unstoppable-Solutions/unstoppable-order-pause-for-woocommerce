<?php
/**
 * License Handler Class
 *
 * Handles license activation, validation, and deactivation with Base44 API.
 *
 * @package UOPWC_Order_Pause
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * License Handler Class.
 */
class UOPWC_License {

    /**
     * Plugin slug for license.
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Option prefix for storing license data.
     *
     * @var string
     */
    private $option_prefix;

    /**
     * Base44 API base URL.
     *
     * @var string
     */
    private $api_base_url = 'https://fm-web-solutions-plugin-hub-d05f2182.base44.app/functions';

    /**
     * Constructor.
     *
     * @param string $plugin_slug   Plugin slug (e.g., "unstoppable-order-pause-for-woocommerce").
     * @param string $option_prefix Option prefix for storing data (e.g., "uop").
     */
    public function __construct( $plugin_slug, $option_prefix ) {
        $this->plugin_slug   = $plugin_slug;
        $this->option_prefix = $option_prefix;
    }

    /**
     * Make API request to Base44.
     *
     * @param string $endpoint API endpoint name (e.g., 'validateLicense').
     * @param array  $body     Request body data.
     * @return array|WP_Error Response array or WP_Error on failure.
     */
    private function api_request( $endpoint, $body = array() ) {
        $url = trailingslashit( $this->api_base_url ) . $endpoint;

        $response = wp_remote_post( $url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
            'body'      => wp_json_encode( $body ),
        ) );

        // Check for WP errors
        if ( is_wp_error( $response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'UOPWC License API Error (' . $this->plugin_slug . '): ' . $response->get_error_message() );
            }
            return $response;
        }

        // Check response code
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            return new WP_Error( 
                'api_error', 
                sprintf( 'API returned status code: %d', $response_code ) 
            );
        }

        // Decode response
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', 'Invalid JSON response from API.' );
        }

        return $data;
    }

    /**
     * Validate a license key.
     *
     * @param string $license_key License key to validate.
     * @return array|WP_Error Validation response or error.
     */
    public function validate( $license_key = '' ) {
        if ( empty( $license_key ) ) {
            $license_key = $this->get_license_key();
        }

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'No license key provided.' );
        }

        $response = $this->api_request( 'validateLicense', array(
            'license_key' => $license_key,
            'site_url'    => home_url(),
            'plugin_slug' => $this->plugin_slug,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Cache the validation result
        if ( isset( $response['success'] ) && $response['success'] === true ) {
            set_transient( 
                'uopwc_license_status', 
                $response, 
                12 * HOUR_IN_SECONDS 
            );
        }

        return $response;
    }

    /**
     * Activate a license key.
     *
     * @param string $license_key License key to activate.
     * @return array|WP_Error Activation response or error.
     */
    public function activate( $license_key ) {
        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'No license key provided.' );
        }

        $response = $this->api_request( 'activateLicense', array(
            'license_key' => $license_key,
            'site_url'    => home_url(),
            'site_name'   => get_bloginfo( 'name' ),
            'plugin_slug' => $this->plugin_slug,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['success'] ) && $response['success'] === true ) {
            // Store license data
            update_option( 'uopwc_license_key', $license_key );
            update_option( 'uopwc_license_data', $response );
            
            // Clear any cached status
            delete_transient( 'uopwc_license_status' );
            
            return $response;
        }

        return new WP_Error( 
            'activation_failed', 
            $response['message'] ?? 'License activation failed.' 
        );
    }

    /**
     * Deactivate the current license.
     *
     * @return array|WP_Error Deactivation response or error.
     */
    public function deactivate() {
        $license_key = $this->get_license_key();

        if ( empty( $license_key ) ) {
            return new WP_Error( 'no_license', 'No license key found.' );
        }

        $response = $this->api_request( 'deactivateLicense', array(
            'license_key' => $license_key,
            'site_url'    => home_url(),
            'plugin_slug' => $this->plugin_slug,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( isset( $response['success'] ) && $response['success'] === true ) {
            // Clear stored license data
            delete_option( 'uopwc_license_key' );
            delete_option( 'uopwc_license_data' );
            delete_transient( 'uopwc_license_status' );
            
            return $response;
        }

        return new WP_Error( 
            'deactivation_failed', 
            $response['message'] ?? 'License deactivation failed.' 
        );
    }

    /**
     * Get stored license key.
     *
     * @return string License key or empty string.
     */
    public function get_license_key() {
        return get_option( 'uopwc_license_key', '' );
    }

    /**
     * Get stored license data.
     *
     * @return array License data or empty array.
     */
    public function get_license_data() {
        return get_option( 'uopwc_license_data', array() );
    }

    /**
     * Check if license is active.
     *
     * @return bool True if license is active.
     */
    public function is_active() {
        // Check cached status first
        $cached = get_transient( 'uopwc_license_status' );
        if ( false !== $cached && isset( $cached['success'] ) ) {
            return $cached['success'] === true && 
                   isset( $cached['status'] ) && 
                   $cached['status'] === 'active';
        }

        // Check stored license data
        $license_data = $this->get_license_data();
        if ( ! empty( $license_data ) && isset( $license_data['success'] ) && $license_data['success'] === true ) {
            return isset( $license_data['status'] ) && $license_data['status'] === 'active';
        }

        return false;
    }

    /**
     * Get masked license key for display.
     *
     * @return string Masked license key.
     */
    public function get_masked_key() {
        $key = $this->get_license_key();
        
        if ( empty( $key ) ) {
            return '';
        }

        if ( strlen( $key ) <= 8 ) {
            return str_repeat( '*', strlen( $key ) );
        }

        return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
    }
}
