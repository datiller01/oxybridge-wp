<?php
/**
 * REST Pages Controller.
 *
 * Handles page listing and CRUD operations.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

/**
 * Pages REST controller.
 *
 * @since 1.1.0
 */
class REST_Pages extends REST_Controller {

    /**
     * Register page routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/pages',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'list_pages' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                    'args'                => array(
                        'post_type'          => array(
                            'description' => __( 'Filter by post type.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => 'page',
                        ),
                        'has_oxygen_content' => array(
                            'description' => __( 'Only return posts with Oxygen content.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => false,
                        ),
                        'status'             => array(
                            'description' => __( 'Filter by post status.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => 'publish',
                        ),
                        'search'             => array(
                            'description' => __( 'Search by title.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => '',
                        ),
                        'per_page'           => array(
                            'description' => __( 'Results per page.', 'oxybridge-wp' ),
                            'type'        => 'integer',
                            'default'     => 20,
                            'maximum'     => 100,
                        ),
                        'page'               => array(
                            'description' => __( 'Page number.', 'oxybridge-wp' ),
                            'type'        => 'integer',
                            'default'     => 1,
                            'minimum'     => 1,
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_page' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'title'         => array(
                            'description' => __( 'Page title.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'required'    => true,
                        ),
                        'post_type'     => array(
                            'description' => __( 'Post type.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => 'page',
                        ),
                        'status'        => array(
                            'description' => __( 'Post status.', 'oxybridge-wp' ),
                            'type'        => 'string',
                            'default'     => 'draft',
                        ),
                        'tree'          => array(
                            'description' => __( 'Initial document tree.', 'oxybridge-wp' ),
                            'type'        => array( 'object', 'array' ),
                            'default'     => null,
                        ),
                        'enable_oxygen' => array(
                            'description' => __( 'Enable Oxygen for this page.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => true,
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/pages/(?P<id>\d+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_page' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                    'args'                => array(
                        'id' => array(
                            'description'       => __( 'Page ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_page' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'id'     => array(
                            'description'       => __( 'Page ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                        'title'  => array(
                            'description' => __( 'Page title.', 'oxybridge-wp' ),
                            'type'        => 'string',
                        ),
                        'status' => array(
                            'description' => __( 'Post status.', 'oxybridge-wp' ),
                            'type'        => 'string',
                        ),
                        'tree'   => array(
                            'description' => __( 'Document tree.', 'oxybridge-wp' ),
                            'type'        => array( 'object', 'array' ),
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_page' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'id'    => array(
                            'description'       => __( 'Page ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                        'force' => array(
                            'description' => __( 'Permanently delete instead of trash.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => false,
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * List pages endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function list_pages( \WP_REST_Request $request ) {
        $post_type          = $request->get_param( 'post_type' );
        $has_oxygen_content = $request->get_param( 'has_oxygen_content' );
        $status             = $request->get_param( 'status' );
        $search             = $request->get_param( 'search' );
        $per_page           = (int) $request->get_param( 'per_page' );
        $page               = (int) $request->get_param( 'page' );

        $post_types = $this->get_page_post_types( $post_type );

        $query_args = array(
            'post_type'      => $post_types,
            'post_status'    => $status,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }

        $query = new \WP_Query( $query_args );
        $pages = array();

        foreach ( $query->posts as $post ) {
            $has_oxygen = $this->has_oxygen_content( $post->ID );

            if ( $has_oxygen_content && ! $has_oxygen ) {
                continue;
            }

            $pages[] = $this->format_page( $post, $has_oxygen );
        }

        return $this->format_response(
            array(
                'pages'       => $pages,
                'total'       => $has_oxygen_content ? count( $pages ) : $query->found_posts,
                'total_pages' => $has_oxygen_content ? 1 : $query->max_num_pages,
                'page'        => $page,
            )
        );
    }

    /**
     * Get single page endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_page( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'id' );
        $post = get_post( $post_id );

        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Page not found.', 'oxybridge-wp' ),
                404
            );
        }

        return $this->format_response( $this->format_page( $post, $this->has_oxygen_content( $post_id ) ) );
    }

    /**
     * Create page endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_page( \WP_REST_Request $request ) {
        $title         = sanitize_text_field( $request->get_param( 'title' ) );
        $post_type     = sanitize_key( $request->get_param( 'post_type' ) );
        $status        = sanitize_key( $request->get_param( 'status' ) );
        $tree          = $request->get_param( 'tree' );
        $enable_oxygen = $request->get_param( 'enable_oxygen' );

        // Create the post.
        $post_id = wp_insert_post(
            array(
                'post_title'  => $title,
                'post_type'   => $post_type,
                'post_status' => $status,
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
        } elseif ( $enable_oxygen ) {
            $empty_tree = $this->create_empty_tree();
            $this->save_document_tree( $post_id, $empty_tree );
        }

        $post = get_post( $post_id );

        return $this->format_response(
            array(
                'success' => true,
                'page'    => $this->format_page( $post, $this->has_oxygen_content( $post_id ) ),
            ),
            201
        );
    }

    /**
     * Update page endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_page( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'id' );
        $title   = $request->get_param( 'title' );
        $status  = $request->get_param( 'status' );
        $tree    = $request->get_param( 'tree' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Page not found.', 'oxybridge-wp' ),
                404
            );
        }

        // Update post fields.
        $update_args = array( 'ID' => $post_id );

        if ( $title !== null ) {
            $update_args['post_title'] = sanitize_text_field( $title );
        }

        if ( $status !== null ) {
            $update_args['post_status'] = sanitize_key( $status );
        }

        if ( count( $update_args ) > 1 ) {
            $result = wp_update_post( $update_args, true );

            if ( is_wp_error( $result ) ) {
                return $this->format_error(
                    'rest_update_failed',
                    $result->get_error_message(),
                    500
                );
            }
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

            $this->save_document_tree( $post_id, $tree );
        }

        $post = get_post( $post_id );

        return $this->format_response(
            array(
                'success' => true,
                'page'    => $this->format_page( $post, $this->has_oxygen_content( $post_id ) ),
            )
        );
    }

    /**
     * Delete page endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_page( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'id' );
        $force   = $request->get_param( 'force' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Page not found.', 'oxybridge-wp' ),
                404
            );
        }

        $result = wp_delete_post( $post_id, $force );

        if ( ! $result ) {
            return $this->format_error(
                'rest_delete_failed',
                __( 'Failed to delete page.', 'oxybridge-wp' ),
                500
            );
        }

        return $this->format_response(
            array(
                'success' => true,
                'deleted' => $post_id,
                'trashed' => ! $force,
            )
        );
    }

    /**
     * Get valid post types for page queries.
     *
     * @since 1.1.0
     * @param string $post_type Requested post type.
     * @return array Array of post types.
     */
    private function get_page_post_types( string $post_type ): array {
        if ( $post_type === 'any' || $post_type === 'all' ) {
            return get_post_types( array( 'public' => true ), 'names' );
        }

        return array( $post_type );
    }

    /**
     * Format page data for response.
     *
     * @since 1.1.0
     * @param \WP_Post $post       The post object.
     * @param bool     $has_oxygen Whether post has Oxygen content.
     * @return array Formatted page data.
     */
    private function format_page( \WP_Post $post, bool $has_oxygen = false ): array {
        $element_count = 0;

        if ( $has_oxygen ) {
            $tree = $this->get_document_tree( $post->ID );
            if ( $tree !== false && isset( $tree['root']['children'] ) ) {
                $element_count = $this->count_elements_recursive( $tree['root']['children'] );
            }
        }

        return array(
            'id'                 => $post->ID,
            'title'              => $post->post_title,
            'post_type'          => $post->post_type,
            'status'             => $post->post_status,
            'permalink'          => get_permalink( $post->ID ),
            'edit_url'           => $this->get_builder_edit_url( $post->ID ),
            'modified_at'        => $post->post_modified,
            'author'             => get_the_author_meta( 'display_name', $post->post_author ),
            'has_oxygen_content' => $has_oxygen,
            'element_count'      => $element_count,
        );
    }
}
