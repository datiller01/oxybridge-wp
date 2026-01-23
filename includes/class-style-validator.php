<?php
/**
 * Style Validator class for Oxybridge.
 *
 * Validates properties against element schemas and previews CSS output.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Style_Validator
 *
 * Validates properties against element schemas and optionally previews CSS output.
 * Uses Breakdance's internal functions when available to verify properties.
 *
 * @since 1.1.0
 */
class Style_Validator {

    /**
     * Property schema instance.
     *
     * @var Property_Schema
     */
    private $schema;

    /**
     * Validation errors.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Validation warnings.
     *
     * @var array
     */
    private $warnings = array();

    /**
     * Constructor.
     *
     * @param Property_Schema|null $schema Property schema instance.
     */
    public function __construct( Property_Schema $schema = null ) {
        $this->schema = $schema ?? new Property_Schema();
    }

    /**
     * Validate a simplified tree.
     *
     * @param array $tree Simplified tree to validate.
     * @return array Validation result with 'valid', 'errors', and 'warnings'.
     */
    public function validate_tree( array $tree ) {
        $this->errors = array();
        $this->warnings = array();

        // Validate root structure.
        if ( isset( $tree['root'] ) ) {
            $this->validate_node( $tree['root'], 'root' );
        } elseif ( isset( $tree['children'] ) ) {
            foreach ( $tree['children'] as $index => $child ) {
                $this->validate_node( $child, "children[{$index}]" );
            }
        } else {
            $this->validate_node( $tree, 'root' );
        }

        return array(
            'valid'    => empty( $this->errors ),
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
        );
    }

    /**
     * Validate a single node.
     *
     * @param array  $node Node to validate.
     * @param string $path Current path for error reporting.
     * @return void
     */
    private function validate_node( array $node, string $path ) {
        // Skip root nodes.
        if ( isset( $node['data']['type'] ) && $node['data']['type'] === 'root' ) {
            if ( isset( $node['children'] ) ) {
                foreach ( $node['children'] as $index => $child ) {
                    $this->validate_node( $child, "{$path}.children[{$index}]" );
                }
            }
            return;
        }

        // Check for element type.
        $type = $node['type'] ?? ( $node['data']['type'] ?? null );

        if ( ! $type ) {
            $this->errors[] = array(
                'path'    => $path,
                'code'    => 'missing_type',
                'message' => 'Element is missing required "type" property.',
            );
            return;
        }

        // Check if type is valid.
        $breakdance_type = $this->schema->get_element_type( $type );
        if ( ! $breakdance_type && strpos( $type, '\\' ) === false ) {
            $this->warnings[] = array(
                'path'    => $path,
                'code'    => 'unknown_type',
                'message' => "Unknown element type '{$type}'. Will default to Div.",
            );
        }

        // Validate element-specific properties.
        $this->validate_element_properties( $node, $type, $path );

        // Validate children.
        if ( isset( $node['children'] ) ) {
            if ( ! is_array( $node['children'] ) ) {
                $this->errors[] = array(
                    'path'    => "{$path}.children",
                    'code'    => 'invalid_children',
                    'message' => 'Children must be an array.',
                );
            } else {
                foreach ( $node['children'] as $index => $child ) {
                    $this->validate_node( $child, "{$path}.children[{$index}]" );
                }
            }
        }
    }

    /**
     * Validate element-specific properties.
     *
     * @param array  $node Node to validate.
     * @param string $type Element type.
     * @param string $path Current path for error reporting.
     * @return void
     */
    private function validate_element_properties( array $node, string $type, string $path ) {
        $element_schema = $this->schema->get_element_simplified_schema( $type );

        if ( empty( $element_schema['properties'] ) ) {
            return;
        }

        // Check required properties.
        foreach ( $element_schema['properties'] as $prop => $config ) {
            if ( ! empty( $config['required'] ) && ! isset( $node[ $prop ] ) ) {
                $this->errors[] = array(
                    'path'    => $path,
                    'code'    => 'missing_required',
                    'message' => "Element '{$type}' is missing required property '{$prop}'.",
                );
            }
        }

        // Validate property values.
        foreach ( $node as $key => $value ) {
            if ( in_array( $key, array( 'type', 'id', 'children', 'responsive' ), true ) ) {
                continue;
            }

            $this->validate_property_value( $key, $value, $type, "{$path}.{$key}" );
        }

        // Validate responsive properties.
        if ( isset( $node['responsive'] ) ) {
            foreach ( $node['responsive'] as $breakpoint => $values ) {
                // Validate breakpoint name.
                $breakpoint_id = $this->schema->get_breakpoint_id( $breakpoint );
                if ( $breakpoint_id === 'breakpoint_base' && $breakpoint !== 'desktop' && $breakpoint !== 'base' ) {
                    $this->warnings[] = array(
                        'path'    => "{$path}.responsive.{$breakpoint}",
                        'code'    => 'unknown_breakpoint',
                        'message' => "Unknown breakpoint '{$breakpoint}'. Valid: desktop, tablet, phone.",
                    );
                }

                foreach ( $values as $key => $value ) {
                    // Check if property supports responsive.
                    if ( ! $this->schema->is_responsive_property( $key ) ) {
                        $this->warnings[] = array(
                            'path'    => "{$path}.responsive.{$breakpoint}.{$key}",
                            'code'    => 'non_responsive_property',
                            'message' => "Property '{$key}' does not support responsive values.",
                        );
                    }

                    $this->validate_property_value( $key, $value, $type, "{$path}.responsive.{$breakpoint}.{$key}" );
                }
            }
        }
    }

    /**
     * Validate a property value.
     *
     * @param string $key   Property key.
     * @param mixed  $value Property value.
     * @param string $type  Element type.
     * @param string $path  Current path for error reporting.
     * @return void
     */
    private function validate_property_value( string $key, $value, string $type, string $path ) {
        $element_schema = $this->schema->get_element_simplified_schema( $type );
        $property_config = $element_schema['properties'][ $key ] ?? null;

        if ( ! $property_config ) {
            // Check if it's a known property in the schema.
            if ( ! $this->schema->get_property_path( $key ) ) {
                $this->warnings[] = array(
                    'path'    => $path,
                    'code'    => 'unknown_property',
                    'message' => "Unknown property '{$key}' for element '{$type}'.",
                );
            }
            return;
        }

        $prop_type = $property_config['type'] ?? 'string';

        switch ( $prop_type ) {
            case 'string':
                if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
                    $this->errors[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_type',
                        'message' => "Property '{$key}' must be a string.",
                    );
                }
                break;

            case 'color':
                if ( ! $this->is_valid_color( $value ) ) {
                    $this->warnings[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_color',
                        'message' => "Property '{$key}' should be a valid color (hex, rgb, rgba, or CSS variable).",
                    );
                }
                break;

            case 'unit':
                if ( ! $this->is_valid_unit_value( $value ) ) {
                    $this->warnings[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_unit',
                        'message' => "Property '{$key}' should be a valid unit value (e.g., '16px', '1.5em', '100%').",
                    );
                }
                break;

            case 'enum':
                $valid_values = $property_config['values'] ?? array();
                if ( ! in_array( $value, $valid_values, true ) ) {
                    $this->warnings[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_enum',
                        'message' => "Property '{$key}' has invalid value '{$value}'. Valid values: " . implode( ', ', $valid_values ),
                    );
                }
                break;

            case 'spacing':
                if ( ! is_string( $value ) && ! is_array( $value ) ) {
                    $this->errors[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_spacing',
                        'message' => "Property '{$key}' must be a spacing string (e.g., '10px 20px') or object.",
                    );
                }
                break;

            case 'code':
                if ( ! is_string( $value ) ) {
                    $this->errors[] = array(
                        'path'    => $path,
                        'code'    => 'invalid_code',
                        'message' => "Property '{$key}' must be a string containing code.",
                    );
                }
                break;
        }
    }

    /**
     * Check if a value is a valid color.
     *
     * @param mixed $value Value to check.
     * @return bool True if valid color.
     */
    private function is_valid_color( $value ) {
        if ( ! is_string( $value ) ) {
            return false;
        }

        // Hex colors.
        if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
            return true;
        }

        // RGB/RGBA.
        if ( preg_match( '/^rgba?\s*\(/', $value ) ) {
            return true;
        }

        // HSL/HSLA.
        if ( preg_match( '/^hsla?\s*\(/', $value ) ) {
            return true;
        }

        // CSS variables.
        if ( strpos( $value, 'var(' ) === 0 ) {
            return true;
        }

        // Named colors (basic check).
        $named_colors = array( 'transparent', 'inherit', 'initial', 'unset', 'currentColor' );
        if ( in_array( strtolower( $value ), $named_colors, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if a value is a valid unit value.
     *
     * @param mixed $value Value to check.
     * @return bool True if valid unit value.
     */
    private function is_valid_unit_value( $value ) {
        if ( is_array( $value ) ) {
            // Check for number/unit object format.
            return isset( $value['number'] ) && isset( $value['unit'] );
        }

        if ( ! is_string( $value ) ) {
            return false;
        }

        // Special values.
        if ( in_array( $value, array( 'auto', 'inherit', 'initial', 'unset', 'none' ), true ) ) {
            return true;
        }

        // CSS variables.
        if ( strpos( $value, 'var(' ) === 0 ) {
            return true;
        }

        // Number with optional unit.
        if ( preg_match( '/^-?[\d.]+(px|em|rem|%|vw|vh|vmin|vmax|ch|ex)?$/i', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate properties and attempt to preview CSS.
     *
     * @param string $element_type Element type.
     * @param array  $properties   Properties to validate.
     * @return array Validation result with CSS preview if available.
     */
    public function validate_with_preview( string $element_type, array $properties ) {
        $result = array(
            'valid'       => true,
            'errors'      => array(),
            'warnings'    => array(),
            'css_preview' => null,
        );

        // Basic validation.
        $node = array_merge( array( 'type' => $element_type ), $properties );
        $validation = $this->validate_tree( $node );

        $result['valid'] = $validation['valid'];
        $result['errors'] = $validation['errors'];
        $result['warnings'] = $validation['warnings'];

        // Try to generate CSS preview if Breakdance functions are available.
        if ( function_exists( '\\Breakdance\\Render\\getFlattenedPropertiesByBreakpoint' ) ) {
            try {
                $transformer = new Property_Transformer( $this->schema );
                $breakdance_node = $transformer->transform_element( $node );

                $element_properties = $breakdance_node['data']['properties'] ?? array();
                $breakpoint_ids = array(
                    'breakpoint_base',
                    'breakpoint_tablet_landscape',
                    'breakpoint_tablet_portrait',
                    'breakpoint_phone_landscape',
                    'breakpoint_phone_portrait',
                );

                // Flatten properties for base breakpoint.
                $flattened = \Breakdance\Render\getFlattenedPropertiesByBreakpoint(
                    'breakpoint_base',
                    $element_properties,
                    $breakpoint_ids,
                    'breakpoint_base',
                    false
                );

                $result['css_preview'] = array(
                    'flattened_properties' => $flattened,
                    'breakpoint'           => 'breakpoint_base',
                );
            } catch ( \Exception $e ) {
                $result['warnings'][] = array(
                    'code'    => 'css_preview_failed',
                    'message' => 'Could not generate CSS preview: ' . $e->getMessage(),
                );
            }
        }

        return $result;
    }

    /**
     * Get supported properties for an element type.
     *
     * @param string $element_type Element type.
     * @return array List of supported properties with their configurations.
     */
    public function get_supported_properties( string $element_type ) {
        $schema = $this->schema->get_element_simplified_schema( $element_type );
        return $schema['properties'] ?? array();
    }

    /**
     * Get the last validation errors.
     *
     * @return array Validation errors.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get the last validation warnings.
     *
     * @return array Validation warnings.
     */
    public function get_warnings() {
        return $this->warnings;
    }
}
