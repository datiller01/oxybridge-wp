<?php
/**
 * Admin Page class for OxyBridge.
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
 * Registers and manages the OxyBridge admin menu pages.
 * Provides setup instructions, element reference, and how-to guides.
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
     * Loads CSS assets on OxyBridge admin pages.
     *
     * @since 1.0.0
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on our admin pages.
        $oxybridge_pages = array(
            'toplevel_page_' . self::MENU_SLUG,
            'oxybridge_page_oxybridge-reference',
            'oxybridge_page_oxybridge-howto',
            'oxybridge_page_oxybridge-ai',
        );

        if ( ! in_array( $hook_suffix, $oxybridge_pages, true ) ) {
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
     * Register admin menu pages.
     *
     * Registers the OxyBridge top-level menu and submenus in WordPress admin.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_menu() {
        // Parent menu.
        add_menu_page(
            __( 'OxyBridge', 'oxybridge-wp' ),
            __( 'OxyBridge', 'oxybridge-wp' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_dashboard_page' ),
            'dashicons-rest-api',
            80
        );

        // Dashboard submenu (same as parent, replaces default).
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Dashboard', 'oxybridge-wp' ),
            __( 'Dashboard', 'oxybridge-wp' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'render_dashboard_page' )
        );

        // Element Reference submenu.
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Element Reference', 'oxybridge-wp' ),
            __( 'Element Reference', 'oxybridge-wp' ),
            self::CAPABILITY,
            'oxybridge-reference',
            array( $this, 'render_reference_page' )
        );

        // How To submenu.
        add_submenu_page(
            self::MENU_SLUG,
            __( 'How To', 'oxybridge-wp' ),
            __( 'How To', 'oxybridge-wp' ),
            self::CAPABILITY,
            'oxybridge-howto',
            array( $this, 'render_howto_page' )
        );

        // AI Integration submenu.
        add_submenu_page(
            self::MENU_SLUG,
            __( 'AI Integration', 'oxybridge-wp' ),
            __( 'AI Integration', 'oxybridge-wp' ),
            self::CAPABILITY,
            'oxybridge-ai',
            array( $this, 'render_ai_page' )
        );
    }

    /**
     * Render the Dashboard page content.
     *
     * Outputs the setup instructions and API overview.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_dashboard_page() {
        $site_url          = get_site_url();
        $api_base_url      = $site_url . '/wp-json/oxybridge/v1/';
        $profile_url       = admin_url( 'profile.php#application-passwords-section' );
        $is_oxygen_active  = function_exists( 'oxybridge_is_oxygen_active' ) && oxybridge_is_oxygen_active();
        ?>
        <div class="wrap oxybridge-admin-wrap">
            <h1><?php esc_html_e( 'OxyBridge', 'oxybridge-wp' ); ?></h1>

            <?php if ( ! $is_oxygen_active ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e( 'Note:', 'oxybridge-wp' ); ?></strong>
                        <?php esc_html_e( 'Oxygen Builder is not currently active. OxyBridge requires Oxygen Builder to function.', 'oxybridge-wp' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="oxybridge-columns">
                <!-- Left Column: Setup & Info -->
                <div class="oxybridge-column oxybridge-column-left">

                    <div class="card">
                        <h2><?php esc_html_e( 'About OxyBridge', 'oxybridge-wp' ); ?></h2>
                        <p>
                            <?php esc_html_e( 'OxyBridge exposes a secure REST API that allows external tools (like AI assistants) to read, query, and modify Oxygen/Breakdance template data, styles, and element structures.', 'oxybridge-wp' ); ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=oxybridge-reference' ) ); ?>" class="button button-primary">
                                <?php esc_html_e( 'View Element Reference', 'oxybridge-wp' ); ?>
                            </a>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=oxybridge-howto' ) ); ?>" class="button button-secondary">
                                <?php esc_html_e( 'How To Guide', 'oxybridge-wp' ); ?>
                            </a>
                        </p>
                    </div>

                    <div class="card">
                        <h2><?php esc_html_e( 'Quick Setup', 'oxybridge-wp' ); ?></h2>

                        <h3><?php esc_html_e( '1. Oxygen Builder Status', 'oxybridge-wp' ); ?></h3>
                        <?php if ( $is_oxygen_active ) : ?>
                            <p><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e( 'Oxygen Builder is active and ready.', 'oxybridge-wp' ); ?></p>
                        <?php else : ?>
                            <p><span class="dashicons dashicons-warning" style="color: #dc3232;"></span> <?php esc_html_e( 'Oxygen Builder is not active.', 'oxybridge-wp' ); ?></p>
                        <?php endif; ?>

                        <h3><?php esc_html_e( '2. Create an Application Password', 'oxybridge-wp' ); ?></h3>
                        <p><?php esc_html_e( 'Required for API authentication:', 'oxybridge-wp' ); ?></p>
                        <ol>
                            <li><?php esc_html_e( 'Go to Users > Profile', 'oxybridge-wp' ); ?></li>
                            <li><?php esc_html_e( 'Scroll to "Application Passwords"', 'oxybridge-wp' ); ?></li>
                            <li><?php esc_html_e( 'Enter name "OxyBridge" and click Add', 'oxybridge-wp' ); ?></li>
                            <li><?php esc_html_e( 'Copy the password immediately', 'oxybridge-wp' ); ?></li>
                        </ol>
                        <p>
                            <a href="<?php echo esc_url( $profile_url ); ?>" class="button button-secondary">
                                <?php esc_html_e( 'Create Application Password', 'oxybridge-wp' ); ?>
                            </a>
                        </p>

                        <h3><?php esc_html_e( '3. Test the API', 'oxybridge-wp' ); ?></h3>
                        <pre class="oxybridge-code"><code>curl <?php echo esc_html( $api_base_url ); ?>health</code></pre>
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
                                    <th><?php esc_html_e( 'API Base URL', 'oxybridge-wp' ); ?></th>
                                    <td><code><?php echo esc_html( $api_base_url ); ?></code></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Namespace', 'oxybridge-wp' ); ?></th>
                                    <td><code>oxybridge/v1</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Right Column: Quick API Reference -->
                <div class="oxybridge-column oxybridge-column-right">

                    <div class="card">
                        <h2><?php esc_html_e( 'API Endpoints Overview', 'oxybridge-wp' ); ?></h2>

                        <h3><?php esc_html_e( 'Public (No Auth)', 'oxybridge-wp' ); ?></h3>
                        <table class="widefat">
                            <tbody>
                                <tr><td><code class="method-get">GET</code></td><td><code>/health</code></td><td><?php esc_html_e( 'Health check', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-get">GET</code></td><td><code>/info</code></td><td><?php esc_html_e( 'Plugin info', 'oxybridge-wp' ); ?></td></tr>
                            </tbody>
                        </table>

                        <h3><?php esc_html_e( 'Read Endpoints', 'oxybridge-wp' ); ?></h3>
                        <table class="widefat">
                            <tbody>
                                <tr><td><code class="method-get">GET</code></td><td><code>/documents/{id}</code></td><td><?php esc_html_e( 'Get design tree', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-get">GET</code></td><td><code>/pages</code></td><td><?php esc_html_e( 'List pages', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-get">GET</code></td><td><code>/templates</code></td><td><?php esc_html_e( 'List templates', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-get">GET</code></td><td><code>/styles/global</code></td><td><?php esc_html_e( 'Global styles', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-get">GET</code></td><td><code>/schema</code></td><td><?php esc_html_e( 'Element schemas', 'oxybridge-wp' ); ?></td></tr>
                            </tbody>
                        </table>

                        <h3><?php esc_html_e( 'Write Endpoints', 'oxybridge-wp' ); ?></h3>
                        <table class="widefat">
                            <tbody>
                                <tr><td><code class="method-post">POST</code></td><td><code>/pages</code></td><td><?php esc_html_e( 'Create page', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-post">POST</code></td><td><code>/templates</code></td><td><?php esc_html_e( 'Create template', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-post">POST</code></td><td><code>/clone/{id}</code></td><td><?php esc_html_e( 'Clone page/template', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-post">POST</code></td><td><code>/validate</code></td><td><?php esc_html_e( 'Validate tree', 'oxybridge-wp' ); ?></td></tr>
                                <tr><td><code class="method-post">POST</code></td><td><code>/regenerate-css/{id}</code></td><td><?php esc_html_e( 'Regenerate CSS', 'oxybridge-wp' ); ?></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <h2><?php esc_html_e( 'Authentication Example', 'oxybridge-wp' ); ?></h2>
                        <pre class="oxybridge-code"><code>curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>documents/123"</code></pre>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Element Reference page.
     *
     * Displays comprehensive element structure documentation.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_reference_page() {
        ?>
        <div class="wrap oxybridge-admin-wrap">
            <h1><?php esc_html_e( 'OxyBridge Element Reference', 'oxybridge-wp' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Complete property paths and data structures for all Breakdance/Oxygen elements.', 'oxybridge-wp' ); ?></p>

            <div class="oxybridge-reference-content">

                <!-- Critical Concept -->
                <div class="card">
                    <h2><?php esc_html_e( 'Critical: Double-Nested Content', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'All text content uses double-nested content.content.text path:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>{
  "properties": {
    "content": {
      "content": {
        "text": "Your text here"
      }
    }
  }
}</code></pre>
                </div>

                <!-- Element Types -->
                <div class="card">
                    <h2><?php esc_html_e( 'Element Types', 'oxybridge-wp' ); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Element', 'oxybridge-wp' ); ?></th>
                                <th><?php esc_html_e( 'Type String', 'oxybridge-wp' ); ?></th>
                                <th><?php esc_html_e( 'Category', 'oxybridge-wp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Section</td><td><code>EssentialElements\\Section</code></td><td>Layout</td></tr>
                            <tr><td>Div</td><td><code>EssentialElements\\Div</code></td><td>Layout</td></tr>
                            <tr><td>Columns</td><td><code>EssentialElements\\Columns</code></td><td>Layout</td></tr>
                            <tr><td>Column</td><td><code>EssentialElements\\Column</code></td><td>Layout</td></tr>
                            <tr><td>Grid</td><td><code>EssentialElements\\Grid</code></td><td>Layout</td></tr>
                            <tr><td>Heading</td><td><code>EssentialElements\\Heading</code></td><td>Text</td></tr>
                            <tr><td>Text</td><td><code>EssentialElements\\Text</code></td><td>Text</td></tr>
                            <tr><td>Rich Text</td><td><code>EssentialElements\\RichText</code></td><td>Text</td></tr>
                            <tr><td>Button</td><td><code>EssentialElements\\Button</code></td><td>Interactive</td></tr>
                            <tr><td>Text Link</td><td><code>EssentialElements\\TextLink</code></td><td>Interactive</td></tr>
                            <tr><td>Icon</td><td><code>EssentialElements\\Icon</code></td><td>Media</td></tr>
                            <tr><td>Image</td><td><code>EssentialElements\\Image2</code></td><td>Media</td></tr>
                            <tr><td>Video</td><td><code>EssentialElements\\Video</code></td><td>Media</td></tr>
                            <tr><td>Gallery</td><td><code>EssentialElements\\Gallery</code></td><td>Media</td></tr>
                            <tr><td>Basic Slider</td><td><code>EssentialElements\\BasicSlider</code></td><td>Slider</td></tr>
                            <tr><td>Accordion</td><td><code>EssentialElements\\AdvancedAccordion</code></td><td>Advanced</td></tr>
                            <tr><td>Tabs</td><td><code>EssentialElements\\Tabs</code></td><td>Advanced</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Heading Example -->
                <div class="card">
                    <h2><?php esc_html_e( 'Heading Element', 'oxybridge-wp' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Content Path:', 'oxybridge-wp' ); ?></strong> <code>content.content.text</code>, <code>content.content.tags</code></p>
                    <pre class="oxybridge-code"><code>{
  "id": "heading-1",
  "data": {
    "type": "EssentialElements\\Heading",
    "properties": {
      "content": {
        "content": {
          "text": "Your Heading Text",
          "tags": "h1"
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#000000"}
        }
      }
    }
  },
  "children": []
}</code></pre>
                    <p class="oxybridge-hint"><?php esc_html_e( 'Tag options: h1, h2, h3, h4, h5, h6', 'oxybridge-wp' ); ?></p>
                </div>

                <!-- Text Example -->
                <div class="card">
                    <h2><?php esc_html_e( 'Text Element', 'oxybridge-wp' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Content Path:', 'oxybridge-wp' ); ?></strong> <code>content.content.text</code></p>
                    <pre class="oxybridge-code"><code>{
  "id": "text-1",
  "data": {
    "type": "EssentialElements\\Text",
    "properties": {
      "content": {
        "content": {
          "text": "Your paragraph text goes here."
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#333333"}
        }
      }
    }
  },
  "children": []
}</code></pre>
                </div>

                <!-- Button Example -->
                <div class="card">
                    <h2><?php esc_html_e( 'Button Element', 'oxybridge-wp' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Content Path:', 'oxybridge-wp' ); ?></strong> <code>content.content.text</code>, <code>content.content.link</code></p>
                    <pre class="oxybridge-code"><code>{
  "id": "button-1",
  "data": {
    "type": "EssentialElements\\Button",
    "properties": {
      "content": {
        "content": {
          "text": "Click Here",
          "link": {
            "url": "https://example.com",
            "type": "url",
            "target": "_self"
          }
        }
      },
      "design": {
        "button": {
          "background": {"breakpoint_base": "#0066cc"},
          "typography": {
            "color": {"breakpoint_base": "#ffffff"}
          }
        }
      }
    }
  },
  "children": []
}</code></pre>
                </div>

                <!-- Section Example -->
                <div class="card">
                    <h2><?php esc_html_e( 'Section Element', 'oxybridge-wp' ); ?></h2>
                    <p><strong><?php esc_html_e( 'Note:', 'oxybridge-wp' ); ?></strong> <?php esc_html_e( 'Sections are containers and do not have content.content.text. They contain children.', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>{
  "id": "section-1",
  "data": {
    "type": "EssentialElements\\Section",
    "properties": {
      "design": {
        "background": {"color": "#0a1628"},
        "spacing": {
          "padding": {
            "top": {"breakpoint_base": "100px"},
            "bottom": {"breakpoint_base": "100px"},
            "left": {"breakpoint_base": "60px"},
            "right": {"breakpoint_base": "60px"}
          }
        }
      }
    }
  },
  "children": []
}</code></pre>
                </div>

                <!-- Div/Flex Example -->
                <div class="card">
                    <h2><?php esc_html_e( 'Div Element (Flex/Grid Layout)', 'oxybridge-wp' ); ?></h2>
                    <pre class="oxybridge-code"><code>{
  "id": "div-1",
  "data": {
    "type": "EssentialElements\\Div",
    "properties": {
      "design": {
        "layout": {
          "display": {"breakpoint_base": "flex"},
          "justifyContent": {"breakpoint_base": "center"},
          "alignItems": {"breakpoint_base": "center"},
          "horizontalGap": {"breakpoint_base": "20px"}
        },
        "background": {"color": "#ffffff"},
        "borders": {
          "radius": {"all": {"breakpoint_base": {"number": 8, "unit": "px"}}}
        }
      }
    }
  },
  "children": []
}</code></pre>
                    <p class="oxybridge-hint"><?php esc_html_e( 'For grid: use display: "grid" and gridTemplateColumns: "repeat(3, 1fr)"', 'oxybridge-wp' ); ?></p>
                </div>

                <!-- Breakpoints -->
                <div class="card">
                    <h2><?php esc_html_e( 'Breakpoint System', 'oxybridge-wp' ); ?></h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Breakpoint', 'oxybridge-wp' ); ?></th>
                                <th><?php esc_html_e( 'Key', 'oxybridge-wp' ); ?></th>
                                <th><?php esc_html_e( 'Width', 'oxybridge-wp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Desktop (Base)</td><td><code>breakpoint_base</code></td><td>1120px+</td></tr>
                            <tr><td>Tablet Landscape</td><td><code>breakpoint_tablet_landscape</code></td><td>1024px</td></tr>
                            <tr><td>Tablet Portrait</td><td><code>breakpoint_tablet_portrait</code></td><td>768px</td></tr>
                            <tr><td>Phone Landscape</td><td><code>breakpoint_phone_landscape</code></td><td>480px</td></tr>
                            <tr><td>Phone Portrait</td><td><code>breakpoint_phone_portrait</code></td><td>320px</td></tr>
                        </tbody>
                    </table>
                    <pre class="oxybridge-code"><code>"fontSize": {
  "breakpoint_base": {"number": 48, "unit": "px"},
  "breakpoint_tablet_portrait": {"number": 36, "unit": "px"},
  "breakpoint_phone_portrait": {"number": 28, "unit": "px"}
}</code></pre>
                </div>

                <!-- Design Properties -->
                <div class="card">
                    <h2><?php esc_html_e( 'Common Design Properties', 'oxybridge-wp' ); ?></h2>

                    <h3><?php esc_html_e( 'Typography', 'oxybridge-wp' ); ?></h3>
                    <pre class="oxybridge-code"><code>"design": {
  "typography": {
    "color": {"breakpoint_base": "#333"},
    "typography": {
      "custom": {
        "customTypography": {
          "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
          "fontWeight": {"breakpoint_base": "400"},
          "lineHeight": {"breakpoint_base": {"number": 1.6, "unit": "em"}},
          "textAlign": {"breakpoint_base": "left"}
        }
      }
    }
  }
}</code></pre>

                    <h3><?php esc_html_e( 'Spacing', 'oxybridge-wp' ); ?></h3>
                    <pre class="oxybridge-code"><code>"design": {
  "spacing": {
    "padding": {
      "top": {"breakpoint_base": "20px"},
      "bottom": {"breakpoint_base": "20px"},
      "left": {"breakpoint_base": "20px"},
      "right": {"breakpoint_base": "20px"}
    },
    "margin_top": {"breakpoint_base": "0px"},
    "margin_bottom": {"breakpoint_base": "20px"}
  }
}</code></pre>

                    <h3><?php esc_html_e( 'Borders', 'oxybridge-wp' ); ?></h3>
                    <pre class="oxybridge-code"><code>"design": {
  "borders": {
    "radius": {"all": {"breakpoint_base": {"number": 8, "unit": "px"}}},
    "border": {
      "all": {
        "width": {"breakpoint_base": {"number": 1, "unit": "px"}},
        "style": "solid",
        "color": "#e0e0e0"
      }
    }
  }
}</code></pre>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render the How To page.
     *
     * Displays usage guides and examples.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_howto_page() {
        $site_url     = get_site_url();
        $api_base_url = $site_url . '/wp-json/oxybridge/v1/';
        ?>
        <div class="wrap oxybridge-admin-wrap">
            <h1><?php esc_html_e( 'OxyBridge How To Guide', 'oxybridge-wp' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Step-by-step guides for common tasks using the OxyBridge API.', 'oxybridge-wp' ); ?></p>

            <div class="oxybridge-howto-content">

                <!-- Create a Page -->
                <div class="card">
                    <h2><?php esc_html_e( '1. Create a Page with Content', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Create a new page with a heading and text using the API:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>curl -X POST -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My New Page",
    "status": "publish",
    "tree": {
      "root": {
        "id": "root-1",
        "data": {"type": "EssentialElements\\Root"},
        "children": [{
          "id": "section-1",
          "data": {
            "type": "EssentialElements\\Section",
            "properties": {
              "design": {"background": {"color": "#ffffff"}}
            }
          },
          "children": [{
            "id": "heading-1",
            "data": {
              "type": "EssentialElements\\Heading",
              "properties": {
                "content": {"content": {"text": "Hello World", "tags": "h1"}},
                "design": {"typography": {"color": {"breakpoint_base": "#000"}}}
              }
            },
            "children": []
          }]
        }]
      }
    }
  }' \
  "<?php echo esc_html( $api_base_url ); ?>pages"</code></pre>
                </div>

                <!-- Clone a Page -->
                <div class="card">
                    <h2><?php esc_html_e( '2. Clone an Existing Page', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Duplicate a page with all its Oxygen design data:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>curl -X POST -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"title": "Cloned Page", "status": "draft"}' \
  "<?php echo esc_html( $api_base_url ); ?>clone/123"</code></pre>
                </div>

                <!-- Read Page Structure -->
                <div class="card">
                    <h2><?php esc_html_e( '3. Read Page Structure', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Get the full design tree for a page to understand its structure:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code># Get full tree
curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>documents/123"

# Get flattened element list
curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>documents/123?flatten_elements=true"</code></pre>
                </div>

                <!-- List Pages -->
                <div class="card">
                    <h2><?php esc_html_e( '4. List All Oxygen Pages', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Find all pages that have Oxygen content:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>pages?per_page=100"</code></pre>
                </div>

                <!-- Regenerate CSS -->
                <div class="card">
                    <h2><?php esc_html_e( '5. Regenerate CSS After Changes', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'After modifying a page programmatically, regenerate its CSS:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>curl -X POST -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>regenerate-css/123"</code></pre>
                </div>

                <!-- Get Global Styles -->
                <div class="card">
                    <h2><?php esc_html_e( '6. Get Global Styles & Colors', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Retrieve the site design system settings:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code># All global styles
curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>styles/global"

# Just colors
curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>colors"

# Just fonts
curl -u "username:app-password" \
  "<?php echo esc_html( $api_base_url ); ?>fonts"</code></pre>
                </div>

                <!-- Validate Tree -->
                <div class="card">
                    <h2><?php esc_html_e( '7. Validate a Tree Before Saving', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Check if your tree structure is valid before creating a page:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>curl -X POST -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{"tree": {"root": {"id": "r1", "data": {"type": "EssentialElements\\Root"}, "children": []}}}' \
  "<?php echo esc_html( $api_base_url ); ?>validate"</code></pre>
                </div>

                <!-- Complete Landing Page -->
                <div class="card">
                    <h2><?php esc_html_e( '8. Create a Complete Landing Page', 'oxybridge-wp' ); ?></h2>
                    <p><?php esc_html_e( 'Example: Hero section with heading, text, and button:', 'oxybridge-wp' ); ?></p>
                    <pre class="oxybridge-code"><code>{
  "title": "Landing Page",
  "status": "publish",
  "tree": {
    "root": {
      "id": "root-1",
      "data": {"type": "EssentialElements\\Root"},
      "children": [{
        "id": "hero",
        "data": {
          "type": "EssentialElements\\Section",
          "properties": {
            "design": {
              "background": {"color": "#0a1628"},
              "spacing": {
                "padding": {
                  "top": {"breakpoint_base": "120px"},
                  "bottom": {"breakpoint_base": "120px"}
                }
              }
            }
          }
        },
        "children": [
          {
            "id": "h1",
            "data": {
              "type": "EssentialElements\\Heading",
              "properties": {
                "content": {"content": {"text": "Welcome", "tags": "h1"}},
                "design": {"typography": {"color": {"breakpoint_base": "#fff"}}}
              }
            },
            "children": []
          },
          {
            "id": "p1",
            "data": {
              "type": "EssentialElements\\Text",
              "properties": {
                "content": {"content": {"text": "Your tagline here"}},
                "design": {"typography": {"color": {"breakpoint_base": "#ccc"}}}
              }
            },
            "children": []
          },
          {
            "id": "btn1",
            "data": {
              "type": "EssentialElements\\Button",
              "properties": {
                "content": {
                  "content": {
                    "text": "Get Started",
                    "link": {"url": "/signup", "type": "url"}
                  }
                },
                "design": {
                  "button": {
                    "background": {"breakpoint_base": "#0066cc"},
                    "typography": {"color": {"breakpoint_base": "#fff"}}
                  }
                }
              }
            },
            "children": []
          }
        ]
      }]
    }
  }
}</code></pre>
                </div>

                <!-- Tips -->
                <div class="card">
                    <h2><?php esc_html_e( 'Tips & Best Practices', 'oxybridge-wp' ); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e( 'Always use content.content.text', 'oxybridge-wp' ); ?></strong> - <?php esc_html_e( 'The double-nested path is required for text to render.', 'oxybridge-wp' ); ?></li>
                        <li><strong><?php esc_html_e( 'Use breakpoint_base for styles', 'oxybridge-wp' ); ?></strong> - <?php esc_html_e( 'Wrap values in breakpoint keys for responsive design.', 'oxybridge-wp' ); ?></li>
                        <li><strong><?php esc_html_e( 'Regenerate CSS after changes', 'oxybridge-wp' ); ?></strong> - <?php esc_html_e( 'Call /regenerate-css/{id} after modifying page structure.', 'oxybridge-wp' ); ?></li>
                        <li><strong><?php esc_html_e( 'Clone before modifying', 'oxybridge-wp' ); ?></strong> - <?php esc_html_e( 'Use /clone to copy pages with correct internal format.', 'oxybridge-wp' ); ?></li>
                        <li><strong><?php esc_html_e( 'Validate before creating', 'oxybridge-wp' ); ?></strong> - <?php esc_html_e( 'Use /validate to check tree structure before saving.', 'oxybridge-wp' ); ?></li>
                    </ul>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render the AI Integration page content.
     *
     * Displays AI agent integration documentation and endpoints.
     *
     * @since 1.1.0
     * @return void
     */
    public function render_ai_page() {
        $site_url     = get_site_url();
        $api_base_url = $site_url . '/wp-json/oxybridge/v1/';
        ?>
        <div class="wrap oxybridge-admin">
            <h1><?php esc_html_e( 'AI Integration', 'oxybridge-wp' ); ?></h1>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'AI Agent Pipeline', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( 'OxyBridge provides a minimal-context architecture optimized for AI agents. Instead of passing large documentation, agents receive a compact 3.9KB context with everything needed to generate pages.', 'oxybridge-wp' ); ?></p>

                <h3><?php esc_html_e( 'Context Size Comparison', 'oxybridge-wp' ); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Approach', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Size', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Tokens (~)', 'oxybridge-wp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php esc_html_e( 'Full Documentation', 'oxybridge-wp' ); ?></td>
                            <td>~35KB</td>
                            <td>~9,000</td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e( 'AI Context Endpoint', 'oxybridge-wp' ); ?></strong></td>
                            <td><strong>3.9KB</strong></td>
                            <td><strong>~1,000</strong></td>
                        </tr>
                        <tr>
                            <td colspan="3"><em><?php esc_html_e( '89% reduction in context usage', 'oxybridge-wp' ); ?></em></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'AI Endpoints', 'oxybridge-wp' ); ?></h2>

                <h3><?php esc_html_e( 'Master Context Endpoint', 'oxybridge-wp' ); ?></h3>
                <p><?php esc_html_e( 'Returns everything an AI agent needs in a single request:', 'oxybridge-wp' ); ?></p>
                <pre><code>GET <?php echo esc_html( $api_base_url ); ?>ai/context</code></pre>
                <p><?php esc_html_e( 'Includes: element schema, design tokens, component list, API reference', 'oxybridge-wp' ); ?></p>

                <h3><?php esc_html_e( 'Individual Endpoints', 'oxybridge-wp' ); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Endpoint', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Method', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'oxybridge-wp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/ai/context</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Complete AI context (3.9KB)', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/tokens</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Design tokens (colors, fonts, spacing)', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/schema</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Full element schema', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/components</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'List all 21 components', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/components/{name}</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Get specific component JSON', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/templates</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'List saved templates', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/templates</code></td>
                            <td>POST</td>
                            <td><?php esc_html_e( 'Save new template', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/templates/{name}</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Get specific template', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/templates/{name}</code></td>
                            <td>DELETE</td>
                            <td><?php esc_html_e( 'Delete template', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/schema/simplified</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Simplified format schema (auto-generated)', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/schema/elements/{type}</code></td>
                            <td>GET</td>
                            <td><?php esc_html_e( 'Schema for specific element type', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/transform</code></td>
                            <td>POST</td>
                            <td><?php esc_html_e( 'Transform simplified format to Breakdance format', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/validate</code></td>
                            <td>POST</td>
                            <td><?php esc_html_e( 'Validate properties before saving', 'oxybridge-wp' ); ?></td>
                        </tr>
                        <tr>
                            <td><code>/ai/preview-css</code></td>
                            <td>POST</td>
                            <td><?php esc_html_e( 'Preview CSS for an element', 'oxybridge-wp' ); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'Available Components', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( '21 pre-built component snippets ready for AI agents:', 'oxybridge-wp' ); ?></p>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div>
                        <h4><?php esc_html_e( 'Heroes', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>hero_section</code></li>
                            <li><code>hero_split</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'Features', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>feature_grid</code></li>
                            <li><code>feature_list</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'CTAs', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>cta_section</code></li>
                            <li><code>cta_banner</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4><?php esc_html_e( 'Pricing', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>pricing_table</code></li>
                            <li><code>pricing_cards</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'Testimonials', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>testimonial_single</code></li>
                            <li><code>testimonial_grid</code></li>
                            <li><code>testimonial_slider</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'Team & FAQ', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>team_grid</code></li>
                            <li><code>faq_accordion</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4><?php esc_html_e( 'Gallery', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>gallery_grid</code></li>
                            <li><code>gallery_masonry</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'Other', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>stats_counter</code></li>
                            <li><code>logo_cloud</code></li>
                            <li><code>newsletter_signup</code></li>
                            <li><code>contact_form</code></li>
                        </ul>

                        <h4><?php esc_html_e( 'Footers', 'oxybridge-wp' ); ?></h4>
                        <ul>
                            <li><code>footer_simple</code></li>
                            <li><code>footer_complex</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'AI Agent Workflow', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( 'Typical workflow for an AI agent to create a page:', 'oxybridge-wp' ); ?></p>

                <ol>
                    <li>
                        <strong><?php esc_html_e( 'Get Context', 'oxybridge-wp' ); ?></strong>
                        <pre><code>GET /wp-json/oxybridge/v1/ai/context</code></pre>
                        <p><?php esc_html_e( 'Receive schema, tokens, and component list (~3.9KB)', 'oxybridge-wp' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Get Component (optional)', 'oxybridge-wp' ); ?></strong>
                        <pre><code>GET /wp-json/oxybridge/v1/ai/components/hero_section</code></pre>
                        <p><?php esc_html_e( 'Retrieve a pre-built component to use as starting point', 'oxybridge-wp' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Create Page', 'oxybridge-wp' ); ?></strong>
                        <pre><code>POST /wp-json/oxybridge/v1/pages
{
  "title": "My Landing Page",
  "status": "publish",
  "tree": { ... }
}</code></pre>
                        <p><?php esc_html_e( 'Create the page with the generated tree structure', 'oxybridge-wp' ); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Regenerate CSS', 'oxybridge-wp' ); ?></strong>
                        <pre><code>POST /wp-json/oxybridge/v1/regenerate-css/{page_id}</code></pre>
                        <p><?php esc_html_e( 'Generate the CSS styles for the page', 'oxybridge-wp' ); ?></p>
                    </li>
                </ol>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'Simplified Format (Recommended)', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( 'Use use_simplified: true when creating pages to write AI-friendly flat properties that get auto-transformed to Breakdance\'s nested format.', 'oxybridge-wp' ); ?></p>

                <h3><?php esc_html_e( 'Example Request', 'oxybridge-wp' ); ?></h3>
                <pre><code>POST /wp-json/oxybridge/v1/pages
{
  "title": "Landing Page",
  "use_simplified": true,
  "tree": {
    "root": {
      "children": [{
        "type": "Section",
        "background": "#f5f5f5",
        "padding": "80px 30px",
        "children": [{
          "type": "Heading",
          "text": "Welcome",
          "tag": "h1",
          "color": "#333",
          "fontSize": "48px",
          "responsive": {
            "tablet": { "fontSize": "36px" },
            "phone": { "fontSize": "28px" }
          }
        }]
      }]
    }
  }
}</code></pre>

                <h3><?php esc_html_e( 'Supported Properties', 'oxybridge-wp' ); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Property', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'oxybridge-wp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>text</code></td><td>string</td><td><?php esc_html_e( 'Element text content', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>tag</code></td><td>enum</td><td><?php esc_html_e( 'HTML tag (h1-h6, p, span)', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>color</code></td><td>color</td><td><?php esc_html_e( 'Text color', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>fontSize</code></td><td>unit</td><td><?php esc_html_e( 'Font size (e.g., "48px", "1.5rem")', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>fontWeight</code></td><td>enum</td><td><?php esc_html_e( 'Font weight (400, 500, 600, 700)', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>lineHeight</code></td><td>unit</td><td><?php esc_html_e( 'Line height', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>textAlign</code></td><td>enum</td><td><?php esc_html_e( 'Text alignment (left, center, right)', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>background</code></td><td>color</td><td><?php esc_html_e( 'Background color', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>padding</code></td><td>spacing</td><td><?php esc_html_e( 'Padding (shorthand: "20px 40px")', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>margin</code></td><td>spacing</td><td><?php esc_html_e( 'Margin (shorthand)', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>display</code></td><td>enum</td><td><?php esc_html_e( 'Display type (flex, grid, block)', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>flexDirection</code></td><td>enum</td><td><?php esc_html_e( 'Flex direction', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>justifyContent</code></td><td>enum</td><td><?php esc_html_e( 'Justify content', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>alignItems</code></td><td>enum</td><td><?php esc_html_e( 'Align items', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>gap</code></td><td>unit</td><td><?php esc_html_e( 'Gap between children', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>gridColumns</code></td><td>string</td><td><?php esc_html_e( 'Grid template columns', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>width</code></td><td>unit</td><td><?php esc_html_e( 'Element width', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>maxWidth</code></td><td>unit</td><td><?php esc_html_e( 'Maximum width', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>minHeight</code></td><td>unit</td><td><?php esc_html_e( 'Minimum height', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>borderRadius</code></td><td>unit</td><td><?php esc_html_e( 'Border radius', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>customCss</code></td><td>code</td><td><?php esc_html_e( 'Custom CSS (use %%SELECTOR%%)', 'oxybridge-wp' ); ?></td></tr>
                    </tbody>
                </table>

                <h3><?php esc_html_e( 'Code Elements', 'oxybridge-wp' ); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Element', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Property', 'oxybridge-wp' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'oxybridge-wp' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><code>HtmlCode</code></td><td><code>html</code></td><td><?php esc_html_e( 'Custom HTML/JS code', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>CssCode</code></td><td><code>css</code></td><td><?php esc_html_e( 'Page-specific CSS', 'oxybridge-wp' ); ?></td></tr>
                        <tr><td><code>PhpCode</code></td><td><code>php</code></td><td><?php esc_html_e( 'Dynamic PHP (requires unfiltered_html)', 'oxybridge-wp' ); ?></td></tr>
                    </tbody>
                </table>

                <h3><?php esc_html_e( 'Responsive Breakpoints', 'oxybridge-wp' ); ?></h3>
                <p><?php esc_html_e( 'Use the responsive object to override properties at different breakpoints:', 'oxybridge-wp' ); ?></p>
                <pre><code>{
  "type": "Heading",
  "fontSize": "48px",
  "responsive": {
    "tablet": { "fontSize": "36px" },
    "phone": { "fontSize": "28px" }
  }
}</code></pre>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'Design Tokens', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( 'The /ai/tokens endpoint returns the site design tokens extracted from global styles:', 'oxybridge-wp' ); ?></p>

                <pre><code>{
  "colors": {
    "primary": "#0066cc",
    "secondary": "#1d293d",
    "accent": "#ff6b35",
    "text": "#333333",
    "background": "#ffffff"
  },
  "fonts": {
    "heading": "Inter, sans-serif",
    "body": "Inter, sans-serif"
  },
  "spacing": {
    "sm": "8px",
    "md": "16px",
    "lg": "32px",
    "xl": "64px"
  }
}</code></pre>
                <p><?php esc_html_e( 'AI agents can use these tokens to match the site brand when generating pages.', 'oxybridge-wp' ); ?></p>
            </div>

            <div class="oxybridge-card">
                <h2><?php esc_html_e( 'Quick Test', 'oxybridge-wp' ); ?></h2>
                <p><?php esc_html_e( 'Test the AI context endpoint:', 'oxybridge-wp' ); ?></p>
                <pre><code>curl "<?php echo esc_html( $api_base_url ); ?>ai/context"</code></pre>
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
