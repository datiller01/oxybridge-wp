<?php
/**
 * JSON Builder Class
 *
 * Provides a fluent interface for programmatically building Oxygen/Breakdance
 * design tree structures. This class allows creating valid design JSON without
 * manually constructing the nested array structures.
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
 * Class JSON_Builder
 *
 * Fluent builder for Oxygen/Breakdance document tree structures.
 *
 * Example usage:
 * ```php
 * $builder = new JSON_Builder();
 * $tree = $builder
 *     ->create_document()
 *     ->add_section()
 *         ->add_container()
 *             ->add_heading( 'Welcome', 1 )
 *             ->add_text( 'This is my content.' )
 *         ->end()
 *     ->end()
 *     ->build();
 * ```
 *
 * @since 1.0.0
 */
class JSON_Builder {

    /**
     * The document tree being built.
     *
     * @var array
     */
    private array $tree = array();

    /**
     * Stack of parent elements for nesting support.
     *
     * @var array
     */
    private array $parent_stack = array();

    /**
     * Current parent element reference.
     *
     * @var array|null
     */
    private ?array $current_parent = null;

    /**
     * Generated element IDs mapped to element references.
     *
     * @var array
     */
    private array $element_map = array();

    /**
     * Element type to class name mapping.
     *
     * @var array
     */
    const ELEMENT_TYPES = array(
        'root'      => 'EssentialElements\\Root',
        'section'   => 'EssentialElements\\Section',
        'container' => 'EssentialElements\\Container',
        'div'       => 'EssentialElements\\Div',
        'columns'   => 'EssentialElements\\Columns',
        'column'    => 'EssentialElements\\Column',
        'heading'   => 'EssentialElements\\Heading',
        'text'      => 'EssentialElements\\Text',
        'richtext'  => 'EssentialElements\\TextEditor',
        'image'     => 'EssentialElements\\Image',
        'button'    => 'EssentialElements\\Button',
        'link'      => 'EssentialElements\\Link',
        'icon'      => 'EssentialElements\\Icon',
        'video'     => 'EssentialElements\\Video',
        'spacer'    => 'EssentialElements\\Spacer',
        'divider'   => 'EssentialElements\\Divider',
    );

    /**
     * Create a new document/tree structure.
     *
     * Initializes a fresh document with a root element. This should be
     * called first before adding any elements.
     *
     * @since 1.0.0
     *
     * @param string $root_type Optional. The type for the root element. Default 'root'.
     * @return self For method chaining.
     */
    public function create_document( string $root_type = 'root' ): self {
        $root_id = $this->generate_element_id();

        $this->tree = array(
            'root' => array(
                'id'       => $root_id,
                'data'     => array(
                    'type' => $this->get_element_type( $root_type ),
                ),
                'children' => array(),
            ),
        );

        // Set root as current parent.
        $this->current_parent = &$this->tree['root'];
        $this->parent_stack   = array();

        $this->element_map[ $root_id ] = &$this->tree['root'];

        return $this;
    }

    /**
     * Add a section element.
     *
     * Sections are top-level container elements. After calling this method,
     * subsequent elements will be added as children of the section until
     * end() is called.
     *
     * @since 1.0.0
     *
     * @param array $properties Optional. Section properties.
     * @param array $styles     Optional. Section styles.
     * @return self For method chaining.
     */
    public function add_section( array $properties = array(), array $styles = array() ): self {
        return $this->add_element( 'section', $properties, $styles, true );
    }

    /**
     * Add a container element.
     *
     * Containers are wrapper elements that can hold other elements.
     * After calling this method, subsequent elements will be added as
     * children of the container until end() is called.
     *
     * @since 1.0.0
     *
     * @param array $properties Optional. Container properties.
     * @param array $styles     Optional. Container styles.
     * @return self For method chaining.
     */
    public function add_container( array $properties = array(), array $styles = array() ): self {
        return $this->add_element( 'container', $properties, $styles, true );
    }

    /**
     * Add a div element.
     *
     * Generic div wrapper element. After calling this method, subsequent
     * elements will be added as children until end() is called.
     *
     * @since 1.0.0
     *
     * @param array $properties Optional. Div properties.
     * @param array $styles     Optional. Div styles.
     * @return self For method chaining.
     */
    public function add_div( array $properties = array(), array $styles = array() ): self {
        return $this->add_element( 'div', $properties, $styles, true );
    }

    /**
     * Add a columns layout element.
     *
     * Creates a columns container for grid layouts. After calling this method,
     * use add_column() to add individual columns, then end() when done.
     *
     * @since 1.0.0
     *
     * @param int   $column_count Optional. Number of columns. Default 2.
     * @param array $properties   Optional. Columns properties.
     * @param array $styles       Optional. Columns styles.
     * @return self For method chaining.
     */
    public function add_columns( int $column_count = 2, array $properties = array(), array $styles = array() ): self {
        $properties['columns'] = $column_count;
        return $this->add_element( 'columns', $properties, $styles, true );
    }

    /**
     * Add a column element.
     *
     * Should be used inside a columns element. After calling this method,
     * subsequent elements will be added to this column until end() is called.
     *
     * @since 1.0.0
     *
     * @param array $properties Optional. Column properties.
     * @param array $styles     Optional. Column styles.
     * @return self For method chaining.
     */
    public function add_column( array $properties = array(), array $styles = array() ): self {
        return $this->add_element( 'column', $properties, $styles, true );
    }

    /**
     * Add a heading element.
     *
     * Creates a heading with the specified text and level.
     *
     * @since 1.0.0
     *
     * @param string $text       The heading text.
     * @param int    $level      Optional. Heading level (1-6). Default 2.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Heading styles.
     * @return self For method chaining.
     */
    public function add_heading( string $text, int $level = 2, array $properties = array(), array $styles = array() ): self {
        $level = max( 1, min( 6, $level ) ); // Clamp to 1-6.

        $properties = array_merge(
            array(
                'content' => array(
                    'text' => $text,
                ),
                'tag'     => 'h' . $level,
            ),
            $properties
        );

        return $this->add_element( 'heading', $properties, $styles, false );
    }

    /**
     * Add a text/paragraph element.
     *
     * Creates a paragraph element with the specified text.
     *
     * @since 1.0.0
     *
     * @param string $text       The text content.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Text styles.
     * @return self For method chaining.
     */
    public function add_text( string $text, array $properties = array(), array $styles = array() ): self {
        $properties = array_merge(
            array(
                'content' => array(
                    'text' => $text,
                ),
            ),
            $properties
        );

        return $this->add_element( 'text', $properties, $styles, false );
    }

    /**
     * Add a rich text element.
     *
     * Creates a rich text element that supports HTML content.
     *
     * @since 1.0.0
     *
     * @param string $html       The HTML content.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Rich text styles.
     * @return self For method chaining.
     */
    public function add_rich_text( string $html, array $properties = array(), array $styles = array() ): self {
        $properties = array_merge(
            array(
                'content' => array(
                    'html' => $html,
                ),
            ),
            $properties
        );

        return $this->add_element( 'richtext', $properties, $styles, false );
    }

    /**
     * Add an image element.
     *
     * Creates an image element with the specified source.
     *
     * @since 1.0.0
     *
     * @param string $src        The image source URL.
     * @param string $alt        Optional. Alt text. Default empty.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Image styles.
     * @return self For method chaining.
     */
    public function add_image( string $src, string $alt = '', array $properties = array(), array $styles = array() ): self {
        $properties = array_merge(
            array(
                'media' => array(
                    'type'     => 'url',
                    'url'      => $src,
                    'alt'      => $alt,
                ),
            ),
            $properties
        );

        return $this->add_element( 'image', $properties, $styles, false );
    }

    /**
     * Add an image by attachment ID.
     *
     * Creates an image element referencing a WordPress media library attachment.
     *
     * @since 1.0.0
     *
     * @param int    $attachment_id The attachment post ID.
     * @param string $size          Optional. Image size. Default 'full'.
     * @param array  $properties    Optional. Additional properties.
     * @param array  $styles        Optional. Image styles.
     * @return self For method chaining.
     */
    public function add_image_attachment( int $attachment_id, string $size = 'full', array $properties = array(), array $styles = array() ): self {
        $properties = array_merge(
            array(
                'media' => array(
                    'type'         => 'attachment',
                    'attachmentId' => $attachment_id,
                    'size'         => $size,
                ),
            ),
            $properties
        );

        return $this->add_element( 'image', $properties, $styles, false );
    }

    /**
     * Add a button element.
     *
     * Creates a button element with the specified text and optional link.
     *
     * @since 1.0.0
     *
     * @param string $text       The button text.
     * @param string $link       Optional. Button link URL.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Button styles.
     * @return self For method chaining.
     */
    public function add_button( string $text, string $link = '', array $properties = array(), array $styles = array() ): self {
        $button_props = array(
            'content' => array(
                'text' => $text,
            ),
        );

        if ( ! empty( $link ) ) {
            $button_props['link'] = array(
                'url'      => $link,
                'target'   => '_self',
                'nofollow' => false,
            );
        }

        $properties = array_merge( $button_props, $properties );

        return $this->add_element( 'button', $properties, $styles, false );
    }

    /**
     * Add a link element.
     *
     * Creates a link wrapper element. After calling this method, subsequent
     * elements will be wrapped in the link until end() is called.
     *
     * @since 1.0.0
     *
     * @param string $url        The link URL.
     * @param string $target     Optional. Link target. Default '_self'.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Link styles.
     * @return self For method chaining.
     */
    public function add_link( string $url, string $target = '_self', array $properties = array(), array $styles = array() ): self {
        $properties = array_merge(
            array(
                'link' => array(
                    'url'    => $url,
                    'target' => $target,
                ),
            ),
            $properties
        );

        return $this->add_element( 'link', $properties, $styles, true );
    }

    /**
     * Add a spacer element.
     *
     * Creates a vertical spacing element.
     *
     * @since 1.0.0
     *
     * @param int    $height     Optional. Spacer height in pixels. Default 40.
     * @param array  $properties Optional. Additional properties.
     * @param array  $styles     Optional. Spacer styles.
     * @return self For method chaining.
     */
    public function add_spacer( int $height = 40, array $properties = array(), array $styles = array() ): self {
        $styles = array_merge(
            array(
                'size' => array(
                    'height' => array(
                        'breakpoint_base' => $height . 'px',
                    ),
                ),
            ),
            $styles
        );

        return $this->add_element( 'spacer', $properties, $styles, false );
    }

    /**
     * Add a divider element.
     *
     * Creates a horizontal divider line.
     *
     * @since 1.0.0
     *
     * @param array $properties Optional. Divider properties.
     * @param array $styles     Optional. Divider styles.
     * @return self For method chaining.
     */
    public function add_divider( array $properties = array(), array $styles = array() ): self {
        return $this->add_element( 'divider', $properties, $styles, false );
    }

    /**
     * Add a generic element.
     *
     * Low-level method to add any element type with full control over
     * properties and styling.
     *
     * @since 1.0.0
     *
     * @param string $type         Element type key.
     * @param array  $properties   Optional. Element properties.
     * @param array  $styles       Optional. Element styles.
     * @param bool   $has_children Optional. Whether this element can have children. Default false.
     * @return self For method chaining.
     */
    public function add_element( string $type, array $properties = array(), array $styles = array(), bool $has_children = false ): self {
        if ( null === $this->current_parent ) {
            $this->create_document();
        }

        $element_id = $this->generate_element_id();

        $element = array(
            'id'   => $element_id,
            'data' => array(
                'type' => $this->get_element_type( $type ),
            ),
        );

        // Add properties if any.
        if ( ! empty( $properties ) ) {
            $element['data']['properties'] = $properties;
        }

        // Add styles if any.
        if ( ! empty( $styles ) ) {
            $element['data']['properties']['styles'] = $styles;
        }

        // Add children array if element supports children.
        if ( $has_children ) {
            $element['children'] = array();
        }

        // Add element to current parent's children.
        $this->current_parent['children'][] = $element;

        // Store reference in map.
        $child_index                       = count( $this->current_parent['children'] ) - 1;
        $this->element_map[ $element_id ] = &$this->current_parent['children'][ $child_index ];

        // If element has children, push current parent to stack and make this element current.
        if ( $has_children ) {
            $this->parent_stack[]  = &$this->current_parent;
            $this->current_parent = &$this->current_parent['children'][ $child_index ];
        }

        return $this;
    }

    /**
     * Add a custom element with raw data.
     *
     * Allows inserting an element with completely custom data structure.
     * Useful for advanced elements or third-party components.
     *
     * @since 1.0.0
     *
     * @param string $type Element type string (full class name or key).
     * @param array  $data Complete data array for the element.
     * @param bool   $has_children Optional. Whether this element can have children. Default false.
     * @return self For method chaining.
     */
    public function add_raw_element( string $type, array $data, bool $has_children = false ): self {
        if ( null === $this->current_parent ) {
            $this->create_document();
        }

        $element_id = $this->generate_element_id();

        // Merge type into data if not present.
        if ( ! isset( $data['type'] ) ) {
            $data['type'] = $type;
        }

        $element = array(
            'id'   => $element_id,
            'data' => $data,
        );

        if ( $has_children ) {
            $element['children'] = array();
        }

        // Add element to current parent's children.
        $this->current_parent['children'][] = $element;

        // Store reference in map.
        $child_index                       = count( $this->current_parent['children'] ) - 1;
        $this->element_map[ $element_id ] = &$this->current_parent['children'][ $child_index ];

        if ( $has_children ) {
            $this->parent_stack[]  = &$this->current_parent;
            $this->current_parent = &$this->current_parent['children'][ $child_index ];
        }

        return $this;
    }

    /**
     * End the current element scope.
     *
     * Returns to the parent element's scope. Use this after adding
     * children to container elements like sections, containers, etc.
     *
     * @since 1.0.0
     *
     * @return self For method chaining.
     */
    public function end(): self {
        if ( ! empty( $this->parent_stack ) ) {
            $this->current_parent = &$this->parent_stack[ count( $this->parent_stack ) - 1 ];
            array_pop( $this->parent_stack );
        }

        return $this;
    }

    /**
     * End all nested scopes and return to root.
     *
     * Useful for quickly returning to the document root after
     * adding deeply nested elements.
     *
     * @since 1.0.0
     *
     * @return self For method chaining.
     */
    public function end_all(): self {
        $this->parent_stack   = array();
        $this->current_parent = &$this->tree['root'];

        return $this;
    }

    /**
     * Set properties on the last added element.
     *
     * @since 1.0.0
     *
     * @param array $properties Properties to set.
     * @return self For method chaining.
     */
    public function with_properties( array $properties ): self {
        $last_element = $this->get_last_element();

        if ( null !== $last_element && isset( $last_element['data'] ) ) {
            if ( ! isset( $last_element['data']['properties'] ) ) {
                $last_element['data']['properties'] = array();
            }
            $last_element['data']['properties'] = array_merge(
                $last_element['data']['properties'],
                $properties
            );
        }

        return $this;
    }

    /**
     * Set styles on the last added element.
     *
     * @since 1.0.0
     *
     * @param array $styles Styles to set.
     * @return self For method chaining.
     */
    public function with_styles( array $styles ): self {
        $last_element = $this->get_last_element();

        if ( null !== $last_element && isset( $last_element['data'] ) ) {
            if ( ! isset( $last_element['data']['properties'] ) ) {
                $last_element['data']['properties'] = array();
            }
            if ( ! isset( $last_element['data']['properties']['styles'] ) ) {
                $last_element['data']['properties']['styles'] = array();
            }
            $last_element['data']['properties']['styles'] = array_merge(
                $last_element['data']['properties']['styles'],
                $styles
            );
        }

        return $this;
    }

    /**
     * Add CSS classes to the last added element.
     *
     * @since 1.0.0
     *
     * @param string|array $classes Class name(s) to add.
     * @return self For method chaining.
     */
    public function with_classes( $classes ): self {
        if ( is_string( $classes ) ) {
            $classes = array_filter( explode( ' ', $classes ) );
        }

        $last_element = $this->get_last_element();

        if ( null !== $last_element && isset( $last_element['data'] ) ) {
            if ( ! isset( $last_element['data']['properties'] ) ) {
                $last_element['data']['properties'] = array();
            }
            if ( ! isset( $last_element['data']['properties']['classes'] ) ) {
                $last_element['data']['properties']['classes'] = array();
            }
            $last_element['data']['properties']['classes'] = array_merge(
                $last_element['data']['properties']['classes'],
                $classes
            );
        }

        return $this;
    }

    /**
     * Set a custom ID on the last added element.
     *
     * @since 1.0.0
     *
     * @param string $custom_id The custom ID to set.
     * @return self For method chaining.
     */
    public function with_id( string $custom_id ): self {
        $last_element = $this->get_last_element();

        if ( null !== $last_element && isset( $last_element['data'] ) ) {
            if ( ! isset( $last_element['data']['properties'] ) ) {
                $last_element['data']['properties'] = array();
            }
            $last_element['data']['properties']['customId'] = sanitize_html_class( $custom_id );
        }

        return $this;
    }

    /**
     * Set responsive visibility on the last added element.
     *
     * @since 1.0.0
     *
     * @param array $visibility {
     *     Visibility settings per breakpoint.
     *
     *     @type bool $desktop Show on desktop. Default true.
     *     @type bool $tablet  Show on tablet. Default true.
     *     @type bool $mobile  Show on mobile. Default true.
     * }
     * @return self For method chaining.
     */
    public function with_visibility( array $visibility ): self {
        $defaults = array(
            'desktop' => true,
            'tablet'  => true,
            'mobile'  => true,
        );

        $visibility = array_merge( $defaults, $visibility );

        $last_element = $this->get_last_element();

        if ( null !== $last_element && isset( $last_element['data'] ) ) {
            if ( ! isset( $last_element['data']['properties'] ) ) {
                $last_element['data']['properties'] = array();
            }
            $last_element['data']['properties']['visibility'] = array(
                'desktop' => (bool) $visibility['desktop'],
                'tablet'  => (bool) $visibility['tablet'],
                'mobile'  => (bool) $visibility['mobile'],
            );
        }

        return $this;
    }

    /**
     * Add margin to the last added element.
     *
     * @since 1.0.0
     *
     * @param string|array $margin Margin value or array of values.
     * @return self For method chaining.
     */
    public function with_margin( $margin ): self {
        return $this->with_spacing( 'margin', $margin );
    }

    /**
     * Add padding to the last added element.
     *
     * @since 1.0.0
     *
     * @param string|array $padding Padding value or array of values.
     * @return self For method chaining.
     */
    public function with_padding( $padding ): self {
        return $this->with_spacing( 'padding', $padding );
    }

    /**
     * Set background color on the last added element.
     *
     * @since 1.0.0
     *
     * @param string $color Background color value.
     * @return self For method chaining.
     */
    public function with_background_color( string $color ): self {
        return $this->with_styles(
            array(
                'background' => array(
                    'color' => array(
                        'breakpoint_base' => $color,
                    ),
                ),
            )
        );
    }

    /**
     * Set text color on the last added element.
     *
     * @since 1.0.0
     *
     * @param string $color Text color value.
     * @return self For method chaining.
     */
    public function with_text_color( string $color ): self {
        return $this->with_styles(
            array(
                'typography' => array(
                    'color' => array(
                        'breakpoint_base' => $color,
                    ),
                ),
            )
        );
    }

    /**
     * Build and return the final tree structure.
     *
     * @since 1.0.0
     *
     * @return array The complete document tree.
     */
    public function build(): array {
        if ( empty( $this->tree ) ) {
            $this->create_document();
        }

        return $this->tree;
    }

    /**
     * Build and return the tree as JSON string.
     *
     * @since 1.0.0
     *
     * @param int $flags Optional. JSON encode flags. Default JSON_PRETTY_PRINT.
     * @return string The tree as JSON string.
     */
    public function build_json( int $flags = JSON_PRETTY_PRINT ): string {
        $json = wp_json_encode( $this->build(), $flags );
        return $json !== false ? $json : '{}';
    }

    /**
     * Reset the builder to initial state.
     *
     * @since 1.0.0
     *
     * @return self For method chaining.
     */
    public function reset(): self {
        $this->tree           = array();
        $this->parent_stack   = array();
        $this->current_parent = null;
        $this->element_map    = array();

        return $this;
    }

    /**
     * Get an element by ID.
     *
     * @since 1.0.0
     *
     * @param string $element_id The element ID.
     * @return array|null The element or null if not found.
     */
    public function get_element( string $element_id ): ?array {
        return isset( $this->element_map[ $element_id ] ) ? $this->element_map[ $element_id ] : null;
    }

    /**
     * Get all element IDs in the current tree.
     *
     * @since 1.0.0
     *
     * @return array Array of element IDs.
     */
    public function get_element_ids(): array {
        return array_keys( $this->element_map );
    }

    /**
     * Get the count of elements in the current tree.
     *
     * @since 1.0.0
     *
     * @return int Element count.
     */
    public function get_element_count(): int {
        // Subtract 1 for root element.
        return max( 0, count( $this->element_map ) - 1 );
    }

    /**
     * Import an existing tree for modification.
     *
     * @since 1.0.0
     *
     * @param array $tree The tree structure to import.
     * @return self For method chaining.
     */
    public function import( array $tree ): self {
        $this->tree = $tree;

        if ( isset( $this->tree['root'] ) ) {
            $this->current_parent = &$this->tree['root'];
            $this->rebuild_element_map( $this->tree['root'] );
        }

        $this->parent_stack = array();

        return $this;
    }

    /**
     * Clone an existing element into the current position.
     *
     * @since 1.0.0
     *
     * @param array $element The element to clone.
     * @param bool  $deep    Optional. Whether to clone children. Default true.
     * @return self For method chaining.
     */
    public function clone_element( array $element, bool $deep = true ): self {
        $cloned = $this->deep_clone_element( $element, $deep );

        if ( null === $this->current_parent ) {
            $this->create_document();
        }

        $this->current_parent['children'][] = $cloned;

        // Store reference in map.
        $child_index = count( $this->current_parent['children'] ) - 1;
        $this->rebuild_element_map( $this->current_parent['children'][ $child_index ] );

        return $this;
    }

    /**
     * Generate a unique element ID.
     *
     * @since 1.0.0
     *
     * @return string The generated element ID.
     */
    public function generate_element_id(): string {
        return 'el-' . bin2hex( random_bytes( 8 ) );
    }

    /**
     * Get the full element type string.
     *
     * @since 1.0.0
     *
     * @param string $type Element type key or full type string.
     * @return string The full element type string.
     */
    private function get_element_type( string $type ): string {
        // If it looks like a full type string, return as-is.
        if ( strpos( $type, '\\' ) !== false ) {
            return $type;
        }

        $type_lower = strtolower( $type );

        return isset( self::ELEMENT_TYPES[ $type_lower ] )
            ? self::ELEMENT_TYPES[ $type_lower ]
            : $type;
    }

    /**
     * Get a reference to the last added element.
     *
     * @since 1.0.0
     *
     * @return array|null Reference to the last element or null.
     */
    private function &get_last_element(): ?array {
        $null = null;

        if ( null === $this->current_parent ) {
            return $null;
        }

        // If we pushed to parent stack, the last element is the current parent.
        if ( ! empty( $this->parent_stack ) ) {
            return $this->current_parent;
        }

        // Otherwise, it's the last child of the current parent.
        $children_count = count( $this->current_parent['children'] );

        if ( $children_count > 0 ) {
            return $this->current_parent['children'][ $children_count - 1 ];
        }

        return $null;
    }

    /**
     * Rebuild the element map from a tree node.
     *
     * @since 1.0.0
     *
     * @param array $element The element to map.
     * @return void
     */
    private function rebuild_element_map( array &$element ): void {
        if ( isset( $element['id'] ) ) {
            $this->element_map[ $element['id'] ] = &$element;
        }

        if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
            foreach ( $element['children'] as &$child ) {
                $this->rebuild_element_map( $child );
            }
        }
    }

    /**
     * Deep clone an element with new IDs.
     *
     * @since 1.0.0
     *
     * @param array $element The element to clone.
     * @param bool  $deep    Whether to clone children.
     * @return array The cloned element.
     */
    private function deep_clone_element( array $element, bool $deep = true ): array {
        $cloned = $element;

        // Generate new ID.
        $cloned['id'] = $this->generate_element_id();

        // Clone children if deep and they exist.
        if ( $deep && isset( $cloned['children'] ) && is_array( $cloned['children'] ) ) {
            $cloned['children'] = array_map(
                function ( $child ) use ( $deep ) {
                    return is_array( $child ) ? $this->deep_clone_element( $child, $deep ) : $child;
                },
                $cloned['children']
            );
        } elseif ( ! $deep ) {
            $cloned['children'] = array();
        }

        return $cloned;
    }

    /**
     * Add spacing (margin or padding) to the last element.
     *
     * @since 1.0.0
     *
     * @param string       $property 'margin' or 'padding'.
     * @param string|array $value    Spacing value.
     * @return self For method chaining.
     */
    private function with_spacing( string $property, $value ): self {
        $spacing = array();

        if ( is_string( $value ) ) {
            // Parse CSS shorthand.
            $parts = array_filter( explode( ' ', trim( $value ) ) );
            $count = count( $parts );

            if ( $count === 1 ) {
                $spacing = array(
                    'top'    => $parts[0],
                    'right'  => $parts[0],
                    'bottom' => $parts[0],
                    'left'   => $parts[0],
                );
            } elseif ( $count === 2 ) {
                $spacing = array(
                    'top'    => $parts[0],
                    'right'  => $parts[1],
                    'bottom' => $parts[0],
                    'left'   => $parts[1],
                );
            } elseif ( $count === 3 ) {
                $spacing = array(
                    'top'    => $parts[0],
                    'right'  => $parts[1],
                    'bottom' => $parts[2],
                    'left'   => $parts[1],
                );
            } elseif ( $count >= 4 ) {
                $spacing = array(
                    'top'    => $parts[0],
                    'right'  => $parts[1],
                    'bottom' => $parts[2],
                    'left'   => $parts[3],
                );
            }
        } elseif ( is_array( $value ) ) {
            $spacing = $value;
        }

        // Format for Breakdance/Oxygen styles.
        $formatted = array();
        foreach ( $spacing as $side => $val ) {
            $formatted[ $side ] = array(
                'breakpoint_base' => $val,
            );
        }

        return $this->with_styles(
            array(
                'spacing' => array(
                    $property => $formatted,
                ),
            )
        );
    }
}
