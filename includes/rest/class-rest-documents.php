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
     * Validates the tree structure and provides detailed error feedback for AI agents.
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

        // Validate tree structure with detailed feedback.
        $validation = $this->validate_document_tree( $tree );
        if ( ! $validation['valid'] ) {
            return new \WP_REST_Response(
                array(
                    'code'          => 'rest_invalid_tree',
                    'message'       => __( 'Invalid tree structure provided. See errors for details.', 'oxybridge-wp' ),
                    'valid'         => false,
                    'errors'        => $validation['errors'],
                    'warnings'      => $validation['warnings'],
                    'error_count'   => count( $validation['errors'] ),
                    'warning_count' => count( $validation['warnings'] ),
                    '_links'        => $this->get_response_links(),
                ),
                400
            );
        }

        // Include warnings in save response if any.
        $warnings = $validation['warnings'];

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

        $response_data = array(
            'success'       => true,
            'post_id'       => $post_id,
            'tree'          => $updated_tree,
            'element_count' => $this->count_document_elements( $updated_tree ),
        );

        // Include warnings if any were detected during validation.
        if ( ! empty( $warnings ) ) {
            $response_data['warnings']      = $warnings;
            $response_data['warning_count'] = count( $warnings );
        }

        return $this->format_response( $response_data );
    }

    /**
     * Validate document tree structure with detailed error feedback.
     *
     * Provides actionable error messages with property paths, expected types,
     * and examples for AI agents to correct their requests.
     *
     * @since 1.1.0
     * @param mixed $tree The tree to validate.
     * @return array Validation result with 'valid', 'errors', and 'warnings'.
     */
    private function validate_document_tree( $tree ): array {
        $errors   = array();
        $warnings = array();

        // Basic type validation.
        if ( ! is_array( $tree ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_tree_type',
                'tree',
                'Tree must be an object/array.',
                'object',
                array(
                    'root'   => array(
                        'id'       => 1,
                        'data'     => array( 'type' => 'root', 'properties' => null ),
                        'children' => array(),
                    ),
                    'status' => 'exported',
                )
            );

            return array(
                'valid'    => false,
                'errors'   => $errors,
                'warnings' => $warnings,
            );
        }

        // Check for root property.
        if ( ! isset( $tree['root'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_root',
                'root',
                'Tree must have a root property.',
                'object',
                array(
                    'id'       => 1,
                    'data'     => array( 'type' => 'root', 'properties' => null ),
                    'children' => array(),
                )
            );
        } elseif ( ! is_array( $tree['root'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_root_type',
                'root',
                'Root must be an object.',
                'object',
                array(
                    'id'       => 1,
                    'data'     => array( 'type' => 'root', 'properties' => null ),
                    'children' => array(),
                )
            );
        } else {
            // Validate root structure.
            $this->validate_document_root( $tree['root'], $errors, $warnings );
        }

        // Check for status field.
        if ( ! isset( $tree['status'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_status',
                'status',
                'Tree must have a status property set to "exported".',
                'string',
                'exported'
            );
        } elseif ( 'exported' !== $tree['status'] ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_status',
                'status',
                'Status must be "exported" for valid Oxygen trees.',
                'string (literal "exported")',
                'exported'
            );
        }

        // Warn about fields that will be removed.
        if ( isset( $tree['_nextNodeId'] ) ) {
            $warnings[] = array(
                'code'    => 'unnecessary_next_node_id',
                'path'    => '_nextNodeId',
                'message' => '_nextNodeId should NOT be included in the tree. It will be automatically removed during save.',
                'action'  => 'Remove this property from your tree.',
            );
        }

        if ( isset( $tree['exportedLookupTable'] ) ) {
            $warnings[] = array(
                'code'    => 'unnecessary_exported_lookup_table',
                'path'    => 'exportedLookupTable',
                'message' => 'exportedLookupTable should NOT be included in the tree. It will be automatically removed during save.',
                'action'  => 'Remove this property from your tree.',
            );
        }

        return array(
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
        );
    }

    /**
     * Validate the root element structure.
     *
     * @since 1.1.0
     * @param array $root     Root element data.
     * @param array $errors   Reference to errors array.
     * @param array $warnings Reference to warnings array.
     * @return void
     */
    private function validate_document_root( array $root, array &$errors, array &$warnings ): void {
        // Validate root.id.
        if ( ! isset( $root['id'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_root_id',
                'root.id',
                'Root must have an id property.',
                'integer',
                1
            );
        } elseif ( ! is_int( $root['id'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_root_id_type',
                'root.id',
                'Root id must be an integer, not a string. Received: ' . gettype( $root['id'] ),
                'integer',
                1
            );
        }

        // Validate root.data.
        if ( ! isset( $root['data'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_root_data',
                'root.data',
                'Root must have a data property.',
                'object',
                array( 'type' => 'root', 'properties' => null )
            );
        } elseif ( ! is_array( $root['data'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_root_data_type',
                'root.data',
                'Root data must be an object.',
                'object',
                array( 'type' => 'root', 'properties' => null )
            );
        } else {
            // Validate root.data.type.
            if ( ! isset( $root['data']['type'] ) ) {
                $errors[] = $this->create_tree_validation_error(
                    'missing_root_data_type',
                    'root.data.type',
                    'Root data must have a type property.',
                    'string',
                    'root'
                );
            } elseif ( 'root' !== $root['data']['type'] ) {
                $errors[] = $this->create_tree_validation_error(
                    'invalid_root_data_type_value',
                    'root.data.type',
                    'Root data type must be lowercase "root", not "' . $root['data']['type'] . '".',
                    'string (literal "root")',
                    'root'
                );
            }

            // Validate root.data.properties.
            if ( ! array_key_exists( 'properties', $root['data'] ) ) {
                $errors[] = $this->create_tree_validation_error(
                    'missing_root_data_properties',
                    'root.data.properties',
                    'Root data must have a properties property.',
                    'null',
                    null
                );
            } elseif ( null !== $root['data']['properties'] ) {
                $warnings[] = array(
                    'code'    => 'non_null_root_properties',
                    'path'    => 'root.data.properties',
                    'message' => 'Root data properties should be null for valid Oxygen trees. Found: ' . gettype( $root['data']['properties'] ),
                    'action'  => 'Set root.data.properties to null.',
                );
            }
        }

        // Validate root.children.
        if ( ! isset( $root['children'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_root_children',
                'root.children',
                'Root must have a children array.',
                'array',
                array()
            );
        } elseif ( ! is_array( $root['children'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_root_children_type',
                'root.children',
                'Root children must be an array.',
                'array',
                array()
            );
        } else {
            // Validate each child element.
            $root_id = isset( $root['id'] ) && is_int( $root['id'] ) ? $root['id'] : 1;
            foreach ( $root['children'] as $index => $child ) {
                $this->validate_document_child( $child, "root.children[$index]", $root_id, $errors, $warnings );
            }
        }
    }

    /**
     * Validate a child element structure.
     *
     * @since 1.1.0
     * @param mixed  $element   Element to validate.
     * @param string $path      Current property path.
     * @param int    $parent_id Expected parent ID.
     * @param array  $errors    Reference to errors array.
     * @param array  $warnings  Reference to warnings array.
     * @return void
     */
    private function validate_document_child( $element, string $path, int $parent_id, array &$errors, array &$warnings ): void {
        if ( ! is_array( $element ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_element_type',
                $path,
                'Element must be an object.',
                'object',
                array(
                    'id'        => 100,
                    'data'      => array( 'type' => 'EssentialElements\\Heading', 'properties' => null ),
                    'children'  => array(),
                    '_parentId' => 1,
                )
            );
            return;
        }

        // Validate element.id.
        if ( ! isset( $element['id'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_element_id',
                "{$path}.id",
                'Element must have an id property.',
                'integer',
                100
            );
        } elseif ( ! is_int( $element['id'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_element_id_type',
                "{$path}.id",
                'Element id must be an integer, not ' . gettype( $element['id'] ) . '.',
                'integer',
                100
            );
        }

        // Validate element.data.
        if ( ! isset( $element['data'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_element_data',
                "{$path}.data",
                'Element must have a data property.',
                'object',
                array( 'type' => 'EssentialElements\\Heading', 'properties' => null )
            );
        } elseif ( ! is_array( $element['data'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_element_data_type',
                "{$path}.data",
                'Element data must be an object.',
                'object',
                array( 'type' => 'EssentialElements\\Heading', 'properties' => null )
            );
        } else {
            // Validate element.data.type.
            if ( ! isset( $element['data']['type'] ) ) {
                $errors[] = $this->create_tree_validation_error(
                    'missing_element_type',
                    "{$path}.data.type",
                    'Element data must have a type property.',
                    'string',
                    'EssentialElements\\Heading'
                );
            } elseif ( ! is_string( $element['data']['type'] ) ) {
                $errors[] = $this->create_tree_validation_error(
                    'invalid_element_type_type',
                    "{$path}.data.type",
                    'Element data type must be a string.',
                    'string',
                    'EssentialElements\\Heading'
                );
            } else {
                // Validate element type has valid namespace.
                $this->validate_document_element_type( $element['data']['type'], "{$path}.data.type", $errors, $warnings );
            }

            // Validate element.data.properties exists.
            if ( ! array_key_exists( 'properties', $element['data'] ) ) {
                $errors[] = $this->create_tree_validation_error(
                    'missing_element_properties',
                    "{$path}.data.properties",
                    'Element data must have a properties property (can be null or object).',
                    'object|null',
                    null
                );
            }
        }

        // Validate element.children.
        if ( ! isset( $element['children'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_element_children',
                "{$path}.children",
                'Element must have a children array (use empty [] for leaf nodes).',
                'array',
                array()
            );
        } elseif ( ! is_array( $element['children'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_element_children_type',
                "{$path}.children",
                'Element children must be an array.',
                'array',
                array()
            );
        } else {
            // Recursively validate child elements.
            $element_id = isset( $element['id'] ) && is_int( $element['id'] ) ? $element['id'] : 0;
            foreach ( $element['children'] as $child_index => $child ) {
                $this->validate_document_child( $child, "{$path}.children[$child_index]", $element_id, $errors, $warnings );
            }
        }

        // Validate element._parentId.
        if ( ! isset( $element['_parentId'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'missing_parent_id',
                "{$path}._parentId",
                'Element must have a _parentId property referencing its parent.',
                'integer',
                $parent_id
            );
        } elseif ( ! is_int( $element['_parentId'] ) ) {
            $errors[] = $this->create_tree_validation_error(
                'invalid_parent_id_type',
                "{$path}._parentId",
                'Element _parentId must be an integer, not ' . gettype( $element['_parentId'] ) . '.',
                'integer',
                $parent_id
            );
        } elseif ( $element['_parentId'] !== $parent_id ) {
            $warnings[] = array(
                'code'     => 'parent_id_mismatch',
                'path'     => "{$path}._parentId",
                'message'  => "Element _parentId ({$element['_parentId']}) does not match expected parent ID ({$parent_id}).",
                'expected' => $parent_id,
                'actual'   => $element['_parentId'],
                'action'   => "Set _parentId to {$parent_id}.",
            );
        }
    }

    /**
     * Validate element type has valid namespace prefix.
     *
     * @since 1.1.0
     * @param string $type     Element type string.
     * @param string $path     Property path for error reporting.
     * @param array  $errors   Reference to errors array.
     * @param array  $warnings Reference to warnings array.
     * @return void
     */
    private function validate_document_element_type( string $type, string $path, array &$errors, array &$warnings ): void {
        $valid_namespaces = array(
            'EssentialElements\\',
            'OxygenElements\\',
        );

        $has_valid_namespace = false;
        foreach ( $valid_namespaces as $namespace ) {
            if ( 0 === strpos( $type, $namespace ) ) {
                $has_valid_namespace = true;
                break;
            }
        }

        if ( ! $has_valid_namespace ) {
            // Try to suggest a corrected type.
            $common_types = array(
                'heading'   => 'EssentialElements\\Heading',
                'text'      => 'EssentialElements\\Text',
                'button'    => 'EssentialElements\\Button',
                'image'     => 'EssentialElements\\Image2',
                'container' => 'OxygenElements\\Container',
                'section'   => 'EssentialElements\\Section',
                'div'       => 'EssentialElements\\Div',
                'icon'      => 'EssentialElements\\Icon',
                'link'      => 'EssentialElements\\Link',
                'menu'      => 'EssentialElements\\WpMenu',
            );

            $type_lower  = strtolower( $type );
            $suggestion  = $common_types[ $type_lower ] ?? null;

            $error_data = array(
                'code'     => 'invalid_element_namespace',
                'path'     => $path,
                'message'  => "Element type '{$type}' is missing a valid namespace prefix.",
                'expected' => 'string with EssentialElements\\\\ or OxygenElements\\\\ prefix',
                'example'  => 'EssentialElements\\Heading',
            );

            if ( $suggestion ) {
                $error_data['suggestion'] = $suggestion;
                $error_data['action']     = "Did you mean '{$suggestion}'?";
            } else {
                $error_data['action'] = 'Add EssentialElements\\\\ or OxygenElements\\\\ prefix to your element type.';
            }

            $error_data['valid_namespaces'] = $valid_namespaces;
            $error_data['common_types']     = $common_types;

            $errors[] = $error_data;
        }
    }

    /**
     * Create a validation error with detailed information.
     *
     * @since 1.1.0
     * @param string $code     Error code.
     * @param string $path     Property path where error occurred.
     * @param string $message  Human-readable error message.
     * @param string $expected Expected type or value.
     * @param mixed  $example  Example of valid value.
     * @return array Validation error array.
     */
    private function create_tree_validation_error( string $code, string $path, string $message, string $expected, $example ): array {
        return array(
            'code'     => $code,
            'path'     => $path,
            'message'  => $message,
            'expected' => $expected,
            'example'  => $example,
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
