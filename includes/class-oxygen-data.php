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
use function Breakdance\Data\set_meta;
use function Breakdance\Data\get_global_option;
use function Breakdance\Data\get_tree_elements;
use function Breakdance\BreakdanceOxygen\Strings\__bdox;
use function Breakdance\Render\generateCacheForPost;

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
     * Save a tree structure to a post.
     *
     * Validates the tree, saves it using the appropriate meta format,
     * triggers WordPress revision system, and regenerates CSS cache.
     *
     * @since 1.0.0
     *
     * @param int   $post_id The post ID to save the tree to.
     * @param array $tree    The complete tree structure to save.
     * @return array {
     *     Response array.
     *
     *     @type bool   $success Whether the save was successful.
     *     @type string $message Success or error message.
     *     @type int    $post_id The post ID that was saved.
     * }
     */
    public function save_tree( int $post_id, array $tree ): array {
        // Verify post exists.
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array(
                'success' => false,
                'message' => __( 'Post not found.', 'oxybridge' ),
                'post_id' => $post_id,
            );
        }

        // Validate tree structure using Breakdance function or fallback.
        if ( ! $this->validate_tree( $tree ) ) {
            return array(
                'success' => false,
                'message' => __( 'Invalid tree structure. Tree must have a root element with id, data, and children properties.', 'oxybridge' ),
                'post_id' => $post_id,
            );
        }

        // Encode tree to JSON string.
        $tree_json = wp_json_encode( $tree );
        if ( false === $tree_json ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to encode tree to JSON.', 'oxybridge' ),
                'post_id' => $post_id,
            );
        }

        // Get the correct meta prefix for Oxygen/Breakdance.
        $meta_prefix = $this->get_meta_prefix();

        // Prepare data in Breakdance format.
        $data = array(
            'tree_json_string' => $tree_json,
        );

        // Try using Breakdance set_meta function if available.
        if ( function_exists( 'Breakdance\Data\set_meta' ) ) {
            set_meta( $post_id, 'data', wp_json_encode( $data ) );
        } else {
            // Fallback to direct meta update.
            update_post_meta( $post_id, $meta_prefix . 'data', wp_json_encode( $data ) );
        }

        // Trigger WordPress revision by updating the post.
        $update_result = wp_update_post(
            array(
                'ID'            => $post_id,
                'post_modified' => current_time( 'mysql' ),
            )
        );

        if ( is_wp_error( $update_result ) ) {
            return array(
                'success' => false,
                'message' => __( 'Failed to update post revision.', 'oxybridge' ),
                'post_id' => $post_id,
            );
        }

        // Regenerate CSS cache using Breakdance function if available.
        $css_regenerated = $this->regenerate_css_for_post( $post_id );

        return array(
            'success'         => true,
            'message'         => __( 'Tree saved successfully.', 'oxybridge' ),
            'post_id'         => $post_id,
            'css_regenerated' => $css_regenerated,
        );
    }

    /**
     * Regenerate CSS cache for a post.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID to regenerate CSS for.
     * @return bool True if CSS was regenerated, false otherwise.
     */
    public function regenerate_css_for_post( int $post_id ): bool {
        // Try Breakdance render function first.
        if ( function_exists( 'Breakdance\Render\generateCacheForPost' ) ) {
            try {
                generateCacheForPost( $post_id );
                return true;
            } catch ( \Exception $e ) {
                // Log error but don't fail the save operation.
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'Oxybridge: Failed to regenerate CSS for post ' . $post_id . ': ' . $e->getMessage() );
                }
                return false;
            }
        }

        // Try Oxygen classic CSS regeneration.
        if ( class_exists( 'OxyEl' ) && method_exists( 'OxyEl', 'generate_stylesheet' ) ) {
            try {
                \OxyEl::generate_stylesheet( $post_id );
                return true;
            } catch ( \Exception $e ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log( 'Oxybridge: Failed to regenerate CSS for post ' . $post_id . ': ' . $e->getMessage() );
                }
                return false;
            }
        }

        // No CSS regeneration function available.
        return false;
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

    /**
     * Generate a unique element ID.
     *
     * Uses WordPress's UUID generation for unique element identifiers.
     *
     * @since 1.0.0
     *
     * @return string UUID v4 string.
     */
    public function generate_element_id(): string {
        return wp_generate_uuid4();
    }

    /**
     * Find an element by ID in the tree structure.
     *
     * Recursively searches the tree to find an element with the given ID.
     * Returns a reference to the element array for direct modification.
     *
     * @since 1.0.0
     *
     * @param array  $tree       The tree or subtree to search. Pass by reference for modification.
     * @param string $element_id The element ID to find.
     * @return array|null Reference to the found element, or null if not found.
     */
    public function find_element_in_tree( array &$tree, string $element_id ): ?array {
        // Check if this is the root level with 'root' key.
        if ( isset( $tree['root'] ) ) {
            if ( isset( $tree['root']['id'] ) && $tree['root']['id'] === $element_id ) {
                return $tree['root'];
            }
            // Search in root's children.
            if ( isset( $tree['root']['children'] ) && is_array( $tree['root']['children'] ) ) {
                $result = $this->find_element_in_children( $tree['root']['children'], $element_id );
                if ( $result !== null ) {
                    return $result;
                }
            }
            return null;
        }

        // Check current element.
        if ( isset( $tree['id'] ) && $tree['id'] === $element_id ) {
            return $tree;
        }

        // Search in children.
        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            return $this->find_element_in_children( $tree['children'], $element_id );
        }

        return null;
    }

    /**
     * Find an element in a children array.
     *
     * Helper method for recursive element search.
     *
     * @since 1.0.0
     *
     * @param array  $children   Array of child elements.
     * @param string $element_id The element ID to find.
     * @return array|null The found element, or null if not found.
     */
    private function find_element_in_children( array $children, string $element_id ): ?array {
        foreach ( $children as $child ) {
            if ( isset( $child['id'] ) && $child['id'] === $element_id ) {
                return $child;
            }

            // Recursively search in children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $result = $this->find_element_in_children( $child['children'], $element_id );
                if ( $result !== null ) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Find and return a reference to an element's children array for modification.
     *
     * @since 1.0.0
     *
     * @param array  $tree       The tree to search (passed by reference).
     * @param string $element_id The element ID whose children to find.
     * @return array|null Reference to children array, or null if not found.
     */
    private function &find_element_children_ref( array &$tree, string $element_id ): ?array {
        $null = null;

        // Check if this is the root level with 'root' key.
        if ( isset( $tree['root'] ) ) {
            if ( isset( $tree['root']['id'] ) && $tree['root']['id'] === $element_id ) {
                if ( ! isset( $tree['root']['children'] ) ) {
                    $tree['root']['children'] = array();
                }
                return $tree['root']['children'];
            }
            // Search in root's children.
            if ( isset( $tree['root']['children'] ) && is_array( $tree['root']['children'] ) ) {
                $result = &$this->find_children_in_array( $tree['root']['children'], $element_id );
                if ( $result !== null ) {
                    return $result;
                }
            }
            return $null;
        }

        // Check current element.
        if ( isset( $tree['id'] ) && $tree['id'] === $element_id ) {
            if ( ! isset( $tree['children'] ) ) {
                $tree['children'] = array();
            }
            return $tree['children'];
        }

        // Search in children.
        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            return $this->find_children_in_array( $tree['children'], $element_id );
        }

        return $null;
    }

    /**
     * Find children array reference in a children array.
     *
     * @since 1.0.0
     *
     * @param array  $children   Array of child elements (passed by reference).
     * @param string $element_id The element ID whose children to find.
     * @return array|null Reference to children array, or null if not found.
     */
    private function &find_children_in_array( array &$children, string $element_id ): ?array {
        $null = null;

        foreach ( $children as &$child ) {
            if ( isset( $child['id'] ) && $child['id'] === $element_id ) {
                if ( ! isset( $child['children'] ) ) {
                    $child['children'] = array();
                }
                return $child['children'];
            }

            // Recursively search in children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $result = &$this->find_children_in_array( $child['children'], $element_id );
                if ( $result !== null ) {
                    return $result;
                }
            }
        }

        return $null;
    }

    /**
     * Create a new element in a document tree.
     *
     * Creates a new element with a unique UUID, validates that the parent exists,
     * and inserts it at the specified position in the parent's children array.
     *
     * @since 1.0.0
     *
     * @param int    $post_id      The post ID containing the tree.
     * @param string $parent_id    The ID of the parent element to insert into.
     * @param string $element_type The element type (e.g., "EssentialElements\\Section").
     * @param array  $properties   Optional. Element properties. Default empty array.
     * @param mixed  $position     Optional. Position: 'first', 'last', or integer index. Default 'last'.
     * @return array {
     *     Response array.
     *
     *     @type bool   $success    Whether the creation was successful.
     *     @type string $message    Success or error message.
     *     @type string $element_id The new element's UUID (only on success).
     *     @type int    $post_id    The post ID.
     * }
     */
    public function create_element( int $post_id, string $parent_id, string $element_type, array $properties = array(), $position = 'last' ): array {
        // Get the current tree.
        $tree = $this->get_template_tree( $post_id );

        if ( $tree === false ) {
            return array(
                'success' => false,
                'message' => __( 'Could not retrieve document tree. Post may not exist or have no Oxygen content.', 'oxybridge' ),
                'post_id' => $post_id,
            );
        }

        // Find the parent element's children array.
        $parent_children = &$this->find_element_children_ref( $tree, $parent_id );

        if ( $parent_children === null ) {
            return array(
                'success' => false,
                'message' => sprintf(
                    /* translators: %s: parent element ID */
                    __( 'Parent element with ID "%s" not found in tree.', 'oxybridge' ),
                    $parent_id
                ),
                'post_id' => $post_id,
            );
        }

        // Generate unique ID for the new element.
        $element_id = $this->generate_element_id();

        // Create the new element structure.
        $new_element = array(
            'id'       => $element_id,
            'data'     => array(
                'type'       => $element_type,
                'properties' => $properties,
            ),
            'children' => array(),
        );

        // Insert at the specified position.
        if ( $position === 'first' ) {
            array_unshift( $parent_children, $new_element );
        } elseif ( $position === 'last' || ! is_numeric( $position ) ) {
            $parent_children[] = $new_element;
        } else {
            $index = absint( $position );
            $index = min( $index, count( $parent_children ) ); // Clamp to array bounds.
            array_splice( $parent_children, $index, 0, array( $new_element ) );
        }

        // Save the modified tree.
        $save_result = $this->save_tree( $post_id, $tree );

        if ( ! $save_result['success'] ) {
            return array(
                'success' => false,
                'message' => $save_result['message'],
                'post_id' => $post_id,
            );
        }

        return array(
            'success'    => true,
            'message'    => __( 'Element created successfully.', 'oxybridge' ),
            'element_id' => $element_id,
            'post_id'    => $post_id,
        );
    }

    /**
     * Update an element's properties in the document tree.
     *
     * Finds the element by ID and performs a partial update by merging
     * the provided properties with existing properties.
     *
     * @since 1.0.0
     *
     * @param int    $post_id    The post ID containing the tree.
     * @param string $element_id The ID of the element to update.
     * @param array  $properties The properties to merge/update.
     * @return bool True if update was successful, false otherwise.
     */
    public function update_element( int $post_id, string $element_id, array $properties ): bool {
        // Get the current tree.
        $tree = $this->get_template_tree( $post_id );

        if ( $tree === false ) {
            return false;
        }

        // Update the element in the tree.
        $updated = $this->update_element_in_tree( $tree, $element_id, $properties );

        if ( ! $updated ) {
            return false;
        }

        // Save the modified tree.
        $save_result = $this->save_tree( $post_id, $tree );

        return $save_result['success'];
    }

    /**
     * Update an element's properties in a tree structure.
     *
     * Recursively searches the tree and updates the element's properties
     * by merging with existing values.
     *
     * @since 1.0.0
     *
     * @param array  $tree       The tree to search (passed by reference).
     * @param string $element_id The element ID to update.
     * @param array  $properties The properties to merge.
     * @return bool True if element was found and updated, false otherwise.
     */
    private function update_element_in_tree( array &$tree, string $element_id, array $properties ): bool {
        // Check if this is the root level with 'root' key.
        if ( isset( $tree['root'] ) ) {
            if ( isset( $tree['root']['id'] ) && $tree['root']['id'] === $element_id ) {
                $this->merge_element_properties( $tree['root'], $properties );
                return true;
            }
            // Search in root's children.
            if ( isset( $tree['root']['children'] ) && is_array( $tree['root']['children'] ) ) {
                return $this->update_element_in_children( $tree['root']['children'], $element_id, $properties );
            }
            return false;
        }

        // Check current element.
        if ( isset( $tree['id'] ) && $tree['id'] === $element_id ) {
            $this->merge_element_properties( $tree, $properties );
            return true;
        }

        // Search in children.
        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            return $this->update_element_in_children( $tree['children'], $element_id, $properties );
        }

        return false;
    }

    /**
     * Update an element in a children array.
     *
     * Helper method for recursive element update.
     *
     * @since 1.0.0
     *
     * @param array  $children   Array of child elements (passed by reference).
     * @param string $element_id The element ID to update.
     * @param array  $properties The properties to merge.
     * @return bool True if element was found and updated, false otherwise.
     */
    private function update_element_in_children( array &$children, string $element_id, array $properties ): bool {
        foreach ( $children as &$child ) {
            if ( isset( $child['id'] ) && $child['id'] === $element_id ) {
                $this->merge_element_properties( $child, $properties );
                return true;
            }

            // Recursively search in children.
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                if ( $this->update_element_in_children( $child['children'], $element_id, $properties ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Merge properties into an element.
     *
     * Performs a deep merge of properties, preserving existing values
     * that are not being updated.
     *
     * @since 1.0.0
     *
     * @param array $element    The element to update (passed by reference).
     * @param array $properties The properties to merge.
     */
    private function merge_element_properties( array &$element, array $properties ): void {
        // Ensure data and properties structure exists.
        if ( ! isset( $element['data'] ) ) {
            $element['data'] = array();
        }
        if ( ! isset( $element['data']['properties'] ) ) {
            $element['data']['properties'] = array();
        }

        // Merge properties recursively.
        $element['data']['properties'] = $this->array_merge_recursive_distinct(
            $element['data']['properties'],
            $properties
        );
    }

    /**
     * Recursively merge arrays, replacing values rather than appending.
     *
     * Unlike array_merge_recursive, this replaces scalar values instead
     * of converting them to arrays.
     *
     * @since 1.0.0
     *
     * @param array $array1 The base array.
     * @param array $array2 The array to merge into base.
     * @return array The merged array.
     */
    private function array_merge_recursive_distinct( array $array1, array $array2 ): array {
        $merged = $array1;

        foreach ( $array2 as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = $this->array_merge_recursive_distinct( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }

        return $merged;
    }

    /**
     * Delete an element from the document tree.
     *
     * Finds the element by ID and removes it along with all its children.
     * The root element cannot be deleted.
     *
     * @since 1.0.0
     *
     * @param int    $post_id    The post ID containing the tree.
     * @param string $element_id The ID of the element to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete_element( int $post_id, string $element_id ): bool {
        // Get the current tree.
        $tree = $this->get_template_tree( $post_id );

        if ( $tree === false ) {
            return false;
        }

        // Prevent deletion of root element.
        if ( isset( $tree['root']['id'] ) && $tree['root']['id'] === $element_id ) {
            return false;
        }

        // Delete the element from the tree.
        $deleted = $this->delete_element_in_tree( $tree, $element_id );

        if ( ! $deleted ) {
            return false;
        }

        // Save the modified tree.
        $save_result = $this->save_tree( $post_id, $tree );

        return $save_result['success'];
    }

    /**
     * Delete an element from a tree structure.
     *
     * Recursively searches the tree and removes the element with the given ID.
     * All children of the deleted element are also removed.
     *
     * @since 1.0.0
     *
     * @param array  $tree       The tree to search (passed by reference).
     * @param string $element_id The element ID to delete.
     * @return bool True if element was found and deleted, false otherwise.
     */
    private function delete_element_in_tree( array &$tree, string $element_id ): bool {
        // Check if this is the root level with 'root' key.
        if ( isset( $tree['root'] ) ) {
            // Search in root's children.
            if ( isset( $tree['root']['children'] ) && is_array( $tree['root']['children'] ) ) {
                return $this->delete_element_in_children( $tree['root']['children'], $element_id );
            }
            return false;
        }

        // Search in children.
        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            return $this->delete_element_in_children( $tree['children'], $element_id );
        }

        return false;
    }

    /**
     * Delete an element from a children array.
     *
     * Helper method for recursive element deletion.
     *
     * @since 1.0.0
     *
     * @param array  $children   Array of child elements (passed by reference).
     * @param string $element_id The element ID to delete.
     * @return bool True if element was found and deleted, false otherwise.
     */
    private function delete_element_in_children( array &$children, string $element_id ): bool {
        foreach ( $children as $index => $child ) {
            if ( isset( $child['id'] ) && $child['id'] === $element_id ) {
                // Remove the element and its children.
                array_splice( $children, $index, 1 );
                return true;
            }

            // Recursively search in children.
            if ( isset( $children[ $index ]['children'] ) && is_array( $children[ $index ]['children'] ) ) {
                if ( $this->delete_element_in_children( $children[ $index ]['children'], $element_id ) ) {
                    return true;
                }
            }
        }

        return false;
    }
}
