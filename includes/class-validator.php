<?php
/**
 * Validator Class
 *
 * Provides comprehensive validation for Oxygen/Breakdance JSON tree structures.
 * Validates structure, elements, properties, and returns detailed validation results.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Validator
 *
 * Validates Oxygen/Breakdance document tree structures without saving.
 *
 * @since 1.0.0
 */
class Validator {

    /**
     * Validation errors.
     *
     * @var array
     */
    private array $errors = array();

    /**
     * Validation warnings.
     *
     * @var array
     */
    private array $warnings = array();

    /**
     * Required keys for root element.
     *
     * @var array
     */
    const ROOT_REQUIRED_KEYS = array( 'id', 'data', 'children' );

    /**
     * Required keys for child elements.
     *
     * @var array
     */
    const ELEMENT_REQUIRED_KEYS = array( 'id', 'data' );

    /**
     * Maximum allowed tree depth.
     *
     * @var int
     */
    const MAX_TREE_DEPTH = 50;

    /**
     * Maximum allowed elements.
     *
     * @var int
     */
    const MAX_ELEMENTS = 10000;

    /**
     * Validate a tree structure.
     *
     * Performs comprehensive validation of the tree structure and returns
     * a detailed validation result.
     *
     * @since 1.0.0
     *
     * @param mixed $tree The tree structure to validate.
     * @return array {
     *     Validation result.
     *
     *     @type bool   $valid         Whether the tree is valid.
     *     @type array  $errors        Array of validation errors.
     *     @type array  $warnings      Array of validation warnings.
     *     @type array  $stats         Tree statistics (element_count, max_depth, element_types).
     *     @type string $tree_version  Detected tree format version.
     * }
     */
    public function validate( $tree ): array {
        // Reset state.
        $this->errors   = array();
        $this->warnings = array();

        // Basic type validation.
        if ( ! is_array( $tree ) ) {
            $this->add_error( 'invalid_type', __( 'Tree must be an array.', 'oxybridge-wp' ) );
            return $this->build_result( $tree );
        }

        // Check for empty tree.
        if ( empty( $tree ) ) {
            $this->add_error( 'empty_tree', __( 'Tree is empty.', 'oxybridge-wp' ) );
            return $this->build_result( $tree );
        }

        // Detect and validate tree format.
        $tree_version = $this->detect_tree_version( $tree );

        if ( $tree_version === 'unknown' ) {
            $this->add_error(
                'unknown_format',
                __( 'Unknown tree format. Expected either a "root" key or classic Oxygen structure.', 'oxybridge-wp' )
            );
            return $this->build_result( $tree );
        }

        // Validate based on detected format.
        if ( $tree_version === 'modern' ) {
            $this->validate_modern_tree( $tree );
        } else {
            $this->validate_classic_tree( $tree );
        }

        return $this->build_result( $tree, $tree_version );
    }

    /**
     * Quick validation check for tree structure.
     *
     * Performs basic validation similar to Breakdance\Data\is_valid_tree().
     * Use validate() for detailed validation results.
     *
     * @since 1.0.0
     *
     * @param mixed $tree The tree to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid( $tree ): bool {
        if ( ! is_array( $tree ) ) {
            return false;
        }

        if ( empty( $tree ) ) {
            return false;
        }

        // Check for modern format.
        if ( isset( $tree['root'] ) ) {
            if ( ! is_array( $tree['root'] ) ) {
                return false;
            }

            foreach ( self::ROOT_REQUIRED_KEYS as $key ) {
                if ( ! array_key_exists( $key, $tree['root'] ) ) {
                    return false;
                }
            }

            return true;
        }

        // Check for classic format (array of elements).
        if ( is_array( $tree ) && ! isset( $tree['root'] ) ) {
            // Could be a classic Oxygen array format.
            return true;
        }

        return false;
    }

    /**
     * Validate a single element.
     *
     * @since 1.0.0
     *
     * @param mixed $element The element to validate.
     * @return array {
     *     Validation result.
     *
     *     @type bool   $valid    Whether the element is valid.
     *     @type array  $errors   Array of validation errors.
     *     @type array  $warnings Array of validation warnings.
     * }
     */
    public function validate_element( $element ): array {
        $this->errors   = array();
        $this->warnings = array();

        if ( ! is_array( $element ) ) {
            $this->add_error( 'invalid_element_type', __( 'Element must be an array.', 'oxybridge-wp' ) );
            return array(
                'valid'    => false,
                'errors'   => $this->errors,
                'warnings' => $this->warnings,
            );
        }

        $this->validate_element_structure( $element, 'element' );

        return array(
            'valid'    => empty( $this->errors ),
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
        );
    }

    /**
     * Detect the tree format version.
     *
     * @since 1.0.0
     *
     * @param array $tree The tree structure.
     * @return string Tree version: 'modern', 'classic', or 'unknown'.
     */
    private function detect_tree_version( array $tree ): string {
        // Modern format (Oxygen 6 / Breakdance) has a 'root' key.
        if ( isset( $tree['root'] ) && is_array( $tree['root'] ) ) {
            return 'modern';
        }

        // Classic Oxygen format is typically an array of elements at root level.
        // Check if it looks like an array of element objects.
        if ( ! isset( $tree['root'] ) ) {
            // Could be classic format if it's numeric-keyed or has element-like structures.
            $first_key = array_key_first( $tree );

            // If first key is numeric, it's likely an array of elements.
            if ( is_int( $first_key ) ) {
                return 'classic';
            }

            // If it has element-like keys, it might be a single element.
            if ( isset( $tree['id'] ) || isset( $tree['children'] ) ) {
                return 'classic';
            }
        }

        return 'unknown';
    }

    /**
     * Validate a modern (Oxygen 6/Breakdance) tree structure.
     *
     * @since 1.0.0
     *
     * @param array $tree The tree structure.
     * @return void
     */
    private function validate_modern_tree( array $tree ): void {
        // Validate root element exists.
        if ( ! isset( $tree['root'] ) ) {
            $this->add_error( 'missing_root', __( 'Tree is missing required "root" key.', 'oxybridge-wp' ) );
            return;
        }

        // Validate root is an array.
        if ( ! is_array( $tree['root'] ) ) {
            $this->add_error( 'invalid_root', __( 'Root element must be an array.', 'oxybridge-wp' ) );
            return;
        }

        // Validate root has required keys.
        foreach ( self::ROOT_REQUIRED_KEYS as $key ) {
            if ( ! array_key_exists( $key, $tree['root'] ) ) {
                $this->add_error(
                    'missing_root_key',
                    sprintf(
                        /* translators: %s: key name */
                        __( 'Root element is missing required key: %s', 'oxybridge-wp' ),
                        $key
                    )
                );
            }
        }

        // Validate root ID.
        if ( isset( $tree['root']['id'] ) && ! $this->is_valid_element_id( $tree['root']['id'] ) ) {
            $this->add_error( 'invalid_root_id', __( 'Root element ID must be a non-empty string.', 'oxybridge-wp' ) );
        }

        // Validate root data.
        if ( isset( $tree['root']['data'] ) && ! is_array( $tree['root']['data'] ) ) {
            $this->add_error( 'invalid_root_data', __( 'Root element data must be an array.', 'oxybridge-wp' ) );
        }

        // Validate children.
        if ( isset( $tree['root']['children'] ) ) {
            if ( ! is_array( $tree['root']['children'] ) ) {
                $this->add_error( 'invalid_children', __( 'Root children must be an array.', 'oxybridge-wp' ) );
            } else {
                $this->validate_children( $tree['root']['children'], 'root.children', 1 );
            }
        }

        // Check for unexpected keys at tree level.
        $expected_tree_keys = array( 'root' );
        $unexpected_keys    = array_diff( array_keys( $tree ), $expected_tree_keys );

        if ( ! empty( $unexpected_keys ) ) {
            $this->add_warning(
                'unexpected_tree_keys',
                sprintf(
                    /* translators: %s: comma-separated list of key names */
                    __( 'Tree contains unexpected keys: %s', 'oxybridge-wp' ),
                    implode( ', ', $unexpected_keys )
                )
            );
        }
    }

    /**
     * Validate a classic Oxygen tree structure.
     *
     * @since 1.0.0
     *
     * @param array $tree The tree structure.
     * @return void
     */
    private function validate_classic_tree( array $tree ): void {
        // Classic format could be an array of elements or a single element.
        if ( isset( $tree['id'] ) || isset( $tree['children'] ) ) {
            // Single element at root.
            $this->validate_element_structure( $tree, 'root' );

            if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
                $this->validate_children( $tree['children'], 'children', 1 );
            }
        } else {
            // Array of elements.
            $this->validate_children( $tree, 'tree', 0 );
        }
    }

    /**
     * Validate an array of child elements recursively.
     *
     * @since 1.0.0
     *
     * @param array  $children The children array.
     * @param string $path     The path for error reporting.
     * @param int    $depth    Current depth level.
     * @return void
     */
    private function validate_children( array $children, string $path, int $depth ): void {
        // Check max depth.
        if ( $depth > self::MAX_TREE_DEPTH ) {
            $this->add_error(
                'max_depth_exceeded',
                sprintf(
                    /* translators: 1: path, 2: max depth */
                    __( 'Tree depth exceeds maximum allowed (%1$s). Path: %2$s', 'oxybridge-wp' ),
                    self::MAX_TREE_DEPTH,
                    $path
                )
            );
            return;
        }

        foreach ( $children as $index => $child ) {
            $child_path = "{$path}[{$index}]";

            if ( ! is_array( $child ) ) {
                $this->add_error(
                    'invalid_child_type',
                    sprintf(
                        /* translators: %s: path */
                        __( 'Child element at %s must be an array.', 'oxybridge-wp' ),
                        $child_path
                    )
                );
                continue;
            }

            $this->validate_element_structure( $child, $child_path );

            // Recursively validate children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $this->validate_children( $child['children'], "{$child_path}.children", $depth + 1 );
            }
        }
    }

    /**
     * Validate an element's structure.
     *
     * @since 1.0.0
     *
     * @param array  $element The element to validate.
     * @param string $path    The path for error reporting.
     * @return void
     */
    private function validate_element_structure( array $element, string $path ): void {
        // Check for required keys.
        foreach ( self::ELEMENT_REQUIRED_KEYS as $key ) {
            if ( ! array_key_exists( $key, $element ) ) {
                $this->add_warning(
                    'missing_element_key',
                    sprintf(
                        /* translators: 1: key name, 2: path */
                        __( 'Element at %2$s is missing recommended key: %1$s', 'oxybridge-wp' ),
                        $key,
                        $path
                    )
                );
            }
        }

        // Validate ID if present.
        if ( isset( $element['id'] ) && ! $this->is_valid_element_id( $element['id'] ) ) {
            $this->add_error(
                'invalid_element_id',
                sprintf(
                    /* translators: %s: path */
                    __( 'Element at %s has invalid ID (must be non-empty string).', 'oxybridge-wp' ),
                    $path
                )
            );
        }

        // Validate data if present.
        if ( isset( $element['data'] ) ) {
            if ( ! is_array( $element['data'] ) ) {
                $this->add_error(
                    'invalid_element_data',
                    sprintf(
                        /* translators: %s: path */
                        __( 'Element data at %s must be an array.', 'oxybridge-wp' ),
                        $path
                    )
                );
            } else {
                $this->validate_element_data( $element['data'], "{$path}.data" );
            }
        }

        // Validate children is array if present.
        if ( isset( $element['children'] ) && ! is_array( $element['children'] ) ) {
            $this->add_error(
                'invalid_element_children',
                sprintf(
                    /* translators: %s: path */
                    __( 'Element children at %s must be an array.', 'oxybridge-wp' ),
                    $path
                )
            );
        }
    }

    /**
     * Validate element data structure.
     *
     * @since 1.0.0
     *
     * @param array  $data The data array.
     * @param string $path The path for error reporting.
     * @return void
     */
    private function validate_element_data( array $data, string $path ): void {
        // Validate type if present.
        if ( isset( $data['type'] ) && ! is_string( $data['type'] ) ) {
            $this->add_warning(
                'invalid_type_value',
                sprintf(
                    /* translators: %s: path */
                    __( 'Element type at %s should be a string.', 'oxybridge-wp' ),
                    $path
                )
            );
        }

        // Validate properties if present.
        if ( isset( $data['properties'] ) && ! is_array( $data['properties'] ) ) {
            $this->add_warning(
                'invalid_properties',
                sprintf(
                    /* translators: %s: path */
                    __( 'Element properties at %s should be an array.', 'oxybridge-wp' ),
                    $path
                )
            );
        }
    }

    /**
     * Check if a value is a valid element ID.
     *
     * @since 1.0.0
     *
     * @param mixed $id The ID to check.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_element_id( $id ): bool {
        return is_string( $id ) && strlen( $id ) > 0;
    }

    /**
     * Add a validation error.
     *
     * @since 1.0.0
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     * @return void
     */
    private function add_error( string $code, string $message ): void {
        $this->errors[] = array(
            'code'    => $code,
            'message' => $message,
        );
    }

    /**
     * Add a validation warning.
     *
     * @since 1.0.0
     *
     * @param string $code    Warning code.
     * @param string $message Warning message.
     * @return void
     */
    private function add_warning( string $code, string $message ): void {
        $this->warnings[] = array(
            'code'    => $code,
            'message' => $message,
        );
    }

    /**
     * Build the validation result array.
     *
     * @since 1.0.0
     *
     * @param mixed  $tree         The tree being validated.
     * @param string $tree_version The detected tree version.
     * @return array Validation result.
     */
    private function build_result( $tree, string $tree_version = 'unknown' ): array {
        $stats = $this->calculate_tree_stats( $tree );

        return array(
            'valid'        => empty( $this->errors ),
            'errors'       => $this->errors,
            'warnings'     => $this->warnings,
            'stats'        => $stats,
            'tree_version' => $tree_version,
        );
    }

    /**
     * Calculate tree statistics.
     *
     * @since 1.0.0
     *
     * @param mixed $tree The tree structure.
     * @return array {
     *     Tree statistics.
     *
     *     @type int   $element_count Number of elements.
     *     @type int   $max_depth     Maximum depth.
     *     @type array $element_types Unique element types.
     * }
     */
    private function calculate_tree_stats( $tree ): array {
        if ( ! is_array( $tree ) ) {
            return array(
                'element_count' => 0,
                'max_depth'     => 0,
                'element_types' => array(),
            );
        }

        $element_count = 0;
        $max_depth     = 0;
        $element_types = array();

        // Determine starting point.
        $children = array();
        $start_depth = 0;

        if ( isset( $tree['root']['children'] ) ) {
            $children = $tree['root']['children'];
            $start_depth = 1;
        } elseif ( isset( $tree['children'] ) ) {
            $children = $tree['children'];
            $start_depth = 1;
        } elseif ( is_array( $tree ) && ! isset( $tree['root'] ) ) {
            $children = $tree;
            $start_depth = 0;
        }

        $this->count_elements_recursive( $children, $start_depth, $element_count, $max_depth, $element_types );

        return array(
            'element_count' => $element_count,
            'max_depth'     => $max_depth,
            'element_types' => array_values( array_unique( $element_types ) ),
        );
    }

    /**
     * Recursively count elements and gather stats.
     *
     * @since 1.0.0
     *
     * @param array $children      The children array.
     * @param int   $depth         Current depth.
     * @param int   $count         Reference to element count.
     * @param int   $max_depth     Reference to max depth.
     * @param array $element_types Reference to element types array.
     * @return void
     */
    private function count_elements_recursive( array $children, int $depth, int &$count, int &$max_depth, array &$element_types ): void {
        if ( $depth > $max_depth ) {
            $max_depth = $depth;
        }

        foreach ( $children as $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            $count++;

            // Extract element type.
            if ( isset( $child['data']['type'] ) ) {
                $element_types[] = $child['data']['type'];
            } elseif ( isset( $child['type'] ) ) {
                $element_types[] = $child['type'];
            }

            // Recurse into children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $this->count_elements_recursive( $child['children'], $depth + 1, $count, $max_depth, $element_types );
            }
        }
    }
}
