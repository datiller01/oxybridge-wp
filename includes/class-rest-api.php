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
     * Returns a list of Oxygen/Breakdance templates with optional filtering
     * by template type.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function list_templates( \WP_REST_Request $request ) {
        $template_type = $request->get_param( 'template_type' );

        // Get template post types based on filter.
        $post_types = $this->get_template_post_types( $template_type );

        if ( empty( $post_types ) ) {
            return rest_ensure_response(
                array(
                    'templates'      => array(),
                    'total'          => 0,
                    'template_types' => $this->get_available_template_types(),
                )
            );
        }

        // Query templates.
        $query_args = array(
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        $query     = new \WP_Query( $query_args );
        $templates = array();

        foreach ( $query->posts as $post ) {
            $templates[] = $this->format_template( $post );
        }

        return rest_ensure_response(
            array(
                'templates'      => $templates,
                'total'          => count( $templates ),
                'template_types' => $this->get_available_template_types(),
            )
        );
    }

    /**
     * Get template post types based on filter.
     *
     * @since 1.0.0
     * @param string|null $template_type Optional template type filter.
     * @return array Array of post types to query.
     */
    private function get_template_post_types( ?string $template_type = null ): array {
        $post_types = array();

        // Determine available template post types based on builder mode.
        $is_oxygen_mode = defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen';

        if ( $is_oxygen_mode ) {
            $type_map = array(
                'template' => 'oxygen_template',
                'header'   => 'oxygen_header',
                'footer'   => 'oxygen_footer',
                'block'    => 'oxygen_block',
                'part'     => 'oxygen_part',
            );
        } else {
            $type_map = array(
                'template' => 'breakdance_template',
                'header'   => 'breakdance_header',
                'footer'   => 'breakdance_footer',
                'block'    => 'breakdance_block',
                'popup'    => 'breakdance_popup',
                'part'     => 'breakdance_part',
            );
        }

        // If specific type requested, validate and return.
        if ( ! empty( $template_type ) ) {
            $normalized_type = strtolower( $template_type );

            if ( isset( $type_map[ $normalized_type ] ) ) {
                return array( $type_map[ $normalized_type ] );
            }

            // Check if it's a full post type name.
            if ( in_array( $template_type, $type_map, true ) ) {
                return array( $template_type );
            }

            // Invalid type, return empty.
            return array();
        }

        // Return all template post types.
        return array_values( $type_map );
    }

    /**
     * Get available template types for the current builder.
     *
     * @since 1.0.0
     * @return array Array of available template types.
     */
    private function get_available_template_types(): array {
        $is_oxygen_mode = defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen';

        if ( $is_oxygen_mode ) {
            return array(
                array(
                    'slug'      => 'template',
                    'label'     => __( 'Templates', 'oxybridge-wp' ),
                    'post_type' => 'oxygen_template',
                ),
                array(
                    'slug'      => 'header',
                    'label'     => __( 'Headers', 'oxybridge-wp' ),
                    'post_type' => 'oxygen_header',
                ),
                array(
                    'slug'      => 'footer',
                    'label'     => __( 'Footers', 'oxybridge-wp' ),
                    'post_type' => 'oxygen_footer',
                ),
                array(
                    'slug'      => 'block',
                    'label'     => __( 'Global Blocks', 'oxybridge-wp' ),
                    'post_type' => 'oxygen_block',
                ),
                array(
                    'slug'      => 'part',
                    'label'     => __( 'Parts', 'oxybridge-wp' ),
                    'post_type' => 'oxygen_part',
                ),
            );
        }

        return array(
            array(
                'slug'      => 'template',
                'label'     => __( 'Templates', 'oxybridge-wp' ),
                'post_type' => 'breakdance_template',
            ),
            array(
                'slug'      => 'header',
                'label'     => __( 'Headers', 'oxybridge-wp' ),
                'post_type' => 'breakdance_header',
            ),
            array(
                'slug'      => 'footer',
                'label'     => __( 'Footers', 'oxybridge-wp' ),
                'post_type' => 'breakdance_footer',
            ),
            array(
                'slug'      => 'block',
                'label'     => __( 'Global Blocks', 'oxybridge-wp' ),
                'post_type' => 'breakdance_block',
            ),
            array(
                'slug'      => 'popup',
                'label'     => __( 'Popups', 'oxybridge-wp' ),
                'post_type' => 'breakdance_popup',
            ),
            array(
                'slug'      => 'part',
                'label'     => __( 'Parts', 'oxybridge-wp' ),
                'post_type' => 'breakdance_part',
            ),
        );
    }

    /**
     * Format a template post for API response.
     *
     * @since 1.0.0
     * @param \WP_Post $post The template post object.
     * @return array Formatted template data.
     */
    private function format_template( \WP_Post $post ): array {
        $template_type = $this->get_template_type_from_post_type( $post->post_type );
        $element_count = $this->get_template_element_count( $post->ID );

        return array(
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'slug'          => $post->post_name,
            'type'          => $template_type,
            'post_type'     => $post->post_type,
            'status'        => $post->post_status,
            'modified'      => $post->post_modified_gmt . 'Z',
            'created'       => $post->post_date_gmt . 'Z',
            'element_count' => $element_count,
            'edit_url'      => $this->get_template_edit_url( $post->ID ),
        );
    }

    /**
     * Get the template type slug from post type.
     *
     * @since 1.0.0
     * @param string $post_type The WordPress post type.
     * @return string The template type slug.
     */
    private function get_template_type_from_post_type( string $post_type ): string {
        $type_map = array(
            'oxygen_template'     => 'template',
            'oxygen_header'       => 'header',
            'oxygen_footer'       => 'footer',
            'oxygen_block'        => 'block',
            'oxygen_part'         => 'part',
            'breakdance_template' => 'template',
            'breakdance_header'   => 'header',
            'breakdance_footer'   => 'footer',
            'breakdance_block'    => 'block',
            'breakdance_popup'    => 'popup',
            'breakdance_part'     => 'part',
        );

        return isset( $type_map[ $post_type ] ) ? $type_map[ $post_type ] : 'unknown';
    }

    /**
     * Get element count for a template.
     *
     * @since 1.0.0
     * @param int $post_id The template post ID.
     * @return int The number of elements in the template.
     */
    private function get_template_element_count( int $post_id ): int {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $tree        = $oxygen_data->get_template_tree( $post_id );

            if ( $tree !== false && isset( $tree['root']['children'] ) ) {
                return $this->count_elements_recursive( $tree['root']['children'] );
            }
        }

        // Fallback: try to get tree directly from post meta.
        $meta_prefix = $this->get_meta_prefix();
        $tree_data   = get_post_meta( $post_id, $meta_prefix . 'data', true );

        if ( ! empty( $tree_data ) ) {
            $decoded = is_string( $tree_data ) ? json_decode( $tree_data, true ) : $tree_data;

            if ( is_array( $decoded ) && isset( $decoded['tree_json_string'] ) ) {
                $tree = json_decode( $decoded['tree_json_string'], true );

                if ( is_array( $tree ) && isset( $tree['root']['children'] ) ) {
                    return $this->count_elements_recursive( $tree['root']['children'] );
                }
            }
        }

        return 0;
    }

    /**
     * Recursively count elements in a tree structure.
     *
     * @since 1.0.0
     * @param array $children The children array from tree.
     * @return int Total element count.
     */
    private function count_elements_recursive( array $children ): int {
        $count = count( $children );

        foreach ( $children as $child ) {
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $count += $this->count_elements_recursive( $child['children'] );
            }
        }

        return $count;
    }

    /**
     * Get the builder edit URL for a template.
     *
     * @since 1.0.0
     * @param int $post_id The template post ID.
     * @return string The edit URL.
     */
    private function get_template_edit_url( int $post_id ): string {
        // Try to use Breakdance's URL generator if available.
        if ( function_exists( 'Breakdance\Admin\get_builder_loader_url' ) ) {
            return \Breakdance\Admin\get_builder_loader_url( $post_id );
        }

        // Fallback to standard WordPress edit URL with builder parameter.
        $edit_url = get_edit_post_link( $post_id, 'raw' );

        if ( $edit_url ) {
            return add_query_arg( 'breakdance', 'builder', $edit_url );
        }

        return admin_url( 'post.php?post=' . $post_id . '&action=edit&breakdance=builder' );
    }

    /**
     * Get the meta prefix for Oxygen/Breakdance data.
     *
     * @since 1.0.0
     * @return string The meta prefix.
     */
    private function get_meta_prefix(): string {
        // Try to get prefix from Breakdance.
        if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
            $prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox( 'meta_prefix' );
            if ( is_string( $prefix ) ) {
                return $prefix;
            }
        }

        // Check for Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return 'oxygen_';
        }

        // Default to Breakdance prefix.
        return 'breakdance_';
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
