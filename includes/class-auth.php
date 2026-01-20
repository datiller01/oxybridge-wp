<?php
/**
 * Authentication Helper Class
 *
 * Provides authentication and authorization helpers for the Oxybridge REST API.
 * Uses WordPress Application Passwords for secure API access.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Auth
 *
 * Authentication and authorization helper for REST API endpoints.
 *
 * @since 1.0.0
 */
class Auth {

    /**
     * Required capability for accessing Oxybridge API.
     *
     * @var string
     */
    const REQUIRED_CAPABILITY = 'edit_posts';

    /**
     * REST API namespace.
     *
     * @var string
     */
    const API_NAMESPACE = 'oxybridge/v1';

    /**
     * Option key for API access settings.
     *
     * @var string
     */
    const OPTION_KEY = 'oxybridge_api_settings';

    /**
     * Singleton instance.
     *
     * @var Auth|null
     */
    private static ?Auth $instance = null;

    /**
     * Get singleton instance.
     *
     * @since 1.0.0
     *
     * @return Auth The singleton instance.
     */
    public static function get_instance(): Auth {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Private constructor for singleton.
    }

    /**
     * Check if the current request has permission to access Oxybridge API.
     *
     * This method is used as a permission_callback for REST API routes.
     * It verifies that the user is authenticated (via Application Password
     * or other WordPress authentication methods) and has the required capability.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return bool|\WP_Error True if permission granted, WP_Error otherwise.
     */
    public function check_permission( \WP_REST_Request $request ) {
        // Check if user is logged in (Application Password auth sets the current user).
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'oxybridge_unauthorized',
                __( 'Authentication required. Please use Application Passwords or another authentication method.', 'oxybridge-wp' ),
                array( 'status' => 401 )
            );
        }

        // Check if user has required capability.
        if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
            return new \WP_Error(
                'oxybridge_forbidden',
                __( 'You do not have permission to access this resource.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Check if API access is enabled (optional setting).
        if ( ! $this->is_api_enabled() ) {
            return new \WP_Error(
                'oxybridge_disabled',
                __( 'Oxybridge API access is currently disabled.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check read-only permission for API endpoints.
     *
     * Less restrictive than check_permission, suitable for read-only endpoints.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The REST request object.
     * @return bool|\WP_Error True if permission granted, WP_Error otherwise.
     */
    public function check_read_permission( \WP_REST_Request $request ) {
        // For read operations, we still require authentication.
        return $this->check_permission( $request );
    }

    /**
     * Check if API access is enabled.
     *
     * @since 1.0.0
     *
     * @return bool True if API is enabled, false otherwise.
     */
    public function is_api_enabled(): bool {
        $settings = get_option( self::OPTION_KEY, array() );

        // Default to enabled if not explicitly disabled.
        if ( ! isset( $settings['enabled'] ) ) {
            return true;
        }

        return (bool) $settings['enabled'];
    }

    /**
     * Enable or disable API access.
     *
     * @since 1.0.0
     *
     * @param bool $enabled Whether to enable API access.
     * @return bool True if option was updated successfully.
     */
    public function set_api_enabled( bool $enabled ): bool {
        $settings            = get_option( self::OPTION_KEY, array() );
        $settings['enabled'] = $enabled;

        return update_option( self::OPTION_KEY, $settings );
    }

    /**
     * Get the current authenticated user.
     *
     * @since 1.0.0
     *
     * @return \WP_User|null The current user or null if not authenticated.
     */
    public function get_current_user(): ?\WP_User {
        if ( ! is_user_logged_in() ) {
            return null;
        }

        return wp_get_current_user();
    }

    /**
     * Check if Application Passwords are available.
     *
     * Application Passwords were introduced in WordPress 5.6.
     *
     * @since 1.0.0
     *
     * @return bool True if Application Passwords are available.
     */
    public function is_application_passwords_available(): bool {
        return class_exists( '\WP_Application_Passwords' )
            && \WP_Application_Passwords::is_in_use();
    }

    /**
     * Check if the current request is authenticated via Application Password.
     *
     * @since 1.0.0
     *
     * @return bool True if authenticated via Application Password.
     */
    public function is_application_password_auth(): bool {
        // WordPress sets this when authenticated via Application Password.
        return defined( 'REST_REQUEST' )
            && REST_REQUEST
            && is_user_logged_in()
            && ! empty( $GLOBALS['wp_rest_application_password_status'] );
    }

    /**
     * Get the Application Password UUID for the current request.
     *
     * @since 1.0.0
     *
     * @return string|null The Application Password UUID or null.
     */
    public function get_application_password_uuid(): ?string {
        if ( ! $this->is_application_password_auth() ) {
            return null;
        }

        $uuid = $GLOBALS['wp_rest_application_password_uuid'] ?? null;

        return is_string( $uuid ) ? $uuid : null;
    }

    /**
     * Validate that a user can be granted Application Password access.
     *
     * @since 1.0.0
     *
     * @param int $user_id The user ID to validate.
     * @return bool True if user can use Application Passwords for Oxybridge.
     */
    public function can_user_access_api( int $user_id ): bool {
        $user = get_user_by( 'ID', $user_id );

        if ( ! $user ) {
            return false;
        }

        return user_can( $user, self::REQUIRED_CAPABILITY );
    }

    /**
     * Log API access for auditing purposes.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  The REST request.
     * @param string           $endpoint The endpoint being accessed.
     * @return void
     */
    public function log_api_access( \WP_REST_Request $request, string $endpoint ): void {
        // Only log if logging is enabled.
        $settings = get_option( self::OPTION_KEY, array() );

        if ( empty( $settings['logging_enabled'] ) ) {
            return;
        }

        $user = $this->get_current_user();

        $log_entry = array(
            'timestamp'  => current_time( 'mysql', true ),
            'user_id'    => $user ? $user->ID : 0,
            'user_login' => $user ? $user->user_login : 'anonymous',
            'endpoint'   => $endpoint,
            'method'     => $request->get_method(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $request->get_header( 'user-agent' ) ?? '',
        );

        /**
         * Fires when an API access is logged.
         *
         * @since 1.0.0
         *
         * @param array            $log_entry The log entry data.
         * @param \WP_REST_Request $request   The REST request object.
         */
        do_action( 'oxybridge_api_access_logged', $log_entry, $request );

        // Store log (keeping last 100 entries).
        $logs   = get_option( 'oxybridge_api_logs', array() );
        $logs[] = $log_entry;

        // Keep only last 100 entries.
        if ( count( $logs ) > 100 ) {
            $logs = array_slice( $logs, -100 );
        }

        update_option( 'oxybridge_api_logs', $logs );
    }

    /**
     * Get the client IP address.
     *
     * @since 1.0.0
     *
     * @return string The client IP address.
     */
    private function get_client_ip(): string {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare.
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // Handle comma-separated list (X-Forwarded-For may contain multiple IPs).
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip  = trim( $ips[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get API access logs.
     *
     * @since 1.0.0
     *
     * @param int $limit Maximum number of log entries to return.
     * @return array Array of log entries.
     */
    public function get_api_logs( int $limit = 50 ): array {
        $logs = get_option( 'oxybridge_api_logs', array() );

        // Return most recent entries first.
        $logs = array_reverse( $logs );

        return array_slice( $logs, 0, $limit );
    }

    /**
     * Clear API access logs.
     *
     * @since 1.0.0
     *
     * @return bool True if logs were cleared.
     */
    public function clear_api_logs(): bool {
        return delete_option( 'oxybridge_api_logs' );
    }

    /**
     * Get authentication status information.
     *
     * Useful for debugging and admin UI.
     *
     * @since 1.0.0
     *
     * @return array Authentication status information.
     */
    public function get_auth_status(): array {
        $user = $this->get_current_user();

        return array(
            'is_authenticated'                 => is_user_logged_in(),
            'user_id'                          => $user ? $user->ID : null,
            'user_login'                       => $user ? $user->user_login : null,
            'has_required_capability'          => $user ? user_can( $user, self::REQUIRED_CAPABILITY ) : false,
            'api_enabled'                      => $this->is_api_enabled(),
            'application_passwords_available'  => $this->is_application_passwords_available(),
            'is_application_password_auth'     => $this->is_application_password_auth(),
            'required_capability'              => self::REQUIRED_CAPABILITY,
        );
    }

    /**
     * Generate REST API response headers for CORS.
     *
     * Note: Be cautious with CORS settings in production.
     * Only enable for trusted origins.
     *
     * @since 1.0.0
     *
     * @param array $headers Existing headers.
     * @return array Modified headers.
     */
    public function add_cors_headers( array $headers ): array {
        $settings       = get_option( self::OPTION_KEY, array() );
        $allowed_origin = $settings['cors_origin'] ?? '';

        // Only add CORS headers if explicitly configured.
        if ( ! empty( $allowed_origin ) ) {
            $headers['Access-Control-Allow-Origin']      = sanitize_text_field( $allowed_origin );
            $headers['Access-Control-Allow-Methods']     = 'GET, OPTIONS';
            $headers['Access-Control-Allow-Credentials'] = 'true';
            $headers['Access-Control-Allow-Headers']     = 'Authorization, Content-Type';
        }

        return $headers;
    }
}
