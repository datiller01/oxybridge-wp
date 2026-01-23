<?php
/**
 * Style Validator Class
 *
 * Provides comprehensive validation for styling property values.
 * Validates effects (opacity, box-shadow, filters), transforms,
 * backgrounds, borders, and other CSS-related properties.
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
 * Class Style_Validator
 *
 * Validates styling property values before transformation.
 * Ensures values conform to expected formats and ranges.
 *
 * Example usage:
 * ```php
 * $validator = new Style_Validator();
 * $result = $validator->is_valid_box_shadow('0 4px 6px rgba(0,0,0,0.1)');
 * // Returns true if valid box-shadow format
 * ```
 *
 * @since 1.0.0
 */
class Style_Validator {

    /**
     * Property Schema instance.
     *
     * @var Property_Schema|null
     */
    private ?Property_Schema $schema = null;

    /**
     * Validation errors.
     *
     * @var array
     */
    private array $errors = array();

    /**
     * Valid CSS color formats regex patterns.
     *
     * @var array
     */
    private static array $color_patterns = array(
        'hex3'      => '/^#[0-9a-fA-F]{3}$/',
        'hex6'      => '/^#[0-9a-fA-F]{6}$/',
        'hex8'      => '/^#[0-9a-fA-F]{8}$/',
        'rgb'       => '/^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/i',
        'rgba'      => '/^rgba\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*[\d.]+\s*\)$/i',
        'hsl'       => '/^hsl\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*\)$/i',
        'hsla'      => '/^hsla\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*,\s*[\d.]+\s*\)$/i',
        'named'     => '/^[a-zA-Z]+$/',
        'css_var'   => '/^var\(--[\w-]+(?:\s*,\s*[^)]+)?\)$/',
        'transparent' => '/^transparent$/i',
        'currentColor' => '/^currentColor$/i',
    );

    /**
     * Valid CSS length units.
     *
     * @var array
     */
    private static array $length_units = array(
        'px', 'em', 'rem', '%', 'vw', 'vh', 'vmin', 'vmax', 'cm', 'mm', 'in', 'pt', 'pc', 'ex', 'ch',
    );

    /**
     * Valid box-shadow positions.
     *
     * @var array
     */
    private static array $box_shadow_positions = array(
        'inset',
        'outset', // Default is outset (empty).
    );

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param Property_Schema|null $schema Optional schema instance.
     */
    public function __construct( ?Property_Schema $schema = null ) {
        $this->schema = $schema;
    }

    /**
     * Get Property Schema instance.
     *
     * @since 1.0.0
     *
     * @return Property_Schema The schema instance.
     */
    public function get_schema(): Property_Schema {
        if ( null === $this->schema ) {
            $this->schema = new Property_Schema();
        }
        return $this->schema;
    }

    /**
     * Get validation errors.
     *
     * @since 1.0.0
     *
     * @return array The validation errors.
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * Clear validation errors.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function clear_errors(): void {
        $this->errors = array();
    }

    /**
     * Add a validation error.
     *
     * @since 1.0.0
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     * @param mixed  $context Optional context data.
     * @return void
     */
    private function add_error( string $code, string $message, $context = null ): void {
        $this->errors[] = array(
            'code'    => $code,
            'message' => $message,
            'context' => $context,
        );
    }

    // =========================================================================
    // Box Shadow Validation
    // =========================================================================

    /**
     * Validate a box-shadow property value.
     *
     * Accepts both CSS string format and structured object format.
     *
     * CSS Format Examples:
     * - "0 4px 6px rgba(0,0,0,0.1)"
     * - "inset 0 2px 4px #000"
     * - "0 4px 6px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.06)"
     *
     * Object Format:
     * - { style: "0 4px 6px rgba(0,0,0,0.1)" }
     * - { shadows: [{ x: 0, y: 4, blur: 6, spread: 0, color: "rgba(0,0,0,0.1)", inset: false }] }
     *
     * @since 1.0.0
     *
     * @param mixed $value The box-shadow value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_box_shadow( $value ): bool {
        $this->clear_errors();

        if ( empty( $value ) ) {
            return true; // Empty value is valid (no shadow).
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle "none" keyword.
        if ( is_string( $value ) && strtolower( trim( $value ) ) === 'none' ) {
            return true;
        }

        // Handle object format with 'style' key.
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $this->is_valid_box_shadow_string( $value['style'] );
        }

        // Handle object format with 'shadows' array.
        if ( is_array( $value ) && isset( $value['shadows'] ) ) {
            return $this->is_valid_box_shadow_array( $value['shadows'] );
        }

        // Handle object format with shadow properties directly.
        if ( is_array( $value ) && ( isset( $value['x'] ) || isset( $value['y'] ) || isset( $value['color'] ) ) ) {
            return $this->is_valid_box_shadow_object( $value );
        }

        // Handle CSS string format.
        if ( is_string( $value ) ) {
            return $this->is_valid_box_shadow_string( $value );
        }

        $this->add_error(
            'invalid_box_shadow_format',
            __( 'Box shadow must be a CSS string or structured object.', 'oxybridge-wp' ),
            $value
        );

        return false;
    }

    /**
     * Validate a box-shadow CSS string.
     *
     * Supports single and multiple shadows (comma-separated).
     *
     * @since 1.0.0
     *
     * @param string $value The box-shadow CSS string.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_box_shadow_string( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return true;
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Split by comma for multiple shadows (but not commas in color functions).
        $shadows = $this->split_box_shadow_string( $value );

        foreach ( $shadows as $shadow ) {
            if ( ! $this->is_valid_single_box_shadow( $shadow ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Split a box-shadow string into individual shadows.
     *
     * Handles comma-separated shadows while preserving commas in color functions.
     *
     * @since 1.0.0
     *
     * @param string $value The box-shadow string.
     * @return array Array of individual shadow strings.
     */
    private function split_box_shadow_string( string $value ): array {
        $shadows = array();
        $current = '';
        $depth   = 0;

        for ( $i = 0; $i < strlen( $value ); $i++ ) {
            $char = $value[ $i ];

            if ( $char === '(' ) {
                $depth++;
            } elseif ( $char === ')' ) {
                $depth--;
            } elseif ( $char === ',' && $depth === 0 ) {
                $shadows[] = trim( $current );
                $current   = '';
                continue;
            }

            $current .= $char;
        }

        if ( ! empty( trim( $current ) ) ) {
            $shadows[] = trim( $current );
        }

        return $shadows;
    }

    /**
     * Validate a single box-shadow value.
     *
     * Format: [inset] <offset-x> <offset-y> [blur-radius] [spread-radius] [color]
     *
     * @since 1.0.0
     *
     * @param string $shadow The single shadow string.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_single_box_shadow( string $shadow ): bool {
        $shadow = trim( $shadow );

        if ( empty( $shadow ) ) {
            return true;
        }

        // Check for 'inherit', 'initial', 'unset'.
        $keywords = array( 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $shadow ), $keywords, true ) ) {
            return true;
        }

        // Parse components.
        $components = $this->parse_box_shadow_components( $shadow );

        if ( empty( $components ) ) {
            $this->add_error(
                'invalid_box_shadow_syntax',
                __( 'Could not parse box-shadow components.', 'oxybridge-wp' ),
                $shadow
            );
            return false;
        }

        // Validate extracted components.
        return $this->validate_box_shadow_components( $components );
    }

    /**
     * Parse box-shadow string into components.
     *
     * @since 1.0.0
     *
     * @param string $shadow The shadow string.
     * @return array The parsed components.
     */
    private function parse_box_shadow_components( string $shadow ): array {
        $components = array(
            'inset'  => false,
            'x'      => null,
            'y'      => null,
            'blur'   => null,
            'spread' => null,
            'color'  => null,
        );

        // Check for inset keyword (can be at start or end).
        if ( preg_match( '/\binset\b/i', $shadow ) ) {
            $components['inset'] = true;
            $shadow = preg_replace( '/\binset\b/i', '', $shadow );
            $shadow = trim( $shadow );
        }

        // Extract color value (can be at start or end).
        $color = $this->extract_color_from_shadow( $shadow );
        if ( null !== $color ) {
            $components['color'] = $color['value'];
            $shadow = trim( $color['remaining'] );
        }

        // Parse remaining length values.
        $lengths = $this->extract_length_values( $shadow );

        if ( count( $lengths ) < 2 ) {
            // At minimum, we need x and y offsets.
            $this->add_error(
                'box_shadow_missing_offsets',
                __( 'Box shadow requires at least x and y offset values.', 'oxybridge-wp' ),
                $shadow
            );
            return array();
        }

        $components['x'] = $lengths[0];
        $components['y'] = $lengths[1];

        if ( isset( $lengths[2] ) ) {
            $components['blur'] = $lengths[2];
        }

        if ( isset( $lengths[3] ) ) {
            $components['spread'] = $lengths[3];
        }

        return $components;
    }

    /**
     * Extract color value from shadow string.
     *
     * @since 1.0.0
     *
     * @param string $shadow The shadow string.
     * @return array|null Array with 'value' and 'remaining' or null if no color found.
     */
    private function extract_color_from_shadow( string $shadow ): ?array {
        $shadow = trim( $shadow );

        // Try to extract rgba/rgb/hsla/hsl color functions.
        if ( preg_match( '/(rgba?\s*\([^)]+\)|hsla?\s*\([^)]+\))/i', $shadow, $matches ) ) {
            $color     = $matches[1];
            $remaining = str_replace( $color, '', $shadow );
            return array(
                'value'     => trim( $color ),
                'remaining' => trim( $remaining ),
            );
        }

        // Try to extract hex color.
        if ( preg_match( '/(#[0-9a-fA-F]{3,8})\b/', $shadow, $matches ) ) {
            $color     = $matches[1];
            $remaining = str_replace( $color, '', $shadow );
            return array(
                'value'     => trim( $color ),
                'remaining' => trim( $remaining ),
            );
        }

        // Try to extract CSS variable.
        if ( preg_match( '/(var\(--[\w-]+\))/', $shadow, $matches ) ) {
            $color     = $matches[1];
            $remaining = str_replace( $color, '', $shadow );
            return array(
                'value'     => trim( $color ),
                'remaining' => trim( $remaining ),
            );
        }

        // Try to extract named color (at end of string only to avoid conflicts).
        // Common named colors.
        $named_colors = array(
            'transparent', 'currentColor', 'black', 'white', 'red', 'green', 'blue',
            'yellow', 'orange', 'purple', 'pink', 'gray', 'grey', 'silver', 'gold',
            'aqua', 'cyan', 'magenta', 'lime', 'olive', 'navy', 'teal', 'maroon',
        );

        foreach ( $named_colors as $named ) {
            $pattern = '/\b(' . preg_quote( $named, '/' ) . ')\b/i';
            if ( preg_match( $pattern, $shadow, $matches ) ) {
                $remaining = preg_replace( $pattern, '', $shadow );
                // Only accept if remaining looks like lengths.
                $remaining_clean = trim( $remaining );
                if ( empty( $remaining_clean ) || preg_match( '/^[\d\s.\-pxemr%vwh]+$/', $remaining_clean ) ) {
                    return array(
                        'value'     => strtolower( $matches[1] ),
                        'remaining' => trim( $remaining ),
                    );
                }
            }
        }

        return null;
    }

    /**
     * Extract length values from a string.
     *
     * @since 1.0.0
     *
     * @param string $value The value string.
     * @return array Array of length values.
     */
    private function extract_length_values( string $value ): array {
        $lengths = array();

        // Match length values (number with optional unit).
        preg_match_all( '/(-?[\d.]+)(px|em|rem|%|vw|vh|vmin|vmax|cm|mm|in|pt|pc)?/i', $value, $matches, PREG_SET_ORDER );

        foreach ( $matches as $match ) {
            $lengths[] = array(
                'number' => floatval( $match[1] ),
                'unit'   => isset( $match[2] ) ? strtolower( $match[2] ) : 'px',
                'style'  => $match[0],
            );
        }

        return $lengths;
    }

    /**
     * Validate parsed box-shadow components.
     *
     * @since 1.0.0
     *
     * @param array $components The parsed components.
     * @return bool True if valid, false otherwise.
     */
    private function validate_box_shadow_components( array $components ): bool {
        // Validate x offset.
        if ( null === $components['x'] || ! $this->is_valid_length_value( $components['x'] ) ) {
            $this->add_error(
                'invalid_box_shadow_x_offset',
                __( 'Invalid box-shadow x offset value.', 'oxybridge-wp' ),
                $components['x']
            );
            return false;
        }

        // Validate y offset.
        if ( null === $components['y'] || ! $this->is_valid_length_value( $components['y'] ) ) {
            $this->add_error(
                'invalid_box_shadow_y_offset',
                __( 'Invalid box-shadow y offset value.', 'oxybridge-wp' ),
                $components['y']
            );
            return false;
        }

        // Validate blur radius (optional, must be positive if present).
        if ( null !== $components['blur'] ) {
            if ( ! $this->is_valid_length_value( $components['blur'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_blur',
                    __( 'Invalid box-shadow blur radius value.', 'oxybridge-wp' ),
                    $components['blur']
                );
                return false;
            }
            // Blur radius must be non-negative.
            if ( isset( $components['blur']['number'] ) && $components['blur']['number'] < 0 ) {
                $this->add_error(
                    'negative_box_shadow_blur',
                    __( 'Box-shadow blur radius cannot be negative.', 'oxybridge-wp' ),
                    $components['blur']
                );
                return false;
            }
        }

        // Validate spread radius (optional).
        if ( null !== $components['spread'] && ! $this->is_valid_length_value( $components['spread'] ) ) {
            $this->add_error(
                'invalid_box_shadow_spread',
                __( 'Invalid box-shadow spread radius value.', 'oxybridge-wp' ),
                $components['spread']
            );
            return false;
        }

        // Validate color (optional).
        if ( null !== $components['color'] && ! $this->is_valid_color( $components['color'] ) ) {
            $this->add_error(
                'invalid_box_shadow_color',
                __( 'Invalid box-shadow color value.', 'oxybridge-wp' ),
                $components['color']
            );
            return false;
        }

        // Inset is always valid (boolean).
        return true;
    }

    /**
     * Validate an array of box-shadow objects.
     *
     * @since 1.0.0
     *
     * @param array $shadows Array of shadow objects.
     * @return bool True if all valid, false otherwise.
     */
    public function is_valid_box_shadow_array( array $shadows ): bool {
        foreach ( $shadows as $index => $shadow ) {
            if ( ! $this->is_valid_box_shadow_object( $shadow ) ) {
                $this->add_error(
                    'invalid_box_shadow_item',
                    sprintf(
                        /* translators: %d: shadow index */
                        __( 'Invalid box-shadow at index %d.', 'oxybridge-wp' ),
                        $index
                    ),
                    $shadow
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a single box-shadow object.
     *
     * Object format:
     * {
     *   x: <length>,
     *   y: <length>,
     *   blur: <length>,      // optional
     *   spread: <length>,    // optional
     *   color: <color>,      // optional
     *   inset: <boolean>     // optional
     * }
     *
     * @since 1.0.0
     *
     * @param array $shadow The shadow object.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_box_shadow_object( array $shadow ): bool {
        // Validate x offset if present.
        if ( isset( $shadow['x'] ) ) {
            if ( ! $this->is_valid_length_or_number( $shadow['x'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_x',
                    __( 'Invalid x offset in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['x']
                );
                return false;
            }
        }

        // Validate y offset if present.
        if ( isset( $shadow['y'] ) ) {
            if ( ! $this->is_valid_length_or_number( $shadow['y'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_y',
                    __( 'Invalid y offset in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['y']
                );
                return false;
            }
        }

        // Validate blur if present.
        if ( isset( $shadow['blur'] ) ) {
            if ( ! $this->is_valid_length_or_number( $shadow['blur'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_blur',
                    __( 'Invalid blur radius in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['blur']
                );
                return false;
            }
            // Check for negative blur.
            $blur_value = $this->extract_numeric_value( $shadow['blur'] );
            if ( null !== $blur_value && $blur_value < 0 ) {
                $this->add_error(
                    'negative_box_shadow_object_blur',
                    __( 'Blur radius cannot be negative in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['blur']
                );
                return false;
            }
        }

        // Validate spread if present.
        if ( isset( $shadow['spread'] ) ) {
            if ( ! $this->is_valid_length_or_number( $shadow['spread'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_spread',
                    __( 'Invalid spread radius in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['spread']
                );
                return false;
            }
        }

        // Validate color if present.
        if ( isset( $shadow['color'] ) ) {
            if ( ! $this->is_valid_color( $shadow['color'] ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_color',
                    __( 'Invalid color in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['color']
                );
                return false;
            }
        }

        // Validate inset if present (must be boolean).
        if ( isset( $shadow['inset'] ) && ! is_bool( $shadow['inset'] ) ) {
            // Allow string 'true'/'false' or numeric 0/1.
            $inset_value = $shadow['inset'];
            if ( ! in_array( $inset_value, array( true, false, 'true', 'false', 0, 1, '0', '1' ), true ) ) {
                $this->add_error(
                    'invalid_box_shadow_object_inset',
                    __( 'Inset must be a boolean in box-shadow object.', 'oxybridge-wp' ),
                    $shadow['inset']
                );
                return false;
            }
        }

        // Validate position if present (alias for inset).
        if ( isset( $shadow['position'] ) ) {
            $position = strtolower( $shadow['position'] );
            if ( ! in_array( $position, self::$box_shadow_positions, true ) ) {
                $this->add_error(
                    'invalid_box_shadow_position',
                    __( 'Invalid position in box-shadow object. Must be "inset" or "outset".', 'oxybridge-wp' ),
                    $shadow['position']
                );
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // Color Validation
    // =========================================================================

    /**
     * Validate a CSS color value.
     *
     * Supports:
     * - Hex: #fff, #ffffff, #ffffffaa
     * - RGB: rgb(255, 255, 255)
     * - RGBA: rgba(255, 255, 255, 0.5)
     * - HSL: hsl(0, 0%, 100%)
     * - HSLA: hsla(0, 0%, 100%, 0.5)
     * - Named colors: red, blue, transparent, currentColor
     * - CSS variables: var(--my-color)
     *
     * @since 1.0.0
     *
     * @param mixed $value The color value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_color( $value ): bool {
        if ( empty( $value ) ) {
            return true; // Empty is valid (no color).
        }

        if ( ! is_string( $value ) ) {
            return false;
        }

        $value = trim( $value );

        foreach ( self::$color_patterns as $pattern ) {
            if ( preg_match( $pattern, $value ) ) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Length/Unit Validation
    // =========================================================================

    /**
     * Validate a CSS length value.
     *
     * @since 1.0.0
     *
     * @param mixed $value The length value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_length_value( $value ): bool {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return true; // Empty is valid.
        }

        // Handle array format (unit object).
        if ( is_array( $value ) ) {
            if ( isset( $value['number'] ) ) {
                // Check unit if present.
                if ( isset( $value['unit'] ) && ! empty( $value['unit'] ) ) {
                    return in_array( strtolower( $value['unit'] ), self::$length_units, true );
                }
                return is_numeric( $value['number'] );
            }
            // Check for 'style' property (raw CSS string).
            if ( isset( $value['style'] ) ) {
                return $this->is_valid_length_string( $value['style'] );
            }
            return false;
        }

        // Handle string format.
        if ( is_string( $value ) || is_numeric( $value ) ) {
            return $this->is_valid_length_string( (string) $value );
        }

        return false;
    }

    /**
     * Validate a CSS length string.
     *
     * @since 1.0.0
     *
     * @param string $value The length string.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_length_string( string $value ): bool {
        $value = trim( $value );

        // Pure number (0 is valid without unit).
        if ( is_numeric( $value ) ) {
            return true;
        }

        // Check for CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Number with unit.
        if ( preg_match( '/^(-?[\d.]+)(' . implode( '|', self::$length_units ) . ')$/i', $value ) ) {
            return true;
        }

        // Auto, inherit, initial, unset.
        $keywords = array( 'auto', 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $value ), $keywords, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate a length or number value.
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_length_or_number( $value ): bool {
        if ( is_numeric( $value ) ) {
            return true;
        }
        return $this->is_valid_length_value( $value );
    }

    /**
     * Extract numeric value from various formats.
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to extract from.
     * @return float|null The numeric value or null if not extractable.
     */
    private function extract_numeric_value( $value ): ?float {
        if ( is_numeric( $value ) ) {
            return floatval( $value );
        }

        if ( is_array( $value ) && isset( $value['number'] ) ) {
            return floatval( $value['number'] );
        }

        if ( is_string( $value ) ) {
            if ( preg_match( '/^(-?[\d.]+)/', $value, $matches ) ) {
                return floatval( $matches[1] );
            }
        }

        return null;
    }

    // =========================================================================
    // CSS Variable Validation
    // =========================================================================

    /**
     * Validate a CSS variable reference.
     *
     * Supports multiple CSS variable formats:
     * - Basic: var(--custom-prop)
     * - With fallback: var(--custom-prop, fallback-value)
     * - With nested fallback: var(--custom-prop, var(--fallback-prop))
     * - Multiple levels of nesting are supported
     *
     * Examples:
     * - var(--primary-color)
     * - var(--spacing, 16px)
     * - var(--main-color, #ff0000)
     * - var(--theme-color, var(--fallback-color, blue))
     * - var(--font-size, var(--base-size, 1rem))
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to validate.
     * @return bool True if valid CSS variable format, false otherwise.
     */
    public function is_valid_css_variable( $value ): bool {
        if ( ! is_string( $value ) ) {
            return false;
        }

        $value = trim( $value );

        // Must start with var( and end with ).
        if ( ! preg_match( '/^var\(/', $value ) || substr( $value, -1 ) !== ')' ) {
            return false;
        }

        // Extract the content inside var().
        $inner = substr( $value, 4, -1 );

        return $this->is_valid_css_variable_inner( $inner );
    }

    /**
     * Validate the inner content of a CSS variable.
     *
     * @since 1.0.0
     *
     * @param string $inner The inner content of var().
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_css_variable_inner( string $inner ): bool {
        $inner = trim( $inner );

        if ( empty( $inner ) ) {
            return false;
        }

        // Check for comma (fallback value).
        $comma_pos = $this->find_top_level_comma( $inner );

        if ( false === $comma_pos ) {
            // No fallback, just validate the custom property name.
            return $this->is_valid_css_custom_property_name( $inner );
        }

        // Has fallback - split into property name and fallback value.
        $property_name = trim( substr( $inner, 0, $comma_pos ) );
        $fallback      = trim( substr( $inner, $comma_pos + 1 ) );

        // Validate property name.
        if ( ! $this->is_valid_css_custom_property_name( $property_name ) ) {
            return false;
        }

        // Validate fallback value (can be another var() or any value).
        return $this->is_valid_css_variable_fallback( $fallback );
    }

    /**
     * Find the position of a top-level comma (not inside nested parentheses).
     *
     * @since 1.0.0
     *
     * @param string $value The string to search.
     * @return int|false Position of comma or false if not found.
     */
    private function find_top_level_comma( string $value ) {
        $depth = 0;
        $len   = strlen( $value );

        for ( $i = 0; $i < $len; $i++ ) {
            $char = $value[ $i ];

            if ( $char === '(' ) {
                $depth++;
            } elseif ( $char === ')' ) {
                $depth--;
            } elseif ( $char === ',' && $depth === 0 ) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Validate a CSS custom property name.
     *
     * Custom property names must start with -- and contain valid characters.
     *
     * @since 1.0.0
     *
     * @param string $name The property name to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_css_custom_property_name( string $name ): bool {
        $name = trim( $name );

        // Must start with -- and contain word characters or hyphens.
        return (bool) preg_match( '/^--[\w-]+$/', $name );
    }

    /**
     * Validate a CSS variable fallback value.
     *
     * Fallback values can be:
     * - Another var() reference
     * - A color value
     * - A length value
     * - Any other valid CSS value
     *
     * @since 1.0.0
     *
     * @param string $fallback The fallback value to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_css_variable_fallback( string $fallback ): bool {
        $fallback = trim( $fallback );

        if ( empty( $fallback ) ) {
            return false;
        }

        // If fallback is another var(), validate it recursively.
        if ( preg_match( '/^var\(/', $fallback ) ) {
            return $this->is_valid_css_variable( $fallback );
        }

        // Allow any non-empty value as fallback (CSS is permissive).
        // The fallback can be colors, lengths, keywords, or complex values.
        // We do basic sanity checking:
        // - No unbalanced parentheses.
        // - No obviously invalid characters.
        return $this->has_balanced_parentheses( $fallback );
    }

    /**
     * Check if a string has balanced parentheses.
     *
     * @since 1.0.0
     *
     * @param string $value The string to check.
     * @return bool True if balanced, false otherwise.
     */
    private function has_balanced_parentheses( string $value ): bool {
        $depth = 0;
        $len   = strlen( $value );

        for ( $i = 0; $i < $len; $i++ ) {
            $char = $value[ $i ];

            if ( $char === '(' ) {
                $depth++;
            } elseif ( $char === ')' ) {
                $depth--;
                if ( $depth < 0 ) {
                    return false;
                }
            }
        }

        return $depth === 0;
    }

    /**
     * Check if a value contains a CSS variable reference.
     *
     * This method detects if var(--) appears anywhere in the value,
     * not just at the start. Useful for values like "calc(var(--size) * 2)".
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to check.
     * @return bool True if contains a CSS variable, false otherwise.
     */
    public function contains_css_variable( $value ): bool {
        if ( ! is_string( $value ) ) {
            return false;
        }

        return (bool) preg_match( '/var\(--[\w-]+/', $value );
    }

    /**
     * Extract CSS variable names from a value.
     *
     * Returns all custom property names found in the value.
     *
     * @since 1.0.0
     *
     * @param string $value The value to extract from.
     * @return array Array of custom property names (without var() wrapper).
     */
    public function extract_css_variable_names( string $value ): array {
        $names = array();

        if ( preg_match_all( '/var\((--[\w-]+)/', $value, $matches ) ) {
            $names = array_unique( $matches[1] );
        }

        return $names;
    }

    /**
     * Check if a value is a pure CSS variable (starts with var() and nothing else).
     *
     * This distinguishes between:
     * - Pure: var(--color)
     * - Embedded: calc(var(--size) + 10px)
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to check.
     * @return bool True if pure CSS variable, false otherwise.
     */
    public function is_pure_css_variable( $value ): bool {
        if ( ! is_string( $value ) ) {
            return false;
        }

        return $this->is_valid_css_variable( $value );
    }

    /**
     * Validate a CSS variable with optional type constraint.
     *
     * Validates that a CSS variable is syntactically correct and optionally
     * validates the fallback value against a specific type.
     *
     * @since 1.0.0
     *
     * @param mixed  $value         The value to validate.
     * @param string $expected_type Optional expected type for fallback ('color', 'length', 'number').
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_css_variable_of_type( $value, string $expected_type = '' ): bool {
        if ( ! $this->is_valid_css_variable( $value ) ) {
            return false;
        }

        if ( empty( $expected_type ) ) {
            return true;
        }

        // Extract fallback value if present.
        $fallback = $this->extract_css_variable_fallback( $value );

        if ( null === $fallback ) {
            // No fallback, can't validate type but var() is valid.
            return true;
        }

        // If fallback is another var(), recursively check.
        if ( preg_match( '/^var\(/', $fallback ) ) {
            return $this->is_valid_css_variable_of_type( $fallback, $expected_type );
        }

        // Validate fallback against expected type.
        switch ( $expected_type ) {
            case 'color':
                return $this->is_valid_color( $fallback );
            case 'length':
                return $this->is_valid_length_string( $fallback );
            case 'number':
                return is_numeric( $fallback );
            default:
                return true;
        }
    }

    /**
     * Extract the fallback value from a CSS variable.
     *
     * @since 1.0.0
     *
     * @param string $value The CSS variable to extract from.
     * @return string|null The fallback value or null if none.
     */
    public function extract_css_variable_fallback( string $value ): ?string {
        $value = trim( $value );

        if ( ! preg_match( '/^var\(/', $value ) || substr( $value, -1 ) !== ')' ) {
            return null;
        }

        $inner     = substr( $value, 4, -1 );
        $comma_pos = $this->find_top_level_comma( $inner );

        if ( false === $comma_pos ) {
            return null;
        }

        return trim( substr( $inner, $comma_pos + 1 ) );
    }

    // =========================================================================
    // Filter Validation
    // =========================================================================

    /**
     * Valid CSS filter function names.
     *
     * @var array
     */
    private static array $filter_functions = array(
        'blur',
        'brightness',
        'contrast',
        'drop-shadow',
        'grayscale',
        'hue-rotate',
        'invert',
        'opacity',
        'saturate',
        'sepia',
    );

    /**
     * Filter functions that accept percentage or decimal values.
     *
     * @var array
     */
    private static array $percentage_filters = array(
        'brightness',
        'contrast',
        'grayscale',
        'invert',
        'opacity',
        'saturate',
        'sepia',
    );

    /**
     * Validate a CSS filter property value.
     *
     * Accepts both CSS string format and structured object format.
     *
     * CSS Format Examples:
     * - "blur(5px)"
     * - "brightness(150%)"
     * - "contrast(200%) saturate(150%)"
     * - "blur(5px) brightness(1.2) grayscale(50%)"
     *
     * Object Format:
     * - { style: "blur(5px) brightness(150%)" }
     * - { filters: [{ type: "blur", blur_amount: 5 }, { type: "brightness", amount: 150 }] }
     *
     * @since 1.0.0
     *
     * @param mixed $value The filter value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_filter( $value ): bool {
        $this->clear_errors();

        if ( empty( $value ) ) {
            return true; // Empty value is valid (no filter).
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle "none" keyword.
        if ( is_string( $value ) && strtolower( trim( $value ) ) === 'none' ) {
            return true;
        }

        // Handle object format with 'style' key.
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $this->is_valid_filter_string( $value['style'] );
        }

        // Handle object format with 'filters' array.
        if ( is_array( $value ) && isset( $value['filters'] ) ) {
            return $this->is_valid_filter_array( $value['filters'] );
        }

        // Handle array as a direct filters array (without 'filters' key).
        if ( is_array( $value ) && ! empty( $value ) ) {
            // Check if it's an indexed array of filter objects.
            if ( isset( $value[0] ) && is_array( $value[0] ) ) {
                return $this->is_valid_filter_array( $value );
            }
            // Check if it's a single filter object with 'type' key.
            if ( isset( $value['type'] ) ) {
                return $this->is_valid_filter_object( $value );
            }
        }

        // Handle CSS string format.
        if ( is_string( $value ) ) {
            return $this->is_valid_filter_string( $value );
        }

        $this->add_error(
            'invalid_filter_format',
            __( 'Filter must be a CSS string or structured object.', 'oxybridge-wp' ),
            $value
        );

        return false;
    }

    /**
     * Validate a CSS filter string.
     *
     * Supports single and multiple filter functions.
     *
     * @since 1.0.0
     *
     * @param string $value The filter CSS string.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_filter_string( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return true;
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle inherit, initial, unset keywords.
        $keywords = array( 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $value ), $keywords, true ) ) {
            return true;
        }

        // Extract and validate each filter function.
        $filter_pattern = '/(' . implode( '|', self::$filter_functions ) . ')\s*\([^)]+\)/i';

        if ( ! preg_match_all( $filter_pattern, $value, $matches ) ) {
            $this->add_error(
                'invalid_filter_syntax',
                __( 'Could not parse filter functions.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Validate each matched filter function.
        foreach ( $matches[0] as $filter_match ) {
            if ( ! $this->is_valid_single_filter_function( $filter_match ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a single CSS filter function.
     *
     * @since 1.0.0
     *
     * @param string $filter The filter function string (e.g., "blur(5px)").
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_single_filter_function( string $filter ): bool {
        $filter = trim( $filter );

        // Parse function name and value.
        if ( ! preg_match( '/^(' . implode( '|', self::$filter_functions ) . ')\s*\(([^)]*)\)$/i', $filter, $matches ) ) {
            $this->add_error(
                'invalid_filter_function',
                __( 'Invalid filter function format.', 'oxybridge-wp' ),
                $filter
            );
            return false;
        }

        $function_name = strtolower( $matches[1] );
        $function_value = trim( $matches[2] );

        // Validate based on function type.
        switch ( $function_name ) {
            case 'blur':
                return $this->is_valid_blur_value( $function_value );

            case 'hue-rotate':
                return $this->is_valid_hue_rotate_value( $function_value );

            case 'drop-shadow':
                return $this->is_valid_drop_shadow_value( $function_value );

            case 'brightness':
            case 'contrast':
            case 'grayscale':
            case 'invert':
            case 'opacity':
            case 'saturate':
            case 'sepia':
                return $this->is_valid_percentage_filter_value( $function_value, $function_name );

            default:
                $this->add_error(
                    'unknown_filter_function',
                    sprintf(
                        /* translators: %s: filter function name */
                        __( 'Unknown filter function: %s', 'oxybridge-wp' ),
                        $function_name
                    ),
                    $filter
                );
                return false;
        }
    }

    /**
     * Validate blur filter value.
     *
     * Blur requires a length value (e.g., "5px", "0.5em").
     *
     * @since 1.0.0
     *
     * @param string $value The blur value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_blur_value( string $value ): bool {
        $value = trim( $value );

        // Blur must be a non-negative length.
        if ( ! $this->is_valid_length_string( $value ) ) {
            $this->add_error(
                'invalid_blur_value',
                __( 'Blur filter requires a valid length value (e.g., "5px").', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Check for negative value.
        $numeric = $this->extract_numeric_value( $value );
        if ( null !== $numeric && $numeric < 0 ) {
            $this->add_error(
                'negative_blur_value',
                __( 'Blur filter value cannot be negative.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate hue-rotate filter value.
     *
     * Hue-rotate requires an angle value (e.g., "90deg", "0.5turn").
     *
     * @since 1.0.0
     *
     * @param string $value The hue-rotate value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_hue_rotate_value( string $value ): bool {
        $value = trim( $value );

        // Valid angle units.
        $angle_pattern = '/^-?[\d.]+\s*(deg|rad|grad|turn)?$/i';

        if ( ! preg_match( $angle_pattern, $value ) ) {
            $this->add_error(
                'invalid_hue_rotate_value',
                __( 'Hue-rotate filter requires a valid angle value (e.g., "90deg").', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate percentage-based filter value.
     *
     * Accepts percentage (e.g., "150%") or decimal (e.g., "1.5").
     *
     * @since 1.0.0
     *
     * @param string $value         The filter value.
     * @param string $function_name The filter function name for error messages.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_percentage_filter_value( string $value, string $function_name ): bool {
        $value = trim( $value );

        // Accept percentage or decimal value.
        $percentage_pattern = '/^[\d.]+%?$/';

        if ( ! preg_match( $percentage_pattern, $value ) ) {
            $this->add_error(
                'invalid_' . str_replace( '-', '_', $function_name ) . '_value',
                sprintf(
                    /* translators: %s: filter function name */
                    __( '%s filter requires a percentage or decimal value (e.g., "150%%" or "1.5").', 'oxybridge-wp' ),
                    ucfirst( $function_name )
                ),
                $value
            );
            return false;
        }

        // Check for negative value (most filters don't accept negative).
        $numeric = floatval( $value );
        if ( $numeric < 0 ) {
            $this->add_error(
                'negative_' . str_replace( '-', '_', $function_name ) . '_value',
                sprintf(
                    /* translators: %s: filter function name */
                    __( '%s filter value cannot be negative.', 'oxybridge-wp' ),
                    ucfirst( $function_name )
                ),
                $value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate drop-shadow filter value.
     *
     * Drop-shadow has similar format to box-shadow but without spread.
     * Format: <offset-x> <offset-y> [blur-radius] [color]
     *
     * @since 1.0.0
     *
     * @param string $value The drop-shadow value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_drop_shadow_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_drop_shadow_value',
                __( 'Drop-shadow filter requires offset values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Extract color value if present.
        $color = $this->extract_color_from_shadow( $value );
        if ( null !== $color ) {
            $value = trim( $color['remaining'] );
            // Validate the color.
            if ( ! $this->is_valid_color( $color['value'] ) ) {
                $this->add_error(
                    'invalid_drop_shadow_color',
                    __( 'Invalid color in drop-shadow filter.', 'oxybridge-wp' ),
                    $color['value']
                );
                return false;
            }
        }

        // Parse remaining length values.
        $lengths = $this->extract_length_values( $value );

        // Need at least 2 values (x and y offset).
        if ( count( $lengths ) < 2 ) {
            $this->add_error(
                'drop_shadow_missing_offsets',
                __( 'Drop-shadow filter requires at least x and y offset values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Maximum 3 length values (x, y, blur).
        if ( count( $lengths ) > 3 ) {
            $this->add_error(
                'drop_shadow_too_many_values',
                __( 'Drop-shadow filter accepts at most 3 length values (x, y, blur).', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Validate blur is non-negative if present.
        if ( count( $lengths ) >= 3 && isset( $lengths[2]['number'] ) && $lengths[2]['number'] < 0 ) {
            $this->add_error(
                'negative_drop_shadow_blur',
                __( 'Drop-shadow blur radius cannot be negative.', 'oxybridge-wp' ),
                $lengths[2]
            );
            return false;
        }

        return true;
    }

    /**
     * Validate an array of filter objects.
     *
     * @since 1.0.0
     *
     * @param array $filters Array of filter objects.
     * @return bool True if all valid, false otherwise.
     */
    public function is_valid_filter_array( array $filters ): bool {
        foreach ( $filters as $index => $filter ) {
            if ( ! $this->is_valid_filter_object( $filter ) ) {
                $this->add_error(
                    'invalid_filter_item',
                    sprintf(
                        /* translators: %d: filter index */
                        __( 'Invalid filter at index %d.', 'oxybridge-wp' ),
                        $index
                    ),
                    $filter
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a single filter object.
     *
     * Breakdance object format:
     * {
     *   type: "blur" | "brightness" | "contrast" | "grayscale" | "hue-rotate" | "invert" | "saturate" | "sepia",
     *   blur_amount: <number|unit>,   // For blur type (px)
     *   amount: <number|unit>,        // For percentage-based types (%)
     *   rotate: <number|unit>         // For hue-rotate type (deg)
     * }
     *
     * @since 1.0.0
     *
     * @param array $filter The filter object.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_filter_object( array $filter ): bool {
        // Type is required.
        if ( ! isset( $filter['type'] ) ) {
            $this->add_error(
                'missing_filter_type',
                __( 'Filter object requires a "type" property.', 'oxybridge-wp' ),
                $filter
            );
            return false;
        }

        $type = strtolower( $filter['type'] );

        // Validate type is known.
        $valid_types = array( 'blur', 'brightness', 'contrast', 'grayscale', 'hue-rotate', 'invert', 'saturate', 'sepia' );
        if ( ! in_array( $type, $valid_types, true ) ) {
            $this->add_error(
                'invalid_filter_type',
                sprintf(
                    /* translators: %s: filter type */
                    __( 'Invalid filter type: %s. Must be one of: blur, brightness, contrast, grayscale, hue-rotate, invert, saturate, sepia.', 'oxybridge-wp' ),
                    $filter['type']
                ),
                $filter
            );
            return false;
        }

        // Validate type-specific properties.
        switch ( $type ) {
            case 'blur':
                return $this->is_valid_filter_object_blur( $filter );

            case 'hue-rotate':
                return $this->is_valid_filter_object_hue_rotate( $filter );

            case 'brightness':
            case 'contrast':
            case 'grayscale':
            case 'invert':
            case 'saturate':
            case 'sepia':
                return $this->is_valid_filter_object_percentage( $filter, $type );

            default:
                return true;
        }
    }

    /**
     * Validate blur filter object.
     *
     * @since 1.0.0
     *
     * @param array $filter The filter object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_filter_object_blur( array $filter ): bool {
        // blur_amount is the property for blur type.
        if ( ! isset( $filter['blur_amount'] ) ) {
            // Also accept 'amount' or 'value' as aliases.
            if ( ! isset( $filter['amount'] ) && ! isset( $filter['value'] ) ) {
                $this->add_error(
                    'missing_blur_amount',
                    __( 'Blur filter requires "blur_amount" property.', 'oxybridge-wp' ),
                    $filter
                );
                return false;
            }
            $blur_value = $filter['amount'] ?? $filter['value'];
        } else {
            $blur_value = $filter['blur_amount'];
        }

        // Validate the blur value.
        if ( ! $this->is_valid_length_or_number( $blur_value ) ) {
            $this->add_error(
                'invalid_blur_amount',
                __( 'Invalid blur_amount value in filter object.', 'oxybridge-wp' ),
                $blur_value
            );
            return false;
        }

        // Check for negative value.
        $numeric = $this->extract_numeric_value( $blur_value );
        if ( null !== $numeric && $numeric < 0 ) {
            $this->add_error(
                'negative_blur_amount',
                __( 'Blur amount cannot be negative.', 'oxybridge-wp' ),
                $blur_value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate hue-rotate filter object.
     *
     * @since 1.0.0
     *
     * @param array $filter The filter object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_filter_object_hue_rotate( array $filter ): bool {
        // rotate is the property for hue-rotate type.
        if ( ! isset( $filter['rotate'] ) ) {
            // Also accept 'amount' or 'value' as aliases.
            if ( ! isset( $filter['amount'] ) && ! isset( $filter['value'] ) ) {
                $this->add_error(
                    'missing_hue_rotate_value',
                    __( 'Hue-rotate filter requires "rotate" property.', 'oxybridge-wp' ),
                    $filter
                );
                return false;
            }
            $rotate_value = $filter['amount'] ?? $filter['value'];
        } else {
            $rotate_value = $filter['rotate'];
        }

        // Validate angle value.
        if ( is_numeric( $rotate_value ) ) {
            return true; // Plain number is valid (assumed degrees).
        }

        if ( is_string( $rotate_value ) ) {
            $angle_pattern = '/^-?[\d.]+\s*(deg|rad|grad|turn)?$/i';
            if ( ! preg_match( $angle_pattern, trim( $rotate_value ) ) ) {
                $this->add_error(
                    'invalid_hue_rotate_angle',
                    __( 'Invalid rotate value in hue-rotate filter object.', 'oxybridge-wp' ),
                    $rotate_value
                );
                return false;
            }
        }

        // Handle unit object format.
        if ( is_array( $rotate_value ) && isset( $rotate_value['number'] ) ) {
            if ( isset( $rotate_value['unit'] ) ) {
                $valid_angle_units = array( 'deg', 'rad', 'grad', 'turn' );
                if ( ! in_array( strtolower( $rotate_value['unit'] ), $valid_angle_units, true ) ) {
                    $this->add_error(
                        'invalid_hue_rotate_unit',
                        __( 'Invalid unit for hue-rotate. Must be deg, rad, grad, or turn.', 'oxybridge-wp' ),
                        $rotate_value
                    );
                    return false;
                }
            }
            return is_numeric( $rotate_value['number'] );
        }

        return true;
    }

    /**
     * Validate percentage-based filter object.
     *
     * @since 1.0.0
     *
     * @param array  $filter The filter object.
     * @param string $type   The filter type.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_filter_object_percentage( array $filter, string $type ): bool {
        // amount is the property for percentage-based types.
        if ( ! isset( $filter['amount'] ) ) {
            // Also accept 'value' as alias.
            if ( ! isset( $filter['value'] ) ) {
                $this->add_error(
                    'missing_filter_amount',
                    sprintf(
                        /* translators: %s: filter type */
                        __( '%s filter requires "amount" property.', 'oxybridge-wp' ),
                        ucfirst( $type )
                    ),
                    $filter
                );
                return false;
            }
            $amount_value = $filter['value'];
        } else {
            $amount_value = $filter['amount'];
        }

        // Validate the amount value.
        if ( is_numeric( $amount_value ) ) {
            // Check for negative value.
            if ( floatval( $amount_value ) < 0 ) {
                $this->add_error(
                    'negative_filter_amount',
                    sprintf(
                        /* translators: %s: filter type */
                        __( '%s filter amount cannot be negative.', 'oxybridge-wp' ),
                        ucfirst( $type )
                    ),
                    $amount_value
                );
                return false;
            }
            return true;
        }

        if ( is_string( $amount_value ) ) {
            $percentage_pattern = '/^[\d.]+%?$/';
            if ( ! preg_match( $percentage_pattern, trim( $amount_value ) ) ) {
                $this->add_error(
                    'invalid_filter_amount',
                    sprintf(
                        /* translators: %s: filter type */
                        __( 'Invalid amount value in %s filter object.', 'oxybridge-wp' ),
                        $type
                    ),
                    $amount_value
                );
                return false;
            }
            // Check for negative value.
            if ( floatval( $amount_value ) < 0 ) {
                $this->add_error(
                    'negative_filter_amount',
                    sprintf(
                        /* translators: %s: filter type */
                        __( '%s filter amount cannot be negative.', 'oxybridge-wp' ),
                        ucfirst( $type )
                    ),
                    $amount_value
                );
                return false;
            }
        }

        // Handle unit object format.
        if ( is_array( $amount_value ) && isset( $amount_value['number'] ) ) {
            if ( floatval( $amount_value['number'] ) < 0 ) {
                $this->add_error(
                    'negative_filter_amount',
                    sprintf(
                        /* translators: %s: filter type */
                        __( '%s filter amount cannot be negative.', 'oxybridge-wp' ),
                        ucfirst( $type )
                    ),
                    $amount_value
                );
                return false;
            }
            return is_numeric( $amount_value['number'] );
        }

        return true;
    }

    /**
     * Parse a CSS filter string into a structured array.
     *
     * @since 1.0.0
     *
     * @param string $value The filter CSS string.
     * @return array|null Parsed filter array or null if invalid.
     */
    public function parse_filter( string $value ): ?array {
        if ( ! $this->is_valid_filter_string( $value ) ) {
            return null;
        }

        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return array();
        }

        $filters = array();
        $filter_pattern = '/(' . implode( '|', self::$filter_functions ) . ')\s*\(([^)]*)\)/i';

        if ( preg_match_all( $filter_pattern, $value, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $match ) {
                $function_name = strtolower( $match[1] );
                $function_value = trim( $match[2] );

                $filter = array( 'type' => $function_name );

                switch ( $function_name ) {
                    case 'blur':
                        $filter['blur_amount'] = $this->parse_filter_value( $function_value, 'px' );
                        break;

                    case 'hue-rotate':
                        $filter['rotate'] = $this->parse_filter_value( $function_value, 'deg' );
                        break;

                    case 'brightness':
                    case 'contrast':
                    case 'grayscale':
                    case 'invert':
                    case 'opacity':
                    case 'saturate':
                    case 'sepia':
                        $filter['amount'] = $this->parse_filter_value( $function_value, '%' );
                        break;

                    case 'drop-shadow':
                        // Store as raw value for drop-shadow.
                        $filter['value'] = $function_value;
                        break;
                }

                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Parse a filter value into a unit object.
     *
     * @since 1.0.0
     *
     * @param string $value        The value to parse.
     * @param string $default_unit Default unit if not specified.
     * @return array The unit object with 'number' and 'unit' keys.
     */
    private function parse_filter_value( string $value, string $default_unit ): array {
        $value = trim( $value );

        // Match number with optional unit.
        if ( preg_match( '/^(-?[\d.]+)\s*(%|px|em|rem|deg|rad|grad|turn)?$/i', $value, $matches ) ) {
            return array(
                'number' => floatval( $matches[1] ),
                'unit'   => isset( $matches[2] ) && ! empty( $matches[2] ) ? strtolower( $matches[2] ) : $default_unit,
            );
        }

        // Return as-is if can't parse.
        return array(
            'number' => floatval( $value ),
            'unit'   => $default_unit,
        );
    }

    /**
     * Convert a filter array to CSS string.
     *
     * @since 1.0.0
     *
     * @param array $filters Array of filter objects.
     * @return string The CSS filter string.
     */
    public function filter_to_css( array $filters ): string {
        if ( empty( $filters ) ) {
            return 'none';
        }

        $parts = array();

        foreach ( $filters as $filter ) {
            if ( ! isset( $filter['type'] ) ) {
                continue;
            }

            $type = strtolower( $filter['type'] );
            $css_value = '';

            switch ( $type ) {
                case 'blur':
                    $value = $filter['blur_amount'] ?? $filter['amount'] ?? $filter['value'] ?? 0;
                    $css_value = 'blur(' . $this->value_to_css( $value, 'px' ) . ')';
                    break;

                case 'hue-rotate':
                    $value = $filter['rotate'] ?? $filter['amount'] ?? $filter['value'] ?? 0;
                    $css_value = 'hue-rotate(' . $this->value_to_css( $value, 'deg' ) . ')';
                    break;

                case 'brightness':
                case 'contrast':
                case 'grayscale':
                case 'invert':
                case 'opacity':
                case 'saturate':
                case 'sepia':
                    $value = $filter['amount'] ?? $filter['value'] ?? 100;
                    $css_value = $type . '(' . $this->value_to_css( $value, '%' ) . ')';
                    break;

                case 'drop-shadow':
                    $value = $filter['value'] ?? '';
                    if ( ! empty( $value ) ) {
                        $css_value = 'drop-shadow(' . $value . ')';
                    }
                    break;
            }

            if ( ! empty( $css_value ) ) {
                $parts[] = $css_value;
            }
        }

        return ! empty( $parts ) ? implode( ' ', $parts ) : 'none';
    }

    // =========================================================================
    // Transform Validation
    // =========================================================================

    /**
     * Valid CSS transform function names.
     *
     * @var array
     */
    private static array $transform_functions = array(
        'translate',
        'translateX',
        'translateY',
        'translateZ',
        'translate3d',
        'rotate',
        'rotateX',
        'rotateY',
        'rotateZ',
        'rotate3d',
        'scale',
        'scaleX',
        'scaleY',
        'scaleZ',
        'scale3d',
        'skew',
        'skewX',
        'skewY',
        'perspective',
        'matrix',
        'matrix3d',
    );

    /**
     * Valid CSS angle units.
     *
     * @var array
     */
    private static array $angle_units = array(
        'deg',
        'rad',
        'grad',
        'turn',
    );

    /**
     * Validate a CSS transform property value.
     *
     * Accepts both CSS string format and structured object format.
     *
     * CSS Format Examples:
     * - "rotate(45deg)"
     * - "scale(1.5)"
     * - "translate(10px, 20px)"
     * - "rotate(45deg) scale(1.5) translateX(10px)"
     *
     * Object Format:
     * - { style: "rotate(45deg) scale(1.5)" }
     * - { transforms: [{ type: "rotate", x: 45 }, { type: "scale", x: 1.5, y: 1.5 }] }
     *
     * @since 1.0.0
     *
     * @param mixed $value The transform value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_transform( $value ): bool {
        $this->clear_errors();

        if ( empty( $value ) ) {
            return true; // Empty value is valid (no transform).
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle "none" keyword.
        if ( is_string( $value ) && strtolower( trim( $value ) ) === 'none' ) {
            return true;
        }

        // Handle object format with 'style' key.
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $this->is_valid_transform_string( $value['style'] );
        }

        // Handle object format with 'transforms' array.
        if ( is_array( $value ) && isset( $value['transforms'] ) ) {
            return $this->is_valid_transform_array( $value['transforms'] );
        }

        // Handle array as a direct transforms array (without 'transforms' key).
        if ( is_array( $value ) && ! empty( $value ) ) {
            // Check if it's an indexed array of transform objects.
            if ( isset( $value[0] ) && is_array( $value[0] ) ) {
                return $this->is_valid_transform_array( $value );
            }
            // Check if it's a single transform object with 'type' key.
            if ( isset( $value['type'] ) ) {
                return $this->is_valid_transform_object( $value );
            }
        }

        // Handle CSS string format.
        if ( is_string( $value ) ) {
            return $this->is_valid_transform_string( $value );
        }

        $this->add_error(
            'invalid_transform_format',
            __( 'Transform must be a CSS string or structured object.', 'oxybridge-wp' ),
            $value
        );

        return false;
    }

    /**
     * Validate a CSS transform string.
     *
     * Supports single and multiple transform functions.
     *
     * @since 1.0.0
     *
     * @param string $value The transform CSS string.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_transform_string( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return true;
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle inherit, initial, unset keywords.
        $keywords = array( 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $value ), $keywords, true ) ) {
            return true;
        }

        // Build pattern for transform functions (case-insensitive for function name).
        $functions_pattern = implode( '|', array_map( function( $f ) {
            return preg_quote( $f, '/' );
        }, self::$transform_functions ) );
        $transform_pattern = '/(' . $functions_pattern . ')\s*\([^)]*\)/i';

        if ( ! preg_match_all( $transform_pattern, $value, $matches ) ) {
            $this->add_error(
                'invalid_transform_syntax',
                __( 'Could not parse transform functions.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Validate each matched transform function.
        foreach ( $matches[0] as $transform_match ) {
            if ( ! $this->is_valid_single_transform_function( $transform_match ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a single CSS transform function.
     *
     * @since 1.0.0
     *
     * @param string $transform The transform function string (e.g., "rotate(45deg)").
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_single_transform_function( string $transform ): bool {
        $transform = trim( $transform );

        // Build pattern for transform functions.
        $functions_pattern = implode( '|', array_map( function( $f ) {
            return preg_quote( $f, '/' );
        }, self::$transform_functions ) );

        // Parse function name and value.
        if ( ! preg_match( '/^(' . $functions_pattern . ')\s*\(([^)]*)\)$/i', $transform, $matches ) ) {
            $this->add_error(
                'invalid_transform_function',
                __( 'Invalid transform function format.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        $function_name  = strtolower( $matches[1] );
        $function_value = trim( $matches[2] );

        // Validate based on function type.
        switch ( $function_name ) {
            case 'rotate':
            case 'rotatex':
            case 'rotatey':
            case 'rotatez':
                return $this->is_valid_transform_rotate_value( $function_value );

            case 'rotate3d':
                return $this->is_valid_transform_rotate3d_value( $function_value );

            case 'scale':
            case 'scalex':
            case 'scaley':
            case 'scalez':
                return $this->is_valid_transform_scale_value( $function_value );

            case 'scale3d':
                return $this->is_valid_transform_scale3d_value( $function_value );

            case 'skew':
            case 'skewx':
            case 'skewy':
                return $this->is_valid_transform_skew_value( $function_value );

            case 'translate':
            case 'translatex':
            case 'translatey':
            case 'translatez':
                return $this->is_valid_transform_translate_value( $function_value );

            case 'translate3d':
                return $this->is_valid_transform_translate3d_value( $function_value );

            case 'perspective':
                return $this->is_valid_transform_perspective_value( $function_value );

            case 'matrix':
            case 'matrix3d':
                // Matrix values are complex; accept any numeric values separated by commas.
                return $this->is_valid_transform_matrix_value( $function_value );

            default:
                $this->add_error(
                    'unknown_transform_function',
                    sprintf(
                        /* translators: %s: transform function name */
                        __( 'Unknown transform function: %s', 'oxybridge-wp' ),
                        $function_name
                    ),
                    $transform
                );
                return false;
        }
    }

    /**
     * Validate rotate transform value.
     *
     * Rotate requires an angle value (e.g., "45deg", "0.5turn").
     *
     * @since 1.0.0
     *
     * @param string $value The rotate value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_rotate_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_rotate_value',
                __( 'Rotate transform requires an angle value.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Valid angle pattern.
        if ( ! $this->is_valid_angle_value( $value ) ) {
            $this->add_error(
                'invalid_rotate_value',
                __( 'Rotate transform requires a valid angle value (e.g., "45deg", "0.5turn").', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate rotate3d transform value.
     *
     * Rotate3d requires x, y, z vector and angle: rotate3d(x, y, z, angle).
     *
     * @since 1.0.0
     *
     * @param string $value The rotate3d value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_rotate3d_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_rotate3d_value',
                __( 'Rotate3d transform requires vector and angle values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma.
        $parts = array_map( 'trim', explode( ',', $value ) );

        if ( count( $parts ) !== 4 ) {
            $this->add_error(
                'invalid_rotate3d_format',
                __( 'Rotate3d transform requires 4 values: x, y, z, angle.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // First 3 values should be numbers (vector components).
        for ( $i = 0; $i < 3; $i++ ) {
            if ( ! is_numeric( $parts[ $i ] ) ) {
                $this->add_error(
                    'invalid_rotate3d_vector',
                    __( 'Rotate3d vector components must be numbers.', 'oxybridge-wp' ),
                    $parts[ $i ]
                );
                return false;
            }
        }

        // Fourth value should be an angle.
        if ( ! $this->is_valid_angle_value( $parts[3] ) ) {
            $this->add_error(
                'invalid_rotate3d_angle',
                __( 'Rotate3d angle must be a valid angle value.', 'oxybridge-wp' ),
                $parts[3]
            );
            return false;
        }

        return true;
    }

    /**
     * Validate scale transform value.
     *
     * Scale accepts one or two number values (e.g., "1.5" or "1.5, 2").
     *
     * @since 1.0.0
     *
     * @param string $value The scale value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_scale_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_scale_value',
                __( 'Scale transform requires a numeric value.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma for scale(x, y) format.
        $parts = array_map( 'trim', explode( ',', $value ) );

        // Maximum 2 values for scale().
        if ( count( $parts ) > 2 ) {
            $this->add_error(
                'too_many_scale_values',
                __( 'Scale transform accepts at most 2 values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be numbers.
        foreach ( $parts as $part ) {
            if ( ! is_numeric( $part ) ) {
                $this->add_error(
                    'invalid_scale_value',
                    __( 'Scale transform values must be numbers.', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate scale3d transform value.
     *
     * Scale3d requires exactly 3 number values: scale3d(sx, sy, sz).
     *
     * @since 1.0.0
     *
     * @param string $value The scale3d value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_scale3d_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_scale3d_value',
                __( 'Scale3d transform requires 3 numeric values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma.
        $parts = array_map( 'trim', explode( ',', $value ) );

        if ( count( $parts ) !== 3 ) {
            $this->add_error(
                'invalid_scale3d_format',
                __( 'Scale3d transform requires exactly 3 values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be numbers.
        foreach ( $parts as $part ) {
            if ( ! is_numeric( $part ) ) {
                $this->add_error(
                    'invalid_scale3d_value',
                    __( 'Scale3d transform values must be numbers.', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate skew transform value.
     *
     * Skew requires one or two angle values (e.g., "45deg" or "45deg, 30deg").
     *
     * @since 1.0.0
     *
     * @param string $value The skew value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_skew_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_skew_value',
                __( 'Skew transform requires an angle value.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma for skew(x, y) format.
        $parts = array_map( 'trim', explode( ',', $value ) );

        // Maximum 2 values for skew() (skewX and skewY only take 1).
        if ( count( $parts ) > 2 ) {
            $this->add_error(
                'too_many_skew_values',
                __( 'Skew transform accepts at most 2 angle values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be valid angles.
        foreach ( $parts as $part ) {
            if ( ! $this->is_valid_angle_value( $part ) ) {
                $this->add_error(
                    'invalid_skew_value',
                    __( 'Skew transform values must be valid angles (e.g., "45deg").', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate translate transform value.
     *
     * Translate accepts one or two length values (e.g., "10px" or "10px, 20px").
     *
     * @since 1.0.0
     *
     * @param string $value The translate value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_translate_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_translate_value',
                __( 'Translate transform requires a length value.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma for translate(x, y) format.
        $parts = array_map( 'trim', explode( ',', $value ) );

        // Maximum 2 values for translate().
        if ( count( $parts ) > 2 ) {
            $this->add_error(
                'too_many_translate_values',
                __( 'Translate transform accepts at most 2 values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be valid lengths.
        foreach ( $parts as $part ) {
            if ( ! $this->is_valid_length_string( $part ) ) {
                $this->add_error(
                    'invalid_translate_value',
                    __( 'Translate transform values must be valid lengths (e.g., "10px", "5%").', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate translate3d transform value.
     *
     * Translate3d requires exactly 3 length values: translate3d(tx, ty, tz).
     *
     * @since 1.0.0
     *
     * @param string $value The translate3d value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_translate3d_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_translate3d_value',
                __( 'Translate3d transform requires 3 length values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma.
        $parts = array_map( 'trim', explode( ',', $value ) );

        if ( count( $parts ) !== 3 ) {
            $this->add_error(
                'invalid_translate3d_format',
                __( 'Translate3d transform requires exactly 3 values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be valid lengths.
        foreach ( $parts as $part ) {
            if ( ! $this->is_valid_length_string( $part ) ) {
                $this->add_error(
                    'invalid_translate3d_value',
                    __( 'Translate3d transform values must be valid lengths.', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate perspective transform value.
     *
     * Perspective requires a positive length value (e.g., "500px").
     *
     * @since 1.0.0
     *
     * @param string $value The perspective value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_perspective_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_perspective_value',
                __( 'Perspective transform requires a length value.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Must be a valid length.
        if ( ! $this->is_valid_length_string( $value ) ) {
            $this->add_error(
                'invalid_perspective_value',
                __( 'Perspective transform requires a valid length value (e.g., "500px").', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Check for negative value (perspective must be positive).
        $numeric = $this->extract_numeric_value( $value );
        if ( null !== $numeric && $numeric < 0 ) {
            $this->add_error(
                'negative_perspective_value',
                __( 'Perspective transform value cannot be negative.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate matrix/matrix3d transform value.
     *
     * Matrix accepts 6 numeric values, matrix3d accepts 16.
     *
     * @since 1.0.0
     *
     * @param string $value The matrix value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_matrix_value( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            $this->add_error(
                'empty_matrix_value',
                __( 'Matrix transform requires numeric values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Split by comma.
        $parts = array_map( 'trim', explode( ',', $value ) );

        // matrix() needs 6 values, matrix3d() needs 16.
        if ( count( $parts ) !== 6 && count( $parts ) !== 16 ) {
            $this->add_error(
                'invalid_matrix_format',
                __( 'Matrix transform requires 6 values, matrix3d requires 16 values.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // All values must be numbers.
        foreach ( $parts as $part ) {
            if ( ! is_numeric( $part ) ) {
                $this->add_error(
                    'invalid_matrix_value',
                    __( 'Matrix transform values must be numbers.', 'oxybridge-wp' ),
                    $part
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate an array of transform objects.
     *
     * @since 1.0.0
     *
     * @param array $transforms Array of transform objects.
     * @return bool True if all valid, false otherwise.
     */
    public function is_valid_transform_array( array $transforms ): bool {
        foreach ( $transforms as $index => $transform ) {
            if ( ! $this->is_valid_transform_object( $transform ) ) {
                $this->add_error(
                    'invalid_transform_item',
                    sprintf(
                        /* translators: %d: transform index */
                        __( 'Invalid transform at index %d.', 'oxybridge-wp' ),
                        $index
                    ),
                    $transform
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Validate a single transform object.
     *
     * Breakdance object format:
     * {
     *   type: "rotate" | "rotate3d" | "scale" | "scale3d" | "skew" | "translate" | "perspective",
     *   x: <number|unit>,        // For rotate/scale/skew/translate
     *   y: <number|unit>,        // For scale/skew/translate
     *   z: <number|unit>,        // For 3d transforms
     *   angle: <number|unit>,    // For rotate3d
     *   value: <number|unit>     // For perspective
     * }
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_transform_object( array $transform ): bool {
        // Type is required.
        if ( ! isset( $transform['type'] ) ) {
            $this->add_error(
                'missing_transform_type',
                __( 'Transform object requires a "type" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        $type = strtolower( $transform['type'] );

        // Validate type is known.
        $valid_types = array( 'rotate', 'rotate3d', 'scale', 'scale3d', 'skew', 'translate', 'perspective' );
        if ( ! in_array( $type, $valid_types, true ) ) {
            $this->add_error(
                'invalid_transform_type',
                sprintf(
                    /* translators: %s: transform type */
                    __( 'Invalid transform type: %s. Must be one of: rotate, rotate3d, scale, scale3d, skew, translate, perspective.', 'oxybridge-wp' ),
                    $transform['type']
                ),
                $transform
            );
            return false;
        }

        // Validate type-specific properties.
        switch ( $type ) {
            case 'rotate':
                return $this->is_valid_transform_object_rotate( $transform );

            case 'rotate3d':
                return $this->is_valid_transform_object_rotate3d( $transform );

            case 'scale':
                return $this->is_valid_transform_object_scale( $transform );

            case 'scale3d':
                return $this->is_valid_transform_object_scale3d( $transform );

            case 'skew':
                return $this->is_valid_transform_object_skew( $transform );

            case 'translate':
                return $this->is_valid_transform_object_translate( $transform );

            case 'perspective':
                return $this->is_valid_transform_object_perspective( $transform );

            default:
                return true;
        }
    }

    /**
     * Validate rotate transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_rotate( array $transform ): bool {
        // x is the primary property for rotate.
        $angle_value = $transform['x'] ?? $transform['angle'] ?? $transform['value'] ?? null;

        if ( null === $angle_value ) {
            $this->add_error(
                'missing_rotate_angle',
                __( 'Rotate transform requires an "x" or "angle" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        // Validate angle value.
        if ( ! $this->is_valid_angle_or_number( $angle_value ) ) {
            $this->add_error(
                'invalid_rotate_angle',
                __( 'Invalid angle value in rotate transform object.', 'oxybridge-wp' ),
                $angle_value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate rotate3d transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_rotate3d( array $transform ): bool {
        // Validate vector components (x, y, z).
        foreach ( array( 'x', 'y', 'z' ) as $component ) {
            if ( isset( $transform[ $component ] ) ) {
                $value = $this->extract_numeric_value( $transform[ $component ] );
                if ( null === $value ) {
                    $this->add_error(
                        'invalid_rotate3d_' . $component,
                        sprintf(
                            /* translators: %s: component name */
                            __( 'Invalid %s value in rotate3d transform object.', 'oxybridge-wp' ),
                            $component
                        ),
                        $transform[ $component ]
                    );
                    return false;
                }
            }
        }

        // Validate angle.
        if ( isset( $transform['angle'] ) ) {
            if ( ! $this->is_valid_angle_or_number( $transform['angle'] ) ) {
                $this->add_error(
                    'invalid_rotate3d_angle',
                    __( 'Invalid angle value in rotate3d transform object.', 'oxybridge-wp' ),
                    $transform['angle']
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate scale transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_scale( array $transform ): bool {
        // At least one of x, y, or value should be present.
        $has_value = isset( $transform['x'] ) || isset( $transform['y'] ) || isset( $transform['value'] );

        if ( ! $has_value ) {
            $this->add_error(
                'missing_scale_value',
                __( 'Scale transform requires "x", "y", or "value" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        // Validate each component.
        foreach ( array( 'x', 'y', 'value' ) as $component ) {
            if ( isset( $transform[ $component ] ) ) {
                $value = $this->extract_numeric_value( $transform[ $component ] );
                if ( null === $value ) {
                    $this->add_error(
                        'invalid_scale_' . $component,
                        sprintf(
                            /* translators: %s: component name */
                            __( 'Invalid %s value in scale transform object.', 'oxybridge-wp' ),
                            $component
                        ),
                        $transform[ $component ]
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate scale3d transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_scale3d( array $transform ): bool {
        // Validate each component (x, y, z).
        foreach ( array( 'x', 'y', 'z' ) as $component ) {
            if ( isset( $transform[ $component ] ) ) {
                $value = $this->extract_numeric_value( $transform[ $component ] );
                if ( null === $value ) {
                    $this->add_error(
                        'invalid_scale3d_' . $component,
                        sprintf(
                            /* translators: %s: component name */
                            __( 'Invalid %s value in scale3d transform object.', 'oxybridge-wp' ),
                            $component
                        ),
                        $transform[ $component ]
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate skew transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_skew( array $transform ): bool {
        // At least one of x or y should be present.
        $has_value = isset( $transform['x'] ) || isset( $transform['y'] ) || isset( $transform['value'] );

        if ( ! $has_value ) {
            $this->add_error(
                'missing_skew_value',
                __( 'Skew transform requires "x", "y", or "value" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        // Validate each angle component.
        foreach ( array( 'x', 'y', 'value' ) as $component ) {
            if ( isset( $transform[ $component ] ) ) {
                if ( ! $this->is_valid_angle_or_number( $transform[ $component ] ) ) {
                    $this->add_error(
                        'invalid_skew_' . $component,
                        sprintf(
                            /* translators: %s: component name */
                            __( 'Invalid %s angle value in skew transform object.', 'oxybridge-wp' ),
                            $component
                        ),
                        $transform[ $component ]
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate translate transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_translate( array $transform ): bool {
        // At least one of x, y, z, or value should be present.
        $has_value = isset( $transform['x'] ) || isset( $transform['y'] ) || isset( $transform['z'] ) || isset( $transform['value'] );

        if ( ! $has_value ) {
            $this->add_error(
                'missing_translate_value',
                __( 'Translate transform requires "x", "y", "z", or "value" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        // Validate each length component.
        foreach ( array( 'x', 'y', 'z', 'value' ) as $component ) {
            if ( isset( $transform[ $component ] ) ) {
                if ( ! $this->is_valid_length_or_number( $transform[ $component ] ) ) {
                    $this->add_error(
                        'invalid_translate_' . $component,
                        sprintf(
                            /* translators: %s: component name */
                            __( 'Invalid %s length value in translate transform object.', 'oxybridge-wp' ),
                            $component
                        ),
                        $transform[ $component ]
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate perspective transform object.
     *
     * @since 1.0.0
     *
     * @param array $transform The transform object.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_transform_object_perspective( array $transform ): bool {
        // Value is required.
        $perspective_value = $transform['value'] ?? $transform['distance'] ?? null;

        if ( null === $perspective_value ) {
            $this->add_error(
                'missing_perspective_value',
                __( 'Perspective transform requires a "value" or "distance" property.', 'oxybridge-wp' ),
                $transform
            );
            return false;
        }

        // Must be a valid length.
        if ( ! $this->is_valid_length_or_number( $perspective_value ) ) {
            $this->add_error(
                'invalid_perspective_value',
                __( 'Invalid value in perspective transform object.', 'oxybridge-wp' ),
                $perspective_value
            );
            return false;
        }

        // Check for negative value.
        $numeric = $this->extract_numeric_value( $perspective_value );
        if ( null !== $numeric && $numeric < 0 ) {
            $this->add_error(
                'negative_perspective_value',
                __( 'Perspective value cannot be negative.', 'oxybridge-wp' ),
                $perspective_value
            );
            return false;
        }

        return true;
    }

    /**
     * Validate an angle value (string format).
     *
     * Accepts values like "45deg", "0.5turn", "1.5rad", "100grad".
     *
     * @since 1.0.0
     *
     * @param string $value The angle value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_angle_value( string $value ): bool {
        $value = trim( $value );

        // Pure number is valid (assumed degrees).
        if ( is_numeric( $value ) ) {
            return true;
        }

        // Check for CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Number with angle unit.
        $angle_pattern = '/^-?[\d.]+\s*(' . implode( '|', self::$angle_units ) . ')$/i';
        if ( preg_match( $angle_pattern, $value ) ) {
            return true;
        }

        // CSS keywords.
        $keywords = array( 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $value ), $keywords, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate an angle or number value.
     *
     * Accepts numeric values, string angles, or unit objects.
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_angle_or_number( $value ): bool {
        if ( is_numeric( $value ) ) {
            return true;
        }

        if ( is_string( $value ) ) {
            return $this->is_valid_angle_value( $value );
        }

        // Handle unit object format.
        if ( is_array( $value ) && isset( $value['number'] ) ) {
            if ( ! is_numeric( $value['number'] ) ) {
                return false;
            }
            if ( isset( $value['unit'] ) && ! empty( $value['unit'] ) ) {
                return in_array( strtolower( $value['unit'] ), self::$angle_units, true );
            }
            return true; // No unit specified is valid (assumed degrees).
        }

        // Handle style format.
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $this->is_valid_angle_value( $value['style'] );
        }

        return false;
    }

    /**
     * Parse a CSS transform string into a structured array.
     *
     * @since 1.0.0
     *
     * @param string $value The transform CSS string.
     * @return array|null Parsed transform array or null if invalid.
     */
    public function parse_transform( string $value ): ?array {
        if ( ! $this->is_valid_transform_string( $value ) ) {
            return null;
        }

        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return array();
        }

        $transforms = array();
        $functions_pattern = implode( '|', array_map( function( $f ) {
            return preg_quote( $f, '/' );
        }, self::$transform_functions ) );
        $transform_pattern = '/(' . $functions_pattern . ')\s*\(([^)]*)\)/i';

        if ( preg_match_all( $transform_pattern, $value, $matches, PREG_SET_ORDER ) ) {
            foreach ( $matches as $match ) {
                $function_name  = strtolower( $match[1] );
                $function_value = trim( $match[2] );

                $transform = array( 'type' => $function_name );

                // Parse values based on function type.
                $parts = array_map( 'trim', explode( ',', $function_value ) );

                switch ( $function_name ) {
                    case 'rotate':
                    case 'rotatex':
                    case 'rotatey':
                    case 'rotatez':
                        $transform['x'] = $this->parse_angle_value( $parts[0] ?? '0' );
                        break;

                    case 'rotate3d':
                        $transform['x']     = floatval( $parts[0] ?? 0 );
                        $transform['y']     = floatval( $parts[1] ?? 0 );
                        $transform['z']     = floatval( $parts[2] ?? 0 );
                        $transform['angle'] = $this->parse_angle_value( $parts[3] ?? '0' );
                        break;

                    case 'scale':
                        $transform['x'] = floatval( $parts[0] ?? 1 );
                        $transform['y'] = floatval( $parts[1] ?? $parts[0] ?? 1 );
                        break;

                    case 'scalex':
                        $transform['x'] = floatval( $parts[0] ?? 1 );
                        break;

                    case 'scaley':
                        $transform['y'] = floatval( $parts[0] ?? 1 );
                        break;

                    case 'scalez':
                        $transform['z'] = floatval( $parts[0] ?? 1 );
                        break;

                    case 'scale3d':
                        $transform['x'] = floatval( $parts[0] ?? 1 );
                        $transform['y'] = floatval( $parts[1] ?? 1 );
                        $transform['z'] = floatval( $parts[2] ?? 1 );
                        break;

                    case 'skew':
                        $transform['x'] = $this->parse_angle_value( $parts[0] ?? '0' );
                        $transform['y'] = $this->parse_angle_value( $parts[1] ?? '0' );
                        break;

                    case 'skewx':
                        $transform['x'] = $this->parse_angle_value( $parts[0] ?? '0' );
                        break;

                    case 'skewy':
                        $transform['y'] = $this->parse_angle_value( $parts[0] ?? '0' );
                        break;

                    case 'translate':
                        $transform['x'] = $this->parse_length_value( $parts[0] ?? '0' );
                        $transform['y'] = $this->parse_length_value( $parts[1] ?? '0' );
                        break;

                    case 'translatex':
                        $transform['x'] = $this->parse_length_value( $parts[0] ?? '0' );
                        break;

                    case 'translatey':
                        $transform['y'] = $this->parse_length_value( $parts[0] ?? '0' );
                        break;

                    case 'translatez':
                        $transform['z'] = $this->parse_length_value( $parts[0] ?? '0' );
                        break;

                    case 'translate3d':
                        $transform['x'] = $this->parse_length_value( $parts[0] ?? '0' );
                        $transform['y'] = $this->parse_length_value( $parts[1] ?? '0' );
                        $transform['z'] = $this->parse_length_value( $parts[2] ?? '0' );
                        break;

                    case 'perspective':
                        $transform['value'] = $this->parse_length_value( $parts[0] ?? '0' );
                        break;

                    case 'matrix':
                    case 'matrix3d':
                        $transform['values'] = array_map( 'floatval', $parts );
                        break;
                }

                $transforms[] = $transform;
            }
        }

        return $transforms;
    }

    /**
     * Parse an angle value into a unit object.
     *
     * @since 1.0.0
     *
     * @param string $value The value to parse.
     * @return array The unit object with 'number' and 'unit' keys.
     */
    private function parse_angle_value( string $value ): array {
        $value = trim( $value );

        if ( preg_match( '/^(-?[\d.]+)\s*(deg|rad|grad|turn)?$/i', $value, $matches ) ) {
            return array(
                'number' => floatval( $matches[1] ),
                'unit'   => isset( $matches[2] ) && ! empty( $matches[2] ) ? strtolower( $matches[2] ) : 'deg',
            );
        }

        return array(
            'number' => floatval( $value ),
            'unit'   => 'deg',
        );
    }

    /**
     * Parse a length value into a unit object.
     *
     * @since 1.0.0
     *
     * @param string $value The value to parse.
     * @return array The unit object with 'number' and 'unit' keys.
     */
    private function parse_length_value( string $value ): array {
        $value = trim( $value );

        $units_pattern = implode( '|', self::$length_units );
        if ( preg_match( '/^(-?[\d.]+)\s*(' . $units_pattern . ')?$/i', $value, $matches ) ) {
            return array(
                'number' => floatval( $matches[1] ),
                'unit'   => isset( $matches[2] ) && ! empty( $matches[2] ) ? strtolower( $matches[2] ) : 'px',
            );
        }

        return array(
            'number' => floatval( $value ),
            'unit'   => 'px',
        );
    }

    /**
     * Convert a transform array to CSS string.
     *
     * @since 1.0.0
     *
     * @param array $transforms Array of transform objects.
     * @return string The CSS transform string.
     */
    public function transform_to_css( array $transforms ): string {
        if ( empty( $transforms ) ) {
            return 'none';
        }

        $parts = array();

        foreach ( $transforms as $transform ) {
            if ( ! isset( $transform['type'] ) ) {
                continue;
            }

            $type      = strtolower( $transform['type'] );
            $css_value = '';

            switch ( $type ) {
                case 'rotate':
                    $angle     = $transform['x'] ?? $transform['angle'] ?? $transform['value'] ?? 0;
                    $css_value = 'rotate(' . $this->value_to_css( $angle, 'deg' ) . ')';
                    break;

                case 'rotatex':
                    $angle     = $transform['x'] ?? $transform['value'] ?? 0;
                    $css_value = 'rotateX(' . $this->value_to_css( $angle, 'deg' ) . ')';
                    break;

                case 'rotatey':
                    $angle     = $transform['y'] ?? $transform['value'] ?? 0;
                    $css_value = 'rotateY(' . $this->value_to_css( $angle, 'deg' ) . ')';
                    break;

                case 'rotatez':
                    $angle     = $transform['z'] ?? $transform['value'] ?? 0;
                    $css_value = 'rotateZ(' . $this->value_to_css( $angle, 'deg' ) . ')';
                    break;

                case 'rotate3d':
                    $x         = $transform['x'] ?? 0;
                    $y         = $transform['y'] ?? 0;
                    $z         = $transform['z'] ?? 0;
                    $angle     = $transform['angle'] ?? $transform['value'] ?? 0;
                    $css_value = 'rotate3d(' . $x . ', ' . $y . ', ' . $z . ', ' . $this->value_to_css( $angle, 'deg' ) . ')';
                    break;

                case 'scale':
                    $x         = $transform['x'] ?? $transform['value'] ?? 1;
                    $y         = $transform['y'] ?? $x;
                    $css_value = 'scale(' . $x . ', ' . $y . ')';
                    break;

                case 'scalex':
                    $x         = $transform['x'] ?? $transform['value'] ?? 1;
                    $css_value = 'scaleX(' . $x . ')';
                    break;

                case 'scaley':
                    $y         = $transform['y'] ?? $transform['value'] ?? 1;
                    $css_value = 'scaleY(' . $y . ')';
                    break;

                case 'scalez':
                    $z         = $transform['z'] ?? $transform['value'] ?? 1;
                    $css_value = 'scaleZ(' . $z . ')';
                    break;

                case 'scale3d':
                    $x         = $transform['x'] ?? 1;
                    $y         = $transform['y'] ?? 1;
                    $z         = $transform['z'] ?? 1;
                    $css_value = 'scale3d(' . $x . ', ' . $y . ', ' . $z . ')';
                    break;

                case 'skew':
                    $x         = $transform['x'] ?? $transform['value'] ?? 0;
                    $y         = $transform['y'] ?? 0;
                    $css_value = 'skew(' . $this->value_to_css( $x, 'deg' ) . ', ' . $this->value_to_css( $y, 'deg' ) . ')';
                    break;

                case 'skewx':
                    $x         = $transform['x'] ?? $transform['value'] ?? 0;
                    $css_value = 'skewX(' . $this->value_to_css( $x, 'deg' ) . ')';
                    break;

                case 'skewy':
                    $y         = $transform['y'] ?? $transform['value'] ?? 0;
                    $css_value = 'skewY(' . $this->value_to_css( $y, 'deg' ) . ')';
                    break;

                case 'translate':
                    $x         = $transform['x'] ?? $transform['value'] ?? 0;
                    $y         = $transform['y'] ?? 0;
                    $css_value = 'translate(' . $this->value_to_css( $x, 'px' ) . ', ' . $this->value_to_css( $y, 'px' ) . ')';
                    break;

                case 'translatex':
                    $x         = $transform['x'] ?? $transform['value'] ?? 0;
                    $css_value = 'translateX(' . $this->value_to_css( $x, 'px' ) . ')';
                    break;

                case 'translatey':
                    $y         = $transform['y'] ?? $transform['value'] ?? 0;
                    $css_value = 'translateY(' . $this->value_to_css( $y, 'px' ) . ')';
                    break;

                case 'translatez':
                    $z         = $transform['z'] ?? $transform['value'] ?? 0;
                    $css_value = 'translateZ(' . $this->value_to_css( $z, 'px' ) . ')';
                    break;

                case 'translate3d':
                    $x         = $transform['x'] ?? 0;
                    $y         = $transform['y'] ?? 0;
                    $z         = $transform['z'] ?? 0;
                    $css_value = 'translate3d(' . $this->value_to_css( $x, 'px' ) . ', ' . $this->value_to_css( $y, 'px' ) . ', ' . $this->value_to_css( $z, 'px' ) . ')';
                    break;

                case 'perspective':
                    $value     = $transform['value'] ?? $transform['distance'] ?? 0;
                    $css_value = 'perspective(' . $this->value_to_css( $value, 'px' ) . ')';
                    break;

                case 'matrix':
                    $values    = $transform['values'] ?? array( 1, 0, 0, 1, 0, 0 );
                    $css_value = 'matrix(' . implode( ', ', $values ) . ')';
                    break;

                case 'matrix3d':
                    $values    = $transform['values'] ?? array( 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1 );
                    $css_value = 'matrix3d(' . implode( ', ', $values ) . ')';
                    break;
            }

            if ( ! empty( $css_value ) ) {
                $parts[] = $css_value;
            }
        }

        return ! empty( $parts ) ? implode( ' ', $parts ) : 'none';
    }

    // =========================================================================
    // Gradient Validation
    // =========================================================================

    /**
     * Valid CSS gradient types.
     *
     * @var array
     */
    private static array $gradient_types = array(
        'linear',
        'radial',
        'conic',
    );

    /**
     * Valid radial gradient shapes.
     *
     * @var array
     */
    private static array $radial_shapes = array(
        'circle',
        'ellipse',
    );

    /**
     * Valid radial gradient size keywords.
     *
     * @var array
     */
    private static array $radial_sizes = array(
        'closest-side',
        'closest-corner',
        'farthest-side',
        'farthest-corner',
    );

    /**
     * Valid gradient position keywords.
     *
     * @var array
     */
    private static array $gradient_positions = array(
        'center',
        'top',
        'bottom',
        'left',
        'right',
        'top left',
        'top right',
        'bottom left',
        'bottom right',
    );

    /**
     * Validate a CSS gradient property value.
     *
     * Accepts both CSS string format and structured object format.
     *
     * CSS Format Examples:
     * - "linear-gradient(180deg, #ff0000, #0000ff)"
     * - "radial-gradient(circle at center, #fff, #000)"
     * - "conic-gradient(from 0deg at center, red, blue)"
     *
     * Object Format:
     * - { style: "linear-gradient(180deg, #ff0000, #0000ff)" }
     * - { type: "linear", angle: "180deg", colors: ["#ff0000", "#0000ff"] }
     * - { type: "radial", position: "center", shape: "circle", colors: [...] }
     *
     * @since 1.0.0
     *
     * @param mixed $value The gradient value to validate.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient( $value ): bool {
        $this->clear_errors();

        if ( empty( $value ) ) {
            return true; // Empty value is valid (no gradient).
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle "none" keyword.
        if ( is_string( $value ) && strtolower( trim( $value ) ) === 'none' ) {
            return true;
        }

        // Handle object format with 'style' or 'value' key.
        if ( is_array( $value ) && ( isset( $value['style'] ) || isset( $value['value'] ) ) ) {
            $css_string = $value['style'] ?? $value['value'];
            return $this->is_valid_gradient_string( $css_string );
        }

        // Handle object format with 'type' key (structured object).
        if ( is_array( $value ) && isset( $value['type'] ) ) {
            return $this->is_valid_gradient_object( $value );
        }

        // Handle object format with 'colors' key (assumed linear).
        if ( is_array( $value ) && isset( $value['colors'] ) ) {
            return $this->is_valid_gradient_object( $value );
        }

        // Handle CSS string format.
        if ( is_string( $value ) ) {
            return $this->is_valid_gradient_string( $value );
        }

        $this->add_error(
            'invalid_gradient_format',
            __( 'Gradient must be a CSS string or structured object.', 'oxybridge-wp' ),
            $value
        );

        return false;
    }

    /**
     * Validate a CSS gradient string.
     *
     * Supports linear-gradient, radial-gradient, and conic-gradient.
     *
     * @since 1.0.0
     *
     * @param string $value The gradient CSS string.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient_string( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) || strtolower( $value ) === 'none' ) {
            return true;
        }

        // Handle CSS variable.
        if ( $this->is_valid_css_variable( $value ) ) {
            return true;
        }

        // Handle inherit, initial, unset keywords.
        $keywords = array( 'inherit', 'initial', 'unset', 'revert' );
        if ( in_array( strtolower( $value ), $keywords, true ) ) {
            return true;
        }

        // Check for valid gradient function prefix.
        $gradient_pattern = '/^(repeating-)?(linear|radial|conic)-gradient\s*\(/i';

        if ( ! preg_match( $gradient_pattern, $value, $matches ) ) {
            $this->add_error(
                'invalid_gradient_syntax',
                __( 'Gradient must start with linear-gradient(), radial-gradient(), or conic-gradient().', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Extract gradient type.
        $gradient_type = strtolower( $matches[2] );

        // Check for balanced parentheses.
        if ( substr_count( $value, '(' ) !== substr_count( $value, ')' ) ) {
            $this->add_error(
                'unbalanced_gradient_parentheses',
                __( 'Gradient has unbalanced parentheses.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Extract content between the outer parentheses.
        $content = $this->extract_gradient_content( $value );

        if ( null === $content ) {
            $this->add_error(
                'cannot_parse_gradient_content',
                __( 'Could not parse gradient content.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Validate based on gradient type.
        switch ( $gradient_type ) {
            case 'linear':
                return $this->is_valid_linear_gradient_content( $content );

            case 'radial':
                return $this->is_valid_radial_gradient_content( $content );

            case 'conic':
                return $this->is_valid_conic_gradient_content( $content );

            default:
                return true;
        }
    }

    /**
     * Extract content from gradient function.
     *
     * @since 1.0.0
     *
     * @param string $value The full gradient string.
     * @return string|null The content between parentheses or null if invalid.
     */
    private function extract_gradient_content( string $value ): ?string {
        // Find the opening parenthesis.
        $start = strpos( $value, '(' );
        if ( false === $start ) {
            return null;
        }

        // Find the matching closing parenthesis.
        $depth = 1;
        $end   = $start + 1;
        $len   = strlen( $value );

        while ( $depth > 0 && $end < $len ) {
            $char = $value[ $end ];
            if ( $char === '(' ) {
                $depth++;
            } elseif ( $char === ')' ) {
                $depth--;
            }
            $end++;
        }

        if ( $depth !== 0 ) {
            return null;
        }

        return trim( substr( $value, $start + 1, $end - $start - 2 ) );
    }

    /**
     * Validate linear gradient content.
     *
     * @since 1.0.0
     *
     * @param string $content The gradient content.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_linear_gradient_content( string $content ): bool {
        $parts = $this->split_gradient_parts( $content );

        if ( empty( $parts ) ) {
            $this->add_error(
                'empty_gradient_content',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        $first_part  = trim( $parts[0] );
        $color_start = 0;

        // First part could be direction (angle or "to <side>").
        if ( $this->is_valid_gradient_direction( $first_part ) ) {
            $color_start = 1;
        }

        // Need at least one color stop after direction.
        if ( count( $parts ) <= $color_start ) {
            $this->add_error(
                'gradient_missing_colors',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        // Validate remaining parts as color stops.
        for ( $i = $color_start; $i < count( $parts ); $i++ ) {
            if ( ! $this->is_valid_gradient_color_stop( $parts[ $i ] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate radial gradient content.
     *
     * @since 1.0.0
     *
     * @param string $content The gradient content.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_radial_gradient_content( string $content ): bool {
        $parts = $this->split_gradient_parts( $content );

        if ( empty( $parts ) ) {
            $this->add_error(
                'empty_gradient_content',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        $first_part  = trim( $parts[0] );
        $color_start = 0;

        // First part could be shape/size/position configuration.
        if ( $this->is_valid_radial_gradient_config( $first_part ) ) {
            $color_start = 1;
        }

        // Need at least one color stop.
        if ( count( $parts ) <= $color_start ) {
            $this->add_error(
                'gradient_missing_colors',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        // Validate remaining parts as color stops.
        for ( $i = $color_start; $i < count( $parts ); $i++ ) {
            if ( ! $this->is_valid_gradient_color_stop( $parts[ $i ] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate conic gradient content.
     *
     * @since 1.0.0
     *
     * @param string $content The gradient content.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_conic_gradient_content( string $content ): bool {
        $parts = $this->split_gradient_parts( $content );

        if ( empty( $parts ) ) {
            $this->add_error(
                'empty_gradient_content',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        $first_part  = trim( $parts[0] );
        $color_start = 0;

        // First part could be "from <angle> at <position>" configuration.
        if ( $this->is_valid_conic_gradient_config( $first_part ) ) {
            $color_start = 1;
        }

        // Need at least one color stop.
        if ( count( $parts ) <= $color_start ) {
            $this->add_error(
                'gradient_missing_colors',
                __( 'Gradient requires at least one color stop.', 'oxybridge-wp' ),
                $content
            );
            return false;
        }

        // Validate remaining parts as color stops.
        for ( $i = $color_start; $i < count( $parts ); $i++ ) {
            if ( ! $this->is_valid_gradient_color_stop( $parts[ $i ] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Split gradient content by commas while respecting nested functions.
     *
     * @since 1.0.0
     *
     * @param string $content The gradient content.
     * @return array Array of parts.
     */
    private function split_gradient_parts( string $content ): array {
        $parts   = array();
        $current = '';
        $depth   = 0;

        for ( $i = 0; $i < strlen( $content ); $i++ ) {
            $char = $content[ $i ];

            if ( $char === '(' ) {
                $depth++;
            } elseif ( $char === ')' ) {
                $depth--;
            } elseif ( $char === ',' && $depth === 0 ) {
                $parts[] = trim( $current );
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ( ! empty( trim( $current ) ) ) {
            $parts[] = trim( $current );
        }

        return $parts;
    }

    /**
     * Validate gradient direction (for linear gradients).
     *
     * Accepts:
     * - Angle: "45deg", "0.25turn", "100grad", "3.14rad"
     * - Side: "to top", "to right", "to bottom left"
     *
     * @since 1.0.0
     *
     * @param string $value The direction value.
     * @return bool True if valid direction, false otherwise.
     */
    public function is_valid_gradient_direction( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            return false;
        }

        // Check for angle.
        if ( $this->is_valid_angle_value( $value ) ) {
            return true;
        }

        // Check for "to <side>" syntax.
        if ( preg_match( '/^to\s+(top|bottom|left|right)(\s+(top|bottom|left|right))?$/i', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate radial gradient configuration.
     *
     * @since 1.0.0
     *
     * @param string $value The configuration value.
     * @return bool True if valid configuration, false otherwise.
     */
    private function is_valid_radial_gradient_config( string $value ): bool {
        $value = trim( strtolower( $value ) );

        if ( empty( $value ) ) {
            return false;
        }

        // If it's a valid color, this is not a config.
        if ( $this->is_valid_color( $value ) ) {
            return false;
        }

        // Check for shape keywords.
        foreach ( self::$radial_shapes as $shape ) {
            if ( strpos( $value, $shape ) !== false ) {
                return true;
            }
        }

        // Check for size keywords.
        foreach ( self::$radial_sizes as $size ) {
            if ( strpos( $value, $size ) !== false ) {
                return true;
            }
        }

        // Check for "at <position>" syntax.
        if ( preg_match( '/\bat\s+/i', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate conic gradient configuration.
     *
     * @since 1.0.0
     *
     * @param string $value The configuration value.
     * @return bool True if valid configuration, false otherwise.
     */
    private function is_valid_conic_gradient_config( string $value ): bool {
        $value = trim( strtolower( $value ) );

        if ( empty( $value ) ) {
            return false;
        }

        // If it's a valid color, this is not a config.
        if ( $this->is_valid_color( $value ) ) {
            return false;
        }

        // Check for "from <angle>" syntax.
        if ( preg_match( '/^from\s+/i', $value ) ) {
            return true;
        }

        // Check for "at <position>" syntax.
        if ( preg_match( '/\bat\s+/i', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate a gradient color stop.
     *
     * Accepts:
     * - Color only: "#ff0000", "red", "rgba(255,0,0,0.5)"
     * - Color with position: "#ff0000 50%", "red 100px"
     * - Color with two positions: "#ff0000 20% 30%"
     *
     * @since 1.0.0
     *
     * @param string $value The color stop value.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient_color_stop( string $value ): bool {
        $value = trim( $value );

        if ( empty( $value ) ) {
            return false;
        }

        // Try to extract color value.
        $color = $this->extract_color_from_color_stop( $value );

        if ( null === $color ) {
            $this->add_error(
                'invalid_gradient_color_stop',
                __( 'Invalid color in gradient color stop.', 'oxybridge-wp' ),
                $value
            );
            return false;
        }

        // Validate the extracted color.
        if ( ! $this->is_valid_color( $color['color'] ) ) {
            $this->add_error(
                'invalid_gradient_color',
                __( 'Invalid color value in gradient.', 'oxybridge-wp' ),
                $color['color']
            );
            return false;
        }

        // Validate remaining positions if any.
        if ( ! empty( $color['remaining'] ) ) {
            $positions = preg_split( '/\s+/', trim( $color['remaining'] ) );
            foreach ( $positions as $pos ) {
                $pos = trim( $pos );
                if ( empty( $pos ) ) {
                    continue;
                }
                if ( ! $this->is_valid_length_or_percentage( $pos ) ) {
                    $this->add_error(
                        'invalid_gradient_position',
                        __( 'Invalid position in gradient color stop.', 'oxybridge-wp' ),
                        $pos
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Extract color from a color stop.
     *
     * @since 1.0.0
     *
     * @param string $value The color stop value.
     * @return array|null Array with 'color' and 'remaining' or null if not found.
     */
    private function extract_color_from_color_stop( string $value ): ?array {
        $value = trim( $value );

        // Try to match color functions (rgb, rgba, hsl, hsla).
        if ( preg_match( '/^(rgba?\s*\([^)]+\)|hsla?\s*\([^)]+\))/i', $value, $matches ) ) {
            return array(
                'color'     => trim( $matches[1] ),
                'remaining' => trim( substr( $value, strlen( $matches[1] ) ) ),
            );
        }

        // Try to match hex color.
        if ( preg_match( '/^(#[0-9a-fA-F]{3,8})/i', $value, $matches ) ) {
            return array(
                'color'     => trim( $matches[1] ),
                'remaining' => trim( substr( $value, strlen( $matches[1] ) ) ),
            );
        }

        // Try to match CSS variable.
        if ( preg_match( '/^(var\(--[\w-]+\))/i', $value, $matches ) ) {
            return array(
                'color'     => trim( $matches[1] ),
                'remaining' => trim( substr( $value, strlen( $matches[1] ) ) ),
            );
        }

        // Try to match named color (word at start, followed by space or end).
        if ( preg_match( '/^([a-zA-Z]+)(?:\s+|$)/', $value, $matches ) ) {
            // Verify it's a valid color name (not a keyword).
            $potential_color = strtolower( $matches[1] );
            $reserved_words  = array( 'to', 'at', 'from' );
            if ( ! in_array( $potential_color, $reserved_words, true ) ) {
                return array(
                    'color'     => trim( $matches[1] ),
                    'remaining' => trim( substr( $value, strlen( $matches[1] ) ) ),
                );
            }
        }

        return null;
    }

    /**
     * Validate a length or percentage value.
     *
     * @since 1.0.0
     *
     * @param string $value The value to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_length_or_percentage( string $value ): bool {
        $value = trim( $value );

        // Pure number (0 is valid).
        if ( is_numeric( $value ) ) {
            return true;
        }

        // Number with unit.
        if ( preg_match( '/^(-?[\d.]+)(px|em|rem|%|vw|vh|vmin|vmax|cm|mm|in|pt|pc)$/i', $value ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate a structured gradient object.
     *
     * Object format:
     * {
     *   type: "linear" | "radial" | "conic",    // optional, defaults to "linear"
     *   angle: <angle>,                          // for linear/conic
     *   position: <position>,                    // for radial/conic
     *   shape: "circle" | "ellipse",            // for radial
     *   size: <size-keyword>,                    // for radial
     *   colors: [<color>, ...],                  // required
     *   stops: [<percentage>, ...]               // optional
     * }
     *
     * @since 1.0.0
     *
     * @param array $gradient The gradient object.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient_object( array $gradient ): bool {
        // Validate type if present.
        if ( isset( $gradient['type'] ) ) {
            if ( ! $this->is_valid_gradient_type( $gradient['type'] ) ) {
                return false;
            }
        }

        $type = strtolower( $gradient['type'] ?? 'linear' );

        // Validate angle for linear and conic gradients.
        if ( isset( $gradient['angle'] ) ) {
            if ( ! $this->is_valid_angle_or_number( $gradient['angle'] ) ) {
                $this->add_error(
                    'invalid_gradient_angle',
                    __( 'Invalid angle in gradient object.', 'oxybridge-wp' ),
                    $gradient['angle']
                );
                return false;
            }
        }

        // Validate position for radial and conic gradients.
        if ( isset( $gradient['position'] ) ) {
            if ( ! $this->is_valid_gradient_position( $gradient['position'] ) ) {
                $this->add_error(
                    'invalid_gradient_position_value',
                    __( 'Invalid position in gradient object.', 'oxybridge-wp' ),
                    $gradient['position']
                );
                return false;
            }
        }

        // Validate shape for radial gradients.
        if ( isset( $gradient['shape'] ) ) {
            if ( ! in_array( strtolower( $gradient['shape'] ), self::$radial_shapes, true ) ) {
                $this->add_error(
                    'invalid_gradient_shape',
                    __( 'Invalid shape in gradient object. Must be "circle" or "ellipse".', 'oxybridge-wp' ),
                    $gradient['shape']
                );
                return false;
            }
        }

        // Validate size for radial gradients.
        if ( isset( $gradient['size'] ) ) {
            if ( ! $this->is_valid_radial_size( $gradient['size'] ) ) {
                $this->add_error(
                    'invalid_gradient_size',
                    __( 'Invalid size in gradient object.', 'oxybridge-wp' ),
                    $gradient['size']
                );
                return false;
            }
        }

        // Validate colors array.
        if ( isset( $gradient['colors'] ) ) {
            if ( ! $this->is_valid_gradient_colors( $gradient['colors'] ) ) {
                return false;
            }
        }

        // Validate stops array if present.
        if ( isset( $gradient['stops'] ) ) {
            if ( ! $this->is_valid_gradient_stops( $gradient['stops'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate gradient type.
     *
     * @since 1.0.0
     *
     * @param string $type The gradient type.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient_type( string $type ): bool {
        $type = strtolower( trim( $type ) );

        if ( ! in_array( $type, self::$gradient_types, true ) ) {
            $this->add_error(
                'invalid_gradient_type',
                sprintf(
                    /* translators: %s: gradient type, %s: valid types */
                    __( 'Invalid gradient type: %1$s. Must be one of: %2$s.', 'oxybridge-wp' ),
                    $type,
                    implode( ', ', self::$gradient_types )
                ),
                $type
            );
            return false;
        }

        return true;
    }

    /**
     * Validate gradient position.
     *
     * @since 1.0.0
     *
     * @param mixed $position The position value.
     * @return bool True if valid, false otherwise.
     */
    public function is_valid_gradient_position( $position ): bool {
        if ( ! is_string( $position ) ) {
            return false;
        }

        $position = trim( strtolower( $position ) );

        // Check for keyword positions.
        if ( in_array( $position, self::$gradient_positions, true ) ) {
            return true;
        }

        // Check for percentage or length positions (e.g., "50% 50%", "100px 200px").
        if ( preg_match( '/^(-?[\d.]+)(px|em|rem|%|vw|vh)?(\s+(-?[\d.]+)(px|em|rem|%|vw|vh)?)?$/i', $position ) ) {
            return true;
        }

        // Check for combined keyword and length (e.g., "center 50%").
        if ( preg_match( '/^(center|top|bottom|left|right)\s+(-?[\d.]+)(px|em|rem|%|vw|vh)?$/i', $position ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate radial gradient size.
     *
     * @since 1.0.0
     *
     * @param mixed $size The size value.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_radial_size( $size ): bool {
        if ( ! is_string( $size ) ) {
            return false;
        }

        $size = trim( strtolower( $size ) );

        // Check for size keywords.
        if ( in_array( $size, self::$radial_sizes, true ) ) {
            return true;
        }

        // Check for explicit size values (e.g., "50px 100px" for ellipse).
        if ( preg_match( '/^[\d.]+(px|em|rem|%|vw|vh)?(\s+[\d.]+(px|em|rem|%|vw|vh)?)?$/i', $size ) ) {
            return true;
        }

        return false;
    }

    /**
     * Validate gradient colors array.
     *
     * @since 1.0.0
     *
     * @param array $colors Array of color values.
     * @return bool True if all colors valid, false otherwise.
     */
    public function is_valid_gradient_colors( array $colors ): bool {
        if ( empty( $colors ) ) {
            $this->add_error(
                'empty_gradient_colors',
                __( 'Gradient colors array cannot be empty.', 'oxybridge-wp' ),
                $colors
            );
            return false;
        }

        foreach ( $colors as $index => $color ) {
            // Handle array format with 'color' key.
            if ( is_array( $color ) && isset( $color['color'] ) ) {
                $color_value = $color['color'];
            } else {
                $color_value = $color;
            }

            if ( ! $this->is_valid_color( $color_value ) ) {
                $this->add_error(
                    'invalid_gradient_color_item',
                    sprintf(
                        /* translators: %d: color index */
                        __( 'Invalid color at index %d in gradient colors.', 'oxybridge-wp' ),
                        $index
                    ),
                    $color
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate gradient stops array.
     *
     * @since 1.0.0
     *
     * @param array $stops Array of stop positions.
     * @return bool True if all stops valid, false otherwise.
     */
    public function is_valid_gradient_stops( array $stops ): bool {
        foreach ( $stops as $index => $stop ) {
            // Handle array format with 'position' key.
            if ( is_array( $stop ) && isset( $stop['position'] ) ) {
                $position_value = $stop['position'];
            } else {
                $position_value = $stop;
            }

            if ( ! $this->is_valid_percentage_or_number( $position_value ) ) {
                $this->add_error(
                    'invalid_gradient_stop',
                    sprintf(
                        /* translators: %d: stop index */
                        __( 'Invalid stop position at index %d in gradient stops.', 'oxybridge-wp' ),
                        $index
                    ),
                    $stop
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a percentage or number value.
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_percentage_or_number( $value ): bool {
        if ( is_numeric( $value ) ) {
            return true;
        }

        if ( is_string( $value ) ) {
            $value = trim( $value );
            // Allow number with optional percentage sign.
            if ( preg_match( '/^-?[\d.]+%?$/', $value ) ) {
                return true;
            }
        }

        if ( is_array( $value ) && isset( $value['number'] ) ) {
            return is_numeric( $value['number'] );
        }

        return false;
    }

    /**
     * Parse a CSS gradient string into a structured object.
     *
     * @since 1.0.0
     *
     * @param string $value The gradient CSS string.
     * @return array|null Parsed gradient object or null if invalid.
     */
    public function parse_gradient( string $value ): ?array {
        if ( ! $this->is_valid_gradient_string( $value ) ) {
            return null;
        }

        $value = trim( $value );

        // Handle special keywords.
        if ( strtolower( $value ) === 'none' || empty( $value ) ) {
            return null;
        }

        // Determine gradient type.
        $type = 'linear';
        if ( preg_match( '/^(repeating-)?(linear|radial|conic)-gradient/i', $value, $matches ) ) {
            $type = strtolower( $matches[2] );
        }

        // Extract content.
        $content = $this->extract_gradient_content( $value );
        if ( null === $content ) {
            return null;
        }

        $parts = $this->split_gradient_parts( $content );

        $gradient = array(
            'type'   => $type,
            'colors' => array(),
            'stops'  => array(),
        );

        $color_start = 0;

        // Parse configuration (direction/position) from first part.
        $first_part = trim( $parts[0] ?? '' );

        if ( $type === 'linear' && $this->is_valid_gradient_direction( $first_part ) ) {
            $gradient['angle'] = $first_part;
            $color_start       = 1;
        } elseif ( $type === 'radial' && $this->is_valid_radial_gradient_config( $first_part ) ) {
            $this->parse_radial_config( $first_part, $gradient );
            $color_start = 1;
        } elseif ( $type === 'conic' && $this->is_valid_conic_gradient_config( $first_part ) ) {
            $this->parse_conic_config( $first_part, $gradient );
            $color_start = 1;
        }

        // Parse color stops.
        for ( $i = $color_start; $i < count( $parts ); $i++ ) {
            $stop_data = $this->extract_color_from_color_stop( $parts[ $i ] );
            if ( null !== $stop_data ) {
                $gradient['colors'][] = $stop_data['color'];
                if ( ! empty( $stop_data['remaining'] ) ) {
                    $gradient['stops'][] = trim( $stop_data['remaining'] );
                } else {
                    $gradient['stops'][] = null;
                }
            }
        }

        return $gradient;
    }

    /**
     * Parse radial gradient configuration.
     *
     * @since 1.0.0
     *
     * @param string $config   The configuration string.
     * @param array  $gradient The gradient array to populate.
     * @return void
     */
    private function parse_radial_config( string $config, array &$gradient ): void {
        // Extract position.
        if ( preg_match( '/\bat\s+(.+)$/i', $config, $matches ) ) {
            $gradient['position'] = trim( $matches[1] );
            $config               = trim( preg_replace( '/\bat\s+.+$/i', '', $config ) );
        }

        // Extract shape.
        foreach ( self::$radial_shapes as $shape ) {
            if ( stripos( $config, $shape ) !== false ) {
                $gradient['shape'] = $shape;
                $config            = str_ireplace( $shape, '', $config );
                break;
            }
        }

        // Extract size.
        foreach ( self::$radial_sizes as $size ) {
            if ( stripos( $config, $size ) !== false ) {
                $gradient['size'] = $size;
                break;
            }
        }
    }

    /**
     * Parse conic gradient configuration.
     *
     * @since 1.0.0
     *
     * @param string $config   The configuration string.
     * @param array  $gradient The gradient array to populate.
     * @return void
     */
    private function parse_conic_config( string $config, array &$gradient ): void {
        // Extract "from <angle>".
        if ( preg_match( '/\bfrom\s+([\d.]+(?:deg|rad|grad|turn)?)/i', $config, $matches ) ) {
            $gradient['angle'] = trim( $matches[1] );
        }

        // Extract "at <position>".
        if ( preg_match( '/\bat\s+(.+)$/i', $config, $matches ) ) {
            $gradient['position'] = trim( $matches[1] );
        }
    }

    /**
     * Convert a gradient object to CSS string.
     *
     * @since 1.0.0
     *
     * @param array $gradient The gradient object.
     * @return string The CSS gradient string.
     */
    public function gradient_to_css( array $gradient ): string {
        $type   = $gradient['type'] ?? 'linear';
        $colors = $gradient['colors'] ?? array();
        $stops  = $gradient['stops'] ?? array();

        if ( empty( $colors ) ) {
            return '';
        }

        // Build color stops string.
        $color_stops_parts = array();
        foreach ( $colors as $index => $color ) {
            $stop = $stops[ $index ] ?? null;
            if ( null !== $stop && $stop !== '' ) {
                $color_stops_parts[] = $color . ' ' . $stop;
            } else {
                $color_stops_parts[] = $color;
            }
        }
        $color_stops = implode( ', ', $color_stops_parts );

        switch ( $type ) {
            case 'radial':
                $position = $gradient['position'] ?? 'center';
                $shape    = $gradient['shape'] ?? 'ellipse';
                $size     = $gradient['size'] ?? 'farthest-corner';
                return "radial-gradient({$shape} {$size} at {$position}, {$color_stops})";

            case 'conic':
                $angle    = $gradient['angle'] ?? '0deg';
                $position = $gradient['position'] ?? 'center';
                if ( is_numeric( $angle ) ) {
                    $angle = $angle . 'deg';
                }
                return "conic-gradient(from {$angle} at {$position}, {$color_stops})";

            case 'linear':
            default:
                $angle = $gradient['angle'] ?? '180deg';
                if ( is_numeric( $angle ) ) {
                    $angle = $angle . 'deg';
                }
                return "linear-gradient({$angle}, {$color_stops})";
        }
    }

    // =========================================================================
    // Box Shadow Parsing Helpers
    // =========================================================================

    /**
     * Parse a box-shadow CSS string into a structured object.
     *
     * @since 1.0.0
     *
     * @param string $value The box-shadow CSS string.
     * @return array|null Parsed shadow object or null if invalid.
     */
    public function parse_box_shadow( string $value ): ?array {
        if ( ! $this->is_valid_box_shadow_string( $value ) ) {
            return null;
        }

        $shadows = $this->split_box_shadow_string( $value );
        $result  = array();

        foreach ( $shadows as $shadow ) {
            $components = $this->parse_box_shadow_components( $shadow );
            if ( ! empty( $components ) ) {
                $result[] = $components;
            }
        }

        return $result;
    }

    /**
     * Convert a box-shadow object to CSS string.
     *
     * @since 1.0.0
     *
     * @param array $shadow The shadow object.
     * @return string The CSS box-shadow string.
     */
    public function box_shadow_to_css( array $shadow ): string {
        $parts = array();

        // Inset keyword.
        if ( ! empty( $shadow['inset'] ) ) {
            $parts[] = 'inset';
        }

        // X offset.
        if ( isset( $shadow['x'] ) ) {
            $parts[] = $this->value_to_css( $shadow['x'], 'px' );
        } else {
            $parts[] = '0';
        }

        // Y offset.
        if ( isset( $shadow['y'] ) ) {
            $parts[] = $this->value_to_css( $shadow['y'], 'px' );
        } else {
            $parts[] = '0';
        }

        // Blur radius.
        if ( isset( $shadow['blur'] ) ) {
            $parts[] = $this->value_to_css( $shadow['blur'], 'px' );
        }

        // Spread radius.
        if ( isset( $shadow['spread'] ) ) {
            $parts[] = $this->value_to_css( $shadow['spread'], 'px' );
        }

        // Color.
        if ( isset( $shadow['color'] ) && ! empty( $shadow['color'] ) ) {
            $parts[] = $shadow['color'];
        }

        return implode( ' ', $parts );
    }

    /**
     * Convert a value to CSS string.
     *
     * @since 1.0.0
     *
     * @param mixed  $value        The value.
     * @param string $default_unit Default unit if not specified.
     * @return string The CSS string.
     */
    private function value_to_css( $value, string $default_unit = '' ): string {
        if ( is_array( $value ) ) {
            if ( isset( $value['style'] ) ) {
                return $value['style'];
            }
            if ( isset( $value['number'] ) ) {
                $unit = $value['unit'] ?? $default_unit;
                return $value['number'] . $unit;
            }
        }

        if ( is_numeric( $value ) ) {
            return $value . $default_unit;
        }

        return (string) $value;
    }
}
