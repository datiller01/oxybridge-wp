<?php
/**
 * Test: End-to-End Template Creation and Builder Verification
 *
 * This script tests the complete flow of:
 * 1. Creating a template via the REST API
 * 2. Verifying the response structure includes _nextNodeId
 * 3. Providing the edit_url for manual browser verification
 * 4. Documenting expected browser console behavior (no IO-TS errors)
 *
 * Run via WP-CLI: wp eval-file wp-content/plugins/oxybridge-wp/tests/test-e2e-template-creation.php
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
 * E2E Template Creation Test Runner.
 */
class Oxybridge_E2E_Template_Test {

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
	 * Test template IDs for cleanup.
	 *
	 * @var array
	 */
	private $test_template_ids = array();

	/**
	 * Run all E2E tests.
	 *
	 * @return void
	 */
	public function run() {
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'Oxybridge E2E Template Creation Test' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( '' );

		// Check prerequisites.
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 1: Create Header Template ===' );
		WP_CLI::log( '' );
		$header_id = $this->test_create_header_template();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 2: Verify Template Tree Structure ===' );
		WP_CLI::log( '' );
		if ( $header_id ) {
			$this->test_template_tree_structure( $header_id );
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 3: Create Footer Template ===' );
		WP_CLI::log( '' );
		$footer_id = $this->test_create_footer_template();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 4: Test Template with Elements ===' );
		WP_CLI::log( '' );
		$this->test_template_with_elements();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Test 5: Verify Document Endpoint ===' );
		WP_CLI::log( '' );
		if ( $header_id ) {
			$this->test_document_endpoint( $header_id );
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Manual Verification Required ===' );
		WP_CLI::log( '' );
		$this->print_manual_verification_steps();

		WP_CLI::log( '' );
		WP_CLI::log( '=== Cleanup ===' );
		WP_CLI::log( '' );
		$this->cleanup();

		$this->print_summary();
	}

	/**
	 * Check prerequisites.
	 *
	 * @return bool True if prerequisites are met.
	 */
	private function check_prerequisites() {
		WP_CLI::log( 'Checking prerequisites...' );

		// Check if Oxybridge plugin is active.
		if ( ! defined( 'OXYBRIDGE_VERSION' ) ) {
			WP_CLI::error( 'Oxybridge plugin is not active.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge plugin active: v' . OXYBRIDGE_VERSION );

		// Check if REST API is available.
		if ( ! class_exists( 'Oxybridge\\REST_API' ) ) {
			WP_CLI::error( 'Oxybridge REST API class not found.', false );
			return false;
		}
		WP_CLI::success( 'Oxybridge REST API class available' );

		// Check current user permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			WP_CLI::error( 'Current user does not have edit_posts capability.', false );
			return false;
		}
		WP_CLI::success( 'User has required permissions' );

		return true;
	}

	/**
	 * Test creating a header template.
	 *
	 * @return int|false Template ID or false on failure.
	 */
	private function test_create_header_template() {
		$server = rest_get_server();

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', 'E2E Test Header - ' . date( 'Y-m-d H:i:s' ) );
		$request->set_param( 'type', 'header' );

		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 && $status !== 201 ) {
			$this->fail( 'POST /templates creates header template', "Status: {$status}" );
			return false;
		}

		$this->pass( 'POST /templates creates header template' );

		// Store for cleanup.
		if ( isset( $data['template']['id'] ) ) {
			$template_id = $data['template']['id'];
			$this->test_template_ids[] = $template_id;
			WP_CLI::log( "    Created template ID: {$template_id}" );

			if ( isset( $data['template']['edit_url'] ) ) {
				WP_CLI::log( "    Edit URL: {$data['template']['edit_url']}" );
			}

			return $template_id;
		}

		return false;
	}

	/**
	 * Test template tree structure has required properties.
	 *
	 * @param int $template_id Template ID.
	 */
	private function test_template_tree_structure( $template_id ) {
		$server = rest_get_server();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $template_id );
		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 ) {
			$this->fail( 'GET /templates/{id} returns template', "Status: {$status}" );
			return;
		}

		$this->pass( 'GET /templates/{id} returns template' );

		// Test 1: Response should include tree.
		if ( ! isset( $data['tree'] ) ) {
			$this->fail( 'Template response includes tree', 'Missing tree' );
			return;
		}
		$this->pass( 'Template response includes tree' );

		// Test 2: Tree should have root.
		if ( ! isset( $data['tree']['root'] ) ) {
			$this->fail( 'Template tree has root', 'Missing root' );
			return;
		}
		$this->pass( 'Template tree has root' );

		// Test 3: Tree should have _nextNodeId (CRITICAL for IO-TS).
		if ( isset( $data['tree']['_nextNodeId'] ) && is_int( $data['tree']['_nextNodeId'] ) ) {
			$this->pass( 'Template tree has _nextNodeId (IO-TS required)' );
			WP_CLI::log( "    _nextNodeId value: {$data['tree']['_nextNodeId']}" );
		} else {
			$this->fail( 'Template tree has _nextNodeId', 'Property missing or not an integer' );
			WP_CLI::log( '    This is REQUIRED for Oxygen Builder IO-TS validation!' );

			// Debug output.
			WP_CLI::log( '    Tree keys present: ' . implode( ', ', array_keys( $data['tree'] ) ) );
		}

		// Test 4: Tree should have exportedLookupTable.
		if ( isset( $data['tree']['exportedLookupTable'] ) ) {
			$this->pass( 'Template tree has exportedLookupTable' );
		} else {
			$this->fail( 'Template tree has exportedLookupTable', 'Property missing' );
		}

		// Test 5: Tree should have status.
		if ( isset( $data['tree']['status'] ) && $data['tree']['status'] === 'exported' ) {
			$this->pass( 'Template tree has status "exported"' );
		} else {
			$this->fail( 'Template tree has status', 'Missing or wrong value' );
		}

		// Test 6: Root should have correct structure.
		$root = $data['tree']['root'];
		$has_correct_root = isset( $root['id'] ) &&
			isset( $root['data'] ) &&
			isset( $root['children'] ) &&
			is_array( $root['children'] );

		if ( $has_correct_root ) {
			$this->pass( 'Template root has correct structure (id, data, children)' );
		} else {
			$this->fail( 'Template root structure', 'Missing required keys' );
		}

		// Test 7: Root data type should be "root".
		if ( isset( $root['data']['type'] ) && $root['data']['type'] === 'root' ) {
			$this->pass( 'Template root type is "root"' );
		} else {
			$this->fail( 'Template root type', 'Type is not "root"' );
		}

		// Test 8: Validate with Validator class.
		if ( class_exists( 'Oxybridge\\Validator' ) ) {
			$validator = new \Oxybridge\Validator();
			$result = $validator->validate_tree_has_next_node_id( $data['tree'] );

			if ( $result['valid'] === true ) {
				$this->pass( 'Validator confirms tree has _nextNodeId' );
			} else {
				$this->fail( 'Validator tree check', $result['errors'][0]['message'] ?? 'Validation failed' );
			}
		}
	}

	/**
	 * Test creating a footer template.
	 *
	 * @return int|false Template ID or false on failure.
	 */
	private function test_create_footer_template() {
		$server = rest_get_server();

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', 'E2E Test Footer - ' . date( 'Y-m-d H:i:s' ) );
		$request->set_param( 'type', 'footer' );

		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 && $status !== 201 ) {
			$this->fail( 'POST /templates creates footer template', "Status: {$status}" );
			return false;
		}

		$this->pass( 'POST /templates creates footer template' );

		if ( isset( $data['template']['id'] ) ) {
			$template_id = $data['template']['id'];
			$this->test_template_ids[] = $template_id;
			WP_CLI::log( "    Created template ID: {$template_id}" );

			// Verify footer has _nextNodeId.
			$request2 = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $template_id );
			$response2 = $server->dispatch( $request2 );
			$data2 = $response2->get_data();

			if ( isset( $data2['tree']['_nextNodeId'] ) ) {
				$this->pass( 'Footer template tree has _nextNodeId' );
			} else {
				$this->fail( 'Footer template tree has _nextNodeId', 'Missing' );
			}

			return $template_id;
		}

		return false;
	}

	/**
	 * Test creating a template with elements.
	 */
	private function test_template_with_elements() {
		$server = rest_get_server();

		// Create a template with a tree that has elements.
		$tree = array(
			'root' => array(
				'id'       => 'el-root',
				'data'     => array(
					'type'       => 'root',
					'properties' => null,
				),
				'children' => array(
					array(
						'id'       => 'el-1',
						'data'     => array(
							'type'       => 'EssentialElements\\Section',
							'properties' => array(
								'layout' => array( 'padding' => '20px' ),
							),
						),
						'children' => array(
							array(
								'id'   => 'el-5',
								'data' => array(
									'type'       => 'EssentialElements\\Heading',
									'properties' => array(
										'content' => array( 'text' => 'Hello World' ),
									),
								),
								'children' => array(),
							),
						),
					),
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/' . $this->namespace . '/templates' );
		$request->set_param( 'title', 'E2E Test With Elements - ' . date( 'Y-m-d H:i:s' ) );
		$request->set_param( 'type', 'header' );
		$request->set_param( 'tree', $tree );

		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 && $status !== 201 ) {
			$this->fail( 'POST /templates with tree creates template', "Status: {$status}" );
			return;
		}

		$this->pass( 'POST /templates with tree creates template' );

		if ( isset( $data['template']['id'] ) ) {
			$template_id = $data['template']['id'];
			$this->test_template_ids[] = $template_id;

			// Get the template and check _nextNodeId.
			$request2 = new WP_REST_Request( 'GET', '/' . $this->namespace . '/templates/' . $template_id );
			$response2 = $server->dispatch( $request2 );
			$data2 = $response2->get_data();

			if ( isset( $data2['tree']['_nextNodeId'] ) ) {
				$next_id = $data2['tree']['_nextNodeId'];
				// Max ID in tree is 5 (el-5), so _nextNodeId should be 6.
				if ( $next_id >= 6 ) {
					$this->pass( "Template with elements has correct _nextNodeId ({$next_id})" );
				} else {
					$this->fail( "Template _nextNodeId calculation", "Expected >= 6, got {$next_id}" );
				}
			} else {
				$this->fail( 'Template with elements has _nextNodeId', 'Missing' );
			}
		}
	}

	/**
	 * Test the document endpoint.
	 *
	 * @param int $post_id Post ID.
	 */
	private function test_document_endpoint( $post_id ) {
		$server = rest_get_server();

		$request = new WP_REST_Request( 'GET', '/' . $this->namespace . '/documents/' . $post_id );
		$request->set_param( 'include_metadata', true );

		$response = $server->dispatch( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		if ( $status !== 200 ) {
			$this->fail( 'GET /documents/{id} returns document', "Status: {$status}" );
			return;
		}

		$this->pass( 'GET /documents/{id} returns document' );

		// Test tree has required properties.
		if ( isset( $data['tree']['_nextNodeId'] ) ) {
			$this->pass( 'Document tree has _nextNodeId' );
		} else {
			$this->fail( 'Document tree has _nextNodeId', 'Missing' );
		}

		if ( isset( $data['tree']['status'] ) ) {
			$this->pass( 'Document tree has status' );
		} else {
			$this->fail( 'Document tree has status', 'Missing' );
		}
	}

	/**
	 * Print manual verification steps.
	 */
	private function print_manual_verification_steps() {
		WP_CLI::log( 'The following steps require MANUAL browser verification:' );
		WP_CLI::log( '' );
		WP_CLI::log( '1. Open any created template in Oxygen/Breakdance Builder' );
		WP_CLI::log( '   - Use the edit_url provided in the test output above' );
		WP_CLI::log( '' );
		WP_CLI::log( '2. Open browser Developer Tools (F12 or Cmd+Option+I)' );
		WP_CLI::log( '' );
		WP_CLI::log( '3. Check the Console tab for errors' );
		WP_CLI::log( '   - PASS: No "IO-TS validation failed" errors' );
		WP_CLI::log( '   - FAIL: "IO-TS validation failed" error with message about _nextNodeId' );
		WP_CLI::log( '' );
		WP_CLI::log( '4. Expected behavior:' );
		WP_CLI::log( '   - Builder should load without console errors' );
		WP_CLI::log( '   - Template content should be editable' );
		WP_CLI::log( '   - No "Property document.tree._nextNodeId is missing" errors' );
		WP_CLI::log( '' );

		if ( ! empty( $this->test_template_ids ) ) {
			$site_url = get_site_url();
			WP_CLI::log( 'Test template edit URLs:' );
			foreach ( $this->test_template_ids as $id ) {
				$post = get_post( $id );
				if ( $post ) {
					$edit_url = $site_url . '/' . $post->post_type . '/' . $post->post_name . '/?oxygen=builder';
					WP_CLI::log( "  - Template {$id}: {$edit_url}" );
				}
			}
		}
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
	 * @param string $test_name     Test name.
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
		foreach ( $this->test_template_ids as $id ) {
			$deleted = wp_delete_post( $id, true );
			if ( $deleted ) {
				WP_CLI::log( 'Deleted test template ID: ' . $id );
			}
		}
		$this->test_template_ids = array();
	}

	/**
	 * Print test summary.
	 */
	private function print_summary() {
		WP_CLI::log( '' );
		WP_CLI::log( '========================================================' );
		WP_CLI::log( 'E2E Template Creation Test Summary' );
		WP_CLI::log( '========================================================' );
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
			WP_CLI::success( 'All automated E2E tests passed!' );
			WP_CLI::log( '' );
			WP_CLI::log( 'IMPORTANT: Manual browser verification is still required.' );
			WP_CLI::log( 'See "Manual Verification Required" section above.' );
		} else {
			WP_CLI::warning( sprintf( '%d test(s) failed - review output above.', $this->results['failed'] ) );
		}

		// IO-TS verification summary.
		WP_CLI::log( '' );
		WP_CLI::log( 'IO-TS Validation Requirements Verified:' );
		WP_CLI::log( '  - tree._nextNodeId: REQUIRED for document responses' );
		WP_CLI::log( '  - tree.root: REQUIRED for valid tree structure' );
		WP_CLI::log( '  - tree.status: REQUIRED (should be "exported")' );
		WP_CLI::log( '  - tree.exportedLookupTable: REQUIRED (empty object {})' );
	}
}

// Run the tests.
$test = new Oxybridge_E2E_Template_Test();
$test->run();
