<?php
/**
 * Cache Management Class
 *
 * Provides utilities for managing CSS cache for Oxygen Builder / Breakdance
 * designs. This class centralizes cache operations including regeneration,
 * clearing, status checking, and statistics gathering.
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
 * Class Cache
 *
 * CSS cache management utilities for Oxygen/Breakdance.
 *
 * Example usage:
 * ```php
 * $cache = new Cache();
 *
 * // Regenerate CSS for a single post.
 * $result = $cache->regenerate( $post_id );
 *
 * // Regenerate CSS for all posts.
 * $results = $cache->regenerate_all();
 *
 * // Check cache status for a post.
 * $status = $cache->get_status( $post_id );
 *
 * // Clear cache for a post.
 * $cache->clear( $post_id );
 * ```
 *
 * @since 1.0.0
 */
class Cache {

    /**
     * Meta key suffix for CSS cache data.
     *
     * @var string
     */
    const CSS_CACHE_KEY = 'css_cache';

    /**
     * Meta key suffix for CSS file paths cache.
     *
     * @var string
     */
    const CSS_FILE_PATHS_KEY = 'css_file_paths_cache';

    /**
     * Meta key suffix for dependency cache.
     *
     * @var string
     */
    const DEPENDENCY_CACHE_KEY = 'dependency_cache';

    /**
     * Default batch size for bulk operations.
     *
     * @var int
     */
    const DEFAULT_BATCH_SIZE = 50;

    /**
     * Maximum batch size for bulk operations.
     *
     * @var int
     */
    const MAX_BATCH_SIZE = 200;

    /**
     * The meta prefix for Oxygen/Breakdance data.
     *
     * @var string
     */
    private string $meta_prefix;

    /**
     * Constructor.
     *
     * Initializes the cache manager with the appropriate meta prefix.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->meta_prefix = $this->determine_meta_prefix();
    }

    /**
     * Regenerate CSS cache for a single post.
     *
     * Uses Oxygen/Breakdance's built-in cache regeneration function to
     * regenerate the CSS for the specified post.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID to regenerate CSS for.
     * @return array {
     *     Regeneration result.
     *
     *     @type bool   $success     Whether regeneration succeeded.
     *     @type int    $post_id     The post ID.
     *     @type string $message     Status message.
     *     @type int    $duration_ms Regeneration duration in milliseconds.
     *     @type string $error       Error message if failed.
     * }
     */
    public function regenerate( int $post_id ): array {
        $result = array(
            'success'     => false,
            'post_id'     => $post_id,
            'message'     => '',
            'duration_ms' => 0,
            'error'       => null,
        );

        // Verify the post exists.
        $post = get_post( $post_id );
        if ( ! $post ) {
            $result['error']   = __( 'Post not found.', 'oxybridge-wp' );
            $result['message'] = $result['error'];
            return $result;
        }

        // Check if regeneration function is available.
        if ( ! $this->is_regeneration_available() ) {
            $result['error']   = __( 'CSS regeneration function is not available. Oxygen/Breakdance may not be active.', 'oxybridge-wp' );
            $result['message'] = $result['error'];
            return $result;
        }

        // Record start time.
        $start_time = microtime( true );

        try {
            // Call Oxygen/Breakdance's built-in cache regeneration function.
            \Breakdance\Render\generateCacheForPost( $post_id );

            $result['success']     = true;
            $result['duration_ms'] = round( ( microtime( true ) - $start_time ) * 1000 );
            $result['message']     = __( 'CSS cache regenerated successfully.', 'oxybridge-wp' );

            /**
             * Fires after CSS cache has been regenerated for a post.
             *
             * @since 1.0.0
             * @param int   $post_id  The post ID.
             * @param float $duration The regeneration duration in milliseconds.
             */
            do_action( 'oxybridge_cache_regenerated', $post_id, $result['duration_ms'] );

        } catch ( \Exception $e ) {
            $result['duration_ms'] = round( ( microtime( true ) - $start_time ) * 1000 );
            $result['error']       = $e->getMessage();
            $result['message']     = sprintf(
                /* translators: %s: error message */
                __( 'CSS regeneration failed: %s', 'oxybridge-wp' ),
                $e->getMessage()
            );
        } catch ( \Error $e ) {
            $result['duration_ms'] = round( ( microtime( true ) - $start_time ) * 1000 );
            $result['error']       = $e->getMessage();
            $result['message']     = sprintf(
                /* translators: %s: error message */
                __( 'CSS regeneration encountered an error: %s', 'oxybridge-wp' ),
                $e->getMessage()
            );
        }

        return $result;
    }

    /**
     * Regenerate CSS cache for multiple posts.
     *
     * Iterates through posts with Oxygen/Breakdance content and regenerates
     * the CSS cache for each. Supports filtering by post type and batch size.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Regeneration arguments.
     *
     *     @type string       $post_type  Filter by post type. Default all types.
     *     @type int          $batch_size Number of posts per batch. Default 50.
     *     @type array|string $status     Post status(es) to include. Default multiple.
     *     @type callable     $callback   Optional callback for progress updates.
     * }
     * @return array {
     *     Bulk regeneration results.
     *
     *     @type bool   $success     Whether all regenerations succeeded.
     *     @type string $message     Summary message.
     *     @type int    $total       Total posts found.
     *     @type int    $processed   Number of posts processed.
     *     @type int    $succeeded   Number of successful regenerations.
     *     @type int    $failed      Number of failed regenerations.
     *     @type int    $duration_ms Total duration in milliseconds.
     *     @type array  $results     Per-post results.
     * }
     */
    public function regenerate_all( array $args = array() ): array {
        $defaults = array(
            'post_type'  => '',
            'batch_size' => self::DEFAULT_BATCH_SIZE,
            'status'     => array( 'publish', 'draft', 'pending', 'private' ),
            'callback'   => null,
        );

        $args = wp_parse_args( $args, $defaults );

        // Clamp batch size.
        $batch_size = max( 1, min( self::MAX_BATCH_SIZE, absint( $args['batch_size'] ) ) );

        $response = array(
            'success'     => true,
            'message'     => '',
            'total'       => 0,
            'processed'   => 0,
            'succeeded'   => 0,
            'failed'      => 0,
            'duration_ms' => 0,
            'results'     => array(),
        );

        // Check if regeneration function is available.
        if ( ! $this->is_regeneration_available() ) {
            $response['success'] = false;
            $response['message'] = __( 'CSS regeneration function is not available. Oxygen/Breakdance may not be active.', 'oxybridge-wp' );
            return $response;
        }

        // Record start time.
        $start_time = microtime( true );

        // Get posts with Oxygen content.
        $post_ids = $this->get_posts_with_oxygen_content( $args['post_type'], $args['status'] );
        $response['total'] = count( $post_ids );

        if ( $response['total'] === 0 ) {
            $response['message'] = __( 'No posts with Oxygen/Breakdance content found to regenerate.', 'oxybridge-wp' );
            return $response;
        }

        // Process posts in batches.
        $batches = array_chunk( $post_ids, $batch_size );

        foreach ( $batches as $batch_index => $batch ) {
            foreach ( $batch as $post_id ) {
                $result = $this->regenerate( $post_id );

                // Add post details.
                $post = get_post( $post_id );
                if ( $post ) {
                    $result['post_title'] = $post->post_title;
                    $result['post_type']  = $post->post_type;
                }

                $response['results'][] = $result;
                $response['processed']++;

                if ( $result['success'] ) {
                    $response['succeeded']++;
                } else {
                    $response['failed']++;
                }

                // Call progress callback if provided.
                if ( is_callable( $args['callback'] ) ) {
                    call_user_func(
                        $args['callback'],
                        $response['processed'],
                        $response['total'],
                        $result
                    );
                }
            }
        }

        $response['duration_ms'] = round( ( microtime( true ) - $start_time ) * 1000 );
        $response['success']     = $response['failed'] === 0;

        if ( $response['success'] ) {
            $response['message'] = sprintf(
                /* translators: %d: number of posts */
                __( 'CSS cache regenerated successfully for %d posts.', 'oxybridge-wp' ),
                $response['succeeded']
            );
        } else {
            $response['message'] = sprintf(
                /* translators: 1: number succeeded, 2: number failed */
                __( 'CSS regeneration completed with %1$d successes and %2$d failures.', 'oxybridge-wp' ),
                $response['succeeded'],
                $response['failed']
            );
        }

        /**
         * Fires after bulk CSS regeneration completes.
         *
         * @since 1.0.0
         * @param int   $total      Total posts found.
         * @param int   $succeeded  Number of posts successfully regenerated.
         * @param int   $failed     Number of posts that failed regeneration.
         * @param float $duration   Total duration in milliseconds.
         */
        do_action( 'oxybridge_cache_bulk_regenerated', $response['total'], $response['succeeded'], $response['failed'], $response['duration_ms'] );

        return $response;
    }

    /**
     * Get cache status for a post.
     *
     * Returns information about the current CSS cache state for a post,
     * including whether cache exists, when it was last modified, and file sizes.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return array {
     *     Cache status information.
     *
     *     @type bool   $has_cache       Whether cache exists.
     *     @type bool   $has_meta_cache  Whether meta cache exists.
     *     @type bool   $has_file_cache  Whether file cache exists.
     *     @type string $cache_file      Path to cache file if exists.
     *     @type int    $file_size       Cache file size in bytes.
     *     @type string $last_modified   Last modified timestamp.
     *     @type array  $meta_keys       List of cache-related meta keys.
     * }
     */
    public function get_status( int $post_id ): array {
        $status = array(
            'post_id'        => $post_id,
            'has_cache'      => false,
            'has_meta_cache' => false,
            'has_file_cache' => false,
            'cache_file'     => null,
            'file_size'      => 0,
            'last_modified'  => null,
            'meta_keys'      => array(),
        );

        // Check meta cache.
        $css_cache = get_post_meta( $post_id, $this->meta_prefix . self::CSS_CACHE_KEY, true );
        $file_paths = get_post_meta( $post_id, $this->meta_prefix . self::CSS_FILE_PATHS_KEY, true );
        $dependencies = get_post_meta( $post_id, $this->meta_prefix . self::DEPENDENCY_CACHE_KEY, true );

        if ( ! empty( $css_cache ) ) {
            $status['has_meta_cache'] = true;
            $status['meta_keys'][]    = $this->meta_prefix . self::CSS_CACHE_KEY;
        }

        if ( ! empty( $file_paths ) ) {
            $status['meta_keys'][] = $this->meta_prefix . self::CSS_FILE_PATHS_KEY;
        }

        if ( ! empty( $dependencies ) ) {
            $status['meta_keys'][] = $this->meta_prefix . self::DEPENDENCY_CACHE_KEY;
        }

        // Check for file cache.
        $cache_file = $this->get_cache_file_path( $post_id );

        if ( $cache_file && file_exists( $cache_file ) ) {
            $status['has_file_cache'] = true;
            $status['cache_file']     = $cache_file;
            $status['file_size']      = filesize( $cache_file );
            $status['last_modified']  = gmdate( 'Y-m-d\TH:i:s\Z', filemtime( $cache_file ) );
        }

        $status['has_cache'] = $status['has_meta_cache'] || $status['has_file_cache'];

        /**
         * Filters the cache status for a post.
         *
         * @since 1.0.0
         * @param array $status  The cache status array.
         * @param int   $post_id The post ID.
         */
        return apply_filters( 'oxybridge_cache_status', $status, $post_id );
    }

    /**
     * Clear CSS cache for a post.
     *
     * Removes all CSS cache data for a post including meta and file cache.
     *
     * @since 1.0.0
     *
     * @param int  $post_id       The post ID.
     * @param bool $clear_file    Whether to delete cache file. Default true.
     * @param bool $clear_meta    Whether to delete meta cache. Default true.
     * @return array {
     *     Clear operation result.
     *
     *     @type bool  $success      Whether clearing succeeded.
     *     @type int   $post_id      The post ID.
     *     @type bool  $meta_cleared Whether meta was cleared.
     *     @type bool  $file_cleared Whether file was cleared.
     *     @type array $cleared_keys List of cleared meta keys.
     * }
     */
    public function clear( int $post_id, bool $clear_file = true, bool $clear_meta = true ): array {
        $result = array(
            'success'      => false,
            'post_id'      => $post_id,
            'meta_cleared' => false,
            'file_cleared' => false,
            'cleared_keys' => array(),
        );

        // Clear meta cache.
        if ( $clear_meta ) {
            $meta_keys = array(
                $this->meta_prefix . self::CSS_CACHE_KEY,
                $this->meta_prefix . self::CSS_FILE_PATHS_KEY,
                $this->meta_prefix . self::DEPENDENCY_CACHE_KEY,
            );

            foreach ( $meta_keys as $key ) {
                if ( delete_post_meta( $post_id, $key ) ) {
                    $result['cleared_keys'][] = $key;
                }
            }

            $result['meta_cleared'] = ! empty( $result['cleared_keys'] );
        }

        // Clear file cache.
        if ( $clear_file ) {
            $cache_file = $this->get_cache_file_path( $post_id );

            if ( $cache_file && file_exists( $cache_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                if ( unlink( $cache_file ) ) {
                    $result['file_cleared'] = true;
                }
            } else {
                // No file to clear counts as success.
                $result['file_cleared'] = true;
            }
        }

        $result['success'] = ( ! $clear_meta || $result['meta_cleared'] ) &&
                            ( ! $clear_file || $result['file_cleared'] );

        /**
         * Fires after cache has been cleared for a post.
         *
         * @since 1.0.0
         * @param int   $post_id The post ID.
         * @param array $result  The clear operation result.
         */
        do_action( 'oxybridge_cache_cleared', $post_id, $result );

        return $result;
    }

    /**
     * Clear CSS cache for all posts.
     *
     * Removes CSS cache data for all posts with Oxygen/Breakdance content.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Clear arguments.
     *
     *     @type string $post_type  Filter by post type. Default all types.
     *     @type bool   $clear_file Whether to delete cache files. Default true.
     *     @type bool   $clear_meta Whether to delete meta cache. Default true.
     * }
     * @return array {
     *     Bulk clear results.
     *
     *     @type bool $success   Whether all clears succeeded.
     *     @type int  $total     Total posts processed.
     *     @type int  $succeeded Number of successful clears.
     *     @type int  $failed    Number of failed clears.
     * }
     */
    public function clear_all( array $args = array() ): array {
        $defaults = array(
            'post_type'  => '',
            'clear_file' => true,
            'clear_meta' => true,
        );

        $args = wp_parse_args( $args, $defaults );

        $response = array(
            'success'   => true,
            'total'     => 0,
            'succeeded' => 0,
            'failed'    => 0,
            'results'   => array(),
        );

        // Get posts with Oxygen content.
        $post_ids = $this->get_posts_with_oxygen_content( $args['post_type'] );
        $response['total'] = count( $post_ids );

        foreach ( $post_ids as $post_id ) {
            $result = $this->clear( $post_id, $args['clear_file'], $args['clear_meta'] );
            $response['results'][] = $result;

            if ( $result['success'] ) {
                $response['succeeded']++;
            } else {
                $response['failed']++;
            }
        }

        $response['success'] = $response['failed'] === 0;

        /**
         * Fires after bulk cache clear completes.
         *
         * @since 1.0.0
         * @param int $total     Total posts processed.
         * @param int $succeeded Number of successful clears.
         * @param int $failed    Number of failed clears.
         */
        do_action( 'oxybridge_cache_bulk_cleared', $response['total'], $response['succeeded'], $response['failed'] );

        return $response;
    }

    /**
     * Get CSS content for a post.
     *
     * Retrieves the generated CSS content for a post from either meta
     * or file cache.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return string The CSS content or empty string if not found.
     */
    public function get_css( int $post_id ): string {
        // Try meta cache first.
        $css = get_post_meta( $post_id, $this->meta_prefix . self::CSS_CACHE_KEY, true );

        if ( ! empty( $css ) && is_string( $css ) ) {
            return $css;
        }

        // Try alternate meta key.
        $css = get_post_meta( $post_id, $this->meta_prefix . 'css', true );

        if ( ! empty( $css ) && is_string( $css ) ) {
            return $css;
        }

        // Try file cache.
        $cache_file = $this->get_cache_file_path( $post_id );

        if ( $cache_file && file_exists( $cache_file ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $file_css = file_get_contents( $cache_file );

            if ( ! empty( $file_css ) ) {
                return $file_css;
            }
        }

        return '';
    }

    /**
     * Get cache statistics.
     *
     * Returns overall statistics about the CSS cache state including
     * total cached posts, total file size, and average cache age.
     *
     * @since 1.0.0
     *
     * @param array $args {
     *     Optional. Statistics arguments.
     *
     *     @type string $post_type Filter by post type. Default all types.
     * }
     * @return array {
     *     Cache statistics.
     *
     *     @type int    $total_posts          Total posts with Oxygen content.
     *     @type int    $cached_posts         Posts with cache.
     *     @type int    $uncached_posts       Posts without cache.
     *     @type int    $meta_cached_posts    Posts with meta cache.
     *     @type int    $file_cached_posts    Posts with file cache.
     *     @type int    $total_file_size      Total cache file size in bytes.
     *     @type string $total_file_size_human Human readable file size.
     *     @type string $oldest_cache         Oldest cache timestamp.
     *     @type string $newest_cache         Newest cache timestamp.
     *     @type float  $cache_coverage       Percentage of posts with cache.
     * }
     */
    public function get_statistics( array $args = array() ): array {
        $defaults = array(
            'post_type' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $stats = array(
            'total_posts'           => 0,
            'cached_posts'          => 0,
            'uncached_posts'        => 0,
            'meta_cached_posts'     => 0,
            'file_cached_posts'     => 0,
            'total_file_size'       => 0,
            'total_file_size_human' => '0 B',
            'oldest_cache'          => null,
            'newest_cache'          => null,
            'cache_coverage'        => 0.0,
        );

        // Get posts with Oxygen content.
        $post_ids = $this->get_posts_with_oxygen_content( $args['post_type'] );
        $stats['total_posts'] = count( $post_ids );

        if ( $stats['total_posts'] === 0 ) {
            return $stats;
        }

        $oldest_time = null;
        $newest_time = null;

        foreach ( $post_ids as $post_id ) {
            $status = $this->get_status( $post_id );

            if ( $status['has_cache'] ) {
                $stats['cached_posts']++;
            }

            if ( $status['has_meta_cache'] ) {
                $stats['meta_cached_posts']++;
            }

            if ( $status['has_file_cache'] ) {
                $stats['file_cached_posts']++;
                $stats['total_file_size'] += $status['file_size'];

                // Track oldest/newest.
                if ( $status['last_modified'] ) {
                    $modified_time = strtotime( $status['last_modified'] );

                    if ( null === $oldest_time || $modified_time < $oldest_time ) {
                        $oldest_time = $modified_time;
                        $stats['oldest_cache'] = $status['last_modified'];
                    }

                    if ( null === $newest_time || $modified_time > $newest_time ) {
                        $newest_time = $modified_time;
                        $stats['newest_cache'] = $status['last_modified'];
                    }
                }
            }
        }

        $stats['uncached_posts']        = $stats['total_posts'] - $stats['cached_posts'];
        $stats['total_file_size_human'] = size_format( $stats['total_file_size'], 2 );
        $stats['cache_coverage']        = $stats['total_posts'] > 0
            ? round( ( $stats['cached_posts'] / $stats['total_posts'] ) * 100, 2 )
            : 0.0;

        /**
         * Filters the cache statistics.
         *
         * @since 1.0.0
         * @param array $stats The statistics array.
         * @param array $args  The arguments passed to get_statistics.
         */
        return apply_filters( 'oxybridge_cache_statistics', $stats, $args );
    }

    /**
     * Check if CSS regeneration is available.
     *
     * Verifies that the Oxygen/Breakdance cache regeneration function exists.
     *
     * @since 1.0.0
     *
     * @return bool True if regeneration is available, false otherwise.
     */
    public function is_regeneration_available(): bool {
        return function_exists( '\Breakdance\Render\generateCacheForPost' );
    }

    /**
     * Get cache directory path.
     *
     * Returns the path to the CSS cache directory.
     *
     * @since 1.0.0
     *
     * @return string The cache directory path.
     */
    public function get_cache_directory(): string {
        $upload_dir = wp_upload_dir();

        // Determine the correct subdirectory based on active builder.
        if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
            $subdir = 'oxygen';
        } else {
            $subdir = 'breakdance';
        }

        return trailingslashit( $upload_dir['basedir'] ) . $subdir . '/css/';
    }

    /**
     * Get the cache file path for a post.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return string|null The cache file path or null if not determinable.
     */
    public function get_cache_file_path( int $post_id ): ?string {
        $upload_dir = wp_upload_dir();

        // Check possible cache file locations.
        $possible_paths = array(
            $upload_dir['basedir'] . '/breakdance/css/post-' . $post_id . '.css',
            $upload_dir['basedir'] . '/breakdance/css/' . $post_id . '.css',
            $upload_dir['basedir'] . '/oxygen/css/post-' . $post_id . '.css',
            $upload_dir['basedir'] . '/oxygen/css/' . $post_id . '.css',
        );

        foreach ( $possible_paths as $path ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        // Return the expected path based on meta prefix.
        $cache_dir = $this->get_cache_directory();
        return $cache_dir . 'post-' . $post_id . '.css';
    }

    /**
     * Get the cache file URL for a post.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return string|null The cache file URL or null if not found.
     */
    public function get_cache_file_url( int $post_id ): ?string {
        $cache_file = $this->get_cache_file_path( $post_id );

        if ( ! $cache_file || ! file_exists( $cache_file ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();

        // Convert file path to URL.
        $url = str_replace(
            $upload_dir['basedir'],
            $upload_dir['baseurl'],
            $cache_file
        );

        return $url;
    }

    /**
     * Get posts with Oxygen/Breakdance content.
     *
     * Queries for all posts that have Oxygen/Breakdance design data.
     *
     * @since 1.0.0
     *
     * @param string       $post_type  Optional. Filter by post type.
     * @param array|string $status     Optional. Post status(es). Default 'any'.
     * @return array Array of post IDs.
     */
    public function get_posts_with_oxygen_content( string $post_type = '', $status = 'any' ): array {
        $query_args = array(
            'posts_per_page' => -1,
            'post_status'    => $status,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'OR',
                // Modern Oxygen 6 / Breakdance format.
                array(
                    'key'     => $this->meta_prefix . 'data',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen format (ct_builder_json).
                array(
                    'key'     => 'ct_builder_json',
                    'compare' => 'EXISTS',
                ),
                // Classic Oxygen shortcodes format.
                array(
                    'key'     => 'ct_builder_shortcodes',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        // Handle post type filtering.
        if ( ! empty( $post_type ) && post_type_exists( $post_type ) ) {
            $query_args['post_type'] = sanitize_key( $post_type );
        } else {
            // Query all public post types plus template types.
            $public_types   = get_post_types( array( 'public' => true ), 'names' );
            $template_types = $this->get_template_post_types();
            $all_types      = array_unique( array_merge( $public_types, $template_types ) );
            $query_args['post_type'] = array_values( $all_types );
        }

        $query = new \WP_Query( $query_args );

        return $query->posts;
    }

    /**
     * Invalidate cache for a post.
     *
     * Marks the cache as stale without removing it. Useful for triggering
     * regeneration on next page load.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return bool True if invalidation succeeded, false otherwise.
     */
    public function invalidate( int $post_id ): bool {
        // Update a timestamp meta to indicate cache is stale.
        $result = update_post_meta(
            $post_id,
            $this->meta_prefix . 'cache_invalidated_at',
            current_time( 'mysql', true )
        );

        /**
         * Fires after cache has been invalidated for a post.
         *
         * @since 1.0.0
         * @param int $post_id The post ID.
         */
        do_action( 'oxybridge_cache_invalidated', $post_id );

        return (bool) $result;
    }

    /**
     * Check if cache is stale for a post.
     *
     * Determines if the cache needs to be regenerated based on
     * invalidation timestamp or other factors.
     *
     * @since 1.0.0
     *
     * @param int $post_id The post ID.
     * @return bool True if cache is stale, false otherwise.
     */
    public function is_stale( int $post_id ): bool {
        // Check invalidation timestamp.
        $invalidated_at = get_post_meta( $post_id, $this->meta_prefix . 'cache_invalidated_at', true );

        if ( empty( $invalidated_at ) ) {
            return false;
        }

        // Get cache file modification time.
        $cache_file = $this->get_cache_file_path( $post_id );

        if ( ! $cache_file || ! file_exists( $cache_file ) ) {
            return true;
        }

        $cache_time       = filemtime( $cache_file );
        $invalidated_time = strtotime( $invalidated_at );

        return $invalidated_time > $cache_time;
    }

    /**
     * Get the current meta prefix.
     *
     * @since 1.0.0
     *
     * @return string The meta prefix.
     */
    public function get_meta_prefix(): string {
        return $this->meta_prefix;
    }

    /**
     * Determine the meta prefix for Oxygen/Breakdance data.
     *
     * @since 1.0.0
     *
     * @return string The meta prefix.
     */
    private function determine_meta_prefix(): string {
        // Try to get prefix from Breakdance.
        if ( function_exists( '\Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
            $prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox( 'meta_prefix' );
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
     * Get template post types.
     *
     * Returns an array of post types used for templates.
     *
     * @since 1.0.0
     *
     * @return array Array of template post type names.
     */
    private function get_template_post_types(): array {
        $types = array();

        // Oxygen 6 template types.
        $oxygen_types = array(
            'oxygen_template',
            'oxygen_header',
            'oxygen_footer',
            'oxygen_block',
            'oxygen_part',
        );

        // Breakdance template types.
        $breakdance_types = array(
            'breakdance_template',
            'breakdance_header',
            'breakdance_footer',
            'breakdance_block',
            'breakdance_popup',
            'breakdance_part',
        );

        // Classic Oxygen type.
        $classic_types = array(
            'ct_template',
            'oxy_user_library',
        );

        $all_types = array_merge( $oxygen_types, $breakdance_types, $classic_types );

        // Only return types that exist.
        foreach ( $all_types as $type ) {
            if ( post_type_exists( $type ) ) {
                $types[] = $type;
            }
        }

        return $types;
    }
}
