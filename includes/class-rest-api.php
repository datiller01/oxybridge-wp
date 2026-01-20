<?php
/**
 * REST API class for Oxybridge.
 *
 * Handles registration of custom REST API endpoints for MCP bridge communication.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class REST_API
 *
 * Registers and manages custom REST API endpoints for the Oxybridge MCP bridge.
 * Provides endpoints for reading Oxygen/Breakdance document trees, templates,
 * global settings, and other builder data.
 *
 * @since 1.0.0
 */
class REST_API {

    /**
     * REST API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'oxybridge/v1';

    /**
     * Constructor.
     *
     * Registers the REST API routes on the rest_api_init hook.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     *
     * Registers all custom endpoints for the Oxybridge MCP bridge.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes() {
        // Health check endpoint - always available.
        register_rest_route(
            self::NAMESPACE,
            '/health',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'health_check' ),
                'permission_callback' => '__return_true',
            )
        );

        // Authentication endpoint - returns nonce for subsequent requests.
        register_rest_route(
            self::NAMESPACE,
            '/auth',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'authenticate' ),
                'permission_callback' => array( $this, 'check_authentication' ),
            )
        );

        // Document reader endpoint - read Oxygen/Breakdance document tree.
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'read_document' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'id'               => array(
                        'description'       => __( 'WordPress post/page ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'include_metadata' => array(
                        'description' => __( 'Include document metadata in response.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                    'flatten_elements' => array(
                        'description' => __( 'Return flat list of elements instead of tree.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => false,
                    ),
                ),
            )
        );

        // Page/Post navigator endpoint - list and search content.
        register_rest_route(
            self::NAMESPACE,
            '/pages',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'list_pages' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'post_type'           => array(
                        'description'       => __( 'Filter by post type.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'any',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'search'              => array(
                        'description'       => __( 'Search by title.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status'              => array(
                        'description'       => __( 'Filter by post status.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'publish',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'has_oxygen_content'  => array(
                        'description' => __( 'Only return posts with Oxygen content.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                    'per_page'            => array(
                        'description'       => __( 'Results per page.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'default'           => 20,
                        'minimum'           => 1,
                        'maximum'           => 100,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'                => array(
                        'description'       => __( 'Page number.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'default'           => 1,
                        'minimum'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Templates endpoint - list Oxygen templates.
        register_rest_route(
            self::NAMESPACE,
            '/templates',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'list_templates' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'template_type' => array(
                        'description'       => __( 'Filter by template type.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Global settings endpoint - read global Oxygen settings.
        register_rest_route(
            self::NAMESPACE,
            '/settings',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'read_global_settings' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'key' => array(
                        'description'       => __( 'Specific setting key to retrieve.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Element schema endpoint - get element definitions.
        register_rest_route(
            self::NAMESPACE,
            '/schema',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_element_schema' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'element_type'     => array(
                        'description'       => __( 'Specific element type to retrieve schema for.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'include_controls' => array(
                        'description' => __( 'Include control definitions.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                ),
            )
        );

        /**
         * Fires after Oxybridge REST routes are registered.
         *
         * Allows other plugins to register additional routes under the Oxybridge namespace.
         *
         * @since 1.0.0
         * @param REST_API $this The REST API instance.
         */
        do_action( 'oxybridge_rest_routes_registered', $this );
    }

    /**
     * Health check endpoint callback.
     *
     * Returns basic information about the Oxybridge installation.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function health_check( \WP_REST_Request $request ) {
        $response = array(
            'status'          => 'ok',
            'version'         => OXYBRIDGE_VERSION,
            'oxygen_active'   => oxybridge_is_oxygen_active(),
            'oxygen_version'  => $this->get_oxygen_version(),
            'wordpress'       => get_bloginfo( 'version' ),
            'php'             => PHP_VERSION,
            'timestamp'       => current_time( 'c' ),
        );

        return rest_ensure_response( $response );
    }

    /**
     * Authentication endpoint callback.
     *
     * Validates credentials and returns a nonce for subsequent requests.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function authenticate( \WP_REST_Request $request ) {
        $user = wp_get_current_user();

        if ( ! $user->exists() ) {
            return new \WP_Error(
                'rest_not_authenticated',
                __( 'Authentication failed.', 'oxybridge-wp' ),
                array( 'status' => 401 )
            );
        }

        $nonce = wp_create_nonce( 'wp_rest' );

        return rest_ensure_response(
            array(
                'success'  => true,
                'user_id'  => $user->ID,
                'username' => $user->user_login,
                'nonce'    => $nonce,
            )
        );
    }

    /**
     * Read document endpoint callback.
     *
     * Placeholder for document reading functionality.
     * Will be implemented in subsequent subtasks.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function read_document( \WP_REST_Request $request ) {
        $post_id = $request->get_param( 'id' );

        // Placeholder response - full implementation in subsequent subtasks.
        return rest_ensure_response(
            array(
                'post_id' => $post_id,
                'message' => __( 'Document reading will be implemented in subsequent subtasks.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * List pages endpoint callback.
     *
     * Placeholder for page listing functionality.
     * Will be implemented in subsequent subtasks.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function list_pages( \WP_REST_Request $request ) {
        // Placeholder response - full implementation in subsequent subtasks.
        return rest_ensure_response(
            array(
                'pages'       => array(),
                'total'       => 0,
                'total_pages' => 0,
                'page'        => $request->get_param( 'page' ),
                'message'     => __( 'Page listing will be implemented in subsequent subtasks.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * List templates endpoint callback.
     *
     * Placeholder for template listing functionality.
     * Will be implemented in subsequent subtasks.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function list_templates( \WP_REST_Request $request ) {
        // Placeholder response - full implementation in subsequent subtasks.
        return rest_ensure_response(
            array(
                'templates' => array(),
                'message'   => __( 'Template listing will be implemented in subsequent subtasks.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * Read global settings endpoint callback.
     *
     * Placeholder for global settings reading functionality.
     * Will be implemented in subsequent subtasks.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function read_global_settings( \WP_REST_Request $request ) {
        // Placeholder response - full implementation in subsequent subtasks.
        return rest_ensure_response(
            array(
                'settings' => array(),
                'message'  => __( 'Global settings reading will be implemented in subsequent subtasks.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * Get element schema endpoint callback.
     *
     * Placeholder for element schema generation functionality.
     * Will be implemented in subsequent subtasks.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_element_schema( \WP_REST_Request $request ) {
        // Placeholder response - full implementation in subsequent subtasks.
        return rest_ensure_response(
            array(
                'elements'      => array(),
                'control_types' => array(),
                'categories'    => array(),
                'message'       => __( 'Element schema generation will be implemented in subsequent subtasks.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * Check if user is authenticated.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if authenticated, WP_Error otherwise.
     */
    public function check_authentication( \WP_REST_Request $request ) {
        // Allow application passwords and basic auth.
        if ( is_user_logged_in() ) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __( 'Authentication required.', 'oxybridge-wp' ),
            array( 'status' => 401 )
        );
    }

    /**
     * Check if user has read permission.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if permitted, WP_Error otherwise.
     */
    public function check_read_permission( \WP_REST_Request $request ) {
        // User must be authenticated.
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'Authentication required.', 'oxybridge-wp' ),
                array( 'status' => 401 )
            );
        }

        // Check for edit_posts capability (minimum required for reading builder content).
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to access this resource.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        /**
         * Filters whether the current user can access Oxybridge read endpoints.
         *
         * @since 1.0.0
         * @param bool             $allowed Whether access is allowed.
         * @param \WP_REST_Request $request The request object.
         */
        return apply_filters( 'oxybridge_rest_read_permission', true, $request );
    }

    /**
     * Validate post ID.
     *
     * @since 1.0.0
     * @param mixed            $value   The parameter value.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_post_id( $value, \WP_REST_Request $request, $param ) {
        if ( ! is_numeric( $value ) || (int) $value < 1 ) {
            return new \WP_Error(
                'rest_invalid_param',
                /* translators: %s: parameter name */
                sprintf( __( '%s must be a valid positive integer.', 'oxybridge-wp' ), $param ),
                array( 'status' => 400 )
            );
        }

        $post = get_post( (int) $value );
        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        return true;
    }

    /**
     * Get the Oxygen Builder version.
     *
     * @since 1.0.0
     * @return string|null The Oxygen version or null if not detected.
     */
    private function get_oxygen_version() {
        // Check for Oxygen 6 (Breakdance-based).
        if ( defined( '__BREAKDANCE_VERSION' ) && defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return defined( '__BREAKDANCE_VERSION' ) ? __BREAKDANCE_VERSION : null;
        }

        // Check for classic Oxygen.
        if ( defined( 'CT_VERSION' ) ) {
            return CT_VERSION;
        }

        return null;
    }

    /**
     * Get the REST API namespace.
     *
     * @since 1.0.0
     * @return string The namespace.
     */
    public function get_namespace() {
        return self::NAMESPACE;
    }
}
