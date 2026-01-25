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
        $schema_file = OXYBRIDGE_PLUGIN_DIR . '/ai/schema-simplified.json';

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
     * Provides detailed validation with property paths, expected types, and examples.
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
            $errors[] = $this->create_validation_error(
                'invalid_type',
                'tree',
                'Tree must be an object/array.',
                'object',
                array( 'root' => array( 'id' => 1, 'data' => array( 'type' => 'root', 'properties' => null ), 'children' => array() ), 'status' => 'exported' )
            );

            return $this->format_response(
                array(
                    'valid'    => false,
                    'errors'   => $errors,
                    'warnings' => $warnings,
                )
            );
        }

        // Check for root property.
        if ( ! isset( $tree['root'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_root',
                'root',
                'Tree must have a root property.',
                'object',
                array( 'id' => 1, 'data' => array( 'type' => 'root', 'properties' => null ), 'children' => array() )
            );
        } elseif ( ! is_array( $tree['root'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_root_type',
                'root',
                'Root must be an object.',
                'object',
                array( 'id' => 1, 'data' => array( 'type' => 'root', 'properties' => null ), 'children' => array() )
            );
        } else {
            // Validate root structure.
            $this->validate_root_element( $tree['root'], $errors, $warnings );
        }

        // Check for status field.
        if ( ! isset( $tree['status'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_status',
                'status',
                'Tree must have a status property.',
                'string',
                'exported'
            );
        } elseif ( 'exported' !== $tree['status'] ) {
            $errors[] = $this->create_validation_error(
                'invalid_status',
                'status',
                'Status must be "exported" for valid Oxygen trees.',
                'string (literal "exported")',
                'exported'
            );
        }

        // Warn if _nextNodeId or exportedLookupTable are present.
        if ( isset( $tree['_nextNodeId'] ) ) {
            $warnings[] = array(
                'code'    => 'unnecessary_next_node_id',
                'path'    => '_nextNodeId',
                'message' => '_nextNodeId should NOT be included in the tree. It will be removed automatically. Working Oxygen documents do not have this field.',
                'action'  => 'Remove this property from your tree.',
            );
        }

        if ( isset( $tree['exportedLookupTable'] ) ) {
            $warnings[] = array(
                'code'    => 'unnecessary_exported_lookup_table',
                'path'    => 'exportedLookupTable',
                'message' => 'exportedLookupTable should NOT be included in the tree. It will be removed automatically. Working Oxygen documents do not have this field.',
                'action'  => 'Remove this property from your tree.',
            );
        }

        $valid = empty( $errors );

        return $this->format_response(
            array(
                'valid'         => $valid,
                'errors'        => $errors,
                'warnings'      => $warnings,
                'error_count'   => count( $errors ),
                'warning_count' => count( $warnings ),
            )
        );
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
    private function create_validation_error( string $code, string $path, string $message, string $expected, $example ): array {
        return array(
            'code'     => $code,
            'path'     => $path,
            'message'  => $message,
            'expected' => $expected,
            'example'  => $example,
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
    private function validate_root_element( array $root, array &$errors, array &$warnings ): void {
        // Validate root.id.
        if ( ! isset( $root['id'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_root_id',
                'root.id',
                'Root must have an id property.',
                'integer',
                1
            );
        } elseif ( ! is_int( $root['id'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_root_id_type',
                'root.id',
                'Root id must be an integer, not a string.',
                'integer',
                1
            );
        }

        // Validate root.data.
        if ( ! isset( $root['data'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_root_data',
                'root.data',
                'Root must have a data property.',
                'object',
                array( 'type' => 'root', 'properties' => null )
            );
        } elseif ( ! is_array( $root['data'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_root_data_type',
                'root.data',
                'Root data must be an object.',
                'object',
                array( 'type' => 'root', 'properties' => null )
            );
        } else {
            // Validate root.data.type.
            if ( ! isset( $root['data']['type'] ) ) {
                $errors[] = $this->create_validation_error(
                    'missing_root_data_type',
                    'root.data.type',
                    'Root data must have a type property.',
                    'string',
                    'root'
                );
            } elseif ( 'root' !== $root['data']['type'] ) {
                $errors[] = $this->create_validation_error(
                    'invalid_root_data_type_value',
                    'root.data.type',
                    'Root data type must be lowercase "root".',
                    'string (literal "root")',
                    'root'
                );
            }

            // Validate root.data.properties.
            if ( ! array_key_exists( 'properties', $root['data'] ) ) {
                $errors[] = $this->create_validation_error(
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
                    'message' => 'Root data properties should be null for valid Oxygen trees.',
                    'action'  => 'Set root.data.properties to null.',
                );
            }
        }

        // Validate root.children.
        if ( ! isset( $root['children'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_root_children',
                'root.children',
                'Root must have a children array.',
                'array',
                array()
            );
        } elseif ( ! is_array( $root['children'] ) ) {
            $errors[] = $this->create_validation_error(
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
                $this->validate_child_element( $child, "root.children[$index]", $root_id, $errors, $warnings );
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
    private function validate_child_element( $element, string $path, int $parent_id, array &$errors, array &$warnings ): void {
        if ( ! is_array( $element ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_element_type',
                $path,
                'Element must be an object.',
                'object',
                array( 'id' => 100, 'data' => array( 'type' => 'EssentialElements\\Heading', 'properties' => null ), 'children' => array(), '_parentId' => 1 )
            );
            return;
        }

        // Validate element.id.
        if ( ! isset( $element['id'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_element_id',
                "{$path}.id",
                'Element must have an id property.',
                'integer',
                100
            );
        } elseif ( ! is_int( $element['id'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_element_id_type',
                "{$path}.id",
                'Element id must be an integer, not a string. Found: ' . gettype( $element['id'] ),
                'integer',
                100
            );
        }

        // Validate element.data.
        if ( ! isset( $element['data'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_element_data',
                "{$path}.data",
                'Element must have a data property.',
                'object',
                array( 'type' => 'EssentialElements\\Heading', 'properties' => null )
            );
        } elseif ( ! is_array( $element['data'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_element_data_type',
                "{$path}.data",
                'Element data must be an object.',
                'object',
                array( 'type' => 'EssentialElements\\Heading', 'properties' => null )
            );
        } else {
            // Validate element.data.type.
            if ( ! isset( $element['data']['type'] ) ) {
                $errors[] = $this->create_validation_error(
                    'missing_element_type',
                    "{$path}.data.type",
                    'Element data must have a type property.',
                    'string',
                    'EssentialElements\\Heading'
                );
            } elseif ( ! is_string( $element['data']['type'] ) ) {
                $errors[] = $this->create_validation_error(
                    'invalid_element_type_type',
                    "{$path}.data.type",
                    'Element data type must be a string.',
                    'string',
                    'EssentialElements\\Heading'
                );
            } else {
                // Validate element type has valid namespace.
                $this->validate_element_type( $element['data']['type'], "{$path}.data.type", $errors, $warnings );
            }

            // Validate element.data.properties exists (can be null or object).
            if ( ! array_key_exists( 'properties', $element['data'] ) ) {
                $errors[] = $this->create_validation_error(
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
            $errors[] = $this->create_validation_error(
                'missing_element_children',
                "{$path}.children",
                'Element must have a children array (use empty [] for leaf nodes).',
                'array',
                array()
            );
        } elseif ( ! is_array( $element['children'] ) ) {
            $errors[] = $this->create_validation_error(
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
                $this->validate_child_element( $child, "{$path}.children[$child_index]", $element_id, $errors, $warnings );
            }
        }

        // Validate element._parentId.
        if ( ! isset( $element['_parentId'] ) ) {
            $errors[] = $this->create_validation_error(
                'missing_parent_id',
                "{$path}._parentId",
                'Element must have a _parentId property referencing its parent.',
                'integer',
                $parent_id
            );
        } elseif ( ! is_int( $element['_parentId'] ) ) {
            $errors[] = $this->create_validation_error(
                'invalid_parent_id_type',
                "{$path}._parentId",
                'Element _parentId must be an integer, not a string.',
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
     * Provides detailed validation with suggestions for invalid types.
     *
     * @since 1.1.0
     * @param string $type     Element type string.
     * @param string $path     Property path for error reporting.
     * @param array  $errors   Reference to errors array.
     * @param array  $warnings Reference to warnings array.
     * @return void
     */
    private function validate_element_type( string $type, string $path, array &$errors, array &$warnings ): void {
        $valid_types = $this->get_all_element_types();
        $valid_namespaces = array(
            'EssentialElements\\',
            'OxygenElements\\',
        );

        // Check for exact match first.
        if ( in_array( $type, $valid_types, true ) ) {
            return; // Valid type, no error.
        }

        // Check if it has a valid namespace prefix.
        $has_valid_namespace = false;
        foreach ( $valid_namespaces as $namespace ) {
            if ( 0 === strpos( $type, $namespace ) ) {
                $has_valid_namespace = true;
                break;
            }
        }

        if ( $has_valid_namespace ) {
            // Has namespace but is not a known type - find similar types.
            $suggestions = $this->find_similar_element_types( $type, $valid_types, 3 );

            $errors[] = array(
                'code'        => 'invalid_element_type',
                'path'        => $path,
                'message'     => "Element type '{$type}' is not a recognized element type.",
                'expected'    => 'Valid element type string',
                'suggestions' => $suggestions,
                'valid_types' => $valid_types,
                'action'      => ! empty( $suggestions )
                    ? "Did you mean one of: " . implode( ', ', $suggestions ) . '?'
                    : 'Use one of the valid element types listed above.',
            );
            return;
        }

        // No namespace prefix - try to find a match.
        $type_lower = strtolower( $type );
        $suggested_type = null;

        // First, check for exact short name match (case-insensitive).
        foreach ( $valid_types as $valid_type ) {
            $short_name = substr( $valid_type, strrpos( $valid_type, '\\' ) + 1 );
            if ( strtolower( $short_name ) === $type_lower ) {
                $suggested_type = $valid_type;
                break;
            }
        }

        if ( $suggested_type ) {
            $errors[] = array(
                'code'        => 'missing_element_namespace',
                'path'        => $path,
                'message'     => "Element type '{$type}' is missing namespace prefix. Did you mean '{$suggested_type}'?",
                'expected'    => 'string with namespace prefix (EssentialElements\\\\ or OxygenElements\\\\)',
                'example'     => $suggested_type,
                'valid_types' => $valid_types,
                'action'      => "Change '{$type}' to '{$suggested_type}'",
            );
            return;
        }

        // No exact match - try fuzzy matching.
        $suggestions = $this->find_similar_element_types( $type, $valid_types, 3 );

        $errors[] = array(
            'code'        => 'invalid_element_type',
            'path'        => $path,
            'message'     => "Element type '{$type}' is not recognized and is missing a namespace prefix.",
            'expected'    => 'Valid element type with EssentialElements\\\\ or OxygenElements\\\\ prefix',
            'example'     => 'EssentialElements\\Heading',
            'suggestions' => $suggestions,
            'valid_types' => $valid_types,
            'action'      => ! empty( $suggestions )
                ? "Did you mean one of: " . implode( ', ', $suggestions ) . '?'
                : 'Use one of the valid element types listed above.',
        );
    }

    /**
     * Find similar element types using string similarity.
     *
     * Uses Levenshtein distance to find the most similar valid element types.
     *
     * @since 1.1.0
     * @param string $type        The invalid type to find matches for.
     * @param array  $valid_types Array of valid element types.
     * @param int    $limit       Maximum number of suggestions to return.
     * @return array Array of suggested similar types.
     */
    private function find_similar_element_types( string $type, array $valid_types, int $limit = 3 ): array {
        $type_lower = strtolower( $type );
        $similarities = array();

        foreach ( $valid_types as $valid_type ) {
            // Compare against both full type and short name.
            $short_name = substr( $valid_type, strrpos( $valid_type, '\\' ) + 1 );
            $short_name_lower = strtolower( $short_name );
            $full_type_lower = strtolower( $valid_type );

            // Calculate similarity scores.
            $short_distance = levenshtein( $type_lower, $short_name_lower );
            $full_distance = levenshtein( $type_lower, $full_type_lower );

            // Use the better (lower) distance.
            $best_distance = min( $short_distance, $full_distance );

            // Also check for substring match (type is contained in valid type or vice versa).
            $contains_bonus = 0;
            if ( strpos( $short_name_lower, $type_lower ) !== false || strpos( $type_lower, $short_name_lower ) !== false ) {
                $contains_bonus = 10; // Significant bonus for substring matches.
            }

            // Calculate a similarity score (higher is better).
            // Max length provides normalization.
            $max_len = max( strlen( $type ), strlen( $short_name ) );
            $score = ( $max_len - $best_distance + $contains_bonus ) / $max_len;

            // Only include if reasonably similar (score > 0.3 or has substring match).
            if ( $score > 0.3 || $contains_bonus > 0 ) {
                $similarities[ $valid_type ] = $score;
            }
        }

        // Sort by similarity score (highest first).
        arsort( $similarities );

        // Return top matches.
        return array_slice( array_keys( $similarities ), 0, $limit );
    }

    /**
     * Get all valid element types (comprehensive list).
     *
     * Returns the complete list of valid element types for validation.
     *
     * @since 1.1.0
     * @return array Array of all valid element types.
     */
    private function get_all_element_types(): array {
        return array(
            // Essential Elements - Content.
            'EssentialElements\\Heading',
            'EssentialElements\\Text',
            'EssentialElements\\RichText',
            'EssentialElements\\Button',
            'EssentialElements\\Image',
            'EssentialElements\\Image2',
            'EssentialElements\\Icon',
            'EssentialElements\\Link',
            'EssentialElements\\Video',
            'EssentialElements\\Audio',
            'EssentialElements\\Embed',

            // Essential Elements - Layout.
            'EssentialElements\\Section',
            'EssentialElements\\Container',
            'EssentialElements\\Div',
            'EssentialElements\\Columns',
            'EssentialElements\\Column',
            'EssentialElements\\Grid',
            'EssentialElements\\Slider',
            'EssentialElements\\Tabs',
            'EssentialElements\\Tab',
            'EssentialElements\\Accordion',
            'EssentialElements\\AccordionItem',
            'EssentialElements\\Modal',

            // Essential Elements - Navigation.
            'EssentialElements\\WpMenu',
            'EssentialElements\\Menu',
            'EssentialElements\\MobileMenu',
            'EssentialElements\\Breadcrumbs',

            // Essential Elements - Forms.
            'EssentialElements\\Form',
            'EssentialElements\\FormInput',
            'EssentialElements\\FormTextarea',
            'EssentialElements\\FormSelect',
            'EssentialElements\\FormCheckbox',
            'EssentialElements\\FormRadio',
            'EssentialElements\\FormSubmit',
            'EssentialElements\\SearchForm',

            // Essential Elements - Dynamic.
            'EssentialElements\\PostTitle',
            'EssentialElements\\PostContent',
            'EssentialElements\\PostExcerpt',
            'EssentialElements\\PostImage',
            'EssentialElements\\PostMeta',
            'EssentialElements\\PostDate',
            'EssentialElements\\PostAuthor',
            'EssentialElements\\PostCategories',
            'EssentialElements\\PostTags',
            'EssentialElements\\PostsLoop',
            'EssentialElements\\Pagination',

            // Essential Elements - Misc.
            'EssentialElements\\Shortcode',
            'EssentialElements\\Code',
            'EssentialElements\\Html',
            'EssentialElements\\Spacer',
            'EssentialElements\\Divider',
            'EssentialElements\\SocialIcons',
            'EssentialElements\\CountDown',
            'EssentialElements\\ProgressBar',
            'EssentialElements\\StarRating',
            'EssentialElements\\GoogleMaps',

            // Oxygen Elements - Structure.
            'OxygenElements\\Container',
            'OxygenElements\\ContainerLink',
            'OxygenElements\\Section',
            'OxygenElements\\Div',
            'OxygenElements\\Columns',
            'OxygenElements\\Column',
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

        $components_file = OXYBRIDGE_PLUGIN_DIR . '/ai/components.json';

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
     * Returns the most commonly used element types for quick reference.
     * For full validation, use get_all_element_types() instead.
     *
     * @since 1.1.0
     * @return array Array of common element types.
     */
    private function get_common_element_types(): array {
        return array(
            // Essential Elements - Most used.
            'EssentialElements\\Section',
            'EssentialElements\\Container',
            'EssentialElements\\Div',
            'EssentialElements\\Heading',
            'EssentialElements\\Text',
            'EssentialElements\\Button',
            'EssentialElements\\Image',
            'EssentialElements\\Image2',
            'EssentialElements\\Icon',
            'EssentialElements\\Link',
            'EssentialElements\\Columns',
            'EssentialElements\\Column',
            'EssentialElements\\WpMenu',
            // Oxygen Elements - Structure.
            'OxygenElements\\Container',
            'OxygenElements\\ContainerLink',
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
                'description' => 'The main structure for storing page/template content. Based on analysis of working Oxygen documents.',
                'required_properties' => array(
                    'root'                 => 'REQUIRED - Root container element object',
                    'root.id'              => 'REQUIRED - Integer ID (typically 1)',
                    'root.data'            => 'REQUIRED - Element data object',
                    'root.data.type'       => 'REQUIRED - Must be lowercase "root"',
                    'root.data.properties' => 'REQUIRED - Must be null',
                    'root.children'        => 'REQUIRED - Array of child elements',
                    'status'               => 'REQUIRED - Must be "exported"',
                ),
                'optional_properties' => array(
                    '_nextNodeId'          => 'Do NOT include - causes io-ts errors',
                    'exportedLookupTable'  => 'Do NOT include - causes io-ts errors',
                ),
                'structure'   => array(
                    'root' => array(
                        'id'       => 'integer (typically 1)',
                        'data'     => array(
                            'type'       => '"root" (lowercase string)',
                            'properties' => 'null',
                        ),
                        'children' => 'array (child elements with _parentId)',
                    ),
                    'status' => '"exported"',
                ),
                'validation_note' => 'Oxygen Builder uses io-ts for runtime validation. IDs must be integers. Children must have _parentId. Tree must have status: "exported".',
            ),
            'element' => array(
                'description' => 'Individual element within the tree. All elements must have id, data, children, and _parentId.',
                'required_properties' => array(
                    'id'              => 'REQUIRED - Integer ID (e.g., 100, 101, 102)',
                    'data'            => 'REQUIRED - Element data object',
                    'data.type'       => 'REQUIRED - Element type string with namespace',
                    'data.properties' => 'REQUIRED - Properties object or null if no properties',
                    'children'        => 'REQUIRED - Array of child elements (empty [] for leaf nodes)',
                    '_parentId'       => 'REQUIRED - Integer ID of parent element',
                ),
                'structure'   => array(
                    'id'        => 'integer (e.g., 100)',
                    'data'      => array(
                        'type'       => 'string (e.g., "OxygenElements\\\\Container" or "EssentialElements\\\\Heading")',
                        'properties' => 'object or null',
                    ),
                    'children'  => 'array (child elements, use empty [] for leaf nodes)',
                    '_parentId' => 'integer (parent element ID)',
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
                'description' => 'Minimal valid document tree. IDs are integers, children have _parentId, tree has status: "exported".',
                'tree'        => array(
                    'root'   => array(
                        'id'       => 1,
                        'data'     => array(
                            'type'       => 'root',
                            'properties' => null,
                        ),
                        'children' => array(
                            array(
                                'id'        => 100,
                                'data'      => array(
                                    'type'       => 'OxygenElements\\Container',
                                    'properties' => null,
                                ),
                                'children'  => array(
                                    array(
                                        'id'        => 101,
                                        'data'      => array(
                                            'type'       => 'EssentialElements\\Heading',
                                            'properties' => array(
                                                'content' => array(
                                                    'content' => array(
                                                        'text' => 'Hello World',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'children'  => array(),
                                        '_parentId' => 100,
                                    ),
                                ),
                                '_parentId' => 1,
                            ),
                        ),
                    ),
                    'status' => 'exported',
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
            'critical_structure'  => 'CRITICAL: Document tree MUST have: root (with integer id, data, children), status: "exported". Children MUST have _parentId referencing parent integer ID.',
            'root_element'        => 'The ROOT element: id MUST be integer (typically 1), data.type MUST be lowercase "root", data.properties MUST be null.',
            'element_structure'   => 'Every CHILD element MUST have: id (integer), data.type (string with namespace), data.properties (object or null), children (array), and _parentId (parent integer ID).',
            'element_types'       => 'Use OxygenElements\\\\ for containers: OxygenElements\\\\Container, OxygenElements\\\\ContainerLink. Use EssentialElements\\\\ for content: EssentialElements\\\\Heading, EssentialElements\\\\Div, EssentialElements\\\\WpMenu, EssentialElements\\\\Button.',
            'text_content'        => 'Text content uses double-nested path: content.content.text',
            'unit_values'         => 'Numeric values with units must be objects: {"number": 20, "unit": "px", "style": ""}',
            'breakpoints'         => 'Use breakpoint_base for desktop, breakpoint_tablet_portrait for tablet, breakpoint_phone_portrait for mobile',
            'element_ids'         => 'Element IDs MUST be integers. Root is typically 1, children use sequential numbers like 100, 101, 102.',
            'parent_ids'          => 'Every child element MUST have _parentId set to its parent integer ID. Direct children of root have _parentId: 1.',
            'empty_children'      => 'Leaf elements MUST have children: [] - do not omit the property',
            'empty_properties'    => 'Elements with no custom properties should use properties: null (not {} or omitted).',
            'status_field'        => 'Tree MUST have status: "exported" at the top level alongside root.',
            'no_extra_fields'     => 'Do NOT add _nextNodeId or exportedLookupTable to the tree.',
            'css_regeneration'    => 'CSS is automatically regenerated after saving via the API. Changes appear on frontend immediately.',
            'authentication'      => 'Write operations require authentication; most read operations are public',
            'workflow'            => 'Typical workflow: 1) GET /pages to find page, 2) GET /documents/{id} to read tree, 3) POST /documents/{id} to update, 4) Changes appear on frontend',
        );
    }
}
