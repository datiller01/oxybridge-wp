<?php
/**
 * CSS Cache Verification Script
 *
 * This script verifies that CSS regeneration works correctly for a given post.
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/verify-css-cache.php -- 123
 * Where 123 is the post ID to test.
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    echo "This script must be run via WP-CLI.\n";
    echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/verify-css-cache.php -- <post_id>\n";
    exit( 1 );
}

// Get post ID from args.
global $argv;
$post_id = isset( $argv[1] ) ? intval( $argv[1] ) : 0;

if ( $post_id < 1 ) {
    WP_CLI::error( 'Please provide a valid post ID as the first argument.' );
}

// Determine meta prefix.
function get_oxybridge_meta_prefix() {
    if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
        $prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox( 'meta_prefix' );
        if ( is_string( $prefix ) ) {
            return $prefix;
        }
    }

    if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
        return 'oxygen_';
    }

    return 'breakdance_';
}

$meta_prefix = get_oxybridge_meta_prefix();

WP_CLI::log( '=============================================' );
WP_CLI::log( 'CSS Cache Verification for Post #' . $post_id );
WP_CLI::log( '=============================================' );
WP_CLI::log( '' );

// Check if post exists.
$post = get_post( $post_id );
if ( ! $post ) {
    WP_CLI::error( 'Post not found.' );
}

WP_CLI::log( 'Post Title: ' . $post->post_title );
WP_CLI::log( 'Post Type: ' . $post->post_type );
WP_CLI::log( 'Meta Prefix: ' . $meta_prefix );
WP_CLI::log( '' );

// Check for builder content.
$has_builder_data = get_post_meta( $post_id, $meta_prefix . 'data', true );
$has_classic_data = get_post_meta( $post_id, 'ct_builder_json', true );

if ( empty( $has_builder_data ) && empty( $has_classic_data ) ) {
    WP_CLI::warning( 'Post does not appear to have Oxygen/Breakdance content.' );
}

// Get current CSS cache.
WP_CLI::log( '=== BEFORE Regeneration ===' );
WP_CLI::log( '' );

$css_cache_key = $meta_prefix . 'css_file_paths_cache';
$dep_cache_key = $meta_prefix . 'dependency_cache';

$css_cache_before = get_post_meta( $post_id, $css_cache_key, true );
$dep_cache_before = get_post_meta( $post_id, $dep_cache_key, true );

WP_CLI::log( 'CSS Cache Meta (' . $css_cache_key . '):' );
if ( empty( $css_cache_before ) ) {
    WP_CLI::log( '  (empty - no cache exists)' );
} else {
    if ( is_array( $css_cache_before ) ) {
        foreach ( $css_cache_before as $key => $value ) {
            WP_CLI::log( '  ' . $key . ': ' . $value );
        }
    } else {
        WP_CLI::log( '  ' . print_r( $css_cache_before, true ) );
    }
}
WP_CLI::log( '' );

// Extract version hashes.
$hash_before = [];
if ( is_array( $css_cache_before ) ) {
    foreach ( $css_cache_before as $key => $value ) {
        if ( preg_match( '/\?v=([a-f0-9]+)/', $value, $matches ) ) {
            $hash_before[ $key ] = $matches[1];
        }
    }
}

WP_CLI::log( '=== Regenerating CSS Cache ===' );
WP_CLI::log( '' );

// Check if regeneration function exists.
if ( ! function_exists( '\Breakdance\Render\generateCacheForPost' ) ) {
    WP_CLI::error( 'Breakdance\\Render\\generateCacheForPost() function not found. Oxygen/Breakdance may not be active.' );
}

// Regenerate CSS.
$start_time = microtime( true );

try {
    \Breakdance\Render\generateCacheForPost( $post_id );
    $duration = round( ( microtime( true ) - $start_time ) * 1000 );
    WP_CLI::success( 'CSS regenerated successfully in ' . $duration . 'ms' );
} catch ( \Exception $e ) {
    WP_CLI::error( 'Regeneration failed: ' . $e->getMessage() );
} catch ( \Error $e ) {
    WP_CLI::error( 'Regeneration error: ' . $e->getMessage() );
}

WP_CLI::log( '' );

// Get CSS cache after regeneration.
WP_CLI::log( '=== AFTER Regeneration ===' );
WP_CLI::log( '' );

// Clear any object cache.
wp_cache_flush();

$css_cache_after = get_post_meta( $post_id, $css_cache_key, true );
$dep_cache_after = get_post_meta( $post_id, $dep_cache_key, true );

WP_CLI::log( 'CSS Cache Meta (' . $css_cache_key . '):' );
if ( empty( $css_cache_after ) ) {
    WP_CLI::log( '  (empty - regeneration may have failed)' );
} else {
    if ( is_array( $css_cache_after ) ) {
        foreach ( $css_cache_after as $key => $value ) {
            WP_CLI::log( '  ' . $key . ': ' . $value );
        }
    } else {
        WP_CLI::log( '  ' . print_r( $css_cache_after, true ) );
    }
}
WP_CLI::log( '' );

// Extract version hashes after.
$hash_after = [];
if ( is_array( $css_cache_after ) ) {
    foreach ( $css_cache_after as $key => $value ) {
        if ( preg_match( '/\?v=([a-f0-9]+)/', $value, $matches ) ) {
            $hash_after[ $key ] = $matches[1];
        }
    }
}

// Compare.
WP_CLI::log( '=== Verification Results ===' );
WP_CLI::log( '' );

$cache_changed = ( $css_cache_before !== $css_cache_after );
$has_cache     = ! empty( $css_cache_after );

if ( $has_cache ) {
    WP_CLI::success( 'CSS cache exists after regeneration' );
} else {
    WP_CLI::warning( 'CSS cache is empty - page may have no renderable elements' );
}

if ( $cache_changed ) {
    WP_CLI::success( 'CSS cache was updated' );
} else {
    WP_CLI::log( 'CSS cache unchanged (content may be identical)' );
}

// Compare hashes.
WP_CLI::log( '' );
WP_CLI::log( 'Version Hash Comparison:' );
foreach ( array_unique( array_merge( array_keys( $hash_before ), array_keys( $hash_after ) ) ) as $key ) {
    $before = isset( $hash_before[ $key ] ) ? substr( $hash_before[ $key ], 0, 8 ) . '...' : '(none)';
    $after  = isset( $hash_after[ $key ] ) ? substr( $hash_after[ $key ], 0, 8 ) . '...' : '(none)';
    $status = ( $before === $after ) ? '(unchanged)' : '(CHANGED)';
    WP_CLI::log( "  $key: $before -> $after $status" );
}

// Check dependency cache.
WP_CLI::log( '' );
$dep_has_data = ! empty( $dep_cache_after ) && is_array( $dep_cache_after );
if ( $dep_has_data ) {
    WP_CLI::success( 'Dependency cache exists' );
} else {
    WP_CLI::warning( 'Dependency cache is empty' );
}

WP_CLI::log( '' );
WP_CLI::log( '=============================================' );
WP_CLI::log( 'Verification Complete' );
WP_CLI::log( '=============================================' );
WP_CLI::log( '' );

// Final summary.
$all_passed = $has_cache;
if ( $all_passed ) {
    WP_CLI::success( 'All checks passed!' );
} else {
    WP_CLI::warning( 'Some checks failed - review output above.' );
}

WP_CLI::log( '' );
WP_CLI::log( 'Next step: Open the page in a browser to verify visual styles:' );
WP_CLI::log( '  ' . get_permalink( $post_id ) );
