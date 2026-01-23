<?php
/**
 * Oxygen Data Access Class
 *
 * Provides data access methods for Oxygen Builder templates using the
 * Breakdance\Data namespace API. This class wraps the underlying data
 * layer to provide a clean interface for REST API integration.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Import Breakdance\Data functions.
use function Breakdance\Data\get_tree;
use function Breakdance\Data\is_valid_tree;
use function Breakdance\Data\get_meta;
use function Breakdance\Data\get_global_option;
use function Breakdance\Data\get_tree_elements;
use function Breakdance\BreakdanceOxygen\Strings\__bdox;

/**
 * Class Oxygen_Data
 *
 * Data access layer for Oxygen Builder templates.
 *
 * @since 1.0.0
 */
class Oxygen_Data {

    /**
     * Post type for Oxygen templates.
     *
     * @var string
     */
    const TEMPLATE_POST_TYPE = 'ct_template';

    /**
     * Get Oxygen templates with optional filtering.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Query arguments.
     *
     *     @type int    $per_page  Number of templates to return. Default 20.
     *     @type int    $page      Page number for pagination. Default 1.
     *     @type string $search    Search term to filter by title. Default empty.
     *     @type string $orderby   Field to order by. Default 'modified'.
     *     @type string $order     Sort order (ASC|DESC). Default 'DESC'.
     * }
     * @return array {
     *     Response array.
     *
     *     @type array  $templates Array of template data.
     *     @type int    $total     Total number of templates.
     *     @type int    $pages     Total number of pages.
     * }
     */
    public function get_templates( array $args = array() ): array {
        $defaults = array(
            'per_page' => 20,
            'page'     => 1,
            'search'   => '',
            'orderby'  => 'modified',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'post_type'      => self::TEMPLATE_POST_TYPE,
            'posts_per_page' => absint( $args['per_page'] ),
            'paged'          => absint( $args['page'] ),
            'post_status'    => 'publish',
            'orderby'        => sanitize_key( $args['orderby'] ),
            'order'          => in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true )
                ? strtoupper( $args['order'] )
                : 'DESC',
        );

        // Add search if provided.
        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }

        $query = new \WP_Query( $query_args );

        $templates = array();
        foreach ( $query->posts as $post ) {
            $templates[] = $this->format_template_summary( $post );
        }

        return array(
            'templates' => $templates,
            'total'     => (int) $query->found_posts,
            'pages'     => (int) $query->max_num_pages,
        );
    }

    /**
     * Get a single template by ID.
     *
     * @since 1.0.0
     *
     * @param int $template_id The template post ID.
     * @return array|null Template data or null if not found.
     */
    public function get_template( int $template_id ): ?array {
        $post = get_post( $template_id );

        if ( ! $post || $post->post_type !== self::TEMPLATE_POST_TYPE ) {
            return null;
        }

        return $this->format_template_full( $post );
    }

    /**
     * Get a template by slug.
     *
     * @since 1.0.0
     *
     * @param string $slug The template slug (post_name).
     * @return array|null Template data or null if not found.
     */
    public function get_template_by_slug( string $slug ): ?array {
        $posts = get_posts(
            array(
                'post_type'   => self::TEMPLATE_POST_TYPE,
                'name'        => sanitize_title( $slug ),
                'post_status' => 'publish',
                'numberposts' => 1,
            )
        );

        if ( empty( $posts ) ) {
            return null;
        }

        return $this->format_template_full( $posts[0] );
    }

    /**
     * Get the document tree for a template.
     *
     * Uses the Breakdance\Data\get_tree() function to retrieve
     * the JSON tree structure of the template.
     *
     * @since 1.0.0
     *
     * @param int $template_id The template post ID.
     * @return array|false Tree structure or false if not found.
     */
    public function get_template_tree( int $template_id ) {
        // Check if Breakdance get_tree function exists.
        if ( ! function_exists( 'Breakdance\Data\get_tree' ) ) {
            return $this->get_template_tree_fallback( $template_id );
        }

        return get_tree( $template_id );
    }

    /**
     * Fallback method to get template tree when Breakdance functions are unavailable.
     *
     * @since 1.0.0
     *
     * @param int $template_id The template post ID.
     * @return array|false Tree structure or false if not found.
     */
    private function get_template_tree_fallback( int $template_id ) {
        // Try Oxygen 6 (Breakdance-based) storage.
        $meta_prefix = $this->get_meta_prefix();
        $tree_data   = get_post_meta( $template_id, $meta_prefix . 'data', true );

        if ( ! empty( $tree_data ) ) {
            $decoded = json_decode( $tree_data, true );
            if ( is_array( $decoded ) && isset( $decoded['tree_json_string'] ) ) {
                $tree = json_decode( $decoded['tree_json_string'], true );
                if ( $this->validate_tree( $tree ) ) {
                    return $tree;
                }
            }
        }

        // Try classic Oxygen storage (ct_builder_json).
        $builder_json = get_post_meta( $template_id, 'ct_builder_json', true );
        if ( ! empty( $builder_json ) ) {
            $tree = json_decode( $builder_json, true );
            if ( is_array( $tree ) ) {
                return $tree;
            }
        }

        return false;
    }

    /**
     * Validate a tree structure.
     *
     * @since 1.0.0
     *
     * @param mixed $tree The tree to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate_tree( $tree ): bool {
        // Use Breakdance validator if available.
        if ( function_exists( 'Breakdance\Data\is_valid_tree' ) ) {
            return is_valid_tree( $tree );
        }

        // Fallback validation.
        if ( ! is_array( $tree ) ) {
            return false;
        }

        if ( ! array_key_exists( 'root', $tree ) ) {
            return false;
        }

        if ( ! is_array( $tree['root'] ) ) {
            return false;
        }

        $required_keys = array( 'id', 'data', 'children' );
        foreach ( $required_keys as $key ) {
            if ( ! array_key_exists( $key, $tree['root'] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get flattened list of elements from a template.
     *
     * @since 1.0.0
     *
     * @param int    $template_id  The template post ID.
     * @param string $element_type Optional. Filter by element type.
     * @return array Array of elements with metadata.
     */
    public function get_template_elements( int $template_id, string $element_type = '' ): array {
        $tree = $this->get_template_tree( $template_id );

        if ( $tree === false || ! isset( $tree['root']['children'] ) ) {
            return array();
        }

        $elements = $this->extract_elements( $tree['root']['children'] );

        // Filter by element type if specified.
        if ( ! empty( $element_type ) ) {
            $elements = array_filter(
                $elements,
                function ( $element ) use ( $element_type ) {
                    return isset( $element['type'] ) &&
                           stripos( $element['type'], $element_type ) !== false;
                }
            );
            $elements = array_values( $elements ); // Re-index array.
        }

        return $elements;
    }

    /**
     * Recursively extract elements from tree children.
     *
     * @since 1.0.0
     *
     * @param array  $children The children array from tree.
     * @param string $path     Current hierarchy path.
     * @param int    $depth    Current depth level.
     * @return array Flattened array of elements.
     */
    private function extract_elements( array $children, string $path = '', int $depth = 0 ): array {
        $elements = array();

        foreach ( $children as $index => $child ) {
            $element_path = $path ? "{$path}/{$index}" : (string) $index;

            $element = array(
                'id'         => $child['id'] ?? '',
                'type'       => $child['data']['type'] ?? 'unknown',
                'path'       => $element_path,
                'depth'      => $depth,
                'properties' => $child['data']['properties'] ?? array(),
            );

            $elements[] = $element;

            // Recursively process children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $child_elements = $this->extract_elements(
                    $child['children'],
                    $element_path,
                    $depth + 1
                );
                $elements       = array_merge( $elements, $child_elements );
            }
        }

        return $elements;
    }

    /**
     * Get unique element types used in a template.
     *
     * @since 1.0.0
     *
     * @param int $template_id The template post ID.
     * @return array Array of unique element type slugs.
     */
    public function get_element_types( int $template_id ): array {
        $tree = $this->get_template_tree( $template_id );

        if ( $tree === false || ! isset( $tree['root']['children'] ) ) {
            return array();
        }

        // Use Breakdance function if available.
        if ( function_exists( 'Breakdance\Data\get_tree_elements' ) ) {
            return array_unique( get_tree_elements( $tree['root']['children'] ) );
        }

        // Fallback: extract types manually.
        $elements = $this->get_template_elements( $template_id );
        $types    = array_column( $elements, 'type' );

        return array_unique( $types );
    }

    /**
     * Get global styles/settings.
     *
     * @since 1.0.0
     *
     * @param string $category Optional. Category to retrieve (colors, fonts, spacing, all).
     * @return array Global styles data.
     */
    public function get_global_styles( string $category = 'all' ): array {
        $global_settings = $this->get_global_settings();

        if ( $category === 'all' ) {
            return $this->format_global_styles( $global_settings );
        }

        $styles = $this->format_global_styles( $global_settings );

        return isset( $styles[ $category ] ) ? array( $category => $styles[ $category ] ) : array();
    }

    /**
     * Get raw global settings.
     *
     * @since 1.0.0
     *
     * @return array Global settings array.
     */
    public function get_global_settings(): array {
        // Try Breakdance function first.
        if ( function_exists( 'Breakdance\Data\get_global_option' ) ) {
            $settings = get_global_option( 'global_settings_json_string' );
            if ( is_string( $settings ) ) {
                $decoded = json_decode( $settings, true );
                return is_array( $decoded ) ? $decoded : array();
            }
            return is_array( $settings ) ? $settings : array();
        }

        // Fallback to direct option access.
        $option_prefix = $this->get_option_prefix();
        $settings      = get_option( $option_prefix . 'global_settings_json_string' );

        if ( is_string( $settings ) ) {
            $decoded = json_decode( $settings, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $settings ) ? $settings : array();
    }

    /**
     * Get design variables.
     *
     * @since 1.0.0
     *
     * @return array Design variables data.
     */
    public function get_variables(): array {
        // Try Breakdance function first.
        if ( function_exists( 'Breakdance\Data\get_global_option' ) ) {
            $variables = get_global_option( 'variables_json_string' );
            if ( is_string( $variables ) ) {
                $decoded = json_decode( $variables, true );
                return is_array( $decoded ) ? $decoded : array();
            }
            return is_array( $variables ) ? $variables : array();
        }

        // Fallback to direct option access.
        $option_prefix = $this->get_option_prefix();
        $variables     = get_option( $option_prefix . 'variables_json_string' );

        if ( is_string( $variables ) ) {
            $decoded = json_decode( $variables, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $variables ) ? $variables : array();
    }

    /**
     * Get CSS classes/selectors.
     *
     * @since 1.0.0
     *
     * @return array CSS selectors data.
     */
    public function get_selectors(): array {
        // Try Breakdance function first.
        if ( function_exists( 'Breakdance\Data\get_global_option' ) ) {
            $selectors = get_global_option( 'breakdance_classes_json_string' );
            if ( is_string( $selectors ) ) {
                $decoded = json_decode( $selectors, true );
                return is_array( $decoded ) ? $decoded : array();
            }
            return is_array( $selectors ) ? $selectors : array();
        }

        // Fallback to direct option access.
        $option_prefix = $this->get_option_prefix();
        $selectors     = get_option( $option_prefix . 'breakdance_classes_json_string' );

        if ( is_string( $selectors ) ) {
            $decoded = json_decode( $selectors, true );
            return is_array( $decoded ) ? $decoded : array();
        }

        return is_array( $selectors ) ? $selectors : array();
    }

    /**
     * Search for elements across all templates.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Search arguments.
     *
     *     @type string $query        Search term.
     *     @type string $element_type Filter by element type.
     *     @type string $property     Filter by property name containing query.
     *     @type int    $limit        Maximum results to return. Default 50.
     * }
     * @return array Array of matching elements with template context.
     */
    public function search_elements( array $args ): array {
        $defaults = array(
            'query'        => '',
            'element_type' => '',
            'property'     => '',
            'limit'        => 50,
        );

        $args    = wp_parse_args( $args, $defaults );
        $results = array();

        // Get all templates.
        $templates = $this->get_templates(
            array(
                'per_page' => -1,
            )
        );

        foreach ( $templates['templates'] as $template ) {
            $elements = $this->get_template_elements( $template['id'], $args['element_type'] );

            foreach ( $elements as $element ) {
                // Check if element matches search criteria.
                if ( $this->element_matches_search( $element, $args ) ) {
                    $results[] = array(
                        'template_id'    => $template['id'],
                        'template_title' => $template['title'],
                        'element'        => $element,
                    );

                    if ( count( $results ) >= $args['limit'] ) {
                        break 2; // Break both loops.
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Check if an element matches search criteria.
     *
     * @since 1.0.0
     *
     * @param array $element The element to check.
     * @param array $args    Search arguments.
     * @return bool True if element matches, false otherwise.
     */
    private function element_matches_search( array $element, array $args ): bool {
        // If no query, just check element type filter.
        if ( empty( $args['query'] ) ) {
            return true;
        }

        $query = strtolower( $args['query'] );

        // Search in element type.
        if ( isset( $element['type'] ) && stripos( $element['type'], $query ) !== false ) {
            return true;
        }

        // Search in properties.
        if ( ! empty( $args['property'] ) && isset( $element['properties'] ) ) {
            $property_name = $args['property'];
            if ( isset( $element['properties'][ $property_name ] ) ) {
                $property_value = $element['properties'][ $property_name ];
                if ( is_string( $property_value ) && stripos( $property_value, $query ) !== false ) {
                    return true;
                }
            }
        } elseif ( isset( $element['properties'] ) ) {
            // Search all properties.
            $json_properties = wp_json_encode( $element['properties'] );
            if ( $json_properties && stripos( $json_properties, $query ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format template post to summary array.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post The template post object.
     * @return array Formatted template summary.
     */
    private function format_template_summary( \WP_Post $post ): array {
        $element_count = 0;
        $tree          = $this->get_template_tree( $post->ID );

        if ( $tree !== false && isset( $tree['root']['children'] ) ) {
            $elements      = $this->extract_elements( $tree['root']['children'] );
            $element_count = count( $elements );
        }

        return array(
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'slug'          => $post->post_name,
            'modified'      => $post->post_modified_gmt . 'Z',
            'element_count' => $element_count,
        );
    }

    /**
     * Format template post to full array with JSON structure.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post The template post object.
     * @return array Formatted template with full data.
     */
    private function format_template_full( \WP_Post $post ): array {
        $tree     = $this->get_template_tree( $post->ID );
        $elements = array();

        if ( $tree !== false && isset( $tree['root']['children'] ) ) {
            $elements = $this->extract_elements( $tree['root']['children'] );
        }

        return array(
            'id'            => $post->ID,
            'title'         => $post->post_title,
            'slug'          => $post->post_name,
            'modified'      => $post->post_modified_gmt . 'Z',
            'json'          => $tree !== false ? $tree : new \stdClass(),
            'elements'      => $elements,
            'element_count' => count( $elements ),
            'element_types' => $this->get_element_types( $post->ID ),
        );
    }

    /**
     * Format global settings into categorized styles.
     *
     * @since 1.0.0
     *
     * @param array $settings Raw global settings.
     * @return array Formatted styles by category.
     */
    private function format_global_styles( array $settings ): array {
        $styles = array(
            'colors'  => array(),
            'fonts'   => array(),
            'spacing' => array(),
            'other'   => array(),
        );

        // Extract color settings.
        if ( isset( $settings['colors'] ) ) {
            $styles['colors'] = $settings['colors'];
        }

        // Extract typography/font settings.
        if ( isset( $settings['typography'] ) ) {
            $styles['fonts'] = $settings['typography'];
        }

        // Extract spacing settings.
        if ( isset( $settings['spacing'] ) ) {
            $styles['spacing'] = $settings['spacing'];
        }

        // Add design variables as additional style info.
        $variables = $this->get_variables();
        if ( ! empty( $variables ) ) {
            $styles['variables'] = $variables;
        }

        // Add any other top-level settings.
        $excluded_keys = array( 'colors', 'typography', 'spacing' );
        foreach ( $settings as $key => $value ) {
            if ( ! in_array( $key, $excluded_keys, true ) ) {
                $styles['other'][ $key ] = $value;
            }
        }

        return $styles;
    }

    /**
     * Get the meta prefix for Oxygen/Breakdance data.
     *
     * @since 1.0.0
     *
     * @return string The meta prefix.
     */
    private function get_meta_prefix(): string {
        // Try to get prefix from Breakdance/Oxygen.
        // Note: Oxygen uses '_meta_prefix' (with underscore) for post meta keys.
        if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
            $prefix = __bdox( '_meta_prefix' );
            if ( is_string( $prefix ) ) {
                return $prefix;
            }
        }

        // Check for Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return '_oxygen_';
        }

        // Default to Breakdance prefix.
        return '_breakdance_';
    }

    /**
     * Get the option prefix for global settings/options.
     *
     * Options use 'meta_prefix' (without underscore) unlike post meta.
     *
     * @since 1.0.0
     *
     * @return string The option prefix.
     */
    private function get_option_prefix(): string {
        // Try to get prefix from Breakdance/Oxygen.
        // Note: Options use 'meta_prefix' (without underscore).
        if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
            $prefix = __bdox( 'meta_prefix' );
            if ( is_string( $prefix ) ) {
                return $prefix;
            }
        }

        // Check for Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            return 'oxygen_';
        }

        // Default to Breakdance prefix.
        return 'breakdance_';
    }

    /**
     * Count total templates.
     *
     * @since 1.0.0
     *
     * @return int Total number of templates.
     */
    public function count_templates(): int {
        $counts = wp_count_posts( self::TEMPLATE_POST_TYPE );
        return isset( $counts->publish ) ? (int) $counts->publish : 0;
    }

    /**
     * Save document tree to post meta.
     *
     * Saves the Oxygen/Breakdance design tree structure to post meta,
     * using the correct format for the active builder.
     *
     * @since 1.0.0
     *
     * @param int   $post_id The post ID.
     * @param array $tree    The tree structure to save.
     * @return bool True if saved successfully, false otherwise.
     */
    public function save_document_tree( int $post_id, array $tree ): bool {
        // Validate tree structure.
        if ( ! $this->validate_tree( $tree ) ) {
            return false;
        }

        $meta_prefix = $this->get_meta_prefix();

        // Prepare tree data in Breakdance/Oxygen 6 format.
        $tree_json = wp_json_encode( $tree );

        if ( $tree_json === false ) {
            return false;
        }

        $data = array(
            'tree_json_string' => $tree_json,
        );

        // Encode the wrapper data.
        $encoded_data = wp_json_encode( $data );

        if ( $encoded_data === false ) {
            return false;
        }

        // Save to post meta.
        $result = update_post_meta( $post_id, $meta_prefix . 'data', $encoded_data );

        /**
         * Fires after document tree has been saved via Oxygen_Data.
         *
         * @since 1.0.0
         * @param int   $post_id The post ID.
         * @param array $tree    The tree structure that was saved.
         * @param bool  $result  Whether the save was successful.
         */
        do_action( 'oxybridge_oxygen_data_tree_saved', $post_id, $tree, (bool) $result );

        return (bool) $result;
    }

    /**
     * Create a new page with Oxygen design.
     *
     * Creates a WordPress page/post and optionally initializes it
     * with Oxygen/Breakdance design data.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Page creation arguments.
     *
     *     @type string $title       Page title (required).
     *     @type string $status      Post status. Default 'draft'.
     *     @type string $post_type   Post type. Default 'page'.
     *     @type string $slug        Post name/slug.
     *     @type string $content     Post content.
     *     @type int    $parent      Parent post ID.
     *     @type array  $tree        Oxygen design tree.
     *     @type bool   $enable_oxygen Enable Oxygen for this page. Default true.
     * }
     * @return int|\WP_Error The new post ID or WP_Error on failure.
     */
    public function create_page( array $args ) {
        $defaults = array(
            'title'         => '',
            'status'        => 'draft',
            'post_type'     => 'page',
            'slug'          => '',
            'content'       => '',
            'parent'        => 0,
            'tree'          => array(),
            'enable_oxygen' => true,
        );

        $args = wp_parse_args( $args, $defaults );

        // Title is required.
        if ( empty( $args['title'] ) ) {
            return new \WP_Error( 'missing_title', __( 'Page title is required.', 'oxybridge-wp' ) );
        }

        // Validate post type.
        if ( ! post_type_exists( $args['post_type'] ) ) {
            return new \WP_Error(
                'invalid_post_type',
                sprintf( __( 'Post type "%s" does not exist.', 'oxybridge-wp' ), $args['post_type'] )
            );
        }

        // Build post data.
        $post_data = array(
            'post_title'  => sanitize_text_field( $args['title'] ),
            'post_status' => sanitize_key( $args['status'] ),
            'post_type'   => sanitize_key( $args['post_type'] ),
        );

        if ( ! empty( $args['slug'] ) ) {
            $post_data['post_name'] = sanitize_title( $args['slug'] );
        }

        if ( ! empty( $args['content'] ) ) {
            $post_data['post_content'] = wp_kses_post( $args['content'] );
        }

        if ( $args['parent'] > 0 ) {
            $post_data['post_parent'] = absint( $args['parent'] );
        }

        // Insert the post.
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Handle Oxygen design data.
        if ( ! empty( $args['tree'] ) ) {
            $this->save_document_tree( $post_id, $args['tree'] );
        } elseif ( $args['enable_oxygen'] ) {
            // Create empty tree for Oxygen builder.
            $empty_tree = $this->create_empty_tree();
            $this->save_document_tree( $post_id, $empty_tree );
        }

        return $post_id;
    }

    /**
     * Create a new Oxygen/Breakdance template.
     *
     * Creates a template post of the specified type with optional
     * Oxygen/Breakdance design data.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Template creation arguments.
     *
     *     @type string $title         Template title (required).
     *     @type string $template_type Template type slug (header, footer, template, block, part, popup).
     *     @type string $status        Post status. Default 'publish'.
     *     @type string $slug          Post name/slug.
     *     @type array  $tree          Oxygen design tree.
     *     @type bool   $enable_oxygen Enable Oxygen for this template. Default true.
     * }
     * @return int|\WP_Error The new template post ID or WP_Error on failure.
     */
    public function create_template( array $args ) {
        $defaults = array(
            'title'         => '',
            'template_type' => '',
            'status'        => 'publish',
            'slug'          => '',
            'tree'          => array(),
            'enable_oxygen' => true,
        );

        $args = wp_parse_args( $args, $defaults );

        // Title is required.
        if ( empty( $args['title'] ) ) {
            return new \WP_Error( 'missing_title', __( 'Template title is required.', 'oxybridge-wp' ) );
        }

        // Template type is required.
        if ( empty( $args['template_type'] ) ) {
            return new \WP_Error( 'missing_template_type', __( 'Template type is required.', 'oxybridge-wp' ) );
        }

        // Get the post type for this template type.
        $post_type = $this->get_post_type_for_template_type( $args['template_type'] );

        if ( ! $post_type ) {
            return new \WP_Error(
                'invalid_template_type',
                sprintf(
                    /* translators: %s: template type */
                    __( 'Invalid template type "%s".', 'oxybridge-wp' ),
                    $args['template_type']
                )
            );
        }

        // Validate post type exists.
        if ( ! post_type_exists( $post_type ) ) {
            return new \WP_Error(
                'post_type_not_available',
                sprintf(
                    /* translators: %s: post type */
                    __( 'Template post type "%s" is not registered.', 'oxybridge-wp' ),
                    $post_type
                )
            );
        }

        // Build post data.
        $post_data = array(
            'post_title'  => sanitize_text_field( $args['title'] ),
            'post_status' => sanitize_key( $args['status'] ),
            'post_type'   => $post_type,
        );

        if ( ! empty( $args['slug'] ) ) {
            $post_data['post_name'] = sanitize_title( $args['slug'] );
        }

        // Insert the post.
        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Handle Oxygen design data.
        if ( ! empty( $args['tree'] ) ) {
            $this->save_document_tree( $post_id, $args['tree'] );
        } elseif ( $args['enable_oxygen'] ) {
            // Create empty tree for Oxygen builder.
            $empty_tree = $this->create_empty_tree();
            $this->save_document_tree( $post_id, $empty_tree );
        }

        return $post_id;
    }

    /**
     * Get the WordPress post type for a template type slug.
     *
     * @since 1.0.0
     *
     * @param string $template_type The template type slug.
     * @return string|false The post type or false if invalid.
     */
    private function get_post_type_for_template_type( string $template_type ) {
        $is_oxygen_mode = defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen';

        if ( $is_oxygen_mode ) {
            $type_map = array(
                'template' => 'oxygen_template',
                'header'   => 'oxygen_header',
                'footer'   => 'oxygen_footer',
                'block'    => 'oxygen_block',
                'part'     => 'oxygen_part',
            );
        } else {
            $type_map = array(
                'template' => 'breakdance_template',
                'header'   => 'breakdance_header',
                'footer'   => 'breakdance_footer',
                'block'    => 'breakdance_block',
                'popup'    => 'breakdance_popup',
                'part'     => 'breakdance_part',
            );
        }

        $normalized_type = strtolower( $template_type );

        return isset( $type_map[ $normalized_type ] ) ? $type_map[ $normalized_type ] : false;
    }

    /**
     * Create an empty tree structure for new Oxygen pages.
     *
     * @since 1.0.0
     *
     * @return array The empty tree structure.
     */
    public function create_empty_tree(): array {
        return array(
            'root' => array(
                'id'       => $this->generate_element_id(),
                'data'     => array(
                    'type' => 'root',
                ),
                'children' => array(),
            ),
        );
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
     * Clone a document (page, post, or template) including Oxygen design data.
     *
     * Creates a complete copy of a post with all its Oxygen/Breakdance design
     * data, regenerating element IDs to ensure uniqueness.
     *
     * @since 1.0.0
     *
     * @param int   $source_id The ID of the source post to clone.
     * @param array $args {
     *     Optional. Clone arguments.
     *
     *     @type string $title       Title for the cloned post. Default "Copy of [original title]".
     *     @type string $status      Post status for the clone. Default 'draft'.
     *     @type string $slug        Post slug for the clone. Default auto-generated.
     *     @type bool   $clone_meta  Whether to clone post meta. Default true.
     *     @type bool   $clone_terms Whether to clone taxonomy terms. Default true.
     * }
     * @return int|\WP_Error The new post ID or WP_Error on failure.
     */
    public function clone_document( int $source_id, array $args = array() ) {
        $defaults = array(
            'title'       => '',
            'status'      => 'draft',
            'slug'        => '',
            'clone_meta'  => true,
            'clone_terms' => true,
        );

        $args = wp_parse_args( $args, $defaults );

        // Get the source post.
        $source_post = get_post( $source_id );

        if ( ! $source_post ) {
            return new \WP_Error( 'source_not_found', __( 'Source post not found.', 'oxybridge-wp' ) );
        }

        // Generate title if not provided.
        $title = ! empty( $args['title'] )
            ? $args['title']
            /* translators: %s: original post title */
            : sprintf( __( 'Copy of %s', 'oxybridge-wp' ), $source_post->post_title );

        // Build new post data.
        $new_post_data = array(
            'post_title'   => sanitize_text_field( $title ),
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => sanitize_key( $args['status'] ),
            'post_type'    => $source_post->post_type,
            'post_author'  => get_current_user_id(),
            'post_parent'  => $source_post->post_parent,
            'menu_order'   => $source_post->menu_order,
        );

        if ( ! empty( $args['slug'] ) ) {
            $new_post_data['post_name'] = sanitize_title( $args['slug'] );
        }

        // Insert the new post.
        $new_post_id = wp_insert_post( $new_post_data, true );

        if ( is_wp_error( $new_post_id ) ) {
            return $new_post_id;
        }

        // Clone post meta if requested.
        if ( $args['clone_meta'] ) {
            $this->clone_post_meta( $source_id, $new_post_id );
        }

        // Clone taxonomy terms if requested.
        if ( $args['clone_terms'] ) {
            $this->clone_post_terms( $source_id, $new_post_id );
        }

        /**
         * Fires after a document has been cloned via Oxygen_Data.
         *
         * @since 1.0.0
         * @param int   $new_post_id The newly created post ID.
         * @param int   $source_id   The source post ID.
         * @param array $args        The clone arguments.
         */
        do_action( 'oxybridge_oxygen_data_document_cloned', $new_post_id, $source_id, $args );

        return $new_post_id;
    }

    /**
     * Clone post meta from one post to another.
     *
     * Copies all post meta from the source post to the target post,
     * with special handling for Oxygen/Breakdance design data to
     * regenerate element IDs.
     *
     * @since 1.0.0
     *
     * @param int $source_id The source post ID.
     * @param int $target_id The target post ID.
     * @return bool True on success, false on failure.
     */
    public function clone_post_meta( int $source_id, int $target_id ): bool {
        $source_meta = get_post_meta( $source_id );

        if ( ! is_array( $source_meta ) ) {
            return false;
        }

        $meta_prefix = $this->get_meta_prefix();

        foreach ( $source_meta as $meta_key => $meta_values ) {
            // Skip internal WordPress meta.
            if ( strpos( $meta_key, '_edit_' ) === 0 ) {
                continue;
            }

            foreach ( $meta_values as $meta_value ) {
                $meta_value = maybe_unserialize( $meta_value );

                // For Oxygen/Breakdance tree data, regenerate element IDs.
                if ( $meta_key === $meta_prefix . 'data' ) {
                    $meta_value = $this->clone_tree_data( $meta_value );
                }

                add_post_meta( $target_id, $meta_key, $meta_value );
            }
        }

        return true;
    }

    /**
     * Clone taxonomy terms from one post to another.
     *
     * Copies all taxonomy term associations from the source post
     * to the target post.
     *
     * @since 1.0.0
     *
     * @param int $source_id The source post ID.
     * @param int $target_id The target post ID.
     * @return bool True on success, false on failure.
     */
    public function clone_post_terms( int $source_id, int $target_id ): bool {
        $source_post = get_post( $source_id );

        if ( ! $source_post ) {
            return false;
        }

        $taxonomies = get_object_taxonomies( $source_post->post_type );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $target_id, $terms, $taxonomy );
            }
        }

        return true;
    }

    /**
     * Clone tree data with regenerated element IDs.
     *
     * Decodes the tree data, regenerates all element IDs to ensure
     * uniqueness, and re-encodes the data for storage.
     *
     * @since 1.0.0
     *
     * @param string|array $tree_data The tree data (JSON string or decoded array).
     * @return string|array The cloned tree data with new IDs.
     */
    public function clone_tree_data( $tree_data ) {
        // Decode if it's a JSON string.
        $decoded = is_string( $tree_data ) ? json_decode( $tree_data, true ) : $tree_data;

        if ( ! is_array( $decoded ) ) {
            return $tree_data;
        }

        // Handle Breakdance/Oxygen 6 format with tree_json_string.
        if ( isset( $decoded['tree_json_string'] ) ) {
            $tree = json_decode( $decoded['tree_json_string'], true );

            if ( is_array( $tree ) ) {
                $tree = $this->regenerate_element_ids( $tree );
                $decoded['tree_json_string'] = wp_json_encode( $tree );

                return is_string( $tree_data ) ? wp_json_encode( $decoded ) : $decoded;
            }
        }

        // If no tree_json_string, try to regenerate IDs on the decoded data directly.
        $decoded = $this->regenerate_element_ids( $decoded );

        return is_string( $tree_data ) ? wp_json_encode( $decoded ) : $decoded;
    }

    /**
     * Recursively regenerate element IDs in a tree structure.
     *
     * Creates new unique IDs for all elements in the tree to ensure
     * the cloned content has no ID conflicts with the original.
     *
     * @since 1.0.0
     *
     * @param array $tree The tree or element array.
     * @return array The tree with regenerated IDs.
     */
    public function regenerate_element_ids( array $tree ): array {
        // Regenerate ID if present.
        if ( isset( $tree['id'] ) && is_string( $tree['id'] ) ) {
            $tree['id'] = $this->generate_element_id();
        }

        // Process root element.
        if ( isset( $tree['root'] ) && is_array( $tree['root'] ) ) {
            $tree['root'] = $this->regenerate_element_ids( $tree['root'] );
        }

        // Process children recursively.
        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            foreach ( $tree['children'] as $index => $child ) {
                if ( is_array( $child ) ) {
                    $tree['children'][ $index ] = $this->regenerate_element_ids( $child );
                }
            }
        }

        return $tree;
    }

    /**
     * Render a document/template to HTML.
     *
     * Uses Breakdance's rendering system if available, otherwise falls back
     * to a basic tree-to-HTML conversion.
     *
     * @since 1.0.0
     *
     * @param int  $post_id     The post ID to render.
     * @param bool $include_css Whether to include inline CSS.
     * @return array {
     *     Render result array.
     *
     *     @type string $html        The rendered HTML content.
     *     @type string $css         The CSS content (if requested).
     *     @type bool   $has_content Whether content was rendered.
     *     @type string $method      The render method used ('breakdance', 'fallback').
     * }
     */
    public function render_document_to_html( int $post_id, bool $include_css = false ): array {
        $result = array(
            'html'        => '',
            'css'         => '',
            'has_content' => false,
            'method'      => 'fallback',
        );

        // Try using Breakdance's render function if available.
        if ( function_exists( '\Breakdance\Render\render' ) ) {
            try {
                // Set up the post context for rendering.
                global $post;
                $original_post = $post;
                $post          = get_post( $post_id );
                setup_postdata( $post );

                // Render using Breakdance.
                $html = \Breakdance\Render\render( $post_id );

                // Restore original post context.
                $post = $original_post;
                if ( $original_post ) {
                    setup_postdata( $original_post );
                } else {
                    wp_reset_postdata();
                }

                if ( ! empty( $html ) ) {
                    $result['html']        = $html;
                    $result['has_content'] = true;
                    $result['method']      = 'breakdance';
                }
            } catch ( \Exception $e ) {
                // Rendering failed, continue to fallback.
            }
        }

        // Try alternative render function if first method failed.
        if ( empty( $result['html'] ) && function_exists( '\Breakdance\Render\renderDocument' ) ) {
            try {
                $html = \Breakdance\Render\renderDocument( $post_id );
                if ( ! empty( $html ) ) {
                    $result['html']        = $html;
                    $result['has_content'] = true;
                    $result['method']      = 'breakdance';
                }
            } catch ( \Exception $e ) {
                // Rendering failed, continue to fallback.
            }
        }

        // Fallback: render from tree structure.
        if ( empty( $result['html'] ) ) {
            $tree = $this->get_template_tree( $post_id );

            if ( $tree !== false && isset( $tree['root']['children'] ) ) {
                $html = $this->render_tree_to_html( $tree['root']['children'] );
                if ( ! empty( $html ) ) {
                    $result['html']        = $html;
                    $result['has_content'] = true;
                    $result['method']      = 'fallback';
                }
            }
        }

        // Get CSS if requested.
        if ( $include_css ) {
            $result['css'] = $this->get_document_css( $post_id );
        }

        return $result;
    }

    /**
     * Render tree elements to HTML.
     *
     * Recursively converts tree structure to basic HTML output.
     *
     * @since 1.0.0
     *
     * @param array $elements Array of element nodes.
     * @return string Rendered HTML.
     */
    private function render_tree_to_html( array $elements ): string {
        $html = '';

        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }

            // Determine element type.
            $type = '';
            if ( isset( $element['data']['type'] ) ) {
                $type = $element['data']['type'];
            } elseif ( isset( $element['type'] ) ) {
                $type = $element['type'];
            }

            // Get element ID.
            $element_id = isset( $element['id'] ) ? $element['id'] : 'el-' . bin2hex( random_bytes( 4 ) );

            // Get element properties.
            $properties = isset( $element['data']['properties'] )
                ? $element['data']['properties']
                : ( isset( $element['data'] ) ? $element['data'] : array() );

            // Map type to HTML tag.
            $tag = $this->map_element_type_to_tag( $type );

            // Get content from properties.
            $content = $this->extract_element_content( $properties );

            // Build classes.
            $classes = array(
                'ob-element',
                sanitize_html_class( 'ob-' . strtolower( str_replace( '\\', '-', $type ) ) ),
                sanitize_html_class( $element_id ),
            );

            // Add custom classes from properties.
            if ( isset( $properties['classes'] ) && is_array( $properties['classes'] ) ) {
                foreach ( $properties['classes'] as $class ) {
                    $classes[] = sanitize_html_class( $class );
                }
            }

            $class_attr = implode( ' ', array_filter( $classes ) );

            // Render children recursively.
            $children_html = '';
            if ( isset( $element['children'] ) && is_array( $element['children'] ) ) {
                $children_html = $this->render_tree_to_html( $element['children'] );
            }

            // Build element HTML.
            $void_elements = array( 'img', 'br', 'hr', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'source', 'track', 'wbr' );

            if ( in_array( $tag, $void_elements, true ) ) {
                $html .= sprintf( '<%s class="%s" />', esc_attr( $tag ), esc_attr( $class_attr ) );
            } else {
                $html .= sprintf(
                    '<%s class="%s">%s%s</%s>',
                    esc_attr( $tag ),
                    esc_attr( $class_attr ),
                    $content,
                    $children_html,
                    esc_attr( $tag )
                );
            }
        }

        return $html;
    }

    /**
     * Map element type to HTML tag.
     *
     * @since 1.0.0
     *
     * @param string $type The element type.
     * @return string The HTML tag.
     */
    private function map_element_type_to_tag( string $type ): string {
        $type_lower = strtolower( $type );

        $tag_map = array(
            'section'   => 'section',
            'container' => 'div',
            'div'       => 'div',
            'columns'   => 'div',
            'column'    => 'div',
            'heading'   => 'h2',
            'text'      => 'p',
            'richtext'  => 'div',
            'paragraph' => 'p',
            'image'     => 'img',
            'button'    => 'button',
            'link'      => 'a',
            'nav'       => 'nav',
            'header'    => 'header',
            'footer'    => 'footer',
            'article'   => 'article',
            'aside'     => 'aside',
            'main'      => 'main',
            'form'      => 'form',
            'root'      => 'div',
        );

        if ( isset( $tag_map[ $type_lower ] ) ) {
            return $tag_map[ $type_lower ];
        }

        // Check partial matches.
        foreach ( $tag_map as $key => $tag ) {
            if ( stripos( $type_lower, $key ) !== false ) {
                return $tag;
            }
        }

        return 'div';
    }

    /**
     * Extract content from element properties.
     *
     * @since 1.0.0
     *
     * @param array $properties The element properties.
     * @return string The extracted content.
     */
    private function extract_element_content( array $properties ): string {
        $content_keys = array( 'text', 'content', 'html', 'value', 'label' );

        foreach ( $content_keys as $key ) {
            if ( isset( $properties[ $key ] ) && is_string( $properties[ $key ] ) ) {
                return wp_kses_post( $properties[ $key ] );
            }
        }

        if ( isset( $properties['content']['text'] ) ) {
            return wp_kses_post( $properties['content']['text'] );
        }

        return '';
    }

    /**
     * Get CSS for a document.
     *
     * Retrieves the CSS associated with a rendered document.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return string The CSS content.
     */
    public function get_document_css( int $post_id ): string {
        $meta_prefix = $this->get_meta_prefix();

        // Try post meta first.
        $css = get_post_meta( $post_id, $meta_prefix . 'css_cache', true );
        if ( ! empty( $css ) ) {
            return $css;
        }

        $css = get_post_meta( $post_id, $meta_prefix . 'css', true );
        if ( ! empty( $css ) ) {
            return $css;
        }

        // Check for cached CSS file.
        $upload_dir     = wp_upload_dir();
        $possible_files = array(
            $upload_dir['basedir'] . '/breakdance/css/post-' . $post_id . '.css',
            $upload_dir['basedir'] . '/breakdance/css/' . $post_id . '.css',
            $upload_dir['basedir'] . '/oxygen/css/' . $post_id . '.css',
        );

        foreach ( $possible_files as $file ) {
            if ( file_exists( $file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $file_css = file_get_contents( $file );
                if ( ! empty( $file_css ) ) {
                    return $file_css;
                }
            }
        }

        return '';
    }
}
