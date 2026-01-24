<?php
/**
 * REST API class for Oxybridge.
 *
 * Handles registration of custom REST API endpoints for Oxybridge.
 * This class acts as a coordinator, delegating to specialized controller classes.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

namespace Oxybridge;

use Oxybridge\REST\REST_Core;
use Oxybridge\REST\REST_Documents;
use Oxybridge\REST\REST_Pages;
use Oxybridge\REST\REST_Templates;
use Oxybridge\REST\REST_Settings;
use Oxybridge\REST\REST_AI;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class REST_API
 *
 * Registers and manages custom REST API endpoints for Oxybridge.
 * Delegates to specialized controller classes for each domain:
 * - Core: Health check, info, authentication
 * - Documents: Document tree CRUD operations
 * - Pages: Page listing and CRUD
 * - Templates: Template CRUD operations
 * - Settings: Global settings, colors, fonts, variables
 * - AI: AI context, components, transformation
 *
 * @since 1.0.0
 */
class REST_API {

    /**
     * REST API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'oxybridge/v1';

    /**
     * Controller instances.
     *
     * @var array
     */
    private array $controllers = array();

    /**
     * Constructor.
     *
     * Registers the REST API routes on the rest_api_init hook.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     *
     * Initializes all controllers and registers their routes.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes() {
        $this->init_controllers();

        foreach ( $this->controllers as $controller ) {
            $controller->register_routes();
        }
    }

    /**
     * Initialize all controller instances.
     *
     * @since 1.1.0
     * @return void
     */
    private function init_controllers(): void {
        $this->controllers = array(
            'core'      => new REST_Core(),
            'documents' => new REST_Documents(),
            'pages'     => new REST_Pages(),
            'templates' => new REST_Templates(),
            'settings'  => new REST_Settings(),
            'ai'        => new REST_AI(),
        );
    }

    /**
     * Get a specific controller instance.
     *
     * @since 1.1.0
     * @param string $name Controller name.
     * @return object|null Controller instance or null if not found.
     */
    public function get_controller( string $name ) {
        return $this->controllers[ $name ] ?? null;
    }

    /**
     * Get all controller instances.
     *
     * @since 1.1.0
     * @return array Array of controller instances.
     */
    public function get_controllers(): array {
        return $this->controllers;
    }
}
