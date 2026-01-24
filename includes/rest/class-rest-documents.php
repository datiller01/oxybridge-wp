<?php
/**
 * REST Documents Controller.
 *
 * Handles document CRUD operations including reading and updating
 * Oxygen/Breakdance document trees.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

/**
 * Documents REST controller.
 *
 * @since 1.1.0
 */
class REST_Documents extends REST_Controller {

    /**
     * Register document routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/documents/(?P<id>\d+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'read_document' ),
                    'permission_callback' => array( $this, 'check_read_permission' ),
                    'args'                => array(
                        'id'               => array(
                            'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                        'include_metadata' => array(
                            'description' => __( 'Include document metadata.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => true,
                        ),
                        'flatten_elements' => array(
                            'description' => __( 'Return flat list of elements instead of tree.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => false,
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_document' ),
                    'permission_callback' => array( $this, 'check_write_permission' ),
                    'args'                => array(
                        'id'   => array(
                            'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                            'type'              => 'integer',
                            'required'          => true,
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                        'tree' => array(
                            'description' => __( 'Document tree structure.', 'oxybridge-wp' ),
                            'type'        => array( 'object', 'array' ),
                            'required'    => true,
                        ),
                        'regenerate_css' => array(
                            'description' => __( 'Regenerate CSS cache after save.', 'oxybridge-wp' ),
                            'type'        => 'boolean',
                            'default'     => true,
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/regenerate-css/(?P<id>\d+)',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'regenerate_css' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                ),
            )
        );
    }

    /**
     * Read document endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function read_document( \WP_REST_Request $request ) {
        $post_id          = (int) $request->get_param( 'id' );
        $include_metadata = $request->get_param( 'include_metadata' );
        $flatten_elements = $request->get_param( 'flatten_elements' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                404
            );
        }

        // get_document_tree() applies ensure_tree_integrity() which adds required
        // IO-TS properties (_nextNodeId, exportedLookupTable) for Breakdance builder.
        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return $this->format_error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                404
            );
        }

        // Tree includes _nextNodeId (required) and exportedLookupTable for IO-TS validation.
        $response_data = array(
            'post_id' => $post_id,
            'tree'    => $tree,
        );

        // Include flattened elements if requested.
        if ( $flatten_elements ) {
            $response_data['elements'] = $this->flatten_document_tree( $tree );
            unset( $response_data['tree'] );
        }

        // Include metadata if requested.
        if ( $include_metadata ) {
            $response_data['metadata'] = $this->get_document_metadata( $post );
        }

        // Add element count and types.
        $response_data['element_count'] = $this->count_document_elements( $tree );
        $response_data['element_types'] = $this->get_document_element_types( $tree );

        return $this->format_response( $response_data );
    }

    /**
     * Update document endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_document( \WP_REST_Request $request ) {
        $post_id        = (int) $request->get_param( 'id' );
        $tree           = $request->get_param( 'tree' );
        $regenerate_css = $request->get_param( 'regenerate_css' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                404
            );
        }

        // Validate tree structure.
        if ( ! is_array( $tree ) || ! $this->is_valid_tree_structure( $tree ) ) {
            return $this->format_error(
                'rest_invalid_tree',
                __( 'Invalid tree structure provided.', 'oxybridge-wp' ),
                400
            );
        }

        // Save the tree.
        $saved = $this->save_document_tree( $post_id, $tree );

        if ( ! $saved ) {
            return $this->format_error(
                'rest_save_failed',
                __( 'Failed to save document tree.', 'oxybridge-wp' ),
                500
            );
        }

        // Regenerate CSS if requested.
        if ( $regenerate_css ) {
            $this->regenerate_post_css( $post_id );
        }

        // Return updated document with IO-TS compliant tree (_nextNodeId included).
        $updated_tree = $this->get_document_tree( $post_id );

        return $this->format_response(
            array(
                'success'       => true,
                'post_id'       => $post_id,
                'tree'          => $updated_tree,
                'element_count' => $this->count_document_elements( $updated_tree ),
            )
        );
    }

    /**
     * Regenerate CSS endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function regenerate_css( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'id' );

        if ( ! $this->has_oxygen_content( $post_id ) ) {
            return $this->format_error(
                'rest_no_oxygen_content',
                __( 'This post does not have Oxygen content.', 'oxybridge-wp' ),
                400
            );
        }

        $result = $this->regenerate_post_css( $post_id );

        return $this->format_response(
            array(
                'success' => $result,
                'post_id' => $post_id,
                'message' => $result
                    ? __( 'CSS regenerated successfully.', 'oxybridge-wp' )
                    : __( 'CSS regeneration function not available.', 'oxybridge-wp' ),
            )
        );
    }

    /**
     * Flatten a document tree into a list of elements.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return array Flat array of elements.
     */
    private function flatten_document_tree( array $tree ): array {
        $elements = array();

        if ( isset( $tree['root']['children'] ) ) {
            $elements = $this->extract_elements_recursive( $tree['root']['children'] );
        } elseif ( isset( $tree[0] ) ) {
            $elements = $this->extract_elements_recursive( $tree );
        }

        return $elements;
    }

    /**
     * Recursively extract elements from children array.
     *
     * @since 1.1.0
     * @param array  $children Array of child elements.
     * @param string $path     Current path in tree.
     * @param int    $depth    Current depth level.
     * @return array Flat array of elements.
     */
    private function extract_elements_recursive( array $children, string $path = '', int $depth = 0 ): array {
        $elements = array();

        foreach ( $children as $index => $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            $current_path = $path ? "{$path}.{$index}" : (string) $index;
            $element_id   = $child['id'] ?? null;
            $element_type = $child['data']['type'] ?? 'unknown';

            $elements[] = array(
                'id'         => $element_id,
                'type'       => $element_type,
                'path'       => $current_path,
                'depth'      => $depth,
                'properties' => $child['data']['properties'] ?? array(),
                'has_children' => ! empty( $child['children'] ),
            );

            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $elements = array_merge(
                    $elements,
                    $this->extract_elements_recursive( $child['children'], $current_path, $depth + 1 )
                );
            }
        }

        return $elements;
    }

    /**
     * Get document metadata.
     *
     * @since 1.1.0
     * @param \WP_Post $post The post object.
     * @return array Metadata array.
     */
    private function get_document_metadata( \WP_Post $post ): array {
        return array(
            'post_id'      => $post->ID,
            'title'        => $post->post_title,
            'post_type'    => $post->post_type,
            'status'       => $post->post_status,
            'author_id'    => (int) $post->post_author,
            'created_at'   => $post->post_date,
            'modified_at'  => $post->post_modified,
            'permalink'    => get_permalink( $post->ID ),
            'edit_url'     => $this->get_builder_edit_url( $post->ID ),
        );
    }

    /**
     * Count total elements in a document tree.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return int Total element count.
     */
    private function count_document_elements( array $tree ): int {
        if ( isset( $tree['root']['children'] ) ) {
            return $this->count_elements_recursive( $tree['root']['children'] );
        }

        if ( isset( $tree[0] ) ) {
            return $this->count_elements_recursive( $tree );
        }

        return 0;
    }

    /**
     * Get unique element types from a document tree.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return array Array of unique element type slugs.
     */
    private function get_document_element_types( array $tree ): array {
        $elements = $this->flatten_document_tree( $tree );
        $types = array_column( $elements, 'type' );

        return array_values( array_unique( $types ) );
    }

    // regenerate_post_css() is inherited from REST_Controller
}
