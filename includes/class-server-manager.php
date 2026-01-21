<?php
/**
 * Server Manager class for Oxybridge.
 *
 * Handles MCP server process management including installation, launch, stop, and status checking.
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
 * Class Server_Manager
 *
 * Manages the oxybridge-mcp Node.js server process from the WordPress admin.
 * Provides methods for installing dependencies, launching/stopping the server,
 * and checking server status.
 *
 * @since 1.0.0
 */
class Server_Manager {

    /**
     * WordPress option key for storing the server PID.
     *
     * @var string
     */
    const PID_OPTION = 'oxybridge_mcp_server_pid';

    /**
     * Relative path from plugin directory to the MCP server directory.
     *
     * @var string
     */
    const MCP_DIR_RELATIVE = '../../../oxybridge-mcp';

    /**
     * Required capability to manage the server.
     *
     * @var string
     */
    const REQUIRED_CAPABILITY = 'manage_options';

    /**
     * AJAX nonce action name.
     *
     * @var string
     */
    const NONCE_ACTION = 'oxybridge_server_nonce';

    /**
     * Constructor.
     *
     * Registers AJAX action hooks for server management operations.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'wp_ajax_oxybridge_install_deps', array( $this, 'ajax_install_deps' ) );
        add_action( 'wp_ajax_oxybridge_launch_server', array( $this, 'ajax_launch_server' ) );
        add_action( 'wp_ajax_oxybridge_stop_server', array( $this, 'ajax_stop_server' ) );
        add_action( 'wp_ajax_oxybridge_server_status', array( $this, 'ajax_server_status' ) );
    }

    /**
     * Get the absolute path to the MCP server directory.
     *
     * @since 1.0.0
     *
     * @return string The absolute path to the oxybridge-mcp directory.
     */
    public function get_mcp_directory(): string {
        $plugin_dir = defined( 'OXYBRIDGE_PLUGIN_DIR' ) ? OXYBRIDGE_PLUGIN_DIR : __DIR__ . '/..';
        $mcp_dir    = realpath( $plugin_dir . '/' . self::MCP_DIR_RELATIVE );

        // If realpath fails (directory doesn't exist), construct the path manually.
        if ( false === $mcp_dir ) {
            $mcp_dir = $plugin_dir . '/' . self::MCP_DIR_RELATIVE;
        }

        return $mcp_dir;
    }

    /**
     * Check if the MCP server process is currently running.
     *
     * Validates the stored PID to ensure the process is actually running.
     *
     * @since 1.0.0
     *
     * @return bool True if the server is running, false otherwise.
     */
    public function is_server_running(): bool {
        $pid = $this->get_stored_pid();

        if ( empty( $pid ) ) {
            return false;
        }

        return $this->is_process_running( $pid );
    }

    /**
     * Get the comprehensive server status.
     *
     * Returns an array with status information including running state,
     * directory paths, and dependency status.
     *
     * @since 1.0.0
     *
     * @return array {
     *     Server status information.
     *
     *     @type bool   $running           Whether the server is currently running.
     *     @type int    $pid               The server process ID (0 if not running).
     *     @type string $mcp_directory     Absolute path to the MCP directory.
     *     @type bool   $directory_exists  Whether the MCP directory exists.
     *     @type bool   $is_built          Whether dist/index.js exists.
     *     @type bool   $is_installed      Whether node_modules exists.
     *     @type bool   $node_available    Whether Node.js is available.
     *     @type bool   $npm_available     Whether npm is available.
     *     @type string $node_version      Node.js version if available.
     *     @type string $npm_version       npm version if available.
     * }
     */
    public function get_server_status(): array {
        $mcp_dir = $this->get_mcp_directory();
        $pid     = $this->get_stored_pid();
        $running = $this->is_server_running();

        // Check Node.js and npm availability.
        $node_version = $this->get_command_version( 'node' );
        $npm_version  = $this->get_command_version( 'npm' );

        return array(
            'running'          => $running,
            'pid'              => $running ? $pid : 0,
            'mcp_directory'    => $mcp_dir,
            'directory_exists' => is_dir( $mcp_dir ),
            'is_built'         => file_exists( $mcp_dir . '/dist/index.js' ),
            'is_installed'     => is_dir( $mcp_dir . '/node_modules' ),
            'node_available'   => ! empty( $node_version ),
            'npm_available'    => ! empty( $npm_version ),
            'node_version'     => $node_version,
            'npm_version'      => $npm_version,
        );
    }

    /**
     * Install npm dependencies for the MCP server.
     *
     * Executes `npm install` in the MCP server directory.
     *
     * @since 1.0.0
     *
     * @return array {
     *     Installation result.
     *
     *     @type bool   $success Whether the installation succeeded.
     *     @type string $output  Command output.
     *     @type string $error   Error message if failed.
     * }
     */
    public function install_dependencies(): array {
        $mcp_dir = $this->get_mcp_directory();

        // Validate directory exists.
        if ( ! is_dir( $mcp_dir ) ) {
            return array(
                'success' => false,
                'output'  => '',
                'error'   => sprintf(
                    /* translators: %s: MCP directory path */
                    __( 'MCP directory not found: %s', 'oxybridge-wp' ),
                    $mcp_dir
                ),
            );
        }

        // Check npm availability.
        if ( empty( $this->get_command_version( 'npm' ) ) ) {
            return array(
                'success' => false,
                'output'  => '',
                'error'   => __( 'npm is not installed or not available in PATH.', 'oxybridge-wp' ),
            );
        }

        // Execute npm install.
        $command = sprintf(
            'cd %s && npm install 2>&1',
            escapeshellarg( $mcp_dir )
        );

        $output      = array();
        $return_code = 0;

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( $command, $output, $return_code );

        $output_string = implode( "\n", $output );

        if ( 0 !== $return_code ) {
            return array(
                'success' => false,
                'output'  => $output_string,
                'error'   => sprintf(
                    /* translators: %d: Return code */
                    __( 'npm install failed with exit code %d', 'oxybridge-wp' ),
                    $return_code
                ),
            );
        }

        return array(
            'success' => true,
            'output'  => $output_string,
            'error'   => '',
        );
    }

    /**
     * Launch the MCP server.
     *
     * Starts the server as a background process and stores the PID.
     *
     * @since 1.0.0
     *
     * @return array {
     *     Launch result.
     *
     *     @type bool   $success Whether the server started successfully.
     *     @type int    $pid     The server process ID.
     *     @type string $error   Error message if failed.
     * }
     */
    public function launch_server(): array {
        // Check if server is already running.
        if ( $this->is_server_running() ) {
            return array(
                'success' => false,
                'pid'     => $this->get_stored_pid(),
                'error'   => __( 'Server is already running.', 'oxybridge-wp' ),
            );
        }

        $mcp_dir = $this->get_mcp_directory();

        // Validate directory exists.
        if ( ! is_dir( $mcp_dir ) ) {
            return array(
                'success' => false,
                'pid'     => 0,
                'error'   => sprintf(
                    /* translators: %s: MCP directory path */
                    __( 'MCP directory not found: %s', 'oxybridge-wp' ),
                    $mcp_dir
                ),
            );
        }

        // Check if built.
        if ( ! file_exists( $mcp_dir . '/dist/index.js' ) ) {
            return array(
                'success' => false,
                'pid'     => 0,
                'error'   => __( 'Server not built. Please run Install Dependencies first, which will run npm install and build the project.', 'oxybridge-wp' ),
            );
        }

        // Check Node.js availability.
        if ( empty( $this->get_command_version( 'node' ) ) ) {
            return array(
                'success' => false,
                'pid'     => 0,
                'error'   => __( 'Node.js is not installed or not available in PATH.', 'oxybridge-wp' ),
            );
        }

        // Launch server as a background process.
        // Using nohup to ensure process continues after parent exits.
        // Redirecting output to a log file.
        $log_file = $mcp_dir . '/server.log';
        $pid_file = $mcp_dir . '/server.pid';

        $command = sprintf(
            'cd %s && nohup node dist/index.js > %s 2>&1 & echo $!',
            escapeshellarg( $mcp_dir ),
            escapeshellarg( $log_file )
        );

        $output = array();
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( $command, $output );

        $pid = isset( $output[0] ) ? absint( trim( $output[0] ) ) : 0;

        if ( empty( $pid ) ) {
            return array(
                'success' => false,
                'pid'     => 0,
                'error'   => __( 'Failed to start server. Could not obtain process ID.', 'oxybridge-wp' ),
            );
        }

        // Verify process is actually running.
        // Give it a moment to start.
        usleep( 100000 ); // 100ms.

        if ( ! $this->is_process_running( $pid ) ) {
            return array(
                'success' => false,
                'pid'     => 0,
                'error'   => __( 'Server process exited immediately. Check the server log for errors.', 'oxybridge-wp' ),
            );
        }

        // Store the PID.
        $this->store_pid( $pid );

        // Also write PID to file in MCP directory for external reference.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents( $pid_file, $pid );

        return array(
            'success' => true,
            'pid'     => $pid,
            'error'   => '',
        );
    }

    /**
     * Stop the MCP server.
     *
     * Terminates the server process gracefully.
     *
     * @since 1.0.0
     *
     * @return array {
     *     Stop result.
     *
     *     @type bool   $success Whether the server stopped successfully.
     *     @type string $error   Error message if failed.
     * }
     */
    public function stop_server(): array {
        $pid = $this->get_stored_pid();

        if ( empty( $pid ) ) {
            // Clear any stale PID.
            $this->clear_pid();

            return array(
                'success' => true,
                'error'   => '',
            );
        }

        if ( ! $this->is_process_running( $pid ) ) {
            // Process not running, just clear the PID.
            $this->clear_pid();

            return array(
                'success' => true,
                'error'   => '',
            );
        }

        // Send SIGTERM for graceful shutdown.
        $result = $this->terminate_process( $pid );

        if ( $result ) {
            $this->clear_pid();

            // Also remove PID file if it exists.
            $mcp_dir  = $this->get_mcp_directory();
            $pid_file = $mcp_dir . '/server.pid';
            if ( file_exists( $pid_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                unlink( $pid_file );
            }

            return array(
                'success' => true,
                'error'   => '',
            );
        }

        return array(
            'success' => false,
            'error'   => __( 'Failed to stop server. The process may need to be terminated manually.', 'oxybridge-wp' ),
        );
    }

    /**
     * AJAX handler for installing dependencies.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajax_install_deps(): void {
        $this->verify_ajax_request();

        $result = $this->install_dependencies();

        if ( $result['success'] ) {
            wp_send_json_success(
                array(
                    'message' => __( 'Dependencies installed successfully.', 'oxybridge-wp' ),
                    'output'  => $result['output'],
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => $result['error'],
                    'output'  => $result['output'],
                )
            );
        }
    }

    /**
     * AJAX handler for launching the server.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajax_launch_server(): void {
        $this->verify_ajax_request();

        $result = $this->launch_server();

        if ( $result['success'] ) {
            wp_send_json_success(
                array(
                    'message' => sprintf(
                        /* translators: %d: Process ID */
                        __( 'Server started successfully (PID: %d).', 'oxybridge-wp' ),
                        $result['pid']
                    ),
                    'pid'     => $result['pid'],
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => $result['error'],
                )
            );
        }
    }

    /**
     * AJAX handler for stopping the server.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajax_stop_server(): void {
        $this->verify_ajax_request();

        $result = $this->stop_server();

        if ( $result['success'] ) {
            wp_send_json_success(
                array(
                    'message' => __( 'Server stopped successfully.', 'oxybridge-wp' ),
                )
            );
        } else {
            wp_send_json_error(
                array(
                    'message' => $result['error'],
                )
            );
        }
    }

    /**
     * AJAX handler for checking server status.
     *
     * Returns comprehensive status information including server state,
     * environment details, and configuration suggestions.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajax_server_status(): void {
        $this->verify_ajax_request();

        $status = $this->get_server_status();

        // Build comprehensive response with additional context.
        $response = array_merge(
            $status,
            $this->get_additional_status_info( $status )
        );

        wp_send_json_success( $response );
    }

    /**
     * Get additional status information for comprehensive AJAX response.
     *
     * Provides human-readable messages, warnings, and configuration info.
     *
     * @since 1.0.0
     *
     * @param array $status The base server status from get_server_status().
     * @return array {
     *     Additional status information.
     *
     *     @type string $status_label      Human-readable status label (running, stopped, error).
     *     @type string $status_message    Detailed status message.
     *     @type array  $warnings          Array of warning messages.
     *     @type array  $config            WordPress/REST API configuration info for MCP.
     *     @type string $timestamp         ISO 8601 timestamp of status check.
     *     @type bool   $can_install       Whether install operation is available.
     *     @type bool   $can_launch        Whether launch operation is available.
     *     @type bool   $can_stop          Whether stop operation is available.
     * }
     */
    private function get_additional_status_info( array $status ): array {
        $warnings = array();
        $status_label   = 'stopped';
        $status_message = '';

        // Determine status label and message.
        if ( $status['running'] ) {
            $status_label   = 'running';
            $status_message = sprintf(
                /* translators: %d: Process ID */
                __( 'MCP server is running (PID: %d).', 'oxybridge-wp' ),
                $status['pid']
            );
        } elseif ( ! $status['directory_exists'] ) {
            $status_label   = 'error';
            $status_message = __( 'MCP server directory not found. Please verify the oxybridge-mcp folder exists.', 'oxybridge-wp' );
        } elseif ( ! $status['node_available'] ) {
            $status_label   = 'error';
            $status_message = __( 'Node.js is not installed or not available in PATH.', 'oxybridge-wp' );
        } elseif ( ! $status['is_installed'] ) {
            $status_label   = 'not_installed';
            $status_message = __( 'Dependencies not installed. Click "Install Dependencies" to set up the MCP server.', 'oxybridge-wp' );
        } elseif ( ! $status['is_built'] ) {
            $status_label   = 'not_built';
            $status_message = __( 'Server not built. Please run "Install Dependencies" to build the project.', 'oxybridge-wp' );
        } else {
            $status_label   = 'stopped';
            $status_message = __( 'MCP server is stopped. Click "Launch Server" to start.', 'oxybridge-wp' );
        }

        // Collect warnings.
        if ( ! $status['node_available'] ) {
            $warnings[] = __( 'Node.js is not installed or not in PATH. Install Node.js 18+ to manage the MCP server.', 'oxybridge-wp' );
        }

        if ( ! $status['npm_available'] ) {
            $warnings[] = __( 'npm is not available. npm is required to install dependencies.', 'oxybridge-wp' );
        }

        if ( $status['directory_exists'] && ! $status['is_installed'] ) {
            $warnings[] = __( 'Node modules not installed. Run "Install Dependencies" first.', 'oxybridge-wp' );
        }

        if ( $status['is_installed'] && ! $status['is_built'] ) {
            $warnings[] = __( 'Server not compiled. The dist/index.js file is missing.', 'oxybridge-wp' );
        }

        // Check for orphaned PID (PID stored but process not running).
        $stored_pid = $this->get_stored_pid();
        if ( $stored_pid > 0 && ! $status['running'] ) {
            $warnings[] = __( 'Stale process ID detected. The server may have crashed or been stopped externally.', 'oxybridge-wp' );
            // Clean up the stale PID.
            $this->clear_pid();
        }

        // Determine available operations.
        $can_install = $status['directory_exists'] && $status['npm_available'];
        $can_launch  = $status['directory_exists'] && $status['is_built'] && $status['node_available'] && ! $status['running'];
        $can_stop    = $status['running'];

        // Build configuration info for MCP server setup.
        $config = array(
            'site_url'       => get_site_url(),
            'rest_url'       => get_rest_url( null, 'oxybridge/v1/' ),
            'rest_namespace' => 'oxybridge/v1',
            'admin_url'      => admin_url(),
            'plugin_version' => defined( 'OXYBRIDGE_VERSION' ) ? OXYBRIDGE_VERSION : 'unknown',
        );

        return array(
            'status_label'   => $status_label,
            'status_message' => $status_message,
            'warnings'       => $warnings,
            'config'         => $config,
            'timestamp'      => gmdate( 'c' ),
            'can_install'    => $can_install,
            'can_launch'     => $can_launch,
            'can_stop'       => $can_stop,
        );
    }

    /**
     * Verify AJAX request has valid nonce and user capabilities.
     *
     * Exits with JSON error if verification fails.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function verify_ajax_request(): void {
        // Verify nonce.
        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Security check failed. Please refresh the page and try again.', 'oxybridge-wp' ),
                ),
                403
            );
        }

        // Check capabilities.
        if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'You do not have permission to perform this action.', 'oxybridge-wp' ),
                ),
                403
            );
        }
    }

    /**
     * Get the stored server PID from WordPress options.
     *
     * @since 1.0.0
     *
     * @return int The stored PID, or 0 if not set.
     */
    private function get_stored_pid(): int {
        return absint( get_option( self::PID_OPTION, 0 ) );
    }

    /**
     * Store the server PID in WordPress options.
     *
     * @since 1.0.0
     *
     * @param int $pid The process ID to store.
     * @return bool True if option was updated successfully.
     */
    private function store_pid( int $pid ): bool {
        return update_option( self::PID_OPTION, $pid );
    }

    /**
     * Clear the stored server PID.
     *
     * @since 1.0.0
     *
     * @return bool True if option was deleted successfully.
     */
    private function clear_pid(): bool {
        return delete_option( self::PID_OPTION );
    }

    /**
     * Check if a process with the given PID is running.
     *
     * @since 1.0.0
     *
     * @param int $pid The process ID to check.
     * @return bool True if the process is running.
     */
    private function is_process_running( int $pid ): bool {
        if ( empty( $pid ) ) {
            return false;
        }

        // On Unix-like systems, check if process exists.
        // Using kill with signal 0 to check existence without actually killing.
        $output      = array();
        $return_code = 0;

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( sprintf( 'kill -0 %d 2>/dev/null', $pid ), $output, $return_code );

        return 0 === $return_code;
    }

    /**
     * Terminate a process by PID.
     *
     * Attempts graceful termination (SIGTERM) first, then forceful (SIGKILL) if needed.
     *
     * @since 1.0.0
     *
     * @param int $pid The process ID to terminate.
     * @return bool True if process was terminated.
     */
    private function terminate_process( int $pid ): bool {
        if ( empty( $pid ) ) {
            return false;
        }

        // Send SIGTERM for graceful shutdown.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( sprintf( 'kill -15 %d 2>/dev/null', $pid ) );

        // Wait a moment for graceful shutdown.
        usleep( 500000 ); // 500ms.

        // Check if process is still running.
        if ( ! $this->is_process_running( $pid ) ) {
            return true;
        }

        // Force kill if still running.
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( sprintf( 'kill -9 %d 2>/dev/null', $pid ) );

        // Give it a moment.
        usleep( 100000 ); // 100ms.

        return ! $this->is_process_running( $pid );
    }

    /**
     * Get the version of a command-line tool.
     *
     * @since 1.0.0
     *
     * @param string $command The command to check (e.g., 'node', 'npm').
     * @return string The version string, or empty string if not available.
     */
    private function get_command_version( string $command ): string {
        $output      = array();
        $return_code = 0;

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( sprintf( '%s --version 2>/dev/null', escapeshellarg( $command ) ), $output, $return_code );

        if ( 0 !== $return_code || empty( $output[0] ) ) {
            return '';
        }

        return trim( $output[0] );
    }
}
