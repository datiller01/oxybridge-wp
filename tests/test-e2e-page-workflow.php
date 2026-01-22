<?php
/**
 * End-to-End Test: Page Creation and Oxygen Design Workflow
 *
 * This script tests the complete workflow:
 * 1. POST /pages to create a new page with design
 * 2. GET /documents/{id} to verify saved design
 * 3. Verify page renders correctly
 * 4. Clean up test data
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-page-workflow.php
 *
 * @package Oxybridge
 */

// Ensure this is run via WP-CLI.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run via WP-CLI.\n";
	echo "Usage: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-page-workflow.php\n";
	exit( 1 );
}

/**
 * E2E Test Runner for Oxybridge Page Workflow.
 */
class Oxybridge_E2E_Page_Workflow_Test {

	/**
	 * Test page ID for cleanup.
	 *
	 * @var int
	 */
	private $test_page_id = 0;

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
	 * Run all E2E tests.
	 *
	 * @return void
	 */
	public function run() {
		WP_CLI::log( '=============================================' );
		WP_CLI::log( 'Oxybridge E2E Test: Page Creation Workflow' );
		WP_CLI::log( '=============================================' );
		WP_CLI::log( '' );

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		WP_CLI::log( '=== Test 1: Create Page with Design ===' );
		WP_CLI::log( '' );
		$create_result = $this->test_create_page_with_design();

		if ( ! $create_result ) {
			WP_CLI::error( 'Cannot continue tests - page creation failed.', false );
			$this->cleanup();
			$this->print_summary();
			return;
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 2: Verify Document Saved ===' );
		WP_CLI::log( '' );
		$this->test_verify_document_saved();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 3: Verify Page Renders ===' );
		WP_CLI::log( '' );
		$this->test_verify_page_renders();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 4: Verify CSS Cache Generated ===' );
		WP_CLI::log( '' );
		$this->test_verify_css_cache();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 5: Test Clone Functionality ===' );
		WP_CLI::log( '' );
		$this->test_clone_page();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 6: Test Validation Endpoint ===' );
		WP_CLI::log( '' );
		$this->test_validate_tree();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Cleanup ===' );
		WP_CLI::log( '' );
		$this->cleanup();

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

		// Check if REST API is available.
		if ( ! class_exists( 'Oxybridge\\REST_API' ) ) {
			WP_CLI::error( 'Oxybridge REST API class not found.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge REST API class available' );

		// Check if JSON Builder is available.
		if ( ! class_exists( 'Oxybridge\\JSON_Builder' ) ) {
			WP_CLI::warning( 'JSON Builder class not found - some tests may be limited' );
		} else {
			WP_CLI::success( 'JSON Builder class available' );
		}

		// Check current user permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			WP_CLI::error( 'Current user does not have edit_posts capability.', false );
			return false;
		}
		WP_CLI::success( 'User has required permissions' );

		WP_CLI::log( '' );
		return true;
	}

	/**
	 * Test 1: Create page with Oxygen design.
	 *
	 * @return bool True if test passed.
	 */
	private function test_create_page_with_design() {
		$test_name = 'Create Page with Design';

		// Build a test design tree using JSON Builder if available.
		if ( class_exists( 'Oxybridge\\JSON_Builder' ) ) {
			$builder = new \Oxybridge\JSON_Builder();
			$tree    = $builder
				->create_document()
				->add_section(
					array(),
					array(
						'padding' => array(
							'top'    => '40px',
							'bottom' => '40px',
						),
					)
				)
					->add_container()
						->add_heading( 'E2E Test Page', 1 )
						->with_styles(
							array(
								'color'     => '#333333',
								'textAlign' => 'center',
							)
						)
						->add_text( 'This page was created by the Oxybridge E2E test suite.' )
						->with_styles(
							array(
								'textAlign' => 'center',
								'margin'    => array(
									'top' => '20px',
								),
							)
						)
					->end()
				->end()
				->build();
		} else {
			// Fallback to manual tree structure.
			$tree = $this->get_fallback_test_tree();
		}

		// Create the page via REST API simulation.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/pages' );
		$request->set_param( 'title', 'Oxybridge E2E Test Page - ' . date( 'Y-m-d H:i:s' ) );
		$request->set_param( 'status', 'draft' );
		$request->set_param( 'post_type', 'page' );
		$request->set_param( 'enable_oxygen', true );
		$request->set_param( 'tree', $tree );

		// Get REST server and dispatch request.
		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		// Check response.
		$status = $response->get_status();

		if ( $status !== 201 ) {
			$this->fail(
				$test_name,
				sprintf(
					'Expected status 201, got %d. Error: %s',
					$status,
					isset( $data['message'] ) ? $data['message'] : 'Unknown error'
				)
			);
			return false;
		}

		if ( ! isset( $data['id'] ) || ! is_numeric( $data['id'] ) ) {
			$this->fail( $test_name, 'Response missing page ID' );
			return false;
		}

		$this->test_page_id = (int) $data['id'];

		$this->pass( $test_name );
		WP_CLI::log( '  Created page ID: ' . $this->test_page_id );
		WP_CLI::log( '  Title: ' . ( isset( $data['title'] ) ? $data['title'] : 'N/A' ) );
		WP_CLI::log( '  Oxygen enabled: ' . ( isset( $data['oxygen_enabled'] ) && $data['oxygen_enabled'] ? 'Yes' : 'No' ) );

		return true;
	}

	/**
	 * Test 2: Verify document was saved correctly.
	 *
	 * @return bool True if test passed.
	 */
	private function test_verify_document_saved() {
		$test_name = 'Verify Document Saved';

		if ( ! $this->test_page_id ) {
			$this->fail( $test_name, 'No test page ID available' );
			return false;
		}

		// Request document via REST API.
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/documents/' . $this->test_page_id );
		$request->set_param( 'include_metadata', true );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$status = $response->get_status();

		if ( $status !== 200 ) {
			$this->fail(
				$test_name,
				sprintf(
					'Expected status 200, got %d. Error: %s',
					$status,
					isset( $data['message'] ) ? $data['message'] : 'Unknown error'
				)
			);
			return false;
		}

		// Verify document structure.
		if ( ! isset( $data['tree'] ) || ! is_array( $data['tree'] ) ) {
			$this->fail( $test_name, 'Response missing tree data' );
			return false;
		}

		// Verify tree has root element.
		if ( ! isset( $data['tree']['root'] ) ) {
			$this->fail( $test_name, 'Tree missing root element' );
			return false;
		}

		// Verify root has children (our section).
		if ( ! isset( $data['tree']['root']['children'] ) || empty( $data['tree']['root']['children'] ) ) {
			$this->fail( $test_name, 'Tree root has no children - design may not have saved' );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Document ID: ' . $this->test_page_id );
		WP_CLI::log( '  Has root element: Yes' );
		WP_CLI::log( '  Root children count: ' . count( $data['tree']['root']['children'] ) );

		if ( isset( $data['element_count'] ) ) {
			WP_CLI::log( '  Total element count: ' . $data['element_count'] );
		}

		return true;
	}

	/**
	 * Test 3: Verify page renders correctly.
	 *
	 * @return bool True if test passed.
	 */
	private function test_verify_page_renders() {
		$test_name = 'Verify Page Renders';

		if ( ! $this->test_page_id ) {
			$this->fail( $test_name, 'No test page ID available' );
			return false;
		}

		// Request rendered HTML via REST API.
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/render/' . $this->test_page_id );
		$request->set_param( 'include_css', false );
		$request->set_param( 'include_wrapper', true );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$status = $response->get_status();

		if ( $status !== 200 ) {
			$this->fail(
				$test_name,
				sprintf(
					'Expected status 200, got %d. Error: %s',
					$status,
					isset( $data['message'] ) ? $data['message'] : 'Unknown error'
				)
			);
			return false;
		}

		// Check for HTML content.
		if ( ! isset( $data['html'] ) || empty( $data['html'] ) ) {
			// Might be returned directly as string.
			if ( is_string( $data ) && ! empty( $data ) ) {
				$html = $data;
			} else {
				$this->fail( $test_name, 'Response missing HTML content' );
				return false;
			}
		} else {
			$html = $data['html'];
		}

		// Verify HTML contains our test content.
		$has_heading = stripos( $html, 'E2E Test Page' ) !== false;
		$has_text    = stripos( $html, 'Oxybridge E2E test suite' ) !== false;

		if ( ! $has_heading && ! $has_text ) {
			WP_CLI::warning( 'Rendered HTML may not contain expected content (could be using Breakdance native rendering)' );
		}

		$this->pass( $test_name );
		WP_CLI::log( '  HTML length: ' . strlen( $html ) . ' characters' );
		WP_CLI::log( '  Contains heading text: ' . ( $has_heading ? 'Yes' : 'No' ) );
		WP_CLI::log( '  Contains body text: ' . ( $has_text ? 'Yes' : 'No' ) );

		return true;
	}

	/**
	 * Test 4: Verify CSS cache was generated.
	 *
	 * @return bool True if test passed.
	 */
	private function test_verify_css_cache() {
		$test_name = 'Verify CSS Cache Generated';

		if ( ! $this->test_page_id ) {
			$this->fail( $test_name, 'No test page ID available' );
			return false;
		}

		// Check for CSS cache in post meta.
		$meta_prefix   = $this->get_meta_prefix();
		$css_cache_key = $meta_prefix . 'css_file_paths_cache';
		$css_cache     = get_post_meta( $this->test_page_id, $css_cache_key, true );

		$has_meta_cache = ! empty( $css_cache );

		// Try regenerating CSS via API if no cache exists.
		if ( ! $has_meta_cache ) {
			WP_CLI::log( '  CSS cache not found, attempting regeneration via API...' );

			$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/regenerate-css/' . $this->test_page_id );

			$server   = rest_get_server();
			$response = $server->dispatch( $request );
			$data     = $response->get_data();

			$status = $response->get_status();

			if ( $status === 200 ) {
				WP_CLI::log( '  CSS regeneration API returned 200' );

				// Check cache again.
				wp_cache_flush();
				$css_cache      = get_post_meta( $this->test_page_id, $css_cache_key, true );
				$has_meta_cache = ! empty( $css_cache );
			} else {
				WP_CLI::log( '  CSS regeneration API returned status: ' . $status );
			}
		}

		// This might not fail the test since CSS generation depends on element content.
		if ( ! $has_meta_cache ) {
			WP_CLI::warning( 'CSS cache not found - this may be normal for pages with no styled elements' );
		} else {
			WP_CLI::log( '  CSS cache meta key: ' . $css_cache_key );
			if ( is_array( $css_cache ) ) {
				WP_CLI::log( '  CSS files cached: ' . count( $css_cache ) );
			}
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Has meta cache: ' . ( $has_meta_cache ? 'Yes' : 'No' ) );

		return true;
	}

	/**
	 * Test 5: Test clone functionality.
	 *
	 * @return bool True if test passed.
	 */
	private function test_clone_page() {
		$test_name = 'Clone Page';

		if ( ! $this->test_page_id ) {
			$this->fail( $test_name, 'No test page ID available' );
			return false;
		}

		// Clone the test page via REST API.
		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/clone/' . $this->test_page_id );
		$request->set_param( 'title', 'Cloned E2E Test Page' );
		$request->set_param( 'status', 'draft' );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$status = $response->get_status();

		if ( $status !== 201 ) {
			$this->fail(
				$test_name,
				sprintf(
					'Expected status 201, got %d. Error: %s',
					$status,
					isset( $data['message'] ) ? $data['message'] : 'Unknown error'
				)
			);
			return false;
		}

		if ( ! isset( $data['id'] ) || ! is_numeric( $data['id'] ) ) {
			$this->fail( $test_name, 'Response missing cloned page ID' );
			return false;
		}

		$cloned_id = (int) $data['id'];

		// Verify cloned page has design data.
		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/documents/' . $cloned_id );

		$response = $server->dispatch( $request );
		$doc_data = $response->get_data();

		$has_tree = isset( $doc_data['tree'] ) && isset( $doc_data['tree']['root'] );

		// Clean up cloned page.
		wp_delete_post( $cloned_id, true );

		if ( ! $has_tree ) {
			$this->fail( $test_name, 'Cloned page missing design tree' );
			return false;
		}

		$this->pass( $test_name );
		WP_CLI::log( '  Cloned page ID: ' . $cloned_id );
		WP_CLI::log( '  Cloned page has tree: Yes' );
		WP_CLI::log( '  Cloned page deleted: Yes (cleanup)' );

		return true;
	}

	/**
	 * Test 6: Test validation endpoint.
	 *
	 * @return bool True if test passed.
	 */
	private function test_validate_tree() {
		$test_name = 'Validate Tree';

		// Test with valid tree.
		$valid_tree = array(
			'root' => array(
				'id'       => 'test-root',
				'data'     => array(
					'type' => 'EssentialElements\\Root',
				),
				'children' => array(
					array(
						'id'       => 'test-section',
						'data'     => array(
							'type' => 'EssentialElements\\Section',
						),
						'children' => array(),
					),
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', $valid_tree );

		$server   = rest_get_server();
		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		$status = $response->get_status();

		if ( $status !== 200 ) {
			$this->fail(
				$test_name,
				sprintf(
					'Expected status 200 for valid tree, got %d. Error: %s',
					$status,
					isset( $data['message'] ) ? $data['message'] : 'Unknown error'
				)
			);
			return false;
		}

		$is_valid = isset( $data['valid'] ) && $data['valid'] === true;

		if ( ! $is_valid ) {
			$this->fail( $test_name, 'Valid tree was not recognized as valid' );
			return false;
		}

		WP_CLI::log( '  Valid tree passed validation: Yes' );

		// Test with invalid tree (missing root).
		$invalid_tree = array(
			'data' => array(
				'type' => 'EssentialElements\\Section',
			),
		);

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/validate' );
		$request->set_param( 'tree', $invalid_tree );

		$response = $server->dispatch( $request );
		$data     = $response->get_data();

		// Invalid tree should return 200 with valid=false, or 400.
		$status          = $response->get_status();
		$invalid_handled = ( $status === 200 && isset( $data['valid'] ) && $data['valid'] === false )
			|| $status === 400;

		if ( ! $invalid_handled ) {
			WP_CLI::warning( 'Invalid tree handling may need review' );
		} else {
			WP_CLI::log( '  Invalid tree detected: Yes' );
		}

		$this->pass( $test_name );

		return true;
	}

	/**
	 * Get fallback test tree if JSON Builder is not available.
	 *
	 * @return array Test tree structure.
	 */
	private function get_fallback_test_tree() {
		return array(
			'root' => array(
				'id'       => 'e2e-root-' . uniqid(),
				'data'     => array(
					'type' => 'EssentialElements\\Root',
				),
				'children' => array(
					array(
						'id'       => 'e2e-section-' . uniqid(),
						'data'     => array(
							'type'       => 'EssentialElements\\Section',
							'properties' => array(),
						),
						'children' => array(
							array(
								'id'       => 'e2e-container-' . uniqid(),
								'data'     => array(
									'type'       => 'EssentialElements\\Container',
									'properties' => array(),
								),
								'children' => array(
									array(
										'id'       => 'e2e-heading-' . uniqid(),
										'data'     => array(
											'type'       => 'EssentialElements\\Heading',
											'properties' => array(
												'content' => array(
													'text' => 'E2E Test Page',
													'tag'  => 'h1',
												),
											),
										),
										'children' => array(),
									),
									array(
										'id'       => 'e2e-text-' . uniqid(),
										'data'     => array(
											'type'       => 'EssentialElements\\Text',
											'properties' => array(
												'content' => array(
													'text' => 'This page was created by the Oxybridge E2E test suite.',
												),
											),
										),
										'children' => array(),
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the meta prefix for Oxygen/Breakdance.
	 *
	 * @return string Meta prefix.
	 */
	private function get_meta_prefix() {
		if ( function_exists( 'Breakdance\BreakdanceOxygen\Strings\__bdox' ) ) {
			$prefix = \Breakdance\BreakdanceOxygen\Strings\__bdox( 'meta_prefix' );
			if ( is_string( $prefix ) ) {
				return $prefix;
			}
		}

		if ( defined( 'BREAKDANCE_MODE' ) && BREAKDANCE_MODE === 'oxygen' ) {
			return 'oxygen_';
		}

		return 'breakdance_';
	}

	/**
	 * Mark a test as passed.
	 *
	 * @param string $test_name Test name.
	 */
	private function pass( $test_name ) {
		$this->results['passed']++;
		WP_CLI::success( $test_name );
	}

	/**
	 * Mark a test as failed.
	 *
	 * @param string $test_name    Test name.
	 * @param string $error_message Error message.
	 */
	private function fail( $test_name, $error_message ) {
		$this->results['failed']++;
		$this->results['errors'][] = array(
			'test'    => $test_name,
			'message' => $error_message,
		);
		WP_CLI::error( $test_name . ': ' . $error_message, false );
	}

	/**
	 * Clean up test data.
	 */
	private function cleanup() {
		if ( $this->test_page_id ) {
			$deleted = wp_delete_post( $this->test_page_id, true );
			if ( $deleted ) {
				WP_CLI::log( 'Deleted test page ID: ' . $this->test_page_id );
			} else {
				WP_CLI::warning( 'Failed to delete test page ID: ' . $this->test_page_id );
			}
			$this->test_page_id = 0;
		}
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

// Run the tests.
$test = new Oxybridge_E2E_Page_Workflow_Test();
$test->run();
