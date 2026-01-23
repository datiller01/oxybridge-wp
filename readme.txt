=== Oxybridge WP ===
Contributors: oxybridge-contributors
Tags: oxygen, ai, builder, api, rest
Requires at least: 5.9
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

REST API bridge for Oxygen Builder - Enables external tools to read, query, and modify Oxygen templates.

== Description ==

Oxybridge WP is a WordPress plugin that exposes a secure REST API for Oxygen Builder. It allows external tools to read and query Oxygen template data, styles, and element structures.

**Key Features:**

* **Template Access** - List, search, and retrieve full Oxygen template data via REST API
* **Element Parsing** - Query individual elements within templates with hierarchy information
* **Global Styles** - Access Oxygen's global color palettes, fonts, and design tokens
* **Secure Authentication** - Uses WordPress application passwords for API security

**Use Cases:**

* Let external tools understand your Oxygen site structure
* Query templates programmatically for documentation
* Build custom integrations with Oxygen Builder data
* Enable AI-powered design assistance workflows

**Requirements:**

* WordPress 5.9 or higher
* PHP 7.4 or higher
* Oxygen Builder 6.x (Breakdance-based) or classic Oxygen Builder

== Installation ==

**Automatic Installation:**

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "Oxybridge WP"
4. Click "Install Now" and then "Activate"

**Manual Installation:**

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Select the downloaded ZIP file and click "Install Now"
5. Activate the plugin after installation completes

**Manual Installation via FTP:**

1. Download and extract the plugin files
2. Upload the `oxybridge-wp` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress Plugins menu

**Configuration:**

1. Ensure Oxygen Builder is installed and activated
2. Create an Application Password in WordPress:
   - Go to Users > Profile (or your user profile)
   - Scroll to "Application Passwords"
   - Enter a name (e.g., "Oxybridge") and click "Add New Application Password"
   - Copy the generated password (you won't see it again)
3. The REST API will be available at `/wp-json/oxybridge/v1/`

== Frequently Asked Questions ==

= Does this plugin require Oxygen Builder? =

Yes, Oxybridge WP requires Oxygen Builder to be installed and activated. Without Oxygen, the plugin will display an admin notice and will not function.

= What version of Oxygen Builder is supported? =

Oxybridge WP supports Oxygen 6.x (the Breakdance-based rewrite) as well as classic Oxygen Builder versions. The plugin automatically detects which version is installed.

= How do I authenticate API requests? =

The plugin uses WordPress Application Passwords for authentication. Create an application password in your WordPress user profile and include it with API requests using HTTP Basic Auth.

= What data can be accessed through the API? =

The API provides read-only access to:
- Oxygen templates (list, search, individual template details)
- Template element structures (parsed JSON hierarchy)
- Global styles (colors, fonts, spacing, design tokens)

= Is write access supported? =

No, the current version (1.0.0) is read-only. Write operations for modifying templates are planned for a future release.

= Is my data secure? =

Yes, all API endpoints require authentication. Unauthenticated requests are rejected. The plugin follows WordPress security best practices including input sanitization, capability checks, and proper permission callbacks.

= Can I use this with Breakdance Builder? =

Currently, Oxybridge WP is specifically designed for Oxygen Builder. Breakdance compatibility may be added in a future release.

== Changelog ==

= 1.0.0 =
* Initial release
* REST API endpoints for templates, elements, and global styles
* Application password authentication
* Support for Oxygen 6.x (Breakdance-based) and classic Oxygen

== Upgrade Notice ==

= 1.0.0 =
Initial release of Oxybridge WP.

== REST API Endpoints ==

The plugin registers the following REST API endpoints under the `oxybridge/v1` namespace:

**Templates:**

* `GET /wp-json/oxybridge/v1/templates` - List all Oxygen templates
* `GET /wp-json/oxybridge/v1/templates/{id}` - Get a specific template by ID

**Elements:**

* `GET /wp-json/oxybridge/v1/templates/{id}/elements` - Get elements within a template

**Styles:**

* `GET /wp-json/oxybridge/v1/styles/global` - Get global style settings

All endpoints require authentication via Application Passwords.

== Screenshots ==

1. Plugin activation in WordPress admin
2. Application password configuration
3. REST API response example

== Additional Resources ==

* [GitHub Repository](https://github.com/your-repo/oxybridge)
