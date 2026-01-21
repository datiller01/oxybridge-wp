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

        // Single template endpoint - get template details.
        register_rest_route(
            self::NAMESPACE,
            '/templates/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_template' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'id'               => array(
                        'description'       => __( 'Template post ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_template_id' ),
                    ),
                    'include_elements' => array(
                        'description' => __( 'Include parsed element tree in response.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
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

        // Global styles endpoint - read global Oxygen/Breakdance styles.
        register_rest_route(
            self::NAMESPACE,
            '/styles/global',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_global_styles' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'category' => array(
                        'description'       => __( 'Style category to retrieve (colors, fonts, spacing, or all).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'all',
                        'enum'              => array( 'colors', 'fonts', 'spacing', 'all' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'include_variables' => array(
                        'description' => __( 'Include design variables in response.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                    'include_selectors' => array(
                        'description' => __( 'Include CSS class selectors in response.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => false,
                    ),
                ),
            )
        );

        // CSS regeneration endpoint - regenerate CSS cache for a specific post.
        register_rest_route(
            self::NAMESPACE,
            '/regenerate-css/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'regenerate_css' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Post ID to regenerate CSS for.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_oxygen_post_id' ),
                    ),
                ),
            )
        );

        // Create element endpoint - add new element to a document.
        register_rest_route(
            self::NAMESPACE,
            '/documents/(?P<id>\d+)/elements',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_element' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id'           => array(
                        'description'       => __( 'WordPress post/page ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'parent_id'    => array(
                        'description'       => __( 'Parent element ID to append to (use "root" for top-level).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'element_type' => array(
                        'description'       => __( 'Element type (e.g., "EssentialElements\\Section").', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'properties'   => array(
                        'description' => __( 'Element properties object.', 'oxybridge-wp' ),
                        'type'        => 'object',
                        'default'     => array(),
                    ),
                    'position'     => array(
                        'description'       => __( 'Position within parent: "first", "last", or numeric index.', 'oxybridge-wp' ),
                        'type'              => array( 'string', 'integer' ),
                        'default'           => 'last',
                        'sanitize_callback' => array( $this, 'sanitize_position' ),
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
     * Get template details endpoint callback.
     *
     * Returns the full details of a specific Oxygen/Breakdance template
     * including the JSON structure and parsed element tree.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function get_template( \WP_REST_Request $request ) {
        $template_id      = $request->get_param( 'id' );
        $include_elements = $request->get_param( 'include_elements' );

        // Get the template post.
        $post = get_post( $template_id );

        if ( ! $post ) {
            return new \WP_Error(
                'rest_template_not_found',
                __( 'Template not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Verify it's a valid template post type.
        $valid_post_types = $this->get_all_template_post_types();

        if ( ! in_array( $post->post_type, $valid_post_types, true ) ) {
            return new \WP_Error(
                'rest_invalid_template_type',
                __( 'The specified post is not a valid template.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        // Get basic template data using existing format method.
        $template_data = $this->format_template( $post );

        // Get the raw JSON data.
        $json_data = $this->get_template_json( $template_id );
        $template_data['json'] = $json_data;

        // Include parsed element tree if requested.
        if ( $include_elements ) {
            $template_data['elements'] = $this->get_template_element_tree( $template_id );
        }

        // Add additional metadata.
        $template_data['excerpt']      = $post->post_excerpt;
        $template_data['author']       = (int) $post->post_author;
        $template_data['author_name']  = get_the_author_meta( 'display_name', $post->post_author );
        $template_data['conditions']   = $this->get_template_conditions( $template_id );
        $template_data['preview_url']  = get_permalink( $template_id );

        /**
         * Filters the template data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $template_data The template data.
         * @param \WP_Post         $post          The template post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $template_data = apply_filters( 'oxybridge_rest_template_data', $template_data, $post, $request );

        return rest_ensure_response( $template_data );
    }

    /**
     * Get the raw JSON data for a template.
     *
     * @since 1.0.0
     * @param int $template_id The template post ID.
     * @return array|null The parsed JSON data or null if not found.
     */
    private function get_template_json( int $template_id ) {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $tree        = $oxygen_data->get_template_tree( $template_id );

            if ( $tree !== false ) {
                return $tree;
            }
        }

        // Fallback: get directly from post meta.
        $meta_prefix = $this->get_meta_prefix();
        $tree_data   = get_post_meta( $template_id, $meta_prefix . 'data', true );

        if ( ! empty( $tree_data ) ) {
            $decoded = is_string( $tree_data ) ? json_decode( $tree_data, true ) : $tree_data;

            if ( is_array( $decoded ) && isset( $decoded['tree_json_string'] ) ) {
                $tree = json_decode( $decoded['tree_json_string'], true );

                if ( is_array( $tree ) ) {
                    return $tree;
                }
            }

            // If tree_json_string is not present, return the decoded data directly.
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        // Try classic Oxygen meta key as fallback.
        $classic_data = get_post_meta( $template_id, 'ct_builder_json', true );

        if ( ! empty( $classic_data ) ) {
            $decoded = is_string( $classic_data ) ? json_decode( $classic_data, true ) : $classic_data;

            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Get the parsed element tree for a template.
     *
     * @since 1.0.0
     * @param int $template_id The template post ID.
     * @return array The element tree array.
     */
    private function get_template_element_tree( int $template_id ): array {
        $json_data = $this->get_template_json( $template_id );

        if ( empty( $json_data ) ) {
            return array();
        }

        // Check for root/children structure (Breakdance/Oxygen 6 format).
        if ( isset( $json_data['root']['children'] ) ) {
            return $this->parse_element_tree( $json_data['root']['children'] );
        }

        // Check for direct children array.
        if ( isset( $json_data['children'] ) ) {
            return $this->parse_element_tree( $json_data['children'] );
        }

        // If it's a flat array at root level, parse directly.
        if ( is_array( $json_data ) && ! isset( $json_data['root'] ) ) {
            return $this->parse_element_tree( $json_data );
        }

        return array();
    }

    /**
     * Parse an element tree recursively.
     *
     * @since 1.0.0
     * @param array  $children The children array from tree.
     * @param string $parent_path The parent path for hierarchy tracking.
     * @return array Parsed element tree with normalized structure.
     */
    private function parse_element_tree( array $children, string $parent_path = '' ): array {
        $elements = array();

        foreach ( $children as $index => $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            $element_id   = isset( $child['id'] ) ? $child['id'] : 'element-' . $index;
            $element_type = isset( $child['type'] ) ? $child['type'] : 'unknown';
            $current_path = $parent_path ? $parent_path . '/' . $element_id : $element_id;

            $element = array(
                'id'         => $element_id,
                'type'       => $element_type,
                'path'       => $current_path,
                'properties' => $this->extract_element_properties( $child ),
            );

            // Parse nested children recursively.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $element['children'] = $this->parse_element_tree( $child['children'], $current_path );
            } else {
                $element['children'] = array();
            }

            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * Extract relevant properties from an element.
     *
     * @since 1.0.0
     * @param array $element The raw element data.
     * @return array Extracted properties.
     */
    private function extract_element_properties( array $element ): array {
        $properties = array();

        // Common properties to extract.
        $property_keys = array(
            'data',
            'settings',
            'styles',
            'classes',
            'attributes',
            'content',
            'text',
            'html',
            'tag',
            'name',
            'label',
        );

        foreach ( $property_keys as $key ) {
            if ( isset( $element[ $key ] ) ) {
                $properties[ $key ] = $element[ $key ];
            }
        }

        return $properties;
    }

    /**
     * Get template conditions (where it applies).
     *
     * @since 1.0.0
     * @param int $template_id The template post ID.
     * @return array Template conditions.
     */
    private function get_template_conditions( int $template_id ): array {
        $conditions = array();
        $meta_prefix = $this->get_meta_prefix();

        // Check for template assignment conditions.
        $template_conditions = get_post_meta( $template_id, $meta_prefix . 'template_conditions', true );

        if ( ! empty( $template_conditions ) ) {
            $decoded = is_string( $template_conditions ) ? json_decode( $template_conditions, true ) : $template_conditions;

            if ( is_array( $decoded ) ) {
                $conditions['rules'] = $decoded;
            }
        }

        // Check for classic Oxygen template meta.
        $single_all   = get_post_meta( $template_id, 'ct_template_single_all', true );
        $post_types   = get_post_meta( $template_id, 'ct_template_post_types', true );
        $archive_all  = get_post_meta( $template_id, 'ct_template_archive_all', true );

        if ( $single_all ) {
            $conditions['single_all'] = true;
        }

        if ( ! empty( $post_types ) ) {
            $conditions['post_types'] = is_string( $post_types ) ? maybe_unserialize( $post_types ) : $post_types;
        }

        if ( $archive_all ) {
            $conditions['archive_all'] = true;
        }

        // Check for Breakdance fallback meta.
        $fallback_meta = get_post_meta( $template_id, $meta_prefix . 'fallback', true );

        if ( ! empty( $fallback_meta ) ) {
            $conditions['is_fallback'] = (bool) $fallback_meta;
        }

        return $conditions;
    }

    /**
     * Get all valid template post types.
     *
     * @since 1.0.0
     * @return array Array of valid template post types.
     */
    private function get_all_template_post_types(): array {
        return array(
            // Oxygen 6 / Breakdance post types.
            'oxygen_template',
            'oxygen_header',
            'oxygen_footer',
            'oxygen_block',
            'oxygen_part',
            'breakdance_template',
            'breakdance_header',
            'breakdance_footer',
            'breakdance_block',
            'breakdance_popup',
            'breakdance_part',
            // Classic Oxygen post type.
            'ct_template',
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
     * Get global styles endpoint callback.
     *
     * Returns global design tokens including colors, fonts, spacing, and CSS variables
     * from Oxygen/Breakdance global settings.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function get_global_styles( \WP_REST_Request $request ) {
        $category           = $request->get_param( 'category' );
        $include_variables  = $request->get_param( 'include_variables' );
        $include_selectors  = $request->get_param( 'include_selectors' );

        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $styles      = $oxygen_data->get_global_styles( $category );
        } else {
            // Fallback: get styles directly.
            $styles = $this->get_global_styles_fallback( $category );
        }

        // Add variables if requested.
        if ( $include_variables ) {
            $variables = $this->get_design_variables();
            if ( ! empty( $variables ) && ! isset( $styles['variables'] ) ) {
                $styles['variables'] = $variables;
            }
        }

        // Add selectors if requested.
        if ( $include_selectors ) {
            $selectors = $this->get_css_selectors();
            if ( ! empty( $selectors ) ) {
                $styles['selectors'] = $selectors;
            }
        }

        // Add breakpoints information.
        $styles['breakpoints'] = $this->get_breakpoints();

        // Add metadata about the styles source.
        $styles['_meta'] = array(
            'builder'   => $this->get_builder_name(),
            'version'   => $this->get_oxygen_version(),
            'category'  => $category,
            'timestamp' => current_time( 'c' ),
        );

        /**
         * Filters the global styles data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $styles  The global styles data.
         * @param string           $category The requested category.
         * @param \WP_REST_Request $request The request object.
         */
        $styles = apply_filters( 'oxybridge_rest_global_styles', $styles, $category, $request );

        return rest_ensure_response( $styles );
    }

    /**
     * Regenerate CSS cache endpoint callback.
     *
     * Triggers CSS regeneration for a specific post using Oxygen/Breakdance's
     * built-in cache regeneration function.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function regenerate_css( \WP_REST_Request $request ) {
        $post_id = $request->get_param( 'id' );

        // Verify the post exists (already validated by validate_post_id, but double-check).
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Check if the Breakdance render function exists.
        if ( ! function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
            return new \WP_Error(
                'rest_regeneration_unavailable',
                __( 'CSS regeneration function is not available. Oxygen/Breakdance may not be active.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        // Record start time for duration tracking.
        $start_time = microtime( true );

        try {
            // Call Oxygen/Breakdance's built-in cache regeneration function.
            \Breakdance\Render\generateCacheForPost( $post_id );

            $duration = round( ( microtime( true ) - $start_time ) * 1000 );

            /**
             * Fires after CSS cache has been regenerated for a post.
             *
             * @since 1.0.0
             * @param int   $post_id  The post ID.
             * @param float $duration The regeneration duration in milliseconds.
             */
            do_action( 'oxybridge_css_regenerated', $post_id, $duration );

            return rest_ensure_response(
                array(
                    'success'              => true,
                    'post_id'              => $post_id,
                    'message'              => __( 'CSS cache regenerated successfully.', 'oxybridge-wp' ),
                    'regeneration_time_ms' => $duration,
                )
            );
        } catch ( \Exception $e ) {
            return new \WP_Error(
                'rest_regeneration_failed',
                sprintf(
                    /* translators: %s: error message */
                    __( 'CSS regeneration failed: %s', 'oxybridge-wp' ),
                    $e->getMessage()
                ),
                array( 'status' => 500 )
            );
        } catch ( \Error $e ) {
            return new \WP_Error(
                'rest_regeneration_error',
                sprintf(
                    /* translators: %s: error message */
                    __( 'CSS regeneration encountered an error: %s', 'oxybridge-wp' ),
                    $e->getMessage()
                ),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Create element endpoint callback.
     *
     * Creates a new element in the document tree at the specified parent location.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function create_element( \WP_REST_Request $request ) {
        $post_id      = $request->get_param( 'id' );
        $parent_id    = $request->get_param( 'parent_id' );
        $element_type = $request->get_param( 'element_type' );
        $properties   = $request->get_param( 'properties' );
        $position     = $request->get_param( 'position' );

        // Verify the post exists.
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Get the existing document tree.
        $tree = $this->get_document_tree( $post_id );
        if ( $tree === null ) {
            // Initialize empty tree if document doesn't have Oxygen content yet.
            $tree = $this->create_empty_tree();
        }

        // Generate unique element ID.
        $element_id = $this->generate_element_id();

        // Create the new element structure.
        $new_element = array(
            'id'       => $element_id,
            'data'     => array(
                'type'       => $element_type,
                'properties' => is_array( $properties ) ? $properties : array(),
            ),
            'children' => array(),
        );

        /**
         * Filters the new element data before insertion.
         *
         * @since 1.0.0
         * @param array            $new_element  The new element data.
         * @param string           $parent_id    The parent element ID.
         * @param \WP_REST_Request $request      The request object.
         */
        $new_element = apply_filters( 'oxybridge_create_element_data', $new_element, $parent_id, $request );

        // Find parent and insert element.
        $result = $this->insert_element_into_tree( $tree, $parent_id, $new_element, $position );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $tree = $result;

        // Validate tree structure before saving.
        if ( function_exists( '\Breakdance\Data\is_valid_tree' ) ) {
            if ( ! \Breakdance\Data\is_valid_tree( $tree ) ) {
                return new \WP_Error(
                    'rest_invalid_tree',
                    __( 'The resulting tree structure is invalid.', 'oxybridge-wp' ),
                    array( 'status' => 400 )
                );
            }
        }

        // Save the updated tree.
        $save_result = $this->save_document_tree( $post_id, $tree );
        if ( is_wp_error( $save_result ) ) {
            return $save_result;
        }

        // Trigger CSS regeneration.
        if ( function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
            try {
                \Breakdance\Render\generateCacheForPost( $post_id );
            } catch ( \Exception $e ) {
                // Log error but don't fail the request - element was created successfully.
                error_log( 'Oxybridge: CSS regeneration failed after element creation: ' . $e->getMessage() );
            }
        }

        /**
         * Fires after an element has been created.
         *
         * @since 1.0.0
         * @param array  $new_element The created element data.
         * @param int    $post_id     The post ID.
         * @param string $parent_id   The parent element ID.
         */
        do_action( 'oxybridge_element_created', $new_element, $post_id, $parent_id );

        $response = rest_ensure_response(
            array(
                'success'    => true,
                'element'    => array(
                    'id'           => $element_id,
                    'type'         => $element_type,
                    'parent_id'    => $parent_id,
                    'properties'   => $new_element['data']['properties'],
                ),
                'post_id'    => $post_id,
                'message'    => __( 'Element created successfully.', 'oxybridge-wp' ),
            )
        );

        $response->set_status( 201 );

        return $response;
    }

    /**
     * Get the document tree for a post.
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return array|null The document tree or null if not found.
     */
    private function get_document_tree( int $post_id ) {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $tree        = $oxygen_data->get_template_tree( $post_id );

            if ( $tree !== false ) {
                return $tree;
            }
        }

        // Fallback: get directly from post meta.
        $meta_prefix = $this->get_meta_prefix();
        $tree_data   = get_post_meta( $post_id, $meta_prefix . 'data', true );

        if ( ! empty( $tree_data ) ) {
            $decoded = is_string( $tree_data ) ? json_decode( $tree_data, true ) : $tree_data;

            if ( is_array( $decoded ) && isset( $decoded['tree_json_string'] ) ) {
                $tree = json_decode( $decoded['tree_json_string'], true );

                if ( is_array( $tree ) ) {
                    return $tree;
                }
            }

            // If tree_json_string is not present, return the decoded data directly.
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Create an empty document tree structure.
     *
     * @since 1.0.0
     * @return array Empty tree structure.
     */
    private function create_empty_tree(): array {
        return array(
            'root' => array(
                'id'       => 'root',
                'data'     => array(
                    'type'       => 'root',
                    'properties' => array(),
                ),
                'children' => array(),
            ),
        );
    }

    /**
     * Generate a unique element ID.
     *
     * Uses WordPress's UUID generation function for guaranteed uniqueness.
     *
     * @since 1.0.0
     * @return string Unique element ID.
     */
    private function generate_element_id(): string {
        if ( function_exists( 'wp_generate_uuid4' ) ) {
            return wp_generate_uuid4();
        }

        // Fallback for older WordPress versions.
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff )
        );
    }

    /**
     * Insert an element into the tree at the specified parent location.
     *
     * @since 1.0.0
     * @param array        $tree        The document tree.
     * @param string       $parent_id   The parent element ID.
     * @param array        $new_element The new element to insert.
     * @param string|int   $position    Position within parent ("first", "last", or index).
     * @return array|\WP_Error The modified tree or error.
     */
    private function insert_element_into_tree( array $tree, string $parent_id, array $new_element, $position ) {
        // Handle "root" as special case.
        if ( 'root' === $parent_id ) {
            if ( isset( $tree['root'] ) ) {
                $tree['root']['children'] = $this->insert_at_position(
                    $tree['root']['children'] ?? array(),
                    $new_element,
                    $position
                );
                return $tree;
            }

            // Tree might not have root wrapper.
            if ( isset( $tree['children'] ) ) {
                $tree['children'] = $this->insert_at_position(
                    $tree['children'],
                    $new_element,
                    $position
                );
                return $tree;
            }

            return new \WP_Error(
                'rest_invalid_tree_structure',
                __( 'Unable to locate root element in tree structure.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        // Find parent element in tree recursively.
        $modified_tree = $this->find_and_insert_element( $tree, $parent_id, $new_element, $position );

        if ( $modified_tree === false ) {
            return new \WP_Error(
                'rest_parent_not_found',
                sprintf(
                    /* translators: %s: parent element ID */
                    __( 'Parent element with ID "%s" not found in document tree.', 'oxybridge-wp' ),
                    $parent_id
                ),
                array( 'status' => 404 )
            );
        }

        return $modified_tree;
    }

    /**
     * Recursively find parent element and insert new element.
     *
     * @since 1.0.0
     * @param array      $node        Current node in tree.
     * @param string     $parent_id   The parent element ID to find.
     * @param array      $new_element The new element to insert.
     * @param string|int $position    Position within parent.
     * @return array|false Modified node or false if parent not found.
     */
    private function find_and_insert_element( array $node, string $parent_id, array $new_element, $position ) {
        // Check if current node has 'root' wrapper.
        if ( isset( $node['root'] ) ) {
            $result = $this->find_and_insert_element( $node['root'], $parent_id, $new_element, $position );
            if ( $result !== false ) {
                $node['root'] = $result;
                return $node;
            }
            return false;
        }

        // Check if this node is the parent.
        if ( isset( $node['id'] ) && $node['id'] === $parent_id ) {
            $node['children'] = $this->insert_at_position(
                $node['children'] ?? array(),
                $new_element,
                $position
            );
            return $node;
        }

        // Search children.
        if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
            foreach ( $node['children'] as $index => $child ) {
                $result = $this->find_and_insert_element( $child, $parent_id, $new_element, $position );
                if ( $result !== false ) {
                    $node['children'][ $index ] = $result;
                    return $node;
                }
            }
        }

        return false;
    }

    /**
     * Insert element at specified position in children array.
     *
     * @since 1.0.0
     * @param array      $children    The children array.
     * @param array      $element     The element to insert.
     * @param string|int $position    Position ("first", "last", or index).
     * @return array Modified children array.
     */
    private function insert_at_position( array $children, array $element, $position ): array {
        if ( 'first' === $position ) {
            array_unshift( $children, $element );
        } elseif ( 'last' === $position || ! is_numeric( $position ) ) {
            $children[] = $element;
        } else {
            $index = (int) $position;
            // Ensure index is within bounds.
            $index = max( 0, min( $index, count( $children ) ) );
            array_splice( $children, $index, 0, array( $element ) );
        }

        return $children;
    }

    /**
     * Save the document tree to post meta.
     *
     * @since 1.0.0
     * @param int   $post_id The post ID.
     * @param array $tree    The document tree.
     * @return true|\WP_Error True on success or WP_Error on failure.
     */
    private function save_document_tree( int $post_id, array $tree ) {
        $meta_prefix = $this->get_meta_prefix();

        // Encode tree to JSON string.
        $tree_json = wp_json_encode( $tree );

        if ( false === $tree_json ) {
            return new \WP_Error(
                'rest_json_encode_failed',
                __( 'Failed to encode document tree to JSON.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        // Prepare meta data in Breakdance format.
        $meta_data = array(
            'tree_json_string' => $tree_json,
        );

        // Update post meta.
        $result = update_post_meta( $post_id, $meta_prefix . 'data', $meta_data );

        if ( false === $result ) {
            // Check if it's because the value is unchanged.
            $existing = get_post_meta( $post_id, $meta_prefix . 'data', true );
            if ( $existing === $meta_data ) {
                // Value unchanged, treat as success.
                return true;
            }

            return new \WP_Error(
                'rest_meta_update_failed',
                __( 'Failed to save document tree to post meta.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        // Trigger WordPress revision for undo capability.
        wp_update_post(
            array(
                'ID'            => $post_id,
                'post_modified' => current_time( 'mysql' ),
            )
        );

        return true;
    }

    /**
     * Sanitize position parameter.
     *
     * @since 1.0.0
     * @param mixed $value The parameter value.
     * @return string|int Sanitized position value.
     */
    public function sanitize_position( $value ) {
        if ( is_numeric( $value ) ) {
            return absint( $value );
        }

        $value = sanitize_text_field( $value );

        // Only allow specific string values.
        if ( in_array( $value, array( 'first', 'last' ), true ) ) {
            return $value;
        }

        // Default to 'last' for invalid values.
        return 'last';
    }

    /**
     * Fallback method for getting global styles when Oxygen_Data is unavailable.
     *
     * @since 1.0.0
     * @param string $category The style category to retrieve.
     * @return array The global styles data.
     */
    private function get_global_styles_fallback( string $category ): array {
        $meta_prefix     = $this->get_meta_prefix();
        $global_settings = get_option( $meta_prefix . 'global_settings_json_string', '' );

        if ( is_string( $global_settings ) && ! empty( $global_settings ) ) {
            $settings = json_decode( $global_settings, true );
        } else {
            $settings = is_array( $global_settings ) ? $global_settings : array();
        }

        $styles = array(
            'colors'  => array(),
            'fonts'   => array(),
            'spacing' => array(),
            'other'   => array(),
        );

        // Extract color settings.
        if ( isset( $settings['colors'] ) ) {
            $styles['colors'] = $settings['colors'];
        }

        // Extract typography/font settings.
        if ( isset( $settings['typography'] ) ) {
            $styles['fonts'] = $settings['typography'];
        }

        // Extract spacing settings.
        if ( isset( $settings['spacing'] ) ) {
            $styles['spacing'] = $settings['spacing'];
        }

        // Add any other top-level settings.
        $excluded_keys = array( 'colors', 'typography', 'spacing' );
        foreach ( $settings as $key => $value ) {
            if ( ! in_array( $key, $excluded_keys, true ) ) {
                $styles['other'][ $key ] = $value;
            }
        }

        // Return specific category if requested.
        if ( $category !== 'all' && isset( $styles[ $category ] ) ) {
            return array( $category => $styles[ $category ] );
        }

        return $styles;
    }

    /**
     * Get design variables from global settings.
     *
     * @since 1.0.0
     * @return array Design variables.
     */
    private function get_design_variables(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            return $oxygen_data->get_variables();
        }

        // Fallback: get variables directly.
        $meta_prefix = $this->get_meta_prefix();
        $variables   = get_option( $meta_prefix . 'variables_json_string', '' );

        if ( is_string( $variables ) && ! empty( $variables ) ) {
            $decoded = json_decode( $variables, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $variables ) ? $variables : array();
    }

    /**
     * Get CSS selectors/classes from global settings.
     *
     * @since 1.0.0
     * @return array CSS selectors.
     */
    private function get_css_selectors(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            return $oxygen_data->get_selectors();
        }

        // Fallback: get selectors directly.
        $meta_prefix = $this->get_meta_prefix();
        $selectors   = get_option( $meta_prefix . 'breakdance_classes_json_string', '' );

        if ( is_string( $selectors ) && ! empty( $selectors ) ) {
            $decoded = json_decode( $selectors, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $selectors ) ? $selectors : array();
    }

    /**
     * Get responsive breakpoints configuration.
     *
     * Returns the breakpoints used by Oxygen/Breakdance for responsive styling.
     *
     * @since 1.0.0
     * @return array Breakpoint configuration.
     */
    private function get_breakpoints(): array {
        // Try to get breakpoints from Breakdance settings.
        $meta_prefix = $this->get_meta_prefix();
        $breakpoints = get_option( $meta_prefix . 'breakpoints', array() );

        if ( ! empty( $breakpoints ) && is_array( $breakpoints ) ) {
            return $breakpoints;
        }

        // Return default breakpoints based on common Oxygen/Breakdance configuration.
        // These align with the CoLabs design tokens specification.
        return array(
            'base' => array(
                'label' => 'Base (Mobile)',
                'value' => 0,
                'unit'  => 'px',
            ),
            'sm'   => array(
                'label' => 'Small',
                'value' => 640,
                'unit'  => 'px',
            ),
            'md'   => array(
                'label' => 'Medium (Tablet)',
                'value' => 768,
                'unit'  => 'px',
            ),
            'lg'   => array(
                'label' => 'Large (Desktop)',
                'value' => 1024,
                'unit'  => 'px',
            ),
            'xl'   => array(
                'label' => 'Extra Large',
                'value' => 1280,
                'unit'  => 'px',
            ),
            '2xl'  => array(
                'label' => 'Extra Extra Large',
                'value' => 1536,
                'unit'  => 'px',
            ),
        );
    }

    /**
     * Get the current builder name.
     *
     * @since 1.0.0
     * @return string Builder name (oxygen or breakdance).
     */
    private function get_builder_name(): string {
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return 'oxygen';
        }

        if ( defined( '__BREAKDANCE_VERSION' ) ) {
            return 'breakdance';
        }

        return 'unknown';
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
     * Check if user has write permission.
     *
     * Verifies that the current user has sufficient capabilities to perform
     * write operations such as CSS regeneration and element modifications.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if permitted, WP_Error otherwise.
     */
    public function check_write_permission( \WP_REST_Request $request ) {
        // User must be authenticated.
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'Authentication required.', 'oxybridge-wp' ),
                array( 'status' => 401 )
            );
        }

        // Check for edit_posts capability (minimum required for write operations).
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to modify this resource.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        /**
         * Filters whether the current user can access Oxybridge write endpoints.
         *
         * @since 1.0.0
         * @param bool             $allowed Whether access is allowed.
         * @param \WP_REST_Request $request The request object.
         */
        return apply_filters( 'oxybridge_rest_write_permission', true, $request );
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
     * Validate template ID.
     *
     * Validates that the provided value is a valid template post ID.
     *
     * @since 1.0.0
     * @param mixed            $value   The parameter value.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_template_id( $value, \WP_REST_Request $request, $param ) {
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
                'rest_template_not_found',
                __( 'Template not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Validate post type is a valid template type.
        $valid_post_types = $this->get_all_template_post_types();

        if ( ! in_array( $post->post_type, $valid_post_types, true ) ) {
            return new \WP_Error(
                'rest_invalid_template_type',
                __( 'The specified post is not a valid template.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        return true;
    }

    /**
     * Validate Oxygen post ID.
     *
     * Validates that the provided value is a valid post ID AND that the post
     * has Oxygen/Breakdance content (page builder data).
     *
     * @since 1.0.0
     * @param mixed            $value   The parameter value.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_oxygen_post_id( $value, \WP_REST_Request $request, $param ) {
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

        // Check if the post has Oxygen/Breakdance content.
        if ( ! $this->has_oxygen_content( (int) $value ) ) {
            return new \WP_Error(
                'rest_no_oxygen_content',
                __( 'The specified post does not have Oxygen/Breakdance content.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        return true;
    }

    /**
     * Check if a post has Oxygen/Breakdance content.
     *
     * Checks for the presence of builder data in post meta using both
     * modern (Oxygen 6/Breakdance) and classic (Oxygen) meta key formats.
     *
     * @since 1.0.0
     * @param int $post_id The post ID to check.
     * @return bool True if post has Oxygen/Breakdance content, false otherwise.
     */
    private function has_oxygen_content( int $post_id ): bool {
        // Check for modern Oxygen 6 / Breakdance data format.
        $meta_prefix = $this->get_meta_prefix();
        $builder_data = get_post_meta( $post_id, $meta_prefix . 'data', true );

        if ( ! empty( $builder_data ) ) {
            return true;
        }

        // Check for classic Oxygen data format (ct_builder_json).
        $classic_data = get_post_meta( $post_id, 'ct_builder_json', true );

        if ( ! empty( $classic_data ) ) {
            return true;
        }

        // Check for Oxygen shortcodes meta (another classic format).
        $shortcodes = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

        if ( ! empty( $shortcodes ) ) {
            return true;
        }

        return false;
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
