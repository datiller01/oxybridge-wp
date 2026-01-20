<?php
/**
 * Autoloader for Oxybridge plugin classes.
 *
 * Implements PSR-4-style autoloading for the Oxybridge namespace.
 *
 * @package Oxybridge
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Autoloader
 *
 * Handles autoloading of Oxybridge plugin classes following PSR-4 conventions.
 * Maps the Oxybridge namespace to the includes directory.
 *
 * Class naming convention:
 * - Class file: class-{class-name}.php (WordPress style)
 * - Class name: Oxybridge\Class_Name
 *
 * Examples:
 * - Oxybridge\REST_API -> includes/class-rest-api.php
 * - Oxybridge\Oxygen_Data -> includes/class-oxygen-data.php
 * - Oxybridge\Auth -> includes/class-auth.php
 */
class Oxybridge_Autoloader {

    /**
     * Namespace prefix for Oxybridge classes.
     *
     * @var string
     */
    private $namespace_prefix = 'Oxybridge\\';

    /**
     * Base directory for includes.
     *
     * @var string
     */
    private $base_dir;

    /**
     * Constructor.
     *
     * Sets up the base directory and registers the autoloader.
     */
    public function __construct() {
        $this->base_dir = OXYBRIDGE_PLUGIN_DIR . '/includes/';
        $this->register();
    }

    /**
     * Register the autoloader with SPL.
     *
     * @return void
     */
    public function register() {
        spl_autoload_register( array( $this, 'autoload' ) );
    }

    /**
     * Autoload callback.
     *
     * Loads the class file for a given class name if it belongs to the Oxybridge namespace.
     *
     * @param string $class The fully-qualified class name.
     * @return void
     */
    public function autoload( $class ) {
        // Check if the class uses the Oxybridge namespace prefix.
        $len = strlen( $this->namespace_prefix );
        if ( strncmp( $this->namespace_prefix, $class, $len ) !== 0 ) {
            // Not an Oxybridge class, let other autoloaders handle it.
            return;
        }

        // Get the relative class name (without namespace prefix).
        $relative_class = substr( $class, $len );

        // Convert class name to file path.
        $file = $this->class_to_file( $relative_class );

        // If the file exists, require it.
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }

    /**
     * Convert a class name to a file path.
     *
     * Converts Class_Name to class-class-name.php following WordPress naming conventions.
     *
     * @param string $class The relative class name (without namespace prefix).
     * @return string The file path.
     */
    private function class_to_file( $class ) {
        // Handle sub-namespaces (e.g., Oxybridge\Tools\Templates -> tools/class-templates.php).
        $parts = explode( '\\', $class );
        $class_name = array_pop( $parts );

        // Build the subdirectory path.
        $subdir = '';
        if ( ! empty( $parts ) ) {
            $subdir = strtolower( implode( '/', $parts ) ) . '/';
        }

        // Convert class name: Class_Name -> class-name.
        $file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

        return $this->base_dir . $subdir . $file_name;
    }
}

// Initialize the autoloader.
new Oxybridge_Autoloader();
