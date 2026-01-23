<?php
/**
 * Property Transformer class for Oxybridge.
 *
 * Transforms simplified AI-friendly format to Breakdance format.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Property_Transformer
 *
 * Transforms simplified property format to exact Breakdance structure.
 * Uses the correct Breakdance preset section structures for CSS generation.
 *
 * @since 1.1.0
 */
class Property_Transformer {

    /**
     * Property schema instance.
     *
     * @var Property_Schema
     */
    private $schema;

    /**
     * Constructor.
     *
     * @param Property_Schema|null $schema Property schema instance.
     */
    public function __construct( Property_Schema $schema = null ) {
        $this->schema = $schema ?? new Property_Schema();
    }

    /**
     * Transform a simplified tree to Breakdance format.
     *
     * @param array $simplified_tree Simplified tree structure.
     * @return array Breakdance-compatible tree.
     */
    public function transform_tree( array $simplified_tree ) {
        if ( ! isset( $simplified_tree['root'] ) ) {
            $simplified_tree = array(
                'root' => array(
                    'id'       => 'root',
                    'data'     => array( 'type' => 'root' ),
                    'children' => $simplified_tree['children'] ?? array( $simplified_tree ),
                ),
            );
        }

        return array(
            'root' => $this->transform_node( $simplified_tree['root'], true ),
        );
    }

    /**
     * Transform a single node from simplified to Breakdance format.
     *
     * @param array $node    Simplified node.
     * @param bool  $is_root Whether this is the root node.
     * @return array Breakdance-compatible node.
     */
    public function transform_node( array $node, $is_root = false ) {
        if ( $is_root || ( isset( $node['data']['type'] ) && $node['data']['type'] === 'root' ) ) {
            return array(
                'id'       => $node['id'] ?? 'root',
                'data'     => array( 'type' => 'root' ),
                'children' => $this->transform_children( $node['children'] ?? array() ),
            );
        }

        if ( $this->is_breakdance_format( $node ) ) {
            $node['children'] = $this->transform_children( $node['children'] ?? array() );
            return $node;
        }

        return $this->transform_simplified_node( $node );
    }

    /**
     * Check if a node is already in Breakdance format.
     *
     * @param array $node Node to check.
     * @return bool True if Breakdance format, false if simplified.
     */
    private function is_breakdance_format( array $node ) {
        if ( isset( $node['data']['type'] ) ) {
            return strpos( $node['data']['type'], '\\' ) !== false;
        }
        return false;
    }

    /**
     * Transform a simplified node to Breakdance format.
     *
     * @param array $node Simplified node.
     * @return array Breakdance node.
     */
    private function transform_simplified_node( array $node ) {
        $type = $node['type'] ?? 'Div';
        $breakdance_type = $this->schema->get_element_type( $type );

        if ( ! $breakdance_type ) {
            $breakdance_type = 'EssentialElements\\Div';
        }

        $id = $node['id'] ?? $this->generate_id( $type );
        $properties = $this->transform_properties( $node, $type );

        return array(
            'id'       => $id,
            'data'     => array(
                'type'       => $breakdance_type,
                'properties' => $properties,
            ),
            'children' => $this->transform_children( $node['children'] ?? array() ),
        );
    }

    /**
     * Transform children array.
     *
     * @param array $children Children nodes.
     * @return array Transformed children.
     */
    private function transform_children( array $children ) {
        return array_map( array( $this, 'transform_node' ), $children );
    }

    /**
     * Transform simplified properties to Breakdance format.
     *
     * @param array  $node Simplified node.
     * @param string $type Element type.
     * @return array Breakdance properties.
     */
    private function transform_properties( array $node, string $type ) {
        $properties = array();
        $responsive = $node['responsive'] ?? array();

        // Extract layout properties
        $layout_props = $this->extract_layout_props( $node, $responsive );

        // Extract spacing properties
        $spacing_props = $this->extract_spacing_props( $node, $responsive );

        // Build properties based on element type
        switch ( $type ) {
            case 'Section':
                $properties = $this->build_section_properties( $node, $layout_props, $spacing_props, $responsive );
                break;
            case 'Div':
                $properties = $this->build_div_properties( $node, $layout_props, $spacing_props, $responsive );
                break;
            case 'Heading':
                $properties = $this->build_heading_properties( $node, $responsive );
                break;
            case 'Text':
            case 'RichText':
                $properties = $this->build_text_properties( $node, $responsive );
                break;
            case 'Button':
                $properties = $this->build_button_properties( $node, $responsive );
                break;
            case 'Image':
                $properties = $this->build_image_properties( $node, $responsive );
                break;
            case 'Spacer':
                $properties = $this->build_spacer_properties( $node, $responsive );
                break;
            case 'HtmlCode':
            case 'CssCode':
            case 'PhpCode':
                $properties = $this->build_code_properties( $node, $type );
                break;
            default:
                $properties = $this->build_generic_properties( $node, $layout_props, $spacing_props, $responsive );
                break;
        }

        return $properties;
    }

    /**
     * Extract layout-related properties from node.
     */
    private function extract_layout_props( array $node, array $responsive ) {
        $props = array();
        $layout_keys = array( 'display', 'flexDirection', 'justifyContent', 'alignItems', 'flexWrap', 'gap', 'gridColumns' );

        foreach ( $layout_keys as $key ) {
            if ( isset( $node[ $key ] ) ) {
                $props[ $key ] = array( 'breakpoint_base' => $node[ $key ] );
            }
        }

        foreach ( $responsive as $bp => $values ) {
            $bp_id = $this->schema->get_breakpoint_id( $bp );
            foreach ( $layout_keys as $key ) {
                if ( isset( $values[ $key ] ) ) {
                    if ( ! isset( $props[ $key ] ) ) {
                        $props[ $key ] = array();
                    }
                    $props[ $key ][ $bp_id ] = $values[ $key ];
                }
            }
        }

        return $props;
    }

    /**
     * Extract spacing-related properties from node.
     * Converts to unit objects with .style property for CSS templates.
     */
    private function extract_spacing_props( array $node, array $responsive ) {
        $props = array( 'padding' => array(), 'margin' => array() );

        // Parse padding shorthand
        if ( isset( $node['padding'] ) ) {
            $parsed = $this->parse_spacing_shorthand( $node['padding'] );
            foreach ( $parsed as $side => $value ) {
                $props['padding'][ $side ]['breakpoint_base'] = $this->to_unit_object( $value );
            }
        }

        // Parse margin shorthand
        if ( isset( $node['margin'] ) ) {
            $parsed = $this->parse_spacing_shorthand( $node['margin'] );
            foreach ( $parsed as $side => $value ) {
                $props['margin'][ $side ]['breakpoint_base'] = $this->to_unit_object( $value );
            }
        }

        // Handle responsive padding/margin
        foreach ( $responsive as $bp => $values ) {
            $bp_id = $this->schema->get_breakpoint_id( $bp );
            if ( isset( $values['padding'] ) ) {
                $parsed = $this->parse_spacing_shorthand( $values['padding'] );
                foreach ( $parsed as $side => $value ) {
                    $props['padding'][ $side ][ $bp_id ] = $this->to_unit_object( $value );
                }
            }
            if ( isset( $values['margin'] ) ) {
                $parsed = $this->parse_spacing_shorthand( $values['margin'] );
                foreach ( $parsed as $side => $value ) {
                    $props['margin'][ $side ][ $bp_id ] = $this->to_unit_object( $value );
                }
            }
        }

        return $props;
    }

    /**
     * Parse CSS spacing shorthand (e.g., "20px 40px" or "10px 20px 30px 40px").
     */
    private function parse_spacing_shorthand( string $value ) {
        $parts = preg_split( '/\s+/', trim( $value ) );
        $count = count( $parts );

        return array(
            'top'    => $parts[0],
            'right'  => $count >= 2 ? $parts[1] : $parts[0],
            'bottom' => $count >= 3 ? $parts[2] : $parts[0],
            'left'   => $count >= 4 ? $parts[3] : ( $count >= 2 ? $parts[1] : $parts[0] ),
        );
    }

    /**
     * Build Section element properties using Breakdance's expected structure.
     */
    private function build_section_properties( array $node, array $layout, array $spacing, array $responsive ) {
        $props = array( 'design' => array() );

        // Background
        if ( isset( $node['background'] ) ) {
            $props['design']['background']['color'] = $node['background'];
        }
        if ( isset( $node['backgroundImage'] ) ) {
            $props['design']['background']['layers'] = array(
                array(
                    'type'     => 'image',
                    'image'    => array( 'type' => 'external_url', 'url' => $node['backgroundImage'] ),
                    'size'     => 'cover',
                    'position' => 'center center',
                ),
            );
        }

        // Layout using layout_v2 structure
        if ( ! empty( $layout ) ) {
            $props['design']['layout_v2'] = $this->build_layout_v2( $layout );
        }

        // Spacing - Section uses design.spacing.padding
        if ( ! empty( $spacing['padding'] ) ) {
            $props['design']['spacing']['padding'] = $spacing['padding'];
        }
        if ( ! empty( $spacing['margin'] ) ) {
            if ( isset( $spacing['margin']['top'] ) ) {
                $props['design']['spacing']['margin_top'] = $spacing['margin']['top'];
            }
            if ( isset( $spacing['margin']['bottom'] ) ) {
                $props['design']['spacing']['margin_bottom'] = $spacing['margin']['bottom'];
            }
        }

        // Size
        if ( isset( $node['maxWidth'] ) ) {
            $props['design']['size']['width'] = 'custom';
            $props['design']['size']['container_width'] = $this->to_unit_object( $node['maxWidth'] );
        }
        if ( isset( $node['minHeight'] ) ) {
            $props['design']['size']['height'] = 'custom';
            $props['design']['size']['min_height'] = $this->to_unit_object( $node['minHeight'] );
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build Div element properties using Breakdance's expected structure.
     * Div uses design.container.padding.padding.{side} (nested because of spacing_padding_all preset)
     */
    private function build_div_properties( array $node, array $layout, array $spacing, array $responsive ) {
        $props = array( 'design' => array() );

        // Background
        if ( isset( $node['background'] ) ) {
            $props['design']['background']['color'] = $node['background'];
        }
        if ( isset( $node['backgroundImage'] ) ) {
            $props['design']['background']['layers'] = array(
                array(
                    'type'     => 'image',
                    'image'    => array( 'type' => 'external_url', 'url' => $node['backgroundImage'] ),
                    'size'     => 'cover',
                    'position' => 'center center',
                ),
            );
        }

        // Layout using layout_v2 structure
        if ( ! empty( $layout ) ) {
            $props['design']['layout_v2'] = $this->build_layout_v2( $layout );
        }

        // Container properties - Div uses design.container.padding.padding.{side} (nested preset)
        // The spacing_padding_all preset creates a nested 'padding' control
        if ( ! empty( $spacing['padding'] ) ) {
            $props['design']['container']['padding']['padding'] = $spacing['padding'];
        }
        if ( isset( $node['width'] ) ) {
            $props['design']['container']['width'] = $this->to_unit_object( $node['width'] );
        }
        if ( isset( $node['maxWidth'] ) ) {
            $props['design']['container']['width'] = $this->to_unit_object( $node['maxWidth'] );
        }
        if ( isset( $node['minHeight'] ) ) {
            $props['design']['container']['min_height'] = $this->to_unit_object( $node['minHeight'] );
        }

        // Spacing margin - uses spacing_margin_y which expects margin_top/margin_bottom directly
        if ( ! empty( $spacing['margin'] ) ) {
            if ( isset( $spacing['margin']['top'] ) ) {
                $props['design']['spacing']['margin_top'] = $spacing['margin']['top'];
            }
            if ( isset( $spacing['margin']['bottom'] ) ) {
                $props['design']['spacing']['margin_bottom'] = $spacing['margin']['bottom'];
            }
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build layout_v2 structure from simplified layout props.
     */
    private function build_layout_v2( array $layout ) {
        $layout_v2 = array();

        // Determine layout type
        $display = $layout['display']['breakpoint_base'] ?? null;
        $direction = $layout['flexDirection']['breakpoint_base'] ?? null;
        $grid_cols = $layout['gridColumns']['breakpoint_base'] ?? null;

        if ( $display === 'grid' || $grid_cols ) {
            // Grid layout
            $layout_v2['layout'] = 'grid';

            // Parse grid columns (e.g., "repeat(4, 1fr)" -> 4)
            if ( $grid_cols && preg_match( '/repeat\s*\(\s*(\d+)/', $grid_cols, $matches ) ) {
                $layout_v2['g_items_per_row'] = array( 'breakpoint_base' => (int) $matches[1] );
            } elseif ( $grid_cols ) {
                // Count columns from "1fr 1fr 1fr" format
                $cols = count( preg_split( '/\s+/', trim( $grid_cols ) ) );
                $layout_v2['g_items_per_row'] = array( 'breakpoint_base' => $cols );
            }

            // Handle responsive grid columns
            foreach ( $layout['gridColumns'] ?? array() as $bp => $val ) {
                if ( $bp !== 'breakpoint_base' && preg_match( '/repeat\s*\(\s*(\d+)/', $val, $matches ) ) {
                    $layout_v2['g_items_per_row'][ $bp ] = (int) $matches[1];
                } elseif ( $bp !== 'breakpoint_base' ) {
                    $cols = count( preg_split( '/\s+/', trim( $val ) ) );
                    $layout_v2['g_items_per_row'][ $bp ] = $cols;
                }
            }

            // Gap
            if ( isset( $layout['gap'] ) ) {
                $layout_v2['g_space_between_items'] = $this->to_responsive_unit( $layout['gap'] );
            }

        } elseif ( $direction === 'column' || $direction === 'column-reverse' ) {
            // Vertical layout
            $layout_v2['layout'] = 'vertical';
            $layout_v2['v_align'] = $this->map_align_items( $layout['alignItems'] ?? array() );
            $layout_v2['v_vertical_align'] = $this->map_justify_content( $layout['justifyContent'] ?? array() );
            if ( isset( $layout['gap'] ) ) {
                $layout_v2['v_gap'] = $this->to_responsive_unit( $layout['gap'] );
            }

        } else {
            // Horizontal layout (default for flex)
            $layout_v2['layout'] = 'horizontal';
            $layout_v2['h_align'] = $this->map_justify_content( $layout['justifyContent'] ?? array() );
            $layout_v2['h_vertical_align'] = $this->map_align_items( $layout['alignItems'] ?? array() );
            if ( isset( $layout['gap'] ) ) {
                $layout_v2['h_gap'] = $this->to_responsive_unit( $layout['gap'] );
            }
        }

        return $layout_v2;
    }

    /**
     * Map align-items values (flex values stay the same for Breakdance).
     */
    private function map_align_items( array $values ) {
        $result = array();
        foreach ( $values as $bp => $val ) {
            $result[ $bp ] = $val; // flex-start, center, flex-end, stretch, baseline
        }
        return $result;
    }

    /**
     * Map justify-content values.
     */
    private function map_justify_content( array $values ) {
        $result = array();
        foreach ( $values as $bp => $val ) {
            $result[ $bp ] = $val; // flex-start, center, flex-end, space-between, space-around, space-evenly
        }
        return $result;
    }

    /**
     * Build Heading element properties.
     */
    private function build_heading_properties( array $node, array $responsive ) {
        $props = array();

        // Content
        if ( isset( $node['text'] ) ) {
            $props['content']['content']['text'] = $node['text'];
        }
        if ( isset( $node['tag'] ) ) {
            $props['content']['content']['tags'] = $node['tag'];
        }

        // Typography
        $typo = array();
        if ( isset( $node['color'] ) ) {
            $props['design']['typography']['color']['breakpoint_base'] = $node['color'];
        }

        // Custom typography
        $custom_typo = array();
        if ( isset( $node['fontSize'] ) ) {
            $custom_typo['fontSize']['breakpoint_base'] = $this->to_unit_object( $node['fontSize'] );
        }
        if ( isset( $node['fontWeight'] ) ) {
            $custom_typo['fontWeight'] = $node['fontWeight'];
        }
        if ( isset( $node['lineHeight'] ) ) {
            $custom_typo['lineHeight']['breakpoint_base'] = $this->to_unit_object( $node['lineHeight'] );
        }
        if ( isset( $node['letterSpacing'] ) ) {
            $custom_typo['letterSpacing'] = $this->to_unit_object( $node['letterSpacing'] );
        }
        if ( isset( $node['textAlign'] ) ) {
            $custom_typo['textAlign']['breakpoint_base'] = $node['textAlign'];
        }
        if ( isset( $node['textTransform'] ) ) {
            $custom_typo['textTransform'] = $node['textTransform'];
        }

        // Handle responsive typography
        foreach ( $responsive as $bp => $values ) {
            $bp_id = $this->schema->get_breakpoint_id( $bp );
            if ( isset( $values['color'] ) ) {
                $props['design']['typography']['color'][ $bp_id ] = $values['color'];
            }
            if ( isset( $values['fontSize'] ) ) {
                $custom_typo['fontSize'][ $bp_id ] = $this->to_unit_object( $values['fontSize'] );
            }
            if ( isset( $values['textAlign'] ) ) {
                $custom_typo['textAlign'][ $bp_id ] = $values['textAlign'];
            }
        }

        if ( ! empty( $custom_typo ) ) {
            $props['design']['typography']['typography']['custom']['customTypography'] = $custom_typo;
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build Text element properties.
     */
    private function build_text_properties( array $node, array $responsive ) {
        $props = array();

        // Content
        if ( isset( $node['text'] ) ) {
            $props['content']['content']['text'] = $node['text'];
        }

        // Typography (same as heading)
        if ( isset( $node['color'] ) ) {
            $props['design']['typography']['color']['breakpoint_base'] = $node['color'];
        }

        $custom_typo = array();
        if ( isset( $node['fontSize'] ) ) {
            $custom_typo['fontSize']['breakpoint_base'] = $this->to_unit_object( $node['fontSize'] );
        }
        if ( isset( $node['fontWeight'] ) ) {
            $custom_typo['fontWeight'] = $node['fontWeight'];
        }
        if ( isset( $node['lineHeight'] ) ) {
            $custom_typo['lineHeight']['breakpoint_base'] = $this->to_unit_object( $node['lineHeight'] );
        }
        if ( isset( $node['letterSpacing'] ) ) {
            $custom_typo['letterSpacing'] = $this->to_unit_object( $node['letterSpacing'] );
        }
        if ( isset( $node['textAlign'] ) ) {
            $custom_typo['textAlign']['breakpoint_base'] = $node['textAlign'];
        }

        // Handle responsive
        foreach ( $responsive as $bp => $values ) {
            $bp_id = $this->schema->get_breakpoint_id( $bp );
            if ( isset( $values['color'] ) ) {
                $props['design']['typography']['color'][ $bp_id ] = $values['color'];
            }
            if ( isset( $values['fontSize'] ) ) {
                $custom_typo['fontSize'][ $bp_id ] = $this->to_unit_object( $values['fontSize'] );
            }
            if ( isset( $values['textAlign'] ) ) {
                $custom_typo['textAlign'][ $bp_id ] = $values['textAlign'];
            }
        }

        if ( ! empty( $custom_typo ) ) {
            $props['design']['typography']['typography']['custom']['customTypography'] = $custom_typo;
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build Button element properties.
     */
    private function build_button_properties( array $node, array $responsive ) {
        $props = array();

        // Content
        if ( isset( $node['text'] ) ) {
            $props['content']['content']['text'] = $node['text'];
        }
        if ( isset( $node['link'] ) ) {
            $props['content']['content']['link']['url'] = $node['link'];
            $props['content']['content']['link']['type'] = 'url';
        }

        // Button style
        if ( isset( $node['background'] ) ) {
            $props['design']['button']['styles']['background'] = $node['background'];
        }
        if ( isset( $node['color'] ) ) {
            $props['design']['button']['styles']['text']['color']['breakpoint_base'] = $node['color'];
        }
        if ( isset( $node['fontSize'] ) ) {
            $props['design']['button']['styles']['text']['typography']['custom']['customTypography']['fontSize']['breakpoint_base'] = $this->to_unit_object( $node['fontSize'] );
        }
        if ( isset( $node['fontWeight'] ) ) {
            $props['design']['button']['styles']['text']['typography']['custom']['customTypography']['fontWeight'] = $node['fontWeight'];
        }
        if ( isset( $node['padding'] ) ) {
            $parsed = $this->parse_spacing_shorthand( $node['padding'] );
            $props['design']['button']['styles']['size']['padding'] = array(
                'top'    => array( 'breakpoint_base' => $this->to_unit_object( $parsed['top'] ) ),
                'right'  => array( 'breakpoint_base' => $this->to_unit_object( $parsed['right'] ) ),
                'bottom' => array( 'breakpoint_base' => $this->to_unit_object( $parsed['bottom'] ) ),
                'left'   => array( 'breakpoint_base' => $this->to_unit_object( $parsed['left'] ) ),
            );
        }
        if ( isset( $node['borderRadius'] ) ) {
            $props['design']['button']['styles']['corners']['radius']['all']['breakpoint_base'] = $this->to_unit_object( $node['borderRadius'] );
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build Image element properties.
     */
    private function build_image_properties( array $node, array $responsive ) {
        $props = array();

        // Content
        if ( isset( $node['url'] ) || isset( $node['imageUrl'] ) ) {
            $props['content']['image']['url'] = $node['url'] ?? $node['imageUrl'];
            $props['content']['image']['from'] = 'url';
        }
        if ( isset( $node['alt'] ) || isset( $node['imageAlt'] ) ) {
            $props['content']['image']['alt'] = $node['alt'] ?? $node['imageAlt'];
        }

        // Size
        if ( isset( $node['width'] ) ) {
            $props['design']['image']['width']['breakpoint_base'] = $this->to_unit_object( $node['width'] );
        }
        if ( isset( $node['height'] ) ) {
            $props['design']['image']['height']['breakpoint_base'] = $this->to_unit_object( $node['height'] );
        }
        if ( isset( $node['borderRadius'] ) ) {
            $props['design']['image']['corners']['radius']['all']['breakpoint_base'] = $this->to_unit_object( $node['borderRadius'] );
        }

        // Custom CSS
        $this->add_custom_css( $props, $node, $responsive );

        return $props;
    }

    /**
     * Build Spacer element properties.
     */
    private function build_spacer_properties( array $node, array $responsive ) {
        $props = array();

        if ( isset( $node['height'] ) ) {
            $props['design']['spacer']['height']['breakpoint_base'] = $this->to_unit_object( $node['height'] );
        }

        foreach ( $responsive as $bp => $values ) {
            $bp_id = $this->schema->get_breakpoint_id( $bp );
            if ( isset( $values['height'] ) ) {
                $props['design']['spacer']['height'][ $bp_id ] = $this->to_unit_object( $values['height'] );
            }
        }

        return $props;
    }

    /**
     * Build Code element properties (HTML, CSS, PHP).
     */
    private function build_code_properties( array $node, string $type ) {
        $props = array();

        if ( $type === 'HtmlCode' && isset( $node['html'] ) ) {
            $props['content']['content']['html_code'] = $node['html'];
        }
        if ( $type === 'CssCode' && isset( $node['css'] ) ) {
            $props['content']['content']['css_code'] = $node['css'];
        }
        if ( $type === 'PhpCode' && isset( $node['php'] ) ) {
            $props['content']['content']['php_code'] = $node['php'];
        }
        if ( isset( $node['label'] ) ) {
            $props['content']['content']['builder_label'] = $node['label'];
        }

        return $props;
    }

    /**
     * Build generic container properties.
     */
    private function build_generic_properties( array $node, array $layout, array $spacing, array $responsive ) {
        // Fallback to Div-like properties
        return $this->build_div_properties( $node, $layout, $spacing, $responsive );
    }

    /**
     * Add custom CSS to properties.
     */
    private function add_custom_css( array &$props, array $node, array $responsive ) {
        if ( isset( $node['customCss'] ) ) {
            $props['design']['custom_css']['css']['breakpoint_base'] = $node['customCss'];
        }
        if ( isset( $node['customCssTablet'] ) ) {
            $props['design']['custom_css']['css']['breakpoint_tablet_portrait'] = $node['customCssTablet'];
        }
        if ( isset( $node['customCssPhone'] ) ) {
            $props['design']['custom_css']['css']['breakpoint_phone_portrait'] = $node['customCssPhone'];
        }

        foreach ( $responsive as $bp => $values ) {
            if ( isset( $values['customCss'] ) ) {
                $bp_id = $this->schema->get_breakpoint_id( $bp );
                $props['design']['custom_css']['css'][ $bp_id ] = $values['customCss'];
            }
        }
    }

    /**
     * Convert a value to unit object format.
     * Breakdance CSS templates expect: {number, unit, style} where style is the CSS value string.
     *
     * @param mixed $value Value string (e.g., "24px") or already an object.
     * @return array|string Unit object or original value.
     */
    private function to_unit_object( $value ) {
        if ( is_array( $value ) ) {
            // Ensure style property exists
            if ( isset( $value['number'] ) && isset( $value['unit'] ) && ! isset( $value['style'] ) ) {
                $value['style'] = $value['number'] . $value['unit'];
            }
            return $value;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        // Special values - return as-is with style property
        if ( in_array( $value, array( 'auto', 'inherit', 'initial', 'unset', 'none' ), true ) ) {
            return array( 'style' => $value );
        }

        // CSS variable - return with style property
        if ( strpos( $value, 'var(' ) === 0 ) {
            return array( 'style' => $value );
        }

        // Parse number and unit
        if ( preg_match( '/^(-?[\d.]+)(px|em|rem|%|vw|vh|vmin|vmax|ch|ex)?$/i', $value, $matches ) ) {
            $number = (float) $matches[1];
            $unit = isset( $matches[2] ) ? strtolower( $matches[2] ) : 'px';
            return array(
                'number' => $number,
                'unit'   => $unit,
                'style'  => $number . $unit,
            );
        }

        // Return as style property for any other string
        return array( 'style' => $value );
    }

    /**
     * Convert responsive values to unit objects.
     */
    private function to_responsive_unit( array $values ) {
        $result = array();
        foreach ( $values as $bp => $val ) {
            $result[ $bp ] = $this->to_unit_object( $val );
        }
        return $result;
    }

    /**
     * Generate a unique ID for an element.
     *
     * @param string $type Element type.
     * @return string Generated ID.
     */
    private function generate_id( string $type ) {
        static $counters = array();

        if ( ! isset( $counters[ $type ] ) ) {
            $counters[ $type ] = 0;
        }

        $counters[ $type ]++;
        return strtolower( $type ) . '-' . $counters[ $type ];
    }

    /**
     * Transform a single element (not a full tree).
     *
     * @param array $element Simplified element.
     * @return array Breakdance element.
     */
    public function transform_element( array $element ) {
        return $this->transform_node( $element );
    }

    /**
     * Get the schema instance.
     *
     * @return Property_Schema Schema instance.
     */
    public function get_schema() {
        return $this->schema;
    }
}
