<?php
/**
 * REST API class for Oxybridge.
 *
 * Handles registration of custom REST API endpoints for Oxybridge.
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
 * Registers and manages custom REST API endpoints for Oxybridge.
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
     * Registers all custom endpoints for Oxybridge.
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

        // Info endpoint - returns plugin version, Oxygen version, and capabilities.
        register_rest_route(
            self::NAMESPACE,
            '/info',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_plugin_info' ),
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

        // Page/Post creation endpoint - create new page with Oxygen design.
        register_rest_route(
            self::NAMESPACE,
            '/pages',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_page' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'title'     => array(
                        'description'       => __( 'Page title.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status'    => array(
                        'description'       => __( 'Page status (draft, publish, pending, private).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'draft',
                        'enum'              => array( 'draft', 'publish', 'pending', 'private' ),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'post_type' => array(
                        'description'       => __( 'Post type (page, post, or custom).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'page',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'slug'      => array(
                        'description'       => __( 'Page slug (post_name).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'content'   => array(
                        'description'       => __( 'Page content (fallback content for non-Oxygen display).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ),
                    'parent'    => array(
                        'description'       => __( 'Parent page ID for hierarchical post types.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'default'           => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'tree'      => array(
                        'description' => __( 'Oxygen/Breakdance design tree JSON structure.', 'oxybridge-wp' ),
                        'type'        => 'object',
                    ),
                    'use_simplified' => array(
                        'description' => __( 'Set true if tree uses simplified format (will be auto-transformed to Breakdance format).', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => false,
                    ),
                    'enable_oxygen' => array(
                        'description' => __( 'Enable Oxygen builder for this page (creates empty tree if tree not provided).', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                    'template'  => array(
                        'description'       => __( 'Page template file or blank for default.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
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

        // Template creation endpoint - create new Oxygen/Breakdance template.
        register_rest_route(
            self::NAMESPACE,
            '/templates',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_template' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'title'         => array(
                        'description'       => __( 'Template title.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'template_type' => array(
                        'description'       => __( 'Template type (header, footer, template, block, part, or popup for Breakdance).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'status'        => array(
                        'description'       => __( 'Template status (draft, publish, pending, private).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'publish',
                        'enum'              => array( 'draft', 'publish', 'pending', 'private' ),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'slug'          => array(
                        'description'       => __( 'Template slug (post_name).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'tree'          => array(
                        'description' => __( 'Oxygen/Breakdance design tree JSON structure.', 'oxybridge-wp' ),
                        'type'        => 'object',
                    ),
                    'enable_oxygen' => array(
                        'description' => __( 'Enable Oxygen builder for this template (creates empty tree if tree not provided).', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
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

        // Bulk CSS regeneration endpoint - regenerate CSS cache for all posts.
        register_rest_route(
            self::NAMESPACE,
            '/regenerate-css',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'regenerate_all_css' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'post_type' => array(
                        'description'       => __( 'Filter regeneration to specific post type (optional).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'batch_size' => array(
                        'description'       => __( 'Number of posts to process per batch (default 50, max 200).', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'default'           => 50,
                        'minimum'           => 1,
                        'maximum'           => 200,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Breakpoints endpoint - returns responsive breakpoint definitions.
        register_rest_route(
            self::NAMESPACE,
            '/breakpoints',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_responsive_breakpoints' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Colors endpoint - returns global color palette.
        register_rest_route(
            self::NAMESPACE,
            '/colors',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_global_colors' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Variables endpoint - returns CSS design variables.
        register_rest_route(
            self::NAMESPACE,
            '/variables',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_design_variables' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Fonts endpoint - returns available fonts list.
        register_rest_route(
            self::NAMESPACE,
            '/fonts',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_available_fonts' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Classes endpoint - returns global CSS classes/selectors.
        register_rest_route(
            self::NAMESPACE,
            '/classes',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_global_classes' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
            )
        );

        // Clone endpoint - clone an existing page or template.
        register_rest_route(
            self::NAMESPACE,
            '/clone/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'clone_page' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id'        => array(
                        'description'       => __( 'ID of the post/page/template to clone.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'title'     => array(
                        'description'       => __( 'Title for the cloned page (defaults to "Copy of [original title]").', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status'    => array(
                        'description'       => __( 'Status for the cloned page (draft, publish, pending, private).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'default'           => 'draft',
                        'enum'              => array( 'draft', 'publish', 'pending', 'private' ),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'slug'      => array(
                        'description'       => __( 'Slug for the cloned page (auto-generated if not provided).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                ),
            )
        );

        // Validate endpoint - validate JSON structure without saving.
        register_rest_route(
            self::NAMESPACE,
            '/validate',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'validate_tree' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'tree' => array(
                        'description' => __( 'Oxygen/Breakdance design tree JSON structure to validate.', 'oxybridge-wp' ),
                        'type'        => 'object',
                        'required'    => true,
                    ),
                ),
            )
        );

        // Render endpoint - render design to HTML.
        register_rest_route(
            self::NAMESPACE,
            '/render/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'render_design' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'id'          => array(
                        'description'       => __( 'Post ID to render.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'include_css' => array(
                        'description' => __( 'Include inline CSS in the rendered output.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => false,
                    ),
                    'include_wrapper' => array(
                        'description' => __( 'Wrap output in a container div with metadata attributes.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                ),
            )
        );

        // =================================================================
        // AI Agent Endpoints
        // =================================================================

        // AI context endpoint - returns everything an AI agent needs.
        register_rest_route(
            self::NAMESPACE,
            '/ai/context',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_context' ),
                'permission_callback' => '__return_true',
            )
        );

        // AI tokens endpoint - returns design tokens (colors, fonts, spacing).
        register_rest_route(
            self::NAMESPACE,
            '/ai/tokens',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_tokens' ),
                'permission_callback' => '__return_true',
            )
        );

        // AI schema endpoint - returns compact element schema.
        register_rest_route(
            self::NAMESPACE,
            '/ai/schema',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_schema' ),
                'permission_callback' => '__return_true',
            )
        );

        // AI components endpoint - returns available component snippets.
        register_rest_route(
            self::NAMESPACE,
            '/ai/components',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_components' ),
                'permission_callback' => '__return_true',
            )
        );

        // AI single component endpoint - returns specific component snippet.
        register_rest_route(
            self::NAMESPACE,
            '/ai/components/(?P<name>[a-z0-9_-]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_component' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'name' => array(
                        'description'       => __( 'Component name (e.g., hero_section).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            )
        );

        // AI templates endpoint - list saved AI templates.
        register_rest_route(
            self::NAMESPACE,
            '/ai/templates',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_ai_templates' ),
                    'permission_callback' => '__return_true',
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_ai_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'name'        => array(
                            'description'       => __( 'Template name.', 'oxybridge-wp' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                        'description' => array(
                            'description'       => __( 'Template description.', 'oxybridge-wp' ),
                            'type'              => 'string',
                            'default'           => '',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'tree'        => array(
                            'description' => __( 'The element tree structure.', 'oxybridge-wp' ),
                            'type'        => 'object',
                            'required'    => true,
                        ),
                        'tags'        => array(
                            'description'       => __( 'Template tags for categorization.', 'oxybridge-wp' ),
                            'type'              => 'array',
                            'default'           => array(),
                            'sanitize_callback' => function( $tags ) {
                                return array_map( 'sanitize_key', (array) $tags );
                            },
                        ),
                    ),
                ),
            )
        );

        // AI single template endpoint.
        register_rest_route(
            self::NAMESPACE,
            '/ai/templates/(?P<name>[a-z0-9_-]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_ai_template' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'name' => array(
                            'description'       => __( 'Template name.', 'oxybridge-wp' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_ai_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'name' => array(
                            'description'       => __( 'Template name.', 'oxybridge-wp' ),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                    ),
                ),
            )
        );

        // =================================================================
        // AI Transformation & Validation Endpoints (Simplified Format)
        // =================================================================

        // AI transform endpoint - transforms simplified format to Breakdance format.
        register_rest_route(
            self::NAMESPACE,
            '/ai/transform',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'transform_simplified_tree' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'tree' => array(
                        'description' => __( 'Simplified tree to transform.', 'oxybridge-wp' ),
                        'type'        => 'object',
                        'required'    => true,
                    ),
                ),
            )
        );

        // AI validate endpoint - validates properties before saving.
        register_rest_route(
            self::NAMESPACE,
            '/ai/validate',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'validate_simplified_tree' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'tree' => array(
                        'description' => __( 'Tree to validate.', 'oxybridge-wp' ),
                        'type'        => 'object',
                        'required'    => true,
                    ),
                ),
            )
        );

        // AI preview CSS endpoint - previews CSS that would be generated.
        register_rest_route(
            self::NAMESPACE,
            '/ai/preview-css',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'preview_element_css' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'element_type' => array(
                        'description'       => __( 'Element type.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'properties'   => array(
                        'description' => __( 'Element properties.', 'oxybridge-wp' ),
                        'type'        => 'object',
                        'required'    => true,
                    ),
                ),
            )
        );

        // AI element schema endpoint - returns schema for specific element.
        register_rest_route(
            self::NAMESPACE,
            '/ai/schema/elements/(?P<type>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_element_schema' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'type' => array(
                        'description'       => __( 'Element type (e.g., Heading, Section).', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // AI simplified schema endpoint - returns full simplified schema.
        register_rest_route(
            self::NAMESPACE,
            '/ai/schema/simplified',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_ai_simplified_schema' ),
                'permission_callback' => '__return_true',
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
     * Plugin info endpoint callback.
     *
     * Returns detailed information about the Oxybridge plugin including
     * version numbers, Oxygen version, and available capabilities.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_plugin_info( \WP_REST_Request $request ) {
        $response = array(
            'plugin_version'  => OXYBRIDGE_VERSION,
            'oxygen_version'  => $this->get_oxygen_version(),
            'oxygen_active'   => oxybridge_is_oxygen_active(),
            'builder'         => $this->get_builder_name(),
            'capabilities'    => $this->get_capabilities(),
            'environment'     => array(
                'wordpress_version' => get_bloginfo( 'version' ),
                'php_version'       => PHP_VERSION,
                'site_url'          => get_site_url(),
                'rest_url'          => rest_url( self::NAMESPACE ),
            ),
        );

        /**
         * Filters the plugin info response data.
         *
         * @since 1.0.0
         * @param array            $response The response data.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_plugin_info', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get plugin capabilities.
     *
     * Returns an array of capabilities that the Oxybridge plugin provides,
     * indicating what operations are available via the REST API.
     *
     * @since 1.0.0
     * @return array List of capability names and their availability.
     */
    private function get_capabilities(): array {
        $oxygen_active = oxybridge_is_oxygen_active();

        return array(
            'read_documents'       => $oxygen_active,
            'read_templates'       => $oxygen_active,
            'read_global_settings' => $oxygen_active,
            'read_global_styles'   => $oxygen_active,
            'read_element_schema'  => $oxygen_active,
            'regenerate_css'       => $oxygen_active,
            'create_pages'         => $oxygen_active,
            'validate_tree'        => true,
            'list_pages'           => true,
            'health_check'         => true,
            'authentication'       => true,
        );
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
     * Returns the full document tree for a WordPress post/page with
     * Oxygen/Breakdance content. Supports optional metadata inclusion
     * and element flattening.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function read_document( \WP_REST_Request $request ) {
        $post_id          = $request->get_param( 'id' );
        $include_metadata = $request->get_param( 'include_metadata' );
        $flatten_elements = $request->get_param( 'flatten_elements' );

        // Get the post object.
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Check if the post has Oxygen/Breakdance content.
        if ( ! $this->has_oxygen_content( $post_id ) ) {
            return new \WP_Error(
                'rest_no_oxygen_content',
                __( 'The specified post does not have Oxygen/Breakdance content.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Get the document tree.
        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return new \WP_Error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Build the response data.
        $response_data = array(
            'id'   => $post_id,
            'tree' => $tree,
        );

        // Include flattened elements if requested.
        if ( $flatten_elements ) {
            $response_data['elements'] = $this->flatten_document_tree( $tree );
            // When flattening, remove the full tree to reduce payload size.
            unset( $response_data['tree'] );
        }

        // Include metadata if requested.
        if ( $include_metadata ) {
            $response_data['metadata'] = $this->get_document_metadata( $post );
        }

        // Add element count and types.
        $element_count = $this->count_document_elements( $tree );
        $element_types = $this->get_document_element_types( $tree );

        $response_data['element_count'] = $element_count;
        $response_data['element_types'] = $element_types;

        /**
         * Filters the document data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response_data The document data.
         * @param \WP_Post         $post          The post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_document_data', $response_data, $post, $request );

        return rest_ensure_response( $response_data );
    }

    /**
     * Get the document tree for a post.
     *
     * Uses the Oxygen_Data class if available, or falls back to direct
     * post meta access for retrieving the JSON tree structure.
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return array|false The document tree or false if not found.
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

        // Try classic Oxygen meta key as fallback.
        $classic_data = get_post_meta( $post_id, 'ct_builder_json', true );

        if ( ! empty( $classic_data ) ) {
            $decoded = is_string( $classic_data ) ? json_decode( $classic_data, true ) : $classic_data;

            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return false;
    }

    /**
     * Flatten a document tree into a list of elements.
     *
     * Recursively extracts all elements from the tree structure into
     * a flat array with path information preserved.
     *
     * @since 1.0.0
     * @param array $tree The document tree.
     * @return array Flat array of elements.
     */
    private function flatten_document_tree( array $tree ): array {
        $elements = array();

        // Check for root/children structure (Breakdance/Oxygen 6 format).
        if ( isset( $tree['root']['children'] ) ) {
            $elements = $this->extract_elements_recursive( $tree['root']['children'] );
        } elseif ( isset( $tree['children'] ) ) {
            // Check for direct children array.
            $elements = $this->extract_elements_recursive( $tree['children'] );
        } elseif ( is_array( $tree ) && ! isset( $tree['root'] ) ) {
            // If it's a flat array at root level, parse directly.
            $elements = $this->extract_elements_recursive( $tree );
        }

        return $elements;
    }

    /**
     * Recursively extract elements from tree children.
     *
     * @since 1.0.0
     * @param array  $children The children array from tree.
     * @param string $path     Current hierarchy path.
     * @param int    $depth    Current depth level.
     * @return array Flattened array of elements.
     */
    private function extract_elements_recursive( array $children, string $path = '', int $depth = 0 ): array {
        $elements = array();

        foreach ( $children as $index => $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            $element_id   = isset( $child['id'] ) ? $child['id'] : 'element-' . $index;
            $element_type = isset( $child['data']['type'] )
                ? $child['data']['type']
                : ( isset( $child['type'] ) ? $child['type'] : 'unknown' );
            $element_path = $path ? "{$path}/{$index}" : (string) $index;

            $element = array(
                'id'         => $element_id,
                'type'       => $element_type,
                'path'       => $element_path,
                'depth'      => $depth,
                'properties' => isset( $child['data']['properties'] )
                    ? $child['data']['properties']
                    : ( isset( $child['data'] ) ? $child['data'] : array() ),
            );

            $elements[] = $element;

            // Recursively process children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $child_elements = $this->extract_elements_recursive(
                    $child['children'],
                    $element_path,
                    $depth + 1
                );
                $elements       = array_merge( $elements, $child_elements );
            }
        }

        return $elements;
    }

    /**
     * Get document metadata for a post.
     *
     * Returns post information including title, slug, dates, author,
     * status, and URLs.
     *
     * @since 1.0.0
     * @param \WP_Post $post The post object.
     * @return array Document metadata.
     */
    private function get_document_metadata( \WP_Post $post ): array {
        return array(
            'title'        => $post->post_title,
            'slug'         => $post->post_name,
            'post_type'    => $post->post_type,
            'status'       => $post->post_status,
            'author_id'    => (int) $post->post_author,
            'author_name'  => get_the_author_meta( 'display_name', $post->post_author ),
            'created'      => $post->post_date_gmt . 'Z',
            'modified'     => $post->post_modified_gmt . 'Z',
            'permalink'    => get_permalink( $post->ID ),
            'edit_url'     => $this->get_builder_edit_url( $post->ID ),
            'builder'      => $this->get_builder_name(),
            'builder_version' => $this->get_oxygen_version(),
        );
    }

    /**
     * Count total elements in a document tree.
     *
     * @since 1.0.0
     * @param array $tree The document tree.
     * @return int Total element count.
     */
    private function count_document_elements( array $tree ): int {
        // Check for root/children structure (Breakdance/Oxygen 6 format).
        if ( isset( $tree['root']['children'] ) ) {
            return $this->count_elements_recursive( $tree['root']['children'] );
        }

        // Check for direct children array.
        if ( isset( $tree['children'] ) ) {
            return $this->count_elements_recursive( $tree['children'] );
        }

        // If it's a flat array at root level, count directly.
        if ( is_array( $tree ) && ! isset( $tree['root'] ) ) {
            return $this->count_elements_recursive( $tree );
        }

        return 0;
    }

    /**
     * Get unique element types from a document tree.
     *
     * @since 1.0.0
     * @param array $tree The document tree.
     * @return array Array of unique element type slugs.
     */
    private function get_document_element_types( array $tree ): array {
        $elements = $this->flatten_document_tree( $tree );
        $types    = array_column( $elements, 'type' );

        return array_values( array_unique( $types ) );
    }

    /**
     * Get the builder edit URL for a post.
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return string The builder edit URL.
     */
    private function get_builder_edit_url( int $post_id ): string {
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
     * List pages endpoint callback.
     *
     * Returns a list of posts and pages with Oxygen/Breakdance content.
     * Supports filtering by post type, search term, and status.
     * Optionally filters to only show posts with Oxygen content.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function list_pages( \WP_REST_Request $request ) {
        $post_type          = $request->get_param( 'post_type' );
        $search             = $request->get_param( 'search' );
        $status             = $request->get_param( 'status' );
        $has_oxygen_content = $request->get_param( 'has_oxygen_content' );
        $per_page           = $request->get_param( 'per_page' );
        $page               = $request->get_param( 'page' );

        // Determine post types to query.
        $query_post_types = $this->get_page_post_types( $post_type );

        // Build query arguments.
        $query_args = array(
            'post_type'      => $query_post_types,
            'posts_per_page' => absint( $per_page ),
            'paged'          => absint( $page ),
            'post_status'    => sanitize_key( $status ),
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        // Add search if provided.
        if ( ! empty( $search ) ) {
            $query_args['s'] = sanitize_text_field( $search );
        }

        // If filtering for Oxygen content, add meta query.
        if ( $has_oxygen_content ) {
            $meta_prefix = $this->get_meta_prefix();
            $query_args['meta_query'] = array(
                'relation' => 'OR',
                // Modern Oxygen 6 / Breakdance format.
                array(
                    'key'     => $meta_prefix . 'data',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen format (ct_builder_json).
                array(
                    'key'     => 'ct_builder_json',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen shortcodes format.
                array(
                    'key'     => 'ct_builder_shortcodes',
                    'compare' => 'EXISTS',
                ),
            );
        }

        // Execute the query.
        $query = new \WP_Query( $query_args );

        // Format results.
        $pages = array();
        foreach ( $query->posts as $post ) {
            $pages[] = $this->format_page( $post );
        }

        $response_data = array(
            'pages'       => $pages,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => absint( $page ),
            'per_page'    => absint( $per_page ),
        );

        /**
         * Filters the page listing data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response_data The page listing data.
         * @param \WP_Query        $query         The WP_Query instance.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_pages_data', $response_data, $query, $request );

        return rest_ensure_response( $response_data );
    }

    /**
     * Get post types to query for page listing.
     *
     * Returns an array of post types based on the filter parameter.
     * Excludes template post types as they have their own endpoint.
     *
     * @since 1.0.0
     * @param string $post_type The post type filter from request.
     * @return array Array of post types to query.
     */
    private function get_page_post_types( string $post_type ): array {
        // Get all template post types to exclude.
        $template_post_types = $this->get_all_template_post_types();

        if ( $post_type === 'any' || empty( $post_type ) ) {
            // Get all public post types except templates.
            $public_post_types = get_post_types(
                array(
                    'public' => true,
                ),
                'names'
            );

            // Exclude template post types and attachment.
            $excluded = array_merge( $template_post_types, array( 'attachment' ) );

            return array_values( array_diff( $public_post_types, $excluded ) );
        }

        // Handle specific post type request.
        $normalized_type = sanitize_key( $post_type );

        // Don't allow querying template post types via this endpoint.
        if ( in_array( $normalized_type, $template_post_types, true ) ) {
            return array();
        }

        // Verify the post type exists.
        if ( post_type_exists( $normalized_type ) ) {
            return array( $normalized_type );
        }

        return array();
    }

    /**
     * Format a post for the page listing API response.
     *
     * @since 1.0.0
     * @param \WP_Post $post The post object.
     * @return array Formatted page data.
     */
    private function format_page( \WP_Post $post ): array {
        $has_oxygen = $this->has_oxygen_content( $post->ID );
        $element_count = 0;

        // Only calculate element count if post has Oxygen content.
        if ( $has_oxygen ) {
            $tree = $this->get_document_tree( $post->ID );
            if ( $tree !== false ) {
                $element_count = $this->count_document_elements( $tree );
            }
        }

        return array(
            'id'                 => $post->ID,
            'title'              => $post->post_title,
            'slug'               => $post->post_name,
            'post_type'          => $post->post_type,
            'status'             => $post->post_status,
            'has_oxygen_content' => $has_oxygen,
            'element_count'      => $element_count,
            'author_id'          => (int) $post->post_author,
            'author_name'        => get_the_author_meta( 'display_name', $post->post_author ),
            'created'            => $post->post_date_gmt . 'Z',
            'modified'           => $post->post_modified_gmt . 'Z',
            'permalink'          => get_permalink( $post->ID ),
            'edit_url'           => $has_oxygen ? $this->get_builder_edit_url( $post->ID ) : get_edit_post_link( $post->ID, 'raw' ),
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
     * Create template endpoint callback.
     *
     * Creates a new Oxygen/Breakdance template with the specified type
     * and optional design tree structure.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function create_template( \WP_REST_Request $request ) {
        $title         = $request->get_param( 'title' );
        $template_type = $request->get_param( 'template_type' );
        $status        = $request->get_param( 'status' );
        $slug          = $request->get_param( 'slug' );
        $tree          = $request->get_param( 'tree' );
        $enable_oxygen = $request->get_param( 'enable_oxygen' );

        // Get the post type for this template type.
        $post_type = $this->get_post_type_for_template_type( $template_type );

        if ( ! $post_type ) {
            return new \WP_Error(
                'rest_invalid_template_type',
                sprintf(
                    /* translators: %s: template type */
                    __( 'Invalid template type "%s". Valid types are: header, footer, template, block, part%s.', 'oxybridge-wp' ),
                    $template_type,
                    $this->is_breakdance_mode() ? ', popup' : ''
                ),
                array( 'status' => 400 )
            );
        }

        // Verify post type exists.
        if ( ! post_type_exists( $post_type ) ) {
            return new \WP_Error(
                'rest_post_type_not_available',
                sprintf(
                    /* translators: %s: post type */
                    __( 'Template post type "%s" is not registered. Ensure Oxygen/Breakdance is active.', 'oxybridge-wp' ),
                    $post_type
                ),
                array( 'status' => 400 )
            );
        }

        // Check if user can create posts of this type.
        $post_type_obj = get_post_type_object( $post_type );
        if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
            return new \WP_Error(
                'rest_cannot_create',
                __( 'You do not have permission to create templates of this type.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Check publish permission if status is publish.
        if ( $status === 'publish' && ! current_user_can( $post_type_obj->cap->publish_posts ) ) {
            return new \WP_Error(
                'rest_cannot_publish',
                __( 'You do not have permission to publish templates of this type.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Build post data array.
        $post_data = array(
            'post_title'  => $title,
            'post_status' => $status,
            'post_type'   => $post_type,
            'post_author' => get_current_user_id(),
        );

        if ( ! empty( $slug ) ) {
            $post_data['post_name'] = $slug;
        }

        /**
         * Filters the post data before creating a new template via REST API.
         *
         * @since 1.0.0
         * @param array            $post_data The post data array.
         * @param \WP_REST_Request $request   The request object.
         */
        $post_data = apply_filters( 'oxybridge_rest_create_template_data', $post_data, $request );

        // Insert the post.
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_Error(
                'rest_template_creation_failed',
                sprintf(
                    /* translators: %s: error message */
                    __( 'Failed to create template: %s', 'oxybridge-wp' ),
                    $post_id->get_error_message()
                ),
                array( 'status' => 500 )
            );
        }

        // Handle Oxygen/Breakdance design data.
        $tree_saved = false;
        if ( ! empty( $tree ) ) {
            // Validate and save provided tree.
            $tree_saved = $this->save_document_tree( $post_id, $tree );
        } elseif ( $enable_oxygen ) {
            // Create empty tree structure for Oxygen builder.
            $empty_tree = $this->create_empty_tree();
            $tree_saved = $this->save_document_tree( $post_id, $empty_tree );
        }

        // Regenerate CSS if tree was saved and Oxygen is active.
        $css_regenerated = false;
        if ( $tree_saved && function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
            try {
                \Breakdance\Render\generateCacheForPost( $post_id );
                $css_regenerated = true;
            } catch ( \Exception $e ) {
                // CSS regeneration failed but template was created - continue.
            }
        }

        // Get the newly created post.
        $post = get_post( $post_id );

        // Build response data using the same format as get_template.
        $response_data = $this->format_template( $post );

        // Add additional creation-specific data.
        $response_data['tree_saved']      = $tree_saved;
        $response_data['css_regenerated'] = $css_regenerated;
        $response_data['edit_url']        = $this->get_template_edit_url( $post_id );

        /**
         * Fires after a template has been created via REST API.
         *
         * @since 1.0.0
         * @param int              $post_id The newly created template ID.
         * @param \WP_REST_Request $request The request object.
         */
        do_action( 'oxybridge_rest_template_created', $post_id, $request );

        /**
         * Filters the create template response data before returning.
         *
         * @since 1.0.0
         * @param array            $response_data The response data.
         * @param \WP_Post         $post          The created template post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_create_template_response', $response_data, $post, $request );

        // Return 201 Created response.
        $response = rest_ensure_response( $response_data );
        $response->set_status( 201 );

        // Set Location header to the new resource.
        $response->header( 'Location', rest_url( self::NAMESPACE . '/templates/' . $post_id ) );

        return $response;
    }

    /**
     * Get the WordPress post type for a template type slug.
     *
     * @since 1.0.0
     * @param string $template_type The template type slug.
     * @return string|false The post type or false if invalid.
     */
    private function get_post_type_for_template_type( string $template_type ) {
        $is_oxygen_mode = $this->is_breakdance_mode() === false;

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

        $normalized_type = strtolower( $template_type );

        return isset( $type_map[ $normalized_type ] ) ? $type_map[ $normalized_type ] : false;
    }

    /**
     * Check if we're in Breakdance mode (not Oxygen mode).
     *
     * @since 1.0.0
     * @return bool True if Breakdance mode, false if Oxygen mode.
     */
    private function is_breakdance_mode(): bool {
        return ! ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' );
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
        // Try to get prefix from Breakdance/Oxygen.
        // Note: Post meta uses '_meta_prefix' (with underscore).
        if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
            $prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox( '_meta_prefix' );
            if ( is_string( $prefix ) ) {
                return $prefix;
            }
        }

        // Check for Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return '_oxygen_';
        }

        // Default to Breakdance prefix.
        return '_breakdance_';
    }

    /**
     * Get the option prefix for global settings/options.
     *
     * Options use 'meta_prefix' (without underscore) unlike post meta.
     *
     * @since 1.0.0
     * @return string The option prefix.
     */
    private function get_option_prefix(): string {
        // Try to get prefix from Breakdance/Oxygen.
        // Note: Options use 'meta_prefix' (without underscore).
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
     * Returns global Oxygen/Breakdance settings including design tokens,
     * color palette, typography settings, and other builder configuration.
     * Supports retrieving a specific setting key or all settings.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function read_global_settings( \WP_REST_Request $request ) {
        $key = $request->get_param( 'key' );

        // Get global settings using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data     = new Oxygen_Data();
            $global_settings = $oxygen_data->get_global_settings();
        } else {
            // Fallback: get settings directly from options.
            $global_settings = $this->get_global_settings_fallback();
        }

        // If a specific key is requested, return only that setting.
        if ( ! empty( $key ) ) {
            $key = sanitize_key( $key );

            if ( isset( $global_settings[ $key ] ) ) {
                $response_data = array(
                    'key'   => $key,
                    'value' => $global_settings[ $key ],
                );
            } else {
                return new \WP_Error(
                    'rest_setting_not_found',
                    sprintf(
                        /* translators: %s: setting key */
                        __( 'Setting "%s" not found.', 'oxybridge-wp' ),
                        $key
                    ),
                    array( 'status' => 404 )
                );
            }
        } else {
            // Return all settings.
            $response_data = array(
                'settings' => $global_settings,
            );
        }

        // Add design variables to the response.
        $variables = $this->fetch_design_variables_data();
        if ( ! empty( $variables ) ) {
            $response_data['variables'] = $variables;
        }

        // Add CSS selectors/classes to the response.
        $selectors = $this->get_css_selectors();
        if ( ! empty( $selectors ) ) {
            $response_data['selectors'] = $selectors;
        }

        // Add breakpoints to the response.
        $response_data['breakpoints'] = $this->get_breakpoints();

        // Add metadata about the settings source.
        $response_data['_meta'] = array(
            'builder'   => $this->get_builder_name(),
            'version'   => $this->get_oxygen_version(),
            'timestamp' => current_time( 'c' ),
        );

        /**
         * Filters the global settings data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response_data The global settings data.
         * @param string|null      $key           The specific key requested, or null for all.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_global_settings', $response_data, $key, $request );

        return rest_ensure_response( $response_data );
    }

    /**
     * Fallback method for getting global settings when Oxygen_Data is unavailable.
     *
     * @since 1.0.0
     * @return array The global settings data.
     */
    private function get_global_settings_fallback(): array {
        $option_prefix   = $this->get_option_prefix();
        $global_settings = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( is_string( $global_settings ) && ! empty( $global_settings ) ) {
            $decoded = json_decode( $global_settings, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $global_settings ) ? $global_settings : array();
    }

    /**
     * Get element schema endpoint callback.
     *
     * Returns element type definitions, control types, and categories for
     * Oxygen/Breakdance builder elements. Supports filtering by specific
     * element type and optionally excluding control definitions.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_element_schema( \WP_REST_Request $request ) {
        $element_type     = $request->get_param( 'element_type' );
        $include_controls = $request->get_param( 'include_controls' );

        // Get element definitions.
        $elements = $this->get_element_definitions();

        // Filter by specific element type if requested.
        if ( ! empty( $element_type ) ) {
            $element_type = sanitize_text_field( $element_type );
            $filtered     = array();

            foreach ( $elements as $element ) {
                if (
                    ( isset( $element['slug'] ) && $element['slug'] === $element_type ) ||
                    ( isset( $element['type'] ) && $element['type'] === $element_type )
                ) {
                    $filtered[] = $element;
                }
            }

            // If no exact match, try partial match.
            if ( empty( $filtered ) ) {
                foreach ( $elements as $element ) {
                    $slug = isset( $element['slug'] ) ? $element['slug'] : '';
                    $type = isset( $element['type'] ) ? $element['type'] : '';

                    if (
                        stripos( $slug, $element_type ) !== false ||
                        stripos( $type, $element_type ) !== false
                    ) {
                        $filtered[] = $element;
                    }
                }
            }

            $elements = $filtered;
        }

        // Remove control details if not requested.
        if ( ! $include_controls ) {
            foreach ( $elements as &$element ) {
                unset( $element['controls'] );
            }
            unset( $element );
        }

        // Get control types.
        $control_types = $include_controls ? $this->get_control_types() : array();

        // Get element categories.
        $categories = $this->get_element_categories();

        // Build response.
        $response_data = array(
            'elements'      => $elements,
            'total'         => count( $elements ),
            'control_types' => $control_types,
            'categories'    => $categories,
            '_meta'         => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the element schema data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response_data The element schema data.
         * @param string|null      $element_type  The specific element type filter, or null for all.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_element_schema', $response_data, $element_type, $request );

        return rest_ensure_response( $response_data );
    }

    /**
     * Get element definitions from Breakdance registry or fallback.
     *
     * Attempts to retrieve element definitions from the Breakdance element
     * registry if available, otherwise returns a comprehensive list of
     * known Oxygen/Breakdance element types.
     *
     * @since 1.0.0
     * @return array Array of element definitions.
     */
    private function get_element_definitions(): array {
        // Try to get elements from Breakdance registry.
        $elements = $this->get_elements_from_registry();

        if ( ! empty( $elements ) ) {
            return $elements;
        }

        // Fallback: return known element types.
        return $this->get_known_element_types();
    }

    /**
     * Get elements from Breakdance element registry.
     *
     * @since 1.0.0
     * @return array Array of element definitions or empty if unavailable.
     */
    private function get_elements_from_registry(): array {
        $elements = array();

        // Try Breakdance's element registry.
        if ( class_exists( '\Breakdance\Elements\Elements' ) && method_exists( '\Breakdance\Elements\Elements', 'getInstance' ) ) {
            try {
                $registry           = \Breakdance\Elements\Elements::getInstance();
                $registered_elements = method_exists( $registry, 'getElements' )
                    ? $registry->getElements()
                    : array();

                foreach ( $registered_elements as $element_class ) {
                    $element_data = $this->format_registry_element( $element_class );
                    if ( $element_data ) {
                        $elements[] = $element_data;
                    }
                }
            } catch ( \Exception $e ) {
                // Registry access failed, fall through to fallback.
            }
        }

        // Try alternative registry access via global.
        if ( empty( $elements ) && isset( $GLOBALS['breakdance_elements'] ) && is_array( $GLOBALS['breakdance_elements'] ) ) {
            foreach ( $GLOBALS['breakdance_elements'] as $element_class ) {
                $element_data = $this->format_registry_element( $element_class );
                if ( $element_data ) {
                    $elements[] = $element_data;
                }
            }
        }

        return $elements;
    }

    /**
     * Format a registered element class into schema data.
     *
     * @since 1.0.0
     * @param string|object $element_class The element class name or instance.
     * @return array|null Formatted element data or null if invalid.
     */
    private function format_registry_element( $element_class ): ?array {
        try {
            // Handle string class name.
            if ( is_string( $element_class ) && class_exists( $element_class ) ) {
                // Check for static methods first.
                if ( method_exists( $element_class, 'slug' ) ) {
                    $slug = $element_class::slug();
                } elseif ( method_exists( $element_class, 'getSlug' ) ) {
                    $slug = $element_class::getSlug();
                } else {
                    // Derive slug from class name.
                    $slug = strtolower( basename( str_replace( '\\', '/', $element_class ) ) );
                }

                $label = $slug;
                if ( method_exists( $element_class, 'name' ) ) {
                    $label = $element_class::name();
                } elseif ( method_exists( $element_class, 'getName' ) ) {
                    $label = $element_class::getName();
                }

                $category = 'basic';
                if ( method_exists( $element_class, 'category' ) ) {
                    $category = $element_class::category();
                } elseif ( method_exists( $element_class, 'getCategory' ) ) {
                    $category = $element_class::getCategory();
                }

                $controls = array();
                if ( method_exists( $element_class, 'controls' ) ) {
                    $controls = $element_class::controls();
                } elseif ( method_exists( $element_class, 'getControls' ) ) {
                    $controls = $element_class::getControls();
                }

                return array(
                    'slug'        => $slug,
                    'type'        => $slug,
                    'label'       => $label,
                    'category'    => $category,
                    'class'       => $element_class,
                    'controls'    => is_array( $controls ) ? $controls : array(),
                    'source'      => 'registry',
                );
            }
        } catch ( \Exception $e ) {
            // Element parsing failed.
        }

        return null;
    }

    /**
     * Get known element types as fallback.
     *
     * Returns a comprehensive list of known Oxygen/Breakdance element types
     * when the element registry is not accessible.
     *
     * @since 1.0.0
     * @return array Array of known element type definitions.
     */
    private function get_known_element_types(): array {
        $is_oxygen_mode = defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen';
        $prefix         = $is_oxygen_mode ? 'oxy_' : 'EssentialElements\\\\';

        // Common elements available in both Oxygen and Breakdance.
        $elements = array(
            // Layout Elements.
            array(
                'slug'        => 'section',
                'type'        => $prefix . 'Section',
                'label'       => __( 'Section', 'oxybridge-wp' ),
                'category'    => 'layout',
                'description' => __( 'A container section for grouping elements.', 'oxybridge-wp' ),
                'controls'    => $this->get_layout_controls(),
            ),
            array(
                'slug'        => 'container',
                'type'        => $prefix . 'Container',
                'label'       => __( 'Container', 'oxybridge-wp' ),
                'category'    => 'layout',
                'description' => __( 'A div container for flexible layouts.', 'oxybridge-wp' ),
                'controls'    => $this->get_layout_controls(),
            ),
            array(
                'slug'        => 'div',
                'type'        => $prefix . 'Div',
                'label'       => __( 'Div', 'oxybridge-wp' ),
                'category'    => 'layout',
                'description' => __( 'A generic div block element.', 'oxybridge-wp' ),
                'controls'    => $this->get_layout_controls(),
            ),
            array(
                'slug'        => 'columns',
                'type'        => $prefix . 'Columns',
                'label'       => __( 'Columns', 'oxybridge-wp' ),
                'category'    => 'layout',
                'description' => __( 'A multi-column layout container.', 'oxybridge-wp' ),
                'controls'    => array_merge(
                    $this->get_layout_controls(),
                    array(
                        'columns' => array(
                            'type'        => 'number',
                            'label'       => __( 'Number of Columns', 'oxybridge-wp' ),
                            'default'     => 2,
                            'min'         => 1,
                            'max'         => 12,
                        ),
                        'gap' => array(
                            'type'        => 'unit',
                            'label'       => __( 'Gap', 'oxybridge-wp' ),
                            'default'     => '20px',
                        ),
                    )
                ),
            ),

            // Text Elements.
            array(
                'slug'        => 'heading',
                'type'        => $prefix . 'Heading',
                'label'       => __( 'Heading', 'oxybridge-wp' ),
                'category'    => 'text',
                'description' => __( 'A heading element (h1-h6).', 'oxybridge-wp' ),
                'controls'    => array_merge(
                    $this->get_typography_controls(),
                    array(
                        'text' => array(
                            'type'    => 'text',
                            'label'   => __( 'Heading Text', 'oxybridge-wp' ),
                            'default' => 'Heading',
                        ),
                        'tag' => array(
                            'type'    => 'select',
                            'label'   => __( 'Tag', 'oxybridge-wp' ),
                            'options' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ),
                            'default' => 'h2',
                        ),
                    )
                ),
            ),
            array(
                'slug'        => 'text',
                'type'        => $prefix . 'Text',
                'label'       => __( 'Text', 'oxybridge-wp' ),
                'category'    => 'text',
                'description' => __( 'A paragraph or text block.', 'oxybridge-wp' ),
                'controls'    => array_merge(
                    $this->get_typography_controls(),
                    array(
                        'text' => array(
                            'type'    => 'textarea',
                            'label'   => __( 'Content', 'oxybridge-wp' ),
                            'default' => 'Text content here.',
                        ),
                    )
                ),
            ),
            array(
                'slug'        => 'rich-text',
                'type'        => $prefix . 'RichText',
                'label'       => __( 'Rich Text', 'oxybridge-wp' ),
                'category'    => 'text',
                'description' => __( 'A rich text editor block.', 'oxybridge-wp' ),
                'controls'    => array_merge(
                    $this->get_typography_controls(),
                    array(
                        'content' => array(
                            'type'    => 'richtext',
                            'label'   => __( 'Content', 'oxybridge-wp' ),
                        ),
                    )
                ),
            ),

            // Media Elements.
            array(
                'slug'        => 'image',
                'type'        => $prefix . 'Image',
                'label'       => __( 'Image', 'oxybridge-wp' ),
                'category'    => 'media',
                'description' => __( 'An image element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'image' => array(
                        'type'  => 'media',
                        'label' => __( 'Image', 'oxybridge-wp' ),
                    ),
                    'alt' => array(
                        'type'  => 'text',
                        'label' => __( 'Alt Text', 'oxybridge-wp' ),
                    ),
                    'width' => array(
                        'type'  => 'unit',
                        'label' => __( 'Width', 'oxybridge-wp' ),
                    ),
                    'height' => array(
                        'type'  => 'unit',
                        'label' => __( 'Height', 'oxybridge-wp' ),
                    ),
                ),
            ),
            array(
                'slug'        => 'video',
                'type'        => $prefix . 'Video',
                'label'       => __( 'Video', 'oxybridge-wp' ),
                'category'    => 'media',
                'description' => __( 'A video element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'source' => array(
                        'type'    => 'select',
                        'label'   => __( 'Source', 'oxybridge-wp' ),
                        'options' => array( 'youtube', 'vimeo', 'self-hosted' ),
                    ),
                    'url' => array(
                        'type'  => 'url',
                        'label' => __( 'Video URL', 'oxybridge-wp' ),
                    ),
                    'autoplay' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Autoplay', 'oxybridge-wp' ),
                        'default' => false,
                    ),
                    'loop' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Loop', 'oxybridge-wp' ),
                        'default' => false,
                    ),
                ),
            ),
            array(
                'slug'        => 'icon',
                'type'        => $prefix . 'Icon',
                'label'       => __( 'Icon', 'oxybridge-wp' ),
                'category'    => 'media',
                'description' => __( 'An icon element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'icon' => array(
                        'type'  => 'icon',
                        'label' => __( 'Icon', 'oxybridge-wp' ),
                    ),
                    'size' => array(
                        'type'    => 'unit',
                        'label'   => __( 'Size', 'oxybridge-wp' ),
                        'default' => '24px',
                    ),
                    'color' => array(
                        'type'  => 'color',
                        'label' => __( 'Color', 'oxybridge-wp' ),
                    ),
                ),
            ),

            // Interactive Elements.
            array(
                'slug'        => 'button',
                'type'        => $prefix . 'Button',
                'label'       => __( 'Button', 'oxybridge-wp' ),
                'category'    => 'interactive',
                'description' => __( 'A button element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'text' => array(
                        'type'    => 'text',
                        'label'   => __( 'Button Text', 'oxybridge-wp' ),
                        'default' => 'Click Me',
                    ),
                    'link' => array(
                        'type'  => 'link',
                        'label' => __( 'Link', 'oxybridge-wp' ),
                    ),
                    'style' => array(
                        'type'    => 'select',
                        'label'   => __( 'Style', 'oxybridge-wp' ),
                        'options' => array( 'primary', 'secondary', 'outline', 'text' ),
                    ),
                    'size' => array(
                        'type'    => 'select',
                        'label'   => __( 'Size', 'oxybridge-wp' ),
                        'options' => array( 'small', 'medium', 'large' ),
                        'default' => 'medium',
                    ),
                ),
            ),
            array(
                'slug'        => 'link',
                'type'        => $prefix . 'Link',
                'label'       => __( 'Link', 'oxybridge-wp' ),
                'category'    => 'interactive',
                'description' => __( 'A text link element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'text' => array(
                        'type'  => 'text',
                        'label' => __( 'Link Text', 'oxybridge-wp' ),
                    ),
                    'url' => array(
                        'type'  => 'url',
                        'label' => __( 'URL', 'oxybridge-wp' ),
                    ),
                    'target' => array(
                        'type'    => 'select',
                        'label'   => __( 'Target', 'oxybridge-wp' ),
                        'options' => array( '_self', '_blank' ),
                    ),
                ),
            ),

            // Dynamic Elements.
            array(
                'slug'        => 'post-title',
                'type'        => $prefix . 'PostTitle',
                'label'       => __( 'Post Title', 'oxybridge-wp' ),
                'category'    => 'dynamic',
                'description' => __( 'Displays the post title dynamically.', 'oxybridge-wp' ),
                'controls'    => $this->get_typography_controls(),
            ),
            array(
                'slug'        => 'post-content',
                'type'        => $prefix . 'PostContent',
                'label'       => __( 'Post Content', 'oxybridge-wp' ),
                'category'    => 'dynamic',
                'description' => __( 'Displays the post content dynamically.', 'oxybridge-wp' ),
                'controls'    => $this->get_typography_controls(),
            ),
            array(
                'slug'        => 'featured-image',
                'type'        => $prefix . 'FeaturedImage',
                'label'       => __( 'Featured Image', 'oxybridge-wp' ),
                'category'    => 'dynamic',
                'description' => __( 'Displays the featured image dynamically.', 'oxybridge-wp' ),
                'controls'    => array(
                    'size' => array(
                        'type'    => 'select',
                        'label'   => __( 'Image Size', 'oxybridge-wp' ),
                        'options' => array( 'thumbnail', 'medium', 'large', 'full' ),
                    ),
                ),
            ),
            array(
                'slug'        => 'post-meta',
                'type'        => $prefix . 'PostMeta',
                'label'       => __( 'Post Meta', 'oxybridge-wp' ),
                'category'    => 'dynamic',
                'description' => __( 'Displays post metadata (date, author, categories).', 'oxybridge-wp' ),
                'controls'    => array(
                    'meta_type' => array(
                        'type'    => 'select',
                        'label'   => __( 'Meta Type', 'oxybridge-wp' ),
                        'options' => array( 'date', 'author', 'categories', 'tags', 'custom' ),
                    ),
                ),
            ),

            // Form Elements.
            array(
                'slug'        => 'form',
                'type'        => $prefix . 'Form',
                'label'       => __( 'Form', 'oxybridge-wp' ),
                'category'    => 'form',
                'description' => __( 'A form container element.', 'oxybridge-wp' ),
                'controls'    => array(
                    'action' => array(
                        'type'  => 'url',
                        'label' => __( 'Form Action', 'oxybridge-wp' ),
                    ),
                    'method' => array(
                        'type'    => 'select',
                        'label'   => __( 'Method', 'oxybridge-wp' ),
                        'options' => array( 'post', 'get' ),
                    ),
                ),
            ),
            array(
                'slug'        => 'text-input',
                'type'        => $prefix . 'TextInput',
                'label'       => __( 'Text Input', 'oxybridge-wp' ),
                'category'    => 'form',
                'description' => __( 'A text input field.', 'oxybridge-wp' ),
                'controls'    => array(
                    'label' => array(
                        'type'  => 'text',
                        'label' => __( 'Label', 'oxybridge-wp' ),
                    ),
                    'placeholder' => array(
                        'type'  => 'text',
                        'label' => __( 'Placeholder', 'oxybridge-wp' ),
                    ),
                    'required' => array(
                        'type'    => 'toggle',
                        'label'   => __( 'Required', 'oxybridge-wp' ),
                        'default' => false,
                    ),
                ),
            ),

            // WordPress Elements.
            array(
                'slug'        => 'menu',
                'type'        => $prefix . 'Menu',
                'label'       => __( 'Menu', 'oxybridge-wp' ),
                'category'    => 'wordpress',
                'description' => __( 'A WordPress navigation menu.', 'oxybridge-wp' ),
                'controls'    => array(
                    'menu' => array(
                        'type'  => 'menu_select',
                        'label' => __( 'Menu', 'oxybridge-wp' ),
                    ),
                    'orientation' => array(
                        'type'    => 'select',
                        'label'   => __( 'Orientation', 'oxybridge-wp' ),
                        'options' => array( 'horizontal', 'vertical' ),
                    ),
                ),
            ),
            array(
                'slug'        => 'shortcode',
                'type'        => $prefix . 'Shortcode',
                'label'       => __( 'Shortcode', 'oxybridge-wp' ),
                'category'    => 'wordpress',
                'description' => __( 'Renders a WordPress shortcode.', 'oxybridge-wp' ),
                'controls'    => array(
                    'shortcode' => array(
                        'type'  => 'text',
                        'label' => __( 'Shortcode', 'oxybridge-wp' ),
                    ),
                ),
            ),
            array(
                'slug'        => 'code-block',
                'type'        => $prefix . 'CodeBlock',
                'label'       => __( 'Code Block', 'oxybridge-wp' ),
                'category'    => 'wordpress',
                'description' => __( 'Custom HTML/CSS/JS code block.', 'oxybridge-wp' ),
                'controls'    => array(
                    'html' => array(
                        'type'  => 'code',
                        'label' => __( 'HTML', 'oxybridge-wp' ),
                        'lang'  => 'html',
                    ),
                    'css' => array(
                        'type'  => 'code',
                        'label' => __( 'CSS', 'oxybridge-wp' ),
                        'lang'  => 'css',
                    ),
                    'js' => array(
                        'type'  => 'code',
                        'label' => __( 'JavaScript', 'oxybridge-wp' ),
                        'lang'  => 'javascript',
                    ),
                ),
            ),
        );

        // Add source indicator to all elements.
        foreach ( $elements as &$element ) {
            $element['source'] = 'known_types';
        }
        unset( $element );

        return $elements;
    }

    /**
     * Get common layout control definitions.
     *
     * @since 1.0.0
     * @return array Layout control definitions.
     */
    private function get_layout_controls(): array {
        return array(
            'width' => array(
                'type'  => 'unit',
                'label' => __( 'Width', 'oxybridge-wp' ),
            ),
            'max_width' => array(
                'type'  => 'unit',
                'label' => __( 'Max Width', 'oxybridge-wp' ),
            ),
            'min_height' => array(
                'type'  => 'unit',
                'label' => __( 'Min Height', 'oxybridge-wp' ),
            ),
            'padding' => array(
                'type'  => 'spacing',
                'label' => __( 'Padding', 'oxybridge-wp' ),
            ),
            'margin' => array(
                'type'  => 'spacing',
                'label' => __( 'Margin', 'oxybridge-wp' ),
            ),
            'background' => array(
                'type'  => 'background',
                'label' => __( 'Background', 'oxybridge-wp' ),
            ),
            'display' => array(
                'type'    => 'select',
                'label'   => __( 'Display', 'oxybridge-wp' ),
                'options' => array( 'block', 'flex', 'grid', 'inline-block', 'none' ),
            ),
            'flex_direction' => array(
                'type'    => 'select',
                'label'   => __( 'Flex Direction', 'oxybridge-wp' ),
                'options' => array( 'row', 'column', 'row-reverse', 'column-reverse' ),
            ),
            'justify_content' => array(
                'type'    => 'select',
                'label'   => __( 'Justify Content', 'oxybridge-wp' ),
                'options' => array( 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly' ),
            ),
            'align_items' => array(
                'type'    => 'select',
                'label'   => __( 'Align Items', 'oxybridge-wp' ),
                'options' => array( 'flex-start', 'flex-end', 'center', 'stretch', 'baseline' ),
            ),
            'gap' => array(
                'type'  => 'unit',
                'label' => __( 'Gap', 'oxybridge-wp' ),
            ),
        );
    }

    /**
     * Get common typography control definitions.
     *
     * @since 1.0.0
     * @return array Typography control definitions.
     */
    private function get_typography_controls(): array {
        return array(
            'font_family' => array(
                'type'  => 'font',
                'label' => __( 'Font Family', 'oxybridge-wp' ),
            ),
            'font_size' => array(
                'type'  => 'unit',
                'label' => __( 'Font Size', 'oxybridge-wp' ),
            ),
            'font_weight' => array(
                'type'    => 'select',
                'label'   => __( 'Font Weight', 'oxybridge-wp' ),
                'options' => array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ),
            ),
            'line_height' => array(
                'type'  => 'unit',
                'label' => __( 'Line Height', 'oxybridge-wp' ),
            ),
            'letter_spacing' => array(
                'type'  => 'unit',
                'label' => __( 'Letter Spacing', 'oxybridge-wp' ),
            ),
            'text_align' => array(
                'type'    => 'select',
                'label'   => __( 'Text Align', 'oxybridge-wp' ),
                'options' => array( 'left', 'center', 'right', 'justify' ),
            ),
            'text_transform' => array(
                'type'    => 'select',
                'label'   => __( 'Text Transform', 'oxybridge-wp' ),
                'options' => array( 'none', 'uppercase', 'lowercase', 'capitalize' ),
            ),
            'color' => array(
                'type'  => 'color',
                'label' => __( 'Text Color', 'oxybridge-wp' ),
            ),
        );
    }

    /**
     * Get available control types.
     *
     * Returns the list of control types available in the builder's
     * element configuration system.
     *
     * @since 1.0.0
     * @return array Array of control type definitions.
     */
    private function get_control_types(): array {
        return array(
            array(
                'type'        => 'text',
                'label'       => __( 'Text Input', 'oxybridge-wp' ),
                'description' => __( 'Single line text input.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'textarea',
                'label'       => __( 'Textarea', 'oxybridge-wp' ),
                'description' => __( 'Multi-line text input.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'richtext',
                'label'       => __( 'Rich Text Editor', 'oxybridge-wp' ),
                'description' => __( 'WYSIWYG text editor.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'number',
                'label'       => __( 'Number', 'oxybridge-wp' ),
                'description' => __( 'Numeric input with optional min/max.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'unit',
                'label'       => __( 'Unit Value', 'oxybridge-wp' ),
                'description' => __( 'Number with CSS unit (px, em, rem, %, vw, vh).', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'select',
                'label'       => __( 'Select', 'oxybridge-wp' ),
                'description' => __( 'Dropdown selection from options.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'toggle',
                'label'       => __( 'Toggle', 'oxybridge-wp' ),
                'description' => __( 'Boolean on/off switch.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'color',
                'label'       => __( 'Color Picker', 'oxybridge-wp' ),
                'description' => __( 'Color selection with picker and presets.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'media',
                'label'       => __( 'Media', 'oxybridge-wp' ),
                'description' => __( 'WordPress media library selector.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'icon',
                'label'       => __( 'Icon', 'oxybridge-wp' ),
                'description' => __( 'Icon picker from available icon sets.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'link',
                'label'       => __( 'Link', 'oxybridge-wp' ),
                'description' => __( 'URL input with target options.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'url',
                'label'       => __( 'URL', 'oxybridge-wp' ),
                'description' => __( 'Simple URL input.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'font',
                'label'       => __( 'Font Family', 'oxybridge-wp' ),
                'description' => __( 'Font family selector with Google Fonts.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'spacing',
                'label'       => __( 'Spacing', 'oxybridge-wp' ),
                'description' => __( 'Four-sided spacing control (top, right, bottom, left).', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'background',
                'label'       => __( 'Background', 'oxybridge-wp' ),
                'description' => __( 'Background color, image, gradient controls.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'border',
                'label'       => __( 'Border', 'oxybridge-wp' ),
                'description' => __( 'Border width, style, color, radius controls.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'shadow',
                'label'       => __( 'Shadow', 'oxybridge-wp' ),
                'description' => __( 'Box shadow configuration.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'code',
                'label'       => __( 'Code Editor', 'oxybridge-wp' ),
                'description' => __( 'Code editor with syntax highlighting.', 'oxybridge-wp' ),
            ),
            array(
                'type'        => 'menu_select',
                'label'       => __( 'Menu Selector', 'oxybridge-wp' ),
                'description' => __( 'WordPress menu selection.', 'oxybridge-wp' ),
            ),
        );
    }

    /**
     * Get element categories.
     *
     * Returns the list of element categories used to organize
     * elements in the builder interface.
     *
     * @since 1.0.0
     * @return array Array of category definitions.
     */
    private function get_element_categories(): array {
        return array(
            array(
                'slug'        => 'layout',
                'label'       => __( 'Layout', 'oxybridge-wp' ),
                'description' => __( 'Structural layout elements like sections, containers, and columns.', 'oxybridge-wp' ),
                'icon'        => 'layout',
            ),
            array(
                'slug'        => 'text',
                'label'       => __( 'Text', 'oxybridge-wp' ),
                'description' => __( 'Text and typography elements.', 'oxybridge-wp' ),
                'icon'        => 'text',
            ),
            array(
                'slug'        => 'media',
                'label'       => __( 'Media', 'oxybridge-wp' ),
                'description' => __( 'Images, videos, icons, and other media elements.', 'oxybridge-wp' ),
                'icon'        => 'media',
            ),
            array(
                'slug'        => 'interactive',
                'label'       => __( 'Interactive', 'oxybridge-wp' ),
                'description' => __( 'Buttons, links, and interactive elements.', 'oxybridge-wp' ),
                'icon'        => 'interactive',
            ),
            array(
                'slug'        => 'dynamic',
                'label'       => __( 'Dynamic', 'oxybridge-wp' ),
                'description' => __( 'Elements that display dynamic WordPress content.', 'oxybridge-wp' ),
                'icon'        => 'dynamic',
            ),
            array(
                'slug'        => 'form',
                'label'       => __( 'Form', 'oxybridge-wp' ),
                'description' => __( 'Form containers and input elements.', 'oxybridge-wp' ),
                'icon'        => 'form',
            ),
            array(
                'slug'        => 'wordpress',
                'label'       => __( 'WordPress', 'oxybridge-wp' ),
                'description' => __( 'WordPress-specific elements like menus and shortcodes.', 'oxybridge-wp' ),
                'icon'        => 'wordpress',
            ),
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
            $variables = $this->fetch_design_variables_data();
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
     * Get responsive breakpoints endpoint callback.
     *
     * Returns the responsive breakpoint definitions used by Oxygen/Breakdance
     * for responsive styling. Breakpoints define the viewport widths at which
     * styles change for different device sizes.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_responsive_breakpoints( \WP_REST_Request $request ) {
        $breakpoints = $this->get_breakpoints();

        $response = array(
            'breakpoints' => $breakpoints,
            '_meta'       => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'count'     => count( $breakpoints ),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the responsive breakpoints data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response The response data containing breakpoints and metadata.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_breakpoints', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get global colors endpoint callback.
     *
     * Returns the global color palette defined in Oxygen/Breakdance settings.
     * Colors are retrieved from the global settings and include both named
     * colors and any color variables.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_global_colors( \WP_REST_Request $request ) {
        $colors = $this->get_colors();

        $response = array(
            'colors' => $colors,
            '_meta'  => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'count'     => count( $colors ),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the global colors data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response The response data containing colors and metadata.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_colors', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get design variables endpoint callback.
     *
     * Returns the CSS design variables defined in Oxygen/Breakdance settings.
     * Variables include custom properties for colors, spacing, typography,
     * and other design tokens.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_design_variables( \WP_REST_Request $request ) {
        $variables = $this->get_variables();

        $response = array(
            'variables' => $variables,
            '_meta'     => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'count'     => count( $variables ),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the design variables data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response The response data containing variables and metadata.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_variables', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get available fonts endpoint callback.
     *
     * Returns the available fonts list defined in Oxygen/Breakdance settings.
     * Fonts are retrieved from the global typography settings and include
     * both system fonts and custom uploaded fonts.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_available_fonts( \WP_REST_Request $request ) {
        $fonts = $this->get_fonts();

        $response = array(
            'fonts' => $fonts,
            '_meta' => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'count'     => count( $fonts ),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the available fonts data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response The response data containing fonts and metadata.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_fonts', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get global CSS classes endpoint callback.
     *
     * Returns the global CSS classes/selectors defined in Oxygen/Breakdance settings.
     * Classes include reusable style definitions that can be applied to elements.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_global_classes( \WP_REST_Request $request ) {
        $classes = $this->get_css_selectors();

        $response = array(
            'classes' => $classes,
            '_meta'   => array(
                'builder'   => $this->get_builder_name(),
                'version'   => $this->get_oxygen_version(),
                'count'     => count( $classes ),
                'timestamp' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the global classes data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response The response data containing classes and metadata.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_rest_classes', $response, $request );

        return rest_ensure_response( $response );
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
     * Regenerate CSS for all posts with Oxygen/Breakdance content (bulk).
     *
     * Iterates through all posts with Oxygen/Breakdance content and regenerates
     * the CSS cache for each. Supports optional filtering by post type and
     * configurable batch size.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function regenerate_all_css( \WP_REST_Request $request ) {
        $post_type  = $request->get_param( 'post_type' );
        $batch_size = $request->get_param( 'batch_size' );

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

        // Build query args to find all posts with Oxygen content.
        $meta_prefix = $this->get_meta_prefix();
        $query_args  = array(
            'posts_per_page' => absint( $batch_size ),
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                // Modern Oxygen 6 / Breakdance format.
                array(
                    'key'     => $meta_prefix . 'data',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen format (ct_builder_json).
                array(
                    'key'     => 'ct_builder_json',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen shortcodes format.
                array(
                    'key'     => 'ct_builder_shortcodes',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        // Determine post types to query.
        if ( ! empty( $post_type ) && post_type_exists( $post_type ) ) {
            $query_args['post_type'] = sanitize_key( $post_type );
        } else {
            // Query all public post types plus template types.
            $public_types   = get_post_types( array( 'public' => true ), 'names' );
            $template_types = $this->get_all_template_post_types();
            $all_types      = array_unique( array_merge( $public_types, $template_types ) );
            $query_args['post_type'] = array_values( $all_types );
        }

        // Get total count first (for progress tracking).
        $count_args                   = $query_args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields']         = 'ids';
        $count_query                  = new \WP_Query( $count_args );
        $total_posts                  = $count_query->found_posts;

        if ( $total_posts === 0 ) {
            return rest_ensure_response(
                array(
                    'success'       => true,
                    'message'       => __( 'No posts with Oxygen/Breakdance content found to regenerate.', 'oxybridge-wp' ),
                    'total'         => 0,
                    'processed'     => 0,
                    'succeeded'     => 0,
                    'failed'        => 0,
                    'duration_ms'   => 0,
                    'results'       => array(),
                )
            );
        }

        // Process posts in batches.
        $processed = 0;
        $succeeded = 0;
        $failed    = 0;
        $results   = array();
        $page      = 1;

        // Process all posts by paging through.
        while ( $processed < $total_posts ) {
            $query_args['paged'] = $page;
            $query               = new \WP_Query( $query_args );

            if ( empty( $query->posts ) ) {
                break;
            }

            foreach ( $query->posts as $post ) {
                $processed++;
                $post_start_time = microtime( true );
                $result          = array(
                    'post_id'    => $post->ID,
                    'post_title' => $post->post_title,
                    'post_type'  => $post->post_type,
                    'success'    => false,
                    'error'      => null,
                    'duration_ms' => 0,
                );

                try {
                    // Call Oxygen/Breakdance's built-in cache regeneration function.
                    \Breakdance\Render\generateCacheForPost( $post->ID );
                    $result['success']     = true;
                    $result['duration_ms'] = round( ( microtime( true ) - $post_start_time ) * 1000 );
                    $succeeded++;

                    /**
                     * Fires after CSS cache has been regenerated for a post during bulk regeneration.
                     *
                     * @since 1.0.0
                     * @param int   $post_id  The post ID.
                     * @param float $duration The regeneration duration in milliseconds.
                     */
                    do_action( 'oxybridge_css_regenerated', $post->ID, $result['duration_ms'] );
                } catch ( \Exception $e ) {
                    $result['error']       = $e->getMessage();
                    $result['duration_ms'] = round( ( microtime( true ) - $post_start_time ) * 1000 );
                    $failed++;
                } catch ( \Error $e ) {
                    $result['error']       = $e->getMessage();
                    $result['duration_ms'] = round( ( microtime( true ) - $post_start_time ) * 1000 );
                    $failed++;
                }

                $results[] = $result;
            }

            $page++;
        }

        $total_duration = round( ( microtime( true ) - $start_time ) * 1000 );

        /**
         * Fires after bulk CSS regeneration completes.
         *
         * @since 1.0.0
         * @param int   $total      Total posts found.
         * @param int   $succeeded  Number of posts successfully regenerated.
         * @param int   $failed     Number of posts that failed regeneration.
         * @param float $duration   Total duration in milliseconds.
         */
        do_action( 'oxybridge_bulk_css_regenerated', $total_posts, $succeeded, $failed, $total_duration );

        return rest_ensure_response(
            array(
                'success'     => $failed === 0,
                'message'     => $failed === 0
                    ? sprintf(
                        /* translators: %d: number of posts */
                        __( 'CSS cache regenerated successfully for %d posts.', 'oxybridge-wp' ),
                        $succeeded
                    )
                    : sprintf(
                        /* translators: 1: number succeeded, 2: number failed */
                        __( 'CSS regeneration completed with %1$d successes and %2$d failures.', 'oxybridge-wp' ),
                        $succeeded,
                        $failed
                    ),
                'total'       => $total_posts,
                'processed'   => $processed,
                'succeeded'   => $succeeded,
                'failed'      => $failed,
                'duration_ms' => $total_duration,
                'results'     => $results,
            )
        );
    }

    /**
     * Create page endpoint callback.
     *
     * Creates a new WordPress page/post with optional Oxygen/Breakdance design data.
     * Supports setting title, status, post type, slug, content, parent, and initial
     * design tree structure.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function create_page( \WP_REST_Request $request ) {
        $title           = $request->get_param( 'title' );
        $status          = $request->get_param( 'status' );
        $post_type       = $request->get_param( 'post_type' );
        $slug            = $request->get_param( 'slug' );
        $content         = $request->get_param( 'content' );
        $parent          = $request->get_param( 'parent' );
        $tree            = $request->get_param( 'tree' );
        $use_simplified  = $request->get_param( 'use_simplified' );
        $enable_oxygen   = $request->get_param( 'enable_oxygen' );
        $template        = $request->get_param( 'template' );

        // Transform simplified tree to Breakdance format if requested.
        if ( $use_simplified && ! empty( $tree ) ) {
            $transformer = new Property_Transformer();
            $tree = $transformer->transform_tree( $tree );
        }

        // Validate post type exists and is not a reserved template type.
        if ( ! post_type_exists( $post_type ) ) {
            return new \WP_Error(
                'rest_invalid_post_type',
                sprintf(
                    /* translators: %s: post type */
                    __( 'Post type "%s" does not exist.', 'oxybridge-wp' ),
                    $post_type
                ),
                array( 'status' => 400 )
            );
        }

        // Don't allow creating posts with template post types via this endpoint.
        $template_post_types = $this->get_all_template_post_types();
        if ( in_array( $post_type, $template_post_types, true ) ) {
            return new \WP_Error(
                'rest_invalid_post_type',
                __( 'Template post types cannot be created via this endpoint. Use /templates instead.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        // Check if user can publish posts of this type.
        $post_type_obj = get_post_type_object( $post_type );
        if ( ! current_user_can( $post_type_obj->cap->publish_posts ) && $status === 'publish' ) {
            return new \WP_Error(
                'rest_cannot_publish',
                __( 'You do not have permission to publish posts of this type.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Validate parent if provided for hierarchical post types.
        if ( $parent > 0 ) {
            if ( ! is_post_type_hierarchical( $post_type ) ) {
                return new \WP_Error(
                    'rest_invalid_parent',
                    __( 'Parent ID is only valid for hierarchical post types.', 'oxybridge-wp' ),
                    array( 'status' => 400 )
                );
            }

            $parent_post = get_post( $parent );
            if ( ! $parent_post || $parent_post->post_type !== $post_type ) {
                return new \WP_Error(
                    'rest_invalid_parent',
                    __( 'Parent post not found or is of different post type.', 'oxybridge-wp' ),
                    array( 'status' => 400 )
                );
            }
        }

        // Build post data array.
        $post_data = array(
            'post_title'  => $title,
            'post_status' => $status,
            'post_type'   => $post_type,
            'post_author' => get_current_user_id(),
        );

        if ( ! empty( $slug ) ) {
            $post_data['post_name'] = $slug;
        }

        if ( ! empty( $content ) ) {
            $post_data['post_content'] = $content;
        }

        if ( $parent > 0 ) {
            $post_data['post_parent'] = $parent;
        }

        /**
         * Filters the post data before creating a new page via REST API.
         *
         * @since 1.0.0
         * @param array            $post_data The post data array.
         * @param \WP_REST_Request $request   The request object.
         */
        $post_data = apply_filters( 'oxybridge_rest_create_page_data', $post_data, $request );

        // Insert the post.
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_Error(
                'rest_post_creation_failed',
                sprintf(
                    /* translators: %s: error message */
                    __( 'Failed to create page: %s', 'oxybridge-wp' ),
                    $post_id->get_error_message()
                ),
                array( 'status' => 500 )
            );
        }

        // Set page template if provided.
        if ( ! empty( $template ) && $post_type === 'page' ) {
            update_post_meta( $post_id, '_wp_page_template', $template );
        }

        // Handle Oxygen/Breakdance design data.
        $tree_saved = false;
        if ( ! empty( $tree ) ) {
            // Validate and save provided tree.
            $tree_saved = $this->save_document_tree( $post_id, $tree );
        } elseif ( $enable_oxygen ) {
            // Create empty tree structure for Oxygen builder.
            $empty_tree = $this->create_empty_tree();
            $tree_saved = $this->save_document_tree( $post_id, $empty_tree );
        }

        // Regenerate CSS if tree was saved and Oxygen is active.
        $css_regenerated = false;
        if ( $tree_saved && function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
            try {
                \Breakdance\Render\generateCacheForPost( $post_id );
                $css_regenerated = true;
            } catch ( \Exception $e ) {
                // CSS regeneration failed but post was created - continue.
            }
        }

        // Get the newly created post.
        $post = get_post( $post_id );

        // Build response data using the same format as list_pages.
        $response_data = $this->format_page( $post );

        // Add additional creation-specific data.
        $response_data['tree_saved']      = $tree_saved;
        $response_data['css_regenerated'] = $css_regenerated;

        /**
         * Fires after a page has been created via REST API.
         *
         * @since 1.0.0
         * @param int              $post_id The newly created post ID.
         * @param \WP_REST_Request $request The request object.
         */
        do_action( 'oxybridge_rest_page_created', $post_id, $request );

        /**
         * Filters the create page response data before returning.
         *
         * @since 1.0.0
         * @param array            $response_data The response data.
         * @param \WP_Post         $post          The created post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_create_page_response', $response_data, $post, $request );

        // Return 201 Created response.
        $response = rest_ensure_response( $response_data );
        $response->set_status( 201 );

        // Set Location header to the new resource.
        $response->header( 'Location', rest_url( self::NAMESPACE . '/documents/' . $post_id ) );

        return $response;
    }

    /**
     * Clone page endpoint callback.
     *
     * Creates a duplicate of an existing page or template, including
     * all Oxygen/Breakdance design data. The cloned post will have a
     * new title and can optionally have a custom slug and status.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function clone_page( \WP_REST_Request $request ) {
        $source_id = $request->get_param( 'id' );
        $title     = $request->get_param( 'title' );
        $status    = $request->get_param( 'status' );
        $slug      = $request->get_param( 'slug' );

        // Get the source post.
        $source_post = get_post( $source_id );

        if ( ! $source_post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Source post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Check if user can create posts of this type.
        $post_type_obj = get_post_type_object( $source_post->post_type );

        if ( ! $post_type_obj ) {
            return new \WP_Error(
                'rest_invalid_post_type',
                __( 'Invalid post type.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
            return new \WP_Error(
                'rest_cannot_create',
                __( 'You do not have permission to create posts of this type.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Check publish permission if status is publish.
        if ( $status === 'publish' && ! current_user_can( $post_type_obj->cap->publish_posts ) ) {
            return new \WP_Error(
                'rest_cannot_publish',
                __( 'You do not have permission to publish posts of this type.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        // Generate title if not provided.
        if ( empty( $title ) ) {
            /* translators: %s: original post title */
            $title = sprintf( __( 'Copy of %s', 'oxybridge-wp' ), $source_post->post_title );
        }

        // Build new post data.
        $new_post_data = array(
            'post_title'   => $title,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => $status,
            'post_type'    => $source_post->post_type,
            'post_author'  => get_current_user_id(),
            'post_parent'  => $source_post->post_parent,
            'menu_order'   => $source_post->menu_order,
        );

        // Set custom slug if provided.
        if ( ! empty( $slug ) ) {
            $new_post_data['post_name'] = $slug;
        }

        /**
         * Filters the post data before cloning a page via REST API.
         *
         * @since 1.0.0
         * @param array            $new_post_data The new post data array.
         * @param \WP_Post         $source_post   The source post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $new_post_data = apply_filters( 'oxybridge_rest_clone_page_data', $new_post_data, $source_post, $request );

        // Insert the new post.
        $new_post_id = wp_insert_post( $new_post_data, true );

        if ( is_wp_error( $new_post_id ) ) {
            return new \WP_Error(
                'rest_clone_failed',
                sprintf(
                    /* translators: %s: error message */
                    __( 'Failed to clone page: %s', 'oxybridge-wp' ),
                    $new_post_id->get_error_message()
                ),
                array( 'status' => 500 )
            );
        }

        // Clone all post meta and taxonomies using Oxygen_Data class.
        $oxygen_data = new Oxygen_Data();
        $meta_cloned = $oxygen_data->clone_post_meta( $source_id, $new_post_id );
        $terms_cloned = $oxygen_data->clone_post_terms( $source_id, $new_post_id );

        // Check if tree was cloned by checking for Oxygen content.
        $tree_cloned = $meta_cloned && $this->has_oxygen_content( $new_post_id );

        // Regenerate CSS if tree was cloned and Oxygen is active.
        $css_regenerated = false;
        if ( $tree_cloned && function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
            try {
                \Breakdance\Render\generateCacheForPost( $new_post_id );
                $css_regenerated = true;
            } catch ( \Exception $e ) {
                // CSS regeneration failed but clone was successful - continue.
            }
        }

        // Get the newly created post.
        $new_post = get_post( $new_post_id );

        // Build response data.
        $template_post_types = $this->get_all_template_post_types();
        $is_template = in_array( $new_post->post_type, $template_post_types, true );

        if ( $is_template ) {
            $response_data = $this->format_template( $new_post );
        } else {
            $response_data = $this->format_page( $new_post );
        }

        // Add clone-specific data.
        $response_data['cloned_from']      = $source_id;
        $response_data['tree_cloned']      = $tree_cloned;
        $response_data['css_regenerated']  = $css_regenerated;

        /**
         * Fires after a page has been cloned via REST API.
         *
         * @since 1.0.0
         * @param int              $new_post_id The newly created post ID.
         * @param int              $source_id   The source post ID.
         * @param \WP_REST_Request $request     The request object.
         */
        do_action( 'oxybridge_rest_page_cloned', $new_post_id, $source_id, $request );

        /**
         * Filters the clone page response data before returning.
         *
         * @since 1.0.0
         * @param array            $response_data The response data.
         * @param \WP_Post         $new_post      The cloned post object.
         * @param \WP_Post         $source_post   The source post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_clone_page_response', $response_data, $new_post, $source_post, $request );

        // Return 201 Created response.
        $response = rest_ensure_response( $response_data );
        $response->set_status( 201 );

        // Set Location header to the new resource.
        if ( $is_template ) {
            $response->header( 'Location', rest_url( self::NAMESPACE . '/templates/' . $new_post_id ) );
        } else {
            $response->header( 'Location', rest_url( self::NAMESPACE . '/documents/' . $new_post_id ) );
        }

        return $response;
    }

    /**
     * Validate tree structure endpoint callback.
     *
     * Validates the provided JSON tree structure without saving it.
     * Returns detailed validation results including errors, warnings, and statistics.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function validate_tree( \WP_REST_Request $request ) {
        $tree = $request->get_param( 'tree' );

        // Handle JSON string if passed as string instead of decoded object.
        if ( is_string( $tree ) ) {
            $decoded = json_decode( $tree, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $tree = $decoded;
            } else {
                return rest_ensure_response(
                    array(
                        'valid'        => false,
                        'errors'       => array(
                            array(
                                'code'    => 'invalid_json',
                                'message' => sprintf(
                                    /* translators: %s: JSON error message */
                                    __( 'Invalid JSON: %s', 'oxybridge-wp' ),
                                    json_last_error_msg()
                                ),
                            ),
                        ),
                        'warnings'     => array(),
                        'stats'        => array(
                            'element_count' => 0,
                            'max_depth'     => 0,
                            'element_types' => array(),
                        ),
                        'tree_version' => 'unknown',
                    )
                );
            }
        }

        // Check if tree is null or empty.
        if ( $tree === null ) {
            return rest_ensure_response(
                array(
                    'valid'        => false,
                    'errors'       => array(
                        array(
                            'code'    => 'missing_tree',
                            'message' => __( 'Tree parameter is required.', 'oxybridge-wp' ),
                        ),
                    ),
                    'warnings'     => array(),
                    'stats'        => array(
                        'element_count' => 0,
                        'max_depth'     => 0,
                        'element_types' => array(),
                    ),
                    'tree_version' => 'unknown',
                )
            );
        }

        // Use the Validator class for comprehensive validation.
        $validator = new Validator();
        $result    = $validator->validate( $tree );

        /**
         * Filters the tree validation result before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $result  The validation result.
         * @param mixed            $tree    The tree that was validated.
         * @param \WP_REST_Request $request The request object.
         */
        $result = apply_filters( 'oxybridge_rest_validate_tree_result', $result, $tree, $request );

        return rest_ensure_response( $result );
    }

    /**
     * Render design endpoint callback.
     *
     * Renders the Oxygen/Breakdance design tree for a post to HTML output.
     * Uses the Breakdance rendering system if available, otherwise falls back
     * to a basic tree-to-HTML conversion.
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response object or error.
     */
    public function render_design( \WP_REST_Request $request ) {
        $post_id         = $request->get_param( 'id' );
        $include_css     = $request->get_param( 'include_css' );
        $include_wrapper = $request->get_param( 'include_wrapper' );

        // Get the post object.
        $post = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Check if the post has Oxygen/Breakdance content.
        if ( ! $this->has_oxygen_content( $post_id ) ) {
            return new \WP_Error(
                'rest_no_oxygen_content',
                __( 'The specified post does not have Oxygen/Breakdance content.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        // Record start time for duration tracking.
        $start_time = microtime( true );

        // Try to render using Breakdance's rendering system.
        $html = $this->render_with_breakdance( $post_id );

        // If Breakdance render failed, try fallback rendering.
        if ( $html === null ) {
            $html = $this->render_tree_fallback( $post_id );
        }

        // Get CSS if requested.
        $css = '';
        if ( $include_css ) {
            $css = $this->get_rendered_css( $post_id );
        }

        // Calculate render duration.
        $duration = round( ( microtime( true ) - $start_time ) * 1000 );

        // Wrap output if requested.
        if ( $include_wrapper && ! empty( $html ) ) {
            $html = sprintf(
                '<div class="oxybridge-render" data-post-id="%d" data-builder="%s">%s</div>',
                esc_attr( $post_id ),
                esc_attr( $this->get_builder_name() ),
                $html
            );
        }

        // Build response data.
        $response_data = array(
            'post_id'        => $post_id,
            'html'           => $html,
            'render_time_ms' => $duration,
            'builder'        => $this->get_builder_name(),
            'has_content'    => ! empty( $html ),
        );

        // Include CSS if requested.
        if ( $include_css ) {
            $response_data['css'] = $css;
        }

        // Add metadata.
        $response_data['metadata'] = array(
            'title'     => $post->post_title,
            'slug'      => $post->post_name,
            'post_type' => $post->post_type,
            'status'    => $post->post_status,
            'modified'  => $post->post_modified_gmt . 'Z',
        );

        /**
         * Filters the rendered design data before returning via REST API.
         *
         * @since 1.0.0
         * @param array            $response_data The render response data.
         * @param \WP_Post         $post          The post object.
         * @param \WP_REST_Request $request       The request object.
         */
        $response_data = apply_filters( 'oxybridge_rest_render_design', $response_data, $post, $request );

        return rest_ensure_response( $response_data );
    }

    /**
     * Render a post using Breakdance's rendering system.
     *
     * Attempts to use Breakdance's built-in rendering functions to generate
     * HTML output for the design tree.
     *
     * @since 1.0.0
     * @param int $post_id The post ID to render.
     * @return string|null Rendered HTML or null if rendering unavailable.
     */
    private function render_with_breakdance( int $post_id ): ?string {
        // Try using Breakdance's render function if available.
        if ( function_exists( '\Breakdance\Render\render' ) ) {
            try {
                // Set up the post context for rendering.
                global $post;
                $original_post = $post;
                $post          = get_post( $post_id );
                setup_postdata( $post );

                // Render using Breakdance.
                $html = \Breakdance\Render\render( $post_id );

                // Restore original post context.
                $post = $original_post;
                if ( $original_post ) {
                    setup_postdata( $original_post );
                } else {
                    wp_reset_postdata();
                }

                return $html;
            } catch ( \Exception $e ) {
                // Rendering failed, fall through to null return.
            }
        }

        // Try alternative render function.
        if ( function_exists( '\Breakdance\Render\renderDocument' ) ) {
            try {
                return \Breakdance\Render\renderDocument( $post_id );
            } catch ( \Exception $e ) {
                // Rendering failed, fall through to null return.
            }
        }

        // Try to get rendered content from post meta if cached.
        $meta_prefix    = $this->get_meta_prefix();
        $cached_content = get_post_meta( $post_id, $meta_prefix . 'rendered_html', true );

        if ( ! empty( $cached_content ) ) {
            return $cached_content;
        }

        return null;
    }

    /**
     * Fallback rendering for when Breakdance functions are unavailable.
     *
     * Converts the design tree structure to basic HTML output. This is a
     * simplified render that won't include dynamic functionality but provides
     * a static HTML representation of the design structure.
     *
     * @since 1.0.0
     * @param int $post_id The post ID to render.
     * @return string Rendered HTML.
     */
    private function render_tree_fallback( int $post_id ): string {
        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return '';
        }

        // Get children to render.
        $children = array();
        if ( isset( $tree['root']['children'] ) ) {
            $children = $tree['root']['children'];
        } elseif ( isset( $tree['children'] ) ) {
            $children = $tree['children'];
        }

        if ( empty( $children ) ) {
            return '';
        }

        return $this->render_elements_to_html( $children );
    }

    /**
     * Recursively render elements to HTML.
     *
     * Converts element tree nodes to basic HTML representation.
     *
     * @since 1.0.0
     * @param array $elements Array of element nodes.
     * @param int   $depth    Current nesting depth.
     * @return string Rendered HTML.
     */
    private function render_elements_to_html( array $elements, int $depth = 0 ): string {
        $html = '';

        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }

            // Determine element type.
            $type = '';
            if ( isset( $element['data']['type'] ) ) {
                $type = $element['data']['type'];
            } elseif ( isset( $element['type'] ) ) {
                $type = $element['type'];
            }

            // Get element ID for class naming.
            $element_id = isset( $element['id'] ) ? $element['id'] : 'el-' . wp_rand();

            // Get element properties.
            $properties = isset( $element['data']['properties'] ) ? $element['data']['properties'] : array();
            if ( empty( $properties ) && isset( $element['data'] ) ) {
                $properties = $element['data'];
            }

            // Determine HTML tag based on element type.
            $tag     = $this->get_html_tag_for_element_type( $type );
            $classes = $this->get_classes_for_element( $type, $element_id, $properties );
            $content = $this->get_content_for_element( $type, $properties );

            // Render children if present.
            $children_html = '';
            if ( isset( $element['children'] ) && is_array( $element['children'] ) && ! empty( $element['children'] ) ) {
                $children_html = $this->render_elements_to_html( $element['children'], $depth + 1 );
            }

            // Build element HTML.
            if ( $this->is_void_element( $tag ) ) {
                // Self-closing tags (img, br, hr, etc.).
                $html .= sprintf(
                    '<%s class="%s" />',
                    esc_attr( $tag ),
                    esc_attr( $classes )
                );
            } else {
                // Container tags.
                $html .= sprintf(
                    '<%s class="%s">%s%s</%s>',
                    esc_attr( $tag ),
                    esc_attr( $classes ),
                    $content,
                    $children_html,
                    esc_attr( $tag )
                );
            }
        }

        return $html;
    }

    /**
     * Get the HTML tag for an element type.
     *
     * Maps Oxygen/Breakdance element types to appropriate HTML tags.
     *
     * @since 1.0.0
     * @param string $type The element type.
     * @return string The HTML tag to use.
     */
    private function get_html_tag_for_element_type( string $type ): string {
        $type_lower = strtolower( $type );

        // Map element types to HTML tags.
        $tag_map = array(
            'section'        => 'section',
            'container'      => 'div',
            'div'            => 'div',
            'columns'        => 'div',
            'column'         => 'div',
            'heading'        => 'h2',
            'text'           => 'p',
            'rich-text'      => 'div',
            'richtext'       => 'div',
            'paragraph'      => 'p',
            'image'          => 'img',
            'video'          => 'div',
            'icon'           => 'span',
            'button'         => 'button',
            'link'           => 'a',
            'form'           => 'form',
            'input'          => 'input',
            'textarea'       => 'textarea',
            'select'         => 'select',
            'nav'            => 'nav',
            'header'         => 'header',
            'footer'         => 'footer',
            'article'        => 'article',
            'aside'          => 'aside',
            'main'           => 'main',
            'ul'             => 'ul',
            'ol'             => 'ol',
            'li'             => 'li',
            'span'           => 'span',
            'code'           => 'code',
            'pre'            => 'pre',
            'blockquote'     => 'blockquote',
            'hr'             => 'hr',
            'br'             => 'br',
            'root'           => 'div',
        );

        // Check for exact match.
        if ( isset( $tag_map[ $type_lower ] ) ) {
            return $tag_map[ $type_lower ];
        }

        // Check for partial matches (e.g., "EssentialElements\\Section" -> "section").
        foreach ( $tag_map as $key => $tag ) {
            if ( stripos( $type_lower, $key ) !== false ) {
                return $tag;
            }
        }

        // Default to div for unknown types.
        return 'div';
    }

    /**
     * Get CSS classes for an element.
     *
     * Generates appropriate CSS classes based on element type and properties.
     *
     * @since 1.0.0
     * @param string $type       The element type.
     * @param string $element_id The element ID.
     * @param array  $properties The element properties.
     * @return string Space-separated CSS classes.
     */
    private function get_classes_for_element( string $type, string $element_id, array $properties ): string {
        $classes = array();

        // Add base class based on type.
        $type_class = sanitize_html_class( strtolower( str_replace( '\\', '-', $type ) ) );
        if ( ! empty( $type_class ) ) {
            $classes[] = 'ob-' . $type_class;
        }

        // Add element ID class.
        $classes[] = sanitize_html_class( $element_id );

        // Add custom classes from properties if present.
        if ( isset( $properties['classes'] ) ) {
            if ( is_array( $properties['classes'] ) ) {
                foreach ( $properties['classes'] as $class ) {
                    $classes[] = sanitize_html_class( $class );
                }
            } elseif ( is_string( $properties['classes'] ) ) {
                $classes[] = sanitize_html_class( $properties['classes'] );
            }
        }

        // Add className if present (common React-style property).
        if ( isset( $properties['className'] ) && is_string( $properties['className'] ) ) {
            $classes[] = sanitize_html_class( $properties['className'] );
        }

        return implode( ' ', array_filter( $classes ) );
    }

    /**
     * Get content for an element based on type and properties.
     *
     * Extracts text/content from element properties for rendering.
     *
     * @since 1.0.0
     * @param string $type       The element type.
     * @param array  $properties The element properties.
     * @return string The element content.
     */
    private function get_content_for_element( string $type, array $properties ): string {
        // Check common content properties.
        $content_keys = array( 'text', 'content', 'html', 'value', 'label' );

        foreach ( $content_keys as $key ) {
            if ( isset( $properties[ $key ] ) && is_string( $properties[ $key ] ) ) {
                return wp_kses_post( $properties[ $key ] );
            }
        }

        // Check nested content in 'content' object.
        if ( isset( $properties['content']['text'] ) ) {
            return wp_kses_post( $properties['content']['text'] );
        }

        return '';
    }

    /**
     * Check if an HTML tag is a void (self-closing) element.
     *
     * @since 1.0.0
     * @param string $tag The HTML tag.
     * @return bool True if void element, false otherwise.
     */
    private function is_void_element( string $tag ): bool {
        $void_elements = array(
            'area',
            'base',
            'br',
            'col',
            'embed',
            'hr',
            'img',
            'input',
            'link',
            'meta',
            'param',
            'source',
            'track',
            'wbr',
        );

        return in_array( strtolower( $tag ), $void_elements, true );
    }

    /**
     * Get rendered CSS for a post.
     *
     * Retrieves the CSS that was generated for the post's design.
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return string The CSS content.
     */
    private function get_rendered_css( int $post_id ): string {
        $meta_prefix = $this->get_meta_prefix();

        // Try to get CSS from post meta cache.
        $css = get_post_meta( $post_id, $meta_prefix . 'css_cache', true );

        if ( ! empty( $css ) ) {
            return $css;
        }

        // Try alternative CSS meta key.
        $css = get_post_meta( $post_id, $meta_prefix . 'css', true );

        if ( ! empty( $css ) ) {
            return $css;
        }

        // Check for cached CSS file.
        $upload_dir = wp_upload_dir();
        $css_dir    = $upload_dir['basedir'] . '/breakdance/css/';

        // Check various possible CSS file locations.
        $possible_files = array(
            $css_dir . 'post-' . $post_id . '.css',
            $css_dir . $post_id . '.css',
            $upload_dir['basedir'] . '/oxygen/css/' . $post_id . '.css',
        );

        foreach ( $possible_files as $file ) {
            if ( file_exists( $file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $css = file_get_contents( $file );
                if ( ! empty( $css ) ) {
                    return $css;
                }
            }
        }

        return '';
    }

    /**
     * Save document tree to post meta.
     *
     * Saves the Oxygen/Breakdance design tree structure to post meta,
     * using the correct format for the active builder.
     *
     * @since 1.0.0
     * @param int   $post_id The post ID.
     * @param array $tree    The tree structure to save.
     * @return bool True if saved successfully, false otherwise.
     */
    private function save_document_tree( int $post_id, array $tree ): bool {
        // Validate tree structure.
        if ( ! $this->is_valid_tree_structure( $tree ) ) {
            return false;
        }

        $meta_prefix = $this->get_meta_prefix();

        // Prepare tree data in Breakdance/Oxygen 6 format.
        $tree_json = wp_json_encode( $tree );

        if ( $tree_json === false ) {
            return false;
        }

        $data = array(
            'tree_json_string' => $tree_json,
        );

        // Encode the wrapper data.
        $encoded_data = wp_json_encode( $data );

        if ( $encoded_data === false ) {
            return false;
        }

        // WordPress update_post_meta calls wp_unslash() which strips backslashes.
        // We need to use wp_slash() to preserve the escaped quotes in our JSON.
        $result = update_post_meta( $post_id, $meta_prefix . 'data', wp_slash( $encoded_data ) );

        /**
         * Fires after document tree has been saved.
         *
         * @since 1.0.0
         * @param int   $post_id The post ID.
         * @param array $tree    The tree structure that was saved.
         * @param bool  $result  Whether the save was successful.
         */
        do_action( 'oxybridge_document_tree_saved', $post_id, $tree, (bool) $result );

        return (bool) $result;
    }

    /**
     * Create an empty tree structure for new Oxygen pages.
     *
     * Creates a valid empty tree structure that Oxygen/Breakdance
     * will recognize and can be edited in the builder.
     *
     * @since 1.0.0
     * @return array The empty tree structure.
     */
    private function create_empty_tree(): array {
        return array(
            'root' => array(
                'id'       => $this->generate_element_id(),
                'data'     => array(
                    'type' => 'root',
                ),
                'children' => array(),
            ),
        );
    }

    /**
     * Generate a unique element ID.
     *
     * Creates a unique identifier for Oxygen/Breakdance elements
     * using WordPress's cryptographically secure random function.
     *
     * @since 1.0.0
     * @return string The generated element ID.
     */
    private function generate_element_id(): string {
        return 'el-' . bin2hex( random_bytes( 8 ) );
    }

    /**
     * Validate tree structure.
     *
     * Checks if a tree array has the minimum required structure
     * for Oxygen/Breakdance to recognize it.
     *
     * @since 1.0.0
     * @param array $tree The tree structure to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_tree_structure( array $tree ): bool {
        // Must have a root element.
        if ( ! isset( $tree['root'] ) || ! is_array( $tree['root'] ) ) {
            return false;
        }

        // Root must have id, data, and children.
        $required_keys = array( 'id', 'data', 'children' );
        foreach ( $required_keys as $key ) {
            if ( ! array_key_exists( $key, $tree['root'] ) ) {
                return false;
            }
        }

        // Children must be an array.
        if ( ! is_array( $tree['root']['children'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Fallback method for getting global styles when Oxygen_Data is unavailable.
     *
     * @since 1.0.0
     * @param string $category The style category to retrieve.
     * @return array The global styles data.
     */
    private function get_global_styles_fallback( string $category ): array {
        $option_prefix   = $this->get_option_prefix();
        $global_settings = get_option( $option_prefix . 'global_settings_json_string', '' );

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
    private function fetch_design_variables_data(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            return $oxygen_data->get_variables();
        }

        // Fallback: get variables directly.
        $option_prefix = $this->get_option_prefix();
        $variables     = get_option( $option_prefix . 'variables_json_string', '' );

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
        $option_prefix = $this->get_option_prefix();
        $selectors     = get_option( $option_prefix . 'breakdance_classes_json_string', '' );

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
        $option_prefix = $this->get_option_prefix();
        $breakpoints   = get_option( $option_prefix . 'breakpoints', array() );

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
     * Get global color palette.
     *
     * Returns the colors defined in Oxygen/Breakdance global settings.
     * Attempts to use the Oxygen_Data class first, then falls back to
     * direct option access.
     *
     * @since 1.0.0
     * @return array Color palette configuration.
     */
    private function get_colors(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $styles      = $oxygen_data->get_global_styles( 'colors' );

            if ( ! empty( $styles['colors'] ) && is_array( $styles['colors'] ) ) {
                return $styles['colors'];
            }
        }

        // Fallback: get directly from global settings option.
        $option_prefix   = $this->get_option_prefix();
        $global_settings = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( is_string( $global_settings ) && ! empty( $global_settings ) ) {
            $settings = json_decode( $global_settings, true );

            if ( is_array( $settings ) && isset( $settings['colors'] ) && is_array( $settings['colors'] ) ) {
                return $settings['colors'];
            }
        }

        // Return empty array if no colors found.
        return array();
    }

    /**
     * Get design variables.
     *
     * Returns the CSS design variables defined in Oxygen/Breakdance settings.
     * Attempts to use the Oxygen_Data class first, then falls back to
     * direct option access.
     *
     * @since 1.0.0
     * @return array Design variables configuration.
     */
    private function get_variables(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $variables   = $oxygen_data->get_variables();

            if ( ! empty( $variables ) && is_array( $variables ) ) {
                return $variables;
            }
        }

        // Fallback: get directly from variables option.
        $option_prefix = $this->get_option_prefix();
        $variables     = get_option( $option_prefix . 'variables_json_string', '' );

        if ( is_string( $variables ) && ! empty( $variables ) ) {
            $decoded = json_decode( $variables, true );

            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        // Return empty array if no variables found.
        return array();
    }

    /**
     * Get available fonts.
     *
     * Returns the fonts defined in Oxygen/Breakdance global settings.
     * Attempts to use the Oxygen_Data class first, then falls back to
     * direct option access.
     *
     * @since 1.0.0
     * @return array Available fonts configuration.
     */
    private function get_fonts(): array {
        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $styles      = $oxygen_data->get_global_styles( 'fonts' );

            if ( ! empty( $styles['fonts'] ) && is_array( $styles['fonts'] ) ) {
                return $styles['fonts'];
            }
        }

        // Fallback: get directly from global settings option.
        $option_prefix   = $this->get_option_prefix();
        $global_settings = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( is_string( $global_settings ) && ! empty( $global_settings ) ) {
            $settings = json_decode( $global_settings, true );

            if ( is_array( $settings ) && isset( $settings['typography'] ) && is_array( $settings['typography'] ) ) {
                return $settings['typography'];
            }
        }

        // Return empty array if no fonts found.
        return array();
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

    // =================================================================
    // AI Agent Endpoint Callbacks
    // =================================================================

    /**
     * Get AI context endpoint callback.
     *
     * Returns everything an AI agent needs to generate pages:
     * - Compact element schema
     * - Design tokens (colors, fonts, spacing)
     * - Available component list
     * - API reference
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_context( \WP_REST_Request $request ) {
        $schema_file = OXYBRIDGE_PLUGIN_DIR . '/ai/schema.json';
        $schema      = array();

        if ( file_exists( $schema_file ) ) {
            $schema_content = file_get_contents( $schema_file );
            $schema         = json_decode( $schema_content, true );
        }

        // Get design tokens.
        $design_tokens = new Design_Tokens();
        $tokens        = $design_tokens->get_tokens();

        // Get component list (names only for minimal context).
        $components = $this->get_ai_component_names();

        // Get template list (names only).
        $templates = $this->get_ai_template_names();

        $response = array(
            'schema'     => array(
                'elements'         => $schema['elements'] ?? array(),
                'rules'            => $schema['rules'] ?? array(),
                'breakpoints'      => $schema['breakpoints'] ?? array(),
                'node_structure'   => $schema['node_structure'] ?? array(),
                'property_patterns' => $schema['property_patterns'] ?? array(),
            ),
            'tokens'     => $tokens,
            'components' => $components,
            'templates'  => $templates,
            'api'        => array(
                'base_url'    => rest_url( self::NAMESPACE ),
                'create_page' => array(
                    'method' => 'POST',
                    'path'   => '/pages',
                    'body'   => array( 'title' => 'string', 'status' => 'publish|draft', 'tree' => 'object' ),
                ),
                'save_tree'   => array(
                    'method' => 'POST',
                    'path'   => '/documents/{id}',
                    'body'   => array( 'tree' => 'object' ),
                ),
                'regenerate_css' => array(
                    'method' => 'POST',
                    'path'   => '/regenerate-css/{id}',
                ),
            ),
            'meta'       => array(
                'context_size' => 'optimized',
                'version'      => OXYBRIDGE_VERSION,
                'generated_at' => current_time( 'c' ),
            ),
        );

        /**
         * Filters the AI context response.
         *
         * @since 1.1.0
         * @param array            $response The response data.
         * @param \WP_REST_Request $request  The request object.
         */
        $response = apply_filters( 'oxybridge_ai_context', $response, $request );

        return rest_ensure_response( $response );
    }

    /**
     * Get AI tokens endpoint callback.
     *
     * Returns design tokens extracted from site's global styles.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_tokens( \WP_REST_Request $request ) {
        $design_tokens = new Design_Tokens();
        $tokens        = $design_tokens->get_tokens();

        return rest_ensure_response( $tokens );
    }

    /**
     * Get AI schema endpoint callback.
     *
     * Returns the compact element schema for AI agents.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_schema( \WP_REST_Request $request ) {
        $schema_file = OXYBRIDGE_PLUGIN_DIR . '/ai/schema.json';

        if ( ! file_exists( $schema_file ) ) {
            return new \WP_Error(
                'schema_not_found',
                __( 'AI schema file not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        $schema_content = file_get_contents( $schema_file );
        $schema         = json_decode( $schema_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'schema_invalid',
                __( 'AI schema file is invalid JSON.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( $schema );
    }

    /**
     * Get AI components endpoint callback.
     *
     * Returns all available component snippets.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_components( \WP_REST_Request $request ) {
        $components = $this->load_ai_components();

        if ( is_wp_error( $components ) ) {
            return $components;
        }

        $response = array(
            'components' => $components,
            'meta'       => array(
                'count'        => count( $components ),
                'generated_at' => current_time( 'c' ),
            ),
        );

        return rest_ensure_response( $response );
    }

    /**
     * Get single AI component endpoint callback.
     *
     * Returns a specific component snippet by name.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response or error.
     */
    public function get_ai_component( \WP_REST_Request $request ) {
        $name       = $request->get_param( 'name' );
        $components = $this->load_ai_components();

        if ( is_wp_error( $components ) ) {
            return $components;
        }

        if ( ! isset( $components[ $name ] ) ) {
            return new \WP_Error(
                'component_not_found',
                sprintf( __( 'Component "%s" not found.', 'oxybridge-wp' ), $name ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'name'      => $name,
            'component' => $components[ $name ],
        ) );
    }

    /**
     * Get AI templates list endpoint callback.
     *
     * Returns all saved AI templates.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_templates( \WP_REST_Request $request ) {
        $templates = get_option( 'oxybridge_ai_templates', array() );

        $response = array(
            'templates' => array(),
            'meta'      => array(
                'count'        => count( $templates ),
                'generated_at' => current_time( 'c' ),
            ),
        );

        foreach ( $templates as $name => $template ) {
            $response['templates'][] = array(
                'name'        => $name,
                'description' => $template['description'] ?? '',
                'tags'        => $template['tags'] ?? array(),
                'created_at'  => $template['created_at'] ?? null,
            );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Get single AI template endpoint callback.
     *
     * Returns a specific template by name.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response or error.
     */
    public function get_ai_template( \WP_REST_Request $request ) {
        $name      = $request->get_param( 'name' );
        $templates = get_option( 'oxybridge_ai_templates', array() );

        if ( ! isset( $templates[ $name ] ) ) {
            return new \WP_Error(
                'template_not_found',
                sprintf( __( 'Template "%s" not found.', 'oxybridge-wp' ), $name ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'name'     => $name,
            'template' => $templates[ $name ],
        ) );
    }

    /**
     * Create AI template endpoint callback.
     *
     * Saves a new AI template.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response or error.
     */
    public function create_ai_template( \WP_REST_Request $request ) {
        $name        = $request->get_param( 'name' );
        $description = $request->get_param( 'description' );
        $tree        = $request->get_param( 'tree' );
        $tags        = $request->get_param( 'tags' );

        $templates = get_option( 'oxybridge_ai_templates', array() );

        // Check if template already exists.
        $is_update = isset( $templates[ $name ] );

        $templates[ $name ] = array(
            'description' => $description,
            'tree'        => $tree,
            'tags'        => $tags,
            'created_at'  => $is_update ? ( $templates[ $name ]['created_at'] ?? current_time( 'c' ) ) : current_time( 'c' ),
            'updated_at'  => current_time( 'c' ),
        );

        $saved = update_option( 'oxybridge_ai_templates', $templates );

        if ( ! $saved && ! $is_update ) {
            return new \WP_Error(
                'template_save_failed',
                __( 'Failed to save template.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response( array(
            'success' => true,
            'name'    => $name,
            'action'  => $is_update ? 'updated' : 'created',
        ) );
    }

    /**
     * Delete AI template endpoint callback.
     *
     * Deletes an AI template by name.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error The response or error.
     */
    public function delete_ai_template( \WP_REST_Request $request ) {
        $name      = $request->get_param( 'name' );
        $templates = get_option( 'oxybridge_ai_templates', array() );

        if ( ! isset( $templates[ $name ] ) ) {
            return new \WP_Error(
                'template_not_found',
                sprintf( __( 'Template "%s" not found.', 'oxybridge-wp' ), $name ),
                array( 'status' => 404 )
            );
        }

        unset( $templates[ $name ] );
        update_option( 'oxybridge_ai_templates', $templates );

        return rest_ensure_response( array(
            'success' => true,
            'name'    => $name,
            'action'  => 'deleted',
        ) );
    }

    /**
     * Load AI components from file.
     *
     * @since 1.1.0
     *
     * @return array|\WP_Error Components array or error.
     */
    private function load_ai_components() {
        $components_file = OXYBRIDGE_PLUGIN_DIR . '/ai/components.json';

        if ( ! file_exists( $components_file ) ) {
            // Return empty array if file doesn't exist yet.
            return array();
        }

        $content    = file_get_contents( $components_file );
        $components = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'components_invalid',
                __( 'AI components file is invalid JSON.', 'oxybridge-wp' ),
                array( 'status' => 500 )
            );
        }

        return $components;
    }

    /**
     * Get AI component names for minimal context.
     *
     * @since 1.1.0
     *
     * @return array Array of component names.
     */
    private function get_ai_component_names(): array {
        $components = $this->load_ai_components();

        if ( is_wp_error( $components ) || empty( $components ) ) {
            return array();
        }

        return array_keys( $components );
    }

    /**
     * Get AI template names for minimal context.
     *
     * @since 1.1.0
     *
     * @return array Array of template names with descriptions.
     */
    private function get_ai_template_names(): array {
        $templates = get_option( 'oxybridge_ai_templates', array() );
        $names     = array();

        foreach ( $templates as $name => $template ) {
            $names[] = array(
                'name'        => $name,
                'description' => $template['description'] ?? '',
            );
        }

        return $names;
    }

    // =================================================================
    // AI Transformation & Validation Methods (Simplified Format Support)
    // =================================================================

    /**
     * Transform simplified tree to Breakdance format.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function transform_simplified_tree( \WP_REST_Request $request ) {
        $tree = $request->get_param( 'tree' );

        if ( empty( $tree ) ) {
            return new \WP_Error(
                'missing_tree',
                __( 'Tree parameter is required.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        $transformer = new Property_Transformer();
        $transformed = $transformer->transform_tree( $tree );

        return rest_ensure_response( array(
            'success'     => true,
            'tree'        => $transformed,
            'transformed' => true,
        ) );
    }

    /**
     * Validate simplified tree.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function validate_simplified_tree( \WP_REST_Request $request ) {
        $tree = $request->get_param( 'tree' );

        if ( empty( $tree ) ) {
            return new \WP_Error(
                'missing_tree',
                __( 'Tree parameter is required.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        $validator = new Style_Validator();
        $result    = $validator->validate_tree( $tree );

        return rest_ensure_response( array(
            'valid'    => $result['valid'],
            'errors'   => $result['errors'],
            'warnings' => $result['warnings'],
        ) );
    }

    /**
     * Preview CSS for an element.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function preview_element_css( \WP_REST_Request $request ) {
        $element_type = $request->get_param( 'element_type' );
        $properties   = $request->get_param( 'properties' );

        if ( empty( $element_type ) || empty( $properties ) ) {
            return new \WP_Error(
                'missing_params',
                __( 'Both element_type and properties are required.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        $validator = new Style_Validator();
        $result    = $validator->validate_with_preview( $element_type, $properties );

        return rest_ensure_response( $result );
    }

    /**
     * Get simplified schema for a specific element.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_element_schema( \WP_REST_Request $request ) {
        $type = $request->get_param( 'type' );

        $schema       = new Property_Schema();
        $element_info = $schema->get_element_simplified_schema( $type );

        if ( empty( $element_info ) ) {
            return new \WP_Error(
                'element_not_found',
                sprintf( __( 'Element type "%s" not found.', 'oxybridge-wp' ), $type ),
                array( 'status' => 404 )
            );
        }

        return rest_ensure_response( array(
            'type'       => $type,
            'breakdance_type' => $schema->get_element_type( $type ),
            'properties' => $element_info['properties'] ?? array(),
            'permissions' => $element_info['permissions'] ?? array(),
        ) );
    }

    /**
     * Get full simplified schema.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
     */
    public function get_ai_simplified_schema( \WP_REST_Request $request ) {
        $generator = new Schema_Generator();
        $schema    = $generator->generate();

        return rest_ensure_response( $schema );
    }
}
