<?php
/**
 * Oxygen Data Access Class
 *
 * Provides data access methods for Oxygen Builder templates using the
 * Breakdance\Data namespace API. This class wraps the underlying data
 * layer to provide a clean interface for MCP tool integration.
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
        $meta_prefix = $this->get_meta_prefix();
        $settings    = get_option( $meta_prefix . 'global_settings_json_string' );

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
        $meta_prefix = $this->get_meta_prefix();
        $variables   = get_option( $meta_prefix . 'variables_json_string' );

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
        $meta_prefix = $this->get_meta_prefix();
        $selectors   = get_option( $meta_prefix . 'breakdance_classes_json_string' );

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
        // Try to get prefix from Breakdance.
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
}
