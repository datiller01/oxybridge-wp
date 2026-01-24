<?php
/**
 * REST AI Controller.
 *
 * Handles AI-specific endpoints for context, components, transformation, and validation.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

use Oxybridge\Property_Transformer;

/**
 * AI REST controller.
 *
 * @since 1.1.0
 */
class REST_AI extends REST_Controller {

    /**
     * Cached AI components.
     *
     * @var array|null
     */
    private ?array $components = null;

    /**
     * Register AI routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/ai/docs',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_api_docs' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/context',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_context' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/tokens',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_tokens' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/schema',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_schema' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/components',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_components' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/components/(?P<name>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_component' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'name' => array(
                        'description' => __( 'Component name.', 'oxybridge-wp' ),
                        'type'        => 'string',
                        'required'    => true,
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/transform',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'transform_tree' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'tree' => array(
                        'description' => __( 'Simplified tree to transform.', 'oxybridge-wp' ),
                        'type'        => array( 'object', 'array' ),
                        'required'    => true,
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/ai/validate',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'validate_tree' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'tree' => array(
                        'description' => __( 'Tree to validate.', 'oxybridge-wp' ),
                        'type'        => array( 'object', 'array' ),
                        'required'    => true,
                    ),
                ),
            )
        );
    }

    /**
     * Get API documentation endpoint callback.
     *
     * Returns comprehensive documentation for all API endpoints.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_api_docs( \WP_REST_Request $request ) {
        $base_url = rest_url( $this->get_namespace() );

        return $this->format_response(
            array(
                'version'       => '1.1.0',
                'base_url'      => $base_url,
                'authentication' => $this->get_auth_docs(),
                'endpoints'     => $this->get_endpoint_docs(),
                'data_structures' => $this->get_data_structure_docs(),
                'examples'      => $this->get_example_docs(),
                'notes'         => $this->get_usage_notes(),
            )
        );
    }

    /**
     * Get AI context endpoint callback.
     *
     * Returns everything an AI agent needs to understand the system.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_context( \WP_REST_Request $request ) {
        $components = $this->load_components();

        return $this->format_response(
            array(
                'version'     => '1.0.0',
                'builder'     => $this->get_builder_name(),
                'components'  => array_keys( $components ),
                'breakpoints' => array(
                    'breakpoint_base',
                    'breakpoint_tablet_portrait',
                    'breakpoint_phone_portrait',
                ),
                'element_types' => $this->get_common_element_types(),
                'property_paths' => $this->get_common_property_paths(),
                'notes'       => array(
                    'Text content uses double-nested path: content.content.text',
                    'Element types use EssentialElements\\\\ prefix',
                    'Unit values are objects with number, unit, and style properties',
                ),
            )
        );
    }

    /**
     * Get design tokens endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_tokens( \WP_REST_Request $request ) {
        $settings_controller = new REST_Settings();

        return $this->format_response(
            array(
                'colors'      => $this->get_color_tokens(),
                'fonts'       => $this->get_font_tokens(),
                'breakpoints' => array(
                    'base'            => 'breakpoint_base',
                    'tablet_portrait' => 'breakpoint_tablet_portrait',
                    'phone_portrait'  => 'breakpoint_phone_portrait',
                ),
            )
        );
    }

    /**
     * Get element schema endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_schema( \WP_REST_Request $request ) {
        $schema_file = OXYBRIDGE_PLUGIN_DIR . 'ai/schema-simplified.json';

        if ( file_exists( $schema_file ) ) {
            $schema = json_decode( file_get_contents( $schema_file ), true );

            if ( is_array( $schema ) ) {
                return $this->format_response( $schema );
            }
        }

        // Fallback to basic schema.
        return $this->format_response( $this->get_basic_schema() );
    }

    /**
     * Get components endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_components( \WP_REST_Request $request ) {
        $components = $this->load_components();

        $list = array();
        foreach ( $components as $name => $component ) {
            $list[] = array(
                'name'        => $name,
                'description' => $component['description'] ?? '',
                'category'    => $component['category'] ?? 'general',
            );
        }

        return $this->format_response(
            array(
                'components' => $list,
                'count'      => count( $list ),
            )
        );
    }

    /**
     * Get single component endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_component( \WP_REST_Request $request ) {
        $name = $request->get_param( 'name' );
        $components = $this->load_components();

        if ( ! isset( $components[ $name ] ) ) {
            return $this->format_error(
                'rest_component_not_found',
                __( 'Component not found.', 'oxybridge-wp' ),
                404
            );
        }

        return $this->format_response( $components[ $name ] );
    }

    /**
     * Transform simplified tree endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function transform_tree( \WP_REST_Request $request ) {
        $tree = $request->get_param( 'tree' );

        if ( ! class_exists( 'Oxybridge\Property_Transformer' ) ) {
            return $this->format_error(
                'rest_transformer_unavailable',
                __( 'Property transformer not available.', 'oxybridge-wp' ),
                500
            );
        }

        try {
            $transformer = new Property_Transformer();
            $transformed = $transformer->transform_tree( $tree );

            return $this->format_response(
                array(
                    'success' => true,
                    'tree'    => $transformed,
                )
            );
        } catch ( \Exception $e ) {
            return $this->format_error(
                'rest_transform_failed',
                $e->getMessage(),
                400
            );
        }
    }

    /**
     * Validate tree endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function validate_tree( \WP_REST_Request $request ) {
        $tree = $request->get_param( 'tree' );

        $errors = array();
        $warnings = array();

        // Basic structure validation.
        if ( ! is_array( $tree ) ) {
            $errors[] = array(
                'code'    => 'invalid_type',
                'message' => 'Tree must be an array.',
            );

            return $this->format_response(
                array(
                    'valid'    => false,
                    'errors'   => $errors,
                    'warnings' => $warnings,
                )
            );
        }

        // Check for root.
        if ( ! isset( $tree['root'] ) ) {
            $errors[] = array(
                'code'    => 'missing_root',
                'message' => 'Tree must have a root property.',
            );
        } else {
            // Validate root structure.
            if ( ! isset( $tree['root']['id'] ) ) {
                $errors[] = array(
                    'code'    => 'missing_root_id',
                    'message' => 'Root must have an id.',
                );
            }

            if ( ! isset( $tree['root']['children'] ) ) {
                $errors[] = array(
                    'code'    => 'missing_root_children',
                    'message' => 'Root must have children array.',
                );
            } elseif ( ! is_array( $tree['root']['children'] ) ) {
                $errors[] = array(
                    'code'    => 'invalid_children',
                    'message' => 'Root children must be an array.',
                );
            }
        }

        // Validate _nextNodeId if present.
        if ( isset( $tree['_nextNodeId'] ) && ! is_int( $tree['_nextNodeId'] ) ) {
            $warnings[] = array(
                'code'    => 'invalid_next_node_id',
                'message' => '_nextNodeId should be an integer.',
            );
        }

        $valid = empty( $errors );

        return $this->format_response(
            array(
                'valid'    => $valid,
                'errors'   => $errors,
                'warnings' => $warnings,
            )
        );
    }

    /**
     * Load AI components from JSON file.
     *
     * @since 1.1.0
     * @return array Array of components.
     */
    private function load_components(): array {
        if ( $this->components !== null ) {
            return $this->components;
        }

        $components_file = OXYBRIDGE_PLUGIN_DIR . 'ai/components.json';

        if ( ! file_exists( $components_file ) ) {
            $this->components = array();
            return $this->components;
        }

        $content = file_get_contents( $components_file );
        $components = json_decode( $content, true );

        $this->components = is_array( $components ) ? $components : array();

        return $this->components;
    }

    /**
     * Get common element types.
     *
     * @since 1.1.0
     * @return array Array of element types.
     */
    private function get_common_element_types(): array {
        return array(
            'EssentialElements\\Section',
            'EssentialElements\\Container',
            'EssentialElements\\Div',
            'EssentialElements\\Heading',
            'EssentialElements\\Text',
            'EssentialElements\\Button',
            'EssentialElements\\Image',
            'EssentialElements\\Icon',
            'EssentialElements\\Link',
            'EssentialElements\\Columns',
            'EssentialElements\\Column',
        );
    }

    /**
     * Get common property paths.
     *
     * @since 1.1.0
     * @return array Property path reference.
     */
    private function get_common_property_paths(): array {
        return array(
            'text'       => 'content.content.text',
            'background' => 'design.background',
            'padding'    => 'design.spacing.padding',
            'margin'     => 'design.spacing.margin',
            'typography' => 'design.typography',
            'border'     => 'design.border',
        );
    }

    /**
     * Get color tokens from global settings.
     *
     * @since 1.1.0
     * @return array Color tokens.
     */
    private function get_color_tokens(): array {
        $option_prefix = $this->get_option_prefix();
        $settings_json = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( empty( $settings_json ) ) {
            return array();
        }

        $settings = json_decode( $settings_json, true );

        if ( ! isset( $settings['colorPalette'] ) ) {
            return array();
        }

        $tokens = array();
        foreach ( $settings['colorPalette'] as $color ) {
            if ( isset( $color['name'] ) && isset( $color['value'] ) ) {
                $key = strtolower( str_replace( ' ', '_', $color['name'] ) );
                $tokens[ $key ] = $color['value'];
            }
        }

        return $tokens;
    }

    /**
     * Get font tokens from global settings.
     *
     * @since 1.1.0
     * @return array Font tokens.
     */
    private function get_font_tokens(): array {
        $option_prefix = $this->get_option_prefix();
        $settings_json = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( empty( $settings_json ) ) {
            return array();
        }

        $settings = json_decode( $settings_json, true );

        if ( ! isset( $settings['typography']['fonts'] ) ) {
            return array();
        }

        $tokens = array();
        foreach ( $settings['typography']['fonts'] as $index => $font ) {
            $key = $index === 0 ? 'primary' : 'font_' . $index;
            $tokens[ $key ] = $font['family'] ?? '';
        }

        return $tokens;
    }

    /**
     * Get basic schema for fallback.
     *
     * @since 1.1.0
     * @return array Basic schema.
     */
    private function get_basic_schema(): array {
        return array(
            'version' => '1.0.0',
            'elements' => array(
                'Section' => array(
                    'type' => 'EssentialElements\\Section',
                    'properties' => array( 'design', 'settings' ),
                ),
                'Heading' => array(
                    'type' => 'EssentialElements\\Heading',
                    'properties' => array( 'content', 'design' ),
                ),
                'Text' => array(
                    'type' => 'EssentialElements\\Text',
                    'properties' => array( 'content', 'design' ),
                ),
                'Button' => array(
                    'type' => 'EssentialElements\\Button',
                    'properties' => array( 'content', 'design' ),
                ),
            ),
        );
    }

    /**
     * Get authentication documentation.
     *
     * @since 1.1.0
     * @return array Authentication docs.
     */
    private function get_auth_docs(): array {
        return array(
            'description' => 'Most endpoints require WordPress authentication. Use cookie-based auth or Application Passwords.',
            'methods'     => array(
                array(
                    'name'        => 'Cookie Authentication',
                    'description' => 'For browser-based requests. Requires valid WordPress login session and nonce.',
                    'header'      => 'X-WP-Nonce: {nonce}',
                ),
                array(
                    'name'        => 'Application Passwords',
                    'description' => 'For external API access. Generate in WordPress user profile.',
                    'header'      => 'Authorization: Basic {base64(username:app_password)}',
                ),
            ),
            'public_endpoints' => array(
                'GET /health',
                'GET /info',
                'GET /ai/docs',
                'GET /ai/context',
                'GET /ai/tokens',
                'GET /ai/schema',
                'GET /ai/components',
                'GET /template-types',
                'GET /colors',
                'GET /fonts',
                'GET /variables',
                'GET /breakpoints',
            ),
        );
    }

    /**
     * Get endpoint documentation.
     *
     * @since 1.1.0
     * @return array Endpoint docs.
     */
    private function get_endpoint_docs(): array {
        return array(
            'core'      => $this->get_core_endpoint_docs(),
            'documents' => $this->get_document_endpoint_docs(),
            'pages'     => $this->get_page_endpoint_docs(),
            'templates' => $this->get_template_endpoint_docs(),
            'settings'  => $this->get_settings_endpoint_docs(),
            'ai'        => $this->get_ai_endpoint_docs(),
        );
    }

    /**
     * Get core endpoint documentation.
     *
     * @since 1.1.0
     * @return array Core endpoint docs.
     */
    private function get_core_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /health',
                'description' => 'Health check endpoint. Returns server status.',
                'auth'        => false,
                'response'    => array(
                    'status'    => 'string (ok)',
                    'timestamp' => 'string (ISO 8601)',
                    'builder'   => 'string (Oxygen|Breakdance)',
                    'version'   => 'string|null',
                ),
            ),
            array(
                'endpoint'    => 'GET /info',
                'description' => 'Plugin and builder information.',
                'auth'        => false,
                'response'    => array(
                    'plugin'         => 'string',
                    'version'        => 'string',
                    'builder'        => 'string',
                    'builder_version' => 'string|null',
                    'php_version'    => 'string',
                    'wp_version'     => 'string',
                    'endpoints'      => 'object (endpoint => description)',
                    'capabilities'   => 'object',
                ),
            ),
            array(
                'endpoint'    => 'POST /authenticate',
                'description' => 'Verify authentication and get user info.',
                'auth'        => true,
                'response'    => array(
                    'authenticated' => 'boolean',
                    'user_id'       => 'integer',
                    'user_login'    => 'string',
                    'display_name'  => 'string',
                    'capabilities'  => 'object',
                ),
            ),
        );
    }

    /**
     * Get document endpoint documentation.
     *
     * @since 1.1.0
     * @return array Document endpoint docs.
     */
    private function get_document_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /documents/{id}',
                'description' => 'Read Oxygen/Breakdance document tree for a post.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'WordPress post ID',
                    ),
                    'include_metadata' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => true,
                        'description' => 'Include post metadata in response',
                    ),
                    'flatten_elements' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => false,
                        'description' => 'Return flat array instead of tree',
                    ),
                ),
                'response'    => array(
                    'post_id'       => 'integer',
                    'tree'          => 'object (document tree structure)',
                    'metadata'      => 'object (if include_metadata=true)',
                    'element_count' => 'integer',
                    'element_types' => 'array of strings',
                ),
            ),
            array(
                'endpoint'    => 'POST /documents/{id}',
                'description' => 'Update document tree for a post.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'WordPress post ID',
                    ),
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => true,
                        'description' => 'Complete document tree structure',
                    ),
                    'regenerate_css' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => true,
                        'description' => 'Regenerate CSS cache after save',
                    ),
                ),
                'response'    => array(
                    'success'       => 'boolean',
                    'post_id'       => 'integer',
                    'tree'          => 'object',
                    'element_count' => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'POST /regenerate-css/{id}',
                'description' => 'Regenerate CSS cache for a post.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'WordPress post ID',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'post_id' => 'integer',
                    'message' => 'string',
                ),
            ),
        );
    }

    /**
     * Get page endpoint documentation.
     *
     * @since 1.1.0
     * @return array Page endpoint docs.
     */
    private function get_page_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /pages',
                'description' => 'List pages with optional filtering.',
                'auth'        => true,
                'parameters'  => array(
                    'post_type' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'page',
                        'description' => 'Filter by post type (page, post, any)',
                    ),
                    'has_oxygen_content' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => false,
                        'description' => 'Only return posts with Oxygen content',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'publish',
                        'description' => 'Filter by post status',
                    ),
                    'search' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'description' => 'Search by title',
                    ),
                    'per_page' => array(
                        'type'        => 'integer',
                        'required'    => false,
                        'default'     => 20,
                        'description' => 'Results per page (max 100)',
                    ),
                    'page' => array(
                        'type'        => 'integer',
                        'required'    => false,
                        'default'     => 1,
                        'description' => 'Page number',
                    ),
                ),
                'response'    => array(
                    'pages'       => 'array of page objects',
                    'total'       => 'integer',
                    'total_pages' => 'integer',
                    'page'        => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'POST /pages',
                'description' => 'Create a new page with optional Oxygen tree.',
                'auth'        => true,
                'parameters'  => array(
                    'title' => array(
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Page title',
                    ),
                    'post_type' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'page',
                        'description' => 'Post type',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'draft',
                        'description' => 'Initial post status',
                    ),
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => false,
                        'description' => 'Initial document tree',
                    ),
                    'enable_oxygen' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => true,
                        'description' => 'Enable Oxygen for this page',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'page'    => 'object (page data)',
                ),
            ),
            array(
                'endpoint'    => 'GET /pages/{id}',
                'description' => 'Get a single page.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Page ID',
                    ),
                ),
                'response'    => array(
                    'id'                 => 'integer',
                    'title'              => 'string',
                    'post_type'          => 'string',
                    'status'             => 'string',
                    'permalink'          => 'string (URL)',
                    'edit_url'           => 'string (builder URL)',
                    'modified_at'        => 'string (datetime)',
                    'author'             => 'string',
                    'has_oxygen_content' => 'boolean',
                    'element_count'      => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'PUT /pages/{id}',
                'description' => 'Update a page.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Page ID',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'description' => 'New title',
                    ),
                    'status' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'description' => 'New status',
                    ),
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => false,
                        'description' => 'Updated document tree',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'page'    => 'object (updated page data)',
                ),
            ),
            array(
                'endpoint'    => 'DELETE /pages/{id}',
                'description' => 'Delete a page.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Page ID',
                    ),
                    'force' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => false,
                        'description' => 'Permanently delete instead of trash',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'deleted' => 'integer (post ID)',
                    'trashed' => 'boolean',
                ),
            ),
        );
    }

    /**
     * Get template endpoint documentation.
     *
     * @since 1.1.0
     * @return array Template endpoint docs.
     */
    private function get_template_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /templates',
                'description' => 'List all templates (headers, footers, global blocks, etc.).',
                'auth'        => true,
                'parameters'  => array(
                    'type' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'description' => 'Filter by type: header, footer, global_block, popup, template',
                    ),
                    'per_page' => array(
                        'type'        => 'integer',
                        'required'    => false,
                        'default'     => 50,
                        'description' => 'Results per page',
                    ),
                ),
                'response'    => array(
                    'templates' => 'array of template objects',
                    'total'     => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'POST /templates',
                'description' => 'Create a new template.',
                'auth'        => true,
                'parameters'  => array(
                    'title' => array(
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Template title',
                    ),
                    'type' => array(
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Template type: header, footer, global_block, popup, template',
                    ),
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => false,
                        'description' => 'Initial document tree',
                    ),
                ),
                'response'    => array(
                    'success'  => 'boolean',
                    'template' => 'object (template data)',
                ),
            ),
            array(
                'endpoint'    => 'GET /templates/{id}',
                'description' => 'Get a single template with its tree.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Template ID',
                    ),
                ),
                'response'    => array(
                    'id'            => 'integer',
                    'title'         => 'string',
                    'type'          => 'string',
                    'post_type'     => 'string',
                    'status'        => 'string',
                    'edit_url'      => 'string',
                    'modified_at'   => 'string',
                    'element_count' => 'integer',
                    'tree'          => 'object (document tree)',
                ),
            ),
            array(
                'endpoint'    => 'PUT /templates/{id}',
                'description' => 'Update a template.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Template ID',
                    ),
                    'title' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'description' => 'New title',
                    ),
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => false,
                        'description' => 'Updated document tree',
                    ),
                ),
                'response'    => array(
                    'success'  => 'boolean',
                    'template' => 'object',
                ),
            ),
            array(
                'endpoint'    => 'DELETE /templates/{id}',
                'description' => 'Delete a template.',
                'auth'        => true,
                'parameters'  => array(
                    'id' => array(
                        'type'        => 'integer',
                        'required'    => true,
                        'description' => 'Template ID',
                    ),
                    'force' => array(
                        'type'        => 'boolean',
                        'required'    => false,
                        'default'     => false,
                        'description' => 'Permanently delete',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'deleted' => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /template-types',
                'description' => 'List available template types.',
                'auth'        => false,
                'response'    => array(
                    'types' => 'array of {slug, name, description}',
                ),
            ),
        );
    }

    /**
     * Get settings endpoint documentation.
     *
     * @since 1.1.0
     * @return array Settings endpoint docs.
     */
    private function get_settings_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /colors',
                'description' => 'Get global color palette.',
                'auth'        => false,
                'response'    => array(
                    'colors' => 'array of {name, value, variable}',
                    'count'  => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /fonts',
                'description' => 'Get global font settings.',
                'auth'        => false,
                'response'    => array(
                    'fonts' => 'array of {family, fallback, weights, source}',
                    'count' => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /variables',
                'description' => 'Get CSS variables.',
                'auth'        => false,
                'response'    => array(
                    'variables' => 'array of {name, value}',
                    'count'     => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /breakpoints',
                'description' => 'Get responsive breakpoints.',
                'auth'        => false,
                'response'    => array(
                    'breakpoints' => 'array of {id, label, minWidth, maxWidth, default}',
                    'count'       => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /global-styles',
                'description' => 'Get all global styles or filtered by category.',
                'auth'        => false,
                'parameters'  => array(
                    'category' => array(
                        'type'        => 'string',
                        'required'    => false,
                        'default'     => 'all',
                        'description' => 'Filter: all, colors, fonts, spacing, breakpoints',
                    ),
                ),
                'response'    => array(
                    'colors'      => 'array (if category includes colors)',
                    'fonts'       => 'array (if category includes fonts)',
                    'breakpoints' => 'array (if category includes breakpoints)',
                    'spacing'     => 'object (if category includes spacing)',
                ),
            ),
            array(
                'endpoint'    => 'GET /css-classes',
                'description' => 'Get custom CSS classes.',
                'auth'        => false,
                'response'    => array(
                    'classes' => 'array of {id, name, styles}',
                    'count'   => 'integer',
                ),
            ),
        );
    }

    /**
     * Get AI endpoint documentation.
     *
     * @since 1.1.0
     * @return array AI endpoint docs.
     */
    private function get_ai_endpoint_docs(): array {
        return array(
            array(
                'endpoint'    => 'GET /ai/docs',
                'description' => 'This endpoint. Complete API documentation.',
                'auth'        => false,
            ),
            array(
                'endpoint'    => 'GET /ai/context',
                'description' => 'Get AI context including element types and property paths.',
                'auth'        => false,
                'response'    => array(
                    'version'        => 'string',
                    'builder'        => 'string',
                    'components'     => 'array of component names',
                    'breakpoints'    => 'array of breakpoint IDs',
                    'element_types'  => 'array of element type strings',
                    'property_paths' => 'object mapping property names to paths',
                    'notes'          => 'array of usage notes',
                ),
            ),
            array(
                'endpoint'    => 'GET /ai/tokens',
                'description' => 'Get design tokens (colors, fonts, breakpoints).',
                'auth'        => false,
                'response'    => array(
                    'colors'      => 'object (token_name => hex_value)',
                    'fonts'       => 'object (token_name => font_family)',
                    'breakpoints' => 'object (name => breakpoint_id)',
                ),
            ),
            array(
                'endpoint'    => 'GET /ai/schema',
                'description' => 'Get element schema definitions.',
                'auth'        => false,
                'response'    => array(
                    'version'  => 'string',
                    'elements' => 'object of element definitions',
                ),
            ),
            array(
                'endpoint'    => 'GET /ai/components',
                'description' => 'List available AI components.',
                'auth'        => false,
                'response'    => array(
                    'components' => 'array of {name, description, category}',
                    'count'      => 'integer',
                ),
            ),
            array(
                'endpoint'    => 'GET /ai/components/{name}',
                'description' => 'Get a single component definition.',
                'auth'        => false,
                'parameters'  => array(
                    'name' => array(
                        'type'        => 'string',
                        'required'    => true,
                        'description' => 'Component name',
                    ),
                ),
                'response'    => 'Component object with full definition',
            ),
            array(
                'endpoint'    => 'POST /ai/transform',
                'description' => 'Transform simplified tree to Oxygen format.',
                'auth'        => true,
                'parameters'  => array(
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => true,
                        'description' => 'Simplified tree structure',
                    ),
                ),
                'response'    => array(
                    'success' => 'boolean',
                    'tree'    => 'object (transformed Oxygen tree)',
                ),
            ),
            array(
                'endpoint'    => 'POST /ai/validate',
                'description' => 'Validate a document tree structure.',
                'auth'        => true,
                'parameters'  => array(
                    'tree' => array(
                        'type'        => 'object',
                        'required'    => true,
                        'description' => 'Tree to validate',
                    ),
                ),
                'response'    => array(
                    'valid'    => 'boolean',
                    'errors'   => 'array of {code, message}',
                    'warnings' => 'array of {code, message}',
                ),
            ),
        );
    }

    /**
     * Get data structure documentation.
     *
     * @since 1.1.0
     * @return array Data structure docs.
     */
    private function get_data_structure_docs(): array {
        return array(
            'document_tree' => array(
                'description' => 'The main structure for storing page/template content. All properties are REQUIRED for Oxygen Builder compatibility.',
                'required_properties' => array(
                    'root'                => 'REQUIRED - Root container element object',
                    'root.id'             => 'REQUIRED - Unique string identifier for root',
                    'root.data'           => 'REQUIRED - Element data object',
                    'root.data.type'      => 'REQUIRED - Must be "EssentialElements\\\\Root"',
                    'root.data.properties' => 'REQUIRED - Properties object (can be empty {})',
                    'root.children'       => 'REQUIRED - Array of child elements',
                    '_nextNodeId'         => 'REQUIRED - Integer, next available ID for new elements. Must be higher than any existing numeric ID.',
                    'exportedLookupTable' => 'REQUIRED - Object for class lookups. Use empty object {} if none.',
                ),
                'structure'   => array(
                    'root' => array(
                        'id'       => 'string (unique element ID, e.g., "root" or "el-abc123")',
                        'data'     => array(
                            'type'       => 'string (must be "EssentialElements\\\\Root" for root)',
                            'properties' => 'object (root properties, usually empty {})',
                        ),
                        'children' => 'array (child elements)',
                    ),
                    '_nextNodeId'         => 'integer (REQUIRED - next available ID, typically max(existing_ids) + 1)',
                    'exportedLookupTable' => 'object (REQUIRED - class lookups, use {} if empty)',
                ),
                'validation_note' => 'Oxygen Builder uses io-ts for runtime validation. Missing required properties will cause "IO-TS validation failed" errors.',
            ),
            'element' => array(
                'description' => 'Individual element within the tree. All elements must have id, data, and children properties.',
                'required_properties' => array(
                    'id'              => 'REQUIRED - Unique string identifier',
                    'data'            => 'REQUIRED - Element data object',
                    'data.type'       => 'REQUIRED - Element type string with namespace',
                    'data.properties' => 'REQUIRED - Properties object (can be empty {})',
                    'children'        => 'REQUIRED - Array of child elements (empty [] for leaf nodes)',
                ),
                'structure'   => array(
                    'id'       => 'string (unique, e.g., "el-a1b2c3d4")',
                    'data'     => array(
                        'type'       => 'string (e.g., "EssentialElements\\\\Heading")',
                        'properties' => 'object (element-specific properties)',
                    ),
                    'children' => 'array (child elements, use empty [] for leaf nodes)',
                ),
            ),
            'element_properties' => array(
                'description' => 'Common property structure for elements.',
                'paths'       => array(
                    'content.content.text'     => 'Text content for text elements',
                    'design.background'        => 'Background styles',
                    'design.spacing.padding'   => 'Padding values',
                    'design.spacing.margin'    => 'Margin values',
                    'design.typography'        => 'Typography settings',
                    'design.border'            => 'Border styles',
                    'design.effects'           => 'Box shadow, opacity, etc.',
                    'design.size.width'        => 'Element width',
                    'design.size.height'       => 'Element height',
                    'design.layout'            => 'Flexbox/grid layout settings',
                ),
            ),
            'unit_value' => array(
                'description' => 'Structure for values with units (padding, margins, etc.).',
                'structure'   => array(
                    'number' => 'number (the numeric value)',
                    'unit'   => 'string (px, em, rem, %, vw, vh)',
                    'style'  => 'string (usually "solid" or empty)',
                ),
                'example'     => array(
                    'number' => 20,
                    'unit'   => 'px',
                    'style'  => '',
                ),
            ),
            'breakpoint_values' => array(
                'description' => 'Responsive values keyed by breakpoint.',
                'structure'   => array(
                    'breakpoint_base'            => 'value for desktop',
                    'breakpoint_tablet_portrait' => 'value for tablet',
                    'breakpoint_phone_portrait'  => 'value for mobile',
                ),
            ),
        );
    }

    /**
     * Get example documentation.
     *
     * @since 1.1.0
     * @return array Example docs.
     */
    private function get_example_docs(): array {
        return array(
            'minimal_tree' => array(
                'description' => 'Minimal valid document tree with a section and heading.',
                'tree'        => array(
                    'root' => array(
                        'id'       => 'root',
                        'data'     => array(
                            'type'       => 'EssentialElements\\Root',
                            'properties' => array(),
                        ),
                        'children' => array(
                            array(
                                'id'       => 'el-section1',
                                'data'     => array(
                                    'type'       => 'EssentialElements\\Section',
                                    'properties' => array(
                                        'design' => array(
                                            'spacing' => array(
                                                'padding' => array(
                                                    'breakpoint_base' => array(
                                                        'top'    => array( 'number' => 40, 'unit' => 'px', 'style' => '' ),
                                                        'bottom' => array( 'number' => 40, 'unit' => 'px', 'style' => '' ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                                'children' => array(
                                    array(
                                        'id'       => 'el-heading1',
                                        'data'     => array(
                                            'type'       => 'EssentialElements\\Heading',
                                            'properties' => array(
                                                'content' => array(
                                                    'content' => array(
                                                        'text' => 'Hello World',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'children' => array(),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    '_nextNodeId'         => 3,
                    'exportedLookupTable' => new \stdClass(),
                ),
            ),
            'create_page_request' => array(
                'description' => 'Example request to create a page with initial content.',
                'method'      => 'POST',
                'endpoint'    => '/pages',
                'body'        => array(
                    'title'         => 'My New Page',
                    'status'        => 'draft',
                    'enable_oxygen' => true,
                    'tree'          => '(optional document tree)',
                ),
            ),
            'update_document_request' => array(
                'description' => 'Example request to update page content.',
                'method'      => 'POST',
                'endpoint'    => '/documents/123',
                'body'        => array(
                    'tree'           => '(complete document tree)',
                    'regenerate_css' => true,
                ),
            ),
        );
    }

    /**
     * Get usage notes.
     *
     * @since 1.1.0
     * @return array Usage notes.
     */
    private function get_usage_notes(): array {
        return array(
            'critical_structure'  => 'CRITICAL: Every document tree MUST have: root (with id, data, children), _nextNodeId (integer), and exportedLookupTable (object, use {} if empty). Missing any of these causes validation errors.',
            'root_element'        => 'The root element MUST have: id (string), data.type ("EssentialElements\\\\Root"), data.properties (object), and children (array).',
            'element_structure'   => 'Every element MUST have: id (unique string), data.type (string), data.properties (object), and children (array, use [] for leaf nodes).',
            'element_types'       => 'All element types use the EssentialElements\\\\ prefix (e.g., EssentialElements\\\\Heading, EssentialElements\\\\Section)',
            'text_content'        => 'Text content uses double-nested path: content.content.text',
            'unit_values'         => 'Numeric values with units must be objects: {"number": 20, "unit": "px", "style": ""}',
            'breakpoints'         => 'Use breakpoint_base for desktop, breakpoint_tablet_portrait for tablet, breakpoint_phone_portrait for mobile',
            'next_node_id'        => '_nextNodeId must be an integer greater than the highest numeric ID in the tree. Calculate as max(all_numeric_ids) + 1.',
            'element_ids'         => 'Element IDs should be unique strings, typically "el-" followed by random characters (e.g., "el-a1b2c3d4")',
            'empty_children'      => 'Leaf elements MUST have children: [] - do not omit the property',
            'empty_properties'    => 'Elements with no custom properties MUST still have data.properties: {} - do not omit',
            'css_regeneration'    => 'After updating document trees, CSS should be regenerated for changes to appear on frontend',
            'authentication'      => 'Write operations require authentication; most read operations are public',
            'workflow'            => 'Typical workflow: 1) GET /pages to find page, 2) GET /documents/{id} to read tree, 3) POST /documents/{id} to update, 4) Changes appear on frontend',
        );
    }
}
