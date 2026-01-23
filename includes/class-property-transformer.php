<?php
/**
 * Property Transformer Class
 *
 * Transforms simplified AI-friendly property names and values into
 * the complete nested array structure expected by Breakdance/Oxygen.
 * Works in conjunction with Property_Schema for property mappings.
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
 * Class Property_Transformer
 *
 * Transforms flat property arrays from AI input into properly nested
 * Breakdance/Oxygen design properties structure.
 *
 * Example usage:
 * ```php
 * $transformer = new Property_Transformer();
 * $effects = $transformer->build_effects_properties([
 *     'opacity' => 0.8,
 *     'boxShadow' => '0 4px 6px rgba(0,0,0,0.1)',
 *     'filterBlur' => '5px',
 *     'transitionDuration' => '300ms',
 * ]);
 * // Returns properly nested array for design.effects
 * ```
 *
 * @since 1.0.0
 */
class Property_Transformer {

    /**
     * Property Schema instance.
     *
     * @var Property_Schema
     */
    private Property_Schema $schema;

    /**
     * Default breakpoint for responsive properties.
     *
     * @var string
     */
    private string $default_breakpoint = 'breakpoint_base';

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param Property_Schema|null $schema Optional schema instance.
     */
    public function __construct( ?Property_Schema $schema = null ) {
        $this->schema = $schema ?? new Property_Schema();
    }

    /**
     * Build effects properties from simplified input.
     *
     * Transforms flat effects properties (opacity, boxShadow, filters, transitions)
     * into the nested structure expected by Breakdance.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The nested effects properties array.
     */
    public function build_effects_properties( array $node, string $breakpoint = 'breakpoint_base' ): array {
        $effects = array();

        // Build opacity properties.
        $effects = $this->merge_deep(
            $effects,
            $this->extract_opacity_props( $node, $breakpoint )
        );

        // Build box shadow properties.
        $effects = $this->merge_deep(
            $effects,
            $this->extract_box_shadow_props( $node, $breakpoint )
        );

        // Build mix blend mode properties.
        $effects = $this->merge_deep(
            $effects,
            $this->extract_mix_blend_mode_props( $node, $breakpoint )
        );

        // Build transition properties.
        $effects = $this->merge_deep(
            $effects,
            $this->extract_transition_props( $node, $breakpoint )
        );

        // Build filter properties.
        $effects = $this->merge_deep(
            $effects,
            $this->extract_filter_props( $node, $breakpoint )
        );

        return $effects;
    }

    /**
     * Build transform properties from simplified input.
     *
     * Transforms flat transform properties (rotate, scale, skew, translate, perspective)
     * into the nested structure expected by Breakdance.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The nested transform properties array.
     */
    public function build_transform_properties( array $node, string $breakpoint = 'breakpoint_base' ): array {
        $transform = array();

        // Build transform items (repeater array).
        $transform = $this->merge_deep(
            $transform,
            $this->extract_transform_props( $node, $breakpoint )
        );

        // Build transform origin properties.
        $transform = $this->merge_deep(
            $transform,
            $this->extract_transform_origin_props( $node, $breakpoint )
        );

        // Build perspective properties.
        $transform = $this->merge_deep(
            $transform,
            $this->extract_perspective_props( $node, $breakpoint )
        );

        // Build transform style properties.
        $transform = $this->merge_deep(
            $transform,
            $this->extract_transform_style_props( $node, $breakpoint )
        );

        return $transform;
    }

    /**
     * Extract transform items from node.
     *
     * Builds the transforms repeater array from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The transform properties.
     */
    private function extract_transform_props( array $node, string $breakpoint ): array {
        $props = array();

        // Build normal state transforms.
        $transforms = $this->extract_transform_items( $node, false );
        if ( ! empty( $transforms ) ) {
            $props['transforms'] = array(
                $breakpoint => $transforms,
            );
        }

        // Build hover state transforms.
        $transforms_hover = $this->extract_transform_items( $node, true );
        if ( ! empty( $transforms_hover ) ) {
            $props['transforms_hover'] = array(
                $breakpoint => $transforms_hover,
            );
        }

        // Handle complete transform array if provided directly.
        if ( isset( $node['transform'] ) && is_array( $node['transform'] ) ) {
            $transforms = array();
            foreach ( $node['transform'] as $t ) {
                if ( isset( $t['type'] ) ) {
                    $transforms[] = $this->build_transform_item_from_object( $t );
                }
            }
            if ( ! empty( $transforms ) ) {
                $props['transforms'] = array(
                    $breakpoint => $transforms,
                );
            }
        }

        if ( isset( $node['transformHover'] ) && is_array( $node['transformHover'] ) ) {
            $transforms = array();
            foreach ( $node['transformHover'] as $t ) {
                if ( isset( $t['type'] ) ) {
                    $transforms[] = $this->build_transform_item_from_object( $t );
                }
            }
            if ( ! empty( $transforms ) ) {
                $props['transforms_hover'] = array(
                    $breakpoint => $transforms,
                );
            }
        }

        return $props;
    }

    /**
     * Extract individual transform items from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array $node     The element node.
     * @param bool  $is_hover Whether to extract hover variants.
     * @return array Array of transform items.
     */
    private function extract_transform_items( array $node, bool $is_hover ): array {
        $transforms = array();
        $suffix     = $is_hover ? 'Hover' : '';

        // Check for rotate transforms.
        $rotate_item = $this->build_rotate_transform( $node, $suffix );
        if ( ! empty( $rotate_item ) ) {
            $transforms[] = $rotate_item;
        }

        // Check for rotate3d transforms.
        $rotate3d_item = $this->build_rotate3d_transform( $node, $suffix );
        if ( ! empty( $rotate3d_item ) ) {
            $transforms[] = $rotate3d_item;
        }

        // Check for scale transforms.
        $scale_item = $this->build_scale_transform( $node, $suffix );
        if ( ! empty( $scale_item ) ) {
            $transforms[] = $scale_item;
        }

        // Check for scale3d transforms.
        $scale3d_item = $this->build_scale3d_transform( $node, $suffix );
        if ( ! empty( $scale3d_item ) ) {
            $transforms[] = $scale3d_item;
        }

        // Check for skew transforms.
        $skew_item = $this->build_skew_transform( $node, $suffix );
        if ( ! empty( $skew_item ) ) {
            $transforms[] = $skew_item;
        }

        // Check for translate transforms.
        $translate_item = $this->build_translate_transform( $node, $suffix );
        if ( ! empty( $translate_item ) ) {
            $transforms[] = $translate_item;
        }

        // Check for perspective transform (in transforms array).
        $perspective_item = $this->build_perspective_transform( $node, $suffix );
        if ( ! empty( $perspective_item ) ) {
            $transforms[] = $perspective_item;
        }

        return $transforms;
    }

    /**
     * Build rotate transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The rotate transform item or empty array.
     */
    private function build_rotate_transform( array $node, string $suffix ): array {
        $has_rotate = isset( $node[ 'rotateX' . $suffix ] ) ||
                      isset( $node[ 'rotateY' . $suffix ] ) ||
                      isset( $node[ 'rotateZ' . $suffix ] ) ||
                      isset( $node[ 'rotate' . $suffix ] );

        if ( ! $has_rotate ) {
            return array();
        }

        $item = array( 'type' => 'rotate' );

        // rotateX.
        if ( isset( $node[ 'rotateX' . $suffix ] ) ) {
            $item['rotate_x'] = $this->parse_angle_value( $node[ 'rotateX' . $suffix ] );
        }

        // rotateY.
        if ( isset( $node[ 'rotateY' . $suffix ] ) ) {
            $item['rotate_y'] = $this->parse_angle_value( $node[ 'rotateY' . $suffix ] );
        }

        // rotateZ (or just 'rotate' as shorthand).
        if ( isset( $node[ 'rotateZ' . $suffix ] ) ) {
            $item['rotate_z'] = $this->parse_angle_value( $node[ 'rotateZ' . $suffix ] );
        } elseif ( isset( $node[ 'rotate' . $suffix ] ) && ! is_array( $node[ 'rotate' . $suffix ] ) ) {
            // 'rotate' without axis defaults to Z rotation.
            $item['rotate_z'] = $this->parse_angle_value( $node[ 'rotate' . $suffix ] );
        }

        return $item;
    }

    /**
     * Build rotate3d transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The rotate3d transform item or empty array.
     */
    private function build_rotate3d_transform( array $node, string $suffix ): array {
        $prop = 'rotate3d' . $suffix;

        if ( ! isset( $node[ $prop ] ) ) {
            return array();
        }

        $r3d = $node[ $prop ];

        if ( ! is_array( $r3d ) ) {
            return array();
        }

        $item = array(
            'type'  => 'rotate3d',
            'x'     => isset( $r3d['x'] ) ? floatval( $r3d['x'] ) : 0,
            'y'     => isset( $r3d['y'] ) ? floatval( $r3d['y'] ) : 0,
            'z'     => isset( $r3d['z'] ) ? floatval( $r3d['z'] ) : 1,
            'angle' => isset( $r3d['angle'] ) ? $this->parse_angle_value( $r3d['angle'] ) : $this->parse_angle_value( '0deg' ),
        );

        return $item;
    }

    /**
     * Build scale transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The scale transform item or empty array.
     */
    private function build_scale_transform( array $node, string $suffix ): array {
        $prop = 'scale' . $suffix;

        // Only use the simple 'scale' property (not scaleX, scaleY, scaleZ).
        if ( ! isset( $node[ $prop ] ) ) {
            return array();
        }

        // If it's an array with x/y/z, it's for scale3d.
        if ( is_array( $node[ $prop ] ) ) {
            return array();
        }

        return array(
            'type'  => 'scale',
            'scale' => floatval( $node[ $prop ] ),
        );
    }

    /**
     * Build scale3d transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The scale3d transform item or empty array.
     */
    private function build_scale3d_transform( array $node, string $suffix ): array {
        $has_scale3d = isset( $node[ 'scaleX' . $suffix ] ) ||
                       isset( $node[ 'scaleY' . $suffix ] ) ||
                       isset( $node[ 'scaleZ' . $suffix ] ) ||
                       ( isset( $node[ 'scale3d' . $suffix ] ) && is_array( $node[ 'scale3d' . $suffix ] ) );

        if ( ! $has_scale3d ) {
            return array();
        }

        $item = array( 'type' => 'scale3d' );

        // Check for scale3d array format first.
        if ( isset( $node[ 'scale3d' . $suffix ] ) && is_array( $node[ 'scale3d' . $suffix ] ) ) {
            $s3d             = $node[ 'scale3d' . $suffix ];
            $item['scale_x'] = isset( $s3d['x'] ) ? floatval( $s3d['x'] ) : 1;
            $item['scale_y'] = isset( $s3d['y'] ) ? floatval( $s3d['y'] ) : 1;
            $item['scale_z'] = isset( $s3d['z'] ) ? floatval( $s3d['z'] ) : 1;
            return $item;
        }

        // Individual properties.
        if ( isset( $node[ 'scaleX' . $suffix ] ) ) {
            $item['scale_x'] = floatval( $node[ 'scaleX' . $suffix ] );
        }

        if ( isset( $node[ 'scaleY' . $suffix ] ) ) {
            $item['scale_y'] = floatval( $node[ 'scaleY' . $suffix ] );
        }

        if ( isset( $node[ 'scaleZ' . $suffix ] ) ) {
            $item['scale_z'] = floatval( $node[ 'scaleZ' . $suffix ] );
        }

        return $item;
    }

    /**
     * Build skew transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The skew transform item or empty array.
     */
    private function build_skew_transform( array $node, string $suffix ): array {
        $has_skew = isset( $node[ 'skewX' . $suffix ] ) ||
                    isset( $node[ 'skewY' . $suffix ] ) ||
                    isset( $node[ 'skew' . $suffix ] );

        if ( ! $has_skew ) {
            return array();
        }

        $item = array( 'type' => 'skew' );

        // skewX.
        if ( isset( $node[ 'skewX' . $suffix ] ) ) {
            $item['skew_x'] = $this->parse_angle_value( $node[ 'skewX' . $suffix ] );
        }

        // skewY.
        if ( isset( $node[ 'skewY' . $suffix ] ) ) {
            $item['skew_y'] = $this->parse_angle_value( $node[ 'skewY' . $suffix ] );
        }

        // Shorthand 'skew' with x,y values.
        if ( isset( $node[ 'skew' . $suffix ] ) && is_array( $node[ 'skew' . $suffix ] ) ) {
            $skew = $node[ 'skew' . $suffix ];
            if ( isset( $skew['x'] ) && ! isset( $item['skew_x'] ) ) {
                $item['skew_x'] = $this->parse_angle_value( $skew['x'] );
            }
            if ( isset( $skew['y'] ) && ! isset( $item['skew_y'] ) ) {
                $item['skew_y'] = $this->parse_angle_value( $skew['y'] );
            }
        }

        return $item;
    }

    /**
     * Build translate transform item from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The translate transform item or empty array.
     */
    private function build_translate_transform( array $node, string $suffix ): array {
        $has_translate = isset( $node[ 'translateX' . $suffix ] ) ||
                         isset( $node[ 'translateY' . $suffix ] ) ||
                         isset( $node[ 'translateZ' . $suffix ] ) ||
                         isset( $node[ 'translate' . $suffix ] );

        if ( ! $has_translate ) {
            return array();
        }

        $item = array( 'type' => 'translate' );

        // translateX.
        if ( isset( $node[ 'translateX' . $suffix ] ) ) {
            $item['translate_x'] = $this->parse_unit_value( $node[ 'translateX' . $suffix ], 'px' );
        }

        // translateY.
        if ( isset( $node[ 'translateY' . $suffix ] ) ) {
            $item['translate_y'] = $this->parse_unit_value( $node[ 'translateY' . $suffix ], 'px' );
        }

        // translateZ.
        if ( isset( $node[ 'translateZ' . $suffix ] ) ) {
            $item['translate_z'] = $this->parse_unit_value( $node[ 'translateZ' . $suffix ], 'px' );
        }

        // Shorthand 'translate' with x,y,z values.
        if ( isset( $node[ 'translate' . $suffix ] ) && is_array( $node[ 'translate' . $suffix ] ) ) {
            $trans = $node[ 'translate' . $suffix ];
            if ( isset( $trans['x'] ) && ! isset( $item['translate_x'] ) ) {
                $item['translate_x'] = $this->parse_unit_value( $trans['x'], 'px' );
            }
            if ( isset( $trans['y'] ) && ! isset( $item['translate_y'] ) ) {
                $item['translate_y'] = $this->parse_unit_value( $trans['y'], 'px' );
            }
            if ( isset( $trans['z'] ) && ! isset( $item['translate_z'] ) ) {
                $item['translate_z'] = $this->parse_unit_value( $trans['z'], 'px' );
            }
        }

        return $item;
    }

    /**
     * Build perspective transform item from simplified properties.
     *
     * This is for the perspective() function within transform, not the
     * parent perspective property.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return array The perspective transform item or empty array.
     */
    private function build_perspective_transform( array $node, string $suffix ): array {
        $prop = 'transformPerspective' . $suffix;

        if ( ! isset( $node[ $prop ] ) ) {
            return array();
        }

        return array(
            'type'        => 'perspective',
            'perspective' => $this->parse_unit_value( $node[ $prop ], 'px' ),
        );
    }

    /**
     * Build a transform item from an object definition.
     *
     * @since 1.0.0
     *
     * @param array $transform_obj The transform object with type and values.
     * @return array The formatted transform item.
     */
    private function build_transform_item_from_object( array $transform_obj ): array {
        $type = $transform_obj['type'];
        $item = array( 'type' => $type );

        switch ( $type ) {
            case 'rotate':
                if ( isset( $transform_obj['rotate_x'] ) ) {
                    $item['rotate_x'] = $this->ensure_unit_object( $transform_obj['rotate_x'], 'deg' );
                }
                if ( isset( $transform_obj['rotate_y'] ) ) {
                    $item['rotate_y'] = $this->ensure_unit_object( $transform_obj['rotate_y'], 'deg' );
                }
                if ( isset( $transform_obj['rotate_z'] ) ) {
                    $item['rotate_z'] = $this->ensure_unit_object( $transform_obj['rotate_z'], 'deg' );
                }
                break;

            case 'rotate3d':
                $item['x']     = isset( $transform_obj['x'] ) ? floatval( $transform_obj['x'] ) : 0;
                $item['y']     = isset( $transform_obj['y'] ) ? floatval( $transform_obj['y'] ) : 0;
                $item['z']     = isset( $transform_obj['z'] ) ? floatval( $transform_obj['z'] ) : 1;
                $item['angle'] = isset( $transform_obj['angle'] )
                    ? $this->ensure_unit_object( $transform_obj['angle'], 'deg' )
                    : $this->parse_angle_value( '0deg' );
                break;

            case 'scale':
                $item['scale'] = isset( $transform_obj['scale'] ) ? floatval( $transform_obj['scale'] ) : 1;
                break;

            case 'scale3d':
                if ( isset( $transform_obj['scale_x'] ) ) {
                    $item['scale_x'] = floatval( $transform_obj['scale_x'] );
                }
                if ( isset( $transform_obj['scale_y'] ) ) {
                    $item['scale_y'] = floatval( $transform_obj['scale_y'] );
                }
                if ( isset( $transform_obj['scale_z'] ) ) {
                    $item['scale_z'] = floatval( $transform_obj['scale_z'] );
                }
                break;

            case 'skew':
                if ( isset( $transform_obj['skew_x'] ) ) {
                    $item['skew_x'] = $this->ensure_unit_object( $transform_obj['skew_x'], 'deg' );
                }
                if ( isset( $transform_obj['skew_y'] ) ) {
                    $item['skew_y'] = $this->ensure_unit_object( $transform_obj['skew_y'], 'deg' );
                }
                break;

            case 'translate':
                if ( isset( $transform_obj['translate_x'] ) ) {
                    $item['translate_x'] = $this->ensure_unit_object( $transform_obj['translate_x'], 'px' );
                }
                if ( isset( $transform_obj['translate_y'] ) ) {
                    $item['translate_y'] = $this->ensure_unit_object( $transform_obj['translate_y'], 'px' );
                }
                if ( isset( $transform_obj['translate_z'] ) ) {
                    $item['translate_z'] = $this->ensure_unit_object( $transform_obj['translate_z'], 'px' );
                }
                break;

            case 'perspective':
                if ( isset( $transform_obj['perspective'] ) ) {
                    $item['perspective'] = $this->ensure_unit_object( $transform_obj['perspective'], 'px' );
                }
                break;
        }

        return $item;
    }

    /**
     * Extract transform origin properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The transform origin properties.
     */
    private function extract_transform_origin_props( array $node, string $breakpoint ): array {
        $props = array();

        // Valid origin keywords.
        $valid_origins = array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        );

        // Normal origin.
        if ( isset( $node['transformOrigin'] ) ) {
            $origin = $node['transformOrigin'];

            if ( is_string( $origin ) && in_array( $origin, $valid_origins, true ) ) {
                $props['origin'] = array(
                    $breakpoint => $origin,
                );
            } elseif ( is_array( $origin ) && isset( $origin['x'] ) && isset( $origin['y'] ) ) {
                $props['origin'] = array(
                    $breakpoint => 'custom',
                );
                $props['origin_position'] = array(
                    $breakpoint => array(
                        'x' => floatval( $origin['x'] ),
                        'y' => floatval( $origin['y'] ),
                    ),
                );
            }
        }

        // Hover origin.
        if ( isset( $node['transformOriginHover'] ) ) {
            $origin = $node['transformOriginHover'];

            if ( is_string( $origin ) && in_array( $origin, $valid_origins, true ) ) {
                $props['origin_hover'] = array(
                    $breakpoint => $origin,
                );
            } elseif ( is_array( $origin ) && isset( $origin['x'] ) && isset( $origin['y'] ) ) {
                $props['origin_hover'] = array(
                    $breakpoint => 'custom',
                );
                $props['origin_position_hover'] = array(
                    $breakpoint => array(
                        'x' => floatval( $origin['x'] ),
                        'y' => floatval( $origin['y'] ),
                    ),
                );
            }
        }

        return $props;
    }

    /**
     * Extract perspective properties from node.
     *
     * This is for the parent perspective and perspective-origin CSS properties,
     * not the perspective() transform function.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The perspective properties.
     */
    private function extract_perspective_props( array $node, string $breakpoint ): array {
        $props = array();

        // Normal perspective.
        if ( isset( $node['perspective'] ) ) {
            $props['perspective'] = array(
                $breakpoint => $this->parse_unit_value( $node['perspective'], 'px' ),
            );
        }

        // Hover perspective.
        if ( isset( $node['perspectiveHover'] ) ) {
            $props['perspective_hover'] = array(
                $breakpoint => $this->parse_unit_value( $node['perspectiveHover'], 'px' ),
            );
        }

        // Perspective origin.
        if ( isset( $node['perspectiveOrigin'] ) && is_array( $node['perspectiveOrigin'] ) ) {
            $origin = $node['perspectiveOrigin'];
            if ( isset( $origin['x'] ) && isset( $origin['y'] ) ) {
                $props['perspective_origin'] = array(
                    $breakpoint => array(
                        'x' => floatval( $origin['x'] ),
                        'y' => floatval( $origin['y'] ),
                    ),
                );
            }
        }

        // Hover perspective origin.
        if ( isset( $node['perspectiveOriginHover'] ) && is_array( $node['perspectiveOriginHover'] ) ) {
            $origin = $node['perspectiveOriginHover'];
            if ( isset( $origin['x'] ) && isset( $origin['y'] ) ) {
                $props['perspective_origin_hover'] = array(
                    $breakpoint => array(
                        'x' => floatval( $origin['x'] ),
                        'y' => floatval( $origin['y'] ),
                    ),
                );
            }
        }

        return $props;
    }

    /**
     * Extract transform style properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The transform style properties.
     */
    private function extract_transform_style_props( array $node, string $breakpoint ): array {
        $props = array();

        $valid_styles = array( 'flat', 'preserve-3d' );

        // Normal transform style.
        if ( isset( $node['transformStyle'] ) && in_array( $node['transformStyle'], $valid_styles, true ) ) {
            $props['transform_style'] = array(
                $breakpoint => $node['transformStyle'],
            );
        }

        // Hover transform style.
        if ( isset( $node['transformStyleHover'] ) && in_array( $node['transformStyleHover'], $valid_styles, true ) ) {
            $props['transform_style_hover'] = array(
                $breakpoint => $node['transformStyleHover'],
            );
        }

        return $props;
    }

    /**
     * Parse an angle value into a unit object.
     *
     * @since 1.0.0
     *
     * @param mixed $value The angle value (e.g., '45deg', 45, '0.5turn').
     * @return array The unit object with number, unit, and style.
     */
    private function parse_angle_value( $value ): array {
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $value;
        }

        $parsed = $this->schema->parse_css_value( (string) $value );

        // Default to degrees if no unit specified.
        if ( empty( $parsed['unit'] ) && is_numeric( $parsed['number'] ) ) {
            $parsed['unit']  = 'deg';
            $parsed['style'] = $parsed['number'] . 'deg';
        }

        return $parsed;
    }

    /**
     * Parse a unit value into a unit object.
     *
     * @since 1.0.0
     *
     * @param mixed  $value        The value (e.g., '10px', 10, '50%').
     * @param string $default_unit The default unit if none specified.
     * @return array The unit object with number, unit, and style.
     */
    private function parse_unit_value( $value, string $default_unit = 'px' ): array {
        if ( is_array( $value ) && isset( $value['style'] ) ) {
            return $value;
        }

        $parsed = $this->schema->parse_css_value( (string) $value );

        // Apply default unit if none specified.
        if ( empty( $parsed['unit'] ) && is_numeric( $parsed['number'] ) ) {
            $parsed['unit']  = $default_unit;
            $parsed['style'] = $parsed['number'] . $default_unit;
        }

        return $parsed;
    }

    /**
     * Ensure a value is a unit object.
     *
     * @since 1.0.0
     *
     * @param mixed  $value        The value to convert.
     * @param string $default_unit The default unit if none specified.
     * @return array The unit object.
     */
    private function ensure_unit_object( $value, string $default_unit = 'px' ): array {
        if ( is_array( $value ) ) {
            if ( isset( $value['style'] ) ) {
                return $value;
            }
            // Try to build from number/unit.
            if ( isset( $value['number'] ) ) {
                $unit = $value['unit'] ?? $default_unit;
                return array(
                    'number' => $value['number'],
                    'unit'   => $unit,
                    'style'  => $value['number'] . $unit,
                );
            }
        }

        return $this->parse_unit_value( $value, $default_unit );
    }

    /**
     * Extract opacity properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The opacity properties.
     */
    private function extract_opacity_props( array $node, string $breakpoint ): array {
        $props = array();

        // Normal opacity.
        if ( isset( $node['opacity'] ) ) {
            $value = $this->normalize_opacity( $node['opacity'] );
            $props['opacity'] = array(
                $breakpoint => $value,
            );
        }

        // Hover opacity.
        if ( isset( $node['opacityHover'] ) ) {
            $value = $this->normalize_opacity( $node['opacityHover'] );
            $props['opacity_hover'] = array(
                $breakpoint => $value,
            );
        }

        return $props;
    }

    /**
     * Extract box shadow properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The box shadow properties.
     */
    private function extract_box_shadow_props( array $node, string $breakpoint ): array {
        $props = array();

        // Normal box shadow.
        if ( isset( $node['boxShadow'] ) ) {
            $shadow = $this->build_shadow_object( $node['boxShadow'] );
            $props['box_shadow'] = array(
                $breakpoint => $shadow,
            );
        }

        // Hover box shadow.
        if ( isset( $node['boxShadowHover'] ) ) {
            $shadow = $this->build_shadow_object( $node['boxShadowHover'] );
            $props['box_shadow_hover'] = array(
                $breakpoint => $shadow,
            );
        }

        return $props;
    }

    /**
     * Extract mix blend mode properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The mix blend mode properties.
     */
    private function extract_mix_blend_mode_props( array $node, string $breakpoint ): array {
        $props = array();

        // Normal blend mode.
        if ( isset( $node['mixBlendMode'] ) ) {
            $props['mix_blend_mode'] = array(
                $breakpoint => $node['mixBlendMode'],
            );
        }

        // Hover blend mode.
        if ( isset( $node['mixBlendModeHover'] ) ) {
            $props['mix_blend_mode_hover'] = array(
                $breakpoint => $node['mixBlendModeHover'],
            );
        }

        return $props;
    }

    /**
     * Extract transition properties from node.
     *
     * Builds the transition repeater array from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The transition properties.
     */
    private function extract_transition_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check for any transition properties.
        $has_transition = isset( $node['transitionDuration'] ) ||
                          isset( $node['transitionTiming'] ) ||
                          isset( $node['transitionProperty'] ) ||
                          isset( $node['transitionDelay'] ) ||
                          isset( $node['transition'] );

        if ( ! $has_transition ) {
            return $props;
        }

        // If transition is provided as an array of transition objects.
        if ( isset( $node['transition'] ) && is_array( $node['transition'] ) ) {
            $transitions = array();
            foreach ( $node['transition'] as $trans ) {
                $transitions[] = $this->build_transition_item( $trans );
            }
            $props['transition'] = array(
                $breakpoint => $transitions,
            );
            return $props;
        }

        // Build single transition from individual properties.
        $transition_item = array();

        // Duration.
        if ( isset( $node['transitionDuration'] ) ) {
            $transition_item['duration'] = $this->schema->parse_css_value(
                (string) $node['transitionDuration']
            );
            // Ensure unit defaults to ms.
            if ( empty( $transition_item['duration']['unit'] ) ) {
                $transition_item['duration']['unit'] = 'ms';
                $transition_item['duration']['style'] = $transition_item['duration']['number'] . 'ms';
            }
        }

        // Timing function.
        if ( isset( $node['transitionTiming'] ) ) {
            $transition_item['timing_function'] = $node['transitionTiming'];
        } else {
            $transition_item['timing_function'] = 'ease-in-out';
        }

        // Property.
        if ( isset( $node['transitionProperty'] ) ) {
            $transition_item['property'] = $node['transitionProperty'];
        } else {
            $transition_item['property'] = 'all';
        }

        // Custom property (when property is 'custom').
        if ( isset( $node['transitionCustomProperty'] ) ) {
            $transition_item['custom_property'] = $node['transitionCustomProperty'];
        }

        // Delay.
        if ( isset( $node['transitionDelay'] ) ) {
            $transition_item['delay'] = $this->schema->parse_css_value(
                (string) $node['transitionDelay']
            );
            // Ensure unit defaults to ms.
            if ( empty( $transition_item['delay']['unit'] ) ) {
                $transition_item['delay']['unit'] = 'ms';
                $transition_item['delay']['style'] = $transition_item['delay']['number'] . 'ms';
            }
        }

        if ( ! empty( $transition_item ) ) {
            $props['transition'] = array(
                $breakpoint => array( $transition_item ),
            );
        }

        return $props;
    }

    /**
     * Extract filter properties from node.
     *
     * Builds the filter repeater array from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The filter properties.
     */
    private function extract_filter_props( array $node, string $breakpoint ): array {
        $props = array();

        // Build normal state filters.
        $filters = $this->extract_filter_items( $node, false );
        if ( ! empty( $filters ) ) {
            $props['filter'] = array(
                $breakpoint => $filters,
            );
        }

        // Build hover state filters.
        $filters_hover = $this->extract_filter_items( $node, true );
        if ( ! empty( $filters_hover ) ) {
            $props['filter_hover'] = array(
                $breakpoint => $filters_hover,
            );
        }

        // Handle complete filter array if provided directly.
        if ( isset( $node['filter'] ) && is_array( $node['filter'] ) ) {
            $filters = array();
            foreach ( $node['filter'] as $f ) {
                if ( isset( $f['type'] ) ) {
                    $filters[] = $this->build_filter_item_from_object( $f );
                }
            }
            if ( ! empty( $filters ) ) {
                $props['filter'] = array(
                    $breakpoint => $filters,
                );
            }
        }

        if ( isset( $node['filterHover'] ) && is_array( $node['filterHover'] ) ) {
            $filters = array();
            foreach ( $node['filterHover'] as $f ) {
                if ( isset( $f['type'] ) ) {
                    $filters[] = $this->build_filter_item_from_object( $f );
                }
            }
            if ( ! empty( $filters ) ) {
                $props['filter_hover'] = array(
                    $breakpoint => $filters,
                );
            }
        }

        return $props;
    }

    /**
     * Extract individual filter items from simplified properties.
     *
     * @since 1.0.0
     *
     * @param array $node     The element node.
     * @param bool  $is_hover Whether to extract hover variants.
     * @return array Array of filter items.
     */
    private function extract_filter_items( array $node, bool $is_hover ): array {
        $filters = array();
        $suffix  = $is_hover ? 'Hover' : '';

        // Filter type mappings: property name => (type, value property).
        $filter_types = array(
            'filterBlur'       => array( 'type' => 'blur', 'value_prop' => 'blur_amount', 'default_unit' => 'px' ),
            'filterBrightness' => array( 'type' => 'brightness', 'value_prop' => 'amount', 'default_unit' => '%' ),
            'filterContrast'   => array( 'type' => 'contrast', 'value_prop' => 'amount', 'default_unit' => '%' ),
            'filterGrayscale'  => array( 'type' => 'grayscale', 'value_prop' => 'amount', 'default_unit' => '%' ),
            'filterHueRotate'  => array( 'type' => 'hue-rotate', 'value_prop' => 'rotate', 'default_unit' => 'deg' ),
            'filterInvert'     => array( 'type' => 'invert', 'value_prop' => 'amount', 'default_unit' => '%' ),
            'filterSaturate'   => array( 'type' => 'saturate', 'value_prop' => 'amount', 'default_unit' => '%' ),
            'filterSepia'      => array( 'type' => 'sepia', 'value_prop' => 'amount', 'default_unit' => '%' ),
        );

        foreach ( $filter_types as $prop_name => $filter_config ) {
            $full_prop = $prop_name . $suffix;

            if ( isset( $node[ $full_prop ] ) ) {
                $value = $this->schema->parse_css_value( (string) $node[ $full_prop ] );

                // Ensure default unit.
                if ( empty( $value['unit'] ) && is_numeric( $value['number'] ) ) {
                    $value['unit']  = $filter_config['default_unit'];
                    $value['style'] = $value['number'] . $filter_config['default_unit'];
                }

                $filter_item = array(
                    'type' => $filter_config['type'],
                );
                $filter_item[ $filter_config['value_prop'] ] = $value;

                $filters[] = $filter_item;
            }

            // Also check for explicit amount properties (e.g., filterBlurAmount).
            $amount_prop = $prop_name . 'Amount' . $suffix;
            if ( isset( $node[ $amount_prop ] ) && ! isset( $node[ $full_prop ] ) ) {
                $value = $this->schema->parse_css_value( (string) $node[ $amount_prop ] );

                if ( empty( $value['unit'] ) && is_numeric( $value['number'] ) ) {
                    $value['unit']  = $filter_config['default_unit'];
                    $value['style'] = $value['number'] . $filter_config['default_unit'];
                }

                $filter_item = array(
                    'type' => $filter_config['type'],
                );
                $filter_item[ $filter_config['value_prop'] ] = $value;

                $filters[] = $filter_item;
            }
        }

        return $filters;
    }

    /**
     * Build a filter item from an object definition.
     *
     * @since 1.0.0
     *
     * @param array $filter_obj The filter object with type and value.
     * @return array The formatted filter item.
     */
    private function build_filter_item_from_object( array $filter_obj ): array {
        $type = $filter_obj['type'];

        // Determine value property based on type.
        $value_configs = array(
            'blur'       => array( 'value_prop' => 'blur_amount', 'source_prop' => 'value', 'default_unit' => 'px' ),
            'brightness' => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
            'contrast'   => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
            'grayscale'  => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
            'hue-rotate' => array( 'value_prop' => 'rotate', 'source_prop' => 'value', 'default_unit' => 'deg' ),
            'invert'     => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
            'saturate'   => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
            'sepia'      => array( 'value_prop' => 'amount', 'source_prop' => 'value', 'default_unit' => '%' ),
        );

        $item = array( 'type' => $type );

        if ( isset( $value_configs[ $type ] ) ) {
            $config       = $value_configs[ $type ];
            $source_value = $filter_obj[ $config['source_prop'] ] ?? $filter_obj[ $config['value_prop'] ] ?? null;

            if ( $source_value !== null ) {
                $value = is_array( $source_value )
                    ? $source_value
                    : $this->schema->parse_css_value( (string) $source_value );

                if ( empty( $value['unit'] ) && is_numeric( $value['number'] ) ) {
                    $value['unit']  = $config['default_unit'];
                    $value['style'] = $value['number'] . $config['default_unit'];
                }

                $item[ $config['value_prop'] ] = $value;
            }
        }

        return $item;
    }

    /**
     * Build a transition item from an array definition.
     *
     * @since 1.0.0
     *
     * @param array $trans The transition definition.
     * @return array The formatted transition item.
     */
    private function build_transition_item( array $trans ): array {
        $item = array();

        // Duration.
        if ( isset( $trans['duration'] ) ) {
            $item['duration'] = is_array( $trans['duration'] )
                ? $trans['duration']
                : $this->schema->parse_css_value( (string) $trans['duration'] );

            if ( empty( $item['duration']['unit'] ) ) {
                $item['duration']['unit']  = 'ms';
                $item['duration']['style'] = $item['duration']['number'] . 'ms';
            }
        }

        // Timing function.
        $item['timing_function'] = $trans['timing_function'] ?? $trans['timing'] ?? 'ease-in-out';

        // Property.
        $item['property'] = $trans['property'] ?? 'all';

        // Custom property.
        if ( isset( $trans['custom_property'] ) ) {
            $item['custom_property'] = $trans['custom_property'];
        }

        // Delay.
        if ( isset( $trans['delay'] ) ) {
            $item['delay'] = is_array( $trans['delay'] )
                ? $trans['delay']
                : $this->schema->parse_css_value( (string) $trans['delay'] );

            if ( empty( $item['delay']['unit'] ) ) {
                $item['delay']['unit']  = 'ms';
                $item['delay']['style'] = $item['delay']['number'] . 'ms';
            }
        }

        return $item;
    }

    /**
     * Build a shadow object from CSS value.
     *
     * @since 1.0.0
     *
     * @param mixed $value The shadow value (CSS string or array).
     * @return array The shadow object with style property.
     */
    private function build_shadow_object( $value ): array {
        if ( is_array( $value ) ) {
            // Already formatted or needs conversion.
            if ( isset( $value['style'] ) ) {
                return $value;
            }
            // Build from individual shadow properties.
            return $this->build_shadow_from_parts( $value );
        }

        // CSS string value.
        return array(
            'style' => (string) $value,
        );
    }

    /**
     * Build shadow CSS from individual parts.
     *
     * @since 1.0.0
     *
     * @param array $parts Shadow parts (x, y, blur, spread, color, inset).
     * @return array The shadow object.
     */
    private function build_shadow_from_parts( array $parts ): array {
        $shadow_parts = array();

        // Inset keyword (if present).
        if ( ! empty( $parts['inset'] ) ) {
            $shadow_parts[] = 'inset';
        }

        // X offset.
        $shadow_parts[] = $parts['x'] ?? '0px';

        // Y offset.
        $shadow_parts[] = $parts['y'] ?? '0px';

        // Blur radius.
        if ( isset( $parts['blur'] ) ) {
            $shadow_parts[] = $parts['blur'];
        }

        // Spread radius.
        if ( isset( $parts['spread'] ) ) {
            $shadow_parts[] = $parts['spread'];
        }

        // Color.
        if ( isset( $parts['color'] ) ) {
            $shadow_parts[] = $parts['color'];
        }

        return array(
            'style' => implode( ' ', $shadow_parts ),
        );
    }

    /**
     * Build background properties from simplified input.
     *
     * Transforms flat background properties (color, image, gradient, blend mode, overlay)
     * into the nested structure expected by Breakdance.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The nested background properties array.
     */
    public function build_background_properties( array $node, string $breakpoint = 'breakpoint_base' ): array {
        $background = array();

        // Build basic background properties (color, image).
        $background = $this->merge_deep(
            $background,
            $this->extract_basic_background_props( $node, $breakpoint )
        );

        // Build gradient properties.
        $background = $this->merge_deep(
            $background,
            $this->extract_gradient_props( $node, $breakpoint )
        );

        // Build blend mode properties.
        $background = $this->merge_deep(
            $background,
            $this->extract_background_blend_mode_props( $node, $breakpoint )
        );

        // Build overlay properties.
        $background = $this->merge_deep(
            $background,
            $this->extract_overlay_props( $node, $breakpoint )
        );

        // Build background layers (for fancy backgrounds).
        $background = $this->merge_deep(
            $background,
            $this->extract_background_layer_props( $node, $breakpoint )
        );

        // Build background transition properties.
        $background = $this->merge_deep(
            $background,
            $this->extract_background_transition_props( $node, $breakpoint )
        );

        return $background;
    }

    /**
     * Extract basic background properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The basic background properties.
     */
    private function extract_basic_background_props( array $node, string $breakpoint ): array {
        $props = array();

        // Background type.
        if ( isset( $node['backgroundType'] ) ) {
            $props['type'] = array(
                $breakpoint => $node['backgroundType'],
            );
        }

        // Background color.
        if ( isset( $node['backgroundColor'] ) ) {
            $props['color'] = array(
                $breakpoint => $node['backgroundColor'],
            );
        }

        // Background image.
        if ( isset( $node['backgroundImage'] ) ) {
            $props['image'] = array(
                $breakpoint => $node['backgroundImage'],
            );
        }

        // Background position.
        if ( isset( $node['backgroundPosition'] ) ) {
            $props['position'] = array(
                $breakpoint => $node['backgroundPosition'],
            );
        }

        // Background size.
        if ( isset( $node['backgroundSize'] ) ) {
            $props['size'] = array(
                $breakpoint => $node['backgroundSize'],
            );
        }

        // Background repeat.
        if ( isset( $node['backgroundRepeat'] ) ) {
            $props['repeat'] = array(
                $breakpoint => $node['backgroundRepeat'],
            );
        }

        // Background attachment.
        if ( isset( $node['backgroundAttachment'] ) ) {
            $props['attachment'] = array(
                $breakpoint => $node['backgroundAttachment'],
            );
        }

        // Hover variants.
        if ( isset( $node['backgroundTypeHover'] ) ) {
            $props['type_hover'] = array(
                $breakpoint => $node['backgroundTypeHover'],
            );
        }

        if ( isset( $node['backgroundColorHover'] ) ) {
            $props['color_hover'] = array(
                $breakpoint => $node['backgroundColorHover'],
            );
        }

        if ( isset( $node['backgroundImageHover'] ) ) {
            $props['image_hover'] = array(
                $breakpoint => $node['backgroundImageHover'],
            );
        }

        if ( isset( $node['backgroundPositionHover'] ) ) {
            $props['position_hover'] = array(
                $breakpoint => $node['backgroundPositionHover'],
            );
        }

        if ( isset( $node['backgroundSizeHover'] ) ) {
            $props['size_hover'] = array(
                $breakpoint => $node['backgroundSizeHover'],
            );
        }

        if ( isset( $node['backgroundRepeatHover'] ) ) {
            $props['repeat_hover'] = array(
                $breakpoint => $node['backgroundRepeatHover'],
            );
        }

        if ( isset( $node['backgroundAttachmentHover'] ) ) {
            $props['attachment_hover'] = array(
                $breakpoint => $node['backgroundAttachmentHover'],
            );
        }

        return $props;
    }

    /**
     * Extract gradient properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The gradient properties.
     */
    private function extract_gradient_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check if we have any gradient properties.
        $has_gradient = isset( $node['gradient'] ) ||
                        isset( $node['gradientStyle'] ) ||
                        isset( $node['gradientType'] ) ||
                        isset( $node['gradientColors'] );

        if ( ! $has_gradient ) {
            // Check for hover variants too.
            $has_gradient = isset( $node['gradientHover'] ) ||
                            isset( $node['gradientStyleHover'] ) ||
                            isset( $node['gradientTypeHover'] ) ||
                            isset( $node['gradientColorsHover'] );
        }

        if ( ! $has_gradient ) {
            return $props;
        }

        // Build normal state gradient.
        $gradient = $this->build_gradient_object( $node, false );
        if ( ! empty( $gradient ) ) {
            $props['gradient'] = array(
                $breakpoint => $gradient,
            );
        }

        // Build hover state gradient.
        $gradient_hover = $this->build_gradient_object( $node, true );
        if ( ! empty( $gradient_hover ) ) {
            $props['gradient_hover'] = array(
                $breakpoint => $gradient_hover,
            );
        }

        // Gradient animation properties.
        if ( isset( $node['gradientAnimation'] ) ) {
            $props['gradient']['animation'] = array(
                $breakpoint => $node['gradientAnimation'],
            );
        }

        if ( isset( $node['gradientAnimationDuration'] ) ) {
            $props['gradient']['animation_duration'] = array(
                $breakpoint => $this->parse_unit_value( $node['gradientAnimationDuration'], 's' ),
            );
        }

        return $props;
    }

    /**
     * Build a gradient object from node properties.
     *
     * @since 1.0.0
     *
     * @param array $node     The element node.
     * @param bool  $is_hover Whether to build hover variant.
     * @return array The gradient object.
     */
    private function build_gradient_object( array $node, bool $is_hover ): array {
        $suffix   = $is_hover ? 'Hover' : '';
        $gradient = array();

        // If gradient is provided as a complete CSS string.
        if ( isset( $node[ 'gradient' . $suffix ] ) && is_string( $node[ 'gradient' . $suffix ] ) ) {
            return array(
                'style' => $node[ 'gradient' . $suffix ],
                'value' => $node[ 'gradient' . $suffix ],
            );
        }

        // If gradient is provided as an object.
        if ( isset( $node[ 'gradient' . $suffix ] ) && is_array( $node[ 'gradient' . $suffix ] ) ) {
            return $this->normalize_gradient_object( $node[ 'gradient' . $suffix ] );
        }

        // Build from individual properties.
        if ( isset( $node[ 'gradientStyle' . $suffix ] ) ) {
            $gradient['style'] = $node[ 'gradientStyle' . $suffix ];
            $gradient['value'] = $node[ 'gradientStyle' . $suffix ];
            return $gradient;
        }

        if ( isset( $node[ 'gradientValue' . $suffix ] ) ) {
            $gradient['style'] = $node[ 'gradientValue' . $suffix ];
            $gradient['value'] = $node[ 'gradientValue' . $suffix ];
            return $gradient;
        }

        // Build gradient from type, angle, colors, stops.
        if ( isset( $node[ 'gradientType' . $suffix ] ) || isset( $node[ 'gradientColors' . $suffix ] ) ) {
            $gradient_css = $this->build_gradient_css( $node, $suffix );
            if ( ! empty( $gradient_css ) ) {
                $gradient['style'] = $gradient_css;
                $gradient['value'] = $gradient_css;
            }
        }

        return $gradient;
    }

    /**
     * Normalize a gradient object to ensure it has required properties.
     *
     * @since 1.0.0
     *
     * @param array $gradient The input gradient object.
     * @return array The normalized gradient object.
     */
    private function normalize_gradient_object( array $gradient ): array {
        // If already has style/value, return as-is.
        if ( isset( $gradient['style'] ) || isset( $gradient['value'] ) ) {
            return array(
                'style' => $gradient['style'] ?? $gradient['value'] ?? '',
                'value' => $gradient['value'] ?? $gradient['style'] ?? '',
            );
        }

        // Build CSS from object properties.
        $css = $this->build_gradient_css_from_object( $gradient );

        return array(
            'style' => $css,
            'value' => $css,
        );
    }

    /**
     * Build CSS gradient string from node properties.
     *
     * @since 1.0.0
     *
     * @param array  $node   The element node.
     * @param string $suffix The property suffix ('' or 'Hover').
     * @return string The CSS gradient string.
     */
    private function build_gradient_css( array $node, string $suffix ): string {
        $type   = $node[ 'gradientType' . $suffix ] ?? 'linear';
        $colors = $node[ 'gradientColors' . $suffix ] ?? array();
        $stops  = $node[ 'gradientStops' . $suffix ] ?? array();

        if ( empty( $colors ) ) {
            return '';
        }

        // Build color stops string.
        $color_stops = $this->build_gradient_color_stops( $colors, $stops );

        if ( $type === 'radial' ) {
            $position = $node[ 'gradientRadialPosition' . $suffix ] ?? 'center';
            $shape    = $node[ 'gradientRadialShape' . $suffix ] ?? 'ellipse';
            $size     = $node[ 'gradientRadialSize' . $suffix ] ?? 'farthest-corner';

            return "radial-gradient({$shape} {$size} at {$position}, {$color_stops})";
        }

        if ( $type === 'conic' ) {
            $angle    = $node[ 'gradientAngle' . $suffix ] ?? '0deg';
            $position = $node[ 'gradientRadialPosition' . $suffix ] ?? 'center';

            if ( is_numeric( $angle ) ) {
                $angle = $angle . 'deg';
            }

            return "conic-gradient(from {$angle} at {$position}, {$color_stops})";
        }

        // Linear gradient (default).
        $angle = $node[ 'gradientAngle' . $suffix ] ?? '180deg';

        if ( is_numeric( $angle ) ) {
            $angle = $angle . 'deg';
        }

        return "linear-gradient({$angle}, {$color_stops})";
    }

    /**
     * Build CSS gradient string from gradient object.
     *
     * @since 1.0.0
     *
     * @param array $gradient The gradient object with type, colors, stops, etc.
     * @return string The CSS gradient string.
     */
    private function build_gradient_css_from_object( array $gradient ): string {
        $type   = $gradient['type'] ?? 'linear';
        $colors = $gradient['colors'] ?? array();
        $stops  = $gradient['stops'] ?? array();

        if ( empty( $colors ) ) {
            return '';
        }

        // Build color stops string.
        $color_stops = $this->build_gradient_color_stops( $colors, $stops );

        if ( $type === 'radial' ) {
            $position = $gradient['position'] ?? 'center';
            $shape    = $gradient['shape'] ?? 'ellipse';
            $size     = $gradient['size'] ?? 'farthest-corner';

            return "radial-gradient({$shape} {$size} at {$position}, {$color_stops})";
        }

        if ( $type === 'conic' ) {
            $angle    = $gradient['angle'] ?? '0deg';
            $position = $gradient['position'] ?? 'center';

            if ( is_numeric( $angle ) ) {
                $angle = $angle . 'deg';
            }

            return "conic-gradient(from {$angle} at {$position}, {$color_stops})";
        }

        // Linear gradient (default).
        $angle = $gradient['angle'] ?? '180deg';

        if ( is_numeric( $angle ) ) {
            $angle = $angle . 'deg';
        }

        return "linear-gradient({$angle}, {$color_stops})";
    }

    /**
     * Build gradient color stops string.
     *
     * @since 1.0.0
     *
     * @param array $colors Array of colors.
     * @param array $stops  Array of stop positions (optional).
     * @return string The color stops string for CSS gradient.
     */
    private function build_gradient_color_stops( array $colors, array $stops = array() ): string {
        $parts = array();

        foreach ( $colors as $index => $color ) {
            $part = $color;

            // Add stop position if available.
            if ( isset( $stops[ $index ] ) ) {
                $stop = $stops[ $index ];
                if ( is_numeric( $stop ) ) {
                    $stop = $stop . '%';
                }
                $part .= ' ' . $stop;
            }

            $parts[] = $part;
        }

        return implode( ', ', $parts );
    }

    /**
     * Extract background blend mode properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The blend mode properties.
     */
    private function extract_background_blend_mode_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check for backgroundBlendMode or shorthand blendMode.
        $blend_mode = $node['backgroundBlendMode'] ?? $node['blendMode'] ?? null;
        if ( $blend_mode !== null ) {
            $props['blend_mode'] = array(
                $breakpoint => $blend_mode,
            );
        }

        // Hover variant.
        $blend_mode_hover = $node['backgroundBlendModeHover'] ?? $node['blendModeHover'] ?? null;
        if ( $blend_mode_hover !== null ) {
            $props['blend_mode_hover'] = array(
                $breakpoint => $blend_mode_hover,
            );
        }

        return $props;
    }

    /**
     * Extract overlay properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The overlay properties.
     */
    private function extract_overlay_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check if we have any overlay properties.
        $has_overlay = isset( $node['overlay'] ) ||
                       isset( $node['overlayColor'] ) ||
                       isset( $node['overlayImage'] ) ||
                       isset( $node['overlayGradient'] ) ||
                       isset( $node['overlayOpacity'] ) ||
                       isset( $node['overlayBlendMode'] );

        if ( ! $has_overlay ) {
            // Check for hover variants.
            $has_overlay = isset( $node['overlayColorHover'] ) ||
                           isset( $node['overlayImageHover'] ) ||
                           isset( $node['overlayGradientHover'] ) ||
                           isset( $node['overlayOpacityHover'] ) ||
                           isset( $node['overlayBlendModeHover'] );
        }

        if ( ! $has_overlay ) {
            return $props;
        }

        // Build normal state overlay.
        $overlay = $this->build_overlay_object( $node, false );
        if ( ! empty( $overlay ) ) {
            $props['overlay'] = array(
                $breakpoint => $overlay,
            );
        }

        // Build hover state overlay.
        $overlay_hover = $this->build_overlay_object( $node, true );
        if ( ! empty( $overlay_hover ) ) {
            $props['overlay_hover'] = array(
                $breakpoint => $overlay_hover,
            );
        }

        return $props;
    }

    /**
     * Build an overlay object from node properties.
     *
     * @since 1.0.0
     *
     * @param array $node     The element node.
     * @param bool  $is_hover Whether to build hover variant.
     * @return array The overlay object.
     */
    private function build_overlay_object( array $node, bool $is_hover ): array {
        $suffix  = $is_hover ? 'Hover' : '';
        $overlay = array();

        // If overlay is provided as a complete object.
        if ( isset( $node[ 'overlay' . $suffix ] ) && is_array( $node[ 'overlay' . $suffix ] ) ) {
            return $this->normalize_overlay_object( $node[ 'overlay' . $suffix ] );
        }

        // Build from individual properties.
        if ( isset( $node[ 'overlayColor' . $suffix ] ) ) {
            $overlay['color'] = $node[ 'overlayColor' . $suffix ];
        }

        if ( isset( $node[ 'overlayImage' . $suffix ] ) ) {
            $overlay['image'] = $node[ 'overlayImage' . $suffix ];
        }

        if ( isset( $node[ 'overlayGradient' . $suffix ] ) ) {
            $gradient = $node[ 'overlayGradient' . $suffix ];
            if ( is_string( $gradient ) ) {
                $overlay['gradient'] = array(
                    'style' => $gradient,
                    'value' => $gradient,
                );
            } elseif ( is_array( $gradient ) ) {
                $overlay['gradient'] = $this->normalize_gradient_object( $gradient );
            }
        }

        if ( isset( $node[ 'overlayOpacity' . $suffix ] ) ) {
            $overlay['opacity'] = $this->normalize_opacity( $node[ 'overlayOpacity' . $suffix ] );
        }

        if ( isset( $node[ 'overlayBlendMode' . $suffix ] ) ) {
            if ( ! isset( $overlay['effects'] ) ) {
                $overlay['effects'] = array();
            }
            $overlay['effects']['blend_mode'] = $node[ 'overlayBlendMode' . $suffix ];
        }

        if ( isset( $node[ 'overlayFilter' . $suffix ] ) ) {
            if ( ! isset( $overlay['effects'] ) ) {
                $overlay['effects'] = array();
            }
            // Handle filter as array of filter items.
            $filter = $node[ 'overlayFilter' . $suffix ];
            if ( is_array( $filter ) ) {
                $overlay['effects']['filter'] = $filter;
            }
        }

        return $overlay;
    }

    /**
     * Normalize an overlay object to ensure it has proper structure.
     *
     * @since 1.0.0
     *
     * @param array $overlay The input overlay object.
     * @return array The normalized overlay object.
     */
    private function normalize_overlay_object( array $overlay ): array {
        $normalized = array();

        if ( isset( $overlay['color'] ) ) {
            $normalized['color'] = $overlay['color'];
        }

        if ( isset( $overlay['image'] ) ) {
            $normalized['image'] = $overlay['image'];
        }

        if ( isset( $overlay['gradient'] ) ) {
            if ( is_string( $overlay['gradient'] ) ) {
                $normalized['gradient'] = array(
                    'style' => $overlay['gradient'],
                    'value' => $overlay['gradient'],
                );
            } else {
                $normalized['gradient'] = $this->normalize_gradient_object( $overlay['gradient'] );
            }
        }

        if ( isset( $overlay['opacity'] ) ) {
            $normalized['opacity'] = $this->normalize_opacity( $overlay['opacity'] );
        }

        if ( isset( $overlay['blend_mode'] ) || isset( $overlay['effects']['blend_mode'] ) ) {
            if ( ! isset( $normalized['effects'] ) ) {
                $normalized['effects'] = array();
            }
            $normalized['effects']['blend_mode'] = $overlay['effects']['blend_mode'] ?? $overlay['blend_mode'];
        }

        if ( isset( $overlay['filter'] ) || isset( $overlay['effects']['filter'] ) ) {
            if ( ! isset( $normalized['effects'] ) ) {
                $normalized['effects'] = array();
            }
            $normalized['effects']['filter'] = $overlay['effects']['filter'] ?? $overlay['filter'];
        }

        return $normalized;
    }

    /**
     * Extract background layer properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The background layer properties.
     */
    private function extract_background_layer_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check for background layers array.
        if ( ! isset( $node['backgroundLayers'] ) || ! is_array( $node['backgroundLayers'] ) ) {
            return $props;
        }

        $layers = array();

        foreach ( $node['backgroundLayers'] as $layer ) {
            $normalized_layer = $this->build_background_layer_object( $layer );
            if ( ! empty( $normalized_layer ) ) {
                $layers[] = $normalized_layer;
            }
        }

        if ( ! empty( $layers ) ) {
            $props['layers'] = array(
                $breakpoint => $layers,
            );
        }

        return $props;
    }

    /**
     * Build a background layer object from input.
     *
     * @since 1.0.0
     *
     * @param array $layer The input layer data.
     * @return array The normalized background layer object.
     */
    private function build_background_layer_object( array $layer ): array {
        $normalized = array();

        // Layer type is required.
        $type = $layer['type'] ?? 'none';
        $normalized['type'] = $type;

        // Handle based on type.
        switch ( $type ) {
            case 'image':
                if ( isset( $layer['image'] ) ) {
                    $normalized['image'] = $layer['image'];
                }
                break;

            case 'gradient':
                if ( isset( $layer['gradient'] ) ) {
                    if ( is_string( $layer['gradient'] ) ) {
                        $normalized['gradient'] = array(
                            'style' => $layer['gradient'],
                            'value' => $layer['gradient'],
                        );
                    } else {
                        $normalized['gradient'] = $this->normalize_gradient_object( $layer['gradient'] );
                    }
                }
                break;

            case 'overlay_color':
                if ( isset( $layer['overlay_color'] ) ) {
                    $normalized['overlay_color'] = $layer['overlay_color'];
                } elseif ( isset( $layer['color'] ) ) {
                    $normalized['overlay_color'] = $layer['color'];
                }
                break;
        }

        // Common layer properties.
        if ( isset( $layer['blend_mode'] ) ) {
            $normalized['blend_mode'] = $layer['blend_mode'];
        }

        if ( isset( $layer['size'] ) ) {
            $normalized['size'] = $layer['size'];
        }

        if ( isset( $layer['position'] ) ) {
            $normalized['position'] = $layer['position'];
        }

        if ( isset( $layer['repeat'] ) ) {
            $normalized['repeat'] = $layer['repeat'];
        }

        if ( isset( $layer['attachment'] ) ) {
            $normalized['attachment'] = $layer['attachment'];
        }

        return $normalized;
    }

    /**
     * Extract background transition properties from node.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The background transition properties.
     */
    private function extract_background_transition_props( array $node, string $breakpoint ): array {
        $props = array();

        if ( isset( $node['backgroundTransitionDuration'] ) ) {
            $props['transition_duration'] = array(
                $breakpoint => $this->parse_unit_value( $node['backgroundTransitionDuration'], 'ms' ),
            );
        }

        return $props;
    }

    /**
     * Build border properties from simplified input.
     *
     * Transforms flat border properties (radius, per-side borders, uniform borders)
     * into the nested structure expected by Breakdance.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The nested border properties array.
     */
    public function build_border_properties( array $node, string $breakpoint = 'breakpoint_base' ): array {
        $borders = array();

        // Build border radius properties.
        $borders = $this->merge_deep(
            $borders,
            $this->extract_border_radius_props( $node, $breakpoint )
        );

        // Build per-side border properties.
        $borders = $this->merge_deep(
            $borders,
            $this->extract_per_side_border_props( $node, $breakpoint )
        );

        // Build uniform border properties.
        $borders = $this->merge_deep(
            $borders,
            $this->extract_uniform_border_props( $node, $breakpoint )
        );

        return $borders;
    }

    /**
     * Extract border radius properties from node.
     *
     * Handles both uniform radius (all corners) and per-corner radius.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The border radius properties.
     */
    private function extract_border_radius_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check for uniform border radius.
        if ( isset( $node['borderRadius'] ) ) {
            $radius = $this->build_border_radius_object( $node['borderRadius'] );
            if ( ! empty( $radius ) ) {
                $props['radius'] = array(
                    $breakpoint => $radius,
                );
            }
        }

        // Check for per-corner radius values.
        $corners = array(
            'radiusTopLeft'     => 'topLeft',
            'radiusTopRight'    => 'topRight',
            'radiusBottomLeft'  => 'bottomLeft',
            'radiusBottomRight' => 'bottomRight',
        );

        $per_corner = array();
        foreach ( $corners as $prop => $corner ) {
            if ( isset( $node[ $prop ] ) ) {
                $per_corner[ $corner ] = $this->parse_unit_value( $node[ $prop ], 'px' );
            }
        }

        if ( ! empty( $per_corner ) ) {
            if ( isset( $props['radius'][ $breakpoint ] ) ) {
                $props['radius'][ $breakpoint ] = array_merge(
                    $props['radius'][ $breakpoint ],
                    $per_corner
                );
            } else {
                $props['radius'] = array(
                    $breakpoint => $per_corner,
                );
            }
        }

        // Handle uniform 'borderRadiusAll' shorthand.
        if ( isset( $node['borderRadiusAll'] ) ) {
            $props['radius'] = array(
                $breakpoint => array(
                    'all' => $this->parse_unit_value( $node['borderRadiusAll'], 'px' ),
                ),
            );
        }

        // Hover variants for border radius.
        if ( isset( $node['borderRadiusHover'] ) ) {
            $radius = $this->build_border_radius_object( $node['borderRadiusHover'] );
            if ( ! empty( $radius ) ) {
                $props['radius_hover'] = array(
                    $breakpoint => $radius,
                );
            }
        }

        // Per-corner hover radius.
        $per_corner_hover = array();
        foreach ( $corners as $prop => $corner ) {
            $hover_prop = $prop . 'Hover';
            if ( isset( $node[ $hover_prop ] ) ) {
                $per_corner_hover[ $corner ] = $this->parse_unit_value( $node[ $hover_prop ], 'px' );
            }
        }

        if ( ! empty( $per_corner_hover ) ) {
            if ( isset( $props['radius_hover'][ $breakpoint ] ) ) {
                $props['radius_hover'][ $breakpoint ] = array_merge(
                    $props['radius_hover'][ $breakpoint ],
                    $per_corner_hover
                );
            } else {
                $props['radius_hover'] = array(
                    $breakpoint => $per_corner_hover,
                );
            }
        }

        // Uniform hover radius.
        if ( isset( $node['borderRadiusAllHover'] ) ) {
            $props['radius_hover'] = array(
                $breakpoint => array(
                    'all' => $this->parse_unit_value( $node['borderRadiusAllHover'], 'px' ),
                ),
            );
        }

        return $props;
    }

    /**
     * Build a border radius object from input.
     *
     * @since 1.0.0
     *
     * @param mixed $value The border radius value (string, number, or array).
     * @return array The border radius object.
     */
    private function build_border_radius_object( $value ): array {
        // If it's a simple value (string or number), apply to all corners.
        if ( is_string( $value ) || is_numeric( $value ) ) {
            return array(
                'all' => $this->parse_unit_value( $value, 'px' ),
            );
        }

        // If it's an array, handle per-corner values.
        if ( is_array( $value ) ) {
            $radius = array();

            if ( isset( $value['all'] ) ) {
                $radius['all'] = $this->parse_unit_value( $value['all'], 'px' );
            }

            if ( isset( $value['topLeft'] ) ) {
                $radius['topLeft'] = $this->parse_unit_value( $value['topLeft'], 'px' );
            }

            if ( isset( $value['topRight'] ) ) {
                $radius['topRight'] = $this->parse_unit_value( $value['topRight'], 'px' );
            }

            if ( isset( $value['bottomLeft'] ) ) {
                $radius['bottomLeft'] = $this->parse_unit_value( $value['bottomLeft'], 'px' );
            }

            if ( isset( $value['bottomRight'] ) ) {
                $radius['bottomRight'] = $this->parse_unit_value( $value['bottomRight'], 'px' );
            }

            return $radius;
        }

        return array();
    }

    /**
     * Extract per-side border properties from node.
     *
     * Handles borderTop, borderRight, borderBottom, borderLeft and their
     * individual width/style/color sub-properties.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The per-side border properties.
     */
    private function extract_per_side_border_props( array $node, string $breakpoint ): array {
        $props = array();
        $sides = array( 'top', 'right', 'bottom', 'left' );

        // Process each side for normal state.
        $border = array();
        foreach ( $sides as $side ) {
            $side_props = $this->extract_border_side_from_node( $node, $side, false );
            if ( ! empty( $side_props ) ) {
                $border[ $side ] = $side_props;
            }
        }

        if ( ! empty( $border ) ) {
            $props['border'] = array(
                $breakpoint => $border,
            );
        }

        // Process each side for hover state.
        $border_hover = array();
        foreach ( $sides as $side ) {
            $side_props = $this->extract_border_side_from_node( $node, $side, true );
            if ( ! empty( $side_props ) ) {
                $border_hover[ $side ] = $side_props;
            }
        }

        if ( ! empty( $border_hover ) ) {
            $props['border_hover'] = array(
                $breakpoint => $border_hover,
            );
        }

        return $props;
    }

    /**
     * Extract border properties for a specific side from node.
     *
     * @since 1.0.0
     *
     * @param array  $node     The element node.
     * @param string $side     The border side (top, right, bottom, left).
     * @param bool   $is_hover Whether to extract hover variant.
     * @return array The border side properties.
     */
    private function extract_border_side_from_node( array $node, string $side, bool $is_hover ): array {
        $suffix     = $is_hover ? 'Hover' : '';
        $side_upper = ucfirst( $side );
        $props      = array();

        // Check for complete border side object (e.g., borderTop: { width: '1px', style: 'solid', color: '#000' }).
        $full_prop = 'border' . $side_upper . $suffix;
        if ( isset( $node[ $full_prop ] ) ) {
            if ( is_array( $node[ $full_prop ] ) ) {
                return $this->build_border_side_object( $node[ $full_prop ] );
            }
            // If it's a string, try to parse as CSS shorthand.
            if ( is_string( $node[ $full_prop ] ) ) {
                return $this->parse_border_shorthand( $node[ $full_prop ] );
            }
        }

        // Check for individual properties (e.g., borderTopWidth, borderTopStyle, borderTopColor).
        $width_prop = 'border' . $side_upper . 'Width' . $suffix;
        $style_prop = 'border' . $side_upper . 'Style' . $suffix;
        $color_prop = 'border' . $side_upper . 'Color' . $suffix;

        if ( isset( $node[ $width_prop ] ) ) {
            $props['width'] = $this->parse_unit_value( $node[ $width_prop ], 'px' );
        }

        if ( isset( $node[ $style_prop ] ) ) {
            $props['style'] = $node[ $style_prop ];
        }

        if ( isset( $node[ $color_prop ] ) ) {
            $props['color'] = $node[ $color_prop ];
        }

        return $props;
    }

    /**
     * Build a border side object from array values.
     *
     * @since 1.0.0
     *
     * @param array $values The border side values (width, style, color).
     * @return array The formatted border side object.
     */
    private function build_border_side_object( array $values ): array {
        $side = array();

        if ( isset( $values['width'] ) ) {
            $side['width'] = is_array( $values['width'] )
                ? $values['width']
                : $this->parse_unit_value( $values['width'], 'px' );
        }

        if ( isset( $values['style'] ) ) {
            $side['style'] = $values['style'];
        }

        if ( isset( $values['color'] ) ) {
            $side['color'] = $values['color'];
        }

        return $side;
    }

    /**
     * Parse a CSS border shorthand value.
     *
     * Parses CSS border shorthand like "1px solid #000" into components.
     *
     * @since 1.0.0
     *
     * @param string $css_value The CSS border shorthand value.
     * @return array The parsed border side object.
     */
    private function parse_border_shorthand( string $css_value ): array {
        $css_value = trim( $css_value );

        // Check for 'none' value.
        if ( strtolower( $css_value ) === 'none' || empty( $css_value ) ) {
            return array(
                'width' => array( 'number' => 0, 'unit' => 'px', 'style' => '0px' ),
                'style' => 'none',
                'color' => '',
            );
        }

        // Valid border styles.
        $valid_styles = array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        );

        $side = array();

        // Split by whitespace.
        $parts = preg_split( '/\s+/', $css_value );

        foreach ( $parts as $part ) {
            $part = trim( $part );

            // Check if it's a border style.
            if ( in_array( strtolower( $part ), $valid_styles, true ) ) {
                $side['style'] = strtolower( $part );
                continue;
            }

            // Check if it's a width value (has a unit or is a number).
            if ( preg_match( '/^-?[\d.]+[a-z%]*$/i', $part ) ) {
                $side['width'] = $this->parse_unit_value( $part, 'px' );
                continue;
            }

            // Otherwise assume it's a color.
            $side['color'] = $part;
        }

        return $side;
    }

    /**
     * Extract uniform border properties from node.
     *
     * Handles borderAll and border shorthand for all sides.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node.
     * @param string $breakpoint The target breakpoint.
     * @return array The uniform border properties.
     */
    private function extract_uniform_border_props( array $node, string $breakpoint ): array {
        $props = array();

        // Check for complete uniform border object.
        if ( isset( $node['borderAll'] ) ) {
            $all_border = is_array( $node['borderAll'] )
                ? $this->build_border_side_object( $node['borderAll'] )
                : $this->parse_border_shorthand( (string) $node['borderAll'] );

            if ( ! empty( $all_border ) ) {
                // If border already has per-side values, add 'all'.
                if ( isset( $props['border'][ $breakpoint ] ) ) {
                    $props['border'][ $breakpoint ]['all'] = $all_border;
                } else {
                    $props['border'] = array(
                        $breakpoint => array(
                            'all' => $all_border,
                        ),
                    );
                }
            }
        }

        // Check for individual uniform properties.
        $has_uniform = isset( $node['borderAllWidth'] ) ||
                       isset( $node['borderAllStyle'] ) ||
                       isset( $node['borderAllColor'] );

        if ( $has_uniform ) {
            $all_props = array();

            if ( isset( $node['borderAllWidth'] ) ) {
                $all_props['width'] = $this->parse_unit_value( $node['borderAllWidth'], 'px' );
            }

            if ( isset( $node['borderAllStyle'] ) ) {
                $all_props['style'] = $node['borderAllStyle'];
            }

            if ( isset( $node['borderAllColor'] ) ) {
                $all_props['color'] = $node['borderAllColor'];
            }

            if ( ! empty( $all_props ) ) {
                if ( isset( $props['border'][ $breakpoint ]['all'] ) ) {
                    $props['border'][ $breakpoint ]['all'] = array_merge(
                        $props['border'][ $breakpoint ]['all'],
                        $all_props
                    );
                } elseif ( isset( $props['border'][ $breakpoint ] ) ) {
                    $props['border'][ $breakpoint ]['all'] = $all_props;
                } else {
                    $props['border'] = array(
                        $breakpoint => array(
                            'all' => $all_props,
                        ),
                    );
                }
            }
        }

        // Check for generic 'border' shorthand.
        if ( isset( $node['border'] ) && is_string( $node['border'] ) ) {
            $all_border = $this->parse_border_shorthand( $node['border'] );
            if ( ! empty( $all_border ) ) {
                $props['border'] = array(
                    $breakpoint => array(
                        'all' => $all_border,
                    ),
                );
            }
        }

        // Hover variants.
        if ( isset( $node['borderAllHover'] ) ) {
            $all_border = is_array( $node['borderAllHover'] )
                ? $this->build_border_side_object( $node['borderAllHover'] )
                : $this->parse_border_shorthand( (string) $node['borderAllHover'] );

            if ( ! empty( $all_border ) ) {
                $props['border_hover'] = array(
                    $breakpoint => array(
                        'all' => $all_border,
                    ),
                );
            }
        }

        // Individual uniform hover properties.
        $has_uniform_hover = isset( $node['borderAllWidthHover'] ) ||
                            isset( $node['borderAllStyleHover'] ) ||
                            isset( $node['borderAllColorHover'] );

        if ( $has_uniform_hover ) {
            $all_props = array();

            if ( isset( $node['borderAllWidthHover'] ) ) {
                $all_props['width'] = $this->parse_unit_value( $node['borderAllWidthHover'], 'px' );
            }

            if ( isset( $node['borderAllStyleHover'] ) ) {
                $all_props['style'] = $node['borderAllStyleHover'];
            }

            if ( isset( $node['borderAllColorHover'] ) ) {
                $all_props['color'] = $node['borderAllColorHover'];
            }

            if ( ! empty( $all_props ) ) {
                if ( isset( $props['border_hover'][ $breakpoint ]['all'] ) ) {
                    $props['border_hover'][ $breakpoint ]['all'] = array_merge(
                        $props['border_hover'][ $breakpoint ]['all'],
                        $all_props
                    );
                } elseif ( isset( $props['border_hover'][ $breakpoint ] ) ) {
                    $props['border_hover'][ $breakpoint ]['all'] = $all_props;
                } else {
                    $props['border_hover'] = array(
                        $breakpoint => array(
                            'all' => $all_props,
                        ),
                    );
                }
            }
        }

        // Generic 'borderHover' shorthand.
        if ( isset( $node['borderHover'] ) && is_string( $node['borderHover'] ) ) {
            $all_border = $this->parse_border_shorthand( $node['borderHover'] );
            if ( ! empty( $all_border ) ) {
                $props['border_hover'] = array(
                    $breakpoint => array(
                        'all' => $all_border,
                    ),
                );
            }
        }

        return $props;
    }

    /**
     * Normalize opacity value to 0-1 range.
     *
     * @since 1.0.0
     *
     * @param mixed $value The opacity value.
     * @return float The normalized opacity.
     */
    private function normalize_opacity( $value ): float {
        $numeric = is_numeric( $value ) ? floatval( $value ) : 1.0;

        // If value is greater than 1, assume it's a percentage.
        if ( $numeric > 1 ) {
            $numeric = $numeric / 100;
        }

        // Clamp to valid range.
        return max( 0, min( 1, $numeric ) );
    }

    /**
     * Deep merge two arrays.
     *
     * @since 1.0.0
     *
     * @param array $array1 First array.
     * @param array $array2 Second array.
     * @return array Merged array.
     */
    private function merge_deep( array $array1, array $array2 ): array {
        foreach ( $array2 as $key => $value ) {
            if ( isset( $array1[ $key ] ) && is_array( $array1[ $key ] ) && is_array( $value ) ) {
                $array1[ $key ] = $this->merge_deep( $array1[ $key ], $value );
            } else {
                $array1[ $key ] = $value;
            }
        }
        return $array1;
    }

    /**
     * Get the Property Schema instance.
     *
     * @since 1.0.0
     *
     * @return Property_Schema The schema instance.
     */
    public function get_schema(): Property_Schema {
        return $this->schema;
    }

    /**
     * Set the default breakpoint for responsive properties.
     *
     * @since 1.0.0
     *
     * @param string $breakpoint The breakpoint identifier.
     * @return self For method chaining.
     */
    public function set_default_breakpoint( string $breakpoint ): self {
        $this->default_breakpoint = $breakpoint;
        return $this;
    }

    /**
     * Get the default breakpoint.
     *
     * @since 1.0.0
     *
     * @return string The default breakpoint.
     */
    public function get_default_breakpoint(): string {
        return $this->default_breakpoint;
    }

    /**
     * Transform a complete node into Breakdance properties structure.
     *
     * Combines effects, transforms, backgrounds, and borders into
     * a complete design properties array.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint.
     * @return array The complete design properties.
     */
    public function transform_design_properties( array $node, string $breakpoint = 'breakpoint_base' ): array {
        $design = array();

        // Build effects (this subtask).
        $effects = $this->build_effects_properties( $node, $breakpoint );
        if ( ! empty( $effects ) ) {
            $design['effects'] = $effects;
        }

        // Build transform properties (subtask 3-2).
        $transform = $this->build_transform_properties( $node, $breakpoint );
        if ( ! empty( $transform ) ) {
            if ( ! isset( $design['effects'] ) ) {
                $design['effects'] = array();
            }
            $design['effects']['transform'] = $transform;
        }

        // Build background properties (gradients, blend modes, overlays).
        $background = $this->build_background_properties( $node, $breakpoint );
        if ( ! empty( $background ) ) {
            $design['background'] = $background;
        }

        // Build border properties.
        $borders = $this->build_border_properties( $node, $breakpoint );
        if ( ! empty( $borders ) ) {
            $design['borders'] = $borders;
        }

        return $design;
    }

    /**
     * Wrap properties in the design structure.
     *
     * Utility method to wrap effects/transform/etc properties
     * in the proper design path.
     *
     * @since 1.0.0
     *
     * @param array  $properties The properties to wrap.
     * @param string $category   The design category (effects, background, borders).
     * @return array The wrapped properties.
     */
    public function wrap_in_design( array $properties, string $category ): array {
        if ( empty( $properties ) ) {
            return array();
        }

        return array(
            'design' => array(
                $category => $properties,
            ),
        );
    }

    /**
     * Element type to supported property categories mapping.
     *
     * Defines which property categories each element type supports.
     *
     * @var array
     */
    private static array $element_property_support = array(
        'Section'   => array( 'effects', 'transform', 'background', 'borders' ),
        'Container' => array( 'effects', 'transform', 'background', 'borders' ),
        'Div'       => array( 'effects', 'transform', 'background', 'borders' ),
        'Column'    => array( 'effects', 'transform', 'background', 'borders' ),
        'Columns'   => array( 'effects', 'transform', 'background', 'borders' ),
        'Heading'   => array( 'effects', 'transform', 'borders' ),
        'Text'      => array( 'effects', 'transform', 'borders' ),
        'Button'    => array( 'effects', 'transform', 'background', 'borders' ),
        'Image'     => array( 'effects', 'transform', 'borders' ),
        'Icon'      => array( 'effects', 'transform' ),
        'Link'      => array( 'effects', 'transform', 'borders' ),
        'Spacer'    => array(),
        'Divider'   => array( 'effects', 'borders' ),
        'Video'     => array( 'effects', 'transform', 'borders' ),
    );

    /**
     * Build design properties for a specific element type.
     *
     * Uses element type awareness to apply only relevant property categories.
     * This ensures that elements only receive properties they can actually use.
     *
     * @since 1.0.0
     *
     * @param string $element_type The element type (Section, Div, etc.).
     * @param array  $node         The element node with simplified properties.
     * @param string $breakpoint   Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for the element.
     */
    public function build_element_design( string $element_type, array $node, string $breakpoint = 'breakpoint_base' ): array {
        $design = array();

        // Get supported categories for this element type.
        $supported = self::$element_property_support[ $element_type ] ?? array( 'effects', 'transform', 'background', 'borders' );

        // Build effects properties if supported.
        if ( in_array( 'effects', $supported, true ) ) {
            $effects = $this->build_effects_properties( $node, $breakpoint );
            if ( ! empty( $effects ) ) {
                $design['effects'] = $effects;
            }
        }

        // Build transform properties if supported.
        if ( in_array( 'transform', $supported, true ) ) {
            $transform = $this->build_transform_properties( $node, $breakpoint );
            if ( ! empty( $transform ) ) {
                if ( ! isset( $design['effects'] ) ) {
                    $design['effects'] = array();
                }
                $design['effects']['transform'] = $transform;
            }
        }

        // Build background properties if supported.
        if ( in_array( 'background', $supported, true ) ) {
            $background = $this->build_background_properties( $node, $breakpoint );
            if ( ! empty( $background ) ) {
                $design['background'] = $background;
            }
        }

        // Build border properties if supported.
        if ( in_array( 'borders', $supported, true ) ) {
            $borders = $this->build_border_properties( $node, $breakpoint );
            if ( ! empty( $borders ) ) {
                $design['borders'] = $borders;
            }
        }

        return $design;
    }

    /**
     * Build design properties for a Section element.
     *
     * Sections support all property categories including advanced backgrounds
     * with video and slideshow options.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Section.
     */
    public function build_section_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Section', $node, $breakpoint );
    }

    /**
     * Build design properties for a Div element.
     *
     * Divs support effects, transforms, backgrounds (less fancy), and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Div.
     */
    public function build_div_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Div', $node, $breakpoint );
    }

    /**
     * Build design properties for a Container element.
     *
     * Containers support all property categories similar to Sections.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Container.
     */
    public function build_container_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Container', $node, $breakpoint );
    }

    /**
     * Build design properties for a Column element.
     *
     * Columns support effects, transforms, backgrounds, and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Column.
     */
    public function build_column_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Column', $node, $breakpoint );
    }

    /**
     * Build design properties for a Heading element.
     *
     * Headings support effects, transforms, and borders but not backgrounds.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Heading.
     */
    public function build_heading_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Heading', $node, $breakpoint );
    }

    /**
     * Build design properties for a Text element.
     *
     * Text elements support effects, transforms, and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Text.
     */
    public function build_text_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Text', $node, $breakpoint );
    }

    /**
     * Build design properties for a Button element.
     *
     * Buttons support all property categories for full styling flexibility.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Button.
     */
    public function build_button_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Button', $node, $breakpoint );
    }

    /**
     * Build design properties for an Image element.
     *
     * Images support effects (filters, opacity), transforms, and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Image.
     */
    public function build_image_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Image', $node, $breakpoint );
    }

    /**
     * Build design properties for an Icon element.
     *
     * Icons support effects and transforms.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Icon.
     */
    public function build_icon_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Icon', $node, $breakpoint );
    }

    /**
     * Build design properties for a Link element.
     *
     * Links support effects, transforms, and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Link.
     */
    public function build_link_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Link', $node, $breakpoint );
    }

    /**
     * Build design properties for a Video element.
     *
     * Videos support effects, transforms, and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Video.
     */
    public function build_video_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Video', $node, $breakpoint );
    }

    /**
     * Build design properties for a Divider element.
     *
     * Dividers support effects and borders.
     *
     * @since 1.0.0
     *
     * @param array  $node       The element node with simplified properties.
     * @param string $breakpoint Optional. Target breakpoint. Default 'breakpoint_base'.
     * @return array The design properties for Divider.
     */
    public function build_divider_design( array $node, string $breakpoint = 'breakpoint_base' ): array {
        return $this->build_element_design( 'Divider', $node, $breakpoint );
    }

    /**
     * Get supported property categories for an element type.
     *
     * Returns the list of property categories that an element type supports.
     * Useful for validation and documentation purposes.
     *
     * @since 1.0.0
     *
     * @param string $element_type The element type (Section, Div, etc.).
     * @return array Array of supported category names.
     */
    public function get_supported_categories( string $element_type ): array {
        return self::$element_property_support[ $element_type ] ?? array( 'effects', 'transform', 'background', 'borders' );
    }

    /**
     * Check if an element type supports a specific property category.
     *
     * @since 1.0.0
     *
     * @param string $element_type The element type (Section, Div, etc.).
     * @param string $category     The property category to check.
     * @return bool True if the element supports the category.
     */
    public function element_supports_category( string $element_type, string $category ): bool {
        $supported = $this->get_supported_categories( $element_type );
        return in_array( $category, $supported, true );
    }

    /**
     * Get all supported element types.
     *
     * @since 1.0.0
     *
     * @return array Array of element type names.
     */
    public function get_supported_element_types(): array {
        return array_keys( self::$element_property_support );
    }

    /**
     * Register a custom element type with its supported property categories.
     *
     * Allows third-party elements to register their property support.
     *
     * @since 1.0.0
     *
     * @param string $element_type The element type name.
     * @param array  $categories   Array of supported category names.
     * @return self For method chaining.
     */
    public function register_element_type( string $element_type, array $categories ): self {
        self::$element_property_support[ $element_type ] = $categories;
        return $this;
    }
}
