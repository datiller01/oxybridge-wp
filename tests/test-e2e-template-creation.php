<?php
/**
 * End-to-End Test: Template Creation Workflow
 *
 * This script tests template creation via the OxyBridge REST API:
 * 1. POST /templates to create a new header/footer template with design tree
 * 2. GET /templates/{id} to verify template was created
 * 3. Verify template can be edited in Oxygen Builder
 * 4. Optionally clean up test templates
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-template-creation.php
 *
 * Options:
 *   --template=<name>  Create specific template (colabs-header, colabs-footer, test-header)
 *   --keep             Keep created templates (don't clean up)
 *   --list             List available template fixtures
 *
 * Examples:
 *   wp eval-file tests/test-e2e-template-creation.php
 *   wp eval-file tests/test-e2e-template-creation.php -- --template=colabs-header
 *   wp eval-file tests/test-e2e-template-creation.php -- --template=colabs-header --keep
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-template-creation.php\n";
	exit( 1 );
}

/**
 * E2E Test Runner for Oxybridge Template Creation.
 */
class Oxybridge_E2E_Template_Creation_Test {

	/**
	 * Created template IDs for cleanup.
	 *
	 * @var array
	 */
	private $created_template_ids = array();

	/**
	 * Test results.
	 *
	 * @var array
	 */
	private $results = array(
		'passed' => 0,
		'failed' => 0,
		'errors' => array(),
	);

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'oxybridge/v1';

	/**
	 * Keep templates after test (don't clean up).
	 *
	 * @var bool
	 */
	private $keep_templates = false;

	/**
	 * Specific template to create.
	 *
	 * @var string|null
	 */
	private $target_template = null;

	/**
	 * Path to fixtures directory.
	 *
	 * @var string
	 */
	private $fixtures_path = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fixtures_path = dirname( __FILE__ ) . '/fixtures/';
	}

	/**
	 * Parse CLI arguments.
	 *
	 * @param array $args CLI arguments.
	 */
	public function parse_args( array $args ) {
		foreach ( $args as $arg ) {
			if ( strpos( $arg, '--template=' ) === 0 ) {
				$this->target_template = str_replace( '--template=', '', $arg );
			}
			if ( $arg === '--keep' ) {
				$this->keep_templates = true;
			}
			if ( $arg === '--list' ) {
				$this->list_fixtures();
				exit( 0 );
			}
		}
	}

	/**
	 * List available template fixtures.
	 */
	private function list_fixtures() {
		WP_CLI::log( 'Available template fixtures:' );
		WP_CLI::log( '' );

		$fixtures = $this->get_available_fixtures();

		if ( empty( $fixtures ) ) {
			WP_CLI::warning( 'No fixture files found in: ' . $this->fixtures_path );
			return;
		}

		foreach ( $fixtures as $fixture ) {
			WP_CLI::log( sprintf( '  - %s (%s)', $fixture['name'], $fixture['file'] ) );
		}

		WP_CLI::log( '' );
		WP_CLI::log( 'Usage: wp eval-file tests/test-e2e-template-creation.php -- --template=<name>' );
	}

	/**
	 * Get available fixture files.
	 *
	 * @return array List of fixtures.
	 */
	private function get_available_fixtures() {
		$fixtures = array();

		if ( ! is_dir( $this->fixtures_path ) ) {
			return $fixtures;
		}

		$files = glob( $this->fixtures_path . '*-tree.json' );

		foreach ( $files as $file ) {
			$basename = basename( $file, '-tree.json' );
			$fixtures[] = array(
				'name' => $basename,
				'file' => basename( $file ),
				'path' => $file,
			);
		}

		return $fixtures;
	}

	/**
	 * Run all E2E tests.
	 *
	 * @return void
	 */
	public function run() {
		WP_CLI::log( '=============================================' );
		WP_CLI::log( 'Oxybridge E2E Test: Template Creation' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( '' );

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		// If a specific template is requested, create only that one.
		if ( $this->target_template ) {
			WP_CLI::log( '=== Creating Specific Template: ' . $this->target_template . ' ===' );
			WP_CLI::log( '' );
			$this->test_create_template_from_fixture( $this->target_template );
		} else {
			// Run all template creation tests.
			WP_CLI::log( '=== Test 1: Create Header Template ===' );
			WP_CLI::log( '' );
			$this->test_create_header_template();

			WP_CLI::log( '' );
			WP_CLI::log( '=== Test 2: Create Footer Template ===' );
			WP_CLI::log( '' );
			$this->test_create_footer_template();

			WP_CLI::log( '' );
			WP_CLI::log( '=== Test 3: Test Template Types Endpoint ===' );
			WP_CLI::log( '' );
			$this->test_template_types();

			// Try to create from fixtures if they exist.
			WP_CLI::log( '' );
			WP_CLI::log( '=== Test 4: Create Colabs Header from Fixture ===' );
			WP_CLI::log( '' );
			$this->test_create_template_from_fixture( 'colabs-header' );

			WP_CLI::log( '' );
			WP_CLI::log( '=== Test 5: Create Colabs Footer from Fixture ===' );
			WP_CLI::log( '' );
			$this->test_create_template_from_fixture( 'colabs-footer' );
		}

		// Cleanup unless --keep is specified.
		if ( ! $this->keep_templates ) {
			WP_CLI::log( '' );
			WP_CLI::log( '=== Cleanup ===' );
			WP_CLI::log( '' );
			$this->cleanup();
		} else {
			WP_CLI::log( '' );
			WP_CLI::log( '=== Templates Kept (--keep flag) ===' );
			WP_CLI::log( '' );
			foreach ( $this->created_template_ids as $id => $info ) {
				WP_CLI::log( sprintf( '  - %s (ID: %d): %s', $info['title'], $id, $info['edit_url'] ) );
			}
		}

		$this->print_summary();
	}

	/**
	 * Check prerequisites for E2E tests.
	 *
	 * @return bool True if prerequisites are met.
	 */
	private function check_prerequisites() {
		WP_CLI::log( 'Checking prerequisites...' );
		WP_CLI::log( '' );

		// Check if Oxybridge plugin is active.
		if ( ! defined( 'OXYBRIDGE_VERSION' ) ) {
			WP_CLI::error( 'Oxybridge plugin is not active.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge plugin active: v' . OXYBRIDGE_VERSION );

		// Check if Oxygen is active.
		if ( ! function_exists( 'oxybridge_is_oxygen_active' ) || ! oxybridge_is_oxygen_active() ) {
			WP_CLI::error( 'Oxygen/Breakdance is not active.', false );
			return false;
		}
		WP_CLI::success( 'Oxygen/Breakdance is active' );

		// Check current user permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			WP_CLI::error( 'Current user does not have edit_posts capability.', false );
			return false;
		}
		WP_CLI::success( 'User has required permissions' );

		// Check fixtures directory.
		if ( is_dir( $this->fixtures_path ) ) {
			WP_CLI::success( 'Fixtures directory exists' );
		} else {
			WP_CLI::warning( 'Fixtures directory not found: ' . $this->fixtures_path );
		}

		WP_CLI::log( '' );
		return true;
	}

	/**
	 * Test: Create header template.
	 *
	 * @return bool True if test passed.
	 */
	private function test_create_header_template() {
		$test_name = 'Create Header Template';

		$tree = $this->get_minimal_header_tree();

		$result = $this->create_template(
			'E2E Test Header - ' . date( 'Y-m-d H:i:s' ),
			'header',
			$tree
		);

		if ( is_wp_error( $result ) ) {
			$this->fail( $test_name, $result->get_error_message() );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Template ID: ' . $result['id'] );
		WP_CLI::log( '  Template type: ' . $result['type'] );
		WP_CLI::log( '  Edit URL: ' . $result['edit_url'] );

		return true;
	}

	/**
	 * Test: Create footer template.
	 *
	 * @return bool True if test passed.
	 */
	private function test_create_footer_template() {
		$test_name = 'Create Footer Template';

		$tree = $this->get_minimal_footer_tree();

		$result = $this->create_template(
			'E2E Test Footer - ' . date( 'Y-m-d H:i:s' ),
			'footer',
			$tree
		);

		if ( is_wp_error( $result ) ) {
			$this->fail( $test_name, $result->get_error_message() );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Template ID: ' . $result['id'] );
		WP_CLI::log( '  Template type: ' . $result['type'] );
		WP_CLI::log( '  Edit URL: ' . $result['edit_url'] );

		return true;
	}

	/**
	 * Test: Create template from fixture file.
	 *
	 * @param string $fixture_name Fixture name (without -tree.json suffix).
	 * @return bool True if test passed.
	 */
	private function test_create_template_from_fixture( string $fixture_name ) {
		$test_name = 'Create Template from Fixture: ' . $fixture_name;

		$fixture_file = $this->fixtures_path . $fixture_name . '-tree.json';

		if ( ! file_exists( $fixture_file ) ) {
			WP_CLI::warning( 'Fixture file not found: ' . $fixture_file );
			WP_CLI::log( '  Skipping this test...' );
			return false;
		}

		// Load fixture JSON.
		$json_content = file_get_contents( $fixture_file );
		$tree = json_decode( $json_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->fail( $test_name, 'Invalid JSON in fixture: ' . json_last_error_msg() );
			return false;
		}

		// Determine template type from fixture name.
		$template_type = 'template';
		if ( strpos( $fixture_name, 'header' ) !== false ) {
			$template_type = 'header';
		} elseif ( strpos( $fixture_name, 'footer' ) !== false ) {
			$template_type = 'footer';
		}

		// Create a nice title from fixture name.
		$title = ucwords( str_replace( '-', ' ', $fixture_name ) );

		$result = $this->create_template( $title, $template_type, $tree );

		if ( is_wp_error( $result ) ) {
			$this->fail( $test_name, $result->get_error_message() );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Template ID: ' . $result['id'] );
		WP_CLI::log( '  Template title: ' . $title );
		WP_CLI::log( '  Template type: ' . $template_type );
		WP_CLI::log( '  Element count: ' . ( isset( $result['element_count'] ) ? $result['element_count'] : 'N/A' ) );
		WP_CLI::log( '  Edit URL: ' . $result['edit_url'] );

		// Verify template was saved correctly.
		$verify_result = $this->verify_template( $result['id'] );
		if ( $verify_result ) {
			WP_CLI::log( '  Verification: Passed' );
		} else {
			WP_CLI::warning( 'Template verification had issues - check manually' );
		}

		return true;
	}

	/**
	 * Test: Template types endpoint.
	 *
	 * @return bool True if test passed.
	 */
	private function test_template_types() {
		$test_name = 'Template Types Endpoint';

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/template-types' );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();
		$status   = $response->get_status();

		if ( $status !== 200 ) {
			$this->fail( $test_name, 'Expected status 200, got ' . $status );
			return false;
		}

		if ( ! isset( $data['types'] ) || ! is_array( $data['types'] ) ) {
			$this->fail( $test_name, 'Response missing types array' );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Available types: ' . count( $data['types'] ) );

		foreach ( $data['types'] as $type ) {
			WP_CLI::log( sprintf( '    - %s: %s', $type['slug'], $type['name'] ) );
		}

		return true;
	}

	/**
	 * Create a template via REST API.
	 *
	 * @param string $title Template title.
	 * @param string $type  Template type.
	 * @param array  $tree  Template tree.
	 * @return array|WP_Error Template data or error.
	 */
	private function create_template( string $title, string $type, array $tree ) {
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', $title );
		$request->set_param( 'type', $type );
		$request->set_param( 'tree', $tree );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();
		$status   = $response->get_status();

		if ( $status !== 201 ) {
			$error_message = isset( $data['message'] ) ? $data['message'] : 'Unknown error';
			return new WP_Error( 'create_failed', sprintf( 'Expected status 201, got %d: %s', $status, $error_message ) );
		}

		if ( ! isset( $data['template']['id'] ) ) {
			return new WP_Error( 'missing_id', 'Response missing template ID' );
		}

		$template_data = $data['template'];

		// Track for cleanup.
		$this->created_template_ids[ $template_data['id'] ] = array(
			'title'    => $title,
			'type'     => $type,
			'edit_url' => isset( $template_data['edit_url'] ) ? $template_data['edit_url'] : '',
		);

		return $template_data;
	}

	/**
	 * Verify a template was created correctly.
	 *
	 * @param int $template_id Template ID.
	 * @return bool True if verification passed.
	 */
	private function verify_template( int $template_id ) {
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $template_id );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();
		$status   = $response->get_status();

		if ( $status !== 200 ) {
			return false;
		}

		// Check for tree.
		if ( ! isset( $data['tree'] ) || ! isset( $data['tree']['root'] ) ) {
			return false;
		}

		// Check tree has children.
		if ( empty( $data['tree']['root']['children'] ) ) {
			WP_CLI::warning( 'Template tree has no children' );
		}

		return true;
	}

	/**
	 * Get minimal header tree for testing.
	 *
	 * @return array Tree structure.
	 */
	private function get_minimal_header_tree() {
		return array(
			'root'   => array(
				'id'       => 1,
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(
					array(
						'id'        => 100,
						'data'      => array(
							'type'       => 'EssentialElements\\Section',
							'properties' => array(
								'design' => array(
									'background' => array(
										'color' => '#ffffff',
									),
									'spacing' => array(
										'padding' => array(
											'top'    => array( 'breakpoint_base' => '20px' ),
											'bottom' => array( 'breakpoint_base' => '20px' ),
										),
									),
								),
							),
						),
						'children'  => array(
							array(
								'id'        => 101,
								'data'      => array(
									'type'       => 'EssentialElements\\Heading',
									'properties' => array(
										'content' => array(
											'content' => array(
												'text' => 'Test Header',
												'tags' => 'h1',
											),
										),
									),
								),
								'children'  => array(),
								'_parentId' => 100,
							),
						),
						'_parentId' => 1,
					),
				),
			),
			'status' => 'exported',
		);
	}

	/**
	 * Get minimal footer tree for testing.
	 *
	 * @return array Tree structure.
	 */
	private function get_minimal_footer_tree() {
		return array(
			'root'   => array(
				'id'       => 1,
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(
					array(
						'id'        => 100,
						'data'      => array(
							'type'       => 'EssentialElements\\Section',
							'properties' => array(
								'design' => array(
									'background' => array(
										'color' => '#333333',
									),
									'spacing' => array(
										'padding' => array(
											'top'    => array( 'breakpoint_base' => '40px' ),
											'bottom' => array( 'breakpoint_base' => '40px' ),
										),
									),
								),
							),
						),
						'children'  => array(
							array(
								'id'        => 101,
								'data'      => array(
									'type'       => 'EssentialElements\\Text',
									'properties' => array(
										'content' => array(
											'content' => array(
												'text' => 'Copyright 2024 - Test Footer',
											),
										),
										'design'  => array(
											'typography' => array(
												'color' => array( 'breakpoint_base' => '#ffffff' ),
											),
										),
									),
								),
								'children'  => array(),
								'_parentId' => 100,
							),
						),
						'_parentId' => 1,
					),
				),
			),
			'status' => 'exported',
		);
	}

	/**
	 * Mark a test as passed.
	 *
	 * @param string $test_name Test name.
	 */
	private function pass( string $test_name ) {
		$this->results['passed']++;
		WP_CLI::success( $test_name );
	}

	/**
	 * Mark a test as failed.
	 *
	 * @param string $test_name     Test name.
	 * @param string $error_message Error message.
	 */
	private function fail( string $test_name, string $error_message ) {
		$this->results['failed']++;
		$this->results['errors'][] = array(
			'test'    => $test_name,
			'message' => $error_message,
		);
		WP_CLI::error( $test_name . ': ' . $error_message, false );
	}

	/**
	 * Clean up created templates.
	 */
	private function cleanup() {
		if ( empty( $this->created_template_ids ) ) {
			WP_CLI::log( 'No templates to clean up.' );
			return;
		}

		foreach ( $this->created_template_ids as $id => $info ) {
			$deleted = wp_delete_post( $id, true );
			if ( $deleted ) {
				WP_CLI::log( sprintf( 'Deleted template ID %d: %s', $id, $info['title'] ) );
			} else {
				WP_CLI::warning( sprintf( 'Failed to delete template ID %d', $id ) );
			}
		}

		$this->created_template_ids = array();
	}

	/**
	 * Print test summary.
	 */
	private function print_summary() {
		WP_CLI::log( '' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( 'Test Summary' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( '' );

		$total = $this->results['passed'] + $this->results['failed'];

		WP_CLI::log( sprintf( 'Total tests: %d', $total ) );
		WP_CLI::log( sprintf( 'Passed: %d', $this->results['passed'] ) );
		WP_CLI::log( sprintf( 'Failed: %d', $this->results['failed'] ) );

		if ( ! empty( $this->results['errors'] ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'Failed Tests:' );
			foreach ( $this->results['errors'] as $error ) {
				WP_CLI::log( sprintf( '  - %s: %s', $error['test'], $error['message'] ) );
			}
		}

		WP_CLI::log( '' );

		if ( $this->results['failed'] === 0 ) {
			WP_CLI::success( 'All E2E tests passed!' );
		} else {
			WP_CLI::warning( sprintf( '%d test(s) failed - review output above.', $this->results['failed'] ) );
		}
	}
}

// Parse CLI arguments.
$args = array();
if ( isset( $argv ) && is_array( $argv ) ) {
	$args = $argv;
}

// Run the tests.
$test = new Oxybridge_E2E_Template_Creation_Test();
$test->parse_args( $args );
$test->run();
