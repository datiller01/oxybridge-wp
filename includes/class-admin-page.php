<?php
/**
 * Admin Page class for Oxybridge.
 *
 * Handles registration of admin menu and display of setup/usage instructions.
 *
 * @package Oxybridge
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin_Page
 *
 * Registers and manages the Oxybridge admin menu page.
 * Provides setup and usage instructions for administrators.
 *
 * @since 1.0.0
 */
class Admin_Page {

    /**
     * Menu slug for the admin page.
     *
     * @var string
     */
    const MENU_SLUG = 'oxybridge';

    /**
     * Required capability to access the admin page.
     *
     * @var string
     */
    const CAPABILITY = 'manage_options';

    /**
     * Constructor.
     *
     * Registers the admin menu on the admin_menu hook.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register admin menu page.
     *
     * Registers the Oxybridge top-level menu in the WordPress admin.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_menu() {
        add_menu_page(
            __( 'Oxybridge Instructions', 'oxybridge-wp' ),
            __( 'Oxybridge', 'oxybridge-wp' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_page' ),
            'dashicons-rest-api',
            80
        );
    }

    /**
     * Render the admin page content.
     *
     * Outputs the setup and usage instructions for Oxybridge.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_page() {
        $site_url          = get_site_url();
        $api_base_url      = $site_url . '/wp-json/oxybridge/v1/';
        $profile_url       = admin_url( 'profile.php#application-passwords-section' );
        $is_oxygen_active  = function_exists( 'oxybridge_is_oxygen_active' ) && oxybridge_is_oxygen_active();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Oxybridge Instructions', 'oxybridge-wp' ); ?></h1>

            <?php if ( ! $is_oxygen_active ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e( 'Note:', 'oxybridge-wp' ); ?></strong>
                        <?php esc_html_e( 'Oxygen Builder is not currently active. Oxybridge requires Oxygen Builder to function. The information below is for reference only.', 'oxybridge-wp' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2><?php esc_html_e( 'About Oxybridge', 'oxybridge-wp' ); ?></h2>
                <p>
                    <?php esc_html_e( 'Oxybridge WP creates a bridge between Oxygen Builder and AI assistants through the Model Context Protocol (MCP). It exposes a secure REST API that allows external MCP servers to read and query Oxygen template data, styles, and element structures.', 'oxybridge-wp' ); ?>
                </p>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Setup Instructions', 'oxybridge-wp' ); ?></h2>

                <h3><?php esc_html_e( '1. Ensure Oxygen Builder is Installed', 'oxybridge-wp' ); ?></h3>
                <p>
                    <?php esc_html_e( 'Oxybridge requires Oxygen Builder 6.x (Breakdance-based) or classic Oxygen Builder to be installed and activated.', 'oxybridge-wp' ); ?>
                </p>
                <?php if ( $is_oxygen_active ) : ?>
                    <p><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'Oxygen Builder is active.', 'oxybridge-wp' ); ?></p>
                <?php else : ?>
                    <p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> <?php esc_html_e( 'Oxygen Builder is not active.', 'oxybridge-wp' ); ?></p>
                <?php endif; ?>

                <h3><?php esc_html_e( '2. Create an Application Password', 'oxybridge-wp' ); ?></h3>
                <p>
                    <?php esc_html_e( 'WordPress Application Passwords are used to authenticate API requests securely.', 'oxybridge-wp' ); ?>
                </p>
                <ol>
                    <li>
                        <?php
                        printf(
                            /* translators: %s: Link to user profile */
                            wp_kses(
                                __( 'Go to <a href="%s">Users &gt; Profile</a> (or your user profile).', 'oxybridge-wp' ),
                                array( 'a' => array( 'href' => array() ) )
                            ),
                            esc_url( $profile_url )
                        );
                        ?>
                    </li>
                    <li><?php esc_html_e( 'Scroll down to the "Application Passwords" section.', 'oxybridge-wp' ); ?></li>
                    <li><?php esc_html_e( 'Enter a name (e.g., "Oxybridge MCP") and click "Add New Application Password".', 'oxybridge-wp' ); ?></li>
                    <li><?php esc_html_e( 'Copy the generated password immediately — you will not be able to see it again.', 'oxybridge-wp' ); ?></li>
                </ol>
                <p>
                    <a href="<?php echo esc_url( $profile_url ); ?>" class="button button-secondary">
                        <?php esc_html_e( 'Go to Application Passwords', 'oxybridge-wp' ); ?>
                    </a>
                </p>

                <h3><?php esc_html_e( '3. Configure the MCP Server', 'oxybridge-wp' ); ?></h3>
                <p>
                    <?php esc_html_e( 'Configure the oxybridge-mcp server with your WordPress credentials:', 'oxybridge-wp' ); ?>
                </p>
                <ul>
                    <li><strong><?php esc_html_e( 'WordPress URL:', 'oxybridge-wp' ); ?></strong> <code><?php echo esc_html( $site_url ); ?></code></li>
                    <li><strong><?php esc_html_e( 'Username:', 'oxybridge-wp' ); ?></strong> <?php esc_html_e( 'Your WordPress username', 'oxybridge-wp' ); ?></li>
                    <li><strong><?php esc_html_e( 'Application Password:', 'oxybridge-wp' ); ?></strong> <?php esc_html_e( 'The password generated in step 2', 'oxybridge-wp' ); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'REST API Endpoints', 'oxybridge-wp' ); ?></h2>
                <p>
                    <?php
                    printf(
                        /* translators: %s: API base URL */
                        esc_html__( 'The REST API is available at: %s', 'oxybridge-wp' ),
                        '<code>' . esc_html( $api_base_url ) . '</code>'
                    );
                    ?>
                </p>

                <h3><?php esc_html_e( 'Available Endpoints', 'oxybridge-wp' ); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Method', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Endpoint', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Auth Required', 'oxybridge-wp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/health</code></td>
                            <td><?php esc_html_e( 'Health check and version info', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'No', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>POST</code></td>
                            <td><code>/auth</code></td>
                            <td><?php esc_html_e( 'Authenticate and get nonce', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/templates</code></td>
                            <td><?php esc_html_e( 'List all Oxygen templates', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/templates/{id}</code></td>
                            <td><?php esc_html_e( 'Get a specific template by ID', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/pages</code></td>
                            <td><?php esc_html_e( 'List pages with Oxygen content', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/documents/{id}</code></td>
                            <td><?php esc_html_e( 'Read Oxygen document tree', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/styles/global</code></td>
                            <td><?php esc_html_e( 'Get global style settings', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/settings</code></td>
                            <td><?php esc_html_e( 'Read global Oxygen settings', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>GET</code></td>
                            <td><code>/schema</code></td>
                            <td><?php esc_html_e( 'Get element definitions', 'oxybridge-wp' ); ?></td>
                            <td><?php esc_html_e( 'Yes', 'oxybridge-wp' ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Authentication', 'oxybridge-wp' ); ?></h2>
                <p>
                    <?php esc_html_e( 'All endpoints (except /health) require authentication using HTTP Basic Auth with your WordPress username and Application Password.', 'oxybridge-wp' ); ?>
                </p>

                <h3><?php esc_html_e( 'Example Request', 'oxybridge-wp' ); ?></h3>
                <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;"><code>curl -u "username:application-password" \
  <?php echo esc_html( $api_base_url ); ?>templates</code></pre>

                <h3><?php esc_html_e( 'Authentication Header', 'oxybridge-wp' ); ?></h3>
                <p>
                    <?php esc_html_e( 'Alternatively, you can use the Authorization header with Base64-encoded credentials:', 'oxybridge-wp' ); ?>
                </p>
                <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;"><code>Authorization: Basic base64(username:application-password)</code></pre>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'MCP Integration', 'oxybridge-wp' ); ?></h2>
                <p>
                    <?php esc_html_e( 'Oxybridge is designed to work with the companion oxybridge-mcp Node.js server, which connects to AI assistants like Claude Desktop via the Model Context Protocol (MCP).', 'oxybridge-wp' ); ?>
                </p>

                <h3><?php esc_html_e( 'Use Cases', 'oxybridge-wp' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Let AI assistants understand your Oxygen site structure', 'oxybridge-wp' ); ?></li>
                    <li><?php esc_html_e( 'Query templates programmatically for documentation', 'oxybridge-wp' ); ?></li>
                    <li><?php esc_html_e( 'Build custom integrations with Oxygen Builder data', 'oxybridge-wp' ); ?></li>
                    <li><?php esc_html_e( 'Enable AI-powered design assistance workflows', 'oxybridge-wp' ); ?></li>
                </ul>

                <h3><?php esc_html_e( 'Resources', 'oxybridge-wp' ); ?></h3>
                <ul>
                    <li>
                        <a href="https://modelcontextprotocol.io/" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e( 'Model Context Protocol Documentation', 'oxybridge-wp' ); ?>
                            <span class="dashicons dashicons-external" style="font-size: 16px; text-decoration: none;"></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Plugin Information', 'oxybridge-wp' ); ?></h2>
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th><?php esc_html_e( 'Version', 'oxybridge-wp' ); ?></th>
                            <td><?php echo esc_html( defined( 'OXYBRIDGE_VERSION' ) ? OXYBRIDGE_VERSION : '1.0.0' ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'REST API Namespace', 'oxybridge-wp' ); ?></th>
                            <td><code>oxybridge/v1</code></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'REST API Base URL', 'oxybridge-wp' ); ?></th>
                            <td><code><?php echo esc_html( $api_base_url ); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Requires PHP', 'oxybridge-wp' ); ?></th>
                            <td>7.4+</td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Requires WordPress', 'oxybridge-wp' ); ?></th>
                            <td>5.9+</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <?php
    }

    /**
     * Get the menu slug.
     *
     * @since 1.0.0
     * @return string The menu slug.
     */
    public function get_menu_slug() {
        return self::MENU_SLUG;
    }
}
