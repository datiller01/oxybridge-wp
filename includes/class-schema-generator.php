<?php
/**
 * Schema Generator class for Oxybridge.
 *
 * Auto-generates schema.json from registered Breakdance elements.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Schema_Generator
 *
 * Generates accurate schema.json from registered Breakdance elements.
 * Extracts control definitions to document available properties.
 *
 * @since 1.1.0
 */
class Schema_Generator {

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
     * Generate complete schema.
     *
     * @return array Generated schema.
     */
    public function generate() {
        $schema = array(
            '$schema'     => 'https://json-schema.org/draft/2020-12/schema',
            '$id'         => 'oxybridge-ai-schema',
            'version'     => OXYBRIDGE_VERSION . '-simplified',
            'description' => 'Simplified schema for AI-powered Breakdance page generation with property transformation',

            'elements'    => $this->generate_elements_schema(),
            'breakpoints' => $this->generate_breakpoints_schema(),
            'valueTypes'  => $this->generate_value_types_schema(),
            'api'         => $this->generate_api_schema(),
            'examples'    => $this->generate_examples(),
        );

        return $schema;
    }

    /**
     * Generate elements schema.
     *
     * @return array Elements schema.
     */
    private function generate_elements_schema() {
        $elements = array();

        foreach ( $this->schema->get_element_map() as $name => $type ) {
            $element_schema = $this->schema->get_element_simplified_schema( $name );
            $elements[ $name ] = array(
                'type'       => $type,
                'properties' => $element_schema['properties'] ?? array(),
            );

            if ( isset( $element_schema['permissions'] ) ) {
                $elements[ $name ]['permissions'] = $element_schema['permissions'];
            }
        }

        return $elements;
    }

    /**
     * Generate breakpoints schema.
     *
     * @return array Breakpoints schema.
     */
    private function generate_breakpoints_schema() {
        return array(
            'desktop' => array(
                'id'          => 'breakpoint_base',
                'description' => 'Default/desktop breakpoint',
            ),
            'tablet'  => array(
                'id'          => 'breakpoint_tablet_portrait',
                'description' => 'Tablet portrait (768-1023px)',
            ),
            'phone'   => array(
                'id'          => 'breakpoint_phone_portrait',
                'description' => 'Phone portrait (<480px)',
            ),
        );
    }

    /**
     * Generate value types schema.
     *
     * @return array Value types schema.
     */
    private function generate_value_types_schema() {
        return array(
            'string'  => array(
                'description' => 'Plain text string',
                'example'     => 'Hello World',
            ),
            'color'   => array(
                'description' => 'CSS color value',
                'formats'     => array( 'hex', 'rgb', 'rgba', 'hsl', 'hsla', 'var()' ),
                'examples'    => array( '#333333', 'rgb(51,51,51)', 'var(--brand-primary)' ),
            ),
            'unit'    => array(
                'description' => 'Size value with unit',
                'formats'     => array( 'px', 'em', 'rem', '%', 'vw', 'vh' ),
                'examples'    => array( '16px', '1.5em', '100%', 'auto' ),
            ),
            'spacing' => array(
                'description' => 'CSS shorthand spacing',
                'formats'     => array( 'single', 'vertical/horizontal', 'top/horizontal/bottom', 'all four' ),
                'examples'    => array( '20px', '20px 40px', '10px 20px 30px', '10px 20px 30px 40px' ),
            ),
            'enum'    => array(
                'description' => 'One of predefined values',
                'note'        => 'See property definition for valid values',
            ),
            'code'    => array(
                'description' => 'Code string (HTML, CSS, PHP)',
                'note'        => 'Language specified in property definition',
            ),
        );
    }

    /**
     * Generate API schema.
     *
     * @return array API schema.
     */
    private function generate_api_schema() {
        return array(
            'base_url'  => '/wp-json/oxybridge/v1',
            'endpoints' => array(
                'create_page'   => array(
                    'method'      => 'POST',
                    'path'        => '/pages',
                    'description' => 'Create a page with Breakdance design',
                    'body'        => array(
                        'title'          => 'string (required)',
                        'status'         => 'publish|draft (default: draft)',
                        'use_simplified' => 'boolean - Set true to use simplified format',
                        'tree'           => 'object - Page tree structure',
                    ),
                ),
                'transform'     => array(
                    'method'      => 'POST',
                    'path'        => '/ai/transform',
                    'description' => 'Transform simplified format to Breakdance format',
                    'body'        => array(
                        'tree' => 'object - Simplified tree to transform',
                    ),
                ),
                'validate'      => array(
                    'method'      => 'POST',
                    'path'        => '/ai/validate',
                    'description' => 'Validate properties before saving',
                    'body'        => array(
                        'tree' => 'object - Tree to validate',
                    ),
                ),
                'preview_css'   => array(
                    'method'      => 'POST',
                    'path'        => '/ai/preview-css',
                    'description' => 'Preview CSS that would be generated',
                    'body'        => array(
                        'element_type' => 'string - Element type',
                        'properties'   => 'object - Element properties',
                    ),
                ),
                'regenerate_css' => array(
                    'method'      => 'POST',
                    'path'        => '/regenerate-css/{id}',
                    'description' => 'Regenerate CSS for a page',
                ),
            ),
        );
    }

    /**
     * Generate examples.
     *
     * @return array Examples.
     */
    private function generate_examples() {
        return array(
            'heading'             => array(
                'type'     => 'Heading',
                'text'     => 'Welcome to Our Site',
                'tag'      => 'h1',
                'color'    => '#333333',
                'fontSize' => '48px',
                'responsive' => array(
                    'tablet' => array( 'fontSize' => '36px' ),
                    'phone'  => array( 'fontSize' => '28px' ),
                ),
            ),
            'text'                => array(
                'type'       => 'Text',
                'text'       => 'Lorem ipsum dolor sit amet.',
                'color'      => '#666666',
                'fontSize'   => '16px',
                'lineHeight' => '1.6',
            ),
            'button'              => array(
                'type'         => 'Button',
                'text'         => 'Get Started',
                'link'         => '/contact',
                'background'   => '#0066cc',
                'color'        => '#ffffff',
                'padding'      => '15px 40px',
                'borderRadius' => '8px',
            ),
            'section_with_layout' => array(
                'type'           => 'Section',
                'background'     => '#f5f5f5',
                'padding'        => '80px 30px',
                'display'        => 'flex',
                'flexDirection'  => 'column',
                'alignItems'     => 'center',
                'gap'            => '40px',
                'maxWidth'       => '1200px',
                'children'       => array(
                    array(
                        'type'      => 'Heading',
                        'text'      => 'Section Title',
                        'tag'       => 'h2',
                        'color'     => '#333',
                        'textAlign' => 'center',
                    ),
                ),
            ),
            'grid_layout'         => array(
                'type'        => 'Div',
                'display'     => 'grid',
                'gridColumns' => 'repeat(3, 1fr)',
                'gap'         => '30px',
                'responsive'  => array(
                    'tablet' => array( 'gridColumns' => 'repeat(2, 1fr)' ),
                    'phone'  => array( 'gridColumns' => '1fr' ),
                ),
            ),
            'custom_css'          => array(
                'type'      => 'Heading',
                'text'      => 'Styled Heading',
                'color'     => '#333',
                'customCss' => '%%SELECTOR%% { text-shadow: 2px 2px 4px rgba(0,0,0,0.1); }',
            ),
            'css_code_element'    => array(
                'type'  => 'CssCode',
                'css'   => ':root { --brand-color: #0066cc; --accent-color: #ff6b35; }',
                'label' => 'CSS Variables',
            ),
            'html_code_element'   => array(
                'type'  => 'HtmlCode',
                'html'  => '<div class="custom-widget"><p>Custom content</p></div>',
                'label' => 'Custom Widget',
            ),
        );
    }

    /**
     * Save schema to file.
     *
     * @param string $path File path.
     * @return bool True on success, false on failure.
     */
    public function save_to_file( string $path = '' ) {
        if ( empty( $path ) ) {
            $path = OXYBRIDGE_PLUGIN_DIR . '/ai/schema-simplified.json';
        }

        $schema = $this->generate();
        $json = wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        if ( ! $json ) {
            return false;
        }

        // Ensure directory exists.
        $dir = dirname( $path );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        return file_put_contents( $path, $json ) !== false;
    }

    /**
     * Get schema as JSON string.
     *
     * @return string JSON schema.
     */
    public function get_json() {
        return wp_json_encode( $this->generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }
}
