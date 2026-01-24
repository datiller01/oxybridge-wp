<?php
/**
 * REST Core Controller.
 *
 * Handles core endpoints like health check, plugin info, and authentication.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

/**
 * Core REST controller.
 *
 * @since 1.1.0
 */
class REST_Core extends REST_Controller {

    /**
     * Register core routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/health',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'health_check' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/info',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_info' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/authenticate',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'authenticate' ),
                'permission_callback' => array( $this, 'check_authentication' ),
            )
        );
    }

    /**
     * Health check endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function health_check( \WP_REST_Request $request ) {
        return $this->format_response(
            array(
                'status'    => 'ok',
                'timestamp' => gmdate( 'c' ),
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
            )
        );
    }

    /**
     * Get plugin info endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_info( \WP_REST_Request $request ) {
        return $this->format_response(
            array(
                'plugin'       => 'oxybridge-wp',
                'version'      => defined( 'OXYBRIDGE_VERSION' ) ? OXYBRIDGE_VERSION : '1.0.0',
                'builder'      => $this->get_builder_name(),
                'builder_version' => $this->get_oxygen_version(),
                'php_version'  => PHP_VERSION,
                'wp_version'   => get_bloginfo( 'version' ),
                'endpoints'    => $this->get_available_endpoints(),
                'capabilities' => $this->get_capabilities(),
            )
        );
    }

    /**
     * Authenticate endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function authenticate( \WP_REST_Request $request ) {
        $user = wp_get_current_user();

        return $this->format_response(
            array(
                'authenticated' => true,
                'user_id'       => $user->ID,
                'user_login'    => $user->user_login,
                'display_name'  => $user->display_name,
                'capabilities'  => array(
                    'can_edit_posts'   => current_user_can( 'edit_posts' ),
                    'can_edit_pages'   => current_user_can( 'edit_pages' ),
                    'can_manage_options' => current_user_can( 'manage_options' ),
                ),
            )
        );
    }

    /**
     * Get available endpoints.
     *
     * @since 1.1.0
     * @return array List of endpoints.
     */
    private function get_available_endpoints(): array {
        return array(
            // Core.
            'GET /health'           => 'Health check',
            'GET /info'             => 'Plugin information',
            'POST /authenticate'    => 'Verify authentication',

            // Documents.
            'GET /documents/{id}'   => 'Read document tree',
            'POST /documents/{id}'  => 'Update document tree',
            'POST /regenerate-css/{id}' => 'Regenerate CSS cache',

            // Pages.
            'GET /pages'            => 'List pages',
            'POST /pages'           => 'Create page',
            'GET /pages/{id}'       => 'Get page',
            'PUT /pages/{id}'       => 'Update page',
            'DELETE /pages/{id}'    => 'Delete page',

            // Templates.
            'GET /templates'        => 'List templates',
            'POST /templates'       => 'Create template',
            'GET /templates/{id}'   => 'Get template',
            'PUT /templates/{id}'   => 'Update template',
            'DELETE /templates/{id}' => 'Delete template',
            'GET /template-types'   => 'List template types',

            // Settings.
            'GET /colors'           => 'Get color palette',
            'GET /fonts'            => 'Get fonts',
            'GET /variables'        => 'Get CSS variables',
            'GET /breakpoints'      => 'Get breakpoints',
            'GET /global-styles'    => 'Get all global styles',
            'GET /css-classes'      => 'Get CSS classes',

            // AI.
            'GET /ai/docs'          => 'Complete API documentation for AI agents',
            'GET /ai/context'       => 'Get AI context',
            'GET /ai/tokens'        => 'Get design tokens',
            'GET /ai/schema'        => 'Get element schema',
            'GET /ai/components'    => 'List AI components',
            'GET /ai/components/{name}' => 'Get component',
            'POST /ai/transform'    => 'Transform simplified tree',
            'POST /ai/validate'     => 'Validate tree',
        );
    }

    /**
     * Get plugin capabilities.
     *
     * @since 1.1.0
     * @return array Capabilities list.
     */
    private function get_capabilities(): array {
        return array(
            'read_documents'    => true,
            'write_documents'   => true,
            'read_templates'    => true,
            'write_templates'   => true,
            'read_settings'     => true,
            'ai_transform'      => class_exists( 'Oxybridge\Property_Transformer' ),
            'css_regeneration'  => function_exists( 'Breakdance\Render\generateCacheForPost' ),
        );
    }
}
