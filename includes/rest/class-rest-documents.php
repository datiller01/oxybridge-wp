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

        // Class management endpoint - GET all element classes.
        register_rest_route(
            $this->get_namespace(),
            '/documents/(?P<id>\d+)/classes',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_document_classes' ),
                'permission_callback' => array( $this, 'check_read_permission' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'element_id' => array(
                        'description' => __( 'Filter by specific element ID.', 'oxybridge-wp' ),
                        'type'        => 'string',
                        'required'    => false,
                    ),
                ),
            )
        );

        // Class management endpoint - PUT to update element classes.
        register_rest_route(
            $this->get_namespace(),
            '/documents/(?P<id>\d+)/elements/(?P<element_id>[a-zA-Z0-9_-]+)/classes',
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_element_classes' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'element_id' => array(
                        'description'       => __( 'Element ID within the document.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'classes' => array(
                        'description'       => __( 'Array of CSS class names to set on the element.', 'oxybridge-wp' ),
                        'type'              => 'array',
                        'required'          => true,
                        'items'             => array(
                            'type' => 'string',
                        ),
                        'validate_callback' => array( $this, 'validate_class_names' ),
                    ),
                    'regenerate_css' => array(
                        'description' => __( 'Regenerate CSS cache after save.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                ),
            )
        );

        // Class management endpoint - DELETE to remove a specific class from an element.
        register_rest_route(
            $this->get_namespace(),
            '/documents/(?P<id>\d+)/elements/(?P<element_id>[a-zA-Z0-9_-]+)/classes/(?P<class_name>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_element_class' ),
                'permission_callback' => array( $this, 'check_write_permission' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Post ID.', 'oxybridge-wp' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'validate_callback' => array( $this, 'validate_post_id' ),
                    ),
                    'element_id' => array(
                        'description'       => __( 'Element ID within the document.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'class_name' => array(
                        'description'       => __( 'CSS class name to remove.', 'oxybridge-wp' ),
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'regenerate_css' => array(
                        'description' => __( 'Regenerate CSS cache after save.', 'oxybridge-wp' ),
                        'type'        => 'boolean',
                        'default'     => true,
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

        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return $this->format_error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                404
            );
        }

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

        // Return updated document.
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

    /**
     * Get all element classes from a document.
     *
     * Returns an array of elements with their associated CSS classes,
     * including both custom classes and built-in element classes.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_document_classes( \WP_REST_Request $request ) {
        $post_id    = (int) $request->get_param( 'id' );
        $element_id = $request->get_param( 'element_id' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                404
            );
        }

        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return $this->format_error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                404
            );
        }

        // Extract classes from all elements.
        $elements_with_classes = $this->extract_element_classes( $tree );

        // Filter by specific element if requested.
        if ( ! empty( $element_id ) ) {
            $elements_with_classes = array_filter(
                $elements_with_classes,
                function ( $element ) use ( $element_id ) {
                    // Cast both to string for comparison (tree IDs may be int).
                    return (string) $element['id'] === (string) $element_id;
                }
            );
            $elements_with_classes = array_values( $elements_with_classes );

            // Return 404 if specific element not found.
            if ( empty( $elements_with_classes ) ) {
                return $this->format_error(
                    'rest_element_not_found',
                    __( 'Element not found in document.', 'oxybridge-wp' ),
                    404
                );
            }
        }

        // Calculate summary statistics.
        $all_classes    = array();
        $elements_count = 0;

        foreach ( $elements_with_classes as $element ) {
            $elements_count++;
            $all_classes = array_merge( $all_classes, $element['classes'] );
        }

        $unique_classes = array_values( array_unique( $all_classes ) );

        return $this->format_response(
            array(
                'post_id'        => $post_id,
                'elements'       => $elements_with_classes,
                'summary'        => array(
                    'total_elements'     => $elements_count,
                    'unique_classes'     => $unique_classes,
                    'total_class_count'  => count( $unique_classes ),
                ),
            )
        );
    }

    /**
     * Update element classes endpoint callback.
     *
     * Sets the CSS classes for a specific element within a document tree.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_element_classes( \WP_REST_Request $request ) {
        $post_id        = (int) $request->get_param( 'id' );
        $element_id     = $request->get_param( 'element_id' );
        $classes        = $request->get_param( 'classes' );
        $regenerate_css = $request->get_param( 'regenerate_css' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                404
            );
        }

        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return $this->format_error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                404
            );
        }

        // Clean and deduplicate classes while preserving order.
        $classes = array_values( array_unique( array_filter( array_map( 'trim', $classes ) ) ) );

        // Find and update the element in the tree.
        $updated_tree = $this->update_element_in_tree( $tree, $element_id, $classes );

        if ( $updated_tree === false ) {
            return $this->format_error(
                'rest_element_not_found',
                __( 'Element not found in document tree.', 'oxybridge-wp' ),
                404
            );
        }

        // Save the updated tree.
        $saved = $this->save_document_tree( $post_id, $updated_tree );

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

        // Get the updated element's class info.
        $updated_element = $this->find_element_in_tree( $updated_tree, $element_id );

        return $this->format_response(
            array(
                'success'    => true,
                'post_id'    => $post_id,
                'element_id' => $element_id,
                'classes'    => $classes,
                'element'    => $updated_element ? array(
                    'id'   => $updated_element['id'],
                    'type' => $updated_element['data']['type'] ?? 'unknown',
                ) : null,
            )
        );
    }

    /**
     * Delete a specific class from an element.
     *
     * Removes a single CSS class from an element's class list.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_element_class( \WP_REST_Request $request ) {
        $post_id        = (int) $request->get_param( 'id' );
        $element_id     = $request->get_param( 'element_id' );
        $class_name     = $request->get_param( 'class_name' );
        $regenerate_css = $request->get_param( 'regenerate_css' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->format_error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                404
            );
        }

        $tree = $this->get_document_tree( $post_id );

        if ( $tree === false ) {
            return $this->format_error(
                'rest_tree_not_found',
                __( 'Could not retrieve document tree for this post.', 'oxybridge-wp' ),
                404
            );
        }

        // Find the element in the tree.
        $element = $this->find_element_in_tree( $tree, $element_id );

        if ( $element === null ) {
            return $this->format_error(
                'rest_element_not_found',
                __( 'Element not found in document tree.', 'oxybridge-wp' ),
                404
            );
        }

        // Extract current classes from the element.
        $element_type = $element['data']['type'] ?? 'unknown';
        $properties   = $element['data']['properties'] ?? array();
        $current_classes = $this->extract_classes_from_properties( $properties, $element_type );

        // Filter out built-in classes - we only manage custom classes.
        $custom_classes = $this->filter_custom_classes( $current_classes );

        // Check if the class exists on the element.
        if ( ! in_array( $class_name, $custom_classes, true ) ) {
            return $this->format_error(
                'rest_class_not_found',
                sprintf(
                    /* translators: %s: class name */
                    __( 'Class "%s" not found on element.', 'oxybridge-wp' ),
                    $class_name
                ),
                404
            );
        }

        // Remove the class from the list.
        $updated_classes = array_values( array_filter(
            $custom_classes,
            function ( $class ) use ( $class_name ) {
                return $class !== $class_name;
            }
        ) );

        // Update the element in the tree.
        $updated_tree = $this->update_element_in_tree( $tree, $element_id, $updated_classes );

        if ( $updated_tree === false ) {
            return $this->format_error(
                'rest_update_failed',
                __( 'Failed to update element in document tree.', 'oxybridge-wp' ),
                500
            );
        }

        // Save the updated tree.
        $saved = $this->save_document_tree( $post_id, $updated_tree );

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

        return $this->format_response(
            array(
                'success'       => true,
                'post_id'       => $post_id,
                'element_id'    => $element_id,
                'removed_class' => $class_name,
                'classes'       => $updated_classes,
            )
        );
    }

    /**
     * Validate CSS class names.
     *
     * Ensures all class names follow valid CSS identifier syntax.
     *
     * @since 1.1.0
     * @param array            $classes Array of class names.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_class_names( $classes, \WP_REST_Request $request, $param ) {
        if ( ! is_array( $classes ) ) {
            return new \WP_Error(
                'rest_invalid_param',
                __( 'Classes must be an array.', 'oxybridge-wp' ),
                array( 'status' => 400 )
            );
        }

        // CSS class name pattern: starts with letter, underscore, or hyphen,
        // followed by letters, digits, underscores, or hyphens.
        $pattern = '/^[a-zA-Z_-][a-zA-Z0-9_-]*$/';

        foreach ( $classes as $class ) {
            if ( ! is_string( $class ) ) {
                return new \WP_Error(
                    'rest_invalid_class',
                    __( 'Each class must be a string.', 'oxybridge-wp' ),
                    array( 'status' => 400 )
                );
            }

            $trimmed = trim( $class );
            if ( empty( $trimmed ) ) {
                continue; // Skip empty strings.
            }

            if ( ! preg_match( $pattern, $trimmed ) ) {
                return new \WP_Error(
                    'rest_invalid_class',
                    sprintf(
                        /* translators: %s: class name */
                        __( 'Invalid CSS class name: %s', 'oxybridge-wp' ),
                        $trimmed
                    ),
                    array( 'status' => 400 )
                );
            }
        }

        return true;
    }

    /**
     * Update an element's classes in the document tree.
     *
     * @since 1.1.0
     * @param array  $tree       The document tree.
     * @param string $element_id The element ID to update.
     * @param array  $classes    The new classes to set.
     * @return array|false The updated tree or false if element not found.
     */
    private function update_element_in_tree( array $tree, string $element_id, array $classes ) {
        $found = false;

        if ( isset( $tree['root']['children'] ) ) {
            $tree['root']['children'] = $this->update_element_classes_recursive(
                $tree['root']['children'],
                $element_id,
                $classes,
                $found
            );
        } elseif ( isset( $tree[0] ) ) {
            $tree = $this->update_element_classes_recursive( $tree, $element_id, $classes, $found );
        }

        return $found ? $tree : false;
    }

    /**
     * Recursively search and update element classes in children array.
     *
     * @since 1.1.0
     * @param array  $children   Array of child elements.
     * @param string $element_id The element ID to find.
     * @param array  $classes    The new classes to set.
     * @param bool   $found      Reference flag to indicate if element was found.
     * @return array The updated children array.
     */
    private function update_element_classes_recursive( array $children, string $element_id, array $classes, bool &$found ): array {
        foreach ( $children as $index => $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            // Check if this is the element we're looking for.
            // Cast both to string for comparison (tree IDs may be int, route param is string).
            if ( isset( $child['id'] ) && (string) $child['id'] === (string) $element_id ) {
                $children[ $index ] = $this->set_element_classes( $child, $classes );
                $found = true;
                return $children;
            }

            // Recurse into children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $children[ $index ]['children'] = $this->update_element_classes_recursive(
                    $child['children'],
                    $element_id,
                    $classes,
                    $found
                );

                if ( $found ) {
                    return $children;
                }
            }
        }

        return $children;
    }

    /**
     * Set classes on an element.
     *
     * Updates the element's properties to include the specified classes.
     * Stores classes in the standard 'attributes.className' path.
     *
     * @since 1.1.0
     * @param array $element The element to update.
     * @param array $classes The classes to set.
     * @return array The updated element.
     */
    private function set_element_classes( array $element, array $classes ): array {
        // Ensure data and properties structure exists.
        if ( ! isset( $element['data'] ) ) {
            $element['data'] = array();
        }
        if ( ! isset( $element['data']['properties'] ) ) {
            $element['data']['properties'] = array();
        }
        if ( ! isset( $element['data']['properties']['attributes'] ) ) {
            $element['data']['properties']['attributes'] = array();
        }

        // Set the className as a space-separated string (CSS standard).
        $element['data']['properties']['attributes']['className'] = implode( ' ', $classes );

        return $element;
    }

    /**
     * Find an element in the document tree by ID.
     *
     * @since 1.1.0
     * @param array  $tree       The document tree.
     * @param string $element_id The element ID to find.
     * @return array|null The element or null if not found.
     */
    private function find_element_in_tree( array $tree, string $element_id ): ?array {
        if ( isset( $tree['root']['children'] ) ) {
            return $this->find_element_recursive( $tree['root']['children'], $element_id );
        } elseif ( isset( $tree[0] ) ) {
            return $this->find_element_recursive( $tree, $element_id );
        }

        return null;
    }

    /**
     * Recursively find an element by ID.
     *
     * @since 1.1.0
     * @param array  $children   Array of child elements.
     * @param string $element_id The element ID to find.
     * @return array|null The element or null if not found.
     */
    private function find_element_recursive( array $children, string $element_id ): ?array {
        foreach ( $children as $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            // Cast both to string for comparison (tree IDs may be int, route param is string).
            if ( isset( $child['id'] ) && (string) $child['id'] === (string) $element_id ) {
                return $child;
            }

            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $found = $this->find_element_recursive( $child['children'], $element_id );
                if ( $found !== null ) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Extract CSS classes from all elements in a tree.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return array Array of elements with their classes.
     */
    private function extract_element_classes( array $tree ): array {
        $elements = array();

        if ( isset( $tree['root']['children'] ) ) {
            $elements = $this->extract_classes_recursive( $tree['root']['children'] );
        } elseif ( isset( $tree[0] ) ) {
            $elements = $this->extract_classes_recursive( $tree );
        }

        return $elements;
    }

    /**
     * Recursively extract classes from children array.
     *
     * @since 1.1.0
     * @param array $children Array of child elements.
     * @return array Array of elements with their classes.
     */
    private function extract_classes_recursive( array $children ): array {
        $elements = array();

        foreach ( $children as $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            $element_id   = $child['id'] ?? null;
            $element_type = $child['data']['type'] ?? 'unknown';
            $properties   = $child['data']['properties'] ?? array();

            // Extract classes from various property locations.
            $classes = $this->extract_classes_from_properties( $properties, $element_type );

            // Only include elements that have an ID.
            if ( $element_id !== null ) {
                $elements[] = array(
                    'id'             => $element_id,
                    'type'           => $element_type,
                    'classes'        => $classes,
                    'custom_classes' => $this->filter_custom_classes( $classes ),
                    'builtin_classes' => $this->filter_builtin_classes( $classes ),
                );
            }

            // Recurse into children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $elements = array_merge(
                    $elements,
                    $this->extract_classes_recursive( $child['children'] )
                );
            }
        }

        return $elements;
    }

    /**
     * Extract CSS classes from element properties.
     *
     * Checks multiple common property paths where classes can be stored:
     * - properties.attributes.className
     * - properties.className
     * - properties.settings.attributes.customCssClass
     * - properties.content.attributes.className
     *
     * @since 1.1.0
     * @param array  $properties  Element properties.
     * @param string $element_type Element type for context.
     * @return array Array of class names.
     */
    private function extract_classes_from_properties( array $properties, string $element_type ): array {
        $classes = array();

        // Common class property paths in Oxygen/Breakdance elements.
        $class_paths = array(
            array( 'attributes', 'className' ),
            array( 'className' ),
            array( 'settings', 'attributes', 'customCssClass' ),
            array( 'settings', 'attributes', 'className' ),
            array( 'content', 'attributes', 'className' ),
            array( 'design', 'attributes', 'className' ),
            array( 'advanced', 'attributes', 'className' ),
            array( 'customCssClass' ),
        );

        foreach ( $class_paths as $path ) {
            $value = $this->get_nested_value( $properties, $path );
            if ( ! empty( $value ) ) {
                // Handle both string and array formats.
                if ( is_string( $value ) ) {
                    $parsed = array_filter( array_map( 'trim', explode( ' ', $value ) ) );
                    $classes = array_merge( $classes, $parsed );
                } elseif ( is_array( $value ) ) {
                    $classes = array_merge( $classes, $value );
                }
            }
        }

        // Add element-type-based built-in classes.
        $builtin = $this->get_element_builtin_classes( $element_type );
        $classes = array_merge( $builtin, $classes );

        // Remove duplicates while preserving order.
        return array_values( array_unique( $classes ) );
    }

    /**
     * Get a nested value from an array using a path.
     *
     * @since 1.1.0
     * @param array $array The array to search.
     * @param array $path  The path as array of keys.
     * @return mixed|null The value or null if not found.
     */
    private function get_nested_value( array $array, array $path ) {
        $current = $array;

        foreach ( $path as $key ) {
            if ( ! is_array( $current ) || ! isset( $current[ $key ] ) ) {
                return null;
            }
            $current = $current[ $key ];
        }

        return $current;
    }

    /**
     * Get built-in CSS classes for an element type.
     *
     * @since 1.1.0
     * @param string $element_type The element type.
     * @return array Array of built-in class names.
     */
    private function get_element_builtin_classes( string $element_type ): array {
        // Map of element types to their built-in classes.
        $builtin_map = array(
            'EssentialElements\\Button'         => array( 'bde-button' ),
            'EssentialElements\\TextLink'       => array( 'breakdance-link' ),
            'EssentialElements\\Link'           => array( 'breakdance-link' ),
            'EssentialElements\\Heading'        => array( 'bde-heading' ),
            'EssentialElements\\Text'           => array( 'bde-text' ),
            'EssentialElements\\Image'          => array( 'bde-image' ),
            'EssentialElements\\Section'        => array( 'bde-section' ),
            'EssentialElements\\Div'            => array( 'bde-div' ),
            'EssentialElements\\Container'      => array( 'bde-container' ),
            'EssentialElements\\Column'         => array( 'bde-column' ),
            'EssentialElements\\Columns'        => array( 'bde-columns' ),
        );

        return $builtin_map[ $element_type ] ?? array();
    }

    /**
     * Filter to return only custom (non-built-in) classes.
     *
     * @since 1.1.0
     * @param array $classes All classes.
     * @return array Custom classes only.
     */
    private function filter_custom_classes( array $classes ): array {
        return array_values( array_filter(
            $classes,
            function ( $class ) {
                // Built-in classes typically start with bde-, breakdance-, or ee-.
                return ! preg_match( '/^(bde-|breakdance-|ee-|oxy-)/', $class );
            }
        ) );
    }

    /**
     * Filter to return only built-in classes.
     *
     * @since 1.1.0
     * @param array $classes All classes.
     * @return array Built-in classes only.
     */
    private function filter_builtin_classes( array $classes ): array {
        return array_values( array_filter(
            $classes,
            function ( $class ) {
                // Built-in classes typically start with bde-, breakdance-, or ee-.
                return preg_match( '/^(bde-|breakdance-|ee-|oxy-)/', $class );
            }
        ) );
    }

    // regenerate_post_css() is inherited from REST_Controller
}
