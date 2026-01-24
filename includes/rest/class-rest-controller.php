<?php
/**
 * Base REST Controller class.
 *
 * Provides shared functionality for all REST API controllers including
 * authentication, permission checks, and common utility methods.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

use Oxybridge\Oxygen_Data;

/**
 * Abstract base class for REST API controllers.
 *
 * @since 1.1.0
 */
abstract class REST_Controller {

    /**
     * REST API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'oxybridge/v1';

    /**
     * Cached meta prefix.
     *
     * @var string|null
     */
    private ?string $meta_prefix = null;

    /**
     * Cached option prefix.
     *
     * @var string|null
     */
    private ?string $option_prefix = null;

    /**
     * Register controller routes.
     *
     * @since 1.1.0
     * @return void
     */
    abstract public function register_routes(): void;

    /**
     * Get the REST namespace.
     *
     * @since 1.1.0
     * @return string
     */
    public function get_namespace(): string {
        return self::NAMESPACE;
    }

    /**
     * Check if request has valid authentication.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if authenticated, WP_Error otherwise.
     */
    public function check_authentication( \WP_REST_Request $request ) {
        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'rest_not_logged_in',
                __( 'You must be logged in to access this endpoint.', 'oxybridge-wp' ),
                array( 'status' => 401 )
            );
        }

        return true;
    }

    /**
     * Check if user has read permission.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if permitted, WP_Error otherwise.
     */
    public function check_read_permission( \WP_REST_Request $request ) {
        $auth_check = $this->check_authentication( $request );
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to access this resource.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check if user has write permission.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if permitted, WP_Error otherwise.
     */
    public function check_write_permission( \WP_REST_Request $request ) {
        $auth_check = $this->check_authentication( $request );
        if ( is_wp_error( $auth_check ) ) {
            return $auth_check;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to modify this resource.', 'oxybridge-wp' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Validate a post ID parameter.
     *
     * @since 1.1.0
     * @param mixed            $value   The parameter value.
     * @param \WP_REST_Request $request The request object.
     * @param string           $param   The parameter name.
     * @return bool|\WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_post_id( $value, \WP_REST_Request $request, $param ) {
        if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
            return new \WP_Error(
                'rest_invalid_param',
                sprintf(
                    /* translators: %s: parameter name */
                    __( '%s must be a positive integer.', 'oxybridge-wp' ),
                    $param
                ),
                array( 'status' => 400 )
            );
        }

        $post = get_post( (int) $value );
        if ( ! $post ) {
            return new \WP_Error(
                'rest_post_not_found',
                __( 'Post not found.', 'oxybridge-wp' ),
                array( 'status' => 404 )
            );
        }

        return true;
    }

    /**
     * Get the meta key prefix for the current builder mode.
     *
     * @since 1.1.0
     * @return string Meta key prefix.
     */
    protected function get_meta_prefix(): string {
        if ( $this->meta_prefix !== null ) {
            return $this->meta_prefix;
        }

        // Check for Breakdance/Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) ) {
            $mode = BREAKDANCE_MODE;
            $this->meta_prefix = ( $mode === 'breakdance' ) ? '_breakdance_' : '_oxygen_';
        } elseif ( defined( 'STARTER_STARTER_MODE' ) ) {
            $mode = STARTER_STARTER_MODE;
            $this->meta_prefix = ( $mode === 'starter' ) ? '_starter_' : '_starter_';
        } else {
            // Default to Oxygen prefix.
            $this->meta_prefix = '_oxygen_';
        }

        return $this->meta_prefix;
    }

    /**
     * Get the option key prefix for the current builder mode.
     *
     * @since 1.1.0
     * @return string Option key prefix.
     */
    protected function get_option_prefix(): string {
        if ( $this->option_prefix !== null ) {
            return $this->option_prefix;
        }

        // Check for Breakdance/Oxygen mode.
        if ( defined( 'BREAKDANCE_MODE' ) ) {
            $mode = BREAKDANCE_MODE;
            $this->option_prefix = ( $mode === 'breakdance' ) ? 'breakdance_' : 'oxygen_';
        } else {
            // Default to Oxygen prefix.
            $this->option_prefix = 'oxygen_';
        }

        return $this->option_prefix;
    }

    /**
     * Check if running in Breakdance mode.
     *
     * @since 1.1.0
     * @return bool True if Breakdance mode, false otherwise.
     */
    protected function is_breakdance_mode(): bool {
        return defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'breakdance';
    }

    /**
     * Get the builder name.
     *
     * @since 1.1.0
     * @return string Builder name.
     */
    protected function get_builder_name(): string {
        if ( $this->is_breakdance_mode() ) {
            return 'Breakdance';
        }

        return 'Oxygen';
    }

    /**
     * Get Oxygen/Breakdance version.
     *
     * @since 1.1.0
     * @return string|null Version string or null if not available.
     */
    protected function get_oxygen_version(): ?string {
        if ( defined( 'STARTER_VERSION' ) ) {
            return STARTER_VERSION;
        }

        if ( defined( '__STARTER_VERSION__' ) ) {
            return __STARTER_VERSION__;
        }

        return null;
    }

    /**
     * Check if a post has Oxygen/Breakdance content.
     *
     * @since 1.1.0
     * @param int $post_id The post ID.
     * @return bool True if has Oxygen content.
     */
    protected function has_oxygen_content( int $post_id ): bool {
        $meta_prefix = $this->get_meta_prefix();
        $tree_data = get_post_meta( $post_id, $meta_prefix . 'data', true );

        if ( ! empty( $tree_data ) ) {
            return true;
        }

        // Check classic Oxygen format.
        $classic_data = get_post_meta( $post_id, 'ct_builder_json', true );

        return ! empty( $classic_data );
    }

    /**
     * Get the document tree for a post.
     *
     * @since 1.1.0
     * @param int $post_id The post ID.
     * @return array|false The document tree or false if not found.
     */
    protected function get_document_tree( int $post_id ) {
        $tree = false;

        // Try using Oxygen_Data class if available.
        if ( class_exists( 'Oxybridge\Oxygen_Data' ) ) {
            $oxygen_data = new Oxygen_Data();
            $tree = $oxygen_data->get_template_tree( $post_id );
        }

        // Fallback: get directly from post meta.
        if ( $tree === false ) {
            $meta_prefix = $this->get_meta_prefix();
            $tree_data = get_post_meta( $post_id, $meta_prefix . 'data', true );

            if ( ! empty( $tree_data ) ) {
                $decoded = is_string( $tree_data ) ? json_decode( $tree_data, true ) : $tree_data;

                if ( is_array( $decoded ) && isset( $decoded['tree_json_string'] ) ) {
                    $tree = json_decode( $decoded['tree_json_string'], true );
                } elseif ( is_array( $decoded ) ) {
                    $tree = $decoded;
                }
            }
        }

        // Try classic Oxygen meta key as fallback.
        if ( $tree === false ) {
            $classic_data = get_post_meta( $post_id, 'ct_builder_json', true );

            if ( ! empty( $classic_data ) ) {
                $decoded = is_string( $classic_data ) ? json_decode( $classic_data, true ) : $classic_data;

                if ( is_array( $decoded ) ) {
                    $tree = $decoded;
                }
            }
        }

        // Ensure tree has required properties for Oxygen Builder io-ts validation.
        if ( is_array( $tree ) ) {
            $tree = $this->ensure_tree_integrity( $tree );
        }

        return $tree;
    }

    /**
     * Ensure tree has all required properties for Oxygen Builder compatibility.
     *
     * Based on IO-TS validation requirements, the tree needs:
     * - root.id: unique string identifier
     * - root.data.type: "root" (lowercase, no namespace)
     * - root.data.properties: null (not empty array or object)
     * - root.children: array of child elements
     * - _nextNodeId: integer for next element ID (REQUIRED for IO-TS validation)
     * - exportedLookupTable: empty object (stdClass to prevent PHP [] vs {} issue)
     *
     * NOTE: Uses stdClass for exportedLookupTable to ensure json_encode produces
     * {} instead of [] which would fail TypeScript io-ts object validation.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return array The tree with required properties ensured.
     */
    protected function ensure_tree_integrity( array $tree ): array {
        // If tree doesn't have a root, it's not a modern format tree.
        if ( ! isset( $tree['root'] ) ) {
            return $tree;
        }

        // Ensure root has correct structure for Oxygen Builder io-ts validation.
        if ( isset( $tree['root']['data'] ) ) {
            // Root type must be lowercase "root", not "EssentialElements\\Root".
            if ( isset( $tree['root']['data']['type'] ) &&
                 ( $tree['root']['data']['type'] === 'EssentialElements\\Root' ||
                   $tree['root']['data']['type'] === 'EssentialElements\Root' ) ) {
                $tree['root']['data']['type'] = 'root';
            }

            // Root properties must be null, not empty array or object.
            if ( ! isset( $tree['root']['data']['properties'] ) ||
                 $tree['root']['data']['properties'] === array() ||
                 $tree['root']['data']['properties'] === new \stdClass() ) {
                $tree['root']['data']['properties'] = null;
            }
        }

        // Add _nextNodeId - REQUIRED for IO-TS validation in Breakdance/Oxygen Builder.
        // Calculate from max existing element ID + 1.
        if ( ! isset( $tree['_nextNodeId'] ) ) {
            $tree['_nextNodeId'] = $this->calculate_next_node_id( $tree );
        }

        // Add exportedLookupTable as empty object (stdClass prevents PHP [] vs {} issue).
        // Using stdClass ensures json_encode produces {} not [].
        if ( ! isset( $tree['exportedLookupTable'] ) ) {
            $tree['exportedLookupTable'] = new \stdClass();
        }

        // Ensure status is set - Oxygen uses "exported" for valid documents.
        if ( ! isset( $tree['status'] ) ) {
            $tree['status'] = 'exported';
        }

        return $tree;
    }

    /**
     * Calculate the next node ID by finding the maximum existing ID.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return int The next node ID to use.
     */
    protected function calculate_next_node_id( array $tree ): int {
        $max_id = 0;
        $ids = $this->extract_all_element_ids( $tree );

        foreach ( $ids as $id ) {
            if ( is_numeric( $id ) ) {
                $max_id = max( $max_id, (int) $id );
                continue;
            }

            if ( is_string( $id ) && preg_match( '/(\d+)$/', $id, $matches ) ) {
                $max_id = max( $max_id, (int) $matches[1] );
            }
        }

        return max( 1, $max_id + 1 );
    }

    /**
     * Extract all element IDs from a tree structure.
     *
     * @since 1.1.0
     * @param array $tree The document tree.
     * @return array Array of element IDs.
     */
    protected function extract_all_element_ids( array $tree ): array {
        $ids = array();

        if ( isset( $tree['root']['id'] ) ) {
            $ids[] = $tree['root']['id'];

            if ( isset( $tree['root']['children'] ) && is_array( $tree['root']['children'] ) ) {
                $ids = array_merge( $ids, $this->extract_element_ids_recursive( $tree['root']['children'] ) );
            }
        }

        if ( isset( $tree['children'] ) && is_array( $tree['children'] ) ) {
            $ids = array_merge( $ids, $this->extract_element_ids_recursive( $tree['children'] ) );
        }

        if ( isset( $tree[0] ) && is_array( $tree[0] ) ) {
            $ids = array_merge( $ids, $this->extract_element_ids_recursive( $tree ) );
        }

        return $ids;
    }

    /**
     * Recursively extract element IDs from children array.
     *
     * @since 1.1.0
     * @param array $children Array of child elements.
     * @return array Array of element IDs.
     */
    protected function extract_element_ids_recursive( array $children ): array {
        $ids = array();

        foreach ( $children as $child ) {
            if ( ! is_array( $child ) ) {
                continue;
            }

            if ( isset( $child['id'] ) ) {
                $ids[] = $child['id'];
            }

            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $ids = array_merge( $ids, $this->extract_element_ids_recursive( $child['children'] ) );
            }
        }

        return $ids;
    }

    /**
     * Save document tree to post meta using Oxygen's native save mechanism.
     *
     * This method mirrors Oxygen's save_document() function to ensure compatibility:
     * 1. Save tree using Oxygen's set_meta() or fallback to direct meta update
     * 2. Update post to trigger revisions
     * 3. Regenerate CSS cache
     *
     * @since 1.1.0
     * @param int   $post_id The post ID.
     * @param array $tree    The tree structure to save.
     * @return bool True if saved successfully.
     */
    protected function save_document_tree( int $post_id, array $tree ): bool {
        if ( ! $this->is_valid_tree_structure( $tree ) ) {
            return false;
        }

        // Ensure tree integrity before saving.
        $tree = $this->ensure_tree_integrity( $tree );

        // Convert tree to JSON string (Oxygen stores tree_json_string as a string, not array).
        $tree_json = wp_json_encode( $tree );
        if ( $tree_json === false ) {
            return false;
        }

        $meta_prefix = $this->get_meta_prefix();
        $meta_key = $meta_prefix . 'data';

        // Try to use Oxygen's native set_meta function for proper encoding.
        if ( function_exists( 'Breakdance\Data\set_meta' ) ) {
            \Breakdance\Data\set_meta(
                $post_id,
                $meta_key,
                array( 'tree_json_string' => $tree_json )
            );
            $result = true;
        } else {
            // Fallback: Manual save with proper encoding (matching Oxygen's format).
            $data = array( 'tree_json_string' => $tree_json );
            $encoded_data = wp_json_encode( $data );

            if ( $encoded_data === false ) {
                return false;
            }

            $result = update_post_meta( $post_id, $meta_key, wp_slash( $encoded_data ) );
        }

        // Update post to trigger revisions (like Oxygen does).
        wp_update_post( array( 'ID' => $post_id ) );

        // Regenerate CSS cache immediately.
        $this->regenerate_post_css( $post_id );

        // Fire Oxygen's after save action if available.
        if ( function_exists( 'bdox_run_action' ) ) {
            bdox_run_action( 'breakdance_after_save_document', $post_id );
        }

        /**
         * Fires after document tree has been saved.
         *
         * @since 1.1.0
         * @param int   $post_id The post ID.
         * @param array $tree    The tree structure that was saved.
         * @param bool  $result  Whether the save was successful.
         */
        do_action( 'oxybridge_document_tree_saved', $post_id, $tree, (bool) $result );

        return (bool) $result;
    }

    /**
     * Check if array is a valid tree structure.
     *
     * @since 1.1.0
     * @param array $tree The tree to validate.
     * @return bool True if valid.
     */
    protected function is_valid_tree_structure( array $tree ): bool {
        // Modern format: must have root with id and children.
        if ( isset( $tree['root'] ) ) {
            if ( ! isset( $tree['root']['id'] ) || ! isset( $tree['root']['children'] ) ) {
                return false;
            }
            return true;
        }

        // Classic format: array of elements.
        if ( isset( $tree[0] ) && is_array( $tree[0] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Create an empty tree structure.
     *
     * Creates a valid Oxygen Builder tree matching the structure of
     * working Oxygen-created documents:
     * - root.id: unique identifier
     * - root.data.type: "root" (lowercase)
     * - root.data.properties: null
     * - root.children: empty array
     * - _nextNodeId: integer for next element ID (REQUIRED for IO-TS validation)
     *
     * @since 1.1.0
     * @return array Empty tree structure.
     */
    protected function create_empty_tree(): array {
        return array(
            'root' => array(
                'id'       => 'el-root',
                'data'     => array(
                    'type'       => 'root',
                    'properties' => null,
                ),
                'children' => array(),
            ),
            '_nextNodeId' => 1,
        );
    }

    /**
     * Generate a unique element ID.
     *
     * @since 1.1.0
     * @return string Element ID.
     */
    protected function generate_element_id(): string {
        return 'el-' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 );
    }

    /**
     * Regenerate CSS cache for a post using Oxygen's native function.
     *
     * This should be called after any document tree save to ensure
     * CSS is updated on the frontend.
     *
     * @since 1.1.0
     * @param int $post_id The post ID.
     * @return bool True if regeneration was successful.
     */
    protected function regenerate_post_css( int $post_id ): bool {
        // Try Breakdance/Oxygen 6 cache regeneration (primary method).
        if ( function_exists( 'Breakdance\Render\generateCacheForPost' ) ) {
            \Breakdance\Render\generateCacheForPost( $post_id );
            return true;
        }

        // Try classic Oxygen cache regeneration.
        if ( function_exists( 'oxygen_vsb_cache_page_css' ) ) {
            oxygen_vsb_cache_page_css( $post_id );
            return true;
        }

        return false;
    }

    /**
     * Get the builder edit URL for a post.
     *
     * @since 1.1.0
     * @param int $post_id The post ID.
     * @return string The edit URL.
     */
    protected function get_builder_edit_url( int $post_id ): string {
        $base_url = get_permalink( $post_id );

        if ( ! $base_url ) {
            return '';
        }

        $query_param = $this->is_breakdance_mode() ? 'breakdance' : 'oxygen';

        return add_query_arg( $query_param, 'builder', $base_url );
    }

    /**
     * Count elements in a tree recursively.
     *
     * @since 1.1.0
     * @param array $children Array of child elements.
     * @return int Element count.
     */
    protected function count_elements_recursive( array $children ): int {
        $count = count( $children );

        foreach ( $children as $child ) {
            if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
                $count += $this->count_elements_recursive( $child['children'] );
            }
        }

        return $count;
    }

    /**
     * Format a REST response with standard structure.
     *
     * Adds _links footer to all responses for API discoverability.
     *
     * @since 1.1.0
     * @param mixed $data    Response data.
     * @param int   $status  HTTP status code.
     * @return \WP_REST_Response
     */
    protected function format_response( $data, int $status = 200 ): \WP_REST_Response {
        // Wrap non-array data to allow adding _links.
        if ( ! is_array( $data ) ) {
            $data = array( 'data' => $data );
        }

        // Add documentation links footer.
        $data['_links'] = $this->get_response_links();

        return new \WP_REST_Response( $data, $status );
    }

    /**
     * Format an error response.
     *
     * @since 1.1.0
     * @param string $code    Error code.
     * @param string $message Error message.
     * @param int    $status  HTTP status code.
     * @return \WP_Error
     */
    protected function format_error( string $code, string $message, int $status = 400 ): \WP_Error {
        return new \WP_Error(
            $code,
            $message,
            array(
                'status' => $status,
                '_links' => $this->get_response_links(),
            )
        );
    }

    /**
     * Get standard response links for API discoverability.
     *
     * @since 1.1.0
     * @return array Links array.
     */
    protected function get_response_links(): array {
        return array(
            'documentation' => array(
                'href'        => rest_url( self::NAMESPACE . '/ai/docs' ),
                'title'       => 'Complete API documentation for AI agents',
            ),
            'context'       => array(
                'href'        => rest_url( self::NAMESPACE . '/ai/context' ),
                'title'       => 'AI context with element types and property paths',
            ),
            'schema'        => array(
                'href'        => rest_url( self::NAMESPACE . '/ai/schema' ),
                'title'       => 'Element schema definitions',
            ),
        );
    }
}
