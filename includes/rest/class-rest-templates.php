<?php
/**
 * REST Templates Controller.
 *
 * Handles template CRUD operations for headers, footers, global blocks, etc.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

/**
 * Templates REST controller.
 *
 * @since 1.1.0
 */
class REST_Templates extends REST_Controller {

    /**
     * Template post type.
     *
     * @var string
     */
    const TEMPLATE_POST_TYPE = 'breakdance_template';

    /**
     * Register template routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/templates',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_templates' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                    'args'                => array(
                        'type'     => array(
                            'description' => __( 'Filter by template type.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => '',
                        ),
                        'per_page' => array(
                            'description' => __( 'Results per page.', 'oxybridge-wp' ),
                            'type'        => 'integer',
                            'default'     => 50,
                            'maximum'     => 100,
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'title' => array(
                            'description' => __( 'Template title.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'required'    => true,
                        ),
                        'type'  => array(
                            'description' => __( 'Template type.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'required'    => true,
                        ),
                        'tree'  => array(
                            'description' => __( 'Template tree.', 'oxybridge-wp' ),
                            'type'        => array( 'object', 'array' ),
                            'default'     => null,
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/templates/(?P<id>\d+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_template' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => __( 'Template ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_template_id' ),
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'id'    => array(
                            'description'       => __( 'Template ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_template_id' ),
                        ),
                        'title' => array(
                            'description' => __( 'Template title.', 'oxybridge-wp' ),
                            'type'        => 'string',
                        ),
                        'tree'  => array(
                            'description' => __( 'Template tree.', 'oxybridge-wp' ),
                            'type'        => array( 'object', 'array' ),
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_template' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'id'    => array(
                            'description'       => __( 'Template ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_template_id' ),
                        ),
                        'force' => array(
                            'description' => __( 'Permanently delete.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => false,
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/template-types',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_template_types' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Validate template ID parameter.
     *
     * @since 1.1.0
     * @param mixed            $value   The parameter value.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error
     */
    public function validate_template_id( $value, \WP_REST_Request $request, $param ) {
        $result = $this->validate_post_id( $value, $request, $param );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $post = get_post( (int) $value );
        $template_types = $this->get_template_post_types();

        if ( ! in_array( $post->post_type, $template_types, true ) ) {
            return new \WP_Error(
                'rest_invalid_template',
                __( 'Post is not a template.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        return true;
    }

    /**
     * List templates endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function list_templates( \WP_REST_Request $request ) {
        $type     = $request->get_param( 'type' );
        $per_page = (int) $request->get_param( 'per_page' );

        $post_types = $this->get_template_post_types( $type );

        if ( empty( $post_types ) ) {
            return $this->format_response( array( 'templates' => array() ) );
        }

        $query = new \WP_Query(
            array(
                'post_type'      => $post_types,
                'post_status'    => array( 'publish', 'draft' ),
                'posts_per_page' => $per_page,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        $templates = array();
        foreach ( $query->posts as $post ) {
            $templates[] = $this->format_template( $post );
        }

        return $this->format_response(
            array(
                'templates' => $templates,
                'total'     => $query->found_posts,
            )
        );
    }

    /**
     * Get template endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_template( \WP_REST_Request $request ) {
        $template_id = (int) $request->get_param( 'id' );
        $post = get_post( $template_id );

        if ( ! $post ) {
            return $this->format_error(
                'rest_template_not_found',
                __( 'Template not found.', 'oxybridge-wp' ),
                404
            );
        }

        $template = $this->format_template( $post );
        $tree = $this->get_document_tree( $template_id );

        // Ensure tree is always a valid structure with required IO-TS properties.
        // get_document_tree() already applies ensure_tree_integrity() which adds _nextNodeId.
        // If no tree found, create empty tree and apply ensure_tree_integrity() for _nextNodeId.
        if ( $tree === false ) {
            $tree = $this->ensure_tree_integrity( $this->create_empty_tree() );
        }

        $template['tree'] = $tree;

        return $this->format_response( $template );
    }

    /**
     * Create template endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_template( \WP_REST_Request $request ) {
        $title = sanitize_text_field( $request->get_param( 'title' ) );
        $type  = sanitize_key( $request->get_param( 'type' ) );
        $tree  = $request->get_param( 'tree' );

        $post_type = $this->get_post_type_for_template_type( $type );

        if ( ! $post_type ) {
            return $this->format_error(
                'rest_invalid_template_type',
                __( 'Invalid template type.', 'oxybridge-wp' ),
                400
            );
        }

        $post_id = wp_insert_post(
            array(
                'post_title'  => $title,
                'post_type'   => $post_type,
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $this->format_error(
                'rest_create_failed',
                $post_id->get_error_message(),
                500
            );
        }

        // Save tree if provided or create empty tree.
        if ( ! empty( $tree ) ) {
            $this->save_document_tree( $post_id, $tree );
        } else {
            $empty_tree = $this->create_empty_tree();
            $this->save_document_tree( $post_id, $empty_tree );
        }

        $post = get_post( $post_id );

        return $this->format_response(
            array(
                'success'  => true,
                'template' => $this->format_template( $post ),
            ),
            201
        );
    }

    /**
     * Update template endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_template( \WP_REST_Request $request ) {
        $template_id = (int) $request->get_param( 'id' );
        $title       = $request->get_param( 'title' );
        $tree        = $request->get_param( 'tree' );

        $post = get_post( $template_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_template_not_found',
                __( 'Template not found.', 'oxybridge-wp' ),
                404
            );
        }

        // Update title if provided.
        if ( $title !== null ) {
            wp_update_post(
                array(
                    'ID'         => $template_id,
                    'post_title' => sanitize_text_field( $title ),
                )
            );
        }

        // Update tree if provided.
        if ( $tree !== null ) {
            if ( ! is_array( $tree ) || ! $this->is_valid_tree_structure( $tree ) ) {
                return $this->format_error(
                    'rest_invalid_tree',
                    __( 'Invalid tree structure provided.', 'oxybridge-wp' ),
                    400
                );
            }

            $this->save_document_tree( $template_id, $tree );
        }

        $post = get_post( $template_id );

        return $this->format_response(
            array(
                'success'  => true,
                'template' => $this->format_template( $post ),
            )
        );
    }

    /**
     * Delete template endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_template( \WP_REST_Request $request ) {
        $template_id = (int) $request->get_param( 'id' );
        $force       = $request->get_param( 'force' );

        $result = wp_delete_post( $template_id, $force );

        if ( ! $result ) {
            return $this->format_error(
                'rest_delete_failed',
                __( 'Failed to delete template.', 'oxybridge-wp' ),
                500
            );
        }

        return $this->format_response(
            array(
                'success' => true,
                'deleted' => $template_id,
            )
        );
    }

    /**
     * Get available template types.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_template_types( \WP_REST_Request $request ) {
        return $this->format_response(
            array(
                'types' => $this->get_available_template_types(),
            )
        );
    }

    /**
     * Get template post types.
     *
     * @since 1.1.0
     * @param string|null $template_type Optional specific type.
     * @return array Array of post types.
     */
    private function get_template_post_types( ?string $template_type = null ): array {
        $prefix = $this->is_breakdance_mode() ? 'breakdance_' : 'oxygen_';

        $types = array(
            'header'       => $prefix . 'header',
            'footer'       => $prefix . 'footer',
            'global_block' => $prefix . 'block',
            'popup'        => $prefix . 'popup',
            'template'     => $prefix . 'template',
        );

        if ( $template_type && isset( $types[ $template_type ] ) ) {
            return array( $types[ $template_type ] );
        }

        if ( empty( $template_type ) ) {
            return array_values( $types );
        }

        return array();
    }

    /**
     * Get post type for template type.
     *
     * @since 1.1.0
     * @param string $template_type The template type.
     * @return string|null Post type or null if invalid.
     */
    private function get_post_type_for_template_type( string $template_type ): ?string {
        $post_types = $this->get_template_post_types( $template_type );

        return ! empty( $post_types ) ? $post_types[0] : null;
    }

    /**
     * Get available template types.
     *
     * @since 1.1.0
     * @return array Array of template types.
     */
    private function get_available_template_types(): array {
        return array(
            array(
                'slug'        => 'header',
                'name'        => __( 'Header', 'oxybridge-wp' ),
                'description' => __( 'Site header templates.', 'oxybridge-wp' ),
            ),
            array(
                'slug'        => 'footer',
                'name'        => __( 'Footer', 'oxybridge-wp' ),
                'description' => __( 'Site footer templates.', 'oxybridge-wp' ),
            ),
            array(
                'slug'        => 'global_block',
                'name'        => __( 'Global Block', 'oxybridge-wp' ),
                'description' => __( 'Reusable content blocks.', 'oxybridge-wp' ),
            ),
            array(
                'slug'        => 'popup',
                'name'        => __( 'Popup', 'oxybridge-wp' ),
                'description' => __( 'Modal and popup templates.', 'oxybridge-wp' ),
            ),
            array(
                'slug'        => 'template',
                'name'        => __( 'Template', 'oxybridge-wp' ),
                'description' => __( 'Page templates.', 'oxybridge-wp' ),
            ),
        );
    }

    /**
     * Format template for response.
     *
     * @since 1.1.0
     * @param \WP_Post $post The template post.
     * @return array Formatted template data.
     */
    private function format_template( \WP_Post $post ): array {
        $template_type = $this->get_template_type_from_post_type( $post->post_type );
        $element_count = 0;

        $tree = $this->get_document_tree( $post->ID );
        if ( $tree !== false && isset( $tree['root']['children'] ) ) {
            $element_count = $this->count_elements_recursive( $tree['root']['children'] );
        }

        return array(
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'type'          => $template_type,
            'post_type'     => $post->post_type,
            'status'        => $post->post_status,
            'edit_url'      => $this->get_builder_edit_url( $post->ID ),
            'modified_at'   => $post->post_modified,
            'element_count' => $element_count,
        );
    }

    /**
     * Get template type from post type.
     *
     * @since 1.1.0
     * @param string $post_type The post type.
     * @return string Template type slug.
     */
    private function get_template_type_from_post_type( string $post_type ): string {
        $map = array(
            'breakdance_header'   => 'header',
            'breakdance_footer'   => 'footer',
            'breakdance_block'    => 'global_block',
            'breakdance_popup'    => 'popup',
            'breakdance_template' => 'template',
            'oxygen_header'       => 'header',
            'oxygen_footer'       => 'footer',
            'oxygen_block'        => 'global_block',
            'oxygen_popup'        => 'popup',
            'oxygen_template'     => 'template',
        );

        return $map[ $post_type ] ?? 'template';
    }
}
