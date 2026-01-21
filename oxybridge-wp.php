<?php
/**
 * Plugin Name: Oxybridge WP
 * Plugin URI: https://github.com/your-repo/oxybridge
 * Description: MCP (Model Context Protocol) bridge for Oxygen Builder - Enables AI assistants to read and understand Oxygen templates.
 * Author: Oxybridge Contributors
 * Version: 1.0.0
 * Author URI: https://github.com/your-repo/oxybridge
 * Text Domain: oxybridge-wp
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 *
 * @package Oxybridge
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin constants.
 */
define( 'OXYBRIDGE_VERSION', '1.0.0' );
define( 'OXYBRIDGE_PLUGIN_FILE', __FILE__ );
define( 'OXYBRIDGE_PLUGIN_DIR', __DIR__ );
define( 'OXYBRIDGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OXYBRIDGE_MIN_PHP_VERSION', '7.4' );
define( 'OXYBRIDGE_MIN_WP_VERSION', '5.9' );

/**
 * PHP version check.
 * Deactivate plugin and show admin notice if PHP version is too low.
 */
if ( ! version_compare( PHP_VERSION, OXYBRIDGE_MIN_PHP_VERSION, '>=' ) ) {
    add_action( 'admin_notices', 'oxybridge_php_version_notice' );
    add_action( 'admin_init', 'oxybridge_deactivate_self' );
    return;
}

/**
 * Display PHP version notice.
 *
 * @return void
 */
function oxybridge_php_version_notice() {
    $message = sprintf(
        /* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version */
        esc_html__( '%1$s requires PHP version %2$s or higher. You are running PHP %3$s. Please upgrade PHP to use this plugin.', 'oxybridge-wp' ),
        '<strong>Oxybridge WP</strong>',
        OXYBRIDGE_MIN_PHP_VERSION,
        PHP_VERSION
    );
    echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
}

/**
 * Deactivate plugin.
 *
 * @return void
 */
function oxybridge_deactivate_self() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    deactivate_plugins( OXYBRIDGE_PLUGIN_FILE );

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['activate'] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        unset( $_GET['activate'] );
    }
}

/**
 * Load the autoloader.
 */
require_once OXYBRIDGE_PLUGIN_DIR . '/includes/class-autoloader.php';

/**
 * Initialize the plugin.
 *
 * Fires on 'plugins_loaded' to ensure all dependencies are available.
 *
 * @return void
 */
function oxybridge_init() {
    // Initialize admin page (works regardless of Oxygen status for informational purposes).
    if ( is_admin() && class_exists( 'Oxybridge\\Admin_Page' ) ) {
        new \Oxybridge\Admin_Page();
    }

    // Initialize Server Manager for MCP server process management (admin AJAX handlers).
    if ( is_admin() && class_exists( 'Oxybridge\\Server_Manager' ) ) {
        new \Oxybridge\Server_Manager();
    }

    // Check if Oxygen/Breakdance is active.
    if ( ! oxybridge_is_oxygen_active() ) {
        add_action( 'admin_notices', 'oxybridge_oxygen_missing_notice' );
        return;
    }

    // Initialize plugin components.
    do_action( 'oxybridge_before_init' );

    // Load REST API class.
    if ( class_exists( 'Oxybridge\\REST_API' ) ) {
        new \Oxybridge\REST_API();
    }

    do_action( 'oxybridge_loaded' );
}
add_action( 'plugins_loaded', 'oxybridge_init', 20 );

/**
 * Check if Oxygen Builder is active.
 *
 * @return bool True if Oxygen is active, false otherwise.
 */
function oxybridge_is_oxygen_active() {
    // Check for Oxygen 6 (Breakdance-based) constants.
    if ( defined( '__BREAKDANCE_VERSION' ) && defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
        return true;
    }

    // Check for classic Oxygen (pre-6.0).
    if ( defined( 'CT_VERSION' ) ) {
        return true;
    }

    // Check if plugin files exist.
    if ( function_exists( 'is_plugin_active' ) ) {
        return is_plugin_active( 'oxygen/plugin.php' ) || is_plugin_active( 'oxygen/ct-oxygen.php' );
    }

    return false;
}

/**
 * Display Oxygen missing notice.
 *
 * @return void
 */
function oxybridge_oxygen_missing_notice() {
    $message = sprintf(
        /* translators: 1: Plugin name, 2: Oxygen Builder name */
        esc_html__( '%1$s requires %2$s to be installed and activated. Please install and activate Oxygen Builder first.', 'oxybridge-wp' ),
        '<strong>Oxybridge WP</strong>',
        '<strong>Oxygen Builder</strong>'
    );
    echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
}

/**
 * Plugin activation hook.
 *
 * @return void
 */
function oxybridge_activate() {
    // Activation tasks (e.g., create options, flush rewrite rules).
    update_option( 'oxybridge_version', OXYBRIDGE_VERSION );
    update_option( 'oxybridge_activated', time() );

    // Flush rewrite rules for REST API routes.
    flush_rewrite_rules();
}
register_activation_hook( OXYBRIDGE_PLUGIN_FILE, 'oxybridge_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function oxybridge_deactivate() {
    // Cleanup tasks if needed.
    flush_rewrite_rules();
}
register_deactivation_hook( OXYBRIDGE_PLUGIN_FILE, 'oxybridge_deactivate' );
