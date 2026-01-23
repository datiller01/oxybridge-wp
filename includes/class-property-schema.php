<?php
/**
 * Property Schema class for Oxybridge.
 *
 * Extracts valid property paths from Breakdance element classes.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Property_Schema
 *
 * Extracts and manages property schemas from Breakdance elements.
 * Parses designControls(), contentControls(), and settingsControls() methods
 * to build accurate property path mappings.
 *
 * @since 1.1.0
 */
class Property_Schema {

    /**
     * Cached element schemas.
     *
     * @var array
     */
    private $schemas = array();

    /**
     * Element type to class mapping.
     *
     * @var array
     */
    private $element_map = array();

    /**
     * Simplified property name to Breakdance path mapping.
     *
     * @var array
     */
    private static $property_mappings = array(
        // Typography
        'color'           => 'design.typography.color',
        'fontSize'        => 'design.typography.typography.custom.customTypography.fontSize',
        'fontWeight'      => 'design.typography.typography.custom.customTypography.fontWeight',
        'fontFamily'      => 'design.typography.typography.custom.customTypography.fontFamily',
        'lineHeight'      => 'design.typography.typography.custom.customTypography.lineHeight',
        'letterSpacing'   => 'design.typography.typography.custom.customTypography.letterSpacing',
        'textAlign'       => 'design.typography.typography.custom.customTypography.textAlign',
        'textTransform'   => 'design.typography.typography.custom.customTypography.textTransform',
        'textDecoration'  => 'design.typography.typography.custom.customTypography.textDecoration',

        // Layout
        'display'         => 'design.layout.display',
        'flexDirection'   => 'design.layout.flexDirection',
        'justifyContent'  => 'design.layout.justifyContent',
        'alignItems'      => 'design.layout.alignItems',
        'flexWrap'        => 'design.layout.flexWrap',
        'gap'             => 'design.layout.horizontalGap', // Will also set verticalGap
        'horizontalGap'   => 'design.layout.horizontalGap',
        'verticalGap'     => 'design.layout.verticalGap',
        'gridColumns'     => 'design.layout.gridTemplateColumns',
        'gridRows'        => 'design.layout.gridTemplateRows',

        // Spacing
        'padding'         => 'design.spacing.padding',
        'paddingTop'      => 'design.spacing.padding.top',
        'paddingRight'    => 'design.spacing.padding.right',
        'paddingBottom'   => 'design.spacing.padding.bottom',
        'paddingLeft'     => 'design.spacing.padding.left',
        'margin'          => 'design.spacing.margin',
        'marginTop'       => 'design.spacing.margin_top',
        'marginRight'     => 'design.spacing.margin_right',
        'marginBottom'    => 'design.spacing.margin_bottom',
        'marginLeft'      => 'design.spacing.margin_left',

        // Size
        'width'           => 'design.size.width',
        'height'          => 'design.size.height',
        'minWidth'        => 'design.size.minWidth',
        'minHeight'       => 'design.size.minHeight',
        'maxWidth'        => 'design.size.maxWidth',
        'maxHeight'       => 'design.size.maxHeight',

        // Background
        'background'      => 'design.background.color',
        'backgroundColor' => 'design.background.color',
        'backgroundImage' => 'design.background.image',

        // Borders
        'borderRadius'    => 'design.borders.radius.all',
        'borderWidth'     => 'design.borders.border.all.width',
        'borderStyle'     => 'design.borders.border.all.style',
        'borderColor'     => 'design.borders.border.all.color',

        // Effects
        'boxShadow'       => 'design.effects.box_shadow',
        'opacity'         => 'design.effects.opacity',
        'overflow'        => 'design.effects.overflow',

        // Custom CSS
        'customCss'       => 'design.custom_css.css',
        'customCssTablet' => 'design.custom_css.css',
        'customCssPhone'  => 'design.custom_css.css',

        // Content - Heading
        'text'            => 'content.content.text',
        'tag'             => 'content.content.tags',

        // Content - Button
        'buttonText'      => 'content.content.text',
        'link'            => 'content.content.link.url',
        'linkType'        => 'content.content.link.type',

        // Content - Image
        'imageUrl'        => 'content.image.url',
        'imageFrom'       => 'content.image.from',
        'imageAlt'        => 'content.image.alt',

        // Code Elements
        'html'            => 'content.content.html_code',
        'css'             => 'content.content.css_code',
        'php'             => 'content.content.php_code',
        'label'           => 'content.content.builder_label',
    );

    /**
     * Breakpoint name mappings.
     *
     * @var array
     */
    private static $breakpoint_map = array(
        'desktop'         => 'breakpoint_base',
        'base'            => 'breakpoint_base',
        'tabletLandscape' => 'breakpoint_tablet_landscape',
        'tablet'          => 'breakpoint_tablet_portrait',
        'phoneLandscape'  => 'breakpoint_phone_landscape',
        'phone'           => 'breakpoint_phone_portrait',
        'mobile'          => 'breakpoint_phone_portrait',
    );

    /**
     * Properties that support responsive values.
     *
     * @var array
     */
    private static $responsive_properties = array(
        'color',
        'fontSize',
        'textAlign',
        'lineHeight',
        'letterSpacing',
        'display',
        'flexDirection',
        'justifyContent',
        'alignItems',
        'flexWrap',
        'gap',
        'horizontalGap',
        'verticalGap',
        'gridColumns',
        'gridRows',
        'padding',
        'paddingTop',
        'paddingRight',
        'paddingBottom',
        'paddingLeft',
        'margin',
        'marginTop',
        'marginRight',
        'marginBottom',
        'marginLeft',
        'width',
        'height',
        'minWidth',
        'minHeight',
        'maxWidth',
        'maxHeight',
        'borderRadius',
        'customCss',
    );

    /**
     * Properties that require unit format (number + unit object).
     *
     * @var array
     */
    private static $unit_properties = array(
        'fontSize',
        'lineHeight',
        'letterSpacing',
        'padding',
        'paddingTop',
        'paddingRight',
        'paddingBottom',
        'paddingLeft',
        'margin',
        'marginTop',
        'marginRight',
        'marginBottom',
        'marginLeft',
        'width',
        'height',
        'minWidth',
        'minHeight',
        'maxWidth',
        'maxHeight',
        'borderRadius',
        'borderWidth',
        'gap',
        'horizontalGap',
        'verticalGap',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_element_map();
    }

    /**
     * Initialize element type to class mapping.
     *
     * @return void
     */
    private function init_element_map() {
        $this->element_map = array(
            // Essential Elements
            'Section'       => 'EssentialElements\\Section',
            'Div'           => 'EssentialElements\\Div',
            'Columns'       => 'EssentialElements\\Columns',
            'Column'        => 'EssentialElements\\Column',
            'Heading'       => 'EssentialElements\\Heading',
            'Text'          => 'EssentialElements\\Text',
            'RichText'      => 'EssentialElements\\RichText',
            'Button'        => 'EssentialElements\\Button',
            'Image'         => 'EssentialElements\\Image2',
            'Icon'          => 'EssentialElements\\Icon',
            'Video'         => 'EssentialElements\\Video',
            'Spacer'        => 'EssentialElements\\Spacer',
            'Slider'        => 'EssentialElements\\Slider',
            'Tabs'          => 'EssentialElements\\Tabs',
            'Accordion'     => 'EssentialElements\\Accordion',
            'Form'          => 'EssentialElements\\FormBuilder',

            // Code Elements
            'HtmlCode'      => 'OxygenElements\\HtmlCode',
            'CssCode'       => 'OxygenElements\\CssCode',
            'PhpCode'       => 'OxygenElements\\PhpCode',
        );
    }

    /**
     * Get Breakdance element type from simplified name.
     *
     * @param string $simplified_name Simplified element name (e.g., 'Heading').
     * @return string|null Breakdance element type or null if not found.
     */
    public function get_element_type( $simplified_name ) {
        return $this->element_map[ $simplified_name ] ?? null;
    }

    /**
     * Get simplified name from Breakdance element type.
     *
     * @param string $breakdance_type Breakdance element type.
     * @return string|null Simplified name or null if not found.
     */
    public function get_simplified_name( $breakdance_type ) {
        return array_search( $breakdance_type, $this->element_map, true ) ?: null;
    }

    /**
     * Get all element mappings.
     *
     * @return array Element type mappings.
     */
    public function get_element_map() {
        return $this->element_map;
    }

    /**
     * Get property path mapping.
     *
     * @param string $simplified_property Simplified property name.
     * @return string|null Breakdance property path or null if not found.
     */
    public function get_property_path( $simplified_property ) {
        return self::$property_mappings[ $simplified_property ] ?? null;
    }

    /**
     * Get all property mappings.
     *
     * @return array Property path mappings.
     */
    public function get_property_mappings() {
        return self::$property_mappings;
    }

    /**
     * Check if a property supports responsive values.
     *
     * @param string $property Property name.
     * @return bool True if responsive, false otherwise.
     */
    public function is_responsive_property( $property ) {
        return in_array( $property, self::$responsive_properties, true );
    }

    /**
     * Check if a property requires unit format.
     *
     * @param string $property Property name.
     * @return bool True if unit property, false otherwise.
     */
    public function is_unit_property( $property ) {
        return in_array( $property, self::$unit_properties, true );
    }

    /**
     * Get breakpoint ID from name.
     *
     * @param string $name Breakpoint name (e.g., 'tablet').
     * @return string Breakpoint ID (e.g., 'breakpoint_tablet_portrait').
     */
    public function get_breakpoint_id( $name ) {
        return self::$breakpoint_map[ $name ] ?? 'breakpoint_base';
    }

    /**
     * Get all breakpoint mappings.
     *
     * @return array Breakpoint name to ID mappings.
     */
    public function get_breakpoint_map() {
        return self::$breakpoint_map;
    }

    /**
     * Get responsive properties list.
     *
     * @return array List of responsive property names.
     */
    public function get_responsive_properties() {
        return self::$responsive_properties;
    }

    /**
     * Get unit properties list.
     *
     * @return array List of unit property names.
     */
    public function get_unit_properties() {
        return self::$unit_properties;
    }

    /**
     * Generate simplified schema for AI context.
     *
     * @return array Simplified schema for AI consumption.
     */
    public function get_ai_schema() {
        $schema = array(
            'elements'          => array(),
            'propertyMappings'  => self::$property_mappings,
            'breakpoints'       => self::$breakpoint_map,
            'responsiveProps'   => self::$responsive_properties,
            'unitProps'         => self::$unit_properties,
        );

        // Generate element-specific schemas.
        foreach ( $this->element_map as $name => $type ) {
            $schema['elements'][ $name ] = $this->get_element_simplified_schema( $name );
        }

        return $schema;
    }

    /**
     * Get simplified schema for a specific element.
     *
     * @param string $element_name Simplified element name.
     * @return array Element schema.
     */
    public function get_element_simplified_schema( $element_name ) {
        $type = $this->get_element_type( $element_name );

        if ( ! $type ) {
            return array();
        }

        $schema = array(
            'type'       => $type,
            'properties' => array(),
        );

        // Define common properties for all elements.
        $common_props = array(
            'customCss' => array(
                'type'        => 'code',
                'language'    => 'css',
                'responsive'  => true,
                'description' => 'Custom CSS. Use %%SELECTOR%% as placeholder.',
            ),
        );

        // Define element-specific properties.
        switch ( $element_name ) {
            case 'Heading':
                $schema['properties'] = array_merge(
                    array(
                        'text'          => array( 'type' => 'string', 'required' => true ),
                        'tag'           => array( 'type' => 'enum', 'values' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), 'default' => 'h2' ),
                        'color'         => array( 'type' => 'color', 'responsive' => true ),
                        'fontSize'      => array( 'type' => 'unit', 'responsive' => true ),
                        'fontWeight'    => array( 'type' => 'enum', 'values' => array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) ),
                        'textAlign'     => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right', 'justify' ), 'responsive' => true ),
                        'lineHeight'    => array( 'type' => 'unit', 'responsive' => true ),
                        'letterSpacing' => array( 'type' => 'unit', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;

            case 'Text':
                $schema['properties'] = array_merge(
                    array(
                        'text'       => array( 'type' => 'string', 'required' => true ),
                        'color'      => array( 'type' => 'color', 'responsive' => true ),
                        'fontSize'   => array( 'type' => 'unit', 'responsive' => true ),
                        'fontWeight' => array( 'type' => 'enum', 'values' => array( '400', '500', '600', '700' ) ),
                        'textAlign'  => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right', 'justify' ), 'responsive' => true ),
                        'lineHeight' => array( 'type' => 'unit', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;

            case 'Button':
                $schema['properties'] = array_merge(
                    array(
                        'text'            => array( 'type' => 'string', 'required' => true ),
                        'link'            => array( 'type' => 'string' ),
                        'background'      => array( 'type' => 'color' ),
                        'color'           => array( 'type' => 'color' ),
                        'fontSize'        => array( 'type' => 'unit', 'responsive' => true ),
                        'fontWeight'      => array( 'type' => 'enum', 'values' => array( '400', '500', '600', '700' ) ),
                        'padding'         => array( 'type' => 'spacing', 'responsive' => true ),
                        'borderRadius'    => array( 'type' => 'unit', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;

            case 'Image':
                $schema['properties'] = array_merge(
                    array(
                        'url'          => array( 'type' => 'string', 'required' => true ),
                        'alt'          => array( 'type' => 'string' ),
                        'width'        => array( 'type' => 'unit', 'responsive' => true ),
                        'height'       => array( 'type' => 'unit', 'responsive' => true ),
                        'borderRadius' => array( 'type' => 'unit', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;

            case 'Section':
            case 'Div':
                $schema['properties'] = array_merge(
                    array(
                        'background'      => array( 'type' => 'color' ),
                        'padding'         => array( 'type' => 'spacing', 'responsive' => true ),
                        'margin'          => array( 'type' => 'spacing', 'responsive' => true ),
                        'display'         => array( 'type' => 'enum', 'values' => array( 'block', 'flex', 'grid', 'none' ), 'responsive' => true ),
                        'flexDirection'   => array( 'type' => 'enum', 'values' => array( 'row', 'column', 'row-reverse', 'column-reverse' ), 'responsive' => true ),
                        'justifyContent'  => array( 'type' => 'enum', 'values' => array( 'flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly' ), 'responsive' => true ),
                        'alignItems'      => array( 'type' => 'enum', 'values' => array( 'flex-start', 'flex-end', 'center', 'stretch', 'baseline' ), 'responsive' => true ),
                        'gap'             => array( 'type' => 'unit', 'responsive' => true ),
                        'gridColumns'     => array( 'type' => 'string', 'responsive' => true, 'description' => 'e.g., "repeat(3, 1fr)"' ),
                        'width'           => array( 'type' => 'unit', 'responsive' => true ),
                        'maxWidth'        => array( 'type' => 'unit', 'responsive' => true ),
                        'minHeight'       => array( 'type' => 'unit', 'responsive' => true ),
                        'borderRadius'    => array( 'type' => 'unit', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;

            case 'Spacer':
                $schema['properties'] = array(
                    'height' => array( 'type' => 'unit', 'responsive' => true, 'required' => true ),
                );
                break;

            case 'HtmlCode':
                $schema['properties'] = array(
                    'html'  => array( 'type' => 'code', 'language' => 'html', 'required' => true ),
                    'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
                );
                break;

            case 'CssCode':
                $schema['properties'] = array(
                    'css'   => array( 'type' => 'code', 'language' => 'css', 'required' => true ),
                    'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
                );
                break;

            case 'PhpCode':
                $schema['properties'] = array(
                    'php'   => array( 'type' => 'code', 'language' => 'php', 'required' => true ),
                    'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
                );
                $schema['permissions'] = array( 'unfiltered_html' );
                break;

            default:
                // Generic container-like element.
                $schema['properties'] = array_merge(
                    array(
                        'background' => array( 'type' => 'color' ),
                        'padding'    => array( 'type' => 'spacing', 'responsive' => true ),
                        'margin'     => array( 'type' => 'spacing', 'responsive' => true ),
                    ),
                    $common_props
                );
                break;
        }

        return $schema;
    }
}
