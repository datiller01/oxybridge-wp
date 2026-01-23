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
     * Registers the admin menu and script enqueuing hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * Loads CSS and JavaScript assets only on the Oxybridge admin page.
     * Uses wp_localize_script to pass AJAX URL, nonce, and translated strings
     * to the JavaScript.
     *
     * @since 1.0.0
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on our admin page.
        if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
            return;
        }

        // Get plugin URL for assets.
        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

        // Enqueue admin CSS.
        wp_enqueue_style(
            'oxybridge-admin',
            $plugin_url . 'assets/css/admin.css',
            array(),
            defined( 'OXYBRIDGE_VERSION' ) ? OXYBRIDGE_VERSION : '1.0.0'
        );

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
        <div class="wrap oxybridge-admin-wrap">
            <h1><?php esc_html_e( 'Oxybridge', 'oxybridge-wp' ); ?></h1>

            <?php if ( ! $is_oxygen_active ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e( 'Note:', 'oxybridge-wp' ); ?></strong>
                        <?php esc_html_e( 'Oxygen Builder is not currently active. Oxybridge requires Oxygen Builder to function. The information below is for reference only.', 'oxybridge-wp' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="oxybridge-columns">
                <!-- Left Column: Setup & Info -->
                <div class="oxybridge-column oxybridge-column-left">

                    <div class="card">
                        <h2><?php esc_html_e( 'About Oxybridge', 'oxybridge-wp' ); ?></h2>
                        <p>
                            <?php esc_html_e( 'Oxybridge WP exposes a secure REST API that allows external tools to read, query, and modify Oxygen template data, styles, and element structures.', 'oxybridge-wp' ); ?>
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
                            <li><?php esc_html_e( 'Enter a name (e.g., "Oxybridge") and click "Add New Application Password".', 'oxybridge-wp' ); ?></li>
                            <li><?php esc_html_e( 'Copy the generated password immediately — you will not be able to see it again.', 'oxybridge-wp' ); ?></li>
                        </ol>
                        <p>
                            <a href="<?php echo esc_url( $profile_url ); ?>" class="button button-secondary">
                                <?php esc_html_e( 'Go to Application Passwords', 'oxybridge-wp' ); ?>
                            </a>
                        </p>
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

                <!-- Right Column: REST API Documentation -->
                <div class="oxybridge-column oxybridge-column-right">

                    <div class="card">
                        <h2><?php esc_html_e( 'Authentication', 'oxybridge-wp' ); ?></h2>
                        <p><?php esc_html_e( 'Most endpoints require HTTP Basic Auth with your WordPress username and Application Password.', 'oxybridge-wp' ); ?></p>
                        <pre class="oxybridge-code"><code>curl -u "username:app-password" <?php echo esc_html( $api_base_url ); ?>templates</code></pre>
                        <p class="oxybridge-hint"><?php esc_html_e( 'Or use the Authorization header:', 'oxybridge-wp' ); ?> <code>Authorization: Basic base64(username:app-password)</code></p>
                    </div>

                    <!-- Public Endpoints -->
                    <div class="card">
                        <h2><?php esc_html_e( 'Public Endpoints', 'oxybridge-wp' ); ?> <span class="oxybridge-badge oxybridge-badge-public"><?php esc_html_e( 'No Auth', 'oxybridge-wp' ); ?></span></h2>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/health</code></h3>
                            <p><?php esc_html_e( 'Quick health check. Returns plugin version, Oxygen status, WordPress/PHP versions, and timestamp. Use this to verify API connectivity.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl <?php echo esc_html( $api_base_url ); ?>health</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/info</code></h3>
                            <p><?php esc_html_e( 'Detailed plugin information including available capabilities, builder type (Oxygen/Breakdance), and environment details.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl <?php echo esc_html( $api_base_url ); ?>info</code></pre>
                        </div>
                    </div>

                    <!-- Read Endpoints -->
                    <div class="card">
                        <h2><?php esc_html_e( 'Read Endpoints', 'oxybridge-wp' ); ?> <span class="oxybridge-badge oxybridge-badge-auth"><?php esc_html_e( 'Auth Required', 'oxybridge-wp' ); ?></span></h2>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/documents/{id}</code></h3>
                            <p><?php esc_html_e( 'Read the full Oxygen/Breakdance design tree for any page or post. This is the primary endpoint for accessing design data.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>include_metadata</code> (bool, default: true),
                                <code>flatten_elements</code> (bool, default: false)
                            </p>
                            <pre class="oxybridge-code"><code># Get full tree with metadata
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>documents/123"

# Get flattened element list (easier to parse)
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>documents/123?flatten_elements=true"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/pages</code></h3>
                            <p><?php esc_html_e( 'List all pages/posts that have Oxygen content. Supports pagination, filtering by post type, and search.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>post_type</code> (string),
                                <code>search</code> (string),
                                <code>status</code> (string),
                                <code>has_oxygen_content</code> (bool),
                                <code>per_page</code> (int, 1-100),
                                <code>page</code> (int)
                            </p>
                            <pre class="oxybridge-code"><code># List all pages with Oxygen content
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>pages"

# Search pages by title
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>pages?search=about&per_page=50"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/templates</code></h3>
                            <p><?php esc_html_e( 'List all Oxygen/Breakdance templates (headers, footers, blocks, etc.).', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>template_type</code> (string: header, footer, template, block, part)
                            </p>
                            <pre class="oxybridge-code"><code># List all templates
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>templates"

# List only headers
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>templates?template_type=header"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/templates/{id}</code></h3>
                            <p><?php esc_html_e( 'Get a specific template with its full design tree and element structure.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>include_elements</code> (bool, default: true)
                            </p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>templates/456"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/render/{id}</code></h3>
                            <p><?php esc_html_e( 'Render a page/template design to HTML output. Useful for previewing or exporting content.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>include_css</code> (bool),
                                <code>include_wrapper</code> (bool)
                            </p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>render/123?include_css=true"</code></pre>
                        </div>
                    </div>

                    <!-- Style & Schema Endpoints -->
                    <div class="card">
                        <h2><?php esc_html_e( 'Styles & Schema', 'oxybridge-wp' ); ?> <span class="oxybridge-badge oxybridge-badge-auth"><?php esc_html_e( 'Auth Required', 'oxybridge-wp' ); ?></span></h2>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/styles/global</code></h3>
                            <p><?php esc_html_e( 'Get global design system settings including colors, fonts, spacing, and CSS variables.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>category</code> (colors|fonts|spacing|all),
                                <code>include_variables</code> (bool),
                                <code>include_selectors</code> (bool)
                            </p>
                            <pre class="oxybridge-code"><code># Get all global styles
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>styles/global"

# Get only colors
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>styles/global?category=colors"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/colors</code> | <code>/fonts</code> | <code>/variables</code> | <code>/classes</code></h3>
                            <p><?php esc_html_e( 'Convenience endpoints to get specific style categories directly.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>colors"
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>fonts"
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>variables"
curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>classes"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/breakpoints</code></h3>
                            <p><?php esc_html_e( 'Get responsive breakpoint definitions (desktop, tablet, mobile sizes).', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>breakpoints"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/settings</code></h3>
                            <p><?php esc_html_e( 'Read global Oxygen/Breakdance builder settings.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>key</code> (string - specific setting key)
                            </p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>settings"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-get">GET</code> <code>/schema</code></h3>
                            <p><?php esc_html_e( 'Get element type definitions and available controls. Useful for understanding what properties each element supports.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>element_type</code> (string),
                                <code>include_controls</code> (bool)
                            </p>
                            <pre class="oxybridge-code"><code>curl -u user:pass "<?php echo esc_html( $api_base_url ); ?>schema?element_type=Section"</code></pre>
                        </div>
                    </div>

                    <!-- Write Endpoints -->
                    <div class="card">
                        <h2><?php esc_html_e( 'Write Endpoints', 'oxybridge-wp' ); ?> <span class="oxybridge-badge oxybridge-badge-write"><?php esc_html_e( 'Auth Required', 'oxybridge-wp' ); ?></span></h2>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/auth</code></h3>
                            <p><?php esc_html_e( 'Authenticate and receive a WordPress nonce for subsequent requests. Returns user ID and username on success.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl -X POST -u user:pass "<?php echo esc_html( $api_base_url ); ?>auth"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/pages</code></h3>
                            <p><?php esc_html_e( 'Create a new page with Oxygen design. Optionally provide a complete design tree or create an empty Oxygen-enabled page.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>title</code> (required),
                                <code>status</code> (draft|publish),
                                <code>post_type</code>,
                                <code>slug</code>,
                                <code>tree</code> (JSON object),
                                <code>enable_oxygen</code> (bool)
                            </p>
                            <pre class="oxybridge-code"><code>curl -X POST -u user:pass \
  -H "Content-Type: application/json" \
  -d '{"title":"New Page","status":"draft"}' \
  "<?php echo esc_html( $api_base_url ); ?>pages"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/templates</code></h3>
                            <p><?php esc_html_e( 'Create a new Oxygen/Breakdance template (header, footer, block, etc.).', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>title</code> (required),
                                <code>template_type</code> (required: header|footer|template|block|part),
                                <code>status</code>,
                                <code>tree</code> (JSON object)
                            </p>
                            <pre class="oxybridge-code"><code>curl -X POST -u user:pass \
  -H "Content-Type: application/json" \
  -d '{"title":"New Header","template_type":"header"}' \
  "<?php echo esc_html( $api_base_url ); ?>templates"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/clone/{id}</code></h3>
                            <p><?php esc_html_e( 'Clone an existing page or template with all its Oxygen design data. Element IDs are regenerated to ensure uniqueness.', 'oxybridge-wp' ); ?></p>
                            <p class="oxybridge-params"><strong><?php esc_html_e( 'Parameters:', 'oxybridge-wp' ); ?></strong>
                                <code>title</code> (optional),
                                <code>status</code> (draft|publish),
                                <code>slug</code>
                            </p>
                            <pre class="oxybridge-code"><code>curl -X POST -u user:pass \
  -d '{"title":"Cloned Page","status":"draft"}' \
  "<?php echo esc_html( $api_base_url ); ?>clone/123"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/validate</code></h3>
                            <p><?php esc_html_e( 'Validate a design tree JSON structure without saving. Use this to check if your tree is valid before creating/updating content.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code>curl -X POST -u user:pass \
  -H "Content-Type: application/json" \
  -d '{"tree":{"root":{"id":"el-1","data":{"type":"root"},"children":[]}}}' \
  "<?php echo esc_html( $api_base_url ); ?>validate"</code></pre>
                        </div>

                        <div class="oxybridge-endpoint">
                            <h3><code class="method-post">POST</code> <code>/regenerate-css/{id}</code> | <code>/regenerate-css</code></h3>
                            <p><?php esc_html_e( 'Regenerate CSS cache for a specific post or all posts. Use after programmatically modifying design data.', 'oxybridge-wp' ); ?></p>
                            <pre class="oxybridge-code"><code># Regenerate CSS for specific post
curl -X POST -u user:pass "<?php echo esc_html( $api_base_url ); ?>regenerate-css/123"

# Regenerate CSS for all posts (batch)
curl -X POST -u user:pass "<?php echo esc_html( $api_base_url ); ?>regenerate-css?batch_size=50"</code></pre>
                        </div>
                    </div>

                    <!-- Response Format -->
                    <div class="card">
                        <h2><?php esc_html_e( 'Response Format', 'oxybridge-wp' ); ?></h2>
                        <p><?php esc_html_e( 'All responses are JSON. Successful responses return the requested data directly. Errors follow the WordPress REST API format:', 'oxybridge-wp' ); ?></p>
                        <pre class="oxybridge-code"><code>{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": { "status": 403 }
}</code></pre>
                        <p class="oxybridge-hint"><?php esc_html_e( 'Common status codes: 200 (success), 201 (created), 400 (bad request), 401 (unauthorized), 403 (forbidden), 404 (not found)', 'oxybridge-wp' ); ?></p>
                    </div>

                </div>
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
