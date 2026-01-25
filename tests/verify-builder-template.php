<?php
/**
 * Template Builder Verification Script
 *
 * This script verifies that templates can be loaded in Oxygen Builder without errors.
 * It performs automated pre-checks and generates a browser verification checklist.
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/verify-builder-template.php
 *
 * Options:
 *   --template=<id>    Verify specific template by ID
 *   --fixture=<name>   Verify fixture file (e.g., colabs-header)
 *   --create           Create template from fixture before verifying
 *   --json             Output results as JSON
 *
 * Examples:
 *   wp eval-file tests/verify-builder-template.php -- --fixture=colabs-header --create
 *   wp eval-file tests/verify-builder-template.php -- --template=123
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/verify-builder-template.php\n";
	exit( 1 );
}

/**
 * Template Builder Verification Class.
 */
class Oxybridge_Template_Verification {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'oxybridge/v1';

	/**
	 * Path to fixtures directory.
	 *
	 * @var string
	 */
	private $fixtures_path = '';

	/**
	 * Verification results.
	 *
	 * @var array
	 */
	private $results = array(
		'automated_checks'    => array(),
		'browser_checks'      => array(),
		'template_id'         => null,
		'template_title'      => '',
		'edit_url'            => '',
		'status'              => 'pending',
		'errors'              => array(),
		'warnings'            => array(),
	);

	/**
	 * Output as JSON flag.
	 *
	 * @var bool
	 */
	private $json_output = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fixtures_path = dirname( __FILE__ ) . '/fixtures/';
	}

	/**
	 * Run verification.
	 *
	 * @param array $args CLI arguments.
	 */
	public function run( array $args ) {
		$template_id = null;
		$fixture_name = null;
		$create_template = false;

		// Parse arguments.
		foreach ( $args as $arg ) {
			if ( strpos( $arg, '--template=' ) === 0 ) {
				$template_id = (int) str_replace( '--template=', '', $arg );
			}
			if ( strpos( $arg, '--fixture=' ) === 0 ) {
				$fixture_name = str_replace( '--fixture=', '', $arg );
			}
			if ( $arg === '--create' ) {
				$create_template = true;
			}
			if ( $arg === '--json' ) {
				$this->json_output = true;
			}
		}

		if ( ! $this->json_output ) {
			WP_CLI::log( '=============================================' );
			WP_CLI::log( 'OxyBridge Template Builder Verification' );
			WP_CLI::log( '=============================================' );
			WP_CLI::log( '' );
		}

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			$this->output_results();
			return;
		}

		// If fixture specified, verify or create from fixture.
		if ( $fixture_name ) {
			$template_id = $this->handle_fixture( $fixture_name, $create_template );
			if ( ! $template_id ) {
				$this->output_results();
				return;
			}
		}

		// If we have a template ID, verify it.
		if ( $template_id ) {
			$this->verify_template( $template_id );
		} else {
			// Find existing Colabs Header templates.
			$this->find_and_verify_colabs_header();
		}

		$this->output_results();
	}

	/**
	 * Check prerequisites.
	 *
	 * @return bool True if prerequisites are met.
	 */
	private function check_prerequisites() {
		if ( ! defined( 'OXYBRIDGE_VERSION' ) ) {
			$this->add_error( 'Oxybridge plugin is not active' );
			return false;
		}

		if ( ! function_exists( 'oxybridge_is_oxygen_active' ) || ! oxybridge_is_oxygen_active() ) {
			$this->add_error( 'Oxygen/Breakdance is not active' );
			return false;
		}

		$this->add_check( 'prerequisites', 'Plugin Prerequisites', true, 'OxyBridge and Oxygen are active' );
		return true;
	}

	/**
	 * Handle fixture file.
	 *
	 * @param string $fixture_name    Fixture name.
	 * @param bool   $create_template Whether to create template.
	 * @return int|null Template ID or null on error.
	 */
	private function handle_fixture( string $fixture_name, bool $create_template ) {
		$fixture_file = $this->fixtures_path . $fixture_name . '-tree.json';

		if ( ! file_exists( $fixture_file ) ) {
			$this->add_error( 'Fixture file not found: ' . $fixture_file );
			return null;
		}

		// Load and validate fixture.
		$json_content = file_get_contents( $fixture_file );
		$tree = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->add_error( 'Invalid JSON in fixture: ' . json_last_error_msg() );
			return null;
		}

		$this->add_check( 'fixture_json', 'Fixture JSON Valid', true, 'JSON parsed successfully' );

		// Validate tree structure.
		$tree_valid = $this->validate_tree_structure( $tree );

		if ( ! $tree_valid ) {
			return null;
		}

		// If create flag, create template.
		if ( $create_template ) {
			return $this->create_template_from_fixture( $fixture_name, $tree );
		}

		// Otherwise, find existing template with matching name.
		return $this->find_template_by_title( ucwords( str_replace( '-', ' ', $fixture_name ) ) );
	}

	/**
	 * Validate tree structure.
	 *
	 * @param array $tree Tree to validate.
	 * @return bool True if valid.
	 */
	private function validate_tree_structure( array $tree ) {
		$errors = array();
		$checks = array();

		// Check root exists.
		if ( ! isset( $tree['root'] ) ) {
			$errors[] = 'Missing root element';
		} else {
			$checks['root_exists'] = true;
		}

		// Check status.
		if ( ! isset( $tree['status'] ) || $tree['status'] !== 'exported' ) {
			$errors[] = 'Missing or invalid status (must be "exported")';
		} else {
			$checks['status_valid'] = true;
		}

		// Check root structure.
		if ( isset( $tree['root'] ) ) {
			$root = $tree['root'];

			if ( ! isset( $root['id'] ) || ! is_int( $root['id'] ) ) {
				$errors[] = 'Root id must be an integer';
			}

			if ( ! isset( $root['data']['type'] ) || $root['data']['type'] !== 'root' ) {
				$errors[] = 'Root data.type must be "root"';
			}

			if ( ! array_key_exists( 'properties', $root['data'] ) || $root['data']['properties'] !== null ) {
				$errors[] = 'Root data.properties must be null';
			}

			if ( ! isset( $root['children'] ) || ! is_array( $root['children'] ) ) {
				$errors[] = 'Root children must be an array';
			}

			$checks['root_structure'] = empty( $errors );
		}

		// Validate children recursively.
		if ( isset( $tree['root']['children'] ) ) {
			$element_count = 0;
			$parent_errors = $this->validate_children( $tree['root']['children'], $tree['root']['id'], $element_count );
			$errors = array_merge( $errors, $parent_errors );

			$this->add_check( 'element_count', 'Element Count', true, $element_count . ' elements found' );
		}

		// Report results.
		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				$this->add_error( 'Tree validation: ' . $error );
			}
			$this->add_check( 'tree_structure', 'Tree Structure Valid', false, implode( '; ', $errors ) );
			return false;
		}

		$this->add_check( 'tree_structure', 'Tree Structure Valid', true, 'All structure checks passed' );
		return true;
	}

	/**
	 * Validate children recursively.
	 *
	 * @param array $children      Children array.
	 * @param int   $expected_parent Expected parent ID.
	 * @param int   $element_count  Element counter (by reference).
	 * @return array Errors found.
	 */
	private function validate_children( array $children, int $expected_parent, int &$element_count ) {
		$errors = array();

		foreach ( $children as $child ) {
			$element_count++;

			// Check required fields.
			if ( ! isset( $child['id'] ) || ! is_int( $child['id'] ) ) {
				$errors[] = 'Element missing integer id';
			}

			if ( ! isset( $child['data']['type'] ) ) {
				$errors[] = 'Element ' . ( $child['id'] ?? '?' ) . ' missing data.type';
			}

			if ( ! isset( $child['children'] ) || ! is_array( $child['children'] ) ) {
				$errors[] = 'Element ' . ( $child['id'] ?? '?' ) . ' missing children array';
			}

			// Check parent ID.
			if ( isset( $child['_parentId'] ) && $child['_parentId'] !== $expected_parent ) {
				$errors[] = 'Element ' . ( $child['id'] ?? '?' ) . ' has wrong _parentId (expected ' . $expected_parent . ', got ' . $child['_parentId'] . ')';
			}

			// Check element type format.
			if ( isset( $child['data']['type'] ) ) {
				$type = $child['data']['type'];
				if ( strpos( $type, '\\' ) === false && $type !== 'root' ) {
					$errors[] = 'Element ' . ( $child['id'] ?? '?' ) . ' type "' . $type . '" missing namespace (should be EssentialElements\\Type)';
				}
			}

			// Recursively validate children.
			if ( isset( $child['children'] ) && ! empty( $child['children'] ) ) {
				$child_errors = $this->validate_children( $child['children'], $child['id'] ?? 0, $element_count );
				$errors = array_merge( $errors, $child_errors );
			}
		}

		return $errors;
	}

	/**
	 * Create template from fixture.
	 *
	 * @param string $fixture_name Fixture name.
	 * @param array  $tree         Tree data.
	 * @return int|null Template ID or null on error.
	 */
	private function create_template_from_fixture( string $fixture_name, array $tree ) {
		$title = ucwords( str_replace( '-', ' ', $fixture_name ) );
		$type = strpos( $fixture_name, 'header' ) !== false ? 'header' : ( strpos( $fixture_name, 'footer' ) !== false ? 'footer' : 'template' );

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', $title );
		$request->set_param( 'type', $type );
		$request->set_param( 'tree', $tree );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();
		$status   = $response->get_status();

		if ( $status !== 201 ) {
			$error_msg = isset( $data['message'] ) ? $data['message'] : 'Unknown error';
			$this->add_error( 'Failed to create template: ' . $error_msg );
			return null;
		}

		$template_id = $data['template']['id'];
		$this->results['template_id'] = $template_id;
		$this->results['template_title'] = $title;
		$this->results['edit_url'] = $data['template']['edit_url'] ?? '';

		$this->add_check( 'template_created', 'Template Created', true, 'ID: ' . $template_id );

		return $template_id;
	}

	/**
	 * Find template by title.
	 *
	 * @param string $title Template title.
	 * @return int|null Template ID or null.
	 */
	private function find_template_by_title( string $title ) {
		$posts = get_posts( array(
			'post_type'      => array( 'breakdance_header', 'breakdance_footer', 'breakdance_template' ),
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'title'          => $title,
		) );

		if ( empty( $posts ) ) {
			$this->add_warning( 'Template not found: "' . $title . '". Use --create to create it.' );
			return null;
		}

		$template_id = $posts[0]->ID;
		$this->results['template_id'] = $template_id;
		$this->results['template_title'] = $posts[0]->post_title;

		return $template_id;
	}

	/**
	 * Find and verify Colabs Header template.
	 */
	private function find_and_verify_colabs_header() {
		// Search for existing Colabs Header templates.
		$posts = get_posts( array(
			'post_type'      => array( 'breakdance_header', 'breakdance_footer', 'breakdance_template' ),
			'post_status'    => 'any',
			'posts_per_page' => 10,
			's'              => 'Colabs',
		) );

		if ( empty( $posts ) ) {
			if ( ! $this->json_output ) {
				WP_CLI::log( 'No Colabs templates found.' );
				WP_CLI::log( '' );
				WP_CLI::log( 'To create the Colabs Header template:' );
				WP_CLI::log( '  wp eval-file tests/verify-builder-template.php -- --fixture=colabs-header --create' );
				WP_CLI::log( '' );
			}

			// Verify the fixture file instead.
			$fixture_file = $this->fixtures_path . 'colabs-header-tree.json';
			if ( file_exists( $fixture_file ) ) {
				$this->handle_fixture( 'colabs-header', false );
			}
			return;
		}

		// List found templates.
		if ( ! $this->json_output ) {
			WP_CLI::log( 'Found ' . count( $posts ) . ' Colabs template(s):' );
			foreach ( $posts as $post ) {
				WP_CLI::log( sprintf( '  - ID %d: %s (%s)', $post->ID, $post->post_title, $post->post_type ) );
			}
			WP_CLI::log( '' );
		}

		// Verify the first one.
		$this->verify_template( $posts[0]->ID );
	}

	/**
	 * Verify template.
	 *
	 * @param int $template_id Template ID.
	 */
	private function verify_template( int $template_id ) {
		$post = get_post( $template_id );

		if ( ! $post ) {
			$this->add_error( 'Template not found: ID ' . $template_id );
			return;
		}

		$this->results['template_id'] = $template_id;
		$this->results['template_title'] = $post->post_title;

		// Determine template type from post type or title.
		$template_type = 'template';
		if ( $post->post_type === 'breakdance_header' || stripos( $post->post_title, 'header' ) !== false ) {
			$template_type = 'header';
		} elseif ( $post->post_type === 'breakdance_footer' || stripos( $post->post_title, 'footer' ) !== false ) {
			$template_type = 'footer';
		}

		// Get edit URL.
		$site_url = get_site_url();
		$this->results['edit_url'] = $site_url . '/?breakdance_iframe=true&id=' . $template_id;

		$this->add_check( 'template_exists', 'Template Exists', true, 'Post ID: ' . $template_id );

		// Check tree data exists.
		$tree = get_post_meta( $template_id, '_breakdance_data', true );
		$element_count = 0;

		if ( empty( $tree ) ) {
			$this->add_error( 'Template has no tree data (_breakdance_data meta)' );
			$this->add_check( 'tree_saved', 'Tree Data Saved', false, 'No tree data found' );
		} else {
			if ( is_string( $tree ) ) {
				$tree = json_decode( $tree, true );
			}

			if ( isset( $tree['root'] ) && isset( $tree['root']['children'] ) ) {
				$this->count_elements( $tree['root']['children'], $element_count );
				$this->add_check( 'tree_saved', 'Tree Data Saved', true, $element_count . ' elements in tree' );
			} else {
				$this->add_check( 'tree_saved', 'Tree Data Saved', false, 'Tree structure invalid' );
			}
		}

		// Check post status.
		$this->add_check( 'post_status', 'Post Status', true, $post->post_status );

		// Add browser verification checklist.
		$this->add_browser_checks( $template_id, $template_type, $element_count );
	}

	/**
	 * Count elements in tree.
	 *
	 * @param array $children     Children array.
	 * @param int   $count        Counter (by reference).
	 */
	private function count_elements( array $children, int &$count ) {
		foreach ( $children as $child ) {
			$count++;
			if ( isset( $child['children'] ) && is_array( $child['children'] ) ) {
				$this->count_elements( $child['children'], $count );
			}
		}
	}

	/**
	 * Add browser verification checks.
	 *
	 * @param int    $template_id Template ID.
	 * @param string $template_type Template type (header, footer, etc.).
	 * @param int    $element_count Number of elements in tree.
	 */
	private function add_browser_checks( int $template_id, string $template_type = 'template', int $element_count = 0 ) {
		$site_url = get_site_url();
		$edit_url = $site_url . '/?breakdance_iframe=true&id=' . $template_id;

		// Determine structure description based on type.
		$structure_desc = 'Section > Div > elements';
		if ( $template_type === 'header' ) {
			$structure_desc = 'Section > Div > Logo, Nav, Actions';
		} elseif ( $template_type === 'footer' ) {
			$structure_desc = 'Section > Div > Locations, Navigation, Social, Copyright';
		}

		$element_text = $element_count > 0 ? $element_count . ' elements total visible' : 'All elements visible';

		$this->results['browser_checks'] = array(
			array(
				'check' => 'Builder loads without JavaScript errors',
				'steps' => array(
					'Open: ' . $edit_url,
					'Open browser Developer Tools (F12)',
					'Check Console tab for JavaScript errors',
					'Look for IO-TS validation errors',
				),
				'expected' => 'No errors in console',
				'status' => 'pending',
			),
			array(
				'check' => ucfirst( $template_type ) . ' structure visible in element tree',
				'steps' => array(
					'Look at the left sidebar (Structure panel)',
					'Verify Section element is visible',
					'Expand to see child elements (Div, Link, Text, Button)',
					'Confirm ' . $element_text,
				),
				'expected' => 'All elements visible in tree: ' . $structure_desc,
				'status' => 'pending',
			),
			array(
				'check' => 'No IO-TS validation errors',
				'steps' => array(
					'Check console for "IO-TS" or "validation" errors',
					'Check for "invalid" or "unexpected" property errors',
					'Verify no red error banners in builder UI',
				),
				'expected' => 'No validation errors',
				'status' => 'pending',
			),
			array(
				'check' => 'Elements are selectable and editable',
				'steps' => array(
					'Click on the ' . $template_type . ' Section in preview',
					'Verify element becomes selected',
					'Check right sidebar shows element properties',
					'Try changing a property (e.g., background color)',
				),
				'expected' => 'Elements respond to selection and property changes work',
				'status' => 'pending',
			),
		);
	}

	/**
	 * Add automated check result.
	 *
	 * @param string $id      Check ID.
	 * @param string $name    Check name.
	 * @param bool   $passed  Whether check passed.
	 * @param string $message Result message.
	 */
	private function add_check( string $id, string $name, bool $passed, string $message ) {
		$this->results['automated_checks'][ $id ] = array(
			'name'    => $name,
			'passed'  => $passed,
			'message' => $message,
		);

		if ( ! $this->json_output ) {
			if ( $passed ) {
				WP_CLI::success( $name . ': ' . $message );
			} else {
				WP_CLI::error( $name . ': ' . $message, false );
			}
		}
	}

	/**
	 * Add error.
	 *
	 * @param string $message Error message.
	 */
	private function add_error( string $message ) {
		$this->results['errors'][] = $message;
		$this->results['status'] = 'failed';

		if ( ! $this->json_output ) {
			WP_CLI::error( $message, false );
		}
	}

	/**
	 * Add warning.
	 *
	 * @param string $message Warning message.
	 */
	private function add_warning( string $message ) {
		$this->results['warnings'][] = $message;

		if ( ! $this->json_output ) {
			WP_CLI::warning( $message );
		}
	}

	/**
	 * Output results.
	 */
	private function output_results() {
		// Determine overall status.
		if ( empty( $this->results['errors'] ) ) {
			$this->results['status'] = 'passed';
		}

		if ( $this->json_output ) {
			echo json_encode( $this->results, JSON_PRETTY_PRINT ) . "\n";
			return;
		}

		// Print summary.
		WP_CLI::log( '' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( 'Verification Summary' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( '' );

		if ( $this->results['template_id'] ) {
			WP_CLI::log( 'Template ID: ' . $this->results['template_id'] );
			WP_CLI::log( 'Template Title: ' . $this->results['template_title'] );
			WP_CLI::log( 'Edit URL: ' . $this->results['edit_url'] );
			WP_CLI::log( '' );
		}

		// Automated checks summary.
		$passed = 0;
		$failed = 0;
		foreach ( $this->results['automated_checks'] as $check ) {
			if ( $check['passed'] ) {
				$passed++;
			} else {
				$failed++;
			}
		}
		WP_CLI::log( sprintf( 'Automated Checks: %d passed, %d failed', $passed, $failed ) );

		// Browser verification checklist.
		if ( ! empty( $this->results['browser_checks'] ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( '=== Browser Verification Checklist ===' );
			WP_CLI::log( '' );
			WP_CLI::log( 'Open in browser: ' . $this->results['edit_url'] );
			WP_CLI::log( '' );

			foreach ( $this->results['browser_checks'] as $i => $check ) {
				WP_CLI::log( sprintf( '[ ] %d. %s', $i + 1, $check['check'] ) );
				foreach ( $check['steps'] as $step ) {
					WP_CLI::log( '       - ' . $step );
				}
				WP_CLI::log( '       Expected: ' . $check['expected'] );
				WP_CLI::log( '' );
			}
		}

		// Final status.
		WP_CLI::log( '=============================================' );
		if ( $this->results['status'] === 'passed' ) {
			WP_CLI::success( 'All automated checks passed! Complete browser verification manually.' );
		} else {
			WP_CLI::warning( 'Some checks failed. Review errors above.' );
		}
	}
}

// Parse CLI arguments.
$args = array();
if ( isset( $argv ) && is_array( $argv ) ) {
	$args = $argv;
}

// Run verification.
$verification = new Oxybridge_Template_Verification();
$verification->run( $args );
